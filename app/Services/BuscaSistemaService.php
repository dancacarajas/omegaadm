<?php

namespace App\Services;

use App\Models\Beneficio;
use App\Models\Colaborador;
use App\Models\Rh\RhMovimentacaoChamado;
use App\Models\User;
use App\Models\Veiculo;
use Illuminate\Database\Eloquent\Builder;

final class BuscaSistemaService
{
    private const LIMITE_PADRAO = 8;

    /**
     * @return array{termo: string, grupos: list<array{titulo: string, icone: string, itens: list<array{titulo: string, subtitulo: string, url: string, badge: string|null}>}>}
     */
    public function buscar(User $user, string $termo, int $limite = self::LIMITE_PADRAO): array
    {
        $termo = trim($termo);

        if (mb_strlen($termo) < 2) {
            return ['termo' => $termo, 'grupos' => []];
        }

        $grupos = [];

        if ($user->temQualquerPermissaoNoModulo('rh') && $user->temAlgumaSecaoRh()) {
            $colaboradores = $this->buscarColaboradores($termo, $limite);
            if ($colaboradores !== []) {
                $grupos[] = [
                    'titulo' => 'Colaboradores',
                    'icone' => 'users',
                    'itens' => $colaboradores,
                ];
            }

            $beneficios = $this->buscarBeneficios($termo, $limite);
            if ($beneficios !== []) {
                $grupos[] = [
                    'titulo' => 'Benefícios',
                    'icone' => 'hand-heart',
                    'itens' => $beneficios,
                ];
            }

            if ($user->podeSecaoRh('chamados_movimentacao')) {
                $chamados = $this->buscarChamadosMovimentacao($termo, $limite);
                if ($chamados !== []) {
                    $grupos[] = [
                        'titulo' => 'Chamados de movimentação',
                        'icone' => 'git-branch',
                        'itens' => $chamados,
                    ];
                }
            }
        }

        if ($user->temQualquerPermissaoNoModulo('acessos')) {
            $usuarios = $this->buscarUsuarios($termo, $limite);
            if ($usuarios !== []) {
                $grupos[] = [
                    'titulo' => 'Usuários do sistema',
                    'icone' => 'user-cog',
                    'itens' => $usuarios,
                ];
            }
        }

        if ($user->temQualquerPermissaoNoModulo('veiculos')) {
            $veiculos = $this->buscarVeiculos($termo, $limite);
            if ($veiculos !== []) {
                $grupos[] = [
                    'titulo' => 'Veículos',
                    'icone' => 'car',
                    'itens' => $veiculos,
                ];
            }
        }

        return ['termo' => $termo, 'grupos' => $grupos];
    }

    /**
     * @return list<array{titulo: string, subtitulo: string, url: string, badge: string|null}>
     */
    private function buscarColaboradores(string $termo, int $limite): array
    {
        return Colaborador::query()
            ->where(fn (Builder $q) => $this->aplicarTermoColaborador($q, $termo))
            ->orderBy('nome')
            ->limit($limite)
            ->get(['id', 'nome', 'matricula', 'cargo', 'status'])
            ->map(fn (Colaborador $c) => [
                'titulo' => $c->nome,
                'subtitulo' => collect([
                    $c->matricula ? 'Mat. '.$c->matricula : null,
                    $c->cargo,
                ])->filter()->implode(' · '),
                'url' => route('rh.efetivo.show', $c),
                'badge' => $this->rotuloStatusColaborador($c->status),
            ])
            ->all();
    }

    /**
     * @return list<array{titulo: string, subtitulo: string, url: string, badge: string|null}>
     */
    private function buscarBeneficios(string $termo, int $limite): array
    {
        return Beneficio::query()
            ->where(fn (Builder $q) => $this->aplicarTermoBeneficio($q, $termo))
            ->orderBy('nome')
            ->limit($limite)
            ->get(['id', 'nome', 'tipo', 'fornecedor', 'status'])
            ->map(fn (Beneficio $b) => [
                'titulo' => $b->nome,
                'subtitulo' => collect([$b->tipo, $b->fornecedor])->filter()->implode(' · '),
                'url' => route('rh.beneficios.show', $b),
                'badge' => ucfirst((string) $b->status),
            ])
            ->all();
    }

