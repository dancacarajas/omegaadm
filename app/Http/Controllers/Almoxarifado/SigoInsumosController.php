<?php

namespace App\Http\Controllers\Almoxarifado;

use App\Http\Controllers\Controller;
use App\Jobs\ExtrairSigoInsumosJob;
use App\Models\Almoxarifado\SigoExtracao;
use App\Support\Almoxarifado\AlmoxarifadoAcesso;
use App\Support\Almoxarifado\SigoInsumosExtracaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SigoInsumosController extends Controller
{
    public function __construct(
        private SigoInsumosExtracaoService $extracao,
    ) {}

    public function index(): View
    {
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeExtrairInsumosSigo());

        $status = $this->extracao->statusDependencias();
        $dependenciasOk = ! isset($status['diagnostico']);

        $extracaoAtiva = null;
        $uuidSessao = session('sigo_extracao_uuid');
        if (is_string($uuidSessao) && $uuidSessao !== '') {
            $registro = SigoExtracao::query()
                ->where('uuid', $uuidSessao)
                ->where('user_id', auth()->id())
                ->first();
            if ($registro) {
                $extracaoAtiva = $registro->paraPainel();
            }
        }

        return view('almoxarifado.sigo-insumos.index', [
            'dependenciasOk' => $dependenciasOk,
            'dependenciasMsg' => $status['diagnostico'] ?? null,
            'pythonDetectado' => $status['python'] ?? null,
            'sigoUrl' => config('sigo.base_url').config('sigo.novo_pm_path'),
            'extracaoAtiva' => $extracaoAtiva,
            'queueConnection' => config('queue.default'),
        ]);
    }

    public function extrair(Request $request): RedirectResponse
    {
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeExtrairInsumosSigo());

        $data = $request->validate([
            'sigo_usuario' => ['required', 'string', 'max:120'],
            'sigo_senha' => ['required', 'string', 'max:120'],
        ], [
            'sigo_usuario.required' => 'Informe o usuário do SIGO.',
            'sigo_senha.required' => 'Informe a senha do SIGO.',
        ]);

        try {
            $registro = $this->extracao->iniciarExtracao(
                (int) auth()->id(),
                $data['sigo_usuario'],
                $data['sigo_senha'],
            );
        } catch (RuntimeException $e) {
            return redirect()
                ->route('almoxarifado.sigo-insumos.index')
                ->with('error', $e->getMessage());
        }

        $job = new ExtrairSigoInsumosJob($registro->id);
        if (config('queue.default') === 'sync') {
            dispatch_sync($job);
        } else {
            dispatch($job);
        }

        return redirect()
            ->route('almoxarifado.sigo-insumos.index')
            ->with('success', 'Extração iniciada. Aguarde o processamento na tela.')
            ->with('sigo_extracao_uuid', $registro->uuid);
    }

    public function status(string $uuid): JsonResponse
    {
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeExtrairInsumosSigo());

        $registro = SigoExtracao::query()
            ->where('uuid', $uuid)
            ->where('user_id', auth()->id())
            ->first();

        abort_unless($registro !== null, 404);

        return response()->json($registro->paraPainel());
    }

    public function download(Request $request, string $token, string $tipo): BinaryFileResponse
    {
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeExtrairInsumosSigo());

        $tipo = strtolower($tipo);
        abort_unless(in_array($tipo, ['xlsx', 'csv', 'log', 'debug'], true), 404);

        $path = $this->extracao->caminhoArquivo($token, $tipo, (int) auth()->id());
        abort_unless($path !== null, 404);

        $nome = match ($tipo) {
            'xlsx' => 'insumos_sigo_extraidos.xlsx',
            'csv' => 'insumos_sigo_extraidos.csv',
            'debug' => 'sigo_debug_erro.txt',
            default => basename($path),
        };

        return response()->download($path, $nome);
    }
}
