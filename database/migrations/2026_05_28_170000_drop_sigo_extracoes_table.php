<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('sigo_extracoes');
    }

    public function down(): void
    {
        // Funcionalidade removida — não recriar tabela.
    }
};
