{{-- Atividades informadas no cadastro "por enquanto, só Projeto/Rotulagem".
     Passam a valer (competência pela pactuação) quando a empresa abrir o Licenciamento. --}}
@if(!empty($estabelecimento->atividades_declaradas))
<div class="bg-white rounded-xl shadow-sm border border-amber-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-1 flex items-center">
        <svg class="w-5 h-5 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Atividades declaradas (aguardando Licenciamento)
    </h3>
    <p class="text-xs text-gray-500 mb-4">
        Cadastro feito apenas para Projeto Arquitetônico e/ou Análise de Rotulagem. Estas são as atividades que o estabelecimento exerce;
        elas passam a valer — com a competência definida pela pactuação — quando o Licenciamento for aberto.
    </p>
    <div class="space-y-2">
        @foreach($estabelecimento->atividades_declaradas as $atividade)
        <div class="flex items-start gap-3 p-3 bg-amber-50/60 rounded-lg">
            <span class="flex-shrink-0 px-2 py-0.5 bg-amber-100 text-amber-800 text-xs font-mono rounded">
                {{ is_array($atividade) ? ($atividade['codigo'] ?? '-') : $atividade }}
            </span>
            <span class="text-sm text-gray-700">{{ is_array($atividade) ? ($atividade['descricao'] ?? '-') : '' }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif
