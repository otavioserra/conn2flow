<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-118/119/120 (BATCH-123) — governança do Sistema de Recursos no módulo `perfil-usuario`.
 *
 * Correção de rumo apontada pelo Chefe: eu havia escrito markup direto no PHP e fixado as classes
 * utilitárias em constantes do arquivo. As duas coisas violam a arquitetura do Conn2Flow — HTML
 * pertence a `resources/` e valor de apresentação pertence às VARIÁVEIS do sistema, que são dados de
 * banco e podem ser editados por instalação sem tocar em código.
 *
 * O defeito é do tipo que volta sozinho na próxima pressa: escrever `$html .= '<div…'` é sempre mais
 * rápido do que criar o bloco no componente. Por isso a regra virou teste.
 */
final class PerfilUsuarioRecursosTest extends TestCase
{
    private const COMPONENTES = [
        'perfil-usuario-seguranca',
        'perfil-usuario-sessoes',
        'perfil-usuario-api-tokens',
        'perfil-usuario-2fa-campos',
        'perfil-usuario-login-metodos',
    ];

    private const IDIOMAS = ['pt-br', 'en'];

    private static function modulo(): string
    {
        return CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
            . 'perfil-usuario' . DIRECTORY_SEPARATOR;
    }

    private static function php(): string
    {
        return (string)file_get_contents(self::modulo() . 'perfil-usuario.php');
    }

    private static function componente(string $lang, string $id): string
    {
        $caminho = self::modulo() . 'resources' . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR
            . 'components' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . $id . '.html';

        self::assertFileExists($caminho, "Componente ausente: {$lang}/{$id}");

        return (string)file_get_contents($caminho);
    }

    private static function json(): array
    {
        $dados = json_decode((string)file_get_contents(self::modulo() . 'perfil-usuario.json'), true);

        self::assertIsArray($dados);

        return $dados;
    }

    // ===== O PHP não escreve markup

    public function testOPhpNaoConstroiTagsHtml(): void
    {
        // `$html .= '<div…'` é a assinatura exata do que foi removido deste módulo.
        $php = self::php();

        self::assertSame(0, preg_match('/\$\w+\s*\.?=\s*[\'"]\s*<[a-z]/i', $php),
            'HTML sendo montado em PHP — o markup pertence a resources/components.');
    }

    public function testOPhpNaoDeclaraClasseCssEmAtributo(): void
    {
        self::assertSame(0, preg_match('/class="/', self::php()),
            'Atributo class no PHP — as classes pertencem às variáveis do sistema.');
    }

    public function testOPhpNaoFixaClassesUtilitariasEmConstante(): void
    {
        // As constantes `PERFIL_*_CARTAO`, `PERFIL_PUBLICO_CAMPO` etc. foram substituídas por
        // variáveis do sistema (`classe-…`), editáveis por instalação.
        self::assertSame(0, preg_match('/^\s*const\s+PERFIL_/mi', self::php()),
            'Constante de classe no PHP — use as variáveis do sistema.');
    }

    public function testOPhpMontaAsAbasAPartirDeComponentes(): void
    {
        $php = self::php();

        foreach (['perfil-usuario-seguranca', 'perfil-usuario-sessoes', 'perfil-usuario-api-tokens'] as $id) {
            self::assertStringContainsString("perfil_usuario_componente('{$id}')", $php,
                "A aba deixou de consumir o componente {$id}.");
        }
    }

    // ===== Os componentes existem e usam as variáveis

    public function testTodosOsComponentesExistemNosDoisIdiomas(): void
    {
        foreach (self::IDIOMAS as $lang) {
            foreach (self::COMPONENTES as $id) {
                self::assertNotSame('', trim(self::componente($lang, $id)), "{$lang}/{$id} está vazio");
            }
        }
    }

    public function testComponentesConsomemAsVariaveisDeClasse(): void
    {
        // Se o componente voltasse a ter a classe literal, a customização por instalação se perderia.
        foreach (self::IDIOMAS as $lang) {
            foreach (['perfil-usuario-seguranca', 'perfil-usuario-sessoes', 'perfil-usuario-api-tokens'] as $id) {
                self::assertMatchesRegularExpression('/@\[\[classe-[a-z-]+\]\]@/', self::componente($lang, $id),
                    "{$lang}/{$id} não usa variável de classe.");
            }
        }
    }

    public function testBlocosUsadosPeloPhpExistemNosComponentes(): void
    {
        $obrigatorios = [
            'perfil-usuario-seguranca' => ['conteiner', '2fa-ativo', '2fa-sem-metodo', '2fa-inativo',
                                           '2fa-configurar', '2fa-metodo-opcao', 'social-vazio',
                                           'social-lista', 'social-item', 'social-vinculado',
                                           'social-desvinculado', 'app', 'email'],
            'perfil-usuario-sessoes' => ['conteiner', 'revogar-outras', 'vazio', 'lista', 'cartao',
                                         'atual', 'origem', 'revogar', 'etiqueta'],
            'perfil-usuario-api-tokens' => ['conteiner', 'escopo', 'vazio', 'tabela', 'linha', 'revogar'],
            'perfil-usuario-2fa-campos' => ['email-enviado', 'ajuda', 'reenviar-email', 'metodo',
                                            'metodo-opcao', 'qr', 'email-bloco', 'campo-codigo',
                                            'recovery-aviso'],
            'perfil-usuario-login-metodos' => ['switch', 'social-conteiner', 'social-botao'],
        ];

        foreach (self::IDIOMAS as $lang) {
            foreach ($obrigatorios as $id => $blocos) {
                $html = self::componente($lang, $id);

                foreach ($blocos as $bloco) {
                    // Bloco ausente faz `modelo_tag_val` devolver string vazia — a seção some da
                    // tela sem erro nenhum.
                    self::assertStringContainsString("<!-- {$bloco} < -->", $html,
                        "Bloco '{$bloco}' ausente em {$lang}/{$id}");
                    self::assertStringContainsString("<!-- {$bloco} > -->", $html,
                        "Fechamento de '{$bloco}' ausente em {$lang}/{$id}");
                }
            }
        }
    }

