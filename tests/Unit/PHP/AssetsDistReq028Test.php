<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-028 / BATCH-023 — publicação de assets em `public_html/dist/`, resolução de URLs e
 * fallback transparente do controlador `arquivo-estatico`.
 *
 * O ponto crítico do lote é a RETROCOMPATIBILIDADE: nenhuma instalação sem `dist/` publicado pode
 * mudar de comportamento, e nenhuma URL antiga pode deixar de resolver depois da publicação.
 */
final class AssetsDistReq028Test extends TestCase
{
    /** @var array<string, mixed> */
    private array $gestorOriginal = [];

    public static function setUpBeforeClass(): void
    {
        if (!defined('SDD_NO_AUTORUN')) {
            define('SDD_NO_AUTORUN', true);
        }

        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'bibliotecas'
            . DIRECTORY_SEPARATOR . 'recursos.php';
        require_once CONN2FLOW_GESTOR_ROOT . DIRECTORY_SEPARATOR . 'controladores'
            . DIRECTORY_SEPARATOR . 'arquivo-estatico' . DIRECTORY_SEPARATOR . 'arquivo-estatico.php';
    }

    protected function setUp(): void
    {
        global $_GESTOR;
        $this->gestorOriginal = $_GESTOR;
    }

    protected function tearDown(): void
    {
        global $_GESTOR;
        $_GESTOR = $this->gestorOriginal;
    }

    /** Simula uma instalação com assets publicados em `dist/`. */
    private function comManifesto(array $arquivos, string $urlRaiz = '/', string $urlRaizSemLang = '/'): void
    {
        global $_GESTOR;

        $_GESTOR['url-raiz'] = $urlRaiz;
        $_GESTOR['url-raiz-sem-lang'] = $urlRaizSemLang;
        $_GESTOR['dist-url'] = $urlRaizSemLang . 'dist/';
        $_GESTOR['dist-manifest'] = $arquivos;
        $_GESTOR['dist-ativo'] = true;
    }

    /** Simula uma instalação sem publicação — o estado de toda instalação existente. */
    private function semManifesto(string $urlRaiz = '/'): void
    {
        global $_GESTOR;

        $_GESTOR['url-raiz'] = $urlRaiz;
        $_GESTOR['url-raiz-sem-lang'] = '/';
        $_GESTOR['dist-url'] = '/dist/';
        $_GESTOR['dist-manifest'] = [];
        $_GESTOR['dist-ativo'] = false;
    }

    public function testNormalizacaoDeCaminhoIgnoraQueryERecusaTraversal(): void
    {
        self::assertSame('interface/interface.js', recursos_caminho_normalizar('interface/interface.js'));
        self::assertSame('interface/interface.js', recursos_caminho_normalizar('/interface/interface.js'));
        self::assertSame('interface/interface.js', recursos_caminho_normalizar('//interface//interface.js'));
        // A query de cache busting não faz parte da identidade do arquivo.
        self::assertSame('interface/interface.js', recursos_caminho_normalizar('interface/interface.js?v=abc'));
        self::assertSame('interface/interface.js', recursos_caminho_normalizar('interface/interface.js#topo'));
        self::assertSame('admin-arquivos/js.js', recursos_caminho_normalizar('admin-arquivos\\js.js'));

        self::assertSame('', recursos_caminho_normalizar(''));
        self::assertSame('', recursos_caminho_normalizar('/'));
        self::assertSame('', recursos_caminho_normalizar('../../gestor/.env'));
        self::assertSame('', recursos_caminho_normalizar('interface/../../.env'));
    }

    public function testSemManifestoAUrlEmitidaEExatamenteAHistorica(): void
    {
        $this->semManifesto();

        self::assertSame('/interface/interface.js', recursos_url('interface/interface.js'));
        self::assertSame('/admin-arquivos/js.js', recursos_url('admin-arquivos/js.js'));
        self::assertFalse(recursos_publicado('interface/interface.js'));

        // Instalação em subpasta e com idioma na URL preserva a base histórica.
        $this->semManifesto('/gestor/en/');
        self::assertSame('/gestor/en/interface/interface.js', recursos_url('interface/interface.js'));
    }

