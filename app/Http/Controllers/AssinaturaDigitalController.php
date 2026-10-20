<?php

namespace App\Http\Controllers;

use App\Models\DocumentoDigital;
use App\Models\DocumentoAssinatura;
use App\Models\UsuarioInterno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class AssinaturaDigitalController extends Controller
{
    /**
     * Exibe formulário para configurar senha de assinatura
     */
    public function configurarSenha()
    {
        $usuario = auth('interno')->user();
        return view('assinatura.configurar-senha', compact('usuario'));
    }

    /**
     * Salva/atualiza senha de assinatura digital
     */
    public function salvarSenha(Request $request)
    {
        $usuario = auth('interno')->user();

        $request->validate([
            'senha_atual' => 'required',
            'senha_assinatura' => 'required|min:6|confirmed',
        ], [
            'senha_atual.required' => 'Digite sua senha de login para confirmar',
            'senha_assinatura.required' => 'Digite a senha de assinatura digital',
            'senha_assinatura.min' => 'A senha deve ter no mínimo 6 caracteres',
            'senha_assinatura.confirmed' => 'As senhas não conferem',
        ]);

        // Verifica se a senha atual está correta
        if (!Hash::check($request->senha_atual, $usuario->password)) {
            return back()->withErrors(['senha_atual' => 'Senha de login incorreta'])->withInput();
        }

        // Atualiza a senha de assinatura
        $usuario->senha_assinatura_digital = $request->senha_assinatura;
        $usuario->save();

        return redirect()
            ->route('admin.assinatura.configurar-senha')
            ->with('success', 'Senha de assinatura digital configurada com sucesso!');
    }

    /**
     * Lista documentos pendentes de assinatura do usuário
     */
    public function documentosPendentes()
    {
        $usuario = auth('interno')->user();

        $assinaturasPendentes = DocumentoAssinatura::where('usuario_interno_id', $usuario->id)
            ->where('status', 'pendente')
            ->whereHas('documentoDigital', function($query) {
                $query->where('status', '!=', 'rascunho');
            })
            ->with(['documentoDigital.tipoDocumento', 'documentoDigital.processo'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('assinatura.pendentes', compact('assinaturasPendentes'));
    }

    /**
     * Exibe página para assinar documento
     */
    public function assinar($documentoId)
    {
        try {
            $usuario = auth('interno')->user();
            
            \Log::info('Tentando acessar página de assinatura', [
                'documento_id' => $documentoId,
                'usuario_id' => $usuario->id,
                'tem_senha_assinatura' => $usuario->temSenhaAssinatura()
            ]);

            // Verifica se o usuário tem senha de assinatura cadastrada
            if (!$usuario->temSenhaAssinatura()) {
                \Log::info('Usuário sem senha de assinatura, redirecionando para configuração');
                return redirect()
                    ->route('admin.assinatura.configurar-senha')
                    ->with('warning', 'Você precisa configurar sua senha de assinatura digital primeiro.');
            }

            $documento = DocumentoDigital::with(['tipoDocumento', 'processo.estabelecimento', 'ordemServico'])
                ->findOrFail($documentoId);

            // Verifica se o usuário está na lista de assinantes
            $assinatura = DocumentoAssinatura::where('documento_digital_id', $documentoId)
                ->where('usuario_interno_id', $usuario->id)
                ->firstOrFail();

            // Verifica se já assinou
            if ($assinatura->status === 'assinado') {
                \Log::info('Documento já foi assinado pelo usuário');
                return redirect()
                    ->route('admin.assinatura.pendentes')
                    ->with('info', 'Você já assinou este documento.');
            }

            \Log::info('Exibindo página de assinatura');
            
            // Conta outros documentos pendentes de assinatura do usuário (excluindo o atual)
            $outrosPendentes = DocumentoAssinatura::where('usuario_interno_id', $usuario->id)
                ->where('status', 'pendente')
                ->where('documento_digital_id', '!=', $documentoId)
                ->whereHas('documentoDigital', function($query) {
                    $query->where('status', '!=', 'rascunho');
                })
                ->count();
            
            return view('assinatura.assinar', compact('documento', 'assinatura', 'outrosPendentes'));
        } catch (\Exception $e) {
            \Log::error('Erro ao acessar página de assinatura', [
                'documento_id' => $documentoId,
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()
                ->route('admin.assinatura.pendentes')
                ->with('error', 'Erro ao acessar documento: ' . $e->getMessage());
        }
    }

    /**
     * Visualiza o documento em PDF antes de assinar
     */
    public function visualizarPdf($documentoId)
    {
        $usuario = auth('interno')->user();
        
        $documento = DocumentoDigital::with([
            'tipoDocumento',
            'processo.tipoProcesso',
            'processo.estabelecimento.responsaveis',
            'processo.estabelecimento.municipio',
        ])->findOrFail($documentoId);

        // Verifica se o usuário está na lista de assinantes
        $assinatura = DocumentoAssinatura::where('documento_digital_id', $documentoId)
            ->where('usuario_interno_id', $usuario->id)
            ->firstOrFail();

        // Logomarca do documento: a regra é a mesma da tela de criação e do preview
        $logomarca = $documento->processo?->logomarcaDocumento()
            ?? \App\Models\ConfiguracaoSistema::logomarcaEstadual();

        // Prepara dados para o PDF
        $data = [
            'documento' => $documento,
            'estabelecimento' => $documento->processo->estabelecimento ?? null,
            'processo' => $documento->processo ?? null,
            'logomarca' => $logomarca,
        ];

        // Gera o PDF para visualização (sem assinaturas)
        $pdf = Pdf::loadView('documentos.pdf-preview', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 15)
            ->setOption('margin-right', 15);

        return $pdf->stream($documento->numero_documento . '_preview.pdf');
    }

    /**
     * Processa a assinatura do documento
     */
    public function processar(Request $request, $documentoId)
    {
        $usuario = auth('interno')->user();

        $request->validate([
            'senha_assinatura' => 'required',
            'acao' => 'required|in:assinar,recusar',
            'motivo_recusa' => 'required_if:acao,recusar',
        ], [
            'senha_assinatura.required' => 'Digite sua senha de assinatura digital',
            'motivo_recusa.required_if' => 'Informe o motivo da recusa',
        ]);

        // Verifica se a senha de assinatura está correta
        if (!Hash::check($request->senha_assinatura, $usuario->senha_assinatura_digital)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Senha de assinatura incorreta'], 422);
            }
            return back()->withErrors(['senha_assinatura' => 'Senha de assinatura incorreta'])->withInput();
        }

        $documento = DocumentoDigital::with('processo')->findOrFail($documentoId);
        
        $assinatura = DocumentoAssinatura::where('documento_digital_id', $documentoId)
            ->where('usuario_interno_id', $usuario->id)
            ->firstOrFail();

        if ($request->acao === 'assinar') {
            // Assina o documento
            $assinatura->status = 'assinado';
            $assinatura->assinado_em = now();
            $assinatura->hash_assinatura = hash('sha256', $documento->id . $usuario->id . now());
            $assinatura->save();

            // Verifica se todos assinaram
            $todasAssinaturas = DocumentoAssinatura::where('documento_digital_id', $documentoId)
                ->where('obrigatoria', true)
                ->get();

            $todasAssinadas = $todasAssinaturas->every(function ($assinatura) {
                return $assinatura->status === 'assinado';
            });

            if ($todasAssinadas) {
                // Gera código de autenticidade se ainda não tiver
                if (!$documento->codigo_autenticidade) {
                    $documento->codigo_autenticidade = DocumentoDigital::gerarCodigoAutenticidade();
                }
                
                // Atualiza status do documento para "assinado"
                $documento->status = 'assinado';
                $documento->finalizado_em = now();
                $documento->save();

                // Verifica se é documento em lote (múltiplos processos)
                if ($documento->isLote()) {
                    // Fan-out: distribui para todos os processos vinculados
                    $documentoController = app(\App\Http\Controllers\DocumentoDigitalController::class);
                    $documentoController->executarDistribuicaoLote($documento);
                } else {
                    // Documento único: gera PDF com assinaturas
                    try {
                        $this->gerarPdfComAssinaturas($documento);
                    } catch (\Throwable $e) {
                        \Log::error('Erro ao gerar PDF após assinatura', [
                            'documento_id' => $documento->id,
                            'numero_documento' => $documento->numero_documento,
                            'os_id' => $documento->os_id,
                            'erro' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        // Não impede a assinatura - PDF será gerado na próxima visualização
                    }
                }
            }

            $isLote = $documento->isLote();
            $mensagem = 'Documento assinado com sucesso!'
                . ($todasAssinadas && $isLote ? ' O documento foi distribuído para ' . count($documento->processos_ids) . ' processos vinculados.' : '');

            // Retorna JSON se for requisição AJAX
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true, 
                    'message' => $mensagem,
                    'todas_assinadas' => $todasAssinadas
                ]);
            }

            // Redireciona para a lista de documentos pendentes
            return redirect()
                ->route('admin.documentos.index')
                ->with('success', $mensagem);
        } else {
            // Recusa o documento
            $assinatura->status = 'recusado';
            $assinatura->observacao = $request->motivo_recusa;
            $assinatura->save();

            // Atualiza status do documento para "recusado"
            $documento->status = 'recusado';
            $documento->save();

            // Retorna JSON se for requisição AJAX
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Documento recusado.']);
            }

            return redirect()
                ->route('admin.assinatura.pendentes')
                ->with('info', 'Documento recusado.');
        }
    }

    /**
     * Gera PDF do documento com assinaturas eletrônicas.
     * Pode ser chamado externamente (ex: distribuição de documentos em lote).
     */
    public function gerarPdfAssinado(DocumentoDigital $documento)
    {
        return $this->gerarPdfComAssinaturas($documento);
    }

    /**
     * Gera PDF do documento com assinaturas eletrônicas
     */
    private function gerarPdfComAssinaturas(DocumentoDigital $documento)
    {
        // Recarrega o documento com todos os relacionamentos
        $documento = DocumentoDigital::with([
            'tipoDocumento',
            'processo.tipoProcesso',
            'processo.estabelecimento.responsaveis',
            'processo.estabelecimento.municipio',
            'assinaturas' => function($query) {
                $query->where('status', 'assinado')->orderBy('ordem');
            },
            'assinaturas.usuarioInterno'
        ])->findOrFail($documento->id);

        // Garante código de autenticidade antes de gerar URL/QR
        if (empty($documento->codigo_autenticidade)) {
            $documento->codigo_autenticidade = DocumentoDigital::gerarCodigoAutenticidade();
            $documento->save();
        }

        // Gera URL de autenticidade
        $urlAutenticidade = route('verificar.autenticidade', ['codigo' => $documento->codigo_autenticidade]);

        // Gera QR Code
        $qrCode = new QrCode($urlAutenticidade);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        $qrCodeBase64 = base64_encode($result->getString());

        // Debug: Log do processo
        \Log::info('Gerando PDF - Processo:', [
            'documento_id' => $documento->id,
            'processo_id' => $documento->processo_id,
            'processo_existe' => $documento->processo ? 'sim' : 'não',
            'processo_numero' => $documento->processo->numero ?? 'null',
            'processo_tipo' => $documento->processo->tipo ?? 'null',
        ]);

        // Logomarca do documento: a regra é a mesma da tela de criação e do preview
        $logomarca = $documento->processo?->logomarcaDocumento()
            ?? \App\Models\ConfiguracaoSistema::logomarcaEstadual();

        \Log::info('Logomarca do PDF assinado', [
            'documento_id' => $documento->id,
            'processo_id' => $documento->processo?->id,
            'tipo_processo' => $documento->processo?->tipo,
            'estabelecimento_id' => $documento->processo?->estabelecimento?->id,
            'competencia_estadual' => $documento->processo?->estabelecimento?->isCompetenciaEstadual(),
            'logomarca' => $logomarca,
        ]);

        // Prepara dados para o PDF
        $data = [
            'documento' => $documento,
            'estabelecimento' => $documento->processo->estabelecimento ?? null,
            'processo' => $documento->processo ?? null,
            'assinaturas' => $documento->assinaturas,
            'urlAutenticidade' => $urlAutenticidade,
            'codigoAutenticidade' => $documento->codigo_autenticidade,
            'qrCodeBase64' => $qrCodeBase64,
            'logomarca' => $logomarca,
        ];

        // Gera o PDF
        $pdf = Pdf::loadView('documentos.pdf-assinado', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 15)
            ->setOption('margin-right', 15);

        // Salva o PDF
        $nomeArquivo = 'documentos/' . $documento->numero_documento . '.pdf';
        Storage::disk('public')->put($nomeArquivo, $pdf->output());

        // Atualiza o documento com o caminho do PDF
        $documento->arquivo_pdf = $nomeArquivo;
        $documento->save();

        // Garante vínculo/atualização do PDF assinado no processo
        if ($documento->processo_id) {
            $processoDocumento = \App\Models\ProcessoDocumento::where('processo_id', $documento->processo_id)
                ->where('observacoes', 'Documento Digital: ' . $documento->numero_documento)
                ->first();

            try {
                $tamanhoArquivo = Storage::disk('public')->size($nomeArquivo);
            } catch (\Throwable $e) {
                $tamanhoArquivo = 0;
            }

            $dadosProcessoDocumento = [
                'processo_id' => $documento->processo_id,
                'usuario_id' => $documento->usuario_criador_id,
                'tipo_usuario' => 'interno',
                'nome_arquivo' => basename($nomeArquivo),
                'nome_original' => $documento->numero_documento . '.pdf',
                'caminho' => $nomeArquivo,
                'extensao' => 'pdf',
                'tamanho' => $tamanhoArquivo,
                'tipo_documento' => 'documento_digital',
                'observacoes' => 'Documento Digital: ' . $documento->numero_documento,
            ];

            if ($processoDocumento) {
                $processoDocumento->update($dadosProcessoDocumento);
            } else {
                \App\Models\ProcessoDocumento::create($dadosProcessoDocumento);
            }
        }

        return $nomeArquivo;
    }

    /**
     * Processa assinaturas em lote
     */
    public function processarLote(Request $request)
    {
        $usuario = auth('interno')->user();

        $request->validate([
            'documentos' => 'required|array|min:1',
            'documentos.*' => 'integer',
            'senha_assinatura' => 'required',
        ]);

        // Verifica se a senha de assinatura está correta
        if (!Hash::check($request->senha_assinatura, $usuario->senha_assinatura_digital)) {
            return response()->json([
                'success' => false,
                'error' => 'Senha de assinatura incorreta'
            ], 422);
        }

        $assinados = 0;
        $erros = [];

        foreach ($request->documentos as $documentoId) {
            try {
                $documento = DocumentoDigital::with('processo')->find($documentoId);
                
                if (!$documento) {
                    $erros[] = "Documento #{$documentoId} não encontrado";
                    continue;
                }

                $assinatura = DocumentoAssinatura::where('documento_digital_id', $documentoId)
                    ->where('usuario_interno_id', $usuario->id)
                    ->where('status', 'pendente')
                    ->first();

                if (!$assinatura) {
                    $erros[] = "Documento #{$documentoId} não está pendente de assinatura";
                    continue;
                }

                // Assina o documento
                $assinatura->status = 'assinado';
                $assinatura->assinado_em = now();
                $assinatura->hash_assinatura = hash('sha256', $documento->id . $usuario->id . now());
                $assinatura->save();

                // Verifica se todos assinaram
                $todasAssinaturas = DocumentoAssinatura::where('documento_digital_id', $documentoId)
                    ->where('obrigatoria', true)
                    ->get();

                $todasAssinadas = $todasAssinaturas->every(function ($ass) {
                    return $ass->status === 'assinado';
                });

                if ($todasAssinadas) {
                    if (!$documento->codigo_autenticidade) {
                        $documento->codigo_autenticidade = DocumentoDigital::gerarCodigoAutenticidade();
                    }
                    
                    $documento->status = 'assinado';
                    $documento->finalizado_em = now();
                    $documento->save();

                    // Verifica se é documento em lote (múltiplos processos)
                    if ($documento->isLote()) {
                        $documentoController = app(\App\Http\Controllers\DocumentoDigitalController::class);
                        $documentoController->executarDistribuicaoLote($documento);
                    } else {
                        $this->gerarPdfComAssinaturas($documento);
                    }
                }

                $assinados++;
            } catch (\Exception $e) {
                $erros[] = "Erro ao assinar documento #{$documentoId}: " . $e->getMessage();
            }
        }

        if ($assinados > 0) {
            return response()->json([
                'success' => true,
                'message' => "{$assinados} documento(s) assinado(s) com sucesso!",
                'erros' => $erros
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Nenhum documento foi assinado',
            'erros' => $erros
        ], 422);
    }
}
