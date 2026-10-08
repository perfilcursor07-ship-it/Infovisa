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
@endphp

<div class="max-w-8xl mx-auto space-y-5"
     x-data="{
        q: @js($valoresIniciais),
        aceite: {{ old('aceite') ? 'true' : 'false' }},
        justificar: {{ old('justificativa') ? 'true' : 'false' }},
        verDeclaracoes: false,
        verParametros: false,
        enviando: false,
        rotulos: @js($modalidades),
        num(v) { return parseInt(v) || 0 },
        get total() { return Object.values(this.q).reduce((s, m) => s + Object.values(m).reduce((a, v) => a + this.num(v), 0), 0) },
        get itens() {
            const lista = [];
            Object.entries(this.q).forEach(([m, tipos]) => Object.entries(tipos).forEach(([t, v]) => { if (this.num(v) > 0) lista.push({ m: this.rotulos[m], t, v: this.num(v) }) }));
            return lista;
        },
        ajustar(m, t, d) { this.q[m][t] = Math.max(0, Math.min({{ $Req::QUANTIDADE_MAXIMA }}, this.num(this.q[m][t]) + d)) },
        get podeEnviar() { return this.total > 0 && this.aceite && !this.enviando }
     }">

    {{-- Cabeçalho --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('company.processos.show', $processo->id) }}" title="Voltar para o processo"
           class="w-9 h-9 flex-shrink-0 inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="min-w-0">
            <h1 class="text-lg font-semibold text-slate-900 leading-tight">Nova requisição de receituário</h1>
            <p class="text-xs text-slate-500">Processo {{ $processo->numero_processo }} · informe quantos blocos precisa e envie para a Vigilância Sanitária</p>
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
                        <h2 class="text-sm font-semibold text-slate-900">Quantos blocos você precisa?</h2>
                        <p class="text-xs text-slate-500">Preencha só os tipos que deseja. A quantidade liberada pode ser menor que a solicitada.</p>
                    </div>
                    <button type="button" @click="verParametros = !verParametros" class="text-xs font-medium text-blue-700 hover:underline">
                        <span x-text="verParametros ? 'Ocultar limites' : 'Quanto posso pedir?'"></span>
                    </button>
                </header>

                {{-- Limites de entrega (orientação da DVISA) --}}
                <div x-show="verParametros" x-cloak class="mx-5 mb-3 rounded-lg bg-blue-50/70 border border-blue-100 p-3">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-left text-slate-500">
                                <th class="pb-1 pr-3 font-semibold">Tipo</th>
                                <th class="pb-1 pr-3 font-semibold">Médico especialista</th>
                                <th class="pb-1 font-semibold">Outras especialidades</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-700">
                            @foreach($Req::PARAMETROS_ENTREGA as $p)
                            <tr class="border-t border-blue-100">
                                <td class="py-1 pr-3 font-bold">{{ $p['tipo'] }}</td>
                                <td class="py-1 pr-3">{{ $p['especialista'] }}</td>
                                <td class="py-1">{{ $p['outras'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="mt-2 text-[11px] text-slate-600">
                        @foreach($Req::PARAMETROS_OUTROS as $quem => $limite)
                            <span class="font-semibold">{{ $quem }}:</span> {{ $limite }}@if(!$loop->last) · @endif
                        @endforeach
                    </p>
                </div>

                {{-- Tabela de quantidades --}}
                <div class="px-5 pb-4 overflow-x-auto">
                    <table class="w-full min-w-[560px]">
                        <thead>
                            <tr>
                                <th class="w-28"></th>
                                @foreach($tipos as $tipo)
                                <th class="pb-2 text-center text-sm font-bold text-slate-700">{{ $tipo }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($modalidades as $chave => $rotulo)
                            <tr class="{{ $loop->first ? '' : 'border-t border-slate-100' }}">
                                <th class="py-2.5 pr-3 text-left text-sm font-medium text-slate-700">{{ $rotulo }}</th>
                                @foreach($tipos as $tipo)
                                <td class="py-2.5 px-1">
                                    <div class="mx-auto w-24 flex items-center rounded-lg border bg-white transition focus-within:ring-2 focus-within:ring-blue-500"
                                         :class="num(q['{{ $chave }}']['{{ $tipo }}']) > 0 ? 'border-blue-400 bg-blue-50' : 'border-slate-200'">
                                        <button type="button" @click="ajustar('{{ $chave }}', '{{ $tipo }}', -1)" tabindex="-1"
                                                class="w-7 h-9 text-slate-400 hover:text-slate-700" aria-label="Diminuir {{ $rotulo }} {{ $tipo }}">−</button>
                                        <input type="number" min="0" max="{{ $Req::QUANTIDADE_MAXIMA }}" inputmode="numeric"
                                               name="quantidades[{{ $chave }}][{{ $tipo }}]" x-model.number="q['{{ $chave }}']['{{ $tipo }}']"
                                               aria-label="{{ $rotulo }} {{ $tipo }}"
                                               class="w-full min-w-0 h-9 border-0 bg-transparent p-0 text-center text-sm font-semibold tabular-nums focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                        <button type="button" @click="ajustar('{{ $chave }}', '{{ $tipo }}', 1)" tabindex="-1"
                                                class="w-7 h-9 text-slate-400 hover:text-slate-700" aria-label="Aumentar {{ $rotulo }} {{ $tipo }}">+</button>
                                    </div>
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Justificativa (opcional) --}}
                <div class="px-5 py-3 border-t border-slate-100">
                    <button type="button" x-show="!justificar" @click="justificar = true; $nextTick(() => $refs.justificativa.focus())"
                            class="text-xs font-medium text-blue-700 hover:underline">+ Adicionar justificativa (para quantidade acima do limite)</button>
                    <div x-show="justificar" x-cloak>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Justificativa</label>
                        <textarea name="justificativa" x-ref="justificativa" rows="2" maxlength="2000"
                                  class="w-full text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Por que precisa dessa quantidade?">{{ old('justificativa') }}</textarea>
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
                    <p class="text-sm text-slate-500"><span class="text-xl font-bold text-slate-900 tabular-nums" x-text="total"></span> bloco(s)</p>
                </div>

                <div class="mt-2 min-h-[2rem] flex flex-wrap gap-1.5">
                    <template x-for="item in itens" :key="item.m + item.t">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-50 text-xs text-blue-800">
                            <span class="text-blue-500" x-text="item.m"></span>
                            <span class="font-bold" x-text="item.t"></span>
                            <span class="tabular-nums" x-text="'× ' + item.v"></span>
                        </span>
                    </template>
                    <p x-show="total === 0" class="text-xs text-slate-400">Nenhuma quantidade informada.</p>
                </div>

                <button type="submit" :disabled="!podeEnviar"
                        class="mt-4 w-full inline-flex items-center justify-center gap-2 h-10 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm disabled:opacity-40 disabled:cursor-not-allowed transition">
                    <span x-text="enviando ? 'Enviando...' : 'Enviar requisição'"></span>
                </button>
                <p class="mt-2 text-center text-[11px]" :class="podeEnviar ? 'text-emerald-700' : 'text-slate-500'"
                   x-text="total === 0 ? 'Informe ao menos uma quantidade.' : (!aceite ? 'Marque o aceite das declarações.' : 'Pronto para enviar.')"></p>

                @if($ultimaRequisicao)
                <p class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500">
                    Último pedido:
                    <a href="{{ route('company.processos.receituario-requisicoes.show', [$processo->id, $ultimaRequisicao->id]) }}" class="font-medium text-blue-700 hover:underline">nº {{ $ultimaRequisicao->numero }}</a>
                    em {{ $ultimaRequisicao->created_at->format('d/m/Y') }} ({{ mb_strtolower($ultimaRequisicao->situacao['label']) }})
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
