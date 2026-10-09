@extends('layouts.auth')

@section('title', 'Cadastro de Usuário Externo')

@section('content')
<div x-data="registroForm()" class="min-h-screen bg-slate-50 font-sans antialiased flex justify-center p-4 sm:p-8">
    <div class="w-full max-w-xl">
        {{-- Passo a passo --}}
        <ol class="mb-5 flex items-start">
            <template x-for="(etapa, i) in etapas" :key="i">
                <li class="flex-1 flex flex-col items-center text-center relative">
                    {{-- linha de ligação --}}
                    <div x-show="i > 0" class="absolute top-4 right-1/2 w-full h-0.5 -z-0 transition"
                         :class="etapaAtual > i ? 'bg-blue-600' : 'bg-slate-200'"></div>
                    <span class="relative z-10 w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold ring-4 ring-slate-50 transition"
                          :class="etapaAtual > i + 1 ? 'bg-emerald-500 text-white' : (etapaAtual === i + 1 ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'bg-white text-slate-400 border border-slate-200')">
                        <svg x-show="etapaAtual > i + 1" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        <span x-show="etapaAtual <= i + 1" x-text="i + 1"></span>
                    </span>
                    <span class="mt-2 text-xs font-medium transition"
                          :class="etapaAtual === i + 1 ? 'text-blue-700' : (etapaAtual > i + 1 ? 'text-slate-700' : 'text-slate-400')"
                          x-text="etapa"></span>
                </li>
            </template>
        </ol>

        <div class="bg-white rounded-3xl shadow-xl shadow-slate-900/5 ring-1 ring-slate-200/80 p-6 sm:p-9">
            {{-- Cabeçalho --}}
            <div class="mb-6">
                <img src="{{ asset('img/logo.png') }}" alt="InfoVISA" class="mx-auto mb-4 block h-8 w-auto">
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight mb-1">Criar conta</h1>
                <p class="text-slate-500 text-sm">Cadastro de usuário externo (empresas, responsáveis, contadores e profissionais prescritores)</p>
            </div>

            {{-- Alertas --}}
            @if (session('error'))
                <div class="mb-5 bg-red-50 border border-red-200/80 rounded-xl p-4 flex gap-3">
                    <svg class="h-5 w-5 text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <p class="text-sm text-red-800">{{ session('error') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 bg-red-50 border border-red-200/80 rounded-xl p-4 flex gap-3">
                    <svg class="h-5 w-5 text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <div>
                        <h3 class="text-sm font-medium text-red-800">Revise os dados informados</h3>
                        <ul class="mt-1.5 text-sm text-red-700 list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- ETAPA 1: CPF --}}
            <section x-show="etapaAtual === 1" x-cloak x-transition>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="cpf" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">CPF <span class="text-red-500">*</span></label>
                    <button type="button" x-show="cpfStatus === 'valido'" x-cloak @click="alterarCpf()" class="text-xs font-medium text-blue-600 hover:text-blue-700">Alterar CPF</button>
                </div>
                <div class="relative">
                    <input
                        type="text"
                        id="cpf"
                        x-ref="cpf"
                        x-model="cpf"
                        @input="onCpfInput()"
                        inputmode="numeric"
                        autocomplete="off"
                        maxlength="14"
                        autofocus
                        class="w-full px-4 py-3 pr-11 text-sm tracking-wide bg-slate-50 border rounded-xl focus:bg-white focus:ring-4 transition-all duration-200 disabled:cursor-not-allowed"
                        :class="{
                            'border-emerald-300 bg-emerald-50/60 text-slate-700 focus:ring-emerald-500/15 focus:border-emerald-500': cpfStatus === 'valido',
                            'border-red-300 focus:ring-red-500/15 focus:border-red-500': cpfStatus === 'invalido' || cpfStatus === 'cadastrado',
                            'border-slate-200 focus:ring-blue-500/15 focus:border-blue-500': cpfStatus === 'aguardando' || cpfStatus === 'consultando'
                        }"
                        placeholder="000.000.000-00"
                        :readonly="cpfStatus === 'valido' || consultando"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                        <svg x-show="cpfStatus === 'consultando'" x-cloak class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <svg x-show="cpfStatus === 'valido'" x-cloak class="h-5 w-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <svg x-show="cpfStatus === 'invalido' || cpfStatus === 'cadastrado'" x-cloak class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                </div>
                <p x-show="mensagemCpf && cpfStatus !== 'cadastrado'" x-cloak x-text="mensagemCpf" class="mt-1.5 text-xs"
                   :class="{
                       'text-emerald-600': cpfStatus === 'valido',
                       'text-red-600': cpfStatus === 'invalido',
                       'text-blue-600': cpfStatus === 'consultando'
                   }"></p>
                <p x-show="cpfStatus === 'aguardando' && !mensagemCpf" class="mt-1.5 text-xs text-slate-500">Digite seu CPF para verificarmos se ele já possui cadastro.</p>

                {{-- CPF já cadastrado --}}
                <div x-show="cpfStatus === 'cadastrado'" x-cloak x-transition class="mt-4 bg-amber-50 border border-amber-200 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex items-start gap-3 flex-1">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        <div>
                            <p class="text-sm font-semibold text-amber-900">CPF já cadastrado</p>
                            <p class="text-xs text-amber-800 mt-0.5">Acesse com sua senha. Se não lembrar, use a recuperação de senha.</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('recuperar-senha.form') }}" class="px-3 py-2 text-xs font-semibold text-amber-900 bg-white border border-amber-200 rounded-lg hover:bg-amber-100 transition">Recuperar senha</a>
                        <a href="{{ route('login') }}" class="px-3 py-2 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">Ir para login</a>
                    </div>
                </div>
            </section>

            {{-- ETAPAS 2 a 4: Uso + Dados + Termos --}}
            <form action="{{ route('registro.submit') }}" method="POST" x-ref="form"
                  x-show="cpfStatus === 'valido'" x-cloak
                  x-transition:enter="transition ease-out duration-300"
                  x-transition:enter-start="opacity-0 -translate-y-2"
                  x-transition:enter-end="opacity-100 translate-y-0"
                  @submit="onSubmit($event)"
                  class="mt-6 pt-6 border-t border-slate-100 space-y-6" novalidate>
                @csrf
                <input type="hidden" name="cpf" :value="cpf">

                <div x-show="etapaAtual > 1" x-cloak class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 text-xs">
                    <span class="text-slate-600">CPF validado: <strong class="tabular-nums text-slate-800" x-text="cpf"></strong></span>
                    <button type="button" @click="alterarCpf()" class="font-semibold text-blue-700 hover:underline">Alterar</button>
                </div>

                {{-- Como vai usar --}}
                <div x-show="etapaAtual === 2" x-cloak x-transition class="space-y-4">
                <fieldset>
                    <legend class="text-sm font-semibold text-slate-900">Como você vai usar o InfoVISA? <span class="text-red-500">*</span></legend>
                    <p class="text-xs text-slate-500 mt-0.5 mb-3">Marque uma ou as duas opções. Se precisar de outra depois, a Vigilância Sanitária pode liberar.</p>
                    <template x-for="m in modulos" :key="m"><input type="hidden" name="modulos[]" :value="m"></template>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <button type="button" x-ref="moduloProcessos" @click="alternarModulo('processos')" :aria-pressed="modulos.includes('processos')"
                                class="relative flex items-center gap-3 text-left rounded-lg border p-3 pr-10 transition focus:outline-none focus:ring-4 focus:ring-blue-500/15"
                                :class="modulos.includes('processos') ? 'border-blue-500 bg-blue-50/60 ring-1 ring-blue-500' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'">
                            <span class="absolute top-2.5 right-2.5 w-4 h-4 rounded border flex items-center justify-center transition"
                                  :class="modulos.includes('processos') ? 'bg-blue-600 border-blue-600 text-white' : 'border-slate-300 bg-white text-transparent'">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="w-8 h-8 rounded-md bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-[13px] font-semibold text-slate-900 leading-tight">Licenciamento e processos</span>
                                <span class="block text-[11px] text-slate-500 mt-0.5 leading-snug">Cadastrar estabelecimentos, abrir processos e enviar documentos à Vigilância Sanitária.</span>
                            </span>
                        </button>

                        <button type="button" @click="alternarModulo('receituario')" :aria-pressed="modulos.includes('receituario')"
                                class="relative flex items-center gap-3 text-left rounded-lg border p-3 pr-10 transition focus:outline-none focus:ring-4 focus:ring-emerald-500/15"
                                :class="modulos.includes('receituario') ? 'border-emerald-500 bg-emerald-50/60 ring-1 ring-emerald-500' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'">
                            <span class="absolute top-2.5 right-2.5 w-4 h-4 rounded border flex items-center justify-center transition"
                                  :class="modulos.includes('receituario') ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-300 bg-white text-transparent'">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="w-8 h-8 rounded-md bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-[13px] font-semibold text-slate-900 leading-tight">Receituário</span>
                                <span class="block text-[11px] text-slate-500 mt-0.5 leading-snug">Cadastro de prescritor (médico, dentista, veterinário…) para requisição de notificações de receita.</span>
                            </span>
                        </button>
                    </div>
                    @error('modulos')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('modulos.*')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </fieldset>

                <p x-show="erroEtapa" x-cloak x-text="erroEtapa" class="text-xs font-medium text-red-600" role="alert"></p>
                <div class="flex justify-end">
                    <button type="button" @click="avancarEtapa()" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/20">
                        Continuar
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
                </div>

                {{-- Dados pessoais --}}
                <div x-show="etapaAtual === 3" x-cloak x-transition class="space-y-6">
                <fieldset class="space-y-4">
                    <legend class="text-sm font-semibold text-slate-900 mb-3">Dados pessoais</legend>

                    <div>
                        <label for="nome" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Nome completo <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" id="nome" name="nome" x-ref="nome" x-model="nome"
                                   @input="nome = nome.toUpperCase(); nomeAutoPreenchido = false" @blur="tocado.nome = true"
                                   autocomplete="name" maxlength="255"
                                   class="w-full px-4 py-3 text-sm uppercase bg-slate-50 border rounded-xl focus:bg-white focus:ring-4 focus:ring-blue-500/15 focus:border-blue-500 transition-all duration-200 @error('nome') border-red-300 @enderror"
                                   :class="termoEmpresaNome ? 'border-amber-400 focus:border-amber-500 focus:ring-amber-500/15' : (nomeAutoPreenchido ? 'pr-28 border-emerald-300' : (tocado.nome && !nomeValido ? 'border-red-300' : 'border-slate-200'))"
                                   placeholder="SEU NOME COMPLETO">
                            <span x-show="nomeAutoPreenchido" x-cloak class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-700">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    Automático
                                </span>
                            </span>
                        </div>
                        <p x-show="nomeAutoPreenchido" x-cloak class="mt-1 text-xs text-emerald-600">Nome encontrado na base do sistema. Confira e corrija se necessário.</p>
                        <div x-show="termoEmpresaNome" x-cloak class="mt-2 flex items-start gap-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2">
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            <p class="text-xs text-amber-800">Esse nome parece ser de uma <strong>empresa</strong> (<span x-text="termoEmpresaNome"></span>). Informe o <strong>seu nome completo</strong>, como consta no CPF. Os dados da empresa são cadastrados depois, em Estabelecimentos.</p>
                        </div>
                        <p x-show="tocado.nome && !termoEmpresaNome && !nomeValido" x-cloak class="mt-1 text-xs text-red-600">Informe nome e sobrenome, somente letras.</p>
                        @error('nome')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </fieldset>

                {{-- Contato --}}
                <fieldset class="space-y-4">
                    <legend class="text-sm font-semibold text-slate-900 mb-3">Contato</legend>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">E-mail <span class="text-red-500">*</span></label>
                            <input type="email" id="email" name="email" x-model.trim="email" @blur="tocado.email = true"
                                   autocomplete="email" maxlength="255"
                                   class="w-full px-4 py-3 text-sm bg-slate-50 border rounded-xl focus:bg-white focus:ring-4 focus:ring-blue-500/15 focus:border-blue-500 transition-all duration-200 @error('email') border-red-300 @enderror"
                                   :class="tocado.email && !emailValido ? 'border-red-300' : 'border-slate-200'"
                                   placeholder="seu@email.com">
                            <p x-show="tocado.email && !emailValido" x-cloak class="mt-1 text-xs text-red-600">Digite um e-mail válido.</p>
                            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="telefone" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Telefone / WhatsApp <span class="text-red-500">*</span></label>
                            <input type="text" id="telefone" name="telefone" x-model="telefone" @input="formatTelefone()" @blur="tocado.telefone = true"
                                   inputmode="tel" autocomplete="tel" maxlength="15"
                                   class="w-full px-4 py-3 text-sm bg-slate-50 border rounded-xl focus:bg-white focus:ring-4 focus:ring-blue-500/15 focus:border-blue-500 transition-all duration-200 @error('telefone') border-red-300 @enderror"
                                   :class="tocado.telefone && !telefoneValido ? 'border-red-300' : 'border-slate-200'"
                                   placeholder="(00) 00000-0000">
                            <p x-show="tocado.telefone && !telefoneValido" x-cloak class="mt-1 text-xs text-red-600">Informe o telefone com DDD.</p>
                            @error('telefone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">Usaremos o e-mail para recuperação de senha e o telefone para avisos sobre seus processos.</p>
                </fieldset>

                {{-- Segurança --}}
                <fieldset class="space-y-4">
                    <legend class="text-sm font-semibold text-slate-900 mb-3">Senha de acesso</legend>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Senha <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input :type="mostrarSenha ? 'text' : 'password'" id="password" name="password" x-model="senha"
                                       autocomplete="new-password" maxlength="255"
                                       class="w-full px-4 py-3 pr-12 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-4 focus:ring-blue-500/15 focus:border-blue-500 transition-all duration-200 @error('password') border-red-300 @enderror"
                                       placeholder="••••••••">
                                <button type="button" @click="mostrarSenha = !mostrarSenha" class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-slate-600" :title="mostrarSenha ? 'Ocultar senha' : 'Mostrar senha'">
                                    <svg x-show="!mostrarSenha" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="mostrarSenha" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Confirmar senha <span class="text-red-500">*</span></label>
                            <input :type="mostrarSenha ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" x-model="confirmacao"
                                   autocomplete="new-password" maxlength="255"
                                   class="w-full px-4 py-3 text-sm bg-slate-50 border rounded-xl focus:bg-white focus:ring-4 focus:ring-blue-500/15 focus:border-blue-500 transition-all duration-200"
                                   :class="confirmacao && !senhasConferem ? 'border-red-300' : 'border-slate-200'"
                                   placeholder="••••••••">
                            <p x-show="confirmacao && !senhasConferem" x-cloak class="mt-1 text-xs text-red-600">As senhas não conferem.</p>
                        </div>
                    </div>

                    {{-- Requisitos da senha --}}
                    <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200/70 p-3">
                        <div class="flex gap-1 mb-2.5">
                            <template x-for="n in 4" :key="n">
                                <div class="h-1 flex-1 rounded-full transition" :class="forcaSenha >= n ? corForca : 'bg-slate-200'"></div>
                            </template>
                        </div>
                        <ul class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
                            <template x-for="req in requisitosSenha" :key="req.label">
                                <li class="flex items-center gap-1.5" :class="req.ok ? 'text-emerald-600' : 'text-slate-500'">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path x-show="req.ok" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        <circle x-show="!req.ok" cx="12" cy="12" r="4" stroke-width="2"/>
                                    </svg>
                                    <span x-text="req.label"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </fieldset>
                <p x-show="erroEtapa" x-cloak x-text="erroEtapa" class="text-xs font-medium text-red-600" role="alert"></p>
                <div class="flex items-center justify-between gap-3">
                    <button type="button" @click="voltarEtapa()" class="px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-900">Voltar</button>
                    <button type="button" @click="avancarEtapa()" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/20">
                        Continuar
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
                </div>

                {{-- Termos --}}
                <div x-show="etapaAtual === 4" x-cloak x-transition class="space-y-5">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Aceite dos termos</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Leia os termos para concluir a criação da sua conta.</p>
                </div>
                <div class="pt-6 border-t border-slate-100">
                    <div class="flex items-start gap-3 rounded-xl p-3 -m-3 transition" :class="termosAceitos ? 'bg-emerald-50/60' : ''">
                        <input type="hidden" name="aceite_termos" :value="termosAceitos ? '1' : '0'">
                        <input id="aceite_termos" x-ref="termos" type="checkbox"
                               :checked="termosAceitos"
                               @click.prevent="termosLidos ? (termosAceitos = !termosAceitos) : (modalAberto = true)"
                               class="mt-0.5 w-5 h-5 text-blue-600 border-slate-300 rounded focus:ring-blue-500 cursor-pointer">
                        <label for="aceite_termos" class="text-sm text-slate-700 leading-relaxed cursor-pointer">
                            Li, compreendi e aceito os
                            <button type="button" @click.prevent="modalAberto = true" class="text-blue-600 hover:text-blue-700 font-semibold underline underline-offset-2">Termos e Condições de Uso</button>
                            do InfoVISA. <span class="text-red-500">*</span>
                            <span x-show="!termosLidos" class="block mt-0.5 text-xs text-amber-600">Abra e leia os termos para poder aceitá-los.</span>
                        </label>
                    </div>
                    @error('aceite_termos')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- Enviar --}}
                <div class="flex items-center justify-between gap-3">
                    <button type="button" @click="voltarEtapa()" class="px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-900">Voltar</button>
                    <button type="submit" :disabled="!podeEnviar || enviando"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:from-blue-700 hover:to-indigo-700 disabled:cursor-not-allowed disabled:opacity-50 sm:flex-none">
                        <svg x-show="enviando" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="enviando ? 'Criando conta...' : 'Criar minha conta'"></span>
                        <svg x-show="!enviando" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                    </button>
                </div>
                <div>
                    <p x-show="!podeEnviar" class="mt-2 text-center text-xs text-slate-500" x-text="pendencia"></p>
                </div>
                </div>

                {{-- Modal de Termos e Condições --}}
                <div x-show="modalAberto" x-cloak @keydown.escape.window="modalAberto = false"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4"
                     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="modalAberto = false"></div>

                    <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full flex flex-col max-h-[90vh]" @click.stop>
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="text-base font-semibold text-slate-900 flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </span>
                                Termos e Condições de Uso
                            </h3>
                            <button type="button" @click="modalAberto = false" class="text-slate-400 hover:text-slate-600 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="px-6 py-5 overflow-y-auto text-left">
                            <h4 class="font-bold text-slate-900 text-center mb-5 border-b border-slate-100 pb-3">
                                DECLARAÇÃO E ACEITE DOS TERMOS DE USO DO SISTEMA INFOVISA
                            </h4>

                            <div class="space-y-4 text-sm text-slate-700 leading-relaxed">
                                <p>
                                    <strong class="text-slate-900">DECLARO</strong> que conheço a legislação sanitária e demais normas pertinentes às atividades CNAEs exercidas na Instituição* que neste ato represento.
                                </p>

                                <p>
                                    <strong class="text-slate-900">DECLARO</strong> que todo o documento protocolado é verdadeiro e está sujeito ao aceite da DIRETORIA DE VIGILÂNCIA SANITÁRIA – DVISA, podendo ser recusado se não atender aos critérios exigidos.
                                </p>

                                <p>
                                    Estou <strong class="text-slate-900">CIENTE E ACEITO</strong> que os documentos relacionados à instituição e ao processo de licenciamento são tramitados exclusivamente pelo sistema INFOVISA e produzem <strong class="text-slate-900">EFEITO DE NOTIFICAÇÃO OFICIAL</strong> nos termos do <strong class="text-slate-900">Art. 14, §1º da Portaria Nº 305/2026/SES/GASEC</strong>:
                                </p>

                                <blockquote class="border-l-4 border-blue-500 bg-blue-50 px-4 py-3 text-sm text-slate-800 italic rounded-r-lg">
                                    “§1º O estabelecimento é considerado notificado oficialmente quando o INFOVISA for acessado por um dos colaboradores da empresa, independente da visualização ou não do documento, ou após 5 (cinco) dias de sua disponibilidade no INFOVISA.”
                                </blockquote>

                                <p class="text-sm text-slate-800">
                                    <strong class="text-slate-900">Em outras palavras:</strong> a notificação oficial ocorre na <strong>primeira</strong> das hipóteses abaixo, o que acontecer primeiro:
                                </p>
                                <ol class="list-decimal list-inside space-y-2 text-sm text-slate-800 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                                    <li>quando <strong>qualquer colaborador da empresa acessar o INFOVISA</strong>, mesmo sem abrir ou visualizar o documento específico; ou</li>
                                    <li><strong>automaticamente, após 5 (cinco) dias</strong> da disponibilização do documento no INFOVISA, mesmo que ninguém tenha acessado o sistema.</li>
                                </ol>
                                <p class="text-xs text-slate-600">
                                    Ou seja: não é necessário abrir o documento para que a notificação seja considerada válida. O simples acesso ao sistema por um colaborador, ou o prazo de 5 dias, já produz o efeito oficial.
                                </p>

                                <p>
                                    Estou <strong class="text-slate-900">CIENTE</strong> que qualquer alteração de Responsável Legal ou Técnico, estrutura física, procedimentos operacionais e/ou atividade exercida devo comunicar oficialmente pelo INFOVISA** a Vigilância Sanitária Estadual no prazo de cinco dias úteis.
                                </p>

                                <p>
                                    <strong class="text-slate-900">DECLARO</strong> ainda, sob as penas da lei, serem verdadeiras as informações prestadas e que estou ciente de que, sendo constatada a omissão de qualquer informação relevante ou a declaração falsa no cadastro da instituição e/ou processo de licenciamento sanitário, ficará configurado <strong class="text-red-600">crime de falsidade ideológica</strong>, previsto no artigo 299 do Código Penal Brasileiro, ensejando na cassação automática da Licença Sanitária, sem prejuízo de sanções civis e criminais cabíveis.
                                </p>
                            </div>

                            <div class="mt-5 pt-4 border-t border-slate-100 text-xs text-slate-500 space-y-1">
                                <p><strong>*Instituição:</strong> empresa, estabelecimento ou serviço de natureza jurídica pública ou privada.</p>
                                <p><strong>**INFOVISA:</strong> Sistema oficial de informação e gerenciamento da Vigilância Sanitária Estadual.</p>
                            </div>
                        </div>

                        <div class="bg-slate-50 px-6 py-4 rounded-b-2xl flex flex-col-reverse sm:flex-row gap-3 border-t border-slate-100">
                            <button type="button" @click="modalAberto = false"
                                    class="flex-1 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl font-medium hover:bg-slate-100 transition">
                                Fechar
                            </button>
                            <button type="button" @click="termosLidos = true; termosAceitos = true; modalAberto = false"
                                    class="flex-1 px-4 py-2.5 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-700 transition flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Li e aceito os termos
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Link para login --}}
            <div class="mt-7 text-center border-t border-slate-100 pt-6">
                <p class="text-sm text-slate-600">
                    Já tem uma conta?
                    <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700 font-bold transition-colors">Faça login</a>
                </p>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center text-sm text-slate-500 hover:text-slate-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Voltar para página inicial
            </a>
        </div>
    </div>
