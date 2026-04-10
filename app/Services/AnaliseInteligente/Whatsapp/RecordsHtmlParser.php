<?php

namespace App\Services\AnaliseInteligente\Whatsapp;

use Carbon\Carbon;

class RecordsHtmlParser
{
    public function parse(string $html): array
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $xp = new \DOMXPath($dom);

        $target = $this->getSimpleValueByLabel($xp, 'Target');
        $generated = $this->getSimpleValueByLabel($xp, 'Generated');
        $dateRange = $this->getSimpleValueByLabel($xp, 'Date Range');

        $deviceInfo = $this->extractDeviceInfo($xp);
        $deviceBuild = $deviceInfo['device_build'];
        $device = $deviceInfo['device'];

        $symTotalFromLabel = $this->extractLeadingInt($this->getSimpleValueByLabel($xp, 'Symmetric contacts'));
        $asymTotalFromLabel = $this->extractLeadingInt($this->getSimpleValueByLabel($xp, 'Asymmetric contacts'));

        $contacts = $this->extractAddressBookContacts($dom, $symTotalFromLabel, $asymTotalFromLabel);
        $symmetricContacts = $contacts['symmetric_contacts'];
        $asymmetricContacts = $contacts['asymmetric_contacts'];

        $symTotal = $symTotalFromLabel ?? count($symmetricContacts);
        $asymTotal = $asymTotalFromLabel ?? count($asymmetricContacts);

        $ipEvents = $this->extractIpEvents($xp);

        [$rangeStartUtc, $rangeEndUtc] = $this->parseDateRangeUtc($dateRange);
        $generatedAtUtc = $this->parseUtc($generated);