    public function testComManifestoAUrlApontaParaDistSemOPrefixoDeIdioma(): void
    {
        // Cenário real: o roteador já anexou `en/` a `url-raiz` para a requisição corrente.
        $this->comManifesto(
            ['interface/interface.js' => ['sha1' => str_repeat('a', 40), 'v' => 'aaaaaaaaaaaaaaaa']],
            '/en/',
            '/'
        );

        self::assertTrue(recursos_publicado('interface/interface.js'));
        // Um mesmo arquivo físico não pode existir em uma URL por idioma.
        self::assertSame('/dist/interface/interface.js', recursos_url('interface/interface.js'));

        // O que NÃO está publicado continua na rota antiga, com o idioma.
        self::assertFalse(recursos_publicado('admin-arquivos/js.js'));
        self::assertSame('/en/admin-arquivos/js.js', recursos_url('admin-arquivos/js.js'));
    }

    public function testManifestoDesligadoMantemTudoNaRotaAntiga(): void
    {
        global $_GESTOR;

        $this->comManifesto(['interface/interface.js' => ['sha1' => str_repeat('b', 40), 'v' => 'bbbb']]);
        // É assim que o ambiente de desenvolvimento se comporta: fonte tem precedência.
        $_GESTOR['dist-ativo'] = false;

        self::assertFalse(recursos_publicado('interface/interface.js'));
        self::assertSame('/interface/interface.js', recursos_url('interface/interface.js'));
    }

    public function testVersaoUsaOHashDoConteudoPublicadoQuandoDisponivel(): void
    {
        $this->comManifesto(['interface/interface.js' => ['sha1' => str_repeat('c', 40), 'v' => 'cccccccccccccccc']]);

        // O hash do conteúdo é mais preciso que a versão do dono do asset.
        self::assertSame('cccccccccccccccc', recursos_versao('interface/interface.js', '1.0.0'));
        // Fora do manifesto, vale a versão informada pelo chamador (comportamento histórico).
        self::assertSame('1.0.0', recursos_versao('admin-arquivos/js.js', '1.0.0'));
    }

    public function testTagsGeradasCarregamOCacheBustingEAtributosExtras(): void
    {
        $this->comManifesto(['interface/interface.css' => ['sha1' => str_repeat('d', 40), 'v' => 'dddddddddddddddd']]);

        self::assertSame(
            '<script src="/admin-arquivos/js.js?v=9.9.9"></script>',
            recursos_tag_js('admin-arquivos/js.js', '9.9.9')
        );
        self::assertSame(
            '<link rel="stylesheet" type="text/css" media="all" href="/dist/interface/interface.css?v=dddddddddddddddd" />',
            recursos_tag_css('interface/interface.css', '1.0.0')
        );
        self::assertStringContainsString(
            'data-c2f-css-role="quill"',
            recursos_tag_css('interface/quill-content.css', '1.0.0', 'data-c2f-css-role="quill"')
        );
    }

    public function testContratoDeMapeamentoDeFonteParaCaminhoPublicado(): void
    {
        // assets/ publica com o mesmo caminho da URL.
        self::assertSame('interface/interface.js', recursos_dist_mapear_fonte('assets/interface/interface.js'));
        self::assertSame('favicon/site.webmanifest', recursos_dist_mapear_fonte('assets/favicon/site.webmanifest'));
        self::assertSame('datatables/datatables.min.js', recursos_dist_mapear_fonte('assets/datatables/datatables.min.js'));

        // modulos/<m>/<m>.js  ->  <m>/js.js  (a URL que `gestor_pagina_javascript_incluir` emite).
        self::assertSame('admin-arquivos/js.js', recursos_dist_mapear_fonte('modulos/admin-arquivos/admin-arquivos.js'));
        self::assertSame('admin-arquivos/css.css', recursos_dist_mapear_fonte('modulos/admin-arquivos/admin-arquivos.css'));
        self::assertSame('forms/widget.js', recursos_dist_mapear_fonte('modulos/forms/forms.widget.js'));
        self::assertSame('dashboard/toolbar.js', recursos_dist_mapear_fonte('modulos/dashboard/dashboard.toolbar.js'));

        // O derivado do módulo não tem caminho próprio: ele é a variante preferida do de autoria.
        self::assertSame('', recursos_dist_mapear_fonte('modulos/admin-arquivos/admin-arquivos.min.js'));

        // Nada fora do contrato entra na pasta pública.
        self::assertSame('', recursos_dist_mapear_fonte('modulos/admin-arquivos/admin-arquivos.php'));
        self::assertSame('', recursos_dist_mapear_fonte('modulos/admin-arquivos/admin-arquivos.json'));
        self::assertSame('', recursos_dist_mapear_fonte('modulos/admin-arquivos/resources/pt-br/pagina.html'));
        self::assertSame('', recursos_dist_mapear_fonte('bibliotecas/gestor.php'));
        self::assertSame('', recursos_dist_mapear_fonte('contents/usuario/foto.webp'));
        self::assertSame('', recursos_dist_mapear_fonte('.env'));
        self::assertSame('', recursos_dist_mapear_fonte('assets'));
        self::assertSame('', recursos_dist_mapear_fonte('../gestor/.env'));
    }

