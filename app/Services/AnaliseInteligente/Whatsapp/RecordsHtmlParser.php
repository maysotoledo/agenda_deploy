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

            'symmetric_contacts_total' => $symTotal,
            'asymmetric_contacts_total' => $asymTotal,

            'symmetric_contacts_count' => $symTotal,
            'asymmetric_contacts_count' => $asymTotal,

            'symmetric_contacts' => $symmetricContacts,
            'asymmetric_contacts' => $asymmetricContacts,

            // ✅ agora cada evento tem ip (base) + ip_with_port (display) + port
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

    private function extractAddressBookContacts(\DOMDocument $dom, ?int $symCount, ?int $asymCount): array
    {
        $sectionHtml = $this->extractPropertySectionHtml($dom, 'property-address_book_info');

        if ($sectionHtml === '') {
            return [
                'symmetric_contacts' => [],
                'asymmetric_contacts' => [],
            ];
        }

        $sectionHtml = preg_replace('/<div[^>]*class="p"[^>]*>\s*<\/div>/i', "\n", $sectionHtml) ?? $sectionHtml;
        $sectionHtml = preg_replace('/<br\s*\/?>/i', "\n", $sectionHtml) ?? $sectionHtml;

        $text = html_entity_decode(strip_tags($sectionHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t\r]+/', ' ', $text) ?? '';
        $text = preg_replace("/\n+/", "\n", $text) ?? '';
        $text = trim($text);

        $startPos = stripos($text, 'Symmetric contacts');
        if ($startPos !== false) {
            $text = substr($text, $startPos);
        }

        preg_match_all('/(?<!\d)(55\d{10,13})(?!\d)/', $text, $matches);
        $allPhones = array_values($matches[1] ?? []);

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
        $last = null; // ['ip' => base, 'ip_with_port' => display, 'port' => int|null]

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

                if ($last && $lastTime instanceof Carbon) {
                    $ipEvents[] = [
                        'ip' => $last['ip'], // base
                        'ip_with_port' => $last['ip_with_port'], // display
                        'port' => $last['port'],
                        'time_utc' => $lastTime->copy(),
                    ];
                }
            }

            if ($label === 'IP Address') {
                $parsed = $this->parseIpAndPort($value);
                $last = $parsed['ip'] ? $parsed : null;
            }
        }

        return $ipEvents;
    }

    /**
     * Retorna:
     * - ip: IP base (sem porta / sem colchetes)
     * - ip_with_port: string p/ exibição (mantém :porta e colchetes no IPv6)
     * - port: int|null
     */
    private function parseIpAndPort(?string $value): array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return ['ip' => null, 'ip_with_port' => null, 'port' => null];
        }

        // IPv6 [..]:port
        if (preg_match('/^\[([0-9a-fA-F:]+)\]:(\d{1,5})$/', $value, $m)) {
            $ipBase = trim($m[1]);
            $port = (int) $m[2];

            return [
                'ip' => $ipBase,
                'ip_with_port' => "[{$ipBase}]:{$port}",
                'port' => $port,
            ];
        }

        // IPv6 [..]
        if (preg_match('/^\[([0-9a-fA-F:]+)\]$/', $value, $m)) {
            $ipBase = trim($m[1]);

            return [
                'ip' => $ipBase,
                'ip_with_port' => "[{$ipBase}]",
                'port' => null,
            ];
        }

        // IPv4 ip:port
        if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3}):(\d{1,5})$/', $value, $m)) {
            $ipBase = trim($m[1]);
            $port = (int) $m[2];

            return [
                'ip' => $ipBase,
                'ip_with_port' => "{$ipBase}:{$port}",
                'port' => $port,
            ];
        }

        // IPv4 ip
        if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3})$/', $value, $m)) {
            $ipBase = trim($m[1]);

            return [
                'ip' => $ipBase,
                'ip_with_port' => $ipBase,
                'port' => null,
            ];
        }

        // fallback: mantém display e tenta base sem colchetes
        $ipBase = $value;
        if (preg_match('/^\[([^\]]+)\](?::\d+)?$/', $value, $m)) {
            $ipBase = trim($m[1]);
        }

        return [
            'ip' => $ipBase !== '' ? $ipBase : null,
            'ip_with_port' => $value,
            'port' => null,
        ];
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
