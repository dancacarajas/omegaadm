<?php

namespace App\Console\Commands;

use App\Services\ConfiguracaoZimbraEmailService;
use Illuminate\Console\Command;

class MailTestZimbraJarbasCommand extends Command
{
    protected $signature = 'mail:test-zimbra-jarbas {email? : Destinatário do teste}';

    protected $description = 'Envia e-mail de teste pelo SMTP Zimbra (Configurações → E-mail → bloco Zimbra)';

    public function handle(ConfiguracaoZimbraEmailService $zimbra): int
    {
        if (! $zimbra->configurado()) {
            $this->error('SMTP Zimbra incompleto. Configure em Configurações → E-mail → SMTP Zimbra (envio como Jarbas).');

            return self::FAILURE;
        }

        $zimbra->aplicarConfiguracaoRuntime();

        $destino = $this->argument('email')
            ?: config('mail.beneficio_adesao_matriz.zimbra_from_address');

        if (! is_string($destino) || ! filter_var($destino, FILTER_VALIDATE_EMAIL)) {
            $this->error('Informe um e-mail válido como argumento ou configure o remetente Zimbra.');

            return self::FAILURE;
        }

        try {
            $zimbra->enviarTeste($destino);
            $this->info('E-mail de teste Zimbra enviado para '.$destino);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($zimbra->mensagemErroParaUsuario($e));

            return self::FAILURE;
        }
    }
}
