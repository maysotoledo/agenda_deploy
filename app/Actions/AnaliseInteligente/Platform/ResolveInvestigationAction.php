<?php

namespace App\Actions\AnaliseInteligente\Platform;

use App\Models\AnaliseInvestigation;
use RuntimeException;

class ResolveInvestigationAction
{
    public function execute(?int $investigationId, int $userId, string $source, string $name = ''): AnaliseInvestigation
    {
        if ($investigationId) {
            $investigation = AnaliseInvestigation::query()
                ->whereKey($investigationId)
                ->where('user_id', $userId)
                ->first();

            if (! $investigation) {
                throw new RuntimeException('Investigacao nao encontrada.');
            }

            if ($investigation->source !== $source) {
                throw new RuntimeException('Esta investigacao pertence a outra plataforma.');
            }

            return $investigation;
        }

        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('Informe o nome da investigacao.');
        }

        return AnaliseInvestigation::create([
            'user_id' => $userId,
            'uuid' => (string) str()->uuid(),
            'name' => $name,
            'source' => $source,
        ]);
    }
}
