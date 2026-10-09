<?php
ob_start();
/** Pure boundary tests with DB/runtime doubles; no installation database is accessed. */
$writes = [];
$stored = [];
$available = true;
$writeFails = false;
$pages = [
    ['id'=>'pages','nome'=>'Pages','caminho'=>'admin-paginas/','modulo'=>'pages','opcao'=>'listar','raiz'=>1,'icone_tailwind'=>'files'],
    ['id'=>'new-page','nome'=>'New page','caminho'=>'admin-paginas/adicionar/','modulo'=>'pages','opcao'=>'adicionar','raiz'=>null,'icone_tailwind'=>'files'],
    ['id'=>'forbidden','nome'=>'Forbidden','caminho'=>'secret/','modulo'=>'secret','opcao'=>'listar','raiz'=>1,'icone_tailwind'=>'star'],
    ['id'=>'delete','nome'=>'Delete','caminho'=>'pages/excluir/','modulo'=>'pages','opcao'=>'excluir','raiz'=>null,'icone_tailwind'=>'star'],
    ['id'=>'external','nome'=>'External','caminho'=>'//evil.test/','modulo'=>'pages','opcao'=>'listar','raiz'=>1,'icone_tailwind'=>'star'],
    ['id'=>'profile','nome'=>'Profile','caminho'=>'perfil-usuario/','modulo'=>'perfil-usuario','opcao'=>'editar','raiz'=>1,'icone_tailwind'=>'user'],
    ['id'=>'dashboard','nome'=>'Dashboard','caminho'=>'dashboard/','modulo'=>'dashboard','opcao'=>'inicio','raiz'=>1,'icone_tailwind'=>'not valid'],
];
function banco_escape_field($s){ return addslashes($s); }
function banco_campo_existe($c,$t){ global $available; return $t === 'modulos' || $available; }
function gestor_permissao_modulo($alert,$module){ return $module !== 'secret'; }
function gestor_acesso($action,$module){ return false; }
function gestor_incluir_biblioteca($lib){}
function interface_ajax_iniciar(){}
function interface_ajax_finalizar(){}
function gestor_variaveis($params){ return 'translated-error'; }
function gestor_usuario(){ return ['id_usuarios'=>7,'nome'=>'<img src=x onerror=alert(1)>','email'=>'" onmouseover="alert(1)','perfil_nome'=>'<script>']; }
function gestor_componente($params){ return file_get_contents(__DIR__.'/../../gestor/resources/pt-br/components/admin-topbar-tailwind/admin-topbar-tailwind.html'); }
function recursos_tag_js($id,$version){ return $id.'?v='.$version; }
function gestor_asset_version(){ return 'test-version'; }
function gestor_pagina_javascript_incluir($script){ global $scripts; $scripts[]=$script; }
function banco_sql($query){
    global $pages,$stored;
    if(strpos($query,'FROM paginas') !== false) return $pages;
    preg_match('/id_usuarios=(\d+)/', $query, $m);
    return array_map(fn($id)=>['pagina_id'=>$id], $stored[(int)$m[1]] ?? []);
}
function banco_query($query){
    global $writes,$stored,$writeFails;
    $writes[] = $query;
    if($writeFails) return false;
    if(preg_match("/VALUES \((\d+), '([^']+)'\)/",$query,$m)){
        $stored[(int)$m[1]] = array_values(array_unique(array_merge($stored[(int)$m[1]] ?? [],[$m[2]])));
    } elseif(preg_match("/id_usuarios=(\d+) AND pagina_id='([^']+)'/",$query,$m)){
        $stored[(int)$m[1]] = array_values(array_diff($stored[(int)$m[1]] ?? [],[$m[2]]));
    }
    return true;
}
require __DIR__.'/../../gestor/bibliotecas/admin-topbar.php';
$_GESTOR=['linguagem-codigo'=>'pt-br','url-raiz'=>'/tenant/','usuario-id'=>7];
$_SERVER['REQUEST_METHOD']='POST';
$checks=0;
function check($condition,$name){ global $checks; if(!$condition) throw new RuntimeException($name); $checks++; echo "OK $name\n"; }
function action($type,$id){ global $_GESTOR; $_GESTOR['ajax-opcao']='admin-topbar-'.$type; $_POST=['paginaId'=>$id,'id_usuarios'=>99]; http_response_code(200); admin_topbar_ajax(); }
foreach(['https://evil.test/','//evil.test/','a/../b/','a/./b/','a\\b/','a?delete=1','a/#fragment','a/<id>/','a/[[id]]/'] as $path) check(!admin_topbar_caminho_seguro($path),'reject path '.$path);
check(admin_topbar_caminho_seguro('admin-paginas/adicionar/'),'allow safe action path');
check(admin_topbar_escape('<img src=x onerror=alert(1)>"') === '&lt;img src=x onerror=alert(1)&gt;&quot;','escape identity');
$catalog=admin_topbar_catalogo();
check(array_keys($catalog) === ['pages','profile','dashboard'],'filter forbidden module, operation, mutation and external URL');
check($catalog['dashboard']['icone'] === 'star','invalid icon fallback');
action('adicionar','pages'); check($_GESTOR['ajax-json']['status']==='Ok' && $stored[7]===['pages'] && empty($stored[99]),'owner from authenticated session');
action('adicionar','pages'); check(count($stored[7])===1,'idempotent add');
$_GESTOR['usuario-id']=8; action('adicionar','profile'); check($stored[7]===['pages'] && $stored[8]===['profile'],'separate accounts');
action('remover','pages'); check($stored[7]===['pages'],'cannot remove other account favorite');
$n=count($writes); action('adicionar','forbidden'); check(http_response_code()===403 && count($writes)===$n,'deny unavailable target');
action('adicionar',['pages']); check(http_response_code()===400 && count($writes)===$n,'reject array payload');
action('unknown','pages'); check(http_response_code()===400 && count($writes)===$n,'reject unknown action');
$_SERVER['REQUEST_METHOD']='GET'; action('adicionar','pages'); check(http_response_code()===405 && count($writes)===$n,'reject GET mutation');
$_SERVER['REQUEST_METHOD']='POST'; $_GESTOR['usuario-id']=0; action('adicionar','pages'); check(http_response_code()===401 && count($writes)===$n,'reject anonymous');
$_GESTOR['usuario-id']=7; $available=false; action('adicionar','pages'); check(http_response_code()===503 && count($writes)===$n,'missing migration is explicit');
$available=true; $writeFails=true; action('remover','pages'); check(http_response_code()===500 && $_GESTOR['ajax-json']['status']==='Erro','failed write never reports success');
$writeFails=false; action('remover','pages'); check($stored[7]===[] && $stored[8]===['profile'],'remove scoped to account');
$html=admin_topbar_renderizar();
check(strpos($html,'<img src=x onerror=alert(1)>')===false && strpos($html,'&lt;img src=x onerror=alert(1)&gt;')!==false,'render escapes user identity');
preg_match('/data-topbar-state="([^"]+)"/',$html,$match);
$state=json_decode(html_entity_decode($match[1],ENT_QUOTES,'UTF-8'),true);
check($state['catalogo'][0]['url']==='/tenant/admin-paginas/' && $state['disponivel']===true,'state safely round trips from HTML attribute');
check($scripts===['global/admin-topbar.js?v=test-version'],'script uses asset version');
echo "$checks checks passed\n";
ob_end_flush();
