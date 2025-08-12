<?php

/**
 * Recurso Variáveis Criar Origem
 *
 * Este script é responsável por migrar as variáveis do seed `VariaveisData.json`
 * para os arquivos de recursos correspondentes (globais, módulos e plugins).
 *
 * @version 1.0
 * @author Otavio Serra
 * @date 12/08/2025
 */

// Definições de caminhos e configurações
$basePath = realpath(__DIR__ . '/../../../../') . '/';

require_once $basePath . 'gestor/bibliotecas/lang.php';

$variaveisSeedFile = $basePath . 'gestor/db/data/VariaveisData.json';
$modulosPath = $basePath . 'gestor/modulos';
$pluginsPath = $basePath . 'gestor-plugins';
$resourcesPath = $basePath . 'gestor/resources';
$logPath = $basePath . 'gestor/logs/arquitetura';
$logFilename = 'recurso-variaveis-criar-origem.log';

/**
 * Função para registrar logs em disco.
 * @param string $message A mensagem de log.
 */
function log_disco($message, $file) {
    $logMessage = "[" . date("Y-m-d H:i:s") . "] " . $message . PHP_EOL;
    file_put_contents($file, $logMessage, FILE_APPEND);
}

/**
 * Lê as variáveis do arquivo de seed.
 * @return array As variáveis lidas do arquivo JSON.
 */
function lerVariaveis() {
    global $variaveisSeedFile, $logFile;
    log_disco(_('log_read_vars', ['file' => $variaveisSeedFile]), $logFile);
    if (!file_exists($variaveisSeedFile)) {
        log_disco(_('log_read_vars_error', ['file' => $variaveisSeedFile]), $logFile);
        return [];
    }
    $jsonContent = file_get_contents($variaveisSeedFile);
    $data = json_decode($jsonContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        log_disco(_('log_json_decode_error', ['error' => json_last_error_msg()]), $logFile);
        return [];
    }
    log_disco(_('log_vars_read_success'), $logFile);
    return $data;
}

/**
 * Obtém os IDs dos módulos principais.
 * @return array Uma lista com os IDs dos módulos.
 */
function modulosIDs() {
    global $modulosPath, $logFile;
    log_disco(_('log_read_module_ids', ['path' => $modulosPath]), $logFile);
    $modulos = [];
    $items = scandir($modulosPath);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        if (is_dir($modulosPath . '/' . $item)) {
            $modulos[] = $item;
        }
    }
    log_disco(_('log_module_ids_success'), $logFile);
    return $modulos;
}

/**
 * Obtém os IDs dos módulos de plugins, mapeando cada módulo ao seu plugin.
 * @return array Um mapa onde a chave é o ID do módulo e o valor é o ID do plugin.
 */
function modulosPluginsIDs() {
    global $pluginsPath, $logFile;
    log_disco(_('log_read_plugin_module_ids', ['path' => $pluginsPath]), $logFile);
    $pluginModulesMap = [];
    $plugins = scandir($pluginsPath);
    foreach ($plugins as $plugin) {
        if ($plugin === '.' || $plugin === '..') {
            continue;
        }
        $pluginDir = $pluginsPath . '/' . $plugin;
        if (is_dir($pluginDir)) {
            $localModulosPath = $pluginDir . '/local/modulos';
            if (is_dir($localModulosPath)) {
                $modules = scandir($localModulosPath);
                foreach ($modules as $module) {
                    if ($module === '.' || $module === '..') {
                        continue;
                    }
                    if (is_dir($localModulosPath . '/' . $module)) {
                        $pluginModulesMap[$module] = $plugin;
                    }
                }
            }
        }
    }
    log_disco(_('log_plugin_module_ids_success'), $logFile);
    return $pluginModulesMap;
}

/**
 * Formata e categoriza as variáveis.
 * @param array $variaveis As variáveis a serem formatadas.
 * @param array $modulosIDs Os IDs dos módulos principais.
 * @param array $modulosPluginsMap O mapa de módulos de plugins.
 * @return array As variáveis formatadas e categorizadas.
 */
