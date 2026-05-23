<?php

namespace App\Models;

use App\Support\Rh\CafeDaManhaRegraConfig;
use App\Support\Rh\ValeAlimentacaoRegraConfig;
use App\Support\Rh\WebcardRegraConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficioExtratoRegra extends Model
{
    public const TIPO_ASSIDUIDADE = 'assiduidade';

    public const TIPO_VALOR_FIXO = 'valor_fixo';

    public const TIPO_CAFE_MANHA = 'cafe_manha';

    public const TIPO_WEBCARD = 'webcard';

    public const TIPOS = [
        self::TIPO_ASSIDUIDADE,
        self::TIPO_CAFE_MANHA,
        self::TIPO_WEBCARD,
        self::TIPO_VALOR_FIXO,
    ];

    protected $fillable = [
        'beneficio_id',
        'tipo_regra',
        'ano_vigencia',
        'parametros',
        'configurado',
        'ativo',
    ];

    protected $casts = [
        'beneficio_id' => 'integer',
        'ano_vigencia' => 'integer',
        'parametros' => 'array',
        'configurado' => 'boolean',
        'ativo' => 'boolean',
    ];

    public function configValeAlimentacao(): ValeAlimentacaoRegraConfig
    {
        return ValeAlimentacaoRegraConfig::resolver(
            $this->parametros,
            $this->ano_vigencia
        );
    }

    public function configCafeDaManha(): CafeDaManhaRegraConfig
    {
        return CafeDaManhaRegraConfig::resolver(
            $this->parametros,
            $this->ano_vigencia
        );
    }

    public function configWebcard(): WebcardRegraConfig
    {
        return WebcardRegraConfig::resolver(
            $this->parametros,
            $this->ano_vigencia
        );
    }

    /**
     * Sugestão automática ao incluir o benefício no extrato (não é definitivo — o tipo salvo na regra manda).
     */
    public static function inferirTipoRegra(Beneficio $beneficio): string
    {
        if (self::pareceWebcard($beneficio)) {
            return self::TIPO_WEBCARD;
        }

        if (self::pareceValeAlimentacao($beneficio)) {
            return self::TIPO_ASSIDUIDADE;
        }

        if (self::pareceCafeDaManha($beneficio)) {
            return self::TIPO_CAFE_MANHA;
        }

        return self::TIPO_VALOR_FIXO;
    }

    public static function pareceWebcard(Beneficio $beneficio): bool
    {
        $nome = mb_strtolower((string) $beneficio->nome);
        $tipo = mb_strtolower((string) ($beneficio->tipo ?? ''));
        $codigo = mb_strtolower((string) ($beneficio->codigo ?? ''));

        if (in_array($codigo, ['webcard', 'web-card', 'wc'], true)) {
            return true;
        }

        if (str_contains($tipo, 'webcard') || str_contains($tipo, 'adiantamento')) {
            return true;
        }

        return str_contains($nome, 'webcard') || str_contains($nome, 'web card');
    }

    public static function pareceCafeDaManha(Beneficio $beneficio): bool
    {
        if (self::pareceValeAlimentacao($beneficio) || self::pareceWebcard($beneficio)) {
            return false;
        }

        $nome = mb_strtolower((string) $beneficio->nome);
        $tipo = mb_strtolower((string) ($beneficio->tipo ?? ''));
        $codigo = mb_strtolower((string) ($beneficio->codigo ?? ''));

        if (in_array($codigo, ['cafe-manha', 'cafe_manha', 'cafe'], true)) {
            return true;
        }

        if (str_contains($tipo, 'cafe') || str_contains($tipo, 'café')) {
            return true;
        }

        return (str_contains($nome, 'cafe') || str_contains($nome, 'café'))
            && (str_contains($nome, 'manha') || str_contains($nome, 'manhã') || str_contains($nome, 'breakfast'));
    }

    /**
     * Vale/auxílio alimentação: nome/tipo/código, sem confundir com "Vale Transporte" etc.
     */
    public static function pareceValeAlimentacao(Beneficio $beneficio): bool
    {
        $nome = mb_strtolower((string) $beneficio->nome);
        $tipo = mb_strtolower((string) ($beneficio->tipo ?? ''));
        $codigo = mb_strtolower((string) ($beneficio->codigo ?? ''));

        if (in_array($codigo, ['alelo001', 'vale-alimentacao', 'va', 'vale-alimentacao'], true)) {
            return true;
        }

        if (str_contains($tipo, 'aliment')) {
            return true;
        }

        if (str_contains($nome, 'aliment') || str_contains($nome, 'refei')) {
            return true;
        }

        // "Vale X" só conta se X for alimentação/refeição (evita Vale Transporte, Vale Combustível…)
        if (str_contains($nome, 'vale') && (str_contains($nome, 'aliment') || str_contains($nome, 'refei'))) {
            return true;
        }

        return false;
    }

    public function beneficio(): BelongsTo
    {
        return $this->belongsTo(Beneficio::class);
    }

    public static function rotuloTipo(string $tipo): string
    {
        return match ($tipo) {
            self::TIPO_ASSIDUIDADE => 'Assiduidade + proporcional (vale alimentação)',
            self::TIPO_CAFE_MANHA => 'Café da manhã (dias trabalhados na apuração)',
            self::TIPO_WEBCARD => 'WebCard (direito — % do salário, teto mensal)',
            self::TIPO_VALOR_FIXO => 'Valor fixo do cadastro',
            default => $tipo,
        };
    }
}
