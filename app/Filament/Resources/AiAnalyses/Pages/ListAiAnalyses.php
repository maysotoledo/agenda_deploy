<?php

namespace App\Filament\Resources\AiAnalyses\Pages;

use App\Filament\Resources\AiAnalyses\AiAnalysisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAiAnalyses extends ListRecords
{
    protected static string $resource = AiAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova análise manual'),
        ];
    }
}
