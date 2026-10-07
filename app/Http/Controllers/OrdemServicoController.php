<?php

namespace App\Http\Controllers;

use App\Models\OrdemServico;
use App\Models\Estabelecimento;
use App\Models\Processo;
use App\Models\ProcessoDocumento;
use App\Models\ProcessoPasta;
use App\Models\TipoAcao;
use App\Models\UsuarioInterno;
use App\Models\Municipio;
use App\Models\ChatConversa;
use App\Models\ChatMensagem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OrdemServicoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $usuario = Auth::guard('interno')->user();
        
        // Query base
        $query = OrdemServico::with(['estabelecimento', 'estabelecimentos', 'municipio'])
            ->orderBy('created_at', 'desc');
        
        // Filtro por competência baseado no nível de acesso
        if ($usuario->isAdmin()) {
            // Administrador vê tudo (sem filtro)
        } elseif ($usuario->isEstadual()) {
            // Gestor/Técnico Estadual vê apenas OSs estaduais
            $query->where('competencia', 'estadual');
        } elseif ($usuario->isMunicipal()) {
            // Gestor/Técnico Municipal vê apenas OSs municipais do seu município
            $query->where('competencia', 'municipal')
                  ->where('municipio_id', $usuario->municipio_id);
        } else {
            // Outros usuários não veem nada (segurança)
            $query->whereRaw('1 = 0');
        }
        
        // Filtros personalizados
        if ($request->filled('estabelecimento')) {
            $term = trim($request->input('estabelecimento'));
            $numericTerm = preg_replace('/\D+/', '', $term);

            $query->where(function ($scope) use ($term, $numericTerm) {
                $scope->whereHas('estabelecimento', function ($subQuery) use ($term, $numericTerm) {
                    $subQuery->where(function ($inner) use ($term, $numericTerm) {
                        $inner->whereRaw("nome_fantasia ILIKE ?", ["%{$term}%"])
                            ->orWhereRaw("razao_social ILIKE ?", ["%{$term}%"])
                            ->orWhere('cnpj', 'like', "%{$term}%")
                            ->orWhere('cpf', 'like', "%{$term}%");

                        if (!empty($numericTerm)) {
                            $inner->orWhere('cnpj', 'like', "%{$numericTerm}%")
                                  ->orWhere('cpf', 'like', "%{$numericTerm}%");
                        }
                    });
                })->orWhereHas('estabelecimentos', function ($subQuery) use ($term, $numericTerm) {
                    $subQuery->where(function ($inner) use ($term, $numericTerm) {
                        $inner->whereRaw("nome_fantasia ILIKE ?", ["%{$term}%"])
                            ->orWhereRaw("razao_social ILIKE ?", ["%{$term}%"])
                            ->orWhere('cnpj', 'like', "%{$term}%")
                            ->orWhere('cpf', 'like', "%{$term}%");

                        if (!empty($numericTerm)) {
                            $inner->orWhere('cnpj', 'like', "%{$numericTerm}%")
                                  ->orWhere('cpf', 'like', "%{$numericTerm}%");
                        }
                    });
                });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate('data_inicio', '>=', $request->input('data_inicio'));
        }

        if ($request->filled('data_fim')) {
            $query->whereDate('data_fim', '<=', $request->input('data_fim'));
        }

        // Filtro por técnico
        if ($request->filled('tecnico')) {
            $termoTecnico = trim($request->input('tecnico'));
            $tecnicoIds = UsuarioInterno::where('ativo', true)
                ->where(function ($q) use ($termoTecnico) {
                    $q->where('nome', 'ILIKE', "%{$termoTecnico}%")
                      ->orWhere('email', 'ILIKE', "%{$termoTecnico}%");
                })
                ->pluck('id')
                ->toArray();

            if (!empty($tecnicoIds)) {
                $query->where(function ($q) use ($tecnicoIds) {
                    foreach ($tecnicoIds as $tecnicoId) {
                        $q->orWhereJsonContains('tecnicos_ids', $tecnicoId)
                          ->orWhereJsonContains('tecnicos_ids', (string) $tecnicoId);
                    }
                });
            } else {
                // Nenhum técnico encontrado com esse termo, não retorna resultados
                $query->whereRaw('1 = 0');
            }
        }

        $ordensServico = $query->paginate(10)->withQueryString();

        $statusOptions = [
            'em_andamento' => 'Em Andamento',
            'finalizada' => 'Finalizada',
            'cancelada' => 'Cancelada',
        ];

        $filters = $request->only(['estabelecimento', 'status', 'data_inicio', 'data_fim', 'tecnico']);
        
        return view('ordens-servico.index', compact('ordensServico', 'statusOptions', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     * APENAS Administrador, Gestor Estadual e Gestor Municipal podem criar OS
     */
    public function create(Request $request)
    {
        $usuario = Auth::guard('interno')->user();
        $permiteDatasRetroativas = $usuario->isAdmin();
        
        // Verifica permissão: apenas Admin e Gestores podem criar OS
        if (!$usuario->isAdmin() && !$usuario->isGestor()) {
            return redirect()
                ->back()
                ->with('error', 'Apenas Administradores e Gestores podem criar Ordens de Serviço.');
        }
        
        // Busca estabelecimentos conforme competência
        $estabelecimentos = $this->getEstabelecimentosPorCompetencia($usuario);
        
        // Busca tipos de ação ativos com subações
        $tiposAcao = TipoAcao::ativo()->with('subAcoesAtivas')->orderBy('descricao')->get();
        
        // Busca técnicos conforme competência
        $tecnicos = $this->getTecnicosPorCompetencia($usuario);
        
        // Busca municípios se for municipal
        $municipios = null;
        if ($usuario->isMunicipal()) {
            $municipios = Municipio::where('id', $usuario->municipio_id)->get();
        } elseif ($usuario->isAdmin()) {
            $municipios = Municipio::orderBy('nome')->get();
        }
        
        // Pré-seleciona estabelecimento e processo se passados via query string
        $estabelecimentoPreSelecionado = null;
        $processoPreSelecionado = null;
        
        if ($request->filled('estabelecimento_id')) {
            $estabelecimentoPreSelecionado = Estabelecimento::find($request->estabelecimento_id);
        }
        
        if ($request->filled('processo_id')) {
            $processoPreSelecionado = Processo::find($request->processo_id);
        }

        $pastasProcesso = collect();
        if ($processoPreSelecionado) {
            $pastasProcesso = $processoPreSelecionado->pastas()
                ->orderBy('ordem')
                ->orderBy('nome')
                ->get();
        }
        
        return view('ordens-servico.create', compact('estabelecimentos', 'tiposAcao', 'tecnicos', 'municipios', 'estabelecimentoPreSelecionado', 'processoPreSelecionado', 'permiteDatasRetroativas', 'pastasProcesso'));
    }

    /**
     * Store a newly created resource in storage.
     * APENAS Administrador, Gestor Estadual e Gestor Municipal podem criar OS
     */
    public function store(Request $request)
    {
        $usuario = Auth::guard('interno')->user();
        
        // Verifica permissão: apenas Admin e Gestores podem criar OS
        if (!$usuario->isAdmin() && !$usuario->isGestor()) {
            return redirect()
                ->back()
                ->with('error', 'Apenas Administradores e Gestores podem criar Ordens de Serviço.');
        }
        
        // Validação condicional: processo é obrigatório se há estabelecimento
        $rules = [
            'tipo_vinculacao' => 'required|in:com_estabelecimento,sem_estabelecimento',
            'estabelecimento_id' => 'nullable|exists:estabelecimentos,id',
            'estabelecimentos_ids' => 'nullable|array',
            'estabelecimentos_ids.*' => 'exists:estabelecimentos,id',
            'continuar_sem_processo_estabelecimentos' => 'nullable|array',
            'pasta_id' => 'nullable|integer',
            'tipos_acao_ids' => 'required|array|min:1',
            'tipos_acao_ids.*' => 'exists:tipo_acoes,id',
            'atividades_tecnicos' => 'required|json',
            'observacoes' => 'nullable|string',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'documento_anexo' => 'nullable|file|mimes:pdf|max:10240',
        ];

        if (!$usuario->isAdmin()) {
            $rules['data_inicio'] = 'required|date|after_or_equal:today';
            $rules['data_fim'] = 'required|date|after_or_equal:data_inicio|after_or_equal:today';
        }
        
        // Processo agora é obrigatório por estabelecimento (via processos_estabelecimentos)
        $estabelecimentosIds = $request->input('estabelecimentos_ids', []);
        $rules['processo_id'] = 'nullable|exists:processos,id';
        
        // Validação de processos por estabelecimento
        if (!empty($estabelecimentosIds)) {
            $rules['processos_estabelecimentos'] = 'required|array';
            $rules['processos_estabelecimentos.*'] = 'nullable|exists:processos,id';
        }
        
        $messages = [
            'processos_estabelecimentos.required' => 'Revise os processos vinculados aos estabelecimentos selecionados.',
            'processo_id.required' => 'Selecione um processo vinculado ao estabelecimento.',
            'atividades_tecnicos.required' => 'Atribua pelo menos um técnico para cada atividade selecionada.',
            'atividades_tecnicos.json' => 'Estrutura de técnicos por atividade inválida.',
            'data_inicio.required' => 'Informe a data de início da ordem de serviço.',
            'data_fim.required' => 'Informe a data de término da ordem de serviço.',
            'data_fim.after_or_equal' => 'A data de término não pode ser anterior à data de início.',
            'data_inicio.after_or_equal' => 'Não é permitido criar ordem de serviço com data de início retroativa.',
        ];

        $validated = $request->validate($rules, $messages);
        
        // Processa e valida a estrutura de atividades com técnicos
        $atividadesTecnicos = json_decode($validated['atividades_tecnicos'] ?? '[]', true);
        
        if (!is_array($atividadesTecnicos) || empty($atividadesTecnicos)) {
            return back()->withErrors(['atividades_tecnicos' => 'Atribua pelo menos um técnico para cada atividade selecionada.'])->withInput();
        }
        
        // Valida se todos os técnicos existem e têm permissão
        $tecnicosIds = [];
        foreach ($atividadesTecnicos as $atividade) {
            if (!isset($atividade['tecnicos']) || !is_array($atividade['tecnicos'])) {
                return back()->withErrors(['atividades_tecnicos' => 'Estrutura de técnicos inválida.'])->withInput();
            }

            // Validação: cada atividade DEVE ter pelo menos um técnico atribuído
            if (empty($atividade['tecnicos'])) {
                $nomeAtividade = $atividade['nome_atividade'] ?? 'Atividade';
                return back()->withErrors(['atividades_tecnicos' => "Atribua pelo menos um técnico para a atividade \"{$nomeAtividade}\"."])->withInput();
            }

            if (!empty($atividade['tecnicos'])) {
                if (!isset($atividade['responsavel_id']) || !$atividade['responsavel_id']) {
                    return back()->withErrors(['atividades_tecnicos' => 'Defina um responsável para cada atividade com técnicos atribuídos.'])->withInput();
                }

                if (!in_array((int) $atividade['responsavel_id'], array_map('intval', $atividade['tecnicos']), true)) {
                    return back()->withErrors(['atividades_tecnicos' => 'O responsável deve estar na lista de técnicos da atividade.'])->withInput();
                }
            }

            $tecnicosIds = array_merge($tecnicosIds, $atividade['tecnicos']);
        }
        
        $tecnicosIds = array_unique($tecnicosIds);
        $tecnicosValidos = $this->getTecnicosPorCompetencia($usuario)->pluck('id')->toArray();
        
        foreach ($tecnicosIds as $tecnicoId) {
            if (!in_array($tecnicoId, $tecnicosValidos)) {
                return back()->withErrors(['atividades_tecnicos' => 'Um ou mais técnicos selecionados não são válidos para sua competência.'])->withInput();
            }
        }
        
        // Mantém compatibilidade com campo antigo tecnicos_ids
        $validated['tecnicos_ids'] = $tecnicosIds;
        $validated['atividades_tecnicos'] = $atividadesTecnicos;
        
        // Upload do documento se fornecido
        if ($request->hasFile('documento_anexo')) {
            $arquivo = $request->file('documento_anexo');
            $nomeArquivo = time() . '_' . $arquivo->getClientOriginalName();
            $caminhoArquivo = $arquivo->storeAs('ordens-servico/documentos', $nomeArquivo, 'public');
            $validated['documento_anexo_path'] = $caminhoArquivo;
            $validated['documento_anexo_nome'] = $arquivo->getClientOriginalName();
        }
        
        // Coleta IDs de estabelecimentos múltiplos e mapeamento de processos
        $estabelecimentosIds = $request->input('estabelecimentos_ids', []);
        $processosEstabelecimentos = $request->input('processos_estabelecimentos', []);
        $continuarSemProcesso = $request->input('continuar_sem_processo_estabelecimentos', []);

        $this->validarSelecaoProcessosPorEstabelecimento(
            $estabelecimentosIds,
            $processosEstabelecimentos,
            $continuarSemProcesso
        );
        
        // Se tem múltiplos, usa o primeiro como referência para competência
        $estabelecimentoRef = null;
        if (!empty($estabelecimentosIds)) {
            $validated['estabelecimento_id'] = $estabelecimentosIds[0]; // primeiro como referência principal
            $estabelecimentoRef = Estabelecimento::find($estabelecimentosIds[0]);
            // Processo principal = processo do primeiro estabelecimento (compatibilidade)
            if (isset($processosEstabelecimentos[$estabelecimentosIds[0]])) {
                $validated['processo_id'] = $processosEstabelecimentos[$estabelecimentosIds[0]];
            }
        }
        
        // Determina competência e município
        if (!empty($validated['estabelecimento_id'])) {
            // Tem estabelecimento vinculado
            $estabelecimento = $estabelecimentoRef ?? Estabelecimento::findOrFail($validated['estabelecimento_id']);
            
            // Se não foi especificado processo_id e não veio de processosEstabelecimentos, tenta vincular ao processo ativo
            if (empty($validated['processo_id'])) {
                $validated['processo_id'] = $this->resolverProcessoParaVinculoOS($estabelecimento->id);
            }
            
            if ($usuario->isEstadual()) {
                $validated['competencia'] = 'estadual';
                $validated['municipio_id'] = null;
            } elseif ($usuario->isMunicipal()) {
                $validated['competencia'] = 'municipal';
                $validated['municipio_id'] = $usuario->municipio_id;
                
                // Valida se o estabelecimento pertence ao município do usuário
                if ($estabelecimento->municipio_id != $usuario->municipio_id) {
                    return back()->withErrors(['estabelecimento_id' => 'Você não tem permissão para criar OS para este estabelecimento.'])->withInput();
                }
            } elseif ($usuario->isAdmin()) {
                // Admin pode escolher competência baseado no estabelecimento
                $validated['competencia'] = $estabelecimento->competencia_manual ?? 'estadual';
                $validated['municipio_id'] = $estabelecimento->municipio_id;
            }
        } else {
            // Sem estabelecimento - define competência baseada no usuário
            if ($usuario->isEstadual()) {
                $validated['competencia'] = 'estadual';
                $validated['municipio_id'] = null;
            } elseif ($usuario->isMunicipal()) {
                $validated['competencia'] = 'municipal';
                $validated['municipio_id'] = $usuario->municipio_id;
            } elseif ($usuario->isAdmin()) {
                // Admin sem estabelecimento - define como estadual por padrão
                $validated['competencia'] = 'estadual';
                $validated['municipio_id'] = null;
            }
        }

        $validated['pasta_id'] = $this->resolverPastaIdValida(
            $request->filled('pasta_id') ? (int) $request->input('pasta_id') : null,
            isset($validated['processo_id']) ? (int) $validated['processo_id'] : null
        );
        
        // Gera número da OS e define data de abertura automática
        // Gestor que assina a OS (apenas para gestores estaduais)
        if ($request->filled('gestor_assinatura_id')) {
            $validated['gestor_assinatura_id'] = $request->gestor_assinatura_id;
        }

        // Usa transação para garantir atomicidade na geração do número
        $ordemServico = \DB::transaction(function () use ($validated, $estabelecimentosIds, $processosEstabelecimentos) {
            $validated['numero'] = OrdemServico::gerarNumero();
            $validated['data_abertura'] = now()->format('Y-m-d');
            $validated['status'] = 'em_andamento';
            
            $os = OrdemServico::create($validated);
            
            // Salva múltiplos estabelecimentos na tabela pivot com processo_id
            if (!empty($estabelecimentosIds)) {
                $syncData = [];
                foreach ($estabelecimentosIds as $estId) {
                    $syncData[$estId] = [
                        'processo_id' => $processosEstabelecimentos[$estId] ?? null,
                    ];
                }
                $os->estabelecimentos()->sync($syncData);
            } elseif (!empty($validated['estabelecimento_id'])) {
                // Se apenas um estabelecimento (legado), salva no pivot também
                $estId = $validated['estabelecimento_id'];
                $os->estabelecimentos()->sync([
                    $estId => ['processo_id' => $processosEstabelecimentos[$estId] ?? $validated['processo_id'] ?? null]
                ]);
            }
            
            return $os;
        });
        
        // Envia notificação no chat para os técnicos atribuídos
        $this->enviarNotificacaoTecnicos($ordemServico, $usuario);
        
        return redirect()->route('admin.ordens-servico.index')
            ->with('success', "Ordem de Serviço {$ordemServico->numero} criada com sucesso!");
    }

    /**
     * Display the specified resource.
     */
    public function show(OrdemServico $ordemServico)
    {
        $usuario = Auth::guard('interno')->user();
        
        // Verifica permissão
        if (!$this->podeVisualizarOS($usuario, $ordemServico)) {
            abort(403, 'Você não tem permissão para visualizar esta ordem de serviço.');
        }

        // Ao visualizar a OS, marca como lidas as notificações pendentes relacionadas a ela
        // Compatibilidade: considera tanto ordem_servico_id quanto link da OS
        $osPath = '/admin/ordens-servico/' . $ordemServico->id;

        \App\Models\Notificacao::doUsuario($usuario->id)
            ->naoLidas()
            ->where(function ($query) use ($ordemServico, $osPath) {
                $query->where('ordem_servico_id', $ordemServico->id)
                      ->orWhere('link', 'like', '%' . $osPath . '%');
            })
            ->update([
                'lida' => true,
                'lida_em' => now(),
            ]);
        
        $ordemServico->load([
            'estabelecimento.municipio',
            'estabelecimentos.municipio',
            'municipio',
            'processo',
            'gestorAssinatura',
            'documentosDigitais.tipoDocumento',
            'documentosDigitais.assinaturas',
            'documentosDigitais.usuarioCriador',
            'arquivosExternos.usuario',
            'arquivosExternos.processo.estabelecimento',
        ]);

        $pesquisaInterna = $this->buscarPesquisaInternaPendente($ordemServico, $usuario);

        if ($pesquisaInterna) {
            $pesquisaInterna->load('perguntas.opcoes');
        }

        return view('ordens-servico.show', compact(
            'ordemServico', 'pesquisaInterna'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OrdemServico $ordemServico)
    {
        $usuario = Auth::guard('interno')->user();
        
        // Bloqueia edição se OS estiver finalizada
        if ($ordemServico->status === 'finalizada') {
            return redirect()->route('admin.ordens-servico.show', $ordemServico)
                ->with('error', 'Não é possível editar uma Ordem de Serviço finalizada. Use a opção "Reiniciar OS" se necessário.');
        }
        
        // Verifica permissão
        if (!$this->podeEditarOS($usuario, $ordemServico)) {
            abort(403, 'Você não tem permissão para editar esta ordem de serviço.');
        }
        
        // Busca tipos de ação ativos com subações
        $tiposAcao = TipoAcao::ativo()->with('subAcoesAtivas')->orderBy('descricao')->get();
        
        $somentVincularEstabelecimento = false;

        // Busca técnicos conforme competência
        $tecnicos = $this->getTecnicosPorCompetencia($usuario);
        
        // Busca municípios se for municipal
        $municipios = null;
        if ($usuario->isMunicipal()) {
            $municipios = Municipio::where('id', $usuario->municipio_id)->get();
        } elseif ($usuario->isAdmin()) {
            $municipios = Municipio::orderBy('nome')->get();
        }
        
        // Carrega estabelecimentos múltiplos
        $ordemServico->load('estabelecimentos');

        $pastasProcesso = collect();
        if ($ordemServico->processo_id) {
            $pastasProcesso = ProcessoPasta::where('processo_id', $ordemServico->processo_id)
                ->orderBy('ordem')
                ->orderBy('nome')
                ->get();
        }
        
        return view('ordens-servico.edit', compact('ordemServico', 'tiposAcao', 'tecnicos', 'municipios', 'somentVincularEstabelecimento', 'pastasProcesso'));
    }

    /**
     * Assina digitalmente a OS como gestor
     */
    public function assinarGestor(Request $request, OrdemServico $ordemServico)
    {
        $usuario = Auth::guard('interno')->user();

        // Verifica se o usuário é o gestor designado para assinar
        if ($ordemServico->gestor_assinatura_id !== $usuario->id) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Você não é o gestor designado para assinar esta OS.'], 403);
            }
            return back()->with('error', 'Você não é o gestor designado para assinar esta OS.');
        }

        // Verifica se já foi assinada
        if ($ordemServico->assinadaPeloGestor()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Esta OS já foi assinada.'], 400);
            }
            return back()->with('error', 'Esta OS já foi assinada.');
        }

        // Valida senha de assinatura
        $request->validate([
            'senha_assinatura' => 'required',
        ]);

        if (!$usuario->temSenhaAssinatura()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Você precisa configurar sua senha de assinatura digital primeiro.'], 400);
            }
            return back()->with('error', 'Você precisa configurar sua senha de assinatura digital primeiro.');
        }

        if (!\Hash::check($request->senha_assinatura, $usuario->senha_assinatura_digital)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Senha de assinatura incorreta.'], 422);
            }
            return back()->withErrors(['senha_assinatura' => 'Senha de assinatura incorreta.']);
        }

        // Gera hash da assinatura
        $hash = hash('sha256', $ordemServico->id . $usuario->id . now()->toISOString());

        $ordemServico->update([
            'gestor_assinatura_hash' => $hash,
            'gestor_assinado_em' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Ordem de Serviço assinada com sucesso!',
            ]);
        }

        return redirect()
            ->route('admin.ordens-servico.show', $ordemServico)
            ->with('success', 'Ordem de Serviço assinada com sucesso!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OrdemServico $ordemServico)
    {
        $usuario = Auth::guard('interno')->user();
        
        // Bloqueia edição se OS estiver finalizada
        if ($ordemServico->status === 'finalizada') {
            return redirect()->route('admin.ordens-servico.show', $ordemServico)
                ->with('error', 'Não é possível editar uma Ordem de Serviço finalizada.');
        }
        
        // Verifica permissão
        if (!$this->podeEditarOS($usuario, $ordemServico)) {
            abort(403, 'Você não tem permissão para editar esta ordem de serviço.');
        }

        // Validação condicional: processo é obrigatório se há estabelecimento
        $rules = [
            'estabelecimento_id' => 'nullable|exists:estabelecimentos,id',
            'estabelecimentos_ids' => 'nullable|array',
            'estabelecimentos_ids.*' => 'exists:estabelecimentos,id',
            'continuar_sem_processo_estabelecimentos' => 'nullable|array',
            'pasta_id' => 'nullable|integer',
            'tipos_acao_ids' => 'required|array|min:1',
            'tipos_acao_ids.*' => 'exists:tipo_acoes,id',
            'atividades_tecnicos' => 'required|json',
            'observacoes' => 'nullable|string',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
        ];
        
        // Processo agora é obrigatório por estabelecimento (via processos_estabelecimentos)
        $estabelecimentosIds = $request->input('estabelecimentos_ids', []);
        $rules['processo_id'] = 'nullable|exists:processos,id';
        
        if (!empty($estabelecimentosIds)) {
            $rules['processos_estabelecimentos'] = 'required|array';
            $rules['processos_estabelecimentos.*'] = 'nullable|exists:processos,id';
        }
        
        $validated = $request->validate($rules, [
            'processos_estabelecimentos.required' => 'Revise os processos vinculados aos estabelecimentos selecionados.',
            'atividades_tecnicos.required' => 'Atribua pelo menos um técnico para cada atividade selecionada.',
            'atividades_tecnicos.json' => 'Estrutura de técnicos por atividade inválida.',
        ]);
        
        // Processa e valida a estrutura de atividades com técnicos
        $atividadesTecnicos = json_decode($validated['atividades_tecnicos'] ?? '[]', true);
        
        if (!is_array($atividadesTecnicos) || empty($atividadesTecnicos)) {
            return back()->withErrors(['atividades_tecnicos' => 'Atribua pelo menos um técnico para cada atividade selecionada.'])->withInput();
        }
        
        // Valida se todos os técnicos existem e têm permissão
        $tecnicosIds = [];
        foreach ($atividadesTecnicos as $atividade) {
            if (!isset($atividade['tecnicos']) || !is_array($atividade['tecnicos'])) {
                return back()->withErrors(['atividades_tecnicos' => 'Estrutura de técnicos inválida.'])->withInput();
            }

            // Validação: cada atividade DEVE ter pelo menos um técnico atribuído
            if (empty($atividade['tecnicos'])) {
                $nomeAtividade = $atividade['nome_atividade'] ?? 'Atividade';
                return back()->withErrors(['atividades_tecnicos' => "Atribua pelo menos um técnico para a atividade \"{$nomeAtividade}\"."])->withInput();
            }

            if (!empty($atividade['tecnicos'])) {
                if (!isset($atividade['responsavel_id']) || !$atividade['responsavel_id']) {
                    return back()->withErrors(['atividades_tecnicos' => 'Defina um responsável para cada atividade com técnicos atribuídos.'])->withInput();
                }

                if (!in_array((int) $atividade['responsavel_id'], array_map('intval', $atividade['tecnicos']), true)) {
                    return back()->withErrors(['atividades_tecnicos' => 'O responsável deve estar na lista de técnicos da atividade.'])->withInput();
                }
            }

            $tecnicosIds = array_merge($tecnicosIds, $atividade['tecnicos']);
        }
        
        $tecnicosIds = array_unique($tecnicosIds);
        $tecnicosValidos = $this->getTecnicosPorCompetencia($usuario)->pluck('id')->toArray();
        
        foreach ($tecnicosIds as $tecnicoId) {
            if (!in_array($tecnicoId, $tecnicosValidos)) {
                return back()->withErrors(['atividades_tecnicos' => 'Um ou mais técnicos selecionados não são válidos para sua competência.'])->withInput();
            }
        }
        
        // Mantém compatibilidade com campo antigo tecnicos_ids
        $validated['tecnicos_ids'] = $tecnicosIds;
        $validated['atividades_tecnicos'] = $atividadesTecnicos;
        
        // Coleta IDs de estabelecimentos múltiplos e mapeamento de processos
        $estabelecimentosIds = $request->input('estabelecimentos_ids', []);
        $processosEstabelecimentos = $request->input('processos_estabelecimentos', []);
        $continuarSemProcesso = $request->input('continuar_sem_processo_estabelecimentos', []);

        $this->validarSelecaoProcessosPorEstabelecimento(
            $estabelecimentosIds,
            $processosEstabelecimentos,
            $continuarSemProcesso
        );
        
        // Se tem múltiplos, usa o primeiro como referência
        if (!empty($estabelecimentosIds)) {
            $validated['estabelecimento_id'] = $estabelecimentosIds[0];
            // Processo principal = processo do primeiro estabelecimento (compatibilidade)
            $processoDoEstab = $processosEstabelecimentos[$estabelecimentosIds[0]] ?? null;
            if (!empty($processoDoEstab)) {
                $validated['processo_id'] = (int) $processoDoEstab;
            }
        }
        
        // Valida se o estabelecimento pertence ao município do usuário (se municipal)
        if (!empty($validated['estabelecimento_id']) && $usuario->isMunicipal()) {
            $estabelecimento = Estabelecimento::findOrFail($validated['estabelecimento_id']);
            if ($estabelecimento->municipio_id != $usuario->municipio_id) {
                return back()->withErrors(['estabelecimento_id' => 'Você não tem permissão para atribuir OS para este estabelecimento.'])->withInput();
            }
        }
        
        // Se estabelecimento foi alterado, busca processo ativo para vincular
        if (!empty($validated['estabelecimento_id']) && $validated['estabelecimento_id'] != $ordemServico->estabelecimento_id && count($estabelecimentosIds) <= 1) {
            $estabelecimento = Estabelecimento::findOrFail($validated['estabelecimento_id']);
            
            // Busca processo do estabelecimento para vincular a OS
            $validated['processo_id'] = $this->resolverProcessoParaVinculoOS($estabelecimento->id);
            
            // Atualiza competência e município baseado no estabelecimento
            if ($usuario->isEstadual()) {
                $validated['competencia'] = 'estadual';
                $validated['municipio_id'] = null;
            } elseif ($usuario->isMunicipal()) {
                $validated['competencia'] = 'municipal';
                $validated['municipio_id'] = $usuario->municipio_id;
            } elseif ($usuario->isAdmin()) {
                $validated['competencia'] = $estabelecimento->competencia_manual ?? 'estadual';
                $validated['municipio_id'] = $estabelecimento->municipio_id;
            }
        } elseif (count($estabelecimentosIds) > 1) {
            // Múltiplos estabelecimentos: competência e município do primeiro
            $estabelecimento = Estabelecimento::find($estabelecimentosIds[0]);
            if ($estabelecimento) {
                if ($usuario->isEstadual()) {
                    $validated['competencia'] = 'estadual';
                    $validated['municipio_id'] = null;
                } elseif ($usuario->isMunicipal()) {
                    $validated['competencia'] = 'municipal';
                    $validated['municipio_id'] = $usuario->municipio_id;
                } elseif ($usuario->isAdmin()) {
                    $validated['competencia'] = $estabelecimento->competencia_manual ?? 'estadual';
                    $validated['municipio_id'] = $estabelecimento->municipio_id;
                }
            }
            // Processo principal = processo do primeiro estabelecimento (compatibilidade)
            if (isset($processosEstabelecimentos[$estabelecimentosIds[0]])) {
                $validated['processo_id'] = $processosEstabelecimentos[$estabelecimentosIds[0]];
            } else {
                $validated['processo_id'] = null;
            }
        } elseif (!empty($estabelecimentosIds) && count($estabelecimentosIds) === 1) {
            // Mesmo estabelecimento, 1 só - usa o processo selecionado no formulário
            $processoDoEstab = $processosEstabelecimentos[$estabelecimentosIds[0]] ?? null;
            if (!empty($processoDoEstab)) {
                $validated['processo_id'] = (int) $processoDoEstab;
            }
        }

        $processoIdValidacaoPasta = isset($validated['processo_id'])
            ? (int) $validated['processo_id']
            : ($ordemServico->processo_id ? (int) $ordemServico->processo_id : null);

        if ($request->has('pasta_id')) {
            $validated['pasta_id'] = $this->resolverPastaIdValida(
                $request->filled('pasta_id') ? (int) $request->input('pasta_id') : null,
                $processoIdValidacaoPasta
            );
        } elseif ($ordemServico->pasta_id) {
            $pastaAtualValida = ProcessoPasta::where('id', $ordemServico->pasta_id)
                ->where('processo_id', $processoIdValidacaoPasta)
                ->exists();

            if (!$pastaAtualValida) {
                $validated['pasta_id'] = null;
            }
        }

        // Gestor que assina a OS (apenas para gestores estaduais)
        if ($request->has('gestor_assinatura_id')) {
            $validated['gestor_assinatura_id'] = $request->gestor_assinatura_id ?: null;
        }
        
        $ordemServico->update($validated);

        \Log::info('OS Update Debug', [
            'os_id' => $ordemServico->id,
            'processo_id_salvo' => $validated['processo_id'] ?? 'NAO_DEFINIDO',
            'estabelecimentos_ids' => $estabelecimentosIds,
            'processos_estabelecimentos' => $processosEstabelecimentos,
            'processo_id_request' => $request->input('processo_id'),
        ]);
        
        // Sincroniza estabelecimentos na tabela pivot com processo_id
        if (!empty($estabelecimentosIds)) {
            $syncData = [];
            foreach ($estabelecimentosIds as $estId) {
                $syncData[$estId] = [
                    'processo_id' => $processosEstabelecimentos[$estId] ?? null,
                ];
            }
            $ordemServico->estabelecimentos()->sync($syncData);
        } elseif (!empty($validated['estabelecimento_id'])) {
            $estId = $validated['estabelecimento_id'];
            $ordemServico->estabelecimentos()->sync([
                $estId => ['processo_id' => $processosEstabelecimentos[$estId] ?? $validated['processo_id'] ?? null]
            ]);
        } else {
            $ordemServico->estabelecimentos()->sync([]);
        }
        
        return redirect()->route('admin.ordens-servico.index')
            ->with('success', "Ordem de Serviço {$ordemServico->numero} atualizada com sucesso!");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, OrdemServico $ordemServico)
    {
        $usuario = Auth::guard('interno')->user();
        
        // Verifica permissão
        if (!$this->podeExcluirOS($usuario, $ordemServico)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para excluir esta ordem de serviço.'
                ], 403);
            }
            abort(403, 'Você não tem permissão para excluir esta ordem de serviço.');
        }
        
        // Valida senha de assinatura digital
        $senhaAssinatura = $request->input('senha_assinatura');
        
        if (!$senhaAssinatura) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'A senha de assinatura digital é obrigatória.'
                ], 422);
            }
            return back()->withErrors(['senha_assinatura' => 'A senha de assinatura digital é obrigatória.']);
        }
        
        // Verifica se o usuário tem senha de assinatura configurada
        if (!$usuario->senha_assinatura_digital) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não possui senha de assinatura digital configurada. Configure em "Configurar Senha de Assinatura".'
                ], 422);
            }
            return back()->withErrors(['senha_assinatura' => 'Você não possui senha de assinatura digital configurada.']);
        }
        
        // Valida a senha
        if (!Hash::check($senhaAssinatura, $usuario->senha_assinatura_digital)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Senha de assinatura digital incorreta.'
                ], 422);
            }
            return back()->withErrors(['senha_assinatura' => 'Senha de assinatura digital incorreta.']);
        }
        
        $numero = $ordemServico->numero;
        $ordemServico->delete();
        
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Ordem de Serviço {$numero} excluída com sucesso!"
            ]);
        }
        
        return redirect()->route('admin.ordens-servico.index')
            ->with('success', "Ordem de Serviço {$numero} excluída com sucesso!");
    }

    /**
     * Cancela uma ordem de serviço
     */
    public function cancelar(Request $request, OrdemServico $ordemServico)
    {
        $usuario = Auth::guard('interno')->user();

        // TecnicoEstadual pode apenas vincular estabelecimento, não cancelar OS
        if ($usuario->nivel_acesso === \App\Enums\NivelAcesso::TecnicoEstadual) {
            abort(403, 'Técnicos estaduais não têm permissão para cancelar ordens de serviço.');
        }
        
        // Verifica permissão
        if (!$this->podeEditarOS($usuario, $ordemServico)) {
            abort(403, 'Você não tem permissão para cancelar esta ordem de serviço.');
        }
        
        // Não permite cancelar OS finalizada
        if ($ordemServico->status === 'finalizada') {
            return redirect()->route('admin.ordens-servico.show', $ordemServico)
                ->with('error', 'Não é possível cancelar uma Ordem de Serviço finalizada.');
        }
        
        // Não permite cancelar OS já cancelada
        if ($ordemServico->status === 'cancelada') {
            return redirect()->route('admin.ordens-servico.show', $ordemServico)
                ->with('error', 'Esta Ordem de Serviço já está cancelada.');
        }
        
        // Valida motivo do cancelamento
        $validated = $request->validate([
            'motivo_cancelamento' => 'required|string|min:20',
        ], [
            'motivo_cancelamento.required' => 'Informe o motivo do cancelamento.',
            'motivo_cancelamento.min' => 'O motivo deve ter no mínimo 20 caracteres.',
        ]);
        
        $numero = $ordemServico->numero;
        $ordemServico->update([
            'status' => 'cancelada',
            'motivo_cancelamento' => $validated['motivo_cancelamento'],
            'cancelada_em' => now(),
            'cancelada_por' => $usuario->id,
        ]);
        
        return redirect()->route('admin.ordens-servico.index')
            ->with('success', "Ordem de Serviço {$numero} cancelada com sucesso!");
    }

    /**
     * Reinicia uma ordem de serviço cancelada
     */
    public function reativar(OrdemServico $ordemServico)
    {
        $usuario = Auth::guard('interno')->user();
        
        // Apenas gestores podem reativar
        if (!$usuario->isAdmin() && !$usuario->isEstadual() && !$usuario->isMunicipal()) {
            abort(403, 'Apenas gestores podem reativar ordens de serviço canceladas.');
        }
        
        // Verifica se está cancelada
        if ($ordemServico->status !== 'cancelada') {
            return redirect()->route('admin.ordens-servico.show', $ordemServico)
                ->with('error', 'Apenas ordens de serviço canceladas podem ser reativadas.');
        }
        
        $numero = $ordemServico->numero;
        $ordemServico->update([
            'status' => 'em_andamento',
            'motivo_cancelamento' => null,
            'cancelada_em' => null,
            'cancelada_por' => null,
        ]);
        
        return redirect()->route('admin.ordens-servico.show', $ordemServico)
            ->with('success', "Ordem de Serviço {$numero} reativada com sucesso!");
    }

    /**
     * Retorna estabelecimentos conforme competência do usuário
     */
    private function getEstabelecimentosPorCompetencia($usuario)
    {
        $query = Estabelecimento::orderBy('nome_fantasia');
        
        if ($usuario->isMunicipal()) {
            // Gestor municipal vê apenas estabelecimentos do seu município
            $query->where('municipio_id', $usuario->municipio_id);
        }
        // Administrador e Estadual veem todos inicialmente
        
        $estabelecimentos = $query->get();
        
        // Filtra por competência usando o método do modelo
        if ($usuario->isEstadual()) {
            // Gestor estadual vê apenas estabelecimentos de competência estadual
            $estabelecimentos = $estabelecimentos->filter(function($estabelecimento) {
                return $estabelecimento->isCompetenciaEstadual();
            });
        } elseif ($usuario->isMunicipal()) {
            // Gestor municipal vê apenas estabelecimentos de competência municipal
            $estabelecimentos = $estabelecimentos->filter(function($estabelecimento) {
                return !$estabelecimento->isCompetenciaEstadual();
            });
        }
        
        return $estabelecimentos;
    }

    /**
     * Retorna técnicos conforme competência do usuário
     */
    private function getTecnicosPorCompetencia($usuario)
    {
        $query = UsuarioInterno::where('ativo', true)->orderBy('nome');
        
        if ($usuario->isAdmin() || $usuario->isEstadual()) {
            // Administrador e usuários estaduais veem técnicos e gestores estaduais (não admin)
            $query->whereIn('nivel_acesso', ['gestor_estadual', 'tecnico_estadual']);
        } elseif ($usuario->isMunicipal()) {
            // Gestor/Técnico municipal vê apenas usuários municipais do seu município
            $query->whereIn('nivel_acesso', ['gestor_municipal', 'tecnico_municipal'])
                  ->where('municipio_id', $usuario->municipio_id);
        }
        
        return $query->get();
    }

    /**
     * Verifica se usuário pode visualizar a OS
     */
    private function podeVisualizarOS($usuario, $ordemServico)
    {
        // Admin sempre pode
        if ($usuario->isAdmin()) {
            return true;
        }
        
        // Se é técnico atribuído, sempre pode visualizar (independente da competência)
        if ($ordemServico->tecnicos_ids && in_array($usuario->id, $ordemServico->tecnicos_ids)) {
            return true;
        }
        
        // Gestor estadual pode ver OSs estaduais
        if ($usuario->isEstadual() && $ordemServico->competencia === 'estadual') {
            return true;
        }
        
        // Gestor municipal pode ver OSs municipais do seu município
        if ($usuario->isMunicipal() && $ordemServico->competencia === 'municipal' && $ordemServico->municipio_id == $usuario->municipio_id) {
            return true;
        }
        
        return false;
    }

    /**
     * Atualiza APENAS o vínculo de estabelecimento da OS.
     * Exclusivo para TecnicoEstadual — todos os outros campos permanecem inalterados.
     */
    private function updateVincularEstabelecimento(Request $request, OrdemServico $ordemServico)
    {
        $request->validate([
            'estabelecimento_id'       => 'nullable|exists:estabelecimentos,id',
            'estabelecimentos_ids'     => 'nullable|array',
            'estabelecimentos_ids.*'   => 'exists:estabelecimentos,id',
        ]);

        $estabelecimentosIds     = $request->input('estabelecimentos_ids', []);
        $processosEstabelecimentos = $request->input('processos_estabelecimentos', []);

        if (!empty($estabelecimentosIds)) {
            // Primeiro estabelecimento como referência principal
            $ordemServico->estabelecimento_id = $estabelecimentosIds[0];

            // Busca processo ativo do primeiro estabelecimento
            $processo = \App\Models\Processo::where('estabelecimento_id', $estabelecimentosIds[0])
                ->whereIn('status', ['aberto', 'em_analise', 'pendente'])
                ->orderBy('created_at', 'desc')
                ->first();
            $ordemServico->processo_id = $processo?->id;
            $ordemServico->save();

            // Sincroniza pivot com processo por estabelecimento
            $syncData = [];
            foreach ($estabelecimentosIds as $estId) {
                $syncData[$estId] = [
                    'processo_id' => $processosEstabelecimentos[$estId] ?? null,
                ];
            }
            $ordemServico->estabelecimentos()->sync($syncData);
        } elseif ($request->filled('estabelecimento_id')) {
            $estId = $request->input('estabelecimento_id');
            $ordemServico->estabelecimento_id = $estId;

            $processo = \App\Models\Processo::where('estabelecimento_id', $estId)
                ->whereIn('status', ['aberto', 'em_analise', 'pendente'])
                ->orderBy('created_at', 'desc')
                ->first();
            $ordemServico->processo_id = $processo?->id;
            $ordemServico->save();

            $ordemServico->estabelecimentos()->sync([
                $estId => ['processo_id' => $processosEstabelecimentos[$estId] ?? $ordemServico->processo_id]
            ]);
        }

        if ($ordemServico->pasta_id) {
            $pastaValida = ProcessoPasta::where('id', $ordemServico->pasta_id)
                ->where('processo_id', $ordemServico->processo_id)
                ->exists();

            if (!$pastaValida) {
                $ordemServico->pasta_id = null;
                $ordemServico->save();
            }
        }
        // Se nenhum estabelecimento enviado, não altera nada

        return redirect()->route('admin.ordens-servico.show', $ordemServico)
            ->with('success', "Estabelecimento vinculado à Ordem de Serviço {$ordemServico->numero} com sucesso!");
    }

    /**
     * Verifica se usuário pode editar a OS.
     * Técnicos não podem editar.
     */
    private function podeEditarOS($usuario, $ordemServico)
    {
        if ($usuario->nivel_acesso === \App\Enums\NivelAcesso::TecnicoEstadual ||
            $usuario->nivel_acesso === \App\Enums\NivelAcesso::TecnicoMunicipal) {
            return false;
        }
        
        return $this->podeVisualizarOS($usuario, $ordemServico);
    }

    /**
     * Verifica se usuário pode excluir a OS
     * Apenas Administrador e Gestores podem excluir
     */
    private function podeExcluirOS($usuario, $ordemServico)
    {
        // Técnicos não podem excluir OS
        if ($usuario->nivel_acesso === \App\Enums\NivelAcesso::TecnicoEstadual ||
            $usuario->nivel_acesso === \App\Enums\NivelAcesso::TecnicoMunicipal) {
            return false;
        }
        
        // Admin pode excluir qualquer OS
        if ($usuario->nivel_acesso === \App\Enums\NivelAcesso::Administrador) {
            return true;
        }
        
        // Gestores podem excluir se tiverem acesso à OS
        return $this->podeVisualizarOS($usuario, $ordemServico);
    }

    /**
     * Retorna pesquisa interna pendente para o técnico na OS.
     */
    private function buscarPesquisaInternaPendente(OrdemServico $ordemServico, $usuario)
    {
        $tecnicoJaRespondeu = \App\Models\PesquisaSatisfacaoResposta::where('ordem_servico_id', $ordemServico->id)
            ->where('usuario_interno_id', $usuario->id)
            ->where('tipo_respondente', 'interno')
            ->exists();

        if ($tecnicoJaRespondeu) {
            return null;
        }

        $setorUsuario = mb_strtolower(trim($usuario->setor ?? ''));

        $pesquisasInternas = \App\Models\PesquisaSatisfacao::where('ativo', true)
            ->where('tipo_publico', 'interno')
            ->get();

        foreach ($pesquisasInternas as $pesquisaInterna) {
            $setoresIds = $pesquisaInterna->tipo_setores_ids;

            if (empty($setoresIds)) {
                return $pesquisaInterna;
            }

            if (!$setorUsuario) {
                continue;
            }

            $codigosSetores = \App\Models\TipoSetor::whereIn('id', $setoresIds)
                ->pluck('codigo')
                ->map(fn($s) => mb_strtolower(trim($s)))
                ->toArray();

            if (in_array($setorUsuario, $codigosSetores, true)) {
                return $pesquisaInterna;
            }
        }

        return null;
    }

    /**
     * API: Retorna processos de um estabelecimento
     */
    public function getProcessosPorEstabelecimento($estabelecimentoId)
    {
        $usuario = Auth::guard('interno')->user();
        
        // Valida se o estabelecimento existe
        $estabelecimento = Estabelecimento::find($estabelecimentoId);
        
        if (!$estabelecimento) {
            return response()->json(['error' => 'Estabelecimento não encontrado'], 404);
        }
        
        // Verifica permissão de acesso ao estabelecimento
        if ($usuario->isMunicipal() && $estabelecimento->municipio_id != $usuario->municipio_id) {
            return response()->json(['error' => 'Sem permissão para acessar este estabelecimento'], 403);
        }
        
        // Busca processos do estabelecimento (exceto arquivados)
        $processos = Processo::where('estabelecimento_id', $estabelecimentoId)
            ->where('status', '!=', 'arquivado')
            ->orderBy('numero_processo', 'desc')
            ->get(['id', 'numero_processo', 'tipo', 'status'])
            ->map(function($processo) {
                return [
                    'id' => $processo->id,
                    'numero_processo' => $processo->numero_processo,
                    'tipo' => $processo->tipo,
                    'tipo_label' => \App\Models\Processo::tipos()[$processo->tipo] ?? $processo->tipo,
                    'status' => $processo->status,
                ];
            });
        
        return response()->json([
            'success' => true,
            'processos' => $processos,
            'total' => $processos->count(),
            'permite_continuar_sem_processo' => $processos->isEmpty(),
            'mensagem_sem_processos' => 'Este estabelecimento não possui processos abertos. Deseja continuar sem vincular processo?'
        ]);
    }

    /**
     * API: Busca tipos de ação com autocomplete
     */
    public function searchTiposAcao(Request $request)
    {
        $usuario = Auth::guard('interno')->user();
        $search = $request->get('q', '');
        
        // Define competências permitidas
        $competenciaFiltro = ['ambos'];
        if ($usuario->isEstadual()) {
            $competenciaFiltro[] = 'estadual';
        } elseif ($usuario->isMunicipal()) {
            $competenciaFiltro[] = 'municipal';
        } else {
            // Admin vê todos
            $competenciaFiltro = ['estadual', 'municipal', 'ambos'];
        }
        
        // Busca tipos de ação
        $tiposAcao = TipoAcao::ativo()
            ->whereIn('competencia', $competenciaFiltro)
            ->when($search, function($query) use ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('descricao', 'ILIKE', "%{$search}%")
                      ->orWhere('codigo_procedimento', 'ILIKE', "%{$search}%");
                });
            })
            ->orderBy('descricao')
            ->limit(50)
            ->get(['id', 'descricao', 'codigo_procedimento']);
        
        // Formata para Select2
        $results = $tiposAcao->map(function($tipo) {
            return [
                'id' => $tipo->id,
                'text' => $tipo->descricao,
                'codigo' => $tipo->codigo_procedimento
            ];
        });
        
        return response()->json([
            'results' => $results,
            'pagination' => ['more' => false]
        ]);
    }

    /**
     * API: Busca técnicos com autocomplete
     */
    public function searchTecnicos(Request $request)
    {
        $usuario = Auth::guard('interno')->user();
        $search = $request->get('q', '');
        
        // Busca técnicos conforme competência
        $query = UsuarioInterno::where('ativo', true);
        
        if ($usuario->isEstadual()) {
            $query->where('nivel_acesso', 'estadual');
        } elseif ($usuario->isMunicipal()) {
            $query->where('nivel_acesso', 'municipal')
                  ->where('municipio_id', $usuario->municipio_id);
        }
        // Admin vê todos
        
        // Aplica filtro de busca
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nome', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }
        
        $tecnicos = $query->orderBy('nome')
            ->limit(50)
            ->get(['id', 'nome', 'email', 'nivel_acesso']);
        
        // Formata para Select2
        $results = $tecnicos->map(function($tecnico) {
            return [
                'id' => $tecnico->id,
                'text' => $tecnico->nome,
                'email' => $tecnico->email,
                'nivel' => $tecnico->nivel_acesso
            ];
        });
        
        return response()->json([
            'results' => $results,
            'pagination' => ['more' => false]
        ]);
    }

    /**
     * Finalizar ordem de serviço
     */
    public function finalizar(Request $request, OrdemServico $ordemServico)
    {
        $usuario = Auth::guard('interno')->user();
        
        // Verifica se o usuário é técnico atribuído à OS
        $isTecnico = $ordemServico->tecnicos_ids && in_array($usuario->id, $ordemServico->tecnicos_ids);
        
        if (!$isTecnico) {
            return response()->json([
                'message' => 'Você não tem permissão para finalizar esta ordem de serviço.'
            ], 403);
        }
        
        // Valida os dados
        $validated = $request->validate([
            'atividades_realizadas' => 'required|in:sim,parcial,nao',
            'observacoes_finalizacao' => 'required|string|min:20',
            'estabelecimento_id' => 'nullable|exists:estabelecimentos,id',
            'acoes_executadas_ids' => 'nullable|array',
            'acoes_executadas_ids.*' => 'exists:tipo_acoes,id',
        ], [
            'atividades_realizadas.required' => 'Informe se as atividades foram realizadas.',
            'observacoes_finalizacao.required' => 'As observações são obrigatórias.',
            'observacoes_finalizacao.min' => 'As observações devem ter no mínimo 20 caracteres.',
        ]);
        
        // Processa ações executadas conforme status
        $acoesExecutadasIds = [];
        
        if ($validated['atividades_realizadas'] === 'sim') {
            // Concluído com sucesso: todas as ações foram executadas
            $acoesExecutadasIds = $ordemServico->tipos_acao_ids;
        } elseif ($validated['atividades_realizadas'] === 'parcial') {
            // Concluído parcialmente: apenas as ações selecionadas
            $acoesExecutadasIds = $request->input('acoes_executadas_ids', []);
        } elseif ($validated['atividades_realizadas'] === 'nao') {
            // Não concluído: nenhuma ação foi executada
            $acoesExecutadasIds = [];
        }
        
        // Se estabelecimento foi informado, vincula e busca processo
        $dadosAtualizacao = [
            'status' => 'finalizada',
            'data_conclusao' => now(),
            'atividades_realizadas' => $validated['atividades_realizadas'],
            'observacoes_finalizacao' => $validated['observacoes_finalizacao'],
            'acoes_executadas_ids' => $acoesExecutadasIds,
            'finalizada_por' => $usuario->id,
            'finalizada_em' => now(),
        ];
        
        if (!empty($validated['estabelecimento_id'])) {
            $estabelecimento = Estabelecimento::findOrFail($validated['estabelecimento_id']);
            
            // Busca processo ativo do estabelecimento
            $processo = \App\Models\Processo::where('estabelecimento_id', $estabelecimento->id)
                ->whereIn('status', ['aberto', 'em_analise', 'pendente'])
                ->orderBy('created_at', 'desc')
                ->first();
            
            $dadosAtualizacao['estabelecimento_id'] = $estabelecimento->id;
            $dadosAtualizacao['processo_id'] = $processo ? $processo->id : null;
            
            // Atualiza competência e município baseado no estabelecimento
            if ($usuario->isEstadual()) {
                $dadosAtualizacao['competencia'] = 'estadual';
                $dadosAtualizacao['municipio_id'] = null;
            } elseif ($usuario->isMunicipal()) {
                $dadosAtualizacao['competencia'] = 'municipal';
                $dadosAtualizacao['municipio_id'] = $usuario->municipio_id;
            } elseif ($usuario->isAdmin()) {
                $dadosAtualizacao['competencia'] = $estabelecimento->competencia_manual ?? 'estadual';
                $dadosAtualizacao['municipio_id'] = $estabelecimento->municipio_id;
            }
        }
        
        // Atualiza a OS
        $ordemServico->update($dadosAtualizacao);
        
        // Cria notificação para gestores
        $this->criarNotificacaoFinalizacao($ordemServico, $usuario);
        
        return response()->json([
            'message' => 'Ordem de serviço finalizada com sucesso!',
            'ordem_servico' => $ordemServico
        ]);
    }

    /**
     * Criar notificação de finalização para gestores
     */
    private function criarNotificacaoFinalizacao(OrdemServico $ordemServico, $tecnico)
    {
        // Busca gestores que devem receber notificação
        $gestores = UsuarioInterno::where('ativo', true)
            ->where(function($query) use ($ordemServico) {
                if ($ordemServico->competencia === 'estadual') {
                    $query->where('nivel_acesso', 'estadual')
                          ->orWhere('nivel_acesso', 'administrador');
                } elseif ($ordemServico->competencia === 'municipal') {
                    $query->where(function($q) use ($ordemServico) {
                        $q->where('nivel_acesso', 'municipal')
                          ->where('municipio_id', $ordemServico->municipio_id);
                    })->orWhere('nivel_acesso', 'administrador');
                }
            })
            ->get();
        
        foreach ($gestores as $gestor) {
            // Monta mensagem com ou sem estabelecimento
            $estabelecimentoInfo = $ordemServico->estabelecimento 
                ? ' do estabelecimento ' . $ordemServico->estabelecimento->nome_fantasia 
                : ' (sem estabelecimento vinculado)';
            
            \App\Models\Notificacao::create([
                'usuario_interno_id' => $gestor->id,
                'tipo' => 'ordem_servico_finalizada',
                'titulo' => 'OS #' . $ordemServico->numero . ' Finalizada',
                'mensagem' => 'O técnico ' . $tecnico->nome . ' finalizou a OS #' . $ordemServico->numero . $estabelecimentoInfo,
                'link' => route('admin.ordens-servico.show', $ordemServico),
                'ordem_servico_id' => $ordemServico->id,
                'prioridade' => 'normal',
            ]);
        }
    }

    /**
     * Reiniciar ordem de serviço finalizada
     */
    public function reiniciar(OrdemServico $ordemServico)
    {
        $usuario = Auth::guard('interno')->user();
        
        // Apenas gestores podem reiniciar
        if (!$usuario->isAdmin() && !$usuario->isEstadual() && !$usuario->isMunicipal()) {
            return redirect()->route('admin.ordens-servico.show', $ordemServico)
                ->with('error', 'Você não tem permissão para reiniciar esta ordem de serviço.');
        }
        
        // Verifica se está finalizada
        if ($ordemServico->status !== 'finalizada') {
            return redirect()->route('admin.ordens-servico.show', $ordemServico)
                ->with('error', 'Apenas ordens de serviço finalizadas podem ser reiniciadas.');
        }
        
        // Reseta o status de todas as atividades para pendente
        $atividades = $ordemServico->atividades_tecnicos ?? [];
        foreach ($atividades as $index => $atividade) {
            $atividades[$index]['status'] = 'pendente';
            $atividades[$index]['status_execucao'] = null;
            $atividades[$index]['finalizada_por'] = null;
            $atividades[$index]['finalizada_em'] = null;
            $atividades[$index]['observacoes_finalizacao'] = null;
        }
        
        // Reinicia a OS
        $ordemServico->update([
            'status' => 'em_andamento',
            'atividades_realizadas' => null,
            'observacoes_finalizacao' => null,
            'acoes_executadas_ids' => [],
            'finalizada_por' => null,
            'finalizada_em' => null,
            'data_conclusao' => null,
            'atividades_tecnicos' => $atividades, // Reseta as atividades
        ]);
        
        // Cria notificação para os técnicos
        foreach ($ordemServico->tecnicos_ids ?? [] as $tecnicoId) {
            \App\Models\Notificacao::create([
                'usuario_interno_id' => $tecnicoId,
                'tipo' => 'ordem_servico_reiniciada',
                'titulo' => 'OS #' . $ordemServico->numero . ' Reiniciada',
                'mensagem' => 'A OS #' . $ordemServico->numero . ' foi reiniciada por ' . $usuario->nome . '. Todas as atividades voltaram ao status "Pendente".',
                'link' => route('admin.ordens-servico.show', $ordemServico),
                'ordem_servico_id' => $ordemServico->id,
                'prioridade' => 'alta',
            ]);
        }
        
        return redirect()->route('admin.ordens-servico.show', $ordemServico)
            ->with('success', 'Ordem de Serviço reiniciada com sucesso! Todas as atividades voltaram ao status "Pendente".');
    }

    /**
     * Reiniciar uma atividade individual (apenas gestores)
     */
    public function reiniciarAtividade(Request $request, OrdemServico $ordemServico)
    {
        $usuario = Auth::guard('interno')->user();
        
        // Apenas gestores podem reiniciar atividades
        if (!$usuario->isAdmin() && !$usuario->isGestor()) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para reiniciar atividades.'
            ], 403);
        }
        
        $validated = $request->validate([
            'atividade_index' => 'required|integer|min:0',
        ]);
        
        $atividadeIndex = $validated['atividade_index'];
        $atividades = $ordemServico->atividades_tecnicos ?? [];
        
        // Verifica se o índice é válido
        if (!isset($atividades[$atividadeIndex])) {
            return response()->json([
                'success' => false,
                'message' => 'Atividade não encontrada.'
            ], 404);
        }
        
        $atividade = $atividades[$atividadeIndex];
        
        // Verifica se a atividade está finalizada
        if (($atividade['status'] ?? 'pendente') !== 'finalizada') {
            return response()->json([
                'success' => false,
                'message' => 'Esta atividade já está pendente.'
            ], 400);
        }
        
        // Reseta a atividade para pendente
        $atividades[$atividadeIndex]['status'] = 'pendente';
        $atividades[$atividadeIndex]['status_execucao'] = null;
        $atividades[$atividadeIndex]['finalizada_por'] = null;
        $atividades[$atividadeIndex]['finalizada_em'] = null;
        $atividades[$atividadeIndex]['observacoes_finalizacao'] = null;
        
        // Se a OS estava finalizada, volta para em_andamento
        $dadosOS = ['atividades_tecnicos' => $atividades];
        if ($ordemServico->status === 'finalizada') {
            $dadosOS['status'] = 'em_andamento';
            $dadosOS['atividades_realizadas'] = null;
            $dadosOS['observacoes_finalizacao'] = null;
            $dadosOS['acoes_executadas_ids'] = [];
            $dadosOS['finalizada_por'] = null;
            $dadosOS['finalizada_em'] = null;
            $dadosOS['data_conclusao'] = null;
        }
        
        $ordemServico->update($dadosOS);
        
        $nomeAtividade = $atividade['nome_atividade'] ?? 'Atividade';
        
        // Notifica os técnicos da atividade
        $tecnicosAtividade = $atividade['tecnicos'] ?? [];
        foreach ($tecnicosAtividade as $tecnicoId) {
            \App\Models\Notificacao::create([
                'usuario_interno_id' => $tecnicoId,
                'tipo' => 'atividade_reiniciada',
                'titulo' => 'Atividade Reiniciada - OS #' . $ordemServico->numero,
                'mensagem' => 'A atividade "' . $nomeAtividade . '" da OS #' . $ordemServico->numero . ' foi reiniciada por ' . $usuario->nome . '. A atividade voltou ao status "Pendente".',
                'link' => route('admin.ordens-servico.show', $ordemServico),
                'ordem_servico_id' => $ordemServico->id,
                'prioridade' => 'alta',
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Atividade "' . $nomeAtividade . '" reiniciada com sucesso!',
            'os_reaberta' => $ordemServico->status === 'em_andamento' && $ordemServico->getOriginal('status') === 'finalizada',
        ]);
    }

    /**
     * Buscar estabelecimentos com autocomplete (AJAX)
     */
    public function buscarEstabelecimentos(Request $request)
    {
        $usuario = Auth::guard('interno')->user();
        $termo = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = 20;
        
        // Query base
        $query = Estabelecimento::query();
        
        // Filtro por município para usuário municipal
        if ($usuario->isMunicipal() && $usuario->municipio_id) {
            $query->where('municipio_id', $usuario->municipio_id);
        }
        // Admin e Estadual veem todos inicialmente (filtro de competência será aplicado depois)
        
        // Busca por CNPJ/CPF, Nome Fantasia ou Razão Social
        if (!empty($termo)) {
            // Remove formatação do termo (pontos, hífen, barra) para buscar apenas números
            $termoNumeros = preg_replace('/[^0-9]/', '', $termo);
            
            $query->where(function($q) use ($termo, $termoNumeros) {
                // Busca por CNPJ/CPF (com ou sem formatação)
                if (!empty($termoNumeros)) {
                    $q->where('cnpj', 'ILIKE', "%{$termoNumeros}%")
                      ->orWhere('cpf', 'ILIKE', "%{$termoNumeros}%");
                }
                // Busca por nome
                $q->orWhere('nome_fantasia', 'ILIKE', "%{$termo}%")
                  ->orWhere('razao_social', 'ILIKE', "%{$termo}%")
                  ->orWhere('nome_completo', 'ILIKE', "%{$termo}%");
            });
        }
        
        // Busca todos os resultados para filtrar por competência
        $estabelecimentosQuery = $query->orderBy('nome_fantasia')->get();
        
        // Filtra por competência baseado no tipo de usuário
        if ($usuario->isEstadual()) {
            // Estadual vê apenas estabelecimentos de competência estadual
            $estabelecimentosQuery = $estabelecimentosQuery->filter(function($est) {
                return $est->isCompetenciaEstadual();
            });
        } elseif ($usuario->isMunicipal()) {
            // Municipal vê apenas estabelecimentos de competência municipal
            $estabelecimentosQuery = $estabelecimentosQuery->filter(function($est) {
                return $est->isCompetenciaMunicipal();
            });
        }
        // Admin vê todos
        
        // Paginação manual após filtro
        $total = $estabelecimentosQuery->count();
        $estabelecimentos = $estabelecimentosQuery->slice(($page - 1) * $perPage, $perPage)->values();
        
        // Formata resultados para Select2
        $results = $estabelecimentos->map(function($estabelecimento) {
            // Pessoa Jurídica (CNPJ)
            if (!empty($estabelecimento->cnpj)) {
                $documento = $estabelecimento->cnpj;
                $nome = $estabelecimento->nome_fantasia . ' - ' . $estabelecimento->razao_social;
            } 
            // Pessoa Física (CPF)
            else {
                $documento = $estabelecimento->cpf ?? 'Sem documento';
                $nome = $estabelecimento->nome_completo ?? $estabelecimento->razao_social;
            }
            
            return [
                'id' => $estabelecimento->id,
                'text' => $documento . ' - ' . $nome
            ];
        });
        
        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ]
        ]);
    }

    /**
     * Buscar processos ativos de um estabelecimento
     */
    public function getProcessosEstabelecimento($estabelecimentoId, Request $request = null)
    {
        try {
            // Pega o processo atual da OS (se estiver editando)
            $processoAtualId = request()->query('processo_atual_id');

            $processos = $this->buscarProcessosDisponiveisEstabelecimento($estabelecimentoId, $processoAtualId)
                ->map(function ($processo) {
                    return [
                        'id' => $processo->id,
                        'numero' => $processo->numero_processo ?? "Processo #{$processo->id}",
                        'tipo' => $processo->tipo ?? 'Não informado',
                        'texto_completo' => ($processo->numero_processo ?? "Processo #{$processo->id}") . ' - ' . ($processo->tipo ?? 'Não informado'),
                        'status' => $processo->status,
                        'status_label' => $this->getStatusProcessoLabel($processo->status),
                        'data_abertura' => $processo->created_at ? $processo->created_at->format('d/m/Y') : '-',
                    ];
                })
                ->values();

            return response()->json([
                'processos' => $processos,
                'total' => $processos->count(),
                'permite_continuar_sem_processo' => $processos->isEmpty(),
                'mensagem_sem_processos' => 'Este estabelecimento não possui processos abertos. Deseja continuar sem vincular processo?'
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao buscar processos do estabelecimento: ' . $e->getMessage());
            return response()->json([
                'processos' => [],
                'total' => 0,
                'permite_continuar_sem_processo' => true,
                'error' => 'Erro ao buscar processos'
            ], 500);
        }
    }

    private function validarSelecaoProcessosPorEstabelecimento(array $estabelecimentosIds, array $processosEstabelecimentos, array $continuarSemProcesso): void
    {
        if (empty($estabelecimentosIds)) {
            return;
        }

        $errors = [];

        foreach ($estabelecimentosIds as $estabelecimentoId) {
            $processoSelecionado = $processosEstabelecimentos[$estabelecimentoId] ?? null;
            $confirmouContinuarSemProcesso = (string) ($continuarSemProcesso[$estabelecimentoId] ?? '0') === '1';
            $temProcessosDisponiveis = $this->buscarProcessosDisponiveisEstabelecimento((int) $estabelecimentoId)->isNotEmpty();

            if ($temProcessosDisponiveis && empty($processoSelecionado)) {
                $errors["processos_estabelecimentos.$estabelecimentoId"] = 'Selecione um processo para este estabelecimento.';
                continue;
            }

            if (!$temProcessosDisponiveis && !$confirmouContinuarSemProcesso) {
                $errors["continuar_sem_processo_estabelecimentos.$estabelecimentoId"] = 'Confirme que deseja continuar sem processo para este estabelecimento.';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function buscarProcessosDisponiveisEstabelecimento(int $estabelecimentoId, $processoAtualId = null)
    {
        return Processo::where('estabelecimento_id', $estabelecimentoId)
            ->where(function ($query) use ($processoAtualId) {
                $query->whereIn('status', ['aberto', 'em_analise', 'pendente']);

                if ($processoAtualId) {
                    $query->orWhere('id', $processoAtualId);
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Processo ao qual a OS deve ser vinculada automaticamente.
     *
     * Prefere um processo em andamento. Se o estabelecimento so tiver processos parados
     * ou arquivados, usa o mais recente em vez de deixar a OS sem processo: uma OS com
     * processo_id nulo nao aparece na tela de processo nenhuma, junto com os documentos
     * gerados nela.
     */
    private function resolverProcessoParaVinculoOS(int $estabelecimentoId): ?int
    {
        $emAndamento = $this->buscarProcessosDisponiveisEstabelecimento($estabelecimentoId)->first();

        if ($emAndamento) {
            return (int) $emAndamento->id;
        }

        $maisRecente = Processo::where('estabelecimento_id', $estabelecimentoId)
            ->orderByDesc('created_at')
            ->first();

        return $maisRecente ? (int) $maisRecente->id : null;
    }

    private function getStatusProcessoLabel(?string $status): string
    {
        return [
            'aberto' => 'Aberto',
            'em_analise' => 'Em Análise',
            'pendente' => 'Pendente',
            'deferido' => 'Deferido',
            'indeferido' => 'Indeferido',
            'arquivado' => 'Arquivado',
        ][$status] ?? ucfirst((string) $status);
    }

    private function resolverPastaIdValida(?int $pastaId, ?int $processoId): ?int
    {
        if (!$pastaId) {
            return null;
        }

        if (!$processoId) {
            throw ValidationException::withMessages([
                'pasta_id' => 'Selecione um processo antes de escolher a pasta da OS.',
            ]);
        }

        $pastaValida = ProcessoPasta::where('id', $pastaId)
            ->where('processo_id', $processoId)
            ->exists();

        if (!$pastaValida) {
            throw ValidationException::withMessages([
                'pasta_id' => 'A pasta selecionada não pertence ao processo informado.',
            ]);
        }

        return $pastaId;
    }

    /**
     * Exibe a página de finalização de atividade
     */
    public function showFinalizarAtividade(OrdemServico $ordemServico, int $atividadeIndex)
    {
        $usuario = Auth::guard('interno')->user();

        if (!$this->podeVisualizarOS($usuario, $ordemServico)) {
            abort(403, 'Você não tem permissão para visualizar esta ordem de serviço.');
        }

        if ($ordemServico->status !== 'em_andamento') {
            return redirect()->route('admin.ordens-servico.show', $ordemServico)
                ->with('error', 'Esta OS não está em andamento.');
        }

        $atividades = $ordemServico->atividades_tecnicos ?? [];
        if (!isset($atividades[$atividadeIndex])) {
            return redirect()->route('admin.ordens-servico.show', $ordemServico)
                ->with('error', 'Atividade não encontrada.');
        }

        $atividade = $atividades[$atividadeIndex];

        // Verifica se a atividade já foi finalizada
        if (($atividade['status'] ?? 'pendente') === 'finalizada') {
            return redirect()->route('admin.ordens-servico.show', $ordemServico)
                ->with('error', 'Esta atividade já foi finalizada.');
        }

        // Verifica se o técnico está atribuído à atividade
        $tecnicosAtividade = $atividade['tecnicos'] ?? [];
        if (!in_array($usuario->id, $tecnicosAtividade)) {
            return redirect()->route('admin.ordens-servico.show', $ordemServico)
                ->with('error', 'Você não está atribuído a esta atividade.');
        }

        // Identifica se o técnico é o responsável (usado na view para controlar permissões)
        $responsavelId = $atividade['responsavel_id'] ?? null;
        $isResponsavelAtividade = !$responsavelId || $usuario->id === $responsavelId || count($tecnicosAtividade) <= 1;

        $ordemServico->load(['estabelecimento.municipio', 'estabelecimentos.municipio', 'municipio', 'processo', 'documentosDigitais.tipoDocumento', 'documentosDigitais.assinaturas', 'documentosDigitais.usuarioCriador']);

        // Estabelecimentos da atividade
        $todosEstabelecimentosOs = $ordemServico->getTodosEstabelecimentos();
        $atividadeEstabelecimentoId = $atividade['estabelecimento_id'] ?? null;

        if (!empty($atividadeEstabelecimentoId)) {
            $estabelecimentosAtividade = $todosEstabelecimentosOs->where('id', (int) $atividadeEstabelecimentoId)->values();
        } else {
            $estabelecimentosAtividade = $todosEstabelecimentosOs->values();
        }

        $isMultiEstabelecimento = $estabelecimentosAtividade->count() > 1;

        // Processos vinculados à atividade atual. Quando a atividade possui um
        // estabelecimento específico, documentos e uploads devem ir somente para
        // o processo daquele estabelecimento, não para todos os processos da OS.
        $processosVinculadosOs = $this->obterProcessosVinculadosOs($ordemServico, $estabelecimentosAtividade);

        $documentosOs = $ordemServico->documentosDigitais
            ->where('atividade_index', $atividadeIndex)
            ->sortByDesc('created_at')
            ->values();
        $arquivosExternosOs = $ordemServico->arquivosExternos()
            ->with(['usuario', 'ordemServico', 'processo.estabelecimento'])
            ->where('atividade_index', $atividadeIndex)
            ->when($processosVinculadosOs->isNotEmpty(), function ($query) use ($processosVinculadosOs) {
                $query->whereIn('processo_id', $processosVinculadosOs->all());
            })
            ->orderBy('created_at', 'desc')
            ->get();
        $documentosOsComAssinatura = $documentosOs->filter(function ($documento) {
            return $documento->assinaturas->contains(function ($assinatura) {
                return $assinatura->status === 'assinado';
            });
        });
        $documentosOsAssinadosCompletos = $documentosOs->filter(function ($documento) {
            return $documento->status === 'assinado' && $documento->todasAssinaturasCompletas();
        });
        $documentosOsPendentesAssinatura = $documentosOs->filter(function ($documento) {
            return $documento->status !== 'assinado' || !$documento->todasAssinaturasCompletas();
        });
        $documentosOsEditaveis = $documentosOs->filter(function ($documento) {
            if ($documento->status === 'rascunho') {
                return true;
            }

            if ($documento->status !== 'aguardando_assinatura') {
                return false;
            }

            return !$documento->assinaturas->contains(function ($assinatura) {
                return $assinatura->status === 'assinado';
            });
        });

        // Tecnicos
        $tecnicos = \App\Models\UsuarioInterno::whereIn('id', $tecnicosAtividade)->get();
        $responsavel = $responsavelId ? \App\Models\UsuarioInterno::find($responsavelId) : null;

        // Pesquisa interna pendente — só para ação principal (sem sub_acao_id)
        $isSubAcao = !empty($atividade['sub_acao_id']);
        $pesquisaInterna = null;
        if (!$isSubAcao) {
            $pesquisaInterna = $this->buscarPesquisaInternaPendente($ordemServico, $usuario);
            if ($pesquisaInterna) {
                $pesquisaInterna->load('perguntas.opcoes');
            }
        }

        // Estabelecimentos como array para JS
        $estabelecimentosJs = $estabelecimentosAtividade->map(function ($est) use ($ordemServico) {
            $processoId = $est->pivot->processo_id ?? null;
            if (!$processoId && $ordemServico->estabelecimento_id == $est->id) {
                $processoId = $ordemServico->processo_id;
            }

            return [
                'id' => (int) $est->id,
                'nome' => $est->nome_fantasia ?? $est->nome_razao_social,
                'cnpj' => $est->cnpj_formatado ?? $est->cnpj ?? $est->cpf_cnpj ?? null,
                'processo_id' => $processoId ? (int) $processoId : null,
            ];
        })->values()->all();

        // Busca info dos processos vinculados para exibir na sidebar
        $processosInfo = $processosVinculadosOs->isNotEmpty()
            ? \App\Models\Processo::with('estabelecimento')
                ->whereIn('id', $processosVinculadosOs)
                ->get()
            : collect();

        return view('ordens-servico.finalizar-atividade', compact(
            'ordemServico',
            'atividade',
            'atividadeIndex',
            'estabelecimentosAtividade',
            'isMultiEstabelecimento',
            'processosVinculadosOs',
            'documentosOs',
            'arquivosExternosOs',
            'documentosOsComAssinatura',
            'documentosOsAssinadosCompletos',
            'documentosOsPendentesAssinatura',
            'documentosOsEditaveis',
            'tecnicos',
            'responsavel',
            'pesquisaInterna',
            'estabelecimentosJs',
            'processosInfo',
            'isResponsavelAtividade'
        ));
    }

    /**
     * Finalizar uma atividade específica do técnico
     * A OS só será finalizada quando TODAS as atividades forem concluídas
     */
    public function finalizarAtividade(Request $request, OrdemServico $ordemServico)
    {
        $usuario = Auth::guard('interno')->user();
        
        // Valida os dados
        $validated = $request->validate([
            'atividade_index' => 'required|integer|min:0',
            'status_execucao' => 'nullable|in:concluido,parcial,nao_concluido',
            'observacoes' => 'nullable|string|max:1000',
            'estabelecimento_id' => 'nullable|exists:estabelecimentos,id',
            'execucao_estabelecimentos' => 'nullable|array',
            'execucao_estabelecimentos.*.estabelecimento_id' => 'required|integer|exists:estabelecimentos,id',
            'execucao_estabelecimentos.*.executada' => 'required|boolean',
            'execucao_estabelecimentos.*.justificativa' => 'nullable|string|max:1000',
        ], [
            'atividade_index.required' => 'Índice da atividade é obrigatório.',
            'status_execucao.required' => 'Selecione o status da execução.',
            'status_execucao.in' => 'Status de execução inválido.',
            'observacoes.required' => 'Informe as observações da atividade.',
            'observacoes.min' => 'As observações devem ter no mínimo 10 caracteres.',
        ]);
        
        $atividadeIndex = $validated['atividade_index'];
        $atividades = $ordemServico->atividades_tecnicos ?? [];
        
        // Verifica se o índice é válido
        if (!isset($atividades[$atividadeIndex])) {
            return response()->json([
                'success' => false,
                'message' => 'Atividade não encontrada.'
            ], 404);
        }
        
        $atividade = $atividades[$atividadeIndex];
        
        // Verifica se o técnico está atribuído a esta atividade
        $tecnicosAtividade = $atividade['tecnicos'] ?? [];
        if (!in_array($usuario->id, $tecnicosAtividade)) {
            return response()->json([
                'success' => false,
                'message' => 'Você não está atribuído a esta atividade.'
            ], 403);
        }

        // Se houver mais de um técnico e existir responsável, só o responsável pode finalizar
        $responsavelId = $atividade['responsavel_id'] ?? null;
        if (count($tecnicosAtividade) > 1 && $responsavelId && $usuario->id !== $responsavelId) {
            return response()->json([
                'success' => false,
                'message' => 'Somente o técnico responsável pode finalizar esta atividade.'
            ], 403);
        }
        
        // Verifica se a atividade já foi finalizada
        if (($atividade['status'] ?? 'pendente') === 'finalizada') {
            return response()->json([
                'success' => false,
                'message' => 'Esta atividade já foi finalizada.'
            ], 400);
        }

        // Se é uma atividade principal (sem sub_acao_id), verifica se todas as subatividades estão finalizadas
        $isSubAcao = !empty($atividade['sub_acao_id']);
        if (!$isSubAcao) {
            $tipoAcaoIdPrincipal = $atividade['tipo_acao_id'] ?? null;
            if ($tipoAcaoIdPrincipal) {
                $subatividadesPendentes = [];
                foreach ($atividades as $idx => $atv) {
                    // Subatividade = tem sub_acao_id E mesmo tipo_acao_id da principal
                    if (!empty($atv['sub_acao_id']) && ($atv['tipo_acao_id'] ?? null) == $tipoAcaoIdPrincipal && $idx != $atividadeIndex) {
                        if (($atv['status'] ?? 'pendente') !== 'finalizada') {
                            $subatividadesPendentes[] = $atv['nome_atividade'] ?? 'Subatividade';
                        }
                    }
                }
                if (!empty($subatividadesPendentes)) {
                    $nomes = implode(', ', $subatividadesPendentes);
                    return response()->json([
                        'success' => false,
                        'message' => 'Esta atividade possui subatividades pendentes que precisam ser finalizadas primeiro: ' . $nomes
                    ], 422);
                }
            }
        }

        // Pesquisa interna: só exige para ação principal (não subação) E quando atividade foi executada
        $atividadeFoiExecutada = true;

        // Verifica se pelo menos um estabelecimento teve a atividade executada
        $execucaoEstabelecimentos = $request->input('execucao_estabelecimentos', []);
        if (!empty($execucaoEstabelecimentos)) {
            $atividadeFoiExecutada = collect($execucaoEstabelecimentos)->contains(fn($e) => !empty($e['executada']));
        } elseif ($request->has('status_execucao') && $request->status_execucao === 'nao_concluido') {
            $atividadeFoiExecutada = false;
        }

        if (!$isSubAcao && $atividadeFoiExecutada) {
            $pesquisaInternaPendente = $this->buscarPesquisaInternaPendente($ordemServico, $usuario);
            if ($pesquisaInternaPendente) {
                return response()->json([
                    'success' => false,
                    'survey_required' => true,
                    'message' => 'Para finalizar a atividade, é obrigatório responder a pesquisa de satisfação.'
                ], 422);
            }
        }

        $documentosOs = $ordemServico->documentosDigitais()
            ->where('atividade_index', $atividadeIndex)
            ->with('assinaturas')
            ->get();

        if ($documentosOs->isNotEmpty()) {
            $documentosPendentesAssinatura = $documentosOs->filter(function ($documento) {
                return $documento->status !== 'assinado' || !$documento->todasAssinaturasCompletas();
            });

            if ($documentosPendentesAssinatura->isNotEmpty()) {
                $quantidadePendentes = $documentosPendentesAssinatura->count();

                return response()->json([
                    'success' => false,
                    'message' => $quantidadePendentes === 1
                        ? 'A atividade só pode ser finalizada quando o documento vinculado à OS estiver com todas as assinaturas concluídas.'
                        : 'A atividade só pode ser finalizada quando todos os documentos vinculados à OS estiverem com todas as assinaturas concluídas.'
                ], 422);
            }
        }

        // Determina os estabelecimentos afetados pela atividade
        $todosEstabelecimentosOs = $ordemServico->getTodosEstabelecimentos();
        $atividadeEstabelecimentoId = $atividade['estabelecimento_id'] ?? null;

        if (!empty($atividadeEstabelecimentoId)) {
            $estabelecimentosAtividade = $todosEstabelecimentosOs->where('id', (int) $atividadeEstabelecimentoId)->values();
        } else {
            $estabelecimentosAtividade = $todosEstabelecimentosOs->values();
        }

        // Controle de execução por estabelecimento
        $execucaoEstabelecimentos = [];
        $statusExecucaoFinal = $validated['status_execucao'] ?? null;
        $observacoesFinalizacao = trim((string) ($validated['observacoes'] ?? ''));

        if ($estabelecimentosAtividade->count() > 1) {
            $execucaoPayload = collect($validated['execucao_estabelecimentos'] ?? []);

            if ($execucaoPayload->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Informe a execução da atividade para cada estabelecimento.'
                ], 422);
            }

            $idsEsperados = $estabelecimentosAtividade->pluck('id')->map(fn($id) => (int) $id)->sort()->values();
            $idsRecebidos = $execucaoPayload->pluck('estabelecimento_id')->map(fn($id) => (int) $id)->sort()->values();

            if ($idsEsperados->toJson() !== $idsRecebidos->toJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'É obrigatório informar a execução para todos os estabelecimentos vinculados à atividade.'
                ], 422);
            }

            foreach ($execucaoPayload as $item) {
                $estId = (int) ($item['estabelecimento_id'] ?? 0);
                $executada = filter_var($item['executada'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $executada = $executada === null ? false : $executada;
                $justificativa = trim((string) ($item['justificativa'] ?? ''));

                if (!$executada && mb_strlen($justificativa) < 10) {
                    $nomeEst = optional($estabelecimentosAtividade->firstWhere('id', $estId))->nome_fantasia
                        ?? optional($estabelecimentosAtividade->firstWhere('id', $estId))->nome_razao_social
                        ?? "ID {$estId}";

                    return response()->json([
                        'success' => false,
                        'message' => "Informe justificativa (mínimo 10 caracteres) para o estabelecimento {$nomeEst}."
                    ], 422);
                }

                $est = $estabelecimentosAtividade->firstWhere('id', $estId);
                $execucaoEstabelecimentos[] = [
                    'estabelecimento_id' => $estId,
                    'estabelecimento_nome' => $est?->nome_fantasia ?? $est?->nome_razao_social,
                    'executada' => $executada,
                    'justificativa' => $executada ? null : $justificativa,
                ];
            }

            $executadasCount = collect($execucaoEstabelecimentos)->where('executada', true)->count();
            if ($executadasCount === 0) {
                $statusExecucaoFinal = 'nao_concluido';
            } elseif ($executadasCount === count($execucaoEstabelecimentos)) {
                $statusExecucaoFinal = 'concluido';
            } else {
                $statusExecucaoFinal = 'parcial';
            }

            // Observação geral opcional para múltiplos estabelecimentos
            if (empty($observacoesFinalizacao)) {
                $observacoesFinalizacao = 'Finalização por estabelecimento registrada.';
            }
        } elseif ($estabelecimentosAtividade->count() === 1) {
            // Para atividade de estabelecimento único, mantém regra antiga
            if (empty($statusExecucaoFinal)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selecione o status da execução.'
                ], 422);
            }

            if (mb_strlen($observacoesFinalizacao) < 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'Informe as observações da atividade (mínimo 10 caracteres).'
                ], 422);
            }

            $est = $estabelecimentosAtividade->first();
            $execucaoEstabelecimentos[] = [
                'estabelecimento_id' => $est->id,
                'estabelecimento_nome' => $est->nome_fantasia ?? $est->nome_razao_social,
                'executada' => $statusExecucaoFinal !== 'nao_concluido',
                'justificativa' => $statusExecucaoFinal === 'nao_concluido' ? $observacoesFinalizacao : null,
            ];
        } else {
            if (empty($statusExecucaoFinal)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selecione o status da execução.'
                ], 422);
            }

            if (mb_strlen($observacoesFinalizacao) < 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'Informe as observações da atividade (mínimo 10 caracteres).'
                ], 422);
            }
        }
        
        // Atualiza o status da atividade
        $atividades[$atividadeIndex]['status'] = 'finalizada';
        $atividades[$atividadeIndex]['status_execucao'] = $statusExecucaoFinal;
        $atividades[$atividadeIndex]['finalizada_por'] = $usuario->id;
        $atividades[$atividadeIndex]['finalizada_em'] = now()->toISOString();
        $atividades[$atividadeIndex]['observacoes_finalizacao'] = $observacoesFinalizacao;
        if (!empty($execucaoEstabelecimentos)) {
            $atividades[$atividadeIndex]['execucao_estabelecimentos'] = $execucaoEstabelecimentos;
        }

        // Registra informação sobre documentos da atividade. Quando a atividade
        // possui estabelecimento específico, considera somente o processo dele.
        $processosVinculadosOs = $this->obterProcessosVinculadosOs($ordemServico, $estabelecimentosAtividade);

        $quantidadeDocumentosDigitaisOs = $ordemServico->documentosDigitais()
            ->where('atividade_index', $atividadeIndex)
            ->count();

        $quantidadeArquivosExternosOs = $ordemServico->arquivosExternos()
            ->where('atividade_index', $atividadeIndex)
            ->when($processosVinculadosOs->isNotEmpty(), function ($query) use ($processosVinculadosOs) {
                $query->whereIn('processo_id', $processosVinculadosOs->all());
            })
            ->count();

        $quantidadeTotalDocumentosOs = $quantidadeDocumentosDigitaisOs + $quantidadeArquivosExternosOs;

        if ($quantidadeTotalDocumentosOs > 0) {
            $atividades[$atividadeIndex]['tem_documentos_os'] = true;
            $atividades[$atividadeIndex]['qtd_documentos_os'] = $quantidadeTotalDocumentosOs;
            $atividades[$atividadeIndex]['qtd_arquivos_externos_os'] = $quantidadeArquivosExternosOs;
        } elseif ($request->boolean('confirmou_sem_documentos')) {
            $atividades[$atividadeIndex]['confirmou_sem_documentos'] = true;
            $atividades[$atividadeIndex]['confirmou_sem_documentos_por'] = $usuario->id;
            $atividades[$atividadeIndex]['confirmou_sem_documentos_nome'] = $usuario->nome;
            $atividades[$atividadeIndex]['confirmou_sem_documentos_em'] = now()->toISOString();
        }
        
        // Dados para atualizar na OS
        $dadosOS = ['atividades_tecnicos' => $atividades];
        
        // Se foi informado um estabelecimento e a OS não tem, vincula
        if (!empty($validated['estabelecimento_id']) && !$ordemServico->estabelecimento_id) {
            $dadosOS['estabelecimento_id'] = $validated['estabelecimento_id'];
        }
        
        // Salva as atividades atualizadas
        $ordemServico->update($dadosOS);
        
        // Verifica se TODAS as atividades foram finalizadas
        $todasFinalizadas = true;
        foreach ($atividades as $atv) {
            if (($atv['status'] ?? 'pendente') !== 'finalizada') {
                $todasFinalizadas = false;
                break;
            }
        }
        
        // Se todas as atividades foram finalizadas, finaliza a OS automaticamente
        if ($todasFinalizadas) {
            // Determina o status geral baseado nas atividades
            $statusGeral = 'sim';
            $acoesExecutadasIds = [];
            foreach ($atividades as $atv) {
                $tipoAcaoId = $atv['tipo_acao_id'] ?? null;
                $statusExecucaoAtividade = $atv['status_execucao'] ?? 'concluido';

                if ($tipoAcaoId && $statusExecucaoAtividade !== 'nao_concluido') {
                    $acoesExecutadasIds[] = (int) $tipoAcaoId;
                }

                if (($atv['status_execucao'] ?? 'concluido') === 'nao_concluido') {
                    $statusGeral = 'nao';
                    break;
                } elseif (($atv['status_execucao'] ?? 'concluido') === 'parcial') {
                    $statusGeral = 'parcial';
                }
            }

            $acoesExecutadasIds = array_values(array_unique($acoesExecutadasIds));
            
            $ordemServico->update([
                'status' => 'finalizada',
                'data_conclusao' => now(),
                'finalizada_em' => now(),
                'atividades_realizadas' => $statusGeral,
                'acoes_executadas_ids' => $acoesExecutadasIds,
                'observacoes_finalizacao' => 'Ordem de Serviço finalizada automaticamente após conclusão de todas as atividades.',
            ]);
            
            // Cria notificação para gestores
            $this->criarNotificacaoFinalizacao($ordemServico, $usuario);
            
            return response()->json([
                'success' => true,
                'message' => 'Atividade finalizada! A Ordem de Serviço foi encerrada automaticamente pois todas as atividades foram concluídas.',
                'os_finalizada' => true,
                'atividade_nome' => $atividade['nome_atividade'] ?? 'Atividade'
            ]);
        }
        
        // Conta quantas atividades ainda estão pendentes
        $pendentes = 0;
        foreach ($atividades as $atv) {
            if (($atv['status'] ?? 'pendente') !== 'finalizada') {
                $pendentes++;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Atividade finalizada com sucesso!',
            'os_finalizada' => false,
            'atividade_nome' => $atividade['nome_atividade'] ?? 'Atividade',
            'atividades_pendentes' => $pendentes
        ]);
    }

    public function uploadArquivoExternoAtividade(Request $request, OrdemServico $ordemServico)
    {
        $usuario = Auth::guard('interno')->user();

        $validated = $request->validate([
            'atividade_index' => 'required|integer|min:0',
            'processo_id' => 'nullable|integer|exists:processos,id',
            'tipo_documento' => 'required|string|max:255',
            'arquivo' => 'required|file|mimes:pdf|max:10240',
        ], [
            'tipo_documento.required' => 'Selecione o tipo de documento.',
            'arquivo.required' => 'Selecione um arquivo para upload.',
            'arquivo.mimes' => 'Apenas arquivos PDF são permitidos.',
            'arquivo.max' => 'O arquivo não pode ser maior que 10MB.',
        ]);

        $atividadeIndex = (int) $validated['atividade_index'];
        $atividades = $ordemServico->atividades_tecnicos ?? [];

        if (!isset($atividades[$atividadeIndex])) {
            return redirect()
                ->route('admin.ordens-servico.show', $ordemServico)
                ->with('error', 'Atividade não encontrada para vincular o arquivo.');
        }

        $atividade = $atividades[$atividadeIndex];
        $tecnicosAtividade = $atividade['tecnicos'] ?? [];
        $responsavelId = $atividade['responsavel_id'] ?? null;

        if (!$this->podeVisualizarOS($usuario, $ordemServico) || !in_array($usuario->id, $tecnicosAtividade)) {
            return redirect()
                ->route('admin.ordens-servico.show', $ordemServico)
                ->with('error', 'Você não tem permissão para vincular arquivos nesta atividade.');
        }

        $todosEstabelecimentosOs = $ordemServico->getTodosEstabelecimentos();
        $atividadeEstabelecimentoId = $atividade['estabelecimento_id'] ?? null;
        $estabelecimentosAtividade = !empty($atividadeEstabelecimentoId)
            ? $todosEstabelecimentosOs->where('id', (int) $atividadeEstabelecimentoId)->values()
            : $todosEstabelecimentosOs->values();
        $processosVinculadosOs = $this->obterProcessosVinculadosOs($ordemServico, $estabelecimentosAtividade);

        $processo = null;
        $processoId = isset($validated['processo_id']) ? (int) $validated['processo_id'] : null;

        if ($processosVinculadosOs->isNotEmpty()) {
            if (!$processoId) {
                return redirect()
                    ->route('admin.ordens-servico.show-finalizar-atividade', [$ordemServico, $atividadeIndex])
                    ->withErrors(['processo_id' => 'Selecione o processo que receberá o arquivo.'])
                    ->withInput();
            }

            if (!$processosVinculadosOs->contains($processoId)) {
                return redirect()
                    ->route('admin.ordens-servico.show-finalizar-atividade', [$ordemServico, $atividadeIndex])
                    ->with('error', 'O processo selecionado não está vinculado a esta OS.');
            }

            $processo = Processo::findOrFail($processoId);
        } elseif ($processoId) {
            return redirect()
                ->route('admin.ordens-servico.show-finalizar-atividade', [$ordemServico, $atividadeIndex])
                ->with('error', 'Esta OS não possui processo vinculado para receber o arquivo.');
        }

        $arquivo = $request->file('arquivo');

        try {
            $nomeOriginal = $arquivo->getClientOriginalName();
            $extensao = strtolower($arquivo->getClientOriginalExtension());
            $tamanho = $arquivo->getSize();
            $nomeBase = Str::slug(pathinfo($nomeOriginal, PATHINFO_FILENAME));
            $nomeArquivo = ($nomeBase !== '' ? $nomeBase : 'arquivo') . '_' . time() . '.' . $extensao;

            $diretorio = $processo
                ? 'processos' . DIRECTORY_SEPARATOR . $processo->id
                : 'ordens-servico' . DIRECTORY_SEPARATOR . $ordemServico->id . DIRECTORY_SEPARATOR . 'arquivos-externos';
            $caminhoCompleto = storage_path('app') . DIRECTORY_SEPARATOR . $diretorio;

            if (!file_exists($caminhoCompleto)) {
                mkdir($caminhoCompleto, 0755, true);
            }

            $arquivo->move($caminhoCompleto, $nomeArquivo);

            $caminhoArquivo = $caminhoCompleto . DIRECTORY_SEPARATOR . $nomeArquivo;
            if (!file_exists($caminhoArquivo)) {
                throw new \RuntimeException('Falha ao salvar o arquivo enviado.');
            }

            $tipoSelecionado = trim($validated['tipo_documento']);
            $tipoSlug = Str::slug($tipoSelecionado, '_');
            $nomeVisual = $tipoSelecionado . '.' . $extensao;

            if (in_array($tipoSelecionado, ['Arquivo Externo', 'Usar nome do arquivo'], true)) {
                $tipoSlug = 'arquivo_externo';
                $nomeVisual = $nomeOriginal;
            }

            ProcessoDocumento::create([
                'processo_id' => $processo?->id,
                'os_id' => $ordemServico->id,
                'atividade_index' => $atividadeIndex,
                'pasta_id' => $processo ? $this->resolverPastaParaProcesso($ordemServico, $processo->id) : null,
                'usuario_id' => $usuario->id,
                'tipo_usuario' => 'interno',
                'nome_arquivo' => $nomeArquivo,
                'nome_original' => $nomeVisual,
                'caminho' => str_replace(DIRECTORY_SEPARATOR, '/', $diretorio . DIRECTORY_SEPARATOR . $nomeArquivo),
                'extensao' => $extensao,
                'tamanho' => $tamanho,
                'tipo_documento' => $tipoSlug,
            ]);

            return redirect()
                ->route('admin.ordens-servico.show-finalizar-atividade', [$ordemServico, $atividadeIndex])
                ->with('success', $processo
                    ? 'Arquivo externo vinculado à OS e ao processo com sucesso!'
                    : 'Arquivo externo vinculado à OS com sucesso!');
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.ordens-servico.show-finalizar-atividade', [$ordemServico, $atividadeIndex])
                ->with('error', 'Erro ao fazer upload do arquivo: ' . $e->getMessage());
        }
    }

    public function visualizarArquivoExternoAtividade(OrdemServico $ordemServico, ProcessoDocumento $documento)
    {
        $usuario = Auth::guard('interno')->user();

        if (!$this->podeVisualizarOS($usuario, $ordemServico)) {
            abort(403, 'Você não tem permissão para visualizar este arquivo.');
        }

        $documento = ProcessoDocumento::where('os_id', $ordemServico->id)
            ->findOrFail($documento->id);

        if ($documento->tipo_documento === 'documento_digital' || $documento->tipo_usuario === 'externo') {
            $caminhoCompleto = storage_path('app/public') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $documento->caminho);
        } else {
            $caminhoCompleto = storage_path('app') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $documento->caminho);
        }

        if (!file_exists($caminhoCompleto)) {
            abort(404, 'Arquivo não encontrado.');
        }

        $mimeType = mime_content_type($caminhoCompleto) ?: 'application/octet-stream';

        return response()->file($caminhoCompleto, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $documento->nome_original . '"'
        ]);
    }

    /**
     * Serve o documento anexo da OS (documento_anexo_path).
     * Evita usar Storage::url diretamente (que quebra em subdiretório).
     */
    public function visualizarDocumentoAnexo(Request $request, OrdemServico $ordemServico)
    {
        $usuario = Auth::guard('interno')->user();

        if (!$this->podeVisualizarOS($usuario, $ordemServico)) {
            abort(403, 'Você não tem permissão para visualizar este arquivo.');
        }

        if (empty($ordemServico->documento_anexo_path)) {
            abort(404, 'Documento não encontrado.');
        }

        $relativo = str_replace('/', DIRECTORY_SEPARATOR, $ordemServico->documento_anexo_path);
        $candidatos = [
            storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $relativo),
            storage_path('app' . DIRECTORY_SEPARATOR . $relativo),
        ];

        $caminhoCompleto = null;
        foreach ($candidatos as $c) {
            if (file_exists($c)) {
                $caminhoCompleto = $c;
                break;
            }
        }

        if (!$caminhoCompleto) {
            abort(404, 'Arquivo não encontrado.');
        }

        $mimeType = mime_content_type($caminhoCompleto) ?: 'application/octet-stream';
        $nome = $ordemServico->documento_anexo_nome ?: basename($caminhoCompleto);
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response()->file($caminhoCompleto, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition . '; filename="' . $nome . '"',
        ]);
    }

    private function obterProcessosVinculadosOs(OrdemServico $ordemServico, $todosEstabelecimentosOs = null)
    {
        $todosEstabelecimentosOs = $todosEstabelecimentosOs ?? $ordemServico->getTodosEstabelecimentos();

        $processosVinculadosOs = $todosEstabelecimentosOs
            ->map(function ($est) use ($ordemServico) {
                $processoId = $est->pivot->processo_id ?? null;
                if (!$processoId && $ordemServico->estabelecimento_id == $est->id) {
                    $processoId = $ordemServico->processo_id;
                }

                return $processoId ? (int) $processoId : null;
            })
            ->filter()
            ->unique()
            ->values();

        if ($processosVinculadosOs->isEmpty() && $ordemServico->processo_id) {
            return collect([(int) $ordemServico->processo_id]);
        }

        return $processosVinculadosOs;
    }

    private function resolverPastaParaProcesso(OrdemServico $ordemServico, int $processoId): ?int
    {
        if (!$ordemServico->pasta_id) {
            return null;
        }

        $pastaValida = ProcessoPasta::where('id', $ordemServico->pasta_id)
            ->where('processo_id', $processoId)
            ->exists();

        return $pastaValida ? $ordemServico->pasta_id : null;
    }

    /**
     * Retorna as atividades do técnico logado para uma OS
     */
    public function getMinhasAtividades(OrdemServico $ordemServico)
    {
        $usuario = Auth::guard('interno')->user();
        $atividades = $ordemServico->atividades_tecnicos ?? [];
        
        $minhasAtividades = [];
        
        foreach ($atividades as $index => $atividade) {
            $tecnicosAtividade = $atividade['tecnicos'] ?? [];
            
            // Verifica se o técnico está atribuído a esta atividade
            if (in_array($usuario->id, $tecnicosAtividade)) {
                $responsavelId = $atividade['responsavel_id'] ?? null;
                $responsavel = $responsavelId ? UsuarioInterno::find($responsavelId) : null;
                
                $minhasAtividades[] = [
                    'index' => $index,
                    'tipo_acao_id' => $atividade['tipo_acao_id'] ?? null,
                    'sub_acao_id' => $atividade['sub_acao_id'] ?? null,
                    'nome_atividade' => $atividade['nome_atividade'] ?? 'Atividade',
                    'status' => $atividade['status'] ?? 'pendente',
                    'responsavel_id' => $responsavelId,
                    'responsavel_nome' => $responsavel ? $responsavel->nome : null,
                    'sou_responsavel' => $responsavelId == $usuario->id,
                    'finalizada_em' => $atividade['finalizada_em'] ?? null,
                    'observacoes_finalizacao' => $atividade['observacoes_finalizacao'] ?? null,
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'atividades' => $minhasAtividades,
            'total' => count($minhasAtividades),
            'pendentes' => count(array_filter($minhasAtividades, fn($a) => $a['status'] !== 'finalizada')),
            'finalizadas' => count(array_filter($minhasAtividades, fn($a) => $a['status'] === 'finalizada')),
        ]);
    }

    /**
     * Gerar PDF da Ordem de Serviço
     */
    public function gerarPdf(Request $request, OrdemServico $ordemServico)
    {
        $usuario = Auth::guard('interno')->user();
        
        // Verifica permissão
        if (!$this->podeVisualizarOS($usuario, $ordemServico)) {
            abort(403, 'Você não tem permissão para gerar PDF desta ordem de serviço.');
        }

        $ordemServico->load([
            'estabelecimento.municipio',
            'estabelecimento.responsaveisLegais',
            'estabelecimento.responsaveisTecnicos',
            'estabelecimentos.municipio',
            'estabelecimentos.responsaveisLegais',
            'estabelecimentos.responsaveisTecnicos',
            'municipio',
            'processo.tipoProcesso',
            'processo.documentos',
        ]);

        $estabelecimentoPdf = $ordemServico->estabelecimento;
        $processoPdf = $ordemServico->processo;

        $todosEstabelecimentos = $ordemServico->getTodosEstabelecimentos();
        if ($todosEstabelecimentos->count() > 0) {
            $estabelecimentoIdSelecionado = $request->input('estabelecimento_id');

            if ($estabelecimentoIdSelecionado) {
                $estabelecimentoPdf = $todosEstabelecimentos->firstWhere('id', (int) $estabelecimentoIdSelecionado) ?? $todosEstabelecimentos->first();
            } else {
                $estabelecimentoPdf = $todosEstabelecimentos->first();
            }

            $processoIdPivot = $estabelecimentoPdf?->pivot?->processo_id;
            if ($processoIdPivot) {
                $processoPdf = Processo::with(['tipoProcesso', 'documentos'])->find($processoIdPivot) ?? $processoPdf;
            }
        }

        if ($estabelecimentoPdf) {
            $ordemServico->setRelation('estabelecimento', $estabelecimentoPdf);
        }

        if ($processoPdf) {
            $ordemServico->setRelation('processo', $processoPdf);
            $ordemServico->processo_id = $processoPdf->id;
        }

        $checklistPdf = $this->montarChecklistPdfOs($estabelecimentoPdf, $processoPdf);

        // Determina logomarca para o PDF da OS
        $logomarca = $this->resolverLogomarcaPdfOs($usuario, $estabelecimentoPdf);

        // Pesquisa de satisfação externa: gerar QR Code para o PDF
        // Somente se a OS tem técnicos vinculados ao setor da pesquisa
        $qrCodePesquisaBase64 = null;
        $linkPesquisaExterna  = null;

        $pesquisasExternas = \App\Models\PesquisaSatisfacao::where('ativo', true)
            ->where('tipo_publico', 'externo')
            ->get();

        if ($pesquisasExternas->isNotEmpty()) {
            // Buscar setores dos técnicos vinculados à OS
            $tecnicosIds = [];
            $atividadesTecnicos = $ordemServico->atividades_tecnicos ?? [];
            foreach ($atividadesTecnicos as $atividade) {
                foreach (($atividade['tecnicos'] ?? []) as $tid) {
                    $tecnicosIds[] = (int) $tid;
                }
            }
            // Fallback para campo legado
            if (empty($tecnicosIds) && !empty($ordemServico->tecnicos_ids)) {
                $tecnicosIds = array_map('intval', $ordemServico->tecnicos_ids);
            }
            $tecnicosIds = array_unique($tecnicosIds);

            $setoresTecnicos = [];
            if (!empty($tecnicosIds)) {
                $setoresTecnicos = \App\Models\UsuarioInterno::whereIn('id', $tecnicosIds)
                    ->whereNotNull('setor')
                    ->pluck('setor')
                    ->map(fn($s) => mb_strtolower(trim($s)))
                    ->unique()
                    ->toArray();
            }

            // Encontrar pesquisa externa compatível com os setores dos técnicos
            $pesquisaExterna = null;
            foreach ($pesquisasExternas as $pe) {
                $setoresIds = $pe->tipo_setores_ids;
                if (empty($setoresIds)) {
                    // Sem filtro de setor = aplica para todas as gerências
                    $pesquisaExterna = $pe;
                    break;
                }
                // Buscar codigos dos setores vinculados à pesquisa
                $codigosSetoresPesquisa = \App\Models\TipoSetor::whereIn('id', $setoresIds)
                    ->pluck('codigo')
                    ->map(fn($s) => mb_strtolower(trim($s)))
                    ->toArray();

                // Verificar se algum técnico da OS pertence a um desses setores
                foreach ($setoresTecnicos as $setorTecnico) {
                    if (in_array($setorTecnico, $codigosSetoresPesquisa)) {
                        $pesquisaExterna = $pe;
                        break 2;
                    }
                }
            }

            if ($pesquisaExterna) {
                $estabQr = $estabelecimentoPdf ?? $ordemServico->getTodosEstabelecimentos()->first();

                $linkPesquisaExterna = url('/pesquisa/' . $pesquisaExterna->slug)
                    . '?os=' . $ordemServico->id
                    . ($estabQr ? '&est=' . $estabQr->id : '');

                try {
                    $qr     = new \Endroid\QrCode\QrCode($linkPesquisaExterna);
                    $writer = new \Endroid\QrCode\Writer\PngWriter();
                    $result = $writer->write($qr);
                    $qrCodePesquisaBase64 = base64_encode($result->getString());
                } catch (\Throwable $e) {
                    \Log::warning('Erro ao gerar QR da pesquisa no PDF da OS: ' . $e->getMessage());
                }
            }
        }

        // Renderiza a view para PDF
        $html = view('ordens-servico.pdf', compact(
            'ordemServico', 'estabelecimentoPdf', 'processoPdf',
            'qrCodePesquisaBase64', 'linkPesquisaExterna', 'logomarca', 'checklistPdf'
        ))->render();

        // Gera PDF usando DomPDF
        $pdf = \PDF::loadHTML($html)
            ->setPaper('a4')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);

        return $pdf->download('OS-' . str_pad($ordemServico->numero, 5, '0', STR_PAD_LEFT) . '.pdf');
    }

    /**
     * Gerar PDF consolidado com TODOS os estabelecimentos (cada um em uma página)
     */
    public function gerarPdfTodos(Request $request, OrdemServico $ordemServico)
    {
        $usuario = Auth::guard('interno')->user();

        if (!$this->podeVisualizarOS($usuario, $ordemServico)) {
            abort(403, 'Você não tem permissão para gerar PDF desta ordem de serviço.');
        }

        $ordemServico->load([
            'estabelecimento.municipio',
            'estabelecimento.responsaveisLegais',
            'estabelecimento.responsaveisTecnicos',
            'estabelecimentos.municipio',
            'estabelecimentos.responsaveisLegais',
            'estabelecimentos.responsaveisTecnicos',
            'municipio',
            'processo.tipoProcesso',
            'processo.documentos',
        ]);

        $todosEstabelecimentos = $ordemServico->getTodosEstabelecimentos();

        if ($todosEstabelecimentos->isEmpty()) {
            return redirect()->back()->with('error', 'Nenhum estabelecimento vinculado a esta OS.');
        }

        $htmlPages = [];

        foreach ($todosEstabelecimentos as $index => $estabelecimentoPdf) {
            $processoPdf = $ordemServico->processo;

            $processoIdPivot = $estabelecimentoPdf?->pivot?->processo_id;
            if ($processoIdPivot) {
                $processoPdf = Processo::with(['tipoProcesso', 'documentos'])->find($processoIdPivot) ?? $processoPdf;
            }

            // Clona a OS para não afetar as iterações seguintes
            $osClone = clone $ordemServico;
            $osClone->setRelation('estabelecimento', $estabelecimentoPdf);
            if ($processoPdf) {
                $osClone->setRelation('processo', $processoPdf);
                $osClone->processo_id = $processoPdf->id;
            }

            // Logomarca
            $logomarca = $this->resolverLogomarcaPdfOs(Auth::guard('interno')->user(), $estabelecimentoPdf);

            // QR Code pesquisa
            $qrCodePesquisaBase64 = null;
            $linkPesquisaExterna = null;

            $pesquisasExternas = \App\Models\PesquisaSatisfacao::where('ativo', true)
                ->where('tipo_publico', 'externo')
                ->get();

            if ($pesquisasExternas->isNotEmpty()) {
                $tecnicosIds = [];
                $atividadesTecnicos = $ordemServico->atividades_tecnicos ?? [];
                foreach ($atividadesTecnicos as $atividade) {
                    foreach (($atividade['tecnicos'] ?? []) as $tid) {
                        $tecnicosIds[] = (int) $tid;
                    }
                }
                if (empty($tecnicosIds) && !empty($ordemServico->tecnicos_ids)) {
                    $tecnicosIds = array_map('intval', $ordemServico->tecnicos_ids);
                }
                $tecnicosIds = array_unique($tecnicosIds);

                $setoresTecnicos = [];
                if (!empty($tecnicosIds)) {
                    $setoresTecnicos = \App\Models\UsuarioInterno::whereIn('id', $tecnicosIds)
                        ->whereNotNull('setor')
                        ->pluck('setor')
                        ->map(fn($s) => mb_strtolower(trim($s)))
                        ->unique()
                        ->toArray();
                }

                $pesquisaExterna = null;
                foreach ($pesquisasExternas as $pe) {
                    $setoresIds = $pe->tipo_setores_ids;
                    if (empty($setoresIds)) {
                        $pesquisaExterna = $pe;
                        break;
                    }
                    $codigosSetoresPesquisa = \App\Models\TipoSetor::whereIn('id', $setoresIds)
                        ->pluck('codigo')
                        ->map(fn($s) => mb_strtolower(trim($s)))
                        ->toArray();
                    foreach ($setoresTecnicos as $setorTecnico) {
                        if (in_array($setorTecnico, $codigosSetoresPesquisa)) {
                            $pesquisaExterna = $pe;
                            break 2;
                        }
                    }
                }

                if ($pesquisaExterna) {
                    $linkPesquisaExterna = url('/pesquisa/' . $pesquisaExterna->slug)
                        . '?os=' . $ordemServico->id
                        . '&est=' . $estabelecimentoPdf->id;

                    try {
                        $qr = new \Endroid\QrCode\QrCode($linkPesquisaExterna);
                        $writer = new \Endroid\QrCode\Writer\PngWriter();
                        $result = $writer->write($qr);
                        $qrCodePesquisaBase64 = base64_encode($result->getString());
                    } catch (\Throwable $e) {
                        \Log::warning('Erro ao gerar QR da pesquisa no PDF consolidado: ' . $e->getMessage());
                    }
                }
            }

            $checklistPdf = $this->montarChecklistPdfOs($estabelecimentoPdf, $processoPdf);

            $ordemServicoView = $osClone;
            $htmlPages[] = view('ordens-servico.pdf', [
                'ordemServico' => $ordemServicoView,
                'estabelecimentoPdf' => $estabelecimentoPdf,
                'processoPdf' => $processoPdf,
                'qrCodePesquisaBase64' => $qrCodePesquisaBase64,
                'linkPesquisaExterna' => $linkPesquisaExterna,
                'logomarca' => $logomarca,
                'checklistPdf' => $checklistPdf,
            ])->render();
        }

        // Extrai o conteúdo do <body> de cada página e junta com page-break
        $bodyContents = [];
        $styleBlock = '';
        foreach ($htmlPages as $i => $fullHtml) {
            // Extrai o style da primeira página
            if ($i === 0 && preg_match('/<style[^>]*>(.*?)<\/style>/s', $fullHtml, $styleMatch)) {
                $styleBlock = $styleMatch[0];
            }
            // Extrai o body
            if (preg_match('/<body[^>]*>(.*)<\/body>/s', $fullHtml, $bodyMatch)) {
                $bodyContents[] = $bodyMatch[1];
            } else {
                $bodyContents[] = $fullHtml;
            }
        }

        // Monta HTML consolidado
        $htmlConsolidado = '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">' . $styleBlock . '
            <style>.page-break { page-break-before: always; }</style>
        </head><body>';

        foreach ($bodyContents as $i => $body) {
            if ($i > 0) {
                $htmlConsolidado .= '<div class="page-break"></div>';
            }
            $htmlConsolidado .= $body;
        }

        $htmlConsolidado .= '</body></html>';

        $pdf = \PDF::loadHTML($htmlConsolidado)
            ->setPaper('a4')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);

        return $pdf->download('OS-' . str_pad($ordemServico->numero, 5, '0', STR_PAD_LEFT) . '-TODOS.pdf');
    }

    /**
     * Define a logomarca do PDF da OS:
     * 1. Usuário municipal cujo município tem logomarca cadastrada → logomarca do município do usuário.
     * 2. Senão, mesma regra dos documentos: estabelecimento de competência municipal com
     *    logomarca no município → logomarca do município; caso contrário, a estadual.
     *
     * Obs.: Estabelecimento possui a coluna texto "municipio" com o mesmo nome da relação,
     * por isso o município é obtido via getRelation()/municipio_id e não por $estabelecimento->municipio.
     */
    private function resolverLogomarcaPdfOs($usuario, ?Estabelecimento $estabelecimento): ?string
    {
        if ($usuario && $usuario->isMunicipal() && $usuario->municipio_id) {
            $municipioUsuario = \App\Models\Municipio::find($usuario->municipio_id);
            if ($municipioUsuario && !empty($municipioUsuario->logomarca)) {
                return $municipioUsuario->logomarca;
            }
        }

        if ($estabelecimento && !$estabelecimento->isCompetenciaEstadual() && $estabelecimento->municipio_id) {
            $municipio = $estabelecimento->relationLoaded('municipio') && is_object($estabelecimento->getRelation('municipio'))
                ? $estabelecimento->getRelation('municipio')
                : \App\Models\Municipio::find($estabelecimento->municipio_id);

            if ($municipio && !empty($municipio->logomarca)) {
                return $municipio->logomarca;
            }
        }

        return \App\Models\ConfiguracaoSistema::logomarcaEstadual();
    }

    private function montarChecklistPdfOs(?Estabelecimento $estabelecimento, ?Processo $processo): array
    {
        if ($estabelecimento) {
            $estabelecimento->loadMissing(['responsaveisLegais', 'responsaveisTecnicos']);
        }

        if ($processo) {
            $processo->loadMissing(['tipoProcesso', 'documentos']);
        }

        $documentosObrigatorios = $processo
            ? $processo->getDocumentosObrigatoriosChecklist()->where('obrigatorio', true)->values()
            : collect();

        $documentosPendentes = $documentosObrigatorios
            ->filter(fn ($documento) => ($documento['status'] ?? null) !== 'aprovado')
            ->values();

        $atividadesExigemRt = $estabelecimento
            ? collect($estabelecimento->getAtividadesQueExigemResponsavelTecnico())
                ->map(function ($atividade) {
                    if (is_array($atividade)) {
                        $codigo = $atividade['codigo'] ?? null;
                        $descricao = $atividade['descricao'] ?? $atividade['nome'] ?? null;

                        return trim(collect([$codigo, $descricao])->filter()->implode(' - '));
                    }

                    return (string) $atividade;
                })
                ->filter()
                ->values()
            : collect();

        return [
            'titulo_documentos' => match($processo?->tipoProcesso?->codigo ?? $processo?->tipo ?? 'licenciamento') {
                'projeto_arquitetonico' => 'Docs. Projeto Arq.',
                'analise_rotulagem' => 'Docs. Rotulagem',
                default => 'Docs. Licenciamento',
            },
            'documentos_obrigatorios' => $documentosObrigatorios,
            'documentos_pendentes' => $documentosPendentes,
            'total_documentos' => $documentosObrigatorios->count(),
            'total_aprovados' => $documentosObrigatorios->where('status', 'aprovado')->count(),
            'total_pendentes' => $documentosObrigatorios->where('status', 'pendente')->count(),
            'total_rejeitados' => $documentosObrigatorios->where('status', 'rejeitado')->count(),
            'total_nao_enviados' => $documentosObrigatorios->whereNull('status')->count(),
            'responsavel_legal_ok' => $estabelecimento ? $estabelecimento->responsaveisLegais->count() > 0 : false,
            'responsavel_legal_total' => $estabelecimento ? $estabelecimento->responsaveisLegais->count() : 0,
            'responsavel_tecnico_ok' => $estabelecimento ? $estabelecimento->responsaveisTecnicos->count() > 0 : false,
            'responsavel_tecnico_total' => $estabelecimento ? $estabelecimento->responsaveisTecnicos->count() : 0,
            'responsavel_tecnico_obrigatorio' => $atividadesExigemRt->isNotEmpty(),
            'atividades_exigem_rt' => $atividadesExigemRt,
        ];
    }
    
    /**
     * Envia notificação no chat interno para os técnicos atribuídos
     */
    private function enviarNotificacaoTecnicos(OrdemServico $ordemServico, $remetente)
    {
        try {
            \Log::info('OS Notificação: Iniciando envio de notificações', [
                'os_id' => $ordemServico->id,
                'remetente_id' => $remetente->id,
                'remetente_nome' => $remetente->nome,
            ]);
            
            // Verifica se o chat interno está ativo
            $chatAtivo = \App\Models\ConfiguracaoSistema::where('chave', 'chat_interno_ativo')->first();
            if (!$chatAtivo || $chatAtivo->valor !== 'true') {
                \Log::info('OS Notificação: Chat interno está DESATIVADO');
                return;
            }
            
            \Log::info('OS Notificação: Chat interno está ATIVO');
            
            // Carrega dados completos da OS
            $ordemServico->load(['estabelecimento.municipio', 'municipio', 'processo']);
            
            // Extrai técnicos únicos de todas as atividades
            $tecnicosNotificados = [];
            $atividadesTecnicos = $ordemServico->atividades_tecnicos ?? [];
            
            \Log::info('OS Notificação: Atividades encontradas', [
                'total' => count($atividadesTecnicos),
                'atividades' => $atividadesTecnicos,
            ]);
            
            // Agrupa atividades por técnico
            $atividadesPorTecnico = [];
            foreach ($atividadesTecnicos as $atividade) {
                $nomeAtividade = $atividade['nome_atividade'] ?? 'Atividade';
                $tecnicosIds = $atividade['tecnicos'] ?? [];
                $responsavelId = $atividade['responsavel_id'] ?? null;
                
                foreach ($tecnicosIds as $tecnicoId) {
                    if (!isset($atividadesPorTecnico[$tecnicoId])) {
                        $atividadesPorTecnico[$tecnicoId] = [
                            'atividades' => [],
                            'eh_responsavel' => false,
                        ];
                    }
                    $atividadesPorTecnico[$tecnicoId]['atividades'][] = $nomeAtividade;
                    if ($tecnicoId == $responsavelId) {
                        $atividadesPorTecnico[$tecnicoId]['eh_responsavel'] = true;
                    }
                }
            }
            
            // Dados do estabelecimento
            $estabelecimentoNome = $ordemServico->estabelecimento 
                ? ($ordemServico->estabelecimento->nome_fantasia ?? $ordemServico->estabelecimento->razao_social)
                : 'Não vinculado';
            
            $estabelecimentoEndereco = '';
            if ($ordemServico->estabelecimento) {
                $est = $ordemServico->estabelecimento;
                $estabelecimentoEndereco = $est->logradouro;
                if ($est->numero) $estabelecimentoEndereco .= ', ' . $est->numero;
                if ($est->bairro) $estabelecimentoEndereco .= ' - ' . $est->bairro;
                if ($est->municipio) $estabelecimentoEndereco .= ' - ' . $est->municipio->nome . '/' . $est->municipio->uf;
            }
            
            $estabelecimentoCnpj = '';
            if ($ordemServico->estabelecimento) {
                $estabelecimentoCnpj = $ordemServico->estabelecimento->cnpj_formatado ?? $ordemServico->estabelecimento->cpf_formatado ?? '';
            }
            
            // URL do PDF (rota correta)
            $pdfUrl = route('admin.ordens-servico.pdf', $ordemServico->id);
            $osUrl = route('admin.ordens-servico.show', $ordemServico->id);
            
            foreach ($atividadesPorTecnico as $tecnicoId => $dados) {
                \Log::info('OS Notificação: Verificando técnico', [
                    'tecnico_id' => $tecnicoId,
                    'remetente_id' => $remetente->id,
                ]);
                
                // Busca o técnico
                $tecnico = UsuarioInterno::find($tecnicoId);
                if (!$tecnico) {
                    \Log::warning('OS Notificação: Técnico não encontrado', ['tecnico_id' => $tecnicoId]);
                    continue;
                }
                
                \Log::info('OS Notificação: Enviando mensagem para técnico', [
                    'tecnico_id' => $tecnicoId,
                    'tecnico_nome' => $tecnico->nome,
                ]);
                
                // Tipo de atribuição
                $tipoTecnico = $dados['eh_responsavel'] ? 'Técnico Responsável' : 'Técnico';
                
                // Lista de atividades
                $listaAtividades = implode("\n• ", $dados['atividades']);
                
                // Monta a mensagem
                $mensagemTexto = "📋 *NOVA ORDEM DE SERVIÇO*\n\n";
                $mensagemTexto .= "Olá {$tecnico->nome}!\n\n";
                $mensagemTexto .= "Você foi atribuído como *{$tipoTecnico}* em uma nova Ordem de Serviço.\n\n";
                $mensagemTexto .= "═══════════════════════════\n";
                $mensagemTexto .= "📌 *OS Nº:* " . str_pad($ordemServico->numero, 5, '0', STR_PAD_LEFT) . "\n";
                $mensagemTexto .= "🏢 *Estabelecimento:* {$estabelecimentoNome}\n";
                if ($estabelecimentoCnpj) {
                    $mensagemTexto .= "📄 *CNPJ/CPF:* {$estabelecimentoCnpj}\n";
                }
                if ($estabelecimentoEndereco) {
                    $mensagemTexto .= "📍 *Endereço:* {$estabelecimentoEndereco}\n";
                }
                $mensagemTexto .= "📅 *Período:* " . \Carbon\Carbon::parse($ordemServico->data_inicio)->format('d/m/Y') . " a " . \Carbon\Carbon::parse($ordemServico->data_fim)->format('d/m/Y') . "\n";
                $mensagemTexto .= "═══════════════════════════\n\n";
                $mensagemTexto .= "📝 *Ações a executar:*\n• {$listaAtividades}\n\n";
                $mensagemTexto .= "🔗 *Acessar OS:* {$osUrl}\n";
                $mensagemTexto .= "📎 *Baixar PDF:* {$pdfUrl}\n\n";
                $mensagemTexto .= "— Suporte INFOVISA";
                
                // Encontra ou cria conversa entre remetente e técnico
                $conversa = ChatConversa::encontrarOuCriar($remetente->id, $tecnicoId);
                
                \Log::info('OS Notificação: Conversa encontrada/criada', [
                    'conversa_id' => $conversa->id,
                ]);
                
                // Cria a mensagem de TEXTO (não arquivo)
                $mensagem = ChatMensagem::create([
                    'conversa_id' => $conversa->id,
                    'remetente_id' => $remetente->id,
                    'conteudo' => $mensagemTexto,
                    'tipo' => 'texto',
                ]);
                
                \Log::info('OS Notificação: Mensagem criada com sucesso', [
                    'mensagem_id' => $mensagem->id,
                ]);
                
                // Atualiza timestamp da conversa
                $conversa->update(['ultima_mensagem_at' => now()]);
            }
            
            \Log::info('OS Notificação: Processo finalizado', [
                'total_notificados' => count($atividadesPorTecnico),
            ]);
            
        } catch (\Exception $e) {
            \Log::error('OS Notificação: ERRO ao enviar notificações', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
