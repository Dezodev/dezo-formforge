<?php

namespace App\Forms;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class InfosTeleContactForm extends BaseForm
{
    public string $site = '24infostele';
    public string $slug = 'contact';
    public string $title = '[24 Infos Télé] Formulaire de contact';
    public string $notifyEmail = 'tele@24infos.fr';

    public function schema(): array
    {
        return [
            TextInput::make('name')
                ->label('Nom')
                ->required(),
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required(),
            Select::make('subject')
                ->label('Sujet')
                ->options([
                    'question'    => 'Question générale',
                    'programme'   => 'Erreur de programme',
                    'partenariat' => 'Partenariat',
                    'probleme'    => 'Signaler un problème technique',
                    'suggestion'  => 'Suggestion / amélioration',
                ])
                ->required(),
            Textarea::make('message')
                ->label('Message')
                ->rows(5)
                ->required(),
        ];
    }
}
