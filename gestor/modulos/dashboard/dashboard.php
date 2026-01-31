<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'dashboard';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/dashboard.json'), true);

function dashboard_toast($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id - String - Obrigatório - Identificador.
	// opcoes - Array - Obrigatório - Opções do toast.
	// botoes - Array - Opcional - Botões do toast.
	// regra - String - Opcional - Regra caso seja necessário disparar alguma opção específica.
	
	// ===== 
	
	if(isset($id) && isset($opcoes)){
		// ===== Criar variável toast caso a mesma não tenha sido criada antes.
		
		if(!isset($_GESTOR['javascript-vars']['toasts'])){
			$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
			
			$_GESTOR['javascript-vars']['toasts'] = Array();
			$_GESTOR['javascript-vars']['toasts_options'] = Array(
				'troca_time' => $modulo['toasts']['troca_time'],
				'updateNotShowToastTime' => $modulo['toasts']['updateNotShowToastTime'],
				'opcoes_padroes' => $modulo['toasts']['opcoes_padroes'],
			);
		}
		
		// ===== Criar o array do toast
		
		$toast = Array();
		
		// ===== Incluir opções no toast
		
		foreach($opcoes as $chave => $valor){
			$toast['opcoes'][$chave] = $valor;
		}
		
		// ===== Incluir opções no toast
		
		foreach($botoes as $chave => $valor){
			$toast['botoes'][$chave] = $valor;
		}
		
		// ===== Incluir regra no toast
		
		if(isset($regra)){ $toast['regra'] = $regra; }
		
		// ===== Inserir o toast no array de toasts.
		
		$_GESTOR['javascript-vars']['toasts'][$id] = $toast;
	}
}

function dashboard_toast_atualizacoes(){
	global $_GESTOR;
	
	// ===== Verificação de atualização
	
	$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
	
	// ===== Verifica se o usuário é admin do host para poder ter acesso a atualizações.
	
	if(isset($host_verificacao['privilegios_admin'])){
		// ===== Verificar versão do gestor cliente.
		
		$id_hosts = $host_verificacao['id_hosts'];
		
		$hosts = banco_select_name
		(
			banco_campos_virgulas(Array(
				'gestor_cliente_versao_num',
			))
			,
			"hosts",
			"WHERE id_hosts='".$id_hosts."'"
		);
		
		$gestor_cliente_versao_num = $hosts[0]['gestor_cliente_versao_num'];
		
		// ===== Comparar versões e montar a interface. Ou é atualização normal, dado que há uma versão mais nova, ou então se quiser forçar a atualização afim de sobrescrever os dados no hospedeiro do cliente.
		
		if($_GESTOR['gestor-cliente']['versao_num'] > (int)$gestor_cliente_versao_num){
			$botaoNegativeMessageLayout = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'toast-update-button-negative-message'));
			
			$botaoNegativeMessageLayout = modelo_var_troca($botaoNegativeMessageLayout,"#url#",'<a href="'.$_GESTOR['url-raiz'].'admin-atualizacoes/">'.$_GESTOR['url-raiz'].'admin-atualizacoes/</a>');
			
			dashboard_toast(Array(
				'id' => 'update',
				'regra' => 'update',
				'opcoes' => Array(
					'title' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'toast-update-title')),
					'message' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'toast-update-message')),
				),
				'botoes' => Array(
					'update-positivo' => Array(
						'text' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'toast-update-button-positive-label')),
						'icon' => 'check',
						'class' => 'green',
						'click' => Array(
							'message' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'toast-update-button-positive-message')),
							'showProgress' => 'bottom',
							'class' => 'success',
							'displayTime' => 4000,
						),
					),
					'update-negativo' => Array(
						'text' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'toast-update-button-negative-label')),
						'icon' => 'ban',
						'class' => 'icon red',
						'click' => Array(
							'displayTime' => 6000,
							'showProgress' => 'bottom',
							'message' => $botaoNegativeMessageLayout,
						),
					),
				),
			));
		}
	}
}

/**
 * Verifica se há atualização disponível para o gestor.
 * Utiliza a função descobrirUltimaTagGestor() para buscar a última versão no GitHub.
 * Armazena o resultado na sessão com tempo de expiração.
 * 
 * @return bool True se há atualização disponível
 */
