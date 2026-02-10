<?php
/**
 * Biblioteca de validação e processamento de formulários.
 *
 * Fornece funções para validação client-side e server-side de formulários,
 * incluindo regras personalizadas, campos obrigatórios, validação de email,
 * integração com Google reCAPTCHA v2 e v3.
 *
 * @package Conn2Flow
 * @subpackage Bibliotecas
 * @version 1.0.4
 */

global $_GESTOR;

// Registro da versão da biblioteca no sistema global
$_GESTOR['biblioteca-formulario']							=	Array(
	'versao' => '1.0.0',
);

// ===== Funções auxiliares

/**
 * Inclui JavaScript da biblioteca de formulários na página.
 *
 * Carrega o arquivo JavaScript necessário para validações client-side.
 * Usa controle para incluir apenas uma vez por requisição.
 * 
 * @param array|false $params Parâmetros da função.
 * 
 * @param array $params['js_vars'] Array de variáveis JavaScript a serem incluídas (opcional).
 * 
 * @return void
 */
function formulario_incluir_js($params = false){
	global $_GESTOR;

	if($params)foreach($params as $var => $val)$$var = $val;

	// Variáveis padrões 
	$js_padroes = [
		'teste' => '123',
	];

	// Mesclar variáveis personalizadas com as padrões
	if(isset($js_vars) && is_array($js_vars)){
		$js_padroes = array_merge($js_padroes, $js_vars);
	}

	// Incluir variáveis JS para a página
	gestor_js_variavel_incluir('form',$js_padroes);

	// Incluir o JavaScript da biblioteca de formulários
	gestor_pagina_javascript_incluir('biblioteca','formulario');
}

// ===== Funções principais

/**
 * Configura validação de formulário com regras personalizadas.
 *
 * Sistema completo de validação client-side usando JavaScript.
 * Suporta múltiplas regras (email, CPF, CNPJ, telefone, comparação, regex, etc).
 * Gera código JavaScript inline com configurações de validação.
 *
 * @global array $_GESTOR Sistema global com configurações.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['formId'] ID do formulário HTML (obrigatório).
 * @param array $params['validacao'] Array de regras de validação (opcional).
 * @param string $params['validacao'][]['regra'] Nome da regra: 'email', 'cpf', 'cnpj', 'telefone', 'email-comparacao', etc (obrigatório).
 * @param string $params['validacao'][]['campo'] Nome do campo HTML a validar (obrigatório).
 * @param string $params['validacao'][]['label'] Label do campo para mensagens de erro (obrigatório).
 * @param string $params['validacao'][]['identificador'] ID alternativo do campo (opcional).
 * @param array $params['validacao'][]['removerRegra'] Regras padrão a remover (opcional).
 * @param array $params['validacao'][]['comparacao'] Dados para regra 'email-comparacao' (opcional).
 * @param array $params['regrasExtra'] Regras adicionais além das padrões (opcional).
 * 
 * @return void
 */
