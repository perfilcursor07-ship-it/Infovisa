@extends('layouts.admin')

@section('title', 'Criar Novo Documento')

@php
    // Desativa o assistente IA principal nesta página (já tem o assistente de redação)
    $desativarAssistenteIA = true;
@endphp

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    /* Variáveis dinâmicas no editor: mesma aparência do texto normal */
    .variavel-dinamica {
        background: transparent !important;
        color: inherit !important;
        font-family: inherit !important;
        font-size: inherit !important;
        padding: 0 !important;
        border-radius: 0 !important;
    }

    /* Estilos legados do editor */
    .tox-tinymce {
        border-radius: 0 0 0.5rem 0.5rem !important;
    }
    /* Forçar cursor de texto visível dentro do editor */
    .tox-tinymce .tox-edit-area__iframe {
        cursor: text !important;
    }
    .tox .tox-edit-area {
        cursor: text !important;
    }
    .tox .tox-edit-area__iframe {
        cursor: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='18' viewBox='0 0 12 18'%3E%3Cline x1='6' y1='1' x2='6' y2='17' stroke='white' stroke-width='3' stroke-linecap='round'/%3E%3Cline x1='3' y1='1' x2='9' y2='1' stroke='white' stroke-width='3' stroke-linecap='round'/%3E%3Cline x1='3' y1='17' x2='9' y2='17' stroke='white' stroke-width='3' stroke-linecap='round'/%3E%3Cline x1='6' y1='1' x2='6' y2='17' stroke='%23000000' stroke-width='2.5' stroke-linecap='round'/%3E%3Cline x1='3' y1='1' x2='9' y2='1' stroke='%23000000' stroke-width='2.5' stroke-linecap='round'/%3E%3Cline x1='3' y1='17' x2='9' y2='17' stroke='%23000000' stroke-width='2.5' stroke-linecap='round'/%3E%3C/svg%3E") 6 9, text !important;
    }
    .tox .tox-edit-area__iframe {
        background: #fff !important;
    }

    /* Evita conflito visual de rolagem entre área do editor e página */
    .tox .tox-edit-area {
        overflow: hidden !important;
    }

    .documento-conteudo-preservado p,
    .documento-conteudo-preservado .MsoNormal,
    .documento-conteudo-preservado div,
    .documento-conteudo-preservado li,
    .documento-conteudo-preservado h1,
    .documento-conteudo-preservado h2,
    .documento-conteudo-preservado h3,
    .documento-conteudo-preservado h4,
    .documento-conteudo-preservado h5,
    .documento-conteudo-preservado h6 {
        margin: 0;
        padding: 0;
        line-height: 1.6;
        word-break: break-word;
    }

    .documento-conteudo-preservado ul,
    .documento-conteudo-preservado ol {
        margin: 0;
        padding-left: 1.5em;
    }
</style>
@endpush

@section('content')
@if(isset($processo))
    <meta name="processo-id" content="{{ $processo->id }}">
    <meta name="estabelecimento-id" content="{{ $processo->estabelecimento_id }}">
@elseif(isset($estabelecimento) && $estabelecimento)
    <meta name="estabelecimento-id" content="{{ $estabelecimento->id }}">
@endif

