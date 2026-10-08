@extends('layouts.admin')

@section('title', 'Editar Tipo de Processo')
@section('page-title', 'Editar Tipo de Processo')

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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Editar Tipo de Processo</h1>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $tipoProcesso->nome }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.configuracoes.tipos-processo.update', $tipoProcesso->id) }}" method="POST" class="space-y-6"
          x-data="{
              abaTipoProcesso: 'basico',
              competencia: @js(old('competencia', $tipoProcesso->competencia ?? 'municipal')),
              municipiosSelecionados: @js(old('municipios_descentralizados', $tipoProcesso->municipios_descentralizados ?? [])),
              municipiosBusca: @js($municipios->map(fn($m) => mb_strtolower($m->nome, 'UTF-8'))->values()->all()),
              buscaDesc: '',
              abertoDesc: false,
              avisoEmpresa: {{ old('exibir_aviso_abertura_empresa', $tipoProcesso->exibir_aviso_abertura_empresa) ? 'true' : 'false' }},
              anual: {{ old('anual', $tipoProcesso->anual) ? 'true' : 'false' }},
              unico: {{ old('unico_por_estabelecimento', $tipoProcesso->unico_por_estabelecimento) ? 'true' : 'false' }},
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
        @method('PUT')

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

            <div class="space-y-6">
                {{-- Nome --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nome do Tipo <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="nome" 
                           value="{{ old('nome', $tipoProcesso->nome) }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('nome') border-red-500 @enderror"
                           placeholder="Ex: Licenciamento Sanitário">
                    @error('nome')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Código --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Código <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="codigo" 
                           value="{{ old('codigo', $tipoProcesso->codigo) }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('codigo') border-red-500 @enderror"
                           placeholder="Ex: licenciamento">
                    <p class="mt-1 text-xs text-gray-500">Código único para identificação interna (sem espaços, apenas letras minúsculas e underscores)</p>
                    @error('codigo')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Descrição --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Descrição
                    </label>
                    <textarea name="descricao" 
                              rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none @error('descricao') border-red-500 @enderror"
                              placeholder="Descreva brevemente este tipo de processo...">{{ old('descricao', $tipoProcesso->descricao) }}</textarea>
                    @error('descricao')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Ordem --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Ordem de Exibição
                    </label>
                    <input type="number" 
                           name="ordem" 
                           value="{{ old('ordem', $tipoProcesso->ordem) }}"
                           min="0"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('ordem') border-red-500 @enderror"
                           placeholder="0">
                    <p class="mt-1 text-xs text-gray-500">Ordem em que aparecerá nas listagens (menor número aparece primeiro)</p>
                    @error('ordem')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Setor Responsável --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <span x-show="competencia === 'estadual'">Setor Padrão / Estadual pela Análise Inicial</span>
                        <span x-show="competencia !== 'estadual'">Setor Padrão pela Análise Inicial</span>
                    </label>
                    <select name="tipo_setor_id" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('tipo_setor_id') border-red-500 @enderror">
                        <option value="">-- Selecione um setor (opcional) --</option>
                        @foreach($tiposSetor as $setor)
                            <option value="{{ $setor->id }}" {{ old('tipo_setor_id', $tipoProcesso->tipo_setor_id) == $setor->id ? 'selected' : '' }}>
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
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Unidades --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Unidades</label>
                    <p class="text-xs text-gray-500 mb-2">Selecione as unidades que o estabelecimento poderá escolher ao abrir este tipo de processo. Se nenhuma for marcada, o processo não terá separação por unidades.</p>
                    @php
                        $unidadesDisponiveis = \App\Models\Unidade::ativas()->ordenadas()->get();
                        $unidadesSelecionadas = old('unidades', $tipoProcesso->unidades->pluck('id')->toArray());
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-base font-semibold text-gray-900">Competência e Descentralização</h3>
                </div>
            </div>
            <div class="p-6">

            <div class="space-y-6">
                {{-- Competência --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Competência <span class="text-red-500">*</span>
                    </label>
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="radio" 
                                       name="competencia" 
                                       id="competencia_municipal"
                                       value="municipal"
                                       x-model="competencia"
                                       {{ old('competencia', $tipoProcesso->competencia ?? 'municipal') === 'municipal' ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            </div>
                            <div class="ml-3">
                                <label for="competencia_municipal" class="font-medium text-gray-700">🏢 Somente Municipal</label>
                                <p class="text-sm text-gray-500">Apenas municípios podem criar este tipo de processo</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="radio" 
                                       name="competencia" 
                                       id="competencia_estadual"
                                       value="estadual"
                                       x-model="competencia"
                                       {{ old('competencia', $tipoProcesso->competencia) === 'estadual' ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            </div>
                            <div class="ml-3">
                                <label for="competencia_estadual" class="font-medium text-gray-700">🏛️ Estadual (com seleção de municípios descentralizados)</label>
                                <p class="text-sm text-gray-500">Apenas o estado pode criar, mas municípios descentralizados também podem (selecione abaixo)</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="radio" 
                                       name="competencia" 
                                       id="competencia_estadual_exclusivo"
                                       value="estadual_exclusivo"
                                       x-model="competencia"
                                       {{ old('competencia', $tipoProcesso->competencia) === 'estadual_exclusivo' ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            </div>
                            <div class="ml-3">
                                <label for="competencia_estadual_exclusivo" class="font-medium text-gray-700">🏛️ Somente Estadual</label>
                                <p class="text-sm text-gray-500">Apenas o estado pode criar este tipo de processo (sem exceções)</p>
                            </div>
                        </div>
                    </div>
                    @error('competencia')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Municípios Descentralizados (apenas para estadual) --}}
                <div x-show="competencia === 'estadual'" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
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
                                <div x-show="!municipiosBusca.some(nome => municipioMatches(nome))"
                                     class="px-3 py-2 text-sm text-gray-500">
                                    Nenhum município encontrado.
                                </div>
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">
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
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
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
                        <input type="text" name="aviso_abertura_titulo" value="{{ old('aviso_abertura_titulo', $tipoProcesso->aviso_abertura_titulo ?? 'Atenção antes de abrir este processo') }}" maxlength="255" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Mensagem para empresa <span class="text-red-500">*</span></label>
                        <textarea name="aviso_abertura_mensagem" rows="5" maxlength="5000" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Ex.: Abra este processo somente se sua empresa possui atividade de rotulagem. Se deseja renovar ou solicitar licenciamento sanitário, selecione Licenciamento Sanitário.">{{ old('aviso_abertura_mensagem', $tipoProcesso->aviso_abertura_mensagem) }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Este texto aparece no portal da empresa antes de ela confirmar a abertura.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Texto de confirmação</label>
                        <input type="text" name="aviso_abertura_confirmacao" value="{{ old('aviso_abertura_confirmacao', $tipoProcesso->aviso_abertura_confirmacao ?? 'Li e confirmo que este é o processo correto para minha solicitação.') }}" maxlength="255" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
                           {{ old('usuario_externo_pode_abrir', $tipoProcesso->usuario_externo_pode_abrir) ? 'checked' : '' }}
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
                           {{ old('exclusivo_receituario', $tipoProcesso->exclusivo_receituario) ? 'checked' : '' }}
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
                           {{ old('usuario_externo_pode_visualizar', $tipoProcesso->usuario_externo_pode_visualizar ?? true) ? 'checked' : '' }}
                           class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-900">Usuário Externo Pode Visualizar</span>
                        <p class="text-xs text-gray-500 mt-0.5">Empresas podem visualizar este processo quando aberto por usuário interno. Desmarque para processos internos como Descentralização, Denúncia, etc.</p>
                    </div>
                </label>

                {{-- Exibir Fila Pública --}}
                <div x-data="{ filaPublica: {{ old('exibir_fila_publica', $tipoProcesso->exibir_fila_publica) ? 'true' : 'false' }} }">
                    <label class="flex items-start gap-3 p-2.5 hover:bg-gray-50 rounded-lg cursor-pointer transition-colors border-2 border-purple-200 bg-purple-50">
                        <input type="checkbox" 
                               name="exibir_fila_publica" 
                               id="exibir_fila_publica"
                               x-model="filaPublica"
                               {{ old('exibir_fila_publica', $tipoProcesso->exibir_fila_publica) ? 'checked' : '' }}
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
                            <label class="block text-sm font-medium text-gray-900 mb-1.5">
                                <svg class="w-4 h-4 inline-block text-purple-600 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Prazo padrão para análise (em dias)
                            </label>
                            <input type="number" name="prazo_fila_publica" id="prazo_fila_publica"
                                   value="{{ old('prazo_fila_publica', $tipoProcesso->prazo_fila_publica) }}"
                                   min="1" max="365" placeholder="Ex: 30"
                                   class="w-32 px-3 py-2 text-sm border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <p class="text-xs text-gray-500 mt-1.5">
                                Prazo padrão em dias úteis. Usado quando não houver prazo específico por grau de risco.
                            </p>
                        </div>

                        {{-- Prazos por Grau de Risco --}}
                        <div class="pt-3 border-t border-purple-200">
                            <label class="block text-sm font-medium text-gray-900 mb-2">
                                Prazo por Grau de Risco (opcional)
                            </label>
                            <p class="text-xs text-gray-500 mb-3">Se preenchido, o prazo será definido pelo grau de risco do estabelecimento. Caso contrário, usa o prazo padrão acima.</p>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-red-700 mb-1">Alto Risco</label>
                                    <input type="number" name="prazo_fila_publica_alto"
                                           value="{{ old('prazo_fila_publica_alto', $tipoProcesso->prazo_fila_publica_alto) }}"
                                           min="1" max="365" placeholder="Ex: 60"
                                           class="w-full px-3 py-2 text-sm border border-red-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-amber-700 mb-1">Médio Risco</label>
                                    <input type="number" name="prazo_fila_publica_medio"
                                           value="{{ old('prazo_fila_publica_medio', $tipoProcesso->prazo_fila_publica_medio) }}"
                                           min="1" max="365" placeholder="Ex: 30"
                                           class="w-full px-3 py-2 text-sm border border-amber-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-green-700 mb-1">Baixo Risco</label>
                                    <input type="number" name="prazo_fila_publica_baixo"
                                           value="{{ old('prazo_fila_publica_baixo', $tipoProcesso->prazo_fila_publica_baixo) }}"
                                           min="1" max="365" placeholder="Ex: 15"
                                           class="w-full px-3 py-2 text-sm border border-green-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                </div>
                            </div>
                        </div>
                        
                        {{-- Checkbox para exibir aviso no processo --}}
                        <label class="flex items-start gap-3 p-2 hover:bg-purple-100/50 rounded-lg cursor-pointer transition-colors">
                            <input type="checkbox" 
                                   name="exibir_aviso_prazo_fila" 
                                   id="exibir_aviso_prazo_fila"
                                   {{ old('exibir_aviso_prazo_fila', $tipoProcesso->exibir_aviso_prazo_fila) ? 'checked' : '' }}
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
                           {{ old('ativo', $tipoProcesso->ativo) ? 'checked' : '' }}
                           class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-900">Ativo</span>
                        <p class="text-xs text-gray-500 mt-0.5">Tipo de processo disponível para uso no sistema</p>
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
                Salvar Alterações
            </button>
        </div>
        </div>
    </form>
</div>

<script>
// === Filtrar setores por município nos selects de "Setor Responsável Municipal" ===
(function() {
    const tiposSetorData = @json($tiposSetorJs);

    function popularSelectSetor(select) {
        const municipioId = parseInt(select.dataset.municipioId);
        const valorAtual = select.dataset.valorAtual;

        // Filtra: setores globais (sem municipio_ids) + setores vinculados a este município
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
