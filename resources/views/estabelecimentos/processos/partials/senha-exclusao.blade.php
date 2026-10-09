{{-- Confirmação da exclusão do processo com a senha da assinatura digital ($modelo = variável Alpine do campo) --}}
@if(auth('interno')->user()->temSenhaAssinatura())
    <label class="block">
        <span class="block text-xs font-semibold text-slate-600 mb-1">Senha da assinatura digital *</span>
        <input type="password" name="senha_assinatura" x-model="{{ $modelo }}" required autocomplete="off"
               class="w-full text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-400 focus:border-red-400">
    </label>
@else
    <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
        Para excluir processos é preciso a senha da assinatura digital.
        <a href="{{ route('admin.assinatura.configurar-senha') }}" class="font-semibold underline">Configurar minha senha</a>
    </p>
@endif
@if(session('erro_exclusao'))
    <p class="text-xs font-semibold text-red-600">{{ session('erro_exclusao') }}</p>
@endif
