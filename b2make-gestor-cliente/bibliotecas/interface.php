<?php

global $_GESTOR;

$_GESTOR['biblioteca-interface']							=	Array(
	'versao' => '1.1.0',
);

// ===== Funções auxiliares

function interface_formulario_campos($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// pagina - String - Opcional - Fornecer a página onde será incluído o campo ao invés de colocar na página padrão.
	// campos - Array - Opcional - Conjunto de todos os campos.
		// tipo - String - Obrigatório - Identificador do tipo do campo.
		// id - String - Obrigatório - Identificador do campo.
		// nome - String - Obrigatório - Nome do campo.
		
		// ##### Caso tipo = 'select'
		
		// disabled - Bool - Opcional - Se habilitado, o select ficará desabilitado e só poderá ser visto.
		// menu - Bool - Opcional - Se habilitado, o select terá mais opções visuais.
		// procurar - Bool - Opcional - Se habilitado, é possível procurar um valor no select digitando no teclado.
		// limpar - Bool - Opcional - Se habilitado, o select tem uma opção de deselecionar a opção selecionada.
		// multiple - Bool - Opcional - Se habilitado, o select tem uma opção de selecionar várias opções ao mesmo tempo.
		// fluid - Bool - Opcional - Se habilitado, o select será do tipo fluído tentando completar toda a tela.
		// placeholder - String - Opcional - Se definido o select terá uma opção coringa para mostrar na caixa quando não houver valor selecionado.
		// selectClass - String - Opcional - Se definido será incluída a classe ou classes no final do parâmetro class do select.
		
		// Ou tabela Ou dados Ou dadosAntes Ou dadosDepois
		
		// tabela - Array - Opcional - Conjunto com dados que virão de uma tabela no banco de dados
			// id - Bool - Opcional - Caso definido, vai usar o campo id como campo referencial e não o id_numerico.
			// nome - String - Obrigatório - nome da tabela do banco de dados.
			// campo - String - Obrigatório - campo da tabela que será retornado como valor textual das opções.
			// id_numerico - String - Obrigatório - identificador numérico da dos dados da tabela.
			// id_selecionado - Int - Opcional - Caso definido, deixar a opção com o valor do id selecionado.
			// where - String - Opcional - Caso definido, usar o where como filtro na tabela do banco de dados.
		
		// valor_selecionado - String - Opcional - Caso definido, deixar a opção com o valor do id selecionado.
		// valor_selecionado_icone - String - Opcional - Caso definido, mostrar ícone na versão menu (não funciona no tipo select/option).
		// placeholder_icone - String - Opcional - Caso definido, mostrar ícone no 'text default' na versão menu (não funciona no tipo select/option).
		// dados, dadosAntes, dadosDepois - Array - Opcional - Conjunto com dados que virão de um array
			// texto - String - Obrigatório - Texto de cada opção no select.
			// valor - String - Obrigatório - Valor de cada opção no select.
			// icone - String - Opcional - Ícone que deverá ser aplicado a cada dado.
		
		// ##### Caso tipo = 'imagepick'
		
		// id_arquivos - Int - Opcional - Id referencial do arquivo.
		
		// ##### Caso tipo = 'imagepick-hosts'
		
		// id_hosts_arquivos - Int - Opcional - Id referencial do arquivo no host.
		
		// ##### Caso tipo = 'templates-hosts'
		
		// categoria_id - String - Obrigatório - Identificador da categoria de templates.
		// template_id - String - Opcional - Id do template.
		// template_tipo - String - Opcional - Tipo do template (gestor ou hosts).
	
	// ===== 
	
	if(isset($campos)){
		foreach($campos as $campo){
			switch($campo['tipo']){
				case 'select':
					if(isset($campo['menu'])){
						$campo_saida = "	".'<div id="'.$campo['id'].'" class="ui '.(isset($campo['disabled']) ? 'disabled ':'').(isset($campo['fluid']) ? 'fluid ':'').(isset($campo['procurar']) ? 'search ':'').(isset($campo['limpar']) ? 'clearable ':'').(isset($campo['multiple']) ? 'multiple ':'').'selection dropdown'.(isset($campo['selectClass']) ? ' '.$campo['selectClass'] : '').'">'."\n";
						$campo_saida .= "		".'<input type="hidden" name="'.$campo['nome'].'"#selectedValue#>'."\n";
						$campo_saida .= "		".'<i class="dropdown icon"></i>'."\n";
					} else {
						$campo_saida = "	".'<select id="'.$campo['id'].'" class="ui '.(isset($campo['disabled']) ? 'disabled ':'').(isset($campo['fluid']) ? 'fluid ':'').(isset($campo['procurar']) ? 'search ':'').(isset($campo['limpar']) ? 'clearable ':'').'dropdown'.(isset($campo['selectClass']) ? ' '.$campo['selectClass'] : '').'" name="'.$campo['nome'].'"'.(isset($campo['multiple']) ? ' multiple':'').'>'."\n";
					}
					
					if(isset($campo['placeholder'])){
						if(isset($campo['menu'])){
							if(isset($campo['valor_selecionado_texto'])){
								$campo_saida .= "		".'<div class="text">'.(isset($campo['valor_selecionado_icone']) ? '<i class="'.$campo['valor_selecionado_icone'].' icon"></i>' : '').$campo['valor_selecionado_texto'].'</div>'."\n";
							} else {
								$campo_saida .= "		".'<div class="default text">'.(isset($campo['placeholder_icone']) ? '<i class="'.$campo['placeholder_icone'].' icon"></i>' : '').$campo['placeholder'].'</div>'."\n";
							}
						
							$campo_saida .= "		".'<div class="menu">'."\n";
						} else {
							$campo_saida .= "		".'<option value="">'.$campo['placeholder'].'</option>'."\n";
						}
					}
					
					// ===== Dados antes ou dados depois.
					
					if(isset($campo['dadosAntes']) || isset($campo['dadosDepois'])){
						$dadosAntes = (isset($campo['dadosAntes']) ? $campo['dadosAntes'] : Array());
						$dadosDepois = (isset($campo['dadosDepois']) ? $campo['dadosDepois'] : Array());
						
						$camposAntes = '';
						$camposDepois = '';
						
						if(isset($campo['valor_selecionado'])){
							$valor_selecionado = $campo['valor_selecionado'];
						}
						
						foreach($dadosAntes as $dado){
							if(isset($dado['texto']) && isset($dado['valor'])){
								if(isset($campo['menu'])){
									$camposAntes .= "			".'<div class="item '.(isset($valor_selecionado) ? ($valor_selecionado == $dado['valor'] ? 'active selected' : '' ) : '' ).'" data-value="'.$dado['valor'].'">'.(isset($dado['icone']) ? '<i class="'.$dado['icone'].' icon"></i>' : '').$dado['texto'].'</div>'."\n";
									
									if(isset($valor_selecionado)){
										if($valor_selecionado == $dado['valor']){
											$camposAntes = modelo_var_troca($camposAntes,"#selectedValue#",' value="'.$dado['valor'].'"');
										}
									}
								} else {
									$camposAntes .= "		".'<option value="'.$dado['valor'].'"'.(isset($valor_selecionado) ? ($valor_selecionado == $dado['valor'] ? ' selected' : '' ) : '' ).'>'.$dado['texto'].'</option>'."\n";
								}
							}
						}
						
						foreach($dadosDepois as $dado){
							if(isset($dado['texto']) && isset($dado['valor'])){
								if(isset($campo['menu'])){
									$camposDepois .= "			".'<div class="item '.(isset($valor_selecionado) ? ($valor_selecionado == $dado['valor'] ? 'active selected' : '' ) : '' ).'" data-value="'.$dado['valor'].'">'.(isset($dado['icone']) ? '<i class="'.$dado['icone'].' icon"></i>' : '').$dado['texto'].'</div>'."\n";
									
									if(isset($valor_selecionado)){
										if($valor_selecionado == $dado['valor']){
											$camposDepois = modelo_var_troca($camposDepois,"#selectedValue#",' value="'.$dado['valor'].'"');
										}
									}
								} else {
									$camposDepois .= "		".'<option value="'.$dado['valor'].'"'.(isset($valor_selecionado) ? ($valor_selecionado == $dado['valor'] ? ' selected' : '' ) : '' ).'>'.$dado['texto'].'</option>'."\n";
								}
							}
						}
					}
					
					// ===== Incluir os campos antes.
					
					if(isset($camposAntes)){
						$campo_saida .= $camposAntes;
					}
					
					// ===== Dados da tabela ou dados específicos.
					
					if(isset($campo['tabela'])){
						$tabela = $campo['tabela'];
						
						if(isset($tabela['id'])){
							if(isset($tabela['nome']) && isset($tabela['campo'])){
								$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
								
								$resultado = banco_select_name
								(
									banco_campos_virgulas(Array(
										$tabela['campo'],
										$modulo['tabela']['id']
									))
									,
									$tabela['nome'],
									"WHERE ".$modulo['tabela']['status']."='A'"
									.(isset($tabela['where']) ? ' AND ('.$tabela['where'].')' : '' )
									." ORDER BY ".$tabela['campo']." ASC"
								);
								
								if($resultado){
									foreach($resultado as $res){
										if(isset($campo['menu'])){
											$campo_saida .= "			".'<div class="item '.(isset($tabela['id_selecionado']) ? ($tabela['id_selecionado'] == $res[$modulo['tabela']['id']] ? 'active selected' : '' ) : '' ).'" data-value="'.$res[$modulo['tabela']['id']].'">'.$res[$tabela['campo']].'</div>'."\n";
										} else {
											$campo_saida .= "		".'<option value="'.$res[$modulo['tabela']['id']].'"'.(isset($tabela['id_selecionado']) ? ($tabela['id_selecionado'] == $res[$modulo['tabela']['id']] ? ' selected' : '' ) : '' ).'>'.$res[$tabela['campo']].'</option>'."\n";
										}
									}
								}
							}
						} else {
							if(isset($tabela['nome']) && isset($tabela['campo']) && isset($tabela['id_numerico'])){
								$modulo = $_GESTOR['modulo#'.$_GESTOR['modulo-id']];
								
								$resultado = banco_select_name
								(
									banco_campos_virgulas(Array(
										$tabela['campo'],
										$tabela['id_numerico']
									))
									,
									$tabela['nome'],
									"WHERE ".$modulo['tabela']['status']."='A'"
									.(isset($tabela['where']) ? ' AND ('.$tabela['where'].')' : '' )
									." ORDER BY ".$tabela['campo']." ASC"
								);
								
								if($resultado){
									foreach($resultado as $res){
										if(isset($campo['menu'])){
											$campo_saida .= "			".'<div class="item '.(isset($tabela['id_selecionado']) ? ($tabela['id_selecionado'] == $res[$tabela['id_numerico']] ? 'active selected' : '' ) : '' ).'" data-value="'.$res[$tabela['id_numerico']].'">'.$res[$tabela['campo']].'</div>'."\n";
										} else {
											$campo_saida .= "		".'<option value="'.$res[$tabela['id_numerico']].'"'.(isset($tabela['id_selecionado']) ? ($tabela['id_selecionado'] == $res[$tabela['id_numerico']] ? ' selected' : '' ) : '' ).'>'.$res[$tabela['campo']].'</option>'."\n";
										}
									}
								}
							}
						}
					} else if(isset($campo['dados'])){
						$dados = $campo['dados'];
						
						if(isset($campo['valor_selecionado'])){
							$valor_selecionado = $campo['valor_selecionado'];
						}
						
						foreach($dados as $dado){
							if(isset($dado['texto']) && isset($dado['valor'])){
								if(isset($campo['menu'])){
									$campo_saida .= "			".'<div class="item '.(isset($valor_selecionado) ? ($valor_selecionado == $dado['valor'] ? 'active selected' : '' ) : '' ).'" data-value="'.$dado['valor'].'">'.(isset($dado['icone']) ? '<i class="'.$dado['icone'].' icon"></i>' : '').$dado['texto'].'</div>'."\n";
									
									if(isset($valor_selecionado)){
										if($valor_selecionado == $dado['valor']){
											$campo_saida = modelo_var_troca($campo_saida,"#selectedValue#",' value="'.$dado['valor'].'"');
										}
									}
								} else {
									$campo_saida .= "		".'<option value="'.$dado['valor'].'"'.(isset($valor_selecionado) ? ($valor_selecionado == $dado['valor'] ? ' selected' : '' ) : '' ).'>'.$dado['texto'].'</option>'."\n";
								}
							}
						}
					}
					
					// ===== Incluir os campos antes.
					
					if(isset($camposDepois)){
						$campo_saida .= $camposDepois;
					}
					
					// ===== Finalizar a montagem do select
					
					if(isset($campo['menu'])){
						$campo_saida .= "		".'</div>'."\n";
						$campo_saida .= "	".'</div>'."\n";
						
						$campo_saida = modelo_var_troca($campo_saida,"#selectedValue#",'');
					} else {
						$campo_saida .= "	".'</select>'."\n";
					}
					
					// ===== Incluir o select na página
					
					if(isset($pagina)){
						return modelo_var_troca($pagina,'<span>#select-'.$campo['id'].'#</span>',$campo_saida);
					} else {
						$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<span>#select-'.$campo['id'].'#</span>',$campo_saida);
					}
				break;
				case 'imagepick':
					// ===== Ler o layout do image pick
					
					$imagepick = gestor_componente(Array(
						'id' => 'widget-imagem',
						'modulosExtra' => Array(
							'interface',
						),
					));
					
					// ===== Definir valores padrões
					
					$imagepickJS['padroes'] = Array(
						'fileId' => '-1',
						'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-default-name')),
						'data' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-default-date')),
						'tipo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-default-type')),
						'imgSrc' => $_GESTOR['url-full'] . 'images/imagem-padrao.png',
					);
					
					$imagepickJS['modal'] = Array(
						'head' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-modal-head')),
						'cancel' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-modal-cancel')),
						'url' => $_GESTOR['url-full'] . 'admin-arquivos/?paginaIframe=sim',
					);
					
					$imagepickJS['alertas'] = Array(
						'naoImagem' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-alert-not-image')),
					);
					
					// ===== Se existe o arquivo, baixar dados do banco, senão definir valores padrões
					
					$found = false;
					if(isset($campo['id_arquivos'])){
						$id_arquivos = $campo['id_arquivos'];
						
						$arquivos = banco_select_name
						(
							banco_campos_virgulas(Array(
								'tipo',
								'nome',
								'data_criacao',
								'caminho',
								'caminho_mini',
							))
							,
							"arquivos",
							"WHERE id_arquivos='".$id_arquivos."'"
						);
						
						if($arquivos){
							$found = true;
							
							if($arquivos[0]['caminho_mini']){
								$imgSrc = $_GESTOR['url-full'] . $arquivos[0]['caminho_mini'];
							} else {
								$imgSrc = $_GESTOR['url-full'] . 'images/imagem-padrao.png';
							}
							
							$data = interface_formatar_dado(Array('dado' => $arquivos[0]['data_criacao'], 'formato' => 'dataHora'));
							$nome = $arquivos[0]['nome'];
							$tipo = $arquivos[0]['tipo'];
							
							$fileId = $id_arquivos;
						}
					}
					
					if(!$found){
						$fileId = $imagepickJS['padroes']['fileId'];
						$nome = $imagepickJS['padroes']['nome'];
						$data = $imagepickJS['padroes']['data'];
						$tipo = $imagepickJS['padroes']['tipo'];
						$imgSrc = $imagepickJS['padroes']['imgSrc'];
					}
					
					// ===== Alterar os dados do widget
					
					$imagepick = modelo_var_troca($imagepick,"#cont-id#",$campo['id']);
					$imagepick = modelo_var_troca($imagepick,"#campo-nome#",$campo['nome']);
					
					$imagepick = modelo_var_troca_tudo($imagepick,"#file-id#",$fileId);
					$imagepick = modelo_var_troca($imagepick,"#nome#",$nome);
					$imagepick = modelo_var_troca($imagepick,"#tipo#",$tipo);
					$imagepick = modelo_var_troca($imagepick,"#data#",$data);
					$imagepick = modelo_var_troca($imagepick,"#img-src#",$imgSrc);
					
					// ===== Incluir o imagepick na página
					
					if(isset($pagina)){
						$pagina = modelo_var_troca($pagina,'<span>#imagepick-'.$campo['id'].'#</span>',$imagepick);
					} else {
						$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<span>#imagepick-'.$campo['id'].'#</span>',$imagepick);
					}
					
					// ===== Atualizar variável javascript
					
					$_GESTOR['javascript-vars']['interface']['imagepick'] = $imagepickJS;
					
					// ===== Incluir o componente iframe modal
					
					interface_componentes_incluir(Array(
						'componente' => Array(
							'modal-iframe',
							'modal-alerta',
						)
					));
					
					// ===== Se precisar retornar a página.
				
					if(isset($pagina)){
						return $pagina;
					}
				break;
				case 'imagepick-hosts':
					// ===== Ler o layout do image pick
					
					$imagepick = gestor_componente(Array(
						'id' => 'widget-imagem',
						'modulosExtra' => Array(
							'interface',
						),
					));
					
					// ===== Definir valores padrões
					
					$imagepickJS['padroes'] = Array(
						'fileId' => '-1',
						'nome' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-default-name')),
						'data' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-default-date')),
						'tipo' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-default-type')),
						'imgSrc' => $_GESTOR['url-full'] . 'images/imagem-padrao.png',
					);
					
					$imagepickJS['modal'] = Array(
						'head' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-modal-head')),
						'cancel' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-modal-cancel')),
						'url' => $_GESTOR['url-full'] . 'arquivos/?paginaIframe=sim',
					);
					
					$imagepickJS['alertas'] = Array(
						'naoImagem' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-image-alert-not-image')),
					);
					
					// ===== Se existe o arquivo, baixar dados do banco, senão definir valores padrões
					
					$found = false;
					if(isset($campo['id_hosts_arquivos'])){
						$id_hosts_arquivos = $campo['id_hosts_arquivos'];
						
						$arquivos = banco_select_name
						(
							banco_campos_virgulas(Array(
								'tipo',
								'nome',
								'data_criacao',
								'caminho',
								'caminho_mini',
							))
							,
							"hosts_arquivos",
							"WHERE id_hosts_arquivos='".$id_hosts_arquivos."'"
						);
						
						if($arquivos){
							$found = true;
							
							if($arquivos[0]['caminho_mini']){
								// ===== Carregar domínio do host.
								
								gestor_incluir_biblioteca('host');
								$dominio = host_url(Array(
									'opcao' => 'full',
								));
								
								// ===== Montar imgSrc.
								
								$imgSrc = $dominio . $arquivos[0]['caminho_mini'];
							} else {
								$imgSrc = $_GESTOR['url-full'] . 'images/imagem-padrao.png';
							}
							
							$data = interface_formatar_dado(Array('dado' => $arquivos[0]['data_criacao'], 'formato' => 'dataHora'));
							$nome = $arquivos[0]['nome'];
							$tipo = $arquivos[0]['tipo'];
							
							$fileId = $id_hosts_arquivos;
						}
					}
					
					if(!$found){
						$fileId = $imagepickJS['padroes']['fileId'];
						$nome = $imagepickJS['padroes']['nome'];
						$data = $imagepickJS['padroes']['data'];
						$tipo = $imagepickJS['padroes']['tipo'];
						$imgSrc = $imagepickJS['padroes']['imgSrc'];
					}
					
					// ===== Alterar os dados do widget
					
					$imagepick = modelo_var_troca($imagepick,"#cont-id#",$campo['id']);
					$imagepick = modelo_var_troca($imagepick,"#campo-nome#",$campo['nome']);
					
					$imagepick = modelo_var_troca_tudo($imagepick,"#file-id#",$fileId);
					$imagepick = modelo_var_troca($imagepick,"#nome#",$nome);
					$imagepick = modelo_var_troca($imagepick,"#tipo#",$tipo);
					$imagepick = modelo_var_troca($imagepick,"#data#",$data);
					$imagepick = modelo_var_troca($imagepick,"#img-src#",$imgSrc);
					
					// ===== Incluir o imagepick na página
					
					if(isset($pagina)){
						$pagina = modelo_var_troca($pagina,'<span>#imagepick-'.$campo['id'].'#</span>',$imagepick);
					} else {
						$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<span>#imagepick-'.$campo['id'].'#</span>',$imagepick);
					}
					
					// ===== Atualizar variável javascript
					
					$_GESTOR['javascript-vars']['interface']['imagepick'] = $imagepickJS;
					
					// ===== Incluir o componente iframe modal
					
					interface_componentes_incluir(Array(
						'componente' => Array(
							'modal-iframe',
							'modal-alerta',
						)
					));
					
					// ===== Se precisar retornar a página.
				
					if(isset($pagina)){
						return $pagina;
					}
				break;
				case 'templates-hosts':
					// ===== Ler o layout do template pick
					
					$templatePick = gestor_componente(Array(
						'id' => 'widget-template',
						'modulosExtra' => Array(
							'interface',
						),
					));
					
					// ===== Pegar a categoria
					
					$categorias = banco_select_name
					(
						banco_campos_virgulas(Array(
							'nome',
							'id_categorias',
						))
						,
						"categorias",
						"WHERE status!='D'"
						." AND id='".$campo['categoria_id']."'"
					);
					
					if(!$categorias){
						$msgError = modelo_var_troca(gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-error-load')),"#msg#",gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-template-error-category')).': #1');
						$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<span>#templates-'.$campo['id'].'#</span>',$msgError);
						return;
					}
					
					$id_categorias = $categorias[0]['id_categorias'];
					
					// ===== Definir valores padrões
					
					$templatesJS['modal'] = Array(
						'head' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-template-modal-head')),
						'cancel' => gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-template-modal-cancel')),
						'url' => $_GESTOR['url-full'] . 'templates/seletores/?paginaIframe=sim&modelo=paginas&categoria_id='.$campo['categoria_id'],
					);
					
					// ===== Se template_id e template_tipo foram enviados, baixar dados do banco, senão definir valores padrões.
					
					$found = false;
					if(isset($campo['template_id']) && isset($campo['template_tipo'])){
						// ===== Verificar se o tipo enviado é o correto, senão devolver erro.
						
						switch($campo['template_tipo']){
							case 'gestor':
								$templates = banco_select_name
								(
									banco_campos_virgulas(Array(
										'id',
										'nome',
										'id_arquivos_Imagem',
										'data_modificacao',
									))
									,
									"templates",
									"WHERE status!='D'"
									." AND id='".$campo['template_id']."'"
								);
								
								$tipoLabelID = 'template-gestor-label';
							break;
							case 'hosts':
								$templates = banco_select_name
								(
									banco_campos_virgulas(Array(
										'id',
										'nome',
										'id_hosts_arquivos_Imagem',
										'data_modificacao',
									))
									,
									"hosts_templates",
									"WHERE status!='D'"
									." AND id='".$campo['template_id']."'"
								);
								
								$tipoLabelID = 'template-custom-label';
							break;
							default:
								$msgError = modelo_var_troca(gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-error-load')),"#msg#",gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-template-error-category')).': #3');
								$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<span>#templates-'.$campo['id'].'#</span>',$msgError);
								return;
						}
						
						if(!$templates){
							$msgError = modelo_var_troca(gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-error-load')),"#msg#",gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-template-error-category')).': #4');
							$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<span>#templates-'.$campo['id'].'#</span>',$msgError);
							return;
						}
						
						// ===== Formatar os campos principais.
						
						$templateId = $templates[0]['id'];
						$templateTipo = $campo['template_tipo'];
						
						$nome = $templates[0]['nome'];
						$tipo = gestor_variaveis(Array('modulo' => 'templates','id' => $tipoLabelID));
						$data = interface_formatar_dado(Array('dado' => $templates[0]['data_modificacao'], 'formato' => 'dataHora'));
						
						// ===== Se existir pegar o caminho do arquivo mini.
						
						switch($campo['template_tipo']){
							case 'gestor':
								$id_arquivos = $templates[0]['id_arquivos_Imagem'];
								
								if(existe($id_arquivos)){
									$resultado = banco_select_name(
										banco_campos_virgulas(Array(
											'caminho_mini',
										)),
										"arquivos",
										"WHERE id_arquivos='".$id_arquivos."'"
									);
									
									if($resultado){
										if(existe($resultado[0]['caminho_mini'])){
											$caminho_mini = $resultado[0]['caminho_mini'];
										}
									}
								}
								
								// ===== Domínio completo do Gestor.
						
								$dominio = $_GESTOR['url-full'];
							break;
							case 'hosts':
								$id_hosts_arquivos = $templates[0]['id_hosts_arquivos_Imagem'];
								
								if(existe($id_hosts_arquivos)){
									$resultado = banco_select_name(
										banco_campos_virgulas(Array(
											'caminho_mini',
										)),
										"hosts_arquivos",
										"WHERE id_hosts_arquivos='".$id_hosts_arquivos."'"
									);
									
									if($resultado){
										if(existe($resultado[0]['caminho_mini'])){
											$caminho_mini = $resultado[0]['caminho_mini'];
										}
									}
								}
								
								// ===== Carregar domínio do host.
								
								gestor_incluir_biblioteca('host');
								$dominio = host_url(Array(
									'opcao' => 'full',
								));
						}
					} else {
						// ===== Pegar template 'padrao' da categoria_id.
						
						$templates = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id',
								'nome',
								'id_arquivos_Imagem',
								'data_modificacao',
							))
							,
							"templates",
							"WHERE status!='D'"
							." AND id_categorias='".$id_categorias."'"
							." AND padrao IS NOT NULL"
						);
						
						if(!$templates){
							$msgError = modelo_var_troca(gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-error-load')),"#msg#",gestor_variaveis(Array('modulo' => 'interface','id' => 'widget-template-error-category')).': #2');
							$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<span>#templates-'.$campo['id'].'#</span>',$msgError);
							return;
						}
						
						// ===== Formatar os campos principais.
						
						$templateId = $templates[0]['id'];
						$templateTipo = 'gestor';
						
						$nome = $templates[0]['nome'];
						$tipo = gestor_variaveis(Array('modulo' => 'templates','id' => 'template-gestor-label'));
						$data = interface_formatar_dado(Array('dado' => $templates[0]['data_modificacao'], 'formato' => 'dataHora'));
						
						// ===== Se existir pegar o caminho do arquivo mini.
						
						$id_arquivos = $templates[0]['id_arquivos_Imagem'];
						
						if(existe($id_arquivos)){
							$resultado = banco_select_name(
								banco_campos_virgulas(Array(
									'caminho_mini',
								)),
								"arquivos",
								"WHERE id_arquivos='".$id_arquivos."'"
							);
							
							if($resultado){
								if(existe($resultado[0]['caminho_mini'])){
									$caminho_mini = $resultado[0]['caminho_mini'];
								}
							}
						}
						
						// ===== Domínio completo do Gestor.
				
						$dominio = $_GESTOR['url-full'];
					}
					
					// ===== Imagem Mini padrão ou Imagem Referência.
					
					if(existe($caminho_mini)){
						$imgSrc = $dominio . $caminho_mini;
					} else {
						$imgSrc = $_GESTOR['url-full'] . 'images/imagem-padrao.png';
					}
					
					// ===== Alterar os dados do widget
					
					$templatePick = modelo_var_troca($templatePick,"#cont-id#",$campo['id']);
					$templatePick = modelo_var_troca($templatePick,"#campo-nome#",$campo['nome'].'_id');
					$templatePick = modelo_var_troca($templatePick,"#campo-tipo#",$campo['nome'].'_tipo');
					
					$templatePick = modelo_var_troca($templatePick,"#template-id#",$templateId);
					$templatePick = modelo_var_troca($templatePick,"#template-tipo#",$templateTipo);
					$templatePick = modelo_var_troca($templatePick,"#nome#",$nome);
					$templatePick = modelo_var_troca($templatePick,"#tipo#",$tipo);
					$templatePick = modelo_var_troca($templatePick,"#data#",$data);
					$templatePick = modelo_var_troca($templatePick,"#img-src#",$imgSrc);
					
					// ===== Incluir o templatePick na página
					
					$_GESTOR['pagina'] = modelo_var_troca($_GESTOR['pagina'],'<span>#templates-'.$campo['id'].'#</span>',$templatePick);
					
					// ===== Atualizar variável javascript
					
					$_GESTOR['javascript-vars']['interface']['templates'] = $templatesJS;
					
					// ===== Incluir o componente iframe modal
					
					interface_componentes_incluir(Array(
						'componente' => Array(
							'modal-iframe',
						)
					));
				break;
			}
		}
	}
}

