<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * req-125 (BATCH-127) — laço de sessão expirada no login e warnings do Lucide no console.
 *
 * Os dois defeitos são invisíveis para um teste de fluxo: o primeiro só se manifesta no HISTÓRICO do
 * navegador (o formulário volta do bfcache com o token já queimado), o segundo só existe no console.
 * O que os falsifica é o contrato das funções puras que decidem, respectivamente, PARA ONDE o botão
 * de voltar navega e SE o atributo `data-lucide` chega a ser escrito.
 */
final class CsrfReloadIconesMenuTest extends TestCase
{
    // ===== F1: destino de recarregamento limpo depois de um CSRF inválido

    /**
     * O caminho da REQUISIÇÃO é a fonte primária. O POST de login vai para `/signin/`, então quando
     * ele falha a validação é a URL corrente que nomeia a tela de origem — e ela está disponível
     * mesmo quando o navegador não manda referer nenhum (`Referrer-Policy: no-referrer`, navegação
     * de HTTPS para HTTP, extensão de privacidade).
     */
    #[DataProvider('rotasDeIdentidadeProvider')]
    public function testCaminhoDeIdentidadeDefineODestinoMesmoSemReferer(string $caminho, string $esperado): void
    {
        self::assertSame($esperado, gestor_csrf_destino_recarregamento($caminho, '', '/'));
    }

    /** @return array<string,array{0:string,1:string}> */
    public static function rotasDeIdentidadeProvider(): array
    {
        return [
            'signin'          => ['signin/', '/signin/'],
            'sem barra'       => ['signin', '/signin/'],
            'caixa alta'      => ['SignIn/', '/signin/'],
            'signin-2fa'      => ['signin-2fa/', '/signin-2fa/'],
            'signup'          => ['signup/', '/signup/'],
            'forgot-password' => ['forgot-password/', '/forgot-password/'],
            'com subrota'     => ['signin/erro/', '/signin/'],
        ];
    }

    public function testDestinoRespeitaARaizComPrefixoDeIdioma(): void
    {
        self::assertSame('/en/signin/', gestor_csrf_destino_recarregamento('signin/', '', '/en/'));
        self::assertSame(
            'https://app.exemplo.com/pt-br/signin/',
            gestor_csrf_destino_recarregamento('signin/', '', 'https://app.exemplo.com/pt-br/')
        );
    }

    /**
     * Fonte secundária: um POST que SAIU do login para outra rota. Aqui o caminho corrente não diz
     * nada, e o referer é a única pista de onde o formulário com o token vencido está.
     */
    public function testRefererDeLoginResolveODestinoQuandoOCaminhoNaoEhDeIdentidade(): void
    {
        self::assertSame(
            '/signin/',
            gestor_csrf_destino_recarregamento('dashboard/', 'https://app.exemplo.com/signin/', '/')
        );
        self::assertSame(
            '/en/signup/',
            gestor_csrf_destino_recarregamento('dashboard/', 'https://app.exemplo.com/en/signup/?next=x', '/en/')
        );
    }

    /**
     * Fora das telas de identidade o destino tem que ser VAZIO — é o que devolve o botão ao
     * `history.back()`. No resto do gestor voltar pelo histórico é o comportamento desejável: quem
     * estava editando uma página quer o que digitou de volta, não uma tela em branco.
     */
    #[DataProvider('semDestinoProvider')]
    public function testSemRotaDeIdentidadeNaoHaDestinoEOBotaoVoltaPeloHistorico(string $caminho, string $referer): void
    {
        self::assertSame('', gestor_csrf_destino_recarregamento($caminho, $referer, '/'));
    }

    /** @return array<string,array{0:string,1:string}> */
    public static function semDestinoProvider(): array
    {
        return [
            'dashboard sem referer'   => ['dashboard/', ''],
            'referer de outra tela'   => ['admin-paginas/editar/', 'https://app.exemplo.com/admin-paginas/'],
            'tudo vazio'              => ['', ''],
            'referer sem caminho'     => ['dashboard/', 'https://app.exemplo.com'],
            'referer lixo'            => ['dashboard/', 'nao-e-uma-url'],
            'nome parecido nao conta' => ['signin-personalizado/', ''],
        ];
    }

