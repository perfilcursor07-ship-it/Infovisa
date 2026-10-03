@extends('layouts.auth')

@section('title', 'Login - InfoVISA')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-slate-50 font-sans antialiased p-4 sm:p-8">
    {{-- Formulário --}}
    <div class="w-full max-w-md">
        {{-- Card de Login --}}
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80 p-7 sm:p-9">
            {{-- Logo e Cabeçalho --}}
            <div class="mb-7 text-center">
                <img src="{{ asset('img/logo.png') }}" alt="InfoVISA" class="h-11 w-auto mx-auto mb-6">
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight mb-1">Bem-vindo de volta</h1>
                <p class="text-slate-500 text-sm">Entre com suas credenciais para acessar</p>
            </div>

            {{-- Mensagens de Erro --}}
            @if ($errors->any())
                <div class="mb-5 bg-red-50 border border-red-200/80 rounded-xl p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-red-400 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <h3 class="text-sm font-medium text-red-800">Erro ao fazer login</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Mensagem de Sucesso --}}
            @if (session('success'))
                <div class="mb-5 bg-emerald-50 border border-emerald-200/80 rounded-xl p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-green-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            {{-- Formulário --}}
            <form method="POST" action="{{ route('login.submit') }}">
                @csrf

                {{-- CPF --}}
                <div class="mb-4">
                    <label for="cpf" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                        CPF
                    </label>
                    <input 
                        type="text" 
                        id="cpf" 
                        name="cpf" 
                        value="{{ old('cpf') }}"
                        required
                        autofocus
                        maxlength="14"
                        class="w-full px-4 py-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-4 focus:ring-blue-500/15 focus:border-blue-500 transition-all duration-200 @error('cpf') border-red-300 focus:ring-red-500 @enderror"
                        placeholder="000.000.000-00"
                        oninput="formatCPF(this)"
                    >
                    @error('cpf')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Senha --}}
                <div class="mb-5">
                    <label for="password" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                        Senha
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full px-4 py-3 pr-12 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-4 focus:ring-blue-500/15 focus:border-blue-500 transition-all duration-200 @error('password') border-red-300 focus:ring-red-500 @enderror"
                            placeholder="••••••••"
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword()"
                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-slate-600 transition-colors"
                            title="Mostrar/ocultar senha"
                        >
                            <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eyeOffIcon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Lembrar-me e Esqueci a senha --}}
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="remember" 
                            id="remember"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded transition-colors"
                        >
                        <span class="ml-2 text-sm text-slate-700">Lembrar-me</span>
                    </label>

                    <a href="{{ route('recuperar-senha.form') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors">
                        Esqueci minha senha
                    </a>
                </div>

                {{-- Botão de Entrar --}}
                <button 
                    type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg shadow-blue-600/25 hover:shadow-xl hover:shadow-blue-600/30 flex items-center justify-center space-x-2 active:scale-[0.98]"
                >
                    <span>Entrar no Sistema</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </button>
            </form>

            {{-- Link para cadastro --}}
            <div class="mt-7 text-center border-t border-slate-100 pt-6">
                <p class="text-sm text-slate-600">
                    Não tem uma conta? 
                    <a href="{{ route('registro') }}" class="text-blue-600 hover:text-blue-700 font-bold transition-colors">
                        Cadastre-se aqui
                    </a>
                </p>
            </div>
        </div>

        {{-- Link para home --}}
        <div class="mt-6 text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center text-sm text-slate-500 hover:text-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Voltar para página inicial
            </a>
        </div>
    </div>
</div>

{{-- Modal de Recuperação de Senha --}}
<div id="modalRecuperacaoSenha" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all">
        <div class="text-center mb-6">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-2">Recuperação de Senha</h3>
            <p class="text-sm text-slate-600">Entre em contato conosco para recuperar sua senha</p>
        </div>

        <div class="space-y-4 mb-6">
            {{-- Telefones --}}
            <div class="bg-slate-50 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-slate-900 mb-1">Telefones</p>
                        <p class="text-sm text-slate-700">(63) 3027-4486</p>
                        <p class="text-sm text-slate-700">(63) 3027-4484</p>
                    </div>
                </div>
            </div>

            {{-- Email --}}
            <div class="bg-slate-50 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-slate-900 mb-1">Email</p>
                        <a href="mailto:licenciamento.visato@gmail.com" class="text-sm text-blue-600 hover:text-blue-700 hover:underline">
                            licenciamento.visato@gmail.com
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <button 
            type="button"
            onclick="fecharModalRecuperacao()"
            class="w-full px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors"
        >
            Fechar
        </button>
    </div>
</div>

