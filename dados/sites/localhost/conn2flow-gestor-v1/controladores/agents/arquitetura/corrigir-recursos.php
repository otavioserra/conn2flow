<?php

// =========================== Configuração Inicial

require_once(dirname(__FILE__) . '/../../../bibliotecas/banco.php');
require_once(dirname(__FILE__) . '/../../../bibliotecas/log.php');

function verificar_recursos(){
    /*
    Estrutura da tabela `componentes`

    CREATE TABLE `componentes` (
        `id_componentes` int NOT NULL,
        `id_usuarios` int DEFAULT NULL,
        `nome` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
        `id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
        `modulo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
        `html` mediumtext COLLATE utf8mb4_general_ci,
        `css` mediumtext COLLATE utf8mb4_general_ci,
        `status` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
        `versao` int DEFAULT NULL,
        `data_criacao` datetime DEFAULT NULL,
        `data_modificacao` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    */
    $componentes = banco_select_name
    (
        '*'
        ,
        "componentes",
        ""
    );

    /*
    Estrutura da tabela `paginas`

    CREATE TABLE `paginas` (
        `id_paginas` int NOT NULL,
        `id_usuarios` int DEFAULT NULL,
        `id_layouts` int DEFAULT NULL,
        `nome` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
        `id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
        `caminho` text COLLATE utf8mb4_general_ci,
        `tipo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
        `modulo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
        `opcao` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
        `raiz` tinyint DEFAULT NULL,
        `sem_permissao` tinyint DEFAULT NULL,
        `html` mediumtext COLLATE utf8mb4_general_ci,
        `css` mediumtext COLLATE utf8mb4_general_ci,
        `status` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
        `versao` int DEFAULT NULL,
        `data_criacao` datetime DEFAULT NULL,
        `data_modificacao` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    */

    $paginas = banco_select_name
    (
        '*'
        ,
        "paginas",
        ""
    );

    /*
    Estrutura da tabela `layouts`

    CREATE TABLE `layouts` (
        `id_layouts` int NOT NULL,
        `id_usuarios` int DEFAULT NULL,
        `nome` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
        `id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
        `html` mediumtext COLLATE utf8mb4_general_ci,
        `css` mediumtext COLLATE utf8mb4_general_ci,
        `status` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
        `versao` int DEFAULT NULL,
        `data_criacao` datetime DEFAULT NULL,
        `data_modificacao` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    */

    $layouts = banco_select_name
    (
        '*'
        ,
        "layouts",
        ""
    );

    return [
        'componentes' => $componentes,
        'paginas' => $paginas,
        'layouts' => $layouts
    ];
}

function modulos(){
    $modulos = array();
    $modulosPath = dirname(__FILE__) . '/../../../modulos/';
    if (is_dir($modulosPath)) {
        $dirs = scandir($modulosPath);
        foreach ($dirs as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($modulosPath . $dir)) {
                $modulos[] = $dir;
            }
        }
    }
    return $modulos;
}

function modulos_plugin(){
    $plugins_modulos = array();
    $pluginsPath = dirname(__FILE__) . '/../../../../gestor-plugins/';
    if (is_dir($pluginsPath)) {
        $plugins = scandir($pluginsPath);
        foreach ($plugins as $plugin) {
            if ($plugin !== '.' && $plugin !== '..' && is_dir($pluginsPath . $plugin)) {
                $modulosPluginPath = $pluginsPath . $plugin . '/local/modulos/';
                $modulosPlugin = array();
                if (is_dir($modulosPluginPath)) {
                    $modulosDirs = scandir($modulosPluginPath);
                    foreach ($modulosDirs as $modulo) {
                        if ($modulo !== '.' && $modulo !== '..' && is_dir($modulosPluginPath . $modulo)) {
                            $modulosPlugin[] = $modulo;
                        }
                    }
                }
                $plugins_modulos[$plugin] = $modulosPlugin;
            }
        }
    }
    return $plugins_modulos;
}

