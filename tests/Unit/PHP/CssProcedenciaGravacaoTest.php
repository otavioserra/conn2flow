<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * BATCH-144 fase 4 (req-141 / CR-002) — carimbo da procedência no salvamento dos recursos.
 *
 * Todo módulo que grava recurso precisa carimbar de que autoria o CSS veio. Sem isso o recurso nasce
 * sem procedência, conta como stale para sempre e a auditoria nunca zera — e conteúdo criado online
 * continuaria dependendo de alguém lembrar de rodar `c2f css:rebuild` depois.
 *
 * O teste estrutural existe porque a regressão provável aqui não é lógica, é OMISSÃO: alguém
 * acrescenta um fluxo novo de gravação (um "duplicar", um import) e esquece o carimbo.
 */
final class CssProcedenciaGravacaoTest extends TestCase
{
    /** Módulos que gravam recurso e os arquivos correspondentes. */
    private const MODULOS = [
        'admin-paginas',
        'admin-layouts',
        'admin-componentes',
        'admin-templates',
        'publisher-pages',
    ];

    private static function arquivo(string $modulo): string
    {
        return CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
            . $modulo . DIRECTORY_SEPARATOR . $modulo . '.php';
    }

    public function testTodoPontoDeGravacaoDeRecursoCarimbaAProcedencia(): void
    {
        // `css_compiled` marca cada ponto que grava CSS de recurso (adicionar, editar, clonar).
        // Cada um deles precisa de um carimbo ao lado — senão aquele fluxo nasce sem procedência.
        foreach (self::MODULOS as $modulo) {
            $codigo = (string)file_get_contents(self::arquivo($modulo));

            $pontos = preg_match_all('/campo_nome = "css_compiled"/', $codigo);
            $carimbos = preg_match_all(
                '/gestor_css_procedencia_para_recurso\(|publisher_pages_css_derivado\(\s*\$publisher_id/',
                $codigo
            );

            self::assertGreaterThan(0, $pontos, $modulo . ' deveria gravar css_compiled');
            self::assertGreaterThanOrEqual(
                $pontos,
                $carimbos,
                $modulo . ": {$pontos} ponto(s) de gravação e apenas {$carimbos} carimbo(s)"
            );
        }
    }

    public function testOCarimboUsaAAutoriaQueFicaGravada(): void
    {
        // No editar, o campo pode não ter vindo no POST: usar `$_REQUEST` cru assinaria contra vazio
        // e marcaria como stale um recurso que está íntegro.
        foreach (['admin-paginas', 'admin-layouts', 'admin-componentes', 'admin-templates'] as $modulo) {
            $codigo = (string)file_get_contents(self::arquivo($modulo));

            if (strpos($codigo, "\$editar['dados'][]") === false) {
                continue;
            }

            self::assertStringContainsString(
                "banco_select_campos_antes('html')",
                $codigo,
                $modulo . ': o carimbo do editar precisa cair no valor atual quando o campo não veio'
            );
        }
    }

    public function testPublisherPagesHerdaOCssDoTemplate(): void
    {
        // Publicação nasce no banco e o compilador offline nunca a vê: sem herdar o CSS do template
        // que a gerou, ela é servida sem uma única utility própria.
        $codigo = (string)file_get_contents(self::arquivo('publisher-pages'));

        self::assertStringContainsString('function publisher_pages_css_derivado(', $codigo);
        self::assertStringContainsString("'tabela' => 'templates'", $codigo);
        self::assertStringContainsString("\$campos[] = Array('css_precompiled'", $codigo);
    }

    public function testHelperDegradaComSegurancaSemSchema(): void
    {
        // Sem banco (ou antes da migração) a função não pode explodir nem inventar assinatura: o
        // salvamento do recurso não pode falhar por causa do carimbo.
        self::assertSame('', gestor_css_procedencia_para_recurso('<div class="flex"></div>', '', '', 'paginas'));
    }

    public function testHelperNaoAssinaRecursoSemAutoria(): void
    {
        self::assertSame('', gestor_css_procedencia_para_recurso('', '', '', 'paginas'));
    }

    public function testOsModulosNaoGravamCssPrecompiledDoUsuarioComoAutoria(): void
    {
        // `css_precompiled` é DERIVADO: só o compilador e a herança do template o escrevem. Um módulo
        // gravando o valor cru do POST devolveria ao operador a capacidade de descolar CSS de HTML.
        foreach (['admin-paginas', 'admin-layouts', 'admin-componentes', 'admin-templates'] as $modulo) {
            $codigo = (string)file_get_contents(self::arquivo($modulo));

            self::assertStringNotContainsString(
                '$campo_nome = "css_precompiled"; $post_nome',
                $codigo,
                $modulo . ' não pode gravar css_precompiled vindo do formulário'
            );
        }
    }
}
