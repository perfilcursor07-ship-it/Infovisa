{{-- Requisições de notificação/numeração de receita (somente processos de receituário) --}}
@php
    $requisicaoEmAndamento ??= null;
    // Uma requisição por vez: com uma aguardando a Vigilância, não dá para pedir outra
    $podeNovaRequisicao = !$processoArquivado && $receituarioAprovado && !$requisicaoEmAndamento;
@endphp
<section id="secao-requisicoes" class="scroll-mt-4 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <header class="px-4 py-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
        <div class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            </span>
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Requisições de receituário</h2>
                <p class="text-[11px] text-slate-500">Cada pedido de notificação/numeração é individual e passa pela liberação da Vigilância Sanitária</p>
            </div>
        </div>
        @if($podeNovaRequisicao)
        <a href="{{ route('company.processos.receituario-requisicoes.create', $processo->id) }}"
           class="self-start sm:self-auto flex-shrink-0 whitespace-nowrap inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nova requisição
        </a>
        @endif
    </header>

    @if($requisicaoEmAndamento && !$processoArquivado)
    <div class="mx-4 mt-3 flex flex-col sm:flex-row sm:items-center gap-2 p-2.5 rounded-lg bg-blue-50 border border-blue-200 text-xs text-blue-900">
        <svg class="w-4 h-4 flex-shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="flex-1">
            A requisição <strong>nº {{ $requisicaoEmAndamento->numero }}</strong> está aguardando a análise da Vigilância Sanitária.
            Você poderá fazer uma nova requisição depois que ela for liberada ou indeferida{{ $requisicaoEmAndamento->podeSerCancelada() ? ' — ou se cancelá-la' : '' }}.
        </span>
        <a href="{{ route('company.processos.receituario-requisicoes.show', [$requisicaoEmAndamento->processo_id, $requisicaoEmAndamento->id]) }}"
           class="self-start sm:self-auto font-semibold text-blue-700 hover:underline whitespace-nowrap">Ver requisição →</a>
    </div>
    @endif

    @if(!$receituarioAprovado && !$processoArquivado)
    <div class="mx-4 mt-3 flex items-start gap-2 p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-800">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        O cadastro de receituário precisa estar aprovado pela Vigilância para enviar requisições.
    </div>
    @endif

    @if($requisicoesReceituario->isEmpty())
    <div class="px-4 py-10 text-center">
        <div class="w-11 h-11 mx-auto rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mb-2.5">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <p class="text-sm font-semibold text-slate-800">Nenhuma requisição enviada</p>
        <p class="text-xs text-slate-500 mt-1">Solicite notificações de receita A, B, B2, C2 e C3, físicas ou eletrônicas.</p>
        @if($podeNovaRequisicao)
        <a href="{{ route('company.processos.receituario-requisicoes.create', $processo->id) }}"
           class="inline-flex items-center gap-1.5 mt-3.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm">
            Fazer primeira requisição →
        </a>
        @endif
    </div>
    @else
    <ul class="divide-y divide-slate-100">
        @foreach($requisicoesReceituario as $requisicao)
        @php $situacaoReq = $requisicao->situacao; @endphp
        <li>
            <a href="{{ route('company.processos.receituario-requisicoes.show', [$processo->id, $requisicao->id]) }}"
               class="group flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 px-4 py-3 hover:bg-slate-50 transition">
                <div class="sm:w-36 flex-shrink-0">
                    <p class="text-sm font-bold text-slate-900 tabular-nums group-hover:text-blue-700">Nº {{ $requisicao->numero }}</p>
                    <p class="text-[11px] text-slate-500">{{ $requisicao->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div class="flex-1 min-w-0 flex flex-wrap gap-1.5">
                    @foreach($requisicao->resumoQuantidades() as $modalidade)
                        @foreach($modalidade['itens'] as $item)
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[11px] bg-slate-100 text-slate-700">
                            <span class="text-slate-400">{{ $modalidade['rotulo'] }}</span>
                            <span class="font-bold">{{ $item['tipo'] }}</span>
                            <span class="tabular-nums">× {{ $item['quantidade'] }}</span>
                        </span>
                        @endforeach
                    @endforeach
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset {{ $situacaoReq['classe'] }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $situacaoReq['dot'] }}"></span>
                        {{ $situacaoReq['label'] }}
                    </span>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-blue-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
            </a>

            {{-- Documentos de numeração (SNCR) liberados por esta requisição --}}
            @php
                $documentosDaRequisicao = $requisicao->status === 'liberada' ? $requisicao->documentosLiberados() : collect();
                $liberadosNaRequisicao = collect($requisicao->linhasPedidas())->filter(fn ($l) => $l['documento_id'] && $documentosDaRequisicao->has($l['documento_id']));
            @endphp
            @if($liberadosNaRequisicao->isNotEmpty())
            <div class="px-4 pb-3 -mt-1">
                <p class="text-[10px] font-semibold text-emerald-700 uppercase tracking-wider mb-1.5">✓ Documentos de numeração ({{ $liberadosNaRequisicao->count() }})</p>
                <div class="grid grid-cols-1 gap-1.5">
                    @foreach($liberadosNaRequisicao as $l)
                    @php $doc = $documentosDaRequisicao->get($l['documento_id']); @endphp
                    <div class="flex items-center gap-2 rounded-lg border border-emerald-100 bg-emerald-50/50 px-2 py-1.5">
                        <span class="inline-flex items-center justify-center min-w-[1.75rem] h-6 px-1 rounded-md bg-emerald-600 text-white text-[11px] font-bold flex-shrink-0">{{ $l['tipo'] }}</span>
                        <a href="{{ route('company.processos.documento.visualizar', [$processo->id, $doc->id]) }}" target="_blank" title="{{ $doc->nome_original }}"
                           class="min-w-0 flex-1 hover:text-blue-700">
                            <span class="block text-xs font-semibold text-slate-800 truncate">{{ $l['nome_tipo'] }} · {{ $l['rotulo_modalidade'] }}</span>
                            <span class="block text-[10px] text-slate-500">{{ number_format((int) $l['liberado'], 0, ',', '.') }} numeração(ões) liberada(s) · clique para ver</span>
                        </a>
                        <a href="{{ route('company.processos.download', [$processo->id, $doc->id]) }}" title="Baixar"
                           class="p-1 rounded-md text-slate-400 hover:text-blue-700 hover:bg-white flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </li>
        @endforeach
    </ul>
    @endif
</section>