function dashboard_verificar_atualizacao(){
	global $_GESTOR;
	
	// Verificar se o usuário é admin do host
	$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
	
	if(!isset($host_verificacao['privilegios_admin'])){
		$_GESTOR['javascript-vars']['update_available'] = false;
		return false;
	}
	
	// Chave para armazenar a última verificação na sessão
	$chave_verificacao = 'dashboard_update_check_'.$_GESTOR['usuario-id'];
	$tempo_expiracao_minutos = 60; // Verificar a cada 1 hora
	
	// Verificar se já existe uma verificação recente na sessão
	$cache = gestor_sessao_variavel($chave_verificacao);
	
	if($cache && isset($cache['timestamp']) && isset($cache['update_available'])){
		$agora = time();
		$tempo_expiracao_segundos = $tempo_expiracao_minutos * 60;
		
		if(($agora - $cache['timestamp']) < $tempo_expiracao_segundos){
			$_GESTOR['javascript-vars']['update_available'] = $cache['update_available'];
			return $cache['update_available'];
		}
	}
	
	// Fazer nova verificação
	try {
		// Incluir o arquivo de atualizações se necessário
		$arquivo_atualizacoes = $_GESTOR['gestor-raiz'] . 'controladores/atualizacoes/atualizacoes-sistema.php';
		if(file_exists($arquivo_atualizacoes) && !function_exists('descobrirUltimaTagGestor')){
			require_once $arquivo_atualizacoes;
		}
		
		if(function_exists('descobrirUltimaTagGestor')){
			$info_release = descobrirUltimaTagGestor();
			
			// Extrair número da versão da tag (gestor-v1.0.0 -> 1.0.0)
			$tag = $info_release['tag'] ?? '';
			$versao_remota = str_replace('gestor-v', '', $tag);
			$versao_local = $_GESTOR['gestor-cliente']['versao'] ?? '0.0.0';
			
			// Comparar versões
			$update_disponivel = version_compare($versao_remota, $versao_local, '>');
			
			// Armazenar resultado na sessão
			gestor_sessao_variavel($chave_verificacao, Array(
				'timestamp' => time(),
				'update_available' => $update_disponivel,
				'remote_version' => $versao_remota,
				'local_version' => $versao_local
			));
			
			$_GESTOR['javascript-vars']['update_available'] = $update_disponivel;
			return $update_disponivel;
		}
	} catch(Exception $e) {
		// Falha silenciosa - não mostrar erro ao usuário
		error_log('Dashboard: Erro ao verificar atualização: ' . $e->getMessage());
	}
	
	$_GESTOR['javascript-vars']['update_available'] = false;
	return false;
}

/**
 * Gera os SVGs decorativos baseados nos ícones dos módulos.
 * 
 * @param string $icon Nome do ícone principal do Fomantic-UI
 * @param string $icon2 Nome do ícone secundário (opcional)
 * @return string SVG gerado
 */
