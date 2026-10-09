@extends('layouts.company')

@section('title', 'Continuar cadastro')
@section('page-title', 'Receituário · Continuar cadastro')

@section('content')
@php
    $locais = collect($receituario->locais_trabalho ?? [])->filter(fn ($l) => !empty($l['nome']));
    $dados = [
        'Nome' => $receituario->nome,
        'CPF' => $receituario->cpf_formatado,
        'Especialidade' => $receituario->especialidade,
        'Nº do conselho' => $receituario->numero_conselho_classe,
        'Telefone' => $receituario->telefone,
        'Endereço' => trim(($receituario->endereco ?? '') . ($receituario->municipio?->nome ? ' - ' . $receituario->municipio->nome : '') . ($receituario->cep ? ' · CEP ' . $receituario->cep : '')),
    ];
@endphp

<div class="max-w-8xl mx-auto space-y-4"
     x-data="{ arquivoNome: '', arquivoTamanho: '', enviando: false,
               escolher(e) { const f = e.target.files[0]; this.arquivoNome = f ? f.name : '';
                             this.arquivoTamanho = f ? (f.size >= 1048576 ? (f.size / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(f.size / 1024)) + ' KB') : ''; } }">

    <div>
        <a href="{{ route('company.receituarios.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-700">← Profissionais cadastrados</a>
        <h1 class="text-xl font-bold text-slate-900 mt-1">📋 Continue o cadastro de onde parou</h1>
        <p class="text-sm text-slate-500">Os passos 1 a 3 já estão salvos. Falta só anexar a <strong>ficha cadastral assinada</strong> e enviar para a Vigilância Sanitária.</p>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 text-sm">
            @foreach($errors->all() as $erro)<p>{{ $erro }}</p>@endforeach
        </div>
    @endif

    {{-- Progresso --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-5 py-3">
        <div class="flex items-center">
            @foreach(['Dados Pessoais', 'Endereço', 'Locais de Trabalho', 'Ficha assinada'] as $i => $titulo)
                <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                    <div class="flex flex-col items-center gap-1">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold {{ $loop->last ? 'bg-blue-600 text-white ring-4 ring-blue-100' : 'bg-emerald-500 text-white' }}">{{ $loop->last ? 4 : '✓' }}</span>
                        <span class="text-xs font-semibold whitespace-nowrap {{ $loop->last ? 'text-blue-700' : 'text-slate-500' }}">{{ $titulo }}</span>
                    </div>
                    @unless($loop->last)<div class="flex-1 h-0.5 mx-3 mb-4 rounded bg-emerald-500"></div>@endunless
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_22rem] gap-4 items-start">
        {{-- Passo 4 --}}
        <form method="POST" action="{{ route('company.receituarios.concluir', $receituario->id) }}" enctype="multipart/form-data"
              @submit="enviando = true" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
            @csrf
            <div>
                <h2 class="text-base font-bold text-slate-900">Passo 4: Ficha cadastral assinada</h2>
                <p class="text-xs text-slate-500">Se ainda não assinou, baixe a ficha de novo. Depois anexe o arquivo assinado.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('company.receituarios.gerar-pdf', $receituario->id) }}" download="ficha-cadastral-receituario.pdf"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Baixar ficha (PDF)
                </a>
                <a href="{{ route('company.receituarios.gerar-pdf', $receituario->id) }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                    Abrir para imprimir
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3">
                    <p class="text-sm font-semibold text-emerald-900">✍️ Assinatura digital gov.br <span class="text-[11px] font-normal">(recomendado)</span></p>
                    <ol class="mt-1.5 space-y-1 text-xs text-emerald-900 list-decimal list-inside">
                        <li>Acesse o <a href="https://assinador.iti.br" target="_blank" rel="noopener" class="font-semibold underline">assinador gov.br</a> com sua conta gov.br (prata ou ouro).</li>
                        <li>Envie o PDF da ficha e assine.</li>
                        <li>Baixe o PDF assinado e anexe abaixo.</li>
                    </ol>
                </div>
                <div class="rounded-lg bg-slate-50 border border-slate-200 p-3">
                    <p class="text-sm font-semibold text-slate-900">🖨️ Assinatura à mão, com carimbo</p>
                    <ol class="mt-1.5 space-y-1 text-xs text-slate-700 list-decimal list-inside">
                        <li>Imprima a ficha.</li>
                        <li>Assine e <strong>carimbe</strong> com seu nome e nº do conselho.</li>
                        <li>Digitalize e anexe abaixo.</li>
                    </ol>
                </div>
            </div>

            <label class="flex items-center gap-3 rounded-lg border-2 border-dashed px-4 py-3 cursor-pointer transition"
                   :class="arquivoNome ? 'border-emerald-300 bg-emerald-50/40' : 'border-slate-300 hover:border-blue-400 hover:bg-blue-50/40'">
                <input type="file" name="documento_assinado" accept="application/pdf,image/jpeg,image/png,image/webp" required class="sr-only" @change="escolher($event)">
                <svg class="w-6 h-6 flex-shrink-0" :class="arquivoNome ? 'text-emerald-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold truncate" :class="arquivoNome ? 'text-emerald-800' : 'text-slate-700'" x-text="arquivoNome || 'Anexar a ficha assinada *'"></span>
                    <span class="block text-xs text-slate-500" x-text="arquivoNome ? arquivoTamanho + ' · clique para trocar' : 'PDF ou imagem (JPG, PNG), até 10 MB'"></span>
                </span>
            </label>

            <div class="flex flex-wrap items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="submit" :disabled="!arquivoNome || enviando"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-text="enviando ? 'Enviando…' : '✓ Enviar cadastro para análise'"></span>
                </button>
            </div>
        </form>

        {{-- Resumo do que já foi salvo --}}
        <aside class="space-y-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Dados salvos</p>
                <dl class="mt-2 space-y-1.5 text-xs">
                    @foreach($dados as $rotulo => $valor)
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500 flex-shrink-0">{{ $rotulo }}</dt>
                            <dd class="text-slate-800 text-right">{{ $valor ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if($locais->isNotEmpty())
                    <p class="mt-3 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Locais de trabalho</p>
                    <ul class="mt-1 space-y-0.5 text-xs text-slate-800">
                        @foreach($locais as $local)
                            <li>{{ $local['nome'] }}{{ !empty($local['municipio']) ? ' · ' . $local['municipio'] : '' }}</li>
                        @endforeach
                    </ul>
                @endif
                <ul class="mt-3 space-y-1 text-xs">
                    <li class="{{ $receituario->carteira_conselho_path ? 'text-emerald-700' : 'text-red-600' }}">{{ $receituario->carteira_conselho_path ? '✓' : '✕' }} Carteira do conselho anexada</li>
                    <li class="{{ $receituario->comprovante_endereco_path ? 'text-emerald-700' : 'text-red-600' }}">{{ $receituario->comprovante_endereco_path ? '✓' : '✕' }} Comprovante de endereço anexado</li>
                </ul>
            </div>

            <form method="POST" action="{{ route('company.receituarios.descartar-rascunho', $receituario->id) }}"
                  onsubmit="return confirm('Descartar este cadastro salvo e começar de novo? Os arquivos enviados serão apagados.')"
                  class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                @csrf
                @method('DELETE')
                <p class="text-xs text-slate-600">Precisa corrigir algum dado dos passos 1 a 3?</p>
                <button type="submit" class="mt-2 text-xs font-semibold text-red-600 hover:underline">Descartar e começar o cadastro de novo</button>
            </form>
        </aside>
    </div>
</div>
@endsection
