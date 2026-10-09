@extends('layouts.admin')

@section('title', 'Requisição nº ' . $requisicao->numero)
@section('page-title', 'Requisição de Receituário')

@section('content')
@php
    $Req = \App\Models\ReceituarioRequisicao::class;
    $situacao = $requisicao->situacao;
    $r = $requisicao->requisitante ?? [];
    $aguardando = $requisicao->aguardandoAnalise();
    $voltarUrl = route('admin.estabelecimentos.processos.show', [$estabelecimento->id, $processo->id]) . '#requisicoes';
    $linhas = $requisicao->linhasPedidas();
    $documentos = $requisicao->documentosLiberados();
    $unidade = $requisicao->emBlocos() ? 'blocos' : 'numerações';
    $limitesProfissional = $Req::limitesPara($r['especialidade'] ?? null);
    $pedidoPorTipo = collect($Req::TIPOS_NOTIFICACAO)->mapWithKeys(fn ($t) => [$t => collect($linhas)->where('tipo', $t)->sum('pedido')]);
    $acimaDoLimite = $requisicao->emBlocos() ? [] : collect($pedidoPorTipo)
        ->filter(fn ($total, $t) => $total > 0 && ($limitesProfissional[$t]['justificar'] || $total > $limitesProfissional[$t]['limite']))
        ->keys()->all();
    $etapas = [
        ['rotulo' => 'Enviada pela empresa', 'feito' => true, 'quando' => $requisicao->created_at->format('d/m/Y H:i')],
        ['rotulo' => 'Análise da Vigilância', 'feito' => !$aguardando, 'atual' => $aguardando],
        ['rotulo' => match ($requisicao->status) { 'indeferida' => 'Indeferida', 'cancelada' => 'Cancelada', default => 'Numeração liberada' },
         'feito' => in_array($requisicao->status, ['liberada', 'indeferida', 'cancelada'], true),
         'quando' => $requisicao->analisado_em?->format('d/m/Y H:i') ?? $requisicao->cancelado_em?->format('d/m/Y H:i'),
         'cor' => match ($requisicao->status) { 'indeferida' => 'red', 'cancelada' => 'slate', default => 'emerald' }],
    ];
    $iniciais = collect($linhas)->mapWithKeys(fn ($l) => ["{$l['modalidade']}.{$l['tipo']}" => (int) old("liberado.{$l['modalidade']}.{$l['tipo']}", $l['pedido'])])->all();
@endphp

