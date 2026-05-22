<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\User;
use App\Support\Installation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PDO;
use Throwable;

class InstallController extends Controller
{
    public function index()
    {
        if (Installation::complete()) {
            return redirect()->route('login');
        }

        return view('install.index');
    }

    public function store(Request $request)
    {
        if (Installation::complete()) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:80'],
            'app_url' => ['required', 'url', 'max:255'],
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        try {
            $this->testDatabase($data);
            $this->writeEnvironment($data, installed: false);
            $this->refreshDatabaseConfig($data);

            Artisan::call('config:clear');
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('cache:clear');

            try {
                Artisan::call('storage:link');
            } catch (Throwable) {
                // Hospedagens compartilhadas podem bloquear symlink. O sistema continua instalado.
            }

            $this->createAdmin($data);
            $this->writeEnvironment($data, installed: true);
            Artisan::call('config:clear');

            return redirect()->route('login')->with('success', 'Sistema instalado com sucesso. Entre com o usuário administrador criado.');
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->except(['db_password', 'admin_password', 'admin_password_confirmation']))
                ->withErrors(['install' => 'Não foi possível concluir a instalação: '.$exception->getMessage()]);
        }
    }

    private function testDatabase(array $data): void
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $data['db_host'], $data['db_port'], $data['db_database']);
        new PDO($dsn, $data['db_username'], $data['db_password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    private function refreshDatabaseConfig(array $data): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $data['db_host'],
            'database.connections.mysql.port' => $data['db_port'],
            'database.connections.mysql.database' => $data['db_database'],
            'database.connections.mysql.username' => $data['db_username'],
            'database.connections.mysql.password' => $data['db_password'] ?? '',
            'session.driver' => 'database',
            'cache.default' => 'database',
            'queue.default' => 'database',
        ]);

        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    private function createAdmin(array $data): void
    {
        $perfil = Perfil::firstOrCreate(
            ['nome' => 'Administrador'],
            [
                'descricao' => 'Acesso completo ao sistema.',
                'ativo' => true,
                'permissoes' => $this->adminPermissoes(),
            ]
        );

        User::updateOrCreate(
            ['email' => $data['admin_email']],
            [
                'perfil_id' => $perfil->id,
                'todos_contratos' => true,
                'name' => $data['admin_name'],
                'cargo' => 'Administrador master',
                'status' => 'ativo',
                'password' => Hash::make($data['admin_password']),
                'email_verified_at' => now(),
            ]
        );
    }

    private function writeEnvironment(array $data, bool $installed): void
    {
        $key = $this->currentAppKey() ?: 'base64:'.base64_encode(random_bytes(32));
        $env = [
            'APP_NAME' => $this->quote($data['app_name']),
            'APP_ENV' => 'production',
            'APP_KEY' => $key,
            'APP_DEBUG' => 'false',
            'APP_URL' => $data['app_url'],
            'APP_INSTALLED' => $installed ? 'true' : 'false',
            'APP_LOCALE' => 'pt_BR',
            'APP_FALLBACK_LOCALE' => 'pt_BR',
            'APP_FAKER_LOCALE' => 'pt_BR',
            'APP_MAINTENANCE_DRIVER' => 'file',
            'BCRYPT_ROUNDS' => '12',
            'LOG_CHANNEL' => 'stack',
            'LOG_STACK' => 'single',
            'LOG_LEVEL' => 'error',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => (string) $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $this->quote($data['db_password'] ?? ''),
            'SESSION_DRIVER' => 'database',
            'SESSION_LIFETIME' => '120',
            'SESSION_ENCRYPT' => 'false',
            'SESSION_PATH' => '/',
            'SESSION_DOMAIN' => 'null',
            'BROADCAST_CONNECTION' => 'log',
            'FILESYSTEM_DISK' => 'public',
            'QUEUE_CONNECTION' => 'database',
            'CACHE_STORE' => 'database',
            'MAIL_MAILER' => 'log',
            'MAIL_FROM_ADDRESS' => 'noreply@omegaadm.feston.net.br',
            'MAIL_FROM_NAME' => $this->quote($data['app_name']),
            'VITE_APP_NAME' => $this->quote($data['app_name']),
        ];

        $content = collect($env)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode(PHP_EOL).PHP_EOL;

        file_put_contents(base_path('.env'), $content);
    }

    private function quote(string $value): string
    {
        if ($value === '' || Str::contains($value, [' ', '#', '"', "'"])) {
            return '"'.str_replace('"', '\"', $value).'"';
        }

        return $value;
    }

    private function currentAppKey(): ?string
    {
        $envPath = base_path('.env');

        if (! is_file($envPath)) {
            return null;
        }

        $content = file_get_contents($envPath);

        if (! preg_match('/^APP_KEY=(.+)$/m', $content, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    private function adminPermissoes(): array
    {
        $modules = ['dashboard', 'rh', 'veiculos', 'sesmt', 'contratos', 'patrimonial', 'medicao', 'rdo', 'acessos', 'configuracoes'];
        $permissoes = [];

        foreach ($modules as $module) {
            $permissoes[$module] = ['visualizar' => true, 'criar' => true, 'editar' => true, 'excluir' => true];
        }
        $permissoes['sesmt']['secoes'] = array_fill_keys(array_keys(User::sesmtSecoesDefinicao()), true);

        return $permissoes;
    }
}
