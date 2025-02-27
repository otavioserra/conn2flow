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

$_VERSAO_MODULO_INCLUDE				=	'1.3.2';

// ================================= Funções Auxiliares ===============================

function interface_data_hora_from_datetime($data_hora){
	$data_hora = explode(" ",$data_hora);
	$data_aux = explode("-",$data_hora[0]);
	$data = $data_aux[2] . "/" . $data_aux[1] . "/" .$data_aux[0];
	$hora = $data_hora[1];

	$retorno[0] = $data;
	$retorno[1] = $hora;

	return $retorno;
}

function format_size($size) {
	$sizes = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
	if ($size == 0) { return('n/a'); } else {
	return (round($size/pow(1024, ($i = floor(log($size, 1024)))), $i > 1 ? 2 : 0) . $sizes[$i]); }
}

// ================================= Listagem BD ===============================

function interface_busca($parametros){
	$parametros['layout_tag1'] = '<!-- busca < -->';
	$parametros['layout_tag2'] = '<!-- busca > -->';

	$modelo = modelo_abrir($parametros['layout_url']);
	$modelo = modelo_tag_val($modelo,$parametros['layout_tag1'],$parametros['layout_tag2']);

	$modelo = modelo_var_troca($modelo,"#buscar_url",$parametros['busca_url']);
	$modelo = modelo_var_troca($modelo,"#buscar_opcao",$parametros['busca_opcao']);

	$modelo = modelo_var_troca($modelo,"#buscar_name",($parametros['busca_name']?$parametros['busca_name']:'busca_nome'));
	$modelo = modelo_var_troca($modelo,"#buscar_id",($parametros['busca_name']?$parametros['busca_name']:'busca_nome'));
	$modelo = modelo_var_troca($modelo,"#buscar_val",$parametros['busca_titulo']?$parametros['busca_titulo']:$parametros['ferramenta']);

	return $modelo;
}

function interface_menu_principal($parametros){
	/*
	Array(
		'menu_principal' => Array( // array com todos os campos das opções do menu
			'id' => $valor, // OPCIONAL -  Id da opção
			'url' => $valor, // link da opção
			'title' => $valor, // título da opção
			'img' => $valor, // caminho da imagem
			'img_src2' => $valor, // OPCIONAL - caminho da imagem2
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
			'link_extra' => $valor, // OPCIONAL - parâmetros extras no link
			'img_extra' => $valor, // OPCIONAL - parâmetros extras na imagem
			'bloquear' => $valor, // OPCIONAL - Parâmetro para caso da opção ser de bloqueio
			'bloq_tipo' => $valor, // OPCIONAL - Qual bloqueio será feito
		),
	);
	*/

	global $_CONEXAO_BANCO;
	global $_INTERFACE_OPCAO;

	$parametros['layout_tag1'] = '<!-- menu_principal < -->';
	$parametros['layout_tag2'] = '<!-- menu_principal > -->';
	$parametros['layout_cel1_1'] = '<!-- cel1 < -->';
	$parametros['layout_cel1_2'] = '<!-- cel1 > -->';

	$modelo = modelo_abrir($parametros['layout_url']);
	$modelo = modelo_tag_val($modelo,$parametros['layout_tag1'],$parametros['layout_tag2']);

	$cel1 = modelo_tag_val($modelo,$parametros['layout_cel1_1'],$parametros['layout_cel1_2']);
	$modelo = modelo_tag_in($modelo,$parametros['layout_cel1_1'],$parametros['layout_cel1_2'],'<!-- menu_cel1 -->');

	if($_INTERFACE_OPCAO == 'editar'){
		if(!$_CONEXAO_BANCO) $connect = true;
		if($connect)banco_conectar();
		if(!$parametros['tabela_nao_connect']){
			$tabela = banco_select
			(
				banco_campos_virgulas($parametros['tabela_campos'])
				,
				$parametros['tabela_nome'],
				"WHERE " . $parametros['tabela_campos'][$parametros['tabela_id_posicao']] . "='" . $parametros['informacao_id'] . "'"
			);
		}
		if($connect)banco_fechar_conexao();

		if($tabela[0][$parametros['tabela_status_posicao']] == 'A'){
			$bloq_title = $parametros['bloquear_titulo_2'];
			$bloq_tipo = '1';
		} else {
			$bloq_title = $parametros['bloquear_titulo_1'];
			$bloq_tipo = '2';
		}

		$parametros['menu_principal'] = interface_menu_opcoes_definir_id(
			Array(
			'menu_id' => $parametros['informacao_id'], // Menu id
			'menu' => $parametros['menu_principal'], // array com todos os campos das opções do menu
		));
		$parametros['menu_principal'] = interface_menu_opcoes_definir_bloqueio(
			Array(
			'bloq_tipo' => $bloq_tipo, // Opção bloqueio
			'bloq_title' => $bloq_title, // Opção bloqueio
			'menu' => $parametros['menu_principal'], // array com todos os campos das opções do menu
		));
	}
	
	if($parametros['forcar_informacao_id']){
		$parametros['menu_principal'] = interface_menu_opcoes_definir_id(
			Array(
			'menu_id' => $parametros['forcar_informacao_id'], // Menu id
			'menu' => $parametros['menu_principal'], // array com todos os campos das opções do menu
		));
	}
	
	$sep = '<div class="float-left in_barra_lateral"></div>';

	foreach($parametros['menu_principal'] as $menu){
		$cel_aux = $cel1;

		if($_INTERFACE_OPCAO == 'editar'){
			if($menu['bloquear']){
				$menu['url'] = modelo_var_troca($menu['url'],"#id",$menu['id']);
				$menu['url'] = modelo_var_troca($menu['url'],"#tipo",$menu['bloq_tipo']);

				if($menu['bloq_tipo'] != 1){
					$menu['img_coluna'] = $menu['img_coluna2'];
					$menu['img_linha'] = $menu['img_linha2'];
				}

			} else {
				$menu['url'] = modelo_var_troca($menu['url'],"#id",$menu['id']);
			}
		}
	
		if($parametros['forcar_informacao_id']){
			$menu['url'] = modelo_var_troca($menu['url'],"#id",$menu['id']);
		}
	
		if($count < count($parametros['menu_principal']) - 1){
			$separador = $sep;
		} else {
			$separador = '';
		}
		
		$cel_aux = modelo_var_troca($cel_aux,"#separador#",$separador);
		
		$menu['img_coluna']--;
		$menu['img_linha']--;
		
		$cel_aux = modelo_var_troca($cel_aux,"#img_coluna#",$menu['img_coluna']*20);
		$cel_aux = modelo_var_troca($cel_aux,"#img_linha#",$menu['img_linha']*20);
		
		$cel_aux = modelo_var_troca($cel_aux,"#name#",$menu['name']);
		$cel_aux = modelo_var_troca($cel_aux,"#url",$menu['url']);
		$cel_aux = modelo_var_troca($cel_aux,"#title",$menu['title']);
		$cel_aux = modelo_var_troca($cel_aux,"#alt",$menu['title']);
		$cel_aux = modelo_var_troca($cel_aux,"#img",$menu['img']);

		if($menu['img_extra'])	$cel_aux = modelo_var_in($cel_aux,"#img_extra",modelo_var_troca($menu['img_extra'],"#id",$menu['id']));
		if($menu['height'])		$cel_aux = modelo_var_in($cel_aux,"#img_extra",'height="'.$menu['height'].'" ');
		if($menu['height'])		$cel_aux = modelo_var_in($cel_aux,"#img_extra",'height="'.$menu['height'].'" ');

		$cel_aux = modelo_var_troca($cel_aux,"#img_extra",'');

		if($menu['link_extra'])		$cel_aux = modelo_var_in($cel_aux,"#link_extra",modelo_var_troca($menu['link_extra'],"#id",$menu['id']));

		$cel_aux = modelo_var_troca($cel_aux,"#link_extra",'');

		$modelo = modelo_var_in($modelo,'<!-- menu_cel1 -->',$cel_aux);
		
		$count++;
	}

	return $modelo;
}

function interface_menu_legenda($parametros){
	/*
	Array(
		'menu' => Array( // array com todos os campos das opções do menu
			'id' => $valor, // idenficador
			'url' => $valor, // link da opção
			'title' => $valor, // título da opção
			'img_src' => $valor, // caminho da imagem
			'img_src2' => $valor, // OPCIONAL - caminho da imagem2
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
			'link_extra' => $valor, // OPCIONAL - parâmetros extras no link
			'img_extra' => $valor, // OPCIONAL - parâmetros extras na imagem
			'bloquear' => $valor, // OPCIONAL - Parâmetro para caso da opção ser de bloqueio
			'bloq_tipo' => $valor, // OPCIONAL - Qual bloqueio será feito
		),
	);
	*/

	$parametros['layout_tag1'] = '<!-- legenda < -->';
	$parametros['layout_tag2'] = '<!-- legenda > -->';
	$parametros['layout_cel1_1'] = '<!-- cel1 < -->';
	$parametros['layout_cel1_2'] = '<!-- cel1 > -->';
	$parametros['layout_cel2_1'] = '<!-- cel2 < -->';
	$parametros['layout_cel2_2'] = '<!-- cel2 > -->';

	$modelo = modelo_abrir($parametros['layout_url']);
	$modelo = modelo_tag_val($modelo,$parametros['layout_tag1'],$parametros['layout_tag2']);

	$cel1 = modelo_tag_val($modelo,$parametros['layout_cel1_1'],$parametros['layout_cel1_2']);
	$modelo = modelo_tag_in($modelo,$parametros['layout_cel1_1'],$parametros['layout_cel1_2'],'<!-- menu_cel1 -->');
	$cel2 = modelo_tag_val($modelo,$parametros['layout_cel2_1'],$parametros['layout_cel2_2']);
	$modelo = modelo_tag_in($modelo,$parametros['layout_cel2_1'],$parametros['layout_cel2_2'],'<!-- menu_cel2 -->');
	
	$sep = '<div class="in_barra_lateral2"></div>';
	
	if($parametros['menu_opcoes'])
	foreach($parametros['menu_opcoes'] as $menu){
		$cel_aux = $cel1;
		
		if($count < count($parametros['menu_opcoes']) - 1){
			$separador = $sep;
		} else {
			$separador = '';
		}

		$cel_aux = modelo_var_troca($cel_aux,"#separador#",$separador);
		$cel_aux = modelo_var_troca($cel_aux,"#img#",$menu['img_src']);
		$cel_aux = modelo_var_troca($cel_aux,"#legenda#",$menu['legenda']);

		$modelo = modelo_var_in($modelo,'<!-- menu_cel1 -->',$cel_aux);
		
		$count++;
	}

	return $modelo;
}

function interface_menu_opcoes($parametros){
	/*
	Array(
		'menu' => Array( // array com todos os campos das opções do menu
			'id' => $valor, // idenficador
			'url' => $valor, // link da opção
			'title' => $valor, // título da opção
			'img_src' => $valor, // caminho da imagem
			'img_src2' => $valor, // OPCIONAL - caminho da imagem2
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
			'link_extra' => $valor, // OPCIONAL - parâmetros extras no link
			'img_extra' => $valor, // OPCIONAL - parâmetros extras na imagem
			'bloquear' => $valor, // OPCIONAL - Parâmetro para caso da opção ser de bloqueio
			'bloq_tipo' => $valor, // OPCIONAL - Qual bloqueio será feito
		),
	);
	*/

	$parametros['layout_tag1'] = '<!-- menu_opcoes < -->';
	$parametros['layout_tag2'] = '<!-- menu_opcoes > -->';
	$parametros['layout_cel1_1'] = '<!-- cel1 < -->';
	$parametros['layout_cel1_2'] = '<!-- cel1 > -->';
	$parametros['layout_cel2_1'] = '<!-- cel2 < -->';
	$parametros['layout_cel2_2'] = '<!-- cel2 > -->';
	$parametros['layout_cel3_1'] = '<!-- cel3 < -->';
	$parametros['layout_cel3_2'] = '<!-- cel3 > -->';

	$modelo = modelo_abrir($parametros['layout_url']);
	$modelo = modelo_tag_val($modelo,$parametros['layout_tag1'],$parametros['layout_tag2']);

	$cel1 = modelo_tag_val($modelo,$parametros['layout_cel1_1'],$parametros['layout_cel1_2']);
	$modelo = modelo_tag_in($modelo,$parametros['layout_cel1_1'],$parametros['layout_cel1_2'],'<!-- menu_cel1 -->');
	$cel2 = modelo_tag_val($modelo,$parametros['layout_cel2_1'],$parametros['layout_cel2_2']);
	$modelo = modelo_tag_in($modelo,$parametros['layout_cel2_1'],$parametros['layout_cel2_2'],'<!-- menu_cel2 -->');
	$cel3 = modelo_tag_val($modelo,$parametros['layout_cel3_1'],$parametros['layout_cel3_2']);
	$modelo = modelo_tag_in($modelo,$parametros['layout_cel3_1'],$parametros['layout_cel3_2'],'<!-- menu_cel3 -->');

	if($parametros['menu_opcoes'])
	foreach($parametros['menu_opcoes'] as $menu){
		if($menu['input_name']){
			$cel_aux = $cel2;

			if($menu['tabela']){
				$menu['tabela_extra'] = modelo_var_troca($menu['tabela_extra'],"#id",$menu['id']);

				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						$menu['tabela_campo'],
					))
					,
					$menu['tabela'],
					$menu['tabela_extra']
				);

				if($resultado[0][$menu['tabela_campo']])
					$checked = htmlParam('checked','checked');
				else
					$checked = '';
			}

			$menu['input_name'] = modelo_var_troca($menu['input_name'],"#id",$menu['id']);
			$menu['input_id'] = modelo_var_troca($menu['input_id'],"#id",$menu['id']);
			$menu['input_extra'] = modelo_var_troca($menu['input_extra'],"#id",$menu['id']);

			if($menu['value'])		$menu['input_extra'] .= htmlParam('value',$menu['value']);
			if($menu['class'])		$menu['input_extra'] .= htmlParam('class',$menu['class']);
			if($menu['title'])		$menu['input_extra'] .= htmlParam('title',$menu['title']);
			if($menu['size'])		$menu['input_extra'] .= htmlParam('size',$menu['size']);
			if($menu['maxlength'])	$menu['input_extra'] .= htmlParam('maxlength',$menu['maxlength']);
			if($checked)			$menu['input_extra'] .= $checked;

			$cel_aux = modelo_var_troca($cel_aux,"#input_name",$menu['input_name']);
			$cel_aux = modelo_var_troca($cel_aux,"#input_id",($menu['input_id']?$menu['input_id']:$menu['input_name']));
			$cel_aux = modelo_var_troca($cel_aux,"#input_type",($menu['input_type']?$menu['input_type']:'text'));
			$cel_aux = modelo_var_troca($cel_aux,"#input_extra",$menu['input_extra']);
		} else {
			if($menu['opcao_div']){
				$cel_aux = $cel3;
			} else {
				$cel_aux = $cel1;
			}

			if($menu['bloquear']){
				$menu['url'] = modelo_var_troca($menu['url'],"#id",$menu['id']);
				$menu['url'] = modelo_var_troca($menu['url'],"#tipo",$menu['bloq_tipo']);

				if($menu['bloq_tipo'] != 1){
					$menu['img_coluna'] = $menu['img_coluna2'];
					$menu['img_linha'] = $menu['img_linha2'];
				}

			} else {
				$menu['url'] = modelo_var_troca($menu['url'],"#id",$menu['id']);
				$menu['url'] = modelo_var_troca($menu['url'],"#campo",$menu['campo']);
			}

			$menu['img_coluna']--;
			$menu['img_linha']--;
			
			$cel_aux = modelo_var_troca($cel_aux,"#img_coluna#",$menu['img_coluna']*16);
			$cel_aux = modelo_var_troca($cel_aux,"#img_linha#",$menu['img_linha']*16);
			
			$cel_aux = modelo_var_troca($cel_aux,"#url",$menu['url']);
			$cel_aux = modelo_var_troca($cel_aux,"#title",$menu['title']);
			$cel_aux = modelo_var_troca($cel_aux,"#alt",$menu['title']);

			if($menu['img_extra'])	$cel_aux = modelo_var_in($cel_aux,"#img_extra",modelo_var_troca($menu['img_extra'],"#id",$menu['id']));
			if($menu['height'])		$cel_aux = modelo_var_in($cel_aux,"#img_extra",htmlParam('height',$menu['height']));
			if($menu['html_id'])	$cel_aux = modelo_var_in($cel_aux,"#img_extra",htmlParam('id',modelo_var_troca($menu['html_id'],"#campo",$menu['campo'])));
			if($menu['class'])		$cel_aux = modelo_var_in($cel_aux,"#img_extra",htmlParam('class',$menu['class']));

			$cel_aux = modelo_var_troca($cel_aux,"#img_extra",'');

			if($menu['link_extra'])		$cel_aux = modelo_var_in($cel_aux,"#link_extra",modelo_var_troca($menu['link_extra'],"#id",$menu['id']));
			if($menu['target'])			$cel_aux = modelo_var_in($cel_aux,"#link_extra",htmlParam('target',$menu['target']));

			$cel_aux = modelo_var_troca($cel_aux,"#link_extra",'');
		}

		$modelo = modelo_var_in($modelo,'<!-- menu_cel1 -->',$cel_aux);
	}

	return $modelo;
}

function interface_menu_opcoes_arquivos($parametros){
	/*
	Array(
		'menu' => Array( // array com todos os campos das opções do menu
			'id' => $valor, // idenficador
			'url' => $valor, // link da opção
			'title' => $valor, // título da opção
			'img_src' => $valor, // caminho da imagem
			'img_src2' => $valor, // OPCIONAL - caminho da imagem2
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
			'link_extra' => $valor, // OPCIONAL - parâmetros extras no link
			'img_extra' => $valor, // OPCIONAL - parâmetros extras na imagem
			'bloquear' => $valor, // OPCIONAL - Parâmetro para caso da opção ser de bloqueio
			'bloq_tipo' => $valor, // OPCIONAL - Qual bloqueio será feito
		),
	);
	*/

	$parametros['layout_tag1'] = '<!-- menu_opcoes < -->';
	$parametros['layout_tag2'] = '<!-- menu_opcoes > -->';
	$parametros['layout_cel1_1'] = '<!-- cel1 < -->';
	$parametros['layout_cel1_2'] = '<!-- cel1 > -->';
	$parametros['layout_cel2_1'] = '<!-- cel2 < -->';
	$parametros['layout_cel2_2'] = '<!-- cel2 > -->';

	$modelo = modelo_abrir($parametros['layout_url']);
	$modelo = modelo_tag_val($modelo,$parametros['layout_tag1'],$parametros['layout_tag2']);

	$cel1 = modelo_tag_val($modelo,$parametros['layout_cel1_1'],$parametros['layout_cel1_2']);
	$modelo = modelo_tag_in($modelo,$parametros['layout_cel1_1'],$parametros['layout_cel1_2'],'<!-- menu_cel1 -->');
	$cel2 = modelo_tag_val($modelo,$parametros['layout_cel2_1'],$parametros['layout_cel2_2']);
	$modelo = modelo_tag_in($modelo,$parametros['layout_cel2_1'],$parametros['layout_cel2_2'],'<!-- menu_cel2 -->');

	foreach($parametros['menu_opcoes'] as $menu){
		$inserir = false;
		if($parametros['arquivo'] && !$menu['diretorio'])	$inserir = true;
		if(!$parametros['arquivo'] && !$menu['arquivo'])	$inserir = true;

		if($inserir){
			if($menu['input_name']){
				$cel_aux = $cel2;

				if($menu['tabela']){
					$menu['tabela_extra'] = modelo_var_troca($menu['tabela_extra'],"#id",$menu['id']);

					$resultado = banco_select_name
					(
						banco_campos_virgulas(Array(
							$menu['tabela_campo'],
						))
						,
						$menu['tabela'],
						$menu['tabela_extra']
					);

					if($resultado[0][$menu['tabela_campo']])
						$checked = htmlParam('checked','checked');
					else
						$checked = '';
				}

				$menu['input_name'] = modelo_var_troca($menu['input_name'],"#id",$menu['id']);
				$menu['input_id'] = modelo_var_troca($menu['input_id'],"#id",$menu['id']);
				$menu['input_extra'] = modelo_var_troca($menu['input_extra'],"#id",$menu['id']);

				if($menu['value'])		$menu['input_extra'] .= htmlParam('value',$menu['value']);
				if($menu['class'])		$menu['input_extra'] .= htmlParam('class',$menu['class']);
				if($menu['title'])		$menu['input_extra'] .= htmlParam('title',$menu['title']);
				if($menu['size'])		$menu['input_extra'] .= htmlParam('size',$menu['size']);
				if($menu['maxlength'])	$menu['input_extra'] .= htmlParam('maxlength',$menu['maxlength']);
				if($checked)			$menu['input_extra'] .= $checked;

				$cel_aux = modelo_var_troca($cel_aux,"#input_name",$menu['input_name']);
				$cel_aux = modelo_var_troca($cel_aux,"#input_id",($menu['input_id']?$menu['input_id']:$menu['input_name']));
				$cel_aux = modelo_var_troca($cel_aux,"#input_type",($menu['input_type']?$menu['input_type']:'text'));
				$cel_aux = modelo_var_troca($cel_aux,"#input_extra",$menu['input_extra']);
			} else {
				$cel_aux = $cel1;

				if($menu['bloquear']){
					$menu['url'] = modelo_var_troca($menu['url'],"#id",$menu['id']);
					$menu['url'] = modelo_var_troca($menu['url'],"#tipo",$menu['bloq_tipo']);

					if($menu['bloq_tipo'] != 1){
						$menu['img_coluna'] = $menu['img_coluna2'];
						$menu['img_linha'] = $menu['img_linha2'];
					}


				} else {
					$menu['url'] = modelo_var_troca($menu['url'],"#id",$menu['id']);
					$menu['url'] = modelo_var_troca($menu['url'],"#campo",$menu['campo']);
				}
				
				$menu['img_coluna']--;
				$menu['img_linha']--;
				
				$cel_aux = modelo_var_troca($cel_aux,"#img_coluna#",$menu['img_coluna']*16);
				$cel_aux = modelo_var_troca($cel_aux,"#img_linha#",$menu['img_linha']*16);

				$cel_aux = modelo_var_troca($cel_aux,"#url",$menu['url']);
				$cel_aux = modelo_var_troca($cel_aux,"#title",$menu['title']);
				$cel_aux = modelo_var_troca($cel_aux,"#alt",$menu['title']);

				if($menu['img_extra'])	$cel_aux = modelo_var_in($cel_aux,"#img_extra",modelo_var_troca($menu['img_extra'],"#id",$menu['id']));
				if($menu['height'])		$cel_aux = modelo_var_in($cel_aux,"#img_extra",htmlParam('height',$menu['height']));
				if($menu['html_id'])	$cel_aux = modelo_var_in($cel_aux,"#img_extra",htmlParam('id',modelo_var_troca($menu['html_id'],"#campo",$menu['campo'])));
				if($menu['class'])		$cel_aux = modelo_var_in($cel_aux,"#img_extra",htmlParam('class',$menu['class']));

				$cel_aux = modelo_var_troca($cel_aux,"#img_extra",'');

				if($menu['classa'])			$cel_aux = modelo_var_in($cel_aux,"#link_extra",htmlParam('class',$menu['classa']));
				if($menu['opcao'])			$cel_aux = modelo_var_in($cel_aux,"#link_extra",htmlParam('url',$menu['id']));
				if($menu['link_extra'])		$cel_aux = modelo_var_in($cel_aux,"#link_extra",modelo_var_troca($menu['link_extra'],"#id",$menu['id']));
				if($menu['target'])			$cel_aux = modelo_var_in($cel_aux,"#link_extra",htmlParam('target',$menu['target']));

				$cel_aux = modelo_var_troca($cel_aux,"#link_extra",'');
			}

			$modelo = modelo_var_in($modelo,'<!-- menu_cel1 -->',$cel_aux);
		}
	}

	return $modelo;
}

