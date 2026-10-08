@extends('layouts.admin')

@section('title', 'Receituários')
@section('page-title', 'Receituários')

@section('content')
@php $podeRemover = auth('interno')->user()?->isAdmin(); @endphp
<div class="mx-auto max-w-8xl space-y-4" x-data="{ remover: null }">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">{{ session('error') }}</div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Solicitações de receituário</h2>
            <p class="mt-0.5 text-sm text-slate-500">Consulte os cadastros e acompanhe cada situação.</p>
        </div>

        {{-- Novo cadastro --}}
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" 
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Novo Receituário
                <svg class="ml-1 h-4 w-4 transition-transform" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            
            <div x-show="open" 
                 @click.away="open = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] rounded-xl border border-slate-200 bg-white shadow-xl"
                 style="display: none;">
                
                <div class="py-2">
                    <a href="{{ route('admin.receituarios.create', ['tipo' => 'medico']) }}" 
                       class="flex items-center gap-3 px-4 py-3 hover:bg-blue-50 transition-colors group">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-900">Médico, Dentista ou Veterinário</div>
                            <div class="text-xs text-gray-500">Cadastro de profissionais de saúde</div>
                        </div>
                    </a>
                    
                    <a href="{{ route('admin.receituarios.create', ['tipo' => 'instituicao']) }}" 
                       class="flex items-center gap-3 px-4 py-3 hover:bg-green-50 transition-colors group">
                        <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-900">Hospital, Clínica e Similares</div>
                            <div class="text-xs text-gray-500">Cadastro de instituições de saúde</div>
                        </div>
                    </a>
                    
                    <a href="{{ route('admin.receituarios.create', ['tipo' => 'secretaria']) }}" 
                       class="flex items-center gap-3 px-4 py-3 hover:bg-purple-50 transition-colors group">
                        <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-900">Secretaria de Saúde e VISA</div>
                            <div class="text-xs text-gray-500">Cadastro de órgãos públicos</div>
                        </div>
                    </a>
                    
                    <a href="{{ route('admin.receituarios.create', ['tipo' => 'talidomida']) }}" 
                       class="flex items-center gap-3 px-4 py-3 hover:bg-red-50 transition-colors group">
                        <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center group-hover:bg-red-200 transition-colors">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-900">Prescritor de Talidomida</div>
                            <div class="text-xs text-gray-500">Cadastro especial para talidomida</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtro principal por situação --}}
    @php
        $abasStatus = [
            'todos' => ['rotulo' => 'Todos', 'cor' => 'slate'],
            'aguardando_assinatura' => ['rotulo' => 'Aguardando documento', 'cor' => 'amber'],
            'ativo' => ['rotulo' => 'Aprovados', 'cor' => 'emerald'],
            'rejeitado' => ['rotulo' => 'Rejeitados', 'cor' => 'red'],
        ];
        $corAbaAtiva = [
            'slate' => 'border-slate-800 bg-slate-800 text-white',
            'amber' => 'border-amber-300 bg-amber-50 text-amber-900',
            'emerald' => 'border-emerald-300 bg-emerald-50 text-emerald-900',
            'red' => 'border-red-300 bg-red-50 text-red-900',
        ];
    @endphp
    <nav aria-label="Filtrar receituários por situação" class="flex gap-2 overflow-x-auto pb-1">
        @foreach($abasStatus as $chave => $aba)
            @php
                $ativa = $status === $chave;
                $total = $chave === 'todos' ? $porStatus->sum() : ($porStatus[$chave] ?? 0);
            @endphp
            <a href="{{ route('admin.receituarios.index', array_filter(['status' => $chave] + request()->except(['page', 'status']))) }}"
               @if($ativa) aria-current="page" @endif
               class="inline-flex min-h-10 shrink-0 items-center gap-2 rounded-lg border px-3.5 py-2 text-sm font-semibold transition {{ $ativa ? $corAbaAtiva[$aba['cor']] : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50' }}">
                {{ $aba['rotulo'] }}
                <span class="rounded-md px-1.5 py-0.5 text-xs tabular-nums {{ $ativa ? 'bg-white/70 text-current' : 'bg-slate-100 text-slate-600' }}">{{ $total }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Busca e filtro secundário por tipo --}}
    <div class="rounded-xl border border-slate-200 bg-white p-3 sm:p-4">
        <form method="GET" action="{{ route('admin.receituarios.index') }}" class="grid grid-cols-1 items-end gap-3 sm:grid-cols-[minmax(0,1fr)_220px_auto]">
            <div class="min-w-0">
                <label for="receituario-busca" class="mb-1 block text-xs font-semibold text-slate-600">Buscar por nome ou documento</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="m16 16 4.5 4.5"/></svg>
                    <input id="receituario-busca" type="search" name="busca" value="{{ request('busca') }}"
                           placeholder="Nome, CPF, CNPJ ou razão social"
                           class="h-10 w-full rounded-lg border border-slate-300 bg-white pl-9 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
            </div>
            
            <div>
                <label for="receituario-tipo" class="mb-1 block text-xs font-semibold text-slate-600">Tipo de cadastro</label>
                <select id="receituario-tipo" name="tipo" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="">Todos</option>
                    <option value="medico" {{ request('tipo') == 'medico' ? 'selected' : '' }}>Médico/Dentista/Vet</option>
                    <option value="instituicao" {{ request('tipo') == 'instituicao' ? 'selected' : '' }}>Instituição</option>
                    <option value="secretaria" {{ request('tipo') == 'secretaria' ? 'selected' : '' }}>Secretaria</option>
                    <option value="talidomida" {{ request('tipo') == 'talidomida' ? 'selected' : '' }}>Talidomida</option>
                </select>
            </div>
            
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="flex items-center gap-2">
            <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M7 12h10m-7 6h4"/></svg>
                Filtrar
            </button>
            @if(request()->hasAny(['busca', 'tipo']))
                <a href="{{ route('admin.receituarios.index', ['status' => $status]) }}" class="inline-flex h-10 items-center rounded-lg px-3 text-sm font-semibold text-slate-600 hover:bg-slate-100">
                    Limpar busca
                </a>
            @endif
            </div>
        </form>
    </div>

    {{-- Resultados --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        @if($receituarios->count() > 0)
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
                <h3 class="text-sm font-bold text-slate-900">Resultados</h3>
                <p class="text-xs text-slate-500">{{ number_format($receituarios->total(), 0, ',', '.') }} cadastro(s)</p>
            </div>
            <div class="overflow-x-auto">
            <table class="min-w-[960px] w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">Tipo</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">Nome / Razão social</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">CPF / CNPJ</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">Município</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">Situação</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">Data</th>
                        <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wide text-slate-500">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($receituarios as $receituario)
                        <tr class="transition-colors hover:bg-slate-50/80">
                            <td class="px-4 py-3.5">
                                <span class="inline-flex max-w-[11rem] rounded-md px-2 py-1 text-xs font-semibold leading-snug
                                    {{ $receituario->tipo == 'medico' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $receituario->tipo == 'instituicao' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $receituario->tipo == 'secretaria' ? 'bg-purple-100 text-purple-800' : '' }}
                                    {{ $receituario->tipo == 'talidomida' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ $receituario->tipo_nome }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="text-sm font-semibold text-slate-900">{{ $receituario->identificador }}</div>
                                @if($receituario->solicitante_descricao)
                                    <div class="mt-0.5 text-xs {{ $receituario->solicitante_proprio === false ? 'text-violet-700' : 'text-blue-700' }}">{{ $receituario->solicitante_descricao }}</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-sm text-slate-600">
                                {{ $receituario->cpf_formatado ?? $receituario->cnpj_formatado }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-sm text-slate-600">
                                {{ $receituario->municipio->nome ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $receituario->situacao['classe'] }}">
                                    {{ $receituario->situacao['label'] }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-sm text-slate-600">
                                {{ $receituario->created_at->format('d/m/Y') }}
                                @if($receituario->status === 'pendente' && $receituario->documento_assinado_enviado_em)
                                    <div class="mt-0.5 text-xs text-slate-400">enviado {{ $receituario->documento_assinado_enviado_em->format('d/m/Y') }}</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-right text-sm font-medium">
                                <a href="{{ route('admin.receituarios.show', $receituario->id) }}"
                                   aria-label="Ver cadastro {{ $receituario->identificador }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:border-blue-200 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
                                    Ver
                                </a>
                                @if($podeRemover)
                                    <button type="button"
                                            @click="remover = @js(['url' => route('admin.receituarios.destroy', ['id' => $receituario->id] + request()->only(['status', 'tipo', 'busca', 'page'])), 'nome' => $receituario->identificador, 'processos' => (int) ($receituario->estabelecimento?->processos_count ?? 0)])"
                                            aria-label="Remover cadastro {{ $receituario->identificador }}" title="Remover cadastro"
                                            class="ml-1 inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-1.5 text-slate-400 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $receituarios->links() }}
            </div>
        @else
            <div class="px-6 py-14 text-center">
                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3.75h7l5 5V20a1.25 1.25 0 0 1-1.25 1.25h-10.5A1.25 1.25 0 0 1 6 20V5a1.25 1.25 0 0 1 1-1.25Z"/><path stroke-linecap="round" stroke-width="1.8" d="M14 4v5h5M9 13h6m-6 3.5h6"/>
                </svg>
                </span>
                <h3 class="mt-3 text-sm font-bold text-slate-900">Nenhum receituário encontrado</h3>
                <p class="mt-1 text-sm text-slate-500">Ajuste a situação ou remova algum filtro de busca.</p>
                @if(request()->hasAny(['busca', 'tipo']))
                    <a href="{{ route('admin.receituarios.index', ['status' => $status]) }}" class="mt-3 inline-flex items-center text-sm font-semibold text-blue-700 hover:text-blue-900">Limpar busca e tipo</a>
                @endif
            </div>
        @endif
    </div>

    {{-- Confirmação de remoção (somente administrador) --}}
    @if($podeRemover)
        <template x-teleport="body">
            <div x-show="remover" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="remover = null">
                <div class="absolute inset-0 bg-slate-900/50" @click="remover = null"></div>
                <form x-show="remover" method="POST" :action="remover?.url" class="relative w-full max-w-md rounded-2xl bg-white p-5 shadow-xl">
                    @csrf
                    @method('DELETE')
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h3 class="text-base font-bold text-slate-900">Remover cadastro</h3>
                            <p class="mt-1 text-sm text-slate-600">
                                O cadastro de <strong class="text-slate-900" x-text="remover?.nome"></strong> sairá da lista da Vigilância e da área da empresa.
                            </p>
                            <p x-show="remover?.processos > 0" class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                                Este cadastro tem <strong x-text="remover?.processos"></strong>
                                <span x-text="remover?.processos === 1 ? 'processo de receituário, que também será removido.' : 'processos de receituário, que também serão removidos.'"></span>
                            </p>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" @click="remover = null" class="h-9 px-4 text-sm font-semibold text-slate-600 hover:text-slate-900">Cancelar</button>
                        <button type="submit" class="h-9 rounded-lg bg-red-600 px-4 text-sm font-semibold text-white hover:bg-red-700">Remover cadastro</button>
                    </div>
                </form>
            </div>
        </template>
    @endif
</div>
@endsection
