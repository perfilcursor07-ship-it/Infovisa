@extends('layouts.company')

@section('title', 'Profissionais cadastrados')
@section('page-title', 'Receituário · Profissionais')

@php
    // Tipos de cadastro (classes completas para o Tailwind detectar)
    $tipos = [
        'medico' => [
            'titulo' => 'Médico, Dentista ou Veterinário',
            'curto' => 'Médico/Dentista/Vet.',
            'texto' => 'Feito pelo próprio profissional, com a conta dele',
            'icone' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            'chip' => 'bg-blue-50 text-blue-600',
            'hover' => 'hover:border-blue-300 hover:shadow-blue-100',
            'link' => 'text-blue-600',
            'badge' => 'bg-blue-50 text-blue-700',
        ],
        'instituicao' => [
            'titulo' => 'Hospital, Clínica e Similares',
            'curto' => 'Instituição',
            'texto' => 'Instituições de saúde com CNPJ',
            'icone' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            'chip' => 'bg-emerald-50 text-emerald-600',
            'hover' => 'hover:border-emerald-300 hover:shadow-emerald-100',
            'link' => 'text-emerald-600',
            'badge' => 'bg-emerald-50 text-emerald-700',
        ],
        'secretaria' => [
            'titulo' => 'Secretaria de Saúde e VISA',
            'curto' => 'Secretaria',
            'texto' => 'Órgãos públicos de saúde',
            'icone' => 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z',
            'chip' => 'bg-violet-50 text-violet-600',
            'hover' => 'hover:border-violet-300 hover:shadow-violet-100',
            'link' => 'text-violet-600',
            'badge' => 'bg-violet-50 text-violet-700',
        ],
        'talidomida' => [
            'titulo' => 'Prescritor de Talidomida',
            'curto' => 'Talidomida',
            'texto' => 'Cadastro especial para prescrição',
            'icone' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
            'chip' => 'bg-rose-50 text-rose-600',
            'hover' => 'hover:border-rose-300 hover:shadow-rose-100',
            'link' => 'text-rose-600',
            'badge' => 'bg-rose-50 text-rose-700',
        ],
    ];
    $status = [
        'aguardando_assinatura' => ['rotulo' => 'Cadastro em análise', 'badge' => 'bg-blue-50 text-blue-700', 'ponto' => 'bg-blue-500'],
        'pendente' => ['rotulo' => 'Cadastro em análise', 'badge' => 'bg-blue-50 text-blue-700', 'ponto' => 'bg-blue-500'],
        'ativo' => ['rotulo' => 'Cadastro aprovado', 'badge' => 'bg-green-50 text-green-700', 'ponto' => 'bg-green-500'],
        'rejeitado' => ['rotulo' => 'Correção solicitada', 'badge' => 'bg-red-50 text-red-700', 'ponto' => 'bg-red-500'],
        'inativo' => ['rotulo' => 'Inativo', 'badge' => 'bg-slate-100 text-slate-600', 'ponto' => 'bg-slate-400'],
    ];
    $urlStatus = fn ($s) => route('company.receituarios.index', array_filter(['status' => $s] + request()->except(['page', 'status'])));
@endphp

