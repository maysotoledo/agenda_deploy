<?php

namespace App\Services\IA;

use Illuminate\Support\Facades\Http;
use Throwable;

class OllamaService
{
    public function chat(
        string $pergunta,
        array $contexto = [],
        ?string $tipo = null,
        ?string $modelo = null
    ): string {
        $url = rtrim(config('services.ollama.url'), '/');

        $model = $modelo ?: config('services.ollama.model', 'llama3.2:3b');

        $contextoTexto = json_encode(
            $contexto,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        $userPrompt = <<<PROMPT
TIPO DA ANÁLISE:
{$tipo}

DADOS DISPONÍVEIS DO RELATÓRIO:
{$contextoTexto}

SOLICITAÇÃO:
{$pergunta}
PROMPT;

        try {
            $response = Http::timeout(180)
                ->post($url . '/api/chat', [
                    'model' => $model,
                    'stream' => false,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->systemPrompt(),
                        ],
                        [
                            'role' => 'user',
                            'content' => $userPrompt,
                        ],
                    ],
                    'options' => [
                        'temperature' => 0.2,
                        'top_p' => 0.9,
                    ],
                ]);

            if ($response->failed()) {
                return 'Erro ao consultar o Ollama. Verifique se o serviço está rodando em: ' . $url;
            }

            return trim($response->json('message.content') ?? 'A IA não retornou conteúdo.');
        } catch (Throwable $e) {
            return 'Erro ao conectar ao Ollama: ' . $e->getMessage();
        }
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
Você é um agente local de apoio à investigação policial e análise telemática.

REGRAS OBRIGATÓRIAS:
- Responda em português do Brasil.
- Use linguagem técnica, objetiva e formal.
- Responda SOMENTE com base nos dados fornecidos.
- NÃO invente dados, nomes, IPs, horários ou vínculos.
- NÃO conclua autoria, culpa ou participação criminosa.
- Utilize termos como:
  "indício", "possível", "sugere", "compatível com", "necessita validação".

DIRETRIZES:
- Separe claramente DADOS OBJETIVOS de INTERPRETAÇÕES.
- Aponte padrões relevantes (horário, IP, recorrência, localização).
- Identifique possíveis inconsistências.
- Sugira diligências investigativas.
- Sempre indique quando houver limitação de dados.

FORMATO DE RESPOSTA:
Use estrutura organizada, exemplo:

1. Síntese
2. Dados relevantes
3. Padrões identificados
4. Pontos de atenção
5. Diligências sugeridas

CONTEXTO:
Sistema de análise telemática com dados como:
- IPs
- provedores
- horários
- bilhetagem
- contatos
- localização
- acessos noturnos
- eventos móveis

IMPORTANTE:
A resposta é apenas apoio técnico e depende de validação humana.
PROMPT;
    }
}
