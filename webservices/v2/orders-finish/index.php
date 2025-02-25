<?php
/***********************************************************************************************************************************
	AgeOne Digital Marketing.
	Setor de Desenvolvimento de Sistemas - Desenvolvimento Para Web
	Contato: webmaster@age1.com.br
	
	B2Make
	
	Copyright (c) 2014 AgeOne Digital Marketing

	Permission is hereby granted, free of charge, to any person
	obtaining a copy of this software and associated documentation
	files (the "Software"), to deal in the Software without
	restriction, including without limitation the rights to use,
	copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following
	conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
	OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
	HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	OTHER DEALINGS IN THE SOFTWARE.
***********************************************************************************************************************************/

// Funções de Iniciação do sistema B2make

$_VERSAO_MODULO				=	'1.1.0';
$_INCLUDE_MAILER			=	true;
$_PUBLICO					=	true;
$_LOCAL_ID					=	"ws-orders-finish";
$_CAMINHO_RELATIVO_RAIZ		=	"../../../";
$_PEDIDOS_BAIXA_SLEEP_TIME	=	2;

include($_CAMINHO_RELATIVO_RAIZ."config.php");

function strem_php(){
	$json = file_get_contents('php://input');
	$_REQUEST = json_decode($json, TRUE);
}

function senha_gerar2($limite){
	$CaracteresAceitos = 'abcdefghijklmnopqrstuvxywzABCDEFGHIJKLMNOPQRSTUVXYWZ';
	$CaracteresAceitos_especiais = '@*';
	$CaracteresAceitos2 = '0123456789';
	
	$max = strlen($CaracteresAceitos)-1;
	$max2 = strlen($CaracteresAceitos2)-1;
	$max3 = strlen($CaracteresAceitos_especiais)-1;

	$password = null;

	for($i=0; $i < $limite; $i++) {
		if($i==0){
			$password .= $CaracteresAceitos{mt_rand(0, $max)};
		} else {
			if(mt_rand(0, 7) == 7)
				$password .= $CaracteresAceitos_especiais{mt_rand(0, $max3)};
			else if(mt_rand(0, 3) == 3)
				$password .= $CaracteresAceitos2{mt_rand(0, $max2)};
			else
				$password .= $CaracteresAceitos{mt_rand(0, $max)};
		}
	}

	return $password;
}

function ajax_nao_tem_pedido($params = false){
	global $_PEDIDOS_BAIXA_SLEEP_TIME;
	global $_SYSTEM;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	switch($opcao){
		case 'pedido':
			$resultado = banco_select_name(
				banco_campos_virgulas(Array(
					'id_pedidos',
				))
				,
				"pedidos",
				"WHERE codigo='".$codigo."'"
			);
			
			$motivo = 'errou a senha do pedido ao tentar baixar.';
		break;
		case 'servico':
			$resultado = banco_select_name(
				banco_campos_virgulas(Array(
					'id_pedidos',
				))
				,
				"pedidos_servicos",
				"WHERE codigo='".$codigo."'"
			);
			
			$motivo = 'errou a senha do serviço ao tentar baixar.';
		break;
		case 'baixar':
			switch($pedido_servico){
				case 'pedido':
					$resultado = banco_select_name(
						banco_campos_virgulas(Array(
							'id_pedidos',
						))
						,
						"pedidos",
						"WHERE codigo='".$codigo."'"
					);
					
					$motivo = 'errou a senha do pedido ao confirmar a baixa.';
				break;
				case 'servico':
					$resultado = banco_select_name(
						banco_campos_virgulas(Array(
							'id_pedidos',
						))
						,
						"pedidos_servicos",
						"WHERE codigo='".$codigo."'"
					);
					
					$motivo = 'errou a senha do serviço ao confirmar a baixa.';
				break;
			}
		break;
		
	}
	
	if($resultado){
		$id_pedidos = $resultado[0]['id_pedidos'];
		
		log_banco(Array(
			'id_referencia' => $id_pedidos,
			'grupo' => 'pedidos',
			'valor' => '<b>Administração:</b> o usuário <b>'.$usuario['nome'].'</b> '.$motivo,
		));
	}
	
	sleep($_PEDIDOS_BAIXA_SLEEP_TIME);
	$saida = Array(
		'status' => 'OrderFinishPasswordIncorrect',
		'message' => "NÃO É POSSÍVEL DAR BAIXA! Código e/ou senha incorreto(s)!"),
	);
	
	return $saida;
}

