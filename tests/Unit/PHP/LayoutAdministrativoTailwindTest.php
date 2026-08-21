<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * req-124 (BATCH-126) — layout administrativo Tailwind, menu do gestor e histórico do perfil.
 *
 * Os quatro defeitos cobertos aqui têm a mesma assinatura: nada estoura, nada aparece no log, e o
 * sintoma só existe no navegador — um ícone que some, uma faixa vazia, um botão fora do alcance da
 * rolagem, um marcador cru no meio da tela. Testes de comportamento não os pegam; o que os pega é
 * ler os arquivos REAIS e verificar o contrato de marcação que cada correção estabeleceu.
 */
final class LayoutAdministrativoTailwindTest extends TestCase
{
    private const IDIOMAS = ['pt-br', 'en'];

    private static function recurso(string $lang, string $tipo, string $id, string $ext = 'html'): string
    {
        $caminho = CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR
            . $lang . DIRECTORY_SEPARATOR . $tipo . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR
            . $id . '.' . $ext;

        self::assertFileExists($caminho, "Recurso ausente: {$lang}/{$tipo}/{$id}.{$ext}");

        return (string)file_get_contents($caminho);
    }

    /** @return array<string,array{0:string}> */
    public static function idiomasProvider(): array
    {
        $casos = [];
        foreach (self::IDIOMAS as $lang) {
            $casos[$lang] = [$lang];
        }

        return $casos;
    }

    // ===== F1: compensação de topo da Editbar

    /**
     * A Editbar é um iframe `position:fixed` de 30px. O `dashboard.toolbar.js` compensa o documento
     * com `margin-top` no <html>, mas margem não alcança quem está fora do fluxo: a barra lateral
     * (`fixed`) e o cabeçalho (`sticky top-0`) voltam a ficar por baixo dela. A compensação precisa
     * ser CSS ancorado numa classe que o PHP escreve no <body>.
     */
    #[DataProvider('idiomasProvider')]
    public function testLayoutCompensaAAlturaDaEditbar(string $lang): void
    {
        $css = self::recurso($lang, 'layouts', 'layout-administrativo-tailwind', 'css');

        self::assertMatchesRegularExpression(
            '/body\.c2f-toolbar-ativa[^{]*\[data-admin-sidebar\][^{]*\{[^}]*top:\s*30px/s',
            $css,
            "Barra lateral sem compensação da Editbar ({$lang})"
        );

        self::assertMatchesRegularExpression(
            '/body\.c2f-toolbar-ativa[^{]*header[^{]*\{[^}]*top:\s*30px/s',
            $css,
            "Cabeçalho sem compensação da Editbar ({$lang})"
        );
    }

    /**
     * O gancho só existe se o PHP escrever a classe. `gestor_dashboard_toolbar()` injeta o iframe
     * logo após `<body …>` — é no mesmo passo que a classe entra.
     */
    public function testGestorMarcaOBodyQuandoInjetaAEditbar(): void
    {
        $php = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'gestor.php');

        $inicio = strpos($php, 'function gestor_dashboard_toolbar(');
        self::assertNotFalse($inicio, 'gestor_dashboard_toolbar() não encontrada.');

        $corpo = substr($php, $inicio, 4000);

