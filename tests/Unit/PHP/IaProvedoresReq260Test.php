<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-260 — camada de provedores de IA: pedido e leitura de resposta de cada provedor, sem rede,
 * e a chave fora do endereço e das mensagens de erro.
 */
final class IaProvedoresReq260Test extends TestCase
{
    private const CHAVE = 'sk-chave-secreta-de-teste-1234567890';

    public static function setUpBeforeClass(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/ia-provedores.php';
    }

    private function servidor(string $tipo, array $extra = []): array
    {
        return array_merge(['tipo' => $tipo, 'chave' => self::CHAVE], $extra);
    }

    private function pedido(): array
    {
        return [
            'sistema' => 'Responda em português.',
            'mensagens' => [
                ['papel' => 'user', 'texto' => 'Olá'],
                ['papel' => 'assistant', 'texto' => 'Oi'],
                ['papel' => 'qualquer', 'texto' => 'Tudo bem?'],
                ['papel' => 'user', 'texto' => ''],
            ],
            'max_tokens' => 200,
        ];
    }

    public function testQuatroProvedoresConhecidos(): void
    {
        $this->assertSame(['gemini', 'anthropic', 'openai', 'openai-compativel'], array_keys(ia_provedores()));
        $this->assertNull(ia_provedor_dados('desconhecido'));
        $this->assertTrue(ia_provedor_dados('openai-compativel')['url_base_obrigatoria']);
    }

    /** @dataProvider tipos */
    public function testChaveVaiEmCabecalhoENuncaNoEndereco(string $tipo, array $extra, string $cabecalho): void
    {
        $http = ia_provedor_pedido_texto($this->servidor($tipo, $extra), $this->pedido());

        $this->assertArrayNotHasKey('erro', $http);
        $this->assertStringNotContainsString(self::CHAVE, $http['url']);
        $this->assertStringNotContainsString('key=', $http['url']);
        $this->assertContains($cabecalho . self::CHAVE, $http['cabecalhos']);
        $this->assertStringNotContainsString(self::CHAVE, (string)json_encode($http['corpo']));
    }

    public static function tipos(): array
    {
        return [
            'gemini' => ['gemini', [], 'x-goog-api-key: '],
            'anthropic' => ['anthropic', [], 'x-api-key: '],
            'openai' => ['openai', [], 'Authorization: Bearer '],
            'compativel' => ['openai-compativel', ['url_base' => 'http://localhost:11434/v1', 'modelo' => 'llama3'], 'Authorization: Bearer '],
        ];
    }

    public function testPedidoGemini(): void
    {
        $http = ia_provedor_pedido_texto($this->servidor('gemini'), $this->pedido());

        $this->assertSame('https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent', $http['url']);
        $this->assertSame(['user', 'model', 'user'], array_column($http['corpo']['contents'], 'role'));
        $this->assertSame('Tudo bem?', $http['corpo']['contents'][2]['parts'][0]['text']);
        $this->assertSame('Responda em português.', $http['corpo']['systemInstruction']['parts'][0]['text']);
        $this->assertSame(200, $http['corpo']['generationConfig']['maxOutputTokens']);

        // Modelo sem o prefixo `models/` é aceito.
        $curto = ia_provedor_pedido_texto($this->servidor('gemini', ['modelo' => 'gemini-2.5-pro']), $this->pedido());
        $this->assertStringEndsWith('/models/gemini-2.5-pro:generateContent', $curto['url']);
    }

    public function testPedidoAnthropic(): void
    {
        $http = ia_provedor_pedido_texto($this->servidor('anthropic'), $this->pedido());

        $this->assertSame('https://api.anthropic.com/v1/messages', $http['url']);
        $this->assertContains('anthropic-version: 2023-06-01', $http['cabecalhos']);
        $this->assertSame('Responda em português.', $http['corpo']['system']);
        $this->assertSame(['user', 'assistant', 'user'], array_column($http['corpo']['messages'], 'role'));
        $this->assertSame(200, $http['corpo']['max_tokens']);

        // A Anthropic exige `max_tokens`: sem limite pedido, vai um padrão.
        $pedido = $this->pedido();
        unset($pedido['max_tokens']);
        $this->assertGreaterThan(0, ia_provedor_pedido_texto($this->servidor('anthropic'), $pedido)['corpo']['max_tokens']);
    }

