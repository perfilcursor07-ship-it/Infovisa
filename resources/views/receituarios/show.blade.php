@extends('layouts.admin')

@section('title', 'Receituário · ' . $receituario->identificador)
@section('page-title', 'Profissional · Receituário')

@php
    $profissional = in_array($receituario->tipo, ['medico', 'talidomida'], true);
    $externo = $receituario->isSolicitacaoExterna();
    $locais = collect($receituario->locais_trabalho ?? [])->filter(fn ($l) => !empty($l['nome']))->values();
    $municipioNome = $receituario->municipio?->nome;
    $docsCadastro = $externo ? $receituario->documentosDaAnalise() : [];
    $situacoesDocs = collect($docsCadastro)->map(fn ($d) => $receituario->statusDocumento($d));
    $docsAprovados = $situacoesDocs->filter(fn ($s) => $s === 'aprovado')->count();
    $docsPendentes = $situacoesDocs->filter(fn ($s) => $s === 'pendente')->count();
    $docsRejeitados = $situacoesDocs->filter(fn ($s) => $s === 'rejeitado')->count();
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
    $avisoCadastro = match (true) {
        $externo && $docsPendentes > 0 => ['classe' => 'border-amber-200 bg-amber-50 text-amber-900', 'icone' => 'text-amber-500', 'titulo' => 'Aguardando análise',
            'texto' => ($docsPendentes === 1 ? '1 documento do cadastro aguarda' : $docsPendentes . ' documentos do cadastro aguardam') . ' a sua análise.', 'acao' => 'Analisar documentos'],
        $externo && $receituario->status === 'rejeitado' => ['classe' => 'border-red-200 bg-red-50 text-red-900', 'icone' => 'text-red-500', 'titulo' => 'Correção solicitada',
            'texto' => 'Aguardando a empresa reenviar ' . ($docsRejeitados === 1 ? 'o documento rejeitado' : 'os documentos rejeitados') . '.', 'acao' => 'Ver documentos'],
        default => null,
    };
    $iconeMenu = 'w-[18px] h-[18px] text-slate-400 group-hover:text-blue-600';
    $itemMenu = 'group w-full flex items-center gap-2.5 px-3 py-2 text-sm font-medium rounded-lg transition-colors';
    $input = 'w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500';
    $rotulo = 'block text-xs font-semibold text-slate-600 mb-1';
    $locaisForm = old('locais_trabalho', $locais->map(fn ($l) => ['nome' => $l['nome'] ?? '', 'municipio' => $l['municipio'] ?? '', 'cep' => $l['cep'] ?? ''])->all());
@endphp

