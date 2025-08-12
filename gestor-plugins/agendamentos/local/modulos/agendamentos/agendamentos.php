<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'agendamentos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/agendamentos.json'), true);

// ===== Alterar o 'id_hosts'

$where = $_GESTOR['modulo#'.$_GESTOR['modulo-id']]['tabela']['where'];

$where = modelo_var_troca($where,"#id_hosts#",$_GESTOR['host-id']);

$_GESTOR['modulo#'.$_GESTOR['modulo-id']]['tabela']['where'] = $where;

// ===== Funções Auxiliares

function agendamentos_calendario(){
	global $_GESTOR;
	
	gestor_incluir_biblioteca('formato');
	gestor_incluir_biblioteca('configuracao');
	
	$ano_inicio = date('Y');
	$hoje = date('Y-m-d');
	
	$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-agendamentos'));
	
	$dias_semana = (existe($config['dias-semana']) ? explode(',',$config['dias-semana']) : Array());
	$anos = (existe($config['calendario-anos']) ? (int)$config['calendario-anos'] : 2);
	if(existe($config['datas-indisponiveis'])) $datas_indisponiveis = (existe($config['datas-indisponiveis-valores']) ? explode('|',$config['datas-indisponiveis-valores']) : Array()); else $datas_indisponiveis = Array();
	$calendario_ferias_de = (existe($config['calendario-ferias-de']) ? trim($config['calendario-ferias-de']) : '15 December');
	$calendario_ferias_ate = (existe($config['calendario-ferias-ate']) ? trim($config['calendario-ferias-ate']) : '20 January');
	
	$ano_fim = (int)$ano_inicio + $anos;
	
	for($i=-1;$i<$anos+1;$i++){
		$periodo_ferias[] = Array(
			'inicio' => strtotime($calendario_ferias_de." ".($ano_inicio+$i)),
			'fim' => strtotime($calendario_ferias_ate." ".($ano_inicio+$i+1)),
		);
	}
	
	$primeiro_dia = strtotime(date("Y-m-d", time()) . " - 60 day");
	$ultimo_dia = strtotime(date("Y-m-d", time()) . " + ".$anos." year");
	
	$dia = $primeiro_dia;
	do {
		$dataFormatada = date('d/m/Y', $dia);
		$flag = false;
		
		if($periodo_ferias){
			foreach($periodo_ferias as $periodo){
				if(
					$dia > $periodo['inicio'] &&
					$dia < $periodo['fim']
				){
					$flag = true;
					break;
				}
			}
		}
		
		if($datas_indisponiveis){
			foreach($datas_indisponiveis as $di){
				if($dataFormatada == $di){
					$flag = true;
					break;
				}
			}
		}
		
		if(!$flag){
			$flag2 = false;
			$count_dias = 0;
			if($dias_semana)
			foreach($dias_semana as $dia_semana){
				if($dia_semana == strtolower(date('D',$dia))){
					$flag2 = true;
					break;
				}
				$count_dias++;
			}
			
			if($flag2){
				$data = date('Y-m-d', $dia);
				$datas[$data] = 1;
			}
		}
		
		$dia += 86400;
	} while ($dia < $ultimo_dia);
	
	$JScalendario['datas_disponiveis'] = $datas;
	$JScalendario['ano_inicio'] = $ano_inicio;
	$JScalendario['ano_fim'] = $ano_fim;
	
	// ===== Variáveis JS.
	
	$_GESTOR['javascript-vars']['calendario'] = $JScalendario;
}

function agendamentos_impressao_cupons(){
	global $_GESTOR;
	
	// ===== Incluir bibliotecas.
	
	gestor_incluir_biblioteca(Array(
		'formato',
		'comunicacao',
		'configuracao',
	));
	
	// ===== Identificador do host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Pegar dados de requisição.
	
	$id = banco_escape_field($_REQUEST['id']);
	
	// ===== Pegar os cupons do banco de dados.
	
	$hosts_conjunto_cupons_prioridade = banco_select(Array(
		'unico' => true,
		'tabela' => 'hosts_conjunto_cupons_prioridade',
		'campos' => Array(
			'id_hosts_conjunto_cupons_prioridade',
			'nome',
			'quantidade',
			'valido_de',
			'valido_ate',
		),
		'extra' => 
			"WHERE id='".$id."'"
			." AND id_hosts='".$id_hosts."'"
			." AND status='A'"
	));
	
	// ===== Caso não exista o registro, retornar erro com a mensagem.
	
	if(!$hosts_conjunto_cupons_prioridade){
		$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-cupons-not-found'));
		
		comunicacao_impressao(Array(
			'titulo' => 'Cupons de Prioridade - Problema: '.$id,
			'pagina' => $alerta,
		));
		
		return;
	}
	
	// ===== Iniciar variáveis.
	
	$hoje = date('Y-m-d');
	
	$id_hosts_conjunto_cupons_prioridade = $hosts_conjunto_cupons_prioridade['id_hosts_conjunto_cupons_prioridade'];
	$valido_de = $hosts_conjunto_cupons_prioridade['valido_de'];
	$valido_ate = $hosts_conjunto_cupons_prioridade['valido_ate'];
	$nome = $hosts_conjunto_cupons_prioridade['nome'];
	$quantidade = $hosts_conjunto_cupons_prioridade['quantidade'];
	
	// ===== Verificar se os cupons estão dentro do prazo de validade.
	
	if(strtotime($hoje) > strtotime($valido_ate)){
		$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-cupons-expired'));
		
		comunicacao_impressao(Array(
			'titulo' => 'Cupons de Prioridade - Problema: '.$id,
			'pagina' => $alerta,
		));
		
		return;
	}
	
	// ===== Pegar os códigos dos cupons no banco de dados.
	
	$hosts_cupons_prioridade = banco_select(Array(
		'tabela' => 'hosts_cupons_prioridade',
		'campos' => Array(
			'codigo',
		),
		'extra' => 
			"WHERE id_hosts_conjunto_cupons_prioridade='".$id_hosts_conjunto_cupons_prioridade."'"
			." AND id_hosts='".$id_hosts."'"
	));
	
	// ===== Pegar o componente de impressão da tabela de cupons.
	
	$tabela = gestor_componente(Array(
		'id' => 'tabela-cupons-prioridade',
	));
	
	// ===== Pegar células da tabela.
	
	$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($tabela,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $tabela = modelo_tag_in($tabela,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	// ===== Pegar configurações do host.
	
	$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-agendamentos'));
	
	$titulo = $config['titulo-estabelecimento'];
	$descricao = $config['cupom-prioridade-descricao'];
	
	// ===== Formatar a validade.
	
	$validade_de = formato_dado_para('data',$valido_de);
	$validade = formato_dado_para('data',$valido_ate);
	
	// ===== Montar a tabela com todos os códigos.
	
	$impressao = '';
	$cont = 0;
	
	if($hosts_cupons_prioridade)
	foreach($hosts_cupons_prioridade as $cupom){
		if($cont % 9 == 0){
			$impressao = modelo_var_troca($impressao,'<!-- '.$cel_nome.' -->','');
			$impressao .= (existe($impressao) ? '<div class="pagebreak"></div>' : '').$tabela;
		}
		
		$cel_aux = $cel[$cel_nome];
		
		$cel_aux = modelo_var_troca($cel_aux,"#titulo#",$titulo);
		$cel_aux = modelo_var_troca($cel_aux,"#descricao#",$descricao);
		$cel_aux = modelo_var_troca($cel_aux,"#validade#",$validade);
		$cel_aux = modelo_var_troca($cel_aux,"#codigo#",$cupom['codigo']);
		
		$impressao = modelo_var_in($impressao,'<!-- '.$cel_nome.' -->',$cel_aux);
		
		$cont++;
	}
	$impressao = modelo_var_troca($impressao,'<!-- '.$cel_nome.' -->','');
	
	// ===== Incluir a tabela no buffer de impressão.
	
	comunicacao_impressao(Array(
		'titulo' => $nome.' - Qtd: '.$quantidade.' - Válido de '.$validade_de.' até '.$validade,
		'pagina' => $impressao,
	));
}

// ===== Funções Principais

function agendamentos_administrar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Reler URL.
		
		gestor_redirecionar_raiz();
	}
	
	// ===== Adicionar o calendário.
	
	agendamentos_calendario();
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface config finalizar opções
	
	$_GESTOR['interface']['simples']['finalizar'] = Array(
		'botoes' => Array(
			'administrar' => Array(
				'url' => '',
				'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-button-admin')),
				'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-admin')),
				'icon' => 'calendar alternate',
				'cor' => 'orange',
			),
			'cupons' => Array(
				'url' => 'cupons-de-prioridade/',
				'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-button-coupon')),
				'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-coupon')),
				'icon' => 'certificate',
				'cor' => 'green',
			),
		),
	);
}

