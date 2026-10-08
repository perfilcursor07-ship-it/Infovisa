<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Requisições de notificação/numeração de receituário feitas pela empresa dentro do
     * processo de receituário. Cada pedido é individual e passa pela liberação da Vigilância.
     */
    public function up(): void
    {
        Schema::create('receituario_requisicoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos')->cascadeOnDelete();
            $table->foreignId('receituario_id')->nullable()->constrained('receituarios')->nullOnDelete();
            $table->integer('ano');
            $table->integer('numero_sequencial');
            $table->string('numero', 20)->unique(); // 2026/0001

            // Quantidades solicitadas: {"fisica": {"A": 2, "B": 5, ...}, "eletronica": {...}}
            $table->json('quantidades');
            $table->text('justificativa')->nullable();

            // Dados do requisitante no momento do pedido (o cadastro pode mudar depois)
            $table->json('requisitante');

            // Declarações aceitas e assinatura eletrônica do usuário externo
            $table->json('declaracoes');
            $table->foreignId('usuario_externo_id')->nullable()->constrained('usuarios_externos')->nullOnDelete();
            $table->timestamp('assinado_em')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            // enviada → em_analise → liberada | indeferida ; cancelada (pela empresa, antes da análise)
            $table->string('status', 20)->default('enviada');
            $table->json('quantidades_liberadas')->nullable();
            $table->text('observacao_vigilancia')->nullable();
            $table->foreignId('analisado_por')->nullable()->constrained('usuarios_internos')->nullOnDelete();
            $table->timestamp('analisado_em')->nullable();
            $table->timestamp('cancelado_em')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['processo_id', 'status']);
            $table->unique(['ano', 'numero_sequencial']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receituario_requisicoes');
    }
};
