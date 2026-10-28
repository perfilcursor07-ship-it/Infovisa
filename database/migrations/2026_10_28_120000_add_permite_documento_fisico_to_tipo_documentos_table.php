<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Marca quais tipos de documento podem ser lançados como documento FÍSICO a partir do
 * estabelecimento ("Criar Documento > Documento Físico").
 *
 * Antes isso vinha carona em "abrir_processo_automaticamente", que quer dizer outra coisa:
 * o tipo só aparecia na lista se também abrisse processo sozinho. Agora é uma opção própria.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipo_documentos', function (Blueprint $table) {
            $table->boolean('permite_documento_fisico')->default(false)->after('tipo_processo_codigo');
        });

        // Mantém o que já funcionava: os tipos que hoje aparecem na lista continuam aparecendo
        DB::table('tipo_documentos')
            ->where('abrir_processo_automaticamente', true)
            ->update(['permite_documento_fisico' => true]);
    }

    public function down(): void
    {
        Schema::table('tipo_documentos', function (Blueprint $table) {
            $table->dropColumn('permite_documento_fisico');
        });
    }
};
