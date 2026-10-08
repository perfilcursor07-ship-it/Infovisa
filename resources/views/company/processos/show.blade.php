@extends('layouts.company')

@section('title', 'Detalhes do Processo')
@section('page-title', 'Detalhes do Processo')

@section('content')
<div class="max-w-8xl mx-auto space-y-5" x-data="{ modalUpload: false, modalAlertas: false, modalVisualizador: false, documentoUrl: '', documentoNome: '', documentoExtensao: '', modalResposta: false, docRespostaId: null, docRespostaNome: '', docRespostaTipos: [], docRespostaEnviados: [], modalReenvio: false, docReenvioId: null, docReenvioNome: '', docReenvioMotivo: '' }" data-processo-root>
    {{-- Mensagens --}}
    @if(session('success'))
    <div class="flex items-center gap-2.5 bg-emerald-50 border border-emerald-200 px-3.5 py-2.5 rounded-lg">
        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm text-emerald-800">{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="flex items-center gap-2.5 bg-red-50 border border-red-200 px-3.5 py-2.5 rounded-lg">
        <svg class="w-4 h-4 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm text-red-800">{{ session('error') }}</p>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 px-3.5 py-2.5 rounded-lg">
        <ul class="list-disc list-inside text-sm text-red-700">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($processo->status === 'parado')
    <div class="flex items-start gap-2.5 bg-red-50 border border-red-200 px-3.5 py-2.5 rounded-lg">
        <svg class="w-4 h-4 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="text-sm">
            <p class="font-semibold text-red-800">Processo parado</p>
            @if($processo->motivo_parada)
            <p class="text-red-700"><strong>Motivo:</strong> {{ $processo->motivo_parada }}</p>
            @endif
            @if($processo->data_parada)
            <p class="text-xs text-red-600">Parado em {{ $processo->data_parada->format('d/m/Y H:i') }}</p>
            @endif
        </div>
    </div>
    @endif

    @php
        $statusBadgeProc = match(true) {
            $processo->status === 'aprovado' => ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500'],
            $processo->status === 'em_analise' => ['bg-blue-50 text-blue-700 ring-blue-200', 'bg-blue-500'],
            $processo->status === 'arquivado' => ['bg-slate-100 text-slate-700 ring-slate-200', 'bg-slate-400'],
            $processo->status === 'parado' => ['bg-red-50 text-red-700 ring-red-200', 'bg-red-500'],
            default => ['bg-amber-50 text-amber-800 ring-amber-200', 'bg-amber-500'],
        };

        // Progresso dos documentos obrigatórios
        // Docs base (sem unidade) - sempre existem
        $totalObrigatorios = isset($documentosObrigatorios) ? $documentosObrigatorios->where('obrigatorio', true)->count() : 0;
        $enviadosOuAprovados = isset($documentosObrigatorios) ? $documentosObrigatorios->where('obrigatorio', true)->whereIn('status_envio', ['pendente', 'aprovado'])->count() : 0;
        $aprovados = isset($documentosObrigatorios) ? $documentosObrigatorios->where('obrigatorio', true)->where('status_envio', 'aprovado')->count() : 0;
        $aguardandoAprovacao = isset($documentosObrigatorios) ? $documentosObrigatorios->where('obrigatorio', true)->where('status_envio', 'pendente')->count() : 0;

        // Soma docs das unidades (adicionais)
        if ($processo->unidades->count() > 0 && !empty($documentosObrigatoriosPorUnidade)) {
            foreach ($documentosObrigatoriosPorUnidade as $info) {
                $docsObrig = $info['documentos']->where('obrigatorio', true);
                $totalObrigatorios += $docsObrig->count();
                $enviadosOuAprovados += $docsObrig->whereIn('status_envio', ['pendente', 'aprovado'])->count();
                $aprovados += $docsObrig->where('status_envio', 'aprovado')->count();
                $aguardandoAprovacao += $docsObrig->where('status_envio', 'pendente')->count();
            }
        }

        $percentual = $totalObrigatorios > 0 ? round(($enviadosOuAprovados / $totalObrigatorios) * 100) : 0;
        $faltam = $totalObrigatorios - $enviadosOuAprovados;
        $todosAprovados = ($aprovados == $totalObrigatorios && $totalObrigatorios > 0);
        $todosEnviados = ($percentual == 100 && $totalObrigatorios > 0);

        // Documentos da vigilância com prazo pendente
        $documentosComPrazo = collect();
        if(isset($todosDocumentos)) {
            $documentosComPrazo = $todosDocumentos->filter(function($item) {
                if($item['tipo'] === 'vigilancia') {
                    $doc = $item['documento'];
                    return $doc->temPrazo() && !$doc->isPrazoFinalizado() && $doc->status === 'assinado';
                }
                return false;
            });
        }

        $alertasPendentesCount = $alertas->where('status', 'pendente')->count();
        $processoArquivado = $processo->status === 'arquivado';

        // Próximo passo sugerido para a empresa (apenas apresentação)
        if ($processoArquivado) {
            $proximoPasso = ['tom' => 'slate', 'titulo' => 'Processo arquivado', 'texto' => 'Você ainda pode consultar e baixar os documentos.', 'acao' => null];
        } elseif ($documentosRejeitados->count() > 0) {
            $proximoPasso = ['tom' => 'red', 'titulo' => 'Corrija ' . $documentosRejeitados->count() . ' arquivo(s) rejeitado(s)', 'texto' => 'Veja o motivo e reenvie a versão corrigida.', 'acao' => 'rejeitados'];
        } elseif ($documentosComPrazo->count() > 0) {
            $proximoPasso = ['tom' => 'orange', 'titulo' => 'Responda ' . $documentosComPrazo->count() . ' documento(s) com prazo', 'texto' => 'Abra o documento e anexe sua resposta dentro do prazo.', 'acao' => 'prazos'];
        } elseif ($isProcessoReceituario ?? false) {
            $requisicoesEmAnalise = $requisicoesReceituario->whereIn('status', ['enviada', 'em_analise'])->count();
            $proximoPasso = match (true) {
                !$receituarioAprovado => ['tom' => 'amber', 'titulo' => 'Cadastro de receituário em análise', 'texto' => 'Após a aprovação do cadastro você poderá solicitar notificações de receita.', 'acao' => null],
                $requisicoesEmAnalise > 0 => ['tom' => 'amber', 'titulo' => $requisicoesEmAnalise . ' requisição(ões) aguardando a Vigilância', 'texto' => 'Acompanhe a liberação abaixo. Você pode fazer novos pedidos quando precisar.', 'acao' => 'requisicao'],
                default => ['tom' => 'blue', 'titulo' => 'Solicite notificações de receita', 'texto' => 'Faça uma requisição informando os tipos e quantidades de blocos.', 'acao' => 'requisicao'],
            };
        } elseif ($totalObrigatorios > 0 && $faltam > 0) {
            $proximoPasso = ['tom' => 'blue', 'titulo' => 'Envie ' . $faltam . ' documento(s) obrigatório(s)', 'texto' => 'O processo segue para análise após o envio de todos.', 'acao' => 'upload'];
        } elseif ($aguardandoAprovacao > 0) {
            $proximoPasso = ['tom' => 'amber', 'titulo' => 'Documentos em análise', 'texto' => 'Aguarde a Vigilância Sanitária. Você será avisado se algo precisar de ajuste.', 'acao' => null];
        } elseif ($todosAprovados) {
            $proximoPasso = ['tom' => 'emerald', 'titulo' => 'Documentação verificada', 'texto' => 'Acompanhe por aqui os próximos andamentos.', 'acao' => null];
        } else {
            $proximoPasso = ['tom' => 'blue', 'titulo' => 'Acompanhe seu processo', 'texto' => 'Envie arquivos, responda notificações e acompanhe a análise por aqui.', 'acao' => null];
        }

        $tonsPasso = [
            'slate'   => ['card' => 'bg-slate-50 border-slate-200',     'icone' => 'bg-slate-200 text-slate-600',     'titulo' => 'text-slate-900',   'botao' => 'bg-slate-700 hover:bg-slate-800'],
            'red'     => ['card' => 'bg-red-50 border-red-200',         'icone' => 'bg-red-100 text-red-600',         'titulo' => 'text-red-900',     'botao' => 'bg-red-600 hover:bg-red-700'],
            'orange'  => ['card' => 'bg-orange-50 border-orange-200',   'icone' => 'bg-orange-100 text-orange-600',   'titulo' => 'text-orange-900',  'botao' => 'bg-orange-600 hover:bg-orange-700'],
            'blue'    => ['card' => 'bg-blue-50 border-blue-200',       'icone' => 'bg-blue-100 text-blue-600',       'titulo' => 'text-blue-900',    'botao' => 'bg-blue-600 hover:bg-blue-700'],
            'amber'   => ['card' => 'bg-amber-50 border-amber-200',     'icone' => 'bg-amber-100 text-amber-600',     'titulo' => 'text-amber-900',   'botao' => 'bg-amber-600 hover:bg-amber-700'],
            'emerald' => ['card' => 'bg-emerald-50 border-emerald-200', 'icone' => 'bg-emerald-100 text-emerald-600', 'titulo' => 'text-emerald-900', 'botao' => 'bg-emerald-600 hover:bg-emerald-700'],
        ];
        $tomPasso = $tonsPasso[$proximoPasso['tom']];
    @endphp

    {{-- Cabeçalho com dados do processo --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-4 py-3 flex flex-col lg:flex-row lg:items-center gap-3 justify-between">
            <div class="flex items-start gap-3 min-w-0">
                <a href="{{ route('company.processos.index') }}" title="Voltar para meus processos"
                   class="mt-0.5 w-8 h-8 flex-shrink-0 inline-flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <h1 class="text-lg font-semibold text-slate-900 tabular-nums leading-tight">{{ $processo->numero_processo }}</h1>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[11px] font-semibold rounded-full ring-1 ring-inset {{ $statusBadgeProc[0] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $statusBadgeProc[1] }}"></span>
                            {{ $processo->status_nome }}
                        </span>
                        <span class="text-sm text-slate-500">{{ $processo->tipo_nome }}</span>
                    </div>
                    {{-- Metadados --}}
                    <dl class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                        <div class="flex items-center gap-1 min-w-0">
                            <dt class="sr-only">Estabelecimento</dt>
                            <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01M9 18v.01"/></svg>
                            <dd class="truncate max-w-[16rem]">
                                {{-- Processo de receituário: o "estabelecimento" é o cadastro interno do profissional --}}
                                <a href="{{ $processo->estabelecimento->oculto_receituario && $processo->estabelecimento->receituario
                                        ? route('company.receituarios.show', ['id' => $processo->estabelecimento->receituario->id, 'aba' => 'processos'])
                                        : route('company.estabelecimentos.show', $processo->estabelecimento->id) }}" class="font-medium text-slate-700 hover:text-blue-700 hover:underline underline-offset-2"
                                   title="{{ $processo->estabelecimento->nome_fantasia ?: $processo->estabelecimento->razao_social }}">
                                    {{ $processo->estabelecimento->nome_fantasia ?: $processo->estabelecimento->razao_social }}
                                </a>
                            </dd>
                        </div>
                        <div class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <dt>Aberto em</dt>
                            <dd class="text-slate-700 tabular-nums">{{ $processo->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <dt>Atualizado em</dt>
                            <dd class="text-slate-700 tabular-nums">{{ $processo->updated_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        {{-- Setor Atual (visível para o estabelecimento) --}}
                        @if($processo->setor_atual)
                        <div class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <dt>Está com</dt>
                            <dd class="font-medium text-blue-700">{{ $processo->setor_atual_nome }}</dd>
                            @if($processo->responsavel_desde)
                            @php
                                $diasNoSetor = (int) $processo->responsavel_desde->startOfDay()->diffInDays(now()->startOfDay());
                            @endphp
                            <dd class="text-slate-400" title="{{ $processo->responsavel_desde->format('d/m/Y') }}">
                                @if($diasNoSetor === 0)
                                    (desde hoje)
                                @elseif($diasNoSetor === 1)
                                    (há 1 dia)
                                @else
                                    (há {{ $diasNoSetor }} dias)
                                @endif
                            </dd>
                            @endif
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Ações específicas do cabeçalho --}}
            @if(!$processoArquivado && isset($tipoProcessoTemUnidades) && $tipoProcessoTemUnidades)
            <div class="flex flex-wrap items-center gap-1.5 lg:flex-shrink-0">
                <button type="button" @click="$refs.modalNovaUnidade.classList.remove('hidden')"
                        class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition">
                    <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Nova Unidade
                </button>
            </div>
            @endif
        </div>

        @if($processo->observacoes)
        <div class="px-4 py-2 border-t border-slate-100 text-xs text-slate-600">
            <span class="font-semibold text-amber-700">Observações:</span> {{ $processo->observacoes }}
        </div>
        @endif
    </div>

    {{-- Próximo passo --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-2.5 sm:gap-3 rounded-xl border {{ $tomPasso['card'] }} px-4 py-2.5">
        <div class="flex items-center gap-3 flex-1 min-w-0">
            <span class="w-7 h-7 rounded-lg {{ $tomPasso['icone'] }} flex items-center justify-center flex-shrink-0">
                @if($proximoPasso['tom'] === 'emerald')
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @elseif(in_array($proximoPasso['tom'], ['red', 'orange']))
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                @elseif($proximoPasso['tom'] === 'amber')
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @else
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @endif
            </span>
            <p class="text-sm min-w-0">
                <span class="font-semibold {{ $tomPasso['titulo'] }}">{{ $proximoPasso['titulo'] }}</span>
                <span class="text-slate-600"> — {{ $proximoPasso['texto'] }}</span>
            </p>
        </div>
        @if($proximoPasso['acao'] === 'rejeitados')
        <a href="#secao-rejeitados"
           class="self-start sm:self-auto flex-shrink-0 inline-flex items-center gap-1 h-7 px-3 text-xs font-semibold text-white rounded-md transition {{ $tomPasso['botao'] }}">
            Ver rejeitados
        </a>
        @elseif($proximoPasso['acao'] === 'prazos')
        <a href="#secao-prazos"
           class="self-start sm:self-auto flex-shrink-0 inline-flex items-center gap-1 h-7 px-3 text-xs font-semibold text-white rounded-md transition {{ $tomPasso['botao'] }}">
            Responder agora
        </a>
        @endif
    </div>

    {{-- Modal Bloqueante de Responsável Legal / Responsável Técnico / Equipamentos de Imagem --}}
    @if(isset($precisaCadastrarResponsavelLegal) && $precisaCadastrarResponsavelLegal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 border-t-4 border-red-500">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                </div>

                <h2 class="text-xl font-bold text-slate-900 text-center mb-2">Ação Obrigatória</h2>
                <h3 class="text-lg font-semibold text-red-600 text-center mb-4">Cadastro de Responsável Legal</h3>

                <div class="bg-red-50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-slate-700 mb-3">
                        O estabelecimento <strong class="text-slate-900">{{ $processo->estabelecimento->nome_fantasia ?: $processo->estabelecimento->razao_social }}</strong>
                        não possui Responsável Legal cadastrado.
                    </p>
                    <p class="text-sm text-slate-700">
                        Para visualizar e dar continuidade ao processo, é obrigatório cadastrar ao menos <strong>um Responsável Legal</strong> no estabelecimento.
                    </p>
                </div>

                <a href="{{ route('company.estabelecimentos.responsaveis.create', [$processo->estabelecimento->id, 'legal']) }}"
                   class="flex items-center justify-center w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white text-base font-semibold rounded-xl transition-colors shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Cadastrar Responsável Legal
                </a>

                <p class="text-xs text-slate-500 text-center mt-4">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Este processo está bloqueado até o cadastro ser realizado
                </p>
            </div>
        </div>
    </div>
    @elseif(isset($precisaCadastrarResponsavelTecnico) && $precisaCadastrarResponsavelTecnico)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 border-t-4 border-red-500">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>

                <h2 class="text-xl font-bold text-slate-900 text-center mb-2">Ação Obrigatória</h2>
                <h3 class="text-lg font-semibold text-red-600 text-center mb-4">Cadastro de Responsável Técnico</h3>

                <div class="bg-red-50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-slate-700 mb-3">
                        O estabelecimento <strong class="text-slate-900">{{ $processo->estabelecimento->nome_fantasia ?: $processo->estabelecimento->razao_social }}</strong>
                        possui atividade que exige responsável técnico cadastrado.
                    </p>
                    <p class="text-sm text-slate-700">
                        Para dar continuidade ao processo, é necessário cadastrar ao menos <strong>um Responsável Técnico</strong> no estabelecimento.
                    </p>
                </div>

                <a href="{{ route('company.estabelecimentos.responsaveis.index', $processo->estabelecimento->id) }}"
                   class="flex items-center justify-center w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white text-base font-semibold rounded-xl transition-colors shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Cadastrar Responsável Técnico
                </a>

                <p class="text-xs text-slate-500 text-center mt-4">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Este processo está bloqueado até o cadastro ser realizado
                </p>
            </div>
        </div>
    </div>
    @elseif(isset($precisaCadastrarEquipamentos) && $precisaCadastrarEquipamentos)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog">
        {{-- Overlay escuro --}}
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm"></div>
        
        {{-- Container do Modal --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 border-t-4 border-red-500">
                {{-- Ícone de alerta --}}
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
                
                {{-- Título --}}
                <h2 class="text-xl font-bold text-slate-900 text-center mb-2">
                    Ação Obrigatória
                </h2>
                <h3 class="text-lg font-semibold text-red-600 text-center mb-4">
                    Cadastro de Equipamentos de Imagem
                </h3>
                
                {{-- Conteúdo --}}
                <div class="bg-red-50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-slate-700 mb-3">
                        O estabelecimento <strong class="text-slate-900">{{ $processo->estabelecimento->nome_fantasia ?: $processo->estabelecimento->razao_social }}</strong> 
                        possui atividades que exigem o cadastro de equipamentos de imagem (raio-x, tomografia, ressonância, etc).
                    </p>
                    <p class="text-sm text-slate-700">
                        Para dar continuidade ao processo, é necessário <strong>cadastrar os equipamentos</strong> ou <strong>declarar que o estabelecimento não os possui</strong>.
                    </p>
                </div>
                
                {{-- Botão --}}
                <a href="{{ route('company.estabelecimentos.equipamentos-radiacao.index', $processo->estabelecimento->id) }}" 
                   class="flex items-center justify-center w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white text-base font-semibold rounded-xl transition-colors shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                    Cadastrar Equipamentos de Imagem
                </a>
                
                {{-- Aviso --}}
                <p class="text-xs text-slate-500 text-center mt-4">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Este processo está bloqueado até o cadastro ser realizado
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Avisos de prazo da fila pública --}}
    @php
        $mostrarAvisoGeral = isset($avisoFilaPublica) && $avisoFilaPublica && $processo->status !== 'arquivado';
        $mostrarAvisoUnidades = isset($avisoFilaPublicaPorUnidade) && $avisoFilaPublicaPorUnidade instanceof \Illuminate\Support\Collection && $avisoFilaPublicaPorUnidade->count() > 0 && $processo->status !== 'arquivado';
    @endphp
    @if($mostrarAvisoGeral || $mostrarAvisoUnidades)
    <div class="space-y-1.5">
        {{-- Aviso de Prazo da Fila Pública --}}
        @if($mostrarAvisoGeral)
            @php
                $dias = $avisoFilaPublica['dias_restantes'];
                $prazoPausado = $avisoFilaPublica['pausado'] ?? false;
                $prazoReiniciado = $avisoFilaPublica['prazo_reiniciado'] ?? false;
                $corBg = $prazoPausado ? 'bg-slate-50 border-slate-200 text-slate-700' : ($avisoFilaPublica['atrasado'] ? 'bg-red-50 border-red-200 text-red-700' : ($dias <= 5 ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-cyan-50 border-cyan-200 text-cyan-800'));
            @endphp
            <div class="flex items-center gap-2 {{ $corBg }} border px-3.5 py-2 rounded-lg text-xs sm:text-sm">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>
                    @if($prazoPausado)
                        <strong>Prazo suspenso.</strong> {{ $avisoFilaPublica['atrasado'] ? 'Atraso de ' . abs($dias) . ' dias' : 'Restavam ' . $dias . ' dias' }}
                    @elseif($avisoFilaPublica['atrasado'])
                        <strong>Prazo vencido!</strong> Atrasado há {{ abs($dias) }} {{ abs($dias) == 1 ? 'dia' : 'dias' }}
                    @else
                        Documentação completa em {{ $avisoFilaPublica['data_documentos_completos']->format('d/m/Y') }} • Prazo: {{ $avisoFilaPublica['prazo'] }} dias • <strong>Restam {{ $dias }} dias</strong>
                    @endif
                </span>
            </div>
        @endif

        {{-- Avisos de Prazo por Unidade --}}
        @if($mostrarAvisoUnidades)
            @foreach($avisoFilaPublicaPorUnidade as $pastaId => $avisoU)
            @php
                $diasU = $avisoU['dias_restantes'];
                $pausadoU = $avisoU['pausado'] ?? false;
                $corBgU = $pausadoU ? 'bg-slate-50 border-slate-200 text-slate-700' : ($avisoU['atrasado'] ? 'bg-red-50 border-red-200 text-red-700' : ($diasU <= 5 ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-violet-50 border-violet-200 text-violet-800'));
            @endphp
            <div class="flex items-center gap-2 {{ $corBgU }} border px-3.5 py-2 rounded-lg text-xs sm:text-sm">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span>
                    <strong>{{ $avisoU['nome'] }}:</strong>
                    @if($pausadoU && $avisoU['atrasado'])
                        Prazo suspenso com atraso de {{ abs($diasU) }} dias
                    @elseif($pausadoU)
                        Prazo suspenso • Restavam {{ $diasU }} dias
                    @elseif($avisoU['atrasado'])
                        Prazo vencido! Atrasado há {{ abs($diasU) }} dias
                    @else
                        Documentação completa em {{ $avisoU['data_documentos_completos']->format('d/m/Y') }} • Prazo: {{ $avisoU['prazo'] }} dias • Restam {{ $diasU }} dias
                    @endif
                </span>
            </div>
            @endforeach
        @endif
    </div>
    @endif

    {{-- Layout 2 colunas: painel lateral à esquerda + conteúdo principal à direita --}}
    <div class="grid grid-cols-1 xl:grid-cols-[18rem_minmax(0,1fr)] gap-5 items-start">
        {{-- Coluna Principal --}}
        <div class="space-y-5 min-w-0">
            @if($isProcessoReceituario ?? false)
                @include('company.processos.partials.receituario-requisicoes')
            @endif

            {{-- Alerta de Documentos com Prazo + Upload Inline --}}
            @if($documentosComPrazo->count() > 0)
            <section id="secao-prazos" x-data="{ uploadAberto: null, enviando: false }" class="scroll-mt-4 bg-white rounded-xl border border-orange-200 shadow-sm overflow-hidden">
                <header class="px-4 py-2.5 flex items-center gap-2 bg-orange-50/70 border-b border-orange-100">
                    <svg class="w-4 h-4 text-orange-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h2 class="text-sm font-semibold text-orange-900">Aguardando sua resposta</h2>
                    <span class="px-1.5 py-0.5 bg-orange-500 text-white text-[10px] font-bold rounded-full leading-none">{{ $documentosComPrazo->count() }}</span>
                    <span class="hidden md:inline text-xs text-orange-700 ml-auto">Abra o documento e anexe a resposta dentro do prazo</span>
                </header>

                <ul class="divide-y divide-slate-100">
                    @foreach($documentosComPrazo as $itemPrazo)
                        @php
                            $docPrazo = $itemPrazo['documento'];
                            $precisaVisualizarPrazo = $docPrazo->prazo_notificacao && !$docPrazo->prazo_iniciado_em;
                        @endphp
                        <li class="px-4 py-2.5">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5 flex-1 min-w-0">
                                    <span class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-[13px] font-medium text-slate-900 truncate">{{ $docPrazo->nome_exibicao }}</p>
                                        <p class="text-[11px] text-slate-500 truncate">Nº {{ $docPrazo->numero_documento }} · {{ $docPrazo->data_disponibilizacao ? 'Disponível desde ' . $docPrazo->data_disponibilizacao->format('d/m/Y H:i') : 'Data de disponibilização não registrada' }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 flex-shrink-0 pl-[2.625rem] md:pl-0">
                                    @php
                                        $corBadge = $docPrazo->cor_status_prazo;
                                        $textoBadge = $docPrazo->texto_status_prazo;
                                        $classesCor = [
                                            'red' => 'bg-red-50 text-red-700 ring-red-200',
                                            'yellow' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                            'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                            'blue' => 'bg-blue-50 text-blue-700 ring-blue-200',
                                            'gray' => 'bg-slate-50 text-slate-600 ring-slate-200',
                                        ];
                                        $classeBadge = $classesCor[$corBadge] ?? $classesCor['gray'];
                                    @endphp
                                    <span class="text-[11px] font-medium px-2 py-0.5 rounded-full ring-1 ring-inset {{ $classeBadge }}">{{ $textoBadge }}</span>
                                    <a href="{{ route('company.processos.documento-digital.visualizar', [$processo->id, $docPrazo->id]) }}" target="_blank" @if($precisaVisualizarPrazo) onclick="recarregarAposVisualizarDocumentoPrazo()" @endif
                                       class="inline-flex items-center gap-1 h-7 px-2.5 rounded-md border border-slate-200 bg-white text-xs font-medium text-slate-700 transition hover:bg-slate-50">
                                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                        </svg>
                                        Visualizar
                                    </a>
                                    @if($docPrazo->permiteResposta() && $docPrazo->itensAtendimento->isEmpty() && !$precisaVisualizarPrazo)
                                    @php
                                        $setorEstabPrazo = $processo->estabelecimento?->tipo_setor;
                                        $setorEstabPrazo = $setorEstabPrazo instanceof \App\Enums\TipoSetor ? $setorEstabPrazo->value : ($setorEstabPrazo ?? 'privado');
                                        $tiposRespPrazo = ($docPrazo->tipoDocumento?->tiposDocumentoResposta ?? collect())
                                            ->filter(function($tr) use ($setorEstabPrazo) { return $tr->tipo_setor === 'todos' || $tr->tipo_setor === $setorEstabPrazo; })
                                            ->map(function($tr) { return ['id' => $tr->id, 'nome' => $tr->nome, 'descricao' => $tr->descricao]; })
                                            ->values();
                                        $enviadosPrazo = $docPrazo->respostas
                                            ->whereIn('status', ['pendente', 'aprovado'])
                                            ->pluck('tipo_documento_resposta_id')
                                            ->filter()
                                            ->values();
                                    @endphp
                                    <button @click="docRespostaId = {{ $docPrazo->id }}; docRespostaNome = '{{ addslashes($docPrazo->nome_exibicao) }}'; docRespostaTipos = {{ $tiposRespPrazo->toJson() }}; docRespostaEnviados = {{ $enviadosPrazo->toJson() }}; arquivosResposta = []; modalResposta = true"
                                            class="inline-flex items-center gap-1 h-7 px-2.5 bg-emerald-600 text-white text-xs font-semibold rounded-md hover:bg-emerald-700 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        Anexar Resposta
                                    </button>
                                    @elseif($precisaVisualizarPrazo)
                                    <span class="text-[11px] text-slate-500">Visualize para liberar a resposta</span>
                                    @endif
                                </div>
                            </div>

                            @if($docPrazo->todasAssinaturasCompletas())
                                @include('company.processos.partials.itens-atendimento', ['docDigital' => $docPrazo, 'compacto' => true])
                            @endif

                            {{-- Respostas já enviadas para este documento --}}
                            @if($docPrazo->respostas->whereNull('documento_item_atendimento_id')->count() > 0)
                            <div class="mt-1.5 pl-[2.625rem] flex flex-wrap gap-x-3 gap-y-0.5">
                                @foreach($docPrazo->respostas->whereNull('documento_item_atendimento_id') as $resp)
                                <span class="inline-flex items-center gap-1.5 max-w-full text-[11px]">
                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0
                                        {{ $resp->status === 'aprovado' ? 'bg-emerald-500' : ($resp->status === 'rejeitado' ? 'bg-red-500' : 'bg-amber-500') }}"></span>
                                    <span class="text-slate-600 truncate">{{ $resp->nome_original }}</span>
                                    <span class="{{ $resp->status === 'aprovado' ? 'text-emerald-600' : ($resp->status === 'rejeitado' ? 'text-red-600' : 'text-amber-600') }} font-medium">
                                        {{ $resp->status === 'aprovado' ? 'Verificado' : ($resp->status === 'rejeitado' ? 'Rejeitado' : 'Pendente') }}
                                    </span>
                                </span>
                                @endforeach
                            </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
            @endif

            {{-- Documentos Rejeitados --}}
            @if($documentosRejeitados->count() > 0)
            <section id="secao-rejeitados" class="scroll-mt-4 bg-white rounded-xl border border-red-200 shadow-sm overflow-hidden">
                <header class="px-4 py-2.5 flex items-center gap-2 bg-red-50/70 border-b border-red-100">
                    <svg class="w-4 h-4 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h2 class="text-sm font-semibold text-red-900">Arquivos rejeitados</h2>
                    <span class="px-1.5 py-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full leading-none">{{ $documentosRejeitados->count() }}</span>
                    <span class="hidden md:inline text-xs text-red-700 ml-auto">Corrija e clique em Reenviar</span>
                </header>
                <ul class="divide-y divide-slate-100">
                    @foreach($documentosRejeitados as $documento)
                    <li class="px-4 py-2.5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                <span class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center text-base flex-shrink-0">{{ $documento->icone }}</span>
                                <div class="min-w-0">
                                    <button type="button"
                                            @click="documentoUrl = '{{ route('company.processos.documento.visualizar', [$processo->id, $documento->id]) }}'; documentoNome = '{{ $documento->nome_original }}'; documentoExtensao = '{{ $documento->extensao }}'; modalVisualizador = true"
                                            class="block max-w-full text-[13px] font-medium text-slate-900 hover:text-blue-700 hover:underline underline-offset-2 text-left truncate" title="{{ $documento->nome_original }}">
                                        {{ $documento->nome_original }}
                                    </button>
                                    <p class="text-[11px] text-slate-500">{{ $documento->tamanho_formatado }} · {{ $documento->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 flex-shrink-0 pl-[2.625rem] sm:pl-0">
                                <span class="text-[11px] font-medium px-2 py-0.5 rounded-full ring-1 ring-inset bg-red-50 text-red-700 ring-red-200">Rejeitado</span>
                                @if($processo->status !== 'arquivado')
                                <button type="button"
                                    @click.prevent="docReenvioId = {{ $documento->id }}; docReenvioNome = @js($documento->nome_original); docReenvioMotivo = @js($documento->motivo_rejeicao ?? ''); modalReenvio = true"
                                        class="inline-flex items-center gap-1 h-7 px-2.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Reenviar
                                </button>
                                @endif
                            </div>
                        </div>
                        @if($documento->motivo_rejeicao)
                        <p class="mt-1.5 ml-[2.625rem] px-2.5 py-1.5 bg-red-50 rounded-md text-xs text-red-700">
                            <strong>Motivo:</strong> {{ $documento->motivo_rejeicao }}
                        </p>
                        @endif

                        {{-- Histórico de Rejeições Anteriores --}}
                        @if($documento->historico_rejeicao && count($documento->historico_rejeicao) > 0)
                        <div class="mt-1.5 ml-[2.625rem] text-xs text-slate-600" x-data="{ showHistorico: false }">
                            <button type="button" @click="showHistorico = !showHistorico" class="inline-flex items-center gap-1 text-slate-500 hover:text-slate-800">
                                <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-90': showHistorico }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                Histórico de rejeições ({{ count($documento->historico_rejeicao) }})
                            </button>
                            <div x-show="showHistorico" x-transition class="mt-1.5 space-y-1.5" style="display: none;">
                                @foreach($documento->historico_rejeicao as $index => $rejeicao)
                                <div class="px-2.5 py-1.5 bg-slate-50 rounded-md border border-slate-200">
                                    <p class="text-[10px] text-slate-500">Tentativa {{ $index + 1 }} - {{ \Carbon\Carbon::parse($rejeicao['rejeitado_em'])->format('d/m/Y H:i') }}</p>
                                    <p class="text-xs text-slate-700"><strong>Arquivo:</strong> {{ $rejeicao['arquivo_anterior'] }}</p>
                                    <p class="text-xs text-red-600"><strong>Motivo:</strong> {{ $rejeicao['motivo'] }}</p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </section>
            @endif

            {{-- Documentos Pendentes --}}
            <section id="pendentes-wrapper" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden {{ $documentosPendentes->count() > 0 ? '' : 'hidden' }}">
                <header class="px-4 py-2.5 flex items-center gap-2 border-b border-slate-100">
                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h2 class="text-sm font-semibold text-slate-900">Aguardando aprovação</h2>
                    <span id="pendentes-count" class="px-1.5 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-bold rounded-full leading-none">{{ $documentosPendentes->count() }}</span>
                    <span class="hidden md:inline text-xs text-slate-500 ml-auto">Em análise pela Vigilância Sanitária</span>
                </header>
                <div id="pendentes-list" class="divide-y divide-slate-100">
                    @foreach($documentosPendentes as $documento)
                    <div class="px-4 py-2 flex items-center justify-between hover:bg-slate-50 transition-colors gap-3" data-pendente-doc-id="{{ $documento->id }}">
                        <button type="button"
                                @click="documentoUrl = '{{ route('company.processos.documento.visualizar', [$processo->id, $documento->id]) }}'; documentoNome = '{{ $documento->nome_original }}'; documentoExtensao = '{{ $documento->extensao }}'; modalVisualizador = true"
                                class="group flex items-center gap-2.5 text-left flex-1 min-w-0">
                            <span class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center text-base flex-shrink-0">{{ $documento->icone }}</span>
                            <div class="min-w-0">
                                <p class="text-[13px] font-medium text-slate-900 group-hover:text-blue-700 truncate">{{ $documento->nome_original }}</p>
                                <p class="text-[11px] text-slate-500">{{ $documento->tamanho_formatado }} · {{ $documento->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </button>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <span class="text-[11px] font-medium px-2 py-0.5 rounded-full ring-1 ring-inset bg-amber-50 text-amber-700 ring-amber-200">Em análise</span>
                            @if($documento->usuario_externo_id == auth('externo')->id())
                            <form action="{{ route('company.processos.documento.delete', [$processo->id, $documento->id]) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este arquivo?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-7 h-7 inline-flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Excluir">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>
            <div id="pendentes-empty" class="hidden"></div>

            {{-- Lista de Documentos e Arquivos do Processo --}}
            @php
                $totalDocumentos = isset($todosDocumentos) ? $todosDocumentos->count() : 0;
            @endphp
            {{-- Receituário: a seção só aparece quando houver documento ou arquivo --}}
            @unless(($isProcessoReceituario ?? false) && $totalDocumentos === 0)
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" x-data="{ pastaAtiva: null }">
                <header class="px-4 py-2.5 flex items-center gap-2 border-b border-slate-100">
                    <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <h2 class="text-sm font-semibold text-slate-900">Documentos do processo</h2>
                    <span class="px-1.5 py-0.5 bg-slate-100 text-slate-600 text-[10px] font-bold rounded-full leading-none">{{ $totalDocumentos }}</span>
                </header>

                {{-- Abas de Pastas --}}
                @if($pastas->count() > 0)
                <div class="border-b border-slate-100">
                    <nav class="flex px-2 overflow-x-auto" aria-label="Tabs">
                        {{-- Aba "Todos" --}}
                        <button @click="pastaAtiva = null"
                                :class="pastaAtiva === null ? 'text-slate-900 border-slate-900' : 'text-slate-500 border-transparent hover:text-slate-700'"
                                class="px-2.5 py-2 text-xs font-medium border-b-2 transition-colors whitespace-nowrap inline-flex items-center gap-1.5">
                            Todos
                            <span class="text-[10px] text-slate-400">{{ $totalDocumentos }}</span>
                        </button>

                        {{-- Abas das Pastas --}}
                        @foreach($pastas as $pasta)
                        @php
                            $docsNaPasta = $todosDocumentos->where('pasta_id', $pasta->id)->count();
                        @endphp
                        <button @click="pastaAtiva = {{ $pasta->id }}"
                                :class="pastaAtiva === {{ $pasta->id }} ? 'text-slate-900' : 'text-slate-500 border-transparent hover:text-slate-700'"
                                :style="pastaAtiva === {{ $pasta->id }} ? 'border-color: {{ $pasta->cor }}' : ''"
                                class="px-2.5 py-2 text-xs font-medium border-b-2 transition-colors whitespace-nowrap inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $pasta->cor }}"></span>
                            {{ $pasta->nome }}
                            @if($docsNaPasta > 0)
                            <span class="text-[10px] text-slate-400">{{ $docsNaPasta }}</span>
                            @endif
                        </button>
                        @endforeach

                    </nav>
                </div>
                @endif

                @if($totalDocumentos > 0)
                @php
                    $pastasPorId = $pastas->keyBy('id');
                    $contagemPorGrupoPasta = $todosDocumentos
                        ->groupBy(fn($item) => $item['pasta_id'] ?? 'sem_pasta')
                        ->map(fn($itens) => $itens->count());
                @endphp
                <div class="divide-y divide-slate-100">
                    @foreach($todosDocumentos as $indice => $item)
                        @php
                            $pastaAtualId = $item['pasta_id'] ?? null;
                            $chaveGrupoAtual = $pastaAtualId ?? 'sem_pasta';

                            $itemAnterior = $indice > 0 ? $todosDocumentos[$indice - 1] : null;
                            $pastaAnteriorId = $itemAnterior ? ($itemAnterior['pasta_id'] ?? null) : '__inicio__';
                            $chaveGrupoAnterior = $pastaAnteriorId ?? 'sem_pasta';

                            $mostrarCabecalhoGrupo = $indice === 0 || $chaveGrupoAtual !== $chaveGrupoAnterior;

                            $pastaAtual = $pastaAtualId ? $pastasPorId->get($pastaAtualId) : null;
                            $nomeGrupo = $pastaAtual ? $pastaAtual->nome : 'Sem pasta';
                            $corGrupo = $pastaAtual ? $pastaAtual->cor : '#9CA3AF';
                            $contagemGrupo = $contagemPorGrupoPasta[$chaveGrupoAtual] ?? 0;
                        @endphp

                        @if($mostrarCabecalhoGrupo)
                            <div x-show="pastaAtiva === null"
                                class="px-4 py-1.5 bg-slate-50"
                             style="display: none;">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $corGrupo }}"></span>
                                <span class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">{{ $nomeGrupo }}</span>
                                <span class="text-[11px] text-slate-400">{{ $contagemGrupo }}</span>
                            </div>
                        </div>
                        @endif

                        @if($item['tipo'] === 'vigilancia')
                            @php
                                $docDigital = $item['documento'];
                                // Determinar cor da borda para documentos da vigilância
                                // Verde: documento assinado e sem prazo pendente
                                // Laranja: documento com prazo pendente ou respostas pendentes
                                // Cinza: outros casos
                                $temRespostaPendente = $docDigital->respostas->where('status', 'pendente')->count() > 0;
                                $temRespostaRejeitada = $docDigital->respostas->where('status', 'rejeitado')->count() > 0;
                                $temPrazoPendente = $docDigital->temPrazo() && !$docDigital->isPrazoFinalizado() && $docDigital->status === 'assinado';
                                $precisaVisualizarDocDigital = $docDigital->prazo_notificacao && !$docDigital->prazo_iniciado_em;

                                if ($temRespostaRejeitada) {
                                    $corBordaDoc = '!border-l-red-500';
                                } elseif ($temRespostaPendente || $temPrazoPendente) {
                                    $corBordaDoc = '!border-l-orange-500';
                                } elseif ($docDigital->status === 'assinado') {
                                    $corBordaDoc = '!border-l-emerald-500';
                                } else {
                                    $corBordaDoc = '!border-l-slate-300';
                                }
                            @endphp
                            <div x-show="pastaAtiva === null || pastaAtiva === {{ $item['pasta_id'] ?? 'null' }}"
                                 class="pl-3.5 pr-4 py-2.5 hover:bg-slate-50 transition-colors border-l-2 {{ $corBordaDoc }}">
                                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                    <a href="{{ route('company.processos.documento-digital.visualizar', [$processo->id, $docDigital->id]) }}"
                                       target="_blank"
                                       class="group flex items-center gap-2.5 flex-1 min-w-0">
                                        <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            </svg>
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[13px] font-medium text-slate-900 group-hover:text-blue-700 truncate">
                                                {{ $docDigital->nome_exibicao }}
                                            </p>
                                            <p class="text-[11px] text-slate-500 truncate">
                                                <span class="text-indigo-600 font-medium">Vigilância Sanitária</span>
                                                · Nº {{ $docDigital->numero_documento }}
                                                · <span title="Data de conclusão das assinaturas e liberação do documento no portal">{{ $docDigital->data_disponibilizacao ? 'Disponível desde ' . $docDigital->data_disponibilizacao->format('d/m/Y H:i') : 'Data de disponibilização não registrada' }}</span>
                                            </p>
                                        </div>
                                    </a>
                                    <div class="flex flex-wrap items-center gap-1.5 flex-shrink-0 pl-[2.625rem] md:pl-0 md:justify-end">
                                        {{-- Badge de Prazo --}}
                                        @if($docDigital->temPrazo())
                                            @php
                                                $corBadge = $docDigital->cor_status_prazo;
                                                $textoBadge = $docDigital->texto_status_prazo;
                                                $classesCor = [
                                                    'red' => 'bg-red-50 text-red-700 ring-red-200',
                                                    'yellow' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                                    'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                                    'blue' => 'bg-blue-50 text-blue-700 ring-blue-200',
                                                    'gray' => 'bg-slate-50 text-slate-600 ring-slate-200',
                                                ];
                                                $classeBadge = $classesCor[$corBadge] ?? $classesCor['gray'];
                                            @endphp
                                            <span class="text-[11px] font-medium px-2 py-0.5 rounded-full ring-1 ring-inset whitespace-nowrap {{ $classeBadge }}">{{ $textoBadge }}</span>
                                        @endif
                                        @if($docDigital->permiteResposta() && $docDigital->itensAtendimento->isEmpty() && !$precisaVisualizarDocDigital)
                                        @php
                                            $setorEstab = $processo->estabelecimento?->tipo_setor;
                                            $setorEstab = $setorEstab instanceof \App\Enums\TipoSetor ? $setorEstab->value : ($setorEstab ?? 'privado');
                                            $tiposResp = ($docDigital->tipoDocumento?->tiposDocumentoResposta ?? collect())
                                                ->filter(function($tr) use ($setorEstab) { return $tr->tipo_setor === 'todos' || $tr->tipo_setor === $setorEstab; })
                                                ->map(function($tr) { return ['id' => $tr->id, 'nome' => $tr->nome, 'descricao' => $tr->descricao]; })
                                                ->values();
                                            $enviadosDoc = ($docDigital->respostas ?? collect())
                                                ->whereIn('status', ['pendente', 'aprovado'])
                                                ->pluck('tipo_documento_resposta_id')
                                                ->filter()
                                                ->values();
                                        @endphp
                                        <button type="button"
                                                @click="docRespostaId = {{ $docDigital->id }}; docRespostaNome = '{{ addslashes($docDigital->nome_exibicao) }}'; docRespostaTipos = {{ $tiposResp->toJson() }}; docRespostaEnviados = {{ $enviadosDoc->toJson() }}; arquivosResposta = []; modalResposta = true"
                                                class="inline-flex items-center gap-1 h-7 px-2.5 bg-emerald-600 text-white text-xs font-semibold rounded-md hover:bg-emerald-700 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                            </svg>
                                            Responder
                                        </button>
                                        @elseif($docDigital->temPrazo() && $docDigital->isPrazoFinalizado())
                                        <span class="inline-flex items-center gap-1 text-[11px] font-medium px-2 py-0.5 rounded-full ring-1 ring-inset bg-emerald-50 text-emerald-700 ring-emerald-200">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Finalizado
                                        </span>
                                        @endif
                                        <a href="{{ route('company.processos.documento-digital.visualizar', [$processo->id, $docDigital->id]) }}"
                                           target="_blank" title="Visualizar"
                                           @if($precisaVisualizarDocDigital) onclick="recarregarAposVisualizarDocumentoPrazo()" @endif
                                           class="inline-flex items-center gap-1 h-7 px-2.5 rounded-md border border-slate-200 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                            </svg>
                                            Visualizar
                                        </a>
                                        <a href="{{ route('company.processos.documento-digital.download', [$processo->id, $docDigital->id]) }}" title="Baixar"
                                           class="w-7 h-7 inline-flex items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>

                                @if($docDigital->todasAssinaturasCompletas())
                                    @include('company.processos.partials.itens-atendimento', ['docDigital' => $docDigital, 'compacto' => false])
                                @endif

                                {{-- Respostas vinculadas a este documento --}}
                                @php
                                    $respostasGerais = $docDigital->respostas
                                        ->whereNull('documento_item_atendimento_id');
                                @endphp
                                @if($respostasGerais->count() > 0)
                                @php
                                    $respostasAprovadas = $respostasGerais->where('status', 'aprovado');
                                    $respostasPendentes = $respostasGerais->where('status', 'pendente');
                                    $respostasRejeitadas = $respostasGerais->where('status', 'rejeitado');
                                    $totalRejeicoes = $respostasGerais->sum(function($r) {
                                        return $r->historico_rejeicao ? count($r->historico_rejeicao) : 0;
                                    });
                                @endphp
                                <div class="mt-2 ml-[2.625rem] rounded-lg border border-slate-200 px-3 py-1.5">
                                    {{-- Resumo das respostas --}}
                                    <div class="flex flex-wrap items-center gap-1.5 py-1">
                                        <span class="text-[11px] font-semibold text-slate-600">Suas respostas ({{ $respostasGerais->count() }})</span>
                                        @if($respostasAprovadas->count() > 0)
                                        <span class="px-1.5 py-0.5 text-[10px] font-medium bg-emerald-50 text-emerald-700 rounded">{{ $respostasAprovadas->count() }} verificado(s)</span>
                                        @endif
                                        @if($respostasPendentes->count() > 0)
                                        <span class="px-1.5 py-0.5 text-[10px] font-medium bg-amber-50 text-amber-700 rounded">{{ $respostasPendentes->count() }} pendente(s)</span>
                                        @endif
                                        @if($respostasRejeitadas->count() > 0)
                                        <span class="px-1.5 py-0.5 text-[10px] font-medium bg-red-50 text-red-700 rounded">{{ $respostasRejeitadas->count() }} rejeitado(s)</span>
                                        @endif
                                    </div>

                                    @foreach($respostasGerais as $resposta)
                                    <div class="flex flex-col gap-1 py-1.5 border-t border-slate-100 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $resposta->status === 'aprovado' ? 'bg-emerald-500' : ($resposta->status === 'rejeitado' ? 'bg-red-500' : 'bg-amber-500') }}"></span>
                                            <div class="min-w-0">
                                                <p class="text-xs font-medium text-slate-700 truncate">{{ $resposta->nome_original }}</p>
                                                <p class="text-[10px] text-slate-500">
                                                    {{ $resposta->tamanho_formatado }}
                                                    · {{ $resposta->created_at->format('d/m H:i') }}
                                                    · {{ $resposta->usuarioExterno->nome ?? 'Usuário' }}
                                                    @if($resposta->status === 'aprovado' && $resposta->avaliadoPor)
                                                    · <span class="text-emerald-600">Verificado por {{ $resposta->avaliadoPor->nome }}</span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-0.5 pl-3.5 sm:pl-0">
                                            <span class="px-1.5 py-0.5 text-[10px] font-medium rounded mr-1
                                                @if($resposta->status === 'pendente') bg-amber-50 text-amber-700
                                                @elseif($resposta->status === 'aprovado') bg-emerald-50 text-emerald-700
                                                @else bg-red-50 text-red-700
                                                @endif">
                                                {{ $resposta->status === 'aprovado' ? 'Verificado' : ($resposta->status === 'rejeitado' ? 'Rejeitado' : 'Pendente') }}
                                            </span>
                                            <a href="{{ route('company.processos.documento-digital.resposta.visualizar', [$processo->id, $docDigital->id, $resposta->id]) }}"
                                               target="_blank"
                                               class="w-6 h-6 inline-flex items-center justify-center text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded" title="Visualizar">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
                                            <a href="{{ route('company.processos.documento-digital.resposta.download', [$processo->id, $docDigital->id, $resposta->id]) }}"
                                               class="w-6 h-6 inline-flex items-center justify-center text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded" title="Download">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                            </a>
                                            @if($resposta->status === 'pendente')
                                            <form action="{{ route('company.processos.documento-digital.resposta.excluir', [$processo->id, $docDigital->id, $resposta->id]) }}"
                                                  method="POST"
                                                  class="inline"
                                                  onsubmit="return confirm('Tem certeza que deseja excluir esta resposta?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="w-6 h-6 inline-flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 rounded"
                                                        title="Excluir resposta">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </div>
                                    @if($resposta->status === 'rejeitado' && $resposta->motivo_rejeicao)
                                    <p class="mb-1.5 ml-3.5 px-2.5 py-1 bg-red-50 rounded text-[11px] text-red-700">
                                        <strong>Motivo da rejeição:</strong> {{ $resposta->motivo_rejeicao }}
                                    </p>
                                    @endif
                                    @endforeach

                                    {{-- Histórico de Rejeições Consolidado --}}
                                    @if($totalRejeicoes > 0)
                                    <div class="mt-1 mb-1 p-2 bg-orange-50 border border-orange-200 rounded-md" x-data="{ showHistorico: false }">
                                        <button type="button" @click="showHistorico = !showHistorico" class="flex items-center gap-2 text-orange-800 hover:text-orange-900 w-full">
                                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span class="text-xs font-semibold">Histórico de rejeições ({{ $totalRejeicoes }})</span>
                                            <svg class="w-3 h-3 ml-auto transition-transform" :class="{ 'rotate-180': showHistorico }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div x-show="showHistorico" x-transition class="mt-2 space-y-1.5" style="display: none;">
                                            @foreach($respostasGerais as $resposta)
                                                @if($resposta->historico_rejeicao && count($resposta->historico_rejeicao) > 0)
                                                    @foreach($resposta->historico_rejeicao as $index => $rejeicao)
                                                    <div class="px-2.5 py-1.5 bg-white rounded-md border border-orange-200">
                                                        <div class="flex items-center gap-2 mb-1">
                                                            <span class="px-1.5 py-0.5 text-[10px] font-medium bg-orange-100 text-orange-700 rounded">Tentativa {{ $index + 1 }}</span>
                                                            <span class="text-[10px] text-slate-500">{{ \Carbon\Carbon::parse($rejeicao['rejeitado_em'])->format('d/m/Y H:i') }}</span>
                                                        </div>
                                                        <p class="text-xs text-slate-700">
                                                            <strong>Arquivo:</strong> {{ $rejeicao['arquivo_anterior'] }}
                                                        </p>
                                                        <p class="text-xs text-red-600 mt-1">
                                                            <strong>Motivo:</strong> {{ $rejeicao['motivo'] }}
                                                        </p>
                                                    </div>
                                                    @endforeach
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                @endif
                            </div>
                        @else
                            @php
                                $documento = $item['documento'];
                                // Determinar cor da borda para arquivos do usuário
                                // Se o tipo é 'aprovado' (vem da collection documentosAprovados), é verde
                                // Caso contrário, verifica o status_aprovacao
                                if ($item['tipo'] === 'aprovado') {
                                    $corBordaArquivo = '!border-l-emerald-500';
                                } elseif ($documento->status_aprovacao === 'rejeitado') {
                                    $corBordaArquivo = '!border-l-red-500';
                                } elseif ($documento->status_aprovacao === 'pendente') {
                                    $corBordaArquivo = '!border-l-orange-500';
                                } elseif ($documento->status_aprovacao === 'aprovado') {
                                    $corBordaArquivo = '!border-l-emerald-500';
                                } else {
                                    $corBordaArquivo = '!border-l-slate-300';
                                }
                            @endphp
                            <div x-show="pastaAtiva === null || pastaAtiva === {{ $item['pasta_id'] ?? 'null' }}"
                                 class="pl-3.5 pr-4 py-2.5 hover:bg-slate-50 transition-colors border-l-2 {{ $corBordaArquivo }}">
                                <div class="flex items-center justify-between gap-3">
                                <button type="button"
                                        @click="documentoUrl = '{{ route('company.processos.documento.visualizar', [$processo->id, $documento->id]) }}'; documentoNome = '{{ $documento->nome_original }}'; documentoExtensao = '{{ $documento->extensao }}'; modalVisualizador = true"
                                        class="group flex items-center gap-2.5 text-left flex-1 min-w-0">
                                    <span class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center text-base flex-shrink-0">{{ $documento->icone }}</span>
                                    <div class="min-w-0">
                                        <p class="text-[13px] font-medium text-slate-900 group-hover:text-blue-700 truncate">{{ $documento->nome_original }}</p>
                                        <p class="text-[11px] text-slate-500 truncate">
                                            @if($documento->tipo_usuario === 'externo')
                                            <span class="text-emerald-600 font-medium">Enviado por você</span>
                                            @else
                                            <span class="text-indigo-600 font-medium">Vigilância Sanitária</span>
                                            @endif
                                            · {{ $documento->tamanho_formatado }}
                                            · {{ $documento->created_at->format('d/m/Y H:i') }}
                                        </p>
                                    </div>
                                </button>
                                <a href="{{ route('company.processos.download', [$processo->id, $documento->id]) }}" title="Baixar"
                                   class="flex-shrink-0 w-7 h-7 inline-flex items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </a>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
                @else
                <div class="px-4 py-8 text-center">
                    <svg class="mx-auto h-8 w-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="mt-2 text-sm text-slate-600">Nenhum documento no processo</p>
                </div>
                @endif
            </section>
            @endunless
        </div>

        {{-- Painel lateral --}}
        <aside class="xl:order-first grid grid-cols-1 md:grid-cols-2 xl:grid-cols-1 gap-5 items-start">
            {{-- Ações do processo --}}
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <h2 class="px-4 pt-3 pb-1.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Ações</h2>
                <div class="px-2 pb-2 space-y-0.5">
                    <button type="button" @click="modalAlertas = true"
                            class="group w-full flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-left">
                        <svg class="w-[18px] h-[18px] text-slate-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="flex-1">Alertas</span>
                        @if($alertasPendentesCount > 0)
                        <span class="min-w-[1.25rem] h-5 px-1 inline-flex items-center justify-center bg-red-100 text-red-700 text-[10px] font-bold rounded-full">{{ $alertasPendentesCount }}</span>
                        @endif
                    </button>
                    <a href="{{ route('company.processos.protocolo', $processo->id) }}" target="_blank" rel="noopener"
                       title="Comprovante de abertura do processo"
                       class="group flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-50 transition-colors">
                        <svg class="w-[18px] h-[18px] text-slate-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Protocolo
                    </a>
                    @if(!$processoArquivado && ($isProcessoReceituario ?? false) && $receituarioAprovado)
                    <a href="{{ route('company.processos.receituario-requisicoes.create', $processo->id) }}"
                       class="group flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-50 transition-colors">
                        <svg class="w-[18px] h-[18px] text-slate-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/>
                        </svg>
                        Nova requisição
                    </a>
                    @endif
                    {{-- Receituário: envio de ofício/documento pela empresa desativado por enquanto.
                         Para reativar, troque a condição abaixo por: @if(!$processoArquivado) --}}
                    @if(!$processoArquivado && !($isProcessoReceituario ?? false))
                    <button type="button" @click="modalUpload = true"
                            class="group w-full flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-left">
                        <svg class="w-[18px] h-[18px] text-slate-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        {{ ($isProcessoReceituario ?? false) ? 'Enviar ofício/documento' : 'Enviar arquivo' }}
                    </button>
                    @endif
                </div>
            </section>

            @if($isProcessoReceituario ?? false)
            {{-- Resumo das requisições de receituário --}}
            @php
                $contagemRequisicoes = [
                    ['rotulo' => 'Aguardando', 'total' => $requisicoesReceituario->whereIn('status', ['enviada', 'em_analise'])->count(), 'cor' => 'text-amber-600'],
                    ['rotulo' => 'Liberadas', 'total' => $requisicoesReceituario->where('status', 'liberada')->count(), 'cor' => 'text-emerald-600'],
                    ['rotulo' => 'Indeferidas', 'total' => $requisicoesReceituario->where('status', 'indeferida')->count(), 'cor' => 'text-red-600'],
                ];
            @endphp
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-4 py-3">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Requisições</h3>
                        <span class="text-xs font-semibold text-slate-700 tabular-nums">{{ $requisicoesReceituario->where('status', '!=', 'cancelada')->count() }}</span>
                    </div>
                    <p class="text-xs text-slate-500">Pedidos de notificação e numeração de receita deste processo.</p>
                </div>
                <div class="grid grid-cols-3 border-t border-slate-100 divide-x divide-slate-100 text-center">
                    @foreach($contagemRequisicoes as $item)
                    <a href="#secao-requisicoes" class="py-2 hover:bg-slate-50 transition-colors">
                        <p class="text-sm font-semibold tabular-nums {{ $item['total'] ? $item['cor'] : 'text-slate-400' }}">{{ $item['total'] }}</p>
                        <p class="text-[10px] text-slate-500">{{ $item['rotulo'] }}</p>
                    </a>
                    @endforeach
                </div>
            </div>
            @else
            {{-- Resumo / Documentos Obrigatórios --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-4 py-3">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Documentos obrigatórios</h3>
                        <span class="text-xs font-semibold tabular-nums {{ $todosAprovados ? 'text-emerald-600' : ($todosEnviados ? 'text-amber-600' : ($totalObrigatorios == 0 ? 'text-slate-400' : 'text-slate-700')) }}">
                            {{ $enviadosOuAprovados }}/{{ $totalObrigatorios }}
                        </span>
                    </div>

                    {{-- Barra de Progresso --}}
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden flex">
                        @if($totalObrigatorios > 0)
                            @php
                                $pctAprovados = round(($aprovados / $totalObrigatorios) * 100);
                                $pctPendentes = round(($aguardandoAprovacao / $totalObrigatorios) * 100);
                            @endphp
                            @if($aprovados > 0)
                            <div class="h-full bg-emerald-500 transition-all duration-500" style="width: {{ $pctAprovados }}%"></div>
                            @endif
                            @if($aguardandoAprovacao > 0)
                            <div class="h-full bg-amber-400 transition-all duration-500" style="width: {{ $pctPendentes }}%"></div>
                            @endif
                        @endif
                    </div>

                    {{-- Status --}}
                    <div class="mt-2">
                        @if($totalObrigatorios == 0)
                        <p class="text-xs text-slate-500">Nenhum documento obrigatório configurado</p>
                        @elseif($todosAprovados)
                        <p class="text-xs text-emerald-700 font-medium">Todos os documentos foram verificados</p>
                        @elseif($todosEnviados && $aguardandoAprovacao > 0)
                        <p class="text-xs text-amber-700">Todos enviados · <span class="font-semibold">{{ $aguardandoAprovacao }}</span> aguardando aprovação</p>
                        @else
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs text-slate-600"><span class="font-semibold text-amber-600">{{ $faltam }}</span> pendente(s) de envio</p>
                        </div>
                        @endif
                    </div>

                    {{-- Progresso por Unidade --}}
                    @if(!empty($documentosObrigatoriosPorUnidade) && count($documentosObrigatoriosPorUnidade) > 0)
                    <div class="mt-3 pt-2.5 border-t border-slate-100 space-y-1.5">
                        @foreach($documentosObrigatoriosPorUnidade as $pastaId => $info)
                        @php
                            $pctUnidade = $info['total'] > 0 ? round(($info['enviados'] / $info['total']) * 100) : 0;
                        @endphp
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] text-slate-600 w-24 truncate" title="{{ $info['nome'] }}">{{ $info['nome'] }}</span>
                            <div class="flex-1 bg-slate-100 rounded-full h-1 overflow-hidden">
                                <div class="h-full rounded-full {{ $pctUnidade === 100 ? 'bg-emerald-500' : 'bg-violet-500' }}" style="width: {{ $pctUnidade }}%"></div>
                            </div>
                            <span class="text-[10px] font-semibold tabular-nums {{ $pctUnidade === 100 ? 'text-emerald-600' : 'text-violet-600' }}">{{ $info['enviados'] }}/{{ $info['total'] }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- Resumo --}}
                <div class="grid grid-cols-3 border-t border-slate-100 divide-x divide-slate-100 text-center">
                    <div class="py-2">
                        <p class="text-sm font-semibold text-slate-900 tabular-nums">{{ $documentosAprovados->count() }}</p>
                        <p class="text-[10px] text-slate-500">Verificados</p>
                    </div>
                    <div class="py-2">
                        <p id="pendentes-count-resumo" class="text-sm font-semibold text-amber-600 tabular-nums">{{ $documentosPendentes->count() }}</p>
                        <p class="text-[10px] text-slate-500">Em análise</p>
                    </div>
                    <div class="py-2">
                        <p class="text-sm font-semibold text-orange-600 tabular-nums">{{ $alertasPendentesCount }}</p>
                        <p class="text-[10px] text-slate-500">Alertas</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Documentos de Ajuda --}}
            @if(isset($documentosAjuda) && $documentosAjuda->count() > 0)
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <h3 class="px-4 pt-3 pb-1.5 text-xs font-semibold text-slate-500 uppercase tracking-wide">Ajuda</h3>
                <ul class="pb-1.5">
                    @foreach($documentosAjuda as $docAjuda)
                    <li>
                        <a href="{{ route('company.processos.documento-ajuda', [$processo->id, $docAjuda->id]) }}"
                           target="_blank"
                           class="flex items-center gap-2 px-4 py-1.5 hover:bg-slate-50 transition-colors group">
                            <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                            </svg>
                            <span class="flex-1 min-w-0 text-xs text-slate-700 group-hover:text-blue-700 truncate" title="{{ $docAjuda->titulo }}">{{ $docAjuda->titulo }}</span>
                            <svg class="w-3 h-3 text-slate-300 group-hover:text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </aside>
    </div>

    {{-- Modal Upload --}}
    @include('company.processos.partials.modal-upload', ['somenteDiversos' => $isProcessoReceituario ?? false])

    {{-- Modal Nova Unidade --}}
    @if(isset($tipoProcessoTemUnidades) && $tipoProcessoTemUnidades)
    <div x-ref="modalNovaUnidade" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" @click="$refs.modalNovaUnidade.classList.add('hidden')"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
                <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-violet-50 to-purple-50">
                    <h3 class="text-lg font-semibold text-slate-900">Solicitar Nova Unidade</h3>
                    <p class="text-xs text-slate-500 mt-1">Selecione a unidade que deseja adicionar ao processo</p>
                    <div class="mt-3 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        <p class="text-xs text-amber-800 leading-relaxed">
                            ⚠️ Solicitação de nova unidade para análise de projeto arquitetônico. Após adicionar, será necessário enviar os documentos obrigatórios para análise da Vigilância Sanitária.
                        </p>
                    </div>
                </div>
                @if(isset($unidadesDisponiveis) && $unidadesDisponiveis->count() > 0)
                <form action="{{ route('company.processos.adicionar-unidade', $processo->id) }}" method="POST" class="p-6">
                    @csrf
                    <div class="space-y-2">
                        @foreach($unidadesDisponiveis as $unidade)
                        <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-lg cursor-pointer hover:border-violet-300 hover:bg-violet-50/50 has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50 transition-all">
                            <input type="radio" name="unidade_id" value="{{ $unidade->id }}" required
                                   class="h-4 w-4 text-violet-600 border-slate-300 focus:ring-violet-500">
                            <div>
                                <span class="text-sm font-medium text-slate-900">{{ $unidade->nome }}</span>
                                @if($unidade->descricao)
                                    <p class="text-xs text-slate-500">{{ $unidade->descricao }}</p>
                                @endif
                            </div>
                        </label>
                        @endforeach
                    </div>
                    <div class="mt-4">
                        <label for="nome_unidade_company" class="block text-sm font-medium text-slate-700 mb-1">Nome da unidade <span class="text-slate-400 font-normal">(opcional)</span></label>
                        <input type="text" id="nome_unidade_company" name="nome_unidade" maxlength="255"
                               placeholder="Ex.: UTI Pediátrica, PS Infantil, Centro Cirúrgico"
                               class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                        <p class="text-xs text-slate-500 mt-1">Identifique a unidade. Se deixar em branco, será usado o nome do tipo selecionado.</p>
                    </div>
                    <div class="flex items-center gap-3 mt-6 pt-4 border-t border-slate-200">
                        <button type="submit" class="px-4 py-2 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700 transition">
                            Adicionar Unidade
                        </button>
                        <button type="button" @click="$refs.modalNovaUnidade.classList.add('hidden')" class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-200 transition">
                            Cancelar
                        </button>
                    </div>
                </form>
                @else
                <div class="p-6 text-center">
                    <svg class="w-12 h-12 text-green-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-slate-600">Todas as unidades disponíveis já foram adicionadas ao processo.</p>
                    <button type="button" @click="$refs.modalNovaUnidade.classList.add('hidden')" class="mt-4 px-4 py-2 bg-slate-100 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-200 transition">
                        Fechar
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Resposta a Documento --}}
    <div x-show="modalResposta" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" @click="modalResposta = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl" @click.stop
                 x-data="{
                     arquivosResposta: [],
                     maxArquivos: 6,
                     dragover: false,
                     enviando: false,
                     handleFiles(e) {
                         const files = e.target.files || (e.dataTransfer && e.dataTransfer.files);
                         if (files) {
                             for (let i = 0; i < files.length && this.arquivosResposta.length < this.maxArquivos; i++) {
                                 const file = files[i];
                                 if (file.size > 30 * 1024 * 1024) {
                                     alert('O arquivo ' + file.name + ' excede o limite de 30MB.');
                                     continue;
                                 }
                                 const jaExiste = this.arquivosResposta.some(f => f.name === file.name && f.size === file.size);
                                 if (!jaExiste) {
                                     this.arquivosResposta.push({
                                         file: file,
                                         name: file.name,
                                         size: (file.size / 1024 / 1024).toFixed(2) + ' MB'
                                     });
                                 }
                             }
                         }
                         if (e.target && e.target.value) e.target.value = '';
                         this.dragover = false;
                     },
                     removeFile(index) {
                         this.arquivosResposta.splice(index, 1);
                     },
                     async enviarRespostas() {
                         if (this.arquivosResposta.length === 0 || this.enviando) return;
                         
                         this.enviando = true;
                         let sucessos = 0;
                         let erros = 0;
                         const observacoes = '';
                         
                         for (let i = 0; i < this.arquivosResposta.length; i++) {
                             const arquivo = this.arquivosResposta[i];
                             const formData = new FormData();
                             formData.append('arquivo', arquivo.file);
                             formData.append('observacoes', observacoes);
                             formData.append('_token', '{{ csrf_token() }}');
                             if (arquivo.tipoRespostaId) {
                                 formData.append('tipo_documento_resposta_id', arquivo.tipoRespostaId);
                             }
                             
                             try {
                                 const response = await fetch(`{{ url('/company/processos/' . $processo->id . '/documentos-vigilancia') }}/${docRespostaId}/resposta`, {
                                     method: 'POST',
                                     body: formData,
                                     headers: {
                                         'X-Requested-With': 'XMLHttpRequest',
                                         'Accept': 'application/json'
                                     }
                                 });
                                 
                                 if (response.ok) {
                                     sucessos++;
                                 } else {
                                     erros++;
                                 }
                             } catch (error) {
                                 console.error('Erro:', error);
                                 erros++;
                             }
                         }
                         
                         this.enviando = false;
                         this.arquivosResposta = [];
                         
                         if (erros === 0) {
                             alert(`${sucessos} resposta(s) enviada(s) com sucesso!`);
                             modalResposta = false;
                             location.reload();
                         } else {
                             alert(`${sucessos} resposta(s) enviada(s). ${erros} erro(s).`);
                         }
                     }
                 }">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-green-50 to-emerald-50 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Responder Notificação</h3>
                                <p class="text-xs text-slate-500">Anexe os documentos de resposta</p>
                            </div>
                        </div>
                        <button type="button" @click="modalResposta = false" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="px-6 py-5 space-y-4">
                    {{-- Info do documento --}}
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                        <p class="text-sm text-green-800 font-medium flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Respondendo a: <span x-text="docRespostaNome" class="text-green-900"></span>
                        </p>
                    </div>

                    {{-- Upload com tipos definidos --}}
                    <template x-if="docRespostaTipos.length > 0">
                        <div class="space-y-2">
                            <template x-for="(tipoResp, idx) in docRespostaTipos" :key="tipoResp.id">
                                <div class="flex items-center gap-3 p-3 rounded-lg border"
                                     :class="docRespostaEnviados.includes(tipoResp.id) ? 'bg-slate-50 border-slate-200 opacity-60' : 'bg-white border-slate-200'">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                         :class="docRespostaEnviados.includes(tipoResp.id) ? 'bg-slate-100' : 'bg-green-100'">
                                        <template x-if="!docRespostaEnviados.includes(tipoResp.id)">
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </template>
                                        <template x-if="docRespostaEnviados.includes(tipoResp.id)">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </template>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium" :class="docRespostaEnviados.includes(tipoResp.id) ? 'text-slate-500' : 'text-slate-900'" x-text="tipoResp.nome"></p>
                                        <template x-if="docRespostaEnviados.includes(tipoResp.id)">
                                            <p class="text-[10px] text-slate-400">Já enviado — aguardando análise</p>
                                        </template>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        {{-- Não enviado ainda --}}
                                        <template x-if="!docRespostaEnviados.includes(tipoResp.id) && !arquivosResposta.some(f => f.tipoRespostaId === tipoResp.id)">
                                            <label class="px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition cursor-pointer inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                Anexar
                                                <input type="file" accept=".pdf" class="hidden"
                                                       @change="
                                                           const file = $event.target.files[0];
                                                           if (file && file.size > 30*1024*1024) { alert('Máximo 30MB'); $event.target.value=''; return; }
                                                           if (file) {
                                                               arquivosResposta = arquivosResposta.filter(f => f.tipoRespostaId !== tipoResp.id);
                                                               arquivosResposta.push({ file, name: tipoResp.nome + '.pdf', size: (file.size/1024/1024).toFixed(2)+' MB', tipoRespostaId: tipoResp.id });
                                                           }
                                                           $event.target.value = '';
                                                       ">
                                            </label>
                                        </template>
                                        {{-- Arquivo selecionado --}}
                                        <template x-if="!docRespostaEnviados.includes(tipoResp.id) && arquivosResposta.some(f => f.tipoRespostaId === tipoResp.id)">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs text-green-700 font-medium flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    Anexado
                                                </span>
                                                <button type="button" @click="arquivosResposta = arquivosResposta.filter(f => f.tipoRespostaId !== tipoResp.id)"
                                                        class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                    
                    {{-- Upload livre (sem tipos definidos) --}}
                    <template x-if="docRespostaTipos.length === 0">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                Arquivos de Resposta * 
                                <span class="text-slate-500 font-normal">(<span x-text="arquivosResposta.length"></span>/6 selecionados)</span>
                            </label>
                        
                        {{-- Área de drop --}}
                        <div class="relative mb-3"
                             @dragover.prevent="dragover = true"
                             @dragleave.prevent="dragover = false"
                             @drop.prevent="handleFiles($event)">
                            <input type="file" 
                                   @change="handleFiles($event)"
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                   accept=".pdf"
                                   multiple
                                   :disabled="arquivosResposta.length >= maxArquivos">
                            <div class="border-2 border-dashed rounded-xl p-6 text-center transition-all"
                                 :class="dragover ? 'border-green-500 bg-green-50' : (arquivosResposta.length >= maxArquivos ? 'border-slate-200 bg-slate-50' : 'border-slate-300 bg-slate-50 hover:border-green-400 hover:bg-green-50')">
                                <template x-if="arquivosResposta.length < maxArquivos">
                                    <div>
                                        <svg class="w-12 h-12 mx-auto text-green-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                        <p class="text-sm text-slate-600 mb-1">
                                            <span class="text-green-600 font-semibold">Clique para selecionar</span> ou arraste os arquivos
                                        </p>
                                        <p class="text-xs text-slate-500">Apenas PDF • Máx. 30MB cada • Até 6 arquivos</p>
                                        <p class="text-xs text-amber-600 mt-2 font-medium">💡 Dica: Documentos com muitas folhas devem ter no máximo 5MB</p>
                                    </div>
                                </template>
                                <template x-if="arquivosResposta.length >= maxArquivos">
                                    <div>
                                        <svg class="w-12 h-12 mx-auto text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="text-sm text-slate-500 font-medium">Limite de 6 arquivos atingido</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        {{-- Lista de arquivos selecionados --}}
                        <template x-if="arquivosResposta.length > 0">
                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                <template x-for="(arquivo, index) in arquivosResposta" :key="index">
                                    <div class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-slate-900 truncate" x-text="arquivo.name"></p>
                                                <p class="text-xs text-slate-500" x-text="arquivo.size"></p>
                                            </div>
                                        </div>
                                        <button type="button" @click="removeFile(index)" 
                                                class="p-1.5 text-red-500 hover:bg-red-100 rounded-lg transition-colors flex-shrink-0" title="Remover">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                    </template>
                    
                </div>
                
                {{-- Footer --}}
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 rounded-b-2xl flex flex-row-reverse gap-3">
                    <button type="button" @click="enviarRespostas()"
                            :disabled="arquivosResposta.length === 0 || enviando"
                            class="px-5 py-2.5 bg-green-600 text-white text-sm font-medium rounded-xl hover:bg-green-700 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="!enviando">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </template>
                        <template x-if="enviando">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span x-text="enviando ? 'Enviando...' : 'Enviar ' + arquivosResposta.length + ' Resposta(s)'"></span>
                    </button>
                    <button type="button" @click="modalResposta = false" class="px-4 py-2.5 bg-white text-slate-700 text-sm font-medium rounded-xl border border-slate-300 hover:bg-slate-50 transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Alertas --}}
    <div x-show="modalAlertas" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="modalAlertas = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden" @click.stop>
                <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        Alertas do Processo
                    </h3>
                    <button type="button" @click="modalAlertas = false" class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-md transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    @if($alertas->count() > 0)
                    <div class="divide-y divide-slate-100">
                        @foreach($alertas as $alerta)
                        <div class="px-6 py-4 {{ $alerta->status === 'pendente' ? ($alerta->isVencido() ? 'bg-red-50' : ($alerta->isProximo() ? 'bg-yellow-50' : '')) : 'bg-slate-50' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <p class="text-sm text-slate-900">{{ $alerta->descricao }}</p>
                                    <div class="flex items-center gap-3 mt-1">
                                        <p class="text-xs text-slate-500 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            {{ $alerta->data_alerta->format('d/m/Y') }}
                                        </p>
                                        @if($alerta->usuarioCriador)
                                        <p class="text-xs text-slate-400">
                                            por {{ $alerta->usuarioCriador->nome }}
                                        </p>
                                        @endif
                                    </div>
                                    @if($alerta->status === 'concluido' && $alerta->concluido_em)
                                    <p class="text-xs text-green-600 mt-1">
                                        Resolvido em {{ $alerta->concluido_em->format('d/m/Y H:i') }}
                                    </p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <span class="px-2 py-0.5 text-xs font-medium rounded
                                        @if($alerta->status === 'pendente')
                                            @if($alerta->isVencido()) bg-red-100 text-red-700
                                            @elseif($alerta->isProximo()) bg-yellow-100 text-yellow-700
                                            @else bg-blue-100 text-blue-700
                                            @endif
                                        @elseif($alerta->status === 'concluido') bg-green-100 text-green-700
                                        @else bg-slate-100 text-slate-700
                                        @endif">
                                        @if($alerta->status === 'pendente')
                                            @if($alerta->isVencido()) Vencido
                                            @elseif($alerta->isProximo()) Próximo
                                            @else Pendente
                                            @endif
                                        @elseif($alerta->status === 'concluido') Concluído
                                        @else {{ ucfirst($alerta->status) }}
                                        @endif
                                    </span>
                                    @if($alerta->status !== 'concluido')
                                    <form action="{{ route('company.processos.alertas.concluir', [$processo->id, $alerta->id]) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                            class="p-1.5 text-green-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors"
                                            title="Marcar como resolvido"
                                            onclick="return confirm('Confirma que este alerta foi resolvido?')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="px-6 py-8 text-center">
                        <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <p class="mt-2 text-sm text-slate-500">Nenhum alerta cadastrado</p>
                    </div>
                    @endif
                </div>
                <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
                    <button type="button" @click="modalAlertas = false" class="h-8 px-4 bg-white text-slate-700 text-sm font-medium rounded-lg border border-slate-300 hover:bg-slate-50 transition-colors">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Visualizador de Documento --}}
    <div x-show="modalVisualizador" x-cloak class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" @click="modalVisualizador = false"></div>
        <div class="fixed inset-2 sm:inset-6 flex flex-col">
            {{-- Header --}}
            <div class="bg-white rounded-t-xl px-4 py-2.5 flex items-center justify-between gap-3 shadow-lg">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="text-base">📄</span>
                    <span class="text-sm font-medium text-slate-900 truncate" x-text="documentoNome"></span>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="documentoUrl.replace('/visualizar', '/download')" class="h-8 px-3 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download
                    </a>
                    <button type="button" @click="modalVisualizador = false" class="p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            {{-- Content --}}
            <div class="flex-1 bg-slate-100 rounded-b-xl overflow-hidden">
                <template x-if="['pdf'].includes(documentoExtensao.toLowerCase())">
                    <iframe :src="documentoUrl" class="w-full h-full border-0"></iframe>
                </template>
                <template x-if="['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(documentoExtensao.toLowerCase())">
                    <div class="w-full h-full flex items-center justify-center p-4 overflow-auto">
                        <img :src="documentoUrl" :alt="documentoNome" class="max-w-full max-h-full object-contain shadow-lg rounded">
                    </div>
                </template>
                <template x-if="!['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'].includes(documentoExtensao.toLowerCase())">
                    <div class="w-full h-full flex flex-col items-center justify-center p-8">
                        <svg class="w-16 h-16 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-slate-600 text-center mb-4">Este tipo de arquivo não pode ser visualizado no navegador.</p>
                        <a :href="documentoUrl.replace('/visualizar', '/download')" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                            Fazer Download
                        </a>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Modal Reenvio de Documento Rejeitado --}}
    <div x-show="modalReenvio" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;"
         x-data="{ 
             fileReenvio: null, 
             fileReenvioSize: '', 
             enviandoReenvio: false,
             dragover: false,
             handleFile(e) {
                 const file = e.target.files[0] || (e.dataTransfer && e.dataTransfer.files[0]);
                 if (file) {
                     this.fileReenvio = file;
                     this.fileReenvioSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                 }
                 this.dragover = false;
             },
             resetFile() {
                 this.fileReenvio = null;
                 this.fileReenvioSize = '';
                 this.$refs.fileReenvioInput.value = '';
             },
             async enviarReenvio() {
                 if (!this.fileReenvio || this.enviandoReenvio) return;
                 
                 this.enviandoReenvio = true;
                 const formData = new FormData();
                 formData.append('arquivo', this.fileReenvio);
                 formData.append('documento_id', docReenvioId);
                 formData.append('_token', '{{ csrf_token() }}');
                 
                 try {
                     const response = await fetch('{{ route('company.processos.upload', $processo->id) }}', {
                         method: 'POST',
                         body: formData,
                         headers: {
                             'X-Requested-With': 'XMLHttpRequest',
                             'Accept': 'application/json'
                         }
                     });
                     
                     if (response.ok) {
                         alert('Documento reenviado com sucesso! Aguarde a aprovação.');
                         window.location.reload();
                     } else {
                         const data = await response.json();
                         alert(data.message || 'Erro ao reenviar documento');
                     }
                 } catch (error) {
                     console.error('Erro:', error);
                     alert('Erro ao reenviar documento. Tente novamente.');
                 } finally {
                     this.enviandoReenvio = false;
                 }
             }
         }">
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" @click="modalReenvio = false; resetFile()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg" @click.stop>
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-slate-100 bg-red-50 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Reenviar Documento</h3>
                                <p class="text-xs text-slate-500">Substitua o documento rejeitado</p>
                            </div>
                        </div>
                        <button type="button" @click="modalReenvio = false; resetFile()" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                {{-- Content --}}
                <div class="p-6 space-y-4">
                    {{-- Info do documento rejeitado --}}
                    <div class="p-4 bg-red-50 border border-red-200 rounded-xl">
                        <p class="text-sm font-medium text-red-800 mb-1">Documento rejeitado:</p>
                        <p class="text-sm text-red-700" x-text="docReenvioNome"></p>
                        <template x-if="docReenvioMotivo">
                            <div class="mt-2 pt-2 border-t border-red-200">
                                <p class="text-xs text-red-600"><strong>Motivo:</strong> <span x-text="docReenvioMotivo"></span></p>
                            </div>
                        </template>
                    </div>
                    
                    {{-- Aviso --}}
                    <div class="flex items-start gap-3 p-3 bg-blue-50 border border-blue-200 rounded-xl">
                        <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-blue-800">
                            O novo arquivo substituirá o documento rejeitado. O histórico de rejeições será mantido.
                        </p>
                    </div>
                    
                    {{-- Área de Upload --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Novo Arquivo *</label>
                        <div class="relative"
                             @dragover.prevent="dragover = true"
                             @dragleave.prevent="dragover = false"
                             @drop.prevent="handleFile($event)">
                            <input type="file" x-ref="fileReenvioInput"
                                   @change="handleFile($event)"
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                   accept=".pdf">
                            <div class="border-2 border-dashed rounded-xl p-6 text-center transition-all"
                                 :class="dragover ? 'border-blue-500 bg-blue-50' : (fileReenvio ? 'border-green-400 bg-green-50' : 'border-slate-300 hover:border-blue-400 hover:bg-slate-50')">
                                <template x-if="!fileReenvio">
                                    <div>
                                        <svg class="w-10 h-10 mx-auto text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                        <p class="text-sm text-slate-600 mb-1">
                                            <span class="text-blue-600 font-medium">Clique para selecionar</span> ou arraste o arquivo
                                        </p>
                                        <p class="text-xs text-slate-500">Apenas PDF (máx. 30MB)</p>
                                    </div>
                                </template>
                                <template x-if="fileReenvio">
                                    <div class="flex items-center justify-center gap-3">
                                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div class="text-left">
                                            <p class="text-sm font-medium text-slate-900" x-text="fileReenvio.name"></p>
                                            <p class="text-xs text-slate-500" x-text="fileReenvioSize"></p>
                                        </div>
                                        <button type="button" @click.stop="resetFile()" class="p-1 text-red-500 hover:bg-red-50 rounded">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Footer --}}
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 rounded-b-2xl flex justify-end gap-3">
                    <button type="button" @click="modalReenvio = false; resetFile()" 
                            class="px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="button" @click="enviarReenvio()"
                            :disabled="!fileReenvio || enviandoReenvio"
                            class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="!enviandoReenvio">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                        </template>
                        <template x-if="enviandoReenvio">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span x-text="enviandoReenvio ? 'Enviando...' : 'Reenviar Documento'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.recarregarAposVisualizarDocumentoPrazo = function () {
        if (window.__recarregandoAposVisualizarDocumentoPrazo) {
            return;
        }

        window.__recarregandoAposVisualizarDocumentoPrazo = true;
        window.setTimeout(() => {
            window.location.reload();
        }, 1800);
    };

    document.addEventListener('DOMContentLoaded', () => {
        const pendentesWrapper = document.getElementById('pendentes-wrapper');
        const pendentesList = document.getElementById('pendentes-list');
        const pendentesCount = document.getElementById('pendentes-count');
        const pendentesCountResumo = document.getElementById('pendentes-count-resumo');
        const pendentesEmpty = document.getElementById('pendentes-empty');
        const csrfToken = '{{ csrf_token() }}';

        const abrirModalDocumento = (doc) => {
            const root = document.querySelector('[data-processo-root]');
            if (!root || !window.Alpine) return;
            const data = window.Alpine.$data(root);
            if (!data) return;
            data.documentoUrl = doc.visualizar_url || '';
            data.documentoNome = doc.nome_original || '';
            data.documentoExtensao = doc.extensao || '';
            data.modalVisualizador = true;
        };

        const criarItemPendente = (doc) => {
            const item = document.createElement('div');
            item.className = 'px-4 py-2 flex items-center justify-between hover:bg-slate-50 transition-colors gap-3';
            item.dataset.pendenteDocId = doc.id;

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'group flex items-center gap-2.5 text-left flex-1 min-w-0';
            button.addEventListener('click', () => abrirModalDocumento(doc));

            const icon = document.createElement('span');
            icon.className = 'w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center text-base flex-shrink-0';
            icon.textContent = doc.icone || '📄';

            const info = document.createElement('div');
            info.className = 'min-w-0';

            const nome = document.createElement('p');
            nome.className = 'text-[13px] font-medium text-slate-900 group-hover:text-blue-700 truncate';
            nome.textContent = doc.nome_original || 'Documento';

            const meta = document.createElement('p');
            meta.className = 'text-[11px] text-slate-500';
            const metaParts = [];
            if (doc.tamanho_formatado) metaParts.push(doc.tamanho_formatado);
            if (doc.created_at) metaParts.push(doc.created_at);
            meta.textContent = metaParts.join(' • ');

            info.appendChild(nome);
            info.appendChild(meta);
            button.appendChild(icon);
            button.appendChild(info);

            const actions = document.createElement('div');
            actions.className = 'flex items-center gap-1 flex-shrink-0';

            const badge = document.createElement('span');
            badge.className = 'text-[11px] font-medium px-2 py-0.5 rounded-full ring-1 ring-inset bg-amber-50 text-amber-700 ring-amber-200';
            badge.textContent = 'Em análise';
            actions.appendChild(badge);

            if (doc.pode_excluir && doc.delete_url) {
                const form = document.createElement('form');
                form.action = doc.delete_url;
                form.method = 'POST';
                form.setAttribute('onsubmit', 'return confirm(\'Tem certeza que deseja excluir este arquivo?\')');

                const inputToken = document.createElement('input');
                inputToken.type = 'hidden';
                inputToken.name = '_token';
                inputToken.value = csrfToken;

                const inputMethod = document.createElement('input');
                inputMethod.type = 'hidden';
                inputMethod.name = '_method';
                inputMethod.value = 'DELETE';

                const buttonDelete = document.createElement('button');
                buttonDelete.type = 'submit';
                buttonDelete.className = 'w-7 h-7 inline-flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors';
                buttonDelete.title = 'Excluir';
                buttonDelete.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                `;

                form.appendChild(inputToken);
                form.appendChild(inputMethod);
                form.appendChild(buttonDelete);
                actions.appendChild(form);
            }

            item.appendChild(button);
            item.appendChild(actions);

            return item;
        };

        window.addEventListener('company:documento-enviado', (event) => {
            const doc = event.detail && event.detail.documento ? event.detail.documento : null;
            if (!doc || !pendentesList || !pendentesCount) return;
            if (pendentesList.querySelector(`[data-pendente-doc-id="${doc.id}"]`)) return;

            const novoItem = criarItemPendente(doc);
            pendentesList.prepend(novoItem);

            const atual = parseInt(pendentesCount.textContent || '0', 10) || 0;
            const novoTotal = atual + 1;
            pendentesCount.textContent = String(novoTotal);
            if (pendentesCountResumo) pendentesCountResumo.textContent = String(novoTotal);

            if (pendentesWrapper) pendentesWrapper.classList.remove('hidden');
            if (pendentesEmpty) pendentesEmpty.classList.add('hidden');
        });
    });
</script>
@endsection
