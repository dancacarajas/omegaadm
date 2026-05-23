<?php

namespace App\Services\Rh;

use App\Models\Beneficio;
use App\Models\ColaboradorBeneficio;
use App\Support\Rh\BeneficioAdesaoStatus;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

class BeneficioAdesaoService
{
    public function statusInicialParaBeneficio(Beneficio $beneficio): string
    {
        if (! $beneficio->requer_controle_adesao || $beneficio->adesao_automatica_admissao) {
            return BeneficioAdesaoStatus::ADESAO_AUTOMATICA;
        }

        return $beneficio->exige_formulario_colaborador
            ? BeneficioAdesaoStatus::PENDENTE_FORMULARIO
            : BeneficioAdesaoStatus::FORMULARIO_RECEBIDO;
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    public function normalizarDadosAdesao(ColaboradorBeneficio $vinculo, array $dados, Request $request): array
    {
        if (! $vinculo->usaControleAdesao()) {
            return $dados;
        }

        $dados = $this->unificarDataAvisoColeta($dados, $request);

        $status = (string) ($dados['status_adesao'] ?? $vinculo->status_adesao ?? BeneficioAdesaoStatus::PENDENTE_FORMULARIO);
        if (! in_array($status, BeneficioAdesaoStatus::valores(), true)) {
            $status = $vinculo->status_adesao ?? BeneficioAdesaoStatus::PENDENTE_FORMULARIO;
        }

        if (! empty($dados['data_entrega_cartao']) || $request->boolean('cartao_entregue')) {
            $status = BeneficioAdesaoStatus::CARTAO_ENTREGUE;
            if (empty($dados['data_entrega_cartao'])) {
                $dados['data_entrega_cartao'] = now()->toDateString();
            }
        } elseif ($request->boolean('beneficio_ativo')) {
            $status = BeneficioAdesaoStatus::BENEFICIO_ATIVO;
        }

        $dados['status_adesao'] = $status;
        $dados['adesao_atualizado_por_id'] = $request->user()?->id;

        if ($status === BeneficioAdesaoStatus::FORMULARIO_RECEBIDO && empty($dados['data_formulario_recebido'])) {
            $dados['data_formulario_recebido'] = now()->toDateString();
        }

        if (in_array($status, [BeneficioAdesaoStatus::ENVIADO_MATRIZ, BeneficioAdesaoStatus::AGUARDANDO_CARTAO], true)
            && empty($dados['data_envio_matriz'])
            && empty($vinculo->data_envio_matriz)) {
            $dados['data_envio_matriz'] = now()->toDateString();
        }

        if (! empty($dados['data_aviso_coleta_matriz']) && ! $request->boolean('cartao_entregue')) {
            $dados['status_adesao'] = BeneficioAdesaoStatus::CARTAO_DISPONIVEL_COLETA;
        } elseif (! empty($dados['data_envio_matriz'])
            && empty($dados['data_aviso_coleta_matriz'])
            && in_array($dados['status_adesao'], [BeneficioAdesaoStatus::ENVIADO_MATRIZ, BeneficioAdesaoStatus::AGUARDANDO_CARTAO], true)) {
            $dados['status_adesao'] = BeneficioAdesaoStatus::AGUARDANDO_CARTAO;
        }

        return $dados;
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    private function unificarDataAvisoColeta(array $dados, Request $request): array
    {
        $aviso = $dados['data_aviso_coleta_matriz']
            ?? $request->input('data_aviso_coleta_matriz')
            ?? $dados['data_retorno_matriz']
            ?? $dados['data_previsao_cartao']
            ?? null;

        if ($aviso !== null && $aviso !== '') {
            $dados['data_aviso_coleta_matriz'] = $aviso;
        }

        unset($dados['data_retorno_matriz'], $dados['data_previsao_cartao']);

        return $dados;
    }

    public function dataAvisoColeta(ColaboradorBeneficio $vinculo): ?CarbonInterface
    {
        if ($vinculo->data_aviso_coleta_matriz !== null) {
            return $vinculo->data_aviso_coleta_matriz;
        }

        if ($vinculo->data_retorno_matriz !== null) {
            return $vinculo->data_retorno_matriz;
        }

        return $vinculo->data_previsao_cartao;
    }

    public function diasDesdeEnvioMatriz(ColaboradorBeneficio $vinculo): ?int
    {
        if ($vinculo->data_envio_matriz === null) {
            return null;
        }

        $fim = $this->dataAvisoColeta($vinculo) ?? now();

        return (int) $vinculo->data_envio_matriz->diffInDays($fim);
    }

    /** Dias entre o pedido à Matriz e o aviso de que o cartão está disponível para coleta. */
    public function diasEntrePedidoMatrizEAvisoColeta(ColaboradorBeneficio $vinculo): ?int
    {
        $aviso = $this->dataAvisoColeta($vinculo);
        if ($vinculo->data_envio_matriz === null || $aviso === null) {
            return null;
        }

        return (int) $vinculo->data_envio_matriz->diffInDays($aviso);
    }

    /** Dias aguardando aviso da Matriz (pedido feito, sem aviso de coleta ainda). */
    public function diasAguardandoAvisoMatriz(ColaboradorBeneficio $vinculo): ?int
    {
        if ($vinculo->data_envio_matriz === null || $this->dataAvisoColeta($vinculo) !== null) {
            return null;
        }

        return (int) $vinculo->data_envio_matriz->diffInDays(now());
    }

    public function diasDesdeFormulario(ColaboradorBeneficio $vinculo): ?int
    {
        if ($vinculo->data_formulario_recebido === null) {
            return null;
        }

        return (int) $vinculo->data_formulario_recebido->diffInDays(now());
    }

    public function cartaoAtrasado(ColaboradorBeneficio $vinculo, int $diasLimite = 15): bool
    {
        if ($vinculo->cartao_entregue || $this->dataAvisoColeta($vinculo) !== null) {
            return false;
        }

        $dias = $this->diasAguardandoAvisoMatriz($vinculo);

        return $dias !== null && $dias > $diasLimite;
    }

    /**
     * Indicador de prazo Matriz para exibição na listagem e no formulário.
     *
     * @return array{tipo: string, dias: int|null, texto: string, alerta: bool}
     */
    public function indicadorPrazoMatriz(ColaboradorBeneficio $vinculo, int $diasAlerta = 15): array
    {
        if ($vinculo->cartao_entregue) {
            $dias = $this->diasEntrePedidoMatrizEAvisoColeta($vinculo);

            return [
                'tipo' => 'entregue',
                'dias' => $dias,
                'texto' => $dias !== null
                    ? "{$dias} dia(s) entre pedido à Matriz e aviso de coleta"
                    : 'Cartão já entregue ao colaborador',
                'alerta' => false,
            ];
        }

        $diasPedidoAviso = $this->diasEntrePedidoMatrizEAvisoColeta($vinculo);
        if ($diasPedidoAviso !== null) {
            return [
                'tipo' => 'aviso_recebido',
                'dias' => $diasPedidoAviso,
                'texto' => "{$diasPedidoAviso} dia(s) entre pedido à Matriz e aviso para coleta — retire e entregue ao colaborador",
                'alerta' => false,
            ];
        }

        $diasAguardando = $this->diasAguardandoAvisoMatriz($vinculo);
        if ($diasAguardando !== null) {
            $alerta = $diasAguardando > $diasAlerta;

            return [
                'tipo' => 'aguardando_aviso',
                'dias' => $diasAguardando,
                'texto' => "{$diasAguardando} dia(s) aguardando a Matriz avisar que o cartão está para coleta".($alerta ? ' — possível atraso' : ''),
                'alerta' => $alerta,
            ];
        }

        return [
            'tipo' => 'sem_pedido',
            'dias' => null,
            'texto' => 'Informe a data do pedido enviado à Matriz',
            'alerta' => false,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function resumoPainel(int $diasAlerta = 15): array
    {
        $base = ColaboradorBeneficio::query()
            ->whereHas('beneficio', fn ($q) => $q->where('requer_controle_adesao', true));

        return [
            'pendente_formulario' => (clone $base)->where('status_adesao', BeneficioAdesaoStatus::PENDENTE_FORMULARIO)->count(),
            'formulario_recebido' => (clone $base)->where('status_adesao', BeneficioAdesaoStatus::FORMULARIO_RECEBIDO)->count(),
            'enviado_matriz' => (clone $base)->where('status_adesao', BeneficioAdesaoStatus::ENVIADO_MATRIZ)->count(),
            'aguardando_cartao' => (clone $base)->where('status_adesao', BeneficioAdesaoStatus::AGUARDANDO_CARTAO)->count(),
            'cartao_disponivel_coleta' => (clone $base)->where('status_adesao', BeneficioAdesaoStatus::CARTAO_DISPONIVEL_COLETA)->count(),
            'cartao_atrasado' => (clone $base)
                ->where('cartao_entregue', false)
                ->whereNotNull('data_envio_matriz')
                ->whereNull('data_aviso_coleta_matriz')
                ->whereNull('data_retorno_matriz')
                ->whereNull('data_previsao_cartao')
                ->whereDate('data_envio_matriz', '<=', now()->subDays($diasAlerta)->toDateString())
                ->whereIn('status_adesao', [
                    BeneficioAdesaoStatus::ENVIADO_MATRIZ,
                    BeneficioAdesaoStatus::AGUARDANDO_CARTAO,
                ])
                ->count(),
        ];
    }
}
