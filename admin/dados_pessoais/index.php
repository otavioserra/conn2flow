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

$_VERSAO_MODULO				=	'1.3.1';
$_LOCAL_ID					=	"dados_pessoais";
$_PERMISSAO					=	true;
$_INCLUDE_INTERFACE			=	true;
$_INCLUDE_MAILER			=	true;
$_CAMINHO_RELATIVO_RAIZ		=	"../../";
$_CAMINHO_MODULO_RAIZ		=	"../";
$_HTML['LAYOUT']			=	"../layout.html";

include($_CAMINHO_RELATIVO_RAIZ."config.php");

if(!$_SESSION[$_SYSTEM['ID']."permissao"])
	header("Location: ".$_CAMINHO_RELATIVO_RAIZ);

if(!$_SESSION[$_SYSTEM['ID']."admin"]){
	$permissao_modulos = $_SESSION[$_SYSTEM['ID']."modulos"];
	
	if(!$permissao_modulos[$_LOCAL_ID]){
		header("Location: ".$_CAMINHO_MODULO_RAIZ);
	}
}

$_HTML['titulo'] 			= 	$_HTML['titulo']."Dados Pessoais.";

$_HTML['js'] = 
$_JS['menu'].
$_JS['maskedInput'].
$_JS['jQueryPassStrengthMeter'].
"<script type=\"text/javascript\" src=\"js.js?v=".$_VERSAO_MODULO."\"></script>\n";

$_HTML['css'] .= "<link href=\"css.css?v=".$_VERSAO_MODULO."\" rel=\"stylesheet\" type=\"text/css\" />\n";

$_LISTA['tabela']['nome']		=	'usuario';
$_LISTA['tabela']['campo']		=	'usuario';
$_LISTA['tabela']['id']			=	'id_usuario';
$_LISTA['tabela']['status']		=	'status';
$_LISTA['ferramenta']			=	'Dados Pessoais';
$_LISTA['ferramenta_unidade']	=	'o usuário';

$_LISTA_2['tabela']['nome']			=	'pastas_usuarios';
$_LISTA_2['tabela']['campo']			=	'nome';
$_LISTA_2['tabela']['id']				=	'id_'.'pastas_usuarios';
$_LISTA_2['tabela']['status']			=	'status';

$_HTML['separador']		=	$_CAMINHO_RELATIVO_RAIZ;

// ======================================================================================

function perfil_select(){
	global $_SYSTEM;
	
	$nome = 'perfil';
	$id = $nome . '_id';
	
	$tabela = banco_select
	(
		"nome",
		"usuario_perfil",
		"WHERE id_usuario_perfil='".$_SESSION[$_SYSTEM['ID'].$id]."'"
	);
	
	return $tabela[0][0];
}

