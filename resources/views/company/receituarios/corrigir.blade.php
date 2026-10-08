@extends('layouts.company')

{{-- Correção de um documento rejeitado: abre o MESMO passo do cadastro (carteira → Passo 1, comprovante → Passo 2),
     com os dados atuais preenchidos. Os outros passos não mudam. --}}
@php
    $info = \App\Models\Receituario::DOCUMENTOS[$documento];
    $rotaBuscarCnpj = route('company.receituarios.buscar-cnpj');
    $permitirCarteira = in_array($tipo, ['medico', 'talidomida'], true);
    $carteiraObrigatoria = $documento === 'carteira'; // a carteira rejeitada precisa ser enviada de novo
    $rotaLerCarteira = route('company.receituarios.ler-carteira');
    $permitirComprovante = $permitirCarteira;
    $comprovanteObrigatorio = $documento === 'comprovante';
    $rotaLerComprovante = route('company.receituarios.ler-comprovante');
    $nomesPassos = ['Dados Pessoais', 'Endereço', 'Locais de Trabalho'];
    $oQueFazer = $documento === 'carteira'
        ? 'Envie a carteira do conselho de novo (frente e verso, bem nítida) e confira os dados do profissional. A gente lê a carteira e confere com o CPF.'
        : 'Envie um novo comprovante de endereço (água, energia ou telefone fixo) e confira o endereço. A gente lê o comprovante e preenche o CEP e o endereço.';
@endphp

@section('title', 'Corrigir ' . mb_strtolower($info['nome']))
@section('page-title', 'Receituário · Corrigir cadastro')

