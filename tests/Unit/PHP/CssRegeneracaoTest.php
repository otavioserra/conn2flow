<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * BATCH-144 (req-141 / CR-002) — contrato dos scripts de auditoria e regeneração de CSS.
 *
 * Estes dois scripts são a metade operacional da correção: um mede o acervo, o outro o conserta
 * compilando contra o HTML que o runtime realmente serve (o do banco). O que se trava aqui são as
 * invariantes que fazem eles serem seguros de rodar — porque um deles ESCREVE no banco.
 */
final class CssRegeneracaoTest extends TestCase
{
    private static function script(string $nome): string
    {
        return CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'controladores' . DIRECTORY_SEPARATOR
            . 'agents' . DIRECTORY_SEPARATOR . 'arquitetura' . DIRECTORY_SEPARATOR . $nome;
    }

    public function testOsDoisScriptsExistemESaoSintaticamenteValidos(): void
    {
        foreach (['css-auditoria.php', 'css-regenerar.php'] as $nome) {
            $caminho = self::script($nome);
            self::assertFileExists($caminho);

            $saida = [];
            $codigo = 1;
            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($caminho) . ' 2>&1', $saida, $codigo);
            self::assertSame(0, $codigo, $nome . ': ' . implode("\n", $saida));
        }
    }

    public function testAuditoriaNaoEscreveNoBanco(): void
    {
        // O auditor é a ferramenta que se roda em produção para diagnosticar: ele mede, não conserta.
        $codigo = (string)file_get_contents(self::script('css-auditoria.php'));

        foreach (['UPDATE ', 'INSERT ', 'DELETE ', 'ALTER '] as $comando) {
            self::assertStringNotContainsStringIgnoringCase(
                $comando,
                $codigo,
                'o auditor não pode conter ' . trim($comando)
            );
        }
    }

    public function testAmbosAuditamApenasRecursosTailwind(): void
    {
        // Recurso Fomantic recebe a folha inteira por <link> de CDN; contá-lo como "sem CSS" daria
        // um número grande e falso, e regenerá-lo produziria CSS que ninguém usa.
        foreach (['css-auditoria.php', 'css-regenerar.php'] as $nome) {
            $codigo = (string)file_get_contents(self::script($nome));
            self::assertStringContainsString("!== 'tailwindcss'", $codigo, $nome);
        }
    }

    public function testRegeneradorPreservaAAutoriaEEscreveApenasODerivado(): void
    {
        $codigo = (string)file_get_contents(self::script('css-regenerar.php'));

        // A única escrita permitida é do derivado + a assinatura. Tocar em `html` ou `css` aqui
        // significaria o compilador sobrescrevendo o trabalho do autor.
        self::assertStringContainsString('UPDATE `{$tabela}` SET css_precompiled=?', $codigo);
        self::assertStringNotContainsString('SET html=', $codigo);
        self::assertStringNotContainsString('SET css=', $codigo);
    }

    public function testRegeneradorTemModoDeSimulacao(): void
    {
        // Escrever em 1.400 registros sem poder olhar antes seria irresponsável.
        $codigo = (string)file_get_contents(self::script('css-regenerar.php'));

        self::assertStringContainsString("dry-run", $codigo);
        self::assertStringContainsString('if ($dryRun) {', $codigo);
    }

    public function testRegeneradorUsaCaminhoAbsolutoDoGestor(): void
    {
        // O Tailwind roda com o cwd no gestor; caminho relativo faria o input virar
        // `<gestor>/<gestor>/...` e o CLI recusaria o arquivo.
        $codigo = (string)file_get_contents(self::script('css-regenerar.php'));

        self::assertStringContainsString('$gestorPath = realpath($gestorPath)', $codigo);
    }

    public function testRegeneradorMantemAsCamadasDoBuildOffline(): void
    {
        // Layout carrega theme/base/preflight; recurso isolado importa só utilities. Divergir disso
        // faria o CSS regenerado brigar na cascata com o que o resources:sync produz.
        $codigo = (string)file_get_contents(self::script('css-regenerar.php'));

        self::assertStringContainsString('@import "\' . $central . \'";', $codigo);
        self::assertStringContainsString('@reference "\' . $central . \'";', $codigo);
        self::assertStringContainsString('layer(utilities) source(none)', $codigo);
    }

    public function testCompiladorRecebeAsFontesDeclaradasComoParametro(): void
    {
        // Regressão real: a assinatura perdeu `$fontesExtras` numa edição e o `foreach` passou a
        // iterar sobre variável inexistente. O PHP só emitia warning — engolido pelo log em
        // background — e as `tailwind_sources` NUNCA eram aplicadas, silenciosamente.
        $codigo = (string)file_get_contents(self::script('css-regenerar.php'));

        self::assertMatchesRegularExpression(
            '/function regenerarCompilar\(\s*(?:[^)]*?)array \$fontesExtras = \[\]\s*\)/s',
            $codigo,
            'regenerarCompilar() precisa declarar $fontesExtras; sem o parâmetro o foreach usa variável indefinida'
        );

        // E quem chama tem de passar de fato — declarar sem usar seria o mesmo defeito.
        self::assertStringContainsString('$fontesExtras', $codigo);
        self::assertStringContainsString('regenerarFontesDeclaradas(', $codigo);
    }

    public function testRegeneradorDeclaraOAlvoAntesDeGravar(): void
    {
        // O comando escreve em massa e projetos de teste e produção compartilham o mesmo mirror:
        // imprimir base, host e `.env` antes de qualquer escrita é o que permite perceber o engano.
        $codigo = (string)file_get_contents(self::script('css-regenerar.php'));

        self::assertStringContainsString('alvo da gravação', $codigo);
        self::assertStringContainsString('confirmar-remoto', $codigo);
        self::assertStringContainsString("'localhost', '127.0.0.1'", $codigo);
    }

    public function testPipelinesIncluemAEtapaDeRegeneracao(): void
    {
        // Sem esta etapa, todo deploy que preserva autoria deixa o acervo em estado híbrido e
        // alguém precisa lembrar de rodar o rebuild — a classe de falha que o req-141 elimina.
        $cli = dirname(CONN2FLOW_GESTOR_ROOT) . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR
            . 'src' . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR;

        foreach (['ProjectUpdateAllCommand.php', 'ManagerUpdateAllCommand.php'] as $arquivo) {
            $codigo = (string)file_get_contents($cli . $arquivo);

            self::assertStringContainsString('new CssRebuildCommand(', $codigo, $arquivo);
            // Não-fatal: as etapas essenciais já foram aplicadas quando a regeneração falha.
            self::assertStringContainsString('Não aborta', $codigo, $arquivo . ' deve avisar sem abortar');
        }
    }

    public function testCssRebuildRespeitaAFlagLocalDoEnvironment(): void
    {
        // A autoridade sobre "posso gravar sem perguntar?" é declarativa, não decorada.
        $cli = dirname(CONN2FLOW_GESTOR_ROOT) . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR
            . 'src' . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR . 'CssRebuildCommand.php';
        $codigo = (string)file_get_contents($cli);

        self::assertStringContainsString("\$config['local']", $codigo);
        self::assertStringContainsString('local=false', $codigo);
        self::assertStringContainsString('confirmar-remoto', $codigo);
    }

    public function testRegeneradorCarimbaAProcedenciaDoQueGera(): void
    {
        // Sem o carimbo, o recurso seria recompilado eternamente e a auditoria nunca zeraria.
        $codigo = (string)file_get_contents(self::script('css-regenerar.php'));

        self::assertStringContainsString('gestor_css_procedencia_assinatura($entradas)', $codigo);
        self::assertStringContainsString('css_source_hash=?', $codigo);
    }
}