function parametros_interface(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_HTML;
	global $_LISTA;
	global $_CAMINHO_MODULO_RAIZ;
	global $_URL;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	//$tabela_order = $_LISTA['tabela']['id'].' DESC';
	$tabela_order = $_LISTA['tabela']['campo'].' ASC';
	
	$tabela_campos[] = $_LISTA['tabela']['id'];
	$tabela_campos[] = $_LISTA['tabela']['status'];
	$tabela_campos[] = $_LISTA['tabela']['campo'];
	
	$informacao_titulo = ($_INTERFACE_OPCAO == 'lista' ? 'Lista' : $_INTERFACE['informacao_titulo']);
	
	$menu_principal[] = Array( // array com todos os campos das opções do menu
		'url' => $_CAMINHO_MODULO_RAIZ,// link da opção
		'title' => 'Voltar ao início do sistema', // título da opção
		'img' => $_HTML['separador'].$_HTML['ICONS'] . 'home_mini.png', // caminho da imagem
		'name' => 'Início', // Nome do menu
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Id', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'campo' => 'id_usuario', // OPCIONAL - Nome do campo da tabela
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Status', // Valor do campo
		'oculto' => true, // OPCIONAL - Se o campo é oculto
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$header_campos[] = Array( // array com todos os campos do cabeçalho
		'campo' => 'Usuário', // Valor do campo
		'ordenar' => true, // Valor do campo
	);
	$campos[] = Array( // OPCIONAL - array com os dados dos campos
		'align' => $valor, // OPCIONAL - alinhamento horizontal
	);
	
	$parametros = Array(
		'opcao' => $_INTERFACE_OPCAO, // Opção para alteração do layout
		'inclusao' => $_INTERFACE['inclusao'], // Informação para incluir na interface
		'ferramenta' => $_LISTA['ferramenta'], // Texto da ferramenta
		'informacao_titulo' => $informacao_titulo . ' ' . $_LISTA['ferramenta'] , // Título da Informação
		'informacao_id' => $informacao_id , // Id da Informação
		'busca' => false, // Formulário de busca
		'busca_url' => $_URL, // Url da busca
		'busca_opcao' => 'busca_ver', // Opção da busca
		'legenda' => true, // Colocar o menu em cima
		'menu_pagina_acima' => true, // Colocar o menu em cima
		'menu_pagina_embaixo' => false, // Colocar o menu em baixo
		'menu_paginas_id' => "menu_".$_LOCAL_ID, // Identificador do menu
		'menu_paginas_reiniciar' => $_INTERFACE['menu_paginas_reiniciar'], // Reiniciar do menu
		'tabela_nome' => $_LISTA['tabela']['nome'], // Nome da tabela
		'tabela_id_posicao' => 0, // Posicao do id
		'tabela_status_posicao' => 1, // Posicao do status
		'bloquear_titulo_1' => "Ativar " . $_LISTA['ferramenta_unidade'], // Título 1 do botão bloquear 
		'bloquear_titulo_2' => "Desativar " . $_LISTA['ferramenta_unidade'], // Título 2 do botão bloquear 
		'tabela_campos' => $tabela_campos, // Array com os nomes dos campos
		'tabela_extra' => "WHERE ".$_LISTA['tabela']['status']."!='D' AND id_usuario_perfil!='1' AND status!='D' AND status!='V'", // Tabela extra
		'tabela_order' => $tabela_order, // Ordenação da tabela
		'tabela_width' => '100%', // Tamanho width da tabela
		'menu_principal' => $menu_principal,
		'menu_opcoes' => $menu_opcoes,
		'header_campos' => $header_campos,
		'header_acao' => Array( // array com todos os campos do cabeçalho
			'campo' => 'Ação', // Valor do campo
			'align' => $valor, // OPCIONAL - alinhamento horizontal
			'valign' => $valor, // OPCIONAL - alinhamento vertical
			'width' => floor(count($menu_opcoes) * 22), // OPCIONAL - tamanho x da célula
			'height' => $valor, // OPCIONAL - tamanho x da célula
		),
		'campos' => $campos,
		'outra_tabela' => $outra_tabela,
		'layout_pagina' => true,
		
	);
	
	return $parametros;
}

function lista(){
	global $_INTERFACE_OPCAO;
	
	$_INTERFACE_OPCAO = 'lista';
	
	return interface_layout(parametros_interface());
}

function historico($params){
	/* 
	$select = opcao_select_change(Array(
		'nome' => 'select',
		'tabela_campos' => Array(
			'id' => 'id',
			'nome' => 'nome',
		),
		'tabela_nome' => 'tabela_nome',
		'tabela_extra' => false,
		'tabela_order' => 'campo DESC',
		'opcao_inicial' => 'Selecione',
		'link_extra' => 'opcao=valor',
		'url' => false,
		'onchange' => true,
		'extra_select' => false,
		'option_value_igual_nome' => false,
	));
	*/
	
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	
	$nome = $params['nome'];
	$tabela_campos = $params['tabela_campos'];
	$tabela_nome = $params['tabela_nome'];
	$tabela_extra = $params['tabela_extra'];
	$tabela_order = $params['tabela_order'];
	$opcao_inicial = $params['opcao_inicial'];
	$opcao_inicial_id = $params['opcao_inicial_id'];
	$link_extra = $params['link_extra'];
	$onchange = $params['onchange'];
	$option_value_igual_nome = $params['option_value_igual_nome'];
	$extra_select = $params['extra_select'];
	$url = $params['url'];
	$id = $nome . '_id';
	
	if($_REQUEST[$id])	$_SESSION[$_SYSTEM['ID'].$id] = $_REQUEST[$id];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultados = banco_select_name
	(
		banco_campos_virgulas(Array(
			$tabela_campos['id'],
			$tabela_campos['nome'],
			$tabela_campos['moderacao'],
		))
		,
		$tabela_nome,
		$tabela_extra
		.($tabela_order ? " ORDER BY " . $tabela_order : "")
	);
	if($connect_db)banco_fechar_conexao();
	
	if($opcao_inicial){
		$options[] = $opcao_inicial;
		$optionsValue[] = ($opcao_inicial_id?$opcao_inicial_id:"-1");
	}
	
	$cont = 0;
	if($resultados){
		foreach($resultados as $resultado){
			if($resultado[$tabela_campos['moderacao']])$options[] = 'Aguardando Moderação'; else $options[] = data_hora_from_datetime_to_text($resultado[$tabela_campos['nome']]);
			$optionsValue[] = ($option_value_igual_nome ? $resultado[$tabela_campos['nome']] : $resultado[$tabela_campos['id']]);
			
			$cont++;
			
			if($_SESSION[$_SYSTEM['ID'].$id] == $resultado[$tabela_campos['id']]){
				$optionSelected = $cont;
				if(!$opcao_inicial)$optionSelected--;
			}
		}
		
		if($link_extra)$link_extra .= '&';
		$url .= '?';
		$select = formSelect($nome,$nome,$options,$optionsValue,$optionSelected,($onchange ? 'onchange=window.open("'.$url.$link_extra.$id.'="+this.value,"_self")' : '') . ($extra_select ? ($onchange ? ' ' : '') . $extra_select : ''));
		
		return $select;
	} else {
		return false;
	}
}

function editar($param = false){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_INTERFACE_OPCAO;
	global $_INTERFACE;
	
	$id = $_SESSION[$_SYSTEM['ID']."usuario"]["id_usuario"];
	
	if($id){
		$pagina = paginaModelo('html.html');
		$pagina = paginaTagValor($pagina,'<!-- form < -->','<!-- form > -->');
		
		$num_cols = 3;
		
		banco_conectar();
		
		if($_REQUEST['historico_id']){
			if($_REQUEST['historico_id'] != $id){
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						$_LISTA['tabela']['id'],
					))
					,
					$_LISTA['tabela']['nome'],
					"WHERE ".$_LISTA['tabela']['id']."='".$_REQUEST['historico_id']."'"
					." AND id_usuario_original='".$id."'"
				);
			} else {
				$resultado = true;
			}
		} else {
			$resultado = true;
		}
		
		if($resultado){
			// ================================= Local de Edição ===============================
			// Altere os campos da interface com os valores iniciais
			
			$cel1 = modelo_tag_val($pagina,'<!-- cel1 < -->','<!-- cel1 > -->');
			$pagina = modelo_tag_in($pagina,'<!-- cel1 < -->','<!-- cel1 > -->','<!-- cel1 -->');
			$cel2 = modelo_tag_val($pagina,'<!-- cel2 < -->','<!-- cel2 > -->');
			$pagina = modelo_tag_in($pagina,'<!-- cel2 < -->','<!-- cel2 > -->','<!-- cel2 -->');
			$pagina = modelo_tag_in($pagina,'<!-- grupo_cel < -->','<!-- grupo_cel > -->','<!-- grupo_cel -->');
			
			$hitorico = historico(Array(
				'nome' => 'historico',
				'tabela_campos' => Array(
					'nome' => 'data_cadastro',
					'id' => 'id_usuario',
					'moderacao' => 'moderacao',
				),
				'tabela_nome' => 'usuario',
				'tabela_nome' => 'usuario',
				'tabela_extra' => "WHERE id_usuario_original='".$id."'",
				'tabela_order' => "data_cadastro DESC",
				'onchange' => true,
				'opcao_inicial' => 'Versão Atual',
				'opcao_inicial_id' => $id,
			));
			
			if($hitorico){
				$hitorico = html(Array(
					'tag' => 'h2',
					'val' => 'Histórico de Modificações: '.$hitorico,
					'attr' => false
				));
			}
			
			$pagina = paginaTrocaVarValor($pagina,'#historico#',$hitorico);
			
			$pagina = modelo_var_troca($pagina,"#grupos_num",count($grupos));
		
			$usuario = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_usuario_perfil',
					'usuario',
					'senha',
					'nome',
					'email',
					'sobrenome',
					'cep',
					'endereco',
					'numero',
					'complemento',
					'bairro',
					'cidade',
					'uf',
					'celular',
					'telefone',
				))
				,
				$_LISTA['tabela']['nome'],
				($_REQUEST['historico_id']?"WHERE ".$_LISTA['tabela']['id']."='".$_REQUEST['historico_id']."'":"WHERE ".$_LISTA['tabela']['id']."='".$id."'")
			);
			
			$campos_guardar = Array(
				'usuario' => $usuario[0]['usuario'],
				'senha' => $usuario[0]['senha'],
				'id_usuario_perfil' => $usuario[0]['id_usuario_perfil'],
				'email' => $usuario[0]['email'],
				'nome' => $usuario[0]['nome'],
				'sobrenome' => $usuario[0]['sobrenome'],
				'cep' => $usuario[0]['cep'],
				'endereco' => $usuario[0]['endereco'],
				'numero' => $usuario[0]['numero'],
				'complemento' => $usuario[0]['complemento'],
				'bairro' => $usuario[0]['bairro'],
				'cidade' => $usuario[0]['cidade'],
				'uf' => $usuario[0]['uf'],
				'celular' => $usuario[0]['celular'],
				'telefone' => $usuario[0]['telefone'],
				'id_grupo' => $id_grupo,
			);
			
			$_SESSION[$_SYSTEM['ID'].'perfil_id'] = $usuario[0]['id_usuario_perfil'];
			
			$pagina = paginaTrocaVarValor($pagina,'#usuario',$usuario[0]['usuario']);
			$pagina = paginaTrocaVarValor($pagina,'#edit_usuario',$usuario[0]['usuario']);
			$pagina = paginaTrocaVarValor($pagina,'#senha','');
			$pagina = paginaTrocaVarValor($pagina,'#senha2','');
			$pagina = paginaTrocaVarValor($pagina,'#perfil',perfil_select());
			$pagina = paginaTrocaVarValor($pagina,'#email',$usuario[0]['email']);
			$pagina = paginaTrocaVarValor($pagina,'#edit_email',$usuario[0]['email']);
			$pagina = paginaTrocaVarValor($pagina,'#nome',$usuario[0]['nome']);
			$pagina = paginaTrocaVarValor($pagina,'#sobrenome',$usuario[0]['sobrenome']);
			$pagina = paginaTrocaVarValor($pagina,'#cep',$usuario[0]['cep']);
			$pagina = paginaTrocaVarValor($pagina,'#endereco',$usuario[0]['endereco']);
			$pagina = paginaTrocaVarValor($pagina,'#numero',$usuario[0]['numero']);
			$pagina = paginaTrocaVarValor($pagina,'#complemento',$usuario[0]['complemento']);
			$pagina = paginaTrocaVarValor($pagina,'#bairro',$usuario[0]['bairro']);
			$pagina = paginaTrocaVarValor($pagina,'#cidade',$usuario[0]['cidade']);
			$pagina = paginaTrocaVarValor($pagina,'#uf',$usuario[0]['uf']);
			$pagina = paginaTrocaVarValor($pagina,'#tel',$usuario[0]['telefone']);
			$pagina = paginaTrocaVarValor($pagina,'#cel',$usuario[0]['celular']);
			
			// ======================================================================================
			
			banco_fechar_conexao();
			
			campos_antes_guardar($campos_guardar);
			
			$in_titulo = $param ? "Visualizar" : "Modificar";
			$botao = "Gravar";
			$opcao = "editar_base";
			
			$pagina = paginaTrocaVarValor($pagina,"#form_url",$_LOCAL_ID);
			$pagina = paginaTrocaVarValor($pagina,"#botao",$botao);
			$pagina = paginaTrocaVarValor($pagina,"#opcao",$opcao);
			$pagina = paginaTrocaVarValor($pagina,"#id",$id);
			$pagina = paginaTrocaVarValor($pagina,"#cep_search",($_SYSTEM['CEP_SEARCH']?'1':''));
		
			if(!operacao('modificar_dados'))$cel_nome = 'botao'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			
			$_INTERFACE_OPCAO = 'editar'; 
			$_INTERFACE['informacao_titulo'] = $in_titulo;
			$_INTERFACE['informacao_id'] = $id;
			$_INTERFACE['inclusao'] = $pagina;
		
			return interface_layout(parametros_interface());
		} else
			header('Location: .');
	} else
		header('Location: .');
}

