<?php

namespace App\Http\Controllers;

use App\Models\Receituario;
use App\Models\Municipio;
use App\Models\Processo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceituarioController extends Controller
{
    /**
     * Verifica se o usuário tem permissão para acessar receituários
     * Apenas Admin e Estadual podem acessar
     */
    private function verificarPermissao()
    {
        $usuario = auth('interno')->user();
        
        if (!$usuario) {
            return redirect()->route('admin.login');
        }
        
        // Apenas Admin e Estadual podem acessar
        if (!$usuario->isAdmin() && !$usuario->isEstadual()) {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Você não tem permissão para acessar o módulo de Receituários. Este módulo é exclusivo para usuários estaduais.');
        }
        
        return null; // Tem permissão
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Verifica permissão
        if ($redirect = $this->verificarPermissao()) {
            return $redirect;
        }
        
        $query = Receituario::with(['municipio', 'processo', 'usuarioExterno:id,nome', 'estabelecimento' => fn ($q) => $q->withCount('processos')]);

        // Filtros
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        // A listagem abre em todos; as abas restringem por status quando necessário.
        $status = $request->input('status', 'todos');
        if ($status && $status !== 'todos') {
            $query->where('status', $status);
        }
        
        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function($q) use ($busca) {
                $q->where('nome', 'ILIKE', "%{$busca}%")
                  ->orWhere('razao_social', 'ILIKE', "%{$busca}%")
                  ->orWhere('cpf', 'LIKE', "%{$busca}%")
                  ->orWhere('cnpj', 'LIKE', "%{$busca}%");
            });
        }
        
        // Pendentes: os mais antigos primeiro (fila de análise)
        $receituarios = ($status === 'pendente'
            ? $query->orderByRaw('COALESCE(documento_assinado_enviado_em, created_at) asc')
            : $query->orderBy('created_at', 'desc'))
            ->paginate(20)->withQueryString();

        $porStatus = Receituario::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('receituarios.index', compact('receituarios', 'status', 'porStatus'));
    }

    /**
     * Aprova ou rejeita UM documento do cadastro (carteira, comprovante ou requisição assinada).
     * Rejeitado: a empresa reenvia só aquele documento. Todos aprovados: o cadastro fica aprovado
     * e a empresa já pode abrir o processo de receituário.
     */
    public function analisarDocumento(Request $request, $id, string $documento, \App\Services\ReceituarioCadastroService $cadastro)
    {
        if ($redirect = $this->verificarPermissao()) {
            return $redirect;
        }

        $receituario = Receituario::findOrFail($id);

        if (!in_array($documento, $receituario->documentosDaAnalise(), true) || !$receituario->temDocumento($documento)) {
            return back()->with('error', 'Documento não encontrado neste cadastro.');
        }
        if ($receituario->status === 'aguardando_assinatura') {
            return back()->with('error', 'Aguarde a empresa enviar a requisição assinada para analisar os documentos.');
        }

        $validated = $request->validate([
            'acao' => 'required|in:aprovar,rejeitar,revalidar',
            'motivo' => 'required_if:acao,rejeitar|nullable|string|min:10|max:2000',
        ], [
            'motivo.required_if' => 'Informe o motivo da rejeição.',
            'motivo.min' => 'Descreva o motivo com pelo menos 10 caracteres.',
        ]);

        $eraAprovado = $receituario->isAprovado();

        // Revalidar: o documento já analisado volta para pendente (nova análise)
        if ($validated['acao'] === 'revalidar') {
            if ($receituario->statusDocumento($documento) === 'pendente') {
                return back()->with('error', 'Este documento já está pendente de análise.');
            }
            $receituario->revalidarDocumento($documento, Auth::guard('interno')->id());

            return redirect()->route('admin.receituarios.show', ['id' => $receituario->id, 'aba' => 'documentos'])
                ->with('success', Receituario::DOCUMENTOS[$documento]['nome'] . ' voltou para pendente de análise.'
                    . ($eraAprovado ? ' O cadastro voltou para "em análise" até a nova aprovação.' : ''));
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($receituario, $documento, $validated, $cadastro) {
            $receituario->analisarDocumento(
                $documento,
                $validated['acao'] === 'aprovar' ? 'aprovado' : 'rejeitado',
                $validated['motivo'] ?? null,
                Auth::guard('interno')->id()
            );
            // Todos os documentos aprovados: cria o cadastro interno onde ficarão os processos de receituário
            if ($receituario->isAprovado()) {
                $cadastro->garantirEstabelecimento($receituario);
            }
        });

        $mensagem = match (true) {
            $receituario->isAprovado() && !$eraAprovado => Receituario::frase($documento, 'aprovad') . '. Todos os documentos foram aprovados: cadastro aprovado e a empresa já pode abrir o processo de receituário.',
            $validated['acao'] === 'aprovar' => Receituario::frase($documento, 'aprovad') . '.',
            default => Receituario::frase($documento, 'rejeitad') . '. A empresa verá o motivo e reenviará este documento.',
        };

        return redirect()->route('admin.receituarios.show', ['id' => $receituario->id, 'aba' => 'documentos'])->with('success', $mensagem);
    }

    public function documentoAssinado($id)
    {
        if ($redirect = $this->verificarPermissao()) {
            return $redirect;
        }

        return Receituario::findOrFail($id)->respostaDocumento('assinado');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // Verifica permissão
        if ($redirect = $this->verificarPermissao()) {
            return $redirect;
        }
        
        $tipo = $request->get('tipo', 'medico');
        $municipios = Municipio::orderBy('nome')->get();
        
        return view('receituarios.create-wizard', compact('tipo', 'municipios'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Verifica permissão
        if ($redirect = $this->verificarPermissao()) {
            return $redirect;
        }
        
        $rules = [
            'tipo' => 'required|in:medico,instituicao,secretaria,talidomida',
        ];
        
        // Regras específicas por tipo
        if (in_array($request->tipo, ['medico', 'talidomida'])) {
            $rules['nome'] = 'required|string|max:255';
            $rules['cpf'] = 'required|string|max:14';
            $rules['especialidade'] = ['required', 'string', Rule::in(Receituario::ESPECIALIDADES)];
            $rules['telefone'] = 'required|string|max:20';
            $rules['numero_conselho_classe'] = ($request->tipo === 'medico' ? 'required' : 'nullable') . '|string|max:50';
            $rules['numero_crm'] = ($request->tipo === 'talidomida' ? 'required' : 'nullable') . '|string|max:50';
            $rules['endereco'] = 'nullable|string|max:255';
            $rules['cep'] = 'nullable|string|max:10';
            $rules['municipio_id'] = 'nullable|exists:municipios,id';
        }
        
        if (in_array($request->tipo, ['instituicao', 'secretaria'])) {
            $rules['razao_social'] = 'required|string|max:255';
            $rules['cnpj'] = 'required|string|max:18';
            $rules['municipio_id'] = 'nullable|exists:municipios,id';
            $rules['endereco'] = 'nullable|string|max:255';
            $rules['cep'] = 'nullable|string|max:10';
            $rules['telefone'] = 'nullable|string|max:20';
            $rules['email'] = 'nullable|email|max:255';
        }
        
        if ($request->tipo === 'instituicao') {
            $rules['responsavel_nome'] = 'nullable|string|max:255';
            $rules['responsavel_cpf'] = 'nullable|string|max:14';
            $rules['responsavel_crm'] = 'nullable|string|max:50';
        }
        
        $validated = $request->validate($rules, [
            'especialidade.required' => 'Selecione uma especialidade ou área de atuação.',
            'especialidade.in' => 'Selecione uma especialidade ou área de atuação da lista.',
            'numero_conselho_classe.required' => 'Informe o número do conselho de classe.',
            'numero_crm.required' => 'Informe o número do CRM.',
        ]);
        
        // Processa locais de trabalho
        if ($request->has('locais_trabalho')) {
            $validated['locais_trabalho'] = array_filter($request->locais_trabalho, function($local) {
                return !empty($local['nome']);
            });
        }
        
        $validated['usuario_criacao_id'] = Auth::guard('interno')->user()->id;
        $validated['status'] = 'pendente';
        
        $receituario = Receituario::create($validated);
        
        // Redireciona para página com PDF e instruções de assinatura
        return redirect()
            ->route('admin.receituarios.pdf-gerado', $receituario->id)
            ->with('success', 'Receituário cadastrado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        // Verifica permissão
        if ($redirect = $this->verificarPermissao()) {
            return $redirect;
        }

        $receituario = Receituario::with(['municipio', 'processo', 'usuarioCriacao', 'usuarioExterno', 'analisadoPor:id,nome', 'estabelecimento.processos'])->findOrFail($id);

        // Aba inicial: a pedida na URL; senão Documentos quando há documento aguardando análise
        $aba = $request->query('aba');
        if (!in_array($aba, ['geral', 'editar', 'documentos', 'processos'], true)) {
            $temPendente = collect($receituario->documentosDaAnalise())->contains(fn ($d) => $receituario->statusDocumento($d) === 'pendente');
            $aba = $receituario->isSolicitacaoExterna() && $temPendente ? 'documentos' : 'geral';
        }
        if ($request->old('_form') === 'editar') {
            $aba = 'editar';
        }

        $municipios = Municipio::orderBy('nome')->get(['id', 'nome']);

        return view('receituarios.show', compact('receituario', 'aba', 'municipios'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        // Verifica permissão
        if ($redirect = $this->verificarPermissao()) {
            return $redirect;
        }
        
        // A edição fica na aba "Editar dados" da página do cadastro
        return redirect()->route('admin.receituarios.show', ['id' => Receituario::findOrFail($id)->id, 'aba' => 'editar']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id, \App\Services\ReceituarioCadastroService $cadastro)
    {
        // Verifica permissão
        if ($redirect = $this->verificarPermissao()) {
            return $redirect;
        }

        $receituario = Receituario::findOrFail($id);

        $rules = [
            'observacoes' => 'nullable|string|max:5000',
            'telefone' => 'nullable|string|max:20',
            'telefone2' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'endereco' => 'nullable|string|max:255',
            'cep' => 'nullable|string|max:10',
            'municipio_id' => 'nullable|exists:municipios,id',
        ];

        // Mesmas regras do store baseadas no tipo
        if (in_array($receituario->tipo, ['medico', 'talidomida'])) {
            $rules['nome'] = 'required|string|max:255';
            $rules['cpf'] = 'required|string|max:14';
            $rules['telefone'] = 'required|string|max:20';
            $rules['especialidade'] = 'nullable|string|max:255';
            $rules['numero_conselho_classe'] = ($receituario->tipo === 'medico' ? 'required' : 'nullable') . '|string|max:50';
            $rules['numero_crm'] = ($receituario->tipo === 'talidomida' ? 'required' : 'nullable') . '|string|max:50';
            $rules['locais_trabalho'] = 'nullable|array|max:20';
            $rules['locais_trabalho.*.nome'] = 'nullable|string|max:255';
            $rules['locais_trabalho.*.municipio'] = 'nullable|string|max:255';
            $rules['locais_trabalho.*.cep'] = 'nullable|string|max:10';
        }

        if (in_array($receituario->tipo, ['instituicao', 'secretaria'])) {
            $rules['razao_social'] = 'required|string|max:255';
            $rules['cnpj'] = 'required|string|max:18';
        }

        if ($receituario->tipo === 'instituicao') {
            $rules['responsavel_nome'] = 'nullable|string|max:255';
            $rules['responsavel_cpf'] = 'nullable|string|max:14';
            $rules['responsavel_crm'] = 'nullable|string|max:50';
            $rules['responsavel_especialidade'] = 'nullable|string|max:255';
            $rules['responsavel_telefone'] = 'nullable|string|max:20';
        }

        $validated = $request->validate($rules, [
            'nome.required' => 'Informe o nome do profissional.',
            'cpf.required' => 'Informe o CPF.',
            'telefone.required' => 'Informe o telefone.',
            'numero_conselho_classe.required' => 'Informe o número do conselho de classe.',
            'numero_crm.required' => 'Informe o número do CRM.',
        ]);

        if (array_key_exists('locais_trabalho', $rules)) {
            $validated['locais_trabalho'] = array_values(array_filter($request->input('locais_trabalho', []), fn ($local) => !empty($local['nome'])));
        }
        if (isset($validated['cpf'])) {
            $validated['cpf'] = preg_replace('/\D/', '', $validated['cpf']);
        }

        $validated['usuario_atualizacao_id'] = Auth::guard('interno')->user()->id;

        \Illuminate\Support\Facades\DB::transaction(function () use ($receituario, $validated, $cadastro) {
            $receituario->update($validated);
            // O cadastro interno (onde ficam os processos) acompanha os dados do profissional
            $cadastro->sincronizarEstabelecimento($receituario);
        });

        return redirect()
            ->route('admin.receituarios.show', ['id' => $receituario->id, 'aba' => 'geral'])
            ->with('success', 'Dados do cadastro atualizados.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Verifica permissão
        if ($redirect = $this->verificarPermissao()) {
            return $redirect;
        }
        
        // Remover cadastro: somente administrador
        if (!auth('interno')->user()->isAdmin()) {
            return back()->with('error', 'Somente o administrador pode remover cadastros de receituário.');
        }

        $receituario = Receituario::with('estabelecimento.processos')->findOrFail($id);
        $nome = $receituario->identificador;

        // Leva junto o cadastro interno e os processos de receituário dele (exclusão lógica, recuperável)
        \Illuminate\Support\Facades\DB::transaction(function () use ($receituario) {
            if ($receituario->estabelecimento?->oculto_receituario) {
                $receituario->estabelecimento->processos->each->delete();
                $receituario->estabelecimento->delete();
            }
            $receituario->update(['usuario_atualizacao_id' => auth('interno')->id()]);
            $receituario->delete();
        });

        return redirect()
            ->route('admin.receituarios.index', request()->only(['status', 'tipo', 'busca', 'page']))
            ->with('success', 'Cadastro de ' . $nome . ' removido.');
    }
    
    /**
     * Busca CNPJ na API da Receita Federal
     */
    public function buscarCnpj(Request $request)
    {
        // Verifica permissão
        if ($redirect = $this->verificarPermissao()) {
            return $redirect;
        }
        
        $cnpj = preg_replace('/[^0-9]/', '', $request->cnpj);
        
        try {
            $response = Http::timeout(10)->get("https://brasilapi.com.br/api/cnpj/v1/{$cnpj}");
            
            if ($response->successful()) {
                $data = $response->json();
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'razao_social' => $data['razao_social'] ?? null,
                        'nome_fantasia' => $data['nome_fantasia'] ?? null,
                        'cnpj' => $data['cnpj'] ?? null,
                        'municipio' => $data['municipio'] ?? null,
                        'uf' => $data['uf'] ?? null,
                        'cep' => $data['cep'] ?? null,
                        'logradouro' => $data['logradouro'] ?? null,
                        'numero' => $data['numero'] ?? null,
                        'complemento' => $data['complemento'] ?? null,
                        'bairro' => $data['bairro'] ?? null,
                        'telefone' => $data['ddd_telefone_1'] ?? null,
                        'email' => $data['email'] ?? null,
                    ]
                ]);
            }
            
            return response()->json(['success' => false, 'message' => 'CNPJ não encontrado'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao buscar CNPJ'], 500);
        }
    }
    
    /**
     * Criar processo de receituário
     */
    public function criarProcesso($id)
    {
        // Verifica permissão
        if ($redirect = $this->verificarPermissao()) {
            return $redirect;
        }
        
        $receituario = Receituario::findOrFail($id);
        
        if ($receituario->processo_id) {
            return redirect()
                ->back()
                ->with('error', 'Este receituário já possui um processo vinculado.');
        }
        
        // Aqui você pode implementar a lógica de criação do processo
        // Por enquanto, vou deixar como placeholder
        
        return redirect()
            ->back()
            ->with('info', 'Funcionalidade de criar processo será implementada em breve.');
    }

    /**
     * Abre a carteira do conselho enviada pelo profissional (área da empresa)
     */
    public function carteira($id, string $lado = 'frente')
    {
        if ($redirect = $this->verificarPermissao()) {
            return $redirect;
        }

        return Receituario::findOrFail($id)->respostaDocumento($lado);
    }

    /**
     * Abre o comprovante de endereço enviado
     */
    public function comprovante($id)
    {
        if ($redirect = $this->verificarPermissao()) {
            return $redirect;
        }

        return Receituario::findOrFail($id)->respostaDocumento('comprovante');
    }

    /**
     * Mostra a página com o PDF gerado e instruções de assinatura
     */
    public function pdfGerado($id)
    {
        // Verifica permissão
        if ($redirect = $this->verificarPermissao()) {
            return $redirect;
        }
        
        $receituario = Receituario::with(['municipio'])->findOrFail($id);
        
        return view('receituarios.pdf-gerado', compact('receituario'));
    }

    /**
     * Gera o PDF do receituário para assinatura
     */
    public function gerarPdf($id)
    {
        // Verifica permissão
        if ($redirect = $this->verificarPermissao()) {
            return $redirect;
        }
        
        $receituario = Receituario::with(['municipio'])->findOrFail($id);
        
        // Define o template baseado no tipo
        $templates = [
            'medico' => 'receituarios.pdf.medico',
            'instituicao' => 'receituarios.pdf.instituicao',
            'secretaria' => 'receituarios.pdf.secretaria',
            'talidomida' => 'receituarios.pdf.talidomida',
        ];
        
        $template = $templates[$receituario->tipo] ?? 'receituarios.pdf.medico';
        
        // Gera o PDF
        $pdf = Pdf::loadView($template, ['receituario' => $receituario])
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);
        
        // Nome do arquivo
        $nomeArquivo = 'receituario_' . $receituario->tipo . '_' . $receituario->id . '.pdf';
        
        // Retorna o PDF para visualização no navegador
        return $pdf->stream($nomeArquivo);
    }
}
