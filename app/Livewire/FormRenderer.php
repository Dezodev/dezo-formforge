<?php

namespace App\Livewire;

use App\Forms\BaseForm;
use App\Forms\FormRegistry;
use App\Mail\FormSubmissionMail;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Locked;
use Livewire\Component;

class FormRenderer extends Component implements HasForms
{
    use InteractsWithForms;

    #[Locked]
    public string $formSlug;

    public array $data = [];
    public bool $submitted = false;
    public string $turnstileError = '';

    public function mount(BaseForm $formDefinition): void
    {
        $this->formSlug = $formDefinition->slug;
        $this->form->fill();
    }

    protected function getFormDefinition(): BaseForm
    {
        return FormRegistry::resolve($this->formSlug);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components($this->getFormDefinition()->schema())
            ->statePath('data');
    }

    public function submit(): void
    {
        $this->turnstileError = '';

        $token = request()->input('cf-turnstile-response', '');

        if (! $this->verifyTurnstile($token)) {
            $this->turnstileError = 'La vérification de sécurité a échoué. Veuillez réessayer.';
            return;
        }

        $this->form->validate();

        $definition = $this->getFormDefinition();

        Mail::to($definition->notifyEmail)
            ->send(new FormSubmissionMail($definition, $this->data));

        $this->submitted = true;
    }

    private function verifyTurnstile(string $token): bool
    {
        if (app()->environment('local') && empty(config('services.turnstile.secret'))) {
            return true;
        }

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret'   => config('services.turnstile.secret'),
            'response' => $token,
        ]);

        return $response->json('success', false);
    }

    public function render()
    {
        return view('livewire.form-renderer', [
            'formDefinition' => $this->getFormDefinition(),
        ]);
    }
}
