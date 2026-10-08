<?php

namespace App\Services;

use App\Models\Receituario;
use App\Services\Concerns\LeDocumentoComIa;

/**
 * Lê a carteira do conselho de classe (CRM, CRO, CRMV) a partir do TEXTO do documento
 * (frente e verso) e devolve conselho, UF, número, especialidade, nome e CPF.
 *
 * O texto vem do PDF (extração direta) ou do OCR feito no navegador (foto / PDF escaneado),
 * pois a API de IA configurada pode não aceitar imagens (ex.: DeepSeek).
 * Usa a IA configurada em Configurações > Sistema > Inteligência Artificial; se não houver
 * IA ou ela falhar, usa regras (expressões regulares).
 */
class LeitorCarteiraConselhoService
{
    use LeDocumentoComIa;

    private const CONSELHOS = ['CRM', 'CRO', 'CRMV'];

    private const UFS = [
        'ACRE' => 'AC', 'ALAGOAS' => 'AL', 'AMAPA' => 'AP', 'AMAZONAS' => 'AM', 'BAHIA' => 'BA', 'CEARA' => 'CE',
        'DISTRITO FEDERAL' => 'DF', 'ESPIRITO SANTO' => 'ES', 'GOIAS' => 'GO', 'MARANHAO' => 'MA', 'MATO GROSSO DO SUL' => 'MS',
        'MATO GROSSO' => 'MT', 'MINAS GERAIS' => 'MG', 'PARAIBA' => 'PB', 'PARANA' => 'PR', 'PARA' => 'PA',
        'PERNAMBUCO' => 'PE', 'PIAUI' => 'PI', 'RIO DE JANEIRO' => 'RJ', 'RIO GRANDE DO NORTE' => 'RN',
        'RIO GRANDE DO SUL' => 'RS', 'RONDONIA' => 'RO', 'RORAIMA' => 'RR', 'SANTA CATARINA' => 'SC',
        'SAO PAULO' => 'SP', 'SERGIPE' => 'SE', 'TOCANTINS' => 'TO',
    ];

    /**
     * Texto de um PDF (vazio se for escaneado / só imagem).
     */
    public function textoDoPdf(string $caminho): string
    {
        try {
            $texto = (new \Smalot\PdfParser\Parser())->parseFile($caminho)->getText();
        } catch (\Throwable $e) {
            return '';
        }

        return mb_substr(trim(preg_replace('/[ \t]+/', ' ', $texto)), 0, 8000);
    }

    /**
     * @return array{dados: array, origem: string, encontrou: bool}
     */
    public function interpretar(string $texto): array
    {
        $texto = mb_substr(trim($texto), 0, 8000);
        $porRegras = $this->viaRegras($texto);
        $porIa = $this->viaIa($texto);

        // IA tem prioridade; o que ela não achar vem das regras
        $dados = $porRegras;
        $origem = 'regras';
        if ($porIa) {
            foreach ($porIa as $campo => $valor) {
                if ($valor !== null && $valor !== '') {
                    $dados[$campo] = $valor;
                }
            }
            $origem = 'ia';

            // CPF que a IA "corrigiu" errado: fica o das regras, se este for válido
            if (!self::cpfValido($dados['cpf']) && self::cpfValido($porRegras['cpf'])) {
                $dados['cpf'] = $porRegras['cpf'];
            }
        }

        $dados['cpf'] = $this->corrigirCpfDoOcr($dados['cpf']);
        $dados['cpf_valido'] = self::cpfValido($dados['cpf']);

        $dados['numero_formatado'] = $dados['numero']
            ? trim(($dados['conselho'] ?? 'CRM') . ($dados['uf'] ? '-' . $dados['uf'] : '') . ' ' . $dados['numero'])
            : null;

        // Sem especialidade escrita: dentista e veterinário têm especialidade pelo conselho
        if (!$dados['especialidade']) {
            $dados['especialidade'] = match ($dados['conselho']) {
                'CRO' => 'ODONTOLOGIA',
                'CRMV' => 'MEDICINA VETERINÁRIA',
                default => null,
            };
        }

        return [
            'dados' => $dados,
            'origem' => $origem,
            'encontrou' => (bool) ($dados['numero'] || $dados['especialidade']),
        ];
    }

