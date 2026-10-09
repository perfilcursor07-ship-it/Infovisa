@extends('layouts.company')

@section('title', $receituario->identificador)
@section('page-title', 'Profissional · Receituário')

@php
    $profissional = in_array($receituario->tipo, ['medico', 'talidomida'], true);
    $locais = collect($receituario->locais_trabalho ?? [])->filter(fn ($l) => !empty($l['nome']))->values();
    $docsCadastro = $receituario->documentosDaAnalise();
    $docsAprovados = collect($docsCadastro)->filter(fn ($d) => $receituario->statusDocumento($d) === 'aprovado')->count();
    $documentosRejeitados = collect($docsCadastro)->filter(fn ($d) => $receituario->statusDocumento($d) === 'rejeitado')->values();
    $docsRejeitados = $documentosRejeitados->count();
    $processosProfissional = ($receituario->estabelecimento?->processos ?? collect())->sortByDesc('created_at')->values();
    $processosAtivos = $processosProfissional->reject(fn ($p) => in_array($p->status, ['arquivado', 'concluido', 'indeferido'], true))->values();
    $statusProcesso = [
        'aberto' => 'bg-blue-50 text-blue-700 ring-blue-200', 'em_analise' => 'bg-violet-50 text-violet-700 ring-violet-200',
        'em_andamento' => 'bg-amber-50 text-amber-700 ring-amber-200', 'parado' => 'bg-red-50 text-red-700 ring-red-200',
        'arquivado' => 'bg-slate-100 text-slate-600 ring-slate-200', 'concluido' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    ];
    $campo = fn ($rotulo, $valor, $classe = '') => $valor
        ? '<div class="' . $classe . '"><dt class="text-xs text-slate-500">' . e($rotulo) . '</dt><dd class="mt-0.5 text-sm font-medium text-slate-900 break-words">' . e($valor) . '</dd></div>'
        : '';
    $avisoCadastro = [
        'pendente' => ['classe' => 'border-blue-200 bg-blue-50 text-blue-900', 'icone' => 'text-blue-500', 'titulo' => 'Cadastro em análise', 'texto' => 'A Vigilância Sanitária está conferindo os documentos do cadastro. Depois da aprovação, o processo de receituário é aberto automaticamente.'],
        'rejeitado' => ['classe' => 'border-red-200 bg-red-50 text-red-900', 'icone' => 'text-red-500', 'titulo' => 'Correção solicitada', 'texto' => 'A Vigilância Sanitária rejeitou ' . ($docsRejeitados === 1 ? 'um documento' : 'documentos') . ' do cadastro. Veja o motivo em Documentos e corrija.'],
    ][$receituario->status] ?? null;
    $iconeMenu = 'w-[18px] h-[18px] text-slate-400 group-hover:text-blue-600';
    $itemMenu = 'group w-full flex items-center gap-2.5 px-3 py-2 text-sm font-medium rounded-lg transition-colors';
@endphp

