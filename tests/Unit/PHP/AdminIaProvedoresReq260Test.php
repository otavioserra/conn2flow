<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-260 — cadastro de servidores de IA com os quatro provedores e o envio do editor pela camada de provedores.
 */
final class AdminIaProvedoresReq260Test extends TestCase
{
    private const MODULO = CONN2FLOW_GESTOR_ROOT . '/modulos/admin-ia/';
    private const TIPOS = ['gemini', 'anthropic', 'openai', 'openai-compativel'];

    private function ler(string $caminho): string
    {
        return (string)file_get_contents($caminho);
    }

    public function testTelasDeInclusaoEEdicaoTrazemOsQuatroTiposEOsCamposDoProvedor(): void
    {
        foreach (['pt-br', 'en'] as $lingua) {
            foreach (['admin-ia-adicionar', 'admin-ia-editar'] as $pagina) {
                $html = $this->ler(self::MODULO . "resources/$lingua/pages/$pagina/$pagina.html");
                foreach (self::TIPOS as $tipo) {
                    $this->assertStringContainsString('<option value="' . $tipo . '"', $html, "$lingua/$pagina");
                }
                foreach (['url_base', 'modelo', 'modelo_imagem'] as $campo) {
                    $this->assertSame(1, substr_count($html, 'name="' . $campo . '"'), "$lingua/$pagina/$campo");
                }
                $this->assertStringContainsString('data-provedores="[[provedores-json]]"', $html);
                $this->assertSame(1, substr_count($html, ' selected') + substr_count($html, '[[sel-gemini]]'), "$lingua/$pagina: um tipo marcado");
            }
            $editar = $this->ler(self::MODULO . "resources/$lingua/pages/admin-ia-editar/admin-ia-editar.html");
            foreach (['[[sel-gemini]]', '[[sel-anthropic]]', '[[sel-openai]]', '[[sel-compativel]]', '[[url-base]]', '[[modelo]]', '[[modelo-imagem]]'] as $marcador) {
                $this->assertSame(1, substr_count($editar, $marcador), "$lingua: $marcador");
            }
        }
    }

    public function testVariaveisNovasExistemNasDuasLinguasEAsTelasNaoUsamVariavelInexistente(): void
    {
        $modulo = json_decode($this->ler(self::MODULO . 'admin-ia.json'), true);
        $novas = ['ui-provider-anthropic', 'ui-provider-openai', 'ui-provider-compatible', 'ui-base-url', 'ui-base-url-hint', 'ui-model', 'ui-model-hint',
            'ui-image-model', 'ui-image-model-hint', 'msg-type-invalid', 'msg-base-url-invalid', 'msg-base-url-required', 'msg-model-invalid',
            'msg-model-required', 'msg-key-missing'];
        foreach (['pt-br', 'en'] as $lingua) {
            $ids = array_column($modulo['resources'][$lingua]['variables'], 'id');
            $this->assertSame([], array_values(array_diff($novas, $ids)), $lingua);
            $this->assertSame(count($ids), count(array_unique($ids)), "$lingua: variável repetida");
            foreach (['admin-ia-adicionar', 'admin-ia-editar', 'admin-ia-listar'] as $pagina) {
                preg_match_all('/@\[\[([a-z0-9\-]+)\]\]@/', $this->ler(self::MODULO . "resources/$lingua/pages/$pagina/$pagina.html"), $usadas);
                $this->assertSame([], array_values(array_diff(array_unique($usadas[1]), $ids)), "$lingua/$pagina");
            }
        }

        // As mensagens que o PHP pede existem.
        preg_match_all("/'id' => '((?:msg|ui)-[a-z\-]+)'/", $this->ler(self::MODULO . 'admin-ia.php'), $pedidas);
        $this->assertSame([], array_values(array_diff(array_unique($pedidas[1]), array_column($modulo['resources']['pt-br']['variables'], 'id'))));
    }

    public function testChaveNaoVaiMaisNoEndereco(): void
    {
        $modulo = json_decode($this->ler(self::MODULO . 'admin-ia.json'), true);
        $this->assertStringNotContainsString('key=', $modulo['apis']['gemini']['urlGenerateContent']);

        foreach ([self::MODULO . 'admin-ia.php', CONN2FLOW_GESTOR_ROOT . '/bibliotecas/ia.php', CONN2FLOW_GESTOR_ROOT . '/bibliotecas/ia-provedores.php'] as $arquivo) {
            $codigo = $this->ler($arquivo);
            $this->assertStringNotContainsString('?key=', $codigo, $arquivo);
            $this->assertStringNotContainsString('{API_KEY}', $codigo, $arquivo);
        }
    }

    public function testCadastroETesteUsamACamadaDeProvedores(): void
    {
        $php = $this->ler(self::MODULO . 'admin-ia.php');

        $this->assertStringNotContainsString('curl_', $php);
        $this->assertStringNotContainsString('admin_ia_testar_gemini', $php);
        $this->assertStringContainsString('ia_provedor_testar($servidor_ia)', $php);
        $this->assertSame(2, substr_count($php, 'admin_ia_campos_provedor($erro_provedor)'));
        foreach (['url_base', 'modelo', 'modelo_imagem'] as $campo) {
            $this->assertStringContainsString("banco_insert_name_campo('$campo',\$campos_provedor['$campo'])", $php);
            $this->assertStringContainsString("banco_update_campo('$campo',\$campos_provedor['$campo'])", $php);
        }
        // Identificador vindo do pedido nunca entra cru na consulta.
        $this->assertDoesNotMatchRegularExpression("/id_servidores_ia = ' \\. \\\$id\\b/", $php);
    }

