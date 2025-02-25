<?php

/***********************************************************************************************************************************
	AgeOne Digital Marketing.
	Setor de Desenvolvimento de Sistemas - Desenvolvimento Para Web
	Contato: webmaster@age1.com.br
	
	B2Make
	
	Copyright (c) 2014 AgeOne Digital Marketing

	Permission is hereby granted, free of charge, to any person
	obtaining a copy of this software and associated documentation
	files (the "Software"), to deal in the Software without
	restriction, including without limitation the rights to use,
	copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following
	conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
	OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
	HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	OTHER DEALINGS IN THE SOFTWARE.
***********************************************************************************************************************************/

$_VERSAO_MODULO				=	'1.0.0';
$_LOCAL_ID					=	"updateconfigs";
$_PERMISSAO					=	false;
$_INCLUDE_UPDATES			=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../";
$_CAMINHO_MODULO_RAIZ		=	".";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

updates_autenticar();

function configs(){
	global $_USUARIO;
	global $_B2MAKE_URL;
	global $_SYSTEM;
	global $_HTML_META;
	
	$loja = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_loja',
			'id',
			'nome',
			'descricao',
			'email',
			'endereco',
			'numero',
			'complemento',
			'bairro',
			'cidade',
			'uf',
			'pais',
			'cnpj',
			'telefone',
			'logomarca',
			'versao',
			'voucher_sem_para_presente',
			'identificacao_voucher',
			'voucher_sem_escolha_tema',
			'url_continuar_comprando',
			'widget_loja',
			'esquema_cores',
			'fontes',
			'loja_url_cliente',
		))
		,
		"loja",
		"WHERE id_usuario='".$_USUARIO['id_usuario']."'"
	);
	
	if($loja){
		$loja[0]['logomarca'] = $_B2MAKE_URL . $loja[0]['logomarca'] . '?v=' . $loja[0]['versao'];
		
		$host = banco_select_name
		(
			banco_campos_virgulas(Array(
				'url',
				'url_files',
				'url_mobile',
				'google_analytic',
				'google_site_verification',
				'meta_global',
				'body_global',
				'javascript_global_published',
				'css_global_published',
				'global_version',
			))
			,
			"host",
			"WHERE id_usuario='".$_USUARIO['id_usuario']."'"
			." AND atual IS TRUE"
		);
		
		$site = banco_select_name
		(
			banco_campos_virgulas(Array(
				'pagina_favicon',
				'pagina_favicon_version',
			))
			,
			"site",
			"WHERE id_usuario='".$_USUARIO['id_usuario']."'"
			." AND id_site_pai IS NULL"
		);
		
		$generator = "	<meta name=\"generator\" content=\"".$_HTML_META['generator']."\">\n";
		
		return Array(
			'status' => 'Ok',
			'loja' => $loja[0],
			'host' => Array(
				'favicon' => $favicon,
				'generator' => $generator,
				'url' => $host[0]['url'],
				'url_mobile' => $host[0]['url_mobile'],
				'url_files' => $host[0]['url_files'],
				'google_analytic' => $host[0]['google_analytic'],
				'google_site_verification' => $host[0]['google_site_verification'],
				'javascript_global_published' => $host[0]['javascript_global_published'],
				'css_global_published' => $host[0]['css_global_published'],
				'global_version' => $host[0]['global_version'],
				'pagina_favicon' => $site[0]['pagina_favicon'],
				'pagina_favicon_version' => $site[0]['pagina_favicon_version'],
				'meta_global' => $host[0]['meta_global'],
				'body_global' => $host[0]['body_global'],
			),
		);
	} else {
		return Array(
			'error' => 'Loja não encontrada',
		);
	}
}

function start(){
	global $_HOST_VERSION;
	global $_LOCAL_ID;
	
	$opcao = $_REQUEST['option'];
	
	switch($opcao){
		//case 'configs': return configs(); break;
		default:
			return configs();
	}
}

function main(){
	$saida = start();
	
	header('Content-Type: text/xml; charset=UTF-8;');
	echo updates_format_xml($saida);
}

main();

?>