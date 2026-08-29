<?php

// ===== Força charset UTF-8 em todo o sistema

ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// =========================== Configuração Inicial

require_once(__DIR__ . '/config.php');

// =========================== Funções de Montagem da Página

/**
 * Resolve o ícone de um módulo no vocabulário do menu que está sendo desenhado (req-086).
 *
 * O mesmo módulo aparece em menus de frameworks diferentes: o painel legado desenha
 * `<i class="credit card outline icon">` (Fomantic UI) e os layouts Tailwind desenham
 * `<i data-lucide="credit-card">`. Os catálogos não se traduzem — um nome do Lucide colocado em
 * `modulos.icone` simplesmente não existe no Fomantic e o ícone some da tela sem erro nenhum.
 *
 * `icone`/`icone2` seguem sendo o vocabulário Fomantic (o histórico e o padrão de toda instalação
 * existente); `icone_tailwind`/`icone2_tailwind` guardam o par.
 *
 * O fallback para o campo legado quando o par não foi declarado vale só para o ícone PRINCIPAL: é o
 * que preserva o menu de instalações Tailwind que hoje escrevem o nome do Lucide direto em `icone`.
 * No ícone ANCORADO o fallback seria nocivo — `icone2` guarda modificadores de posicionamento do
 * Fomantic ("bottom right corner list") que não existem no Lucide, e herdá-lo abriria a célula de
 * dois ícones para desenhar um `<i>` sem alvo nenhum.
 *
 * @param array $modulo Registro do módulo vindo da tabela `modulos`.
 * @param string $campo Campo base a resolver: `icone` ou `icone2`.
 * @param bool $tailwind Se o menu em desenho usa o framework Tailwind.
 *
 * @return string Nome do ícone, ou string vazia quando o módulo não declara nenhum.
 */
function gestor_pagina_menu_icone($modulo, $campo, $tailwind){
	$legado = isset($modulo[$campo]) && is_string($modulo[$campo]) ? trim($modulo[$campo]) : '';

	if(!$tailwind){
		return $legado;
	}

	$campoTailwind = $campo.'_tailwind';
	$especifico = isset($modulo[$campoTailwind]) && is_string($modulo[$campoTailwind]) ? trim($modulo[$campoTailwind]) : '';

	if($especifico !== ''){
		return $especifico;
	}

	return $campo === 'icone' ? $legado : '';
}


