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

$_PROJETO_VERSAO							=	$_VERSAO;
$_HOST_VERSION								=	9;
$_PROJETO_DATA								=	2014;
$_AMBIENTE_TESTE							=	false;
$_AMBIENTE_TESTE_USER						=	'teste';
$_AMBIENTE_TESTE_PASS						=	'teste123';

$_B2MAKE_TESTES								=	false; // true | false
$_B2MAKE_TESTES2							=	false;
$_B2MAKE_TESTES_JS							=	false;
$_B2MAKE_TESTES_OPEN						=	false;
$_B2MAKE_TESTES_IPS							=	Array('177.68.177.73');
$_B2MAKE_SYSTEM_DISABLED					=	false;
$_B2MAKE_TESTES_URLS_PERMITIDAS				=	Array('store\/pagseguro-notifications','store\/paypal-notifications');
$_B2MAKE_PATH								=	"design";

//--------------------------------------- Variáveis do Banco de dados ------------------------------------------------

$_B2MAKE_PLUGINS = Array(
	Array('id' => 'texto-complexo' , 'carregando' => true , 'css' => true , 'callback' => true),
	Array('id' => 'services' , 'carregando' => true , 'css' => true , 'callback' => true),
	Array('id' => 'banners' , 'carregando' => true , 'css' => true , 'callback' => true),
	Array('id' => 'formularios' , 'carregando' => true , 'css' => true , 'callback' => true),
	Array('id' => 'google-maps' , 'carregando' => true , 'css' => true , 'callback' => true),
	Array('id' => 'galeria-imagens' , 'carregando' => true , 'css' => true , 'callback' => true),
	Array('id' => 'contents' , 'carregando' => true , 'css' => true , 'callback' => true),
	Array('id' => 'posts-filter' , 'carregando' => true , 'css' => true , 'callback' => true),
	Array('id' => 'menu-paginas' , 'carregando' => true , 'css' => true , 'callback' => true),
	Array('id' => 'progresso' , 'carregando' => true , 'css' => true , 'callback' => true),
	Array('id' => 'breadcrumbs' , 'carregando' => true , 'css' => true , 'callback' => true),
	Array('id' => 'codigo-html' , 'carregando' => true , 'css' => true , 'callback' => true),
	Array('id' => 'accordion' , 'carregando' => true , 'css' => true , 'callback' => true),
);

$_VARIAVEIS_JS['b2make_plugins'] = $_B2MAKE_PLUGINS;

$_B2MAKE_GESTOR_MENU = Array(
	Array('id' => 'dashboard', 'url' => 'dashboard', 'title' => 'dashboard'),
	Array('id' => 'store', 'url' => 'store/orders', 'title' => 'loja'),
	Array('id' => 'orders', 'parent-id' => 'store', 'url' => 'store/orders', 'title' => 'pedidos'),
	Array('id' => 'services', 'parent-id' => 'store', 'url' => 'store/services', 'title' => 'serviços'),
	Array('id' => 'customers', 'parent-id' => 'store', 'url' => 'store/customers', 'title' => 'clientes'),
	Array('id' => 'voucher-layouts', 'parent-id' => 'store', 'url' => 'store/voucher-layouts', 'title' => 'layout voucher'),
	Array('id' => 'orders-add', 'parent-id' => 'store', 'url' => 'store/orders-add', 'title' => 'criar pedidos'),
	Array('id' => 'content', 'url' => 'content', 'title' => 'conteúdos'),
	Array('id' => 'content-new', 'parent-id' => 'content', 'url' => 'content/?opcao=add', 'title' => 'novo'),
	Array('id' => 'content-all', 'parent-id' => 'content', 'url' => 'content', 'title' => 'todos'),
	Array('id' => 'category', 'parent-id' => 'content', 'url' => 'content/category', 'title' => 'categorias'),
	Array('id' => 'tags', 'parent-id' => 'content', 'url' => 'content/tags', 'title' => 'tags'),
	Array('id' => 'users', 'url' => 'management/users', 'title' => 'usuários'),
	Array('id' => 'design', 'url' => 'design', 'title' => 'design'),
	Array('id' => 'config', 'url' => 'config', 'title' => 'configurações'),
	Array('id' => 'config-site', 'parent-id' => 'config', 'url' => 'config/?sessao=site', 'title' => 'site'),
	Array('id' => 'config-store', 'parent-id' => 'config', 'url' => 'config/?sessao=store', 'title' => 'loja'),
	Array('id' => 'config-paypal', 'parent-id' => 'config', 'url' => 'config/?sessao=paypal', 'title' => 'paypal'),
	Array('id' => 'config-voucher', 'parent-id' => 'config', 'url' => 'config/?sessao=voucher', 'title' => 'voucher'),
);

if($_B2MAKE_TESTES){
	foreach($_B2MAKE_TESTES_IPS as $ip){
		if($ip == $_SERVER["REMOTE_ADDR"]){
			$_B2MAKE_TESTES_IP_FOUND = true;
			break;
		}
	}
	
	if($_B2MAKE_TESTES_URLS_PERMITIDAS)
	foreach($_B2MAKE_TESTES_URLS_PERMITIDAS as $_B2MAKE_TESTES_URL_PERMITIDA){
		if(preg_match('/^'.$_B2MAKE_TESTES_URL_PERMITIDA.'/', $_REQUEST[caminho]) > 0){
			$_B2MAKE_TESTES_URL_OK = true;
		}
	}
	
	if(!$_B2MAKE_TESTES_IP_FOUND && !$_B2MAKE_TESTES_OPEN && !$_B2MAKE_TESTES_URL_OK)
		$_B2MAKE_SYSTEM_DISABLED					=	true;
	
	$_GOOGLE_ANALITYCS_STOP = true;
	$_B2MAKE_SCRIPTS_FORCE_RELOAD = true;
}