        self::assertStringContainsString('c2f-toolbar-ativa', $corpo,
            'A Editbar é injetada sem marcar o <body>: o CSS de compensação nunca casa.');
        self::assertStringContainsString('id="c2f-site-toolbar"', $corpo);
    }

    // ===== F2: ícones do menu no vocabulário que a página realmente desenha

    /**
     * `gestor_pagina_menu_icone()` entrega a um menu Tailwind o conteúdo de
     * `modulos.icone_tailwind`, que é vocabulário Lucide. Enquanto o componente escrevia
     * `<i class="#icon# icon">`, esse nome era interpretado contra a folha do Fomantic — e todo
     * nome que não existisse nos dois catálogos virava um ícone invisível.
     */
    #[DataProvider('idiomasProvider')]
    public function testMenuTailwindDesenhaIconesNoVocabularioLucide(string $lang): void
    {
        $html = self::recurso($lang, 'components', 'menu-principal-sistema-tailwind');

        self::assertStringContainsString('#icon-lucide#', $html,
            "Ícone principal do menu fora do vocabulário Lucide ({$lang})");
        self::assertStringContainsString('#icon-2-lucide#', $html,
            "Ícone ancorado do menu fora do vocabulário Lucide ({$lang})");

        // O `class` legado continua no MESMO elemento de propósito: `createIcons()` devolve o <i>
        // intacto quando o nome não existe no Lucide, e aí quem desenha é a folha do Fomantic. É o
        // que mantém o menu com ícones num banco que ainda não rodou a migração do req-086 e cai no
        // vocabulário legado. Sem os dois no mesmo <i>, um dos dois cenários fica sem ícone.
        self::assertMatchesRegularExpression(
            '/<i #icon-lucide# class="#icon# icon"><\/i>/',
            $html,
            "Item do menu não serve aos dois catálogos de ícone ({$lang})"
        );
    }

    #[DataProvider('idiomasProvider')]
    public function testLayoutCarregaOPacoteLucide(string $lang): void
    {
        $html = self::recurso($lang, 'layouts', 'layout-administrativo-tailwind');

        // `defer` importa: é o que garante execução antes do DOMContentLoaded em que o
        // `admin-tailwind.js` chama `lucide.createIcons()`.
        self::assertMatchesRegularExpression(
            '/<script\s+defer\s+src="[^"]*lucide[^"]*"><\/script>/',
            $html,
            "Layout administrativo não carrega o Lucide ({$lang})"
        );

        // A folha do Fomantic continua necessária: os ícones ESTRUTURAIS do layout são dela.
        self::assertStringContainsString('fomantic-ui', $html);
    }

    /**
     * Todo módulo que chega ao menu precisa de um par de ícones, e o nome Lucide fica na grafia
     * canônica do catálogo: kebab-case (`settings-2`).
     *
     * A normalização do Lucide é tolerante — `toPascalCase()` colapsa separadores, então tanto
     * `settings-2` quanto `settings2` chegam a `Settings2` e desenham. Não é por quebrar que a regra
     * existe: é para o cadastro não acumular duas grafias do MESMO ícone, o que faz qualquer
     * conferência contra a lista oficial dar falso negativo. Um nome de fato ausente do catálogo
     * (`createIcons()` avisa no console e devolve o elemento) continua invisível para este teste —
     * quem cobre esse caso é a validação cruzada contra o bundle, feita na execução do lote.
     */
    public function testTodosOsModulosDeclaramOParDeIcones(): void
    {
        $modulos = json_decode(
            (string)file_get_contents(
                CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR
                . 'data' . DIRECTORY_SEPARATOR . 'ModulosData.json'
            ),
            true
        );

        self::assertIsArray($modulos);
        self::assertNotEmpty($modulos);

        foreach ($modulos as $modulo) {
            $id = $modulo['id'] ?? '?';
            $lang = $modulo['language'] ?? '?';

            self::assertNotEmpty($modulo['icone'] ?? null,
                "Módulo sem ícone Fomantic: {$id} ({$lang})");
            self::assertNotEmpty($modulo['icone_tailwind'] ?? null,
                "Módulo sem ícone Lucide: {$id} ({$lang})");

            foreach (['icone_tailwind', 'icone2_tailwind'] as $campo) {
                $valor = $modulo[$campo] ?? null;
                if ($valor === null || $valor === '') continue;

                self::assertMatchesRegularExpression('/^[a-z0-9]+(-[a-z0-9]+)*$/', $valor,
                    "`{$campo}` fora do kebab-case do Lucide em {$id} ({$lang}): {$valor}");
            }
        }
    }

    // ===== F4: a barra lateral rola até o fim

    /**
     * O componente do menu é um item flex do `<aside>`. Com `h-full` e `min-height:auto` ele se
     * recusava a encolher, empurrava o rodapé para fora do viewport e levava junto o último item da
     * rolagem — o botão "Sair". `min-h-0 flex-1` é o par que faz o `overflow-y-auto` do <nav>
     * realmente rolar dentro da altura disponível.
     */
    #[DataProvider('idiomasProvider')]
    public function testMenuLateralRolaAteOBotaoSair(string $lang): void
    {
        $html = self::recurso($lang, 'components', 'menu-principal-sistema-tailwind');

        self::assertSame(0, preg_match('/<div class="flex h-full flex-col">/', $html),
            "Container do menu ainda usa `h-full` sem `min-h-0`: o rodapé sai do viewport ({$lang})");

        self::assertMatchesRegularExpression('/<div class="[^"]*\bmin-h-0\b[^"]*\bflex-1\b[^"]*">/', $html,
            "Container do menu sem `min-h-0 flex-1` ({$lang})");

        self::assertMatchesRegularExpression('/<nav class="[^"]*\bmin-h-0\b[^"]*\boverflow-y-auto\b[^"]*"/', $html,
            "Área rolável do menu sem `min-h-0` ({$lang})");

        // Folga no fim da rolagem: sem ela o último item encosta na borda e parece cortado.
        self::assertMatchesRegularExpression('/<nav class="[^"]*\bpb-1[2-6]\b[^"]*"/', $html,
            "Menu sem folga inferior para o botão Sair ({$lang})");
    }

    // ===== F5: o marcador do histórico nunca chega à tela

    /**
     * A troca do histórico casava a string literal `<td>#historico#</td>`, que só existe no
     * componente Fomantic. O componente Tailwind escreve `<td class="…">#historico#</td>` e não
     * casava com nada: o marcador cru ficava impresso na página.
     */
    public function testTrocaDoHistoricoNaoDependeDeUmTdSemAtributos(): void
    {
        $php = (string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'interface.php'
        );

        self::assertStringNotContainsString('"<td>#historico#</td>"', $php,
            'A troca do histórico voltou a depender de um `<td>` sem atributos.');
    }

    /**
     * O outro lado do contrato: os dois componentes de edição precisam manter o token e as tags de
     * célula que o PHP remove quando não há histórico nenhum.
     */
    #[DataProvider('idiomasProvider')]
    public function testComponentesDeEdicaoMantemOContratoDoHistorico(string $lang): void
    {
        foreach (['interface-formulario-edicao', 'interface-formulario-edicao-tailwind'] as $id) {
            $html = self::recurso($lang, 'components', $id);

            self::assertStringContainsString('#historico#', $html, "{$lang}/{$id}: token perdido");
            self::assertStringContainsString('<!-- historico < -->', $html, "{$lang}/{$id}: abertura da célula");
            self::assertStringContainsString('<!-- historico > -->', $html, "{$lang}/{$id}: fechamento da célula");
        }

        // Só a variante Tailwind ganha o gancho: é ela que convive com painéis de abas.
        self::assertStringContainsString('data-c2f-historico',
            self::recurso($lang, 'components', 'interface-formulario-edicao-tailwind'),
            "{$lang}: bloco de histórico sem gancho para isolamento por aba");
    }

    // ===== req-125 (BATCH-127): estado inicial dos botões de menu

    /**
     * req-125 F3: no desktop o menu nasce EXPANDIDO, então o botão "abrir" precisa nascer oculto —
     * senão ele pisca ao lado do "fechar" até o runtime sincronizar os dois. O `lg:hidden` é o mesmo
     * recurso que a barra lateral usa com `lg:translate-x-0`: estado correto na primeira pintura,
     * removido pelo JS assim que o estado real (viewport + localStorage) é conhecido.
     */
    #[DataProvider('idiomasProvider')]
    public function testBotaoAbrirMenuNasceOcultoNoDesktop(string $lang): void
    {
        $html = self::recurso($lang, 'layouts', 'layout-administrativo-tailwind');

        self::assertMatchesRegularExpression(
            '/<button[^>]*data-admin-abrir[^>]*class="[^"]*lg:hidden[^"]*"/',
            $html,
            "Botão data-admin-abrir sem classe `lg:hidden` no layout inicial ({$lang})"
        );
    }
}
