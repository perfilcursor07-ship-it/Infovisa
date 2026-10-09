{{-- Processos de receituário: só com o cadastro aprovado e só abertos por aqui --}}
@if($receituario->isAprovado())
    @php
        $processos = $receituario->estabelecimento?->processos?->sortByDesc('created_at') ?? collect();
        $statusProcesso = [
            'aberto' => 'bg-blue-50 text-blue-700', 'em_analise' => 'bg-violet-50 text-violet-700', 'em_andamento' => 'bg-amber-50 text-amber-700',
            'parado' => 'bg-red-50 text-red-700', 'arquivado' => 'bg-slate-100 text-slate-600', 'concluido' => 'bg-emerald-50 text-emerald-700',
        ];
    @endphp
    <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" x-data="{ abrir: false }" @abrir-processo-receituario.window="abrir = true">
        <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/></svg>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Processos de receituário</h2>
                    <p class="text-xs text-slate-500">O processo de receituário é aberto automaticamente na aprovação do cadastro — é nele que é feita a requisição.</p>
                </div>
            </div>
            {{-- O processo é aberto automaticamente na aprovação; abrir manualmente só quando não há nenhum em andamento --}}
            @if($tiposProcesso->isNotEmpty() && $processos->whereNotIn('status', ['arquivado', 'concluido', 'indeferido'])->isEmpty())
                <button type="button" @click="abrir = !abrir"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Abrir processo
                </button>
            @endif
        </div>

        @if($tiposProcesso->isEmpty())
            <p class="px-5 py-4 text-sm text-slate-500">Nenhum tipo de processo de receituário está disponível no momento. Entre em contato com a Vigilância Sanitária.</p>
        @else
            <form x-show="abrir" x-cloak method="POST" action="{{ route('company.receituarios.abrir-processo', $receituario->id) }}"
                  class="px-5 py-4 bg-slate-50 border-b border-slate-100 grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-3 items-end">
                @csrf
                <label class="block">
                    <span class="block text-xs font-semibold text-slate-600 mb-1">Tipo de processo *</span>
                    <select name="tipo_processo_id" required class="w-full px-3 py-2.5 text-sm bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @foreach($tiposProcesso as $tipo)
                            <option value="{{ $tipo->id }}">{{ $tipo->nome }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="block text-xs font-semibold text-slate-600 mb-1">Observação (opcional)</span>
                    <input type="text" name="observacao" maxlength="1000" class="w-full px-3 py-2.5 text-sm bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </label>
                <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Confirmar abertura</button>
            </form>
        @endif

        @if($processos->isEmpty())
            <p class="px-5 py-4 text-sm text-slate-400">Nenhum processo aberto ainda.</p>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach($processos as $processo)
                    <li>
                        <a href="{{ route('company.processos.show', $processo->id) }}" class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-slate-50">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-slate-900">{{ $processo->tipo_nome }}</span>
                                <span class="block text-xs text-slate-500">nº {{ $processo->numero_processo }} · aberto em {{ $processo->created_at->format('d/m/Y') }}</span>
                            </span>
                            <span class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $statusProcesso[$processo->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $processo->status_nome ?? ucfirst(str_replace('_', ' ', $processo->status)) }}</span>
                                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endif
