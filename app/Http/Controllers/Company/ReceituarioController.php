<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Models\Receituario;
use App\Models\UsuarioExterno;
use App\Services\LeitorComprovanteEnderecoService;
use App\Services\ReceituarioCadastroService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

/**
 * Solicitação de receituário pela área da empresa (usuário externo).
 * Cada usuário vê e acessa as solicitações que fez e os cadastros aos quais foi vinculado.
 */
class ReceituarioController extends Controller
{
    private const TIPOS = ['medico', 'instituicao', 'secretaria', 'talidomida'];

    public function index(Request $request)
    {
        $query = Receituario::with(['municipio', 'estabelecimento' => fn ($q) => $q->withCount('processos')])
            ->acessivelPor(auth('externo')->id());

        if ($request->filled('tipo') && in_array($request->tipo, self::TIPOS, true)) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('busca')) {
            $busca = trim($request->busca);
            $digitos = preg_replace('/\D/', '', $busca);
            $query->where(function ($q) use ($busca, $digitos) {
                $q->where('nome', 'ILIKE', "%{$busca}%")
                    ->orWhere('razao_social', 'ILIKE', "%{$busca}%");
                if ($digitos !== '') {
                    $q->orWhere('cpf', 'LIKE', "%{$digitos}%")->orWhere('cnpj', 'LIKE', "%{$digitos}%");
                }
            });
        }

        $receituarios = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $porStatus = Receituario::acessivelPor(auth('externo')->id())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $estatisticas = [
            'total' => (int) $porStatus->sum(),
            'pendente' => (int) ($porStatus['pendente'] ?? 0),
            'ativo' => (int) ($porStatus['ativo'] ?? 0),
            'rejeitado' => (int) ($porStatus['rejeitado'] ?? 0),
            'inativo' => (int) ($porStatus['inativo'] ?? 0),
        ];

