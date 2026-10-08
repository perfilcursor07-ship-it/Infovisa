{{-- Documentos do cadastro: cada um é analisado pela Vigilância; rejeitado → a empresa reenvia só aquele --}}
@if($receituario->isSolicitacaoExterna())
@php
    $docs = $receituario->documentosDaAnalise();
    $situacoes = collect($docs)->mapWithKeys(fn ($d) => [$d => $receituario->statusDocumento($d)]);
    $aprovados = $situacoes->filter(fn ($s) => $s === 'aprovado')->count();
    $urlVer = [
        'carteira' => route('company.receituarios.carteira', $receituario->id),
        'comprovante' => route('company.receituarios.comprovante', $receituario->id),
    ];
@endphp
<section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Documentos do cadastro</h2>
            <p class="text-xs text-slate-500">A Vigilância Sanitária analisa cada documento. Se algum for rejeitado, você corrige só aquela parte do cadastro.</p>
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-600">
            <div class="w-24 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full bg-emerald-500" style="width: {{ count($docs) ? round($aprovados * 100 / count($docs)) : 0 }}%"></div>
            </div>
            <span class="font-semibold tabular-nums">{{ $aprovados }}/{{ count($docs) }} aprovados</span>
        </div>
    </div>

    <ul class="divide-y divide-slate-100">
        @foreach($docs as $doc)
            @php
                $info = \App\Models\Receituario::DOCUMENTOS[$doc];
                $situacao = $situacoes[$doc];
                $estilo = \App\Models\Receituario::STATUS_DOCUMENTO[$situacao];
                $analise = $receituario->analiseDocumento($doc);

                $reenvios = count($analise['historico'] ?? []);
            @endphp
            <li id="documento-{{ $doc }}" class="scroll-mt-6 px-5 py-3.5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex items-start gap-3 min-w-0">
                        <span class="mt-0.5 w-8 h-8 flex-shrink-0 rounded-lg flex items-center justify-center text-sm
                            {{ ['aprovado' => 'bg-emerald-50 text-emerald-600', 'rejeitado' => 'bg-red-50 text-red-600', 'nao_enviado' => 'bg-amber-50 text-amber-600'][$situacao] ?? 'bg-blue-50 text-blue-600' }}">
                            {{ ['aprovado' => '✓', 'rejeitado' => '✕', 'nao_enviado' => '!'][$situacao] ?? '⏳' }}
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900">{{ $info['nome'] }}</p>
                            <p class="text-xs text-slate-500">{{ $info['detalhe'] }}{{ $reenvios ? ' · reenviado ' . $reenvios . 'x' : '' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 sm:flex-shrink-0 pl-11 sm:pl-0">
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset {{ $estilo['classe'] }}">{{ $estilo['label'] }}</span>
                        @if($receituario->temDocumento($doc))
                            <a href="{{ $urlVer[$doc] }}" target="_blank" rel="noopener" class="text-xs font-semibold text-blue-700 hover:underline">Ver</a>
                            @if($doc === 'carteira' && $receituario->carteira_conselho_verso_path)
                                <a href="{{ route('company.receituarios.carteira', [$receituario->id, 'verso']) }}" target="_blank" rel="noopener" class="text-xs font-semibold text-blue-700 hover:underline">Verso</a>
                            @endif
                        @endif
                    </div>
                </div>

                @if($situacao === 'rejeitado')
                    {{-- Rejeitado: a correção é feita no mesmo passo do cadastro (dados + novo arquivo) --}}
                    <div class="mt-2.5 ml-11 rounded-xl border border-red-200 bg-red-50 p-3 flex flex-col md:flex-row md:items-center gap-3">
                        <div class="flex-1 min-w-0 text-xs text-red-900">
                            <p><strong>Motivo da rejeição:</strong> <span class="whitespace-pre-line">{{ $analise['motivo'] ?? 'não informado' }}</span></p>
                            <p class="mt-1 text-red-700">
                                {{ $doc === 'carteira'
                                    ? 'Corrija no Passo 1 (Dados pessoais): envie a carteira de novo — frente e verso — e confira os dados do profissional.'
                                    : 'Corrija no Passo 2 (Endereço): envie um novo comprovante e confira o endereço.' }}
                            </p>
                        </div>
                        <a href="{{ route('company.receituarios.corrigir', [$receituario->id, $doc]) }}"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 whitespace-nowrap">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Corrigir {{ mb_strtolower($info['nome']) }}
                        </a>
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
</section>
@endif
