{{-- STEP 1: DADOS PESSOAIS --}}
<div x-show="currentStep === 0" x-transition data-passo="0">
    <div class="mb-4">
        <h3 class="text-lg font-bold text-blue-900 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Passo 1: Dados Pessoais
        </h3>
        <p class="text-sm text-gray-500">Preencha as informações pessoais do profissional</p>
    </div>

    <div class="space-y-4"
         @if(!empty($permitirCarteira))
         x-data="carteiraConselho(@js(['url' => $rotaLerCarteira ?? '', 'obrigatoria' => !empty($carteiraObrigatoria), 'liberar' => !empty(old('tipo'))]))" data-carteira
         x-effect="$dispatch('estado-passo', { passo: 0, motivo: bloqueio(), lendo })"
         @endif>
        @if(!empty($permitirCarteira))
        {{-- Carteira do conselho (frente e verso): o sistema lê e preenche os dados do profissional --}}
        <section class="rounded-2xl border border-slate-200 bg-gradient-to-b from-slate-50 to-white p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center flex-shrink-0 shadow-sm shadow-blue-600/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                    </span>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="text-sm font-bold text-slate-900">Carteira do conselho</h4>
                            <span class="text-xs text-slate-500">CRM, CRO ou CRMV</span>
                            @if(!empty($carteiraObrigatoria))
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-red-50 text-red-600 ring-1 ring-red-100">Obrigatória</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-slate-100 text-slate-500">Opcional</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Envie a <strong class="text-slate-700">frente</strong> e o <strong class="text-slate-700">verso</strong> (foto ou PDF). Um PDF com as duas páginas também serve. A gente lê a carteira e preenche os dados para você.</p>
                    </div>
                </div>
                <button type="button" x-show="completa && !lendo" x-cloak @click="lerTudo()"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Ler de novo
                </button>
            </div>

            {{-- Frente e verso --}}
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach(['frente' => ['Frente', 'Lado com foto, nome e nº do conselho', 'carteira_conselho'], 'verso' => ['Verso', 'Lado com CPF e dados pessoais', 'carteira_conselho_verso']] as $lado => [$rotuloLado, $dicaLado, $campoLado])
                <div class="relative">
                    <input type="file" name="{{ $campoLado }}" id="carteira_{{ $lado }}" class="hidden"
                           x-ref="arquivo{{ ucfirst($lado) }}"
                           accept="application/pdf,image/jpeg,image/png,image/webp"
                           @change="escolherArquivo('{{ $lado }}', $event)">

                    {{-- Vazio --}}
                    <label for="carteira_{{ $lado }}" x-show="!lados.{{ $lado }}.arquivo{{ $lado === 'verso' ? ' && !versoNoPdf && !versoJunto' : '' }}"
                           class="group flex flex-col items-center justify-center gap-1.5 h-40 px-4 rounded-xl border-2 border-dashed bg-white cursor-pointer transition text-center"
                           :class="{{ $lado === 'verso' ? 'faltaVerso' : 'false' }} ? 'border-blue-400 bg-blue-50/60 animate-pulse' : 'border-slate-300 hover:border-blue-400 hover:bg-blue-50/40'">
                        @if($lado === 'frente')
                            <svg class="w-14 h-10 text-slate-300 group-hover:text-blue-400 transition" viewBox="0 0 56 40" fill="none"><rect x="1" y="1" width="54" height="38" rx="5" stroke="currentColor" stroke-width="2"/><circle cx="44" cy="17" r="5" fill="currentColor"/><path d="M36 30c1.5-4 4.5-6 8-6s6.5 2 8 6" fill="currentColor"/><rect x="7" y="9" width="20" height="3" rx="1.5" fill="currentColor"/><rect x="7" y="17" width="14" height="2.5" rx="1.25" fill="currentColor"/><rect x="7" y="23" width="17" height="2.5" rx="1.25" fill="currentColor"/></svg>
                        @else
                            <svg class="w-14 h-10 text-slate-300 group-hover:text-blue-400 transition" viewBox="0 0 56 40" fill="none"><rect x="1" y="1" width="54" height="38" rx="5" stroke="currentColor" stroke-width="2"/><rect x="7" y="8" width="12" height="2.5" rx="1.25" fill="currentColor"/><rect x="7" y="13" width="20" height="3" rx="1.5" fill="currentColor"/><rect x="7" y="21" width="12" height="2.5" rx="1.25" fill="currentColor"/><rect x="7" y="26" width="16" height="3" rx="1.5" fill="currentColor"/><rect x="37" y="11" width="12" height="12" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="41" y="15" width="4" height="4" fill="currentColor"/></svg>
                        @endif
                        <span class="text-sm font-semibold text-slate-800">{{ $rotuloLado }} da carteira</span>
                        <span class="text-xs text-slate-500">{{ $dicaLado }}</span>
                        <span class="mt-1 inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-600 text-white text-xs font-semibold group-hover:bg-blue-700">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Enviar {{ mb_strtolower($rotuloLado) }}
                        </span>
                    </label>

                    {{-- Arquivo escolhido --}}
                    <div x-show="lados.{{ $lado }}.arquivo" x-cloak class="relative h-40 rounded-xl overflow-hidden border border-slate-200 bg-slate-100 shadow-sm">
                        <img x-show="lados.{{ $lado }}.previa" :src="lados.{{ $lado }}.previa" alt="{{ $rotuloLado }} da carteira" class="w-full h-full object-contain">
                        <div x-show="!lados.{{ $lado }}.previa" class="w-full h-full flex items-center justify-center">
                            <span class="w-12 h-14 rounded-lg bg-white border border-slate-200 text-red-600 flex items-center justify-center text-xs font-bold shadow-sm">PDF</span>
                        </div>
                        <div x-show="lendo" class="absolute inset-0 bg-blue-600/10 overflow-hidden"><div class="carteira-scan"></div></div>
                        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-slate-900/70 text-white text-[11px] font-semibold backdrop-blur">{{ $rotuloLado }}</span>
                        <span x-show="resultado && !lendo" class="absolute top-2 right-2 w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center shadow">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <div class="absolute inset-x-0 bottom-0 flex items-center gap-2 px-2.5 py-1.5 bg-white/95 backdrop-blur border-t border-slate-200">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-slate-800 truncate" x-text="lados.{{ $lado }}.nome"></p>
                                <p class="text-[11px] text-slate-500"><span x-text="lados.{{ $lado }}.tamanho"></span><span x-show="lados.{{ $lado }}.paginas > 1" x-text="' · ' + lados.{{ $lado }}.paginas + ' páginas'"></span></p>
                            </div>
                            <label for="carteira_{{ $lado }}" x-show="!lendo" class="px-2 py-1 text-[11px] font-semibold text-slate-600 bg-slate-100 rounded-md hover:bg-slate-200 cursor-pointer">Trocar</label>
                        </div>
                    </div>

                    @if($lado === 'verso')
                    {{-- Frente e verso no mesmo arquivo/foto --}}
                    <button type="button" x-show="lados.frente.arquivo && !lados.verso.arquivo && !versoNoPdf && !versoJunto" x-cloak @click="marcarVersoJunto(true)"
                            class="mt-1.5 w-full text-center text-[11px] font-semibold text-blue-600 hover:underline">
                        A frente e o verso estão no mesmo arquivo / na mesma foto
                    </button>
                    <div x-show="versoJunto && !lados.verso.arquivo" x-cloak class="relative h-40 rounded-xl overflow-hidden border border-slate-200 bg-slate-100 shadow-sm">
                        <img x-show="lados.frente.previa" :src="lados.frente.previa" alt="Frente e verso da carteira" class="w-full h-full object-contain">
                        <div x-show="lendo" class="absolute inset-0 bg-blue-600/10 overflow-hidden"><div class="carteira-scan"></div></div>
                        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-slate-900/70 text-white text-[11px] font-semibold backdrop-blur">Verso</span>
                        <div class="absolute inset-x-0 bottom-0 flex items-center gap-2 px-2.5 py-1.5 bg-white/95 backdrop-blur border-t border-slate-200">
                            <p class="flex-1 text-xs font-semibold text-emerald-700">✓ No mesmo arquivo da frente</p>
                            <button type="button" x-show="!lendo" @click="marcarVersoJunto(false)" class="px-2 py-1 text-[11px] font-semibold text-slate-600 bg-slate-100 rounded-md hover:bg-slate-200">Desfazer</button>
                        </div>
                    </div>
                    <input type="hidden" name="carteira_frente_verso_juntos" :value="versoJunto ? 1 : ''">

                    {{-- Verso já veio no PDF da frente --}}
                    <div x-show="versoNoPdf && !lados.verso.arquivo" x-cloak class="relative h-40 rounded-xl overflow-hidden border border-slate-200 bg-slate-100 shadow-sm">
                        <img x-show="lados.frente.previa2" :src="lados.frente.previa2" alt="Verso da carteira" class="w-full h-full object-contain">
                        <div x-show="lendo" class="absolute inset-0 bg-blue-600/10 overflow-hidden"><div class="carteira-scan"></div></div>
                        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-slate-900/70 text-white text-[11px] font-semibold backdrop-blur">Verso</span>
                        <span x-show="resultado && !lendo" class="absolute top-2 right-2 w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center shadow">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <div class="absolute inset-x-0 bottom-0 flex items-center gap-2 px-2.5 py-1.5 bg-white/95 backdrop-blur border-t border-slate-200">
                            <p class="flex-1 text-xs font-semibold text-emerald-700">✓ Página 2 do PDF da frente</p>
                            <label for="carteira_verso" x-show="!lendo" class="px-2 py-1 text-[11px] font-semibold text-slate-600 bg-slate-100 rounded-md hover:bg-slate-200 cursor-pointer">Enviar outro</label>
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>

            @error('carteira_conselho')<p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
            @error('carteira_conselho_verso')<p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>@enderror

            {{-- Falta o verso --}}
            <p x-show="faltaVerso && !lendo" x-cloak class="mt-3 flex items-center gap-2 text-xs font-medium text-blue-700">
                <span class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center">👉</span>
                Ótimo! Agora envie o <strong>verso</strong> da carteira para começarmos a leitura — ou, se a frente e o verso estão no mesmo arquivo, clique em "<strong>A frente e o verso estão no mesmo arquivo</strong>".
            </p>

            {{-- Lendo: etapas --}}
            <div x-show="lendo" x-cloak class="mt-4 rounded-xl border border-blue-100 bg-white p-4">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <p class="text-sm font-semibold text-slate-800">Lendo a carteira…</p>
                    <span class="text-xs font-semibold text-blue-600" x-text="progressoTotal + '%'"></span>
                </div>
                <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 transition-all duration-300" :style="`width: ${progressoTotal}%`"></div>
                </div>
                <ol class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <template x-for="(nomeEtapa, i) in etapas" :key="i">
                        <li class="flex items-center gap-2 text-xs" :class="i < etapa ? 'text-emerald-700' : (i === etapa ? 'text-blue-700 font-semibold' : 'text-slate-400')">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0"
                                  :class="i < etapa ? 'bg-emerald-100' : (i === etapa ? 'bg-blue-100' : 'bg-slate-100')">
                                <svg x-show="i < etapa" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                <svg x-show="i === etapa" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span x-show="i > etapa" class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                            </span>
                            <span x-text="nomeEtapa + (i === etapa && detalhe ? ' · ' + detalhe : '')"></span>
                        </li>
                    </template>
                </ol>
            </div>


            {{-- Documento não identificado / pouca nitidez: enviar arquivo melhor ou declarar ciência --}}
            <div x-show="precisaCiencia" x-cloak class="mt-3 rounded-xl border overflow-hidden"
                 :class="cienteIlegivel ? 'border-amber-200' : 'border-red-200'" x-data="{ verDicas: false }">
                <div class="flex items-start gap-3 px-4 py-3" :class="cienteIlegivel ? 'bg-amber-50' : 'bg-red-50'">
                    <span class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                          :class="cienteIlegivel ? 'bg-amber-100 text-amber-600' : 'bg-red-100 text-red-600'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86l-8.1 14.02A2 2 0 003.92 21h16.16a2 2 0 001.73-3.12l-8.1-14.02a2 2 0 00-3.46 0z"/></svg>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold" :class="cienteIlegivel ? 'text-amber-900' : 'text-red-900'"
                           x-text="erro && erro.startsWith('Muitas') ? erro : ('Documento não identificado' + (poucaNitidez ? ' (imagem com baixa nitidez)' : ''))"></p>
                        <p class="mt-0.5 text-xs" :class="cienteIlegivel ? 'text-amber-800' : 'text-red-800'">
                            Não conseguimos identificar os dados da carteira do conselho (<strong>nº do conselho, nome e CPF</strong>).
                            Um documento <strong>ilegível pode levar à rejeição do cadastro</strong> pela Vigilância Sanitária.
                        </p>
                    </div>
                </div>

                <div class="bg-white px-4 py-3 space-y-3">
                    {{-- Opção 1: arquivo melhor --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <label for="carteira_frente" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Enviar arquivo mais nítido
                        </label>
                        <button type="button" @click="verDicas = !verDicas" class="text-xs font-semibold text-blue-700 hover:underline"
                                x-text="verDicas ? 'Ocultar dicas' : 'Como tirar uma boa foto?'"></button>
                    </div>
                    <ul x-show="verDicas" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600">
                        <li>✓ Use scanner de mesa ou app de digitalização (ex.: Google Drive → Digitalizar)</li>
                        <li>✓ Boa luz, sem reflexo, sombra ou flash estourado</li>
                        <li>✓ Documento inteiro, reto e na posição de leitura</li>
                        <li>✓ Frente e verso legíveis (números, nome e CPF)</li>
                    </ul>

                    {{-- Opção 2: seguir assim, declarando ciência --}}
                    <label class="flex items-start gap-2.5 rounded-lg border px-3 py-2.5 cursor-pointer transition"
                           :class="cienteIlegivel ? 'border-amber-300 bg-amber-50' : 'border-slate-200 hover:bg-slate-50'">
                        <input type="checkbox" x-model="cienteIlegivel" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500 flex-shrink-0">
                        <span class="text-xs text-slate-700 leading-relaxed">
                            <strong class="text-slate-900">Não tenho um arquivo melhor agora.</strong>
                            Estou ciente de que o documento enviado <strong>não foi identificado e pode estar ilegível</strong>, e que, por isso,
                            o cadastro <strong>poderá ser rejeitado</strong> pela Vigilância Sanitária. Vou conferir e preencher os dados manualmente.
                        </span>
                    </label>
                    <input type="hidden" name="carteira_ilegivel_ciente" :value="precisaCiencia && cienteIlegivel ? 1 : 0">
                </div>
            </div>

            {{-- Resultado: conferência --}}
            <div x-show="resultado && !lendo" x-cloak class="mt-4 rounded-xl border border-slate-200 bg-white overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 border-b border-slate-100 bg-emerald-50/60">
                    <p class="flex items-center gap-2 text-sm font-semibold text-emerald-800">
                        <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        Carteira lida — confira os dados
                    </p>
                    <span class="text-[11px] font-medium text-slate-500" x-text="resultado?.origem === 'ia' ? '✨ Lido com inteligência artificial' : 'Lido automaticamente'"></span>
                </div>
                <dl class="grid grid-cols-1 sm:grid-cols-2">
                    <div class="px-4 py-3 border-b sm:border-r border-slate-100">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Nº do conselho</dt>
                        <dd class="mt-0.5 flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-slate-900" x-text="resultado?.numero_formatado || 'Não encontrado'"></span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="resultado?.numero_formatado ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'" x-text="resultado?.numero_formatado ? 'Preenchido' : 'Preencha abaixo'"></span>
                        </dd>
                    </div>
                    <div class="px-4 py-3 border-b border-slate-100">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Especialidade</dt>
                        <dd class="mt-0.5 flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold" :class="resultado?.especialidade ? 'text-slate-900' : 'text-slate-400'" x-text="resultado?.especialidade || 'Não consta na carteira'"></span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="resultado?.especialidade ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'" x-text="resultado?.especialidade ? 'Preenchido' : 'Selecione abaixo'"></span>
                        </dd>
                    </div>
                    <div class="px-4 py-3 border-b sm:border-b-0 sm:border-r border-slate-100">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Nome na carteira</dt>
                        <dd class="mt-0.5 flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold truncate" :class="nomeCarteira ? 'text-slate-900' : 'text-slate-400'" x-text="nomeCarteira || 'Não encontrado'"></span>
                            <span x-show="nomeCarteira && nomeFormulario" class="px-2 py-0.5 rounded-full text-[10px] font-bold whitespace-nowrap" :class="nomeDivergente ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'" x-text="nomeDivergente ? 'Diferente' : 'Confere'"></span>
                        </dd>
                    </div>
                    <div class="px-4 py-3">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">CPF na carteira</dt>
                        <dd class="mt-0.5 flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold" :class="cpfCarteira ? 'text-slate-900' : 'text-slate-400'" x-text="cpfCarteira ? formatarCpf(cpfCarteira) : 'Não encontrado'"></span>
                            <span x-show="cpfStatus" class="px-2 py-0.5 rounded-full text-[10px] font-bold whitespace-nowrap"
                                  :class="{ 'bg-emerald-50 text-emerald-700': cpfStatus === 'igual', 'bg-red-50 text-red-700': cpfStatus === 'diferente', 'bg-slate-100 text-slate-500': cpfStatus === 'duvida' }"
                                  x-text="{ igual: 'Confere', diferente: 'Diferente', duvida: 'Não deu para confirmar' }[cpfStatus]"></span>
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Divergência de CPF: exige uma decisão antes de continuar --}}
            <div x-show="cpfStatus === 'diferente' && !lendo" x-cloak x-transition.opacity
                 class="fixed inset-0 z-[100] flex items-center justify-center p-4" role="presentation">
                <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm"></div>
                <section role="dialog" aria-modal="true" aria-labelledby="cpf-divergente-titulo"
                         class="relative w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl sm:p-6">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86l-8.1 14.02A2 2 0 003.92 21h16.16a2 2 0 001.73-3.12l-8.1-14.02a2 2 0 00-3.46 0z"/>
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <h2 id="cpf-divergente-titulo" class="text-base font-bold text-slate-900">O CPF da carteira não é o mesmo do profissional</h2>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                Carteira: <strong class="text-slate-900" x-text="formatarCpf(cpfCarteira)"></strong>
                                · <span x-text="typeof solicitante !== 'undefined' && solicitante === 'proprio' ? 'Seu CPF' : 'CPF informado'"></span>:
                                <strong class="text-slate-900" x-text="formatarCpf(cpfFormulario)"></strong>.
                                A carteira deve pertencer ao profissional informado no cadastro. Confira os dados e escolha uma opção abaixo.
                            </p>
                        </div>
                    </div>
                    <div class="mt-5 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <label for="carteira_frente" @click="$refs.arquivoFrente.value = ''"
                               class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Enviar outra carteira
                        </label>
                        <p x-show="typeof solicitante !== 'undefined' && solicitante === 'proprio'"
                           class="sm:col-span-1 flex items-center rounded-lg bg-red-50 px-3 py-2 text-xs text-red-800">
                            O cadastro é só do próprio profissional: envie a carteira que está no seu nome e CPF.
                        </p>
                        <button type="button" x-show="typeof solicitante === 'undefined' || solicitante !== 'proprio'"
                                @click="usarDadosDaCarteira(true)"
                                class="inline-flex min-h-11 items-center justify-center rounded-lg bg-red-600 px-3 py-2 text-center text-sm font-semibold text-white hover:bg-red-700">
                            Usar o CPF da carteira
                        </button>
                    </div>
                </section>
            </div>

            {{-- Nome diferente (CPF confere ou não encontrado) --}}
            <div x-show="nomeDivergente && cpfStatus !== 'diferente' && !lendo" x-cloak class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-bold">⚠️ O nome na carteira é diferente</p>
                <p class="mt-0.5 text-xs">
                    Carteira: <strong x-text="nomeCarteira"></strong> ·
                    <span x-text="typeof solicitante !== 'undefined' && solicitante === 'proprio' ? 'Seu cadastro' : 'Nome informado'"></span>: <strong x-text="nomeFormulario"></strong>.
                    Confira se é a carteira do profissional certo.
                </p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <label for="carteira_frente" class="px-2.5 py-1 text-xs font-semibold bg-white border border-amber-300 rounded-lg hover:bg-amber-100 cursor-pointer">Enviar outra carteira</label>
                    <span x-show="typeof solicitante !== 'undefined' && solicitante === 'proprio'" class="self-center text-xs">A carteira deve estar no seu nome.</span>
                    <button type="button" x-show="typeof solicitante === 'undefined' || solicitante !== 'proprio'" @click="usarNomeDaCarteira()"
                            class="px-2.5 py-1 text-xs font-semibold text-white bg-amber-600 rounded-lg hover:bg-amber-700">Usar o nome da carteira</button>
                </div>
            </div>

            <input type="hidden" name="carteira_leitura" :value="resultado ? JSON.stringify(resultado) : ''">
        </section>

        {{-- Os dados do profissional aparecem depois da carteira --}}
        <div x-show="!liberado" class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3">
            <span class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-400 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </span>
            <p class="flex-1 text-xs text-slate-600">
                <strong class="text-slate-800">Nome, CPF, telefone, especialidade e nº do conselho</strong> aparecem aqui depois que a carteira for lida.
            </p>
            @if(empty($carteiraObrigatoria))
                <button type="button" @click="manual = true" class="text-xs font-semibold text-blue-600 hover:underline whitespace-nowrap">Não tenho a carteira — preencher manualmente</button>
            @endif
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3" @if(!empty($permitirCarteira)) x-show="liberado" x-cloak x-transition @endif>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1">
                    Nome Completo <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nome" value="{{ old('nome') }}" required
                       style="text-transform: uppercase;"
                       placeholder="Digite o nome completo"
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                @error('nome')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">
                    CPF <span class="text-red-500">*</span>
                </label>
                <input type="text" name="cpf" value="{{ old('cpf') }}" required 
                       x-mask="999.999.999-99"
                       placeholder="000.000.000-00"
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                @error('cpf')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">
                    Telefone <span class="text-red-500">*</span>
                </label>
                <input type="text" name="telefone" value="{{ old('telefone') }}" required
                       x-mask="(99) 99999-9999"
                       placeholder="(00) 00000-0000"
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                @error('telefone')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
            </div>

            @if($tipo == 'talidomida')
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">
                    Telefone 2 (Opcional)
                </label>
                <input type="text" name="telefone2" value="{{ old('telefone2') }}"
                       x-mask="(99) 99999-9999"
                       placeholder="(00) 00000-0000"
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">
                    E-mail
                </label>
                <input type="email" name="email" value="{{ old('email') }}"
                       placeholder="email@exemplo.com"
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
            </div>
            @endif

            <div x-data="{
                    aberto: false,
                    busca: @js(in_array(old('especialidade'), \App\Models\Receituario::ESPECIALIDADES, true) ? old('especialidade') : ''),
                    selecionada: @js(in_array(old('especialidade'), \App\Models\Receituario::ESPECIALIDADES, true) ? old('especialidade') : ''),
                    indice: 0,
                    opcoes: @js(\App\Models\Receituario::ESPECIALIDADES),
                    get filtradas() {
                        const termo = this.busca.trim().toLocaleLowerCase('pt-BR');
                        return this.opcoes.filter(opcao => opcao.toLocaleLowerCase('pt-BR').includes(termo));
                    },
                    escolher(opcao) {
                        this.selecionada = opcao;
                        this.busca = opcao;
                        this.$refs.selectEspecialidade.value = opcao;
                        this.$refs.selectEspecialidade.dispatchEvent(new Event('change', { bubbles: true }));
                        this.aberto = false;
                    }
                }" @click.outside="aberto = false">
                <label for="busca-especialidade" class="block text-xs font-semibold text-gray-600 mb-1">
                    Especialidade ou área de atuação <span class="text-red-500">*</span>
                </label>
                <input id="busca-especialidade" type="search" x-model="busca" role="combobox" aria-autocomplete="list"
                       :aria-expanded="aberto.toString()" aria-controls="lista-especialidades"
                       required
                       @focus="aberto = true" @input="aberto = true; indice = 0; selecionada = ''; $refs.selectEspecialidade.value = ''"
                       @keydown.escape="aberto = false"
                       @keydown.arrow-down.prevent="if (!aberto) { aberto = true; indice = 0 } else if (filtradas.length) indice = Math.min(indice + 1, filtradas.length - 1)"
                       @keydown.arrow-up.prevent="if (filtradas.length) indice = Math.max(indice - 1, 0)"
                       @keydown.enter.prevent="if (aberto && filtradas[indice]) escolher(filtradas[indice])"
                       placeholder="Digite para pesquisar..." autocomplete="off"
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                <select name="especialidade" x-ref="selectEspecialidade" x-model="selecionada"
                        @change="selecionada = $event.target.value; busca = selecionada"
                        class="hidden" aria-hidden="true" tabindex="-1">
                    <option value="">Selecione...</option>
                    @foreach(\App\Models\Receituario::ESPECIALIDADES as $especialidade)
                        <option value="{{ $especialidade }}" @selected(old('especialidade') === $especialidade)>{{ $especialidade }}</option>
                    @endforeach
                </select>
                <div id="lista-especialidades" x-show="aberto" x-cloak role="listbox"
                     class="relative z-20 mt-1 max-h-56 overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                    <template x-for="(opcao, posicao) in filtradas" :key="opcao">
                        <button type="button" role="option" :aria-selected="selecionada === opcao"
                                @mouseenter="indice = posicao" @click="escolher(opcao)"
                                :class="indice === posicao ? 'bg-blue-50 text-blue-800' : 'text-gray-700'"
                                class="block w-full px-3 py-2 text-left text-sm hover:bg-blue-50 hover:text-blue-800"
                                x-text="opcao"></button>
                    </template>
                    <p x-show="filtradas.length === 0" class="px-3 py-2 text-sm text-gray-500">Nenhuma opção encontrada.</p>
                </div>
                <p class="mt-1 text-[11px] text-gray-500">Selecione uma opção da lista.</p>
                @error('especialidade')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">
                    @if($tipo == 'talidomida')
                        Nº CRM
                    @else
                        Nº Conselho de Classe
                    @endif
                    <span class="text-red-500">*</span>
                </label>
                <input type="text" name="{{ $tipo == 'talidomida' ? 'numero_crm' : 'numero_conselho_classe' }}" 
                       value="{{ old($tipo == 'talidomida' ? 'numero_crm' : 'numero_conselho_classe') }}"
                       required
                       style="text-transform: uppercase;"
                       placeholder="Ex: CRM-TO 1234"
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
            </div>
        </div>
    </div>
