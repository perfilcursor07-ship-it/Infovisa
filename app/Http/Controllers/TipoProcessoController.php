<?php

namespace App\Http\Controllers;

use App\Models\TipoProcesso;
use App\Models\Municipio;
use App\Models\TipoSetor;
use Illuminate\Http\Request;

class TipoProcessoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tiposProcesso = TipoProcesso::with(['tipoSetor', 'setoresMunicipais'])->ordenado()->get();
        return view('admin.configuracoes.tipos-processo.index', compact('tiposProcesso'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $municipios = Municipio::orderBy('nome')->get();
        $tiposSetor = TipoSetor::where('ativo', true)->with('municipios:id')->orderBy('nome')->get();
        $setoresMunicipaisPorMunicipio = [];

        // Preparar dados de setores para JS (evita arrow functions no Blade)
        $tiposSetorJs = $tiposSetor->map(function ($s) {
            return [
                'id' => $s->id,
                'nome' => $s->nome,
                'municipio_ids' => $s->municipios->pluck('id')->values()->toArray(),
            ];
        })->values();

        return view('admin.configuracoes.tipos-processo.create', compact('municipios', 'tiposSetor', 'setoresMunicipaisPorMunicipio', 'tiposSetorJs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'codigo' => 'required|string|max:255|unique:tipo_processos,codigo',
            'descricao' => 'nullable|string',
            'aviso_abertura_titulo' => 'nullable|string|max:255',
            'aviso_abertura_mensagem' => 'required_if:exibir_aviso_abertura_empresa,on|nullable|string|max:5000',
            'aviso_abertura_confirmacao' => 'nullable|string|max:255',
            'ordem' => 'nullable|integer|min:0',
            'competencia' => 'required|in:estadual,municipal,estadual_exclusivo',
            'tipo_setor_id' => 'nullable|exists:tipo_setores,id',
            'prazo_fila_publica' => 'nullable|integer|min:1|max:365',
            'prazo_fila_publica_alto' => 'nullable|integer|min:1|max:365',
            'prazo_fila_publica_medio' => 'nullable|integer|min:1|max:365',
            'prazo_fila_publica_baixo' => 'nullable|integer|min:1|max:365',
            'setores_municipais' => 'nullable|array',
            'setores_municipais.*' => 'nullable|exists:tipo_setores,id',
        ]);

        // Converte checkboxes para boolean (checkboxes não enviam valor quando desmarcados)
        $validated['anual'] = $request->has('anual');
        $validated['usuario_externo_pode_abrir'] = $request->has('usuario_externo_pode_abrir');
        $validated['exclusivo_receituario'] = $request->has('exclusivo_receituario');
        $validated['exibir_aviso_abertura_empresa'] = $request->has('exibir_aviso_abertura_empresa');
        $validated['usuario_externo_pode_visualizar'] = $request->has('usuario_externo_pode_visualizar');
        $validated['exibir_fila_publica'] = $request->has('exibir_fila_publica');
        $validated['exibir_aviso_prazo_fila'] = $request->has('exibir_aviso_prazo_fila');
        $validated['unico_por_estabelecimento'] = $request->has('unico_por_estabelecimento');
        
        // Se fila pública não está marcada, limpa o prazo e aviso
        if (!$validated['exibir_fila_publica']) {
            $validated['prazo_fila_publica'] = null;
            $validated['exibir_aviso_prazo_fila'] = false;
        }
        $validated['ativo'] = $request->has('ativo');
        $validated['ordem'] = $validated['ordem'] ?? 0;

        if (!$validated['exibir_aviso_abertura_empresa']) {
            $validated['aviso_abertura_titulo'] = null;
            $validated['aviso_abertura_mensagem'] = null;
            $validated['aviso_abertura_confirmacao'] = null;
        } else {
            $validated['aviso_abertura_titulo'] = $validated['aviso_abertura_titulo'] ?: 'Atenção antes de abrir este processo';
            $validated['aviso_abertura_confirmacao'] = $validated['aviso_abertura_confirmacao'] ?: 'Li e confirmo que este é o processo correto para minha solicitação.';
        }
        
        // Trata o tipo_setor_id vazio
        if (empty($validated['tipo_setor_id'])) {
            $validated['tipo_setor_id'] = null;
        }
        
        // Processa municípios descentralizados (apenas para tipos estaduais)
        if ($validated['competencia'] === 'estadual' && $request->filled('municipios_descentralizados')) {
            $validated['municipios_descentralizados'] = $request->municipios_descentralizados;
            
            // Busca IDs dos municípios
            $municipiosIds = Municipio::whereIn('nome', $request->municipios_descentralizados)
                ->pluck('id')
                ->toArray();
            $validated['municipios_descentralizados_ids'] = $municipiosIds;
        }

        $tipoProcesso = TipoProcesso::create($validated);
        $this->sincronizarSetoresMunicipais($tipoProcesso, $request->input('setores_municipais', []), $validated['competencia']);

        // Sincroniza unidades
        $tipoProcesso->unidades()->sync($request->input('unidades', []));

        return redirect()
            ->route('admin.configuracoes.tipos-processo.index')
            ->with('success', 'Tipo de processo criado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoProcesso $tipoProcesso)
    {
        return view('admin.configuracoes.tipos-processo.show', compact('tipoProcesso'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TipoProcesso $tipoProcesso)
    {
        $tipoProcesso->load('setoresMunicipais');
        $municipios = Municipio::orderBy('nome')->get();
        $tiposSetor = TipoSetor::where('ativo', true)->with('municipios:id')->orderBy('nome')->get();
        $setoresMunicipaisPorMunicipio = $tipoProcesso->setoresMunicipais
            ->pluck('tipo_setor_id', 'municipio_id')
            ->toArray();

        // Preparar dados de setores para JS
        $tiposSetorJs = $tiposSetor->map(function ($s) {
            return [
                'id' => $s->id,
                'nome' => $s->nome,
                'municipio_ids' => $s->municipios->pluck('id')->values()->toArray(),
            ];
        })->values();

        return view('admin.configuracoes.tipos-processo.edit', compact('tipoProcesso', 'municipios', 'tiposSetor', 'setoresMunicipaisPorMunicipio', 'tiposSetorJs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TipoProcesso $tipoProcesso)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'codigo' => 'required|string|max:255|unique:tipo_processos,codigo,' . $tipoProcesso->id,
            'descricao' => 'nullable|string',
            'aviso_abertura_titulo' => 'nullable|string|max:255',
            'aviso_abertura_mensagem' => 'required_if:exibir_aviso_abertura_empresa,on|nullable|string|max:5000',
            'aviso_abertura_confirmacao' => 'nullable|string|max:255',
            'ordem' => 'nullable|integer|min:0',
            'competencia' => 'required|in:estadual,municipal,estadual_exclusivo',
            'tipo_setor_id' => 'nullable|exists:tipo_setores,id',
            'prazo_fila_publica' => 'nullable|integer|min:1|max:365',
            'prazo_fila_publica_alto' => 'nullable|integer|min:1|max:365',
            'prazo_fila_publica_medio' => 'nullable|integer|min:1|max:365',
            'prazo_fila_publica_baixo' => 'nullable|integer|min:1|max:365',
            'setores_municipais' => 'nullable|array',
            'setores_municipais.*' => 'nullable|exists:tipo_setores,id',
        ]);

        // Converte checkboxes para boolean (checkboxes não enviam valor quando desmarcados)
        $validated['anual'] = $request->has('anual');
        $validated['usuario_externo_pode_abrir'] = $request->has('usuario_externo_pode_abrir');
        $validated['exclusivo_receituario'] = $request->has('exclusivo_receituario');
        $validated['exibir_aviso_abertura_empresa'] = $request->has('exibir_aviso_abertura_empresa');
        $validated['usuario_externo_pode_visualizar'] = $request->has('usuario_externo_pode_visualizar');
        $validated['exibir_fila_publica'] = $request->has('exibir_fila_publica');
        $validated['exibir_aviso_prazo_fila'] = $request->has('exibir_aviso_prazo_fila');
        $validated['unico_por_estabelecimento'] = $request->has('unico_por_estabelecimento');
        
        // Se fila pública não está marcada, limpa o prazo e aviso
        if (!$validated['exibir_fila_publica']) {
            $validated['prazo_fila_publica'] = null;
            $validated['exibir_aviso_prazo_fila'] = false;
        }
        $validated['ativo'] = $request->has('ativo');
        $validated['ordem'] = $validated['ordem'] ?? 0;

        if (!$validated['exibir_aviso_abertura_empresa']) {
            $validated['aviso_abertura_titulo'] = null;
            $validated['aviso_abertura_mensagem'] = null;
            $validated['aviso_abertura_confirmacao'] = null;
        } else {
            $validated['aviso_abertura_titulo'] = $validated['aviso_abertura_titulo'] ?: 'Atenção antes de abrir este processo';
            $validated['aviso_abertura_confirmacao'] = $validated['aviso_abertura_confirmacao'] ?: 'Li e confirmo que este é o processo correto para minha solicitação.';
        }
        
        // Trata o tipo_setor_id vazio
        if (empty($validated['tipo_setor_id'])) {
            $validated['tipo_setor_id'] = null;
        }
        
        // Processa municípios descentralizados (apenas para tipos estaduais)
        if ($validated['competencia'] === 'estadual' && $request->filled('municipios_descentralizados')) {
            $validated['municipios_descentralizados'] = $request->municipios_descentralizados;
            
            // Busca IDs dos municípios
            $municipiosIds = Municipio::whereIn('nome', $request->municipios_descentralizados)
                ->pluck('id')
                ->toArray();
            $validated['municipios_descentralizados_ids'] = $municipiosIds;
        } else {
            // Se mudou para municipal ou não tem municípios, limpa a descentralização
            $validated['municipios_descentralizados'] = null;
            $validated['municipios_descentralizados_ids'] = null;
        }

        $tipoProcesso->update($validated);
        $this->sincronizarSetoresMunicipais($tipoProcesso, $request->input('setores_municipais', []), $validated['competencia']);

        // Sincroniza unidades
        $tipoProcesso->unidades()->sync($request->input('unidades', []));

        return redirect()
            ->route('admin.configuracoes.tipos-processo.index')
            ->with('success', 'Tipo de processo atualizado com sucesso!');
    }

    private function sincronizarSetoresMunicipais(TipoProcesso $tipoProcesso, array $setoresMunicipais, string $competencia): void
    {
        if (!in_array($competencia, ['municipal', 'estadual'], true)) {
            $tipoProcesso->setoresMunicipais()->delete();
            return;
        }

        $registros = collect($setoresMunicipais)
            ->filter(fn ($tipoSetorId) => !empty($tipoSetorId))
            ->map(fn ($tipoSetorId, $municipioId) => [
                'tipo_processo_id' => $tipoProcesso->id,
                'municipio_id' => (int) $municipioId,
                'tipo_setor_id' => (int) $tipoSetorId,
            ])
            ->values();

        $tipoProcesso->setoresMunicipais()->delete();

        if ($registros->isNotEmpty()) {
            $tipoProcesso->setoresMunicipais()->createMany($registros->all());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoProcesso $tipoProcesso)
    {
        $tipoProcesso->delete();

        return redirect()
            ->route('admin.configuracoes.tipos-processo.index')
            ->with('success', 'Tipo de processo removido com sucesso!');
    }
}
