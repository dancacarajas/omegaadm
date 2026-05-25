<?php

namespace App\Support;

use App\Services\AuthEmailService;
use App\Services\Rh\BeneficioAdesaoMatrizNotificacaoService;
use App\Services\SsmaTstRegistroNotificacaoService;

/**
 * Catálogo de e-mails automáticos do sistema (hub Configurações → E-mails Enviados).
 */
final class SistemaEmailCatalogo
{
    /** @return array<string, string> */
    public static function categorias(): array
    {
        return [
            'auth' => 'Login e acesso',
            'rh' => 'RH / Benefícios',
            'sesmt' => 'SESMT / TST',
            'sistema' => 'Sistema / testes',
        ];
    }

    /**
     * @return list<array{
     *     categoria: string,
     *     categoria_label: string,
     *     tipo: string,
     *     nome: string,
     *     descricao: string,
     *     mailer: string,
     *     mailer_label: string,
     *     gatilho: string,
     *     preview_route: string|null,
     *     preview_params: array<string, string>,
     *     config_route: string|null,
     *     config_anchor: string|null
     * }>
     */
    public static function tipos(): array
    {
        $itens = [];

        foreach (AuthEmailService::tiposPreview() as $tipo => $nome) {
            $itens[] = self::item(
                categoria: 'auth',
                tipo: $tipo,
                nome: $nome,
                descricao: 'Disparado nos fluxos de cadastro, recuperação ou alteração de senha de usuários.',
                mailer: 'smtp',
                mailerLabel: 'SMTP central',
                gatilho: 'Ação em usuários (cadastro, reset, admin)',
                previewRoute: 'configuracoes.email.preview.auth',
                previewParams: ['tipo' => $tipo],
                configRoute: 'configuracoes.email.edit',
                configAnchor: null,
            );
        }

        foreach (SsmaTstRegistroNotificacaoService::tiposPreview() as $tipo => $nome) {
            $itens[] = self::item(
                categoria: 'sesmt',
                tipo: $tipo,
                nome: $nome,
                descricao: 'Enviado quando um registro TST é concluído, para os destinatários configurados.',
                mailer: 'smtp',
                mailerLabel: 'SMTP central',
                gatilho: 'Conclusão de registro TST (SESMT)',
                previewRoute: 'configuracoes.email.preview.tst',
                previewParams: ['tipo' => $tipo],
                configRoute: 'configuracoes.email.edit',
                configAnchor: 'tst-destinatarios',
            );
        }

        foreach (BeneficioAdesaoMatrizNotificacaoService::tiposPreview() as $tipo => $nome) {
            $itens[] = self::item(
                categoria: 'rh',
                tipo: $tipo,
                nome: $nome,
                descricao: 'Pedido de adesão à Matriz com PDF anexo. Dois envios: cópia interna (Omega) e destinatários Matriz (Jarbas/Zimbra).',
                mailer: 'smtp + zimbra_jarbas',
                mailerLabel: 'SMTP central + Zimbra',
                gatilho: 'RH → Benefícios → Enviar solicitação à Matriz',
                previewRoute: 'configuracoes.email.preview.beneficio-adesao',
                previewParams: ['tipo' => $tipo],
                configRoute: 'configuracoes.email.edit',
                configAnchor: 'beneficio-matriz',
            );
        }

        $itens[] = self::item(
            categoria: 'sistema',
            tipo: 'teste-smtp-central',
            nome: 'Teste SMTP central',
            descricao: 'E-mail de diagnóstico enviado pelo botão de teste na configuração burgundy.',
            mailer: 'smtp',
            mailerLabel: 'SMTP central',
            gatilho: 'Configurações → E-mail → Testar envio',
            previewRoute: null,
            previewParams: [],
            configRoute: 'configuracoes.email.edit',
            configAnchor: null,
        );

        $itens[] = self::item(
            categoria: 'sistema',
            tipo: 'teste-zimbra-jarbas',
            nome: 'Teste SMTP Zimbra (Jarbas)',
            descricao: 'Teste do mailer zimbra_jarbas, com assinatura no rodapé quando configurada.',
            mailer: 'zimbra_jarbas',
            mailerLabel: 'SMTP Zimbra',
            gatilho: 'Configurações → E-mail → Enviar teste Zimbra',
            previewRoute: null,
            previewParams: [],
            configRoute: 'configuracoes.email.edit',
            configAnchor: 'zimbra-jarbas',
        );

        return $itens;
    }

    /** @return array<string, array{categoria: string, categoria_label: string, tipo: string, nome: string}> */
    public static function indicePorTipo(): array
    {
        $indice = [];
        foreach (self::tipos() as $item) {
            $indice[$item['tipo']] = [
                'categoria' => $item['categoria'],
                'categoria_label' => $item['categoria_label'],
                'tipo' => $item['tipo'],
                'nome' => $item['nome'],
            ];
        }

        return $indice;
    }

    public static function nomeParaTipo(string $tipo): string
    {
        return self::indicePorTipo()[$tipo]['nome'] ?? $tipo;
    }

    /**
     * @return array{
     *     categoria: string,
     *     categoria_label: string,
     *     tipo: string,
     *     nome: string,
     *     descricao: string,
     *     mailer: string,
     *     mailer_label: string,
     *     gatilho: string,
     *     preview_route: string|null,
     *     preview_params: array<string, string>,
     *     config_route: string|null,
     *     config_anchor: string|null
     * }
     */
    private static function item(
        string $categoria,
        string $tipo,
        string $nome,
        string $descricao,
        string $mailer,
        string $mailerLabel,
        string $gatilho,
        ?string $previewRoute,
        array $previewParams,
        ?string $configRoute,
        ?string $configAnchor,
    ): array {
        return [
            'categoria' => $categoria,
            'categoria_label' => self::categorias()[$categoria] ?? $categoria,
            'tipo' => $tipo,
            'nome' => $nome,
            'descricao' => $descricao,
            'mailer' => $mailer,
            'mailer_label' => $mailerLabel,
            'gatilho' => $gatilho,
            'preview_route' => $previewRoute,
            'preview_params' => $previewParams,
            'config_route' => $configRoute,
            'config_anchor' => $configAnchor,
        ];
    }
}
