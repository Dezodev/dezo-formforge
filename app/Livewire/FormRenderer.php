<?php

namespace App\Livewire;

use App\Forms\BaseForm;
use App\Forms\FormRegistry;
use App\Mail\FormSubmissionMail;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Locked;
use Livewire\Component;

class FormRenderer extends Component implements HasForms
{
    use InteractsWithForms;

    #[Locked]
    public string $formSite;

    #[Locked]
    public string $formSlug;

    public array $data = [];
    public bool $submitted = false;

    public function mount(string $site, string $slug): void
    {
        $this->formSite = $site;
        $this->formSlug = $slug;
        $this->form->fill();
    }

    protected function getFormDefinition(): BaseForm
    {
        return FormRegistry::resolve($this->formSite, $this->formSlug);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components($this->getFormDefinition()->schemaWithCaptcha())
            ->statePath('data');
    }

    public function submit(): void
    {
        \Illuminate\Support\Facades\Log::info('submit() called', ['data' => $this->data]);

        $this->form->validate();

        $definition = $this->getFormDefinition();

        Mail::to($definition->notifyEmail)
            ->send(new FormSubmissionMail($definition, $this->data));

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.form-renderer', [
            'formDefinition' => $this->getFormDefinition(),
        ]);
    }
}
