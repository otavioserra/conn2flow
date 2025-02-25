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

$_VERSAO_MODULO 		= '2.2.0';
$_VERSAO_MODULO			=		$_VERSAO;

global $_HTML;
global $_SYSTEM;
global $_THIS_PAGINA;

$_THIS_ROOT = '/'.$_SYSTEM['ROOT'] . 'includes/menu/';
$_THIS_PAGINA = $_SYSTEM['PATH'] . 'includes' . $_SYSTEM['SEPARADOR'] . 'menu' . $_SYSTEM['SEPARADOR'] . 'html.html';

$_HTML['js'] = "	<link href=\"".$_THIS_ROOT."css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n".
"	<script type=\"text/javascript\" src=\"".$_THIS_ROOT."js.js?v=".$_VERSAO_MODULO."\"></script>\n".$_HTML['js'];

function menu_configuracoes(){
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	
	if(!$_SESSION[$_SYSTEM['ID']."configuracoes"]){
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$resultado2 = banco_select_name
		(
			banco_campos_virgulas(Array(
				'disklimit',
				'diskused',
				'plano',
				'user_cpanel',
			))
			,
			"host",
			"WHERE id_usuario='".($usuario['id_usuario_pai'] ? $usuario['id_usuario_pai'] : $usuario['id_usuario'])."'"
			." AND atual IS TRUE"
		);
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'atual_pago',
			))
			,
			"assinaturas",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND plano='".$resultado2[0]['plano']."'"
		);
		
		if($resultado)
		foreach($resultado as $res){
			if($res['atual_pago']){
				$atual_pago = true;
			}
		}
		
		$_SESSION[$_SYSTEM['ID']."configuracoes"] = Array(
			'disklimit' => $resultado2[0]['disklimit'],
			'diskused' => $resultado2[0]['diskused'],
			'plan' => $resultado2[0]['plano'],
			'user_cpanel' => $resultado2[0]['user_cpanel'],
			'atual_pago' => $atual_pago,
		);
	}
	
	$configuracoes = $_SESSION[$_SYSTEM['ID']."configuracoes"];
	
	if($configuracoes['plano'] != '1'){
		if($configuracoes['atual_pago']){
			$plano = $_SYSTEM['B2MAKE_PLANOS'][$configuracoes['plano']]['nome'];			
		} else {
			$plano = $_SYSTEM['B2MAKE_PLANO_FREE'];
		}
	} else {
		$plano = $_SYSTEM['B2MAKE_PLANO_FREE'];
	}
	
	$_VARIAVEIS_JS['user_cpanel'] = $configuracoes['user_cpanel'];
	$_VARIAVEIS_JS['disklimit'] = $configuracoes['disklimit'];
	$_VARIAVEIS_JS['diskused'] = $configuracoes['diskused'];
	$_VARIAVEIS_JS['plan'] = 'Plano '.$plano;
}

