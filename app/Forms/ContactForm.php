<?php

namespace App\Forms;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class ContactForm extends BaseForm
{
    public string $site = 'dezo';
    public string $slug = 'contact';
    public string $title = '[Dezodev] Formulaire de contact';
    public string $notifyEmail = 'hello@dezo.dev';

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
            Textarea::make('message')
                ->label('Message')
                ->rows(5)
                ->required(),
        ];
    }
}
