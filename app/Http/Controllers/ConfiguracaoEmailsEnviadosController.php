<?php

namespace App\Http\Controllers;

use App\Models\SistemaEmailEnviado;
use App\Support\SistemaEmailCatalogo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ConfiguracaoEmailsEnviadosController extends Controller
{
    public function index(Request $request): View
    {
        $catalogo = SistemaEmailCatalogo::tipos();
        $porCategoria = collect($catalogo)->groupBy('categoria');

        $filtros = $request->validate([
            'categoria' => ['nullable', 'string', 'max:32'],
            'tipo' => ['nullable', 'string', 'max:64'],
            'destinatario' => ['nullable', 'string', 'max:255'],
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date'],
        ]);

        $kpis = [
            'total_30d' => 0,
            'hoje' => 0,
            'por_categoria' => [],
        ];

        $envios = null;

        if (Schema::hasTable('sistema_emails_enviados')) {
            $base = SistemaEmailEnviado::query();

            $kpis['total_30d'] = (clone $base)->where('enviado_em', '>=', now()->subDays(30))->count();
            $kpis['hoje'] = (clone $base)->whereDate('enviado_em', today())->count();
            $kpis['por_categoria'] = (clone $base)
                ->where('enviado_em', '>=', now()->subDays(30))
                ->selectRaw('categoria, count(*) as total')
                ->groupBy('categoria')
                ->pluck('total', 'categoria')
                ->all();

            $query = SistemaEmailEnviado::query()
                ->with('enviadoPor:id,name,email')
                ->orderByDesc('enviado_em');

            if (filled($filtros['categoria'] ?? null)) {
                $query->where('categoria', $filtros['categoria']);
            }
            if (filled($filtros['tipo'] ?? null)) {
                $query->where('tipo', $filtros['tipo']);
            }
            if (filled($filtros['destinatario'] ?? null)) {
                $query->where('destinatario', 'like', '%'.$filtros['destinatario'].'%');
            }
            if (filled($filtros['de'] ?? null)) {
                $query->whereDate('enviado_em', '>=', $filtros['de']);
            }
            if (filled($filtros['ate'] ?? null)) {
                $query->whereDate('enviado_em', '<=', $filtros['ate']);
            }

            $envios = $query->paginate(25)->withQueryString();
        }

        return view('configuracoes.emails-enviados.index', [
            'catalogo' => $catalogo,
            'porCategoria' => $porCategoria,
            'categorias' => SistemaEmailCatalogo::categorias(),
            'tiposCatalogo' => collect($catalogo)->pluck('nome', 'tipo')->all(),
            'kpis' => $kpis,
            'envios' => $envios,
            'filtros' => $filtros,
        ]);
    }

    public function show(SistemaEmailEnviado $emailEnviado): View
    {
        $emailEnviado->load('enviadoPor:id,name,email');
        $itemCatalogo = collect(SistemaEmailCatalogo::tipos())->firstWhere('tipo', $emailEnviado->tipo);

        return view('configuracoes.emails-enviados.show', [
            'envio' => $emailEnviado,
            'itemCatalogo' => $itemCatalogo,
        ]);
    }
}