    public function testPedidoOpenAiECompativel(): void
    {
        $openai = ia_provedor_pedido_texto($this->servidor('openai'), $this->pedido());
        $this->assertSame('https://api.openai.com/v1/chat/completions', $openai['url']);
        $this->assertSame(['system', 'user', 'assistant', 'user'], array_column($openai['corpo']['messages'], 'role'));
        $this->assertSame(200, $openai['corpo']['max_completion_tokens']);
        $this->assertArrayNotHasKey('max_tokens', $openai['corpo']);

        $local = ia_provedor_pedido_texto($this->servidor('openai-compativel', ['url_base' => 'http://localhost:11434/v1/', 'modelo' => 'llama3']), $this->pedido());
        $this->assertSame('http://localhost:11434/v1/chat/completions', $local['url']);
        $this->assertSame('llama3', $local['corpo']['model']);
        $this->assertSame(200, $local['corpo']['max_tokens']);
    }

    public function testModeloDoPedidoVenceODoServidor(): void
    {
        $pedido = $this->pedido();
        $pedido['modelo'] = 'gpt-outro';
        $http = ia_provedor_pedido_texto($this->servidor('openai', ['modelo' => 'gpt-do-servidor']), $pedido);
        $this->assertSame('gpt-outro', $http['corpo']['model']);

        unset($pedido['modelo']);
        $this->assertSame('gpt-do-servidor', ia_provedor_pedido_texto($this->servidor('openai', ['modelo' => 'gpt-do-servidor']), $pedido)['corpo']['model']);
    }

    public function testPedidosRecusados(): void
    {
        $this->assertArrayHasKey('erro', ia_provedor_pedido_texto($this->servidor('outro'), $this->pedido()));
        $this->assertArrayHasKey('erro', ia_provedor_pedido_texto(['tipo' => 'gemini', 'chave' => ''], $this->pedido()));
        $this->assertArrayHasKey('erro', ia_provedor_pedido_texto($this->servidor('gemini'), ['mensagens' => []]));
        // Compatível sem endereço, ou sem modelo, não tem para onde ir.
        $this->assertArrayHasKey('erro', ia_provedor_pedido_texto($this->servidor('openai-compativel'), $this->pedido()));
        $this->assertArrayHasKey('erro', ia_provedor_pedido_texto($this->servidor('openai-compativel', ['url_base' => 'http://localhost:11434/v1']), $this->pedido()));
    }

    public function testEnderecoBaseEModeloSaoConferidos(): void
    {
        $this->assertSame('https://ia.exemplo.com/v1', ia_provedor_url_base_normalizar(' https://ia.exemplo.com/v1/ '));
        $this->assertSame('', ia_provedor_url_base_normalizar('ftp://ia.exemplo.com'));
        $this->assertSame('', ia_provedor_url_base_normalizar('https://usuario:senha@ia.exemplo.com'));
        $this->assertSame('', ia_provedor_url_base_normalizar('https://ia.exemplo.com/v1?key=abc'));
        $this->assertSame('', ia_provedor_url_base_normalizar('javascript:alert(1)'));

        $this->assertSame('models/gemini-2.5-flash', ia_provedor_modelo_normalizar('models/gemini-2.5-flash'));
        $this->assertSame('', ia_provedor_modelo_normalizar('modelo com espaço'));
        $this->assertSame('', ia_provedor_modelo_normalizar('../../etc?x=1'));

        // Endereço inválido no servidor cai no padrão do provedor.
        $http = ia_provedor_pedido_texto($this->servidor('openai', ['url_base' => 'https://x.com/?a=1']), $this->pedido());
        $this->assertStringStartsWith('https://api.openai.com/v1/', $http['url']);
    }

    public function testRespostaGemini(): void
    {
        $r = ia_provedor_resposta_texto('gemini', 200, [
            'candidates' => [['content' => ['parts' => [['text' => 'Olá, '], ['text' => 'mundo']]]]],
            'usageMetadata' => ['promptTokenCount' => 3, 'candidatesTokenCount' => 4, 'totalTokenCount' => 7],
        ]);
        $this->assertSame(['success', 'Olá, mundo', 3, 4, 7], [$r['status'], $r['texto'], $r['tokens_entrada'], $r['tokens_saida'], $r['tokens_total']]);
    }

    public function testRespostaAnthropic(): void
    {
        $r = ia_provedor_resposta_texto('anthropic', 200, [
            'content' => [['type' => 'thinking', 'thinking' => 'x'], ['type' => 'text', 'text' => 'Resposta']],
            'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
        ]);
        $this->assertSame(['success', 'Resposta', 15], [$r['status'], $r['texto'], $r['tokens_total']]);
    }

