@extends('layouts.company')

@section('title', 'Documento para assinatura')
@section('page-title', 'Receituário · Documento para assinatura')

@section('content')
<div class="mx-auto max-w-7xl space-y-4">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('company.receituarios.index') }}" class="text-xs font-semibold text-slate-500 hover:text-blue-700">← Minhas solicitações</a>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-bold text-slate-900">Documento para assinatura</h1>
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset {{ $receituario->situacao['classe'] }}">
                    {{ $receituario->situacao['label'] }}
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-600">{{ $receituario->tipo_nome }} <span class="px-1 text-slate-300">·</span> Solicitação {{ $receituario->identificador }}</p>
        </div>
        <a href="{{ route('company.receituarios.show', $receituario->id) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
            Ver dados da solicitação
        </a>
    </header>

    <section class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 sm:px-5" aria-labelledby="proximos-passos-titulo">
        <div class="flex items-start gap-3">
            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-800">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l2.5 1.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
            <div class="min-w-0 flex-1">
                <h2 id="proximos-passos-titulo" class="text-sm font-bold text-amber-950">Próximo passo: imprimir e assinar</h2>
                <p class="mt-0.5 text-xs leading-relaxed text-amber-900">Siga as etapas para concluir a entrega do documento à Vigilância Sanitária.</p>
            </div>
        </div>

        <ol class="mt-4 grid grid-cols-1 divide-y divide-amber-200 sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-4 lg:divide-x">
            <li class="flex items-start gap-2.5 py-3 first:pt-0 sm:px-3 sm:first:pl-0 lg:py-1 lg:first:pt-1">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-white text-xs font-bold text-blue-700 ring-1 ring-amber-200">1</span>
                <span><strong class="block text-xs text-slate-900">Baixe e imprima</strong><span class="mt-0.5 block text-xs text-slate-600">Use o botão “Baixar PDF” ou imprima a prévia.</span></span>
            </li>
            <li class="flex items-start gap-2.5 py-3 sm:px-3 lg:py-1">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-white text-xs font-bold text-blue-700 ring-1 ring-amber-200">2</span>
                <span><strong class="block text-xs text-slate-900">Assine as 3 vias</strong><span class="mt-0.5 block text-xs text-slate-600">Assine nos campos indicados no documento.</span></span>
            </li>
            <li class="flex items-start gap-2.5 py-3 sm:px-3 lg:py-1">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-white text-xs font-bold text-blue-700 ring-1 ring-amber-200">3</span>
                <span><strong class="block text-xs text-slate-900">Digitalize as vias assinadas</strong><span class="mt-0.5 block text-xs text-slate-600">Confira se todas as páginas estão legíveis.</span></span>
            </li>
            <li class="flex items-start gap-2.5 py-3 last:pb-0 sm:px-3 lg:py-1 lg:pr-0">
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-white text-xs font-bold text-blue-700 ring-1 ring-amber-200">4</span>
                <span><strong class="block text-xs text-slate-900">Envie aqui o documento assinado</strong><span class="mt-0.5 block text-xs text-slate-600">Só depois do envio o cadastro vai para a análise da Vigilância Sanitária.</span></span>
            </li>
        </ol>

        <p class="mt-3 border-t border-amber-200 pt-3 text-xs leading-relaxed text-amber-900">
            A assinatura deve ser do próprio profissional e semelhante à do documento de identificação. Se necessário, reconheça-a em cartório.
        </p>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white" aria-labelledby="pdf-preview-title">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h2 id="pdf-preview-title" class="text-sm font-bold text-slate-900">Prévia do documento</h2>
                <p class="mt-0.5 text-xs text-slate-500">Confira os dados antes de imprimir e assinar.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('company.receituarios.gerar-pdf', $receituario->id) }}" target="_blank" rel="noopener"
                   class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-blue-700 px-3.5 py-2 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v3h14v-3"/></svg>
                    Baixar PDF
                </a>
                <button type="button" onclick="document.getElementById('pdfViewer').contentWindow.print()"
                        class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2m-10-3h10v7H7z"/></svg>
                    Imprimir
                </button>
            </div>
        </div>
        <div class="bg-slate-100 px-2 py-4 sm:px-5 sm:py-6">
            <iframe id="pdfViewer" src="{{ route('company.receituarios.gerar-pdf', $receituario->id) }}" class="mx-auto block w-full max-w-4xl border-0 bg-white shadow-sm" style="height: min(82vh, 1100px); min-height: 560px;" title="Prévia centralizada do PDF para assinatura"></iframe>
        </div>
    </section>
</div>
@endsection