function formulario_validacao($params = false){
	global $_GESTOR;

	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val;
		// regex - String - Obrigatório - Regex que será usado pelo validador de formulário.
		// regexPermitedChars - String - Obrigatório - Caracteres permitidos que será mostrado junto com a mensagem de erro.
	
	// Se regra = 'regexNecessary'
		
		// regex - String - Obrigatório - Regex que será usado pelo validador de formulário.
		// regexNecessaryChars - String - Obrigatório - Caracteres necessários que será mostrado junto com a mensagem de erro.
	
	// Se regra = 'manual'
		
		// regrasManuais - Array - Opcional - Conjunto regras definidas manualmente.
	
	// ===== 
	
	if(isset($validacao) && isset($formId)){
		foreach($validacao as $regra){
			switch($regra['regra']){
				case 'manual':
					$regras_validacao[$regra['campo']] = Array(
						'rules' => $regra['regrasManuais'],
					);
				break;
				case 'texto-obrigatorio':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-min-length'));
					$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
					
					$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-max-length'));
					$prompt[3] = modelo_var_troca($prompt[3],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'notEmpty',
								'prompt' => $prompt[1],
							),
							Array(
								'type' => 'minLength[3]',
								'prompt' => $prompt[2],
							),
							Array(
								'type' => 'maxLength[100]',
								'prompt' => $prompt[3],
							),
						)
					);
				break;
				case 'texto-obrigatorio-verificar-campo':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-min-length'));
					$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
					
					$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-max-length'));
					$prompt[3] = modelo_var_troca($prompt[3],"#label#",$regra['label']);
					
					$prompt[4] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-verify-field'));
					$prompt[4] = modelo_var_troca_tudo($prompt[4],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'notEmpty',
								'prompt' => $prompt[1],
							),
							Array(
								'type' => 'minLength[3]',
								'prompt' => $prompt[2],
							),
							Array(
								'type' => 'maxLength[100]',
								'prompt' => $prompt[3],
							),
						)
					);
					
					if(isset($regra['identificador'])){
						$validarCampos[$regra['identificador']] = Array(
							'prompt' => $prompt[4],
							'campo' => $regra['campo'],
						);
					} else {
						$validarCampos[$regra['campo']] = Array(
							'prompt' => $prompt[4],
						);
					}
				break;
				case 'selecao-obrigatorio':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-select'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'notEmpty',
								'prompt' => $prompt[1],
							),
						)
					);
				break;
				case 'nao-vazio':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'notEmpty',
								'prompt' => $prompt[1],
							),
						)
					);
				break;
				case 'email':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email'));
					$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'notEmpty',
								'prompt' => $prompt[1],
							),
							Array(
								'type' => 'email',
								'prompt' => $prompt[2],
							),
						)
					);
				break;
				case 'senha':
					$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
					$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
					
					$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-min-length-password'));
					$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
					
					$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-max-length'));
					$prompt[3] = modelo_var_troca($prompt[3],"#label#",$regra['label']);
					
					$prompt[4] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-password-chars'));
					$prompt[4] = modelo_var_troca($prompt[4],"#label#",$regra['label']);
					
					$regras_validacao[$regra['campo']] = Array(
						'rules' => Array(
							Array(
								'type' => 'notEmpty',
								'prompt' => $prompt[1],
							),
							Array(
								'type' => 'minLength[12]',
								'prompt' => $prompt[2],
							),
							Array(
								'type' => 'maxLength[100]',
								'prompt' => $prompt[3],
							),
							Array(
								'type' => 'regExp[/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])/]',
								'prompt' => $prompt[4],
							),
						)
					);
				break;
				case 'email-comparacao':
					if(isset($regra['comparcao'])){
						if(isset($regra['comparcao']['id']) && isset($regra['comparcao']['campo-1']) && isset($regra['comparcao']['campo-2'])){
							$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
							$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
							
							$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email'));
							$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
							
							$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email-compare'));
							$prompt[3] = modelo_var_troca($prompt[3],"#campo-1#",$regra['comparcao']['campo-1']);
							$prompt[3] = modelo_var_troca($prompt[3],"#campo-2#",$regra['comparcao']['campo-2']);
							
							$regras_validacao[$regra['campo']] = Array(
								'rules' => Array(
									Array(
										'type' => 'notEmpty',
										'prompt' => $prompt[1],
									),
									Array(
										'type' => 'email',
										'prompt' => $prompt[2],
									),
									Array(
										'type' => 'match['.$regra['comparcao']['id'].']',
										'prompt' => $prompt[3],
									),
								)
							);
						}
					}
				break;
				case 'senha-comparacao':
					if(isset($regra['comparcao'])){
						if(isset($regra['comparcao']['id']) && isset($regra['comparcao']['campo-1']) && isset($regra['comparcao']['campo-2'])){
							$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
							$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
							
							$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-min-length-password'));
							$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
							
							$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-max-length'));
							$prompt[3] = modelo_var_troca($prompt[3],"#label#",$regra['label']);
							
							$prompt[4] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email-compare'));
							$prompt[4] = modelo_var_troca($prompt[4],"#campo-1#",$regra['comparcao']['campo-1']);
							$prompt[4] = modelo_var_troca($prompt[4],"#campo-2#",$regra['comparcao']['campo-2']);
							
							$prompt[5] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-password-chars'));
							$prompt[5] = modelo_var_troca($prompt[5],"#label#",$regra['label']);
							
							$regras_validacao[$regra['campo']] = Array(
								'rules' => Array(
									Array(
										'type' => 'notEmpty',
										'prompt' => $prompt[1],
									),
									Array(
										'type' => 'minLength[12]',
										'prompt' => $prompt[2],
									),
									Array(
										'type' => 'maxLength[100]',
										'prompt' => $prompt[3],
									),
									Array(
										'type' => 'match['.$regra['comparcao']['id'].']',
										'prompt' => $prompt[4],
									),
									Array(
										'type' => 'regExp[/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])/]',
										'prompt' => $prompt[5],
									),
								)
							);
						}
					}
				break;
				case 'email-comparacao-verificar-campo':
					if(isset($regra['comparcao'])){
						if(isset($regra['comparcao']['id']) && isset($regra['comparcao']['campo-1']) && isset($regra['comparcao']['campo-2'])){
							$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-empty'));
							$prompt[1] = modelo_var_troca($prompt[1],"#label#",$regra['label']);
							
							$prompt[2] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email'));
							$prompt[2] = modelo_var_troca($prompt[2],"#label#",$regra['label']);
							
							$prompt[3] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-email-compare'));
							$prompt[3] = modelo_var_troca($prompt[3],"#campo-1#",$regra['comparcao']['campo-1']);
							$prompt[3] = modelo_var_troca($prompt[3],"#campo-2#",$regra['comparcao']['campo-2']);
							
							$prompt[4] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-verify-field'));
							$prompt[4] = modelo_var_troca_tudo($prompt[4],"#label#",$regra['label']);
							
							$regras_validacao[$regra['campo']] = Array(
								'rules' => Array(
									Array(
										'type' => 'notEmpty',
										'prompt' => $prompt[1],
									),
									Array(
										'type' => 'email',
										'prompt' => $prompt[2],
									),
									Array(
										'type' => 'match['.$regra['comparcao']['id'].']',
										'prompt' => $prompt[3],
									),
								)
							);
							
							if(isset($regra['identificador'])){
								$validarCampos[$regra['identificador']] = Array(
									'prompt' => $prompt[4],
									'campo' => $regra['campo'],
								);
							} else {
								$validarCampos[$regra['campo']] = Array(
									'prompt' => $prompt[4],
								);
							}
						}
					}
				break;
			}
			
			if(isset($regra['regrasExtra'])){
				$regrasExtra = $regra['regrasExtra'];
				foreach($regrasExtra as $regraExtra){
					switch($regraExtra['regra']){
						case 'regexPermited':
							$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-regex-permited-chars'));
							$prompt[1] = modelo_var_troca_tudo($prompt[1],"#label#",$regra['label']);
							$prompt[1] = modelo_var_troca_tudo($prompt[1],"#permited-chars#",$regraExtra['regexPermitedChars']);
							
							$regras_validacao[$regra['campo']]['rules'][] = Array(
								'type' => 'regExp['.$regraExtra['regex'].']',
								'prompt' => $prompt[1],
							);
						break;
						case 'regexNecessary':
							$prompt[1] = gestor_variaveis(Array('modulo' => 'interface','id' => 'validation-regex-necessary-chars'));
							$prompt[1] = modelo_var_troca_tudo($prompt[1],"#label#",$regra['label']);
							$prompt[1] = modelo_var_troca_tudo($prompt[1],"#necessary-chars#",$regraExtra['regexNecessaryChars']);
							
							$regras_validacao[$regra['campo']]['rules'][] = Array(
								'type' => 'regExp['.$regraExtra['regex'].']',
								'prompt' => $prompt[1],
							);
						break;
					}
				}
			}
			
			if(isset($regra['removerRegra'])){
				$rules = $regras_validacao[$regra['campo']]['rules'];
				unset($rulesAux);
				
				foreach($rules as $rule){
					$removeuRegra = false;
					foreach($regra['removerRegra'] as $removerRegra){
						if($rule['type'] == $removerRegra){
							$removeuRegra = true;
							break;
						}
					}
					
					if(!$removeuRegra){
						$rulesAux[] = $rule;
					}
				}
				
				if(isset($rulesAux)){
					$regras_validacao[$regra['campo']]['rules'] = $rulesAux;
				}
			}
			
			if(isset($regra['identificador'])){
				$regras_validacao[$regra['campo']]['identifier'] = $regra['identificador'];
			}
		}
		
		// ===== Inclui as regras de validação no javascript
		
		if(isset($regras_validacao)){
			if(!isset($_GESTOR['javascript-vars']['formulario'])){
				$_GESTOR['javascript-vars']['formulario'] = Array();
			}
		
			$_GESTOR['javascript-vars']['formulario'][$formId]['regrasValidacao'] = $regras_validacao;
		}
		
		if(isset($validarCampos)){
			if(!isset($_GESTOR['javascript-vars']['formulario'])){
				$_GESTOR['javascript-vars']['formulario'] = Array();
			}
		
			$_GESTOR['javascript-vars']['formulario']['validarCampos'] = $validarCampos;
		}
		
		// ===== Incluir JS do módulo.
		
		formulario_incluir_js();
	}
}

