<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-288: toda URL inexistente levava ~1 s no Lab e ~6 s em produção antes do 302 para /404. A busca "a página
 * existe em outro idioma?" pedia html/css sem filtrar idioma: sem o índice (language, caminho) da req-263, o banco
 * lia todas as páginas inteiras (7,8 s medidos em produção; 26 ms com a busca nova).
 */
final class Roteador404RapidoReq288Test extends TestCase
{
    private static function roteador(): string
    {
        return (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/gestor.php');
    }

    public function testOutroIdiomaBuscaPrimeiroSoOIdPeloIndice(): void
    {
        $php = self::roteador();
        $ini = strpos($php, 'Caso não encontre a página e a mesma exista em linguagem diferente');
        $fim = strpos($php, '// ===== Hook: roteador.paginas', (int)$ini);
        $this->assertNotFalse($ini);
        $this->assertNotFalse($fim);
        $trecho = substr($php, (int)$ini, $fim - $ini);
        $this->assertStringContainsString("banco_campos_virgulas(Array('id_paginas'))", $trecho, 'Primeiro só o id.');
        $this->assertStringContainsString('" AND language IN ("', $trecho, 'Idiomas habilitados levam ao índice.');
        $this->assertStringContainsString('" LIMIT 1"', $trecho);
        $this->assertStringContainsString("\"WHERE id_paginas='\".banco_escape_field(\$outro_idioma[0]['id_paginas']).\"'\"", $trecho, 'Página inteira só por id.');
        $this->assertSame(1, substr_count($trecho, 'banco_campos_virgulas($campos)'), 'As colunas pesadas só na busca por id.');
    }

    public function testIdiomasDaPaginaUsamOIndice(): void
    {
        $php = self::roteador();
        $ini = strpos($php, '$paginas_languages = banco_select_name');
        $this->assertNotFalse($ini);
        $this->assertStringContainsString("AND language IN ('", substr($php, (int)$ini, 700));
    }
}
