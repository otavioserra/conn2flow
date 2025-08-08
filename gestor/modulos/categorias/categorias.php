<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'categorias';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.0.0',
	'bibliotecas' => Array('interface','html'),
	'tabela' => Array(
		'nome' => 'hosts_categorias',
		'id' => 'id',
		'id_numerico' => 'id_'.'hosts_categorias',
		'id_numerico_ref' => 'id_'.'hosts_categorias_pai',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
	),
	'resources' => [
		'pt-br' => [
			'layouts' => [],
			'pages' => [
			    [
			        'name' => 'Categorias',
			        'id' => 'categorias',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'categorias/',
			        'type' => 'system',
			        'option' => 'listar',
			        'root' => true,
			        'version' => '1.0',
			        'checksum' => [
			            'html' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'combined' => 'd41d8cd98f00b204e9800998ecf8427e',
			        ],
			    ],
			    [
			        'name' => 'Categorias - Adicionar',
			        'id' => 'categorias-adicionar',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'categorias/adicionar/',
			        'type' => 'system',
			        'option' => 'adicionar',
			        'version' => '1.0',
			        'checksum' => [
			            'html' => '877ad7e017a5293421bc23a06577b179',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'combined' => '877ad7e017a5293421bc23a06577b179',
			        ],
			    ],
			    [
			        'name' => 'Categorias - Editar',
			        'id' => 'categorias-editar',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'categorias/editar/',
			        'type' => 'system',
			        'option' => 'editar',
			        'version' => '1.0',
			        'checksum' => [
			            'html' => '362d2c41bd361cb52bc8469587751608',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'combined' => '362d2c41bd361cb52bc8469587751608',
			        ],
			    ],
			    [
			        'name' => 'Categorias - Adicionar Filho',
			        'id' => 'categorias-adicionar-filho',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'categorias/adicionar-filho/',
			        'type' => 'system',
			        'option' => 'adicionar-filho',
			        'version' => '1.0',
			        'checksum' => [
			            'html' => '061e4d081dd20b182ece5a50c0d82cd8',
			            'css' => 'd41d8cd98f00b204e9800998ecf8427e',
			            'combined' => '061e4d081dd20b182ece5a50c0d82cd8',
			        ],
			    ],
			],
			'components' => [],
		],
	],
);

// ===== Funções Auxiliares

function categorias_categorias_pais_arvore($params = false){
	/**********
		Descrição: função responsável por buscar recursivamente todos as categorias ancestrais de uma categoria.
	**********/
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_categorias_pai - Int - Obrigatório - Identificador numérico da categoria pai.
	
	// ===== 
	
	$categoriaPaiBD = banco_select_name
	(
		banco_campos_virgulas(Array(
			$modulo['tabela']['id'],
			$modulo['tabela']['id_numerico_ref'],
			'nome',
		))
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id_numerico']."='".$id_categorias_pai."'"
	);
	
	if($categoriaPaiBD){
		$categoria = Array(
			'nome' => $categoriaPaiBD[0]['nome'],
			'id' => $categoriaPaiBD[0][$modulo['tabela']['id']],
		);
		
		if(existe($categoriaPaiBD[0][$modulo['tabela']['id_numerico_ref']])){
			$categoriasPais = categorias_categorias_pais_arvore(Array(
				'id_categorias_pai' => $categoriaPaiBD[0][$modulo['tabela']['id_numerico_ref']],
			));
			
			array_push($categoriasPais, $categoria);
			
			$arrayRetorno = $categoriasPais;
		} else {
			$arrayRetorno[] = $categoria;
		}
		
		return $arrayRetorno;
	} else {
		return Array();
	}
}