function gestor_pagina_menu($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// ===== 
	
	if(!isset($_GESTOR['usuario-token-id'])){
		return '';
	}
	
	// ===== Layout do menu
	//
	// req-118: o menu acompanha o framework da requisição (layout + página). A árvore de permissões,
	// os grupos e as células são EXATAMENTE as mesmas nas duas variantes — só o HTML muda —, então
	// nenhuma regra de acesso foi duplicada.

	$menuFramework = gestor_framework_css_atual();
	$menuTailwind = ($menuFramework['modo'] === 'tailwindcss');

	$componenteMenuId = ($menuTailwind ? 'menu-principal-sistema-tailwind' : 'menu-principal-sistema');

	// req-123: Override de componente de menu por projeto ($_GESTOR['project-admin-menu-components'])
	$layoutAtualId = (isset($_GESTOR['layout#id']) && is_string($_GESTOR['layout#id']) && $_GESTOR['layout#id'] !== '')
		? $_GESTOR['layout#id']
		: ($_GESTOR['layout-id'] ?? null);

	if (isset($_GESTOR['project-admin-menu-components']) && is_array($_GESTOR['project-admin-menu-components'])) {
		if ($layoutAtualId && isset($_GESTOR['project-admin-menu-components'][$layoutAtualId])) {
			$componenteMenuId = $_GESTOR['project-admin-menu-components'][$layoutAtualId];
		} elseif (isset($_GESTOR['project-admin-menu-components']['padrao'])) {
			$componenteMenuId = $_GESTOR['project-admin-menu-components']['padrao'];
		} elseif (isset($_GESTOR['project-admin-menu-components']['default'])) {
			$componenteMenuId = $_GESTOR['project-admin-menu-components']['default'];
		}
	}

	// req-086: Configuração dos itens PADRÃO do menu por projeto ($_GESTOR['project-admin-menu-config']).
	//
	// Dashboard e Sair não vêm da árvore de módulos: o motor os injeta sempre, no topo e no rodapé.
	// Um layout autoral pode já resolver os dois por conta própria (dashboard na navegação, logout
	// no menu do avatar) e aí o item do motor vira duplicata — mas o MESMO projeto continua servindo
	// o painel genérico, onde os dois itens são a única saída. Por isso a chave é o layout, igual ao
	// mapa de componentes acima, e não uma decisão global do projeto.
	//
	// Dentro de cada layout, `perfis` guarda exceções por perfil de usuário: um perfil listado usa a
	// configuração dele, qualquer outro cai na do layout. Perfil administrativo pode assim manter o
	// Dashboard e o Sair numa tela em que o assinante não os vê.
	//
	// A ausência da variável — e a de uma entrada para o layout corrente — mantém o comportamento
	// histórico intacto.
	$menuConfigProjeto = (isset($_GESTOR['project-admin-menu-config']) && is_array($_GESTOR['project-admin-menu-config']))
		? $_GESTOR['project-admin-menu-config']
		: Array();

	$menuConfig = Array();

	if($menuConfigProjeto){
		if($layoutAtualId && isset($menuConfigProjeto[$layoutAtualId]) && is_array($menuConfigProjeto[$layoutAtualId])){
			$menuConfig = $menuConfigProjeto[$layoutAtualId];
		} elseif (isset($menuConfigProjeto['padrao']) && is_array($menuConfigProjeto['padrao'])) {
			$menuConfig = $menuConfigProjeto['padrao'];
		} elseif (isset($menuConfigProjeto['default']) && is_array($menuConfigProjeto['default'])) {
			$menuConfig = $menuConfigProjeto['default'];
		}
	}

	$menu = gestor_componente(Array(
		'id' => $componenteMenuId,
	));
	
	$cel_nome = 'icon'; $cel[$cel_nome] = modelo_tag_val($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $menu = modelo_tag_in($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'icon-2'; $cel[$cel_nome] = modelo_tag_val($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $menu = modelo_tag_in($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'item'; $cel[$cel_nome] = modelo_tag_val($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $menu = modelo_tag_in($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'categoria'; $cel[$cel_nome] = modelo_tag_val($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $menu = modelo_tag_in($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'simples'; $cel[$cel_nome] = modelo_tag_val($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $menu = modelo_tag_in($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'itemContCel'; $cel[$cel_nome] = modelo_tag_val($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $menu = modelo_tag_in($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	$cel_nome = 'conteiner'; $cel[$cel_nome] = modelo_tag_val($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $menu = modelo_tag_in($menu,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	// ===== Verificar quais módulos o usuário pode acessar
	
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

	// req-086: identificador TEXTUAL do perfil corrente, unificando os três ramos acima — usuário de
	// host com perfil de gestor próprio (`gestor_perfil`), usuário de host que herda o perfil do pai
	// e usuário direto (`perfil`). É a chave usada pelas exceções por perfil da configuração de menu.
	$menuPerfilId = isset($perfil) ? $perfil : (isset($gestor_perfil) ? $gestor_perfil : null);

	if($menuPerfilId && !empty($menuConfig['perfis']) && is_array($menuConfig['perfis'])
		&& isset($menuConfig['perfis'][$menuPerfilId]) && is_array($menuConfig['perfis'][$menuPerfilId])){
		// A exceção SUBSTITUI a configuração do layout em vez de somar-se a ela: um perfil listado
		// declara por inteiro o que vê, e omitir uma flag ali significa "não esconder", não "herdar".
		$menuConfig = $menuConfig['perfis'][$menuPerfilId];
	}

	$menuOcultarDashboard = !empty($menuConfig['hide_dashboard']);
	$menuOcultarLogout = !empty($menuConfig['hide_logout']) || !empty($menuConfig['hide_signout']);

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
		." AND status='A'"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
	);
	
	// req-086: as colunas do vocabulário Tailwind chegam por migração e o código pode alcançar um
	// banco que ainda não a rodou. Sem o guard a consulta inteira falharia e o menu sumiria — o
	// desfecho aceitável é o menu seguir desenhando com o vocabulário legado.
	$camposModulos = Array(
		'id_modulos',
		'modulo_grupo_id', // campo textual
		'id',
		'nome',
		'icone',
		'icone2',
		'titulo',
		'plugin',
	);

	if(banco_campo_existe('icone_tailwind','modulos')){
		$camposModulos[] = 'icone_tailwind';
	}

	if(banco_campo_existe('icone2_tailwind','modulos')){
		$camposModulos[] = 'icone2_tailwind';
	}

	$modulos = banco_select_name
	(
		banco_campos_virgulas($camposModulos)
		,
		"modulos",
		"WHERE nao_menu_principal IS NULL"
		." AND status='A'"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
		." ORDER BY nome ASC"
	);

	$modulos_grupos = banco_select(Array(
		'tabela' => 'modulos_grupos',
		'campos' => Array(
			'id', // campo textual
			'nome',
			'menu_label',
			'ordemMenu',
		),
		'extra' => 
			"WHERE language='".$_GESTOR['linguagem-codigo']."' ORDER BY CASE WHEN ordemMenu IS NULL THEN 1 ELSE 0 END, ordemMenu ASC, nome ASC"
	));
	
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
	
	// ===== Montar o menu conforme permissão
	
	$dashboard = '';
	$grupos = Array();
	
	if($modulos)
	foreach($modulos as $modulo){
		// ===== Se o módulo tiver permissão de acesso incluir
		$modulo_perfil = false;
		
		if($modulo['id'] == 'dashboard'){
			$modulo_perfil = true;
		} else {
			if($usuarios_perfis_modulos)
			foreach($usuarios_perfis_modulos as $upm){
				if($upm['modulo'] == $modulo['id']){
					$modulo_perfil = true;
					break;
				}
			}
		}
		
		if(!$modulo_perfil){
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
		
		// ===== Montar ítem do menu do módulo
		
		$cel_nome = 'item';
		$cel_aux = $cel[$cel_nome];
		
		$cel_aux = modelo_var_troca($cel_aux,"#nome#",(existe($modulo['titulo']) ? $modulo['titulo'] : $modulo['nome']));
		// O estado ativo é escrito no vocabulário do framework: `.active` é uma classe do Fomantic e
		// não existe em Tailwind, onde o realce precisa vir de utilities.
		$menuClasseAtiva = $menuTailwind ? ' bg-slate-800 text-white' : ' active';

		$cel_aux = modelo_var_troca($cel_aux,"#class#",(isset($_GESTOR['modulo-id']) && $modulo['id'] == $_GESTOR['modulo-id'] ? $menuClasseAtiva : ''));
		
		// req-086: o vocabulário do ícone acompanha o framework do menu. A escolha é feita ANTES de
		// decidir a célula porque é o ícone secundário RESOLVIDO que diz se há dois ícones a
		// desenhar: um módulo com `icone2` só no vocabulário Fomantic não deve abrir a célula
		// dupla num menu Tailwind, senão o segundo `<i>` sai sem alvo.
		$moduloIcone = gestor_pagina_menu_icone($modulo,'icone',$menuTailwind);
		$moduloIcone2 = gestor_pagina_menu_icone($modulo,'icone2',$menuTailwind);

		if($moduloIcone2){
			$cel_nome_icon = 'icon-2';
			$cel_icon = $cel[$cel_nome_icon];

			$cel_icon = modelo_var_troca($cel_icon,"#icon-2#",$moduloIcone2);
			$cel_icon = modelo_var_troca($cel_icon,"#icon-2-lucide#",gestor_pagina_menu_icone_lucide_atributo($moduloIcone2));
		} else {
			$cel_nome_icon = 'icon';
			$cel_icon = $cel[$cel_nome_icon];
		}

		$iconePadrao = $menuTailwind ? 'circle-help' : 'question circle outline';
		$iconeFinal = $moduloIcone ? $moduloIcone : $iconePadrao;

		$cel_icon = modelo_var_troca($cel_icon,"#icon#",$iconeFinal);

		// req-125 F4: o ATRIBUTO `data-lucide` é montado aqui, não escrito no template.
		//
		//   Antes, a célula trazia `data-lucide="#icon#"` e o mesmo valor ia para o atributo e para a
		//   classe. Num banco onde o módulo só tem o vocabulário Fomantic legado — todo módulo de
		//   projeto derivado, hoje —, isso entregava `data-lucide="comments outline"` ao Lucide, que
		//   respondia com `icon name was not found` no console a cada item do menu. Um nome de ícone
		//   é endereço DENTRO de um catálogo: o do Lucide é kebab-case de um segmento só, e nada mais
		//   é endereçável ali.
		//
		//   Marcador vazio não resolveria: `createIcons()` seleciona `[data-lucide]` por PRESENÇA do
		//   atributo, e `data-lucide=""` gera exatamente o mesmo warning. O atributo precisa não ser
		//   emitido — por isso o template usa `#icon-lucide#`, que recebe o atributo inteiro ou nada.
		$cel_icon = modelo_var_troca($cel_icon,"#icon-lucide#",gestor_pagina_menu_icone_lucide_atributo($iconeFinal));
		
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
			continue;
		}
		
		// ===== Caso seja dashboard incluir depois primeiro, senão colocar em ordem alfabética
		
		if($modulo['id'] == 'dashboard'){
			$dashboard = $cel_aux;
		} else {
			// ===== Incluir o item no módulo grupo.
			
			if(!isset($grupos[$modulo['modulo_grupo_id']])){
				$achouGrupo = false;
				$nomeGrupo = '';
				if($modulos_grupos)
				foreach($modulos_grupos as $modulo_grupo){
					if($modulo_grupo['id'] == $modulo['modulo_grupo_id']){
						$achouGrupo = true;
						$nomeGrupo = (existe($modulo_grupo['menu_label']) ? $modulo_grupo['menu_label'] : $modulo_grupo['nome']);
						break;
					}
				}
                
				if($achouGrupo){
					$grupos[$modulo['modulo_grupo_id']] = $cel['categoria'];
                    
					$grupos[$modulo['modulo_grupo_id']] = modelo_var_troca($grupos[$modulo['modulo_grupo_id']],'#categoria-nome#',$nomeGrupo);
				} else {
					continue;
				}
			}
            
			$grupos[$modulo['modulo_grupo_id']] = modelo_var_in($grupos[$modulo['modulo_grupo_id']],'<!-- itemMenu -->',$cel_aux);
		}
	}
	
	// ===== Montar o conteiner do menu.
	
	$menuConteiner = $cel['conteiner'];
	
	// ===== Incluir dashboard no conteiner

	// req-086: com `hide_dashboard` a célula simples inteira é pulada. Preencher com string vazia
	// não bastaria: o `itemContCel` continuaria no menu como um bloco vazio, com a borda e o
	// espaçamento do grupo desenhados em volta de nada.
	if(!$menuOcultarDashboard){
		$cel_simples = $cel['simples'];
		$cel_simples = modelo_var_troca($cel_simples,"<!-- itemMenu -->",$dashboard);

		$cel_conteiner = $cel['itemContCel'];
		$cel_conteiner = modelo_var_troca($cel_conteiner,"#itemCont#",$cel_simples);

		$menuConteiner = modelo_var_in($menuConteiner,'<!-- itemContCel -->',$cel_conteiner);
	}

	// ===== Incluir grupos no conteiner (em ordem de prioridade definida na consulta).
	
	if($modulos_grupos)
	foreach($modulos_grupos as $modulo_grupo){
		if(isset($grupos[$modulo_grupo['id']])){
			$cel_conteiner = $cel['itemContCel'];
			$cel_conteiner = modelo_var_troca($cel_conteiner,"#itemCont#",$grupos[$modulo_grupo['id']]);
            
			$menuConteiner = modelo_var_in($menuConteiner,'<!-- itemContCel -->',$cel_conteiner);
		}
	}
	
	// ===== Incluir sair no conteiner

	// req-086: `hide_logout` (ou o apelido `hide_signout`) suprime o item de saída do rodapé. Um
	// layout que já oferece o logout em outro lugar — menu do avatar, barra superior — passa a não
	// repetir a mesma ação em dois pontos da mesma tela.
	if(!$menuOcultarLogout){
		$cel_nome = 'item';
		$cel_aux = $cel[$cel_nome];

		$cel_aux = modelo_var_troca($cel_aux,"#nome#",gestor_variaveis(Array('id' => 'logout-label','modulo' => 'dashboard')));
		$cel_aux = modelo_var_troca($cel_aux,"#class#",'');

		$cel_nome_icon = 'icon';
		$cel_icon = $cel[$cel_nome_icon];

		// req-086: o item de saída não vem da tabela `modulos`, então o vocabulário do ícone é
		// escolhido aqui pelo mesmo critério dos demais itens.
		$cel_icon = modelo_var_troca($cel_icon,"#icon#",($menuTailwind ? 'log-out' : 'sign out alternate'));

		$cel_aux = modelo_var_troca($cel_aux,"<!-- icon -->",$cel_icon);

		$cel_aux = modelo_var_troca_tudo($cel_aux,"#link#",$_GESTOR['url-raiz'].'signout/');

		$cel_simples = $cel['simples'];
		$cel_simples = modelo_var_troca($cel_simples,"<!-- itemMenu -->",$cel_aux);

		$cel_conteiner = $cel['itemContCel'];
		$cel_conteiner = modelo_var_troca($cel_conteiner,"#itemCont#",$cel_simples);

		$menuConteiner = modelo_var_in($menuConteiner,'<!-- itemContCel -->',$cel_conteiner);
	}

	// ===== Remover celulas inúteis

	$menuConteiner = modelo_var_troca($menuConteiner,'<!-- itemContCel -->','');
	$menuConteiner = modelo_var_troca($menuConteiner,'<!-- itemMenu -->','');

	// ===== JavaScript do painel administrativo (BATCH-103): filtro do menu.
	//
	// A tag acompanha o HTML do menu em vez de entrar na fila de assets (`gestor_pagina_css_incluir`
	// / `gestor_pagina_javascript_incluir`): esta função é chamada por `gestor_pagina_variaveis()`,
	// que roda DEPOIS de `gestor_pagina_extra_head_e_javascript()` — quando o `<!-- pagina#js -->` já
	// foi resolvido, qualquer item enfileirado ali fica órfão e o script nunca chega à página.
	// Como `admin.js` só interessa a quem vê o menu, carregá-lo junto dele mantém o custo restrito
	// ao painel e dispensa um gate extra no pipeline.
	//
	// req-118: em Tailwind puro o runtime é outro (`admin-tailwind.js`, vanilla) — o legado depende
	// de jQuery e das classes `.menuComputerCont`/`.paginaCont`, que a marcação nova não tem.

	$menuAsset = ($menuTailwind ? 'global/admin-tailwind.js' : 'global/admin.js');

	$menuConteiner .= "\n".'<script src="'.$_GESTOR['url-raiz'].$menuAsset.'?v='.gestor_asset_version('global').'"></script>';

	// ===== Retornar o conteiner.

	return $menuConteiner;
}

function gestor_pagina_variaveis_modulos($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// modulosExtra - Conjunto - Opcional - Conjunto de móulos extras para ler variáveis globais quando necessário.
	
	// ===== 
	
	if(isset($modulosExtra)){
		foreach($modulosExtra as $modulo){
			$_GESTOR['paginas-variaveis'][$modulo] = true;
		}
	}
	
}

function gestor_pagina_layout($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// layout - String - Obrigatório - Layout principal onde a página será incluída.
	
	// ===== 
	
	$open = $_GESTOR['variavel-global']['open'];
	$close = $_GESTOR['variavel-global']['close'];
	
	// ===== Variáveis do layout
	
	$layout = modelo_var_troca($layout,'<!-- pagina#titulo -->',($_GESTOR['pagina#titulo'] ? '<title>'.(isset($_GESTOR['pagina#titulo-extra']) ? $_GESTOR['pagina#titulo-extra'] : '').$_GESTOR['pagina#titulo'].'</title>' : ''));
	
	// ===== Página fundir layout + página

	$conteudo = $_GESTOR['pagina'];

	// Para editores logados (Dashboard Site Toolbar / Meta 3), envolver o conteúdo da página
	// num wrapper identificável — alvo da edição visual in-place (swap p/ render com caixas).
	if(gestor_dashboard_toolbar_ativo()){
		$conteudo = '<div id="c2f-page-content" data-c2f-content>'.$conteudo.'</div>';
	}

	$_GESTOR['pagina'] = modelo_var_troca($layout,$open.'pagina#corpo'.$close,$conteudo);

	// Para edição de LAYOUT in-place (BATCH-075/Meta 3 ponto 4), envolver o CORPO do layout num
	// #c2f-layout-root (que contém o #c2f-page-content). O iframe da toolbar é injetado depois,
	// após <body>, FORA deste wrapper. Só o body do layout é editável (o <head> não).
	if(gestor_dashboard_toolbar_ativo()){
		$layoutId = isset($_GESTOR['layout#id']) ? (string)$_GESTOR['layout#id'] : '';
		$_GESTOR['pagina'] = preg_replace_callback('/(<body\b[^>]*>)([\s\S]*?)(<\/body>)/i', function($m) use ($layoutId){
			return $m[1].'<div id="c2f-layout-root" data-c2f-layout data-layout-id="'.htmlspecialchars($layoutId, ENT_QUOTES, 'UTF-8').'">'.$m[2].'</div>'.$m[3];
		}, $_GESTOR['pagina'], 1);
	}
}

function gestor_pagina_widgets(){
	global $_GESTOR;

	// ===== Variáveis globais de página

	$open = $_GESTOR['variavel-global']['open'];
	$close = $_GESTOR['variavel-global']['close'];

	// ===== Widgets Envelopados (Wrappers) - BATCH-008
	// Sintaxe:
	//   <!-- widgets#MODULO_ID->FUNCAO(JSON_PARAMS) < -->
	//     ...HTML mockup estático (template de preview visual para designers)...
	//   <!-- widgets#MODULO_ID->FUNCAO(JSON_PARAMS) > -->
	// O conteúdo interno é capturado e repassado ao callback do widget pela
	// chave 'html' do array de parâmetros, junto com o 'id'.

	// No modo de edição do Live Editor (Dashboard Site Toolbar) PRESERVAMOS os comentários
	// `<!-- widgets#SIG < --> … <!-- widgets#SIG > -->` ao redor do render (trocamos apenas o
	// conteúdo interno = mockup → widget renderizado). Assim o DOM vivo carrega a fronteira EXATA
	// de cada widget, e o live editor delimita/marca cada um sem heurística de alinhamento
	// (resolve widgets duplicados/consecutivos e o mapeamento no contêiner pai). Para o visitante
	// normal, os marcadores continuam sendo removidos (comportamento original).
	$editMode = function_exists('gestor_dashboard_toolbar_ativo') ? gestor_dashboard_toolbar_ativo() : false;

	$wrapperPattern = '/<!--\s*widgets#(.+?)\s*<\s*-->([\s\S]*?)<!--\s*widgets#\s*\1\s*>\s*-->/i';

	if(preg_match_all($wrapperPattern, $_GESTOR['pagina'], $matchesWrapper, PREG_SET_ORDER)){
		// ===== Incluir a biblioteca dos widgets e disparar a função de iniciação dos mesmos.
		gestor_incluir_biblioteca('widgets');

		foreach($matchesWrapper as $wrapperMatch){
			$widgetId    = $wrapperMatch[1];
			$widgetHtml  = $wrapperMatch[2];
			$widgetBlock = $wrapperMatch[0];

			$widget = widgets_get(Array(
				'id'   => $widgetId,
				'html' => $widgetHtml,
			));

			if(existe($widget)){
				$substituicao = $editMode
					? ('<!-- widgets#'.$widgetId.' < -->'.$widget.'<!-- widgets#'.$widgetId.' > -->')
					: $widget;
				$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$widgetBlock,$substituicao);
			}
		}
	}

	// ===== Compatibilidade retrógrada: marcador inline @[[widgets#...]]@
	// O padrão procurado é algo como
	//   @[[widgets#MODULO_ID->FUNCAO(JSON_PARAMS)]]@
	// ou apenas @[[widgets#meu-widget]]@ para compatibilidade.
	// Tudo que estiver entre "widgets#" e o fechamento será passado
	// diretamente para widgets_get() que conhece o formato.

	$pattern = "/".preg_quote($open)."widgets#(.+?)".preg_quote($close)."/i";
	preg_match_all($pattern, $_GESTOR['pagina'], $matchesWidgets);

	if($matchesWidgets){
		// ===== Incluir a biblioteca dos widgets e disparar a função de iniciação dos mesmos.
		gestor_incluir_biblioteca('widgets');

		// ===== Varrer todos os matchs e trocar os marcadores por seus widgets.
		foreach($matchesWidgets[1] as $match){
			// $match contém a string completa depois de "widgets#" –
			// pode ser "modulo->func({...})" ou um nome simples.
			$widget = widgets_get(Array(
				'id' => $match,
			));

			if(existe($widget)){
				$substituicao = $editMode
					? ('<!-- widgets#'.$match.' < -->'.$widget.'<!-- widgets#'.$match.' > -->')
					: $widget;
				$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open."widgets#".$match.$close,$substituicao);
			}
		}
	}
}

/**
 * req-096 (BATCH-096): inclui os assets do motor PDF.js somente quando a página final contém o
 * contêiner `.conn2flow-pdfjs` (gerado pelo Editor HTML). Deve rodar DEPOIS de gestor_pagina_widgets()
 * — o contêiner pode vir do HTML de um widget — e ANTES de gestor_pagina_extra_head_e_javascript(),
 * que serializa as inclusões na página.
 */
function gestor_pagina_pdf_viewer(){
	global $_GESTOR;

	if(!isset($_GESTOR['pagina'])) return;
	if(!function_exists('gestor_pdf_viewer_detectar')) return;
	if(!gestor_pdf_viewer_detectar($_GESTOR['pagina'])) return;

	$assets = gestor_pdf_viewer_assets($_GESTOR['url-raiz'],gestor_asset_version('interface'));

	foreach($assets as $asset){
		gestor_pagina_javascript_incluir($asset);
	}
}

/**
 * CSS de conteúdo do editor Quill (BATCH-144 / req-141).
 *
 * Mesmo padrão do PDF.js acima: o asset entra SÓ nas páginas que realmente publicam conteúdo
 * formatado no Quill, nunca no site inteiro. A detecção roda sobre o HTML final (depois dos
 * widgets), porque conteúdo Quill também chega por widget de publicação.
 */
function gestor_pagina_quill(){
	global $_GESTOR;

	if(!isset($_GESTOR['pagina'])) return;

	// A biblioteca do editor de texto é a autoridade sobre o Quill: detecção, versão e assets. Ela é
	// carregada sob demanda porque a esmagadora maioria das páginas não publica conteúdo do editor.
	if(!function_exists('editor_texto_conteudo_detectar') && !empty($_GESTOR['bibliotecas-path'])){
		require_once($_GESTOR['bibliotecas-path'].'editor-texto.php');
	}

	if(!function_exists('editor_texto_conteudo_detectar')) return;
	if(!editor_texto_conteudo_detectar($_GESTOR['pagina'])) return;

	foreach(editor_texto_assets_publicacao($_GESTOR['url-raiz'],gestor_asset_version('interface')) as $asset){
		$_GESTOR['css'][] = $asset;
	}
}

function gestor_pagina_widgets_ajax(){
	global $_GESTOR;

	// ===== Se receber uma requisição AJAX para widgets, disparar os controladores AJAX dos widgets e retornar o resultado.
	if(!isset($_GESTOR['ajaxWidgets'])) return;

	$widgetsAjaxList = array_filter(explode('<#;>', $_GESTOR['ajaxWidgets']));
	
	if($widgetsAjaxList){
		// ===== Incluir a biblioteca dos widgets e disparar a função de iniciação dos mesmos.
		gestor_incluir_biblioteca('widgets');
		
		// ===== Varrer todos os matchs e trocar os marcadores por seus widgets.
		foreach($widgetsAjaxList as $match){
			// inclui o widget e dispara a função correspondente, porém se houver retorno é porque teve erro.
			$widget = widgets_get(Array(
				'id' => $match,
			));
			
			if(!empty($widget)){
				gestor_roteador_erro(Array(
					'codigo' => 500,
					'ajax' => $_GESTOR['ajax'],
					'mensagem' => 'Widget AJAX error: ['.$match.'] - Return: '.$widget,
				));
			}
		}
	}
}

function gestor_pagina_variaveis(){
	global $_GESTOR;

	// ===== Variáveis globais de página
	
	$open = $_GESTOR['variavel-global']['open'];
	$close = $_GESTOR['variavel-global']['close'];
	
	// ===== Página variáveis operações
	
	$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
	$caminho = rtrim($caminho,'/').'/';
	
	// ===== Página variáveis trocar
	
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#menu'.$close,gestor_pagina_menu());
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#url-raiz'.$close,$_GESTOR['url-raiz']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#url-full-http'.$close,$_GESTOR['url-full-http']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#url-caminho'.$close,$caminho);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#titulo'.$close,$_GESTOR['pagina#titulo']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#contato-url'.$close,$_GESTOR['pagina#contato-url']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'gestor#versao'.$close,$_GESTOR['versao']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'gestor#asset-version'.$close,gestor_asset_version());

	// ===== Projeto variáveis trocar

	if(isset($_GESTOR['project-version'])) $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'project#version'.$close,$_GESTOR['project-version']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'project#asset-version'.$close,$_GESTOR['project-asset-version'] ?? gestor_asset_version());
	
	// ===== Dados do usuário
	
	$usuario = gestor_usuario();
	
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'usuario#nome'.$close,$usuario['nome']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'usuario#slug'.$close,$usuario['id']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'usuario#perfil-nome'.$close,$usuario['perfil_nome']);
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'usuario#perfil-slug'.$close,$usuario['perfil_slug']);
	
	if(isset($_GESTOR['modulo-id'])) $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#modulo-id'.$close,$_GESTOR['modulo-id']);
	if(isset($_GESTOR['modulo-registro-id'])) $_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.'pagina#registro-id'.$close,$_GESTOR['modulo-registro-id']);
	

	$variaveisEncontradas = Array();

	// ===== Variáveis globais.

	$pattern = "/".preg_quote($open)."(.+?)".preg_quote($close)."/i";
	preg_match_all($pattern, $_GESTOR['pagina'], $matches);
	
	if($matches)
	foreach($matches[1] as $match){
		// ===== Pegar o valor da variável
		$valor = gestor_variaveis_globais(Array('id' => $match));

		if(isset($valor)){
			$variaveisEncontradas[] = $match;
			$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.$match.$close,existe($valor) ? $valor : '');
		}
	}

	// ===== Variáveis do módulo atual.

	if(isset($_GESTOR['modulo-id'])){
		$pattern = "/".preg_quote($open)."(.+?)".preg_quote($close)."/i";
		preg_match_all($pattern, $_GESTOR['pagina'], $matches);
		
		if($matches)
		foreach($matches[1] as $match){
			if(in_array($match,$variaveisEncontradas)) continue;
			$variaveisEncontradas[] = $match;
			// ===== Pegar o valor da variável
			$valor = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => $match));
			
			if(existe($valor)){
				$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.$match.$close,$valor);
			}
		}
	}
	
	// ===== Módulos extras que devem ser lidos e colocados as variáveis nas páginas.
	
	if(isset($_GESTOR['paginas-variaveis'])){
		$modulosExtra = $_GESTOR['paginas-variaveis'];
		
		foreach($modulosExtra as $modulo => $valor){
			$pattern = "/".preg_quote($open)."(.+?)".preg_quote($close)."/i";
			preg_match_all($pattern, $_GESTOR['pagina'], $matches);
			
			if($matches)
			foreach($matches[1] as $match){
				if(in_array($match,$variaveisEncontradas)) continue;
				$variaveisEncontradas[] = $match;
				// ===== Pegar o valor da variável
				$valor = gestor_variaveis(Array('modulo' => $modulo,'id' => $match));
				
				if(existe($valor)){
					$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],$open.$match.$close,$valor);
				}
			}
		}
	}
}

function gestor_pagina_css(){
	global $_GESTOR;

	// ===== Inclusão de bibliotecas CSS Fomantic-UI / Tailwind CSS
	//       A decisão sai de layout + página juntos (req-118): gestor_framework_css_resolver().

	$framework = gestor_framework_css_atual();

	$fomantic_ui_included = $framework['fomantic'];
	$tailwindcss_included = $framework['tailwind'];

	$css_global = '';

	$css_padrao = Array();

	// req-143 / BATCH-146: mesma resolução do JS — registro de assets externos, disco primeiro.
	if($fomantic_ui_included){
		if(!function_exists('assets_externos_tags') && !empty($_GESTOR['bibliotecas-path'])){
			require_once($_GESTOR['bibliotecas-path'].'assets-externos.php');
		}

		$tagsFomanticCss = assets_externos_tags(
			'fomantic-ui',
			($_GESTOR['assets-path'] ?? '').'vendor'.DIRECTORY_SEPARATOR,
			($_GESTOR['url-raiz'] ?? '/').'vendor/'
		);

		foreach($tagsFomanticCss['css'] as $tag){ $css_padrao[] = $tag; }
	}

	if(!isset($_GESTOR['css-precompiled'])) $_GESTOR['css-precompiled'] = Array();
	if(!isset($_GESTOR['css-compiled'])) $_GESTOR['css-compiled'] = Array();
	if(!isset($_GESTOR['css'])) $_GESTOR['css'] = Array();
	if(!isset($_GESTOR['css-fim'])) $_GESTOR['css-fim'] = Array();

	// CSS do projeto regras de negócio, se existir.

	if(!isset($_GESTOR['project-css'])) $_GESTOR['project-css'] = Array();

	if(isset($_GESTOR['layout#id']) && isset($_GESTOR['project-css-layouts-remove']) && in_array($_GESTOR['layout#id'], $_GESTOR['project-css-layouts-remove'])){
		// Não incluir CSS do projeto, pois o layout atual está na lista de layouts para não incluir o CSS do projeto.
		$_GESTOR['project-css'] = Array();
	} else if(isset($_GESTOR['layout#id']) && isset($_GESTOR['project-css-layouts-include']) && in_array($_GESTOR['layout#id'], $_GESTOR['project-css-layouts-include'])){
		// Incluir somente os CSS's do projeto relacionados a este layout, pois o layout atual está na lista de layouts para incluir somente os CSS's do projeto relacionados a este layout.
		$_GESTOR['project-css'] = $_GESTOR['project-css-layouts-include'][$_GESTOR['layout#id']];
	}

	// Ordem: CSS padrão/projeto, pré-compilado offline, compilado online, autoral e final.
	$css_precompiled_ordenado = gestor_css_precompiled_ordenar($_GESTOR['css-precompiled']);
	$csss = array_merge($css_padrao,$_GESTOR['project-css'],$css_precompiled_ordenado,$_GESTOR['css-compiled'],$_GESTOR['css'],$_GESTOR['css-fim']);

	if($csss)
	foreach($csss as $css){
		if(existe($css_global)){
			$css_global .= "	" . $css . "\n";
		} else {
			$css_global .= $css . "\n";
		}
	}
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<!-- pagina#css -->',$css_global);
}

function gestor_pagina_css_incluir($css = false){
	global $_GESTOR;
	
	if(!$css){
		$_GESTOR['css-fim'][] = $css = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/css.css?v='.gestor_modulo_asset_version($_GESTOR['modulo-id']).'">';
		
		// ===== Verifica se já foi adicionado este css, se sim, remover o último que foi adicionado.
		if(!isset($_GESTOR['css-fim-adicionados'])){
			$_GESTOR['css-fim-adicionados'] = Array();
		} else {
			if(in_array($css,$_GESTOR['css-fim-adicionados'])){
				array_pop($_GESTOR['css-fim']);
				return;
			}
		}
	
		$_GESTOR['css-fim-adicionados'][] = $css;
	} else {
		$_GESTOR['javascript-fim'][] = $css;
		
		// ===== Verifica se já foi adicionado este css, se sim, remover o último que foi adicionado.
		if(!isset($_GESTOR['javascript-fim-adicionados'])){
			$_GESTOR['javascript-fim-adicionados'] = Array();
		} else {
			if(in_array($css,$_GESTOR['javascript-fim-adicionados'])){
				array_pop($_GESTOR['javascript-fim']);
				return;
			}
		}
	
		$_GESTOR['javascript-fim-adicionados'][] = $css;
	}
}

function gestor_pagina_extra_head_e_javascript(){
	global $_GESTOR;
	global $_CONFIG;

	// ===== Inclusão de bibliotecas Javascript Fomantic-UI (mesma resolução do CSS: layout + página).

	$fomantic_ui_included = gestor_framework_css_atual()['fomantic'];

	// ===== Inclusão de bibliotecas javascript
	
	$js_global_includes = '';

	// req-143 / BATCH-146: jQuery e Fomantic-UI vêm do registro de assets externos, servidos do
	// disco quando `assets/vendor/` existe e do CDN enquanto não existir.
	//
	// O jQuery daqui era 3.5.1 vindo de `ajax.googleapis.com` e valia para TODA página do gestor,
	// enquanto o editor HTML injetava 3.7.1 de jsdelivr e a toolbar 3.7.1 de cdnjs. Três versões,
	// três hosts: numa tela que carregasse mais de uma, a última vencia e plugins registrados na
	// anterior sumiam — falha intermitente e difícil de rastrear.
	if(!function_exists('assets_externos_tags') && !empty($_GESTOR['bibliotecas-path'])){
		require_once($_GESTOR['bibliotecas-path'].'assets-externos.php');
	}

	$vendorFisico = ($_GESTOR['assets-path'] ?? '').'vendor'.DIRECTORY_SEPARATOR;
	$vendorPublico = ($_GESTOR['url-raiz'] ?? '/').'vendor/';

	$tagsJquery = assets_externos_tags('jquery', $vendorFisico, $vendorPublico);
	foreach($tagsJquery['js'] as $tag){ $js_padrao[] = $tag; }

	if($fomantic_ui_included){
		$tagsFomantic = assets_externos_tags('fomantic-ui', $vendorFisico, $vendorPublico);
		foreach($tagsFomantic['js'] as $tag){ $js_padrao[] = $tag; }
	}

	$js_padrao[] = '<script src="'.$_GESTOR['url-raiz'].'global/global.js?v='.gestor_asset_version('global').'"></script>'; // Global JS
	
	if(!isset($_GESTOR['html-extra-head'])) $_GESTOR['html-extra-head'] = Array();
	if(!isset($_GESTOR['javascript'])) $_GESTOR['javascript'] = Array();
	if(!isset($_GESTOR['javascript-fim'])) $_GESTOR['javascript-fim'] = Array();

	// Javascript do projeto regras de negócio, se existir.

	if(!isset($_GESTOR['project-javascript'])) $_GESTOR['project-javascript'] = Array();

	// ===== req-109: metatags OpenGraph do <head>.
	//       Só injeta quando a página/layout não trouxe OpenGraph próprio no html_extra_head — duas
	//       tags og:title fazem o scraper escolher arbitrariamente qual usar.

	if(empty($_GESTOR['paginaIframe']) && !gestor_open_graph_existe($_GESTOR['html-extra-head'])){
		foreach(gestor_open_graph_tags(gestor_open_graph_dados()) as $tag){
			$_GESTOR['html-extra-head'][] = $tag."\n";
		}
	}

	// ===== req-112: meta tags clássicas de SEO (`description` e `keywords`), com o mesmo cuidado —
	//       página/layout que já traga a sua não recebe a do core.

	if(empty($_GESTOR['paginaIframe']) && !gestor_meta_seo_existe($_GESTOR['html-extra-head'])){
		foreach(gestor_meta_seo_tags(gestor_meta_seo_dados()) as $tag){
			$_GESTOR['html-extra-head'][] = $tag."\n";
		}
	}

	// ===== req-111 (CR-001): rota de sistema não é conteúdo — sai do índice.
	//       Enquanto o laço existiu, `cookies-is-mandatory/` foi entregue a buscador e a coletor no
	//       lugar da página pedida; ela é `tipo=page`/`sem_permissao`, então nada impedia a indexação.
	//       Fechado o laço, isto garante que o que já foi indexado saia — e que uma reincidência não
	//       volte a poluir relatório e resultado de busca.

	if(gestor_pagina_rota_sistema((string)($_GESTOR['caminho-total'] ?? ''))){
		$_GESTOR['html-extra-head'][] = '<meta name="robots" content="noindex, nofollow">'."\n";
		if(!headers_sent()) header('X-Robots-Tag: noindex, nofollow');
	}

	// ===== req-111 (CR-001): o bloqueio de analytics em páginas de sistema, introduzido pelo
	//       req-109 §3/§4, foi REMOVIDO. O diagnóstico original estava invertido: a
	//       `cookies-is-mandatory/` não aparecia nos relatórios porque o analytics rodava nela, e
	//       sim porque todo cliente sem cookie era EMPURRADO para lá pelo laço de verificação.
	//       Fechado o laço (ver gestor_cookie_verificacao e a rota `_gestor-cookie-verify`), o
	//       sintoma desaparece na origem e os coletores voltam a receber tudo, sem exceção.

	// Token do painel para formulários e clientes AJAX autenticados por cookie.
	if(isset($_COOKIE[$_CONFIG['cookie-authname']])){
		gestor_incluir_biblioteca('seguranca');
		$csrfToken = gestor_csrf_token();
		$_GESTOR['html-extra-head'][] = '<meta name="csrf-token" content="'.htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8').'">';
		if(!isset($_GESTOR['javascript-vars'])) $_GESTOR['javascript-vars'] = Array();
		$_GESTOR['javascript-vars']['csrfToken'] = $csrfToken;
	}
	
	if(isset($_GESTOR['layout#id']) && isset($_GESTOR['project-javascript-layouts-remove']) && in_array($_GESTOR['layout#id'], $_GESTOR['project-javascript-layouts-remove'])){
		// Não incluir javascript do projeto, pois o layout atual está na lista de layouts para não incluir o javascript do projeto.
		$_GESTOR['project-javascript'] = Array();
	} else if(isset($_GESTOR['layout#id']) && isset($_GESTOR['project-javascript-layouts-include']) && in_array($_GESTOR['layout#id'], $_GESTOR['project-javascript-layouts-include'])){
		// Incluir somente os javascript's do projeto relacionados a este layout, pois o layout atual está na lista de layouts para incluir somente os javascript's do projeto relacionados a este layout.
		$_GESTOR['project-javascript'] = $_GESTOR['project-javascript-layouts-include'][$_GESTOR['layout#id']];
	}
	
	// Incluir os javascript's seguindo a ordem: javascript padrão, javascript extra head, javascript normal, javascript fim e javascript do projeto.
	$jss = array_merge($js_padrao,$_GESTOR['html-extra-head'],$_GESTOR['javascript'],$_GESTOR['javascript-fim'],$_GESTOR['project-javascript']);
	
	if($jss)
	foreach($jss as $js){
		$js_global_includes .= "	" . $js . "\n";
	}
	
	// ===== Inclusão de variáveis javascript
	
	$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
	$caminho = rtrim($caminho,'/').'/';
	
	$variaveis_js = Array(
		'versao' => $_GESTOR['versao'],
		'assetVersion' => gestor_asset_version(),
		'projectAssetVersion' => $_GESTOR['project-asset-version'] ?? gestor_asset_version(),
		'raiz' => $_GESTOR['url-raiz'],
		'raizSemLang' => $_GESTOR['url-raiz-sem-lang'],
		'language' => $_GESTOR['linguagem-codigo'],
		'pageLanguages' => $_GESTOR['page-languages'],
		'languageSystem' => $_GESTOR['linguagem-padrao'],
		'languageCookie' => $_CONFIG['cookie-language'],
		'moduloId' => (isset($_GESTOR['modulo-id']) ? $_GESTOR['modulo-id'] : false ),
		'moduloVersao' => (isset($_GESTOR['modulo-versao']) ? $_GESTOR['modulo-versao'] : false ),
		'moduloAssetVersion' => (isset($_GESTOR['modulo-id']) ? gestor_modulo_asset_version($_GESTOR['modulo-id']) : false ),
		'moduloOpcao' => (isset($_GESTOR['opcao']) ? $_GESTOR['opcao'] : false ),
		'widgetsToAjax' => (isset($_GESTOR['widgetsToAjax']) ? $_GESTOR['widgetsToAjax'] : null ),
		'moduloCaminho' => $caminho,
	);
	
	if($_GESTOR['paginaIframe']) $variaveis_js['paginaIframe'] = true;
	
	$js_global_vars = '<script>
		var gestor = '.json_encode((isset($_GESTOR['javascript-vars']) ? array_merge($variaveis_js, $_GESTOR['javascript-vars']):$variaveis_js), JSON_UNESCAPED_UNICODE).';
	</script>'."\n";
	
	// ===== Inclusão na página
	
	$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<!-- pagina#js -->',$js_global_vars.$js_global_includes);
}

function gestor_pagina_javascript_incluir($js = false,$id = false, $retornar = false){
	global $_GESTOR;

	$js_script = '';
	$js_id = '';
	
	if(!$js){
		if(!isset($_GESTOR['modulo-id'])){
			return;
		}
		if(isset($_GESTOR['modulo#'.$_GESTOR['modulo-id']]['plugin'])){
			$js_script = '<script src="'.$_GESTOR['url-raiz'].$_GESTOR['modulo#'.$_GESTOR['modulo-id']]['plugin'].'/'.$_GESTOR['modulo-id'].'/js.js?v='.gestor_modulo_asset_version($_GESTOR['modulo-id']).'"></script>';
			$js_id = $_GESTOR['modulo#'.$_GESTOR['modulo-id']]['plugin'].'/'.$_GESTOR['modulo-id'].'/js.js';
		} else {
			$js_script = '<script src="'.$_GESTOR['url-raiz'].$_GESTOR['modulo-id'].'/js.js?v='.gestor_modulo_asset_version($_GESTOR['modulo-id']).'"></script>';
			$js_id = $_GESTOR['modulo-id'].'/js.js';
		}
	} elseif(gettype($js) == 'array') {
		$tipo = (isset($js['tipo']) ? $js['tipo'] : '');
		$modulo_id = (isset($js['modulo_id']) ? $js['modulo_id'] : 'undefined');
		$versao = (isset($js['asset_version']) ? $js['asset_version'] : (isset($_GESTOR['modulo#'.$modulo_id]) ? gestor_modulo_asset_version($modulo_id) : (isset($js['versao']) ? $js['versao'] : gestor_asset_version())));
		
		$js_script = '<script src="'.$_GESTOR['url-raiz'].$modulo_id.'/'.$tipo.'.js?v='.$versao.'"></script>';
		$js_id = $modulo_id.'/'.$tipo.'.js';
	} else {
		switch($js){
			case 'biblioteca':
				if (is_array($id)) {
					$js = '<script src="'.$_GESTOR['url-raiz'].'interface/'.(isset($id['caminho']) ? $id['caminho'] : '').'.js?v='.gestor_asset_version('interface', $_GESTOR['biblioteca-'.(isset($id['biblioteca']) ? $id['biblioteca'] : '')]['versao'] ?? null).'"></script>';
                } else {
					$js = '<script src="'.$_GESTOR['url-raiz'].'interface/'.$id.'.js?v='.gestor_asset_version('interface', $_GESTOR['biblioteca-'.$id]['versao'] ?? null).'"></script>';
                }
			break;
		}
		
		$js_script = $js;
		$js_id = $js;
	}

	// ===== Se não existir o javascript, retornar.
	if(!existe($js_script)){
		return;
	}

	// ===== Se for para retornar o javascript, retornar.
	if($retornar){
		return $js_script;
	} else {
		$_GESTOR['javascript-fim'][] = $js_script;
	}

	// ===== Verifica se já foi adicionado este javascript, se sim, remover o último que foi adicionado.
	if(!isset($_GESTOR['javascript-fim-adicionados'])){
		$_GESTOR['javascript-fim-adicionados'] = Array();
	} else {
		if(in_array($js_id,$_GESTOR['javascript-fim-adicionados'])){
			array_pop($_GESTOR['javascript-fim']);
			return;
		}
	}

	$_GESTOR['javascript-fim-adicionados'][] = $js_id;
}

function gestor_pagina_ultimas_operacoes(){
	global $_GESTOR;
	
	$_GESTOR['pagina'] = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $_GESTOR['pagina']);

	// req-132: ultima etapa antes de a pagina ir para o navegador. Depois daqui nada mais e
	// injetado, entao remover comentario e indentacao nao pode quebrar marcador nenhum do sistema.
	if(gestor_pagina_higienizar_ativo()){
		$_GESTOR['pagina'] = gestor_html_higienizar($_GESTOR['pagina']);
	}
}

