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
function atualizacoes_hooks_sincronizar(array $opcoes = []): array {
    global $_GESTOR;

    $apenasModulos = !empty($opcoes['apenas_modulos']);
    $apenasProjeto = !empty($opcoes['apenas_projeto']);
    $resumo = [
        'modulos' => 0,
        'plugins' => 0,
        'projeto' => 0,
        'total' => 0,
    ];

    // ===== Sincronizar hooks de módulos (instalados no sistema)
    if (!$apenasProjeto) {
        $resumo['modulos'] = atualizacoes_hooks_sincronizar_modulos();
    }

    // ===== Sincronizar hooks de plugins (módulos de plugins)
    if (!$apenasProjeto) {
        $resumo['plugins'] = atualizacoes_hooks_sincronizar_plugins();
    }

    // ===== Sincronizar hooks do projeto
    if (!$apenasModulos) {
        $resumo['projeto'] = hooks_registrar_projeto();
    }

    $resumo['total'] = $resumo['modulos'] + $resumo['plugins'] + $resumo['projeto'];
    return $resumo;
}

/**
 * Varre todos os módulos instalados em modulos-path e sincroniza seus hooks.
 */
function atualizacoes_hooks_sincronizar_modulos(): int {
    global $_GESTOR;

    $modulosPath = $_GESTOR['modulos-path'];
    $total = 0;

    if (!is_dir($modulosPath)) {
        return 0;
    }

    $dirs = @scandir($modulosPath);
    if (!$dirs) {
        return 0;
    }

    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') continue;

        $moduloDir = $modulosPath . $dir;
        if (!is_dir($moduloDir)) continue;

        $jsonPath = $moduloDir . '/' . $dir . '.json';
        if (!file_exists($jsonPath)) continue;

        $json = @json_decode(file_get_contents($jsonPath), true);
        if (!is_array($json)) continue;

        // Ausência da chave hooks também é estado declarativo: remove registros antigos.
        $hooks = isset($json['hooks']) && is_array($json['hooks']) ? $json['hooks'] : [];
        $total += hooks_registrar_modulo($dir, null, $hooks);
    }

    return $total;
}

/**
 * Varre todos os plugins instalados e sincroniza hooks de seus módulos.
 */
function atualizacoes_hooks_sincronizar_plugins(): int {
    global $_GESTOR;

    $pluginsPath = $_GESTOR['plugins-path'];
    $total = 0;

    if (!is_dir($pluginsPath)) {
        return 0;
    }

    $pluginDirs = @scandir($pluginsPath);
    if (!$pluginDirs) {
        return 0;
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
            if (!is_array($json)) continue;

            $hooks = isset($json['hooks']) && is_array($json['hooks']) ? $json['hooks'] : [];
            $total += hooks_registrar_modulo($moduleDir, $pluginDir, $hooks);
        }
    }

    return $total;
}