if($_SERVER['SERVER_NAME'] == "localhost"){
	$_BANCO['TYPE']							=		"mysqli";
	$_BANCO['USUARIO']						=		"root";
	$_BANCO['SENHA']						=		"serra123";
	$_BANCO['NOME']							=		"b2makeco_versao2";
	$_BANCO['HOST']							=		"127.0.0.1";
	$_BANCO['UTF8']							=		false;
	$_BANCO['UTF8_LOCAL']					=		true;
	
	$_B2MAKE_HOST = 'localhost';
	$_B2MAKE_INC = 'http://localhost/sistemas/b2make/b2make-inc/';
	$_B2MAKE_URL = 'http://localhost/sistemas/b2make/';
	$_B2MAKE_URL_SEM_SLASH = 'http://localhost/sistemas/b2make';
	$_B2MAKE_FTP_SITE_HOST = 'localhost';
	$_B2MAKE_FTP_FILES_HOST = 'localhost';
	$_B2MAKE_SITE_SUFIX_REGEX = 'localhost';
	$_B2MAKE_FTP_SITE_PATH = 'sites';
	$_B2MAKE_FTP_FILES_PATH = false;
	$_B2MAKE_FTP_SITE_ROOT = '/public_html/sites/';
	$_B2MAKE_FTP_FILES_ROOT = '/public_html/';
	$_B2MAKE_FTP_SITE_QUOTA = 10;
	$_B2MAKE_FTP_FILES_QUOTA = 10;
	$_B2MAKE_FTP_SITE_LOCALHOST = 'localhost';
	$_B2MAKE_SERVER_ALIAS = 'server1';
	$_B2MAKE_PLAN_FREE = 'TRIAL';
	$_B2MAKE_PAGINA_LOCAL = true;
	$_B2MAKE_SCRIPTS_FORCE_RELOAD = true;
} else if($_SERVER['SERVER_NAME'] == "beta.b2make.com"){
	$_BANCO['TYPE']							=		"mysqli";
	$_BANCO['HOST']							=		"localhost";
	$_BANCO['UTF8']							=		false;
	$_BANCO['UTF8_LOCAL']					=		true;
	
	if($_B2MAKE_TESTES || $_B2MAKE_TESTES2){
		$_BANCO['USUARIO']						=		"b2makeco_versao2";
		$_BANCO['SENHA']						=		"Y@1**FgTqwrn";
		$_BANCO['NOME']							=		"b2makeco_versao2";
		$_B2MAKE_TESTES_PATH					=		'/teste';
	} else {
		$_BANCO['USUARIO']						=		"betab2ma_versao2";
		$_BANCO['SENHA']						=		"Adp2s@v@";
		$_BANCO['NOME']							=		"betab2ma_versao2";
	}
	
	$_B2MAKE_HOST = 'beta.b2make.com';
	$_B2MAKE_INC = 'https://beta.b2make.com/b2make-inc/';
	$_B2MAKE_URL = 'https://beta.b2make.com'.$_B2MAKE_TESTES_PATH.'/';
	$_B2MAKE_URL_SEM_SLASH = 'https://beta.b2make.com'.$_B2MAKE_TESTES_PATH;
	$_B2MAKE_FTP_SITE_HOST = 's0.b2make.com';
	$_B2MAKE_FTP_FILES_HOST = 's0.b2make.com';
	$_B2MAKE_SITE_SUFIX_REGEX = '\.s0\.b2make\.com';
	$_B2MAKE_FTP_SITE_PATH = false;
	$_B2MAKE_FTP_FILES_PATH = 'files';
	$_B2MAKE_FTP_SITE_ROOT = '/public_html/';
	$_B2MAKE_FTP_FILES_ROOT = '/public_html/files/';
	$_B2MAKE_FTP_SITE_QUOTA = 10;
	$_B2MAKE_FTP_FILES_QUOTA = 30;
	$_B2MAKE_FTP_SITE_LOCALHOST = 'localhost';
	$_B2MAKE_SERVER_ALIAS = 'bserver0';
	$_B2MAKE_PLAN_FREE = 'TRIAL';
	$_B2MAKE_DEBUG_ALPHA = true;
	$_B2MAKE_BETA = true;
	
	$_DAEMON = Array(
		'path' => "/home/betab2make/b2make-daemon/",
		'includes_path' => "/home/betab2make/public_html/includes/",
		'seconds' => 5,
	);
} else {
	$_BANCO['TYPE']							=		"mysqli";
	$_BANCO['HOST']							=		"localhost";
	$_BANCO['UTF8']							=		false;
	$_BANCO['UTF8_LOCAL']					=		true;
	
	if($_B2MAKE_TESTES || $_B2MAKE_TESTES2){
		//$_BANCO['USUARIO']						=		"b2makeco_versao2";
		//$_BANCO['SENHA']						=		"Y@1**FgTqwrn";
		$_BANCO['USUARIO']						=		"platform_b2make";
		$_BANCO['SENHA']						=		"zq,AZj&Rt7%h";
		$_BANCO['NOME']							=		"platform_b2make";
		$_B2MAKE_TESTES_PATH					=		'/teste';
	} else {
		$_BANCO['USUARIO']						=		"platform_b2make";
		$_BANCO['SENHA']						=		"zq,AZj&Rt7%h";
		$_BANCO['NOME']							=		"platform_b2make";
	}
	
	$_B2MAKE_HOST = 'platform.b2make.com';
	$_B2MAKE_INC = 'https://platform.b2make.com/b2make-inc/';
	$_B2MAKE_URL = 'https://platform.b2make.com'.$_B2MAKE_TESTES_PATH.'/';
	$_B2MAKE_URL_SEM_SLASH = 'https://platform.b2make.com'.$_B2MAKE_TESTES_PATH;
	$_B2MAKE_FTP_SITE_HOST = 's0.b2make.com';
	$_B2MAKE_FTP_FILES_HOST = 's0.b2make.com';
	$_B2MAKE_SITE_SUFIX_REGEX = '\.s0\.b2make\.com';
	$_B2MAKE_FTP_SITE_PATH = false;
	$_B2MAKE_FTP_FILES_PATH = 'files';
	$_B2MAKE_FTP_SITE_ROOT = '/public_html/';
	$_B2MAKE_FTP_FILES_ROOT = '/public_html/files/';
	$_B2MAKE_FTP_SITE_QUOTA = 10;
	$_B2MAKE_FTP_FILES_QUOTA = 30;
	$_B2MAKE_FTP_SITE_LOCALHOST = 'localhost';
	$_B2MAKE_SERVER_ALIAS = 'server0';
	$_B2MAKE_PLAN_FREE = 'TRIAL';
	
	$_DAEMON = Array(
		'path' => "/home/platform/b2make-daemon/",
		'includes_path' => "/home/platform/public_html/includes/",
		'seconds' => 5,
	);
}