        return [
            'target' => $target,
            'generated_at' => $generatedAtUtc,
            'date_range' => $dateRange,
            'range_start_utc' => $rangeStartUtc,
            'range_end_utc' => $rangeEndUtc,

            'device' => $device,
            'device_build' => $deviceBuild,

            // Totais informados no HTML (quando disponíveis)
            'symmetric_contacts_total' => $symTotal,
            'asymmetric_contacts_total' => $asymTotal,

            // Mantém compatibilidade com seu report/summary
            'symmetric_contacts_count' => $symTotal,
            'asymmetric_contacts_count' => $asymTotal,

            'symmetric_contacts' => $symmetricContacts,
            'asymmetric_contacts' => $asymmetricContacts,

            'ip_events' => $ipEvents,
        ];
    }

    private function getSimpleValueByLabel(\DOMXPath $xp, string $label): ?string
    {
        $query = "//div[contains(@class,'t i')][normalize-space(text())='{$label}']/div[contains(@class,'m')]/div";
        $nodes = $xp->query($query);

        if (! $nodes || $nodes->length === 0) {
            return null;
        }

        $value = trim($nodes->item(0)?->textContent ?? '');

        return $value !== '' ? $value : null;
    }

    private function extractDeviceInfo(\DOMXPath $xp): array
    {
        $manufacturer = null;
        $model = null;
        $deviceBuild = null;

        $deviceInfoBlocks = $xp->query("//div[contains(@class,'t i')][normalize-space(text())='Device Info']/div[contains(@class,'m')]");

        if ($deviceInfoBlocks && $deviceInfoBlocks->length > 0) {
            $container = $deviceInfoBlocks->item(0);
            $rows = $xp->query(".//div[contains(@class,'t o')]", $container);

            if ($rows) {
                foreach ($rows as $row) {
                    $labelNode = $xp->query(".//div[contains(@class,'t i')]", $row)?->item(0);

                    if (! $labelNode) {
                        continue;
                    }

                    $labelText = trim($labelNode->childNodes->item(0)?->textContent ?? $labelNode->textContent ?? '');
                    $valueNode = null;

                    foreach ($labelNode->childNodes as $child) {
                        if ($child instanceof \DOMElement && str_contains($child->getAttribute('class'), 'm')) {
                            $valueNode = $child->getElementsByTagName('div')->item(0);
                            break;
                        }
                    }

                    $valueText = trim($valueNode?->textContent ?? '');
                    if ($valueText === '') {
                        continue;
                    }

                    if (strcasecmp($labelText, 'Device Manufacturer') === 0) {
                        $manufacturer = $valueText;
                    }

                    if (strcasecmp($labelText, 'Device Model') === 0) {
                        $model = $valueText;
                    }

                    if (
                        strcasecmp($labelText, 'OS Version') === 0 ||
                        strcasecmp($labelText, 'Device OS Build Number') === 0
                    ) {
                        $deviceBuild = $valueText;
                    }
                }
            }
        }

        $deviceBuild ??= $this->getSimpleValueByLabel($xp, 'Device OS Build Number');

        $device = null;

        if ($manufacturer && $model) {
            $device = "{$manufacturer} - {$model}";
        } elseif ($model) {
            $device = $model;
        } elseif ($manufacturer) {
            $device = $manufacturer;
        }

        return [
            'device' => $device,
            'device_build' => $deviceBuild,
        ];
    }

    /**
     * ✅ FIX PRINCIPAL:
     * Antes de strip_tags(), substitui os separadores <div class="p"></div> por \n,
     * para os telefones NÃO ficarem colados.
     */
    private function extractAddressBookContacts(\DOMDocument $dom, ?int $symCount, ?int $asymCount): array
    {
        $sectionHtml = $this->extractPropertySectionHtml($dom, 'property-address_book_info');

        if ($sectionHtml === '') {
            return [
                'symmetric_contacts' => [],
                'asymmetric_contacts' => [],
            ];
        }

        // separadores do HTML
        $sectionHtml = preg_replace('/<div[^>]*class="p"[^>]*>\s*<\/div>/i', "\n", $sectionHtml) ?? $sectionHtml;
        $sectionHtml = preg_replace('/<br\s*\/?>/i', "\n", $sectionHtml) ?? $sectionHtml;

        $text = html_entity_decode(strip_tags($sectionHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t\r]+/', ' ', $text) ?? '';
        $text = preg_replace("/\n+/", "\n", $text) ?? '';
        $text = trim($text);

        // recorta a partir da área de contatos (opcional, ajuda performance)
        $startPos = stripos($text, 'Symmetric contacts');
        if ($startPos !== false) {
            $text = substr($text, $startPos);
        }

        // Agora os números não ficam colados => boundary funciona
        preg_match_all('/(?<!\d)(55\d{10,13})(?!\d)/', $text, $matches);
        $allPhones = array_values($matches[1] ?? []);

        // fallback extra: se por algum motivo vier vazio, tenta pegar qualquer sequência grande de dígitos
        if (count($allPhones) === 0) {
            preg_match_all('/\d{10,14}/', $text, $m2);
            $cands = array_values($m2[0] ?? []);
            $allPhones = array_values(array_filter(array_map(function ($n) {
                $n = preg_replace('/\D+/', '', (string) $n) ?? '';
                return str_starts_with($n, '55') ? $n : null;
            }, $cands)));
        }

        $symmetric = [];
        $asymmetric = [];

        if (($symCount ?? 0) > 0) {
            $symmetric = array_slice($allPhones, 0, $symCount);
        }

        if (($asymCount ?? 0) > 0) {
            $asymmetric = array_slice($allPhones, $symCount ?? 0, $asymCount);
        }

        return [
            'symmetric_contacts' => array_values(array_unique($symmetric)),
            'asymmetric_contacts' => array_values(array_unique($asymmetric)),
        ];
    }

    private function extractPropertySectionHtml(\DOMDocument $dom, string $propertyId): string
    {
        $target = $dom->getElementById($propertyId);

        if (! $target) {
            return '';
        }

        $html = '';
        $node = $target;

        while ($node) {
            if ($node instanceof \DOMElement) {
                $id = $node->getAttribute('id');

                if ($node !== $target && $id !== '' && str_starts_with($id, 'property-')) {
                    break;
                }

                $html .= $dom->saveHTML($node);
            }

            $node = $node->nextSibling;
        }

        return $html;
    }

    private function extractIpEvents(\DOMXPath $xp): array
    {
        $labels = $xp->query("//div[contains(@class,'t i')][normalize-space(text())='Time' or normalize-space(text())='IP Address']");

        $ipEvents = [];
        $lastTime = null;

        if (! $labels) {
            return $ipEvents;
        }

        foreach ($labels as $labelNode) {
            $label = trim($labelNode->childNodes->item(0)?->textContent ?? $labelNode->textContent ?? '');

            $valueNode = null;

            foreach ($labelNode->childNodes as $child) {
                if ($child instanceof \DOMElement && str_contains($child->getAttribute('class'), 'm')) {
                    $valueNode = $child->getElementsByTagName('div')->item(0);
                    break;
                }
            }

            $value = trim($valueNode?->textContent ?? '');

            if ($label === 'Time') {
                $lastTime = $this->parseUtc($value);
            }

            if ($label === 'IP Address') {
                $ip = trim($value);

                if ($ip !== '' && $lastTime instanceof Carbon) {
                    $ipEvents[] = [
                        'ip' => $ip,
                        'time_utc' => $lastTime->copy(),
                    ];
                }
            }
        }

        return $ipEvents;
    }

    private function parseUtc(?string $value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = str_replace(' UTC', '', $value);

        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $value, 'UTC');
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseDateRangeUtc(?string $range): array
    {
        $range = trim((string) $range);

        if ($range === '' || ! str_contains($range, ' to ')) {
            return [null, null];
        }

        [$a, $b] = explode(' to ', $range, 2);

        return [
            $this->parseUtc($a),
            $this->parseUtc($b),
        ];
    }

    private function extractLeadingInt(?string $text): ?int
    {
        $text = trim((string) $text);

        if ($text === '') {
            return null;
        }

        if (preg_match('/^(\d+)/', $text, $m)) {
            return (int) $m[1];
        }

        return null;
    }
}
