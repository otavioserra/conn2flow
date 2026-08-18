<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * req-120 (BATCH-121) — migração das telas públicas de identidade para Tailwind.
 *
 * A migração é de MARCAÇÃO: o backend não mudou. O risco real, portanto, não é lógico — é uma
 * classe do Fomantic sobrevivendo num arquivo, ou um `name` de campo POST/bloco de template que se
 * perdeu na reescrita. Nenhum dos dois quebra teste de comportamento: o primeiro só aparece como
 * tela sem estilo em produção, e o segundo como formulário que envia e não grava nada.
 *
 * Estes testes leem os arquivos REAIS de recursos, nos dois idiomas.
 */
final class PerfilUsuarioTelasPublicasTest extends TestCase
{
    private const PAGINAS = [
        'acessar-sistema', 'cadastrar-no-sistema', 'esqueceu-a-senha',
        'esqueceu-a-senha-email-enviado', 'redefinir-senha', 'redefinir-senha-confirmacao',
        'signin-2fa', 'confirmacao-de-email', 'social-login', 'oauth-callback',
        'oauth-authenticate', 'oauth-authenticate-2fa', 'validar-usuario', 'sair-sistema',
        'Area-restrita',
    ];

    private const IDIOMAS = ['pt-br', 'en'];

    private static function caminho(string $lang, string $id): string
    {
        return CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
            . 'perfil-usuario' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $lang
            . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR
            . $id . '.html';
    }

    private static function html(string $lang, string $id): string
    {
        $caminho = self::caminho($lang, $id);
        self::assertFileExists($caminho, "Página ausente: {$lang}/{$id}");

        return (string)file_get_contents($caminho);
    }

    /** @return list<array{0:string,1:string}> */
    public static function paginasProvider(): array
    {
        $casos = [];

        foreach (self::IDIOMAS as $lang) {
            foreach (self::PAGINAS as $id) {
                $casos["{$id} ({$lang})"] = [$lang, $id];
            }
        }

        return $casos;
    }

    // ===== Zero Fomantic

    #[DataProvider('paginasProvider')]
    public function testNaoRestaClasseDoFomantic(string $lang, string $id): void
    {
        $html = self::html($lang, $id);

        // `ui` do Fomantic é sempre a primeira classe do átomo (`ui form`, `ui segment`, `fluid ui
        // button`); procurar a palavra solta daria falso positivo em qualquer texto.
        self::assertSame(0, preg_match('/class="[^"]*\bui\s+[a-z]/', $html),
            "Classe do Fomantic encontrada em {$lang}/{$id}");
    }

    #[DataProvider('paginasProvider')]
    public function testNaoRestaIconeDeWebfontDoFomantic(string $lang, string $id): void
    {
        // As telas públicas não carregam a folha de ícones: um `<i class="… icon">` sobrevivente
        // seria um quadrado vazio na tela.
        self::assertSame(0, preg_match('/<i\s+class="[^"]*\bicon\b/', self::html($lang, $id)),
            "Ícone Fomantic encontrado em {$lang}/{$id}");
    }

    #[DataProvider('paginasProvider')]
    public function testUsaVocabularioTailwind(string $lang, string $id): void
    {
        $html = self::html($lang, $id);

        // Toda tela migrada tem ao menos uma utility com valor — o que distingue "migrada" de
        // "esvaziada".
        self::assertSame(1, preg_match('/class="[^"]*\b(rounded-|bg-|text-|px-|py-|flex|grid|w-full|hidden)/', $html),
            "Nenhuma utility Tailwind em {$lang}/{$id}");
    }

    // ===== Contratos do backend preservados

    /**
     * Campos POST e ids de formulário que o PHP lê. Perder um deles produz um formulário que envia
     * e não grava nada — sem erro em lugar nenhum.
     *
     */
    #[DataProvider('contratosProvider')]
    public function testContratoDoFormularioFoiPreservado(string $id, array $obrigatorios): void
    {
        foreach (self::IDIOMAS as $lang) {
            $html = self::html($lang, $id);

            foreach ($obrigatorios as $trecho) {
                self::assertStringContainsString($trecho, $html,
                    "Contrato perdido em {$lang}/{$id}: {$trecho}");
            }
        }
    }

