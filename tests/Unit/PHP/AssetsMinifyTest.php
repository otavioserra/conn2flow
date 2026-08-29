<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-145 / BATCH-148 — minificação do JavaScript próprio.
 *
 * A sugestão original era minificar no roteamento. Não é ali: o `arquivo-estatico` ganhou no
 * BATCH-100 `Content-Length`, `Accept-Ranges`, `ETag` e `304`, e os quatro dependem de o corpo
 * entregue ser exatamente o arquivo em disco. Estes testes existem para que ninguém "otimize" o
 * controlador de volta para a transformação em tempo de resposta.
 */
final class AssetsMinifyTest extends TestCase
{
    private static function controlador(): string
    {
        return (string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'controladores' . DIRECTORY_SEPARATOR
            . 'arquivo-estatico' . DIRECTORY_SEPARATOR . 'arquivo-estatico.php'
        );
    }

    private static function comando(): string
    {
        return (string)file_get_contents(
            dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR . 'AssetsMinifyCommand.php'
        );
    }

    public function testOControladorEscolheOArquivoEmVezDeTransformarOCorpo(): void
    {
        // Esta é a asserção central do lote. Minificar na entrega quebraria as quatro garantias de
        // HTTP de uma vez; escolher qual arquivo enviar não quebra nenhuma.
        $codigo = self::controlador();

        self::assertStringContainsString('function arquivo_estatico_preferir_minificado(', $codigo);

        // Nada de minificador dentro do controlador de entrega.
        self::assertStringNotContainsString('terser', $codigo);
        self::assertStringNotContainsString('JSMin', $codigo);

        // O envio continua sendo um envio de arquivo.
        self::assertStringContainsString('arquivo_estatico_enviar(arquivo_estatico_preferir_minificado(', $codigo);
    }

    public function testAEscolhaAconteceDepoisDaAutorizacao(): void
    {
        // O containment é decidido num lugar só (`resolver_autorizado`). Escolher o derivado ANTES
        // dela criaria um segundo caminho por onde um arquivo poderia escapar da guarda.
        $codigo = self::controlador();

        $posAutorizacao = strpos($codigo, '$fileResolvido = arquivo_estatico_resolver_autorizado($file, $basesAutorizadas);');
        $posEscolha = strpos($codigo, 'arquivo_estatico_enviar(arquivo_estatico_preferir_minificado(');

        self::assertIsInt($posAutorizacao);
        self::assertIsInt($posEscolha);
        self::assertGreaterThan($posAutorizacao, $posEscolha);
    }

    public function testDesenvolvimentoServeOFonte(): void
    {
        // Depurar JavaScript com nomes de variável destruídos pelo `--mangle` é pior do que baixar
        // alguns KB a mais. Isso também torna inofensivo o derivado envelhecer enquanto alguém edita
        // o fonte — quem edita está em dev.
        $codigo = self::controlador();

        self::assertMatchesRegularExpression(
            "/if\\(!empty\\(\\\$_GESTOR\\['development-env'\\]\\)\\) return \\\$file;/",
            $codigo
        );
    }

    public function testDerivadoNaoGeraDerivado(): void
    {
        // Sem esta guarda, `x.min.js` procuraria `x.min.min.js` a cada requisição.
        self::assertStringContainsString("substr(\$file, -7) === '.min.js'", self::controlador());
        self::assertStringContainsString("substr(\$caminho, -7) === '.min.js'", self::comando());
    }

    public function testDerivadoVazioNaoEServido(): void
    {
        // Minificação interrompida no meio deixa arquivo de tamanho zero. Servi-lo entregaria uma
        // tela sem comportamento nenhum, sem erro no servidor.
        self::assertStringContainsString('filesize($minificado) > 0', self::controlador());
    }

    public function testASaidaEValidadaAntesDeSubstituirOFonte(): void
    {
        // `node --check` não prova semântica, mas impede que sintaxe inválida chegue ao navegador.
        $comando = self::comando();

        self::assertStringContainsString('node --check', $comando);
        self::assertStringContainsString('$statusCheck !== 0', $comando);
    }

    public function testTerceirosFicamDeForaDaMinificacao(): void
    {
        // Terceiros já vêm minificados de fábrica; reminificá-los é risco sem ganho.
        $comando = self::comando();

        foreach (['/vendor/', '/datatables/', '/jQuery-File-Upload', '/node_modules/'] as $trecho) {
            self::assertStringContainsString("'" . $trecho . "'", $comando);
        }
    }

    public function testOsPipelinesRodamAMinificacao(): void
    {
        // Fora do pipeline, a etapa vira "alguém precisa lembrar" — e um derivado velho serviria
        // código antigo com cara de novo.
        $cli = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR;

        foreach (['ManagerUpdateAllCommand.php', 'ProjectUpdateAllCommand.php'] as $arquivo) {
            $codigo = (string)file_get_contents($cli . $arquivo);

            self::assertStringContainsString('new AssetsMinifyCommand($this->rootPath)', $codigo, $arquivo);
            // Não fatal: máquina sem Node precisa conseguir rodar o pipeline inteiro.
            self::assertStringContainsString('A minificação não completou', $codigo, $arquivo);
        }
    }

    public function testOManifestoDeProcedenciaCobreOsDerivadosPresentes(): void
    {
        // O manifesto é o que permite `--verificar` acusar derivado velho. Se ele descolar dos
        // arquivos, a checagem passa a mentir.
        $manifestoPath = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'minify-manifest.json';

        if (!is_file($manifestoPath)) {
            self::markTestSkipped('minify-manifest.json ainda não foi gerado neste ambiente.');
        }

        $manifesto = json_decode((string)file_get_contents($manifestoPath), true);
        self::assertIsArray($manifesto);
        self::assertNotEmpty($manifesto);

        foreach ($manifesto as $relativo => $dados) {
            self::assertArrayHasKey('sha1', $dados, $relativo);
            self::assertSame(40, strlen((string)$dados['sha1']), $relativo);
        }
    }
}
