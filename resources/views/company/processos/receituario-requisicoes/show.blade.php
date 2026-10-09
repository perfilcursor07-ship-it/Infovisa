@extends('layouts.company')

@section('title', 'Requisição de receituário nº ' . $requisicao->numero)
@section('page-title', 'Requisição de receituário')

@section('content')
@php
    $Req = \App\Models\ReceituarioRequisicao::class;
    $r = $requisicao->requisitante ?? [];
    $situacao = $requisicao->situacao;
    $liberadas = $requisicao->quantidades_liberadas;
    $etapas = [
        ['chave' => 'enviada', 'rotulo' => 'Enviada', 'data' => $requisicao->assinado_em],
        ['chave' => 'em_analise', 'rotulo' => 'Em análise', 'data' => null],
        ['chave' => 'final', 'rotulo' => $requisicao->status === 'indeferida' ? 'Indeferida' : 'Liberada', 'data' => $requisicao->analisado_em],
    ];
    $etapaAtual = match ($requisicao->status) {
        'enviada' => 0,
        'em_analise' => 1,
        'liberada', 'indeferida' => 2,
        default => -1,
    };
@endphp

<style>
    @media print {
        body * { visibility: hidden; }
        #comprovante-requisicao, #comprovante-requisicao * { visibility: visible; }
        #comprovante-requisicao { position: absolute; inset: 0; box-shadow: none !important; border: 0 !important; }
        .no-print { display: none !important; }
    }
</style>

