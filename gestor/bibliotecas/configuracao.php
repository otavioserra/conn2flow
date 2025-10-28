<?php
/**
 * Biblioteca de Configuração
 *
 * Gerencia as configurações do sistema, incluindo variáveis de módulos,
 * configurações de hosts e administração de parâmetros do sistema.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.2.0
 */

global $_GESTOR;

$_GESTOR['biblioteca-configuracao']							=	Array(
	'versao' => '1.2.0',
	'camposTipos' => Array(
		Array(	'texto' => gestor_variaveis(Array('modulo' => 'configuracao','id' => 'variable-type-string-label')),				'valor' => 'string',			),
		Array(	'texto' => gestor_variaveis(Array('modulo' => 'configuracao','id' => 'variable-type-text-label')),					'valor' => 'text',				),
		Array(	'texto' => gestor_variaveis(Array('modulo' => 'configuracao','id' => 'variable-type-bool-label')),					'valor' => 'bool',				),
		Array(	'texto' => gestor_variaveis(Array('modulo' => 'configuracao','id' => 'variable-type-number-label')),				'valor' => 'number',			),
		Array(	'texto' => gestor_variaveis(Array('modulo' => 'configuracao','id' => 'variable-type-quantidade-label')),			'valor' => 'quantidade',		),
		Array(	'texto' => gestor_variaveis(Array('modulo' => 'configuracao','id' => 'variable-type-dinheiro-label')),				'valor' => 'dinheiro',			),
		Array(	'texto' => gestor_variaveis(Array('modulo' => 'configuracao','id' => 'variable-type-css-label')),					'valor' => 'css',				),
		Array(	'texto' => gestor_variaveis(Array('modulo' => 'configuracao','id' => 'variable-type-js-label')),					'valor' => 'js',				),
		Array(	'texto' => gestor_variaveis(Array('modulo' => 'configuracao','id' => 'variable-type-html-label')),					'valor' => 'html',				),
		Array(	'texto' => gestor_variaveis(Array('modulo' => 'configuracao','id' => 'variable-type-tinymce-label')),				'valor' => 'tinymce',			),
		Array(	'texto' => gestor_variaveis(Array('modulo' => 'configuracao','id' => 'variable-type-datas-multiplas-label')),		'valor' => 'datas-multiplas',	),
		Array(	'texto' => gestor_variaveis(Array('modulo' => 'configuracao','id' => 'variable-type-data-label')),					'valor' => 'data',				),
		Array(	'texto' => gestor_variaveis(Array('modulo' => 'configuracao','id' => 'variable-type-data-hora-label')),				'valor' => 'data-hora',			),
	),
);

// ===== Funções auxiliares

// ===== Funções principais

/**
 * Salva as configurações de administração de um módulo.
 *
 * Função principal que processa e salva as configurações de variáveis de um módulo,
 * comparando o estado anterior com o novo, registrando alterações no histórico e
 * gerenciando inserções, atualizações e exclusões de variáveis.
 *
 * @global array $_GESTOR Sistema global de configurações.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['modulo'] Módulo alvo para filtrar as variáveis (obrigatório).
 * @param string $params['linguagemCodigo'] Linguagem das variáveis (obrigatório).
 * @param array $params['tabela'] Definições da tabela onde será atualizado o histórico (obrigatório).
 * 
 * @return void
 */
