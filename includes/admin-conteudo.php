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

$_VERSAO_MODULO_INCLUDE				=	'1.0.0';

function admin_modificar_caminho_raiz_filhos_2($params){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$conteudo = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_conteudo',
			'caminho_raiz',
			'tipo',
		))
		,
		"conteudo",
		"WHERE id_conteudo_pai='".$id."'"
		." AND status!='D'"
	);
	
	if($conteudo)
	foreach($conteudo as $con){
		$tipo = $con['tipo'];
		$caminho_raiz = $con['caminho_raiz'];
		$id_conteudo = $con['id_conteudo'];
		
		$caminho_raiz = preg_replace('/'.$identificador.'/i', $identificador_novo, $caminho_raiz);
		
		banco_update
		(
			"caminho_raiz='".$caminho_raiz."'",
			"conteudo",
			"WHERE id_conteudo='".$id_conteudo."'"
		);
		
		if($tipo == 'L'){
			admin_modificar_caminho_raiz_filhos_2(Array(
				'id' => $id_conteudo,
				'identificador' => $identificador,
				'identificador_novo' => $identificador_novo,
			));
		}
	}
}

function admin_identificador_unico($id,$num,$id_conteudo,$id_auxiliar){
	global $_PALAVRAS_RESERVADAS;
	
	$conteudo = banco_select
	(
		"id_conteudo"
		,
		"conteudo",
		"WHERE ".($id_auxiliar?"identificador_auxiliar":"identificador")."='".($num ? $id.'-'.$num : $id)."'"
		.($id_conteudo?" AND id_conteudo!='".$id_conteudo."'":"")
		." AND status!='D'"
	);
	
	if($conteudo){
		return admin_identificador_unico($id,$num + 1,$id_conteudo,$id_auxiliar);
	} else {
		if($_PALAVRAS_RESERVADAS)
		foreach($_PALAVRAS_RESERVADAS as $palavra){
			if($palavra == $id){
				$num++;
				break;
			}
		}
		
		return ($num ? $id.'-'.$num : $id);
	}
}

function admin_criar_identificador($id,$id_conteudo = false,$id_auxiliar = false){
	$tam_max_id = 90;
	$id = retirar_acentos(trim($id));
	
	$pre_id_aux = explode('-',$id);
	
	if($pre_id_aux)
	foreach($pre_id_aux as $pre){
		$count++;
		if($pre){
			$pre_id .= $pre;
			
			if(strlen($pre_id) > $tam_max_id){
				break;
			} else {
				$pre_id .= (count($pre_id_aux) > $count ? '-' : '');
			}
		}
	}
	
	$id = $pre_id;
	
	$id_aux = explode('-',$id);
	$count = 0;
	if(count($id_aux) > 1 && is_numeric($id_aux[count($id_aux)-1])){
		$id = false;
		foreach($id_aux as $id2){
			if($count < count($id_aux)-1){
				$id .= ($id ? '-'.$id2 : $id2);
			} else {
				$num = (int)$id2;
			}
			$count++;
		}
		
		return admin_identificador_unico($id,$num,$id_conteudo,$id_auxiliar);
	} else {
		return admin_identificador_unico($id,0,$id_conteudo,$id_auxiliar);
	}
}

function admin_permisao_modelo($id,$pai = false,$nivel = 1){
	global $_ADMIN_CONTEUDO_CAMPOS;
	global $_ADMIN_CONTEUDO_CAMPOS_EXTRA;
	global $_ADMIN_CONTEUDO_CAMPOS_EXTRA_TEXTO;
	
	$campos_todos = array_merge($_ADMIN_CONTEUDO_CAMPOS,$_ADMIN_CONTEUDO_CAMPOS_EXTRA,$_ADMIN_CONTEUDO_CAMPOS_EXTRA_TEXTO);
	
	if($pai){
		if($nivel < 10){
			$permisao = banco_select_name
			(
				banco_campos_virgulas($campos_todos)
				,
				"conteudo_permissao",
				"WHERE id_conteudo='".$id."'".
				" AND tipo='".($nivel)."'"
			);
		} else {
			return false;
		}
	}
	
	if($permisao){
		return $permisao[0];
	} else {
		$conteudo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo_pai',
			))
			,
			"conteudo",
			"WHERE id_conteudo='".$id."'"
		);
		
		if($conteudo)
			return admin_permisao_modelo($conteudo[0]['id_conteudo_pai'],true,$nivel+1);
		else
			return false;
	}
}

