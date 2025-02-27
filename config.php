<?php
/***********************************************************************************************************************************
	AgeOne Digital Marketing.
	Setor de Desenvolvimento de Sistemas - Desenvolvimento Para Web
	Contato: webmaster@ageone.com.br
	
	Copyright (c) 2012 AgeOne Digital Marketing

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

//------------------------------------------------ Variáveis globais -----------------------------------------------------

$_VERSAO								=		'4.2.6';

$_URL									=		'.';
$_URL_REFERER							=		$_SERVER[HTTP_REFERER];
$_SERVER_NAME							=		$_SERVER[SERVER_NAME];
$_URL_FULL								=		$_SERVER[HTTP_REFERER] . $_URL;
$_REMOTE_ADDR							=		$_SERVER[REMOTE_ADDR];
$_DOCUMENT_ROOT							=		$_SERVER[DOCUMENT_ROOT];

$_SYSTEM['SEPARADOR']					=		DIRECTORY_SEPARATOR;
$_SYSTEM['DOMINIO']						=		$_SERVER["HTTP_HOST"];
$_SYSTEM['INCLUDE_PATH']				=		"includes/";
$_SYSTEM['INCLUDE_PROJETO_PATH']		=		"files/projeto/";
$_SYSTEM['SMTP_FORCE_HOST']				=		"localhost";
$_SYSTEM['TMP']							=		sys_get_temp_dir().$_SYSTEM['SEPARADOR'];

$_PALAVRAS_RESERVADAS					=		Array('ecommerce-indisponivel','endereco-entrega-salvar','endereco-entrega','calcular-frete','ecommerce-cupom','paypal-cancelurl','paypal-returnurl','paypal-cancelado','paypal-notificacoes','paypal-pagar','paypal-retorno','voucher-concluir','duvidas-enviar','duvidas','indique-enviar','indique','minha-conta-banco','minha-conta-usuario','minha-conta-email','minha-conta-historico','minha-conta','pagar-pedidos','meus-pedidos','loja-online','login-facebook','pagseguro-notificacoes','pagseguro-retorno','pagseguro-pagar','voucher-temas','voucher-enviar-email','voucher-pedidos','voucher-presente','voucher-form-presente','voucher','pagamento','cadastro-validar','autenticar-cadastro','form-autenticar','autenticar','carrinho','redefinir_senha_banco','gerar-nova-senha','esqueceu_senha_banco','esqueceu-sua-senha','eventos','nl','entrar','logout','login','logar','emarkenting','menu_procurar','procurar','contato_banco','menu_blog','menu_noticias','conteudo','_maquina_testes','galerias-imagens','galerias-videos','galeria-img-facebook');

//--------------------------------------- Bibliotecas Inclusas ---------------------------------------------------------

include($_SYSTEM['INCLUDE_PROJETO_PATH']."config.php");
include($_SYSTEM['INCLUDE_PATH']."geral.php");
include($_SYSTEM['INCLUDE_PATH']."banco.php");
include($_SYSTEM['INCLUDE_PATH']."pagina.php");
include($_SYSTEM['INCLUDE_PATH']."seguranca.php");
include($_SYSTEM['INCLUDE_PATH']."modelo.php");
include($_SYSTEM['INCLUDE_PATH']."html.php");
include($_SYSTEM['INCLUDE_PATH']."formulario.php");
if($_PERMISSAO)include($_SYSTEM['INCLUDE_PATH']."ftp.php");

if($_INCLUDE_MAILER){
	include($_SYSTEM['INCLUDE_PATH']."php/phpmailer/class.phpmailer.php");
	include($_SYSTEM['INCLUDE_PATH']."php/phpmailer/class.smtp.php");
}
if($_INCLUDE_SLICER){			include($_SYSTEM['INCLUDE_PATH']."php/imageSlicer/slicer.php");}
if($_INCLUDE_INTERFACE){		include($_SYSTEM['INCLUDE_PATH']."interface.php");}
if($_INCLUDE_PROCURAR){			include($_SYSTEM['INCLUDE_PATH']."procurar.php");}
if($_INCLUDE_CONTEUDO){			include($_SYSTEM['INCLUDE_PATH']."conteudo.php");}
if($_INCLUDE_ADMIN_CONTEUDO){	include($_SYSTEM['INCLUDE_PATH']."admin-conteudo.php");}
if($_INCLUDE_MOBILE_INTERFACE || $_INCLUDE_MOBILE_INTERFACE2){	include($_SYSTEM['INCLUDE_PATH']."php/mobileDetect/mdetect.php");}

$_ECOMMERCE['permissao_usuario']	=	'3';
$_ECOMMERCE['pagina_padrao']	=	'meus-pedidos';

$_ADMIN_CONTEUDO_CAMPOS = Array(
	'identificador',
	'titulo',
	'titulo_img',
	'sub_titulo',
	'keywords',
	'texto',
	'imagem_pequena',
	'imagem_grande',
	'musica',
	'link_externo',
	'data',
	'galeria',
	'parametros',
	'videos_youtube',
	'identificador_auxiliar',
	'galeria_grupo',
	'videos',
	'videos_grupo',
	'texto2',
	'author',
	'servico',
	'produto',
);

$_ADMIN_CONTEUDO_CAMPOS_SEM_PERMISSAO = Array(
	'conteiner_posicao_x',
	'conteiner_posicao_y',
);

$_ADMIN_CONTEUDO_CAMPOS_EXTRA = Array(
	'addthis',
	'no_search',
	'no_robots',
	'layout_status',
	'no_layout',
	'galeria_todas',
	'videos_todas',
	'conteudos_relacionados',
	'menu_principal',
	'menu_sitemap',
	'titulo_img_recorte_y',
	'imagem_pequena_recorte_y',
	'imagem_grande_recorte_y',
	'conteiner_posicao',
	'comentarios',
	'menu_navegacao',
	'comentarios_facebook',
	'categoria',
);

$_ADMIN_CONTEUDO_CAMPOS_EXTRA_TEXTO = Array(
	'layout',
	'titulo_img_width',
	'titulo_img_height',
	'titulo_img_filters',
	'titulo_img_mask',
	'imagem_pequena_width',
	'imagem_pequena_height',
	'imagem_pequena_filters',
	'imagem_pequena_mask',
	'imagem_grande_width',
	'imagem_grande_height',
	'imagem_grande_filters',
	'imagem_grande_mask',
	'conteiner_posicao_efeito',
	'conteiner_posicao_tempo',
	'cont_padrao_posicao_x',
	'cont_padrao_posicao_y',
);

$_CONTEUDO_VARS = Array(
	'id_conteudo',
	'titulo',
	'titulo_img',
	'sub_titulo',
	'texto',
	'imagem_pequena',
	'imagem_grande',
	'musica',
	'link_externo',
	'data',
	'identificador',
	'keywords',
	'galeria',
	'parametros',
	'galeria_grupo',
	'videos',
	'videos_grupo',
	'texto2',
	'author',
	'servico',
	'produto',
);

$_CONTEUDO_VARS_SEM_PERMISSAO = Array(
	'conteiner_posicao_x',
	'conteiner_posicao_y',
);

//--------------------------------------- Variáveis das Páginas HTML ---------------------------------------------------------

function variavel_global(){
	global $_HTML;
	global $_HTML_META;
	global $_SYSTEM;
	global $_DOCUMENT_ROOT;
	global $_DEBUG;
	global $_AJAX_PAGE;
	global $_PROJETO_JS;
	global $_LAYOUT_BASICO;
	global $_VARS;
	
	if(!$_SESSION[$_SYSTEM['ID'].'variaveis_globais']){
		banco_conectar();
		$variaveis_globais = banco_select
		(
			"valor,grupo,variavel",
			"variavel_global",
			""
		);
		banco_fechar_conexao();
		
		foreach($variaveis_globais as $variavel_global){
			if($variavel_global['grupo'] == 'html'){
				$_HTML[$variavel_global['variavel']] = $variavel_global['valor'];
			} else if($variavel_global['grupo'] == 'html_meta'){
				$_HTML_META[$variavel_global['variavel']] = $variavel_global['valor'];
			} else if($variavel_global['grupo'] == 'system'){
				$_SYSTEM[$variavel_global['variavel']] = $variavel_global['valor'];
			}
			
			$_VARS[$variavel_global['grupo']][$variavel_global['variavel']] = $variavel_global['valor'];
			
			switch($variavel_global['variavel']){
				case 'ADMIN_EMAIL':
					$_SYSTEM['ADMIN_EMAIL_HTML']	=		'<a href="mailto:'.$variavel_global['valor'].'">'.$variavel_global['valor'].'</a>';
				break;
				case 'DEBUG': if($variavel_global['valor'])$_DEBUG = true;break;
				
			}
		}
		
		$_SYSTEM['SITE_ROOT'] = root_sistema();
		
		$_SESSION[$_SYSTEM['ID'].'variaveis_globais'] = Array(
			'HTML' => $_HTML,
			'HTML_META' => $_HTML_META,
			'SYSTEM' => $_SYSTEM,
			'VARS' => $_VARS,
		);
	} else {
		$variaveis_globais = $_SESSION[$_SYSTEM['ID'].'variaveis_globais'];
		
		$_SYSTEM = $variaveis_globais['SYSTEM'];
		$_HTML = $variaveis_globais['HTML'];
		$_HTML_META = $variaveis_globais['HTML_META'];
		$_VARS = $variaveis_globais['VARS'];
	}
	
	$_SYSTEM['ROOT'] = root_sistema();
	$_SYSTEM['PATH'] = $_DOCUMENT_ROOT . $_SYSTEM['SEPARADOR'];
	$aux = explode('/',$_SYSTEM['ROOT']);
	foreach($aux as $valor){
		if($valor){
			$_SYSTEM['PATH'] .= $valor . $_SYSTEM['SEPARADOR'];
		}
	}
	
	$_SYSTEM['PROJETO_PATH'] = $_SYSTEM['PATH'].'files'.$_SYSTEM['SEPARADOR'].'projeto'.$_SYSTEM['SEPARADOR'];
	$_SYSTEM['PROJETO_ROOT'] = '/'.$_SYSTEM['ROOT'].'files/projeto/';
	
	$_SYSTEM['TEMA_PATH'] = $_SYSTEM['PROJETO_PATH'];
	$_SYSTEM['TEMA_ROOT'] = $_SYSTEM['PROJETO_ROOT'];
	
	$_HTML['ADMIN'] = 'admin/';
	
	if($_SYSTEM['INSTALL']){
		if(!$_SYSTEM['SMTP_PASS']){
			banco_conectar();
			banco_update
			(
				"valor='".senha_gerar(12)."'",
				"variavel_global",
				"WHERE variavel='SMTP_PASS'"
			);
			banco_fechar_conexao();
		}
	}
	
	if($_REQUEST['ajax_page'])$_AJAX_PAGE = true;
	if($_REQUEST['projeto_js'])$_PROJETO_JS = json_decode($_REQUEST['projeto_js'],true);
	if($_REQUEST['layout_basico'])$_LAYOUT_BASICO = true;
	
	if($_AJAX_PAGE)
	foreach($_REQUEST as $key => $valor){
		$_REQUEST[$key] = utf8_decode($valor);
	}
}

function javascript_vars(){
	global $_LOCAL_ID;
	global $_HTML;
	global $_JS;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_PERMISSAO;
	global $_MOBILE;
	global $_INCLUDE_HISTORY;
	global $_INCLUDE_ADDTHIS;
	global $_VERSAO;
	global $_JS_TOOLTIP_INICIO;
	global $_SYSTEM;
	
	if($_MOBILE){
		$_HTML['js'] = false;
	} else {
	
	$_JS['jPlayer'] .= '	<link rel="stylesheet" type="text/css" media="all" href="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'jPlayer/blue.monday/jplayer.blue.monday.css?v='.$_VERSAO.'" />'."\n";
	$_JS['jPlayer'] .= '	<script type="text/javascript" src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'jPlayer/jquery.jplayer.min.js?v='.$_VERSAO.'"></script>'."\n";
	
	$_JS['CodeMirror'] .= '	<link rel="stylesheet" type="text/css" media="all" href="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'codemirror-2.36/lib/codemirror.css?v='.$_VERSAO.'" />'."\n";
	$_JS['CodeMirror'] .= '	<script type="text/javascript" src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'codemirror-2.36/lib/codemirror.js?v='.$_VERSAO.'"></script>'."\n";
	$_JS['CodeMirror'] .= '	<script type="text/javascript" src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'codemirror-2.36/mode/xml/xml.js?v='.$_VERSAO.'"></script>'."\n";
	$_JS['CodeMirror'] .= '	<script type="text/javascript" src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'codemirror-2.36/mode/javascript/javascript.js?v='.$_VERSAO.'"></script>'."\n";
	$_JS['CodeMirror'] .= '	<script type="text/javascript" src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'codemirror-2.36/mode/css/css.js?v='.$_VERSAO.'"></script>'."\n";
	$_JS['CodeMirror'] .= '	<link rel="stylesheet" type="text/css" media="all" href="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'codemirror-2.36/theme/cobalt.css?v='.$_VERSAO.'" />'."\n";
	$_JS['CodeMirror'] .= '	<script type="text/javascript" src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'codemirror-2.36/mode/htmlmixed/htmlmixed.js?v='.$_VERSAO.'"></script>'."\n";

	$_JS['jquery.center'] .= '	<script type="text/javascript" src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'jquery.center/jquery.center.js?v='.$_VERSAO.'"></script>'."\n";

	$_JS['prettyPhoto'] = '	<link rel="stylesheet" type="text/css" media="all" href="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'prettyPhoto/css/prettyPhoto.css?v='.$_VERSAO.'" />';
	$_JS['prettyPhoto'] .= '	<script type="text/javascript" src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'prettyPhoto/js/jquery.prettyPhoto.js?v='.$_VERSAO.'"></script>';
	
	$_JS['jquery-tweet'] .= '	<script type="text/javascript" src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'jquery-tweet/jquery.tweet.js?v='.$_VERSAO.'"></script>'."\n";
	$_JS['jquery-tweet'] .= '	<link rel="stylesheet" type="text/css" media="all" href="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'jquery-tweet/jquery.tweet.css?v='.$_VERSAO.'" />'."\n";

	$_JS['menu'] = '
	<link rel="stylesheet" type="text/css" href="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'superfish/css/superfish.css?v='.$_VERSAO.'" />
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'superfish/js/hoverIntent.js?v='.$_VERSAO.'" type="text/javascript"></script>
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'superfish/js/superfish.js?v='.$_VERSAO.'" type="text/javascript"></script>'."\n";

	$_JS['maskedInput'] = '<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'maskedInput/jquery.maskedinput-1.3.min.js?v='.$_VERSAO.'" type="text/javascript"></script>
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'maskedInput/jquery.maskMoney.0.2.js?v='.$_VERSAO.'" type="text/javascript"></script>'."\n";
	
	$_JS['jquery.elevatezoom'] = '
	<link rel="stylesheet" type="text/css" href="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'jquery.elevatezoom/jquery.fancybox.css?v='.$_VERSAO.'" />
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'jquery.elevatezoom/jquery.elevateZoom-3.0.8.min.js?v='.$_VERSAO.'" type="text/javascript"></script>
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'jquery.elevatezoom/jquery.fancybox.pack.js?v='.$_VERSAO.'" type="text/javascript"></script>'."\n";

	$_JS['alphaNumeric'] = '
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'alphaNumeric/jquery.alphanumeric.pack.js?v='.$_VERSAO.'" type="text/javascript"></script>'."\n";

	$_JS['jquery_ui'] = '	<link rel="stylesheet" type="text/css" media="all" href="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'jquery_ui/css/'.($_PERMISSAO ? 'admin':'custom-theme').'/jquery-ui-1.8.4.custom.css?v='.$_VERSAO.'" />
	<script type="text/javascript" src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'jquery_ui/js/jquery-ui-1.8.17.custom.min.js?v='.$_VERSAO.'"></script>'."\n";

	$_JS['jquery_ui_effects'] = '	<script type="text/javascript" src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'jquery_ui_effects/jquery-ui-1.8.21.custom.min.js?v='.$_VERSAO.'"></script>'."\n";

	$_JS['checkTree'] = '	<script type="text/javascript" src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'checkTree/jquery.checktree_yctin.js?v='.$_VERSAO.'"></script>
	<link rel="stylesheet" type="text/css" media="all" href="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'checkTree/jquery.checktree.css?v='.$_VERSAO.'" />'."\n";

	$_JS['tinyMce'] = '
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'tiny_mce/jquery.tinymce.js?v='.$_VERSAO.'" type="text/javascript"></script>'."\n";
	
	/* $_JS['tinyMce'] = '
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'tinyMce/jquery.tinymce.min.js?v='.$_VERSAO.'" type="text/javascript"></script>'."\n";
 */
	$_JS['imageCycle'] = '
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'imageCycle/jquery.cycle.all.js?v='.$_VERSAO.'" type="text/javascript"></script>'."\n";

	$_JS['swfUpload'] = '
	<link rel="stylesheet" type="text/css" href="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'swfupload/swfupload/swfupload.css?v='.$_VERSAO.'" />
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'swfupload/swfupload/swfupload.js?v='.$_VERSAO.'" type="text/javascript"></script>
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'swfupload/js/jquery.swfupload.js?v='.$_VERSAO.'" type="text/javascript"></script>'."\n";

	$_JS['gallerific'] = '
	<link rel="stylesheet" type="text/css" href="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'galleriffic-2.0/css/galleriffic.css?v='.$_VERSAO.'" />
	<!--[if IE]>
	<link rel="stylesheet" type="text/css" href="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'galleriffic-2.0/css/galleriffic_ie.css?v='.$_VERSAO.'" />
	<![endif]-->
	<link rel="stylesheet" type="text/css" href="'.$_CAMINHO_RELATIVO_RAIZ.'files/galleriffic-2.0/css/galleriffic_resolucao.css?v='.$_VERSAO.'" />
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'galleriffic-2.0/js/jquery.galleriffic.js?v='.$_VERSAO.'" type="text/javascript"></script>
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'galleriffic-2.0/js/jquery.opacityrollover.js?v='.$_VERSAO.'" type="text/javascript"></script>
	<script type="text/javascript">
		document.write(\'<style>.noscript { display: none; }</style>\');
	</script>
	';

	$_JS['tips'] = '	<link rel="stylesheet" type="text/css" href="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'jquery-tooltip/jquery.tooltip.'.($_JS_TOOLTIP_INICIO?'inicio.':'').'css?v='.$_VERSAO.'" />'."\n";
	$_JS['tips'] .= '	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'jquery-tooltip/jquery.tooltip.pack.js?v='.$_VERSAO.'" type="text/javascript"></script>'."\n";

	$_JS['colorbox'] = '	<link rel="stylesheet" type="text/css" href="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'colorbox/colorbox.css?v='.$_VERSAO.'" />'."\n";
	$_JS['colorbox'] .= '	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'colorbox/jquery.colorbox-min.js?v='.$_VERSAO.'" type="text/javascript"></script>'."\n";

	$_JS['jQueryPassStrengthMeter'] = '
	<link rel="stylesheet" type="text/css" href="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'jQueryPassStrengthMeter/passwordStrengthMeter.css?v='.$_VERSAO.'" />
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'jQueryPassStrengthMeter/passwordStrengthMeter.js?v='.$_VERSAO.'" type="text/javascript"></script>'."\n";

	$_JS['ie6'] = '
	<!--[if lte IE 6]><script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'ie6/warning.js?v='.$_VERSAO.'"></script><script>window.onload=function(){e("'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'ie6/")}</script><![endif]-->'."\n";

	$_JS['history'] = '
	<script>if ( typeof window.JSON === \'undefined\' ) { document.write(\'<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'history/json2.js?v='.$_VERSAO.'"><\/script>\'); }</script>
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'history/history.adapter.jquery.js?v='.$_VERSAO.'" type="text/javascript"></script>
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'history/history.js?v='.$_VERSAO.'" type="text/javascript"></script>
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'history/history.html4.js?v='.$_VERSAO.'" type="text/javascript"></script>'."\n";

	$_JS['recaptcha'] = '	<script src="https://www.google.com/recaptcha/api/js/recaptcha_ajax.js?v='.$_VERSAO.'" type="text/javascript"></script>'."\n";
	
	$_JS['jstorage'] = '
	<script src="'.$_CAMINHO_RELATIVO_RAIZ.$_HTML['JS_PATH'].'jstorage/jstorage.js?v='.$_VERSAO.'" type="text/javascript"></script>'."\n";
		
		$_HTML['js'] = false;
		
		if($_PERMISSAO)$_HTML['js_padrao'] .= $_JS['jquery_ui'].$_JS['jquery.center'];
		$_HTML['js_padrao'] .= $_JS['ie6'];
		if($_INCLUDE_HISTORY)$_HTML['js_padrao'] .= $_JS['history'];
		$_HTML['js_padrao'] .= $_JS['tips'];
	}
}