function configuracao_administracao_salvar($params = false){
	global $_GESTOR;
	
	// Extrair parâmetros do array para variáveis locais
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Validar parâmetros obrigatórios
	
	if(isset($modulo) && isset($linguagemCodigo) && isset($tabela)){
		// ===== Buscar estado anterior do banco de dados
		// Armazena snapshot das variáveis existentes para detectar mudanças
		
		$banco_antes = Array();
		
		// Buscar todas as variáveis do módulo e linguagem especificados
		$variaveis = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_variaveis',
				'id',
				'valor',
				'tipo',
				'grupo',
				'descricao',
			))
			,
			"variaveis",
			"WHERE linguagem_codigo='".$linguagemCodigo."'"
			." AND modulo='".$modulo."'"
			." ORDER BY id ASC"
		);
		
		// Armazenar valores anteriores indexados por ID para comparação
		if($variaveis){
			foreach($variaveis as $variavel){
				$banco_antes[$variavel['id_variaveis']] = Array(
					'id' => $variavel['id'],
					'valor' => $variavel['valor'],
					'grupo' => $variavel['grupo'],
					'tipo' => $variavel['tipo'],
					'descricao' => $variavel['descricao'],
				);
			}
		}
		
		// ===== Processar todas as variáveis enviadas via formulário
		// Itera sobre os inputs do usuário para atualizar ou inserir variáveis
		
		$variaveisTotal = (int)banco_escape_field($_REQUEST['variaveis-total']);
		
		for($i=0;$i<$variaveisTotal;$i++){
			// Coletar dados do formulário para esta variável
			$id = (isset($_REQUEST['id-'.$i]) ? $_REQUEST['id-'.$i] : '');
			$grupo = (isset($_REQUEST['grupo-'.$i]) ? $_REQUEST['grupo-'.$i] : '');
			$descricao = (isset($_REQUEST['descricao-'.$i]) ? $_REQUEST['descricao-'.$i] : '');
			$tipo = (isset($_REQUEST['tipo-'.$i]) ? $_REQUEST['tipo-'.$i] : '');
			$valor = $_REQUEST['valor-'.$i'];
			$ref = $_REQUEST['ref-'.$i];
			
			// Verificar se a variável já existe no banco
			if(isset($banco_antes[$ref])){
				$banco_antes[$ref]['verificado'] = true;
				
				// Se tem ID definido, atualizar todos os campos caso tenham mudado
				if(existe($id)){
					if(
						$banco_antes[$ref]['id'] != $id || 
						$banco_antes[$ref]['grupo'] != $grupo || 
						$banco_antes[$ref]['descricao'] != $descricao || 
						$banco_antes[$ref]['tipo'] != $tipo || 
						$banco_antes[$ref]['valor'] != $valor
					){
						banco_update_campo('id',$id);
						banco_update_campo('grupo',$grupo);
						banco_update_campo('descricao',$descricao);
						banco_update_campo('tipo',$tipo);
						banco_update_campo('valor',$valor);
						
						banco_update_executar('variaveis',"WHERE id_variaveis='".$ref."'");
						
						$alterouVariavel = true;
					}
				// Se não tem ID definido, atualizar apenas o valor
				} else {
					if(
						$banco_antes[$ref]['valor'] != $valor
					){
						banco_update_campo('valor',$valor);
						
						banco_update_executar('variaveis',"WHERE id_variaveis='".$ref."'");
						
						$alterouVariavel = true;
					}
				}
			// Se é uma nova variável (não existia no banco antes)
			} else if(existe($id)){
				banco_insert_name_campo('linguagem_codigo',$linguagemCodigo);
				banco_insert_name_campo('modulo',$modulo);
				banco_insert_name_campo('id',$id);
				banco_insert_name_campo('tipo',$tipo);
				
				if(existe($grupo))banco_insert_name_campo('grupo',$grupo);
				if(existe($descricao))banco_insert_name_campo('descricao',$descricao);
				if(existe($valor))banco_insert_name_campo('valor',$valor);
				
				banco_insert_name
				(
					banco_insert_name_campos(),
					"variaveis"
				);
				
				$alterouVariavel = true;
			}
		}
		
		// ===== Deletar variáveis que não foram verificadas
		// Se uma variável existia no banco mas não veio no formulário, ela foi removida
		foreach($banco_antes as $ref => $campo){
			if(!isset($campo['verificado'])){
				banco_delete
				(
					"variaveis",
					"WHERE id_variaveis='".$ref."'"
				);
				
				$alterouVariavel = true;
			}
		}
		
		// ===== Registrar alterações e atualizar metadados
		// Se houve qualquer mudança nas variáveis, atualizar histórico e versão
		
		if(isset($alterouVariavel)){
			$alteracoes[] = Array('campo' => 'module-variables');
			
			// ===== Incrementar versão do módulo e atualizar data de modificação
			
			$editar = Array(
				'tabela' => $tabela['nome'],
				'extra' => "WHERE ".$tabela['id']."='".$modulo."' AND ".$tabela['status']."!='D'",
			);
			
			// Incrementar versão e atualizar timestamp
			$campo_nome = $tabela['versao']; $editar['dados'][] = $campo_nome." = ".$campo_nome." + 1";
			$campo_nome = $tabela['data_modificacao']; $editar['dados'][] = $campo_nome."=NOW()";
			
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
	}
}