/**
 * Retorna true quando o Dashboard Site Toolbar deve estar ativo na requisição atual:
 * editor logado com permissão de editar páginas, numa página pública (não o painel
 * administrativo, não um iframe e não o próprio toolbar). O resultado é cacheado em
 * $_GESTOR para que o wrapper de conteúdo (gestor_pagina_layout) e a injeção do iframe
 * (gestor_dashboard_toolbar) usem exatamente o mesmo gate.
 */
function gestor_dashboard_toolbar_ativo(){
	global $_GESTOR;

	if(isset($_GESTOR['dashboard-toolbar-ativo'])) return $_GESTOR['dashboard-toolbar-ativo'];

	$ativo = true;

	if(isset($_GESTOR['opcao']) && isset($_GESTOR['modulo-id']) && $_GESTOR['modulo-id'] == 'dashboard' && $_GESTOR['opcao'] == 'dashboard-site-toolbar'){
		// Acessando o próprio toolbar → não ativar (evita loop).
		$ativo = false;
	} else if(!gestor_usuario_perfil()){
		$ativo = false;
	} else if(!gestor_permissao_token() || !gestor_permissao_modulo(false)){
		$ativo = false;
	} else if(!(isset($_GESTOR['usuario-id']) && (int)$_GESTOR['usuario-id'] > 0)){
		$ativo = false;
	} else if(isset($_GESTOR['layout#id']) && $_GESTOR['layout#id'] == 'layout-administrativo-do-gestor'){
		$ativo = false;
	} else if(isset($_GESTOR['paginaIframe']) && $_GESTOR['paginaIframe']){
		$ativo = false;
	} else if(isset($_REQUEST['c2f-device-preview'])){
		// Preview de dispositivo do Live Editor: o iframe carrega a PRÓPRIA página (layout + CSS + JS
		// reais do site, para media queries e interações como o menu hambúrguer funcionarem) mas SEM
		// a toolbar/editor — evita recursão do iframe da barra (BATCH-085 §preview-device).
		$ativo = false;
	} else if(!gestor_acesso('editar','admin-paginas')){
		$ativo = false;
	}

	$_GESTOR['dashboard-toolbar-ativo'] = $ativo;
	return $ativo;
}

