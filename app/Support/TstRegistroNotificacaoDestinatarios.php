<?php

namespace App\Support;

use App\Models\Colaborador;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class TstRegistroNotificacaoDestinatarios
{
    public const TIPO_COLABORADOR = 'colaborador';

    public const TIPO_USUARIO = 'usuario';

    /**
     * @param  mixed  $raw
     * @return list<array{tipo: string, id: int}>
     */
    public static function normalizar(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $itens = [];

        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }

            $tipo = $item['tipo'] ?? null;
            $id = isset($item['id']) ? (int) $item['id'] : 0;

            if (! in_array($tipo, [self::TIPO_COLABORADOR, self::TIPO_USUARIO], true) || $id < 1) {
                continue;
            }

            $key = self::chave($tipo, $id);
            $itens[$key] = ['tipo' => $tipo, 'id' => $id];
        }

        return array_values($itens);
    }

    /**
     * @param  list<array{tipo: string, id: int}>  $itens
     * @return list<string>
     */
    public static function emailsParaEnvio(array $itens): array
    {
        $emails = [];

        foreach (self::resolverParaExibicao($itens) as $capsula) {
            $email = $capsula['email'] ?? '';
            if ($email !== '') {
                $emails[$email] = $email;
            }
        }

        return array_values($emails);
    }

    /**
     * @param  list<array{tipo: string, id: int}>  $itens
     * @return list<array{key: string, tipo: string, id: int, nome: string, email: string, tipo_label: string}>
     */
    public static function resolverParaExibicao(array $itens): array
    {
        $itens = self::normalizar($itens);
        if ($itens === []) {
            return [];
        }

        $colabIds = [];
        $userIds = [];

        foreach ($itens as $item) {
            if ($item['tipo'] === self::TIPO_COLABORADOR) {
                $colabIds[] = $item['id'];
            } else {
                $userIds[] = $item['id'];
            }
        }

        $colaboradores = $colabIds !== []
            ? Colaborador::query()->whereIn('id', array_unique($colabIds))->get(['id', 'nome', 'email', 'matricula'])->keyBy('id')
            : collect();

        $usuarios = $userIds !== []
            ? User::query()->whereIn('id', array_unique($userIds))->get(['id', 'name', 'email'])->keyBy('id')
            : collect();

        $capsulas = [];

        foreach ($itens as $item) {
            if ($item['tipo'] === self::TIPO_COLABORADOR) {
                $colab = $colaboradores->get($item['id']);
                if ($colab === null || ! self::emailValido($colab->email)) {
                    continue;
                }

                $capsulas[] = [
                    'key' => self::chave($item['tipo'], $item['id']),
                    'tipo' => $item['tipo'],
                    'id' => $item['id'],
                    'nome' => $colab->nome,
                    'email' => strtolower(trim((string) $colab->email)),
                    'tipo_label' => 'Colaborador',
                ];

                continue;
            }

            $usuario = $usuarios->get($item['id']);
            if ($usuario === null || ! self::emailValido($usuario->email)) {
                continue;
            }

            $capsulas[] = [
                'key' => self::chave($item['tipo'], $item['id']),
                'tipo' => $item['tipo'],
                'id' => $item['id'],
                'nome' => $usuario->name,
                'email' => strtolower(trim((string) $usuario->email)),
                'tipo_label' => 'Usuário',
            ];
        }

        return $capsulas;
    }

    /**
     * @return list<array{id: int, nome: string, email: string, matricula: string|null}>
     */
    public static function opcoesColaboradores(): array
    {
        return Colaborador::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('nome')
            ->get(['id', 'nome', 'email', 'matricula'])
            ->filter(fn (Colaborador $c) => self::emailValido($c->email))
            ->map(fn (Colaborador $c) => [
                'id' => $c->id,
                'nome' => $c->nome,
                'email' => strtolower(trim((string) $c->email)),
                'matricula' => $c->matricula,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, nome: string, email: string}>
     */
    public static function opcoesUsuarios(): array
    {
        return User::query()
            ->where('status', 'ativo')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->filter(fn (User $u) => self::emailValido($u->email))
            ->map(fn (User $u) => [
                'id' => $u->id,
                'nome' => $u->name,
                'email' => strtolower(trim((string) $u->email)),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{tipo?: string, id?: int}>  $payload
     * @return list<array{tipo: string, id: int}>
     */
    public static function validarPayload(array $payload): array
    {
        $normalizado = self::normalizar($payload);

        foreach ($normalizado as $item) {
            if ($item['tipo'] === self::TIPO_COLABORADOR) {
                $exists = Colaborador::query()
                    ->whereKey($item['id'])
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->exists();
            } else {
                $exists = User::query()
                    ->whereKey($item['id'])
                    ->where('status', 'ativo')
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->exists();
            }

            if (! $exists) {
                throw ValidationException::withMessages([
                    'destinatarios' => 'Um ou mais destinatários são inválidos ou estão sem e-mail cadastrado.',
                ]);
            }
        }

        return $normalizado;
    }

    public static function chave(string $tipo, int $id): string
    {
        return $tipo.':'.$id;
    }

    private static function emailValido(?string $email): bool
    {
        return filled($email) && filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
    }
}