$_SYSTEM['SITE'] = Array(
	'cpanel-xmlapi-path' => '../',
	'cpanel-xmlapi-path-2' => '../../../',
	'cpanel-xmlapi-path-3' => '../../',
	'ftp-mobile-path' => 'mobile',
	'ftp-files-albummusicas-path' => 'albummusicas',
	'ftp-files-playermusicas-path' => 'playermusicas',
	'ftp-files-slideshow-path' => 'slideshow',
	'ftp-files-albumfotos-path' => 'albumfotos',
	'ftp-files-banners-path' => 'banners',
	'ftp-files-imagens-path' => 'imagens',
	'ftp-files-conteudos-imagens-path' => 'conteudos_imagens',
	'ftp-files-arquivos-path' => 'arquivos',
	'ftp-files-galerias-path' => 'galerias',
	'ftp-files-players-path' => 'players',
	'ftp-files-galeria-imagens-path' => 'galeria_imagens',
	'ftp-files-library-path' => 'library',
	'ftp-files-services-path' => 'servicos',
	'ftp-files-areas-globais-path' => 'areas_globais',
	'ftp-b2make-host-path' => 'b2make',
	'ftp-b2make-store-path' => 'servicos',
	'slideshow-max-width' => 1280,
	'slideshow-max-height' => 960,
	'slideshow-mini-width' => 77,
	'slideshow-mini-height' => 78,
	'albumfotos-max-width' => 1280,
	'albumfotos-max-height' => 960,
	'albumfotos-mini-width' => 77,
	'albumfotos-mini-height' => 78,
	'banners-max-width' => 1920,
	'banners-max-height' => 1080,
	'banners-mini-width' => 77,
	'banners-mini-height' => 78,
	'imagens-max-width' => 1920,
	'imagens-max-height' => 1080,
	'imagens-mini-width' => 77,
	'imagens-mini-height' => 78,
	'galerias-max-width' => 1280,
	'galerias-max-height' => 960,
	'galerias-mini-width' => 77,
	'galerias-mini-height' => 78,
	'galeria-imagens-max-width' => 1280,
	'galeria-imagens-max-height' => 960,
	'galeria-imagens-mini-width' => 77,
	'galeria-imagens-mini-height' => 78,
	'servicos-imagens-max-width' => 1280,
	'servicos-imagens-max-height' => 960,
	'servicos-imagens-mini-width' => 160,
	'servicos-imagens-mini-height' => 160,
	'jquery' => '	<script type="text/javascript" src="'.$_B2MAKE_INC.'jquery.min.js?v='.$_PROJETO_VERSAO.'"></script>',
	'b2make-js' => '	<script type="text/javascript" src="'.$_B2MAKE_INC.'b2make-site'.($_B2MAKE_TESTES || $_B2MAKE_TESTES2 ? '-teste' : '').'.js?v='.($_B2MAKE_TESTES_JS ? time() : $_PROJETO_VERSAO).'"></script>',
	'b2make-css' => '	<link rel="stylesheet" type="text/css" media="all" href="'.$_B2MAKE_INC.'b2make-site'.($_B2MAKE_TESTES || $_B2MAKE_TESTES2 ? '-teste' : '').'.css?v='.($_B2MAKE_TESTES_JS ? time() : $_PROJETO_VERSAO).'">',
	'b2make-store-js' => '	<script type="text/javascript" src="'.$_B2MAKE_INC.'b2make-store'.($_B2MAKE_TESTES || $_B2MAKE_TESTES2 ? '-teste' : '').'.js?v='.($_B2MAKE_TESTES_JS ? time() : $_PROJETO_VERSAO).'"></script>',
	'b2make-store-css' => '	<link rel="stylesheet" type="text/css" media="all" href="'.$_B2MAKE_INC.'b2make-store'.($_B2MAKE_TESTES || $_B2MAKE_TESTES2 ? '-teste' : '').'.css?v='.($_B2MAKE_TESTES_JS ? time() : $_PROJETO_VERSAO).'">',
	'js-extra' => '
	<link href="'.$_B2MAKE_INC.'jquery-ui/jquery-ui.min.css?v='.$_PROJETO_VERSAO.'" rel="stylesheet" type="text/css">
	<script type="text/javascript" src="'.$_B2MAKE_INC.'jquery-ui/jquery-ui.min.js?v='.$_PROJETO_VERSAO.'"></script>
	<link href="//'.$_B2MAKE_HOST.'/design/jpicker/jPicker.css" rel="stylesheet" type="text/css">
	<link href="//'.$_B2MAKE_HOST.'/design/jpicker/css/jPicker-1.1.6.css" rel="stylesheet" type="text/css">
	<script type="text/javascript" src="//'.$_B2MAKE_HOST.'/design/jpicker/jpicker-1.1.6.js?v='.$_PROJETO_VERSAO.'"></script>
	<script type="text/javascript" src="'.$_B2MAKE_INC.'jplayer/jquery.jplayer.min.js?v='.$_PROJETO_VERSAO.'"></script>
	<link rel="stylesheet" type="text/css" media="all" href="'.$_B2MAKE_INC.'prettyPhoto/css/prettyPhoto.css?v='.$_PROJETO_VERSAO.'">
	<script type="text/javascript" src="'.$_B2MAKE_INC.'prettyPhoto/js/jquery.prettyPhoto.js?v='.$_PROJETO_VERSAO.'"></script>',
	'permissao_local_inicial' => 'dashboard',
	'permissao_usuario' => '4',
	'autenticar_validacao_bloqueio' => true,
	'template-width' => 264,
	'template-height' => 158,
	'segmento-width' => 264,
	'segmento-height' => 158,
	'iniciar-ssl-minutos' => 20,
	'mobile-screen-width' => 600,
);

