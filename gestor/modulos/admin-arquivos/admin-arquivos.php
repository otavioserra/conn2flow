<?php

global $_GESTOR;

$_GESTOR['modulo-id']							=	'admin-arquivos';
$_GESTOR['modulo#'.$_GESTOR['modulo-id']] = json_decode(file_get_contents(__DIR__ . '/admin-arquivos.json'), true);

// ===== Interfaces Auxiliares

function admin_arquivos_criar_dir_herdando_permissao($dir) {
	if (!is_dir($dir)) {
		$pai = dirname($dir);
		$permissao_pai = is_dir($pai) ? fileperms($pai) & 0777 : 0755;
		mkdir($dir, $permissao_pai, true);
	}
}

function admin_arquivos_lista($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// pagina - String - Obrigatório - Página onde será implementada lista de arquivos.
	
	// ===== 
	
	// ===== Se houver filtros, aplicar os mesmos
	
	$filtros = Array();
	
	if(isset($_REQUEST['filtros'])){
		gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'#'.'filtros',banco_escape_field($_REQUEST['filtros']));
	}
	
	if(existe(gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'#'.'filtros'))){
		$filtros = json_decode(stripslashes(gestor_sessao_variavel($_GESTOR['modulo'].'-'.$_GESTOR['opcao'].'#'.'filtros')),true);
	}
	
	// ===== Variáveis Padrões
	
	$max_dados_por_pagina = 100;
	$total = 0;
	$paginaAtual = 0;
	$totalPaginas = 0;
	
	// ===== Total Arquivos Sem Filtro
	
	$totalArquivosBanco = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_arquivos',
		))
		,
		"arquivos",
		"WHERE status!='D'"
	);
	
	if(!isset($totalArquivosBanco)){
		$totalArquivosBanco = Array();
	}
	
	$totalArquivosSemFiltrar = count($totalArquivosBanco);
	
	// ===== Filtrar ou não por categoria
	
	$whereCategorias = '';
	if(isset($filtros['categorias'])){
		// ===== Filtros do Banco
		
		if(isset($filtros['dataDe'])){
			$orgDate = $filtros['dataDe'];  
			$dataDe = date("Y-m-d 00:00:00", strtotime($orgDate));  
		}
		
		if(isset($filtros['dataAte'])){
			$orgDate = $filtros['dataAte'];  
			$dataAte = date("Y-m-t 23:59:59", strtotime($orgDate));  
		}
		
		$whereArquivos = " WHERE t1.status!='D'"
		." AND t1.id_arquivos=t2.id_arquivos"
		.(isset($dataDe) ? " AND t1.data_criacao >= '".$dataDe."'" : "")
		.(isset($dataAte) ? " AND t1.data_criacao <= '".$dataAte."'" : "")
		;
		
		$semCategoriaDesc = true;
		if(isset($filtros['order'])){
			switch($filtros['order']){
				case 'alphabetical-asc': $orderArquivos = " ORDER BY t1.nome ASC"; break;
				case 'alphabetical-desc': $orderArquivos = " ORDER BY t1.nome DESC"; $semCategoriaDesc = false; break;
				case 'order-date-asc': $orderArquivos = " ORDER BY t1.data_criacao ASC"; break;
				case 'order-date-desc': $orderArquivos = " ORDER BY t1.data_criacao DESC"; break;
				default:
					$orderArquivos = " ORDER BY t1.nome ASC"; 
			}
		} else {
			$orderArquivos = " ORDER BY t1.nome ASC"; 
		}
		
		$categoriasArr = $filtros['categorias'];
		
		if($categoriasArr){
			$whereCategorias = ' AND (';
			$count = 0;
			foreach($categoriasArr as $cat){
				$whereCategorias .= ($count > 0 ? ' OR ':'')."t2.id_categorias='".$cat."'";
				$count++;
			}
			
			$whereCategorias .= ')';
		}
		
		// ===== Verificar o total de registros.
		
		$pre_arquivos = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.id_arquivos',
			))
			,
			"arquivos as t1,arquivos_categorias as t2",
			$whereArquivos
			.$whereCategorias
			." GROUP BY t1.id_arquivos"
		);
		
		if(!isset($pre_arquivos)){
			$pre_arquivos = Array();
		}
		
		$total = count($pre_arquivos);
		$totalPaginas = ($total % $max_dados_por_pagina > 0 ? 1 : 0) + floor($total / $max_dados_por_pagina);
		
		// ===== Página atual
		
		if($_GESTOR['ajax']){
			if(isset($_REQUEST['pagina'])){
				$paginaAtual = (int)banco_escape_field($_REQUEST['pagina']);
			}
		} else {
			$_GESTOR['javascript-vars']['arquivos'] = Array(
				'totalArquivosSemFiltrar' => $totalArquivosSemFiltrar,
				'total' => $total,
				'totalPaginas' => $totalPaginas,
			);
			
			if(isset($filtros['dataDe'])) $_GESTOR['javascript-vars']['arquivos']['dataDe'] = $filtros['dataDe'];
			if(isset($filtros['dataAte'])) $_GESTOR['javascript-vars']['arquivos']['dataAte'] = $filtros['dataAte'];
			if(isset($filtros['order'])) $_GESTOR['javascript-vars']['arquivos']['order'] = $filtros['order'];
			if(isset($filtros['categorias'])) $_GESTOR['javascript-vars']['arquivos']['categorias'] = $filtros['categorias'];
		}
		
		// ===== Verificar arquivos banco
		
		$arquivos = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.id_arquivos',
				't1.nome',
				't1.id',
				't1.tipo',
				't1.caminho',
				't1.caminho_mini',
				't1.data_criacao',
			))
			,
			"arquivos as t1,arquivos_categorias as t2",
			$whereArquivos
			.$whereCategorias
			." GROUP BY t1.id_arquivos"
			.$orderArquivos
			." LIMIT ".($max_dados_por_pagina * $paginaAtual).",".$max_dados_por_pagina
		);
	} else {
		// ===== Filtros do Banco
		
		if(isset($filtros['dataDe'])){
			$orgDate = $filtros['dataDe'];  
			$dataDe = date("Y-m-d 00:00:00", strtotime($orgDate));  
		}
		
		if(isset($filtros['dataAte'])){
			$orgDate = $filtros['dataAte'];  
			$dataAte = date("Y-m-t 23:59:59", strtotime($orgDate));  
		}
		
		$whereArquivos = " WHERE status!='D'"
		.(isset($dataDe) ? " AND data_criacao >= '".$dataDe."'" : "")
		.(isset($dataAte) ? " AND data_criacao <= '".$dataAte."'" : "")
		;
		
		$semCategoriaDesc = true;
		if(isset($filtros['order'])){
			switch($filtros['order']){
				case 'alphabetical-asc': $orderArquivos = " ORDER BY nome ASC"; break;
				case 'alphabetical-desc': $orderArquivos = " ORDER BY nome DESC"; $semCategoriaDesc = false; break;
				case 'order-date-asc': $orderArquivos = " ORDER BY data_criacao ASC"; break;
				case 'order-date-desc': $orderArquivos = " ORDER BY data_criacao DESC"; break;
				default:
					$orderArquivos = " ORDER BY nome ASC"; 
			}
		} else {
			$orderArquivos = " ORDER BY nome ASC";
		}
		
		// ===== Verificar o total de registros.
		
		$pre_arquivos = banco_select(Array(
			'tabela' => 'arquivos',
			'campos' => Array(
				'id_arquivos',
			),
			'extra' => 
				$whereArquivos
		));
		
		if(!isset($pre_arquivos)){
			$pre_arquivos = Array();
		}
		
		$total = count($pre_arquivos);
		$totalPaginas = ($total % $max_dados_por_pagina > 0 ? 1 : 0) + floor($total / $max_dados_por_pagina);
		
		// ===== Página atual
		
		if($_GESTOR['ajax']){
			if(isset($_REQUEST['pagina'])){
				$paginaAtual = (int)banco_escape_field($_REQUEST['pagina']);
			}
		} else {
			$_GESTOR['javascript-vars']['arquivos'] = Array(
				'totalArquivosSemFiltrar' => $totalArquivosSemFiltrar,
				'total' => $total,
				'totalPaginas' => $totalPaginas,
			);
			
			if(isset($filtros['dataDe'])) $_GESTOR['javascript-vars']['arquivos']['dataDe'] = $filtros['dataDe'];
			if(isset($filtros['dataAte'])) $_GESTOR['javascript-vars']['arquivos']['dataAte'] = $filtros['dataAte'];
			if(isset($filtros['order'])) $_GESTOR['javascript-vars']['arquivos']['order'] = $filtros['order'];
		}
		
		// ===== Verificar arquivos banco
		
		$arquivos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_arquivos',
				'nome',
				'id',
				'tipo',
				'caminho',
				'caminho_mini',
				'data_criacao',
			))
			,
			"arquivos",
			$whereArquivos
			.$orderArquivos
			." LIMIT ".($max_dados_por_pagina * $paginaAtual).",".$max_dados_por_pagina
		);
	}
	
	if($arquivos){
		// ===== Caso haja arquivos, iniciar variáveis.
		
		$layout = gestor_componente(Array(
			'id' => 'arquivos-lista',
		));
		
		// ===== Trocar os controles caso seja página normal ou iframe
		
		if($_GESTOR['paginaIframe']){
			$cel_nome = 'btn-copy'; $layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		} else {
			$cel_nome = 'btn-select'; $layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		}
		
		// ===== Célula do card
		
		$cel_nome = 'card'; $cel[$cel_nome] = modelo_tag_val($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $layout = modelo_tag_in($layout,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
		
		// ===== Varrer todos os registros do arquivo
		
		foreach($arquivos as $item){
			// ===== Iniciar variáveis de cada registro.
			
			if(isset($filtros['categorias'])){
				$id_arquivos = $item['t1.id_arquivos'];
				$nome = $item['t1.nome'];
				$tipo = $item['t1.tipo'];
				$caminho = $item['t1.caminho'];
				$caminho_mini = $item['t1.caminho_mini'];
				$data_criacao = $item['t1.data_criacao'];
			} else {
				$id_arquivos = $item['id_arquivos'];
				$nome = $item['nome'];
				$tipo = $item['tipo'];
				$caminho = $item['caminho'];
				$caminho_mini = $item['caminho_mini'];
				$data_criacao = $item['data_criacao'];
			}
			
			$url = $_GESTOR['url-full'] . $caminho;
			
			// ===== Imagem Mini ou Imagem Referência
			
			if($caminho_mini){
				$imgSrc = $_GESTOR['url-full'] . $caminho_mini;
			} else {
				if(preg_match('/'.preg_quote('image').'\//i', $tipo) > 0){
					$imgSrc = $_GESTOR['url-full'] . 'images/imagem-padrao.png';
				} else if(preg_match('/'.preg_quote('video').'\//i', $tipo) > 0){
					$imgSrc = $_GESTOR['url-full'] . 'images/video-padrao.png';
				} else if(preg_match('/'.preg_quote('audio').'\//i', $tipo) > 0){
					$imgSrc = $_GESTOR['url-full'] . 'images/audio-padrao.png';
				} else {
					$imgSrc = $_GESTOR['url-full'] . 'images/file-padrao.png';
				}
			}
			
			$data = interface_formatar_dado(Array('dado' => $data_criacao, 'formato' => 'dataHora'));
			
			// ===== Popular célula do arquivo
			
			$cel_aux = $cel[$cel_nome];
			
			$cel_aux = modelo_var_troca_tudo($cel_aux,"#file-id#",$id_arquivos);
			$cel_aux = modelo_var_troca($cel_aux,"#img-src#",$imgSrc);
			$cel_aux = modelo_var_troca($cel_aux,"#nome#",$nome);
			$cel_aux = modelo_var_troca($cel_aux,"#data#",$data);
			$cel_aux = modelo_var_troca($cel_aux,"#tipo#",$tipo);
			
			if($_GESTOR['paginaIframe']){
				$cel_aux = modelo_var_troca($cel_aux,"#file-data#",htmlentities(json_encode(Array(
					'id' => $id_arquivos,
					'imgSrc' => $imgSrc,
					'nome' => $nome,
					'data' => $data,
					'tipo' => $tipo,
				)), ENT_QUOTES, 'UTF-8'));
			} else {	
				$cel_aux = modelo_var_troca($cel_aux,"#file-url#",$url);
			}
			
			// ===== Verificar a(s) categoria(s) do arquivo e criar layout caso não exista e vincular o arquivo em cada categoria.
			
			$arquivos_categorias = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_categorias',
				))
				,
				"arquivos_categorias",
				"WHERE id_arquivos='".$id_arquivos."'"
			);
			
			if(isset($filtros['categorias'])){
				if($arquivos_categorias){
					foreach($arquivos_categorias as $ac){
						foreach($categoriasArr as $cat){
							if($cat == $ac['id_categorias']){
								if(!isset($layout_categorias[$ac['id_categorias']])){
									$arquivos_categorias = banco_select_name
									(
										banco_campos_virgulas(Array(
											'nome',
										))
										,
										"categorias",
										"WHERE id_categorias='".$ac['id_categorias']."'"
									);
									
									$layout_aux = $layout;
									
									$layout_aux = modelo_var_troca($layout_aux,"#categoria#",$arquivos_categorias[0]['nome']);
									$layout_aux = modelo_var_troca($layout_aux,"#id#",'categoria-'.$ac['id_categorias']);
								} else {
									$layout_aux = $layout_categorias[$ac['id_categorias']];
								}
								
								$layout_categorias[$ac['id_categorias']] = modelo_var_in($layout_aux,'<!-- '.$cel_nome.' -->',$cel_aux);
							}
						}
					}
				}
			} else {
				if($arquivos_categorias){
					foreach($arquivos_categorias as $ac){
						if(!isset($layout_categorias[$ac['id_categorias']])){
							$arquivos_categorias = banco_select_name
							(
								banco_campos_virgulas(Array(
									'nome',
								))
								,
								"categorias",
								"WHERE id_categorias='".$ac['id_categorias']."'"
							);
							
							$layout_aux = $layout;
							
							$layout_aux = modelo_var_troca($layout_aux,"#categoria#",$arquivos_categorias[0]['nome']);
							$layout_aux = modelo_var_troca($layout_aux,"#id#",'categoria-'.$ac['id_categorias']);
						} else {
							$layout_aux = $layout_categorias[$ac['id_categorias']];
						}
						
						$layout_categorias[$ac['id_categorias']] = modelo_var_in($layout_aux,'<!-- '.$cel_nome.' -->',$cel_aux);
					}
				} else {
					if(!isset($layout_categorias['0'])){
						$layout_aux = $layout;
						
						$layout_aux = modelo_var_troca($layout_aux,"#categoria#",gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'list-not-categorised')));
						$layout_aux = modelo_var_troca($layout_aux,"#id#",'categoria-0');
					} else {
						$layout_aux = $layout_categorias['0'];
					}
					
					$layout_categorias['0'] = modelo_var_in($layout_aux,'<!-- '.$cel_nome.' -->',$cel_aux);
				}
			}
		}
		
		if(isset($layout_categorias)){
			$layouts = '';
			$layoutSemCategoria = '';
			
			foreach($layout_categorias as $key => $lc){
				if($key == '0'){
					$layoutSemCategoria = $lc;
				} else {
					$layouts .= $lc;
				}
			}
			
			if($semCategoriaDesc){					
				$layouts .= $layoutSemCategoria;
			} else {
				$layouts = $layoutSemCategoria . $layouts;
			}
			
			$layouts = modelo_var_troca($layouts,'<!-- '.$cel_nome.' -->','');
		}
		
		// ===== finalizar e incluir todos os registros no layout.
	
		if(!$_GESTOR['ajax']){
			$pagina = modelo_var_troca($pagina,"#arquivos-lista#",(isset($layouts) ? $layouts : ''));
		} else {
			$pagina = gestor_pagina_variaveis_globais(Array('html' => $layouts));
		}
	}
	
	if(!$_GESTOR['ajax']){
		return $pagina;
	} else {
		return Array(
			'pagina' => $pagina,
			'totalArquivosSemFiltrar' => $totalArquivosSemFiltrar,
			'total' => $total,
			'totalPaginas' => $totalPaginas,
		);
	}
}

