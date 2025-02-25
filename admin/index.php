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

$_VERSAO_MODULO				=	'1.2.0';
$_LOCAL_ID					=	"admin";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../";
$_CAMINHO_MODULO_RAIZ		=	".";
$_JS_TOOLTIP_INICIO			=	true;
$_HTML['LAYOUT']			=	"layout.html";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

if(!$_SESSION[$_SYSTEM['ID']."permissao"])
	header("Location: ".$_CAMINHO_RELATIVO_RAIZ);

$_HTML['titulo'] 			= 	$_HTML['titulo']."Painel Administrativo.";

$_HTML['js'] .= 
$_JS['blockUI'].
$_JS['menu'];
//"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

// Funções do sistema

function removeConteudoDeDiretorioTemporario($dir,$tempo = 5,$filho = false) {
	if(is_dir($dir)){
		$abreDir = opendir($dir);
		$date = strtotime('-'.$tempo.' day', time());

		while (false !== ($file = readdir($abreDir))) {
			if ($file==".." || $file ==".") continue;
			
			$filetime = filemtime($dir."/".$file);
			if( $date > $filetime ){
				if (is_dir($cFile=($dir."/".$file))) removeConteudoDeDiretorioTemporario($cFile,$tempo,true);
				elseif (is_file($cFile)) unlink($cFile);
			}
		}

		closedir($abreDir);
		if($filho)rmdir($dir);
	}
}

function guardar_arquivo($uploaded,$tipo,$campo,$id_tabela,$old_name = false){
	global $_LISTA;
	global $_SYSTEM;
	global $_PROJETO;
	global $_PERMISSAO_CONTEUDO;
	global $_RESIZE_IMAGE_Y_ZERO;
	global $_CMS;
	
	$caminho_fisico 		=	$_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR'];
	$caminho_internet 		= 	"files/";
	
	if(
		$uploaded['size'] != 0
	){
		switch($tipo){
			case 'imagem':
				if
				(
					$uploaded['type'] == mime_types("jpe") ||
					$uploaded['type'] == mime_types("jpeg") ||
					$uploaded['type'] == mime_types("jpg") ||
					$uploaded['type'] == mime_types("pjpeg") ||
					$uploaded['type'] == mime_types("png") ||
					$uploaded['type'] == mime_types("x-png") ||
					$uploaded['type'] == mime_types("swf") ||
					$uploaded['type'] == mime_types("gif")
				){
					$cadastrar = true;
				}
			break;
		}
	}
	
	if($cadastrar){
		if
		(
			$uploaded['type'] == mime_types("jpe") ||
			$uploaded['type'] == mime_types("jpeg") ||
			$uploaded['type'] == mime_types("pjpeg") ||
			$uploaded['type'] == mime_types("jpg")
		){
			$extensao = "jpg";
		} else if
		(
			$uploaded['type'] == mime_types("png") ||
			$uploaded['type'] == mime_types("x-png") 
		){
			$extensao = "png";
		} else if
		(
			$uploaded['type'] == mime_types("gif")
		){
			$extensao = "gif";
		}
		
		$extensoes[] = "jpg";
		$extensoes[] = "png";
		$extensoes[] = "gif";
		
		$nome_arquivo = $campo . $id_tabela . "." . $extensao;
		
		foreach($extensoes as $ext){
			if($ext != $extensao){
				$nome_arquivo_busca = $campo . $id_tabela . "." . $ext;
				if(is_file($caminho_fisico . $nome_arquivo_busca)){
					unlink($caminho_fisico . $nome_arquivo_busca);
				}
			}
		}
		
		if(is_file($caminho_fisico . $nome_arquivo))$existe_arquivo = true;
		
		if(!move_uploaded_file($uploaded['tmp_name'], $caminho_fisico . $nome_arquivo))
			echo "<h1>Problemas com o UPLOAD do arquivo: ".$uploaded['tmp_name']."</h1>";
		else {
			if(!$existe_arquivo)chmod($caminho_fisico 	. $nome_arquivo , 0777);
			
			if($_CMS['admin-resize-logomarca-width'] > 0 && $_CMS['admin-resize-logomarca-height'] > 0){
				$original = $caminho_fisico . $nome_arquivo;
				$_RESIZE_IMAGE_Y_ZERO = false;
				resize_image($original, $original, $_CMS['admin-resize-logomarca-width'], $_CMS['admin-resize-logomarca-height'],false,false,false);
			}
		}
	}
}

function mudar_logo(){
	$pagina = modelo_abrir('html.html');
	$menu = modelo_tag_val($pagina,'<!-- menu < -->','<!-- menu > -->');
	$pagina = modelo_tag_val($pagina,'<!-- mudar_logo < -->','<!-- mudar_logo > -->');
	$pagina = $menu.$pagina;
	
	return $pagina;
}

function mudar_logo_base(){
	global $_CMS;
	global $_SYSTEM;
	
	$_CMS['admin-resize-logomarca-width'] = 200;
	$_CMS['admin-resize-logomarca-height'] = 100;
	
	if($_FILES['imagem']['size'] != 0){
		guardar_arquivo($_FILES['imagem'],'imagem','logomarca-cms',false,false);
		$_SESSION[$_SYSTEM['ID']."cms-logomarca"] = false;
		header("Location: /".$_SYSTEM['ROOT']."admin/");
	}
	
	return paginaInicial();
}

