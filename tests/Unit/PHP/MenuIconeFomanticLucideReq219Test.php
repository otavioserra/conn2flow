<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-219: módulo sem `icone_tailwind` caía no `icone` do Fomantic ("chart bar" no `sales-reports` do
 * conn2flow-site) e o ícone sumia do menu Tailwind. O recurso agora traduz para o Lucide.
 */
final class MenuIconeFomanticLucideReq219Test extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (function_exists('gestor_pagina_menu_icone_fomantic_lucide')) return;
        $fonte = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/gestor.php');
        foreach (['gestor_pagina_menu_icone', 'gestor_pagina_menu_icone_fomantic_lucide'] as $funcao) {
            $inicio = strpos($fonte, 'function ' . $funcao . '(');
            self::assertNotFalse($inicio, $funcao);
            $abre = strpos($fonte, '{', $inicio);
            $nivel = 0;
            for ($i = $abre; $i < strlen($fonte); $i++) {
                if ($fonte[$i] === '{') $nivel++;
                if ($fonte[$i] === '}' && --$nivel === 0) break;
            }
            eval(substr($fonte, $inicio, $i - $inicio + 1));
        }
    }

    public function testNomesDoFomanticViramLucide(): void
    {
        self::assertSame('chart-column', gestor_pagina_menu_icone_fomantic_lucide('chart bar'));
        self::assertSame('ticket', gestor_pagina_menu_icone_fomantic_lucide('ticket alternate'));
        self::assertSame('receipt', gestor_pagina_menu_icone_fomantic_lucide('receipt'));
        self::assertSame('file-text', gestor_pagina_menu_icone_fomantic_lucide('file alternate outline'));
        self::assertSame('users', gestor_pagina_menu_icone_fomantic_lucide('users'));
        self::assertSame('folder', gestor_pagina_menu_icone_fomantic_lucide('folder outline'));
        self::assertSame('', gestor_pagina_menu_icone_fomantic_lucide(''));
    }

    public function testMenuTailwindUsaOParQuandoExisteETraduzQuandoNao(): void
    {
        self::assertSame('ticket-percent', gestor_pagina_menu_icone(['icone' => 'ticket alternate', 'icone_tailwind' => 'ticket-percent'], 'icone', true));
        self::assertSame('chart-column', gestor_pagina_menu_icone(['icone' => 'chart bar', 'icone_tailwind' => null], 'icone', true));
        self::assertSame('chart bar', gestor_pagina_menu_icone(['icone' => 'chart bar'], 'icone', false), 'menu Fomantic segue com o nome dele');
        self::assertSame('', gestor_pagina_menu_icone(['icone2' => 'bottom right corner list'], 'icone2', true), 'ícone ancorado não herda');
    }

    public function testItemSairDoMenuTailwindTemOAtributoLucide(): void
    {
        $fonte = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/gestor.php');
        self::assertStringContainsString("\"#icon-lucide#\",(\$menuTailwind ? gestor_pagina_menu_icone_lucide_atributo('log-out') : '')", $fonte);
    }
}