function admin_arquivos_lista_mais_resultados(){
	global $_GESTOR;
	
	$retorno = admin_arquivos_lista(Array(
		'pagina' => '#arquivos-lista#',
	));
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'pagina' => $retorno['pagina'],
		'totalArquivosSemFiltrar' => $retorno['totalArquivosSemFiltrar'],
		'total' => $retorno['total'],
		'totalPaginas' => $retorno['totalPaginas'],
	);
}

// ===== Interfaces Principais

function admin_arquivos_upload(){
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
		$campo_nome = "id"; $campo_valor = $id; 										$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		$campo_nome = "campo"; $post_nome = $campo_nome; 								if($_REQUEST[$post_nome])		$campos[] = Array($campo_nome,banco_escape_field($_REQUEST[$post_nome]));
		
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
	
	// ===== Inclusão Módulo CSS
	
	gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'jQuery-File-Upload-10.32.0/css/jquery.fileupload.css">');
	//gestor_pagina_css_incluir('<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'jQuery-File-Upload-10.32.0/css/jquery.fileupload-ui.css">');
	
	// ===== Inclusão Módulo JS
	
	// ===== The jQuery UI widget factory, can be omitted if jQuery UI is already included
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'jQuery-File-Upload-10.32.0/js/vendor/jquery.ui.widget.js"></script>');
	// ===== The Iframe Transport is required for browsers without support for XHR file uploads
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'jQuery-File-Upload-10.32.0/js/jquery.iframe-transport.js"></script>');
	// ===== The basic File Upload plugin
	gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'jQuery-File-Upload-10.32.0/js/jquery.fileupload.js"></script>');
	// ===== The File Upload processing plugin
	//gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'jQuery-File-Upload-10.32.0/js/jquery.fileupload-process.js"></script>');
	// ===== The File Upload image preview & resize plugin
	//gestor_pagina_javascript_incluir('<script src="'.$_GESTOR['url-raiz'].'jQuery-File-Upload-10.32.0/js/jquery.fileupload-image.js"></script>');
	
	gestor_pagina_javascript_incluir();
	
	// =====
	
	$cel_nome = 'files-cel'; $cel[$cel_nome] = modelo_tag_val($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->'); $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
	
	$filesCel = $cel[$cel_nome];
	
	if($_GESTOR['paginaIframe']){
		$cel_nome = 'btn-copy'; $filesCel = modelo_tag_in($filesCel,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#url#",$_GESTOR['url-full'] . 'admin-arquivos/?paginaIframe=sim');
	} else {
		$cel_nome = 'btn-select'; $filesCel = modelo_tag_in($filesCel,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		$cel_nome = 'botao-voltar'; $_GESTOR['pagina'] = modelo_tag_in($_GESTOR['pagina'],'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
	}
	
	$_GESTOR['javascript-vars']['arquivosCel'] = gestor_pagina_variaveis_globais(Array('html'=>$filesCel));
	$_GESTOR['javascript-vars']['arquivosConcluido'] = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'add-file-progress-finish'));
	$_GESTOR['javascript-vars']['arquivosProcessando'] = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'add-file-progress-processing'));
	$_GESTOR['javascript-vars']['arquivosErro'] = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'add-file-progress-error'));
	
	// ===== Interface finalizar opções
	
	interface_formulario_campos(Array(
		'campos' => Array(
			Array(
				'tipo' => 'select',
				'id' => 'categories',
				'nome' => 'categorias',
				'procurar' => true,
				'limpar' => true,
				'multiple' => true,
				'fluid' => true,
				'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'add-categories-placeholder')),
				'tabela' => Array(
					'nome' => 'categorias',
					'campo' => 'nome',
					'id_numerico' => 'id_categorias',
					'where' => "id_modulos='11' OR id_modulos IS NULL",
				),
			)
		)
	));
}

