<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Carteira do conselho de classe (CRM/CRO/CRMV) enviada pelo profissional — PDF ou foto.
     * Fica guardada no disco privado e faz parte do cadastro do receituário.
     */
    public function up(): void
    {
        Schema::table('receituarios', function (Blueprint $table) {
            $table->string('carteira_conselho_path')->nullable()->after('numero_crm');
            $table->string('carteira_conselho_nome')->nullable()->after('carteira_conselho_path');
            $table->json('carteira_conselho_leitura')->nullable()->after('carteira_conselho_nome');
        });
    }

    public function down(): void
    {
        Schema::table('receituarios', function (Blueprint $table) {
            $table->dropColumn(['carteira_conselho_path', 'carteira_conselho_nome', 'carteira_conselho_leitura']);
        });
    }
};