function verificar_componentes($componentes) {
    // Componentes globais. Para saber se um componente é global, o campo modulo é NULL. Do recurso seria assim: $componentes[X]['modulo'] === NULL
    // Dentro da pasta gestor\resources\pt-br\components cada arquivo HTML e CSS fica armazenado usando o 'id' do componente como nome da pasta e nome do arquivo HTML e CSS: gestor\resources\pt-br\components\<id>\<id>.html e/ou gestor\resources\pt-br\components\<id>\<id>.css . Exemplo: gestor\resources\pt-br\components\admin-categorias-categorias-filho-info\admin-categorias-categorias-filho-info.html

    // Componentes de Módulos. Para saber se um componente é global, o campo modulo não é NULL. Do recurso seria assim: $componentes[X]['modulo'] !== NULL
    // Dentro da pasta gestor\modulos\<modulo>\resources\pt-br\components cada arquivo HTML e CSS fica armazenado usando o 'id' do componente como nome da pasta e nome do arquivo HTML e CSS: gestor\modulos\<modulo>\resources\pt-br\components\<id>\<id>.html e/ou gestor\modulos\<modulo>\resources\pt-br\components\<id>\<id>.css . Exemplo: gestor\modulos\admin-arquivos\resources\pt-br\components\host-plugins-update-carregando\host-plugins-update-carregando.html

    // Componentes de Módulos de Plugins. Para saber se um componente é global, o campo modulo não é NULL. Do recurso seria assim: $componentes[X]['modulo'] !== NULL
    // Dentro da pasta gestor-plugins\<plugin>\local\modulos\<modulo>\resources\pt-br\components cada arquivo HTML e CSS fica armazenado usando o 'id' do componente como nome da pasta e nome do arquivo HTML e CSS: gestor-plugins\<plugin>\local\modulos\<modulo>\resources\pt-br\components\<id>\<id>.html e/ou gestor-plugins\<plugin>\local\modulos\<modulo>\resources\pt-br\components\<id>\<id>.css

    // Listar módulos
    $modulos = modulos();
    $modulos_log = implode(",", $modulos);
    log_disco("Módulos encontrados: $modulos_log", "corrigir-recursos");
    log_disco("-----------------------------", "corrigir-recursos");

    // Listar plugins e seus módulos
    $plugins_modulos = modulos_plugin();
    foreach ($plugins_modulos as $plugin => $modulosPlugin) {
        $modulosPlugin_log = implode(",", $modulosPlugin);
        log_disco("Plugin encontrado: $plugin", "corrigir-recursos");
        log_disco("Módulos do plugin $plugin: $modulosPlugin_log", "corrigir-recursos");
        log_disco("-----------------------------", "corrigir-recursos");
    }

    $faltando = array();
    foreach ($componentes as $componente) {
        $id = $componente['id'];
        $modulo = $componente['modulo'];
        $plugin = isset($componente['plugin']) ? $componente['plugin'] : null;
        $tipo = '';
        $basePath = '';
        $canCreate = false;
        if (is_null($modulo) || $modulo === '' || strtolower($modulo) === 'null') {
            $tipo = 'global';
            $basePath = dirname(__FILE__) . "/../../../resources/pt-br/components/$id/";
            $canCreate = true;
        } else if ($plugin) {
            $tipo = 'módulo de plugin';
            // Validar plugin e módulo usando $plugins_modulos
            $canCreate = isset($plugins_modulos[$plugin]) && in_array($modulo, $plugins_modulos[$plugin]);
            $pluginPath = dirname(__FILE__) . "/../../../../gestor-plugins/$plugin/";
            $moduloPluginPath = $pluginPath . "local/modulos/$modulo/";
            $basePath = $moduloPluginPath . "resources/pt-br/components/$id/";
        } else if (in_array($modulo, $modulos)) {
            $tipo = 'módulo';
            $canCreate = true;
            $moduloPath = dirname(__FILE__) . "/../../../modulos/$modulo/";
            $basePath = $moduloPath . "resources/pt-br/components/$id/";
        } else {
            // Procurar em todos os plugins se o módulo existe
            foreach ($plugins_modulos as $pluginName => $modulosPlugin) {
                if (in_array($modulo, $modulosPlugin)) {
                    $tipo = "módulo de plugin ($pluginName)";
                    $canCreate = true;
                    $pluginPath = dirname(__FILE__) . "/../../../../gestor-plugins/$pluginName/";
                    $moduloPluginPath = $pluginPath . "local/modulos/$modulo/";
                    $basePath = $moduloPluginPath . "resources/pt-br/components/$id/";
                    $plugin = $pluginName;
                    break;
                }
            }
        }
        if (!$canCreate) {
            log_disco("IGNORADO: $tipo $id - módulo/plugin não existe na lista de módulos válidos", "corrigir-recursos");
            continue;
        }
        // Verificar pasta e criar se necessário
        $criouPasta = false;
        if (!is_dir($basePath)) {
            if (mkdir($basePath, 0777, true)) {
                log_disco("CRIADO: $tipo $id - pasta criada", "corrigir-recursos");
                $criouPasta = true;
            } else {
                log_disco("ERRO: $tipo $id - falha ao criar pasta", "corrigir-recursos");
            }
        }
        // Caminhos dos arquivos
        $htmlPath = $basePath . "$id.html";
        $cssPath = $basePath . "$id.css";
        $htmlExists = is_file($htmlPath);
        $cssExists = is_file($cssPath);
        // Criar HTML se houver conteúdo no banco e não existir
        if (!empty($componente['html']) && !$htmlExists) {
            file_put_contents($htmlPath, $componente['html']);
            log_disco("CRIADO: $tipo $id - HTML criado", "corrigir-recursos");
            $htmlExists = true;
        }
        // Criar CSS se houver conteúdo no banco e não existir
        if (!empty($componente['css']) && !$cssExists) {
            file_put_contents($cssPath, $componente['css']);
            log_disco("CRIADO: $tipo $id - CSS criado", "corrigir-recursos");
            $cssExists = true;
        }
        // Mensagem final
        $msg = "OK: $tipo $id - ";
        if ($htmlExists && $cssExists) {
            $msg .= "HTML e CSS encontrados";
        } else if ($htmlExists) {
            $msg .= "apenas HTML encontrado";
        } else if ($cssExists) {
            $msg .= "apenas CSS encontrado";
        } else {
            $msg .= "pasta encontrada, nenhum arquivo HTML ou CSS";
        }
        $encontrados[] = $id;
        log_disco($msg, "corrigir-recursos");
    }
    log_disco("-----------------------------", "corrigir-recursos");
    log_disco("Componentes com arquivos OK: " . implode(", ", $encontrados), "corrigir-recursos");
    log_disco("Componentes com arquivos faltando: " . implode(", ", $faltando), "corrigir-recursos");

        // Verificação dos metadados
        log_disco("-----------------------------", "corrigir-recursos");
        log_disco("Verificando existência dos metadados dos componentes:", "corrigir-recursos");
        $faltando_meta = array();
        $encontrados_meta = array();
        foreach ($componentes as $componente) {
            $id = $componente['id'];
            $modulo = $componente['modulo'];
            $plugin = isset($componente['plugin']) ? $componente['plugin'] : null;
            // Determinar tipo e caminho do metadados
            if (is_null($modulo) || $modulo === '' || strtolower($modulo) === 'null') {
                $tipo = 'global';
                $jsonPath = dirname(__FILE__) . "/../../../resources/pt-br/components.json";
                $found = false;
                if (is_file($jsonPath)) {
                    $json = json_decode(file_get_contents($jsonPath), true);
                    // components.json global é um array de objetos
                    foreach ($json as $obj) {
                        if (isset($obj['id']) && $obj['id'] === $id) {
                            $found = true;
                            break;
                        }
                    }
                }
            } else if ($plugin) {
                $tipo = 'módulo de plugin';
                $jsonPath = dirname(__FILE__) . "/../../../../gestor-plugins/$plugin/local/modulos/$modulo/$modulo.json";
                $found = false;
                if (is_file($jsonPath)) {
                    $json = json_decode(file_get_contents($jsonPath), true);
                    if (
                        isset($json['resources']) &&
                        isset($json['resources']['pt-br']) &&
                        isset($json['resources']['pt-br']['components']) &&
                        is_array($json['resources']['pt-br']['components'])
                    ) {
                        if (in_array($id, $json['resources']['pt-br']['components'])) {
                            $found = true;
                        }
                    }
                }
            } else {
                $tipo = 'módulo';
                $jsonPath = dirname(__FILE__) . "/../../../modulos/$modulo/$modulo.json";
                $found = false;
                if (is_file($jsonPath)) {
                    $json = json_decode(file_get_contents($jsonPath), true);
                    if (
                        isset($json['resources']) &&
                        isset($json['resources']['pt-br']) &&
                        isset($json['resources']['pt-br']['components']) &&
                        is_array($json['resources']['pt-br']['components'])
                    ) {
                        if (in_array($id, $json['resources']['pt-br']['components'])) {
                            $found = true;
                        }
                    }
                }
            }
            if ($found) {
                $encontrados_meta[] = $id;
                log_disco("OK: $tipo $id - metadados encontrados", "corrigir-recursos");
            } else {
                $faltando_meta[] = $id;
                log_disco("FALTANDO: $tipo $id - metadados", "corrigir-recursos");
            }
        }
        log_disco("-----------------------------", "corrigir-recursos");
        log_disco("Componentes com metadados OK: " . implode(", ", $encontrados_meta), "corrigir-recursos");
        log_disco("Componentes com metadados faltando: " . implode(", ", $faltando_meta), "corrigir-recursos");
    // Agora vamos fazer a relação para ver se tah faltando no arquivo de metadados: Global: gestor\resources\pt-br\components.json, por módulo gestor\modulos\<modulo>\<modulo>.json, por módulo de plugin gestor-plugins\<plugin>\local\modulos\<modulo>\<modulo>.json. Dentro dos arquivos .json dos módulos tem a variável resources.pt-br.components

    // Vamos incluir os metadados que não tem nos seus devidos arquivos corretos. Formatação do registro é assim:
    /*
        {
            "name": "nome",
            "id": "id",
            "version": "1.0",
            "checksum": {
                "html": "",
                "css": "",
                "combined": ""
            }
        },
    */

        // Incluir registros faltando nos arquivos de metadados
        foreach ($faltando_meta as $id_faltando) {
            // Buscar componente pelo id
            foreach ($componentes as $componente) {
                if ($componente['id'] === $id_faltando) {
                    $nome = $componente['nome'];
                    $modulo = $componente['modulo'];
                    $plugin = isset($componente['plugin']) ? $componente['plugin'] : null;
                    $registro = array(
                        "name" => $nome,
                        "id" => $id_faltando,
                        "version" => "1.0",
                        "checksum" => array(
                            "html" => "",
                            "css" => "",
                            "combined" => ""
                        )
                    );
                    // Determinar arquivo de metadados
                    if (is_null($modulo) || $modulo === '' || strtolower($modulo) === 'null') {
                        $jsonPath = dirname(__FILE__) . "/../../../resources/pt-br/components.json";
                        // Global: array de objetos
                        $json = is_file($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : array();
                        // Adicionar se não existe
                        $existe = false;
                        foreach ($json as $obj) {
                            if (isset($obj['id']) && $obj['id'] === $id_faltando) {
                                $existe = true;
                                break;
                            }
                        }
                        if (!$existe) {
                            $json[] = $registro;
                            file_put_contents($jsonPath, json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                            log_disco("INCLUÍDO: global $id_faltando no components.json", "corrigir-recursos");
                        }
                    } else if ($plugin) {
                        $jsonPath = dirname(__FILE__) . "/../../../../gestor-plugins/$plugin/local/modulos/$modulo/$modulo.json";
                        $json = is_file($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : array();
                        // Plugin: array em resources.pt-br.components
                        if (!isset($json['resources'])) $json['resources'] = array();
                        if (!isset($json['resources']['pt-br'])) $json['resources']['pt-br'] = array();
                        if (!isset($json['resources']['pt-br']['components'])) $json['resources']['pt-br']['components'] = array();
                        $existe = false;
                        foreach ($json['resources']['pt-br']['components'] as $obj) {
                            if (is_array($obj) && isset($obj['id']) && $obj['id'] === $id_faltando) {
                                $existe = true;
                                break;
                            } else if ($obj === $id_faltando) {
                                $existe = true;
                                break;
                            }
                        }
                        if (!$existe) {
                            $json['resources']['pt-br']['components'][] = $registro;
                            file_put_contents($jsonPath, json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                            log_disco("INCLUÍDO: módulo de plugin $plugin/$modulo $id_faltando no $modulo.json", "corrigir-recursos");
                        }
                    } else if (in_array($modulo, $modulos)) {
                        $jsonPath = dirname(__FILE__) . "/../../../modulos/$modulo/$modulo.json";
                        $json = is_file($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : array();
                        if (!isset($json['resources'])) $json['resources'] = array();
                        if (!isset($json['resources']['pt-br'])) $json['resources']['pt-br'] = array();
                        if (!isset($json['resources']['pt-br']['components'])) $json['resources']['pt-br']['components'] = array();
                        $existe = false;
                        foreach ($json['resources']['pt-br']['components'] as $obj) {
                            if (is_array($obj) && isset($obj['id']) && $obj['id'] === $id_faltando) {
                                $existe = true;
                                break;
                            } else if ($obj === $id_faltando) {
                                $existe = true;
                                break;
                            }
                        }
                        if (!$existe) {
                            $json['resources']['pt-br']['components'][] = $registro;
                            file_put_contents($jsonPath, json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                            log_disco("INCLUÍDO: módulo $modulo $id_faltando no $modulo.json", "corrigir-recursos");
                        }
                    } else {
                        // Procurar em todos os plugins se o módulo existe
                        foreach ($plugins_modulos as $pluginName => $modulosPlugin) {
                            if (in_array($modulo, $modulosPlugin)) {
                                $jsonPath = dirname(__FILE__) . "/../../../../gestor-plugins/$pluginName/local/modulos/$modulo/$modulo.json";
                                $json = is_file($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : array();
                                if (!isset($json['resources'])) $json['resources'] = array();
                                if (!isset($json['resources']['pt-br'])) $json['resources']['pt-br'] = array();
                                if (!isset($json['resources']['pt-br']['components'])) $json['resources']['pt-br']['components'] = array();
                                $existe = false;
                                foreach ($json['resources']['pt-br']['components'] as $obj) {
                                    if (is_array($obj) && isset($obj['id']) && $obj['id'] === $id_faltando) {
                                        $existe = true;
                                        break;
                                    } else if ($obj === $id_faltando) {
                                        $existe = true;
                                        break;
                                    }
                                }
                                if (!$existe) {
                                    $json['resources']['pt-br']['components'][] = $registro;
                                    file_put_contents($jsonPath, json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                                    log_disco("INCLUÍDO: módulo de plugin $pluginName/$modulo $id_faltando no $modulo.json", "corrigir-recursos");
                                }
                                break;
                            }
                        }
                    }
                    break;
                }
            }
        }

}

function verificar_paginas($paginas) {
    // Páginas globais
    // Dentro da pasta gestor\resources\pt-br\pages 
}

function verificar_layouts($layouts) {
    // Layouts globais
    // Dentro da pasta gestor\resources\pt-br\layouts
}

function main() {
    global $_BANCO;

    /* Banco configurado.
    echo print_r($_BANCO, true);

    Array
    (
        [tipo] => mysqli
        [host] => mysql
        [nome] => conn2flow
        [usuario] => conn2flow_user
        [senha] => conn2flow_pass
    )
    */

    // Substituir o nome do banco de busca:
    $_BANCO['tipo'] = 'mysqli';
    $_BANCO['host'] = '127.0.0.1';
    $_BANCO['nome'] = 'conn2flow_old';
    $_BANCO['usuario'] = 'conn2flow_user';
    $_BANCO['senha'] = 'conn2flow_pass';

    log_disco("Iniciar a correção dos recursos!", "corrigir-recursos",true);

    $recursos = verificar_recursos();

    verificar_componentes($recursos['componentes']);
    verificar_paginas($recursos['paginas']);
    verificar_layouts($recursos['layouts']);

    log_disco("Finalizado!", "corrigir-recursos");
}

main();