        return view('company.receituarios.index', compact('receituarios', 'estatisticas'));
    }

    public function create(Request $request)
    {
        if ($request->get('tipo', 'medico') !== 'medico') {
            return redirect()->route('company.receituarios.index')->with(
                'error',
                'No momento, está disponível apenas o cadastro para Médico, Dentista ou Veterinário.'
            );
        }

        $tipo = 'medico';
        $municipios = Municipio::orderBy('nome')->get(['id', 'nome', 'codigo_ibge']);
        $usuario = auth('externo')->user();

        return view('company.receituarios.create', compact('tipo', 'municipios', 'usuario'));
    }

    public function store(Request $request)
    {
        $usuario = auth('externo')->user();

        if ($request->input('tipo') !== 'medico') {
            return redirect()->route('company.receituarios.index')->with(
                'error',
                'No momento, está disponível apenas o cadastro para Médico, Dentista ou Veterinário.'
            );
        }

        // Profissional já cadastrado (por outra pessoa ou pela Vigilância): não cria um segundo cadastro
        $cpfInformado = preg_replace('/\D/', '', (string) ($request->input('solicitante') === 'proprio' ? $usuario->cpf : $request->input('cpf')));
        if (strlen($cpfInformado) === 11 && ($existente = $this->cadastroExistente($cpfInformado))) {
            return back()->withInput($request->except(['carteira_conselho', 'carteira_conselho_verso', 'comprovante_endereco']))
                ->withErrors(['cpf' => $this->mensagemCadastroExistente($existente)]);
        }

        $rules = [
            'tipo' => 'required|in:' . implode(',', self::TIPOS),
        ];

        if ($request->tipo === 'medico') {
            // "proprio" = o próprio usuário logado é o profissional; "terceiro" = em nome de outro profissional
            $rules['solicitante'] = 'required|in:proprio,terceiro';
        }

        if (in_array($request->tipo, ['medico', 'talidomida'], true)) {
            $rules['nome'] = 'required|string|max:255';
            $rules['cpf'] = 'required|string|max:14';
            $rules['especialidade'] = ['required', 'string', Rule::in(Receituario::ESPECIALIDADES)];
            $rules['telefone'] = 'required|string|max:20';
            $rules['telefone2'] = 'nullable|string|max:20';
            $rules['email'] = 'nullable|email|max:255';
            $rules['numero_conselho_classe'] = ($request->tipo === 'medico' ? 'required' : 'nullable') . '|string|max:50';
            $rules['numero_crm'] = ($request->tipo === 'talidomida' ? 'required' : 'nullable') . '|string|max:50';
            $rules['endereco'] = 'nullable|string|max:255';
            $rules['endereco_residencial'] = 'nullable|string|max:255';
            $rules['cep'] = 'nullable|string|max:10';
            $rules['municipio_id'] = 'nullable|exists:municipios,id';
        }

        if (in_array($request->tipo, ['instituicao', 'secretaria'], true)) {
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

        // Carteira do conselho (PDF ou foto): obrigatória para médico/dentista/veterinário
        if (in_array($request->tipo, ['medico', 'talidomida'], true)) {
            $rules['carteira_conselho'] = ($request->tipo === 'medico' ? 'required' : 'nullable') . '|file|mimes:pdf,jpg,jpeg,png,webp|max:10240';
            $rules['carteira_conselho_verso'] = 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240';
            $rules['carteira_leitura'] = 'nullable|string|max:3000';

            // Comprovante de endereço (água, energia ou telefone fixo): obrigatório para médico/dentista/veterinário
            $rules['comprovante_endereco'] = ($request->tipo === 'medico' ? 'required' : 'nullable') . '|file|mimes:pdf,jpg,jpeg,png,webp|max:10240';
            $rules['comprovante_leitura'] = 'nullable|string|max:3000';
            $rules['comprovante_titular'] = 'nullable|in:proprio,terceiro';
            $rules['comprovante_titular_nome'] = 'nullable|string|max:255';
            $rules['declaracao_endereco_vinculo'] = ['nullable', Rule::in(array_keys(LeitorComprovanteEnderecoService::VINCULOS))];
            $rules['declaracao_endereco_aceite'] = 'nullable|in:1';
        }

        $validated = $request->validate($rules, [
            'solicitante.required' => 'Informe se o receituário é para você ou para outro profissional.',
            'nome.required' => 'Informe o nome do profissional.',
            'cpf.required' => 'Informe o CPF do profissional.',
            'telefone.required' => 'Informe o telefone do profissional.',
            'especialidade.required' => 'Selecione uma especialidade ou área de atuação.',
            'especialidade.in' => 'Selecione uma especialidade ou área de atuação da lista.',
            'numero_conselho_classe.required' => 'Informe o número do conselho de classe.',
            'numero_crm.required' => 'Informe o número do CRM.',
            'carteira_conselho.required' => 'Envie a carteira do conselho (CRM, CRO ou CRMV) do profissional, em PDF ou foto.',
            'carteira_conselho.mimes' => 'A carteira do conselho deve ser PDF ou imagem (JPG, PNG ou WEBP).',
            'carteira_conselho.max' => 'A carteira do conselho deve ter no máximo 10 MB.',
            'carteira_conselho_verso.mimes' => 'O verso da carteira deve ser PDF ou imagem (JPG, PNG ou WEBP).',
            'carteira_conselho_verso.max' => 'O verso da carteira deve ter no máximo 10 MB.',
            'comprovante_endereco.required' => 'Envie o comprovante de endereço (conta de água, energia ou telefone fixo).',
            'comprovante_endereco.mimes' => 'O comprovante de endereço deve ser PDF ou imagem (JPG, PNG ou WEBP).',
            'comprovante_endereco.max' => 'O comprovante de endereço deve ter no máximo 10 MB.',
        ]);

        $this->validarCarteira($request, ($validated['solicitante'] ?? null) === 'proprio' ? $usuario->cpf : ($validated['cpf'] ?? ''));

        $solicitante = $validated['solicitante'] ?? null;
        $declaracaoEndereco = $this->declaracaoEndereco(
            $request, $validated, $solicitante === 'proprio' ? $usuario->nome : (string) ($validated['nome'] ?? '')
        );

        unset(
            $validated['solicitante'], $validated['carteira_conselho'], $validated['carteira_conselho_verso'], $validated['carteira_leitura'],
            $validated['comprovante_endereco'], $validated['comprovante_leitura'], $validated['comprovante_titular'],
            $validated['comprovante_titular_nome'], $validated['declaracao_endereco_vinculo'], $validated['declaracao_endereco_aceite']
        );

        // Arquivos da carteira e do comprovante (disco privado) + o que foi lido deles, para conferência da vigilância
        if ($request->hasFile('carteira_conselho')) {
            $validated = array_merge($validated, $this->arquivosCarteira($request));
        }
        if ($request->hasFile('comprovante_endereco')) {
            $validated = array_merge($validated, $this->arquivosComprovante($request, $declaracaoEndereco));
        }

        // Usando o próprio cadastro: nome e CPF vêm sempre do usuário logado (não do formulário)
        if ($solicitante === 'proprio') {
            $validated['nome'] = mb_strtoupper($usuario->nome, 'UTF-8');
            $validated['cpf'] = $usuario->cpf;
        }

        foreach (['cpf', 'cnpj', 'responsavel_cpf'] as $campo) {
            if (!empty($validated[$campo])) {
                $validated[$campo] = preg_replace('/\D/', '', $validated[$campo]);
            }
        }
        foreach (['nome', 'razao_social', 'responsavel_nome'] as $campo) {
            if (!empty($validated[$campo])) {
                $validated[$campo] = mb_strtoupper(trim($validated[$campo]), 'UTF-8');
            }
        }

        if ($request->has('locais_trabalho')) {
            $validated['locais_trabalho'] = array_values(array_filter((array) $request->locais_trabalho, fn ($local) => !empty($local['nome'])));
        }

        $validated['usuario_externo_id'] = $usuario->id;
        $validated['solicitante_proprio'] = $solicitante ? $solicitante === 'proprio' : null;
        // Cadastro do profissional: vai direto para a análise da Vigilância (carteira + comprovante).
        // A requisição de receituário é feita depois, dentro do processo de receituário.
        $validated['status'] = 'pendente';

        $receituario = Receituario::create($validated);

        return redirect()
            ->route('company.receituarios.show', $receituario->id)
            ->with('success', 'Cadastro do profissional enviado! A Vigilância Sanitária vai analisar os documentos.');
    }

    public function show(Request $request, $id, ReceituarioCadastroService $cadastro)
    {
        $receituario = $this->doUsuario($id)->load(['municipio', 'estabelecimento.processos', 'analisadoPor:id,nome', 'usuarioExterno:id,nome,email,cpf', 'usuariosVinculados']);
        $tiposProcesso = $receituario->isAprovado() ? $cadastro->tiposDisponiveis() : collect();
        $aba = in_array($request->query('aba'), ['documentos', 'processos', 'usuarios'], true) ? $request->query('aba') : 'geral';

        return view('company.receituarios.show', compact('receituario', 'tiposProcesso', 'aba'));
    }

    public function pdfGerado($id)
    {
        $receituario = $this->doUsuario($id)->load('municipio');

        return view('company.receituarios.pdf-gerado', compact('receituario'));
    }

    /**
     * Documento do cadastro rejeitado pela Vigilância: a correção é feita no MESMO passo do cadastro
     * (carteira → Passo 1 · Dados pessoais; comprovante → Passo 2 · Endereço), com os dados atuais preenchidos.
     */
    public function corrigir(Request $request, $id, string $documento)
    {
        $receituario = $this->doUsuario($id)->load('municipio');
        if ($redirect = $this->semCorrecaoPendente($receituario, $documento)) {
            return $redirect;
        }

        // Os campos do passo vêm preenchidos com o cadastro (ou com o que foi digitado, se voltou com erro)
        if (!$request->session()->hasOldInput()) {
            $request->session()->now('_old_input', [
                'tipo' => $receituario->tipo,
                'nome' => $receituario->nome,
                'cpf' => $receituario->cpf_formatado ?? $receituario->cpf,
                'telefone' => $receituario->telefone,
                'telefone2' => $receituario->telefone2,
                'email' => $receituario->email,
                'especialidade' => $receituario->especialidade,
                'numero_conselho_classe' => $receituario->numero_conselho_classe,
                'numero_crm' => $receituario->numero_crm,
                'cep' => $receituario->cep,
                'endereco' => $receituario->endereco,
                'endereco_residencial' => $receituario->endereco_residencial,
                'municipio_id' => $receituario->municipio_id,
                'locais_trabalho' => $receituario->locais_trabalho ?? [],
            ]);
        }

        $tipo = $receituario->tipo;
        $municipios = Municipio::orderBy('nome')->get(['id', 'nome', 'codigo_ibge']);
        $usuario = auth('externo')->user();
        $passo = $documento === 'carteira' ? 0 : 1;
        $motivo = $receituario->analiseDocumento($documento)['motivo'] ?? null;

        return view('company.receituarios.corrigir', compact('receituario', 'documento', 'tipo', 'municipios', 'usuario', 'passo', 'motivo'));
    }

    /**
     * Salva a correção: novo arquivo + dados do passo. O documento volta para a análise da Vigilância.
     */
    public function salvarCorrecao(Request $request, $id, string $documento)
    {
        $receituario = $this->doUsuario($id);
        if ($redirect = $this->semCorrecaoPendente($receituario, $documento)) {
            return $redirect;
        }

        $profissional = in_array($receituario->tipo, ['medico', 'talidomida'], true);
        $tipos = 'file|mimes:pdf,jpg,jpeg,png,webp|max:10240';
        $mensagens = [
            'nome.required' => 'Informe o nome do profissional.',
            'cpf.required' => 'Informe o CPF do profissional.',
            'telefone.required' => 'Informe o telefone do profissional.',
            'especialidade.in' => 'Selecione uma especialidade ou área de atuação da lista.',
            'numero_conselho_classe.required' => 'Informe o número do conselho de classe.',
            'numero_crm.required' => 'Informe o número do CRM.',
            'carteira_conselho.required' => 'Envie a nova carteira do conselho (frente e verso).',
            'comprovante_endereco.required' => 'Envie o novo comprovante de endereço.',
            '*.mimes' => 'Envie o arquivo em PDF ou imagem (JPG, PNG ou WEBP).',
            '*.max' => 'O arquivo deve ter no máximo 10 MB.',
        ];
        // "Sou o profissional": nome e CPF continuam sendo os do usuário (não mudam na correção)
        $dadosTravados = $receituario->solicitante_proprio === true;

        if ($documento === 'carteira') {
            $rules = [
                'telefone' => ($profissional ? 'required' : 'nullable') . '|string|max:20',
                'telefone2' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'especialidade' => ['nullable', 'string', Rule::in(Receituario::ESPECIALIDADES)],
                'numero_conselho_classe' => ($receituario->tipo === 'medico' ? 'required' : 'nullable') . '|string|max:50',
                'numero_crm' => ($receituario->tipo === 'talidomida' ? 'required' : 'nullable') . '|string|max:50',
                'carteira_conselho' => 'required|' . $tipos,
                'carteira_conselho_verso' => 'nullable|' . $tipos,
                'carteira_leitura' => 'nullable|string|max:3000',
            ];
            if (!$dadosTravados) {
                $rules['nome'] = 'required|string|max:255';
                $rules['cpf'] = 'required|string|max:14';
            }
            $validated = $request->validate($rules, $mensagens);
            $this->validarCarteira($request, $dadosTravados ? $receituario->cpf : $validated['cpf']);

            $dados = array_intersect_key($validated, array_flip(['nome', 'cpf', 'telefone', 'telefone2', 'email', 'especialidade', 'numero_conselho_classe', 'numero_crm']));
            if (isset($dados['nome'])) {
                $dados['nome'] = mb_strtoupper(trim($dados['nome']), 'UTF-8');
            }
            if (isset($dados['cpf'])) {
                $dados['cpf'] = preg_replace('/\D/', '', $dados['cpf']);
            }
            $dados += $this->arquivosCarteira($request);
            $antigos = [$receituario->carteira_conselho_path, $receituario->carteira_conselho_verso_path];
            $nomeAnterior = $receituario->carteira_conselho_nome;
        } else {
            $campoEndereco = $receituario->tipo === 'talidomida' ? 'endereco_residencial' : 'endereco';
            $validated = $request->validate([
                'cep' => 'nullable|string|max:10',
                $campoEndereco => 'nullable|string|max:255',
                'municipio_id' => 'nullable|exists:municipios,id',
                'comprovante_endereco' => 'required|' . $tipos,
                'comprovante_leitura' => 'nullable|string|max:3000',
                'comprovante_titular' => 'nullable|in:proprio,terceiro',
                'comprovante_titular_nome' => 'nullable|string|max:255',
                'declaracao_endereco_vinculo' => ['nullable', Rule::in(array_keys(LeitorComprovanteEnderecoService::VINCULOS))],
                'declaracao_endereco_aceite' => 'nullable|in:1',
            ], $mensagens);

            $declaracao = $this->declaracaoEndereco($request, $validated, (string) $receituario->nome);
            $dados = array_intersect_key($validated, array_flip(['cep', $campoEndereco, 'municipio_id']))
                + $this->arquivosComprovante($request, $declaracao);
            $antigos = [$receituario->comprovante_endereco_path];
            $nomeAnterior = $receituario->comprovante_endereco_nome;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($receituario, $dados, $documento, $nomeAnterior) {
            $receituario->fill($dados);
            $receituario->documentoReenviado($documento, $nomeAnterior); // salva e volta o documento para análise
        });
        \Illuminate\Support\Facades\Storage::disk('local')->delete(array_values(array_filter($antigos)));

        return redirect()->route('company.receituarios.show', [$receituario->id, 'aba' => 'documentos'])
            ->with('success', Receituario::frase($documento, 'corrigid') . ' e enviad' . Receituario::DOCUMENTOS[$documento]['a'] . ' de novo. A Vigilância Sanitária vai analisar.');
    }

    /**
     * Só corrige documento que foi rejeitado
     */
    private function semCorrecaoPendente(Receituario $receituario, string $documento)
    {
        if (!$receituario->temDocumento($documento) || $receituario->statusDocumento($documento) !== 'rejeitado') {
            return redirect()->route('company.receituarios.show', [$receituario->id, 'aba' => 'documentos'])
                ->with('error', Receituario::DOCUMENTOS[$documento]['nome'] . ': não há correção pendente.');
        }

        return null;
    }

    /**
     * Carteira do conselho: frente e verso enviados e CPF lido igual ao do profissional
     */
    private function validarCarteira(Request $request, ?string $cpfProfissional): void
    {
        // Frente e verso: o verso pode vir em arquivo separado, como 2ª página do mesmo PDF ou na mesma
        // foto/página da frente (cédula antiga aberta — marcado como "frente e verso no mesmo arquivo")
        if ($request->hasFile('carteira_conselho') && !$request->hasFile('carteira_conselho_verso')
            && !$request->boolean('carteira_frente_verso_juntos')
            && !$this->pdfComVariasPaginas($request->file('carteira_conselho'))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'carteira_conselho_verso' => 'Envie também o verso da carteira (ou um PDF com a frente e o verso).',
            ]);
        }

        // A carteira tem que ser do profissional: CPF lido (e válido) igual ao CPF do receituário
        $lido = json_decode((string) $request->input('carteira_leitura'), true);
        $cpf = preg_replace('/\D/', '', (string) $cpfProfissional);
        if (is_array($lido) && !empty($lido['cpf_valido']) && $cpf && preg_replace('/\D/', '', (string) ($lido['cpf'] ?? '')) !== $cpf) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'carteira_conselho' => 'O CPF da carteira do conselho é diferente do CPF do profissional. Envie a carteira do profissional certo.',
            ]);
        }
    }

    /**
     * Guarda a carteira (frente e, se houver, verso) e o que foi lido dela
     */
    private function arquivosCarteira(Request $request): array
    {
        $pasta = 'receituarios/carteiras/' . now()->format('Y/m');
        $frente = $request->file('carteira_conselho');
        $verso = $request->file('carteira_conselho_verso');
        $leitura = json_decode((string) $request->input('carteira_leitura'), true);

        return [
            'carteira_conselho_path' => $frente->store($pasta, 'local'),
            'carteira_conselho_nome' => mb_substr($frente->getClientOriginalName(), 0, 255),
            'carteira_conselho_verso_path' => $verso?->store($pasta, 'local'),
            'carteira_conselho_verso_nome' => $verso ? mb_substr($verso->getClientOriginalName(), 0, 255) : null,
            'carteira_conselho_leitura' => is_array($leitura)
                ? array_intersect_key($leitura, array_flip(['conselho', 'uf', 'numero', 'especialidade', 'nome', 'cpf', 'cpf_valido', 'origem']))
                : null,
        ];
    }

    /**
     * Guarda o comprovante de endereço, o que foi lido dele e a declaração (quando está no nome de outra pessoa)
     */
    private function arquivosComprovante(Request $request, ?array $declaracao): array
    {
        $arquivo = $request->file('comprovante_endereco');
        $lido = json_decode((string) $request->input('comprovante_leitura'), true);

        return [
            'comprovante_endereco_path' => $arquivo->store('receituarios/comprovantes/' . now()->format('Y/m'), 'local'),
            'comprovante_endereco_nome' => mb_substr($arquivo->getClientOriginalName(), 0, 255),
            'comprovante_endereco_leitura' => is_array($lido)
                ? array_intersect_key($lido, array_flip(['titular', 'cep', 'endereco', 'municipio', 'uf', 'tipo', 'origem']))
                : null,
            'declaracao_endereco' => $declaracao,
        ];
    }

    public function documentoAssinado($id)
    {
        return $this->doUsuario($id)->respostaDocumento('assinado');
    }

    /**
     * Abre um processo de receituário (só pela área de Receituários e com o cadastro aprovado).
     */
    public function abrirProcesso(Request $request, $id, ReceituarioCadastroService $cadastro)
    {
        $receituario = $this->doUsuario($id);

        $validated = $request->validate([
            'tipo_processo_id' => 'required|integer',
            'observacao' => 'nullable|string|max:1000',
        ]);

        $tipo = $cadastro->tiposDisponiveis()->firstWhere('id', (int) $validated['tipo_processo_id']);
        if (!$tipo) {
            return back()->with('error', 'Tipo de processo indisponível na área de Receituários.');
        }

        if ($motivo = $cadastro->bloqueioAbertura($receituario, $tipo)) {
            return back()->with('error', $motivo);
        }

        $processo = $cadastro->abrirProcesso($receituario, $tipo, auth('externo')->id(), $validated['observacao'] ?? null);

        return redirect()->route('company.processos.show', $processo->id)
            ->with('success', "Processo de {$tipo->nome} aberto! Envie os documentos solicitados.");
    }

    public function gerarPdf($id)
    {
        $receituario = $this->doUsuario($id)->load('municipio');

        $templates = [
            'medico' => 'receituarios.pdf.medico',
            'instituicao' => 'receituarios.pdf.instituicao',
            'secretaria' => 'receituarios.pdf.secretaria',
            'talidomida' => 'receituarios.pdf.talidomida',
        ];

        $pdf = Pdf::loadView($templates[$receituario->tipo] ?? 'receituarios.pdf.medico', ['receituario' => $receituario])
            ->setPaper('a4', 'portrait');

        return $pdf->stream('receituario_' . $receituario->tipo . '_' . $receituario->id . '.pdf');
    }

    /**
     * Busca CNPJ (instituição / secretaria) — mesma consulta usada pela vigilância
     */
    public function buscarCnpj(Request $request)
    {
        $cnpj = preg_replace('/[^0-9]/', '', (string) $request->cnpj);
        if (strlen($cnpj) !== 14) {
            return response()->json(['success' => false, 'message' => 'CNPJ inválido'], 422);
        }

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
                    ],
                ]);
            }

            return response()->json(['success' => false, 'message' => 'CNPJ não encontrado'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao buscar CNPJ'], 500);
        }
    }

    /**
     * Lê a carteira do conselho e devolve número do conselho e especialidade para preencher o formulário.
     * Recebe o PDF (texto extraído no servidor) ou o texto já reconhecido por OCR no navegador.
     */
    public function lerCarteira(Request $request, \App\Services\LeitorCarteiraConselhoService $leitor)
    {
        $request->validate([
            'arquivo' => 'nullable|file|mimes:pdf|max:10240',
            'texto' => 'nullable|string|max:20000',
        ]);

        $texto = (string) $request->input('texto', '');
        if ($request->hasFile('arquivo')) {
            $texto = $leitor->textoDoPdf($request->file('arquivo')->getRealPath());
        }

        // PDF escaneado (só imagem): o navegador faz o OCR e chama de novo enviando o texto
        if (mb_strlen(trim($texto)) < 15) {
            return response()->json(['success' => false, 'precisa_ocr' => $request->hasFile('arquivo')]);
        }

        $resultado = $leitor->interpretar($texto);

        return response()->json([
            'success' => $resultado['encontrou'],
            'origem' => $resultado['origem'],
            'dados' => $resultado['dados'],
        ]);
    }

    /**
     * Abre o arquivo da carteira do conselho enviado
     */
    public function carteira($id, string $lado = 'frente')
    {
        return $this->doUsuario($id)->respostaDocumento($lado);
    }

    /**
     * Comprovante de endereço no nome de outra pessoa: exige o vínculo com o titular e o aceite da declaração.
     * Devolve a declaração aceita (para guardar) ou null quando o comprovante está no nome do profissional.
     */
    private function declaracaoEndereco(Request $request, array $validated, string $nomeProfissional): ?array
    {
        if (!$request->hasFile('comprovante_endereco')) {
            return null;
        }

        $usuario = auth('externo')->user();
        $leitor = app(LeitorComprovanteEnderecoService::class);
        $lido = json_decode((string) $request->input('comprovante_leitura'), true) ?: [];
        $profissional = mb_strtoupper($nomeProfissional, 'UTF-8');
        $titularLido = trim((string) ($lido['titular'] ?? ''));
        $situacao = $validated['comprovante_titular'] ?? null;

        if ($titularLido === '' && !$situacao) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'comprovante_titular' => 'Informe se o comprovante de endereço está no nome do profissional.',
            ]);
        }

        $emNomeDeOutro = $titularLido !== '' ? !$leitor->nomesParecidos($titularLido, $profissional) : $situacao === 'terceiro';
        if (!$emNomeDeOutro) {
            return null;
        }

        $titular = $titularLido !== '' ? mb_strtoupper($titularLido, 'UTF-8') : mb_strtoupper(trim((string) ($validated['comprovante_titular_nome'] ?? '')), 'UTF-8');
        $vinculo = $validated['declaracao_endereco_vinculo'] ?? null;
        if ($titular === '' || !$vinculo || ($validated['declaracao_endereco_aceite'] ?? null) !== '1') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'declaracao_endereco_aceite' => 'O comprovante de endereço não está no nome do profissional: informe o titular, o vínculo com ele e aceite a declaração.',
            ]);
        }

        return [
            'titular' => $titular,
            'vinculo' => $vinculo,
            'texto' => self::textoDeclaracaoEndereco($profissional, $titular, LeitorComprovanteEnderecoService::VINCULOS[$vinculo]),
            'aceita_em' => now()->toIso8601String(),
            'ip' => $request->ip(),
            'usuario_externo_id' => $usuario->id,
        ];
    }

    public static function textoDeclaracaoEndereco(string $profissional, string $titular, string $vinculo): string
    {
        return "Declaro, sob as penas da lei (art. 299 do Código Penal), que {$profissional} reside ou trabalha no endereço informado nesta solicitação "
            . "e que o comprovante de endereço apresentado, em nome de {$titular} (vínculo: " . mb_strtolower($vinculo, 'UTF-8') . "), "
            . 'é verdadeiro e corresponde a esse endereço.';
    }

    /**
     * Lê o comprovante de endereço (texto extraído no navegador) e devolve titular, CEP, endereço e tipo da conta.
     */
    public function lerComprovante(Request $request, LeitorComprovanteEnderecoService $leitor)
    {
        $request->validate(['texto' => 'nullable|string|max:20000']);
        $texto = (string) $request->input('texto', '');

        if (mb_strlen(trim($texto)) < 15) {
            return response()->json(['success' => false]);
        }

        $resultado = $leitor->interpretar($texto);

        return response()->json([
            'success' => $resultado['encontrou'],
            'origem' => $resultado['origem'],
            'dados' => $resultado['dados'],
        ]);
    }

    /**
     * Abre o comprovante de endereço enviado
     */
    public function comprovante($id)
    {
        return $this->doUsuario($id)->respostaDocumento('comprovante');
    }

    /**
     * PDF com 2+ páginas (frente e verso no mesmo arquivo). Na dúvida, aceita.
     */
    private function pdfComVariasPaginas(?\Illuminate\Http\UploadedFile $arquivo): bool
    {
        if (!$arquivo || strtolower($arquivo->getClientOriginalExtension()) !== 'pdf') {
            return false;
        }
        $paginas = preg_match_all('#/Type\s*/Page(?![a-zA-Z])#', (string) file_get_contents($arquivo->getRealPath()));
        if ($paginas === 0) {
            try {
                $paginas = count((new \Smalot\PdfParser\Parser())->parseFile($arquivo->getRealPath())->getPages());
            } catch (\Throwable $e) {
                return true;
            }
        }

        return $paginas >= 2;
    }

    // ===================== Profissional já cadastrado =====================

    /**
     * Consulta (no passo 1 do cadastro) se o CPF do profissional já tem cadastro no sistema.
     */
    public function verificarCpf(Request $request)
    {
        $cpf = preg_replace('/\D/', '', (string) $request->query('cpf'));
        if (strlen($cpf) !== 11) {
            return response()->json(['existe' => false]);
        }

        $existente = $this->cadastroExistente($cpf);
        if (!$existente) {
            return response()->json(['existe' => false]);
        }

        $temAcesso = Receituario::acessivelPor(auth('externo')->id())->whereKey($existente->id)->exists();

        return response()->json([
            'existe' => true,
            'tem_acesso' => $temAcesso,
            'mensagem' => $this->mensagemCadastroExistente($existente),
            'url' => $temAcesso ? route('company.receituarios.show', $existente->id) : null,
        ]);
    }

    private function cadastroExistente(string $cpf): ?Receituario
    {
        return Receituario::whereIn('tipo', ['medico', 'talidomida'])->where('cpf', $cpf)->first();
    }

    private function mensagemCadastroExistente(Receituario $existente): string
    {
        if (Receituario::acessivelPor(auth('externo')->id())->whereKey($existente->id)->exists()) {
            return 'Este profissional já está cadastrado e você já tem acesso a esse cadastro. Abra-o em "Profissionais cadastrados".';
        }

        return 'Já existe um cadastro deste profissional no sistema. Entre em contato com a Vigilância Sanitária '
            . 'para tirar dúvidas ou pedir que você seja vinculado ao cadastro do profissional.';
    }

    // ===================== Usuários vinculados ao cadastro =====================

    public function usuariosBuscar(Request $request, $id)
    {
        $receituario = $this->doUsuario($id);
        $termo = trim((string) $request->query('q'));
        if (mb_strlen($termo) < 3) {
            return response()->json([]);
        }

        $digitos = preg_replace('/\D/', '', $termo);
        $excluir = $receituario->usuariosVinculados()->pluck('usuarios_externos.id')->push($receituario->usuario_externo_id)->filter()->all();

        $usuarios = UsuarioExterno::query()
            ->whereNotIn('id', $excluir)
            ->where(function ($q) use ($termo, $digitos) {
                $q->where('nome', 'ILIKE', "%{$termo}%")->orWhere('email', 'ILIKE', "%{$termo}%");
                if (strlen($digitos) >= 3) {
                    $q->orWhere('cpf', 'like', "%{$digitos}%");
                }
            })
            ->orderBy('nome')
            ->limit(10)
            ->get(['id', 'nome', 'email', 'cpf']);

        return response()->json($usuarios->map(fn ($u) => [
            'id' => $u->id,
            'nome' => $u->nome,
            'email' => $u->email,
            'cpf' => $u->cpf_formatado ?? $u->cpf,
        ]));
    }

    public function usuariosStore(Request $request, $id, ReceituarioCadastroService $cadastro)
    {
        $receituario = $this->doUsuario($id);
        $dados = $request->validate([
            'usuario_externo_id' => 'required|exists:usuarios_externos,id',
            'tipo_vinculo' => ['required', Rule::in(array_keys(Receituario::TIPOS_VINCULO))],
        ], [
            'usuario_externo_id.required' => 'Selecione o usuário que vai ter acesso ao cadastro.',
            'tipo_vinculo.required' => 'Informe se o usuário é o profissional ou funcionário.',
        ]);

        if ((int) $dados['usuario_externo_id'] === (int) $receituario->usuario_externo_id) {
            return back()->with('error', 'Este usuário já é quem cadastrou o profissional.');
        }

        $cadastro->vincularUsuario($receituario, (int) $dados['usuario_externo_id'], $dados['tipo_vinculo'], null, auth('externo')->id());

        return redirect()->route('company.receituarios.show', ['id' => $receituario->id, 'aba' => 'usuarios'])
            ->with('success', 'Usuário vinculado. Ele já pode acessar o cadastro e os processos do profissional.');
    }

    public function usuariosDestroy($id, $usuarioId, ReceituarioCadastroService $cadastro)
    {
        $receituario = $this->doUsuario($id);
        $cadastro->desvincularUsuario($receituario, (int) $usuarioId);

        if ((int) $usuarioId === (int) auth('externo')->id()) {
            return redirect()->route('company.receituarios.index')->with('success', 'Você saiu do cadastro do profissional.');
        }

        return redirect()->route('company.receituarios.show', ['id' => $receituario->id, 'aba' => 'usuarios'])
            ->with('success', 'Vínculo removido.');
    }

    /**
     * Receituário que o usuário logado cadastrou ou ao qual está vinculado (404 para os demais)
     */
    private function doUsuario($id): Receituario
    {
        return Receituario::acessivelPor(auth('externo')->id())->findOrFail($id);
    }
}
