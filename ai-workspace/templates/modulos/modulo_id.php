<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'modulo_id';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/modulo_id.json'), true);

// ===== Interfaces Auxiliares

function modulo_id_interface_auxiliar(){
	
}

// ===== Interfaces Principais

function modulo_id_adicionar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']]; // Configurações do módulo definido no `RAIZ_MÓDULO/modulo_id.json`
	
	// ===== Gravar registro no Banco
	
	if(isset($_GESTOR['adicionar-banco'])){
		$usuario = gestor_usuario(); // Dados do usuário logado no momento.
		
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio', // texto-obrigatorio | selecao-obrigatorio | email-obrigatorio
					'campo' => 'name-do-campo-post-ou-get', // Campo POST ou GET do formulário.
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'id-variavel-nome')), // Label do campo.
				),
			)
		));
		
		// ===== Definição do identificador
		
		$campos = null;
		$campo_sem_aspas_simples = false;
		
		$id = banco_identificador(Array( // Cria o `id` único dentro da tabela alvo deste módulo baseado no campo recebido neste caso `nome`.
			'id' => banco_escape_field($_REQUEST["nome"]),
			'tabela' => Array(
				'nome' => $modulo['tabela']['nome'],
				'campo' => $modulo['tabela']['id'],
				'id_nome' => $modulo['tabela']['id_numerico'],
			),
		));
		
		// ===== Verificar se os campos enviados não existem no banco de dados
		
		$exiteCampo = interface_verificar_campos(Array(
			'campo' => 'campo', // Caso precise verificar um campo específico único além do `id` acima.
			'valor' => banco_escape_field($_REQUEST['campo']),
		));
		
		if($exiteCampo){ // Caso exista o campo único no banco, retornar erro e pedir para trocar para o usuário.
			$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-there-is-a-field'));
			$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')));
			$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['campo']));
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar($_GESTOR['modulo-id'].'/adicionar/'); // Redireciona novamente ao formulário de adição.
		}
		
        // Montagem dos campos que serão incluídos na tabela do banco de dados.

		// ===== Campos padrões
		
		$campo_nome = "nome"; $post_nome = "nome"; 					        			if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);

		// ===== Campos específicos

		$campo_nome = "campos"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		
		// ===== Campos comuns
		
		$campo_nome = $modulo['tabela']['status']; $campo_valor = 'A'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['versao']; $campo_valor = '1'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);

        // Inserção no banco de dados
		banco_insert_name
		(
			$campos,
			$modulo['tabela']['nome']
		);
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id); // Redireciona para a edição do registro.
	}
	
	// ===== Inclusão de CSS e JS customizados além do padrão que já está no `modulo_id.js`
	
	// $_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="<URL>" />';
	// $_GESTOR['javascript'][] = '<script src="<URL>"></script>';
	
	// ===== Inclusão Módulo JS: `modulo_id.js`
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface adicionar finalizar opções
	
	$_GESTOR['interface']['adicionar']['finalizar'] = Array(
		'formulario' => Array( // Opções de controle do formulário
			'validacao' => Array( // Opções de validação dos campos do formulário.
				Array(
					'regra' => 'texto-obrigatorio', // Regra padrão aplicada ao campo.
					'campo' => 'nome', // Nome do campo no formulário.
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')), // Label do campo no formulário.
					'identificador' => 'nome', // Identificador do campo do formulário.
				)
			),
			'campos' => Array( // Montagem de campos do formulário dinâmicos como select. Exemplo: select dos registros do módulo.
				Array(
					'tipo' => 'select', // Tipo do campo.
					'id' => 'module', // Identificador do campo.
					'nome' => 'modulo', // Nome do campo do formulário.
					'procurar' => true, // Ativar busca no campo.
					'limpar' => true, // Opção de limpar o select.
					'selectClass' => 'class', // Classe CSS do select.
					'placeholder' => gestor_variaveis(Array('restart' => $_GESTOR['modulo-id'],'modulo' => $_GESTOR['modulo-id'],'id' => 'form-module-placeholder')), // Label do select.
					'tabela' => Array( // Tabela onde os dados serão buscados.
						'nome' => 'modulos', // Nome da tabela.
						'campo' => 'nome', // Campo dos registros que serão colocados nos option do select.
						'id_numerico' => 'id', // Referência do registro.
						'where' => "modulo_grupo_id!='bibliotecas'", // Condição para filtrar os registros.
					),
				),
			)
		)
	);
}

