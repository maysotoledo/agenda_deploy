<?php

namespace App\Services\AnaliseInteligente;

use App\Models\AnaliseRun;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RunPayloadStorage
{
    public function storeParsedPayload(string $runUuid, array $parsed): string
    {
        @ini_set('memory_limit', '512M');

        $path = 'analise-runs/' . $runUuid . '/parsed.json.gz';
        $json = json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $json = '{}';
        }

        Storage::disk('local')->put($path, gzencode($json, 6));

        return $path;
    }

    public function loadParsedPayload(AnaliseRun $run, string $reportKey = '_parsed_path'): ?array
    {
        $inline = data_get($run->report, '_parsed');
        if (is_array($inline)) {
            $path = data_get($run->report, $reportKey);

            if (! is_string($path) || trim($path) === '') {
                $path = $this->storeParsedPayload((string) ($run->uuid ?: Str::uuid()), $inline);

                $report = is_array($run->report) ? $run->report : [];
                unset($report['_parsed']);
                $report[$reportKey] = $path;

                $run->report = $report;
                $run->save();
            }

            return $inline;
        }

        $path = data_get($run->report, $reportKey);
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            return null;
        }

        $content = $disk->get($path);
        if (! is_string($content) || $content === '') {
            return null;
        }

        $json = @gzdecode($content);
        if (! is_string($json) || $json === '') {
            $json = $content;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }
}
