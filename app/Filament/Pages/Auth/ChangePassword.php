<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Schemas\Schema;

class ChangePassword extends BaseEditProfile
{
    public static function getLabel(): string
    {
        return 'Alterar senha';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            // Só exibe senha e confirmação
            $this->getPasswordFormComponent()
                ->label('Nova senha'),

            $this->getPasswordConfirmationFormComponent()
                ->label('Confirmar nova senha'),
        ]);
    }
}