<div class="max-w-8xl mx-auto space-y-4" x-data="{ verDeclaracoes: false, indeferindo: {{ $errors->has('motivo') ? 'true' : 'false' }} }">

    @foreach(['success' => 'emerald', 'error' => 'red'] as $chave => $cor)
        @if(session($chave))
        <div class="px-4 py-2.5 rounded-lg border text-sm {{ $cor === 'emerald' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800' }}">{{ session($chave) }}</div>
        @endif
    @endforeach

    {{-- Cabeçalho + andamento --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="px-4 py-3 flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <a href="{{ $voltarUrl }}" title="Voltar ao processo"
                   class="w-8 h-8 flex-shrink-0 inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div class="w-10 h-10 flex-shrink-0 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Requisição de numeração · Processo {{ $processo->numero_processo }}</p>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-lg font-semibold text-slate-900 tabular-nums leading-tight">Nº {{ $requisicao->numero }}</h2>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold ring-1 ring-inset {{ $situacao['classe'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $situacao['dot'] }}"></span>{{ $situacao['label'] }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500">
                        <strong class="font-semibold text-slate-700">{{ $r['nome'] ?? '—' }}</strong>
                        @if(!empty($r['especialidade'])) · {{ $r['especialidade'] }}@endif
                        · pediu <strong class="font-semibold text-slate-700">{{ $requisicao->rotuloQuantidade() }}</strong>
                    </p>
                </div>
            </div>
        </div>
        <ol class="px-4 pb-3 grid grid-cols-3 gap-2">
            @foreach($etapas as $i => $etapa)
            @php $cor = $etapa['cor'] ?? 'emerald'; @endphp
            <li class="flex items-center gap-2 min-w-0">
                <span class="w-6 h-6 flex-shrink-0 rounded-full flex items-center justify-center text-[11px] font-bold
                    {{ $etapa['feito'] ? "bg-{$cor}-500 text-white" : (!empty($etapa['atual']) ? 'bg-amber-500 text-white ring-4 ring-amber-100' : 'bg-slate-100 text-slate-400') }}">
                    {{ $etapa['feito'] ? '✓' : $i + 1 }}
                </span>
                <span class="min-w-0">
                    <span class="block text-xs font-semibold truncate {{ $etapa['feito'] || !empty($etapa['atual']) ? 'text-slate-800' : 'text-slate-400' }}">{{ $etapa['rotulo'] }}</span>
                    <span class="block text-[10px] text-slate-400">{{ !empty($etapa['atual']) ? 'aguardando você' : ($etapa['quando'] ?? '') }}</span>
                </span>
                @if(!$loop->last)<span class="hidden sm:block flex-1 h-px {{ $etapa['feito'] ? 'bg-emerald-300' : 'bg-slate-200' }}"></span>@endif
            </li>
            @endforeach
        </ol>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_22rem] gap-4 items-start">
        {{-- Coluna principal --}}
        <div class="space-y-4 min-w-0">

            @if($aguardando)
            {{-- ===================== ANÁLISE: liberar com um documento do SNCR por tipo ===================== --}}
            <form method="POST" enctype="multipart/form-data"
                  action="{{ route('admin.estabelecimentos.processos.requisicoes.liberar', [$estabelecimento->id, $processo->id, $requisicao->id]) }}"
                  class="bg-white rounded-xl border border-slate-200 shadow-sm"
                  x-data="{
                      liberado: @js($iniciais),
                      arquivos: {},
                      enviando: false,
                      get pendentes() { return Object.keys(this.liberado).filter(k => (parseInt(this.liberado[k]) || 0) > 0 && !this.arquivos[k]) },
                      get total() { return Object.values(this.liberado).reduce((s, v) => s + (parseInt(v) || 0), 0) },
                      get podeLiberar() { return this.total > 0 && this.pendentes.length === 0 && !this.enviando }
                  }"
                  @submit="enviando = true">
                @csrf
                <header class="px-4 py-3 border-b border-slate-100">
                    <h3 class="text-sm font-semibold text-slate-900">Liberar a numeração</h3>
                    <ol class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px] text-slate-600">
                        <li class="flex items-start gap-1.5"><span class="w-4 h-4 flex-shrink-0 rounded-full bg-indigo-100 text-indigo-700 text-[10px] font-bold flex items-center justify-center">1</span>Confira o pedido e os limites ao lado</li>
                        <li class="flex items-start gap-1.5"><span class="w-4 h-4 flex-shrink-0 rounded-full bg-indigo-100 text-indigo-700 text-[10px] font-bold flex items-center justify-center">2</span>Libere a numeração de cada tipo no SNCR</li>
                        <li class="flex items-start gap-1.5"><span class="w-4 h-4 flex-shrink-0 rounded-full bg-indigo-100 text-indigo-700 text-[10px] font-bold flex items-center justify-center">3</span>Anexe aqui o documento do SNCR de cada tipo</li>
                    </ol>
                </header>

                @if($errors->hasAny(['liberado', 'documento', 'liberado.*.*', 'documento.*.*']) || $errors->has('liberado'))
                <div class="mx-4 mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                    @foreach($errors->all() as $erro)<p>{{ $erro }}</p>@endforeach
                    <p class="mt-1">Por segurança, anexe os documentos de novo.</p>
                </div>
                @endif

                <div class="p-4 space-y-2.5">
                    @foreach($linhas as $l)
                    @php $k = "{$l['modalidade']}.{$l['tipo']}"; $acima = in_array($l['tipo'], $acimaDoLimite, true); @endphp
                    <div class="rounded-lg border p-3 transition"
                         :class="(parseInt(liberado['{{ $k }}']) || 0) === 0 ? 'border-slate-200 bg-slate-50/70' : (arquivos['{{ $k }}'] ? 'border-emerald-300 bg-emerald-50/40' : 'border-slate-200 bg-white')">
                        <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center gap-3">
                            {{-- Tipo pedido --}}
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="inline-flex items-center justify-center min-w-[2.5rem] h-9 px-1.5 rounded-lg bg-slate-800 text-white text-sm font-bold">{{ $l['tipo'] }}</span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-slate-900 leading-tight">{{ $l['nome_tipo'] }}</span>
                                    <span class="inline-flex items-center gap-1 mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $l['modalidade'] === 'fisica' ? 'bg-sky-50 text-sky-700' : 'bg-violet-50 text-violet-700' }}">
                                        {{ $l['modalidade'] === 'fisica' ? '🖨️' : '💻' }} {{ $l['rotulo_modalidade'] }}
                                    </span>
                                    @if($acima)<span class="ml-1 text-[10px] font-semibold text-amber-700">acima do limite</span>@endif
                                </span>
                            </div>

                            {{-- Quantidade --}}
                            <div class="flex items-end gap-3 flex-shrink-0">
                                <div class="text-center">
                                    <span class="block text-[10px] font-semibold text-slate-400 uppercase">Pedido</span>
                                    <span class="block h-9 leading-9 text-base font-bold text-slate-700 tabular-nums">{{ number_format($l['pedido'], 0, ',', '.') }}</span>
                                </div>
                                <span class="pb-2 text-slate-300">→</span>
                                <label class="block">
                                    <span class="block text-[10px] font-semibold text-slate-400 uppercase text-center">Liberar</span>
                                    <input type="number" name="liberado[{{ $l['modalidade'] }}][{{ $l['tipo'] }}]" min="0" max="{{ $l['pedido'] }}" required
                                           x-model.number="liberado['{{ $k }}']"
                                           class="w-24 h-9 text-center text-base font-bold tabular-nums border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                </label>
                            </div>

                            {{-- Documento do SNCR --}}
                            <div class="sm:col-span-2 min-w-0">
                                <label x-show="(parseInt(liberado['{{ $k }}']) || 0) > 0"
                                       class="flex items-center gap-2 px-3 h-11 rounded-lg border-2 border-dashed cursor-pointer transition"
                                       :class="arquivos['{{ $k }}'] ? 'border-emerald-300 bg-white' : 'border-slate-300 hover:border-indigo-400 hover:bg-indigo-50/40'">
                                    <i class="fas fa-fw" :class="arquivos['{{ $k }}'] ? 'fa-check-circle text-emerald-500' : 'fa-upload text-slate-400'"></i>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-xs font-semibold truncate" :class="arquivos['{{ $k }}'] ? 'text-slate-800' : 'text-slate-600'"
                                              x-text="arquivos['{{ $k }}'] || 'Anexar documento do SNCR ({{ $l['tipo'] }} · {{ mb_strtolower($l['rotulo_modalidade']) }})'"></span>
                                        <span class="block text-[10px] text-slate-400">PDF ou imagem · até 10 MB</span>
                                    </span>
                                    <span x-show="arquivos['{{ $k }}']" class="text-[11px] font-semibold text-indigo-700">Trocar</span>
                                    <input type="file" name="documento[{{ $l['modalidade'] }}][{{ $l['tipo'] }}]" accept="application/pdf,image/jpeg,image/png" class="sr-only"
                                           @change="arquivos['{{ $k }}'] = $event.target.files[0]?.name || null">
                                </label>
                                <p x-show="(parseInt(liberado['{{ $k }}']) || 0) === 0" x-cloak class="text-xs text-slate-500">Não será liberado — sem documento.</p>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    <label class="block pt-1">
                        <span class="block text-xs font-semibold text-slate-600 mb-1">Observação para a empresa <span class="font-normal text-slate-400">(opcional)</span></span>
                        <textarea name="observacao" rows="2" maxlength="2000" placeholder="Ex.: liberado abaixo do pedido conforme parâmetros da DVISA."
                                  class="w-full text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('observacao') }}</textarea>
                    </label>
                </div>

                <footer class="px-4 py-3 border-t border-slate-100 bg-slate-50 rounded-b-xl flex flex-col sm:flex-row sm:items-center gap-3">
                    <p class="flex-1 text-xs" :class="podeLiberar ? 'text-emerald-700' : 'text-slate-500'"
                       x-text="total === 0 ? 'Nenhuma quantidade a liberar — para negar o pedido, use Indeferir.' : (pendentes.length ? 'Falta anexar o documento de ' + pendentes.length + ' tipo(s).' : 'Pronto: ' + total.toLocaleString('pt-BR') + ' {{ $unidade }} serão liberadas e os documentos ficarão no processo da empresa.')"></p>
                    <div class="flex items-center gap-2 justify-end">
                        <button type="button" @click="indeferindo = true; $nextTick(() => document.getElementById('motivo-indeferimento')?.focus())"
                                class="h-10 px-4 text-sm font-semibold text-red-700 bg-white border border-red-200 rounded-lg hover:bg-red-50">Indeferir</button>
                        <button type="submit" :disabled="!podeLiberar"
                                class="inline-flex items-center gap-2 h-10 px-5 text-sm font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span x-text="enviando ? 'Liberando…' : 'Liberar requisição'"></span>
                        </button>
                    </div>
                </footer>
            </form>

            {{-- Indeferir --}}
            <form x-show="indeferindo" x-cloak method="POST"
                  action="{{ route('admin.estabelecimentos.processos.requisicoes.indeferir', [$estabelecimento->id, $processo->id, $requisicao->id]) }}"
                  class="bg-white rounded-xl border border-red-200 shadow-sm p-4 space-y-2">
                @csrf
                <h3 class="text-sm font-semibold text-red-800">Indeferir a requisição nº {{ $requisicao->numero }}</h3>
                <p class="text-xs text-slate-600">Nenhuma numeração será liberada. A empresa verá o motivo no processo.</p>
                <textarea id="motivo-indeferimento" name="motivo" rows="3" required minlength="10" maxlength="2000" placeholder="Motivo do indeferimento (mínimo 10 caracteres)"
                          class="w-full text-sm border border-red-200 rounded-lg focus:ring-2 focus:ring-red-400 focus:border-red-400">{{ old('motivo') }}</textarea>
                @error('motivo')<p class="text-xs font-semibold text-red-700">{{ $message }}</p>@enderror
                <div class="flex justify-end gap-2">
                    <button type="button" @click="indeferindo = false" class="h-9 px-3.5 text-sm text-slate-600 hover:text-slate-900">Cancelar</button>
                    <button type="submit" class="h-9 px-4 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700">Confirmar indeferimento</button>
                </div>
            </form>

            @elseif($requisicao->status === 'liberada')
            {{-- ===================== LIBERADA: o que foi liberado e os documentos ===================== --}}
            <section class="bg-white rounded-xl border border-emerald-200 shadow-sm overflow-hidden">
                <header class="px-4 py-3 border-b border-emerald-100 bg-emerald-50/60 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-emerald-900">Numeração liberada</h3>
                        <p class="text-[11px] text-emerald-800">{{ $requisicao->analisadoPor ? 'Por ' . $requisicao->analisadoPor->nome . ' · ' : '' }}{{ $requisicao->analisado_em?->format('d/m/Y \à\s H:i') }} · documentos disponíveis no processo da empresa</p>
                    </div>
                    <p class="text-sm text-emerald-800"><span class="text-2xl font-bold tabular-nums">{{ number_format(collect($linhas)->sum('liberado'), 0, ',', '.') }}</span> {{ $unidade }}</p>
                </header>
                <ul class="divide-y divide-slate-100">
                    @foreach($linhas as $l)
                    @php $doc = $l['documento_id'] ? $documentos->get($l['documento_id']) : null; @endphp
                    <li class="px-4 py-3 flex flex-col md:flex-row md:items-center gap-3">
                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                            <span class="inline-flex items-center justify-center min-w-[2.5rem] h-9 px-1.5 rounded-lg {{ $l['liberado'] ? 'bg-emerald-600' : 'bg-slate-300' }} text-white text-sm font-bold">{{ $l['tipo'] }}</span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-slate-900">{{ $l['nome_tipo'] }} <span class="font-normal text-slate-500">· {{ $l['rotulo_modalidade'] }}</span></span>
                                <span class="block text-xs {{ $l['liberado'] < $l['pedido'] ? 'text-amber-700' : 'text-slate-500' }}">
                                    {{ $l['liberado'] ? number_format($l['liberado'], 0, ',', '.') . ' liberada(s)' : 'Não liberado' }} de {{ number_format($l['pedido'], 0, ',', '.') }} pedida(s)
                                </span>
                            </span>
                        </div>
                        @if($doc)
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <a href="{{ route('admin.estabelecimentos.processos.visualizar', [$estabelecimento->id, $processo->id, $doc->id]) }}" target="_blank"
                               class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">
                                <i class="far fa-file-pdf text-red-500"></i> Ver documento
                            </a>
                            <a href="{{ route('admin.estabelecimentos.processos.download', [$estabelecimento->id, $processo->id, $doc->id]) }}" title="Baixar"
                               class="inline-flex items-center justify-center w-8 h-8 text-slate-500 bg-white border border-slate-200 rounded-lg hover:bg-slate-50"><i class="fas fa-download"></i></a>
                        </div>
                        @endif
                    </li>
                    @endforeach
                </ul>
                @if($requisicao->observacao_vigilancia)
                <p class="px-4 py-3 border-t border-slate-100 text-sm text-slate-700"><span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Observação para a empresa</span>{{ $requisicao->observacao_vigilancia }}</p>
                @endif
            </section>

            @elseif($requisicao->status === 'indeferida')
            <section class="bg-white rounded-xl border border-red-200 shadow-sm p-4">
                <h3 class="text-sm font-semibold text-red-800">Requisição indeferida</h3>
                <p class="text-[11px] text-slate-500">{{ $requisicao->analisadoPor ? 'Por ' . $requisicao->analisadoPor->nome . ' · ' : '' }}{{ $requisicao->analisado_em?->format('d/m/Y \à\s H:i') }}</p>
                <p class="mt-2 text-sm text-slate-800 whitespace-pre-line">{{ $requisicao->observacao_vigilancia }}</p>
            </section>

            @elseif($requisicao->status === 'cancelada')
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 text-sm text-slate-600">
                Cancelada pela empresa em {{ $requisicao->cancelado_em?->format('d/m/Y H:i') }}. Não há o que analisar.
            </section>
            @endif

            {{-- Pedido da empresa (visão rápida, quando não está analisando) --}}
            @unless($aguardando)
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Pedido da empresa</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($linhas as $l)
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-slate-100 text-xs text-slate-700">
                        <span class="text-slate-500">{{ $l['rotulo_modalidade'] }}</span> <strong>{{ $l['tipo'] }}</strong> × {{ number_format($l['pedido'], 0, ',', '.') }}
                    </span>
                    @endforeach
                </div>
            </section>
            @endunless

            {{-- Justificativa --}}
            <section class="rounded-xl border shadow-sm p-4 {{ $requisicao->justificativa ? ($acimaDoLimite ? 'bg-amber-50/60 border-amber-200' : 'bg-white border-slate-200') : 'bg-white border-slate-200' }}">
                <p class="text-[11px] font-semibold uppercase tracking-wider {{ $acimaDoLimite ? 'text-amber-700' : 'text-slate-400' }}">
                    Justificativa da empresa{{ $acimaDoLimite ? ' · pedido acima do limite em ' . implode(', ', $acimaDoLimite) : '' }}
                </p>
                <p class="mt-1 text-sm {{ $requisicao->justificativa ? 'text-slate-800 whitespace-pre-line' : 'text-slate-400' }}">{{ $requisicao->justificativa ?: 'Não informada' }}</p>
            </section>

            {{-- Requisitante + assinatura --}}
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
                <div class="px-4 py-3 border-t border-slate-100 flex items-start gap-3">
                    <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-slate-700">
                            Assinada eletronicamente por <span class="font-semibold">{{ $requisicao->usuarioExterno?->nome ?? 'usuário externo' }}</span>
                            em {{ $requisicao->assinado_em?->format('d/m/Y \à\s H:i:s') }}{{ $requisicao->ip_address ? ' · IP ' . $requisicao->ip_address : '' }}
                        </p>
                        <button type="button" @click="verDeclaracoes = !verDeclaracoes" class="mt-0.5 text-xs font-medium text-blue-700 hover:underline"
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
            {{-- Limites do profissional --}}
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Limites para este profissional</p>
                <p class="text-[11px] text-slate-500 mb-2">{{ $r['especialidade'] ?? 'Especialidade não informada' }} · {{ $unidade }}, física + eletrônica</p>
                <ul class="space-y-1.5">
                    @foreach($Req::TIPOS_NOTIFICACAO as $tipo)
                    @php
                        $lim = $limitesProfissional[$tipo];
                        $pedido = $pedidoPorTipo[$tipo];
                        $acima = in_array($tipo, $acimaDoLimite, true);
                        $pct = $lim['limite'] ? min(100, round($pedido * 100 / $lim['limite'])) : ($pedido ? 100 : 0);
                    @endphp
                    <li class="text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-slate-800 w-7">{{ $tipo }}</span>
                            <span class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden"><span class="block h-full {{ $acima ? 'bg-amber-500' : 'bg-indigo-400' }}" style="width: {{ $pct }}%"></span></span>
                            <span class="w-28 text-right tabular-nums {{ $acima ? 'font-semibold text-amber-700' : 'text-slate-600' }}">
                                {{ number_format($pedido, 0, ',', '.') }} / {{ $lim['justificar'] && !$lim['limite'] ? 'justif.' : number_format($lim['limite'], 0, ',', '.') }}
                            </span>
                        </div>
                    </li>
                    @endforeach
                </ul>
                <details class="mt-3 text-[11px] text-slate-500">
                    <summary class="cursor-pointer font-semibold hover:text-slate-700">Parâmetros da DVISA</summary>
                    <ul class="mt-1.5 space-y-0.5">
                        @foreach($Req::PARAMETROS_ENTREGA as $tipo => $p)
                        <li><strong>{{ $tipo }}:</strong> até {{ $p['especialista'] }} ({{ $p['rotulo'] }}) · demais até {{ $p['outras'] }}{{ !empty($p['outras_justificativa']) ? ' + justificativa' : '' }}</li>
                        @endforeach
                        @foreach($Req::PARAMETROS_OUTROS as $outro)
                        <li><strong>{{ $outro['rotulo'] }}:</strong> {{ collect($outro['limites'])->map(fn ($n, $t) => "até {$n} {$t}")->implode(' e ') }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-1 text-slate-400">Referência: bloco de {{ $Req::FOLHAS_POR_BLOCO_REFERENCIA }} folhas.</p>
                </details>
            </section>

            {{-- Histórico do profissional --}}
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <p class="px-4 pt-3 pb-2 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Requisições anteriores do profissional</p>
                @forelse($historico as $anterior)
                <a href="{{ route('admin.estabelecimentos.processos.requisicoes.show', [$estabelecimento->id, $anterior->processo_id, $anterior->id]) }}"
                   class="px-4 py-2 border-t border-slate-100 flex items-center justify-between gap-2 hover:bg-slate-50">
                    <span class="min-w-0">
                        <span class="block text-xs font-semibold text-slate-800 tabular-nums">Nº {{ $anterior->numero }}</span>
                        <span class="block text-[11px] text-slate-500">{{ $anterior->created_at->format('d/m/Y') }} · {{ $anterior->rotuloQuantidade() }}</span>
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