function gestor_dashboard_toolbar($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

	// ===== Parâmetros

	// caminho - String - Opcional - caminho da página pública hospedeira (resolve id/publisher_id).

	// ===== Gate único (mesmo do wrapper de conteúdo): editor logado em página pública.

	if(!gestor_dashboard_toolbar_ativo()) return;

	// ===== Precisa de um corpo de página (<body>) para injetar.

	if(!isset($_GESTOR['pagina']) || stripos($_GESTOR['pagina'],'<body') === false) return;

	// ===== Resolver a página hospedeira (id + publisher_id) pelo caminho.

	$page_id = '';
	$publisher_id = '';

	if(isset($caminho) && $caminho !== ''){
		$pagina_atual = banco_select(Array(
			'unico' => true,
			'tabela' => 'paginas',
			'campos' => Array('id','publisher_id'),
			'extra' =>
				"WHERE caminho='".banco_escape_field($caminho)."'"
				." AND language='".$_GESTOR['linguagem-codigo']."'"
				." AND status='A'",
		));

		if($pagina_atual){
			$page_id = (existe($pagina_atual['id']) ? $pagina_atual['id'] : '');
			$publisher_id = (existe($pagina_atual['publisher_id']) ? $pagina_atual['publisher_id'] : '');
		}
	}

	// ===== Montar o src do iframe com o contexto da página hospedeira.

	$src = $_GESTOR['url-raiz'].'dashboard-site-toolbar/?page_id='.rawurlencode($page_id);
	if($publisher_id !== '' && $publisher_id !== null){
		$src .= '&publisher_id='.rawurlencode($publisher_id);
	}

	// ===== Iframe fixo no topo (~30px) da barra.

	$iframe =
		'<iframe id="c2f-site-toolbar" src="'.$src.'" title="Dashboard Site Toolbar" '
		.'style="position:fixed;top:0;left:0;width:100%;height:30px;border:0;margin:0;padding:0;z-index:2147483000;background:transparent;" allowtransparency="true"></iframe>';

	// ===== Injetar o iframe imediatamente após a abertura do <body>.
	//
	//       req-124 F1: junto com o iframe, o <body> recebe a classe `c2f-toolbar-ativa`. A barra é
	//       `position:fixed` e por isso ignora o `margin-top` que o `dashboard.toolbar.js` aplica no
	//       <html> — todo elemento `fixed`/`sticky` ancorado em `top:0` (a barra lateral e o cabeçalho
	//       do `layout-administrativo-tailwind`) continuaria por baixo dela. A classe é o gancho que os
	//       CSS de layout usam para descer esses elementos nos 30px da barra.

	$_GESTOR['pagina'] = preg_replace_callback('/(<body\b)([^>]*)(>)/i', function($m) use ($iframe){
		$atributos = $m[2];

		if(preg_match('/\sclass\s*=\s*"[^"]*"/i', $atributos)){
			$atributos = preg_replace('/(\sclass\s*=\s*")([^"]*)(")/i', '${1}${2} c2f-toolbar-ativa${3}', $atributos, 1);
		} else if(preg_match("/\sclass\s*=\s*'[^']*'/i", $atributos)){
			$atributos = preg_replace("/(\sclass\s*=\s*')([^']*)(')/i", '${1}${2} c2f-toolbar-ativa${3}', $atributos, 1);
		} else {
			$atributos .= ' class="c2f-toolbar-ativa"';
		}

		return $m[1].$atributos.$m[3].$iframe;
	}, $_GESTOR['pagina'], 1);

	// ===== Script de compensação de topo (dashboard.toolbar.js) — a URL dashboard/toolbar.js
	//       é servida como o físico dashboard.toolbar.js (ver arquivo-estatico.php).
	//
	//       req-112: o cache-bust da Editbar passou a seguir a versão da BIBLIOTECA html-editor.
	//
	//       Antes, sem a chave `versao`, `gestor_pagina_javascript_incluir()` caía em
	//       `$_GESTOR['versao']` — a versão do SISTEMA, que só muda a cada release. Toda alteração
	//       neste arquivo entre releases ficava presa no cache do navegador, mesmo após o deploy.
	//
	//       A versão escolhida é a de `biblioteca-html-editor` (e não a do módulo dashboard) porque a
	//       Editbar e o motor `html-editor.js` mudam juntos: mexer na barra quase sempre implica mexer
	//       no editor. Com uma única versão governando os DOIS arquivos, basta bumpar
	//       `gestor/bibliotecas/html-editor.php` para forçar o reload de ambos — sem depender de
	//       lembrar de uma segunda string espalhada em outro arquivo.

	gestor_incluir_biblioteca('html-editor');

	$htmlEditorVersao = $_GESTOR['biblioteca-html-editor']['versao'] ?? $_GESTOR['versao'];

	// O motor é carregado sob demanda POR DENTRO do dashboard.toolbar.js; como ele não enxerga o
	// PHP, a versão viaja pela variável global de JS.
	if(!isset($_GESTOR['javascript-vars'])) $_GESTOR['javascript-vars'] = Array();
	$_GESTOR['javascript-vars']['htmlEditorVersao'] = $htmlEditorVersao;

	gestor_pagina_javascript_incluir(Array(
		'tipo' => 'toolbar',
		'modulo_id' => 'dashboard',
		'versao' => $htmlEditorVersao,
	));
}

// =========================== Funções de Autenticação de Usuário