function admin_arquivos_listar(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Inclusão Interface
	
	$_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="'.$_GESTOR['url-raiz'].'interface/interface.css?v='.$_GESTOR['biblioteca-interface']['versao'].'" />';
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>';
	
	// ===== Interface Javascript Vars
	
	if(!isset($_GESTOR['javascript-vars']['interface'])){
		$_GESTOR['javascript-vars']['interface'] = Array();
	}
	
	// ===== Incluir componentes
	
	interface_componentes_incluir(Array(
		'componente' => Array(
			'modal-carregamento',
			'modal-delecao',
			'modal-alerta',
		)
	));
	
	// ===== Incluir javascript
	
	gestor_pagina_javascript_incluir();
	
	// ===== Interface finalizar opções
	
	$_GESTOR['pagina'] = admin_arquivos_lista(Array(
		'pagina' => $_GESTOR['pagina'],
	));
	
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#without-results-cont#",gestor_componente(Array(
		'id' => 'interface-listar-sem-registros',
	)));
	
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#without-files-cont#",gestor_componente(Array(
		'id' => 'interface-listar-arquivos-sem-registros',
	)));
	
	$_GESTOR['pagina'] = modelo_var_troca_tudo($_GESTOR['pagina'],"#paginaIframe#",($_GESTOR['paginaIframe'] ? '?paginaIframe=sim' : ''));
	
	// ===== Interface finalizar opções
	
	interface_formulario_campos(Array(
		'campos' => Array(
			Array(
				'tipo' => 'select',
				'id' => 'categories',
				'nome' => 'categorias',
				'procurar' => true,
				'limpar' => true,
				'multiple' => true,
				'fluid' => true,
				'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'categories-placeholder')),
				'tabela' => Array(
					'nome' => 'categorias',
					'campo' => 'nome',
					'id_numerico' => 'id_categorias',
					'where' => "id_modulos='11' OR id_modulos IS NULL",
				),
			),
			Array(
				'tipo' => 'select',
				'id' => 'order',
				'nome' => 'ordenar',
				'menu' => true,
				'procurar' => true,
				'fluid' => true,
				'valor_selecionado' => 'alphabetical-asc',
				'valor_selecionado_texto' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'order-alphabetical-asc')),
				'valor_selecionado_icone' => 'sort alphabet down',
				'placeholder' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'order-placeholder')),
				'dados' => Array(
					Array(
						'texto' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'order-alphabetical-asc')),
						'valor' => 'alphabetical-asc',
						'icone' => 'sort alphabet down',
					),
					Array(
						'texto' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'order-alphabetical-desc')),
						'valor' => 'alphabetical-desc',
						'icone' => 'sort alphabet up alternate',
					),
					Array(
						'texto' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'order-date-asc')),
						'valor' => 'order-date-asc',
						'icone' => 'sort amount down alternate',
					),
					Array(
						'texto' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'order-date-desc')),
						'valor' => 'order-date-desc',
						'icone' => 'sort amount up',
					),
				),
			)
		)
	));
}