function dashboard_gerar_svg_modulo($icon, $icon2 = null){
	// Mapeamento de ícones Feather Icons para paths SVG
	// ViewBox: 0 0 24 24 | Stroke-based | Stroke-width: 2
	// Referência: https://feathericons.com/
	$svg_paths = array(
		// CONFIGURAÇÕES
		'cog' => '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>',
		'cogs' => '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>',
		// ARQUIVOS
		'file' => '<path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline>',
		'file image outline' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline>',
		'file alternate outline' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>',
		'file alternate' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>',
		// USUÁRIOS
		'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
		'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>',
		// PASTAS
		'folder' => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>',
		'folder open' => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>',
		'folder open outline' => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>',
		// E-COMMERCE
		'shopping cart' => '<circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>',
		'box' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>',
		// DATABASE & SERVER
		'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>',
		'server' => '<rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line>',
		// NAVEGAÇÃO
		'home' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>',
		'globe' => '<circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>',
		// DASHBOARD & GRÁFICOS
		'dashboard' => '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>',
		'chart bar' => '<line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line>',
		'chart line' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>',
		// COMUNICAÇÃO
		'envelope' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline>',
		'bell' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path>',
		'comment' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>',
		'comments' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>',
		// SEGURANÇA
		'lock' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>',
		'key' => '<path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path>',
		'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>',
		// AÇÕES
		'edit' => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>',
		'trash' => '<polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>',
		'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line>',
		'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line>',
		'search' => '<circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>',
		'filter' => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>',
		'plus' => '<line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>',
		'minus' => '<line x1="5" y1="12" x2="19" y2="12"></line>',
		'check circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>',
		'times circle' => '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>',
		// INTERFACE
		'eye' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>',
		'eye slash' => '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>',
		'external alternate' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line>',
		'sign out alternate' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>',
		// MÍDIA
		'image' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline>',
		'video' => '<polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>',
		'music' => '<path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle>',
		// TEMPO
		'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>',
		'clock' => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
		// OBJETOS
		'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>',
		'bookmark' => '<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>',
		'tag' => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line>',
		'tags' => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line>',
		'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>',
		'heart' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>',
		'flag' => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line>',
		// INFORMAÇÃO
		'info circle' => '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>',
		'question circle outline' => '<circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line>',
		'exclamation triangle' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>',
		// FERRAMENTAS & TRABALHO
		'briefcase' => '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>',
		'tools' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>',
		'code' => '<polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline>',
		'terminal' => '<polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line>',
		// FORMAS & LAYOUT
		'grid' => '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>',
		'list' => '<line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line>',
		'shapes' => '<polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline>',
		'object ungroup outline' => '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect>',
		// ROBÔ & IA
		'robot' => '<rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path><line x1="8" y1="16" x2="8" y2="16"></line><line x1="16" y1="16" x2="16" y2="16"></line>',
		// OUTROS
		'map' => '<polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line>',
		'plug' => '<path d="M12 22v-5"></path><path d="M9 8V2"></path><path d="M15 8V2"></path><path d="M18 8v5a6 6 0 0 1-12 0V8z"></path>',
		'bolt' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>',
		'sync' => '<polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>',
		'refresh' => '<polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>',
		'copy' => '<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>',
		'clipboard' => '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>',
		'save' => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline>',
		'print' => '<polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect>',
		'share' => '<circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>',
		'link' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>',
		'arrow right' => '<line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline>',
		'arrow left' => '<line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline>',
		'grip vertical' => '<circle cx="9" cy="5" r="1"></circle><circle cx="9" cy="12" r="1"></circle><circle cx="9" cy="19" r="1"></circle><circle cx="15" cy="5" r="1"></circle><circle cx="15" cy="12" r="1"></circle><circle cx="15" cy="19" r="1"></circle>',
		'id card' => '<rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line>',
		'id card outline' => '<rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line>',
		'project diagram' => '<polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline>',
		'sitemap' => '<polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline>',
		'stream' => '<line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line>',
		'graduation cap' => '<path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"></path>',
		'building' => '<rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"></path>',
		'industry' => '<path d="M2 20h20"></path><path d="M5 20V8l5 4V8l5 4V4l5 4v12"></path>',
		'truck' => '<rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle>',
		'plane' => '<path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"></path>',
		'rocket' => '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"></path><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"></path><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"></path>',
		'magic' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>',
		'wand' => '<path d="M15 4V2"></path><path d="M15 16v-2"></path><path d="M8 9h2"></path><path d="M20 9h2"></path><path d="M17.8 11.8L19 13"></path><path d="M15 9h0"></path><path d="M17.8 6.2L19 5"></path><path d="m3 21 9-9"></path><path d="M12.2 6.2L11 5"></path>',
		'puzzle' => '<path d="M19.439 7.85c-.049.322.059.648.289.878l1.568 1.568c.47.47.706 1.087.706 1.704s-.235 1.233-.706 1.704l-1.611 1.611a.98.98 0 0 1-.837.276c-.47-.07-.802-.48-.968-.925a2.501 2.501 0 1 0-3.214 3.214c.446.166.855.497.925.968a.979.979 0 0 1-.276.837l-1.61 1.61a2.404 2.404 0 0 1-1.705.707 2.402 2.402 0 0 1-1.704-.706l-1.568-1.568a1.026 1.026 0 0 0-.877-.29c-.493.074-.84.504-1.02.968a2.5 2.5 0 1 1-3.237-3.237c.464-.18.894-.527.967-1.02a1.026 1.026 0 0 0-.289-.877l-1.568-1.568A2.402 2.402 0 0 1 1.998 12c0-.617.236-1.234.706-1.704L4.23 8.77c.24-.24.581-.353.917-.303.515.077.877.528 1.073 1.01a2.5 2.5 0 1 0 3.259-3.259c-.482-.196-.933-.558-1.01-1.073-.05-.336.062-.676.303-.917l1.525-1.525A2.402 2.402 0 0 1 12 1.998c.617 0 1.234.236 1.704.706l1.568 1.568c.23.23.556.338.877.29.493-.074.84-.504 1.02-.968a2.5 2.5 0 1 1 3.237 3.237c-.464.18-.894.527-.967 1.02Z"></path>',
		'certificate' => '<circle cx="12" cy="8" r="6"></circle><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"></path>',
		'trophy' => '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path><path d="M4 22h16"></path><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path>',
		'fire' => '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path>',
		'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"></path><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path>',
		'sun' => '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>',
		'moon' => '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>',
		'cloud' => '<path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"></path>',
		'umbrella' => '<path d="M23 12a11.05 11.05 0 0 0-22 0zm-5 7a3 3 0 0 1-6 0v-7"></path>',
		'anchor' => '<circle cx="12" cy="5" r="3"></circle><line x1="12" y1="22" x2="12" y2="8"></line><path d="M5 12H2a10 10 0 0 0 20 0h-3"></path>',
		'life ring' => '<circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="4"></circle><line x1="4.93" y1="4.93" x2="9.17" y2="9.17"></line><line x1="14.83" y1="14.83" x2="19.07" y2="19.07"></line><line x1="14.83" y1="9.17" x2="19.07" y2="4.93"></line><line x1="14.83" y1="9.17" x2="18.36" y2="5.64"></line><line x1="4.93" y1="19.07" x2="9.17" y2="14.83"></line>',
		'compass' => '<circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon>',
		'microphone' => '<path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="23"></line><line x1="8" y1="23" x2="16" y2="23"></line>',
		'headphones' => '<path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>',
		'volume up' => '<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>',
		'wifi' => '<path d="M5 12.55a11 11 0 0 1 14.08 0"></path><path d="M1.42 9a16 16 0 0 1 21.16 0"></path><path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path><line x1="12" y1="20" x2="12.01" y2="20"></line>',
		'bluetooth' => '<polyline points="6.5 6.5 17.5 17.5 12 23 12 1 17.5 6.5 6.5 17.5"></polyline>',
		'battery full' => '<rect x="1" y="6" width="18" height="12" rx="2" ry="2"></rect><line x1="23" y1="13" x2="23" y2="11"></line>',
		'battery half' => '<rect x="1" y="6" width="18" height="12" rx="2" ry="2"></rect><line x1="23" y1="13" x2="23" y2="11"></line>',
		'battery empty' => '<rect x="1" y="6" width="18" height="12" rx="2" ry="2"></rect><line x1="23" y1="13" x2="23" y2="11"></line>',
		'signal' => '<path d="M2 20h.01"></path><path d="M7 20v-4"></path><path d="M12 20v-8"></path><path d="M17 20V8"></path>',
		'qrcode' => '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>',
		'credit card' => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line>',
		'money' => '<line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>',
		'percent' => '<line x1="19" y1="5" x2="5" y2="19"></line><circle cx="6.5" cy="6.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle>',
		'hashtag' => '<line x1="4" y1="9" x2="20" y2="9"></line><line x1="4" y1="15" x2="20" y2="15"></line><line x1="10" y1="3" x2="8" y2="21"></line><line x1="16" y1="3" x2="14" y2="21"></line>',
		'at' => '<circle cx="12" cy="12" r="4"></circle><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"></path>',
		'unlink' => '<path d="m18.84 12.25 1.72-1.71h-.02a5.004 5.004 0 0 0-.12-7.07 5.006 5.006 0 0 0-6.95 0l-1.72 1.71"></path><path d="m5.17 11.75-1.71 1.71a5.004 5.004 0 0 0 .12 7.07 5.006 5.006 0 0 0 6.95 0l1.71-1.71"></path><line x1="8" y1="2" x2="8" y2="5"></line><line x1="2" y1="8" x2="5" y2="8"></line><line x1="16" y1="19" x2="16" y2="22"></line><line x1="19" y1="16" x2="22" y2="16"></line>',
		'paperclip' => '<path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>',
		'expand' => '<polyline points="15 3 21 3 21 9"></polyline><polyline points="9 21 3 21 3 15"></polyline><line x1="21" y1="3" x2="14" y2="10"></line><line x1="3" y1="21" x2="10" y2="14"></line>',
		'compress' => '<polyline points="4 14 10 14 10 20"></polyline><polyline points="20 10 14 10 14 4"></polyline><line x1="14" y1="10" x2="21" y2="3"></line><line x1="3" y1="21" x2="10" y2="14"></line>',
		'thumbs up' => '<path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path>',
		'thumbs down' => '<path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"></path>',
		'hand point right' => '<path d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0"></path><path d="M14 10V4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v2"></path><path d="M10 10.5V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v8"></path><path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15"></path>',
		'hand paper' => '<path d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0"></path><path d="M14 10V4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v2"></path><path d="M10 10.5V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v8"></path><path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15"></path>',
		'spinner' => '<line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>',
		'circle notch' => '<circle cx="12" cy="12" r="10"></circle>',
		'paint brush' => '<path d="M9.06 11.9l8.07-8.06a2.85 2.85 0 1 1 4.03 4.03l-8.06 8.08"></path><path d="M7.07 14.94c-1.66 0-3 1.35-3 3.02 0 1.33-2.5 1.52-2 2.02 1.08 1.1 2.49 2.02 4 2.02 2.2 0 4-1.8 4-4.04a3.01 3.01 0 0 0-3-3.02z"></path>',
		'drafting compass' => '<circle cx="12" cy="5" r="2"></circle><path d="M12 7l-2.5 12.5"></path><path d="M12 7l2.5 12.5"></path><path d="M3 19c1-1.2 2.5-2 4-2s3 .8 4 2"></path><path d="M13 19c1-1.2 2.5-2 4-2s3 .8 4 2"></path>',
		'sort' => '<line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline>',
		'th' => '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>',
	);
	
	// Ícone padrão se não encontrado (ponto de interrogação)
	$default_svg = '<circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line>';
	
	// Normalizar o nome do ícone
	$icon_key = str_replace(' icon', '', $icon);
	$icon_key = str_replace(' huge', '', $icon_key);
	$icon_key = str_replace(' large', '', $icon_key);
	$icon_key = trim($icon_key);
	
	// Buscar o path do SVG
	$svg_path = isset($svg_paths[$icon_key]) ? $svg_paths[$icon_key] : $default_svg;
	
	// Montar o SVG completo - Feather Icons usa viewBox 0 0 24 24 e stroke-based
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $svg_path . '</svg>';
	
	return $svg;
}

