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
// Funções de Iniciação do sistema

$_VERSAO_MODULO				=	'1.0.0';
$_LOCAL_ID					=	"changelog";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_MENU_LATERAL				=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../../";
$_CAMINHO_MODULO_RAIZ		=	"../../";
$_JS_TOOLTIP_INICIO			=	true;
$_MENU_LATERAL_GESTOR		=	true;
$_HTML['LAYOUT']			=	$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.html";


include($_CAMINHO_RELATIVO_RAIZ."config.php");

if(!$_SESSION[$_SYSTEM['ID']."permissao"])
	header("Location: ".$_CAMINHO_RELATIVO_RAIZ);

$_HTML['titulo'] 						= 	$_HTML['titulo']."CHANGELOG.";
$_HTML['variaveis']['titulo-modulo']	=	'E-Service';	

$_HTML['js'] .= "<script type=\"text/javascript\" src=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.js?v=".$_VERSAO_MODULO."\"></script>\n";
$_HTML['js'] .= "<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
if($_SESSION[$_SYSTEM['ID']."usuario"]['dark_mode']) $_HTML['css'] .= "<link href=\"".$_CAMINHO_RELATIVO_RAIZ."files/projeto/layout-gestor-dark-mode.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";
if($_SESSION[$_SYSTEM['ID']."usuario"]['dark_mode']) $_HTML['css'] .= "<link href=\"css-dark-mode.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

// Funções do sistema

function paginaInicial(){
	global $_HTML;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_SYSTEM;
	global $_VERSAO;
	
	/* $resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'texto',
		))
		,
		"conteudo",
		"WHERE id_externo='dashboard'"
	);
	
	if($resultado){
		$pagina = '<div id="conteiner-principal">'.$resultado[0]['texto'].'</div>';
	} */
	
	$linhas = file($_SYSTEM['PATH'].'changelog');
	
	if($linhas){
		
		$pagina .= '<h1>Changelog</h1>';
		$pagina .= '<div class="b2make-dashboard-titulo-principal">Atualizações do B2make | Versão Atual: '.$_VERSAO.'</div>';
		
		foreach($linhas as $num => $linha){
			if(!$start && preg_match('/^## /', $linha) > 0){
				$start = true;
				$primeiro = true;
			}
			
			if($start){
				$linha = $linha;
				
				if(preg_match('/^## /', $linha) > 0){
					if(!$primeiro){
						switch($tipo_atual){
							case 'ADDED': $adicionado .= '<ol class="b2make-dashboard-list">'.$adicionado_li.'</ol>'."\n"; $adicionado_li = ''; break;
							case 'FIXED': $corrigido .= '<ol class="b2make-dashboard-list">'.$corrigido_li.'</ol>'."\n"; $corrigido_li = ''; break;
							case 'CHANGED': $modificado .= '<ol class="b2make-dashboard-list">'.$modificado_li.'</ol>'."\n"; $modificado_li = ''; break;
						}
						
						$pagina .= '<div class="b2make-dashboard-cont">'.$cabecalho . $adicionado . $corrigido . $modificado.'</div>';
						
						$cabecalho = '';
						$adicionado = '';
						$corrigido = '';
						$modificado = '';
					}
					
					$linha_arr = explode(' - ',$linha);
					$data = preg_replace('/\./i', '/', trim($linha_arr[1]));
					$versao = modelo_tag_val($linha,'[',']');
					
					$cabecalho = '<div class="b2make-dashboard-titulo">Versão: '.$versao . ' - ' . $data.'</div>'."\n";
					
					$primeiro = false;
				}
				
				if(preg_match('/^### /', $linha) > 0){
					$tipo = trim(strtoupper(preg_replace('/^### /i', '', $linha)));
					
					switch($tipo){
						case 'ADDED': $adicionado = '<div class="b2make-dashboard-tipo">Adicionado</div>'."\n"; break;
						case 'FIXED': $corrigido = '<div class="b2make-dashboard-tipo">Corrigido</div>'."\n"; break;
						case 'CHANGED': $modificado = '<div class="b2make-dashboard-tipo">Modificado</div>'."\n"; break;
					}
					
					if($tipo_atual && $tipo_atual != $tipo){
						switch($tipo_atual){
							case 'ADDED': $adicionado .= '<ol class="b2make-dashboard-list">'.$adicionado_li.'</ol>'."\n"; $adicionado_li = ''; break;
							case 'FIXED': $corrigido .= '<ol class="b2make-dashboard-list">'.$corrigido_li.'</ol>'."\n"; $corrigido_li = ''; break;
							case 'CHANGED': $modificado .= '<ol class="b2make-dashboard-list">'.$modificado_li.'</ol>'."\n"; $modificado_li = ''; break;
						}
					}
					
					$tipo_atual = $tipo;
				}
				
				if(preg_match('/^- /', $linha) > 0){
					$linha = preg_replace('/^- /i', '', $linha);
					
					switch($tipo_atual){
						case 'ADDED': $adicionado_li .= '<li>'.$linha.'</li>'."\n"; break;
						case 'FIXED': $corrigido_li .= '<li>'.$linha.'</li>'."\n"; break;
						case 'CHANGED': $modificado_li .= '<li>'.$linha.'</li>'."\n"; break;
					}
				}
			}
		}
		
		switch($tipo_atual){
			case 'ADDED': $adicionado .= '<ol class="b2make-dashboard-list">'.$adicionado_li.'</ol>'."\n"; $adicionado_li = ''; break;
			case 'FIXED': $corrigido .= '<ol class="b2make-dashboard-list">'.$corrigido_li.'</ol>'."\n"; $corrigido_li = ''; break;
			case 'CHANGED': $modificado .= '<ol class="b2make-dashboard-list">'.$modificado_li.'</ol>'."\n"; $modificado_li = ''; break;
		}
		
		$pagina .= '<div class="b2make-dashboard-cont">'.$cabecalho . $adicionado . $corrigido . $modificado.'</div>';
	}
	
	return $pagina;
}

// ======================================================================================

function ajax(){
	global $_PROJETO;
	global $_SYSTEM;
	
	if($_PROJETO['b2make_permissao_id'])
	foreach($_PROJETO['b2make_permissao_id'] as $permissao){
		if($permissao == $_SESSION[$_SYSTEM['ID']."permissao_id"]){
			$permissao_flag = true;
			break;
		}
	}
	
	if(!$permissao_flag){
		$saida = Array(
			'status' => 'SemPermissao',
		);
		
		return json_encode($saida);
	}
	
	switch($_REQUEST["opcao"]){
		//case 'dark-mode-change': $saida = ajax_dark_mode_change(); break;
	}
	
	return (!$_AJAX_OUT_VARS['not-json-encode'] ? json_encode($saida) : $saida);
}

function main(){
	global $_SYSTEM;
	global $_HTML;
	
	if($_GET[opcao])			$opcao = $_GET[opcao];
	if($_POST[opcao])			$opcao = $_POST[opcao];
	
	if(!$_REQUEST['ajax']){
		switch($opcao){
			//case 'mudar_logo_base':			$saida = (operacao('mudar_logo') ? mudar_logo_base() : paginaInicial());break;
			default: 						$saida = paginaInicial();
		}

		$_HTML['body'] = $saida;
		
		echo pagina();
	} else {
		echo ajax();
	}
}

main();

?>