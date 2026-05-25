<?php

namespace App\Console\Commands;

use App\Mail\LayoutHtmlMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTestZimbraJarbasCommand extends Command
{
    protected $signature = 'mail:test-zimbra-jarbas {email? : Destinatário do teste (padrão: MAIL_ZIMBRA_FROM_ADDRESS)}';

    protected $description = 'Envia e-mail de teste pelo SMTP Zimbra (mailer zimbra_jarbas)';

    public function handle(): int
    {
        $mailer = (string) config('mail.beneficio_adesao_matriz.zimbra_mailer', 'zimbra_jarbas');
        $host = config("mail.mailers.{$mailer}.host");
        $user = config("mail.mailers.{$mailer}.username");
        $pass = config("mail.mailers.{$mailer}.password");

        if (blank($host) || blank($user) || blank($pass)) {
            $this->error('SMTP Zimbra incompleto. Configure no .env:');
            $this->line('  MAIL_ZIMBRA_HOST, MAIL_ZIMBRA_USERNAME, MAIL_ZIMBRA_PASSWORD (senha de aplicativo)');

            return self::FAILURE;
        }

        $destino = $this->argument('email')
            ?: config('mail.beneficio_adesao_matriz.zimbra_from_address');

        if (! is_string($destino) || ! filter_var($destino, FILTER_VALIDATE_EMAIL)) {
            $this->error('Informe um e-mail válido como argumento ou em MAIL_ZIMBRA_FROM_ADDRESS.');

            return self::FAILURE;
        }

        $fromAddress = (string) config('mail.beneficio_adesao_matriz.zimbra_from_address');
        $fromName = (string) config('mail.beneficio_adesao_matriz.zimbra_from_name');

        $this->info("Mailer: {$mailer}");
        $this->line("Host: {$host}");
        $this->line("Usuário: {$user}");
        $this->line("De: {$fromName} <{$fromAddress}>");
        $this->line("Para: {$destino}");

        try {
            Mail::mailer($mailer)
                ->to($destino)
                ->send(new LayoutHtmlMail(
                    '<p>Teste de envio pelo SMTP Zimbra do Jarbas (sistema Omega).</p>',
                    'Teste SMTP Zimbra — Benefícios',
                    [],
                    $fromAddress,
                    $fromName,
                ));

            $this->info('E-mail de teste enviado com sucesso.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Falha: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
