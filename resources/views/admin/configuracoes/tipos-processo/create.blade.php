@extends('layouts.admin')

@section('title', 'Novo Tipo de Processo')
@section('page-title', 'Novo Tipo de Processo')

@section('content')
<div class="max-w-8xl mx-auto">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ route('admin.configuracoes.tipos-processo.index') }}" 
               class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border-2 border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-blue-300 transition-all shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-1">
                    <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Novo Tipo de Processo</h1>
                        <p class="text-sm text-gray-500 mt-0.5">Adicione um novo tipo de processo ao sistema</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Erros de Validação --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-red-800 mb-2">Erro ao criar tipo de processo</h3>
                    <ul class="text-sm text-red-700 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 mt-0.5">•</span>
                                <span>{{ $error }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Form --}}
    <form action="{{ route('admin.configuracoes.tipos-processo.store') }}" method="POST" class="space-y-6"
          x-data="{
              abaTipoProcesso: 'basico',
              competencia: @js(old('competencia', 'municipal')),
              municipiosSelecionados: @js(old('municipios_descentralizados', [])),
              municipiosBusca: @js($municipios->map(fn($m) => mb_strtolower($m->nome, 'UTF-8'))->values()->all()),
              buscaDesc: '',
              abertoDesc: false,
              avisoEmpresa: {{ old('exibir_aviso_abertura_empresa') ? 'true' : 'false' }},
              anual: {{ old('anual') ? 'true' : 'false' }},
              unico: {{ old('unico_por_estabelecimento') ? 'true' : 'false' }},
              municipioMatches(nome) {
                  return String(nome || '').toLowerCase().includes(String(this.buscaDesc || '').toLowerCase());
              },
              selecionarMunicipio(nome) {
                  if (!this.municipiosSelecionados.includes(nome)) {
                      this.municipiosSelecionados.push(nome);
                  }
                  this.buscaDesc = '';
                  this.abertoDesc = false;
              },
              toggleAnual() {
                  this.anual = !this.anual;
                  if (this.anual) this.unico = false;
              },
              toggleUnico() {
                  this.unico = !this.unico;
                  if (this.unico) this.anual = false;
              }
          }">
        @csrf

        <div class="space-y-6">

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-1.5 flex flex-wrap gap-1">
            <button type="button" @click="abaTipoProcesso = 'basico'" :class="abaTipoProcesso === 'basico' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-2 rounded-lg text-xs font-semibold transition">Básico</button>
            <button type="button" @click="abaTipoProcesso = 'competencia'" :class="abaTipoProcesso === 'competencia' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-2 rounded-lg text-xs font-semibold transition">Competência</button>
            <button type="button" @click="abaTipoProcesso = 'portal'" :class="abaTipoProcesso === 'portal' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-2 rounded-lg text-xs font-semibold transition">Portal da empresa</button>
            <button type="button" @click="abaTipoProcesso = 'regras'" :class="abaTipoProcesso === 'regras' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-2 rounded-lg text-xs font-semibold transition">Regras e fila</button>
        </div>

        {{-- Card Principal --}}
        <div x-show="abaTipoProcesso === 'basico'" x-cloak class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-base font-semibold text-gray-900">Informações Básicas</h3>
                </div>
            </div>
            <div class="p-6">

            <div class="space-y-4">
                {{-- Nome e Código --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            Nome do Tipo <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="nome" 
                               value="{{ old('nome') }}"
                               required
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('nome') border-red-500 @enderror"
                               placeholder="Ex: Licenciamento Sanitário">
                        @error('nome')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">
                            Código <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="codigo" 
                               value="{{ old('codigo') }}"
                               required
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('codigo') border-red-500 @enderror"
                               placeholder="Ex: licenciamento">
                        @error('codigo')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Descrição --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
                        Descrição
                    </label>
                    <textarea name="descricao" 
                              rows="2"
                              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none @error('descricao') border-red-500 @enderror"
                              placeholder="Descreva brevemente este tipo de processo...">{{ old('descricao') }}</textarea>
                    @error('descricao')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Ordem --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
                        Ordem de Exibição
                    </label>
                    <input type="number" 
                           name="ordem" 
                           value="{{ old('ordem', 0) }}"
                           min="0"
                           class="w-32 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('ordem') border-red-500 @enderror"
                           placeholder="0">
                    <p class="mt-1 text-xs text-gray-500">Menor número aparece primeiro</p>
                    @error('ordem')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Setor Responsável --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
                        <span x-show="competencia === 'estadual'">Setor Padrão / Estadual pela Análise Inicial</span>
                        <span x-show="competencia !== 'estadual'">Setor Padrão pela Análise Inicial</span>
                    </label>
                    <select name="tipo_setor_id" 
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('tipo_setor_id') border-red-500 @enderror">
                        <option value="">-- Selecione um setor (opcional) --</option>
                        @foreach($tiposSetor as $setor)
                            <option value="{{ $setor->id }}" {{ old('tipo_setor_id') == $setor->id ? 'selected' : '' }}>
                                {{ $setor->nome }}
                            </option>
                        @endforeach
                    </select>
                    <p x-show="competencia === 'estadual_exclusivo'" class="mt-1 text-xs text-gray-500">
                        Usado para encaminhar automaticamente os processos deste tipo para o setor inicial definido.
                    </p>
                    <p x-show="competencia === 'municipal'" class="mt-1 text-xs text-gray-500">
                        Usado como setor padrão quando não houver um setor municipal específico configurado para o município.
                    </p>
                    <p x-show="competencia === 'estadual'" class="mt-1 text-xs text-gray-500">
                        Usado para processos estaduais e como fallback quando não houver setor municipal configurado para o município.
                    </p>
                    @error('tipo_setor_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Unidades --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Unidades</label>
                    <p class="text-xs text-gray-500 mb-2">Selecione as unidades que o estabelecimento poderá escolher ao abrir este tipo de processo.</p>
                    @php
                        $unidadesDisponiveis = \App\Models\Unidade::ativas()->ordenadas()->get();
                        $unidadesSelecionadas = old('unidades', []);
                    @endphp
                    @if($unidadesDisponiveis->count() > 0)
                        <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                            @foreach($unidadesDisponiveis as $unidade)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="unidades[]" value="{{ $unidade->id }}"
                                           {{ in_array($unidade->id, $unidadesSelecionadas) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">{{ $unidade->nome }}</span>
                                    @if($unidade->descricao)
                                        <span class="text-xs text-gray-400">- {{ $unidade->descricao }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-gray-400 italic">Nenhuma unidade cadastrada. <a href="{{ route('admin.configuracoes.unidades.index') }}" class="text-blue-600 hover:underline">Cadastrar unidades</a></p>
                    @endif
                </div>
            </div>
        </div>
        </div>

        {{-- Card Competência e Descentralização --}}
        <div x-show="abaTipoProcesso === 'competencia'" x-cloak class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <h3 class="text-base font-semibold text-gray-900">Competência</h3>
                </div>
            </div>
            <div class="p-6">

            <div class="space-y-4">
                {{-- Competência --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-2">
                        Tipo de Competência <span class="text-red-500">*</span>
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-start p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                            <input type="radio" 
                                   name="competencia" 
                                   id="competencia_municipal"
                                   value="municipal"
                                   x-model="competencia"
                                   {{ old('competencia', 'municipal') === 'municipal' ? 'checked' : '' }}
                                   class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <div class="ml-3 flex-1">
                                <span class="text-sm font-medium text-gray-900">🏢 Somente Municipal</span>
                                <p class="text-xs text-gray-500 mt-0.5">Apenas municípios podem criar este tipo de processo</p>
                            </div>
                        </label>

                        <label class="flex items-start p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                            <input type="radio" 
                                   name="competencia" 
                                   id="competencia_estadual"
                                   value="estadual"
                                   x-model="competencia"
                                   {{ old('competencia') === 'estadual' ? 'checked' : '' }}
                                   class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <div class="ml-3 flex-1">
                                <span class="text-sm font-medium text-gray-900">🏛️ Estadual (com municípios descentralizados)</span>
                                <p class="text-xs text-gray-500 mt-0.5">Estado pode criar, e municípios descentralizados também (selecione abaixo)</p>
                            </div>
                        </label>

                        <label class="flex items-start p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                            <input type="radio" 
                                   name="competencia" 
                                   id="competencia_estadual_exclusivo"
                                   value="estadual_exclusivo"
                                   x-model="competencia"
                                   {{ old('competencia') === 'estadual_exclusivo' ? 'checked' : '' }}
                                   class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <div class="ml-3 flex-1">
                                <span class="text-sm font-medium text-gray-900">🏛️ Somente Estadual</span>
                                <p class="text-xs text-gray-500 mt-0.5">Apenas o estado pode criar (sem exceções)</p>
                            </div>
                        </label>
                    </div>
                    @error('competencia')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Municípios Descentralizados (apenas para estadual) --}}
                <div x-show="competencia === 'estadual'" x-cloak class="pt-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
                        Municípios Descentralizados
                    </label>
                    <p class="mb-3 text-xs text-gray-500">
                        Busque e selecione os municípios que terão permissão para criar este tipo de processo.
                    </p>

                    <div class="space-y-3">
                        {{-- Campo de busca --}}
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input type="text"
                                   x-model="buscaDesc"
                                   @focus="abertoDesc = true"
                                   @click.away="abertoDesc = false"
                                   placeholder="Digite para buscar município..."
                                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">

                            {{-- Dropdown de resultados --}}
                            <div x-show="abertoDesc && buscaDesc.length >= 1"
                                 x-transition
                                 class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                @foreach($municipios as $municipio)
                                    <button type="button"
                                            data-nome="{{ $municipio->nome }}"
                                            data-nome-busca="{{ mb_strtolower($municipio->nome, 'UTF-8') }}"
                                            x-show="municipioMatches($el.dataset.nomeBusca)"
                                            @mousedown.prevent="selecionarMunicipio($el.dataset.nome)"
                                            class="w-full text-left px-3 py-2 text-sm transition"
                                            :class="municipiosSelecionados.includes($el.dataset.nome) ? 'bg-blue-50 text-blue-400' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-700'">
                                        {{ $municipio->nome }}
                                        <span data-nome="{{ $municipio->nome }}" x-show="municipiosSelecionados.includes($el.dataset.nome)" class="text-xs ml-1">✓</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Tags dos selecionados --}}
                        <div x-show="municipiosSelecionados.length > 0" class="space-y-2">
                            <span class="text-xs text-gray-500" x-text="municipiosSelecionados.length + ' município(s) selecionado(s)'"></span>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="nome in municipiosSelecionados" :key="nome">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-100 text-blue-800 rounded-lg text-xs font-medium">
                                        <span x-text="nome"></span>
                                        <button type="button" @click="municipiosSelecionados = municipiosSelecionados.filter(n => n !== nome)" class="text-blue-600 hover:text-blue-900">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </span>
                                </template>
                            </div>
                        </div>

                        {{-- Hidden inputs --}}
                        <template x-for="nome in municipiosSelecionados" :key="'hidden-'+nome">
                            <input type="hidden" name="municipios_descentralizados[]" :value="nome">
                        </template>
                    </div>
                </div>

                <div x-show="competencia === 'municipal' || competencia === 'estadual'" x-cloak class="pt-2 border-t border-gray-100">
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
                        Setor Responsável Municipal por Município
                    </label>
                    <p class="mb-3 text-xs text-gray-500">
                        Defina o setor inicial dos processos municipais. Para tipos estaduais descentralizados, configure apenas os municípios selecionados acima.
                    </p>

                    <div class="max-h-80 overflow-y-auto rounded-lg border border-gray-200">
                        <div class="divide-y divide-gray-200">
                            @foreach($municipios as $municipio)
                                  <div class="grid grid-cols-1 gap-3 px-4 py-3 md:grid-cols-[minmax(0,1fr)_minmax(260px,320px)]"
                                      x-show="competencia === 'municipal' || municipiosSelecionados.includes(@js($municipio->nome))">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $municipio->nome }}</p>
                                        <p class="text-xs text-gray-500">Setor inicial quando o estabelecimento deste município abrir o processo.</p>
                                    </div>
                                    <div>
                                        <select name="setores_municipais[{{ $municipio->id }}]"
                                                class="setor-municipal-select w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                data-municipio-id="{{ $municipio->id }}"
                                                data-valor-atual="{{ old('setores_municipais.' . $municipio->id, $setoresMunicipaisPorMunicipio[$municipio->id] ?? '') }}">
                                            <option value="">-- Usar setor padrão / sem override --</option>
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @error('setores_municipais.*')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        </div>

        {{-- Card Portal da Empresa --}}
        <div x-show="abaTipoProcesso === 'portal'" x-cloak class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                    </svg>
                    <h3 class="text-base font-semibold text-gray-900">Portal da empresa</h3>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <label class="flex items-start gap-3 rounded-xl border border-indigo-200 bg-indigo-50 p-4 cursor-pointer">
                    <input type="checkbox" name="exibir_aviso_abertura_empresa" x-model="avisoEmpresa" class="mt-0.5 w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <div class="flex-1">
                        <span class="text-sm font-semibold text-gray-900">Exibir aviso obrigatório antes da empresa abrir este processo</span>
                        <p class="text-xs text-gray-600 mt-1">Use para evitar abertura errada, por exemplo quando a empresa confunde análise de rotulagem com licenciamento.</p>
                    </div>
                </label>

                <div x-show="avisoEmpresa" x-transition class="space-y-4 rounded-xl border border-indigo-100 bg-white p-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Título do aviso</label>
                        <input type="text" name="aviso_abertura_titulo" value="{{ old('aviso_abertura_titulo', 'Atenção antes de abrir este processo') }}" maxlength="255" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Mensagem para empresa <span class="text-red-500">*</span></label>
                        <textarea name="aviso_abertura_mensagem" rows="5" maxlength="5000" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Ex.: Abra este processo somente se sua empresa possui atividade de rotulagem. Se deseja renovar ou solicitar licenciamento sanitário, selecione Licenciamento Sanitário.">{{ old('aviso_abertura_mensagem') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Este texto aparece no portal da empresa antes de ela confirmar a abertura.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Texto de confirmação</label>
                        <input type="text" name="aviso_abertura_confirmacao" value="{{ old('aviso_abertura_confirmacao', 'Li e confirmo que este é o processo correto para minha solicitação.') }}" maxlength="255" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Configurações --}}
        <div x-show="abaTipoProcesso === 'regras'" x-cloak class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <h3 class="text-base font-semibold text-gray-900">Configurações</h3>
                </div>
            </div>
            <div class="p-6">

            <div class="space-y-3">
                {{-- Processo Anual --}}
                <label class="flex items-start gap-3 p-2.5 hover:bg-gray-50 rounded-lg cursor-pointer transition-colors"
                       :class="anual ? 'bg-blue-50 border border-blue-200' : ''">
                    <input type="checkbox" 
                           name="anual" 
                           id="anual"
                           x-model="anual"
                           @click="toggleAnual()"
                           class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-900">Processo Anual</span>
                        <p class="text-xs text-gray-500 mt-0.5">Apenas um processo por estabelecimento por ano</p>
                    </div>
                </label>

                {{-- Processo Único --}}
                <label class="flex items-start gap-3 p-2.5 hover:bg-gray-50 rounded-lg cursor-pointer transition-colors"
                       :class="unico ? 'bg-blue-50 border border-blue-200' : ''">
                    <input type="checkbox" 
                           name="unico_por_estabelecimento" 
                           id="unico_por_estabelecimento"
                           x-model="unico"
                           @click="toggleUnico()"
                           class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-900">Processo Único por Estabelecimento</span>
                        <p class="text-xs text-gray-500 mt-0.5">Estabelecimento poderá abrir este processo apenas UMA VEZ (não renovável)</p>
                    </div>
                </label>

                {{-- Usuário Externo Pode Abrir --}}
                <label class="flex items-start gap-3 p-2.5 hover:bg-gray-50 rounded-lg cursor-pointer transition-colors">
                    <input type="checkbox" 
                           name="usuario_externo_pode_abrir" 
                           id="usuario_externo_pode_abrir"
                           {{ old('usuario_externo_pode_abrir') ? 'checked' : '' }}
                           class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-900">Usuário Externo Pode Abrir</span>
                        <p class="text-xs text-gray-500 mt-0.5">Empresas podem abrir este tipo de processo</p>
                    </div>
                </label>

                {{-- Exclusivo da área de Receituários --}}
                <label class="flex items-start gap-3 p-2.5 hover:bg-gray-50 rounded-lg cursor-pointer transition-colors">
                    <input type="checkbox"
                           name="exclusivo_receituario"
                           id="exclusivo_receituario"
                           {{ old('exclusivo_receituario') ? 'checked' : '' }}
                           class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-900">Exclusivo da área de Receituários</span>
                        <p class="text-xs text-gray-500 mt-0.5">Só pode ser aberto em Receituários, para o profissional com cadastro aprovado. Não aparece na abertura de processo dos estabelecimentos.</p>
                    </div>
                </label>

                {{-- Usuário Externo Pode Visualizar --}}
                <label class="flex items-start gap-3 p-2.5 hover:bg-gray-50 rounded-lg cursor-pointer transition-colors">
                    <input type="checkbox" 
                           name="usuario_externo_pode_visualizar" 
                           id="usuario_externo_pode_visualizar"
                           {{ old('usuario_externo_pode_visualizar', true) ? 'checked' : '' }}
                           class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-900">Usuário Externo Pode Visualizar</span>
                        <p class="text-xs text-gray-500 mt-0.5">Empresas podem visualizar processos abertos por usuário interno</p>
                    </div>
                </label>

                {{-- Exibir Fila Pública --}}
                <div x-data="{ filaPublica: {{ old('exibir_fila_publica') ? 'true' : 'false' }} }">
                    <label class="flex items-start gap-3 p-2.5 hover:bg-gray-50 rounded-lg cursor-pointer transition-colors border-2 border-purple-200 bg-purple-50">
                        <input type="checkbox" 
                               name="exibir_fila_publica" 
                               id="exibir_fila_publica"
                               x-model="filaPublica"
                               {{ old('exibir_fila_publica') ? 'checked' : '' }}
                               class="mt-0.5 w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                        <div class="flex-1">
                            <span class="text-sm font-medium text-gray-900 flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Exibir Fila Pública
                            </span>
                            <p class="text-xs text-gray-600 mt-0.5">
                                Processos deste tipo serão exibidos na página inicial pública <strong>após todos os documentos obrigatórios serem aprovados</strong>.
                                O prazo de análise começa a contar a partir da aprovação do último documento obrigatório.
                            </p>
                        </div>
                    </label>
                    
                    {{-- Campo de Prazo (visível apenas quando fila pública está marcada) --}}
                    <div x-show="filaPublica" x-transition class="mt-3 ml-7 p-3 bg-purple-50 border border-purple-200 rounded-lg space-y-4">
                        <div>
                            <label for="prazo_fila_publica" class="block text-sm font-medium text-gray-900 mb-1.5">
                                <svg class="w-4 h-4 inline-block text-purple-600 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Prazo para análise (em dias)
                            </label>
                            <input type="number" 
                                   name="prazo_fila_publica" 
                                   id="prazo_fila_publica"
                                   value="{{ old('prazo_fila_publica') }}"
                                   min="1" max="365"
                                   placeholder="Ex: 30"
                                   class="w-32 px-3 py-2 text-sm border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <p class="text-xs text-gray-500 mt-1.5">
                                Prazo em dias úteis que a equipe da vigilância tem para analisar o processo após todos os documentos obrigatórios estarem <strong>completos e aprovados</strong>.
                            </p>
                        </div>
                        
                        {{-- Checkbox para exibir aviso no processo --}}
                        <label class="flex items-start gap-3 p-2 hover:bg-purple-100/50 rounded-lg cursor-pointer transition-colors">
                            <input type="checkbox" 
                                   name="exibir_aviso_prazo_fila" 
                                   id="exibir_aviso_prazo_fila"
                                   {{ old('exibir_aviso_prazo_fila') ? 'checked' : '' }}
                                   class="mt-0.5 w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-900 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                    </svg>
                                    Exibir aviso de prazo no processo
                                </span>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Quando a documentação estiver completa e aprovada, exibe um aviso na página do processo informando quantos dias faltam para o prazo de análise.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Ativo --}}
                <label class="flex items-start gap-3 p-2.5 hover:bg-gray-50 rounded-lg cursor-pointer transition-colors">
                    <input type="checkbox" 
                           name="ativo" 
                           id="ativo"
                           {{ old('ativo', true) ? 'checked' : '' }}
                           class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-900">Ativo</span>
                        <p class="text-xs text-gray-500 mt-0.5">Disponível para uso no sistema</p>
                    </div>
                </label>
            </div>
        </div>
        </div>

        {{-- Botões --}}
        <div class="flex items-center justify-between gap-4 pt-4 border-t border-gray-200">
            <a href="{{ route('admin.configuracoes.tipos-processo.index') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Cancelar
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-md hover:shadow-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Criar Tipo de Processo
            </button>
        </div>
        </div>
    </form>
</div>

<script>
(function() {
    const tiposSetorData = @json($tiposSetorJs);

    function popularSelectSetor(select) {
        const municipioId = parseInt(select.dataset.municipioId);
        const valorAtual = select.dataset.valorAtual;

        const setoresDisponiveis = tiposSetorData.filter(function(setor) {
            if (setor.municipio_ids.length === 0) return true;
            return setor.municipio_ids.includes(municipioId);
        });

        select.innerHTML = '<option value="">-- Usar setor padrão / sem override --</option>';
        setoresDisponiveis.forEach(function(setor) {
            const option = document.createElement('option');
            option.value = setor.id;
            option.textContent = setor.nome;
            if (valorAtual && String(setor.id) === String(valorAtual)) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    }

    document.querySelectorAll('.setor-municipal-select').forEach(popularSelectSetor);
})();
</script>
@endsection
