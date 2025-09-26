<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'modulos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/modulos.json'), true);

// ===== Funções Administrativas de Sistema

function modulos_copiar_variaveis(){
	global $_GESTOR;
	
	// ===== Ativar / Desativar
	
	$ativar = false;
	
	if(!$ativar){
		$_GESTOR['pagina'] .= '<h2>COPIAR VARIÁVEIS - <span class="ui error text">DESATIVADO</span></h2>';
		return;
	}
	
	$_GESTOR['pagina'] .= '<h2>COPIAR VARIÁVEIS</h2>';
	
	// ===== Definir os módulos origem e destino
	
	$modulos = Array(
		'origem' => 'usuarios',
		'destino' => 'usuarios-hospedeiro',
	);
	
	// ===== Buscar no banco de dados 
	
	$variaveisOrigem = banco_select_name
	(
		'*'
		,
		"variaveis",
		"WHERE modulo='".$modulos['origem']."'"
	);
	
	$variaveisDestino = banco_select_name
	(
		'*'
		,
		"variaveis",
		"WHERE modulo='".$modulos['destino']."'"
	);
	
	$quantidade = 0;
	
	// ===== Varrer todos as variaveis e só incluir caso não exista no destino.
	
	if($variaveisOrigem){
		foreach($variaveisOrigem as $origem){
			// ===== Procurar no destino, se encontrar não incluir.
			
			$found = false;
			if($variaveisDestino){
				foreach($variaveisDestino as $destino){
					if($origem['id'] == $destino['id']){
						$found = true;
						break;
					}
				}
			}
			
			// ===== Senão encontrou no destino, incluir nos valores processados.
			
			if(!$found){
				$quantidade++;
				
				// ===== Remover o campo ID padrão e deixar a tabela criar o mesmo sozinho.
				
				unset($origem['id_variaveis']);
				
				// ===== Incluir tudo nos valoresProcessados e trocar o 'modulo' para o destino.
				
				foreach($origem as $id => $valor){
					if(!isset($valoresProcessados[$id])){
						$valoresProcessados[$id] = Array();
					}
					
					if($id == 'modulo'){
						$valoresProcessados[$id][] = $modulos['destino'];
					} else {
						$valoresProcessados[$id][] = banco_escape_field($valor);
					}
				}
			}
		}
		
		// ===== Criar o vetor 'campos' com o nome e os valores para cada campo.
		
		if(isset($valoresProcessados)){
			foreach($valoresProcessados as $nome => $valores){
				$campos[] = Array(
					'nome' => $nome,
					'valores' => $valores,
				);
			}
		}
		
		if(isset($campos)){
			banco_insert_name_varios(Array(
				'campos' => $campos,
				'tabela' => 'variaveis',
			));
		}
	}
	
	$_GESTOR['pagina'] .= '<p>Quantidade: <b>'.$quantidade.'</b></p>';
	$_GESTOR['pagina'] .= '<p>Finalizado!</p>';
}

