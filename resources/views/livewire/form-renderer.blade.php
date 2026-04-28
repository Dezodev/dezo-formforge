<div>
    @if ($submitted)
        <div class="ff-success">
            <p>{{ $formDefinition->successMessage }}</p>
        </div>
    @else
        <form wire:submit="submit">
            {{ $this->form }}

            @if ($turnstileError)
                <p class="ff-error">{{ $turnstileError }}</p>
            @endif

            {{-- Cloudflare Turnstile --}}
            <div
                class="cf-turnstile"
                data-sitekey="{{ config('services.turnstile.site_key') }}"
                data-theme="auto"
                style="margin-bottom: 1rem;"
            ></div>

            <button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $formDefinition->submitLabel }}</span>
                <span wire:loading>Envoi en cours…</span>
            </button>
        </form>

        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
</div>
