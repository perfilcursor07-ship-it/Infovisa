<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const NOVOS_EVENTOS = [
        'requisicao_receituario_liberada',
        'requisicao_receituario_indeferida',
    ];

    /**
     * Liberação da requisição pela Vigilância: para cada tipo/modalidade liberado, o documento de numeração
     * emitido no SNCR fica no processo (processo_documentos) e a requisição guarda qual arquivo é de qual tipo.
     */
    public function up(): void
    {
        Schema::table('receituario_requisicoes', function (Blueprint $table) {
            // {"fisica": {"A": 123}, "eletronica": {"B": 124}} → id em processo_documentos
            $table->json('documentos_liberacao')->nullable()->after('quantidades_liberadas');
        });

        $this->recriarConstraint(array_unique(array_merge($this->tiposAtuais(), self::NOVOS_EVENTOS)));
    }

    public function down(): void
    {
        Schema::table('receituario_requisicoes', function (Blueprint $table) {
            $table->dropColumn('documentos_liberacao');
        });

        DB::table('processo_eventos')->whereIn('tipo_evento', self::NOVOS_EVENTOS)->delete();
        $this->recriarConstraint(array_values(array_diff($this->tiposAtuais(), self::NOVOS_EVENTOS)));
    }

    private function tiposAtuais(): array
    {
        $definicao = DB::selectOne("
            SELECT pg_get_constraintdef(oid) AS def
            FROM pg_constraint
            WHERE conrelid = 'processo_eventos'::regclass
              AND conname = 'processo_eventos_tipo_evento_check'
        ")?->def ?? '';

        preg_match_all("/'([a-z_]+)'::text/", $definicao, $matches);

        return $matches[1];
    }

    private function recriarConstraint(array $tipos): void
    {
        if (empty($tipos)) {
            return;
        }

        $lista = implode(', ', array_map(fn ($tipo) => "'{$tipo}'::text", $tipos));

        DB::statement('ALTER TABLE processo_eventos DROP CONSTRAINT IF EXISTS processo_eventos_tipo_evento_check');
        DB::statement("
            ALTER TABLE processo_eventos
            ADD CONSTRAINT processo_eventos_tipo_evento_check
            CHECK (tipo_evento::text = ANY (ARRAY[{$lista}]))
        ");
    }
};
