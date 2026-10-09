@extends('layouts.company')

@section('title', 'Nova requisição de receituário')
@section('page-title', 'Nova requisição de receituário')

@section('content')
@php
    $Req = \App\Models\ReceituarioRequisicao::class;
    $tipos = $Req::TIPOS_NOTIFICACAO;
    $modalidades = $Req::MODALIDADES;
    $valoresIniciais = [];
    foreach (array_keys($modalidades) as $m) {
        foreach ($tipos as $t) {
            $valoresIniciais[$m][$t] = (int) old("quantidades.$m.$t", 0);
        }
    }
    $usuarioNome = auth('externo')->user()->nome;
    $passo = 10; // − / + de 10 em 10 (dá para digitar qualquer quantidade)
@endphp

<div class="max-w-8xl mx-auto space-y-5"
     x-data="{
        q: @js($valoresIniciais),
        limites: @js($limites),
        aceite: {{ old('aceite') ? 'true' : 'false' }},
        justificar: {{ old('justificativa') || $errors->has('justificativa') ? 'true' : 'false' }},
        textoJustificativa: @js((string) old('justificativa', '')),
        verDeclaracoes: false,
        verComo: false,
        enviando: false,
        rotulos: @js($modalidades),
        num(v) { return Math.max(0, parseInt(v) || 0) },
        totalTipo(t) { return Object.keys(this.q).reduce((s, m) => s + this.num(this.q[m][t]), 0) },
        get total() { return Object.keys(this.q).reduce((s, m) => s + Object.values(this.q[m]).reduce((a, v) => a + this.num(v), 0), 0) },
        get itens() {
            const lista = [];
            Object.entries(this.q).forEach(([m, tipos]) => Object.entries(tipos).forEach(([t, v]) => { if (this.num(v) > 0) lista.push({ m: this.rotulos[m], t, v: this.num(v) }) }));
            return lista;
        },
        // Tipos acima do limite de referência (ou que sempre pedem justificativa)
        get acima() { return Object.keys(this.limites).filter(t => this.totalTipo(t) > 0 && (this.limites[t].justificar || this.totalTipo(t) > this.limites[t].limite)) },
        get faltaJustificativa() { return this.acima.length > 0 && !this.textoJustificativa.trim() },
        ajustar(m, t, d) { this.q[m][t] = Math.max(0, Math.min({{ $Req::QUANTIDADE_MAXIMA }}, this.num(this.q[m][t]) + d)) },
        fmt(n) { return Number(n).toLocaleString('pt-BR') },
        get podeEnviar() { return this.total > 0 && this.aceite && !this.faltaJustificativa && !this.enviando }
     }"
     x-effect="if (acima.length) justificar = true">

    {{-- Cabeçalho --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('company.processos.show', $processo->id) }}" title="Voltar para o processo"
           class="w-9 h-9 flex-shrink-0 inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="min-w-0">
            <h1 class="text-lg font-semibold text-slate-900 leading-tight">Nova requisição de numeração</h1>
            <p class="text-xs text-slate-500">Processo {{ $processo->numero_processo }} · informe quantas numerações de Notificação de Receita precisa e envie para a Vigilância Sanitária</p>
        </div>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 px-3.5 py-2.5 rounded-lg text-sm text-red-700">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('company.processos.receituario-requisicoes.store', $processo->id) }}" @submit="enviando = true"
          class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_340px] gap-5 items-start">
        @csrf

        {{-- Coluna principal --}}
        <div class="space-y-5 min-w-0">
            {{-- Quantidades --}}
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <header class="px-5 pt-4 pb-3 flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Quantas numerações você precisa?</h2>
                        <p class="text-xs text-slate-500">Cada numeração é uma Notificação de Receita. Preencha só os tipos que deseja — a quantidade liberada pode ser menor que a solicitada.</p>
                    </div>
                    <button type="button" @click="verComo = !verComo" class="text-xs font-medium text-blue-700 hover:underline">
                        <span x-text="verComo ? 'Ocultar' : 'Como funciona a física e a eletrônica?'"></span>
                    </button>
                </header>

                {{-- Como funciona (SNCR · RDC 1.000/2025) --}}
                <div x-show="verComo" x-cloak class="mx-5 mb-3 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 text-xs text-slate-600 leading-relaxed">
                        <p class="font-semibold text-slate-800 mb-1">🖨️ Física</p>
                        A Vigilância libera os números no SNCR. Com eles, você manda imprimir os talonários numa gráfica de sua escolha, no
                        <strong>modelo oficial da Anvisa</strong>. A quantidade de folhas por bloco é livre e não precisa voltar à Vigilância para carimbar.
                    </div>
                    <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 text-xs text-slate-600 leading-relaxed">
                        <p class="font-semibold text-slate-800 mb-1">💻 Eletrônica</p>
                        É uma numeração <strong>diferente da física</strong>, vinculada só a você no SNCR. Ela fica como saldo e é usada pelo seu
                        serviço de prescrição eletrônica integrado ao SNCR ao emitir a notificação.
                    </div>
                </div>

                {{-- Tabela de quantidades: um tipo por linha --}}
                <div class="px-5 pb-4 overflow-x-auto">
                    <table class="w-full min-w-[480px]">
                        <thead>
                            <tr class="text-xs text-slate-500">
                                <th class="pb-2 pr-3 text-left font-semibold">Tipo de notificação</th>
                                @foreach($modalidades as $chave => $rotulo)
                                <th class="pb-2 px-1 text-center font-semibold">
                                    <span class="block text-sm text-slate-700">{{ $rotulo }}</span>
                                    <span class="block text-[10px] font-normal text-slate-400">{{ $Req::DESCRICAO_MODALIDADE[$chave] }}</span>
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tipos as $tipo)
                            <tr class="border-t border-slate-100">
                                <th class="py-2.5 pr-3 text-left font-normal">
                                    <span class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center min-w-[2.25rem] h-8 px-1.5 rounded-md bg-slate-800 text-white text-xs font-bold flex-shrink-0">{{ $tipo }}</span>
                                        <span class="min-w-0">
                                            <span class="block text-xs font-semibold text-slate-700 leading-tight">{{ $Req::NOMES_TIPO[$tipo] }}</span>
                                            @if($limites[$tipo]['justificar'] && !$limites[$tipo]['limite'])
                                                <span class="block text-[11px] text-amber-700">mediante justificativa</span>
                                            @else
                                                <span class="block text-[11px]" :class="acima.includes('{{ $tipo }}') ? 'text-amber-700 font-semibold' : 'text-slate-400'"
                                                      title="{{ $limites[$tipo]['motivo'] }} · física + eletrônica">
                                                    limite: até {{ number_format($limites[$tipo]['limite'], 0, ',', '.') }}{{ $limites[$tipo]['justificar'] ? ' + justificativa' : '' }}
                                                </span>
                                            @endif
                                        </span>
                                    </span>
                                </th>
                                @foreach($modalidades as $chave => $rotulo)
                                <td class="py-2.5 px-1">
                                    <div class="mx-auto w-32 flex items-center rounded-lg border bg-white transition focus-within:ring-2 focus-within:ring-blue-500"
                                         :class="num(q['{{ $chave }}']['{{ $tipo }}']) > 0 ? (acima.includes('{{ $tipo }}') ? 'border-amber-400 bg-amber-50' : 'border-blue-400 bg-blue-50') : 'border-slate-200'">
                                        <button type="button" @click="ajustar('{{ $chave }}', '{{ $tipo }}', -{{ $passo }})" tabindex="-1"
                                                class="w-8 h-9 text-slate-400 hover:text-slate-700" aria-label="Diminuir {{ $rotulo }} {{ $tipo }}">−</button>
                                        <input type="number" min="0" max="{{ $Req::QUANTIDADE_MAXIMA }}" inputmode="numeric"
                                               name="quantidades[{{ $chave }}][{{ $tipo }}]" x-model.number="q['{{ $chave }}']['{{ $tipo }}']"
                                               aria-label="Numerações {{ $rotulo }} {{ $tipo }}"
                                               class="w-full min-w-0 h-9 border-0 bg-transparent p-0 text-center text-sm font-semibold tabular-nums focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                        <button type="button" @click="ajustar('{{ $chave }}', '{{ $tipo }}', {{ $passo }})" tabindex="-1"
                                                class="w-8 h-9 text-slate-400 hover:text-slate-700" aria-label="Aumentar {{ $rotulo }} {{ $tipo }}">+</button>
                                    </div>
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="mt-2 text-[11px] text-slate-400">Limites da Vigilância Sanitária para {{ $requisitante['especialidade'] ? mb_strtolower($requisitante['especialidade']) : 'a sua especialidade' }}, somando física e eletrônica. Acima disso, informe a justificativa.</p>
                </div>

                {{-- Justificativa (obrigatória acima do limite) --}}
                <div class="px-5 py-3 border-t border-slate-100">
                    <button type="button" x-show="!justificar" @click="justificar = true; $nextTick(() => $refs.justificativa.focus())"
                            class="text-xs font-medium text-blue-700 hover:underline">+ Adicionar justificativa</button>
                    <div x-show="justificar" x-cloak>
                        <label class="block text-xs font-medium mb-1" :class="acima.length ? 'text-amber-800' : 'text-slate-600'">
                            Justificativa<span x-show="acima.length"> * — obrigatória para <span class="font-bold" x-text="acima.join(', ')"></span> (acima do limite de referência)</span>
                        </label>
                        <textarea name="justificativa" x-ref="justificativa" x-model="textoJustificativa" rows="2" maxlength="2000"
                                  class="w-full text-sm border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  :class="faltaJustificativa ? 'border-amber-400' : 'border-slate-300'"
                                  placeholder="Por que precisa dessa quantidade?"></textarea>
                    </div>
                </div>
            </section>

            {{-- Aceite único: declarações + assinatura --}}
            <section class="bg-white rounded-xl border shadow-sm transition" :class="aceite ? 'border-emerald-300' : 'border-slate-200'">
                <label class="flex items-start gap-3 px-5 py-4 cursor-pointer">
                    <input type="checkbox" name="aceite" value="1" x-model="aceite"
                           class="mt-0.5 h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 flex-shrink-0">
                    <span class="text-sm text-slate-700">
                        Li e concordo com as <button type="button" @click.prevent="verDeclaracoes = !verDeclaracoes" class="font-semibold text-blue-700 hover:underline">declarações da requisição</button>
                        e assino eletronicamente como <span class="font-semibold text-slate-900">{{ $usuarioNome }}</span>.
                    </span>
                </label>
                <ul x-show="verDeclaracoes" x-cloak class="mx-5 mb-4 -mt-1 space-y-2 rounded-lg bg-slate-50 p-3.5 text-xs text-slate-600 leading-relaxed list-disc list-inside">
                    @foreach($Req::DECLARACOES as $texto)
                    <li>{{ $texto }}</li>
                    @endforeach
                </ul>
            </section>
        </div>

        {{-- Resumo lateral --}}
        <aside class="lg:sticky lg:top-4 space-y-4">
            {{-- Requisitante --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Requisitante</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $requisitante['nome'] ?? '—' }}</p>
                <p class="text-xs text-slate-500 tabular-nums">
                    {{ $requisitante['documento'] ?? '' }}@if(!empty($requisitante['conselho'])) · {{ $requisitante['conselho'] }}@endif
                </p>
                @if(!empty($requisitante['especialidade']))
                <p class="text-xs text-slate-500">{{ $requisitante['especialidade'] }}</p>
                @endif
                <p class="mt-2 text-xs text-slate-500 leading-snug">
                    {{ $requisitante['endereco'] ?? '' }}{{ !empty($requisitante['municipio']) ? ' · ' . $requisitante['municipio'] : '' }}{{ !empty($requisitante['cep']) ? ' · ' . $requisitante['cep'] : '' }}
                </p>
                <a href="{{ route('company.receituarios.show', $processo->estabelecimento->receituario->id) }}"
                   class="mt-2 inline-block text-[11px] font-medium text-blue-700 hover:underline">Dados errados? Atualize o cadastro</a>
            </div>

            {{-- Resumo e envio --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                <div class="flex items-baseline justify-between">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Seu pedido</p>
                    <p class="text-sm text-slate-500"><span class="text-xl font-bold text-slate-900 tabular-nums" x-text="fmt(total)"></span> <span x-text="total === 1 ? 'numeração' : 'numerações'"></span></p>
                </div>

                <div class="mt-2 min-h-[2rem] flex flex-wrap gap-1.5">
                    <template x-for="item in itens" :key="item.m + item.t">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs"
                              :class="acima.includes(item.t) ? 'bg-amber-50 text-amber-800' : 'bg-blue-50 text-blue-800'">
                            <span class="opacity-70" x-text="item.m"></span>
                            <span class="font-bold" x-text="item.t"></span>
                            <span class="tabular-nums" x-text="'× ' + fmt(item.v)"></span>
                        </span>
                    </template>
                    <p x-show="total === 0" class="text-xs text-slate-400">Nenhuma quantidade informada.</p>
                </div>

                <button type="submit" :disabled="!podeEnviar"
                        class="mt-4 w-full inline-flex items-center justify-center gap-2 h-10 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm disabled:opacity-40 disabled:cursor-not-allowed transition">
                    <span x-text="enviando ? 'Enviando...' : 'Enviar requisição'"></span>
                </button>
                <p class="mt-2 text-center text-[11px]" :class="podeEnviar ? 'text-emerald-700' : 'text-slate-500'"
                   x-text="total === 0 ? 'Informe ao menos uma quantidade.' : (faltaJustificativa ? 'Informe a justificativa para ' + acima.join(', ') + '.' : (!aceite ? 'Marque o aceite das declarações.' : 'Pronto para enviar.'))"></p>

                @if($ultimaRequisicao)
                <p class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500">
                    Último pedido:
                    <a href="{{ route('company.processos.receituario-requisicoes.show', [$processo->id, $ultimaRequisicao->id]) }}" class="font-medium text-blue-700 hover:underline">nº {{ $ultimaRequisicao->numero }}</a>
                    em {{ $ultimaRequisicao->created_at->format('d/m/Y') }} · {{ $ultimaRequisicao->rotuloQuantidade() }} ({{ mb_strtolower($ultimaRequisicao->situacao['label']) }})
                </p>
                @endif
            </div>

            {{-- Envio de ofício pela empresa desativado por enquanto (reativar junto com o botão em company/processos/show)
            <p class="text-[11px] text-slate-500 px-1">
                Precisa enviar uma justificativa assinada ou ofício? Use <span class="font-medium">Enviar ofício/documento</span> no processo.
            </p>
            --}}
        </aside>
    </form>
</div>
@endsection
