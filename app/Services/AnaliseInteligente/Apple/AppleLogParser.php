<?php

namespace App\Services\AnaliseInteligente\Apple;

use App\Services\AnaliseInteligente\Platform\PlatformLogParser;

class AppleLogParser extends PlatformLogParser
{
    public function __construct()
    {
        parent::__construct('apple', 'Apple');
    }

    protected function extractIdentifiers(string $text): array
    {
        $out = [];

        foreach (parent::extractIdentifiers($text) as $identifier) {
            $type = (string) ($identifier['type'] ?? 'ID');
            $value = (string) ($identifier['value'] ?? '');
            if ($value !== '') {
                $out["{$type}:{$value}"] = ['type' => $type, 'value' => $value];
            }
        }

        if (preg_match_all('/\b(?:DSID|dsid)\s*[:#-]?\s*([A-Z0-9._-]{5,})/i', $text, $matches)) {
            foreach (($matches[1] ?? []) as $value) {
                $value = trim((string) $value);
                if ($value !== '') {
                    $out["DSID:{$value}"] = ['type' => 'DSID', 'value' => $value];
                }
            }
        }

        return array_values($out);
    }
}