function modulos_buscar_ids_duplicados(){
	global $_GESTOR;
	global $_BANCOS;
	global $_BANCO;
	
	$ativar = true;
	
	// ===== Definir origem 1 / destino 2
	
	$servers = Array(
		'origem' => 'beta.conn2flow.com',
		//'destino' => 'localhost',
		'destino' => 'conn2flow.com',
	);
	
	$serverOrigem = $servers['origem'];
	$serverDestino = $servers['destino'];
	
	// ===== Mostrar mensagem de desativado.
	
	if(!$ativar){
		$_GESTOR['pagina'] .= '<h2>SINCRONIZAR BANCOS - <span class="ui error text">DESATIVADO</span></h2>';
		return;
	}
	
	// ===== Pegar o arquivo de autenticação nos bancos.
	
	require_once($_GESTOR['AUTH_PATH'] . 'bancos.php');
	
	// ===== Head inicial
	
	$_GESTOR['pagina'] .= '<h2>SINCRONIZAR BANCOS</h2>';
	
	// ===== Parâmetros padrões.
	
	$parametrosPadroes = Array(
		'print1' => 'Módulo - <span class="ui text info">#titulo#</span>: <span class="ui success text">Inserir</span> no Destino',
		'print2' => 'Módulo - <span class="ui text info">#titulo#</span>: <span class="ui warning text">Atualizar</span> no Destino',
	);
	
	// ===== Tabelas que serão sincronizadas.
	
	$tabelas = Array(
		'variaveis',
	);
	
	// ===== Dados das tabelas.
	
	$dados['variaveis'] = Array(
		'titulo' => 'Variáveis',
		'tabela' => Array(
			'nome' => 'variaveis',
			'id_referencia' => 'id_variaveis',
			'camposComparacao' => Array(
				'id_variaveis',
				'id',
				'modulo',
				'grupo',
			),
			'comparacaoIDEModuloGrupo' => true,
			'ignorarAtualizacoes' => true,
			'ignorarAddSlashes' => true,
		),
	);
	
	foreach($tabelas as $tabela){
		
		$idAtual = $tabela;
		$dadosDef = $dados[$idAtual];
		
		// ===== Título da tabela.
		
		$titulo = $dadosDef['titulo'];
		
		// ===== Tabela Origem
		
		unset($camposAtualizar);
		
		$_BANCO = $_BANCOS[$serverOrigem];
		
		$tabelaOrigem = banco_select_name
		(
			'*'
			,
			$dadosDef['tabela']['nome'],
			""
		);
		
		banco_fechar_conexao();
		
		// ===== Tabela Destino
		
		$_BANCO = $_BANCOS[$serverDestino];
		
		$tabelaDestino = banco_select_name
		(
			banco_campos_virgulas($dadosDef['tabela']['camposComparacao'])
			,
			$dadosDef['tabela']['nome'],
			""
		);
		
		$SQL = '';
		
		if($tabelaOrigem){
			foreach($tabelaOrigem as $to){
				if($to['modulo'] == '_sistema'){
					continue;
				}
				
				// ===== Procurar se o dado existe no destino.
				
				$found = false;
				$found2 = false;
				$idUm = '';
				$idDois = '';
				
				if($tabelaDestino){
					foreach($tabelaDestino as $td){
						if(
							$to['id'] == $td['id'] &&
							//$to['grupo'] == $td['grupo'] &&
							$to['modulo'] == $td['modulo']
						){
							if(!existe($idUm)){
								$idUm = $td['id_variaveis'].' <=> '.$td['id'].' - '.$td['modulo'].' - '.$td['grupo'];
							}
							
							if($found2){
								$idDois .= (existe($idDois) ? ', ':'') . $td['id_variaveis'].' <=> '.$td['id'].' - '.$td['modulo'].' - '.$td['grupo'];
								$SQL .= "DELETE FROM variaveis WHERE id_variaveis='".$td['id_variaveis']."';<br>";
								$found = true;
							}
							
							$found2 = true;
						}
					}
				}
				
				if($found){
					// ===== Colocar o título no início das impressões dos dados que serão inseridos.
					
					if(!isset($inserirSinal[$idAtual])){
						$print1 = modelo_var_troca_tudo($parametrosPadroes['print1'],"#titulo#",$titulo);
						
						$_GESTOR['pagina'] .= '<b>'.$print1.':</b><br><br><!-- inserir-'.$idAtual.' -->';
					}
					
					$inserirSinal[$idAtual] = true;
					
					// ===== Incluir na impressão o dado a ser incluído para conferência visual.
					
					$_GESTOR['pagina'] = modelo_var_in($_GESTOR['pagina'],'<!-- inserir-'.$idAtual.' -->',$to['id'].'<br>'.$to['id'].' - '.$to['modulo'].' - '.$to['grupo'].'<br>'.$idUm.'<br>'.$idDois.'<div class="ui divider"></div>');
					
				}
			}
		}
		
		$_GESTOR['pagina'] .= '<div class="ui divider"></div>'.$SQL;
	}
}

// ===== Funções Principais