    /** @return array<string,array{0:string,1:list<string>}> */
    public static function contratosProvider(): array
    {
        return [
            'login' => ['acessar-sistema', [
                'id="_gestor-form-logar"', 'name="usuario"', 'name="senha"',
                'name="permanecer-logado"', 'name="_gestor-logar"', 'name="login_method"',
                '#login-method-switch#', '#login-method-default#', '#login-social#',
                '<!-- bloqueado-mensagem < -->', '<!-- bloqueado-mensagem > -->',
                '<!-- formulario < -->', '<!-- formulario > -->',
                '<!-- login-local < -->', '<!-- login-local > -->',
                '<!-- login-senha < -->', '<!-- login-senha > -->',
                'class="password-login-field', 'login-submit-button',
            ]],
            'cadastro' => ['cadastrar-no-sistema', [
                'id="_gestor-form-signup"', 'name="nome"', 'name="email"', 'name="email-2"',
                'name="senha"', 'name="senha-2"', 'name="plano"', 'name="_gestor-signup"',
                '<!-- plano-cont < -->', '<!-- plano-cont > -->',
                '<!-- plano-cel < -->', '<!-- plano-cel > -->',
                '#val#', '#checked#', '#nome#',
            ]],
            'esqueci-senha' => ['esqueceu-a-senha', [
                'id="_gestor-form-forgot-password"', 'name="email"', 'name="email-2"',
                '<!-- bloqueado-mensagem < -->', '<!-- formulario < -->',
            ]],
            'redefinir-senha' => ['redefinir-senha', [
                'id="_gestor-form-redefine-password"', 'name="senha"', 'name="senha-2"',
                'name="_gestor-redefine-password-token"', '#token#',
            ]],
            'signin-2fa' => ['signin-2fa', [
                'id="_gestor-form-2fa"', 'name="_gestor-2fa"', '#conteudo-2fa#',
            ]],
            'oauth-2fa' => ['oauth-authenticate-2fa', [
                'id="_gestor-form-oauth-2fa"', 'name="_gestor-oauth-2fa"', '#conteudo-2fa#',
            ]],
            'oauth-authenticate' => ['oauth-authenticate', [
                'id="_gestor-form-autenticar"', 'name="usuario"', 'name="senha"',
                'name="scope"', 'name="url_redirect"', 'name="grant_type"',
                'name="_gestor-autenticate"', '#form-action#', '#titulo#',
                '<!-- login-senha < -->', '<!-- login-senha > -->',
            ]],
            'validar-usuario' => ['validar-usuario', [
                'id="_gestor-form-validar-usuario"', 'name="_gestor-validar-usuario"',
                'name="_gestor-validar-usuario-querystring"', '#form-querystring#', '#form-action#',
            ]],
            'area-restrita' => ['Area-restrita', [
                'id="_gestor-restrict-area"', 'name="senha"',
                'name="_gestor-restrict-area-atualizar"', 'name="_gestor-restrict-area-querystring"',
                'id="_gestor-restrict-area-button"', '#form-action#', '#form-querystring#',
                '#form-button-value#',
            ]],
            'email-enviado' => ['esqueceu-a-senha-email-enviado', ['#message#']],
            'senha-redefinida' => ['redefinir-senha-confirmacao', ['#message#']],
        ];
    }

    // ===== Contrato com o runtime Tailwind

