<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Em quais tipos de processo o tipo de documento aparece na criação de documentos:
     *  - todos       → em qualquer processo (padrão, comportamento anterior)
     *  - especificos → somente nos tipos listados em tipos_processo_permitidos (códigos)
     *  - nenhum      → não aparece dentro de processos (só na criação fora de processo)
     */
    public function up(): void
    {
        Schema::table('tipo_documentos', function (Blueprint $table) {
            if (!Schema::hasColumn('tipo_documentos', 'escopo_processos')) {
                $table->string('escopo_processos', 20)->default('todos');
            }
            if (!Schema::hasColumn('tipo_documentos', 'tipos_processo_permitidos')) {
                $table->json('tipos_processo_permitidos')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tipo_documentos', function (Blueprint $table) {
            foreach (['escopo_processos', 'tipos_processo_permitidos'] as $coluna) {
                if (Schema::hasColumn('tipo_documentos', $coluna)) {
                    $table->dropColumn($coluna);
                }
            }
        });
    }
};