/**
 * Gera a descrição padrão de um módulo baseado no ID.
 * 
 * @param string $modulo_id ID do módulo
 * @param string $linguagem Código do idioma
 * @return string Descrição do módulo
 */
function dashboard_gerar_descricao_modulo($modulo_id, $linguagem = 'pt-br'){
	$descricoes = array(
		'pt-br' => array(
			'admin-layouts' => 'Gerencie os layouts do sistema, modelos visuais base para as páginas.',
			'admin-paginas' => 'Administre páginas do sistema, conteúdo e estrutura do site.',
			'admin-componentes' => 'Controle componentes reutilizáveis de interface.',
			'admin-templates' => 'Gerencie templates para criação rápida de conteúdo.',
			'admin-arquivos' => 'Upload e gerenciamento de arquivos e mídias.',
			'admin-categorias' => 'Organize conteúdo através de categorias hierárquicas.',
			'admin-hosts' => 'Configure domínios e hosts do sistema.',
			'admin-plugins' => 'Instale e gerencie plugins do sistema.',
			'admin-atualizacoes' => 'Verifique e aplique atualizações do sistema.',
			'usuarios' => 'Gerencie usuários e suas permissões de acesso.',
			'usuarios-perfis' => 'Configure perfis de acesso e permissões.',
			'usuarios-gestores' => 'Administre usuários gestores do sistema.',
			'usuarios-hospedeiro' => 'Gerencie usuários clientes hospedeiros.',
			'host-configuracao' => 'Configure automaticamente seu ambiente de hospedagem.',
			'interface' => 'Personalize a interface visual do sistema.',
			'comunicacao-configuracoes' => 'Configure emails e comunicações do sistema.',
			'pedidos' => 'Gerencie pedidos e vendas realizadas.',
			'servicos' => 'Administre catálogo de serviços oferecidos.',
			'gateways-de-pagamentos' => 'Configure integrações com gateways de pagamento.',
			'loja-configuracoes' => 'Configure opções gerais da loja virtual.',
			'modulos' => 'Gerencie módulos ativos do sistema.',
			'modulos-grupos' => 'Organize módulos em grupos funcionais.',
			'paginas' => 'Gerencie páginas públicas do site.',
			'postagens' => 'Crie e edite posts e artigos.',
			'menus' => 'Configure menus de navegação do site.',
			'categorias' => 'Organize conteúdo em categorias.',
			'arquivos' => 'Acesse arquivos públicos do sistema.',
			'componentes' => 'Visualize componentes visuais disponíveis.',
			'layouts' => 'Veja layouts disponíveis para páginas.',
			'templates' => 'Acesse templates para criar conteúdo.',
			'publisher' => 'Crie e publique conteúdo dinâmico.',
			'publisher-pages' => 'Gerencie páginas do publicador.',
		),
		'en' => array(
			'admin-layouts' => 'Manage system layouts, base visual models for pages.',
			'admin-paginas' => 'Administer system pages, content and site structure.',
			'admin-componentes' => 'Control reusable interface components.',
			'admin-templates' => 'Manage templates for quick content creation.',
			'admin-arquivos' => 'Upload and manage files and media.',
			'admin-categorias' => 'Organize content through hierarchical categories.',
			'admin-hosts' => 'Configure system domains and hosts.',
			'admin-plugins' => 'Install and manage system plugins.',
			'admin-atualizacoes' => 'Check and apply system updates.',
			'usuarios' => 'Manage users and their access permissions.',
			'usuarios-perfis' => 'Configure access profiles and permissions.',
			'usuarios-gestores' => 'Administer system manager users.',
			'usuarios-hospedeiro' => 'Manage host client users.',
			'host-configuracao' => 'Automatically configure your hosting environment.',
			'interface' => 'Customize the system visual interface.',
			'comunicacao-configuracoes' => 'Configure system emails and communications.',
			'pedidos' => 'Manage orders and completed sales.',
			'servicos' => 'Administer offered services catalog.',
			'gateways-de-pagamentos' => 'Configure payment gateway integrations.',
			'loja-configuracoes' => 'Configure virtual store general options.',
			'modulos' => 'Manage active system modules.',
			'modulos-grupos' => 'Organize modules into functional groups.',
			'paginas' => 'Manage public site pages.',
			'postagens' => 'Create and edit posts and articles.',
			'menus' => 'Configure site navigation menus.',
			'categorias' => 'Organize content into categories.',
			'arquivos' => 'Access public system files.',
			'componentes' => 'View available visual components.',
			'layouts' => 'See available layouts for pages.',
			'templates' => 'Access templates to create content.',
			'publisher' => 'Create and publish dynamic content.',
			'publisher-pages' => 'Manage publisher pages.',
		),
	);
	
	$lang_descricoes = isset($descricoes[$linguagem]) ? $descricoes[$linguagem] : $descricoes['pt-br'];
	
	$descricao_padrao = $linguagem === 'en' 
		? 'Access and manage this module.' 
		: 'Acesse e gerencie este módulo.';
	
	return isset($lang_descricoes[$modulo_id]) ? $lang_descricoes[$modulo_id] : $descricao_padrao;
}