/**
 * Exibe o widget de administração de configurações de um módulo.
 *
 * Gera a interface HTML completa para administração de variáveis de configuração,
 * incluindo formulários editáveis com diferentes tipos de campos (string, texto,
 * bool, número, dinheiro, CSS, JS, HTML, TinyMCE, datas, etc).
 *
 * @global array $_GESTOR Sistema global de configurações.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['marcador'] Marcador textual onde será incluído o widget (obrigatório).
 * @param string $params['modulo'] Módulo alvo para filtrar as variáveis (obrigatório).
 * @param string $params['linguagemCodigo'] Linguagem das variáveis (obrigatório).
 * 
 * @return void
 */
function configuracao_administracao($params = false){
	global $_GESTOR;
	
	// Extrair parâmetros do array para variáveis locais
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Validar parâmetros obrigatórios
	
	if(isset($marcador) && isset($modulo) && isset($linguagemCodigo)){
		// ===== Incluir bibliotecas necessárias
		
		gestor_incluir_biblioteca('html');
		gestor_incluir_biblioteca('interface');
		
		// ===== Obter dados da biblioteca de configuração
		
		$biblioteca = $_GESTOR['biblioteca-configuracao'];
		
		// ===== Carregar template do widget de configuração
		
		$widget = gestor_componente(Array(
			'id' => 'configuracao-widget',
		));
		
		// ===== Carregar template dos campos de formulário
		
		$campos = gestor_componente(Array(
			'id' => 'configuracao-campos',
		));
		
		// ===== Buscar variáveis do módulo no banco de dados
		
		$variaveis = banco_select(Array(
			'tabela' => 'variaveis',
			'campos' => Array(
				'id_variaveis',
				'id',
				'valor',
				'tipo',
				'grupo',
				'descricao',
			),
			'extra' => 
				"WHERE linguagem_codigo='".$linguagemCodigo."'"
				." AND modulo='".$modulo."'"
				." ORDER BY id ASC"
		));
		
		// ===== Preparar templates para cada tipo de campo
		// Extrai os templates específicos para string, text, bool, number, etc.
		
		if($biblioteca['camposTipos'])
		foreach($biblioteca['camposTipos'] as $campoTipo){
			$cel_nome = $campoTipo['valor']; $campo[$cel_nome] = modelo_tag_val($campos,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
		}
		
		// ===== Extrair template do item individual
		
		$cel_nome = 'item'; $cel[$cel_nome] = modelo_tag_val($widget,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $widget = modelo_tag_in($widget,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		// ===== Montar HTML de todas as variáveis
		// Itera pelas variáveis do banco e gera o HTML de cada campo
		
		if($variaveis){
			$count = 0;
			
			foreach($variaveis as $variavel){
				$cel_aux = $cel[$cel_nome];
				
				// Substituir variáveis do template com dados do banco
				$cel_aux = modelo_var_troca($cel_aux,"#variavelID#",$variavel['id_variaveis']);
				$cel_aux = modelo_var_troca($cel_aux,"#variavelNum#",(string)$count);
				$cel_aux = modelo_var_troca($cel_aux,"#variavelTipo#",$variavel['tipo']);
				$cel_aux = modelo_var_troca($cel_aux,"#variavelNome#",$variavel['id']);
				
				// ===== Mostrar ou esconder campo descrição conforme existência
				
				if($variavel['descricao']){
					$cel_aux = modelo_var_troca($cel_aux,"#variavelDescricao#",$variavel['descricao']);
				} else {
					html_iniciar(Array('valor' => $cel_aux));
					
					html_adicionar_classe(Array(
						'consulta' => 'variavelDescricaoCont',
						'classe' => 'escondido',
					));
					
					$cel_aux = html_finalizar();
					
					$cel_aux = modelo_var_troca($cel_aux,"#variavelDescricao#",'');
				}
				
				// ===== Mostrar ou esconder campo grupo conforme existência
				
				if($variavel['grupo']){
					$cel_aux = modelo_var_troca($cel_aux,"#variavelGrupo#",$variavel['grupo']);
				} else {
					html_iniciar(Array('valor' => $cel_aux));
					
					html_adicionar_classe(Array(
						'consulta' => 'variavelGrupoCont',
						'classe' => 'escondido',
					));
					
					$cel_aux = html_finalizar();
					
					$cel_aux = modelo_var_troca($cel_aux,"#variavelGrupo#",'');
				}
				
				// ===== Incluir o campo específico conforme tipo da variável
				
				$campo_aux = $campo[$variavel['tipo']];
				
				// ===== Popular dados específicos conforme tipo do campo
				// Tratamento especial para bool, string e outros tipos
				
				switch($variavel['tipo']){
					case 'bool':
						// Checkbox: remove checked se valor for falso
						if(!$variavel['valor']){
							$campo_aux = modelo_var_troca($campo_aux," checked",'');
						}
					break;
					case 'string':
						// String: escapar HTML para evitar XSS
						$campo_aux = modelo_var_troca($campo_aux,"#value-valor#",($variavel['valor'] ? htmlspecialchars($variavel['valor']) : ''));
					break;
					default:
						// Outros tipos: inserir valor diretamente
						$campo_aux = modelo_var_troca($campo_aux,"#value-valor#",($variavel['valor'] ? $variavel['valor'] : ''));
				}
				
				// ===== Incluir o campo renderizado na célula
				
				$cel_aux = modelo_var_troca($cel_aux,"#variavelValor#",$campo_aux);
				
				// ===== Popular referências dos inputs para identificação no formulário
				
				$cel_aux = modelo_var_troca($cel_aux,"#value-num#",$count);
				$cel_aux = modelo_var_troca($cel_aux,"#ref-num#",$count);
				$cel_aux = modelo_var_troca($cel_aux,"#ref-valor#",$variavel['id_variaveis']);
				
				// ===== Incrementar contador
				
				$count++;
				
				// ===== Incluir item renderizado no widget
				
				$widget = modelo_var_in($widget,'<!-- '.$cel_nome.' -->',$cel_aux);
			}
			
			$widget = modelo_var_troca($widget,'<!-- '.$cel_nome.' -->','');
			
			// ===== Incluir quantidade total de variáveis
			
			$widget = modelo_var_troca($widget,"#variaveis-total#",$count);
		} else {
			// ===== Se não houver variáveis, quantidade zerada
			
			$widget = modelo_var_troca($widget,"#variaveis-total#",'0');
			
			// ===== Esconder botão Adicionar quando não há variáveis (estética)
			
			html_iniciar(Array('valor' => $widget));
			
			html_adicionar_classe(Array(
				'consulta' => 'componenteAdicionarBaixo',
				'classe' => 'escondido',
			));
			
			$widget = html_finalizar();
		}
		
		// ===== Incluir templates dos campos no widget
		
		$widget = modelo_var_troca($widget,"#campos-modelos#",$campos);
		
		// ===== Inserir widget renderizado na página
		
		$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],$marcador,$widget);
		
		// ===== Incluir seletor de tipos para funcionalidade Adicionar/Editar
		
		$formulario['campos'] = Array(
			Array(
				'tipo' => 'select',
				'id' => 'tipo',
				'nome' => 'tipo',
				'selectClass' => 'tipo',
				'procurar' => true,
				'valor_selecionado' => 'string',
				'placeholder' => gestor_variaveis(Array('modulo' => 'configuracao','id' => 'variavel-tipo-placeholder')),
				'dados' => $biblioteca['camposTipos'],
			)
		);
		
		interface_formulario_campos($formulario);
		
		$formulario['campos'] = Array(
			Array(
				'tipo' => 'select',
				'id' => 'tipo',
				'nome' => 'tipo',
				'selectClass' => 'tipo',
				'procurar' => true,
				'valor_selecionado' => 'string',
				'placeholder' => gestor_variaveis(Array('modulo' => 'configuracao','id' => 'variavel-tipo-placeholder')),
				'dados' => $biblioteca['camposTipos'],
			)
		);
		
		interface_formulario_campos($formulario);
		
		// ===== Incluir variáveis globais do módulo configuracao.
		
		$_GESTOR['paginas-variaveis']['configuracao'] = true;
		
		// ===== Inclusão do jQuery-Mask-Plugin
		
		$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'jQuery-Mask-Plugin-v1.14.16/jquery.mask.min.js"></script>';
		
		// ===== Inclusão do TinyMCE
		
		$_GESTOR['javascript'][] = '<script src="https://cdn.tiny.cloud/1/puqfgloszrueuf7nkzrlzxqbc0qihojtiq46oikukhty0jw9/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>';
		
		// ===== Inclusão do CodeMirror
		
		gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/codemirror.min.css" />');
		gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/theme/tomorrow-night-bright.css" />');
		gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/dialog/dialog.css" />');
		gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/display/fullscreen.css" />');
		gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/matchesonscrollbar.css" />');
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/codemirror.min.js"></script>');
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/selection/active-line.js"></script>');
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/dialog/dialog.js"></script>');
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/searchcursor.js"></script>');
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/search.js"></script>');
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/scroll/annotatescrollbar.js"></script>');
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/matchesonscrollbar.js"></script>');
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/search/jump-to-line.js"></script>');
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/edit/matchbrackets.js"></script>');
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/addon/display/fullscreen.js"></script>');
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/xml/xml.js"></script>');
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/css/css.js"></script>');
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/javascript/javascript.js"></script>');
		gestor_pagina_javascript_incluir('<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.20/mode/htmlmixed/htmlmixed.js"></script>');
		
		// ===== Inclusão configuracao javascript
		
		$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'configuracao/configuracao.js?v='.$_GESTOR['biblioteca-configuracao']['versao'].'"></script>';
		
		// ===== Configuração Javascript Vars
		
		if(!isset($_GESTOR['javascript-vars']['configuracao'])){
			$_GESTOR['javascript-vars']['configuracao'] = Array();
		}
		
		// ===== Incluir modal de confirmação.
		
		interface_componentes_incluir(Array(
			'componente' => Array(
				'modal-delecao',
			)
		));
	}
}

/**
 * Salva as configurações de hosts para variáveis de um módulo.
 *
 * Função especializada que gerencia configurações específicas por host,
 * permitindo que cada host tenha suas próprias configurações personalizadas
 * para variáveis de módulos. Suporta filtragem por grupos e associação com plugins.
 *
 * @global array $_GESTOR Sistema global de configurações.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['modulo'] Módulo alvo para filtrar as variáveis (obrigatório).
 * @param string $params['linguagemCodigo'] Linguagem das variáveis (obrigatório).
 * @param array $params['tabela'] Definições da tabela onde será atualizado o histórico (obrigatório).
 * @param array $params['grupos'] Grupos alvos para filtrar as variáveis (opcional).
 * @param string $params['plugin'] Identificador do plugin relacionado (opcional).
 * 
 * @return array Array de retorno com informações do processamento.
 */
function configuracao_hosts_salvar($params = false){
	global $_GESTOR;
	
	// Extrair parâmetros do array para variáveis locais
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Inicializar array de retorno
	
	$retorno = Array();
	
	if(isset($modulo) && isset($linguagemCodigo) && isset($tabela)){
		// ===== Montar SQL de filtragem por grupos (se fornecido)
		
		$gruposSQL = '';
		
		if(isset($grupos)){
			$gruposSQL = ' AND (';
			
			foreach($grupos as $grupo){
				$gruposSQL .= (!isset($primeiro) ? '':' OR ') . "grupo='".$grupo."'";
				$primeiro = true;
			}
			
			$gruposSQL .= ')';
		}
		
		// ===== Banco antes de atualizar.
		
		$banco_antes = Array();
		$banco_antes_hosts = Array();
		
		$variaveis = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_variaveis',
				'id',
				'tipo',
				'grupo',
				'valor',
			))
			,
			"variaveis",
			"WHERE linguagem_codigo='".$linguagemCodigo."'"
			." AND modulo='".$modulo."'"
			. $gruposSQL
			." ORDER BY id ASC"
		);
		
		if($variaveis){
			foreach($variaveis as $variavel){
				$banco_antes[$variavel['id_variaveis']] = Array(
					'id' => $variavel['id'],
					'tipo' => $variavel['tipo'],
					'grupo' => $variavel['grupo'],
					'valor' => $variavel['valor'],
				);
			}
		}
		
		$hosts_variaveis = banco_select(Array(
			'tabela' => 'hosts_variaveis',
			'campos' => Array(
				'id_hosts_variaveis',
				'id',
				'valor',
			),
			'extra' => 
				"WHERE linguagem_codigo='".$linguagemCodigo."'"
				." AND modulo='".$modulo."'"
				. $gruposSQL
				." AND id_hosts='".$_GESTOR['host-id']."'"
				." ORDER BY id ASC"
		));
		
		if($hosts_variaveis){
			foreach($hosts_variaveis as $hosts_variavel){
				$banco_antes_hosts[$hosts_variavel['id_hosts_variaveis']] = Array(
					'id' => $hosts_variavel['id'],
					'valor' => $hosts_variavel['valor'],
				);
			}
		}
		
		// ===== Alteração TXT definição.
		
		$alteracao_txt = '';
		
		// ===== Varrer todos os inputs enviados
		
		$variaveisTotal = (int)banco_escape_field($_REQUEST['variaveis-total']);
		
		for($i=0;$i<$variaveisTotal;$i++){
			$valor = $_REQUEST['valor-'.$i];
			$ref = $_REQUEST['ref-'.$i];
			$refHost = $_REQUEST['ref-host-'.$i];
			
			if(isset($banco_antes[$ref])){
				// ===== Pegar referência da variável padrão.
				
				$id = $banco_antes[$ref]['id'];
				$tipo = $banco_antes[$ref]['tipo'];
				$grupo = $banco_antes[$ref]['grupo'];
				
				// ===== Verificar se a variável host existe. Se sim, ver se o valor foi alterado. Senão, criar nova variável host.
				
				if(isset($banco_antes_hosts[$refHost])){
					if(
						$banco_antes_hosts[$refHost]['valor'] != $valor
					){
						banco_update_campo('valor',$valor);
						
						banco_update_executar(
							'hosts_variaveis',
							"WHERE linguagem_codigo='".$linguagemCodigo."'"
							." AND modulo='".$modulo."'"
							." AND id_hosts_variaveis='".$refHost."'"
							." AND id_hosts='".$_GESTOR['host-id']."'"
						);
						
						$alterouVariavel = true;
						
						$alteracao_txt .= (existe($alteracao_txt) ? ', ':'') . $id;
					}
				} else {
					if(
						$banco_antes[$ref]['valor'] != $valor
					){
						banco_insert_name_campo('id_hosts',$_GESTOR['host-id']);
						banco_insert_name_campo('linguagem_codigo',$linguagemCodigo);
						banco_insert_name_campo('modulo',$modulo);
						banco_insert_name_campo('id',$id);
						banco_insert_name_campo('tipo',$tipo);
						
						if(existe($grupo))banco_insert_name_campo('grupo',$grupo);
						if(existe($valor))banco_insert_name_campo('valor',$valor);
						
						banco_insert_name
						(
							banco_insert_name_campos(),
							"hosts_variaveis"
						);
						
						$alterouVariavel = true;
						
						$alteracao_txt .= (existe($alteracao_txt) ? ', ':'') . $id;
					}
				}
			}
		}
		
		// ===== Atualização dos demais campos.
		
		if(isset($alterouVariavel)){
			// ===== Padrão de alteração txt.
			
			$historicChange = gestor_variaveis(Array('modulo' => 'configuracao','id' => 'historic-change'));
			
			if(isset($plugin)){
				// ===== Alterações txt.
				
				$alteracao_txt = $historicChange . ' <b>' . $alteracao_txt . '</b>';
				
				// ===== Alteções vetor.
				
				$alteracoes[] = Array(
					'alteracao' => 'change-variable',
					'alteracao_txt' => $alteracao_txt,
				);
				
				// ===== Alteração de versão e data do plugin.
				
				banco_update_campo('versao_config','versao_config+1',true);
				banco_update_campo('data_modificacao','NOW()',true);
				
				banco_update_executar('hosts_plugins',"WHERE id_hosts='".$_GESTOR['host-id']."' AND plugin='".$plugin."'");
				
				// ===== Pegar a versão do host plugin.
				
				$hosts_plugins = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_plugins',
					'campos' => Array(
						'versao_config',
					),
					'extra' => 
						"WHERE id_hosts='".$_GESTOR['host-id']."'"
						." AND plugin='".$plugin."'"
				));
				
				$versao_config = $hosts_plugins['versao_config'];
				
				// ===== Incluir no histórico as alterações.
				
				interface_historico_incluir(Array(
					'alteracoes' => $alteracoes,
					'sem_id' => true,
					'versao' => $versao_config,
				));
			} else {
				// ===== Alterações txt.
				
				$alteracao_txt = $historicChange . ' <b>' . $alteracao_txt . '</b>';
				
				// ===== Alteções vetor.
				
				$alteracoes[] = Array(
					'alteracao' => 'change-variable',
					'alteracao_txt' => $alteracao_txt,
				);
				
				// ===== Alterar versão e data.
				
				$hosts_configuracoes = banco_select(Array(
					'unico' => true,
					'tabela' => 'hosts_configuracoes',
					'campos' => Array(
						'versao',
					),
					'extra' => 
						"WHERE id_hosts='".$_GESTOR['host-id']."'"
						." AND modulo='".$modulo."'"
				));
				
				if($hosts_configuracoes){
					banco_update_campo('versao','versao+1',true);
					banco_update_campo('data_modificacao','NOW()',true);
					
					banco_update_executar('hosts_configuracoes',"WHERE id_hosts='".$_GESTOR['host-id']."' AND modulo='".$modulo."'");
					
					$versao_config = (int)$hosts_configuracoes['versao'] + 1;
				} else {
					banco_insert_name_campo('id_hosts',$_GESTOR['host-id']);
					banco_insert_name_campo('modulo',$modulo);
					banco_insert_name_campo('versao','1',true);
					banco_insert_name_campo('data_modificacao','NOW()',true);
					
					banco_insert_name
					(
						banco_insert_name_campos(),
						"hosts_configuracoes"
					);
					
					$versao_config = '1';
				}
				
				// ===== Incluir no histórico as alterações.
				
				interface_historico_incluir(Array(
					'alteracoes' => $alteracoes,
					'sem_id' => true,
					'versao' => $versao_config,
				));
			}
			
			// ===== Marcar que alterou a variável.
			
			$retorno['alterouVariavel'] = true;
		}
	}
	
	return $retorno;
}

