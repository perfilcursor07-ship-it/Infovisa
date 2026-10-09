@extends('layouts.admin')

@section('title', 'Editar Usuário Externo')

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.usuarios-externos.show', $usuarioExterno) }}" 
               class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Editar Usuário Externo</h1>
                <p class="text-sm text-gray-600 mt-1">Atualize as informações do usuário</p>
            </div>
        </div>
    </div>

    {{-- Formulário --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('admin.usuarios-externos.update', $usuarioExterno) }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Informações Pessoais --}}
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">
                    Informações Pessoais
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Nome --}}
                    <div class="md:col-span-2">
                        <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">
                            Nome Completo <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="nome" 
                               name="nome" 
                               value="{{ old('nome', $usuarioExterno->nome) }}"
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('nome') border-red-500 @enderror">
                        @error('nome')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- CPF --}}
                    <div>
                        <label for="cpf" class="block text-sm font-medium text-gray-700 mb-1">
                            CPF <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="cpf" 
                               name="cpf" 
                               value="{{ old('cpf', $usuarioExterno->cpf) }}"
                               placeholder="000.000.000-00"
                               maxlength="14"
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('cpf') border-red-500 @enderror">
                        @error('cpf')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="{{ old('email', $usuarioExterno->email) }}"
                               placeholder="email@exemplo.com"
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Telefone --}}
                    <div>
                        <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">
                            Telefone
                        </label>
                        <input type="text" 
                               id="telefone" 
                               name="telefone" 
                               value="{{ old('telefone', $usuarioExterno->telefone) }}"
                               placeholder="(00) 00000-0000"
                               maxlength="20"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('telefone') border-red-500 @enderror">
                        @error('telefone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Vínculo com Estabelecimento --}}
                    <div>
                        <label for="vinculo_estabelecimento" class="block text-sm font-medium text-gray-700 mb-1">
                            Vínculo com Estabelecimento
                        </label>
                        <select id="vinculo_estabelecimento" 
                                name="vinculo_estabelecimento" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('vinculo_estabelecimento') border-red-500 @enderror">
                            <option value="">Não informado</option>
                            @foreach(\App\Enums\VinculoEstabelecimento::cases() as $vinculo)
                                <option value="{{ $vinculo->value }}" 
                                        {{ old('vinculo_estabelecimento', $usuarioExterno->vinculo_estabelecimento?->value) == $vinculo->value ? 'selected' : '' }}>
                                    {{ $vinculo->label() }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">O vínculo principal do usuário. Os vínculos específicos por estabelecimento são gerenciados no cadastro de cada estabelecimento.</p>
                        @error('vinculo_estabelecimento')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Informações de Aceite --}}
            @if($usuarioExterno->aceitouTermos())
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">
                    Aceite de Termos
                </h2>
                
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
            </div>
            @endif

            {{-- Alterar Senha --}}
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">
                    Alterar Senha
                </h2>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-blue-800">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Deixe os campos em branco se não desejar alterar a senha
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Nova Senha --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Nova Senha
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               minlength="8"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Mínimo de 8 caracteres</p>
                    </div>

                    {{-- Confirmar Nova Senha --}}
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                            Confirmar Nova Senha
                        </label>
                        <input type="password" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               minlength="8"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            {{-- Módulos de acesso --}}
            @php
                $modulosMarcados = old('modulos', $usuarioExterno->modulos ?? array_keys(\App\Models\UsuarioExterno::MODULOS));
                $descricaoModulos = [
                    'processos' => 'Estabelecimentos, processos, documentos e alertas.',
                    'receituario' => 'Cadastro de prescritor e requisições de receituário.',
                ];
            @endphp
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-1 pb-2 border-b border-gray-200">
                    Módulos de acesso
                </h2>
                <p class="mb-3 text-xs text-gray-500">O que o usuário vê na área da empresa. Quem está vinculado a um estabelecimento ou a um cadastro de receituário continua acessando o módulo correspondente.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach(\App\Models\UsuarioExterno::MODULOS as $chave => $rotulo)
                    <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50/50 transition">
                        <input type="checkbox" name="modulos[]" value="{{ $chave }}"
                               {{ in_array($chave, (array) $modulosMarcados, true) ? 'checked' : '' }}
                               class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span>
                            <span class="block text-sm font-medium text-gray-900">{{ $rotulo }}</span>
                            <span class="block text-xs text-gray-500">{{ $descricaoModulos[$chave] ?? '' }}</span>
                        </span>
                    </label>
                    @endforeach
                </div>
                @error('modulos.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Status --}}
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">
                    Status
                </h2>
                
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="ativo" 
                           name="ativo" 
                           value="1"
                           {{ old('ativo', $usuarioExterno->ativo) ? 'checked' : '' }}
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="ativo" class="ml-2 text-sm font-medium text-gray-700">
                        Usuário ativo
                    </label>
                </div>
                <p class="mt-1 text-xs text-gray-500">Usuários inativos não poderão acessar o sistema</p>
            </div>

            {{-- Botões --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('admin.usuarios-externos.show', $usuarioExterno) }}" 
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Formatar CPF ao carregar
    window.addEventListener('DOMContentLoaded', function() {
        const cpfInput = document.getElementById('cpf');
        let value = cpfInput.value.replace(/\D/g, '');
        if (value.length === 11) {
            value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            cpfInput.value = value;
        }
    });

    // Máscara de CPF
    document.getElementById('cpf').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length <= 11) {
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        }
    });

    // Máscara de Telefone
    document.getElementById('telefone').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length <= 11) {
            if (value.length <= 10) {
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            } else {
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
            }
            e.target.value = value;
        }
    });
</script>
@endpush
@endsection
