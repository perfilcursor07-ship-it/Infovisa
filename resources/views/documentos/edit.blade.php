@extends('layouts.admin')

@php
    $permiteSalvarRascunho = $documento->status === 'rascunho';
@endphp

@section('title', $permiteSalvarRascunho ? 'Editar Rascunho' : 'Editar Documento')

@php
    // Desativa o assistente IA principal nesta página (já tem o assistente de redação)
    $desativarAssistenteIA = true;
@endphp

@push('styles')
<style>
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
@endif

{{-- Script de Edição Colaborativa --}}
<script src="{{ asset('js/edicao-colaborativa.js') }}"></script>

<div class="min-h-screen bg-gray-50" x-data="documentoEditor()">
    <div class="max-w-8xl mx-auto px-4 py-8">
        {{-- Header com Breadcrumb --}}
        <div class="mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-600 mb-3">
                @if(isset($processo))
                    <a href="{{ route('admin.estabelecimentos.processos.show', [$processo->estabelecimento_id, $processo->id]) }}" class="hover:text-blue-600 transition">
                        Processo {{ $processo->numero_processo }}
                    </a>
                @else
                    <a href="{{ route('admin.documentos.index') }}" class="hover:text-blue-600 transition">
                        Documentos
                    </a>
                @endif
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-gray-900 font-medium">{{ $permiteSalvarRascunho ? 'Editar Rascunho' : 'Editar Documento' }}</span>
            </div>
            
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                {{ $permiteSalvarRascunho ? 'Editar Rascunho' : 'Editar Documento' }}
            </h1>
            
            @if(isset($processo))
                <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="font-medium">{{ $processo->estabelecimento?->nome_fantasia ?? $processo->estabelecimento?->razao_social ?? 'Estabelecimento não informado' }}</span>
                </div>
            @endif

            {{-- Banner Documento em Lote --}}
            @if($documento->isLote())
            <div class="mt-4 p-3 bg-purple-50 border border-purple-200 rounded-lg">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-purple-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <div class="flex-1">
                        <h4 class="text-sm font-semibold text-purple-900">Documento em Lote — {{ count($documento->processos_ids) }} processos</h4>
                        <p class="text-xs text-purple-700 mt-0.5">
                            Ao finalizar e assinar, este documento será distribuído automaticamente para todos os processos vinculados.
                        </p>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            @foreach($documento->processosLote() as $procLote)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-medium text-purple-700 bg-purple-100 rounded">
                                    {{ $procLote->numero_processo }} — {{ $procLote->estabelecimento->nome_fantasia ?? '' }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            {{-- Aviso sobre edição --}}
            @if($documento->assinaturas->where('status', 'assinado')->count() === 0)
            <div class="mt-4 flex items-start gap-3 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm text-blue-900 font-semibold mb-1">Documento editável</p>
                    <p class="text-sm text-blue-800">
                        Este documento pode ser editado livremente enquanto não houver assinaturas. Após a primeira assinatura, não será mais possível fazer alterações.
                    </p>
                    @if($permiteSalvarRascunho)
                    <p class="text-sm text-blue-700 mt-2">
                        💡 <strong>Dica:</strong> Se ainda não finalizou as edições, salve como <strong>Rascunho</strong> para continuar editando depois.
                    </p>
                    @else
                    <p class="text-sm text-blue-700 mt-2">
                        💡 <strong>Dica:</strong> Este documento já foi finalizado para assinatura. Você pode ajustar o conteúdo antes da primeira assinatura, mas ele continuará no fluxo de assinatura.
                    </p>
                    @endif
                </div>
            </div>
            @else
            <div class="mt-4 flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm text-amber-900 font-semibold mb-1">Documento bloqueado para edição</p>
                    <p class="text-sm text-amber-800">
                        Este documento já possui assinaturas e não pode mais ser editado. Para fazer alterações, será necessário criar um novo documento.
                    </p>
                </div>
            </div>
            @endif
        </div>

    <form id="formDocumentoEdit" method="POST" action="{{ route('admin.documentos.update', $documento->id) }}" @submit="handleSubmit">
        @csrf
        @method('PUT')
        
        {{-- Campo hidden para o conteúdo do editor --}}
        <input type="hidden" name="conteudo" x-model="conteudo" form="formDocumentoEdit">

        {{-- Seção: Tipo de Documento --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-3">
            <div class="px-3 py-2 bg-gradient-to-r from-blue-50 to-white border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                    <span class="flex items-center justify-center w-4 h-4 bg-blue-600 text-white rounded-full text-xs font-bold">1</span>
                    Tipo de Documento
                </h2>
            </div>
            <div class="p-3">
                <input type="hidden" name="tipo_documento_id" value="{{ $documento->tipo_documento_id }}" form="formDocumentoEdit">
                <div class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm text-gray-700">
                    {{ $documento->tipoDocumento?->nome ?? 'Tipo não informado' }}
                </div>
                <p class="text-xs text-amber-600 mt-1.5 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    O tipo de documento não pode ser alterado após a criação
                </p>

                @if(isset($pastasProcesso) && $pastasProcesso->isNotEmpty())
                    @php
                        $pastaSelecionada = old('pasta_id', $documento->pasta_id);
                    @endphp
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <label for="pasta_id" class="block text-sm font-medium text-gray-700 mb-2">Pasta do Processo</label>
                        <select name="pasta_id"
                                id="pasta_id"
                                form="formDocumentoEdit"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">Todos (sem pasta)</option>
                            @foreach($pastasProcesso as $pasta)
                                <option value="{{ $pasta->id }}" {{ (string) $pastaSelecionada === (string) $pasta->id ? 'selected' : '' }}>
                                    {{ $pasta->nome }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1.5">Opcional: altere a pasta para organizar o documento na listagem do processo.</p>
                    </div>
                @endif

                {{-- Toggle Sigiloso --}}
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
                            <input type="checkbox" name="sigiloso" value="1" x-model="sigiloso" form="formDocumentoEdit" class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-600"></div>
                        </label>
                    </div>
                </div>
                @endif

                {{-- Prazo em Dias (aparece quando documento tem prazo) --}}
                @if($documento->prazo_dias || ($documento->tipoDocumento && $documento->tipoDocumento->tem_prazo))
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-3 mb-3 rounded">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm text-blue-800">
                                <p class="font-semibold">Este documento possui prazo de validade</p>
                                @if($documento->data_vencimento)
                                <p class="text-xs mt-0.5">Vencimento atual: <strong>{{ $documento->data_vencimento->format('d/m/Y') }}</strong></p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="prazo_dias" class="block text-sm font-medium text-gray-700 mb-2">
                                Prazo em Dias <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="number" 
                                       name="prazo_dias" 
                                       id="prazo_dias"
                                       value="{{ old('prazo_dias', $documento->prazo_dias) }}"
                                       form="formDocumentoEdit"
                                       min="1"
                                       class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                       placeholder="Ex: 365">
                                <span class="text-sm text-gray-600">dias</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Exemplos: Alvará (365 dias), Notificação (30 dias)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Prazo</label>
                            <div class="space-y-2">
                                <label class="flex items-center cursor-pointer p-2 border border-gray-300 rounded-lg hover:bg-blue-50 transition">
                                    <input type="radio" 
                                           name="tipo_prazo" 
                                           value="corridos"
                                           form="formDocumentoEdit"
                                           {{ old('tipo_prazo', $documento->tipo_prazo ?? 'corridos') === 'corridos' ? 'checked' : '' }}
                                           class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-xs text-gray-700"><strong>Dias Corridos</strong> - Todos os dias</span>
                                </label>
                                <label class="flex items-center cursor-pointer p-2 border border-gray-300 rounded-lg hover:bg-blue-50 transition">
                                    <input type="radio" 
                                           name="tipo_prazo" 
                                           value="uteis"
                                           form="formDocumentoEdit"
                                           {{ old('tipo_prazo', $documento->tipo_prazo ?? 'corridos') === 'uteis' ? 'checked' : '' }}
                                           class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-xs text-gray-700"><strong>Dias Úteis</strong> - Excluindo finais de semana</span>
                                </label>
                            </div>
                        </div>
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

                <div class="mb-2 flex items-center gap-2 relative" style="min-height: 24px;">
                    <span x-show="salvandoAuto" x-transition.opacity class="text-sm text-green-600 flex items-center gap-1.5 font-medium absolute left-0 top-0">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Salvando...
                    </span>
                </div>

                <!-- Editor de documento -->
                <textarea id="editor-tinymce" style="visibility: hidden;"></textarea>
                <input type="hidden" name="conteudo" x-model="conteudo">

            </div>
        </div>

        @include('documentos.partials.itens-atendimento-form')

        {{-- Seção: Histórico de Versões (Completo com Restaurar) --}}
        @if(($totalVersoes ?? $documento->versoes->count()) > 0)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-3"
             x-data="historicoVersoes({{ $documento->id }})">
            <div class="px-3 py-2 bg-gradient-to-r from-orange-50 to-white border-b border-gray-200 cursor-pointer" @click="historicoAberto = !historicoAberto">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                        <span class="flex items-center justify-center w-4 h-4 bg-orange-600 text-white rounded-full text-xs font-bold">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        Histórico de Versões
                        <span class="px-1.5 py-0.5 bg-orange-100 text-orange-700 text-xs font-semibold rounded-full">{{ $totalVersoes ?? $documento->versoes->count() }}</span>
                    </h2>
                    <svg class="w-4 h-4 text-gray-500 transition-transform" :class="historicoAberto ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>
            <div x-show="historicoAberto" x-transition>
                <div class="p-3">
                    <p class="text-xs text-gray-500 mb-2">Exibindo as {{ $documento->versoes->count() }} versões mais recentes (máximo de 10).</p>
                    <div class="space-y-2">
                        @foreach($documento->versoes as $versao)
                        <div class="border border-gray-200 rounded-lg p-2.5 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded">
                                            Versão {{ $versao->versao }}
                                        </span>
                                        <div class="flex items-center gap-2 text-sm text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            <span class="font-medium text-gray-900">{{ $versao->usuarioInterno?->nome ?? 'Usuário indisponível' }}</span>
                                        </div>
                                        <div class="flex items-center gap-1 text-xs text-gray-500">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ $versao->created_at->format('d/m/Y H:i') }}
                                            <span class="text-gray-400">•</span>
                                            {{ $versao->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-1.5">
                                    <button type="button"
                                            @click="toggleVersaoConteudo({{ $versao->id }})"
                                            class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors">
                                        <span x-show="versaoAbertaId !== {{ $versao->id }}">Ver</span>
                                        <span x-show="versaoAbertaId === {{ $versao->id }}">Ocultar</span>
                                    </button>
                                    
                                    <form action="{{ route('admin.documentos.restaurarVersao', [$documento->id, $versao->id]) }}" 
                                          method="POST" 
                                          onsubmit="return confirm('Tem certeza que deseja restaurar esta versão? O conteúdo atual será substituído.')">
                                        @csrf
                                        <button type="submit" 
                                                class="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            Restaurar
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div x-show="versaoAbertaId === {{ $versao->id }}" x-transition class="mt-2 pt-2 border-t border-gray-200">
                                <template x-if="carregandoVersaoId === {{ $versao->id }}">
                                    <p class="text-xs text-gray-500">Carregando conteúdo da versão...</p>
                                </template>
                                <div x-show="conteudosVersao[{{ $versao->id }}]"
                                     x-html="conteudosVersao[{{ $versao->id }}]"
                                     class="bg-gray-50 rounded p-2 max-h-40 overflow-y-auto text-xs documento-conteudo-preservado"
                                     style="font-family: 'Times New Roman', serif;"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Seção: Assinaturas Digitais --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-3">
            <div class="px-3 py-2 bg-gradient-to-r from-purple-50 to-white border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                        <span class="flex items-center justify-center w-4 h-4 bg-purple-600 text-white rounded-full text-xs font-bold">3</span>
                        Assinaturas Digitais
                    </h2>
                    <span class="px-1.5 py-0.5 bg-red-100 text-red-700 text-xs font-semibold rounded-full">Obrigatório</span>
                </div>
            </div>
            <div class="p-3">
                @include('documentos.partials.selecao-assinantes', [
                    'usuariosInternos' => $usuariosInternos,
                    'assinantesSelecionados' => old('assinaturas', $documento->assinaturas->pluck('usuario_interno_id')->all()),
                    'formAssinaturas' => 'formDocumentoEdit',
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
                </div>

                <div class="flex gap-3 w-full sm:w-auto">
                    @if($permiteSalvarRascunho)
                    <button type="submit" 
                            form="formDocumentoEdit"
                            name="acao" 
                            value="rascunho"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        Salvar Rascunho
                    </button>
                    @endif
                    
                    <button type="submit" 
                            form="formDocumentoEdit"
                            name="acao" 
                            value="finalizar"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg hover:from-blue-700 hover:to-blue-800 shadow-lg hover:shadow-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $permiteSalvarRascunho ? 'Finalizar Documento' : 'Salvar Alterações' }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@php
    $itensAtendimentoSalvos = $documento->itensAtendimento
        ->map(function ($item) {
            return [
                'area' => $item->area ?? '',
                'descricao' => $item->descricao,
                'embasamento_legal' => $item->embasamento_legal ?? '',
            ];
        })
        ->all();
    $colaboradoresExigenciasSalvos = $documento->exigenciaColaboradores
        ->map(function ($colaborador) {
            return [
                'usuario_interno_id' => $colaborador->usuario_interno_id,
                'area' => $colaborador->area ?? '',
                'prazo_interno' => $colaborador->prazo_interno?->format('Y-m-d') ?? '',
            ];
        })
        ->all();
    $itensAtendimentoIniciais = [];
    $colaboradoresExigenciasIniciais = [];

    foreach (old('itens_atendimento', $itensAtendimentoSalvos) as $indice => $item) {
        $itensAtendimentoIniciais[] = [
            'chave' => 'item_' . $indice,
            'area' => $item['area'] ?? '',
            'descricao' => $item['descricao'] ?? '',
            'embasamento_legal' => $item['embasamento_legal'] ?? '',
        ];
    }

    foreach (old('colaboradores_exigencias', $colaboradoresExigenciasSalvos) as $indice => $colaborador) {
        $colaboradoresExigenciasIniciais[] = [
            'chave' => 'colab_' . $indice,
            'usuario_interno_id' => $colaborador['usuario_interno_id'] ?? '',
            'area' => $colaborador['area'] ?? '',
            'prazo_interno' => $colaborador['prazo_interno'] ?? '',
        ];
    }
@endphp

<script>
function historicoVersoes(documentoId) {
    return {
        historicoAberto: false,
        versaoAbertaId: null,
        conteudosVersao: {},
        carregandoVersaoId: null,
        versaoConteudoUrl(versaoId) {
            return @json(url('/admin/documentos')) + '/' + documentoId + '/versoes/' + versaoId + '/conteudo';
        },
        async toggleVersaoConteudo(versaoId) {
            if (this.versaoAbertaId === versaoId) {
                this.versaoAbertaId = null;
                return;
            }

            this.versaoAbertaId = versaoId;

            if (this.conteudosVersao[versaoId]) {
                return;
            }

            this.carregandoVersaoId = versaoId;

            try {
                const response = await fetch(this.versaoConteudoUrl(versaoId), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();
                this.conteudosVersao[versaoId] = data.success
                    ? data.conteudo
                    : '<p class="text-red-600 text-xs">Não foi possível carregar esta versão.</p>';
            } catch (error) {
                this.conteudosVersao[versaoId] = '<p class="text-red-600 text-xs">Erro ao carregar esta versão.</p>';
            } finally {
                this.carregandoVersaoId = null;
            }
        }
    };
}

function documentoEditor() {
    return {
        tipoSelecionado: {{ $documento->tipo_documento_id }},
        sigiloso: {{ $documento->sigiloso ? 'true' : 'false' }},
        conteudo: '',
        exigeItensAtendimento: {{ $documento->tipoDocumento?->exige_itens_atendimento ? 'true' : 'false' }},
        itensAtendimento: @json($itensAtendimentoIniciais),
        colaboradoresExigencias: @json($colaboradoresExigenciasIniciais),
        modalColaboradoresExigenciasAberto: false,
        modalItemAtendimentoAberto: false,
        modalItemAtendimentoIndice: null,
        modalItemAtendimento: { area: '', descricao: '', embasamento_legal: '' },
        modalItensAtendimento: [],
        modalTextoExigenciasEmLote: '',
        modelos: [],
        salvandoAuto: false,
        ultimoSalvo: '',
        timeoutSalvar: null,
        contadorErros: 0,
        timeoutVerificacao: null,
        chaveLocalStorage: 'documento_rascunho_{{ request()->get("processo_id", "novo") }}',

        init() {
            const self = this;
            this.tipoSelecionado = {{ $documento->tipo_documento_id ?? 'null' }};
            this.conteudo = {!! json_encode($documento->conteudo) !!};
            
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
                    table { border-collapse: collapse; width: 100%; cursor: text; }
                    table td, table th { border: 1px solid #ddd; padding: 8px; cursor: text; }
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
                branding: false,
                promotion: false,
                browser_spellcheck: true,
                contextmenu: false,
                statusbar: true,
                elementpath: true,
                setup: (editor) => {
                    // Botão de Correção Ortográfica na toolbar
                    editor.ui.registry.addButton('spellcheck_btn', {
                        icon: 'spell-check',
                        tooltip: 'Verificar Ortografia (PT-BR)',
                        onAction: () => self.verificarOrtografia()
                    });
                    editor.on('init', () => {
                        editor.setContent(self.conteudo || '');
                    });
                    
                    editor.on('input change keyup', () => {
                        self.conteudo = editor.getContent();
                        self.salvarAutomaticamente();
                        self.verificarErrosTempoReal();
                    });
                    
                    window._tinymceEditor = editor;
                }
            });
            
            // Inicializa sistema de edição colaborativa
            this.edicaoColaborativa = new EdicaoColaborativa(
                {{ $documento->id }},
                '{{ auth("interno")->user()->nome }}'
            );
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

        salvarAutomaticamente() {
            clearTimeout(this.timeoutSalvar);
            this.salvandoAuto = true;
            
            this.timeoutSalvar = setTimeout(() => {
                const dados = {
                    conteudo: this.conteudo,
                    timestamp: Date.now()
                };
                localStorage.setItem(this.chaveLocalStorage, JSON.stringify(dados));
                this.salvandoAuto = false;
                this.ultimoSalvo = 'agora';
            }, 1000);
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
            event.target.value = '';
        },

        handlePaste(event) {
            // TinyMCE handles paste natively
        },

        inserirTabela() {
            const editor = tinymce.get('editor-tinymce');
            if (editor) {
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

        async carregarModelos(tipoId) {
            if (!tipoId) return;
            
            try {
                const response = await fetch(`${window.APP_URL}/admin/documentos/modelos/${tipoId}`);
                
                if (!response.ok) {
                    console.warn('Nenhum modelo encontrado para este tipo de documento');
                    return;
                }
                
                this.modelos = await response.json();
                
                if (this.modelos && this.modelos.length > 0) {
                    this.conteudo = this.modelos[0].conteudo;
                    const editor = tinymce.get('editor-tinymce');
                    if (editor) {
                        editor.setContent(this.conteudo);
                        this.salvarAutomaticamente();
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar modelos:', error);
            }
        },

        contarPalavras() {
            const texto = this.conteudo.replace(/<[^>]*>/g, '').trim();
            return texto.split(/\s+/).filter(word => word.length > 0).length;
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
            }, 2000);
        },

        async verificarOrtografia(event = null) {
            const tmceEditor = tinymce.get('editor-tinymce');
            if (!tmceEditor) return;
            const texto = tmceEditor.getContent({ format: 'text' });
            
            if (!texto.trim()) {
                alert('Digite algum texto para verificar a ortografia.');
                return;
            }

            const btnVerificar = event?.target?.closest?.('button') || null;
            const originalHTML = btnVerificar ? btnVerificar.innerHTML : '';
            if (btnVerificar) {
                btnVerificar.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                btnVerificar.disabled = true;
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
                
                if (data.matches && data.matches.length > 0) {
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
                    
                    window.substituirPalavra = (palavraErrada, sugestao, index) => {
                        const tmceEd = tinymce.get('editor-tinymce');
                        if (!tmceEd) return;
                        let conteudo = tmceEd.getContent();
                        const regex = new RegExp(`\\b${palavraErrada.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'gi');
                        let substituido = false;
                        conteudo = conteudo.replace(regex, (match) => {
                            if (!substituido) {
                                substituido = true;
                                return sugestao;
                            }
                            return match;
                        });
                        tmceEd.setContent(conteudo);
                        this.conteudo = conteudo;
                        this.salvarAutomaticamente();
                        
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
                            const errosRestantes = document.querySelectorAll('#lista-erros li[style*="border-left: 3px solid rgb(239, 68, 68)"]').length;
                            document.getElementById('contador-erros').textContent = errosRestantes;
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
                if (btnVerificar) {
                    btnVerificar.innerHTML = originalHTML;
                    btnVerificar.disabled = false;
                }
            }
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

            this.enviandoImagens = true;
            try {
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
            } finally {
                this.enviandoImagens = false;
            }
        },

        async handleSubmit(event) {
            event.preventDefault();

            const assinaturas = document.querySelectorAll('input[name="assinaturas[]"]');
            if (assinaturas.length === 0) {
                alert('Selecione pelo menos um usuário para assinar o documento!');
                return false;
            }

            // Converte fotos base64 → upload no servidor antes do POST (evita 405 por body grande)
            await this.externalizarImagensBase64();

            const editor = tinymce.get('editor-tinymce');
            if (editor) {
                this.conteudo = editor.getContent();
            }

            const form = document.getElementById('formDocumentoEdit');
            const inputConteudo = form.querySelector('input[name="conteudo"]');
            if (inputConteudo) {
                inputConteudo.value = this.conteudo || '';
            }

            const acaoInput = event.submitter;
            if (acaoInput && acaoInput.name === 'acao') {
                // Garante o botão clicado no submit programático
                let hidden = form.querySelector('input[type="hidden"][name="acao"][data-submit-shadow]');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'acao';
                    hidden.setAttribute('data-submit-shadow', '1');
                    form.appendChild(hidden);
                }
                hidden.value = acaoInput.value;
                if (acaoInput.value === 'finalizar') {
                    localStorage.removeItem(this.chaveLocalStorage);
                }
            }

            form.submit();
            return true;
        }
    }
}
</script>

{{-- Assistente de Redação --}}
@include('components.assistente-edicao-documento-chat')
@endsection
