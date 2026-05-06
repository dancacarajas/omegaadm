<?php

namespace App\Console\Commands;

use App\Services\SsmaRegistroMensalProvisioner;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SsmaProvisionarRegistroMensalCommand extends Command
{
    protected $signature = 'ssma:provisionar-registro-mensal {--mes= : Competência Y-m (padrão: mês atual)}';

    protected $description = 'Garante um rascunho de registro mensal SSMA para a competência (mês), se ainda não existir.';

    public function handle(): int
    {
        $mes = $this->option('mes')
            ? Carbon::createFromFormat('Y-m', (string) $this->option('mes'))->startOfMonth()
            : now()->startOfMonth();

        if (SsmaRegistroMensalProvisioner::provision($mes)) {
            $this->info('Rascunho criado para a competência '.$mes->format('m/Y').'.');
        } else {
            $this->comment('Já existe registro para '.$mes->format('m/Y').'; nada a fazer.');
        }

        return self::SUCCESS;
    }
}
