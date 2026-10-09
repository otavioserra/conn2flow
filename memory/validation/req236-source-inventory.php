<?php
declare(strict_types=1);
require_once __DIR__.'/req225-inventory.php';
$rows = array_merge(Req225Inventory::scan(dirname(__DIR__,2),'core'), Req225Inventory::scan(dirname(__DIR__,3).'/conn2flow-site-req101','site'));
$issues=[];
foreach($rows as $row)if($row['violations'])$issues[]=['project'=>$row['project'],'source'=>$row['source'],'violations'=>$row['violations']];
file_put_contents(__DIR__.'/req236-source-inventory.json',json_encode(['pages'=>count($rows),'issues'=>$issues],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
foreach($issues as $row)echo $row['project'].' '.$row['source'].' '.implode(' | ',$row['violations']).PHP_EOL;
echo 'Pages: '.count($rows).'; issue rows: '.count($issues).PHP_EOL;
