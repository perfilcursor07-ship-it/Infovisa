<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Estabelecimento;
use App\Models\Processo;
use App\Models\ProcessoAlerta;
use App\Models\ProcessoDocumento;
use App\Models\DocumentoDigital;
use App\Models\DocumentoAjuda;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index()
    {
        // Quem não usa o módulo de processos (ex.: só receituário) começa pela sua área
        $usuario = auth('externo')->user();
        if (!$usuario->temModulo('processos')) {
            return redirect()->route($usuario->rotaInicial());
        }

        $usuarioId = $usuario->id;
        
        // Buscar estabelecimentos do usuário (próprios e vinculados)
        $estabelecimentos = Estabelecimento::with('municipio')
            ->where('usuario_externo_id', $usuarioId)
            ->orWhereHas('usuariosVinculados', function($q) use ($usuarioId) {
                $q->where('usuario_externo_id', $usuarioId);
            })
            ->orderBy('created_at', 'desc')
            ->get();
        // Cadastro interno de receituário: os processos contam, mas não aparece como estabelecimento
        $estabelecimentosVisiveis = $estabelecimentos->where('oculto_receituario', false);

        // Estatísticas de estabelecimentos
        $estatisticasEstabelecimentos = [
            'total' => $estabelecimentosVisiveis->count(),
            'pendentes' => $estabelecimentosVisiveis->where('status', 'pendente')->count(),
            'aprovados' => $estabelecimentosVisiveis->where('status', 'aprovado')->count(),
            'rejeitados' => $estabelecimentosVisiveis->where('status', 'rejeitado')->count(),
        ];
        
        // IDs dos estabelecimentos do usuário
        $estabelecimentoIds = $estabelecimentos->pluck('id');
        
        // Buscar processos dos estabelecimentos do usuário (apenas tipos visíveis para externo)
        $processos = Processo::whereIn('estabelecimento_id', $estabelecimentoIds)
            ->whereHas('tipoProcesso', fn($q) => $q->where('usuario_externo_pode_visualizar', true))
            ->with(['estabelecimento', 'tipoProcesso'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // IDs dos processos
        $processoIds = $processos->pluck('id');
        
        // Estatísticas de processos
        $estatisticasProcessos = [
            'total' => $processos->count(),
            'abertos' => $processos->where('status', 'aberto')->count(),
            'em_andamento' => $processos->where('status', 'em_andamento')->count(),
            'concluidos' => $processos->where('status', 'concluido')->count(),
            'arquivados' => $processos->where('status', 'arquivado')->count(),
        ];
        
        // Últimos 5 estabelecimentos
        $ultimosEstabelecimentos = $estabelecimentosVisiveis->take(5);
        
        // Últimos 5 processos
        $ultimosProcessos = $processos->take(5);
        
        // Alertas pendentes dos processos do usuário (não concluídos)
        $alertasPendentes = ProcessoAlerta::whereIn('processo_id', $processoIds)
            ->where('status', '!=', 'concluido')
            ->with(['processo.estabelecimento', 'usuarioCriador'])
            ->orderBy('data_alerta', 'asc')
            ->get();
        
        // Documentos digitais da vigilância que ainda NÃO foram visualizados pelo estabelecimento
        // São documentos assinados, não sigilosos, com todas as assinaturas completas
        $documentosPendentesVisualizacao = DocumentoDigital::whereIn('processo_id', $processoIds)
            ->where('status', 'assinado')
            ->where('sigiloso', false)
            ->whereDoesntHave('visualizacoes') // Ainda não foi visualizado
            ->with(['processo.estabelecimento', 'tipoDocumento', 'assinaturas'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(fn ($doc) => $doc->todasAssinaturasCompletas());
        
        // Documentos rejeitados que precisam de correção pelo usuário externo
        $documentosRejeitados = ProcessoDocumento::whereIn('processo_id', $processoIds)
            ->where('status_aprovacao', 'rejeitado')
            ->with(['processo.estabelecimento', 'tipoDocumentoObrigatorio'])
            ->orderBy('updated_at', 'desc')
            ->get();
        
        // Documentos com prazo pendente (notificações que precisam de resposta)
        $documentosComPrazo = DocumentoDigital::whereIn('processo_id', $processoIds)
            ->where('status', 'assinado')
            ->where('sigiloso', false)
            ->where('prazo_notificacao', true)
            ->whereNotNull('prazo_iniciado_em')
            ->whereNull('prazo_finalizado_em')
            ->with(['processo.estabelecimento', 'tipoDocumento'])
            ->orderBy('data_vencimento', 'asc')
            ->get()
            ->filter(fn ($doc) => $doc->todasAssinaturasCompletas());

        // Avisos do sistema para usuários externos
        $avisos_sistema = \App\Models\Aviso::ativos()
            ->paraNivel('usuario_externo')
            ->orderBy('tipo', 'desc')
            ->get();

        // Processos com prazo de análise ativo (documentação completa na fila pública)
        $processosComPrazoFila = collect();
        $processosAtivos = $processos->whereIn('status', ['aberto', 'em_analise', 'pendente', 'parado']);
        foreach ($processosAtivos as $proc) {
            if (!$proc->tipoProcesso || !$proc->tipoProcesso->exibir_fila_publica || !$proc->tipoProcesso->prazo_fila_publica) continue;

            $proc->loadMissing(['documentos', 'pastas', 'unidades']);
            $checklist = $proc->getDocumentosObrigatoriosChecklist();
            $docsObrig = $checklist->where('obrigatorio', true);

            if ($docsObrig->isEmpty()) continue;

            $todosAprov = true;
            $dataUltimoAprov = null;
            foreach ($docsObrig as $docO) {
                if ($docO['status'] !== 'aprovado') { $todosAprov = false; break; }
                $docP = $proc->documentos
                    ->where('tipo_documento_obrigatorio_id', $docO['id'])
                    ->where('status_aprovacao', 'aprovado')
                    ->sortByDesc(fn ($d) => $d->aprovado_em ?? $d->updated_at)
                    ->first();
                $dr = $docP?->aprovado_em ?? $docP?->updated_at;
                if ($dr && (!$dataUltimoAprov || $dr > $dataUltimoAprov)) $dataUltimoAprov = $dr;
            }

            if ($todosAprov && $dataUltimoAprov) {
                $grupoRisco = $proc->estabelecimento ? $proc->estabelecimento->getGrupoRisco() : null;
                $prazo = $proc->tipoProcesso->getPrazoFilaPublicaPorRisco($grupoRisco);
                $dataLimite = $proc->calcularDataLimiteFilaPublica($dataUltimoAprov, $prazo);
                $diasRestantes = (int) round(\Carbon\Carbon::now()->diffInDays($dataLimite, false));

                $processosComPrazoFila->push([
                    'processo' => $proc,
                    'prazo' => $prazo,
                    'dias_restantes' => $diasRestantes,
                    'atrasado' => $diasRestantes < 0,
                    'pausado' => $proc->status === 'parado',
                    'data_documentos_completos' => $dataUltimoAprov,
                ]);
            }
        }
        
        return view('company.dashboard', compact(
            'estatisticasEstabelecimentos',
            'estatisticasProcessos',
            'ultimosEstabelecimentos',
            'ultimosProcessos',
            'alertasPendentes',
            'documentosPendentesVisualizacao',
            'documentosRejeitados',
            'documentosComPrazo',
            'avisos_sistema',
            'processosComPrazoFila'
        ));
    }

    /**
     * Visualizar um documento de ajuda (PDF) - acesso global sem contexto de processo
     */
    public function visualizarDocumentoAjuda($documentoId)
    {
        $documento = DocumentoAjuda::ativos()->genericosGlobais()->findOrFail($documentoId);

        if (!Storage::disk('local')->exists($documento->arquivo)) {
            abort(404, 'Arquivo não encontrado.');
        }

        $caminho = Storage::disk('local')->path($documento->arquivo);

        return response()->file($caminho, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $documento->nome_original . '"',
        ]);
    }
}