<div class="min-h-screen bg-gray-50" x-data="documentoEditor()" @keydown.escape="modalConfirmarFinalizacao = false">
    <div class="max-w-8xl mx-auto px-4 py-8">
        <div x-show="popupSemUsuarioExternoVisivel"
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div class="absolute inset-0 bg-black/20"></div>
            <div class="relative w-full max-w-2xl overflow-hidden rounded-none border border-amber-200 bg-white shadow-2xl">
                <div class="h-1.5 bg-amber-500"></div>
                <div class="p-6">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-100">
                            <svg class="h-5 w-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-sm font-semibold text-amber-900">Não existe usuário vinculado ao estabelecimento para visualizar este documento com prazo.</p>
                                <button type="button" @click="fecharPopupSemUsuarioExterno()" class="text-gray-400 hover:text-gray-600 transition">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-2 text-sm leading-relaxed text-gray-700">
                                O técnico ainda pode criar o documento, mas precisa ter ciência de que não há usuário externo vinculado para visualizar o documento ou iniciar a contagem de prazo pela ciência do estabelecimento.
                            </p>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                @if(isset($processo) && $processo->estabelecimento_id)
                                    <a href="{{ route('admin.estabelecimentos.usuarios.index', $processo->estabelecimento_id) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-amber-800 underline underline-offset-2 hover:text-amber-700">
                                        Ver usuários vinculados do estabelecimento
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                @endif
                                <span class="text-xs font-medium text-amber-700" x-text="`Fechando em ${popupSemUsuarioExternoSegundos}s`"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mensagens de Erro --}}
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-red-800 mb-2">Erro ao salvar documento:</h3>
                        <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        {{-- Aviso de outro usuário editando --}}
        <div x-show="outroUsuarioEditando" 
             x-transition
             x-cloak
             class="mb-6 bg-amber-50 border-l-4 border-amber-500 p-4 rounded-lg shadow-md">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-amber-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-bold text-amber-800">Documento em edição por outro usuário</h3>
                    <p class="mt-1 text-sm text-amber-700">
                        <span class="font-semibold" x-text="nomeUsuarioEditando"></span> está editando este documento no momento.
                        <br>
                        <span class="text-amber-600">Aguarde o usuário finalizar as alterações antes de salvar para evitar conflitos.</span>
                    </p>
                    <div class="mt-2 flex items-center gap-2 text-xs text-amber-600">
                        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Verificando a cada 10 segundos...
                    </div>
                </div>
            </div>
        </div>

        {{-- Mensagem de recuperação de dados --}}
        <div x-show="dadosRecuperados" 
             x-transition
             class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
            <div class="flex items-start justify-between">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-blue-800 mb-1">Rascunho recuperado</h3>
                        <p class="text-sm text-blue-700">Seus dados foram recuperados automaticamente. Continue editando de onde parou.</p>
                    </div>
                </div>
                <button @click="dadosRecuperados = false" class="text-blue-500 hover:text-blue-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Header com Breadcrumb --}}
        <div class="mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-600 mb-3">
                @if(isset($processo))
                    <a href="{{ route('admin.estabelecimentos.processos.show', [$processo->estabelecimento_id, $processo->id]) }}" class="hover:text-blue-600 transition">
                        Processo {{ $processo->numero_processo }}
                    </a>
                @elseif(isset($estabelecimento) && $estabelecimento)
                    <a href="{{ route('admin.estabelecimentos.show', $estabelecimento->id) }}" class="hover:text-blue-600 transition">
                        {{ $estabelecimento->nome_fantasia ?? $estabelecimento->razao_social }}
                    </a>
                    <span class="text-gray-400">/</span>
                    <span class="text-gray-900">Novo Documento</span>
                @else
                    <a href="{{ route('admin.documentos.index') }}" class="hover:text-blue-600 transition">
                        Documentos
                    </a>
                @endif
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-gray-900 font-medium">Novo Documento</span>
            </div>

            @if(isset($estabelecimento) && $estabelecimento && !isset($processo))
                <div class="mt-3 p-3 bg-indigo-50 border border-indigo-200 rounded-lg">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-indigo-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-indigo-800">Documento para {{ $estabelecimento->nome_fantasia ?? $estabelecimento->razao_social }}</p>
                            <p class="text-xs text-indigo-700 mt-0.5">Se o tipo de documento selecionado estiver configurado para abrir processo automaticamente, um novo processo será criado e o documento será vinculado a ele.</p>
                        </div>
                    </div>
                </div>
            @endif
            
            @if(isset($processosSelecionados) && $processosSelecionados->count() > 1)
                <div class="mt-3 p-3 bg-purple-50 border border-purple-200 rounded-lg">
                    <p class="text-sm font-semibold text-purple-800 mb-1">Criação em lote para múltiplos processos</p>
                    <p class="text-xs text-purple-700 mb-2">Este documento será criado em {{ $processosSelecionados->count() }} processos selecionados.</p>
                    <div class="space-y-1">
                        @foreach($processosSelecionados as $procSel)
                            <p class="text-xs text-purple-700">
                                • {{ $procSel->numero_processo ?? ('Processo #' . $procSel->id) }}
                                @if($procSel->estabelecimento)
                                    — {{ $procSel->estabelecimento->nome_fantasia ?? $procSel->estabelecimento->razao_social }}
                                @endif
                            </p>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(isset($processo))
                <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="font-medium">{{ $processo->estabelecimento->nome_fantasia ?? $processo->estabelecimento->razao_social }}</span>
                </div>
            @endif
        </div>

    <form id="formDocumento" method="POST" action="{{ route('admin.documentos.store') }}" @submit="handleSubmit">
        @csrf
        
        @if(isset($processosSelecionados) && $processosSelecionados->count() > 0)
            @foreach($processosSelecionados as $procSel)
                <input type="hidden" name="processos_ids[]" value="{{ $procSel->id }}">
            @endforeach
        @elseif(isset($processo))
            <input type="hidden" name="processo_id" value="{{ $processo->id }}">
        @endif

        @if(isset($estabelecimento) && $estabelecimento && !isset($processo))
            <input type="hidden" name="estabelecimento_id" value="{{ $estabelecimento->id }}">
        @endif

        @if(isset($osId) && $osId)
            <input type="hidden" name="os_id" value="{{ $osId }}">
        @endif

        @if(isset($atividadeIndex) && $atividadeIndex !== null)
            <input type="hidden" name="atividade_index" value="{{ $atividadeIndex }}">
        @endif
        
        {{-- Campo hidden para a ação (rascunho ou finalizar) --}}
        <input type="hidden" name="acao" id="inputAcao" value="rascunho">
        <input type="hidden" name="confirmar_sem_usuario_externo" id="inputConfirmarSemUsuarioExterno" value="{{ old('confirmar_sem_usuario_externo', 0) }}">
        
        {{-- Campo hidden para o conteúdo do editor --}}
        <input type="hidden" name="conteudo" x-model="conteudo">

        {{-- Seção: Tipo de Documento --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-3">
            <div class="px-3 py-2 bg-gradient-to-r from-blue-50 to-white border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                    <span class="flex items-center justify-center w-4 h-4 bg-blue-600 text-white rounded-full text-xs font-bold">1</span>
                    Tipo de Documento
                </h2>
            </div>
            <div class="p-3">
                <select name="tipo_documento_id" 
                        x-model="tipoSelecionado"
                        @change="onTipoSelecionadoChange($event.target.value)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                        required>
                    <option value="">Selecione o tipo de documento</option>
                    @foreach($tiposDocumento as $tipo)
                        <option value="{{ $tipo->id }}">{{ $tipo->nome }}</option>
                    @endforeach
                </select>
                <p class="text-sm text-gray-500 mt-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Ao selecionar um tipo, o modelo predefinido será carregado automaticamente
                </p>

                {{-- Subcategoria (aparece quando o tipo possui subcategorias cadastradas) --}}
                <div x-show="subcategoriasDoTipo.length > 0" x-cloak class="mt-3 pt-3 border-t border-dashed border-purple-200">
                    <label class="block text-xs font-semibold text-purple-700 mb-1.5 uppercase tracking-wide">
                        Subcategoria
                    </label>
                    <select name="subcategoria_id"
                            x-model="subcategoriaSelecionada"
                            @change="carregarModelos(tipoSelecionado); salvarAutomaticamente()"
                            class="w-full px-3 py-2 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm bg-purple-50/30">
                        <option value="">Nenhuma (usar modelo genérico)</option>
                        <template x-for="sub in subcategoriasDoTipo" :key="sub.id">
                            <option :value="sub.id" x-text="sub.nome"></option>
                        </template>
                    </select>
                    <p class="text-xs text-purple-600 mt-1.5">
                        Selecione a subcategoria para carregar o modelo específico (ex: provisório, administrativo, definitivo).
                    </p>
                </div>

                @if(isset($pastasProcesso) && $pastasProcesso->isNotEmpty())
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <label for="pasta_id" class="block text-sm font-medium text-gray-700 mb-2">Pasta do Processo</label>
                        <select name="pasta_id"
                                id="pasta_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">Todos (sem pasta)</option>
                            @foreach($pastasProcesso as $pasta)
                                <option value="{{ $pasta->id }}" {{ (string) old('pasta_id') === (string) $pasta->id ? 'selected' : '' }}>
                                    {{ $pasta->nome }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1.5">Opcional: selecione a pasta para o documento já ser criado no agrupamento correto.</p>
                    </div>
                @endif

                {{-- Toggle Sigiloso (dentro da seção tipo de documento) --}}
                @if($podeMarcarSigiloso ?? false)
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Documento Sigiloso</p>
                                <p class="text-xs text-gray-500">Não será visível para o estabelecimento</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="sigiloso" value="1" x-model="sigiloso" class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-600"></div>
                        </label>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Seção: Editor de Conteúdo --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-visible mb-3">
            <div class="px-3 py-2 bg-gradient-to-r from-green-50 to-white border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                    <span class="flex items-center justify-center w-4 h-4 bg-green-600 text-white rounded-full text-xs font-bold">2</span>
                    Conteúdo do Documento
                </h2>
            </div>
            <div class="p-3">

                {{-- Botão Variáveis Dinâmicas (acima do editor) --}}
                <div class="mb-2 flex items-center gap-2 flex-wrap relative" style="min-height: 24px;">
                    <div class="relative" x-data="{ showVarsDropdown: false }">
                        <button type="button" @click="showVarsDropdown = !showVarsDropdown" class="flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-300 rounded hover:bg-amber-100 hover:shadow transition-all" title="Inserir variável dinâmica">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            Inserir Variáveis
                        </button>
                        <div x-show="showVarsDropdown" @click.away="showVarsDropdown = false" 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             class="absolute z-50 mt-1 w-80 bg-white border border-gray-300 rounded-lg shadow-xl p-2 max-h-80 overflow-y-auto" style="display: none;">
                            <p class="text-xs text-gray-500 mb-2 px-2 font-medium">Clique para inserir no documento:</p>
                            
                            {{-- Estabelecimento --}}
                            <div class="mb-2">
                                <p class="text-xs font-bold text-gray-700 px-2 py-1 bg-gray-100 rounded">📍 Estabelecimento</p>
                                <div class="space-y-0.5 mt-1">
                                    @foreach([
                                        ['{estabelecimento_nome}', 'Nome/Fantasia'],
                                        ['{estabelecimento_razao_social}', 'Razão Social'],
                                        ['{estabelecimento_cnpj}', 'CNPJ'],
                                        ['{estabelecimento_cpf}', 'CPF'],
                                        ['{estabelecimento_endereco}', 'Endereço completo'],
                                        ['{estabelecimento_bairro}', 'Bairro'],
                                        ['{estabelecimento_cidade}', 'Cidade'],
                                        ['{municipio}', 'Município'],
                                        ['{estabelecimento_telefone}', 'Telefone'],
                                        ['{estabelecimento_email}', 'E-mail'],
                                        ['{atividades}', 'Lista de atividades'],
                                    ] as $var)
                                    <button type="button" @click="inserirVariavel('{{ $var[0] }}'); showVarsDropdown = false" class="w-full text-left px-2 py-1 text-sm hover:bg-amber-50 rounded">
                                        <span class="font-mono text-amber-600 text-xs">{{ $var[0] }}</span>
                                        <span class="text-gray-500 text-xs ml-1">- {{ $var[1] }}</span>
                                    </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Responsável Legal --}}
                            <div class="mb-2">
                                <p class="text-xs font-bold text-gray-700 px-2 py-1 bg-gray-100 rounded">👤 Responsável Legal</p>
                                <div class="space-y-0.5 mt-1">
                                    @foreach([
                                        ['{responsavel_legal_nome}', 'Nome'],
                                        ['{responsavel_legal_cpf}', 'CPF'],
                                        ['{responsavel_legal_email}', 'E-mail'],
                                        ['{responsavel_legal_telefone}', 'Telefone'],
                                    ] as $var)
                                    <button type="button" @click="inserirVariavel('{{ $var[0] }}'); showVarsDropdown = false" class="w-full text-left px-2 py-1 text-sm hover:bg-amber-50 rounded">
                                        <span class="font-mono text-amber-600 text-xs">{{ $var[0] }}</span>
                                        <span class="text-gray-500 text-xs ml-1">- {{ $var[1] }}</span>
                                    </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Responsável Técnico --}}
                            <div class="mb-2">
                                <p class="text-xs font-bold text-gray-700 px-2 py-1 bg-gray-100 rounded">👤 Responsável Técnico</p>
                                <div class="space-y-0.5 mt-1">
                                    @foreach([
                                        ['{responsavel_tecnico_nome}', 'Nome'],
                                        ['{responsavel_tecnico_cpf}', 'CPF'],
                                        ['{responsavel_tecnico_email}', 'E-mail'],
                                        ['{responsavel_tecnico_telefone}', 'Telefone'],
                                        ['{responsavel_tecnico_conselho}', 'Nº Conselho'],
                                    ] as $var)
                                    <button type="button" @click="inserirVariavel('{{ $var[0] }}'); showVarsDropdown = false" class="w-full text-left px-2 py-1 text-sm hover:bg-amber-50 rounded">
                                        <span class="font-mono text-amber-600 text-xs">{{ $var[0] }}</span>
                                        <span class="text-gray-500 text-xs ml-1">- {{ $var[1] }}</span>
                                    </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Processo --}}
                            <div class="mb-2">
                                <p class="text-xs font-bold text-gray-700 px-2 py-1 bg-gray-100 rounded">📄 Processo</p>
                                <div class="space-y-0.5 mt-1">
                                    @foreach([
                                        ['{processo_numero}', 'Número'],
                                        ['{processo_tipo}', 'Tipo'],
                                        ['{processo_data_criacao}', 'Data de criação'],
                                    ] as $var)
                                    <button type="button" @click="inserirVariavel('{{ $var[0] }}'); showVarsDropdown = false" class="w-full text-left px-2 py-1 text-sm hover:bg-amber-50 rounded">
                                        <span class="font-mono text-amber-600 text-xs">{{ $var[0] }}</span>
                                        <span class="text-gray-500 text-xs ml-1">- {{ $var[1] }}</span>
                                    </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Data --}}
                            <div>
                                <p class="text-xs font-bold text-gray-700 px-2 py-1 bg-gray-100 rounded">📅 Data</p>
                                <div class="space-y-0.5 mt-1">
                                    @foreach([
                                        ['{data_atual}', 'Data atual (dd/mm/aaaa)'],
                                        ['{data_extenso}', 'Data por extenso'],
                                        ['{data_extenso_maiusculo}', 'Data por extenso MAIÚSCULO'],
                                        ['{ano_atual}', 'Ano atual'],
                                    ] as $var)
                                    <button type="button" @click="inserirVariavel('{{ $var[0] }}'); showVarsDropdown = false" class="w-full text-left px-2 py-1 text-sm hover:bg-amber-50 rounded">
                                        <span class="font-mono text-amber-600 text-xs">{{ $var[0] }}</span>
                                        <span class="text-gray-500 text-xs ml-1">- {{ $var[1] }}</span>
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <span x-show="salvandoAuto" x-transition.opacity class="text-sm text-green-600 flex items-center gap-1.5 font-medium absolute right-0 top-0">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Salvando...
                    </span>
                </div>

                {{-- Logomarca do Documento --}}
                @if(isset($logomarca) && $logomarca)
                    <div class="border border-gray-300 bg-gradient-to-b from-blue-50 to-white p-4 flex items-center justify-between rounded-t-lg">
                        <div class="flex items-center gap-3">
                            <img src="{{ asset($logomarca) }}" 
                                 alt="Logomarca" 
                                 class="h-16 w-auto object-contain">
                            <div class="text-xs text-gray-600">
                                <p class="font-semibold text-gray-800">Logomarca do Documento</p>
                                <p class="text-gray-500">
                                    @if(isset($processo) && $processo->estabelecimento)
                                        @if($processo->estabelecimento->isCompetenciaEstadual())
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3 h-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                                </svg>
                                                Competência Estadual
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                </svg>
                                                Município: {{ $processo->estabelecimento->municipioRelacionado->nome ?? 'N/A' }}
                                            </span>
                                        @endif
                                    @else
                                        Logomarca padrão
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 text-right">
                            <p class="font-medium">Esta logomarca aparecerá</p>
                            <p>no documento final</p>
                        </div>
                    </div>
                @endif

                <!-- Editor de documento -->
                <textarea id="editor-tinymce" style="visibility: hidden;"></textarea>
                <input type="hidden" name="conteudo" x-model="conteudo">

            </div>
        </div>

        @include('documentos.partials.itens-atendimento-form')

        {{-- Seção: Prazo/Validade (Condicional) --}}
        <div x-show="temPrazo" 
             x-cloak
             class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-3">
            <div class="px-3 py-2 bg-gradient-to-r from-blue-50 to-white border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                        <span class="flex items-center justify-center w-4 h-4 bg-blue-600 text-white rounded-full text-xs font-bold">3</span>
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Prazo/Validade do Documento
                    </h2>
                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">Obrigatório</span>
                </div>
            </div>
            <div class="p-3">
                <div class="bg-blue-50 border-l-4 border-blue-400 p-3 mb-3 rounded">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm text-blue-800">
                            <p class="font-semibold mb-1">Este tipo de documento possui prazo de validade</p>
                            <p>Defina o prazo em dias. A data de vencimento será calculada automaticamente a partir da data de criação.</p>
                        </div>
                    </div>
                </div>

                @if(($processosSemUsuarioExternoCount ?? 0) > 0)
                    <div class="bg-amber-50 border-l-4 border-amber-500 p-3 mb-3 rounded-lg">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div class="text-sm text-amber-900">
                                <p class="font-semibold mb-1">Não existe usuário vinculado ao estabelecimento para visualizar este documento com prazo.</p>
                                <p class="text-xs text-amber-800">
                                    O técnico ainda pode criar o documento, mas precisa ter ciência de que não há usuário externo vinculado para visualizar o documento ou iniciar a contagem de prazo pela ciência do estabelecimento.
                                </p>
                                @if(isset($processo) && $processo->estabelecimento_id)
                                    <a href="{{ route('admin.estabelecimentos.usuarios.index', $processo->estabelecimento_id) }}" class="inline-flex items-center gap-1 mt-2 text-xs font-semibold text-amber-900 hover:text-amber-700 underline underline-offset-2">
                                        Ver usuários vinculados do estabelecimento
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Aviso sobre contagem de prazo (será exibido dinamicamente via JavaScript) --}}
                <div id="aviso-prazo-container" style="display: none;">
                    {{-- Aviso para documentos de notificação/fiscalização --}}
                    <div id="aviso-notificacao" class="bg-amber-50 border-l-4 border-amber-500 p-3 mb-3 rounded-lg" style="display: none;">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div class="text-sm text-amber-800">
                                <p class="font-semibold">📋 Contagem de Prazo (§1º)</p>
                                <p class="text-xs text-amber-700 mt-1">
                                    O prazo inicia quando o estabelecimento <strong>visualizar o documento</strong> ou após <strong>5 dias úteis da última assinatura</strong> (o que ocorrer primeiro).
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Aviso para documentos de licenciamento (Alvará, etc) --}}
                    <div id="aviso-licenciamento" class="bg-blue-50 border-l-4 border-blue-400 p-3 mb-3 rounded-lg" style="display: none;">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm text-blue-800">
                                <p class="font-semibold">📅 Prazo Fixo</p>
                                <p class="text-xs text-blue-700 mt-1">
                                    O prazo é contado a partir da <strong>data de criação</strong> do documento.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="prazo_dias" class="block text-sm font-medium text-gray-700 mb-2">
                            Prazo em Dias <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-2 mb-3">
                            <input type="number" 
                                   name="prazo_dias" 
                                   id="prazo_dias"
                                   x-model="prazoDias"
                                   @input="calcularDataVencimento()"
                                   min="1"
                                   :required="temPrazo"
                                   class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                   placeholder="Ex: 365">
                            <span class="text-sm text-gray-600">dias</span>
                        </div>
                        <p class="mb-3 text-xs text-gray-500">
                            Exemplos: Alvará (365 dias), Notificação (30 dias)
                        </p>

                        {{-- Tipo de Prazo --}}
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo de Prazo <span class="text-red-500">*</span>
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center cursor-pointer p-2 border border-gray-300 rounded-lg hover:bg-blue-50 transition">
                                <input type="radio" 
                                       name="tipo_prazo" 
                                       value="corridos" 
                                       x-model="tipoPrazo"
                                       @change="calcularDataVencimento()"
                                       :required="temPrazo"
                                       class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-xs text-gray-700">
                                    <strong>Dias Corridos</strong> - Todos os dias
                                </span>
                            </label>
                            <label class="flex items-center cursor-pointer p-2 border border-gray-300 rounded-lg hover:bg-blue-50 transition">
                                <input type="radio" 
                                       name="tipo_prazo" 
                                       value="uteis" 
                                       x-model="tipoPrazo"
                                       @change="calcularDataVencimento()"
                                       :required="temPrazo"
                                       class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-xs text-gray-700">
                                    <strong>Dias Úteis</strong> - Exclui finais de semana/feriados
                                </span>
                            </label>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">
                            💡 Você pode ajustar considerando feriados municipais específicos
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <span x-show="!isNotificacao">Data de Vencimento Prevista</span>
                            <span x-show="isNotificacao">Vencimento Estimado (5º dia útil)</span>
                        </label>
                        <div class="flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-lg" :class="isNotificacao ? 'bg-amber-50 border-amber-300' : 'bg-gray-50'">
                            <svg class="w-5 h-5" :class="isNotificacao ? 'text-amber-500' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm font-medium" :class="isNotificacao ? 'text-amber-800' : 'text-gray-900'" x-text="dataVencimentoFormatada || 'Informe o prazo'"></span>
                        </div>
                        <p class="mt-1 text-xs" :class="isNotificacao ? 'text-amber-600' : 'text-gray-500'">
                            <span x-show="!isNotificacao">Calculada automaticamente baseada no tipo de prazo</span>
                            <span x-show="isNotificacao">⚠️ Data estimada considerando início no 5º dia útil. Pode ser antecipada se o estabelecimento visualizar antes.</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Seção: Assinaturas Digitais --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-3">
            <div class="px-3 py-2 bg-gradient-to-r from-purple-50 to-white border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                        <span class="flex items-center justify-center w-4 h-4 bg-purple-600 text-white rounded-full text-xs font-bold" x-text="temPrazo ? '4' : '3'"></span>
                        Assinaturas Digitais
                    </h2>
                    <span class="px-1.5 py-0.5 bg-red-100 text-red-700 text-xs font-semibold rounded-full">Obrigatório</span>
                </div>
            </div>
            <div class="p-3">
                @include('documentos.partials.selecao-assinantes', [
                    'usuariosInternos' => $usuariosInternos,
                    'assinantesSelecionados' => old('assinaturas', $assinaturasPreSelecionadas ?? []),
                ])
            </div>
        </div>

        {{-- Botões de Ação --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex gap-2 w-full sm:w-auto">
                    @if(isset($processo))
                        <a href="{{ route('admin.estabelecimentos.processos.show', [$processo->estabelecimento_id, $processo->id]) }}" 
                           class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Voltar
                        </a>
                    @else
                        <a href="{{ route('admin.documentos.index') }}" 
                           class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Voltar
                        </a>
                    @endif
                    
                    {{-- Botão Pré-visualizar --}}
                    <button type="button" 
                            @click="modalPreview = true"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-medium text-blue-700 bg-blue-50 border-2 border-blue-200 rounded-lg hover:bg-blue-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Pré-visualizar
                    </button>
                </div>

                <div class="flex gap-2 w-full sm:w-auto">
                    <button type="submit" 
                            name="acao" 
                            value="rascunho"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        Salvar Rascunho
                    </button>
                    
                    <button type="button"
                            @click="modalConfirmarFinalizacao = true"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg hover:from-blue-700 hover:to-blue-800 shadow-lg hover:shadow-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Finalizar Documento
                    </button>
                </div>
            </div>
        </div>
    </form>
    
    {{-- Modal de Pré-visualização --}}
    <div x-show="modalPreview" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            {{-- Overlay --}}
            <div x-show="modalPreview"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="modalPreview = false"
                 class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>

            {{-- Modal Content --}}
            <div x-show="modalPreview"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block w-full max-w-5xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-lg shadow-xl">
                
                {{-- Header --}}
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Pré-visualização do Documento
                        </h3>
                        <button @click="modalPreview = false" 
                                class="text-white hover:text-gray-200 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="px-6 py-6 max-h-[70vh] overflow-y-auto">
                    {{-- Informações do Documento --}}
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs font-semibold text-gray-600 mb-1">Tipo de Documento</p>
                                <p class="text-sm text-gray-900" x-text="tipoSelecionado ? document.querySelector('select[name=tipo_documento_id] option[value=\'' + tipoSelecionado + '\']')?.text : 'Não selecionado'"></p>
                            </div>
                            @if(isset($processo))
                            <div>
                                <p class="text-xs font-semibold text-gray-600 mb-1">Processo Vinculado</p>
                                <p class="text-sm text-gray-900">{{ $processo->numero_processo }}</p>
                            </div>
                            @endif
                            <div>
                                <p class="text-xs font-semibold text-gray-600 mb-1">Documento Sigiloso</p>
                                <p class="text-sm text-gray-900" x-text="sigiloso ? 'Sim' : 'Não'"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-600 mb-1">Palavras</p>
                                <p class="text-sm text-gray-900" x-text="contarPalavras()"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Conteúdo do Documento --}}
                    <div class="max-w-none bg-white p-6 rounded-lg border border-gray-200 documento-conteudo-preservado">
                        <div x-html="conteudo || '<p class=\'text-gray-400 italic\'>Nenhum conteúdo digitado ainda...</p>'"></div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button @click="modalPreview = false"
                            class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal de Confirmação para Finalizar Documento --}}
