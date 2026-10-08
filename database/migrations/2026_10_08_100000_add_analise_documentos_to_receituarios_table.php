<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Análise de cada documento do cadastro de receituário (como os documentos de um processo):
     * { "carteira": {status, motivo, analisado_por, analisado_em, historico: [...]}, "comprovante": {...}, "assinado": {...} }
     * status: pendente | aprovado | rejeitado. Todos aprovados → cadastro aprovado.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('receituarios', 'analise_documentos')) {
            Schema::table('receituarios', function (Blueprint $table) {
                $table->json('analise_documentos')->nullable()->after('motivo_rejeicao');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('receituarios', 'analise_documentos')) {
            Schema::table('receituarios', fn (Blueprint $table) => $table->dropColumn('analise_documentos'));
        }
    }
};
