<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "rascunho": cadastro salvo no passo 4 (ficha baixada para assinar), ainda não enviado à Vigilância.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE receituarios DROP CONSTRAINT IF EXISTS receituarios_status_check');
        DB::statement("ALTER TABLE receituarios ADD CONSTRAINT receituarios_status_check CHECK (status::text = ANY (ARRAY['ativo', 'inativo', 'pendente', 'aguardando_assinatura', 'rejeitado', 'rascunho']::text[]))");
    }

    public function down(): void
    {
        DB::table('receituarios')->where('status', 'rascunho')->update(['status' => 'pendente']);
        DB::statement('ALTER TABLE receituarios DROP CONSTRAINT IF EXISTS receituarios_status_check');
        DB::statement("ALTER TABLE receituarios ADD CONSTRAINT receituarios_status_check CHECK (status::text = ANY (ARRAY['ativo', 'inativo', 'pendente', 'aguardando_assinatura', 'rejeitado']::text[]))");
    }
};
