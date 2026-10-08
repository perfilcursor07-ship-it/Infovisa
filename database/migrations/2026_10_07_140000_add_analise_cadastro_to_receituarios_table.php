<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fluxo do cadastro de receituário feito pela empresa:
     *   aguardando_assinatura → (empresa envia o documento assinado) → pendente
     *   pendente → (Vigilância) → ativo (aprovado) | rejeitado
     *   rejeitado → (empresa corrige e reenvia o documento assinado) → pendente
     *
     * Aprovado, o profissional ganha um cadastro interno (estabelecimento oculto de pessoa física)
     * onde ficam os processos de receituário.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE receituarios DROP CONSTRAINT IF EXISTS receituarios_status_check');
        DB::statement("ALTER TABLE receituarios ADD CONSTRAINT receituarios_status_check CHECK (status::text = ANY (ARRAY['ativo', 'inativo', 'pendente', 'aguardando_assinatura', 'rejeitado']::text[]))");

        Schema::table('receituarios', function (Blueprint $table) {
            if (!Schema::hasColumn('receituarios', 'documento_assinado_path')) {
                $table->string('documento_assinado_path')->nullable()->after('declaracao_endereco');
                $table->string('documento_assinado_nome')->nullable()->after('documento_assinado_path');
                $table->timestamp('documento_assinado_enviado_em')->nullable()->after('documento_assinado_nome');
            }
            if (!Schema::hasColumn('receituarios', 'analisado_por')) {
                $table->foreignId('analisado_por')->nullable()->after('documento_assinado_enviado_em')->constrained('usuarios_internos')->nullOnDelete();
                $table->timestamp('analisado_em')->nullable()->after('analisado_por');
                $table->text('motivo_rejeicao')->nullable()->after('analisado_em');
            }
            if (!Schema::hasColumn('receituarios', 'estabelecimento_id')) {
                $table->foreignId('estabelecimento_id')->nullable()->after('processo_id')->constrained('estabelecimentos')->nullOnDelete();
            }
        });

        // Cadastro interno do profissional (não aparece nas listas de estabelecimentos)
        if (!Schema::hasColumn('estabelecimentos', 'oculto_receituario')) {
            Schema::table('estabelecimentos', function (Blueprint $table) {
                $table->boolean('oculto_receituario')->default(false)->index()
                    ->comment('Cadastro interno criado para os processos de receituário de um profissional');
            });
        }

        // Tipo de processo que só pode ser aberto pela área de Receituários
        if (!Schema::hasColumn('tipo_processos', 'exclusivo_receituario')) {
            Schema::table('tipo_processos', function (Blueprint $table) {
                $table->boolean('exclusivo_receituario')->default(false)
                    ->comment('Só aparece na área de Receituários (não na abertura de processo do estabelecimento)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tipo_processos', 'exclusivo_receituario')) {
            Schema::table('tipo_processos', fn (Blueprint $table) => $table->dropColumn('exclusivo_receituario'));
        }
        if (Schema::hasColumn('estabelecimentos', 'oculto_receituario')) {
            Schema::table('estabelecimentos', fn (Blueprint $table) => $table->dropColumn('oculto_receituario'));
        }
        Schema::table('receituarios', function (Blueprint $table) {
            if (Schema::hasColumn('receituarios', 'estabelecimento_id')) {
                $table->dropConstrainedForeignId('estabelecimento_id');
            }
            if (Schema::hasColumn('receituarios', 'analisado_por')) {
                $table->dropConstrainedForeignId('analisado_por');
                $table->dropColumn(['analisado_em', 'motivo_rejeicao']);
            }
            if (Schema::hasColumn('receituarios', 'documento_assinado_path')) {
                $table->dropColumn(['documento_assinado_path', 'documento_assinado_nome', 'documento_assinado_enviado_em']);
            }
        });

        DB::table('receituarios')->whereIn('status', ['aguardando_assinatura', 'rejeitado'])->update(['status' => 'pendente']);
        DB::statement('ALTER TABLE receituarios DROP CONSTRAINT IF EXISTS receituarios_status_check');
        DB::statement("ALTER TABLE receituarios ADD CONSTRAINT receituarios_status_check CHECK (status::text = ANY (ARRAY['ativo', 'inativo', 'pendente']::text[]))");
    }
};
