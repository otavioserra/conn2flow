<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-143 / BATCH-146 — biblioteca de assets externos.
 *
 * O inventário que motivou esta biblioteca: 11 bibliotecas de terceiros em 27 arquivos, com
 * `sortablejs@latest` repetido 16 vezes. Versão flutuante significa que qualquer publicação no npm
 * entra em produção sem revisão — um release quebrado do Sortable derrubaria os CRUDs de ordenação
 * sem que ninguém tivesse alterado o projeto.
 */
final class AssetsExternosTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas'
            . DIRECTORY_SEPARATOR . 'assets-externos.php';
    }

    public function testNenhumaBibliotecaUsaVersaoFlutuante(): void
    {
        // `latest`, `*` e faixas (`^1.2`) reintroduzem exatamente o risco que a biblioteca elimina.
        foreach (assets_externos_registro() as $nome => $lib) {
            $versao = (string)($lib['versao'] ?? '');

            self::assertNotSame('', $versao, $nome . ' sem versão declarada');
            self::assertDoesNotMatchRegularExpression(
                '/latest|\*|\^|~/i',
                $versao,
                $nome . ': versão flutuante (' . $versao . ')'
            );
        }
    }

    public function testPrefereOArquivoLocalQuandoEleExiste(): void
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'c2f-vendor-' . uniqid() . DIRECTORY_SEPARATOR;
        @mkdir($dir . 'sortablejs' . DIRECTORY_SEPARATOR . '1.15.6', 0777, true);
        file_put_contents($dir . 'sortablejs/1.15.6/Sortable.min.js', '// local');

        $lib = assets_externos_registro()['sortablejs'];
        $url = assets_externos_url($lib, 'sortablejs', 'Sortable.min.js', $dir, '/vendor/');

        self::assertSame('/vendor/sortablejs/1.15.6/Sortable.min.js', $url);

        @unlink($dir . 'sortablejs/1.15.6/Sortable.min.js');
        @rmdir($dir . 'sortablejs' . DIRECTORY_SEPARATOR . '1.15.6');
        @rmdir($dir . 'sortablejs');
        @rmdir($dir);
    }

    public function testCaiNoCdnQuandoOArquivoLocalNaoExiste(): void
    {
        // O fallback é deliberado: instalação que ainda não baixou os arquivos continua funcionando,
        // e a migração pode ser feita biblioteca por biblioteca.
        $lib = assets_externos_registro()['sortablejs'];
        $url = assets_externos_url($lib, 'sortablejs', 'Sortable.min.js', '/caminho/inexistente/', '/vendor/');

        self::assertStringStartsWith('https://', $url);
        self::assertStringContainsString('sortablejs@1.15.6', $url);
        self::assertStringNotContainsString('@latest', $url);
    }

    public function testTagsSaoMontadasParaCadaTipoDeArquivo(): void
    {
        $tags = assets_externos_tags('sortablejs', '/inexistente/', '/vendor/');

        self::assertCount(1, $tags['js']);
        self::assertStringContainsString('<script src="', $tags['js'][0]);
        self::assertSame([], $tags['css']);
    }

    public function testBibliotecaDesconhecidaNaoQuebra(): void
    {
        // Erro de digitação no nome não pode derrubar a tela: devolve vazio e quem chama decide.
        $tags = assets_externos_tags('nao-registrada', '', '');

        self::assertSame([], $tags['css']);
        self::assertSame([], $tags['js']);
    }

    public function testNenhumModuloReferenciaOSortableDiretamente(): void
    {
        // A regressão provável é alguém colar de novo a tag do CDN num módulo novo.
        $arquivos = glob(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos'
            . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.php') ?: [];

        self::assertNotEmpty($arquivos);

        $reincidentes = [];
        foreach ($arquivos as $arquivo) {
            $codigo = (string)file_get_contents($arquivo);
            if (strpos($codigo, 'cdn.jsdelivr.net/npm/sortablejs') !== false) {
                $reincidentes[] = basename($arquivo);
            }
        }

        self::assertSame([], $reincidentes, 'inclusão direta do Sortable em: ' . implode(', ', $reincidentes));
    }

    public function testAsBibliotecasDoInventarioEstaoRegistradas(): void
    {
        // O inventario medido no core antes do lote. Se uma sair do registro, o ponto de uso volta a
        // ser uma URL solta em codigo e a versao deixa de ter dono.
        $registro = assets_externos_registro();

        foreach (['jquery', 'codemirror', 'fomantic-ui', 'quill', 'sortablejs'] as $nome) {
            self::assertArrayHasKey($nome, $registro, $nome . ' saiu do registro');
        }
    }

    public function testOJqueryTemUmaVersaoSo(): void
    {
        // Estava em QUATRO pontos com TRES versoes e QUATRO hosts (3.5.1 googleapis, 3.7.1 jsdelivr,
        // 3.7.1 cdnjs, 3.6.0 jsdelivr). Duas versoes na mesma tela quebram plugins de um jeito
        // dificil de rastrear, porque quem carrega por ultimo vence.
        $registro = assets_externos_registro();

        self::assertSame('3.7.1', $registro['jquery']['versao']);

        $emissores = array_merge(
            glob(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . '*.php') ?: [],
            glob(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . '*.php') ?: []
        );

        $reincidentes = [];
        foreach ($emissores as $arquivo) {
            if (basename($arquivo) === 'assets-externos.php') {
                continue;
            }
            if (preg_match('#(jsdelivr|cdnjs|googleapis)\S{0,80}jquery#i', (string)file_get_contents($arquivo))) {
                $reincidentes[] = basename($arquivo);
            }
        }

        self::assertSame([], $reincidentes, 'jQuery ainda vem de CDN em: ' . implode(', ', $reincidentes));
    }

    public function testAOrdemDeCargaDoCodeMirrorEPreservada(): void
    {
        // `codemirror.min.js` define o objeto que TODO addon estende. Se ele deixar de ser o
        // primeiro, os addons falham na carga e o editor abre como textarea puro.
        $js = assets_externos_registro()['codemirror']['js'];

        self::assertSame('codemirror.min.js', $js[0]);
        self::assertContains('mode/htmlmixed/htmlmixed.js', $js);
    }

    public function testNenhumaBibliotecaEmiteCodeMirrorDiretamente(): void
    {
        // Eram 161 tags espalhadas por sete arquivos PHP. A regressao provavel e alguem colar o
        // bloco de novo num modulo novo.
        $arquivos = array_merge(
            glob(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . '*.php') ?: [],
            glob(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . '*'
                . DIRECTORY_SEPARATOR . '*.php') ?: []
        );

        $reincidentes = [];
        foreach ($arquivos as $arquivo) {
            if (basename($arquivo) === 'assets-externos.php') {
                continue;
            }
            if (strpos((string)file_get_contents($arquivo), 'cdnjs.cloudflare.com/ajax/libs/codemirror') !== false) {
                $reincidentes[] = basename($arquivo);
            }
        }

        self::assertSame([], $reincidentes, 'CodeMirror direto do CDN em: ' . implode(', ', $reincidentes));
    }

    public function testAVersaoDoQuillNaoEUmaFaixa(): void
    {
        // O default era `'2'` — uma faixa, nao uma versao. E `quill-content.css` e gerado a partir
        // dela: uma faixa aqui significa o editor e a pagina publicada podendo divergir sozinhos.
        $registro = assets_externos_registro();

        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $registro['quill']['versao']);

        $editor = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR
            . 'bibliotecas' . DIRECTORY_SEPARATOR . 'editor-texto.php');

        self::assertStringNotContainsString('? $versao : ' . "'2';", $editor);
        self::assertStringContainsString('assets_externos_registro()', $editor);
    }

    public function testNenhumAssetDeTerceiroUsaLatestNoCore(): void
    {
        // Varre além do Sortable: qualquer `@latest` novo em módulo ou biblioteca falha aqui.
        $arquivos = array_merge(
            glob(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'modulos' . DIRECTORY_SEPARATOR . '*'
                . DIRECTORY_SEPARATOR . '*.php') ?: [],
            glob(CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas' . DIRECTORY_SEPARATOR . '*.php') ?: []
        );

        $flutuantes = [];
        foreach ($arquivos as $arquivo) {
            $codigo = (string)file_get_contents($arquivo);
            if (preg_match('#(cdn\.jsdelivr\.net|unpkg\.com)/[^"\']*@latest#', $codigo)) {
                $flutuantes[] = basename($arquivo);
            }
        }

        self::assertSame([], $flutuantes, 'versão flutuante em: ' . implode(', ', $flutuantes));
    }
}