function gestor_permissao_validar_jwt($params = false){
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// token - String - Obrigatório - Token JWT de verificação.
	// chavePrivada - String - Obrigatório - Chave privada para conferir a assinatura do token.
	// chavePrivadaSenha - String - Obrigatório - Senha da chave privada.
	
	// ===== 
	
	if(isset($chavePrivada) && isset($chavePrivadaSenha)){
		// ===== Quebra o token em header, payload e signature
		
		$part = explode(".",$token);
		
		if(gettype($part) != 'array'){
			return false;
		}
		
		$header = $part[0];
		$payload = $part[1];
		$signature = $part[2];

		$encodedData = $signature;
		
		// ===== Abrir chave privada com a senha
		
		$resPrivateKey = openssl_get_privatekey($chavePrivada,$chavePrivadaSenha);
		
		// ===== Decode base64 to reaveal dots (Dots are used in JWT syntaxe)

		$encodedData = base64_decode($encodedData);

		// ===== Decrypt data in parts if necessary. Using dots as split separator.

		$rawEncodedData = $encodedData;

		$countCrypt = 0;
		$partialDecodedData = '';
		$decodedData = '';
		$split2 = explode('.',$rawEncodedData);
		foreach($split2 as $part2){
			$part2 = base64_decode($part2);
			
			openssl_private_decrypt($part2, $partialDecodedData, $resPrivateKey);
			$decodedData .= $partialDecodedData;
		}

		// ===== Validate JWT

		if($header.".".$payload === $decodedData){
			$payload = base64_decode($payload);
			$payload = json_decode($payload,true);
			
			// ===== Verifica se as variáveis existem, senão foi formatado errado e não deve aceitar.
			
			if(!isset($payload['exp']) || !isset($payload['sub'])){
				return false;
			}
			
			$expiracao_ok = false;
			
			// ===== Se a expiração for igual a 0 é sessão, senão tem que comparar tempo.
			
			if((int)$payload['exp'] === 0){
				$expiracao_ok = true;
			} else {
				// ===== Se o tempo de expiração do token for menor que o tempo agora, é porque este token está vencido.
				
				if((int)$payload['exp'] > time()){
					$expiracao_ok = true;
				}
			}
			
			if($expiracao_ok){
				// Se tudo estiver válido, retorna o pubID do token.
				
				return $payload['sub'];
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
}

/**
 * Requisição de crawler/scraper social, memoizada por request (req-109 / BATCH-109).
 *
 * @return bool
 */
function gestor_requisicao_crawler(){
	global $_GESTOR;

	if(!array_key_exists('requisicao-crawler', $_GESTOR)){
		$_GESTOR['requisicao-crawler'] = gestor_crawler_detectar($_SERVER['HTTP_USER_AGENT'] ?? null);
	}

	return $_GESTOR['requisicao-crawler'];
}

/**
 * Emite (e, quando necessário, confere) o cookie de verificação do navegador.
 *
 * req-109 / BATCH-109 — o comportamento passou a depender de QUEM pergunta:
 *
 * - Crawler/scraper social: nada acontece. Esses agentes não guardam cookie e não seguem o
 *   round-trip; qualquer `Location:` faz o preview do link perder o HTML público e as tags
 *   OpenGraph da página real.
 * - Página pública (padrão): o cookie é emitido no cabeçalho `Set-Cookie` da PRÓPRIA resposta,
 *   sem interromper o carregamento com o 302 para `_gestor-cookie-verify/`. O visitante recebe a
 *   página pedida na primeira requisição e o cookie passa a existir a partir da próxima.
 * - Fluxo que exige sessão ativa por cookie ($exigirSessao = true — login, perfil, áreas
 *   restritas): mantém o round-trip original, que é o único jeito de PROVAR que o navegador
 *   aceita cookies antes de autenticar.
 *
 * @param bool $exigirSessao Quando true, redireciona para conferir o cookie numa nova conexão.
 * @return void
 */
function gestor_cookie_verificacao($exigirSessao = false){
	global $_GESTOR;
	global $_CONFIG;

	// ===== req-111 (CR-001): a decisão vive em `gestor_cookie_verificacao_desfecho()`, função pura
	//       da biblioteca, para ser coberta por teste. O laço que ela impede foi MEDIDO em produção:
	//
	//       `/` → `_gestor-cookie-verify/<id>/?url=` → `cookies-is-mandatory/` →
	//       `_gestor-cookie-verify/<id>/?url=cookies-is-mandatory%2F` → `cookies-is-mandatory/` → …
	//
	//       Nasce porque a PRÓPRIA `cookies-is-mandatory/` é uma página e, ao ser renderizada,
	//       reentra aqui — a tela que existe para explicar o problema estava presa nele. Qualquer
	//       cliente sem cookie (o Googlebot não persiste cookie entre requisições) ficava rodando
	//       em círculo e NUNCA via conteúdo.

	$desfecho = gestor_cookie_verificacao_desfecho(Array(
		'crawler' => gestor_requisicao_crawler(),
		'tem_cookie' => isset($_COOKIE[$_CONFIG['cookie-verify']]) || isset($_COOKIE[$_CONFIG['cookie-authname']]),
		'exigir_sessao' => $exigirSessao,
		'caminho' => (string)($_GESTOR['caminho-total'] ?? ''),
	));

	if($desfecho === 'ignorar') return;

	// ===== Criar um cookie de verificação

	gestor_incluir_biblioteca('seguranca');
	$cookieId = seguranca_token_aleatorio(32);

	if(!headers_sent()){
		setcookie($_CONFIG['cookie-verify'], $cookieId, [
			'expires' => '0',
			'path' => '/',
			'domain' => $_SERVER['SERVER_NAME'],
			'secure' => gestor_cookie_is_secure(),
			'httponly' => true,
			'samesite' => 'Lax',
		]);
	}

	// ===== O cookie recém-emitido só chega no $_COOKIE da PRÓXIMA requisição; registrar aqui evita
	//       que uma segunda chamada no mesmo request reemita o cabeçalho com outro valor.

	$_COOKIE[$_CONFIG['cookie-verify']] = $cookieId;

	// ===== Verificação silenciosa: a página pedida segue carregando normalmente.

	if($desfecho !== 'redirecionar') return;

	// ===== Redirecionar o usuário afim de conferir se está ativo numa nova conexão com a URL e queryString caso o mesmo não tenha sido logado de outra forma.

	$url = !empty($_GESTOR['caminho-total']) ? urlencode($_GESTOR['caminho-total']) : '';
	$queryString = urlencode(gestor_querystring());

	header("Location: " . $_GESTOR['url-raiz'] . '_gestor-cookie-verify/'.$cookieId.'/?url='.$url.(existe($queryString) ? '&queryString='.$queryString : ''));
	exit;
}

function gestor_permissao_token(){
	global $_GESTOR;

	// ===== Idempotência por requisição (req-047 / BATCH-085 §1).
	// Memoiza o resultado para não revalidar/renovar o JWT mais de uma vez no mesmo request.
	// Sem isto, chamar esta função duas vezes (ex.: o filtro global roteador.paginas do
	// bloqueio de assinatura expirada, seguido de gestor_permissao()) faria a segunda
	// chamada ler o token que a primeira acabou de renovar e invalidar (o cookie novo só
	// vale no próximo request), deslogando um usuário legítimo com 401.

	if(array_key_exists('permissao-token-resultado', $_GESTOR)){
		return $_GESTOR['permissao-token-resultado'];
	}

	$_GESTOR['permissao-token-resultado'] = gestor_permissao_token_processar();
	return $_GESTOR['permissao-token-resultado'];
}

function gestor_permissao_token_processar(){
	global $_GESTOR;
	global $_CONFIG;

	// ===== Verifica se cookie no navegador está ativo.

	gestor_cookie_verificacao();
	
	// ===== Verifica se existe o cookie de autenticação gerado no login com sucesso.
	
	if(!isset($_COOKIE[$_CONFIG['cookie-authname']])){
		return false;
	}
	
	$JWTToken = $_COOKIE[$_CONFIG['cookie-authname']];
	
	if(!existe($JWTToken)){
		return false;
	}
	
	// ===== Abrir chave privada e a senha da chave
	
	$keyPrivatePath = $_GESTOR['openssl-path'] . 'privada.key';
	
	$fp = fopen($keyPrivatePath,"r");
	$keyPrivateString = fread($fp,8192);
	fclose($fp);
	
	$chavePrivadaSenha = $_CONFIG['openssl-password'];
	
	// ===== Verificar se o JWT é válido.
	
	$tokenPubId = gestor_permissao_validar_jwt(Array(
		'token' => $JWTToken,
		'chavePrivada' => $keyPrivateString,
		'chavePrivadaSenha' => $chavePrivadaSenha,
	));
	
	if($tokenPubId){
		// ===== Verifica se o token está ativo. Senão estiver invalidar o cookie.
		
		$usuarios_tokens = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_usuarios_tokens',
				'id_usuarios',
				'pubIDValidation',
				'data_criacao',
				'expiration',
			))
			,
			"usuarios_tokens",
			"WHERE pubID='".$tokenPubId."'"
		);
		
		if($usuarios_tokens){
			// ===== Limpeza dos tokens mais antigos no banco de dados.
			
			$invalidar_token = false;
			
			if(!existe(gestor_sessao_variavel('usuario-tokens-limpeza'))){
				// ===== Deletar todos os tokens de sessão (expiration == 0) quando as datas de criação mais o tempo de limpeza dos mesmos forem menor que o tempo agora.
				
				banco_delete
				(
					"usuarios_tokens",
					"WHERE expiration=0"
					." AND TIMESTAMPADD(SECOND,".$_CONFIG['session-garbagetime'].",data_criacao) < NOW()"
				);
				
				// ===== Deletar todos os tokens persistentes (expiration != 0) quando o tempo de expiração mais o tempo de vida dos tokens forem menor que o tempo agora.
				
				banco_delete
				(
					"usuarios_tokens",
					"WHERE expiration!=0"
					." AND expiration < ".time()
				);
				
				gestor_sessao_variavel('usuario-tokens-limpeza',true);
				
				// ===== Verificar se um dos tokens excluídos é o token atual. Se sim, invalidar token.
				
				$usuarios_tokens_verificar = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_usuarios_tokens',
					))
					,
					"usuarios_tokens",
					"WHERE pubID='".$tokenPubId."'"
				);
				
				if(!$usuarios_tokens_verificar){
					$invalidar_token = true;
				}
			}
			
			if(!$invalidar_token){
				// ===== Verificar se o token não expirou.
				
				$expiration = $usuarios_tokens[0]['expiration'];
				
				$expiracao_ok = false;
				$token_sessao = false;
				
				// ===== Se a expiração for igual a 0 é sessão, senão tem que comparar tempo de expiração.
				
				if((int)$expiration === 0){
					$expiracao_ok = true;
					$token_sessao = true;
					
					// ===== Caso o tempo de criação deste token for maior que o tempo de limpeza, deve ser deletado e não aceito.
					
					$data_criacao = $usuarios_tokens[0]['data_criacao'];
					
					$time_criacao = strtotime($data_criacao);
					
					if($time_criacao + $_CONFIG['session-garbagetime'] < time()){
						$expiracao_ok = false;
						
						$id_usuarios_tokens = $usuarios_tokens[0]['id_usuarios_tokens'];
						
						banco_delete
						(
							"usuarios_tokens",
							"WHERE id_usuarios_tokens='".$id_usuarios_tokens."'"
						);
					}
				} else {
					// ===== Se o tempo de expiração do token for maior que o tempo agora, é porque este token está ativo. Senão está vencido e deve ser deletado.
					
					if((int)$expiration > time()){
						$expiracao_ok = true;
					} else {
						$id_usuarios_tokens = $usuarios_tokens[0]['id_usuarios_tokens'];
						
						banco_delete
						(
							"usuarios_tokens",
							"WHERE id_usuarios_tokens='".$id_usuarios_tokens."'"
						);
					}
				}
				
				if($expiracao_ok){
					// ===== Validar o token com o hash de validação para evitar geração de token por hacker caso ocorra roubo da tabela 'usuarios_tokens'.
					
					$bd_hash = $usuarios_tokens[0]['pubIDValidation'];
					$token_hash = hash_hmac($_CONFIG['usuario-hash-algo'], $tokenPubId, $_CONFIG['usuario-hash-password']);
					
					if($bd_hash === $token_hash){
						$data_criacao = $usuarios_tokens[0]['data_criacao'];
						$id_usuarios = $usuarios_tokens[0]['id_usuarios'];
						
						if(!$token_sessao){
							// ===== Verificar se precisa renovar JWTToken, se sim, apagar token anterior e criar um novo no lugar.
							
							$time_criacao = strtotime($data_criacao);
							
							if($time_criacao + $_CONFIG['cookie-renewtime'] < time()){
								gestor_incluir_biblioteca('usuario');
								
								usuario_gerar_token_autorizacao(Array(
									'id_usuarios' => $id_usuarios,
								));
								
								$id_usuarios_tokens = $usuarios_tokens[0]['id_usuarios_tokens'];
								
								banco_delete
								(
									"usuarios_tokens",
									"WHERE id_usuarios_tokens='".$id_usuarios_tokens."'"
								);
							}
						}
						
						$_GESTOR['usuario-id'] = $id_usuarios;
						$_GESTOR['usuario-token-id'] = $tokenPubId;

						// ===== Proteção contra Session Hijacking (req-030)
						// Valida User-Agent e bloco de IP; em discrepância suspeita, invalida
						// o token e cai no fluxo de falha (limpa cookie e retorna false).

						gestor_incluir_biblioteca('seguranca');

						if(seguranca_sessao_validar()){
							return true;
						}

						seguranca_sessao_invalidar($tokenPubId);
					}
				}
			}
		}
	}
	
	// ===== Caso não valide, deletar cookie e retornar 'false'.
	
	setcookie($_CONFIG['cookie-authname'], "", [
		'expires' => time() - 3600,
		'path' => '/',
		'domain' => $_SERVER['SERVER_NAME'],
		'secure' => gestor_cookie_is_secure(),
		'httponly' => true,
		'samesite' => 'Lax',
	]);
	
	unset($_COOKIE[$_CONFIG['cookie-authname']]);
	
	return false;
}

function gestor_permissao_fingerprint(){
	global $_GESTOR;
	
	// =====
	
	if(existe(gestor_sessao_variavel('browser-fingerprint'))){
		return true;
	} else {
		return false;
	}
}

/**
 * Verifica se o usuário atual pode acessar o módulo da requisição.
 *
 * @param bool $alertar Quando false, retorna apenas o resultado da consulta,
 *                      sem gravar alerta em sessão.
 * @param string|false $modulo_forcado Módulo a consultar; false usa a rota atual.
 */
function gestor_permissao_modulo($alertar = true, $modulo_forcado = false){
	global $_GESTOR;
	
	$usuario = gestor_usuario();
	$modulo = $modulo_forcado ?: ($_GESTOR['modulo'] ?? '');
	
	if(!existe($modulo)){
		return true;
	}
	
	$modulos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_modulos',
		))
		,
		"modulos",
		"WHERE id='".$modulo."'"
		." AND status='A'"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
	);
	
	if($modulos){
		// ===== Verificar se o usuário é filho de um host ou não.
		
		if(existe($usuario['id_hosts'])){
			// ===== Verificar se o usuário tem um perfil de gestor ativo.
			
			if(existe($usuario['gestor_perfil'])){
				$gestor_perfil = $usuario['gestor_perfil'];
				
				// ===== Verificar se o módulo alvo tem permissão no perfil.
				
				$usuarios_gestores_perfis_modulos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_usuarios_gestores_perfis_modulos',
					))
					,
					"usuarios_gestores_perfis_modulos",
					"WHERE perfil='".$gestor_perfil."'"
					." AND modulo='".$modulo."'"
					." AND id_hosts='".$usuario['id_hosts']."'"
				);
				
				// ===== Caso tenha permissão retornar true.
				
				if($usuarios_gestores_perfis_modulos){
					return true;
				}
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
						'id_usuarios_perfis_modulos',
					))
					,
					"usuarios_perfis_modulos",
					"WHERE perfil='".$perfil."'"
					." AND modulo='".$modulo."'"
				);
				
				// ===== Caso tenha permissão retornar true.
				
				if($usuarios_perfis_modulos){
					return true;
				}
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
					'id_usuarios_perfis_modulos',
				))
				,
				"usuarios_perfis_modulos",
				"WHERE perfil='".$perfil."'"
				." AND modulo='".$modulo."'"
			);
			
			// ===== Caso tenha permissão retornar true.
			
			if($usuarios_perfis_modulos){
				return true;
			}
		}
	}
	
	if($alertar){
		gestor_incluir_biblioteca('interface');

		interface_alerta(Array(
			'redirect' => true,
			'msg' => gestor_variaveis(Array('modulo' => 'usuarios','id' => 'alert-without-permission'))
		));
	}
	
	return false;
}

function gestor_permissao(){
	global $_GESTOR;

	// ===== req-111 (CR-001): a chamada `gestor_cookie_verificacao(true)` que o req-109 acrescentou
	//       aqui foi REMOVIDA por ser redundante e por custar um salto a mais.
	//
	//       Quem chega numa página protegida sem sessão é mandado para `/signin/` duas linhas abaixo,
	//       e é o `/signin/` que precisa (e faz) a prova de cookie. Quem chega COM sessão já tem o
	//       cookie de autenticação, e a verificação retornaria sem efeito. O único resultado prático
	//       do round-trip aqui era um redirecionamento extra antes do login.

	if(!gestor_permissao_token()){
		$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
		$caminho = rtrim($caminho,'/').'/';

		gestor_sessao_variavel("redirecionar-local",$caminho);

		if($_GESTOR['ajax']){
			gestor_roteador_erro(Array(
				'codigo' => 401,
				'ajax' => $_GESTOR['ajax'],
				'redirect' => 'signin/',
				'auth_required' => true,
			));
		} else {
			gestor_roteador_erro(Array(
				'codigo' => 401,
			));
		}
	}
	
	/* if(!gestor_permissao_fingerprint()){
		if($_GESTOR['ajax']){
			gestor_roteador_erro(Array(
				'codigo' => 401,
				'ajax' => $_GESTOR['ajax'],
			));
		} else {
			$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
			$caminho = rtrim($caminho,'/').'/';
			
			gestor_sessao_variavel("redirecionar-local",$caminho);
			
			gestor_roteador_erro(Array(
				'codigo' => 401,
				'redirect' => 'validate-user/',
				'querystring' => true,
			));
		}
	} */
	
	if(!gestor_permissao_modulo()){
		if($_GESTOR['ajax']){
			gestor_roteador_erro(Array(
				'codigo' => 401,
				'ajax' => $_GESTOR['ajax'],
				'redirect' => 'dashboard/',
			));
		} else {
			gestor_roteador_erro(Array(
				'codigo' => 401,
				'redirect' => 'dashboard/',
			));
		}
	}
}

function gestor_usuario(){
	global $_GESTOR;
	
	if(isset($_GESTOR['usuario-id'])){
		if(!isset($_GESTOR['usuario'])){
			$usuarios = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_hosts',
					'id_usuarios',
					'id_usuarios_perfis',
					'id',
					'usuario',
					'nome',
					'email',
					'gestor',
					'gestor_perfil',
				))
				,
				"usuarios",
				"WHERE id_usuarios='".$_GESTOR['usuario-id']."'"
			);

			$usuarios_perfis = banco_select_name
			(
				banco_campos_virgulas(Array(
					'nome',
					'id',
				))
				,
				"usuarios_perfis",
				"WHERE id_usuarios_perfis='".$usuarios[0]['id_usuarios_perfis']."'"
			);
			
			$_GESTOR['usuario'] = $usuarios[0];
			$_GESTOR['usuario']['perfil_nome'] = $usuarios_perfis[0]['nome'];
			$_GESTOR['usuario']['perfil_slug'] = $usuarios_perfis[0]['id'];
		}
		
		return $_GESTOR['usuario'];
	} else {
		return Array(
			'id_hosts' => '',
			'id_usuarios' => '0',
			'id_usuarios_perfis' => '0',
			'id' => '_anonimo',
			'gestor' => '',
			'gestor_perfil' => '',
			'usuario' => '_anonimo',
			'nome' => 'Anônimo',
			'perfil_nome' => 'Anônimo',
			'perfil_slug' => 'anonimo',
		);
	}
}