function formatarVars($variaveis, $modulosIDs, $modulosPluginsMap) {
    global $logFile;
    log_disco(_('log_format_vars'), $logFile);

    $varsFormatadasPorTipo = [
        'modulos' => [],
        'plugins' => [],
        'globais' => []
    ];

    $modulosPluginsIDs = array_keys($modulosPluginsMap);

    foreach ($variaveis as $var) {
        $formattedVar = [
            'id' => $var['id'],
            'value' => $var['valor'],
            'type' => $var['tipo']
        ];

        if (!empty($var['grupo'])) {
            $formattedVar['group'] = $var['grupo'];
        }
        if (!empty($var['descricao'])) {
            $formattedVar['description'] = $var['descricao'];
        }

        $modulo = $var['modulo'];
        $lang = $var['linguagem_codigo'];

        if (!empty($modulo)) {
            if (in_array($modulo, $modulosIDs)) {
                if (!isset($varsFormatadasPorTipo['modulos'][$modulo])) {
                    $varsFormatadasPorTipo['modulos'][$modulo] = [];
                }
                if (!isset($varsFormatadasPorTipo['modulos'][$modulo][$lang])) {
                    $varsFormatadasPorTipo['modulos'][$modulo][$lang] = [];
                }
                $varsFormatadasPorTipo['modulos'][$modulo][$lang][] = $formattedVar;
            } elseif (in_array($modulo, $modulosPluginsIDs)) {
                $plugin = $modulosPluginsMap[$modulo];
                if (!isset($varsFormatadasPorTipo['plugins'][$plugin])) {
                    $varsFormatadasPorTipo['plugins'][$plugin] = [];
                }
                if (!isset($varsFormatadasPorTipo['plugins'][$plugin][$modulo])) {
                    $varsFormatadasPorTipo['plugins'][$plugin][$modulo] = [];
                }
                if (!isset($varsFormatadasPorTipo['plugins'][$plugin][$modulo][$lang])) {
                    $varsFormatadasPorTipo['plugins'][$plugin][$modulo][$lang] = [];
                }
                $varsFormatadasPorTipo['plugins'][$plugin][$modulo][$lang][] = $formattedVar;
            } else { // Se o módulo não for encontrado, trate como global
                // Adiciona o campo 'modulo' para referência, conforme solicitado
                $formattedVar['modulo'] = $modulo;
                if (!isset($varsFormatadasPorTipo['globais'][$lang])) {
                    $varsFormatadasPorTipo['globais'][$lang] = [];
                }
                $varsFormatadasPorTipo['globais'][$lang][] = $formattedVar;
            }
        } else {
            if (!isset($varsFormatadasPorTipo['globais'][$lang])) {
                $varsFormatadasPorTipo['globais'][$lang] = [];
            }
            $varsFormatadasPorTipo['globais'][$lang][] = $formattedVar;
        }
    }
    log_disco(_('log_format_vars_success'), $logFile);
    return $varsFormatadasPorTipo;
}

/**
 * Salva as variáveis formatadas nos arquivos de recursos.
 * @param array $varsFormatadasPorTipo As variáveis formatadas por tipo.
 */
