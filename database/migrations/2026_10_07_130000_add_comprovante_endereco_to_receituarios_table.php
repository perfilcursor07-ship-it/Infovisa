<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receituarios', function (Blueprint $table) {
            $table->string('comprovante_endereco_path')->nullable()->after('carteira_conselho_leitura');
            $table->string('comprovante_endereco_nome')->nullable()->after('comprovante_endereco_path');
            $table->json('comprovante_endereco_leitura')->nullable()->after('comprovante_endereco_nome');
            // Aceite da declaração quando o comprovante não está no nome do profissional
            $table->json('declaracao_endereco')->nullable()->after('comprovante_endereco_leitura');
        });
    }

    public function down(): void
    {
        Schema::table('receituarios', function (Blueprint $table) {
            $table->dropColumn(['comprovante_endereco_path', 'comprovante_endereco_nome', 'comprovante_endereco_leitura', 'declaracao_endereco']);
        });
    }
};