function categorias_categoria_localizacao($params = false){
	/**********
		Descrição: função responsável por montar a localização da categoria em relação aos seus ancestrais.
	**********/
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id_categorias_pai - Int - Obrigatório - Identificador numérico da categoria pai.
	// nome - String - Obrigatório - Nome da categoria.
	
	// ===== 
	
	if(isset($id_categorias_pai) && isset($nome)){
		$arvoreCategoriasPais = categorias_categorias_pais_arvore(Array(
			'id_categorias_pai' => $id_categorias_pai,
		));
		
		// ===== Incluir a raiz na árvode de categorias pai.
		
		$categoriaRaiz = Array(
			'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'root-category')),
			'id' => '_raiz',
		);
		
		array_unshift($arvoreCategoriasPais, $categoriaRaiz);
		
		// ===== Layout da localização com todos os itens.
		
		$categoriaPai = gestor_componente(Array(
			'id' => 'categorias-categorias-pai-info',
		));
		
		// ===== Verifica se existe pais.
		
		if(count($arvoreCategoriasPais) > 0){
			// ===== Pegar células do modelo.
			
			$cel_nome = 'div-pais'; $cel[$cel_nome] = modelo_tag_val($categoriaPai,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $categoriaPai = modelo_tag_in($categoriaPai,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
			$cel_nome = 'pais'; $cel[$cel_nome] = modelo_tag_val($categoriaPai,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $categoriaPai = modelo_tag_in($categoriaPai,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
			
			// ===== URLs de acesso as categorias pai.
			
			$urlRaiz = $_GESTOR['url-raiz'].'categorias/';
			$urlComum = $_GESTOR['url-raiz'].'categorias/editar/?id=#pai-id#';
			
			$cPaiCount = 0;
			
			foreach($arvoreCategoriasPais as $cPai){
				// ===== Montar link dos pais.
				
				$cel_nome = 'pais';
				$cel_aux = $cel[$cel_nome];
				
				if($cPai['id'] == '_raiz'){
					$url = $urlRaiz;
				} else {
					$url = $urlComum;
					
					$url = modelo_var_troca($url,"#pai-id#",$cPai['id']);
				}
				
				$cel_aux = modelo_var_troca($cel_aux,"#pai-url#",$url);
				$cel_aux = modelo_var_troca($cel_aux,"#pai-nome#",$cPai['nome']);
				
				// ===== Incluir divisor pai caso seja necessário.
				
				$cel_nome = 'div-pais';
				if($cPaiCount < count($arvoreCategoriasPais) - 1){
					$cel_aux = modelo_var_in($cel_aux,'<!-- '.$cel_nome.' -->',$cel[$cel_nome]);
				}
				
				// ===== Incluir a célula formatada no layout principal.
				
				$cel_nome = 'pais';
				$categoriaPai = modelo_var_in($categoriaPai,'<!-- '.$cel_nome.' -->',$cel_aux);
				
				$cPaiCount++;
			}
			
			$cel_nome = 'pais'; $categoriaPai = modelo_var_troca($categoriaPai,'<!-- '.$cel_nome.' -->','');
			$cel_nome = 'div-pais'; $categoriaPai = modelo_var_troca($categoriaPai,'<!-- '.$cel_nome.' -->','');
		}
		
		$categoriaPai = modelo_var_troca($categoriaPai,"#filho-nome#",$nome);
		
		return $categoriaPai;
	}
	
	return '';
}

// ===== Funções Principais

function categorias_adicionar_filho(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Gravar registro no Banco
	
	if(isset($_GESTOR['adicionar-banco'])){
		$usuario = gestor_usuario();
		
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				)
			)
		));
		
		// ===== Categoria Pai
		
		$id_pai = banco_escape_field($_REQUEST['id_pai']);
		
		$categoriaPaiBD = banco_select_name
		(
			banco_campos_virgulas(Array(
				$modulo['tabela']['id_numerico'],
				'id_modulos',
			))
			,
			$modulo['tabela']['nome'],
			"WHERE ".$modulo['tabela']['id']."='".$id_pai."'"
			." AND ".$modulo['tabela']['status']."!='D'"
			." AND id_hosts='".$_GESTOR['host-id']."'"
		);
		
		if(!$categoriaPaiBD){
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'category-child-parent-not-found'))
			));
			
			gestor_redirecionar_raiz();
		}
		
		$id_categorias_pai = $categoriaPaiBD[0][$modulo['tabela']['id_numerico']];
		$id_modulos = $categoriaPaiBD[0]['id_modulos'];
		
		// ===== Definição do identificador
		
		$campos = null;
		$campo_sem_aspas_simples = false;
		
		$id = banco_identificador(Array(
			'id' => banco_escape_field($_REQUEST["nome"]),
			'tabela' => Array(
				'nome' => $modulo['tabela']['nome'],
				'campo' => $modulo['tabela']['id'],
				'id_nome' => $modulo['tabela']['id_numerico'],
				'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
			),
		));
		
		// ===== Campos gerais
		
		$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_hosts"; $campo_valor = $_GESTOR['host-id']; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $post_nome = "nome"; 										if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_modulos"; $campo_valor = $id_modulos; 						$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['id_numerico_ref']; $campo_valor = $id_categorias_pai; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		// ===== Campos comuns
		
		$campo_nome = $modulo['tabela']['status']; $campo_valor = 'A'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['versao']; $campo_valor = '1'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['data_criacao']; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = $modulo['tabela']['data_modificacao']; $campo_valor = 'NOW()'; 	$campos[] = Array($campo_nome,$campo_valor,true);
	
		banco_insert_name
		(
			$campos,
			$modulo['tabela']['nome']
		);
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
	}
	
	// ===== Categoria Pai
	
	$id_pai = banco_escape_field($_REQUEST['id']);
	
	$categoriaPaiBD = banco_select_name
	(
		banco_campos_virgulas(Array(
			'nome',
		))
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id_pai."'"
		." AND ".$modulo['tabela']['status']."!='D'"
		." AND id_hosts='".$_GESTOR['host-id']."'"
	);
	
	if(!$categoriaPaiBD){
		interface_alerta(Array(
			'redirect' => true,
			'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'category-child-parent-not-found'))
		));
		
		gestor_redirecionar_raiz();
	}
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#categoria-pai-texto#",$categoriaPaiBD[0]['nome']);
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#id-pai#",$id_pai);
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface adicionar finalizar opções
	
	$_GESTOR['interface'][$_GESTOR['interface-opcao']]['finalizar'] = Array(
		'formulario' => Array(
			'validacao' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				)
			)
		)
	);
}

