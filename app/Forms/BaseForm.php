<?php

namespace App\Forms;

use l3aro\FilamentTurnstile\Forms\Turnstile;

abstract class BaseForm implements FormInterface
{
    public string $site;
    public string $slug;
    public string $title;
    public string $notifyEmail;
    public string $submitLabel = 'Envoyer';
    public string $successMessage = 'Votre message a bien été envoyé. Merci !';

    final public function schemaWithCaptcha(): array
    {
        return [
            ...$this->schema(),
            Turnstile::make('captcha')->theme('auto'),
        ];
    }
}
