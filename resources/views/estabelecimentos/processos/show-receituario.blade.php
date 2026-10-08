@extends('layouts.admin')

@section('title', 'Processo ' . $processo->numero_processo)
@section('page-title', 'Detalhes do Processo')

@section('content')
@php
    $Req = \App\Models\ReceituarioRequisicao::class;
    $arquivado = $processo->status === 'arquivado';
    [$statusRotulo, $statusBadge, $statusDot] = match ($processo->status) {
        'arquivado' => ['Arquivado', 'bg-slate-100 text-slate-700 ring-slate-200', 'bg-slate-400'],
        'parado' => ['Parado', 'bg-red-50 text-red-700 ring-red-200', 'bg-red-500'],
        default => ['Aberto', 'bg-blue-50 text-blue-700 ring-blue-200', 'bg-blue-500'],
    };
    $contagem = [
        ['rotulo' => 'Aguardando', 'total' => $requisicoes->whereIn('status', ['enviada', 'em_analise'])->count(), 'cor' => 'text-amber-600'],
        ['rotulo' => 'Liberadas', 'total' => $requisicoes->where('status', 'liberada')->count(), 'cor' => 'text-emerald-600'],
        ['rotulo' => 'Indeferidas', 'total' => $requisicoes->where('status', 'indeferida')->count(), 'cor' => 'text-red-600'],
    ];
    $aguardandoRequisicoes = $requisicoes->whereIn('status', ['enviada', 'em_analise'])->count();
    $filtroInicialRequisicoes = $aguardandoRequisicoes > 0 ? 'aguardando' : 'todas';
    $filtrosRequisicoes = [];
    if ($aguardandoRequisicoes > 0) {
        $filtrosRequisicoes[] = ['id' => 'aguardando', 'rotulo' => 'Aguardando análise', 'total' => $aguardandoRequisicoes, 'ativa' => 'bg-amber-500 text-white'];
    }
    $filtrosRequisicoes[] = ['id' => 'todas', 'rotulo' => 'Todas', 'total' => $requisicoes->count(), 'ativa' => 'bg-slate-700 text-white'];
    $filtrosRequisicoes[] = ['id' => 'liberada', 'rotulo' => 'Liberadas', 'total' => $requisicoes->where('status', 'liberada')->count(), 'ativa' => 'bg-emerald-600 text-white'];
    $filtrosRequisicoes[] = ['id' => 'indeferida', 'rotulo' => 'Indeferidas', 'total' => $requisicoes->where('status', 'indeferida')->count(), 'ativa' => 'bg-rose-600 text-white'];
    $filtrosRequisicoes[] = ['id' => 'cancelada', 'rotulo' => 'Canceladas', 'total' => $requisicoes->where('status', 'cancelada')->count(), 'ativa' => 'bg-slate-500 text-white'];
    $totalAtivas = $requisicoes->where('status', '!=', 'cancelada')->count();
    $aguardando = $contagem[0]['total'];
    $abertoPor = $processo->aberto_por_externo
        ? (\App\Models\UsuarioExterno::find($processo->usuario_externo_id)?->nome ?? 'Usuário externo') . ' (empresa)'
        : ($processo->usuario?->nome ?? 'Vigilância Sanitária');
    $voltarUrl = $receituario
        ? route('admin.receituarios.show', $receituario->id)
        : route('admin.estabelecimentos.processos.index', $estabelecimento->id);
    $dados = $receituario ? $Req::dadosRequisitante($receituario) : null;
    $primeiraPendente = $requisicoes->first(fn ($requisicao) => in_array($requisicao->status, ['enviada', 'em_analise'], true))?->id ?? $requisicoes->first()?->id;
    $acompanhando = $processo->acompanhamentos->where('usuario_interno_id', auth('interno')->id())->first();

    $itemMenu = 'w-full flex items-center gap-2.5 px-2 py-1.5 text-[13px] font-medium text-slate-700 hover:bg-slate-50 rounded-lg transition-colors';
@endphp

