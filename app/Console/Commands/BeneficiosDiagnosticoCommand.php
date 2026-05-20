<?php

namespace App\Console\Commands;

use App\Models\Beneficio;
use Illuminate\Console\Command;

class BeneficiosDiagnosticoCommand extends Command
{
    protected $signature = 'beneficios:diagnostico {id? : ID para verificar (ex.: 1)}';

    protected $description = 'Lista benefícios no banco e verifica se um ID existe (diagnóstico 404 em produção)';

    public function handle(): int
    {
        $total = Beneficio::query()->count();

        $this->info("Total de benefícios: {$total}");

        if ($total === 0) {
            $this->warn('Nenhum registro na tabela beneficios. GET /rh/beneficios/{id} retornará 404 para qualquer ID.');
            $this->line('Cadastre em: /public/rh/beneficios/create ou php artisan db:seed (se houver seed).');

            return self::SUCCESS;
        }

        $rows = Beneficio::query()
            ->orderBy('id')
            ->get(['id', 'nome', 'status']);

        $this->table(['id', 'nome', 'status'], $rows->map(fn ($b) => [
            $b->id,
            $b->nome,
            $b->status,
        ])->all());

        $id = $this->argument('id');
        if ($id !== null) {
            $registro = Beneficio::query()->find($id);
            if ($registro === null) {
                $this->error("ID {$id} NÃO existe → Laravel retorna 404 em rh/beneficios/{$id} (comportamento esperado).");
                $this->line('Use um ID da listagem acima ou abra /public/rh/beneficios e clique em Ver.');
            } else {
                $this->info("ID {$id} existe: {$registro->nome} ({$registro->status})");
                $this->line("URL gestão: /public/rh/beneficios/{$id}");
            }
        }

        return self::SUCCESS;
    }
}
