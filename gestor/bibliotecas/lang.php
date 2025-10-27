<?php

/**
 * Biblioteca de Idiomas - Funções para internacionalização (i18n)
 *
 * Sistema de tradução baseado em dicionários JSON, permitindo suporte
 * a múltiplos idiomas sem dependência de gettext.
 *
 * @version 1.0
 * @author Otavio Serra
 * @date 12/08/2025
 */

/**
 * Carrega o dicionário de traduções de um idioma.
 *
 * Busca e carrega o arquivo JSON de tradução para o idioma especificado.
 * O arquivo deve estar no formato: {idioma}.json (ex: pt-br.json, en.json).
 *
 * @param string $lang Código do idioma a ser carregado (ex: 'pt-br', 'en'). Padrão: 'pt-br'.
 * @param string $base Caminho base relativo onde os arquivos de idioma estão localizados. Padrão: ''.
 * @return array Retorna array associativo com as traduções ou array vazio se o arquivo não existir.
 */
if (!function_exists('carregar_dicionario')) {
    function carregar_dicionario($lang = 'pt-br', $base = '') {
        $dicionario = [];
        
        // Monta o caminho absoluto para o arquivo de idioma
        $caminhoBase = realpath(__DIR__ . $base) . '/';
        $caminhoArquivo = $caminhoBase . $lang . '.json';

        // Carrega e decodifica o JSON se o arquivo existir
        if (file_exists($caminhoArquivo)) {
            $jsonContent = file_get_contents($caminhoArquivo);
            $dicionario = json_decode($jsonContent, true);
        }

        return $dicionario;
    }
}

// Define o idioma padrão global apenas se ainda não estiver definido
if (!isset($GLOBALS['lang'])) {
    $GLOBALS['lang'] = 'pt-br';
}

// Carrega o dicionário padrão apenas se ainda não estiver carregado
if (!isset($GLOBALS['dicionario'])) {
    $GLOBALS['dicionario'] = carregar_dicionario($GLOBALS['lang']);
}


/**
 * Traduz uma chave de idioma usando o dicionário customizado.
 *
 * Busca a tradução no dicionário carregado e aplica substituições de placeholders.
 * Se a chave não existir, retorna a própria chave como fallback.
 * 
 * Suporta dois formatos de placeholders: {nome} e :nome
 *
 * @param string $key Chave de tradução a ser buscada no dicionário.
 * @param array $replacements Array associativo de substituições [placeholder => valor]. Padrão: [].
 * @return string Retorna o texto traduzido com placeholders substituídos.
 * 
 * @example __t('welcome_message', ['name' => 'João']) retorna "Bem-vindo, João!"
 */
if (!function_exists('__t')) {
    function __t($key, $replacements = []) {
        // Busca a tradução no dicionário ou usa a própria chave se não encontrada
        $text = isset($GLOBALS['dicionario'][$key]) ? $GLOBALS['dicionario'][$key] : $key;
        
        // Aplica todas as substituições de placeholders
        foreach ($replacements as $placeholder => $value) {
            // Substitui tanto {placeholder} quanto :placeholder
            $text = str_replace(['{' . $placeholder . '}', ':' . $placeholder], $value, $text);
        }
        
        return $text;
    }
}

/**
 * Define o idioma ativo do sistema.
 *
 * Altera o idioma global e recarrega o dicionário correspondente.
 * Todas as chamadas subsequentes de __t() usarão o novo idioma.
 *
 * @param string $lang Código do idioma a ser ativado (ex: 'en', 'pt-br', 'es').
 * @return void
 */
if (!function_exists('set_lang')) {
    function set_lang($lang) {
        // Define o novo idioma global
        $GLOBALS['lang'] = $lang;
        
        // Carrega o dicionário do novo idioma
        $GLOBALS['dicionario'] = carregar_dicionario($lang);
    }
}
