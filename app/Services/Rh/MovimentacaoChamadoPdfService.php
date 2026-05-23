<?php

namespace App\Services\Rh;

use App\Models\Rh\RhMovimentacaoAnexo;
use App\Models\Rh\RhMovimentacaoChamado;
use App\Support\Pdf\DompdfArial;
use App\Support\Pdf\MovimentacaoPdfBranding;
use App\Support\Rh\ColaboradorMovimentacaoTipos;
use App\Support\Rh\MovimentacaoDesligamentoCatalog;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;

final class MovimentacaoChamadoPdfService
{
    /**
     * Gera PDF, grava em disco e registra anexo no chamado.
     */
    public function gerarEArmazenar(RhMovimentacaoChamado $chamado, ?int $userId = null): RhMovimentacaoAnexo
    {
        $chamado->loadMissing([
            'colaborador',
            'etapas.checklistItens',
            'anexos',
            'nadaConsta.itens',
            'solicitante',
            'logs.usuario',
        ]);

        $conteudo = $this->renderPdf($chamado);
        $nome = 'chamado-'.$chamado->protocolo.'-'.now()->format('Ymd_His').'.pdf';
        $caminho = 'rh/chamados-movimentacao/'.$chamado->id.'/'.$nome;
        Storage::disk('public')->put($caminho, $conteudo);

        RhMovimentacaoAnexo::query()
            ->where('chamado_id', $chamado->id)
            ->where('tipo_documento', MovimentacaoDesligamentoCatalog::ANEXO_CHAMADO_PDF)
            ->delete();

        $anexo = RhMovimentacaoAnexo::query()->create([
            'chamado_id' => $chamado->id,
            'nome_arquivo' => $nome,
            'caminho' => $caminho,
            'tipo_documento' => MovimentacaoDesligamentoCatalog::ANEXO_CHAMADO_PDF,
            'obrigatorio' => false,
            'uploaded_by' => $userId,
        ]);

        return $anexo;
    }

    public function renderPdf(RhMovimentacaoChamado $chamado): string
    {
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

    private function html(RhMovimentacaoChamado $chamado): string
    {
        $dados = $chamado->dados_depois_json ?? [];
        $tiposRescisao = ColaboradorMovimentacaoTipos::tiposRescisao();

        $setor = MovimentacaoPdfBranding::resolverSetorTrabalho($chamado->colaborador, $dados);

        return view('rh.chamados-movimentacao.pdf', [
            'chamado' => $chamado,
            'dados' => $dados,
            'tiposRescisao' => $tiposRescisao,
            'labelsAnexos' => MovimentacaoDesligamentoCatalog::labelsAnexos(),
            'labelsAreas' => MovimentacaoDesligamentoCatalog::labelsAreas(),
            'areasCatalogo' => MovimentacaoDesligamentoCatalog::areasNadaConsta(),
            'geradoEm' => now(),
            'logoBase64' => MovimentacaoPdfBranding::logoBase64(),
            'setorTrabalho' => $setor !== '' ? $setor : '—',
        ])->render();
    }
}
