<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * REQ-262 — pontos de extensão do uso de IA na camada de provedores: `pedido.autorizar` (pode recusar antes da
 * chamada) e `pedido.concluido` (recebe o consumo). Roda em processo separado, com os hooks simulados e sem rede.
 */
final class IaProvedoresUsoReq262Test extends TestCase
{
    private function rodar(string $corpo, bool $comHooks = true): array
    {
        $hooks = $comHooks ? '
$GLOBALS["autorizar"] = []; $GLOBALS["concluidos"] = []; $GLOBALS["recusa"] = ""; $GLOBALS["quebra"] = false;
function hook_apply_filters(string $ns, string $evento, mixed $valor, mixed ...$args): mixed { if($GLOBALS["quebra"]) throw new RuntimeException("callback quebrado"); $GLOBALS["autorizar"][] = [$ns, $evento, $valor, $args[0]]; return $GLOBALS["recusa"] !== "" ? $GLOBALS["recusa"] : $valor; }
function hook_do_action(string $ns, string $evento, mixed ...$args): void { if($GLOBALS["quebra"]) throw new RuntimeException("callback quebrado"); $GLOBALS["concluidos"][] = [$ns, $evento, $args[0]]; }
' : '';
        $script = tempnam(sys_get_temp_dir(), 'req262');
        file_put_contents($script, '<?php
ini_set("error_log", ' . var_export($script . '.log', true) . ');
' . $hooks . '
require ' . var_export(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/ia-provedores.php', true) . ';
$fechado = ["tipo" => "openai-compativel", "chave" => "sk-chave-de-teste-0123456789", "url_base" => "http://127.0.0.1:9/v1", "modelo" => "modelo-x", "modelo_imagem" => "imagem-x"];
$pedido = ["mensagens" => [["papel" => "user", "texto" => "conteudo secreto do pedido"]], "recurso" => "AI-Fields", "referencia" => "products/name"];
$r = [];
' . $corpo . '
echo json_encode($r);
');
        $linhas = [];
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script), $linhas);
        @unlink($script);
        @unlink($script . '.log');
        $r = json_decode((string)end($linhas), true);
        $this->assertIsArray($r, implode("\n", $linhas));
        return $r;
    }

    public function testContextoIdentificaOPedidoSemConteudoNemChave(): void
    {
        $r = $this->rodar('
$r["texto"] = ia_provedor_contexto("texto", $fechado, $pedido, "modelo-x");
$r["imagem"] = ia_provedor_contexto("imagem", ["tipo" => "gemini"], ["recurso" => "../x y", "referencia" => str_repeat("a", 300) . "\n"], "m");
$r["vazio"] = ia_provedor_contexto("outro", [], [], "");
');
        $this->assertSame(['tipo' => 'texto', 'provedor' => 'openai-compativel', 'modelo' => 'modelo-x', 'recurso' => 'ai-fields', 'referencia' => 'products/name'], $r['texto']);
        $this->assertSame(['imagem', 'gemini', '', 120], [$r['imagem']['tipo'], $r['imagem']['provedor'], $r['imagem']['recurso'], strlen($r['imagem']['referencia'])]);
        $this->assertSame(['tipo' => 'texto', 'provedor' => '', 'modelo' => '', 'recurso' => '', 'referencia' => ''], $r['vazio']);
        $this->assertStringNotContainsString('secreto', json_encode($r));
        $this->assertStringNotContainsString('sk-chave', json_encode($r));
    }

    public function testRecusaImpedeOPedidoEDevolveAMensagem(): void
    {
        $r = $this->rodar('
$GLOBALS["recusa"] = "Seus créditos de IA acabaram.";
$r["texto"] = ia_provedor_gerar_texto($fechado, $pedido, 5);
$r["imagem"] = ia_provedor_gerar_imagem($fechado, ["prompt" => "um farol", "recurso" => "ai-images"], 5);
$r["autorizar"] = $GLOBALS["autorizar"];
$r["concluidos"] = count($GLOBALS["concluidos"]);
');
        $this->assertSame(['error', 'Seus créditos de IA acabaram.', true, 'modelo-x'], [$r['texto']['status'], $r['texto']['message'], $r['texto']['bloqueado'], $r['texto']['modelo']]);
        $this->assertSame(['error', true, 'imagem-x'], [$r['imagem']['status'], $r['imagem']['bloqueado'], $r['imagem']['modelo']]);
        $this->assertSame(['ia-provedores', 'pedido.autorizar', ''], array_slice($r['autorizar'][0], 0, 3));
        $this->assertSame(['texto', 'ai-fields', 'products/name'], [$r['autorizar'][0][3]['tipo'], $r['autorizar'][0][3]['recurso'], $r['autorizar'][0][3]['referencia']]);
        $this->assertSame(['imagem', 'ai-images'], [$r['autorizar'][1][3]['tipo'], $r['autorizar'][1][3]['recurso']]);
        // Pedido recusado não foi ao provedor: não há o que concluir.
        $this->assertSame(0, $r['concluidos']);
    }

    public function testConclusaoRecebeOResultadoTambemQuandoOProvedorFalha(): void
    {
        $r = $this->rodar('
$r["resposta"] = ia_provedor_gerar_texto($fechado, $pedido, 5);
$r["concluidos"] = $GLOBALS["concluidos"];
ia_provedor_concluir(ia_provedor_contexto("texto", $fechado, $pedido, "m"), ["status" => "success", "tokens_entrada" => 120, "tokens_saida" => -5]);
ia_provedor_concluir(ia_provedor_contexto("imagem", $fechado, $pedido, "m"), ["status" => "success", "imagens" => 2]);
$r["sucesso"] = $GLOBALS["concluidos"][1][2];
$r["imagem"] = $GLOBALS["concluidos"][2][2];
$r["teste"] = null;
ia_provedor_testar($fechado);
$r["recurso-do-teste"] = end($GLOBALS["autorizar"])[3]["recurso"];
');
        $this->assertSame('error', $r['resposta']['status']);
        $this->assertArrayNotHasKey('bloqueado', $r['resposta']);
        $this->assertCount(1, $r['concluidos']);
        $this->assertSame(['ia-provedores', 'pedido.concluido'], array_slice($r['concluidos'][0], 0, 2));
        $this->assertSame(['error', 0, 0, 0, 'ai-fields'], [$r['concluidos'][0][2]['status'], $r['concluidos'][0][2]['tokens_entrada'], $r['concluidos'][0][2]['tokens_saida'], $r['concluidos'][0][2]['imagens'], $r['concluidos'][0][2]['recurso']]);
        // Contagem negativa vinda do provedor não vira crédito: é zerada.
        $this->assertSame(['success', 120, 0, 0], [$r['sucesso']['status'], $r['sucesso']['tokens_entrada'], $r['sucesso']['tokens_saida'], $r['sucesso']['imagens']]);
        $this->assertSame(['imagem', 2], [$r['imagem']['tipo'], $r['imagem']['imagens']]);
        $this->assertSame('teste-conexao', $r['recurso-do-teste']);
    }

    public function testErroNoCallbackNaoDerrubaOPedidoESemHooksNadaMuda(): void
    {
        $r = $this->rodar('
$GLOBALS["quebra"] = true;
$r["autorizar"] = ia_provedor_autorizar(ia_provedor_contexto("texto", $fechado, $pedido, "m"));
ia_provedor_concluir(ia_provedor_contexto("texto", $fechado, $pedido, "m"), ["status" => "success"]);
$r["resposta"] = ia_provedor_gerar_texto($fechado, $pedido, 5);
');
        $this->assertSame('', $r['autorizar']);
        $this->assertSame('error', $r['resposta']['status']);
        $this->assertArrayNotHasKey('bloqueado', $r['resposta']);
        $this->assertStringContainsString('comunicação', $r['resposta']['message']);

        $sem = $this->rodar('
$r["disponiveis"] = ia_provedor_hooks_disponiveis();
$r["autorizar"] = ia_provedor_autorizar(ia_provedor_contexto("texto", $fechado, $pedido, "m"));
ia_provedor_concluir(ia_provedor_contexto("texto", $fechado, $pedido, "m"), ["status" => "success"]);
$r["resposta"] = ia_provedor_gerar_texto($fechado, $pedido, 5)["status"];
', false);
        $this->assertSame([false, '', 'error'], [$sem['disponiveis'], $sem['autorizar'], $sem['resposta']]);
    }

    public function testEditorIdentificaORecursoEADocumentacaoCitaOsPontos(): void
    {
        $ia = (string)file_get_contents(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/ia.php');
        $this->assertStringContainsString("'recurso' => 'editor-html',", $ia);
        foreach (['pt-br', 'en'] as $idioma) {
            $doc = (string)file_get_contents(dirname(CONN2FLOW_GESTOR_ROOT) . "/ai-workspace/$idioma/docs/concepts/hooks.md");
            $this->assertStringContainsString('| `ia-provedores` | `pedido.autorizar` | filter |', $doc, $idioma);
            $this->assertStringContainsString('| `ia-provedores` | `pedido.concluido` | action |', $doc, $idioma);
        }
    }
}