</div>

{{-- STEP 2: ENDEREÇO (primeiro o CEP; os demais campos aparecem depois da busca) --}}
<div x-show="currentStep === 1" x-transition data-passo="1" x-data="cepLookup(@js(['comprovante' => !empty($permitirComprovante)]))"
     @comprovante-lido="aoLerComprovante($event.detail)" @comprovante-liberar="comprovanteLiberado = true">
    <div class="mb-4">
        <h3 class="text-lg font-bold text-green-900 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Passo 2: Endereço {{ $tipo == 'talidomida' ? 'Residencial' : '' }}
        </h3>
        <p class="text-sm text-gray-500">
            @if(!empty($permitirComprovante))
                Comece pelo comprovante de endereço: o CEP, o endereço e o município são preenchidos automaticamente.
            @else
                Comece pelo CEP: o endereço e o município são preenchidos automaticamente.
            @endif
        </p>
    </div>

    <div class="space-y-4">
        @if(!empty($permitirComprovante))
        {{-- Comprovante de endereço: o sistema lê CEP, endereço e titular --}}
        <section x-data="comprovanteEndereco(@js(['url' => $rotaLerComprovante ?? '', 'obrigatorio' => !empty($comprovanteObrigatorio), 'vinculos' => \App\Services\LeitorComprovanteEnderecoService::VINCULOS]))" data-comprovante
                 x-effect="$dispatch('estado-passo', { passo: 1, motivo: bloqueio(), lendo })"
                 class="rounded-2xl border border-slate-200 bg-gradient-to-b from-slate-50 to-white p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 shadow-sm shadow-emerald-600/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 14l2 2 4-4M7 3h10a2 2 0 012 2v16l-3-2-2 2-2-2-2 2-2-2-3 2V5a2 2 0 012-2z"/></svg>
                    </span>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="text-sm font-bold text-slate-900">Comprovante de endereço</h4>
                            @if(!empty($comprovanteObrigatorio))
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-red-50 text-red-600 ring-1 ring-red-100">Obrigatório</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-slate-100 text-slate-500">Opcional</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Conta de <strong class="text-slate-700">água</strong>, <strong class="text-slate-700">energia</strong> ou <strong class="text-slate-700">telefone fixo</strong> — residencial ou comercial. A gente lê o CEP e o endereço para você. Se não estiver no nome do profissional, é só aceitar a declaração.</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach(['PDF ou foto', 'Legível, na posição de leitura', 'Sem sombras, distorções ou cortes'] as $regra)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[11px] text-slate-600">
                                    <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>{{ $regra }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
                <button type="button" x-show="arquivo && !lendo" x-cloak @click="ler()"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Ler de novo
                </button>
            </div>

            <input type="file" name="comprovante_endereco" id="comprovante_endereco" x-ref="arquivoComprovante" class="hidden"
                   accept="application/pdf,image/jpeg,image/png,image/webp" @change="escolherArquivo($event)">

            {{-- Vazio --}}
            <label for="comprovante_endereco" x-show="!arquivo"
                   class="group mt-4 flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 h-auto sm:h-28 py-5 sm:py-0 px-4 rounded-xl border-2 border-dashed border-slate-300 bg-white cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/40 transition text-center sm:text-left">
                <svg class="w-12 h-14 text-slate-300 group-hover:text-emerald-400 transition flex-shrink-0" viewBox="0 0 40 48" fill="none"><path d="M3 3h34v42l-5-3-5 3-5-3-5 3-5-3-5 3-4-3V3z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><rect x="9" y="10" width="16" height="3" rx="1.5" fill="currentColor"/><rect x="9" y="17" width="22" height="2.5" rx="1.25" fill="currentColor"/><rect x="9" y="23" width="18" height="2.5" rx="1.25" fill="currentColor"/><rect x="22" y="31" width="9" height="4" rx="1" fill="currentColor"/></svg>
                <span>
                    <span class="block text-sm font-semibold text-slate-800">Envie a conta de água, energia ou telefone fixo</span>
                    <span class="block text-xs text-slate-500">Clique para escolher o arquivo ou tirar uma foto · até 10 MB</span>
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-600 text-white text-xs font-semibold group-hover:bg-emerald-700 sm:ml-auto">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Enviar comprovante
                </span>
            </label>

            {{-- Arquivo escolhido --}}
            <div x-show="arquivo" x-cloak class="mt-4 flex items-center gap-3 p-2.5 rounded-xl bg-white border border-slate-200 shadow-sm">
                <div class="relative w-32 h-24 rounded-lg overflow-hidden bg-slate-100 flex-shrink-0">
                    <img x-show="previa" :src="previa" alt="Comprovante de endereço" class="w-full h-full object-contain">
                    <div x-show="!previa" class="w-full h-full flex items-center justify-center text-xs font-bold text-red-600">PDF</div>
                    <div x-show="lendo" class="absolute inset-0 bg-emerald-600/10 overflow-hidden"><div class="carteira-scan"></div></div>
                    <span x-show="resultado && !lendo" class="absolute top-1.5 right-1.5 w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center shadow">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-800 truncate" x-text="nomeArquivo"></p>
                    <p class="text-xs text-slate-500" x-text="tamanho"></p>
                    <p x-show="resultado?.tipo_descricao && !lendo" class="mt-1 text-xs font-medium" :class="resultado?.tipo === 'outro' ? 'text-amber-700' : 'text-emerald-700'" x-text="resultado?.tipo_descricao"></p>
                </div>
                <label for="comprovante_endereco" x-show="!lendo" class="px-2.5 py-1 text-xs font-semibold text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 cursor-pointer">Trocar</label>
            </div>

            @error('comprovante_endereco')<p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
            @error('comprovante_titular')<p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
            @error('declaracao_endereco_aceite')<p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>@enderror

            {{-- Lendo: etapas --}}
            <div x-show="lendo" x-cloak class="mt-4 rounded-xl border border-emerald-100 bg-white p-4">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <p class="text-sm font-semibold text-slate-800">Lendo o comprovante…</p>
                    <span class="text-xs font-semibold text-emerald-600" x-text="progressoTotal + '%'"></span>
                </div>
                <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-300" :style="`width: ${progressoTotal}%`"></div>
                </div>
                <ol class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <template x-for="(nomeEtapa, i) in etapas" :key="i">
                        <li class="flex items-center gap-2 text-xs" :class="i < etapa ? 'text-emerald-700' : (i === etapa ? 'text-emerald-700 font-semibold' : 'text-slate-400')">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0" :class="i <= etapa ? 'bg-emerald-100' : 'bg-slate-100'">
                                <svg x-show="i < etapa" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                <svg x-show="i === etapa" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span x-show="i > etapa" class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                            </span>
                            <span x-text="nomeEtapa"></span>
                        </li>
                    </template>
                </ol>
            </div>


            {{-- Comprovante não identificado / pouca nitidez: enviar arquivo melhor ou declarar ciência --}}
            <div x-show="precisaCiencia" x-cloak class="mt-3 rounded-xl border overflow-hidden"
                 :class="cienteIlegivel ? 'border-amber-200' : 'border-red-200'" x-data="{ verDicas: false }">
                <div class="flex items-start gap-3 px-4 py-3" :class="cienteIlegivel ? 'bg-amber-50' : 'bg-red-50'">
                    <span class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                          :class="cienteIlegivel ? 'bg-amber-100 text-amber-600' : 'bg-red-100 text-red-600'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86l-8.1 14.02A2 2 0 003.92 21h16.16a2 2 0 001.73-3.12l-8.1-14.02a2 2 0 00-3.46 0z"/></svg>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold" :class="cienteIlegivel ? 'text-amber-900' : 'text-red-900'"
                           x-text="erro && erro.startsWith('Muitas') ? erro : ('Comprovante não identificado' + (poucaNitidez ? ' (imagem com baixa nitidez)' : ''))"></p>
                        <p class="mt-0.5 text-xs" :class="cienteIlegivel ? 'text-amber-800' : 'text-red-800'">
                            Não conseguimos identificar os dados do comprovante de endereço (<strong>nome do titular, endereço e CEP</strong>).
                            Um documento <strong>ilegível pode levar à rejeição do cadastro</strong> pela Vigilância Sanitária.
                        </p>
                    </div>
                </div>

                <div class="bg-white px-4 py-3 space-y-3">
                    {{-- Opção 1: arquivo melhor --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <label for="comprovante_endereco" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Enviar arquivo mais nítido
                        </label>
                        <button type="button" @click="verDicas = !verDicas" class="text-xs font-semibold text-blue-700 hover:underline"
                                x-text="verDicas ? 'Ocultar dicas' : 'Como tirar uma boa foto?'"></button>
                    </div>
                    <ul x-show="verDicas" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600">
                        <li>✓ Use scanner de mesa ou app de digitalização (ex.: Google Drive → Digitalizar)</li>
                        <li>✓ Boa luz, sem reflexo, sombra ou flash estourado</li>
                        <li>✓ Documento inteiro, reto e na posição de leitura</li>
                        <li>✓ Nome do titular, endereço e CEP legíveis</li>
                    </ul>

                    {{-- Opção 2: seguir assim, declarando ciência --}}
                    <label class="flex items-start gap-2.5 rounded-lg border px-3 py-2.5 cursor-pointer transition"
                           :class="cienteIlegivel ? 'border-amber-300 bg-amber-50' : 'border-slate-200 hover:bg-slate-50'">
                        <input type="checkbox" x-model="cienteIlegivel" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500 flex-shrink-0">
                        <span class="text-xs text-slate-700 leading-relaxed">
                            <strong class="text-slate-900">Não tenho um arquivo melhor agora.</strong>
                            Estou ciente de que o comprovante enviado <strong>não foi identificado e pode estar ilegível</strong>, e que, por isso,
                            o cadastro <strong>poderá ser rejeitado</strong> pela Vigilância Sanitária. Vou conferir e preencher o endereço manualmente.
                        </span>
                    </label>
                    <input type="hidden" name="comprovante_ilegivel_ciente" :value="precisaCiencia && cienteIlegivel ? 1 : 0">
                </div>
            </div>

            {{-- Resultado: conferência --}}
            <div x-show="resultado && !lendo && !declaracaoPopupAberto" x-cloak class="mt-4 rounded-xl border border-slate-200 bg-white overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 border-b border-slate-100 bg-emerald-50/60">
                    <p class="flex items-center gap-2 text-sm font-semibold text-emerald-800">
                        <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        Comprovante lido — confira os dados
                    </p>
                    <span class="text-[11px] font-medium text-slate-500" x-text="resultado?.origem === 'ia' ? '✨ Lido com inteligência artificial' : 'Lido automaticamente'"></span>
                </div>
                <dl class="grid grid-cols-1 sm:grid-cols-2">
                    <div class="px-4 py-3 border-b sm:border-r border-slate-100">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Titular da conta</dt>
                        <dd class="mt-0.5 flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold truncate" :class="titular ? 'text-slate-900' : 'text-slate-400'" x-text="titular || 'Não identificado'"></span>
                            <span x-show="situacao === 'confere' || situacao === 'diferente'" class="px-2 py-0.5 rounded-full text-[10px] font-bold whitespace-nowrap"
                                  :class="situacao === 'confere' ? 'bg-emerald-50 text-emerald-700' : 'bg-violet-50 text-violet-700'"
                                  x-text="situacao === 'confere' ? 'No nome do profissional' : 'Outra pessoa'"></span>
                        </dd>
                    </div>
                    <div class="px-4 py-3 border-b border-slate-100">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tipo de conta</dt>
                        <dd class="mt-0.5 flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-slate-900" x-text="resultado?.tipo_descricao"></span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="resultado?.tipo === 'outro' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'" x-text="resultado?.tipo === 'outro' ? 'Verifique' : 'Aceito'"></span>
                        </dd>
                    </div>
                    <div class="px-4 py-3 border-b sm:border-b-0 sm:border-r border-slate-100">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">CEP</dt>
                        <dd class="mt-0.5 text-sm font-semibold" :class="resultado?.cep_formatado ? 'text-slate-900' : 'text-slate-400'" x-text="resultado?.cep_formatado || 'Não encontrado'"></dd>
                    </div>
                    <div class="px-4 py-3">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Endereço</dt>
                        <dd class="mt-0.5 text-sm font-semibold" :class="resultado?.endereco ? 'text-slate-900' : 'text-slate-400'"
                            x-text="resultado?.endereco ? resultado.endereco + (resultado.municipio ? ' — ' + resultado.municipio + (resultado.uf ? '/' + resultado.uf : '') : '') : 'Não encontrado'"></dd>
                    </div>
                </dl>
            </div>

            {{-- Documento que não é conta de água, energia ou telefone fixo --}}
            <div x-show="resultado?.tipo === 'outro' && !lendo" x-cloak class="mt-3 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-900">
                <span class="text-base leading-none">⚠️</span>
                <p>Este documento não parece ser uma conta de <strong>água, energia ou telefone fixo</strong>. A Vigilância Sanitária pode recusar — se puder, envie uma dessas contas.</p>
            </div>

            {{-- Titular não identificado: pergunta --}}
            <div x-show="situacao === 'desconhecido' && !lendo" x-cloak class="mt-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-sm font-semibold text-slate-800">O comprovante está no nome de <span x-text="nomeProfissional || 'o profissional'"></span>?</p>
                <p class="text-xs text-slate-500">Não conseguimos ler o nome do titular da conta.</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <button type="button" @click="situacaoTitular = 'proprio'; declaracaoPopupAberto = false"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition"
                            :class="situacaoTitular === 'proprio' ? 'bg-emerald-600 border-emerald-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'">Sim, está no nome dele(a)</button>
                    <button type="button" @click="situacaoTitular = 'terceiro'; declaracaoPopupAberto = true"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition"
                            :class="situacaoTitular === 'terceiro' ? 'bg-violet-600 border-violet-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'">Não, está em nome de outra pessoa</button>
                </div>
            </div>

            {{-- Revisão resumida após confirmação da declaração --}}
            <div x-show="precisaDeclaracao && !declaracaoPopupAberto && !lendo" x-cloak class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-violet-200 bg-violet-50/60 px-4 py-3">
                <p class="text-xs text-violet-900">
                    Declaração aceita para o vínculo <strong x-text="rotuloVinculo || 'informado'"></strong>.
                </p>
                <button type="button" @click="declaracaoPopupAberto = true" class="text-xs font-semibold text-violet-800 underline hover:text-violet-950">Revisar dados e declaração</button>
            </div>

            {{-- Popup com os dados lidos e a declaração para titular diferente --}}
            <div x-show="declaracaoPopupAberto && precisaDeclaracao && !lendo" x-cloak x-transition.opacity
                 class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-5" role="presentation">
                <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm"></div>
                <section role="dialog" aria-modal="true" aria-labelledby="declaracao-comprovante-titulo"
                         class="relative max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
                    <div class="sticky top-0 z-10 flex items-start justify-between gap-3 border-b border-slate-100 bg-white px-4 py-4 sm:px-6">
                        <div>
                            <h2 id="declaracao-comprovante-titulo" class="text-base font-bold text-slate-900">Comprovante lido — confira os dados</h2>
                            <p class="mt-1 text-xs font-medium text-slate-500" x-text="resultado?.origem === 'ia' ? '✨ Lido com inteligência artificial' : 'Lido automaticamente'"></p>
                        </div>
                        <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-bold text-violet-700">Outra pessoa</span>
                    </div>

                    <div class="space-y-4 px-4 py-4 sm:px-6 sm:py-5">
                        <dl class="grid grid-cols-1 gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 sm:grid-cols-2">
                            <div class="bg-white px-3 py-3 sm:border-r sm:border-slate-100">
                                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Titular da conta</dt>
                                <dd class="mt-1 break-words text-sm font-semibold text-slate-900" x-text="titular || titularInformado || 'Não identificado'"></dd>
                            </div>
                            <div class="bg-white px-3 py-3">
                                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tipo de conta</dt>
                                <dd class="mt-1 flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-900">
                                    <span x-text="resultado?.tipo_descricao || 'Não identificado'"></span>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" :class="resultado?.tipo === 'outro' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'" x-text="resultado?.tipo === 'outro' ? 'Verifique' : 'Aceito'"></span>
                                </dd>
                            </div>
                            <div class="bg-white px-3 py-3 sm:border-r sm:border-slate-100">
                                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">CEP</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900" x-text="resultado?.cep_formatado || 'Não encontrado'"></dd>
                            </div>
                            <div class="bg-white px-3 py-3">
                                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Endereço</dt>
                                <dd class="mt-1 break-words text-sm font-semibold text-slate-900" x-text="resultado?.endereco ? resultado.endereco + (resultado.municipio ? ' — ' + resultado.municipio + (resultado.uf ? '/' + resultado.uf : '') : '') : 'Não encontrado'"></dd>
                            </div>
                        </dl>

                        <div class="rounded-xl border border-violet-200 bg-violet-50/70 p-4">
                            <h3 class="text-sm font-bold text-violet-950">📝 Comprovante em nome de outra pessoa</h3>
                            <p x-show="situacao === 'diferente'" class="mt-1 text-xs leading-relaxed text-violet-950/80">
                                A conta está em nome de <strong x-text="titular"></strong>, e não de <strong x-text="nomeProfissional"></strong>. Sem problema: informe o vínculo e aceite a declaração.
                            </p>
                            <p x-show="situacao === 'desconhecido'" class="mt-1 text-xs leading-relaxed text-violet-950/80">
                                Informe quem está na conta, selecione o vínculo e aceite a declaração para continuar.
                            </p>

                            <div x-show="situacao === 'desconhecido'" class="mt-3">
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Nome de quem está na conta <span class="text-red-500">*</span></label>
                                <input type="text" name="comprovante_titular_nome" x-ref="titularComprovante" x-model="titularInformado" :required="situacao === 'desconhecido'" style="text-transform: uppercase;"
                                       placeholder="Nome do titular da conta"
                                       class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-violet-500 focus:ring-2 focus:ring-violet-500/30">
                            </div>

                            <div class="mt-3">
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Vínculo com o titular da conta <span class="text-red-500">*</span></label>
                                <select name="declaracao_endereco_vinculo" x-ref="vinculoDeclaracao" x-model="vinculo" required
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-violet-500 focus:ring-2 focus:ring-violet-500/30">
                                    <option value="">Selecione...</option>
                                    @foreach(\App\Services\LeitorComprovanteEnderecoService::VINCULOS as $chave => $rotulo)
                                        <option value="{{ $chave }}">{{ $rotulo }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mt-3 rounded-lg border border-violet-100 bg-white px-3 py-3 text-xs leading-relaxed text-slate-700" x-text="textoDeclaracao"></div>
                            <label class="mt-3 flex cursor-pointer select-none items-start gap-2">
                                <input type="checkbox" name="declaracao_endereco_aceite" value="1" x-model="aceite" required class="mt-0.5 h-4 w-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                                <span class="text-xs font-semibold text-slate-800">Li e aceito a declaração acima. Sei que a declaração falsa é crime.</span>
                            </label>
                        </div>

                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                            <label for="comprovante_endereco" @click="$refs.arquivoComprovante.value = ''"
                                   class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Enviar outro comprovante
                            </label>
                            <button type="button" @click="declaracaoPopupAberto = false"
                                    :disabled="!vinculo || !aceite || (situacao === 'desconhecido' && !titularInformado.trim())"
                                    class="inline-flex min-h-11 items-center justify-center rounded-lg bg-violet-700 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-800 disabled:cursor-not-allowed disabled:opacity-50">
                                Confirmar declaração
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <input type="hidden" name="comprovante_leitura" :value="resultado ? JSON.stringify(resultado) : ''">
            <input type="hidden" name="comprovante_titular" :value="situacao === 'desconhecido' ? situacaoTitular : (situacao === 'diferente' ? 'terceiro' : (situacao === 'confere' ? 'proprio' : ''))">
        </section>

        {{-- CEP e endereço aparecem depois do comprovante --}}
        <div x-show="!comprovanteLiberado" class="flex items-center gap-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3">
            <span class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-400 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </span>
            <p class="flex-1 text-xs text-slate-600"><strong class="text-slate-800">CEP, endereço e município</strong> aparecem aqui, já preenchidos, depois que o comprovante for lido.</p>
            @if(empty($comprovanteObrigatorio))
                <button type="button" @click="comprovanteLiberado = true" class="text-xs font-semibold text-blue-600 hover:underline whitespace-nowrap">Preencher sem comprovante</button>
            @endif
        </div>
        @endif

        <div class="space-y-4" @if(!empty($permitirComprovante)) x-show="comprovanteLiberado" x-cloak x-transition @endif>
        {{-- CEP --}}
        <div class="max-w-md">
            <label class="block text-xs font-semibold text-gray-600 mb-1">CEP</label>
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <input type="text" name="cep" x-model="cep"
                           x-mask="99999-999"
                           @input="aoDigitarCep()"
                           @keydown.enter.prevent="buscarCep()"
                           :readonly="buscando"
                           placeholder="00000-000"
                           inputmode="numeric"
                           class="w-full px-3 py-1.5 text-sm border rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500"
                           :class="erroCep ? 'border-red-300' : (encontrado ? 'border-green-400' : 'border-gray-300')">
                    <svg x-show="encontrado && !buscando" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <button type="button" @click="buscarCep()" :disabled="buscando"
                        class="inline-flex items-center gap-2 px-3.5 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold disabled:opacity-70 disabled:cursor-wait">
                    <svg x-show="buscando" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-text="buscando ? 'Buscando...' : 'Buscar'"></span>
                </button>
            </div>
            <p x-show="buscando" x-cloak class="mt-1.5 text-xs text-blue-600">Consultando o CEP...</p>
            <p x-show="erroCep" x-cloak class="mt-1.5 text-xs text-red-600" x-text="erroCep"></p>
            <p x-show="cepDiferenteDoComprovante()" x-cloak class="mt-1.5 text-xs text-amber-700">⚠️ Este CEP é diferente do que está no comprovante (<span x-text="cepComprovante"></span>).</p>
            <button type="button" x-show="!mostrarCampos()" @click="preencherManual()" class="mt-2 text-xs font-semibold text-blue-600 hover:underline">
                Não sei o CEP — preencher manualmente
            </button>
            @error('cep')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
        </div>

        {{-- Demais campos: só depois de buscar o CEP (ou de escolher preencher manualmente) --}}
        <div x-show="mostrarCampos()" x-cloak x-transition class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1">
                    Endereço {{ $tipo == 'talidomida' ? 'Residencial' : '' }}
                </label>
                <input type="text" name="{{ $tipo == 'talidomida' ? 'endereco_residencial' : 'endereco' }}"
                       x-model="endereco" x-ref="endereco"
                       style="text-transform: uppercase;"
                       placeholder="Rua, Avenida, Quadra, Lote, número, etc."
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                <p class="mt-1 text-xs text-gray-500">Confira e complete com o número, quadra ou lote.</p>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Município</label>
                {{-- Encontrado pelo CEP: fica travado (o valor vai pelo campo oculto) --}}
                <input type="hidden" name="municipio_id" :value="municipioId">
                <div class="relative">
                    <select x-model="municipioId" :disabled="municipioTravado"
                            class="w-full px-3 py-1.5 text-sm border rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500"
                            :class="municipioTravado ? 'bg-gray-100 border-gray-200 text-gray-700 cursor-not-allowed pr-10' : 'border-gray-300'">
                        <option value="">Selecione...</option>
                        @foreach($municipios as $municipio)
                            <option value="{{ $municipio->id }}">{{ $municipio->nome }}</option>
                        @endforeach
                    </select>
                    <svg x-show="municipioTravado" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <p x-show="municipioTravado" x-cloak class="mt-1 text-xs text-gray-500">🔒 Definido pelo CEP. Para mudar, informe outro CEP.</p>
                @error('municipio_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
            </div>
        </div>
        </div>
    </div>
</div>

{{-- STEP 3: LOCAIS DE TRABALHO (em cada local: primeiro o CEP, depois nome e município) --}}
<div x-show="currentStep === 2" x-transition data-passo="2" x-data="locaisTrabalho()">
    <div class="mb-4">
        <h3 class="text-lg font-bold text-purple-900 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            Passo 3: Locais de Trabalho
        </h3>
        <p class="text-sm text-gray-500">Adicione os locais onde o profissional atua (opcional). Comece pelo CEP de cada local.</p>
    </div>

    <div class="space-y-4">
        <template x-for="(local, index) in locais" :key="local.chave">
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-bold text-sm text-gray-900">Local <span x-text="index + 1"></span></h4>
                    <button type="button" @click="removerLocal(index)" x-show="locais.length > 1"
                            class="text-xs text-red-600 hover:text-red-800 font-semibold">
                        ✕ Remover
                    </button>
                </div>

                {{-- CEP do local --}}
                <div class="max-w-md">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">CEP</label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input type="text" :name="'locais_trabalho[' + index + '][cep]'"
                                   x-model="local.cep"
                                   x-mask="99999-999"
                                   @input="aoDigitarCep(local)"
                                   @keydown.enter.prevent="buscarCep(local)"
                                   :readonly="local.buscando"
                                   placeholder="00000-000"
                                   inputmode="numeric"
                                   class="w-full px-3 py-1.5 text-sm border rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 bg-white"
                                   :class="local.erro ? 'border-red-300' : (local.encontrado ? 'border-green-400' : 'border-gray-300')">
                            <svg x-show="local.encontrado && !local.buscando" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <button type="button" @click="buscarCep(local)" :disabled="local.buscando"
                                class="inline-flex items-center gap-2 px-3.5 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold disabled:opacity-70 disabled:cursor-wait">
                            <svg x-show="local.buscando" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <span x-text="local.buscando ? 'Buscando...' : 'Buscar'"></span>
                        </button>
                    </div>
                    <p x-show="local.buscando" x-cloak class="mt-1.5 text-xs text-blue-600">Consultando o CEP...</p>
                    <p x-show="local.erro" x-cloak class="mt-1.5 text-xs text-red-600" x-text="local.erro"></p>
                    <button type="button" x-show="!mostrarCampos(local)" @click="preencherManual(local)" class="mt-2 text-xs font-semibold text-blue-600 hover:underline">
                        Não sei o CEP — preencher manualmente
                    </button>
                </div>

                {{-- Nome e município: depois de buscar o CEP (ou de escolher preencher manualmente) --}}
                <div x-show="mostrarCampos(local)" x-cloak x-transition class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3 mt-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nome do Local</label>
                        <input type="text" :name="'locais_trabalho[' + index + '][nome]'"
                               x-model="local.nome" :id="'nome-local-' + local.chave"
                               style="text-transform: uppercase;"
                               placeholder="Ex: HOSPITAL MUNICIPAL"
                               class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 bg-white">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Município</label>
                        <div class="relative">
                            <input type="text" :name="'locais_trabalho[' + index + '][municipio]'"
                                   x-model="local.municipio"
                                   :readonly="local.travado"
                                   style="text-transform: uppercase;"
                                   placeholder="Digite o município"
                                   class="w-full px-3 py-1.5 text-sm border rounded-lg focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500"
                                   :class="local.travado ? 'bg-gray-100 border-gray-200 text-gray-700 cursor-not-allowed pr-10' : 'bg-white border-gray-300'">
                            <svg x-show="local.travado" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <p x-show="local.travado" x-cloak class="mt-1 text-xs text-gray-500">🔒 Definido pelo CEP. Para mudar, informe outro CEP.</p>
                    </div>
                </div>
            </div>
        </template>

        <button type="button" @click="adicionarLocal()"
                class="w-full py-2 text-sm border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-blue-500 hover:text-blue-600 font-semibold transition-colors">
            + Adicionar Outro Local
        </button>
    </div>
</div>

<style>
    .carteira-scan { position: absolute; left: 0; right: 0; height: 30%; top: -30%;
        background: linear-gradient(to bottom, transparent, rgba(59, 130, 246, .35), transparent);
        border-bottom: 2px solid rgb(59 130 246); animation: carteira-scan 1.6s ease-in-out infinite; }
    @keyframes carteira-scan { 0% { top: -30%; } 100% { top: 100%; } }
</style>

<script>
// ===== Leitura de documentos (carteira do conselho e comprovante de endereço) =====
const TIPOS_ACEITOS = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
const TESSERACT = 'https://cdn.jsdelivr.net/npm/tesseract.js@5.1.1/dist/tesseract.min.js';
const PDFJS = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js';
const PDFJS_WORKER = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
const scripts = {};
const carregarScript = (src) => scripts[src] ??= new Promise((ok, falha) => {
    const s = document.createElement('script');
    s.src = src; s.onload = ok; s.onerror = () => falha(new Error('Falha ao carregar ' + src));
    document.head.appendChild(s);
});
const normalizar = (t) => (t || '').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/\s+/g, ' ').trim().toUpperCase();
const soDigitos = (t) => (t || '').replace(/\D/g, '');
// Compara nomes tolerando erros de leitura ("ABNERC RIBEIRO" ≈ "ABNER RIBEIRO")
const distancia = (a, b) => {
    const d = Array.from({ length: a.length + 1 }, (_, i) => [i, ...Array(b.length).fill(0)]);
    for (let j = 1; j <= b.length; j++) d[0][j] = j;
    for (let i = 1; i <= a.length; i++)
        for (let j = 1; j <= b.length; j++)
            d[i][j] = Math.min(d[i - 1][j] + 1, d[i][j - 1] + 1, d[i - 1][j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1));
    return d[a.length][b.length];
};
const nomesParecidos = (a, b) => {
    const partes = (t) => normalizar(t).split(' ').filter(p => p.length > 2 && !['DOS', 'DAS', 'DES'].includes(p));
    const pa = partes(a), pb = partes(b);
    if (!pa.length || !pb.length) return true;
    const parecida = (p, q) => distancia(p, q) <= (p.length > 5 ? 2 : 1);
    const iguais = pa.filter(p => pb.some(q => parecida(p, q))).length;
    return parecida(pa[0], pb[0]) && iguais >= Math.ceil(Math.min(pa.length, pb.length) / 2);
};
const tamanhoLegivel = (b) => b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';

// PDF aberto pelo pdf.js (um por arquivo)
const pdfs = new WeakMap();
const abrirPdf = async (file) => {
    if (!pdfs.has(file)) {
        pdfs.set(file, (async () => {
            await carregarScript(PDFJS);
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS_WORKER;
            return window.pdfjsLib.getDocument({ data: await file.arrayBuffer() }).promise;
        })());
    }
    return pdfs.get(file);
};
const renderizarPagina = async (pdf, n, largura) => {
    const pagina = await pdf.getPage(n);
    const base = pagina.getViewport({ scale: 1 });
    const viewport = pagina.getViewport({ scale: largura ? largura / base.width : 2 });
    const canvas = document.createElement('canvas');
    canvas.width = viewport.width;
    canvas.height = viewport.height;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    await pagina.render({ canvasContext: ctx, viewport }).promise;
    return canvas;
};

// Corta as bordas brancas: o leitor de texto não acha nada quando a carteira
// é uma foto pequena no meio de uma folha A4 escaneada.
const recortarMargens = async (fonte) => {
    const origem = fonte instanceof HTMLCanvasElement ? fonte : await createImageBitmap(fonte);
    const largura = origem.width, altura = origem.height;
    const base = document.createElement('canvas');
    base.width = largura;
    base.height = altura;
    const ctx = base.getContext('2d', { willReadFrequently: true });
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, largura, altura);
    ctx.drawImage(origem, 0, 0);

    const px = ctx.getImageData(0, 0, largura, altura).data;
    const escuro = (x, y) => { const i = (y * largura + x) * 4; return px[i] < 235 || px[i + 1] < 235 || px[i + 2] < 235; };
    const passo = Math.max(1, Math.floor(Math.min(largura, altura) / 400));
    const minimo = 8;
    let topo = -1, baixo = -1, esq = largura, dir = -1;
    for (let y = 0; y < altura; y += passo) {
        let n = 0, primeiro = -1, ultimo = -1;
        for (let x = 0; x < largura; x += passo) {
            if (escuro(x, y)) { n++; if (primeiro < 0) primeiro = x; ultimo = x; }
        }
        if (n >= minimo) {
            if (topo < 0) topo = y;
            baixo = y;
            esq = Math.min(esq, primeiro);
            dir = Math.max(dir, ultimo);
        }
    }
    if (topo < 0) return base;

    const margem = 12;
    const x0 = Math.max(0, esq - margem), y0 = Math.max(0, topo - margem);
    const w = Math.min(largura, dir + margem) - x0, h = Math.min(altura, baixo + margem) - y0;
    if (w * h > largura * altura * 0.85) return base; // já ocupa a imagem quase toda

    const recorte = document.createElement('canvas');
    recorte.width = w;
    recorte.height = h;
    recorte.getContext('2d').drawImage(base, x0, y0, w, h, 0, 0, w, h);
    return recorte;
};

// Amplia imagens pequenas: o OCR erra muito abaixo de ~1800 px de largura (ex.: "fess" no lugar de "655")
const ampliarParaOcr = (canvas, alvo = 1800) => {
    if (canvas.width >= alvo) return canvas;
    const fator = Math.min(3, alvo / canvas.width);
    const saida = document.createElement('canvas');
    saida.width = Math.round(canvas.width * fator);
    saida.height = Math.round(canvas.height * fator);
    const ctx = saida.getContext('2d');
    ctx.imageSmoothingQuality = 'high';
    ctx.drawImage(canvas, 0, 0, saida.width, saida.height);
    return saida;
};

// Texto de um arquivo: PDF com texto é lido direto; PDF escaneado ou foto passa pelo OCR.
// "qualidade" recebe a confiança do OCR (0–100) e a largura útil da imagem, para orientar o usuário.
const ocrFontes = async (fontes, aoProgredir = () => {}, qualidade = {}) => {
    await carregarScript(TESSERACT);
    let atual = 0;
    const worker = await window.Tesseract.createWorker('por', 1, {
        logger: (m) => {
            if (m.status === 'recognizing text') aoProgredir(Math.round(((atual + m.progress) / fontes.length) * 100));
        },
    });
    try {
        const textos = [];
        for (; atual < fontes.length; atual++) {
            const recorte = await recortarMargens(fontes[atual]);
            const { data } = await worker.recognize(ampliarParaOcr(recorte));
            textos.push(data.text || '');
            qualidade.ocr = true;
            qualidade.confianca = Math.min(qualidade.confianca ?? 100, Math.round(data.confidence || 0));
            // Largura só diz algo sobre a resolução em FOTO (tamanho real do arquivo). Página de PDF é
            // desenhada aqui na escala que escolhemos, então a largura do recorte não mede nitidez.
            if (!(fontes[atual] instanceof HTMLCanvasElement)) {
                qualidade.largura = Math.min(qualidade.largura ?? Infinity, recorte.width);
            }
        }
        return textos.join('\n');
    } finally {
        await worker.terminate();
    }
};
// Imagem com pouca nitidez: lida, mas com risco de a vigilância não conseguir conferir
const qualidadeBaixa = (q) => !!q.ocr && ((q.confianca ?? 100) < 60 || (q.largura ?? Infinity) < 700);
const lerTextoDoArquivo = async (file, aoProgredir, maxPaginas = 3, qualidade = {}) => {
    if (file.type !== 'application/pdf') return ocrFontes([file], aoProgredir, qualidade);
    const pdf = await abrirPdf(file);
    const total = Math.min(pdf.numPages, maxPaginas);
    let texto = '';
    for (let n = 1; n <= total; n++) {
        const conteudo = await (await pdf.getPage(n)).getTextContent();
        texto += conteudo.items.map(i => i.str).join(' ') + '\n';
    }
    if (texto.replace(/\s/g, '').length >= 15) return texto;
    const paginas = [];
    for (let n = 1; n <= total; n++) paginas.push(await renderizarPagina(pdf, n));
    return ocrFontes(paginas, aoProgredir, qualidade);
};
const mesmoArquivo = (a, b) => !!(a && b && a.name === b.name && a.size === b.size && a.lastModified === b.lastModified);
const miniaturaPdf = async (file, n = 1) => (await recortarMargens(await renderizarPagina(await abrirPdf(file), n, 900))).toDataURL('image/jpeg', 0.85);
const postarLeitura = async (url, texto) => {
    const fd = new FormData();
    fd.append('texto', texto);
    const r = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
        body: fd,
    });
    if (r.status === 429) throw new Error('Muitas leituras seguidas. Aguarde um minuto e tente de novo.');
    if (!r.ok) throw new Error('HTTP ' + r.status);
    return r.json();
};

function carteiraConselho(config) {
    const novoLado = () => ({ arquivo: null, nome: '', tamanho: '', previa: null, previa2: null, paginas: 0 });

    return {
        lados: { frente: novoLado(), verso: novoLado() },
        versoNoPdf: false,
        versoJunto: false, // frente e verso na mesma foto/página (ex.: cédula antiga aberta)
        qualidade: {},
        lendo: false,
        etapas: ['Abrindo a carteira', 'Reconhecendo o texto', 'Identificando os dados'],
        etapa: 0,
        detalhe: '',
        progresso: 0,
        resultado: null,
        erro: '',
        lido: false,
        manual: !!config.liberar, // voltou com erro de validação: mostra os campos já preenchidos
        nomeCarteira: '',
        cpfCarteira: '',
        cpfCarteiraValido: false,
        nomeFormulario: '',
        cpfFormulario: '',
        cienteIlegivel: false,

        init() {
            // Acompanha Nome e CPF (digitados ou preenchidos pelo "Para quem é o receituário?")
            ['nome', 'cpf'].forEach(nome => {
                ['input', 'change'].forEach(ev => this.campo(nome)?.addEventListener(ev, () => this.atualizarFormulario()));
            });
            this.atualizarFormulario();
        },

        get completa() {
            return !!this.lados.frente.arquivo && (!!this.lados.verso.arquivo || this.versoNoPdf || this.versoJunto);
        },
        // Os dados principais foram lidos (nº do conselho, nome e CPF válido): o documento está legível
        get dadosEssenciaisLidos() {
            return !!(this.resultado?.numero_formatado && this.nomeCarteira && this.cpfCarteiraValido);
        },
        get poucaNitidez() {
            return this.lido && !this.lendo && !this.dadosEssenciaisLidos && qualidadeBaixa(this.qualidade);
        },
        // Carteira não identificada (a leitura falhou ou não achou nem o nº do conselho nem o nome):
        // exige arquivo melhor ou a ciência do usuário. CPF duvidoso sozinho não conta (tem o selo próprio).
        get precisaCiencia() {
            if (!this.lido || this.lendo || !this.lados.frente.arquivo) return false;
            return !!this.erro || !(this.resultado?.numero_formatado || this.nomeCarteira);
        },

        // "A frente e o verso estão no mesmo arquivo"
        marcarVersoJunto(valor = true) {
            this.versoJunto = valor;
            if (valor) {
                this.lados.verso = novoLado();
                const entrada = document.getElementById('carteira_verso');
                if (entrada) entrada.value = '';
                if (this.completa) this.lerTudo();
            } else {
                this.resultado = null;
            }
        },
        get faltaVerso() {
            return !!this.lados.frente.arquivo && !this.completa;
        },
        get liberado() {
            return this.lido || this.manual;
        },
        get progressoTotal() {
            return Math.min(100, Math.round([0, 10, 85][this.etapa] + (this.etapa === 1 ? this.progresso * 0.75 : 0) + (this.etapa === 2 ? 10 : 0)));
        },

        formatarCpf(cpf) {
            const d = soDigitos(cpf);
            return d.length === 11 ? d.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4') : (cpf || '');
        },

        campo(nome) {
            return this.$root.closest('form')?.querySelector(`[name="${nome}"]`);
        },

        atualizarFormulario() {
            this.nomeFormulario = (this.campo('nome')?.value || '').trim().toUpperCase();
            this.cpfFormulario = soDigitos(this.campo('cpf')?.value);
        },

        get nomeDivergente() {
            return !!(this.nomeCarteira && this.nomeFormulario && !nomesParecidos(this.nomeCarteira, this.nomeFormulario));
        },

        // igual | diferente | duvida (CPF lido com erro) | null (falta um dos dois)
        get cpfStatus() {
            const a = soDigitos(this.cpfCarteira), b = this.cpfFormulario;
            if (a.length !== 11 || b.length !== 11) return null;
            if (a === b) return 'igual';
            const diferencas = [...a].filter((c, i) => c !== b[i]).length;
            if (!this.cpfCarteiraValido) return diferencas <= 1 ? 'igual' : 'duvida';
            return 'diferente';
        },

        // Motivo para não deixar avançar (vazio = pode seguir)
        bloqueio() {
            if (this.lendo) return 'Aguarde terminar a leitura da carteira.';
            if (config.obrigatoria && !this.lados.frente.arquivo) return 'Envie a carteira do conselho (CRM, CRO ou CRMV) do profissional: frente e verso.';
            if (this.faltaVerso) return 'Envie também o verso da carteira (ou um PDF com a frente e o verso).';
            if (!this.liberado) return 'Envie a carteira do conselho ou escolha preencher manualmente.';
            if (this.precisaCiencia && !this.cienteIlegivel) {
                return 'A carteira não foi identificada: envie um arquivo mais nítido ou marque que está ciente de que o cadastro poderá ser rejeitado.';
            }
            if (this.cpfStatus === 'diferente') {
                return `O CPF da carteira (${this.formatarCpf(this.cpfCarteira)}) é diferente do CPF do profissional (${this.formatarCpf(this.cpfFormulario)}). Envie a carteira do profissional certo.`;
            }
            // Cadastro do próprio profissional (área da empresa): a carteira tem que estar no nome dele
            if (this.campo('solicitante')?.value === 'proprio' && this.nomeDivergente && this.cpfStatus !== 'igual') {
                return `A carteira está no nome de ${this.nomeCarteira}, e não no seu (${this.nomeFormulario}). Envie a sua carteira do conselho.`;
            }
            return '';
        },

        async escolherArquivo(lado, evento) {
            const file = evento.target.files[0];
            if (!file) return;
            this.erro = '';
            if (!TIPOS_ACEITOS.includes(file.type)) {
                this.erro = 'Envie a carteira em PDF ou imagem (JPG, PNG ou WEBP).';
                evento.target.value = '';
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                this.erro = 'O arquivo deve ter no máximo 10 MB.';
                evento.target.value = '';
                return;
            }

            // Mesmo arquivo nos dois lados: é frente e verso juntos
            if (lado === 'verso' && mesmoArquivo(file, this.lados.frente.arquivo)) {
                this.marcarVersoJunto(true);
                return;
            }
            if (lado === 'frente') this.versoJunto = false;

            const anterior = this.lados[lado].previa;
            if (anterior && anterior.startsWith('blob:')) URL.revokeObjectURL(anterior);
            this.lados[lado] = { ...novoLado(), arquivo: file, nome: file.name, tamanho: tamanhoLegivel(file.size) };
            if (lado === 'frente') this.versoNoPdf = false;
            this.resultado = null;
            this.nomeCarteira = '';
            this.cpfCarteira = '';
            this.cpfCarteiraValido = false;
            this.qualidade = {}; // arquivo novo: o aviso de nitidez da leitura anterior não vale mais
            this.cienteIlegivel = false;

            try {
                await this.prepararPrevia(lado, file);
            } catch (e) {
                console.warn('Prévia da carteira:', e);
            }
            if (this.completa) this.lerTudo();
        },

        async prepararPrevia(lado, file) {
            const l = this.lados[lado];
            if (file.type.startsWith('image/')) {
                l.previa = URL.createObjectURL(file);
                return;
            }
            const pdf = await abrirPdf(file);
            l.paginas = pdf.numPages;
            l.previa = await miniaturaPdf(file, 1);
            // Frente e verso no mesmo PDF
            if (lado === 'frente' && pdf.numPages >= 2) {
                l.previa2 = await miniaturaPdf(file, 2);
                this.versoNoPdf = true;
            }
        },

        async lerTudo() {
            if (!this.completa || this.lendo) return;
            this.lendo = true;
            this.erro = '';
            this.resultado = null;
            this.etapa = 0;
            this.progresso = 0;
            this.detalhe = '';
            this.qualidade = {};
            this.cienteIlegivel = false;

            try {
                const arquivos = [['Frente', this.lados.frente.arquivo], ['Verso', this.lados.verso.arquivo]].filter(([, f]) => f);
                await new Promise(r => setTimeout(r, 250));
                this.etapa = 1;
                const textos = [];
                for (const [rotulo, file] of arquivos) {
                    this.detalhe = arquivos.length > 1 ? rotulo : '';
                    textos.push(await this.textoDoArquivo(file));
                }
                this.etapa = 2;
                this.detalhe = '';
                const resposta = await this.enviar({ texto: textos.join('\n\n') });

                if (resposta.success) {
                    this.aplicar(resposta);
                } else {
                    this.erro = 'Não conseguimos ler os dados desta carteira.';
                }
            } catch (e) {
                console.error('Leitura da carteira:', e);
                this.erro = e.message && e.message.startsWith('Muitas')
                    ? e.message
                    : 'Não foi possível ler a carteira agora. Preencha os campos abaixo manualmente — a carteira continua anexada ao cadastro.';
            } finally {
                this.lendo = false;
                this.lido = true;
            }
        },

        // PDF com texto: lê direto. PDF escaneado ou foto: reconhecimento de texto (OCR).
        textoDoArquivo(file) {
            return lerTextoDoArquivo(file, (p) => { this.progresso = p; }, 3, this.qualidade);
        },

        enviar(dados) {
            return postarLeitura(config.url, dados.texto);
        },

        // Preenche os campos do formulário com o que foi lido
        aplicar(resposta) {
            const d = resposta.dados || {};
            this.resultado = { ...d, origem: resposta.origem };
            this.nomeCarteira = d.nome || '';
            this.cpfCarteira = d.cpf || '';
            this.cpfCarteiraValido = !!d.cpf_valido;

            const numero = this.campo('numero_conselho_classe') || this.campo('numero_crm');
            if (numero && d.numero_formatado) this.preencher(numero, d.numero_formatado);

            const especialidade = this.campo('especialidade');
            if (especialidade && d.especialidade) {
                this.preencher(especialidade, d.especialidade);
                especialidade.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // Em nome de outro profissional: nome e CPF vazios vêm da carteira
            this.usarDadosDaCarteira(false, true);
            this.atualizarFormulario();
        },

        preencher(el, valor) {
            el.value = valor;
            el.classList.add('!border-emerald-400', '!bg-emerald-50');
        },

        usarNomeDaCarteira() {
            const nome = this.campo('nome');
            if (!nome || nome.readOnly || !this.nomeCarteira) return;
            this.preencher(nome, this.nomeCarteira);
            this.atualizarFormulario();
        },

        // forcar: troca mesmo o que já foi digitado; soVazios: só preenche campos em branco
        usarDadosDaCarteira(forcar = false, soVazios = false) {
            const nome = this.campo('nome'), cpf = this.campo('cpf');
            if (nome && !nome.readOnly && this.nomeCarteira && (!soVazios || !nome.value.trim())) {
                this.preencher(nome, this.nomeCarteira);
            }
            if (cpf && !cpf.readOnly && this.cpfCarteiraValido && (forcar || !soDigitos(cpf.value))) {
                this.preencher(cpf, this.formatarCpf(this.cpfCarteira));
            }
            this.atualizarFormulario();
        },
    };
}

// Comprovante de endereço: lê titular, CEP e endereço; se não estiver no nome do profissional, pede a declaração
function comprovanteEndereco(config) {
    return {
        arquivo: null,
        nomeArquivo: '',
        tamanho: '',
        previa: null,
        lendo: false,
        etapas: ['Abrindo o comprovante', 'Reconhecendo o texto', 'Identificando os dados'],
        etapa: 0,
        progresso: 0,
        resultado: null,
        erro: '',
        lido: false,
        nomeProfissional: '',
        qualidade: {},
        situacaoTitular: @js(old('comprovante_titular', '')), // proprio | terceiro (quando o titular não foi lido)
        titularInformado: @js(old('comprovante_titular_nome', '')),
        vinculo: @js(old('declaracao_endereco_vinculo', '')),
        aceite: false,
        declaracaoPopupAberto: false,
        cienteIlegivel: false,

        init() {
            // Nome do profissional vem do Passo 1
            const nome = this.$root.closest('form')?.querySelector('[name="nome"]');
            const atualizar = () => { this.nomeProfissional = (nome?.value || '').trim().toUpperCase(); };
            ['input', 'change'].forEach(ev => nome?.addEventListener(ev, atualizar));
            atualizar();
            this.$watch('currentStep', atualizar);
            this.$watch('declaracaoPopupAberto', aberto => {
                if (!aberto) return;
                this.$nextTick(() => {
                    const campo = this.situacao === 'desconhecido' ? this.$refs.titularComprovante : this.$refs.vinculoDeclaracao;
                    campo?.focus();
                });
            });
        },

        get progressoTotal() {
            return Math.min(100, Math.round([0, 10, 85][this.etapa] + (this.etapa === 1 ? this.progresso * 0.75 : 0) + (this.etapa === 2 ? 10 : 0)));
        },
        get titular() {
            return (this.resultado?.titular || '').trim();
        },
        // Titular e CEP (ou endereço) lidos: o comprovante está legível
        get dadosEssenciaisLidos() {
            return !!(this.titular && (this.resultado?.cep || this.resultado?.endereco));
        },
        get poucaNitidez() {
            return this.lido && !this.lendo && !this.dadosEssenciaisLidos && qualidadeBaixa(this.qualidade);
        },
        // Comprovante não identificado (a leitura falhou ou não achou titular, CEP nem endereço):
        // exige arquivo melhor ou a ciência do usuário
        get precisaCiencia() {
            if (!this.lido || this.lendo || !this.arquivo) return false;
            return !!this.erro || !(this.titular || this.resultado?.cep || this.resultado?.endereco);
        },
        // confere | diferente | desconhecido (sem titular lido) | null (sem leitura)
        get situacao() {
            if (!this.lido) return null;
            if (!this.titular) return 'desconhecido';
            if (!this.nomeProfissional) return 'confere';
            return nomesParecidos(this.titular, this.nomeProfissional) ? 'confere' : 'diferente';
        },
        get precisaDeclaracao() {
            return this.situacao === 'diferente' || (this.situacao === 'desconhecido' && this.situacaoTitular === 'terceiro');
        },
        get titularDeclarado() {
            return (this.titular || this.titularInformado || '').trim().toUpperCase();
        },
        get rotuloVinculo() {
            return config.vinculos[this.vinculo] || '';
        },
        get textoDeclaracao() {
            const vinculo = (this.rotuloVinculo || '…').toLowerCase();
            return `Declaro, sob as penas da lei (art. 299 do Código Penal), que ${this.nomeProfissional || 'o profissional'} reside ou trabalha no endereço informado nesta solicitação `
                + `e que o comprovante de endereço apresentado, em nome de ${this.titularDeclarado || '…'} (vínculo: ${vinculo}), é verdadeiro e corresponde a esse endereço.`;
        },

        // Motivo para não deixar avançar (vazio = pode seguir)
        bloqueio() {
            if (this.lendo) return 'Aguarde terminar a leitura do comprovante de endereço.';
            if (config.obrigatorio && !this.arquivo) return 'Envie o comprovante de endereço (conta de água, energia ou telefone fixo).';
            if (!this.arquivo) return '';
            if (!this.lido) return 'Aguarde terminar a leitura do comprovante de endereço.';
            if (this.precisaCiencia && !this.cienteIlegivel) {
                return 'O comprovante não foi identificado: envie um arquivo mais nítido ou marque que está ciente de que o cadastro poderá ser rejeitado.';
            }
            if (this.situacao === 'desconhecido' && !this.situacaoTitular) return 'Informe se o comprovante de endereço está no nome do profissional.';
            if (this.precisaDeclaracao) {
                if (!this.titularDeclarado) return 'Informe o nome de quem está no comprovante de endereço.';
                if (!this.vinculo) return 'Informe o vínculo do profissional com o titular do comprovante de endereço.';
                if (!this.aceite) return 'O comprovante não está no nome do profissional: leia e aceite a declaração para continuar.';
            }
            return '';
        },

        async escolherArquivo(evento) {
            const file = evento.target.files[0];
            if (!file) return;
            this.erro = '';
            if (!['application/pdf', 'image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                this.erro = 'Envie o comprovante em PDF ou imagem (JPG, PNG ou WEBP).';
                evento.target.value = '';
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                this.erro = 'O arquivo deve ter no máximo 10 MB.';
                evento.target.value = '';
                return;
            }

            if (this.previa && this.previa.startsWith('blob:')) URL.revokeObjectURL(this.previa);
            this.arquivo = file;
            this.nomeArquivo = file.name;
            this.tamanho = tamanhoLegivel(file.size);
            this.previa = null;
            this.resultado = null;
            this.lido = false;
            this.aceite = false;
            this.situacaoTitular = '';
            this.titularInformado = '';
            this.vinculo = '';
            this.declaracaoPopupAberto = false;
            this.cienteIlegivel = false;

            // Prévia em paralelo: a leitura não espera por ela
            if (file.type.startsWith('image/')) {
                this.previa = URL.createObjectURL(file);
            } else {
                miniaturaPdf(file).then(p => { if (this.arquivo === file) this.previa = p; }).catch(e => console.warn('Prévia do comprovante:', e));
            }
            this.ler();
        },

        async ler() {
            if (!this.arquivo || this.lendo) return;
            this.lendo = true;
            this.erro = '';
            this.resultado = null;
            this.etapa = 0;
            this.progresso = 0;

            try {
                await new Promise(r => setTimeout(r, 250));
                this.etapa = 1;
                this.qualidade = {};
                this.cienteIlegivel = false;
                const texto = await lerTextoDoArquivo(this.arquivo, (p) => { this.progresso = p; }, 2, this.qualidade);
                this.etapa = 2;
                const resposta = await postarLeitura(config.url, texto);
                if (resposta.success) {
                    this.resultado = { ...resposta.dados, origem: resposta.origem };
                    // Preenche CEP e endereço lá embaixo
                    this.$dispatch('comprovante-lido', this.resultado);
                } else {
                    this.erro = 'Não conseguimos ler os dados deste comprovante.';
                }
            } catch (e) {
                console.error('Leitura do comprovante:', e);
                this.erro = e.message && e.message.startsWith('Muitas')
                    ? e.message
                    : 'Não foi possível ler o comprovante agora. Preencha o endereço abaixo manualmente — o comprovante continua anexado.';
            } finally {
                this.lendo = false;
                this.lido = true;
                if (this.precisaDeclaracao) this.declaracaoPopupAberto = true;
                this.$dispatch('comprovante-liberar');
            }
        },
    };
}

function cepLookup(config = {}) {
    const municipios = @json($municipios->map(fn ($m) => ['id' => $m->id, 'nome' => $m->nome, 'ibge' => $m->codigo_ibge ?? null])->values());
    const normalizar = (t) => (t || '').normalize('NFD').replace(/[̀-ͯ]/g, '').trim().toUpperCase();
    const enderecoAntigo = @js(old('endereco') ?? old('endereco_residencial') ?? '');
    const municipioAntigo = @js((string) old('municipio_id', ''));

    return {
        cep: @js(old('cep', '')),
        endereco: enderecoAntigo,
        municipioId: municipioAntigo,
        buscando: false,
        erroCep: '',
        encontrado: false,
        manual: !!(enderecoAntigo || municipioAntigo), // voltou com erro de validação: mostra o que já foi digitado
        municipioTravado: false,
        ultimoCepBuscado: '',
        // Com comprovante de endereço: CEP e endereço aparecem depois da leitura
        comprovanteLiberado: !config.comprovante || !!(enderecoAntigo || municipioAntigo || @js(old('cep', ''))),
        cepComprovante: '',

        // Comprovante lido: preenche CEP (e busca município) e usa o endereço completo do comprovante
        async aoLerComprovante(dados) {
            this.comprovanteLiberado = true;
            if (dados.cep_formatado) {
                this.cepComprovante = dados.cep_formatado;
                this.cep = dados.cep_formatado;
                await this.buscarCep();
            }
            if (dados.endereco) {
                this.endereco = dados.endereco;
                if (!this.encontrado) this.manual = true;
            }
        },

        cepDiferenteDoComprovante() {
            const a = this.cep.replace(/\D/g, ''), b = this.cepComprovante.replace(/\D/g, '');
            return a.length === 8 && b.length === 8 && a !== b;
        },

        mostrarCampos() {
            return this.encontrado || this.manual;
        },

        // Busca sozinho quando o CEP fica completo
        aoDigitarCep() {
            const digitos = this.cep.replace(/\D/g, '');
            this.erroCep = '';
            if (digitos.length === 8 && digitos !== this.ultimoCepBuscado) {
                this.buscarCep();
            } else if (digitos.length < 8 && this.encontrado) {
                // Mudou o CEP: libera para nova busca
                this.encontrado = false;
                this.municipioTravado = false;
            }
        },

        preencherManual() {
            this.manual = true;
            this.municipioTravado = false;
            this.erroCep = '';
        },

        async buscarCep() {
            const cepLimpo = this.cep.replace(/\D/g, '');
            if (cepLimpo.length !== 8) {
                this.erroCep = 'Digite os 8 números do CEP.';
                return;
            }

            this.buscando = true;
            this.erroCep = '';
            this.ultimoCepBuscado = cepLimpo;

            try {
                const response = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
                const data = await response.json();

                if (data.erro) {
                    this.erroCep = 'CEP não encontrado. Confira os números ou preencha o endereço manualmente.';
                    this.encontrado = false;
                    this.municipioTravado = false;
                    return;
                }

                this.endereco = [data.logradouro, data.complemento, data.bairro].filter(Boolean).join(', ').toUpperCase();

                // Município pelo código IBGE (exato); se não houver, pelo nome sem acentos
                const municipio = municipios.find(m => m.ibge && String(m.ibge) === String(data.ibge))
                    || municipios.find(m => normalizar(m.nome) === normalizar(data.localidade));

                if (municipio) {
                    this.municipioId = String(municipio.id);
                    this.municipioTravado = true;
                } else {
                    this.municipioId = '';
                    this.municipioTravado = false;
                    this.erroCep = `O município "${data.localidade}/${data.uf}" não está na lista. Selecione o município abaixo.`;
                }

                this.encontrado = true;
                this.manual = false;
                this.$nextTick(() => this.$refs.endereco?.focus());
            } catch (error) {
                this.erroCep = 'Não foi possível consultar o CEP agora. Tente de novo ou preencha manualmente.';
            } finally {
                this.buscando = false;
            }
        }
    };
}

function locaisTrabalho() {
    const municipios = @json($municipios->map(fn ($m) => ['id' => $m->id, 'nome' => $m->nome, 'ibge' => $m->codigo_ibge ?? null])->values());
    const normalizar = (t) => (t || '').normalize('NFD').replace(/[̀-ͯ]/g, '').trim().toUpperCase();
    let proximaChave = 1;

    const novoLocal = (dados = {}) => {
        const temDados = !!(dados.nome || dados.municipio);
        return {
            chave: proximaChave++,
            cep: dados.cep || '',
            nome: dados.nome || '',
            municipio: dados.municipio || '',
            buscando: false,
            erro: '',
            encontrado: false,
            manual: temDados, // voltou com erro de validação: mostra o que já foi digitado
            travado: false,
            ultimoCepBuscado: '',
        };
    };

    const anteriores = @js(array_values(old('locais_trabalho', [])));

    return {
        locais: anteriores.length ? anteriores.map(novoLocal) : [novoLocal()],

        mostrarCampos(local) {
            return local.encontrado || local.manual;
        },

        adicionarLocal() {
            this.locais.push(novoLocal());
        },

        removerLocal(index) {
            this.locais.splice(index, 1);
        },

        // Busca sozinho quando o CEP fica completo
        aoDigitarCep(local) {
            const digitos = local.cep.replace(/\D/g, '');
            local.erro = '';
            if (digitos.length === 8 && digitos !== local.ultimoCepBuscado) {
                this.buscarCep(local);
            } else if (digitos.length < 8 && local.encontrado) {
                local.encontrado = false;
                local.travado = false;
            }
        },

        preencherManual(local) {
            local.manual = true;
            local.travado = false;
            local.erro = '';
        },

        async buscarCep(local) {
            const cepLimpo = local.cep.replace(/\D/g, '');
            if (cepLimpo.length !== 8) {
                local.erro = 'Digite os 8 números do CEP.';
                return;
            }

            local.buscando = true;
            local.erro = '';
            local.ultimoCepBuscado = cepLimpo;

            try {
                const response = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
                const data = await response.json();

                if (data.erro) {
                    local.erro = 'CEP não encontrado. Confira os números ou preencha manualmente.';
                    local.encontrado = false;
                    local.travado = false;
                    return;
                }

                // Município pelo código IBGE; se não estiver na lista, usa a cidade do CEP
                const municipio = municipios.find(m => m.ibge && String(m.ibge) === String(data.ibge))
                    || municipios.find(m => normalizar(m.nome) === normalizar(data.localidade));
                local.municipio = municipio
                    ? municipio.nome
                    : `${(data.localidade || '').toUpperCase()}${data.uf && data.uf !== 'TO' ? '/' + data.uf : ''}`;
                local.travado = !!local.municipio;
                local.encontrado = true;
                local.manual = false;

                this.$nextTick(() => document.getElementById('nome-local-' + local.chave)?.focus());
            } catch (error) {
                local.erro = 'Não foi possível consultar o CEP agora. Tente de novo ou preencha manualmente.';
            } finally {
                local.buscando = false;
            }
        }
    };
}
</script>
