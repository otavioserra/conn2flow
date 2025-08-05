#!/usr/bin/env php
<?php
// Script: limpar_recursos_modulos.php
// Objetivo: Limpar arquivos e pastas que não pertencem ao módulo correto em gestor/modulos, gestor-plugins/*/local/modulos, gestor-plugins/*/remoto/modulos, gestor-cliente/modulos
// Uso: php ai-workspace/scripts/limpar_recursos_modulos.php

function limpar_modulos($base) {
    if (!is_dir($base)) return;
    $modulos = scandir($base);
    foreach ($modulos as $modulo) {
        if ($modulo === '.' || $modulo === '..') continue;
        $moduloPath = $base . $modulo . '/';
        if (!is_dir($moduloPath)) continue;
        $itens = scandir($moduloPath);
        foreach ($itens as $item) {
            if ($item === '.' || $item === '..') continue;
            $itemPath = $moduloPath . $item;
            // Manter apenas subpastas com o mesmo nome do módulo ou ids válidos
            if (is_dir($itemPath)) {
                // Só manter se for subpasta de página (id) ou o próprio nome do módulo
                if ($item !== $modulo && !preg_match('/^[a-zA-Z0-9\-_]+$/', $item)) {
                    remover_recursivo($itemPath);
                    echo "Removido: $itemPath\n";
                }
            } else {
                // Arquivos soltos .html/.css que não deveriam estar aqui
                if (preg_match('/\.(html|css)$/', $item)) {
                    unlink($itemPath);
                    echo "Removido: $itemPath\n";
                }
            }
        }
    }
}

function remover_recursivo($dir) {
    if (!file_exists($dir)) return;
    if (is_file($dir) || is_link($dir)) {
        unlink($dir);
    } else {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            remover_recursivo("$dir/$file");
        }
        rmdir($dir);
    }
}

function limpar_todos() {
    $bases = [
        __DIR__ . '/../../gestor/modulos/',
        __DIR__ . '/../../gestor-cliente/modulos/',
    ];
    // gestor-plugins/*/local/modulos/ e gestor-plugins/*/remoto/modulos/
    $pluginsBase = __DIR__ . '/../../gestor-plugins/';
    if (is_dir($pluginsBase)) {
        $plugins = scandir($pluginsBase);
        foreach ($plugins as $plugin) {
            if ($plugin === '.' || $plugin === '..') continue;
            $localMod = $pluginsBase . $plugin . '/local/modulos/';
            $remotoMod = $pluginsBase . $plugin . '/remoto/modulos/';
            if (is_dir($localMod)) $bases[] = $localMod;
            if (is_dir($remotoMod)) $bases[] = $remotoMod;
        }
    }
    foreach ($bases as $base) {
        limpar_modulos($base);
    }
}

limpar_todos();
echo "Limpeza concluída!\n";
