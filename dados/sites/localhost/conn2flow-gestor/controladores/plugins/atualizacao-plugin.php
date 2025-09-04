<?php
// Orquestrador inicial de instalação/atualização de plugin (stub Fase 1)
// Uso CLI (exemplo): php gestor/controladores/plugins/atualizacao-plugin.php --id=example-plugin --origem_tipo=upload --arquivo=/caminho/arquivo.zip
// Códigos de saída:
//  0  Sucesso / checksum inalterado
// 10 Erro genérico de parâmetros / arquivo
// 11 Manifest ou parâmetros inválidos
// 12 Falha mover diretório final
// 20 Falha download
// 21 Zip inválido ou muito pequeno

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../bibliotecas/plugins-consts.php';
require_once __DIR__ . '/../../bibliotecas/plugins-installer.php';

$options = getopt('', [
  'id::', 'origem_tipo::', 'arquivo::', 'owner::', 'repo::', 'ref::', 'local_path::', 'cred_ref::', 'reprocessar::',
  // Novas flags de controle
  'dry-run::', 'no-resources::', 'no-migrations::', 'only-migrations::', 'only-resources::'
]);

function plg_log($slug, $level, $msg){
  $dir = __DIR__ . '/../../logs/plugins';
  if(!is_dir($dir)) mkdir($dir, 0777, true);
  $line = sprintf("[%s] [%s] [PLUGIN:%s] %s\n", date('c'), strtoupper($level), $slug ?: '-', $msg);
  file_put_contents($dir.'/installer.log', $line, FILE_APPEND);
  echo $line;
}

$slug = $options['id'] ?? null;
if(!$slug){
  plg_log('-', 'error', 'Slug (id) não informado.');
  exit(PLG_EXIT_VALIDATE);
}

$origemTipo = $options['origem_tipo'] ?? null;
if(!$origemTipo){
  plg_log($slug, 'error', 'origem_tipo não informado.');
  exit(PLG_EXIT_VALIDATE);
}

// Detecta se já existe registro para diferenciar instalar vs atualizar
$existe = false;
$res = @banco_select([
  'tabela' => 'plugins',
  'campos' => ['id'],
  'extra' => "WHERE id='".banco_escape_field($slug)."'"
]);
if($res && count($res)>0) $existe = true;
$fase = $existe ? 'atualizacao' : 'instalacao';
plg_log($slug, 'info', 'Início processamento ('.$fase.') origem_tipo='.$origemTipo.(isset($options['reprocessar'])?' reprocessar=1':''));

$params = [
  'slug' => $slug,
  'origem_tipo' => $origemTipo,
  'arquivo' => $options['arquivo'] ?? null,
  'owner' => $options['owner'] ?? null,
  'repo' => $options['repo'] ?? null,
  'ref' => $options['ref'] ?? null,
  'cred_ref' => $options['cred_ref'] ?? null,
  'local_path' => $options['local_path'] ?? null,
  'reprocessar' => isset($options['reprocessar']),
  // Forward flags booleanas
  'dry_run' => isset($options['dry-run']),
  'no_resources' => isset($options['no-resources']),
  'no_migrations' => isset($options['no-migrations']),
  'only_migrations' => isset($options['only-migrations']),
  'only_resources' => isset($options['only-resources']),
];

// Marcar status inicial conforme fase
if(function_exists('plugin_mark_status')) plugin_mark_status($slug, $existe?PLG_STATUS_ATUALIZANDO:PLG_STATUS_INSTALANDO);

$code = plugin_process($params);

// Ajustar status final
if($code===PLG_EXIT_OK){
  plugin_mark_status($slug, PLG_STATUS_OK);
} else {
  plugin_mark_status($slug, PLG_STATUS_ERRO);
}

plg_log($slug, $code===PLG_EXIT_OK?'info':'error', 'Processo finalizado com código '.$code.' ('.plg_exit_code_label($code).')');
exit($code);
