{{-- Modal "Tempo por etapa": resumo, linha do tempo, tempo de cada documento obrigatório e tempo em cada setor --}}
<div x-data="linhaTempoProcesso(@js(route('admin.estabelecimentos.processos.linha-tempo', [$estabelecimento->id, $processo->id])))"
     @abrir-linha-tempo.window="abrir()" @keydown.escape.window="aberto = false">
    <template x-teleport="body">
        <div x-show="aberto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="aberto = false"></div>

            <div class="relative w-full max-w-4xl max-h-[92vh] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden">
                {{-- Cabeçalho --}}
                <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h3 class="text-base font-bold text-slate-900 truncate">Tempo por etapa <span class="text-slate-400 font-medium" x-text="dados ? '· ' + dados.numero : ''"></span></h3>
                            <p class="text-xs text-slate-500" x-show="dados">
                                <span x-text="dados?.em_andamento ? 'Em andamento há ' + dados?.total : 'Durou ' + dados?.total + ' até o arquivamento'"></span>
                                <template x-if="dados?.parado">
                                    <span class="ml-1 px-1.5 py-0.5 rounded bg-red-50 text-red-700 font-semibold" x-text="'parado por ' + dados.parado"></span>
                                </template>
                            </p>
                        </div>
                    </div>
                    <button @click="aberto = false" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg" title="Fechar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-5">
                    <div x-show="carregando" class="py-12 text-center text-slate-400">
                        <svg class="w-6 h-6 mx-auto animate-spin mb-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <p class="text-xs">Calculando os tempos...</p>
                    </div>
                    <p x-show="erro" class="py-10 text-center text-sm text-red-600">Não foi possível calcular a linha do tempo deste processo.</p>

                    <template x-if="dados && !carregando">
                        <div class="space-y-6">
                            {{-- 1. Resumo --}}
                            <section class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <p class="text-[11px] font-medium text-slate-500">Duração total</p>
                                    <p class="mt-0.5 text-lg font-bold text-slate-900" x-text="dados.total"></p>
                                    <p class="text-[11px] text-slate-400" x-text="dados.em_andamento ? 'desde a abertura' : 'até o arquivamento'"></p>
                                </div>
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <p class="text-[11px] font-medium text-slate-500">Empresa enviou tudo em</p>
                                    <p class="mt-0.5 text-lg font-bold"
                                       :class="dados.resumo.envio_completo ? 'text-slate-900' : 'text-amber-600'"
                                       x-text="dados.resumo.docs_total === 0 ? '—' : (dados.resumo.envio_completo ?? (dados.resumo.docs_enviados + ' de ' + dados.resumo.docs_total))"></p>
                                    <p class="text-[11px] text-slate-400"
                                       x-text="dados.resumo.docs_total === 0 ? 'sem documentos obrigatórios' : (dados.resumo.envio_completo ? 'da abertura ao último obrigatório' : 'obrigatórios enviados até agora')"></p>
                                </div>
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <p class="text-[11px] font-medium text-slate-500">Aprovação por documento</p>
                                    <p class="mt-0.5 text-lg font-bold text-slate-900" x-text="dados.resumo.media_aprovacao ?? '—'"></p>
                                    <p class="text-[11px] text-slate-400 truncate"
                                       :title="dados.resumo.maior_aprovacao ? 'Mais demorado: ' + dados.resumo.maior_aprovacao : ''"
                                       x-text="dados.resumo.media_aprovacao ? 'média do envio à verificação' : 'nenhum verificado ainda'"></p>
                                </div>
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <p class="text-[11px] font-medium text-slate-500">Documentos obrigatórios</p>
                                    <p class="mt-0.5 text-lg font-bold text-slate-900">
                                        <span x-text="dados.resumo.docs_aprovados"></span><span class="text-slate-400 font-semibold" x-text="'/' + dados.resumo.docs_total"></span>
                                        <span class="text-xs font-medium text-slate-500">verificados</span>
                                    </p>
                                    {{-- mini gráfico: aprovados / enviados / faltando --}}
                                    <div class="mt-1 flex h-1.5 rounded-full overflow-hidden bg-slate-100" x-show="dados.resumo.docs_total > 0">
                                        <div class="bg-emerald-500" :style="`width: ${dados.resumo.docs_aprovados * 100 / dados.resumo.docs_total}%`"></div>
                                        <div class="bg-blue-400" :style="`width: ${(dados.resumo.docs_enviados - dados.resumo.docs_aprovados) * 100 / dados.resumo.docs_total}%`"></div>
                                    </div>
                                    <p class="mt-1 text-[11px]" :class="dados.resumo.rejeicoes ? 'text-red-600' : 'text-slate-400'"
                                       x-text="dados.resumo.rejeicoes ? dados.resumo.rejeicoes + (dados.resumo.rejeicoes === 1 ? ' rejeição' : ' rejeições') : 'nenhuma rejeição'"></p>
                                </div>
                            </section>

                            {{-- 2. Linha do tempo --}}
                            <section>
                                <h4 class="text-sm font-bold text-slate-900 mb-2">Linha do tempo</h4>

                                {{-- Barra proporcional --}}
                                <div class="flex h-2.5 rounded-full overflow-hidden bg-slate-100 mb-3">
                                    <template x-for="(etapa, i) in dados.etapas" :key="i">
                                        <div class="h-full border-r border-white last:border-0" :class="cor(etapa.de).barra" :style="`width: ${Math.max(etapa.percentual, 1.5)}%`" :title="etapa.titulo + ': ' + etapa.duracao"></div>
                                    </template>
                                </div>

                                <ol class="rounded-xl border border-slate-200 divide-y divide-slate-100">
                                    <template x-for="(marco, i) in dados.marcos" :key="marco.chave">
                                        <li class="flex items-center gap-3 px-3 py-2">
                                            <span class="w-7 h-7 flex-shrink-0 rounded-full bg-white ring-2 flex items-center justify-center text-xs" :class="cor(marco.chave).anel" x-text="marco.icone"></span>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[13px] font-semibold text-slate-900 truncate" x-text="marco.titulo"></p>
                                                <p class="text-[11px] text-slate-500" x-text="marco.data"></p>
                                            </div>
                                            {{-- tempo até o próximo marco --}}
                                            <template x-if="dados.etapas[i]">
                                                <div class="text-right flex-shrink-0 max-w-[45%]">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold" :class="cor(dados.etapas[i].de).fundo">
                                                        <span x-text="'⏳ ' + dados.etapas[i].duracao"></span>
                                                        <span x-show="dados.etapas[i].em_andamento" class="font-semibold opacity-70">· até hoje</span>
                                                    </span>
                                                    <p class="text-[10px] text-slate-400 mt-0.5 truncate" :title="dados.etapas[i].descricao" x-text="dados.etapas[i].descricao"></p>
                                                </div>
                                            </template>
                                        </li>
                                    </template>
                                    <template x-for="f in dados.faltando" :key="f">
                                        <li class="flex items-center gap-3 px-3 py-2 opacity-60">
                                            <span class="w-7 h-7 flex-shrink-0 rounded-full border-2 border-dashed border-slate-300"></span>
                                            <p class="text-[13px] text-slate-500" x-text="f"></p>
                                            <span class="ml-auto text-[10px] font-semibold uppercase tracking-wide text-slate-400">ainda não aconteceu</span>
                                        </li>
                                    </template>
                                </ol>
                            </section>

                            {{-- 3. Documentos obrigatórios --}}
                            <section>
                                <div class="flex items-baseline justify-between gap-2 mb-2">
                                    <h4 class="text-sm font-bold text-slate-900">Documentos obrigatórios</h4>
                                    <span class="text-[11px] text-slate-400">tempo do 1º envio até a aprovação</span>
                                </div>
                                <p x-show="!dados.documentos.length" class="text-xs text-slate-400 rounded-xl border border-dashed border-slate-200 px-3 py-4 text-center">
                                    Nenhum documento obrigatório configurado para este processo.
                                </p>
                                <ul x-show="dados.documentos.length" class="rounded-xl border border-slate-200 divide-y divide-slate-100">
                                    <template x-for="(doc, i) in dados.documentos" :key="i">
                                        <li class="px-3 py-2.5 grid grid-cols-1 sm:grid-cols-[1fr_190px] gap-x-4 gap-y-1.5 items-center">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full flex-shrink-0" :class="corDoc(doc.situacao.chave).ponto"></span>
                                                    <p class="text-[13px] font-semibold text-slate-900 truncate" :title="doc.nome" x-text="doc.nome"></p>
                                                    <span x-show="doc.rejeicoes" class="flex-shrink-0 px-1.5 py-0.5 rounded bg-red-50 text-red-700 text-[10px] font-bold"
                                                          x-text="doc.rejeicoes + (doc.rejeicoes === 1 ? ' rejeição' : ' rejeições')"></span>
                                                </div>
                                                <p class="text-[11px] text-slate-500 mt-0.5 pl-4">
                                                    <template x-if="doc.enviado">
                                                        <span>Enviado em <strong class="font-medium text-slate-700" x-text="doc.enviado"></strong> <span class="text-slate-400" x-text="'(' + doc.apos_abertura + ' após a abertura)'"></span></span>
                                                    </template>
                                                    <template x-if="doc.aprovado">
                                                        <span> · Verificado em <strong class="font-medium text-slate-700" x-text="doc.aprovado"></strong></span>
                                                    </template>
                                                    <template x-if="!doc.aprovado">
                                                        <span :class="corDoc(doc.situacao.chave).texto" x-text="(doc.enviado ? ' · ' : '') + doc.situacao.texto"></span>
                                                    </template>
                                                </p>
                                            </div>
                                            {{-- mini barra do tempo --}}
                                            <div class="pl-4 sm:pl-0">
                                                <div class="flex items-center justify-between text-[11px] mb-0.5">
                                                    <span class="text-slate-400" x-text="doc.ate_aprovar ? 'até aprovar' : (doc.situacao.chave === 'nao_enviado' ? '' : 'na situação atual')"></span>
                                                    <span class="font-bold text-slate-800" x-text="doc.ate_aprovar ?? doc.na_situacao ?? ''"></span>
                                                </div>
                                                <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden" x-show="doc.situacao.chave !== 'nao_enviado'">
                                                    <div class="h-full rounded-full" :class="corDoc(doc.situacao.chave).barra" :style="`width: ${Math.max(doc.percentual, 3)}%`"></div>
                                                </div>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                            </section>

                            {{-- 4. Setores --}}
                            <section>
                                <div class="flex items-baseline justify-between gap-2 mb-2">
                                    <h4 class="text-sm font-bold text-slate-900">Tempo em cada setor</h4>
                                    <span class="text-[11px] text-slate-400">fora os períodos arquivado</span>
                                </div>
                                <p x-show="!dados.setores.length" class="text-xs text-slate-400">Sem registros de tramitação.</p>
                                <div class="space-y-2">
                                    <template x-for="(s, i) in dados.setores" :key="i">
                                        <div class="grid grid-cols-[minmax(0,1fr)_auto] sm:grid-cols-[220px_minmax(0,1fr)_auto] items-center gap-x-3 gap-y-1">
                                            <span class="text-xs font-medium text-slate-700 truncate" :title="s.nome">
                                                <span x-text="s.nome"></span>
                                                <span x-show="s.atual" class="ml-1 px-1 py-px rounded bg-blue-50 text-blue-700 text-[9px] font-bold align-middle">AGORA</span>
                                            </span>
                                            <div class="order-3 sm:order-none col-span-2 sm:col-span-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                                <div class="h-full rounded-full" :class="s.atual ? 'bg-blue-500' : 'bg-slate-400'" :style="`width: ${Math.max(s.percentual, 2)}%`"></div>
                                            </div>
                                            <span class="text-xs font-bold text-slate-900 whitespace-nowrap">
                                                <span x-text="s.duracao"></span>
                                                <span x-show="s.passagens > 1" class="text-slate-400 font-normal" x-text="'· ' + s.passagens + 'x'"></span>
                                            </span>
                                        </div>
                                    </template>
                                </div>

                                {{-- Caminho da tramitação --}}
                                <div x-show="dados.trajeto.length > 1" class="mt-3 flex flex-wrap items-center gap-1.5 text-[11px]">
                                    <span class="text-slate-400 mr-0.5">Caminho:</span>
                                    <template x-for="(t, i) in dados.trajeto" :key="i">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded-full ring-1"
                                                  :class="t.arquivado ? 'bg-slate-50 ring-slate-200 text-slate-400' : (t.atual ? 'bg-blue-50 ring-blue-200 text-blue-700 font-semibold' : 'bg-white ring-slate-200 text-slate-600')"
                                                  :title="t.periodo + (t.responsaveis.length ? ' · ' + t.responsaveis.join(', ') : '')"
                                                  x-text="t.nome + ' · ' + t.duracao"></span>
                                            <svg x-show="i < dados.trajeto.length - 1" class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </span>
                                    </template>
                                </div>
                            </section>
                        </div>
                    </template>
                </div>

                <div class="px-5 py-2.5 border-t border-slate-100 bg-slate-50 text-[11px] text-slate-500">
                    Calculado a partir da abertura, dos documentos enviados pela empresa (envios, rejeições e aprovações), do Alvará Sanitário assinado e do histórico de tramitação.
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function linhaTempoProcesso(url) {
    const CORES = {
        abertura: { barra: 'bg-amber-400', fundo: 'bg-amber-50 text-amber-900', anel: 'ring-amber-300' },
        primeiro_envio: { barra: 'bg-blue-500', fundo: 'bg-blue-50 text-blue-900', anel: 'ring-blue-300' },
        todos_enviados: { barra: 'bg-indigo-500', fundo: 'bg-indigo-50 text-indigo-900', anel: 'ring-indigo-300' },
        doc_completa: { barra: 'bg-violet-500', fundo: 'bg-violet-50 text-violet-900', anel: 'ring-violet-300' },
        alvara_provisorio: { barra: 'bg-sky-400', fundo: 'bg-sky-50 text-sky-900', anel: 'ring-sky-300' },
        alvara: { barra: 'bg-emerald-500', fundo: 'bg-emerald-50 text-emerald-900', anel: 'ring-emerald-300' },
        arquivamento: { barra: 'bg-slate-400', fundo: 'bg-slate-100 text-slate-700', anel: 'ring-slate-300' },
    };
    const CORES_DOC = {
        aprovado: { ponto: 'bg-emerald-500', barra: 'bg-emerald-500', texto: 'text-emerald-700' },
        pendente: { ponto: 'bg-blue-500', barra: 'bg-blue-400', texto: 'text-blue-700' },
        rejeitado: { ponto: 'bg-red-500', barra: 'bg-red-400', texto: 'text-red-700' },
        nao_enviado: { ponto: 'bg-slate-300', barra: 'bg-slate-300', texto: 'text-slate-500' },
    };

    return {
        aberto: false,
        carregando: false,
        erro: false,
        dados: null,

        cor(chave) { return CORES[chave] || CORES.arquivamento; },
        corDoc(chave) { return CORES_DOC[chave] || CORES_DOC.nao_enviado; },

        async abrir() {
            this.aberto = true;
            if (this.dados || this.carregando) return;
            this.carregando = true;
            this.erro = false;
            try {
                const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!r.ok) throw new Error('HTTP ' + r.status);
                this.dados = await r.json();
            } catch (e) {
                console.error('Linha do tempo:', e);
                this.erro = true;
            }
            this.carregando = false;
        },
    };
}
</script>
