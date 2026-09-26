<?php

declare(strict_types=1);

use Conn2Flow\Cli\Support\Docs\DocsAuditor;
use Conn2Flow\Cli\Support\Docs\DocsTree;
use Conn2Flow\Cli\Support\Docs\Frontmatter;
use Conn2Flow\Cli\Support\Docs\LibraryReference;
use Conn2Flow\Cli\Support\Docs\PhpFunctionExtractor;
use PHPUnit\Framework\TestCase;

require_once CONN2FLOW_ROOT . '/cli/src/Support/Docs/Frontmatter.php';
require_once CONN2FLOW_ROOT . '/cli/src/Support/Docs/PhpFunctionExtractor.php';
require_once CONN2FLOW_ROOT . '/cli/src/Support/Docs/LibraryReference.php';
require_once CONN2FLOW_ROOT . '/cli/src/Support/Docs/DocsTree.php';
require_once CONN2FLOW_ROOT . '/cli/src/Support/Docs/DocsAuditor.php';

/**
 * req-177 / BATCH-182: contrato de documentação, docs:audit e docs:extract.
 */
final class DocsToolingReq177Test extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . '/c2f-docs-' . bin2hex(random_bytes(4));
        mkdir($this->tmp . '/gestor/bibliotecas', 0777, true);
        mkdir($this->tmp . '/gestor/modulos/menus', 0777, true);
    }

    protected function tearDown(): void
    {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tmp, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($this->tmp);
    }

    public function testFrontmatterLeEscalaresListasEAspas(): void
    {
        $fm = Frontmatter::parse("\xEF\xBB\xBF---\r\ntitle: \"Olá: mundo\"\r\norder: 20\r\nsources:\r\n  - a.php\r\n  - 'b.php'\r\n---\r\n# Corpo\n");

        self::assertTrue($fm['has']);
        self::assertSame('Olá: mundo', $fm['meta']['title']);
        self::assertSame(20, $fm['meta']['order']);
        self::assertSame(['a.php', 'b.php'], $fm['meta']['sources']);
        self::assertSame("# Corpo\n", $fm['body']);
        self::assertSame([], $fm['errors']);
    }

    public function testFrontmatterAusenteEInvalido(): void
    {
        self::assertFalse(Frontmatter::parse("# Sem frontmatter\n")['has']);
        $fm = Frontmatter::parse("---\n  - solto\nTitulo Errado\n---\n");
        self::assertCount(2, $fm['errors']);
    }

    public function testExtratorPegaFuncoesCondicionaisIgnoraClassesEClosures(): void
    {
        $code = <<<'PHP'
<?php
/**
 * @param string $a
 * @return string
 */
function lib_um($a, array $b = [], ...$resto) { $f = function($x) { return $x; }; return ''; }
if (!function_exists('lib_dois')) {
    /** @param int $n */
    function lib_dois(&$n, ?string $s = null): ?int { return 1; }
}
/**
 * @param array|false $params
 * @param string $params['codigo']
 */
function lib_quatro($params = false) {}
class Foo { public function metodo() {} }
$x = Foo::class;
function lib_tres() {}
PHP;
        $fns = PhpFunctionExtractor::extract($code);

        self::assertSame(['lib_um', 'lib_dois', 'lib_quatro', 'lib_tres'], array_column($fns, 'name'));
        self::assertSame('lib_quatro(array|false $params = false)', PhpFunctionExtractor::signatureText($fns[2]), 'chaves $params[...] do docblock não são o parâmetro');
        self::assertSame('lib_um(string $a, array $b = [], ...$resto): string', PhpFunctionExtractor::signatureText($fns[0]));
        self::assertSame('lib_dois(int &$n, ?string $s = null): ?int', PhpFunctionExtractor::signatureText($fns[1]));
        self::assertSame(6, $fns[0]['line']);
    }

    public function testExtratorLeTiposGenericosComEspacos(): void
    {
        $code = "<?php\n"
            . "/**\n * @param list<string> \$nomes Nomes.\n * @param array<string, int> \$mapa\n"
            . " * @return array{css: list<string>, js: list<string>}\n */\n"
            . "function lib_tags(\$nomes, \$mapa){}\n";
        $fns = PhpFunctionExtractor::extract($code);

        self::assertSame(
            'lib_tags(list<string> $nomes, array<string, int> $mapa): array{css: list<string>, js: list<string>}',
            PhpFunctionExtractor::signatureText($fns[0])
        );
    }

    public function testExtratorBateComAsBibliotecasReaisDoCore(): void
    {
        $fns = PhpFunctionExtractor::extract((string)file_get_contents(CONN2FLOW_ROOT . '/gestor/bibliotecas/modelo.php'));
        self::assertCount(10, $fns);

        // arquivo.php declara tudo dentro de `if (!function_exists(...))`.
        $arquivo = (string)file_get_contents(CONN2FLOW_ROOT . '/gestor/bibliotecas/arquivo.php');
        self::assertCount(preg_match_all('/^\s*function\s+\w+\s*\(/m', $arquivo), PhpFunctionExtractor::extract($arquivo));
    }

    public function testBlocoExtraidoESubstituidoDeFormaIdempotente(): void
    {
        $fns = PhpFunctionExtractor::extract("<?php\nfunction x_a(\$b){}\n");
        $block = LibraryReference::render($fns, 'gestor/bibliotecas/x.php', 'ai-workspace/pt-br/docs/reference/libraries/x.md', 'pt-br');

        self::assertStringContainsString('(../../../../../gestor/bibliotecas/x.php#L2)', $block);

        $doc = "# X\r\n\r\n## Funções\r\n";
        $uma = LibraryReference::replace($doc, $block);
        $duas = LibraryReference::replace($uma, $block);

        self::assertSame($uma, $duas);
        self::assertSame($block, LibraryReference::current($uma), 'current() normaliza CRLF para comparar com o render');
        self::assertStringNotContainsString("\n\n", str_replace("\r\n", '', $uma), 'o arquivo CRLF continua CRLF');
        self::assertStringNotContainsString('x_a', LibraryReference::withoutBlock($uma));
    }

    public function testBlocoIncluiResumoParametrosChavesERetornoSemAlterarFuncaoSemDocblock(): void
    {
        $code = <<<'PHP'
<?php
/**
 * Busca dados da página.
 *
 * A explicação longa não faz parte do resumo.
 * @param array $params Opções da busca.
 * @param string $params['slug'] Identificador da página.
 * @return array Dados encontrados.
 */
function buscar($params) {}
function simples($x) {}
PHP;
        $functions = PhpFunctionExtractor::extract($code);
        self::assertSame('buscar(array $params): array', PhpFunctionExtractor::signatureText($functions[0]));
        self::assertSame('Busca dados da página.', $functions[0]['description']);
        self::assertSame(['$params' => 'Opções da busca.', "$" . "params['slug']" => 'Identificador da página.'], $functions[0]['paramDescriptions']);
        self::assertSame('Dados encontrados.', $functions[0]['returnDescription']);
        $block = LibraryReference::render($functions, 'gestor/bibliotecas/x.php', 'ai-workspace/pt-br/docs/reference/libraries/x.md', 'pt-br');
        self::assertStringContainsString('  - `$params[\'slug\']`: Identificador da página.', $block);
        self::assertStringContainsString('  Retorno: Dados encontrados.', $block);
        self::assertStringContainsString('- `simples($x)` — [linha 11]', $block);
        self::assertStringNotContainsString('  Parâmetros:', substr($block, strpos($block, '- `simples($x)`')));
    }

    public function testResolveLinksRelativos(): void
    {
        self::assertSame('reference/modules/menus.md', DocsAuditor::resolve('reference/libraries', '../modules/menus.md'));
        self::assertSame('guides/a.md', DocsAuditor::resolve('.', 'guides/a.md'));
        self::assertNull(DocsAuditor::resolve('guides', '../../fora.md'));
    }

    public function testAuditorAcusaParAusenteSecaoErradaLinkQuebradoEFontesAlteradas(): void
    {
        file_put_contents($this->tmp . '/gestor/bibliotecas/lib.php', "<?php\nfunction lib_a(){}\nfunction lib_b(){}\n");
        $this->doc('pt-br', 'reference/libraries/lib.md', "---\ntitle: T\ndescription: D\nsection: guides\nsources:\n  - gestor/bibliotecas/lib.php\n  - gestor/nao-existe.php\nverified_at: abc123\n---\nFala de lib_a. [x](../modules/nada.md)\n");
        $this->doc('pt-br', 'guides/ok.md', "---\ntitle: T\ndescription: D\nsection: guides\nverified_at: abc123\n---\nOk.\n");
        $this->doc('en', 'guides/ok.md', "---\ntitle: T\ndescription: D\nsection: guides\nverified_at: abc123\n---\nOk.\n");
        $this->doc('pt-br', 'LEGADO.md', "# velho\n");

        $git = static function (array $args): array {
            if ($args[0] === 'rev-parse') {
                return ['code' => 0, 'stdout' => "abc123\n"];
            }

            return ['code' => 0, 'stdout' => "f1\nf2\n"];
        };
        $report = (new DocsAuditor(new DocsTree($this->tmp), $git))->audit();
        $byItem = array_column($report['items'], null, 'item');

        $msgs = implode("\n", array_column($byItem['pt-br:reference/libraries/lib.md']['issues'], 'message'));
        self::assertStringContainsString("section 'guides' diverge da pasta ('reference')", $msgs);
        self::assertStringContainsString('Sem par em en', $msgs);
        self::assertStringContainsString('Fonte inexistente: gestor/nao-existe.php', $msgs);
        self::assertStringContainsString('Fontes mudaram em 2 commit(s)', $msgs);
        self::assertStringContainsString('Link quebrado: ../modules/nada.md', $msgs);
        self::assertStringContainsString('Sem bloco c2f:extract', $msgs);

        self::assertSame(0, $byItem['pt-br:guides/ok.md']['score']);
        self::assertArrayHasKey('missing:reference/modules/menus.md', $byItem);
        self::assertSame(1, $report['legacy']['pt-br']);
        self::assertSame('pt-br:reference/libraries/lib.md', $report['items'][0]['item'], 'o pior item encabeça o ranking');
    }

    public function testAuditorAcusaFuncoesSemExplicacaoEBlocoDesatualizado(): void
    {
        file_put_contents($this->tmp . '/gestor/bibliotecas/lib.php', "<?php\nfunction lib_a(){}\nfunction lib_b(){}\n");
        $tree = new DocsTree($this->tmp);
        $block = LibraryReference::render([['name' => 'lib_a', 'line' => 2, 'params' => [], 'return' => '']], 'gestor/bibliotecas/lib.php', $tree->repoRelative('pt-br', 'reference/libraries/lib.md'), 'pt-br');
        $this->doc('pt-br', 'reference/libraries/lib.md', "---\ntitle: T\ndescription: D\nsection: reference\nsources:\n  - gestor/bibliotecas/lib.php\nverified_at: abc\n---\nSó lib_a.\n\n" . $block . "\n");

        $issues = (new DocsAuditor($tree, static fn (): array => ['code' => 0, 'stdout' => '']))->auditDoc('pt-br', 'reference/libraries/lib.md');
        $msgs = implode("\n", array_column($issues, 'message'));

        self::assertStringContainsString('Bloco c2f:extract desatualizado', $msgs);
        self::assertStringContainsString('1 função(ões) sem explicação no texto: lib_b', $msgs);
    }

    private function doc(string $lang, string $rel, string $content): void
    {
        $path = $this->tmp . '/ai-workspace/' . $lang . '/docs/' . $rel;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $content);
    }
}