function categorias_adicionar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Gravar registro no Banco
	
	if(isset($_GESTOR['adicionar-banco'])){
		$usuario = gestor_usuario();
		
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				)
			)
		));
		
		// ===== Definição do identificador
		
		$campos = null;
		$campo_sem_aspas_simples = false;
		
		$id = banco_identificador(Array(
			'id' => banco_escape_field($_REQUEST["nome"]),
			'tabela' => Array(
				'nome' => $modulo['tabela']['nome'],
				'campo' => $modulo['tabela']['id'],
				'id_nome' => $modulo['tabela']['id_numerico'],
				'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
			),
		));
		
		// ===== Campos gerais
		
		$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id_hosts"; $campo_valor = $_GESTOR['host-id']; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $post_nome = "nome"; 										if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		$campo_nome = "id_modulos"; $post_nome = 'modulo'; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		
		// ===== Campos comuns
		
		$campo_nome = $modulo['tabela']['status']; $campo_valor = 'A'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['versao']; $campo_valor = '1'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['data_criacao']; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = $modulo['tabela']['data_modificacao']; $campo_valor = 'NOW()'; 	$campos[] = Array($campo_nome,$campo_valor,true);
	
		banco_insert_name
		(
			$campos,
			$modulo['tabela']['nome']
		);
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface adicionar finalizar opções
	
	$_GESTOR['interface']['adicionar']['finalizar'] = Array(
		'formulario' => Array(
			'validacao' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				)
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'module',
					'nome' => 'modulo',
					'procurar' => true,
					'limpar' => true,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-module-placeholder')),
					'tabela' => Array(
						'nome' => 'modulos',
						'campo' => 'nome',
						'id_numerico' => 'id_modulos',
						'where' => "id_modulos_grupos='7'",
					),
				)
			)
		)
	);
}