/**
 * Valida campos obrigatórios no servidor (server-side).
 *
 * Executa validação de campos após submissão do formulário.
 * Suporta validação de texto (min/max caracteres), seleção e email.
 * Exibe alertas e redireciona se validação falhar.
 *
 * @global array $_GESTOR Sistema global com configurações.
 * 
 * @param array|false $params Parâmetros da função.
 * @param string $params['redirect'] URL de redirecionamento em caso de erro (opcional, usa reload se omitido).
 * @param array $params['campos'] Array de campos a validar (opcional).
 * @param string $params['campos'][]['regra'] Tipo de validação: 'texto-obrigatorio', 'selecao-obrigatorio', 'email-obrigatorio' (obrigatório).
 * @param string $params['campos'][]['campo'] Nome do campo no $_REQUEST (obrigatório).
 * @param string $params['campos'][]['label'] Label do campo para mensagens (obrigatório).
 * @param int $params['campos'][]['min'] Tamanho mínimo para 'texto-obrigatorio' (opcional, padrão 3).
 * @param int $params['campos'][]['max'] Tamanho máximo para 'texto-obrigatorio' (opcional, padrão 100).
 * 
 * @return void Exibe alerta e redireciona se validação falhar.
 */
function formulario_validacao_campos_obrigatorios($params = false){
	global $_GESTOR;

	// Extrai parâmetros
	if($params)foreach($params as $var => $val)$$var = $val; 
	
	// Valida cada campo conforme regra especificada
	if(isset($campos)){
		foreach($campos as $campo){
			switch($campo['regra']){
				case 'texto-obrigatorio':
					// Valida tamanho mínimo e máximo do texto
					$min = (isset($campo['min']) ? $campo['min'] : 3);
					$max = (isset($campo['max']) ? $campo['max'] : 100);
					
					$len = strlen($_REQUEST[$campo['campo']]);
					
					if($len < $min){
						$naoValidou = true;
					} else if($len > $max){
						$naoValidou = true;
					}
					
					if(isset($naoValidou)){
						$naoValidouMsgAlerta = gestor_variaveis(Array('modulo' => 'formulario','id' => 'validation-min-max-length'));
						
						$naoValidouMsgAlerta = modelo_var_troca($naoValidouMsgAlerta,"#label#",$campo['label']);
						$naoValidouMsgAlerta = modelo_var_troca($naoValidouMsgAlerta,"#min#",$min);
						$naoValidouMsgAlerta = modelo_var_troca($naoValidouMsgAlerta,"#max#",$max);
					}
				break;
				case 'selecao-obrigatorio':
					// Valida se campo de seleção tem valor
					if(!existe($_REQUEST[$campo['campo']])){
						$naoValidouMsgAlerta = gestor_variaveis(Array('modulo' => 'formulario','id' => 'validation-select'));
						
						$naoValidouMsgAlerta = modelo_var_troca($naoValidouMsgAlerta,"#label#",$campo['label']);
						
						$naoValidou = true;
					}
				break;
				case 'email-obrigatorio':
					// Valida formato de email usando regex
					$email = $_REQUEST[$campo['campo']];
					$regex = '/^[^0-9][_a-z0-9-]+(\.[_a-z0-9-]+)*@([a-z0-9-]{2,})+(\.[a-z0-9-]{2,})*$/';
					
					if(!preg_match($regex, $email)){
						$naoValidou = true;
						
						$naoValidouMsgAlerta = gestor_variaveis(Array('modulo' => 'formulario','id' => 'validation-email'));
						
						$naoValidouMsgAlerta = modelo_var_troca($naoValidouMsgAlerta,"#label#",$campo['label']);
					}
				break;
			}
			
			// Para na primeira validação que falhar
			if(isset($naoValidou)){
				break;
			}
		}
	}
	
	// Exibe alerta se validação falhou
	if(isset($naoValidouMsgAlerta)){
		interface_alerta(Array('msg' => $naoValidouMsgAlerta));
	}
	
	// Redireciona se validação falhou
	if(isset($naoValidou)){
		if(isset($redirect)){
			gestor_redirecionar($redirect);
		} else {
			gestor_reload_url();
		}
	}
}