<div x-show="modalConfirmarFinalizacao" 
     class="fixed inset-0 z-50 flex items-center justify-center"
     style="display: none;">
    {{-- Backdrop --}}
    <div x-show="modalConfirmarFinalizacao"
         @click="modalConfirmarFinalizacao = false"
         class="absolute inset-0 bg-black bg-opacity-40"
         x-transition>
    </div>

    {{-- Modal Content --}}
    <div x-show="modalConfirmarFinalizacao"
         class="relative bg-white rounded-lg shadow-xl max-w-md mx-auto p-6"
         x-transition>
        
        {{-- Icon and Title --}}
        <div class="flex items-start gap-4 mb-4">
            <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-amber-100">
                <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0-6a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900">
                    Finalizar Documento
                </h3>
            </div>
        </div>

        {{-- Message --}}
        <p class="text-gray-700 text-sm mb-6 leading-relaxed">
            <span class="font-medium">Atenção:</span> O documento poderá ser editado desde que não tenha sido assinado por nenhuma pessoa. Após a primeira assinatura, não será mais possível fazer alterações.
        </p>
        <p class="text-gray-600 text-sm mb-6 leading-relaxed flex items-start gap-2">
            <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><strong>Dica:</strong> Se deseja continuar editando o documento, salve como <strong>Rascunho</strong>. Assim você poderá fazer alterações antes de finalizar e enviar para assinatura.</span>
        </p>

        {{-- Buttons --}}
        <div class="flex gap-3 justify-end">
            <button type="button" 
                    @click="modalConfirmarFinalizacao = false"
                    class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition">
                Cancelar
            </button>
            <button type="button"
                    @click="confirmarFinalizacao()"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg hover:from-blue-700 hover:to-blue-800 transition">
                Finalizar
            </button>
        </div>
    </div>
