#!/usr/bin/env php
<?php
// Script: exportar_seeds_para_arquivos.php
// Objetivo: Exportar layouts, páginas e componentes dos seeders para arquivos HTML/CSS na estrutura definida em resources/ e modulos/
// Uso: php ai-workspace/scripts/exportar_seeds_para_arquivos.php

// Este é um esqueleto inicial. A lógica de exportação será incrementada conforme avançarmos.

function exportar_layouts() {
    $seeder = __DIR__ . '/../../gestor/db/seeds/LayoutsSeeder.php';
    if (!file_exists($seeder)) {
        echo "Arquivo LayoutsSeeder.php não encontrado!\n";
        return;
    }
    $conteudo = file_get_contents($seeder);
    if (!preg_match('/\\$data\\s*=\\s*\[(.*)\];/sU', $conteudo, $matches)) {
        echo "Não foi possível localizar o array $data em LayoutsSeeder.php\n";
        return;
    }
    $arrayStr = $matches[1];
    $arrayStr = preg_replace(["/\r?\n/", "/\s+/"], ["", " "], $arrayStr);
    $arrayStr = str_replace("'", '"', $arrayStr);
    $arrayStr = '[' . $arrayStr . ']';
    $layouts = @json_decode($arrayStr, true);
    if (!$layouts) {
        $layouts = [];
        eval('$tmp = [' . $matches[1] . '];');
        if (isset($tmp) && is_array($tmp)) $layouts = $tmp;
    }
    if (!$layouts || !is_array($layouts)) {
        echo "Não foi possível extrair os layouts do seeder.\n";
        return;
    }
    $base = dirname(__DIR__, 2) . '/gestor/resources/layouts/';
    if (!is_dir($base)) mkdir($base, 0777, true);
    foreach ($layouts as $layout) {
        if (empty($layout['id'])) continue;
        $id = $layout['id'];
        $dir = $base . $id . '/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $html = isset($layout['html']) ? $layout['html'] : '';
        file_put_contents($dir . "$id.html", $html);
        $css = isset($layout['css']) ? $layout['css'] : '';
        if ($css !== null && $css !== '') {
            file_put_contents($dir . "$id.css", $css);
        }
    }
    echo "Layouts exportados para gestor/resources/layouts/\n";
}