function ajax_status_nao_ativo($params = false){
	global $_SYSTEM;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	if($status == 'F'){
		$resultado3 = banco_select_name
		(
			banco_campos_virgulas(Array(
				'usuario',
			))
			,
			"usuario",
			"WHERE id_usuario='".$id_usuario_baixa."'"
		);
		$resultado4 = banco_select_name
		(
			banco_campos_virgulas(Array(
				't2.nome',
			))
			,
			"usuario_grupo as t1,grupo as t2",
			"WHERE t1.id_usuario='".$id_usuario_baixa."'"
			." AND t1.id_grupo=t2.id_grupo"
			." ORDER BY t2.nome ASC"
		);
		
		$grupos = false;
		if($resultado4){
			$grupos = ' do(s) seguinte(s) ';
			$flag4 = false;
			foreach($resultado4 as $res4){
				$grupos .= ($flag4?', ':'').$res4['t2.nome'];
				$flag4 = true;
			}
			$grupos .= ' ';
		}
		
		$saida = Array(
			'message' => "NÃO É POSSÍVEL DAR BAIXA! Esse ".$opcao_txt." já foi baixado no sistema! O usuário [".$resultado3[0]['usuario']."]".$grupos." fez a baixa na data [".data_hora_from_datetime_to_text($data_baixa."]."),
		);
	} else if($status == 'N' || $status == 'P'){
		$saida = Array(
			'message' => "NÃO É POSSÍVEL DAR BAIXA! Esse ".$opcao_txt." ainda está em processo de pagamento!",
		);
	} else if($status == 'B'){
		$saida = Array(
			'message' => "NÃO É POSSÍVEL DAR BAIXA! Esse ".$opcao_txt." está bloqueado, favor entrar em contato com o administrador da loja para saber como proceder!",
		);
	} else if($status == 'D'){
		$saida = Array(
			'message' => "NÃO É POSSÍVEL DAR BAIXA! Esse ".$opcao_txt." foi excluído, favor entrar em contato com o administrador da loja para saber como proceder!",
		);
	} else if($status == '5'){
		$saida = Array(
			'message' => "NÃO É POSSÍVEL DAR BAIXA! Esse ".$opcao_txt." está em processo de disputa para devolução de dinheiro!",
		);
	} else if($status == '6'){
		$saida = Array(
			'message' => "NÃO É POSSÍVEL DAR BAIXA! Esse ".$opcao_txt." teve o voucher cancelado e o dinheiro devolvido ao comprador!",
		);
	} else if($status == '7'){
		$saida = Array(
			'message' => "NÃO É POSSÍVEL DAR BAIXA! Esse ".$opcao_txt." teve o voucher cancelado!",
		);
	} else {
		$saida = Array(
			'message' => "NÃO É POSSÍVEL DAR BAIXA! Motivo não definido, favor entrar em contato com o suporte para saber como proceder!",
		);
	}
	
	$saida['status'] = 'OrderNotActive';
	
	log_banco(Array(
		'id_referencia' => $id_pedidos,
		'grupo' => 'pedidos',
		'valor' => '<b>Administração:</b> o usuário <b>'.$usuario['nome'].'</b> tentou baixar o '.$opcao_txt.' com status diferente de <b>Pago</b>',
	));
	
	return $saida;
}