function interface_alerta($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// msg - String - Opcional - Incluir uma mensagem para ser alertada na próxima tela do usuário.
	// redirect - Bool - Opcional - Só permitir imprimir depois do redirect.
	// imprimir - Bool - Opcional - Imprimir o alerta na tela.
	
	// ===== 
	
	if(isset($msg)){
		if(isset($redirect)){
			gestor_sessao_variavel('alerta',Array(
				'msg' => $msg,
			));
			
			$_GESTOR['interface-alerta-nao-imprimir'] = true;
		} else {
			$_GESTOR['pagina-alerta'] = Array(
				'msg' => $msg,
			);
		}
	}
	
	if(isset($imprimir)){
		if(!isset($_GESTOR['interface-alerta-nao-imprimir'])){
			if(!existe(gestor_sessao_variavel('alerta-redirect'))){
				if(existe(gestor_sessao_variavel('alerta'))){
					$alerta = gestor_sessao_variavel('alerta');
					gestor_sessao_variavel_del('alerta');
				} else if(isset($_GESTOR['pagina-alerta'])){
					$alerta = $_GESTOR['pagina-alerta'];
				}
				
				if(isset($alerta)){
					if(!isset($_GESTOR['javascript-vars']['interface'])){
						$_GESTOR['javascript-vars']['interface'] = Array();
					}
					
					$_GESTOR['javascript-vars']['interface']['alerta'] = $alerta;
					
					interface_componentes_incluir(Array(
						'componente' => Array(
							'modal-alerta',
						)
					));
				}
			}
		} else {
			unset($_GESTOR['interface-alerta-nao-imprimir']);
		}
	}
}