    // ===== Contrato com o JS preservado

    public function testGanchosDoJavascriptContinuamNosComponentes(): void
    {
        $ganchos = [
            'perfil-usuario-seguranca' => ['id="seg-seguranca"', 'id="seg-msg"', 'id="seg-2fa-codigo"',
                                           'id="btn-2fa-ativar"', 'btn-social-desvincular', 'data-provider='],
            'perfil-usuario-sessoes' => ['id="seg-sessoes"', 'id="sessoes-msg"',
                                         'id="btn-sessoes-revogar-outras"', 'btn-sessao-revogar',
                                         'data-sessao-pubid='],
            'perfil-usuario-api-tokens' => ['id="seg-api-tokens"', 'id="api-tokens-msg"',
                                            'id="btn-api-token-criar"', 'id="api-token-valor"',
                                            'btn-api-token-revogar', 'api-token-escopo'],
            'perfil-usuario-login-metodos' => ['login-method-toggle', 'data-method='],
            'perfil-usuario-2fa-campos' => ['id="seg-2fa-codigo"', 'name="codigo"'],
        ];

        foreach (self::IDIOMAS as $lang) {
            foreach ($ganchos as $id => $itens) {
                $html = self::componente($lang, $id);

                foreach ($itens as $gancho) {
                    self::assertStringContainsString($gancho, $html,
                        "Gancho de JS '{$gancho}' perdido em {$lang}/{$id}");
                }
            }
        }
    }

    // ===== Contrato de recursos

    public function testAsClassesEstaoRegistradasComoVariaveisDoSistema(): void
    {
        $json = self::json();

        $esperadas = ['classe-cartao', 'classe-titulo', 'classe-rotulo', 'classe-campo',
                      'classe-botao', 'classe-botao-ok', 'classe-botao-alerta', 'classe-botao-mini',
                      'classe-etiqueta', 'classe-ajuda', 'classe-publico-campo',
                      'classe-publico-rotulo', 'classe-publico-ok', 'classe-publico-secundario',
                      'classe-publico-aba'];

        foreach (self::IDIOMAS as $lang) {
            $ids = [];
            foreach ($json['resources'][$lang]['variables'] as $variavel) {
                $ids[$variavel['id']] = $variavel['value'] ?? '';
            }

            foreach ($esperadas as $id) {
                self::assertArrayHasKey($id, $ids, "Variável de classe ausente em {$lang}: {$id}");
                self::assertNotSame('', trim((string)$ids[$id]), "Variável vazia em {$lang}: {$id}");
            }
        }
    }

    public function testComponentesEstaoRegistradosNoManifesto(): void
    {
        $json = self::json();

        foreach (self::IDIOMAS as $lang) {
            $ids = array_column($json['resources'][$lang]['components'] ?? [], 'framework_css', 'id');

            foreach (self::COMPONENTES as $id) {
                self::assertArrayHasKey($id, $ids, "Componente fora do manifesto em {$lang}: {$id}");
                self::assertSame('tailwindcss', $ids[$id], "framework_css ausente em {$lang}: {$id}");
            }
        }
    }

    public function testOJsonDoModuloEhFonteTailwindDasPaginas(): void
    {
        // É o que faz o compilador enxergar as classes que vivem nas variáveis — verificado
        // compilando um probe real antes de adotar o desenho.
        $json = self::json();

        foreach (self::IDIOMAS as $lang) {
            foreach ($json['resources'][$lang]['pages'] as $pagina) {
                $fontes = $pagina['tailwind_sources'] ?? null;

                if ($fontes === null) {
                    continue;
                }

                self::assertContains('../../../../perfil-usuario.json', $fontes,
                    "Página {$pagina['id']} ({$lang}) não escaneia as variáveis do módulo.");
            }
        }
    }

    public function testOBundleDoPerfilDeclaraOsComponentesDoModulo(): void
    {
        // Sem esta declaração o bundle sai sem as utilities dos componentes: medido — a classe
        // `divide-slate-100` ficou de fora até a dependência ser adicionada.
        $json = self::json();

        foreach (self::IDIOMAS as $lang) {
            foreach ($json['resources'][$lang]['pages'] as $pagina) {
                if ($pagina['id'] !== 'perfil-usuario') {
                    continue;
                }

                $deps = array_column($pagina['tailwind_dependencies'] ?? [], 'id');

                foreach (['perfil-usuario-seguranca', 'perfil-usuario-sessoes', 'perfil-usuario-api-tokens'] as $id) {
                    self::assertContains($id, $deps, "Dependência ausente no bundle ({$lang}): {$id}");
                }
            }
        }
    }
}
