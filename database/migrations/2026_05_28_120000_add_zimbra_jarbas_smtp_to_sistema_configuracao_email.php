<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sistema_configuracao_email', function (Blueprint $table) {
            if (! Schema::hasColumn('sistema_configuracao_email', 'zimbra_host')) {
                $table->string('zimbra_host', 255)->nullable()->after('mail_from_address');
            }
            if (! Schema::hasColumn('sistema_configuracao_email', 'zimbra_port')) {
                $table->unsignedSmallInteger('zimbra_port')->default(587)->after('zimbra_host');
            }
            if (! Schema::hasColumn('sistema_configuracao_email', 'zimbra_encryption')) {
                $table->string('zimbra_encryption', 10)->nullable()->after('zimbra_port');
            }
            if (! Schema::hasColumn('sistema_configuracao_email', 'zimbra_username')) {
                $table->string('zimbra_username', 255)->nullable()->after('zimbra_encryption');
            }
            if (! Schema::hasColumn('sistema_configuracao_email', 'zimbra_password')) {
                $table->text('zimbra_password')->nullable()->after('zimbra_username');
            }
            if (! Schema::hasColumn('sistema_configuracao_email', 'zimbra_from_address')) {
                $table->string('zimbra_from_address', 255)->nullable()->after('zimbra_password');
            }
            if (! Schema::hasColumn('sistema_configuracao_email', 'zimbra_from_name')) {
                $table->string('zimbra_from_name', 120)->nullable()->after('zimbra_from_address');
            }
            if (! Schema::hasColumn('sistema_configuracao_email', 'beneficio_adesao_copia_email')) {
                $table->string('beneficio_adesao_copia_email', 255)->nullable()->after('zimbra_from_name');
            }
            if (! Schema::hasColumn('sistema_configuracao_email', 'zimbra_updated_at')) {
                $table->timestamp('zimbra_updated_at')->nullable()->after('beneficio_adesao_copia_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sistema_configuracao_email', function (Blueprint $table) {
            $cols = [
                'zimbra_host',
                'zimbra_port',
                'zimbra_encryption',
                'zimbra_username',
                'zimbra_password',
                'zimbra_from_address',
                'zimbra_from_name',
                'beneficio_adesao_copia_email',
                'zimbra_updated_at',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('sistema_configuracao_email', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
