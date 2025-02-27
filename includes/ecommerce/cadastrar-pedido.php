<?php
/***********************************************************************************************************************************
	AgeOne Digital Marketing.
	Setor de Desenvolvimento de Sistemas - Desenvolvimento Para Web
	Contato: webmaster@ageone.com.br
	
	Copyright (c) 2012 AgeOne Digital Marketing

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

// Funções Locais

function cadastrar_pedido_calcular_frete($params = false){
	global $_PROJETO;
	
	if($params)foreach($params as $var => $val)$$var = $val;
	
	$produtos = explode(',',$produtos_frete);
	$quantidades = explode(',',$quantidades_frete);
	
	$count = 0;
	
	foreach($produtos as $produto){
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				'peso',
				'largura',
				'altura',
				'comprimento',
			))
			,
			"produtos",
			"WHERE id_produtos='".$produto."'"
		);
		
		$peso += (int)$quantidades[$count]*($resultado[0]['peso']?(float)$resultado[0]['peso']:0);
		$cubagem += (int)$quantidades[$count]*($resultado[0]['largura']?(int)$resultado[0]['largura']:0)*($resultado[0]['altura']?(int)$resultado[0]['altura']:0)*($resultado[0]['comprimento']?(int)$resultado[0]['comprimento']:0);
		
		$count++;
	}
	
	if($cubagem > 0){
		$dimensoes = floor(pow($cubagem,(1/3)));
	} else {
		$dimensoes = 0;
	}
	
	$comprimento = ($dimensoes < 16 ? 16 : $dimensoes);
	
	$peso = ceil($peso);
	
	$cep = preg_replace('/\./i', '', $cep);
	$cep = preg_replace('/\-/i', '', $cep);
	
	$data['nCdEmpresa'] = '';
	$data['sDsSenha'] = '';
	$data['sCepOrigem'] = $_PROJETO['ecommerce']['cep_origem'];
	$data['sCepDestino'] = $cep;
	$data['nVlPeso'] = $peso;
	$data['nCdFormato'] = '1';
	$data['nVlComprimento'] = $comprimento;
	$data['nVlAltura'] = $comprimento;
	$data['nVlLargura'] = $comprimento;
	$data['nVlDiametro'] = '0';
	$data['sCdMaoPropria'] = 's';
	$data['nVlValorDeclarado'] = '0';
	$data['sCdAvisoRecebimento'] = 'n';
	$data['StrRetorno'] = 'xml';
	$data['nCdServico'] = '40010,41106';
	
	$data = http_build_query($data);
	$url = "http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx";
	
	$curl = curl_init($url . '?' .  $data);
	
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	
	$result = curl_exec($curl);
	
	$result = simplexml_load_string($result);
	
	if($result){
		foreach($result -> cServico as $row) {
			if($row -> Erro == 0) {
				if($frete_codigo == (string)$row->Codigo){
					switch($frete_codigo){
						case '40010': $tipo = '1'; break;
						case '41106': $tipo = '2'; break;
						default: $tipo = '3';
					}
	
					return Array(
						'tipo' => $tipo,
						'valor' => (string)$row->Valor,
						'prazo' => (string)$row->PrazoEntrega,
					);
				}
			} else {
				return null;
			}
		}
	} else {
		return Array(
			'tipo' => '3',
			'valor' => '0',
			'prazo' => '0',
		);
	}
	
	return null;
}

function cadastrar_pedido_main(){
	global $_SYSTEM;
	global $_PROJETO;
	global $_CONEXAO_BANCO;
	global $_VARIAVEIS_JS;
	global $_ALERTA;
	
	if($_SESSION[$_SYSTEM['ID'].'ecommerce-itens']){
		if($_SESSION[$_SYSTEM['ID'].'ecommerce-id_usuario']){
			$id_usuario = $_SESSION[$_SYSTEM['ID'].'ecommerce-id_usuario'];
			$_SESSION[$_SYSTEM['ID'].'ecommerce-id_usuario'] = false;
		} else {
			$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
			$id_usuario = $usuario['id_usuario'];
		}
		
		$itens = $_SESSION[$_SYSTEM['ID'].'ecommerce-itens'];
		$cupom = $_SESSION[$_SYSTEM['ID'].'ecommerce-cupom'];
		$cep = $_SESSION[$_SYSTEM['ID'].'ecommerce-cep'];
		$frete_codigo = $_SESSION[$_SYSTEM['ID'].'ecommerce-frete_codigo'];
		
		$_SESSION[$_SYSTEM['ID'].'ecommerce-itens'] = false;
		$_SESSION[$_SYSTEM['ID'].'ecommerce-cupom'] = false;
		$_SESSION[$_SYSTEM['ID'].'ecommerce-cep'] = false;
		$_SESSION[$_SYSTEM['ID'].'ecommerce-frete_codigo'] = false;
		$_SESSION[$_SYSTEM['ID'].'ecommerce-limpar-carrinho'] = true;
		
		$itens_arr = explode(';',$itens);
		
		if($_PROJETO['ecommerce'])
		if($_PROJETO['ecommerce']['produtos']){
			$loja_online_produtos = true;
		}
		
		if($loja_online_produtos){
			if($itens_arr){
				if(!$_CONEXAO_BANCO)$connect_db = true;
				if($connect_db)banco_conectar();
				
				// ============================== Verificar se tem quantidade em estoque de todos os itens
				
				if($itens_arr)
				foreach($itens_arr as $item){
					list($id_produtos,$quant) = explode(',',$item);
					
					$resultado = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_produtos',
						))
						,
						"produtos",
						"WHERE id_produtos='".$id_produtos."'"
						." AND quantidade - ".$quant." >= 0"
						." AND status='A'"
					);
					
					if($resultado){
						
					} else {
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'nome',
							))
							,
							"produtos",
							"WHERE id_produtos='".$id_produtos."'"
						);
						
						$flag = true;
						$lista_sem_estoque .= '<li>'.$resultado[0]['nome'].'</li>'."\n";
						
						$erro = '<p><b style="color:red;">Não foi possível registrar seu pedido</b></p><p>Produto(s) indisponíveis no momento:</p><ul>'.$lista_sem_estoque.'</ul>';
					}
				}
				
				// ============================== Verificar cupom de desconto quando houver
				
				if($cupom){
					$cupom_desconto = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_cupom_desconto',
							'max_usos',
							'desconto',
							'data_inicio',
							'data_fim',
						))
						,
						"cupom_desconto",
						"WHERE codigo='".$cupom."'"
						." AND status='A'"
					);
					
					if($cupom_desconto){
						$cupom_desconto_pedidos = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_cupom_desconto',
							))
							,
							"cupom_desconto_pedidos",
							"WHERE id_cupom_desconto='".$cupom_desconto[0]['id_cupom_desconto']."'"
						);
						
						$cupom_de_ate = true;
						
						if($cupom_desconto[0]['data_inicio']){
							$cupom_de_ate = true;
							$data_inicio = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_cupom_desconto',
								))
								,
								"cupom_desconto",
								"WHERE id_cupom_desconto='".$cupom_desconto[0]['id_cupom_desconto']."'"
								." AND data_inicio <= NOW()"
							);
							
							if(!$data_inicio){
								$cupom_de_ate = false;
							}
						}
						
						if($cupom_desconto[0]['data_fim']){
							$cupom_de_ate = true;
							$data_fim = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_cupom_desconto',
								))
								,
								"cupom_desconto",
								"WHERE id_cupom_desconto='".$cupom_desconto[0]['id_cupom_desconto']."'"
								." AND data_fim >= NOW()"
							);
							
							if(!$data_fim){
								$cupom_de_ate = false;
							}
						}
						
						if($cupom_de_ate){
							if($cupom_desconto[0]['max_usos'] > count($cupom_desconto_pedidos)){
								$desconto_cupom = $cupom_desconto[0]['desconto'];
								$desconto_cupom_id = $cupom_desconto[0]['id_cupom_desconto'];
							} else {
								$flag = true;
								$erro = '<p>Não é possível mais usar esse cupom de desconto pois o mesmo já excedeu a quantidade máxima de usos.</p>';
							}
						} else {
							$flag = true;
							$erro = '<p>Não é possível mais usar esse cupom de desconto pois o mesmo está fora do período de validade.</p>';
						}
					} else {
						$flag = true;
						$erro = '<p>Cupom inválido!</p>';
					}
				}
				
				if($flag){
					$_ALERTA .= $erro;
					redirecionar('/');
				} else {
					// ============================== Cadastrar pedido
					$campos = null;
					
					$campo_nome = "data"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
					$campo_nome = "senha"; $campo_valor = zero_a_esquerda(rand(1,9999),4); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "status"; $campo_valor = 'N'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					
					banco_insert_name
					(
						$campos,
						"pedidos"
					);
					
					$id_pedidos = banco_last_id();
					
					$valor_total = 0;
					
					// ============================== Cadastrar itens do pedido
					
					foreach($itens_arr as $item){
						list($id_produtos,$quant) = explode(',',$item);
						
						$produtos_frete = $produtos_frete + ($produtos_frete ? ',':'') + $id_produtos;
						$quantidades_frete = $quantidades_frete + ($quantidades_frete ? ',':'') + $quant;
						
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'nome',
								'preco',
								'validade',
								'desconto',
								'desconto_de',
								'desconto_ate',
							))
							,
							"produtos",
							"WHERE id_produtos='".$id_produtos."'"
						);
						
						banco_update
						(
							"quantidade = (quantidade - ".$quant.")",
							"produtos",
							"WHERE id_produtos='".$id_produtos."'"
						);
						
						$time = time();
						if($resultado[0]['desconto']){
							$desconto_de_ate = true;
						} else {
							$desconto_de_ate = false;
						}
						
						if($resultado[0]['desconto_de']){
							$de = strtotime($resultado[0]['desconto_de']);
							
							if($time < $de){
								$desconto_de_ate = false;
							}
						}
						
						if($resultado[0]['desconto_ate']){
							$ate = strtotime($resultado[0]['desconto_ate']);
							
							if($time > $ate){
								$desconto_de_ate = false;
							}
						}
						
						$validade = (int)$resultado[0]['validade'];
						(int)$quant;
						
						if($desconto_de_ate){
							/* if($desconto_cupom){
								$desconto1 = 100 - (float)$resultado[0]['desconto'];
								$desconto2 = 100 - (float)$desconto_cupom;

								$desconto = $desconto1 * ($desconto2/100);
								
								$valor_original = (float)$resultado[0]['preco'];
								
								$preco = (($valor_original * $desconto) / 100);
								
								$desconto = 100 - $desconto;
							} else { */
								$desconto = (float)$resultado[0]['desconto'];
								$valor_original = (float)$resultado[0]['preco'];
								
								$preco = (($valor_original * (100 - $desconto)) / 100);
							//}
						} else {
							if($desconto_cupom){
								$desconto = (float)$desconto_cupom;
								$valor_original = (float)$resultado[0]['preco'];
								
								$preco = (($valor_original * (100 - $desconto)) / 100);
							} else {
								$desconto = false;
								$valor_original = false;
								
								$preco = (float)$resultado[0]['preco'];
							}
						}
						
						$sub_total = $preco*$quant;
						$valor_total = $valor_total + $sub_total;
						
						for($i=0;$i<$quant;$i++){
							$campos = null;
							
							$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "id_produtos"; $campo_valor = $id_produtos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							//$campo_nome = "senha"; $campo_valor = zero_a_esquerda(rand(1,9999),4); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "quantidade"; $campo_valor = 1; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "desconto"; $campo_valor = $desconto; 		if($desconto)$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "validade"; $campo_valor = $validade; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "valor_original"; if($valor_original){$campo_valor = number_format($valor_original, 2, ".", ""); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
							$campo_nome = "sub_total"; $campo_valor = number_format($preco, 2, ".", ""); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "status"; $campo_valor = 'N'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							
							banco_insert_name
							(
								$campos,
								"pedidos_produtos"
							);
							
							$id_pedidos_produtos = banco_last_id();
							
							banco_update
							(
								"codigo='P".((int)$id_pedidos_produtos + 1000)."'",
								"pedidos_produtos",
								"WHERE id_pedidos_produtos='".$id_pedidos_produtos."'"
							);
						}
						
						$pedidos_produtos[] = Array(
							'id' => $id_produtos,
							'preco' => (string)$preco,
							'quant' => $quant,
							'titulo' => utf8_encode($resultado[0]['nome']),
							'href' => '',
						);
					}
					
					// ================================= Calcular Frete ===============================
				
					if($frete_codigo){
						$frete = cadastrar_pedido_calcular_frete(Array(
							'produtos_frete' => $produtos_frete,
							'quantidades_frete' => $quantidades_frete,
							'frete_codigo' => $frete_codigo,
							'cep' => $cep,
						));
						
						if($frete){
							$tipo_frete = $frete['tipo'];
							$valor_frete = $frete['valor'];
						} else {
							$tipo_frete = '3';
						}
					} else {
						$tipo_frete = '3';
					}
					
					// ============================== Vincular Pedido ao Cupom
					
					if($desconto_cupom){
						$campos = null;
						
						$campo_nome = "id_cupom_desconto"; $campo_valor = $desconto_cupom_id; 		$campos[] = Array($campo_nome,$campo_valor,true);
						$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,true);
						
						banco_insert_name
						(
							$campos,
							"cupom_desconto_pedidos"
						);
					}
					
					// ============================== Gerar código do pedido e atualizar valor total
					
					banco_update
					(
						($cep?"dest_cep='".$cep."',":"").
						($tipo_frete?"tipo_frete='".$tipo_frete."',":"").
						($valor_frete?"valor_frete='".preparar_texto_4_float($valor_frete)."',":"").
						"valor_total='".number_format($valor_total, 2, ".", "")."',".
						"codigo='E".((int)$id_pedidos+1000)."'",
						"pedidos",
						"WHERE id_pedidos='".$id_pedidos."'"
					);
					
					// ============================== Vincular pedido com o usuário
					
					banco_update
					(
						"pedido_atual=NULL",
						"usuario_pedidos",
						"WHERE id_usuario='".$id_usuario."'"
					);
					
					$campos = null;
					
					$campo_nome = "id_usuario"; $campo_valor = $id_usuario; 		$campos[] = Array($campo_nome,$campo_valor,true);
					$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,true);
					$campo_nome = "pedido_atual"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					
					banco_insert_name
					(
						$campos,
						"usuario_pedidos"
					);
					
					$ecommerce_pedido_itens = json_encode($pedidos_produtos);
					
					$_SESSION[$_SYSTEM['ID'].'ecommerce-limpar-carrinho'] = "E".((int)$id_pedidos+1000);
					$_SESSION[$_SYSTEM['ID'].'ecommerce_pedido_itens'] = $ecommerce_pedido_itens;
				}
			}
		} else {
			if($itens_arr){
				if(!$_CONEXAO_BANCO)$connect_db = true;
				if($connect_db)banco_conectar();
				
				// ============================== Verificar se tem quantidade em estoque de todos os itens
				
				if($itens_arr)
				foreach($itens_arr as $item){
					list($id_servicos,$quant) = explode(',',$item);
					
					$resultado = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_servicos',
						))
						,
						"servicos",
						"WHERE id_servicos='".$id_servicos."'"
						." AND quantidade - ".$quant." >= 0"
						." AND status='A'"
					);
					
					if($resultado){
						
					} else {
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'nome',
							))
							,
							"servicos",
							"WHERE id_servicos='".$id_servicos."'"
						);
						
						$flag = true;
						$lista_sem_estoque .= '<li>'.$resultado[0]['nome'].'</li>'."\n";
						
						$erro = '<p><b style="color:red;">Não foi possível registrar seu pedido</b></p><p>Serviço(s) indisponíveis no momento:</p><ul>'.$lista_sem_estoque.'</ul>';
					}
				}
				
				// ============================== Verificar cupom de desconto quando houver
				
				if($cupom){
					$cupom_desconto = banco_select_name
					(
						banco_campos_virgulas(Array(
							'id_cupom_desconto',
							'max_usos',
							'desconto',
							'data_inicio',
							'data_fim',
						))
						,
						"cupom_desconto",
						"WHERE codigo='".$cupom."'"
						." AND status='A'"
					);
					
					if($cupom_desconto){
						$cupom_desconto_pedidos = banco_select_name
						(
							banco_campos_virgulas(Array(
								'id_cupom_desconto',
							))
							,
							"cupom_desconto_pedidos",
							"WHERE id_cupom_desconto='".$cupom_desconto[0]['id_cupom_desconto']."'"
						);
						
						$cupom_de_ate = true;
						
						if($cupom_desconto[0]['data_inicio']){
							$cupom_de_ate = true;
							$data_inicio = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_cupom_desconto',
								))
								,
								"cupom_desconto",
								"WHERE id_cupom_desconto='".$cupom_desconto[0]['id_cupom_desconto']."'"
								." AND data_inicio <= NOW()"
							);
							
							if(!$data_inicio){
								$cupom_de_ate = false;
							}
						}
						
						if($cupom_desconto[0]['data_fim']){
							$cupom_de_ate = true;
							$data_fim = banco_select_name
							(
								banco_campos_virgulas(Array(
									'id_cupom_desconto',
								))
								,
								"cupom_desconto",
								"WHERE id_cupom_desconto='".$cupom_desconto[0]['id_cupom_desconto']."'"
								." AND data_fim >= NOW()"
							);
							
							if(!$data_fim){
								$cupom_de_ate = false;
							}
						}
						
						if($cupom_de_ate){
							if($cupom_desconto[0]['max_usos'] > count($cupom_desconto_pedidos)){
								$desconto_cupom = $cupom_desconto[0]['desconto'];
								$desconto_cupom_id = $cupom_desconto[0]['id_cupom_desconto'];
							} else {
								$flag = true;
								$erro = '<p>Não é possível mais usar esse cupom de desconto pois o mesmo já excedeu a quantidade máxima de usos.</p>';
							}
						} else {
							$flag = true;
							$erro = '<p>Não é possível mais usar esse cupom de desconto pois o mesmo está fora do período de validade.</p>';
						}
					} else {
						$flag = true;
						$erro = '<p>Cupom inválido!</p>';
					}
				}
				
				if($flag){
					$_ALERTA .= $erro;
					redirecionar('/');
				} else {
					// ============================== Cadastrar pedido
					$campos = null;
					
					$campo_nome = "data"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
					$campo_nome = "senha"; $campo_valor = zero_a_esquerda(rand(1,9999),4); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					$campo_nome = "status"; $campo_valor = 'N'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					
					banco_insert_name
					(
						$campos,
						"pedidos"
					);
					
					$id_pedidos = banco_last_id();
					
					$valor_total = 0;
					
					// ============================== Cadastrar itens do pedido
					
					foreach($itens_arr as $item){
						list($id_servicos,$quant) = explode(',',$item);
						
						$resultado = banco_select_name
						(
							banco_campos_virgulas(Array(
								'nome',
								'preco',
								'validade',
								'desconto',
								'desconto_de',
								'desconto_ate',
							))
							,
							"servicos",
							"WHERE id_servicos='".$id_servicos."'"
						);
						
						banco_update
						(
							"quantidade = (quantidade - ".$quant.")",
							"servicos",
							"WHERE id_servicos='".$id_servicos."'"
						);
						
						$time = time();
						if($resultado[0]['desconto']){
							$desconto_de_ate = true;
						} else {
							$desconto_de_ate = false;
						}
						
						if($resultado[0]['desconto_de']){
							$de = strtotime($resultado[0]['desconto_de']);
							
							if($time < $de){
								$desconto_de_ate = false;
							}
						}
						
						if($resultado[0]['desconto_ate']){
							$ate = strtotime($resultado[0]['desconto_ate']);
							
							if($time > $ate){
								$desconto_de_ate = false;
							}
						}
						
						$validade = (int)$resultado[0]['validade'];
						(int)$quant;
						
						if($desconto_de_ate){
							/* if($desconto_cupom){
								$desconto1 = 100 - (float)$resultado[0]['desconto'];
								$desconto2 = 100 - (float)$desconto_cupom;

								$desconto = $desconto1 * ($desconto2/100);
								
								$valor_original = (float)$resultado[0]['preco'];
								
								$preco = (($valor_original * $desconto) / 100);
								
								$desconto = 100 - $desconto;
							} else { */
								$desconto = (float)$resultado[0]['desconto'];
								$valor_original = (float)$resultado[0]['preco'];
								
								$preco = (($valor_original * (100 - $desconto)) / 100);
							//}
						} else {
							if($desconto_cupom){
								$desconto = (float)$desconto_cupom;
								$valor_original = (float)$resultado[0]['preco'];
								
								$preco = (($valor_original * (100 - $desconto)) / 100);
							} else {
								$desconto = false;
								$valor_original = false;
								
								$preco = (float)$resultado[0]['preco'];
							}
						}
						
						$sub_total = $preco*$quant;
						$valor_total = $valor_total + $sub_total;
						
						for($i=0;$i<$quant;$i++){
							$campos = null;
							
							$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "id_servicos"; $campo_valor = $id_servicos; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "senha"; $campo_valor = zero_a_esquerda(rand(1,9999),4); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "quantidade"; $campo_valor = 1; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "desconto"; $campo_valor = $desconto; 		if($desconto)$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "validade"; $campo_valor = $validade; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "valor_original"; if($valor_original){$campo_valor = number_format($valor_original, 2, ".", ""); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);}
							$campo_nome = "sub_total"; $campo_valor = number_format($preco, 2, ".", ""); 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							$campo_nome = "status"; $campo_valor = 'N'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
							
							banco_insert_name
							(
								$campos,
								"pedidos_servicos"
							);
							
							$id_pedidos_servicos = banco_last_id();
							
							banco_update
							(
								"codigo='S".((int)$id_pedidos_servicos + 1000)."'",
								"pedidos_servicos",
								"WHERE id_pedidos_servicos='".$id_pedidos_servicos."'"
							);
						}
						
						$pedidos_servicos[] = Array(
							'id' => $id_servicos,
							'preco' => (string)$preco,
							'quant' => $quant,
							'titulo' => utf8_encode($resultado[0]['nome']),
							'href' => '',
						);
					}
					
					// ============================== Vincular Pedido ao Cupom
					
					if($desconto_cupom){
						$campos = null;
						
						$campo_nome = "id_cupom_desconto"; $campo_valor = $desconto_cupom_id; 		$campos[] = Array($campo_nome,$campo_valor,true);
						$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,true);
						
						banco_insert_name
						(
							$campos,
							"cupom_desconto_pedidos"
						);
					}
					
					// ============================== Gerar código do pedido e atualizar valor total
					
					banco_update
					(
						"valor_total='".number_format($valor_total, 2, ".", "")."',".
						"codigo='E".((int)$id_pedidos+1000)."'",
						"pedidos",
						"WHERE id_pedidos='".$id_pedidos."'"
					);
					
					// ============================== Vincular pedido com o usuário
					
					banco_update
					(
						"pedido_atual=NULL",
						"usuario_pedidos",
						"WHERE id_usuario='".$id_usuario."'"
					);
					
					$campos = null;
					
					$campo_nome = "id_usuario"; $campo_valor = $id_usuario; 		$campos[] = Array($campo_nome,$campo_valor,true);
					$campo_nome = "id_pedidos"; $campo_valor = $id_pedidos; 		$campos[] = Array($campo_nome,$campo_valor,true);
					$campo_nome = "pedido_atual"; $campo_valor = '1'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
					
					banco_insert_name
					(
						$campos,
						"usuario_pedidos"
					);
					
					$ecommerce_pedido_itens = json_encode($pedidos_servicos);
					
					$_SESSION[$_SYSTEM['ID'].'ecommerce-limpar-carrinho'] = "E".((int)$id_pedidos+1000);
					$_SESSION[$_SYSTEM['ID'].'ecommerce_pedido_itens'] = $ecommerce_pedido_itens;
				}
			}
		}
	}
}

return cadastrar_pedido_main();

?>