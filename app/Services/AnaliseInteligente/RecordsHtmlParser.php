<?php

namespace App\Services\AnaliseInteligente;

use Carbon\Carbon;

class RecordsHtmlParser
{
    public function parse(string $html): array
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $xp = new \DOMXPath($dom);

        $getValueByLabel = function (string $label) use ($xp): ?string {
            $q = "//div[contains(@class,'t i')][normalize-space(text())='{$label}']/div[contains(@class,'m')]/div";
            $nodes = $xp->query($q);
            if (! $nodes || $nodes->length === 0) return null;
            return trim($nodes->item(0)->textContent ?? '');
        };

        $target = $getValueByLabel('Target');
        $dateRange = $getValueByLabel('Date Range');
        $deviceBuild = $getValueByLabel('Device OS Build Number');

        $symTotal = $this->extractLeadingInt($getValueByLabel('Symmetric contacts'));
        $asymTotal = $this->extractLeadingInt($getValueByLabel('Asymmetric contacts'));

        $labels = $xp->query("//div[contains(@class,'t i')][normalize-space(text())='Time' or normalize-space(text())='IP Address']");
        $ipEvents = [];
        $lastTime = null;

        if ($labels) {
            foreach ($labels as $labelNode) {
                $label = trim($labelNode->childNodes->item(0)->textContent ?? $labelNode->textContent ?? '');

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
                        $ipEvents[] = ['ip' => $ip, 'time_utc' => $lastTime->copy()];
                    }
                }
            }
        }

        [$rangeStartUtc, $rangeEndUtc] = $this->parseDateRangeUtc($dateRange);

        return [
            'target' => $target,
            'range_start_utc' => $rangeStartUtc,
            'range_end_utc' => $rangeEndUtc,
            'device_build' => $deviceBuild,
            'symmetric_contacts_total' => $symTotal,
            'asymmetric_contacts_total' => $asymTotal,
            'ip_events' => $ipEvents,
        ];
    }

    private function parseUtc(?string $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') return null;

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
        if ($range === '' || ! str_contains($range, ' to ')) return [null, null];

        [$a, $b] = explode(' to ', $range, 2);
        return [$this->parseUtc($a), $this->parseUtc($b)];
    }

    private function extractLeadingInt(?string $text): ?int
    {
        $text = trim((string) $text);
        if ($text === '') return null;

        if (preg_match('/^(\d+)/', $text, $m)) return (int) $m[1];
        return null;
    }
}