    /**
     * Leitura por regras: procura "CRM-TO 1234", "CRM 1234/TO", "Conselho Regional de Medicina do Estado do Tocantins ... Nº 1234".
     */
    public function viaRegras(string $texto): array
    {
        $t = $this->normalizar($texto);
        $dados = ['conselho' => null, 'uf' => null, 'numero' => null, 'especialidade' => null, 'nome' => null, 'cpf' => null];

        $siglas = 'CRMV|CRM|CRO';
        $ufs = implode('|', array_unique(array_values(self::UFS)));

        $numeroRotulo = '(?:N(?:O|°|º)?\.?\s*)?'; // "Nº", "N°", "NO." antes do número
        if (preg_match("/\b($siglas)\s*[-\/:]?\s*($ufs)\b\s*[-:\/]?\s*{$numeroRotulo}(\d[\d.]{1,8})\b/u", $t, $m)) {
            [$dados['conselho'], $dados['uf'], $dados['numero']] = [$m[1], $m[2], $m[3]];
        } elseif (preg_match("/\b($siglas)\s*[-:\/]?\s*{$numeroRotulo}(\d[\d.]{1,8})\s*[-\/]\s*($ufs)\b/u", $t, $m)) {
            [$dados['conselho'], $dados['numero'], $dados['uf']] = [$m[1], $m[2], $m[3]];
        } elseif (preg_match("/\b($siglas)\b\s*[-:\/]?\s*{$numeroRotulo}(\d[\d.]{1,8})\b/u", $t, $m)) {
            [$dados['conselho'], $dados['numero']] = [$m[1], $m[2]];
        } elseif (preg_match("/\b($siglas)\s*\/\s*UF\b/u", $t, $m) && preg_match("/(?<![\d\/])(\d[\d.]{1,8})\s*\/\s*($ufs)\b/u", $t, $n)) {
            // Cédula do CFM: rótulo "CRM/UF" e, na linha de baixo, "3269/TO"
            [$dados['conselho'], $dados['numero'], $dados['uf']] = [$m[1], $n[1], $n[2]];
        }

        // "7.890" (OCR/formatação) → "7890"
        if ($dados['numero']) {
            $dados['numero'] = preg_replace('/\D/', '', $dados['numero']);
            if (strlen($dados['numero']) < 2 || strlen($dados['numero']) > 7) {
                $dados['numero'] = null;
            }
        }

        // Conselho pelo nome por extenso
        if (!$dados['conselho']) {
            $dados['conselho'] = match (true) {
                str_contains($t, 'MEDICINA VETERINARIA') => 'CRMV',
                str_contains($t, 'CONSELHO REGIONAL DE ODONTOLOGIA') || str_contains($t, 'CIRURGIAO-DENTISTA') || str_contains($t, 'CIRURGIAO DENTISTA') => 'CRO',
                str_contains($t, 'CONSELHO REGIONAL DE MEDICINA') => 'CRM',
                default => null,
            };
        }

        // Número pela palavra "inscrição"/"registro"
        if (!$dados['numero'] && preg_match('/\b(?:INSCRICAO|REGISTRO|CRM|CRO|CRMV)\b[^0-9\n]{0,25}(\d{3,7})\b/', $t, $m)) {
            $dados['numero'] = $m[1];
        }

        // UF pelo nome do estado ("do Estado do Tocantins")
        if (!$dados['uf']) {
            foreach (self::UFS as $estado => $sigla) {
                if (preg_match('/(?:\b(?:ESTADO D[OEA]|D[OEA])\s+|\bMEDICINA\s*-\s*|\bODONTOLOGIA\s*-\s*|\bVETERINARIA\s*-\s*)' . $estado . '\b/', $t)) {
                    $dados['uf'] = $sigla;
                    break;
                }
            }
        }

        $dados['especialidade'] = $this->especialidadeNoTexto($t);

        if (preg_match('/\bNOME\s*:?\s*([A-Z][A-Z ]{5,80})/', $t, $m)) {
            $dados['nome'] = trim(preg_split('/\s{2,}|\n/', $m[1])[0]);
        }

        $dados['cpf'] = $this->cpfNoTexto($t);

        return $dados;
    }

    /**
     * Leitura pela IA configurada no sistema (API compatível com OpenAI: DeepSeek, Together, OpenAI...).
     */
    private function viaIa(string $texto): ?array
    {
        $especialidades = implode('; ', Receituario::ESPECIALIDADES);
        $instrucao = 'Você lê o texto extraído (às vezes por OCR, com erros) de uma carteira profissional de conselho de classe '
            . 'brasileiro (CRM, CRO ou CRMV) e devolve SOMENTE um JSON, sem comentários, no formato: '
            . '{"conselho":"CRM|CRO|CRMV","uf":"sigla do estado","numero":"apenas dígitos da inscrição","especialidade":"uma da lista ou null","nome":"nome do profissional ou null","cpf":"11 dígitos do CPF do profissional ou null"}. '
            . 'A especialidade deve ser exatamente um destes valores (ou null se não houver): ' . $especialidades . '. '
            . 'O texto pode ter a frente e o verso da carteira. Copie o CPF exatamente como aparece, sem corrigir. '
            . 'Não invente dados: use null quando a informação não estiver no texto.';

        $json = $this->jsonDaIa($instrucao, $texto, 'carteira do conselho');
        if (!$json) {
            return null;
        }

        $conselho = self::siglaConselho($json['conselho'] ?? null);
        $uf = strtoupper((string) ($json['uf'] ?? ''));
        $numero = preg_replace('/\D/', '', (string) ($json['numero'] ?? ''));
        $cpf = preg_replace('/\D/', '', (string) ($json['cpf'] ?? ''));

        return [
            'conselho' => in_array($conselho, self::CONSELHOS, true) ? $conselho : null,
            'uf' => in_array($uf, self::UFS, true) ? $uf : null,
            'numero' => strlen($numero) >= 2 && strlen($numero) <= 7 ? $numero : null,
            'especialidade' => $this->especialidadeValida($json['especialidade'] ?? null),
            'nome' => !empty($json['nome']) && is_string($json['nome']) ? mb_strtoupper(trim($json['nome']), 'UTF-8') : null,
            'cpf' => strlen($cpf) === 11 ? $cpf : null,
        ];
    }

