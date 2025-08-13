<?php

/**
 * Biblioteca de Funções de Idioma
 *
 * @version 1.0
 * @author Otavio Serra
 * @date 12/08/2025
 */

// Carrega o dicionário de idiomas
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

// Define o idioma padrão
$GLOBALS['lang'] = 'pt-br';
$GLOBALS['dicionario'] = carregar_dicionario($GLOBALS['lang']);

/**
 * Traduz uma chave de idioma.
 *
 * @param string $key A chave a ser traduzida.
 * @param array $replacements Um array associativo de placeholders e seus valores.
 * @return string O texto traduzido.
 */
function _($key, $replacements = []) {
    $text = isset($GLOBALS['dicionario'][$key]) ? $GLOBALS['dicionario'][$key] : $key;

    foreach ($replacements as $placeholder => $value) {
        $text = str_replace('{' . $placeholder . '}', $value, $text);
    }

    return $text;
}

/**
 * Define o idioma a ser usado.
 *
 * @param string $lang O código do idioma (ex: 'en', 'pt-br').
 */
function set_lang($lang) {
    $GLOBALS['lang'] = $lang;
    $GLOBALS['dicionario'] = carregar_dicionario($lang);
}