function admin_arquivos_interfaces_padroes(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	switch($_GESTOR['opcao']){
		case 'listar':
			
		break;
	}
}

// ==== Ajax

function admin_arquivos_ajax_upload_file(){
	global $_GESTOR;
	
	$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
	
	// ===== Preparar nome e extensão do arquivo
	
	$path_parts = pathinfo(basename($_FILES['files']['name'][0]));
	$extensao = $path_parts['extension'];
	
	$nome = preg_replace('/\.'.$extensao.'/i', '', $_FILES['files']['name'][0]);
	
	$id = banco_identificador(Array(
		'id' => $nome, // Valor cru
		'tabela' => Array(
			'nome' => $modulo['tabela']['nome'],
			'campo' => $modulo['tabela']['id'],
			'id_nome' => $modulo['tabela']['id_numerico'],
		),
	));
	
	$extensao = strtolower($extensao);
	$nome_extensao = $id . '.' . $extensao;
	
	// ===== Verificar se o tamanho do arquivo é permitido
	
	$size = $_FILES['files']['size'][0];
	
	if($size > 10000000){
		unlink($_FILES['files']['tmp_name'][0]);
		$_GESTOR['ajax-json'] = Array(
			'error' => gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => 'upload-error-size'))
		);
		return;
	}
	
	// ===== Diretório, caminho físico e URL do arquivo.
	
	$basedir = 'files';
	$thumbnail = 'mini';
	
	admin_arquivos_criar_dir_herdando_permissao($_GESTOR['contents-path'].$basedir);
	admin_arquivos_criar_dir_herdando_permissao($_GESTOR['contents-path'].$basedir.'/'.date('Y'));
	admin_arquivos_criar_dir_herdando_permissao($_GESTOR['contents-path'].$basedir.'/'.date('Y').'/'.date('m'));
	admin_arquivos_criar_dir_herdando_permissao($_GESTOR['contents-path'].$basedir.'/'.date('Y').'/'.date('m').'/'.$thumbnail);
	
	$caminho = $basedir.'/'.date('Y').'/'.date('m').'/';
	$caminho_mini = $basedir.'/'.date('Y').'/'.date('m').'/'.$thumbnail.'/';
	
	$caminho_arquivo = $_GESTOR['contents-path'].$caminho.$nome_extensao;
	$caminho_arquivo_mini = $_GESTOR['contents-path'].$caminho_mini.$nome_extensao;
	
	$url_arquivo = $caminho.$nome_extensao;
	$url_arquivo_mini = $caminho_mini.$nome_extensao;
	
	// ===== Mover arquivo para caminho definitivo.
	
	if(move_uploaded_file($_FILES['files']['tmp_name'][0], $caminho_arquivo)){
		$tipo = $_FILES['files']['type'][0];
		
		// ===== Criar referência do arquivo no banco de dados
		
		$campos = null;$campo_sem_aspas_simples = null;
		
		$campo_nome = "id_usuarios"; $campo_valor = $_GESTOR['usuario-id']; 	$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "nome"; $campo_valor = banco_escape_field($nome);			$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "id"; $campo_valor = $id; 								$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "tipo"; $campo_valor = $tipo; 							$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "caminho"; $campo_valor = $url_arquivo; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		$campo_nome = $modulo['tabela']['status']; $campo_valor = 'A'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['versao']; $campo_valor = '1'; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = $modulo['tabela']['data_criacao']; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = $modulo['tabela']['data_modificacao']; $campo_valor = 'NOW()'; 	$campos[] = Array($campo_nome,$campo_valor,true);
		
		// ===== Verificar o tipo do arquivo e criar imagem_mini
		
		$imagem = false;
		if(preg_match('/'.preg_quote('image').'\//i', $tipo) > 0){
			require_once $_GESTOR['bibliotecas-path'].'SimpleImage/src/claviska/SimpleImage.php';
			
			try {
				$image = new \claviska\SimpleImage();
				
				$image->fromFile($caminho_arquivo);
				
				if($image->getWidth() > $modulo['imagem']['mini_width']){
					$image->resize($modulo['imagem']['mini_width']);
				}
				
				$image->toFile($caminho_arquivo_mini);
				
				$imagem = true;
				
				$campo_nome = "caminho_mini"; $campo_valor = $url_arquivo_mini; 					$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
			} catch(Exception $err) {
				$warning_msg = $err->getMessage();
			}
		}
		
		banco_insert_name
		(
			$campos,
			"arquivos"
		);
		
		$id_arquivos = banco_last_id();
		
		// ===== Vincular categorias caso definido
		
		if(isset($_REQUEST['categorias'])){
			$categorias = explode(',',banco_escape_field($_REQUEST['categorias']));
			
			if($categorias)
			foreach($categorias as $categoria){
				$campos = null;
			
				$campo_nome = "id_arquivos"; $campo_valor = $id_arquivos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				$campo_nome = "id_categorias"; $campo_valor = $categoria; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
				
				banco_insert_name
				(
					$campos,
					"arquivos_categorias"
				);
			}
		}
		
		// ===== Imagem Mini ou Imagem Referência e Data
		
		if($imagem){
			$imgSrc = $_GESTOR['url-full'] . $url_arquivo_mini;
		} else {
			if(preg_match('/'.preg_quote('image').'\//i', $tipo) > 0){
				$imgSrc = $_GESTOR['url-full'] . 'images/imagem-padrao.png';
			} else if(preg_match('/'.preg_quote('video').'\//i', $tipo) > 0){
				$imgSrc = $_GESTOR['url-full'] . 'images/video-padrao.png';
			} else if(preg_match('/'.preg_quote('audio').'\//i', $tipo) > 0){
				$imgSrc = $_GESTOR['url-full'] . 'images/audio-padrao.png';
			} else {
				$imgSrc = $_GESTOR['url-full'] . 'images/file-padrao.png';
			}
		}
		
		$data = interface_formatar_dado(Array('dado' => date("Y-m-d H:i:s"), 'formato' => 'dataHora'));
		
		// ===== Dados de Retorno
		
		$_GESTOR['ajax-json'] = Array(
			'id' => $id_arquivos,
			'nome' => $nome,
			'data' => $data,
			'tipo' => $tipo,
			'imgSrc' => $imgSrc,
			'url' => $_GESTOR['url-full'] . $url_arquivo,
			'files' => $_FILES['files'],
			'status' => 'Ok',
		);
		
		if(isset($warning_msg)){ $_GESTOR['ajax-json']['warning_msg'] = $warning_msg; }
	} else {
		unlink($_FILES['files']['tmp_name'][0]);
		$_GESTOR['ajax-json'] = Array(
			'error' => 'Error - '.$_FILES['files']['error'][0]
		);
	}
}

