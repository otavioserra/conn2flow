<?php

/**
 * Biblioteca de Funções de Idioma
 *
 * @version 1.0
 * @author Otavio Serra
 * @date 12/08/2025
 */

// Carrega o dicionário de idiomas
if (!function_exists('carregar_dicionario')) {
    function carregar_dicionario($lang = 'pt-br', $base = '') {
        $dicionario = [];
        $caminhoBase = realpath(__DIR__ . $base) . '/';
        $caminhoArquivo = $caminhoBase . $lang . '.json';

        if (file_exists($caminhoArquivo)) {
            $jsonContent = file_get_contents($caminhoArquivo);
            $dicionario = json_decode($jsonContent, true);
        }

        return $dicionario;
    }
}

// Define o idioma padrão apenas se ainda não definido
if (!isset($GLOBALS['lang'])) {
    $GLOBALS['lang'] = 'pt-br';
}
if (!isset($GLOBALS['dicionario'])) {
    $GLOBALS['dicionario'] = carregar_dicionario($GLOBALS['lang']);
}


/**
 * Traduz uma chave de idioma (sempre usando dicionário customizado, nunca gettext).
 * @param string $key
 * @param array $replacements
 * @return string
 */
if (!function_exists('__t')) {
    function __t($key, $replacements = []) {
        $text = isset($GLOBALS['dicionario'][$key]) ? $GLOBALS['dicionario'][$key] : $key;
        foreach ($replacements as $placeholder => $value) {
            // Substitui tanto {placeholder} quanto :placeholder
            $text = str_replace(['{' . $placeholder . '}', ':' . $placeholder], $value, $text);
        }
        return $text;
    }
}

/**
 * Define o idioma a ser usado.
 *
 * @param string $lang O código do idioma (ex: 'en', 'pt-br').
 */
if (!function_exists('set_lang')) {
    function set_lang($lang) {
        $GLOBALS['lang'] = $lang;
        $GLOBALS['dicionario'] = carregar_dicionario($lang);
    }
}
