{{-- Em quais tipos de processo este tipo de documento aparece ao criar documentos. Espera $tipoDocumento (opcional). --}}
@php
    $tiposProcessoDisponiveis = \App\Models\TipoProcesso::ativos()->ordenado()->get(['id', 'nome', 'codigo']);
    $escopoAtual = old('escopo_processos', $tipoDocumento->escopo_processos ?? 'todos');
    $tiposMarcados = old('tipos_processo_permitidos', $tipoDocumento->tipos_processo_permitidos ?? []) ?? [];
@endphp
<div class="border border-sky-200 rounded-lg p-4 bg-sky-50" x-data="{ escopo: @js($escopoAtual) }">
    <div class="flex items-center mb-1">
        <svg class="w-5 h-5 text-sky-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
        </svg>
        <label class="text-sm font-semibold text-gray-900">Em quais processos este documento aparece</label>
    </div>
    <p class="text-xs text-gray-600 mb-3">Define em quais tipos de processo este tipo de documento pode ser criado (tela "Criar documento" dentro do processo).</p>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <label class="flex items-start gap-2.5 p-2.5 bg-white rounded-lg border cursor-pointer transition"
               :class="escopo === 'todos' ? 'border-sky-400 ring-1 ring-sky-300' : 'border-gray-200 hover:border-sky-300'">
            <input type="radio" name="escopo_processos" value="todos" x-model="escopo" class="mt-0.5 w-4 h-4 text-sky-600 focus:ring-sky-500">
            <div>
                <span class="text-sm font-medium text-gray-900">Todos os processos</span>
                <p class="text-[11px] text-gray-500">Aparece em qualquer tipo de processo</p>
            </div>
        </label>
        <label class="flex items-start gap-2.5 p-2.5 bg-white rounded-lg border cursor-pointer transition"
               :class="escopo === 'especificos' ? 'border-sky-400 ring-1 ring-sky-300' : 'border-gray-200 hover:border-sky-300'">
            <input type="radio" name="escopo_processos" value="especificos" x-model="escopo" class="mt-0.5 w-4 h-4 text-sky-600 focus:ring-sky-500">
            <div>
                <span class="text-sm font-medium text-gray-900">Processos específicos</span>
                <p class="text-[11px] text-gray-500">Escolha os tipos de processo</p>
            </div>
        </label>
        <label class="flex items-start gap-2.5 p-2.5 bg-white rounded-lg border cursor-pointer transition"
               :class="escopo === 'nenhum' ? 'border-sky-400 ring-1 ring-sky-300' : 'border-gray-200 hover:border-sky-300'">
            <input type="radio" name="escopo_processos" value="nenhum" x-model="escopo" class="mt-0.5 w-4 h-4 text-sky-600 focus:ring-sky-500">
            <div>
                <span class="text-sm font-medium text-gray-900">Nenhum processo</span>
                <p class="text-[11px] text-gray-500">Só na criação fora de processo</p>
            </div>
        </label>
    </div>

    <div x-show="escopo === 'especificos'" x-cloak class="mt-3">
        <p class="text-xs font-medium text-gray-700 mb-2">Tipos de processo em que o documento aparece:</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-60 overflow-y-auto p-2 bg-white border border-gray-200 rounded-lg">
            @forelse($tiposProcessoDisponiveis as $tp)
                <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-sky-50 cursor-pointer">
                    <input type="checkbox" name="tipos_processo_permitidos[]" value="{{ $tp->codigo }}"
                           :disabled="escopo !== 'especificos'"
                           @checked(in_array($tp->codigo, $tiposMarcados, true))
                           class="w-4 h-4 text-sky-600 rounded focus:ring-sky-500">
                    <span class="text-sm text-gray-800">{{ $tp->nome }}</span>
                </label>
            @empty
                <p class="text-xs text-gray-500 px-2 py-1">Nenhum tipo de processo ativo cadastrado.</p>
            @endforelse
        </div>
        @error('tipos_processo_permitidos')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <p x-show="escopo === 'nenhum'" x-cloak class="mt-3 text-[11px] text-sky-800 bg-white border border-sky-200 rounded-lg px-3 py-2">
        Não aparece ao criar documento dentro de processos. Continua disponível na criação fora de processo
        (ex.: documento que abre processo automaticamente, como o Auto de Infração).
    </p>
</div>