@section('content')
<div class="max-w-8xl mx-auto space-y-4" x-data="correcaoReceituario()"
     @estado-passo="estadoPasso[$event.detail.passo] = { motivo: $event.detail.motivo || '', lendo: !!$event.detail.lendo }">

    {{-- Cabeçalho --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('company.receituarios.show', [$receituario->id, 'aba' => 'documentos']) }}" title="Voltar para o cadastro"
           class="w-9 h-9 flex-shrink-0 rounded-lg border border-slate-200 bg-white text-slate-500 flex items-center justify-center hover:bg-slate-50 hover:text-slate-800">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-slate-900">Corrigir {{ mb_strtolower($info['nome']) }}</h1>
            <p class="text-xs text-slate-500 truncate">{{ $receituario->identificador }} · cadastro #{{ $receituario->id }}</p>
        </div>
    </div>

    {{-- Por que voltou e o que fazer --}}
    <section role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3.5">
        <div class="flex items-start gap-3">
            <span class="w-9 h-9 flex-shrink-0 rounded-lg bg-red-100 text-red-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <div class="min-w-0 text-sm text-red-950">
                <p class="font-bold">A Vigilância Sanitária rejeitou {{ $info['a'] === 'a' ? 'a' : 'o' }} {{ mb_strtolower($info['nome']) }}</p>
                <p class="mt-1 rounded-lg bg-white/70 border border-red-100 px-3 py-2 text-sm whitespace-pre-line"><span class="text-xs font-semibold uppercase tracking-wide text-red-500">Motivo</span><br>{{ $motivo ?: 'Não informado.' }}</p>
                <p class="mt-2 text-xs text-red-800"><strong>O que fazer:</strong> {{ $oQueFazer }}</p>
            </div>
        </div>
    </section>

    @if($errors->any())
        <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-xl p-4 text-sm">
            <p class="font-semibold mb-1">Revise antes de enviar:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach
            </ul>
            <p class="mt-1 text-xs">Por segurança, escolha o arquivo de novo.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('company.receituarios.salvar-correcao', [$receituario->id, $documento]) }}" id="receituarioForm"
          enctype="multipart/form-data" @submit="validarEnvio($event)" class="space-y-4">
        @csrf
        <input type="hidden" name="tipo" value="{{ $tipo }}">

        {{-- Passos: só o passo do documento rejeitado é aberto; os outros ficam como estão --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-5 py-3">
            <div class="flex items-center">
                @foreach($nomesPassos as $indice => $nomePasso)
                    <div class="flex items-center {{ $indice < count($nomesPassos) - 1 ? 'flex-1' : '' }}">
                        <div class="flex flex-col items-center gap-1">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                                {{ $indice === $passo ? 'bg-red-600 text-white ring-4 ring-red-100' : 'bg-emerald-500 text-white' }}">
                                {{ $indice === $passo ? $indice + 1 : '✓' }}
                            </span>
                            <span class="text-xs font-semibold whitespace-nowrap {{ $indice === $passo ? 'text-red-700' : 'text-slate-400' }}">{{ $nomePasso }}</span>
                            <span class="text-[10px] {{ $indice === $passo ? 'text-red-600 font-semibold' : 'text-slate-400' }}">{{ $indice === $passo ? 'corrigir' : 'sem alteração' }}</span>
                        </div>
                        @if($indice < count($nomesPassos) - 1)
                            <div class="flex-1 h-0.5 mx-3 mb-8 rounded bg-slate-200"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- O mesmo passo do cadastro --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            @include('receituarios.wizard.medico-steps')
        </div>

        {{-- Envio --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-4 py-3 space-y-3">
            <div x-show="pendenciaAtual() && !lendoAtual()" x-cloak
                 class="flex items-start gap-2 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900" role="status">
                <svg class="w-4 h-4 flex-shrink-0 text-amber-600 mt-px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><strong>Para enviar:</strong> <span x-text="pendenciaAtual()"></span></span>
            </div>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('company.receituarios.show', [$receituario->id, 'aba' => 'documentos']) }}" class="px-5 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-800">Cancelar</a>
                <span x-show="lendoAtual()" x-cloak
                      class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-100 text-slate-600 rounded-xl font-semibold text-sm" role="status">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Lendo o documento…
                </span>
                <button type="submit" x-show="!lendoAtual()"
                        :aria-disabled="pendenciaAtual() ? 'true' : 'false'"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm transition"
                        :class="pendenciaAtual() ? 'bg-slate-200 text-slate-500 cursor-not-allowed' : 'bg-emerald-600 text-white hover:bg-emerald-700'">
                    ✓ Enviar correção para análise
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function correcaoReceituario() {
    const passo = @js($passo);
    const travarNomeCpf = @js($receituario->solicitante_proprio === true);

    return {
        currentStep: passo,
        estadoPasso: {},

        init() {
            this.$nextTick(() => this.desativarOutrosPassos());
            // "Sou o profissional": nome e CPF são os do usuário e não mudam
            if (travarNomeCpf) {
                this.$nextTick(() => ['nome', 'cpf'].forEach(nome => {
                    const el = this.campo(nome);
                    if (!el) return;
                    el.readOnly = true;
                    el.classList.add('bg-slate-100', 'cursor-not-allowed');
                }));
            }
        },

        campo(nome) {
            return document.querySelector(`#receituarioForm [name="${nome}"]`);
        },

        lendoAtual() {
            return !!this.estadoPasso[passo]?.lendo;
        },

        pendenciaAtual() {
            return this.estadoPasso[passo]?.motivo || '';
        },

        carteira() {
            const el = document.querySelector('#receituarioForm [data-carteira]');
            return el ? Alpine.$data(el) : null;
        },

        comprovante() {
            const el = document.querySelector('#receituarioForm [data-comprovante]');
            return el ? Alpine.$data(el) : null;
        },

        avisar(motivo) {
            const bloco = document.querySelector(passo === 1 ? '#receituarioForm [data-comprovante]' : '#receituarioForm [data-carteira] section');
            bloco?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            bloco?.classList.add('ring-4', 'ring-red-200');
            setTimeout(() => bloco?.classList.remove('ring-4', 'ring-red-200'), 2500);
            if (!this.estadoPasso[passo]) alert(motivo);
        },

        validarEnvio(evento) {
            const motivo = passo === 0 ? (this.carteira()?.bloqueio() || '') : (this.comprovante()?.bloqueio() || '');
            if (motivo) {
                evento.preventDefault();
                this.avisar(motivo);
                return;
            }
            if (passo === 0) {
                const busca = document.getElementById('busca-especialidade');
                const selecionada = this.campo('especialidade')?.value || '';
                if (busca && busca.value.trim() && busca.value.trim() !== selecionada) {
                    evento.preventDefault();
                    busca.setCustomValidity('Selecione uma opção da lista antes de enviar.');
                    busca.reportValidity();
                    busca.setCustomValidity('');
                    return;
                }
            }
            this.desativarOutrosPassos();
        },

        // Só vai o passo corrigido: os campos dos outros passos não são enviados nem validados
        // (continuam legíveis — o comprovante usa o nome do profissional do Passo 1)
        desativarOutrosPassos() {
            document.querySelectorAll('#receituarioForm [data-passo]').forEach(bloco => {
                if (bloco.dataset.passo !== String(passo)) {
                    bloco.querySelectorAll('input, select, textarea').forEach(el => { el.disabled = true; });
                }
            });
        },
    };
}
</script>
@endsection
