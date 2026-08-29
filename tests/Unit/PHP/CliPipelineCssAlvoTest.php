<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * BATCH-147 — o estágio de CSS dos pipelines precisa apontar para a base certa.
 *
 * O defeito medido: `c2f project:update-all transformamp-local` recebe o projeto como ARGUMENTO,
 * mas o `css:rebuild` o lê como OPÇÃO (`--project=`). Repassando o mesmo `$input`, a etapa caía no
 * default — o ambiente de teste do SISTEMA — e regenerava `conn2flow` em vez de `transformamp`.
 *
 * O pior é que falhava reportando SUCESSO: "analisados: 235 | regenerados: 0 | já coerentes: 235".
 * Números plausíveis, base errada. É a mesma classe de erro silencioso que o req-141 existe para
 * eliminar, e por isso a checagem é estrutural.
 */
final class CliPipelineCssAlvoTest extends TestCase
{
    private static function comando(string $arquivo): string
    {
        return (string)file_get_contents(
            dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR . $arquivo
        );
    }

    public function testOPipelineDeProjetoDeclaraOProjetoParaOCssRebuild(): void
    {
        $codigo = self::comando('ProjectUpdateAllCommand.php');

        // Um `$input` novo, com o id explícito — e não o `$input` de quem chamou.
        self::assertStringContainsString("'css:rebuild', '--project=' . \$project", $codigo);
        self::assertStringContainsString('$cssCmd->execute(new Input($argv), $output)', $codigo);
        self::assertStringNotContainsString('$cssCmd->execute($input, $output)', $codigo);

        // A trava de ambiente remoto tem de sobreviver ao repasse.
        self::assertStringContainsString("hasOption('confirmar-remoto')", $codigo);
    }

    public function testOPipelineDoSistemaContinuaSemProjeto(): void
    {
        // No pipeline do SISTEMA a ausência de projeto é correta: o alvo é o ambiente de teste do
        // gestor. A asserção existe para que a correção acima não seja copiada para cá por engano.
        $codigo = self::comando('ManagerUpdateAllCommand.php');

        self::assertStringNotContainsString("--project=", $codigo);
        self::assertStringContainsString('$cssCmd->execute($input, $output)', $codigo);
    }

    public function testOsDoisPipelinesMantemOEstagioDeCssNaoFatal(): void
    {
        // A etapa é condicionada (sem Tailwind CLI ela apenas avisa). Se virar fatal, uma máquina
        // sem Node passa a não conseguir rodar o pipeline inteiro.
        foreach (['ProjectUpdateAllCommand.php', 'ManagerUpdateAllCommand.php'] as $arquivo) {
            $codigo = self::comando($arquivo);

            self::assertStringContainsString('$output->warning(', $codigo, $arquivo);
            self::assertStringContainsString("css:audit", $codigo, $arquivo);
        }
    }
}