function exportar_paginas() {
    $seeder = __DIR__ . '/../../gestor/db/seeds/PaginasSeeder.php';
    if (!file_exists($seeder)) {
        echo "Arquivo PaginasSeeder.php não encontrado!\n";
        return;
    }
    $conteudo = file_get_contents($seeder);
    if (!preg_match('/\\$data\\s*=\\s*\[(.*)\];/sU', $conteudo, $matches)) {
        echo "Não foi possível localizar o array $data em PaginasSeeder.php\n";
        return;
    }
    $arrayStr = $matches[1];
    $arrayStr = preg_replace(["/\r?\n/", "/\s+/"], ["", " "], $arrayStr);
    $arrayStr = str_replace("'", '"', $arrayStr);
    $arrayStr = '[' . $arrayStr . ']';
    $paginas = @json_decode($arrayStr, true);
    if (!$paginas) {
        $paginas = [];
        eval('$tmp = [' . $matches[1] . '];');
        if (isset($tmp) && is_array($tmp)) $paginas = $tmp;
    }
    if (!$paginas || !is_array($paginas)) {
        echo "Não foi possível extrair as páginas do seeder.\n";
        return;
    }
    $basePaginas = dirname(__DIR__, 2) . '/gestor/resources/paginas/';
    $baseModulos = dirname(__DIR__, 2) . '/gestor/modulos/';
    $basePlugins = dirname(__DIR__, 2) . '/gestor-plugins/';
    $baseCliente = dirname(__DIR__, 2) . '/gestor-cliente/modulos/';
    if (!is_dir($basePaginas)) mkdir($basePaginas, 0777, true);
    foreach ($paginas as $pagina) {
        if (empty($pagina['id'])) continue;
        $id = $pagina['id'];
        $modulo = isset($pagina['modulo']) ? $pagina['modulo'] : null;
        $exported = false;
        if ($modulo) {
            // gestor/modulos
            $moduloDir = $baseModulos . $modulo . '/';
            if (is_dir($moduloDir)) {
                $dir = $moduloDir . $id . '/';
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $htmlPath = $dir . "$id.html";
                $cssPath = $dir . "$id.css";
                $exported = true;
            }
            // gestor-plugins
            if (!$exported && is_dir($basePlugins)) {
                $plugins = scandir($basePlugins);
                foreach ($plugins as $plugin) {
                    if ($plugin === '.' || $plugin === '..') continue;
                    $localMod = $basePlugins . $plugin . '/local/modulos/' . $modulo . '/';
                    $remotoMod = $basePlugins . $plugin . '/remoto/modulos/' . $modulo . '/';
                    if (is_dir($localMod)) {
                        $dir = $localMod . $id . '/';
                        if (!is_dir($dir)) mkdir($dir, 0777, true);
                        $htmlPath = $dir . "$id.html";
                        $cssPath = $dir . "$id.css";
                        $exported = true;
                        break;
                    } elseif (is_dir($remotoMod)) {
                        $dir = $remotoMod . $id . '/';
                        if (!is_dir($dir)) mkdir($dir, 0777, true);
                        $htmlPath = $dir . "$id.html";
                        $cssPath = $dir . "$id.css";
                        $exported = true;
                        break;
                    }
                }
            }
            // gestor-cliente
            if (!$exported && is_dir($baseCliente)) {
                $clienteMod = $baseCliente . $modulo . '/';
                if (is_dir($clienteMod)) {
                    $dir = $clienteMod . $id . '/';
                    if (!is_dir($dir)) mkdir($dir, 0777, true);
                    $htmlPath = $dir . "$id.html";
                    $cssPath = $dir . "$id.css";
                    $exported = true;
                }
            }
        }
        if (!$exported) {
            // Exporta como página global
            $dir = $basePaginas . $id . '/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $htmlPath = $dir . "$id.html";
            $cssPath = $dir . "$id.css";
        }
        $html = isset($pagina['html']) ? $pagina['html'] : '';
        file_put_contents($htmlPath, $html);
        $css = isset($pagina['css']) ? $pagina['css'] : '';
        if ($css !== null && $css !== '') {
            file_put_contents($cssPath, $css);
        }
    }
    echo "Páginas exportadas para gestor/resources/paginas/, gestor/modulos/, gestor-plugins/.../modulos/ ou gestor-cliente/modulos/\n";
}

function exportar_componentes() {
    $seeder = __DIR__ . '/../../gestor/db/seeds/ComponentesSeeder.php';
    if (!file_exists($seeder)) {
        echo "Arquivo ComponentesSeeder.php não encontrado!\n";
        return;
    }
    $conteudo = file_get_contents($seeder);
    if (!preg_match('/\\$data\\s*=\\s*\[(.*)\];/sU', $conteudo, $matches)) {
        echo "Não foi possível localizar o array $data em ComponentesSeeder.php\n";
        return;
    }
    $arrayStr = $matches[1];
    $arrayStr = preg_replace(["/\r?\n/", "/\s+/"], ["", " "], $arrayStr);
    $arrayStr = str_replace("'", '"', $arrayStr);
    $arrayStr = '[' . $arrayStr . ']';
    $componentes = @json_decode($arrayStr, true);
    if (!$componentes) {
        $componentes = [];
        eval('$tmp = [' . $matches[1] . '];');
        if (isset($tmp) && is_array($tmp)) $componentes = $tmp;
    }
    if (!$componentes || !is_array($componentes)) {
        echo "Não foi possível extrair os componentes do seeder.\n";
        return;
    }
    $base = dirname(__DIR__, 2) . '/gestor/resources/componentes/';
    if (!is_dir($base)) mkdir($base, 0777, true);
    foreach ($componentes as $componente) {
        if (empty($componente['id'])) continue;
        $id = $componente['id'];
        $dir = $base . $id . '/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $html = isset($componente['html']) ? $componente['html'] : '';
        file_put_contents($dir . "$id.html", $html);
        $css = isset($componente['css']) ? $componente['css'] : '';
        if ($css !== null && $css !== '') {
            file_put_contents($dir . "$id.css", $css);
        }
    }
    echo "Componentes exportados para gestor/resources/componentes/\n";
}

function main() {
    exportar_layouts();
    exportar_paginas();
    exportar_componentes();
    echo "Exportação concluída!\n";
}

main();
