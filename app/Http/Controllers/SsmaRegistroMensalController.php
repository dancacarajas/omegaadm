<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\SsmaRegistroMensal;
use App\Models\SsmaRegistroMensalPrazo;
use App\Services\SsmaRegistroMensalEtapasAssembler;
use App\Support\SsmaRegistroMensalResumo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SsmaRegistroMensalController extends Controller
{
    public function index(Request $request)
    {
        $query = SsmaRegistroMensal::query();

        $competenciaFiltro = (string) $request->query('competencia', now()->format('Y-m'));
        if ($competenciaFiltro !== '') {
            $competenciaDate = Carbon::createFromFormat('Y-m', $competenciaFiltro)->startOfMonth()->toDateString();
            $query->whereDate('competencia', $competenciaDate);
        }

        if ($busca = trim((string) $request->query('busca', ''))) {
            $query->where(function ($q) use ($busca) {
                $q->where('titulo', 'like', "%{$busca}%")
                    ->orWhere('responsavel', 'like', "%{$busca}%")
                    ->orWhere('contrato', 'like', "%{$busca}%")
                    ->orWhere('local_base', 'like', "%{$busca}%");
            });
        }

        $registros = $query
            ->orderByDesc('competencia')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $baseResumo = SsmaRegistroMensal::query();
        if ($competenciaFiltro !== '') {
            $competenciaDate = Carbon::createFromFormat('Y-m', $competenciaFiltro)->startOfMonth()->toDateString();
            $baseResumo->whereDate('competencia', $competenciaDate);
        }

        $total = (clone $baseResumo)->count();
        $rascunhos = (clone $baseResumo)->where('status', 'rascunho')->count();
        $emValidacao = (clone $baseResumo)->whereIn('status', ['enviado', 'validado'])->count();
        $finalizados = (clone $baseResumo)->where('status', 'finalizado')->count();

        $mesRefSla = Carbon::createFromFormat('Y-m', $competenciaFiltro)->startOfMonth();
        $prazoSla = SsmaRegistroMensalPrazo::prazoEfetivoParaCompetencia($mesRefSla);

        $situacaoSla = null;
        if ($prazoSla) {
            $situacaoSla = array_merge(
                [
                    'prazo' => $prazoSla,
                    'data_limite_efetiva' => $prazoSla->dataLimiteEfetiva($mesRefSla),
                    'competencia_filtro_label' => $mesRefSla->format('m/Y'),
                ],
                $prazoSla->situacaoNoMes($mesRefSla)
            );
        }

        return view('sesmt.registros.index', compact(
            'registros',
            'competenciaFiltro',
            'total',
            'rascunhos',
            'emValidacao',
            'finalizados',
            'situacaoSla'
        ));
    }

    public function create(Request $request)
    {
        $colaboradores = Colaborador::query()
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome', 'matricula', 'foto_path']);

        if ($request->filled('competencia')) {
            $ck = Carbon::createFromFormat('Y-m', (string) $request->query('competencia'))->startOfMonth()->toDateString();
            $existente = SsmaRegistroMensal::query()->whereDate('competencia', $ck)->orderByDesc('id')->first();
            if ($existente) {
                return redirect()->route('sesmt.registros.edit', $existente);
            }
        }

        $resumoLinhas = SsmaRegistroMensalResumo::linhas(old('etapas', []));

        return view('sesmt.registros.create', [
            'etapas' => SsmaRegistroMensal::ETAPAS,
            'colaboradores' => $colaboradores,
            'registro' => null,
            'fill' => [],
            'resumoLinhas' => $resumoLinhas,
        ]);
    }

    public function preview(SsmaRegistroMensal $registro)
    {
        $resumoLinhas = SsmaRegistroMensalResumo::linhas($registro->etapas ?? []);

        return view('sesmt.registros.preview', [
            'registro' => $registro,
            'resumoLinhas' => $resumoLinhas,
        ]);
    }

    public function edit(SsmaRegistroMensal $registro)
    {
        $colaboradores = Colaborador::query()
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome', 'matricula', 'foto_path']);

        $resumoLinhas = SsmaRegistroMensalResumo::linhas(old('etapas', $registro->etapas ?? []));

        return view('sesmt.registros.create', [
            'etapas' => SsmaRegistroMensal::ETAPAS,
            'colaboradores' => $colaboradores,
            'registro' => $registro,
            'fill' => $this->normalizeEtapasForForm($registro->etapas),
            'resumoLinhas' => $resumoLinhas,
        ]);
    }

    public function update(Request $request, SsmaRegistroMensal $registro)
    {
        $data = $this->validatedRegistroPayload($request);
        $data['competencia'] = $registro->competencia->format('Y-m');
        $this->aplicarAcaoFluxo($request, $data);

        $etapas = SsmaRegistroMensalEtapasAssembler::assemble($request, $data, $registro->etapas);

        $registro->update(array_merge($this->atributosCabecalhoRegistro($data), [
            'etapas' => $etapas,
        ]));

        $msg = $request->input('acao') === 'enviar_validacao'
            ? 'Registro enviado para validação SSMA.'
            : 'Registro mensal SSMA atualizado com sucesso.';

        return redirect()
            ->route('sesmt.registros.index', ['competencia' => $data['competencia']])
            ->with('success', $msg);
    }

    /** @return array<string, mixed> */
    protected function normalizeEtapasForForm(?array $etapas): array
    {
        if (empty($etapas)) {
            return [];
        }

        $e = $etapas;
        if (isset($e['campanha_seguranca']['campanhas'])) {
            $e['campanha_seguranca']['itens'] = $e['campanha_seguranca']['campanhas'];
        }

        $colabSnap = data_get($e, 'boas_praticas_kaizen.colaboradores');
        if (is_array($colabSnap) && $colabSnap !== []) {
            $ids = collect($colabSnap)->pluck('id')->filter()->map(fn ($id) => (string) $id)->values()->all();
            $e['boas_praticas_kaizen'] ??= [];
            $e['boas_praticas_kaizen']['colaborador_ids'] = $ids;
        }

        return $e;
    }

    public function store(Request $request)
    {
        $data = $this->validatedRegistroPayload($request);
        $this->aplicarAcaoFluxo($request, $data);

        $compKey = Carbon::createFromFormat('Y-m', $data['competencia'])->startOfMonth()->toDateString();
        if (SsmaRegistroMensal::query()->whereDate('competencia', $compKey)->exists()) {
            throw ValidationException::withMessages([
                'competencia' => 'Já existe um registro para esta competência. Use Preencher na listagem.',
            ]);
        }

        $etapas = SsmaRegistroMensalEtapasAssembler::assemble($request, $data, null);

        SsmaRegistroMensal::create(array_merge($this->atributosCabecalhoRegistro($data), [
            'competencia' => Carbon::createFromFormat('Y-m', $data['competencia'])->startOfMonth()->toDateString(),
            'etapas' => $etapas,
        ]));

        $msg = $request->input('acao') === 'enviar_validacao'
            ? 'Registro enviado para validação SSMA.'
            : 'Registro mensal SSMA criado com sucesso.';

        return redirect()
            ->route('sesmt.registros.index', ['competencia' => $data['competencia']])
            ->with('success', $msg);
    }

    /** @param  array<string, mixed>  $data */
    protected function aplicarAcaoFluxo(Request $request, array &$data): void
    {
        if ($request->input('acao') !== 'enviar_validacao') {
            return;
        }

        $st = $data['status'] ?? '';
        if (in_array($st, ['finalizado', 'validado'], true)) {
            return;
        }

        $data['status'] = 'enviado';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function atributosCabecalhoRegistro(array $data): array
    {
        return [
            'titulo' => $data['titulo'] ?? null,
            'responsavel' => $data['responsavel'] ?? null,
            'contrato' => $data['contrato'] ?? null,
            'local_base' => $data['local_base'] ?? null,
            'efetivo_ativo_mes' => $data['efetivo_ativo_mes'] ?? null,
            'hht_mes' => $data['hht_mes'] ?? null,
            'comentario_executivo' => $data['comentario_executivo'] ?? null,
            'observacoes_gerais_mes' => $data['observacoes_gerais_mes'] ?? null,
            'status' => $data['status'],
        ];
    }

    /** @return array<string, mixed> */
    protected function validatedRegistroPayload(Request $request): array
    {
        return $request->validate([
            'competencia' => ['required', 'date_format:Y-m'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'responsavel' => ['nullable', 'string', 'max:255'],
            'contrato' => ['nullable', 'string', 'max:255'],
            'local_base' => ['nullable', 'string', 'max:500'],
            'efetivo_ativo_mes' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'hht_mes' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'comentario_executivo' => ['nullable', 'string', 'max:10000'],
            'observacoes_gerais_mes' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', 'in:rascunho,enviado,validado,finalizado'],
            'etapas' => ['nullable', 'array'],
            'etapas.auditoria_mensal.passou_auditoria' => ['nullable', 'in:sim,nao'],
            'etapas.auditoria_mensal.data_auditoria' => ['nullable', 'date'],
            'etapas.auditoria_mensal.auditor' => ['nullable', 'string', 'max:255'],
            'etapas.auditoria_mensal.local' => ['nullable', 'string', 'max:500'],
            'etapas.auditoria_mensal.descricao' => ['nullable', 'string'],
            'etapas.auditoria_mensal.nota' => ['nullable', 'string', 'max:50'],
            'etapas.auditoria_mensal.evidencia_1' => ['nullable', 'file', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp,pdf'],
            'etapas.auditoria_mensal.evidencia_2' => ['nullable', 'file', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp,pdf'],
            'etapas.inspecao_mensal_canteiro.passou_inspecao' => ['nullable', 'in:sim,nao'],
            'etapas.inspecao_mensal_canteiro.data_inspecao' => ['nullable', 'date'],
            'etapas.inspecao_mensal_canteiro.inspetor' => ['nullable', 'string', 'max:255'],
            'etapas.inspecao_mensal_canteiro.local' => ['nullable', 'string', 'max:500'],
            'etapas.inspecao_mensal_canteiro.descricao' => ['nullable', 'string'],
            'etapas.inspecao_mensal_canteiro.nota' => ['nullable', 'string', 'max:50'],
            'etapas.inspecao_mensal_canteiro.evidencia_1' => ['nullable', 'file', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp,pdf'],
            'etapas.inspecao_mensal_canteiro.evidencia_2' => ['nullable', 'file', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp,pdf'],
            'etapas.treinamentos_mensais.linhas' => ['nullable', 'array'],
            'etapas.treinamentos_mensais.linhas.*.rac' => ['nullable', 'boolean'],
            'etapas.treinamentos_mensais.linhas.*.nr' => ['nullable', 'boolean'],
            'etapas.treinamentos_mensais.linhas.*.pro_outros' => ['nullable', 'string', 'max:255'],
            'etapas.treinamentos_mensais.linhas.*.data' => ['nullable', 'date'],
            'etapas.treinamentos_mensais.linhas.*.instrutor' => ['nullable', 'string', 'max:255'],
            'etapas.treinamentos_mensais.linhas.*.titulo_descricao' => ['nullable', 'string', 'max:500'],
            'etapas.registro_acoes_proativas' => ['nullable', 'array'],
            'etapas.registro_acoes_proativas.quase_acidente.linhas' => ['nullable', 'array', 'max:4'],
            'etapas.registro_acoes_proativas.quase_acidente.linhas.*.data' => ['nullable', 'date'],
            'etapas.registro_acoes_proativas.quase_acidente.linhas.*.local' => ['nullable', 'string', 'max:255'],
            'etapas.registro_acoes_proativas.quase_acidente.linhas.*.descricao' => ['nullable', 'string', 'max:500'],
            'etapas.registro_acoes_proativas.termo_interdicao_vale.linhas' => ['nullable', 'array', 'max:4'],
            'etapas.registro_acoes_proativas.termo_interdicao_vale.linhas.*.data' => ['nullable', 'date'],
            'etapas.registro_acoes_proativas.termo_interdicao_vale.linhas.*.local' => ['nullable', 'string', 'max:255'],
            'etapas.registro_acoes_proativas.termo_interdicao_vale.linhas.*.descricao' => ['nullable', 'string', 'max:500'],
            'etapas.registro_acoes_proativas.termo_interdicao_vale.linhas.*.emissor' => ['nullable', 'string', 'max:255'],
            'etapas.registro_acoes_proativas.termo_notificacao_vale.linhas' => ['nullable', 'array', 'max:4'],
            'etapas.registro_acoes_proativas.termo_notificacao_vale.linhas.*.data' => ['nullable', 'date'],
            'etapas.registro_acoes_proativas.termo_notificacao_vale.linhas.*.local' => ['nullable', 'string', 'max:255'],
            'etapas.registro_acoes_proativas.termo_notificacao_vale.linhas.*.descricao' => ['nullable', 'string', 'max:500'],
            'etapas.registro_acoes_proativas.termo_notificacao_vale.linhas.*.emissor' => ['nullable', 'string', 'max:255'],
            'etapas.registro_acoes_proativas.interdicao_interna_omega.linhas' => ['nullable', 'array', 'max:6'],
            'etapas.registro_acoes_proativas.interdicao_interna_omega.linhas.*.data' => ['nullable', 'date'],
            'etapas.registro_acoes_proativas.interdicao_interna_omega.linhas.*.local' => ['nullable', 'string', 'max:255'],
            'etapas.registro_acoes_proativas.interdicao_interna_omega.linhas.*.descricao' => ['nullable', 'string', 'max:500'],
            'etapas.registro_acoes_proativas.interdicao_interna_omega.linhas.*.emissor' => ['nullable', 'string', 'max:255'],
            'etapas.registro_acoes_proativas.notificacao_interna_omega.linhas' => ['nullable', 'array', 'max:6'],
            'etapas.registro_acoes_proativas.notificacao_interna_omega.linhas.*.data' => ['nullable', 'date'],
            'etapas.registro_acoes_proativas.notificacao_interna_omega.linhas.*.local' => ['nullable', 'string', 'max:255'],
            'etapas.registro_acoes_proativas.notificacao_interna_omega.linhas.*.descricao' => ['nullable', 'string', 'max:500'],
            'etapas.registro_acoes_proativas.notificacao_interna_omega.linhas.*.emissor' => ['nullable', 'string', 'max:255'],
            'etapas.boas_praticas_kaizen' => ['nullable', 'array'],
            'etapas.boas_praticas_kaizen.titulo' => ['nullable', 'string', 'max:255'],
            'etapas.boas_praticas_kaizen.responsaveis' => ['nullable', 'string', 'max:500'],
            'etapas.boas_praticas_kaizen.ganhos_processo' => ['nullable', 'string'],
            'etapas.boas_praticas_kaizen.colaborador_ids' => ['nullable', 'array', 'max:30'],
            'etapas.boas_praticas_kaizen.colaborador_ids.*' => ['integer', Rule::exists('colaboradores', 'id')],
            'etapas.boas_praticas_kaizen.foto_antes' => ['nullable', 'file', 'max:8192', 'mimes:jpeg,jpg,png,gif,webp'],
            'etapas.boas_praticas_kaizen.foto_depois' => ['nullable', 'file', 'max:8192', 'mimes:jpeg,jpg,png,gif,webp'],
            'etapas.acoes_reativas' => ['nullable', 'array'],
            'etapas.acoes_reativas.primeiros_socorros.linhas' => ['nullable', 'array', 'max:3'],
            'etapas.acoes_reativas.primeiros_socorros.linhas.*.data' => ['nullable', 'date'],
            'etapas.acoes_reativas.primeiros_socorros.linhas.*.local' => ['nullable', 'string', 'max:255'],
            'etapas.acoes_reativas.primeiros_socorros.linhas.*.descricao' => ['nullable', 'string', 'max:500'],
            'etapas.acoes_reativas.restricao_trabalho.linhas' => ['nullable', 'array', 'max:3'],
            'etapas.acoes_reativas.restricao_trabalho.linhas.*.data' => ['nullable', 'date'],
            'etapas.acoes_reativas.restricao_trabalho.linhas.*.local' => ['nullable', 'string', 'max:255'],
            'etapas.acoes_reativas.restricao_trabalho.linhas.*.descricao' => ['nullable', 'string', 'max:500'],
            'etapas.acoes_reativas.tratamento_medico.linhas' => ['nullable', 'array', 'max:7'],
            'etapas.acoes_reativas.tratamento_medico.linhas.*.data' => ['nullable', 'date'],
            'etapas.acoes_reativas.tratamento_medico.linhas.*.local' => ['nullable', 'string', 'max:255'],
            'etapas.acoes_reativas.tratamento_medico.linhas.*.descricao' => ['nullable', 'string', 'max:500'],
            'etapas.acoes_reativas.regra_ouro.linhas' => ['nullable', 'array', 'max:3'],
            'etapas.acoes_reativas.regra_ouro.linhas.*.data' => ['nullable', 'date'],
            'etapas.acoes_reativas.regra_ouro.linhas.*.local' => ['nullable', 'string', 'max:255'],
            'etapas.acoes_reativas.regra_ouro.linhas.*.descricao' => ['nullable', 'string', 'max:500'],
            'etapas.acoes_reativas.telemetria.linhas' => ['nullable', 'array', 'max:7'],
            'etapas.acoes_reativas.telemetria.linhas.*.data' => ['nullable', 'date'],
            'etapas.acoes_reativas.telemetria.linhas.*.local' => ['nullable', 'string', 'max:255'],
            'etapas.acoes_reativas.telemetria.linhas.*.descricao' => ['nullable', 'string', 'max:500'],
            'etapas.campanha_seguranca' => ['nullable', 'array'],
            'etapas.campanha_seguranca.itens' => ['nullable', 'array', 'max:15'],
            'etapas.campanha_seguranca.itens.*.titulo' => ['nullable', 'string', 'max:255'],
            'etapas.campanha_seguranca.itens.*.local' => ['nullable', 'string', 'max:500'],
            'etapas.campanha_seguranca.itens.*.responsavel_campanha' => ['nullable', 'string', 'max:255'],
            'etapas.campanha_seguranca.itens.*.gerencia' => ['nullable', 'string', 'max:255'],
            'etapas.campanha_seguranca.itens.*.data_reuniao' => ['nullable', 'date'],
            'etapas.campanha_seguranca.itens.*.descricao' => ['nullable', 'string'],
            'etapas.campanha_seguranca.itens.*.evidencia_1' => ['nullable', 'file', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp,pdf'],
            'etapas.campanha_seguranca.itens.*.evidencia_2' => ['nullable', 'file', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp,pdf'],
            'etapas.registro_acidente' => ['nullable', 'array'],
            'etapas.registro_acidente.linhas' => ['nullable', 'array', 'max:7'],
            'etapas.registro_acidente.linhas.*.material' => ['nullable', 'boolean'],
            'etapas.registro_acidente.linhas.*.pessoal' => ['nullable', 'boolean'],
            'etapas.registro_acidente.linhas.*.ambiental' => ['nullable', 'boolean'],
            'etapas.registro_acidente.linhas.*.data' => ['nullable', 'date'],
            'etapas.registro_acidente.linhas.*.hora' => ['nullable', 'string', 'max:10'],
            'etapas.registro_acidente.linhas.*.local' => ['nullable', 'string', 'max:500'],
            'etapas.registro_acidente.linhas.*.descricao' => ['nullable', 'string', 'max:1000'],
            'etapas.registro_acidente.evidencia_1' => ['nullable', 'file', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp,pdf'],
            'etapas.registro_acidente.evidencia_2' => ['nullable', 'file', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp,pdf'],
            'etapas.*.realizado' => ['nullable', 'boolean'],
            'etapas.*.data_referencia' => ['nullable', 'date'],
            'etapas.*.responsavel' => ['nullable', 'string', 'max:255'],
            'etapas.*.observacoes' => ['nullable', 'string'],
        ]);
    }

}