function admin_permisao_modelo_acima($id,$tipo,$pai = false,$nivel = 0){
	global $_ADMIN_CONTEUDO_CAMPOS;
	global $_ADMIN_CONTEUDO_CAMPOS_EXTRA;
	global $_ADMIN_CONTEUDO_CAMPOS_EXTRA_TEXTO;
	
	$campos_todos = array_merge($_ADMIN_CONTEUDO_CAMPOS,$_ADMIN_CONTEUDO_CAMPOS_EXTRA,$_ADMIN_CONTEUDO_CAMPOS_EXTRA_TEXTO);
	
	if($pai){
		if($nivel < 10){
			$permisao = banco_select_name
			(
				banco_campos_virgulas($campos_todos)
				,
				"conteudo_permissao",
				"WHERE id_conteudo='".$id."'".
				" AND tipo='".($nivel+(int)$tipo)."'"
			);
		} else {
			return false;
		}
	}
	
	if($permisao){
		return $permisao[0];
	} else {
		$conteudo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo_pai',
			))
			,
			"conteudo",
			"WHERE id_conteudo='".$id."'"
		);
		
		if($conteudo)
			return admin_permisao_modelo_acima($conteudo[0]['id_conteudo_pai'],$tipo,true,$nivel+1);
		else
			return false;
	}
}

function admin_permisao_pai($id,$pai = false,$nivel = 0){
	global $_ADMIN_CONTEUDO_CAMPOS;
	global $_ADMIN_CONTEUDO_CAMPOS_EXTRA;
	global $_ADMIN_CONTEUDO_CAMPOS_EXTRA_TEXTO;
	
	$campos_todos = array_merge($_ADMIN_CONTEUDO_CAMPOS,$_ADMIN_CONTEUDO_CAMPOS_EXTRA,$_ADMIN_CONTEUDO_CAMPOS_EXTRA_TEXTO);
	
	if($pai){
		$permisao = banco_select_name
		(
			banco_campos_virgulas($campos_todos)
			,
			"conteudo_permissao",
			"WHERE id_conteudo='".$id."'".
			" AND tipo='".$nivel."'"
		);
		
		if(!$permisao){
			$permisao = banco_select_name
			(
				banco_campos_virgulas($campos_todos)
				,
				"conteudo_permissao",
				"WHERE id_conteudo='".$id."'".
				" AND tipo='L'"
			);
		}
	}
	
	if($permisao){
		return $permisao[0];
	} else {
		$conteudo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo_pai',
			))
			,
			"conteudo",
			"WHERE id_conteudo='".$id."'"
		);
		
		if($conteudo)
			return admin_permisao_pai($conteudo[0]['id_conteudo_pai'],true,$nivel + 1);
		else
			return Array();
	}
}

