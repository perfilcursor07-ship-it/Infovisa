@extends('layouts.admin')

@section('title', 'Configurações do Sistema')
@section('page-title', 'Configurações Gerais do Sistema')

@section('content')
<div class="max-w-8xl mx-auto">
    
    {{-- Cabeçalho --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
            <div class="p-2 bg-purple-100 rounded-lg">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            Configurações do Sistema
        </h1>
        <p class="mt-2 text-gray-600">Gerencie as configurações globais do sistema INFOVISA</p>
    </div>

    {{-- Campo de busca dinâmica --}}
    <div class="mb-6">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input type="text"
                   id="busca-configuracoes"
                   placeholder="Buscar configuração... (ex: logomarca, IA, chat, rodapé, redação)"
                   class="w-full pl-12 pr-10 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm bg-white shadow-sm"
                   autocomplete="off">
            <button type="button" id="limpar-busca" class="absolute inset-y-0 right-0 pr-4 flex items-center hidden" title="Limpar busca">
                <svg class="w-5 h-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div id="busca-sem-resultado" class="hidden mt-3 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800 text-center">
            Nenhuma configuração encontrada para o termo pesquisado.
        </div>
    </div>

    {{-- Navegação por Abas --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="flex space-x-1 -mb-px" aria-label="Categorias de configuração">
            <button type="button" 
                    onclick="trocarAba('identidade-visual')"
                    id="tab-identidade-visual"
                    class="tab-btn group inline-flex items-center gap-2 px-5 py-3 border-b-2 border-purple-600 text-purple-600 font-medium text-sm transition-colors"
                    aria-selected="true"
                    role="tab">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Identidade Visual
            </button>
            <button type="button" 
                    onclick="trocarAba('inteligencia-artificial')"
                    id="tab-inteligencia-artificial"
                    class="tab-btn group inline-flex items-center gap-2 px-5 py-3 border-b-2 border-transparent text-gray-500 hover:text-blue-600 hover:border-blue-300 font-medium text-sm transition-colors"
                    aria-selected="false"
                    role="tab">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                Inteligência Artificial
            </button>
            <button type="button" 
                    onclick="trocarAba('comunicacao')"
                    id="tab-comunicacao"
                    class="tab-btn group inline-flex items-center gap-2 px-5 py-3 border-b-2 border-transparent text-gray-500 hover:text-green-600 hover:border-green-300 font-medium text-sm transition-colors"
                    aria-selected="false"
                    role="tab">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                Comunicação
            </button>
            <button type="button" 
                    onclick="trocarAba('permissoes')"
                    id="tab-permissoes"
                    class="tab-btn group inline-flex items-center gap-2 px-5 py-3 border-b-2 border-transparent text-gray-500 hover:text-red-600 hover:border-red-300 font-medium text-sm transition-colors"
                    aria-selected="false"
                    role="tab">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Permissões
            </button>
        </nav>
    </div>

    {{-- ============================================================ --}}
    {{-- ABA: Identidade Visual                                       --}}
    {{-- ============================================================ --}}
    <div id="aba-identidade-visual" class="tab-content" data-tab="identidade-visual">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6 config-section" data-search="identidade visual logomarca estadual logo imagem upload marca">
            <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-white border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Identidade Visual Estadual
                </h2>
                <p class="text-sm text-gray-600 mt-1">Configure a logomarca e a imagem de rodapé padrão usadas nos documentos e PDFs estaduais</p>
            </div>

            <div class="p-6">
                <form action="{{ route('admin.configuracoes.sistema.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-3">Logomarca Estadual</h3>

                        @if($logomarcaEstadual && $logomarcaEstadual->valor)
                            <div class="mb-6 p-4 bg-purple-50 rounded-lg border border-purple-200">
                            <div class="flex items-start gap-4">
                                <img src="{{ asset($logomarcaEstadual->valor) }}" 
                                     alt="Logomarca do Estado do Tocantins"
                                     class="w-40 h-40 object-contain bg-white border-2 border-purple-300 rounded-lg p-3 shadow-sm">
                                <div class="flex-1">
                                    <p class="text-sm text-purple-900 font-semibold mb-2">
                                        <svg class="inline w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Logomarca Estadual Configurada
                                    </p>
                                    <p class="text-xs text-purple-700 mb-3">
                                        Esta logomarca aparecerá automaticamente nos documentos criados por:
                                    </p>
                                    <ul class="text-xs text-purple-700 space-y-1 mb-4">
                                        <li class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            <strong>Gestor Estadual</strong>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            <strong>Técnico Estadual</strong>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            Usuários sem município vinculado
                                        </li>
                                    </ul>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" 
                                               name="remover_logomarca_estadual" 
                                               value="1"
                                               class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                        <span class="ml-2 text-sm text-red-600 font-medium">Remover logomarca estadual</span>
                                    </label>
                                </div>
                            </div>
                            </div>
                        @else
                            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-yellow-800">Nenhuma logomarca estadual configurada</p>
                                    <p class="text-xs text-yellow-700 mt-1">
                                        Documentos criados por <strong>Gestores Estaduais</strong> e <strong>Técnicos Estaduais</strong> não terão logomarca até que você configure uma.
                                    </p>
                                </div>
                            </div>
                            </div>
                        @endif

                        <div class="space-y-4">
                            <div class="block">
                                <span class="text-sm font-medium text-gray-700 mb-2 block">
                                    {{ $logomarcaEstadual && $logomarcaEstadual->valor ? 'Substituir Logomarca' : 'Fazer Upload da Logomarca' }}
                                </span>
                                <div class="flex items-center justify-center w-full px-6 py-8 border-2 border-dashed border-gray-300 rounded-lg hover:border-purple-400 hover:bg-purple-50 transition-colors cursor-pointer"
                                     onclick="document.getElementById('input-logomarca-estadual').click()">
                                    <div class="text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-600">
                                            <span class="font-semibold text-purple-600">Clique para selecionar</span> ou arraste a imagem
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500">PNG, JPG, JPEG ou SVG (máx. 2MB)</p>
                                        <p class="mt-2 text-xs text-gray-600 bg-gray-100 inline-block px-3 py-1 rounded-full">
                                            Recomendado: 400x400px ou maior
                                        </p>
                                    </div>
                                </div>
                                <input type="file" 
                                       id="input-logomarca-estadual"
                                       name="logomarca_estadual" 
                                       accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                                       class="hidden"
                                       onchange="previewImagem(event, 'preview-image-estadual', 'preview-container-estadual')">
                            </div>

                            <div id="preview-container-estadual" class="hidden p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <p class="text-sm text-blue-700 font-semibold mb-3">Prévia da nova logomarca:</p>
                                <img id="preview-image-estadual" src="" alt="Prévia" class="w-40 h-40 object-contain bg-white border border-gray-300 rounded-lg p-3 mx-auto">
                            </div>

                            @error('logomarca_estadual')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900 mb-3">Rodapé Estadual</h3>

                        @if($rodapeEstadual && $rodapeEstadual->valor)
                            <div class="mb-6 p-4 bg-purple-50 rounded-lg border border-purple-200">
                                <div class="flex items-start gap-4">
                                    <img src="{{ asset($rodapeEstadual->valor) }}"
                                         alt="Rodapé do Estado do Tocantins"
                                         class="w-full max-w-md h-auto object-contain bg-white border-2 border-purple-300 rounded-lg p-3 shadow-sm">
                                    <div class="flex-1">
                                        <p class="text-sm text-purple-900 font-semibold mb-2">Rodapé estadual configurado</p>
                                        <p class="text-xs text-purple-700 mb-4">Esta imagem será usada como rodapé padrão dos PDFs quando o município não tiver um rodapé próprio cadastrado.</p>
                                        <label class="flex items-center cursor-pointer">
                                            <input type="checkbox"
                                                   name="remover_rodape_estadual"
                                                   value="1"
                                                   class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                            <span class="ml-2 text-sm text-red-600 font-medium">Remover rodapé estadual</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <p class="text-sm font-semibold text-yellow-800">Nenhum rodapé estadual personalizado configurado</p>
                                <p class="text-xs text-yellow-700 mt-1">Enquanto não houver upload aqui, o sistema continua usando o rodapé padrão já existente do estado.</p>
                            </div>
                        @endif

                        <div class="space-y-4">
                            <div class="block">
                                <span class="text-sm font-medium text-gray-700 mb-2 block">
                                    {{ $rodapeEstadual && $rodapeEstadual->valor ? 'Substituir Rodapé' : 'Fazer Upload do Rodapé' }}
                                </span>
                                <div class="flex items-center justify-center w-full px-6 py-8 border-2 border-dashed border-gray-300 rounded-lg hover:border-purple-400 hover:bg-purple-50 transition-colors cursor-pointer"
                                     onclick="document.getElementById('input-rodape-estadual').click()">
                                    <div class="text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-600">
                                            <span class="font-semibold text-purple-600">Clique para selecionar</span> ou arraste a imagem
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500">PNG, JPG, JPEG ou SVG (máx. 4MB)</p>
                                        <p class="mt-2 text-xs text-gray-600 bg-gray-100 inline-block px-3 py-1 rounded-full">
                                            Recomendado: imagem horizontal com boa resolução
                                        </p>
                                    </div>
                                </div>
                                <input type="file"
                                       id="input-rodape-estadual"
                                       name="rodape_estadual"
                                       accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                                       class="hidden"
                                       onchange="previewImagem(event, 'preview-image-rodape-estadual', 'preview-container-rodape-estadual')">
                            </div>

                            <div id="preview-container-rodape-estadual" class="hidden p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <p class="text-sm text-blue-700 font-semibold mb-3">Prévia do novo rodapé:</p>
                                <img id="preview-image-rodape-estadual" src="" alt="Prévia do rodapé" class="w-full max-w-md h-auto object-contain bg-white border border-gray-300 rounded-lg p-3 mx-auto">
                            </div>

                            @error('rodape_estadual')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-6">
                            <label for="rodape_texto_padrao" class="block text-sm font-medium text-gray-700 mb-2">
                                Texto Padrão do Rodapé
                            </label>
                            <textarea id="rodape_texto_padrao"
                                      name="rodape_texto_padrao"
                                      rows="5"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 @error('rodape_texto_padrao') border-red-500 @enderror"
                                      placeholder="Informe o texto padrão que será usado no rodapé dos documentos">{{ old('rodape_texto_padrao', $rodapeTextoPadrao) }}</textarea>
                            <p class="mt-2 text-xs text-gray-500">Esse texto é usado nos documentos estaduais e como padrão para municípios que não definirem um texto próprio.</p>
                            @error('rodape_texto_padrao')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200">
                        <a href="{{ route('admin.dashboard') }}" 
                           class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            Voltar
                        </a>
                        <button type="submit" 
                                class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Salvar Identidade Visual
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Informações sobre identidade visual --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 config-section" data-search="identidade visual informações documentos municipal estadual fallback">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-blue-800">
                    <p class="font-semibold mb-2">Como funciona a identidade visual nos documentos:</p>
                    <ul class="space-y-1 text-xs">
                        <li>• <strong>Usuários Municipais</strong>: usam a logomarca e o rodapé do município quando existirem</li>
                        <li>• <strong>Usuários Estaduais</strong>: usam a identidade visual estadual configurada aqui</li>
                        <li>• <strong>Fallback</strong>: quando o município não tiver rodapé próprio, o sistema usa o rodapé estadual padrão</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- ABA: Inteligência Artificial (Unificada)                     --}}
    {{-- ============================================================ --}}
    <div id="aba-inteligencia-artificial" class="tab-content hidden" data-tab="inteligencia-artificial">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6 config-section" data-search="inteligência artificial ia assistente redação pesquisa satisfação api key modelo openai busca web chatgpt externo interno módulos">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-white border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    Inteligência Artificial
                </h2>
                <p class="text-sm text-gray-600 mt-1">Gerencie todas as funcionalidades de IA do sistema em um só lugar</p>
            </div>

            <div class="p-6">
                <form action="{{ route('admin.configuracoes.sistema.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_form_ia" value="1">

                    {{-- ============================== --}}
                    {{-- SEÇÃO 1: Módulos de IA         --}}
                    {{-- ============================== --}}
                    <div class="mb-8">
                        <h3 class="text-base font-semibold text-gray-900 mb-1 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Módulos de IA
                        </h3>
                        <p class="text-xs text-gray-500 mb-4">Ative ou desative cada funcionalidade de inteligência artificial do sistema</p>

                        <div class="space-y-3">
                            {{-- Assistente IA - Interno --}}
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex-1 pr-4">
                                    <label class="text-sm font-medium text-gray-900">Assistente IA — Usuário Interno</label>
                                    <p class="text-xs text-gray-600 mt-1">Chat de IA para os usuários internos (área administrativa)</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="ia_ativa" value="1"
                                           {{ $iaAtiva && $iaAtiva->valor === 'true' ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>
                                    <span class="ml-3 text-sm font-medium text-gray-900">{{ $iaAtiva && $iaAtiva->valor === 'true' ? 'Ativo' : 'Inativo' }}</span>
                                </label>
                            </div>

                            {{-- Assistente IA - Externo --}}
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex-1 pr-4">
                                    <label class="text-sm font-medium text-gray-900">Assistente IA — Usuário Externo</label>
                                    <p class="text-xs text-gray-600 mt-1">Assistente no painel externo (/company/dashboard) para orientar cadastro, processos e documentos</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="ia_externa_ativa" value="1"
                                           {{ $iaExternaAtiva && $iaExternaAtiva->valor === 'true' ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>
                                    <span class="ml-3 text-sm font-medium text-gray-900">{{ $iaExternaAtiva && $iaExternaAtiva->valor === 'true' ? 'Ativo' : 'Inativo' }}</span>
                                </label>
                            </div>

                            {{-- Assistente de Redação --}}
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex-1 pr-4">
                                    <label class="text-sm font-medium text-gray-900">Assistente de Redação</label>
                                    <p class="text-xs text-gray-600 mt-1">IA para auxiliar na redação de documentos oficiais</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="assistente_redacao_ativo" value="1"
                                           {{ $assistenteRedacaoAtivo && $assistenteRedacaoAtivo->valor === 'true' ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>
                                    <span class="ml-3 text-sm font-medium text-gray-900">{{ $assistenteRedacaoAtivo && $assistenteRedacaoAtivo->valor === 'true' ? 'Ativo' : 'Inativo' }}</span>
                                </label>
                            </div>

                            {{-- Pesquisa de Satisfação --}}
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex-1 pr-4">
                                    <label class="text-sm font-medium text-gray-900">Análise IA — Pesquisa de Satisfação</label>
                                    <p class="text-xs text-gray-600 mt-1">Botão "Gerar Análise com IA" nos relatórios de pesquisa de satisfação</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="ia_pesquisa_satisfacao_ativa" value="1"
                                           {{ $iaPesquisaSatisfacaoAtiva && $iaPesquisaSatisfacaoAtiva->valor === 'true' ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>
                                    <span class="ml-3 text-sm font-medium text-gray-900">{{ $iaPesquisaSatisfacaoAtiva && $iaPesquisaSatisfacaoAtiva->valor === 'true' ? 'Ativo' : 'Inativo' }}</span>
                                </label>
                            </div>

                            {{-- Busca na Internet --}}
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex-1 pr-4">
                                    <label class="text-sm font-medium text-gray-900">Busca Complementar na Internet</label>
                                    <p class="text-xs text-gray-600 mt-1">Busca em sites oficiais (ANVISA, Diário Oficial) quando não houver documentos POPs relevantes</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="ia_busca_web" value="1"
                                           {{ $iaBuscaWeb && $iaBuscaWeb->valor === 'true' ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>
                                    <span class="ml-3 text-sm font-medium text-gray-900">{{ $iaBuscaWeb && $iaBuscaWeb->valor === 'true' ? 'Ativo' : 'Inativo' }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- ============================== --}}
                    {{-- SEÇÃO 2: Configuração da API   --}}
                    {{-- ============================== --}}
                    <div class="mb-8 pt-6 border-t border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900 mb-1 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            </svg>
                            Configuração da API
                        </h3>
                        <p class="text-xs text-gray-500 mb-4">Credenciais e modelo usados por todas as funcionalidades de IA</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- API Key --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">API Key (Together AI)</label>
                                <input type="text" name="ia_api_key" value="{{ $iaApiKey->valor ?? '' }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Digite a chave de API">
                                <p class="text-xs text-gray-500 mt-1">Chave de autenticação para a API</p>
                            </div>

                            {{-- Modelo --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Modelo de IA</label>
                                <input type="text" name="ia_model" value="{{ $iaModel->valor ?? '' }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="meta-llama/Llama-3-70b-chat-hf">
                                <p class="text-xs text-gray-500 mt-1">Nome do modelo de linguagem</p>
                            </div>

                            {{-- API URL --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">URL da API</label>
                                <input type="url" name="ia_api_url" value="{{ $iaApiUrl->valor ?? '' }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="https://api.together.xyz/v1/chat/completions">
                                <p class="text-xs text-gray-500 mt-1">Endpoint da API do Together AI</p>
                            </div>
                        </div>
                    </div>

                    {{-- ============================== --}}
                    {{-- SEÇÃO 3: Pesquisa Satisfação   --}}
                    {{-- ============================== --}}
                    <div class="mb-6 pt-6 border-t border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900 mb-1 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Prompt da Pesquisa de Satisfação
                        </h3>
                        <p class="text-xs text-gray-500 mb-4">Instruções adicionais para personalizar a análise gerada pela IA</p>

                        <div>
                            <textarea id="ia_pesquisa_satisfacao_prompt"
                                      name="ia_pesquisa_satisfacao_prompt"
                                      rows="4"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Ex: Foque na comparação entre pesquisas, destaque indicadores abaixo de 3.0, sugira ações específicas para o setor de atendimento...">{{ old('ia_pesquisa_satisfacao_prompt', $iaPesquisaSatisfacaoPrompt->valor ?? '') }}</textarea>
                            <p class="mt-2 text-xs text-gray-500">Essas instruções serão adicionadas ao prompt padrão da IA ao gerar análises. Deixe em branco para usar o padrão.</p>
                        </div>
                    </div>

                    {{-- Informações --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm text-blue-800">
                                <p class="font-semibold mb-2">Sobre as funcionalidades de IA:</p>
                                <ul class="space-y-1 text-xs">
                                    <li>• <strong>Assistente Interno</strong>: apoio operacional da equipe da vigilância</li>
                                    <li>• <strong>Assistente Externo</strong>: orientação didática para empresas e responsáveis</li>
                                    <li>• <strong>Assistente de Redação</strong>: auxílio na elaboração de documentos oficiais</li>
                                    <li>• <strong>Pesquisa de Satisfação</strong>: análises estratégicas com base nos dados das pesquisas</li>
                                    <li>• <strong>Busca Web</strong>: consulta em sites oficiais (ANVISA, Diário Oficial) como complemento</li>
                                    <li>• Todos os módulos compartilham a mesma configuração de API acima</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                        <button type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Salvar Configurações de IA
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- IA da leitura de documentos do receituário (separada do assistente) --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6 config-section"
             data-search="ia leitura documentos receituário carteira conselho comprovante endereço groq gemini gratuita ocr"
             x-data="{
                testando: false,
                resultado: null,
                async testar() {
                    this.testando = true; this.resultado = null;
                    try {
                        const r = await fetch(@js(route('admin.configuracoes.sistema.testar-ia-documentos')), {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
                        });
                        this.resultado = await r.json();
                    } catch (e) {
                        this.resultado = { ok: false, etapa: 'navegador', mensagem: 'Não foi possível chamar o teste.' };
                    }
                    this.testando = false;
                },
                usar(url, modelo) { this.$refs.url.value = url; this.$refs.modelo.value = modelo; }
             }">
            <div class="px-6 py-4 bg-gradient-to-r from-emerald-50 to-white border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">IA da leitura de documentos (Receituário)</h2>
                <p class="text-sm text-gray-600 mt-1">
                    Usada só para ler a carteira do conselho e o comprovante de endereço no cadastro de receituário.
                    Se ficar em branco, usa a configuração de IA acima. Sem IA, a leitura continua funcionando por regras.
                </p>
            </div>
            <form action="{{ route('admin.configuracoes.sistema.update') }}" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form_ia_documentos" value="1">

                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="text-gray-500 self-center">Preencher com:</span>
                    <button type="button" @click="usar('http://localhost:11434/v1/chat/completions', 'qwen2.5:0.5b')"
                            class="px-2.5 py-1 rounded-full border border-emerald-300 bg-emerald-50 text-emerald-800 hover:bg-emerald-100"
                            title="IA instalada no próprio servidor: gratuita, sem limite, sem chave e os dados não saem do servidor">Ollama no servidor (100% gratuito)</button>
                    <button type="button" @click="usar('https://api.groq.com/openai/v1/chat/completions', 'llama-3.3-70b-versatile')"
                            class="px-2.5 py-1 rounded-full border border-gray-300 hover:bg-gray-50">Groq (gratuito)</button>
                    <button type="button" @click="usar('https://generativelanguage.googleapis.com/v1beta/openai/chat/completions', 'gemini-2.5-flash')"
                            class="px-2.5 py-1 rounded-full border border-gray-300 hover:bg-gray-50">Google Gemini (gratuito)</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL da API (compatível com OpenAI)</label>
                        <input type="url" name="ia_documentos_api_url" x-ref="url" value="{{ $iaDocumentos['url'] }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="https://api.groq.com/openai/v1/chat/completions">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Modelo</label>
                        <input type="text" name="ia_documentos_model" x-ref="modelo" value="{{ $iaDocumentos['model'] }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="llama-3.3-70b-versatile">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Chave da API <span class="font-normal text-gray-400">(não precisa para o Ollama)</span></label>
                        <input type="password" name="ia_documentos_api_key" autocomplete="new-password"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="{{ $iaDocumentos['tem_chave'] ? '•••••••• (salva — deixe em branco para manter)' : 'Cole a chave aqui' }}">
                        @if($iaDocumentos['tem_chave'])
                        <label class="mt-1 inline-flex items-center gap-1.5 text-xs text-gray-500">
                            <input type="checkbox" name="ia_documentos_remover_chave" value="1" class="rounded border-gray-300"> Remover chave salva
                        </label>
                        @endif
                    </div>
                </div>

                <div x-show="resultado" x-cloak class="rounded-lg border px-4 py-3 text-sm"
                     :class="resultado?.ok ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'">
                    <p class="font-semibold" x-text="resultado?.ok ? 'Conexão funcionando' : ('Falhou na etapa: ' + (resultado?.etapa || ''))"></p>
                    <p class="mt-0.5 break-words" x-text="resultado?.mensagem"></p>
                    <p class="mt-0.5 text-xs opacity-75" x-show="resultado?.origem" x-text="resultado?.origem === 'documentos' ? 'Usando a configuração desta seção.' : 'Usando a configuração geral de IA (esta seção está incompleta).'"></p>
                </div>

                <div class="flex flex-wrap justify-end gap-3 pt-2 border-t border-gray-100">
                    <button type="button" @click="testar()" :disabled="testando"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                        <span x-text="testando ? 'Testando…' : 'Testar conexão (configuração salva)'"></span>
                    </button>
                    <button type="submit" class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- ABA: Comunicação                                             --}}
    {{-- ============================================================ --}}
    <div id="aba-comunicacao" class="tab-content hidden" data-tab="comunicacao">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6 config-section" data-search="comunicação chat interno mensagens conversa tempo real arquivos">
            <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-white border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    Chat Interno
                </h2>
                <p class="text-sm text-gray-600 mt-1">Gerencie o chat de mensagens entre usuários internos do sistema</p>
            </div>

            <div class="p-6">
                <form action="{{ route('admin.configuracoes.sistema.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_form_chat" value="1">

                    <div class="space-y-6">
                        {{-- Ativar/Desativar Chat Interno --}}
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <label class="text-sm font-medium text-gray-900 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    Status do Chat Interno
                                </label>
                                <p class="text-xs text-gray-600 mt-1">Ative ou desative o chat interno para todos os usuários</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" 
                                       name="chat_interno_ativo" 
                                       value="1"
                                       {{ $chatInternoAtivo && $chatInternoAtivo->valor === 'true' ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-green-600"></div>
                                <span class="ml-3 text-sm font-medium text-gray-900">
                                    {{ $chatInternoAtivo && $chatInternoAtivo->valor === 'true' ? 'Ativo' : 'Inativo' }}
                                </span>
                            </label>
                        </div>

                        {{-- Informações --}}
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="text-sm text-green-800">
                                    <p class="font-semibold mb-2">Funcionalidades do Chat Interno:</p>
                                    <ul class="space-y-1 text-xs">
                                        <li>• Mensagens de texto entre usuários internos</li>
                                        <li>• Compartilhamento de arquivos e imagens</li>
                                        <li>• Status de digitação em tempo real</li>
                                        <li>• Indicador de mensagens lidas</li>
                                        <li>• Histórico de conversas</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200">
                        <button type="submit" 
                                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Salvar Configurações do Chat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
function previewImagem(event, imageId, containerId) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(imageId).src = e.target.result;
            document.getElementById(containerId).classList.remove('hidden');
        }
        reader.readAsDataURL(file);
    }
}

const tabColors = {
    'identidade-visual': { active: 'border-purple-600 text-purple-600', hover: 'hover:text-purple-600 hover:border-purple-300' },
    'inteligencia-artificial': { active: 'border-blue-600 text-blue-600', hover: 'hover:text-blue-600 hover:border-blue-300' },
    'comunicacao': { active: 'border-green-600 text-green-600', hover: 'hover:text-green-600 hover:border-green-300' },
};

function trocarAba(abaId) {
    // Esconder todas as abas
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    
    // Resetar todos os botões
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.setAttribute('aria-selected', 'false');
        // Remover classes ativas de todas as cores
        Object.values(tabColors).forEach(c => {
            c.active.split(' ').forEach(cls => btn.classList.remove(cls));
        });
        btn.classList.add('border-transparent', 'text-gray-500');
        // Adicionar hover da aba correspondente
        const btnAba = btn.id.replace('tab-', '');
        if (tabColors[btnAba]) {
            tabColors[btnAba].hover.split(' ').forEach(cls => btn.classList.add(cls));
        }
    });
    
    // Mostrar aba selecionada
    document.getElementById('aba-' + abaId).classList.remove('hidden');
    
    // Ativar botão
    const activeBtn = document.getElementById('tab-' + abaId);
    activeBtn.setAttribute('aria-selected', 'true');
    activeBtn.classList.remove('border-transparent', 'text-gray-500');
    // Remover hover classes
    Object.values(tabColors).forEach(c => {
        c.hover.split(' ').forEach(cls => activeBtn.classList.remove(cls));
    });
    // Adicionar classes ativas
    tabColors[abaId].active.split(' ').forEach(cls => activeBtn.classList.add(cls));
    
    // Salvar aba ativa no URL hash
    history.replaceState(null, null, '#' + abaId);
}

// Restaurar aba ativa do hash da URL
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById('aba-' + hash)) {
        trocarAba(hash);
    }
});

// ============================================================
// Busca dinâmica de configurações
// ============================================================
(function() {
    const inputBusca = document.getElementById('busca-configuracoes');
    const btnLimpar = document.getElementById('limpar-busca');
    const semResultado = document.getElementById('busca-sem-resultado');
    const tabNav = document.querySelector('nav[aria-label="Categorias de configuração"]');
    let buscaAtiva = false;

    function removerAcentos(str) {
        return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function filtrarConfiguracoes(termo) {
        const sections = document.querySelectorAll('.config-section');
        const tabs = document.querySelectorAll('.tab-content');
        const termoNorm = removerAcentos(termo.toLowerCase().trim());

        if (!termoNorm) {
            // Restaurar estado normal: esconder todas as abas, mostrar só a ativa
            buscaAtiva = false;
            tabNav.classList.remove('hidden');
            semResultado.classList.add('hidden');
            sections.forEach(s => {
                s.classList.remove('hidden');
                s.classList.remove('ring-2', 'ring-purple-300', 'ring-offset-2');
            });
            // Restaurar abas ao estado normal
            tabs.forEach(t => t.classList.add('hidden'));
            const hash = window.location.hash.replace('#', '');
            const abaAtiva = hash && document.getElementById('aba-' + hash) ? hash : 'identidade-visual';
            trocarAba(abaAtiva);
            return;
        }

        buscaAtiva = true;
        tabNav.classList.add('hidden');

        // Mostrar todas as abas para que as seções fiquem visíveis
        tabs.forEach(t => t.classList.remove('hidden'));

        let encontrou = false;
        sections.forEach(s => {
            const searchData = removerAcentos((s.getAttribute('data-search') || '').toLowerCase());
            const textoVisivel = removerAcentos((s.textContent || '').toLowerCase());
            const match = searchData.includes(termoNorm) || textoVisivel.includes(termoNorm);

            if (match) {
                s.classList.remove('hidden');
                s.classList.add('ring-2', 'ring-purple-300', 'ring-offset-2');
                encontrou = true;
            } else {
                s.classList.add('hidden');
                s.classList.remove('ring-2', 'ring-purple-300', 'ring-offset-2');
            }
        });

        // Esconder abas que ficaram sem seções visíveis
        tabs.forEach(t => {
            const visibleSections = t.querySelectorAll('.config-section:not(.hidden)');
            if (visibleSections.length === 0) {
                t.classList.add('hidden');
            }
        });

        semResultado.classList.toggle('hidden', encontrou);
    }

    inputBusca.addEventListener('input', function() {
        const termo = this.value;
        btnLimpar.classList.toggle('hidden', !termo);
        filtrarConfiguracoes(termo);
    });

    btnLimpar.addEventListener('click', function() {
        inputBusca.value = '';
        btnLimpar.classList.add('hidden');
        filtrarConfiguracoes('');
        inputBusca.focus();
    });
})();
</script>

{{-- ============================================================ --}}
{{-- ABA: Permissões                                              --}}
{{-- ============================================================ --}}
<div id="aba-permissoes" class="tab-content hidden" data-tab="permissoes">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6 config-section" data-search="permissões sigiloso documento sigilo nível acesso perfil">
        <div class="px-6 py-4 bg-gradient-to-r from-red-50 to-white border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Documento Sigiloso — Níveis de Acesso
            </h2>
            <p class="text-sm text-gray-600 mt-1">Defina quais níveis de acesso podem marcar um documento como sigiloso. Documentos sigilosos não são visíveis para o estabelecimento.</p>
        </div>
        <div class="p-6">
            <form action="{{ route('admin.configuracoes.sistema.salvar-permissoes-sigiloso') }}" method="POST">
                @csrf
                @php
                    $niveisPermitidos = json_decode(\App\Models\ConfiguracaoSistema::obter('niveis_permitidos_sigiloso', '["administrador","gestor_estadual","gestor_municipal"]'), true) ?? [];
                @endphp

                <div class="space-y-3">
                    @foreach(\App\Enums\NivelAcesso::cases() as $nivel)
                        @if($nivel !== \App\Enums\NivelAcesso::Administrador)
                        <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer transition">
                            <input type="checkbox" name="niveis[]" value="{{ $nivel->value }}"
                                   {{ in_array($nivel->value, $niveisPermitidos) ? 'checked' : '' }}
                                   class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $nivel->label() }}</p>
                                <p class="text-xs text-gray-500">{{ $nivel->descricao() }}</p>
                            </div>
                        </label>
                        @endif
                    @endforeach
                </div>

                <p class="mt-4 text-xs text-gray-500">
                    <strong>Nota:</strong> O Administrador sempre pode marcar documentos como sigiloso, independente desta configuração.
                </p>

                <div class="mt-6">
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                        Salvar Permissões
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
