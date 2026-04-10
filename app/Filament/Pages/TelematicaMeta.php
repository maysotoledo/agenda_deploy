<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;

class TelematicaMeta extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'Meta (Facebook/Instagram)';

    protected static string|\UnitEnum|null $navigationGroup = 'Telemática';
    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'telematica/meta';

    protected string $view = 'filament.pages.telematica-meta';

    public function getTitle(): string
    {
        return 'Meta (Facebook/Instagram)';
    }

    public function getHeading(): string
    {
        return 'Meta (Facebook/Instagram)';
    }
}