<div class="max-w-8xl mx-auto space-y-5">
    {{-- Barra de ações (mensagens de sucesso/erro já são exibidas pelo layout) --}}
    <div class="no-print flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <a href="{{ route('company.processos.show', $processo->id) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Voltar ao processo {{ $processo->numero_processo }}
        </a>
        <div class="flex gap-2">
            @if($requisicao->podeSerCancelada())
            <form method="POST" action="{{ route('company.processos.receituario-requisicoes.cancelar', [$processo->id, $requisicao->id]) }}"
                  onsubmit="return confirm('Cancelar a requisição nº {{ $requisicao->numero }}? Você poderá enviar uma nova depois.')">
                @csrf
                <button type="submit" class="h-9 px-3.5 text-sm font-medium text-red-700 bg-white border border-red-200 rounded-lg hover:bg-red-50">Cancelar requisição</button>
            </form>
            @endif
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-1.5 h-9 px-3.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Imprimir
            </button>
        </div>
    </div>

    {{-- Andamento --}}
    @if($etapaAtual >= 0)
    <div class="no-print bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4">
        <ol class="flex items-center">
            @foreach($etapas as $i => $etapa)
            @php
                $feita = $i <= $etapaAtual;
                $cor = $feita ? ($i === 2 && $requisicao->status === 'indeferida' ? 'bg-red-500 text-white' : 'bg-emerald-500 text-white') : 'bg-slate-100 text-slate-400';
            @endphp
            <li class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-full {{ $cor }} flex items-center justify-center text-xs font-bold">{{ $feita ? '✓' : $i + 1 }}</span>
                    <div class="leading-tight">
                        <p class="text-xs font-semibold {{ $feita ? 'text-slate-900' : 'text-slate-400' }}">{{ $etapa['rotulo'] }}</p>
                        @if($etapa['data'])<p class="text-[10px] text-slate-500">{{ $etapa['data']->format('d/m/Y H:i') }}</p>@endif
                    </div>
                </div>
                @if(!$loop->last)
                <div class="flex-1 h-0.5 mx-3 {{ $i < $etapaAtual ? 'bg-emerald-400' : 'bg-slate-200' }}"></div>
                @endif
            </li>
            @endforeach
        </ol>
        @if($requisicao->status === 'enviada')
        <p class="mt-3 text-xs text-slate-500">A Vigilância Sanitária vai analisar sua requisição. Você será avisado quando ela for liberada.</p>
        @endif
    </div>
    @endif

    {{-- Liberada: documento de numeração (SNCR) de cada tipo --}}
    @if($requisicao->status === 'liberada')
    @php $documentosLiberados = $requisicao->documentosLiberados(); @endphp
    <section class="no-print bg-white rounded-xl border border-emerald-200 shadow-sm overflow-hidden">
        <header class="px-5 py-3 bg-emerald-50/70 border-b border-emerald-100">
            <h2 class="text-sm font-semibold text-emerald-900">✓ Numeração liberada pela Vigilância Sanitária</h2>
            <p class="text-xs text-emerald-800">Baixe o documento de cada tipo: nele estão os números liberados no SNCR. Para a física, leve à gráfica; a eletrônica já fica no seu saldo do SNCR.</p>
        </header>
        <ul class="divide-y divide-slate-100">
            @foreach($requisicao->linhasPedidas() as $l)
            @php $doc = $l['documento_id'] ? $documentosLiberados->get($l['documento_id']) : null; @endphp
            <li class="px-5 py-3 flex flex-col sm:flex-row sm:items-center gap-3">
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
                    <a href="{{ route('company.processos.documento.visualizar', [$processo->id, $doc->id]) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 h-9 px-3 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">Ver documento</a>
                    <a href="{{ route('company.processos.download', [$processo->id, $doc->id]) }}"
                       class="inline-flex items-center gap-1.5 h-9 px-3 text-xs font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Baixar</a>
                </div>
                @endif
            </li>
            @endforeach
        </ul>
        @if($requisicao->observacao_vigilancia)
        <p class="px-5 py-3 border-t border-slate-100 text-sm text-slate-700"><span class="block text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Observação da Vigilância</span>{{ $requisicao->observacao_vigilancia }}</p>
        @endif
    </section>
    @elseif($requisicao->status === 'indeferida')
    <section class="no-print bg-white rounded-xl border border-red-200 shadow-sm px-5 py-4">
        <h2 class="text-sm font-semibold text-red-800">Requisição indeferida</h2>
        <p class="mt-1 text-sm text-slate-800 whitespace-pre-line">{{ $requisicao->observacao_vigilancia }}</p>
        <p class="mt-2 text-xs text-slate-500">Você pode fazer uma nova requisição no processo, corrigindo o que foi apontado.</p>
    </section>
    @endif

    {{-- Comprovante --}}
    <article id="comprovante-requisicao" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <header class="px-6 py-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Requisição de notificação e/ou numeração de receituário</p>
                <h1 class="text-xl font-bold text-slate-900 tabular-nums">Nº {{ $requisicao->numero }}</h1>
                <p class="text-xs text-slate-500 mt-0.5">Processo {{ $processo->numero_processo }} · enviada em {{ $requisicao->created_at->format('d/m/Y \à\s H:i') }}</p>
            </div>
            <span class="self-start inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset {{ $situacao['classe'] }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $situacao['dot'] }}"></span>
                {{ $situacao['label'] }}
            </span>
        </header>

        {{-- Requisitante --}}
        <section class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2.5">Requisitante</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-4 gap-x-5 gap-y-2.5 text-sm">
                <div class="sm:col-span-2"><dt class="text-[11px] text-slate-500">Requisitante</dt><dd class="font-medium text-slate-900">{{ $r['nome'] ?? '—' }}</dd></div>
                <div><dt class="text-[11px] text-slate-500">CNPJ/CPF</dt><dd class="text-slate-900 tabular-nums">{{ $r['documento'] ?? '—' }}</dd></div>
                <div><dt class="text-[11px] text-slate-500">Nº / Conselho</dt><dd class="text-slate-900">{{ $r['conselho'] ?: '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-[11px] text-slate-500">Endereço completo</dt><dd class="text-slate-900">{{ $r['endereco'] ?? '—' }}</dd></div>
                <div><dt class="text-[11px] text-slate-500">Município</dt><dd class="text-slate-900">{{ $r['municipio'] ?? '—' }}</dd></div>
                <div><dt class="text-[11px] text-slate-500">CEP</dt><dd class="text-slate-900 tabular-nums">{{ $r['cep'] ?? '—' }}</dd></div>
                @if(!empty($r['especialidade']))
                <div class="sm:col-span-2"><dt class="text-[11px] text-slate-500">Especialidade</dt><dd class="text-slate-900">{{ $r['especialidade'] }}</dd></div>
                @endif
            </dl>
        </section>

        {{-- Quantidades --}}
        <section class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2.5">Tipos de notificação requerida (quantidade de {{ $requisicao->emBlocos() ? 'blocos' : 'numerações' }})</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border border-slate-200 rounded-lg overflow-hidden">
                    <thead>
                        <tr class="bg-slate-50 text-xs text-slate-600">
                            <th class="px-3 py-2 text-left font-semibold">Modalidade</th>
                            @foreach($Req::TIPOS_NOTIFICACAO as $tipo)
                            <th class="px-3 py-2 text-center font-semibold">{{ $tipo }}</th>
                            @endforeach
                            <th class="px-3 py-2 text-center font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($Req::MODALIDADES as $chave => $rotulo)
                        @php $linha = $requisicao->quantidades[$chave] ?? []; @endphp
                        <tr>
                            <td class="px-3 py-2 font-medium text-slate-800">{{ $rotulo }}</td>
                            @foreach($Req::TIPOS_NOTIFICACAO as $tipo)
                            @php
                                $qtd = (int) ($linha[$tipo] ?? 0);
                                $lib = $liberadas[$chave][$tipo] ?? null;
                            @endphp
                            <td class="px-3 py-2 text-center tabular-nums {{ $qtd > 0 ? 'font-bold text-slate-900' : 'text-slate-300' }}">
                                {{ $qtd ?: '—' }}
                                @if($liberadas !== null && $qtd > 0)
                                <span class="block text-[10px] font-semibold {{ (int) $lib >= $qtd ? 'text-emerald-600' : 'text-amber-600' }}">liberado: {{ (int) $lib }}</span>
                                @endif
                            </td>
                            @endforeach
                            <td class="px-3 py-2 text-center font-semibold tabular-nums text-slate-700">{{ array_sum(array_map('intval', $linha)) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($requisicao->justificativa)
            <div class="mt-3">
                <p class="text-[11px] text-slate-500">Justificativa</p>
                <p class="text-sm text-slate-800 whitespace-pre-line">{{ $requisicao->justificativa }}</p>
            </div>
            @endif
        </section>

        @if($requisicao->observacao_vigilancia)
        <section class="px-6 py-4 border-b border-slate-100 {{ $requisicao->status === 'indeferida' ? 'bg-red-50/60' : 'bg-emerald-50/60' }}">
            <h2 class="text-xs font-semibold uppercase tracking-wide mb-1 {{ $requisicao->status === 'indeferida' ? 'text-red-700' : 'text-emerald-700' }}">Resposta da Vigilância Sanitária</h2>
            <p class="text-sm text-slate-800 whitespace-pre-line">{{ $requisicao->observacao_vigilancia }}</p>
        </section>
        @endif

        {{-- Declarações --}}
        <section class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2.5">Declarações</h2>
            <ul class="space-y-1.5">
                @foreach($requisicao->declaracoes ?? [] as $declaracao)
                <li class="flex items-start gap-2 text-xs text-slate-700 leading-relaxed">
                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    {{ $declaracao['texto'] ?? '' }}
                </li>
                @endforeach
            </ul>
        </section>

        {{-- Assinatura --}}
        <footer class="px-6 py-4 bg-slate-50 flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <p class="text-xs text-slate-600">
                Assinado eletronicamente por <span class="font-semibold text-slate-900">{{ $requisicao->usuarioExterno?->nome ?? 'Usuário externo' }}</span>
                em {{ $requisicao->assinado_em?->format('d/m/Y \à\s H:i:s') }}{{ $requisicao->ip_address ? ' · IP ' . $requisicao->ip_address : '' }}.
                Preenchimento eletrônico pelo InfoVISA.
            </p>
        </footer>
    </article>
</div>
@endsection