</div>

<style>[x-cloak]{display:none !important;}</style>

<script>
const termosEmpresa = @js(\App\Support\NomePessoaHelper::TERMOS_EMPRESA);

function registroForm() {
    return {
        etapas: ['Validar CPF', 'Como vai usar', 'Seus dados', 'Aceite dos termos'],
        etapaCadastro: @js($errors->has('aceite_termos') ? 4 : (($errors->has('nome') || $errors->has('email') || $errors->has('telefone') || $errors->has('password')) ? 3 : 2)),
        modulos: @js(array_values((array) old('modulos', ($moduloSugerido ?? null) ? [$moduloSugerido] : []))),
        cpf: @js(old('cpf', $cpfFornecido ?? '')),
        nome: @js(old('nome', '')),
        email: @js(old('email', '')),
        telefone: @js(old('telefone', '')),
        senha: '',
        confirmacao: '',
        mostrarSenha: false,
        cpfStatus: 'aguardando', // aguardando | consultando | valido | invalido | cadastrado
        mensagemCpf: '',
        nomeAutoPreenchido: false,
        consultando: false,
        enviando: false,
        erroEtapa: '',
        modalAberto: false,
        termosLidos: @js((bool) old('aceite_termos')),
        termosAceitos: @js((bool) old('aceite_termos')),
        tocado: { nome: false, email: false, telefone: false },
        debounceTimer: null,
        consultaId: 0,

        init() {
            this.formatCpf();
            this.formatTelefone();
            if (this.cpfDigitos.length === 11) {
                this.consultarCpf();
            }
        },

        get cpfDigitos() { return this.cpf.replace(/\D/g, ''); },
        get nomeValido() { return !this.termoEmpresaNome && /^[A-Za-zÀ-ÿ'.\-]+(\s+[A-Za-zÀ-ÿ'.\-]+)+$/.test(this.nome.trim()); },
        // Espelha App\Support\NomePessoaHelper::termoEmpresa (a validação definitiva é no servidor)
        get termoEmpresaNome() {
            const n = this.nome.normalize('NFD').replace(/[̀-ͯ]/g, '').toUpperCase().trim();
            if (!n) return null;
            const sa = n.match(/\bS\s*[\/.]\s*A\b\.?/);
            if (sa) return sa[0].trim();
            if (n.includes('&')) return '&';
            if (/\d/.test(n)) return 'números';
            return n.split(/[^A-Z]+/).find(p => termosEmpresa.includes(p)) || null;
        },
        get emailValido() { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(this.email); },
        get telefoneValido() { const d = this.telefone.replace(/\D/g, ''); return d.length === 10 || d.length === 11; },
        get senhasConferem() { return this.senha !== '' && this.senha === this.confirmacao; },

        get requisitosSenha() {
            return [
                { label: 'Mínimo 8 caracteres', ok: this.senha.length >= 8 },
                { label: 'Pelo menos uma letra', ok: /\p{L}/u.test(this.senha) },
                { label: 'Pelo menos um número', ok: /\d/.test(this.senha) },
                { label: 'Senhas conferem', ok: this.senhasConferem },
            ];
        },
        get senhaValida() { return this.requisitosSenha.slice(0, 3).every(r => r.ok); },
        get forcaSenha() {
            if (!this.senha) return 0;
            let f = this.requisitosSenha.slice(0, 3).filter(r => r.ok).length;
            if (this.senha.length >= 12 && /[^\p{L}\d]/u.test(this.senha)) f++;
            return Math.max(1, f);
        },
        get corForca() { return ['bg-red-400', 'bg-red-400', 'bg-amber-400', 'bg-emerald-500', 'bg-emerald-600'][this.forcaSenha]; },

        get dadosValidos() {
            return this.nomeValido && this.emailValido && this.telefoneValido && this.senhaValida && this.senhasConferem;
        },
        get etapaAtual() {
            if (this.cpfStatus !== 'valido') return 1;
            return this.etapaCadastro;
        },
        get podeEnviar() { return this.cpfStatus === 'valido' && this.modulos.length > 0 && this.dadosValidos && this.termosAceitos; },
        get pendencia() {
            if (!this.modulos.length) return 'Informe como você vai usar o InfoVISA.';
            if (this.termoEmpresaNome) return 'Informe o seu nome, não o nome da empresa.';
            if (!this.nomeValido) return 'Informe seu nome completo.';
            if (!this.emailValido) return 'Informe um e-mail válido.';
            if (!this.telefoneValido) return 'Informe um telefone com DDD.';
            if (!this.senhaValida) return 'A senha não atende aos requisitos.';
            if (!this.senhasConferem) return 'Confirme a senha.';
            if (!this.termosAceitos) return 'Aceite os termos de uso para continuar.';
            return '';
        },

        alternarModulo(modulo) {
            this.erroEtapa = '';
            this.modulos = this.modulos.includes(modulo)
                ? this.modulos.filter(m => m !== modulo)
                : [...this.modulos, modulo];
        },

        avancarEtapa() {
            this.erroEtapa = '';

            if (this.etapaAtual === 2) {
                if (!this.modulos.length) {
                    this.erroEtapa = 'Selecione pelo menos uma opção para continuar.';
                    return;
                }

                this.etapaCadastro = 3;
                this.$nextTick(() => {
                    const alvo = this.nomeValido ? document.getElementById('email') : this.$refs.nome;
                    alvo?.focus();
                });
            } else if (this.etapaAtual === 3) {
                this.tocado = { nome: true, email: true, telefone: true };
                if (!this.dadosValidos) {
                    this.erroEtapa = this.pendencia;
                    const alvo = !this.nomeValido ? this.$refs.nome
                        : !this.emailValido ? document.getElementById('email')
                        : !this.telefoneValido ? document.getElementById('telefone')
                        : !this.senhaValida ? document.getElementById('password')
                        : document.getElementById('password_confirmation');
                    this.$nextTick(() => alvo?.focus());
                    return;
                }

                this.etapaCadastro = 4;
                this.$nextTick(() => this.$refs.termos?.focus());
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        voltarEtapa() {
            if (this.etapaCadastro <= 2) return;
            this.etapaCadastro--;
            this.erroEtapa = '';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        formatCpf() {
            let v = this.cpfDigitos.slice(0, 11);
            v = v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            this.cpf = v;
        },

        formatTelefone() {
            const d = this.telefone.replace(/\D/g, '').slice(0, 11);
            let v = d;
            if (d.length > 10) v = d.replace(/^(\d{2})(\d{5})(\d{0,4})$/, '($1) $2-$3');
            else if (d.length > 6) v = d.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3');
            else if (d.length > 2) v = d.replace(/^(\d{2})(\d{0,5})$/, '($1) $2');
            else if (d.length > 0) v = '(' + d;
            this.telefone = v;
        },

        onCpfInput() {
            this.formatCpf();
            clearTimeout(this.debounceTimer);

            if (this.cpfDigitos.length < 11) {
                this.cpfStatus = 'aguardando';
                this.mensagemCpf = '';
                return;
            }

            this.debounceTimer = setTimeout(() => this.consultarCpf(), 350);
        },

        alterarCpf() {
            this.cpfStatus = 'aguardando';
            this.mensagemCpf = '';
            this.etapaCadastro = 2;
            this.erroEtapa = '';
            if (this.nomeAutoPreenchido) {
                this.nome = '';
                this.nomeAutoPreenchido = false;
            }
            this.$nextTick(() => { this.$refs.cpf.focus(); this.$refs.cpf.select(); });
        },

        async consultarCpf() {
            const digitos = this.cpfDigitos;
            if (digitos.length !== 11) return;

            const id = ++this.consultaId;
            this.cpfStatus = 'consultando';
            this.mensagemCpf = 'Verificando CPF...';
            this.consultando = true;

            try {
                // url(): respeita o APP_URL (subpasta /infovisacore em produção)
                const response = await fetch(@js(url('/api/consultar-cpf')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ cpf: digitos })
                });

                // Resposta de uma consulta antiga (usuário alterou o CPF no meio): ignora
                if (id !== this.consultaId) return;

                if (response.status === 429) {
                    this.cpfStatus = 'invalido';
                    this.mensagemCpf = 'Muitas tentativas. Aguarde um minuto e tente novamente.';
                    return;
                }

                const data = await response.json().catch(() => ({}));

                // Erro do servidor (404, 500...) não significa CPF inválido: trata como falha de verificação
                if (!response.ok && response.status !== 422) {
                    throw new Error('Falha ao consultar CPF (HTTP ' + response.status + ')');
                }

                if (!response.ok || !data.valido) {
                    this.cpfStatus = 'invalido';
                    this.mensagemCpf = data.mensagem || 'CPF inválido. Verifique os números digitados.';
                    return;
                }

                if (data.cadastrado) {
                    this.cpfStatus = 'cadastrado';
                    this.mensagemCpf = data.mensagem || 'Este CPF já possui cadastro no sistema.';
                    return;
                }

                this.cpfStatus = 'valido';
                this.erroEtapa = '';
                if (data.encontrado && data.nome && !this.nome) {
                    this.nome = data.nome;
                    this.nomeAutoPreenchido = true;
                    this.mensagemCpf = 'CPF válido. Nome preenchido automaticamente.';
                } else {
                    this.mensagemCpf = 'CPF válido. Complete seus dados abaixo.';
                }
                this.focarEtapaAtual();
            } catch (error) {
                if (id !== this.consultaId) return;
                console.error('Erro ao consultar CPF:', error);
                // Falha de rede: libera o preenchimento manual; o servidor valida tudo no envio
                this.cpfStatus = 'valido';
                this.mensagemCpf = 'Não foi possível verificar agora. Preencha os dados manualmente.';
                this.focarEtapaAtual();
            } finally {
                if (id === this.consultaId) this.consultando = false;
            }
        },

        focarEtapaAtual() {
            this.$nextTick(() => {
                if (this.etapaCadastro === 2) this.$refs.moduloProcessos?.focus();
                else if (this.etapaCadastro === 3) {
                    const alvo = this.nomeValido ? document.getElementById('email') : this.$refs.nome;
                    alvo?.focus();
                } else this.$refs.termos?.focus();
            });
        },

        onSubmit(event) {
            if (this.etapaAtual !== 4) {
                event.preventDefault();
                this.avancarEtapa();
                return;
            }
            if (!this.podeEnviar || this.enviando) {
                event.preventDefault();
                this.tocado = { nome: true, email: true, telefone: true };
                return;
            }
            this.enviando = true;
        }
    }
}
</script>
@endsection
