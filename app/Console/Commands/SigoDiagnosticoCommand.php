<?php

namespace App\Console\Commands;

use App\Support\Almoxarifado\SigoInsumosExtracaoService;
use Illuminate\Console\Command;

class SigoDiagnosticoCommand extends Command
{
    protected $signature = 'sigo:diagnostico {--json : Saída em JSON}';

    protected $description = 'Diagnóstico do ambiente SIGO (Python, Playwright, PHP, variáveis Windows)';

    public function handle(SigoInsumosExtracaoService $extracao): int
    {
        $dados = $extracao->diagnosticoCompleto();

        if ($this->option('json')) {
            $this->line(json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return ($dados['dependencias_ok'] ?? false) ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Diagnóstico SIGO — '.now()->format('Y-m-d H:i:s'));
        $this->newLine();

        foreach ($dados as $chave => $valor) {
            if (is_array($valor)) {
                $this->line('<comment>'.$chave.':</comment>');
                foreach ($valor as $k => $v) {
                    $this->line('  '.$k.': '.(is_scalar($v) ? $v : json_encode($v)));
                }
            } else {
                $this->line('<comment>'.$chave.':</comment> '.$valor);
            }
        }

        $this->newLine();
        if ($dados['dependencias_ok'] ?? false) {
            $this->info('OK — ambiente pronto para extração SIGO.');
        } else {
            $this->error('ERRO — '.$dados['dependencias_erro']);
        }

        return ($dados['dependencias_ok'] ?? false) ? self::SUCCESS : self::FAILURE;
    }
}