function html_vars(){
	global $_LOCAL_ID;
	global $_HTML;
	global $_SYSTEM;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_CAMINHO_MODULO_RAIZ;
	global $_INCLUDE_INTERFACE;
	global $_PERMISSAO;
	global $_MOBILE;
	global $_VERSAO;
	global $_PROJETO_VERSAO;
	global $_PROJETO;
	
	$_HTML['TITULO_ANTES']		=	$_HTML['TITULO'];
	$_HTML['titulo']			=	$_HTML['TITULO'].$_HTML['TITULO_SEPARADOR'];
	$_HTML['sub_titulo']		=	$_HTML['SUB_TITULO'];
	
	$css						=	$_HTML['PADRAO_CSS'];
	$css2						=	$_HTML['LAYOUT_CSS'];
	$css3						=	$_HTML['PADRAO_CSS_ADMIN'];
	$css4						=	$_HTML['LAYOUT_CSS_ADMIN'];
	$js							=	$_HTML['PADRAO_JS'];
	$js_mobile					=	$_HTML['PADRAO_JS_MOBILE'];
	$jquery						=	$_HTML['JQUERY'];
	$jquery_mobile				=	$_HTML['JQUERY-MOBILE'];
	$jquery_mobile_css			=	$_HTML['JQUERY-MOBILE-CSS'];
	
	if($_PERMISSAO){
		$css = $_CAMINHO_RELATIVO_RAIZ . $css3;
		$css2 = $_CAMINHO_RELATIVO_RAIZ . $css4;
	} else {
		$css = $_CAMINHO_RELATIVO_RAIZ . $css;
		$css2 = $_CAMINHO_RELATIVO_RAIZ . $css2;
	}
	
	$js = $_CAMINHO_RELATIVO_RAIZ . $js;
	$jquery = $_CAMINHO_RELATIVO_RAIZ . $jquery;
	$js_mobile = $_CAMINHO_RELATIVO_RAIZ . $js_mobile;
	$jquery_mobile_css = $_CAMINHO_RELATIVO_RAIZ . $jquery_mobile_css;
	$jquery_mobile = $_CAMINHO_RELATIVO_RAIZ . $jquery_mobile;
	
	$_HTML['js_padrao']			=	'	<script type="text/javascript" src="'.$jquery.'?v='.$_VERSAO.'"></script>'."\n";
	
	if($_MOBILE){
		if($_PROJETO['config']['js_padrao']){
			$_HTML['js_padrao']			=	$_PROJETO['config']['js_padrao'];
		} else {
			$_HTML['js_padrao']			.=	'	<script type="text/javascript" src="'.$jquery_mobile.'?v='.$_VERSAO.'"></script>'."\n";
			$_HTML['js_padrao']			.=	'	<script type="text/javascript" src="'.$js_mobile.'?v='.$_VERSAO.'"></script>'."\n";
		}
		
		if($_PROJETO['config']['css_padrao']){
			$_HTML['css_padrao']			=	$_PROJETO['config']['css_padrao'];
		} else {
			$_HTML['css_padrao']			.=	'	<link href="'.$jquery_mobile_css.'?v='.$_VERSAO.'" rel="stylesheet" type="text/css" />'."\n";
		}
		
		javascript_vars();
		$_HTML['css'] = false;
	} else {
		$_HTML['js_padrao']			.=	'	<script type="text/javascript" src="'.$js.'?v='.$_VERSAO.'"></script>'."\n";
		if($_PERMISSAO)$_HTML['js_padrao']			.=	'<script type="text/javascript" src="'.($_CAMINHO_RELATIVO_RAIZ != '.' ? $_CAMINHO_RELATIVO_RAIZ : '').'includes/js/admin.js?v='.$_VERSAO.'"></script>'."\n";
		
		$_HTML['css_padrao']			=	'	<link href=\'http://fonts.googleapis.com/css?family=Ubuntu:400,700,400italic,700italic\' rel=\'stylesheet\' type=\'text/css\'>'."\n";
		$_HTML['css_padrao']			.=	'	<link href="'.$css.'?v='.$_VERSAO.'" rel="stylesheet" type="text/css" />'."\n";
		$_HTML['css_padrao']			.=	'	<link href="'.$css2.'?v='.(!$_PERMISSAO?$_PROJETO_VERSAO:$_VERSAO).'" rel="stylesheet" type="text/css" />'."\n";
		
		javascript_vars();
		$_HTML['css'] = false;
		
		if($_REQUEST['opcao'])		$_HTML['flashvars'] = 'opcao='.$_REQUEST['opcao'];
		
		if($_PERMISSAO){
			$permissao_modulos = $_SESSION[$_SYSTEM['ID']."modulos"];
			
			banco_conectar();
			$modulos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_modulo',
					'id_modulo_pai',
					'nome',
					'caminho',
					'titulo',
					'imagem',
				))
				,
				"modulo",
				"WHERE status='A'"
				." ORDER BY nome ASC"
			);
			banco_fechar_conexao();
			
			
			if(!$_SESSION[$_SYSTEM['ID']."cms-logomarca"]){
				$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR'];
				$caminho_internet 		= 	"files/";
				
				$nome_arquivo = 'logomarca-cms.jpg';if(is_file($caminho_fisico . $nome_arquivo)){$find = true;$_SESSION[$_SYSTEM['ID']."cms-logomarca-file"] = $caminho_internet.$nome_arquivo;}
				$nome_arquivo = 'logomarca-cms.png';if(!$find)if(is_file($caminho_fisico . $nome_arquivo)){$find = true;$_SESSION[$_SYSTEM['ID']."cms-logomarca-file"] = $caminho_internet.$nome_arquivo;}
				$nome_arquivo = 'logomarca-cms.gif';if(!$find)if(is_file($caminho_fisico . $nome_arquivo))$_SESSION[$_SYSTEM['ID']."cms-logomarca-file"] = $caminho_internet.$nome_arquivo;
				
				$_SESSION[$_SYSTEM['ID']."cms-logomarca"] = true;
			}
			
			if($_SESSION[$_SYSTEM['ID']."cms-logomarca-file"])$logomarca = path_com_versao_arquivo($_SESSION[$_SYSTEM['ID']."cms-logomarca-file"]);
			
			if($_SESSION[$_SYSTEM['ID']."admin"]){
				$_HTML['menu'] = "<a id=\"_lay-logomarca\" title=\"Clique para definir uma imagem logomarca da sua empresa\" href=\"/".$_SYSTEM['ROOT']."admin/?opcao=mudar_logo\">".($logomarca? "<img border=\"0\" src=\"".$logomarca."\">" : "Inserir a sua logomarca")."</a>";
			} else {
				$_HTML['menu'] = "<div id=\"_lay-logomarca2\">".($logomarca? "<img src=\"".$logomarca."\">" : "Inserir a sua logomarca")."</div>";
			}
			
			
			
			$_HTML['menu'] .= "\n<ul id=\"_lay-menu-lateral\">\n";
			$_HTML['menu'] .= "<!-- itens -->";
			$_HTML['menu'] .= "</ul>\n";
			$_HTML['menu'] .= "<div id=\"_lay-menu-lateral-base\"></div>\n";
			
			$linha_grade = 20;
			$coluna_grade = 20;
			
			$cel['item'] = "\n	<li><a href=\"#item_href\" title=\"#title#\"><div style=\"background-position:-#coluna#px -#linha#px;\"></div>#item_nome</a></li>";
			
			foreach($modulos as $modulo){
				if($modulo['id_modulo_pai']){
					if($permissao_modulos[$modulo['caminho']] || $_SESSION[$_SYSTEM['ID']."admin"]){
						if($modulo['nome'] != 'Sair'){
							if($modulo['imagem']){
								list($linha,$coluna) = explode(',',$modulo['imagem']);
							} else {
								$linha = 1;
								$coluna = 6;
							}
							
							(int)$linha;(int)$coluna;
							$linha--;$coluna--;
							
							$linha = $linha * $linha_grade;
							$coluna = $coluna * $coluna_grade;
							
							$cel_nome = 'item';
							$cel_aux2 = $cel[$cel_nome];
							
							$cel_aux2 = modelo_var_troca($cel_aux2,"#coluna#",$coluna);
							$cel_aux2 = modelo_var_troca($cel_aux2,"#linha#",$linha);
							
							$cel_aux2 = modelo_var_troca($cel_aux2,"#title#",$modulo['titulo']);
							$cel_aux2 = modelo_var_troca($cel_aux2,"#item_href",$_CAMINHO_RELATIVO_RAIZ.$_HTML['ADMIN'].$modulo['caminho']);
							$cel_aux2 = modelo_var_troca_tudo($cel_aux2,"#item_nome",$modulo['nome']);
							
							$_HTML['menu'] = modelo_var_in($_HTML['menu'],'<!-- itens -->',$cel_aux2);
						}
					}
				}
			}
			
			$_HTML['menu'] = modelo_var_troca_tudo($_HTML['menu'],'<!-- itens -->','');
		}
		
		if($_INCLUDE_INTERFACE) 	$_HTML['css'] .= '	<link href="'.$_CAMINHO_RELATIVO_RAIZ.'includes/css/interface.css?v='.$_VERSAO.'" rel="stylesheet" type="text/css" />'."\n";
	}
}