function modulos_variaveis(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do 
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Interface de administração da configuração salvar.
		
		gestor_incluir_biblioteca('configuracao');
		
		configuracao_administracao_salvar(Array(
			'modulo' => $id,
			'linguagemCodigo' => $_GESTOR['linguagem-codigo'],
			'tabela' => $modulo['tabela'],
		));
		
		// ===== Reler URL.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/variaveis/?'.$modulo['tabela']['id'].'='.(isset($id_novo) ? $id_novo : $id));
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Selecionar dados do banco de dados
	
	$retorno_bd = banco_select_editar
	(
		banco_campos_virgulas(Array(
			'nome',
			$modulo['tabela']['status'],
			$modulo['tabela']['versao'],
			$modulo['tabela']['data_criacao'],
			$modulo['tabela']['data_modificacao'],
		))
		,
		$modulo['tabela']['nome'],
		"WHERE ".$modulo['tabela']['id']."='".$id."'"
		." AND ".$modulo['tabela']['status']."!='D'"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
	);
	
	if($_GESTOR['banco-resultado']){
		$nome = (isset($retorno_bd['nome']) ? $retorno_bd['nome'] : '');
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome#',$nome);
		
		// ===== Popular os metaDados
		
		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');
		
		if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['versao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-version')),'dado' => $retorno_bd[$modulo['tabela']['versao']]); }
		if(isset($retorno_bd[$modulo['tabela']['status']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status')),'dado' => ($retorno_bd[$modulo['tabela']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-active')).'</b></div>' : '').($retorno_bd[$modulo['tabela']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-inactive')).'</b></div>' : '')); }
		
		// ===== Interface de administração da configuração.
		
		gestor_incluir_biblioteca('configuracao');
		
		configuracao_administracao(Array(
			'modulo' => $id,
			'linguagemCodigo' => $_GESTOR['linguagem-codigo'],
			'marcador' => '<!-- configuracao-administracao -->',
		));
	} else {
		gestor_redirecionar_raiz();
	}
	
	// ===== Interface alteracoes finalizar opções
	
	$_GESTOR['interface'][$_GESTOR['interface-opcao']]['finalizar'] = Array(
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
			'editar' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/editar/?'.$modulo['tabela']['id'].'='.$id,
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-edit')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-edit')),
				'icon' => 'edit',
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
					'regra' => 'nao-vazio',
					'campo' => 'ids-obrigatorios',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-id-label')),
				),
			),
		)
	);
}

function modulos_adicionar(){
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
			),
		));
		
		// ===== Campos gerais
		
		$campo_nome = "id_usuarios"; $campo_valor = $usuario['id_usuarios']; 			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $post_nome = "nome"; 										if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "titulo"; $post_nome = "titulo"; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "modulo_grupo_id"; $post_nome = "grupo"; 						if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "icone"; $post_nome = "icone"; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "icone2"; $post_nome = "icone2"; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "plugin"; $post_nome = "plugin"; 									if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		$campo_nome = "nao_menu_principal"; $post_nome = "menu"; 						if($_REQUEST[$post_nome] == 'nao')		$campos[] = Array($campo_nome,'1',true);
		$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "host"; $post_nome = "host"; $campo_valor = '1';					if($_REQUEST[$post_nome] == 'on')		$campos[] = Array($campo_nome,$campo_valor,true);
		
		// ===== Campos comuns
		
		$campo_nome = 'language '; $campo_valor = $_GESTOR['linguagem-codigo']; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
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
				),
				Array(
					'regra' => 'selecao-obrigatorio',
					'campo' => 'grupo',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-grup-label')),
				)
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'grup',
					'nome' => 'grupo',
					'procurar' => true,
					'limpar' => true,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-grup-placeholder')),
					'tabela' => Array(
						'nome' => 'modulos_grupos',
						'campo' => 'nome',
						'id_numerico' => 'id',
						'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
					),
				),
				Array(
					'tipo' => 'select',
					'id' => 'menu',
					'nome' => 'menu',
					'procurar' => true,
					'limpar' => true,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-menu-placeholder')),
					'dados' => Array(
						Array(
							'texto' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-positive-label')),
							'valor' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-positive-value')),
						),
						Array(
							'texto' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-negative-label')),
							'valor' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-negative-value')),
						),
					),
				),
				Array(
					'tipo' => 'select',
					'id' => 'plugin',
					'nome' => 'plugin',
					'procurar' => true,
					'limpar' => true,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-plugin-placeholder')),
					'tabela' => Array(
						'nome' => 'plugins',
						'campo' => 'nome',
						'id' => true,
					),
				),
			)
		)
	);
}

