@extends('layouts.admin')

@section('title', 'Detalhes do Usuário Externo')

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Detalhes do Usuário Externo</h1>
            <p class="text-sm text-gray-600 mt-1">Informações completas do usuário</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.usuarios-externos.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar
            </a>
            <a href="{{ route('admin.usuarios-externos.edit', $usuarioExterno) }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Informações Principais --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Dados Pessoais --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Dados Pessoais
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Nome Completo</label>
                        <p class="text-gray-900 font-medium">{{ $usuarioExterno->nome }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">CPF</label>
                        <p class="text-gray-900 font-medium">{{ $usuarioExterno->cpf_formatado }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                        <p class="text-gray-900 font-medium">{{ $usuarioExterno->email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Telefone</label>
                        <p class="text-gray-900 font-medium">{{ $usuarioExterno->telefone_formatado ?? '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Vínculo</label>
                        @if($usuarioExterno->vinculo_estabelecimento)
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                {{ $usuarioExterno->vinculo_estabelecimento->label() }}
                            </span>
                        @else
                            <span class="text-gray-500 text-sm">-</span>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
                        @if($usuarioExterno->ativo)
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Ativo</span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Inativo</span>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Módulos de acesso</label>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach(\App\Models\UsuarioExterno::MODULOS as $chave => $rotulo)
                                @if($usuarioExterno->temModulo($chave))
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $chave === 'receituario' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}"
                                      @if(!in_array($chave, $usuarioExterno->modulos ?? array_keys(\App\Models\UsuarioExterno::MODULOS), true)) title="Liberado por vínculo" @endif>
                                    {{ $rotulo }}
                                </span>
                                @endif
                            @endforeach
                            @if(!$usuarioExterno->temModulo('processos') && !$usuarioExterno->temModulo('receituario'))
                                <span class="text-gray-500 text-sm">Nenhum</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Aceite de Termos --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Aceite de Termos
                </h2>
                @if($usuarioExterno->aceitouTermos())
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-green-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-green-900">Termos aceitos</p>
                                <p class="text-xs text-green-700 mt-1">
                                    Data: {{ $usuarioExterno->aceite_termos_em->format('d/m/Y H:i:s') }}
                                </p>
                                <p class="text-xs text-green-700">
                                    IP: {{ $usuarioExterno->ip_aceite_termos }}
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-yellow-900">Aguardando aceite dos termos</p>
                                <p class="text-xs text-yellow-700 mt-1">O usuário ainda não aceitou os termos de uso</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Estabelecimentos Vinculados --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l9-4 9 4m-9-4v18m-9-7l9 4 9-4M3 7v10m18-10v10"/>
                    </svg>
                    Estabelecimentos Vinculados ({{ $estabelecimentosRelacionados->count() }})
                </h2>

                @if($estabelecimentosRelacionados->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($estabelecimentosRelacionados as $estabelecimento)
                            <div class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate">
                                            {{ $estabelecimento->nome_fantasia ?: $estabelecimento->nome_razao_social }}
                                        </p>
                                        <p class="text-xs text-gray-600 mt-1">
                                            {{ $estabelecimento->documento_formatado ?: '-' }}
                                            @if($estabelecimento->cidade || $estabelecimento->estado)
                                                • {{ trim(($estabelecimento->cidade ?? '') . ' - ' . ($estabelecimento->estado ?? ''), ' -') }}
                                            @endif
                                        </p>
                                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                            @if((int) $estabelecimento->usuario_externo_id === (int) $usuarioExterno->id)
                                                <span class="px-2 py-1 rounded-full bg-amber-100 text-amber-800 font-medium">
                                                    Criador do cadastro
                                                </span>
                                            @endif
                                            @if(!empty($estabelecimento->pivot->tipo_vinculo))
                                                <span class="px-2 py-1 rounded-full bg-blue-100 text-blue-800 font-medium">
                                                    {{ ucfirst($estabelecimento->pivot->tipo_vinculo) }}
                                                </span>
                                            @endif
                                            @if(!empty($estabelecimento->pivot->nivel_acesso))
                                                <span class="px-2 py-1 rounded-full bg-indigo-100 text-indigo-800 font-medium">
                                                    {{ ucfirst($estabelecimento->pivot->nivel_acesso) }}
                                                </span>
                                            @endif
                                            @if(!empty($estabelecimento->pivot->created_at))
                                                <span class="text-gray-500">
                                                    Vinculado em {{ optional($estabelecimento->pivot->created_at)->format('d/m/Y H:i') ?? '-' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-2 shrink-0">
                                        <a href="{{ route('admin.estabelecimentos.show', $estabelecimento->id) }}"
                                           class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-md hover:bg-blue-100 transition-colors">
                                            Ver estabelecimento
                                        </a>
                                        <a href="{{ route('admin.estabelecimentos.usuarios.index', $estabelecimento->id) }}"
                                           class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 rounded-md hover:bg-indigo-100 transition-colors">
                                            Gerenciar usuários
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="text-sm text-gray-600">Este usuário externo ainda não possui estabelecimentos vinculados.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Informações do Sistema --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Informações do Sistema</h2>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Cadastrado em</label>
                        <p class="text-sm text-gray-900">{{ $usuarioExterno->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Última atualização</label>
                        <p class="text-sm text-gray-900">{{ $usuarioExterno->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Email verificado</label>
                        @if($usuarioExterno->email_verified_at)
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                Verificado em {{ $usuarioExterno->email_verified_at->format('d/m/Y') }}
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                Não verificado
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Ações --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Ações</h2>
                <div class="space-y-2">
                    <a href="{{ route('admin.usuarios-externos.edit', $usuarioExterno) }}" 
                       class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar Usuário
                    </a>
                    @if(auth('interno')->user()->isAdmin())
                    <form action="{{ route('admin.usuarios-externos.destroy', $usuarioExterno) }}" method="POST" 
                          onsubmit="return confirm('Tem certeza que deseja excluir este usuário?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Excluir Usuário
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
