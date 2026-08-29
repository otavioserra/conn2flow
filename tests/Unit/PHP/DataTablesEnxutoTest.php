<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-145 / BATCH-148 — o DataTables ficou reduzido ao que o sistema realmente usa.
 *
 * Eram 67 arquivos (1,7 MB): as versões não minificadas e temas de Bootstrap, Foundation e jQuery UI
 * que este projeto não usa. O sistema referencia TRÊS, e o CSS não pede arquivo nenhum (`url()`
 * vazio), o que tornou a remoção segura de verificar.
 *
 * O teste existe nos dois sentidos: impedir que os três sumam (a tela de listagem para de funcionar)
 * e impedir que os 64 voltem (superfície exposta sem uso).
 */
final class DataTablesEnxutoTest extends TestCase
{
    private const NECESSARIOS = [
        'datatables.min.js',
        'datatables.min.css',
        '1.10.23/pt_br.json',
    ];

    private static function base(): string
    {
        return CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'datatables';
    }

    public function testOsTresArquivosUsadosContinuamPresentes(): void
    {
        foreach (self::NECESSARIOS as $relativo) {
            $arquivo = self::base() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativo);

            self::assertFileExists($arquivo, $relativo . ' e usado pelo runtime');
            self::assertGreaterThan(0, (int)filesize($arquivo), $relativo . ' esta vazio');
        }
    }

    public function testCadaArquivoPresenteTemUmConsumidorNoCodigo(): void
    {
        // A regra que justificou a remocao: fica o que alguem referencia. Se um arquivo novo entrar
        // sem consumidor, ele e peso morto — e o teste avisa antes de virar 1,7 MB de novo.
        $arquivos = glob(self::base() . DIRECTORY_SEPARATOR . '*') ?: [];
        $arquivos = array_merge($arquivos, glob(self::base() . DIRECTORY_SEPARATOR . '*'
            . DIRECTORY_SEPARATOR . '*') ?: []);

        $presentes = [];
        foreach ($arquivos as $caminho) {
            if (is_file($caminho)) {
                $presentes[] = str_replace(DIRECTORY_SEPARATOR, '/', substr($caminho, strlen(self::base()) + 1));
            }
        }

        sort($presentes);
        $esperados = self::NECESSARIOS;
        sort($esperados);

        self::assertSame($esperados, $presentes);
    }

    public function testOReferenciadorDoIdiomaApontaParaOCaminhoQueExiste(): void
    {
        // `interface.js` e `interface-v2.js` montam a URL do arquivo de idioma. Se o caminho mudar de
        // um lado so, a tabela carrega em ingles sem nenhum erro visivel.
        $caminho = 'datatables/1.10.23/pt_br.json';

        foreach (['interface/interface.js', 'interface-v2/interface-v2.js'] as $relativo) {
            $js = (string)file_get_contents(
                CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $relativo)
            );

            self::assertStringContainsString($caminho, $js, $relativo);
        }

        self::assertFileExists(self::base() . DIRECTORY_SEPARATOR . '1.10.23'
            . DIRECTORY_SEPARATOR . 'pt_br.json');
    }

    public function testOCssNaoPedeArquivoExterno(): void
    {
        // Foi esta checagem que tornou a remocao segura: se o CSS pedisse sprites por caminho
        // relativo, apagar a arvore repetiria a falha dos icones do Fomantic (DEC-123).
        $css = (string)file_get_contents(self::base() . DIRECTORY_SEPARATOR . 'datatables.min.css');

        preg_match_all('#url\(([^)]*)\)#i', $css, $m);

        $externos = [];
        foreach ($m[1] ?? [] as $url) {
            $url = trim($url, "\"' ");
            if ($url !== '' && strpos($url, 'data:') !== 0) {
                $externos[] = $url;
            }
        }

        self::assertSame([], $externos, 'CSS passou a pedir arquivo: ' . implode(', ', $externos));
    }
}