function agendamentos_cupons_adicionar(){
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
		
		// ===== Tratar data padrão date.
		
		gestor_incluir_biblioteca('formato');
		
		$valido_de = formato_data_hora_padrao_datetime($_REQUEST['valido_de'],true);
		$valido_ate = formato_data_hora_padrao_datetime($_REQUEST['valido_ate'],true);
		
		$quantidade = (isset($_REQUEST['quantidade']) ? (int)$_REQUEST['quantidade'] : 0);
		
		// ===== Campos gerais
		
		banco_insert_name_campo('id_hosts',$_GESTOR['host-id']);
		banco_insert_name_campo('id',$id);
		if(isset($_REQUEST['nome'])){ banco_insert_name_campo('nome',$_REQUEST['nome']); }
		banco_insert_name_campo('quantidade',$quantidade);
		banco_insert_name_campo('valido_de',$valido_de);
		banco_insert_name_campo('valido_ate',$valido_ate);
		
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
		
		$id_hosts_conjunto_cupons_prioridade = banco_last_id();
		
		// ===== Criar os cupons baseado na quantidade.
		
		for($i=0;$i<$quantidade;$i++){
			// ===== Gerar o código único para o cupom.
			
			$better_token = strtoupper(substr(md5(uniqid(rand(), true)),0,8));
			$codigo = formato_colocar_char_meio_numero($better_token);
			
			// ===== Criar o cupom no banco de dados.
			
			banco_insert_name_campo('id_hosts',$_GESTOR['host-id']);
			banco_insert_name_campo('id_hosts_conjunto_cupons_prioridade',$id_hosts_conjunto_cupons_prioridade);
			banco_insert_name_campo('codigo',$codigo);
			
			banco_insert_name
			(
				banco_insert_name_campos(),
				"hosts_cupons_prioridade"
			);
		}
		
		// ===== Redirecionar o gestor.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/cupons-de-prioridade/editar/?'.$modulo['tabela']['id'].'='.$id);
	}
	
	// ===== Inclusão do jQuery-Mask-Plugin
	
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'jQuery-Mask-Plugin-v1.14.16/jquery.mask.min.js"></script>';
	
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
					'regra' => 'nao-vazio',
					'campo' => 'quantidade',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-quantity-label')),
				),
				Array(
					'regra' => 'nao-vazio',
					'campo' => 'valido_de',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-valid-from-label')),
				),
				Array(
					'regra' => 'nao-vazio',
					'campo' => 'valido_ate',
					'label' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-valid-until-label')),
				),
			)
		),
	);
}

