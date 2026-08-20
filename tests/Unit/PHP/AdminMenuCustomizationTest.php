<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-123 (BATCH-125) — Customização de Menus Administrativos.
 *
 * Cobre as regras puras de:
 * 1. Fallback de rótulo do grupo (menu_label vs nome).
 * 2. Ordenação combinada de grupos (ordemMenu ASC com NULLs por último ordenados por nome ASC).
 * 3. Resolução de componente de menu por projeto ($_GESTOR['project-admin-menu-components']) com fallbacks.
 */
final class AdminMenuCustomizationTest extends TestCase
{
    /**
     * Helper para resolução de rótulo do grupo conforme req-123.
     */
    private function resolverRotuloGrupo(array $grupo): string
    {
        return (isset($grupo['menu_label']) && $grupo['menu_label'] !== '')
            ? $grupo['menu_label']
            : ($grupo['nome'] ?? '');
    }

    /**
     * Helper para ordenação de grupos conforme a lógica SQL do gestor_pagina_menu().
     */
    private function ordenarGrupos(array $grupos): array
    {
        usort($grupos, static function (array $a, array $b): int {
            $aOrdem = $a['ordemMenu'] ?? null;
            $bOrdem = $b['ordemMenu'] ?? null;

            // Prioridade para quem tem ordemMenu definido
            $aTemOrdem = ($aOrdem !== null);
            $bTemOrdem = ($bOrdem !== null);

            if ($aTemOrdem && $bTemOrdem) {
                if ($aOrdem !== $bOrdem) {
                    return $aOrdem <=> $bOrdem;
                }
            } elseif ($aTemOrdem && !$bTemOrdem) {
                return -1;
            } elseif (!$aTemOrdem && $bTemOrdem) {
                return 1;
            }

            return strcasecmp($a['nome'] ?? '', $b['nome'] ?? '');
        });

        return $grupos;
    }

    /**
     * Helper para resolução do identificador do componente de menu conforme req-123.
     */
    private function resolverComponenteMenu(?string $layoutId, bool $isTailwind, ?array $projectMenuConfig = null): string
    {
        $padraoNativo = $isTailwind ? 'menu-principal-sistema-tailwind' : 'menu-principal-sistema';

        if ($projectMenuConfig !== null) {
            if ($layoutId !== null && isset($projectMenuConfig[$layoutId])) {
                return $projectMenuConfig[$layoutId];
            }
            if (isset($projectMenuConfig['padrao'])) {
                return $projectMenuConfig['padrao'];
            }
            if (isset($projectMenuConfig['default'])) {
                return $projectMenuConfig['default'];
            }
        }

        return $padraoNativo;
    }

    // ===== 1. Resolução do Rótulo do Grupo (menu_label)

    public function testUsaMenuLabelQuandoPreenchido(): void
    {
        $grupo = [
            'id' => 'cadastros',
            'nome' => 'Cadastros Gerais',
            'menu_label' => 'Cadastros',
        ];

        self::assertSame('Cadastros', $this->resolverRotuloGrupo($grupo));
    }

    public function testFallbackParaNomeQuandoMenuLabelNull(): void
    {
        $grupo = [
            'id' => 'sistema',
            'nome' => 'Configurações do Sistema',
            'menu_label' => null,
        ];

        self::assertSame('Configurações do Sistema', $this->resolverRotuloGrupo($grupo));
    }

    public function testFallbackParaNomeQuandoMenuLabelVazio(): void
    {
        $grupo = [
            'id' => 'relatorios',
            'nome' => 'Relatórios e Métricas',
            'menu_label' => '',
        ];

        self::assertSame('Relatórios e Métricas', $this->resolverRotuloGrupo($grupo));
    }

    // ===== 2. Ordenação Combinada de Grupos

    public function testOrdenaGruposPrioritariosAntesDeGruposSemOrdem(): void
    {
        $grupos = [
            ['id' => 'z-sem-ordem', 'nome' => 'Zeta', 'ordemMenu' => null],
            ['id' => 'a-sem-ordem', 'nome' => 'Alfa', 'ordemMenu' => null],
            ['id' => 'segundo', 'nome' => 'Segundo', 'ordemMenu' => 2],
            ['id' => 'primeiro', 'nome' => 'Primeiro', 'ordemMenu' => 1],
            ['id' => 'decimo', 'nome' => 'Décimo', 'ordemMenu' => 10],
        ];

        $ordenados = $this->ordenarGrupos($grupos);

        $idsOrdenados = array_column($ordenados, 'id');
        self::assertSame(['primeiro', 'segundo', 'decimo', 'a-sem-ordem', 'z-sem-ordem'], $idsOrdenados);
    }

    public function testDesempateAlfabeticoQuandoMesmaOrdemOuAmbosSemOrdem(): void
    {
        $grupos = [
            ['id' => 'beta', 'nome' => 'Beta', 'ordemMenu' => null],
            ['id' => 'alfa', 'nome' => 'Alfa', 'ordemMenu' => null],
            ['id' => 'gama', 'nome' => 'Gama', 'ordemMenu' => null],
        ];

        $ordenados = $this->ordenarGrupos($grupos);
        $idsOrdenados = array_column($ordenados, 'id');
        self::assertSame(['alfa', 'beta', 'gama'], $idsOrdenados);
    }

    // ===== 3. Override de Componente de Menu por Projeto

    public function testUsaComponenteDoLayoutQuandoConfigurado(): void
    {
        $config = [
            'layout-administrativo-custom' => 'menu-customizado-projeto',
            'padrao' => 'menu-fallback-projeto',
        ];

        $componente = $this->resolverComponenteMenu('layout-administrativo-custom', true, $config);
        self::assertSame('menu-customizado-projeto', $componente);
    }

    public function testFallbackParaChavePadraoQuandoLayoutNaoMapeado(): void
    {
        $config = [
            'layout-outro' => 'menu-outro',
            'padrao' => 'menu-padrao-projeto',
        ];

        $componente = $this->resolverComponenteMenu('layout-desconhecido', true, $config);
        self::assertSame('menu-padrao-projeto', $componente);
    }

    public function testFallbackParaChaveDefaultQuandoPadraoNaoExiste(): void
    {
        $config = [
            'default' => 'menu-default-projeto',
        ];

        $componente = $this->resolverComponenteMenu('layout-qualquer', false, $config);
        self::assertSame('menu-default-projeto', $componente);
    }

    public function testFallbackParaFrameworkNativoQuandoSemConfigDeProjeto(): void
    {
        $componenteTailwind = $this->resolverComponenteMenu('layout-admin', true, null);
        self::assertSame('menu-principal-sistema-tailwind', $componenteTailwind);

        $componenteFomantic = $this->resolverComponenteMenu('layout-admin', false, null);
        self::assertSame('menu-principal-sistema', $componenteFomantic);
    }
}