function admin_permisao_nivel_ids($params){
	global $_CONEXAO_BANCO;
	
	$id = $params['id'];
	$nivel = $params['nivel'];
	$nivel_atual = $params['nivel_atual'];
	
	if(!$nivel_atual)$nivel_atual = 1;
	
	if(!$_CONEXAO_BANCO)banco_conectar();
	
	if($nivel_atual == $nivel){
		$conteudo_permissao = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo_permissao',
				'id_conteudo',
			))
			,
			"conteudo_permissao",
			"WHERE id_conteudo='".$id."'"
			." AND tipo='L'"
		);
		
		if($conteudo_permissao)
		foreach($conteudo_permissao as $cont_perm){
			$retorno[] = Array(
				'id_conteudo_permissao' => $cont_perm['id_conteudo_permissao'],
				'id_conteudo' => $cont_perm['id_conteudo'],
				'sitemap' => true,
			);
		}
	} else {
		$conteudo = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_conteudo',
			))
			,
			"conteudo",
			"WHERE id_conteudo_pai='".$id."'"
			." AND tipo='L'"
			." AND status!='D'"
		);
		
		if($conteudo)
		foreach($conteudo as $cont){
			$conteudo_permissao = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_conteudo_permissao',
				))
				,
				"conteudo_permissao",
				"WHERE id_conteudo='".$cont['id_conteudo']."'"
				." AND tipo='".($nivel - $nivel_atual)."'"
			);
			
			if($conteudo_permissao){
				$retorno[] = Array(
					'id_conteudo_permissao' => $conteudo_permissao[0]['id_conteudo_permissao'],
					'id_conteudo' => $cont['id_conteudo'],
					'sitemap' => false,
				);
			}
			
			$retorno_filhos = admin_permisao_nivel_ids(Array(
				'id' => $cont['id_conteudo'],
				'nivel' => $nivel,
				'nivel_atual' => $nivel_atual+1,
			));
			
			if($retorno_filhos){
				if($retorno){
					$retorno = array_merge($retorno,$retorno_filhos);
				} else {
					$retorno = $retorno_filhos;
				}
			}
		}
	}
	
	return $retorno;
}

