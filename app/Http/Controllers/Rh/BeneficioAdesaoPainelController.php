<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Beneficio;
use App\Models\ColaboradorBeneficio;
use App\Services\Rh\BeneficioAdesaoMatrizNotificacaoService;
use App\Services\Rh\BeneficioAdesaoService;
use App\Support\Rh\BeneficioAdesaoStatus;
use Illuminate\Http\Request;

class BeneficioAdesaoPainelController extends Controller
{
    public function index(Request $request, BeneficioAdesaoService $adesao)
    {
        $diasAlerta = max(1, min(120, (int) $request->input('dias_alerta', 15)));
        $status = (string) $request->input('status', 'em_andamento');
        $beneficioId = $request->integer('beneficio_id') ?: null;
        $busca = trim((string) $request->input('busca', ''));

        $vinculos = ColaboradorBeneficio::query()
            ->with(['colaborador', 'beneficio', 'adesaoAtualizadoPor'])
            ->whereHas('beneficio', fn ($q) => $q->where('requer_controle_adesao', true))
            ->when($beneficioId, fn ($q) => $q->where('beneficio_id', $beneficioId))
            ->when($status === 'em_andamento', fn ($q) => $q->whereIn('status_adesao', BeneficioAdesaoStatus::emAndamento()))
            ->when($status === 'cartao_atrasado', function ($q) use ($diasAlerta) {
                $q->where('cartao_entregue', false)
                    ->whereNotNull('data_envio_matriz')
                    ->whereNull('data_aviso_coleta_matriz')
                    ->whereNull('data_retorno_matriz')
                    ->whereNull('data_previsao_cartao')
                    ->whereDate('data_envio_matriz', '<=', now()->subDays($diasAlerta)->toDateString())
                    ->whereIn('status_adesao', [
                        BeneficioAdesaoStatus::ENVIADO_MATRIZ,
                        BeneficioAdesaoStatus::AGUARDANDO_CARTAO,
                    ]);
            })
            ->when($status === BeneficioAdesaoStatus::CARTAO_DISPONIVEL_COLETA, fn ($q) => $q->where('status_adesao', BeneficioAdesaoStatus::CARTAO_DISPONIVEL_COLETA))
            ->when(
                $status !== '' && $status !== 'todos' && $status !== 'em_andamento' && $status !== 'cartao_atrasado',
                fn ($q) => $q->where('status_adesao', $status)
            )
            ->when($busca !== '', function ($q) use ($busca) {
                $q->whereHas('colaborador', function ($inner) use ($busca) {
                    $inner->where('nome', 'like', "%{$busca}%")
                        ->orWhere('matricula', 'like', "%{$busca}%")
                        ->orWhere('cargo', 'like', "%{$busca}%");
                });
            })
            ->join('colaboradores', 'colaboradores.id', '=', 'colaborador_beneficios.colaborador_id')
            ->select('colaborador_beneficios.*')
            ->orderBy('colaboradores.nome')
            ->paginate(20)
            ->withQueryString();

        return view('rh.beneficios.adesoes.index', [
            'vinculos' => $vinculos,
            'resumo' => $adesao->resumoPainel($diasAlerta),
            'beneficios' => Beneficio::query()->where('requer_controle_adesao', true)->orderBy('nome')->get(['id', 'nome']),
            'statusFiltro' => $status,
            'beneficioId' => $beneficioId,
            'busca' => $busca,
            'diasAlerta' => $diasAlerta,
            'statusOpcoes' => BeneficioAdesaoStatus::rotulos(),
            'adesao' => $adesao,
            'emailMatrizDiagnostico' => app(BeneficioAdesaoMatrizNotificacaoService::class)->diagnosticoEnvio(),
        ]);
    }
}