function interface_menu_opcoes_definir_id($parametros){
	/*
	Array(
		'menu_id' => $valor, // Menu id
		'menu' => Array( // array com todos os campos das opções do menu
			'id' => $valor, // idenficador
			'url' => $valor, // link da opção
			'title' => $valor, // título da opção
			'img_src' => $valor, // caminho da imagem
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
			'link_extra' => $valor, // OPCIONAL - parâmetros extras no link
			'img_extra' => $valor, // OPCIONAL - parâmetros extras na imagem
		),
	);
	*/

	if($parametros['menu'])
	foreach($parametros['menu'] as $menu){
		if($parametros['input_value'])	$menu['value'] = $parametros['input_value'];
		if($parametros['campo'])		$menu['campo'] = $parametros['campo'];
		$menu['id'] = $parametros['menu_id'];

		$menu_out[] = $menu;
	}

	return $menu_out;
}

function interface_menu_opcoes_definir_bloqueio($parametros){
	/*
	Array(
		'bloq_tipo' => $valor, // Opção bloqueio
		'bloq_title' => $valor, // Título bloqueio
		'menu' => Array( // array com todos os campos das opções do menu
			'id' => $valor, // idenficador
			'url' => $valor, // link da opção
			'title' => $valor, // título da opção
			'img_src' => $valor, // caminho da imagem
			'img_src2' => $valor, // OPCIONAL - caminho da imagem2
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
			'link_extra' => $valor, // OPCIONAL - parâmetros extras no link
			'img_extra' => $valor, // OPCIONAL - parâmetros extras na imagem
			'bloquear' => $valor, // OPCIONAL - Parâmetro para caso da opção ser de bloqueio
			'bloq_tipo' => $valor, // OPCIONAL - Qual bloqueio será feito
		),
	);
	*/

	if($parametros['menu'])
	foreach($parametros['menu'] as $menu){
		if($menu['bloquear']){
			$menu['bloq_tipo'] = $parametros['bloq_tipo'];
			$menu['title'] = $parametros['bloq_title'];
		}

		$menu_out[] = $menu;
	}

	return $menu_out;
}

function interface_menu_paginas($parametros){
	/*
	Array(
		'menu_paginas_id' => $valor, // Id do menu
		'menu_paginas_reiniciar' => $valor, // Reiniciar menu
		'tabela_id' => $valor, // tag delimitadora do menu
		'tabela_nome' => $valor, // tag delimitadora do menu
		'tabela_extra' => $valor, // cel de cada opção do menu
	);
	*/

	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_HTML;
	global $_DADOS;
	global $_MENU_NUM;
	global $_MENU_COMPUTADO;
	global $_MENU_PAGINA;
	global $_LOCAL_ID;
	global $_MENU_NUM_PAGINAS;
	global $_MENU_PAGINAS_INICIAL;
	
	$parametros['layout_tag1'] = '<!-- menu_paginas < -->';
	$parametros['layout_tag2'] = '<!-- menu_paginas > -->';

	$id = $parametros['menu_paginas_id'];
	$reiniciar = $parametros['menu_paginas_reiniciar'];
	$forcar_inicio = $parametros['forcar_inicio'];
	$not_scroll = $parametros['not_scroll'];
	
	$_MENU_NUM++;

	if(!$_CONEXAO_BANCO) $connect = true;
	if($connect)banco_conectar();
	if(!$parametros['tabela_nao_connect']){
		$res = banco_select
		(
			$parametros['tabela_id'],
			$parametros['tabela_nome'],
			$parametros['tabela_extra'].
			($parametros['menu_limit']?$parametros['menu_limit']:'')
		);
	}
	if($connect)banco_fechar_conexao();

	if($res){
		$_DADOS = true;
		
		if($forcar_inicio){
			$_SESSION[$_SYSTEM['ID'].$id."nPaginas"] = false;
			$_SESSION[$_SYSTEM['ID'].$id."dados_num"] = false;
			$_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] = false;
		}

		if(
			!$_SESSION[$_SYSTEM['ID'].$id."dados_num"] ||
			$_SESSION[$_SYSTEM['ID'].$id."dados_num"] != count($res)
		){
			$numDados = $_SESSION[$_SYSTEM['ID'].$id."dados_num"] = count($res);
			$reiniciar = true;
		} else {
			$numDados = $_SESSION[$_SYSTEM['ID'].$id."dados_num"];
		}

		if($_SESSION[$_SYSTEM['ID'].$id."pagina_menu_num_paginas"] != $_HTML['MENU_NUM_PAGINAS'])
			$reiniciar = true;

		if($reiniciar){
			if($numDados % $_HTML['MENU_NUM_PAGINAS'] != 0)
				$nPaginas = floor($numDados / $_HTML['MENU_NUM_PAGINAS']) + 1;
			else
				$nPaginas = floor($numDados / $_HTML['MENU_NUM_PAGINAS']);

			if(!$_SESSION[$_SYSTEM['ID'].$id."nPaginas"]){
				$_SESSION[$_SYSTEM['ID'].$id."nPaginas"] = $nPaginas;
				$_SESSION[$_SYSTEM['ID'].$id."pagina"] = 1;
				$_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] = '0';
				$_SESSION[$_SYSTEM['ID'].$id."pagina_menu_num_paginas"] = $_HTML['MENU_NUM_PAGINAS'];
			} else if($_SESSION[$_SYSTEM['ID'].$id."nPaginas"] != $nPaginas){
				$_SESSION[$_SYSTEM['ID'].$id."nPaginas"] = $nPaginas;
				if($_SESSION[$_SYSTEM['ID'].$id."pagina"] > $_SESSION[$_SYSTEM['ID'].$id."nPaginas"]){
					$_SESSION[$_SYSTEM['ID'].$id."pagina"] = $nPaginas;
				}
				if($_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] >= $_SESSION[$_SYSTEM['ID'].$id."nPaginas"]){
					$_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] = $nPaginas-1;
				}
			}
			
			if(!$not_scroll)$_MENU_NUM_PAGINAS = $nPaginas;
			if($parametros['menu_paginas_inicial'])$_MENU_PAGINAS_INICIAL = $_MENU_NUM_PAGINAS;
		}

		if($_SESSION[$_SYSTEM['ID'].$id."nPaginas"] > 1){
			if(!$_MENU_PAGINA)
				$_MENU_PAGINA = $_SESSION[$_SYSTEM['ID'].$id."pagina"];

			if($_LOCAL_ID == "index") $url = ''; else $url = $_SERVER["PHP_SELF"];
			
			$pagina			=	$_MENU_PAGINA;
			$nPaginas		=	$_SESSION[$_SYSTEM['ID'].$id."nPaginas"];

			switch($_REQUEST[opcao_menu]){
				case 'comeco':		$pagina = 1;break;
				case 'anterior':	$pagina--;break;
				case 'proximo':		$pagina++;break;
				case 'ultimo':		$pagina = $nPaginas;break;
				case 'paginas':		$pagina = (int)($_REQUEST[paginas]);break;
			}

			if($pagina < 1)
				$pagina = 1;

			if($pagina > $_SESSION[$_SYSTEM['ID'].$id."nPaginas"])
				$pagina = $_SESSION[$_SYSTEM['ID'].$id."nPaginas"];

			if($_REQUEST[opcao_menu] && !$_MENU_COMPUTADO){
				if($pagina == 1)
					$_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] = '0';
				else
					$_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] = ($pagina-1)*$_HTML['MENU_NUM_PAGINAS'];
			}

			if(!$_MENU_COMPUTADO)
				$_SESSION[$_SYSTEM['ID'].$id."pagina"]	= $pagina;

			if(!$parametros['menu_dont_show']){
				$modelo = paginaModelo($parametros['layout_url']);
				$modelo = paginaTagValor($modelo,$parametros['layout_tag1'],$parametros['layout_tag2']);
				$options = paginaTagValor($modelo,'<!-- options < -->','<!-- options > -->');
				$modelo = paginaTrocaTag($modelo,'<!-- options < -->','<!-- options > -->','<!-- options -->');

				$modelo = paginaTrocaVarValor($modelo,'#num_menu',$_MENU_NUM);
				$modelo = paginaTrocaVarValor($modelo,'#menu_id','menu_form'.$_MENU_NUM);
				$modelo = paginaTrocaVarValor($modelo,'#menu_nome','menu_form'.$_MENU_NUM);
				$modelo = paginaTrocaVarValor($modelo,'#menu_opcao',$id);
				$modelo = paginaTrocaVarValor($modelo,'#url',$url);

				// =========================== MENU EXTRA =============================

				if($parametros['menu_vars']){
					$menu_vars = $parametros['menu_vars'];

					foreach($menu_vars as $menu_var){
						$html = html(Array(
							'tag' => 'input',
							'val' => '',
							'attr' => Array(
								'value' => $menu_var['value'],
								'name' => $menu_var['name'],
								'type' => 'hidden',
							)
						));

						$modelo = modelo_var_in($modelo,'<!-- extra -->',$html);
					}
				}

				$modelo = modelo_var_troca($modelo,'<!-- extra -->','');

				if($pagina == 1){
					$modelo = paginaTrocaTag($modelo,'<!-- comeco < -->','<!-- comeco > -->','&nbsp;');
					$modelo = paginaTrocaTag($modelo,'<!-- anterior < -->','<!-- anterior > -->','&nbsp;');
				} else if($pagina == $nPaginas){
					$modelo = paginaTrocaTag($modelo,'<!-- proximo < -->','<!-- proximo > -->','&nbsp;');
					$modelo = paginaTrocaTag($modelo,'<!-- ultimo < -->','<!-- ultimo > -->','&nbsp;');
				}

				for($i=1;$i<=$nPaginas;$i++){
					$options_aux = $options;

					if($pagina == $i)
						$checked = ' selected="selected"';
					else
						$checked = NULL;

					$options_aux = paginaTrocaVarValor($options_aux,'#num_pagina_valor',($i));
					$options_aux = paginaTrocaVarValor($options_aux,'#checked',$checked);
					$options_aux = paginaTrocaVarValor($options_aux,'#num_pagina',($i));

					$modelo = paginaInserirValor($modelo,'<!-- options -->',$options_aux);
				}

				if(!$parametros['nao_mostrar_menu'])$menu = $modelo;
			}
			
			$_MENU_COMPUTADO = true;
		}
	} else {
		$_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] = '0';
		$_SESSION[$_SYSTEM['ID'].$id."pagina"] = 1;
		$_SESSION[$_SYSTEM['ID'].$id."nPaginas"] = 1;
	}

	return $menu;
}

function interface_menu_paginas_arquivos($parametros){
	/*
	Array(
		'menu_paginas_id' => $valor, // Id do menu
		'menu_paginas_reiniciar' => $valor, // Reiniciar menu
		'tabela_id' => $valor, // tag delimitadora do menu
		'tabela_nome' => $valor, // tag delimitadora do menu
		'tabela_extra' => $valor, // cel de cada opção do menu
	);
	*/

	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_HTML;
	global $_DADOS;
	global $_MENU_NUM;
	global $_MENU_COMPUTADO;
	global $_MENU_PAGINA;

	$parametros['layout_tag1'] = '<!-- menu_paginas < -->';
	$parametros['layout_tag2'] = '<!-- menu_paginas > -->';

	$id = $parametros['diretorio_sem_path'];
	$reiniciar = $parametros['menu_paginas_reiniciar'];

	$_MENU_NUM++;
	
	if(is_dir($parametros['diretorio']))
	if($parametros['diretorio']){
		$diretorio = dir($parametros['diretorio']);
		while(false !== ($entrada = $diretorio->read())){
			if($entrada != '.' && $entrada != '..'){
				$dados_num++;
			}
		}
		$diretorio->close();
	}

	if($dados_num){
		$_DADOS = true;

		if(
			!$_SESSION[$_SYSTEM['ID'].$id."dados_num"] ||
			$_SESSION[$_SYSTEM['ID'].$id."dados_num"] != $dados_num
		){
			$numDados = $_SESSION[$_SYSTEM['ID'].$id."dados_num"] = $dados_num;
			$reiniciar = true;
		} else {
			$numDados = $_SESSION[$_SYSTEM['ID'].$id."dados_num"];
		}

		if($_SESSION[$_SYSTEM['ID'].$id."pagina_menu_num_paginas"] != $_HTML['MENU_NUM_PAGINAS'])
			$reiniciar = true;

		if($reiniciar){
			if($numDados % $_HTML['MENU_NUM_PAGINAS'] != 0)
				$nPaginas = floor($numDados / $_HTML['MENU_NUM_PAGINAS']) + 1;
			else
				$nPaginas = floor($numDados / $_HTML['MENU_NUM_PAGINAS']);

			if(!$_SESSION[$_SYSTEM['ID'].$id."nPaginas"]){
				$_SESSION[$_SYSTEM['ID'].$id."nPaginas"] = $nPaginas;
				$_SESSION[$_SYSTEM['ID'].$id."pagina"] = 1;
				$_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] = '0';
				$_SESSION[$_SYSTEM['ID'].$id."pagina_menu_num_paginas"] = $_HTML['MENU_NUM_PAGINAS'];
			} else if($_SESSION[$_SYSTEM['ID'].$id."nPaginas"] != $nPaginas){
				$_SESSION[$_SYSTEM['ID'].$id."nPaginas"] = $nPaginas;
				if($_SESSION[$_SYSTEM['ID'].$id."pagina"] > $_SESSION[$_SYSTEM['ID'].$id."nPaginas"]){
					$_SESSION[$_SYSTEM['ID'].$id."pagina"] = $nPaginas;
				}
				if($_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] >= $_SESSION[$_SYSTEM['ID'].$id."nPaginas"]){
					$_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] = $nPaginas-1;
				}
			}
		}

		if($_SESSION[$_SYSTEM['ID'].$id."nPaginas"] > 1){
			if(!$_MENU_PAGINA)
				$_MENU_PAGINA = $_SESSION[$_SYSTEM['ID'].$id."pagina"];

			$url			=	$_SERVER["PHP_SELF"];
			$pagina			=	$_MENU_PAGINA;
			$nPaginas		=	$_SESSION[$_SYSTEM['ID'].$id."nPaginas"];

			switch($_REQUEST[opcao_menu]){
				case 'comeco':		$pagina = 1;break;
				case 'anterior':	$pagina--;break;
				case 'proximo':		$pagina++;break;
				case 'ultimo':		$pagina = $nPaginas;break;
				case 'paginas':		$pagina = (int)($_REQUEST[paginas]);break;
			}

			if($pagina < 1)
				$pagina = 1;

			if($pagina > $_SESSION[$_SYSTEM['ID'].$id."nPaginas"])
				$pagina = $_SESSION[$_SYSTEM['ID'].$id."nPaginas"];

			if($_REQUEST[opcao_menu] && !$_MENU_COMPUTADO){
				if($pagina == 1)
					$_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] = '0';
				else
					$_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] = ($pagina-1)*$_HTML['MENU_NUM_PAGINAS'];
			}

			if(!$_MENU_COMPUTADO)
				$_SESSION[$_SYSTEM['ID'].$id."pagina"]	= $pagina;

			$modelo = paginaModelo($parametros['layout_url']);
			$modelo = paginaTagValor($modelo,$parametros['layout_tag1'],$parametros['layout_tag2']);
			$options = paginaTagValor($modelo,'<!-- options < -->','<!-- options > -->');
			$modelo = paginaTrocaTag($modelo,'<!-- options < -->','<!-- options > -->','<!-- options -->');

			$modelo = paginaTrocaVarValor($modelo,'#num_menu',$_MENU_NUM);
			$modelo = paginaTrocaVarValor($modelo,'#menu_id','menu_form'.$_MENU_NUM);
			$modelo = paginaTrocaVarValor($modelo,'#menu_nome','menu_form'.$_MENU_NUM);
			$modelo = paginaTrocaVarValor($modelo,'#menu_opcao',$id);
			$modelo = paginaTrocaVarValor($modelo,'#url',$url);

			if($pagina == 1){
				$modelo = paginaTrocaTag($modelo,'<!-- comeco < -->','<!-- comeco > -->','&nbsp;');
				$modelo = paginaTrocaTag($modelo,'<!-- anterior < -->','<!-- anterior > -->','&nbsp;');
			} else if($pagina == $nPaginas){
				$modelo = paginaTrocaTag($modelo,'<!-- proximo < -->','<!-- proximo > -->','&nbsp;');
				$modelo = paginaTrocaTag($modelo,'<!-- ultimo < -->','<!-- ultimo > -->','&nbsp;');
			}

			for($i=1;$i<=$nPaginas;$i++){
				$options_aux = $options;

				if($pagina == $i)
					$checked = ' selected="selected"';
				else
					$checked = NULL;

				$options_aux = paginaTrocaVarValor($options_aux,'#num_pagina_valor',($i));
				$options_aux = paginaTrocaVarValor($options_aux,'#checked',$checked);
				$options_aux = paginaTrocaVarValor($options_aux,'#num_pagina',($i));

				$modelo = paginaInserirValor($modelo,'<!-- options -->',$options_aux);
			}

			$_MENU_COMPUTADO = true;

			$menu = $modelo;
		}
	} else {
		$_SESSION[$_SYSTEM['ID'].$id."pagina_limite"] = '0';
		$_SESSION[$_SYSTEM['ID'].$id."pagina"] = 1;
		$_SESSION[$_SYSTEM['ID'].$id."nPaginas"] = 1;
	}

	return $menu;
}

