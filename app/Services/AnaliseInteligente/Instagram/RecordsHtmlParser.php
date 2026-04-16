<?php

namespace App\Services\AnaliseInteligente\Instagram;

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
        $accountIdentifier = $this->getSimpleValueByLabel($xp, 'Account Identifier');
        $registrationDate = $this->getSimpleValueByLabel($xp, 'Registration Date');

        // ✅ fallback: se não vier Target, usa Account Identifier como alvo
        $target = $target ?? $accountIdentifier;

        // Registration IP (no HTML normalmente vem sem porta)
        $registrationIpRaw = $this->getSimpleValueByLabel($xp, 'Registration Ip');
        $registrationParsed = $this->parseIpAndPort($registrationIpRaw);
        $registrationIp = $registrationParsed['ip'];

        $firstName = $this->extractFirstName($xp);
        $phone = $this->extractPhoneInfo($xp);
        $lastLocation = $this->extractLastLocation($dom);

        $ipEvents = $this->extractIpEvents($xp);

        [$rangeStartUtc, $rangeEndUtc] = $this->parseDateRangeUtc($dateRange);

        return [
            'target' => $target,
            'generated_at' => $this->parseUtc($generated),
            'date_range' => $dateRange,
            'range_start_utc' => $rangeStartUtc,
            'range_end_utc' => $rangeEndUtc,

            'account_identifier' => $accountIdentifier,
            'first_name' => $firstName,
            'registration_date' => $this->parseUtc($registrationDate),
            'registration_ip' => $registrationIp,

            'registration_phone' => $phone['phone'],
            'registration_phone_verified_on' => $this->parseUtc($phone['verified_on']),

            'last_location_time' => $lastLocation['time'],
            'last_location_latitude' => $lastLocation['latitude'],
            'last_location_longitude' => $lastLocation['longitude'],
            'last_location_maps_url' => $this->makeMapsUrl(
                $lastLocation['latitude'],
                $lastLocation['longitude']
            ),

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

    private function extractFirstName(\DOMXPath $xp): ?string
    {
        $nodes = $xp->query("//div[contains(@class,'t i')][normalize-space(text())='First']/div[contains(@class,'m')]/div");

        if (! $nodes || $nodes->length === 0) {
            return null;
        }

        $value = trim($nodes->item(0)?->textContent ?? '');

        return $value !== '' ? $value : null;
    }

    private function extractPhoneInfo(\DOMXPath $xp): array
    {
        $value = $this->getSimpleValueByLabel($xp, 'Phone Numbers');

        if (! $value) {
            return [
                'phone' => null,
                'verified_on' => null,
            ];
        }

        $phone = null;
        $verifiedOn = null;

        if (preg_match('/(\+?\d{10,16})/', $value, $m)) {
            $phone = $m[1];
        }

        if (preg_match('/Verified on\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\s+UTC)/i', $value, $m)) {
            $verifiedOn = $m[1];
        }

        return [
            'phone' => $phone,
            'verified_on' => $verifiedOn,
        ];
    }

    private function extractLastLocation(\DOMDocument $dom): array
    {
        $html = $this->extractPropertySectionHtml($dom, 'property-last_location');

        if ($html === '') {
            return [
                'time' => null,
                'latitude' => null,
                'longitude' => null,
            ];
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? '';

        $time = null;
        $lat = null;
        $lng = null;

        if (preg_match('/Time\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/i', $text, $m)) {
            $time = $this->parseUtc($m[1] . ' UTC');
        }

        preg_match_all('/-?\d+\.\d+/', $text, $matches);
        $numbers = $matches[0] ?? [];

        if (count($numbers) >= 2) {
            $coords = array_slice($numbers, -2);
            $lat = (float) $coords[0];
            $lng = (float) $coords[1];
        }

        return [
            'time' => $time,
            'latitude' => $lat,
            'longitude' => $lng,
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
        $container = $xp->query("//div[@id='property-ip_addresses']")->item(0);

        if (! $container) {
            return [];
        }

        $labels = $xp->query(
            ".//div[contains(@class,'t i')][normalize-space(text())='IP Address' or normalize-space(text())='Time']",
            $container
        );

        $events = [];
        $last = null;

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

            if ($label === 'IP Address') {
                $parsed = $this->parseIpAndPort($value);
                $last = $parsed['ip'] ? $parsed : null;
            }

            if ($label === 'Time') {
                $time = $this->parseUtc($value);

                if ($last && $time instanceof Carbon) {
                    $events[] = [
                        'ip' => $last['ip'],
                        'ip_with_port' => $last['ip_with_port'],
                        'port' => $last['port'],
                        'time_utc' => $time->copy(),
                    ];
                }
            }
        }

        return $events;
    }

    private function parseIpAndPort(?string $value): array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return [
                'ip' => null,
                'ip_with_port' => null,
                'port' => null,
            ];
        }

        if (preg_match('/^\[([0-9a-fA-F:]+)\]:(\d{1,5})$/', $value, $m)) {
            $ipBase = trim($m[1]);
            $port = (int) $m[2];

            return [
                'ip' => $ipBase,
                'ip_with_port' => "[{$ipBase}]:{$port}",
                'port' => $port,
            ];
        }

        if (preg_match('/^\[([0-9a-fA-F:]+)\]$/', $value, $m)) {
            $ipBase = trim($m[1]);

            return [
                'ip' => $ipBase,
                'ip_with_port' => "[{$ipBase}]",
                'port' => null,
            ];
        }

        if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3}):(\d{1,5})$/', $value, $m)) {
            $ipBase = trim($m[1]);
            $port = (int) $m[2];

            return [
                'ip' => $ipBase,
                'ip_with_port' => "{$ipBase}:{$port}",
                'port' => $port,
            ];
        }

        if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3})$/', $value, $m)) {
            $ipBase = trim($m[1]);

            return [
                'ip' => $ipBase,
                'ip_with_port' => $ipBase,
                'port' => null,
            ];
        }

        $ipBase = $value;
        if (preg_match('/^\[([^\]]+)\]$/', $value, $m)) {
            $ipBase = trim($m[1]);
        }

        return [
            'ip' => $ipBase !== '' ? $ipBase : null,
            'ip_with_port' => $value,
            'port' => null,
        ];
    }

    private function makeMapsUrl(?float $lat, ?float $lng): ?string
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        return "https://www.google.com/maps?q={$lat},{$lng}";
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

        return [$this->parseUtc($a), $this->parseUtc($b)];
    }
}
