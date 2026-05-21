<?php

namespace App\Console\Commands;

use App\Models\Colaborador;
use App\Models\ColaboradorMovimentacao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class MovimentacoesDiagnosticoCommand extends Command
{
    protected $signature = 'movimentacoes:diagnostico
                            {colaborador? : ID do colaborador}
                            {movimentacao? : ID da movimentação}
                            {--rota : Verifica se a rota de edição está registrada}';

    protected $description = 'Diagnóstico de 404 em /rh/efetivo/{id}/movimentacoes/{id}/editar (produção)';

    public function handle(): int
    {
        if ($this->option('rota')) {
            $edit = Route::has('rh.efetivo.movimentacoes.edit');
            $this->line('rh.efetivo.movimentacoes.edit: '.($edit ? 'SIM' : 'NÃO'));
            if ($edit) {
                $this->line('URL exemplo: '.route('rh.efetivo.movimentacoes.edit', 1));
            } else {
                $this->error('Rota ausente → git pull + php artisan route:clear');
            }
        }

        $total = ColaboradorMovimentacao::query()->count();
        $this->info("Total movimentações: {$total}");

        $colabId = $this->argument('colaborador');
        $movId = $this->argument('movimentacao');

        if ($colabId === null) {
            return self::SUCCESS;
        }

        $colab = Colaborador::query()->find($colabId);
        if ($colab === null) {
            $this->error("Colaborador {$colabId} não existe.");

            return self::FAILURE;
        }

        $this->info("Colaborador {$colabId}: {$colab->nome}");

        if ($movId === null) {
            $movs = ColaboradorMovimentacao::query()
                ->where('colaborador_id', $colabId)
                ->orderByDesc('id')
                ->get(['id', 'tipo', 'data_inicio']);

            $this->table(['id', 'tipo', 'data_inicio'], $movs->map(fn ($m) => [
                $m->id,
                $m->tipo,
                $m->data_inicio?->format('Y-m-d'),
            ])->all());

            return self::SUCCESS;
        }

        $mov = ColaboradorMovimentacao::query()->find($movId);
        if ($mov === null) {
            $this->error("Movimentação {$movId} não existe no banco.");

            return self::FAILURE;
        }

        if ((int) $mov->colaborador_id !== (int) $colabId) {
            $this->error("Movimentação {$movId} pertence ao colaborador {$mov->colaborador_id}, não ao {$colabId}.");
            $this->line('URL incorreta → 404 esperado. Use o botão Alterar na listagem.');

            return self::FAILURE;
        }

        $this->info("OK: movimentação {$movId} ({$mov->tipo}) pertence ao colaborador {$colabId}.");
        $this->line('URL editar: /public/rh/movimentacoes/'.$movId.'/editar');
        $this->line('URL legado: /public/rh/efetivo/'.$colabId.'/movimentacoes/'.$movId.'/editar (redireciona)');

        return self::SUCCESS;
    }
}
