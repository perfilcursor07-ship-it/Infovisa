<?php

namespace App\Http\Controllers;

use App\Models\Estabelecimento;
use App\Models\UsuarioExterno;
use App\Enums\VinculoEstabelecimento;
use Illuminate\Http\Request;

class UsuarioExternoController extends Controller
{
    private function podeVisualizarEditar(): bool
    {
        $usuario = auth('interno')->user();

        return $usuario && (
            $usuario->isAdmin() || $usuario->nivel_acesso->value === 'gestor_estadual'
        );
    }

    private function somenteAdmin(): void
    {
        if (!auth('interno')->user()?->isAdmin()) {
            abort(403, 'Você não tem permissão para executar esta ação.');
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$this->podeVisualizarEditar()) {
            abort(403, 'Você não tem permissão para acessar esta área.');
        }

        $query = UsuarioExterno::query();

        // Filtro por nome (case-insensitive usando ILIKE)
        if ($request->filled('nome')) {
            $query->whereRaw("nome ILIKE ?", ['%' . $request->nome . '%']);
        }

        // Filtro por CPF
        if ($request->filled('cpf')) {
            $cpf = preg_replace('/\D/', '', $request->cpf);
            $query->where('cpf', 'like', '%' . $cpf . '%');
        }

        // Filtro por email (case-insensitive usando ILIKE)
        if ($request->filled('email')) {
            $query->whereRaw("email ILIKE ?", ['%' . $request->email . '%']);
        }

        // Filtro por vínculo
        if ($request->filled('vinculo_estabelecimento')) {
            $query->where('vinculo_estabelecimento', $request->vinculo_estabelecimento);
        }

        // Filtro por status
        if ($request->filled('ativo')) {
            $query->where('ativo', $request->ativo === '1');
        }

        // Filtro por aceite de termos
        if ($request->filled('aceite_termos')) {
            if ($request->aceite_termos === '1') {
                $query->whereNotNull('aceite_termos_em');
            } else {
                $query->whereNull('aceite_termos_em');
            }
        }

        // Ordenação
        $sortField = $request->get('sort', 'nome');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Paginação
        $usuarios = $query->paginate(15)->withQueryString();

        return view('admin.usuarios-externos.index', compact('usuarios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->somenteAdmin();

        return view('admin.usuarios-externos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->somenteAdmin();

        // Remove máscara do CPF antes de validar
        $request->merge([
            'cpf' => preg_replace('/[^0-9]/', '', $request->cpf)
        ]);
        
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'cpf' => 'required|string|size:11|unique:usuarios_externos,cpf',
            'email' => 'required|email|unique:usuarios_externos,email',
            'telefone' => 'nullable|string|max:20',
            'vinculo_estabelecimento' => 'nullable|string',
            'password' => 'required|string|min:8|confirmed',
            'ativo' => 'boolean',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['ativo'] = $request->has('ativo');

        UsuarioExterno::create($validated);

        return redirect()->route('admin.usuarios-externos.index')
            ->with('success', 'Usuário externo criado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(UsuarioExterno $usuarioExterno)
    {
        if (!$this->podeVisualizarEditar()) {
            abort(403, 'Você não tem permissão para visualizar usuários externos.');
        }

        $usuarioExterno->load([
            'estabelecimentosVinculados' => fn ($query) => $query
                ->orderBy('estabelecimento_usuario_externo.created_at', 'desc')
        ]);

        $estabelecimentosComoCriador = Estabelecimento::where('usuario_externo_id', $usuarioExterno->id)
            ->get();

        $estabelecimentosRelacionados = $usuarioExterno->estabelecimentosVinculados
            ->merge($estabelecimentosComoCriador)
            ->unique('id')
            ->values();

        return view('admin.usuarios-externos.show', compact('usuarioExterno', 'estabelecimentosRelacionados'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UsuarioExterno $usuarioExterno)
    {
        if (!$this->podeVisualizarEditar()) {
            abort(403, 'Você não tem permissão para editar usuários externos.');
        }

        return view('admin.usuarios-externos.edit', compact('usuarioExterno'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UsuarioExterno $usuarioExterno)
    {
        if (!$this->podeVisualizarEditar()) {
            abort(403, 'Você não tem permissão para editar usuários externos.');
        }

        // Remove máscara do CPF antes de validar
        $request->merge([
            'cpf' => preg_replace('/[^0-9]/', '', $request->cpf)
        ]);
        
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'cpf' => 'required|string|size:11|unique:usuarios_externos,cpf,' . $usuarioExterno->id,
            'email' => 'required|email|unique:usuarios_externos,email,' . $usuarioExterno->id,
            'telefone' => 'nullable|string|max:20',
            'vinculo_estabelecimento' => 'nullable|string',
            'password' => 'nullable|string|min:8|confirmed',
            'ativo' => 'boolean',
            'modulos' => 'nullable|array',
            'modulos.*' => 'string|in:' . implode(',', array_keys(UsuarioExterno::MODULOS)),
        ]);

        // Nenhum marcado = sem módulos (lista vazia; nulo significaria acesso a tudo)
        $validated['modulos'] = array_values(array_intersect(array_keys(UsuarioExterno::MODULOS), $request->input('modulos', [])));

        if ($request->filled('password')) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['ativo'] = $request->has('ativo');

        $usuarioExterno->update($validated);

        return redirect()->route('admin.usuarios-externos.index')
            ->with('success', 'Usuário externo atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UsuarioExterno $usuarioExterno)
    {
        $this->somenteAdmin();

        $usuarioExterno->delete();

        return redirect()->route('admin.usuarios-externos.index')
            ->with('success', 'Usuário externo excluído com sucesso!');
    }
}