{{-- Modal de Verificação de CPF --}}
<div id="modalVerificacaoCPF" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all">
        <div class="text-center mb-6">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-2">Criar Nova Conta</h3>
            <p class="text-sm text-slate-600">Digite seu CPF para iniciar o cadastro como usuário externo</p>
        </div>

        <div class="mb-6">
            <label for="cpf_verificacao" class="block text-sm font-medium text-slate-700 mb-2">
                CPF
            </label>
            <input 
                type="text" 
                id="cpf_verificacao" 
                maxlength="14"
                class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="000.000.000-00"
                oninput="formatCPF(this)"
            >
            <p id="mensagemVerificacao" class="mt-2 text-sm hidden"></p>
        </div>

        <div class="flex gap-3">
            <button 
                type="button"
                onclick="fecharModal()"
                class="flex-1 px-4 py-2.5 border-2 border-slate-300 text-slate-700 rounded-lg font-medium hover:bg-slate-50 transition-colors"
            >
                Cancelar
            </button>
            <button 
                type="button"
                onclick="verificarCPF()"
                class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors"
            >
                Verificar
            </button>
        </div>
    </div>
</div>

<script>
function abrirModalRecuperacao(event) {
    event.preventDefault();
    document.getElementById('modalRecuperacaoSenha').classList.remove('hidden');
}

function fecharModalRecuperacao() {
    document.getElementById('modalRecuperacaoSenha').classList.add('hidden');
}

function togglePassword() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    const eyeOffIcon = document.getElementById('eyeOffIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.add('hidden');
        eyeOffIcon.classList.remove('hidden');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('hidden');
        eyeOffIcon.classList.add('hidden');
    }
}

function formatCPF(input) {
    // Remove tudo que não é dígito
    let value = input.value.replace(/\D/g, '');
    
    // Aplica a máscara do CPF
    if (value.length <= 11) {
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }
    
    input.value = value;
}

function verificarCPFCadastro() {
    document.getElementById('modalVerificacaoCPF').classList.remove('hidden');
    document.getElementById('cpf_verificacao').focus();
}

function fecharModal() {
    document.getElementById('modalVerificacaoCPF').classList.add('hidden');
    document.getElementById('cpf_verificacao').value = '';
    document.getElementById('mensagemVerificacao').classList.add('hidden');
}

function verificarCPF() {
    const cpfInput = document.getElementById('cpf_verificacao');
    const cpf = cpfInput.value.replace(/\D/g, '');
    const mensagem = document.getElementById('mensagemVerificacao');
    
    if (!cpf || cpf.length !== 11) {
        mensagem.textContent = 'Por favor, digite um CPF válido.';
        mensagem.className = 'mt-2 text-sm text-red-600';
        mensagem.classList.remove('hidden');
        return;
    }
    
    // Validação básica de CPF
    if (!validarCPF(cpf)) {
        mensagem.textContent = 'CPF inválido. Por favor, verifique o número digitado.';
        mensagem.className = 'mt-2 text-sm text-red-600';
        mensagem.classList.remove('hidden');
        return;
    }
    
    // CPF válido - redirecionar para cadastro
    mensagem.textContent = '✓ CPF válido! Redirecionando para o cadastro...';
    mensagem.className = 'mt-2 text-sm text-green-600 font-semibold';
    mensagem.classList.remove('hidden');
    
    setTimeout(() => {
        window.location.href = '{{ route("registro") }}?cpf=' + cpfInput.value;
    }, 1000);
}

function validarCPF(cpf) {
    // Remove caracteres não numéricos
    cpf = cpf.replace(/\D/g, '');
    
    // Verifica se tem 11 dígitos
    if (cpf.length !== 11) return false;
    
    // Verifica se todos os dígitos são iguais
    if (/^(\d)\1+$/.test(cpf)) return false;
    
    // Validação do primeiro dígito verificador
    let soma = 0;
    for (let i = 0; i < 9; i++) {
        soma += parseInt(cpf.charAt(i)) * (10 - i);
    }
    let resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    if (resto !== parseInt(cpf.charAt(9))) return false;
    
    // Validação do segundo dígito verificador
    soma = 0;
    for (let i = 0; i < 10; i++) {
        soma += parseInt(cpf.charAt(i)) * (11 - i);
    }
    resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    if (resto !== parseInt(cpf.charAt(10))) return false;
    
    return true;
}

// Permitir verificar com Enter
document.addEventListener('DOMContentLoaded', function() {
    const cpfInput = document.getElementById('cpf_verificacao');
    if (cpfInput) {
        cpfInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                verificarCPF();
            }
        });
    }
});
</script>
@endsection
