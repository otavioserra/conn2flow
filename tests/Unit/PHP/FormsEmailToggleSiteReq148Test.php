<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * conn2flow-site REQ-148: formulário pode registrar a submissão sem disparar o e-mail genérico
 * ("Novo Formulário Recebido"), por parâmetro do chamador ou pelo schema.
 */
final class FormsEmailToggleSiteReq148Test extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once CONN2FLOW_GESTOR_ROOT . '/bibliotecas/formulario.php';
    }

    public function testEnvioPadraoContinuaLigado(): void
    {
        self::assertTrue(formulario_email_habilitado());
        self::assertTrue(formulario_email_habilitado(null, []));
        self::assertTrue(formulario_email_habilitado(true, ['enabled' => true]));
        self::assertTrue(formulario_email_habilitado(null, ['recipients' => 'a@example.invalid']));
        // Só o booleano false desliga: valores "falsy" do schema não mudam o comportamento atual.
        self::assertTrue(formulario_email_habilitado(0, ['enabled' => 0]));
        self::assertTrue(formulario_email_habilitado('', ['enabled' => 'false']));
    }

    public function testParametroOuSchemaDesligamOEnvio(): void
    {
        self::assertFalse(formulario_email_habilitado(false));
        self::assertFalse(formulario_email_habilitado(false, ['enabled' => true]));
        self::assertFalse(formulario_email_habilitado(null, ['enabled' => false]));
    }

    public function testSubmissaoGravadaAntesEEmailPulado(): void
    {
        $php = (string) file_get_contents(CONN2FLOW_GESTOR_ROOT . '/bibliotecas/formulario.php');
        $inicio = strpos($php, 'function formulario_processador(');
        $corpo = substr($php, $inicio, strpos($php, 'function formulario_acesso_verificar(') - $inicio);
        $insert = strpos($corpo, "'forms_submissions');");
        $guarda = strpos($corpo, 'formulario_email_habilitado($enviarEmail ?? null, $emailData)');
        $envio = strpos($corpo, 'comunicacao_email(Array(');
        self::assertNotFalse($guarda);
        self::assertLessThan($guarda, $insert, 'a submissão fica registrada mesmo sem e-mail');
        self::assertLessThan($envio, $guarda, 'a guarda vem antes do envio');
        self::assertStringContainsString("'email_status' => 'email-disabled'", $corpo);
    }
}
