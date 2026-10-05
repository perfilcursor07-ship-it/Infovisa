@extends('layouts.admin')

@section('title', 'Editar Tipo de Documento')
@section('page-title', 'Editar Tipo de Documento')

@section('content')
<div class="max-w-8xl mx-auto">
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-sm text-gray-600">
            <a href="{{ route('admin.configuracoes.index') }}" class="hover:text-blue-600">Configurações</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('admin.configuracoes.tipos-documento.index') }}" class="hover:text-blue-600">Tipos de Documento</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-900 font-medium">Editar: {{ $tipoDocumento->nome }}</span>
        </nav>
    </div>

    {{-- Formulário --}}
    <form method="POST" action="{{ route('admin.configuracoes.tipos-documento.update', $tipoDocumento->id) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Card Principal --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-6">Informações do Tipo</h3>

            <div class="space-y-6">
                {{-- Grid: Nome e Ordem --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Nome --}}
                    <div class="md:col-span-2">
                        <label for="nome" class="block text-sm font-medium text-gray-700 mb-2">
                            Nome do Tipo <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="nome" 
                               id="nome" 
                               value="{{ old('nome', $tipoDocumento->nome) }}"
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('nome') border-red-500 @enderror"
                               placeholder="Ex: Alvará Sanitário">
                        @error('nome')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Ordem --}}
                    <div>
                        <label for="ordem" class="block text-sm font-medium text-gray-700 mb-2">
                            Ordem
                        </label>
                        <input type="number" 
                               name="ordem" 
                               id="ordem" 
                               value="{{ old('ordem', $tipoDocumento->ordem) }}"
                               min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('ordem') border-red-500 @enderror">
                        @error('ordem')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Código --}}
                <div>
                    <label for="codigo" class="block text-sm font-medium text-gray-700 mb-2">
                        Código (opcional)
                    </label>
                    <input type="text" 
                           name="codigo" 
                           id="codigo" 
                           value="{{ old('codigo', $tipoDocumento->codigo) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('codigo') border-red-500 @enderror"
                           placeholder="Ex: alvara_sanitario">
                    <p class="mt-1 text-xs text-gray-500">Se não informado, será gerado automaticamente a partir do nome</p>
                    @error('codigo')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Visibilidade por Nível (somente admin) --}}
                @if(auth('interno')->user()->isAdmin())
                <div class="border border-indigo-200 rounded-lg p-4 bg-indigo-50">
                    <div class="flex items-center mb-3">
                        <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <label class="text-sm font-semibold text-gray-900">Visibilidade por Nível</label>
                    </div>
                    <p class="text-xs text-gray-600 mb-3">Define quais usuários podem ver e usar este tipo de documento.</p>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 p-2.5 bg-white rounded-lg border border-gray-200 cursor-pointer hover:border-indigo-300 transition">
                            <input type="radio" name="visibilidade" value="todos" {{ old('visibilidade', $tipoDocumento->visibilidade) === 'todos' ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900">Todos</span>
                                <p class="text-[11px] text-gray-500">Visível para estadual e municipal</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-2.5 bg-white rounded-lg border border-gray-200 cursor-pointer hover:border-indigo-300 transition">
                            <input type="radio" name="visibilidade" value="estadual" {{ old('visibilidade', $tipoDocumento->visibilidade) === 'estadual' ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900">Somente Estadual</span>
                                <p class="text-[11px] text-gray-500">Gestor Estadual e Técnico Estadual</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-2.5 bg-white rounded-lg border border-gray-200 cursor-pointer hover:border-indigo-300 transition">
                            <input type="radio" name="visibilidade" value="municipal" {{ old('visibilidade', $tipoDocumento->visibilidade) === 'municipal' ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900">Somente Municipal</span>
                                <p class="text-[11px] text-gray-500">Gestor Municipal e Técnico Municipal</p>
                            </div>
                        </label>
                    </div>
                </div>
                @else
                <input type="hidden" name="visibilidade" value="{{ $tipoDocumento->visibilidade }}">
                @endif

                @include('configuracoes.tipos-documento.partials.escopo-processos', ['tipoDocumento' => $tipoDocumento ?? null])

                {{-- Descrição --}}
                <div>
                    <label for="descricao" class="block text-sm font-medium text-gray-700 mb-2">
                        Descrição
                    </label>
                    <textarea name="descricao" 
                              id="descricao" 
                              rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('descricao') border-red-500 @enderror"
                              placeholder="Descreva o propósito deste tipo (opcional)">{{ old('descricao', $tipoDocumento->descricao) }}</textarea>
                    @error('descricao')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Grid: Prazo e Status --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Configuração de Prazo --}}
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <div class="flex items-center mb-3">
                            <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <label class="text-sm font-semibold text-gray-900">
                                Configuração de Prazo
                            </label>
                        </div>

                        {{-- Checkbox: Tem Prazo --}}
                        <div class="mb-3">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" 
                                       name="tem_prazo" 
                                       id="tem_prazo" 
                                       value="1"
                                       {{ old('tem_prazo', $tipoDocumento->tem_prazo) ? 'checked' : '' }}
                                       onchange="togglePrazoPadrao()"
                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                <span class="ms-2 text-sm text-gray-700">Este tipo de documento possui prazo/validade</span>
                            </label>
                            <p class="mt-1 text-xs text-gray-500 ml-6">
                                Ex: Alvarás, Notificações, Licenças, etc.
                            </p>
                        </div>

                        {{-- Campo: Prazo Padrão (opcional) --}}
                        <div id="prazo_padrao_container" style="display: {{ old('tem_prazo', $tipoDocumento->tem_prazo) ? 'block' : 'none' }};">
                            <label for="prazo_padrao_dias" class="block text-sm font-medium text-gray-700 mb-2">
                                Prazo Padrão (opcional)
                            </label>
                            <div class="flex items-center gap-2 mb-3">
                                <input type="number" 
                                       name="prazo_padrao_dias" 
                                       id="prazo_padrao_dias" 
                                       value="{{ old('prazo_padrao_dias', $tipoDocumento->prazo_padrao_dias) }}"
                                       min="1"
                                       class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('prazo_padrao_dias') border-red-500 @enderror"
                                       placeholder="Ex: 365">
                                <span class="text-sm text-gray-600">dias</span>
                            </div>
                            @error('prazo_padrao_dias')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-xs text-gray-500">
                                Se informado, será sugerido automaticamente ao criar o documento. O usuário poderá escolher entre dias corridos ou úteis na criação.
                            </p>

                            {{-- Checkbox: É documento de notificação/fiscalização --}}
                            <div class="mt-4 p-3 bg-white border border-gray-200 rounded-lg">
                                <label class="flex items-start cursor-pointer">
                                    <input type="checkbox" 
                                           name="prazo_notificacao" 
                                           id="prazo_notificacao"
                                           value="1"
                                           {{ old('prazo_notificacao', $tipoDocumento->prazo_notificacao) ? 'checked' : '' }}
                                           class="mt-0.5 w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                    <div class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">Documento de notificação/fiscalização (§1º)</span>
                                        <p class="text-xs text-gray-600 mt-1">
                                            Marque se for: <strong>Notificação, Auto de Infração, Intimação</strong> ou similar.
                                        </p>
                                        <div class="mt-2 text-xs">
                                            <div class="flex items-start gap-1.5 text-amber-700 bg-amber-50 p-2 rounded border border-amber-200">
                                                <svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                </svg>
                                                <div>
                                                    <strong>Se marcado (§1º):</strong>
                                                    <p class="mt-0.5">Prazo inicia quando o estabelecimento visualizar o documento OU após 5 dias úteis da última assinatura.</p>
                                                </div>
                                            </div>
                                            <div class="flex items-start gap-1.5 text-blue-700 bg-blue-50 p-2 rounded mt-1.5 border border-blue-200">
                                                <svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span><strong>Se desmarcado:</strong> Prazo fixo contado da data de criação (ex: Alvará válido por 1 ano)</span>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <label for="ativo" class="block text-sm font-semibold text-gray-900 mb-3">
                            Status do Tipo
                        </label>
                        <div class="flex items-center">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" 
                                       name="ativo" 
                                       id="ativo" 
                                       value="1"
                                       {{ old('ativo', $tipoDocumento->ativo) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                <span class="ms-3 text-sm font-medium text-gray-700">Ativo</span>
                            </label>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">
                            Tipos inativos não aparecem na lista de criação de documentos
                        </p>
                    </div>
                </div>

                {{-- Opção: Abrir Processo Automaticamente --}}
                <div class="border border-indigo-200 rounded-lg p-4 bg-indigo-50" x-data="{ abrirProcesso: {{ old('abrir_processo_automaticamente', $tipoDocumento->abrir_processo_automaticamente) ? 'true' : 'false' }} }">
                    <div class="flex items-center mb-3">
                        <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <label class="text-sm font-semibold text-gray-900">Abrir Processo Automaticamente</label>
                    </div>
                    <label class="flex items-start cursor-pointer">
                        <input type="checkbox" name="abrir_processo_automaticamente" value="1" x-model="abrirProcesso"
                               class="mt-0.5 w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700">
                            Ao criar este tipo de documento em um estabelecimento (sem processo), criar um processo automaticamente e vincular o documento.
                        </span>
                    </label>
                    <div x-show="abrirProcesso" x-transition class="mt-3 pt-3 border-t border-indigo-200">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Processo</label>
                        <select name="tipo_processo_codigo" class="w-full px-3 py-2 text-sm border border-indigo-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Selecione o tipo de processo</option>
                            @foreach(\App\Models\TipoProcesso::ativos()->ordenado()->get() as $tp)
                                <option value="{{ $tp->codigo }}" {{ old('tipo_processo_codigo', $tipoDocumento->tipo_processo_codigo) === $tp->codigo ? 'selected' : '' }}>
                                    {{ $tp->nome }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">O processo será criado com este tipo quando o documento for criado sem processo vinculado.</p>
                    </div>
                </div>

                {{-- Opção: Permitir Resposta do Estabelecimento --}}
                <div class="border border-green-200 rounded-lg p-4 bg-green-50" x-data="{ permiteResposta: {{ old('permite_resposta', $tipoDocumento->permite_resposta) ? 'true' : 'false' }} }">
                    <div class="flex items-center mb-3">
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        <label class="text-sm font-semibold text-gray-900">
                            Resposta do Estabelecimento
                        </label>
                    </div>

                    <label class="flex items-start cursor-pointer">
                        <input type="checkbox" 
                               name="permite_resposta" 
                               id="permite_resposta"
                               value="1"
                               x-model="permiteResposta"
                               onchange="if (!this.checked) document.getElementById('exige_itens_atendimento').checked = false"
                               class="mt-0.5 w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500 focus:ring-2">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900">Permitir que a empresa responda este documento</span>
                            <p class="text-xs text-gray-600 mt-1">
                                Quando marcado, o estabelecimento poderá enviar uma resposta em PDF vinculada a este documento.
                            </p>
                        </div>
                    </label>

                    {{-- Documentos exigidos na resposta (aparece só se permite_resposta) --}}
                    @if(isset($tiposResposta) && $tiposResposta->count() > 0)
                    <div x-show="permiteResposta" x-transition class="mt-4 pt-4 border-t border-green-200">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Documentos Exigidos na Resposta
                            </label>
                            <a href="{{ route('admin.configuracoes.tipos-documento.index', ['aba' => 'resposta']) }}" class="text-[10px] text-blue-600 hover:text-blue-800 font-medium">Gerenciar tipos →</a>
                        </div>
                        <p class="text-xs text-gray-500 mb-3">Selecione quais documentos o estabelecimento deve enviar. Se nenhum for selecionado, o upload será livre.</p>
                        @php $vinculados = $tipoDocumento->tiposDocumentoResposta->pluck('id')->toArray(); @endphp
                        <div class="space-y-1.5">
                            @foreach($tiposResposta as $tr)
                            <label class="flex items-center gap-3 p-2 rounded-lg border cursor-pointer transition-all
                                {{ in_array($tr->id, $vinculados) ? 'border-blue-400 bg-blue-50' : 'border-gray-200 bg-white hover:border-blue-300' }}">
                                <input type="checkbox" name="tipos_resposta[]" value="{{ $tr->id }}"
                                       {{ in_array($tr->id, $vinculados) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $tr->nome }}</p>
                                    @if($tr->descricao)
                                    <p class="text-xs text-gray-500">{{ $tr->descricao }}</p>
                                    @endif
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Opção: exigir resposta individual por item --}}
                <div class="border border-amber-200 rounded-lg p-4 bg-amber-50">
                    <div class="flex items-center mb-3">
                        <svg class="w-5 h-5 text-amber-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 4l2 2 4-4"/>
                        </svg>
                        <label class="text-sm font-semibold text-gray-900">Atendimento por Itens</label>
                    </div>
                    <label class="flex items-start cursor-pointer">
                        <input type="checkbox"
                               name="exige_itens_atendimento"
                               id="exige_itens_atendimento"
                               value="1"
                               {{ old('exige_itens_atendimento', $tipoDocumento->exige_itens_atendimento) ? 'checked' : '' }}
                               onchange="if (this.checked) { const resposta = document.getElementById('permite_resposta'); resposta.checked = true; resposta.dispatchEvent(new Event('change', { bubbles: true })); }"
                               class="mt-0.5 w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900">Exigir upload separado para cada item do documento</span>
                            <p class="text-xs text-gray-600 mt-1">
                                Na criação do documento, o técnico informará cada providência e seu embasamento legal. A empresa deverá anexar uma resposta individual para cada item.
                            </p>
                            <p class="text-xs text-amber-700 mt-2 font-medium">Ao marcar esta opção, a resposta do estabelecimento também será habilitada.</p>
                        </div>
                    </label>
                </div>

                {{-- Subcategorias do Tipo --}}
                @php $subcategorias = $tipoDocumento->subcategorias; @endphp
                @include('configuracoes.tipos-documento.partials.subcategorias')
            </div>
        </div>
        {{-- Botões --}}
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.configuracoes.tipos-documento.index') }}" 
               class="px-6 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Cancelar
            </a>
            <button type="submit" 
                    class="px-6 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                Salvar Alterações
            </button>
        </div>
    </form>
</div>

<script>
function togglePrazoPadrao() {
    const temPrazo = document.getElementById('tem_prazo');
    const prazoPadraoContainer = document.getElementById('prazo_padrao_container');
    
    if (temPrazo.checked) {
        prazoPadraoContainer.style.display = 'block';
    } else {
        prazoPadraoContainer.style.display = 'none';
        document.getElementById('prazo_padrao_dias').value = '';
    }
}
</script>
@endsection
