<?php

namespace App\Providers;

use App\Forms\FormRegistry;
use App\Forms\ContactForm;
use App\Forms\InfosRadioContactForm;
use App\Forms\InfosTeleContactForm;
use App\Forms\InfosTraficContactForm;
use Illuminate\Support\ServiceProvider;

class FormServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        FormRegistry::register('dezo', ContactForm::class);
        FormRegistry::register('24infosradio', InfosRadioContactForm::class);
        FormRegistry::register('24infostele', InfosTeleContactForm::class);
        FormRegistry::register('24infostrafic', InfosTraficContactForm::class);
    }
}
