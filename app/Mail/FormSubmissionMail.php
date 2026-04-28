<?php

namespace App\Mail;

use App\Forms\BaseForm;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FormSubmissionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly BaseForm $formDefinition,
        public readonly array $data,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[FormForge] ' . $this->formDefinition->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.form-submission',
            with: [
                'title'  => $this->formDefinition->title,
                'fields' => $this->data,
                'date'   => now()->format('d/m/Y à H:i'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
