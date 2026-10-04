<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * req-229 / BATCH-238 — Biblioteca de Estado e Acesso Controlado a Variáveis Globais (Ponte OOP).
 *
 * Valida:
 *  1. Leitura com fallback e notação pontuada (gestor_get).
 *  2. Escrita segura com mutação profunda (gestor_set).
 *  3. Verificação de existência (gestor_has).
 *  4. Fatias de contexto delimitadas (gestor_contexto).
 *  5. Proteção de chaves críticas do Core (raiz-absoluta, url-raiz, linguagem-codigo, versao-num).
 *  6. 100% de retrocompatibilidade bidirecional com $_GESTOR.
 */
final class GestorStateTest extends TestCase
{
    private array $gestorBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        global $_GESTOR;
        $this->gestorBackup = is_array($_GESTOR) ? $_GESTOR : [];
        GestorState::reset();
    }

    protected function tearDown(): void
    {
        global $_GESTOR;
        $_GESTOR = $this->gestorBackup;
        GestorState::reset();
        parent::tearDown();
    }

    // =========================================================================
    // 1. gestor_get()
    // =========================================================================

    public function testGetRetornaValorDireto(): void
    {
        global $_GESTOR;
        $_GESTOR['teste-chave'] = 'valor-esperado';

        self::assertSame('valor-esperado', gestor_get('teste-chave'));
        self::assertSame('valor-esperado', GestorState::get('teste-chave'));
    }

    public function testGetRetornaPadraoQuandoChaveNaoExiste(): void
    {
        self::assertNull(gestor_get('chave-inexistente'));
        self::assertSame('fallback', gestor_get('chave-inexistente', 'fallback'));
        self::assertSame(1234, gestor_get('chave-inexistente', 1234));
    }

    public function testGetDiferenciaChaveComValorNullDeChaveInexistente(): void
    {
        global $_GESTOR;
        $_GESTOR['chave-nula'] = null;

        // Se a chave existe e seu valor é null, deve retornar null e NÃO o fallback
        self::assertNull(gestor_get('chave-nula', 'fallback-nao-usado'));
    }

    public function testGetSuportaNotacaoPontuada(): void
    {
        global $_GESTOR;
        $_GESTOR['banco'] = [
            'conexao' => [
                'host'  => 'db.local',
                'porta' => 3306,
                'ativo' => true,
            ],
            'driver' => 'mysql',
        ];

        self::assertSame('db.local', gestor_get('banco.conexao.host'));
        self::assertSame(3306, gestor_get('banco.conexao.porta'));
        self::assertTrue(gestor_get('banco.conexao.ativo'));
        self::assertSame('mysql', gestor_get('banco.driver'));
        self::assertSame(['host' => 'db.local', 'porta' => 3306, 'ativo' => true], gestor_get('banco.conexao'));
    }

    public function testGetNotacaoPontuadaFallbackQuandoSegmentoNaoExiste(): void
    {
        global $_GESTOR;
        $_GESTOR['banco'] = ['driver' => 'sqlite'];

        self::assertSame('padrao', gestor_get('banco.conexao.host', 'padrao'));
        self::assertSame(5432, gestor_get('banco.conexao.porta', 5432));
    }

    public function testGetNotacaoPontuadaNaoGeraErroQuandoElementoIntermediarioNaoEArray(): void
    {
        global $_GESTOR;
        $_GESTOR['configuracao'] = 'string-escalar';

        self::assertSame('fallback', gestor_get('configuracao.item.propriedade', 'fallback'));
    }

    public function testGetChaveVaziaRetornaPadrao(): void
    {
        self::assertSame('padrao', gestor_get('', 'padrao'));
    }

    // =========================================================================
    // 2. gestor_set()
    // =========================================================================

    public function testSetAtribuiValorDireto(): void
    {
        global $_GESTOR;

        $sucesso = gestor_set('usuario-ativo', 'admin');

        self::assertTrue($sucesso);
        self::assertSame('admin', $_GESTOR['usuario-ativo']);
        self::assertSame('admin', gestor_get('usuario-ativo'));
    }

    public function testSetSuportaNotacaoPontuadaProfunda(): void
    {
        global $_GESTOR;

        $sucesso = gestor_set('cache.redis.cluster.primario', '10.0.0.1');

        self::assertTrue($sucesso);
        self::assertSame('10.0.0.1', $_GESTOR['cache']['redis']['cluster']['primario']);
        self::assertSame('10.0.0.1', gestor_get('cache.redis.cluster.primario'));
    }

    public function testSetSobrescreveValorExistente(): void
    {
        global $_GESTOR;
        $_GESTOR['contador'] = 10;

        self::assertTrue(gestor_set('contador', 20));
        self::assertSame(20, $_GESTOR['contador']);
        self::assertSame(20, gestor_get('contador'));
    }

    public function testSetChaveVaziaRetornaFalse(): void
    {
        self::assertFalse(gestor_set('', 'valor'));
    }

    // =========================================================================
    // 3. gestor_has()
    // =========================================================================

    public function testHasVerificaExistenciaDireta(): void
    {
        global $_GESTOR;
        $_GESTOR['item-presente'] = 'sim';
        $_GESTOR['item-com-null'] = null;

        self::assertTrue(gestor_has('item-presente'));
        self::assertTrue(gestor_has('item-com-null'));
        self::assertFalse(gestor_has('item-ausente'));
    }

    public function testHasVerificaNotacaoPontuada(): void
    {
        global $_GESTOR;
        $_GESTOR['servicos'] = [
            'email' => [
                'habilitado' => true,
                'fila' => null,
            ],
        ];

        self::assertTrue(gestor_has('servicos'));
        self::assertTrue(gestor_has('servicos.email'));
        self::assertTrue(gestor_has('servicos.email.habilitado'));
        self::assertTrue(gestor_has('servicos.email.fila'));
        self::assertFalse(gestor_has('servicos.email.porta'));
        self::assertFalse(gestor_has('servicos.sms.ativo'));
    }

    public function testHasChaveVaziaRetornaFalse(): void
    {
        self::assertFalse(gestor_has(''));
    }

    // =========================================================================
    // 4. Proteção de Chaves Críticas (CA-3)
    // =========================================================================

    public function testChavesCriticasDefaultEstaoProtegidas(): void
    {
        $chaves = GestorState::getChavesProtegidas();

        self::assertContains('raiz-absoluta', $chaves);
        self::assertContains('url-raiz', $chaves);
        self::assertContains('linguagem-codigo', $chaves);
        self::assertContains('versao-num', $chaves);
    }

    public function testSetBloqueiaMutacaoEmChavesCriticas(): void
    {
        global $_GESTOR;
        $_GESTOR['url-raiz'] = '/app/';
        $_GESTOR['linguagem-codigo'] = 'pt-br';
        $_GESTOR['versao-num'] = '2.10.0';
        $_GESTOR['raiz-absoluta'] = '/var/www/';

        // Tentativa de alterar url-raiz
        $resultadoUrl = gestor_set('url-raiz', 'https://malicioso.com/');
        self::assertFalse($resultadoUrl);
        self::assertSame('/app/', $_GESTOR['url-raiz']);

        // Tentativa de alterar linguagem-codigo
        $resultadoLang = gestor_set('linguagem-codigo', 'en-us');
        self::assertFalse($resultadoLang);
        self::assertSame('pt-br', $_GESTOR['linguagem-codigo']);

        // Tentativa de alterar versao-num
        $resultadoVersao = gestor_set('versao-num', '9.9.9');
        self::assertFalse($resultadoVersao);
        self::assertSame('2.10.0', $_GESTOR['versao-num']);

        // Tentativa de alterar raiz-absoluta
        $resultadoRaiz = gestor_set('raiz-absoluta', '/tmp/');
        self::assertFalse($resultadoRaiz);
        self::assertSame('/var/www/', $_GESTOR['raiz-absoluta']);
    }

    public function testSetBloqueiaMutacaoComNotacaoPontuadaEmChaveProtegida(): void
    {
        global $_GESTOR;
        $_GESTOR['url-raiz'] = '/base/';

        $resultado = gestor_set('url-raiz.subdominio', 'invalido');
        self::assertFalse($resultado);
        self::assertSame('/base/', $_GESTOR['url-raiz']);
    }

    public function testSetRegistraAuditoriaAoBloquearChaveProtegida(): void
    {
        GestorState::limparAuditoria();

        gestor_set('url-raiz', 'https://forjado.com');

        $auditoria = GestorState::getAuditoria();
        self::assertCount(1, $auditoria);
        self::assertSame('url-raiz', $auditoria[0]['chave']);
        self::assertSame('https://forjado.com', $auditoria[0]['valor']);
        self::assertStringContainsString("bloqueada", $auditoria[0]['mensagem']);
    }

    public function testPermiteProtegerEDesprotegerChavesCustomizadas(): void
    {
        global $_GESTOR;
        $_GESTOR['chave-custom'] = 'original';

        // Inicialmente desprotegida
        self::assertFalse(GestorState::isProtegida('chave-custom'));
        self::assertTrue(gestor_set('chave-custom', 'modificado'));
        self::assertSame('modificado', $_GESTOR['chave-custom']);

        // Proteger chave
        GestorState::proteger('chave-custom');
        self::assertTrue(GestorState::isProtegida('chave-custom'));

        // Tentativa agora é barrada
        self::assertFalse(gestor_set('chave-custom', 'ataque'));
        self::assertSame('modificado', $_GESTOR['chave-custom']);

        // Desproteger
        GestorState::desproteger('chave-custom');
        self::assertFalse(GestorState::isProtegida('chave-custom'));
        self::assertTrue(gestor_set('chave-custom', 'atualizado-apos-desbloqueio'));
        self::assertSame('atualizado-apos-desbloqueio', $_GESTOR['chave-custom']);
    }

    // =========================================================================
    // 5. Retrocompatibilidade Integral 100% (CA-2)
    // =========================================================================

    public function testMutacaoLegadaEmGestorRefleteImediatamenteEmGestorGet(): void
    {
        global $_GESTOR;

        // Código legado escreve diretamente no array superglobal
        $_GESTOR['modulo-id'] = 'admin-paginas';
        $_GESTOR['opcao'] = 'editar';

        // Acesso novo lê exatamente o valor modificado
        self::assertSame('admin-paginas', gestor_get('modulo-id'));
        self::assertSame('editar', gestor_get('opcao'));
        self::assertTrue(gestor_has('modulo-id'));
    }

    public function testGestorSetRefleteImediatamenteNoArrayGlobalLegado(): void
    {
        global $_GESTOR;

        gestor_set('novo-token', 'abc-123-xyz');

        // Código legado lê diretamente de $_GESTOR
        self::assertArrayHasKey('novo-token', $_GESTOR);
        self::assertSame('abc-123-xyz', $_GESTOR['novo-token']);
    }

    // =========================================================================
    // 6. gestor_contexto() (CA-1)
    // =========================================================================

    public function testContextoModulo(): void
    {
        global $_GESTOR;
        $_GESTOR['modulo-id'] = 'produtos';
        $_GESTOR['opcao'] = 'listar';
        $_GESTOR['tipo'] = 'pagina';
        $_GESTOR['caminho'] = '/produtos/';
        $_GESTOR['modulo#produtos'] = [
            'nome' => 'Produtos',
            'versao' => '1.2.0',
        ];

        $contexto = gestor_contexto('modulo');

        self::assertSame('produtos', $contexto['id']);
        self::assertSame('listar', $contexto['opcao']);
        self::assertSame('tipo', $contexto['tipo'] === 'pagina' ? 'tipo' : '');
        self::assertSame('pagina', $contexto['tipo']);
        self::assertSame('/produtos/', $contexto['caminho']);
        self::assertSame('Produtos', $contexto['dados']['nome']);
        self::assertSame('1.2.0', $contexto['dados']['versao']);
    }

    public function testContextoUsuario(): void
    {
        global $_GESTOR;
        $_GESTOR['usuario-id'] = 42;
        $_GESTOR['usuario-nome'] = 'Administrador do Sistema';
        $_GESTOR['usuario-perfil-id'] = '1';
        $_GESTOR['usuario-token-id'] = 'tok-8899';
        $_GESTOR['usuario-dados'] = ['email' => 'admin@teste.com'];

        $contexto = gestor_contexto('usuario');

        self::assertSame(42, $contexto['id']);
        self::assertSame('Administrador do Sistema', $contexto['nome']);
        self::assertSame('1', $contexto['perfil_id']);
        self::assertSame('tok-8899', $contexto['token_id']);
        self::assertSame(['email' => 'admin@teste.com'], $contexto['dados']);
    }

    public function testContextoSistema(): void
    {
        global $_GESTOR;
        $_GESTOR['versao-num'] = '3.0.0';
        $_GESTOR['versao'] = '3.0.0';
        $_GESTOR['raiz-absoluta'] = '/var/www/core/';
        $_GESTOR['ROOT_PATH'] = '/var/www/core/';
        $_GESTOR['url-raiz'] = 'https://exemplo.com/';
        $_GESTOR['linguagem-codigo'] = 'pt-br';

        $contexto = gestor_contexto('sistema');

        self::assertSame('3.0.0', $contexto['versao_num']);
        self::assertSame('3.0.0', $contexto['versao']);
        self::assertSame('/var/www/core/', $contexto['raiz_absoluta']);
        self::assertSame('/var/www/core/', $contexto['root_path']);
        self::assertSame('https://exemplo.com/', $contexto['url_raiz']);
        self::assertSame('pt-br', $contexto['linguagem']);
    }

    public function testContextoEscopoGenericoPorChaveArray(): void
    {
        global $_GESTOR;
        $_GESTOR['sessao'] = [
            'id' => 'sess-123',
            'expira' => 3600,
        ];

        $contexto = gestor_contexto('sessao');

        self::assertSame('sess-123', $contexto['id']);
        self::assertSame(3600, $contexto['expira']);
    }

    public function testContextoEscopoGenericoPorChavesPrefixadas(): void
    {
        global $_GESTOR;
        $_GESTOR['api-timeout'] = 30;
        $_GESTOR['api-tentativas'] = 3;
        $_GESTOR['api_endpoint'] = 'https://api.exemplo.com';

        $contexto = gestor_contexto('api');

        self::assertSame(30, $contexto['timeout']);
        self::assertSame(3, $contexto['tentativas']);
        self::assertSame('https://api.exemplo.com', $contexto['endpoint']);
    }

    public function testContextoEscopoInexistenteRetornaArrayVazio(): void
    {
        self::assertSame([], gestor_contexto('escopo-totalmente-inexistente'));
        self::assertSame([], gestor_contexto(''));
    }

    // =========================================================================
    // 7. Singleton e Instanciação
    // =========================================================================

    public function testSingletonRetornaMesmaInstancia(): void
    {
        $instancia1 = GestorState::getInstance();
        $instancia2 = GestorState::getInstance();

        self::assertSame($instancia1, $instancia2);
    }
}