function categorias_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do 
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para editar.
	
	$camposBanco = Array(
		'nome',
		'id_modulos',
		$modulo['tabela']['id_numerico_ref'],
		$modulo['tabela']['id_numerico'],
	);
	
	$camposBancoPadrao = Array(
		$modulo['tabela']['status'],
		$modulo['tabela']['versao'],
		$modulo['tabela']['data_criacao'],
		$modulo['tabela']['data_modificacao'],
	);
	
	$camposBancoEditar = array_merge($camposBanco,$camposBancoPadrao);
	$camposBancoAntes = $camposBanco;
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Recuperar o estado dos dados do banco de dados antes de editar.
		
		if(!banco_select_campos_antes_iniciar(
			banco_campos_virgulas($camposBancoAntes)
			,
			$modulo['tabela']['nome'],
			"WHERE ".$modulo['tabela']['id']."='".$id."'"
			." AND ".$modulo['tabela']['status']."!='D'"
			." AND id_hosts='".$_GESTOR['host-id']."'"
		)){
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-database-field-before-error'))
			));
			
			gestor_redirecionar_raiz();
		}
		
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				)
			)
		));
		
		// ===== Valores padrões da tabela e regras para o campo nome
		
		$editar = Array(
			'tabela' => $modulo['tabela']['nome'],
			'extra' => "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D' AND id_hosts='".$_GESTOR['host-id']."'",
		);
		
		$campo_nome = "nome"; $request_name = 'nome'; $alteracoes_name = 'name'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; if(!isset($_REQUEST['_gestor-nao-alterar-id'])){$alterar_id = true;} $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		
		// ===== Se mudar o nome, mudar o identificador do registro
		
		if(isset($alterar_id)){
			$id_novo = banco_identificador(Array(
				'id' => banco_escape_field($_REQUEST["nome"]),
				'tabela' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campo' => $modulo['tabela']['id'],
					'id_nome' => $modulo['tabela']['id_numerico'],
					'id_valor' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
					'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
				),
			));
			
			$alteracoes_name = 'id'; $alteracoes[] = Array('campo' => 'field-id', 'valor_antes' => $id,'valor_depois' => $id_novo);
			$campo_nome = $modulo['tabela']['id']; $editar['dados'][] = $campo_nome."='" . $id_novo . "'";
			$_GESTOR['modulo-registro-id'] = $id_novo;
		}
		
		// ===== Atualização dos demais campos.
		
		$campo_nome = "id_modulos"; $request_name = 'modulo'; $alteracoes_name = 'module'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."=".($_REQUEST[$request_name] ? "'" . banco_escape_field($_REQUEST[$request_name]) . "'" : 'NULL'); $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]),'tabela' => Array(
				'nome' => 'modulos',
				'campo' => 'nome',
				'id_numerico' => 'id_modulos',
			));}
		
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if(isset($editar['dados'])){
			$campo_nome = $modulo['tabela']['versao']; $editar['dados'][] = $campo_nome." = ".$campo_nome." + 1";
			$campo_nome = $modulo['tabela']['data_modificacao']; $editar['dados'][] = $campo_nome."=NOW()";
			
			$editar['sql'] = banco_campos_virgulas($editar['dados']);
			
			if($editar['sql']){
				banco_update
				(
					$editar['sql'],
					$editar['tabela'],
					$editar['extra']
				);
			}
			$editar = false;
			
			// ===== Incluir no histórico as alterações.
			
			interface_historico_incluir(Array(
				'alteracoes' => $alteracoes,
			));
		}
		
		// ===== Reler URL.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.(isset($id_novo) ? $id_novo : $id));
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Selecionar dados do banco de dados
	
	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas($camposBancoEditar)
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
		." AND id_hosts='".$_GESTOR['host-id']."'"
	);
	
	if($_GESTOR['banco-resultado']){
		$nome = (isset($retorno_bd['nome']) ? $retorno_bd['nome'] : '');
		$id_modulos = (isset($retorno_bd['id_modulos']) ? $retorno_bd['id_modulos'] : '');
		$id_numerico = (isset($retorno_bd[$modulo['tabela']['id_numerico']]) ? $retorno_bd[$modulo['tabela']['id_numerico']] : '');
		$id_categorias_pai = (isset($retorno_bd[$modulo['tabela']['id_numerico_ref']]) ? $retorno_bd[$modulo['tabela']['id_numerico_ref']] : '');
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome#',$nome);
		
		// ===== Popular os metaDados
		
		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');
		
		if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['versao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-version')),'dado' => $retorno_bd[$modulo['tabela']['versao']]); }
		if(isset($retorno_bd[$modulo['tabela']['status']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status')),'dado' => ($retorno_bd[$modulo['tabela']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-active')).'</b></div>' : '').($retorno_bd[$modulo['tabela']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-inactive')).'</b></div>' : '')); }
		
		// ===== Categorias Filho
		
		$categoriasFilhoBD = banco_select_name
		(
			banco_campos_virgulas(Array(
				$modulo['tabela']['id'],
				'nome',
			))
			,
			$modulo['tabela']['nome'],
			"WHERE ".$modulo['tabela']['id_numerico_ref']."='".$id_numerico."'"
			." AND ".$modulo['tabela']['status']."!='D'"
			." AND id_hosts='".$_GESTOR['host-id']."'"
			." ORDER BY nome ASC"
		);
		
		if($categoriasFilhoBD){
			$categoriasFilho = gestor_componente(Array(
				'id' => 'categorias-categorias-filho-lista',
			));
			
			$cel_nome = 'item'; $cel[$cel_nome] = modelo_tag_val($categoriasFilho,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $categoriasFilho = modelo_tag_in($categoriasFilho,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
			
			foreach($categoriasFilhoBD as $cFilho){
				$cel_aux = $cel[$cel_nome];
				
				$cel_aux = modelo_var_troca($cel_aux,"#label-button-edit#",gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-edit')));
				$cel_aux = modelo_var_troca($cel_aux,"#tooltip-button-edit#",gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-edit')));
				$cel_aux = modelo_var_troca($cel_aux,"#id#",$cFilho[$modulo['tabela']['id']]);
				$cel_aux = modelo_var_troca($cel_aux,"#nome#",$cFilho['nome']);
				
				$categoriasFilho = modelo_var_in($categoriasFilho,'<!-- '.$cel_nome.' -->',$cel_aux);
			}
			$categoriasFilho = modelo_var_troca($categoriasFilho,'<!-- '.$cel_nome.' -->','');
		} else {
			$categoriasFilho = gestor_componente(Array(
				'id' => 'categorias-categorias-filho-info',
			));
			
			$message = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'info-categories-child-empty'));
			
			$categoriasFilho = modelo_var_troca($categoriasFilho,"#message#",$message);
		}
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#categorias-filho#',$categoriasFilho);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#categoria-pai#',$id);
		
		// ===== Categoria Pai
		
		if(existe($id_categorias_pai)){
			$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#cont-categoria-pai#",categorias_categoria_localizacao(Array(
				'id_categorias_pai' => $id_categorias_pai,
				'nome' => $nome,
			)));
		} else {
			$cel_nome = 'categoria-pai'; $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		}
		
	} else {
		gestor_redirecionar_raiz();
	}
	
	// ===== Interface editar finalizar opções
	
	$_GESTOR['interface']['editar']['finalizar'] = Array(
		'id' => $id,
		'metaDados' => $metaDados,
		'banco' => Array(
			'nome' => $modulo['tabela']['nome'],
			'id' => $modulo['tabela']['id'],
			'status' => $modulo['tabela']['status'],
		),
		'botoes' => Array(
			'adicionar' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/adicionar/',
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-insert')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-insert')),
				'icon' => 'plus circle',
				'cor' => 'blue',
			),
			'status' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=status&'.$modulo['tabela']['status'].'='.($status_atual == 'A' ? 'I' : 'A' ).'&'.$modulo['tabela']['id'].'='.$id.'&redirect='.urlencode($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id),
				'rotulo' => ($status_atual == 'A' ? gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-desactive')) : gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-active')) ),
				'tooltip' => ($status_atual == 'A' ? gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-desactive')) : gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-active'))),
				'icon' => ($status_atual == 'A' ? 'eye' : 'eye slash' ),
				'cor' => ($status_atual == 'A' ? 'green' : 'brown' ),
			),
			'excluir' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=excluir&'.$modulo['tabela']['id'].'='.$id,
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-delete')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-delete')),
				'icon' => 'trash alternate',
				'cor' => 'red',
			),
		),
		'formulario' => Array(
			'validacao' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'nome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
				)
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'module',
					'nome' => 'modulo',
					'procurar' => true,
					'limpar' => true,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-module-placeholder')),
					'tabela' => Array(
						'nome' => 'modulos',
						'campo' => 'nome',
						'id_numerico' => 'id_modulos',
						'id_selecionado' => $id_modulos,
						'where' => "id_modulos_grupos='7'",
					),
				)
			)
		)
	);
}

