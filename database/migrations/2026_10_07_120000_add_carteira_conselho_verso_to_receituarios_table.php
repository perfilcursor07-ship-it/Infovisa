<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receituarios', function (Blueprint $table) {
            $table->string('carteira_conselho_verso_path')->nullable()->after('carteira_conselho_nome');
            $table->string('carteira_conselho_verso_nome')->nullable()->after('carteira_conselho_verso_path');
        });
    }

    public function down(): void
    {
        Schema::table('receituarios', function (Blueprint $table) {
            $table->dropColumn(['carteira_conselho_verso_path', 'carteira_conselho_verso_nome']);
        });
    }
};
