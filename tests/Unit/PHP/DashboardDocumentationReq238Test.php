<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DashboardDocumentationReq238Test extends TestCase
{
    public function testProjectModulesWithoutPluginMarkerUseInternalGuideWhileCoreKeepsPublicDocs(): void
    {
        $source = file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/dashboard/dashboard.php');
        $start = strpos($source, 'function dashboard_modulo_documentacao(');
        $end = strpos($source, "\n/**", $start);
        $function = substr($source, $start, $end - $start);
        $metadata = json_decode(file_get_contents(CONN2FLOW_GESTOR_ROOT . '/modulos/dashboard/dashboard.json'), true);
        $script = '<?php function gestor_variaveis($params){return $params["id"];} ' . $function
            . ' $_GESTOR=["modulo-id"=>"dashboard","linguagem-codigo"=>"en","url-raiz"=>"/admin/en/","modulo#dashboard"=>["core_modules"=>'
            . var_export($metadata['core_modules'], true) . ']];'
            . ' foreach(["dashboard","menus","social-connections","host-manager","3d-catalog"] as $id){$results[$id]=dashboard_modulo_documentacao(["id"=>$id,"plugin"=>null]);} echo json_encode($results);';
        $file = tempnam(sys_get_temp_dir(), 'req238-docs-');
        try {
            file_put_contents($file, $script);
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file), $output, $exit);
            self::assertSame(0, $exit);
            $results = json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR);
            foreach (['dashboard', 'menus'] as $id) self::assertSame('https://conn2flow.com/docs/reference/modules/' . $id . '/', $results[$id]['docs_link']);
            foreach (['social-connections', 'host-manager', '3d-catalog'] as $id) {
                self::assertSame('/admin/en/documentation/' . $id . '/', $results[$id]['docs_link']);
                self::assertSame($results[$id]['docs_link'], $results[$id]['manual_link']);
            }
        } finally { unlink($file); }
    }
}
