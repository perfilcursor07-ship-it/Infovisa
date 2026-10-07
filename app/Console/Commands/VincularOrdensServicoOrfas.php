<?php

namespace App\Console\Commands;

use App\Models\DocumentoDigital;
use App\Models\OrdemServico;
use App\Models\Processo;
use App\Models\ProcessoDocumento;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VincularOrdensServicoOrfas extends Command
{
    protected $signature = 'os:vincular-orfas
                            {--dry-run : Apenas mostra o que seria alterado, sem gravar}
                            {--force : Aplica sem pedir confirmação}
                            {--os= : Limita a uma Ordem de Serviço (id ou número)}
                            {--estabelecimento= : Limita a um estabelecimento}';

    protected $description = 'Vincula ao processo do estabelecimento as Ordens de Serviço órfãs (sem processo) e os documentos gerados nelas';

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        $orfas = $this->buscarOrfas();

        if ($orfas->isEmpty()) {
            $this->info('Nenhuma Ordem de Serviço órfã encontrada.');
            return 0;
        }

        $this->info(($dryRun ? '[DRY-RUN] ' : '') . "Ordens de Serviço órfãs encontradas: {$orfas->count()}");
        $this->newLine();

        $aplicaveis = [];
        $semProcesso = [];
        $ambiguas = [];

        foreach ($orfas as $os) {
            $estabelecimentos = $this->estabelecimentosDaOs($os);

            if ($estabelecimentos->isEmpty()) {
                $semProcesso[] = [$os, 'OS sem estabelecimento vinculado'];
                continue;
            }

            // Processo de destino de cada estabelecimento da OS
            $destinos = [];
            foreach ($estabelecimentos as $estabelecimentoId) {
                $processo = $this->processoMaisRecente($estabelecimentoId);

                if (!$processo) {
                    $semProcesso[] = [$os, "estabelecimento {$estabelecimentoId} não possui processo"];
                    continue 2;
                }

                $destinos[$estabelecimentoId] = $processo;
            }

            $aplicaveis[] = [$os, $destinos];

            if (count($destinos) > 1) {
                $ambiguas[] = $os;
            }
        }

        // ------------------------------------------------------------- relatório
        foreach ($aplicaveis as [$os, $destinos]) {
            $this->line("OS {$os->id} ({$os->numero}) status={$os->status}");

            foreach ($destinos as $estabelecimentoId => $processo) {
                $this->line("    estabelecimento {$estabelecimentoId} => processo {$processo->id} "
                    . "({$processo->numero_processo}, {$processo->status})");
            }

            $docs = $this->documentosOrfaos($os);
            $digitais = $docs['digitais'];
            $arquivos = $docs['arquivos'];

            if (count($destinos) === 1) {
                $processo = reset($destinos);

                foreach ($digitais as $d) {
                    $this->line("    documento digital #{$d->id} ({$d->numero_documento}) => processo {$processo->id}");
                }
                foreach ($arquivos as $a) {
                    $this->line("    arquivo #{$a->id} ({$a->nome_original}) => processo {$processo->id}");
                }
            } elseif ($digitais->isNotEmpty() || $arquivos->isNotEmpty()) {
                $this->warn('    OS com vários estabelecimentos: os ' . ($digitais->count() + $arquivos->count())
                    . ' documento(s) NÃO serão movidos automaticamente (destino ambíguo).');
            }

            $this->newLine();
        }

        foreach ($semProcesso as [$os, $motivo]) {
            $this->warn("OS {$os->id} ({$os->numero}) ignorada: {$motivo}");
        }

        if ($ambiguas !== []) {
            $this->newLine();
            $this->warn('OS com mais de um estabelecimento (só o vínculo da OS é ajustado, documentos ficam de fora): '
                . collect($ambiguas)->pluck('numero')->implode(', '));
        }

        if ($aplicaveis === []) {
            $this->newLine();
            $this->info('Nada a aplicar.');
            return 0;
        }

        $this->newLine();

        if ($dryRun) {
            $this->info('[DRY-RUN] Nada foi gravado. Rode sem --dry-run para aplicar.');
            return 0;
        }

        if (!$this->option('force') && !$this->confirm('Aplicar os vínculos acima?', false)) {
            $this->info('Cancelado. Nada foi gravado. Use --force para aplicar sem confirmação.');
            return 0;
        }

        $osAtualizadas = 0;
        $docsAtualizados = 0;

        DB::transaction(function () use ($aplicaveis, &$osAtualizadas, &$docsAtualizados) {
            foreach ($aplicaveis as [$os, $destinos]) {
                // processo_id legado = processo do primeiro estabelecimento
                $primeiro = reset($destinos);
                $os->processo_id = $primeiro->id;
                $os->save();

                // pivot: cada estabelecimento com o processo dele
                foreach ($destinos as $estabelecimentoId => $processo) {
                    DB::table('ordem_servico_estabelecimentos')
                        ->where('ordem_servico_id', $os->id)
                        ->where('estabelecimento_id', $estabelecimentoId)
                        ->update(['processo_id' => $processo->id]);
                }

                $osAtualizadas++;

                // documentos só quando o destino é único
                if (count($destinos) !== 1) {
                    continue;
                }

                $docs = $this->documentosOrfaos($os);

                foreach ($docs['digitais'] as $d) {
                    $d->processo_id = $primeiro->id;
                    $d->save();
                    $docsAtualizados++;
                }

                foreach ($docs['arquivos'] as $a) {
                    $a->processo_id = $primeiro->id;
                    $a->save();
                    $docsAtualizados++;
                }
            }
        });

        $this->newLine();
        $this->info("Concluído: {$osAtualizadas} OS vinculada(s), {$docsAtualizados} documento(s) ajustado(s).");

        return 0;
    }

    /**
     * OS sem processo no campo legado e sem processo em nenhuma linha do pivot.
     */
    private function buscarOrfas(): Collection
    {
        $query = OrdemServico::query()
            ->whereNull('processo_id')
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('ordem_servico_estabelecimentos')
                    ->whereColumn('ordem_servico_estabelecimentos.ordem_servico_id', 'ordens_servico.id')
                    ->whereNotNull('ordem_servico_estabelecimentos.processo_id');
            });

        if ($osFiltro = $this->option('os')) {
            $query->where(function ($q) use ($osFiltro) {
                $q->where('numero', $osFiltro);

                if (ctype_digit((string) $osFiltro)) {
                    $q->orWhere('id', (int) $osFiltro);
                }
            });
        }

        if ($estabFiltro = $this->option('estabelecimento')) {
            $estabFiltro = (int) $estabFiltro;

            $query->where(function ($q) use ($estabFiltro) {
                $q->where('estabelecimento_id', $estabFiltro)
                    ->orWhereExists(function ($sub) use ($estabFiltro) {
                        $sub->select(DB::raw(1))
                            ->from('ordem_servico_estabelecimentos')
                            ->whereColumn('ordem_servico_estabelecimentos.ordem_servico_id', 'ordens_servico.id')
                            ->where('ordem_servico_estabelecimentos.estabelecimento_id', $estabFiltro);
                    });
            });
        }

        return $query->orderBy('id')->get();
    }

    /**
     * Estabelecimentos da OS, do pivot e do campo legado.
     */
    private function estabelecimentosDaOs(OrdemServico $os): Collection
    {
        $doPivot = DB::table('ordem_servico_estabelecimentos')
            ->where('ordem_servico_id', $os->id)
            ->pluck('estabelecimento_id');

        return $doPivot
            ->push($os->estabelecimento_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function processoMaisRecente(int $estabelecimentoId): ?Processo
    {
        return Processo::where('estabelecimento_id', $estabelecimentoId)
            ->orderByRaw("CASE WHEN status = 'aberto' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Documentos gerados na OS que também ficaram sem processo.
     */
    private function documentosOrfaos(OrdemServico $os): array
    {
        return [
            'digitais' => DocumentoDigital::where('os_id', $os->id)->whereNull('processo_id')->get(),
            'arquivos' => ProcessoDocumento::where('os_id', $os->id)->whereNull('processo_id')->get(),
        ];
    }
}
