<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'publisher';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/publisher.json'), true);

function publisher_adicionar(){
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
					'campo' => 'name',
					'label' => 'Nome do Tipo',
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'template_id',
					'label' => 'Template'
				)
			)
		));
		
		// ===== Definição do ID (Slug)
		
		$name = trim($_REQUEST['name']);
		$id = modelo_fazer_slug($name); // Helper function assumed, or use lowercase replace
        if(!$id) $id = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '-', $name)));
		
        // Validar unicidade do ID
        $existe = banco_select(Array(
            'unico' => true,
            'tabela' => $modulo['tabela']['nome'],
            'campos' => Array('id'),
            'extra' => "WHERE id='".banco_escape_field($id)."'"
        ));
        
        if($existe){
            interface_alerta(Array(
				'redirect' => true,
				'msg' => 'O identificador gerado ja existe. Tente outro nome.'
			));
			gestor_redirecionar($_GESTOR['modulo-id'].'/adicionar');
        }

		// ===== Campos para salvar
		
		$campos = null;
		$campo_sem_aspas_simples = false;
		
		$campos[] = Array('id',banco_escape_field($id));
		$campos[] = Array('name',banco_escape_field($name));
		$campos[] = Array('template_id',banco_escape_field($_REQUEST['template_id']));
		$campos[] = Array('fields_schema',banco_escape_field($_REQUEST['fields_schema'])); // JSON String from frontend
		
		$campos[] = Array('status','A');
		$campos[] = Array('versao','1');
		$campos[] = Array('data_criacao','NOW()',$campo_sem_aspas_simples);
		$campos[] = Array('data_modificacao','NOW()',$campo_sem_aspas_simples);
		
		banco_insert_name(
			$campos,
			$modulo['tabela']['nome']
		);
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => 'Registro incluído com sucesso!'
		));
		
		gestor_redirecionar($_GESTOR['modulo-id']);
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface adicionar finalizar opções
	
	$_GESTOR['interface']['adicionar']['finalizar'] = Array(
		'formulario' => Array(
			'validacao' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'name',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-name-label')),
					'identificador' => 'name',
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'template_id',
					'label' => gestor_variaveis(Array('modulo' => 'admin-templates','id' => 'form-name-placeholder')),
					'identificador' => 'template_id',
				),
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'template_id',
					'nome' => 'template_id',
					'procurar' => true,
					'limpar' => true,
					'placeholder' => gestor_variaveis(Array('modulo' => 'admin-templates','id' => 'form-name-placeholder')),
					'tabela' => Array(
						'nome' => 'templates',
						'campo' => 'nome',
						'id_numerico' => 'id',
						'where' => 'language="'.$_GESTOR['linguagem-codigo'].'" AND target="publisher"',
					),
				),
			)
		)
	);
}

