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

function cadastro_campos($params){
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	
	$campos_layout = '<!-- campos -->';
	
	if(!$_CONEXAO_BANCO)$connect = true;
	if($connect)banco_conectar();
	
	$campos = $params['campos'];
	
	if($campos)
	foreach($campos as $campo){
		if(
			$campo['tipo'] == 'radio' ||
			$campo['tipo'] == 'checkbox' ||
			$campo['tipo'] == 'select'
		){
			$checklist[] = $campo['nome'];
		}
	}
	
	if($checklist){
		$where = '';
		foreach($checklist as $nome){
			if($where)$where .= " OR ";
			$where .= "nome='".$nome."'"
		}
		
		$cad_checklist = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_checklist',
			))
			,
			"cad_checklist",
			"WHERE " . $where
			." AND status='A'"
		);
		
		if($cad_checklist){
			$where = '';
			foreach($cad_checklist as $id_checklist){
				if($where)$where .= " OR ";
				$where .= "id_checklist='".$id_checklist."'"
			}
			
			$cad_checkoption = banco_select_name
			(
				banco_campos_virgulas(Array(
					'label',
					'value',
					'checked',
				))
				,
				"cad_checkoption",
				"WHERE " . $where
				." AND status='A'"
			);
		}
	}
	
	$layout_campos = $params['layout_campos'];
	$layout_campo = $params['layout_campo'];
	
	if($campos)
	foreach($campos as $campo){
		$cel_nome = 'campos';
		
		if($campo['tipo'] == 'hidden'){
			$cel_aux = $params['layout_campos'];
			$cel_nome2 = 'hidden'; $cel_aux = modelo_tag_val($layout_campos,'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->');
			
			$cel_aux = modelo_var_troca($cel_aux,"#nome#",$campo['nome']);
			$cel_aux = modelo_var_troca($cel_aux,"#id#",$campo['id'] ? $campo['id'] : $campo['nome']);
			$cel_aux = modelo_var_troca($cel_aux,"#valor#",$campo['valor']);
			$cel_aux = modelo_var_troca($cel_aux,"#extra#",$campo['extra']);
		} else {
			$cel_aux = $params['layout_campo'];
			
			$cel_nome2 = 'label'; $label = modelo_tag_val($layout_campos,'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->');
			$cel_nome2 = $campo['tipo']; $cel_aux2 = modelo_tag_val($layout_campos,'<!-- '.$cel_nome2.' < -->','<!-- '.$cel_nome2.' > -->');
			
			$label = modelo_var_troca($label,"#for#",$campo['id'] ? $campo['id'] : $campo['nome']);
			$label = modelo_var_troca($label,"#extra#",$campo['label_extra']);
			
			$cel_aux2 = modelo_var_troca($cel_aux2,"#nome#",$campo['nome']);
			$cel_aux2 = modelo_var_troca($cel_aux2,"#id#",$campo['id'] ? $campo['id'] : $campo['nome']);
			$cel_aux2 = modelo_var_troca($cel_aux2,"#tabindex#",$tabindex);
			$cel_aux2 = modelo_var_troca($cel_aux2,"#valor#",$campo['valor']);
			$cel_aux2 = modelo_var_troca($cel_aux2,"#extra#",$campo['extra']);
		}
		
		$campos_layout = modelo_var_in($campos_layout,'<!-- '.$cel_nome.' -->',$cel_aux);
	}
	
	if($connect)banco_fechar_conexao();
	
	return $campos_layout;
}

function cadastro($params){
	global $_SYSTEM;
	
	if(!$params['layout_url'])$params['layout_url'] = $_SYSTEM['PATH'] . 'includes' . $_SYSTEM['SEPARADOR'] . 'cadastro.html';
	if(!$params['max_file_size'])$params['max_file_size'] = 50000000;
	if(!$params['botao_title'])$params['botao_title'] = "Clique nesse botão para GRAVAR as alterações";
	if(!$params['botao_value'])$params['botao_value'] = "Gravar";
	if(!$params['erro_nao_encontrado'])$params['erro_nao_encontrado'] = "Resultado não encontrado";
	
	$modelo = modelo_abrir($params['layout_url']);
	
	$layout = modelo_tag_val($modelo,'<!-- layout < -->','<!-- layout > -->');
	$cadastro = modelo_tag_val($modelo,'<!-- cadastro < -->','<!-- cadastro > -->');
	$params['layout_campo'] = modelo_tag_val($modelo,'<!-- layout_campo < -->','<!-- layout_campo > -->');
	$params['layout_campos'] = modelo_tag_val($modelo,'<!-- layout_campos < -->','<!-- layout_campos > -->');
	
	$campos = cadastro_campos($params);
	
	// ================================= Definir variáveis principais do formulário ===============================
	
	$formulario = $params['formulario'];
	
	if(!$formulario['width'])$formulario['width'] = 500;
	if(!$formulario['style'])$formulario['style'] = 'width: '.$formulario['width'].'px;';
	if(!$formulario['class'])$formulario['class'] = 'form_cadastro div_lista';
	if(!$formulario['action'])$formulario['action'] = '.';
	if(!$formulario['method'])$formulario['method'] = 'post';
	if(!$formulario['id'])$formulario['id'] = $formulario['name'];
	
	$cadastro = modelo_var_troca($cadastro,"#extra#",' '.$formulario['style'].' '.$formulario['class'].($formulario['extra'] ? ' '.$formulario['extra'] : ''));
	$cadastro = modelo_var_troca($cadastro,"#form#action#",$formulario['action']);
	$cadastro = modelo_var_troca($cadastro,"#form#method#",$formulario['method']);
	$cadastro = modelo_var_troca($cadastro,"#form#name#",$formulario['name']);
	$cadastro = modelo_var_troca($cadastro,"#form#id#",$formulario['id']);
	
	$cadastro = modelo_var_troca($cadastro,"#input#max_file_size#",$params['max_file_size']);
	$cadastro = modelo_var_troca($cadastro,"#input#opcao#",$formulario['opcao']);
	$cadastro = modelo_var_troca($cadastro,"#input#id#",$formulario['tabela_id']);
	$cadastro = modelo_var_troca($cadastro,"#input#formulario#",$formulario['id']);
	
	$cadastro = modelo_var_troca($cadastro,"<!-- campos -->",$campos);
	$cadastro = modelo_var_troca($cadastro,"#input#campos_obrigatorios#",$campos_obrigatorios);
	
	if($params['nao_mostrar_botao']){
		$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	} else {
		$cadastro = modelo_var_troca($cadastro,"#input#botao#value#",$params['botao_value']);
		$cadastro = modelo_var_troca($cadastro,"#input#botao#title#",$params['botao_title']);
	}
	
	// ================================= Modificar Layout Principal ===============================
	
	if($params['info_acima']) $info_acima = $params['info_acima'];
	if($params['info_abaixo']) $info_abaixo = $params['info_abaixo'];
	
	$layout = modelo_var_troca($layout,"#info_acima#",$info_acima);
	$layout = modelo_var_troca($layout,"#cadastro#",$cadastro);
	$layout = modelo_var_troca($layout,"#info_abaixo#",$info_abaixo);
	
	return $layout;
}

?>