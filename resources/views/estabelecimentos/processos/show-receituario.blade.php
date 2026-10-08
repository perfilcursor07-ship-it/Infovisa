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
    $totalAtivas = $requisicoes->where('status', '!=', 'cancelada')->count();
    $aguardando = $contagem[0]['total'];
    $abertoPor = $processo->aberto_por_externo
        ? (\App\Models\UsuarioExterno::find($processo->usuario_externo_id)?->nome ?? 'Usuário externo') . ' (empresa)'
        : ($processo->usuario?->nome ?? 'Vigilância Sanitária');
    $voltarUrl = $receituario
        ? route('admin.receituarios.show', $receituario->id)
        : route('admin.estabelecimentos.processos.index', $estabelecimento->id);
    $dados = $receituario ? $Req::dadosRequisitante($receituario) : null;
    $primeiraPendente = $requisicoes->firstWhere('status', 'enviada')?->id ?? $requisicoes->first()?->id;
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
            <div id="historico" class="scroll-mt-4 bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <h3 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2.5">Histórico</h3>
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
        </div>

        {{-- Coluna direita --}}
        <div class="space-y-4 min-w-0">
            {{-- Requisições --}}
            <section id="requisicoes" class="scroll-mt-4 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" x-data="{ aberta: {{ $primeiraPendente ?? 'null' }} }">
                <header class="px-4 py-3 border-b border-blue-100 bg-gradient-to-r from-blue-50 to-white flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Requisições de receita</h2>
                        <p class="text-[11px] text-slate-500">Pedidos de notificação/numeração enviados pela empresa. Cada pedido é individual — clique para ver os detalhes.</p>
                    </div>
                </header>

                @forelse($requisicoes as $requisicao)
                @php
                    $situacao = $requisicao->situacao;
                    $r = $requisicao->requisitante ?? [];
                @endphp
                <article class="border-b border-slate-100 last:border-0">
                    <button type="button" @click="aberta = aberta === {{ $requisicao->id }} ? null : {{ $requisicao->id }}"
                            class="w-full text-left px-4 py-3 flex flex-col md:flex-row md:items-center gap-2 md:gap-4 hover:bg-slate-50 transition"
                            :class="aberta === {{ $requisicao->id }} ? 'bg-slate-50' : ''">
                        <div class="md:w-36 flex-shrink-0">
                            <p class="text-sm font-bold text-slate-900 tabular-nums">Nº {{ $requisicao->numero }}</p>
                            <p class="text-[11px] text-slate-500">{{ $requisicao->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="flex-1 min-w-0 flex flex-wrap gap-1.5">
                            @foreach($requisicao->resumoQuantidades() as $modalidade)
                                @foreach($modalidade['itens'] as $item)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-slate-100 text-[11px] text-slate-700">
                                    <span class="text-slate-400">{{ $modalidade['rotulo'] }}</span>
                                    <span class="font-bold">{{ $item['tipo'] }}</span>
                                    <span class="tabular-nums">× {{ $item['quantidade'] }}</span>
                                </span>
                                @endforeach
                            @endforeach
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span class="text-xs text-slate-500 tabular-nums">{{ $requisicao->totalBlocos() }} bloco(s)</span>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset {{ $situacao['classe'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $situacao['dot'] }}"></span>{{ $situacao['label'] }}
                            </span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="aberta === {{ $requisicao->id }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </button>

                    <div x-show="aberta === {{ $requisicao->id }}" x-cloak class="px-4 pb-4 pt-1 space-y-4" x-data="{ verDeclaracoes: false }">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[480px] text-sm border border-slate-200 rounded-lg overflow-hidden">
                                <thead>
                                    <tr class="bg-slate-50 text-xs text-slate-600">
                                        <th class="px-3 py-2 text-left font-semibold">Blocos</th>
                                        @foreach($Req::TIPOS_NOTIFICACAO as $tipo)
                                        <th class="px-3 py-2 text-center font-semibold">{{ $tipo }}</th>
                                        @endforeach
                                        <th class="px-3 py-2 text-center font-semibold">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($Req::MODALIDADES as $chave => $rotulo)
                                    @php $linha = $requisicao->quantidades[$chave] ?? []; @endphp
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-slate-700">{{ $rotulo }}</td>
                                        @foreach($Req::TIPOS_NOTIFICACAO as $tipo)
                                        @php $qtd = (int) ($linha[$tipo] ?? 0); @endphp
                                        <td class="px-3 py-2 text-center tabular-nums {{ $qtd ? 'font-bold text-slate-900' : 'text-slate-300' }}">
                                            {{ $qtd ?: '—' }}
                                            @if($requisicao->quantidades_liberadas !== null && $qtd)
                                            <span class="block text-[10px] font-semibold text-emerald-600">liberado: {{ (int) ($requisicao->quantidades_liberadas[$chave][$tipo] ?? 0) }}</span>
                                            @endif
                                        </td>
                                        @endforeach
                                        <td class="px-3 py-2 text-center font-semibold tabular-nums text-slate-700">{{ array_sum(array_map('intval', $linha)) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Justificativa</p>
                                <p class="mt-0.5 {{ $requisicao->justificativa ? 'text-slate-800 whitespace-pre-line' : 'text-slate-400' }}">{{ $requisicao->justificativa ?: 'Não informada' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Assinatura eletrônica</p>
                                <p class="mt-0.5 text-slate-800">{{ $requisicao->usuarioExterno?->nome ?? 'Usuário externo' }}</p>
                                <p class="text-xs text-slate-500">{{ $requisicao->assinado_em?->format('d/m/Y H:i:s') }}{{ $requisicao->ip_address ? ' · IP ' . $requisicao->ip_address : '' }}</p>
                            </div>
                        </div>

                        @if(!empty($r['conselho']) || !empty($r['especialidade']))
                        <p class="text-xs text-slate-500">Dados do requisitante no envio: {{ $r['nome'] ?? '' }}{{ !empty($r['conselho']) ? ' · ' . $r['conselho'] : '' }}{{ !empty($r['especialidade']) ? ' · ' . $r['especialidade'] : '' }}</p>
                        @endif

                        @if($requisicao->observacao_vigilancia)
                        <div class="rounded-lg border px-3 py-2 text-sm {{ $requisicao->status === 'indeferida' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-emerald-50 border-emerald-200 text-emerald-800' }}">
                            <span class="font-semibold">Resposta da Vigilância:</span> {{ $requisicao->observacao_vigilancia }}
                            @if($requisicao->analisadoPor)<span class="text-xs opacity-75"> — {{ $requisicao->analisadoPor->nome }}, {{ $requisicao->analisado_em?->format('d/m/Y H:i') }}</span>@endif
                        </div>
                        @endif

                        @if($requisicao->status === 'cancelada')
                        <p class="text-xs text-slate-500">Cancelada pela empresa em {{ $requisicao->cancelado_em?->format('d/m/Y H:i') }}.</p>
                        @endif

                        <div>
                            <button type="button" @click="verDeclaracoes = !verDeclaracoes" class="text-xs font-medium text-blue-700 hover:underline"
                                    x-text="verDeclaracoes ? 'Ocultar declarações aceitas' : 'Ver declarações aceitas ({{ count($requisicao->declaracoes ?? []) }})'"></button>
                            <ul x-show="verDeclaracoes" x-cloak class="mt-2 space-y-1.5 rounded-lg bg-slate-50 p-3 text-xs text-slate-600 leading-relaxed list-disc list-inside">
                                @foreach($requisicao->declaracoes ?? [] as $declaracao)
                                <li>{{ $declaracao['texto'] ?? '' }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
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
            </section>

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
