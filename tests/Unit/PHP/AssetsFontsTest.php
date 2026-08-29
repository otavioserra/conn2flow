<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-143 / BATCH-148 — self-hosting de Google Fonts.
 *
 * Um `<link>` para `fonts.googleapis.com` faz o navegador de CADA visitante entregar ao Google o IP,
 * o `Referer` (a página exata que a pessoa está lendo) e o `User-Agent`, antes de qualquer
 * consentimento. Em 2022 o Landgericht München I condenou o operador de um site exatamente por isso.
 *
 * As checagens abaixo são estruturais porque as regressões prováveis são silenciosas: alguém cola um
 * `<link>` do Google num layout novo, ou o comando passa a baixar `ttf` em vez de `woff2` porque o
 * User-Agent deixou de parecer um navegador moderno.
 */
final class AssetsFontsTest extends TestCase
{
    /**
     * O codigo sem comentarios.
     *
     * Asserir "nao contem X" sobre o arquivo inteiro bate no proprio comentario que EXPLICA por que
     * X nao e usado — o teste falharia por o codigo estar bem documentado.
     */
    private static function codigoSemComentarios(): string
    {
        $limpo = '';

        foreach (token_get_all(self::comando()) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $limpo .= is_array($token) ? $token[1] : $token;
        }

        return $limpo;
    }

    private static function comando(): string
    {
        return (string)file_get_contents(
            dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR . 'AssetsFontsCommand.php'
        );
    }

    public function testPedeWoff2ComUserAgentDeNavegadorModerno(): void
    {
        // O Google decide o formato pelo User-Agent: sem um moderno ele devolve `ttf`, que é várias
        // vezes maior e não traz a compressão que justifica o formato web.
        $codigo = self::comando();

        self::assertStringContainsString('UA_MODERNO', $codigo);
        self::assertMatchesRegularExpression('/Chrome\/\d+/', $codigo);
        self::assertStringContainsString('CURLOPT_USERAGENT', $codigo);
    }

    public function testPreservaOUnicodeRangeDeCadaFace(): void
    {
        // O `unicode-range` é o que faz o navegador baixar SÓ o subset de que precisa. Removê-lo
        // transformaria a economia do filtro de subset em desperdício de banda do visitante.
        $codigo = self::comando();

        // A reescrita substitui APENAS o `src`; o resto do bloco (incluindo o `unicode-range`)
        // atravessa intacto, porque o `preg_replace` ancora no `src:` e devolve o bloco original.
        self::assertStringContainsString('src: url(', $codigo);
        self::assertStringContainsString('bloco', $codigo);

        // O bloco nunca e reconstruido campo a campo — seria a forma de perder o unicode-range.
        $reescrita = substr($codigo, (int)strpos($codigo, 'private function reescreverFace'));
        self::assertStringNotContainsString('font-family:', $reescrita);
    }

    public function testFiltraSubsetsPorPadraoMasPermiteTodos(): void
    {
        // Um site em português não precisa de cirílico, grego nem vietnamita: das 78 faces devolvidas
        // para as quatro famílias do transformamp, 50 nunca seriam pedidas por um leitor brasileiro.
        $codigo = self::comando();

        self::assertStringContainsString("SUBSETS_PADRAO = 'latin,latin-ext'", $codigo);
        self::assertStringContainsString("hasOption('todos')", $codigo);
    }

    public function testEscreveNaFonteDoProjetoENaoNoEspelho(): void
    {
        // `ProjectEnvironmentResolver::resolve()` prefere `path_tests` (o espelho), mas o que o
        // pipeline lê e o deploy publica é `path`. Gravar no espelho faria o trabalho sumir no
        // próximo sync, sem erro nenhum.
        $codigo = self::comando();

        self::assertStringContainsString('private function caminhoFonte(', $codigo);
        self::assertStringContainsString("\$config['path']", $codigo);
    }

    public function testRespeitaATravaDeAmbienteRemoto(): void
    {
        // Mesma trava do `css:rebuild`: o comando ESCREVE no repositório do projeto, e projeto de
        // teste e de produção compartilham a mesma fonte.
        $codigo = self::comando();

        self::assertStringContainsString("\$config['local'] ?? false", $codigo);
        self::assertStringContainsString("hasOption('confirmar-remoto')", $codigo);
    }

    public function testNuncaDesligaAVerificacaoDeCertificado(): void
    {
        $codigo = self::codigoSemComentarios();

        self::assertStringNotContainsString('SSL_VERIFYPEER', $codigo);
        self::assertStringNotContainsString('--insecure', $codigo);
        self::assertStringNotContainsString(' -k ', $codigo);
    }

    public function testNenhumRecursoDoCoreCarregaFonteDoGoogle(): void
    {
        // O core não deve reintroduzir a dependência que o projeto acabou de eliminar.
        $arquivos = array_merge(
            glob(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . '*'
                . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '*'
                . DIRECTORY_SEPARATOR . '*.html') ?: [],
            glob(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . '*'
                . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . '*'
                . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . '*'
                . DIRECTORY_SEPARATOR . '*.html') ?: []
        );

        $reincidentes = [];
        foreach ($arquivos as $arquivo) {
            if (strpos((string)file_get_contents($arquivo), 'fonts.googleapis.com') !== false) {
                $reincidentes[] = basename($arquivo);
            }
        }

        self::assertSame([], $reincidentes, 'Google Fonts em: ' . implode(', ', $reincidentes));
    }
}
