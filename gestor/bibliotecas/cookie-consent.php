<?php
/**
 * Decisão de cookies do visitante, lida no servidor (req-214).
 *
 * O aviso de cookies (módulo `cookie-consent`) guarda a decisão no cookie `c2f_consent` do próprio
 * site: `{ v: versão, t: instante, c: { categoria: bool } }`. Esta biblioteca dá ao PHP a mesma
 * resposta que `window.c2fConsent.has()` dá ao JavaScript, para um módulo condicionar um cookie
 * gravado no servidor à permissão da categoria.
 *
 * Sem decisão, só a categoria `necessary` é permitida.
 *
 * Ganchos (namespace `cookie-consent`):
 *  - filtro `permitido` (bool $permitido, string $categoria, array $estado): a palavra final sobre
 *    uma categoria. Um projeto pode, por exemplo, tratar uma categoria própria.
 */

const COOKIE_CONSENT_COOKIE = 'c2f_consent';

/**
 * @return array{decided: bool, categories: array<string, bool>}
 */
function cookie_consent_estado(){
	$estado = ['decided' => false, 'categories' => []];
	$bruto = $_COOKIE[COOKIE_CONSENT_COOKIE] ?? null;
	if(!is_string($bruto) || $bruto === '' || strlen($bruto) > 4096) return $estado;

	$dados = json_decode($bruto, true);
	if(!is_array($dados) || !isset($dados['c']) || !is_array($dados['c'])) return $estado;

	$estado['decided'] = true;
	foreach($dados['c'] as $categoria => $valor){
		if(is_string($categoria) && preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/D', $categoria)){
			$estado['categories'][$categoria] = ($valor === true);
		}
	}

	return $estado;
}

/**
 * O visitante permitiu esta categoria de cookies?
 *
 * @param string $categoria Identificador da categoria (ex.: `analytics`, `marketing`).
 */
function cookie_consent_permitido($categoria){
	$categoria = (string)$categoria;
	$estado = cookie_consent_estado();
	$permitido = ($categoria === 'necessary') || ($estado['decided'] && !empty($estado['categories'][$categoria]));

	// Um ouvinte com defeito, ou o gerenciador de ganchos sem banco, não pode derrubar a página nem
	// liberar o que o visitante não permitiu: vale a resposta lida do cookie.
	if(function_exists('hook_apply_filters')){
		try {
			$permitido = (bool)hook_apply_filters('cookie-consent', 'permitido', $permitido, $categoria, $estado);
		} catch (\Throwable $e) {
			error_log('COOKIE-CONSENT: filtro de permissão falhou');
		}
	}

	return $permitido;
}
