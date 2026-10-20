<?php

namespace App\Console\Commands;

use App\Http\Controllers\AssinaturaDigitalController;
use App\Models\ConfiguracaoSistema;
use App\Models\DocumentoDigital;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RegerarPdfDocumentoAssinado extends Command
{
    protected $signature = 'documentos:regerar-pdf
                            {--documento= : Número (003483.2026) ou id do documento}
                            {--processo= : Id do processo, regera todos os documentos assinados dele}
                            {--tipo= : Tipo de processo (ex.: descentralizacao), regera todos os afetados}
                            {--dry-run : Apenas mostra o que seria regerado}
                            {--force : Aplica sem pedir confirmação}';

    protected $description = 'Regera o PDF de documentos já assinados, aplicando a logomarca correta do processo';

    public function handle()
    {
        $documentos = $this->buscarDocumentos();

        if ($documentos === null) {
            return 1;
        }

        if ($documentos->isEmpty()) {
            $this->info('Nenhum documento assinado encontrado para esse filtro.');
            return 0;
        }

        $estadual = ConfiguracaoSistema::logomarcaEstadual();
        $dryRun = (bool) $this->option('dry-run');

        $this->info(($dryRun ? '[DRY-RUN] ' : '') . "Documentos encontrados: {$documentos->count()}");
        $this->newLine();

        $aRegerar = [];

        foreach ($documentos as $documento) {
            $processo = $documento->processo;
            $logomarcaCorreta = $processo?->logomarcaDocumento() ?? $estadual;

            $this->line("Documento {$documento->numero_documento} (id {$documento->id})");
            $this->line('  processo .........: ' . ($processo
                ? $processo->numero_processo . ' [' . $processo->tipo . ']'
                : 'sem processo'));
            $this->line('  PDF atual ........: ' . ($documento->arquivo_pdf ?: '(não gerado)'));
            $this->line('  logomarca correta : ' . $logomarcaCorreta);
            $this->line('  é a estadual? ....: ' . ($logomarcaCorreta === $estadual ? 'sim' : 'não'));

            $aRegerar[] = $documento;
            $this->newLine();
        }

        if ($dryRun) {
            $this->info('[DRY-RUN] Nada foi gravado. Rode sem --dry-run para regerar.');
            return 0;
        }

        $this->warn('O PDF é regravado no mesmo caminho. O código de autenticidade, o QR Code e as');
        $this->warn('assinaturas já coletadas são preservados — só o arquivo é desenhado de novo.');

        if (!$this->option('force') && !$this->confirm('Regerar os PDFs acima?', false)) {
            $this->info('Cancelado. Nada foi gravado.');
            return 0;
        }

        $controller = new AssinaturaDigitalController();
        $ok = 0;
        $erros = 0;

        foreach ($aRegerar as $documento) {
            $codigoAntes = $documento->codigo_autenticidade;
            $assinaturasAntes = $documento->assinaturas()->where('status', 'assinado')->count();

            try {
                $caminho = $controller->gerarPdfAssinado($documento);
                $documento->refresh();

                $preservou = $documento->codigo_autenticidade === $codigoAntes
                    && $documento->assinaturas()->where('status', 'assinado')->count() === $assinaturasAntes;

                if (!$preservou) {
                    $this->error("  {$documento->numero_documento}: código ou assinaturas mudaram — confira este documento.");
                    $erros++;
                    continue;
                }

                $tamanho = Storage::disk('public')->exists($caminho)
                    ? number_format(Storage::disk('public')->size($caminho) / 1024, 1) . ' KB'
                    : '?';

                $this->info("  {$documento->numero_documento}: regerado ({$tamanho})");
                $ok++;
            } catch (\Throwable $e) {
                $this->error("  {$documento->numero_documento}: falhou — " . $e->getMessage());
                $erros++;
            }
        }

        $this->newLine();
        $this->info("Concluído: {$ok} regerado(s), {$erros} com erro.");

        return $erros === 0 ? 0 : 1;
    }

    /** @return \Illuminate\Support\Collection<int, DocumentoDigital>|null */
    private function buscarDocumentos()
    {
        $relacoes = ['processo.estabelecimento.municipio', 'assinaturas'];

        if ($numero = $this->option('documento')) {
            $documentos = DocumentoDigital::with($relacoes)
                ->where('numero_documento', $numero)
                ->when(ctype_digit((string) $numero), fn ($q) => $q->orWhere('id', (int) $numero))
                ->get();

            if ($documentos->isEmpty()) {
                $this->error("Documento não encontrado: {$numero}");
                return null;
            }

            return $documentos;
        }

        $query = DocumentoDigital::with($relacoes)->where('status', 'assinado');

        if ($processoId = $this->option('processo')) {
            return $query->where('processo_id', (int) $processoId)->orderBy('id')->get();
        }

        if ($tipo = $this->option('tipo')) {
            return $query->whereHas('processo', fn ($q) => $q->where('tipo', $tipo))->orderBy('id')->get();
        }

        $this->error('Informe --documento, --processo ou --tipo.');
        return null;
    }
}