    /**
     * Sigla do conselho dentro do que a IA devolveu ("CRM|UF", "CRM-TO" → "CRM"); CRMV antes de CRM.
     */
    public static function siglaConselho($valor): ?string
    {
        preg_match_all('/\b(CRMV|CRO|CRM)\b/', strtoupper((string) $valor), $m);
        $siglas = array_values(array_unique($m[1]));

        // Mais de uma sigla (ex.: a IA repetiu o modelo "CRM|CRO|CRMV"): não dá para saber, ficam as regras
        return count($siglas) === 1 ? $siglas[0] : null;
    }

    /**
     * Especialidade da lista que aparece no texto (a de nome mais longo vence: "CIRURGIA PEDIÁTRICA" antes de "PEDIATRIA").
     */
    private function especialidadeNoTexto(string $textoNormalizado): ?string
    {
        $encontradas = collect(Receituario::ESPECIALIDADES)
            ->reject(fn ($e) => $e === 'OUTRAS')
            ->filter(fn ($e) => preg_match('/\b' . preg_quote($this->normalizar($e), '/') . '\b/', $textoNormalizado))
            ->sortByDesc(fn ($e) => mb_strlen($e));

        return $encontradas->first();
    }

    /**
     * CPF da carteira: primeiro o que vem depois do rótulo "CPF", senão o primeiro número no formato 000.000.000-00.
     */
    private function cpfNoTexto(string $textoNormalizado): ?string
    {
        $formato = '(\d{3}\s?[.,]?\s?\d{3}\s?[.,]?\s?\d{3}\s?[-.,]?\s?\d{2})(?!\d)';
        if (preg_match('/\bCPF\b[^0-9]{0,80}' . $formato . '/s', $textoNormalizado, $m)
            || preg_match('/(?<!\d)(\d{3}\.\d{3}\.\d{3}-\d{2})(?!\d)/', $textoNormalizado, $m)) {
            $cpf = preg_replace('/\D/', '', $m[1]);

            return strlen($cpf) === 11 ? $cpf : null;
        }

        return null;
    }

    /**
     * OCR costuma trocar dígitos parecidos (0↔8, 1↔7...). Se o CPF lido é inválido e só UMA troca
     * de um dígito o torna válido, usa essa troca; se houver mais de uma possibilidade, mantém o lido.
     */
    private function corrigirCpfDoOcr(?string $cpf): ?string
    {
        if (!$cpf || self::cpfValido($cpf)) {
            return $cpf;
        }
        $parecidos = ['0' => '869', '1' => '74', '2' => '7', '3' => '858', '4' => '1', '5' => '638', '6' => '580', '7' => '12', '8' => '03695', '9' => '08'];
        $candidatos = [];
        for ($i = 0; $i < 11; $i++) {
            foreach (str_split($parecidos[$cpf[$i]] ?? '') as $troca) {
                $tentativa = substr_replace($cpf, $troca, $i, 1);
                if (self::cpfValido($tentativa)) {
                    $candidatos[$tentativa] = true;
                }
            }
        }

        return count($candidatos) === 1 ? array_key_first($candidatos) : $cpf;
    }

    public static function cpfValido(?string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', (string) $cpf);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $cpf[$i] * (($t + 1) - $i);
            }
            if ((int) $cpf[$t] !== ((10 * $soma) % 11) % 10) {
                return false;
            }
        }

        return true;
    }

    private function especialidadeValida($valor): ?string
    {
        if (!is_string($valor) || trim($valor) === '') {
            return null;
        }
        $procurada = $this->normalizar($valor);

        foreach (Receituario::ESPECIALIDADES as $especialidade) {
            if ($this->normalizar($especialidade) === $procurada) {
                return $especialidade;
            }
        }

        return $this->especialidadeNoTexto($procurada);
    }
}