function modulo_id_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']]; // Configurações do módulo definido no `RAIZ_MÓDULO/modulo_id.json`
	
	// ===== Identificador do registro que será editado.
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para editar.
	
	$camposBanco = Array(
		'nome',
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
		
		if(!banco_select_campos_antes_iniciar( // Pega os valores dos campos antes de atualizar o banco de dados para efeito de comparação de antes => depois.
			banco_campos_virgulas($camposBancoAntes)
			,
			$modulo['tabela']['nome'],
			"WHERE ".$modulo['tabela']['id']."='".$id."'"
			." AND ".$modulo['tabela']['status']."!='D'"
		)){ // Caso dê algum problema no registro, redireciona e alerta o usuário do problema.
			interface_alerta(Array(
				'redirect' => true,
				'msg' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-database-field-before-error'))
			));
			
			gestor_redirecionar_raiz();
		}
		
		// ===== Validação de campos obrigatórios

		interface_validacao_campos_obrigatorios(Array( // Definição de todos os campos que são obrigatórios. Além de definir eles no JS, confirma aqui no PHP, para evitar de usuário tentar colocar dados não permitidos usando algum subterfúgio.
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio', // Regra, neste caso só obrigação de preenchimento.
					'campo' => 'nome', // Nome do campo.
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')), // Label do campo.
				)
			)
		));
		
		// ===== Verificar se os campos enviados não existem no banco de dados
		
		$exiteCampo = interface_verificar_campos(Array(
			'campo' => 'campo', // Caso precise verificar um campo específico único além do `id` acima.
			'valor' => banco_escape_field($_REQUEST['campo']),
		));
		
		if($exiteCampo){ // Caso exista o campo único no banco, retornar erro e pedir para trocar para o usuário.
			$alerta = gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-there-is-a-field'));
			$alerta = modelo_var_troca_tudo($alerta,"#label#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-path-label')));
			$alerta = modelo_var_troca($alerta,"#value#",banco_escape_field($_REQUEST['campo']));
			
			interface_alerta(Array(
				'redirect' => true,
				'msg' => $alerta
			));
			
			gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id); // Redireciona novamente ao formulário de edição.
		}
		
		// ===== Valores padrões da tabela e regras para o campo nome
		
		$editar = Array(
			'tabela' => $modulo['tabela']['nome'],
			'extra' => "WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D'",
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
                    'id_valor' => $layouts[0][$modulo['tabela']['id_numerico']],
                ),
            ));
            
            $alteracoes_name = 'id'; $alteracoes[] = Array('campo' => 'field-id', 'valor_antes' => $id,'valor_depois' => $id_novo);
            $campo_nome = $modulo['tabela']['id']; $editar['dados'][] = $campo_nome."='" . $id_novo . "'";
            $_GESTOR['modulo-registro-id'] = $id_novo;
		}
		
		// ===== Atualização dos demais campos.
		
		$campo_nome = "campo"; $request_name = $campo_nome; $alteracoes_name = 'campo'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if(isset($editar['dados'])){
			$campo_nome = $modulo['tabela']['versao']; $editar['dados'][] = $campo_nome." = ".$campo_nome." + 1";
			
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
			
			// ===== Incluir no backup os campos.
			
			if(isset($backups)){
				foreach($backups as $backup){
					interface_backup_campo_incluir(Array(
						'id_numerico' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
						'versao' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['versao'])),
						'campo' => $backup['campo'],
						'valor' => $backup['valor'],
					));
				}
			}
			
			// ===== Incluir no histórico as alterações.
			
			interface_historico_incluir(Array(
				'id' => $id,
				'tabela' => Array(
					'nome' => $modulo['tabela']['nome'],
					'id_numerico' => $modulo['tabela']['id_numerico'],
					'versao' => $modulo['tabela']['versao'],
				),
				'alteracoes' => $alteracoes,
			));
		}
		
		// ===== Reler URL com os dados atualizados.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.(isset($id_novo) ? $id_novo : $id));
	}
	
	// ===== Inclusão de CSS e JS customizados além do padrão que já está no `modulo_id.js`
	
	// $_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="<URL>" />';
	// $_GESTOR['javascript'][] = '<script src="<URL>"></script>';
	
	// ===== Inclusão Módulo JS: `modulo_id.js`
	
	gestor_pagina_javascript_incluir();
	
	// ===== Selecionar dados do banco de dados
	
	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas($camposBancoEditar)
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
	);
	
	if($_GESTOR['banco-resultado']){
		$nome = (isset($retorno_bd['nome']) ? $retorno_bd['nome'] : '');
		$campo = (isset($retorno_bd['campo']) ? $retorno_bd['campo'] : '');

		// ===== Alterar demais variáveis.

		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome#',$nome);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#campo#',$campo);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#id#',$id);
		
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
		'id' => $id, // Identificador do registro que está sendo editado.
		'metaDados' => $metaDados, // Metadados para montar o histórico de modificações.
		'banco' => Array( // Definições do banco de dados da tabela atual.
			'nome' => $modulo['tabela']['nome'], // Nome da tabela.
			'id' => $modulo['tabela']['id'], // Id referencial da tabela.
			'status' => $modulo['tabela']['status'], // Status da tabela.
		),
		'botoes' => Array( // Opções que aparecem no menu principal superior para navegação dentro de um módulo.
			'adicionar' => Array( // Id da opção.
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/adicionar/', // URL da opção. Neste caso: `modulo-id/adicionar/`
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-insert')), // Label que aparece no botão.
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-insert')), // Label que aparece como tooltip.
				'icon' => 'plus circle', // Icon usado no botão: https://fomantic-ui.com/elements/icon.html
				'cor' => 'blue', // Cor do ícone do botão. https://fomantic-ui.com/elements/icon.html#colored | https://fomantic-ui.com/elements/icon.html#inverted
			),
			'status' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=status&'.$modulo['tabela']['status'].'='.($status_atual == 'A' ? 'I' : 'A' ).'&'.$modulo['tabela']['id'].'='.$id.'&redirect='.urlencode($_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id), // URL formada dinâmicamente conforme status atual.
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
		'formulario' => Array( // Opções de controle do formulário
			'validacao' => Array( // Opções de validação dos campos do formulário.
				Array(
					'regra' => 'texto-obrigatorio', // Regra padrão aplicada ao campo.
					'campo' => 'nome', // Nome do campo no formulário.
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')), // Label do campo no formulário.
					'identificador' => 'nome', // Identificador do campo do formulário.
				)
			),
            'campos' => Array( // Montagem de campos do formulário dinâmicos como select. Exemplo: select dos registros do módulo.
				Array(
					'tipo' => 'select', // Tipo do campo.
					'id' => 'module', // Identificador do campo.
					'nome' => 'modulo', // Nome do campo do formulário.
					'procurar' => true, // Ativar busca no campo.
					'limpar' => true, // Opção de limpar o select.
					'selectClass' => 'class', // Classe CSS do select.
					'placeholder' => gestor_variaveis(Array('restart' => $_GESTOR['modulo-id'],'modulo' => $_GESTOR['modulo-id'],'id' => 'form-module-placeholder')), // Label do select.
					'tabela' => Array( // Tabela onde os dados serão buscados.
						'nome' => 'modulos', // Nome da tabela.
						'campo' => 'nome', // Campo dos registros que serão colocados nos option do select.
						'id_numerico' => 'id', // Referência do registro.
						'id_selecionado' => $modulo_id, // Referência atual no banco de dados para o valor selecionado.
						'where' => "modulo_grupo_id!='bibliotecas'", // Condição para filtrar os registros.
					),
				),
			)
		)
	);
}

