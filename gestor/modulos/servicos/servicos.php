<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'servicos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']]		=	Array(
	'versao' => '1.1.116',
	'bibliotecas' => Array('interface','html','pagina','formato'),
	'tabela' => Array(
		'nome' => 'hosts_servicos',
		'id' => 'id',
		'id_numerico' => 'id_'.'hosts_servicos',
		'status' => 'status',
		'versao' => 'versao',
		'data_criacao' => 'data_criacao',
		'data_modificacao' => 'data_modificacao',
		'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
	),
	'verificarCamposOutraTabela' => Array(
		'caminho' => Array(
			'nome' => 'hosts_paginas',
			'id_numerico' => 'id_'.'hosts_paginas',
			'id' => 'id',
			'status' => 'status',
			'where' => "id_hosts='".$_GESTOR['host-id']."'", // Somente acessar dados do host permitido.
		),
	),
	'lotesPadroes' => Array(
		'nomeLotes' => 'Lote',
		'idLotes' => 'lote',
		'nomeVariacao' => 'Variação',
		'idVariacao' => 'variacao',
		'visibilidade' => Array(
			Array(
				'texto' => 'Sempre',
				'valor' => 'sempre',
			),
			Array(
				'texto' => 'Data Início',
				'valor' => 'datainicio',
			),
			Array(
				'texto' => 'Data Fim',
				'valor' => 'datafim',
			),
			Array(
				'texto' => 'Período',
				'valor' => 'periodo',
			),
		),
	)
);

// ===== Funções auxiliares

function servicos_pagina($params = false){
	/**********
		Descrição: adicionar/editar a página do serviço.
	**********/
	
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// opcao - String - Obrigatório - Opção para saber se vai adicionar ou editar uma página.
	// id_servicos - String - Obrigatório - Identificador numérico do serviço.
	
	// Se opcao = 'editar'
	
	// nomeMudou - Bool - Opcional - Alterar o nome da página.
	// caminhoMudou - Bool - Opcional - Alterar o caminho da página.
	
	// Se opcao = 'status'
	
	// status - String - Obrigatório - Status atual do serviço.
	
	// ===== 
	
	if(isset($opcao) && isset($id_servicos)){
		// ===== Modulo dados.
		
		$tabela = $_GESTOR['modulo#'.$_GESTOR['modulo-id']]['tabela'];
		
		// ===== Opção operações.
		
		switch($opcao){
			case 'adicionar':
			case 'editar':
				// ===== Buscar dados do serviço.
				
				$servicos = banco_select_name(
					banco_campos_virgulas(Array(
						'template_id',
						'template_tipo',
						'id_hosts_arquivos_Imagem',
						'id_hosts_paginas',
						'nome',
						'caminho',
						'id',
						'preco',
						'descricao',
					)),
					$tabela['nome'],
					"WHERE ".$tabela['id_numerico']."='".$id_servicos."'"
				);
				
				$servicos = $servicos[0];
				
				// ===== Buscar dados do template.
				
				switch($servicos['template_tipo']){
					case 'gestor':
						$templates = banco_select_name(
							banco_campos_virgulas(Array(
								'html',
								'css',
								'versao',
							)),
							"templates",
							"WHERE id='".$servicos['template_id']."'"
							." AND status!='D'"
						); $templates = $templates[0];
						
						$html = $templates['html'];
						$css = $templates['css'];
						$template_versao = $templates['versao'];
					break;
					case 'hosts':
						$templates = banco_select_name(
							banco_campos_virgulas(Array(
								'html',
								'css',
								'versao',
							)),
							"hosts_templates",
							"WHERE id='".$servicos['template_id']."'"
							." AND status!='D'"
							." AND id_hosts='".$_GESTOR['host-id']."'"
						); $templates = $templates[0];
						
						$html = $templates['html'];
						$css = $templates['css'];
						$template_versao = $templates['versao'];
						
						// ===== Adicionar o prefixo hosts para diferenciar o template id com o do gestor. É necessário pois o template_id pode ser o mesmo em templates diferentes.
						
						$servicos['template_id'] = 'hosts_'.$servicos['template_id'];
					break;
					default:
						$html = '';
						$css = '';
						$template_versao = '';
				}
				
				// ===== Desmascarar o HTML e CSS.
			
				$html = pagina_variaveis_globais_desmascarar(Array('valor' => $html));
				$css = pagina_variaveis_globais_desmascarar(Array('valor' => $css));
				
				// ===== Pegar o ID do layout.
				
				$config = gestor_incluir_configuracao(Array(
					'id' => 'templates.config',
				));
				
				$layoutsIds = $config['layouts']['servicos'];
				
				$hosts_layouts = banco_select_name(
					banco_campos_virgulas(Array(
						'id_hosts_layouts',
					)),
					"hosts_layouts",
					"WHERE id='".$layoutsIds."'"
					." AND status!='D'"
					." AND id_hosts='".$_GESTOR['host-id']."'"
				); $hosts_layouts = $hosts_layouts[0];
				
				$id_hosts_layouts = $hosts_layouts['id_hosts_layouts'];
				
				// ===== Verificar se a página não foi excluída, se for criar uma nova.
				
				$paginaOpcao = $opcao;
				
				if(existe($servicos['id_hosts_paginas'])){
					$hosts_paginas = banco_select_name(
						banco_campos_virgulas(Array(
							'id_hosts_paginas',
						)),
						"hosts_paginas",
						"WHERE id_hosts_paginas='".$servicos['id_hosts_paginas']."'"
						." AND status!='D'"
					);
					
					if(!$hosts_paginas){
						$paginaOpcao = 'adicionar';
					}
				}
				
				// ===== Criar a nova página ou atualizar a página atual com do serviço.
				
				switch($paginaOpcao){
					case 'adicionar':
						// ===== Adicionar página do serviço.
						
						$id_hosts_paginas = pagina_adicionar(Array(
							'dados' => Array(
								'nome' => $servicos['nome'],
								'id_hosts_layouts' => $id_hosts_layouts,
								'caminho' => $servicos['caminho'],
								'tipo' => 'pagina',
								'html' => $html,
								'css' => $css,
								'modulo' => 'servicos-host',
								'modulo_id_registro' => $id_servicos,
								'sem_permissao' => '1',
								'template_padrao' => '1',
								'template_categoria' => 'servicos',
								'template_id' => $servicos['template_id'],
								'template_versao' => $template_versao,
							),
						));
						
						// ===== Atualizar o serviço e adicionar o id_hosts_paginas.
						
						banco_update
						(
							"id_hosts_paginas='".$id_hosts_paginas."'",
							$tabela['nome'],
							"WHERE ".$tabela['id_numerico']."='".$id_servicos."'"
						);
					break;
					case 'editar':
						// ===== Editar página do serviço.
						
						pagina_editar(Array(
							'id_hosts_paginas' => $servicos['id_hosts_paginas'],
							'dados' => Array(
								'nome' => (isset($nomeMudou) ? $servicos['nome'] : null),
								'caminho' => (isset($caminhoMudou) ? $servicos['caminho'] : null),
								'html' => $html,
								'css' => $css,
								'sem_permissao' => '1',
								'template_padrao' => '1',
								'template_categoria' => 'servicos',
								'template_id' => $servicos['template_id'],
								'template_versao' => $template_versao,
							),
						));
					break;
				}
			break;
			case 'status':
				if(isset($status)){
					// ===== Buscar dados do serviço.
					
					$servicos = banco_select(Array(
						'unico' => true,
						'tabela' => $tabela['nome'],
						'campos' => Array(
							'id_hosts_paginas',
						),
						'extra' => 
							"WHERE ".$tabela['id_numerico']."='".$id_servicos."'"
					));
					
					// ===== Alterar status da página.
					
					pagina_status(Array(
						'id_hosts_paginas' => $servicos['id_hosts_paginas'],
						'status' => $status,
					));
				}
			break;
			case 'excluir':
				// ===== Buscar dados do serviço.
				
				$servicos = banco_select(Array(
					'unico' => true,
					'tabela' => $tabela['nome'],
					'campos' => Array(
						'id_hosts_paginas',
					),
					'extra' => 
						"WHERE ".$tabela['id_numerico']."='".$id_servicos."'"
				));
				
				// ===== Alterar status da página.
				
				pagina_excluir(Array(
					'id_hosts_paginas' => $servicos['id_hosts_paginas'],
				));
			break;
		}
	}
}

// ===== Funções principais