function agendamentos_cupons_editar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Identificador do host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Identificador do registro.
	
	$id = $_GESTOR['modulo-registro-id'];
	
	// ===== Definição dos campos do banco de dados para editar.
	
	$camposBanco = Array(
		'nome',
		'quantidade',
		'valido_de',
		'valido_ate',
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
		// ===== Adicionar bibliotecas.
		
		gestor_incluir_biblioteca('formato');
		
		// ===== Recuperar o estado dos dados do banco de dados antes de editar.
		
		if(!banco_select_campos_antes_iniciar(
			banco_campos_virgulas($camposBancoAntes)
			,
			$modulo['tabela']['nome'],
			"WHERE ".$modulo['tabela']['id']."='".$id."'"
			." AND ".$modulo['tabela']['status']."!='D'"
			." AND id_hosts='".$id_hosts."'"
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
		
		$campo = 'nome'; $request = 'nome'; $alteracoes_name = 'name'; if(banco_select_campos_antes($campo) != (isset($_REQUEST[$request]) ? $_REQUEST[$request] : NULL)){
			$editar = true;
			banco_update_campo($campo,$_REQUEST[$request],false,true);
			if(!isset($_REQUEST['_gestor-nao-alterar-id'])){$alterar_id = true;}
			$alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo),'valor_depois' => banco_escape_field($_REQUEST[$request]));
		}
		
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
			
			$alteracoes[] = Array('campo' => 'field-id', 'valor_antes' => $id,'valor_depois' => $id_novo);
			banco_update_campo($modulo['tabela']['id'],$id_novo);
			$_GESTOR['modulo-registro-id'] = $id_novo;
		}
		
		// ===== Atualização dos demais campos.
		
		$campo = 'quantidade'; $request = $campo; $alteracoes_name = 'quantity'; if(banco_select_campos_antes($campo) != (isset($_REQUEST[$request]) ? $_REQUEST[$request] : NULL)){
			$editar = true;
			$alterouQuantidade = true;
			banco_update_campo($campo,$_REQUEST[$request],false,true);
			$alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => banco_select_campos_antes($campo),'valor_depois' => banco_escape_field($_REQUEST[$request]));
		}
		
		// ===== Tratar data padrão date e editar os campos de data.
		
		$_REQUEST['valido_de'] = formato_data_hora_padrao_datetime($_REQUEST['valido_de'],true);
		$_REQUEST['valido_ate'] = formato_data_hora_padrao_datetime($_REQUEST['valido_ate'],true);
		
		$campo = 'valido_de'; $request = $campo; $alteracoes_name = 'valid-from'; if(banco_select_campos_antes($campo) != (isset($_REQUEST[$request]) ? $_REQUEST[$request] : NULL)){
			$editar = true;
			banco_update_campo($campo,$_REQUEST[$request],false,true);
			$alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => formato_data_from_datetime_to_text(banco_select_campos_antes($campo)),'valor_depois' => banco_escape_field(formato_data_from_datetime_to_text($_REQUEST[$request])));
		}
		
		$campo = 'valido_ate'; $request = $campo; $alteracoes_name = 'valid-until'; if(banco_select_campos_antes($campo) != (isset($_REQUEST[$request]) ? $_REQUEST[$request] : NULL)){
			$editar = true;
			banco_update_campo($campo,$_REQUEST[$request],false,true);
			$alteracoes[] = Array('campo' => 'form-'.$alteracoes_name.'-label', 'valor_antes' => formato_data_from_datetime_to_text(banco_select_campos_antes($campo)),'valor_depois' => banco_escape_field(formato_data_from_datetime_to_text($_REQUEST[$request])));
		}
		
		// ===== Se houve alterações, modificar no banco de dados junto com campos padrões de atualização
		
		if(isset($editar)){
			banco_update_campo($modulo['tabela']['versao'],$modulo['tabela']['versao']." + 1",true);
			banco_update_campo($modulo['tabela']['data_modificacao'],'NOW()',true);
			
			banco_update_executar($modulo['tabela']['nome'],"WHERE ".$modulo['tabela']['id']."='".$id."' AND ".$modulo['tabela']['status']."!='D' AND id_hosts='".$id_hosts."'");
			
			// ===== Incluir no histórico as alterações.
			
			interface_historico_incluir(Array(
				'alteracoes' => $alteracoes,
			));
		}
		
		// ===== Alterar quantidade de cupons se a quantidade for mudada.
		
		if(isset($alterouQuantidade)){
			$quantidadeAntes = (int)banco_select_campos_antes('quantidade');
			$quantidadeDepois = (int)$_REQUEST['quantidade'];
			
			// ===== Pegar o identificador do conjunto de cupons.
			
			$hosts_conjunto_cupons_prioridade = banco_select(Array(
				'unico' => true,
				'tabela' => 'hosts_conjunto_cupons_prioridade',
				'campos' => Array(
					'id_hosts_conjunto_cupons_prioridade',
				),
				'extra' => 
					"WHERE id='".(isset($id_novo) ? $id_novo : $id)."'"
					." AND status!='D'"
			));
			
			$id_hosts_conjunto_cupons_prioridade = $hosts_conjunto_cupons_prioridade['id_hosts_conjunto_cupons_prioridade'];
			
			// ===== Caso a quantidade depois seja maior que antes, criar novos cupons. Senão, remover cupons excedentes.
			
			if($quantidadeDepois > $quantidadeAntes){
				for($i=0;$i<($quantidadeDepois - $quantidadeAntes);$i++){
					// ===== Gerar o código único para o cupom.
					
					$better_token = strtoupper(substr(md5(uniqid(rand(), true)),0,8));
					$codigo = formato_colocar_char_meio_numero($better_token);
					
					// ===== Criar o cupom no banco de dados.
					
					banco_insert_name_campo('id_hosts',$id_hosts);
					banco_insert_name_campo('id_hosts_conjunto_cupons_prioridade',$id_hosts_conjunto_cupons_prioridade);
					banco_insert_name_campo('codigo',$codigo);
					
					banco_insert_name
					(
						banco_insert_name_campos(),
						"hosts_cupons_prioridade"
					);
				}
			} else {
				$hosts_cupons_prioridade = banco_select(Array(
					'tabela' => 'hosts_cupons_prioridade',
					'campos' => Array(
						'id_hosts_cupons_prioridade',
					),
					'extra' => 
						"WHERE id_hosts_conjunto_cupons_prioridade='".$id_hosts_conjunto_cupons_prioridade."'"
						." AND id_hosts='".$id_hosts."'"
						." ORDER BY id_hosts_cupons_prioridade DESC"
				));
				
				$cont = 0;
				if($hosts_cupons_prioridade)
				foreach($hosts_cupons_prioridade as $cupom){
					if($cont >= ($quantidadeAntes - $quantidadeDepois)){
						break;
					}
					
					banco_delete
					(
						"hosts_cupons_prioridade",
						"WHERE id_hosts_cupons_prioridade='".$cupom['id_hosts_cupons_prioridade']."'"
						." AND id_hosts='".$id_hosts."'"
					);
					
					$cont++;
				}
			}
		}
		
		// ===== Reler URL.
		
		gestor_redirecionar($_GESTOR['modulo-id'].'/cupons-de-prioridade/editar/?'.$modulo['tabela']['id'].'='.(isset($id_novo) ? $id_novo : $id));
	}
	
	// ===== Inclusão do jQuery-Mask-Plugin
	
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'jQuery-Mask-Plugin-v1.14.16/jquery.mask.min.js"></script>';
	
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
		$quantidade = (isset($retorno_bd['quantidade']) ? $retorno_bd['quantidade'] : '');
		$valido_de = (isset($retorno_bd['valido_de']) ? $retorno_bd['valido_de'] : '');
		$valido_ate = (isset($retorno_bd['valido_ate']) ? $retorno_bd['valido_ate'] : '');
		
		// ===== Popular formulario.
		
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#nome#',$nome);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#quantidade#',$quantidade);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#valido_de#',$valido_de);
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],'#valido_ate#',$valido_ate);
		
		// ===== Popular os metaDados
		
		$status_atual = (isset($retorno_bd[$modulo['tabela']['status']]) ? $retorno_bd[$modulo['tabela']['status']] : '');
		
		if(isset($retorno_bd[$modulo['tabela']['data_criacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-start')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_criacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['data_modificacao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-date-modification')),'dado' => interface_formatar_dado(Array('dado' => $retorno_bd[$modulo['tabela']['data_modificacao']], 'formato' => 'dataHora'))); }
		if(isset($retorno_bd[$modulo['tabela']['versao']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-version')),'dado' => $retorno_bd[$modulo['tabela']['versao']]); }
		if(isset($retorno_bd[$modulo['tabela']['status']])){ $metaDados[] = Array('titulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status')),'dado' => ($retorno_bd[$modulo['tabela']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-active')).'</b></div>' : '').($retorno_bd[$modulo['tabela']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.gestor_variaveis(Array('modulo' => 'interface','id' => 'field-status-inactive')).'</b></div>' : '')); }
	} else {
		gestor_redirecionar_raiz();
	}
	
	// ===== Montar impressão dos cupons.
	
	agendamentos_impressao_cupons();
	
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
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/cupons-de-prioridade/adicionar/',
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-insert')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-insert')),
				'icon' => 'plus circle',
				'cor' => 'blue',
			),
			'status' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/?opcao=status&'.$modulo['tabela']['status'].'='.($status_atual == 'A' ? 'I' : 'A' ).'&'.$modulo['tabela']['id'].'='.$id.'&redirect='.urlencode($_GESTOR['modulo-id'].'/cupons-de-prioridade/editar/?'.$modulo['tabela']['id'].'='.$id),
				'rotulo' => ($status_atual == 'A' ? gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-desactive')) : gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-active')) ),
				'tooltip' => ($status_atual == 'A' ? gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-desactive')) : gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-active'))),
				'icon' => ($status_atual == 'A' ? 'eye' : 'eye slash' ),
				'cor' => ($status_atual == 'A' ? 'green' : 'brown' ),
			),
			'excluir' => Array(
				'url' => $_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/cupons-de-prioridade/?opcao=excluir&'.$modulo['tabela']['id'].'='.$id,
				'rotulo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'label-button-delete')),
				'tooltip' => gestor_variaveis(Array('modulo' => 'interface','id' => 'tooltip-button-delete')),
				'icon' => 'trash alternate',
				'cor' => 'red',
			),
			'imprimirCupons' => Array(
				'callback' => 'imprimirCupons',
				'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-print')),
				'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-print')),
				'icon' => 'print',
				'cor' => 'brown',
			),
		),
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

