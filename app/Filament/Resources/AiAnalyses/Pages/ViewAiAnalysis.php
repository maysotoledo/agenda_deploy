<?php

namespace App\Filament\Resources\AiAnalyses\Pages;

use App\Filament\Resources\AiAnalyses\AiAnalysisResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAiAnalysis extends ViewRecord
{
    protected static string $resource = AiAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Excluir'),
        ];
    }
}
