<?php
declare(strict_types=1);
$fixtureMode = 'prepare';
$root = '/home/c2ftest/web/c2f-teste.local/conn2flow-gestor';
if (realpath($root) !== $root || !str_contains($root, '/c2f-teste.local/')) throw new RuntimeException('Test tenant guard');
$files = glob($root . '/autenticacoes/*/.env');
if (count($files) !== 1) throw new RuntimeException('Ambiguous test tenant configuration');
$cfg = [];
foreach (file($files[0], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2); $cfg[trim($k)] = trim(trim($v), "\"'");
}
if (($cfg['DB_DATABASE'] ?? '') !== 'c2ftest_c2f') throw new RuntimeException('Test database guard');
$pdo = new PDO('mysql:host='.$cfg['DB_HOST'].';dbname='.$cfg['DB_DATABASE'].';charset=utf8mb4', $cfg['DB_USERNAME'], $cfg['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$modules = json_decode('{"admin-categorias": {"table": "categorias", "key": "id"}, "admin-componentes": {"table": "componentes", "key": "id"}, "admin-ia": {"table": "servidores_ia", "key": "id_servidores_ia"}, "admin-layouts": {"table": "layouts", "key": "id"}, "admin-modos-ia": {"table": "modos_ia", "key": "id"}, "admin-paginas": {"table": "paginas", "key": "id"}, "admin-plugins": {"table": "plugins", "key": "id"}, "admin-prompts-ia": {"table": "prompts_ia", "key": "id"}, "admin-templates": {"table": "templates", "key": "id"}, "cookie-consent": {"table": "cookie_consent", "key": "id"}, "forms": {"table": "forms", "key": "id"}, "forms-search": {"table": "forms_search", "key": "id"}, "forms-submissions": {"table": "forms_submissions", "key": "id"}, "galleries": {"table": "galleries", "key": "id"}, "menus": {"table": "menus", "key": "id"}, "modulos": {"table": "modulos", "key": "id"}, "modulos-grupos": {"table": "modulos_grupos", "key": "id"}, "modulos-operacoes": {"table": "modulos_operacoes", "key": "id"}, "pages-index": {"table": "pages_index", "key": "id"}, "perfil-usuario": {"table": "usuarios", "key": "id"}, "publisher": {"table": "publisher", "key": "id"}, "publisher-highlights": {"table": "publisher_highlights", "key": "id"}, "publisher-index": {"table": "publisher_index", "key": "id"}, "publisher-pages": {"table": "paginas", "key": "id"}, "usuarios": {"table": "usuarios", "key": "id"}, "usuarios-perfis": {"table": "usuarios_perfis", "key": "id"}, "3d-catalog": {"table": "catalog_3d", "key": "id"}, "3d-catalog-groups": {"table": "catalog_3d_groups", "key": "id"}, "3d-catalog-items": {"table": "catalog_3d_items", "key": "id"}, "affiliates": {"table": "affiliates", "key": "id"}, "coupons": {"table": "coupons", "key": "id"}, "gateways-pagamentos": {"table": "gateways_pagamentos", "key": "id"}, "modulos-grupos-distribuido": {"table": "grupos", "key": "id"}, "orders": {"table": "orders", "key": "id"}, "presentations": {"table": "presentations", "key": "id"}, "product-types": {"table": "product_types", "key": "id"}, "products": {"table": "products", "key": "id"}, "products-index": {"table": "products_index", "key": "id"}, "social-apps": {"table": "social_apps", "key": "id"}, "social-connections": {"table": "social_connections", "key": "id"}, "stripe-products": {"table": "stripe_products", "key": "id"}, "subscriptions": {"table": "subscriptions", "key": "id"}, "subscriptions-plans": {"table": "subscriptions_plans", "key": "id"}, "subscriptions-service-stages": {"table": "subscriptions_service_stages", "key": "id_subscriptions_service_stages"}, "subscriptions-service-types": {"table": "subscriptions_service_types", "key": "id"}, "subscriptions-status": {"table": "subscriptions_status", "key": "id"}}', true, 512, JSON_THROW_ON_ERROR);
$result = [];
$priority=['product-types','forms','forms-search','3d-catalog-groups','3d-catalog-items','3d-catalog','social-apps','social-connections'];
uksort($modules, static fn($a,$b)=>(array_search($a,$priority)===false?99:array_search($a,$priority))<=>(array_search($b,$priority)===false?99:array_search($b,$priority)));
$pdo->beginTransaction();
try {
if ($fixtureMode === 'cleanup') {
    // The editor scenario owns only this exact disposable page and its version history.
    $q = $pdo->prepare('SELECT id_paginas FROM paginas WHERE nome = ? AND caminho = ?');
    $q->execute(['E2E req-219', 'e2e-req219/']);
    $ids = $q->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        $q = $pdo->prepare('DELETE FROM backup_campos WHERE modulo = ? AND id = ?');
        $q->execute(['admin-paginas', $id]);
        $q = $pdo->prepare('DELETE FROM historico WHERE modulo = ? AND id = ?');
        $q->execute(['admin-paginas', $id]);
        $q = $pdo->prepare('DELETE FROM paginas WHERE id_paginas = ? AND nome = ? AND caminho = ?');
        $q->execute([$id, 'E2E req-219', 'e2e-req219/']);
    }
    $result['req219-editor'] = ['removed' => count($ids)];
}
foreach ($modules as $module=>$meta) {
    $table=$meta['table']; $key=$meta['key'];
    if ($table === 'orders') $key = 'number';
    if (!preg_match('/^[a-zA-Z0-9_]+$/',$table.$key)) throw new RuntimeException('Identifier guard');
    try { $columns=$pdo->query('DESCRIBE `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC); }
    catch (PDOException $e) { if ($e->getCode()==='42S02') { fwrite(STDERR,'Skipped absent metadata table: '.$table."\n"); continue; } throw $e; }
    $byName=array_column($columns,null,'Field');
    if (!isset($byName[$key])) continue;
    if ($fixtureMode === 'cleanup') {
        if (isset($byName['id'])) {
            $q=$pdo->prepare('DELETE FROM `'.$table.'` WHERE id IN (?, ?)');
            $q->execute(['req225-fixture-pt-br','req225-fixture-en']);
        } elseif ($table === 'servidores_ia') {
            $q=$pdo->prepare('DELETE FROM `'.$table.'` WHERE nome IN (?, ?) AND (chave_api IS NULL OR chave_api IN (?, ?, ?))');
            $q->execute(['REQ225 Fixture pt-br','REQ225 Fixture en','','req225-fixture-pt-br','req225-fixture-en']);
        } else continue;
        $result[$module]=['removed'=>$q->rowCount()];
        continue;
    }
    foreach (['pt-br','en'] as $language) {
        if ($table === 'orders') {
            $q=$pdo->prepare('UPDATE orders SET number = ? WHERE id = ? AND number = ?');
            $q->execute(['REQ225-'.strtoupper($language),'req225-fixture-'.$language,'req225-fixture-'.$language]);
        }
        if (isset($byName['id'], $byName['status']) && str_starts_with($byName['status']['Type'], 'char(1)')) {
            $q=$pdo->prepare('UPDATE `'.$table.'` SET status = ? WHERE id = ? AND status IS NULL');
            $q->execute(['A','req225-fixture-'.$language]);
        }
        $where=isset($byName['language'])?' WHERE language = ?':'';
        $params=isset($byName['language'])?[$language]:[];
        if(isset($byName['status']) && $table!=='subscriptions') $where.=($where===''?' WHERE ':' AND ')."status != 'D'";
        $q=$pdo->prepare('SELECT `'.$key.'` FROM `'.$table.'`'.$where.' LIMIT 1'); $q->execute($params);
        if ($existing=$q->fetchColumn()) { $result[$module][$language]=['table'=>$table,'key'=>$key,'value'=>$existing,'created'=>false]; continue; }
        $id='req225-fixture-'.$language;
        $values=isset($byName['id'])?['id'=>$id]:[];
        foreach ($columns as $column) {
            $name=$column['Field'];$type=$column['Type'];
            if(str_contains($column['Extra'],'auto_increment') || $name==='id') continue;
            if($name==='language') { $values[$name]=$language;continue; }
            if($name==='number' && $table==='orders') { $values[$name]='REQ225-'.strtoupper($language);continue; }
            if($name==='status' && str_starts_with($type,'char(1)')) { $values[$name]='A';continue; }
            if(in_array($name,['name','nome','customer_name'])) { $values[$name]='REQ225 Fixture '.$language;continue; }
            if(in_array($name,['email','customer_email'])) { $values[$name]='req225-'.$language.'@example.invalid';continue; }
            if($name==='plataforma') { $values[$name]='instagram';continue; }
            if($name==='client_id') { $values[$name]='req225-test-only';continue; }
            if($name==='client_secret') { $values[$name]='';continue; }
            if($name==='token_salt') { $values[$name]=substr(hash('sha256',$id),0,32);continue; }
            if(in_array($name,['fields_schema','custom_fields','fields_values'])) { $values[$name]='{}';continue; }
            if($column['Null']==='YES' || $column['Default']!==null) continue;
            if(preg_match('/^(int|tinyint|smallint|bigint|decimal|double|float)/',$type)) $values[$name]=0;
            elseif(str_starts_with($type,'datetime') || $type==='timestamp') $values[$name]='2026-10-04 12:00:00';
            elseif(str_starts_with($type,'enum(')) { preg_match("/'([^']*)'/",$type,$m);$values[$name]=$m[1]; }
            else $values[$name]=str_starts_with($type,'char(1)')?'A':$id;
        }
        if ($table==='subscriptions') { $values['status']='pending'; $values['payment_status']='pending'; }
        $q=$pdo->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`',array_keys($values)).'`) VALUES ('.implode(',',array_fill(0,count($values),'?')).')');
        $q->execute(array_values($values));
        $value=str_contains($byName[$key]['Extra'] ?? '','auto_increment')?$pdo->lastInsertId():$values[$key];
        $result[$module][$language]=['table'=>$table,'key'=>$key,'value'=>$value,'fixture_id'=>$id,'created'=>true];
    }
}
$pdo->commit();
echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
} catch(Throwable $e) { $pdo->rollBack(); fwrite(STDERR,$e->getMessage()."\n");exit(1); }
