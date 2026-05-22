<?php

namespace App\Services\Rh;

use App\Models\Colaborador;
use App\Models\Contrato;
use App\Models\RecrutamentoVaga;
use App\Models\Rh\RhMovimentacaoChamado;
use App\Support\Rh\MovimentacaoChamadoTipo;

final class MovimentacaoSubstituicaoVagaService
{
    public function __construct(
        private readonly MovimentacaoLogService $logService,
    ) {}

    /**
     * Cria vaga de recrutamento quando a solicitação de desligamento prevê substituição.
     */
    public function criarSeSolicitado(RhMovimentacaoChamado $chamado, Colaborador $colaborador): ?RecrutamentoVaga
    {
        if ($chamado->tipo !== MovimentacaoChamadoTipo::DESLIGAMENTO) {
            return null;
        }

        $depois = $chamado->dados_depois_json ?? [];
        if (($depois['havera_substituicao_vaga'] ?? '') !== 'sim') {
            return null;
        }

        if (! empty($depois['recrutamento_vaga_id'])) {
            return RecrutamentoVaga::query()->find($depois['recrutamento_vaga_id']);
        }

        $existente = RecrutamentoVaga::query()
            ->where('form_state->origem_desligamento_chamado_id', $chamado->id)
            ->first();
        if ($existente) {
            $this->vincularVagaAoChamado($chamado, $existente);

            return $existente;
        }

        $contrato = $this->resolverContrato($colaborador);
        $contratoValor = $contrato
            ? $this->valorContratoRecrutamento($contrato)
            : $this->valorContratoFallback($colaborador);

        $titulo = $this->montarTitulo($colaborador, $chamado);
        $gestor = trim((string) ($depois['gestor_responsavel'] ?? ''));
        $local = trim((string) ($colaborador->local_trabalho ?? ''));
        if ($local === '' && $contrato) {
            $local = trim((string) ($contrato->local_execucao ?? ''));
        }
        if ($gestor === '' && $contrato) {
            $gestor = trim((string) ($contrato->gestor ?? ''));
        }

        $dataSolicitacao = today()->toDateString();
        $previsaoInicio = $chamado->data_prevista?->format('Y-m-d')
            ?? ($depois['data_prevista'] ?? null)
            ?? $dataSolicitacao;

        $formState = [
            'vaga_titulo' => $titulo,
            'vaga_quantidade' => '1',
            'vaga_tipo' => 'Substituição',
            'vaga_status' => 'Em abertura',
            'vaga_contrato' => $contratoValor,
            'vaga_gestor' => $gestor,
            'vaga_local' => $local,
            'vaga_data_solicitacao' => $dataSolicitacao,
            'vaga_previsao_inicio' => $previsaoInicio,
            'origem_desligamento' => true,
            'origem_desligamento_chamado_id' => $chamado->id,
            'origem_desligamento_protocolo' => $chamado->protocolo,
            'origem_desligamento_colaborador_id' => $colaborador->id,
            'origem_desligamento_colaborador_nome' => $colaborador->nome,
        ];

        if ($contrato) {
            $formState['origem_desligamento_contrato_id'] = $contrato->id;
        }

        $vaga = RecrutamentoVaga::query()->create([
            'titulo' => $titulo,
            'quantidade' => 1,
            'prioridade' => null,
            'tipo' => 'Substituição',
            'contrato' => $contratoValor,
            'gestor' => $gestor ?: null,
            'local' => $local ?: null,
            'data_solicitacao' => $dataSolicitacao,
            'previsao_inicio' => $previsaoInicio,
            'salario' => null,
            'status' => 'Em abertura',
            'descricao' => "Vaga aberta automaticamente para substituir o desligamento do chamado {$chamado->protocolo} ({$colaborador->nome}).",
            'requisitos' => $colaborador->cargo ? "Função de referência: {$colaborador->cargo}" : null,
            'form_state' => $formState,
        ]);

        $this->vincularVagaAoChamado($chamado, $vaga);
        $this->logService->registrar(
            $chamado,
            'vaga_substituicao_criada',
            'recrutamento_vaga_id',
            null,
            (string) $vaga->id,
            $chamado->solicitante_id,
        );

        return $vaga;
    }

