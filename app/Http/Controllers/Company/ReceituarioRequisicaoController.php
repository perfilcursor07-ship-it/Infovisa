<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Estabelecimento;
use App\Models\Processo;
use App\Models\ProcessoEvento;
use App\Models\ReceituarioRequisicao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Requisições de notificação/numeração de receita feitas pela empresa
 * dentro de um processo de receituário. Não se aplica a outros tipos de processo.
 */
class ReceituarioRequisicaoController extends Controller
{
    public function create($processoId)
    {
        $processo = $this->processoReceituario($processoId);
        $this->garantirPodeSolicitar($processo);

        $receituario = $processo->estabelecimento->receituario;
        $requisitante = ReceituarioRequisicao::dadosRequisitante($receituario);
        $ultimaRequisicao = $processo->requisicoesReceituario()->where('status', '!=', 'cancelada')->first();
        $limites = ReceituarioRequisicao::limitesPara($receituario->especialidade);

        return view('company.processos.receituario-requisicoes.create', compact('processo', 'requisitante', 'ultimaRequisicao', 'limites'));
    }

    public function store(Request $request, $processoId)
    {
        $processo = $this->processoReceituario($processoId);
        $this->garantirPodeSolicitar($processo);

        // Um único aceite cobre todas as declarações e a assinatura eletrônica
        $regras = [
            'justificativa' => 'nullable|string|max:2000',
            'aceite' => 'accepted',
        ];
        foreach (array_keys(ReceituarioRequisicao::MODALIDADES) as $modalidade) {
            foreach (ReceituarioRequisicao::TIPOS_NOTIFICACAO as $tipo) {
                $regras["quantidades.{$modalidade}.{$tipo}"] = 'nullable|integer|min:0|max:' . ReceituarioRequisicao::QUANTIDADE_MAXIMA;
            }
        }

        $dados = $request->validate($regras, [
            'aceite.accepted' => 'Confirme as declarações e a assinatura eletrônica para enviar.',
            'quantidades.*.*.max' => 'A quantidade máxima por tipo é ' . ReceituarioRequisicao::QUANTIDADE_MAXIMA . '.',
            'quantidades.*.*.integer' => 'Informe as quantidades apenas com números inteiros.',
        ]);

        $quantidades = [];
        foreach (array_keys(ReceituarioRequisicao::MODALIDADES) as $modalidade) {
            foreach (ReceituarioRequisicao::TIPOS_NOTIFICACAO as $tipo) {
                $quantidades[$modalidade][$tipo] = (int) ($dados['quantidades'][$modalidade][$tipo] ?? 0);
            }
        }

        if (collect($quantidades)->flatten()->sum() === 0) {
            return back()->withInput()->withErrors(['quantidades' => 'Informe a quantidade de numerações de pelo menos um tipo de notificação.']);
        }

        $receituario = $processo->estabelecimento->receituario;

        // Acima do parâmetro da DVISA (ou tipo fora da especialidade): a Vigilância precisa da justificativa
        $acimaDoLimite = ReceituarioRequisicao::tiposQuePedemJustificativa($quantidades, $receituario->especialidade);
        if ($acimaDoLimite && trim((string) ($dados['justificativa'] ?? '')) === '') {
            return back()->withInput()->withErrors([
                'justificativa' => 'Explique por que precisa dessa quantidade de ' . implode(', ', $acimaDoLimite) . ' (acima do limite de referência).',
            ]);
        }
        $usuario = auth('externo')->user();

        $requisicao = DB::transaction(function () use ($processo, $receituario, $quantidades, $dados, $usuario, $request) {
            $numeracao = ReceituarioRequisicao::gerarNumero((int) now()->year);

            $requisicao = ReceituarioRequisicao::create($numeracao + [
                'processo_id' => $processo->id,
                'receituario_id' => $receituario->id,
                'quantidades' => $quantidades,
                'unidade' => 'numeracoes',
                'justificativa' => $dados['justificativa'] ?? null,
                'requisitante' => ReceituarioRequisicao::dadosRequisitante($receituario),
                'declaracoes' => collect(ReceituarioRequisicao::DECLARACOES)->map(fn ($texto) => ['texto' => $texto, 'aceito' => true])->all(),
                'usuario_externo_id' => $usuario->id,
                'assinado_em' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'status' => 'enviada',
            ]);

            ProcessoEvento::create([
                'processo_id' => $processo->id,
                'usuario_interno_id' => null,
                'tipo_evento' => 'requisicao_receituario_enviada',
                'titulo' => 'Requisição de receituário enviada',
                'descricao' => "Requisição nº {$requisicao->numero} enviada por {$usuario->nome} ({$requisicao->rotuloQuantidade()} solicitadas)",
                'dados_adicionais' => [
                    'requisicao_id' => $requisicao->id,
                    'numero' => $requisicao->numero,
                    'quantidades' => $quantidades,
                    'usuario_externo_id' => $usuario->id,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $requisicao;
        });

        return redirect()
            ->route('company.processos.receituario-requisicoes.show', [$processo->id, $requisicao->id])
            ->with('success', "Requisição nº {$requisicao->numero} enviada para a Vigilância Sanitária.");
    }

    public function show($processoId, $requisicaoId)
    {
        $processo = $this->processoReceituario($processoId);
        $requisicao = $processo->requisicoesReceituario()->with('usuarioExterno:id,nome')->findOrFail($requisicaoId);

        return view('company.processos.receituario-requisicoes.show', compact('processo', 'requisicao'));
    }

    public function cancelar(Request $request, $processoId, $requisicaoId)
    {
        $processo = $this->processoReceituario($processoId);
        $this->bloquearVisualizador($processo);

        $requisicao = $processo->requisicoesReceituario()->findOrFail($requisicaoId);

        if (!$requisicao->podeSerCancelada()) {
            return back()->with('error', 'Esta requisição já está com a Vigilância Sanitária e não pode mais ser cancelada.');
        }

        $requisicao->update(['status' => 'cancelada', 'cancelado_em' => now()]);

        ProcessoEvento::create([
            'processo_id' => $processo->id,
            'usuario_interno_id' => null,
            'tipo_evento' => 'requisicao_receituario_cancelada',
            'titulo' => 'Requisição de receituário cancelada',
            'descricao' => "Requisição nº {$requisicao->numero} cancelada pela empresa",
            'dados_adicionais' => ['requisicao_id' => $requisicao->id, 'usuario_externo_id' => auth('externo')->id()],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('company.processos.show', $processo->id)
            ->with('success', "Requisição nº {$requisicao->numero} cancelada.");
    }

    /**
     * Processo de receituário acessível pelo usuário externo logado.
     */
    private function processoReceituario($processoId): Processo
    {
        $usuarioId = auth('externo')->id();
        $estabelecimentoIds = Estabelecimento::where('usuario_externo_id', $usuarioId)
            ->orWhereHas('usuariosVinculados', fn ($q) => $q->where('usuario_externo_id', $usuarioId))
            ->pluck('id');

        $processo = Processo::whereIn('estabelecimento_id', $estabelecimentoIds)
            ->with(['tipoProcesso', 'estabelecimento.receituario'])
            ->findOrFail($processoId);

        abort_unless($processo->tipoProcesso?->usuario_externo_pode_visualizar, 403, 'Este tipo de processo não está disponível para visualização.');
        abort_unless($processo->isProcessoReceituario(), 404);

        return $processo;
    }

    private function garantirPodeSolicitar(Processo $processo): void
    {
        $this->bloquearVisualizador($processo);

        if ($processo->status === 'arquivado') {
            abort(redirect()->route('company.processos.show', $processo->id)
                ->with('error', 'O processo está arquivado. Não é possível enviar novas requisições.'));
        }

        $receituario = $processo->estabelecimento->receituario;
        if (!$receituario || !$receituario->isAprovado()) {
            abort(redirect()->route('company.processos.show', $processo->id)
                ->with('error', 'O cadastro de receituário precisa estar aprovado para solicitar notificações de receita.'));
        }

        // Uma requisição por vez: enquanto a anterior aguarda a Vigilância, não dá para pedir outra
        if ($emAndamento = ReceituarioRequisicao::emAndamentoPara($processo)) {
            abort(redirect()->route('company.processos.receituario-requisicoes.show', [$emAndamento->processo_id, $emAndamento->id])
                ->with('error', "A requisição nº {$emAndamento->numero} ainda está aguardando a análise da Vigilância Sanitária. "
                    . 'Aguarde a liberação' . ($emAndamento->podeSerCancelada() ? ' (ou cancele-a)' : '') . ' para fazer uma nova requisição.'));
        }
    }

    private function bloquearVisualizador(Processo $processo): void
    {
        if ($processo->estabelecimento?->usuarioEhVisualizador()) {
            abort(redirect()->route('company.processos.show', $processo->id)
                ->with('error', 'Acesso restrito: sua conta possui permissão apenas para visualização.'));
        }
    }
}
