<?php

namespace App\Services\Concerns;

use App\Models\ConfiguracaoSistema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Leitura de documentos (texto extraído do PDF ou por OCR) com a IA configurada em
 * Configurações > Sistema > Inteligência Artificial (API compatível com OpenAI: DeepSeek, OpenAI...).
 */
trait LeDocumentoComIa
{
    /**
     * Pede à IA um JSON a partir do texto do documento. Null se não houver IA configurada ou ela falhar.
     */
    protected function jsonDaIa(string $instrucao, string $texto, string $documento): ?array
    {
        $apiKey = ConfiguracaoSistema::where('chave', 'ia_api_key')->value('valor');
        $apiUrl = ConfiguracaoSistema::where('chave', 'ia_api_url')->value('valor');
        $modelo = ConfiguracaoSistema::where('chave', 'ia_model')->value('valor');

        if (!$apiKey || !$apiUrl || !$modelo || mb_strlen($texto) < 10) {
            return null;
        }

        try {
            $resposta = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->timeout(25)
                ->post($apiUrl, [
                    'model' => $modelo,
                    'temperature' => 0,
                    'max_tokens' => 500,
                    'messages' => [
                        ['role' => 'system', 'content' => $instrucao],
                        ['role' => 'user', 'content' => "Texto do documento:\n\n" . $texto],
                    ],
                ]);

            if (!$resposta->successful()) {
                Log::warning("Leitura de {$documento}: IA respondeu com erro", ['status' => $resposta->status()]);
                return null;
            }

            $conteudo = (string) data_get($resposta->json(), 'choices.0.message.content', '');
            if (!preg_match('/\{.*\}/s', $conteudo, $m)) {
                return null;
            }
            $json = json_decode($m[0], true);

            return is_array($json) ? $json : null;
        } catch (\Throwable $e) {
            Log::warning("Leitura de {$documento}: falha ao chamar a IA", ['erro' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Maiúsculas e sem acentos, para comparar textos.
     */
    protected function normalizar(string $texto): string
    {
        $texto = mb_strtoupper($texto, 'UTF-8');
        $texto = strtr($texto, ['Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'É' => 'E', 'Ê' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ç' => 'C']);

        return preg_replace('/[ \t]+/', ' ', $texto);
    }

    /**
     * Mesmo nome, tolerando erros de leitura ("ABNERC RIBEIRO" ≈ "ABNER RIBEIRO"):
     * o primeiro nome tem que bater e pelo menos metade dos outros também.
     */
    public function nomesParecidos(?string $a, ?string $b): bool
    {
        $partes = fn ($t) => array_values(array_filter(
            explode(' ', preg_replace('/[^A-Z ]/', '', $this->normalizar((string) $t))),
            fn ($p) => strlen($p) > 2 && !in_array($p, ['DOS', 'DAS', 'DES'], true)
        ));
        $pa = $partes($a);
        $pb = $partes($b);
        if (!$pa || !$pb) {
            return true;
        }
        $parecida = fn ($p, $q) => levenshtein($p, $q) <= (strlen($p) > 5 ? 2 : 1);
        $iguais = count(array_filter($pa, fn ($p) => (bool) array_filter($pb, fn ($q) => $parecida($p, $q))));

        return $parecida($pa[0], $pb[0]) && $iguais >= (int) ceil(min(count($pa), count($pb)) / 2);
    }
}
