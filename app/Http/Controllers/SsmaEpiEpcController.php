<?php

namespace App\Http\Controllers;

use App\Models\SsmaEpiEntrega;
use App\Models\SsmaEpcRegistro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SsmaEpiEpcController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeView();

        $epis = SsmaEpiEntrega::query()
            ->filtrarEpi(
                $request->filled('epi_busca') ? (string) $request->input('epi_busca') : null,
                $request->filled('epi_status') ? (string) $request->input('epi_status') : null,
            )
            ->orderByDesc('id')
            ->paginate(12, ['*'], 'page_epi')
            ->withQueryString();

        $epcs = SsmaEpcRegistro::query()
            ->filtrarEpc(
                $request->filled('epc_busca') ? (string) $request->input('epc_busca') : null,
                $request->filled('epc_condicao') ? (string) $request->input('epc_condicao') : null,
                $request->filled('epc_local') ? (string) $request->input('epc_local') : null,
            )
            ->orderByDesc('id')
            ->paginate(12, ['*'], 'page_epc')
            ->withQueryString();

        $indicadores = $this->montarIndicadores();

        $podeEditar = auth()->user()?->podeAcaoNoModulo('sesmt', 'editar') ?? false;

        return view('sesmt.epi-epc.index', compact('epis', 'epcs', 'indicadores', 'podeEditar'));
    }

    public function createEpi()
    {
        $this->authorizeEdit();

        return view('sesmt.epi-epc.epi.create', [
            'epi' => new SsmaEpiEntrega(['status' => 'pendente']),
        ]);
    }

    public function storeEpi(Request $request)
    {
        $this->authorizeEdit();

        $data = $this->validatedEpi($request);
        $data['evidencia_path'] = $this->storeFile($request, 'evidencia_epi', null, 'ssma/epi/evidencias');

        SsmaEpiEntrega::create($data);

        return redirect()
            ->route('sesmt.epi-epc.index')
            ->with('success', 'Registro de EPI salvo.');
    }

    public function editEpi(SsmaEpiEntrega $epi)
    {
        $this->authorizeEdit();

        return view('sesmt.epi-epc.epi.edit', compact('epi'));
    }

    public function updateEpi(Request $request, SsmaEpiEntrega $epi)
    {
        $this->authorizeEdit();

        $data = $this->validatedEpi($request);
        $path = $this->storeFile($request, 'evidencia_epi', $epi->evidencia_path, 'ssma/epi/evidencias');
        if ($path !== null) {
            $data['evidencia_path'] = $path;
        }

        $epi->update($data);

        return redirect()
            ->route('sesmt.epi-epc.index')
            ->with('success', 'EPI atualizado.');
    }

    public function destroyEpi(SsmaEpiEntrega $epi)
    {
        $this->authorizeEdit();

        if ($epi->evidencia_path) {
            Storage::disk('public')->delete($epi->evidencia_path);
        }
        $epi->delete();

        return redirect()
            ->route('sesmt.epi-epc.index')
            ->with('success', 'Registro de EPI removido.');
    }

    public function createEpc()
    {
        $this->authorizeEdit();

        return view('sesmt.epi-epc.epc.create', [
            'epc' => new SsmaEpcRegistro([
                'condicao' => 'conforme',
                'necessita_correcao' => false,
            ]),
        ]);
    }

    public function storeEpc(Request $request)
    {
        $this->authorizeEdit();

        $data = $this->validatedEpc($request);
        $data['evidencia_foto_path'] = $this->storeFile($request, 'evidencia_foto', null, 'ssma/epc/fotos');

        SsmaEpcRegistro::create($data);

        return redirect()
            ->route('sesmt.epi-epc.index')
            ->with('success', 'Registro de EPC salvo.');
    }

    public function editEpc(SsmaEpcRegistro $epc)
    {
        $this->authorizeEdit();

        return view('sesmt.epi-epc.epc.edit', compact('epc'));
    }

    public function updateEpc(Request $request, SsmaEpcRegistro $epc)
    {
        $this->authorizeEdit();

        $data = $this->validatedEpc($request);
        $path = $this->storeFile($request, 'evidencia_foto', $epc->evidencia_foto_path, 'ssma/epc/fotos');
        if ($path !== null) {
            $data['evidencia_foto_path'] = $path;
        }

        $epc->update($data);

        return redirect()
            ->route('sesmt.epi-epc.index')
            ->with('success', 'EPC atualizado.');
    }

    public function destroyEpc(SsmaEpcRegistro $epc)
    {
        $this->authorizeEdit();

        if ($epc->evidencia_foto_path) {
            Storage::disk('public')->delete($epc->evidencia_foto_path);
        }
        $epc->delete();

        return redirect()
            ->route('sesmt.epi-epc.index')
            ->with('success', 'Registro de EPC removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function montarIndicadores(): array
    {
        $hoje = now()->toDateString();

        $epiEntregues = SsmaEpiEntrega::where('status', 'entregue')->count();
        $epiPendentes = SsmaEpiEntrega::where('status', 'pendente')->count();
        $epiVencidos = SsmaEpiEntrega::where('status', 'vencido')->count();

        $caVencido = SsmaEpiEntrega::query()
            ->whereIn('status', ['pendente', 'entregue'])
            ->whereNotNull('validade_ca')
            ->whereDate('validade_ca', '<', $hoje)
            ->count();

        $epcBase = SsmaEpcRegistro::query();

        $epcConforme = (clone $epcBase)
            ->where('condicao', 'conforme')
            ->where('necessita_correcao', false)
            ->count();

        $epcNaoConforme = (clone $epcBase)
            ->where(function ($q) {
                $q->where('condicao', 'nao_conforme')
                    ->orWhere('necessita_correcao', true);
            })
            ->count();

        $pendColab = SsmaEpiEntrega::query()
            ->where('status', '!=', 'cancelado')
            ->where(function ($q) use ($hoje) {
                $q->where('status', 'pendente')
                    ->orWhere(function ($q2) use ($hoje) {
                        $q2->whereIn('status', ['pendente', 'entregue'])
                            ->whereNotNull('validade_ca')
                            ->whereDate('validade_ca', '<', $hoje);
                    });
            })
            ->select('colaborador', DB::raw('COUNT(*) as total'))
            ->groupBy('colaborador')
            ->orderByDesc('total')
            ->limit(12)
            ->get();

        $pendLocal = SsmaEpcRegistro::query()
            ->where(function ($q) {
                $q->where('necessita_correcao', true)
                    ->orWhere('condicao', 'nao_conforme');
            })
            ->select('local', DB::raw('COUNT(*) as total'))
            ->groupBy('local')
            ->orderByDesc('total')
            ->limit(12)
            ->get();

        return [
            'epi_entregues' => $epiEntregues,
            'epi_pendentes' => $epiPendentes,
            'epi_vencidos' => $epiVencidos,
            'ca_vencido' => $caVencido,
            'epc_conforme' => $epcConforme,
            'epc_nao_conforme' => $epcNaoConforme,
            'pendencias_colaborador' => $pendColab,
            'pendencias_local' => $pendLocal,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedEpi(Request $request): array
    {
        $data = $request->validate([
            'colaborador' => ['required', 'string', 'max:255'],
            'cargo' => ['nullable', 'string', 'max:255'],
            'epi_obrigatorio' => ['required', 'string', 'max:500'],
            'ca_numero' => ['nullable', 'string', 'max:120'],
            'validade_ca' => ['nullable', 'date'],
            'data_entrega' => ['nullable', 'date'],
            'data_substituicao' => ['nullable', 'date'],
            'status' => ['required', 'in:'.implode(',', array_keys(SsmaEpiEntrega::STATUS))],
            'observacao' => ['nullable', 'string', 'max:20000'],
            'evidencia_epi' => ['nullable', 'file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf'],
        ]);

        unset($data['evidencia_epi']);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedEpc(Request $request): array
    {
        $data = $request->validate([
            'local' => ['required', 'string', 'max:255'],
            'tipo_epc' => ['required', 'string', 'max:255'],
            'condicao' => ['required', 'in:'.implode(',', array_keys(SsmaEpcRegistro::CONDICOES))],
            'necessita_correcao' => ['sometimes', 'boolean'],
            'risco_associado' => ['nullable', 'string', 'max:20000'],
            'responsavel' => ['nullable', 'string', 'max:255'],
            'prazo' => ['nullable', 'date'],
            'evidencia_foto' => ['nullable', 'file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp'],
        ]);

        unset($data['evidencia_foto']);

        $data['necessita_correcao'] = $request->boolean('necessita_correcao');

        return $data;
    }

    private function storeFile(Request $request, string $input, ?string $previous, string $dir): ?string
    {
        if (! $request->hasFile($input)) {
            return null;
        }

        if ($previous) {
            Storage::disk('public')->delete($previous);
        }

        return $request->file($input)->store($dir, 'public');
    }

    private function authorizeView(): void
    {
        abort_unless(
            auth()->user()?->temQualquerPermissaoNoModulo('sesmt'),
            403,
            'Seu perfil não tem acesso ao módulo SSMA.'
        );
    }

    private function authorizeEdit(): void
    {
        abort_unless(
            auth()->user()?->podeAcaoNoModulo('sesmt', 'editar'),
            403,
            'Seu perfil não pode gerenciar EPI/EPC.'
        );
    }
}