@section('content')
<div class="space-y-4" x-data="{ aba: @js($aba), editar: false }"
     x-init="$nextTick(() => { const id = window.location.hash.slice(1); if (id) document.getElementById(id)?.scrollIntoView({ block: 'center' }); })">
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm font-medium text-amber-800">{{ session('error') }}</div>
    @endif

    {{-- Cabeçalho --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('company.receituarios.index') }}" title="Voltar para profissionais cadastrados"
               class="w-9 h-9 flex-shrink-0 rounded-lg border border-slate-200 text-slate-500 flex items-center justify-center hover:bg-slate-50 hover:text-slate-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <span class="w-10 h-10 flex-shrink-0 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </span>
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-slate-900 truncate">{{ $receituario->identificador }}</h1>
                <p class="text-xs text-slate-500 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                    <span>{{ $receituario->cpf_formatado ?? $receituario->cnpj_formatado }}</span>
                    <span class="text-slate-300">·</span>
                    <span>{{ $profissional ? 'Médico, Dentista ou Veterinário' : $receituario->tipo_nome }}</span>
                    <span class="text-slate-300">·</span>
                    @if($receituario->isAprovado())
                        <span class="inline-flex items-center gap-1 text-emerald-700">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Cadastro aprovado{{ $receituario->analisadoPor ? ' por ' : '' }}<strong class="font-semibold">{{ $receituario->analisadoPor?->nome }}</strong>{{ $receituario->analisado_em ? ' em ' . $receituario->analisado_em->format('d/m/Y H:i') : '' }}
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset {{ $receituario->situacao['classe'] }}">{{ $receituario->situacao['label'] }}</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    @if($avisoCadastro)
        @if($receituario->status === 'rejeitado' && $documentosRejeitados->isNotEmpty())
            <section role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3.5 text-red-950">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-sm font-bold">Correção solicitada</h2>
                        <p class="mt-0.5 text-sm leading-relaxed">
                            A Vigilância Sanitária rejeitou {{ $docsRejeitados === 1 ? 'um documento' : 'documentos' }}. Clique abaixo para ver o motivo e corrigir no mesmo passo do cadastro.
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($documentosRejeitados as $doc)
                                @php $nomeDocumento = \App\Models\Receituario::DOCUMENTOS[$doc]['nome']; @endphp
                                <a href="{{ route('company.receituarios.corrigir', [$receituario->id, $doc]) }}"
                                   class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-800 transition hover:border-red-300 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Corrigir {{ mb_strtolower($nomeDocumento, 'UTF-8') }}
                                    <span class="font-normal text-red-600">· {{ $doc === 'carteira' ? 'Passo 1' : 'Passo 2' }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @else
            <div class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm {{ $avisoCadastro['classe'] }}">
                <svg class="w-5 h-5 flex-shrink-0 {{ $avisoCadastro['icone'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p><strong>{{ $avisoCadastro['titulo'] }}.</strong> {{ $avisoCadastro['texto'] }}</p>
            </div>
        @endif
    @endif

    <div class="flex flex-col lg:flex-row gap-4 items-start">
        {{-- Menu lateral --}}
        <aside class="w-full lg:w-64 flex-shrink-0 lg:sticky lg:top-4">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3">
                <p class="px-3 pt-1 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Ações</p>
                <nav class="space-y-0.5">
                    <button type="button" @click="aba = 'geral'" class="{{ $itemMenu }}"
                            :class="aba === 'geral' ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50'">
                        <svg class="{{ $iconeMenu }}" :class="aba === 'geral' && '!text-blue-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Visão geral
                    </button>
                    <button type="button" @click="editar = true" class="{{ $itemMenu }} text-slate-700 hover:bg-slate-50">
                        <svg class="{{ $iconeMenu }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Editar dados
                    </button>
                    <button type="button" @click="aba = 'documentos'" class="{{ $itemMenu }}"
                            :class="aba === 'documentos' ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50'">
                        <svg class="{{ $iconeMenu }}" :class="aba === 'documentos' && '!text-blue-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Documentos
                        <span class="ml-auto px-2 py-0.5 rounded-full text-[11px] font-semibold tabular-nums
                            {{ $docsRejeitados ? 'bg-red-100 text-red-700' : ($docsAprovados === count($docsCadastro) && count($docsCadastro) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600') }}">{{ $docsAprovados }}/{{ count($docsCadastro) }}</span>
                    </button>
                    <button type="button" @click="aba = 'processos'" class="{{ $itemMenu }}"
                            :class="aba === 'processos' ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50'">
                        <svg class="{{ $iconeMenu }}" :class="aba === 'processos' && '!text-blue-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Processos
                        @if($receituario->isAprovado())
                            <span class="ml-auto px-2 py-0.5 rounded-full text-[11px] font-semibold tabular-nums bg-blue-100 text-blue-700">{{ $processosProfissional->count() }}</span>
                        @else
                            <svg class="ml-auto w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-label="Disponível após a aprovação"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        @endif
                    </button>
                    <button type="button" @click="aba = 'usuarios'" class="{{ $itemMenu }}"
                            :class="aba === 'usuarios' ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50'">
                        <svg class="{{ $iconeMenu }}" :class="aba === 'usuarios' && '!text-blue-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Usuários vinculados
                        <span class="ml-auto px-2 py-0.5 rounded-full text-[11px] font-semibold tabular-nums bg-indigo-100 text-indigo-700">{{ $receituario->usuariosVinculados->count() + ($receituario->usuario_externo_id ? 1 : 0) }}</span>
                    </button>
                </nav>

                @if($receituario->isAprovado() && $tiposProcesso->isNotEmpty() && $processosAtivos->isEmpty())
                    <button type="button" @click="aba = 'processos'; $nextTick(() => $dispatch('abrir-processo-receituario'))"
                            class="mt-3 w-full inline-flex items-center justify-center gap-2 px-3 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Abrir processo
                    </button>
                @endif
            </div>
        </aside>

        {{-- Conteúdo --}}
        <div class="flex-1 min-w-0 w-full space-y-4">

            {{-- ============ VISÃO GERAL ============ --}}
            <div x-show="aba === 'geral'" class="space-y-4">
                <section class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h2 class="px-4 py-3 border-b border-slate-100 text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Informações gerais
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 md:divide-x divide-slate-100">
                        <div class="p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-3">Identificação</p>
                            <dl class="grid grid-cols-2 gap-x-4 gap-y-3">
                                @if($profissional)
                                    {!! $campo('Nome completo', $receituario->nome, 'col-span-2') !!}
                                    {!! $campo('CPF', $receituario->cpf_formatado) !!}
                                    {!! $campo($receituario->tipo === 'talidomida' ? 'Nº CRM' : 'Nº do conselho', $receituario->numero_crm ?: $receituario->numero_conselho_classe) !!}
                                    {!! $campo('Especialidade', $receituario->especialidade, 'col-span-2') !!}
                                @else
                                    {!! $campo('Razão social', $receituario->razao_social, 'col-span-2') !!}
                                    {!! $campo('CNPJ', $receituario->cnpj_formatado) !!}
                                @endif
                                {!! $campo('Telefone', $receituario->telefone) !!}
                                {!! $campo('Telefone 2', $receituario->telefone2) !!}
                                {!! $campo('E-mail', $receituario->email, 'col-span-2') !!}
                            </dl>
                        </div>
                        <div class="p-4 border-t md:border-t-0 border-slate-100">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-3">Endereço</p>
                            <dl class="grid grid-cols-2 gap-x-4 gap-y-3">
                                {!! $campo('Logradouro', $receituario->endereco ?: $receituario->endereco_residencial, 'col-span-2') !!}
                                {!! $campo('Município', $receituario->municipio?->nome) !!}
                                {!! $campo('CEP', $receituario->cep) !!}
                            </dl>
                            @if($locais->isNotEmpty())
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mt-4 mb-2">Locais de trabalho</p>
                                <ul class="space-y-1.5">
                                    @foreach($locais as $local)
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            <span class="min-w-0">
                                                <span class="block text-sm font-medium text-slate-900 uppercase leading-snug">{{ $local['nome'] }}</span>
                                                <span class="block text-xs text-slate-500">{{ collect([$local['municipio'] ?? null, !empty($local['cep']) ? 'CEP ' . $local['cep'] : null])->filter()->implode(' · ') }}</span>
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                    <p class="px-4 py-2.5 border-t border-slate-100 text-xs text-slate-500">
                        Cadastrado em <strong class="font-medium text-slate-700">{{ $receituario->created_at->format('d/m/Y H:i') }}</strong>
                        @if($receituario->tipo === 'medico' && $receituario->solicitante_proprio !== null)
                            · {{ $receituario->solicitante_proprio ? 'você é o profissional' : 'por você em nome do profissional' }}
                        @endif
                        · Nº do cadastro <strong class="font-medium text-slate-700">#{{ $receituario->id }}</strong>
                    </p>
                    @if($receituario->observacoes)
                        <div class="px-4 py-3 border-t border-amber-100 bg-amber-50/60 text-sm text-amber-900 rounded-b-xl">
                            <strong>Observações da Vigilância Sanitária:</strong> <span class="whitespace-pre-line">{{ $receituario->observacoes }}</span>
                        </div>
                    @endif
                </section>

                {{-- Processos ativos (cartões) --}}
                <section class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                                    Processos ativos
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700">{{ $processosAtivos->count() }}</span>
                                </h2>
                                <p class="text-xs text-slate-500">{{ $processosProfissional->count() }} {{ $processosProfissional->count() === 1 ? 'processo' : 'processos' }} no total</p>
                            </div>
                        </div>
                        @if($processosProfissional->isNotEmpty())
                            <button type="button" @click="aba = 'processos'" class="text-xs font-semibold text-blue-700 hover:underline">Ver todos</button>
                        @endif
                    </div>
                    @if(!$receituario->isAprovado())
                        <p class="px-4 py-6 text-center text-sm text-slate-500">
                            <svg class="w-5 h-5 mx-auto mb-1.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            O processo de receituário fica disponível depois da aprovação do cadastro.
                        </p>
                    @elseif($processosAtivos->isEmpty())
                        <div class="px-4 py-6 text-center">
                            <p class="text-sm text-slate-500">Nenhum processo ativo.</p>
                            @if($tiposProcesso->isNotEmpty())
                                <button type="button" @click="aba = 'processos'; $nextTick(() => $dispatch('abrir-processo-receituario'))" class="mt-2 text-sm font-semibold text-blue-700 hover:underline">Abrir processo de receituário →</button>
                            @endif
                        </div>
                    @else
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                            @foreach($processosAtivos->take(6) as $processo)
                                <a href="{{ route('company.processos.show', $processo->id) }}"
                                   class="group block rounded-xl border border-slate-200 p-4 hover:border-blue-300 hover:shadow-md transition">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-900 group-hover:text-blue-700 truncate">{{ $processo->tipo_nome }}</p>
                                            <p class="mt-0.5 text-xs font-mono text-slate-500">nº {{ $processo->numero_processo }}</p>
                                        </div>
                                        <span class="flex-shrink-0 px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset {{ $statusProcesso[$processo->status] ?? 'bg-slate-100 text-slate-600 ring-slate-200' }}">{{ $processo->status_nome }}</span>
                                    </div>
                                    <p class="mt-3 pt-3 border-t border-slate-100 text-xs text-slate-500 flex items-center justify-between">
                                        Aberto em {{ $processo->created_at->format('d/m/Y') }}
                                        <svg class="w-4 h-4 text-slate-300 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            {{-- ============ DOCUMENTOS DO CADASTRO ============ --}}
            <div x-show="aba === 'documentos'" x-cloak>
                @include('company.receituarios.partials.documentos')
            </div>

            {{-- ============ PROCESSOS ============ --}}
            <div x-show="aba === 'processos'" x-cloak>
                @if($receituario->isAprovado())
                    @include('company.receituarios.partials.processos')
                @else
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-6 py-10 text-center">
                        <span class="mx-auto w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </span>
                        <p class="text-sm font-semibold text-slate-800">Processos disponíveis depois da aprovação do cadastro</p>
                        <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">Quando a Vigilância Sanitária aprovar os documentos do cadastro, o processo de receituário é aberto automaticamente aqui, onde é feita a requisição.</p>
                        <button type="button" @click="aba = 'documentos'" class="mt-4 text-xs font-semibold text-blue-700 hover:underline">Ver situação dos documentos →</button>
                    </div>
                @endif
            </div>

            {{-- ============ USUÁRIOS VINCULADOS ============ --}}
            <div x-show="aba === 'usuarios'" x-cloak>
                @include('receituarios.partials.usuarios-vinculados', [
                    'rotaBuscar' => route('company.receituarios.usuarios.buscar', $receituario->id),
                    'rotaVincular' => route('company.receituarios.usuarios.store', $receituario->id),
                    'rotaDesvincular' => fn ($usuarioId) => route('company.receituarios.usuarios.destroy', [$receituario->id, $usuarioId]),
                    'usuarioAtualId' => auth('externo')->id(),
                ])
            </div>
        </div>
    </div>

    {{-- Editar dados: por enquanto, pela Vigilância Sanitária Estadual (exige nova análise do cadastro) --}}
    <template x-teleport="body">
        <div x-show="editar" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="editar = false">
            <div class="absolute inset-0 bg-slate-900/50" @click="editar = false"></div>
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-5">
                <div class="flex items-start gap-3">
                    <span class="w-10 h-10 flex-shrink-0 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Alterar dados do cadastro</h3>
                        <p class="mt-1 text-sm text-slate-600">
                            Alterar os dados do profissional exige uma <strong>nova análise e aprovação</strong> do cadastro pela Vigilância Sanitária.
                        </p>
                        <p class="mt-2 text-sm text-slate-600">
                            Por enquanto, para alterar algum dado, <strong>entre em contato com a Vigilância Sanitária Estadual</strong> informando o nº do cadastro <strong>#{{ $receituario->id }}</strong>.
                        </p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end">
                    <button type="button" @click="editar = false" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Entendi</button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