function admin_permisao_modificar($params = false){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_CONEXAO_BANCO;
	global $_PERMISSAO_MASK;
	global $_ADMIN_CONTEUDO_CAMPOS;
	global $_ADMIN_CONTEUDO_CAMPOS_EXTRA;
	global $_ADMIN_CONTEUDO_CAMPOS_EXTRA_TEXTO;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$campos_padrao = $_ADMIN_CONTEUDO_CAMPOS;
	$campos_extra = $_ADMIN_CONTEUDO_CAMPOS_EXTRA;
	$campos_extra_texto = $_ADMIN_CONTEUDO_CAMPOS_EXTRA_TEXTO;
	
	if(!$id)
		$id = '0';
	if(!$tipo)
		$tipo = 'C';
	
	if(!$_CONEXAO_BANCO)banco_conectar();

	$campos_todos = array_merge($_ADMIN_CONTEUDO_CAMPOS,$campos_extra);
	
	$campos_texto = $campos_extra_texto;
	
	$campos_todos_banco = array_merge($campos_todos,$campos_texto);
	
	$tabela2 = banco_select_name
	(
		banco_campos_virgulas(
			$campos_todos_banco
		)
		,
		'conteudo_permissao',
		"WHERE id_conteudo='".$id."'".
		" AND tipo='".$tipo."'"
	);
	
	$campos_antes = Array(
		'permissao' => ($tabela2?true:false),
	);
	
	if(!$tabela2){
		if($tipo != 'L' && $tipo != 'C'){
			$tabela2[0] = admin_permisao_modelo_acima($id,$tipo);
		} else {
			$tabela2[0] = admin_permisao_pai($id);
		}
	}
	
	foreach($campos_todos_banco as $campo){
		$campos_antes[$campo] = $tabela2[0][$campo];
	}
	
	if(!$_CONEXAO_BANCO)banco_conectar();
	
	// ================================= Local de Edição ===============================
	// Altere os campos da tabela e POST aqui, e modifique o UPDATE
	
	if($campos_antes['permissao']){
		$campo_tabela = "tabela2";
		
		foreach($campos_padrao as $campo){
			$campo_nome = $campo; if($campos[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='1'";} else if($remover_campos[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='0'";}
		}
		
		foreach($campos_extra as $campo){
			if(
				$campo == 'titulo_img_recorte_y' ||
				$campo == 'imagem_pequena_recorte_y' ||
				$campo == 'imagem_grande_recorte_y'
			){
				$campo_nome = $campo; if($campos[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='".($campos[$campo_nome] == '2'? '1' : '0')."'";} else if($remover_campos[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='0'";}
			} else {
				$campo_nome = $campo; if($campos[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='1'";} else if($remover_campos[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='0'";}
			}
		}
		
		foreach($campos_extra_texto as $campo){
			$campo_nome = $campo; if($campos[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='".$campos[$campo_nome]."'";} else if($remover_campos[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='0'";}
		}
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				'conteudo_permissao',
				"WHERE id_conteudo='".$id."'".
				" AND tipo='".$tipo."'"
			);
		}
	} else {	
		$campo_nome = "id_conteudo"; 										$campos_bd[] = Array($campo_nome,$id);
		$campo_nome = "tipo"; 												$campos_bd[] = Array($campo_nome,$tipo);
		
		foreach($campos_padrao as $campo){
			$campo_nome = $campo; 	if($campos[$campo_nome]){		$campos_bd[] = Array($campo_nome,'1'); $permissao[$campo_nome] = true;		}
		}
		
		foreach($campos_extra as $campo){
			if(
				$campo == 'titulo_img_recorte_y' ||
				$campo == 'imagem_pequena_recorte_y' ||
				$campo == 'imagem_grande_recorte_y'
			){
				$campo_nome = $campo; 	if($campos[$campo_nome]){		$campos_bd[] = Array($campo_nome,($campos[$campo_nome] == '2'? '1' : '0')); $permissao[$campo_nome] = true;		}
			} else {
				$campo_nome = $campo; 	if($campos[$campo_nome]){		$campos_bd[] = Array($campo_nome,'1'); $permissao[$campo_nome] = true;		}
			}
		}
		
		foreach($campos_extra_texto as $campo){
			$campo_nome = $campo; 	if($campos[$campo_nome]){		$campos_bd[] = Array($campo_nome,$campos[$campo_nome]); $permissao[$campo_nome] = true;		}
		}
		
		banco_insert_name($campos_bd,'conteudo_permissao');
	}
	
	if($nivel || $tipo == 'L'){
		if(!$nivel){
			$nivel = 1;
		} else {
			$nivel = (int)$nivel;
		}
		
		$permissoes_listas = admin_permisao_nivel_ids(Array(
			'id' => $id,
			'nivel' => $nivel,
		));
		
		if($permissoes_listas){
			
			$campo_tabela = "tabela3";
			
			foreach($campos_padrao as $campo){
				$campo_nome = $campo; if($campos[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='1'";} else if($remover_campos[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='0'";}
			}
			
			$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
			
			if($editar_sql[$campo_tabela]){
				foreach($permissoes_listas as $permissao){
					banco_update
					(
						$editar_sql[$campo_tabela],
						'conteudo_permissao',
						"WHERE id_conteudo_permissao='".$permissao['id_conteudo_permissao']."'"
					);
				}
			}
		}
	}
}

function admin_conteudo_add($params = false){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_USUARIO_ATIVO_2;
	global $_LISTA;
	global $_INTERFACE;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_CONEXAO_BANCO;
	global $_PERMISSAO_CONTEUDO;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(!$_CONEXAO_BANCO)banco_conectar();
	
	if($campos['titulo']){
		$identificador = $campos['titulo'];
		$identificador = admin_criar_identificador($identificador);
		
		if($id_conteudo_pai){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'identificador',
					'caminho_raiz',
				))
				,
				"conteudo",
				"WHERE id_conteudo='".$id_conteudo_pai."'"
			);
			
			$caminho_raiz = $resultado[0]['caminho_raiz'].$resultado[0]['identificador'].'/';
		}
		
		if($identificador_auxiliar){
			$identificador_auxiliar = admin_criar_identificador($identificador_auxiliar,false,true);
		}
		
		$campo_nome = "id_conteudo_pai"; 								$campos_bd[] = Array($campo_nome,($id_conteudo_pai?$id_conteudo_pai:'0'));
		$campo_nome = "tipo"; $post_nome = $campo_nome; 				$campos_bd[] = Array($campo_nome,($tipo?$tipo:'C')); if($tipo == 'L') $permisao_modelo = true;
		$campo_nome = "identificador"; $post_nome = $campo_nome; 		$campos_bd[] = Array($campo_nome,$identificador);
		$campo_nome = "identificador_auxiliar"; $post_nome = $campo_nome; 		$campos_bd[] = Array($campo_nome,$identificador_auxiliar);
		
		$campo_nome = "titulo"; $post_nome = $campo_nome; 				if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "sub_titulo"; $post_nome = $campo_nome; 			if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "author"; $post_nome = $campo_nome; 			if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "keywords"; $post_nome = $campo_nome; 			if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "description"; $post_nome = $campo_nome; 			if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "redes_titulo"; $post_nome = $campo_nome; 			if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "redes_subtitulo"; $post_nome = $campo_nome; 			if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "texto"; $post_nome = $campo_nome; 				if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,html_entity_decode($campos[$post_nome],ENT_QUOTES,'ISO-8859-1'));
		$campo_nome = "texto2"; $post_nome = $campo_nome; 				if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,html_entity_decode($campos[$post_nome],ENT_QUOTES,'ISO-8859-1'));
		$campo_nome = "link_externo"; $post_nome = $campo_nome; 		if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "videos_youtube"; $post_nome = $campo_nome; 		if($campos[$post_nome]){		$campos_bd[] = Array($campo_nome,$campos[$post_nome]); $videos_youtube = $campos[$post_nome]; }
		$campo_nome = "conteiner_posicao_x"; $post_nome = $campo_nome; 				if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "conteiner_posicao_y"; $post_nome = $campo_nome; 				if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "servico"; $post_nome = $campo_nome; 				if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "produto"; $post_nome = $campo_nome; 				if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		
		if($campos["data"] && $campos["hora"]){
			$campo_nome = "data"; $data = data_padrao_date($campos["data"])." ".$campos["hora"].":00";
			$campos_bd[] = Array($campo_nome,$data);
		} else {
			$campo_nome = "data_automatica"; $campos_bd[] = Array($campo_nome,'1');
			$campo_nome = "data"; $post_nome = $campo_nome;				$campos_bd[] = Array($campo_nome,'NOW()',true);
		}
		
		$campo_nome = "titulo_img_name"; $post_nome = $campo_nome; 		if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "titulo_img_title"; $post_nome = $campo_nome; 		if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "titulo_img_alt"; $post_nome = $campo_nome; 		if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "imagem_pequena_name"; $post_nome = $campo_nome; 		if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "imagem_pequena_title"; $post_nome = $campo_nome; 		if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "imagem_pequena_alt"; $post_nome = $campo_nome; 		if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "imagem_grande_name"; $post_nome = $campo_nome; 		if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "imagem_grande_title"; $post_nome = $campo_nome; 		if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "imagem_grande_alt"; $post_nome = $campo_nome; 		if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		
		$campo_nome = "galeria"; $post_nome = "galeria_id"; 		if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "galeria_grupo"; $post_nome = "galeria_grupo_id"; 		if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "videos"; $post_nome = "videos_id"; 		if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		$campo_nome = "videos_grupo"; $post_nome = "videos_grupo_id"; 		if($campos[$post_nome])		$campos_bd[] = Array($campo_nome,$campos[$post_nome]);
		
		$campo_nome = "status"; $post_nome = $campo_nome; 				$campos_bd[] = Array($campo_nome,($campos[$post_nome]?$campos[$post_nome]:'A'));
		$campo_nome = "versao"; $post_nome = $campo_nome; 				$campos_bd[] = Array($campo_nome,'1');
		$campo_nome = "sitemap"; $post_nome = $campo_nome; 				$campos_bd[] = Array($campo_nome,'1');
		$campo_nome = "rss"; $post_nome = $campo_nome; 				$campos_bd[] = Array($campo_nome,'1');
		$campo_nome = "rss_redes"; $post_nome = $campo_nome; 				$campos_bd[] = Array($campo_nome,'1');
		$campo_nome = "caminho_raiz"; $post_nome = $campo_nome; 		$campos_bd[] = Array($campo_nome,$caminho_raiz);
		
		banco_insert_name($campos_bd,'conteudo');
		$id_tabela = banco_last_id();
		
		if($permisao_modelo){
			$permisao_modelo = admin_permisao_modelo($id_tabela);
			
			if($permisao_modelo){
				$campos_bd = false;
				
				$campo_nome = "id_conteudo"; 										$campos_bd[] = Array($campo_nome,$id_tabela);
				$campo_nome = "tipo"; 												$campos_bd[] = Array($campo_nome,$campos[$campo_nome]);
				
				foreach($permisao_modelo as $campo => $valor){
					if($valor)
					$campos_bd[] = Array($campo,$valor);
				}
				
				banco_insert_name($campos_bd,'conteudo_permissao');
			}
		}
		
		// ================================= Conjunto de Tags ===============================
		
		if($campos['tags-flag']){
			$resultado = banco_select_name
			(
				banco_campos_bd_virgulas(Array(
					'id_conteudo_tags',
				))
				,
				"conteudo_tags",
				""
			);
			
			if($resultado){
				foreach($resultado as $res){
					if($campos['tags'.$res['id_conteudo_tags']]){
						$array['id_conteudo_tags'][] = $res['id_conteudo_tags'];
						$array['id_conteudo'][] = $id_tabela;
					}
				}
				
				$dados[] = Array("id_conteudo_tags",$array['id_conteudo_tags']);
				$dados[] = Array("id_conteudo",$array['id_conteudo']);
				
				banco_insert_name_varios
				(
					$dados,
					"conteudo_conteudo_tags"
				);
			}
		}
		
		return $id_tabela;
	}
}

