@extends('layouts.company')

@php
    $titulos = [
        'medico' => ['📋', 'Médico, Cirurgião Dentista e Médico Veterinário'],
        'instituicao' => ['🏥', 'Instituição (Hospital, Clínica e Similares)'],
        'secretaria' => ['🏛️', 'Secretaria de Saúde e Vigilância Sanitária'],
        'talidomida' => ['💊', 'Prescritor de Talidomida'],
    ];
    $rotaBuscarCnpj = route('company.receituarios.buscar-cnpj');
    // Carteira do conselho (só na área da empresa): obrigatória para médico/dentista/veterinário
    $permitirCarteira = in_array($tipo, ['medico', 'talidomida'], true);
    $carteiraObrigatoria = $tipo === 'medico';
    $rotaLerCarteira = route('company.receituarios.ler-carteira');
    // Comprovante de endereço (água, energia ou telefone fixo): obrigatório para médico/dentista/veterinário
    $permitirComprovante = $permitirCarteira;
    $comprovanteObrigatorio = $tipo === 'medico';
    $rotaLerComprovante = route('company.receituarios.ler-comprovante');
@endphp

@section('title', 'Cadastrar profissional')
@section('page-title', 'Receituário · Cadastrar profissional')

@section('content')
<div class="max-w-8xl mx-auto space-y-4" x-data="wizardReceituario()"
     @estado-passo="estadoPasso[$event.detail.passo] = { motivo: $event.detail.motivo || '', lendo: !!$event.detail.lendo }">

    {{-- Cabeçalho --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <a href="{{ route('company.receituarios.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-700">← Profissionais cadastrados</a>
            <h1 class="text-xl font-bold text-slate-900 mt-1">{{ $titulos[$tipo][0] }} Cadastro de {{ $titulos[$tipo][1] }}</h1>
        </div>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 text-sm">
            <p class="font-semibold mb-1">Revise os dados informados:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('company.receituarios.store') }}" id="receituarioForm" enctype="multipart/form-data" @submit="validarEnvio($event)" class="space-y-4">
        @csrf
        <input type="hidden" name="tipo" value="{{ $tipo }}">

        @if($tipo === 'medico')
        {{-- O cadastro é sempre do próprio profissional, feito com a conta dele --}}
        <input type="hidden" name="solicitante" value="proprio">

        <div class="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
            <span class="text-lg leading-none">👨‍⚕️</span>
            <div>
                <p>Cadastro do profissional <strong>{{ mb_strtoupper($usuario->nome, 'UTF-8') }}</strong> · CPF {{ $usuario->cpf_formatado }}</p>
                <p class="mt-0.5 text-xs text-blue-800">
                    O cadastro de receituário é feito pelo <strong>próprio profissional</strong>, com a conta dele no InfoVISA.
                    A carteira do conselho enviada no passo 1 deve estar no <strong>seu nome e CPF</strong>.
                    Se você é secretária ou funcionária, peça ao profissional para criar a conta dele e, depois, ele pode te dar acesso em "Usuários vinculados".
                </p>
            </div>
        </div>
        @endif

        {{-- Profissional já cadastrado no sistema --}}
        <div x-show="duplicado" x-cloak class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3" role="alert">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div class="text-sm text-red-900">
                <p class="font-semibold">Profissional já cadastrado</p>
                <p class="mt-0.5" x-text="duplicado?.mensagem"></p>
                <a x-show="duplicado?.url" :href="duplicado?.url" class="mt-1.5 inline-block text-xs font-semibold text-red-800 underline">Abrir o cadastro →</a>
            </div>
        </div>

        <div x-show="podeVerPassos()" x-cloak class="space-y-4">
            {{-- Passos --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-5 py-3">
                <div class="flex items-center">
                    <template x-for="(step, index) in steps" :key="index">
                        <div class="flex items-center" :class="index < steps.length - 1 ? 'flex-1' : ''">
                            <button type="button" @click="goToStep(index)" class="flex flex-col items-center gap-1 group">
                                <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition"
                                      :class="currentStep === index ? 'bg-blue-600 text-white ring-4 ring-blue-100' : (currentStep > index ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500')">
                                    <span x-show="currentStep > index">✓</span>
                                    <span x-show="currentStep <= index" x-text="index + 1"></span>
                                </span>
                                <span class="text-xs font-semibold whitespace-nowrap" :class="currentStep === index ? 'text-blue-700' : 'text-slate-500'" x-text="step.title"></span>
                            </button>
                            <div x-show="index < steps.length - 1" class="flex-1 h-0.5 mx-3 mb-4 rounded" :class="currentStep > index ? 'bg-emerald-500' : 'bg-slate-200'"></div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Conteúdo dos passos (os mesmos formulários usados pela vigilância) --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                @if(in_array($tipo, ['medico', 'talidomida']))
                    @include('receituarios.wizard.medico-steps')
                    @if($tipo === 'medico')
                        @include('company.receituarios.partials.passo-ficha-assinada')
                    @endif
                @elseif($tipo === 'instituicao')
                    @include('receituarios.wizard.instituicao-steps')
                @elseif($tipo === 'secretaria')
                    @include('receituarios.wizard.secretaria-steps')
                @endif
            </div>

            {{-- Navegação --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-4 py-3 space-y-3">
                {{-- O que falta para continuar (carteira/comprovante ainda não processados ou com pendência) --}}
                <div x-show="pendenciaAtual() && !lendoAtual()" x-cloak
                     class="flex items-start gap-2 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900" role="status">
                    <svg class="w-4 h-4 flex-shrink-0 text-amber-600 mt-px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><strong>Para continuar:</strong> <span x-text="pendenciaAtual()"></span></span>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <button type="button" @click="previousStep()" x-show="currentStep > 0"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 font-semibold text-sm">
                        ← Voltar
                    </button>
                    <span x-show="currentStep === 0"></span>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('company.receituarios.index') }}" class="px-5 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-800">Cancelar</a>

                        {{-- Enquanto o documento é lido, o "Próximo" dá lugar ao andamento da leitura --}}
                        <span x-show="lendoAtual()" x-cloak
                              class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-100 text-slate-600 rounded-xl font-semibold text-sm" role="status">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Lendo o documento…
                        </span>
                        <button type="button" @click="nextStep()" x-show="currentStep < steps.length - 1 && !lendoAtual()"
                                :aria-disabled="pendenciaAtual() ? 'true' : 'false'"
                                :title="pendenciaAtual()"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm transition"
                                :class="pendenciaAtual() ? 'bg-slate-200 text-slate-500 cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700'">
                            Próximo →
                        </button>
                        <button type="submit" x-show="currentStep === steps.length - 1 && !lendoAtual()"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 font-semibold text-sm">
                            ✓ Enviar cadastro para análise
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function wizardReceituario() {
    const tipo = @js($tipo);
    const usuario = @js([
        'nome' => mb_strtoupper($usuario->nome, 'UTF-8'),
        'cpf' => $usuario->cpf_formatado,
        'telefone' => $usuario->telefone ? $usuario->telefone_formatado : '',
        'email' => $usuario->email,
    ]);
    const passos = {
        medico: ['Dados Pessoais', 'Endereço', 'Locais de Trabalho', 'Ficha assinada'],
        talidomida: ['Dados Pessoais', 'Endereço', 'Locais de Trabalho'],
        instituicao: ['Dados da Instituição', 'Endereço e Contato', 'Responsável Técnico'],
        secretaria: ['Dados da Secretaria', 'Endereço e Contato', 'Responsável'],
    };

    return {
        currentStep: 0,
        steps: passos[tipo].map(title => ({ title })),
        solicitante: 'proprio', // cadastro sempre do próprio profissional
        alterandoSolicitante: false,
        avisoEscolha: false,
        // Estado enviado pela carteira do conselho (passo 0) e pelo comprovante de endereço (passo 1)
        estadoPasso: {},
        // Profissional já cadastrado (CPF existente no sistema)
        duplicado: null,
        cpfVerificado: '',

        async verificarCpf() {
            if (!['medico', 'talidomida'].includes(tipo)) return true;
            const cpf = (this.campo('cpf')?.value || '').replace(/\D/g, '');
            if (cpf.length !== 11) return true;
            if (cpf === this.cpfVerificado) return !this.duplicado;
            try {
                const r = await fetch(@js(route('company.receituarios.verificar-cpf')) + '?cpf=' + cpf, { headers: { 'Accept': 'application/json' } });
                const dados = r.ok ? await r.json() : { existe: false };
                this.cpfVerificado = cpf;
                this.duplicado = dados.existe ? dados : null;
            } catch (e) {
                return true; // sem conexão: o servidor confere de novo no envio
            }
            if (this.duplicado) window.scrollTo({ top: 0, behavior: 'smooth' });
            return !this.duplicado;
        },

        lendoAtual() {
            return !!this.estadoPasso[this.currentStep]?.lendo;
        },

        pendenciaAtual() {
            return this.estadoPasso[this.currentStep]?.motivo || '';
        },

        init() {
            if (tipo === 'medico') this.$nextTick(() => { this.aplicarSolicitante(); this.verificarCpf(); });
        },

        podeVerPassos() {
            return tipo !== 'medico' || this.solicitante !== '';
        },

        campo(nome) {
            return document.querySelector(`#receituarioForm [name="${nome}"]`);
        },

        escolher(opcao) {
            const trocou = this.solicitante && this.solicitante !== opcao;
            this.solicitante = opcao;
            this.alterandoSolicitante = false;
            this.avisoEscolha = false;
            this.$nextTick(() => { this.aplicarSolicitante(trocou); this.verificarCpf(); });
        },

        // "Sou o profissional": preenche com o cadastro e trava nome/CPF.
        // "Outro profissional": libera os campos para digitar os dados dele.
        aplicarSolicitante(limpar = false) {
            const proprio = this.solicitante === 'proprio';
            const definir = (nome, valor, travar) => {
                const el = this.campo(nome);
                if (!el) return;
                if (proprio) {
                    if (valor !== undefined && valor !== null && (travar || !el.value)) el.value = valor;
                } else if (limpar) {
                    el.value = '';
                }
                el.readOnly = proprio && travar;
                el.dispatchEvent(new Event('change')); // avisa quem acompanha o campo (ex.: leitura da carteira)
                el.classList.toggle('bg-slate-100', proprio && travar);
                el.classList.toggle('cursor-not-allowed', proprio && travar);
            };
            definir('nome', usuario.nome, true);
            definir('cpf', usuario.cpf, true);
            definir('telefone', usuario.telefone, false);
        },

        validarPassoAtual() {
            if (tipo === 'medico' && !this.solicitante) {
                this.avisoEscolha = true;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return false;
            }
            // Passo 1: carteira do conselho (frente e verso, CPF conferido). Passo 2: comprovante de endereço
            const motivo = this.bloqueioDoPasso(this.currentStep);
            if (motivo) {
                this.avisarCarteira(motivo, this.currentStep);
                return false;
            }
            if (this.currentStep === 0 && ['medico', 'talidomida'].includes(tipo)) {
                const buscaEspecialidade = document.getElementById('busca-especialidade');
                const especialidadeSelecionada = this.campo('especialidade')?.value || '';
                const textoEspecialidade = buscaEspecialidade?.value.trim() || '';
                if (textoEspecialidade && textoEspecialidade !== especialidadeSelecionada) {
                    buscaEspecialidade.setCustomValidity('Selecione uma opção da lista antes de continuar.');
                    buscaEspecialidade.reportValidity();
                    buscaEspecialidade.setCustomValidity('');
                    buscaEspecialidade.focus();
                    return false;
                }
            }
            // Campos obrigatórios visíveis do passo atual
            const visiveis = [...document.querySelectorAll('#receituarioForm [required]')].filter(el => el.offsetParent !== null);
            for (const el of visiveis) {
                if (!el.value.trim()) {
                    el.reportValidity();
                    return false;
                }
            }
            return true;
        },

        async nextStep() {
            if (!this.validarPassoAtual()) return;
            if (this.currentStep === 0 && !(await this.verificarCpf())) return;
            if (this.currentStep < this.steps.length - 1) {
                this.currentStep++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        previousStep() {
            if (this.currentStep > 0) {
                this.currentStep--;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        async goToStep(index) {
            if (index > this.currentStep && !this.validarPassoAtual()) return;
            if (index > 0 && this.currentStep === 0 && !(await this.verificarCpf())) return;
            this.currentStep = index;
        },

        // Componente da carteira do conselho (médico e talidomida)
        carteira() {
            const el = document.querySelector('#receituarioForm [data-carteira]');
            return el ? Alpine.$data(el) : null;
        },

        comprovante() {
            const el = document.querySelector('#receituarioForm [data-comprovante]');
            return el ? Alpine.$data(el) : null;
        },

        bloqueioDoPasso(passo) {
            if (passo === 0) return this.carteira()?.bloqueio() || '';
            if (passo === 1) return this.comprovante()?.bloqueio() || '';
            return '';
        },

        avisarCarteira(motivo, passo = 0) {
            this.currentStep = passo;
            const bloco = document.querySelector(passo === 1 ? '#receituarioForm [data-comprovante]' : '#receituarioForm [data-carteira] section');
            bloco?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            bloco?.classList.add('ring-4', 'ring-red-200');
            setTimeout(() => bloco?.classList.remove('ring-4', 'ring-red-200'), 2500);
            // O motivo já aparece em "Para continuar" acima dos botões; o alerta só cobre o caso de o aviso não estar visível
            if (!this.estadoPasso[passo]) alert(motivo);
        },

        validarEnvio(evento) {
            if (this.duplicado) {
                evento.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            if (tipo === 'medico' && !this.solicitante) {
                evento.preventDefault();
                this.avisoEscolha = true;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            for (const passo of [0, 1]) {
                const motivo = this.bloqueioDoPasso(passo);
                if (motivo) {
                    evento.preventDefault();
                    this.avisarCarteira(motivo, passo);
                    return;
                }
            }
        },
    };
}
</script>
@endsection
