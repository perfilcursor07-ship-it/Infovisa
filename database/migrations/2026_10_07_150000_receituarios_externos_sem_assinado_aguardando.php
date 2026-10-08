<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Solicitações da área da empresa criadas antes do envio do documento assinado existir:
     * sem o documento, ainda não podem estar "pendentes" de análise na Vigilância.
     */
    public function up(): void
    {
        DB::table('receituarios')
            ->whereNotNull('usuario_externo_id')
            ->where('status', 'pendente')
            ->whereNull('documento_assinado_path')
            ->update(['status' => 'aguardando_assinatura']);
    }

    public function down(): void
    {
        DB::table('receituarios')
            ->whereNotNull('usuario_externo_id')
            ->where('status', 'aguardando_assinatura')
            ->update(['status' => 'pendente']);
    }
};