    /**
     * Regressão do laço: o valor devolvido precisa ser uma URL de NAVEGAÇÃO. Se um dia esta função
     * voltar a entregar algo que o botão use com `history.back()`, o login volta a reciclar o token
     * expirado do cache do formulário — que é exatamente o defeito do intake.
     */
    public function testDestinoDeLoginNuncaEhOCaminhoRelativoCru(): void
    {
        $destino = gestor_csrf_destino_recarregamento('signin/', '', '/');

        self::assertStringStartsWith('/', $destino);
        self::assertStringEndsWith('/', $destino);
        self::assertStringNotContainsString('..', $destino);
    }

    // ===== F4: `data-lucide` só para nome que o Lucide consegue endereçar

    /**
     * O Lucide resolve `data-lucide` por `toPascalCase()`: só kebab-case alfanumérico chega a algum
     * ícone. Estes são os nomes que o catálogo aceita — todos conferidos contra o bundle real.
     */
    #[DataProvider('nomesLucideValidosProvider')]
    public function testNomeKebabCaseEhEnderecavelNoLucide(string $nome): void
    {
        self::assertTrue(gestor_pagina_menu_icone_lucide_valido($nome), "Deveria aceitar: {$nome}");
        self::assertSame('data-lucide="' . $nome . '"', gestor_pagina_menu_icone_lucide_atributo($nome));
    }

    /** @return array<string,array{0:string}> */
    public static function nomesLucideValidosProvider(): array
    {
        $nomes = ['box', 'boxes', 'share-2', 'credit-card', 'megaphone', 'smartphone', 'folder-open',
                  'network', 'settings-2', 'circle-help', 'arrow-left-right'];

        $casos = [];
        foreach ($nomes as $nome) {
            $casos[$nome] = [$nome];
        }

        return $casos;
    }

    /**
     * E estes são os que geravam um `icon name was not found` por ITEM do menu: os nomes COMPOSTOS
     * do Fomantic, que são várias classes separadas por espaço. Todos vêm de módulos reais — os do
     * `conn2flow-site`, que até este lote só declaravam o vocabulário legado.
     */
    #[DataProvider('nomesNaoEnderecaveisProvider')]
    public function testNomeCompostoDoFomanticNaoViraAtributo(string $nome): void
    {
        self::assertFalse(gestor_pagina_menu_icone_lucide_valido($nome), "Não deveria aceitar: {$nome}");
        self::assertSame('', gestor_pagina_menu_icone_lucide_atributo($nome));
    }

    /** @return array<string,array{0:string}> */
    public static function nomesNaoEnderecaveisProvider(): array
    {
        return [
            'comments outline'        => ['comments outline'],
            'credit card outline'     => ['credit card outline'],
            'folder open outline'     => ['folder open outline'],
            'mobile alternate'        => ['mobile alternate'],
            'share alternate'         => ['share alternate'],
            'bottom right corner'     => ['bottom right corner share'],
            'question circle outline' => ['question circle outline'],
            'vazio'                   => [''],
            'so espaco'               => ['   '],
            'maiuscula'               => ['CreditCard'],
            'underline'               => ['credit_card'],
            'hifen solto'             => ['-box'],
            'hifen duplo'             => ['box--grande'],
        ];
    }

    /**
     * Atributo VAZIO não seria saneamento: `createIcons()` seleciona `[data-lucide]` pela PRESENÇA
     * do atributo, e `data-lucide=""` produz exatamente o mesmo warning que o nome errado. A função
     * tem que devolver a string vazia inteira, para o atributo não existir na marcação.
     */
    public function testSaneamentoOmiteOAtributoEmVezDeEsvaziarOValor(): void
    {
        self::assertSame('', gestor_pagina_menu_icone_lucide_atributo('comments outline'));
        self::assertStringNotContainsString('data-lucide', gestor_pagina_menu_icone_lucide_atributo('comments outline'));
    }

    /** Entrada não-textual não pode estourar: `modulos.icone` é nulo em módulo recém-cadastrado. */
    public function testEntradaNaoTextualNaoQuebra(): void
    {
        self::assertFalse(gestor_pagina_menu_icone_lucide_valido(null));
        self::assertSame('', gestor_pagina_menu_icone_lucide_atributo(null));
    }

    // ===== Contrato de marcação do componente de menu

