<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Receituários solicitados pela área da empresa (usuário externo).
     *  - usuario_externo_id: quem fez a solicitação no InfoVISA
     *  - solicitante_proprio: (médico/dentista/veterinário) true = o próprio profissional solicitou com
     *    os dados do cadastro; false = solicitado em nome de outro profissional (secretária, funcionária...)
     */
    public function up(): void
    {
        Schema::table('receituarios', function (Blueprint $table) {
            $table->foreignId('usuario_externo_id')->nullable()->after('usuario_atualizacao_id')
                ->constrained('usuarios_externos')->nullOnDelete();
            $table->boolean('solicitante_proprio')->nullable()->after('usuario_externo_id');
            $table->index(['usuario_externo_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('receituarios', function (Blueprint $table) {
            $table->dropIndex(['usuario_externo_id', 'created_at']);
            $table->dropConstrainedForeignId('usuario_externo_id');
            $table->dropColumn('solicitante_proprio');
        });
    }
};
