<?php

namespace App\Services;

use App\Models\Colaborador;
use App\Support\PublicWebBase;
use App\Support\Rh\DocumentoBr;
use App\Support\TextoApresentacaoAssinatura;

final class EmailAssinaturaService
{
    public const LOCAL_FIXO = 'Parauapebas/PA';

    /** Formato fixo do modelo: (94) 3352 0115/ + telefone do colaborador */
    public const TELEFONE_PREFIXO = '(94) 3352 0115/';

    public const LARGURA_PX = 583;

    public const ALTURA_PX = 186;

    public const TEXTO_FONT_SIZE_PX = 11;

    public const TEXTO_LINE_HEIGHT_PX = 13;

    public const TEXTO_PADDING_TOP_PX = 22;

    public const TEXTO_PADDING_LEFT_PX = 31;

    /** No GD, Arial Bold na mesma pt parece maior — reduz levemente para igualar ao regular. */
    public const TEXTO_FONT_SIZE_BOLD_RATIO = 0.96;

    public function buscarColaboradorPorCpf(string $cpf): ?Colaborador
    {
        $digitos = DocumentoBr::cpfDigitos($cpf);
        if (! DocumentoBr::cpfTemOnzeDigitos($digitos)) {
            return null;
        }

        return Colaborador::query()
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(TRIM(COALESCE(cpf, '')), '.', ''), '-', ''), ' ', '') = ?",
                [$digitos]
            )
            ->first();
    }

    /**
     * Dados brutos do colaborador (como no cadastro) — para o formulário.
     *
     * @return array{nome: string, funcao: string, contrato: string, telefone: string, email: string}
     */
    public function dadosDeColaborador(Colaborador $colaborador): array
    {
        return $this->normalizar([
            'nome' => $colaborador->nome,
            'funcao' => $colaborador->cargo,
            'contrato' => $colaborador->centro_custo,
            'telefone' => $colaborador->telefone,
            'email' => $colaborador->email,
        ]);
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array{nome: string, funcao: string, contrato: string, telefone: string, email: string}
     */
    public function normalizar(array $dados): array
    {
        return [
            'nome' => trim((string) ($dados['nome'] ?? '')),
            'funcao' => trim((string) ($dados['funcao'] ?? '')),
            'contrato' => trim((string) ($dados['contrato'] ?? '')),
            'telefone' => trim((string) ($dados['telefone'] ?? '')),
            'email' => trim((string) ($dados['email'] ?? '')),
        ];
    }

    /**
     * Formata para a assinatura (modelo oficial) sem gravar no cadastro.
     *
     * @param  array{nome: string, funcao: string, contrato: string, telefone: string, email: string}  $dados
     * @return array{nome: string, funcao: string, contrato: string, telefone: string, email: string}
     */
    public function formatarParaAssinatura(array $dados): array
    {
        return [
            'nome' => TextoApresentacaoAssinatura::nome($dados['nome']),
            'funcao' => TextoApresentacaoAssinatura::funcao($dados['funcao']),
            'contrato' => TextoApresentacaoAssinatura::contrato($dados['contrato']),
            'telefone' => $dados['telefone'],
            'email' => TextoApresentacaoAssinatura::email($dados['email']),
        ];
    }

    public function linhaTelefone(string $telefoneColaborador): string
    {
        $tel = trim($telefoneColaborador);

        return self::TELEFONE_PREFIXO.($tel !== '' ? $tel : '—');
    }

    public function backgroundUrl(): string
    {
        return $this->assetUrlAbsoluto('images/email/assinatura-eletronica-bg.jpg');
    }

    public function cssFonteArial(): string
    {
        $normal = $this->assetUrlAbsoluto('fonts/Arial.ttf');
        $bold = $this->assetUrlAbsoluto('fonts/Arial-Bold.ttf');

        return <<<HTML
<style type="text/css">
@font-face {
    font-family: 'Arial';
    font-style: normal;
    font-weight: normal;
    src: url('{$normal}') format('truetype');
}
@font-face {
    font-family: 'Arial';
    font-style: normal;
    font-weight: bold;
    src: url('{$bold}') format('truetype');
}
table, td, p, span, div { font-family: Arial, sans-serif !important; }
</style>
<!--[if mso]>
<style type="text/css">
body, table, td, p, span, div { font-family: Arial, sans-serif !important; }
</style>
<![endif]-->

HTML;
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function renderHtml(array $dados): string
    {
        $brutos = $this->normalizar($dados);
        $exibicao = $this->formatarParaAssinatura($brutos);

        $corpo = view('configuracoes.partials.assinatura-eletronica-html', [
            'dados' => $exibicao,
            'bgUrl' => $this->backgroundUrl(),
            'localFixo' => self::LOCAL_FIXO,
            'linhaTelefone' => $this->linhaTelefone($brutos['telefone']),
        ])->render();

        return $this->cssFonteArial().$corpo;
    }

    /**
     * Mesmo comportamento do localhost no browser (asset() + middleware) e /public em produção Hostinger.
     */
    private function assetUrlAbsoluto(string $path): string
    {
        $path = ltrim($path, '/');
        $asset = asset($path);

        if (str_starts_with($asset, 'http://') || str_starts_with($asset, 'https://')) {
            return $asset;
        }

        return PublicWebBase::assetUrl($path);
    }
}