function servicos_adicionar(){
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
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'paginaCaminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'min' => 1,
				)
			)
		));
		
		// ===== Definição do identificador do serviço.
		
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
		
		// ===== Decodificar o 'dadosServidor'.
		
		$dadosServidor = Array();
		
		if(isset($_REQUEST['dadosServidor'])){
			$dadosServidor = json_decode($_REQUEST['dadosServidor'],true);
		}
		
		// ===== Campos gerais
		
		banco_insert_name_campo('id_usuarios',$usuario['id_usuarios']);
		banco_insert_name_campo('id_hosts',$_GESTOR['host-id']);
		banco_insert_name_campo('id',$id);
		if(isset($_REQUEST['nome'])){ banco_insert_name_campo('nome',$_REQUEST['nome']); }
		
		if($_REQUEST['imagem'] && $_REQUEST['imagem'] != '-1'){ banco_insert_name_campo('id_hosts_arquivos_Imagem',$_REQUEST['imagem']); }
		
		if(isset($_REQUEST['descricao'])){ banco_insert_name_campo('descricao',pagina_variaveis_globais_mascarar(Array('valor' => $_REQUEST['descricao']))); }
		
		// ===== Caso o tipo simples, guardar o preço e quantidade.
		
		if($dadosServidor['simples']){
			if(isset($_REQUEST['preco'])){ banco_insert_name_campo('preco',formato_dado(Array('valor' => $_REQUEST['preco'],'tipo' => 'texto-para-float'))); }
			if(isset($_REQUEST['quantidade'])){ banco_insert_name_campo('quantidade',formato_dado(Array('valor' => $_REQUEST['quantidade'],'tipo' => 'texto-para-int'))); }
			
			if(isset($_REQUEST['gratuito'])){ banco_insert_name_campo('gratuito','1',true); }
		} else {
			// ===== Definir o tipo do serviço.
			
			banco_insert_name_campo('lotesVariacoes','1',true);
			$lotesVariacoes = true;
		}
		
		// ===== Campos de template.
		
		if(isset($_REQUEST['template_id'])){ banco_insert_name_campo('template_id',$_REQUEST['template_id']); }
		if(isset($_REQUEST['template_tipo'])){ banco_insert_name_campo('template_tipo',$_REQUEST['template_tipo']); }
		if(isset($_REQUEST['paginaCaminho'])){ banco_insert_name_campo('caminho',$_REQUEST['paginaCaminho']); }
		
		// ===== Campos comuns
		
		banco_insert_name_campo($modulo['tabela']['status'],'A');
		banco_insert_name_campo($modulo['tabela']['versao'],'1');
		banco_insert_name_campo($modulo['tabela']['data_criacao'],'NOW()',true);
		banco_insert_name_campo($modulo['tabela']['data_modificacao'],'NOW()',true);
		
		banco_insert_name
		(
			banco_insert_name_campos(),
			$modulo['tabela']['nome']
		);
		
		$id_servicos = banco_last_id();
		
		// ===== Caso exista lotes e variações, guardar os mesmos.
		
		if(isset($lotesVariacoes)){
			$lotes = $dadosServidor['lotes'];
			
			// ===== Incluir o lote no banco de dados.
			
			if(count($lotes) > 0)
			foreach($lotes as $lote){
				banco_insert_name_campo('id_hosts',$_GESTOR['host-id']);
				banco_insert_name_campo('id_usuarios',$usuario['id_usuarios']);
				banco_insert_name_campo('id_hosts_servicos',$id_servicos);
				
				banco_insert_name_campo('nome',$lote['nome']);
				banco_insert_name_campo('visibilidade',$lote['visibilidade']);
				
				if(existe($lote['dataInicio'])){ banco_insert_name_campo('dataInicio',formato_dado(Array('valor' => $lote['dataInicio'],'tipo' => 'datetime'))); }
				if(existe($lote['dataFim'])){ banco_insert_name_campo('dataFim',formato_dado(Array('valor' => $lote['dataFim'],'tipo' => 'datetime'))); }
				
				banco_insert_name
				(
					banco_insert_name_campos(),
					"hosts_servicos_lotes"
				);
				
				$id_hosts_servicos_lotes = banco_last_id();
				
				// ===== Incluir as variações no banco de dados.
				
				if(count($lote['variacoes']) > 0)
				foreach($lote['variacoes'] as $variacao){
					banco_insert_name_campo('id_hosts',$_GESTOR['host-id']);
					banco_insert_name_campo('id_usuarios',$usuario['id_usuarios']);
					banco_insert_name_campo('id_hosts_servicos',$id_servicos);
					banco_insert_name_campo('id_hosts_servicos_lotes',$id_hosts_servicos_lotes);
					
					banco_insert_name_campo('nome',$variacao['nome']);
					banco_insert_name_campo('preco',formato_dado(Array('valor' => $variacao['preco'],'tipo' => 'texto-para-float')));
					banco_insert_name_campo('quantidade',formato_dado(Array('valor' => $variacao['quantidade'],'tipo' => 'texto-para-int')));
					
					if($variacao['gratuito']){ banco_insert_name_campo('gratuito','1',true); }
					
					banco_insert_name
					(
						banco_insert_name_campos(),
						"hosts_servicos_variacoes"
					);
				}
			}
		}
		
		// ===== Criar a página do serviço baseado no template escolhido.
		
		servicos_pagina(Array(
			'opcao' => 'adicionar',
			'id_servicos' => $id_servicos,
		));
		
		// ===== Incluir os dados no host do cliente.
		
		gestor_incluir_biblioteca('api-cliente');
		
		$retorno = api_cliente_servicos(Array(
			'opcao' => 'adicionar',
			'id' => $id,
		));
		
		if(!$retorno['completed']){
			$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
			
			$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
		}
		
		// ===== Redirecionar para o registro incluído.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id);
	}
	
	// ===== Célula lote.
	
	$cel_nome = 'lote-cel'; $cel[$cel_nome] = pagina_celula($cel_nome);
	
	$celLote = $celLote1 = $cel[$cel_nome];
	
	// ===== Definições padrões.
	
	$nomeLotes = $modulo['lotesPadroes']['nomeLotes'];
	$idLotes = $modulo['lotesPadroes']['idLotes'];
	$nomeVariacao = $modulo['lotesPadroes']['nomeVariacao'];
	$idVariacao = $modulo['lotesPadroes']['idVariacao'];
	
	// ===== Lote conteiner 1.
	
	$loteNum = 1;
	
	// ===== Alterar variáveis do menu lotes.
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#lotes-menu-selected-nome#",$nomeLotes.' '.$loteNum);
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#lotes-menu-nome#",$nomeLotes.' '.$loteNum);
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#lotes-menu-value#",$idLotes.'-'.$loteNum);
	
	// ===== Remover botões de exclusão do lote 1.
	
	$cel_nome = 'btn-del'; $celLote1 = modelo_tag_in($celLote1,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	$cel_nome = 'var-btn-del'; $celLote1 = modelo_tag_in($celLote1,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	
	// ===== Alterar variáveis do conteiner lote.
	
	$loteNum = 1;
	$loteValue = $idLotes.'-'.$loteNum;
	$loteNome = $nomeLotes.' '.$loteNum;
	
	$celLote1 = modelo_var_troca($celLote1,"#lote-value#",$loteValue);
	$celLote1 = modelo_var_troca($celLote1,"#lote-num#",$loteNum);
	$celLote1 = modelo_var_troca($celLote1,"#lote-nome#",$loteNome);
	
	// ===== Alterar variáveis da variação.
	
	$variacaoNum = 1;
	$variacaoValue = $idVariacao.'-'.$variacaoNum;
	$variacaoNome = $nomeVariacao.' '.$variacaoNum;
	
	$celLote1 = modelo_var_troca($celLote1,"#variacao-value#",$variacaoValue);
	$celLote1 = modelo_var_troca($celLote1,"#variacao-num#",$variacaoNum);
	$celLote1 = modelo_var_troca($celLote1,"#variacao-nome#",$variacaoNome);
	
	$celLote1 = interface_formulario_campos(Array(
		'pagina' => $celLote1,
		'campos' => Array(
			Array(
				'tipo' => 'select',
				'id' => 'visibility',
				'nome' => 'visibility',
				'selectClass' => 'loteVisibilidade',
				'valor_selecionado' => 'sempre',
				'dados' => $modulo['lotesPadroes']['visibilidade'],
			),
		)
	));
	
	// ===== Incluir lote inicial na página.
	
	pagina_trocar_variavel_valor('<!-- lote-cel -->',$celLote1,true);
	
	// ===== Lote modelo definições.
	
	$celLote = interface_formulario_campos(Array(
		'pagina' => $celLote,
		'campos' => Array(
			Array(
				'tipo' => 'select',
				'id' => 'visibility',
				'nome' => 'visibility',
				'selectClass' => 'loteVisibilidade',
				'valor_selecionado' => 'sempre',
				'dados' => $modulo['lotesPadroes']['visibilidade'],
			),
		)
	));
	
	pagina_trocar_variavel_valor('#lotes-modelos#',$celLote,true);
	
	// ===== Alterar variáveis de controle.
	
	$dadosServidor = Array(
		'simples' => true,
		'definicoes' => Array(
			'nomeLotes' => $nomeLotes,
			'idLotes' => $idLotes,
			'nomeVariacao' => $nomeVariacao,
			'idVariacao' => $idVariacao,
		),
		'lotes' => Array(
			Array(
				'value' => $loteValue,
				'num' => $loteNum,
				'numVariacoes' => $variacaoNum,
				'nome' => $loteNome,
				'selected' => true,
				'variacoes' => Array(
					Array(
						'value' => $variacaoValue,
						'num' => $variacaoNum,
						'nome' => $variacaoNome,
						'preco' => '',
						'quantidade' => '',
					)
				),
			)
		),
	'resources' => [
		'pt-br' => [
			'layouts' => [],
			'pages' => [
			    [
			        'name' => 'Serviços',
			        'id' => 'servicos',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'servicos/',
			        'type' => 'system',
			        'option' => 'listar',
			        'root' => true,
			        'version' => '0',
			        'checksum' => [
			            'html' => '',
			            'css' => '',
			        ],
			    ],
			    [
			        'name' => 'Serviços - Adicionar',
			        'id' => 'servicos-adicionar',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'servicos/adicionar/',
			        'type' => 'system',
			        'option' => 'adicionar',
			        'version' => '1.1',
			        'checksum' => [
			            'html' => '631d2fa48c9b589430a46113a9690b52',
			            'css' => 'ad66d4a62cec6d638233bcbac8aed46c',
			        ],
			    ],
			    [
			        'name' => 'Serviços - Editar',
			        'id' => 'servicos-editar',
			        'layout' => 'layout-administrativo-do-gestor',
			        'path' => 'servicos/editar/',
			        'type' => 'system',
			        'option' => 'editar',
			        'version' => '1.1',
			        'checksum' => [
			            'html' => 'd9972fac28974f2fbcb8ed1045a34c6b',
			            'css' => 'ad66d4a62cec6d638233bcbac8aed46c',
			        ],
			    ],
			],
			'components' => [],
		],
	],
	);
	
	$dadosServidor = htmlentities(json_encode($dadosServidor));
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#dadosServidor#",$dadosServidor);
	
	// ===== Inclusão do jQuery-Mask-Plugin
	
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'jQuery-Mask-Plugin-v1.14.16/jquery.mask.min.js"></script>';
	
	// ===== Inclusão do TinyMCE
	
	$_GESTOR['javascript'][] = '<script src="https://cdn.tiny.cloud/1/puqfgloszrueuf7nkzrlzxqbc0qihojtiq46oikukhty0jw9/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>';
	
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
				),
				Array(
					'regra' => 'maior-ou-igual-a-zero',
					'campo' => 'preco',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-price-label')),
				),
				Array(
					'regra' => 'maior-ou-igual-a-zero',
					'campo' => 'quantidade',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-quantity-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio-verificar-campo',
					'campo' => 'caminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'identificador' => 'paginaCaminho',
					'regrasExtra' => Array(
						Array(
							'regra' => 'regexNecessary',
							'regex' => '/^.*\/$/gi',
							'regexNecessaryChars' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'path-necessary-chars')),
						)
					),
					'removerRegra' => Array(
						'minLength[3]'
					),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'variacaoNome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-variations-name-label')),
				),
				Array(
					'regra' => 'maior-ou-igual-a-zero',
					'campo' => 'variacaoPreco',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-variations-price-label')),
				),
				Array(
					'regra' => 'maior-ou-igual-a-zero',
					'campo' => 'variacaoQuantidade',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-variations-quantity-label')),
				),
				Array(
					'regra' => 'nao-vazio',
					'campo' => 'loteDataInicio',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-variations-startdate-label')),
				),
				Array(
					'regra' => 'nao-vazio',
					'campo' => 'loteDataFim',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-variations-enddate-label')),
				),
			),
			'campos' => Array(
				Array(
					'tipo' => 'imagepick-hosts',
					'id' => 'thumbnail',
					'nome' => 'imagem',
				),
				Array(
					'tipo' => 'templates-hosts',
					'id' => 'template',
					'nome' => 'template',
					'categoria_id' => 'servicos',
				),
			),
		)
	);
}