function gestor_usuario_perfil(){
	global $_CONFIG;

	// ===== Verifica se cookie no navegador está ativo.
	
	gestor_cookie_verificacao();
	
	// ===== Verifica se existe o cookie de profile existe.
	
	if(isset($_COOKIE[$_CONFIG['cookie-authprofile']])){
		return $_COOKIE[$_CONFIG['cookie-authprofile']];
	} else {
		return false;
	}
}

function gestor_acesso($operacao = false,$modulo = false,$usuario = false){
	global $_GESTOR;
	
	// ===== Parâmetros
	
	// operacao - String - Opcional - operação do módulo atual.
	// modulo - String - Opcional - foçar um módulo diferente do atual.
	// usuario - String - Opcional - foçar um usuário diferente do atual.
	
	// ===== 
	
	if(!$usuario){
		$usuario = gestor_usuario();
	}
	
	if(!$modulo){
		$modulo = $_GESTOR['modulo'];
	}
	
	if(!existe($modulo)){
		return false;
	}
	
	$modulos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_modulos',
		))
		,
		"modulos",
		"WHERE id='".$modulo."'"
		." AND status='A'"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
	);
	
	if($modulos){
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

		// ===== Acesso base ao módulo pelo perfil.

		$usuarios_perfis_modulos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_usuarios_perfis_modulos',
			))
			,
			"usuarios_perfis_modulos",
			"WHERE modulo='".$modulo."'"
			." AND perfil='".$perfil."'"
		);

		$modulo_acesso = ($usuarios_perfis_modulos ? true : false);

		// ===== Sem operação informada, o acesso ao módulo é suficiente.

		if(!$operacao){
			return $modulo_acesso;
		}

		$modulos_operacoes = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id',
			))
			,
			"modulos_operacoes",
			"WHERE (operacao='".$operacao."' OR id='".$operacao."')"
			." AND language='".$_GESTOR['linguagem-codigo']."'"
			." AND modulo_id='".$modulo."'"
			." AND status='A'"
		);

		// ===== Operação não cadastrada: fallback para permissão de módulo.

		if(!$modulos_operacoes){
			return $modulo_acesso;
		}
		
		if($modulos_operacoes){
			$operacao_id = $modulos_operacoes[0]['id'];
			
			$usuarios_perfis_modulos_operacoes = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_usuarios_perfis_modulos_operacoes',
				))
				,
				"usuarios_perfis_modulos_operacoes",
				"WHERE operacao='".$operacao_id."'"
				." AND perfil='".$perfil."'"
			);
			
			if($usuarios_perfis_modulos_operacoes){
				return true;
			}
		}
	}
	
	return false;
}

// =========================== Funções de Acesso

function gestor_roteador_301_ou_404($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// caminho - String - Obrigatório - Caminho para verificar se tem alguma página 301, senão gera erro 404.
	
	// ===== 
	
	if(isset($caminho)){
		// F10 do review de 2026-08-15: um mesmo caminho pode ter mais de um registro legítimo —
		// uma linha por IDIOMA da página (a tabela não tem coluna `language` e o caminho gravado é
		// agnóstico) e, quando um caminho é reciclado, uma linha por página que já o teve. Usar
		// sempre `[0]` fazia o registro mais ANTIGO vencer: no melhor caso o 301 não acontecia
		// (destino de outro idioma não passa no filtro), no pior redirecionava para a página errada.
		//
		// A varredura é do mais recente para o mais antigo e para no primeiro que resolva para uma
		// página ATIVA no idioma corrente — que é, por construção, a dona atual do caminho.
		$paginas_301 = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_paginas',
			))
			,
			"paginas_301",
			"WHERE caminho='".banco_escape_field($caminho)."'"
			." ORDER BY id_paginas_301 DESC"
		);

		if($paginas_301)
		foreach($paginas_301 as $registro_301){
			$paginas = banco_select_name
			(
				banco_campos_virgulas(Array(
					'caminho',
				))
				,
				"paginas",
				"WHERE id_paginas='".banco_escape_field($registro_301['id_paginas'])."'"
				." AND language='".$_GESTOR['linguagem-codigo']."'"
				." AND status='A'"
			);

			if($paginas){
				gestor_roteador_erro(Array(
					'codigo' => 301,
					'redirect' => $paginas[0]['caminho'],
				));
			}
		}
	}
	
	gestor_roteador_erro(Array(
		'codigo' => 404,
	));
}

function gestor_roteador_erro($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// codigo - Int - Obrigatório - Código do erro HTTP.
	// ajax - Bool - Opcional - Indicar se é uma conexão AJAX ou normal.
	// redirect - String - Opcional - Redirecionar para um local específico.
	// querystring - Bool - Opcional - Incluir a querystring no redirecionamento.
	
	// ===== 
	
	if(isset($codigo)){
		http_response_code($codigo);
		
		if(isset($ajax)){
			if(isset($_GESTOR['pagina-alerta'])) if($_GESTOR['pagina-alerta']) gestor_sessao_variavel("alerta",$_GESTOR['pagina-alerta']);
			
			header("Content-Type: application/json; charset: UTF-8");
			
			if(isset($redirect)){
				$resposta = Array(
					'error' => $codigo,
					'info' => 'JSON unauthorized',
					'redirect' => $redirect,
				);

				if(!empty($auth_required)){
					$resposta['code'] = 'AUTH_REQUIRED';
					$redirectSeguro = str_replace(Array("\r","\n"),'',(string)$redirect);
					header('X-Gestor-Auth-Redirect: '.rtrim($_GESTOR['url-raiz'],'/').'/'.ltrim($redirectSeguro,'/'));
				}

				echo json_encode($resposta);
			} else {
				switch($codigo){
					case 401:
						echo json_encode(Array(
							'error' => '401',
							'info' => 'JSON unauthorized',
						));
					break;
					case 404:
						echo json_encode(Array(
							'error' => '404',
							'info' => 'JSON not found',
						));
					break;
					case 500:
						echo json_encode(Array(
							'error' => '500',
							'info' => isset($mensagem) ? $mensagem : 'JSON internal server error',
						));
					break;
				}
			}
		} else {
			if(isset($redirect)){
				if(isset($querystring)){
					gestor_redirecionar($redirect,gestor_querystring());
				} else {
					gestor_redirecionar($redirect);
				}
			} else {
				switch($codigo){
					case 401:
						gestor_redirecionar('signin');
					break;
					case 404:
						gestor_redirecionar('404');
					break;
				}
			}
		}
		
		exit;
	}
}

/**
 * Resolve os valores OpenGraph da requisição corrente (req-109 / BATCH-109).
 *
 * A ordem é: metadados próprios da página (`$_GESTOR['pagina#og']`, preenchido pelo CRUD do
 * req-110 ou por um módulo) → título/nome da página → variáveis globais do `config.php`.
 *
 * @param array|false $params
 * @param array $params['pagina'] Registro da página (opcional; usa `pagina#titulo` quando ausente).
 * @return array Valores prontos para `gestor_open_graph_tags()`.
 */
function gestor_open_graph_dados($params = false){
	global $_GESTOR;
	global $_CONFIG;

	$pagina = (is_array($params) && isset($params['pagina']) && is_array($params['pagina'])) ? $params['pagina'] : Array();
	$og = (isset($_GESTOR['pagina#og']) && is_array($_GESTOR['pagina#og'])) ? $_GESTOR['pagina#og'] : Array();

	$siteName = (string)($_CONFIG['site-name'] ?? '');

	$titulo = (string)($og['title'] ?? '');
	if(!existe($titulo)) $titulo = (string)($pagina['nome'] ?? '');
	if(!existe($titulo)) $titulo = (string)($_GESTOR['pagina#titulo'] ?? '');
	if(!existe($titulo)) $titulo = $siteName;

	$descricao = (string)($og['description'] ?? '');
	if(!existe($descricao)) $descricao = (string)($_CONFIG['site-description'] ?? '');

	$imagem = (string)($og['image'] ?? '');
	if(!existe($imagem)) $imagem = (string)($_CONFIG['site-og-image'] ?? '');

	// Imagem relativa vira absoluta: o WhatsApp e o Meta descartam `og:image` sem host.
	if(existe($imagem) && !preg_match('#^(https?:)?//#i', $imagem)){
		$imagem = rtrim((string)($_GESTOR['url-full-http'] ?? ''), '/').'/'.ltrim($imagem, '/');
	}

	$url = (string)($og['url'] ?? '');
	if(!existe($url)){
		$caminho = (string)($_GESTOR['caminho-total'] ?? '');
		$url = (string)($_GESTOR['url-full-http'] ?? '').ltrim($caminho, '/');
	}

	return Array(
		'title'       => $titulo,
		'description' => $descricao,
		'image'       => $imagem,
		'url'         => $url,
		'site_name'   => $siteName,
		'type'        => (string)($og['type'] ?? 'website'),
	);
}

/**
 * Resolve `description` e `keywords` da requisição corrente (req-112 / BATCH-112).
 *
 * Ordem: metadados próprios da página (`$_GESTOR['pagina#og']`) → `og_descricao` da própria página
 * (quem preencheu só o texto social não precisa repetir) → `config.php`.
 *
 * @return array Valores prontos para `gestor_meta_seo_tags()`.
 */
function gestor_meta_seo_dados(){
	global $_GESTOR;
	global $_CONFIG;

	$og = (isset($_GESTOR['pagina#og']) && is_array($_GESTOR['pagina#og'])) ? $_GESTOR['pagina#og'] : Array();

	$descricao = (string)($og['meta_description'] ?? '');
	if(!existe($descricao)) $descricao = (string)($og['description'] ?? '');
	if(!existe($descricao)) $descricao = (string)($_CONFIG['site-description'] ?? '');

	$keywords = (string)($og['meta_keywords'] ?? '');
	if(!existe($keywords)) $keywords = (string)($_CONFIG['site-keywords'] ?? '');

	return Array(
		'description' => $descricao,
		'keywords' => $keywords,
	);
}

/**
 * Resposta mínima para crawler em página protegida (req-109 / BATCH-109).
 *
 * Devolve `200` com um documento que tem apenas `<head>`: título, `robots: noindex` e as tags
 * OpenGraph da página. O corpo fica vazio — o preview do link exibe o card correto sem que
 * nenhuma linha do conteúdo privado seja renderizada ou sequer consultada (o módulo da página
 * não chega a ser incluído).
 *
 * @param array $pagina Registro da página vindo do roteador.
 * @return void Encerra a requisição.
 */
function gestor_roteador_crawler_pagina_protegida($pagina = Array()){
	global $_GESTOR;

	// req-110: metadados próprios da página têm precedência sobre os fallbacks do config.
	$_GESTOR['pagina#og'] = gestor_pagina_og_do_registro($pagina);

	$dados = gestor_open_graph_dados(Array('pagina' => $pagina));
	$tags = gestor_open_graph_tags($dados);

	$html = '<!DOCTYPE html>'."\n"
		.'<html lang="'.htmlspecialchars((string)($_GESTOR['linguagem-codigo'] ?? 'pt-br'), ENT_QUOTES, 'UTF-8').'">'."\n"
		.'<head>'."\n"
		.'	<meta charset="UTF-8">'."\n"
		.'	<meta name="robots" content="noindex, nofollow">'."\n"
		.'	<title>'.htmlspecialchars($dados['title'], ENT_QUOTES, 'UTF-8').'</title>'."\n";

	foreach($tags as $tag) $html .= '	'.$tag."\n";

	$html .= '</head>'."\n".'<body></body>'."\n".'</html>';

	http_response_code(200);
	header('Content-Type: text/html; charset=UTF-8');
	header('X-Robots-Tag: noindex');
	echo $html;
	exit;
}

function gestor_hotfix(){
	
	
	echo '<p>Hotfix Done!</p>';
	
	exit;
}

/**
 * Live Editor — restauração de backup via sessão (BATCH-085).
 *
 * Substitui a injeção de HTML no CLIENTE (que quebrava ao trocar de backup) por um fluxo server-side:
 * o front clica num backup → sinaliza via AJAX (`site-toolbar-backup-restore`) → grava-se uma variável
 * de sessão → recarrega a página. Aqui, no roteamento (page load normal), detectamos a sinalização e
 * substituímos o HTML da PÁGINA ou do LAYOUT pela versão do backup — que é então renderizada pelo
 * pipeline normal do gestor (robusto). A sinalização é consumida UMA vez; um flag JS
 * (`gestor.siteToolbarBackupRestaurado`) avisa o front para reentrar no modo de edição já com o backup.
 *
 * @param array $paginas Resultado do select de páginas do roteador (por referência).
 * @return void
 */
function gestor_site_toolbar_backup_aplicar(&$paginas){
	global $_GESTOR;

	// Só no page load normal (não-AJAX) e para usuário logado — evita a query de sessão para visitantes.
	if(!empty($_GESTOR['ajax'])) return;
	if(!(isset($_GESTOR['usuario-id']) && (int)$_GESTOR['usuario-id'] > 0)) return;
	if(!function_exists('gestor_sessao_variavel')) return;

	$sinal = gestor_sessao_variavel('site-toolbar-backup-restore');
	if(!$sinal || !is_array($sinal)) return;

	// Confere se a sinalização é da página que está sendo roteada (pelo caminho normalizado).
	$caminhoAtual = rtrim(($_GESTOR['caminho-total'] ?? ''), '/').'/';
	if(($sinal['caminho'] ?? '') !== $caminhoAtual) return;

	// Consome a sinalização (uma vez só, mesmo que algo falhe adiante).
	if(function_exists('gestor_sessao_variavel_del')) gestor_sessao_variavel_del('site-toolbar-backup-restore');

	$id = (int)($sinal['id'] ?? 0);
	$type = (($sinal['type'] ?? '') === 'layout') ? 'layout' : 'page';
	if($id <= 0) return;

	$row = banco_select(Array(
		'unico' => true,
		'tabela' => 'backup_campos',
		'campos' => Array('valor'),
		'extra' => "WHERE id_backup_campos='".$id."' AND campo='html'",
	));
	if(!$row) return;

	$valor = (string)$row['valor'];

	// Override consumido no roteador: para a página, ainda substituímos $paginas[0]['html'] (prod); o
	// override em $_GESTOR cobre também o dev-env, onde o html vem do arquivo físico.
	if($type === 'layout'){
		$_GESTOR['site-toolbar-backup-layout-html'] = $valor;
	} else {
		$_GESTOR['site-toolbar-backup-page-html'] = $valor;
		if(is_array($paginas) && isset($paginas[0])) $paginas[0]['html'] = $valor;
	}

	if(!isset($_GESTOR['javascript-vars'])) $_GESTOR['javascript-vars'] = Array();
	$_GESTOR['javascript-vars']['siteToolbarBackupRestaurado'] = true;
}