@section('content')
<div class="max-w-8xl mx-auto space-y-4">
    {{-- Mensagem de sucesso --}}
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200/80 rounded-xl px-4 py-3 flex items-center gap-3">
        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
    </div>
    @endif
    @if(session('error'))
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm font-medium text-amber-800">
        {{ session('error') }}
    </div>
    @endif

    {{-- Cabeçalho --}}
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </div>
            <div>
                <h1 class="text-lg font-bold text-slate-900 tracking-tight leading-tight">Profissionais cadastrados</h1>
                <p class="text-xs text-slate-400">Cadastre o profissional; com o cadastro aprovado pela Vigilância, abra o processo de receituário.</p>
            </div>
        </div>

        {{-- Nova solicitação --}}
        <div class="relative" x-data="{ aberto: false }" @click.outside="aberto = false" @keydown.escape.window="aberto = false">
            <button type="button" @click="aberto = !aberto"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors shadow-sm shadow-blue-600/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/></svg>
                Cadastrar profissional
                <svg class="w-3.5 h-3.5 transition-transform" :class="aberto && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="aberto" x-cloak x-transition.origin.top.right
                 class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl ring-1 ring-slate-200 p-1.5 z-30">
                @foreach($tipos as $codigo => $tipo)
                    @if($codigo === 'medico')
                    <a href="{{ route('company.receituarios.create', ['tipo' => $codigo]) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-50 transition">
                    @else
                    <div aria-disabled="true" title="Cadastro indisponível no momento" class="flex items-center gap-3 px-3 py-2.5 rounded-lg opacity-50 cursor-not-allowed">
                    @endif
                        <span class="w-9 h-9 rounded-lg {{ $tipo['chip'] }} flex items-center justify-center flex-shrink-0">
                            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $tipo['icone'] }}"/></svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-slate-900">{{ $tipo['titulo'] }}</span>
                            <span class="block text-[11px] text-slate-500">{{ $tipo['texto'] }}</span>
                            @if($codigo !== 'medico')
                                <span class="block text-[10px] font-semibold text-slate-500">Indisponível no momento</span>
                            @endif
                        </span>
                    @if($codigo === 'medico')
                    </a>
                    @else
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- Estatísticas (clique para filtrar) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
        <a href="{{ $urlStatus(null) }}"
           class="flex items-center gap-3 bg-white rounded-xl px-3 py-2.5 border shadow-sm hover:shadow transition-all {{ !request('status') ? 'border-blue-300 ring-2 ring-blue-100' : 'border-slate-200/80 hover:border-blue-200' }}">
            <div class="w-9 h-9 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </div>
            <div class="leading-tight">
                <p class="text-lg font-bold text-slate-900 leading-none">{{ $estatisticas['total'] }}</p>
                <p class="text-[11px] font-medium text-slate-500 mt-0.5">Total</p>
            </div>
        </a>
        <a href="{{ $urlStatus('pendente') }}"
           class="flex items-center gap-3 bg-white rounded-xl px-3 py-2.5 border shadow-sm hover:shadow transition-all {{ request('status') === 'pendente' ? 'border-blue-300 ring-2 ring-blue-100' : 'border-slate-200/80 hover:border-blue-200' }}">
            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="leading-tight">
                <p class="text-lg font-bold text-blue-600 leading-none">{{ $estatisticas['pendente'] }}</p>
                <p class="text-[11px] font-medium text-slate-500 mt-0.5">Em análise</p>
            </div>
        </a>
        <a href="{{ $urlStatus('ativo') }}"
           class="flex items-center gap-3 bg-white rounded-xl px-3 py-2.5 border shadow-sm hover:shadow transition-all {{ request('status') === 'ativo' ? 'border-green-300 ring-2 ring-green-100' : 'border-slate-200/80 hover:border-green-200' }}">
            <div class="w-9 h-9 rounded-lg bg-green-50 text-green-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="leading-tight">
                <p class="text-lg font-bold text-green-600 leading-none">{{ $estatisticas['ativo'] }}</p>
                <p class="text-[11px] font-medium text-slate-500 mt-0.5">Aprovados</p>
            </div>
        </a>
        <a href="{{ $urlStatus('rejeitado') }}"
           class="flex items-center gap-3 bg-white rounded-xl px-3 py-2.5 border shadow-sm hover:shadow transition-all {{ request('status') === 'rejeitado' ? 'border-red-300 ring-2 ring-red-100' : 'border-slate-200/80 hover:border-red-200' }}">
            <div class="w-9 h-9 rounded-lg bg-red-50 text-red-500 flex items-center justify-center flex-shrink-0">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
            <div class="leading-tight">
                <p class="text-lg font-bold text-red-600 leading-none">{{ $estatisticas['rejeitado'] }}</p>
                <p class="text-[11px] font-medium text-slate-500 mt-0.5">Correção solicitada</p>
            </div>
        </a>
    </div>

    {{-- Tipos de solicitação --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-4">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Cadastrar profissional</h2>
                <p class="text-[11px] text-slate-500">Escolha o tipo de cadastro. A Vigilância analisa os documentos e, aprovado, você abre o processo de receituário.</p>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
            @foreach($tipos as $codigo => $tipo)
                @if($codigo === 'medico')
                <a href="{{ route('company.receituarios.create', ['tipo' => $codigo]) }}"
                   class="group flex items-start gap-3 rounded-xl border border-slate-200 p-3.5 hover:shadow-md transition-all {{ $tipo['hover'] }}">
                @else
                <div aria-disabled="true" title="Cadastro indisponível no momento"
                     class="flex items-start gap-3 rounded-xl border border-slate-200 p-3.5 opacity-50 grayscale cursor-not-allowed">
                @endif
                    <span class="w-10 h-10 rounded-xl {{ $tipo['chip'] }} flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $tipo['icone'] }}"/></svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-[13px] font-semibold text-slate-900 leading-snug">{{ $tipo['titulo'] }}</span>
                        <span class="block text-[11px] text-slate-500 mt-0.5">{{ $tipo['texto'] }}</span>
                        @if($codigo === 'medico')
                        <span class="inline-flex items-center gap-1 mt-2 text-[11px] font-semibold {{ $tipo['link'] }}">
                            Cadastrar
                            <svg class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </span>
                        @else
                        <span class="inline-flex mt-2 text-[11px] font-semibold text-slate-500">Indisponível no momento</span>
                        @endif
                    </span>
                @if($codigo === 'medico')
                </a>
                @else
                </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-2">
        <form method="GET" action="{{ route('company.receituarios.index') }}" class="flex flex-col lg:flex-row gap-2">
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Buscar por nome, razão social, CPF ou CNPJ..."
                       class="w-full pl-9 pr-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition">
            </div>
            <select name="tipo" class="px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                <option value="">Todos os tipos</option>
                @foreach($tipos as $codigo => $tipo)
                    <option value="{{ $codigo }}" @selected(request('tipo') === $codigo)>{{ $tipo['titulo'] }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-semibold shadow-sm transition">Buscar</button>
                @if(request()->hasAny(['busca', 'tipo', 'status']))
                    <a href="{{ route('company.receituarios.index') }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 text-sm font-semibold transition">Limpar</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Lista --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
        @if($receituarios->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/80 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Profissional / Instituição</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Município</th>
                        <th class="px-4 py-2.5 text-center text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Cadastro</th>
                        <th class="px-4 py-2.5 text-center text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Processos</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Cadastrado em</th>
                        <th class="px-4 py-2.5 text-right text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($receituarios as $receituario)
                        @php
                            $tipo = $tipos[$receituario->tipo] ?? null;
                            $st = $status[$receituario->status] ?? ['rotulo' => ucfirst($receituario->status), 'badge' => 'bg-slate-100 text-slate-600', 'ponto' => 'bg-slate-400'];
                        @endphp
                        <tr class="group hover:bg-blue-50/40 transition-colors">
                            <td class="px-4 py-3">
                                <a href="{{ route('company.receituarios.show', $receituario->id) }}" class="text-[13px] font-semibold text-slate-800 hover:text-blue-700">{{ $receituario->identificador }}</a>
                                <div class="text-[11px] text-slate-400 tabular-nums">{{ $receituario->cpf_formatado ?? $receituario->cnpj_formatado }}</div>
                                @if($receituario->tipo === 'medico' && $receituario->solicitante_proprio !== null)
                                    <div class="mt-0.5 text-[11px] font-medium {{ $receituario->solicitante_proprio ? 'text-blue-600' : 'text-violet-600' }}">
                                        {{ $receituario->solicitante_proprio ? 'Você é o profissional' : 'Em nome do profissional' }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[11px] font-semibold rounded-md {{ $tipo['badge'] ?? 'bg-slate-100 text-slate-600' }}">
                                    @if($tipo)<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tipo['icone'] }}"/></svg>@endif
                                    {{ $tipo['curto'] ?? $receituario->tipo_nome }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-[13px] text-slate-600">{{ $receituario->municipio->nome ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full {{ $st['badge'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $st['ponto'] }}"></span>
                                    {{ $st['rotulo'] }}
                                </span>
                                @if($receituario->status === 'rejeitado')
                                    <a href="{{ route('company.receituarios.show', $receituario->id) }}" class="block mt-1 text-[11px] font-semibold text-red-600 hover:underline">Ver o que corrigir →</a>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                @if($receituario->isAprovado())
                                    <a href="{{ route('company.receituarios.show', [$receituario->id, 'aba' => 'processos']) }}"
                                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold {{ ($receituario->estabelecimento?->processos_count ?? 0) ? 'bg-slate-100 text-slate-700' : 'bg-emerald-50 text-emerald-700' }} hover:underline">
                                        {{ ($receituario->estabelecimento?->processos_count ?? 0) ?: 'Abrir processo' }}
                                    </a>
                                @else
                                    <span class="text-[11px] text-slate-400" title="Disponível depois da aprovação do cadastro">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-[13px] text-slate-600 tabular-nums">{{ $receituario->created_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ route('company.receituarios.show', $receituario->id) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[12px] font-semibold text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                        Abrir
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($receituarios->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">{{ $receituarios->links() }}</div>
        @endif
        @else
        <div class="px-6 py-14 text-center">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <p class="text-sm font-semibold text-slate-800">
                {{ request()->hasAny(['busca', 'tipo', 'status']) ? 'Nenhum profissional encontrado' : 'Nenhum profissional cadastrado ainda' }}
            </p>
            <p class="text-xs text-slate-500 mt-1">
                {{ request()->hasAny(['busca', 'tipo', 'status']) ? 'Ajuste os filtros para ver outros resultados.' : 'Cadastre o profissional acima. Com o cadastro aprovado, você abre o processo de receituário.' }}
            </p>
        </div>
        @endif
    </div>
</div>
@endsection
