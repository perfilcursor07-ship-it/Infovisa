<?php

namespace Tests\Unit;

use App\Services\LeitorCarteiraConselhoService;
use PHPUnit\Framework\TestCase;

/**
 * Leitura por regras da carteira do conselho (sem IA): o texto vem do PDF ou do OCR do navegador.
 */
class LeitorCarteiraConselhoTest extends TestCase
{
    private function ler(string $texto): array
    {
        return (new LeitorCarteiraConselhoService())->viaRegras($texto);
    }

    /**
     * Cédula de identidade médica do CFM: rótulos numa linha e valores na de baixo.
     */
    public function test_cedula_do_cfm_em_duas_colunas(): void
    {
        $dados = $this->ler(
            "CONSELHO FEDERAL DE MEDICINA\n"
            . "CONSELHO REGIONAL DE MEDICINA - TO\n"
            . "NOME CRM/UF\n"
            . "LUIS PIRES DE MELO FILHO 8912/TO\n"
        );

        $this->assertSame('CRM', $dados['conselho']);
        $this->assertSame('TO', $dados['uf']);
        $this->assertSame('8912', $dados['numero']);
        $this->assertSame('LUIS PIRES DE MELO FILHO', $dados['nome']);
    }

    /**
     * O OCR costuma ler a sigla do estado como números parecidos ("TO" vira "10"):
     * o número da inscrição continua valendo e a UF vem do nome do conselho.
     */
    public function test_cedula_do_cfm_com_uf_mal_lida_pelo_ocr(): void
    {
        $dados = $this->ler(
            "CONSELHO REGIONAL DE MEDICINA - TO\n"
            . "NOME CRM/UF\n"
            . "LUIS PIRES DE MELO FILHO 8912/10\n"
        );

        $this->assertSame('TO', $dados['uf']);
        $this->assertSame('8912', $dados['numero']);
        $this->assertSame('LUIS PIRES DE MELO FILHO', $dados['nome']);
    }

    public function test_uf_pela_sigla_no_nome_do_conselho(): void
    {
        $dados = $this->ler("CONSELHO REGIONAL DE ODONTOLOGIA - SP\nNOME CRO/UF\nMARIA SOUZA LIMA 45678/SP");

        $this->assertSame('CRO', $dados['conselho']);
        $this->assertSame('SP', $dados['uf']);
        $this->assertSame('45678', $dados['numero']);
        $this->assertSame('MARIA SOUZA LIMA', $dados['nome']);
    }

    /**
     * Formatos que já funcionavam continuam funcionando.
     */
    public function test_formatos_antigos(): void
    {
        $this->assertSame('1234', $this->ler('CRM-TO 1234')['numero']);
        $this->assertSame('TO', $this->ler('CRM 1234/TO')['uf']);
        $this->assertSame('3269', $this->ler("CRM/UF\n3269/TO")['numero']);
        $this->assertSame('CRMV', $this->ler('CONSELHO REGIONAL DE MEDICINA VETERINARIA DO ESTADO DE GOIAS INSCRICAO 4567')['conselho']);
    }

    /**
     * Documento que não é carteira não pode inventar número nem nome.
     */
    public function test_texto_que_nao_e_carteira(): void
    {
        $dados = $this->ler("ENERGISA TOCANTINS\nFATURA DE ENERGIA\nCLIENTE: JOSE PEREIRA 12345\nCEP 77001-002");

        $this->assertNull($dados['conselho']);
        $this->assertNull($dados['numero']);
        $this->assertNull($dados['nome']);
    }
}