function modulos_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do 
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para editar.
	
	$camposBanco = Array(
		'nome',
		'modulo_grupo_id',
		'icone',
		'icone2',
		'nao_menu_principal',
		'titulo',
		'plugin',
		'host',
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
					'id_valor' => interface_modulo_variavel_valor(Array('variavel' => $modulo['tabela']['id_numerico'])),
				),
			));
			
			$alteracoes_name = 'id'; $alteracoes[] = Array('campo' => 'field-id', 'valor_antes' => $id,'valor_depois' => $id_novo);
			$campo_nome = $modulo['tabela']['id']; $editar['dados'][] = $campo_nome."='" . $id_novo . "'";
			$_GESTOR['modulo-registro-id'] = $id_novo;
		}
		
		// ===== Atualização dos demais campos.
		
		$campo_nome = "modulo_grupo_id"; $request_name = 'grupo'; $alteracoes_name = 'grup'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]),'tabela' => Array(
				'nome' => 'modulos_grupos',
				'campo' => 'nome',
				'id_numerico' => 'id',
			));}
		
		$campo_nome = "plugin"; $request_name = 'plugin'; $alteracoes_name = 'plugin'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = (existe($_REQUEST[$request_name]) ? $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'" : $campo_nome."=NULL"); $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]),'tabela' => Array(
				'nome' => 'plugins',
				'campo' => 'nome',
				'id' => 'id',
			));}
		
		$campo_nome = "titulo"; $request_name = $campo_nome; $alteracoes_name = 'title'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		$campo_nome = "icone"; $request_name = $campo_nome; $alteracoes_name = 'icon'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		$campo_nome = "icone2"; $request_name = $campo_nome; $alteracoes_name = 'icon-2'; if(banco_select_campos_antes($campo_nome) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."='" . banco_escape_field($_REQUEST[$request_name]) . "'"; $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo_nome),'valor_depois' => banco_escape_field($_REQUEST[$request_name]));}
		
		$campo_nome = "nao_menu_principal"; $request_name = 'menu'; $alteracoes_name = 'menu'; if((banco_select_campos_antes($campo_nome) ? 'nao' : 'sim' ) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$editar['dados'][] = $campo_nome."=" . (banco_escape_field($_REQUEST[$request_name]) == 'nao' ? '1' : 'NULL' ); $alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => (banco_select_campos_antes($campo_nome) ? 'Não' : 'Sim'),'valor_depois' => (banco_escape_field($_REQUEST[$request_name])) == 'sim' ? 'Sim' : 'Não');}
		
		$campo_nome = "host"; $request_name = 'host'; $alteracoes_name = 'host'; if(banco_select_campos_antes($campo_nome) != ($_REQUEST[$request_name] == 'on' ? '1' : NULL)){
			$editar['dados'][] = $campo_nome."=" . ($_REQUEST[$request_name] == 'on' ? '1' : 'NULL');
			$alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'filtro' => 'checkbox','valor_antes' => (banco_select_campos_antes($campo_nome) ? '1' : '0'),'valor_depois' => ($_REQUEST[$request_name] == 'on' ? '1' : '0'));
		}
		
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
	);
	
	if($_GESTOR['banco-resultado']){
		$nome = (isset($retorno_bd['nome']) ? $retorno_bd['nome'] : '');
		$icone = (isset($retorno_bd['icone']) ? $retorno_bd['icone'] : '');
		$icone2 = (isset($retorno_bd['icone2']) ? $retorno_bd['icone2'] : '');
		$menu_principal = (isset($retorno_bd['nao_menu_principal']) ? 'nao' : 'sim');
		$modulo_grupo_id = (isset($retorno_bd['modulo_grupo_id']) ? $retorno_bd['modulo_grupo_id'] : '');
		$titulo = (isset($retorno_bd['titulo']) ? $retorno_bd['titulo'] : '');
		$plugin = (isset($retorno_bd['plugin']) ? $retorno_bd['plugin'] : '');
		$host = (isset($retorno_bd['host']) ? true : false);
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome#',$nome);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#icone#',$icone);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#icone2#',$icone2);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#titulo#',$titulo);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#checked#',($host ? 'checked' : ''));
		
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
			'variaveis' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/variaveis/?'.$modulo['tabela']['id'].'='.$id,
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-variable')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-variable')),
				'icon' => 'book',
				'cor' => 'teal',
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
					'regra' => 'selecao-obrigatorio',
					'campo' => 'grupo',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-grup-label')),
				)
			),
			'campos' => Array(
				Array(
					'tipo' => 'select',
					'id' => 'grup',
					'nome' => 'grupo',
					'procurar' => true,
					'limpar' => true,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-grup-placeholder')),
					'tabela' => Array(
						'nome' => 'modulos_grupos',
						'campo' => 'nome',
						'id_numerico' => 'id',
						'id_selecionado' => $modulo_grupo_id,
						'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
					),
				),
				Array(
					'tipo' => 'select',
					'id' => 'plugin',
					'nome' => 'plugin',
					'procurar' => true,
					'limpar' => true,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-plugin-placeholder')),
					'tabela' => Array(
						'nome' => 'plugins',
						'campo' => 'nome',
						'id_selecionado' => $plugin,
						'id' => true,
					),
				),
				Array(
					'tipo' => 'select',
					'id' => 'menu',
					'nome' => 'menu',
					'procurar' => true,
					'limpar' => true,
					'valor_selecionado' => $menu_principal,
					'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-menu-placeholder')),
					'dados' => Array(
						Array(
							'texto' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-positive-label')),
							'valor' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-positive-value')),
						),
						Array(
							'texto' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-negative-label')),
							'valor' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-negative-value')),
						),
					),
				)
			)
		)
	);
}

