<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Atividades reais (CNAEs) informadas por quem se cadastrou "por enquanto, apenas para
     * Projeto Arquitetônico/Análise de Rotulagem". Enquanto o Licenciamento não é aberto,
     * as atividades exercidas continuam sendo PROJ_ARQ/ANAL_ROT (aprovação pelo Estado);
     * ao abrir o Licenciamento, estas atividades passam a valer para a competência.
     */
    public function up(): void
    {
        if (Schema::hasColumn('estabelecimentos', 'atividades_declaradas')) {
            return;
        }

        Schema::table('estabelecimentos', function (Blueprint $table) {
            $table->json('atividades_declaradas')->nullable()->after('atividades_exercidas')
                ->comment('Atividades reais guardadas até a abertura do Licenciamento (cadastro só Projeto/Rotulagem)');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('estabelecimentos', 'atividades_declaradas')) {
            return;
        }

        Schema::table('estabelecimentos', function (Blueprint $table) {
            $table->dropColumn('atividades_declaradas');
        });
    }
};
