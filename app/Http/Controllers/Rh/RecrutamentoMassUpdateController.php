<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Contrato;
use App\Models\RecrutamentoVaga;
use App\Support\ContratoAccess;
use App\Support\RecrutamentoCandidatoFase;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RecrutamentoMassUpdateController extends Controller
{
    private const ABAS = [
        'cadastro' => 'Cadastro',
        'exame_medico' => 'Exame médico',
        'treinamentos' => 'Treinamentos',
        'assinatura' => 'Assinatura documental',
        'sgc' => 'SGC / mobilização',
        'liberacao' => 'Liberação',
        'concluido' => 'Concluído',
    ];

    public function index(Request $request): View
    {
        $contratoSelecionado = trim((string) $request->get('contrato', ''));

        $centrosDeCusto = ContratoAccess::applyContratoModel(Contrato::query())
            ->where('status', 'Ativo')
            ->orderBy('centro_custo')
            ->get(['centro_custo'])
            ->pluck('centro_custo')
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values();

        $porAba = collect(self::ABAS)->mapWithKeys(fn ($titulo, $slug) => [$slug => collect()])->all();

        if ($contratoSelecionado !== '') {
            $this->authorizeContratoString($contratoSelecionado);

            $vagas = ContratoAccess::applyContratoString(RecrutamentoVaga::query())
                ->where('contrato', $contratoSelecionado)
                ->latest()
                ->get();

            foreach ($vagas as $vaga) {
                $state = $vaga->form_state ?? [];
                $qty = max(1, (int) ($state['vaga_quantidade'] ?? $vaga->quantidade ?? 1));

                foreach (range(1, $qty) as $position) {
                    $nome = trim((string) ($state["candidato_{$position}_nome_completo"] ?? ''));
                    if ($nome === '') {
                        continue;
                    }

                    $status = (string) ($state["candidato_{$position}_status"] ?? 'pendente');
                    if ($status !== 'aprovado') {
                        continue;
                    }

                    $fase = RecrutamentoCandidatoFase::faseAtualLabel($state, $position);
                    $aba = RecrutamentoCandidatoFase::abaParaFase($fase);
                    if (! array_key_exists($aba, self::ABAS)) {
                        continue;
                    }

                    $exameOk = RecrutamentoCandidatoFase::etapaExameMedicoConcluida($state, $position);
                    $treinoOk = RecrutamentoCandidatoFase::etapaTreinamentosConcluida($state, $position);
                    $assinOk = RecrutamentoCandidatoFase::etapaAssinaturaConcluida($state, $position);
                    $sgcOk = RecrutamentoCandidatoFase::etapaSgcConcluida($state, $position);

                    $podeInicioTreino = $exameOk
                        && $aba === 'treinamentos'
                        && ! $treinoOk;

                    $podeAgendamentoExame = $aba === 'exame_medico'
                        && ! $exameOk;

                    $iniTr = $state["candidato_{$position}_treinamentos_data_inicio"] ?? null;
                    $podeConfTreino = in_array($aba, ['treinamentos'], true)
                        && $exameOk
                        && ! $treinoOk
                        && filled($iniTr)
                        && ! RecrutamentoCandidatoFase::hasLegacyMirroredTrainingData($state, $position);

                    $podeAssinatura = $aba === 'assinatura'
                        && $treinoOk
                        && ! $assinOk;

                    $pendSgc = filled($state["candidato_{$position}_sgc_pendencia_descricao"] ?? null);
                    $podeSgc = $aba === 'sgc'
                        && $assinOk
                        && ! $sgcOk
                        && ! $pendSgc;

                    $porAba[$aba]->push([
                        'vaga_id' => $vaga->id,
                        'vaga_titulo' => $vaga->titulo,
                        'contrato' => $vaga->contrato,
                        'posicao' => $position,
                        'nome' => $nome,
                        'fase' => $fase,
                        'exame_concluido' => $exameOk,
                        'pode_definir_agendamento_exame' => $podeAgendamentoExame,
                        'pode_definir_inicio_treino' => $podeInicioTreino,
                        'pode_definir_confirmacao_treino' => $podeConfTreino,
                        'pode_definir_assinatura' => $podeAssinatura,
                        'pode_definir_sgc' => $podeSgc,
                    ]);
                }
            }

            foreach ($porAba as $slug => $lista) {
                $porAba[$slug] = $lista->sortBy('nome')->values();
            }
        }

        return view('rh.recrutamento.mass-update', [
            'centrosDeCusto' => $centrosDeCusto,
            'contratoSelecionado' => $contratoSelecionado,
            'abasTitulos' => self::ABAS,
            'porAba' => $porAba,
        ]);
    }

    public function apply(Request $request, RecrutamentoController $recrutamentoController): JsonResponse
    {
        $acoes = [
            'exame_medico_data_agendamento',
            'treinamentos_data_inicio',
            'treinamentos_data_confirmacao',
            'assinatura_confirmacao',
            'sgc_lote',
        ];

        $rules = [
            'contrato' => ['required', 'string'],
            'acao' => ['required', 'string', Rule::in($acoes)],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.vaga_id' => ['required', 'integer'],
            'itens.*.posicao' => ['required', 'integer', 'min:1'],
            'data_agendamento_exame' => ['nullable', 'date'],
            'data_inicio' => ['nullable', 'date'],
            'data_confirmacao' => ['nullable', 'date'],
            'assinatura_data_confirmacao' => ['nullable', 'date'],
            'assinatura_data_programada' => ['nullable', 'date'],
            'sgc_data_postagem' => ['nullable', 'date'],
            'sgc_numero_postagem' => ['nullable', 'string', 'max:120'],
            'sgc_data_mobilizacao' => ['nullable', 'date'],
        ];

        $validated = $request->validate($rules);
        $acao = $validated['acao'];

        if ($acao === 'exame_medico_data_agendamento' && blank($validated['data_agendamento_exame'] ?? null)) {
            return response()->json(['message' => 'Informe a data de agendamento do exame médico.'], 422);
        }
        if ($acao === 'treinamentos_data_inicio' && blank($validated['data_inicio'] ?? null)) {
            return response()->json(['message' => 'Informe a data de início dos treinamentos.'], 422);
        }
        if ($acao === 'treinamentos_data_confirmacao' && blank($validated['data_confirmacao'] ?? null)) {
            return response()->json(['message' => 'Informe a data de confirmação dos treinamentos.'], 422);
        }
        if ($acao === 'assinatura_confirmacao' && blank($validated['assinatura_data_confirmacao'] ?? null)) {
            return response()->json(['message' => 'Informe a data de confirmação da assinatura.'], 422);
        }
        if ($acao === 'sgc_lote') {
            foreach (['sgc_data_postagem', 'sgc_numero_postagem', 'sgc_data_mobilizacao'] as $campo) {
                if (blank($validated[$campo] ?? null)) {
                    return response()->json(['message' => 'Preencha postagem, número e mobilização do SGC.'], 422);
                }
            }
        }

        $contrato = trim((string) $validated['contrato']);
        $this->authorizeContratoString($contrato);

        $dataAgendamentoExame = filled($validated['data_agendamento_exame'] ?? null)
            ? Carbon::parse($validated['data_agendamento_exame'])->toDateString()
            : null;
        $dataInicio = filled($validated['data_inicio'] ?? null)
            ? Carbon::parse($validated['data_inicio'])->toDateString()
            : null;
        $dataConfTreino = filled($validated['data_confirmacao'] ?? null)
            ? Carbon::parse($validated['data_confirmacao'])->toDateString()
            : null;
        $dataConfAssin = filled($validated['assinatura_data_confirmacao'] ?? null)
            ? Carbon::parse($validated['assinatura_data_confirmacao'])->toDateString()
            : null;
        $dataProgAssin = filled($validated['assinatura_data_programada'] ?? null)
            ? Carbon::parse($validated['assinatura_data_programada'])->toDateString()
            : null;
        $sgcPost = filled($validated['sgc_data_postagem'] ?? null)
            ? Carbon::parse($validated['sgc_data_postagem'])->toDateString()
            : null;
        $sgcMob = filled($validated['sgc_data_mobilizacao'] ?? null)
            ? Carbon::parse($validated['sgc_data_mobilizacao'])->toDateString()
            : null;
        $sgcNum = isset($validated['sgc_numero_postagem']) ? trim((string) $validated['sgc_numero_postagem']) : '';

        $aplicados = [];
        $ignorados = [];

        $porVaga = [];
        foreach ($validated['itens'] as $item) {
            $vid = (int) $item['vaga_id'];
            $porVaga[$vid][] = (int) $item['posicao'];
        }

        foreach ($porVaga as $vagaId => $positions) {
            $vaga = RecrutamentoVaga::query()->find($vagaId);
            if (! $vaga || trim((string) $vaga->contrato) !== $contrato) {
                foreach ($positions as $position) {
                    $ignorados[] = [
                        'vaga_id' => $vagaId,
                        'posicao' => $position,
                        'motivo' => 'Vaga inválida ou contrato diferente.',
                    ];
                }

                continue;
            }

            $this->authorizeContratoString($vaga->contrato);

            $state = $vaga->form_state ?? [];
            $qty = max(1, (int) ($state['vaga_quantidade'] ?? $vaga->quantidade ?? 1));
            $tituloAnterior = (string) $vaga->titulo;
            $alterou = false;

            foreach (array_unique($positions) as $position) {
                if ($position < 1 || $position > $qty) {
                    $ignorados[] = [
                        'vaga_id' => $vaga->id,
                        'posicao' => $position,
                        'motivo' => 'Posição inválida na vaga.',
                    ];

                    continue;
                }

                $nome = trim((string) ($state["candidato_{$position}_nome_completo"] ?? ''));
                $status = (string) ($state["candidato_{$position}_status"] ?? 'pendente');

                if ($nome === '' || $status !== 'aprovado') {
                    $ignorados[] = [
                        'vaga_id' => $vaga->id,
                        'posicao' => $position,
                        'motivo' => 'Candidato não aprovado ou sem nome.',
                    ];

                    continue;
                }

                if ($acao === 'exame_medico_data_agendamento') {
                    if (RecrutamentoCandidatoFase::etapaExameMedicoConcluida($state, $position)) {
                        $ignorados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome, 'motivo' => 'Exame médico já concluído na ficha.'];

                        continue;
                    }

                    $faseAtual = RecrutamentoCandidatoFase::faseAtualLabel($state, $position);
                    $abaAtual = RecrutamentoCandidatoFase::abaParaFase($faseAtual);
                    if ($abaAtual !== 'exame_medico') {
                        $ignorados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome, 'motivo' => 'Candidato não está na etapa de exame médico.'];

                        continue;
                    }

                    $state["candidato_{$position}_exameMedico_data_agendamento"] = $dataAgendamentoExame;
                    $alterou = true;
                    $aplicados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome];

                    continue;
                }

                if ($acao === 'treinamentos_data_inicio') {
                    if (! RecrutamentoCandidatoFase::etapaExameMedicoConcluida($state, $position)) {
                        $ignorados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome, 'motivo' => 'Exame médico ainda não concluído na ficha.'];

                        continue;
                    }

                    if (RecrutamentoCandidatoFase::etapaTreinamentosConcluida($state, $position)) {
                        $ignorados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome, 'motivo' => 'Treinamentos já concluídos.'];

                        continue;
                    }

                    $state["candidato_{$position}_treinamentos_data_inicio"] = $dataInicio;
                    RecrutamentoCandidatoFase::sincronizarTreinamentosCapacitacao($state, $position);
                    $alterou = true;
                    $aplicados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome];

                    continue;
                }

                if ($acao === 'treinamentos_data_confirmacao') {
                    if (! RecrutamentoCandidatoFase::etapaExameMedicoConcluida($state, $position)) {
                        $ignorados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome, 'motivo' => 'Exame médico não concluído.'];

                        continue;
                    }

                    if (RecrutamentoCandidatoFase::etapaTreinamentosConcluida($state, $position)) {
                        $ignorados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome, 'motivo' => 'Treinamentos já concluídos.'];

                        continue;
                    }

                    if (! filled($state["candidato_{$position}_treinamentos_data_inicio"] ?? null)) {
                        $ignorados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome, 'motivo' => 'Informe antes a data de início dos treinamentos.'];

                        continue;
                    }

                    if (RecrutamentoCandidatoFase::hasLegacyMirroredTrainingData($state, $position)) {
                        $ignorados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome, 'motivo' => 'Dados legados espelhados — ajuste na ficha individual.'];

                        continue;
                    }

                    $state["candidato_{$position}_treinamentos_data_confirmacao"] = $dataConfTreino;
                    RecrutamentoCandidatoFase::sincronizarTreinamentosCapacitacao($state, $position);
                    $alterou = true;
                    $aplicados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome];

                    continue;
                }

                if ($acao === 'assinatura_confirmacao') {
                    if (! RecrutamentoCandidatoFase::etapaTreinamentosConcluida($state, $position)) {
                        $ignorados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome, 'motivo' => 'Treinamentos não concluídos.'];

                        continue;
                    }

                    if (RecrutamentoCandidatoFase::etapaAssinaturaConcluida($state, $position)) {
                        $ignorados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome, 'motivo' => 'Assinatura já registrada.'];

                        continue;
                    }

                    $state["candidato_{$position}_assinatura_data_confirmacao"] = $dataConfAssin;
                    if ($dataProgAssin !== null) {
                        $state["candidato_{$position}_assinatura_data_programada"] = $dataProgAssin;
                    }
                    $alterou = true;
                    $aplicados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome];

                    continue;
                }

                if ($acao === 'sgc_lote') {
                    if (! RecrutamentoCandidatoFase::etapaAssinaturaConcluida($state, $position)) {
                        $ignorados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome, 'motivo' => 'Assinatura não concluída.'];

                        continue;
                    }

                    if (RecrutamentoCandidatoFase::etapaSgcConcluida($state, $position)) {
                        $ignorados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome, 'motivo' => 'SGC já concluído.'];

                        continue;
                    }

                    if (filled($state["candidato_{$position}_sgc_pendencia_descricao"] ?? null)) {
                        $ignorados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome, 'motivo' => 'Há pendência SGC na ficha — tratar individualmente.'];

                        continue;
                    }

                    $state["candidato_{$position}_sgc_data_postagem"] = $sgcPost;
                    $state["candidato_{$position}_sgc_numero_postagem"] = $sgcNum;
                    $state["candidato_{$position}_sgc_data_mobilizacao"] = $sgcMob;
                    $alterou = true;
                    $aplicados[] = ['vaga_id' => $vaga->id, 'posicao' => $position, 'nome' => $nome];
                }
            }

            if ($alterou) {
                $vaga->forceFill([
                    'form_state' => $state,
                    'quantidade' => $qty,
                ])->save();

                $recrutamentoController->afterFormStateSaved($vaga->fresh(), $tituloAnterior);
            }
        }

        return response()->json([
            'ok' => true,
            'aplicados' => $aplicados,
            'ignorados' => $ignorados,
            'mensagem' => count($aplicados) > 0
                ? 'Atualização aplicada: '.count($aplicados).' candidato(s).'
                : 'Nenhuma alteração aplicada.',
        ]);
    }

    private function authorizeContratoString(?string $contrato): void
    {
        if (! ContratoAccess::shouldRestrict()) {
            return;
        }

        abort_unless($contrato && in_array($contrato, ContratoAccess::contratoValores(), true), 404);
    }
}
