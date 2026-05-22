<?php

namespace App\Console\Commands;

use App\Models\Colaborador;
use App\Models\ColaboradorMovimentacao;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

class MovimentacoesDiagnosticoCommand extends Command
{
    protected $signature = 'movimentacoes:diagnostico
                            {id? : ID da movimentação (use com --mov) ou colaborador (padrão: colaborador)}
                            {--rota : Verifica se a rota de gestão está registrada}
                            {--mov : O argumento id é colaborador_movimentacoes.id, não colaborador_id}
                            {--listar : Lista todas as movimentações (id, colaborador, tipo)}
                            {--http : Simula GET HTTP com /public/ no REQUEST_URI (como o navegador)}
                            {--curl-externo : HEAD na URL pública real (compara movimentação vs benefícios)}';

    protected $description = 'Diagnóstico de 404 em /public/rh/movimentacoes/{id} (produção Hostinger)';

    public function handle(): int
    {
        if ($this->option('rota')) {
            $edit = Route::has('rh.efetivo.movimentacoes.edit');
            $this->line('rh.efetivo.movimentacoes.edit: '.($edit ? 'SIM' : 'NÃO'));
            if ($edit) {
                $this->line('URL gestão (route): '.route('rh.efetivo.movimentacoes.edit', 1));
                $this->line('URL pública esperada: /public/rh/movimentacao/1');
                $this->line('Legado: /public/rh/efetivo/movimentacao/1 → redireciona 301');
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
        $this->line('URL gestão (GET+POST): /public/rh/movimentacao/'.$movId);
        $this->line('Rota nomeada: '.route('rh.efetivo.movimentacoes.edit', $mov));

        if ($this->option('http')) {
            $this->simularHttpGet($movId);
        }

        if ($this->option('curl-externo')) {
            $this->testarHttpExterno($movId);
        }

        return self::SUCCESS;
    }

    private function simularHttpGet(int $movId): void
    {
        $_SERVER['REQUEST_URI'] = '/public/rh/movimentacao/'.$movId;
        $_SERVER['OMEGA_REQUEST_USES_PUBLIC_URL'] = '1';
        $_SERVER['SCRIPT_NAME'] = '/public/index.php';
        $_SERVER['SCRIPT_FILENAME'] = public_path('index.php');
        $_SERVER['QUERY_STRING'] = '';

        (require base_path('bootstrap/fix-public-request-uri.php'))();

        $request = Request::create($_SERVER['REQUEST_URI'], 'GET');

        $kernel = app(Kernel::class);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        $path = $request->path();
        $this->line("Simulação HTTP: status {$response->getStatusCode()}, path()={$path}");

        if ($response->getStatusCode() === 404) {
            $this->error('404 na simulação → path deve ser rh/movimentacao/'.$movId.' (sem prefixo public/)');
            $this->line('Confira bootstrap/fix-public-request-uri.php e .htaccess na raiz do projeto.');
        } elseif ($response->isRedirection()) {
            $this->info('302/301 na simulação interna SEM login = esperado (middleware auth). A rota existe.');
            $this->line('Redirect: '.$response->headers->get('Location'));
            $this->line('Compare com: php artisan movimentacoes:diagnostico '.$movId.' --mov --curl-externo');
        }
    }

    private function testarHttpExterno(int $movId): void
    {
        $host = rtrim((string) config('app.url', 'https://omegaadm.feston.net.br'), '/');
        $urls = [
            'movimentacao' => "{$host}/public/rh/movimentacao/{$movId}",
            'beneficios_ref' => "{$host}/public/rh/beneficios/1",
            'legado_mov' => "{$host}/public/rh/efetivo/movimentacao/{$movId}",
        ];

        $this->line('HTTP externo (sem cookie de sessão):');

        foreach ($urls as $rotulo => $url) {
            try {
                $response = Http::withOptions(['allow_redirects' => false])->timeout(20)->head($url);
                $status = $response->status();
                $this->line("  [{$rotulo}] HTTP {$status} — {$url}");
                if ($location = $response->header('Location')) {
                    $this->line("    Location: {$location}");
                }
            } catch (\Throwable $e) {
                $this->warn("  [{$rotulo}] falhou: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->line('Interpretação:');
        $this->line('  • 302 → /public/login = Laravel recebeu o pedido (igual Benefícios). Rota web OK.');
        $this->line('  • 404 aqui e 302 em Benefícios = problema específico de Movimentações ou cache LiteSpeed.');
        $this->line('  • Navegador logado com 404 mas curl 302 = cache do browser/LiteSpeed ou binding com sessão.');
        $this->line('  • Logado: teste ?debug_movimentacao=1 — JSON = chegou no controller; 404 sem JSON = não chegou.');
    }
}