    public function testRespostaOpenAiECompativel(): void
    {
        $dados = ['choices' => [['message' => ['content' => 'Pronto']]], 'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 2, 'total_tokens' => 3]];
        foreach (['openai', 'openai-compativel'] as $tipo) {
            $r = ia_provedor_resposta_texto($tipo, 200, $dados);
            $this->assertSame(['success', 'Pronto', 3], [$r['status'], $r['texto'], $r['tokens_total']]);
        }
        $partes = ia_provedor_resposta_texto('openai', 200, ['choices' => [['message' => ['content' => [['type' => 'text', 'text' => 'Em '], ['type' => 'text', 'text' => 'partes']]]]]]);
        $this->assertSame('Em partes', $partes['texto']);
    }

    public function testRespostaVaziaEErroNaoVazamAChave(): void
    {
        $this->assertSame('error', ia_provedor_resposta_texto('gemini', 200, ['candidates' => []])['status']);
        $this->assertSame('error', ia_provedor_resposta_texto('openai', 200, null)['status']);

        $erro = ia_provedor_resposta_texto('openai', 401, ['error' => ['message' => 'Incorrect API key provided: ' . self::CHAVE . '.']]);
        $this->assertSame('error', $erro['status']);
        $this->assertStringContainsString('HTTP 401', $erro['message']);
        $this->assertStringNotContainsString(self::CHAVE, $erro['message']);

        $gemini = ia_provedor_resposta_texto('gemini', 400, ['error' => ['message' => 'API key not valid: AIzaSyA1234567890abcdefghij']]);
        $this->assertStringNotContainsString('AIzaSyA1234567890abcdefghij', $gemini['message']);
    }

    public function testConexaoComOBancoESoltaAntesDaEsperaPeloProvedor(): void
    {
        $fonte = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/ia-provedores.php');
        $http = substr($fonte, (int)strpos($fonte, 'function ia_provedor_http('), 400);
        $this->assertTrue(strpos($http, 'ia_provedor_banco_soltar();') < strpos($http, 'curl_init()'));

        // Sem conexão aberta, ou fora do sistema (sem a biblioteca de banco), não faz nada e não falha.
        $antes = $GLOBALS['_BANCO'] ?? null;
        $GLOBALS['_BANCO'] = ['conexao' => null];
        ia_provedor_banco_soltar();
        $this->assertSame(['conexao' => null], $GLOBALS['_BANCO']);
        unset($GLOBALS['_BANCO']);
        ia_provedor_banco_soltar();
        if ($antes !== null) {
            $GLOBALS['_BANCO'] = $antes;
        }
        $this->assertTrue(true);
    }

    public function testPedidoDeImagem(): void
    {
        $gemini = ia_provedor_pedido_imagem($this->servidor('gemini'), ['prompt' => 'Um farol ao entardecer', 'tamanho' => '1536x1024']);
        $this->assertStringEndsWith('/models/gemini-2.5-flash-image:generateContent', $gemini['url']);
        $this->assertStringNotContainsString(self::CHAVE, $gemini['url']);
        $this->assertContains('IMAGE', $gemini['corpo']['generationConfig']['responseModalities']);
        $this->assertStringContainsString('landscape', $gemini['corpo']['contents'][0]['parts'][0]['text']);

        $openai = ia_provedor_pedido_imagem($this->servidor('openai'), ['prompt' => 'Um farol', 'tamanho' => 'gigante']);
        $this->assertSame('https://api.openai.com/v1/images/generations', $openai['url']);
        $this->assertSame(['gpt-image-1', '1024x1024', 1], [$openai['corpo']['model'], $openai['corpo']['size'], $openai['corpo']['n']]);

        $this->assertArrayHasKey('erro', ia_provedor_pedido_imagem($this->servidor('anthropic'), ['prompt' => 'Um farol']));
        $this->assertArrayHasKey('erro', ia_provedor_pedido_imagem($this->servidor('openai'), ['prompt' => '  ']));
    }

    public function testRespostaDeImagem(): void
    {
        $png = base64_encode('conteudo-de-imagem');
        $gemini = ia_provedor_resposta_imagem('gemini', 200, ['candidates' => [['content' => ['parts' => [
            ['text' => 'Aqui está'],
            ['inlineData' => ['mimeType' => 'image/png', 'data' => $png]],
            ['inlineData' => ['mimeType' => 'text/html', 'data' => $png]],
        ]]]]]);
        $this->assertSame('success', $gemini['status']);
        $this->assertSame([['mime' => 'image/png', 'base64' => $png]], $gemini['imagens']);

        $openai = ia_provedor_resposta_imagem('openai', 200, ['data' => [['b64_json' => $png], ['b64_json' => 'não é base64!']]]);
        $this->assertCount(1, $openai['imagens']);

        $this->assertSame('error', ia_provedor_resposta_imagem('openai', 200, ['data' => []])['status']);
        $this->assertSame('error', ia_provedor_resposta_imagem('gemini', 500, ['error' => ['message' => 'falhou']])['status']);
    }
}
