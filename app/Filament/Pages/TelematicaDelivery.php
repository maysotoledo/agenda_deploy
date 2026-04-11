<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;

class TelematicaDelivery extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Delivery';

    protected static string|\UnitEnum|null $navigationGroup = 'Informação Telemática';
    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'telematica/delivery';

    protected string $view = 'filament.pages.telematica-delivery';

    public function getTitle(): string
    {
        return 'Delivery';
    }

    public function getHeading(): string
    {
        return 'Delivery';
    }
}