function modulos_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'variaveis': 
			$_GESTOR['interface-opcao'] = 'alteracoes';
		break;
	}
	
	switch($_GESTOR['opcao']){
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'nome',
						'modulo_grupo_id',
						'plugin',
						'host',
						$modulo['tabela']['data_modificacao'],
					),
					'id' => $modulo['tabela']['id'],
					'status' => $modulo['tabela']['status'],
					'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
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
							'id' => 'modulo_grupo_id',
							'nome' => gestor_variaveis(Array('modulo' => 'modulos','id' => 'form-grup-label')),
							'formatar' => Array(
								'id' => 'outraTabela',
								'tabela' => Array(
									'nome' => 'modulos_grupos',
									'campo_trocar' => 'nome',
									'campo_referencia' => 'id',
									'where' => 'language="'.$_GESTOR['linguagem-codigo'].'"',
								),
							)
						),
						Array(
							'id' => 'plugin',
							'nome' => gestor_variaveis(Array('modulo' => 'modulos','id' => 'form-plugin-label')),
							'formatar' => Array(
								'id' => 'outraTabela',
								'valor_senao_existe' => '<span class="ui info text">'.gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-plugin-empty')).'</span>',
								'tabela' => Array(
									'nome' => 'plugins',
									'campo_trocar' => 'nome',
									'campo_referencia' => 'id',
								),
							)
						),
						Array(
							'id' => 'host',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-host-label')),
							'formatar' => Array(
								'valor_substituir_por_rotulo' => Array(
									Array(
										'valor' => '1',
										'rotulo' => '<b><span class="ui text blue">'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-positive-label')).'</span></b>',
									),
								),
								'valor_senao_existe' => '<b><span class="ui text grey">'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-negative-label')).'</span></b>',
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
					'variaveis' => Array(
						'url' => 'variaveis/',
						'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-variable')),
						'icon' => 'book',
						'cor' => 'basic teal',
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

// ==== Start

function modulos_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': modulos_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		modulos_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'adicionar': modulos_adicionar(); break;
			case 'editar': modulos_editar(); break;
			case 'variaveis': modulos_variaveis(); break;
			case 'copiar-variaveis': modulos_copiar_variaveis(); break;
		}
		
		interface_finalizar();
	}
}

modulos_start();

?>