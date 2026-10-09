@extends('layouts.admin')

@section('title', 'Processos')
@section('page-title', 'Processos')

@section('content')
<div class="max-w-8xl mx-auto" x-data="{ 
    modalAberto: {{ session('error') ? 'true' : 'false' }}, 
    tipoSelecionado: '' 
}">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.estabelecimentos.show', $estabelecimento->id) }}" 
                   class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 hover:text-gray-900 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Processos</h1>
                    <p class="text-sm text-gray-600 mt-1">{{ $estabelecimento->nome_fantasia }}</p>
                </div>
            </div>
            <button @click="modalAberto = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Adicionar Processo
            </button>
        </div>
    </div>

    {{-- Modal para Adicionar Processo --}}
    <template x-teleport="body">
        <div x-show="modalAberto" 
             x-cloak
             @keydown.escape.window="modalAberto = false"
             style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 9999;">
            
            {{-- Overlay --}}
            <div @click="modalAberto = false"
                 style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5);"></div>
            
            {{-- Modal Content --}}
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 100%; max-width: 500px; padding: 0 1rem;">
                <div class="bg-white rounded-xl shadow-2xl p-6" @click.stop>
                    {{-- Close Button --}}
                    <button @click="modalAberto = false"
                            class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    {{-- Header --}}
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-gray-900">Adicionar Novo Processo</h3>
                        <p class="text-sm text-gray-600 mt-1">Selecione o tipo de processo que deseja abrir</p>
                    </div>

                    {{-- Erro dentro do modal --}}
                    @if(session('error'))
                        <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm text-red-800">{{ session('error') }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Form --}}
                    <form method="POST" action="{{ route('admin.estabelecimentos.processos.store', $estabelecimento->id) }}">
                        @csrf
                        
                        {{-- Dropdown Tipo de Processo --}}
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tipo de Processo <span class="text-red-500">*</span>
                            </label>
                            <select name="tipo" 
                                    x-model="tipoSelecionado"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900">
                                <option value="">Selecione o tipo...</option>
                                @foreach($tiposProcesso as $tipo)
                                    <option value="{{ $tipo->codigo }}">
                                        {{ $tipo->nome }}
                                        @if($tipo->anual)
                                            <span class="text-xs">(Anual)</span>
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">
                                @if($tiposProcesso->isEmpty())
                                    Nenhum tipo de processo disponível. Configure em Configurações > Tipos de Processo.
                                @else
                                    Selecione o tipo de processo que deseja abrir
                                @endif
                            </p>
                        </div>

                        {{-- Observações --}}
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Observações (Opcional)
                            </label>
                            <textarea name="observacoes" 
                                      rows="3" 
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none text-gray-900"
                                      placeholder="Adicione observações sobre este processo..."></textarea>
                        </div>

                        {{-- Info --}}
                        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-xs text-blue-700">
                                    O número do processo será gerado automaticamente no formato {{ date('Y') }}/XXXXX
                                </p>
                            </div>
                        </div>

                        {{-- Buttons --}}
                        <div class="flex items-center gap-3">
                            <button type="button"
                                    @click="modalAberto = false"
                                    class="flex-1 px-4 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit"
                                    :disabled="!tipoSelecionado"
                                    :class="tipoSelecionado ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-300 cursor-not-allowed'"
                                    class="flex-1 px-4 py-2.5 text-sm font-semibold text-white rounded-lg transition-colors">
                                Criar Processo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    {{-- Mensagens de Sucesso/Erro --}}
    @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Lista de Processos --}}
    @if($processos->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum processo cadastrado</h3>
            <p class="text-sm text-gray-600 mb-6">Comece adicionando o primeiro processo para este estabelecimento.</p>
            <button @click="modalAberto = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Adicionar Primeiro Processo
            </button>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($processos as $processo)
                <a href="{{ route('admin.estabelecimentos.processos.show', [$estabelecimento->id, $processo->id]) }}" 
                   class="block bg-white rounded-lg shadow-md border border-gray-100 hover:shadow-lg transition-all duration-200 hover:-translate-y-1 cursor-pointer">
                    <div class="p-5">
                        {{-- Header do Card --}}
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                        @if($processo->status_cor === 'blue') bg-blue-100 text-blue-800
                                        @elseif($processo->status_cor === 'yellow') bg-yellow-100 text-yellow-800
                                        @elseif($processo->status_cor === 'orange') bg-orange-100 text-orange-800
                                        @elseif($processo->status_cor === 'green') bg-green-100 text-green-800
                                        @elseif($processo->status_cor === 'red') bg-red-100 text-red-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ $processo->status_nome }}
                                    </span>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ $processo->tipo_nome }}</h3>
                                <p class="text-lg font-bold text-blue-600 mt-1">{{ $processo->numero_processo }}</p>
                            </div>
                            <div class="relative" x-data="{ open: false }" @click.stop>
                                <button @click.stop="open = !open" 
                                        class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                    </svg>
                                </button>
                                <div x-show="open" 
                                     @click.away="open = false"
                                     x-cloak
                                     class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10">
                                    {{-- Excluir: somente administrador, confirmando com a senha da assinatura na tela do processo --}}
                                    <a href="{{ route('admin.estabelecimentos.processos.show', [$estabelecimento->id, $processo->id]) }}"
                                       class="block w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                        Abrir processo
                                    </a>
                                    @if(auth('interno')->user()->isAdmin())
                                        <a href="{{ route('admin.estabelecimentos.processos.show', [$estabelecimento->id, $processo->id, 'excluir' => 1]) }}"
                                           class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                            Excluir processo
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Detalhes --}}
                        <div class="space-y-2 border-t border-gray-100 pt-3 mt-3">
                            <div class="flex items-center gap-2 text-xs">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-gray-500">Criado em:</span>
                                <span class="font-medium text-gray-700">{{ $processo->created_at->format('d/m/Y') }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span class="text-gray-500">Criado por:</span>
                                <span class="font-medium text-gray-700 truncate">{{ $processo->usuario->nome ?? 'N/A' }}</span>
                            </div>
                        </div>

                        {{-- Observações (se houver) --}}
                        @if($processo->observacoes)
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <p class="text-xs text-gray-400 uppercase font-semibold mb-1 tracking-wide">Observações</p>
                                <p class="text-xs text-gray-600 line-clamp-2">{{ $processo->observacoes }}</p>
                            </div>
                        @endif

                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