function admin_conteudo_editar($params = false){
	global $_BANCO_PREFIXO;
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_SYSTEM_PATH;
	global $_SYSTEM_SEPARADOR;
	global $_CAMINHO_RELATIVO_RAIZ;
	global $_CONEXAO_BANCO;
	global $_PERMISSAO_CONTEUDO;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($id_conteudo){
		$id = $id_conteudo;
		
		$campos_antes = campos_antes_recuperar();
		
		if(!$_CONEXAO_BANCO)banco_conectar();
		
		// ================================= Local de Edição ===============================
		// Altere os campos da tabela e POST aqui, e modifique o UPDATE
		
		$campo_tabela = "tabela";
		$campo_nome = "identificador_auxiliar"; if($campos[$campo_nome] || $remover_campos[$campo_nome]){$mudar_identificador2 = true;}
		
		$campo_nome = "tipo"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$campo_nome] . "'"; $tipo_alterar = $campos[$campo_nome];}
		$campo_nome = "titulo"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$campo_nome] . "'";$mudar_identificador = true;}
		$campo_nome = "sub_titulo"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$campo_nome] . "'";} else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "author"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$campo_nome] . "'";} else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "keywords"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$campo_nome] . "'";} else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "description"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$campo_nome] . "'";} else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "redes_titulo"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$campo_nome] . "'";} else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "redes_subtitulo"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$campo_nome] . "'";} else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "texto"; if($campos_antes[$campo_nome] != html_entity_decode($campos[$campo_nome],ENT_QUOTES,'ISO-8859-1')){$editar['tabela'][] = $campo_nome."='" . html_entity_decode($campos[$campo_nome],ENT_QUOTES,'ISO-8859-1') . "'";} else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "texto2"; if($campos_antes[$campo_nome] != html_entity_decode($campos[$campo_nome],ENT_QUOTES,'ISO-8859-1')){$editar['tabela'][] = $campo_nome."='" . html_entity_decode($campos[$campo_nome],ENT_QUOTES,'ISO-8859-1') . "'";} else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "link_externo"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$campo_nome] . "'";} else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "videos_youtube"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$campo_nome] . "'"; $videos_youtube = $campos[$campo_nome]; $videos_antes = $campos_antes[$campo_nome]; } else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "conteiner_posicao_x"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$campo_nome] . "'";} else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "conteiner_posicao_y"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$campo_nome] . "'";} else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "servico"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$campo_nome] . "'";} else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "produto"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$campo_nome] . "'";} else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "galeria"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$post_nome]."'";} else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "galeria_grupo"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$post_nome]."'";} else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "videos"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$post_nome]."'";} else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "videos_grupo"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$post_nome]."'";} else if($remover_campos[$campo_nome]){$editar['tabela'][] = $campo_nome."=NULL";}
		$campo_nome = "status"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='" . $campos[$campo_nome] . "'";}
		
		$campo_nome = "data_automatica"; if($campos[$campo_nome]){$editar['tabela'][] = $campo_nome."='1'";}
		$campo_nome = "data"; 		$editar['tabela'][] = $campo_nome."=NOW()";
		
		if($campos["data"] && $campos["hora"]){
			$data = data_padrao_date($campos["data"])." ".$campos["hora"].":00";
			$campo_nome = "data"; 		$editar['tabela'][] = $campo_nome."='".$data."'";
			$editar['tabela'][] = "data_automatica=NULL";
		}
		
		$campo_nome = "versao"; 	$editar['tabela'][] = $campo_nome."='".((int)$campos_antes[$campo_nome]+1)."'";
		$campo_nome = "sitemap"; 	$editar['tabela'][] = $campo_nome."='1'";
		$campo_nome = "rss"; 	$editar['tabela'][] = $campo_nome."='1'";
		$campo_nome = "rss_redes"; 	$editar['tabela'][] = $campo_nome."='1'";
		
		if($mudar_identificador){
			$identificador = $campos['titulo'];
			$identificador = admin_criar_identificador($identificador,$id);
			$campo_nome = "identificador"; 	$editar['tabela'][] = $campo_nome."='".$identificador."'";
			
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'tipo',
					'identificador',
				))
				,
				"conteudo",
				"WHERE id_conteudo='".$id."'"
			);
			
			if($resultado)
			if($tipo_alterar == 'L' || $resultado[0]['tipo'] == 'L'){
				admin_modificar_caminho_raiz_filhos_2(Array(
					'id' => $id,
					'identificador' => $resultado[0]['identificador'],
					'identificador_novo' => $identificador,
				));
			}
		}
		
		if($mudar_identificador2){
			if($campos['identificador_auxiliar']){
				$identificador_auxiliar = $campos['identificador_auxiliar'];
				$identificador_auxiliar = admin_criar_identificador($identificador_auxiliar,$id,true);
			}
			
			$campo_nome = "identificador_auxiliar"; 	$editar['tabela'][] = $campo_nome."='".$identificador_auxiliar."'";
		}
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				'conteudo',
				"WHERE id_conteudo='".$id."'"
			);
		}
		
		if($tipo_alterar){
			if($tipo_alterar == 'L'){
				$conteudo_permissao = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_conteudo_permissao',
					))
					,
					"conteudo_permissao",
					"WHERE id_conteudo='".$id."'".
					" AND tipo='L'"
				);
				
				if(!$conteudo_permissao){
					$permisao_modelo = admin_permisao_modelo($id);
					
					if($permisao_modelo){
						$campos_bd = false;
						
						$campo_nome = "id_conteudo"; 										$campos_bd[] = Array($campo_nome,$id);
						$campo_nome = "tipo"; 												$campos_bd[] = Array($campo_nome,$tipo_alterar);
					
						foreach($permisao_modelo as $campo => $valor){
							if($valor)
							$campos_bd[] = Array($campo,$valor);
						}
						
						banco_insert_name($campos_bd,'conteudo_permissao');
					}
				}
			} else {
				banco_delete
				(
					"conteudo_permissao",
					"WHERE id_conteudo='".$id."'".
					" AND tipo!='C'"
				);
			}
		}
		
		// ================================= Conjunto de Tags ===============================
		
		if($campos['tags-flag']){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_conteudo_tags',
				))
				,
				"conteudo_tags",
				""
			);
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_conteudo_tags',
				))
				,
				"conteudo_conteudo_tags",
				"WHERE id_conteudo='".$id."'"
			);
			
			if($resultado){
				foreach($resultado as $res){
					if($campos['tags'.$res['id_conteudo_tags']]){
						$found = false;
						if($resultado2)
						foreach($resultado2 as $res2){
							if($res['id_conteudo_tags'] == $res2['id_conteudo_tags']){
								$found = true;
								break;
							}
						}
						
						if(!$found){
							$array['id_conteudo_tags'][] = $res['id_conteudo_tags'];
							$array['id_conteudo'][] = $id;
						}
					} else {
						$found = false;
						if($resultado2)
						foreach($resultado2 as $res2){
							if($res['id_conteudo_tags'] == $res2['id_conteudo_tags']){
								$found = true;
								break;
							}
						}
						
						if($found){
							banco_delete
							(
								"conteudo_conteudo_tags",
								"WHERE id_conteudo='".$id."'"
								." AND id_conteudo_tags='".$res['id_conteudo_tags']."'"
							);
						}
					}
				}
				
				if($array['id_conteudo_tags']){
					$dados[] = Array("id_conteudo_tags",$array['id_conteudo_tags']);
					$dados[] = Array("id_conteudo",$array['id_conteudo']);
					
					banco_insert_name_varios
					(
						$dados,
						"conteudo_conteudo_tags"
					);
				}
			}
		}
	}
}

