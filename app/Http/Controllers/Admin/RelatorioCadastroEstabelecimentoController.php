<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estabelecimento;
use App\Models\Municipio;
use App\Models\UsuarioInterno;
use App\Support\CnaeCatalogo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Relatório de cadastro de estabelecimentos: quantos foram cadastrados no período
 * e a situação de cada um (ativo, inativo ou baixado), com evolução mês a mês.
 *
 * Situações (excludentes):
 *  - baixado → situação cadastral BAIXADA na Receita Federal
 *  - inativo → desativado no sistema (ativo = false)
 *  - ativo   → demais
 */
class RelatorioCadastroEstabelecimentoController extends Controller
{
    private const SITUACOES = [
        'ativo' => 'Ativo',
        'inativo' => 'Inativo',
        'baixado' => 'Baixado',
    ];

    public function index(Request $request)
    {
        $usuario = auth('interno')->user();
        $filtros = $this->filtros($request, $usuario);
        $linhas = $this->montarLinhas($usuario, $filtros);

        $indicadores = $this->indicadores($linhas);
        $graficoMensal = $this->graficoMensal($linhas, $filtros);
        $porMunicipio = $this->porMunicipio($linhas);
        $porTipoServico = $this->porTipoServico($linhas);

        $listagem = $linhas
            ->when($filtros['situacao'], fn ($c) => $c->where('situacao', $filtros['situacao']))
            ->sortByDesc(fn ($l) => $l['estabelecimento']->created_at)
            ->values();
        $estabelecimentos = $this->paginar($listagem, 25, $request);

        $municipios = ($usuario->isAdmin() || $usuario->isEstadual())
            ? Municipio::query()->orderBy('nome')->get(['id', 'nome'])
            : collect();

        return view('admin.relatorios.cadastro-estabelecimentos', compact(
            'filtros', 'indicadores', 'graficoMensal', 'porMunicipio', 'porTipoServico', 'estabelecimentos', 'municipios'
        ) + ['totalListagem' => $listagem->count(), 'situacoes' => self::SITUACOES, 'catalogoTipos' => CnaeCatalogo::tiposPorArea()]);
    }

    public function export(Request $request): StreamedResponse
    {
        $usuario = auth('interno')->user();
        $filtros = $this->filtros($request, $usuario);
        $linhas = $this->montarLinhas($usuario, $filtros)
            ->when($filtros['situacao'], fn ($c) => $c->where('situacao', $filtros['situacao']))
            ->sortByDesc(fn ($l) => $l['estabelecimento']->created_at)
            ->values();

        $nomeArquivo = 'cadastro-estabelecimentos-' . $filtros['inicio']->format('Y-m-d') . '-a-' . $filtros['fim']->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($linhas) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Estabelecimento', 'Razão social', 'CNPJ/CPF', 'Município', 'Tipo de serviço', 'Competência', 'Setor', 'Situação', 'Situação na Receita', 'Cadastrado em', 'Status do cadastro', 'Motivo da desativação'], ';');

            foreach ($linhas as $linha) {
                $e = $linha['estabelecimento'];
                fputcsv($out, [
                    $e->nome_fantasia ?: ($e->razao_social ?: $e->nome_completo),
                    $e->razao_social,
                    $e->documento_formatado,
                    $linha['municipio'],
                    CnaeCatalogo::nomeTipo($linha['tipo_servico']),
                    ucfirst($linha['competencia']),
                    $linha['setor'] === 'publico' ? 'Público' : 'Privado',
                    self::SITUACOES[$linha['situacao']],
                    $e->situacao_label,
                    $e->created_at?->format('d/m/Y'),
                    ucfirst((string) $e->status),
                    $e->motivo_desativacao,
                ], ';');
            }

            fclose($out);
        }, $nomeArquivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtros(Request $request, UsuarioInterno $usuario): array
    {
        // Período: atalhos ou datas personalizadas (padrão: ano atual até hoje)
        $periodo = $request->input('periodo', 'ano');
        [$inicio, $fim] = match ($periodo) {
            'mes' => [now()->startOfMonth(), now()],
            '12meses' => [now()->subMonths(11)->startOfMonth(), now()],
            'ano_passado' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            'tudo' => [Carbon::parse(Estabelecimento::min('created_at') ?? now())->startOfMonth(), now()],
            'personalizado' => [
                $this->data($request->input('inicio')) ?? now()->startOfYear(),
                $this->data($request->input('fim')) ?? now(),
            ],
            default => [now()->startOfYear(), now()],
        };
        if (!in_array($periodo, ['mes', '12meses', 'ano_passado', 'tudo', 'personalizado'], true)) {
            $periodo = 'ano';
        }
        if ($inicio->greaterThan($fim)) {
            [$inicio, $fim] = [$fim, $inicio];
        }

        $podeFiltrarMunicipio = $usuario->isAdmin() || $usuario->isEstadual();

        return [
            'periodo' => $periodo,
            'inicio' => $inicio->copy()->startOfDay(),
            'fim' => $fim->copy()->endOfDay(),
            'competencia' => $usuario->isAdmin() && in_array($request->input('competencia'), ['estadual', 'municipal'], true)
                ? $request->input('competencia') : null,
            'municipio_id' => $podeFiltrarMunicipio && $request->filled('municipio_id') ? $request->integer('municipio_id') : null,
            'setor' => in_array($request->input('setor'), ['publico', 'privado'], true) ? $request->input('setor') : null,
            'tipo_pessoa' => in_array($request->input('tipo_pessoa'), ['juridica', 'fisica'], true) ? $request->input('tipo_pessoa') : null,
            'cadastro' => $request->input('cadastro') === 'todos' ? 'todos' : 'aprovado',
            'situacao' => array_key_exists((string) $request->input('situacao'), self::SITUACOES) ? $request->input('situacao') : null,
            'tipo_servico' => $this->tipoServicoValido((string) $request->input('tipo_servico')),
            'busca' => trim((string) $request->input('busca')),
        ];
    }

