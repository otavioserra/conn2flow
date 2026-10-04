<?php

/** req-227: only expose locally installed covers with a safe module identifier. */
function dashboard_capa_modulo_url($id, $urlRaiz, $raizPath = null){
	if(!is_string($id) || !preg_match('/\A[a-z0-9][a-z0-9-]*\z/D', $id)){
		return '';
	}
	$raizPath = $raizPath ?? dirname(__DIR__, 2);
	$relativo = 'assets/modulos/covers/'.$id.'.webp';
	$arquivo = rtrim($raizPath, '/\\').'/'.$relativo;
	if(!is_file($arquivo)){
		return '';
	}
	return rtrim($urlRaiz, '/').'/modulos/covers/'.$id.'.webp?v='.filemtime($arquivo);
}

/** Preserve the existing SVG slot and its sizing in Fomantic and Tailwind cards. */
function dashboard_capa_modulo_svg($url){
	$href = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" aria-hidden="true" focusable="false"><image href="'.$href.'" width="1024" height="1024" /></svg>';
}