function interface_componentes_incluir($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// componente - String|Array - Obrigatório - Incluir componente(s) na interface.
	
	// ===== 
	
	if(isset($componente)){
		switch(gettype($componente)){
			case 'array':
				if(count($componente) > 0){
					foreach($componente as $com){
						$_GESTOR['interface']['componentes'][$com] = true;
					}
				}
			break;
			default:
				$_GESTOR['interface']['componentes'][$componente] = true;
		}
	}
}

function interface_componentes($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	
	// ===== 
	
	if(isset($_GESTOR['interface'])){
		if(isset($_GESTOR['interface']['componentes'])){
			$componentes_layouts_ids = Array();
			$componentes = $_GESTOR['interface']['componentes'];
			
			if(count($componentes) > 0){
				foreach($componentes as $componente => $val){
					switch($componente){
						case 'modal-carregamento': $componentes_layouts_ids[] = 'hosts-interface-carregando-modal'; break;
						case 'modal-alerta': $componentes_layouts_ids[] = 'hosts-interface-alerta-modal'; break;
						case 'modal-informativo': $componentes_layouts_ids[] = 'hosts-interface-modal-informativo'; break;
					}
				}
			}
			
			// ===== Carregar layout de todos os componentes
			
			$layouts = false;
			
			if(count($componentes_layouts_ids) > 0){
				$layouts = gestor_componente(Array(
					'id' => $componentes_layouts_ids,
				));
			}
			
			if($layouts){
				$variables_js = Array();
				
				foreach($layouts as $id => $layout){
					$componente_html = '';
					
					switch($id){
						// ===== Modal de carregamento
						
						case 'hosts-interface-carregando-modal':
							$componente_html = $layout['html'];
						break;
						
						// ===== Modal de alerta
						
						case 'hosts-interface-alerta-modal':
							$componente_html = $layout['html'];
							
							$componente_html = modelo_var_troca($componente_html,"#titulo#",gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-title')));
							$componente_html = modelo_var_troca($componente_html,"#botao-ok#",gestor_variaveis(Array('modulo' => 'interface','id' => 'alert-button-ok')));
							
							$variables_js['ajaxTimeoutMessage'] = gestor_variaveis(Array('modulo' => 'interface','id' => 'ajax-timeout-message'));
						break;
						
						// ===== Modal informativo
						
						case 'hosts-interface-modal-informativo':
							$componente_html = $layout['html'];
							
							$componente_html = modelo_var_troca($componente_html,"#titulo#",gestor_variaveis(Array('modulo' => 'interface','id' => 'inform-title')));
						break;
						
					}
					
					if(existe($componente_html)){
						$_GESTOR['pagina'] .= $componente_html;
					}
				}
				
				$_GESTOR['javascript-vars']['componentes'] = $variables_js;
			}
		}
	}
}

function interface_historico($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Parâmetros
	
	// id - String - Obrigatório - Identificador do registro.
	// modulo - String - Obrigatório - Identificador do módulo.
	// pagina - String - Obrigatório - Página onde será implementado o histórico.
	
	// ===== 
	
	$max_dados_por_pagina = 10;
	$total = 0;
	$paginaAtual = 0;
	$totalPaginas = 0;
	
	$whereModulo = "modulo='".$modulo."'".(isset($id) ? " AND id='".$id."'" : '');
	
	// ===== Verificar o total de registros.
	
	$pre_historico = banco_select(Array(
		'tabela' => 'historico',
		'campos' => Array(
			'id_historico',
		),
		'extra' => 
			"WHERE "
			.$whereModulo
	));
	
	if(!isset($pre_historico)){
		$pre_historico = Array();
	}
	
	$total = count($pre_historico);
	
	// ===== Página atual
	
	if($_GESTOR['ajax']){
		if(isset($_REQUEST['pagina'])){
			$paginaAtual = (int)banco_escape_field($_REQUEST['pagina']);
		}
	} else {
		$totalPaginas = ($total % $max_dados_por_pagina > 0 ? 1 : 0) + floor($total / $max_dados_por_pagina);
		
		$_GESTOR['javascript-vars']['interface'] = Array(
			'id' => $id,
			'total' => $total,
			'totalPaginas' => $totalPaginas,
			'historico' => true,
		);
	}
	
	$historico = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_hosts_usuarios',
			'versao',
			'campo',
			'opcao',
			'filtro',
			'alteracao',
			'alteracao_txt',
			'valor_antes',
			'valor_depois',
			'tabela',
			'data',
			'controlador',
		))
		,
		"historico",
		"WHERE "
		.$whereModulo
		." ORDER BY versao DESC,id_historico DESC"
		." LIMIT ".($max_dados_por_pagina * $paginaAtual).",".$max_dados_por_pagina
	);
	
	if($historico){
		// ===== Caso haja registros do histórico, iniciar variáveis.
		
		$first_loop = true;
		$change_item = false;
		$versao_atual = 0;
		$id_hosts_usuarios = '0';
		$user_id = '';
		$user_primeiro_nome = '';
		
		if(!$_GESTOR['ajax']){
			$historico_linha = '<div class="ui middle aligned divided list">';
		} else {
			$historico_linha = '';
		}
		
		// ===== Varrer todos os registros do histórico
		
		foreach($historico as $item){
			if(existe($item['controlador'])){
				// ===== Caso tenha sido um histórico de controladores, incluir a referência do mesmo.
				
				switch($item['controlador']){
					case 'paypal-webhook':
						$autorName = 'PayPal Webhook';
					break;
					case 'cron':
						$autorName = 'Robo Controlador';
					break;
					default:
						$autorName = $item['controlador'];
				}
				// ===== Informar o autor do registro do histórico.
				
				$autor = '<a>' . $autorName . '</a>';
			} else {
				// ===== Buscar a referência do usuário do host que incluiu o registro.
				
				if($item['id_hosts_usuarios'] != $id_hosts_usuarios){
					$id_hosts_usuarios = $item['id_hosts_usuarios'];
					
					$usuarios = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id',
							'primeiro_nome',
						))
						,
						"usuarios",
						"WHERE id_hosts_usuarios='".$id_hosts_usuarios."'"
					);
					
					$user_id = $usuarios[0]['id'];
					$user_primeiro_nome = $usuarios[0]['primeiro_nome'];
				}
				
				// ===== Informar o autor do registro do histórico.
				
				$autor = '<a>' . $user_primeiro_nome . '</a>';
			}
			
			// Caso modifique a versão criar nova linha de registro.
			
			gestor_incluir_biblioteca('formato');
			
			if((int)$item['versao'] != $versao_atual){
				$versao_atual = (int)$item['versao'];
				
				$data = formato_dado(Array(
					'valor' => $item['data'],
					'tipo' => 'dataHora',
				));
				
				$historico_linha .= (!$first_loop ? '.</div></div></div>':'') . '<div class="item"><i class="info circle blue icon"></i><div class="content"><div class="header">' . $data . ' - '.$autor.'</div><div class="description first-letter-uppercase">';
				
				$change_item = true;
				$first_loop = false;
			}
			
			// ===== Iniciar variáveis de cada registro.
			
			$campo = $item['campo'];
			$opcao = $item['opcao'];
			$valor_antes = $item['valor_antes'];
			$valor_depois = $item['valor_depois'];
			$alteracao = $item['alteracao'];
			$alteracao_txt = $item['alteracao_txt'];
			$tabela = json_decode($item['tabela'],true);
			
			// ===== Aplicação de filtro de dados para cada caso.
			
			switch($item['filtro']){
				case 'checkbox': 
					if($valor_antes == '1'){
						$valor_antes = gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-checkbox-active'));
					} else {
						$valor_antes = gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-checkbox-inactive'));
					}
					
					if($valor_depois == '1'){
						$valor_depois = gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-checkbox-active'));
					} else {
						$valor_depois = gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-checkbox-inactive'));
					}
				break;
				case 'texto-para-float':
				case 'float-para-texto':
				case 'texto-para-int':
				case 'int-para-texto':
					$valor_antes = formato_dado(Array('valor' => $valor_antes,'tipo' => $item['filtro']));
					$valor_depois = formato_dado(Array('valor' => $valor_depois,'tipo' => $item['filtro']));
				break;
			}
			
			// ===== Definir o valor da variável principal
			
			$campo_texto = gestor_variaveis(Array('modulo' => $_GESTOR['modulo-id'],'id' => $campo));
			
			if(!existe($campo_texto)){
				$campo_texto = gestor_variaveis(Array('modulo' => 'interface','id' => $campo));
			}
			
			// ===== 3 opções de registro de histórico: valor antes e depois, alteracao via id e somente mostrar campo que mudou.
			
			if(existe($valor_antes) || existe($valor_depois)){
				// ===== Verificar se tem tabela referente dos valores. Se sim, trocar valores com base nessa tabela.
				
				if($tabela){
					if(isset($tabela['id'])){
						if(isset($tabela['nome']) && isset($tabela['campo'])){
							$resultado = banco_select_name
							(
								banco_campos_virgulas(Array(
									$tabela['campo'],
									$tabela['id'],
								))
								,
								$tabela['nome'],
								"WHERE ".$tabela['id']."='".$valor_antes."'"
								." OR ".$tabela['id']."='".$valor_depois."'"
							);
							
							if($resultado)
							foreach($resultado as $res){
								if($res[$tabela['id']] == $valor_antes){
									$valor_antes = $res[$tabela['campo']];
								}
								if($res[$tabela['id']] == $valor_depois){
									$valor_depois = $res[$tabela['campo']];
								}
							}
						}
					} else {
						if(isset($tabela['nome']) && isset($tabela['campo']) && isset($tabela['id_numerico'])){
							$resultado = banco_select_name
							(
								banco_campos_virgulas(Array(
									$tabela['campo'],
									$tabela['id_numerico'],
								))
								,
								$tabela['nome'],
								"WHERE ".$tabela['id_numerico']."='".$valor_antes."'"
								." OR ".$tabela['id_numerico']."='".$valor_depois."'"
							);
							
							if($resultado)
							foreach($resultado as $res){
								if($res[$tabela['id_numerico']] == $valor_antes){
									$valor_antes = $res[$tabela['campo']];
								}
								if($res[$tabela['id_numerico']] == $valor_depois){
									$valor_depois = $res[$tabela['campo']];
								}
							}
						}
					}
				}
				
				switch($opcao){
					case 'usuarios-perfis':
						$historico_ocorrencia = gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-change-users-profiles'));
					break;
					default:
						$historico_ocorrencia = gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-change-field-after-before'));
				}
				
				// ===== Procurar variável padrão na interface
				
				$valor_antes_variavel = '';
				if(existe($valor_antes)){
					$valor_antes_variavel = gestor_variaveis(Array('modulo' => 'interface','id' => $valor_antes));
					if(existe($valor_antes_variavel)){
						$valor_antes = $valor_antes_variavel;
					}
				}
				
				$valor_depois_variavel = '';
				if(existe($valor_depois)){
					$valor_depois_variavel = gestor_variaveis(Array('modulo' => 'interface','id' => $valor_depois));
					if(existe($valor_depois_variavel)){
						$valor_depois = $valor_depois_variavel;
					}
				}
				
				// ===== Histórico ocorrência
				
				$historico_ocorrencia = modelo_var_troca($historico_ocorrencia,"#campo#",$campo_texto);
				$historico_ocorrencia = modelo_var_troca($historico_ocorrencia,"#valor_antes#",(existe($valor_antes) ? $valor_antes : gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-empty-value'))));
				$historico_ocorrencia = modelo_var_troca($historico_ocorrencia,"#valor_depois#",(existe($valor_depois) ? $valor_depois : gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-empty-value'))));
			} else if(existe($alteracao)){
				$historico_ocorrencia = gestor_variaveis(Array('modulo' => 'interface','id' => $alteracao));
				
				$historico_ocorrencia = modelo_var_troca($historico_ocorrencia,"#campo#",$campo_texto);
				
				switch($alteracao){
					case 'historic-change-status': 
						$historico_ocorrencia = modelo_var_troca($historico_ocorrencia,"#valor_depois#",gestor_variaveis(Array('modulo' => 'interface','id' => $valor_depois)));
					break;
				}
				
				if(existe($alteracao_txt)){
					$historico_ocorrencia .= $alteracao_txt;
				}
			} else {
				$historico_ocorrencia = gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-change-field-only'));
				
				$historico_ocorrencia = modelo_var_troca($historico_ocorrencia,"#campo#",$campo_texto);
			}
			
			// ===== Incluir todas as ocorrências de uma dada versão.
			
			$historico_linha .= (!$change_item ? ', ':'') . ' ' . $historico_ocorrencia;
			$change_item = false;
		}
		
		// ===== finalizar e incluir todos os registros no componente histórico.
	
		if(!$_GESTOR['ajax']){
			$botao_carregar_mais = '';
			
			if($total > $max_dados_por_pagina){
				$botao_carregar_mais = '<div class="ui grid"><div class="column center aligned"><button class="ui button blue" id="_gestor-interface-edit-historico-mais">'.gestor_variaveis(Array('modulo' => 'interface','id' => 'historic-button-load-more')).'</button></div></div>';
			}
			
			$historico_linha .= '.</div></div></div>'.$botao_carregar_mais;
			$pagina = modelo_var_troca($pagina,"<td>#historico#</td>","<td>".$historico_linha."</td>");
		} else {
			$historico_linha .= '.</div></div></div>';
			$pagina = modelo_var_troca($pagina,"#historico#",$historico_linha);
		}
		
	} else {
		// ===== Remove o componente histórico caso não encontre nenhum registro no histórico.
		if(!$_GESTOR['ajax']){
			$cel_nome = 'historico'; $pagina = modelo_tag_in($pagina,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
		} else {
			$pagina = '';
		}
	}
	
	return $pagina;
}

// ===== Interfaces principais


// ===== Interfaces ajax

function interface_ajax_historico_mais_resultados(){
	global $_GESTOR;
	
	$_GESTOR['ajax-json'] = Array(
		'status' => 'Ok',
		'pagina' => interface_historico(Array(
			'id' => (isset($_REQUEST['id']) ? $_REQUEST['id'] : '' ),
			'modulo' => $_GESTOR['modulo-alvo-id'],
			'pagina' => '#historico#',
		))
	);
}

// ===== Interfaces padrões

function interface_ajax_iniciar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	
}

function interface_ajax_finalizar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($_GESTOR['ajax']){
		switch($_GESTOR['ajax-opcao']){
			case 'historico-mais-resultados': interface_ajax_historico_mais_resultados(); break;
		}
	}
}

function interface_iniciar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	
}

function interface_finalizar($params = false){
	global $_GESTOR;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	// ===== Imprimir alerta
	
	interface_alerta(Array('imprimir' => true));
	
	// ===== Incluir Componentes na Página
	
	interface_componentes();
	
	// ===== Interface Javascript Vars
	
	if(!isset($_GESTOR['javascript-vars']['interface'])){
		$_GESTOR['javascript-vars']['interface'] = Array();
	}
	
	// ===== Inclusão Interface
	
	$_GESTOR['javascript'][] = '<script src="'.$_GESTOR['url-raiz'].'interface/interface.js?v='.$_GESTOR['biblioteca-interface']['versao'].'"></script>';
	
	// ===== Incluir o gestor listener.
	
	$_GESTOR['pagina'] .= "\n".'	<div id="gestor-listener"></div>';
}

?>