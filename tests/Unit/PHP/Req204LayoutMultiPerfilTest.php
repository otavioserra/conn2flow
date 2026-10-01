<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('SDD_NO_AUTORUN')) define('SDD_NO_AUTORUN', true);
if (!function_exists('tailwind_recursos_command_base')) {
    require_once dirname(__DIR__, 3) . '/gestor/controladores/agents/arquitetura/tailwind-recursos.php';
}

/**
 * req-204: o mesmo layout para vários perfis e a página compilada com todos os layouts que recebe.
 */
final class Req204LayoutMultiPerfilTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (function_exists('gestor_roteador_layout_perfil')) return;
        $source = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/gestor.php');
        self::assertSame(1, preg_match('/function gestor_roteador_layout_perfil\(.*?\r?\n}\r?\n/s', $source, $match));
        eval($match[0]);
    }

    public function testMesmoLayoutServeADoisPerfisSemSobrescrita(): void
    {
        $mapa = '{"cloud-nano":"layout-portal","cloud-pro":"layout-portal","equipe":"layout-equipe"}';

        self::assertSame('layout-portal', gestor_roteador_layout_perfil('layout-base', $mapa, 'cloud-nano'));
        self::assertSame('layout-portal', gestor_roteador_layout_perfil('layout-base', $mapa, 'cloud-pro'));
        self::assertSame('layout-equipe', gestor_roteador_layout_perfil('layout-base', $mapa, 'equipe'));
        self::assertSame('layout-base', gestor_roteador_layout_perfil('layout-base', $mapa, 'administradores'));
    }

    public function testMapaDescartaEntradasVaziasEAceitaObjetoDoManifesto(): void
    {
        self::assertSame(
            ['cliente' => 'portal'],
            gestor_layouts_perfis_mapa(['cliente' => ' portal ', 'vazio' => '', '' => 'x', 'lista' => ['a']])
        );
        self::assertSame(['cliente' => 'portal'], gestor_layouts_perfis_mapa((object)['cliente' => 'portal']));
        self::assertSame([], gestor_layouts_perfis_mapa('{invalid'));
        self::assertSame([], gestor_layouts_perfis_mapa(null));
    }

    public function testFormularioGravaIndexadoPorPerfil(): void
    {
        $processo = proc_open([PHP_BINARY, CONN2FLOW_ROOT . '/tests/Fixtures/Req204LayoutMapSave.php'], [
            0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $pipes, CONN2FLOW_ROOT);
        self::assertIsResource($processo);
        fclose($pipes[0]);
        $saida = stream_get_contents($pipes[1]);
        $erros = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($processo), $saida . $erros);
        self::assertSame('{"cliente":"portal","equipe":"portal"}', trim((string)$saida));
    }

    public function testMarcadorDeLayoutsEhEstavelELidoDeVolta(): void
    {
        $marcador = gestor_css_layouts_marcador(['layout-portal', 'layout-admin', 'layout-portal', '', 'a b']);

        self::assertSame('/*! c2f-layouts:layout-admin,layout-portal */', $marcador);
        self::assertSame(['layout-admin', 'layout-portal'], gestor_css_layouts_cobertos($marcador . "\n.a{color:red}"));
        self::assertSame([], gestor_css_layouts_cobertos('.a{color:red}'));
        self::assertSame('', gestor_css_layouts_marcador([]));
    }

    public function testPaginaMapeadaCompilaComLayoutPadraoEAlternativos(): void
    {
        global $GESTOR_DIR;
        $previous = $GESTOR_DIR ?? null;
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f_req203_' . bin2hex(random_bytes(5));
        $arquivos = [];
        foreach (['layout-admin', 'layout-portal'] as $layout) {
            $dir = $root . '/resources/pt-br/layouts/' . $layout;
            mkdir($dir, 0777, true);
            $arquivos[] = $dir . '/' . $layout . '.html';
            file_put_contents(end($arquivos), '<main class="lg:ml-64"></main>');
        }
        $GESTOR_DIR = $root;

        try {
            $metadata = [
                'id' => 'perfil', 'layout' => 'layout-admin',
                'layouts_users_profiles' => ['cliente' => 'layout-portal', 'equipe' => 'layout-portal', 'fora' => 'layout-ausente'],
            ];
            $layouts = tailwind_recursos_layouts_da_pagina($metadata, null, 'pt-br');

            // O layout que não existe nesta árvore fica para o css:rebuild, que lê do banco.
            self::assertSame(['layout-admin', 'layout-portal'], array_keys($layouts));
            self::assertSame([], tailwind_recursos_layouts_da_pagina(['id' => 'perfil', 'layout' => 'layout-admin'], null, 'pt-br'));

            // Compilação completa (theme/base), como um layout: o CSS da página vale sozinho.
            $recurso = ['layout' => false, 'bundle' => false, 'layouts_cobertos' => array_keys($layouts),
                'html' => $arquivos[0], 'sources' => array_values($layouts), 'safelist' => []];
            $input = tailwind_recursos_input_temporario($recurso, $arquivos[0], $root);
            self::assertStringContainsString('@import "', $input);
            self::assertStringNotContainsString('@reference', $input);
            self::assertStringContainsString('layout-portal.html', $input);

            $semMapa = $recurso;
            $semMapa['layouts_cobertos'] = [];
            self::assertNotSame(
                tailwind_recursos_fingerprint($recurso, 'c', '4'),
                tailwind_recursos_fingerprint($semMapa, 'c', '4')
            );
        } finally {
            if ($previous === null) unset($GESTOR_DIR);
            else $GESTOR_DIR = $previous;
            foreach ($arquivos as $arquivo) { @unlink($arquivo); @rmdir(dirname($arquivo)); }
            @rmdir($root . '/resources/pt-br/layouts');
            @rmdir($root . '/resources/pt-br');
            @rmdir($root . '/resources');
            @rmdir($root);
        }
    }

    public function testRegeneracaoERuntimeUsamOMarcador(): void
    {
        $regenerar = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/controladores/agents/arquitetura/css-regenerar.php');
        self::assertStringContainsString('gestor_layouts_perfis_mapa($linha[\'layouts_users_profiles\']', $regenerar);
        self::assertStringContainsString('layouts_users_profiles IS NOT NULL', $regenerar);
        self::assertStringContainsString('$marcadorVigente === $marcadorLayouts', $regenerar);

        $roteador = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/gestor.php');
        self::assertStringContainsString('gestor_css_layouts_cobertos((string)$css_precompiled)', $roteador);
    }
}
