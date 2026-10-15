<?php
/**
 * req-275: visibilidade e link direto privado das páginas, comum a `admin-paginas` e `publisher-pages`.
 *
 * O controle é independente do "sem permissão / acesso público": a página pode ser pública e não visível
 * (abre só pelo link) ou restrita e visível. A decisão no site fica em `gestor_roteador_pagina_visibilidade()`.
 */

/** As colunas da req-275 já existem? (Instalação sem a migração segue sem o controle.) */
function paginas_visibilidade_habilitada(){
	static $habilitada = null;
	if($habilitada === null){
		$habilitada = banco_campo_existe('visivel', 'paginas') && banco_campo_existe('hash_privado', 'paginas');
	}
	return $habilitada;
}

/** Base absoluta do site, a mesma do link "ver página" dos módulos (`#url#`). */
function paginas_visibilidade_url_base(){
	global $_GESTOR;

	return 'http'.(isset($_SERVER['HTTPS']) ? 's' : '').'://'.($_SERVER['SERVER_NAME'] ?? '').($_GESTOR['url-raiz'] ?? '/');
}

/**
 * Troca `#page-visibility#` no formulário pelo componente `page-visibility-tailwind` do módulo.
 *
 * @param string $pagina HTML do formulário.
 * @param array|null $registro Linha da página (`visivel`, `hash_privado`, `caminho`); null ao adicionar.
 * @param string $modulo Id do módulo (variáveis e componente).
 * @return string
 */
function paginas_visibilidade_formulario($pagina, $registro, $modulo){
	if(strpos($pagina, '#page-visibility#') === false) return $pagina;
	if(!paginas_visibilidade_habilitada()) return modelo_var_troca_tudo($pagina, '#page-visibility#', '');

	$visivel = $registro === null ? true : gestor_pagina_visivel($registro['visivel'] ?? null);
	$link = $registro === null ? '' : gestor_pagina_link_privado(paginas_visibilidade_url_base(), (string)($registro['caminho'] ?? ''), (string)($registro['hash_privado'] ?? ''));

	$componente = gestor_componente(['id' => 'page-visibility-tailwind', 'modulo' => $modulo]);
	$componente = modelo_var_troca_tudo($componente, '#visibility-checked#', $visivel ? 'checked' : '');
	$componente = modelo_var_troca_tudo($componente, '#visibility-link#', htmlspecialchars($link, ENT_QUOTES, 'UTF-8'));
	foreach([
		'#visibility-title#' => 'visibility-title',
		'#visibility-public-label#' => 'visibility-public-label',
		'#visibility-public-help#' => 'visibility-public-help',
		'#visibility-link-label#' => 'visibility-link-label',
		'#visibility-link-help#' => 'visibility-link-help',
		'#visibility-link-pending#' => 'visibility-link-pending',
		'#visibility-copy-label#' => 'visibility-copy-label',
		'#visibility-copied#' => 'visibility-copied',
		'#visibility-regenerate-label#' => 'visibility-regenerate-label',
		'#visibility-regenerate-confirm#' => 'visibility-regenerate-confirm',
	] as $marcador => $variavel){
		$texto = gestor_variaveis(['modulo' => $modulo, 'id' => $variavel]);
		$componente = modelo_var_troca_tudo($componente, $marcador, htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8'));
	}

	return modelo_var_troca_tudo($pagina, '#page-visibility#', $componente);
}

/**
 * Campos de `banco_insert_name` para a página nova: visibilidade do formulário e hash novo.
 *
 * @return array
 */
function paginas_visibilidade_campos_adicionar($dados){
	if(!paginas_visibilidade_habilitada()) return [];

	return [
		['visivel', !empty($dados['visivel']) ? '1' : '0', true],
		['hash_privado', gestor_pagina_hash_gerar()],
	];
}

/**
 * Trechos `SET` da edição: visibilidade alterada e hash novo quando pedido ou ausente.
 *
 * @param array $dados `$_REQUEST`.
 * @param mixed $visivelAntes Valor gravado de `visivel`.
 * @param mixed $hashAntes Valor gravado de `hash_privado`.
 * @return array Trechos prontos para `$editar['dados']`.
 */
function paginas_visibilidade_campos_editar($dados, $visivelAntes, $hashAntes){
	if(!paginas_visibilidade_habilitada()) return [];

	$sets = [];
	$visivel = !empty($dados['visivel']) ? '1' : '0';
	if((gestor_pagina_visivel($visivelAntes) ? '1' : '0') !== $visivel) $sets[] = "visivel=".$visivel;
	if(!gestor_pagina_hash_valido((string)$hashAntes) || ($dados['regenerar_hash_privado'] ?? '') === '1'){
		$sets[] = "hash_privado='".gestor_pagina_hash_gerar()."'";
	}

	return $sets;
}
