<?php

use App\Models\Receituario;
use App\Services\ReceituarioCadastroService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * A requisição assinada deixou de fazer parte do CADASTRO do profissional (ela pertence ao processo
     * de receituário). Recalcula a situação dos cadastros da empresa só com carteira + comprovante:
     * aguardando_assinatura → em análise; rejeitado só pela requisição → em análise ou aprovado.
     */
    public function up(): void
    {
        $servico = app(ReceituarioCadastroService::class);

        Receituario::query()
            ->whereNotNull('usuario_externo_id')
            ->whereIn('status', ['aguardando_assinatura', 'pendente', 'rejeitado'])
            ->get()
            ->each(function (Receituario $receituario) use ($servico) {
                $receituario->recalcularStatus($receituario->analisado_por);
                if ($receituario->isAprovado()) {
                    $servico->garantirEstabelecimento($receituario);
                }
            });
    }

    public function down(): void
    {
        // Sem volta automática: a situação passa a depender só dos documentos do cadastro.
    }
};
