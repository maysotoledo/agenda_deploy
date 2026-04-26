<?php

namespace App\Filament\Resources\AiAnalyses\Pages;

use App\Filament\Resources\AiAnalyses\AiAnalysisResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAiAnalysis extends EditRecord
{
    protected static string $resource = AiAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