function main(){
	global $_SYSTEM;
	global $_HTML;
	global $_WEBSERVICES;
	global $_REMOTE_ADDR;
	global $_PEDIDOS_BAIXA_SLEEP_TIME;
	
	header("Access-Control-Allow-Origin: *");
	strem_php();
	
	if($_REQUEST['user'] && $_REQUEST['token']){
		$permicao = false;
		
		$user = $_REQUEST['user'];
		$token = $_REQUEST['token'];
		
		banco_update
		(
			"sessao_mobile=NULL",
			"usuario",
			"WHERE data_login < NOW() - INTERVAL 30 DAY"
		);
		$usuarios = banco_select_name(
			"*",
			"usuario",
			"WHERE usuario='".$user."' AND status!='D'"
		);
		
		$usuario_mobile = $usuarios[0];
		
		if($usuario_mobile){
			if(
				$usuario_mobile['usuario'] == $user &&
				crypt($token, $usuario_mobile['sessao_mobile']) == $usuario_mobile['sessao_mobile']
			){
				$permicao = true;
			}
		}
		
		if($permicao){
			$loja = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_loja',
				))
				,
				"loja",
				"WHERE id_usuario='".($usuario_mobile['id_usuario_pai'] ? $usuario_mobile['id_usuario_pai'] : $usuario_mobile['id_usuario'])."'"
			);
			
			$id_loja = $loja[0]['id_loja'];
			
			$opcao = $_REQUEST['opcao'];
			$codigo = urldecode($_REQUEST['codigo']);
			
			$usuario = $usuario_mobile;
			$usuario['id_loja'] = $id_loja;
			
			if(
				$codigo &&
				$opcao
			){
				$validado = false;
				
				$codigo_arr = explode('#',$codigo);
				$versao_arr = explode('S',$codigo_arr[1]);
				
				$codigo = 'S'.$versao_arr[1];
				$versao = preg_replace('/V/i', '',$versao_arr[0]);
				$senha = $codigo_arr[2];
				
				$resultado = banco_select_name
				(
					banco_campos_virgulas(Array(
						't1.id_pedidos',
						't1.id_pedidos_servicos',
					))
					,
					"pedidos_servicos as t1,pedidos as t2",
					"WHERE t1.codigo='".$codigo."'"
					." AND t1.id_pedidos=t2.id_pedidos"
					." AND t2.id_loja='".$usuario['id_loja']."'"
				);
				
				if($resultado){
					$validado = true;
				}
				
				if($validado){
					switch($opcao){
						case 'servico':
							$opcao_txt = 'serviço';
							
							$id_pedidos = $resultado[0]['t1.id_pedidos'];
							$id_pedidos_servicos = $resultado[0]['t1.id_pedidos_servicos'];
							
							$senha_correta = false;
							
							$pedidos_servicos_senhas = banco_select_name
							(
								banco_campos_virgulas(Array(
									'senha',
								))
								,
								"pedidos_servicos_senhas",
								"WHERE id_pedidos='".$id_pedidos."'"
								." AND id_pedidos_servicos='".$id_pedidos_servicos."'"
								.($versao ? " AND versao='".$versao."'" : "")
							);
							
							if(crypt($senha, $pedidos_servicos_senhas[0]['senha']) == $pedidos_servicos_senhas[0]['senha']){
								$senha_correta = true;
							}
							
							if($senha_correta){
								$resultado = banco_select_name
								(
									banco_campos_virgulas(Array(
										'id_pedidos',
										'id_servicos',
										'status',
										'data_baixa',
										'id_usuario_baixa',
										'validade',
										'validade_data',
										'validade_tipo',
										'identificacao_nome',
										'identificacao_documento',
										'identificacao_telefone',
										'sub_total',
									))
									,
									"pedidos_servicos",
									"WHERE codigo='".$codigo."'"
								);
								
								$id_servicos = $resultado[0]['id_servicos'];
								$validade = $resultado[0]['validade'];
								$validade_data = $resultado[0]['validade_data'];
								$validade_tipo = $resultado[0]['validade_tipo'];
								$identificacao_nome = $resultado[0]['identificacao_nome'];
								$identificacao_documento = $resultado[0]['identificacao_documento'];
								$identificacao_telefone = $resultado[0]['identificacao_telefone'];
								$sub_total = preparar_float_4_texto($resultado[0]['sub_total']);
								
								if(!$identificacao_nome){
									$loja_usuarios_pedidos = banco_select_name
									(
										banco_campos_virgulas(Array(
											't2.nome',
											't2.ultimo_nome',
											't2.cnpj_selecionado',
											't2.cnpj',
											't2.cpf',
											't2.telefone',
										))
										,
										"loja_usuarios_pedidos as t1,loja_usuarios as t2",
										"WHERE t1.id_loja_usuarios=t2.id_loja_usuarios"
										." AND t1.id_pedidos='".$id_pedidos."'"
									);
									
									$loja_usuarios_pedidos = $loja_usuarios_pedidos[0];
									
									$identificacao_nome = $loja_usuarios_pedidos['t2.nome'].' '.$loja_usuarios_pedidos['t2.ultimo_nome'];
									$identificacao_documento = ($loja_usuarios_pedidos['t2.cnpj_selecionado'] ? $loja_usuarios_pedidos['t2.cnpj'] : $loja_usuarios_pedidos['t2.cpf']);
									$identificacao_telefone = $loja_usuarios_pedidos['t2.telefone'];
								}
								
								$status = $resultado[0]['status'];
								
								if($status == 'A'){
									$servicos = banco_select_name
									(
										banco_campos_virgulas(Array(
											'nome',
											'observacao',
										))
										,
										"servicos",
										"WHERE id_servicos='".$id_servicos."'"
									);
									
									$servico = $servicos[0]['nome'];
									$observacao = $servicos[0]['observacao'];
									
									$resultado = banco_select_name
									(
										banco_campos_virgulas(Array(
											'data',
										))
										,
										"pedidos",
										"WHERE id_pedidos='".$id_pedidos."'"
									);
									
									$data = data_hora_from_datetime_to_text($resultado[0]['data']);
									
									$data_full = $resultado[0]['data'];
									$data_arr = explode(' ',$data_full);
									
									if($validade_tipo == 'D'){
										$validade_formatada = data_hora_from_datetime_to_text($validade_data);
										$validade = (strtotime($validade_data) - strtotime(date('Y-m-d H:i:s')))/86400;
									} else {
										$validade_formatada = date("d/m/Y",strtotime($data_arr[0] . " + ".$validade." day"));
										$validade = floor((strtotime($data_arr[0] . " + ".$validade." day")/86400 - strtotime(date('Y-m-d'))/86400));
									}
									
									if($validade >= 0){
										$saida = Array(
											'status' => 'Ok',
											'nome' => $identificacao_nome,
											'documento' => $identificacao_documento,
											'telefone' => $identificacao_telefone,
											'validade' => $validade_formatada,
											'servico' => $servico,
											'valor' => $sub_total,
											'observacao' => $observacao,
										);
									} else {
										$saida = Array(
											'status' => 'OutOfSelfLife',
											'message' => "NÃO É POSSÍVEL DAR BAIXA! A validade de uso desse serviço está vencida!",
										);
									}
								} else {
									$saida = ajax_status_nao_ativo(Array(
										'usuario' => $usuario,
										'id_pedidos' => $id_pedidos,
										'opcao_txt' => $opcao_txt,
										'status' => $status,
										'data_baixa' => $resultado[0]['data_baixa'],
										'id_usuario_baixa' => $resultado[0]['id_usuario_baixa'],
									));
								}
							} else {
								$saida = ajax_nao_tem_pedido(Array(
									'usuario' => $usuario,
									'codigo' => $codigo,
									'opcao' => $opcao,
								));
							}
						break;
						case 'baixar':
							$protocolo = sha1(md5(senha_gerar2('50')));
							$opcao_txt = 'serviço';
							
							$id_pedidos = $resultado[0]['t1.id_pedidos'];
							$id_pedidos_servicos = $resultado[0]['t1.id_pedidos_servicos'];
							
							$senha_correta = false;
							
							$pedidos_servicos_senhas = banco_select_name
							(
								banco_campos_virgulas(Array(
									'senha',
								))
								,
								"pedidos_servicos_senhas",
								"WHERE id_pedidos='".$id_pedidos."'"
								." AND id_pedidos_servicos='".$id_pedidos_servicos."'"
								.($versao ? " AND versao='".$versao."'" : "")
							);
							
							if(crypt($senha, $pedidos_servicos_senhas[0]['senha']) == $pedidos_servicos_senhas[0]['senha']){
								$senha_correta = true;
							}
							
							if($senha_correta){
								$resultado = banco_select_name
								(
									banco_campos_virgulas(Array(
										'id_pedidos_servicos',
										'id_pedidos',
										'id_servicos',
										'status',
										'data_baixa',
										'id_usuario_baixa',
										'validade',
										'validade_data',
										'validade_tipo',
									))
									,
									"pedidos_servicos",
									"WHERE codigo='".$codigo."'"
								);
								
								$id_pedidos_servicos = $resultado[0]['id_pedidos_servicos'];
								$id_pedidos = $resultado[0]['id_pedidos'];
								$status = $resultado[0]['status'];
								$validade = $resultado[0]['validade'];
								$validade_data = $resultado[0]['validade_data'];
								$validade_tipo = $resultado[0]['validade_tipo'];
								
								if($status == 'A'){
									$resultado = banco_select_name
									(
										banco_campos_virgulas(Array(
											'data',
										))
										,
										"pedidos",
										"WHERE id_pedidos='".$id_pedidos."'"
									);
									
									$data_full = $resultado[0]['data'];
									$data_arr = explode(' ',$data_full);
									
									if($validade_tipo == 'D'){
										$validade = (strtotime($validade_data) - strtotime(date('Y-m-d H:i:s')))/86400;
									} else {
										$validade = floor((strtotime($data_arr[0] . " + ".$validade." day")/86400 - strtotime(date('Y-m-d'))/86400));
									}
									
									if($validade >= 0){
										$id_usuario_baixa = $usuario['id_usuario'];
										
										banco_update
										(
											"status='F',".
											"id_usuario_baixa='".$id_usuario_baixa."',".
											"data_baixa=NOW(),".
											"protocolo_baixa='".$protocolo."'",
											"pedidos_servicos",
											"WHERE id_pedidos_servicos='".$id_pedidos_servicos."'"
										);
										
										$resultado2 = banco_select_name
										(
											banco_campos_virgulas(Array(
												'id_pedidos_servicos',
											))
											,
											"pedidos_servicos",
											"WHERE id_pedidos='".$id_pedidos."'"
											." AND status='A'"
										);
										
										if(!$resultado2){
											banco_update
											(
												"status='F',".
												"id_usuario_baixa='".$id_usuario_baixa."',".
												"data_baixa=NOW(),".
												"protocolo_baixa='".$protocolo."'",
												"pedidos",
												"WHERE id_pedidos='".$id_pedidos."'"
											);
										}
										
										$saida = Array(
											'status' => 'Ok',
											'baixado' => true,
											'protocolo' => $protocolo,
											'id_pedidos' => $id_pedidos,
											'id_pedidos_servicos' => $id_pedidos_servicos,
											'message' => "Baixado com sucesso! Protocolo: ".$protocolo,
										);
										
										log_banco(Array(
											'id_referencia' => $id_pedidos,
											'grupo' => 'pedidos',
											'valor' => '<b>Administração:</b> o usuário <b>'.$usuario['nome'].'</b> baixou o '.$opcao_txt.' de código: <b>'.$codigo.'</b> - protocolo: <b>'.$protocolo.'</b>',
										));
									} else {
										$saida = Array(
											'status' => 'OutOfSelfLife',
											'message' => "NÃO É POSSÍVEL DAR BAIXA! A validade de uso desse serviço está vencida!",
										);
									}
								} else {
									$saida = ajax_status_nao_ativo(Array(
										'usuario' => $usuario,
										'id_pedidos' => $id_pedidos,
										'opcao_txt' => $opcao_txt,
										'status' => $status,
										'data_baixa' => $resultado[0]['data_baixa'],
										'id_usuario_baixa' => $resultado[0]['id_usuario_baixa'],
									));
								}
							} else {
								$saida = ajax_nao_tem_pedido(Array(
									'usuario' => $usuario,
									'codigo' => $codigo,
									'opcao' => $opcao,
									'pedido_servico' => $pedido_servico,
								));
							}
						break;
						default:
							$saida = Array(
								'status' => 'ErrorDontInformed',
								'message' => "NÃO É POSSÍVEL DAR BAIXA! Motivo não definido, favor entrar em contato com o suporte para saber como proceder! ERRO: 3",
							);
						
					}
				} else {
					sleep($_PEDIDOS_BAIXA_SLEEP_TIME);
					$saida = Array(
						'baixado' => false,
						'status' => 'StoreFromOtherUser',
						'message' => "Esse serviço não pertence a sua loja!",
					);
				}
			} else {
				sleep($_PEDIDOS_BAIXA_SLEEP_TIME);
				$saida = Array(
					'baixado' => false,
					'status' => 'FieldsNotInformed',
					'message' => "É necessário definir todos os campos antes de executar",
				);
			}
			
			if($saida['baixado']){
				$protocolo = $saida['protocolo'];
				$id_pedidos = $saida['id_pedidos'];
				$id_pedidos_servicos = $saida['id_pedidos_servicos'];
				
				$loja = banco_select_name
				(
					banco_campos_virgulas(Array(
						'nome',
						'email_assinatura',
					))
					,
					"loja",
					"WHERE id_loja='".$usuario['id_loja']."'"
				);
				
				$loja_usuarios_pedidos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_loja_usuarios',
					))
					,
					"loja_usuarios_pedidos",
					"WHERE id_pedidos='".$id_pedidos."'"
				);
				
				$loja_usuarios = banco_select_name
				(
					banco_campos_virgulas(Array(
						'nome',
						'email',
					))
					,
					"loja_usuarios",
					"WHERE id_loja_usuarios='".$loja_usuarios_pedidos[0]['id_loja_usuarios']."'"
				);
				
				$loja_nome = $loja[0]['nome'];
				$loja_email_assinatura = $loja[0]['email_assinatura'];
				$usuario_nome = $loja_usuarios[0]['nome'];
				$usuario_email = $loja_usuarios[0]['email'];
				
				$loja_email_assinatura = modelo_var_troca_tudo($loja_email_assinatura,"#loja-nome#",($loja_nome?$loja_nome:$_HTML['TITULO']));
				
				$assunto = 'Voucher '.$codigo.' baixado no sistema.';
				$mensagem = '	<p>Ol&aacute;, #nome#.</p>
	<p>Seu voucher de c&oacute;digo #codigo# foi utilizado no estabelecimento e baixado no sistema.</p>
	<p>Protocolo de baixa: <b>#protocolo#</b>
	<!-- observacao < --><br>
	Observa&ccedil;&atilde;o de baixa: #observacao#<!-- observacao > --></p>
	<p>#assinatura-loja#</p>