$_B2MAKE_URL_WORDS_BLOCKED								=	Array('platform','identification','custom-css','how-it-works','cart','logout','identify-yourself','account','purchases','payment','checkout','mobile','uncategorised','pagina-de-conteudos','b2make','01-modelos-de-paginas','pagina-de-servicos','servicos','players','galerias','arquivos','albumfotos','slideshow','playermusicas','albummusicas','imagens');

$_FTP_PUT_PASSIVE										=	true;
$_PERMISSAO_SEM_MENSAGEM								=	'<h1>Sem permiss&atilde;o de acesso!</h1>';

$_PROJETO['b2make_notification_logs']					=	false;
$_PROJETO['autenticar_nao_validar']						=	true;
$_PROJETO['CADASTRO_IPS_PERIODO_SEGUNDOS']				=	86400; // 24 HORAS
$_PROJETO['CADASTRO_IPS_TENTATIVAS_MAX']				=	3;
$_PROJETO['b2make_permissao_id_modelo_site']			=	'5';
$_PROJETO['b2make_permissao_id']						=	Array('4','5','6','8','9','10');
$_PROJETO['b2make_stores_permissoes']					=	Array('orders','services','voucher-layouts','orders-add','orders-finish','config');

//$_PAGSEGURO_SANDBOX = true;

$_PROJETO['PAGSEGURO_EMAIL']							=	'b2make@b2make.com';
$_PROJETO['PAGSEGURO_TOKEN']							=	'D185B6BEA07148D1A191426835B27105';
$_PROJETO['PAGSEGURO_APP_PUB_ID']						=	'PUB1AD09F01FDCA47DD930987C696AE0E3B';
$_PROJETO['PAGSEGURO_APP_ID']							=	'b2make';
$_PROJETO['PAGSEGURO_APP_KEY']							=	'A7B321E399992CFAA4F6DFB9151CE53C';
$_PROJETO['PAGSEGURO_APP_PASS']							=	'Yzv4nXkIq@b@';
$_PROJETO['PAGSEGURO_APP_TAXA']							=	2.99;
$_PROJETO['PAGSEGURO_SANDBOX_APP_PUB_ID']				=	'a definir';
$_PROJETO['PAGSEGURO_SANDBOX_APP_ID']					=	'app2119394205';
$_PROJETO['PAGSEGURO_SANDBOX_APP_KEY']					=	'D9D5ACE7B8B885C004E52F8982E77D28';
$_PROJETO['PAGSEGURO_SANDBOX_EMAIL_COMPRADOR']			=	'c63017299095439123626@sandbox.pagseguro.com.br';
$_PROJETO['PAGSEGURO_PARCELAS_SEM_JUROS_MAX']			=	12;

//$_PAYPAL_SANDBOX = true;

