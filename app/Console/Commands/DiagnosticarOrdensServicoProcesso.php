<?php

namespace App\Console\Commands;

use App\Models\DocumentoDigital;
use App\Models\OrdemServico;
use App\Models\Processo;
use App\Models\ProcessoDocumento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnosticarOrdensServicoProcesso extends Command
{
    protected $signature = 'os:diagnosticar {processo_id}';
    protected $description = 'Mostra por que uma Ordem de Serviço do estabelecimento não aparece dentro do processo';

    public function handle()
    {
        $processoId = (int) $this->argument('processo_id');
        $processo = Processo::with('estabelecimento')->find($processoId);

        if (!$processo) {
            $this->error("Processo ID {$processoId} não encontrado.");
            return 1;
        }

        $estabelecimentoId = $processo->estabelecimento_id;

        $this->info('=== PROCESSO ===');
        $this->line("ID: {$processo->id}");
        $this->line("Número: {$processo->numero_processo}");
        $this->line("Tipo: {$processo->tipo}");
        $this->line("Status: {$processo->status}");
        $this->line("Estabelecimento: {$estabelecimentoId} - " . ($processo->estabelecimento->nome_razao_social ?? '?'));
        $this->newLine();

        // O que a tela do processo encontra hoje
        $encontradas = OrdemServico::where('processo_id', $processoId)
            ->orWhereHas('estabelecimentos', function ($query) use ($processoId) {
                $query->where('ordem_servico_estabelecimentos.processo_id', $processoId);
            })
            ->get()
            ->unique('id');

        $this->info('=== OS QUE A TELA DO PROCESSO ENCONTRA HOJE ===');
        if ($encontradas->isEmpty()) {
            $this->warn('Nenhuma. É por isso que nada aparece no processo.');
        } else {
            foreach ($encontradas as $os) {
                $this->line("  OS {$os->id} ({$os->numero}) status={$os->status}");
            }
        }
        $this->newLine();

        // Todas as OS que tocam o estabelecimento, por qualquer caminho
        $idsLegado = OrdemServico::where('estabelecimento_id', $estabelecimentoId)->pluck('id');
        $idsPivot = DB::table('ordem_servico_estabelecimentos')
            ->where('estabelecimento_id', $estabelecimentoId)
            ->pluck('ordem_servico_id');

        $todas = OrdemServico::withTrashed()
            ->whereIn('id', $idsLegado->merge($idsPivot)->unique()->values())
            ->orderBy('id')
            ->get();

        $this->info('=== TODAS AS OS DO ESTABELECIMENTO ===');
        if ($todas->isEmpty()) {
            $this->warn('Nenhuma OS encontrada para este estabelecimento.');
        }

        $orfas = collect();

        foreach ($todas as $os) {
            $pivot = DB::table('ordem_servico_estabelecimentos')
                ->where('ordem_servico_id', $os->id)
                ->get();

            $pivotDesc = $pivot->isEmpty()
                ? 'sem linhas'
                : $pivot->map(fn ($r) => 'estab=' . $r->estabelecimento_id . '/proc=' . var_export($r->processo_id, true))->implode('; ');

            $visivel = $encontradas->contains('id', $os->id);
            $marca = $visivel ? 'APARECE' : 'NAO APARECE';
            $excluida = $os->trashed() ? ' [EXCLUIDA]' : '';

            $this->line("  OS {$os->id} ({$os->numero}) status={$os->status}{$excluida} => {$marca}");
            $this->line("      processo_id (legado): " . var_export($os->processo_id, true));
            $this->line("      pasta_id: " . var_export($os->pasta_id, true));
            $this->line("      pivot: {$pivotDesc}");

            if (!$visivel && !$os->trashed()) {
                $motivo = $this->explicarAusencia($os, $pivot, $processoId, $estabelecimentoId);
                $this->warn("      motivo: {$motivo}");

                $semVinculo = empty($os->processo_id)
                    && $pivot->every(fn ($r) => empty($r->processo_id));

                if ($semVinculo) {
                    $orfas->push($os);
                }
            }

            // Documentos vinculados a esta OS e em qual processo eles estao
            $digitais = DocumentoDigital::where('os_id', $os->id)->get();
            $arquivos = ProcessoDocumento::where('os_id', $os->id)->get();

            if ($digitais->isNotEmpty() || $arquivos->isNotEmpty()) {
                $this->line('      documentos da OS:');
                foreach ($digitais as $d) {
                    $ok = (int) $d->processo_id === $processoId ? 'neste processo' : 'FORA deste processo';
                    $this->line("        digital #{$d->id} {$d->numero_documento} processo_id=" . var_export($d->processo_id, true)
                        . ' processos_ids=' . json_encode($d->processos_ids) . " => {$ok}");
                }
                foreach ($arquivos as $a) {
                    $ok = (int) $a->processo_id === $processoId ? 'neste processo' : 'FORA deste processo';
                    $this->line("        arquivo #{$a->id} {$a->nome_original} processo_id=" . var_export($a->processo_id, true) . " => {$ok}");
                }
            }

            $this->newLine();
        }

        // Processos do estabelecimento, para entender o auto-vinculo na criacao da OS
        $this->info('=== PROCESSOS DO ESTABELECIMENTO ===');
        $statusAceitos = ['aberto', 'em_analise', 'pendente'];
        foreach (Processo::where('estabelecimento_id', $estabelecimentoId)->orderBy('id')->get() as $p) {
            $elegivel = in_array($p->status, $statusAceitos, true) ? 'serve para auto-vínculo' : 'NÃO serve para auto-vínculo';
            $atual = $p->id === $processoId ? ' <= este' : '';
            $this->line("  processo {$p->id} ({$p->numero_processo}) status={$p->status} - {$elegivel}{$atual}");
        }
        $this->newLine();

        if ($orfas->isNotEmpty()) {
            $this->warn('RESUMO: ' . $orfas->count() . ' OS órfã(s) deste estabelecimento sem vínculo de processo: '
                . $orfas->pluck('numero')->implode(', '));
            $this->line('Essas OS não aparecem em nenhum processo até receberem um processo_id.');
        } else {
            $this->info('RESUMO: nenhuma OS órfã neste estabelecimento.');
        }

        return 0;
    }

    private function explicarAusencia(OrdemServico $os, $pivot, int $processoId, int $estabelecimentoId): string
    {
        $semVinculo = empty($os->processo_id) && $pivot->every(fn ($r) => empty($r->processo_id));

        if ($semVinculo) {
            return 'OS sem vínculo de processo (processo_id nulo no legado e no pivot). '
                . 'Na criação não havia processo com status aberto/em_analise/pendente neste estabelecimento, '
                . 'então ficou órfã e não aparece em processo nenhum.';
        }

        $processosLigados = collect([$os->processo_id])
            ->merge($pivot->pluck('processo_id'))
            ->filter()
            ->unique()
            ->values();

        return 'OS vinculada a outro(s) processo(s): ' . $processosLigados->implode(', ')
            . ' - logo não aparece no processo ' . $processoId . '.';
    }
}