function servicos_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do 
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para editar.
	
	$camposBanco = Array(
		'id_hosts_servicos',
		'nome',
		'id_hosts_arquivos_Imagem',
		'preco',
		'quantidade',
		'quantidade_carrinhos',
		'quantidade_pedidos_pendentes',
		'quantidade_pedidos',
		'descricao',
		'template_id',
		'template_tipo',
		'caminho',
		'id_hosts_paginas',
		'lotesVariacoes',
		'gratuito',
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
		$usuario = gestor_usuario();
		
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
		
		// ===== Decodificar o 'dadosServidor'.
		
		$dadosServidor = Array();
		
		if(isset($_REQUEST['dadosServidor'])){
			$dadosServidor = json_decode($_REQUEST['dadosServidor'],true);
		}
		
		// ===== Valores padrões da tabela e regras para o campo nome
		
		$editar = Array(
			'tabela' => $modulo['tabela']['nome'],
			'extra' => "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D'"
				." AND id_hosts='".$_GESTOR['host-id']."'",
		);
		
		$campo_nome = "nome"; $request_name = 'nome'; $alteracoes_name = 'name'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; if(!isset($_REQUEST['_gestor-nao-alterar-id'])){$alterar_id = true;} $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name])); $nomeMudou = true;}
		
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
		
		$campo_nome = "id_hosts_arquivos_Imagem"; $request_name = 'imagem'; $alteracoes_name = 'thumbnail'; if($_REQUEST[$request_name] == '-1'){$_REQUEST[$request_name] = NULL;} if(banco_select_campos_antes($campo_nome) != $_REQUEST[$request_name]){$editar['dados'][] = $campo_nome."=".(isset($_REQUEST[$request_name]) ? "'" . banco_escape_field($_REQUEST[$request_name]) . "'" : "NULL"); $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => (isset($_REQUEST[$request_name]) ? banco_escape_field($_REQUEST[$request_name]) : NULL),'tabela' => Array(
				'nome' => 'hosts_arquivos',
				'campo' => 'nome',
				'id_numerico' => 'id_hosts_arquivos',
			));}
			
		$campo_nome = "descricao"; $request_name = $campo_nome; $alteracoes_name = 'description'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field(pagina_variaveis_globais_mascarar(Array('valor' => $_REQUEST[$request_name]))) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label');if(banco_select_campos_antes($campo_nome)){ $backups[] = Array('campo' => $campo_nome,'valor' => addslashes(banco_select_campos_antes($campo_nome)));}}
		
		// ===== Caso o tipo simples, atualizar o preço, a quantidade e gratuidade.
		
		if($dadosServidor['simples']){
			$campo_nome = "preco"; $request_name = $campo_nome; $alteracoes_name = 'price'; $formatar = 'texto-para-float'; $filtro = 'float-para-texto';
			if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? formato_dado(Array('valor' => $_REQUEST[$request_name],'tipo' => $formatar)) : NULL)){
				$editar['dados'][] = $campo_nome."='" . banco_escape_field(formato_dado(Array('valor' => $_REQUEST[$request_name],'tipo' => $formatar))) . "'"; 
				$alteracoes[] = Array(
					'campo' => 'form-'.$alteracoes_name.'-label',
					'valor_antes' => banco_select_campos_antes($campo_nome),
					'valor_depois' => banco_escape_field(formato_dado(Array('valor' => $_REQUEST[$request_name],'tipo' => $formatar))),
					'filtro' => $filtro
				);
			}
			
			$campo_nome = "quantidade"; $request_name = $campo_nome; $alteracoes_name = 'quantity'; $formatar = 'texto-para-int'; $filtro = 'int-para-texto';
			if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? formato_dado(Array('valor' => $_REQUEST[$request_name],'tipo' => $formatar)) : NULL)){
				$editar['dados'][] = $campo_nome."='" . banco_escape_field(formato_dado(Array('valor' => $_REQUEST[$request_name],'tipo' => $formatar))) . "'"; 
				$alteracoes[] = Array(
					'campo' => 'form-'.$alteracoes_name.'-label',
					'valor_antes' => banco_select_campos_antes($campo_nome),
					'valor_depois' => banco_escape_field(formato_dado(Array('valor' => $_REQUEST[$request_name],'tipo' => $formatar))),
					'filtro' => $filtro
				);
			}
			
			$campo_nome = "gratuito"; $request_name = $campo_nome; $alteracoes_name = 'free'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."=" . (isset($_REQUEST[$request_name]) ? '1' : 'NULL'); $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => (banco_select_campos_antes($campo_nome) ? 'Gratuito' : 'Pago'),'valor_depois' => (isset($_REQUEST[$request_name]) ? 'Gratuito' : 'Pago'));}
		} else {
			$lotesVariacoes = true;
		}
		
		$campo_nome = "lotesVariacoes"; $request_name = 'tipo'; $alteracoes_name = 'type'; if(banco_select_campos_antes($campo_nome) != ($_REQUEST[$request_name] == 'simples' ? NULL : '1')){$editar['dados'][] = $campo_nome."=" . ($_REQUEST[$request_name] == 'simples' ? 'NULL' : '1'); $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => (banco_select_campos_antes($campo_nome) ? 'Lotes / Variações' : 'Simples'),'valor_depois' => ($_REQUEST[$request_name] != 'simples' ? 'Lotes / Variações' : 'Simples'));}
		
		// ===== Dados do template.
		
		$campo_nome = "template_id"; $request_name = $campo_nome; $alteracoes_name = 'template'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label');}
		$campo_nome = "template_tipo"; $request_name = $campo_nome; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'";}
		
		// ===== Caminho da página do serviço.
		
		$campo_nome = "caminho"; $request_name = 'paginaCaminho'; $alteracoes_name = 'path'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name])); $caminhoMudou = banco_select_campos_antes($campo_nome);}
		
		// ===== Verificar lotes e variações e atualizar os mesmos.
		
		if(isset($lotesVariacoes)){
			$lotes = $dadosServidor['lotes'];
			
			// ===== Buscar lotes e variações no banco antes da atualização.
			
			$id_hosts_servicos = banco_select_campos_antes('id_hosts_servicos');
			
			$hosts_servicos_lotes = banco_select(Array(
				'tabela' => 'hosts_servicos_lotes',
				'campos' => Array(
					'id_hosts_servicos_lotes',
					'nome',
					'visibilidade',
					'dataInicio',
					'dataFim',
				),
				'extra' => 
					"WHERE id_hosts='".$_GESTOR['host-id']."'"
					." AND id_hosts_servicos='".$id_hosts_servicos."'"
					." ORDER BY id_hosts_servicos_lotes ASC"
			));
			
			$hosts_servicos_variacoes = banco_select(Array(
				'tabela' => 'hosts_servicos_variacoes',
				'campos' => Array(
					'id_hosts_servicos_variacoes',
					'id_hosts_servicos_lotes',
					'nome',
					'preco',
					'quantidade',
					'gratuito',
				),
				'extra' => 
					"WHERE id_hosts='".$_GESTOR['host-id']."'"
					." AND id_hosts_servicos='".$id_hosts_servicos."'"
					." ORDER BY id_hosts_servicos_variacoes ASC"
			));
			
			// ===== Variável de inclusão de alteração.
			
			$alteracoesTotal = '';
			$alteracaoFlagTotal = false;
			
			// ===== Incluir o lote no banco de dados.
			
			if(count($lotes) > 0)
			foreach($lotes as $lote){
				$alteracaoFlag = false;
				$alteracoesLotes = Array();
				
				// ===== Se não tiver ID definido é um novo lote e deve ser criado. Senão, é um lote já existente e deve ser verificado se houve ou não atualização do mesmo.
				
				if(!isset($lote['id'])){
					banco_insert_name_campo('id_hosts',$_GESTOR['host-id']);
					banco_insert_name_campo('id_usuarios',$usuario['id_usuarios']);
					banco_insert_name_campo('id_hosts_servicos',$id_hosts_servicos);
					
					banco_insert_name_campo('nome',$lote['nome']);
					banco_insert_name_campo('visibilidade',$lote['visibilidade']);
					
					if(existe($lote['dataInicio'])){ banco_insert_name_campo('dataInicio',formato_dado(Array('valor' => $lote['dataInicio'],'tipo' => 'datetime'))); }
					if(existe($lote['dataFim'])){ banco_insert_name_campo('dataFim',formato_dado(Array('valor' => $lote['dataFim'],'tipo' => 'datetime'))); }
					
					banco_insert_name
					(
						banco_insert_name_campos(),
						"hosts_servicos_lotes"
					);
					
					$id_hosts_servicos_lotes = banco_last_id();
					
					// ===== Alterações na variação.
					
					$alteracoesVariacao = Array();
					
					// ===== Incluir as variações no banco de dados.
					
					if(count($lote['variacoes']) > 0)
					foreach($lote['variacoes'] as $variacao){
						banco_insert_name_campo('id_hosts',$_GESTOR['host-id']);
						banco_insert_name_campo('id_usuarios',$usuario['id_usuarios']);
						banco_insert_name_campo('id_hosts_servicos',$id_hosts_servicos);
						banco_insert_name_campo('id_hosts_servicos_lotes',$id_hosts_servicos_lotes);
						
						banco_insert_name_campo('nome',$variacao['nome']);
						banco_insert_name_campo('preco',formato_dado(Array('valor' => $variacao['preco'],'tipo' => 'texto-para-float')));
						banco_insert_name_campo('quantidade',formato_dado(Array('valor' => $variacao['quantidade'],'tipo' => 'texto-para-int')));
						
						if($variacao['gratuito']){ banco_insert_name_campo('gratuito','1',true); }
						
						banco_insert_name
						(
							banco_insert_name_campos(),
							"hosts_servicos_variacoes"
						);
					}
					
					// ===== Alterações dos lotes dados.
					
					$alteracoesLotes['tipo'] = 'adicionou';
					$alteracoesLotes['loteNome'] = $lote['nome'];
				
					$alteracaoFlag = true;
				} else {
					// ===== Procurar o lote atual nos dados do banco de dados.
					
					$foundLote = false;
					
					if($hosts_servicos_lotes)
					foreach($hosts_servicos_lotes as $chave => $loteAntes){
						if($loteAntes['id_hosts_servicos_lotes'] == $lote['id']){
							$foundLote = true;
							$hosts_servicos_lotes[$chave]['processado'] = true;
							break;
						}
					}
					
					// ===== Se encontrou, verificar se houve atualização e atualizar caso necessário.
					
					if($foundLote){
						$alterouLote = false;
						$alteracaoLote = '';
						
						$alteracoesLotes['loteNome'] = $loteAntes['nome'];
						
						// ===== Criar o histórico de cada alteração no lote.
						
						foreach($loteAntes as $campo => $valor){
							$alterouLoteCampo = false;
							switch($campo){
								case 'nome':
									if($lote[$campo] != $valor){
										$valorDe = $valor;
										$valorPara = $lote[$campo];
										$alterouLoteCampo = true;
									}
								break;
								case 'visibilidade':
									if($lote[$campo] != $valor){
										foreach($modulo['lotesPadroes']['visibilidade'] as $visibilidade){
											if($visibilidade['valor'] == $valor){
												$valorDe = $visibilidade['texto'];
												break;
											}
										}
										foreach($modulo['lotesPadroes']['visibilidade'] as $visibilidade){
											if($visibilidade['valor'] == $lote[$campo]){
												$valorPara = $visibilidade['texto'];
												break;
											}
										}
										$alterouLoteCampo = true;
									}
								break;
								case 'dataInicio':
								case 'dataFim':
									if(isset($valor)){
										if(existe($lote[$campo])){ $dataDatetime = formato_dado(Array('valor' => $lote[$campo],'tipo' => 'datetime')); } else { $dataDatetime = ''; }
										
										if($valor != $dataDatetime){
											$valorDe = formato_dado(Array('valor' => $valor,'tipo' => 'dataHora'));
											//if(existe($lote[$campo])){ $valorPara = formato_dado(Array('valor' => $lote[$campo],'tipo' => 'dataHora')); } else { $valorPara = 'nenhuma'; }
											if(existe($lote[$campo])){ $valorPara = $lote[$campo]; } else { $valorPara = 'nenhuma'; }
											$alterouLoteCampo = true;
										}
									} else {
										if(existe($lote[$campo])){
											$valorDe = 'nenhuma';
											//$valorPara = formato_dado(Array('valor' => $lote[$campo],'tipo' => 'dataHora'));
											$valorPara = $lote[$campo];
											$alterouLoteCampo = true;
										}
									}
								break;
							}
							
							if($alterouLoteCampo){
								$alteracaoLote .= (existe($alteracaoLote) ? ', ' : '') . 'alterou <b>'.$campo.'</b> de <b>'.$valorDe.'</b> para <b>'.$valorPara.'</b>';
								$alterouLote = true;
							}
						}
						
						// ===== Dados do lote.
						
						$id_hosts_servicos_lotes = $loteAntes['id_hosts_servicos_lotes'];
						
						if($alterouLote){
							// ===== Atualizar no banco de dados o lote.
							
							banco_update_campo('nome',$lote['nome']);
							banco_update_campo('visibilidade',$lote['visibilidade']);
							
							if(existe($lote['dataInicio'])){ banco_update_campo('dataInicio',formato_dado(Array('valor' => $lote['dataInicio'],'tipo' => 'datetime'))); } else { banco_update_campo('dataInicio','NULL',true); }
							if(existe($lote['dataFim'])){ banco_update_campo('dataFim',formato_dado(Array('valor' => $lote['dataFim'],'tipo' => 'datetime'))); } else { banco_update_campo('dataFim','NULL',true); }
							
							banco_update_executar(
								'hosts_servicos_lotes',
								"WHERE id_hosts='".$_GESTOR['host-id']."'"
								." AND id_hosts_servicos='".$id_hosts_servicos."'"
								." AND id_hosts_servicos_lotes='".$id_hosts_servicos_lotes."'"
							);
							
							// ===== Alterações dos lotes dados.
							
							$alteracoesLotes['tipo'] = 'editou';
							$alteracoesLotes['lotes'] = $alteracaoLote;
							
							$alteracaoFlag = true;
						}
						
						// ===== Editar as variações no banco de dados.
						
						if(count($lote['variacoes']) > 0){
							$alteracaoVariacaoTodas = '';
							
							foreach($lote['variacoes'] as $variacao){
								// ===== Se não tiver ID definido é uma nova variação e deve ser criada. Senão, é uma variação já existente e deve ser verificado se houve ou não atualização da mesma.
								
								if(!isset($variacao['id'])){
									banco_insert_name_campo('id_hosts',$_GESTOR['host-id']);
									banco_insert_name_campo('id_usuarios',$usuario['id_usuarios']);
									banco_insert_name_campo('id_hosts_servicos',$id_hosts_servicos);
									banco_insert_name_campo('id_hosts_servicos_lotes',$id_hosts_servicos_lotes);
									
									banco_insert_name_campo('nome',$variacao['nome']);
									banco_insert_name_campo('preco',formato_dado(Array('valor' => $variacao['preco'],'tipo' => 'texto-para-float')));
									banco_insert_name_campo('quantidade',formato_dado(Array('valor' => $variacao['quantidade'],'tipo' => 'texto-para-int')));
									
									if($variacao['gratuito']){ banco_insert_name_campo('gratuito','1',true); }
									
									banco_insert_name
									(
										banco_insert_name_campos(),
										"hosts_servicos_variacoes"
									);
									
									// ===== Alterações dos dados das variações.
									
									$alteracaoVariacaoTodas .= (existe($alteracaoVariacaoTodas) ? '; ' : '') . '<b>adicionou</b> a variação: [<b>'.$variacao['nome'].'</b>, '.$variacao['quantidade'].', '.($variacao['gratuito'] ? 'Gratuito' : 'Pago').', R$ '.$variacao['preco'].']';
								} else {
									// ===== Procurar a variação atual nos dados do banco de dados.
									
									$foundVariacao = false;
									
									if($hosts_servicos_variacoes)
									foreach($hosts_servicos_variacoes as $chave => $variacaoAntes){
										if($variacaoAntes['id_hosts_servicos_variacoes'] == $variacao['id']){
											$foundVariacao = true;
											$hosts_servicos_variacoes[$chave]['processado'] = true;
											break;
										}
									}
									
									// ===== Se encontrou, verificar se houve atualização e atualizar caso necessário.
									
									if($foundVariacao){
										$alterouVariacao = false;
										$alteracaoVariacao = '';
										
										// ===== Criar o histórico de cada alteração na variação.
										
										foreach($variacaoAntes as $campo => $valor){
											$alterouVariacaoCampo = false;
											switch($campo){
												case 'nome':
													if($variacao[$campo] != $valor){
														$valorDe = $valor;
														$valorPara = $variacao[$campo];
														$alterouVariacaoCampo = true;
													}
												break;
												case 'preco':
													if(isset($valor)){
														if(existe($variacao[$campo])){ $preco = formato_dado(Array('valor' => $variacao[$campo],'tipo' => 'texto-para-float')); } else { $preco = '0'; }
														
														if($valor != $preco){
															$valorDe = formato_dado(Array('valor' => $valor,'tipo' => 'float-para-texto'));
															$valorPara = $variacao[$campo];
															$alterouVariacaoCampo = true;
														}
													} else {
														if(existe($variacao[$campo])){
															$valorDe = '0,00';
															$valorPara = $variacao[$campo];
															$alterouVariacaoCampo = true;
														}
													}
												break;
												case 'quantidade':
													if(isset($valor)){
														if(existe($variacao[$campo])){ $quantidade = formato_dado(Array('valor' => $variacao[$campo],'tipo' => 'texto-para-int')); } else { $quantidade = '0'; }
														
														if($valor != $quantidade){
															$valorDe = formato_dado(Array('valor' => $valor,'tipo' => 'int-para-texto'));
															$valorPara = $variacao[$campo];
															$alterouVariacaoCampo = true;
														}
													} else {
														if(existe($variacao[$campo])){
															$valorDe = '0';
															$valorPara = $variacao[$campo];
															$alterouVariacaoCampo = true;
														}
													}
												break;
												case 'gratuito':
													if(isset($valor)){
														if(!$variacao[$campo]){
															$valorDe = 'Gratuito';
															$valorPara = 'Pago';
															$alterouVariacaoCampo = true;
														}
													} else {
														if($variacao[$campo]){
															$valorDe = 'Pago';
															$valorPara = 'Gratuito';
															$alterouVariacaoCampo = true;
														}
													}
												break;
											}
											
											if($alterouVariacaoCampo){
												$alteracaoVariacao .= (existe($alteracaoVariacao) ? ', ' : '') . 'alterou <b>'.$campo.'</b> de <b>'.$valorDe.'</b> para <b>'.$valorPara.'</b>';
												$alterouVariacao = true;
											}
										}
										
										// ===== Caso haja alterações na variação, atualizar no banco de dados.
										
										if($alterouVariacao){
											// ===== Dados da variação.
											
											$id_hosts_servicos_variacoes = $variacaoAntes['id_hosts_servicos_variacoes'];
											
											// ===== Atualizar no banco de dados a variação.
											
											banco_update_campo('nome',$variacao['nome']);
											
											if(existe($variacao['preco'])){ banco_update_campo('preco',formato_dado(Array('valor' => $variacao['preco'],'tipo' => 'texto-para-float'))); } else { banco_update_campo('preco','NULL',true); }
											if(existe($variacao['quantidade'])){ banco_update_campo('quantidade',formato_dado(Array('valor' => $variacao['quantidade'],'tipo' => 'texto-para-int'))); } else { banco_update_campo('quantidade','NULL',true); }
											
											if($variacao['gratuito']){ banco_update_campo('gratuito','1',true); } else { banco_update_campo('gratuito','NULL',true); }
											
											banco_update_executar(
												'hosts_servicos_variacoes',
												"WHERE id_hosts='".$_GESTOR['host-id']."'"
												." AND id_hosts_servicos='".$id_hosts_servicos."'"
												." AND id_hosts_servicos_lotes='".$id_hosts_servicos_lotes."'"
												." AND id_hosts_servicos_variacoes='".$id_hosts_servicos_variacoes."'"
											);
											
											// ===== Alterações dos dados das variações.
											
											$alteracaoVariacaoTodas .= (existe($alteracaoVariacaoTodas) ? '; ' : '') . '<b>editou</b> a variação <b>'.$variacao['nome'].'</b>: ['.$alteracaoVariacao.']';
										}
									}
								}
							}
							
							if(existe($alteracaoVariacaoTodas)){
								$alteracoesLotes['tipo'] = 'editou';
								$alteracoesLotes['variacoes'] = $alteracaoVariacaoTodas;
								
								$alteracaoFlag = true;
							}
						}
					}
				}
				
				// ===== Caso haja alteração incluir no array de alterações.
				
				if($alteracaoFlag){
					// ===== Sinalizador de atualização de lotes e/ou variações para incluir nas alterações.
					
					$alteracaoFlagTotal = true;
					
					// ===== Tipo de alterações e montagem do txt a ser incluído.
					
					switch($alteracoesLotes['tipo']){
						case 'adicionou':
							// ===== Visibilidade do lote.
							
							$visibilidadeTxt = '';
							foreach($modulo['lotesPadroes']['visibilidade'] as $visibilidade){
								if($visibilidade['valor'] == $lote['visibilidade']){
									$visibilidadeTxt = $visibilidade['texto'];
									break;
								}
							}
							
							switch($lote['visibilidade']){
								case 'datainicio': $visibilidadeTxt .= ': '.$lote['dataInicio']; break;
								case 'datafim': $visibilidadeTxt .= ': '.$lote['dataFim']; break;
								case 'periodo': $visibilidadeTxt .= ': '.$lote['dataInicio'] . ' até ' . $lote['dataFim']; break;
							}
							
							$loteTxt = '<b>'.$alteracoesLotes['loteNome'].'</b>, '.$visibilidadeTxt;
							
							// ===== Variações do lote.
							
							$variacoesTxt = '';
							if(count($lote['variacoes']) > 0)
							foreach($lote['variacoes'] as $variacao){
								$variacoesTxt .= (existe($variacoesTxt) ? ', ' : '') . '[<b>'.$variacao['nome'].'</b>, '.$variacao['quantidade'].', '.($variacao['gratuito'] ? 'Gratuito' : 'Pago').', R$ '.$variacao['preco'].']';
							}
							
							// ===== Alteração txt a ser incluída.
						
							$alteracoesTxt = '<b>adicionou</b> o lote: ['.$loteTxt.'] e as variações: ('.$variacoesTxt.')';
						break;
						case 'editou':
							if(isset($alteracoesLotes['lotes']) && isset($alteracoesLotes['variacoes'])){
								$alteracoesTxt = '<b>editou</b> o lote <b>'.$alteracoesLotes['loteNome'].'</b>: ['.$alteracoesLotes['lotes'].'] e as variações: ('.$alteracoesLotes['variacoes'].')';
							} else if(isset($alteracoesLotes['lotes'])){
								$alteracoesTxt = '<b>editou</b> o lote <b>'.$alteracoesLotes['loteNome'].'</b>: ['.$alteracoesLotes['lotes'].']';
							} else if(isset($alteracoesLotes['variacoes'])){
								$alteracoesTxt = '<b>editou</b> do lote <b>'.$alteracoesLotes['loteNome'].'</b> as variações: ('.$alteracoesLotes['variacoes'].')';
							}
						break;
					}
					
					$alteracoesTotal .= (existe($alteracoesTotal) ? '; ' : '') . $alteracoesTxt;
				}
			}
			
			// ===== Excluir lotes não processados, ou seja, que foram excluídos na interface do cliente.
			
			if($hosts_servicos_lotes)
			foreach($hosts_servicos_lotes as $lotesParaDeletar){
				if(!$lotesParaDeletar['processado']){
					$id_hosts_servicos_lotes = $lotesParaDeletar['id_hosts_servicos_lotes'];
					
					banco_delete
					(
						"hosts_servicos_variacoes",
						"WHERE id_hosts='".$_GESTOR['host-id']."'"
						." AND id_hosts_servicos='".$id_hosts_servicos."'"
						." AND id_hosts_servicos_lotes='".$id_hosts_servicos_lotes."'"
					);
					banco_delete
					(
						"hosts_servicos_lotes",
						"WHERE id_hosts='".$_GESTOR['host-id']."'"
						." AND id_hosts_servicos='".$id_hosts_servicos."'"
						." AND id_hosts_servicos_lotes='".$id_hosts_servicos_lotes."'"
					);
					
					// ===== Incluir alterações na exclusão.
					
					$alteracoesTotal .= (existe($alteracoesTotal) ? '; ' : '') . '<b>excluiu</b> o lote: <b>'.$lotesParaDeletar['nome'].'</b>';
					
					// ===== Sinalizador de atualização de lotes e/ou variações para incluir nas alterações.
					
					$alteracaoFlagTotal = true;
				}
			}
			
			// ===== Excluir variações não processadas, ou seja, que foram excluídas na interface do cliente.
			
			if($hosts_servicos_variacoes)
			foreach($hosts_servicos_variacoes as $variacoesParaDeletar){
				if(!$variacoesParaDeletar['processado']){
					$id_hosts_servicos_lotes = $variacoesParaDeletar['id_hosts_servicos_lotes'];
					$id_hosts_servicos_variacoes = $variacoesParaDeletar['id_hosts_servicos_variacoes'];
					
					// ===== Deletar as variações no banco de dados.
					
					banco_delete
					(
						"hosts_servicos_variacoes",
						"WHERE id_hosts='".$_GESTOR['host-id']."'"
						." AND id_hosts_servicos='".$id_hosts_servicos."'"
						." AND id_hosts_servicos_lotes='".$id_hosts_servicos_lotes."'"
						." AND id_hosts_servicos_variacoes='".$id_hosts_servicos_variacoes."'"
					);
					
					// ===== Lote nome da variação.
					
					$loteNome = '';
					if($hosts_servicos_lotes)
					foreach($hosts_servicos_lotes as $hosts_servicos_lote){
						if($hosts_servicos_lote['id_hosts_servicos_lotes'] == $id_hosts_servicos_lotes){
							$loteNome = $hosts_servicos_lote['nome'];
							break;
						}
					}
					
					// ===== Não incluir variações de lotes excluídos.
					
					$loteExcluido = false;
					if($hosts_servicos_lotes)
					foreach($hosts_servicos_lotes as $lotesParaDeletar){
						if(!$lotesParaDeletar['processado'] && $id_hosts_servicos_lotes == $lotesParaDeletar['id_hosts_servicos_lotes']){
							$loteExcluido = true;
							break;
						}
					}
					
					if($loteExcluido){
						continue;
					}
					
					// ===== Incluir alterações na exclusão.
					
					$alteracoesTotal .= (existe($alteracoesTotal) ? '; ' : '') . '<b>excluiu</b> a variação: <b>'.$variacoesParaDeletar['nome'].'</b> do lote: <b>'.$loteNome.'</b>';
					
					// ===== Sinalizador de atualização de lotes e/ou variações para incluir nas alterações.
					
					$alteracaoFlagTotal = true;
				}
			}
			
			// ===== Incluir as alterações se houverem.
			
			if($alteracaoFlagTotal){
				$alteracoes[] = Array(
					'alteracao' => 'lotes-variacoes',
					'alteracao_txt' => 'alterou <b>Lotes e/ou Variações</b>: '.$alteracoesTotal,
				);
				
				// ===== Sinalizador de atualização de lotes e/ou variações.
				
				$lotesVariacoesAtualizado = true;
			}
		}
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if(isset($editar['dados']) || isset($lotesVariacoesAtualizado)){
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
			
			// ===== Se mudou o caminho, criar página 301 do caminho
			
			if(isset($caminhoMudou)){
				$id_hosts_paginas_301 = pagina_301(Array(
					'caminho' => $caminhoMudou,
					'id_hosts_paginas' => banco_select_campos_antes('id_hosts_paginas'),
				));
			}
			
			// ===== Modificar a página do serviço baseado no template escolhido.
		
			servicos_pagina(Array(
				'opcao' => 'editar',
				'id_servicos' => banco_select_campos_antes($modulo['tabela']['id_numerico']),
				'nomeMudou' => (isset($nomeMudou) ? true : null),
				'caminhoMudou' => (isset($caminhoMudou) ? true : null),
			));
			
			// ===== Incluir os dados no host do cliente.
			
			gestor_incluir_biblioteca('api-cliente');
			
			$retorno = api_cliente_servicos(Array(
				'opcao' => 'editar',
				'id' => $id,
				'caminhoMudou' => (isset($caminhoMudou) ? Array( 
					'id_hosts_paginas_301' => $id_hosts_paginas_301,
					'caminho' => $caminhoMudou,
				) : NULL),
			));
			
			if(!$retorno['completed']){
				$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
				
				$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
				
				interface_alerta(Array(
					'redirect' => true,
					'msg' => $alerta
				));
			}
		}
		
		// ===== Reler URL.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.(isset($id_novo) ? $id_novo : $id));
	}
	
	// ===== Inclusão do jQuery-Mask-Plugin
	
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'jQuery-Mask-Plugin-v1.14.16/jquery.mask.min.js"></script>';
	
	// ===== Inclusão do TinyMCE
	
	$_GESTOR['javascript'][] = '<script src="https://cdn.tiny.cloud/1/puqfgloszrueuf7nkzrlzxqbc0qihojtiq46oikukhty0jw9/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>';
	
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
		$id_hosts_servicos = (isset($retorno_bd['id_hosts_servicos']) ? $retorno_bd['id_hosts_servicos'] : '');
		$nome = (isset($retorno_bd['nome']) ? $retorno_bd['nome'] : '');
		$id_hosts_arquivos = (isset($retorno_bd['id_hosts_arquivos_Imagem']) ? $retorno_bd['id_hosts_arquivos_Imagem'] : '');
		$preco = (isset($retorno_bd['preco']) ? $retorno_bd['preco'] : '');
		$quantidade = (isset($retorno_bd['quantidade']) ? $retorno_bd['quantidade'] : '');
		$descricao = (isset($retorno_bd['descricao']) ? $retorno_bd['descricao'] : '');
		$template_id = (isset($retorno_bd['template_id']) ? $retorno_bd['template_id'] : '');
		$template_tipo = (isset($retorno_bd['template_tipo']) ? $retorno_bd['template_tipo'] : '');
		$caminho = (isset($retorno_bd['caminho']) ? $retorno_bd['caminho'] : '');
		$quantidade_carrinhos = (isset($retorno_bd['quantidade_carrinhos']) ? $retorno_bd['quantidade_carrinhos'] : '0');
		$quantidade_pedidos_pendentes = (isset($retorno_bd['quantidade_pedidos_pendentes']) ? $retorno_bd['quantidade_pedidos_pendentes'] : '0');
		$quantidade_pedidos = (isset($retorno_bd['quantidade_pedidos']) ? $retorno_bd['quantidade_pedidos'] : '0');
		$lotesVariacoes = (isset($retorno_bd['lotesVariacoes']) ? 'lotes-variacoes' : 'simples');
		$gratuito = (isset($retorno_bd['gratuito']) ? true : false);
		
		// ===== URL completa no caminho.
		
		gestor_incluir_biblioteca('host');
		$urlFull = host_url(Array(
			'opcao' => 'full',
		));
		
		// ===== Desmascarar e trocar depois para as variáveis globais não serem subistituídas no gestor.
		
		$descricao = pagina_variaveis_globais_desmascarar(Array('valor' => $descricao));
		
		$variaveisTrocarDepois['descricao'] = $descricao;
		
		// ===== Aplicar formato de dados para quantidade e preço.
		
		$quantidade = formato_dado(Array('valor' => $quantidade,'tipo' => 'int-para-texto'));
		$preco = formato_dado(Array('valor' => $preco,'tipo' => 'float-para-texto'));
		
		// ===== Subistituir as variáveis na página.
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome#',$nome);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#quantidade#',$quantidade);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#preco#',$preco);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#caminho#',$caminho);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#url#',$urlFull.$caminho);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#quantidade-carrinhos#',$quantidade_carrinhos);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#quantidade-pedidos-pendentes#',$quantidade_pedidos_pendentes);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#quantidade-pedidos#',$quantidade_pedidos);
		
		if($gratuito){
			$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#gratuito-checked#','checked');
		} else {
			$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],' checked="#gratuito-checked#"','');
		}
		
		// ===== Pegar as células do menu, lotes e variações.
		
		$cel_nome = 'lote-menu-cel'; $cel[$cel_nome] = pagina_celula($cel_nome);
		$cel_nome = 'variacao-cel'; $cel[$cel_nome] = pagina_celula($cel_nome);
		$cel_nome = 'lote-cel'; $cel[$cel_nome] = pagina_celula($cel_nome);
		
		// ===== Definições padrões.
		
		$nomeLotes = $modulo['lotesPadroes']['nomeLotes'];
		$idLotes = $modulo['lotesPadroes']['idLotes'];
		$nomeVariacao = $modulo['lotesPadroes']['nomeVariacao'];
		$idVariacao = $modulo['lotesPadroes']['idVariacao'];
		
		// ===== Lotes e Variações.
		
		if($lotesVariacoes == 'simples'){
			pagina_trocar_variavel_valor('#tipo-simples#','blue active',true);
			pagina_trocar_variavel_valor('#tipo-lotes#','',true);
			
			pagina_trocar_variavel_valor('#cont-simples#','',true);
			pagina_trocar_variavel_valor('#cont-lotes#','escondido',true);
		} else {
			pagina_trocar_variavel_valor('#tipo-simples#','',true);
			pagina_trocar_variavel_valor('#tipo-lotes#','blue active',true);
			
			pagina_trocar_variavel_valor('#cont-simples#','escondido',true);
			pagina_trocar_variavel_valor('#cont-lotes#','',true);
		}
		
		// ===== Lotes do serviço no banco de dados.
		
		$hosts_servicos_lotes = banco_select(Array(
			'tabela' => 'hosts_servicos_lotes',
			'campos' => Array(
				'id_hosts_servicos_lotes',
				'nome',
				'visibilidade',
				'dataInicio',
				'dataFim',
			),
			'extra' => 
				"WHERE id_hosts='".$_GESTOR['host-id']."'"
				." AND id_hosts_servicos='".$id_hosts_servicos."'"
				." ORDER BY id_hosts_servicos_lotes ASC"
		));
		
		// ===== Varrer os lotes.
		
		$loteValue = 1;
		
		if($hosts_servicos_lotes)
		foreach($hosts_servicos_lotes as $hosts_servicos_lote){
			// ===== Valores do lote.
			
			$loteNum = $loteValue;
			$loteNome = $hosts_servicos_lote['nome'];
			$loteID = $hosts_servicos_lote['id_hosts_servicos_lotes'];
			$loteVisibilidade = $hosts_servicos_lote['visibilidade'];
			
			if($hosts_servicos_lote['dataInicio']){ $loteDataInicio = formato_dado(Array('valor' => $hosts_servicos_lote['dataInicio'],'tipo' => 'dataHora')); $loteDataInicioDatetime = $hosts_servicos_lote['dataInicio']; } else { $loteDataInicio = '';$loteDataInicioDatetime = ''; }
			if($hosts_servicos_lote['dataFim']){ $loteDataFim = formato_dado(Array('valor' => $hosts_servicos_lote['dataFim'],'tipo' => 'dataHora')); $loteDataFimDatetime = $hosts_servicos_lote['dataFim']; } else { $loteDataFim = '';$loteDataFimDatetime = ''; }
			
			// ===== Incluir lote no menu.
			
			if(!isset($lote1)){
				pagina_trocar_variavel_valor('#lotes-menu-selected-nome#',$loteNome,true);
			}
			
			$cel_aux = $cel['lote-menu-cel'];
			
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lotes-menu-value#",$idLotes.'-'.$loteValue,true);
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lotes-menu-nome#",$loteNome,true);
			
			pagina_celula_incluir('lote-menu-cel',$cel_aux);
			
			// ===== Célula do lote.
			
			$cel_aux = $cel['lote-cel'];
			
			// ===== Valores do lote atual.
			
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-value#",$idLotes.'-'.$loteValue,true);
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-num#",$loteNum,true);
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-nome#",$loteNome,true);
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-id#",$loteID,true);
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-startdate#",$loteDataInicio,true);
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-enddate#",$loteDataFim,true);
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-rangestart#",$loteDataInicio,true);
			$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-rangeend#",$loteDataFim,true);
			
			// ===== Campos data conforme visibilidade.
			
			switch($loteVisibilidade){
				case 'datainicio':
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-cont-data-inicio#",'',true);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-cont-data-fim#",'escondido',true);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-cont-periodo#",'escondido',true);
				break;
				case 'datafim':
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-cont-data-inicio#",'escondido',true);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-cont-data-fim#",'',true);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-cont-periodo#",'escondido',true);
				break;
				case 'periodo':
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-cont-data-inicio#",'escondido',true);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-cont-data-fim#",'escondido',true);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-cont-periodo#",'',true);
				break;
				default:
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-cont-data-inicio#",'escondido',true);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-cont-data-fim#",'escondido',true);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"#lote-cont-periodo#",'escondido',true);
			}
			
			// ===== Remover o botão excluir do primeiro lote.
			
			if(!isset($lote1)){
				$cel_nome = 'btn-del'; $cel_aux = modelo_tag_in($cel_aux,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			}
			
			// ===== Select de visibilidade.
			
			$cel_aux = interface_formulario_campos(Array(
				'pagina' => $cel_aux,
				'campos' => Array(
					Array(
						'tipo' => 'select',
						'id' => 'visibility',
						'nome' => 'visibility',
						'selectClass' => 'loteVisibilidade',
						'valor_selecionado' => $loteVisibilidade,
						'dados' => $modulo['lotesPadroes']['visibilidade'],
					),
				)
			));
			
			// ===== Pegar variações do lote no banco de dados.
			
			$hosts_servicos_variacoes = banco_select(Array(
				'tabela' => 'hosts_servicos_variacoes',
				'campos' => Array(
					'id_hosts_servicos_variacoes',
					'id_hosts_servicos_lotes',
					'nome',
					'preco',
					'quantidade',
					'gratuito',
				),
				'extra' => 
					"WHERE id_hosts='".$_GESTOR['host-id']."'"
					." AND id_hosts_servicos='".$id_hosts_servicos."'"
					." ORDER BY id_hosts_servicos_variacoes ASC"
			));
			
			// ===== Varrer variações do lote.
			
			$variacaoValue = 1;
			$variacoes = Array();
			
			if($hosts_servicos_variacoes)
			foreach($hosts_servicos_variacoes as $hosts_servicos_variacao){
				if($hosts_servicos_lote['id_hosts_servicos_lotes'] == $hosts_servicos_variacao['id_hosts_servicos_lotes']){
					// ===== Variação dados principais.
					
					$variacaoNum = $variacaoValue;
					$variacaoID = $hosts_servicos_variacao['id_hosts_servicos_variacoes'];
					$variacaoNome = $hosts_servicos_variacao['nome'];
					$variacaoGratuito = (isset($hosts_servicos_variacao['gratuito']) ? true : false);
					
					if($hosts_servicos_variacao['preco']){ $variacaoPreco = formato_dado(Array('valor' => $hosts_servicos_variacao['preco'],'tipo' => 'float-para-texto')); } else { $variacaoPreco = '0'; }
					if($hosts_servicos_variacao['quantidade']){ $variacaoQuantidade = formato_dado(Array('valor' => $hosts_servicos_variacao['quantidade'],'tipo' => 'int-para-texto')); } else { $variacaoQuantidade = '0'; }
					
					
					// ===== Célula da variação.
					
					$cel_aux_2 = $cel['variacao-cel'];
					
					// ===== Trocar dados da célula da variação atual.
					
					$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,"#variacao-value#",$idVariacao.'-'.$variacaoValue,true);
					$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,"#variacao-num#",$variacaoNum,true);
					$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,"#variacao-id#",$variacaoID,true);
					$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,"#variacao-nome#",$variacaoNome,true);
					$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,"#variacao-preco#",$variacaoPreco,true);
					$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,"#variacao-quantidade#",$variacaoQuantidade,true);
					
					if($variacaoGratuito){
						$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,"#variacao-gratuito-checked#",'checked',true);
					} else {
						$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,' checked="#variacao-gratuito-checked#"','',true);
					}
					
					// ===== Remover o botão excluir da primeira variação.
					
					if($variacaoValue == 1){
						$cel_nome = 'var-btn-del'; $cel_aux_2 = modelo_tag_in($cel_aux_2,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
					}
					
					// ===== Incluir as variações no lote atual.
					
					$cel_aux = modelo_var_in($cel_aux,'<!-- variacao-cel -->',$cel_aux_2);
					
					// ===== Incluir a variação no array.
					
					$variacoes[] = Array(
						'id' => $variacaoID,
						'value' => $variacaoValue,
						'num' => $variacaoNum,
						'nome' => $variacaoNome,
						'preco' => $variacaoPreco,
						'quantidade' => $variacaoQuantidade,
						'gratuito' => $variacaoGratuito,
					);
					
					// ===== Operações finais da variação.
					
					$variacaoValue++;
				}
			}
			
			$cel_aux = modelo_var_in($cel_aux,'<!-- variacao-cel -->','');
			
			// ===== Incluir o lote no conteiner.
			
			pagina_celula_incluir('lote-cel',$cel_aux);
			
			// ===== Incluir lote no array.
			
			$lotes[] = Array(
				'id' => $loteID,
				'value' => $idLotes.'-'.$loteValue,
				'num' => $loteNum,
				'numVariacoes' => $variacaoNum,
				'nome' => $loteNome,
				'selected' => (!isset($lote1) ? true : false),
				'dataInicio' => $loteDataInicio,
				'dataFim' => $loteDataFim,
				'dataInicioDatetime' => $loteDataInicioDatetime,
				'dataFimDatetime' => $loteDataFimDatetime,
				'variacoes' => $variacoes,
			);
			
			// ===== Operações finais no lote.
			
			if(!isset($lote1)){
				$lote1 = true;
			}
			
			$loteValue++;
		}
		
		pagina_celula_incluir('lote-cel','');
		
		pagina_trocar_variavel_valor('#tipo-value#',$lotesVariacoes,true);
		
		// ===== Célula lote.
	
		$celLote = $celLote1 = $cel['lote-cel'];
		
		$variacaoCel = $cel['variacao-cel'];
		
		$variacaoCel = pagina_celula_trocar_variavel_valor($variacaoCel,' data-id="#variacao-id#"','',true);
		
		// ===== Modificações da célula do lote dos modelos.
		
		$celLote = modelo_var_in($celLote,'<!-- variacao-cel -->',$variacaoCel);
		$celLote = pagina_celula_trocar_variavel_valor($celLote,' checked="#variacao-gratuito-checked#"','',true);
		
		$celLote = pagina_celula_trocar_variavel_valor($celLote,"#lote-cont-data-inicio#",'escondido',true);
		$celLote = pagina_celula_trocar_variavel_valor($celLote,"#lote-cont-data-fim#",'escondido',true);
		$celLote = pagina_celula_trocar_variavel_valor($celLote,"#lote-cont-periodo#",'escondido',true);
		
		$celLote = pagina_celula_trocar_variavel_valor($celLote,"#lote-startdate#",'',true);
		$celLote = pagina_celula_trocar_variavel_valor($celLote,"#lote-enddate#",'',true);
		$celLote = pagina_celula_trocar_variavel_valor($celLote,"#lote-rangestart#",'',true);
		$celLote = pagina_celula_trocar_variavel_valor($celLote,"#lote-rangeend#",'',true);
		
		// ===== Lote modelo definições.
		
		$celLote = interface_formulario_campos(Array(
			'pagina' => $celLote,
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'visibility',
					'nome' => 'visibility',
					'selectClass' => 'loteVisibilidade',
					'valor_selecionado' => 'sempre',
					'dados' => $modulo['lotesPadroes']['visibilidade'],
				),
			)
		));
		
		pagina_trocar_variavel_valor('#lotes-modelos#',$celLote,true);
		
		// ===== Alterar variáveis de controle.
		
		if(!isset($lotes)){
			$celLote1 = modelo_var_in($celLote1,'<!-- variacao-cel -->',$variacaoCel);
			$celLote1 = pagina_celula_trocar_variavel_valor($celLote1,' checked="#variacao-gratuito-checked#"','',true);
			
			$celLote1 = pagina_celula_trocar_variavel_valor($celLote1,"#lote-cont-data-inicio#",'escondido',true);
			$celLote1 = pagina_celula_trocar_variavel_valor($celLote1,"#lote-cont-data-fim#",'escondido',true);
			$celLote1 = pagina_celula_trocar_variavel_valor($celLote1,"#lote-cont-periodo#",'escondido',true);
			
			$celLote1 = pagina_celula_trocar_variavel_valor($celLote1,"#lote-startdate#",'',true);
			$celLote1 = pagina_celula_trocar_variavel_valor($celLote1,"#lote-enddate#",'',true);
			$celLote1 = pagina_celula_trocar_variavel_valor($celLote1,"#lote-rangestart#",'',true);
			$celLote1 = pagina_celula_trocar_variavel_valor($celLote1,"#lote-rangeend#",'',true);
			
			// ===== Lote conteiner 1.
			
			$loteNum = 1;
			
			// ===== Alterar variáveis do menu lotes.
			
			$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#lotes-menu-selected-nome#",$nomeLotes.' '.$loteNum);
			$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#lotes-menu-nome#",$nomeLotes.' '.$loteNum);
			$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#lotes-menu-value#",$idLotes.'-'.$loteNum);
			
			// ===== Remover botões de exclusão do lote 1.
			
			$cel_nome = 'btn-del'; $celLote1 = modelo_tag_in($celLote1,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			$cel_nome = 'var-btn-del'; $celLote1 = modelo_tag_in($celLote1,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			
			// ===== Alterar variáveis do conteiner lote.
			
			$loteNum = 1;
			$loteValue = $idLotes.'-'.$loteNum;
			$loteNome = $nomeLotes.' '.$loteNum;
			
			$celLote1 = modelo_var_troca($celLote1,"#lote-value#",$loteValue);
			$celLote1 = modelo_var_troca($celLote1,"#lote-num#",$loteNum);
			$celLote1 = modelo_var_troca($celLote1,"#lote-nome#",$loteNome);
			
			// ===== Alterar variáveis da variação.
			
			$variacaoNum = 1;
			$variacaoValue = $idVariacao.'-'.$variacaoNum;
			$variacaoNome = $nomeVariacao.' '.$variacaoNum;
			
			$celLote1 = modelo_var_troca($celLote1,"#variacao-value#",$variacaoValue);
			$celLote1 = modelo_var_troca($celLote1,"#variacao-num#",$variacaoNum);
			$celLote1 = modelo_var_troca($celLote1,"#variacao-nome#",$variacaoNome);
			
			$celLote1 = interface_formulario_campos(Array(
				'pagina' => $celLote1,
				'campos' => Array(
					Array(
						'tipo' => 'select',
						'id' => 'visibility',
						'nome' => 'visibility',
						'selectClass' => 'loteVisibilidade',
						'valor_selecionado' => 'sempre',
						'dados' => $modulo['lotesPadroes']['visibilidade'],
					),
				)
			));
			
			// ===== Incluir lote inicial na página.
			
			pagina_trocar_variavel_valor('<!-- lote-cel -->',$celLote1,true);
			
			// ===== Valor inicial caso não seja um lote.
			
			$lotes = Array(
				Array(
					'value' => $loteValue,
					'num' => $loteNum,
					'numVariacoes' => $variacaoNum,
					'nome' => $loteNome,
					'selected' => true,
					'variacoes' => Array(
						Array(
							'value' => $variacaoValue,
							'num' => $variacaoNum,
							'nome' => $variacaoNome,
							'preco' => '',
							'quantidade' => '',
						)
					),
				)
			);
		}
		
		$dadosServidor = Array(
			'simples' => ($lotesVariacoes == 'simples' ? true : false),
			'definicoes' => Array(
				'nomeLotes' => $nomeLotes,
				'idLotes' => $idLotes,
				'nomeVariacao' => $nomeVariacao,
				'idVariacao' => $idVariacao,
			),
			'lotes' => $lotes,
		);
		
		$dadosServidor = htmlentities(json_encode($dadosServidor));
		
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#dadosServidor#",$dadosServidor);
		
		// ===== Popular os metaDados
		
		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');
		
		if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['versao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-version')),'dado' => $retorno_bd[$modulo['tabela']['versao']]); }
		if(isset($retorno_bd[$modulo['tabela']['status']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status')),'dado' => ($retorno_bd[$modulo['tabela']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-active')).'</b></div>' : '').($retorno_bd[$modulo['tabela']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-inactive')).'</b></div>' : '')); }
	} else {
		gestor_redirecionar_raiz();
	}
	
	// ===== Interface editar finalizar opções
	
	$_GESTOR['interface']['editar']['finalizar'] = Array(
		'id' => $id,
		'metaDados' => $metaDados,
		'variaveisTrocarDepois' => $variaveisTrocarDepois,
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
				),
				Array(
					'regra' => 'maior-ou-igual-a-zero',
					'campo' => 'preco',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-price-label')),
				),
				Array(
					'regra' => 'maior-ou-igual-a-zero',
					'campo' => 'quantidade',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-quantity-label')),
				),
				Array(
					'regra' => 'texto-obrigatorio-verificar-campo',
					'campo' => 'caminho',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')),
					'identificador' => 'paginaCaminho',
					'regrasExtra' => Array(
						Array(
							'regra' => 'regexNecessary',
							'regex' => '/^.*\/$/gi',
							'regexNecessaryChars' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'path-necessary-chars')),
						)
					),
					'removerRegra' => Array(
						'minLength[3]'
					),
				),
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'variacaoNome',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-variations-name-label')),
				),
				Array(
					'regra' => 'maior-ou-igual-a-zero',
					'campo' => 'variacaoPreco',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-variations-price-label')),
				),
				Array(
					'regra' => 'maior-ou-igual-a-zero',
					'campo' => 'variacaoQuantidade',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-variations-quantity-label')),
				),
				Array(
					'regra' => 'nao-vazio',
					'campo' => 'loteDataInicio',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-variations-startdate-label')),
				),
				Array(
					'regra' => 'nao-vazio',
					'campo' => 'loteDataFim',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-variations-enddate-label')),
				),
			),
			'campos' => Array(
				Array(
					'tipo' => 'imagepick-hosts',
					'id' => 'thumbnail',
					'nome' => 'imagem',
					'id_hosts_arquivos' => $id_hosts_arquivos,
				),
				Array(
					'tipo' => 'templates-hosts',
					'id' => 'template',
					'nome' => 'template',
					'categoria_id' => 'servicos',
					'template_id' => $template_id,
					'template_tipo' => $template_tipo,
				)
			),
		)
	);
}