';
				
				$mensagem = modelo_var_troca_tudo($mensagem,"#nome#",$usuario_nome);
				$mensagem = modelo_var_troca_tudo($mensagem,"#codigo#",$codigo);
				$mensagem = modelo_var_troca_tudo($mensagem,"#protocolo#",$protocolo);
				
				if(!$observacao_baixa){
					$cel_nome = 'observacao'; $mensagem = modelo_tag_in($mensagem,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
				} else {
					$mensagem = modelo_var_troca_tudo($mensagem,"#observacao#",$observacao_baixa);
				}
				
				$mensagem = modelo_var_troca_tudo($mensagem,"#assinatura-loja#",$loja_email_assinatura);
				
				email_enviar(Array(
					'from_name' => ($loja_nome?$loja_nome:$_HTML['TITULO']),
					'email_nome' => $usuario_nome,
					'email' => $usuario_email,
					'assunto' => $assunto,
					'mensagem' => $mensagem,
					'nao_inserir_assinatura' => true,
				));
			}
		} else {
			sleep($_PEDIDOS_BAIXA_SLEEP_TIME);
			$saida = Array(
				'status' => 'UserDontPermited',
				'message' => 'Usuário não tem acesso ao sistema. É obrigatório informar ambos os campos de forma correta.',
			);
		}
	} else {
		sleep($_PEDIDOS_BAIXA_SLEEP_TIME);
		$saida = Array(
			'status' => 'UserOrTokenDontInformed',
			'message' => 'Usuário e/ou Token não informados. É obrigatório informar ambos os campos de forma correta.',
		);
	}
	
	echo json_encode($saida);
}

main();

?>