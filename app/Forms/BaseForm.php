<?php

namespace App\Forms;

abstract class BaseForm implements FormInterface
{
    public string $slug;
    public string $title;
    public string $notifyEmail;
    public string $submitLabel = 'Envoyer';
    public string $successMessage = 'Votre message a bien été envoyé. Merci !';
}