    public function gestorDoContrato(Colaborador $colaborador): string
    {
        $contrato = $this->resolverContrato($colaborador);

        return trim((string) ($contrato?->gestor ?? ''));
    }

    /**
     * @param  iterable<int, Colaborador>  $colaboradores
     * @return array<int, string> colaborador_id => nome do gestor
     */
    public function mapGestoresPorColaborador(iterable $colaboradores): array
    {
        $lista = collect($colaboradores);
        if ($lista->isEmpty()) {
            return [];
        }

        $tokens = [];
        foreach ($lista as $colaborador) {
            foreach ([$colaborador->centro_custo, $colaborador->tipo_contrato] as $token) {
                $token = trim((string) $token);
                if ($token !== '') {
                    $tokens[$token] = true;
                }
            }
        }

        $tokenKeys = array_keys($tokens);
        if ($tokenKeys === []) {
            return [];
        }

        $contratos = Contrato::query()
            ->where(function ($q) use ($tokenKeys) {
                $q->whereIn('numero', $tokenKeys)
                    ->orWhereIn('centro_custo', $tokenKeys)
                    ->orWhereIn('nome', $tokenKeys);
            })
            ->get(['id', 'numero', 'centro_custo', 'nome', 'gestor']);

        $gestorPorToken = [];
        foreach ($contratos as $contrato) {
            $gestor = trim((string) ($contrato->gestor ?? ''));
            if ($gestor === '') {
                continue;
            }
            foreach ([$contrato->numero, $contrato->centro_custo, $contrato->nome] as $chave) {
                $chave = trim((string) $chave);
                if ($chave !== '') {
                    $gestorPorToken[$chave] = $gestor;
                }
            }
        }

        $map = [];
        foreach ($lista as $colaborador) {
            foreach ([$colaborador->centro_custo, $colaborador->tipo_contrato] as $token) {
                $token = trim((string) $token);
                if ($token !== '' && isset($gestorPorToken[$token])) {
                    $map[$colaborador->id] = $gestorPorToken[$token];
                    break;
                }
            }
        }

        return $map;
    }

    public function resolverContrato(Colaborador $colaborador): ?Contrato
    {
        $tokens = array_values(array_unique(array_filter([
            trim((string) $colaborador->centro_custo),
            trim((string) $colaborador->tipo_contrato),
        ])));

        foreach ($tokens as $token) {
            $contrato = Contrato::query()
                ->where('numero', $token)
                ->orWhere('centro_custo', $token)
                ->orWhere('nome', $token)
                ->first();
            if ($contrato) {
                return $contrato;
            }
        }

        return null;
    }

    public function valorContratoRecrutamento(Contrato $contrato): string
    {
        $valor = trim((string) ($contrato->numero ?: ($contrato->centro_custo ?: $contrato->nome)));

        return $valor !== '' ? $valor : '—';
    }

    private function valorContratoFallback(Colaborador $colaborador): string
    {
        return trim((string) ($colaborador->centro_custo ?: $colaborador->tipo_contrato ?: '')) ?: '—';
    }

    private function montarTitulo(Colaborador $colaborador, RhMovimentacaoChamado $chamado): string
    {
        $cargo = trim((string) ($colaborador->cargo ?? ''));
        if ($cargo !== '') {
            return "Substituição — {$cargo}";
        }

        return 'Substituição — '.$colaborador->nome.' ('.$chamado->protocolo.')';
    }

    private function vincularVagaAoChamado(RhMovimentacaoChamado $chamado, RecrutamentoVaga $vaga): void
    {
        $depois = $chamado->dados_depois_json ?? [];
        $depois['recrutamento_vaga_id'] = $vaga->id;
        $depois['recrutamento_vaga_contrato'] = $vaga->contrato;
        $chamado->update(['dados_depois_json' => $depois]);
    }
}