function agendamentos_cupons_de_prioridade(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Gravar Atualizações no Banco
	
	if(isset($_GESTOR['atualizar-banco'])){
		// ===== Reler URL.
		
		gestor_redirecionar_raiz();
	}
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface config finalizar opções
	
	$_GESTOR['interface']['simples']['finalizar'] = Array(
		'botoes' => Array(
			'administrar' => Array(
				'url' => '../',
				'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-button-admin')),
				'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-admin')),
				'icon' => 'calendar alternate',
				'cor' => 'orange',
			),
			'cupons' => Array(
				'url' => '',
				'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-button-coupon')),
				'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-coupon')),
				'icon' => 'certificate',
				'cor' => 'green',
			),
		),
	);
}

function agendamentos_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'administrar':
		case 'cupons':
			$_GESTOR['interface-opcao'] = 'simples';
		break;
		case 'listar':
			$_GESTOR['interface'][$_GESTOR['opcao']]['finalizar'] = Array(
				'banco' => Array(
					'nome' => $modulo['tabela']['nome'],
					'campos' => Array(
						'nome',
						'quantidade',
						'valido_de',
						'valido_ate',
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
							'id' => 'quantidade',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-quantity-label')),
							'nao_procurar' => true,
						),
						Array(
							'id' => 'valido_de',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-valid-from-label')),
							'formatar' => 'data',
							'nao_procurar' => true,
						),
						Array(
							'id' => 'valido_ate',
							'nome' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'form-valid-until-label')),
							'formatar' => 'data',
							'nao_procurar' => true,
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
					'administrar' => Array(
						'url' => '../',
						'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-button-admin')),
						'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-admin')),
						'icon' => 'calendar alternate',
						'cor' => 'orange',
					),
					'cupons' => Array(
						'url' => '',
						'rotulo' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'label-button-coupon')),
						'tooltip' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'tooltip-button-coupon')),
						'icon' => 'certificate',
						'cor' => 'green',
					),
				),
			);
		break;
	}
}

// ==== Ajax

