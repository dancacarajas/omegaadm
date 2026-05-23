<?php

namespace App\Services\Rh;

use App\Models\Rh\RhMovimentacaoAnexo;
use App\Models\Rh\RhMovimentacaoChamado;
use App\Models\Rh\RhMovimentacaoNadaConstaItem;
use App\Support\Pdf\DompdfArial;
use App\Support\Rh\MovimentacaoDesligamentoCatalog;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;

final class MovimentacaoNadaConstaPdfService
{
    public function __construct(
        private readonly MovimentacaoNadaConstaService $nadaConstaService,
    ) {}

    public function renderPdf(RhMovimentacaoChamado $chamado): string
    {
        $chamado = $this->prepararChamado($chamado);

        $options = new Options();
        DompdfArial::applyOptions($options);
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $pdf = new Dompdf($options);
        $pdf->loadHtml($this->html($chamado), 'UTF-8');
        $pdf->setPaper('letter', 'portrait');
        $pdf->render();

        return $pdf->output();
    }

    public function gerarEArmazenar(RhMovimentacaoChamado $chamado, ?int $userId = null): RhMovimentacaoAnexo
    {
        $chamado = $this->prepararChamado($chamado);
        $nada = $chamado->nadaConsta;
        abort_if($nada === null, 404);

        $conteudo = $this->renderPdf($chamado);
        $matricula = $chamado->colaborador->matricula ?? $chamado->colaborador_id;
        $nome = 'nada-consta-'.$matricula.'-'.now()->format('Ymd_His').'.pdf';
        $caminho = 'rh/chamados-movimentacao/'.$chamado->id.'/'.$nome;
        Storage::disk('public')->put($caminho, $conteudo);

        $anexoAnteriorId = $nada->arquivo_pdf_id;
        if ($anexoAnteriorId !== null) {
            $anexoAnterior = RhMovimentacaoAnexo::query()->find($anexoAnteriorId);
            if ($anexoAnterior !== null && $anexoAnterior->tipo_documento === MovimentacaoDesligamentoCatalog::ANEXO_NADA_CONSTA_PDF) {
                Storage::disk('public')->delete($anexoAnterior->caminho);
                $anexoAnterior->delete();
            }
        }

        $anexo = RhMovimentacaoAnexo::query()->create([
            'chamado_id' => $chamado->id,
            'nome_arquivo' => $nome,
            'caminho' => $caminho,
            'tipo_documento' => MovimentacaoDesligamentoCatalog::ANEXO_NADA_CONSTA_PDF,
            'obrigatorio' => false,
            'uploaded_by' => $userId,
        ]);

        $nada->update(['arquivo_pdf_id' => $anexo->id]);

        return $anexo;
    }

    private function prepararChamado(RhMovimentacaoChamado $chamado): RhMovimentacaoChamado
    {
        $chamado->loadMissing(['colaborador', 'nadaConsta.itens']);
        abort_if($chamado->nadaConsta === null, 404, 'Nada Consta não iniciado para este chamado.');

        $this->nadaConstaService->sincronizarItensComCatalogo($chamado->nadaConsta);

        return $chamado->fresh(['colaborador', 'nadaConsta.itens']);
    }

    private function html(RhMovimentacaoChamado $chamado): string
    {
        $dados = $chamado->dados_depois_json ?? [];
        $colaborador = $chamado->colaborador;
        $nada = $chamado->nadaConsta;

        $itensPorArea = [];
        foreach (MovimentacaoDesligamentoCatalog::ordemAreasNadaConstaPdf() as $area) {
            $definicoes = MovimentacaoDesligamentoCatalog::areasNadaConsta()[$area] ?? [];
            if ($definicoes === []) {
                continue;
            }

            $registros = $nada->itens->where('area', $area)->keyBy('item');
            $linhas = [];
            foreach ($definicoes as $def) {
                $item = $registros->get($def['slug']);
                if ($item === null) {
                    continue;
                }
                $linhas[] = $this->linhaPdf($item, $def['nome']);
            }

            if ($linhas !== []) {
                $itensPorArea[$area] = $linhas;
            }
        }

        $local = $this->resolverLocal($colaborador, $dados);
        $setorTrabalho = $this->resolverSetorTrabalho($colaborador, $dados);

        return view('rh.chamados-movimentacao.pdf-nada-consta', [
            'chamado' => $chamado,
            'nada' => $nada,
            'colaborador' => $colaborador,
            'dados' => $dados,
            'itensPorArea' => $itensPorArea,
            'labelsAreas' => MovimentacaoDesligamentoCatalog::labelsAreas(),
            'logoBase64' => $this->logoBase64(),
            'local' => $local,
            'setorTrabalho' => $setorTrabalho !== '' ? $setorTrabalho : '—',
            'dataEmissao' => $nada->data_emissao ?? today(),
        ])->render();
    }

    /**
     * @return array{nome: string, sim_padded: string, nao_padded: string, qual: string}
     */
    private function linhaPdf(RhMovimentacaoNadaConstaItem $item, string $nome): array
    {
        $sim = '  ';
        $nao = '  ';
        if ($item->tem_debito === true) {
            $sim = ' X';
        } elseif ($item->tem_debito === false) {
            $nao = 'X ';
        }

        $qual = '';
        if ($item->tem_debito === true && filled($item->descricao_pendencia)) {
            $qual = (string) $item->descricao_pendencia;
        } elseif ($item->tem_debito === true && filled($item->valor_pendencia)) {
            $qual = 'R$ '.number_format((float) $item->valor_pendencia, 2, ',', '.');
        }

        return [
            'nome' => mb_strtoupper($nome, 'UTF-8'),
            'sim_padded' => $sim,
            'nao_padded' => $nao,
            'qual' => $qual,
        ];
    }

    /**
     * Setor de trabalho no PDF = centro de custo do colaborador (ex.: CT 286 - SALOBO).
     *
     * @param  array<string, mixed>  $dados
     */
    private function resolverSetorTrabalho(\App\Models\Colaborador $colaborador, array $dados): string
    {
        $centroCusto = trim((string) ($colaborador->centro_custo ?? ''));
        if ($centroCusto === '') {
            $centroCusto = trim((string) ($dados['centro_custo'] ?? ''));
        }

        $localTrabalho = trim((string) ($colaborador->local_trabalho ?? ''));
        if ($centroCusto !== '' && $localTrabalho !== '' && ! str_contains(strtoupper($centroCusto), strtoupper($localTrabalho))) {
            return $centroCusto.' - '.$localTrabalho;
        }

        return $centroCusto !== '' ? $centroCusto : $localTrabalho;
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function resolverLocal(\App\Models\Colaborador $colaborador, array $dados): string
    {
        if (filled($dados['local_emissao'] ?? null)) {
            return (string) $dados['local_emissao'];
        }

        $cidade = trim((string) ($colaborador->cidade ?? ''));
        $uf = trim((string) ($colaborador->estado ?? ''));
        if ($cidade !== '' && $uf !== '') {
            return strtoupper($cidade).'-'.strtoupper($uf);
        }

        $local = trim((string) ($colaborador->local_trabalho ?? ''));

        return $local !== '' ? strtoupper($local) : '—';
    }

    private function logoBase64(): ?string
    {
        $path = public_path('logo.png');
        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($path));
    }
}