function menu_main(){
	global $_THIS_PAGINA;
	global $_SYSTEM;
	global $_PROJETO;
	global $_VARIAVEIS_JS;
	global $_MENU_LATERAL_GESTOR;
	global $_B2MAKE_GESTOR_MENU;
	global $_LOCAL_ID;
	
	if($_PROJETO['b2make_permissao_id'])
	foreach($_PROJETO['b2make_permissao_id'] as $permissao_id){
		if($_SESSION[$_SYSTEM['ID']."permissao_id"] == $permissao_id){
			$modelo = modelo_abrir($_THIS_PAGINA);
			if($_MENU_LATERAL_GESTOR){
				$url_atual = rtrim(str_replace("/".$_SYSTEM['ROOT'], "", $_SERVER["REQUEST_URI"]), '/');
				$line_height = 40;
				
				$menu = modelo_tag_val($modelo,'<!-- menu-gestor < -->','<!-- menu-gestor > -->');
				
				$_VARIAVEIS_JS['gestor_menu'] = $_B2MAKE_GESTOR_MENU;
				
				$cel_nome = 'menu-pai'; $cel[$cel_nome] = modelo_tag_val($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');$menu = modelo_tag_in($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
				$cel_nome = 'menu-filho'; $cel[$cel_nome] = modelo_tag_val($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');$menu = modelo_tag_in($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
				
				if($_B2MAKE_GESTOR_MENU)
				foreach($_B2MAKE_GESTOR_MENU as $gm){
					if(!$gm['parent-id']){
						$cel_nome = 'menu-pai';
						$cel_aux = $cel[$cel_nome];
						$filhos = '';
						$class = '';
						$pai_extra = '';
						$filhos_extra = '';
						$pai_atual = false;
						$num_filhos = 0;
						
						$cel_aux = modelo_var_troca($cel_aux,"#id#",$gm['id']);
						$cel_aux = modelo_var_troca($cel_aux,"#url#",$gm['url']);
						$cel_aux = modelo_var_troca($cel_aux,"#icone#",$gm['id']);
						$cel_aux = modelo_var_troca($cel_aux,"#titulo#",$gm['title']);
						
						if($gm['url'] == $url_atual || $gm['id'] == $_LOCAL_ID){
							$pai_atual = true;
						}
						
						foreach($_B2MAKE_GESTOR_MENU as $gm2){
							if($gm2['parent-id'] == $gm['id']){
								$cel_nome = 'menu-filho';
								$cel_aux2 = $cel[$cel_nome];
								$class_filho = '';
								
								
								if($gm2['url'] == $url_atual || $gm2['id'] == $_LOCAL_ID || '/'.$gm2['url'] == $_SERVER["REQUEST_URI"]){
									$pai_atual = true;
									$class_filho = ' b2make-gestor-menu-filho-atual';
								}
								
								$cel_aux2 = modelo_var_troca($cel_aux2,"#class_filho#",$class_filho);
								$cel_aux2 = modelo_var_troca($cel_aux2,"#id#",$gm2['id']);
								$cel_aux2 = modelo_var_troca($cel_aux2,"#url#",$gm2['url']);
								$cel_aux2 = modelo_var_troca($cel_aux2,"#titulo#",$gm2['title']);
								
								$filhos .= $cel_aux2;
								
								$num_filhos++;
							}
						}
						
						$cel_aux = modelo_var_troca($cel_aux,"#filhos#",$filhos);
						
						if($pai_atual){
							$class = ' b2make-gestor-menu-pai-atual';
							if($num_filhos > 0){
								$pai_extra = ' style="height:'.($num_filhos*$line_height + $line_height).'px;"';
								$filhos_extra = ' style="height:'.($num_filhos*$line_height).'px; margin-top:'.$line_height.'px;"';
							}
							
							$pai_extra .= ' data-open="true"';
						}
						
						$cel_aux = modelo_var_troca($cel_aux,"#class#",$class);
						$cel_aux = modelo_var_troca($cel_aux,"#cont-pai-extra#",$pai_extra);
						$cel_aux = modelo_var_troca($cel_aux,"#cont-filhos-extra#",$filhos_extra);
						
						$menu = modelo_var_in($menu,'#menu#',$cel_aux);
					}
				}
				
				$menu = modelo_var_troca($menu,'#menu#','');
			} else {
				$menu = modelo_tag_val($modelo,'<!-- menu < -->','<!-- menu > -->');
			}
			menu_configuracoes();
		}
	}
	
	$permissao_modulos = $_SESSION[$_SYSTEM['ID']."modulos"];
	
	if($permissao_modulos)
	foreach($permissao_modulos as $var => $valor){
		$permissoes_modulos[] = $var;
	}
	
	$_VARIAVEIS_JS['permissoes_modulos'] = $permissoes_modulos;
	$_VARIAVEIS_JS['permissoes_store_modulos'] = $_PROJETO['b2make_stores_permissoes'];
	
	return $menu;
}

return menu_main();

?>