function modulo_id_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array( // Mapeamento com todos os campos do banco de dados que aparecem na tabela HTML com a lista de todos os registros da tabela do banco de dados.
					'nome' => $modulo['tabela']['nome'], // Nome da tabela do banco de dados.
					'campos' => Array(
						'campo', // Campo da tabela do banco de dados que aparece
					),
					'id' => $modulo['tabela']['id'], // nome do campo de `id`
					'status' => $modulo['tabela']['status'], // nome do campo de `status`
				),
				'tabela' => Array( // Regras da Tabela HTML que será montada com todas as regras de cada campo da tabela do banco de dados definido acima.
					'rodape' => true, // Se aparece ou não a paginação no rodapé da tabela HTML.
					'colunas' => Array(
						Array( // Exemplo do campo `nome` da tabela do banco de dados, que aparece na tabela HTML.
							'id' => 'nome', // Campo da tabela do banco de dados definido acima em banco.campos | banco.id | banco.status.
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-name')), // Label que aparece visualmente no cabeçalho da tabela HTML
							'ordenar' => 'asc', // Permissão para ordenação ASC | DESC
						),
						Array( // Exemplo do campo `modulo` da tabela do banco de dados, que tem o id == `modulo-id` que irá buscar em outra tabela o valor desse id e trocar pelo nome == `Módulo`.
							'id' => 'modulo', // Campo da tabela do banco de dados definido acima em banco.campos | banco.id | banco.status.
							'nome' => gestor_variaveis(Array('modulo' => 'modulos','id' => 'module-name')), // Label que aparece visualmente no cabeçalho da tabela HTML
							'formatar' => Array( // Opção de formatação, com o id da formatação e os demais dados necessários para uma formatação em alguns casos.
								'id' => 'outraTabela', // Id da formatação.
								'valor_senao_existe' => '<span class="ui info text">N/A</span>', // Valor padrão caso não exista na outraTabela.
								'tabela' => Array( // Definição dos dados da outra tabela.
									'nome' => 'modulos', // Nome da tabela.
									'campo_trocar' => 'nome', // Campo que será usado o valor para colocar no lugar do valor original. Neste caso id => nome.
									'campo_referencia' => 'id', // Campo que será usado para comparar com o valor original da tabela. Neste caso `modulo`.
								),
							)
						),
						Array(
							'id' => $modulo['tabela']['data_modificacao'], // Campo da tabela do banco de dados definido acima em banco.campos | banco.id | banco.status.
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')), // Label que aparece visualmente no cabeçalho da tabela HTML
							'formatar' => 'dataHora', // Opção de formatação, neste caso como não tem demais dados, é o id direto da formatação.
							'nao_procurar' => true, // Este campo não será considerado no módulo pesquisar que tem na tabela HTML. 
						),
					),
				),
				'opcoes' => Array( // Opções que aparecem na lista de ações de cada registro. Com botões pequenos, na última coluna da tabela HTML, no cabeçalho aparece escrito `Opções`. As referências do registro são geradas automaticamente usando o banco.id acima.
					'editar' => Array( // Id da opção.
						'url' => 'editar/', // URL da opção. Neste caso: `modulo-id/editar/`
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-edit')), // Label que aparece como tooltip.
						'icon' => 'edit', // Icon usado no botão: https://fomantic-ui.com/elements/icon.html
						'cor' => 'basic blue', // Cor do ícone do botão. https://fomantic-ui.com/elements/icon.html#colored | https://fomantic-ui.com/elements/icon.html#inverted
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
				'botoes' => Array( // Opções que aparecem no menu principal superior para navegação dentro de um módulo. Neste caso tem apenas o botão de adicionar um novo registro.
					'adicionar' => Array( // Id da opção.
						'url' => 'adicionar/', // URL da opção. Neste caso: `modulo-id/adicionar/`
						'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-insert')), // Label que aparece no botão.
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-insert')), // Label que aparece como tooltip.
						'icon' => 'plus circle', // Icon usado no botão: https://fomantic-ui.com/elements/icon.html
						'cor' => 'blue', // Cor do ícone do botão. https://fomantic-ui.com/elements/icon.html#colored | https://fomantic-ui.com/elements/icon.html#inverted
					),
				),
			);
		break;
	}
}


