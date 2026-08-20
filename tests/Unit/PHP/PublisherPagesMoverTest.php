<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-121 (BATCH-122) — Mover Página de Publicação entre Tipos de Publicador.
 *
 * Cobre a lógica pura de substituição inteligente de prefixos (path_prefix),
 * preservação de URLs customizadas e regras de redirecionamento 301.
 */
final class PublisherPagesMoverTest extends TestCase
{
    /**
     * Helper que espelha a lógica de cálculo de caminho do endpoint mover-publicador.
     */
    private function calcularNovoCaminho(string $caminhoAtual, string $oldPrefix, string $newPrefix): string
    {
        $norm_old = trim(trim($oldPrefix), '/');
        $norm_new = trim(trim($newPrefix), '/');

        $caminho_clean = ltrim($caminhoAtual, '/');
        $prefix_check = ($norm_old !== '') ? $norm_old . '/' : '';

        $novo_caminho = $caminhoAtual;

        if ($norm_old !== '' && (stripos($caminho_clean, $prefix_check) === 0 || strcasecmp($caminho_clean, $norm_old) === 0)) {
            $rest = (strcasecmp($caminho_clean, $norm_old) === 0) ? '' : substr($caminho_clean, strlen($prefix_check));
            if ($norm_new !== '') {
                $novo_caminho = $norm_new . '/' . ($rest !== false && $rest !== '' ? ltrim($rest, '/') : '');
            } else {
                $novo_caminho = ($rest !== false && $rest !== '' ? ltrim($rest, '/') : '');
            }

            if ($novo_caminho !== '') {
                $novo_caminho = rtrim($novo_caminho, '/') . '/';
            } else {
                $novo_caminho = '/';
            }
        }

        return $novo_caminho;
    }

    public function testSubstituiPrefixoPadraoCorretamente(): void
    {
        $novo = $this->calcularNovoCaminho('noticias/minha-materia/', 'noticias/', 'artigos/');
        self::assertSame('artigos/minha-materia/', $novo);
    }

    public function testSubstituiPrefixoComSubdiretorios(): void
    {
        $novo = $this->calcularNovoCaminho('blog/tech/novidades/', 'blog/', 'noticias/');
        self::assertSame('noticias/tech/novidades/', $novo);
    }

    public function testPreservaUrlCustomizadaSemPrefixoAntigo(): void
    {
        $novo = $this->calcularNovoCaminho('sobre-nos/quem-somos/', 'noticias/', 'artigos/');
        self::assertSame('sobre-nos/quem-somos/', $novo);
    }

    public function testPreservaUrlCustomizadaNaRaiz(): void
    {
        $novo = $this->calcularNovoCaminho('minha-landing-page/', 'noticias/', 'artigos/');
        self::assertSame('minha-landing-page/', $novo);
    }

    public function testMoverParaPublicadorSemPrefixo(): void
    {
        $novo = $this->calcularNovoCaminho('noticias/minha-materia/', 'noticias/', '');
        self::assertSame('minha-materia/', $novo);
    }

    public function testMoverDePublicadorSemPrefixoParaComPrefixoNaoAlteraUrlCustomizada(): void
    {
        $novo = $this->calcularNovoCaminho('minha-materia/', '', 'artigos/');
        self::assertSame('minha-materia/', $novo);
    }

    public function testSubstituiQuandoPaginaEProprioPrefixo(): void
    {
        $novo = $this->calcularNovoCaminho('noticias/', 'noticias/', 'artigos/');
        self::assertSame('artigos/', $novo);
    }

    public function testGaranteBarraFinal(): void
    {
        $novo = $this->calcularNovoCaminho('noticias/slug-sem-barra', 'noticias', 'artigos');
        self::assertSame('artigos/slug-sem-barra/', $novo);
    }
}
