<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Usuários externos vinculados a um cadastro de receituário (além de quem cadastrou).
     * Ex.: a secretária que cadastrou saiu e outra pessoa precisa acessar o cadastro do médico.
     * O vínculo é sempre com nível "gestor" (pode fazer requisições e acompanhar processos).
     */
    public function up(): void
    {
        Schema::create('receituario_usuario_externo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receituario_id')->constrained('receituarios')->cascadeOnDelete();
            $table->foreignId('usuario_externo_id')->constrained('usuarios_externos')->cascadeOnDelete();
            $table->string('tipo_vinculo', 20); // profissional | funcionario
            $table->string('nivel_acesso', 20)->default('gestor');
            $table->foreignId('vinculado_por_interno_id')->nullable()->constrained('usuarios_internos')->nullOnDelete();
            $table->foreignId('vinculado_por_externo_id')->nullable()->constrained('usuarios_externos')->nullOnDelete();
            $table->timestamps();

            $table->unique(['receituario_id', 'usuario_externo_id'], 'receituario_usuario_unique');
            $table->index('usuario_externo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receituario_usuario_externo');
    }
};
