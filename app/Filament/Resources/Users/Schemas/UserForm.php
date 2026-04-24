<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Este e-mail já foi cadastrado para outro usuário.',
                    ]),
               // DateTimePicker::make('email_verified_at'),
                //TextInput::make('password')
                //    ->password()
                //    ->required(),
                TextInput::make('password')
                    ->label('Senha')
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
                    ->dehydrated(fn ($state) => filled($state)) // só manda pro save se tiver valor
                    ->required(fn (string $operation) => $operation === 'create')
                    ->minLength(8)
                    ->autocomplete('new-password')
                    ->helperText('Deixe em branco para manter a senha atual.'),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]);
    }
}
