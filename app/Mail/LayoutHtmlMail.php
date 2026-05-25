<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LayoutHtmlMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{disk: string, path: string, name?: string}>  $anexos
     */
    public function __construct(
        public string $htmlBody,
        public string $assunto,
        public array $anexos = [],
        public ?string $fromAddress = null,
        public ?string $fromName = null,
    ) {}

    public function envelope(): Envelope
    {
        $from = null;
        if (filled($this->fromAddress)) {
            $from = new Address(
                (string) $this->fromAddress,
                (string) ($this->fromName ?? ''),
            );
        }

        return new Envelope(
            from: $from,
            subject: $this->assunto,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->htmlBody,
        );
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        $lista = [];

        foreach ($this->anexos as $anexo) {
            $path = (string) ($anexo['path'] ?? '');
            if ($path === '') {
                continue;
            }

            $attachment = Attachment::fromStorageDisk(
                (string) ($anexo['disk'] ?? 'public'),
                $path,
            );

            if (filled($anexo['name'] ?? null)) {
                $attachment->as((string) $anexo['name']);
            }

            $lista[] = $attachment;
        }

        return $lista;
    }
}