function categorias_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'adicionar-filho':
			$_GESTOR['interface-opcao'] = 'adicionar-incomum';
		break;
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'nome',
						'id_modulos',
						$modulo['tabela']['id_numerico_ref'],
						$modulo['tabela']['data_modificacao'],
					),
					'id' => $modulo['tabela']['id'],
					'status' => $modulo['tabela']['status'],
					'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
				),
				'tabela' => Array(
					'rodape' => true,
					'colunas' => Array(
						Array(
							'id' => 'nome',
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-name')),
							'ordenar' => 'asc',
						),
						Array(
							'id' => 'id_modulos',
							'nome' => gestor_variaveis(Array('modulo' => 'modulos','id' => 'module-name')),
							'formatar' => Array(
								'id' => 'outraTabela',
								'valor_senao_existe' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-module-placeholder')),
								'tabela' => Array(
									'nome' => 'modulos',
									'campo_trocar' => 'nome',
									'campo_referencia' => 'id_modulos',
								),
							)
						),
						Array(
							'id' => $modulo['tabela']['id_numerico_ref'],
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-parent-label')),
							'formatar' => Array(
								'id' => 'outraTabela',
								'valor_senao_existe' => '<span class="ui info text">'.gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-parent-empy')).'</span>',
								'tabela' => Array(
									'nome' => $modulo['tabela']['nome'],
									'campo_trocar' => 'nome',
									'campo_referencia' => $modulo['tabela']['id_numerico'],
								),
							)
						),
						Array(
							'id' => $modulo['tabela']['data_modificacao'],
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),
							'formatar' => 'dataHora',
							'nao_procurar' => true,
						),
					),
				),
				'opcoes' => Array(
					'editar' => Array(
						'url' => 'editar/',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-edit')),
						'icon' => 'edit',
						'cor' => 'basic blue',
					),
					'adicionar-filho' => Array(
						'url' => 'adicionar-filho/',
						'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'category-child-placeholder')),
						'icon' => 'plus circle',
						'cor' => 'basic orange',
					),
					'ativar' => Array(
						'opcao' => 'status',
						'status_atual' => 'I',
						'status_mudar' => 'A',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-active')),
						'icon' => 'eye slash',
						'cor' => 'basic brown',
					),
					'desativar' => Array(
						'opcao' => 'status',
						'status_atual' => 'A',
						'status_mudar' => 'I',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-desactive')),
						'icon' => 'eye',
						'cor' => 'basic green',
					),
					'excluir' => Array(
						'opcao' => 'excluir',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-delete')),
						'icon' => 'trash alternate',
						'cor' => 'basic red',
					),
				),
				'botoes' => Array(
					'adicionar' => Array(
						'url' => 'adicionar/',
						'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-insert')),
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-insert')),
						'icon' => 'plus circle',
						'cor' => 'blue',
					),
				),
			);
		break;
	}
}

// ==== Ajax

function categorias_ajax_opcao(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

// ==== Start

function categorias_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': categorias_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		categorias_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'adicionar-filho': categorias_adicionar_filho(); break;
			case 'adicionar': categorias_adicionar(); break;
			case 'editar': categorias_editar(); break;
		}
		
		interface_finalizar();
	}
}

categorias_start();

?>