// ==== Ajax

function modulo_id_ajax_opcao(){
    global $_GESTOR;

    // ===== Parâmetros recebidos

    if(isset($_REQUEST['params'])){ $params = $_REQUEST['params']; } else { $params = []; } // Parâmetros customizados recebidos.

    // ===== Lógica

    // ===== Dados de Retorno
    
    $_GESTOR['ajax-json'] = [ // Esses dados são depois retornados no formato JSON automaticamente pelo gestor.php
        'status' => 'ok', // ok | erro
    ];
}

// ==== Start

function modulo_id_start(){
	global $_GESTOR;

    // Sempre incluir essa função. Ela irá carregar automaticamente todas as bibliotecas definidas no arquivo de configuração `modulo_id.json`: "bibliotecas": ["nome_biblioteca"]. Além disso, automaticamente o gestor incluirá as seguintes bibliotecas por padrão: ['banco','gestor','modelo'] . Esse nome de biblioteca é um apelido para a biblioteca em si que está em `nome_biblioteca` => `gestor/bibliotecas/nome_biblioteca.php`. Todas as bibliotecas disponível estão na variável: $_GESTOR['bibliotecas-dados']. Caso não encontre uma biblioteca e precise de uma nova, basta incluir a lógica num `arquivo.php`, armazenar na pasta `gestor/bibliotecas/arquivo.php` e incluir a referência dessa variável no `gestor/config.php` na variável $_GESTOR['bibliotecas-dados']. Em seguida, incluir a referência no `modulo_id.json`: "bibliotecas": ["nome_biblioteca"]. Que será carregado aqui.
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){ // Essa variável é controlada automaticamente pelo `gestor/gestor.php`. Quando for fazer uma requisização AJAX, essa variável será marcada como verdadeira e irá entrar aqui.
		interface_ajax_iniciar(); // Apenas incluir as operações inciais do modulo interface para a abertura da lógica de AJAX.
		
		switch($_GESTOR['ajax-opcao']){  // Essa variável é controlada automaticamente pelo `gestor/gestor.php`. Opção definida na requisição AJAX.
			case 'opcao': modulo_id_ajax_opcao(); break; // URL de acesso sempre será a mesma da opção da interface principal que a chamou. A URL é calculada automaticamente. O que muda é 'ajax-opcao'.
		}
		
		interface_ajax_finalizar(); // Apenas incluir as operações inciais do modulo interface para a finalização da lógica de AJAX.
	} else {
		modulo_id_interfaces_padroes(); // Padrões que alteram o módulo de interface.

		interface_iniciar(); // Apenas incluir as operações inciais do modulo interface para a abertura da lógica de interface principal.
		
		switch($_GESTOR['opcao']){
			case 'adicionar': modulo_id_adicionar(); break; // Padrão referencial para adicionar um novo registro ao banco de dados do módulo.
			case 'editar': modulo_id_editar(); break; // Padrão referencial para editar um registro do banco de dados do módulo.
			case 'opcao': modulo_id_opcao(); break; // Outra opção qualquer.
		}
		
		interface_finalizar(); // Apenas incluir as operações inciais do modulo interface para a finalização da lógica de interface principal.
	}
}

modulo_id_start(); // Iniciar o módulo automaticamente quando o gestor.php identificar uma página que referencia a esse módulo.