/**
 * Obtém chave do site Google reCAPTCHA.
 *
 * Busca configuração de reCAPTCHA (v2 ou v3) no banco de dados.
 * Retorna chave pública do site se ativo, null caso contrário.
 *
 * @global array $_GESTOR Sistema global com configurações.
 * 
 * @return string|null Chave pública do site reCAPTCHA ou null se desativado.
 */
function formulario_google_recaptcha(){
	global $_GESTOR;
	
	// Busca configurações de reCAPTCHA no banco
	$variaveis = banco_select(Array(
		'tabela' => 'variaveis',
		'campos' => Array(
			'id',
			'valor',
		),
		'extra' => 
			"WHERE modulo='google-recaptcha'"
	));
	
	// Processa configurações encontradas
	if($variaveis){
		foreach($variaveis as $variavel){
			$googleRecaptcha[$variavel['id']] = $variavel['valor'];
		}
		
		// Determina tipo e chave conforme versão ativa
		if(isset($googleRecaptcha['tipo'])){
			switch($googleRecaptcha['tipo']){
				case 'recaptcha-v3':
					if(isset($googleRecaptcha['chave-site'])){
						if($googleRecaptcha['chave-site']){
							$chave = $googleRecaptcha['chave-site'];
						}
					}
					if(isset($googleRecaptcha['ativo'])){
						if($googleRecaptcha['ativo']){
							$ativo = true;
						}
					}
				break;
				case 'recaptcha-v2':
					if(isset($googleRecaptcha['chave-site-v2'])){
						if($googleRecaptcha['chave-site-v2']){
							$chave = $googleRecaptcha['chave-site-v2'];
						}
					}
					if(isset($googleRecaptcha['ativo-v2'])){
						if($googleRecaptcha['ativo-v2']){
							$ativo = true;
						}
					}
				break;
			}
		}
	}
	
	// Retorna chave se ativo, null caso contrário
	if(isset($ativo) && isset($chave)){
		return $chave;
	} else {
		return null;
	}
}

/**
 * Obtém tipo de Google reCAPTCHA configurado.
 *
 * Verifica qual versão do reCAPTCHA está ativa (v2 ou v3).
 * Retorna string com tipo ou null se desativado.
 *
 * @global array $_GESTOR Sistema global com configurações.
 * 
 * @return string|null 'recaptcha-v2' ou 'recaptcha-v3', ou null se desativado.
 */
function formulario_google_recaptcha_tipo(){
	global $_GESTOR;
	
	$variaveis = banco_select(Array(
		'tabela' => 'variaveis',
		'campos' => Array(
			'id',
			'valor',
		),
		'extra' => 
			"WHERE modulo='google-recaptcha'"
			." AND id='tipo'"
	));
	
	if($variaveis){
		foreach($variaveis as $variavel){
			$googleRecaptcha[$variavel['id']] = $variavel['valor'];
		}
		
		if(isset($googleRecaptcha['tipo'])){
			if($googleRecaptcha['tipo']){
				return $googleRecaptcha['tipo'];
			}
		}
	}
	
	return null;
}

?>