    /**
     * O template não pode voltar a escrever `data-lucide="#icon#"`: com um marcador só, o mesmo
     * valor ia para o atributo e para a classe, e não havia como omitir um sem perder o outro. O
     * `#icon-lucide#` recebe o ATRIBUTO inteiro, montado pelo backend.
     */
    #[DataProvider('idiomasProvider')]
    public function testComponenteDeMenuUsaMarcadorDeAtributoESeparaOsVocabularios(string $lang): void
    {
        $caminho = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR
            . $lang . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR
            . 'menu-principal-sistema-tailwind' . DIRECTORY_SEPARATOR
            . 'menu-principal-sistema-tailwind.html';

        self::assertFileExists($caminho);
        $html = (string)file_get_contents($caminho);

        self::assertStringNotContainsString('data-lucide="#icon', $html,
            "{$lang}: o atributo voltou a ser escrito no template com o marcador de valor");
        self::assertStringContainsString('<i #icon-lucide# class="#icon# icon"></i>', $html,
            "{$lang}: célula de ícone simples fora do contrato");
        self::assertStringContainsString('<i #icon-2-lucide# class="#icon-2# icon"></i>', $html,
            "{$lang}: célula de ícone ancorado fora do contrato");
    }

    /** @return array<string,array{0:string}> */
    public static function idiomasProvider(): array
    {
        return ['pt-br' => ['pt-br'], 'en' => ['en']];
    }

    /**
     * E o bootstrap tem que SUBSTITUIR os dois marcadores. Um `#icon-lucide#` que não é trocado vai
     * cru para a tela — o mesmo defeito que o `#historico#` teve no BATCH-126.
     */
    public function testBootstrapSubstituiOsDoisMarcadoresDeAtributo(): void
    {
        $php = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'gestor.php');

        self::assertStringContainsString('"#icon-lucide#"', $php,
            'gestor_pagina_menu() não troca #icon-lucide#.');
        self::assertStringContainsString('"#icon-2-lucide#"', $php,
            'gestor_pagina_menu() não troca #icon-2-lucide#.');
    }

    /**
     * Guarda de dados: os módulos que o intake nomeia precisam ter o par declarado no
     * `ModulosData.json` do PROJETO que os hospeda — é o `conn2flow-site`, não o core. Um id em
     * português no arquivo do core não cadastra nada: cria linha órfã em `modulos`, sem página
     * associada, que o menu descarta em silêncio.
     */
    public function testCoreNaoRegistraModulosQueSaoDeProjeto(): void
    {
        $caminho = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR
            . 'data' . DIRECTORY_SEPARATOR . 'ModulosData.json';

        $registros = json_decode((string)file_get_contents($caminho), true);
        self::assertIsArray($registros);

        $ids = array_column($registros, 'id');

        foreach (['catalogo-3d', 'catalogo-3d-grupos', 'catalogo-3d-itens', 'conexoes-sociais',
                  'gateways-pagamentos', 'publicador-midias-sociais', 'social-apps', 'arquivos',
                  'modulos-grupos-distribuidos', '3d-catalog', 'social-connections'] as $id) {
            self::assertNotContains($id, $ids, "O core não hospeda o módulo `{$id}`.");
        }
    }

    /**
     * Todo módulo do core que aparece no menu precisa do par Lucide, e o par precisa ser
     * endereçável. Foi um `settings2` (sem hífen) que o BATCH-126 encontrou desenhando o ícone
     * errado sem erro nenhum.
     */
    public function testTodoIconeTailwindDoCoreEhEnderecavelNoLucide(): void
    {
        $caminho = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR
            . 'data' . DIRECTORY_SEPARATOR . 'ModulosData.json';

        $registros = json_decode((string)file_get_contents($caminho), true);
        self::assertIsArray($registros);

        foreach ($registros as $modulo) {
            foreach (['icone_tailwind', 'icone2_tailwind'] as $campo) {
                $valor = $modulo[$campo] ?? null;
                if (!is_string($valor) || trim($valor) === '') {
                    continue;
                }

                self::assertTrue(
                    gestor_pagina_menu_icone_lucide_valido($valor),
                    "Módulo `{$modulo['id']}` tem {$campo}=`{$valor}`, que o Lucide não endereça."
                );
            }
        }
    }
}
