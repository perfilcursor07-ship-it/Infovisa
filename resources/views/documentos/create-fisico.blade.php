@extends('layouts.admin')

@section('title', 'Criar Documento Físico')

@section('content')
<div class="space-y-4" x-data="documentoFisico()">
    {{-- Cabeçalho --}}
    <div>
        <div class="flex items-center gap-2 text-xs text-gray-500 mb-2">
            <a href="{{ route('admin.documentos.index') }}" class="hover:text-gray-700">Documentos</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-700">Criar Documento Físico</span>
        </div>
        <h1 class="text-xl font-bold text-gray-900">Criar Documento Físico</h1>
        <p class="text-sm text-gray-500 mt-0.5">Upload do auto de infração entregue em loco no estabelecimento</p>
    </div>

    {{-- Card informativo --}}
    @if($estabelecimento)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <p class="text-sm font-semibold text-amber-900">Documento físico para {{ $estabelecimento->nome_fantasia ?? $estabelecimento->razao_social }}</p>
                <p class="text-xs text-amber-700 mt-1">O prazo será contado a partir do dia útil seguinte à data de entrega do documento ao estabelecimento.</p>
            </div>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <ul class="text-sm text-red-700 space-y-1">
            @foreach($errors->all() as $error)
                <li>• {{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.documentos.store-fisico') }}" method="POST" enctype="multipart/form-data" class="space-y-4"
          @submit="enviando = true">
        @csrf
        <input type="hidden" name="estabelecimento_id" value="{{ $estabelecimento->id ?? '' }}">
        @if($processo ?? null)
            <input type="hidden" name="processo_id" value="{{ $processo->id }}">
        @endif

        {{-- Seção 1: Tipo de Documento --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-white">
                <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <span class="flex items-center justify-center w-5 h-5 bg-blue-600 text-white rounded-full text-xs font-bold">1</span>
                    Tipo de Documento
                </h2>
            </div>
            <div class="p-4 space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo <span class="text-red-500">*</span></label>
                    <select name="tipo_documento_id" x-model="tipoSelecionado"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" required>
                        <option value="">Selecione o tipo de documento</option>
                        @foreach($tiposDocumento as $tipo)
                            <option value="{{ $tipo->id }}" data-tem-prazo="{{ $tipo->tem_prazo ? '1' : '0' }}" data-prazo-padrao="{{ $tipo->prazo_padrao_dias }}"
                                    data-abre-processo="{{ $tipo->abrir_processo_automaticamente && $tipo->tipo_processo_codigo ? '1' : '0' }}">
                                {{ $tipo->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if(($estabelecimento ?? null) && !($processo ?? null))
                {{-- O documento se liga ao estabelecimento pelo processo. Os tipos que abrem processo
                     sozinhos criam o seu; os demais precisam que o usuário escolha um. --}}
                <div x-show="!tipoAbreProcesso" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Processo <span class="text-red-500">*</span>
                    </label>
                    @if(($processosEstabelecimento ?? collect())->isNotEmpty())
                        <select name="processo_id" x-bind:required="!tipoAbreProcesso"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="">Selecione o processo</option>
                            @foreach(($processosEstabelecimento ?? collect()) as $p)
                                <option value="{{ $p->id }}" {{ old('processo_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->numero_processo }} — {{ $p->tipo }} ({{ $p->status }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">É o processo que liga o documento ao estabelecimento.</p>
                    @else
                        <div class="flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>
                                Este estabelecimento ainda não tem processo, e este tipo de documento não abre um
                                automaticamente. Abra um processo antes, ou marque "Abrir Processo Automaticamente"
                                em Configurações &gt; Tipos de Documento.
                            </span>
                        </div>
                    @endif
                </div>
                @endif

                @if(isset($pastasProcesso) && $pastasProcesso->isNotEmpty())
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pasta do Processo</label>
                    <select name="pasta_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Sem pasta</option>
                        @foreach($pastasProcesso as $pasta)
                            <option value="{{ $pasta->id }}">{{ $pasta->nome }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if($podeMarcarSigiloso ?? false)
                <div class="pt-3 border-t border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Documento Sigiloso</p>
                                <p class="text-xs text-gray-500">Não será visível para o estabelecimento</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="sigiloso" value="1" class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-600"></div>
                        </label>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Seção 2: Upload do PDF --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-amber-50 to-white">
                <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <span class="flex items-center justify-center w-5 h-5 bg-amber-600 text-white rounded-full text-xs font-bold">2</span>
                    Upload do Documento Físico (PDF)
                </h2>
            </div>
            <div class="p-4">
                <label for="arquivo-input" class="block cursor-pointer">
                    <div class="border-2 border-dashed border-gray-300 hover:border-amber-400 rounded-xl p-6 text-center transition">
                        <svg class="w-10 h-10 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <p class="text-sm font-medium text-gray-700" x-show="!arquivoSelecionado">Clique para selecionar o PDF do documento</p>
                        <p class="text-sm font-medium text-amber-700" x-show="arquivoSelecionado" x-text="arquivoSelecionado"></p>
                        <p class="text-xs text-gray-500 mt-1">PDF até 20MB</p>
                    </div>
                </label>
                <input type="file" id="arquivo-input" name="arquivo_fisico_pdf" accept="application/pdf" required
                       @change="arquivoSelecionado = $event.target.files[0]?.name || ''"
                       class="sr-only">
            </div>
        </div>

        {{-- Seção 3: Data de Entrega e Prazo --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-white">
                <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <span class="flex items-center justify-center w-5 h-5 bg-emerald-600 text-white rounded-full text-xs font-bold">3</span>
                    Data de Entrega e Prazo
                </h2>
            </div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Data de entrega ao estabelecimento <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="data_entrega_fisica" x-model="dataEntrega" required
                           max="{{ now()->format('Y-m-d') }}"
                           class="w-full md:w-64 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 text-sm">
                    <p class="text-xs text-gray-500 mt-1">O prazo conta a partir do dia útil seguinte a esta data.</p>
                </div>

                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center justify-between mb-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="temPrazo" class="w-4 h-4 text-emerald-600 rounded">
                            <span class="text-sm font-medium text-gray-900">Este documento possui prazo</span>
                        </label>
                    </div>

                    <div x-show="temPrazo" x-cloak class="space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Prazo em dias <span class="text-red-500">*</span></label>
                                <input type="number" name="prazo_dias" x-model="prazoDias" min="1" :required="temPrazo"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 text-sm" placeholder="Ex: 30">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Tipo de prazo <span class="text-red-500">*</span></label>
                                <select name="tipo_prazo" x-model="tipoPrazo" :required="temPrazo"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 text-sm">
                                    <option value="corridos">Dias corridos</option>
                                    <option value="uteis">Dias úteis</option>
                                </select>
                            </div>
                        </div>

                        <div x-show="dataEntrega && prazoDias" class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                            <p class="text-xs text-emerald-800">
                                <strong>Cálculo do prazo:</strong> O prazo começa no dia útil seguinte à data de entrega
                                e expira em <span x-text="prazoDias"></span> <span x-text="tipoPrazo === 'uteis' ? 'dias úteis' : 'dias corridos'"></span> depois.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Seção 4: Observações --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <span class="flex items-center justify-center w-5 h-5 bg-gray-600 text-white rounded-full text-xs font-bold">4</span>
                    Observações (opcional)
                </h2>
            </div>
            <div class="p-4">
                <textarea name="observacoes" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 text-sm"
                          placeholder="Informações adicionais sobre o documento..."></textarea>
            </div>
        </div>

        {{-- Botões --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.estabelecimentos.documentos', $estabelecimento->id ?? 0) }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition"
               :class="enviando ? 'pointer-events-none opacity-50' : ''">
                Cancelar
            </a>
            <button type="submit" :disabled="enviando"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-amber-600 hover:bg-amber-700 disabled:bg-amber-400 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg shadow-sm transition">
                <template x-if="!enviando">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </template>
                <template x-if="enviando">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </template>
                <span x-text="enviando ? 'Salvando...' : 'Salvar Documento Físico'"></span>
            </button>
        </div>
    </form>
</div>

<script>
function documentoFisico() {
    return {
        tipoSelecionado: '',
        // Tipo que abre processo sozinho não precisa que o usuário escolha um processo
        get tipoAbreProcesso() {
            const opcao = document.querySelector(
                'select[name="tipo_documento_id"] option[value="' + this.tipoSelecionado + '"]'
            );
            return opcao?.dataset.abreProcesso === '1';
        },
        arquivoSelecionado: '',
        dataEntrega: '',
        temPrazo: true,
        prazoDias: '15',
        tipoPrazo: 'corridos',
        enviando: false,
    };
}
</script>
@endsection
