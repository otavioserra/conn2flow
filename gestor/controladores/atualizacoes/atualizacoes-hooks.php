<?php
/**
 * Controller de Atualização de Hooks
 * 
 * Ponto central de sincronização da tabela `hooks`.
 * Varre JSONs de módulos e do projeto, registrando os hooks encontrados.
 * Deve ser idempotente — pode ser chamado múltiplas vezes sem efeitos colaterais.
 *
 * @package Conn2Flow
 * @subpackage Hooks
 */

/**
 * Sincroniza a tabela hooks com os JSONs de módulos e projeto.
 *
 * @param array $opcoes Opções de sincronização:
 *   - 'apenas_projeto' (bool): Se true, sincroniza apenas project/hooks/hooks.json
 */
function atualizacoes_hooks_sincronizar(array $opcoes = []): void {
    global $_GESTOR;

    $apenasModulos = !empty($opcoes['apenas_modulos']);
    $apenasProjeto = !empty($opcoes['apenas_projeto']);

    // ===== Sincronizar hooks de módulos (instalados no sistema)
    if (!$apenasProjeto) {
        atualizacoes_hooks_sincronizar_modulos();
    }

    // ===== Sincronizar hooks de plugins (módulos de plugins)
    if (!$apenasProjeto) {
        atualizacoes_hooks_sincronizar_plugins();
    }

    // ===== Sincronizar hooks do projeto
    if (!$apenasModulos) {
        hooks_registrar_projeto();
    }
}

/**
 * Varre todos os módulos instalados em modulos-path e sincroniza seus hooks.
 */
function atualizacoes_hooks_sincronizar_modulos(): void {
    global $_GESTOR;

    $modulosPath = $_GESTOR['modulos-path'];

    if (!is_dir($modulosPath)) {
        return;
    }

    $dirs = @scandir($modulosPath);
    if (!$dirs) {
        return;
    }

    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;

        $moduloDir = $modulosPath . $dir;
        if (!is_dir($moduloDir)) continue;

        $jsonPath = $moduloDir . '/' . $dir . '.json';
        if (!file_exists($jsonPath)) continue;

        $json = @json_decode(file_get_contents($jsonPath), true);
        if (!$json || !isset($json['hooks']) || !is_array($json['hooks'])) continue;

        hooks_registrar_modulo($dir, null, $json['hooks']);
    }
}

/**
 * Varre todos os plugins instalados e sincroniza hooks de seus módulos.
 */
function atualizacoes_hooks_sincronizar_plugins(): void {
    global $_GESTOR;

    $pluginsPath = $_GESTOR['plugins-path'];

    if (!is_dir($pluginsPath)) {
        return;
    }

    $pluginDirs = @scandir($pluginsPath);
    if (!$pluginDirs) {
        return;
    }

    foreach ($pluginDirs as $pluginDir) {
        if ($pluginDir === '.' || $pluginDir === '..') continue;

        $pluginModulesPath = $pluginsPath . $pluginDir . '/modules/';
        if (!is_dir($pluginModulesPath)) continue;

        $moduleDirs = @scandir($pluginModulesPath);
        if (!$moduleDirs) continue;

        foreach ($moduleDirs as $moduleDir) {
            if ($moduleDir === '.' || $moduleDir === '..') continue;

            $modulePath = $pluginModulesPath . $moduleDir;
            if (!is_dir($modulePath)) continue;

            $jsonPath = $modulePath . '/' . $moduleDir . '.json';
            if (!file_exists($jsonPath)) continue;

            $json = @json_decode(file_get_contents($jsonPath), true);
            if (!$json || !isset($json['hooks']) || !is_array($json['hooks'])) continue;

            hooks_registrar_modulo($moduleDir, $pluginDir, $json['hooks']);
        }
    }
}