@section('content')
<div class="max-w-8xl mx-auto space-y-4" x-data="{ aba: @js($aba) }">
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm font-medium text-amber-800">{{ session('error') }}</div>
    @endif
    @if(session('info'))
        <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm font-medium text-blue-800">{{ session('info') }}</div>
    @endif

    {{-- Cabeçalho --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3.5 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('admin.receituarios.index') }}" title="Voltar para receituários"
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
                    <span>{{ $receituario->tipo_nome }}</span>
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
        <div class="flex flex-wrap items-center gap-2 md:flex-shrink-0">
            <span class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg text-xs font-semibold {{ $externo ? 'bg-violet-50 text-violet-700' : 'bg-slate-100 text-slate-600' }}">
                {{ $externo ? 'Enviado pela empresa' : 'Cadastrado pela Vigilância' }}
            </span>
            @unless($externo)
                <a href="{{ route('admin.receituarios.gerar-pdf', $receituario->id) }}" target="_blank"
                   class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    PDF para assinatura
                </a>
            @endunless
        </div>
    </div>

    @if($avisoCadastro)
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl border px-4 py-3 text-sm {{ $avisoCadastro['classe'] }}">
            <div class="flex items-start gap-3 flex-1">
                <svg class="w-5 h-5 flex-shrink-0 {{ $avisoCadastro['icone'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p><strong>{{ $avisoCadastro['titulo'] }}.</strong> {{ $avisoCadastro['texto'] }}</p>
            </div>
            <button type="button" x-show="aba !== 'documentos'" @click="aba = 'documentos'" class="self-start sm:self-auto text-xs font-semibold underline underline-offset-2 hover:no-underline">{{ $avisoCadastro['acao'] }} →</button>
        </div>
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
                    <button type="button" @click="aba = 'editar'" class="{{ $itemMenu }}"
                            :class="aba === 'editar' ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50'">
                        <svg class="{{ $iconeMenu }}" :class="aba === 'editar' && '!text-blue-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Editar dados
                    </button>
                    <button type="button" @click="aba = 'documentos'" class="{{ $itemMenu }}"
                            :class="aba === 'documentos' ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50'">
                        <svg class="{{ $iconeMenu }}" :class="aba === 'documentos' && '!text-blue-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Documentos
                        @if(count($docsCadastro))
                            <span class="ml-auto px-2 py-0.5 rounded-full text-[11px] font-semibold tabular-nums
                                {{ $docsPendentes ? 'bg-amber-100 text-amber-800' : ($docsRejeitados ? 'bg-red-100 text-red-700' : ($docsAprovados === count($docsCadastro) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600')) }}"
                                  title="{{ $docsPendentes ? $docsPendentes . ' aguardando análise' : '' }}">{{ $docsAprovados }}/{{ count($docsCadastro) }}</span>
                        @endif
                    </button>
                    <button type="button" @click="aba = 'processos'" class="{{ $itemMenu }}"
                            :class="aba === 'processos' ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50'">
                        <svg class="{{ $iconeMenu }}" :class="aba === 'processos' && '!text-blue-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Processos
                        <span class="ml-auto px-2 py-0.5 rounded-full text-[11px] font-semibold tabular-nums bg-blue-100 text-blue-700">{{ $processosProfissional->count() }}</span>
                    </button>
                    <button type="button" @click="aba = 'usuarios'" class="{{ $itemMenu }}"
                            :class="aba === 'usuarios' ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50'">
                        <svg class="{{ $iconeMenu }}" :class="aba === 'usuarios' && '!text-blue-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Usuários vinculados
                        <span class="ml-auto px-2 py-0.5 rounded-full text-[11px] font-semibold tabular-nums bg-indigo-100 text-indigo-700">{{ $receituario->usuariosVinculados->count() + ($receituario->usuario_externo_id ? 1 : 0) }}</span>
                    </button>
                </nav>
            </div>
        </aside>

        {{-- Conteúdo --}}
        <div class="flex-1 min-w-0 w-full space-y-4">

            {{-- ============ VISÃO GERAL ============ --}}
            <div x-show="aba === 'geral'" x-cloak class="space-y-4">
                <section class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Informações gerais
                        </h2>
                        <button type="button" @click="aba = 'editar'" class="text-xs font-semibold text-blue-700 hover:underline">Editar</button>
                    </div>
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
                            @if($receituario->tipo === 'instituicao' && $receituario->responsavel_nome)
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mt-4 mb-3">Responsável técnico</p>
                                <dl class="grid grid-cols-2 gap-x-4 gap-y-3">
                                    {!! $campo('Nome', $receituario->responsavel_nome, 'col-span-2') !!}
                                    {!! $campo('CPF', $receituario->responsavel_cpf) !!}
                                    {!! $campo('Nº CRM-TO', $receituario->responsavel_crm) !!}
                                    {!! $campo('Especialidade', $receituario->responsavel_especialidade) !!}
                                    {!! $campo('Telefone', $receituario->responsavel_telefone) !!}
                                </dl>
                            @endif
                        </div>
                        <div class="p-4 border-t md:border-t-0 border-slate-100">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-3">Endereço</p>
                            <dl class="grid grid-cols-2 gap-x-4 gap-y-3">
                                {!! $campo('Logradouro', $receituario->endereco ?: $receituario->endereco_residencial, 'col-span-2') !!}
                                {!! $campo('Município', $municipioNome) !!}
                                {!! $campo('CEP', $receituario->cep) !!}
                            </dl>
                            @if($profissional)
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mt-4 mb-2">Locais de trabalho</p>
                                @if($locais->isEmpty())
                                    <p class="text-sm text-slate-400">Não informados.</p>
                                @else
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
                            @endif
                        </div>
                    </div>
                    <p class="px-4 py-2.5 border-t border-slate-100 text-xs text-slate-500">
                        Cadastrado em <strong class="font-medium text-slate-700">{{ $receituario->created_at->format('d/m/Y H:i') }}</strong>
                        @if($externo)
                            · {{ $receituario->solicitante_descricao ?: 'pela empresa' . ($receituario->usuarioExterno?->nome ? ' (' . $receituario->usuarioExterno->nome . ')' : '') }}
                        @elseif($receituario->usuarioCriacao)
                            · por {{ $receituario->usuarioCriacao->nome }}
                        @endif
                        · Nº do cadastro <strong class="font-medium text-slate-700">#{{ $receituario->id }}</strong>
                        @if($receituario->updated_at && $receituario->updated_at->ne($receituario->created_at))
                            · atualizado em {{ $receituario->updated_at->format('d/m/Y H:i') }}
                        @endif
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
                    @if($processosAtivos->isEmpty())
                        <p class="px-4 py-6 text-center text-sm text-slate-500">
                            {{ $receituario->isAprovado() ? 'Nenhum processo ativo. A empresa abre o processo de receituário pela área dela.' : 'O processo de receituário fica disponível para a empresa depois da aprovação do cadastro.' }}
                        </p>
                    @else
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                            @foreach($processosAtivos->take(6) as $processo)
                                @include('receituarios.partials.cartao-processo')
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            {{-- ============ EDITAR DADOS ============ --}}
            <div x-show="aba === 'editar'" x-cloak>
                <form method="POST" action="{{ route('admin.receituarios.update', $receituario->id) }}"
                      class="bg-white rounded-xl border border-slate-200 shadow-sm"
                      x-data="{ locais: @js(array_values($locaisForm)) }">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_form" value="editar">

                    <div class="px-4 py-3 border-b border-slate-100">
                        <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Editar dados do cadastro
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">As alterações valem na hora, sem nova análise dos documentos.{{ $externo ? ' A empresa verá os dados atualizados.' : '' }}</p>
                    </div>

                    @if($errors->any() && old('_form') === 'editar')
                        <div class="mx-4 mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                            <ul class="list-disc list-inside space-y-0.5">
                                @foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="p-4 space-y-5">
                        {{-- Identificação --}}
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-3">Identificação</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                @if($profissional)
                                    <label class="block sm:col-span-2">
                                        <span class="{{ $rotulo }}">Nome completo *</span>
                                        <input type="text" name="nome" required maxlength="255" value="{{ old('nome', $receituario->nome) }}" class="{{ $input }} uppercase">
                                    </label>
                                    <label class="block">
                                        <span class="{{ $rotulo }}">CPF *</span>
                                        <input type="text" name="cpf" required maxlength="14" value="{{ old('cpf', $receituario->cpf_formatado ?? $receituario->cpf) }}" class="{{ $input }}">
                                    </label>
                                    @if($receituario->tipo === 'talidomida')
                                        <label class="block">
                                            <span class="{{ $rotulo }}">Nº CRM *</span>
                                            <input type="text" name="numero_crm" required maxlength="50" value="{{ old('numero_crm', $receituario->numero_crm) }}" class="{{ $input }}">
                                        </label>
                                    @else
                                        <label class="block">
                                            <span class="{{ $rotulo }}">Nº do conselho *</span>
                                            <input type="text" name="numero_conselho_classe" required maxlength="50" value="{{ old('numero_conselho_classe', $receituario->numero_conselho_classe) }}" placeholder="Ex.: CRM-TO 1234" class="{{ $input }}">
                                        </label>
                                    @endif
                                    <label class="block sm:col-span-2">
                                        <span class="{{ $rotulo }}">Especialidade</span>
                                        @php $especialidadeAtual = old('especialidade', $receituario->especialidade); @endphp
                                        <select name="especialidade" class="{{ $input }}">
                                            <option value="">—</option>
                                            @if($especialidadeAtual && !in_array($especialidadeAtual, \App\Models\Receituario::ESPECIALIDADES, true))
                                                <option value="{{ $especialidadeAtual }}" selected>{{ $especialidadeAtual }}</option>
                                            @endif
                                            @foreach(\App\Models\Receituario::ESPECIALIDADES as $esp)
                                                <option value="{{ $esp }}" @selected($especialidadeAtual === $esp)>{{ $esp }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                @else
                                    <label class="block sm:col-span-2 lg:col-span-3">
                                        <span class="{{ $rotulo }}">Razão social *</span>
                                        <input type="text" name="razao_social" required maxlength="255" value="{{ old('razao_social', $receituario->razao_social) }}" class="{{ $input }} uppercase">
                                    </label>
                                    <label class="block">
                                        <span class="{{ $rotulo }}">CNPJ *</span>
                                        <input type="text" name="cnpj" required maxlength="18" value="{{ old('cnpj', $receituario->cnpj_formatado ?? $receituario->cnpj) }}" class="{{ $input }}">
                                    </label>
                                @endif
                                <label class="block">
                                    <span class="{{ $rotulo }}">Telefone{{ $profissional ? ' *' : '' }}</span>
                                    <input type="text" name="telefone" @if($profissional) required @endif maxlength="20" value="{{ old('telefone', $receituario->telefone) }}" class="{{ $input }}">
                                </label>
                                <label class="block">
                                    <span class="{{ $rotulo }}">Telefone 2</span>
                                    <input type="text" name="telefone2" maxlength="20" value="{{ old('telefone2', $receituario->telefone2) }}" class="{{ $input }}">
                                </label>
                                <label class="block sm:col-span-2">
                                    <span class="{{ $rotulo }}">E-mail</span>
                                    <input type="email" name="email" maxlength="255" value="{{ old('email', $receituario->email) }}" class="{{ $input }}">
                                </label>
                            </div>
                        </div>

                        @if($receituario->tipo === 'instituicao')
                            <div class="pt-5 border-t border-slate-100">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-3">Responsável técnico</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                    <label class="block sm:col-span-2">
                                        <span class="{{ $rotulo }}">Nome</span>
                                        <input type="text" name="responsavel_nome" maxlength="255" value="{{ old('responsavel_nome', $receituario->responsavel_nome) }}" class="{{ $input }}">
                                    </label>
                                    <label class="block">
                                        <span class="{{ $rotulo }}">CPF</span>
                                        <input type="text" name="responsavel_cpf" maxlength="14" value="{{ old('responsavel_cpf', $receituario->responsavel_cpf) }}" class="{{ $input }}">
                                    </label>
                                    <label class="block">
                                        <span class="{{ $rotulo }}">Nº CRM-TO</span>
                                        <input type="text" name="responsavel_crm" maxlength="50" value="{{ old('responsavel_crm', $receituario->responsavel_crm) }}" class="{{ $input }}">
                                    </label>
                                    <label class="block sm:col-span-2">
                                        <span class="{{ $rotulo }}">Especialidade</span>
                                        <input type="text" name="responsavel_especialidade" maxlength="255" value="{{ old('responsavel_especialidade', $receituario->responsavel_especialidade) }}" class="{{ $input }}">
                                    </label>
                                    <label class="block">
                                        <span class="{{ $rotulo }}">Telefone</span>
                                        <input type="text" name="responsavel_telefone" maxlength="20" value="{{ old('responsavel_telefone', $receituario->responsavel_telefone) }}" class="{{ $input }}">
                                    </label>
                                </div>
                            </div>
                        @endif

                        {{-- Endereço --}}
                        <div class="pt-5 border-t border-slate-100">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-3">Endereço</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                <label class="block sm:col-span-2 lg:col-span-4">
                                    <span class="{{ $rotulo }}">Logradouro</span>
                                    <input type="text" name="endereco" maxlength="255" value="{{ old('endereco', $receituario->endereco ?: $receituario->endereco_residencial) }}" class="{{ $input }}">
                                </label>
                                <label class="block sm:col-span-1 lg:col-span-3">
                                    <span class="{{ $rotulo }}">Município</span>
                                    <select name="municipio_id" class="{{ $input }}">
                                        <option value="">—</option>
                                        @foreach($municipios as $municipio)
                                            <option value="{{ $municipio->id }}" @selected((string) old('municipio_id', $receituario->municipio_id) === (string) $municipio->id)>{{ $municipio->nome }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="{{ $rotulo }}">CEP</span>
                                    <input type="text" name="cep" maxlength="10" value="{{ old('cep', $receituario->cep) }}" class="{{ $input }}">
                                </label>
                            </div>
                        </div>

                        {{-- Locais de trabalho --}}
                        @if($profissional)
                            <div class="pt-5 border-t border-slate-100">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Locais de trabalho</p>
                                    <button type="button" @click="locais.push({ nome: '', municipio: '', cep: '' })" class="text-xs font-semibold text-blue-700 hover:underline">+ Adicionar local</button>
                                </div>
                                <p x-show="!locais.length" class="text-sm text-slate-400">Nenhum local informado.</p>
                                <div class="space-y-2">
                                    <template x-for="(local, i) in locais" :key="i">
                                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_12rem_8rem_auto] gap-2 items-center">
                                            <input type="text" :name="`locais_trabalho[${i}][nome]`" x-model="local.nome" maxlength="255" placeholder="Nome do local" class="{{ $input }}">
                                            <input type="text" :name="`locais_trabalho[${i}][municipio]`" x-model="local.municipio" maxlength="255" placeholder="Município" class="{{ $input }}">
                                            <input type="text" :name="`locais_trabalho[${i}][cep]`" x-model="local.cep" maxlength="10" placeholder="CEP" class="{{ $input }}">
                                            <button type="button" @click="locais.splice(i, 1)" title="Remover local"
                                                    class="w-9 h-9 justify-self-end rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 flex items-center justify-center">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        @endif

                        {{-- Observações --}}
                        <div class="pt-5 border-t border-slate-100">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-3">Observações da Vigilância Sanitária</p>
                            <textarea name="observacoes" rows="3" maxlength="5000" class="{{ $input }}" placeholder="{{ $externo ? 'Visível para a empresa na página do cadastro.' : '' }}">{{ old('observacoes', $receituario->observacoes) }}</textarea>
                        </div>
                    </div>

                    <div class="px-4 py-3 border-t border-slate-100 bg-slate-50 rounded-b-xl flex justify-end gap-2">
                        <button type="button" @click="aba = 'geral'" class="h-9 px-4 text-sm font-semibold text-slate-600 hover:text-slate-900">Cancelar</button>
                        <button type="submit" class="h-9 px-5 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Salvar alterações</button>
                    </div>
                </form>
            </div>

            {{-- ============ DOCUMENTOS DO CADASTRO ============ --}}
            <div x-show="aba === 'documentos'" x-cloak>
                @if($externo)
                    @include('receituarios.partials.verificacao')
                @else
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-6 py-10 text-center">
                        <p class="text-sm font-semibold text-slate-800">Sem documentos para análise</p>
                        <p class="text-xs text-slate-500 mt-1">Este cadastro foi feito pela Vigilância Sanitária; a carteira do conselho e o comprovante de endereço só são enviados nos cadastros feitos pela empresa.</p>
                    </div>
                @endif
            </div>

            {{-- ============ PROCESSOS ============ --}}
            <div x-show="aba === 'processos'" x-cloak>
                <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">Processos de receituário</h2>
                            <p class="text-xs text-slate-500">Abertos pela empresa depois da aprovação do cadastro — é neles que é feita a requisição.</p>
                        </div>
                    </div>
                    @if($processosProfissional->isEmpty())
                        <p class="px-4 py-6 text-center text-sm text-slate-500">
                            {{ $receituario->isAprovado() ? 'A empresa ainda não abriu processo de receituário.' : 'Disponível para a empresa depois da aprovação do cadastro.' }}
                        </p>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach($processosProfissional as $processo)
                                <li>
                                    <a href="{{ route('admin.estabelecimentos.processos.show', [$processo->estabelecimento_id, $processo->id]) }}" class="px-4 py-3 flex items-center justify-between gap-3 hover:bg-slate-50">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-semibold text-slate-900">{{ $processo->tipo_nome }}</span>
                                            <span class="block text-xs text-slate-500">nº {{ $processo->numero_processo }} · aberto em {{ $processo->created_at->format('d/m/Y') }}</span>
                                        </span>
                                        <span class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset {{ $statusProcesso[$processo->status] ?? 'bg-slate-100 text-slate-600 ring-slate-200' }}">{{ $processo->status_nome }}</span>
                                            <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>

            {{-- ============ USUÁRIOS VINCULADOS ============ --}}
            <div x-show="aba === 'usuarios'" x-cloak>
                @include('receituarios.partials.usuarios-vinculados', [
                    'rotaBuscar' => route('admin.receituarios.usuarios.buscar', $receituario->id),
                    'rotaVincular' => route('admin.receituarios.usuarios.store', $receituario->id),
                    'rotaDesvincular' => fn ($usuarioId) => route('admin.receituarios.usuarios.destroy', [$receituario->id, $usuarioId]),
                    'usuarioAtualId' => null,
                ])
            </div>
        </div>
    </div>
</div>
@endsection