$_PROJETO['PAYPAL_SANDBOX_USER']						=	'b2make-facilitator_api1.b2make.com';
$_PROJETO['PAYPAL_SANDBOX_PASS']						=	'TPQ3WLEEYULFNS2N';
$_PROJETO['PAYPAL_SANDBOX_SIGNATURE']					=	'Ai1PaghZh5FmBLCDCTQpwG8jB264AqvoPM2XMLreZewrT9pJ87rR0.3S';
$_PROJETO['PAYPAL_SANDBOX_EMAIL']						=	'b2make-facilitator@b2make.com';
$_PROJETO['PAYPAL_USER']								=	'b2make_api1.b2make.com';
$_PROJETO['PAYPAL_PASS']								=	'P7SVBNQEFTF5RDDL';
$_PROJETO['PAYPAL_SIGNATURE']							=	'An5ns1Kso7MWUdW4ErQKJJJ4qi4-AxXvev25ZBGvby2bzPTYmpR-5VZE';
$_PROJETO['PAYPAL_COMISSAO_EMAIL']						=	'b2make@b2make.com';
$_PROJETO['PAYPAL_COMISSAO_TAXA']						=	2.99;
$_PROJETO['PAYPAL_COMISSAO_ID']							=	'7FQ3U3DZJE7J6';
$_PROJETO['PAYPAL_COMISSAO_TESTES']						=	false;
$_PROJETO['PAYPAL_COMISSAO_ASSUNTO']					=	'Pagamento de taxa sobre uso do B2make';
$_PROJETO['PAYPAL_COMISSAO_MENSAGEM']					=	'Pagamento de taxa sobre uso do B2make';
$_PROJETO['PAYPAL_B2MAKE_SANDBOX_ID']					=	'ASiRM8MWj_ArWLKSMEHywreAOthHD6fug5xTEtKiSaBaBlpU6XX4YdsoJTYI42SlhHxi3QmsgDHrzA33';
$_PROJETO['PAYPAL_B2MAKE_SANDBOX_SECRET']				=	'EHUKXjpenz-dV07NbXh7B4gOm3GrNNeyI0si8I2lNAaczedk4WTeifw1etcIrJh9UB01PNDR00zMa3s5';
$_PROJETO['PAYPAL_B2MAKE_LIVE_ID']						=	'ARnA3d4isM3DEt4skWXnqL4OgGTJMj4lqoZpNmW7-9xPbxLyu0ucB1whzBinc2FHrjfbCoZPni8kOvDf';
$_PROJETO['PAYPAL_B2MAKE_LIVE_SECRET']					=	'EPpnEzDxux03j7c5VE306KT4rHtHjDV32rXfOWruS15qGOdIyVK_JWPC3Clw61JGGs3yKGOkT9ZU0e-r';

$_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MINUTOS']		=	15;
$_PROJETO['PPPLUS_SEGURANCA_TENTATIVAS_MAX']			=	10;
$_PROJETO['PPPLUS_TESTES']								=	false;

$_PROJETO['INSTAGRAM_CLIENT_ID']						=	'2504698409745806';
$_PROJETO['INSTAGRAM_CLIENT_SECRET']					=	'fe0e6dd4e81f536fe2185adf54516a07';
/* 
$_PROJETO['INSTAGRAM_CLIENT_ID']						=	'404564020435250';
$_PROJETO['INSTAGRAM_CLIENT_SECRET']					=	'22bd56d53da9f7145c716192279745f8';
 */
$_PROJETO['servicos']['new_width_mini']					=	'159';
$_PROJETO['servicos']['new_height_mini']				=	'159';
$_PROJETO['servicos']['new_width']						=	'500';
$_PROJETO['servicos']['new_height']						=	'500';

$_PROJETO['new_width_256']								=	'256';
$_PROJETO['new_height_256']								=	'256';

$_PROJETO['site_conteudos']['new_width_mini']			=	'159';
$_PROJETO['site_conteudos']['new_height_mini']			=	'159';
$_PROJETO['site_conteudos']['new_width']				=	'1024';
$_PROJETO['site_conteudos']['new_height']				=	'1024';

$_PROJETO['e-services']['permissao_usuario']			=	'7';
$_PROJETO['e-services']['permissao_local_inicial']		=	'how-it-works';

$_ESERVICE['pedido_validade']							=	90;
$_ESERVICE['minha-loja']								=	'Minha Loja';
$_ESERVICE['minha-loja-id']								=	'minha-loja';
//$_ESERVICE['store-logomarca-width']						=	150;
$_ESERVICE['store-logomarca-height']					=	60;
$_ESERVICE['store-ids-proibidos']						=	Array('pagseguro-notifications','paypal-notifications');
$_ESERVICE['store-rodape-joja']							=	'Todos os direitos reservados - ';

$_PROJETO['CONTEUDO_CALLBACK']							=	'projeto_categorias_pagina';
$_PROJETO['CONTEUDO_CALLBACK_ID_PAI']					=	'46';

$_PROJETO['STORE_PERFIS_DE_USUARIOS']					=	Array('8','9','10');
$_PROJETO['STORE_PERFIS_PDV']							=	Array('8');
$_PROJETO['STORE_PERFIS_GESTOR']						=	Array('10');

$_PROJETO['GOOGLE_API_KEY']								=	'AIzaSyDR5LYU7Spye-I-jrkQkSoHATJfpWGipGk';

$_SYSTEM['B2MAKE_PLANO_FREE']							=	'TRIAL';

$_CPANEL['LOG']											=	false;

$_PROJETO['B2MAKE_STORE_URLS'] = Array(
	'cart' => Array(
		'titulo' => 'Carrinho de Compras',
		'url' => 'cart',
		'iframe_url' => 'cart',
	),
	'purchases' => Array(
		'titulo' => 'Compras',
		'url' => 'purchases',
		'iframe_url' => 'purchases',
	),
	'account' => Array(
		'titulo' => 'Minha Conta',
		'url' => 'account',
		'iframe_url' => 'account',
	),
	'how-it-works' => Array(
		'titulo' => 'Como Funciona',
		'url' => 'how-it-works',
		'iframe_url' => 'how-it-works',
	),
	'checkout' => Array(
		'titulo' => 'Checkout',
		'url' => 'checkout',
		'iframe_url' => 'checkout',
	),
	'emission' => Array(
		'titulo' => 'Emissão',
		'url' => 'emission',
		'iframe_url' => 'emission',
	),
	'payment' => Array(
		'titulo' => 'Pagamento',
		'url' => 'payment',
		'iframe_url' => 'payment',
	),
	'payment/other-payer' => Array(
		'titulo' => 'Pagamento Outro Pagador',
		'url' => 'payment/other-payer',
		'iframe_url' => 'payment/other-payer',
	),
	'payment/paypal' => Array(
		'titulo' => 'Pagamento PayPal',
		'url' => 'payment/paypal',
		'iframe_url' => 'payment/paypal',
	),
	'identify-yourself' => Array(
		'titulo' => 'Identifique-se',
		'url' => 'identify-yourself',
		'iframe_url' => 'identify-yourself',
	),
	'logout' => Array(
		'titulo' => 'Sair',
		'url' => 'logout',
		'iframe_url' => 'logout',
	),
	'signup' => Array(
		'titulo' => 'Cadastro',
		'url' => 'signup',
		'iframe_url' => 'signup',
	),
	'user-update' => Array(
		'titulo' => 'Atualizar Seus Dados',
		'url' => 'user-update',
		'iframe_url' => 'user-update',
	),
	'print' => Array(
		'titulo' => 'Imprimir',
		'iframe_url_full' => true,
		'url' => 'voucher-print',
		'iframe_url' => 'includes/eservices/print.php',
	),
	'forgot-your-password' => Array(
		'titulo' => 'Esqueceu Senha',
		'url' => 'forgot-your-password',
		'iframe_url' => 'forgot-your-password',
	),
	'generate-new-password' => Array(
		'titulo' => 'Redefinir sua Senha',
		'url' => 'generate-new-password',
		'iframe_url' => 'generate-new-password',
	),
);

