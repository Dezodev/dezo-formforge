<?php

namespace App\Providers;

use App\Forms\FormRegistry;
use App\Forms\ContactForm;
use Illuminate\Support\ServiceProvider;

class FormServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        FormRegistry::register('dezo', ContactForm::class);
    }
}