function html_layout($pagina){
	global $_SYSTEM_ID;
	global $_ALERTA;
	global $_LOCAL_ID;
	
	if($_LOCAL_ID == "index"){
		$params = Array(
			'pagina' => $pagina,
		);
		$pagina = projeto_layout($params);
	}
	
	$pagina = modelo_var_troca($pagina,"#alerta_php",$_ALERTA);
	
	return $pagina;
}

function mobile_detect(){
	global $_SYSTEM;
	global $_INCLUDE_MOBILE_INTERFACE;
	global $_INCLUDE_MOBILE_INTERFACE2;
	global $_MOBILE;
	global $_MOBILE_2;
	global $_PERMISSAO;
	
	if($_PERMISSAO){
		if($_SESSION[$_SYSTEM['ID'].'mobile']){
			$_SESSION[$_SYSTEM['ID'].'mobile'] = false;
		}
		if($_MOBILE){
			$_MOBILE = 0;
		}
	} else {
		if($_INCLUDE_MOBILE_INTERFACE){
			if($_REQUEST['mobile-versao-web']){
				$_SESSION[$_SYSTEM['ID'].'mobile-versao-web'] = true;
				$_SESSION[$_SYSTEM['ID'].'mobile'] = false;
			}
			
			if(!$_SESSION[$_SYSTEM['ID'].'mobile-versao-web']){
				if(!$_SESSION[$_SYSTEM['ID'].'mobile']){
					$uagent_obj = new uagent_info();
					
					if($uagent_obj->DetectMobileLong()){
						$_SESSION[$_SYSTEM['ID'].'mobile'] = 1;
					}
				}
			}
			
			if($_REQUEST['_mobile']){
				$_SESSION[$_SYSTEM['ID'].'mobile'] = 1;
			}
			
			if($_REQUEST['_nomobile']){
				$_SESSION[$_SYSTEM['ID'].'mobile'] = false;
			}
			
			$_MOBILE = $_SESSION[$_SYSTEM['ID'].'mobile'];
		}
		
		if($_INCLUDE_MOBILE_INTERFACE2){
			if(!$_SESSION[$_SYSTEM['ID'].'mobile2']){
				$uagent_obj = new uagent_info();
				
				if($uagent_obj->DetectMobileLong()){
					$_SESSION[$_SYSTEM['ID'].'mobile2'] = 1;
				}
			}
			
			$_MOBILE_2 = $_SESSION[$_SYSTEM['ID'].'mobile2'];
		}
	}
}

function config(){
	global $_PUBLICO;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_SYSTEM;
	global $_NO_SESSION;
	
	if(!$_NO_SESSION)session_start();
	
	variavel_global();

	if($_PUBLICO){
		$_CAMINHO_RELATIVO_RAIZ = '/'.$_SYSTEM['ROOT'];
	}

	if($_REQUEST["ativacao_seguranca"]){
		if(crypt($_REQUEST["senha"], $_SYSTEM['PASS']) == $_SYSTEM['PASS']){
			banco_conectar();
			banco_update
			(
				"valor='1'",
				"variavel_global",
				"WHERE grupo='system'"
				." AND variavel='ATIVO'"
			);
			banco_fechar_conexao();
			
			$_SYSTEM['ATIVO'] = 1;
		}
	}

	if($_SYSTEM['SESSION_TIME_OUT'])session_cache_expire((int)$_SYSTEM['SESSION_TIME_OUT']);

	seguranca();
	mobile_detect();
	html_vars();
}

config();

?>