@extends('layouts.company')

@section('title', 'Cadastrar Pessoa Jurídica')
@section('page-title', 'Cadastrar Pessoa Jurídica')

@section('content')
<div class="max-w-8xl mx-auto">
    {{-- Header --}}
    <div class="mb-5">
        <a href="{{ route('company.estabelecimentos.create') }}" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-700 mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Voltar
        </a>
        <p class="text-sm text-gray-600">Informe o CNPJ para carregar os dados da Receita Federal. Depois é só revisar e completar as etapas.</p>
    </div>

    {{-- Alerta Informativo --}}
    <div class="mb-5 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-blue-900">
                <span class="font-semibold">Processo de Aprovação:</span>
                após o cadastro, seu estabelecimento ficará com status <strong>Pendente</strong> até que a Vigilância Sanitária analise e aprove.
            </p>
        </div>
    </div>

    {{-- Modal de Erro do Servidor (Popup) --}}
    @if ($errors->any())
    <div x-data="{ showModal: true }" x-cloak>
        {{-- Overlay --}}
        <div x-show="showModal" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            
            {{-- Modal --}}
            <div x-show="showModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.away="showModal = false"
                 class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">
                
                {{-- Header com ícone --}}
                <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-5">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Não foi possível cadastrar</h3>
                            <p class="text-red-100 text-sm mt-0.5">Verifique as informações abaixo</p>
                        </div>
                    </div>
                </div>
                
                {{-- Conteúdo --}}
                <div class="px-6 py-5">
                    @if($errors->has('cidade') && str_contains($errors->first('cidade'), 'InfoVISA'))
                        {{-- Erro específico de município que não usa InfoVISA --}}
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900 mb-2">Município não habilitado</h4>
                                <p class="text-gray-600 text-sm leading-relaxed">{{ $errors->first('cidade') }}</p>
                            </div>
                        </div>
                    @else
                        {{-- Outros erros --}}
                        <ul class="space-y-3">
                            @foreach ($errors->all() as $error)
                            <li class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-5 h-5 bg-red-100 rounded-full flex items-center justify-center mt-0.5">
                                    <svg class="w-3 h-3 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                                <span class="text-gray-700 text-sm">{{ $error }}</span>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                
                {{-- Footer --}}
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <a href="{{ route('company.estabelecimentos.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Voltar aos Estabelecimentos
                    </a>
                    <button type="button" @click="showModal = false" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                        Entendi
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Formulário --}}
    <form id="formEstabelecimento" method="POST" action="{{ route('company.estabelecimentos.store') }}" 
          x-data="estabelecimentoFormCompany()" 
          @submit="handleSubmit($event)"
          class="space-y-6"
          novalidate>
        @csrf
        <input type="hidden" name="tipo_pessoa" value="juridica">

        {{-- Modal de Erros --}}
        <div x-cloak x-show="modalErro.visivel" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md bg-white rounded-xl shadow-2xl border border-red-200">
                <div class="flex items-start justify-between px-5 py-4 border-b border-gray-200">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Revisar campos obrigatórios</h3>
                        <p class="text-sm text-gray-500 mt-1">Preencha os itens abaixo para continuar.</p>
                    </div>
                    <button type="button" @click="fecharModalErro" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="px-5 py-4">
                    <ul class="space-y-2">
                        <template x-for="(erro, index) in modalErro.mensagens" :key="index">
                            <li class="flex items-start gap-2 text-sm text-gray-700">
                                <span class="text-red-500 font-semibold mt-0.5">•</span>
                                <span x-text="erro"></span>
                            </li>
                        </template>
                    </ul>
                </div>
                <div class="px-5 py-4 bg-gray-50 rounded-b-xl flex justify-end">
                    <button type="button" @click="fecharModalErro" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Entendi</button>
                </div>
            </div>
        </div>

        {{-- CNPJ já consultado (resumo compacto no lugar da busca) --}}
        <div x-show="dadosCarregados" x-cloak
             class="bg-white rounded-xl shadow-sm border border-gray-200 px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-green-100 text-green-600 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500">CNPJ consultado na Receita Federal</p>
                    <p class="text-sm font-semibold text-gray-900 truncate">
                        <span class="font-mono" x-text="dados.cnpj"></span>
                        <span class="text-gray-400 font-normal mx-1">·</span>
                        <span x-text="dados.razao_social"></span>
                    </p>
                </div>
            </div>
            <a href="{{ route('company.estabelecimentos.create.juridica') }}"
               onclick="return confirm('Consultar outro CNPJ? Os dados preenchidos até agora serão descartados.')"
               class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Consultar outro CNPJ
            </a>
        </div>

        {{-- Busca por CNPJ (oculta depois que os dados são carregados) --}}
        <div x-show="!dadosCarregados" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-9 h-9 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Consulta por CNPJ</h3>
                    <p class="text-xs text-gray-500">Os dados da empresa serão preenchidos automaticamente.</p>
                </div>
            </div>

            <label for="cnpj_busca" class="block text-sm font-medium text-gray-700 mb-1.5">
                CNPJ <span class="text-red-500">*</span>
            </label>
            <div class="flex flex-col sm:flex-row gap-3">
                <input type="text"
                       id="cnpj_busca"
                       x-model="cnpjBusca"
                       @input="formatarCnpj"
                       @keydown.enter.prevent="if (!loading && cnpjBusca.length >= 18) buscarCnpj()"
                       placeholder="00.000.000/0000-00"
                       maxlength="18"
                       inputmode="numeric"
                       autocomplete="off"
                       class="flex-1 sm:max-w-sm px-4 py-3 text-base font-mono tracking-wide border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <button type="button"
                        @click="buscarCnpj"
                        :disabled="loading || cnpjBusca.length < 18"
                        class="px-6 py-3 text-sm text-white rounded-lg font-semibold transition-all bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 disabled:cursor-not-allowed inline-flex items-center justify-center gap-2 shadow-sm">
                    <svg x-show="loading" x-cloak class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg x-show="!loading" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span x-text="loading ? 'Buscando...' : 'Buscar CNPJ'"></span>
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-1.5">Digite apenas os números — a pontuação é colocada automaticamente. Pressione <kbd class="px-1 py-0.5 text-[10px] font-semibold bg-gray-100 border border-gray-200 rounded">Enter</kbd> para buscar.</p>

            {{-- Mensagens --}}
            <div x-show="mensagem" x-cloak class="mt-4">
                <div x-show="tipoMensagem === 'success'" class="flex items-center gap-2 bg-green-50 border border-green-200 px-4 py-3 rounded-lg">
                    <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-semibold text-green-900" x-text="mensagem"></p>
                </div>
                <div x-show="tipoMensagem === 'error'" class="flex items-start gap-2 bg-red-50 border border-red-200 px-4 py-3 rounded-lg">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm text-red-900" x-html="mensagem"></div>
                </div>
                <div x-show="tipoMensagem === 'warning'" class="flex items-center gap-2 bg-yellow-50 border border-yellow-200 px-4 py-3 rounded-lg">
                    <svg class="w-5 h-5 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-sm font-semibold text-yellow-900" x-text="mensagem"></p>
                </div>
            </div>
        </div>

        {{-- Dados Completos em Abas --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200" x-show="dadosCarregados" x-cloak>
            {{-- Etapas (clicáveis) --}}
            <div class="px-4 sm:px-6 pt-5 pb-4 bg-gray-50 border-b border-gray-200 rounded-t-xl">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                            Etapa <span x-text="getEtapaAtual()"></span> de <span x-text="getTotalEtapas()"></span>
                        </p>
                        <p class="text-base font-semibold text-gray-900" x-text="getNomeAba(abaAtiva)"></p>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full"
                          :class="getEtapaAtual() === getTotalEtapas() ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'"
                          x-text="Math.round((getEtapaAtual() / getTotalEtapas()) * 100) + '% concluído'"></span>
                </div>

                <ol class="flex items-start">
                    <template x-for="(etapa, index) in getEtapasVisiveis()" :key="etapa.aba">
                        <li class="flex items-start" :class="index < getTotalEtapas() - 1 ? 'flex-1' : ''">
                            <button type="button" @click="abaAtiva = etapa.aba"
                                    class="flex flex-col items-center gap-1.5 focus:outline-none group"
                                    :aria-current="abaAtiva === etapa.aba ? 'step' : null">
                                <span class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-all"
                                      :class="getEtapaAtual() > index + 1
                                            ? 'bg-green-500 border-green-500 text-white'
                                            : (abaAtiva === etapa.aba ? 'bg-blue-600 border-blue-600 text-white ring-4 ring-blue-100' : 'bg-white border-gray-300 text-gray-400 group-hover:border-gray-400')">
                                    <svg x-show="getEtapaAtual() > index + 1" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span x-show="getEtapaAtual() <= index + 1" x-text="index + 1"></span>
                                </span>
                                <span class="text-[11px] sm:text-xs font-medium text-center whitespace-nowrap"
                                      :class="getEtapaAtual() > index + 1 ? 'text-green-700' : (abaAtiva === etapa.aba ? 'text-blue-700 font-semibold' : 'text-gray-500')"
                                      x-text="etapa.nome"></span>
                            </button>
                            <div x-show="index < getTotalEtapas() - 1"
                                 class="flex-1 h-0.5 mx-1 sm:mx-2 mt-[17px] rounded-full transition-colors duration-500"
                                 :class="getEtapaAtual() > index + 1 ? 'bg-green-500' : 'bg-gray-200'"></div>
                        </li>
                    </template>
                </ol>
            </div>

            {{-- Conteúdo das Abas --}}
            <div class="p-6">
                {{-- Aba: Dados Gerais --}}
                <div x-show="abaAtiva === 'dados-gerais'" x-cloak class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Dados Gerais da Empresa</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Confira os dados abaixo. Nesta etapa você só precisa revisar o <strong class="text-gray-700">Nome Fantasia</strong>.</p>
                    </div>

                    {{-- Campo editável: Nome Fantasia --}}
                    <section class="rounded-xl border-2 p-4 sm:p-5"
                             :class="dados.tipo_setor === 'publico' ? 'border-yellow-300 bg-yellow-50/60' : 'border-blue-200 bg-blue-50/50'">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg"
                                  :class="dados.tipo_setor === 'publico' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-600'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </span>
                            <label for="nome_fantasia" class="text-sm font-semibold text-gray-900">Nome Fantasia <span class="text-red-500">*</span></label>
                            <span class="ml-auto text-[11px] font-medium px-2 py-0.5 rounded-full bg-white border border-gray-200 text-gray-600">Campo editável</span>
                        </div>
                        <input type="text" id="nome_fantasia" name="nome_fantasia" x-model="dados.nome_fantasia"
                               @input="dados.nome_fantasia = $event.target.value.toUpperCase()"
                               placeholder="NOME PELO QUAL O ESTABELECIMENTO É CONHECIDO"
                               :class="dados.tipo_setor === 'publico' ? 'border-yellow-400 focus:ring-yellow-400 focus:border-yellow-400' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'"
                               class="w-full px-4 py-3 text-base font-medium bg-white border-2 rounded-lg focus:ring-2 uppercase">
                        <p x-show="dados.tipo_setor !== 'publico'" class="text-xs text-gray-500 mt-1.5">Nome pelo qual o estabelecimento é conhecido. Se estiver correto, não é preciso alterar.</p>

                        {{-- Alerta para estabelecimentos públicos --}}
                        <div x-show="dados.tipo_setor === 'publico'" x-cloak class="mt-3 flex items-start gap-2">
                            <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-yellow-900">Atenção: Estabelecimento Público</p>
                                <p class="text-xs text-yellow-800 leading-relaxed mt-0.5">
                                    O nome fantasia que veio da API pode ser genérico (ex: "Fundo Municipal de Saúde").
                                    <strong>Altere para o nome específico da unidade</strong>, como:
                                </p>
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    <span class="px-2 py-0.5 text-xs bg-white border border-yellow-200 text-yellow-800 rounded-full">Hospital Municipal [Nome]</span>
                                    <span class="px-2 py-0.5 text-xs bg-white border border-yellow-200 text-yellow-800 rounded-full">Laboratório Central de Saúde Pública</span>
                                    <span class="px-2 py-0.5 text-xs bg-white border border-yellow-200 text-yellow-800 rounded-full">UBS [Nome do Bairro]</span>
                                    <span class="px-2 py-0.5 text-xs bg-white border border-yellow-200 text-yellow-800 rounded-full">HPP - Hospital de Pequeno Porte</span>
                                    <span class="px-2 py-0.5 text-xs bg-white border border-yellow-200 text-yellow-800 rounded-full">Centro de Especialidades</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- Dados da Receita Federal (somente leitura) --}}
                    <section class="rounded-xl border border-gray-200 p-4 sm:p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <h4 class="text-sm font-semibold text-gray-900">Dados da Receita Federal</h4>
                            <span class="text-xs text-gray-500">— preenchidos automaticamente, não é necessário alterar</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-5 gap-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">CNPJ <span class="text-red-500">*</span></label>
                                <input type="text" x-model="dados.cnpj" readonly tabindex="-1"
                                       class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-800 font-mono cursor-default focus:ring-0 focus:border-gray-200">
                                <input type="hidden" name="cnpj" :value="dados.cnpj.replace(/\D/g, '')">
                            </div>
                            <div class="sm:col-span-1 lg:col-span-2">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Razão Social <span class="text-red-500">*</span></label>
                                <input type="text" name="razao_social" x-model="dados.razao_social" readonly tabindex="-1"
                                       class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-800 cursor-default focus:ring-0 focus:border-gray-200">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Natureza Jurídica</label>
                                <input type="text" name="natureza_juridica" x-model="dados.natureza_juridica" readonly tabindex="-1"
                                       class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-800 cursor-default focus:ring-0 focus:border-gray-200">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Situação Cadastral</label>
                                <input type="text" name="descricao_situacao_cadastral" x-model="dados.descricao_situacao_cadastral" readonly tabindex="-1"
                                       :class="(dados.descricao_situacao_cadastral || '').toUpperCase() === 'ATIVA' ? 'text-green-700 font-semibold' : 'text-gray-800'"
                                       class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-default focus:ring-0 focus:border-gray-200">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Porte da Empresa</label>
                                <input type="text" name="porte" x-model="dados.porte" readonly tabindex="-1"
                                       class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-800 cursor-default focus:ring-0 focus:border-gray-200">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Data Início Atividade</label>
                                <input type="text" x-model="dados.data_inicio_atividade" readonly tabindex="-1"
                                       class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-800 cursor-default focus:ring-0 focus:border-gray-200">
                                <input type="hidden" name="data_inicio_atividade" :value="dados.data_inicio_atividade_raw">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Capital Social</label>
                                <input type="text" x-model="formatarMoeda(dados.capital_social)" readonly tabindex="-1"
                                       class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-800 font-mono cursor-default focus:ring-0 focus:border-gray-200">
                                <input type="hidden" name="capital_social" :value="dados.capital_social">
                            </div>
                        </div>

                        {{-- Tipo de Setor --}}
                        <div class="mt-5 flex items-center gap-3 px-4 py-3 rounded-lg border"
                             :class="dados.tipo_setor === 'publico' ? 'bg-green-50 border-green-200' : 'bg-blue-50 border-blue-200'">
                            <div class="text-2xl" x-text="dados.tipo_setor === 'publico' ? '🏛️' : '🏢'"></div>
                            <div class="flex-1">
                                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Tipo de Setor</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="dados.tipo_setor === 'publico' ? 'Estabelecimento Público' : 'Estabelecimento Privado'"></p>
                                <p class="text-xs text-gray-600" x-text="dados.tipo_setor === 'publico' ? 'Permite múltiplos estabelecimentos com mesmo CNPJ' : 'CNPJ deve ser único no sistema'"></p>
                            </div>
                        </div>
                        <input type="hidden" name="tipo_setor" x-model="dados.tipo_setor">
                    </section>

                    {{-- Botões de Navegação --}}
                    <div class="flex justify-end pt-5 border-t border-gray-200">
                        <button type="button" @click="proximaAba('dados-gerais')"
                                class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors">
                            <svg x-show="verificandoNome" x-cloak class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="verificandoNome ? 'Verificando...' : 'Próximo: Endereço'"></span>
                            <svg x-show="!verificandoNome" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Aba: Endereço --}}
                <div x-show="abaAtiva === 'endereco'" x-cloak>
                    <h3 class="text-lg font-semibold text-gray-900">Endereço do Estabelecimento</h3>
                    <p class="text-sm text-gray-500 mt-0.5 mb-5">Informe onde o estabelecimento funciona. Ao digitar o <strong class="text-gray-700">CEP</strong>, logradouro, bairro e cidade são preenchidos automaticamente.</p>

                    {{-- Campos de endereço (em amarelo quando é estabelecimento público: o endereço do CNPJ costuma ser o da sede) --}}
                    <section class="mb-5"
                             :class="dados.tipo_setor === 'publico' ? 'rounded-xl border-2 border-yellow-300 bg-yellow-50 p-4 sm:p-5' : ''">
                        {{-- Alerta para estabelecimentos públicos --}}
                        <div x-show="dados.tipo_setor === 'publico'" x-cloak class="flex items-start gap-3 mb-5">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-yellow-100 text-yellow-700 flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </span>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-yellow-900">Confira e altere o endereço — Estabelecimento Público</p>
                                <p class="text-xs text-yellow-800 leading-relaxed mt-1">
                                    O endereço que veio da API é o endereço da <strong>sede administrativa</strong> do CNPJ (Prefeitura, Secretaria de Saúde, etc.) e
                                    <strong>pode não ser o mesmo</strong> da unidade que você está cadastrando.
                                </p>
                                <p class="text-xs text-yellow-800 leading-relaxed mt-1">
                                    <strong>Altere os campos destacados em amarelo para o endereço real da unidade de saúde</strong> (Hospital, UBS, Laboratório, etc.).
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-6 gap-x-5 gap-y-4">
                            <div class="md:col-span-2">
                                <label for="cep" class="block text-sm font-medium mb-1.5" :class="dados.tipo_setor === 'publico' ? 'text-yellow-900' : 'text-gray-700'">CEP <span class="text-red-500">*</span></label>
                                <input type="text" id="cep" x-model="dados.cep"
                                       @input="dados.cep = dados.cep.replace(/\D/g, '').replace(/(\d{5})(\d)/, '$1-$2').substring(0, 9)"
                                       @blur="buscarCep()"
                                       placeholder="00000-000" maxlength="9" inputmode="numeric"
                                       :class="dados.tipo_setor === 'publico' ? 'border-2 border-yellow-400 focus:ring-yellow-400 focus:border-yellow-500' : 'border border-gray-300 focus:ring-blue-500 focus:border-blue-500'"
                                       class="w-full px-4 py-3 text-sm font-mono tracking-wide bg-white rounded-lg focus:ring-2">
                                <input type="hidden" name="cep" :value="dados.cep.replace(/\D/g, '')">
                                <p class="text-xs mt-1" :class="dados.tipo_setor === 'publico' ? 'text-yellow-800' : 'text-gray-500'">Preenche o endereço automaticamente</p>
                            </div>
                            <div class="md:col-span-4">
                                <label for="endereco" class="block text-sm font-medium mb-1.5" :class="dados.tipo_setor === 'publico' ? 'text-yellow-900' : 'text-gray-700'">Logradouro <span class="text-red-500">*</span></label>
                                <input type="text" id="endereco" name="endereco" x-model="dados.endereco"
                                       @input="dados.endereco = $event.target.value.toUpperCase()"
                                       placeholder="RUA, AVENIDA, QUADRA..."
                                       :class="dados.tipo_setor === 'publico' ? 'border-2 border-yellow-400 focus:ring-yellow-400 focus:border-yellow-500' : 'border border-gray-300 focus:ring-blue-500 focus:border-blue-500'"
                                       class="w-full px-4 py-3 text-sm bg-white rounded-lg focus:ring-2 uppercase">
                            </div>
                            <div class="md:col-span-1">
                                <label for="numero" class="block text-sm font-medium mb-1.5" :class="dados.tipo_setor === 'publico' ? 'text-yellow-900' : 'text-gray-700'">Número <span class="text-red-500">*</span></label>
                                <input type="text" id="numero" name="numero" x-model="dados.numero"
                                       @input="dados.numero = $event.target.value.toUpperCase()"
                                       placeholder="Nº ou S/N"
                                       :class="dados.tipo_setor === 'publico' ? 'border-2 border-yellow-400 focus:ring-yellow-400 focus:border-yellow-500' : 'border border-gray-300 focus:ring-blue-500 focus:border-blue-500'"
                                       class="w-full px-4 py-3 text-sm bg-white rounded-lg focus:ring-2 uppercase">
                            </div>
                            <div class="md:col-span-2">
                                <label for="complemento" class="block text-sm font-medium mb-1.5" :class="dados.tipo_setor === 'publico' ? 'text-yellow-900' : 'text-gray-700'">Complemento <span class="text-xs font-normal text-gray-400">(opcional)</span></label>
                                <input type="text" id="complemento" name="complemento" x-model="dados.complemento"
                                       @input="dados.complemento = $event.target.value.toUpperCase()"
                                       placeholder="SALA, LOTE, BLOCO..."
                                       :class="dados.tipo_setor === 'publico' ? 'border-2 border-yellow-400 focus:ring-yellow-400 focus:border-yellow-500' : 'border border-gray-300 focus:ring-blue-500 focus:border-blue-500'"
                                       class="w-full px-4 py-3 text-sm bg-white rounded-lg focus:ring-2 uppercase">
                            </div>
                            <div class="md:col-span-3">
                                <label for="bairro" class="block text-sm font-medium mb-1.5" :class="dados.tipo_setor === 'publico' ? 'text-yellow-900' : 'text-gray-700'">Bairro <span class="text-red-500">*</span></label>
                                <input type="text" id="bairro" name="bairro" x-model="dados.bairro"
                                       @input="dados.bairro = $event.target.value.toUpperCase()"
                                       :class="dados.tipo_setor === 'publico' ? 'border-2 border-yellow-400 focus:ring-yellow-400 focus:border-yellow-500' : 'border border-gray-300 focus:ring-blue-500 focus:border-blue-500'"
                                       class="w-full px-4 py-3 text-sm bg-white rounded-lg focus:ring-2 uppercase">
                            </div>
                        </div>
                    </section>

                    {{-- Município (somente leitura) --}}
                    <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4 mb-6">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <span class="text-xs font-medium text-gray-600">Município — preenchido automaticamente pelo CEP</span>
                        </div>
                        <p x-show="dados.tipo_setor === 'publico'" x-cloak class="text-xs text-yellow-800 -mt-1 mb-3">
                            Para atualizar a cidade, informe acima o <strong>CEP da unidade</strong>.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Cidade <span class="text-red-500">*</span></label>
                                <input type="text" name="cidade" x-model="dados.cidade" readonly tabindex="-1"
                                       class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-white text-gray-800 cursor-default focus:ring-0 focus:border-gray-200">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Estado <span class="text-red-500">*</span></label>
                                <input type="text" name="estado" x-model="dados.estado" readonly tabindex="-1"
                                       class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-white text-gray-800 cursor-default focus:ring-0 focus:border-gray-200">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Código IBGE</label>
                                <input type="text" name="codigo_municipio_ibge" x-model="dados.codigo_municipio_ibge" readonly tabindex="-1"
                                       class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-white text-gray-800 font-mono cursor-default focus:ring-0 focus:border-gray-200">
                            </div>
                        </div>
                    </div>

                    {{-- Botões de Navegação --}}
                    <div class="flex justify-between gap-3 pt-5 border-t border-gray-200">
                        <button type="button" @click="abaAtiva = 'dados-gerais'"
                                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Voltar
                        </button>
                        <button type="button" @click="proximaAba('endereco')"
                                class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors">
                            Próximo: Atividades
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Pessoa Jurídica sempre informa as atividades exercidas. Se escolher "por enquanto só Projeto/Rotulagem",
                     o cadastro é aprovado pelo Estado e as atividades ficam guardadas para o Licenciamento futuro. --}}
                <input type="hidden" name="apenas_atividades_especiais" :value="finalidade === 'especiais' ? '1' : '0'">
                <input type="hidden" name="atividade_especial_projeto_arq" :value="finalidade === 'especiais' && finalidadeProjetoArq ? '1' : '0'">
                <input type="hidden" name="atividade_especial_rotulagem" :value="finalidade === 'especiais' && finalidadeRotulagem ? '1' : '0'">

                {{-- Aba: Atividades (PASSO 3) --}}
                <div x-show="abaAtiva === 'atividades'" x-cloak>
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Atividades Econômicas</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Marque <strong class="text-gray-700">somente</strong> as atividades realmente exercidas neste estabelecimento — elas constarão no Alvará Sanitário.</p>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap"
                              :class="(atividadePrincipalMarcada || atividadesExercidas.length > 0) ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'"
                              x-text="((atividadePrincipalMarcada ? 1 : 0) + atividadesExercidas.length) + ' selecionada(s)'"></span>
                    </div>

                    {{-- Aviso: atividades são obrigatórias mesmo para quem só vai abrir Projeto/Rotulagem --}}
                    <div class="mb-5 flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">
                        <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-xs text-blue-900 leading-relaxed">
                            <p class="font-semibold text-sm">Vai abrir apenas Projeto Arquitetônico ou Análise de Rotulagem?</p>
                            <p class="mt-0.5">
                                Mesmo assim, informe aqui as atividades que o estabelecimento exerce (ou vai exercer).
                                O tipo de processo — <strong>Licenciamento</strong>, <strong>Projeto Arquitetônico</strong> ou <strong>Análise de Rotulagem</strong> —
                                é escolhido depois, ao abrir o processo, e cada um tem sua competência (Estado ou Município) definida pela pactuação.
                            </p>
                        </div>
                    </div>

                    {{-- Hidden inputs --}}
                    <input type="hidden" name="cnae_fiscal" :value="dados.cnae_fiscal">
                    <input type="hidden" name="cnae_fiscal_descricao" :value="dados.cnae_fiscal_descricao">
                    <input type="hidden" name="cnaes_secundarios" :value="JSON.stringify(dados.cnaes_secundarios)">

                    {{-- Lista de Atividades (Principal + Secundárias) --}}
                    <div class="relative">
                        <p class="text-sm font-medium text-gray-700 mb-2">
                            Atividades do CNPJ <span class="text-red-500">*</span>
                            <span class="text-xs font-normal text-gray-500">— clique na atividade para marcar ou desmarcar</span>
                        </p>
                        <div x-show="popupAvisoAtividades"
                             x-transition.opacity
                             class="absolute inset-0 z-20 rounded-lg bg-white/90 backdrop-blur-[1px] flex items-center justify-center p-4"
                             style="display: none;">
                            <div class="w-full max-w-3xl rounded-xl border-2 border-amber-300 bg-amber-50 shadow-2xl p-6">
                                <div class="flex items-start gap-3">
                                    <svg class="w-6 h-6 text-amber-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-6a1 1 0 00-1 1v2a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div class="flex-1">
                                        <h4 class="text-lg font-bold text-amber-900 mb-2">Atenção obrigatória antes de selecionar as atividades</h4>
                                        <p class="text-sm text-amber-900 leading-relaxed">
                                            Selecione somente as atividades realmente exercidas neste estabelecimento e que sejam de interesse à saúde.
                                            Essas atividades serão as mesmas que constarão no Alvará Sanitário.
                                            Atividades marcadas e não exercidas podem gerar cobrança de taxas e notificação sanitária.
                                        </p>
                                        <div class="mt-4 flex items-center justify-between gap-3">
                                            <span class="text-xs font-medium text-amber-700">
                                                Este aviso será fechado automaticamente em <span x-text="popupAvisoAtividadesExpiraEm"></span>s
                                            </span>
                                            <button type="button"
                                                    @click="fecharPopupAvisoAtividades()"
                                                    class="px-4 py-2 text-sm font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors">
                                                Li e entendi
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2 max-h-[28rem] overflow-y-auto pr-1">
                            {{-- Atividade Principal --}}
                            <label x-show="dados.cnae_fiscal"
                                   class="flex items-start gap-3 p-3.5 border-2 rounded-lg cursor-pointer transition-all"
                                   :class="!atividadePrincipalMarcada
                                        ? 'border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50/40'
                                        : (naoEhVisa(dados.cnae_fiscal) ? 'border-red-300 bg-red-50' : 'border-blue-500 bg-blue-50')">
                                <input type="checkbox"
                                       x-model="atividadePrincipalMarcada"
                                       @change="buscarQuestionarios()"
                                       class="mt-0.5 h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 bg-blue-600 text-white text-[11px] font-bold uppercase tracking-wide rounded">Principal</span>
                                        <span class="font-mono text-xs text-gray-700 bg-gray-100 px-1.5 py-0.5 rounded" x-text="dados.cnae_fiscal"></span>
                                        <span x-show="atividadePrincipalMarcada && naoEhVisa(dados.cnae_fiscal)" x-cloak
                                              class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-semibold rounded">🚫 Não é atividade da Vigilância Sanitária</span>
                                    </div>
                                    <p class="text-sm text-gray-800 leading-snug" x-text="dados.cnae_fiscal_descricao"></p>
                                </div>
                            </label>

                            {{-- Atividades Secundárias --}}
                            <p x-show="dados.cnaes_secundarios.length > 0" class="pt-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Atividades secundárias</p>
                            <template x-for="(cnae, index) in dados.cnaes_secundarios" :key="index">
                                <label class="flex items-start gap-3 p-3.5 border-2 rounded-lg cursor-pointer transition-all"
                                       :class="!atividadesExercidas.includes(String(cnae.codigo))
                                            ? 'border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50/40'
                                            : (naoEhVisa(cnae.codigo) ? 'border-red-300 bg-red-50' : 'border-blue-500 bg-blue-50')">
                                    <input type="checkbox"
                                           :value="String(cnae.codigo)"
                                           x-model="atividadesExercidas"
                                           @change="buscarQuestionarios()"
                                           class="mt-0.5 h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <span class="font-mono text-xs text-gray-700 bg-gray-100 px-1.5 py-0.5 rounded" x-text="cnae.codigo"></span>
                                            <span x-show="cnae.manual" class="px-1.5 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded">Adicionada manualmente</span>
                                            <span x-show="atividadesExercidas.includes(String(cnae.codigo)) && naoEhVisa(cnae.codigo)" x-cloak
                                                  class="px-1.5 py-0.5 bg-red-100 text-red-700 text-xs font-semibold rounded">🚫 Não é atividade da Vigilância Sanitária</span>
                                        </div>
                                        <p class="text-sm text-gray-800 leading-snug" x-text="cnae.descricao || cnae.texto || ''"></p>
                                    </div>
                                </label>
                            </template>
                        </div>

                        {{-- Mensagem informativa quando atividades estão selecionadas --}}
                        <div x-show="(atividadePrincipalMarcada || atividadesExercidas.length > 0) && !naoSujeitoVisa && atividadesNaoVisaSelecionadas().length === 0 && !carregandoQuestionarios"
                             x-cloak
                             class="mt-3 flex items-start gap-2 bg-green-50 border border-green-200 rounded-lg px-3 py-2.5">
                            <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-xs text-green-800">
                                <span class="font-semibold">Atividade(s) selecionada(s) para licenciamento sanitário.</span>
                                Você poderá solicitar <strong>Licenciamento Sanitário</strong> para este estabelecimento.
                            </p>
                        </div>

                        {{-- Não encontrou a atividade? --}}
                        <div class="mt-5 rounded-xl border p-4"
                             :class="dados.tipo_setor === 'publico' ? 'border-yellow-300 bg-yellow-50' : 'border-gray-200 bg-gray-50'">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Não encontrou a atividade?</p>
                                    <p class="text-xs text-gray-600 mt-0.5">Se você alterou os CNAEs na Receita Federal recentemente, atualize a lista.</p>
                                </div>
                                {{-- Botão para atualizar CNAEs via API em tempo real --}}
                                <button type="button" @click="atualizarCnaes()"
                                        :disabled="atualizandoCnaes || jaAtualizouCnaes"
                                        class="inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-medium text-blue-700 bg-white border border-blue-200 rounded-lg hover:bg-blue-50 transition-colors disabled:opacity-60 disabled:cursor-not-allowed whitespace-nowrap">
                                    <svg x-show="!atualizandoCnaes" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    <svg x-show="atualizandoCnaes" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span x-text="jaAtualizouCnaes ? 'CNAEs já atualizados ✓' : (atualizandoCnaes ? 'Consultando Receita Federal...' : 'Atualizei meus CNAEs e não aparecem aqui')"></span>
                                </button>
                            </div>
                            <p x-show="msgAtualizacaoCnaes" x-text="msgAtualizacaoCnaes" class="mt-2 text-xs" :class="tipoMsgCnaes === 'success' ? 'text-green-700' : 'text-red-700'"></p>

                            {{-- Busca de CNAE Manual (Apenas Público) --}}
                            <div x-show="dados.tipo_setor === 'publico'" class="mt-4 pt-4 border-t border-yellow-200">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <h4 class="text-sm font-semibold text-yellow-900">Adicionar atividade manualmente</h4>
                                </div>
                                <p class="text-xs text-yellow-800 mb-3 leading-relaxed">
                                    Para estabelecimentos públicos (Prefeituras, Fundos Municipais) que não possuem os CNAEs de saúde vinculados ao CNPJ,
                                    busque e adicione aqui a atividade correta da unidade (ex: <em>8610-1/01 Atividades de atendimento hospitalar</em>).
                                </p>

                                <div class="flex flex-col sm:flex-row gap-2">
                                    <div class="flex-1">
                                        <div class="relative">
                                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                            </svg>
                                            <input type="text"
                                                   x-model="cnaeBusca"
                                                   @keydown.enter.prevent="buscarCnaeAdicional"
                                                   placeholder="Código CNAE (7 dígitos) ou descrição"
                                                   class="w-full pl-9 pr-4 py-2.5 text-sm bg-white border border-yellow-300 rounded-lg focus:ring-2 focus:ring-yellow-400 focus:border-yellow-500">
                                        </div>
                                        <p x-show="cnaeErro" class="text-xs text-red-600 mt-1" x-text="cnaeErro"></p>
                                    </div>
                                    <button type="button"
                                            @click="buscarCnaeAdicional"
                                            :disabled="loadingCnae"
                                            class="sm:self-start px-5 py-2.5 text-sm bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 disabled:opacity-50">
                                        <span x-show="!loadingCnae">Buscar</span>
                                        <span x-show="loadingCnae" class="flex items-center gap-2">
                                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            Buscando...
                                        </span>
                                    </button>
                                </div>

                                {{-- Resultados da Busca --}}
                                <div x-show="cnaeResultados.length > 0" class="mt-3 max-h-60 overflow-y-auto bg-white border border-gray-200 rounded-lg divide-y divide-gray-100">
                                    <template x-for="resultado in cnaeResultados" :key="resultado.codigo">
                                        <div class="flex items-center justify-between gap-3 p-3 hover:bg-gray-50">
                                            <div class="min-w-0">
                                                <span class="font-mono text-xs font-semibold bg-blue-100 text-blue-800 px-2 py-0.5 rounded" x-text="resultado.codigo"></span>
                                                <p class="text-sm text-gray-800 mt-1 leading-snug" x-text="resultado.descricao"></p>
                                            </div>
                                            <button type="button"
                                                    @click="adicionarCnaeManual(resultado)"
                                                    class="flex-shrink-0 inline-flex items-center gap-1 text-sm font-medium text-blue-700 bg-white border border-blue-200 px-3 py-1.5 rounded-md hover:bg-blue-50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                Adicionar
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Questionários Dinâmicos --}}
                    <div x-show="questionarios.length > 0 && !apenasAtividadesEspeciais" class="mt-6 space-y-4">
                        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg mb-4">
                            <div class="flex items-start">
                                <svg class="h-6 w-6 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="ml-3">
                                    <h4 class="text-sm font-bold text-yellow-900">📋 Questionários Obrigatórios</h4>
                                    <p class="text-xs text-yellow-800 mt-1">
                                        Algumas atividades selecionadas requerem informações adicionais para determinar a competência.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <template x-for="(quest, index) in questionarios" :key="quest.cnae">
                            <div class="bg-white border-2 border-purple-300 rounded-xl p-5 shadow-sm">
                                <div class="flex items-start gap-3 mb-4">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="px-3 py-1 bg-purple-100 text-purple-800 text-xs font-bold rounded-full" x-text="quest.cnae_formatado"></span>
                                            <span class="text-xs text-gray-600" x-text="quest.descricao"></span>
                                        </div>
                                        
                                        {{-- Primeira Pergunta --}}
                                        <p class="text-sm font-semibold text-gray-900 mb-3" x-text="quest.pergunta"></p>
                                        
                                        <div class="flex gap-3">
                                            <button type="button"
                                                    @click="respostasQuestionario[quest.cnae] = 'sim'"
                                                    :class="respostasQuestionario[quest.cnae] === 'sim' ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-green-50'"
                                                    class="flex-1 px-4 py-3 border-2 rounded-lg font-semibold text-sm transition-all duration-200 flex items-center justify-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                SIM
                                            </button>
                                            <button type="button"
                                                    @click="respostasQuestionario[quest.cnae] = 'nao'"
                                                    :class="respostasQuestionario[quest.cnae] === 'nao' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-red-50'"
                                                    class="flex-1 px-4 py-3 border-2 rounded-lg font-semibold text-sm transition-all duration-200 flex items-center justify-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                                NÃO
                                            </button>
                                        </div>

                                        <div x-show="!respostasQuestionario[quest.cnae]" class="mt-2 text-xs text-red-600 font-medium">
                                            ⚠️ Resposta obrigatória
                                        </div>
                                        
                                        {{-- Segunda Pergunta (se existir) --}}
                                        <template x-if="quest.pergunta2">
                                            <div class="mt-4 pt-4 border-t border-gray-200">
                                                <p class="text-sm font-semibold text-gray-900 mb-3" x-text="quest.pergunta2"></p>
                                                
                                                <div class="flex gap-3">
                                                    <button type="button"
                                                            @click="respostasQuestionario2[quest.cnae] = 'sim'"
                                                            :class="respostasQuestionario2[quest.cnae] === 'sim' ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-green-50'"
                                                            class="flex-1 px-4 py-3 border-2 rounded-lg font-semibold text-sm transition-all duration-200 flex items-center justify-center gap-2">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                        SIM
                                                    </button>
                                                    <button type="button"
                                                            @click="respostasQuestionario2[quest.cnae] = 'nao'"
                                                            :class="respostasQuestionario2[quest.cnae] === 'nao' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-red-50'"
                                                            class="flex-1 px-4 py-3 border-2 rounded-lg font-semibold text-sm transition-all duration-200 flex items-center justify-center gap-2">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                        NÃO
                                                    </button>
                                                </div>

                                                <div x-show="!respostasQuestionario2[quest.cnae]" class="mt-2 text-xs text-red-600 font-medium">
                                                    ⚠️ Resposta obrigatória
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Indicador de Competência --}}
                    <div x-show="atividadesExercidas.length > 0 || atividadePrincipalMarcada" class="mt-6">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-2">Resultado da análise — Licenciamento Sanitário</p>

                        {{-- Algumas (não todas) atividades marcadas não são da VISA: precisam ser desmarcadas --}}
                        <div x-show="!naoSujeitoVisa && atividadesNaoVisaSelecionadas().length > 0" x-cloak class="mb-3 flex items-start gap-3 bg-red-50 border border-red-200 p-4 rounded-xl">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-100 text-red-600 flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                            </span>
                            <div class="flex-1">
                                <h4 class="text-sm font-bold text-red-900">Desmarque as atividades que não são da Vigilância Sanitária</h4>
                                <p class="text-xs text-red-800 mt-1 leading-relaxed">
                                    Estas atividades não são de competência da Vigilância Sanitária (não constam na pactuação ou, pela resposta do questionário, não estão sujeitas à fiscalização).
                                    Elas não podem constar no cadastro nem no Alvará Sanitário:
                                </p>
                                <ul class="mt-2 space-y-1">
                                    <template x-for="item in atividadesNaoVisaSelecionadas()" :key="item.codigo">
                                        <li class="text-sm text-red-900"><span class="font-mono font-semibold" x-text="item.codigo"></span> <span x-text="item.descricao ? '— ' + item.descricao : ''"></span></li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        {{-- Alerta NÃO SUJEITO À VISA --}}
                        <div x-show="naoSujeitoVisa" class="flex items-start gap-3 bg-gray-50 border border-gray-300 p-4 rounded-xl">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gray-200 text-gray-600 flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                            </span>
                            <div class="flex-1">
                                <h4 class="text-sm font-bold text-gray-900">Não sujeito à Vigilância Sanitária</h4>
                                <p class="text-xs text-gray-700 mt-1 leading-relaxed">
                                    As atividades selecionadas <strong>não são de competência da Vigilância Sanitária</strong>
                                    (não constam na pactuação ou, pela resposta do questionário, não estão sujeitas à fiscalização).
                                    Este estabelecimento <strong>não precisa de licença sanitária</strong> para exercer estas atividades.
                                </p>
                                <div x-show="dados.tipo_setor === 'publico'" class="mt-3 p-3 bg-yellow-50 border border-yellow-300 rounded-lg">
                                    <p class="text-xs text-yellow-900">
                                        <strong>Estabelecimento público?</strong> Desmarque a atividade genérica do CNPJ (ex: "Administração pública em geral") e use
                                        <strong>"Adicionar atividade manualmente"</strong> acima para incluir a atividade de saúde da unidade.
                                    </p>
                                </div>
                                <p class="mt-3 text-xs text-gray-600">
                                    <strong>Acha que está incorreto?</strong> Revise as respostas do questionário acima ou entre em contato com a Vigilância Sanitária.
                                </p>
                            </div>
                        </div>

                        {{-- Alerta Estadual --}}
                        <div x-show="competenciaEstadual && !naoSujeitoVisa" class="flex items-start gap-3 bg-purple-50 border border-purple-200 p-4 rounded-xl">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-purple-100 text-purple-600 flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </span>
                            <div class="flex-1">
                                <h4 class="text-sm font-bold text-purple-900">Competência Estadual</h4>
                                <p class="text-xs text-purple-800 mt-1 leading-relaxed">
                                    Com base nas atividades selecionadas, este estabelecimento será fiscalizado pela
                                    <strong>Vigilância Sanitária Estadual</strong>.
                                </p>
                                <p class="text-[11px] text-purple-700 mt-2">Projeto Arquitetônico e Análise de Rotulagem seguem a competência própria definida na pactuação.</p>
                            </div>
                        </div>

                        {{-- Alerta Municipal --}}
                        <div x-show="!competenciaEstadual && !naoSujeitoVisa" class="flex items-start gap-3 bg-blue-50 border border-blue-200 p-4 rounded-xl">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-100 text-blue-600 flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                            </span>
                            <div class="flex-1">
                                <h4 class="text-sm font-bold text-blue-900">Competência Municipal</h4>
                                <p class="text-xs text-blue-800 mt-1 leading-relaxed">
                                    Com base nas atividades selecionadas, este estabelecimento será fiscalizado pela
                                    <strong>Vigilância Sanitária Municipal de <span x-text="dados.cidade || 'seu município'"></span></strong>.
                                    Atividades de baixa e média complexidade com atuação local.
                                </p>
                                <p class="text-[11px] text-blue-700 mt-2">Projeto Arquitetônico e Análise de Rotulagem seguem a competência própria definida na pactuação.</p>
                            </div>
                        </div>
                    </div>

                    {{-- O que deseja fazer agora? --}}
                    <div x-show="podeAvancarAtividades() && !naoSujeitoVisa" x-cloak
                         x-effect="if (licenciamentoBloqueado() && finalidade === 'licenciamento') finalidade = 'especiais'"
                         class="mt-6">
                        <p class="text-sm font-semibold text-gray-900">O que você deseja fazer agora? <span class="text-red-500">*</span></p>
                        <p class="text-xs text-gray-500 mb-3">Você poderá abrir os demais processos depois, em "Abrir novo processo".</p>

                        <div class="space-y-3">
                            {{-- Opção: Licenciamento --}}
                            <label class="flex items-start gap-3 p-4 border-2 rounded-xl transition-all"
                                   :class="licenciamentoBloqueado()
                                        ? 'border-gray-200 bg-gray-50 cursor-not-allowed opacity-70'
                                        : (finalidade === 'licenciamento' ? 'border-blue-500 bg-blue-50 cursor-pointer' : 'border-gray-200 hover:border-gray-300 cursor-pointer')">
                                <input type="radio" value="licenciamento" x-model="finalidade" :disabled="licenciamentoBloqueado()"
                                       class="mt-0.5 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900">Licenciamento Sanitário</p>
                                    <p class="text-xs text-gray-600 mt-0.5">Cadastro completo. Projeto Arquitetônico e Análise de Rotulagem também ficam disponíveis.</p>
                                    <div x-show="licenciamentoBloqueado()" class="mt-2 p-2.5 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-900">
                                        A Vigilância Sanitária Municipal de <strong x-text="dados.cidade || 'seu município'"></strong> ainda não utiliza o InfoVISA.
                                        Para o Licenciamento, <strong>procure a VISA municipal</strong>.
                                    </div>
                                </div>
                            </label>

                            {{-- Opção: só Projeto/Rotulagem --}}
                            <div @click="finalidade = 'especiais'"
                                 class="flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all"
                                 :class="finalidade === 'especiais' ? 'border-amber-500 bg-amber-50' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" value="especiais" x-model="finalidade"
                                       class="mt-0.5 h-4 w-4 text-amber-600 border-gray-300 focus:ring-amber-500">
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900">Por enquanto, apenas Projeto Arquitetônico e/ou Análise de Rotulagem</p>
                                    <p class="text-xs text-gray-600 mt-0.5">
                                        O cadastro é analisado pela Vigilância Sanitária Estadual. As atividades marcadas acima ficam guardadas e,
                                        quando você abrir o Licenciamento, o sistema verifica a competência pela pactuação.
                                    </p>

                                    <div x-show="finalidade === 'especiais'" x-cloak class="mt-3 flex flex-col sm:flex-row gap-2">
                                        <label class="flex items-center gap-2 px-3 py-2 bg-white border rounded-lg cursor-pointer"
                                               :class="finalidadeProjetoArq ? 'border-blue-400' : 'border-gray-200'">
                                            <input type="checkbox" x-model="finalidadeProjetoArq" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                            <span class="text-sm text-gray-800">📐 Projeto Arquitetônico</span>
                                        </label>
                                        <label class="flex items-center gap-2 px-3 py-2 bg-white border rounded-lg cursor-pointer"
                                               :class="finalidadeRotulagem ? 'border-blue-400' : 'border-gray-200'">
                                            <input type="checkbox" x-model="finalidadeRotulagem" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                            <span class="text-sm text-gray-800">🏷️ Análise de Rotulagem</span>
                                        </label>
                                    </div>
                                    <p x-show="finalidade === 'especiais' && !finalidadeProjetoArq && !finalidadeRotulagem" x-cloak class="mt-2 text-xs text-red-600">
                                        Selecione pelo menos uma opção
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Atividades Exercidas (hidden) --}}
                    <input type="hidden" name="atividades_exercidas" :value="JSON.stringify(getAtividadesExercidas())">
                    <input type="hidden" name="respostas_questionario" :value="JSON.stringify(respostasQuestionario)">
                    <input type="hidden" name="respostas_questionario2" :value="JSON.stringify(respostasQuestionario2)">
                    <input type="hidden" name="competencia_estadual" :value="competenciaEstadual ? '1' : '0'">
                    <input type="hidden" name="nao_sujeito_visa" :value="naoSujeitoVisa ? '1' : '0'">

                    {{-- Indicador de Carregamento --}}
                    <div x-show="carregandoQuestionarios" class="mt-4 flex items-center justify-center gap-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium text-blue-700">Carregando informações das atividades...</span>
                    </div>

                    {{-- Botões de Navegação --}}
                    <div class="flex justify-between gap-3 pt-5 mt-6 border-t border-gray-200">
                        <button type="button" @click="abaAtiva = 'endereco'"
                                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Voltar
                        </button>
                        <button type="button"
                                @click="proximaAba('atividades')"
                                :disabled="naoSujeitoVisa || !podeAvancarAtividades()"
                                :class="(naoSujeitoVisa || !podeAvancarAtividades()) ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700 shadow-sm'"
                                class="px-6 py-2.5 text-sm text-white rounded-lg font-semibold flex items-center gap-2 transition-colors">
                            <template x-if="carregandoQuestionarios">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <span x-show="naoSujeitoVisa">Cadastro não necessário</span>
                            <span x-show="!naoSujeitoVisa && carregandoQuestionarios">Aguarde...</span>
                            <span x-show="!naoSujeitoVisa && !carregandoQuestionarios && atividadesNaoVisaSelecionadas().length > 0">Desmarque as atividades fora da VISA</span>
                            <span x-show="!naoSujeitoVisa && !carregandoQuestionarios && !podeAvancarAtividades() && atividadesNaoVisaSelecionadas().length === 0">Selecione atividades</span>
                            <span x-show="!naoSujeitoVisa && !carregandoQuestionarios && podeAvancarAtividades()" class="inline-flex items-center gap-2">
                                Próximo: Contato
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </span>
                        </button>
                    </div>
                </div>

                {{-- Aba: Contato --}}
                <div x-show="abaAtiva === 'contato'" x-cloak>
                    <h3 class="text-lg font-semibold text-gray-900">Informações de Contato</h3>
                    <p class="text-sm text-gray-500 mt-0.5 mb-5">Último passo! Informe como a Vigilância Sanitária pode falar com o estabelecimento.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5 gap-y-4 mb-6">
                        <div>
                            <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1.5">Telefone <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <input type="text" id="telefone" x-model="dados.telefone"
                                       @input="formatarTelefone"
                                       placeholder="(00) 00000-0000" inputmode="tel"
                                       class="w-full pl-10 pr-4 py-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <input type="hidden" name="telefone" :value="dados.telefone.replace(/\D/g, '')">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">E-mail <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <input type="email" id="email" name="email" x-model="dados.email"
                                       placeholder="contato@empresa.com.br"
                                       class="w-full pl-10 pr-4 py-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>

                    {{-- Vínculo com Estabelecimento --}}
                    <div class="mb-6">
                        <p class="block text-sm font-medium text-gray-700 mb-0.5">
                            Seu vínculo com este estabelecimento <span class="text-red-500">*</span>
                        </p>
                        <p class="text-xs text-gray-500 mb-2">Informe qual é a sua relação com este estabelecimento</p>
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                            @foreach ([
                                'responsavel_legal' => 'Responsável Legal',
                                'responsavel_tecnico' => 'Responsável Técnico',
                                'funcionario' => 'Funcionário',
                                'contador' => 'Contador',
                            ] as $valorVinculo => $rotuloVinculo)
                            <label class="flex items-center gap-2.5 px-3 py-3 border-2 rounded-lg cursor-pointer transition-all"
                                   :class="dados.vinculo_usuario === '{{ $valorVinculo }}' ? 'border-blue-500 bg-blue-50 text-blue-800' : 'border-gray-200 hover:border-gray-300 text-gray-700'">
                                <input type="radio" name="vinculo_usuario" value="{{ $valorVinculo }}" x-model="dados.vinculo_usuario"
                                       class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                <span class="text-sm font-medium">{{ $rotuloVinculo }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Resumo --}}
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 mb-6">
                        <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-900 mb-3">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                            Confira o resumo antes de enviar
                        </h4>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div>
                                <dt class="text-xs text-gray-500">CNPJ</dt>
                                <dd class="font-medium text-gray-900 font-mono" x-text="dados.cnpj"></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Razão Social</dt>
                                <dd class="font-medium text-gray-900" x-text="dados.razao_social"></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Nome Fantasia</dt>
                                <dd class="font-medium text-gray-900" x-text="dados.nome_fantasia"></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Cidade</dt>
                                <dd class="font-medium text-gray-900" x-text="dados.cidade + ' - ' + dados.estado"></dd>
                            </div>
                            <div class="md:col-span-2">
                                <dt class="text-xs text-gray-500">Deseja abrir agora</dt>
                                <dd class="font-medium text-gray-900"
                                    x-text="finalidade === 'licenciamento'
                                        ? 'Licenciamento Sanitário (cadastro completo)'
                                        : 'Apenas ' + [finalidadeProjetoArq ? 'Projeto Arquitetônico' : null, finalidadeRotulagem ? 'Análise de Rotulagem' : null].filter(Boolean).join(' e ') + ' — análise da Vigilância Sanitária Estadual'"></dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Aviso de Status Pendente --}}
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <h4 class="text-sm font-semibold text-yellow-800">Aguardando Aprovação</h4>
                                <p class="text-sm text-yellow-700 mt-1">
                                    Após o envio, seu estabelecimento ficará com status <strong>Pendente</strong> até que a Vigilância Sanitária (Municipal ou Estadual) analise e aprove o cadastro.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Botões de Navegação --}}
                    <div class="flex justify-between gap-3 pt-5 border-t border-gray-200">
                        <button type="button" @click="abaAtiva = 'atividades'"
                                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Voltar
                        </button>
                        <button type="submit"
                                :disabled="submitting"
                                class="px-8 py-2.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold shadow-sm disabled:bg-green-400 disabled:cursor-not-allowed inline-flex items-center gap-2 transition-colors">
                            <svg x-show="submitting" x-cloak class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="submitting ? 'Cadastrando...' : 'Cadastrar Estabelecimento'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal de Estabelecimentos Existentes --}}
        <div x-show="modalEstabelecimentosExistentes.visivel" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            {{-- Overlay --}}
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
            
            {{-- Modal --}}
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="relative bg-white rounded-lg shadow-2xl max-w-lg w-full mx-auto transform transition-all"
                     @click.away="fecharModalEstabelecimentos()">
                    
                    {{-- Header --}}
                    <div class="bg-gradient-to-r from-yellow-500 to-orange-500 px-4 py-3 rounded-t-lg">
                        <div class="flex items-center gap-2">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div>
                                <h3 class="text-base font-bold text-white">Estabelecimentos Já Cadastrados</h3>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Body --}}
                    <div class="px-4 py-4">
                        {{-- Lista de Estabelecimentos --}}
                        <div class="space-y-2 mb-3">
                            <template x-for="(estabelecimento, index) in modalEstabelecimentosExistentes.estabelecimentos" :key="index">
                                <div class="flex items-center gap-2 p-2 bg-blue-50 border-l-4 border-blue-500 rounded-r">
                                    <svg class="w-5 h-5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <p class="text-sm font-medium text-gray-900" x-text="estabelecimento.nome_fantasia"></p>
                                </div>
                            </template>
                        </div>
                        
                        {{-- Informação --}}
                        <div class="bg-green-50 border-l-4 border-green-400 p-2 rounded-r">
                            <p class="text-xs text-green-700">
                                ✅ Você pode cadastrar outro estabelecimento com o mesmo CNPJ (Hospital, Laboratório, UBS, etc.)
                            </p>
                        </div>
                    </div>
                    
                    {{-- Footer --}}
                    <div class="bg-gray-50 px-4 py-3 rounded-b-lg flex justify-end gap-2">
                        <button type="button"
                                @click="cancelarCadastro()"
                                class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button type="button"
                                @click="continuarCadastro()"
                                class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700 transition-colors">
                            Continuar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>


