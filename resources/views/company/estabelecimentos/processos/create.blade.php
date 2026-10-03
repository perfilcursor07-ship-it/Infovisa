@extends('layouts.company')

@section('title', 'Abrir Processo')
@section('page-title', 'Abrir Processo')

@section('content')
<div class="max-w-8xl mx-auto">
    {{-- Header --}}
    <div class="mb-6">
        <a href="{{ route('company.estabelecimentos.processos.index', $estabelecimento->id) }}" 
           class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-3">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Voltar
        </a>
        <h1 class="text-xl font-bold text-gray-900">Abrir Novo Processo</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $estabelecimento->nome_fantasia ?: $estabelecimento->razao_social }}</p>
    </div>

    @php
        $avisosAbertura = $tiposProcesso
            ->filter(fn ($tipo) => $tipo->exibir_aviso_abertura_empresa && $tipo->aviso_abertura_mensagem)
            ->mapWithKeys(fn ($tipo) => [
                $tipo->id => [
                    'titulo' => $tipo->aviso_abertura_titulo ?: 'Atenção antes de abrir este processo',
                    'mensagem' => $tipo->aviso_abertura_mensagem,
                    'confirmacao' => $tipo->aviso_abertura_confirmacao ?: 'Li e confirmo que este é o processo correto para minha solicitação.',
                ],
            ])
            ->all();
    @endphp

    {{-- Formulário --}}
    <form action="{{ route('company.estabelecimentos.processos.store', $estabelecimento->id) }}" method="POST"
          x-data="{
              tipoSelecionado: @js((string) old('tipo_processo_id', '')),
              avisos: @js($avisosAbertura),
              aceitouAviso: {{ old('confirmar_aviso_abertura') ? 'true' : 'false' }},
              modalAvisoAberto: false,
              init() {
                  if (this.avisoSelecionado && !this.aceitouAviso) {
                      this.modalAvisoAberto = true;
                  }
              },
              get avisoSelecionado() {
                  return this.avisos[this.tipoSelecionado] || null;
              },
              selecionarTipo() {
                  this.aceitouAviso = false;
                  this.modalAvisoAberto = !!this.avisoSelecionado;
              },
              cancelarAviso() {
                  this.modalAvisoAberto = false;
                  this.tipoSelecionado = '';
                  this.aceitouAviso = false;
              },
              confirmarAviso() {
                  this.aceitouAviso = true;
                  this.modalAvisoAberto = false;
              }
          }">
        @csrf
        <input type="hidden" name="confirmar_aviso_abertura" :value="aceitouAviso ? '1' : '0'">

        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            {{-- Erros --}}
            @if($errors->any())
            <div class="p-4 border-b border-gray-200 bg-red-50">
                <ul class="text-sm text-red-700 space-y-1">
                    @foreach($errors->all() as $error)
                    <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="p-6">
                {{-- Aviso de equipamentos de radiação obrigatórios --}}
                @if(isset($tiposBloqueadosPorEquipamentos) && count($tiposBloqueadosPorEquipamentos) > 0)
                <div class="mb-6 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-orange-800">Equipamentos de Imagem Obrigatórios</h4>
                            <p class="mt-1 text-sm text-orange-700">
                                Seu estabelecimento possui atividades que exigem o cadastro de equipamentos de imagem. 
                                Para abrir alguns tipos de processo, você precisa primeiro cadastrar seus equipamentos.
                            </p>
                            <a href="{{ route('company.estabelecimentos.equipamentos-radiacao.index', $estabelecimento->id) }}" 
                               class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-orange-700 bg-orange-100 rounded-lg hover:bg-orange-200 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Cadastrar Equipamentos de Imagem
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Aviso de responsável técnico obrigatório --}}
                @if(isset($precisaCadastrarResponsavelTecnico) && $precisaCadastrarResponsavelTecnico)
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-red-800">Responsável Técnico Obrigatório</h4>
                            <p class="mt-1 text-sm text-red-700">
                                Este estabelecimento possui atividade que exige cadastro de responsável técnico.
                                Cadastre pelo menos um responsável técnico para abrir ou continuar processos.
                            </p>
                            <a href="{{ route('company.estabelecimentos.responsaveis.index', $estabelecimento->id) }}"
                               class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-red-700 bg-red-100 rounded-lg hover:bg-red-200 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Cadastrar Responsável Técnico
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Licenciamento indisponível: competência municipal em município que não usa o InfoVISA --}}
                @if(!empty($avisoLicenciamentoIndisponivel))
                <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-amber-800">Licenciamento Sanitário não disponível no InfoVISA</h4>
                            <p class="mt-1 text-sm text-amber-700">{{ $avisoLicenciamentoIndisponivel }}</p>
                        </div>
                    </div>
                </div>
                @endif

                @if($tiposProcesso->count() > 0)
                    <label class="block text-sm font-medium text-gray-700 mb-4">Selecione o tipo de processo</label>
                    
                    <div class="space-y-2">
                        @foreach($tiposProcesso as $tipo)
                        @php
                            $bloqueadoPorEquipamento = isset($tiposBloqueadosPorEquipamentos) && in_array($tipo->codigo, $tiposBloqueadosPorEquipamentos);
                            $bloqueadoPorResponsavelTecnico = isset($precisaCadastrarResponsavelTecnico) && $precisaCadastrarResponsavelTecnico;
                            $tipoBloqueado = $bloqueadoPorEquipamento || $bloqueadoPorResponsavelTecnico;
                        @endphp
                        <label class="flex items-center gap-3 p-4 border border-gray-200 rounded-lg transition-all {{ $tipoBloqueado ? 'opacity-60 cursor-not-allowed bg-gray-50' : 'cursor-pointer hover:border-blue-300 hover:bg-blue-50/50 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50' }}">
                            <input type="radio" name="tipo_processo_id" value="{{ $tipo->id }}" 
                                   x-model="tipoSelecionado"
                                   @change="selecionarTipo()"
                                   {{ old('tipo_processo_id') == $tipo->id ? 'checked' : '' }}
                                   {{ $tipoBloqueado ? 'disabled' : '' }}
                                   class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500 {{ $tipoBloqueado ? 'cursor-not-allowed' : '' }}" {{ !$tipoBloqueado ? 'required' : '' }}>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-medium {{ $tipoBloqueado ? 'text-gray-500' : 'text-gray-900' }}">{{ $tipo->nome }}</span>
                                    @if($tipo->anual)
                                        <span class="px-1.5 py-0.5 text-[10px] font-medium bg-amber-100 text-amber-700 rounded">Anual</span>
                                    @endif
                                    @if($tipo->unico_por_estabelecimento)
                                        <span class="px-1.5 py-0.5 text-[10px] font-medium bg-purple-100 text-purple-700 rounded">Único</span>
                                    @endif
                                    @if(isset($documentosObrigatorios[$tipo->id]) && count($documentosObrigatorios[$tipo->id]) > 0)
                                        <span class="px-1.5 py-0.5 text-[10px] font-medium bg-blue-100 text-blue-600 rounded">{{ count($documentosObrigatorios[$tipo->id]) }} doc(s)</span>
                                    @endif
                                    @if($bloqueadoPorEquipamento)
                                        <span class="px-1.5 py-0.5 text-[10px] font-medium bg-orange-100 text-orange-700 rounded flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                            </svg>
                                            Requer equipamentos de imagem
                                        </span>
                                    @endif
                                    @if($bloqueadoPorResponsavelTecnico)
                                        <span class="px-1.5 py-0.5 text-[10px] font-medium bg-red-100 text-red-700 rounded flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            Requer responsável técnico
                                        </span>
                                    @endif
                                </div>
                                @if($tipo->descricao)
                                    <p class="text-xs text-gray-500 mt-1">{{ $tipo->descricao }}</p>
                                @endif
                                @if($bloqueadoPorEquipamento)
                                    <p class="text-xs text-orange-600 mt-1">Cadastre os equipamentos de imagem antes de abrir este tipo de processo.</p>
                                @endif
                                @if($bloqueadoPorResponsavelTecnico)
                                    <p class="text-xs text-red-600 mt-1">Cadastre um responsável técnico antes de abrir este tipo de processo.</p>
                                @endif
                            </div>
                        </label>
                        @endforeach
                    </div>

                    {{-- Observação --}}
                    <div class="mt-6">
                        <label for="observacao" class="block text-sm font-medium text-gray-700 mb-2">Observação <span class="text-gray-400 font-normal">(opcional)</span></label>
                        <textarea name="observacao" id="observacao" rows="2" 
                                  placeholder="Informações adicionais..."
                                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('observacao') }}</textarea>
                    </div>

                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="mt-3 text-sm text-gray-500">Nenhum tipo de processo disponível no momento.</p>
                    </div>
                @endif
            </div>

            <div x-show="modalAvisoAberto"
                 x-cloak
                 x-transition.opacity
                 class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/55 px-4 py-6"
                 @keydown.escape.window="cancelarAviso()">
                <div class="w-full max-w-lg overflow-hidden rounded-xl bg-white shadow-2xl"
                     x-show="modalAvisoAberto"
                     x-transition
                     @click.outside="cancelarAviso()">
                    <div class="border-b border-amber-100 bg-amber-50 px-5 py-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Atenção</p>
                                <h3 class="mt-0.5 text-base font-bold text-gray-900" x-text="avisoSelecionado?.titulo"></h3>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-5">
                        <p class="whitespace-pre-line text-sm leading-relaxed text-gray-700" x-text="avisoSelecionado?.mensagem"></p>

                        <label class="mt-5 flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm font-semibold text-gray-900">
                            <input type="checkbox" x-model="aceitouAviso" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span x-text="avisoSelecionado?.confirmacao"></span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4">
                        <button type="button"
                                @click="cancelarAviso()"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="button"
                                @click="confirmarAviso()"
                                :disabled="!aceitouAviso"
                                :class="!aceitouAviso ? 'cursor-not-allowed opacity-60' : ''"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Confirmar e continuar
                        </button>
                    </div>
                </div>
            </div>

            {{-- Botões --}}
            @if($tiposProcesso->count() > 0)
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('company.estabelecimentos.processos.index', $estabelecimento->id) }}" 
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancelar
                </a>
                @if(isset($precisaCadastrarResponsavelTecnico) && $precisaCadastrarResponsavelTecnico)
                    <a href="{{ route('company.estabelecimentos.responsaveis.index', $estabelecimento->id) }}"
                       class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                        Cadastrar Responsável Técnico
                    </a>
                @else
                    <button type="submit" :disabled="avisoSelecionado && !aceitouAviso" :class="avisoSelecionado && !aceitouAviso ? 'opacity-60 cursor-not-allowed' : ''" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        Abrir Processo
                    </button>
                @endif
            </div>
            @endif
        </div>
    </form>

    {{-- Tipos de processo bloqueados --}}
    @if(isset($tiposBloqueados) && $tiposBloqueados->count() > 0)
    <div class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Processos já abertos</h4>
        <div class="space-y-2">
            @foreach($tiposBloqueados as $tipo)
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span>{{ $tipo->nome }}</span>
                <span class="text-xs text-gray-400">
                    @if($tipo->unico_por_estabelecimento)
                        (já aberto - único por estabelecimento)
                    @elseif($tipo->anual)
                        (já aberto em {{ date('Y') }})
                    @endif
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    {{-- Info sobre documentos --}}
    @if($tiposProcesso->count() > 0)
    @endif
</div>
@endsection
