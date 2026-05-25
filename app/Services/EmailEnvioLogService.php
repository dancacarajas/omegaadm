<?php

namespace App\Services;

use App\Mail\LayoutHtmlMail;
use App\Models\SistemaEmailEnviado;
use App\Support\SistemaEmailCatalogo;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class EmailEnvioLogService
{
    public function registrarMessageSent(MessageSent $event): void
    {
        if (! Schema::hasTable('sistema_emails_enviados')) {
            return;
        }

        $message = $event->sent->getOriginalMessage();
        if (! $message instanceof Email) {
            return;
        }

        $mailable = $event->data['mailable'] ?? null;
        $meta = $mailable instanceof LayoutHtmlMail
            ? $mailable->metaEnvio
            : $this->metaDosHeaders($message);

        if ($meta === [] && ! $mailable instanceof LayoutHtmlMail) {
            return;
        }

        $assunto = $mailable instanceof LayoutHtmlMail
            ? $mailable->assunto
            : (string) $message->getSubject();
        $anexosQtd = $mailable instanceof LayoutHtmlMail
            ? count($mailable->anexos)
            : count($message->getAttachments());
        $tipo = (string) ($meta['tipo'] ?? 'desconhecido');
        $catalogo = SistemaEmailCatalogo::indicePorTipo()[$tipo] ?? null;

        $from = $this->extrairRemetente($message, $mailable instanceof LayoutHtmlMail ? $mailable : null);
        $destinatarios = $this->extrairDestinatarios($message);

        if ($destinatarios === []) {
            return;
        }

        $agora = now();
        $payloadBase = [
            'categoria' => (string) ($meta['categoria'] ?? $catalogo['categoria'] ?? 'sistema'),
            'tipo' => $tipo,
            'nome' => (string) ($meta['nome'] ?? $catalogo['nome'] ?? SistemaEmailCatalogo::nomeParaTipo($tipo)),
            'assunto' => $assunto,
            'mailer' => (string) ($meta['mailer'] ?? config('mail.default')),
            'from_address' => $from['address'],
            'from_name' => $from['name'],
            'anexos_qtd' => $anexosQtd,
            'referencia_tipo' => $meta['referencia_tipo'] ?? null,
            'referencia_id' => isset($meta['referencia_id']) ? (int) $meta['referencia_id'] : null,
            'enviado_por_id' => isset($meta['enviado_por_id']) ? (int) $meta['enviado_por_id'] : null,
            'status' => 'enviado',
            'enviado_em' => $agora,
            'created_at' => $agora,
            'updated_at' => $agora,
        ];

        foreach ($destinatarios as $destinatario) {
            SistemaEmailEnviado::query()->create(array_merge($payloadBase, [
                'destinatario' => $destinatario,
            ]));
        }
    }

    /**
     * @return list<string>
     */
    private function extrairDestinatarios(Email $message): array
    {
        $emails = [];

        foreach (['getTo', 'getCc', 'getBcc'] as $metodo) {
            foreach ($message->{$metodo}() as $address) {
                if ($address instanceof Address) {
                    $emails[] = strtolower($address->getAddress());
                }
            }
        }

        return array_values(array_unique($emails));
    }

    /**
     * @return array<string, mixed>
     */
    private function metaDosHeaders(Email $message): array
    {
        $header = $message->getHeaders()->get('X-Omega-Email-Meta');
        if ($header === null) {
            return [];
        }

        $raw = base64_decode($header->getBodyAsString(), true);
        if ($raw === false) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array{address: string|null, name: string|null}
     */
    private function extrairRemetente(Email $message, ?LayoutHtmlMail $mailable): array
    {
        if ($mailable !== null && filled($mailable->fromAddress)) {
            return [
                'address' => (string) $mailable->fromAddress,
                'name' => filled($mailable->fromName) ? (string) $mailable->fromName : null,
            ];
        }

        $from = $message->getFrom();
        if ($from !== []) {
            $primeiro = $from[0];
            if ($primeiro instanceof Address) {
                return [
                    'address' => $primeiro->getAddress(),
                    'name' => $primeiro->getName() ?: null,
                ];
            }
        }

        return [
            'address' => config('mail.from.address'),
            'name' => config('mail.from.name'),
        ];
    }
}
