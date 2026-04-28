<div>
    @if ($submitted)
        <div class="ff-success">
            <p>{{ $formDefinition->successMessage }}</p>
        </div>
    @else
        <form wire:submit="submit">
            {{ $this->form }}

            <button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $formDefinition->submitLabel }}</span>
                <span wire:loading>Envoi en cours…</span>
            </button>
        </form>
    @endif
</div>