/**
 * Retorna as variáveis de configuração de um módulo para um host específico.
 *
 * Busca e retorna todas as variáveis de configuração de um módulo, mesclando
 * valores padrão com valores personalizados do host. Suporta filtragem por grupos
 * e permite especificar host e linguagem.
 *
 * @global array $_GESTOR Sistema global de configurações.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['modulo'] Módulo alvo para filtrar as variáveis (obrigatório).
 * @param string $params['linguagemCodigo'] Linguagem das variáveis (opcional, usa padrão do sistema).
 * @param array $params['grupos'] Grupos alvos para filtrar as variáveis (opcional).
 * @param int $params['id_hosts'] Identificador do host alvo (opcional, usa host atual).
 * 
 * @return array Array de variáveis de configuração com valores mesclados do host.
 */
function configuracao_hosts_variaveis($params = false){
	global $_GESTOR;
	
	// Extrair parâmetros do array para variáveis locais
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Validar parâmetros e definir padrões
	
	if(isset($modulo)){
		// ===== Obter identificador do host (usa atual se não fornecido)
		
		if(!isset($id_hosts)){
			$id_hosts = (isset($_GESTOR['host-id']) ? $_GESTOR['host-id'] : '');
		}
		
		// ===== Definir linguagem padrão se não foi enviada
		
		if(!isset($linguagemCodigo)){
			$linguagemCodigo = $_GESTOR['linguagem-codigo'];
		}
		
		// ===== Montar SQL de filtragem por grupos (se fornecido)
		
		$gruposSQL = '';
		
		if(isset($grupos)){
			$gruposSQL = ' AND (';
			
			foreach($grupos as $grupo){
				$gruposSQL .= (!isset($primeiro) ? '':' OR ') . "grupo='".$grupo."'";
				$primeiro = true;
			}
			
			$gruposSQL .= ')';
		}
		
		// ===== Buscar variáveis padrão do módulo no banco
		
		$variaveis = banco_select(Array(
			'tabela' => 'variaveis',
			'campos' => Array(
				'id_variaveis',
				'id',
				'valor',
				'tipo',
				'grupo',
				'descricao',
			),
			'extra' => 
				"WHERE linguagem_codigo='".$linguagemCodigo."'"
				." AND modulo='".$modulo."'"
				. $gruposSQL
				." ORDER BY id ASC"
		));
		
		$hosts_variaveis = banco_select(Array(
			'tabela' => 'hosts_variaveis',
			'campos' => Array(
				'id_hosts_variaveis',
				'id',
				'valor',
			),
			'extra' => 
				"WHERE linguagem_codigo='".$linguagemCodigo."'"
				." AND modulo='".$modulo."'"
				. $gruposSQL
				." AND id_hosts='".$id_hosts."'"
				." ORDER BY id ASC"
		));
		
		// ===== Montar todas as variáveis.
		
		if($variaveis){
			$count = 0;
			
			foreach($variaveis as $variavel){
				// ===== Verificar se o valor padrão foi modificado por um valor específico do host e subistiuir o mesmo pelo valor específico.
				
				if($hosts_variaveis)
				foreach($hosts_variaveis as $hosts_variavel){
					if(
						$variavel['id'] == $hosts_variavel['id']
					){
						$variavel['valor'] = $hosts_variavel['valor'];
						break;
					}
				}
				
				$variaveisProcessadas[$variavel['id']] = $variavel['valor'];
			}
			
			return $variaveisProcessadas;
		}
	}
	
	return Array();
}

?>