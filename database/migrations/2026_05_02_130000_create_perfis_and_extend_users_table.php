<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfis', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('descricao')->nullable();
            $table->json('permissoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('perfil_id')->nullable()->after('id')->constrained('perfis')->nullOnDelete();
            $table->string('telefone')->nullable()->after('email');
            $table->string('cargo')->nullable()->after('telefone');
            $table->string('status')->default('ativo')->after('password');
            $table->timestamp('ultimo_acesso_em')->nullable()->after('remember_token');
        });

        $now = now();
        $permissoesAdmin = [
            'dashboard' => ['visualizar' => true, 'criar' => true, 'editar' => true, 'excluir' => true],
            'rh' => ['visualizar' => true, 'criar' => true, 'editar' => true, 'excluir' => true],
            'veiculos' => ['visualizar' => true, 'criar' => true, 'editar' => true, 'excluir' => true],
            'sesmt' => ['visualizar' => true, 'criar' => true, 'editar' => true, 'excluir' => true],
            'contratos' => ['visualizar' => true, 'criar' => true, 'editar' => true, 'excluir' => true],
            'patrimonial' => ['visualizar' => true, 'criar' => true, 'editar' => true, 'excluir' => true],
            'rdo' => ['visualizar' => true, 'criar' => true, 'editar' => true, 'excluir' => true],
            'acessos' => ['visualizar' => true, 'criar' => true, 'editar' => true, 'excluir' => true],
        ];

        $adminId = DB::table('perfis')->insertGetId([
            'nome' => 'Administrador',
            'descricao' => 'Acesso completo ao sistema.',
            'permissoes' => json_encode($permissoesAdmin),
            'ativo' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('perfis')->insert([
            [
                'nome' => 'Operacional',
                'descricao' => 'Acesso para lançamento e acompanhamento operacional.',
                'permissoes' => json_encode([
                    'dashboard' => ['visualizar' => true],
                    'rh' => ['visualizar' => true],
                    'veiculos' => ['visualizar' => true, 'criar' => true, 'editar' => true],
                    'sesmt' => ['visualizar' => true, 'criar' => true, 'editar' => true],
                    'rdo' => ['visualizar' => true, 'criar' => true, 'editar' => true],
                ]),
                'ativo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nome' => 'Consulta',
                'descricao' => 'Acesso somente para visualização e relatórios.',
                'permissoes' => json_encode([
                    'dashboard' => ['visualizar' => true],
                    'rh' => ['visualizar' => true],
                    'veiculos' => ['visualizar' => true],
                    'sesmt' => ['visualizar' => true],
                    'contratos' => ['visualizar' => true],
                    'patrimonial' => ['visualizar' => true],
                    'rdo' => ['visualizar' => true],
                ]),
                'ativo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('users')->whereNull('perfil_id')->update(['perfil_id' => $adminId]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('perfil_id');
            $table->dropColumn(['telefone', 'cargo', 'status', 'ultimo_acesso_em']);
        });

        Schema::dropIfExists('perfis');
    }
};
