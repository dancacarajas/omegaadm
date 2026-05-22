<?php

namespace App\Services\Rh;

use App\Models\Rh\RhMovimentacaoAnexo;
use App\Models\Rh\RhMovimentacaoChamado;
use App\Models\Rh\RhMovimentacaoNadaConsta;
use App\Models\Rh\RhMovimentacaoNadaConstaItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Support\Rh\MovimentacaoDesligamentoCatalog;
use App\Support\Rh\MovimentacaoChamadoTipo;

final class MovimentacaoNadaConstaService
{
    public function inicializar(RhMovimentacaoChamado $chamado): RhMovimentacaoNadaConsta
    {
        if ($chamado->tipo !== MovimentacaoChamadoTipo::DESLIGAMENTO) {
            abort(422);
        }

        $existente = RhMovimentacaoNadaConsta::query()->where('chamado_id', $chamado->id)->first();
        if ($existente !== null) {
            return $existente;
        }

        $chamado->loadMissing('solicitante');
        $dados = $chamado->dados_depois_json ?? [];
        $nomeResponsavel = $dados['responsavel_rh']
            ?? $chamado->solicitante?->name
            ?? null;

        $nada = RhMovimentacaoNadaConsta::query()->create([
            'chamado_id' => $chamado->id,
            'colaborador_id' => $chamado->colaborador_id,
            'data_emissao' => today(),
            'status' => MovimentacaoDesligamentoCatalog::NC_STATUS_PENDENTE_PREENCHIMENTO,
            'gestor_contrato' => $dados['gestor_responsavel'] ?? null,
            'responsavel_rh' => $nomeResponsavel,
        ]);

        foreach (MovimentacaoDesligamentoCatalog::areasNadaConsta() as $area => $itens) {
            foreach ($itens as $def) {
                RhMovimentacaoNadaConstaItem::query()->create([
                    'nada_consta_id' => $nada->id,
                    'area' => $area,
                    'item' => $def['slug'],
                    'status_tratativa' => MovimentacaoDesligamentoCatalog::TRATATIVA_SEM_PENDENCIA,
                ]);
            }
        }

        return $nada->fresh('itens');
    }

    /**
     * Remove itens fora do catálogo e cria os que faltarem (ex.: após alteração de regras).
     */
    public function sincronizarItensComCatalogo(RhMovimentacaoNadaConsta $nada): RhMovimentacaoNadaConsta
    {
        $nada->loadMissing('itens.anexoEvidencia', 'itens.anexoTermoBaixa', 'itens.anexoAutorizacaoDesconto');

        $validos = [];
        foreach (MovimentacaoDesligamentoCatalog::areasNadaConsta() as $area => $itens) {
            foreach ($itens as $def) {
                $validos[$area.'|'.$def['slug']] = true;
            }
        }

        foreach ($nada->itens as $item) {
            if (isset($validos[$item->area.'|'.$item->item])) {
                continue;
            }

            foreach (['anexoEvidencia', 'anexoTermoBaixa', 'anexoAutorizacaoDesconto'] as $rel) {
                $anexo = $item->{$rel};
                if ($anexo !== null) {
                    Storage::disk('public')->delete($anexo->caminho);
                    $anexo->delete();
                }
            }

            $item->delete();
        }

        foreach (MovimentacaoDesligamentoCatalog::areasNadaConsta() as $area => $itens) {
            foreach ($itens as $def) {
                $existe = $nada->itens()
                    ->where('area', $area)
                    ->where('item', $def['slug'])
                    ->exists();

                if (! $existe) {
                    RhMovimentacaoNadaConstaItem::query()->create([
                        'nada_consta_id' => $nada->id,
                        'area' => $area,
                        'item' => $def['slug'],
                        'status_tratativa' => MovimentacaoDesligamentoCatalog::TRATATIVA_SEM_PENDENCIA,
                    ]);
                }
            }
        }

        $this->recalcularStatus($nada->fresh('itens'));

        return $nada->fresh('itens');
    }