/**
 * Gera os cards do dashboard com base nos módulos e permissões do usuário.
 */
function dashboard_cards(){
	global $_GESTOR;
	
	// ===== Campos padrões
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#titulo#",$_GESTOR['pagina#titulo']);
	
	// ===== Obter o componente dashboard-cards
	
	$componente = gestor_componente(Array(
		'id' => 'dashboard-cards',
		'modulo' => $_GESTOR['modulo-id'],
	));
	
	// ===== Obter usuário e permissões
	
	$usuario = gestor_usuario();
	
	// ===== Verificar se o usuário é filho de um host ou não.
	
	if(existe($usuario['id_hosts'])){
		// ===== Verificar se o usuário tem um perfil de gestor ativo.
		
		if(existe($usuario['gestor_perfil'])){
			$gestor_perfil = $usuario['gestor_perfil'];
			
			$usuarios_perfis_modulos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'modulo',
				))
				,
				"usuarios_gestores_perfis_modulos",
				"WHERE perfil='".$gestor_perfil."'"
				." AND id_hosts='".$usuario['id_hosts']."'"
			);
		} else {
			$hosts = banco_select(Array(
				'unico' => true,
				'tabela' => 'hosts',
				'campos' => Array('id_usuarios'),
				'extra' => "WHERE id_hosts='".$usuario['id_hosts']."'"
			));
			
			$usuarios = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios',
				'campos' => Array('id_usuarios_perfis'),
				'extra' => "WHERE id_usuarios='".$hosts['id_usuarios']."'"
			));
			
			$usuarios_perfis = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios_perfis',
				'campos' => Array('id'),
				'extra' => "WHERE id_usuarios_perfis='".$usuarios['id_usuarios_perfis']."'"
			));
			
			$perfil = $usuarios_perfis['id'];
			
			$usuarios_perfis_modulos = banco_select_name
			(
				banco_campos_virgulas(Array('modulo'))
				,
				"usuarios_perfis_modulos",
				"WHERE perfil='".$perfil."'"
			);
		}
	} else {
		$usuarios_perfis = banco_select(Array(
			'unico' => true,
			'tabela' => 'usuarios_perfis',
			'campos' => Array('id'),
			'extra' => "WHERE id_usuarios_perfis='".$usuario['id_usuarios_perfis']."'"
		));
		
		$perfil = $usuarios_perfis['id'];
		
		$usuarios_perfis_modulos = banco_select_name
		(
			banco_campos_virgulas(Array('modulo'))
			,
			"usuarios_perfis_modulos",
			"WHERE perfil='".$perfil."'"
		);
	}
	
	// ===== Pegar dados de páginas e módulos
	
	$paginas = banco_select_name
	(
		banco_campos_virgulas(Array('modulo', 'caminho'))
		,
		"paginas",
		"WHERE raiz IS NOT NULL"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
	);
	
	$modulos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_modulos',
			'modulo_grupo_id',
			'id',
			'nome',
			'icone',
			'icone2',
			'plugin',
		))
		,
		"modulos",
		"WHERE language='".$_GESTOR['linguagem-codigo']."'"
		." ORDER BY nome ASC"
	);
	
	$modulos_grupos = banco_select_name
	(
		banco_campos_virgulas(Array('id', 'nome'))
		,
		"modulos_grupos",
		"WHERE id!='bibliotecas'"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
		." ORDER BY nome ASC"
	);
	
	// ===== Extrair células do template
	
	$cel_nome = 'card'; 
	$cel[$cel_nome] = modelo_tag_val($componente,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); 
	$componente = modelo_tag_in($componente,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	$cel_nome = 'cards-container'; 
	$cel[$cel_nome] = modelo_tag_val($componente,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); 
	$componente = modelo_tag_in($componente,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	// ===== Verifica permissões admin do host
	
	$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
	
	$privilegios_admin = false;
	if(isset($host_verificacao['privilegios_admin'])){
		$privilegios_admin = true;
	}
	
	// ===== Verificar plugins habilitados do host
	
	if(isset($_GESTOR['host-id'])){
		$hosts_plugins = banco_select(Array(
			'tabela' => 'hosts_plugins',
			'campos' => Array('plugin', 'habilitado'),
			'extra' => "WHERE id_hosts='".$_GESTOR['host-id']."'"
		));
	}
	
	// ===== Gerar cards dos módulos
	
	$cards_html = '';
	$cards_order = array();
	$order_index = 0;
	
	// URL base para documentação
	$docs_base_url = 'https://github.com/otavioserra/conn2flow/blob/main/ai-workspace/' . $_GESTOR['linguagem-codigo'] . '/docs/modulos/';
	
	if($modulos)
	foreach($modulos as $modulo){
		$modulo_perfil = false;
		
		if($usuarios_perfis_modulos)
		foreach($usuarios_perfis_modulos as $upm){
			if($upm['modulo'] == $modulo['id']){
				$modulo_perfil = true;
				break;
			}
		}
		
		// Pular dashboard e módulos sem permissão
		if($modulo['id'] == 'dashboard' || !$modulo_perfil){
			continue;
		}
		
		// Verificar plugins habilitados
		if(isset($_GESTOR['host-id'])){
			if($modulo['plugin']){
				$habilitado = false;
				
				if($hosts_plugins)
				foreach($hosts_plugins as $hosts_plugin){
					if($hosts_plugin['plugin'] == $modulo['plugin'] && $hosts_plugin['habilitado']){
						$habilitado = true;
					}
				}
				
				if(!$habilitado){
					continue;
				}
			}
		}
		
		// Verificar permissão de host-configuracao
		if($modulo['id'] == 'host-configuracao' && !$privilegios_admin && isset($_GESTOR['host-id'])){
			continue;
		}
		
		// Montar o card
		$cel_aux = $cel['card'];
		
		// ID e ordem do módulo
		$cel_aux = modelo_var_troca($cel_aux, "#modulo-id#", $modulo['id']);
		$cel_aux = modelo_var_troca($cel_aux, "#modulo-order#", $order_index);
		
		// Nome do módulo
		$cel_aux = modelo_var_troca($cel_aux, "#modulo-nome#", $modulo['nome']);
		
		// Grupo do módulo
		$grupo_nome = '';
		if($modulos_grupos)
		foreach($modulos_grupos as $mg){
			if($mg['id'] == $modulo['modulo_grupo_id']){
				$grupo_nome = $mg['nome'];
				break;
			}
		}
		$cel_aux = modelo_var_troca($cel_aux, "#modulo-grupo#", $grupo_nome);
		
		// Descrição do módulo
		$descricao = dashboard_gerar_descricao_modulo($modulo['id'], $_GESTOR['linguagem-codigo']);
		$cel_aux = modelo_var_troca($cel_aux, "#modulo-descricao#", $descricao);
		
		// SVG do módulo
		$svg = dashboard_gerar_svg_modulo($modulo['icone'], $modulo['icone2']);
		$cel_aux = modelo_var_troca($cel_aux, "#modulo-svg#", $svg);
		
		// Link do módulo
		$pagina_found = false;
		if($paginas)
		foreach($paginas as $pagina){
			if($modulo['id'] == $pagina['modulo']){
				$cel_aux = modelo_var_troca_tudo($cel_aux, "#modulo-link#", $_GESTOR['url-raiz'].$pagina['caminho']);
				$pagina_found = true;
				break;
			}
		}
		
		if(!$pagina_found){
			$cel_aux = modelo_var_troca_tudo($cel_aux, "#modulo-link#", $_GESTOR['url-raiz'].'dashboard/');
		}
		
		// Link da documentação
		$docs_link = $docs_base_url . $modulo['id'] . '.md';
		$cel_aux = modelo_var_troca($cel_aux, "#modulo-docs-link#", $docs_link);
		
		$cards_html .= $cel_aux;
		$cards_order[] = $modulo['id'];
		$order_index++;
	}
	
	// ===== Inserir cards no container
	
	$container = $cel['cards-container'];
	$container = modelo_var_troca($container, '<!-- card -->', $cards_html);
	
	// ===== Finalizar componente
	
	$componente = modelo_var_troca($componente, '<!-- cards-container -->', $container);
	$componente = modelo_var_troca($componente, "#titulo#", $_GESTOR['pagina#titulo']);
	
	// ===== Passar ordem dos cards para o JavaScript
	
	$_GESTOR['javascript-vars']['dashboard_cards_order'] = $cards_order;
	
	// ===== Inserir componente na página
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'], "<!-- dashboard-cards -->", $componente);
}

function dashboard_menu(){
	global $_GESTOR;
	
	// ===== Campos padrões
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],"#titulo#",$_GESTOR['pagina#titulo']);
	
	// ===== Menu de módulos
	
	$usuario = gestor_usuario();
	
	// ===== Verificar se o usuário é filho de um host ou não.
	
	if(existe($usuario['id_hosts'])){
		// ===== Verificar se o usuário tem um perfil de gestor ativo.
		
		if(existe($usuario['gestor_perfil'])){
			$gestor_perfil = $usuario['gestor_perfil'];
			
			// ===== Verificar se o módulo alvo tem permissão no perfil.
			
			$usuarios_perfis_modulos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'modulo',
				))
				,
				"usuarios_gestores_perfis_modulos",
				"WHERE perfil='".$gestor_perfil."'"
				." AND id_hosts='".$usuario['id_hosts']."'"
			);
		} else {
			// ===== Pegar o usuário pai do usuário em questão.
			
			$hosts = banco_select(Array(
				'unico' => true,
				'tabela' => 'hosts',
				'campos' => Array(
					'id_usuarios',
				),
				'extra' => 
					"WHERE id_hosts='".$usuario['id_hosts']."'"
			));
			
			// ===== Pegar o identificador do perfil do pai do usuário.
			
			$usuarios = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios',
				'campos' => Array(
					'id_usuarios_perfis',
				),
				'extra' => 
					"WHERE id_usuarios='".$hosts['id_usuarios']."'"
			));
			
			// ===== Pegar o perfil do usuário.
			
			$usuarios_perfis = banco_select(Array(
				'unico' => true,
				'tabela' => 'usuarios_perfis',
				'campos' => Array(
					'id',
				),
				'extra' => 
					"WHERE id_usuarios_perfis='".$usuarios['id_usuarios_perfis']."'"
			));
			
			$perfil = $usuarios_perfis['id'];
			
			// ===== Verificar se o módulo alvo tem permissão no perfil.
			
			$usuarios_perfis_modulos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'modulo',
				))
				,
				"usuarios_perfis_modulos",
				"WHERE perfil='".$perfil."'"
			);
		}
	} else {
		// ===== Pegar o perfil do usuário.
		
		$usuarios_perfis = banco_select(Array(
			'unico' => true,
			'tabela' => 'usuarios_perfis',
			'campos' => Array(
				'id',
			),
			'extra' => 
				"WHERE id_usuarios_perfis='".$usuario['id_usuarios_perfis']."'"
		));
		
		$perfil = $usuarios_perfis['id'];
		
		// ===== Verificar se o módulo alvo tem permissão no perfil.
		
		$usuarios_perfis_modulos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'modulo',
			))
			,
			"usuarios_perfis_modulos",
			"WHERE perfil='".$perfil."'"
		);
	}
	
	// ===== Pegar dados de páginas e módulos
	
	$paginas = banco_select_name
	(
		banco_campos_virgulas(Array(
			'modulo',
			'caminho',
		))
		,
		"paginas",
		"WHERE raiz IS NOT NULL"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
	);
	
		$modulos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_modulos',
				'modulo_grupo_id', // campo textual
				'id',
				'nome',
				'icone',
				'icone2',
				'plugin',
			))
			,
			"modulos",
			"WHERE language='".$_GESTOR['linguagem-codigo']."'"
			." ORDER BY nome ASC"
		);
    
		$modulos_grupos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id', // campo textual
				'nome',
			))
			,
			"modulos_grupos",
			"WHERE id!='bibliotecas'"
			." AND language='".$_GESTOR['linguagem-codigo']."'"
			." ORDER BY nome ASC"
		);
	
	$cel_nome = 'menu-item'; $cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'icon'; $cel[$cel_nome] = modelo_tag_val($cel['menu-item'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $cel['menu-item'] = modelo_tag_in($cel['menu-item'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'icon-2'; $cel[$cel_nome] = modelo_tag_val($cel['menu-item'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $cel['menu-item'] = modelo_tag_in($cel['menu-item'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'card'; $cel[$cel_nome] = modelo_tag_val($cel['menu-item'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $cel['menu-item'] = modelo_tag_in($cel['menu-item'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	
	// ===== Verifica se o usuário é admin do host para mostrar no menu o Host Configurações ou não.
	
	$host_verificacao = gestor_sessao_variavel('host-verificacao-'.$_GESTOR['usuario-id']);
	
	$privilegios_admin = false;
	if(isset($host_verificacao['privilegios_admin'])){
		$privilegios_admin = true;
	}
	
	// ===== Verificar se o usuário faz parte de um host. Se sim, baixar os plugins do host.
	
	if(isset($_GESTOR['host-id'])){
		$hosts_plugins = banco_select(Array(
			'tabela' => 'hosts_plugins',
			'campos' => Array(
				'plugin',
				'habilitado',
			),
			'extra' => 
				"WHERE id_hosts='".$_GESTOR['host-id']."'"
		));
	}
	
	if($modulos_grupos)
	foreach($modulos_grupos as $mg){
		$found_grup = false;
        
		if($modulos)
		foreach($modulos as $modulo){
			if($mg['id'] == $modulo['modulo_grupo_id']){
				$modulo_perfil = false;
				
				if($usuarios_perfis_modulos)
				foreach($usuarios_perfis_modulos as $upm){
					if($upm['modulo'] == $modulo['id']){
						$modulo_perfil = true;
						break;
					}
				}
				
				if($modulo['id'] == 'dashboard' || !$modulo_perfil){
					continue;
				}
				
				// ===== Verificar se o usuário faz parte de um host. Se sim, verificar os plugins do usuario e ver se esse faz parte de um plugin habilitado.
				
				if(isset($_GESTOR['host-id'])){
					if($modulo['plugin']){
						$habilitado = false;
						
						if($hosts_plugins)
						foreach($hosts_plugins as $hosts_plugin){
							if(
								$hosts_plugin['plugin'] == $modulo['plugin'] &&
								$hosts_plugin['habilitado']
							){
								$habilitado = true;
							}
						}
						
						if(!$habilitado){
							continue;
						}
					}
				}
				
				// ===== Se for o host configurações e não tiver privilégio, não mostrar no menu.
				
				if($modulo['id'] == 'host-configuracao' && !$privilegios_admin && isset($_GESTOR['host-id'])){
					continue;
				}
				
				if(!$found_grup){
					$grupo_pagina = $cel['menu-item'];
				}
				
				$cel_nome = 'card';
				$cel_aux = $cel[$cel_nome];
				
				$cel_aux = modelo_var_troca($cel_aux,"#nome#",$modulo['nome']);
				
				if($modulo['icone2']){
					$cel_nome_icon = 'icon-2';
					$cel_icon = $cel[$cel_nome_icon];
					
					$cel_icon = modelo_var_troca($cel_icon,"#icon-2#",($modulo['icone2'] ? $modulo['icone2'] : 'question circle outline'));
				} else {
					$cel_nome_icon = 'icon';
					$cel_icon = $cel[$cel_nome_icon];
				}
				
				$cel_icon = modelo_var_troca($cel_icon,"#icon#",($modulo['icone'] ? $modulo['icone'] : 'question circle outline'));
				
				$cel_aux = modelo_var_troca($cel_aux,"<!-- icon -->",$cel_icon);
				
				// ===== Se existe a página padrão, senão o link será para a raiz.
				
				$pagina_found = false;
				
				if($paginas)
				foreach($paginas as $pagina){
					if($modulo['id'] == $pagina['modulo']){
						$cel_aux = modelo_var_troca_tudo($cel_aux,"#link#",$_GESTOR['url-raiz'].$pagina['caminho']);
						$pagina_found = true;
						break;
					}
				}
				
				if(!$pagina_found){
					$cel_aux = modelo_var_troca_tudo($cel_aux,"#link#",$_GESTOR['url-raiz'].'dashboard/');
				}
				
				// ===== Adicionar ao grupo da página.
				
				$grupo_pagina = modelo_var_in($grupo_pagina,'<!-- '.$cel_nome.' -->',$cel_aux);
				$found_grup = true;
			}
		}
		
		if($found_grup){
			$cel_nome = 'card';
			$grupo_pagina = modelo_var_troca($grupo_pagina,'<!-- '.$cel_nome.' -->','');
			
			$grupo_pagina = modelo_var_troca($grupo_pagina,"#grupo#",$mg['nome']);
			
			$cel_nome = 'menu-item';
			$_GESTOR['pagina'] = modelo_var_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' -->',$grupo_pagina);
		}
	}
	
	$cel_nome = 'menu-item';
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<!-- '.$cel_nome.' -->','');
}

function dashboard_remover_pagina_instalacao_sucesso(){
	global $_GESTOR;
	
	try {
		// ===== Verificar se existe a página de instalação-sucesso
		
		$paginas = banco_select(Array(
			'tabela' => 'paginas',
			'campos' => Array(
				'id_paginas',
				'nome',
			),
			'extra' => "WHERE caminho = 'instalacao-sucesso/' AND status = 'A'"
		));
		
		if($paginas){
			// ===== Remover a página da base de dados
			foreach($paginas as $pagina){
				banco_delete('paginas',"WHERE id_paginas = '".$pagina['id_paginas']."'");
			}
		}
		
	} catch (Exception $e) {
		// ===== Em caso de erro, não interromper o carregamento do dashboard
		// Apenas log interno se necessário
	}
}

function dashboard_pagina_inicial(){
	global $_GESTOR;
	
	// ===== Remover página de instalação-sucesso se existir
	
	dashboard_remover_pagina_instalacao_sucesso();
	
	// ===== Inclusão de Componentes
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-alerta',
		)
	));
	
	// ===== Inclusão Módulo JS
	
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>');
	
	// ===== Inclusão SortableJS para drag-and-drop
	
	gestor_pagina_javascript_incluir('<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>');
	
	gestor_pagina_javascript_incluir();

	// ===== Cards do Dashboard com drag-and-drop
	
	dashboard_cards();
	
	// ===== Verificar atualizações disponíveis
	
	dashboard_verificar_atualizacao();
	
	// ===== Toasts
	
	dashboard_toast_atualizacoes();
}

function dashboard_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'listar':
			
		break;
	}
}

// ==== Start

function dashboard_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			//case 'opcao': dashboard_ajax_opcao(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		dashboard_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'inicio': dashboard_pagina_inicial(); break;
		}
		
		interface_finalizar();
	}
}

dashboard_start();

?>