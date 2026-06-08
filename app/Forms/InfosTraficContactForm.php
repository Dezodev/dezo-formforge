<?php

namespace App\Forms;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class InfosTraficContactForm extends BaseForm
{
    public string $site = '24infostrafic';
    public string $slug = 'contact';
    public string $title = '[24 Infos Trafic] Formulaire de contact';
    public string $notifyEmail = 'trafic@24infos.fr';

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
                    'erreur'      => 'Erreur de données (ligne, arrêt, perturbation…)',
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