function editar_base(){
	global $_SYSTEM;
	global $_LOCAL_ID;
	global $_LISTA;
	global $_ALERTA;
	
	if($_POST["id"]){
		$id = $_POST["id"];
		
		$id_usuario = $_SESSION[$_SYSTEM['ID']."usuario"]["id_usuario"];
		
	
		if($id != $id_usuario){
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					$_LISTA['tabela']['id'],
				))
				,
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
				." AND id_usuario_original='".$id_usuario."'"
			);
		} else {
			$resultado = true;
		}
		
		if($resultado){
			$campos_antes = campos_antes_recuperar();
			
			$campos_editaveis = Array(
				'usuario',
				'senha',
				'email',
				'nome',
				'sobrenome',
				'endereco',
				'numero',
				'complemento',
				'bairro',
				'cidade',
				'uf',
				'cep',
				'telefone',
				'celular',
			);
			
			banco_conectar();
			
			// ================================= Guardar histórico =============================
			
			foreach($campos_editaveis as $campo){
				$campo_nome = $campo; $post_nome = $campo_nome; 
				
				switch($campo){
					case 'senha':
						if($_POST[$campo_nome])$mudou_senha = true;
					break;
					default:
						if($campos_antes[$campo_nome] != $_POST[$campo_nome])$mudou_campos = true;
				}
			}
			
			if(operacao('moderacao') && !$_SESSION[$_SYSTEM['ID']."admin"] && $mudou_campos){
				banco_update
				(
					"moderacao=NULL",
					$_LISTA['tabela']['nome'],
					"WHERE id_usuario_original='".$id."' AND moderacao IS NOT NULL"
				);
				
				$campo_nome = "moderacao"; $post_nome = $campo_nome; 			$campos[] = Array($campo_nome,'1');
				$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'D');
				$campo_nome = "id_usuario_original"; $post_nome = $campo_nome; 	$campos[] = Array($campo_nome,$id);
				$campo_nome = "id_usuario_perfil"; $post_nome = $campo_nome;	$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
				
				foreach($campos_editaveis as $campo){
					$campo_nome = $campo; $post_nome = $campo_nome; 
					
					switch($campo){
						case 'senha':
							if($_POST[$post_nome])		$campos[] = Array($campo_nome,crypt($_POST[$post_nome]));
						break;
						default:
							if($_POST[$post_nome])		$campos[] = Array($campo_nome,$_POST[$post_nome]);
					}
				}
				
				$campo_nome = "data_cadastro";									$campos[] = Array($campo_nome,'NOW()',true);
				
				banco_insert_name($campos,$_LISTA['tabela']['nome']);
				
				$_ALERTA = 'Aguardando moderação pelo administrador do sistema para permitir alteração dos seus Dados Pessoais';
			} else {
				// ================================= Local de Edição ===============================
				
				$campo_tabela = "usuario";
				
				foreach($campos_editaveis as $campo){
					$campo_nome = $campo; $post_nome = $campo_nome; 
					
					switch($campo){
						case 'senha':
							if($_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . crypt($_POST[$campo_nome]) . "'";}
						break;
						default:
							if($campos_antes[$campo_nome] != $_POST[$campo_nome]){$editar[$campo_tabela][] = $campo_nome."='" . $_POST[$campo_nome] . "'";}
					}
				}
				
				$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
				
				if($editar_sql[$campo_tabela]){
					banco_update
					(
						$editar_sql[$campo_tabela],
						$_LISTA['tabela']['nome'],
						"WHERE ".$_LISTA['tabela']['id']."='".$id."'"
					);
				}
				
				$campo_nome = "status"; $post_nome = $campo_nome; 				$campos[] = Array($campo_nome,'D');
				$campo_nome = "id_usuario_original"; $post_nome = $campo_nome; 	$campos[] = Array($campo_nome,$id);
				$campo_nome = "id_usuario_perfil"; $post_nome = $campo_nome;	$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
				
				foreach($campos_editaveis as $campo){
					$campo_nome = $campo; $post_nome = $campo_nome;  			$campos[] = Array($campo_nome,$campos_antes[$campo_nome]);
				}
				
				$campo_nome = "data_cadastro";									$campos[] = Array($campo_nome,'NOW()',true);
				
				banco_insert_name($campos,$_LISTA['tabela']['nome']);
			}
			
			banco_fechar_conexao();
		}
	}
	
	return editar(operacao('modificar_dados'));
}

