<?php

namespace App\Services\Rh;

use App\Models\Rh\RhMovimentacaoChamado;
use App\Models\Rh\RhMovimentacaoLog;
use Illuminate\Http\Request;

final class MovimentacaoLogService
{
    public function registrar(
        RhMovimentacaoChamado $chamado,
        string $acao,
        ?string $campo = null,
        mixed $valorAnterior = null,
        mixed $valorNovo = null,
        ?int $usuarioId = null,
        ?Request $request = null,
    ): void {
        RhMovimentacaoLog::query()->create([
            'chamado_id' => $chamado->id,
            'usuario_id' => $usuarioId,
            'acao' => $acao,
            'campo' => $campo,
            'valor_anterior' => $valorAnterior !== null ? (is_string($valorAnterior) ? $valorAnterior : json_encode($valorAnterior, JSON_UNESCAPED_UNICODE)) : null,
            'valor_novo' => $valorNovo !== null ? (is_string($valorNovo) ? $valorNovo : json_encode($valorNovo, JSON_UNESCAPED_UNICODE)) : null,
            'ip' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
            'created_at' => now(),
        ]);
    }
}
