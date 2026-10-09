<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Com a RDC 1.000/2025 e o SNCR, a Vigilância distribui NUMERAÇÕES de Notificação de Receita
     * (física e eletrônica), não mais blocos. As requisições já feitas continuam contadas em blocos.
     */
    public function up(): void
    {
        Schema::table('receituario_requisicoes', function (Blueprint $table) {
            $table->string('unidade', 20)->default('numeracoes')->after('quantidades');
        });

        DB::table('receituario_requisicoes')->update(['unidade' => 'blocos']);
    }

    public function down(): void
    {
        Schema::table('receituario_requisicoes', function (Blueprint $table) {
            $table->dropColumn('unidade');
        });
    }
};