function interface_lista($parametros){
	/*
	Array(
		'tabela_nome' => $valor, // Nome da tabela
		'tabela_id_posicao' => $valor, // Posicao do id
		'tabela_status_posicao' => $valor, // Posicao do status
		'bloquear_titulo_1' => $valor, // Título 1 do botão bloquear
		'bloquear_titulo_2' => $valor, // Título 2 do botão bloquear
		'tabela_campos' => $valor, // Array com os nomes dos campos
		'tabela_extra' => $valor, // Tabela extra
		'tabela_order' => $valor, // Ordenação da tabela
		'tabela_width' => $valor, // Tamanho width da tabela
		'menu_paginas_id' => $valor, // Identificador do menu
		'ferramenta' => $valor, // Texto da ferramenta
		'menu_opcoes' => Array( // array com todos os campos das opções do menu
			'url' => $valor, // link da opção
			'title' => $valor, // título da opção
			'img_src' => $valor, // caminho da imagem
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
			'link_extra' => $valor, // OPCIONAL - parâmetros extras no link
			'img_extra' => $valor, // OPCIONAL - parâmetros extras na imagem
		),
		'header_campos' => Array( // array com todos os campos do cabeçalho
			'campo' => $valor, // Valor do campo
			'oculto' => $valor, // OPCIONAL - Se o campo é oculto
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => $valor, // OPCIONAL - tamanho x da célula
			'height' => $valor, // OPCIONAL - tamanho x da célula
		),
		'header_acao' => Array( // array com todos os campos do cabeçalho
			'campo' => $valor, // Valor do campo
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => $valor, // OPCIONAL - tamanho x da célula
			'height' => $valor, // OPCIONAL - tamanho x da célula
		),
		'campos' => Array( // OPCIONAL - array com os dados dos campos
			'id' => $valor, // OPCIONAL - Se é ID da tabela e é referência para outra tabela de número desse valor
			'id_campo' => $valor, // OPCIONAL - Nome do campo do id na tabela
			'tabela' => $valor, // OPCIONAL - Se faz parte de outra tabela de número desse valor
			'mudar_valor' => $valor, // OPCIONAL - Mudar o valor desse para o de outra tabela desse número
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => $valor, // OPCIONAL - tamanho x da célula
			'height' => $valor, // OPCIONAL - tamanho x da célula
			'data' => $valor, // OPCIONAL - mostrar dados formatados para data
			'data_hora' => $valor, // OPCIONAL - mostrar dados formatados para data com hora
			'hora' => $valor, // OPCIONAL - mostrar dados formatados para hora
		),
		'outra_tabela' => Array( // OPCIONAL - Se exite outras tabelas para serem tratadas
			'id' => $valor, // Identificador da tabela
			'nome' => $valor, // Nome da tabela
			'campos' => $valor, // Array com os nomes dos campos
			'extra' => $valor, // Tabela extra
		),
	);
	*/

	global $_SYSTEM;
	global $_DADOS;
	global $_HTML;
	global $_CONEXAO_BANCO;

	$parametros['layout_tag1'] = '<!-- lista < -->';
	$parametros['layout_tag2'] = '<!-- lista > -->';
	$parametros['cel_header_1'] = '<!-- cel_header < -->';
	$parametros['cel_header_2'] = '<!-- cel_header > -->';
	$parametros['cel_valor_1'] = '<!-- cel_valor < -->';
	$parametros['cel_valor_2'] = '<!-- cel_valor > -->';
	$parametros['cel_dados_1'] = '<!-- cel_dados < -->';
	$parametros['cel_dados_2'] = '<!-- cel_dados > -->';

	if(!$parametros['tabela_width'])				$parametros['tabela_width'] = '100%';

	$modelo = modelo_abrir($parametros['layout_url']);
	$modelo = modelo_tag_val($modelo,$parametros['layout_tag1'],$parametros['layout_tag2']);

	$modelo = modelo_var_troca($modelo,"#tabela_width",$parametros['tabela_width']);
	$modelo = modelo_var_troca_tudo($modelo,"#css_tabela_lista#",$parametros['css']['tabela_lista']);
	$modelo = modelo_var_troca($modelo,"#css_acao_header#",$parametros['css']['lista_header_acao']);
	$modelo = modelo_var_troca_tudo($modelo,"#css_lista_cel#",$parametros['css']['lista_cel']);

	if(!$_CONEXAO_BANCO) $connect = true;
	if($connect)banco_conectar();

	if($_DADOS){
		$cel_header = modelo_tag_val($modelo,$parametros['cel_header_1'],$parametros['cel_header_2']);
		$modelo = modelo_tag_in($modelo,$parametros['cel_header_1'],$parametros['cel_header_2'],'<!-- cel_header -->');
		$cel_valor = modelo_tag_val($modelo,$parametros['cel_valor_1'],$parametros['cel_valor_2']);
		$modelo = modelo_tag_in($modelo,$parametros['cel_valor_1'],$parametros['cel_valor_2'],'<!-- cel_valor -->');
		$cel_dados = modelo_tag_val($modelo,$parametros['cel_dados_1'],$parametros['cel_dados_2']);
		$modelo = modelo_tag_in($modelo,$parametros['cel_dados_1'],$parametros['cel_dados_2'],'<!-- cel_dados -->');
		
		// ========== Ordenar ============
		
		if($_REQUEST['interface_ordenar']){
			$aux = explode(',',$_REQUEST['interface_ordenar']);
			$coluna = (int)$aux[1];
			
			if($_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id'].'interface_ordenar'] == $coluna){
				if($_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id'].'interface_ordenar_direcao'] > 0){
					$_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id'].'interface_ordenar_direcao'] = -1;
				} else {
					$_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id'].'interface_ordenar_direcao'] = 1;
				}
			} else {
				$_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id'].'interface_ordenar_direcao'] = 1;
			}
			
			$_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id'].'interface_ordenar'] = $coluna;
		}
		
		$interface_ordenar = $_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id'].'interface_ordenar'];
		$interface_ordenar_direcao = $_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id'].'interface_ordenar_direcao'];
		
		if($interface_ordenar){
			$parametros['tabela_order'] = $parametros['tabela_campos'][$interface_ordenar] . ' ' . ($interface_ordenar_direcao > 0 ? 'ASC' : 'DESC');
		}
		
		// ======================
		
		if($parametros['nao_mostrar_acao']){
			$modelo = modelo_tag_in($modelo,'<!-- acao_header < -->','<!-- acao_header > -->','<!-- acao_header -->');
		}

		if($parametros['nao_mostrar_header']){
			$modelo = modelo_tag_in($modelo,'<!-- header < -->','<!-- header > -->','');
		}

		if(!$parametros['tabela_nao_connect']){
			$tabela = banco_select
			(
				banco_campos_virgulas($parametros['tabela_campos'])
				,
				$parametros['tabela_nome'],
				$parametros['tabela_extra'].
				"ORDER BY " . $parametros['tabela_order'].
				" LIMIT ".$_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id']."pagina_limite"].",".$_HTML['MENU_NUM_PAGINAS']
			);
		}
		
		// --------------------------------------------------------------------------------------------------------------------------------
		// Preenchimento dos campos de cabeçalho
		// --------------------------------------------------------------------------------------------------------------------------------

		$count_campos = 0;

		if($parametros['header_campos'])
		foreach($parametros['header_campos'] as $header_campos){
			if(!$header_campos['oculto']){
				$cel_aux = $cel_header;
				
				$cel_aux = modelo_var_troca($cel_aux,"#campo_header_valor#",$header_campos['campo']);

				if($header_campos['align'])		$cel_aux = modelo_var_in($cel_aux,"#campo_header_extra#",htmlParam('align',$header_campos['align']));
				if($header_campos['valign'])	$cel_aux = modelo_var_in($cel_aux,"#campo_header_extra#",htmlParam('valign',$header_campos['valign']));
				if($header_campos['width'])		$cel_aux = modelo_var_in($cel_aux,"#campo_header_extra#",htmlParam('width',$header_campos['width']));
				if($header_campos['height'])	$cel_aux = modelo_var_in($cel_aux,"#campo_header_extra#",htmlParam('height',$header_campos['height']));
				if($header_campos['ordenar']){
					$css_order = '';
					if($interface_ordenar == $count_campos){
						if($interface_ordenar_direcao > 0)
							$css_order_direction = ' in_ordenar_up';
						else
							$css_order_direction = ' in_ordenar_down';
						
						$css_order = $css_order_direction;
					}
					
					$css_lista_header = $parametros['css']['lista_header'] . ' link_hover interface_ordenar'.$css_order;
					$campo_header_extra = ' id="'.$parametros['menu_paginas_id'].','.$count_campos.'" title="Clique para ordenar por '.$header_campos['campo'].'"';
				} else {
					$css_lista_header = $parametros['css']['lista_header'];
					$campo_header_extra = '';
				}

				$cel_aux = modelo_var_troca($cel_aux,"#campo_header_extra#",$campo_header_extra);
				$cel_aux = modelo_var_troca($cel_aux,"#campo_header_class#",$header_campos['class']);
				$cel_aux = modelo_var_troca($cel_aux,"#css_lista_header#",$css_lista_header);

				$modelo = modelo_var_in($modelo,'<!-- cel_header -->',$cel_aux);
			} else
				$campos_ocultos[$count_campos] = true;

			$count_campos++;
		}

		$header_acao = $parametros['header_acao'];

		$modelo = modelo_var_troca($modelo,"#acao_header_valor#",$header_acao['campo']);

		if($header_acao['align'])		$modelo = modelo_var_in($modelo,"#acao_header_extra#",htmlParam('align',$header_acao['align']));
		if($header_acao['valign'])		$modelo = modelo_var_in($modelo,"#acao_header_extra#",htmlParam('valign',$header_acao['valign']));
		if($header_acao['width'])		$modelo = modelo_var_in($modelo,"#acao_header_extra#",htmlParam('width',$header_acao['width']+10));
		if($header_acao['height'])		$modelo = modelo_var_in($modelo,"#acao_header_extra#",htmlParam('height',$header_acao['height']));

		$modelo = modelo_var_troca($modelo,"#acao_header_extra#",'');

		// --------------------------------------------------------------------------------------------------------------------------------
		// Criação dos dados nas células
		// --------------------------------------------------------------------------------------------------------------------------------

		$campos_todos = $parametros['campos'];
		$count_linhas = 0;
		
		if($tabela)
		foreach($tabela as $dados){
			$cel_aux2 = $cel_dados;

			$count_dados = 0;
			$dados_aux = false;

			for($i=0;$i<count($campos_todos);$i++){
				$dados_aux[] = '';
			}

			foreach($dados as $chave => $dado){
				if($chave == $count_dados){
					$campos = $campos_todos[$count_dados];
					$mudou_campo = false;

					if($count_dados == $parametros['tabela_id_posicao'])
						$tabela_id_aux[$count_linhas] = $dado;

					if($campos['mudar_valor_array']){
						$mudar_valor_array = $campos['mudar_valor_array'];
						
						foreach($mudar_valor_array as $valor_que_muda => $mudar_para_valor){
							if($dado == $valor_que_muda){
								$mudou_campo = true;
								$dados_aux[$chave] = $mudar_para_valor;
								break;
							}
						}

						if(!$mudou_campo){
							if($campos['padrao_se_nao_existe']){
								$mudou_campo = true;
								$dados_aux[$chave] = $campos['valor_padrao_se_nao_existe'];
							}
						}
					} else if($campos['id'] && $campos['mudar_valor']){
						if($dado){
							$outra_tabela = $parametros['outra_tabela'][$campos['id']-1];

							$outra_tabela_dados = banco_select
							(
								banco_campos_virgulas($outra_tabela['campos'])
								,
								$outra_tabela['nome'],
								"WHERE ".$campos['campo_id']."='".$dado."'".
								$outra_tabela['extra']
							);

							if($outra_tabela_dados){
								foreach($outra_tabela_dados as $tabela_dado){
									if($tabela_dado[$campos['campo']]){
										$mudou_campo = true;
										$dados_aux[$chave] = $tabela_dado[$campos['campo']];
										break;
									}
								}

								if(!$mudou_campo){
									if($campos['padrao_se_nao_existe']){
										$mudou_campo = true;
										$dados_aux[$chave] = $campos['valor_padrao_se_nao_existe'];
									}
								}
							} else if($campos['padrao_se_nao_existe']){
								$mudou_campo = true;
								$dados_aux[$chave] = $campos['valor_padrao_se_nao_existe'];
							}
						} else {
							if($campos['padrao_se_nao_existe']){
								$mudou_campo = true;
								$dados_aux[$chave] = $campos['valor_padrao_se_nao_existe'];
							}
						}
					} else if($campos['id']){
						$outra_tabela = $parametros['outra_tabela'][$campos['id']-1];

						$outra_tabela_dados = banco_select
						(
							banco_campos_virgulas($outra_tabela['campos'])
							,
							$outra_tabela['nome'],
							"WHERE ".$campos['campo']."='".$dado."'".
							$outra_tabela['extra']
						);

						if($outra_tabela_dados)
						foreach($outra_tabela_dados as $tabela_dado){
							$campo_num = 0;
							foreach($campos_todos as $campo){
								if(
									$tabela_dado[$campo['campo']] &&
									$campo['tabela'] == $campos['id']
								){
									$dados_aux[$campo_num] = $tabela_dado[$campo['campo']];
								}
								$campo_num++;
							}
						}
					} else if($campos['funcao_local']){
						$mudou_campo = true;
						if(!$campos['funcao_params'])$campos['funcao_params'] = Array();
						$dados_aux[$chave] = call_user_func($campos['funcao_local'],array_merge(Array('dado' => $dado), $campos['funcao_params']));
					}

					if(!$mudou_campo)
						$dados_aux[$chave] = $dado;

					$count_dados++;
				}
			}

			$dados = $dados_aux;
			$tabela_id = $tabela_id_aux[$count_linhas];
			$count_dados = 0;

			foreach($dados as $chave => $dado){
				if($chave == $count_dados){
					$campos = $parametros['campos'][$count_dados];

					if($campos['input_value'])
						$input_value = $dado;

					if($campos['campo_value'])
						$campo_value = $dado;

					if(!$campos_ocultos[$count_dados]){
						$cel_aux = $cel_valor;
						
						if($campos['input_ordem']){
							$dado = html(Array(
								'tag' => 'input',
								'val' => false,
								'attr' => Array(
									'value' => ($dado?$dado:'0'),
									'style' => 'width: 40px; text-align: center;',
									'maxlength' => '5',
									'class' => 'inteiro input_ordem',
									'name' => 'input_ordem_'.$tabela_id,
									'id' => 'input_ordem_'.$tabela_id,
									'title' => 'Defina todos os valores das ordens de todas as entradas que desejar e clique em salvar na caixa abaixo.',
								)
							));
						}
						
						if($campos['padrao_se_nao_existe'] && !$dado){ 	$dado = $campos['valor_padrao_se_nao_existe']; }
						if($campos['link']){ 				if($dado){$dado = htmlA(modelo_var_troca($campos['link'],"#id",$tabela_id),$dado,false,false,false);}}
						if($campos['dinheiro']){ 			if($dado){$dado = preparar_float_4_texto($dado);}}
						if($campos['data']){ 				if($dado){$data_hora = interface_data_hora_from_datetime($dado); $dado = $data_hora[0];}}
						if($campos['hora']){ 				if($dado){$data_hora = interface_data_hora_from_datetime($dado); $dado = $data_hora[1];}}
						if($campos['data_hora']){ 			if($dado){$data_hora = interface_data_hora_from_datetime($dado); $dado = $data_hora[0] . " " . $data_hora[1];}}
						if($campos['valor_padrao']){ 		if($dado == $campos['valor_padrao']){$dado = $campos['opcao1'];$campos['class'] = $campos['class1'];}else{$dado = $campos['opcao2'];$campos['class'] = $campos['class2']; $dado = modelo_var_troca($dado,"#id",$tabela_id); $dado = modelo_var_troca($dado,"#id",$tabela_id);}}
						if($campos['valor_padrao_array']){
							if(!$dado && $campos['nao_existe_valor'])$dado = $campos['nao_existe_valor'];
							foreach($campos['valor_padrao_array'] as $chave => $valor){
								if($dado == $chave){
									$dado = $valor;
									break;
								}
							}
						}
						
						$cel_aux = modelo_var_troca($cel_aux,"#campo_dados_valor#",$dado);

						if($campos['align'])		$cel_aux = modelo_var_in($cel_aux,"#campo_dados_extra#",htmlParam('align',$campos['align']));
						if($campos['valign'])		$cel_aux = modelo_var_in($cel_aux,"#campo_dados_extra#",htmlParam('valign',$campos['valign']));
						if($campos['width'])		$cel_aux = modelo_var_in($cel_aux,"#campo_dados_extra#",htmlParam('width',$campos['width']));
						if($campos['height'])		$cel_aux = modelo_var_in($cel_aux,"#campo_dados_extra#",htmlParam('height',$campos['height']));

						$cel_aux = modelo_var_troca($cel_aux,"#campo_dados_extra#",'');
						$cel_aux = modelo_var_troca($cel_aux,"#campo_dados_class#",($campos['class']?' '.$campos['class']:''));

						$cel_aux2 = modelo_var_in($cel_aux2,'<!-- cel_valor -->',$cel_aux);
					}

					if($count_dados == $parametros['tabela_status_posicao']){
						if($dado == 'A'){
							$bloq_title = $parametros['bloquear_titulo_2'];
							$bloq_tipo = '1';
						} else {
							$bloq_title = $parametros['bloquear_titulo_1'];
							$bloq_tipo = '2';
						}
					}

					$count_dados++;
				}
			}

			$menu_opcoes = $parametros['menu_opcoes'];
			$menu_opcoes = interface_menu_opcoes_definir_id(
				Array(
				'campo' => $campo_value, // Menu id
				'input_value' => $input_value, // Menu id
				'menu_id' => $tabela_id, // Menu id
				'menu' => $menu_opcoes, // array com todos os campos das opções do menu
			));
			$menu_opcoes = interface_menu_opcoes_definir_bloqueio(
				Array(
				'bloq_tipo' => $bloq_tipo, // Opção bloqueio
				'bloq_title' => $bloq_title, // Opção bloqueio
				'menu' => $menu_opcoes, // array com todos os campos das opções do menu
			));
			$parametros['menu_opcoes'] = $menu_opcoes;

			$menu_opcoes = interface_menu_opcoes($parametros);

			$cel_aux2 = modelo_var_troca($cel_aux2,"#acao_dados_valor#",$menu_opcoes);

			if($parametros['nao_mostrar_acao']){
				$cel_aux2 = modelo_tag_in($cel_aux2,'<!-- acao_valor < -->','<!-- acao_valor > -->','<!-- acao_valor -->');
			}

			$modelo = modelo_var_in($modelo,'<!-- cel_dados -->',$cel_aux2);

			$count_linhas++;
		}
	} else
		$modelo = '<p>Sem '.$parametros['ferramenta'].' cadastrados(as)!</p>';

	if($connect)banco_fechar_conexao();

	return $modelo;
}

