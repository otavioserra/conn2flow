<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DashboardWidgetStylesReq238Test extends TestCase
{
    public function testIsolatedWidgetReceivesTemplateAuthoredAndCompiledStyles(): void
    {
        $source = file_get_contents(getenv('C2F_DASHBOARD_PHP_SOURCE') ?: CONN2FLOW_GESTOR_ROOT . '/modulos/dashboard/dashboard.php');
        preg_match('/function dashboard_ajax_widget_render\(\)\{[\s\S]*?\n\}/', $source, $function);
        $script = <<<'PHP'
<?php
$_GESTOR=['linguagem-codigo'=>'pt-br'];
$_REQUEST=['widget_id'=>'menus','registro_id'=>'docs-sidebar'];
function dashboard_widget_definicao($id){return ['tabela'=>'menus'];}
function banco_escape_field($v){return addslashes($v);}
function banco_campo_existe($field,$table){return true;}
function banco_select($p){
 if($p['tabela']==='layouts')return ['css_precompiled'=>':root{--spacing:.25rem}'];
 if($p['tabela']==='templates'){$GLOBALS['templateQuery']=$p['extra'];return ['css_precompiled'=>'.search-icon{width:16px}'];}
 return ['id'=>'docs-sidebar','fields_schema'=>'{"template_id":"docs-sidebar"}','css_compiled'=>''];
}
function gestor_incluir_biblioteca($id){}
function gestor_modulos_dados($id){return [];}
function gestor_get($id){return $GLOBALS['_GESTOR'][$id] ?? null;}
function gestor_set($id,$value){$GLOBALS['_GESTOR'][$id]=$value;}
function html_editor_widget_renderizar($sig){
 $GLOBALS['_GESTOR']['css-precompiled'][]='<style>.offline{color:blue}</style>';
 $GLOBALS['_GESTOR']['css-compiled'][]='<style>.online{color:red}</style>';
 $GLOBALS['_GESTOR']['html-extra-head'][]='<meta name="widget-head">';
 return ['html'=>'<div>Widget</div>','css'=>'<style>.authored{padding:8px}</style>'];
}
PHP;
        $script .= $function[0] . '\ndashboard_ajax_widget_render(); echo json_encode([$_GESTOR["ajax-json"],$templateQuery]);';
        $script = str_replace('\ndashboard_ajax_widget_render()', "\ndashboard_ajax_widget_render()", $script);
        $file = tempnam(sys_get_temp_dir(), 'req238-style-');
        try {
            file_put_contents($file, $script);
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file), $output, $exit);
            self::assertSame(0, $exit);
            [$result, $query] = json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('Ok', $result['status']);
            foreach (['.offline', '.online', '.authored', '.search-icon', 'widget-head', '--spacing'] as $marker) self::assertStringContainsString($marker, $result['data']['css']);
            self::assertStringContainsString("target='menus'", $query);
            self::assertStringContainsString("language='pt-br'", $query);
        } finally { unlink($file); }
    }
}
