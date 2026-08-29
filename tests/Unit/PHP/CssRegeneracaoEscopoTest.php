<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * BATCH-149 — o escopo do `css:rebuild`.
 *
 * O problema que o req-141 descreve existe SÓ quando o HTML do banco divergiu do HTML do disco — ou
 * seja, quando alguém editou pelo gestor. Regenerar o resto substitui um CSS mais completo (o do
 * build offline, com tema, plugins e `tailwind_sources`) por um mais pobre.
 *
 * Medido no `transformamp`: 17 de 1.446 recursos têm `user_modified = 1`. Regenerar os 1.429
 * restantes derrubou a página `perfil-usuario` de 25.276 para 7.980 bytes de CSS e deixou a tela sem
 * estilo — com o comando reportando sucesso.
 */
final class CssRegeneracaoEscopoTest extends TestCase
{
    private static function regenerador(): string
    {
        return (string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'controladores' . DIRECTORY_SEPARATOR
            . 'agents' . DIRECTORY_SEPARATOR . 'arquitetura' . DIRECTORY_SEPARATOR . 'css-regenerar.php'
        );
    }

    public function testRegeneraApenasORecursoEditadoOnline(): void
    {
        $codigo = self::regenerador();

        self::assertStringContainsString('user_modified=1', $codigo);
        // `--todos` continua alcancando o acervo inteiro, para auditoria e recuperacao.
        self::assertStringContainsString("!\$todos && regenerarTemColuna(\$conexao, \$tabela, 'user_modified')", $codigo);
    }

    public function testAsFontesDeclaradasSaoLidasTambemDoManifestoDoModulo(): void
    {
        // O `perfil-usuario` nao tem `<id>.json`: as 15 paginas dele declaram `tailwind_sources`
        // dentro de `modulos/perfil-usuario/perfil-usuario.json`. Ler so o metadado por recurso
        // devolvia lista VAZIA e a regeneracao descartava as utilities montadas em PHP/JS.
        $codigo = self::regenerador();

        self::assertStringContainsString("\$manifesto = \$base . DIRECTORY_SEPARATOR . \$modulo . '.json'", $codigo);
        self::assertStringContainsString("\$decodificado['resources'][\$lang][\$tipos[\$tabela]]", $codigo);
    }

    public function testAsFontesContinuamPresasARaizDoGestor(): void
    {
        // A leitura do manifesto nao pode afrouxar o containment: `tailwind_sources` e uma string
        // vinda de arquivo, e um `../` a mais faria o compilador varrer fora da arvore.
        $codigo = self::regenerador();

        self::assertStringContainsString('strpos($candidato, $gestorPath) === 0', $codigo);
        self::assertStringContainsString('realpath(', $codigo);
    }

    public function testOManifestoDoPerfilUsuarioDeclaraAsFontes(): void
    {
        // Guarda do caso concreto: se a declaracao sumir do manifesto, a tela perde o estilo de novo.
        $manifesto = json_decode((string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
            . 'perfil-usuario' . DIRECTORY_SEPARATOR . 'perfil-usuario.json'
        ), true);

        $paginaAlvo = null;
        foreach ($manifesto['resources']['pt-br']['pages'] as $pagina) {
            if (($pagina['id'] ?? '') === 'perfil-usuario') {
                $paginaAlvo = $pagina;
            }
        }

        self::assertNotNull($paginaAlvo);
        self::assertNotEmpty($paginaAlvo['tailwind_sources'] ?? []);
        self::assertContains('../../../../perfil-usuario.js', $paginaAlvo['tailwind_sources']);
    }
}
