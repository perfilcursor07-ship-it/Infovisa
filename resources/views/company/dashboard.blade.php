@extends('layouts.company')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php
    $temAlertas = $alertasPendentes->count() > 0 || 
                  $documentosPendentesVisualizacao->count() > 0 || 
                  $documentosRejeitados->count() > 0 || 
                  $documentosComPrazo->count() > 0;
    $totalAlertas = $alertasPendentes->count() + 
                    $documentosPendentesVisualizacao->count() + 
                    $documentosRejeitados->count() + 
                    $documentosComPrazo->count();
@endphp

<div class="space-y-5">

    {{-- Header --}}
    @php
        $horaAtualExt = (int) now()->format('H');
        $saudacaoExt = $horaAtualExt < 12 ? 'Bom dia' : ($horaAtualExt < 18 ? 'Boa tarde' : 'Boa noite');
    @endphp
    <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-500/15">
        <div class="pointer-events-none absolute -top-16 -right-16 w-52 h-52 rounded-full bg-white/10 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-20 right-40 w-44 h-44 rounded-full bg-fuchsia-400/20 blur-3xl"></div>
        <div class="relative flex flex-col md:flex-row md:items-center gap-3 p-4">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="hidden sm:flex w-11 h-11 rounded-xl bg-white/15 ring-1 ring-white/25 items-center justify-center flex-shrink-0">
                    <span class="text-xl">🏢</span>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-white/70">{{ ucfirst(now()->locale('pt_BR')->isoFormat('dddd, D [de] MMMM')) }}</p>
                    <h1 class="text-lg sm:text-xl font-bold tracking-tight truncate">{{ $saudacaoExt }}, {{ explode(' ', auth('externo')->user()->nome)[0] }}! 👋</h1>
                    <p class="text-xs sm:text-sm text-white/80 mt-0.5">
                        @if($totalAlertas > 0)
                            Você tem <span class="font-semibold text-white">{{ $totalAlertas }} {{ $totalAlertas == 1 ? 'pendência' : 'pendências' }}</span> que {{ $totalAlertas == 1 ? 'precisa' : 'precisam' }} da sua atenção.
                        @else
                            Tudo em dia por aqui. Nenhuma pendência no momento. ✨
                        @endif
                    </p>
                </div>
            </div>
            @if($totalAlertas > 0)
            <a href="{{ route('company.alertas.index') }}"
               class="flex-shrink-0 self-start md:self-auto inline-flex items-center gap-1.5 px-3 py-2 bg-white text-indigo-700 text-xs font-semibold rounded-lg shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                Ver pendências
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
            @endif
        </div>
    </div>

    {{-- Tutorial de primeiro acesso --}}
    <div x-data="tutorialPrimeiroAcesso()" x-show="ativo" x-cloak>
        {{-- Overlay escuro --}}
        <div class="fixed inset-0 bg-black/50 z-[9998]" @click="proximo()"></div>

        {{-- Card do tutorial --}}
        <div class="fixed z-[9999] bg-white rounded-xl shadow-2xl border border-slate-200 p-5 w-80"
             :style="posicao"
             x-transition>
            {{-- Indicador de passos --}}
            <div class="flex items-center gap-1 mb-3">
                <template x-for="(s, i) in passos" :key="i">
                    <div class="h-1 flex-1 rounded-full transition-all"
                         :class="i <= passo ? 'bg-blue-600' : 'bg-slate-200'"></div>
                </template>
            </div>

            {{-- Ícone e conteúdo --}}
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                     :class="passos[passo]?.cor || 'bg-blue-100'">
                    <span x-html="passos[passo]?.icone" class="w-5 h-5"></span>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-slate-900" x-text="passos[passo]?.titulo"></p>
                    <p class="text-xs text-slate-600 mt-1 leading-relaxed" x-text="passos[passo]?.descricao"></p>
                </div>
            </div>

            {{-- Botões --}}
            <div class="flex items-center justify-between mt-4 pt-3 border-t border-slate-100">
                <button @click="fechar()" class="text-xs text-slate-400 hover:text-slate-600">Pular tutorial</button>
                <button @click="proximo()"
                        class="px-4 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition">
                    <span x-text="passo < passos.length - 1 ? 'Próximo →' : 'Entendi!'"></span>
                </button>
            </div>
        </div>
    </div>

    <script>
    function tutorialPrimeiroAcesso() {
        return {
            ativo: !localStorage.getItem('infovisa_tutorial_v2'),
            passo: 0,
            posicao: 'top: 50%; left: 50%; transform: translate(-50%, -50%)',
            passos: [
                {
                    titulo: 'Bem-vindo ao InfoVISA!',
                    descricao: 'Vamos te mostrar rapidamente onde encontrar ajuda e suporte no sistema.',
                    icone: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5 text-blue-600"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                    cor: 'bg-blue-100',
                    alvo: null
                },
                {
                    titulo: 'Manual e Documentos de Ajuda',
                    descricao: 'Clique neste botão (?) para acessar o manual completo do sistema e documentos instrutivos.',
                    icone: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5 text-indigo-600"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>',
                    cor: 'bg-indigo-100',
                    alvo: 'btn-ajuda'
                },
                {
                    titulo: 'Assistente com IA',
                    descricao: 'Use o chat no canto inferior direito para tirar dúvidas sobre processos, documentos e como usar o sistema.',
                    icone: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5 text-purple-600"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>',
                    cor: 'bg-purple-100',
                    alvo: 'btn-ia-chat'
                },
                {
                    titulo: 'Seus estabelecimentos e processos',
                    descricao: 'Aqui no dashboard você acompanha tudo: pendências, documentos, prazos e o status dos seus processos.',
                    icone: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5 text-green-600"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                    cor: 'bg-green-100',
                    alvo: null
                }
            ],
            proximo() {
                if (this.passo < this.passos.length - 1) {
                    this.passo++;
                    this.posicionarCard();
                } else {
                    this.fechar();
                }
            },
            fechar() {
                this.ativo = false;
                localStorage.setItem('infovisa_tutorial_v2', '1');
                // Remove highlights
                document.querySelectorAll('.tutorial-highlight').forEach(el => el.classList.remove('tutorial-highlight'));
            },
            posicionarCard() {
                const alvo = this.passos[this.passo]?.alvo;
                // Remove highlight anterior
                document.querySelectorAll('.tutorial-highlight').forEach(el => {
                    el.classList.remove('tutorial-highlight');
                    el.style.removeProperty('z-index');
                    el.style.removeProperty('position');
                });

                if (!alvo) {
                    this.posicao = 'top: 50%; left: 50%; transform: translate(-50%, -50%)';
                    return;
                }

                const el = document.getElementById(alvo);
                if (!el) {
                    this.posicao = 'top: 50%; left: 50%; transform: translate(-50%, -50%)';
                    return;
                }

                // Highlight no elemento alvo
                el.classList.add('tutorial-highlight');
                el.style.zIndex = '9999';
                el.style.position = 'relative';

                const rect = el.getBoundingClientRect();
                const cardW = 320;
                let top = rect.bottom + 12;
                let left = rect.left + rect.width / 2 - cardW / 2;

                // Ajustar se sair da tela
                if (left < 16) left = 16;
                if (left + cardW > window.innerWidth - 16) left = window.innerWidth - cardW - 16;
                if (top + 200 > window.innerHeight) top = rect.top - 200;

                this.posicao = 'top: ' + top + 'px; left: ' + left + 'px; transform: none';
            }
        };
    }
    </script>
    <style>
        .tutorial-highlight {
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.5), 0 0 20px rgba(59, 130, 246, 0.3);
            border-radius: 12px;
        }
    </style>

    {{-- Avisos do Sistema --}}
    @if(isset($avisos_sistema) && $avisos_sistema->count() > 0)
    <div class="space-y-2">
        @foreach($avisos_sistema as $aviso)
        <div class="flex items-start gap-3 p-3 rounded-xl border {{ $aviso->tipo_color }}">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $aviso->tipo_icone }}"/>
            </svg>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium">{{ $aviso->titulo }}</p>
                <p class="text-xs mt-0.5 opacity-80">{{ $aviso->mensagem }}</p>
                @if($aviso->link)
                <a href="{{ $aviso->link }}" target="_blank" class="inline-flex items-center gap-1 text-xs mt-1 font-semibold underline hover:opacity-80">
                    Acessar
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Cards de resumo clicáveis --}}
    <div id="tour-stats-cards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
        <a href="{{ route('company.estabelecimentos.index') }}" id="tour-meus-estabelecimentos" class="group flex items-center gap-3 bg-white rounded-xl border border-slate-200/80 shadow-sm px-3 py-2.5 hover:shadow hover:border-blue-200 transition-all">
            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-600 group-hover:text-white transition">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div class="min-w-0 flex-1 leading-tight">
                <div class="flex items-baseline gap-1.5">
                    <span class="text-lg font-bold text-slate-900 leading-none">{{ $estatisticasEstabelecimentos['total'] }}</span>
                    <span class="text-xs font-semibold text-slate-600">Estabelecimentos</span>
                </div>
                <p class="text-[11px] text-slate-400 truncate mt-0.5">
                    {{ $estatisticasEstabelecimentos['aprovados'] }} aprovados
                    @if($estatisticasEstabelecimentos['pendentes'] > 0)
                    · {{ $estatisticasEstabelecimentos['pendentes'] }} pendentes
                    @endif
                </p>
            </div>
        </a>

        <a href="{{ route('company.processos.index') }}" id="tour-meus-processos" class="group flex items-center gap-3 bg-white rounded-xl border border-slate-200/80 shadow-sm px-3 py-2.5 hover:shadow hover:border-purple-200 transition-all">
            <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0 group-hover:bg-purple-600 group-hover:text-white transition">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="min-w-0 flex-1 leading-tight">
                <div class="flex items-baseline gap-1.5">
                    <span class="text-lg font-bold text-slate-900 leading-none">{{ $estatisticasProcessos['total'] }}</span>
                    <span class="text-xs font-semibold text-slate-600">Processos</span>
                </div>
                <p class="text-[11px] text-slate-400 truncate mt-0.5">
                    @if($estatisticasProcessos['abertos'] > 0) {{ $estatisticasProcessos['abertos'] }} abertos @endif
                    @if($estatisticasProcessos['em_andamento'] > 0) · {{ $estatisticasProcessos['em_andamento'] }} em andamento @endif
                    @if($estatisticasProcessos['concluidos'] > 0) · {{ $estatisticasProcessos['concluidos'] }} concluídos @endif
                    @if($estatisticasProcessos['abertos'] == 0 && $estatisticasProcessos['em_andamento'] == 0 && $estatisticasProcessos['concluidos'] == 0) Nenhum ativo @endif
                </p>
            </div>
        </a>

        <a href="{{ route('company.alertas.index') }}" id="tour-alertas" class="group flex items-center gap-3 bg-white rounded-xl border shadow-sm px-3 py-2.5 hover:shadow transition-all {{ $totalAlertas > 0 ? 'border-amber-200 hover:border-amber-300' : 'border-slate-200/80 hover:border-green-200' }}">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 transition {{ $totalAlertas > 0 ? 'bg-amber-500 text-white' : 'bg-green-50 text-green-600' }}">
                @if($totalAlertas > 0)
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                @else
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                @endif
            </div>
            <div class="min-w-0 flex-1 leading-tight">
                <div class="flex items-baseline gap-1.5">
                    <span class="text-lg font-bold leading-none {{ $totalAlertas > 0 ? 'text-amber-600' : 'text-green-600' }}">{{ $totalAlertas }}</span>
                    <span class="text-xs font-semibold text-slate-600">Pendências</span>
                </div>
                <p class="text-[11px] {{ $totalAlertas > 0 ? 'text-amber-500' : 'text-green-500' }} truncate mt-0.5">
                    {{ $totalAlertas > 0 ? 'Requer sua atenção' : 'Tudo em dia!' }}
                </p>
            </div>
        </a>

        <a href="{{ route('company.estabelecimentos.create') }}" id="tour-novo-cadastro" class="group flex items-center gap-3 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow-sm shadow-emerald-600/20 px-3 py-2.5 hover:shadow-md hover:from-emerald-600 hover:to-emerald-700 transition-all">
            <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
                <svg class="w-[18px] h-[18px] text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/></svg>
            </div>
            <div class="min-w-0 flex-1 leading-tight">
                <p class="text-sm font-semibold text-white">Novo Cadastro</p>
                <p class="text-[11px] text-emerald-100 truncate">Cadastrar estabelecimento</p>
            </div>
            <svg class="w-4 h-4 text-white/70 group-hover:translate-x-0.5 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>

    {{-- Pendências (docs com prazo + docs rejeitados + novos docs + alertas) --}}
    @if($temAlertas)
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-2">
            <span class="text-sm">⚡</span>
            <h2 class="text-sm font-bold text-slate-900">O que precisa da sua atenção</h2>
            <span class="text-[10px] px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full font-bold">{{ $totalAlertas }}</span>
        </div>
        <div class="divide-y divide-slate-50">
            {{-- Documentos com Prazo (urgente) --}}
            @foreach($documentosComPrazo as $documento)
            <a href="{{ route('company.processos.show', $documento->processo_id) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-red-50/50 transition {{ $documento->vencido ? 'bg-red-50/40' : '' }}">
                <div class="w-8 h-8 rounded-lg {{ $documento->vencido ? 'bg-red-100' : 'bg-amber-100' }} flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 {{ $documento->vencido ? 'text-red-600' : 'text-amber-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 truncate">{{ $documento->tipoDocumento->nome ?? 'Notificação' }}</p>
                    <p class="text-[11px] {{ $documento->vencido ? 'text-red-500 font-medium' : 'text-amber-600' }}">
                        @if($documento->vencido) ⚠️ Prazo vencido há {{ abs($documento->dias_faltando) }} dia(s)
                        @elseif($documento->dias_faltando == 0) ⚠️ Vence hoje!
                        @elseif($documento->dias_faltando == 1) Vence amanhã
                        @else {{ $documento->dias_faltando }} dias para responder
                        @endif
                        · {{ $documento->processo->estabelecimento->nome_fantasia ?? $documento->processo->estabelecimento->razao_social ?? '' }}
                    </p>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full flex-shrink-0 {{ $documento->vencido ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">Responder</span>
            </a>
            @endforeach

            {{-- Docs Rejeitados --}}
            @foreach($documentosRejeitados->take(5) as $documento)
            <a href="{{ route('company.processos.show', $documento->processo_id) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-red-50/50 transition">
                <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 truncate">{{ $documento->tipoDocumentoObrigatorio->nome ?? $documento->nome_original ?? 'Documento' }}</p>
                    <p class="text-[11px] text-red-500 truncate">{{ Str::limit($documento->motivo_rejeicao ?? 'Corrigir e reenviar', 50) }}</p>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700 flex-shrink-0">Corrigir</span>
            </a>
            @endforeach

            {{-- Novos Documentos --}}
            @foreach($documentosPendentesVisualizacao->take(5) as $documento)
            <a href="{{ route('company.processos.documento-digital.visualizar', [$documento->processo_id, $documento->id]) }}" target="_blank" class="flex items-center gap-3 px-4 py-3 hover:bg-blue-50/50 transition">
                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 truncate">{{ $documento->tipoDocumento->nome ?? 'Documento' }}</p>
                    <p class="text-[11px] text-slate-400">Nº {{ $documento->numero_documento }} · Novo documento da vigilância</p>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 flex-shrink-0">Visualizar</span>
            </a>
            @endforeach

            {{-- Alertas --}}
            @foreach($alertasPendentes->take(5) as $alerta)
            <a href="{{ route('company.alertas.index') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-amber-50/50 transition {{ $alerta->isVencido() ? 'bg-red-50/30' : '' }}">
                <div class="w-8 h-8 rounded-lg {{ $alerta->isVencido() ? 'bg-red-100' : 'bg-amber-100' }} flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 {{ $alerta->isVencido() ? 'text-red-500' : 'text-amber-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 truncate">{{ $alerta->descricao }}</p>
                    <p class="text-[11px] {{ $alerta->isVencido() ? 'text-red-500' : 'text-slate-400' }}">Prazo: {{ $alerta->data_alerta->format('d/m/Y') }}</p>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $alerta->isVencido() ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }} flex-shrink-0">
                    {{ $alerta->isVencido() ? 'Vencido' : $alerta->data_alerta->diffInDays(now()) . 'd' }}
                </span>
            </a>
            @endforeach
        </div>
        @if($totalAlertas > 10)
        <div class="px-4 py-2 border-t border-slate-100 text-center">
            <a href="{{ route('company.alertas.index') }}" class="text-xs text-blue-600 font-medium hover:underline">Ver todas as pendências →</a>
        </div>
        @endif
    </div>
    @else
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 text-center">
        <div class="w-12 h-12 bg-green-50 rounded-2xl flex items-center justify-center mx-auto mb-2">
            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <p class="text-sm font-medium text-slate-700">Nenhuma pendência</p>
        <p class="text-xs text-slate-400 mt-0.5">Tudo em dia! Bom trabalho.</p>
    </div>
    @endif

    {{-- Estabelecimentos e Processos lado a lado --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Meus Estabelecimentos --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <h3 class="text-sm font-semibold text-slate-800">Meus Estabelecimentos</h3>
                </div>
                <a href="{{ route('company.estabelecimentos.index') }}" class="text-[11px] text-slate-400 hover:text-blue-600 transition">ver todos →</a>
            </div>
            <div class="divide-y divide-slate-50 max-h-[320px] overflow-y-auto">
                @forelse($ultimosEstabelecimentos as $estabelecimento)
                <a href="{{ route('company.estabelecimentos.show', $estabelecimento->id) }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-blue-50/50 transition">
                    <div class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                        <span class="text-blue-600 font-bold text-[10px]">{{ strtoupper(substr($estabelecimento->nome_fantasia ?: $estabelecimento->razao_social ?: 'E', 0, 1)) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $estabelecimento->nome_fantasia ?: $estabelecimento->razao_social ?: $estabelecimento->nome_completo ?: 'Sem Nome' }}</p>
                        <p class="text-[11px] text-slate-400 truncate">{{ $estabelecimento->documento_formatado }}@if($estabelecimento->municipio && is_object($estabelecimento->municipio)) · {{ $estabelecimento->municipio->nome }}@endif</p>
                    </div>
                    <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-full flex-shrink-0
                        @if($estabelecimento->status === 'aprovado') bg-green-100 text-green-700
                        @elseif($estabelecimento->status === 'pendente') bg-amber-100 text-amber-700
                        @else bg-red-100 text-red-700 @endif">
                        @if($estabelecimento->status === 'aprovado') Aprovado
                        @elseif($estabelecimento->status === 'pendente') Pendente
                        @else Rejeitado @endif
                    </span>
                </a>
                @empty
                <div class="p-8 text-center">
                    <p class="text-xs text-slate-400">Nenhum estabelecimento</p>
                    <a href="{{ route('company.estabelecimentos.create') }}" class="text-xs text-blue-600 font-medium hover:underline mt-1 inline-block">Cadastrar agora →</a>
                </div>
                @endforelse
            </div>
            @if($estatisticasEstabelecimentos['rejeitados'] > 0)
            <div class="px-4 py-2 border-t border-slate-100 bg-red-50/50">
                <a href="{{ route('company.estabelecimentos.index') }}" class="flex items-center gap-1.5 text-[11px] text-red-600 font-medium hover:text-red-700">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    {{ $estatisticasEstabelecimentos['rejeitados'] }} rejeitado(s) - verificar
                </a>
            </div>
            @endif
        </div>

        {{-- Meus Processos --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <h3 class="text-sm font-semibold text-slate-800">Meus Processos</h3>
                </div>
                <a href="{{ route('company.processos.index') }}" class="text-[11px] text-slate-400 hover:text-purple-600 transition">ver todos →</a>
            </div>
            <div class="divide-y divide-slate-50 max-h-[320px] overflow-y-auto">
                @forelse($ultimosProcessos as $processo)
                <a href="{{ route('company.processos.show', $processo->id) }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-purple-50/50 transition">
                    <div class="w-7 h-7 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5">
                            <p class="text-sm font-medium text-slate-800">{{ $processo->numero_processo ?? 'Processo #'.$processo->id }}</p>
                            @if($processo->tipoProcesso)
                            <span class="text-[9px] px-1 py-0.5 rounded bg-slate-100 text-slate-400">{{ $processo->tipoProcesso->nome }}</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-slate-400 truncate">{{ $processo->estabelecimento->nome_fantasia ?? $processo->estabelecimento->razao_social ?? '-' }}</p>
                    </div>
                    <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-full flex-shrink-0
                        @if($processo->status === 'em_andamento' || $processo->status === 'aberto') bg-blue-100 text-blue-700
                        @elseif($processo->status === 'concluido') bg-green-100 text-green-700
                        @elseif($processo->status === 'arquivado') bg-slate-100 text-slate-600
                        @else bg-yellow-100 text-yellow-700 @endif">
                        @if($processo->status === 'em_andamento') Em andamento
                        @elseif($processo->status === 'aberto') Aberto
                        @elseif($processo->status === 'concluido') Concluído
                        @elseif($processo->status === 'arquivado') Arquivado
                        @else {{ ucfirst(str_replace('_', ' ', $processo->status)) }} @endif
                    </span>
                </a>
                @empty
                <div class="p-8 text-center">
                    <p class="text-xs text-slate-400">Nenhum processo</p>
                    <p class="text-[11px] text-slate-300 mt-0.5">Processos aparecem aqui quando iniciados</p>
                </div>
                @endforelse
            </div>
            @if($estatisticasProcessos['total'] > 0)
            <div class="px-4 py-2 border-t border-slate-100 bg-slate-50/50 flex items-center gap-3 text-[10px] flex-wrap">
                @if($estatisticasProcessos['abertos'] > 0)
                <span class="flex items-center gap-1 text-blue-600"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> {{ $estatisticasProcessos['abertos'] }} abertos</span>
                @endif
                @if($estatisticasProcessos['em_andamento'] > 0)
                <span class="flex items-center gap-1 text-blue-600"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> {{ $estatisticasProcessos['em_andamento'] }} em andamento</span>
                @endif
                @if($estatisticasProcessos['concluidos'] > 0)
                <span class="flex items-center gap-1 text-green-600"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> {{ $estatisticasProcessos['concluidos'] }} concluídos</span>
                @endif
                @if($estatisticasProcessos['arquivados'] > 0)
                <span class="flex items-center gap-1 text-slate-500"><span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> {{ $estatisticasProcessos['arquivados'] }} arquivados</span>
                @endif
            </div>
            @endif
        </div>
    </div>

</div>
<x-assistente-ia-externo-chat />
@endsection
