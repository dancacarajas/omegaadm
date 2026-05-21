<?php

namespace App\Console\Commands;

use App\Models\Colaborador;
use App\Models\ColaboradorMovimentacao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class MovimentacoesDiagnosticoCommand extends Command
{
    protected $signature = 'movimentacoes:diagnostico
                            {id? : ID da movimentação (use com --mov) ou colaborador (padrão: colaborador)}
                            {--rota : Verifica se a rota de gestão está registrada}
                            {--mov : O argumento id é colaborador_movimentacoes.id, não colaborador_id}
                            {--listar : Lista todas as movimentações (id, colaborador, tipo)}';

    protected $description = 'Diagnóstico de 404 em /public/rh/movimentacoes/{id} (produção Hostinger)';

    public function handle(): int
    {
        if ($this->option('rota')) {
            $edit = Route::has('rh.efetivo.movimentacoes.edit');
            $this->line('rh.efetivo.movimentacoes.edit: '.($edit ? 'SIM' : 'NÃO'));
            if ($edit) {
                $this->line('URL gestão (route): '.route('rh.efetivo.movimentacoes.edit', 1));
                $this->line('URL pública esperada: /public/rh/movimentacoes/1');
                $this->line('Legado /editar: /public/rh/movimentacoes/1/editar → redireciona 301');
            } else {
                $this->error('Rota ausente → git pull + php artisan route:clear');
            }
        }

        $total = ColaboradorMovimentacao::query()->count();
        $this->info("Total movimentações: {$total}");

        if ($this->argument('id') === null && ! $this->option('listar') && ! $this->option('mov')) {
            return self::SUCCESS;
        }

        if ($this->option('listar')) {
            $rows = ColaboradorMovimentacao::query()
                ->orderByDesc('id')
                ->get(['id', 'colaborador_id', 'tipo', 'data_inicio']);

            if ($rows->isEmpty()) {
                $this->warn('Nenhuma movimentação no banco.');

                return self::SUCCESS;
            }

            $this->table(
                ['mov_id', 'colab_id', 'tipo', 'data_inicio'],
                $rows->map(fn ($m) => [
                    $m->id,
                    $m->colaborador_id,
                    $m->tipo,
                    $m->data_inicio?->format('Y-m-d'),
                ])->all()
            );

            $this->line('Teste: php artisan movimentacoes:diagnostico ID --mov');

            return self::SUCCESS;
        }

        $id = $this->argument('id');

        if ($this->option('mov')) {
            return $this->diagnosticarMovimentacao((int) $id);
        }

        $colab = Colaborador::query()->find($id);
        if ($colab === null) {
            $mov = ColaboradorMovimentacao::query()->find($id);
            if ($mov !== null) {
                $this->warn("ID {$id} é movimentação, não colaborador. Use: php artisan movimentacoes:diagnostico {$id} --mov");

                return $this->diagnosticarMovimentacao((int) $id);
            }

            $this->error("Colaborador {$id} não existe. Use --listar para ver IDs reais.");

            return self::FAILURE;
        }

        $this->info("Colaborador {$id}: {$colab->nome}");

        $movs = ColaboradorMovimentacao::query()
            ->where('colaborador_id', $id)
            ->orderByDesc('id')
            ->get(['id', 'tipo', 'data_inicio']);

        $this->table(['mov_id', 'tipo', 'data_inicio'], $movs->map(fn ($m) => [
            $m->id,
            $m->tipo,
            $m->data_inicio?->format('Y-m-d'),
        ])->all());

        return self::SUCCESS;
    }

    private function diagnosticarMovimentacao(int $movId): int
    {
        $mov = ColaboradorMovimentacao::query()->find($movId);
        if ($mov === null) {
            $this->error("Movimentação {$movId} não existe no banco → GET na URL retorna 404.");
            $this->line('Rode: php artisan movimentacoes:diagnostico --listar');

            return self::FAILURE;
        }

        $colab = $mov->colaborador;
        $this->info("OK: movimentação {$movId} ({$mov->tipo}) — colaborador {$mov->colaborador_id}".($colab ? ": {$colab->nome}" : ''));
        $this->line('URL gestão (GET+POST): /public/rh/movimentacoes/'.$movId);
        $this->line('Rota nomeada: '.route('rh.efetivo.movimentacoes.edit', $mov));

        return self::SUCCESS;
    }
}