    /**
     * @param  array<int, array<string, mixed>>  $itensPayload
     */
    public function salvarItens(RhMovimentacaoNadaConsta $nada, array $itensPayload, ?int $userId = null): RhMovimentacaoNadaConsta
    {
        foreach ($itensPayload as $row) {
            $item = RhMovimentacaoNadaConstaItem::query()
                ->where('nada_consta_id', $nada->id)
                ->where('id', $row['id'] ?? 0)
                ->first();

            if ($item === null) {
                continue;
            }

            $temDebito = isset($row['tem_debito']) ? (bool) $row['tem_debito'] : null;
            $status = (string) ($row['status_tratativa'] ?? $item->status_tratativa);

            $descricao = $row['descricao_pendencia'] ?? null;
            $valor = $row['valor_pendencia'] ?? null;
            $responsavel = filled($row['responsavel_nome'] ?? null)
                ? (string) $row['responsavel_nome']
                : ($userId !== null ? \App\Models\User::query()->find($userId)?->name : null);

            if ($temDebito === false) {
                $status = MovimentacaoDesligamentoCatalog::TRATATIVA_SEM_PENDENCIA;
                $descricao = null;
                $valor = null;
                $responsavel = null;
            } elseif ($temDebito === true && $status === MovimentacaoDesligamentoCatalog::TRATATIVA_SEM_PENDENCIA) {
                $status = MovimentacaoDesligamentoCatalog::TRATATIVA_PENDENTE;
            } elseif ($temDebito !== true) {
                $responsavel = null;
            }

            $item->update([
                'tem_debito' => $temDebito,
                'descricao_pendencia' => $descricao,
                'valor_pendencia' => $valor,
                'status_tratativa' => $status,
                'responsavel_nome' => $responsavel,
                'observacao' => $row['observacao'] ?? null,
                'validado_por' => $userId,
                'validado_em' => now(),
            ]);
        }

        $this->recalcularStatus($nada->fresh('itens'));

        return $nada->fresh('itens');
    }

    public function validarRh(RhMovimentacaoNadaConsta $nada, ?int $userId = null): RhMovimentacaoNadaConsta
    {
        $nada->update([
            'validado_rh' => true,
            'validado_rh_por' => $userId,
            'validado_rh_em' => now(),
            'status' => MovimentacaoDesligamentoCatalog::NC_STATUS_VALIDADO_RH,
        ]);

        return $nada->fresh('itens');
    }

    public function recalcularStatus(RhMovimentacaoNadaConsta $nada): void
    {
        $nada->loadMissing('itens');

        $temPendenciaAberta = $nada->itens->contains(fn ($i) => $i->pendenciaAberta());
        $temDebitoSemConferir = $nada->itens->contains(fn ($i) => $i->tem_debito === null);

        if ($temDebitoSemConferir) {
            $status = MovimentacaoDesligamentoCatalog::NC_STATUS_PENDENTE_PREENCHIMENTO;
        } elseif ($temPendenciaAberta) {
            $aguardaDesconto = $nada->itens->contains(
                fn ($i) => $i->pendenciaAberta() && $i->status_tratativa === MovimentacaoDesligamentoCatalog::TRATATIVA_AGUARDANDO_RH
            );
            $status = $aguardaDesconto
                ? MovimentacaoDesligamentoCatalog::NC_STATUS_AGUARDANDO_AUTORIZACAO
                : MovimentacaoDesligamentoCatalog::NC_STATUS_COM_PENDENCIA;
        } else {
            $status = MovimentacaoDesligamentoCatalog::NC_STATUS_REGULARIZADO;
        }

        if ($nada->validado_rh) {
            $status = MovimentacaoDesligamentoCatalog::NC_STATUS_VALIDADO_RH;
        }

        $nada->update(['status' => $status]);
    }

    public function anexarNoItem(
        RhMovimentacaoNadaConstaItem $item,
        string $tipo,
        UploadedFile $file,
        ?int $userId = null
    ): RhMovimentacaoNadaConstaItem {
        $nada = $item->nadaConsta()->with('chamado')->firstOrFail();
        $chamado = $nada->chamado;
        abort_if($chamado === null, 404);

        $path = $file->store('rh/chamados-movimentacao/'.$chamado->id.'/nada-consta', 'public');
        $anexo = RhMovimentacaoAnexo::query()->create([
            'chamado_id' => $chamado->id,
            'nome_arquivo' => $file->getClientOriginalName(),
            'caminho' => $path,
            'tipo_documento' => 'nada_consta_'.$tipo.'_'.$item->area.'_'.$item->item,
            'uploaded_by' => $userId,
        ]);

        $campo = match ($tipo) {
            'evidencia' => 'anexo_evidencia_id',
            'termo_baixa' => 'anexo_termo_baixa_id',
            'autorizacao_desconto' => 'anexo_autorizacao_desconto_id',
            default => abort(422, 'Tipo de anexo inválido.'),
        };

        $item->update([$campo => $anexo->id]);
        $this->recalcularStatus($item->nadaConsta->fresh('itens'));

        return $item->fresh();
    }
}