function gestor_roteador(){
	global $_GESTOR;
	global $_INDEX;
	global $_CONFIG;
	
	// ===== Condições iniciais para definir o módulo e a página
	
	$caminho = (isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : '');
	$caminho = rtrim($caminho,'/').'/';
	
	$_GESTOR['ajax'] = (isset($_REQUEST['ajax']) ? true : false);
	$_GESTOR['ajaxPagina'] = (isset($_REQUEST['ajaxPagina']) ? true : false);
	$_GESTOR['ajaxWidgets'] = (isset($_REQUEST['ajaxWidgets']) ? $_REQUEST['ajaxWidgets'] : false);
	$_GESTOR['ajax-opcao'] = (isset($_REQUEST['ajaxOpcao']) ? banco_escape_field($_REQUEST['ajaxOpcao']) : false);
	$_GESTOR['opcao'] = (isset($_REQUEST['opcao']) ? banco_escape_field($_REQUEST['opcao']) : false);
	$_GESTOR['paginaIframe'] = (isset($_REQUEST['paginaIframe']) ? true : false);
	$_GESTOR['hotfix'] = (isset($_REQUEST['hotfix']) ? true : false);
	
	$_GESTOR['modulo-registro-id'] = (isset($_REQUEST['ajaxRegistroId']) ? banco_escape_field($_REQUEST['ajaxRegistroId']) : NULL);

	$_GESTOR['page-languages'] = [$_GESTOR['linguagem-codigo']];

	$lang = $_GESTOR['linguagem-codigo'];
	
	// ===== Implementação de um hotfix.
	
	if($_GESTOR['hotfix']){
		gestor_hotfix();
	}
	
	// ===== Rotear URLs de sistema
	
	if(isset($_GESTOR['caminho']) && isset($_GESTOR['caminho'][0]))
	switch($_GESTOR['caminho'][0]){
		case '_gestor-cookie-verify':
			// ===== Verifica se é retorno de redirecionamento veio junto com o cookie. Se sim redirecionar usuário para a URL com queryString. Senão redireciona automaticamente para página informando a obrigatoriedade do uso de cookies para funcionar a página com permissão.

			// req-109: crawler que caiu aqui (link antigo compartilhado) volta para a URL de origem em
			// vez de receber a página de cookies obrigatórios — só assim o preview lê o OpenGraph real.
			if(gestor_requisicao_crawler()){
				$url = !empty($_REQUEST['url']) ? urldecode(banco_escape_field($_REQUEST['url'])) : '';
				$queryString = !empty($_REQUEST['queryString']) ? urldecode(banco_escape_field($_REQUEST['queryString'])) : '';

				header("Location: " . $_GESTOR['url-raiz'] . $url .(existe($queryString) ? '?'.$queryString : '')); exit;
			}

			if(!isset($_COOKIE[$_CONFIG['cookie-verify']])){
				header("Location: " . $_GESTOR['url-raiz'] . 'cookies-is-mandatory/'); exit;
			} else {
				$url = !empty($_REQUEST['url']) ? urldecode(banco_escape_field($_REQUEST['url'])) : '';
				$queryString = !empty($_REQUEST['queryString']) ? urldecode(banco_escape_field($_REQUEST['queryString'])) : '';
				
				header("Location: " . $_GESTOR['url-raiz'] . $url .(existe($queryString) ? '?'.$queryString : '')); exit;
			}
		break;
	}
	
	// ===== Definição dos campos necessários para retornar os dados da página
	
	if($_GESTOR['ajax']){
		$campos = Array(
			'modulo',
			'plugin',
			'sem_permissao',
			'opcao',
		);
		
		// ===== Se válido pegar o html também.
		
		if($_GESTOR['ajaxPagina']){
			$campos[] = 'html';
		}
	} else if($_GESTOR['opcao']){
		$campos = Array(
			'modulo',
			'plugin',
			'sem_permissao',
		);
	} else {
		$campos = Array(
			'layout_id',
			'html',
			'html_extra_head',
			'css',
			'css_precompiled',
			'css_compiled',
			'modulo',
			'plugin',
			'opcao',
			'sem_permissao',
			'nome',
			'framework_css',
			// req-110: metadados de compartilhamento social gravados por página.
			'imagem_destaque',
			'og_titulo',
			'og_descricao',
			// req-112: meta tags clássicas de SEO.
			'meta_descricao',
			'meta_keywords',
		);
	}

	// ===== Identificador da página.
	//
	// Em ambiente de desenvolvimento o id serve para localizar o recurso em disco. req-086: ele
	// passou a ser necessário em QUALQUER ambiente — é a chave por onde a configuração de projeto
	// reconhece a rota (`project-page-tailwind-bundles`). Enquanto vinha só em desenvolvimento,
	// `$paginas[0]['id']` chegava vazio em produção e toda decisão baseada nele virava no-op
	// silencioso: o comportamento divergia entre os dois ambientes sem nada acusar.

	$campos[] = 'id';

	// ===== Buscar no banco de dados o alvo da requisição
	
	$paginas = banco_select_name
	(
		banco_campos_virgulas($campos)
		,
		"paginas",
		"WHERE caminho='".banco_escape_field($caminho)."'"
		." AND language='".$_GESTOR['linguagem-codigo']."'"
		." AND (tipo='sistema' OR tipo='pagina')"
		." AND status='A'"
		// ===== Janela de publicação (agendamento — BATCH-075/Meta 5): fora da janela → 404.
		." AND (data_publicacao_inicio IS NULL OR data_publicacao_inicio <= NOW())"
		." AND (data_publicacao_fim IS NULL OR data_publicacao_fim >= NOW())"
	);

	// ===== Caso não encontre a página e a mesma exista em linguagem diferente do padrão do sistema, devolver a página com a primeira língua disponível.

	if(!isset($paginas)){
		$paginas = banco_select_name
		(
			banco_campos_virgulas($campos)
			,
			"paginas",
			"WHERE caminho='".banco_escape_field($caminho)."'"
			." AND (tipo='sistema' OR tipo='pagina')"
			." AND status='A'"
			." AND (data_publicacao_inicio IS NULL OR data_publicacao_inicio <= NOW())"
			." AND (data_publicacao_fim IS NULL OR data_publicacao_fim >= NOW())"
		);
	}

	// ===== Hook: roteador.paginas
	if (isset($paginas)) {
		gestor_incluir_biblioteca('hooks');
		$paginas = hook_apply_filters('gestor', 'roteador.paginas', $paginas);
	}

	// ===== Live Editor: aplicar restauração de backup sinalizada por sessão (BATCH-085).
	if (isset($paginas)) {
		gestor_site_toolbar_backup_aplicar($paginas);
	}

	// ===== Verificar se a página existe. Se sim, montar a página, executar módulo se houver e imprimir. Senão gerar erro 404 ou redirecionar para página 404.

	if(isset($paginas)){
		// ===== Definir o módulo alvo da página.
		$_GESTOR['modulo'] = $paginas[0]['modulo'];

		// ===== Verificar linguagens de uma página

		$paginas_languages = banco_select_name
		(
			banco_campos_virgulas(Array(
				'language'
			))
			,
			"paginas",
			"WHERE caminho='".banco_escape_field($caminho)."'"
			." AND (tipo='sistema' OR tipo='pagina')"
			." AND status='A'"
		);

		if($paginas_languages){
			$languages = Array();
			foreach($paginas_languages as $paginas_language){
				$languages[] = $paginas_language['language'];
			}

			if(count($languages) > 1){
				$_GESTOR['page-languages'] = $languages;
			}
		}

		// ==== Verificar se a página tem permissão, se houver e o usuário não estiver logado, deve redirecionar para a página de login e finalizar a requisição.
		if(!existe($paginas[0]['sem_permissao'])){
			// req-109: crawler sem sessão numa página protegida recebe só o <head> com OpenGraph.
			// Redirecioná-lo para /signin/ faz o preview do link exibir a tela de login; devolver o
			// conteúdo seria vazamento. O corpo sai vazio e o visitante humano continua no fluxo normal.
			if(!$_GESTOR['ajax'] && gestor_requisicao_crawler() && !gestor_permissao_token()){
				gestor_roteador_crawler_pagina_protegida($paginas[0]);
			}

			gestor_permissao();
		} else {
			$sem_permissao = true;
		}

		// ====== Definir variáveis.
		$modulo = $paginas[0]['modulo'];
		$plugin = $paginas[0]['plugin'];
		$id = $paginas[0]['id'] ?? '';

		// ===== Montar a página de acordo com o tipo de requisição (AJAX ou normal).
		if($_GESTOR['ajax']){
			// ===== Definir opção da página.
			if(!$_GESTOR['opcao']) $_GESTOR['opcao'] = $paginas[0]['opcao'];
			
			// ===== Incluir html da página.
			
			if($_GESTOR['ajaxPagina']){
				if($_GESTOR['development-env']){

					if(existe($modulo)){
						if(existe($plugin)){
							$html_path = $_GESTOR['plugins-path'].$plugin.'/modules/'.$modulo.'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.html';
						} else {
							$html_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.html';
						}
					} else {
						$html_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.html';
					}

					$html = (file_exists($html_path)) ? file_get_contents($html_path) : (existe($paginas[0]['html']) ? $paginas[0]['html'] : '');
				} else {
					$html = $paginas[0]['html'];
				}
				
				$_GESTOR['pagina'] = $html;
			}
			
			// ===== Módulo alvo quando houver no backend, executar.
			$module_path = '';

			// ===== Verificar se a página é de acesso sem permissão, caso seja, deve procurar por um arquivo de módulo com o sufixo '.ajax.public' para ser possível ter um módulo específico para conexões AJAX sem permissão, caso contrário, deve procurar pelo módulo normalmente.
			$modulo_ajax_publico = '';
			if(isset($sem_permissao)){
				$modulo_ajax_publico = '.ajax.public';
			}

			if(existe($modulo)){
				if(existe($plugin)){
					$module_path = $_GESTOR['plugins-path'].$plugin.'/modules/'.$modulo.'/'.$modulo.$modulo_ajax_publico.'.php';
				} else {
					$module_path = $_GESTOR['modulos-path'].$modulo.'/'.$modulo.$modulo_ajax_publico.'.php';
				}
			} else if($_GESTOR['opcao']){
				$module_path = $_GESTOR['modulos-path'].'global.php';
			}

			if(is_file($module_path)){
				require_once($module_path);
			}

			// ===== Incluir controladores de widgets na requisição AJAX.

			gestor_pagina_widgets_ajax();

			// ===== Retornar a página formatada para o cliente. Ou erro caso haja.

			if(isset($_GESTOR['ajax-json'])){
				header("Content-Type: application/json; charset: UTF-8");
				echo json_encode($_GESTOR['ajax-json']);
				exit;
			} else {
				gestor_roteador_erro(Array(
					'codigo' => 500,
					'ajax' => $_GESTOR['ajax'],
					'mensagem' => 'AJAX error: No response data set.',
				));
			}
		} else {
			// ===== Caso haja necessidade, alterar opção no módulo e redirecionar para a raiz do módulo
			if($_GESTOR['opcao']){
				$module_path = '';

				if(existe($modulo)){
					if(existe($plugin)){
						$module_path = $_GESTOR['plugins-path'].$plugin.'/modules/'.$modulo.'/'.$modulo.'.php';
					} else {
						$module_path = $_GESTOR['modulos-path'].$modulo.'/'.$modulo.'.php';
					}
				}

				if(is_file($module_path)){
					require_once($module_path);
				}
				
				gestor_redirecionar_raiz();
			}
			
			// ===== Senão houver opção de alteração retornar a página alvo
			
			$nome = $paginas[0]['nome'];

			if($_GESTOR['development-env']){
				if(existe($modulo)){
					if(existe($plugin)){
						$html_path = $_GESTOR['plugins-path'].$plugin.'/modules/'.$modulo.'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.html';
						$css_path = $_GESTOR['plugins-path'].$plugin.'/modules/'.$modulo.'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.css';
						$css_precompiled_path = $_GESTOR['plugins-path'].$plugin.'/modules/'.$modulo.'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.precompiled.css';
					} else {
						$html_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.html';
						$css_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.css';
						$css_precompiled_path = $_GESTOR['modulos-path'].$modulo.'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.precompiled.css';
					}
				} else {
					$html_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.html';
					$css_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.css';
					$css_precompiled_path = $_GESTOR['ROOT_PATH'].'/resources/'.$lang.'/pages/'.$id.'/'.$id.'.precompiled.css';
				}

				$html = (file_exists($html_path)) ? file_get_contents($html_path) : (existe($paginas[0]['html']) ? $paginas[0]['html'] : '');
				$css = (file_exists($css_path)) ? file_get_contents($css_path) : (existe($paginas[0]['css']) ? $paginas[0]['css'] : '');
				$css_precompiled = (file_exists($css_precompiled_path)) ? file_get_contents($css_precompiled_path) : ($paginas[0]['css_precompiled'] ?? '');
			} else {
				$html = $paginas[0]['html'];
				$css = $paginas[0]['css'];
				$css_precompiled = $paginas[0]['css_precompiled'] ?? '';
			}

			// Live Editor (BATCH-085): backup restaurado tem precedência (cobre dev-env, que lê do arquivo).
			if(isset($_GESTOR['site-toolbar-backup-page-html'])){
				$html = $_GESTOR['site-toolbar-backup-page-html'];
			}

			$html_extra_head = $paginas[0]['html_extra_head'];
			$css_compiled = $paginas[0]['css_compiled'];
			$framework_css = $paginas[0]['framework_css'];

			if(!$_GESTOR['opcao']) $_GESTOR['opcao'] = $paginas[0]['opcao'];
			
			// ===== Definir a página, título e framework CSS.
			
			$_GESTOR['pagina'] = $html;
			$_GESTOR['pagina#titulo'] = $nome;
			$_GESTOR['pagina#framework_css'] = $framework_css;
			$_GESTOR['pagina#id'] = $id;

			// req-086: bundle canônico do Tailwind declarado por PROJETO.
			//
			// O bundle tem DOIS lados: o build compila layout + página + dependências num CSS só
			// (`tailwind_bundle` no manifesto) e o runtime precisa saber que deve usar esse CSS
			// sozinho, descartando os sidecars (`tailwind-page-bundle`). Hoje o segundo lado é
			// ligado à mão dentro do PHP de cada módulo — o que só serve a quem é dono do módulo.
			//
			// Um projeto que sobrescreve a página de um módulo do núcleo com um layout próprio
			// precisa exatamente disso e não pode editar o núcleo: sem o flag, o CSS da página
			// entra DEPOIS do layout e utilities sem variante (`hidden`) passam por cima das
			// responsivas (`md:flex`) — o menu do cabeçalho desaparece no desktop, sem erro nenhum.
			//
			// Ligar o flag sem o bundle correspondente no build é o oposto e também quebra: os
			// sidecars seriam descartados sem ninguém no lugar deles. Por isso a lista é declarada
			// pelo projeto, ao lado do manifesto que a sustenta.
			if(!empty($_GESTOR['project-page-tailwind-bundles']) && is_array($_GESTOR['project-page-tailwind-bundles'])){
				if($id && in_array($id, $_GESTOR['project-page-tailwind-bundles'], true)){
					$_GESTOR['tailwind-page-bundle'] = true;
				}
			}

			// ===== req-110: metadados de compartilhamento social da página alimentam o OpenGraph
			//       montado pelo BATCH-109 (gestor_open_graph_dados). Valor vazio cai no fallback.

			$_GESTOR['pagina#og'] = gestor_pagina_og_do_registro($paginas[0]);

			// ===== Módulo alvo quando houver executar

			$module_path = '';

			if(existe($modulo)){
				if(existe($plugin)){
					$module_path = $_GESTOR['plugins-path'].$plugin.'/modules/'.$modulo.'/'.$modulo.'.php';
				} else {
					$module_path = $_GESTOR['modulos-path'].$modulo.'/'.$modulo.'.php';
				}
			} else if($_GESTOR['opcao']){
				$module_path = $_GESTOR['modulos-path'].'global.php';
			}

			if(is_file($module_path)){
				require_once($module_path);
			}

			// ===== Incluir componentes na página.

			gestor_componentes_incluir_pagina();
			
			// ===== Incluir um layout específico, ou padrão ou nenhum.
			
			if(isset($_GESTOR['layout'])){
				$layout = (isset($_GESTOR['layout']['html']) ? $_GESTOR['layout']['html'] : '');
				$layout_css = (isset($_GESTOR['layout']['css']) ? $_GESTOR['layout']['css'] : '');
				$layout_css_precompiled = (isset($_GESTOR['layout']['css_precompiled']) ? $_GESTOR['layout']['css_precompiled'] : '');
				$layout_css_compiled = (isset($_GESTOR['layout']['css_compiled']) ? $_GESTOR['layout']['css_compiled'] : '');
			} else if($paginas[0]['layout_id']){
				if($_GESTOR['paginaIframe']){
					$layouts = gestor_layout(Array(
						'id' => 'layout-iframes',
						'return_css' => true,
					));
				} else {
					$layouts = gestor_layout(Array(
						'id' => $paginas[0]['layout_id'],
						'return_css' => true,
					));
				}
				
				$layout = $layouts['html'];
				$layout_css = $layouts['css'];
				$layout_css_precompiled = $layouts['css_precompiled'] ?? '';
				$layout_css_compiled = $layouts['css_compiled'] ?? '';

				// Live Editor (BATCH-085): backup de LAYOUT restaurado tem precedência.
				if(isset($_GESTOR['site-toolbar-backup-layout-html'])){
					$layout = $_GESTOR['site-toolbar-backup-layout-html'];
				}

				$_GESTOR['layout#id'] = $paginas[0]['layout_id'];
			} else {
				$layout = '';
				$layout_css = '';
				$layout_css_precompiled = '';
				$layout_css_compiled = '';
			}
			
			// ===== Incluir os recursos da página e do layout.

			gestor_pagina_recursos_incluir(Array(
				'css' => $layout_css,
				'css_precompiled' => $layout_css_precompiled,
				'css_precompiled_role' => 'layout-precompiled',
				'css_compiled' => $layout_css_compiled,
			));

			gestor_pagina_recursos_incluir(Array(
				'css' => $css,
				'css_precompiled' => $css_precompiled,
				'css_precompiled_role' => 'page-precompiled',
				'css_compiled' => $css_compiled,
				'html_extra_head' => $html_extra_head,
			));
			
			// ===== Inclusão de variáveis de linguagem para o widget e detecção automática

			$widgetActive = (isset($_CONFIG['language']['widget-active']) && $_CONFIG['language']['widget-active'] ? true : false);
			$autoDetect = (isset($_CONFIG['language']['auto-detect']) && $_CONFIG['language']['auto-detect'] ? true : false);

			if($widgetActive || $autoDetect){
				gestor_incluir_biblioteca('variaveis');
				
				$languages = Array();
				foreach($_GESTOR['languages'] as $lang){
					$label = gestor_variaveis(Array('id' => 'language-label-' . $lang));
					$languages[] = Array(
						'codigo' => $lang,
						'nome' => ($label ? $label : $lang),
					);
				}

				if(!isset($_GESTOR['javascript-vars'])){
					$_GESTOR['javascript-vars'] = Array();
				}

				$_GESTOR['javascript-vars']['languages'] = Array(
					'codigos' => $languages,
					'widgetActive' => $widgetActive,
					'autoDetect' => $autoDetect
				);
			}
			
			// ===== Aplicar o layout à página

			gestor_pagina_layout(Array(
				'layout' => $layout,
			));

			// ===== Incluir widgets na página e/ou layout.

			gestor_pagina_widgets();

			// ===== Dashboard Site Toolbar (injeção só para editores logados em páginas públicas).
			//       Antes de gestor_pagina_extra_head_e_javascript() para o JS auxiliar ser incluído.

			gestor_dashboard_toolbar(Array('caminho' => $caminho));

			// ===== Motor de exibição PDF.js (req-096): assets incluídos só se a página usa o leitor.

			gestor_pagina_pdf_viewer();

			// ===== CSS de conteúdo do Quill (req-141): só quando a página publica esse conteúdo.

			gestor_pagina_quill();

			// ===== Inclusão de bibliotecas globais de uma página

			gestor_pagina_css();
			gestor_pagina_extra_head_e_javascript();

			// ===== Inclusão de variáveis globais de uma página
			
			gestor_pagina_variaveis();

			// ===== Ultimas operações para a página.

			gestor_pagina_ultimas_operacoes();
			
			// ===== Retornar a página formatada para o cliente
			
			header("Content-Type: text/html; charset: UTF-8");
			echo $_GESTOR['pagina'];
			exit;
		}
	} else {
		// ===== Caso não exista a página, gerar erro 404.
		if($_GESTOR['ajax']){
			gestor_roteador_erro(Array(
				'codigo' => 404,
				'ajax' => $_GESTOR['ajax'],
			));
		} else {
			gestor_roteador_301_ou_404(Array(
				'caminho' => $caminho,
			));
		}
	}
}

