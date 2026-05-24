<?php

namespace App\Jobs;

use App\Models\Almoxarifado\SigoExtracao;
use App\Support\Almoxarifado\SigoInsumosExtracaoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExtrairSigoInsumosJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3700;

    public function __construct(
        public int $sigoExtracaoId,
    ) {}

    public function handle(SigoInsumosExtracaoService $extracao): void
    {
        $registro = SigoExtracao::query()->find($this->sigoExtracaoId);
        if (! $registro) {
            return;
        }

        try {
            $extracao->processarExtracao($registro);
        } catch (\Throwable $e) {
            Log::error('ExtrairSigoInsumosJob falhou', [
                'sigo_extracao_id' => $this->sigoExtracaoId,
                'message' => $e->getMessage(),
            ]);

            $registro->forceFill([
                'status' => SigoExtracao::STATUS_ERRO,
                'erro_tecnico' => $e->getMessage(),
                'erro_usuario' => $extracao->formatarErroParaUsuario($e->getMessage()),
                'finalizado_em' => now(),
            ])->save();
            $registro->limparSenha();
        }
    }
}
