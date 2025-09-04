<?php
// Gera um plugin de teste minimalista para pipeline de instalação (Fase 1)
$base = __DIR__ . '/../../gestor/tests/build';
$work = $base . '/tmp-test-plugin';
$zipFile = $base . '/test-plugin.zip';

function rrmdir($d){ if(!is_dir($d)) return; $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST); foreach($it as $f){ $f->isDir()?rmdir($f->getPathname()):unlink($f->getPathname()); } rmdir($d);} 
if(!is_dir($base)) mkdir($base,0777,true);
if(is_dir($work)) rrmdir($work);
mkdir($work,0777,true);
// manifest
$manifest = [
  'id'=>'test-plugin',
  'nome'=>'Plugin Teste',
  'versao'=>'1.0.0',
  'descricao'=>'Plugin de teste automatizado',
  'compatibilidade'=>['min'=>'1.0.0','max'=>'2.x'],
  'autor'=>'Automated',
  'license'=>'MIT',
  'recursos'=>['layouts'=>true,'pages'=>true,'components'=>true,'variables'=>true],
];
file_put_contents($work.'/manifest.json', json_encode($manifest, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
// Data.json
$data = [
  'resources'=>[
    'pt-br'=>[
      'layouts'=>[
        ['id'=>'layout-alpha','name'=>'Layout Alpha','version'=>'1.0','checksum'=>['html'=>'abc','css'=>'','combined'=>'abc']]
      ],
      'pages'=>[
        ['id'=>'page-home','name'=>'Página Home','version'=>'1.0','path'=>'home/','layout'=>'layout-alpha','checksum'=>['html'=>'def','css'=>'','combined'=>'def']]
      ],
      'components'=>[
        ['id'=>'comp-box','name'=>'Componente Box','version'=>'1.0','checksum'=>['html'=>'ghi','css'=>'','combined'=>'ghi']]
      ],
      'variables'=>[
        ['id'=>'var-msg','value'=>'Olá','type'=>'string']
      ]
    ]
  ]
];
$moduleDir = $work.'/modules/mod-core';
mkdir($moduleDir,0777,true);
$moduleDescriptor = [
  'id' => 'mod-core',
  'resources' => [
    'pt-br' => [
      'pages' => [
        ['id'=>'page-core-dashboard','name'=>'Dashboard Core','version'=>'1.0','path'=>'core/dashboard/','layout'=>'layout-alpha','checksum'=>['html'=>'xyz','css'=>'','combined'=>'xyz']]
      ],
      'components' => [
        ['id'=>'comp-core-panel','name'=>'Painel Core','version'=>'1.0','checksum'=>['html'=>'jjj','css'=>'','combined'=>'jjj']]
      ],
      'variables' => [
        ['id'=>'core-welcome','value'=>'Bem-vindo Core','type'=>'string']
      ]
    ]
  ]
];
file_put_contents($moduleDir.'/module-id.json', json_encode($moduleDescriptor, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
$dd = $work.'/db/data';
mkdir($dd,0777,true);
file_put_contents($dd.'/Data.json', json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
// Criar zip
if(file_exists($zipFile)) unlink($zipFile);
$zip = new ZipArchive();
if($zip->open($zipFile, ZipArchive::CREATE)!==true){ fwrite(STDERR,"Falha criar zip\n"); exit(1);} 
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($work,FilesystemIterator::SKIP_DOTS));
foreach($it as $f){ if($f->isFile()){ $rel = substr($f->getPathname(), strlen($work)+1); $zip->addFile($f->getPathname(), $rel); }}
$zip->close();
// Saída
clearstatcache();
if(file_exists($zipFile)){
  echo "ZIP criado: $zipFile (".filesize($zipFile)." bytes)\n";
  exit(0);
}
exit(2);
