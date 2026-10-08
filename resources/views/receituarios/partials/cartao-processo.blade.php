{{-- Cartão de um processo de receituário (visão geral do cadastro) --}}
<a href="{{ route('admin.estabelecimentos.processos.show', [$processo->estabelecimento_id, $processo->id]) }}"
   class="group block rounded-xl border border-slate-200 p-4 hover:border-blue-300 hover:shadow-md transition">
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-900 group-hover:text-blue-700 truncate">{{ $processo->tipo_nome }}</p>
            <p class="mt-0.5 text-xs font-mono text-slate-500">nº {{ $processo->numero_processo }}</p>
        </div>
        <span class="flex-shrink-0 px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 ring-inset {{ $statusProcesso[$processo->status] ?? 'bg-slate-100 text-slate-600 ring-slate-200' }}">{{ $processo->status_nome }}</span>
    </div>
    <p class="mt-3 pt-3 border-t border-slate-100 text-xs text-slate-500 flex items-center justify-between">
        Aberto em {{ $processo->created_at->format('d/m/Y') }}
        <svg class="w-4 h-4 text-slate-300 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </p>
</a>