<div class="max-w-8xl mx-auto" x-data="{ modalArquivar: false, modalUpload: false }">

    @foreach(['success' => 'emerald', 'error' => 'red'] as $chave => $cor)
        @if(session($chave))
        <div class="mb-4 px-4 py-2.5 rounded-lg border text-sm {{ $cor === 'emerald' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800' }}">{{ session($chave) }}</div>
        @endif
    @endforeach
    @if($errors->any())
    <div class="mb-4 px-4 py-2.5 rounded-lg border bg-red-50 border-red-200 text-sm text-red-800">
        @foreach($errors->all() as $erro)<p>{{ $erro }}</p>@endforeach
    </div>
    @endif

    {{-- Card superior: processo + profissional --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-4 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <a href="{{ $voltarUrl }}" title="Voltar"
                   class="w-8 h-8 flex-shrink-0 inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div class="w-10 h-10 flex-shrink-0 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Processo · Receituário</p>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-lg font-semibold text-slate-900 tabular-nums leading-tight">{{ $processo->numero_processo }}</h2>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold ring-1 ring-inset {{ $statusBadge }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $statusDot }}"></span>{{ $statusRotulo }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-600">Ano {{ $processo->ano }}</span>
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.estabelecimentos.processos.toggleAcompanhamento', [$estabelecimento->id, $processo->id]) }}" class="flex-shrink-0">
                @csrf
                @if($acompanhando)
                <button type="submit" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-red-600 bg-red-50 ring-1 ring-inset ring-red-200 rounded-lg hover:bg-red-100 transition-colors">
                    Parar de Acompanhar
                </button>
                @else
                <button type="submit" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Acompanhar Processo
                </button>
                @endif
            </form>
        </div>

        <div class="px-4 py-3">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                {{ $receituario?->tipo === 'medico' ? 'Profissional' : 'Requisitante' }}
            </p>
            @if($dados)
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('admin.receituarios.show', $receituario->id) }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900 hover:underline underline-offset-2">{{ $dados['nome'] }}</a>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset {{ $receituario->situacao['classe'] }}">{{ $receituario->situacao['label'] }}</span>
                <span class="text-[11px] text-slate-500">{{ $dados['tipo_nome'] }}</span>
            </div>
            <div class="mt-2.5 grid grid-cols-2 md:grid-cols-[auto_auto_auto_auto_minmax(0,1fr)] gap-x-8 gap-y-2">
                @foreach([
                    'CPF/CNPJ' => $dados['documento'],
                    'Conselho' => $dados['conselho'],
                    'Especialidade' => $dados['especialidade'],
                    'Telefone' => $receituario->telefone,
                    'Endereço' => trim(($dados['endereco'] ?? '') . (!empty($dados['municipio']) ? ' - ' . $dados['municipio'] : '') . (!empty($dados['cep']) ? ' · CEP ' . $dados['cep'] : '')),
                ] as $rotulo => $valor)
                <div class="min-w-0 {{ $rotulo === 'Endereço' ? 'col-span-2 md:col-span-1' : '' }}">
                    <label class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">{{ $rotulo }}</label>
                    <p class="text-[13px] font-medium {{ $valor ? 'text-slate-800' : 'text-slate-400' }} leading-snug">{{ $valor ?: 'Não informado' }}</p>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-slate-500">Cadastro de receituário não encontrado.</p>
            @endif
            <p class="mt-2.5 pt-2.5 border-t border-slate-100 text-xs text-slate-500">Aberto em {{ $processo->created_at->format('d/m/Y H:i') }} por {{ $abertoPor }}</p>
        </div>
    </div>

    @if($arquivado)
    <div class="mb-4 px-4 py-2.5 rounded-lg bg-slate-100 border border-slate-200 text-sm text-slate-700">
        <span class="font-semibold">Processo arquivado</span>
        {{ $processo->data_arquivamento ? 'em ' . $processo->data_arquivamento->format('d/m/Y H:i') : '' }}
        @if($processo->motivo_arquivamento) — {{ $processo->motivo_arquivamento }} @endif
    </div>
    @elseif($aguardando > 0)
    <a href="#requisicoes" class="mb-4 flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 hover:bg-amber-100/70 transition">
        <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>
        <p class="text-sm"><span class="font-semibold text-amber-900">{{ $aguardando }} requisição(ões) aguardando análise</span><span class="text-slate-600"> — confira as quantidades solicitadas abaixo.</span></p>
    </a>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-[17rem_minmax(0,1fr)] xl:grid-cols-[19rem_minmax(0,1fr)] gap-4 items-start">
        {{-- Coluna esquerda --}}
        <div class="space-y-4 min-w-0">
            {{-- Resumo das requisições --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                <div class="px-4 pt-3 pb-2.5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Requisições</h3>
                        <span class="text-sm font-semibold text-slate-800 tabular-nums">{{ $totalAtivas }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Pedidos de notificação e numeração de receita</p>
                </div>
                <div class="grid grid-cols-3 border-t border-slate-100 divide-x divide-slate-100 text-center">
                    @foreach($contagem as $item)
                    <a href="#requisicoes" class="py-2 hover:bg-slate-50 transition-colors">
                        <p class="text-sm font-semibold tabular-nums {{ $item['total'] ? $item['cor'] : 'text-slate-400' }}">{{ $item['total'] }}</p>
                        <p class="text-[10px] text-slate-500">{{ $item['rotulo'] }}</p>
                    </a>
                    @endforeach
                </div>
            </div>

            {{-- Menu de Opções --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-3">
                <h3 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5 px-2 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    Menu de Opções
                </h3>
                <div class="space-y-0.5">
                    @unless($arquivado)
                    <a href="{{ route('admin.documentos.create', ['processo_id' => $processo->id]) }}" class="{{ $itemMenu }} [&>svg]:text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Criar Documento Digital
                    </a>
                    <button type="button" @click="modalUpload = true" class="{{ $itemMenu }} [&>svg]:text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        Upload de Arquivos
                    </button>
                    @else
                    <p class="px-2 py-1.5 text-xs text-slate-400">Processo arquivado.</p>
                    @endunless
                </div>
            </div>

            {{-- Ações do Processo --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-3">
                <h3 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5 px-2 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Ações do Processo
                </h3>
                <div class="space-y-0.5">
                    <a href="{{ route('admin.estabelecimentos.processos.integra', [$estabelecimento->id, $processo->id]) }}" class="{{ $itemMenu }} [&>svg]:text-blue-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Processo na Íntegra
                    </a>
                    @if($arquivado)
                    <form method="POST" action="{{ route('admin.estabelecimentos.processos.desarquivar', [$estabelecimento->id, $processo->id]) }}" onsubmit="return confirm('Desarquivar este processo?')">
                        @csrf
                        <button type="submit" class="{{ $itemMenu }} [&>svg]:text-emerald-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Desarquivar Processo
                        </button>
                    </form>
                    @else
                    <button type="button" @click="modalArquivar = true" class="{{ $itemMenu }} [&>svg]:text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                        Arquivar Processo
                    </button>
                    @endif
                </div>
            </div>

            {{-- Histórico --}}
            <details id="historico" class="scroll-mt-4 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <summary class="cursor-pointer px-4 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider hover:bg-slate-50">
                    Histórico <span class="ml-1 text-slate-400">({{ $eventos->count() }})</span>
                </summary>
                <div class="px-4 pb-4">
                    <ol class="relative border-l border-slate-200 ml-1.5 space-y-3">
                        @forelse($eventos as $evento)
                        <li class="pl-4 relative">
                            <span class="absolute -left-[5px] top-1.5 w-2.5 h-2.5 rounded-full bg-slate-300 ring-2 ring-white"></span>
                            <p class="text-xs font-medium text-slate-800">{{ $evento->titulo }}</p>
                            @if($evento->descricao)<p class="text-[11px] text-slate-500 leading-snug">{{ $evento->descricao }}</p>@endif
                            <p class="text-[10px] text-slate-400">{{ $evento->created_at->format('d/m/Y H:i') }}{{ $evento->usuario ? ' · ' . $evento->usuario->nome : '' }}</p>
                        </li>
                        @empty
                        <li class="pl-4 text-xs text-slate-400">Sem eventos.</li>
                        @endforelse
                    </ol>
                </div>
            </details>
        </div>

        {{-- Coluna direita --}}
        <div class="space-y-4 min-w-0">
            {{-- Requisições --}}
            <section id="requisicoes" class="scroll-mt-4 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" x-data="{ filtroRequisicoes: @js($filtroInicialRequisicoes) }">
                <header class="px-4 py-3 border-b border-slate-100 bg-white flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Requisições de receita</h2>
                        <p class="text-[11px] text-slate-500">Pedidos de notificação/numeração enviados pela empresa. Clique em um pedido para ver os dados e analisar.</p>
                    </div>
                </header>

                <div class="px-4 py-2.5 border-b border-slate-100 bg-slate-50/70">
                    <div role="group" aria-label="Filtrar requisições por situação" class="inline-flex max-w-full gap-1 overflow-x-auto rounded-xl bg-slate-100 p-1">
                        @foreach($filtrosRequisicoes as $filtro)
                        <button type="button" :aria-pressed="filtroRequisicoes === @js($filtro['id'])"
                                @click="filtroRequisicoes = @js($filtro['id'])"
                                :class="filtroRequisicoes === @js($filtro['id']) ? @js($filtro['ativa']) + ' shadow-sm' : 'text-slate-600 hover:bg-white'"
                                class="inline-flex min-h-9 flex-shrink-0 items-center gap-1.5 rounded-lg px-3 text-xs font-semibold transition-colors">
                            {{ $filtro['rotulo'] }}
                            <span class="rounded-full px-1.5 py-0.5 text-[10px] tabular-nums"
                                  :class="filtroRequisicoes === @js($filtro['id']) ? 'bg-white/20 text-white' : 'bg-white text-slate-500'">{{ $filtro['total'] }}</span>
                        </button>
                        @endforeach
                    </div>
                </div>

                @forelse($requisicoes as $requisicao)
                @php
                    $situacao = $requisicao->situacao;
                    $r = $requisicao->requisitante ?? [];
                    $grupoFiltro = in_array($requisicao->status, ['enviada', 'em_analise'], true) ? 'aguardando' : $requisicao->status;
                    $bordaSituacao = match ($requisicao->status) {
                        'enviada' => 'border-l-blue-400',
                        'em_analise' => 'border-l-amber-400',
                        'liberada' => 'border-l-emerald-500',
                        'indeferida' => 'border-l-rose-500',
                        'cancelada' => 'border-l-slate-300',
                        default => 'border-l-slate-200',
                    };
                @endphp
                <article x-show="filtroRequisicoes === 'todas' || filtroRequisicoes === @js($grupoFiltro)" class="border-b border-l-2 {{ $bordaSituacao }} border-slate-100 last:border-0">
                    <a href="{{ route('admin.estabelecimentos.processos.requisicoes.show', [$estabelecimento->id, $processo->id, $requisicao->id]) }}"
                       class="group w-full text-left px-4 py-3.5 flex flex-col md:flex-row md:items-center gap-2.5 md:gap-4 bg-white hover:bg-slate-50 transition">
                        <div class="md:w-40 flex-shrink-0">
                            <p class="text-sm font-bold text-slate-900 tabular-nums">Nº {{ $requisicao->numero }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">{{ $requisicao->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="flex-1 min-w-0 flex flex-wrap gap-2">
                            @foreach($requisicao->resumoQuantidades() as $modalidade)
                                @php
                                    $corModalidade = $modalidade['rotulo'] === 'Física'
                                        ? 'border-cyan-100 bg-cyan-50 text-cyan-900'
                                        : 'border-violet-100 bg-violet-50 text-violet-900';
                                @endphp
                                <span class="inline-flex flex-wrap items-center gap-1.5 rounded-md border px-2 py-1 {{ $corModalidade }}">
                                    <span class="text-[10px] font-semibold">{{ $modalidade['rotulo'] }}</span>
                                @foreach($modalidade['itens'] as $item)
                                    <span class="inline-flex items-center gap-1 rounded bg-white/80 px-1.5 py-0.5 text-[11px]">
                                        <span class="font-bold">{{ $item['tipo'] }}</span>
                                        <span class="tabular-nums">× {{ $item['quantidade'] }}</span>
                                    </span>
                                @endforeach
                                </span>
                            @endforeach
                        </div>
                        <div class="flex items-center justify-between gap-2 md:justify-end flex-shrink-0">
                            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-700 tabular-nums">{{ $requisicao->totalBlocos() }} bloco(s)</span>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset {{ $situacao['classe'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $situacao['dot'] }}"></span>{{ $situacao['label'] }}
                            </span>
                            <svg class="w-4 h-4 text-slate-300 group-hover:text-blue-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </a>

                </article>
                @empty
                <div class="px-4 py-12 text-center">
                    <div class="w-11 h-11 mx-auto rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mb-2.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-800">Nenhuma requisição ainda</p>
                    <p class="text-xs text-slate-500 mt-1">Quando a empresa enviar um pedido de notificação de receita, ele aparece aqui.</p>
                </div>
                @endforelse
                @foreach($filtrosRequisicoes as $filtro)
                    @if($filtro['id'] !== 'todas' && $filtro['total'] === 0)
                    <div x-cloak x-show="filtroRequisicoes === @js($filtro['id'])" class="px-4 py-8 text-center text-xs text-slate-500">
                        Nenhuma requisição nesta situação.
                    </div>
                    @endif
                @endforeach
            </section>

            @if($documentosDigitais->isNotEmpty() || $arquivos->isNotEmpty())
            {{-- Documentos do processo (separado das requisições) --}}
            <div class="flex items-center gap-3 pt-2">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider whitespace-nowrap">Documentos do processo</span>
                <span class="flex-1 h-px bg-slate-200"></span>
            </div>
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <header class="px-4 py-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                                Documentos e arquivos
                                <span class="px-1.5 py-0.5 rounded-full bg-slate-100 text-[10px] font-semibold text-slate-600">{{ $documentosDigitais->count() + $arquivos->count() }}</span>
                            </h2>
                            <p class="text-[11px] text-slate-500">Documentos digitais da Vigilância, ofícios e arquivos anexados ao processo</p>
                        </div>
                    </div>
                    @unless($arquivado)
                    <div class="flex gap-2 self-start sm:self-auto">
                        <a href="{{ route('admin.documentos.create', ['processo_id' => $processo->id]) }}"
                           class="inline-flex items-center h-8 px-3 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">+ Documento digital</a>
                        <button type="button" @click="modalUpload = true"
                                class="inline-flex items-center h-8 px-3 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">+ Arquivo</button>
                    </div>
                    @endunless
                </header>

                @if($documentosDigitais->isEmpty() && $arquivos->isEmpty())
                <div class="px-4 py-10 text-center">
                    <svg class="w-8 h-8 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="mt-2 text-sm text-slate-500">Nenhum documento no processo</p>
                </div>
                @else
                @php
                    $statusDigital = [
                        'rascunho' => ['Rascunho', 'bg-slate-100 text-slate-600'],
                        'aguardando_assinatura' => ['Aguardando assinatura', 'bg-amber-50 text-amber-700'],
                        'assinado' => ['Assinado', 'bg-emerald-50 text-emerald-700'],
                        'aprovado' => ['Aprovado', 'bg-emerald-50 text-emerald-700'],
                    ];
                @endphp
                @foreach($documentosDigitais as $doc)
                @php [$stRotulo, $stClasse] = $statusDigital[$doc->status] ?? [ucfirst((string) $doc->status), 'bg-slate-100 text-slate-600']; @endphp
                <div class="px-4 py-2.5 border-b border-slate-100 last:border-0 flex items-center gap-3 hover:bg-slate-50">
                    <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0" title="Documento digital">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </span>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('admin.documentos.show', $doc->id) }}" class="block text-sm font-medium text-slate-800 hover:text-blue-700 truncate">
                            {{ $doc->nome_exibicao }}@if($doc->numero_documento) <span class="text-slate-400 font-normal">nº {{ $doc->numero_documento }}</span>@endif
                        </a>
                        <p class="text-[11px] text-slate-500">Documento digital · {{ $doc->created_at->format('d/m/Y H:i') }}{{ $doc->usuarioCriador ? ' · ' . $doc->usuarioCriador->nome : '' }}</p>
                    </div>
                    @if($doc->sigiloso)<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-50 text-red-700">Sigiloso</span>@endif
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $stClasse }}">{{ $stRotulo }}</span>
                </div>
                @endforeach
                @foreach($arquivos as $arquivo)
                <div class="px-4 py-2.5 border-b border-slate-100 last:border-0 flex items-center gap-3 hover:bg-slate-50">
                    <span class="w-8 h-8 rounded-lg bg-red-50 text-red-500 flex items-center justify-center flex-shrink-0" title="Arquivo PDF">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/></svg>
                    </span>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('admin.estabelecimentos.processos.visualizar', [$estabelecimento->id, $processo->id, $arquivo->id]) }}" target="_blank"
                           class="block text-sm font-medium text-slate-800 hover:text-blue-700 truncate" title="{{ $arquivo->nome_original }}">{{ $arquivo->observacoes ?: $arquivo->nome_original }}</a>
                        <p class="text-[11px] text-slate-500">Arquivo · {{ $arquivo->created_at->format('d/m/Y H:i') }} · enviado pela {{ $arquivo->tipo_usuario === 'externo' ? 'empresa' : 'Vigilância' }}</p>
                    </div>
                    <a href="{{ route('admin.estabelecimentos.processos.download', [$estabelecimento->id, $processo->id, $arquivo->id]) }}" title="Baixar"
                       class="p-1.5 rounded-lg text-slate-400 hover:text-blue-700 hover:bg-blue-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </a>
                </div>
                @endforeach
                @endif
            </section>
            @endif
        </div>
    </div>

    {{-- Modal upload --}}
    @unless($arquivado)
    <div x-show="modalUpload" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="modalUpload = false">
        <div class="absolute inset-0 bg-slate-900/50" @click="modalUpload = false"></div>
        <form method="POST" action="{{ route('admin.estabelecimentos.processos.upload', [$estabelecimento->id, $processo->id]) }}" enctype="multipart/form-data"
              class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-5 space-y-3">
            @csrf
            <h3 class="text-base font-semibold text-slate-900">Enviar arquivo ao processo</h3>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Tipo de documento</label>
                <select name="tipo_documento" required class="w-full text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="Ofício">Ofício</option>
                    <option value="Despacho">Despacho</option>
                    <option value="Usar nome do arquivo">Usar nome do arquivo PDF</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Arquivo PDF (até 10 MB)</label>
                <input type="file" name="arquivo" accept="application/pdf" required
                       class="w-full text-sm text-slate-600 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:font-semibold hover:file:bg-blue-100">
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" @click="modalUpload = false" class="h-9 px-3.5 text-sm text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancelar</button>
                <button type="submit" class="h-9 px-3.5 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Enviar</button>
            </div>
        </form>
    </div>

    {{-- Modal arquivar --}}
    <div x-show="modalArquivar" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="modalArquivar = false">
        <div class="absolute inset-0 bg-slate-900/50" @click="modalArquivar = false"></div>
        <form method="POST" action="{{ route('admin.estabelecimentos.processos.arquivar', [$estabelecimento->id, $processo->id]) }}"
              class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-5 space-y-3">
            @csrf
            <h3 class="text-base font-semibold text-slate-900">Arquivar processo {{ $processo->numero_processo }}</h3>
            <p class="text-xs text-slate-500">A empresa não poderá enviar novas requisições enquanto o processo estiver arquivado.</p>
            <textarea name="motivo_arquivamento" rows="3" required minlength="10"
                      class="w-full text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Motivo do arquivamento (mínimo 10 caracteres)">{{ old('motivo_arquivamento') }}</textarea>
            <div class="flex justify-end gap-2">
                <button type="button" @click="modalArquivar = false" class="h-9 px-3.5 text-sm text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancelar</button>
                <button type="submit" class="h-9 px-3.5 text-sm font-semibold text-white bg-slate-800 rounded-lg hover:bg-slate-900">Arquivar</button>
            </div>
        </form>
    </div>
    @endunless
</div>
@endsection