    public function testEnvioDoEditorUsaOProvedorDoServidor(): void
    {
        $ia = $this->ler(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/ia.php');
        $inicio = strpos($ia, 'function ia_enviar_prompt(');
        $funcao = substr($ia, $inicio, strpos($ia, 'function ia_processar_retorno(') - $inicio);

        $this->assertStringContainsString("gestor_incluir_biblioteca('ia-provedores')", $funcao);
        $this->assertStringContainsString('ia_provedor_gerar_texto($servidorIA, $pedido)', $funcao);
        $this->assertStringNotContainsString('curl_', $funcao);
        foreach (['texto_gerado', 'modelo_usado', 'tokens_entrada', 'tokens_saida', 'tokens_total', 'resposta_completa'] as $chave) {
            $this->assertStringContainsString("'$chave' =>", $funcao);
        }

        $config = $this->ler(CONN2FLOW_GESTOR_ROOT . '/config.php');
        $this->assertStringContainsString("'ia-provedores' => Array('ia-provedores.php')", $config);
    }

    public function testMigrationAcrescentaAsColunas(): void
    {
        $migration = $this->ler(CONN2FLOW_GESTOR_ROOT . '/db/migrations/20261007130000_add_provider_fields_to_servidores_ia.php');
        foreach (['url_base', 'modelo', 'modelo_imagem'] as $coluna) {
            $this->assertStringContainsString("'$coluna'", $migration);
        }
        $this->assertStringContainsString('hasColumn', $migration);
        $this->assertStringContainsString("'null' => true", $migration);
    }

    public function testCamposDoProvedorSaoConferidosAntesDeGravar(): void
    {
        $stubs = <<<'PHP'
<?php
$_GESTOR = [];
function gestor_incluir_biblioteca($b){ require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/' . $b . '.php'; }
function gestor_variaveis($p){ return 'msg:' . $p['id']; }
PHP;
        $arquivo = tempnam(sys_get_temp_dir(), 'req260');
        $codigo = $this->ler(self::MODULO . 'admin-ia.php');
        $inicio = strpos($codigo, 'function admin_ia_campos_provedor(');
        $funcao = substr($codigo, $inicio, strpos($codigo, 'function admin_ia_listar(') - $inicio);
        $casos = var_export([
            'gemini-vazio' => ['tipo' => 'gemini'],
            'openai-completo' => ['tipo' => 'openai', 'url_base' => 'https://proxy.exemplo.com/v1/', 'modelo' => 'gpt-x', 'modelo_imagem' => 'gpt-image-1'],
            'tipo-invalido' => ['tipo' => 'outro'],
            'endereco-invalido' => ['tipo' => 'openai', 'url_base' => 'https://x.com/v1?key=1'],
            'modelo-invalido' => ['tipo' => 'anthropic', 'modelo' => 'claude com espaço'],
            'compativel-sem-endereco' => ['tipo' => 'openai-compativel', 'modelo' => 'llama3'],
            'compativel-sem-modelo' => ['tipo' => 'openai-compativel', 'url_base' => 'http://localhost:11434/v1'],
            'compativel-ok' => ['tipo' => 'openai-compativel', 'url_base' => 'http://localhost:11434/v1', 'modelo' => 'llama3'],
        ], true);
        file_put_contents($arquivo, $stubs . "\ndefine('CONN2FLOW_GESTOR_ROOT', " . var_export(CONN2FLOW_GESTOR_ROOT, true) . ");\n" . $funcao . "\n"
            . '$saida = []; foreach(' . $casos . ' as $nome => $pedido){ $_REQUEST = $pedido; $erro = ""; $r = admin_ia_campos_provedor($erro); $saida[$nome] = [$r, $erro]; }'
            . "\necho json_encode(\$saida);\n");
        $linhas = [];
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($arquivo), $linhas);
        @unlink($arquivo);
        $r = json_decode((string)end($linhas), true);

        $this->assertIsArray($r);
        $this->assertSame(['tipo' => 'gemini', 'url_base' => '', 'modelo' => '', 'modelo_imagem' => ''], $r['gemini-vazio'][0]);
        $this->assertSame(['tipo' => 'openai', 'url_base' => 'https://proxy.exemplo.com/v1', 'modelo' => 'gpt-x', 'modelo_imagem' => 'gpt-image-1'], $r['openai-completo'][0]);
        $this->assertSame([null, 'msg:msg-type-invalid'], $r['tipo-invalido']);
        $this->assertSame([null, 'msg:msg-base-url-invalid'], $r['endereco-invalido']);
        $this->assertSame([null, 'msg:msg-model-invalid'], $r['modelo-invalido']);
        $this->assertSame([null, 'msg:msg-base-url-required'], $r['compativel-sem-endereco']);
        $this->assertSame([null, 'msg:msg-model-required'], $r['compativel-sem-modelo']);
        $this->assertSame('llama3', $r['compativel-ok'][0]['modelo']);
    }
}