    /**
     * @return list<array{titulo: string, subtitulo: string, url: string, badge: string|null}>
     */
    private function buscarChamadosMovimentacao(string $termo, int $limite): array
    {
        return RhMovimentacaoChamado::query()
            ->with('colaborador:id,nome,matricula')
            ->where(function (Builder $q) use ($termo) {
                $q->where(fn (Builder $inner) => $this->aplicarTermoColaborador($inner, $termo, 'colaborador'))
                    ->orWhere('motivo', 'like', "%{$termo}%");

                if (ctype_digit($termo)) {
                    $q->orWhere('id', (int) $termo);
                }
            })
            ->latest('id')
            ->limit($limite)
            ->get()
            ->map(function (RhMovimentacaoChamado $chamado) {
                $colab = $chamado->colaborador;

                return [
                    'titulo' => '#'.$chamado->id.' · '.($colab?->nome ?? 'Sem colaborador'),
                    'subtitulo' => collect([
                        $chamado->tipo,
                        $colab?->matricula ? 'Mat. '.$colab->matricula : null,
                    ])->filter()->implode(' · '),
                    'url' => route('rh.chamados-movimentacao.show', $chamado),
                    'badge' => str_replace('_', ' ', (string) $chamado->status),
                ];
            })
            ->all();
    }

    /**
     * @return list<array{titulo: string, subtitulo: string, url: string, badge: string|null}>
     */
    private function buscarUsuarios(string $termo, int $limite): array
    {
        return User::query()
            ->where(function (Builder $q) use ($termo) {
                $q->where('name', 'like', "%{$termo}%")
                    ->orWhere('email', 'like', "%{$termo}%");
            })
            ->orderBy('name')
            ->limit($limite)
            ->get(['id', 'name', 'email', 'status'])
            ->map(fn (User $u) => [
                'titulo' => $u->name,
                'subtitulo' => $u->email,
                'url' => route('usuarios.edit', $u),
                'badge' => ucfirst((string) $u->status),
            ])
            ->all();
    }

    /**
     * @return list<array{titulo: string, subtitulo: string, url: string, badge: string|null}>
     */
    private function buscarVeiculos(string $termo, int $limite): array
    {
        if (! class_exists(Veiculo::class)) {
            return [];
        }

        return Veiculo::query()
            ->where(function (Builder $q) use ($termo) {
                $q->where('placa', 'like', "%{$termo}%")
                    ->orWhere('modelo', 'like', "%{$termo}%")
                    ->orWhere('marca', 'like', "%{$termo}%")
                    ->orWhere('contrato', 'like', "%{$termo}%");
            })
            ->orderBy('placa')
            ->limit($limite)
            ->get(['id', 'placa', 'modelo', 'marca'])
            ->map(fn (Veiculo $v) => [
                'titulo' => $v->placa ?: ('Veículo #'.$v->id),
                'subtitulo' => collect([$v->marca, $v->modelo])->filter()->implode(' '),
                'url' => route('veiculos.edit', $v),
                'badge' => null,
            ])
            ->all();
    }

    private function aplicarTermoColaborador(Builder $query, string $termo, ?string $relation = null): void
    {
        $aplicar = function (Builder $q) use ($termo) {
            $q->where(function (Builder $inner) use ($termo) {
                $inner->where('nome', 'like', "%{$termo}%")
                    ->orWhere('matricula', 'like', "%{$termo}%")
                    ->orWhere('cargo', 'like', "%{$termo}%")
                    ->orWhere('cpf', 'like', "%{$termo}%")
                    ->orWhere('email', 'like', "%{$termo}%");
            });
        };

        if ($relation !== null) {
            $query->whereHas($relation, $aplicar);

            return;
        }

        $aplicar($query);
    }

    private function aplicarTermoBeneficio(Builder $query, string $termo): void
    {
        $query->where(function (Builder $q) use ($termo) {
            $q->where('nome', 'like', "%{$termo}%")
                ->orWhere('tipo', 'like', "%{$termo}%")
                ->orWhere('fornecedor', 'like', "%{$termo}%")
                ->orWhere('codigo', 'like', "%{$termo}%");
        });
    }

    private function rotuloStatusColaborador(?string $status): ?string
    {
        return match ($status) {
            'ativo' => 'Ativo',
            'desligado' => 'Desligado',
            'afastado' => 'Afastado',
            default => $status ? ucfirst($status) : null,
        };
    }
}