function servicos_status(){
	global $_GESTOR;
	
	$id = $_GESTOR['modulo-registro-id'];
	$status = $_GESTOR['modulo-registro-status'];
	
	$tabela = $_GESTOR['modulo#'.$_GESTOR['modulo-id']]['tabela'];
	
	$resultado = banco_select(Array(
		'unico' => true,
		'tabela' => $tabela['nome'],
		'campos' => Array(
			$tabela['id_numerico'],
		),
		'extra' => 
			"WHERE ".$tabela['id']."='".$id."'"
			." AND ".$tabela['status']."!='D'"
	));
	
	servicos_pagina(Array(
		'opcao' => 'status',
		'id_servicos' => $resultado[$tabela['id_numerico']],
		'status' => $status,
	));
	
	gestor_incluir_biblioteca('api-cliente');
	
	$retorno = api_cliente_servicos(Array(
		'opcao' => 'status',
		'id' => $id,
	));
	
	if(!$retorno['completed']){
		$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
		
		$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => $alerta
		));
	}
}

function servicos_excluir(){
	global $_GESTOR;
	
	$id_numerico = $_GESTOR['modulo-registro-id-numerico'];
	
	servicos_pagina(Array(
		'opcao' => 'excluir',
		'id_servicos' => $id_numerico,
	));
	
	gestor_incluir_biblioteca('api-cliente');
	
	$retorno = api_cliente_servicos(Array(
		'opcao' => 'excluir',
		'id_numerico' => $id_numerico,
	));
	
	if(!$retorno['completed']){
		$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-api-client-error'));
		
		$alerta = modelo_var_troca($alerta,"#error-msg#",$retorno['error-msg']);
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => $alerta
		));
	}
}

function servicos_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'status':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'callbackFunction' => 'servicos_status',
			);
		break;
		case 'excluir':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'callbackFunction' => 'servicos_excluir',
			);
		break;
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'nome',
						'quantidade_carrinhos',
						'quantidade_pedidos_pendentes',
						'quantidade_pedidos',
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
							'id' => 'quantidade_carrinhos',
							'className' => 'dt-head-center',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'menu-quantity-cart')),
							'formatar' => Array(
								'valor_senao_existe' => '0',
							)
						),
						Array(
							'id' => 'quantidade_pedidos_pendentes',
							'className' => 'dt-head-center',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'menu-quantity-pre-order')),
							'formatar' => Array(
								'valor_senao_existe' => '0',
							)
						),
						Array(
							'id' => 'quantidade_pedidos',
							'className' => 'dt-head-center',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'menu-quantity-order')),
							'formatar' => Array(
								'valor_senao_existe' => '0',
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

function servicos_ajax_opcao(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
	);
}

// ==== Start

function servicos_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': servicos_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		servicos_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'adicionar': servicos_adicionar(); break;
			case 'editar': servicos_editar(); break;
		}
		
		interface_finalizar();
	}
}

servicos_start();

?>