// ======================================================================================

function ajax(){
	global $_SYSTEM;
	global $_PAGINA_OPCAO;
	global $_LISTA;
	global $_LISTA_2;
	
	if($_REQUEST['query_id'] == 'busca_nome' && operacao('buscar')){
		$query = utf8_decode($_REQUEST["query"]);
		if(!$query) return;

		banco_conectar();
		
		$resultado = banco_select
		(
			$_LISTA['tabela']['id'] . "," . $_LISTA['tabela']['campo'],
			$_LISTA['tabela']['nome'],
			"WHERE UCASE(".$_LISTA['tabela']['campo'].") LIKE UCASE('%" . $query . "%') AND ".$_LISTA['tabela']['status']."!='D'"
		);
		
		banco_fechar_conexao();

		for($i=0;$i<count($resultado);$i++){
			$saida[] = Array(
				'value' => utf8_encode($resultado[$i][1]),
				'id' => $resultado[$i][0],
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['usuario']){
		if($_REQUEST['usuario'] != $_REQUEST['edit_usuario']){
			banco_conectar();
			
			$resultado = banco_select
			(
				$_LISTA['tabela']['id'] . "," . $_LISTA['tabela']['campo'],
				$_LISTA['tabela']['nome'],
				"WHERE ".$_LISTA['tabela']['campo']."='" . $_REQUEST['usuario'] . "' AND status!='D'"
			);
			
			banco_fechar_conexao();

			if($resultado){
				$saida = "sim";
			} else {
				$saida = "nao";
			}
		} else {
			$saida = "nao";
		}
	}
	
	if($_REQUEST['email']){
		if($_REQUEST['email'] != $_REQUEST['edit_email']){
			banco_conectar();
			
			$resultado = banco_select
			(
				$_LISTA['tabela']['id'],
				$_LISTA['tabela']['nome'],
				"WHERE email='" . $_REQUEST['email'] . "' AND status!='D'"
			);
			$resultado2 = banco_select
			(
				"id_emails",
				"emails",
				"WHERE email='" . $_REQUEST['email'] . "' AND status!='D'"
			);
			
			banco_fechar_conexao();

			if($resultado || $resultado2){
				$saida = "sim";
			} else {
				$saida = "nao";
			}
		} else {
			$saida = "nao";
		}
	}
	
	if($_REQUEST['cep']){
		$cep_part1 = explode('.',$_REQUEST['cep']);
		$cep_part2 = explode('-',$cep_part1[0].$cep_part1[1]);
		
		$cep = $cep_part2[0].$cep_part2[1];
		$cep_cid = $cep_part2[0].'000';
		
		banco_conectar();
		
		$endereco = banco_select_name
		(
			banco_campos_virgulas(Array(
				'bairro_codigo',
				'endereco_logradouro',
			))
			,
			"endereco",
			"WHERE endereco_cep='".$cep."'"
		);
		$bairro = banco_select_name
		(
			banco_campos_virgulas(Array(
				'bairro_descricao',
				'cidade_codigo',
			))
			,
			"bairro",
			"WHERE bairro_codigo='".$endereco[0]['bairro_codigo']."'"
		);
		$cidade = banco_select_name
		(
			banco_campos_virgulas(Array(
				'uf_codigo',
				'cidade_descricao',
			))
			,
			"cidade",
			"WHERE cidade_codigo='".$bairro[0]['cidade_codigo']."'"
		);
		$uf = banco_select_name
		(
			banco_campos_virgulas(Array(
				'uf_sigla',
			))
			,
			"uf",
			"WHERE uf_codigo='".$cidade[0]['uf_codigo']."'"
		);
		
		banco_fechar_conexao();

		$saida = "{\n";
		$saida .= "'endereco' : '".$endereco[0]['endereco_logradouro']."' ,\n";
		$saida .= "'bairro' : '".$bairro[0]['bairro_descricao']."' ,\n";
		$saida .= "'cidade' : '".$cidade[0]['cidade_descricao']."' ,\n";
		$saida .= "'uf' : '".$uf[0]['uf_sigla']."' ,\n";
		$saida .= "}\n";
	}
	
	return utf8_encode($saida);
}

function start(){	
	global $_PAGINA_OPCAO;
	global $_SYSTEM;
	global $_INTERFACE_OPCAO;
	global $_HTML;
	global $_LOCAL_ID;
	
	if($_REQUEST["opcao"])				$opcoes = $_REQUEST["opcao"];
	if($_REQUEST["buscar_opcao"])		$opcoes = $_REQUEST["buscar_opcao"];
	$_PAGINA_OPCAO = $opcoes;
	
	if(!$_REQUEST["ajax"]){
		$opcao_anterior = $_SESSION[$_SYSTEM['ID']."opcao_anterior"];
		
		switch($opcoes){
			case 'editar':						$saida = (operacao('modificar_dados') ? editar(true) : editar());break;
			case 'editar_base':					$saida = (operacao('modificar_dados') ? editar_base() : editar());break;
			default: 							$saida = editar();
		}
		
		$_SESSION[$_SYSTEM['ID']."opcao_anterior"] = $opcoes;
		
		$_HTML['body'] = $saida;
		
		echo pagina();
	} else {
		echo ajax();
	}
}

start();

?>