function admin_conteudo_excluir($params = false){
	global $_CONEXAO_BANCO;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($id){
		if(!$_CONEXAO_BANCO)banco_conectar();
		banco_update
		(
			"identificador=NULL,".
			"sitemap=1,".
			"status='D'",
			'conteudo',
			"WHERE id_conteudo='".$id."'"
		);
		admin_conteudo_excluir_filhos($id);
	}
}

function admin_conteudo_excluir_filhos($id){
	$conteudo = banco_select
	(
		"id_conteudo"
		,
		"conteudo",
		"WHERE id_conteudo_pai='".$id."'"
	);
	
	if($conteudo){
		foreach($conteudo as $con){
			banco_update
			(
				"identificador=NULL,".
				"sitemap=1,".
				"status='D'",
				"conteudo",
				"WHERE id_conteudo='".$con['id_conteudo']."'"
			);
			
			$filho = banco_select
			(
				"id_conteudo"
				,
				"conteudo",
				"WHERE id_conteudo_pai='".$con['id_conteudo']."'"
			);
			
			if($filho){
				admin_conteudo_excluir_filhos($con['id_conteudo']);
			}
		}
	}
}

function admin_conteudo_mudar_pai($params = false){
	global $_CONEXAO_BANCO;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($id_conteudo){
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		if($id_conteudo_pai_novo){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					'identificador',
					'caminho_raiz',
				))
				,
				"conteudo",
				"WHERE id_conteudo='".$id_conteudo_pai_novo."'"
			);
			
			$caminho_raiz = $resultado[0]['caminho_raiz'].$resultado[0]['identificador'].'/';
		} else {
			$id_conteudo_pai_novo = '0';
			$caminho_raiz = '';
		}
		
		$campo_tabela = "conteudo";
		$campo_tabela_extra = "WHERE id_conteudo='".$id_conteudo."'";
		
		$campo_nome = "id_conteudo_pai"; $campo_valor = $id_conteudo_pai_novo; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		$campo_nome = "caminho_raiz"; $campo_valor = $caminho_raiz; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$campo_tabela,
				$campo_tabela_extra
			);
		}
		
		admin_conteudo_mudar_raiz_filhos(Array(
			'id_conteudo_pai' => $id_conteudo,
		));
	}
}

