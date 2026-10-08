<?php

namespace App\Services;

use App\Models\Estabelecimento;
use App\Models\Municipio;
use App\Models\Processo;
use App\Models\Receituario;
use App\Models\TipoProcesso;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cadastro de receituário aprovado pela Vigilância Sanitária e seus processos.
 *
 * Todo processo do sistema pertence a um estabelecimento. O profissional do receituário (pessoa física)
 * ganha um cadastro interno — estabelecimento oculto (oculto_receituario) — que não aparece nas listas
 * de estabelecimentos; os processos de receituário ficam nele e são abertos só pela área de Receituários.
 */
class ReceituarioCadastroService
{
    /**
     * Tipos de processo que a empresa pode abrir pela área de Receituários.
     */
    public function tiposDisponiveis(): Collection
    {
        return TipoProcesso::query()
            ->where('ativo', true)
            ->where('exclusivo_receituario', true)
            ->where('usuario_externo_pode_abrir', true)
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();
    }

    /**
     * Cadastro interno do profissional (cria na primeira vez).
     */
    public function garantirEstabelecimento(Receituario $receituario): Estabelecimento
    {
        if ($receituario->estabelecimento_id && ($existente = Estabelecimento::find($receituario->estabelecimento_id))) {
            return $existente;
        }

        $estabelecimento = Estabelecimento::create($this->dadosEstabelecimento($receituario) + [
            'usuario_externo_id' => $receituario->usuario_externo_id,
            'status' => 'aprovado',
            'ativo' => true,
            'aprovado_em' => now(),
            'atividades_exercidas' => [],
            'oculto_receituario' => true,
            // Receituários são da Vigilância Sanitária Estadual
            'competencia_manual' => 'estadual',
            'motivo_alteracao_competencia' => 'Cadastro interno do receituário #' . $receituario->id,
        ]);

        $receituario->update(['estabelecimento_id' => $estabelecimento->id]);

        return $estabelecimento;
    }

    /**
     * Leva para o cadastro interno os dados do profissional alterados pela Vigilância.
     */
    public function sincronizarEstabelecimento(Receituario $receituario): void
    {
        if ($receituario->estabelecimento_id) {
            Estabelecimento::whereKey($receituario->estabelecimento_id)->update($this->dadosEstabelecimento($receituario));
        }
    }

    /**
     * Identificação e endereço do cadastro interno, a partir dos dados do receituário.
     */
    private function dadosEstabelecimento(Receituario $receituario): array
    {
        $nome = $receituario->identificador ?: 'PROFISSIONAL';
        $municipio = $receituario->municipio_id ? Municipio::find($receituario->municipio_id) : null;
        $documento = preg_replace('/\D/', '', (string) ($receituario->cpf ?: $receituario->cnpj));

        return [
            'tipo_pessoa' => $receituario->cnpj && !$receituario->cpf ? 'juridica' : 'fisica',
            'cpf' => $receituario->cpf ? $documento : null,
            'cnpj' => !$receituario->cpf && $receituario->cnpj ? $documento : null,
            'nome_completo' => $nome,
            'razao_social' => $receituario->razao_social,
            'nome_fantasia' => $nome,
            // Campos obrigatórios da tabela: o endereço do receituário vem numa linha só
            'endereco' => $receituario->endereco ?: ($receituario->endereco_residencial ?: 'NÃO INFORMADO'),
            'numero' => 'S/N',
            'bairro' => '-',
            'cidade' => $municipio?->nome ?? ($receituario->municipio ?: 'NÃO INFORMADO'),
            'estado' => $municipio?->uf ?? 'TO',
            'cep' => preg_replace('/\D/', '', (string) $receituario->cep) ?: '00000000',
            'municipio' => $municipio?->nome ?? $receituario->municipio,
            'municipio_id' => $receituario->municipio_id,
            'telefone' => preg_replace('/\D/', '', (string) $receituario->telefone) ?: null,
            'email' => $receituario->email,
        ];
    }

    /**
     * Abre um processo de receituário para o cadastro aprovado.
     */
    public function abrirProcesso(Receituario $receituario, TipoProcesso $tipo, ?int $usuarioExternoId, ?string $observacao = null): Processo
    {
        return DB::transaction(function () use ($receituario, $tipo, $usuarioExternoId, $observacao) {
            $estabelecimento = $this->garantirEstabelecimento($receituario);
            $numero = Processo::gerarNumeroProcesso(date('Y'));

            $dados = [
                'estabelecimento_id' => $estabelecimento->id,
                'usuario_externo_id' => $usuarioExternoId,
                'aberto_por_externo' => (bool) $usuarioExternoId,
                'tipo' => $tipo->codigo,
                'ano' => $numero['ano'],
                'numero_sequencial' => $numero['numero_sequencial'],
                'numero_processo' => $numero['numero_processo'],
                'status' => 'aberto',
                'observacoes' => $observacao,
            ];
            if ($setor = $tipo->resolverSetorInicial($estabelecimento)) {
                $dados['setor_atual'] = $setor->codigo;
            }

            $processo = Processo::create($dados);
            $receituario->update(['processo_id' => $processo->id]);

            return $processo;
        });
    }

    /**
     * Motivo para não poder abrir o tipo agora (null = pode abrir). Mesmas regras de único/anual.
     */
    public function bloqueioAbertura(Receituario $receituario, TipoProcesso $tipo): ?string
    {
        if (!$receituario->isAprovado()) {
            return 'O cadastro precisa estar aprovado pela Vigilância Sanitária.';
        }
        if (!$receituario->estabelecimento_id) {
            return null;
        }

        $existentes = Processo::where('estabelecimento_id', $receituario->estabelecimento_id)->where('tipo', $tipo->codigo);

        if ($tipo->unico_por_estabelecimento && (clone $existentes)->exists()) {
            return "Já existe um processo de {$tipo->nome} para este cadastro.";
        }
        if ($tipo->anual && (clone $existentes)->where('ano', date('Y'))->exists()) {
            return "Já existe um processo de {$tipo->nome} aberto em " . date('Y') . '.';
        }

        return null;
    }
}
