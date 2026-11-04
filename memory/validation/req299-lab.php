<?php
/** REQ-299: sonda/fixtures isoladas no Lab; entrada é definida pelo runner Node. */
if(PHP_SAPI !== 'cli') exit(1);
chdir('/home/snapphoton/web/snapphoton.local/conn2flow-gestor');
$_SERVER['SERVER_NAME'] = 'snapphoton.local';
$_SERVER['REQUEST_URI'] = '/';
$_INDEX = ['sistemas-dir' => getcwd().'/'];
require 'config.php';
foreach(['banco', 'gestor', 'usuario', 'seguranca', 'ip'] as $lib) require_once 'bibliotecas/'.$lib.'.php';
banco_conectar();
$_GESTOR['linguagem-codigo'] = 'pt-br';
$modo = $entrada['modo'];
$prefixo = 'req299-e2e';
function req299_linhas($sql) {
    $r = banco_query($sql);
    if($r === false) throw new RuntimeException('SQL refused');
    $rows = [];
    while($row = mysqli_fetch_assoc($r)) $rows[] = $row;
    return $rows;
}
function req299_exec($sql) {
    if(banco_query($sql) === false) throw new RuntimeException('SQL refused');
}
if($modo === 'publish'){
    // Executa o publicador nativo com a autoria já instalada; não lê o lumix em andamento.
    $runtime = getcwd();
    $virtual = $runtime.'/temp/req299-native-publish';
    if(!is_dir($virtual) && !mkdir($virtual, 0755, true)) throw new RuntimeException('Cannot create publisher root');
    if(file_exists($virtual.'/gestor') || is_link($virtual.'/gestor')) throw new RuntimeException('Publisher root occupied');
    if(!symlink($runtime, $virtual.'/gestor')) throw new RuntimeException('Cannot link publisher root');
    spl_autoload_register(function($class) use ($runtime){
        $prefix = 'Conn2Flow\\Cli\\';
        if(str_starts_with($class, $prefix)) require_once $runtime.'/cli/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    });
    ob_start();
    try {
        $code = (new Conn2Flow\Cli\Commands\AssetsPublishCommand($virtual))->execute(
            new Conn2Flow\Cli\Console\Input(['assets:publish', '--public=/home/snapphoton/web/snapphoton.local/public_html']),
            new Conn2Flow\Cli\Console\Output()
        );
        $log = ob_get_clean();
        $saida = ['code'=>$code, 'log'=>$log];
    } finally {
        unlink($virtual.'/gestor');
        rmdir($virtual);
    }
} elseif($modo === 'snapshot'){
    $saida = [
        'usuarios' => req299_linhas('SELECT status,COUNT(*) AS total FROM usuarios GROUP BY status'),
        'perfis' => req299_linhas('SELECT status,COUNT(*) AS total FROM usuarios_perfis GROUP BY status'),
        'paginas' => req299_linhas("SELECT project,status,COUNT(*) AS total FROM paginas GROUP BY project,status"),
        'cota_admin' => usuario_equipe_cota(1),
        'subscription_schema' => req299_linhas('SHOW COLUMNS FROM subscriptions'),
    ];
} elseif($modo === 'prepare') {
    if(req299_linhas("SELECT id_usuarios FROM usuarios WHERE id LIKE 'req299-e2e%'") !== []) throw new RuntimeException('Fixture already exists');
    $perfil = (int)req299_linhas('SELECT id_usuarios_perfis FROM usuarios WHERE id_usuarios=1')[0]['id_usuarios_perfis'];
    req299_exec("INSERT INTO usuarios (id,usuario,email,nome,nome_conta,id_usuarios_perfis,status,versao,data_criacao,data_modificacao) VALUES ('req299-e2e-titular','req299-e2e-titular@teste.local','req299-e2e-titular@teste.local','REQ299 E2E Titular','REQ299 E2E',$perfil,'A',1,NOW(),NOW())");
    $id = (int)banco_last_id();
    $p = usuario_equipe_perfil_salvar($id, ['nome'=>'REQ299 E2E Agenda','modulos'=>['agenda'],'operacoes'=>[]]);
    if(empty($p['ok'])) throw new RuntimeException(json_encode($p));
    $p2 = usuario_equipe_perfil_salvar($id, ['nome'=>'REQ299 E2E Agenda 2','modulos'=>['agenda'],'operacoes'=>[]]);
    if(empty($p2['ok'])) throw new RuntimeException(json_encode($p2));
    $saida = ['titular'=>$id, 'perfil'=>$p['id_perfil'], 'perfil2'=>$p2['id_perfil'], 'cota'=>usuario_equipe_cota($id)];
} elseif($modo === 'member') {
    $titular = (int)$entrada['titular'];
    $saida = req299_linhas("SELECT id_usuarios,id_usuarios_perfis,id_usuarios_pai,nome,email,usuario FROM usuarios WHERE id_usuarios_pai=$titular");
} elseif($modo === 'subscription') {
    $titular = (int)$entrada['titular'];
    $status = banco_escape_field($entrada['status']);
    if(!req299_linhas("SELECT id_usuarios FROM usuarios WHERE id_usuarios=$titular AND id='req299-e2e-titular'")) throw new RuntimeException('Foreign fixture');
    req299_exec("DELETE FROM subscriptions WHERE user_id=$titular AND id='req299-e2e-sub'");
    req299_exec("INSERT INTO subscriptions (id,user_id,plan_id,status,payment_status,status_controle,language,versao,data_criacao,data_modificacao) VALUES ('req299-e2e-sub',$titular,'req299-e2e','$status','completed','A','pt-br',1,NOW(),NOW())");
    $saida = ['status'=>$status];
} elseif($modo === 'verify-password') {
    $titular = (int)$entrada['titular'];
    $membro = (int)$entrada['membro'];
    $r = req299_linhas("SELECT senha FROM usuarios WHERE id_usuarios=$membro AND id_usuarios_pai=$titular");
    $saida = ['valid'=>isset($r[0]) && password_verify(banco_escape_field($entrada['senha']), $r[0]['senha'])];
} elseif($modo === 'cleanup') {
    $titulares = req299_linhas("SELECT id_usuarios FROM usuarios WHERE id='req299-e2e-titular'");
    foreach($titulares as $r){
        $id = (int)$r['id_usuarios'];
        foreach(usuario_equipe_membros($id) as $m) {
            $r = usuario_equipe_membro_alterar($id, (int)$m['id_usuarios'], 'excluir');
            if(empty($r['ok'])) throw new RuntimeException('Member cleanup failed');
        }
        foreach(usuario_equipe_perfis($id) as $p) {
            $r = usuario_equipe_perfil_excluir($id, (int)$p['id_usuarios_perfis']);
            if(empty($r['ok'])) throw new RuntimeException('Profile cleanup failed');
        }
        req299_exec("DELETE FROM usuarios_perfis WHERE id_usuarios_dono=$id");
        req299_exec("DELETE FROM subscriptions WHERE user_id=$id AND id='req299-e2e-sub'");
        req299_exec("DELETE FROM usuarios_tokens WHERE id_usuarios=$id");
        req299_exec("DELETE FROM usuarios WHERE id_usuarios=$id AND id='req299-e2e-titular'");
    }
    $saida = ['remaining'=>count(req299_linhas("SELECT id_usuarios FROM usuarios WHERE id LIKE 'req299-e2e%'"))];
} else throw new RuntimeException('Unknown mode');
echo json_encode($saida, JSON_UNESCAPED_SLASHES)."\n";
