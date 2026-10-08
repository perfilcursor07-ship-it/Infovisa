<?php

namespace App\Services;

use App\Services\Concerns\LeDocumentoComIa;

/**
 * Lê o comprovante de endereço (conta de água, energia ou telefone fixo) a partir do TEXTO
 * do documento (PDF ou OCR feito no navegador) e devolve titular, CEP, endereço e tipo da conta.
 * Usa a IA configurada no sistema; sem IA, usa regras.
 */
class LeitorComprovanteEnderecoService
{
    use LeDocumentoComIa;

    public const TIPOS = [
        'energia' => 'Conta de energia',
        'agua' => 'Conta de água',
        'telefone' => 'Conta de telefone fixo',
        'outro' => 'Outro documento',
    ];

    /** Parentesco/relação de quem declara com o titular do comprovante */
    public const VINCULOS = [
        'familiar' => 'Cônjuge ou familiar',
        'aluguel' => 'Imóvel alugado (proprietário ou imobiliária)',
        'empresa' => 'Empresa, clínica ou consultório onde trabalha',
        'outro' => 'Outro',
    ];

    /**
     * @return array{dados: array, origem: string, encontrou: bool}
     */
    public function interpretar(string $texto): array
    {
        $texto = mb_substr(trim($texto), 0, 8000);
        $dados = $this->viaRegras($texto);
        $origem = 'regras';

        if ($porIa = $this->viaIa($texto)) {
            foreach ($porIa as $campo => $valor) {
                if ($valor !== null && $valor !== '') {
                    $dados[$campo] = $valor;
                }
            }
            $origem = 'ia';
        }

        $dados['tipo'] ??= 'outro';
        $dados['tipo_descricao'] = self::TIPOS[$dados['tipo']] ?? self::TIPOS['outro'];
        $dados['cep_formatado'] = $dados['cep'] ? substr($dados['cep'], 0, 5) . '-' . substr($dados['cep'], 5) : null;

        return [
            'dados' => $dados,
            'origem' => $origem,
            'encontrou' => (bool) ($dados['cep'] || $dados['endereco'] || $dados['titular']),
        ];
    }

    public function viaRegras(string $texto): array
    {
        $t = $this->normalizar($texto);
        $titular = $this->titularNoTexto($t);

        return [
            'titular' => $titular,
            'cep' => $this->cepNoTexto($t, $titular),
            'endereco' => null, // linha de endereço é difícil por regras; fica para a IA ou para o CEP
            'municipio' => null,
            'uf' => null,
            'tipo' => $this->tipoNoTexto($t),
        ];
    }

    private function viaIa(string $texto): ?array
    {
        $instrucao = 'Você lê o texto extraído (às vezes por OCR, com erros) de um comprovante de endereço brasileiro '
            . '(conta de energia, água ou telefone) e devolve SOMENTE um JSON, sem comentários, no formato: '
            . '{"titular":"nome do cliente/titular da conta","cep":"8 dígitos do CEP do endereço do cliente","endereco":"logradouro, número, quadra, lote, complemento e bairro do cliente","municipio":"cidade do cliente","uf":"sigla do estado","tipo":"energia|agua|telefone|outro"}. '
            . 'ATENÇÃO: a conta também traz o endereço e o CEP da empresa (concessionária); ignore-os e use só os dados do CLIENTE / unidade consumidora. '
            . 'Telefone celular/móvel não é telefone fixo: use "outro". '
            . 'Não invente dados: use null quando a informação não estiver no texto.';

        $json = $this->jsonDaIa($instrucao, $texto, 'comprovante de endereço');
        if (!$json) {
            return null;
        }

        $cep = preg_replace('/\D/', '', (string) ($json['cep'] ?? ''));
        $tipo = strtolower((string) ($json['tipo'] ?? ''));
        $texto = fn ($v) => is_string($v) && trim($v) !== '' ? mb_strtoupper(trim($v), 'UTF-8') : null;

        return [
            'titular' => $texto($json['titular'] ?? null),
            'cep' => strlen($cep) === 8 ? $cep : null,
            'endereco' => $texto($json['endereco'] ?? null),
            'municipio' => $texto($json['municipio'] ?? null),
            'uf' => preg_match('/^[A-Z]{2}$/', (string) $texto($json['uf'] ?? null)) ? $texto($json['uf']) : null,
            'tipo' => array_key_exists($tipo, self::TIPOS) ? $tipo : null,
        ];
    }

    /**
     * CEP do cliente: o primeiro depois do nome do titular; sem titular, o primeiro que não está
     * junto dos dados da concessionária (CNPJ, LTDA, S.A., site, SAC).
     */
    private function cepNoTexto(string $t, ?string $titular = null): ?string
    {
        if (!preg_match_all('/(?<!\d)(\d{2}\.?\d{3}\s?-\s?\d{3})(?!\d)/', $t, $m, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $posTitular = $titular ? strpos($t, $titular) : false;
        if ($posTitular !== false) {
            foreach ($m[1] as [$cep, $pos]) {
                if ($pos > $posTitular) {
                    return preg_replace('/\D/', '', $cep);
                }
            }
        }

        $candidatos = [];
        foreach ($m[1] as [$cep, $pos]) {
            $vizinhanca = substr($t, max(0, $pos - 80), 160);
            $daEmpresa = (bool) preg_match('/CNPJ|LTDA|\bS\.?\s?A\b|WWW\.|SAC\b|OUVIDORIA|INSCRICAO ESTADUAL/', $vizinhanca);
            $candidatos[] = [preg_replace('/\D/', '', $cep), $daEmpresa];
        }
        foreach ($candidatos as [$cep, $daEmpresa]) {
            if (!$daEmpresa) {
                return $cep;
            }
        }

        return $candidatos[0][0];
    }

    private function titularNoTexto(string $t): ?string
    {
        if (preg_match('/\b(?:NOME DO CLIENTE|NOME|CLIENTE|TITULAR|DESTINATARIO|CONSUMIDOR)\s*:\s*([A-Z][A-Z .]{5,80})/', $t, $m)) {
            $nome = trim(preg_split('/\s{2,}|\n|\s(?:CPF|CNPJ|RG|CODIGO|COD\.?|MATRICULA|ENDERECO)\b/', $m[1])[0], " .");

            return mb_strlen($nome) >= 5 ? $nome : null;
        }

        return null;
    }

    private function tipoNoTexto(string $t): ?string
    {
        return match (true) {
            (bool) preg_match('/ENERGIA ELETRICA|ENERGISA|\bKWH\b|DISTRIBUIDORA DE ENERGIA|CONSUMO DE ENERGIA|\bENEL\b|CEMIG|COPEL|\bLIGHT\b|EQUATORIAL/', $t) => 'energia',
            (bool) preg_match('/SANEAMENTO|SANEATINS|\bBRK\b|ABASTECIMENTO DE AGUA|AGUA E ESGOTO|\bM3\b|M³|SABESP|COPASA|SANEAGO/', $t) => 'agua',
            (bool) preg_match('/TELEFONIA FIXA|TELEFONE FIXO|LINHA FIXA|TELECOMUNICACOES|\bOI\b S\.?A|EMBRATEL|TELEFONICA/', $t) => 'telefone',
            default => null,
        };
    }
}
