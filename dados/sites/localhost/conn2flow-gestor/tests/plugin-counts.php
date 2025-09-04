<?php
// Conta recursos sincronizados de um plugin.
// Uso: php gestor/tests/plugin-counts.php --id=test-plugin

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../bibliotecas/banco.php';

$opt = getopt('', ['id::']);
$plugin = $opt['id'] ?? null;
if(!$plugin){
    fwrite(STDERR, "Parâmetro --id obrigatório\n");
    exit(1);
}

function qcount($tabela,$plugin){
    $campoLang = $tabela==='variaveis' ? 'linguagem_codigo' : 'language'; // só para garantir conexão inicial
    $res = banco_sql("SELECT COUNT(*) AS c FROM $tabela WHERE plugin='".banco_escape_field($plugin)."'");
    return $res ? (int)$res[0]['c'] : 0;
}

$out = [
  'plugin'=>$plugin,
  'layouts'=>qcount('layouts',$plugin),
  'pages'=>qcount('paginas',$plugin),
  'components'=>qcount('componentes',$plugin),
  'variables'=>qcount('variaveis',$plugin),
];
echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
exit(0);