    /**
     * Filtro de tipo de serviço: "area:saude", "tipo:hospitais" ou "outros"
     */
    private function tipoServicoValido(string $valor): ?string
    {
        if ($valor === 'outros') {
            return $valor;
        }
        if (str_starts_with($valor, 'area:') && isset(CnaeCatalogo::AREAS[substr($valor, 5)])) {
            return $valor;
        }
        if (str_starts_with($valor, 'tipo:') && isset(CnaeCatalogo::TIPOS[substr($valor, 5)])) {
            return $valor;
        }

        return null;
    }

    private function correspondeTipoServico(array $linha, ?string $filtro): bool
    {
        return match (true) {
            !$filtro => true,
            $filtro === 'outros' => $linha['tipo_servico'] === null,
            str_starts_with($filtro, 'area:') => $linha['area'] === substr($filtro, 5),
            default => $linha['tipo_servico'] === substr($filtro, 5),
        };
    }

    /**
     * Tipo de serviço do estabelecimento pela atividade principal; se ela não estiver
     * no catálogo, a primeira atividade exercida que estiver; senão "Outras atividades".
     */
    private function tipoServico(Estabelecimento $e): ?string
    {
        $atividades = collect($e->atividades_exercidas ?? [])
            ->map(fn ($a) => is_array($a) ? $a : ['codigo' => $a])
            ->reject(fn ($a) => in_array(strtoupper((string) ($a['codigo'] ?? '')), ['PROJ_ARQ', 'ANAL_ROT'], true));

        $codigos = $atividades->sortByDesc(fn ($a) => !empty($a['principal']))
            ->pluck('codigo')
            ->prepend($atividades->isEmpty() ? $e->cnae_fiscal : null)
            ->map(fn ($c) => preg_replace('/\D/', '', (string) $c))
            ->filter();

        foreach ($codigos as $codigo) {
            if ($tipo = CnaeCatalogo::tipoDoCnae($codigo)) {
                return $tipo;
            }
        }

        return null;
    }

