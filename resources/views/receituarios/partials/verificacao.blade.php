{{-- Lista de documentos do cadastro (mesmo padrão da lista de arquivos do processo): cada documento é aprovado
     ou rejeitado. Rejeitado → a empresa reenvia só ele. Todos aprovados → cadastro aprovado automaticamente.
     Revalidar → o documento já analisado volta para pendente. --}}
@if($receituario->isSolicitacaoExterna())
@php
    $docs = $receituario->documentosDaAnalise();
    $situacoes = collect($docs)->mapWithKeys(fn ($d) => [$d => $receituario->statusDocumento($d)]);
    $aprovados = $situacoes->filter(fn ($s) => $s === 'aprovado')->count();
    $pendentes = $situacoes->filter(fn ($s) => $s === 'pendente')->count();
    $podeAnalisar = $receituario->status !== 'aguardando_assinatura';
    $disco = \Illuminate\Support\Facades\Storage::disk('local');
    $arquivo = fn ($rotulo, $nome, $caminho, $url) => $caminho ? [
        'rotulo' => $rotulo, 'nome' => $nome ?: basename($caminho), 'url' => $url,
        'extensao' => strtolower(pathinfo($nome ?: $caminho, PATHINFO_EXTENSION)),
        'tamanho' => $disco->exists($caminho) ? $disco->size($caminho) : null,
    ] : null;
    $arquivos = [
        'carteira' => array_values(array_filter([
            $arquivo('Frente', $receituario->carteira_conselho_nome, $receituario->carteira_conselho_path, route('admin.receituarios.carteira', $receituario->id)),
            $arquivo('Verso', $receituario->carteira_conselho_verso_nome, $receituario->carteira_conselho_verso_path, route('admin.receituarios.carteira', [$receituario->id, 'verso'])),
        ])),
        'comprovante' => array_values(array_filter([
            $arquivo('Comprovante', $receituario->comprovante_endereco_nome, $receituario->comprovante_endereco_path, route('admin.receituarios.comprovante', $receituario->id)),
        ])),
        'assinado' => array_values(array_filter([
            $arquivo('Ficha assinada', $receituario->documento_assinado_nome, $receituario->documento_assinado_path, route('admin.receituarios.documento-assinado', $receituario->id)),
        ])),
    ];
    $tamanho = function (?int $bytes) {
        if (!$bytes) return null;
        return $bytes >= 1048576 ? number_format($bytes / 1048576, 2, ',', '.') . ' MB' : number_format($bytes / 1024, 2, ',', '.') . ' KB';
    };
    $leituraCarteira = $receituario->carteira_conselho_leitura ?? [];
    $leituraComprovante = $receituario->comprovante_endereco_leitura ?? [];
    $declaracao = $receituario->declaracao_endereco;
    $usuariosAnalise = \App\Models\UsuarioInterno::whereIn('id', collect($receituario->analise_documentos ?? [])->pluck('analisado_por')->filter()->unique())->pluck('nome', 'id');
    $barra = ['aprovado' => 'border-l-green-500', 'rejeitado' => 'border-l-red-500', 'pendente' => 'border-l-orange-500', 'nao_enviado' => 'border-l-gray-300'];
    $dataHora = fn ($iso) => $iso ? \Carbon\Carbon::parse($iso)->timezone(config('app.timezone'))->format('d/m/Y H:i') : '';
    $iconeAcao = 'p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors';
    $itemMenu = 'w-full text-left px-3 py-2 text-xs sm:text-sm text-slate-600 hover:text-slate-900 hover:bg-slate-50 flex items-center gap-2';