    public function testFormulariosDeclaramOMarcadorDoRuntimeTailwind(): void
    {
        // `data-c2f-form="tailwind"` é o seletor que `interface-tailwind.js` usa para instalar a
        // validação: sem ele, o formulário envia sem validar nada no cliente.
        $comFormulario = [
            'acessar-sistema', 'cadastrar-no-sistema', 'esqueceu-a-senha', 'redefinir-senha',
            'signin-2fa', 'oauth-authenticate', 'oauth-authenticate-2fa', 'Area-restrita',
        ];

        foreach (self::IDIOMAS as $lang) {
            foreach ($comFormulario as $id) {
                self::assertStringContainsString('data-c2f-form="tailwind"', self::html($lang, $id),
                    "Marcador do runtime ausente em {$lang}/{$id}");
            }
        }
    }

    public function testTelasComSenhaTrazemOMedidorDeForca(): void
    {
        foreach (self::IDIOMAS as $lang) {
            foreach (['cadastrar-no-sistema', 'redefinir-senha'] as $id) {
                $html = self::html($lang, $id);

                self::assertStringContainsString('data-perfil-senha', $html, "{$lang}/{$id}");
                self::assertStringContainsString('data-perfil-senha-barra', $html, "{$lang}/{$id}");
                self::assertStringContainsString('data-perfil-senha-rotulo', $html, "{$lang}/{$id}");
            }
        }
    }

    public function testCaixaDeErroExisteEmTodoFormularioValidado(): void
    {
        foreach (self::IDIOMAS as $lang) {
            foreach (['acessar-sistema', 'cadastrar-no-sistema', 'esqueceu-a-senha', 'redefinir-senha'] as $id) {
                self::assertStringContainsString('data-c2f-form-erros', self::html($lang, $id),
                    "Caixa de erros ausente em {$lang}/{$id}");
            }
        }
    }

    // ===== Contrato de recursos

    public function testTodasAsTelasApontamParaUmLayoutTailwind(): void
    {
        $json = json_decode((string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR
            . 'perfil-usuario' . DIRECTORY_SEPARATOR . 'perfil-usuario.json'
        ), true);

        self::assertIsArray($json);

        foreach (self::IDIOMAS as $lang) {
            $paginas = [];
            foreach ($json['resources'][$lang]['pages'] as $pagina) {
                $paginas[$pagina['id']] = $pagina;
            }

            foreach (self::PAGINAS as $id) {
                self::assertArrayHasKey($id, $paginas, "Página fora do manifesto: {$lang}/{$id}");
                self::assertSame('tailwindcss', $paginas[$id]['framework_css'] ?? null,
                    "framework_css não declarado em {$lang}/{$id}");
                self::assertStringContainsString('tailwind', (string)($paginas[$id]['layout'] ?? ''),
                    "Layout legado ainda referenciado em {$lang}/{$id}");
            }
        }
    }

    public function testLayoutLegadoContinuaExistindoIntacto(): void
    {
        // Critério de aceite 1 do req-120: telas ainda não migradas continuam dependendo dele.
        foreach (self::IDIOMAS as $lang) {
            self::assertFileExists(
                CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR
                . $lang . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR
                . 'layout-pagina-sem-permissao' . DIRECTORY_SEPARATOR
                . 'layout-pagina-sem-permissao.html'
            );
        }
    }

    public function testLayoutPublicoTailwindTemOsMarcadoresDoPipeline(): void
    {
        foreach (self::IDIOMAS as $lang) {
            $html = (string)file_get_contents(
                CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR
                . $lang . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR
                . 'layout-pagina-sem-permissao-tailwind' . DIRECTORY_SEPARATOR
                . 'layout-pagina-sem-permissao-tailwind.html'
            );

            // Sem qualquer um destes, a página renderiza sem CSS, sem JS ou sem conteúdo.
            foreach (['<!-- pagina#css -->', '<!-- pagina#js -->', '@[[pagina#corpo]]@',
                      'id="gestor-listener"'] as $marcador) {
                self::assertStringContainsString($marcador, $html, "{$lang}: {$marcador}");
            }

            self::assertSame(0, preg_match('/class="[^"]*\bui\s+[a-z]/', $html),
                "Fomantic no layout público ({$lang})");
        }
    }
}