    private function data($valor): ?Carbon
    {
        try {
            return $valor ? Carbon::parse($valor) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function montarLinhas(UsuarioInterno $usuario, array $filtros): Collection
    {
        $query = Estabelecimento::query()
            ->with('municipioRelacionado:id,nome')
            ->whereBetween('created_at', [$filtros['inicio'], $filtros['fim']])
            // Rejeitados nunca contam; cadastros da própria VISA (Descentralização) não são estabelecimentos fiscalizados
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', 'rejeitado'))
            ->whereDoesntHave('processos', fn ($q) => $q->where('tipo', 'descentralizacao'));

        if ($filtros['cadastro'] === 'aprovado') {
            $query->where('status', 'aprovado');
        }

        if ($usuario->isMunicipal()) {
            $query->where('municipio_id', $usuario->municipio_id ?: 0);
        } elseif ($filtros['municipio_id']) {
            $query->where('municipio_id', $filtros['municipio_id']);
        }

        if ($usuario->isEstadual()) {
            $query->where(fn ($q) => $q->whereNull('competencia_manual')->orWhere('competencia_manual', '!=', 'municipal'));
        }

        if ($filtros['tipo_pessoa']) {
            $query->where('tipo_pessoa', $filtros['tipo_pessoa']);
        }

        if ($filtros['setor'] === 'publico') {
            $query->where('tipo_setor', 'publico');
        } elseif ($filtros['setor'] === 'privado') {
            $query->where(fn ($q) => $q->whereNull('tipo_setor')->orWhere('tipo_setor', '!=', 'publico'));
        }

        if ($filtros['busca'] !== '') {
            $busca = $filtros['busca'];
            $digitos = preg_replace('/\D/', '', $busca);
            $query->where(function ($q) use ($busca, $digitos) {
                $q->where('nome_fantasia', 'ilike', "%{$busca}%")
                    ->orWhere('razao_social', 'ilike', "%{$busca}%")
                    ->orWhere('nome_completo', 'ilike', "%{$busca}%");
                if ($digitos !== '') {
                    $q->orWhere('cnpj', 'ilike', "%{$digitos}%")->orWhere('cpf', 'ilike', "%{$digitos}%");
                }
            });
        }

        return $query->get()
            ->map(fn (Estabelecimento $e) => $this->montarLinha($e))
            ->filter(fn ($linha) => $this->dentroDoEscopo($linha, $usuario, $filtros['competencia']))
            ->filter(fn ($linha) => $this->correspondeTipoServico($linha, $filtros['tipo_servico']))
            ->values();
    }

    /** Nome oficial do município indexado pelo nome normalizado (sem acento, maiúsculo) */
    private ?Collection $municipiosPorNome = null;

    private function normalizarNome(?string $nome): string
    {
        $nome = trim(preg_replace('/\s*[-\/]\s*TO\s*$/i', '', (string) $nome));

        return mb_strtoupper(\Illuminate\Support\Str::ascii($nome));
    }

    /**
     * Município do estabelecimento: o vinculado ou, se não houver, o oficial com o mesmo nome
     * (evita "Palmas" e "PALMAS" separados nos totais por município)
     */
    private function nomeMunicipio(Estabelecimento $e): string
    {
        $municipio = $e->relationLoaded('municipioRelacionado') ? $e->getRelation('municipioRelacionado') : null;
        if ($municipio?->nome) {
            return $municipio->nome;
        }

        if (!$e->cidade) {
            return '—';
        }

        $this->municipiosPorNome ??= Municipio::query()->pluck('nome')
            ->keyBy(fn ($nome) => $this->normalizarNome($nome));

        return $this->municipiosPorNome->get($this->normalizarNome($e->cidade)) ?? mb_strtoupper(trim($e->cidade));
    }

    private function montarLinha(Estabelecimento $e): array
    {
        $tipoSetor = $e->tipo_setor instanceof \App\Enums\TipoSetor ? $e->tipo_setor->value : ($e->tipo_setor ?: 'privado');

        return [
            'estabelecimento' => $e,
            'competencia' => $e->isCompetenciaEstadual() ? 'estadual' : 'municipal',
            'municipio' => $this->nomeMunicipio($e),
            'setor' => $tipoSetor === 'publico' ? 'publico' : 'privado',
            'situacao' => $this->situacao($e),
            'tipo_servico' => $tipoServico = $this->tipoServico($e),
            'area' => CnaeCatalogo::areaDoTipo($tipoServico),
        ];
    }

    /**
     * Baixado (Receita) tem prioridade; depois desativado no sistema; senão ativo.
     */
    private function situacao(Estabelecimento $e): string
    {
        $situacaoReceita = strtoupper(trim((string) ($e->situacao_cadastral ?: $e->descricao_situacao_cadastral)));
        if ($situacaoReceita === '8' || $situacaoReceita === '08' || str_contains($situacaoReceita, 'BAIXADA')
            || str_contains(strtoupper((string) $e->descricao_situacao_cadastral), 'BAIXADA')) {
            return 'baixado';
        }

        return $e->ativo ? 'ativo' : 'inativo';
    }

    private function dentroDoEscopo(array $linha, UsuarioInterno $usuario, ?string $competenciaFiltro): bool
    {
        if ($usuario->isMunicipal()) {
            return $linha['competencia'] === 'municipal';
        }

        if ($usuario->isEstadual()) {
            return $linha['competencia'] === 'estadual';
        }

        if (!$usuario->isAdmin()) {
            return false;
        }

        return !$competenciaFiltro || $linha['competencia'] === $competenciaFiltro;
    }

    private function indicadores(Collection $linhas): array
    {
        $total = $linhas->count();
        $pct = fn ($n) => $total ? round($n / $total * 100) : 0;

        $contagem = collect(self::SITUACOES)->map(fn ($rotulo, $chave) => $linhas->where('situacao', $chave)->count());

        return [
            'total' => $total,
            'ativo' => $contagem['ativo'],
            'inativo' => $contagem['inativo'],
            'baixado' => $contagem['baixado'],
            'pct_ativo' => $pct($contagem['ativo']),
            'pct_inativo' => $pct($contagem['inativo']),
            'pct_baixado' => $pct($contagem['baixado']),
            'estadual' => $linhas->where('competencia', 'estadual')->count(),
            'municipal' => $linhas->where('competencia', 'municipal')->count(),
            'publico' => $linhas->where('setor', 'publico')->count(),
            'privado' => $linhas->where('setor', 'privado')->count(),
            'juridica' => $linhas->filter(fn ($l) => $l['estabelecimento']->tipo_pessoa === 'juridica')->count(),
            'fisica' => $linhas->filter(fn ($l) => $l['estabelecimento']->tipo_pessoa === 'fisica')->count(),
        ];
    }

    /**
     * Cadastros por mês no período, separados por situação atual
     */
    private function graficoMensal(Collection $linhas, array $filtros): array
    {
        $nomesMes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $meses = [];
        $cursor = $filtros['inicio']->copy()->startOfMonth();
        $ultimo = $filtros['fim']->copy()->startOfMonth();
        while ($cursor->lessThanOrEqualTo($ultimo) && count($meses) < 120) {
            $meses[$cursor->format('Y-m')] = $nomesMes[$cursor->month - 1] . '/' . $cursor->format('y');
            $cursor->addMonth();
        }

        $porMes = $linhas->groupBy(fn ($l) => $l['estabelecimento']->created_at->format('Y-m'));

        $series = collect(self::SITUACOES)->map(fn ($rotulo, $situacao) => array_values(array_map(
            fn ($mes) => ($porMes->get($mes) ?? collect())->where('situacao', $situacao)->count(),
            array_keys($meses)
        )));

        $totais = array_values(array_map(fn ($mes) => ($porMes->get($mes) ?? collect())->count(), array_keys($meses)));

        return [
            'rotulos' => array_values($meses),
            'series' => $series,
            'totais' => $totais,
            'media' => count($totais) ? round(array_sum($totais) / count($totais), 1) : 0,
            'pico' => count($totais) ? ['total' => max($totais), 'mes' => array_values($meses)[array_search(max($totais), $totais, true)]] : null,
        ];
    }

    /**
     * Quantidade por tipo de serviço (e por área), com a situação de cada um
     */
    private function porTipoServico(Collection $linhas): array
    {
        $tipos = $linhas->groupBy(fn ($l) => $l['tipo_servico'] ?? 'outros')
            ->map(fn ($grupo, $slug) => [
                'slug' => $slug,
                'filtro' => $slug === 'outros' ? 'outros' : 'tipo:' . $slug,
                'nome' => $slug === 'outros' ? 'Outras atividades' : CnaeCatalogo::nomeTipo($slug),
                'area' => $slug === 'outros' ? 'outros' : CnaeCatalogo::areaDoTipo($slug),
                'total' => $grupo->count(),
                'ativo' => $grupo->where('situacao', 'ativo')->count(),
                'inativo' => $grupo->where('situacao', 'inativo')->count(),
                'baixado' => $grupo->where('situacao', 'baixado')->count(),
            ])
            ->sortByDesc('total')
            ->values();

        $areas = collect(CnaeCatalogo::AREAS)
            ->map(fn ($dados, $area) => [
                'area' => $area,
                'filtro' => 'area:' . $area,
                'nome' => $dados['nome'],
                'total' => $linhas->where('area', $area)->count(),
            ])
            ->push(['area' => 'outros', 'filtro' => 'outros', 'nome' => 'Outras atividades', 'total' => $linhas->whereNull('tipo_servico')->count()])
            ->filter(fn ($a) => $a['total'] > 0)
            ->sortByDesc('total')
            ->values();

        return ['tipos' => $tipos, 'areas' => $areas];
    }

    private function porMunicipio(Collection $linhas): Collection
    {
        return $linhas->groupBy('municipio')
            ->map(fn ($grupo, $municipio) => [
                'municipio' => $municipio,
                'total' => $grupo->count(),
                'ativo' => $grupo->where('situacao', 'ativo')->count(),
                'inativo' => $grupo->where('situacao', 'inativo')->count(),
                'baixado' => $grupo->where('situacao', 'baixado')->count(),
            ])
            ->sortByDesc('total')
            ->values();
    }

    private function paginar(Collection $itens, int $porPagina, Request $request): LengthAwarePaginator
    {
        $pagina = max(1, (int) $request->input('page', 1));

        return new LengthAwarePaginator(
            $itens->forPage($pagina, $porPagina)->values(),
            $itens->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }
}