    public function testAUrlEmitidaEOMesmoCaminhoPublicadoPeloPipeline(): void
    {
        // Esta é a garantia que sustenta o lote: se as duas pontas divergirem, o site quebra em
        // produção sem quebrar em desenvolvimento. O caminho publicado TEM de ser a chave usada
        // pela resolução de URL.
        $publicado = recursos_dist_mapear_fonte('modulos/admin-paginas/admin-paginas.js');
        $this->comManifesto([$publicado => ['sha1' => str_repeat('e', 40), 'v' => 'eeee']]);

        self::assertSame('admin-paginas/js.js', $publicado);
        self::assertSame('/dist/admin-paginas/js.js', recursos_url($publicado));
    }

    public function testFallbackDoControladorRemoveOPrefixoDist(): void
    {
        // Requisição a /dist/... só chega ao PHP quando o arquivo NÃO está publicado: ambiente de
        // desenvolvimento, asset novo, ou instalação sem o pipeline executado.
        self::assertSame('interface/interface.js', arquivo_estatico_sem_prefixo_dist('dist/interface/interface.js'));
        self::assertSame('admin-arquivos/js.js', arquivo_estatico_sem_prefixo_dist('dist/admin-arquivos/js.js'));

        // Sem prefixo, o caminho não é tocado — a rota antiga segue idêntica.
        self::assertSame('interface/interface.js', arquivo_estatico_sem_prefixo_dist('interface/interface.js'));
        self::assertSame('distribuidor/js.js', arquivo_estatico_sem_prefixo_dist('distribuidor/js.js'));
        self::assertSame('modulos/dist/js.js', arquivo_estatico_sem_prefixo_dist('modulos/dist/js.js'));

        // `dist/` sozinho não descreve asset nenhum e não vira caminho vazio.
        self::assertSame('dist/', arquivo_estatico_sem_prefixo_dist('dist/'));
        self::assertSame('', arquivo_estatico_sem_prefixo_dist(''));

        // Só um nível é removido: `dist/dist/x` continua pedindo um `dist/x` inexistente.
        self::assertSame('dist/x.js', arquivo_estatico_sem_prefixo_dist('dist/dist/x.js'));
    }

    public function testTemplateNginxDoLabEntregaDistDiretamenteDoDisco(): void
    {
        // O template vive no repositório do Site; quando ele não está montado ao lado do core, o
        // teste não tem o que verificar — mas nunca inventa uma aprovação.
        $base = dirname(CONN2FLOW_ROOT) . DIRECTORY_SEPARATOR . 'conn2flow-site'
            . DIRECTORY_SEPARATOR . 'devops' . DIRECTORY_SEPARATOR . 'lab-hestiacp';

        if (!is_dir($base)) {
            self::markTestSkipped('Repositório conn2flow-site não está ao lado do core.');
        }

        foreach (['conn2flow-nginx.tpl', 'conn2flow-nginx.stpl'] as $arquivo) {
            $conteudo = (string)file_get_contents($base . DIRECTORY_SEPARATOR . $arquivo);

            self::assertStringContainsString('location /dist/ {', $conteudo, $arquivo);
            self::assertStringContainsString('expires max;', $conteudo, $arquivo);
            self::assertStringContainsString('try_files $uri =404;', $conteudo, $arquivo);

            // O manifesto e o .htaccess do dist sao ocultos: a regra que nega arquivos iniciados
            // por ponto e regex, entao vence o prefixo `/dist/` e os mantem fora da web.
            self::assertStringContainsString('location ~ /\.(?!well-known', $conteudo, $arquivo);

            // `location /dist/` precisa preceder `location /`, senão o front-controller ganharia
            // a requisição e o PHP voltaria a ser acionado por asset.
            self::assertLessThan(
                strpos($conteudo, 'location / {'),
                strpos($conteudo, 'location /dist/ {'),
                "location /dist/ deve preceder location / em {$arquivo}"
            );
        }
    }
}
