<?php

namespace App\Support\Rh;

final class BeneficioAdesaoStatus
{
    public const ADESAO_AUTOMATICA = 'adesao_automatica';

    public const PENDENTE_FORMULARIO = 'pendente_formulario';

    public const FORMULARIO_RECEBIDO = 'formulario_recebido';

    public const ENVIADO_MATRIZ = 'enviado_matriz';

    public const AGUARDANDO_CARTAO = 'aguardando_cartao';

    /** Matriz avisou que o cartão está na unidade para coleta (RH retira e entrega ao colaborador). */
    public const CARTAO_DISPONIVEL_COLETA = 'cartao_disponivel_coleta';

    public const CARTAO_ENTREGUE = 'cartao_entregue';

    public const BENEFICIO_ATIVO = 'beneficio_ativo';

    public const CANCELADO = 'cancelado';

    /** @return array<string, string> */
    public static function rotulos(): array
    {
        return [
            self::ADESAO_AUTOMATICA => 'Adesão automática (admissão)',
            self::PENDENTE_FORMULARIO => 'Aguardando formulário do colaborador',
            self::FORMULARIO_RECEBIDO => 'Formulário recebido — enviar à Matriz',
            self::ENVIADO_MATRIZ => 'Pedido enviado à Matriz',
            self::AGUARDANDO_CARTAO => 'Aguardando aviso da Matriz',
            self::CARTAO_DISPONIVEL_COLETA => 'Cartão na Matriz — disponível para coleta',
            self::CARTAO_ENTREGUE => 'Cartão entregue ao colaborador',
            self::BENEFICIO_ATIVO => 'Benefício ativo',
            self::CANCELADO => 'Cancelado / sem adesão',
        ];
    }

    public static function rotulo(string $status): string
    {
        return self::rotulos()[$status] ?? $status;
    }

    /** Rótulo curto para listagens densas (tabela / accordion). */
    public static function rotuloCurto(string $status): string
    {
        return match ($status) {
            self::PENDENTE_FORMULARIO => 'Formulário pendente',
            self::FORMULARIO_RECEBIDO => 'Enviar à Matriz',
            self::ENVIADO_MATRIZ => 'Pedido Matriz',
            self::AGUARDANDO_CARTAO => 'Aguard. aviso',
            self::CARTAO_DISPONIVEL_COLETA => 'Coleta na Matriz',
            self::CARTAO_ENTREGUE => 'Entregue',
            self::BENEFICIO_ATIVO => 'Benefício ativo',
            self::ADESAO_AUTOMATICA => 'Automática',
            self::CANCELADO => 'Cancelado',
            default => self::rotulo($status),
        };
    }

    /** Ponto de cor do badge (classe Tailwind no indicador). */
    public static function corIndicador(string $status): string
    {
        return match ($status) {
            self::PENDENTE_FORMULARIO => 'bg-amber-500',
            self::FORMULARIO_RECEBIDO => 'bg-sky-500',
            self::ENVIADO_MATRIZ => 'bg-violet-500',
            self::AGUARDANDO_CARTAO => 'bg-orange-500',
            self::CARTAO_DISPONIVEL_COLETA => 'bg-teal-500',
            self::CARTAO_ENTREGUE, self::BENEFICIO_ATIVO => 'bg-emerald-500',
            self::CANCELADO => 'bg-zinc-400',
            default => 'bg-zinc-400',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_keys(self::rotulos());
    }

    /** @return list<string> */
    public static function emAndamento(): array
    {
        return [
            self::PENDENTE_FORMULARIO,
            self::FORMULARIO_RECEBIDO,
            self::ENVIADO_MATRIZ,
            self::AGUARDANDO_CARTAO,
            self::CARTAO_DISPONIVEL_COLETA,
        ];
    }

    public static function corBadge(string $status): string
    {
        return match ($status) {
            self::PENDENTE_FORMULARIO => 'bg-amber-50 text-amber-900 ring-amber-200',
            self::FORMULARIO_RECEBIDO => 'bg-sky-50 text-sky-900 ring-sky-200',
            self::ENVIADO_MATRIZ => 'bg-violet-50 text-violet-900 ring-violet-200',
            self::AGUARDANDO_CARTAO => 'bg-orange-50 text-orange-900 ring-orange-200',
            self::CARTAO_DISPONIVEL_COLETA => 'bg-teal-50 text-teal-900 ring-teal-200',
            self::CARTAO_ENTREGUE => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            self::BENEFICIO_ATIVO => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            self::ADESAO_AUTOMATICA => 'bg-zinc-100 text-zinc-600 ring-zinc-200',
            self::CANCELADO => 'bg-zinc-100 text-zinc-600 ring-zinc-200',
            default => 'bg-zinc-100 text-brand-gray ring-zinc-200',
        };
    }
}