function agendamentos_ajax_atualizar(){
	global $_GESTOR;
	
	// ===== Incluir bibliotecas.
	
	gestor_incluir_biblioteca(Array(
		'pagina',
	));
	
	// ===== Variáveis padrões iniciais.
	
	$total = 0;
	$imprimir = false;
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Pegar dados de requisição.
	
	$data = banco_escape_field($_REQUEST['data']);
	$status = banco_escape_field($_REQUEST['status']);
	
	// ===== Pegar células da tabela.
	
	$cel_nome = 'th-senha'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
	$cel_nome = 'th-email'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
	
	$cel_nome = 'cel-acompanhante'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
	$cel_nome = 'td-acompanhantes'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
	$cel_nome = 'td-senha'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
	
	$cel_nome = 'enviado'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
	$cel_nome = 'nao-enviado'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
	$cel_nome = 'td-email'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
	
	$cel_nome = 'cel-agendamento'; $cel[$cel_nome] = pagina_celula($cel_nome,false);
	
	// ===== Pegar a tabela da página.
	
	$cel_nome = 'tabela-pessoas'; $tabela = pagina_celula($cel_nome,false);
	
	// ===== Tratar cada status enviado.
	
	switch($status){
		case 'pre':
			// ===== Pegar os dados do banco.
			
			$hosts_agendamentos = banco_select(Array(
				'tabela' => 'hosts_agendamentos',
				'campos' => Array(
					'id_hosts_agendamentos',
					'id_hosts_usuarios',
					'acompanhantes',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
					." AND data='".$data."'"
					." AND status='novo'"
			));
			
			// ===== Varrer todos os agendamentos.
			
			if($hosts_agendamentos)
			foreach($hosts_agendamentos as $agendamento){
				// ===== Pegar os dados do agendamento.
				
				$id_hosts_agendamentos = $agendamento['id_hosts_agendamentos'];
				$id_hosts_usuarios = $agendamento['id_hosts_usuarios'];
				$acompanhantes = (int)$agendamento['acompanhantes'];
				
				// ===== Pegar os dados do usuário do agendamento.
				
				$hosts_usuarios = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_usuarios',
					'campos' => Array(
						'nome',
					),
					'extra' => 
						"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				$agendamentosAux = Array(
					'nome' => $hosts_usuarios['nome'],
					'acompanhantes' => $acompanhantes,
				);
				
				// ===== Pegar os dados dos acompanhantes.
				
				$hosts_agendamentos_acompanhantes = banco_select(Array(
					'tabela' => 'hosts_agendamentos_acompanhantes',
					'campos' => Array(
						'nome',
					),
					'extra' => 
						"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				$agendamentosAux['acompanhantesDados'] = $hosts_agendamentos_acompanhantes;
				
				// ===== Atualizar o total de pessoas agendadas.
				
				$total += 1+$acompanhantes;
				
				// ===== Incluir os dados do agendamento no array agendamentos.
				
				$agendamentos[] = $agendamentosAux;
			}
			
			// ===== Ordenar por nome os dados para montagem da tabela.
			
			usort($agendamentos, function($a, $b){
				return $a['nome'] <=> $b['nome'];
			});
			
			// ===== Montar tabela.
			
			if($agendamentos){
				$cel_nome = 'cel-agendamento';
				
				foreach($agendamentos as $agendamento){
					$cel_aux = $cel[$cel_nome];
					
					// ===== Incluir o nome.
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"nome",$agendamento['nome']);
					
					// ===== Popular os acompanhantes.
					
					$acompanhanteNum = 0;
					if(isset($agendamento['acompanhantesDados'])){
						$cel_aux = modelo_var_troca($cel_aux,"<!-- td-acompanhantes -->",$cel['td-acompanhantes']);
						
						foreach($agendamento['acompanhantesDados'] as $acompanhantesDados){
							$acompanhanteNum++;

							$cel_acomp = 'cel-acompanhante'; $cel_aux_2 = $cel[$cel_acomp];
							
							$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,"num",$acompanhanteNum);
							$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,"acompanhante",$acompanhantesDados['nome']);
							
							$cel_aux = modelo_var_in($cel_aux,'<!-- '.$cel_acomp.' -->',$cel_aux_2);
						}
					}
					
					$tabela = modelo_var_in($tabela,'<!-- '.$cel_nome.' -->',$cel_aux);
				}
				
				$tabela = modelo_var_troca($tabela,'<!-- '.$cel_nome.' -->','');
			} else {
				$tabela = '';
			}
		break;
		case 'aguardando':
			// ===== Pegar os dados do banco.
			
			$hosts_agendamentos = banco_select(Array(
				'tabela' => 'hosts_agendamentos',
				'campos' => Array(
					'id_hosts_agendamentos',
					'id_hosts_usuarios',
					'acompanhantes',
					'status',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
					." AND data='".$data."'"
					." AND (status='email-enviado' OR status='email-nao-enviado')"
			));
			
			// ===== Varrer todos os agendamentos.
			
			if($hosts_agendamentos)
			foreach($hosts_agendamentos as $agendamento){
				// ===== Pegar os dados do agendamento.
				
				$id_hosts_agendamentos = $agendamento['id_hosts_agendamentos'];
				$id_hosts_usuarios = $agendamento['id_hosts_usuarios'];
				$acompanhantes = (int)$agendamento['acompanhantes'];
				
				// ===== Pegar os dados do usuário do agendamento.
				
				$hosts_usuarios = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_usuarios',
					'campos' => Array(
						'nome',
					),
					'extra' => 
						"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				$agendamentosAux = Array(
					'nome' => $hosts_usuarios['nome'],
					'acompanhantes' => $acompanhantes,
					'status' => $agendamento['status'],
				);
				
				// ===== Pegar os dados dos acompanhantes.
				
				$hosts_agendamentos_acompanhantes = banco_select(Array(
					'tabela' => 'hosts_agendamentos_acompanhantes',
					'campos' => Array(
						'nome',
					),
					'extra' => 
						"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				$agendamentosAux['acompanhantesDados'] = $hosts_agendamentos_acompanhantes;
				
				// ===== Atualizar o total de pessoas agendadas.
				
				$total += 1+$acompanhantes;
				
				// ===== Incluir os dados do agendamento no array agendamentos.
				
				$agendamentos[] = $agendamentosAux;
			}
			
			// ===== Ordenar por nome os dados para montagem da tabela.
			
			usort($agendamentos, function($a, $b){
				return $a['nome'] <=> $b['nome'];
			});
			
			// ===== Montar tabela.
			
			if($agendamentos){
				$cel_nome = 'th-email'; $tabela = modelo_var_troca($tabela,'<!-- '.$cel_nome.' -->',$cel[$cel_nome]);
				
				$cel_nome = 'cel-agendamento';
				
				foreach($agendamentos as $agendamento){
					$cel_aux = $cel[$cel_nome];
					
					// ===== Incluir o status de enviado ou não enviado.
					
					$cel_aux = modelo_var_troca($cel_aux,"<!-- td-email -->",$cel['td-email']);
					
					if($agendamento['status'] == 'email-enviado'){
						$cel_aux = modelo_var_troca($cel_aux,"<!-- enviado -->",$cel['enviado']);
					} else {
						$cel_aux = modelo_var_troca($cel_aux,"<!-- nao-enviado -->",$cel['nao-enviado']);
					}
					
					// ===== Incluir o nome.
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"nome",$agendamento['nome']);
					
					// ===== Popular os acompanhantes.
					
					$acompanhanteNum = 0;
					if(isset($agendamento['acompanhantesDados'])){
						$cel_aux = modelo_var_troca($cel_aux,"<!-- td-acompanhantes -->",$cel['td-acompanhantes']);
						
						foreach($agendamento['acompanhantesDados'] as $acompanhantesDados){
							$acompanhanteNum++;

							$cel_acomp = 'cel-acompanhante'; $cel_aux_2 = $cel[$cel_acomp];
							
							$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,"num",$acompanhanteNum);
							$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,"acompanhante",$acompanhantesDados['nome']);
							
							$cel_aux = modelo_var_in($cel_aux,'<!-- '.$cel_acomp.' -->',$cel_aux_2);
						}
					}
					
					$tabela = modelo_var_in($tabela,'<!-- '.$cel_nome.' -->',$cel_aux);
				}
				
				$tabela = modelo_var_troca($tabela,'<!-- '.$cel_nome.' -->','');
			} else {
				$tabela = '';
			}
		break;
		case 'confirmados':
			// ===== Pegar os dados do banco.
			
			$hosts_agendamentos = banco_select(Array(
				'tabela' => 'hosts_agendamentos',
				'campos' => Array(
					'id_hosts_agendamentos',
					'id_hosts_usuarios',
					'acompanhantes',
					'senha',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
					." AND data='".$data."'"
					." AND status='confirmado'"
			));
			
			// ===== Varrer todos os agendamentos.
			
			if($hosts_agendamentos)
			foreach($hosts_agendamentos as $agendamento){
				// ===== Pegar os dados do agendamento.
				
				$id_hosts_agendamentos = $agendamento['id_hosts_agendamentos'];
				$id_hosts_usuarios = $agendamento['id_hosts_usuarios'];
				$acompanhantes = (int)$agendamento['acompanhantes'];
				$senha = $agendamento['senha'];
				
				// ===== Pegar os dados do usuário do agendamento.
				
				$hosts_usuarios = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_usuarios',
					'campos' => Array(
						'nome',
					),
					'extra' => 
						"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				$agendamentosAux = Array(
					'nome' => $hosts_usuarios['nome'],
					'senha' => $senha,
					'acompanhantes' => $acompanhantes,
				);
				
				// ===== Pegar os dados dos acompanhantes.
				
				$hosts_agendamentos_acompanhantes = banco_select(Array(
					'tabela' => 'hosts_agendamentos_acompanhantes',
					'campos' => Array(
						'nome',
					),
					'extra' => 
						"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				$agendamentosAux['acompanhantesDados'] = $hosts_agendamentos_acompanhantes;
				
				// ===== Atualizar o total de pessoas agendadas.
				
				$total += 1+$acompanhantes;
				
				// ===== Incluir os dados do agendamento no array agendamentos.
				
				$agendamentos[] = $agendamentosAux;
			}
			
			// ===== Ordenar por nome os dados para montagem da tabela.
			
			usort($agendamentos, function($a, $b){
				return $a['nome'] <=> $b['nome'];
			});
			
			// ===== Montar tabela.
			
			if($agendamentos){
				$cel_nome = 'th-senha'; $tabela = modelo_var_troca($tabela,'<!-- '.$cel_nome.' -->',$cel[$cel_nome]);
				
				$cel_nome = 'cel-agendamento';
				
				foreach($agendamentos as $agendamento){
					$cel_aux = $cel[$cel_nome];
					
					// ===== Incluir a senha.
					
					$cel_aux = modelo_var_troca($cel_aux,"<!-- td-senha -->",$cel['td-senha']);
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"senha",$agendamento['senha']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"nome",$agendamento['nome']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"visto",'');
					
					// ===== Popular os acompanhantes.
					
					$acompanhanteNum = 0;
					if(isset($agendamento['acompanhantesDados'])){
						$cel_aux = modelo_var_troca($cel_aux,"<!-- td-acompanhantes -->",$cel['td-acompanhantes']);
						
						foreach($agendamento['acompanhantesDados'] as $acompanhantesDados){
							$acompanhanteNum++;
							
							$cel_acomp = 'cel-acompanhante'; $cel_aux_2 = $cel[$cel_acomp];
							
							$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,"num",$acompanhanteNum);
							$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,"acompanhante",$acompanhantesDados['nome']);
							
							$cel_aux = modelo_var_in($cel_aux,'<!-- '.$cel_acomp.' -->',$cel_aux_2);
						}
					}
					
					$tabela = modelo_var_in($tabela,'<!-- '.$cel_nome.' -->',$cel_aux);
				}
				
				$tabela = modelo_var_troca($tabela,'<!-- '.$cel_nome.' -->','');
			} else {
				$tabela = '';
			}
			
			// ===== Montar tabela.
			
			$maximoAcompanhantes = 0;
			
			if($agendamentos){
				$cel_nome = 'th-senha'; $tabela = modelo_var_troca($tabela,'<!-- '.$cel_nome.' -->',$cel[$cel_nome]);
				
				$cel_nome = 'cel-agendamento';
				
				foreach($agendamentos as $agendamento){
					$cel_aux = $cel[$cel_nome];
					
					// ===== Incluir a senha.
					
					$cel_aux = modelo_var_troca($cel_aux,"<!-- td-senha -->",$cel['td-senha']);
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"senha",$agendamento['senha']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"nome",$agendamento['nome']);
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"visto",'');
					
					// ===== Popular os acompanhantes.
					
					$acompanhanteNum = 0;
					if(isset($agendamento['acompanhantesDados'])){
						$cel_aux = modelo_var_troca($cel_aux,"<!-- td-acompanhantes -->",$cel['td-acompanhantes']);
						
						foreach($agendamento['acompanhantesDados'] as $acompanhantesDados){
							$acompanhanteNum++;
							
							$cel_acomp = 'cel-acompanhante'; $cel_aux_2 = $cel[$cel_acomp];
							
							$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,"num",$acompanhanteNum);
							$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,"acompanhante",$acompanhantesDados['nome']);
							
							$cel_aux = modelo_var_in($cel_aux,'<!-- '.$cel_acomp.' -->',$cel_aux_2);
						}
					}
					
					if($acompanhanteNum > $maximoAcompanhantes){
						$maximoAcompanhantes = $acompanhanteNum;
					}
					
					$tabela = modelo_var_in($tabela,'<!-- '.$cel_nome.' -->',$cel_aux);
				}
				
				$tabela = modelo_var_troca($tabela,'<!-- '.$cel_nome.' -->','');
			} else {
				$tabela = '';
			}
			
			// ===== Impressão opções.
			
			if($total > 0){
				// ===== Pegar as configurações das agendamentos.
				
				gestor_incluir_biblioteca('configuracao');
				
				$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-agendamentos'));
				
				// ===== Pegar o layout de impressão.
				
				$layoutImpressao = $config['agendamentos-impressao'];
				
				// ===== Montar tabela de impressão.
				
				if($agendamentos){
					$tabelaImpressao = $layoutImpressao;
					
					$cel_nome = 'th-acompanhantes'; $cel[$cel_nome] = modelo_tag_val($tabelaImpressao,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $tabelaImpressao = modelo_tag_in($tabelaImpressao,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
					$cel_nome = 'td-acompanhantes'; $cel[$cel_nome] = modelo_tag_val($tabelaImpressao,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $tabelaImpressao = modelo_tag_in($tabelaImpressao,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
					$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($tabelaImpressao,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $tabelaImpressao = modelo_tag_in($tabelaImpressao,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
					
					for($i=1;$i<=$maximoAcompanhantes;$i++){
						$cel_aux = $cel['th-acompanhantes'];
						
						$cel_aux = modelo_var_troca($cel_aux,"#th-acompanhante#",'Acompanhante '.$i);
						
						$tabelaImpressao = modelo_var_in($tabelaImpressao,'<!-- th-acompanhantes -->',$cel_aux);
					}
					
					$tabelaImpressao = modelo_var_troca($tabelaImpressao,'<!-- th-acompanhantes -->','');
					
					$cel_nome = 'cel';
					
					foreach($agendamentos as $agendamento){
						$cel_aux = $cel[$cel_nome];
						
						// ===== Incluir a senha e o nome.
						
						$cel_aux = modelo_var_troca($cel_aux,"#senha#",$agendamento['senha']);
						$cel_aux = modelo_var_troca($cel_aux,"#nome#",$agendamento['nome']);
						
						// ===== Popular os acompanhantes.
						
						$acompanhanteNum = 0;
						if(isset($agendamento['acompanhantesDados'])){
							foreach($agendamento['acompanhantesDados'] as $acompanhantesDados){
								$acompanhanteNum++;
								
								$cel_aux_2 = $cel['td-acompanhantes'];
								
								$cel_aux_2 = modelo_var_troca($cel_aux_2,"#td-acompanhantes#",$acompanhantesDados['nome']);
								
								$cel_aux = modelo_var_in($cel_aux,'<!-- td-acompanhantes -->',$cel_aux_2);
							}
						}
						
						for($i=($acompanhanteNum+1);$i<=$maximoAcompanhantes;$i++){
							$cel_aux_2 = $cel['td-acompanhantes'];
							
							$cel_aux_2 = modelo_var_troca($cel_aux_2,"#td-acompanhantes#",'');
							
							$cel_aux = modelo_var_in($cel_aux,'<!-- td-acompanhantes -->',$cel_aux_2);
						}
						
						$cel_aux = modelo_var_troca($cel_aux,'<!-- td-acompanhantes -->','');
						
						$tabelaImpressao = modelo_var_in($tabelaImpressao,'<!-- '.$cel_nome.' -->',$cel_aux);
					}
					
					$tabelaImpressao = modelo_var_troca($tabelaImpressao,'<!-- '.$cel_nome.' -->','');
				} else {
					$tabelaImpressao = '';
				}
				
				// ===== Variáveis padrões de impressão.
				
				$imprimir = true;
				
				$tabelaAux = $tabelaImpressao;
				
				// ===== Incluir a tabela no buffer de impressão.
				
				gestor_incluir_biblioteca(Array(
					'formato',
					'comunicacao',
				));
				
				// ===== Formatar data.
				
				$dataStr = formato_dado_para('data',$data);
				
				// ===== Pegar o componente 'impressao-cabecalho'.
				
				$impressaoCabecalho = gestor_componente(Array(
					'id' => 'impressao-cabecalho',
				));
				
				$impressaoCabecalho = modelo_var_troca($impressaoCabecalho,"#data#",$dataStr);
				$impressaoCabecalho = modelo_var_troca($impressaoCabecalho,"#total#",$total);
				
				$tabelaAux = $impressaoCabecalho . $tabelaAux;
				
				// ===== Incluir a tabela no buffer de impressão.
				
				comunicacao_impressao(Array(
					'titulo' => 'Agendamentos Confirmados - '.$dataStr,
					'pagina' => $tabelaAux,
				));
			}
		break;
		case 'finalizados':
			// ===== Pegar os dados do banco.
			
			$hosts_agendamentos = banco_select(Array(
				'tabela' => 'hosts_agendamentos',
				'campos' => Array(
					'id_hosts_agendamentos',
					'id_hosts_usuarios',
					'acompanhantes',
				),
				'extra' => 
					"WHERE id_hosts='".$id_hosts."'"
					." AND data='".$data."'"
					." AND status='finalizado'"
			));
			
			// ===== Varrer todos os agendamentos.
			
			if($hosts_agendamentos)
			foreach($hosts_agendamentos as $agendamento){
				// ===== Pegar os dados do agendamento.
				
				$id_hosts_agendamentos = $agendamento['id_hosts_agendamentos'];
				$id_hosts_usuarios = $agendamento['id_hosts_usuarios'];
				$acompanhantes = (int)$agendamento['acompanhantes'];
				
				// ===== Pegar os dados do usuário do agendamento.
				
				$hosts_usuarios = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_usuarios',
					'campos' => Array(
						'nome',
					),
					'extra' => 
						"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				$agendamentosAux = Array(
					'nome' => $hosts_usuarios['nome'],
					'acompanhantes' => $acompanhantes,
				);
				
				// ===== Pegar os dados dos acompanhantes.
				
				$hosts_agendamentos_acompanhantes = banco_select(Array(
					'tabela' => 'hosts_agendamentos_acompanhantes',
					'campos' => Array(
						'nome',
					),
					'extra' => 
						"WHERE id_hosts_agendamentos='".$id_hosts_agendamentos."'"
						." AND id_hosts_usuarios='".$id_hosts_usuarios."'"
						." AND id_hosts='".$id_hosts."'"
				));
				
				$agendamentosAux['acompanhantesDados'] = $hosts_agendamentos_acompanhantes;
				
				// ===== Atualizar o total de pessoas agendadas.
				
				$total += 1+$acompanhantes;
				
				// ===== Incluir os dados do agendamento no array agendamentos.
				
				$agendamentos[] = $agendamentosAux;
			}
			
			// ===== Ordenar por nome os dados para montagem da tabela.
			
			usort($agendamentos, function($a, $b){
				return $a['nome'] <=> $b['nome'];
			});
			
			// ===== Montar tabela.
			
			if($agendamentos){
				$cel_nome = 'cel-agendamento';
				
				foreach($agendamentos as $agendamento){
					$cel_aux = $cel[$cel_nome];
					
					// ===== Incluir o nome.
					
					$cel_aux = pagina_celula_trocar_variavel_valor($cel_aux,"nome",$agendamento['nome']);
					
					// ===== Popular os acompanhantes.
					
					$acompanhanteNum = 0;
					if(isset($agendamento['acompanhantesDados'])){
						$cel_aux = modelo_var_troca($cel_aux,"<!-- td-acompanhantes -->",$cel['td-acompanhantes']);
						
						foreach($agendamento['acompanhantesDados'] as $acompanhantesDados){
							$acompanhanteNum++;
							
							$cel_acomp = 'cel-acompanhante'; $cel_aux_2 = $cel[$cel_acomp];
							
							$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,"num",$acompanhanteNum);
							$cel_aux_2 = pagina_celula_trocar_variavel_valor($cel_aux_2,"acompanhante",$acompanhantesDados['nome']);
							
							$cel_aux = modelo_var_in($cel_aux,'<!-- '.$cel_acomp.' -->',$cel_aux_2);
						}
					}
					
					$tabela = modelo_var_in($tabela,'<!-- '.$cel_nome.' -->',$cel_aux);
				}
				
				$tabela = modelo_var_troca($tabela,'<!-- '.$cel_nome.' -->','');
			} else {
				$tabela = '';
			}
		break;
		default:
			$tabela = '';
	}
	
	// ===== Retornar os dados para atualização no cliente.
	
	$_GESTOR['ajax-json'] = Array(
		'tabela' => $tabela,
		'total' => $total,
		'imprimir' => $imprimir,
		'status' => 'OK',
	);
}

function agendamentos_ajax_imprimir_cupons(){
	global $_GESTOR;
	
	// ===== Incluir bibliotecas.
	
	gestor_incluir_biblioteca(Array(
		'formato',
		'comunicacao',
		'configuracao',
	));
	
	// ===== Identificador do host.
	
	$id_hosts = $_GESTOR['host-id'];
	
	// ===== Pegar dados de requisição.
	
	$id = banco_escape_field($_REQUEST['id']);
	
	// ===== Pegar os cupons do banco de dados.
	
	$hosts_conjunto_cupons_prioridade = banco_select(Array(
		'unico' => true,
		'tabela' => 'hosts_conjunto_cupons_prioridade',
		'campos' => Array(
			'id_hosts_conjunto_cupons_prioridade',
			'nome',
			'quantidade',
			'valido_de',
			'valido_ate',
		),
		'extra' => 
			"WHERE id='".$id."'"
			." AND id_hosts='".$id_hosts."'"
			." AND status='A'"
	));
	
	// ===== Caso não exista o registro, retornar erro com a mensagem.
	
	if(!$hosts_conjunto_cupons_prioridade){
		$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-cupons-not-found'));
		
		$_GESTOR['ajax-json'] = Array(
			'msg' => $alerta,
			'status' => 'ERRO',
		);
		
		return;
	}
	
	// ===== Iniciar variáveis.
	
	$hoje = date('Y-m-d');
	
	$id_hosts_conjunto_cupons_prioridade = $hosts_conjunto_cupons_prioridade['id_hosts_conjunto_cupons_prioridade'];
	$valido_de = $hosts_conjunto_cupons_prioridade['valido_de'];
	$valido_ate = $hosts_conjunto_cupons_prioridade['valido_ate'];
	$nome = $hosts_conjunto_cupons_prioridade['nome'];
	$quantidade = $hosts_conjunto_cupons_prioridade['quantidade'];
	
	// ===== Verificar se os cupons estão dentro do prazo de validade.
	
	if(strtotime($hoje) > strtotime($valido_ate)){
		$alerta = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'alert-cupons-expired'));
		
		$_GESTOR['ajax-json'] = Array(
			'msg' => $alerta,
			'status' => 'ERRO',
		);
		
		return;
	}
	
	// ===== Pegar os códigos dos cupons no banco de dados.
	
	$hosts_cupons_prioridade = banco_select(Array(
		'tabela' => 'hosts_cupons_prioridade',
		'campos' => Array(
			'codigo',
		),
		'extra' => 
			"WHERE id_hosts_conjunto_cupons_prioridade='".$id_hosts_conjunto_cupons_prioridade."'"
			." AND id_hosts='".$id_hosts."'"
	));
	
	// ===== Pegar o componente de impressão da tabela de cupons.
	
	$tabela = gestor_componente(Array(
		'id' => 'tabela-cupons-prioridade',
	));
	
	// ===== Pegar células da tabela.
	
	$cel_nome = 'cel'; $cel[$cel_nome] = modelo_tag_val($tabela,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $tabela = modelo_tag_in($tabela,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	// ===== Pegar configurações do host.
	
	$config = configuracao_hosts_variaveis(Array('modulo' => 'configuracoes-agendamentos'));
	
	$titulo = $config['titulo-estabelecimento'];
	$descricao = $config['cupom-prioridade-descricao'];
	
	// ===== Formatar a validade.
	
	$validade_de = formato_dado_para('data',$valido_de);
	$validade = formato_dado_para('data',$valido_ate);
	
	// ===== Montar a tabela com todos os códigos.
	
	if($hosts_cupons_prioridade)
	foreach($hosts_cupons_prioridade as $cupom){
		$cel_aux = $cel[$cel_nome];
		
		$cel_aux = modelo_var_troca($cel_aux,"#titulo#",$titulo);
		$cel_aux = modelo_var_troca($cel_aux,"#descricao#",$descricao);
		$cel_aux = modelo_var_troca($cel_aux,"#validade#",$validade);
		$cel_aux = modelo_var_troca($cel_aux,"#codigo#",$cupom['codigo']);
		
		$tabela = modelo_var_in($tabela,'<!-- '.$cel_nome.' -->',$cel_aux);
	}
	$tabela = modelo_var_troca($tabela,'<!-- '.$cel_nome.' -->','');
	
	// ===== Incluir a tabela no buffer de impressão.
	
	comunicacao_impressao(Array(
		'titulo' => 'Cupons de Prioridade: '.$nome.' - Qtd: '.$quantidade.' - Válido de '.$validade_de.' até '.$validade,
		'pagina' => $tabela,
	));
	
	// ===== Retornar os dados para atualização no cliente.
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'OK',
	);
}

// ==== Start

function agendamentos_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			case 'atualizar': agendamentos_ajax_atualizar(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		agendamentos_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'administrar': agendamentos_administrar(); break;
			case 'adicionar': agendamentos_cupons_adicionar(); break;
			case 'editar': agendamentos_cupons_editar(); break;
		}
		
		interface_finalizar();
	}
}

agendamentos_start();

?>