</div>

@php
    $itensAtendimentoIniciais = [];
    $colaboradoresExigenciasIniciais = [];

    foreach (old('itens_atendimento', []) as $indice => $item) {
        $itensAtendimentoIniciais[] = [
            'chave' => 'item_' . $indice,
            'area' => $item['area'] ?? '',
            'descricao' => $item['descricao'] ?? '',
            'embasamento_legal' => $item['embasamento_legal'] ?? '',
        ];
    }

    foreach (old('colaboradores_exigencias', []) as $indice => $colaborador) {
        $colaboradoresExigenciasIniciais[] = [
            'chave' => 'colab_' . $indice,
            'usuario_interno_id' => $colaborador['usuario_interno_id'] ?? '',
            'area' => $colaborador['area'] ?? '',
            'prazo_interno' => $colaborador['prazo_interno'] ?? '',
        ];
    }
@endphp

<script>
// Dados dos tipos de documento (prazo_notificacao)
const tiposDocumentoData = {

    @foreach($tiposDocumento as $tipo)
    {{ $tipo->id }}: {
        prazo_notificacao: {{ $tipo->prazo_notificacao ? 'true' : 'false' }},
        tem_prazo: {{ $tipo->tem_prazo ? 'true' : 'false' }},
        prazo_padrao_dias: {{ $tipo->prazo_padrao_dias ?? 'null' }},
        exige_itens_atendimento: {{ $tipo->exige_itens_atendimento ? 'true' : 'false' }},
        subcategorias: @json($tipo->subcategoriasAtivas->map(fn ($s) => ['id' => $s->id, 'nome' => $s->nome])->values()->all())
    },
    @endforeach
};

