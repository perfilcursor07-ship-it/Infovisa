{{-- PASSO 4: ficha cadastral gerada com os dados preenchidos → assinada (gov.br ou à mão, com carimbo) → anexada --}}
<div x-show="currentStep === 3" x-transition data-passo="3"
     x-data="{
        gerando: false,
        erroFicha: '',
        fichaGerada: false,
        arquivoNome: '',
        arquivoTamanho: '',
        async gerarFicha(acao) {
            this.gerando = true;
            this.erroFicha = '';
            try {
                const form = this.$root.closest('form');
                const dados = new FormData(form);
                ['carteira_conselho', 'carteira_conselho_verso', 'comprovante_endereco', 'documento_assinado'].forEach(c => dados.delete(c));
                const r = await fetch(@js(route('company.receituarios.ficha-previa')), {
                    method: 'POST',
                    body: dados,
                    headers: { 'Accept': 'application/pdf', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!r.ok) throw new Error('falha');
                const url = URL.createObjectURL(await r.blob());
                if (acao === 'imprimir') {
                    window.open(url, '_blank');
                } else {
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'ficha-cadastral-receituario.pdf';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                }
                this.fichaGerada = true;
            } catch (e) {
                this.erroFicha = 'Não foi possível gerar a ficha agora. Confira os dados dos passos anteriores e tente de novo.';
            }
            this.gerando = false;
        },
        escolher(evento) {
            const f = evento.target.files[0];
            this.arquivoNome = f ? f.name : '';
            this.arquivoTamanho = f ? (f.size >= 1048576 ? (f.size / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(f.size / 1024)) + ' KB') : '';
        }
     }">
    <div class="mb-4">
        <h2 class="text-base font-bold text-slate-900">Passo 4: Ficha cadastral assinada</h2>
        <p class="text-xs text-slate-500">Gere a ficha com os dados que você preencheu, assine e anexe aqui. Ela vai junto com o cadastro para a análise da Vigilância Sanitária.</p>
    </div>

    <ol class="space-y-4">
        {{-- 1. Gerar --}}
        <li class="rounded-xl border border-slate-200 p-4">
            <div class="flex items-start gap-3">
                <span class="w-7 h-7 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0">1</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-900">Baixe ou imprima a ficha cadastral</p>
                    <p class="text-xs text-slate-500 mt-0.5">O PDF sai com os dados dos passos 1 a 3. Se alterar algum dado depois, gere a ficha de novo.</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" @click="gerarFicha('baixar')" :disabled="gerando"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-60">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span x-text="gerando ? 'Gerando…' : 'Baixar ficha (PDF)'"></span>
                        </button>
                        <button type="button" @click="gerarFicha('imprimir')" :disabled="gerando"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-60">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Abrir para imprimir
                        </button>
                        <span x-show="fichaGerada" x-cloak class="self-center text-xs font-semibold text-emerald-700">✓ Ficha gerada</span>
                    </div>
                    <p x-show="erroFicha" x-cloak class="mt-2 text-xs font-medium text-red-600" x-text="erroFicha"></p>
                </div>
            </div>
        </li>

        {{-- 2. Assinar --}}
        <li class="rounded-xl border border-slate-200 p-4">
            <div class="flex items-start gap-3">
                <span class="w-7 h-7 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center flex-shrink-0">2</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-900">Assine a ficha — escolha uma das formas</p>
                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3">
                            <p class="text-sm font-semibold text-emerald-900">✍️ Assinatura digital gov.br <span class="text-[11px] font-normal">(recomendado)</span></p>
                            <ol class="mt-1.5 space-y-1 text-xs text-emerald-900 list-decimal list-inside">
                                <li>Acesse o <a href="https://assinador.iti.br" target="_blank" rel="noopener" class="font-semibold underline">assinador gov.br</a> e entre com sua conta gov.br (nível prata ou ouro).</li>
                                <li>Envie o PDF da ficha e assine.</li>
                                <li>Baixe o PDF assinado e anexe no item 3 abaixo.</li>
                            </ol>
                        </div>
                        <div class="rounded-lg bg-slate-50 border border-slate-200 p-3">
                            <p class="text-sm font-semibold text-slate-900">🖨️ Assinatura à mão, com carimbo</p>
                            <ol class="mt-1.5 space-y-1 text-xs text-slate-700 list-decimal list-inside">
                                <li>Imprima a ficha.</li>
                                <li>Assine (igual ao documento de identidade) e <strong>carimbe</strong> com seu nome e nº do conselho.</li>
                                <li>Digitalize (scanner ou app de digitalização) e anexe no item 3 abaixo.</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </li>

        {{-- 3. Anexar --}}
        <li class="rounded-xl border p-4" :class="arquivoNome ? 'border-emerald-300 bg-emerald-50/40' : 'border-slate-200'">
            <div class="flex items-start gap-3">
                <span class="w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center flex-shrink-0"
                      :class="arquivoNome ? 'bg-emerald-500 text-white' : 'bg-blue-600 text-white'" x-text="arquivoNome ? '✓' : '3'"></span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-900">Anexe a ficha assinada <span class="text-red-500">*</span></p>
                    <p class="text-xs text-slate-500 mt-0.5">PDF ou imagem (JPG, PNG), até 10 MB. A ficha precisa estar legível e com a assinatura visível.</p>
                    <label class="mt-3 flex items-center gap-3 rounded-lg border-2 border-dashed px-4 py-3 cursor-pointer transition"
                           :class="arquivoNome ? 'border-emerald-300 bg-white' : 'border-slate-300 hover:border-blue-400 hover:bg-blue-50/40'">
                        <input type="file" name="documento_assinado" accept="application/pdf,image/jpeg,image/png,image/webp" required class="sr-only" @change="escolher($event)">
                        <svg class="w-6 h-6 flex-shrink-0" :class="arquivoNome ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold truncate" :class="arquivoNome ? 'text-emerald-800' : 'text-slate-700'" x-text="arquivoNome || 'Clique para escolher o arquivo'"></span>
                            <span class="block text-xs text-slate-500" x-text="arquivoNome ? arquivoTamanho + ' · clique para trocar' : 'Ficha assinada pelo gov.br ou assinada e carimbada'"></span>
                        </span>
                    </label>
                    @error('documento_assinado')<p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </li>
    </ol>
</div>