@endphp
<div class="bg-white rounded-xl shadow-sm border border-slate-200">
    {{-- Cabeçalho da lista --}}
    <div class="px-4 py-3 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="min-w-0">
            <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                <i class="far fa-folder-open fa-fw text-slate-400" style="font-size: 14px;"></i>
                Documentos do cadastro
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">{{ count($docs) }}</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                @switch($receituario->status)
                    @case('ativo') Todos os documentos verificados{{ $receituario->analisado_em ? ' em ' . $receituario->analisado_em->format('d/m/Y H:i') : '' }}. Use <i class="fas fa-redo" style="font-size: 9px;"></i> para revalidar um documento. @break
                    @case('rejeitado') Há documento rejeitado aguardando reenvio da empresa. Os demais podem continuar sendo analisados. @break
                    @default Verifique cada documento. Quando todos forem aprovados, o cadastro é aprovado automaticamente.
                @endswitch
            </p>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            @if($pendentes)
                <span class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-orange-800 bg-orange-50 border border-orange-200 rounded-lg">
                    <i class="far fa-clock" style="font-size: 11px;"></i>
                    Pendentes <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-orange-200 text-orange-900">{{ $pendentes }}</span>
                </span>
            @endif
            <span class="inline-flex items-center gap-2 h-8 px-3 text-xs font-semibold text-slate-600 bg-slate-50 border border-slate-200 rounded-lg">
                <span class="w-16 h-1.5 rounded-full bg-slate-200 overflow-hidden"><span class="block h-full bg-green-500" style="width: {{ count($docs) ? round($aprovados * 100 / count($docs)) : 0 }}%"></span></span>
                {{ $aprovados }}/{{ count($docs) }} verificados
            </span>
        </div>
    </div>

    <div class="p-3 space-y-2">
        @foreach($docs as $doc)
            @php
                $info = \App\Models\Receituario::DOCUMENTOS[$doc];
                $situacao = $situacoes[$doc];
                $analise = $receituario->analiseDocumento($doc);
                $historico = $analise['historico'] ?? [];
                $correcoes = collect($historico)->whereNotNull('reenviado_em')->count();
                $abrirRejeicao = old('_documento') === $doc && $errors->has('motivo');
                $temArquivo = $receituario->temDocumento($doc);
                $lista = $arquivos[$doc];
                $principal = $lista[0] ?? null;
                $enviadoEm = collect($historico)->pluck('reenviado_em')->filter()->last();
                $analisadoPor = isset($analise['analisado_por'], $usuariosAnalise[$analise['analisado_por']]) ? $usuariosAnalise[$analise['analisado_por']] : null;
                $tamanhoTotal = collect($lista)->sum('tamanho');
            @endphp
            <div class="p-3 bg-white rounded-lg border border-gray-200 border-l-4 {{ $barra[$situacao] }} hover:shadow-md transition-all"
                 x-data="{ rejeitando: {{ $abrirRejeicao ? 'true' : 'false' }}, revalidando: false }">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    {{-- ESQUERDA: ícone + nome + metadados --}}
                    <a @if($principal) href="{{ $principal['url'] }}" target="_blank" rel="noopener" @endif
                       class="flex items-start gap-2 min-w-0 flex-1 {{ $principal ? 'cursor-pointer' : '' }}" title="{{ $principal ? 'Abrir ' . $principal['nome'] : '' }}">
                        <div class="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                            @if(($principal['extensao'] ?? '') === 'pdf')
                                <i class="far fa-file-pdf fa-fw text-slate-500" style="font-size: 16px;"></i>
                            @elseif(in_array($principal['extensao'] ?? '', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true))
                                <i class="far fa-file-image fa-fw text-slate-500" style="font-size: 16px;"></i>
                            @else
                                <i class="fas fa-paperclip fa-fw text-slate-500" style="font-size: 16px;"></i>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs sm:text-sm font-semibold text-slate-900 hover:text-blue-600 break-words leading-tight">
                                {{ $info['nome'] }}
                                @if(count($lista) > 1)<span class="font-normal text-slate-400">· frente e verso</span>@endif
                            </p>
                            <div class="flex items-center gap-1.5 flex-wrap mt-1">
                                <span class="text-[10px] sm:text-[11px] text-slate-400" title="Enviado em {{ $receituario->created_at->format('d/m/Y H:i') }}">{{ $receituario->created_at->format('d/m/Y') }}</span>
                                @if($enviadoEm)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-amber-50 text-amber-700 text-[10px] rounded font-bold" title="Arquivo reenviado pela empresa">
                                        <i class="fas fa-redo" style="font-size: 9px;"></i>
                                        Reenviado {{ $dataHora($enviadoEm) }}
                                    </span>
                                @endif
                                @if($tamanhoTotal)
                                    <span class="text-[11px] sm:text-xs text-slate-500">{{ $tamanho($tamanhoTotal) }}</span>
                                @endif
                                <span class="px-1.5 py-0.5 text-[10px] rounded bg-blue-100 text-blue-700 font-semibold">Ext</span>
                                @if($situacao === 'pendente')
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-gray-100 text-gray-600 text-[10px] rounded font-bold">
                                        <i class="far fa-clock" style="font-size: 10px;"></i>
                                        Pendente
                                    </span>
                                @elseif($situacao === 'aprovado')
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-gray-100 text-gray-600 text-[10px] rounded font-bold"
                                          title="{{ $analisadoPor ? 'Verificado por ' . $analisadoPor : 'Verificado' }}{{ !empty($analise['analisado_em']) ? ' em ' . $dataHora($analise['analisado_em']) : '' }}">
                                        <i class="fas fa-check" style="font-size: 10px;"></i>
                                        Verificado{{ $analisadoPor ? ' - ' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::words($analisadoPor, 1, '')) : '' }}
                                    </span>
                                @elseif($situacao === 'rejeitado')
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-gray-100 text-gray-600 text-[10px] rounded font-bold"
                                          title="{{ $analisadoPor ? 'Rejeitado por ' . $analisadoPor : 'Rejeitado' }}{{ !empty($analise['analisado_em']) ? ' em ' . $dataHora($analise['analisado_em']) : '' }}">
                                        <i class="fas fa-times" style="font-size: 10px;"></i>
                                        Rejeitado
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-amber-50 text-amber-700 text-[10px] rounded font-bold">Não enviado</span>
                                @endif
                                @if($correcoes)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-slate-100 text-slate-600 text-[10px] rounded font-bold">
                                        <i class="fas fa-redo" style="font-size: 10px;"></i>
                                        Correção #{{ $correcoes }}
                                    </span>
                                @endif

                                {{-- Carteira não identificada na leitura: a empresa declarou ciência do risco de rejeição --}}
                                @if($doc === 'carteira' && !empty($leituraCarteira['ciente_ilegivel']))
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-amber-50 text-amber-800 text-[10px] rounded font-bold"
                                          title="A carteira não foi identificada na leitura automática. A empresa marcou que está ciente de que o documento pode estar ilegível e de que o cadastro poderá ser rejeitado{{ !empty($leituraCarteira['ciente_ilegivel_em']) ? ' (' . \Carbon\Carbon::parse($leituraCarteira['ciente_ilegivel_em'])->format('d/m/Y H:i') . ')' : '' }}.">
                                        <i class="fas fa-exclamation-triangle" style="font-size: 9px;"></i>
                                        Documento não identificado · empresa ciente
                                    </span>
                                @endif

                                {{-- O que foi lido automaticamente, para ajudar na conferência --}}
                                @if($doc === 'carteira' && !empty($leituraCarteira) && (!empty($leituraCarteira['numero']) || !empty($leituraCarteira['cpf'])))
                                    @php $cpfConfere = !empty($leituraCarteira['cpf']) && preg_replace('/\D/', '', $leituraCarteira['cpf']) === preg_replace('/\D/', '', (string) $receituario->cpf); @endphp
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-indigo-50 text-indigo-700 text-[10px] rounded font-bold" title="Lido automaticamente{{ ($leituraCarteira['origem'] ?? '') === 'ia' ? ' (IA)' : '' }}">
                                        <i class="far fa-id-card" style="font-size: 10px;"></i>
                                        {{ trim(($leituraCarteira['conselho'] ?? '') . (!empty($leituraCarteira['uf']) ? '-' . $leituraCarteira['uf'] : '') . ' ' . ($leituraCarteira['numero'] ?? '')) ?: 'Lido' }}
                                    </span>
                                    @if(!empty($leituraCarteira['cpf']))
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] rounded font-bold {{ $cpfConfere ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                            <i class="fas {{ $cpfConfere ? 'fa-check' : 'fa-exclamation-triangle' }}" style="font-size: 9px;"></i>
                                            CPF {{ $cpfConfere ? 'confere' : 'diferente' }}
                                        </span>
                                    @endif
                                @elseif($doc === 'comprovante')
                                    @if(!empty($leituraComprovante['ciente_ilegivel']))
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-amber-50 text-amber-800 text-[10px] rounded font-bold"
                                              title="O comprovante não foi identificado na leitura automática. A empresa marcou que está ciente de que o documento pode estar ilegível e de que o cadastro poderá ser rejeitado{{ !empty($leituraComprovante['ciente_ilegivel_em']) ? ' (' . \Carbon\Carbon::parse($leituraComprovante['ciente_ilegivel_em'])->format('d/m/Y H:i') . ')' : '' }}.">
                                            <i class="fas fa-exclamation-triangle" style="font-size: 9px;"></i>
                                            Documento não identificado · empresa ciente
                                        </span>
                                    @endif
                                    @if(!empty($leituraComprovante['tipo']))
                                        <span class="px-1.5 py-0.5 bg-indigo-50 text-indigo-700 text-[10px] rounded font-bold">{{ \App\Services\LeitorComprovanteEnderecoService::TIPOS[$leituraComprovante['tipo']] ?? '' }}</span>
                                    @endif
                                    @if($declaracao)
                                        <span class="px-1.5 py-0.5 bg-violet-50 text-violet-700 text-[10px] rounded font-bold" title="Comprovante em nome de terceiro, com declaração aceita">Em nome de terceiro</span>
                                    @endif
                                @endif
                            </div>
                            @if($principal)
                                <p class="mt-1 text-[11px] text-slate-400 truncate">{{ collect($lista)->pluck('nome')->implode(' · ') }}</p>
                            @endif
                        </div>
                    </a>

                    {{-- DIREITA: ações --}}
                    <div class="flex flex-wrap items-center justify-start lg:justify-end gap-1 flex-shrink-0 w-full lg:w-auto pl-11 lg:pl-0">
                        @if($podeAnalisar && $temArquivo)
                            @if($situacao === 'pendente')
                                <form method="POST" action="{{ route('admin.receituarios.documento.analisar', [$receituario->id, $doc]) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="acao" value="aprovar">
                                    <button type="submit" class="{{ $iconeAcao }}" title="Verificar (aprovar)">
                                        <i class="fas fa-check fa-fw text-slate-500" style="font-size: 15px;"></i>
                                    </button>
                                </form>
                                <button type="button" @click="rejeitando = !rejeitando; revalidando = false" class="{{ $iconeAcao }}" title="Rejeitar">
                                    <i class="fas fa-times fa-fw text-slate-500" style="font-size: 15px;"></i>
                                </button>
                            @else
                                <button type="button" @click="revalidando = !revalidando; rejeitando = false" class="{{ $iconeAcao }}" title="Revalidar (voltar para pendente)">
                                    <i class="fas fa-redo fa-fw text-slate-500" style="font-size: 15px;"></i>
                                </button>
                            @endif
                        @endif

                        @if($principal)
                            <a href="{{ $principal['url'] }}" download="{{ $principal['nome'] }}" class="{{ $iconeAcao }}" title="Download">
                                <i class="fas fa-download fa-fw text-slate-500" style="font-size: 15px;"></i>
                            </a>
                        @endif

                        {{-- Menu 3 pontos --}}
                        <div class="relative" x-data="{ menuAberto: false }">
                            <button type="button" @click.stop="menuAberto = !menuAberto" class="{{ $iconeAcao }}" title="Mais opções">
                                <i class="fas fa-ellipsis-h fa-fw text-slate-500" style="font-size: 15px;"></i>
                            </button>
                            <div x-show="menuAberto" @click.away="menuAberto = false" x-transition
                                 class="absolute right-0 top-full mt-1 w-56 max-w-[calc(100vw-2rem)] bg-white rounded-lg shadow-xl border z-50 py-1" style="display: none;">
                                @foreach($lista as $item)
                                    <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="{{ $itemMenu }}">
                                        <i class="far fa-eye fa-fw text-slate-400" style="font-size: 13px;"></i>
                                        Abrir {{ count($lista) > 1 ? mb_strtolower($item['rotulo']) : 'arquivo' }}
                                    </a>
                                    <a href="{{ $item['url'] }}" download="{{ $item['nome'] }}" class="{{ $itemMenu }}">
                                        <i class="fas fa-download fa-fw text-slate-400" style="font-size: 13px;"></i>
                                        Baixar {{ count($lista) > 1 ? mb_strtolower($item['rotulo']) : 'arquivo' }}
                                    </a>
                                @endforeach
                                @if($podeAnalisar && $temArquivo && $situacao !== 'pendente')
                                    <hr class="my-1">
                                    <button type="button" @click="revalidando = true; rejeitando = false; menuAberto = false" class="{{ $itemMenu }}">
                                        <i class="fas fa-redo fa-fw text-slate-400" style="font-size: 13px;"></i>
                                        Revalidar documento
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Motivo da rejeição atual --}}
                @if($situacao === 'rejeitado' && !empty($analise['motivo']))
                    <div class="mt-2 ml-11 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-900">
                        <strong>Motivo:</strong> <span class="whitespace-pre-line">{{ $analise['motivo'] }}</span>
                        <span class="block mt-0.5 text-red-700">Aguardando a empresa reenviar este documento.</span>
                    </div>
                @endif

                @if($podeAnalisar && $temArquivo)
                    {{-- Rejeitar --}}
                    <form x-show="rejeitando" x-cloak method="POST" action="{{ route('admin.receituarios.documento.analisar', [$receituario->id, $doc]) }}"
                          class="mt-2 ml-11 rounded-lg border border-red-200 bg-red-50/60 p-3">
                        @csrf
                        <input type="hidden" name="acao" value="rejeitar">
                        <input type="hidden" name="_documento" value="{{ $doc }}">
                        <label class="block text-xs font-semibold text-red-900 mb-1">Motivo da rejeição de "{{ $info['nome'] }}" *</label>
                        <textarea name="motivo" rows="2" required minlength="10" maxlength="2000"
                                  placeholder="Ex.: Imagem ilegível — envie uma digitalização nítida, com frente e verso."
                                  class="w-full px-3 py-2 text-sm border border-red-200 rounded-lg focus:ring-2 focus:ring-red-400 focus:border-red-400">{{ $abrirRejeicao ? old('motivo') : '' }}</textarea>
                        @if($abrirRejeicao)<p class="mt-1 text-xs font-semibold text-red-700">{{ $errors->first('motivo') }}</p>@endif
                        <div class="mt-2 flex justify-end gap-2">
                            <button type="button" @click="rejeitando = false" class="h-8 px-3 text-xs font-semibold text-slate-600 hover:text-slate-900">Cancelar</button>
                            <button type="submit" class="h-8 px-3 text-xs font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700">Confirmar rejeição</button>
                        </div>
                    </form>

                    {{-- Revalidar: volta para pendente --}}
                    @if($situacao !== 'pendente')
                        <form x-show="revalidando" x-cloak method="POST" action="{{ route('admin.receituarios.documento.analisar', [$receituario->id, $doc]) }}"
                              class="mt-2 ml-11 rounded-lg border border-slate-200 bg-slate-50 p-3 flex flex-col sm:flex-row sm:items-center gap-3">
                            @csrf
                            <input type="hidden" name="acao" value="revalidar">
                            <p class="flex-1 text-xs text-slate-700">
                                <i class="fas fa-redo text-slate-400 mr-1" style="font-size: 10px;"></i>
                                <strong>Revalidar {{ mb_strtolower($info['nome']) }}?</strong>
                                {{ $situacao === 'aprovado' ? 'A verificação será desfeita' : 'A rejeição será desfeita' }} e o documento voltará para <strong>pendente</strong>.
                                @if($receituario->isAprovado())
                                    <span class="block mt-0.5 text-amber-700">O cadastro volta para "em análise" até a nova aprovação; os processos já abertos continuam.</span>
                                @endif
                            </p>
                            <div class="flex justify-end gap-2 flex-shrink-0">
                                <button type="button" @click="revalidando = false" class="h-8 px-3 text-xs font-semibold text-slate-600 hover:text-slate-900">Cancelar</button>
                                <button type="submit" class="h-8 px-3 text-xs font-semibold text-white bg-slate-700 rounded-lg hover:bg-slate-800">Voltar para pendente</button>
                            </div>
                        </form>
                    @endif
                @endif

                {{-- Declaração (comprovante em nome de terceiro) --}}
                @if($doc === 'comprovante' && $declaracao)
                    <details class="mt-2 ml-11 text-[11px] text-slate-600">
                        <summary class="cursor-pointer font-semibold text-violet-700 hover:text-violet-900">Ver declaração de endereço</summary>
                        <div class="mt-1 rounded-lg bg-violet-50 border border-violet-100 px-2.5 py-2">
                            @if(!empty($leituraComprovante['titular']))<p class="mb-1">Titular do comprovante: <strong>{{ $leituraComprovante['titular'] }}</strong></p>@endif
                            <p>{{ $declaracao['texto'] ?? '' }}</p>
                            <p class="mt-1 text-violet-700">Aceita em {{ $dataHora($declaracao['aceita_em'] ?? null) ?: '—' }} · IP {{ $declaracao['ip'] ?? '—' }}</p>
                        </div>
                    </details>
                @endif

                {{-- Histórico de rejeições, reenvios e revalidações --}}
                @if(count($historico))
                    <details class="mt-2 ml-11 text-[11px] text-slate-500">
                        <summary class="cursor-pointer font-semibold hover:text-slate-700">Histórico ({{ count($historico) }})</summary>
                        <ul class="mt-1.5 space-y-1">
                            @foreach(array_reverse($historico) as $h)
                                <li class="rounded bg-slate-50 px-2 py-1">
                                    Rejeitado em {{ $dataHora($h['rejeitado_em'] ?? null) ?: '—' }} — {{ $h['motivo'] ?? 'sem motivo' }}
                                    @if(!empty($h['reenviado_em'])) <span class="text-slate-400">· reenviado em {{ $dataHora($h['reenviado_em']) }}</span>@endif
                                    @if(!empty($h['revalidado_em'])) <span class="text-slate-400">· rejeição desfeita (revalidado) em {{ $dataHora($h['revalidado_em']) }}</span>@endif
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endif