function admin_conteudo_mudar_raiz_filhos($params = false){
	global $_CONEXAO_BANCO;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_conteudo',
		))
		,
		"conteudo",
		"WHERE id_conteudo_pai='".$id_conteudo_pai."'"
	);
	
	if($resultado){
		$conteudo_pai = banco_select_name
		(
			banco_campos_virgulas(Array(
				'identificador',
				'caminho_raiz',
			))
			,
			"conteudo",
			"WHERE id_conteudo='".$id_conteudo_pai."'"
		);
		
		foreach($resultado as $res){
			$campo_tabela = "conteudo";
			$campo_tabela_extra = "WHERE id_conteudo='".$res['id_conteudo']."'";
			
			$campo_nome = "caminho_raiz"; $campo_valor = $conteudo_pai[0]['caminho_raiz'].$conteudo_pai[0]['identificador'].'/'; $editar[$campo_tabela][] = $campo_nome."='" . $campo_valor . "'";
			
			$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
			
			if($editar_sql[$campo_tabela]){
				banco_update
				(
					$editar_sql[$campo_tabela],
					$campo_tabela,
					$campo_tabela_extra
				);
			}
			$editar = false;$editar_sql = false;
			
			admin_conteudo_mudar_raiz_filhos(Array(
				'id_conteudo_pai' => $res['id_conteudo'],
			));
		}
	}
}

?>