function documentoEditor() {
    return {
        tipoSelecionado: @json(old('tipo_documento_id')),
        subcategoriaSelecionada: @json(old('subcategoria_id', '')),
        subcategoriasDoTipo: [],
        sigiloso: false,
        conteudo: '',
        modelos: [],
        salvandoAuto: false,
        ultimoSalvo: '',
        timeoutSalvar: null,
        dadosRecuperados: false,
        modalPreview: false,
        modalConfirmarFinalizacao: false,
        confirmandoFinalizacao: false,
        contadorErros: 0,
        timeoutVerificacao: null,
        chaveLocalStorage: 'documento_rascunho_{{ request()->get("processo_id") ?: (request()->get("processos_ids") ?: (request()->get("os_id") ? "os_" . request()->get("os_id") . "_atividade_" . request()->get("atividade_index", "sem_atividade") : "novo")) }}',
        popupSemUsuarioExternoVisivel: false,
        popupSemUsuarioExternoSegundos: 15,
        popupSemUsuarioExternoTimer: null,
        popupSemUsuarioExternoCountdown: null,
        ultimoTipoPopupSemUsuarioExterno: null,
        // Campos de prazo
        temPrazo: false,
        prazoDias: @json(old('prazo_dias')),
        tipoPrazo: @json(old('tipo_prazo', 'corridos')),
        dataVencimentoFormatada: '',
        isNotificacao: false, // Se é documento de notificação/fiscalização (§1º)
        exigeItensAtendimento: false,
        itensAtendimento: @json($itensAtendimentoIniciais),
        colaboradoresExigencias: @json($colaboradoresExigenciasIniciais),
        modalColaboradoresExigenciasAberto: false,
        modalItemAtendimentoAberto: false,
        modalItemAtendimentoIndice: null,
        modalItemAtendimento: { area: '', descricao: '', embasamento_legal: '' },
        modalItensAtendimento: [],
        modalTextoExigenciasEmLote: '',
        estabelecimentoSemUsuarioExternoParaPrazo: @json(($processosSemUsuarioExternoCount ?? 0) > 0),
        confirmacaoSemUsuarioExternoRealizada: @json((bool) old('confirmar_sem_usuario_externo', false)),
        // Controle de edição simultânea
        outroUsuarioEditando: false,
        nomeUsuarioEditando: '',
        documentoId: {{ isset($documento) ? $documento->id : 'null' }},
        intervalEdicao: null,
        edicaoBloqueada: false,

        init() {
            const self = this;

            // Inicializa a lista de subcategorias baseada no tipo já selecionado (caso seja retorno de erro de validação).
            if (this.tipoSelecionado) {
                const dadosTipoInicial = tiposDocumentoData[this.tipoSelecionado];
                this.subcategoriasDoTipo = (dadosTipoInicial && dadosTipoInicial.subcategorias) ? dadosTipoInicial.subcategorias : [];
                this.exigeItensAtendimento = !!(dadosTipoInicial && dadosTipoInicial.exige_itens_atendimento);
            }

            // Inicia verificação de edição se for edição de documento existente
            if (this.documentoId) {
                this.iniciarVerificacaoEdicao();
            }
            
            // Libera edição quando a página for fechada
            window.addEventListener('beforeunload', () => {
                if (this.documentoId) {
                    this.liberarEdicao();
                }
            });
            
            // Tenta recuperar dados salvos do localStorage
            const dadosSalvos = localStorage.getItem(this.chaveLocalStorage);
            let conteudoInicial = '<p><br></p>';
            
            if (dadosSalvos) {
                try {
                    const dados = JSON.parse(dadosSalvos);
                    if (dados.conteudo) {
                        conteudoInicial = dados.conteudo;
                        this.conteudo = dados.conteudo;
                        this.dadosRecuperados = true;
                    }
                    if (dados.tipoSelecionado) {
                        this.tipoSelecionado = dados.tipoSelecionado;
                        const dadosTipo = tiposDocumentoData[dados.tipoSelecionado];
                        this.subcategoriasDoTipo = (dadosTipo && dadosTipo.subcategorias) ? dadosTipo.subcategorias : [];
                        this.exigeItensAtendimento = !!(dadosTipo && dadosTipo.exige_itens_atendimento);
                        if (dados.subcategoriaSelecionada) {
                            this.subcategoriaSelecionada = dados.subcategoriaSelecionada;
                        }
                        this.$nextTick(() => {
                            const selectTipo = document.querySelector('select[name="tipo_documento_id"]');
                            if (selectTipo) {
                                selectTipo.value = dados.tipoSelecionado;
                            }
                        });
                    }
                    if (Array.isArray(dados.itensAtendimento)
                        && dados.itensAtendimento.length > 0
                        && this.itensAtendimento.every(item => !item.area && !item.descricao && !item.embasamento_legal)) {
                        this.itensAtendimento = dados.itensAtendimento.map(item => ({
                            chave: item.chave || `${Date.now()}_${Math.random()}`,
                            area: item.area || '',
                            descricao: item.descricao || '',
                            embasamento_legal: item.embasamento_legal || ''
                        }));
                    }
                    console.log('Dados recuperados do localStorage:', dados);
                } catch (e) {
                    console.error('Erro ao recuperar dados do localStorage:', e);
                }
            } else {
                this.conteudo = conteudoInicial;
            }

            this.$nextTick(async () => {
                const selectTipo = document.querySelector('select[name="tipo_documento_id"]');
                const tipoInicial = this.tipoSelecionado || selectTipo?.value;

                if (!tipoInicial) {
                    return;
                }

                this.tipoSelecionado = String(tipoInicial);

                if (selectTipo && selectTipo.value !== this.tipoSelecionado) {
                    selectTipo.value = this.tipoSelecionado;
                }

                this.atualizarAvisoPrazo(this.tipoSelecionado);
                await this.buscarPrazoTipo(this.tipoSelecionado, true);
                this.mostrarPopupSemUsuarioExternoSeNecessario(this.tipoSelecionado);
            });

            // Inicializa o editor de documento
            tinymce.init({
                selector: '#editor-tinymce',
                relative_urls: false,
                remove_script_host: false,
                convert_urls: false,
                height: 700,
                min_height: 700,
                resize: false,
                menubar: 'file edit view insert format table',
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount', 'pagebreak',
                    'emoticons', 'nonbreaking'
                ],
                toolbar: [
                    'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor removeformat',
                    'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table | pagebreak | spellcheck_btn | fullscreen code help'
                ],
                font_size_formats: '8pt 10pt 12pt 14pt 16pt 18pt 20pt 24pt 28pt 36pt',
                block_formats: 'Parágrafo=p; Título 1=h1; Título 2=h2; Título 3=h3; Título 4=h4; Título 5=h5; Título 6=h6; Pré-formatado=pre',
                content_style: `
                    body { 
                        font-family: Arial, sans-serif; 
                        font-size: 10pt; 
                        line-height: 1.6; 
                        color: #000; 
                        padding: 15px;
                        margin: 0;
                        cursor: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='18' viewBox='0 0 12 18'%3E%3Cline x1='6' y1='1' x2='6' y2='17' stroke='white' stroke-width='3' stroke-linecap='round'/%3E%3Cline x1='3' y1='1' x2='9' y2='1' stroke='white' stroke-width='3' stroke-linecap='round'/%3E%3Cline x1='3' y1='17' x2='9' y2='17' stroke='white' stroke-width='3' stroke-linecap='round'/%3E%3Cline x1='6' y1='1' x2='6' y2='17' stroke='%23000000' stroke-width='2.5' stroke-linecap='round'/%3E%3Cline x1='3' y1='1' x2='9' y2='1' stroke='%23000000' stroke-width='2.5' stroke-linecap='round'/%3E%3Cline x1='3' y1='17' x2='9' y2='17' stroke='%23000000' stroke-width='2.5' stroke-linecap='round'/%3E%3C/svg%3E") 6 9, text;
                        caret-color: #000;
                    }
                    body * {
                        cursor: inherit;
                    }
                    table { border-collapse: collapse; width: 100%; table-layout: fixed; max-width: 100%; cursor: text; overflow-wrap: break-word; word-wrap: break-word; }
                    table td, table th { border: 1px solid #ddd; padding: 8px; cursor: text; overflow: hidden; word-wrap: break-word; overflow-wrap: break-word; }
                    img { max-width: 100%; height: auto; }
                    .variavel-dinamica {
                        background: transparent !important;
                        color: inherit !important;
                        font-family: inherit !important;
                        font-size: inherit !important;
                        padding: 0 !important;
                    }
                    body div,
                    body li,
                    body td,
                    body th,
                    body h1,
                    body h2,
                    body h3,
                    body h4,
                    body h5,
                    body h6 {
                        white-space: pre-wrap;
                        white-space: break-spaces;
                        word-break: break-word;
                    }
                    body p,
                    body .MsoNormal {
                        margin: 0 0 0.85rem;
                        line-height: 1.45;
                        white-space: pre-wrap;
                        white-space: break-spaces;
                        word-break: break-word;
                    }
                    body .MsoNormal {
                        margin-bottom: 1.15rem;
                        line-height: 1.6;
                    }
                    body p:last-child,
                    body .MsoNormal:last-child {
                        margin-bottom: 0;
                    }
                    body ul,
                    body ol {
                        margin: 0 0 0.85rem 1.25rem;
                        padding-left: 1.25rem;
                    }
                    body li {
                        margin-bottom: 0.25rem;
                    }
                `,
                // Upload de imagem para o servidor (evita embutir base64 e estourar o POST)
                images_upload_url: '{{ route("admin.documentos.upload-imagem") }}',
                images_upload_credentials: true,
                images_reuse_filename: false,
                file_picker_types: 'image',
                images_upload_handler: (blobInfo, progress) => {
                    return new Promise((resolve, reject) => {
                        const formData = new FormData();
                        formData.append('file', blobInfo.blob(), blobInfo.filename());
                        formData.append('_token', '{{ csrf_token() }}');

                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', '{{ route("admin.documentos.upload-imagem") }}');
                        xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
                        xhr.setRequestHeader('Accept', 'application/json');

                        if (progress) {
                            xhr.upload.onprogress = (e) => {
                                if (e.lengthComputable) {
                                    progress(e.loaded / e.total * 100);
                                }
                            };
                        }

                        xhr.onload = () => {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                try {
                                    const json = JSON.parse(xhr.responseText);
                                    if (json.location) {
                                        resolve(json.location);
                                        return;
                                    }
                                    reject('Resposta inválida do servidor ao enviar imagem.');
                                } catch (e) {
                                    reject('Erro ao processar resposta do upload de imagem.');
                                }
                            } else {
                                let msg = 'Erro ao enviar imagem (' + xhr.status + ')';
                                try {
                                    const err = JSON.parse(xhr.responseText);
                                    msg = err.message || err.error || Object.values(err.errors || {})[0]?.[0] || msg;
                                } catch (e) {}
                                reject(msg);
                            }
                        };

                        xhr.onerror = () => reject('Erro de conexão ao enviar imagem.');
                        xhr.send(formData);
                    });
                },
                paste_data_images: true,
                automatic_uploads: true,
                // Configurações de idioma e interface
                branding: false,
                promotion: false,
                browser_spellcheck: true,
                contextmenu: false,
                statusbar: true,
                elementpath: true,
                // Evento de inicialização
                setup: (editor) => {
                    // Botão de Correção Ortográfica na toolbar
                    editor.ui.registry.addButton('spellcheck_btn', {
                        icon: 'spell-check',
                        tooltip: 'Verificar Ortografia (PT-BR)',
                        onAction: () => self.verificarOrtografia()
                    });
                    editor.on('init', () => {
                        editor.setContent(conteudoInicial);
                    });
                    
                    // Sincroniza conteúdo com Alpine.js
                    editor.on('input change keyup', () => {
                        self.conteudo = editor.getContent();
                        self.salvarAutomaticamente();
                        self.verificarErrosTempoReal();
                    });
                    
                    // Salvar referência global para facilitar acesso
                    window._tinymceEditor = editor;
                }
            });
        },

        // Métodos para controle de edição simultânea
        async iniciarVerificacaoEdicao() {
            // Primeiro, registra que estamos editando
            await this.registrarEdicao();
            
            // Inicia o polling a cada 10 segundos
            this.intervalEdicao = setInterval(() => {
                this.registrarEdicao();
            }, 10000);
        },

        async registrarEdicao() {
            if (!this.documentoId) return;
            
            try {
                const response = await fetch(`${window.APP_URL}/admin/documentos/${this.documentoId}/registrar-edicao`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                
                if (data.editando) {
                    this.outroUsuarioEditando = true;
                    this.nomeUsuarioEditando = data.usuario_nome;
                    this.edicaoBloqueada = true;
                } else {
                    this.outroUsuarioEditando = false;
                    this.nomeUsuarioEditando = '';
                    this.edicaoBloqueada = false;
                }
            } catch (error) {
                console.error('Erro ao registrar edição:', error);
            }
        },

        async verificarEdicao() {
            if (!this.documentoId) return;
            
            try {
                const response = await fetch(`${window.APP_URL}/admin/documentos/${this.documentoId}/verificar-edicao`);
                const data = await response.json();
                
                if (data.editando) {
                    this.outroUsuarioEditando = true;
                    this.nomeUsuarioEditando = data.usuario_nome;
                } else {
                    this.outroUsuarioEditando = false;
                    this.nomeUsuarioEditando = '';
                }
            } catch (error) {
                console.error('Erro ao verificar edição:', error);
            }
        },

        async liberarEdicao() {
            if (!this.documentoId) return;
            
            // Usa sendBeacon para garantir que a requisição seja enviada mesmo ao fechar a página
            const data = new FormData();
            data.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            navigator.sendBeacon(`/admin/documentos/${this.documentoId}/liberar-edicao`, data);
            
            if (this.intervalEdicao) {
                clearInterval(this.intervalEdicao);
            }
        },

        salvarAutomaticamente() {
            // Não salva se a modal de confirmação de finalização está aberta
            if (this.modalConfirmarFinalizacao) {
                return;
            }
            
            clearTimeout(this.timeoutSalvar);
            this.salvandoAuto = true;
            
            this.timeoutSalvar = setTimeout(() => {
                const dados = {
                    conteudo: this.conteudo,
                    tipoSelecionado: this.tipoSelecionado,
                    subcategoriaSelecionada: this.subcategoriaSelecionada,
                    itensAtendimento: this.itensAtendimento,
                    timestamp: Date.now()
                };
                localStorage.setItem(this.chaveLocalStorage, JSON.stringify(dados));
                this.salvandoAuto = false;
                this.ultimoSalvo = 'agora';
                console.log('Dados salvos automaticamente:', dados);
            }, 1000);
        },

        verificarErrosTempoReal() {
            clearTimeout(this.timeoutVerificacao);
            
            this.timeoutVerificacao = setTimeout(async () => {
                const editor = tinymce.get('editor-tinymce');
                if (!editor) return;
                const texto = editor.getContent({ format: 'text' });
                
                if (!texto.trim() || texto.length < 10) {
                    this.contadorErros = 0;
                    return;
                }

                try {
                    const response = await fetch('https://api.languagetool.org/v2/check', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            text: texto,
                            language: 'pt-BR',
                            enabledOnly: 'false'
                        })
                    });

                    const data = await response.json();
                    this.contadorErros = data.matches ? data.matches.length : 0;
                } catch (error) {
                    console.error('Erro na verificação em tempo real:', error);
                    this.contadorErros = 0;
                }
            }, 2000); // Aguarda 2 segundos após parar de digitar
        },

        tempoDecorrido(timestamp) {
            const segundos = Math.floor((Date.now() - timestamp) / 1000);
            if (segundos < 60) return 'agora';
            const minutos = Math.floor(segundos / 60);
            if (minutos < 60) return minutos + ' min';
            const horas = Math.floor(minutos / 60);
            return horas + ' h';
        },

        inserirImagem(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            if (!file.type.startsWith('image/')) {
                alert('Por favor, selecione apenas arquivos de imagem.');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = (e) => {
                const editor = tinymce.get('editor-tinymce');
                if (editor) {
                    editor.insertContent(`<img src="${e.target.result}" style="max-width: 100%; height: auto; margin: 10px 0;" />`);
                    this.conteudo = editor.getContent();
                    this.salvarAutomaticamente();
                }
            };
            reader.readAsDataURL(file);
            
            // Limpa o input para permitir selecionar a mesma imagem novamente
            event.target.value = '';
        },

        handlePaste(event) {
            // TinyMCE handles paste natively - this method is kept for compatibility
            // but TinyMCE's built-in paste handling is superior
        },

        inserirTabela() {
            const editor = tinymce.get('editor-tinymce');
            if (editor) {
                // TinyMCE has built-in table plugin, but we keep this for the Variables button
                const linhas = prompt('Número de linhas:', '3');
                const colunas = prompt('Número de colunas:', '3');
                
                if (!linhas || !colunas) return;
                
                let tabela = '<table border="1" style="border-collapse: collapse; width: 100%; margin: 10px 0;">';
                for (let i = 0; i < parseInt(linhas); i++) {
                    tabela += '<tr>';
                    for (let j = 0; j < parseInt(colunas); j++) {
                        tabela += '<td style="border: 1px solid #ddd; padding: 8px;">&nbsp;</td>';
                    }
                    tabela += '</tr>';
                }
                tabela += '</table><p>&nbsp;</p>';
                
                editor.insertContent(tabela);
                this.conteudo = editor.getContent();
                this.salvarAutomaticamente();
            }
        },

        // Inserir variável dinâmica no editor
        inserirVariavel(variavel) {
            const editor = tinymce.get('editor-tinymce');
            if (editor) {
                const variavelFormatada = `<span class="variavel-dinamica" data-variavel="${variavel}">${variavel}</span>&nbsp;`;
                editor.insertContent(variavelFormatada);
                this.conteudo = editor.getContent();
                this.salvarAutomaticamente();
            }
        },

        limparTudo() {
            if (confirm('Tem certeza que deseja limpar todo o conteúdo? Esta ação não pode ser desfeita.')) {
                const editor = tinymce.get('editor-tinymce');
                if (editor) {
                    editor.setContent('<p><br></p>');
                    this.conteudo = '<p><br></p>';
                    this.salvarAutomaticamente();
                }
            }
        },

        async verificarOrtografia(event = null) {
            const tmceEditor = tinymce.get('editor-tinymce');
            if (!tmceEditor) return;
            
            const conteudoHTML = tmceEditor.getContent();
            const texto = tmceEditor.getContent({ format: 'text' });
            
            if (!texto.trim()) {
                alert('Digite algum texto para verificar a ortografia.');
                return;
            }

            // Mostrar indicador de carregamento
            const btnVerificar = event?.target?.closest?.('button') || null;
            const originalHTML = btnVerificar ? btnVerificar.innerHTML : '';
            if (btnVerificar) {
                btnVerificar.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                btnVerificar.disabled = true;
            }

            try {
                // Usar API pública do LanguageTool
                const response = await fetch('https://api.languagetool.org/v2/check', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        text: texto,
                        language: 'pt-BR',
                        enabledOnly: 'false'
                    })
                });

                const data = await response.json();
                
                if (data.matches && data.matches.length > 0) {
                    // Criar lista de erros com botões de substituição
                    let errosHTML = '<div style="max-height: 400px; overflow-y: auto;"><ul style="list-style: none; padding: 0;" id="lista-erros">';
                    
                    data.matches.forEach((erro, index) => {
                        const palavraErrada = texto.substring(erro.offset, erro.offset + erro.length);
                        const sugestoes = erro.replacements.slice(0, 3);
                        
                        errosHTML += `
                            <li id="erro-${index}" style="padding: 12px; margin-bottom: 10px; border-left: 3px solid #ef4444; background: #fef2f2; border-radius: 4px; transition: all 0.3s;">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div style="flex: 1;">
                                        <strong style="color: #dc2626; font-size: 15px;">${palavraErrada}</strong>
                                        <p style="margin: 5px 0; font-size: 14px; color: #374151;">${erro.message}</p>
                                        ${sugestoes.length > 0 ? `
                                            <div style="margin-top: 8px;">
                                                <p style="margin: 0 0 5px 0; font-size: 12px; color: #6b7280; font-weight: 600;">Clique para substituir:</p>
                                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                                    ${sugestoes.map(s => `
                                                        <button 
                                                            onclick="substituirPalavra('${palavraErrada.replace(/'/g, "\\'")}', '${s.value.replace(/'/g, "\\'")}', ${index})"
                                                            style="padding: 6px 12px; background: #059669; color: white; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; font-weight: 500; transition: all 0.2s;"
                                                            onmouseover="this.style.background='#047857'"
                                                            onmouseout="this.style.background='#059669'">
                                                            ${s.value}
                                                        </button>
                                                    `).join('')}
                                                </div>
                                            </div>
                                        ` : '<p style="margin: 5px 0; font-size: 13px; color: #6b7280; font-style: italic;">Sem sugestões disponíveis</p>'}
                                    </div>
                                </div>
                            </li>
                        `;
                    });
                    
                    errosHTML += '</ul></div>';
                    
                    // Criar modal customizado
                    const modal = document.createElement('div');
                    modal.id = 'modal-ortografia';
                    modal.style.cssText = 'position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;';
                    modal.innerHTML = `
                        <div style="background: white; border-radius: 12px; max-width: 650px; width: 90%; max-height: 80vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
                            <div style="background: linear-gradient(to right, #ef4444, #dc2626); padding: 20px; color: white;">
                                <h3 style="margin: 0; font-size: 18px; font-weight: 600;">Verificação Ortográfica</h3>
                                <p style="margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;">Encontrados <span id="contador-erros">${data.matches.length}</span> possíveis erros</p>
                            </div>
                            <div style="padding: 20px;">
                                ${errosHTML}
                            </div>
                            <div style="padding: 15px 20px; background: #f9fafb; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                                <span id="status-correcoes" style="font-size: 13px; color: #059669; font-weight: 500;"></span>
                                <button onclick="this.closest('[style*=fixed]').remove()" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer;">
                                    Fechar
                                </button>
                            </div>
                        </div>
                    `;
                    
                    // Função global para substituir palavra
                    window.substituirPalavra = (palavraErrada, sugestao, index) => {
                        const tmceEditor = tinymce.get('editor-tinymce');
                        if (!tmceEditor) return;
                        let conteudo = tmceEditor.getContent();
                        
                        // Criar regex para encontrar a palavra (case insensitive)
                        const regex = new RegExp(`\\b${palavraErrada.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'gi');
                        
                        // Substituir primeira ocorrência
                        let substituido = false;
                        conteudo = conteudo.replace(regex, (match) => {
                            if (!substituido) {
                                substituido = true;
                                return sugestao;
                            }
                            return match;
                        });
                        
                        // Atualizar editor
                        tmceEditor.setContent(conteudo);
                        this.conteudo = conteudo;
                        this.salvarAutomaticamente();
                        
                        // Marcar erro como corrigido no modal
                        const erroItem = document.getElementById(`erro-${index}`);
                        if (erroItem) {
                            erroItem.style.borderLeftColor = '#059669';
                            erroItem.style.background = '#d1fae5';
                            erroItem.innerHTML = `
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <svg style="width: 24px; height: 24px; color: #059669; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <strong style="color: #065f46;">${palavraErrada}</strong> → <strong style="color: #059669;">${sugestao}</strong>
                                        <p style="margin: 5px 0 0 0; font-size: 13px; color: #047857;">✓ Substituído com sucesso!</p>
                                    </div>
                                </div>
                            `;
                            
                            // Atualizar contador
                            const errosRestantes = document.querySelectorAll('#lista-erros li[style*="border-left: 3px solid rgb(239, 68, 68)"]').length;
                            document.getElementById('contador-erros').textContent = errosRestantes;
                            
                            // Atualizar status
                            const totalCorrigidos = data.matches.length - errosRestantes;
                            document.getElementById('status-correcoes').textContent = 
                                totalCorrigidos > 0 ? `✓ ${totalCorrigidos} correção(ões) aplicada(s)` : '';
                        }
                    };
                    
                    document.body.appendChild(modal);
                    modal.onclick = (e) => {
                        if (e.target === modal) modal.remove();
                    };
                } else {
                    alert('✓ Nenhum erro encontrado! Seu texto está correto.');
                }
            } catch (error) {
                console.error('Erro ao verificar ortografia:', error);
                alert('Erro ao verificar ortografia. Verifique sua conexão com a internet.');
            } finally {
                // Restaurar botão
                if (btnVerificar) {
                    btnVerificar.innerHTML = originalHTML;
                    btnVerificar.disabled = false;
                }
            }
        },

        /**
         * Callback ao mudar o tipo de documento:
         * atualiza lista de subcategorias, limpa a seleção anterior, e recarrega modelos + prazo.
         */
        onTipoSelecionadoChange(tipoId) {
            this.subcategoriaSelecionada = '';
            const dados = tiposDocumentoData[tipoId];
            this.subcategoriasDoTipo = (dados && dados.subcategorias) ? dados.subcategorias : [];
            this.exigeItensAtendimento = !!(dados && dados.exige_itens_atendimento);

            this.carregarModelos(tipoId);
            this.atualizarAvisoPrazo(tipoId);
            this.salvarAutomaticamente();
        },

        adicionarItemAtendimento() {
            this.abrirModalItemAtendimento();
        },

        abrirModalItemAtendimento(indice = null) {
            this.modalItemAtendimentoIndice = indice;
            const item = indice !== null ? this.itensAtendimento[indice] : null;
            this.modalItensAtendimento = [{
                area: item?.area || '',
                descricao: item?.descricao || '',
                embasamento_legal: item?.embasamento_legal || ''
            }];
            this.modalTextoExigenciasEmLote = '';
            this.modalItemAtendimento = this.modalItensAtendimento[0];
            this.modalItemAtendimentoAberto = true;
            this.$nextTick(() => document.getElementById('modal-exigencia-descricao-0')?.focus());
        },

        fecharModalItemAtendimento() {
            this.modalItemAtendimentoAberto = false;
            this.modalItemAtendimentoIndice = null;
            this.modalItemAtendimento = { area: '', descricao: '', embasamento_legal: '' };
            this.modalItensAtendimento = [];
            this.modalTextoExigenciasEmLote = '';
        },

        limparLinhaExigenciaColada(linha) {
            return String(linha || '')
                .replace(/\u00a0/g, ' ')
                .replace(/^[\s>*•·●○▪▫‣⁃\-–—]+/, '')
                .replace(/^\s*(?:\(?\d+\)?[.)-]?|[a-zA-Z][.)-])\s+/, '')
                .trim();
        },

        separarExigenciaEBaseLegal(linha) {
            let descricao = this.limparLinhaExigenciaColada(linha);
            let embasamento_legal = '';

            const baseLegalMatch = descricao.match(/\b(?:base|embasamento)\s+legal\s*:\s*/i);
            if (baseLegalMatch) {
                embasamento_legal = descricao.slice(baseLegalMatch.index + baseLegalMatch[0].length).trim();
                descricao = descricao.slice(0, baseLegalMatch.index).trim();
            } else if (descricao.includes('|')) {
                const partes = descricao.split('|');
                descricao = partes.shift().trim();
                embasamento_legal = partes.join('|').trim();
            }

            return { area: '', descricao, embasamento_legal };
        },

        extrairTrechoProvidencias(texto) {
            let conteudo = String(texto || '').replace(/\u00a0/g, ' ');
            const inicio = conteudo.search(/O\s+ESTABELECIMENTO\s+DEVER[ÁA]\s+PROVIDENCIAR\s*:/i);

            if (inicio >= 0) {
                conteudo = conteudo.slice(inicio).replace(/^.*?PROVIDENCIAR\s*:/i, '');
            }

            const fim = conteudo.search(/\b(?:EQUIPE\s+T[ÉE]CNICA|ORIENTA[ÇC][ÃA]O\s+SOBRE|Para\s+comprova[çc][ãa]o)\b/i);
            if (fim > 0) {
                conteudo = conteudo.slice(0, fim);
            }

            return conteudo.trim();
        },

        limparTituloArea(texto) {
            return String(texto || '')
                .replace(/[;:.\s]+$/g, '')
                .replace(/^[;:.\s]+/g, '')
                .trim();
        },

        extrairTituloAreaFinal(texto) {
            const match = String(texto || '').match(/\s+\b([\p{Lu}\d\/.-]{3,}(?:\s+[\p{Lu}\d\/.-]{2,}){0,6})\s*$/u);
            return match ? this.limparTituloArea(match[1]) : '';
        },

        removerTituloCategoriaDoFinal(texto) {
            const areaFinal = this.extrairTituloAreaFinal(texto);
            if (!areaFinal) {
                return String(texto || '').trim();
            }

            return String(texto || '').slice(0, -areaFinal.length).trim();
        },

        extrairBaseLegalEntreParenteses(texto) {
            let descricao = this.removerTituloCategoriaDoFinal(texto);
            let embasamento_legal = '';
            const matches = [...descricao.matchAll(/\(([^()]*)\)\s*[\.;]?/g)];
            const legal = [...matches].reverse().find((match) => /RDC|ANVISA|IN\s*n?[º°]?|Art\.?|Lei|Portaria|Resolu[çc][ãa]o|Anexo/i.test(match[1] || ''));

            if (legal) {
                embasamento_legal = (legal[1] || '').trim();
                descricao = `${descricao.slice(0, legal.index)} ${descricao.slice(legal.index + legal[0].length)}`
                    .replace(/\s+/g, ' ')
                    .replace(/\s+([.;,])/g, '$1')
                    .replace(/\.\s*\./g, '.')
                    .trim();
            }

            return {
                area: this.extrairTituloAreaFinal(texto),
                descricao: descricao.replace(/;\s*$/, '').trim(),
                embasamento_legal
            };
        },

        extrairItensNumeradosDaNotificacao(texto) {
            const trecho = this.extrairTrechoProvidencias(texto).replace(/\s+/g, ' ').trim();
            const regex = /(?:^|\s)(\d{1,3})[.)]\s+/g;
            const marcadores = [...trecho.matchAll(regex)];

            if (!marcadores.length) {
                return [];
            }

            let areaAtual = this.limparTituloArea(trecho.slice(0, marcadores[0].index));

            return marcadores.map((marcador, indice) => {
                const inicio = marcador.index + marcador[0].length;
                const fim = marcadores[indice + 1]?.index ?? trecho.length;
                const item = this.extrairBaseLegalEntreParenteses(trecho.slice(inicio, fim));
                const itemComArea = { ...item, area: areaAtual };
                if (item.area) {
                    areaAtual = item.area;
                }
                return itemComArea;
            }).filter((item) => item.descricao);
        },

        transformarTextoExigenciasEmItens() {
            const texto = String(this.modalTextoExigenciasEmLote || '');
            let itens = this.extrairItensNumeradosDaNotificacao(texto);

            if (!itens.length) {
                itens = texto
                    .split(/\r?\n/)
                    .map((linha) => this.separarExigenciaEBaseLegal(linha))
                    .filter((item) => item.descricao);
            }

            if (!itens.length) {
                alert('Cole o texto da notificação ou pelo menos uma exigência.');
                return;
            }

            const itensAtuaisPreenchidos = this.modalItensAtendimento
                .filter((item) => item.area?.trim() || item.descricao?.trim() || item.embasamento_legal?.trim());

            this.modalItensAtendimento = [...itensAtuaisPreenchidos, ...itens];
            this.modalItemAtendimento = this.modalItensAtendimento[0] || { area: '', descricao: '', embasamento_legal: '' };
            this.modalTextoExigenciasEmLote = '';
            this.$nextTick(() => document.getElementById('modal-exigencia-descricao-0')?.focus());
        },

        adicionarLinhaModalItemAtendimento() {
            this.modalItensAtendimento.push({ area: '', descricao: '', embasamento_legal: '', editando_area: true });
            const indice = this.modalItensAtendimento.length - 1;
            this.$nextTick(() => document.getElementById(`modal-exigencia-area-${indice}`)?.focus());
        },

        removerLinhaModalItemAtendimento(indice) {
            if (this.modalItensAtendimento.length <= 1) return;
            this.modalItensAtendimento.splice(indice, 1);
        },

        atualizarAreaGrupoModal(indice, valor) {
            const areaAnterior = (this.modalItensAtendimento[indice]?.area || '').trim();
            const novaArea = (valor || '').toString().trim().toUpperCase();
            let posicao = indice;

            while (posicao < this.modalItensAtendimento.length) {
                const areaAtual = (this.modalItensAtendimento[posicao]?.area || '').trim();
                if (areaAtual !== areaAnterior) break;
                this.modalItensAtendimento[posicao].area = novaArea;
                posicao++;
            }
        },

        areasModalDisponiveis() {
            const areas = [];

            [...(this.itensAtendimento || []), ...(this.modalItensAtendimento || [])].forEach((item) => {
                const area = (item.area || '').toString().trim().toUpperCase();
                if (area && !areas.includes(area)) {
                    areas.push(area);
                }
            });

            return areas;
        },

        areasExigenciasDisponiveis() {
            const areas = [];

            [...(this.itensAtendimento || []), ...(this.colaboradoresExigencias || [])].forEach((item) => {
                const area = (item.area || '').toString().trim().toUpperCase();
                if (area && !areas.includes(area)) {
                    areas.push(area);
                }
            });

            return areas;
        },

        abrirModalColaboradoresExigencias() {
            if (this.colaboradoresExigencias.length === 0) {
                this.adicionarColaboradorExigencia();
            }
            this.modalColaboradoresExigenciasAberto = true;
        },

        fecharModalColaboradoresExigencias() {
            this.modalColaboradoresExigenciasAberto = false;
        },

        adicionarColaboradorExigencia() {
            const areas = this.areasExigenciasDisponiveis();
            this.colaboradoresExigencias.push({
                chave: `${Date.now()}_${Math.random()}`,
                usuario_interno_id: '',
                area: areas[0] || '',
                prazo_interno: ''
            });
        },

        removerColaboradorExigencia(indice) {
            this.colaboradoresExigencias.splice(indice, 1);
        },

        nomeUsuarioColaborador(usuarioId) {
            const opcoes = @json($usuariosInternos->map(fn ($u) => ['id' => $u->id, 'nome' => $u->nome])->values()->all());
            const usuario = opcoes.find((item) => String(item.id) === String(usuarioId));
            return usuario ? usuario.nome : 'Técnico não selecionado';
        },

        modalTemItensValidos() {
            return this.modalItensAtendimento.some((item) => item.descricao.trim());
        },

        salvarModalItemAtendimento() {
            const itensValidos = this.modalItensAtendimento
                .map((item) => ({
                    area: item.area.trim(),
                    descricao: item.descricao.trim(),
                    embasamento_legal: item.embasamento_legal.trim()
                }))
                .filter((item) => item.descricao);

            if (itensValidos.length === 0) {
                return;
            }

            if (this.modalItemAtendimentoIndice !== null) {
                const dados = {
                    chave: this.itensAtendimento[this.modalItemAtendimentoIndice].chave,
                    area: itensValidos[0].area,
                    descricao: itensValidos[0].descricao,
                    embasamento_legal: itensValidos[0].embasamento_legal
                };
                this.itensAtendimento.splice(this.modalItemAtendimentoIndice, 1, dados);
            } else {
                itensValidos.forEach((item, indice) => {
                    this.itensAtendimento.push({
                        chave: `${Date.now()}_${indice}_${Math.random()}`,
                        area: item.area,
                        descricao: item.descricao,
                        embasamento_legal: item.embasamento_legal
                    });
                });
            }

            this.fecharModalItemAtendimento();
            this.salvarAutomaticamente();
        },

        removerItemAtendimento(indice) {
            this.itensAtendimento.splice(indice, 1);
            this.salvarAutomaticamente();
        },

        moverItemAtendimento(indice, direcao) {
            const destino = indice + direcao;
            if (destino < 0 || destino >= this.itensAtendimento.length) return;
            const [item] = this.itensAtendimento.splice(indice, 1);
            this.itensAtendimento.splice(destino, 0, item);
            this.salvarAutomaticamente();
        },

        async carregarModelos(tipoId) {
            if (!tipoId) {
                console.log('Tipo de documento não selecionado');
                return;
            }

            console.log('Carregando modelos para tipo:', tipoId);

            try {
                // Busca modelos - usa APP_URL para funcionar em subdiretórios
                const processoId = document.querySelector('input[name="processo_id"]')?.value;
                const query = new URLSearchParams();
                if (processoId) {
                    query.set('processo_id', processoId);
                }
                if (this.subcategoriaSelecionada) {
                    query.set('subcategoria_id', this.subcategoriaSelecionada);
                }

                const url = `${window.APP_URL}/admin/documentos/modelos/${tipoId}${query.toString() ? `?${query.toString()}` : ''}`;
                console.log('Fazendo requisição para:', url);
                
                const response = await fetch(url);
                console.log('Status da resposta:', response.status);
                
                if (!response.ok) {
                    console.warn('Nenhum modelo encontrado para este tipo de documento (Status:', response.status, ')');
                    return;
                }
                
                this.modelos = await response.json();
                console.log('Modelos carregados:', this.modelos);
                
                // Se houver modelos, carrega o primeiro automaticamente e preenche o editor
                if (this.modelos && this.modelos.length > 0) {
                    console.log('Carregando modelo:', this.modelos[0]);
                    // Normaliza tabelas para caber na largura A4
                    let conteudoModelo = this.modelos[0].conteudo;
                    conteudoModelo = conteudoModelo.replace(/<table([^>]*)style="[^"]*width:\s*\d+px[^"]*"/gi, (match, attrs) => {
                        return match.replace(/width:\s*\d+px/gi, 'width: 100%');
                    });
                    conteudoModelo = conteudoModelo.replace(/<table([^>]*)width="\d+"/gi, '<table$1width="100%"');
                    conteudoModelo = conteudoModelo.replace(/<td([^>]*)style="[^"]*width:\s*\d+px[^"]*"/gi, (match) => {
                        return match.replace(/width:\s*\d+px/gi, '');
                    });
                    this.conteudo = conteudoModelo;
                    const tmceEditor = tinymce.get('editor-tinymce');
                    if (tmceEditor) {
                        tmceEditor.setContent(this.conteudo);
                        console.log('Modelo carregado no editor com sucesso!');
                        this.salvarAutomaticamente();
                    } else {
                        console.error('Editor TinyMCE não encontrado');
                    }
                } else {
                    console.log('Nenhum modelo disponível para este tipo de documento');
                }

                // Busca informações de prazo do tipo
                await this.buscarPrazoTipo(tipoId);
                this.mostrarPopupSemUsuarioExternoSeNecessario(tipoId);
                
            } catch (error) {
                console.error('Erro ao carregar modelos:', error);
            }
        },

        atualizarAvisoPrazo(tipoId) {
            if (!tipoId || !tiposDocumentoData[tipoId]) {
                // Esconde todos os avisos
                document.getElementById('aviso-prazo-container').style.display = 'none';
                document.getElementById('aviso-notificacao').style.display = 'none';
                document.getElementById('aviso-licenciamento').style.display = 'none';
                this.isNotificacao = false;
                this.ultimoTipoPopupSemUsuarioExterno = null;
                this.fecharPopupSemUsuarioExterno();
                return;
            }

            const tipoData = tiposDocumentoData[tipoId];
            
            // Mostra o container
            document.getElementById('aviso-prazo-container').style.display = 'block';
            
            // Mostra o aviso correto baseado no tipo
            if (tipoData.prazo_notificacao) {
                // Documento de notificação/fiscalização
                document.getElementById('aviso-notificacao').style.display = 'block';
                document.getElementById('aviso-licenciamento').style.display = 'none';
                this.isNotificacao = true;
            } else {
                // Documento de licenciamento (prazo fixo)
                document.getElementById('aviso-notificacao').style.display = 'none';
                document.getElementById('aviso-licenciamento').style.display = 'block';
                this.isNotificacao = false;
            }
            
            // Recalcula a data de vencimento com a nova informação
            this.calcularDataVencimento();
        },

        mostrarPopupSemUsuarioExternoSeNecessario(tipoId) {
            const tipoData = tipoId ? tiposDocumentoData[tipoId] : null;

            if (!tipoData || !tipoData.tem_prazo || !this.estabelecimentoSemUsuarioExternoParaPrazo) {
                this.ultimoTipoPopupSemUsuarioExterno = null;
                this.fecharPopupSemUsuarioExterno();
                return;
            }

            if (this.ultimoTipoPopupSemUsuarioExterno === String(tipoId) && this.popupSemUsuarioExternoVisivel) {
                return;
            }

            this.ultimoTipoPopupSemUsuarioExterno = String(tipoId);
            this.popupSemUsuarioExternoVisivel = true;
            this.popupSemUsuarioExternoSegundos = 15;

            clearTimeout(this.popupSemUsuarioExternoTimer);
            clearInterval(this.popupSemUsuarioExternoCountdown);

            this.popupSemUsuarioExternoCountdown = setInterval(() => {
                if (this.popupSemUsuarioExternoSegundos > 1) {
                    this.popupSemUsuarioExternoSegundos--;
                    return;
                }

                this.fecharPopupSemUsuarioExterno();
            }, 1000);

            this.popupSemUsuarioExternoTimer = setTimeout(() => {
                this.fecharPopupSemUsuarioExterno();
            }, 15000);
        },

        fecharPopupSemUsuarioExterno() {
            this.popupSemUsuarioExternoVisivel = false;
            this.popupSemUsuarioExternoSegundos = 15;

            clearTimeout(this.popupSemUsuarioExternoTimer);
            clearInterval(this.popupSemUsuarioExternoCountdown);

            this.popupSemUsuarioExternoTimer = null;
            this.popupSemUsuarioExternoCountdown = null;
        },

        contarPalavras() {
            const texto = this.conteudo.replace(/<[^>]*>/g, '').trim();
            return texto.split(/\s+/).filter(word => word.length > 0).length;
        },

        sincronizarConteudoEditor() {
            const editor = tinymce.get('editor-tinymce');
            if (editor) {
                this.conteudo = editor.getContent();
            }
        },

        validarFormularioBasico() {
            this.sincronizarConteudoEditor();

            const assinaturas = document.querySelectorAll('input[name="assinaturas[]"]');
            console.log('Conteúdo:', this.conteudo);
            console.log('Tipo selecionado:', this.tipoSelecionado);
            console.log('Assinaturas selecionadas:', assinaturas.length);

            if (assinaturas.length === 0) {
                alert('Selecione pelo menos um usuário para assinar o documento!');
                return false;
            }

            if (!this.conteudo || this.conteudo.trim() === '' || this.conteudo === '<p><br></p>') {
                alert('Digite o conteúdo do documento!');
                return false;
            }

            if (this.temPrazo) {
                if (!this.prazoDias || Number(this.prazoDias) < 1) {
                    alert('Informe o prazo em dias para este documento.');
                    return false;
                }

                if (!['corridos', 'uteis'].includes(this.tipoPrazo)) {
                    alert('Selecione o tipo de prazo do documento.');
                    return false;
                }
            }

            return true;
        },

        precisaConfirmarAusenciaUsuarioExterno() {
            return this.temPrazo
                && Number(this.prazoDias) > 0
                && this.estabelecimentoSemUsuarioExternoParaPrazo
                && !this.confirmacaoSemUsuarioExternoRealizada;
        },

        confirmarAusenciaUsuarioExterno() {
            const confirmado = window.confirm(
                'Não existe usuário vinculado a esse estabelecimento para visualizar esse documento com prazo. Desejo mesmo assim criar o documento?'
            );

            if (!confirmado) {
                return false;
            }

            this.confirmacaoSemUsuarioExternoRealizada = true;
            document.getElementById('inputConfirmarSemUsuarioExterno').value = '1';

            return true;
        },

        async externalizarImagensBase64() {
            const editor = tinymce.get('editor-tinymce');
            if (!editor) {
                return true;
            }

            let html = editor.getContent();
            if (!html.includes('data:image')) {
                this.conteudo = html;
                return true;
            }

            const regex = /src=["'](data:image\/[a-zA-Z0-9.+-]+;base64,[A-Za-z0-9+/=]+)["']/gi;
            const urls = [];
            let match;
            while ((match = regex.exec(html)) !== null) {
                if (!urls.includes(match[1])) {
                    urls.push(match[1]);
                }
            }

            if (urls.length === 0) {
                this.conteudo = html;
                return true;
            }

            for (const dataUrl of urls) {
                try {
                    const blob = await (await fetch(dataUrl)).blob();
                    const ext = (blob.type || 'image/jpeg').split('/')[1] || 'jpg';
                    const formData = new FormData();
                    formData.append('file', blob, 'imagem.' + ext.replace('jpeg', 'jpg'));
                    formData.append('_token', '{{ csrf_token() }}');

                    const response = await fetch('{{ route("admin.documentos.upload-imagem") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    if (!response.ok) {
                        console.warn('Falha ao externalizar imagem base64', response.status);
                        continue;
                    }

                    const json = await response.json();
                    if (json.location) {
                        html = html.split(dataUrl).join(json.location);
                    }
                } catch (e) {
                    console.warn('Erro ao enviar imagem base64:', e);
                }
            }

            editor.setContent(html);
            this.conteudo = html;
            return true;
        },

        async submeterFormulario(acao) {
            document.getElementById('inputAcao').value = acao;

            if (!this.validarFormularioBasico()) {
                return false;
            }

            if (this.precisaConfirmarAusenciaUsuarioExterno() && !this.confirmarAusenciaUsuarioExterno()) {
                return false;
            }

            // Converte fotos base64 → upload no servidor antes do POST
            await this.externalizarImagensBase64();

            const editor = tinymce.get('editor-tinymce');
            if (editor) {
                this.conteudo = editor.getContent();
            }

            const form = document.getElementById('formDocumento');
            const inputConteudo = form.querySelector('input[name="conteudo"]');
            if (inputConteudo) {
                inputConteudo.value = this.conteudo || '';
            }

            localStorage.removeItem(this.chaveLocalStorage);
            console.log('localStorage limpo após submissão');
            form.submit();

            return true;
        },

        async confirmarFinalizacao() {
            // Fecha o modal
            this.modalConfirmarFinalizacao = false;

            if (!(await this.submeterFormulario('finalizar'))) {
                this.confirmandoFinalizacao = false;
                return;
            }

            this.confirmandoFinalizacao = true;
        },

        async handleSubmit(event) {
            event.preventDefault();
            return this.submeterFormulario(document.getElementById('inputAcao').value || 'rascunho');
        },

        // Busca informações de prazo do tipo de documento
        async buscarPrazoTipo(tipoId, preservarValoresAtuais = false) {
            try {
                const response = await fetch(`${window.APP_URL}/admin/documentos/prazo-tipo/${tipoId}`);
                
                if (!response.ok) {
                    console.warn('Erro ao buscar prazo do tipo');
                    return;
                }
                
                const data = await response.json();
                console.log('Informações de prazo:', data);
                
                this.temPrazo = data.tem_prazo;
                
                // Se tem prazo, preenche os campos
                if (data.tem_prazo) {
                    const prazoAtualValido = this.prazoDias !== null && this.prazoDias !== '' && Number(this.prazoDias) > 0;

                    // Preenche prazo padrão se existir
                    if (preservarValoresAtuais && prazoAtualValido) {
                        this.prazoDias = this.prazoDias;
                    } else if (data.prazo_padrao_dias) {
                        this.prazoDias = data.prazo_padrao_dias;
                    } else {
                        this.prazoDias = null;
                    }
                    
                    // Preenche tipo de prazo
                    if (!preservarValoresAtuais || !['corridos', 'uteis'].includes(this.tipoPrazo)) {
                        this.tipoPrazo = data.tipo_prazo || 'corridos';
                    }
                    
                    // Calcula data de vencimento se tiver prazo
                    if (this.prazoDias) {
                        this.calcularDataVencimento();
                    }
                } else {
                    // Limpa os campos se não tem prazo
                    this.prazoDias = null;
                    this.tipoPrazo = 'corridos';
                    this.dataVencimentoFormatada = '';
                }
                
            } catch (error) {
                console.error('Erro ao buscar prazo do tipo:', error);
            }
        },

        // Calcula a data de vencimento baseada no prazo em dias
        calcularDataVencimento() {
            if (!this.prazoDias || this.prazoDias < 1) {
                this.dataVencimentoFormatada = '';
                return;
            }

            const hoje = new Date();
            let dataInicioPrazo = new Date(hoje);
            
            // Para documentos de notificação (§1º), o prazo começa após 5 dias úteis
            // (ou antes, se o estabelecimento visualizar - mas isso não sabemos agora)
            if (this.isNotificacao) {
                // Adiciona 5 dias úteis para início do prazo
                let diasUteisContados = 0;
                while (diasUteisContados < 5) {
                    dataInicioPrazo.setDate(dataInicioPrazo.getDate() + 1);
                    const diaSemana = dataInicioPrazo.getDay();
                    if (diaSemana !== 0 && diaSemana !== 6) {
                        diasUteisContados++;
                    }
                }
            }
            
            let dataVencimento = new Date(dataInicioPrazo);
            
            if (this.tipoPrazo === 'corridos') {
                // Dias corridos: simplesmente adiciona os dias
                dataVencimento.setDate(dataInicioPrazo.getDate() + parseInt(this.prazoDias));
            } else {
                // Dias úteis: adiciona apenas dias úteis (segunda a sexta)
                let diasRestantes = parseInt(this.prazoDias);
                
                while (diasRestantes > 0) {
                    dataVencimento.setDate(dataVencimento.getDate() + 1);
                    const diaSemana = dataVencimento.getDay();
                    
                    // 0 = Domingo, 6 = Sábado
                    if (diaSemana !== 0 && diaSemana !== 6) {
                        diasRestantes--;
                    }
                }
            }

            // Formata a data em português
            const opcoes = { 
                day: '2-digit', 
                month: 'long', 
                year: 'numeric' 
            };
            this.dataVencimentoFormatada = dataVencimento.toLocaleDateString('pt-BR', opcoes);
        },
    }
}
</script>

{{-- Assistente de Edição de Documento --}}
@include('components.assistente-edicao-documento-chat')

@endsection
