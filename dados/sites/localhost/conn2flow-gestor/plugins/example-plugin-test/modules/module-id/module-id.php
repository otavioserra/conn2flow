<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'module-id';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/'.$_GESTOR['modulo-id'].'.json'), true);

// Example plugin module

function plg_example_plugin_example_module_run() {
    global $_GESTOR;
    
    echo "<h1>Example Module Loaded</h1>";
}