function admin_arquivos_excluir_arquivo(){
	global $_GESTOR;
	
	if(isset($_REQUEST['id'])){
		$id = banco_escape_field($_REQUEST['id']);
		$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
		
		$arquivos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'caminho',
				'caminho_mini',
			))
			,
			$modulo['tabela']['nome'],
			"WHERE ".$modulo['tabela']['id_numerico']."='".$id."'"
		);
		
		if($arquivos){
			$banco = Array(
				'nome' => $modulo['tabela']['nome'],
				'id' => $modulo['tabela']['id_numerico'],
				'status' => $modulo['tabela']['status'],
			);
			
			$campo_tabela = $banco['nome'];
			$campo_tabela_extra = "WHERE ".$banco['id']."='".$id."'".(isset($banco['where']) ? " AND ".$banco['where'] : "" )." AND ".$banco['status']."!='D'";
			
			$campo_nome = $banco['status']; $editar[$campo_tabela][] = $campo_nome."='D'";
			
			$campo_nome = "versao"; $editar[$campo_tabela][] = $campo_nome." = ".$campo_nome." + 1";
			$campo_nome = "data_modificacao"; $editar[$campo_tabela][] = $campo_nome."=NOW()";
			
			$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
			
			// ===== Incluir no histórico as alterações.
			
			interface_historico_incluir(Array(
				'alteracoes' => Array(
					Array(
						'alteracao' => 'historic-delete',
					)
				),
				'deletar' => true,
				'id_numerico_manual' => $id,
			));
			
			// ===== Excluir arquivos no disco
			
			if(is_file($_GESTOR['contents-path'].$arquivos[0]['caminho'])){
				unlink($_GESTOR['contents-path'].$arquivos[0]['caminho']);
			}
			
			if($arquivos[0]['caminho_mini']){
				if(is_file($_GESTOR['contents-path'].$arquivos[0]['caminho_mini'])){
					unlink($_GESTOR['contents-path'].$arquivos[0]['caminho_mini']);
				}
			}
			
			// Executar deleção
			
			if($editar_sql[$campo_tabela]){
				banco_update
				(
					$editar_sql[$campo_tabela],
					$campo_tabela,
					$campo_tabela_extra
				);
			}
			$editar = false;$editar_sql = false;
			
			$_GESTOR['ajax-json'] = Array(
				'status' => 'Ok',
			);
		} else {
			$_GESTOR['ajax-json'] = Array(
				'status' => 'IdNotFounded',
			);
		}
	} else {
		$_GESTOR['ajax-json'] = Array(
			'status' => 'IdNotDefined',
		);
	}
}

// ==== Start

function admin_arquivos_start(){
	global $_GESTOR;
	
	gestor_incluir_bibliotecas();
	
	if($_GESTOR['ajax']){
		interface_ajax_iniciar();
		
		switch($_GESTOR['ajax-opcao']){
			case 'uploadFile': admin_arquivos_ajax_upload_file(); break;
			case 'lista-mais-resultados': admin_arquivos_lista_mais_resultados(); break;
			case 'excluir-arquivo': admin_arquivos_excluir_arquivo(); break;
		}
		
		interface_ajax_finalizar();
	} else {
		admin_arquivos_interfaces_padroes();
		
		interface_iniciar();
		
		switch($_GESTOR['opcao']){
			case 'upload': admin_arquivos_upload(); break;
			case 'listar-arquivos': admin_arquivos_listar(); break;
		}
		
		interface_finalizar();
	}
}

admin_arquivos_start();

?>