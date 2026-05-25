<?php

namespace App\Console\Commands;

use App\Services\ConfiguracaoZimbraEmailService;
use Illuminate\Console\Command;

class MailDiagnosticoZimbraSmtpCommand extends Command
{
    protected $signature = 'mail:diagnostico-zimbra-smtp
                            {--host= : Host SMTP (padrão: configurado ou smtp.omegaservice.com.br)}
                            {--port=587 : Porta TCP}';

    protected $description = 'Diagnóstico de rede SMTP Zimbra a partir deste servidor (TCP + config)';

    public function handle(ConfiguracaoZimbraEmailService $zimbra): int
    {
        $host = $this->option('host') ?: null;
        $port = (int) $this->option('port');

        $this->info('=== Diagnóstico SMTP Zimbra (servidor atual) ===');
        $this->newLine();

        $ctx = $zimbra->contextoMailerParaLog();
        $this->table(
            ['Chave', 'Valor'],
            collect($ctx)->map(fn ($v, $k) => [$k, (string) $v])->values()->all()
        );

        $testHost = $host ?? (string) ($ctx['host'] ?? ConfiguracaoZimbraEmailService::HOST_PADRAO);
        $this->newLine();
        $this->line("Teste TCP (fsockopen) → {$testHost}:{$port}");

        $tcp = $zimbra->testarConexaoTcp($testHost, $port);

        if ($tcp['ok']) {
            $this->info('OK: '.$tcp['message']);
        } else {
            $this->error('FALHA TCP [errno '.$tcp['errno'].']: '.$tcp['message']);
            $this->warn('Se TCP falhar aqui, o PHP na hospedagem não alcança o SMTP (firewall Hostinger).');
            $this->warn('Comandos equivalentes no SSH: nc -vz '.$testHost.' '.$port);
        }

        $this->newLine();
        $this->line('Configuração esperada (Outlook): smtp.omegaservice.com.br:587 TLS/STARTTLS');

        if (! $zimbra->configurado()) {
            $this->warn('Credenciais Zimbra incompletas no banco (.env ou Configurações → E-mail).');

            return $tcp['ok'] ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Credenciais Zimbra presentes (host/usuário/senha).');

        return $tcp['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