function paginaInicial(){
	global $_HTML;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_SYSTEM;
	
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
			'ordem',
		))
		,
		"modulo",
		"WHERE status='A'"
		." ORDER BY id_modulo_pai ASC, ordem ASC, nome ASC"
	);
	banco_fechar_conexao();
	
	$pagina = modelo_abrir('html.html');
	$menu = modelo_tag_val($pagina,'<!-- menu < -->','<!-- menu > -->');
	$pagina = modelo_tag_val($pagina,'<!-- inicio < -->','<!-- inicio > -->');
	$pagina = $menu.$pagina;
	
	$cel_nome = 'item'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'cat'; $cel[$cel_nome] = modelo_tag_val($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	foreach($modulos as $modulo){
		if($modulo['id_modulo_pai']){
			if($permissao_modulos[$modulo['caminho']] || $modulo['id_modulo'] == 6 || $_SESSION[$_SYSTEM['ID']."admin"]){
				if($modulo['nome'] != 'Sair'){
					$filhos_count[$modulo['id_modulo_pai']]++;
				}
			}
		} else {
			if($modulo['id_modulo'] != '42'){
				$cel_nome = 'cat';
				if($modulo['ordem']){
					$ordenar_categoria = true;
					$ordenacao_categoria[$modulo['id_modulo']] = $modulo['ordem'];
				}
				$cel_aux[$modulo['id_modulo']] = $cel[$cel_nome];
				$cel_aux[$modulo['id_modulo']] = modelo_var_troca($cel_aux[$modulo['id_modulo']],"#cat_titulo",$modulo['nome']);
			}
		}
	}
	
	$linha_grade = 20;
	$coluna_grade = 20;
	
	$modulos_ativos = Array(
		'conteudo',
		'dados_pessoais',
		'perfis',
		'preferencias',
		'usuarios',
	);
	
	foreach($modulos as $modulo){
		if($modulo['id_modulo_pai']){
			if($permissao_modulos[$modulo['caminho']] || $modulo['id_modulo'] == 6 || $_SESSION[$_SYSTEM['ID']."admin"]){
				if($modulo['nome'] != 'Sair'){
					$cel_nome = 'item';
					$cel_aux2 = $cel[$cel_nome];
					
					if($modulo['imagem']){
						list($linha,$coluna) = explode(',',$modulo['imagem']);
					} else {
						$linha = 1;
						$coluna = 6;
					}
					
					$continuar = false;
					
					if($modulos_ativos)
					foreach($modulos_ativos as $modulo_ativo){
						if($modulo_ativo == $modulo['caminho']){
							$continuar = true;
							break;
						}
					}
					
					if(!$continuar)continue;
					
					(int)$linha;(int)$coluna;
					$linha--;$coluna--;
					
					$linha = $linha * $linha_grade;
					$coluna = $coluna * $coluna_grade;
					
					$cel_aux2 = modelo_var_troca($cel_aux2,"#img_coluna#",$coluna);
					$cel_aux2 = modelo_var_troca($cel_aux2,"#img_linha#",$linha);
					$cel_aux2 = modelo_var_troca($cel_aux2,"#item_href",$modulo['caminho']);
					$cel_aux2 = modelo_var_troca_tudo($cel_aux2,"#item_nome",$modulo['nome']);
					$cel_aux2 = modelo_var_troca_tudo($cel_aux2,"#item_titulo",$modulo['titulo']);
					
					$cel_aux[$modulo['id_modulo_pai']] = modelo_var_in($cel_aux[$modulo['id_modulo_pai']],'<!-- '.$cel_nome.' -->',$cel_aux2.($filhos_count_aux[$modulo['id_modulo_pai']] < $filhos_count[$modulo['id_modulo_pai']] - 1?'<div class="ini_barra_lateral"></div>':''));
					
					$filhos[$modulo['id_modulo_pai']] = true;
					$filhos_count_aux[$modulo['id_modulo_pai']]++;
				}
			}
		}
	}
	
	// =========================== ORDEM MANUAL ================
	
	if($ordenar_categoria){
		$cel_nome = 'cat';
		asort($ordenacao_categoria);
		foreach($ordenacao_categoria as $id_modulo1 => $ordem){
			foreach($cel_aux as $id_modulo2 =>$cel_val){
				if($filhos[$id_modulo2] && $id_modulo1 == $id_modulo2){
					$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_val);
				}
			}
		}
		foreach($cel_aux as $id_modulo =>$cel_val){
			if($filhos[$id_modulo] && !$ordenacao_categoria[$id_modulo])
				$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_val);
		}
	} else {
		$ordem_manual[1] = true;
		$ordem_manual[2] = true;
		
		if($filhos[2])$pagina = modelo_var_in($pagina,'<!-- cat -->',$cel_aux[2]);
		if($filhos[1])$pagina = modelo_var_in($pagina,'<!-- cat -->',$cel_aux[1]);
		
		//============================================================
		
		foreach($cel_aux as $id_modulo =>$cel_val){
			$cel_nome = 'cat';
			if($filhos[$id_modulo] && !$ordem_manual[$id_modulo])
				$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$cel_val);
		}
	}
	return $pagina;
}

function operacoesAdministrativas(){
	global $_SYSTEM;
	
	removeConteudoDeDiretorioTemporario($_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."tmp");
}

function main(){
	global $_SYSTEM;
	global $_HTML;
	
	if($_GET['opcao'])			$opcao = $_GET['opcao'];
	if($_POST['opcao'])			$opcao = $_POST['opcao'];

	operacoesAdministrativas();

	switch($opcao){
		case 'mudar_logo':				$saida = (operacao('mudar_logo') ? mudar_logo() : paginaInicial());break;
		case 'mudar_logo_base':			$saida = (operacao('mudar_logo') ? mudar_logo_base() : paginaInicial());break;
		default: 						$saida = paginaInicial();
	}

	$_HTML['body'] = $saida;
	
	echo pagina();
}

main();

?>