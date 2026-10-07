<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registra a data do último reenvio dos arquivos anexados ao processo.
     *
     * Até aqui o reenvio de um arquivo rejeitado sobrescrevia a linha existente sem
     * guardar quando isso aconteceu, então o prazo de análise de 5 dias do licenciamento
     * continuava sendo contado a partir do primeiro envio (created_at). Com a coluna o
     * prazo passa a contar do reenvio e a data pode ser exibida na tela do processo.
     *
     * Não se aplica às respostas a documentos com prazo próprio (notificação, auto de
     * infração): nelas o prazo de análise segue contando do primeiro envio.
     */
    public function up(): void
    {
        Schema::table('processo_documentos', function (Blueprint $table) {
            $table->timestamp('reenviado_em')->nullable()->after('tentativas_envio');
        });

        $this->preencherReenvios('processo_documentos', 'status_aprovacao');
    }

    public function down(): void
    {
        Schema::table('processo_documentos', function (Blueprint $table) {
            $table->dropColumn('reenviado_em');
        });
    }

    /**
     * Backfill dos registros que já foram reenviados e seguem pendentes de análise.
     *
     * A data exata do reenvio não existe no histórico, mas para um registro que voltou
     * para "pendente" o reenvio foi a última escrita na linha, então updated_at é a melhor
     * aproximação disponível. Só aproveitamos quando updated_at é posterior à última
     * rejeição registrada, o que confirma que houve uma escrita depois dela.
     *
     * Registros já aprovados ou rejeitados ficam com reenviado_em nulo: neles updated_at
     * aponta para a avaliação, não para o reenvio, e o prazo de análise já não corre.
     */
    private function preencherReenvios(string $tabela, string $colunaStatus): void
    {
        DB::table($tabela)
            ->whereNotNull('historico_rejeicao')
            ->where($colunaStatus, 'pendente')
            ->orderBy('id')
            ->chunkById(500, function ($registros) use ($tabela) {
                foreach ($registros as $registro) {
                    $historico = json_decode($registro->historico_rejeicao ?? '[]', true);

                    if (!is_array($historico) || $historico === []) {
                        continue;
                    }

                    $ultimaRejeicao = null;
                    foreach ($historico as $entrada) {
                        if (empty($entrada['rejeitado_em'])) {
                            continue;
                        }

                        try {
                            $data = Carbon::parse($entrada['rejeitado_em']);
                        } catch (\Exception $e) {
                            continue;
                        }

                        if ($ultimaRejeicao === null || $data->greaterThan($ultimaRejeicao)) {
                            $ultimaRejeicao = $data;
                        }
                    }

                    if (!$ultimaRejeicao || !$registro->updated_at) {
                        continue;
                    }

                    $atualizadoEm = Carbon::parse($registro->updated_at);

                    if ($atualizadoEm->greaterThan($ultimaRejeicao)) {
                        DB::table($tabela)->where('id', $registro->id)->update([
                            'reenviado_em' => $atualizadoEm,
                        ]);
                    }
                }
            });
    }
};