@push('scripts')
<script>
function estabelecimentoFormCompany() {
    return {
        cnpjBusca: '',
        loading: false,
        submitting: false,
        carregandoQuestionarios: false,
        mensagem: '',
        tipoMensagem: '',
        dadosCarregados: false,
        abaAtiva: 'dados-gerais',
        atividadesSelecionadas: [],
        atividadesExercidas: [],
        atividadePrincipalMarcada: false,
        questionarios: [],
        respostasQuestionario: {},
        respostasQuestionario2: {},
        competenciaEstadual: false,
        naoSujeitoVisa: false,
        // Competência de cada atividade marcada (CNAE só dígitos => estadual|municipal|nao_sujeito_visa)
        competenciaPorCnae: {},
        // Atividades Especiais (Projeto Arquitetônico / Análise de Rotulagem)
        apenasAtividadesEspeciais: false,
        atividadeEspecialProjetoArq: false,
        atividadeEspecialRotulagem: false,
        // Finalidade do cadastro (PJ sempre informa as atividades reais):
        // 'licenciamento' = cadastro completo | 'especiais' = por enquanto só Projeto/Rotulagem (aprovado pelo Estado)
        finalidade: 'licenciamento',
        finalidadeProjetoArq: false,
        finalidadeRotulagem: false,
        // Adesão do município ao InfoVISA (null = ainda não verificado)
        municipioUsaInfovisa: null,
        modalErro: {
            visivel: false,
            mensagens: []
        },
        verificandoNome: false,
        modalEstabelecimentosExistentes: {
            visivel: false,
            estabelecimentos: []
        },
        // Atualização de CNAEs via API em tempo real
        atualizandoCnaes: false,
        jaAtualizouCnaes: false,
        msgAtualizacaoCnaes: '',
        tipoMsgCnaes: '',
        // Busca manual de CNAE (para estabelecimentos públicos)
        cnaeBusca: '',
        cnaeErro: '',
        loadingCnae: false,
        cnaeResultados: [],
        popupAvisoAtividades: false,
        popupAvisoAtividadesTimer: null,
        popupAvisoAtividadesExpiraEm: 15,
        dados: {
            cnpj: '',
            razao_social: '',
            nome_fantasia: '',
            natureza_juridica: '',
            porte: '',
            descricao_situacao_cadastral: '',
            data_situacao_cadastral: '',
            data_inicio_atividade: '',
            data_inicio_atividade_raw: '',
            capital_social: '',
            cnae_fiscal: '',
            cnae_fiscal_descricao: '',
            cnaes_secundarios: [],
            endereco: '',
            numero: '',
            complemento: '',
            bairro: '',
            cidade: '',
            estado: '',
            cep: '',
            codigo_municipio_ibge: '',
            telefone: '',
            email: '',
            tipo_setor: 'privado',
            vinculo_usuario: ''
        },

        init() {
            this.$watch('abaAtiva', (value) => {
                if (value === 'atividades') {
                    this.exibirPopupAvisoAtividades();
                } else {
                    this.fecharPopupAvisoAtividades(true);
                }
            });

            // Watchers para verificar competência quando atividades mudarem
            this.$watch('atividadesExercidas', (value) => {
                this.verificarCompetencia();
                this.buscarQuestionarios();
                // Se marcou alguma atividade, desmarcar automaticamente o modo de atividades especiais
                if (value.length > 0) {
                    this.desmarcarAtividadesEspeciais();
                }
            });
            this.$watch('atividadePrincipalMarcada', (value) => {
                this.verificarCompetencia();
                this.buscarQuestionarios();
                // Se marcou a atividade principal, desmarcar automaticamente o modo de atividades especiais
                if (value) {
                    this.desmarcarAtividadesEspeciais();
                }
            });
            // Recalcula competência quando as respostas mudam
            this.$watch('respostasQuestionario', () => {
                this.verificarCompetencia();
            }, { deep: true });
            // Recalcula competência quando as respostas da segunda pergunta mudam
            this.$watch('respostasQuestionario2', () => {
                this.verificarCompetencia();
            }, { deep: true });
        },

        exibirPopupAvisoAtividades() {
            this.fecharPopupAvisoAtividades(true);
            this.popupAvisoAtividades = true;
            this.popupAvisoAtividadesExpiraEm = 15;

            this.popupAvisoAtividadesTimer = setInterval(() => {
                this.popupAvisoAtividadesExpiraEm -= 1;
                if (this.popupAvisoAtividadesExpiraEm <= 0) {
                    this.fecharPopupAvisoAtividades(true);
                }
            }, 1000);
        },

        fecharPopupAvisoAtividades(silencioso = false) {
            if (this.popupAvisoAtividadesTimer) {
                clearInterval(this.popupAvisoAtividadesTimer);
                this.popupAvisoAtividadesTimer = null;
            }

            this.popupAvisoAtividades = false;
            if (!silencioso) {
                this.popupAvisoAtividadesExpiraEm = 15;
            }
        },

        async verificarCompetencia() {
            const atividades = [];
            
            // Se está no modo de atividades especiais
            if (this.apenasAtividadesEspeciais) {
                if (this.atividadeEspecialProjetoArq) {
                    atividades.push('PROJ_ARQ');
                }
                if (this.atividadeEspecialRotulagem) {
                    atividades.push('ANAL_ROT');
                }
                
                console.log('🔍 Verificando competência (atividades especiais):', {
                    atividades: atividades,
                    municipio: this.dados.cidade
                });
                
                if (atividades.length === 0) {
                    this.competenciaEstadual = false;
                    this.naoSujeitoVisa = false;
                    return;
                }
            } else {
                // Adiciona CNAE principal se marcado
                if (this.atividadePrincipalMarcada && this.dados.cnae_fiscal) {
                    atividades.push(this.dados.cnae_fiscal);
                }
                
                // Adiciona atividades secundárias selecionadas
                this.atividadesExercidas.forEach(codigo => {
                    atividades.push(codigo);
                });
                
                console.log('🔍 Verificando competência:', {
                    atividadePrincipalMarcada: this.atividadePrincipalMarcada,
                    cnae_fiscal: this.dados.cnae_fiscal,
                    atividadesExercidas: this.atividadesExercidas,
                    atividades: atividades,
                    municipio: this.dados.cidade,
                    respostas: JSON.parse(JSON.stringify(this.respostasQuestionario)),
                    respostas2: JSON.parse(JSON.stringify(this.respostasQuestionario2))
                });
            }
            
            if (atividades.length === 0) {
                this.competenciaEstadual = false;
                this.naoSujeitoVisa = false;
                return;
            }
            
            // Consulta API para verificar competência
            try {
                const response = await fetch('{{ url('/api/verificar-competencia') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        atividades: atividades,
                        municipio: this.dados.cidade,
                        respostas_questionario: this.respostasQuestionario,
                        respostas_questionario2: this.respostasQuestionario2
                    })
                });
                
                const result = await response.json();
                console.log('✅ Resultado da API:', result);
                
                this.competenciaEstadual = result.competencia === 'estadual';
                this.naoSujeitoVisa = result.competencia === 'nao_sujeito_visa';
                this.municipioUsaInfovisa = typeof result.usa_infovisa === 'boolean' ? result.usa_infovisa : null;
                this.competenciaPorCnae = Object.fromEntries(
                    (result.detalhes || []).map(d => [String(d.cnae).replace(/\D/g, ''), d.competencia])
                );

                console.log('📊 Competência definida:', {
                    competenciaEstadual: this.competenciaEstadual,
                    naoSujeitoVisa: this.naoSujeitoVisa,
                    resultado: result.competencia
                });
            } catch (error) {
                console.error('❌ Erro ao verificar competência:', error);
                this.competenciaEstadual = false;
                this.naoSujeitoVisa = false;
            }
        },

        formatarCnpj() {
            let valor = this.cnpjBusca.replace(/\D/g, '');
            if (valor.length > 14) valor = valor.substring(0, 14);
            valor = valor.replace(/^(\d{2})(\d)/, '$1.$2');
            valor = valor.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            valor = valor.replace(/\.(\d{3})(\d)/, '.$1/$2');
            valor = valor.replace(/(\d{4})(\d)/, '$1-$2');
            this.cnpjBusca = valor;
        },

        formatarTelefone() {
            let valor = this.dados.telefone.replace(/\D/g, '');
            if (valor.length > 11) valor = valor.substring(0, 11);
            if (valor.length > 10) {
                valor = valor.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            } else if (valor.length > 6) {
                valor = valor.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3');
            } else if (valor.length > 2) {
                valor = valor.replace(/^(\d{2})(\d{0,5})$/, '($1) $2');
            }
            this.dados.telefone = valor;
        },

        formatarMoeda(valor) {
            if (!valor) return 'R$ 0,00';
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valor);
        },

        async buscarCnpj() {
            const cnpj = this.cnpjBusca.replace(/\D/g, '');
            if (cnpj.length !== 14) {
                this.mensagem = 'CNPJ deve ter 14 dígitos';
                this.tipoMensagem = 'error';
                return;
            }

            this.loading = true;
            this.mensagem = '';

            try {
                const response = await fetch('{{ url("/api/consultar-cnpj") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        cnpj: this.cnpjBusca
                    })
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'CNPJ não encontrado em nenhuma base de dados');
                }

                const data = result.data || {};
                const apiSource = result.api_source || 'API';
                
                // Preenche os dados (mesmo padrão do admin)
                this.dados.cnpj = this.cnpjBusca;
                this.dados.razao_social = data.razao_social || '';
                this.dados.nome_fantasia = data.nome_fantasia || data.razao_social || '';
                this.dados.natureza_juridica = data.natureza_juridica || '';
                this.dados.porte = data.porte || '';
                this.dados.descricao_situacao_cadastral = data.descricao_situacao_cadastral || data.situacao_cadastral || '';
                this.dados.capital_social = data.capital_social || 0;
                this.dados.cnae_fiscal = data.cnae_fiscal?.toString() || '';
                this.dados.cnae_fiscal_descricao = data.cnae_fiscal_descricao || '';
                
                // Data de início
                if (data.data_inicio_atividade) {
                    this.dados.data_inicio_atividade_raw = data.data_inicio_atividade;
                    const parts = data.data_inicio_atividade.split('-');
                    if (parts.length === 3) {
                        this.dados.data_inicio_atividade = `${parts[2]}/${parts[1]}/${parts[0]}`;
                    } else {
                        this.dados.data_inicio_atividade = data.data_inicio_atividade;
                    }
                }

                // CNAEs secundários
                this.dados.cnaes_secundarios = data.cnaes_secundarios || [];
                
                // Endereço
                this.dados.endereco = data.endereco || data.logradouro || '';
                this.dados.numero = data.numero || '';
                this.dados.complemento = data.complemento || '';
                this.dados.bairro = data.bairro || '';
                this.dados.cidade = data.cidade || data.municipio || '';
                this.dados.estado = data.estado || data.uf || '';
                this.dados.cep = data.cep?.replace(/\D/g, '') || '';
                if (this.dados.cep) {
                    this.dados.cep = this.dados.cep.replace(/(\d{5})(\d{3})/, '$1-$2');
                }
                this.dados.codigo_municipio_ibge = data.codigo_municipio_ibge?.toString() || '';
                
                // Telefone e email
                const telefoneApi = data.telefone || data.ddd_telefone_1 || '';
                if (telefoneApi) {
                    this.dados.telefone = telefoneApi.replace(/\D/g, '');
                    this.formatarTelefone();
                }
                this.dados.email = data.email || '';

                // Tipo de setor baseado na natureza jurídica
                const tipoSetor = this.inferirTipoSetor(data.tipo_setor, data.natureza_juridica);
                this.dados.tipo_setor = tipoSetor;

                // Se for PÚBLICO, verifica se já existem estabelecimentos com este CNPJ
                if (tipoSetor === 'publico') {
                    try {
                        const verificarResponse = await fetch(`{{ url('/api/verificar-cnpj') }}/${cnpj}`);
                        if (verificarResponse.ok) {
                            const verificarData = await verificarResponse.json();
                            if (verificarData.existe && verificarData.estabelecimentos && verificarData.estabelecimentos.length > 0) {
                                this.modalEstabelecimentosExistentes.estabelecimentos = verificarData.estabelecimentos;
                                this.modalEstabelecimentosExistentes.visivel = true;
                                return; // Aguarda decisão do usuário no modal
                            }
                        }
                    } catch (e) {
                        console.log('Erro ao verificar estabelecimentos existentes:', e);
                    }
                }

                this.dadosCarregados = true;
                this.mensagem = `Dados carregados com sucesso via ${apiSource}!`;
                this.tipoMensagem = 'success';

                // Se o email não veio da primeira API, busca na API atualizada (CNPJa)
                if (!this.dados.email) {
                    this.buscarEmailAutomatico();
                }

            } catch (error) {
                this.mensagem = 'Erro ao buscar CNPJ: ' + error.message;
                this.tipoMensagem = 'error';
            } finally {
                this.loading = false;
            }
        },

        async buscarCep() {
            const cep = this.dados.cep.replace(/\D/g, '');
            if (cep.length !== 8) return;

            try {
                const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                const data = await response.json();
                if (!data.erro) {
                    this.dados.endereco = data.logradouro?.toUpperCase() || this.dados.endereco;
                    this.dados.bairro = data.bairro?.toUpperCase() || this.dados.bairro;
                    this.dados.cidade = data.localidade || this.dados.cidade;
                    this.dados.estado = data.uf || this.dados.estado;
                    this.dados.codigo_municipio_ibge = data.ibge || this.dados.codigo_municipio_ibge;
                }
            } catch (error) {
                console.error('Erro ao buscar CEP:', error);
            }
        },

        // Pessoa Jurídica sempre informa as atividades (não há mais a etapa "Tipo de Cadastro")
        getEtapaAtual() {
            const abas = ['dados-gerais', 'endereco', 'atividades', 'contato'];
            return abas.indexOf(this.abaAtiva) + 1;
        },

        getTotalEtapas() {
            return 4;
        },

        getEtapasVisiveis() {
            return [
                { aba: 'dados-gerais', nome: 'Dados' },
                { aba: 'endereco', nome: 'Endereço' },
                { aba: 'atividades', nome: 'Atividades' },
                { aba: 'contato', nome: 'Contato' }
            ];
        },

        getNomeAba(aba) {
            const nomes = {
                'dados-gerais': 'Dados Gerais',
                'endereco': 'Endereço',
                'atividades': 'Atividades',
                'contato': 'Contato'
            };
            return nomes[aba] || '';
        },

        // Funções para Atividades Especiais (Projeto Arquitetônico / Análise de Rotulagem)
        desmarcarAtividadesEspeciais() {
            // Desmarca o modo de atividades especiais quando o usuário marca uma atividade do CNPJ
            if (this.apenasAtividadesEspeciais || this.atividadeEspecialProjetoArq || this.atividadeEspecialRotulagem) {
                this.apenasAtividadesEspeciais = false;
                this.atividadeEspecialProjetoArq = false;
                this.atividadeEspecialRotulagem = false;
                console.log('🔄 Atividades especiais desmarcadas automaticamente (usuário selecionou atividade do CNPJ)');
            }
        },

        toggleAtividadesEspeciais() {
            if (this.apenasAtividadesEspeciais) {
                // Desmarca todas as atividades do CNPJ
                this.atividadePrincipalMarcada = false;
                this.atividadesExercidas = [];
                this.questionarios = [];
                this.respostasQuestionario = {};
                this.respostasQuestionario2 = {};
                console.log('🔄 Modo atividades especiais ativado - atividades do CNPJ desmarcadas');
            } else {
                // Desmarca atividades especiais
                this.atividadeEspecialProjetoArq = false;
                this.atividadeEspecialRotulagem = false;
                console.log('🔄 Modo atividades especiais desativado');
            }
            this.verificarCompetencia();
        },

        atualizarAtividadesEspeciais() {
            console.log('📋 Atividades especiais atualizadas:', {
                projetoArq: this.atividadeEspecialProjetoArq,
                rotulagem: this.atividadeEspecialRotulagem
            });
            this.verificarCompetencia();
        },

        async atualizarCnaes() {
            if (!this.dados.cnpj || this.atualizandoCnaes || this.jaAtualizouCnaes) return;
            this.atualizandoCnaes = true;
            this.msgAtualizacaoCnaes = '';
            try {
                const response = await fetch('{{ url("/api/consultar-cnpj-atualizado") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ cnpj: this.dados.cnpj })
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Não foi possível obter dados atualizados.');
                }
                const data = result.data || {};
                this.dados.cnae_fiscal = data.cnae_fiscal?.toString() || this.dados.cnae_fiscal;
                this.dados.cnae_fiscal_descricao = data.cnae_fiscal_descricao || this.dados.cnae_fiscal_descricao;
                this.dados.cnaes_secundarios = data.cnaes_secundarios || this.dados.cnaes_secundarios;
                if (data.email && !this.dados.email) {
                    this.dados.email = data.email;
                }
                this.msgAtualizacaoCnaes = `CNAEs atualizados com sucesso via ${result.api_source || 'Receita Federal'}!`;
                this.tipoMsgCnaes = 'success';
                this.jaAtualizouCnaes = true;
                this.atividadePrincipalMarcada = false;
                this.atividadesExercidas = [];
            } catch (error) {
                this.msgAtualizacaoCnaes = error.message;
                this.tipoMsgCnaes = 'error';
            } finally {
                this.atualizandoCnaes = false;
            }
        },

        async buscarEmailAutomatico() {
            if (!this.dados.cnpj) return;
            try {
                const response = await fetch('{{ url("/api/consultar-cnpj-atualizado") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ cnpj: this.dados.cnpj })
                });
                const result = await response.json();
                if (response.ok && result.success && result.data?.email && !this.dados.email) {
                    this.dados.email = result.data.email;
                }
            } catch (e) {
                // Silencioso — o usuário pode preencher manualmente
            }
        },

        getAtividadesExercidas() {
            let atividades = [];
            
            // Se está no modo de atividades especiais, retorna apenas as atividades especiais
            if (this.apenasAtividadesEspeciais) {
                if (this.atividadeEspecialProjetoArq) {
                    atividades.push({ 
                        codigo: 'PROJ_ARQ', 
                        descricao: 'Projeto Arquitetônico - Análise de projeto arquitetônico para adequação sanitária',
                        principal: false,
                        especial: true
                    });
                }
                if (this.atividadeEspecialRotulagem) {
                    atividades.push({ 
                        codigo: 'ANAL_ROT', 
                        descricao: 'Análise de Rotulagem - Análise e aprovação de rótulos de produtos',
                        principal: false,
                        especial: true
                    });
                }
                console.log('Atividades especiais selecionadas:', atividades);
                return atividades;
            }
            
            // Adiciona atividade principal se marcada
            if (this.atividadePrincipalMarcada && this.dados.cnae_fiscal) {
                atividades.push({ 
                    codigo: String(this.dados.cnae_fiscal), 
                    descricao: this.dados.cnae_fiscal_descricao,
                    principal: true 
                });
            }
            
            // Adiciona atividades secundárias selecionadas
            // Converte para string para garantir comparação correta
            this.atividadesExercidas.forEach(codigoSelecionado => {
                const codigoStr = String(codigoSelecionado);
                const cnae = this.dados.cnaes_secundarios.find(c => String(c.codigo) === codigoStr);
                if (cnae) {
                    atividades.push({ 
                        codigo: String(cnae.codigo), 
                        descricao: cnae.descricao || cnae.texto || '',
                        principal: false 
                    });
                }
            });
            
            console.log('Atividades exercidas:', atividades);
            console.log('Array atividadesExercidas:', this.atividadesExercidas);
            
            return atividades;
        },

        async buscarQuestionarios() {
            // Monta lista de CNAEs selecionados
            const cnaes = [];
            
            if (this.atividadePrincipalMarcada && this.dados.cnae_fiscal) {
                cnaes.push(this.dados.cnae_fiscal);
            }
            
            this.atividadesExercidas.forEach(codigo => {
                cnaes.push(codigo);
            });
            
            if (cnaes.length === 0) {
                this.questionarios = [];
                this.respostasQuestionario = {};
                this.carregandoQuestionarios = false;
                return;
            }
            
            // Ativa indicador de carregamento
            this.carregandoQuestionarios = true;
            
            try {
                const response = await fetch('{{ route('company.estabelecimentos.buscar-questionarios') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ cnaes })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.questionarios = data;
                    
                    // Remove respostas de questionários que não existem mais
                    const cnaesComQuestionario = data.map(q => q.cnae);
                    Object.keys(this.respostasQuestionario).forEach(cnae => {
                        if (!cnaesComQuestionario.includes(cnae)) {
                            delete this.respostasQuestionario[cnae];
                        }
                    });
                    
                    console.log('Questionários encontrados:', data);
                } else {
                    console.error('Erro ao buscar questionários');
                }
            } catch (error) {
                console.error('Erro ao buscar questionários:', error);
            } finally {
                // Desativa indicador de carregamento
                this.carregandoQuestionarios = false;
            }
        },

        // Atividade fora da pactuação ou respondida "NÃO" na Tabela V
        naoEhVisa(codigo) {
            return this.competenciaPorCnae[String(codigo || '').replace(/\D/g, '')] === 'nao_sujeito_visa';
        },

        // Atividades marcadas que não são da Vigilância Sanitária
        atividadesNaoVisaSelecionadas() {
            const lista = [];
            if (this.atividadePrincipalMarcada && this.naoEhVisa(this.dados.cnae_fiscal)) {
                lista.push({ codigo: this.dados.cnae_fiscal, descricao: this.dados.cnae_fiscal_descricao });
            }
            (this.dados.cnaes_secundarios || []).forEach(cnae => {
                if (this.atividadesExercidas.includes(String(cnae.codigo)) && this.naoEhVisa(cnae.codigo)) {
                    lista.push({ codigo: cnae.codigo, descricao: cnae.descricao || cnae.texto || '' });
                }
            });
            return lista;
        },

        // Verifica se pode avançar da aba de atividades
        podeAvancarAtividades() {
            // Se está no modo de atividades especiais
            if (this.apenasAtividadesEspeciais) {
                // Deve ter pelo menos uma atividade especial selecionada
                return this.atividadeEspecialProjetoArq || this.atividadeEspecialRotulagem;
            }
            
            // Deve ter pelo menos uma atividade selecionada
            const temAtividade = this.atividadePrincipalMarcada || this.atividadesExercidas.length > 0;
            if (!temAtividade) return false;
            
            // Não pode estar carregando questionários
            if (this.carregandoQuestionarios) return false;
            
            // Se houver questionários, todos devem estar respondidos
            if (this.questionarios.length > 0) {
                for (const quest of this.questionarios) {
                    // Verifica primeira pergunta
                    if (!this.respostasQuestionario[quest.cnae]) {
                        return false;
                    }
                    // Verifica segunda pergunta se existir
                    if (quest.pergunta2 && !this.respostasQuestionario2[quest.cnae]) {
                        return false;
                    }
                }
            }

            // Atividades que não são da Vigilância Sanitária precisam ser desmarcadas
            if (this.atividadesNaoVisaSelecionadas().length > 0) return false;

            return true;
        },

        // Licenciamento de competência municipal em município que ainda não aderiu ao InfoVISA:
        // o cadastro só pode seguir para Projeto Arquitetônico/Análise de Rotulagem (Estado).
        licenciamentoBloqueado() {
            return !this.naoSujeitoVisa && !this.competenciaEstadual && this.municipioUsaInfovisa === false;
        },

        errosFinalidade() {
            const erros = [];
            if (this.finalidade === 'licenciamento' && this.licenciamentoBloqueado()) {
                erros.push(`A Vigilância Sanitária Municipal de ${this.dados.cidade || 'seu município'} ainda não utiliza o InfoVISA. Para o Licenciamento, procure a VISA municipal — aqui você pode seguir apenas com Projeto Arquitetônico e/ou Análise de Rotulagem.`);
            }
            if (this.finalidade === 'especiais' && !this.finalidadeProjetoArq && !this.finalidadeRotulagem) {
                erros.push('Selecione pelo menos um processo: Projeto Arquitetônico ou Análise de Rotulagem');
            }
            return erros;
        },

        inferirTipoSetor(tipoSetorApi, naturezaJuridica) {
            const codigosPublicos = ['1015','1023','1031','1040','1050','1060','1070','1080','1104','1112','1120','1139','1147','1155','1163','1171','1180','1210','1228','1236','1244','1317','1252','1260','1279','1287','1295','1309','1321','1330','1341'];
            const natureza = (naturezaJuridica || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
            const codigo = natureza.match(/^(\d{4})/);
            if (codigo && codigosPublicos.includes(codigo[1])) {
                return 'publico';
            }
            const palavrasPublicas = ['orgao publico', 'autarquia', 'fundacao publica', 'empresa publica', 'sociedade de economia mista', 'fundo publico', 'administracao direta', 'administracao indireta', 'administracao publica', 'poder executivo', 'poder legislativo', 'poder judiciario', 'consorcio publico', 'municipio', 'estado ou distrito federal', 'uniao', 'ente federativo'];
            if (palavrasPublicas.some(p => natureza.includes(p))) {
                return 'publico';
            }
            return tipoSetorApi || 'privado';
        },

        validarAba(aba) {
            let erros = [];
            
            if (aba === 'dados-gerais') {
                if (!this.dados.cnpj) erros.push('CNPJ é obrigatório');
                if (!this.dados.razao_social) erros.push('Razão Social é obrigatória');
                if (!this.dados.nome_fantasia) erros.push('Nome Fantasia é obrigatório');
                // Para públicos, nome fantasia deve ser diferente da razão social (nome real do estabelecimento)
                if (this.dados.tipo_setor === 'publico' && this.dados.nome_fantasia && this.dados.razao_social) {
                    if (this.dados.nome_fantasia.trim().toUpperCase() === this.dados.razao_social.trim().toUpperCase()) {
                        erros.push('Para estabelecimentos públicos, informe o nome real da unidade (ex: Hospital Regional de Araguaína) em vez da razão social genérica.');
                    }
                }
            }
            
            if (aba === 'endereco') {
                if (!this.dados.cep) erros.push('CEP é obrigatório');
                if (!this.dados.endereco) erros.push('Logradouro é obrigatório');
                if (!this.dados.numero) erros.push('Número é obrigatório');
                if (!this.dados.bairro) erros.push('Bairro é obrigatório');
                if (!this.dados.cidade) erros.push('Cidade é obrigatória');
                if (!this.dados.estado) erros.push('Estado é obrigatório');
            }
            
            if (aba === 'tipo-processo') {
                // Se está no modo de atividades especiais, valida se pelo menos uma foi selecionada
                if (this.apenasAtividadesEspeciais) {
                    if (!this.atividadeEspecialProjetoArq && !this.atividadeEspecialRotulagem) {
                        erros.push('Selecione pelo menos uma opção: Projeto Arquitetônico ou Análise de Rotulagem');
                    }
                }
            }
            
            if (aba === 'atividades') {
                // Validar se pelo menos uma atividade foi selecionada
                if (!this.atividadePrincipalMarcada && this.atividadesExercidas.length === 0) {
                    erros.push('Selecione pelo menos uma atividade que será exercida');
                }
                
                // Validar questionários
                if (this.questionarios.length > 0) {
                    const questionariosNaoRespondidos = this.questionarios.filter(q => !this.respostasQuestionario[q.cnae]);
                    if (questionariosNaoRespondidos.length > 0) {
                        erros.push('Responda todos os questionários obrigatórios');
                    }
                    
                    // Validar segunda pergunta (se existir)
                    const questionarios2NaoRespondidos = this.questionarios.filter(q => q.pergunta2 && !this.respostasQuestionario2[q.cnae]);
                    if (questionarios2NaoRespondidos.length > 0) {
                        erros.push('Responda todas as perguntas dos questionários (incluindo a segunda pergunta)');
                    }
                }

                erros.push(...this.errosFinalidade());
            }

            return erros;
        },

        proximaAba(abaAtual) {
            const erros = this.validarAba(abaAtual);
            if (erros.length > 0) {
                this.modalErro.mensagens = erros;
                this.modalErro.visivel = true;
                return;
            }

            // Para estabelecimentos públicos, verificar nome fantasia duplicado ao sair da aba dados-gerais
            if (abaAtual === 'dados-gerais' && this.dados.tipo_setor === 'publico') {
                this.verificandoNome = true;
                fetch(`${window.APP_URL}/company/estabelecimentos/verificar-nome-fantasia?nome_fantasia=${encodeURIComponent(this.dados.nome_fantasia)}&cnpj=${encodeURIComponent(this.dados.cnpj)}`)
                    .then(r => r.json())
                    .then(data => {
                        this.verificandoNome = false;
                        if (data.existe) {
                            this.modalErro.mensagens = [data.mensagem];
                            this.modalErro.visivel = true;
                            return;
                        }
                        this.avancarParaProximaAba(abaAtual);
                    })
                    .catch(() => {
                        this.verificandoNome = false;
                        this.avancarParaProximaAba(abaAtual);
                    });
                return;
            }

            this.avancarParaProximaAba(abaAtual);
        },

        avancarParaProximaAba(abaAtual) {
            const abas = ['dados-gerais', 'endereco', 'atividades', 'contato'];
            
            const indexAtual = abas.indexOf(abaAtual);
            if (indexAtual < abas.length - 1) {
                this.abaAtiva = abas[indexAtual + 1];
            }
        },

        fecharModalErro() {
            this.modalErro.visivel = false;
            this.modalErro.mensagens = [];
        },

        handleSubmit(event) {
            let erros = [];
            
            // Validar aba de contato
            if (!this.dados.vinculo_usuario) {
                erros.push('Selecione o seu vínculo com o estabelecimento');
            }
            if (!this.dados.telefone) {
                erros.push('Telefone é obrigatório');
            }
            if (!this.dados.email) {
                erros.push('E-mail é obrigatório');
            }
            
            // Validar atividades
            if (this.apenasAtividadesEspeciais) {
                // Se está no modo de atividades especiais, valida se pelo menos uma foi selecionada
                if (!this.atividadeEspecialProjetoArq && !this.atividadeEspecialRotulagem) {
                    erros.push('Selecione pelo menos uma atividade especial (Projeto Arquitetônico ou Análise de Rotulagem)');
                }
            } else {
                // Validar questionários
                if (this.questionarios.length > 0) {
                    const questionariosNaoRespondidos = this.questionarios.filter(q => !this.respostasQuestionario[q.cnae]);
                    if (questionariosNaoRespondidos.length > 0) {
                        erros.push('Responda todos os questionários obrigatórios na aba Atividades');
                    }
                    
                    // Validar segunda pergunta (se existir)
                    const questionarios2NaoRespondidos = this.questionarios.filter(q => q.pergunta2 && !this.respostasQuestionario2[q.cnae]);
                    if (questionarios2NaoRespondidos.length > 0) {
                        erros.push('Responda todas as perguntas dos questionários (incluindo a segunda pergunta)');
                    }
                }
                
                // Validar se pelo menos uma atividade foi selecionada
                if (!this.atividadePrincipalMarcada && this.atividadesExercidas.length === 0) {
                    erros.push('Selecione pelo menos uma atividade que será exercida');
                }

                erros.push(...this.errosFinalidade());
            }

            if (erros.length > 0) {
                event.preventDefault();
                this.modalErro.mensagens = erros;
                this.modalErro.visivel = true;
                return;
            }
            
            this.submitting = true;
        },

        // Funções do Modal de Estabelecimentos Existentes
        fecharModalEstabelecimentos() {
            this.modalEstabelecimentosExistentes.visivel = false;
            this.modalEstabelecimentosExistentes.estabelecimentos = [];
        },

        cancelarCadastro() {
            this.fecharModalEstabelecimentos();
            this.dadosCarregados = false;
            this.cnpjBusca = '';
            this.dados = {
                cnpj: '',
                razao_social: '',
                nome_fantasia: '',
                natureza_juridica: '',
                porte: '',
                descricao_situacao_cadastral: '',
                data_situacao_cadastral: '',
                data_inicio_atividade: '',
                data_inicio_atividade_raw: '',
                capital_social: '',
                cnae_fiscal: '',
                cnae_fiscal_descricao: '',
                cnaes_secundarios: [],
                endereco: '',
                numero: '',
                complemento: '',
                bairro: '',
                cidade: '',
                estado: '',
                cep: '',
                codigo_municipio_ibge: '',
                telefone: '',
                email: '',
                tipo_setor: 'privado'
            };
        },

        continuarCadastro() {
            this.fecharModalEstabelecimentos();
            this.mensagem = '✅ Dados encontrados com sucesso! Você pode continuar o cadastro.';
            this.tipoMensagem = 'success';
            this.dadosCarregados = true;
        },

        // Métodos para busca manual de CNAE
        async buscarCnaeAdicional() {
            if (!this.cnaeBusca || this.cnaeBusca.length < 3) {
                this.cnaeErro = 'Digite pelo menos 3 caracteres para buscar';
                return;
            }
            
            this.cnaeErro = '';
            this.loadingCnae = true;
            this.cnaeResultados = [];

            try {
                // Se for código (apenas números)
                if (/^\d+$/.test(this.cnaeBusca)) {
                    const response = await fetch(`https://servicodados.ibge.gov.br/api/v2/cnae/subclasses/${this.cnaeBusca}`);
                    if (response.ok) {
                        const data = await response.json();
                        if (data && data.id) {
                            this.cnaeResultados = [{
                                codigo: data.id,
                                descricao: data.descricao
                            }];
                        }
                    }
                } else {
                    // Busca na nossa rota local por descrição
                    const response = await fetch(`{{ route('company.estabelecimentos.buscar-cnaes') }}?q=${encodeURIComponent(this.cnaeBusca)}`);
                    if (response.ok) {
                        this.cnaeResultados = await response.json();
                    }
                }

                if (this.cnaeResultados.length === 0) {
                    this.cnaeErro = 'Nenhum CNAE encontrado';
                }
            } catch (error) {
                console.error('Erro na busca:', error);
                this.cnaeErro = 'Erro ao buscar CNAE';
            } finally {
                this.loadingCnae = false;
            }
        },

        adicionarCnaeManual(cnae) {
            // Verifica se já existe na lista de secundários
            const codigoCnae = String(cnae.codigo);
            const existe = this.dados.cnaes_secundarios.some(c => String(c.codigo) === codigoCnae);
            
            if (!existe) {
                // Adiciona à lista de secundários
                this.dados.cnaes_secundarios.unshift({
                    codigo: cnae.codigo,
                    descricao: cnae.descricao,
                    manual: true
                });
            }
            
            // Marca automaticamente
            if (!this.atividadesExercidas.includes(codigoCnae)) {
                this.atividadesExercidas.push(codigoCnae);
            }
            
            // Limpa busca
            this.cnaeBusca = '';
            this.cnaeResultados = [];
            this.mensagem = 'CNAE adicionado com sucesso!';
            this.tipoMensagem = 'success';
        }
    }
}
</script>
@endpush
@endsection
