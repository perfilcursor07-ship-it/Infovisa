@extends('layouts.admin')

@section('title', 'Cadastro de Estabelecimentos')
@section('page-title', 'Cadastro de Estabelecimentos')

@section('content')
@php
    $usuarioLogado = auth('interno')->user();
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $url = fn (array $alterar) => route('admin.relatorios.cadastro-estabelecimentos', array_filter(
        array_merge(request()->except('page'), $alterar), fn ($v) => $v !== null && $v !== ''
    ));

    $periodos = [
        'mes' => 'Este mês',
        'ano' => 'Este ano',
        '12meses' => 'Últimos 12 meses',
        'ano_passado' => 'Ano passado',
        'tudo' => 'Todo o período',
        'personalizado' => 'Personalizado',
    ];

    $estiloSituacao = [
        'ativo' => ['badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'inativo' => ['badge' => 'bg-amber-50 text-amber-800 ring-amber-200', 'dot' => 'bg-amber-500'],
        'baixado' => ['badge' => 'bg-slate-100 text-slate-700 ring-slate-300', 'dot' => 'bg-slate-500'],
    ];

    // Pílulas de filtro (mesmo padrão do relatório de Estabelecimentos e Processos)
    $iconesFiltro = [
        'competencia' => 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
        'municipio_id' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
        'setor' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'tipo_pessoa' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'cadastro' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    ];
    $pilulas = [];
    if ($usuarioLogado->isAdmin()) {
        $pilulas[] = ['nome' => 'competencia', 'rotulo' => 'Competência', 'valor' => (string) $filtros['competencia'], 'padrao' => '', 'mostrarPadrao' => false,
                      'opcoes' => ['' => 'Estadual e municipal', 'estadual' => 'Estadual', 'municipal' => 'Municipal']];
    }
    if ($municipios->isNotEmpty()) {
        $pilulas[] = ['nome' => 'municipio_id', 'rotulo' => 'Município', 'valor' => (string) $filtros['municipio_id'], 'padrao' => '', 'mostrarPadrao' => false, 'busca' => true,
                      'opcoes' => ['' => 'Todos os municípios'] + $municipios->mapWithKeys(fn ($m) => [(string) $m->id => $m->nome])->all()];
    }
    $pilulas[] = ['nome' => 'tipo_pessoa', 'rotulo' => 'Pessoa', 'valor' => (string) $filtros['tipo_pessoa'], 'padrao' => '', 'mostrarPadrao' => false,
                  'opcoes' => ['' => 'Jurídica e física', 'juridica' => 'Jurídica (CNPJ)', 'fisica' => 'Física (CPF)']];
    $pilulas[] = ['nome' => 'setor', 'rotulo' => 'Setor', 'valor' => (string) $filtros['setor'], 'padrao' => '', 'mostrarPadrao' => false,
                  'opcoes' => ['' => 'Público e privado', 'publico' => 'Público', 'privado' => 'Privado']];
    $pilulas[] = ['nome' => 'cadastro', 'rotulo' => 'Cadastro', 'valor' => (string) $filtros['cadastro'], 'padrao' => 'aprovado', 'mostrarPadrao' => true,
                  'opcoes' => ['aprovado' => 'Aprovados', 'todos' => 'Todos (exceto rejeitados)']];

    $qtdFiltrosAtivos = collect($pilulas)->filter(fn ($p) => $p['valor'] !== $p['padrao'])->count()
        + ($filtros['busca'] !== '' ? 1 : 0) + ($filtros['situacao'] ? 1 : 0);
    $urlLimpar = route('admin.relatorios.cadastro-estabelecimentos', array_filter([
        'periodo' => $filtros['periodo'],
        'inicio' => $filtros['periodo'] === 'personalizado' ? $filtros['inicio']->format('Y-m-d') : null,
        'fim' => $filtros['periodo'] === 'personalizado' ? $filtros['fim']->format('Y-m-d') : null,
    ]));
@endphp

<div class="space-y-5">
    {{-- Cabeçalho --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <nav class="flex items-center gap-1.5 text-xs text-slate-500 mb-1.5">
                <a href="{{ route('admin.relatorios.index') }}" class="hover:text-slate-800">Relatórios</a>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-800 font-medium">Cadastro de Estabelecimentos</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Cadastro de Estabelecimentos</h1>
            <p class="text-sm text-slate-500 mt-1">
                Cadastrados de <span class="font-semibold text-slate-700">{{ $filtros['inicio']->format('d/m/Y') }}</span>
                a <span class="font-semibold text-slate-700">{{ $filtros['fim']->format('d/m/Y') }}</span>
                · {{ $usuarioLogado->isAdmin() ? 'Todos os municípios e competências' : ($usuarioLogado->isMunicipal() ? 'Seu município · competência municipal' : 'Competência estadual') }}
            </p>
        </div>
        <a href="{{ route('admin.relatorios.cadastro-estabelecimentos.export', request()->query()) }}"
           class="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-semibold text-emerald-700 bg-emerald-50 ring-1 ring-inset ring-emerald-200 rounded-lg hover:bg-emerald-100 transition self-start md:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Exportar planilha (CSV)
        </a>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('admin.relatorios.cadastro-estabelecimentos') }}" x-ref="formFiltros"
          x-data="{
              aberto: null,
              termo: '',
              personalizado: @js($filtros['periodo'] === 'personalizado'),
              norm(s) { return (s || '').toString().normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); },
              abrir(nome) { this.aberto = this.aberto === nome ? null : nome; this.termo = ''; if (this.aberto) this.$nextTick(() => this.$refs['busca_' + nome]?.focus()); },
              definir(nome, valor) { this.$refs['f_' + nome].value = valor; this.aberto = null; this.$refs.formFiltros.requestSubmit(); },
              escolherPeriodo(p) {
                  this.$refs.f_periodo.value = p;
                  if (p === 'personalizado') { this.personalizado = true; this.$nextTick(() => this.$refs.dataInicio.focus()); return; }
                  this.personalizado = false;
                  this.$refs.formFiltros.requestSubmit();
              },
          }"
          @submit="$el.querySelectorAll('input').forEach(i => {
              if (i.name && i.value === '') i.disabled = true;
              if (['inicio', 'fim'].includes(i.name) && $refs.f_periodo.value !== 'personalizado') i.disabled = true;
          })"
          @keydown.window="if ($event.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) { $event.preventDefault(); $refs.campoBusca.focus(); }"
          @keydown.escape.window="aberto = null"
          class="bg-white rounded-2xl border border-slate-200/80 shadow-sm">
        <input type="hidden" name="periodo" x-ref="f_periodo" value="{{ $filtros['periodo'] }}">
        <input type="hidden" name="situacao" x-ref="f_situacao" value="{{ $filtros['situacao'] }}">
        @foreach($pilulas as $p)
            <input type="hidden" name="{{ $p['nome'] }}" x-ref="f_{{ $p['nome'] }}" value="{{ $p['valor'] }}">
        @endforeach

        {{-- Período --}}
        <div class="flex flex-col lg:flex-row lg:items-center gap-3 px-3 sm:px-4 py-3 border-b border-slate-100">
            <div class="inline-flex p-1 bg-slate-100 rounded-xl overflow-x-auto max-w-full self-start">
                @foreach($periodos as $chave => $rotulo)
                    <button type="button" @click="escolherPeriodo('{{ $chave }}')"
                            :class="(personalizado ? 'personalizado' : '{{ $filtros['periodo'] }}') === '{{ $chave }}' ? 'bg-white text-blue-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:text-slate-900'"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg whitespace-nowrap transition">
                        {{ $rotulo }}
                    </button>
                @endforeach
            </div>
            <div x-show="personalizado" x-cloak class="flex flex-wrap items-center gap-2">
                <input type="date" name="inicio" x-ref="dataInicio" value="{{ $filtros['inicio']->format('Y-m-d') }}"
                       class="px-2.5 py-1.5 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <span class="text-xs text-slate-400">até</span>
                <input type="date" name="fim" x-ref="dataFim" value="{{ $filtros['fim']->format('Y-m-d') }}"
                       class="px-2.5 py-1.5 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">Aplicar</button>
            </div>
        </div>

        {{-- Busca + pílulas --}}
        <div class="flex flex-col xl:flex-row xl:items-center gap-3 p-3 sm:p-4">
            <div class="relative xl:w-80 flex-shrink-0">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="busca" x-ref="campoBusca" value="{{ $filtros['busca'] }}" placeholder="Buscar por nome ou CNPJ/CPF" autocomplete="off"
                       class="w-full pl-9 pr-10 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                @if($filtros['busca'] !== '')
                    <button type="button" title="Limpar busca" @click="$refs.campoBusca.value = ''; $refs.formFiltros.requestSubmit()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-200 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @else
                    <kbd class="hidden sm:inline-flex absolute right-2 top-1/2 -translate-y-1/2 items-center px-1.5 py-0.5 text-[10px] font-semibold text-slate-400 bg-white border border-slate-200 rounded" title="Pressione / para buscar">/</kbd>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2 flex-1">
                @foreach($pilulas as $p)
                    @php
                        $ativo = $p['valor'] !== $p['padrao'];
                        $textoPilula = ($ativo || $p['mostrarPadrao']) ? $p['rotulo'] . ': ' . ($p['opcoes'][$p['valor']] ?? $p['valor']) : $p['rotulo'];
                    @endphp
                    <div class="relative" @click.outside="if (aberto === '{{ $p['nome'] }}') aberto = null">
                        <div class="inline-flex items-center rounded-lg text-xs font-semibold ring-1 ring-inset transition {{ $ativo ? 'bg-blue-50 text-blue-700 ring-blue-200' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}">
                            <button type="button" @click="abrir('{{ $p['nome'] }}')" class="inline-flex items-center gap-1.5 pl-2.5 {{ $ativo ? 'pr-1' : 'pr-2' }} py-1.5 max-w-[16rem]">
                                <svg class="w-3.5 h-3.5 flex-shrink-0 {{ $ativo ? 'text-blue-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconesFiltro[$p['nome']] }}"/></svg>
                                <span class="truncate">{{ $textoPilula }}</span>
                                @unless($ativo)
                                    <svg class="w-3 h-3 flex-shrink-0 text-slate-400 transition-transform" :class="aberto === '{{ $p['nome'] }}' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                @endunless
                            </button>
                            @if($ativo)
                                <button type="button" title="Remover filtro" @click="definir('{{ $p['nome'] }}', @js($p['padrao']))" class="mr-1 p-0.5 rounded hover:bg-blue-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            @endif
                        </div>
                        <div x-show="aberto === '{{ $p['nome'] }}'" x-cloak x-transition.origin.top.left
                             class="absolute left-0 top-full mt-1.5 z-30 {{ !empty($p['busca']) ? 'w-72' : 'w-56' }} max-w-[calc(100vw-2rem)] bg-white rounded-xl border border-slate-200 shadow-xl shadow-slate-900/10 overflow-hidden">
                            <p class="px-3 pt-2.5 pb-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $p['rotulo'] }}</p>
                            @if(!empty($p['busca']))
                                <div class="px-2 pb-2">
                                    <input type="text" x-ref="busca_{{ $p['nome'] }}" x-model="termo" placeholder="Digite para filtrar..."
                                           @keydown.enter.prevent="$el.closest('[x-show]').querySelector('[data-opcao]:not([style*=\'display: none\'])')?.click()"
                                           class="w-full px-2.5 py-1.5 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            @endif
                            <div class="max-h-64 overflow-y-auto pb-1">
                                @foreach($p['opcoes'] as $valorOpcao => $rotuloOpcao)
                                    @php $selecionada = (string) $valorOpcao === $p['valor']; @endphp
                                    <button type="button" data-opcao @click="definir('{{ $p['nome'] }}', @js((string) $valorOpcao))"
                                            @if(!empty($p['busca'])) x-show="!termo || norm(@js($rotuloOpcao)).includes(norm(termo))" @endif
                                            class="w-full flex items-center justify-between gap-2 px-3 py-1.5 text-sm text-left transition {{ $selecionada ? 'text-blue-700 font-semibold bg-blue-50/60' : 'text-slate-700 hover:bg-slate-50' }}">
                                        <span class="truncate">{{ $rotuloOpcao }}</span>
                                        @if($selecionada)
                                            <svg class="w-4 h-4 flex-shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

                @if($qtdFiltrosAtivos > 0)
                    <a href="{{ $urlLimpar }}" class="inline-flex items-center gap-1 px-2 py-1.5 text-xs font-semibold text-slate-500 hover:text-red-600 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Limpar filtros ({{ $qtdFiltrosAtivos }})
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- Indicadores --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <a href="{{ $url(['situacao' => null]) }}#lista" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 hover:ring-2 hover:ring-blue-200 transition">
            <div class="flex items-start justify-between gap-2">
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Cadastrados no período</p>
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M8 7h8M8 11h8M8 15h5"/></svg>
                </span>
            </div>
            <p class="text-3xl font-bold text-slate-900 tabular-nums -mt-1">{{ $fmt($indicadores['total']) }}</p>
            <p class="text-[11px] text-slate-500 mt-2">
                <span class="font-semibold text-blue-700">{{ $fmt($indicadores['estadual']) }}</span> estaduais ·
                <span class="font-semibold text-emerald-700">{{ $fmt($indicadores['municipal']) }}</span> municipais
            </p>
            <p class="text-[11px] text-slate-500">
                {{ $fmt($indicadores['juridica']) }} CNPJ · {{ $fmt($indicadores['fisica']) }} CPF ·
                {{ $fmt($indicadores['publico']) }} públicos
            </p>
        </a>
        @foreach(['ativo' => ['Ativos', 'text-emerald-600', 'bg-emerald-500', 'hover:ring-emerald-200', 'Em funcionamento no sistema'],
                  'inativo' => ['Inativos', 'text-amber-600', 'bg-amber-500', 'hover:ring-amber-200', 'Desativados no sistema'],
                  'baixado' => ['Baixados', 'text-slate-600', 'bg-slate-500', 'hover:ring-slate-300', 'CNPJ baixado na Receita Federal']] as $chave => [$rotulo, $corTexto, $corBarra, $corRing, $dica])
            <a href="{{ $url(['situacao' => $chave]) }}#lista" title="{{ $dica }}"
               class="bg-white rounded-2xl border shadow-sm p-4 hover:ring-2 transition {{ $corRing }} {{ $filtros['situacao'] === $chave ? 'border-blue-300 ring-2 ring-blue-100' : 'border-slate-200/80' }}">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">{{ $rotulo }}</p>
                    <span class="text-xs font-bold tabular-nums {{ $corTexto }}">{{ $indicadores['pct_' . $chave] }}%</span>
                </div>
                <p class="text-3xl font-bold tabular-nums {{ $corTexto }}">{{ $fmt($indicadores[$chave]) }}</p>
                <div class="mt-2 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full {{ $corBarra }}" style="width: {{ $indicadores['pct_' . $chave] }}%"></div>
                </div>
                <p class="text-[11px] text-slate-500 mt-1.5">{{ $dica }}</p>
            </a>
        @endforeach
    </div>

    {{-- Gráfico mensal + por município --}}
    <div class="grid grid-cols-1 {{ $usuarioLogado->isMunicipal() ? '' : 'xl:grid-cols-3' }} gap-4">
        <div class="{{ $usuarioLogado->isMunicipal() ? '' : 'xl:col-span-2' }} bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2 mb-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Cadastros por mês</h3>
                    <p class="text-[11px] text-slate-500">Quantos estabelecimentos foram cadastrados em cada mês, pela situação atual</p>
                </div>
                @if($indicadores['total'] > 0)
                    <div class="flex flex-wrap gap-2 text-[11px]">
                        <span class="px-2 py-1 rounded-md bg-slate-50 text-slate-600">Média: <strong class="text-slate-900">{{ number_format($graficoMensal['media'], 1, ',', '.') }}</strong>/mês</span>
                        @if($graficoMensal['pico'])
                            <span class="px-2 py-1 rounded-md bg-blue-50 text-blue-700">Pico: <strong>{{ $fmt($graficoMensal['pico']['total']) }}</strong> em {{ $graficoMensal['pico']['mes'] }}</span>
                        @endif
                    </div>
                @endif
            </div>
            @if($indicadores['total'] > 0)
                <div class="h-72"><canvas id="chartCadastrosMes"></canvas></div>
            @else
                <div class="h-72 flex items-center justify-center text-sm text-slate-400">Nenhum cadastro no período</div>
            @endif
        </div>

        @unless($usuarioLogado->isMunicipal())
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-slate-900">Por município</h3>
            <p class="text-[11px] text-slate-500 mb-3">Municípios com mais cadastros no período</p>
            @php $maiorMunicipio = max(1, (int) ($porMunicipio->first()['total'] ?? 1)); @endphp
            <div class="space-y-2.5 max-h-72 overflow-y-auto pr-1">
                @forelse($porMunicipio->take(15) as $m)
                    <div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-slate-700 truncate">{{ $m['municipio'] }}</span>
                            <span class="font-bold text-slate-900 tabular-nums">{{ $fmt($m['total']) }}</span>
                        </div>
                        <div class="mt-1 flex h-1.5 rounded-full bg-slate-100 overflow-hidden" title="{{ $m['ativo'] }} ativos · {{ $m['inativo'] }} inativos · {{ $m['baixado'] }} baixados">
                            <div class="bg-emerald-500" style="width: {{ $m['ativo'] / $maiorMunicipio * 100 }}%"></div>
                            <div class="bg-amber-500" style="width: {{ $m['inativo'] / $maiorMunicipio * 100 }}%"></div>
                            <div class="bg-slate-400" style="width: {{ $m['baixado'] / $maiorMunicipio * 100 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 text-center py-10">Sem dados</p>
                @endforelse
            </div>
        </div>
        @endunless
    </div>

    {{-- Lista --}}
    <div id="lista" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden scroll-mt-4">
        <div class="px-5 pt-4 border-b border-slate-100">
            <div class="mb-3">
                <h3 class="text-sm font-semibold text-slate-900">Estabelecimentos cadastrados</h3>
                <p class="text-[11px] text-slate-500">{{ $fmt($totalListagem) }} {{ $totalListagem === 1 ? 'resultado' : 'resultados' }} · mais recentes primeiro</p>
            </div>
            <div class="flex gap-1 overflow-x-auto -mb-px">
                @foreach([null => ['Todos', $indicadores['total']], 'ativo' => ['Ativos', $indicadores['ativo']], 'inativo' => ['Inativos', $indicadores['inativo']], 'baixado' => ['Baixados', $indicadores['baixado']]] as $chave => [$rotulo, $total])
                    @php $abaAtiva = ($filtros['situacao'] ?? null) === ($chave ?: null); @endphp
                    <a href="{{ $url(['situacao' => $chave ?: null]) }}#lista"
                       class="whitespace-nowrap inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold border-b-2 transition {{ $abaAtiva ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                        {{ $rotulo }}
                        <span class="px-1.5 py-0.5 rounded-md text-[10px] tabular-nums {{ $abaAtiva ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600' }}">{{ $fmt($total) }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        @if($estabelecimentos->isEmpty())
            <div class="px-5 py-14 text-center">
                <p class="text-sm font-semibold text-slate-800">Nenhum estabelecimento encontrado</p>
                <p class="text-xs text-slate-500 mt-1">Ajuste o período ou os filtros.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50/80 text-[11px] font-semibold text-slate-500 uppercase tracking-wider text-left">
                            <th class="px-5 py-2.5">Estabelecimento</th>
                            <th class="px-3 py-2.5">Município</th>
                            <th class="px-3 py-2.5">Cadastrado em</th>
                            <th class="px-3 py-2.5">Situação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($estabelecimentos as $linha)
                            @php $e = $linha['estabelecimento']; @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors align-top">
                                <td class="px-5 py-3 min-w-[260px]">
                                    <a href="{{ route('admin.estabelecimentos.show', $e->id) }}" class="font-semibold text-slate-900 hover:text-blue-700">
                                        {{ $e->nome_fantasia ?: ($e->razao_social ?: $e->nome_completo) }}
                                    </a>
                                    <p class="text-[11px] text-slate-500 tabular-nums mt-0.5">{{ $e->documento_formatado }}</p>
                                    <div class="flex flex-wrap gap-1.5 mt-1.5">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $linha['competencia'] === 'estadual' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700' }}">{{ ucfirst($linha['competencia']) }}</span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $linha['setor'] === 'publico' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-600' }}">{{ $linha['setor'] === 'publico' ? 'Público' : 'Privado' }}</span>
                                        @if($e->status !== 'aprovado')
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-yellow-50 text-yellow-800">Cadastro {{ $e->status }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-xs text-slate-700 whitespace-nowrap">{{ $linha['municipio'] }}</td>
                                <td class="px-3 py-3 text-xs text-slate-700 whitespace-nowrap tabular-nums">
                                    {{ $e->created_at?->format('d/m/Y') }}
                                    <p class="text-[11px] text-slate-400">{{ $e->created_at?->copy()->locale('pt_BR')->diffForHumans() }}</p>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset {{ $estiloSituacao[$linha['situacao']]['badge'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $estiloSituacao[$linha['situacao']]['dot'] }}"></span>
                                        {{ $situacoes[$linha['situacao']] }}
                                    </span>
                                    @if($linha['situacao'] === 'inativo' && $e->motivo_desativacao)
                                        <p class="text-[11px] text-slate-500 mt-1 max-w-[220px] truncate" title="{{ $e->motivo_desativacao }}">{{ $e->motivo_desativacao }}</p>
                                    @elseif($linha['situacao'] === 'baixado')
                                        <p class="text-[11px] text-slate-500 mt-1">Receita: {{ $e->situacao_label }}</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($estabelecimentos->hasPages())
                <div class="px-5 py-3 border-t border-slate-100">{{ $estabelecimentos->fragment('lista')->links() }}</div>
            @endif
        @endif
    </div>

    <p class="text-[11px] text-slate-400 px-1 leading-relaxed">
        <strong>Ativo:</strong> em funcionamento no sistema. <strong>Inativo:</strong> desativado no sistema por um administrador.
        <strong>Baixado:</strong> CNPJ com situação cadastral "Baixada" na Receita Federal (conforme a última consulta registrada no cadastro).
        O período considera a data de cadastro. Cadastros rejeitados não entram nas contagens.
    </p>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    const el = document.getElementById('chartCadastrosMes');
    if (!el || typeof Chart === 'undefined') return;

    Chart.defaults.font.family = "'Inter','Segoe UI',system-ui,sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#64748b';

    const dados = @json($graficoMensal);
    const cores = { ativo: '#1baf7a', inativo: '#eda100', baixado: '#94a3b8' };
    const rotulos = { ativo: 'Ativos', inativo: 'Inativos', baixado: 'Baixados' };
    const num = n => Number(n || 0).toLocaleString('pt-BR');

    new Chart(el, {
        type: 'bar',
        data: {
            labels: dados.rotulos,
            datasets: Object.keys(rotulos).map(chave => ({
                label: rotulos[chave],
                data: dados.series[chave],
                backgroundColor: cores[chave],
                borderRadius: 4,
                borderSkipped: false,
                borderColor: '#fff',
                borderWidth: 1,
                maxBarThickness: 36,
                stack: 'cadastros',
            })),
        },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'rectRounded', boxWidth: 8, boxHeight: 8, padding: 14, color: '#334155' } },
                tooltip: {
                    backgroundColor: '#0f172a', titleColor: '#fff', bodyColor: '#e2e8f0', footerColor: '#94a3b8',
                    padding: 10, cornerRadius: 8, usePointStyle: true,
                    callbacks: {
                        label: ctx => ` ${ctx.dataset.label}: ${num(ctx.parsed.y)}`,
                        footer: items => 'Total: ' + num(dados.totais[items[0].dataIndex]),
                    },
                },
            },
            scales: {
                x: { stacked: true, grid: { display: false }, border: { display: false } },
                y: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef2f6', drawTicks: false }, border: { display: false } },
            },
        },
    });
})();
</script>
@endpush
