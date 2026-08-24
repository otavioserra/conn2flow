<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-128 (BATCH-130) — regressões de compatibilidade com PHP 8.1+ e schema dedicado.
 */
final class Req128HardeningTest extends TestCase
{
    public function testBancoNumRowsAceitaResultadoFalsoSemTypeError(): void
    {
        global $_BANCO;

        $bancoAnterior = $_BANCO;
        $_BANCO['tipo'] = 'mysqli';

        try {
            self::assertSame(0, banco_num_rows(false));
            self::assertSame(0, banco_num_rows(null));
            self::assertSame(0, banco_num_rows(new stdClass()));
        } finally {
            $_BANCO = $bancoAnterior;
        }
    }

    public function testBancoSelectConfereOResultadoAntesDeLerLinhas(): void
    {
        $codigo = (string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'banco.php'
        );

        self::assertStringContainsString('if($res && banco_num_rows($res)){', $codigo);
    }

    public function testRedefinicaoDeSenhaConsultaHostSomenteComATabelaPresente(): void
    {
        $codigo = (string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
            . 'perfil-usuario' . DIRECTORY_SEPARATOR . 'perfil-usuario.php'
        );

        $padrao = <<<'REGEX'
/\$id_hosts = null;.*if\(gestor_schema_tabela_existe\('usuarios_gestores_hosts'\)\)\{.*'tabela' => 'usuarios_gestores_hosts'/s
REGEX;

        self::assertMatchesRegularExpression($padrao, $codigo);
    }

    public function testCamposOpcionaisDePaginaNaoAcessamRequestIndefinido(): void
    {
        $codigo = (string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
            . 'admin-paginas' . DIRECTORY_SEPARATOR . 'admin-paginas.php'
        );

        $padraoSeguro = '/\\$campo_nome = "(?:raiz|sem_permissao)";[^\\r\\n]*'
            . 'if\\(!empty\\(\\$_REQUEST\\[\\$post_nome\\]\\)\\)/';
        $padraoInseguro = '/\\$campo_nome = "(?:raiz|sem_permissao)";[^\\r\\n]*'
            . 'if\\(\\$_REQUEST\\[\\$post_nome\\]\\)/';

        self::assertSame(4, preg_match_all($padraoSeguro, $codigo));
        self::assertDoesNotMatchRegularExpression($padraoInseguro, $codigo);
    }

    public function testLayoutSemCssCompiladoUsaValorVazio(): void
    {
        $codigo = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'gestor.php');

        self::assertStringContainsString("\$layout_css_compiled = \$layouts['css_compiled'] ?? '';", $codigo);
    }
}