function guardarVarsNosResources($varsFormatadasPorTipo) {
    global $resourcesPath, $modulosPath, $pluginsPath, $logFile;
    log_disco(_('log_save_vars'), $logFile);

    // Salvar variáveis globais
    foreach ($varsFormatadasPorTipo['globais'] as $lang => $vars) {
        $langPath = $resourcesPath . '/' . $lang;
        if (!is_dir($langPath)) {
            mkdir($langPath, 0777, true);
        }
        $filePath = $langPath . '/variables.json';
        file_put_contents($filePath, json_encode($vars, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        log_disco(_('log_save_global_vars', ['lang' => $lang, 'path' => $filePath]), $logFile);
    }

    // Salvar variáveis de módulos
    foreach ($varsFormatadasPorTipo['modulos'] as $modulo => $langs) {
        foreach ($langs as $lang => $vars) {
            $moduleFilePath = $modulosPath . '/' . $modulo . '/' . $modulo . '.json';
            $moduleData = [];
            if (file_exists($moduleFilePath)) {
                $moduleData = json_decode(file_get_contents($moduleFilePath), true);
            }
            if (!isset($moduleData['resources'])) {
                $moduleData['resources'] = [];
            }
            if (!isset($moduleData['resources'][$lang])) {
                $moduleData['resources'][$lang] = [];
            }
            $moduleData['resources'][$lang]['variables'] = $vars;
            file_put_contents($moduleFilePath, json_encode($moduleData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            log_disco(_('log_save_module_vars', ['module' => $modulo, 'lang' => $lang, 'path' => $moduleFilePath]), $logFile);
        }
    }

    // Salvar variáveis de plugins
    foreach ($varsFormatadasPorTipo['plugins'] as $plugin => $modulos) {
        foreach ($modulos as $modulo => $langs) {
            foreach ($langs as $lang => $vars) {
                $moduleFilePath = $pluginsPath . '/' . $plugin . '/local/modulos/' . $modulo . '/' . $modulo . '.json';
                $moduleData = [];
                if (file_exists($moduleFilePath)) {
                    $moduleData = json_decode(file_get_contents($moduleFilePath), true);
                }
                if (!isset($moduleData['resources'])) {
                    $moduleData['resources'] = [];
                }
                if (!isset($moduleData['resources'][$lang])) {
                    $moduleData['resources'][$lang] = [];
                }
                $moduleData['resources'][$lang]['variables'] = $vars;
                file_put_contents($moduleFilePath, json_encode($moduleData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                log_disco(_('log_save_plugin_vars', ['module' => $modulo, 'plugin' => $plugin, 'lang' => $lang, 'path' => $moduleFilePath]), $logFile);
            }
        }
    }
    log_disco(_('log_save_vars_success'), $logFile);
}

/**
 * Reporta as mudanças realizadas.
 * @param array $varsFormatadasPorTipo As variáveis que foram processadas.
 */
function reportarMudancas($varsFormatadasPorTipo) {
    global $logFile;
    log_disco(_('report_generating'), $logFile);
    $report = _('report_title') . PHP_EOL;
    $report .= "===================================" . PHP_EOL . PHP_EOL;

    $totalGlobais = 0;
    foreach ($varsFormatadasPorTipo['globais'] as $lang => $vars) {
        $count = count($vars);
        $totalGlobais += $count;
        $report .= _('report_global_vars', ['lang' => $lang, 'count' => $count]) . PHP_EOL;
    }

    $totalModulos = 0;
    foreach ($varsFormatadasPorTipo['modulos'] as $modulo => $langs) {
        foreach ($langs as $lang => $vars) {
            $count = count($vars);
            $totalModulos += $count;
            $report .= _('report_module_vars', ['module' => $modulo, 'lang' => $lang, 'count' => $count]) . PHP_EOL;
        }
    }

    $totalPlugins = 0;
    foreach ($varsFormatadasPorTipo['plugins'] as $plugin => $modulos) {
        foreach ($modulos as $modulo => $langs) {
            foreach ($langs as $lang => $vars) {
                $count = count($vars);
                $totalPlugins += $count;
                $report .= _('report_plugin_vars', ['plugin' => $plugin, 'module' => $modulo, 'lang' => $lang, 'count' => $count]) . PHP_EOL;
            }
        }
    }

    $report .= PHP_EOL . _('report_summary_title') . PHP_EOL;
    $report .= _('report_summary_global', ['count' => $totalGlobais]) . PHP_EOL;
    $report .= _('report_summary_module', ['count' => $totalModulos]) . PHP_EOL;
    $report .= _('report_summary_plugin', ['count' => $totalPlugins]) . PHP_EOL;
    $report .= _('report_summary_total', ['count' => ($totalGlobais + $totalModulos + $totalPlugins)]) . PHP_EOL;

    log_disco($report, $logFile);
    echo nl2br($report);
}

/**
 * Função principal que orquestra a execução do script.
 */
function main() {
    global $logPath, $logFilename;
    $logFile = $logPath . '/' . $logFilename;
    if (!is_dir($logPath)) {
        mkdir($logPath, 0777, true);
    }

    // Adiciona uma variável global para o arquivo de log para ser acessível em outras funções
    $GLOBALS['logFile'] = $logFile;

    // Define o idioma (pode ser dinâmico no futuro)
    set_lang('pt-br');

    log_disco(_('log_start'), $logFile);

    $variaveis = lerVariaveis();
    if (empty($variaveis)) {
        log_disco(_('log_no_vars'), $logFile);
        return;
    }
    
    $modulosIDs = modulosIDs();
    $modulosPluginsMap = modulosPluginsIDs();
    $varsFormatadasPorTipo = formatarVars($variaveis, $modulosIDs, $modulosPluginsMap);
    guardarVarsNosResources($varsFormatadasPorTipo);
    reportarMudancas($varsFormatadasPorTipo);

    log_disco(_('log_end'), $logFile);
}

// Executa a função principal
main();
