<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Módulos que o usuário externo acessa (processos e/ou receituário).
     * Quem já tinha conta continua com acesso a tudo.
     */
    public function up(): void
    {
        Schema::table('usuarios_externos', function (Blueprint $table) {
            $table->json('modulos')->nullable()->after('vinculo_estabelecimento');
        });

        DB::table('usuarios_externos')->update(['modulos' => json_encode(['processos', 'receituario'])]);
    }

    public function down(): void
    {
        Schema::table('usuarios_externos', function (Blueprint $table) {
            $table->dropColumn('modulos');
        });
    }
};
