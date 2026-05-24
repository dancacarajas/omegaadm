<?php

namespace App\Http\Controllers\Almoxarifado;

use App\Http\Controllers\Controller;
use App\Support\Almoxarifado\AlmoxarifadoAcesso;
use App\Support\Almoxarifado\SigoInsumosExtracaoService;
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

        return view('almoxarifado.sigo-insumos.index', [
            'dependenciasOk' => $dependenciasOk,
            'dependenciasMsg' => $status['diagnostico'] ?? null,
            'pythonDetectado' => $status['python'] ?? null,
            'sigoUrl' => config('sigo.base_url').config('sigo.novo_pm_path'),
            'ultimoResultado' => session('sigo_extracao_resultado'),
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

        set_time_limit((int) config('sigo.timeout_seconds', 3600));

        try {
            $resultado = $this->extracao->extrair(
                $data['sigo_usuario'],
                $data['sigo_senha'],
            );
        } catch (RuntimeException $e) {
            return redirect()
                ->route('almoxarifado.sigo-insumos.index')
                ->with('error', $e->getMessage());
        }

        if (! $resultado['ok']) {
            $erro = $this->extracao->formatarErroParaUsuario($resultado['resumo']['erro'] ?? null);

            return redirect()
                ->route('almoxarifado.sigo-insumos.index')
                ->with('error', $erro)
                ->with('sigo_extracao_resultado', array_merge($resultado['resumo'], [
                    'erro' => $erro,
                ]));
        }

        return redirect()
            ->route('almoxarifado.sigo-insumos.index')
            ->with('success', sprintf(
                'Extração concluída: %s insumos únicos (%s páginas lidas).',
                number_format((int) ($resultado['resumo']['registros_unicos'] ?? 0), 0, ',', '.'),
                number_format((int) ($resultado['resumo']['paginas_lidas'] ?? 0), 0, ',', '.'),
            ))
            ->with('sigo_extracao_resultado', array_merge($resultado['resumo'], [
                'token' => $resultado['token'],
            ]));
    }

    public function download(Request $request, string $token, string $tipo): BinaryFileResponse
    {
        AlmoxarifadoAcesso::abortUnless(AlmoxarifadoAcesso::podeExtrairInsumosSigo());

        $tipo = strtolower($tipo);
        abort_unless(in_array($tipo, ['xlsx', 'csv', 'log'], true), 404);

        $path = $this->extracao->caminhoArquivo($token, $tipo);
        abort_unless($path !== null, 404);

        $nome = match ($tipo) {
            'xlsx' => 'insumos_sigo_extraidos.xlsx',
            'csv' => 'insumos_sigo_extraidos.csv',
            default => basename($path),
        };

        return response()->download($path, $nome);
    }
}
