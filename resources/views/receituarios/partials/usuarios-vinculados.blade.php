{{--
    Usuários vinculados ao cadastro de receituário (usado na Vigilância e na área da empresa).
    Variáveis: $receituario, $rotaBuscar, $rotaVincular, $rotaDesvincular (fn ($usuarioId) => url), $usuarioAtualId (externo logado ou null)
--}}
@php
    $dono = $receituario->usuarioExterno;
    $vinculados = $receituario->usuariosVinculados->sortBy('nome')->values();
    $tiposVinculo = \App\Models\Receituario::TIPOS_VINCULO;
    $iniciais = fn ($nome) => collect(explode(' ', trim((string) $nome)))->filter()->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
@endphp

<section class="relative z-30 bg-white rounded-xl border border-slate-200 shadow-sm overflow-visible"
         x-data="{
            busca: '',
            resultados: [],
            buscando: false,
            selecionado: null,
            tipo: 'funcionario',
            timer: null,
            procurar() {
                clearTimeout(this.timer);
                this.selecionado = null;
                if (this.busca.trim().length < 3) { this.resultados = []; return; }
                this.timer = setTimeout(async () => {
                    this.buscando = true;
                    try {
                        const r = await fetch(@js($rotaBuscar) + '?q=' + encodeURIComponent(this.busca.trim()), { headers: { 'Accept': 'application/json' } });
                        this.resultados = r.ok ? await r.json() : [];
                    } catch (e) { this.resultados = []; }
                    this.buscando = false;
                }, 300);
            },
            escolher(u) { this.selecionado = u; this.resultados = []; this.busca = u.nome; }
         }">
    <header class="px-4 py-3 border-b border-slate-100 flex items-center gap-3">
        <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </span>
        <div>
            <h2 class="text-sm font-semibold text-slate-900">Usuários vinculados</h2>
            <p class="text-xs text-slate-500">Quem pode acessar este cadastro, abrir processos e fazer requisições de receita. Todos têm nível <strong>Gestor</strong>.</p>
        </div>
    </header>

    <ul class="divide-y divide-slate-100">
        @if($dono)
        <li class="px-4 py-3 flex items-center gap-3">
            <span class="w-9 h-9 rounded-full bg-slate-700 text-white text-xs font-bold flex items-center justify-center uppercase flex-shrink-0">{{ $iniciais($dono->nome) }}</span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-900 truncate">{{ $dono->nome }}@if($usuarioAtualId === $dono->id) <span class="text-xs font-normal text-slate-500">(você)</span>@endif</p>
                <p class="text-xs text-slate-500 truncate">{{ $dono->email }}{{ $dono->cpf ? ' · ' . ($dono->cpf_formatado ?? $dono->cpf) : '' }}</p>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-200">Fez o cadastro</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200">Gestor</span>
        </li>
        @endif

        @foreach($vinculados as $usuario)
        <li class="px-4 py-3 flex items-center gap-3">
            <span class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center uppercase flex-shrink-0">{{ $iniciais($usuario->nome) }}</span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-900 truncate">{{ $usuario->nome }}@if($usuarioAtualId === $usuario->id) <span class="text-xs font-normal text-slate-500">(você)</span>@endif</p>
                <p class="text-xs text-slate-500 truncate">{{ $usuario->email }}{{ $usuario->cpf ? ' · ' . ($usuario->cpf_formatado ?? $usuario->cpf) : '' }} · desde {{ $usuario->pivot->created_at?->format('d/m/Y') }}</p>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $usuario->pivot->tipo_vinculo === 'profissional' ? 'bg-blue-50 text-blue-700 ring-blue-200' : 'bg-slate-100 text-slate-700 ring-slate-200' }} ring-1 ring-inset">
                {{ $tiposVinculo[$usuario->pivot->tipo_vinculo] ?? ucfirst($usuario->pivot->tipo_vinculo) }}
            </span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200">Gestor</span>
            <form method="POST" action="{{ $rotaDesvincular($usuario->id) }}"
                  onsubmit="return confirm('{{ $usuarioAtualId === $usuario->id ? 'Sair deste cadastro? Você perde o acesso ao profissional e aos processos.' : 'Remover o acesso de ' . e(addslashes($usuario->nome)) . '?' }}')">
                @csrf
                @method('DELETE')
                <button type="submit" title="Remover vínculo" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </form>
        </li>
        @endforeach

        @if(!$dono && $vinculados->isEmpty())
        <li class="px-4 py-6 text-center text-sm text-slate-500">Nenhum usuário externo tem acesso a este cadastro ainda.</li>
        @endif
    </ul>

    {{-- Vincular novo usuário --}}
    <form method="POST" action="{{ $rotaVincular }}" class="px-4 py-4 border-t border-slate-100 bg-slate-50/60 space-y-3">
        @csrf
        <p class="text-xs font-semibold text-slate-700">Dar acesso a outra pessoa</p>
        @if($errors->has('usuario_externo_id') || $errors->has('tipo_vinculo'))
        <p class="text-xs font-medium text-red-600">{{ $errors->first('usuario_externo_id') ?: $errors->first('tipo_vinculo') }}</p>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_auto_auto] gap-2 items-start">
            <div class="relative">
                <input type="text" x-model="busca" @input="procurar()" autocomplete="off"
                       placeholder="Buscar por nome, e-mail ou CPF (a pessoa precisa ter conta no sistema)"
                       class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <input type="hidden" name="usuario_externo_id" :value="selecionado ? selecionado.id : ''">
                <div x-show="resultados.length || buscando || (busca.trim().length >= 3 && !selecionado && !buscando && !resultados.length)" x-cloak
                     class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg max-h-72 overflow-y-auto">
                    <p x-show="buscando" class="px-3 py-2 text-xs text-slate-500">Buscando…</p>
                    <template x-for="u in resultados" :key="u.id">
                        <button type="button" @click="escolher(u)" class="w-full text-left px-3 py-2.5 hover:bg-blue-50">
                            <span class="block text-sm font-semibold text-slate-900" x-text="u.nome + (u.cpf ? ' - ' + u.cpf : '')"></span>
                            <span x-show="u.email" class="block text-xs text-slate-500" x-text="u.email"></span>
                        </button>
                    </template>
                    <p x-show="!buscando && !resultados.length && !selecionado" class="px-3 py-2 text-xs text-slate-500">Nenhum usuário encontrado. A pessoa precisa criar uma conta na área da empresa.</p>
                </div>
            </div>
            <div class="flex rounded-lg border border-slate-300 bg-white overflow-hidden text-sm">
                @foreach($tiposVinculo as $valor => $rotulo)
                <label class="px-3 py-2 cursor-pointer select-none transition" :class="tipo === '{{ $valor }}' ? 'bg-blue-600 text-white' : 'text-slate-700 hover:bg-slate-50'">
                    <input type="radio" name="tipo_vinculo" value="{{ $valor }}" x-model="tipo" class="sr-only">{{ $rotulo }}
                </label>
                @endforeach
            </div>
            <button type="submit" :disabled="!selecionado"
                    class="h-[38px] px-4 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed">Vincular</button>
        </div>
        <p class="text-[11px] text-slate-500">
            <strong>Profissional:</strong> o próprio médico, dentista ou veterinário. <strong>Funcionário:</strong> secretária ou quem cuida dos pedidos dele.
            O vínculo dá acesso como <strong>Gestor</strong>.
        </p>
    </form>
</section>
