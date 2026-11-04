<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * REQ-298 — membro de equipe sem o painel: primeira página permitida, recusa sem laço e atalhos
 * fixos do layout escondidos quando o perfil não abre o módulo.
 */
final class EquipePermissoesReq298Test extends TestCase
{
    private static function fonte(string $arquivo): string
    {
        return str_replace("\r\n", "\n", (string)file_get_contents(dirname(__DIR__, 3) . '/' . $arquivo));
    }

    private const PAGINAS = [
        ['modulo' => 'agenda', 'caminho' => 'agenda/'],
        ['modulo' => 'anamnese', 'caminho' => 'anamnese-paciente/'],
        ['modulo' => 'anamnese', 'caminho' => 'anamnese/'],
        ['modulo' => 'dashboard', 'caminho' => 'dashboard/'],
        ['modulo' => 'perfil-usuario', 'caminho' => 'perfil-usuario/'],
        ['modulo' => 'pacientes', 'caminho' => 'pacientes/'],
    ];

    private const ORDEM = ['agenda', 'anamnese', 'dashboard', 'pacientes', 'perfil-usuario'];

    /** @return array<string,array{0:list<string>,1:string}> */
    public static function rotasProvider(): array
    {
        return [
            'com painel' => [['agenda', 'dashboard'], 'dashboard/'],
            'agenda e anamnese' => [['anamnese', 'agenda', 'perfil-usuario'], 'agenda/'],
            'página do próprio módulo vence a pública' => [['anamnese', 'perfil-usuario'], 'anamnese/'],
            'perfil-usuario só como último recurso' => [['perfil-usuario', 'pacientes'], 'pacientes/'],
            'só perfil-usuario' => [['perfil-usuario'], 'perfil-usuario/'],
            'módulo sem página' => [['calculadora'], ''],
            'nada' => [[], ''],
        ];
    }

    #[DataProvider('rotasProvider')]
    public function testEscolheAPrimeiraPaginaPermitida(array $permitidos, string $esperado): void
    {
        self::assertSame($esperado, gestor_permissao_rota_inicial_escolher($permitidos, self::ORDEM, self::PAGINAS));
    }

    public function testBlocosDeModuloSemPermissaoSomem(): void
    {
        $html = "<nav>\n<!-- c2f-modulo:user-subscription < --><a>Assinatura</a><!-- c2f-modulo:user-subscription > -->\n"
            . "<!-- c2f-modulo:agenda < --><a>Agenda</a><!-- c2f-modulo:agenda > -->\n"
            . "<!-- c2f-modulo:user-subscription < --><b>de novo</b><!-- c2f-modulo:user-subscription > -->\n</nav>";

        $chamadas = [];
        $saida = gestor_pagina_blocos_por_modulo($html, function (string $modulo) use (&$chamadas): bool {
            $chamadas[] = $modulo;
            return $modulo === 'agenda';
        });

        self::assertSame("<nav>\n\n<a>Agenda</a>\n\n</nav>", $saida);
        self::assertSame(['user-subscription', 'agenda'], $chamadas, 'cada módulo é consultado uma vez');
    }

    public function testHtmlSemMarcadorNaoMuda(): void
    {
        $html = '<div><!-- outro comentário --></div>';

        self::assertSame($html, gestor_pagina_blocos_por_modulo($html, fn() => false));
    }

    public function testRecusaUsaARotaInicialDoPerfil(): void
    {
        $fonte = self::fonte('gestor/gestor.php');
        $inicio = strpos($fonte, 'function gestor_permissao(){');
        $corpo = substr($fonte, $inicio, strpos($fonte, 'function gestor_usuario(){') - $inicio);

        self::assertStringContainsString('$rota_inicial = gestor_permissao_rota_inicial();', $corpo);
        self::assertStringContainsString('gestor_redirecionar($rota_inicial);', $corpo);
        self::assertStringNotContainsString("'redirect' => 'dashboard/'", $corpo);
        self::assertStringContainsString("gestor_perfil_permissao_sql(\$usuario),\n\t));", $corpo, 'a rota inicial respeita o titular');
    }

    public function testMenuSoMostraOPainelAoSubUsuarioComPermissao(): void
    {
        $fonte = self::fonte('gestor/gestor.php');

        self::assertStringContainsString("if(\$modulo['id'] == 'dashboard' && !\$menuSubUsuario){", $fonte);
    }

    public function testBlocosDoLayoutSaoResolvidosComAPagina(): void
    {
        $fonte = self::fonte('gestor/gestor.php');

        self::assertStringContainsString("gestor_pagina_blocos_por_modulo(\$_GESTOR['pagina'], function(\$modulo){", $fonte);
    }
}