$_PROJETO['B2MAKE_STORE_STATUS'] = Array(
	'5' => '<span style="color:red;">Em disputa</span>',
	'6' => '<span style="color:brown;">Dinheiro Devolvido</span>',
	'7' => '<span style="color:brown;">Cancelado</span>',
	'F' => '<span style="color:brown;">Finalizado</span>',
	'A' => '<span style="color:green;">Pago</span>',
	'B' => '<span style="color:red;">Bloqueado</span>',
	'D' => '<span style="color:red;">Deletado</span>',
	'N' => '<span style="color:blue;">Aguardando pagamento</span>',
	'P' => '<span style="color:blue;">Em análise</span>',
	'9' => '<span style="color:red;">Em contestação</span>',
	'E' => '<span style="color:red;">Pagamento Expirado</span>',
);

$_PROJETO['B2MAKE_STORE_STATUS_2'] = Array(
	'5' => '<span class="status-aguardando">Em disputa</span>',
	'6' => '<span class="status-cancelado">Dinheiro Devolvido</span>',
	'7' => '<span class="status-cancelado">Cancelado</span>',
	'F' => '<span class="status-concluido">Finalizado</span>',
	'A' => '<span class="status-concluido">Pago</span>',
	'B' => '<span class="status-cancelado">Bloqueado</span>',
	'D' => '<span class="status-cancelado">Deletado</span>',
	'N' => '<span class="status-aguardando">Aguardando pagamento</span>',
	'P' => '<span class="status-aguardando">Em análise</span>',
	'9' => '<span class="status-aguardando">Em contestação</span>',
	'E' => '<span class="status-cancelado">Pagamento Expirado</span>',
);

$_PROJETO['B2MAKE_STORE_STATUS_MUDAR_TITULO'] = Array(
	'5' => 'Em disputa',
	'6' => 'Reembolsado',
	'7' => 'Cancelado',
	'F' => 'Baixado',
	'A' => 'Pago',
	'B' => 'Bloqueado',
	'D' => 'Deletado',
	'N' => 'Aguardando pagamento',
	'P' => 'Em análise',
	'9' => 'Em contestação',
	'E' => 'Pagamento Expirado',
);

$_PROJETO['B2MAKE_STORE_STATUS_MUDAR_CORES'] = Array(
	'5' => '#ff5554',
	'6' => '#423f40',
	'7' => '#e78d00',
	'F' => '#0092d4',
	'A' => '#A1BC31',
	'B' => '#ff5554',
	'D' => '#C44732',
	'N' => '#C44732',
	'P' => '#18629a',
	'9' => '#C44732',
	'E' => '#e78d00',
);

$_PROJETO['B2MAKE_STORE_STATUS_MUDAR_CORES_2'] = Array(
	'5' => '#F9EDC7,#7E3737',
	'6' => '#FFE0E0,#7E3737',
	'7' => '#FFE0E0,#7E3737',
	'F' => '#D1F0CD,#143311',
	'A' => '#D1F0CD,#143311',
	'B' => '#FFE0E0,#7E3737',
	'D' => '#FFE0E0,#7E3737',
	'N' => '#F9EDC7,#7E3737',
	'P' => '#F9EDC7,#7E3737',
	'9' => '#F9EDC7,#7E3737',
	'E' => '#FFE0E0,#7E3737',
);

