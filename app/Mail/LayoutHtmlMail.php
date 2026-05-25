<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

class LayoutHtmlMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{disk: string, path: string, name?: string}>  $anexos
     * @param  array{
     *     categoria?: string,
     *     tipo?: string,
     *     nome?: string,
     *     mailer?: string,
     *     referencia_tipo?: string,
     *     referencia_id?: int|null,
     *     enviado_por_id?: int|null
     * }  $metaEnvio
     */
    public function __construct(
        public string $htmlBody,
        public string $assunto,
        public array $anexos = [],
        public ?string $fromAddress = null,
        public ?string $fromName = null,
        public array $metaEnvio = [],
    ) {
        $this->withSymfonyMessage(function (Email $message): void {
            $headers = $message->getHeaders();
            $meta = json_encode($this->metaEnvio, JSON_UNESCAPED_UNICODE) ?: '{}';
            $headers->addTextHeader('X-Omega-Email-Meta', base64_encode($meta));
        });
    }

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