function publisher_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do registro
	
	$id = $_GESTOR['id'];
	
	// ===== Definição dos campos do banco de dados
	
	$campos = Array(
		'id',
        'id_publisher',
		'name',
		'template_id',
		'fields_schema',
		'status'
	);
	
	$registro = banco_select(Array(
		'unico' => true,
		'tabela' => $modulo['tabela']['nome'],
		'campos' => $campos,
		'extra' => "WHERE ".$modulo['tabela']['id']."='".$id."'"
	));
	
	if(!$registro){
		gestor_redirecionar($_GESTOR['modulo-id']);
	}
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		$usuario = gestor_usuario();
		
		// ===== Validação de campos obrigatórios
		
		interface_validacao_campos_obrigatorios(Array(
			'campos' => Array(
				Array(
					'regra' => 'texto-obrigatorio',
					'campo' => 'name',
					'label' => 'Nome',
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'template_id',
					'label' => 'Template'
				)
			)
		));
		
		// ===== Campos para salvar
		
		$campos = null;
		$campo_sem_aspas_simples = null;
		
		// ID e ID Publisher não mudam
		$campos[] = Array('name',banco_escape_field($_REQUEST['name']));
		$campos[] = Array('template_id',banco_escape_field($_REQUEST['template_id']));
		$campos[] = Array('fields_schema',banco_escape_field($_REQUEST['fields_schema']));
		
		$campos[] = Array('versao','versao+1',$campo_sem_aspas_simples);
		$campos[] = Array('data_modificacao','NOW()',$campo_sem_aspas_simples);
		
		banco_update
		(
			$campos,
			$modulo['tabela']['nome'],
			"WHERE ".$modulo['tabela']['id']."='".$id."'"
		);
		
		// ===== Incluir no Histórico
		
		interface_historico_incluir(Array(
			'alteracoes' => Array(
				Array(
					'campo' => 'name',
					'valor_antes' => $registro['name'],
					'valor_depois' => $_REQUEST['name']
				),
				Array(
					'campo' => 'template_id',
					'valor_antes' => $registro['template_id'],
					'valor_depois' => $_REQUEST['template_id']
				),
				Array(
					'campo' => 'fields_schema',
					'valor_antes' => $registro['fields_schema'],
					'valor_depois' => $_REQUEST['fields_schema']
				)
			)
		));
		
		interface_alerta(Array(
			'redirect' => true,
			'msg' => 'Registro atualizado com sucesso!'
		));
		
		gestor_redirecionar($_GESTOR['modulo-id']);
	}
	
	// ===== Interface editar iniciar
	
	interface_editar_iniciar();
	
	// ===== Preencher Variáveis
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#name#', $registro['name']);
    
    // Select Templates com Selected value
    $paginas = banco_select(Array(
        'tabela' => 'paginas',
        'campos' => Array('id', 'nome'),
        'extra' => "WHERE status!='D' ORDER BY nome ASC"
    ));
    
    $select_templates = '<select name="template_id" class="ui dropdown fluid search selection">';
    $select_templates .= '<option value="">Selecione um Template</option>';
    if($paginas){
        foreach($paginas as $p){
            $selected = ($p['id'] == $registro['template_id']) ? 'selected' : '';
            $select_templates .= '<option value="'.$p['id'].'" '.$selected.'>'.$p['nome'].' ('.$p['id'].')</option>';
        }
    }
    $select_templates .= '</select>';
    
    $_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], '#select-templates#', $select_templates);
    
    // Injetar o schema existente para o JS carregar
    $schema_json = $registro['fields_schema'] ? $registro['fields_schema'] : '[]';
    $_GESTOR['pagina'] .= '<script>var publisher_initial_schema = '.$schema_json.';</script>';
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface editar finalizar
	
	interface_editar_finalizar();
}

function publisher_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];

    switch($_GESTOR['opcao']){
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'name',
						'template_id',
						$modulo['tabela']['data_modificacao'],
					),
					'id' => $modulo['tabela']['id'],
					'status' => $modulo['tabela']['status'],
					'where' => "language='".$_GESTOR['linguagem-codigo']."'",
				),
				'tabela' => Array(
					'colunas' => Array(
						Array(
							'id' => 'name',
							'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-name')),
							'ordenar' => 'asc',
						),
						Array(
							'id' => 'template_id',
							'nome' => gestor_variaveis(Array('modulo' => 'admin-templates','id' => 'form-name-placeholder')),
							'formatar' => Array(
								'id' => 'outraTabela',
								'valor_senao_existe' => '<span class="ui info text">N/A</span>',
								'tabela' => Array(
									'nome' => 'templates',
									'campo_trocar' => 'nome',
									'campo_referencia' => 'id',
									'where' => 'language="'.$_GESTOR['linguagem-codigo'].'" AND target="publisher"',
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

function publisher_start(){
    global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': publisher_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		publisher_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'adicionar': publisher_adicionar(); break;
		    case 'editar': publisher_editar(); break;
		}
		
		interface_finalizar();
	}
}

publisher_start();

?>