if($_B2MAKE_TESTES || $_B2MAKE_TESTES2){
	$_SYSTEM['B2MAKE_PLANOS'] = Array(
		'1' => Array(
			'nome' => 'TRIAL',
			'detalhes' => 'Teste por 07 dias gratuitamente com todas as ferramentas liberadas.',
			'valor' => '0',
			'valorTotal' => '0',
			'quota' => '30',
			'users' => 0,
			'not-show' => true,
		),
		/* '2' => Array(
			'nome' => 'START',
			'detalhes' => '1 GB / SSD<br>
2 GB / tr&aacute;fego<br>
01 Usu&aacute;rio<br>
01 Dom&iacute;nio<br><br>
E-mails ilimitados*<br>
Vendas Ilimitadas<br>
Sem Comiss&atilde;o<br>
M&oacute;dulo de Gest&atilde;o<br>
Templates<br>
Chave SSL 2048 bits<br>
Meios de Pagamento<br>
Helpdesk',
			'valor' => '14.90',
			'valorTotal' => '429.00',
			'quota' => '1000',
			'users' => 1,
		), */
		'3' => Array(
			'nome_mostrar' => 'START',
			'nome' => 'START_NOVO',
			'detalhes' => '2 GB / SSD<br>
4 GB / tr&aacute;fego<br>
01 Usu&aacute;rio<br>
01 Dom&iacute;nio<br><br>
E-mails ilimitados*<br>
Vendas Ilimitadas<br>
Sem Comiss&atilde;o<br>
M&oacute;dulo de Gest&atilde;o<br>
Templates<br>
Chave SSL 2048 bits<br>
Meios de Pagamento<br>
Helpdesk',
			'valor' => '39.90',
			'valorTotal' => '1197.00',
			'quota' => '2000',
			'users' => 1,
		),
		/* '4' => Array(
			'nome' => 'PRO I',
			'detalhes' => '3 GB / SSD<br>
7 GB / tr&aacute;fego<br>
02 Usu&aacute;rios<br>
02 Dom&iacute;nios<br><br>
E-mails ilimitados*<br>
Vendas Ilimitadas<br>
Sem Comiss&atilde;o<br>
M&oacute;dulo de Gest&atilde;o<br>
Templates<br>
Chave SSL 2048 bits<br>
Meios de Pagamento<br>
Helpdesk',
			'valor' => '49.90',
			'valorTotal' => '1497.00',
			'quota' => '3000',
			'users' => 2,
		), */
		'5' => Array(
			'nome_mostrar' => 'PRO',
			'nome' => 'PRO',
			'detalhes' => '5 GB / SSD<br>
10 GB / tr&aacute;fego<br>
03 Usu&aacute;rios<br>
03 Dom&iacute;nios<br><br>
E-mails ilimitados*<br>
Vendas Ilimitadas<br>
Sem Comiss&atilde;o<br>
M&oacute;dulo de Gest&atilde;o<br>
Templates<br>
Chave SSL 2048 bits<br>
Meios de Pagamento<br>
Helpdesk',
			'valor' => '79.90',
			'valorTotal' => '2397.00',
			'quota' => '5000',
			'users' => 3,
		),
		'6' => Array(
			'nome_mostrar' => 'PREMIUM',
			'nome' => 'PREMIUM',
			'detalhes' => '8 GB / SSD<br>
15 GB / tr&aacute;fego<br>
05 Usu&aacute;rios<br>
05 Dom&iacute;nios<br><br>
E-mails ilimitados*<br>
Vendas Ilimitadas<br>
Sem Comiss&atilde;o<br>
M&oacute;dulo de Gest&atilde;o<br>
Templates<br>
Chave SSL 2048 bits<br>
Meios de Pagamento<br>
Helpdesk',
			'valor' => '159.90',
			'valorTotal' => '4797.00',
			'quota' => '8000',
			'users' => 99,
		),
		/* '7' => Array(
			'nome' => 'PREMIUM II',
			'detalhes' => '12 GB / SSD<br>
25 GB / tr&aacute;fego<br>
10 Usu&aacute;rios<br>
10 Dom&iacute;nios<br><br>
E-mails ilimitados*<br>
Vendas Ilimitadas<br>
Sem Comiss&atilde;o<br>
M&oacute;dulo de Gest&atilde;o<br>
Templates<br>
Chave SSL 2048 bits<br>
Meios de Pagamento<br>
Helpdesk',
			'valor' => '179.90',
			'valorTotal' => '5397.00',
			'quota' => '12000',
			'users' => 10,
		),
        '8' => Array(
			'nome' => 'ENTERPRISE I',
			'detalhes' => '16 GB / SSD<br>
35 GB / tr&aacute;fego<br>
Usu&aacute;rios Ilimitados<br>
15 Dom&iacute;nios<br><br>
E-mails ilimitados*<br>
Vendas Ilimitadas<br>
Sem Comiss&atilde;o<br>
M&oacute;dulo de Gest&atilde;o<br>
Templates<br>
Chave SSL 2048 bits<br>
Meios de Pagamento<br>
Helpdesk',
			'valor' => '239.90',
			'valorTotal' => '7197.00',
			'quota' => '16000',
			'users' => 999999999999999999,
		),
        '9' => Array(
			'nome' => 'ENTERPRISE II',
			'detalhes' => '16 GB / SSD<br>
Tr&aacute;fego liberado**<br>
Usu&aacute;rios Ilimitados<br>
15 Dom&iacute;nios<br><br>
E-mails ilimitados*<br>
Vendas Ilimitadas<br>
Sem Comiss&atilde;o<br>
M&oacute;dulo de Gest&atilde;o<br>
Templates<br>
Chave SSL 2048 bits<br>
Meios de Pagamento<br>
Helpdesk',
			'valor' => '239.90',
			'valorTotal' => '7197.00',
			'quota' => '16000',
			'users' => 999999999999999999,
		), */
	);
} else {
	$_SYSTEM['B2MAKE_PLANOS'] = Array(
		'1' => Array(
			'nome' => 'TRIAL',
			'detalhes' => 'Teste por 07 dias gratuitamente com todas as ferramentas liberadas.',
			'valor' => '0',
			'valorTotal' => '0',
			'quota' => '30',
			'users' => 0,
			'not-show' => true,
		),
		/* '2' => Array(
			'nome' => 'START',
			'detalhes' => '1 GB / SSD<br>
2 GB / tr&aacute;fego<br>
01 Usu&aacute;rio<br>
01 Dom&iacute;nio<br><br>
E-mails ilimitados*<br>
Vendas Ilimitadas<br>
Sem Comiss&atilde;o<br>
M&oacute;dulo de Gest&atilde;o<br>
Templates<br>
Chave SSL 2048 bits<br>
Meios de Pagamento<br>
Helpdesk',
			'valor' => '14.90',
			'valorTotal' => '429.00',
			'quota' => '1000',
			'users' => 1,
		), */
		'3' => Array(
			'nome_mostrar' => 'START',
			'nome' => 'START_NOVO',
			'detalhes' => '2 GB / SSD<br>
4 GB / tr&aacute;fego<br>
01 Usu&aacute;rio<br>
01 Dom&iacute;nio<br><br>
E-mails ilimitados*<br>
Vendas Ilimitadas<br>
Sem Comiss&atilde;o<br>
M&oacute;dulo de Gest&atilde;o<br>
Templates<br>
Chave SSL 2048 bits<br>
Meios de Pagamento<br>
Helpdesk',
			'valor' => '39.90',
			'valorTotal' => '1197.00',
			'quota' => '2000',
			'users' => 1,
		),
		/* '4' => Array(
			'nome' => 'PRO I',
			'detalhes' => '3 GB / SSD<br>
7 GB / tr&aacute;fego<br>
02 Usu&aacute;rios<br>
02 Dom&iacute;nios<br><br>
E-mails ilimitados*<br>
Vendas Ilimitadas<br>
Sem Comiss&atilde;o<br>
M&oacute;dulo de Gest&atilde;o<br>
Templates<br>
Chave SSL 2048 bits<br>
Meios de Pagamento<br>
Helpdesk',
			'valor' => '49.90',
			'valorTotal' => '1497.00',
			'quota' => '3000',
			'users' => 2,
		), */
		'5' => Array(
			'nome_mostrar' => 'PRO',
			'nome' => 'PRO',
			'detalhes' => '5 GB / SSD<br>
10 GB / tr&aacute;fego<br>
03 Usu&aacute;rios<br>
03 Dom&iacute;nios<br><br>
E-mails ilimitados*<br>
Vendas Ilimitadas<br>
Sem Comiss&atilde;o<br>
M&oacute;dulo de Gest&atilde;o<br>
Templates<br>
Chave SSL 2048 bits<br>
Meios de Pagamento<br>
Helpdesk',
			'valor' => '79.90',
			'valorTotal' => '2397.00',
			'quota' => '5000',
			'users' => 3,
		),
		'6' => Array(
			'nome_mostrar' => 'PREMIUM',
			'nome' => 'PREMIUM',
			'detalhes' => '8 GB / SSD<br>
15 GB / tr&aacute;fego<br>
05 Usu&aacute;rios<br>
05 Dom&iacute;nios<br><br>
E-mails ilimitados*<br>
Vendas Ilimitadas<br>
Sem Comiss&atilde;o<br>
M&oacute;dulo de Gest&atilde;o<br>
Templates<br>
Chave SSL 2048 bits<br>
Meios de Pagamento<br>
Helpdesk',
			'valor' => '159.90',
			'valorTotal' => '4797.00',
			'quota' => '8000',
			'users' => 99,
		),
		/* '7' => Array(
			'nome' => 'PREMIUM II',
			'detalhes' => '12 GB / SSD<br>
25 GB / tr&aacute;fego<br>
10 Usu&aacute;rios<br>
10 Dom&iacute;nios<br><br>
E-mails ilimitados*<br>
Vendas Ilimitadas<br>
Sem Comiss&atilde;o<br>
M&oacute;dulo de Gest&atilde;o<br>
Templates<br>
Chave SSL 2048 bits<br>
Meios de Pagamento<br>
Helpdesk',
			'valor' => '179.90',
			'valorTotal' => '5397.00',
			'quota' => '12000',
			'users' => 10,
		),
        '8' => Array(
			'nome' => 'ENTERPRISE I',
			'detalhes' => '16 GB / SSD<br>
35 GB / tr&aacute;fego<br>
Usu&aacute;rios Ilimitados<br>
15 Dom&iacute;nios<br><br>
E-mails ilimitados*<br>
Vendas Ilimitadas<br>
Sem Comiss&atilde;o<br>
M&oacute;dulo de Gest&atilde;o<br>
Templates<br>
Chave SSL 2048 bits<br>
Meios de Pagamento<br>
Helpdesk',
			'valor' => '239.90',
			'valorTotal' => '7197.00',
			'quota' => '16000',
			'users' => 999999999999999999,
		),
        '9' => Array(
			'nome' => 'ENTERPRISE II',
			'detalhes' => '16 GB / SSD<br>
Tr&aacute;fego liberado**<br>
Usu&aacute;rios Ilimitados<br>
15 Dom&iacute;nios<br><br>
E-mails ilimitados*<br>
Vendas Ilimitadas<br>
Sem Comiss&atilde;o<br>
M&oacute;dulo de Gest&atilde;o<br>
Templates<br>
Chave SSL 2048 bits<br>
Meios de Pagamento<br>
Helpdesk',
			'valor' => '239.90',
			'valorTotal' => '7197.00',
			'quota' => '16000',
			'users' => 999999999999999999,
		), */
	);
}

if($_LOCAL_ID == "index"){
modulos_add_tags(Array(
	'#segmentos#',
	'#templates#',
	'#planos#',
));
}

function config_modificar_globais(){
	global $_LOCAL_ID;
	global $_INCLUDE_MODULO;
	global $_B2MAKE_HOST;
	
	switch($_LOCAL_ID){
		case 'index':
			//$_INCLUDE_MODULO = true;
		break;
	}
	
}

config_modificar_globais();

?>