/** Rejeita traversal e separadores ambíguos antes de qualquer concatenação de caminho. */
function gestor_caminho_publico_valido($caminho){
	$caminho = (string)$caminho;

	for($i = 0; $i < 3; $i++){
		$decodificado = rawurldecode($caminho);
		if($decodificado === $caminho) break;
		$caminho = $decodificado;
	}

	return strpos($caminho, '..') === false
		&& strpos($caminho, "\0") === false
		&& strpos($caminho, '\\') === false;
}

/** Emite cabeçalhos defensivos para todas as respostas do bootstrap web. */
function gestor_cabecalhos_seguranca(){
	global $_CONFIG;

	if(headers_sent()) return;

	header('X-Content-Type-Options: nosniff');
	header('Referrer-Policy: strict-origin-when-cross-origin');

	$xFrame = trim((string)($_CONFIG['security']['x-frame-options'] ?? 'SAMEORIGIN'));
	if($xFrame !== '' && strcasecmp($xFrame, 'OFF') !== 0) header('X-Frame-Options: '.$xFrame);

	$https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off');
	if($https) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

	$csp = trim((string)($_CONFIG['security']['csp'] ?? ''));
	if($csp !== ''){
		$nome = !empty($_CONFIG['security']['csp-report-only'])
			? 'Content-Security-Policy-Report-Only'
			: 'Content-Security-Policy';
		header($nome.': '.$csp);
	}
}

function gestor_config(){
	global $_GESTOR;
	global $_CONFIG;
	
	// =========================== Definição do Caminho da Página

	if(isset($_REQUEST['_gestor-caminho'])){
		if(!gestor_caminho_publico_valido($_REQUEST['_gestor-caminho'])){
			http_response_code(400);
			exit;
		}

		$_GESTOR['caminho-total'] = (string)$_REQUEST['_gestor-caminho'];
		$_GESTOR['caminho'] = explode('/',strtolower($_GESTOR['caminho-total']));

		// Verificar se o primeiro segmento é uma linguagem válida, se sim definir a linguagem e remover do array de caminho.
		if(!empty($_GESTOR['caminho'][0]) && in_array($_GESTOR['caminho'][0], $_GESTOR['languages'])){
			$_GESTOR['linguagem-codigo'] = $_GESTOR['caminho'][0];
			array_shift($_GESTOR['caminho']);
			$_GESTOR['caminho-total'] = substr($_GESTOR['caminho-total'], strlen($_GESTOR['linguagem-codigo']) + 1);
			$_GESTOR['language-in-url'] = true;

			// Ajustar URL Raiz para incluir a linguagem
			$_GESTOR['url-raiz'] = $_GESTOR['url-raiz'] . $_GESTOR['linguagem-codigo'].'/';
			$_GESTOR['url-full'] =	$_GESTOR['url-full'] . $_GESTOR['linguagem-codigo'].'/';
			$_GESTOR['url-full-http'] =	$_GESTOR['url-full-http'] . $_GESTOR['linguagem-codigo'].'/';
		}
		
		// Remover último segmento caso seja nulo (barra no final da URL)
		if($_GESTOR['caminho'][count($_GESTOR['caminho'])-1] == NULL){
			array_pop($_GESTOR['caminho']);
		}
		
		$_GESTOR['caminho-extensao'] = pathinfo($_GESTOR['caminho-total'], PATHINFO_EXTENSION);
	}

	// Se não tem linguagem na URL verifique o cookie '$_CONFIG['cookie-language']' e defina $_GESTOR['linguagem-codigo'] se válido
	if(isset($_COOKIE[$_CONFIG['cookie-language']]) && !isset($_GESTOR['language-in-url'])){
		$cookieLang = $_COOKIE[$_CONFIG['cookie-language']];
		if(in_array($cookieLang, $_GESTOR['languages'])){
			$_GESTOR['linguagem-codigo'] = $cookieLang;
		}
	}

	// =========================== Retornar arquivo estático caso exista e finalizar gestor

	$_GESTOR['arquivo-estatico'] = false;

	if((isset($_GESTOR['caminho-extensao']) ? $_GESTOR['caminho-extensao'] : null)){
		$_GESTOR['arquivo-estatico'] = Array(
			'alvo' => (isset($_GESTOR['caminho'][0]) ? $_GESTOR['caminho'][0] : null),
			'alvo2' => (isset($_GESTOR['caminho'][1]) ? $_GESTOR['caminho'][1] : null),
			'ext' => $_GESTOR['caminho-extensao'],
		);
	}

	if($_GESTOR['arquivo-estatico']){
		require_once($_GESTOR['controladores-path'].'arquivo-estatico/arquivo-estatico.php');
		exit;
	}

	// =========================== Controladores

	if(isset($_GESTOR['caminho']) && isset($_GESTOR['caminho'][0]))
	switch($_GESTOR['caminho'][0]){
		case '_gateways':
			require_once($_GESTOR['controladores-path'].'plataforma-gateways/plataforma-gateways.php'); exit;
		break;
		case '_api':
			require_once($_GESTOR['controladores-path'].'api/api.php'); exit;
		break;
	}
}


/**
 * Resposta de token CSRF ausente/inválido (req-107, aprimorada no req-109 / BATCH-109).
 *
 * Clientes AJAX continuam recebendo o mesmo JSON de antes (contrato preservado). O que muda é a
 * NAVEGAÇÃO normal: um POST de formulário que falha na validação fazia o navegador exibir o JSON
 * cru em tela cheia — o usuário do Editor Visual via `{"status":"error","message":"Token CSRF
 * inválido ou ausente."}` no lugar da página e concluía que perdera o trabalho. Agora recebe uma
 * página explicando o que houve, com botão de voltar.
 *
 * req-125 F1: o botão fazia `history.back()` puro, e no login isso é um LAÇO. Voltar pelo histórico
 * restaura o formulário do bfcache com o mesmo token expirado dentro do campo oculto; entrar de novo
 * falha de novo, indefinidamente, sem nenhuma pista para o usuário. Quando a tela de origem é uma
 * rota de identidade, o botão passa a NAVEGAR para ela (`location.replace`, que também tira a tela de
 * erro do histórico), forçando um GET novo — sessão limpa e token novo. `history.back()` continua
 * sendo o comportamento para o resto do gestor, onde preservar o que foi digitado é o que importa.
 *
 * @return void Encerra a requisição.
 */
function gestor_csrf_resposta_invalida(){
	global $_GESTOR;

	http_response_code(403);

	$mensagem = 'Token CSRF inválido ou ausente.';

	$aceita = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
	$requisicaoAjax = !empty($_REQUEST['ajax'])
		|| strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
		|| (strpos($aceita, 'application/json') !== false && strpos($aceita, 'text/html') === false);

	if($requisicaoAjax){
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode(Array('status' => 'error', 'message' => $mensagem), JSON_UNESCAPED_UNICODE);
		exit;
	}

	$ingles = strpos(strtolower((string)($_GESTOR['linguagem-codigo'] ?? 'pt-br')), 'en') === 0;

	$titulo = $ingles ? 'Session expired' : 'Sessão expirada';
	$texto = $ingles
		? 'Your security token expired or was not sent. Go back, reload the page and try again — your data was not saved.'
		: 'Seu token de segurança expirou ou não foi enviado. Volte, recarregue a página e tente novamente — nada foi salvo.';
	$voltar = $ingles ? 'Go back' : 'Voltar';

	// req-125 F1: a própria tela de erro não pode entrar no bfcache. Sem isto, voltar PARA ela (por
	// exemplo depois de um `location.replace` seguido de um back) devolveria a página estática com o
	// mesmo botão apontando para um estado já vencido.
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	header('Content-Type: text/html; charset=UTF-8');

	$destino = gestor_csrf_destino_recarregamento(
		(isset($_GESTOR['caminho-total']) ? $_GESTOR['caminho-total'] : ''),
		(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''),
		(isset($_GESTOR['url-raiz']) ? $_GESTOR['url-raiz'] : '/')
	);

	// O destino resolvido é escrito como DADO (`data-c2f-destino`), não interpolado no meio do
	// JavaScript: assim o escape do atributo é a única barreira necessária, e o onclick continua
	// sendo uma string constante, auditável e idêntica em toda resposta.
	$acao = "var d=this.getAttribute('data-c2f-destino');"
		.'if(d){window.location.replace(d);}'
		.'else if(window.history.length > 1){window.history.back();}'
		.'else{window.location.reload();}';

	echo '<!DOCTYPE html>'."\n"
		.'<html lang="'.htmlspecialchars((string)($_GESTOR['linguagem-codigo'] ?? 'pt-br'), ENT_QUOTES, 'UTF-8').'">'."\n"
		.'<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
		.'<meta name="robots" content="noindex"><title>'.htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8').'</title>'
		.'<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;'
		.'font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f6f7f9;color:#1b1c1d}'
		.'.c2f-box{max-width:520px;padding:32px;background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.12);text-align:center}'
		.'h1{font-size:20px;margin:0 0 12px}p{margin:0 0 24px;line-height:1.5;color:#5b5f66}'
		.'button{padding:10px 22px;border:0;border-radius:6px;background:#2185d0;color:#fff;font-size:15px;cursor:pointer}'
		.'</style></head>'."\n"
		.'<body><div class="c2f-box"><h1>'.htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8').'</h1>'
		.'<p>'.htmlspecialchars($texto, ENT_QUOTES, 'UTF-8').'</p>'
		.'<button type="button" data-c2f-destino="'.htmlspecialchars($destino, ENT_QUOTES, 'UTF-8').'"'
		.' onclick="'.htmlspecialchars($acao, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($voltar, ENT_QUOTES, 'UTF-8').'</button>'
		.'</div></body></html>';

	exit;
}

function gestor_start(){
	gestor_cabecalhos_seguranca();
	gestor_config();
	gestor_sessao_iniciar();
	gestor_incluir_biblioteca('seguranca');
	if(!seguranca_csrf_requisicao_validar()){
		gestor_csrf_resposta_invalida();
	}
	gestor_roteador();
}

// =========================== Inciar Gestor 

gestor_start();

?>
