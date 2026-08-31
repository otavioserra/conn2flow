<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-148 — os modais de sistema entram no CSS de toda página Tailwind.
 *
 * `interface_alerta()` injeta `interface-alerta-modal` em QUALQUER tela onde um alerta dispare — não
 * só nas que se lembraram de declará-lo em `tailwind_dependencies`. Enquanto a declaração era manual,
 * a lista era uma lista de EXCEÇÕES, e bastava estar incompleta uma vez para a tela quebrar.
 *
 * Foi o que aconteceu no `signin` do Photon: `perfil-usuario` e `Area-restrita` declaravam os modais,
 * `acessar-sistema` não. Com senha inválida o modal aparecia sem regra nenhuma — `shadow-xl` virava
 * `box-shadow: none` e `bg-sky-600` virava `rgba(0,0,0,0)`, deixando o botão "Ok" com texto branco
 * sobre fundo transparente.
 *
 * Nas variantes Fomantic isso nunca existiu: as classes delas vivem na folha global do Fomantic. É
 * uma armadilha exclusiva do Tailwind, onde cada utility precisa ser compilada.
 */
final class ModaisSistemaTailwindTest extends TestCase
{
    private static function compilador(): string
    {
        return (string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'controladores' . DIRECTORY_SEPARATOR
            . 'agents' . DIRECTORY_SEPARATOR . 'arquitetura' . DIRECTORY_SEPARATOR . 'tailwind-recursos.php'
        );
    }

    public function testOsModaisSaoDependenciaAutomaticaDeTodaPagina(): void
    {
        $codigo = self::compilador();

        self::assertStringContainsString('function tailwind_recursos_modais_de_sistema(', $codigo);
        self::assertStringContainsString("if (\$type === 'pages') {", $codigo);
        self::assertStringContainsString('tailwind_recursos_modais_de_sistema() as $modalId', $codigo);
    }

    public function testALicaoCobreOsTresModaisInjetadosEmRuntime(): void
    {
        // Os tres que `interface_componentes_incluir()` sabe montar. Se um novo modal entrar no
        // switch da biblioteca e nao entrar aqui, ele repete o defeito.
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'controladores' . DIRECTORY_SEPARATOR
            . 'agents' . DIRECTORY_SEPARATOR . 'arquitetura' . DIRECTORY_SEPARATOR . 'tailwind-recursos.php';

        $modais = tailwind_recursos_modais_de_sistema();

        self::assertContains('interface-alerta-modal-tailwind', $modais);
        self::assertContains('interface-carregando-modal-tailwind', $modais);
        self::assertContains('interface-delecao-modal-tailwind', $modais);
    }

    public function testTodoModalDoSwitchDaInterfaceTemCoberturaNoCompilador(): void
    {
        // Guarda de sincronia entre a biblioteca que INJETA e o compilador que gera o CSS. A lista
        // do compilador so precisa ficar para tras uma vez para o modal novo nascer sem estilo.
        $interface = (string)file_get_contents(
            CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . 'interface.php'
        );

        preg_match_all(
            "/case '(interface-[a-z-]+-modal)':/",
            $interface,
            $m
        );

        $injetados = array_unique($m[1] ?? []);
        self::assertNotEmpty($injetados, 'nenhum modal encontrado no switch da interface');

        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'controladores' . DIRECTORY_SEPARATOR
            . 'agents' . DIRECTORY_SEPARATOR . 'arquitetura' . DIRECTORY_SEPARATOR . 'tailwind-recursos.php';
        $cobertos = tailwind_recursos_modais_de_sistema();

        $descobertos = [];
        foreach ($injetados as $modal) {
            // O `iframe-modal` nao tem variante Tailwind: ele e um contêiner de iframe, sem utilities.
            if ($modal === 'interface-iframe-modal') {
                continue;
            }
            if (!in_array($modal . '-tailwind', $cobertos, true)) {
                $descobertos[] = $modal;
            }
        }

        self::assertSame([], $descobertos, 'modal injetado sem cobertura no compilador: '
            . implode(', ', $descobertos));
    }

    public function testAAusenciaDoComponenteNaoAbortaACompilacao(): void
    {
        // Dependencia AUTOMATICA ausente nao e erro: uma instalacao pode nao ter o componente
        // naquele idioma. Medido no Photon, que traz os modais em `pt-br` e nao em `en` — exigi-los
        // abortava a compilacao do projeto inteiro.
        //
        // Dependencia DECLARADA continua falhando alto: ali a ausencia significa erro de digitacao.
        $codigo = self::compilador();

        self::assertStringContainsString("'opcional' => true", $codigo);
        self::assertStringContainsString("if (!empty(\$dependency['opcional'])) {", $codigo);
        self::assertStringContainsString('Dependência Tailwind do Gestor não encontrada', $codigo);
    }
}
