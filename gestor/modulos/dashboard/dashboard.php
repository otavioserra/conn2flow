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
			
			$botaoNegativeMessageLayout = modelo_var_troca($botaoNegativeMessageLayout,"#url#",'<a href="'.$_GESTOR['url-raiz'].'host-update/">'.$_GESTOR['url-raiz'].'host-update/</a>');
			
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
 * Gera os SVGs decorativos baseados nos ícones dos módulos.
 * 
 * @param string $icon Nome do ícone principal do Fomantic-UI
 * @param string $icon2 Nome do ícone secundário (opcional)
 * @return string SVG gerado
 */
function dashboard_gerar_svg_modulo($icon, $icon2 = null){
	// Mapeamento de ícones Fomantic-UI para paths SVG
	$svg_paths = array(
		'cog' => '<path d="M50 35a15 15 0 1 0 0 30 15 15 0 0 0 0-30zm0 25a10 10 0 1 1 0-20 10 10 0 0 1 0 20z"/><path d="M90 45h-7.5c-.8-3.2-2-6.2-3.6-9l5.3-5.3a5 5 0 0 0 0-7.1l-7.8-7.8a5 5 0 0 0-7.1 0l-5.3 5.3c-2.8-1.6-5.8-2.8-9-3.6V10a5 5 0 0 0-5-5h-11a5 5 0 0 0-5 5v7.5c-3.2.8-6.2 2-9 3.6l-5.3-5.3a5 5 0 0 0-7.1 0l-7.8 7.8a5 5 0 0 0 0 7.1l5.3 5.3c-1.6 2.8-2.8 5.8-3.6 9H10a5 5 0 0 0-5 5v11a5 5 0 0 0 5 5h7.5c.8 3.2 2 6.2 3.6 9l-5.3 5.3a5 5 0 0 0 0 7.1l7.8 7.8a5 5 0 0 0 7.1 0l5.3-5.3c2.8 1.6 5.8 2.8 9 3.6V90a5 5 0 0 0 5 5h11a5 5 0 0 0 5-5v-7.5c3.2-.8 6.2-2 9-3.6l5.3 5.3a5 5 0 0 0 7.1 0l7.8-7.8a5 5 0 0 0 0-7.1l-5.3-5.3c1.6-2.8 2.8-5.8 3.6-9H90a5 5 0 0 0 5-5V50a5 5 0 0 0-5-5z"/>',
		'cogs' => '<path d="M25 20a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"/><path d="M47 27h-5c-.5-2-1.3-4-2.4-5.8l3.5-3.5a3 3 0 0 0 0-4.2l-5-5a3 3 0 0 0-4.2 0l-3.5 3.5c-1.8-1.1-3.8-1.9-5.8-2.4V5a3 3 0 0 0-3-3h-7a3 3 0 0 0-3 3v4.6c-2 .5-4 1.3-5.8 2.4l-3.5-3.5a3 3 0 0 0-4.2 0l-5 5a3 3 0 0 0 0 4.2l3.5 3.5c-1.1 1.8-1.9 3.8-2.4 5.8H3a3 3 0 0 0-3 3v7a3 3 0 0 0 3 3h4.6c.5 2 1.3 4 2.4 5.8l-3.5 3.5a3 3 0 0 0 0 4.2l5 5a3 3 0 0 0 4.2 0l3.5-3.5c1.8 1.1 3.8 1.9 5.8 2.4V67a3 3 0 0 0 3 3h7a3 3 0 0 0 3-3v-4.6c2-.5 4-1.3 5.8-2.4l3.5 3.5a3 3 0 0 0 4.2 0l5-5a3 3 0 0 0 0-4.2l-3.5-3.5c1.1-1.8 1.9-3.8 2.4-5.8H47a3 3 0 0 0 3-3v-7a3 3 0 0 0-3-3z"/><path d="M75 55a10 10 0 1 0 0 20 10 10 0 0 0 0-20z" opacity=".7"/><path d="M97 62h-5c-.5-2-1.3-4-2.4-5.8l3.5-3.5a3 3 0 0 0 0-4.2l-5-5a3 3 0 0 0-4.2 0l-3.5 3.5c-1.8-1.1-3.8-1.9-5.8-2.4V40a3 3 0 0 0-3-3h-7a3 3 0 0 0-3 3v4.6c-2 .5-4 1.3-5.8 2.4l-3.5-3.5a3 3 0 0 0-4.2 0l-5 5a3 3 0 0 0 0 4.2l3.5 3.5c-1.1 1.8-1.9 3.8-2.4 5.8H53a3 3 0 0 0-3 3v7a3 3 0 0 0 3 3h4.6c.5 2 1.3 4 2.4 5.8l-3.5 3.5a3 3 0 0 0 0 4.2l5 5a3 3 0 0 0 4.2 0l3.5-3.5c1.8 1.1 3.8 1.9 5.8 2.4V97a3 3 0 0 0 3 3h7a3 3 0 0 0 3-3v-4.6c2-.5 4-1.3 5.8-2.4l3.5 3.5a3 3 0 0 0 4.2 0l5-5a3 3 0 0 0 0-4.2l-3.5-3.5c1.1-1.8 1.9-3.8 2.4-5.8H97a3 3 0 0 0 3-3v-7a3 3 0 0 0-3-3z" opacity=".7"/>',
		'file' => '<path d="M80 30H60V10L80 30z"/><path d="M55 10H20a5 5 0 0 0-5 5v70a5 5 0 0 0 5 5h60a5 5 0 0 0 5-5V35H60a5 5 0 0 1-5-5V10z"/>',
		'file image outline' => '<path d="M80 30H60V10L80 30z"/><path d="M55 10H20a5 5 0 0 0-5 5v70a5 5 0 0 0 5 5h60a5 5 0 0 0 5-5V35H60a5 5 0 0 1-5-5V10z" fill="none" stroke="currentColor" stroke-width="3"/><circle cx="35" cy="50" r="8"/><path d="M25 75l15-15 10 10 15-20 15 25H25z"/>',
		'object ungroup outline' => '<rect x="5" y="5" width="50" height="50" rx="5" fill="none" stroke="currentColor" stroke-width="3"/><rect x="45" y="45" width="50" height="50" rx="5" fill="none" stroke="currentColor" stroke-width="3"/>',
		'users' => '<circle cx="50" cy="25" r="15"/><path d="M50 45c-20 0-35 12-35 27v8h70v-8c0-15-15-27-35-27z"/><circle cx="25" cy="30" r="10" opacity=".6"/><circle cx="75" cy="30" r="10" opacity=".6"/>',
		'user' => '<circle cx="50" cy="30" r="20"/><path d="M50 55c-25 0-40 15-40 30v10h80v-10c0-15-15-30-40-30z"/>',
		'folder' => '<path d="M90 30H50l-10-15H10a5 5 0 0 0-5 5v60a5 5 0 0 0 5 5h80a5 5 0 0 0 5-5V35a5 5 0 0 0-5-5z"/>',
		'folder open' => '<path d="M10 20h25l10 10h45a5 5 0 0 1 5 5v5H5v-15a5 5 0 0 1 5-5z"/><path d="M5 45h85l10 40H15L5 45z"/>',
		'shopping cart' => '<circle cx="35" cy="85" r="8"/><circle cx="70" cy="85" r="8"/><path d="M10 10h10l5 10h70l-15 40H30L15 10z"/>',
		'box' => '<path d="M50 5L5 25v50l45 20 45-20V25L50 5z"/><path d="M50 55v40M5 25l45 30 45-30" fill="none" stroke="currentColor" stroke-width="2"/>',
		'database' => '<ellipse cx="50" cy="20" rx="40" ry="15"/><path d="M10 20v60c0 8.3 17.9 15 40 15s40-6.7 40-15V20" fill="none" stroke="currentColor" stroke-width="3"/><path d="M10 50c0 8.3 17.9 15 40 15s40-6.7 40-15" fill="none" stroke="currentColor" stroke-width="3"/>',
		'globe' => '<circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="3"/><ellipse cx="50" cy="50" rx="20" ry="40" fill="none" stroke="currentColor" stroke-width="2"/><path d="M10 50h80M15 30h70M15 70h70" fill="none" stroke="currentColor" stroke-width="2"/>',
		'home' => '<path d="M50 10L10 45h15v40h20V60h10v25h20V45h15L50 10z"/>',
		'dashboard' => '<path d="M5 5h40v40H5zM55 5h40v25H55zM5 55h40v40H5zM55 40h40v55H55z"/>',
		'chart bar' => '<rect x="10" y="60" width="15" height="30"/><rect x="30" y="40" width="15" height="50"/><rect x="50" y="20" width="15" height="70"/><rect x="70" y="50" width="15" height="40"/>',
		'chart line' => '<path d="M10 80l25-30 20 20 35-55" fill="none" stroke="currentColor" stroke-width="4"/>',
		'envelope' => '<rect x="5" y="20" width="90" height="60" rx="5"/><path d="M5 25l45 30 45-30" fill="none" stroke="currentColor" stroke-width="2"/>',
		'bell' => '<path d="M50 95c5.5 0 10-4.5 10-10H40c0 5.5 4.5 10 10 10z"/><path d="M80 70c-5-5-10-10-10-30 0-15-10-25-20-25s-20 10-20 25c0 20-5 25-10 30v10h60V70z"/>',
		'lock' => '<rect x="20" y="40" width="60" height="50" rx="5"/><path d="M30 40V30a20 20 0 0 1 40 0v10" fill="none" stroke="currentColor" stroke-width="5"/>',
		'key' => '<circle cx="30" cy="30" r="20" fill="none" stroke="currentColor" stroke-width="5"/><path d="M45 45l45 45M70 70l15 15M80 60l15 15"/>',
		'plug' => '<path d="M35 5v30M65 5v30M25 35h50v20c0 25-20 40-25 40s-25-15-25-40V35z"/>',
		'code' => '<path d="M35 25L10 50l25 25M65 25l25 25-25 25M55 15l-10 70" fill="none" stroke="currentColor" stroke-width="5"/>',
		'terminal' => '<rect x="5" y="10" width="90" height="80" rx="5" fill="none" stroke="currentColor" stroke-width="3"/><path d="M20 40l20 15-20 15M50 70h30" fill="none" stroke="currentColor" stroke-width="4"/>',
		'paint brush' => '<path d="M15 60c-10 10-10 35 0 35 15 0 20-15 35-30l35-45c5-5 5-10 0-15s-10-5-15 0L25 40c-15 15-30 20-10 20z"/>',
		'image' => '<rect x="5" y="15" width="90" height="70" rx="5"/><circle cx="30" cy="40" r="10"/><path d="M5 75l25-25 20 20 20-30 25 35"/>',
		'video' => '<rect x="5" y="20" width="60" height="60" rx="5"/><path d="M70 35l25-15v60l-25-15V35z"/>',
		'music' => '<circle cx="25" cy="75" r="15"/><circle cx="75" cy="75" r="15"/><path d="M40 75V15l50-10v70"/>',
		'calendar' => '<rect x="10" y="15" width="80" height="75" rx="5"/><path d="M10 35h80M30 5v20M70 5v20"/>',
		'clock' => '<circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="4"/><path d="M50 25v25l20 15" fill="none" stroke="currentColor" stroke-width="4"/>',
		'map' => '<path d="M5 15l30 10 30-10 30 10v70l-30-10-30 10-30-10V15z"/><path d="M35 25v60M65 15v60" fill="none" stroke="currentColor" stroke-width="2"/>',
		'star' => '<path d="M50 5l12 36h38l-31 22 12 36-31-22-31 22 12-36L0 41h38z"/>',
		'heart' => '<path d="M50 90C20 65 5 45 5 30 5 15 20 5 35 5c10 0 15 5 15 10 0-5 5-10 15-10 15 0 30 10 30 25 0 15-15 35-45 60z"/>',
		'bookmark' => '<path d="M20 5h60v90l-30-20-30 20V5z"/>',
		'tag' => '<path d="M5 5h40l50 50-40 40L5 45V5z"/><circle cx="30" cy="30" r="10"/>',
		'tags' => '<path d="M15 5h35l45 45-35 35L15 40V5z"/><circle cx="35" cy="25" r="8"/><path d="M5 15h35l45 45-35 35" fill="none" stroke="currentColor" stroke-width="3"/>',
		'comment' => '<path d="M10 10h80a5 5 0 0 1 5 5v50a5 5 0 0 1-5 5H30l-20 20V70H10a5 5 0 0 1-5-5V15a5 5 0 0 1 5-5z"/>',
		'comments' => '<path d="M15 5h50a5 5 0 0 1 5 5v30a5 5 0 0 1-5 5H35l-15 15V45H15a5 5 0 0 1-5-5V10a5 5 0 0 1 5-5z"/><path d="M75 30h10a5 5 0 0 1 5 5v30a5 5 0 0 1-5 5h-5v15l-15-15H40" fill="none" stroke="currentColor" stroke-width="3"/>',
		'question circle outline' => '<circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="4"/><path d="M35 35c0-10 8-15 15-15s15 5 15 15c0 10-10 12-15 20v5" fill="none" stroke="currentColor" stroke-width="4"/><circle cx="50" cy="75" r="4"/>',
		'info circle' => '<circle cx="50" cy="50" r="40"/><circle cx="50" cy="30" r="5" fill="#fff"/><rect x="45" y="40" width="10" height="35" fill="#fff"/>',
		'exclamation triangle' => '<path d="M50 5L5 90h90L50 5z"/><rect x="45" y="35" width="10" height="30" fill="#fff"/><circle cx="50" cy="75" r="5" fill="#fff"/>',
		'check circle' => '<circle cx="50" cy="50" r="40"/><path d="M30 50l15 15 25-30" fill="none" stroke="#fff" stroke-width="6"/>',
		'times circle' => '<circle cx="50" cy="50" r="40"/><path d="M35 35l30 30M65 35l-30 30" fill="none" stroke="#fff" stroke-width="6"/>',
		'plus' => '<path d="M45 10v80M10 50h80" stroke="currentColor" stroke-width="10"/>',
		'minus' => '<path d="M10 50h80" stroke="currentColor" stroke-width="10"/>',
		'edit' => '<path d="M70 10l20 20-50 50H20V60L70 10z"/><path d="M60 20l20 20"/>',
		'trash' => '<path d="M20 30h60v60a5 5 0 0 1-5 5H25a5 5 0 0 1-5-5V30z"/><path d="M10 30h80M40 10h20v20H40z"/><path d="M40 45v30M50 45v30M60 45v30"/>',
		'download' => '<path d="M50 5v60M30 45l20 20 20-20"/><path d="M10 75v15h80V75"/>',
		'upload' => '<path d="M50 65V5M30 25l20-20 20 20"/><path d="M10 75v15h80V75"/>',
		'sync' => '<path d="M15 50c0-20 15-35 35-35v15l25-20-25-20v15C25 5 5 25 5 50h10z"/><path d="M85 50c0 20-15 35-35 35v-15l-25 20 25 20v-15c25 0 45-20 45-45h-10z"/>',
		'refresh' => '<path d="M85 50c0-20-15-35-35-35-15 0-27 9-33 22" fill="none" stroke="currentColor" stroke-width="6"/><path d="M15 50c0 20 15 35 35 35 15 0 27-9 33-22" fill="none" stroke="currentColor" stroke-width="6"/><path d="M5 25l15 15 15-15M95 75l-15-15-15 15"/>',
		'search' => '<circle cx="40" cy="40" r="30" fill="none" stroke="currentColor" stroke-width="6"/><path d="M60 60l30 30" stroke="currentColor" stroke-width="8"/>',
		'filter' => '<path d="M10 10h80l-30 40v35l-20 10V50L10 10z"/>',
		'sort' => '<path d="M25 20v60l-15-15M75 80V20l15 15"/>',
		'list' => '<path d="M5 20h10v10H5zM25 20h70v10H25zM5 45h10v10H5zM25 45h70v10H25zM5 70h10v10H5zM25 70h70v10H25z"/>',
		'grid' => '<rect x="5" y="5" width="40" height="40"/><rect x="55" y="5" width="40" height="40"/><rect x="5" y="55" width="40" height="40"/><rect x="55" y="55" width="40" height="40"/>',
		'th' => '<rect x="5" y="5" width="25" height="25"/><rect x="37" y="5" width="25" height="25"/><rect x="70" y="5" width="25" height="25"/><rect x="5" y="37" width="25" height="25"/><rect x="37" y="37" width="25" height="25"/><rect x="70" y="37" width="25" height="25"/><rect x="5" y="70" width="25" height="25"/><rect x="37" y="70" width="25" height="25"/><rect x="70" y="70" width="25" height="25"/>',
		'sign out alternate' => '<path d="M60 20V10H20v80h40V80"/><path d="M40 50h50M75 35l15 15-15 15"/>',
		'arrow right' => '<path d="M10 50h70M60 30l20 20-20 20"/>',
		'arrow left' => '<path d="M90 50H20M40 30L20 50l20 20"/>',
		'external alternate' => '<path d="M70 10h20v20M55 45l35-35M80 45v35a5 5 0 0 1-5 5H20a5 5 0 0 1-5-5V20a5 5 0 0 1 5-5h35"/>',
		'book' => '<path d="M50 10C35 10 25 15 25 15v70s10-5 25-5 25 5 25 5V15s-10-5-25-5z"/><path d="M10 15v70c15 0 25 5 25 5V20s-10-5-25-5zM90 15v70c-15 0-25 5-25 5V20s10-5 25-5z"/>',
		'graduation cap' => '<path d="M50 15L5 35l45 20 45-20-45-20z"/><path d="M25 45v25c0 10 11.2 15 25 15s25-5 25-15V45"/><path d="M85 35v30l5 15-5 15"/>',
		'briefcase' => '<rect x="10" y="30" width="80" height="55" rx="5"/><path d="M35 30V20a5 5 0 0 1 5-5h20a5 5 0 0 1 5 5v10"/><path d="M10 55h80"/>',
		'building' => '<rect x="15" y="10" width="70" height="80" rx="3"/><rect x="25" y="20" width="15" height="15"/><rect x="60" y="20" width="15" height="15"/><rect x="25" y="45" width="15" height="15"/><rect x="60" y="45" width="15" height="15"/><rect x="40" y="70" width="20" height="20"/>',
		'industry' => '<path d="M5 90V50l30-20v20l30-20v20l30-20v60H5z"/>',
		'truck' => '<rect x="5" y="35" width="55" height="40" rx="3"/><path d="M60 50h25l10 25H60z"/><circle cx="25" cy="80" r="10"/><circle cx="80" cy="80" r="10"/>',
		'plane' => '<path d="M90 50L60 35V20L50 10 40 20v15L10 50l10 5 25-5v20l-10 10v10l25-10 25 10V70l-10-10V45l25 5 10-5z"/>',
		'rocket' => '<path d="M50 5c-15 25-15 60 0 70 15-10 15-45 0-70z"/><path d="M35 75l-15 20 20-5-5-15zM65 75l15 20-20-5 5-15z"/><circle cx="50" cy="40" r="10"/>',
		'magic' => '<path d="M5 90l60-60 25 25-60 60L5 90z"/><path d="M60 35l15-15M75 20l10-5-5 10 10 5-10-5-5 10 5-10-10-5z"/>',
		'wand' => '<path d="M10 85l55-55 25 25-55 55-25-25z"/><circle cx="75" cy="20" r="3"/><circle cx="85" cy="30" r="2"/><circle cx="90" cy="15" r="2"/><circle cx="70" cy="10" r="2"/>',
		'puzzle' => '<path d="M45 10h10c5 0 10 5 10 10s-5 10-10 10v15h15c0-5 5-10 10-10s10 5 10 10H75v15c5 0 10 5 10 10s-5 10-10 10v15H60c0-5-5-10-10-10s-10 5-10 10H25V80c-5 0-10-5-10-10s5-10 10-10V45H40c0 5 5 10 10 10s10-5 10-10H45V10z"/>',
		'shield' => '<path d="M50 5L10 20v30c0 25 20 40 40 45 20-5 40-20 40-45V20L50 5z"/>',
		'certificate' => '<circle cx="50" cy="40" r="30" fill="none" stroke="currentColor" stroke-width="5"/><path d="M35 65v30l15-10 15 10V65"/>',
		'trophy' => '<path d="M25 10h50v30c0 15-12.5 25-25 25s-25-10-25-25V10z"/><path d="M25 25H10c0 15 7.5 25 15 25M75 25h15c0 15-7.5 25-15 25"/><path d="M40 65h20v10H40zM35 75h30v10H35z"/>',
		'flag' => '<path d="M20 5v90"/><path d="M20 10h60l-15 20 15 20H20V10z"/>',
		'bolt' => '<path d="M55 5L25 55h25L40 95l40-55H55L70 5H55z"/>',
		'fire' => '<path d="M50 5c-5 15 5 25 0 40-10-5-20-20-15-40C20 20 10 45 10 60c0 25 20 35 40 35s40-10 40-35c0-20-15-35-30-45-5 10-15 0-10-15z"/>',
		'leaf' => '<path d="M90 10C60 10 20 30 10 90c30-20 50-30 80-30V10z"/><path d="M10 90c20-30 40-50 80-80" fill="none" stroke="currentColor" stroke-width="3"/>',
		'sun' => '<circle cx="50" cy="50" r="20"/><path d="M50 5v15M50 80v15M5 50h15M80 50h15M18 18l10 10M72 72l10 10M82 18l-10 10M28 72l-10 10"/>',
		'moon' => '<path d="M70 15c-25 0-45 20-45 45s20 45 45 45c5 0 10-.8 15-2-10 5-22 7-35 2-20-8-35-30-30-55 3-15 15-30 35-37-5 0-10 2-15 2z"/>',
		'cloud' => '<circle cx="35" cy="60" r="25"/><circle cx="60" cy="45" r="30"/><circle cx="80" cy="60" r="20"/><rect x="10" y="60" width="80" height="30"/>',
		'umbrella' => '<path d="M50 10c-35 0-45 40-45 40h40v40c0 5-5 5-5 5s-5 0-5-5M95 50s-10-40-45-40"/>',
		'anchor' => '<circle cx="50" cy="20" r="10" fill="none" stroke="currentColor" stroke-width="4"/><path d="M50 30v60M20 60c0 20 13.4 30 30 30s30-10 30-30" fill="none" stroke="currentColor" stroke-width="4"/><path d="M50 50H30l20 10 20-10H50z"/>',
		'life ring' => '<circle cx="50" cy="50" r="35" fill="none" stroke="currentColor" stroke-width="10"/><circle cx="50" cy="50" r="15" fill="none" stroke="currentColor" stroke-width="4"/>',
		'compass' => '<circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="4"/><path d="M50 20l10 30-10 10-10-10zM50 80l-10-30 10-10 10 10z"/>',
		'sitemap' => '<rect x="40" y="5" width="20" height="15"/><rect x="10" y="40" width="20" height="15"/><rect x="40" y="40" width="20" height="15"/><rect x="70" y="40" width="20" height="15"/><rect x="10" y="75" width="20" height="15"/><rect x="40" y="75" width="20" height="15"/><rect x="70" y="75" width="20" height="15"/><path d="M50 20v20M20 40v-10h60v10M20 55v20M50 55v20M80 55v20" fill="none" stroke="currentColor" stroke-width="2"/>',
		'microphone' => '<rect x="35" y="5" width="30" height="50" rx="15"/><path d="M20 45c0 20 13.4 30 30 30s30-10 30-30" fill="none" stroke="currentColor" stroke-width="5"/><path d="M50 75v20"/>',
		'headphones' => '<path d="M15 50c0-20 15.7-35 35-35s35 15 35 35" fill="none" stroke="currentColor" stroke-width="6"/><rect x="10" y="50" width="15" height="35" rx="5"/><rect x="75" y="50" width="15" height="35" rx="5"/>',
		'volume up' => '<path d="M10 35h20l25-20v70l-25-20H10V35z"/><path d="M65 30c10 10 10 30 0 40M75 20c15 15 15 45 0 60" fill="none" stroke="currentColor" stroke-width="4"/>',
		'wifi' => '<circle cx="50" cy="80" r="5"/><path d="M30 65c10-10 30-10 40 0M20 55c15-15 45-15 60 0M10 45c20-20 60-20 80 0" fill="none" stroke="currentColor" stroke-width="4"/>',
		'bluetooth' => '<path d="M35 30l30 20-30 20M50 10v80l25-30-25-20 25-20z" fill="none" stroke="currentColor" stroke-width="4"/>',
		'battery full' => '<rect x="5" y="25" width="80" height="50" rx="5"/><rect x="85" y="35" width="10" height="30"/><rect x="15" y="35" width="60" height="30"/>',
		'battery half' => '<rect x="5" y="25" width="80" height="50" rx="5"/><rect x="85" y="35" width="10" height="30"/><rect x="15" y="35" width="30" height="30"/>',
		'battery empty' => '<rect x="5" y="25" width="80" height="50" rx="5" fill="none" stroke="currentColor" stroke-width="4"/><rect x="85" y="35" width="10" height="30"/>',
		'signal' => '<rect x="5" y="70" width="15" height="20"/><rect x="25" y="55" width="15" height="35"/><rect x="45" y="40" width="15" height="50"/><rect x="65" y="25" width="15" height="65"/><rect x="85" y="10" width="10" height="80"/>',
		'qrcode' => '<rect x="10" y="10" width="30" height="30"/><rect x="60" y="10" width="30" height="30"/><rect x="10" y="60" width="30" height="30"/><rect x="18" y="18" width="14" height="14" fill="#fff"/><rect x="68" y="18" width="14" height="14" fill="#fff"/><rect x="18" y="68" width="14" height="14" fill="#fff"/><rect x="60" y="60" width="10" height="10"/><rect x="80" y="60" width="10" height="10"/><rect x="60" y="80" width="10" height="10"/><rect x="80" y="80" width="10" height="10"/><rect x="70" y="70" width="10" height="10"/>',
		'barcode' => '<rect x="10" y="20" width="5" height="60"/><rect x="20" y="20" width="10" height="60"/><rect x="35" y="20" width="5" height="60"/><rect x="45" y="20" width="15" height="60"/><rect x="65" y="20" width="5" height="60"/><rect x="75" y="20" width="10" height="60"/><rect x="90" y="20" width="5" height="60"/>',
		'credit card' => '<rect x="5" y="20" width="90" height="60" rx="5"/><rect x="5" y="35" width="90" height="15"/>',
		'money' => '<rect x="5" y="20" width="90" height="60" rx="5"/><circle cx="50" cy="50" r="15"/><circle cx="20" cy="50" r="5"/><circle cx="80" cy="50" r="5"/>',
		'percent' => '<circle cx="30" cy="30" r="15" fill="none" stroke="currentColor" stroke-width="5"/><circle cx="70" cy="70" r="15" fill="none" stroke="currentColor" stroke-width="5"/><path d="M80 20L20 80" stroke="currentColor" stroke-width="5"/>',
		'hashtag' => '<path d="M25 5v90M45 5v90M5 30h60M5 60h60" stroke="currentColor" stroke-width="8"/>',
		'at' => '<circle cx="50" cy="50" r="15" fill="none" stroke="currentColor" stroke-width="4"/><path d="M65 35v25c0 10 15 10 15 0" fill="none" stroke="currentColor" stroke-width="4"/><circle cx="50" cy="50" r="35" fill="none" stroke="currentColor" stroke-width="4"/>',
		'link' => '<path d="M45 55L55 45M35 65c-10-10-10-25 0-35l15-15c10-10 25-10 35 0s10 25 0 35l-7.5 7.5" fill="none" stroke="currentColor" stroke-width="6"/><path d="M65 35c10 10 10 25 0 35L50 85c-10 10-25 10-35 0s-10-25 0-35l7.5-7.5" fill="none" stroke="currentColor" stroke-width="6"/>',
		'unlink' => '<path d="M35 65c-10-10-10-25 0-35l15-15c10-10 25-10 35 0" fill="none" stroke="currentColor" stroke-width="6"/><path d="M65 35c10 10 10 25 0 35L50 85c-10 10-25 10-35 0" fill="none" stroke="currentColor" stroke-width="6"/><path d="M20 80l15 15M80 20L65 5"/>',
		'paperclip' => '<path d="M75 35L35 75c-10 10-25 10-35 0s-10-25 0-35l50-50c7-7 17-7 24 0s7 17 0 24L34 54c-3 3-8 3-11 0s-3-8 0-11l30-30" fill="none" stroke="currentColor" stroke-width="4"/>',
		'copy' => '<rect x="25" y="25" width="50" height="65" rx="3"/><path d="M25 25V15a5 5 0 0 1 5-5h40a5 5 0 0 1 5 5v50a5 5 0 0 1-5 5H65" fill="none" stroke="currentColor" stroke-width="3"/>',
		'clipboard' => '<rect x="20" y="15" width="60" height="75" rx="5"/><rect x="35" y="5" width="30" height="20" rx="3"/><path d="M35 45h30M35 60h30M35 75h20"/>',
		'save' => '<rect x="10" y="10" width="80" height="80" rx="5"/><rect x="25" y="10" width="50" height="30"/><rect x="55" y="15" width="10" height="20"/><rect x="25" y="55" width="50" height="30"/>',
		'print' => '<rect x="20" y="40" width="60" height="35" rx="3"/><path d="M30 40V20h40v20"/><rect x="30" y="55" width="40" height="25" rx="2"/><circle cx="70" cy="50" r="3"/>',
		'share' => '<circle cx="75" cy="25" r="12"/><circle cx="75" cy="75" r="12"/><circle cx="25" cy="50" r="12"/><path d="M37 44l26-13M37 56l26 13" stroke="currentColor" stroke-width="3"/>',
		'expand' => '<path d="M5 35V5h30M65 5h30v30M95 65v30H65M35 95H5V65" fill="none" stroke="currentColor" stroke-width="5"/>',
		'compress' => '<path d="M35 5v30H5M65 35h30V5M95 65H65v30M5 65h30v30" fill="none" stroke="currentColor" stroke-width="5"/>',
		'eye' => '<path d="M50 25C25 25 5 50 5 50s20 25 45 25 45-25 45-25-20-25-45-25z"/><circle cx="50" cy="50" r="15" fill="#fff"/>',
		'eye slash' => '<path d="M50 25C25 25 5 50 5 50s20 25 45 25 45-25 45-25-20-25-45-25z"/><circle cx="50" cy="50" r="15" fill="#fff"/><path d="M15 85L85 15" stroke="currentColor" stroke-width="5"/>',
		'thumbs up' => '<path d="M35 45V85H20V45zM35 45c0-15 5-35 20-35 10 0 10 15 5 25h25c5 0 10 5 10 10L80 85H35"/>',
		'thumbs down' => '<path d="M65 55V15h15v40zM65 55c0 15-5 35-20 35-10 0-10-15-5-25H15c-5 0-10-5-10-10L20 15h45"/>',
		'hand point right' => '<rect x="5" y="35" width="60" height="30" rx="15"/><path d="M65 50h25c5 0 5 10 0 10H75M65 40v-10c0-5 10-5 10 0v10M75 30v-10c0-5 10-5 10 0v20M85 40v-15c0-5 10-5 10 0v25"/>',
		'hand paper' => '<rect x="25" y="40" width="50" height="50" rx="10"/><path d="M35 40V15c0-5 10-5 10 0v25M50 40V10c0-5 10-5 10 0v30M65 40V15c0-5 10-5 10 0v25M25 60H10c-5 0-5-10 0-10h15"/>',
		'spinner' => '<path d="M50 10v15M50 75v15M90 50H75M25 50H10M78 22L67 33M33 67L22 78M78 78L67 67M33 33L22 22"/>',
		'circle notch' => '<circle cx="50" cy="50" r="35" fill="none" stroke="currentColor" stroke-width="8" stroke-dasharray="165" stroke-dashoffset="40"/>',
	);
	
	// Ícone padrão se não encontrado
	$default_svg = '<circle cx="50" cy="50" r="35" fill="none" stroke="currentColor" stroke-width="4"/><path d="M35 35c0-10 8-15 15-15s15 5 15 15c0 10-10 12-15 20v5" fill="none" stroke="currentColor" stroke-width="4"/><circle cx="50" cy="75" r="4"/>';
	
	// Normalizar o nome do ícone
	$icon_key = str_replace(' icon', '', $icon);
	$icon_key = str_replace(' huge', '', $icon_key);
	$icon_key = str_replace(' large', '', $icon_key);
	$icon_key = trim($icon_key);
	
	// Buscar o path do SVG
	$svg_path = isset($svg_paths[$icon_key]) ? $svg_paths[$icon_key] : $default_svg;
	
	// Montar o SVG completo
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="currentColor">' . $svg_path . '</svg>';
	
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