function interface_lista_noticias($parametros){
	global $_SYSTEM;
	global $_DADOS;
	global $_HTML;
	global $_CONEXAO_BANCO;
	global $_LAYOUT_BASICO;
	global $_PROJETO;

	$parametros['layout_tag1'] = '<!-- lista_noticias < -->';
	$parametros['layout_tag2'] = '<!-- lista_noticias > -->';
	
	if($_PROJETO['interface']){
		if($_PROJETO['interface']['noticias-layout'])$layout = $_PROJETO['interface']['noticias-layout'];
	}
	
	if($layout){
		$modelo = $layout;
	} else {
		$modelo = modelo_abrir($parametros['layout_url']);
		$modelo = modelo_tag_val($modelo,$parametros['layout_tag1'],$parametros['layout_tag2']);
	}
	
	if(!$parametros['class_ul'])$modelo = modelo_var_troca($modelo,"#class_ul#",'lista_noticias');else$modelo = modelo_var_troca($modelo,"#class_ul#",$parametros['class_ul']);

	$modelo = modelo_var_troca($modelo,"#ul_extra#",$parametros['ul_extra']);

	$cel_nome = 'format'; $cel[$cel_nome] = modelo_tag_val($modelo,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$modelo = modelo_tag_in($modelo,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'format2'; $cel[$cel_nome] = modelo_tag_val($modelo,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$modelo = modelo_tag_in($modelo,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');

	if($parametros['format'])$cel[$cel_nome] = $parametros['format'];
	if($parametros['format2'])$cel[$cel_nome] = $parametros['format2'];

	$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($modelo,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$modelo = modelo_tag_in($modelo,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'cel2'; $cel[$cel_nome] = modelo_tag_val($modelo,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
	$modelo = modelo_tag_in($modelo,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');

	if(!$_CONEXAO_BANCO) $connect = true;
	if($connect)banco_conectar();

	if($_DADOS){
		if(!$parametros['tabela_nao_connect']){
			$tabela = banco_select
			(
				banco_campos_virgulas($parametros['tabela_campos'])
				,
				$parametros['tabela_nome'],
				$parametros['tabela_extra'].
				"ORDER BY " . $parametros['tabela_order'].
				( $parametros['tabela_limit'] ? "" : " LIMIT ".$_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id']."pagina_limite"].",".$_HTML['MENU_NUM_PAGINAS'])
			);
		}

		// --------------------------------------------------------------------------------------------------------------------------------
		// Criação dos dados nas células
		// --------------------------------------------------------------------------------------------------------------------------------

		foreach($tabela as $dados){
			$cel_aux2 = $cel_dados;

			$count_dados = 0;
			$tabela_id = $dados[$parametros['tabela_identificador']];
			$tipo = $dados[$parametros['tabela_tipo']];

			if($tipo == 'L'){
				$cel_nome = 'cel';
				$format_nome = 'format';
			} else {
				$cel_nome = 'cel2';
				$format_nome = 'format2';
			}

			$cel_aux = $cel[$cel_nome];
			$format_aux = $cel[$format_nome];

			if($parametros['prefixo_titulo'])$dados['titulo'] = $parametros['prefixo_titulo'].$dados['titulo'];

			if($tipo == 'L'){
				$format_aux = modelo_var_troca($format_aux,"#titulo#",$dados['titulo']);
				$link = $parametros['categoria_link'];
			} else {
				$format_aux = modelo_var_troca($format_aux,"#titulo#",$dados['titulo']);
				$format_aux = modelo_var_troca($format_aux,"#data#",data_from_datetime_to_text($dados['data']));
				$link = $parametros['noticia_link'];
			}
			
			if($parametros['campos_extra'])
			foreach($parametros['campos_extra'] as $key){
				$format_aux = modelo_var_troca($format_aux,"#".$key."#",$dados[$key]);
			}

			$cel_aux = modelo_var_troca($cel_aux,'<!-- '.$format_nome.' -->',$format_aux);
			$cel_aux = modelo_var_troca($cel_aux,"#a_extra#",$parametros['a_extra']);
			$cel_aux = modelo_var_troca($cel_aux,"#li_extra#",$parametros['li_extra']);
			$cel_aux = modelo_var_troca($cel_aux,"#a_link#",modelo_var_troca($link,"#id",$tabela_id));

			$modelo = modelo_var_in($modelo,'<!-- '.$cel_nome.' -->',$cel_aux);
		}
		
		if(!$_LAYOUT_BASICO){
			$modelo = html(Array(
				'tag' => 'div',
				'val' => $modelo,
				'attr' => Array(
					'id' => 'cont_secundario',
				)
			));
		} else {
			$modelo = $layout_separador . $modelo;
		}
	} else
		$modelo = '<p>Sem '.$parametros['ferramenta'].' cadastrados(as)!</p>';

	if($connect)banco_fechar_conexao();

	return $modelo;
}

function interface_lista_conteudos($parametros){
	global $_SYSTEM;
	global $_DADOS;
	global $_HTML;
	global $_CONEXAO_BANCO;
	global $_LAYOUT_BASICO;
	global $_CAMINHO_RELATIVO_RAIZ;
	
	$layout_conteudo = $parametros['layout_conteudo'];
	$layout_separador = $parametros['layout_separador'];
	$data = $parametros['data'];
	$data_hora = $parametros['data_hora'];
	$texto_limitar = $parametros['texto_limitar'];
	$forcar_miniatura = $parametros['forcar_miniatura'];
	$escala_cinza_hover = $parametros['escala_cinza_hover'];
	$desativar_conteiner_secundario = $parametros['desativar_conteiner_secundario'];
	$image_hover = $parametros['image_hover'];
	$link_texto = $parametros['link_texto'];
	$link_se_nao_existe = $parametros['link_se_nao_existe'];
	$link_target = $parametros['link_target'];
	$link_conteudo = $parametros['link_conteudo'];
	$link_raiz = $parametros['link_raiz'];
	$classes = $parametros['classes'];
	$no_defaults = $parametros['no_defaults'];

	if(!$_CONEXAO_BANCO) $connect = true;
	if($connect)banco_conectar();

	if($_DADOS){
		if(!$parametros['tabela_nao_connect']){
			$tabela_campos = $parametros['tabela_campos'];
			foreach($tabela_campos as $campo){
				$campos_novo[] = $campo;
				
				switch($campo){
					case 't1.imagem_pequena':
						$campos_novo[] = 't1.'.'imagem_pequena_title';
						$campos_novo[] = 't1.'.'imagem_pequena_alt';
					break;
					case 't1.imagem_grande':
						$campos_novo[] = 't1.'.'imagem_grande_title';
						$campos_novo[] = 't1.'.'imagem_grande_alt';
					break;
					case 't1.titulo_img':
						$campos_novo[] = 't1.'.'titulo_img_title';
						$campos_novo[] = 't1.'.'titulo_img_alt';
					break;
				}

				$campos_novo[] = 't1.'.'identificador';
				$campos_novo[] = 't1.'.'caminho_raiz';
			}
			$tabela_campos = $campos_novo;
			
			$tabela = banco_select
			(
				banco_campos_virgulas($tabela_campos)
				,
				$parametros['tabela_nome'],
				$parametros['tabela_extra'].
				"ORDER BY " . $parametros['tabela_order'].
				( $parametros['tabela_limit'] ? "" : " LIMIT ".$_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id']."pagina_limite"].",".$_HTML['MENU_NUM_PAGINAS'])
			);
		}

		// --------------------------------------------------------------------------------------------------------------------------------
		// Criação dos dados nas células
		// --------------------------------------------------------------------------------------------------------------------------------
		
		$tabela_campos = $parametros['tabela_campos'];
		foreach($tabela_campos as $campos){
			$campos_aux = explode('.',$campos);
			$tabela_campos_aux[] = ($campos_aux[1]?$campos_aux[1]:$campos);
		}
		$tabela_campos = $tabela_campos_aux;
		
		if($tabela)
		foreach($tabela as $dados){	
			$count_campos++;
			$cel_aux = $layout_conteudo;
			
			foreach($tabela_campos as $campos){
				if($dados[$campos]){
					switch($campos){
						case 'texto': 
							if($texto_limitar) $dados[$campos] = limitar_texto_html($dados[$campos],$texto_limitar);
						break;
						case 'data': 
							if($data) $dados[$campos] = data_from_datetime_to_text($dados[$campos]);
							if($data_hora) $dados[$campos] = data_hora_from_datetime_to_text($dados[$campos]);
						break;
						case 'galeria':
							if($dados[$campos])$dados[$campos] = conteudo_galerias_imagens_pretty_photo($dados[$campos]);
						break;
						case 'imagem_pequena':
						case 'imagem_grande':
						case 'titulo_img':
							switch($campos){
								case 'imagem_pequena':
									$title = $dados['imagem_pequena_title'];
									$alt = $dados['imagem_pequena_alt'];
								break;
								case 'imagem_grande':
									$title = $dados['imagem_grande_title'];
									$alt = $dados['imagem_grande_alt'];
								break;
								case 'titulo_img':
									$title = $dados['titulo_img_title'];
									$alt = $dados['titulo_img_alt'];
								break;
							}
							
							if($forcar_miniatura[$campos]){
								$imagem_bd_aux = explode('.',$dados[$campos]);
								$dados[$campos] = $imagem_bd_aux[0].'-mini.'.$imagem_bd_aux[1];
							}
							
							if($escala_cinza_hover){
								$imagem_bd_aux = explode('.',$dados[$campos]);
								$imagem_path = $imagem_bd_aux[0].'-pb.'.$imagem_bd_aux[1];
								$imagem_path2 = $dados[$campos];
								$dados[$campos] = $_CAMINHO_RELATIVO_RAIZ.$imagem_path;
							} else {
								$dados[$campos] = $_CAMINHO_RELATIVO_RAIZ.$dados[$campos];
							}
							
							$attr = Array(
								'src' => $dados[$campos],
								'title' => $title,
								'alt' => $alt,
								'border' => '0',
							);
							
							if($escala_cinza_hover){
								$attr['class'] = 'image_hover';
								$attr['url'] = $_CAMINHO_RELATIVO_RAIZ.$imagem_path2;
							}
							
							if($image_hover[$campos]){
								$attr['class'] = 'image_hover';
								$attr['url'] = $_CAMINHO_RELATIVO_RAIZ.$dados[$image_hover[$campos]];
							}
							
							if($classes[$campo])$attr['class'] = $classes[$campo];
							
							if(!$no_defaults[$campo]){
								$dados[$campos] = html(Array(
									'tag' => 'img',
									'val' => '',
									'attr' => $attr
								));
								
								if($escala_cinza_hover){
									$dados[$campos] .= html(Array(
										'tag' => 'img',
										'val' => '',
										'attr' => Array(
											'src' => $_CAMINHO_RELATIVO_RAIZ.$imagem_path2,
											'style' => 'display:none;',
										),
									));
								}
								
								if($image_hover[$campos]){
									$dados[$campos] .= html(Array(
										'tag' => 'img',
										'val' => '',
										'attr' => Array(
											'src' => $_CAMINHO_RELATIVO_RAIZ.$dados[$image_hover[$campos]],
											'style' => 'display:none;',
										),
									));
								}
								
							}
						break;
						case 'link_externo':
							$dado = $dados[$campos];
							if($link_raiz)$dado = '/'.$_SYSTEM['ROOT'] . $dado;
							if($dado[0] == '/') $dado = '/'.$_SYSTEM['ROOT'] . substr($dado,1,strlen($dado)-1);
							
							$attr = Array(
								'href' => $dado,
								'target' => $link_target ? $link_target : '_blank',
							);
							if($classes[$campos])$attr['class'] = $classes[$campos];
							
							if(!$no_defaults[$campos])
								$dado = html(Array(
									'tag' => 'a',
									'val' => $link_texto ? $link_texto : $dado,
									'attr' => $attr
								));
							
							$dados[$campos] = $dado;
						break;
					}
				
				} else {
					$dado = '';
					
					switch($campos){
						case 'link_externo':

							if($link_se_nao_existe){
								$attr = Array(
									'href' => $link_se_nao_existe,
									'target' => $link_target ? $link_target : '_blank',
								);
								if($classes[$campos])$attr['class'] = $classes[$campos];
								
								if(!$no_defaults[$campos])
									$dado = html(Array(
										'tag' => 'a',
										'val' => $link_texto ? $link_texto : $link_se_nao_existe,
										'attr' => $attr
									));
								else 
									$dado = $link_se_nao_existe;
							}
							
							if($link_conteudo){							
								$attr = Array(
									'href' => '/'.$_SYSTEM['ROOT'].$dados['caminho_raiz'].$dados['identificador'],
								);
								if($link_target)$attr['target'] = $link_target;
								if($classes[$campos])$attr['class'] = $classes[$campos];
								
								if(!$no_defaults[$campos])
									$dado = html(Array(
										'tag' => 'a',
										'val' => $link_texto ? $link_texto : '/'.$_SYSTEM['ROOT'].$dados['caminho_raiz'].$dados['identificador'],
										'attr' => $attr
									));
								else 
									$dado = '/'.$_SYSTEM['ROOT'].$dados['caminho_raiz'].$dados['identificador'];
							}
							
							$dados[$campos] = $dado;
						break;
					}
				}
				
				$cel_aux = modelo_var_troca_tudo($cel_aux,"#".$campos."#",$dados[$campos]);
			}

			$modelo .= $cel_aux . (count($tabela) > $count_campos ? $layout_separador : '');
		}
		
		
		
		if($_LAYOUT_BASICO || $desativar_conteiner_secundario){
			$modelo = $modelo."\n";
		} else {
			$modelo = html(Array(
				'tag' => 'div',
				'val' => $modelo,
				'attr' => Array(
					'id' => 'cont_secundario',
				)
			));		
		}
	} else
		$modelo = '<p>Sem '.$parametros['ferramenta'].' cadastrados(as)!</p>';

	if($connect)banco_fechar_conexao();

	return $modelo;
}

function interface_lista_galerias($parametros){
	global $_SYSTEM;
	global $_DADOS;
	global $_HTML;
	global $_CONEXAO_BANCO;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_PERMISSAO;
	global $_LAYOUT_BASICO;
	global $_MOBILE;
	global $_PROJETO;

	$link_class = $parametros['link_class'];
	$link_class_ajuste_margin = $parametros['link_class_ajuste_margin'];
	$imagem_tamanho = $parametros['imagem_tamanho'];
	$titulo_class = $parametros['titulo_class'];
	$not_scroll = $parametros['not_scroll'];
	$frame_width = $parametros['frame_width'];
	$frame_margin = $parametros['frame_margin'];
	$line_height = $parametros['line_height'];
	$class = $parametros['class'];
	$num_colunas = $parametros['num_colunas'];
	$link_externo = $parametros['link_externo'];
	$link_unico = $parametros['link_unico'];
	$image_hover = $parametros['image_hover'];
	$desativar_conteiner_secundario = $parametros['desativar_conteiner_secundario'];
	$galeria_imagens_banco_nome = $parametros['galeria_imagens_banco_nome'];
	$galeria_imagens_banco_id = $parametros['galeria_imagens_banco_id'];
	$link_target = ($parametros['link_target']?$parametros['link_target']:'_self');
	
	if($_MOBILE) $link_target = '';
	
	$clear = html(Array(
		'tag' => 'div',
		'val' => '',
		'attr' => Array(
			'class' => 'clear',
		)
	));

	if(!$_CONEXAO_BANCO) $connect = true;
	if($connect)banco_conectar();

	if($_DADOS){
		if(!$parametros['tabela_nao_connect']){
			$tabela = banco_select
			(
				banco_campos_virgulas($parametros['tabela_campos'])
				,
				$parametros['tabela_nome'],
				$parametros['tabela_extra'].
				"ORDER BY " . $parametros['tabela_order'].
				( $parametros['tabela_limit'] ? "" : " LIMIT ".$_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id']."pagina_limite"].",".$_HTML['MENU_NUM_PAGINAS'])
			);
		}

		// --------------------------------------------------------------------------------------------------------------------------------
		// Criação dos dados nas células
		// --------------------------------------------------------------------------------------------------------------------------------
		
		if(!$class)$class = 'imagem';
		$count = 0;
		if($tabela)
		foreach($tabela as $dados){
			$id_galerias = $dados[($galeria_imagens_banco_id?$galeria_imagens_banco_id:"id_galerias")];
			$titulo = $dados['nome'];
			$identificador = $dados['identificador'];
			
			$imagem_galeria = banco_select_name
			(
				banco_campos_virgulas(Array(
					$imagem_tamanho,
				))
				,
				($galeria_imagens_banco_nome?$galeria_imagens_banco_nome:"imagens"),
				"WHERE ".($galeria_imagens_banco_id?$galeria_imagens_banco_id:"id_galerias")."='".$id_galerias."'"
				." ORDER BY RAND() LIMIT 1"
			);
			
			$imagem_path = $imagem_galeria[0][$imagem_tamanho];
			
			if($imagem_path){
				$link = $dados['link_externo'];
				$versao = '?v='.($dados['versao']?$dados['versao']:'1.0');
				
				$image_info = imagem_info($imagem_path);
				
				$width = $image_info[0];
				$height = $image_info[1];
				
				$imagem = html(Array(
					'tag' => 'img',
					'val' => '',
					'attr' => Array(
						'src' => $_CAMINHO_RELATIVO_RAIZ.$imagem_path.$versao,
						'alt' => $titulo,
						'width' => $width,
						'height' => $height,
						'border' => '0',
						'class' => $link_class,
					)
				));
				
				if($link_unico){
					$link_unico_aux = modelo_var_troca($link_unico,'#cod',$cod);
					$link_unico_aux = modelo_var_troca($link_unico,'#id',$identificador);
					$imagem = html(Array(
						'tag' => 'a',
						'val' => $imagem,
						'attr' => Array(
							'href' => $link_unico_aux,
							'target' => $link_target,
							'style' => 'width: '.$width.'px; height: '.($height+$link_class_ajuste_margin).'px; display:block;' . ($line_height ? 'line-height: '.$line_height.'px;' : ''),
						)
					));
				} else if($link_externo && $link){
					$link_aux = modelo_var_troca($link,'#cod',$cod);
					$imagem = html(Array(
						'tag' => 'a',
						'val' => $imagem,
						'attr' => Array(
							'href' => $link_aux,
							'target' => $link_target,
							'style' => 'width: '.$width.'px; height: '.($height+$link_class_ajuste_margin).'px; display:block;' . ($line_height ? 'line-height: '.$line_height.'px;' : ''),
						)
					));
				}
				
				$titulo = html(Array(
					'tag' => 'span',
					'val' => $titulo,
					'attr' => Array(
						'class' => $titulo_class,
						'style' => 'width: '.($width+$link_class_ajuste_margin).'px;',
					)
				));

				$count++;
				
				$imagem = html(Array(
					'tag' => 'div',
					'val' => ($_PROJETO['galerias']?($_PROJETO['galerias']['titulo_abaixo']?$imagem."\n".$titulo:$titulo."\n".$imagem):$titulo."\n".$imagem),
					'attr' => Array(
						'style' => ($frame_width ? 'width: '.floor($frame_width/$num_colunas).'px; margin: 0px auto '.(2*$frame_margin).'px auto;':'margin: 0px '.$frame_margin.'px '.(2*$frame_margin).'px '.$frame_margin.'px;').' float: left; text-align: center;',
					)
				));

				$imagens .= "\n	".$imagem;
				$imagens_url .= "\n	".$imagem2;
				
				/* if($count % $num_colunas == 0){
					$imagens .= "\n	".($_MOBILE ? '' : $clear);
				} */
			}
		}
		
		if($_LAYOUT_BASICO || $desativar_conteiner_secundario){
			$modelo = html(Array(
				'tag' => 'div',
				'val' => $imagens."\n".$imagens_url,
				'attr' => Array(
					'class' => 'lista_imagens',
				)
			));
		} else {
			$modelo = html(Array(
				'tag' => 'div',
				'val' => $imagens."\n".$imagens_url,
				'attr' => Array(
					'id' => ($not_scroll?'':'cont_secundario'),
					'class' => 'lista_imagens',
					'style' => ($_MOBILE ? '' : 'width: '.$frame_width.'px; ').'margin: 0px auto 0px auto',
				)
			));		
		}
	} else
		$modelo = '<p>Sem '.$parametros['ferramenta'].' cadastrados(as)!</p>';

	if($connect)banco_fechar_conexao();

	return $modelo;
}

function interface_lista_galerias_imagens_pretty_photo($parametros){
	global $_SYSTEM;
	global $_DADOS;
	global $_HTML;
	global $_CONEXAO_BANCO;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_PERMISSAO;
	global $_LAYOUT_BASICO;
	global $_MOBILE;
	global $_PROJETO;

	$link_class = $parametros['link_class'];
	$link_class_ajuste_margin = $parametros['link_class_ajuste_margin'];
	$imagem_pequena = $parametros['imagem_pequena'];
	$imagem_grande = $parametros['imagem_grande'];
	$titulo_class = $parametros['titulo_class'];
	$not_scroll = $parametros['not_scroll'];
	$frame_width = $parametros['frame_width'];
	$frame_margin = $parametros['frame_margin'];
	$line_height = $parametros['line_height'];
	$class = $parametros['class'];
	$num_colunas = $parametros['num_colunas'];
	$menu_paginas_id = $parametros['menu_paginas_id'];
	$desativar_conteiner_secundario = $parametros['desativar_conteiner_secundario'];
	$forcar_width = $parametros['forcar_width'];
	$forcar_height = $parametros['forcar_height'];
	
	if($_PROJETO['conteudo']['galeria_imagens_parametors']){
		$galeria_imagens_parametors = $_PROJETO['conteudo']['galeria_imagens_parametors'];
		
		if($galeria_imagens_parametors['link_class'])$link_class = $galeria_imagens_parametors['link_class'];
		if($galeria_imagens_parametors['link_class_ajuste_margin'])$link_class_ajuste_margin = $galeria_imagens_parametors['link_class_ajuste_margin'];
		if($galeria_imagens_parametors['imagem_pequena'])$imagem_pequena = $galeria_imagens_parametors['imagem_pequena'];
		if($galeria_imagens_parametors['imagem_grande'])$imagem_grande = $galeria_imagens_parametors['imagem_grande'];
		if($galeria_imagens_parametors['titulo_class'])$titulo_class = $galeria_imagens_parametors['titulo_class'];
		if($galeria_imagens_parametors['not_scroll'])$not_scroll = $galeria_imagens_parametors['not_scroll'];
		if($galeria_imagens_parametors['frame_width'])$frame_width = $galeria_imagens_parametors['frame_width'];
		if($galeria_imagens_parametors['frame_margin'])$frame_margin = $galeria_imagens_parametors['frame_margin'];
		if($galeria_imagens_parametors['line_height'])$line_height = $galeria_imagens_parametors['line_height'];
		if($galeria_imagens_parametors['class'])$class = $galeria_imagens_parametors['class'];
		if($galeria_imagens_parametors['num_colunas'])$num_colunas = $galeria_imagens_parametors['num_colunas'];
		if($galeria_imagens_parametors['menu_paginas_id'])$menu_paginas_id = $galeria_imagens_parametors['menu_paginas_id'];
		if($galeria_imagens_parametors['desativar_conteiner_secundario'])$desativar_conteiner_secundario = $galeria_imagens_parametors['desativar_conteiner_secundario'];
		if($galeria_imagens_parametors['desativar_styles_images_margin'])$desativar_styles_images_margin = $galeria_imagens_parametors['desativar_styles_images_margin'];
	}
	
	if($_MOBILE) $link_target = '';
	
	$clear = html(Array(
		'tag' => 'div',
		'val' => '',
		'attr' => Array(
			'class' => 'clear',
		)
	));

	if(!$_CONEXAO_BANCO) $connect = true;
	if($connect)banco_conectar();

	if($_DADOS){
		if(!$parametros['tabela_nao_connect']){
			$tabela = banco_select
			(
				banco_campos_virgulas($parametros['tabela_campos'])
				,
				$parametros['tabela_nome'],
				$parametros['tabela_extra'].
				"ORDER BY " . $parametros['tabela_order'].
				( $parametros['tabela_limit'] ? "" : " LIMIT ".$_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id']."pagina_limite"].",".$_HTML['MENU_NUM_PAGINAS'])
			);
		}

		// --------------------------------------------------------------------------------------------------------------------------------
		// Criação dos dados nas células
		// --------------------------------------------------------------------------------------------------------------------------------
		
		if(!$class)$class = 'imagem';
		$count = 0;
		foreach($tabela as $dados){
			$imagem_bd = $dados[$imagem_pequena];
			$imagem_grande_bd = $dados[$imagem_grande];
			$titulo = $dados['descricao'];
			
			if($_PROJETO['interface']['galeria-miniatura-escala-cinza']){
				$imagem_bd_aux = explode('.',$imagem_bd);
				$imagem_path = $imagem_bd_aux[0].'_pb.'.$imagem_bd_aux[1];
				$imagem_path2 = $imagem_bd;
			} else {
				$imagem_path = $imagem_bd;
			}
			
			if($imagem_path){
				$image_info = imagem_info($imagem_path);
				
				$width = $image_info[0];
				$height = $image_info[1];
				
				if($forcar_width){
					$width = $forcar_width;
				}
				if($forcar_height){
					$height = $forcar_height;
				}
				
				$attr = Array(
					'src' => $_CAMINHO_RELATIVO_RAIZ.$imagem_path,
					'alt' => $titulo,
					'width' => $width,
					'height' => $height,
					'border' => '0',
					'class' => $link_class.($_PROJETO['interface']['galeria-miniatura-escala-cinza']?' image_hover':''),
				);
				
				if($_PROJETO['interface']['galeria-miniatura-escala-cinza'])$attr['url'] = $_CAMINHO_RELATIVO_RAIZ.$imagem_path2;
				
				$imagem = html(Array(
					'tag' => 'img',
					'val' => '',
					'attr' => $attr,
				));
				
				if($_PROJETO['interface']['galeria-miniatura-escala-cinza']){
					$imagem .= html(Array(
						'tag' => 'img',
						'val' => '',
						'attr' => Array(
							'src' => $_CAMINHO_RELATIVO_RAIZ.$imagem_path2,
							'style' => 'display:none;',
						),
					));
				}
				
				$imagem = html(Array(
					'tag' => 'a',
					'val' => $imagem,
					'attr' => Array(
						'href' => '/'.$_SYSTEM['ROOT'].$imagem_grande_bd,
						'rel' => ($_MOBILE ? 'external' : 'prettyPhoto['.$menu_paginas_id.']'),
						'style' => 'width: '.$width.'px; height: '.($height+$link_class_ajuste_margin).'px; display:block;' . ($line_height ? 'line-height: '.$line_height.'px;' : ''),
					)
				));
				
				$count++;
				
				$imagem = html(Array(
					'tag' => 'li',
					'val' => $imagem,
					'attr' => Array(
						'style' => ($frame_width ? 'width: '.floor($frame_width/$num_colunas).'px;'.($desativar_styles_images_margin?'':' margin: 0px auto '.(2*$frame_margin).'px auto;'):($desativar_styles_images_margin?'':'margin: 0px '.$frame_margin.'px '.(2*$frame_margin).'px '.$frame_margin.'px;')).' float: left; text-align: center;',
					)
				));

				$imagens .= "\n	".$imagem;
			}
		}
		
		$imagens = html(Array(
			'tag' => 'ul',
			'val' => $imagens,
			'attr' => Array(
				'class' => 'gallery clearfix',
				'style' => 'list-style-type: none; margin: 0px; padding: 0px;',
			)
		));
		
		if($_LAYOUT_BASICO || $desativar_conteiner_secundario){
			$modelo = $imagens."\n";
		} else {
			$modelo = html(Array(
				'tag' => 'div',
				'val' => $imagens."\n",
				'attr' => Array(
					'id' => ($not_scroll?'':'cont_secundario'),
					'class' => 'lista_imagens',
					'style' => ($_MOBILE ? '' : ($frame_width ?'width: '.$frame_width.'px; ':'')).'margin: 0px auto 0px auto',
				)
			));		
		}
	} else
		$modelo = '<p>Sem '.$parametros['ferramenta'].' cadastrados(as)!</p>';

	if($connect)banco_fechar_conexao();

	return $modelo;
}

function interface_lista_galerias_videos_youtube_pretty_photo($parametros){
	global $_SYSTEM;
	global $_DADOS;
	global $_HTML;
	global $_CONEXAO_BANCO;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_PERMISSAO;
	global $_LAYOUT_BASICO;
	global $_MOBILE;

	$link_class = $parametros['link_class'];
	$link_class_ajuste_margin = $parametros['link_class_ajuste_margin'];
	$imagem_pequena = $parametros['imagem_pequena'];
	$codigo = $parametros['codigo'];
	$titulo_class = $parametros['titulo_class'];
	$titulo_imagens = $parametros['titulo_imagens'];
	$titulo_imagens_height = $parametros['titulo_imagens_height'];
	$not_scroll = $parametros['not_scroll'];
	$frame_width = $parametros['frame_width'];
	$frame_margin = $parametros['frame_margin'];
	$line_height = $parametros['line_height'];
	$class = $parametros['class'];
	$num_colunas = $parametros['num_colunas'];
	$menu_paginas_id = $parametros['menu_paginas_id'];
	$desativar_conteiner_secundario = $parametros['desativar_conteiner_secundario'];
	
	if($_MOBILE) $link_target = '';
	
	$clear = html(Array(
		'tag' => 'div',
		'val' => '',
		'attr' => Array(
			'class' => 'clear',
		)
	));

	if(!$_CONEXAO_BANCO) $connect = true;
	if($connect)banco_conectar();

	if($_DADOS){
		if(!$parametros['tabela_nao_connect']){
			$tabela = banco_select
			(
				banco_campos_virgulas($parametros['tabela_campos'])
				,
				$parametros['tabela_nome'],
				$parametros['tabela_extra'].
				"ORDER BY " . $parametros['tabela_order'].
				( $parametros['tabela_limit'] ? "" : " LIMIT ".$_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id']."pagina_limite"].",".$_HTML['MENU_NUM_PAGINAS'])
			);
		}

		// --------------------------------------------------------------------------------------------------------------------------------
		// Criação dos dados nas células
		// --------------------------------------------------------------------------------------------------------------------------------
		
		if(!$class)$class = 'imagem';
		$count = 0;
		foreach($tabela as $dados){
			$imagem_bd = $dados[$imagem_pequena];
			$codigo_bd = $dados[$codigo];
			$titulo = $dados['descricao'];
			
			$imagem_path = $imagem_bd;
			
			if($imagem_path){
				$image_info = imagem_info($imagem_path);
				
				$width = $image_info[0];
				$height = $image_info[1];
				
				$imagem = html(Array(
					'tag' => 'img',
					'val' => '',
					'attr' => Array(
						'src' => $_CAMINHO_RELATIVO_RAIZ.$imagem_path,
						'alt' => $titulo,
						'width' => $width,
						'height' => $height,
						'border' => '0',
						'class' => $link_class,
					)
				));
				
				if($titulo_imagens){
					$imagem = html(Array(
						'tag' => 'div',
						'val' => $titulo,
						'attr' => Array(
							'width' => $width,
							'height' => $titulo_imagens_height,
							'class' => $titulo_class,
						)
					)).$imagem;
				}
				
				$imagem = html(Array(
					'tag' => 'a',
					'val' => $imagem,
					'attr' => Array(
						'href' => 'http://www.youtube.com/watch?v='.$codigo_bd,
						'rel' => 'prettyPhoto['.$menu_paginas_id.']',
						'style' => 'width: '.$width.'px; height: '.($height+$link_class_ajuste_margin).'px; display:block;' . ($line_height ? 'line-height: '.$line_height.'px;' : ''),
					)
				));
				
				$count++;
				
				$imagem = html(Array(
					'tag' => 'li',
					'val' => $imagem,
					'attr' => Array(
						'style' => ($frame_width ? 'width: '.floor($frame_width/$num_colunas).'px; margin: 0px auto '.(2*$frame_margin).'px auto;':'margin: 0px '.$frame_margin.'px '.(2*$frame_margin).'px '.$frame_margin.'px;').' float: left; text-align: center;height: '.($height+$link_class_ajuste_margin+$titulo_imagens_height).'px;',
					)
				));

				$imagens .= "\n	".$imagem;
			}
		}
		
		$imagens = html(Array(
			'tag' => 'ul',
			'val' => $imagens,
			'attr' => Array(
				'class' => 'gallery clearfix',
				'style' => 'list-style-type: none; margin: 0px; padding: 0px;',
			)
		));
		
		if($_LAYOUT_BASICO || $desativar_conteiner_secundario){
			$modelo = $imagens."\n";
		} else {
			$modelo = html(Array(
				'tag' => 'div',
				'val' => $imagens."\n",
				'attr' => Array(
					'id' => ($not_scroll?'':'cont_secundario'),
					'class' => 'lista_imagens',
					'style' => ($_MOBILE ? '' : ($frame_width ?'width: '.$frame_width.'px; ':'')).'margin: 0px auto 0px auto',
				)
			));		
		}
	} else
		$modelo = '<p>Sem '.$parametros['ferramenta'].' cadastrados(as)!</p>';

	if($connect)banco_fechar_conexao();

	return $modelo;
}

function interface_lista_imagens($parametros){
	global $_SYSTEM;
	global $_DADOS;
	global $_HTML;
	global $_CONEXAO_BANCO;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_PERMISSAO;
	global $_LAYOUT_BASICO;
	global $_MOBILE;

	$not_scroll = $parametros['not_scroll'];
	$frame_width = $parametros['frame_width'];
	$frame_margin = $parametros['frame_margin'];
	$line_height = $parametros['line_height'];
	$class = $parametros['class'];
	$class_image = $parametros['class_image'];
	$num_colunas = $parametros['num_colunas'];
	$link_externo = $parametros['link_externo'];
	$link_unico = $parametros['link_unico'];
	$link_conteudo = $parametros['link_conteudo'];
	$image_hover = $parametros['image_hover'];
	$escala_cinza_hover = $parametros['escala_cinza_hover'];
	$link_target = ($parametros['link_target']?$parametros['link_target']:'_self');
	
	if($_MOBILE) $link_target = '';
	
	$clear = html(Array(
		'tag' => 'div',
		'val' => '',
		'attr' => Array(
			'class' => 'clear',
		)
	));

	if(!$_CONEXAO_BANCO) $connect = true;
	if($connect)banco_conectar();

	if($_DADOS){
		array_push($parametros['tabela_campos'],'t1.imagem_pequena_title');
		array_push($parametros['tabela_campos'],'t1.imagem_pequena_alt');
		array_push($parametros['tabela_campos'],'t1.imagem_grande_title');
		array_push($parametros['tabela_campos'],'t1.imagem_grande_alt');
		
		if(!$parametros['tabela_nao_connect']){
			$tabela = banco_select
			(
				banco_campos_virgulas($parametros['tabela_campos'])
				,
				$parametros['tabela_nome'],
				$parametros['tabela_extra'].
				"ORDER BY " . $parametros['tabela_order'].
				( $parametros['tabela_limit'] ? "" : " LIMIT ".$_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id']."pagina_limite"].",".$_HTML['MENU_NUM_PAGINAS'])
			);
		}

		// --------------------------------------------------------------------------------------------------------------------------------
		// Criação dos dados nas células
		// --------------------------------------------------------------------------------------------------------------------------------
		
		if(!$class)$class = 'imagem';
		$count = 0;
		foreach($tabela as $dados){
			$titulo = $dados['titulo'];
			$caminho_raiz = $dados['caminho_raiz'];
			$identificador = $dados['identificador'];
			$imagem_path = $dados['imagem_pequena'];
			
			$link = $dados['link_externo'];
			$versao = '?v='.($dados['versao']?$dados['versao']:'1.0');
			
			$image_info = imagem_info($imagem_path);
			$width = $image_info[0];
			$height = $image_info[1];
			
			if($escala_cinza_hover){
				$imagem_path_aux = $imagem_path;
				$imagem_bd_aux = explode('.',$imagem_path_aux);
				$imagem_path = $imagem_bd_aux[0].'-pb.'.$imagem_bd_aux[1];
				$imagem_path2 = $imagem_path_aux;
				
				$image_info2 = imagem_info($imagem_path2);
				$width2 = $image_info2[0];
				$height2 = $image_info2[1];
				
				$imagem2 = html(Array(
					'tag' => 'img',
					'val' => '',
					'attr' => Array(
						'src' => $_CAMINHO_RELATIVO_RAIZ.$imagem_path2.$versao,
						'alt' => $dados['imagem_grande_alt'],
						'title' => $dados['imagem_grande_title'],
						'width' => $width2,
						'height' => $height2,
						'border' => '0',
						'style' => 'display: none;',
					)
				));
			}
			
			if($image_hover){
				$imagem_path2 = $dados['imagem_grande'];
				$image_info2 = imagem_info($imagem_path2);
				$width2 = $image_info2[0];
				$height2 = $image_info2[1];
				
				$imagem2 = html(Array(
					'tag' => 'img',
					'val' => '',
					'attr' => Array(
						'src' => $_CAMINHO_RELATIVO_RAIZ.$imagem_path2.$versao,
						'alt' => $dados['imagem_grande_alt'],
						'title' => $dados['imagem_grande_title'],
						'width' => $width2,
						'height' => $height2,
						'border' => '0',
						'style' => 'display: none;',
					)
				));
			}
			
			$imagem_attr = Array(
				'src' => $_CAMINHO_RELATIVO_RAIZ.$imagem_path.$versao,
				'alt' => $dados['imagem_pequena_alt'] ? $dados['imagem_pequena_alt'] : $titulo,
				'title' => $dados['imagem_pequena_title'],
				'width' => $width,
				'height' => $height,
				'border' => '0',
			);
			
			if($image_hover || $escala_cinza_hover){
				$imagem_attr['url'] = $_CAMINHO_RELATIVO_RAIZ.$imagem_path2;
				$imagem_attr['class'] = 'image_hover';
			}
			
			if($class_image){
				$imagem_attr['class'] = $class_image;
			}

			$imagem = html(Array(
				'tag' => 'img',
				'val' => '',
				'attr' => $imagem_attr,
			));
			
			if($link_conteudo){							
				$imagem = html(Array(
					'tag' => 'a',
					'val' => $imagem,
					'attr' => Array(
						'href' => '/'.$_SYSTEM['ROOT'].$caminho_raiz.$identificador,
						'target' => $link_target,
						//'class' => $class,
						'style' => 'width: '.$width.'px; height: '.$height.'px;' . ($line_height ? 'line-height: '.$line_height.'px;' : ''),
					)
				));
			} else if($link_unico){
				$link_unico_aux = modelo_var_troca($link_unico,'#cod',$cod);
				$imagem = html(Array(
					'tag' => 'a',
					'val' => $imagem,
					'attr' => Array(
						'href' => $link_unico_aux,
						'target' => $link_target,
						//'class' => $class,
						'style' => 'width: '.$width.'px; height: '.$height.'px;' . ($line_height ? 'line-height: '.$line_height.'px;' : ''),
					)
				));
			} else if($link_externo && $link){
				$link_aux = modelo_var_troca($link,'#cod',$cod);
				$imagem = html(Array(
					'tag' => 'a',
					'val' => $imagem,
					'attr' => Array(
						'href' => $link_aux,
						'target' => $link_target,
						//'class' => $class,
						'style' => 'width: '.$width.'px; height: '.$height.'px;' . ($line_height ? 'line-height: '.$line_height.'px;' : ''),
					)
				));
			}

			$count++;
			
			$imagem = html(Array(
				'tag' => 'div',
				'val' => $imagem,
				'attr' => Array(
					'style' => ($frame_width ? 'width: '.floor($frame_width/$num_colunas).'px; margin: 0px auto '.(2*$frame_margin).'px auto;':'margin: 0px '.$frame_margin.'px '.(2*$frame_margin).'px '.$frame_margin.'px;').' float: left; text-align: center;',
				)
			));

			$imagens .= "\n	".$imagem;
			$imagens_url .= "\n	".$imagem2;
			
			/* if($count % $num_colunas == 0){
				$imagens .= "\n	".($_MOBILE ? '' : $clear);
			} */
		}
		
		if($_LAYOUT_BASICO){
			$modelo = $imagens."\n".$imagens_url;
		} else {
			$modelo = html(Array(
				'tag' => 'div',
				'val' => $imagens."\n".$imagens_url,
				'attr' => Array(
					'id' => ($not_scroll?'':'cont_secundario'),
					'class' => 'lista_imagens',
					'style' => ($_MOBILE ? '' : 'width: '.$frame_width.'px; ').'margin: 0px auto 0px auto',
				)
			));		
		}
	} else
		$modelo = '<p>Sem '.$parametros['ferramenta'].' cadastrados(as)!</p>';

	if($connect)banco_fechar_conexao();

	return $modelo;
}

function interface_lista_imagens_animate($parametros){
	global $_SYSTEM;
	global $_DADOS;
	global $_HTML;
	global $_CONEXAO_BANCO;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_PERMISSAO;
	global $_LAYOUT_BASICO;
	global $_MOBILE;
	
	$frame_width = $parametros['frame_width'];
	$frame_margin = $parametros['frame_margin'];
	$line_height = $parametros['line_height'];
	$class = $parametros['class'];
	$num_colunas = $parametros['num_colunas'];
	$link_externo = $parametros['link_externo'];
	$link_unico = $parametros['link_unico'];
	$link_conteudo = $parametros['link_conteudo'];
	$link_target = ($parametros['link_target']?$parametros['link_target']:'_self');

	if($_MOBILE) $link_target = '';
	
	$clear = html(Array(
		'tag' => 'div',
		'val' => '',
		'attr' => Array(
			'class' => 'clear',
		)
	));

	if(!$_CONEXAO_BANCO) $connect = true;
	if($connect)banco_conectar();

	if($_DADOS){
		array_push($parametros['tabela_campos'],'t1.imagem_pequena_title');
		array_push($parametros['tabela_campos'],'t1.imagem_pequena_alt');
		
		if(!$parametros['tabela_nao_connect']){
			$tabela = banco_select
			(
				banco_campos_virgulas($parametros['tabela_campos'])
				,
				$parametros['tabela_nome'],
				$parametros['tabela_extra'].
				"ORDER BY " . $parametros['tabela_order'].
				( $parametros['tabela_limit'] ? "" : " LIMIT ".$_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id']."pagina_limite"].",".$_HTML['MENU_NUM_PAGINAS'])
			);
		}

		// --------------------------------------------------------------------------------------------------------------------------------
		// Criação dos dados nas células
		// --------------------------------------------------------------------------------------------------------------------------------
		
		if(!$class)$class = 'imagem';
		$count = 0;
		foreach($tabela as $dados){
			$titulo = $dados['titulo'];
			$imagem_path = $dados['imagem_pequena'];
			$link = $dados['link_externo'];
			$caminho_raiz = $dados['caminho_raiz'];
			$identificador = $dados['identificador'];
			$versao = '?v='.($dados['versao']?$dados['versao']:'1.0');
			
			$image_info = imagem_info($imagem_path);
			
			$width = $image_info[0];
			$height = $image_info[1];

			$imagem = html(Array(
				'tag' => 'span',
				'val' => '',
				'attr' => Array(
					'alt' => $dados['imagem_pequena_alt'] ? $dados['imagem_pequena_alt'] : $titulo,
					'title' => $dados['imagem_pequena_title'],
					'class' => 'curve',
					'style' => 'background: url('.$_CAMINHO_RELATIVO_RAIZ.$imagem_path.$versao.') no-repeat center center; width: '.$width.'px; height: '.$height.'px; z-index:9999;' . ($_MOBILE ? ' display: block;':'')
				)
			));
			
			if($link_conteudo){
				$link_unico_aux = $_CAMINHO_RELATIVO_RAIZ.$caminho_raiz.$identificador.'/';
				$imagem = html(Array(
					'tag' => 'a',
					'val' => $imagem,
					'attr' => Array(
						'href' => $link_unico_aux,
						'target' => $link_target,
						'class' => 'slide',
						'style' => 'width: '.$width.'px; height: '.$height.'px;' . ($line_height ? 'line-height: '.$line_height.'px;' : ''),
					)
				));
			} else if($link_unico){
				$link_unico_aux = modelo_var_troca($link_unico,'#cod',$cod);
				$imagem = html(Array(
					'tag' => 'a',
					'val' => $imagem,
					'attr' => Array(
						'href' => $link_unico_aux,
						'target' => $link_target,
						'class' => 'slide',
						'style' => 'width: '.$width.'px; height: '.$height.'px;' . ($line_height ? 'line-height: '.$line_height.'px;' : ''),
					)
				));
			} else if($link_externo && $link){
				$link_aux = modelo_var_troca($link,'#cod',$cod);
				$imagem = html(Array(
					'tag' => 'a',
					'val' => $imagem,
					'attr' => Array(
						'href' => $link_aux,
						'target' => $link_target,
						'class' => 'slide',
						'style' => 'width: '.$width.'px; height: '.$height.'px;' . ($line_height ? 'line-height: '.$line_height.'px;' : ''),
					)
				));
			} else {
				$imagem = html(Array(
					'tag' => 'div',
					'val' => $imagem,
					'attr' => Array(
						'class' => 'slide',
						'style' => 'width: '.$width.'px; height: '.$height.'px;' . ($line_height ? 'line-height: '.$line_height.'px;' : ''),
					)
				));
			}
			
			$imagem = "\n" . html(Array(
				'tag' => 'strong',
				'val' => $dados['imagem_pequena_title'] ? $dados['imagem_pequena_title'] : $titulo,
				'attr' => Array(
					'class' => 'title',
					'style' => $_MOBILE ? 'width: ' . $width . 'px; height: 20px; overflow: hidden; display: block;' : '',
				)
			)) . "\n" . $imagem;

			$count++;
			
			$imagem = html(Array(
				'tag' => 'div',
				'val' => $imagem,
				'attr' => Array(
					'class' => 'boxgrid',
					'style' => ($_MOBILE ? 'width: '.$width.'px; float: left; margin: 10px;' : 'width: '.$width.'px; height: '.$height.'px; ' . 'margin: 0px '.$frame_margin.'px '.$frame_margin.'px auto;'),
				)
			));

			$imagens .= "\n	".$imagem;
			$imagens_url .= "\n	".$imagem2;

			/* if($count % $num_colunas == 0){
				$imagens .= "\n	".($_MOBILE ? '' : $clear);
			} */
		}
		
		if($_LAYOUT_BASICO){
			$modelo = $imagens."\n".$imagens_url;
		} else {
			$modelo = html(Array(
				'tag' => 'div',
				'val' => $imagens."\n".$imagens_url,
				'attr' => Array(
					'id' => 'cont_secundario',
					'class' => 'imagens_animate',
					'style' => ($_MOBILE ? '' : 'width: '.$frame_width.'px; ').'margin: 0px auto 0px auto',
				)
			));
		}
	} else
		$modelo = '<p>Sem '.$parametros['ferramenta'].' cadastrados(as)!</p>';

	if($connect)banco_fechar_conexao();

	return $modelo;
}

function interface_lista_arquivos($parametros){
	/*
	Array(
		'tabela_nome' => $valor, // Nome da tabela
		'tabela_id_posicao' => $valor, // Posicao do id
		'tabela_status_posicao' => $valor, // Posicao do status
		'bloquear_titulo_1' => $valor, // Título 1 do botão bloquear
		'bloquear_titulo_2' => $valor, // Título 2 do botão bloquear
		'tabela_campos' => $valor, // Array com os nomes dos campos
		'tabela_extra' => $valor, // Tabela extra
		'tabela_order' => $valor, // Ordenação da tabela
		'tabela_width' => $valor, // Tamanho width da tabela
		'menu_paginas_id' => $valor, // Identificador do menu
		'ferramenta' => $valor, // Texto da ferramenta
		'menu_opcoes' => Array( // array com todos os campos das opções do menu
			'url' => $valor, // link da opção
			'title' => $valor, // título da opção
			'img_src' => $valor, // caminho da imagem
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
			'link_extra' => $valor, // OPCIONAL - parâmetros extras no link
			'img_extra' => $valor, // OPCIONAL - parâmetros extras na imagem
		),
		'header_campos' => Array( // array com todos os campos do cabeçalho
			'campo' => $valor, // Valor do campo
			'oculto' => $valor, // OPCIONAL - Se o campo é oculto
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => $valor, // OPCIONAL - tamanho x da célula
			'height' => $valor, // OPCIONAL - tamanho x da célula
		),
		'header_acao' => Array( // array com todos os campos do cabeçalho
			'campo' => $valor, // Valor do campo
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => $valor, // OPCIONAL - tamanho x da célula
			'height' => $valor, // OPCIONAL - tamanho x da célula
		),
		'campos' => Array( // OPCIONAL - array com os dados dos campos
			'id' => $valor, // OPCIONAL - Se é ID da tabela e é referência para outra tabela de número desse valor
			'id_campo' => $valor, // OPCIONAL - Nome do campo do id na tabela
			'tabela' => $valor, // OPCIONAL - Se faz parte de outra tabela de número desse valor
			'mudar_valor' => $valor, // OPCIONAL - Mudar o valor desse para o de outra tabela desse número
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => $valor, // OPCIONAL - tamanho x da célula
			'height' => $valor, // OPCIONAL - tamanho x da célula
			'data' => $valor, // OPCIONAL - mostrar dados formatados para data
			'data_hora' => $valor, // OPCIONAL - mostrar dados formatados para data com hora
			'hora' => $valor, // OPCIONAL - mostrar dados formatados para hora
		),
		'outra_tabela' => Array( // OPCIONAL - Se exite outras tabelas para serem tratadas
			'id' => $valor, // Identificador da tabela
			'nome' => $valor, // Nome da tabela
			'campos' => $valor, // Array com os nomes dos campos
			'extra' => $valor, // Tabela extra
		),
	);
	*/

	global $_SYSTEM;
	global $_DADOS;
	global $_HTML;
	global $_CONEXAO_BANCO;

	$parametros['layout_tag1'] = '<!-- lista < -->';
	$parametros['layout_tag2'] = '<!-- lista > -->';
	$parametros['cel_header_1'] = '<!-- cel_header < -->';
	$parametros['cel_header_2'] = '<!-- cel_header > -->';
	$parametros['cel_valor_1'] = '<!-- cel_valor < -->';
	$parametros['cel_valor_2'] = '<!-- cel_valor > -->';
	$parametros['cel_dados_1'] = '<!-- cel_dados < -->';
	$parametros['cel_dados_2'] = '<!-- cel_dados > -->';

	if(!$parametros['tabela_width'])		$parametros['tabela_width'] = '100%';
	if(!$parametros['css_tabela_lista'])	$parametros['css_tabela_lista'] = 'tabela_lista';
	if(!$parametros['css_lista_header'])	$parametros['css_lista_header'] = 'lista_header';
	if(!$parametros['css_lista_header_acao'])	$parametros['css_lista_header_acao'] = 'lista_header_acao';
	if(!$parametros['css_lista_cel'])		$parametros['css_lista_cel'] = 'lista_cel';

	$modelo = modelo_abrir($parametros['layout_url']);
	$modelo = modelo_tag_val($modelo,$parametros['layout_tag1'],$parametros['layout_tag2']);

	$modelo = modelo_var_troca($modelo,"#tabela_width",$parametros['tabela_width']);
	$modelo = modelo_var_troca($modelo,"#css_tabela_lista#",$parametros['css_tabela_lista']);

	if($_DADOS){
		$cel_header = modelo_tag_val($modelo,$parametros['cel_header_1'],$parametros['cel_header_2']);
		$modelo = modelo_tag_in($modelo,$parametros['cel_header_1'],$parametros['cel_header_2'],'<!-- cel_header -->');
		$cel_valor = modelo_tag_val($modelo,$parametros['cel_valor_1'],$parametros['cel_valor_2']);
		$modelo = modelo_tag_in($modelo,$parametros['cel_valor_1'],$parametros['cel_valor_2'],'<!-- cel_valor -->');
		$cel_dados = modelo_tag_val($modelo,$parametros['cel_dados_1'],$parametros['cel_dados_2']);
		$modelo = modelo_tag_in($modelo,$parametros['cel_dados_1'],$parametros['cel_dados_2'],'<!-- cel_dados -->');
		
		// ========== Ordenar ============
		
		if($_REQUEST['interface_ordenar']){
			$aux = explode(',',$_REQUEST['interface_ordenar']);
			$coluna = (int)$aux[1];
			
			if($_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id'].'interface_ordenar'] == $coluna){
				if($_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id'].'interface_ordenar_direcao'] > 0){
					$_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id'].'interface_ordenar_direcao'] = -1;
				} else {
					$_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id'].'interface_ordenar_direcao'] = 1;
				}
			} else {
				$_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id'].'interface_ordenar_direcao'] = 1;
			}
			
			$_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id'].'interface_ordenar'] = $coluna;
		}
		
		$interface_ordenar = $_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id'].'interface_ordenar'];
		$interface_ordenar_direcao = $_SESSION[$_SYSTEM['ID'].$parametros['menu_paginas_id'].'interface_ordenar_direcao'];
		
		$tabela_campos[] = 'nome';
		$tabela_campos[] = 'tamanho';
		$tabela_campos[] = 'tipo';
		$tabela_campos[] = 'ultimo_acesso';
		$tabela_campos[] = 'ultimo_modificacao';
		
		$parametros['tabela_campos'] = $tabela_campos;
		
		// ======================

		// --------------------------------------------------------------------------------------------------------------------------------
		// Preenchimento dos campos de cabeçalho
		// --------------------------------------------------------------------------------------------------------------------------------

		$count_campos = 0;

		$parametros['header_campos'][] = Array( // array com todos os campos do cabeçalho
			'campo' => 'Nome', // Valor do campo
			'ordenar' => true, // Valor do campo
		);
		$parametros['campos'][] = Array( // OPCIONAL - array com os dados dos campos
			'align' => $valor, // OPCIONAL - alinhamento horizontal
		);

		$parametros['header_campos'][] = Array( // array com todos os campos do cabeçalho
			'campo' => 'Tamanho', // Valor do campo
			'width' => '50', // Valor do campo
			'ordenar' => true, // Valor do campo
		);
		$parametros['campos'][] = Array( // OPCIONAL - array com os dados dos campos
			'align' => 'center', // OPCIONAL - alinhamento horizontal
		);
		
		$parametros['header_campos'][] = Array( // array com todos os campos do cabeçalho
			'campo' => 'Tipo', // Valor do campo
			'width' => '20', // Valor do campo
		);
		$parametros['campos'][] = Array( // OPCIONAL - array com os dados dos campos
			'align' => 'center', // OPCIONAL - alinhamento horizontal
		);

		$parametros['header_campos'][] = Array( // array com todos os campos do cabeçalho
			'campo' => 'Último Acesso', // Valor do campo
			'width' => '120', // Valor do campo
			'align' => 'center', // OPCIONAL - alinhamento horizontal
			'ordenar' => true, // Valor do campo
		);
		$parametros['campos'][] = Array( // OPCIONAL - array com os dados dos campos
			'align' => 'center', // OPCIONAL - alinhamento horizontal
		);

		$parametros['header_campos'][] = Array( // array com todos os campos do cabeçalho
			'campo' => 'Última Modificação', // Valor do campo
			'width' => '120', // Valor do campo
			'align' => 'center', // OPCIONAL - alinhamento horizontal
			'ordenar' => true, // Valor do campo
		);
		$parametros['campos'][] = Array( // OPCIONAL - array com os dados dos campos
			'align' => 'center', // OPCIONAL - alinhamento horizontal
		);

		foreach($parametros['header_campos'] as $header_campos){
			if(!$header_campos['oculto']){
				$cel_aux = $cel_header;

				$cel_aux = modelo_var_troca($cel_aux,"#campo_header_valor#",$header_campos['campo']);

				if($header_campos['align'])		$cel_aux = modelo_var_in($cel_aux,"#campo_header_extra#",htmlParam('align',$header_campos['align']));
				if($header_campos['valign'])	$cel_aux = modelo_var_in($cel_aux,"#campo_header_extra#",htmlParam('valign',$header_campos['valign']));
				if($header_campos['width'])		$cel_aux = modelo_var_in($cel_aux,"#campo_header_extra#",htmlParam('width',$header_campos['width']));
				if($header_campos['height'])	$cel_aux = modelo_var_in($cel_aux,"#campo_header_extra#",htmlParam('height',$header_campos['height']));
				if($header_campos['ordenar']){
					$css_order = '';
					if($interface_ordenar == $count_campos){
						if($interface_ordenar_direcao > 0)
							$css_order_direction = ' in_ordenar_up';
						else
							$css_order_direction = ' in_ordenar_down';
						
						$css_order = $css_order_direction;
					}
					
					$css_lista_header = $parametros['css']['lista_header'] . ' link_hover interface_ordenar'.$css_order;
					$campo_header_extra = ' id="'.$parametros['menu_paginas_id'].','.$count_campos.'" title="Clique para ordenar por '.$header_campos['campo'].'"';
				} else {
					$css_lista_header = $parametros['css']['lista_header'];
					$campo_header_extra = '';
				}
				
				$cel_aux = modelo_var_troca($cel_aux,"#campo_header_extra#",$campo_header_extra);
				$cel_aux = modelo_var_troca($cel_aux,"#campo_header_class#",$header_campos['class']);
				$cel_aux = modelo_var_troca($cel_aux,"#css_lista_header#",$css_lista_header);

				$modelo = modelo_var_in($modelo,'<!-- cel_header -->',$cel_aux);
			} else
				$campos_ocultos[$count_campos] = true;

			$count_campos++;
		}

		$header_acao = $parametros['header_acao'];

		$modelo = modelo_var_troca($modelo,"#acao_header_valor#",$header_acao['campo']);
		$modelo = modelo_var_troca($modelo,"#css_acao_header#",$parametros['css_lista_header_acao']);

		if($header_acao['align'])		$modelo = modelo_var_in($modelo,"#acao_header_extra#",htmlParam('align',$header_acao['align']));
		if($header_acao['valign'])		$modelo = modelo_var_in($modelo,"#acao_header_extra#",htmlParam('valign',$header_acao['valign']));
		if($header_acao['width'])		$modelo = modelo_var_in($modelo,"#acao_header_extra#",htmlParam('width',$header_acao['width']));
		if($header_acao['height'])		$modelo = modelo_var_in($modelo,"#acao_header_extra#",htmlParam('height',$header_acao['height']));

		$modelo = modelo_var_troca($modelo,"#acao_header_extra#",'');

		// --------------------------------------------------------------------------------------------------------------------------------
		// Abertura dos arquivos e diretórios
		// --------------------------------------------------------------------------------------------------------------------------------

		$diretorio = dir($parametros['diretorio']);
		while(false !== ($entrada = $diretorio->read())){
			if($entrada != '.' && $entrada != '..'){
				if(is_dir($parametros['diretorio'] . $entrada)){
					$diretorio_flag = true;
				} else {
					$diretorio_flag = false;
				}
				
				$entradas[] = Array(
					'caminho' => $entrada,
					'diretorio' => $diretorio_flag,
					'nome' => $entrada,
					'tamanho' => filesize($parametros['diretorio'] . $entrada),
					'ultimo_acesso' => fileatime($parametros['diretorio'] . $entrada),
					'ultimo_modificacao' => filectime($parametros['diretorio'] . $entrada),
				);
			}
		}
		$diretorio->close();

		if($interface_ordenar){
			$coluna_nome = $parametros['tabela_campos'][$interface_ordenar];
		} else {
			$coluna_nome = 'nome';
			$interface_ordenar_direcao = 1;
		}
		
		foreach($entradas as $c=>$key) {
			$sort_coluna[] = $key[$coluna_nome];
		}
		
		//print_r($entradas);
		array_multisort($sort_coluna, ($interface_ordenar_direcao > 0 ? SORT_ASC : SORT_DESC), $entradas);
		//print_r($entradas);
		
		// --------------------------------------------------------------------------------------------------------------------------------
		// Criação dos dados nas células
		// --------------------------------------------------------------------------------------------------------------------------------

		$campos_todos = $parametros['campos'];
		$count_linhas = 0;

		$limite_inferior = $_SESSION[$_SYSTEM['ID'].$parametros['diretorio_sem_path']."pagina_limite"];
		$limite_superior = $limite_inferior + $_HTML['MENU_NUM_PAGINAS'];

		if($entradas)
		foreach($entradas as $ent){
			if($count_linhas >= $limite_inferior && $count_linhas < $limite_superior){
				$entrada = $ent['caminho'];
				$cel_aux2 = $cel_dados;
				$cel_aux3 = $cel_dados;
				$count_dados = 0;
				$arquivo = true;

				if($ent['diretorio'])
					$arquivo = false;
				
				if($parametros['campos'])
				foreach($parametros['campos'] as $campos){
					if(!$campos_ocultos[$count_dados]){
						switch($count_dados){
							case 0: if(!$arquivo) $dado = htmlA('?opcao=lista&diretorio='.$ent['caminho'],$ent['caminho'],$target,$id,$extra); else if($parametros['permissao_download']) $dado = htmlA('?download=sim&id='.$ent['caminho'],$ent['caminho'],$target,$id,$extra); else $dado = $ent['caminho']; break;
							case 1: $dado = format_size($ent['tamanho']);break;
							case 2: if(!$arquivo) $dado = '<div class="_in_arquivos-list" style="background-position:-16px -0px;"></div>'; else $dado = '<div class="_in_arquivos-list" style="background-position:-0px -0px;"></div>'; break;
							case 3: $dado = date("d/m/Y H:i:s", $ent['ultimo_acesso']);break;
							case 4: $dado = date("d/m/Y H:i:s", $ent['ultimo_modificacao']);break;
						}

						$cel_aux = $cel_valor;

						if($campos['data']){ 				if($dado){$data_hora = interface_data_hora_from_datetime($dado); $dado = $data_hora[0];}}
						if($campos['hora']){ 				if($dado){$data_hora = interface_data_hora_from_datetime($dado); $dado = $data_hora[1];}}
						if($campos['data_hora']){ 			if($dado){$data_hora = interface_data_hora_from_datetime($dado); $dado = $data_hora[0] . " " . $data_hora[1];}}
						if($campos['valor_padrao']){ 		if($dado == $campos['valor_padrao']){$dado = $campos['opcao1'];$campos['class'] = $campos['class1'];}else{$dado = $campos['opcao2'];$campos['class'] = $campos['class2']; $dado = modelo_var_troca($dado,"#id",$tabela_id); $dado = modelo_var_troca($dado,"#id",$tabela_id);}}

						$cel_aux = modelo_var_troca($cel_aux,"#campo_dados_valor#",$dado);

						if($campos['align'])		$cel_aux = modelo_var_in($cel_aux,"#campo_dados_extra#",htmlParam('align',$campos['align']));
						if($campos['valign'])		$cel_aux = modelo_var_in($cel_aux,"#campo_dados_extra#",htmlParam('valign',$campos['valign']));
						if($campos['width'])		$cel_aux = modelo_var_in($cel_aux,"#campo_dados_extra#",htmlParam('width',$campos['width']));
						if($campos['height'])		$cel_aux = modelo_var_in($cel_aux,"#campo_dados_extra#",htmlParam('height',$campos['height']));

						$cel_aux = modelo_var_troca($cel_aux,"#campo_dados_extra#",'');
						$cel_aux = modelo_var_troca($cel_aux,"#css_lista_cel#",$parametros['css_lista_cel']);
						$cel_aux = modelo_var_troca($cel_aux,"#campo_dados_class#",($campos['class']?' '.$campos['class']:''));

						if(!$arquivo){
							$cel_aux2 = modelo_var_in($cel_aux2,'<!-- cel_valor -->',$cel_aux);
						} else
							$cel_aux3 = modelo_var_in($cel_aux3,'<!-- cel_valor -->',$cel_aux);
					}

					$count_dados++;
				}

				$menu_opcoes = $parametros['menu_opcoes'];
				$menu_opcoes = interface_menu_opcoes_definir_id(
					Array(
					'campo' => $campo_value, // Menu id
					'input_value' => $input_value, // Menu id
					'menu_id' => $entrada, // Menu id
					'menu' => $menu_opcoes, // array com todos os campos das opções do menu
				));
				$menu_opcoes = interface_menu_opcoes_definir_bloqueio(
					Array(
					'bloq_tipo' => $bloq_tipo, // Opção bloqueio
					'bloq_title' => $bloq_title, // Opção bloqueio
					'menu' => $menu_opcoes, // array com todos os campos das opções do menu
					'arquivo' => $arquivo, // array com todos os campos das opções do menu
				));
				$parametros['menu_opcoes'] = $menu_opcoes;
				$parametros['arquivo'] = $arquivo;

				$menu_opcoes = interface_menu_opcoes_arquivos($parametros);

				if(is_dir($parametros['diretorio'] . $entrada)){
					$cel_aux2 = modelo_var_troca($cel_aux2,"#acao_dados_valor#",$menu_opcoes);
					$cel_aux2 = modelo_var_troca($cel_aux2,"#css_lista_cel#",$parametros['css_lista_cel']);
					$modelo = modelo_var_in($modelo,'<!-- cel_dados -->',$cel_aux2);
				} else {
					$cel_aux3 = modelo_var_troca($cel_aux3,"#acao_dados_valor#",$menu_opcoes);
					$cel_aux3 = modelo_var_troca($cel_aux3,"#css_lista_cel#",$parametros['css_lista_cel']);
					$modelo = modelo_var_in($modelo,'<!-- cel_dados -->',$cel_aux3);
					//$celulas_files[] = $cel_aux3;
				}
			}

			$count_linhas++;
		}

		/* if($celulas_files)
		foreach($celulas_files as $cel){
			$modelo = modelo_var_in($modelo,'<!-- cel_dados -->',$cel);
		} */
	} else
		$modelo = '<p>Sem '.$parametros['ferramenta'].' cadastrados(as)!</p>';

	return $modelo;
}

function interface_layout($parametros){
	/*
	Array(
		'opcao' => $valor, // Opção para alteração do layout
		'inclusao' => $valor, // Informação para incluir na interface
		'informacao_titulo' => $valor, // Título da Informação
		'informacao_id' => $valor, // Id da Informação
		'ferramenta' => $valor, // Texto da ferramenta
		'busca' => $valor, // Formulário de busca
		'busca_url' => $valor, // Url da busca
		'busca_opcao' => $valor, // Opção da busca
		'menu_pagina_acima' => $valor, // Colocar o menu em cima
		'menu_pagina_embaixo' => $valor, // Colocar o menu em baixo
		'menu_paginas_id' => $valor, // Identificador do menu
		'menu_paginas_reiniciar' => $valor, // Reiniciar do menu
		'tabela_nome' => $valor, // Nome da tabela
		'tabela_id_posicao' => $valor, // Posicao do id
		'tabela_nao_connect' => $valor, // Se deve ou não conectar na tabela de referência
		'tabela_campos' => $valor, // Array com os nomes dos campos
		'tabela_extra' => $valor, // Tabela extra
		'tabela_order' => $valor, // Ordenação da tabela
		'tabela_width' => $valor, // Tamanho width da tabela
		'menu_principal' => Array( // array com todos os campos das opções do menu
			'url' => $valor, // link da opção
			'title' => $valor, // título da opção
			'img' => $valor, // caminho da imagem
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
			'link_extra' => $valor, // OPCIONAL - parâmetros extras no link
			'img_extra' => $valor, // OPCIONAL - parâmetros extras na imagem
		),
		'menu_opcoes' => Array( // array com todos os campos das opções do menu
			'id' => $valor, // idenficador
			'url' => $valor, // link da opção
			'title' => $valor, // título da opção
			'img_src' => $valor, // caminho da imagem
			'img_src2' => $valor, // OPCIONAL - caminho da imagem2
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
			'link_extra' => $valor, // OPCIONAL - parâmetros extras no link
			'img_extra' => $valor, // OPCIONAL - parâmetros extras na imagem
			'bloquear' => $valor, // OPCIONAL - Parâmetro para caso da opção ser de bloqueio
			'bloq_tipo' => $valor, // OPCIONAL - Qual bloqueio será feito
		),
		'header_campos' => Array( // array com todos os campos do cabeçalho
			'campo' => $valor, // Valor do campo
			'oculto' => $valor, // OPCIONAL - Se o campo é oculto
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => $valor, // OPCIONAL - tamanho x da célula
			'height' => $valor, // OPCIONAL - tamanho x da célula
		),
		'campos' => Array( // OPCIONAL - array com os dados dos campos
			'id' => $valor, // OPCIONAL - Se é ID da tabela e é referência para outra tabela de número desse valor
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => $valor, // OPCIONAL - tamanho x da célula
			'height' => $valor, // OPCIONAL - tamanho x da célula
			'data' => $valor, // OPCIONAL - mostrar dados formatados para data
			'data_hora' => $valor, // OPCIONAL - mostrar dados formatados para data com hora
			'hora' => $valor, // OPCIONAL - mostrar dados formatados para hora
		),

	);
	*/

	global $_SYSTEM;
	global $_LAYOUT_BASICO;
	global $_DADOS;

	if(!$parametros['layout_url'])$parametros['layout_url'] = $_SYSTEM['PATH'] . 'includes' . $_SYSTEM['SEPARADOR'] . 'interface.html';
	if($parametros['layout_pagina']){
		if(!$parametros['layout_tag1'])$parametros['layout_tag1'] = '<!-- layout_pagina < -->';
		if(!$parametros['layout_tag2'])$parametros['layout_tag2'] = '<!-- layout_pagina > -->';
	} else {
		if(!$parametros['layout_tag1'])$parametros['layout_tag1'] = '<!-- layout < -->';
		if(!$parametros['layout_tag2'])$parametros['layout_tag2'] = '<!-- layout > -->';
	}

	if(!$parametros['css']){
		$parametros['css'] = Array(
			'tabela_lista' => 'tabela_lista',
			'lista_header' => 'lista_header',
			'lista_header_acao' => 'lista_header_acao',
			'lista_cel' => 'lista_cel',
		);
	}

	$modelo = modelo_abrir($parametros['layout_url']);
	$modelo = modelo_tag_val($modelo,$parametros['layout_tag1'],$parametros['layout_tag2']);

	if($parametros['busca'])				$busca = interface_busca($parametros);
	if($parametros['menu_principal'])		$menu_principal = interface_menu_principal($parametros);
	if($parametros['legenda'] && ($parametros['opcao'] == 'lista' || $parametros['opcao'] == 'arquivos'))				$legenda = interface_menu_legenda($parametros);

	if($parametros['informacao_titulo']){
		$modelo = modelo_var_troca($modelo,"#informacao_titulo",$parametros['informacao_titulo']);
	} else {
		$modelo = modelo_tag_in($modelo,'<!-- informacao_titulo < -->','<!-- informacao_titulo > -->','<!-- informacao_titulo -->');
	}

	$modelo = modelo_var_troca($modelo,"#menu_principal",$menu_principal);
	$modelo = modelo_var_troca($modelo,"#legenda",$legenda);
	$modelo = modelo_var_troca($modelo,"#busca",$busca);

	if($parametros['mais_informacao_acima'])	$mais_informacao_acima = $parametros['mais_informacao_acima'];
	if($parametros['mais_informacao_abaixo'])	$mais_informacao_abaixo = $parametros['mais_informacao_abaixo'];
	
	switch($parametros['opcao']){
		case 'lista':
		case 'conteudos':
		case 'noticias':
		case 'galerias':
		case 'galerias_imagens_pretty_photo':
		case 'galerias_videos_youtube_pretty_photo':
		case 'imagens':
		case 'imagens_animate':
			if($parametros['menu_pagina_acima'])		$menu_paginas_1 = interface_menu_paginas(
				Array(
					'not_scroll' => $parametros['not_scroll'], // Id do menu
					'menu_dont_show' => $parametros['menu_dont_show'], // Id do menu
					'forcar_inicio' => $parametros['forcar_inicio'], // Id do menu
					'menu_paginas_id' => $parametros['menu_paginas_id'], // Id do menu
					'menu_paginas_reiniciar' => $parametros['menu_paginas_reiniciar'], // Reiniciar do menu
					'tabela_id' => $parametros['tabela_campos'][$parametros['tabela_id_posicao']], // tag delimitadora do menu
					'tabela_nome' => $parametros['tabela_nome'], // tag delimitadora do menu
					'tabela_extra' => $parametros['tabela_extra'], // cel de cada opção do menu
					'layout_url' => $parametros['layout_url'], // url do layout
					'menu_vars' => $parametros['menu_vars'], // Variáveis Menu
					'menu_limit' => $parametros['menu_limit'], // Limitação
					'nao_mostrar_menu' => $parametros['nao_mostrar_menu'], // Variáveis Menu
					'menu_paginas_inicial' => $parametros['menu_paginas_inicial'],
				)
			);
			if($parametros['menu_pagina_embaixo'])		$menu_paginas_2 = interface_menu_paginas(
				Array(
					'not_scroll' => $parametros['not_scroll'], // Id do menu
					'menu_dont_show' => $parametros['menu_dont_show'], // Id do menu
					'forcar_inicio' => $parametros['forcar_inicio'], // Id do menu
					'menu_paginas_id' => $parametros['menu_paginas_id'], // Id do menu
					'menu_paginas_reiniciar' => $parametros['menu_paginas_reiniciar'], // Reiniciar do menu
					'tabela_id' => $parametros['tabela_campos'][$parametros['tabela_id_posicao']], // tag delimitadora do menu
					'tabela_nome' => $parametros['tabela_nome'], // tag delimitadora do menu
					'tabela_extra' => $parametros['tabela_extra'], // cel de cada opção do menu
					'layout_url' => $parametros['layout_url'], // url do layout
					'menu_vars' => $parametros['menu_vars'], // Variáveis Menu
					'menu_limit' => $parametros['menu_limit'], // Limitação
					'nao_mostrar_menu' => $parametros['nao_mostrar_menu'], // Variáveis Menu
					'menu_paginas_inicial' => $parametros['menu_paginas_inicial'],
				)
			);
			
			switch($parametros['opcao']){
				case 'lista': if($_LAYOUT_BASICO)$modelo = interface_lista($parametros); else $modelo = modelo_var_troca($modelo,"#informacao",$mais_informacao_acima.interface_lista($parametros).$mais_informacao_abaixo); break;
				case 'noticias': if($_LAYOUT_BASICO)$modelo = interface_lista_noticias($parametros); else $modelo = modelo_var_troca($modelo,"#informacao",$mais_informacao_acima.interface_lista_noticias($parametros).$mais_informacao_abaixo); break;
				case 'galerias': if($_LAYOUT_BASICO)$modelo = interface_lista_galerias($parametros); else $modelo = modelo_var_troca($modelo,"#informacao",$mais_informacao_acima.interface_lista_galerias($parametros).$mais_informacao_abaixo); break;
				case 'galerias_imagens_pretty_photo': if($_LAYOUT_BASICO)$modelo = interface_lista_galerias_imagens_pretty_photo($parametros); else $modelo = modelo_var_troca($modelo,"#informacao",$mais_informacao_acima.interface_lista_galerias_imagens_pretty_photo($parametros).$mais_informacao_abaixo); break;
				case 'galerias_videos_youtube_pretty_photo': if($_LAYOUT_BASICO)$modelo = interface_lista_galerias_videos_youtube_pretty_photo($parametros); else $modelo = modelo_var_troca($modelo,"#informacao",$mais_informacao_acima.interface_lista_galerias_videos_youtube_pretty_photo($parametros).$mais_informacao_abaixo); break;
				case 'imagens': if($_LAYOUT_BASICO)$modelo = interface_lista_imagens($parametros); else $modelo = modelo_var_troca($modelo,"#informacao",$mais_informacao_acima.interface_lista_imagens($parametros).$mais_informacao_abaixo); break;
				case 'imagens_animate': if($_LAYOUT_BASICO)$modelo = interface_lista_imagens_animate($parametros); else $modelo = modelo_var_troca($modelo,"#informacao",$mais_informacao_acima.interface_lista_imagens_animate($parametros).$mais_informacao_abaixo); break;
				case 'conteudos': if($_LAYOUT_BASICO)$modelo = interface_lista_conteudos($parametros); else $modelo = modelo_var_troca($modelo,"#informacao",$mais_informacao_acima.interface_lista_conteudos($parametros).$mais_informacao_abaixo); break;
			}
		break;
		case 'arquivos':
			$sep = $_SYSTEM['SEPARADOR'];
			$aux_path = explode($sep.'files'.$sep.'uploads'.$sep,$parametros['diretorio']);
			$parametros['diretorio_sem_path'] = $aux_path[1];
			
			if($parametros['menu_pagina_acima'])		$menu_paginas_1 = interface_menu_paginas_arquivos(
				Array(
					'diretorio' => $parametros['diretorio'], // Id do menu
					'menu_paginas_id' => $parametros['menu_paginas_id'], // Id do menu
					'menu_paginas_reiniciar' => $parametros['menu_paginas_reiniciar'], // Reiniciar do menu
					'tabela_id' => $parametros['tabela_campos'][$parametros['tabela_id_posicao']], // tag delimitadora do menu
					'tabela_nome' => $parametros['tabela_nome'], // tag delimitadora do menu
					'tabela_extra' => $parametros['tabela_extra'], // cel de cada opção do menu
					'layout_url' => $parametros['layout_url'], // url do layout
					'diretorio_sem_path' => $parametros['diretorio_sem_path'], // Diretório sem o path
				)
			);
			if($parametros['menu_pagina_embaixo'])		$menu_paginas_2 = interface_menu_paginas_arquivos(
				Array(
					'diretorio' => $parametros['diretorio'], // Id do menu
					'menu_paginas_id' => $parametros['menu_paginas_id'], // Id do menu
					'menu_paginas_reiniciar' => $parametros['menu_paginas_reiniciar'], // Reiniciar do menu
					'tabela_id' => $parametros['tabela_campos'][$parametros['tabela_id_posicao']], // tag delimitadora do menu
					'tabela_nome' => $parametros['tabela_nome'], // tag delimitadora do menu
					'tabela_extra' => $parametros['tabela_extra'], // cel de cada opção do menu
					'layout_url' => $parametros['layout_url'], // url do layout
					'diretorio_sem_path' => $parametros['diretorio_sem_path'], // Diretório sem o path
				)
			);

			$internet = formInputHidden('internet_atual','internet_atual',$parametros['internet'],$extra);

			$modelo = modelo_var_troca($modelo,"#informacao",$mais_informacao_acima.interface_lista_arquivos($parametros).$mais_informacao_abaixo.$internet);
		break;
		default:
			$modelo = modelo_var_troca($modelo,"#informacao",$mais_informacao_acima.$parametros['inclusao'].$mais_informacao_abaixo);
	}
	
	if($parametros['input_ordem']){
		//$in_width = $parametros['header_acao']['width'];
		
		$caixa_salvar = html(Array(
			'tag' => 'div',
			'val' => 'Salvar',
			'attr' => Array(
				'style' => 'margin-top: -10px;margin-left: 0px; width: 50px; text-align: center;'.($_DADOS && $parametros['opcao'] == 'lista' ? "" : "display: none;"),
				'id' => 'input_ordem_salvar',
				'class' => 'link_hover',
				'title' => 'Clique para salvar a ordenação',
				'data-posicao' => $parametros['input_ordem'],
			)
		));
		
		$parametros['informacao_abaixo'] = $caixa_salvar.$parametros['informacao_abaixo'];
	}

	$modelo = modelo_var_troca($modelo,"#menu_paginas_1",$menu_paginas_1);
	$modelo = modelo_var_troca($modelo,"#menu_paginas_2",$menu_paginas_2);
	$modelo = modelo_var_troca($modelo,"#_informacao_acima",$parametros['informacao_acima']);
	$modelo = modelo_var_troca($modelo,"#_informacao_abaixo",$parametros['informacao_abaixo']);

	return $modelo;
}

// ================================= Calendário ===============================

function interface_data_quebrada($data){
	$data1 = explode('-',$data);
	$data2 = explode('/',$data);

	if($data1[1]){
		$retorno['dia'] = ($data1[2][0] == '0' ? $data1[2][1] : $data1[2]);
		$retorno['mes'] = ($data1[1][0] == '0' ? $data1[1][1] : $data1[1]);
		$retorno['ano'] = $data1[0];
	}

	if($data2[1]){
		$retorno['dia'] = ($data2[0][0] == '0' ? $data2[0][1] : $data2[0]);
		$retorno['mes'] = ($data2[1][0] == '0' ? $data2[1][1] : $data2[1]);
		$retorno['ano'] = $data2[2];
	}

	return $retorno;
}

function interface_verificar_ano_bisexto($ano){
	if($ano % 4 == 0)
		return true;
	else
		return false;
}

function interface_dia_semana($parametros){
	global $_SYSTEM;

	$cal_id = $parametros['calendario_id'];
	$ano = $parametros['ano'];
	$mes = $parametros['mes'];

	$mes_valor = $_SESSION[$_SYSTEM['ID'].$cal_id.'mes_valor'];
	$data_base_ano = $_SESSION[$_SYSTEM['ID'].$cal_id.'data_base_ano'];
	$data_base_dia_semana = $_SESSION[$_SYSTEM['ID'].$cal_id.'data_base_dia_semana'];

	$anos_passados_relativo_base = $ano - $data_base_ano;
	$ano_bisexto = floor(($anos_passados_relativo_base)/4);
	$numero_dia_semana = $data_base_dia_semana + $anos_passados_relativo_base + $ano_bisexto;
	$numero_dia_semana = $numero_dia_semana % 7;

	if(interface_verificar_ano_bisexto($ano))
		$mes_valor[2] = 29;

	if($mes != 1){
		for($i=1;$i<$mes;$i++)
			$numero_dia_semana += $mes_valor[$i];
		$numero_dia_semana = $numero_dia_semana % 7;
	}

	if($numero_dia_semana == 0)
		return 7;
	else
		return $numero_dia_semana;
}

function interface_select_mes($parametros){
	global $_SYSTEM;
	global $_URL;

	$opcao = $parametros['opcao'];

	$nome = 'mes';
	$id = 'mes';

	$options[] = "Mês";
	$optionsValue[] = "0";

	foreach($parametros['mes_nome_min'] as $mes_nome_min){
		$cont++;

		$options[] = $mes_nome_min;
		$optionsValue[] = $cont;

		if($parametros['mes'] == $cont){
			$optionSelected = $cont;
		}
	}

	if(!$optionSelected && $max == 1)
		$optionSelected = 1;

	$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,'onchange=window.open("'.$_URL.'?'.$link1.'opcao='.$opcao.'&'.$id.'="+this.value,"_self")');

	return $select;
}

function interface_select_ano($parametros){
	global $_SYSTEM;
	global $_URL;

	$opcao = $parametros['opcao'];
	$cal_id = $parametros['calendario_id'];

	$ano_inicio = $_SESSION[$_SYSTEM['ID'].$cal_id.'ano_inicio'];
	$ano_fim = $_SESSION[$_SYSTEM['ID'].$cal_id.'ano_fim'];

	$nome = 'ano';
	$id = 'ano';

	$options[] = "Ano";
	$optionsValue[] = "0";

	for($i=$ano_inicio;$i<=$ano_fim;$i++){
		$options[] = $i;
		$optionsValue[] = $i;

		$count++;
		if($parametros['ano'] == $i){
			$optionSelected = $count;
		}
	}

	$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,'onchange=window.open("'.$_URL.'?'.$link1.'opcao='.$opcao.'&'.$id.'="+this.value,"_self")');

	return $select;
}

function interface_calendario_configuracoes($parametros){
	/*
	Array(
		'calendario_id' => $calendario_id, // Identificador do calendario
		'calendario_width' => $calendario_width, // tamanho da tabela do calendario
		'anos_a_frente' => $reiniciar, // Reiniciar valores padrões
		'anos_passados' => $reiniciar, // Reiniciar valores padrões
		'reiniciar' => $reiniciar, // Reiniciar valores padrões

	);
	*/

	global $_SYSTEM;

	$cal_id = $parametros['calendario_id'];

	if($parametros['reiniciar']){
		$mes_valor[1]					=	31;
		$mes_valor[2]					=	28;
		$mes_valor[3]					=	31;
		$mes_valor[4]					=	30;
		$mes_valor[5]					=	31;
		$mes_valor[6]					=	30;
		$mes_valor[7]					=	31;
		$mes_valor[8]					=	31;
		$mes_valor[9]					=	30;
		$mes_valor[10]					=	31;
		$mes_valor[11]					=	30;
		$mes_valor[12]					=	31;
		$ano_padrao = date("Y");

		$ano_inicio = $ano_padrao - $parametros['anos_passados'];
		$ano_fim = $ano_padrao + $parametros['anos_a_frente'];

		$_SESSION[$_SYSTEM['ID'].$cal_id.'calendario'] = true;
		$_SESSION[$_SYSTEM['ID'].$cal_id.'data_base_ano'] = 2001;
		$_SESSION[$_SYSTEM['ID'].$cal_id.'data_base_dia_semana'] = 1;
		$_SESSION[$_SYSTEM['ID'].$cal_id.'ano_inicio'] = $ano_inicio;
		$_SESSION[$_SYSTEM['ID'].$cal_id.'ano_fim'] = $ano_fim;
		$_SESSION[$_SYSTEM['ID'].$cal_id.'mes_valor'] = $mes_valor;
	}

	return $parametros;
}

function interface_calendario_navegador($parametros){
	/*
	Array(
		'modelo' => $modelo, // Opção para alteração do layout
		'navegador' => Array( // array com todos os campos das opções do menu
			'navegador_mes' => $valor, // Mês do navegador
			'navegador_ano' => $valor, // Ano do navegador
			'title' => $valor, // título da opção
			'anterior_img' => $valor, // caminho da imagem anterior
			'anterior_url' => $valor, // Url do botão anterior
			'anterior_tit' => $valor, // Título do botão anterior
			'anterior_link_extra' => $valor, // Extra no link do botão anterior
			'anterior_img_extra' => $valor, // Extra na imagem do botão anterior
			'proximo_img' => $valor, // caminho da imagem próximo
			'proximo_url' => $valor, // Url do botão próximo
			'proximo_tit' => $valor, // Título do botão próximo
			'proximo_link_extra' => $valor, // Extra no link do botão próximo
			'proximo_img_extra' => $valor, // Extra na imagem do botão próximo
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
		),

	);
	*/

	$navegador = $parametros['navegador'];
	$modelo = $parametros['modelo'];

	if($navegador['navegador_mes']){
		$navegador['navegador_mes'] = interface_select_mes($parametros);
	} else {
		foreach($parametros['mes_nome_min'] as $mes_nome_min){
			$count++;
			if($count == $parametros['mes']){
				$navegador['navegador_mes'] = $mes_nome_min;
				break;
			}
		}
	}

	if($navegador['navegador_ano']){
		$navegador['navegador_ano'] = interface_select_ano($parametros);
	} else {
		$navegador['navegador_ano'] = $parametros['ano'];
	}

	if($navegador['width'])		$navegador_tabela_width = $navegador['width']; else $navegador_tabela_width = '100%';
	$modelo = modelo_var_troca($modelo,"#navegador_tabela_width",$navegador_tabela_width);

	if($navegador['align'])		$modelo = modelo_var_in($modelo,"#navegador_extra",htmlParam('align',$navegador['align']));
	if($navegador['valign'])	$modelo = modelo_var_in($modelo,"#navegador_extra",htmlParam('valign',$navegador['valign']));
	if($navegador['width'])		$modelo = modelo_var_in($modelo,"#navegador_extra",htmlParam('width',$navegador['width']));
	if($navegador['height'])	$modelo = modelo_var_in($modelo,"#navegador_extra",htmlParam('height',$navegador['height']));
	$modelo = modelo_var_troca($modelo,"#navegador_extra",'');

	$modelo = modelo_var_troca($modelo,"#anterior_img",$navegador['anterior_img']);
	$modelo = modelo_var_troca($modelo,"#anterior_url",$navegador['anterior_url'] . '&mes=' . ($parametros['mes'] > 1 ? ($parametros['mes'] - 1) : '12&ano=' . ($parametros['ano'] - 1) ));
	$modelo = modelo_var_troca($modelo,"#anterior_title",$navegador['anterior_tit']);
	$modelo = modelo_var_troca($modelo,"#anterior_alt",$navegador['anterior_tit']);
	$modelo = modelo_var_troca($modelo,"#anterior_link_extra",$navegador['anterior_link_extra']);
	$modelo = modelo_var_troca($modelo,"#anterior_img_extra",$navegador['anterior_img_extra']);

	$modelo = modelo_var_troca($modelo,"#navegador_mes",$navegador['navegador_mes']);
	$modelo = modelo_var_troca($modelo,"#navegador_ano",$navegador['navegador_ano']);

	$modelo = modelo_var_troca($modelo,"#proximo_img",$navegador['proximo_img']);
	$modelo = modelo_var_troca($modelo,"#proximo_url",$navegador['proximo_url'] . '&mes=' . ($parametros['mes'] < 12 ? ($parametros['mes'] + 1) : '1&ano=' . ($parametros['ano'] + 1) ));
	$modelo = modelo_var_troca($modelo,"#proximo_title",$navegador['proximo_tit']);
	$modelo = modelo_var_troca($modelo,"#proximo_alt",$navegador['proximo_tit']);
	$modelo = modelo_var_troca($modelo,"#proximo_link_extra",$navegador['proximo_link_extra']);
	$modelo = modelo_var_troca($modelo,"#proximo_img_extra",$navegador['proximo_img_extra']);

	return $modelo;
}

function interface_calendario_header($parametros){
	/*
	Array(
		'modelo' => $modelo, // Opção para alteração do layout
		'dia_nomes_min' => $dia_nomes_min, // Boolean - se deve ou não aparecer dia da semana mínimo
		'header' => Array( // array com todos os campos das opções do menu
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
		),

	);
	*/

	$header = $parametros['header'];
	$modelo = $parametros['modelo'];

	foreach($parametros['dia_nomes_min'] as $dia_nomes){
		$count++;
		if($header['align'])		$modelo = modelo_var_in($modelo,"#header_".$count."_extra",htmlParam('align',$header['align']));
		if($header['valign'])		$modelo = modelo_var_in($modelo,"#header_".$count."_extra",htmlParam('valign',$header['valign']));
		if($header['width'])		$modelo = modelo_var_in($modelo,"#header_".$count."_extra",htmlParam('width',$header['width']));
		if($header['height'])		$modelo = modelo_var_in($modelo,"#header_".$count."_extra",htmlParam('height',$header['height']));
		$modelo = modelo_var_troca($modelo,"#header_".$count."_extra",'');

		$modelo = modelo_var_troca($modelo,"#header_".$count."_valor",$dia_nomes);
	}

	return $modelo;
}

function interface_calendario_campos($parametros){
	/*
	Array(
		'modelo' => $modelo, // Opção para alteração do layout
		'dia_nomes_min' => $dia_nomes_min, // Boolean - se deve ou não aparecer dia da semana mínimo
		'campos' => Array( // array com todos os campos das opções do menu
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
		),

	);
	*/

	global $_SYSTEM;

	$parametros['layout_url'] = $_SYSTEM['PATH'] . 'includes' . $_SYSTEM['SEPARADOR'] . 'interface.html';
	$parametros['layout_tag1'] = '<!-- eventos_calendario < -->';
	$parametros['layout_tag2'] = '<!-- eventos_calendario > -->';

	$modelo_eventos = modelo_abrir($parametros['layout_url']);
	$modelo_eventos = modelo_tag_val($modelo_eventos,$parametros['layout_tag1'],$parametros['layout_tag2']);

	$lista = modelo_tag_val($modelo_eventos,'<!-- lista < -->','<!-- lista > -->');
	$modelo_eventos = modelo_tag_in($modelo_eventos,'<!-- lista < -->','<!-- lista > -->','<!-- lista -->');
	$mais = modelo_tag_val($modelo_eventos,'<!-- mais < -->','<!-- mais > -->');
	$modelo_eventos = modelo_tag_in($modelo_eventos,'<!-- mais < -->','<!-- mais > -->','<!-- mais -->');

	$mes = $parametros['mes'];
	$ano = $parametros['ano'];
	$campos = $parametros['campos'];
	$modelo = $parametros['modelo'];
	$cal_id = $parametros['calendario_id'];

	$cel1 = modelo_tag_val($modelo,'<!-- cel1 < -->','<!-- cel1 > -->');
	$modelo = modelo_tag_in($modelo,'<!-- cel1 < -->','<!-- cel1 > -->','<!-- cel1 -->');

	$mes_valor = $_SESSION[$_SYSTEM['ID'].$cal_id.'mes_valor'];

	if(interface_verificar_ano_bisexto($ano))
		$mes_valor[2] = 29;

	$dia_semana = interface_dia_semana($parametros);

	if($parametros['eventos'])
	foreach($parametros['eventos'] as $evento){
		$data1 = interface_data_quebrada($evento['data_inicio']);
		$data2 = interface_data_quebrada($evento['data_fim']);

		if(
			$data1['dia'] == $data2['dia'] &&
			$data1['mes'] == $data2['mes'] &&
			$data1['ano'] == $data2['ano'] &&
			$data1['mes'] == $mes &&
			$data1['ano'] == $ano
		){
			$eventos[$data1['dia']][] = Array(
				'cor' => $evento['caixa_cor'],
				'titulo' => $evento['caixa_tit'],
				'conteudo' => $evento['conteudo'],
			);
		} else if(
			$data1['mes'] == $mes &&
			$data1['ano'] == $ano &&
			$data2['mes'] == $mes &&
			$data2['ano'] == $ano
		){
			for($i=$data1['dia'];$i<=$data2['dia'];$i++){
				$eventos[$i][] = Array(
					'cor' => $evento['caixa_cor'],
					'titulo' => $evento['caixa_tit'],
					'conteudo' => $evento['conteudo'],
				);
			}
		} else if(
			$data1['mes'] == $mes &&
			$data1['ano'] == $ano
		){
			for($i=$data1['dia'];$i<=$mes_valor[$mes];$i++){
				$eventos[$i][] = $eventos[$data1['dia']][] = Array(
					'cor' => $evento['caixa_cor'],
					'titulo' => $evento['caixa_tit'],
					'conteudo' => $evento['conteudo'],
				);
			}
		} else if(
			$data2['mes'] == $mes &&
			$data2['ano'] == $ano
		){
			for($i=1;$i<=$data2['dia'];$i++){
				$eventos[$i][] = $eventos[$data1['dia']][] = Array(
					'cor' => $evento['caixa_cor'],
					'titulo' => $evento['caixa_tit'],
					'conteudo' => $evento['conteudo'],
				);
			}
		}
	}

	for($i=0;$i<42;$i++){
		if($i % 7 == 0){
			$cel_aux = $cel1;
			$count = 0;
		}

		$dia_valor = '';

		if($i >= $dia_semana && $count_dias < $mes_valor[$mes]){
			$count_dias++;
			$dia_numero = $count_dias;
			$dia_class = ' mes_class1 link_hover';
			$dia_extra = ' id="dia_'.$dia_numero.'"';

			if($eventos[$count_dias]){
				$count_eventos = 0;
				$cel_eventos = $modelo_eventos;

				foreach($eventos[$count_dias] as $evento){
					$count_eventos++;

					if($count_eventos > 3){
						$cel_eventos = modelo_var_in($cel_eventos,'<!-- mais -->',$mais);
						break;
					}

					$cel_lista = $lista;

					$cel_lista = modelo_var_troca($cel_lista,"#evento_cor",$evento['cor']);
					$cel_lista = modelo_var_troca($cel_lista,"#evento_titulo",$evento['titulo']);
					$cel_lista = modelo_var_troca($cel_lista,"#evento_texto",$evento['conteudo']);

					$cel_eventos = modelo_var_in($cel_eventos,'<!-- lista -->',$cel_lista);
				}

				$dia_valor = $cel_eventos;
			}
		} else if($i < $dia_semana){
			if($mes > 1){
				$dia_numero = $mes_valor[$mes-1] - $dia_semana + ($i+1);
			} else {
				$dia_numero = $mes_valor[12] - $dia_semana + ($i+1);
			}
			$dia_class = ' mes_class2';
		} else {
			$count_dias2++;
			$dia_numero = $count_dias2;
			$dia_class = ' mes_class2';
		}

		$count++;
		if($campos['align'])		$cel_aux = modelo_var_in($cel_aux,"#campo_".$count."_extra",htmlParam('align',$campos['align']));
		if($campos['valign'])		$cel_aux = modelo_var_in($cel_aux,"#campo_".$count."_extra",htmlParam('valign',$campos['valign']));
		if($campos['width'])		$cel_aux = modelo_var_in($cel_aux,"#campo_".$count."_extra",htmlParam('width',$campos['width']));
		if($campos['height'])		$cel_aux = modelo_var_in($cel_aux,"#campo_".$count."_extra",htmlParam('height',$campos['height']));
		$cel_aux = modelo_var_troca($cel_aux,"#campo_".$count."_extra",$dia_extra);

		$cel_aux = modelo_var_troca($cel_aux,"#campo_".$count."_valor",$dia_valor);
		$cel_aux = modelo_var_troca($cel_aux,"#campo_".$count."_dia",$dia_numero);
		$cel_aux = modelo_var_troca($cel_aux,"#campo_".$count."_class",$dia_class);

		if($i > 0 && $i % 7 == 6)
			$modelo = modelo_var_in($modelo,'<!-- cel1 -->',$cel_aux);
	}

	return $modelo;
}

function interface_calendario_layout($parametros){
	/*
	Array(
		'opcao' => $opcao, // Opção para alteração do layout
		'modelo' => $modelo, // Modelo do layout
		'dia_nomes_min' => $dia_nomes_min, // Boolean - se deve ou não aparecer dia da semana mínimo
		'mes_nome_min' => $mes_nome_min, // Boolean - se deve ou não aparecer o mês mínimo
		'calendario_id' => $calendario_id, // Identificador do calendario
		'calendario_width' => $calendario_width, // tamanho da tabela do calendario
		'anos_a_frente' => $reiniciar, // Reiniciar valores padrões
		'anos_passados' => $reiniciar, // Reiniciar valores padrões
		'reiniciar' => $reiniciar, // Reiniciar valores padrões
		'mes' => $mes, // Número do mês atual
		'ano' => $mes, // Número do Ano atual
		'navegador' => Array( // array com todos os campos das opções do menu
			'navegador_mes' => $valor, // Mês do navegador
			'navegador_ano' => $valor, // Ano do navegador
			'title' => $valor, // título da opção
			'anterior_img' => $valor, // caminho da imagem anterior
			'anterior_url' => $valor, // Url do botão anterior
			'anterior_tit' => $valor, // Título do botão anterior
			'anterior_link_extra' => $valor, // Extra no link do botão anterior
			'anterior_img_extra' => $valor, // Extra na imagem do botão anterior
			'proximo_img' => $valor, // caminho da imagem próximo
			'proximo_url' => $valor, // Url do botão próximo
			'proximo_tit' => $valor, // Título do botão próximo
			'proximo_link_extra' => $valor, // Extra no link do botão próximo
			'proximo_img_extra' => $valor, // Extra na imagem do botão próximo
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
		),
		'header' => Array( // array com todos os campos das opções do menu
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
		),
		'campos' => Array( // array com todos os campos das opções do menu
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
		),
		'eventos' => Array( // array com todos os eventos
			'caixa_tit' => $valor, // Título da caixa
			'caixa_cor' => $valor, // Cor da caixa
			'data_inicio' => $valor, // Data de início
			'data_fim' => $valor, // Data de término
			'conteudo' => $valor, // Conteúdo do evento
		),

	);
	*/

	global $_SYSTEM;

	$dayNames = array('Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado');
	$dayNamesMin = array('Do', 'Se', 'Te', 'Qa', 'Qi', 'Se', 'Sa');
	$monthNamesShort = array('Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez');
	$monthNames = array('Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro');

	$cal_id = $parametros['calendario_id'];

	if(!$_SESSION[$_SYSTEM['ID'].$cal_id.'mes'])	$_SESSION[$_SYSTEM['ID'].$cal_id.'mes'] = $parametros['mes'];
	if(!$_SESSION[$_SYSTEM['ID'].$cal_id.'ano'])	$_SESSION[$_SYSTEM['ID'].$cal_id.'ano'] = $parametros['ano'];

	if($_REQUEST['mes'])	$_SESSION[$_SYSTEM['ID'].$cal_id.'mes'] = $_REQUEST['mes'];
	if($_REQUEST['ano'])	$_SESSION[$_SYSTEM['ID'].$cal_id.'ano'] = $_REQUEST['ano'];

	if(!$parametros['reiniciar']){
		$parametros['mes'] = $_SESSION[$_SYSTEM['ID'].$cal_id.'mes'];
		$parametros['ano'] = $_SESSION[$_SYSTEM['ID'].$cal_id.'ano'];
	}

	$parametros['layout_url'] = $_SYSTEM['PATH'] . 'includes' . $_SYSTEM['SEPARADOR'] . 'interface.html';
	$parametros['layout_tag1'] = '<!-- calendario < -->';
	$parametros['layout_tag2'] = '<!-- calendario > -->';

	$modelo = modelo_abrir($parametros['layout_url']);
	$modelo = modelo_tag_val($modelo,$parametros['layout_tag1'],$parametros['layout_tag2']);

	if($navegador['calendario_width'])		$calendario_width = $navegador['calendario_width']; else $calendario_width = '100%';
	$modelo = modelo_var_troca($modelo,"#calendario_width",$calendario_width);

	if($parametros['dia_nomes_min'])	$parametros['dia_nomes_min'] = $dayNamesMin; else $parametros['dia_nomes_min'] = $dayNames;
	if($parametros['mes_nome_min'])		$parametros['mes_nome_min'] = $monthNamesShort; else $parametros['mes_nome_min'] = $monthNames;

	if(!$_SESSION[$_SYSTEM['ID'].$cal_id.'calendario'])
		$parametros['reiniciar'] = true;
	$parametros = interface_calendario_configuracoes($parametros);

	$parametros['modelo'] = $modelo;
	$parametros['modelo'] = interface_calendario_campos($parametros);
	$parametros['modelo'] = interface_calendario_header($parametros);
	$parametros['modelo'] = interface_calendario_navegador($parametros);

	return $parametros['modelo'];
}

function interface_calendario($parametros){
	/*
	Array(
		'opcao' => $opcao, // Opção para alteração do layout
		'modelo' => $modelo, // Modelo do layout
		'dia_nomes_min' => $dia_nomes_min, // Boolean - se deve ou não aparecer dia da semana mínimo
		'mes_nome_min' => $mes_nome_min, // Boolean - se deve ou não aparecer o mês mínimo
		'calendario_id' => $calendario_id, // Identificador do calendario
		'calendario_width' => $calendario_width, // tamanho da tabela do calendario
		'anos_a_frente' => $reiniciar, // Reiniciar valores padrões
		'anos_passados' => $reiniciar, // Reiniciar valores padrões
		'reiniciar' => $reiniciar, // Reiniciar valores padrões
		'mes' => $mes, // Número do mês atual
		'ano' => $mes, // Número do Ano atual
		'navegador' => Array( // array com todos os campos das opções do menu
			'navegador_mes' => $valor, // Mês do navegador
			'navegador_ano' => $valor, // Ano do navegador
			'title' => $valor, // título da opção
			'anterior_img' => $valor, // caminho da imagem anterior
			'anterior_url' => $valor, // Url do botão anterior
			'anterior_tit' => $valor, // Título do botão anterior
			'anterior_link_extra' => $valor, // Extra no link do botão anterior
			'anterior_img_extra' => $valor, // Extra na imagem do botão anterior
			'proximo_img' => $valor, // caminho da imagem próximo
			'proximo_url' => $valor, // Url do botão próximo
			'proximo_tit' => $valor, // Título do botão próximo
			'proximo_link_extra' => $valor, // Extra no link do botão próximo
			'proximo_img_extra' => $valor, // Extra na imagem do botão próximo
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
		),
		'header' => Array( // array com todos os campos das opções do menu
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
		),
		'campos' => Array( // array com todos os campos das opções do menu
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => $valor, // OPCIONAL - tamanho x da imagem
			'height' => $valor, // OPCIONAL - y da imagem
		),

	);
	*/

	global $_SYSTEM;

	$parametros['layout_url'] = $_SYSTEM['PATH'] . 'includes' . $_SYSTEM['SEPARADOR'] . 'interface.html';
	$parametros['layout_tag1'] = '<!-- layout < -->';
	$parametros['layout_tag2'] = '<!-- layout > -->';

	$modelo = modelo_abrir($parametros['layout_url']);
	$modelo = modelo_tag_val($modelo,$parametros['layout_tag1'],$parametros['layout_tag2']);

	if($parametros['busca'])				$busca = interface_busca($parametros);
	if($parametros['menu_principal'])		$menu_principal = interface_menu_principal($parametros);

	$modelo = modelo_var_troca($modelo,"#informacao_titulo",$parametros['informacao_titulo']);
	$modelo = modelo_var_troca($modelo,"#menu_principal",$menu_principal);
	$modelo = modelo_var_troca($modelo,"#busca",$busca);

	$modelo = modelo_var_troca($modelo,"#informacao",interface_calendario_layout($parametros));

	$modelo = modelo_var_troca($modelo,"#menu_paginas_1",$menu_paginas_1);
	$modelo = modelo_var_troca($modelo,"#menu_paginas_2",$menu_paginas_2);

	return $modelo;
}

?>