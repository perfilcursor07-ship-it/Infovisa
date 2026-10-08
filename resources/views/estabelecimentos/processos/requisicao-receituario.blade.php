@extends('layouts.admin')

@section('title', 'Requisição nº ' . $requisicao->numero)
@section('page-title', 'Requisição de Receituário')

@section('content')
@php
    $Req = \App\Models\ReceituarioRequisicao::class;
    $situacao = $requisicao->situacao;
    $r = $requisicao->requisitante ?? [];
    $aguardando = in_array($requisicao->status, ['enviada', 'em_analise'], true);
    $voltarUrl = route('admin.estabelecimentos.processos.show', [$estabelecimento->id, $processo->id]) . '#requisicoes';
    $liberadas = $requisicao->quantidades_liberadas;
    $totaisPorModalidade = collect($Req::MODALIDADES)->mapWithKeys(fn ($rotulo, $chave) => [$chave => array_sum(array_map('intval', $requisicao->quantidades[$chave] ?? []))]);
@endphp

<div class="max-w-8xl mx-auto space-y-4" x-data="{ avisoAprovar: false, verDeclaracoes: false }">

    {{-- Cabeçalho --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 px-4 py-3 flex flex-col lg:flex-row lg:items-center gap-3">
        <div class="flex items-center gap-3 min-w-0 flex-1">
            <a href="{{ $voltarUrl }}" title="Voltar ao processo"
               class="w-8 h-8 flex-shrink-0 inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="w-10 h-10 flex-shrink-0 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Requisição de receita · Processo {{ $processo->numero_processo }}</p>
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-lg font-semibold text-slate-900 tabular-nums leading-tight">Nº {{ $requisicao->numero }}</h2>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold ring-1 ring-inset {{ $situacao['classe'] }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $situacao['dot'] }}"></span>{{ $situacao['label'] }}
                    </span>
                </div>
                <p class="text-xs text-slate-500">Enviada em {{ $requisicao->created_at->format('d/m/Y \à\s H:i') }} por {{ $requisicao->usuarioExterno?->nome ?? 'usuário externo' }}</p>
            </div>
        </div>
        <a href="{{ $voltarUrl }}" class="self-start lg:self-auto text-xs font-semibold text-slate-600 hover:text-slate-900">← Voltar ao processo</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_22rem] gap-4 items-start">
        {{-- Coluna principal --}}
        <div class="space-y-4 min-w-0">
            {{-- Quantidades --}}
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <header class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Blocos solicitados</h3>
                        <p class="text-[11px] text-slate-500">Tipos de notificação requerida</p>
                    </div>
                    <p class="text-sm text-slate-500"><span class="text-2xl font-bold text-slate-900 tabular-nums">{{ $requisicao->totalBlocos() }}</span> bloco(s)</p>
                </header>
                <div class="p-4 overflow-x-auto">
                    <table class="w-full min-w-[520px] text-sm">
                        <thead>
                            <tr class="text-xs text-slate-500">
                                <th class="pb-2 text-left font-semibold w-32"></th>
                                @foreach($Req::TIPOS_NOTIFICACAO as $tipo)
                                <th class="pb-2 text-center font-bold text-slate-700">{{ $tipo }}</th>
                                @endforeach
                                <th class="pb-2 text-center font-semibold">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($Req::MODALIDADES as $chave => $rotulo)
                            @php $linha = $requisicao->quantidades[$chave] ?? []; @endphp
                            <tr class="border-t border-slate-100">
                                <th class="py-3 text-left text-sm font-medium text-slate-700">{{ $rotulo }}</th>
                                @foreach($Req::TIPOS_NOTIFICACAO as $tipo)
                                @php $qtd = (int) ($linha[$tipo] ?? 0); @endphp
                                <td class="py-3 text-center">
                                    @if($qtd)
                                    <span class="inline-flex items-center justify-center min-w-[2.5rem] h-9 px-2 rounded-lg bg-blue-50 text-blue-800 text-base font-bold tabular-nums ring-1 ring-inset ring-blue-200">{{ $qtd }}</span>
                                    @if($liberadas !== null)
                                    <span class="block mt-1 text-[10px] font-semibold text-emerald-600">liberado: {{ (int) ($liberadas[$chave][$tipo] ?? 0) }}</span>
                                    @endif
                                    @else
                                    <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                @endforeach
                                <td class="py-3 text-center font-semibold tabular-nums text-slate-700">{{ $totaisPorModalidade[$chave] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/60">
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Justificativa da empresa</p>
                    <p class="mt-0.5 text-sm {{ $requisicao->justificativa ? 'text-slate-800 whitespace-pre-line' : 'text-slate-400' }}">{{ $requisicao->justificativa ?: 'Não informada' }}</p>
                </div>
            </section>

            {{-- Requisitante no envio --}}
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <header class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Requisitante</h3>
                        <p class="text-[11px] text-slate-500">Dados do cadastro no momento do envio</p>
                    </div>
                    @if($receituario)
                    <a href="{{ route('admin.receituarios.show', $receituario->id) }}" class="text-xs font-semibold text-blue-700 hover:underline">Ver cadastro →</a>
                    @endif
                </header>
                <dl class="px-4 py-3 grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-3">
                    @foreach([
                        ['Nome', $r['nome'] ?? null, 'col-span-2'],
                        ['CPF/CNPJ', $r['documento'] ?? null, ''],
                        ['Conselho', $r['conselho'] ?? null, ''],
                        ['Especialidade', $r['especialidade'] ?? null, 'col-span-2'],
                        ['Município', $r['municipio'] ?? null, ''],
                        ['CEP', $r['cep'] ?? null, ''],
                        ['Endereço', $r['endereco'] ?? null, 'col-span-2 md:col-span-4'],
                    ] as [$rotulo, $valor, $classe])
                    <div class="{{ $classe }} min-w-0">
                        <dt class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">{{ $rotulo }}</dt>
                        <dd class="text-[13px] font-medium {{ $valor ? 'text-slate-800' : 'text-slate-400' }}">{{ $valor ?: 'Não informado' }}</dd>
                    </div>
                    @endforeach
                </dl>
            </section>

            {{-- Declarações e assinatura --}}
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-4 py-3 flex items-start gap-3">
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-800">
                            Assinada eletronicamente por <span class="font-semibold">{{ $requisicao->usuarioExterno?->nome ?? 'usuário externo' }}</span>
                            em {{ $requisicao->assinado_em?->format('d/m/Y \à\s H:i:s') }}{{ $requisicao->ip_address ? ' · IP ' . $requisicao->ip_address : '' }}
                        </p>
                        <button type="button" @click="verDeclaracoes = !verDeclaracoes" class="mt-1 text-xs font-medium text-blue-700 hover:underline"
                                x-text="verDeclaracoes ? 'Ocultar declarações aceitas' : 'Ver declarações aceitas ({{ count($requisicao->declaracoes ?? []) }})'"></button>
                        <ul x-show="verDeclaracoes" x-cloak class="mt-2 space-y-1.5 rounded-lg bg-slate-50 p-3 text-xs text-slate-600 leading-relaxed list-disc list-inside">
                            @foreach($requisicao->declaracoes ?? [] as $declaracao)
                            <li>{{ $declaracao['texto'] ?? '' }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </section>
        </div>

        {{-- Coluna lateral --}}
        <aside class="space-y-4 lg:sticky lg:top-4">
            {{-- Análise --}}
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Análise da Vigilância</p>
                @if($aguardando)
                    <p class="mt-1 text-sm text-slate-700">Confira as quantidades com os parâmetros de entrega e o histórico do profissional antes de aprovar.</p>
                    <button type="button" @click="avisoAprovar = true"
                            class="mt-3 w-full inline-flex items-center justify-center gap-2 h-10 text-sm font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        Aprovar requisição
                    </button>
                    <p x-show="avisoAprovar" x-cloak class="mt-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800">
                        A aprovação ainda está sendo definida e por enquanto não altera a requisição.
                    </p>
                @elseif($requisicao->status === 'cancelada')
                    <p class="mt-1 text-sm text-slate-600">Cancelada pela empresa em {{ $requisicao->cancelado_em?->format('d/m/Y H:i') }}. Não há o que analisar.</p>
                @else
                    <p class="mt-1 text-sm text-slate-700">{{ $situacao['label'] }}{{ $requisicao->analisadoPor ? ' por ' . $requisicao->analisadoPor->nome : '' }}{{ $requisicao->analisado_em ? ' em ' . $requisicao->analisado_em->format('d/m/Y H:i') : '' }}.</p>
                    @if($requisicao->observacao_vigilancia)
                    <p class="mt-2 text-sm text-slate-800 whitespace-pre-line">{{ $requisicao->observacao_vigilancia }}</p>
                    @endif
                @endif
            </section>

            {{-- Parâmetros de entrega --}}
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Parâmetros de entrega (DVISA)</p>
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-left text-slate-500">
                            <th class="pb-1 pr-2 font-semibold">Tipo</th>
                            <th class="pb-1 pr-2 font-semibold">Especialista</th>
                            <th class="pb-1 font-semibold">Outras</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-700">
                        @foreach($Req::PARAMETROS_ENTREGA as $p)
                        <tr class="border-t border-slate-100 align-top">
                            <td class="py-1 pr-2 font-bold">{{ $p['tipo'] }}</td>
                            <td class="py-1 pr-2">{{ $p['especialista'] }}</td>
                            <td class="py-1">{{ $p['outras'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <ul class="mt-2 space-y-0.5 text-[11px] text-slate-600">
                    @foreach($Req::PARAMETROS_OUTROS as $quem => $limite)
                    <li><span class="font-semibold">{{ $quem }}:</span> {{ $limite }}</li>
                    @endforeach
                </ul>
                @if(!empty($r['especialidade']))
                <p class="mt-2 text-[11px] text-slate-500">Especialidade informada: <span class="font-semibold text-slate-700">{{ $r['especialidade'] }}</span></p>
                @endif
            </section>

            {{-- Histórico do profissional --}}
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <p class="px-4 pt-3 pb-2 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Requisições anteriores do profissional</p>
                @forelse($historico as $anterior)
                <a href="{{ route('admin.estabelecimentos.processos.requisicoes.show', [$estabelecimento->id, $anterior->processo_id, $anterior->id]) }}"
                   class="px-4 py-2 border-t border-slate-100 flex items-center justify-between gap-2 hover:bg-slate-50">
                    <span class="min-w-0">
                        <span class="block text-xs font-semibold text-slate-800 tabular-nums">Nº {{ $anterior->numero }}</span>
                        <span class="block text-[11px] text-slate-500">{{ $anterior->created_at->format('d/m/Y') }} · {{ $anterior->totalBlocos() }} bloco(s)</span>
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold ring-1 ring-inset {{ $anterior->situacao['classe'] }}">{{ $anterior->situacao['label'] }}</span>
                </a>
                @empty
                <p class="px-4 pb-3 text-xs text-slate-400">Primeira requisição deste profissional.</p>
                @endforelse
            </section>
        </aside>
    </div>
</div>
@endsection
