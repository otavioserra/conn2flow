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

// Funções Internas

global $_VERSAO;
global $_SCRIPTS_JS;$_SCRIPTS_JS[] = 'includes/ecommerce/js.js?v='.$_VERSAO;
global $_STYLESHEETS;$_STYLESHEETS[] = 'includes/ecommerce/css.css?v='.$_VERSAO;

global $_ECOMMERCE;
if(!$_ECOMMERCE['pedido_validade'])$_ECOMMERCE['pedido_validade'] = 90;

global $_VARIAVEIS_JS;
$_VARIAVEIS_JS['voucher']['presente_outro'] = 'Para Presente';
$_VARIAVEIS_JS['voucher']['presente_voce'] = 'Para Você';

$_ECOMMERCE['status_mudar'] = Array(
	'5' => '<span style="color:red;">Em disputa</span>',
	'6' => '<span style="color:brown;">Dinheiro Devolvido</span>',
	'7' => '<span style="color:brown;">Cancelado</span>',
	'F' => '<span style="color:brown;">Finalizado</span>',
	'A' => '<span style="color:green;">Pago</span>',
	'B' => '<span style="color:red;">Bloqueado</span>',
	'D' => '<span style="color:red;">Deletado</span>',
	'N' => '<span style="color:blue;">Aguardando pagamento</span>',
	'P' => '<span style="color:blue;">Em análise</span>',
);

// ====================================== PayPal ======================================

global $_PAYPAL_SANDBOX; $_PAYPAL_SANDBOX = false;

function ecommerce_paypal_isIPNValid(array $message){
    $endpoint = 'https://www.paypal.com';
  
    if (isset($message['test_ipn']) && $message['test_ipn'] == '1') {
        $endpoint = 'https://www.sandbox.paypal.com';
    }
  
    $endpoint .= '/cgi-bin/webscr?cmd=_notify-validate';
  
    $curl = curl_init();
  
    curl_setopt($curl, CURLOPT_URL, $endpoint);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($message));
   
    $response = curl_exec($curl);
    $error = curl_error($curl);
    $errno = curl_errno($curl);
  
    curl_close($curl);
   
    return empty($error) && $errno == 0 && $response == 'VERIFIED';
}

function ecommerce_paypal_sendNvpRequest(array $requestNvp, $sandbox = false){
    //Endpoint da API
    $apiEndpoint  = 'https://api-3t.' . ($sandbox? 'sandbox.': null);
    $apiEndpoint .= 'paypal.com/nvp';
  
    //Executando a operação
    $curl = curl_init();
  
    curl_setopt($curl, CURLOPT_URL, $apiEndpoint);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($requestNvp));
  
    $response = urldecode(curl_exec($curl));
  
    curl_close($curl);
  
    //Tratando a resposta
    $responseNvp = array();
  
    if (preg_match_all('/(?<name>[^\=]+)\=(?<value>[^&]+)&?/', $response, $matches)) {
        foreach ($matches['name'] as $offset => $name) {
            $responseNvp[$name] = $matches['value'][$offset];
        }
    }
  
    //Verificando se deu tudo certo e, caso algum erro tenha ocorrido,
    //gravamos um log para depuração.
    if (isset($responseNvp['ACK']) && $responseNvp['ACK'] != 'Success') {
        for ($i = 0; isset($responseNvp['L_ERRORCODE' . $i]); ++$i) {
            $message = sprintf("PayPal NVP %s[%d]: %s\n",
                               $responseNvp['L_SEVERITYCODE' . $i],
                               $responseNvp['L_ERRORCODE' . $i],
                               $responseNvp['L_LONGMESSAGE' . $i]);
  
            error_log($message);
        }
    }
  
    return $responseNvp;
}

function ecommerce_paypal_pagar(){
	global $_SYSTEM;
	global $_OPCAO;
	global $_CONEXAO_BANCO;
	global $_PAYPAL;
	global $_PAYPAL_SANDBOX;
	global $_PROJETO;
	
	ecommerce_permissao_acesso();
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$usuario_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			't2.codigo',
			't2.valor_total',
			't1.id_pedidos',
		))
		,
		"usuario_pedidos as t1,pedidos as t2",
		"WHERE t1.id_usuario='".$usuario['id_usuario']."'"
		." AND t1.id_pedidos=t2.id_pedidos"
		." AND t1.pedido_atual IS NOT NULL"
	);
	
	if($usuario_pedidos){
		$codigo_referencia = $usuario_pedidos[0]['t2.codigo'];
		$valor_total = $usuario_pedidos[0]['t2.valor_total'];
		$id_pedidos = $usuario_pedidos[0]['t1.id_pedidos'];
		
		if($loja_online_produtos){
			$pedidos_produtos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't1.codigo',
					't1.quantidade',
					't1.sub_total',
					't2.nome',
				))
				,
				'pedidos_produtos as t1,produtos as t2',
				"WHERE t1.id_pedidos='".$id_pedidos."'"
				." AND t1.id_produtos=t2.id_produtos"
				." ORDER BY t2.nome ASC"
			);
			
			if($pedidos_produtos){
				include($_SYSTEM['PATH']."includes".$_SYSTEM['SEPARADOR']."php".$_SYSTEM['SEPARADOR']."paypal".$_SYSTEM['SEPARADOR']."paypal.config.php");
				
				
				//Baseado no ambiente, sandbox ou produção, definimos as credenciais
				//e URLs da API.
				if ($_PAYPAL_SANDBOX) {
					//credenciais da API para o Sandbox
					$user = 'otavioserra-facilitator_api1.gmail.com';
					$pswd = '1400005808';
					$signature = 'A86WhSbRNk-zAyk1sEPIVYSkWyQKAsA5L7BkQ.hesbYmUaAfnhg45vvO';
				  
					//URL da PayPal para redirecionamento, não deve ser modificada
					$paypalURL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
				} else {
					//credenciais da API para produção
					$user = $_PAYPAL['Credencial']['user'];
					$pswd = $_PAYPAL['Credencial']['pass'];
					$signature = $_PAYPAL['Credencial']['signature'];
				  
					//URL da PayPal para redirecionamento, não deve ser modificada
					$paypalURL = 'https://www.paypal.com/cgi-bin/webscr';
				}
				
				$pedidos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'valor_frete',
						'tipo_frete',
						'dest_nome',
						'dest_endereco',
						'dest_num',
						'dest_complemento',
						'dest_bairro',
						'dest_cidade',
						'dest_uf',
						'dest_cep',
					))
					,
					'pedidos',
					"WHERE id_pedidos='".$id_pedidos."'"
				);
				
				if($pedidos)foreach($pedidos[0] as $var => $val)$$var = $val;
				
				if($tipo_frete){
					if($tipo_frete != '3'){
						$valor_total_frete = number_format((float)$valor_total+(float)$valor_frete, 2, '.', '');
					} else {
						$valor_total_frete = number_format((float)$valor_total, 2, '.', '');
					}
				} else {
					$valor_total_frete = number_format((float)$valor_total, 2, '.', '');
				}
				
				//Campos da requisição da operação SetExpressCheckout, como ilustrado acima.
				$requestNvp = array(
					'USER' => $user,
					'PWD' => $pswd,
					'SIGNATURE' => $signature,
				  
					'VERSION' => '108.0',
					'METHOD'=> 'SetExpressCheckout',
					'CUSTOM'=> $codigo_referencia,
				  
					'PAYMENTREQUEST_0_PAYMENTACTION' => 'SALE',
					'PAYMENTREQUEST_0_AMT' => $valor_total_frete,
					'PAYMENTREQUEST_0_CURRENCYCODE' => 'BRL',
					'PAYMENTREQUEST_0_INVNUM' => $codigo_referencia,
					
					'RETURNURL' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'paypal-returnurl',
					'CANCELURL' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'paypal-cancelurl'
				);
				
				$dest_cep = preg_replace('/\./i', '', $dest_cep);
				$dest_cep = preg_replace('/\-/i', '', $dest_cep);
				
				if($tipo_frete)
				if($tipo_frete != '3'){
					$requestNvp['PAYMENTREQUEST_0_ITEMAMT'] = number_format((float)$valor_total, 2, '.', '');
					$requestNvp['PAYMENTREQUEST_0_TAXAMT'] = '0.00';
					$requestNvp['PAYMENTREQUEST_0_HANDLINGAMT'] = '0.00';
					$requestNvp['PAYMENTREQUEST_0_SHIPPINGAMT'] = number_format((float)$valor_frete, 2, '.', '');
					$requestNvp['PAYMENTREQUEST_0_SHIPTONAME'] = $dest_nome;
					$requestNvp['PAYMENTREQUEST_0_SHIPTOSTREET'] = $dest_endereco.($dest_num ? ' '.$dest_num : '');
					$requestNvp['PAYMENTREQUEST_0_SHIPTOSTREET2'] = ($dest_complemento?$dest_complemento:'').($dest_bairro && $dest_complemento ? ', ' : '').($dest_bairro?$dest_bairro:'');
					$requestNvp['PAYMENTREQUEST_0_SHIPTOCITY'] = $dest_cidade;
					$requestNvp['PAYMENTREQUEST_0_SHIPTOSTATE'] = $dest_uf;
					$requestNvp['PAYMENTREQUEST_0_SHIPTOZIP'] = $dest_cep;
					$requestNvp['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] = "BR";
				}
				
				$num_itens = 0;
				
				foreach($pedidos_produtos as $res){
					$requestNvp['L_PAYMENTREQUEST_0_NAME'.$num_itens] = $res['t1.codigo'];
					$requestNvp['L_PAYMENTREQUEST_0_DESC'.$num_itens] = $res['t2.nome'];
					$requestNvp['L_PAYMENTREQUEST_0_AMT'.$num_itens] = number_format((float)$res['t1.sub_total'], 2, '.', '');
					$requestNvp['L_PAYMENTREQUEST_0_QTY'.$num_itens] = $res['t1.quantidade'];
					$num_itens++;
				}
				
				$count = 0;
				$maxTries = 10;
				
				while(true) {
					//Envia a requisição e obtém a resposta da PayPal
					$responseNvp = ecommerce_paypal_sendNvpRequest($requestNvp, $_PAYPAL_SANDBOX);
					
					//Se a operação tiver sido bem sucedida, redirecionamos o cliente para o
					//ambiente de pagamento.
					
					if (isset($responseNvp['ACK']) && $responseNvp['ACK'] == 'Success') {
						$query = array(
							'cmd'    => '_express-checkout',
							'token'  => $responseNvp['TOKEN']
						);
						
						banco_update
						(
							"paypal_code='".$responseNvp['TOKEN']."'",
							"pedidos",
							"WHERE id_pedidos='".$id_pedidos."'"
						);
						
						log_banco(Array(
							'id_referencia' => $id_pedidos,
							'grupo' => 'pedidos',
							'valor' => '<b>PayPal:</b> usuário redirecionado para o paypal token: '.$responseNvp['TOKEN'],
						));
						
						$redirectURL = sprintf('%s?%s', $paypalURL, http_build_query($query));
					  
						header('Location: ' . $redirectURL);
						
						break;
					} else {
						//Opz, alguma coisa deu errada.
						//Verifique os logs de erro para depuração.
						
						$count++;
						if($count >= $maxTries){
							alerta('<p>Houve um problema com o PayPal.</p><p>Por favor, tente novamente pagar com essa opção.</p>');
							redirecionar('pagamento');
							break;
						}
					}
					usleep(400);
				}
			} else {
				alerta('<p>O seu pedido não tem itens cadastrados. Favor entrar em contato com o suporte técnico para saber como proceder e informe o ERRO: paypal_pagar 1.</p>');
				redirecionar('/');
			}
		} else {
			$pedidos_servicos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't1.codigo',
					't1.quantidade',
					't1.sub_total',
					't2.nome',
				))
				,
				'pedidos_servicos as t1,servicos as t2',
				"WHERE t1.id_pedidos='".$id_pedidos."'"
				." AND t1.id_servicos=t2.id_servicos"
				." ORDER BY t2.nome ASC"
			);
			
			if($pedidos_servicos){
				include($_SYSTEM['PATH']."includes".$_SYSTEM['SEPARADOR']."php".$_SYSTEM['SEPARADOR']."paypal".$_SYSTEM['SEPARADOR']."paypal.config.php");
				
				
				//Baseado no ambiente, sandbox ou produção, definimos as credenciais
				//e URLs da API.
				if ($_PAYPAL_SANDBOX) {
					//credenciais da API para o Sandbox
					$user = 'otavioserra-facilitator_api1.gmail.com';
					$pswd = '1400005808';
					$signature = 'A86WhSbRNk-zAyk1sEPIVYSkWyQKAsA5L7BkQ.hesbYmUaAfnhg45vvO';
				  
					//URL da PayPal para redirecionamento, não deve ser modificada
					$paypalURL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
				} else {
					//credenciais da API para produção
					$user = $_PAYPAL['Credencial']['user'];
					$pswd = $_PAYPAL['Credencial']['pass'];
					$signature = $_PAYPAL['Credencial']['signature'];
				  
					//URL da PayPal para redirecionamento, não deve ser modificada
					$paypalURL = 'https://www.paypal.com/cgi-bin/webscr';
				}
				  
				//Campos da requisição da operação SetExpressCheckout, como ilustrado acima.
				$requestNvp = array(
					'USER' => $user,
					'PWD' => $pswd,
					'SIGNATURE' => $signature,
				  
					'VERSION' => '108.0',
					'METHOD'=> 'SetExpressCheckout',
					'CUSTOM'=> $codigo_referencia,
				  
					'PAYMENTREQUEST_0_PAYMENTACTION' => 'SALE',
					'PAYMENTREQUEST_0_AMT' => number_format((float)$valor_total, 2, '.', ''),
					'PAYMENTREQUEST_0_CURRENCYCODE' => 'BRL',
					'PAYMENTREQUEST_0_INVNUM' => $codigo_referencia,
					
					'RETURNURL' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'paypal-returnurl',
					'CANCELURL' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'paypal-cancelurl'
				);
				
				$num_itens = 0;
				
				foreach($pedidos_servicos as $res){
					$requestNvp['L_PAYMENTREQUEST_0_NAME'.$num_itens] = $res['t1.codigo'];
					$requestNvp['L_PAYMENTREQUEST_0_DESC'.$num_itens] = $res['t2.nome'];
					$requestNvp['L_PAYMENTREQUEST_0_AMT'.$num_itens] = number_format((float)$res['t1.sub_total'], 2, '.', '');
					$requestNvp['L_PAYMENTREQUEST_0_QTY'.$num_itens] = $res['t1.quantidade'];
					$num_itens++;
				}
				
				$count = 0;
				$maxTries = 10;
				
				while(true) {
					//Envia a requisição e obtém a resposta da PayPal
					$responseNvp = ecommerce_paypal_sendNvpRequest($requestNvp, $_PAYPAL_SANDBOX);
					
					//Se a operação tiver sido bem sucedida, redirecionamos o cliente para o
					//ambiente de pagamento.
					if (isset($responseNvp['ACK']) && $responseNvp['ACK'] == 'Success') {
						$query = array(
							'cmd'    => '_express-checkout',
							'token'  => $responseNvp['TOKEN']
						);
						
						banco_update
						(
							"paypal_code='".$responseNvp['TOKEN']."'",
							"pedidos",
							"WHERE id_pedidos='".$id_pedidos."'"
						);
						
						log_banco(Array(
							'id_referencia' => $id_pedidos,
							'grupo' => 'pedidos',
							'valor' => '<b>PayPal:</b> usuário redirecionado para o paypal token: '.$responseNvp['TOKEN'],
						));
						
						$redirectURL = sprintf('%s?%s', $paypalURL, http_build_query($query));
					  
						header('Location: ' . $redirectURL);
						
						break;
					} else {
						//Opz, alguma coisa deu errada.
						//Verifique os logs de erro para depuração.
						
						$count++;
						if($count >= $maxTries){
							alerta('<p>Houve um problema com o PayPal.</p><p>Por favor, tente novamente pagar com essa opção.</p>');
							redirecionar('pagamento');
							break;
						}
					}
					usleep(400);
				}
			} else {
				alerta('<p>O seu pedido não tem itens cadastrados. Favor entrar em contato com o suporte técnico para saber como proceder e informe o ERRO: paypal_pagar 1.</p>');
				redirecionar('/');
			}
		}
	} else {
		alerta('<p>Você ainda não tem pedidos cadastrados para fazer pagamentos.</p>');
		redirecionar('/');
	}
}

function ecommerce_paypal_pagar2(){
	global $_SYSTEM;
	global $_OPCAO;
	global $_CONEXAO_BANCO;
	global $_PAYPAL;
	global $_PAYPAL_SANDBOX;
	global $_PROJETO;
	
	ecommerce_permissao_acesso();
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$usuario_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			't2.codigo',
			't2.valor_total',
			't2.paypal_code',
			't1.id_pedidos',
		))
		,
		"usuario_pedidos as t1,pedidos as t2",
		"WHERE t1.id_usuario='".$usuario['id_usuario']."'"
		." AND t1.id_pedidos=t2.id_pedidos"
		." AND t1.pedido_atual IS NOT NULL"
	);
	
	$paypal_token = $_REQUEST['token'];
	$paypal_payer_id = $_REQUEST['PayerID'];
	
	if($usuario_pedidos){
		$codigo_referencia = $usuario_pedidos[0]['t2.codigo'];
		$valor_total = $usuario_pedidos[0]['t2.valor_total'];
		$id_pedidos = $usuario_pedidos[0]['t1.id_pedidos'];
		$paypal_code = $usuario_pedidos[0]['t2.paypal_code'];
		
		if($paypal_token == $paypal_code){
			if($loja_online_produtos){
				$pedidos_produtos = banco_select_name
				(
					banco_campos_virgulas(Array(
						't1.codigo',
						't1.quantidade',
						't1.sub_total',
						't2.nome',
					))
					,
					'pedidos_produtos as t1,produtos as t2',
					"WHERE t1.id_pedidos='".$id_pedidos."'"
					." AND t1.id_produtos=t2.id_produtos"
					." ORDER BY t2.nome ASC"
				);
				
				if($pedidos_produtos){
					include($_SYSTEM['PATH']."includes".$_SYSTEM['SEPARADOR']."php".$_SYSTEM['SEPARADOR']."paypal".$_SYSTEM['SEPARADOR']."paypal.config.php");
					
					
					//Baseado no ambiente, sandbox ou produção, definimos as credenciais
					//e URLs da API.
					if ($_PAYPAL_SANDBOX) {
						//credenciais da API para o Sandbox
						$user = 'otavioserra-facilitator_api1.gmail.com';
						$email = 'otavioserra-facilitator@gmail.com';
						$pswd = '1400005808';
						$signature = 'A86WhSbRNk-zAyk1sEPIVYSkWyQKAsA5L7BkQ.hesbYmUaAfnhg45vvO';
					  
						//URL da PayPal para redirecionamento, não deve ser modificada
						$paypalURL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
					} else {
						//credenciais da API para produção
						$user = $_PAYPAL['Credencial']['user'];
						$email = $_PAYPAL['Credencial']['email'];
						$pswd = $_PAYPAL['Credencial']['pass'];
						$signature = $_PAYPAL['Credencial']['signature'];
					  
						//URL da PayPal para redirecionamento, não deve ser modificada
						$paypalURL = 'https://www.paypal.com/cgi-bin/webscr';
					}
					
					$pedidos = banco_select_name
					(
						banco_campos_virgulas(Array(
							'valor_frete',
							'tipo_frete',
							'dest_nome',
							'dest_endereco',
							'dest_num',
							'dest_complemento',
							'dest_bairro',
							'dest_cidade',
							'dest_uf',
							'dest_cep',
						))
						,
						'pedidos',
						"WHERE id_pedidos='".$id_pedidos."'"
					);
					
					if($pedidos)foreach($pedidos[0] as $var => $val)$$var = $val;
					
					if($tipo_frete){
						if($tipo_frete != '3'){
							$valor_total_frete = number_format((float)$valor_total+(float)$valor_frete, 2, '.', '');
						} else {
							$valor_total_frete = number_format((float)$valor_total, 2, '.', '');
						}
					} else {
						$valor_total_frete = number_format((float)$valor_total, 2, '.', '');
					}
					
					//Campos da requisição da operação SetExpressCheckout, como ilustrado acima.
					$requestNvp = array(
						'USER' => $user,
						'PWD' => $pswd,
						'SIGNATURE' => $signature,
					  
						'VERSION' => '108.0',
						'METHOD' => 'DoExpressCheckoutPayment',
						'CUSTOM' => $codigo_referencia,
						'TOKEN' => $paypal_code,
						'PAYERID' => $paypal_payer_id,
						'SUBJECT' => $email,
					  
						'PAYMENTREQUEST_0_PAYMENTACTION' => 'SALE',
						'PAYMENTREQUEST_0_AMT' => $valor_total_frete,
						'PAYMENTREQUEST_0_CURRENCYCODE' => 'BRL',
						'PAYMENTREQUEST_0_INVNUM' => $codigo_referencia,
						
						'NOTIFYURL' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'paypal-notificacoes'
					);
					
					$dest_cep = preg_replace('/\./i', '', $dest_cep);
					$dest_cep = preg_replace('/\-/i', '', $dest_cep);
					
					if($tipo_frete)
					if($tipo_frete != '3'){
						$requestNvp['PAYMENTREQUEST_0_ITEMAMT'] = number_format((float)$valor_total, 2, '.', '');
						$requestNvp['PAYMENTREQUEST_0_TAXAMT'] = '0.00';
						$requestNvp['PAYMENTREQUEST_0_HANDLINGAMT'] = '0.00';
						$requestNvp['PAYMENTREQUEST_0_SHIPPINGAMT'] = number_format((float)$valor_frete, 2, '.', '');
						$requestNvp['PAYMENTREQUEST_0_SHIPTONAME'] = $dest_nome;
						$requestNvp['PAYMENTREQUEST_0_SHIPTOSTREET'] = $dest_endereco.($dest_num ? ' '.$dest_num : '');
						$requestNvp['PAYMENTREQUEST_0_SHIPTOSTREET2'] = ($dest_complemento?$dest_complemento:'').($dest_bairro && $dest_complemento ? ', ' : '').($dest_bairro?$dest_bairro:'');
						$requestNvp['PAYMENTREQUEST_0_SHIPTOCITY'] = $dest_cidade;
						$requestNvp['PAYMENTREQUEST_0_SHIPTOSTATE'] = $dest_uf;
						$requestNvp['PAYMENTREQUEST_0_SHIPTOZIP'] = $dest_cep;
						$requestNvp['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] = "BR";
					}
					
					$num_itens = 0;
					
					foreach($pedidos_produtos as $res){
						$requestNvp['L_PAYMENTREQUEST_0_NAME'.$num_itens] = $res['t1.codigo'];
						$requestNvp['L_PAYMENTREQUEST_0_DESC'.$num_itens] = $res['t2.nome'];
						$requestNvp['L_PAYMENTREQUEST_0_AMT'.$num_itens] = number_format((float)$res['t1.sub_total'], 2, '.', '');
						$requestNvp['L_PAYMENTREQUEST_0_QTY'.$num_itens] = $res['t1.quantidade'];
						$num_itens++;
					}
					
					$count = 0;
					$maxTries = 10;
					
					while(true) {
						//Envia a requisição e obtém a resposta da PayPal
						$responseNvp = ecommerce_paypal_sendNvpRequest($requestNvp, $_PAYPAL_SANDBOX);
						
						//Se a operação tiver sido bem sucedida, redirecionamos o cliente para o
						//ambiente de pagamento.
						if (isset($responseNvp['ACK']) && $responseNvp['ACK'] == 'Success') {
							if($responseNvp['PAYMENTINFO_0_PAYMENTSTATUS'] == 'Completed'){
								$pago = true;
							}
							
							if($pago){
								banco_update
								(
									"status='A',".
									"paypal_code='".$responseNvp['PAYMENTINFO_0_TRANSACTIONID']."'",
									"pedidos",
									"WHERE id_pedidos='".$id_pedidos."'"
								);
								banco_update
								(
									"status='A'",
									"pedidos_produtos",
									"WHERE id_pedidos='".$id_pedidos."'"
								);
							}
							
							log_banco(Array(
								'id_referencia' => $id_pedidos,
								'grupo' => 'pedidos',
								'valor' => '<b>PayPal:</b> usuário redirecionado do paypal | payment_status: '.$responseNvp['PAYMENTINFO_0_PAYMENTSTATUS'] . ', transactionID: '.$responseNvp['PAYMENTINFO_0_TRANSACTIONID'],
							));
							
							redirecionar('paypal-retorno');
							break;
						} else {
							//Opz, alguma coisa deu errada.
							//Verifique os logs de erro para depuração.
							
							$count++;
							if($count >= $maxTries){
								alerta('<p>Houve um problema com o PayPal.</p><p>Por favor, tente novamente pagar com essa opção.</p>');
								redirecionar('pagamento');
								break;
							}
						}
						usleep(400);
					}
				} else {
					alerta('<p>O seu pedido não tem itens cadastrados. Favor entrar em contato com o suporte técnico para saber como proceder e informe o ERRO: paypal_pagar 1.</p>');
					redirecionar('pagamento');
				}
			} else {
				$pedidos_servicos = banco_select_name
				(
					banco_campos_virgulas(Array(
						't1.codigo',
						't1.quantidade',
						't1.sub_total',
						't2.nome',
					))
					,
					'pedidos_servicos as t1,servicos as t2',
					"WHERE t1.id_pedidos='".$id_pedidos."'"
					." AND t1.id_servicos=t2.id_servicos"
					." ORDER BY t2.nome ASC"
				);
				
				if($pedidos_servicos){
					include($_SYSTEM['PATH']."includes".$_SYSTEM['SEPARADOR']."php".$_SYSTEM['SEPARADOR']."paypal".$_SYSTEM['SEPARADOR']."paypal.config.php");
					
					
					//Baseado no ambiente, sandbox ou produção, definimos as credenciais
					//e URLs da API.
					if ($_PAYPAL_SANDBOX) {
						//credenciais da API para o Sandbox
						$user = 'otavioserra-facilitator_api1.gmail.com';
						$email = 'otavioserra-facilitator@gmail.com';
						$pswd = '1400005808';
						$signature = 'A86WhSbRNk-zAyk1sEPIVYSkWyQKAsA5L7BkQ.hesbYmUaAfnhg45vvO';
					  
						//URL da PayPal para redirecionamento, não deve ser modificada
						$paypalURL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
					} else {
						//credenciais da API para produção
						$user = $_PAYPAL['Credencial']['user'];
						$email = $_PAYPAL['Credencial']['email'];
						$pswd = $_PAYPAL['Credencial']['pass'];
						$signature = $_PAYPAL['Credencial']['signature'];
					  
						//URL da PayPal para redirecionamento, não deve ser modificada
						$paypalURL = 'https://www.paypal.com/cgi-bin/webscr';
					}
					  
					//Campos da requisição da operação SetExpressCheckout, como ilustrado acima.
					$requestNvp = array(
						'USER' => $user,
						'PWD' => $pswd,
						'SIGNATURE' => $signature,
					  
						'VERSION' => '108.0',
						'METHOD' => 'DoExpressCheckoutPayment',
						'CUSTOM' => $codigo_referencia,
						'TOKEN' => $paypal_code,
						'PAYERID' => $paypal_payer_id,
						'SUBJECT' => $email,
					  
						'PAYMENTREQUEST_0_PAYMENTACTION' => 'SALE',
						'PAYMENTREQUEST_0_AMT' => number_format((float)$valor_total, 2, '.', ''),
						'PAYMENTREQUEST_0_CURRENCYCODE' => 'BRL',
						'PAYMENTREQUEST_0_INVNUM' => $codigo_referencia,
						
						'NOTIFYURL' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'paypal-notificacoes'
					);
					
					$num_itens = 0;
					
					foreach($pedidos_servicos as $res){
						$requestNvp['L_PAYMENTREQUEST_0_NAME'.$num_itens] = $res['t1.codigo'];
						$requestNvp['L_PAYMENTREQUEST_0_DESC'.$num_itens] = $res['t2.nome'];
						$requestNvp['L_PAYMENTREQUEST_0_AMT'.$num_itens] = number_format((float)$res['t1.sub_total'], 2, '.', '');
						$requestNvp['L_PAYMENTREQUEST_0_QTY'.$num_itens] = $res['t1.quantidade'];
						$num_itens++;
					}
					
					$count = 0;
					$maxTries = 10;
					
					while(true) {
						//Envia a requisição e obtém a resposta da PayPal
						$responseNvp = ecommerce_paypal_sendNvpRequest($requestNvp, $_PAYPAL_SANDBOX);
						
						//Se a operação tiver sido bem sucedida, redirecionamos o cliente para o
						//ambiente de pagamento.
						if (isset($responseNvp['ACK']) && $responseNvp['ACK'] == 'Success') {
							if($responseNvp['PAYMENTINFO_0_PAYMENTSTATUS'] == 'Completed'){
								$pago = true;
							}
							
							if($pago){
								banco_update
								(
									"status='A',".
									"paypal_code='".$responseNvp['PAYMENTINFO_0_TRANSACTIONID']."'",
									"pedidos",
									"WHERE id_pedidos='".$id_pedidos."'"
								);
								banco_update
								(
									"status='A'",
									"pedidos_servicos",
									"WHERE id_pedidos='".$id_pedidos."'"
								);
							}
							
							log_banco(Array(
								'id_referencia' => $id_pedidos,
								'grupo' => 'pedidos',
								'valor' => '<b>PayPal:</b> usuário redirecionado do paypal | payment_status: '.$responseNvp['PAYMENTINFO_0_PAYMENTSTATUS'] . ', transactionID: '.$responseNvp['PAYMENTINFO_0_TRANSACTIONID'],
							));
							
							redirecionar('paypal-retorno');
							break;
						} else {
							//Opz, alguma coisa deu errada.
							//Verifique os logs de erro para depuração.
							
							$count++;
							if($count >= $maxTries){
								alerta('<p>Houve um problema com o PayPal.</p><p>Por favor, tente novamente pagar com essa opção.</p>');
								redirecionar('pagamento');
								break;
							}
						}
						usleep(400);
					}
				} else {
					alerta('<p>O seu pedido não tem itens cadastrados. Favor entrar em contato com o suporte técnico para saber como proceder e informe o ERRO: paypal_pagar 1.</p>');
					redirecionar('pagamento');
				}
			}
		} else {
			alerta('<p>O token da transação expirou ou então você está tentando pagar novamente algo já pago.</p>');
			redirecionar('pagamento');
		}
	} else {
		alerta('<p>Você ainda não tem pedidos cadastrados para fazer pagamentos.</p>');
		redirecionar('pagamento');
	}
}

function ecommerce_paypal_notificacoes(){
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_HTML;
	global $_VARS;
	global $_PAYPAL;
	global $_PAYPAL_SANDBOX;
	global $_PROJETO;
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}
	
	if($_SYSTEM['DOMINIO'] != 'localhost')$parametros['enviar_mail'] = true;
	
	include($_SYSTEM['PATH']."includes".$_SYSTEM['SEPARADOR']."php".$_SYSTEM['SEPARADOR']."paypal".$_SYSTEM['SEPARADOR']."paypal.config.php");
	
	//Email da conta do vendedor, que será utilizada para verificar o
	//destinatário da notificação.
	
	if($_PAYPAL_SANDBOX){
		$receiver_email = 'otavioserra-facilitator@gmail.com';
	} else {
		$receiver_email = $_PAYPAL['Credencial']['email'];
	}
	
	//As notificações sempre serão via HTTP POST, então verificamos o método
	//utilizado na requisição, antes de fazer qualquer coisa.
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//Antes de trabalhar com a notificação, precisamos verificar se ela
		//é válida e, se não for, descartar.
		if (!ecommerce_paypal_isIPNValid($_POST)) {
			return;
		}
		
		//Se chegamos até aqui, significa que estamos lidando com uma
		//notificação IPN válida. Agora precisamos verificar se somos o
		//destinatário dessa notificação, verificando o campo receiver_email.
		if ($_POST['receiver_email'] == $receiver_email) {
			$reference = $_POST['invoice'];
			$code = $_POST['txn_id'];
			$status = $_POST['payment_status'];
			
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					't3.nome',
					't3.email',
					't1.id_pedidos',
					't1.paypal_code',
					't1.status',
				))
				,
				"pedidos as t1,usuario_pedidos as t2,usuario as t3",
				"WHERE t1.codigo='".$reference."'"
				." AND t1.id_pedidos=t2.id_pedidos"
				." AND t2.id_usuario=t3.id_usuario"
			);
			
			$nome = $resultado[0]['t3.nome'];
			$email = $resultado[0]['t3.email'];
			$id_pedidos = $resultado[0]['t1.id_pedidos'];
			$paypal_code = $resultado[0]['t1.paypal_code'];
			$status_atual = $resultado[0]['t1.status'];
			
			if($paypal_code)
			if($paypal_code != $code && $status_atual == 'A'){
				$ignorar_mudanca_status = true;
			}
			
			if($ignorar_mudanca_status){
				switch($status){
					case 'Pending':
					case 'Processed': 
						$titulo = "Em análise"; 
					break;
					case 'Completed':
					case 'Canceled_Reversal': 
						$titulo = "Pago"; 
					break;
					case 'Refunded':
					case 'Reversed': 
						$titulo = "Dinheiro Devolvido"; 
					break;
					case 'Denied':
					case 'Expired':
					case 'Failed':
					case 'Voided': 
						$titulo = "Cancelado"; break;
					default: 
						$titulo = $status;
				}
				
				log_banco(Array(
					'id_referencia' => $id_pedidos,
					'grupo' => 'pedidos',
					'valor' => '<b>PayPal:</b> tentativa de mudança de status para: <b>'.$titulo.'</b> NÃO permitida pois esta transação: '.$code.' é diferente da transação: '.$paypal_code.' com status <b>Pago</b>.',
				));
			} else {
				$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
				
				$parametros['from_name'] = $_HTML['TITULO'];
				$parametros['from'] = $_SYSTEM['SMTP_USER'].'@'.$dominio_sem_www;
				
				$parametros['email_name'] = $nome;
				$parametros['email'] = $email;
				
				$parametros['subject'] = $_VARS['ecommerce']['pagseguro_notificacoes_assunto'];
				
				$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#codigo#",$reference);
				
				switch($status){
					/* case 1:
						$status_valor = 1;
						$titulo = "Aguardando pagamento";
						$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
						$parametros['mensagem'] = $_VARS['ecommerce']['pagseguro_notificacoes_mens_'.$status_valor];
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$reference);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
					break; */
					case 'Pending':
					case 'Processed':
						banco_update
						(
							"status='P'",
							"pedidos",
							"WHERE id_pedidos='".$id_pedidos."'"
						);
						
						if($loja_online_produtos){
							banco_update
							(
								"status='P'",
								"pedidos_produtos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						} else {
							banco_update
							(
								"status='P'",
								"pedidos_servicos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						}
						
						$status_valor = 2;
						$titulo = "Em análise";
						$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
						$parametros['mensagem'] = $_VARS['ecommerce']['pagseguro_notificacoes_mens_'.$status_valor];
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$reference);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
					break;
					case 'Completed':
					case 'Canceled_Reversal':
						$url = html(Array(
							'tag' => 'a',
							'val' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'voucher',
							'attr' => Array(
								'href' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'voucher',
							)
						));
						
						banco_update
						(
							"paypal_code='".$code."',".
							"status='A'",
							"pedidos",
							"WHERE id_pedidos='".$id_pedidos."'"
						);
						if($loja_online_produtos){
							banco_update
							(
								"status='A'",
								"pedidos_produtos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						} else {
							banco_update
							(
								"status='A'",
								"pedidos_servicos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						}
						
						$status_valor = 3;
						$titulo = "Pago";
						$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
						$parametros['mensagem'] = $_VARS['ecommerce']['pagseguro_notificacoes_mens_'.$status_valor];
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$reference);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#url#",$url);
					break;
					/* case 5:
						banco_update
						(
							"status='5'",
							"pedidos",
							"WHERE id_pedidos='".$id_pedidos."'"
						);
						if($loja_online_produtos){
							banco_update
							(
								"status='5'",
								"pedidos_produtos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						} else {
							banco_update
							(
								"status='5'",
								"pedidos_servicos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						}
						
						$status_valor = 5;
						$titulo = "Em disputa";
						$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
						$parametros['mensagem'] = $_VARS['ecommerce']['pagseguro_notificacoes_mens_'.$status_valor];
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$reference);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
					break; */
					case 'Refunded':
					case 'Reversed':
						banco_update
						(
							"paypal_code=NULL,".
							"status='6'",
							"pedidos",
							"WHERE id_pedidos='".$id_pedidos."'"
						);
						if($loja_online_produtos){
							banco_update
							(
								"status='6'",
								"pedidos_produtos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						} else {
							banco_update
							(
								"status='6'",
								"pedidos_servicos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						}
						
						$status_valor = 6;
						$titulo = "Dinheiro Devolvido";
						$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
						$parametros['mensagem'] = $_VARS['ecommerce']['pagseguro_notificacoes_mens_'.$status_valor];
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$reference);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
					break;
					case 'Denied':
					case 'Expired':
					case 'Failed':
					case 'Voided':
						banco_update
						(
							"paypal_code=NULL,".
							"status='7'",
							"pedidos",
							"WHERE id_pedidos='".$id_pedidos."'"
						);
						if($loja_online_produtos){
							banco_update
							(
								"status='7'",
								"pedidos_produtos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						} else {
							banco_update
							(
								"status='7'",
								"pedidos_servicos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						}
						
						$status_valor = 7;
						$titulo = "Cancelado";
						$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
						$parametros['mensagem'] = $_VARS['ecommerce']['pagseguro_notificacoes_mens_'.$status_valor];
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$reference);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
					break;
					default: 
						$titulo = $status;
				}
				
				log_banco(Array(
					'id_referencia' => $id_pedidos,
					'grupo' => 'pedidos',
					'valor' => '<b>PayPal:</b> alterou o status para: <b>'.$titulo.'</b> | payment_status: '.$status.', transactionID: '.$code,
				));
				
				if($loja_online_produtos) $parametros['mensagem'] .= ecommerce_pagseguro_lista_produtos($id_pedidos); else $parametros['mensagem'] .= ecommerce_pagseguro_lista_servicos($id_pedidos);
				$parametros['mensagem'] .= $_SYSTEM['MAILER_ASSINATURA'];
				
				if($parametros['enviar_mail'])enviar_mail($parametros);
			}
		}
	}
}

function ecommerce_paypal_cancelado(){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . 'Cancelamento do Pagamento.';
	
	$_HTML_DADOS['description'] = 'Página para de informação do retorno de cancelamento do pagamento de um pedido no paypal.';
	$_HTML_DADOS['keywords'] = 'pagamento retorno cancelado,pagamento serviços retorno cancelado,pagamento produtos retorno cancelado, retorno paypal cancelamento';
	
	if($_PROJETO['ecommerce']){
		if($_PROJETO['ecommerce']['retorno_pagamento_cancelado_layout']){
			$layout = $_PROJETO['ecommerce']['retorno_pagamento_cancelado_layout'];
		}
	}
	
	if(!$layout){
		$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
		$pagina = modelo_tag_val($modelo,'<!-- retorno_pagamento_cancelado'.($loja_online_produtos?'2':'').' < -->','<!-- retorno_pagamento_cancelado'.($loja_online_produtos?'2':'').' > -->');
		
		$layout = $pagina;
	}
	
	$url = html(Array(
		'tag' => 'a',
		'val' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'pagamento',
		'attr' => Array(
			'href' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'pagamento',
		)
	));
	
	$layout = modelo_var_troca_tudo($layout,"#url#",$url);
	
	return $layout;
}

function ecommerce_paypal_retorno(){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	
	ecommerce_permissao_acesso();
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			't1.paypal_code',
			't1.status',
		))
		,
		"pedidos as t1,usuario_pedidos as t2",
		"WHERE t2.pedido_atual IS NOT NULL"
		." AND t1.id_pedidos=t2.id_pedidos"
		." AND t2.id_usuario='".$usuario['id_usuario']."'"
	);
	
	$paypal_code = $resultado[0]['t1.paypal_code'];
	$status = $resultado[0]['t1.status'];
	
	if($paypal_code){
		if($status == 'A'){
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['retorno_pagamento_sucesso_layout']){
					$layout = $_PROJETO['ecommerce']['retorno_pagamento_sucesso_layout'];
				}
			}
			
			$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
			$_HTML_DADOS['titulo'] = $titulo . 'Pagamento Efetuado com Sucesso.';
			
			$_HTML_DADOS['description'] = 'Página para de informação do retorno de pagamento de um pedido no paypal.';
			$_HTML_DADOS['keywords'] = 'pagamento retorno,pagamento serviços retorno,pagamento produtos retorno, retorno paypal';
			
			if(!$layout){
				$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- retorno_pagamento_sucesso'.($loja_online_produtos?'2':'').' < -->','<!-- retorno_pagamento_sucesso'.($loja_online_produtos?'2':'').' > -->');
				
				$layout = $pagina;
			}
			
			$url = 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'voucher';
			
			$layout = modelo_var_troca($layout,"#url#",$url);
		} else {
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['retorno_pagamento_aguarde_layout']){
					$layout = $_PROJETO['ecommerce']['retorno_pagamento_aguarde_layout'];
				}
			}
			
			$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
			$_HTML_DADOS['titulo'] = $titulo . 'Pagamento Aguardando Aprovação.';
			
			$_HTML_DADOS['description'] = 'Página para de informação do retorno de pagamento de um pedido no paypal.';
			$_HTML_DADOS['keywords'] = 'pagamento retorno,pagamento serviços retorno,pagamento produtos retorno, retorno paypal';
			
			if(!$layout){
				$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- retorno_pagamento_aguarde'.($loja_online_produtos?'2':'').' < -->','<!-- retorno_pagamento_aguarde'.($loja_online_produtos?'2':'').' > -->');
				
				$layout = $pagina;
			}
		}
	} else {
		alerta("<p>Não há compras efetuadas por você para fazer a retirada do produto / serviço</p>");
		redirecionar('/');
	}
	
	return $layout;
}

// ====================================== PagSeguro ======================================

function ecommerce_pagseguro_lista_produtos($id_pedidos){
	global $_CONEXAO_BANCO;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			't3.nome',
			't3.observacao',
			't2.sub_total',
			't2.validade',
			't2.id_produtos',
		))
		,
		"pedidos as t1,pedidos_produtos as t2,produtos as t3",
		"WHERE t1.id_pedidos='".$id_pedidos."'"
		." AND t1.id_pedidos=t2.id_pedidos"
		." AND t2.id_produtos=t3.id_produtos"
	);
	
	if($resultado)
	foreach($resultado as $res){
		$found = false;
		if($formatado)
		foreach($formatado as $key => $val){
			if($val['id_produtos'] == $res['t2.id_produtos']){
				$found = true;
				$formatado[$key]['quant']++;
				break;
			}
		}
		
		if(!$found){
			$formatado[] = Array(
				'quant' => 1,
				'sub_total' => (float)$res['t2.sub_total'],
				'id_produtos' => $res['t2.id_produtos'],
				'nome' => $res['t3.nome'],
				'validade' => $res['t2.validade'],
				'observacao' => $res['t3.observacao'],
			);
		}
	}
	
	$table = '
<h3>Seu Pedido</h3>
<table cellpadding="10" width="100%" style="background-color:#E1E1E1;margin:15px 0px 0px 0px;">
    <tr style="background-color:#E1E1E1;">
        <td style="font-weight:bold;">Produto</td>
        <td style="width:20px;text-align:center;font-weight:bold;">Quant.</td>
        <td style="width:110px;text-align:center;font-weight:bold;">Preço unit.</td>
        <td style="width:110px;text-align:center;font-weight:bold;">Sub Total</td>
    </tr>
    <!-- item_tr -->
</table>';

	$servicos_total = 0;
	$valor_total = 0;

	$cel_nome = 'item_tr';
	if($formatado)
	foreach($formatado as $for){
		$sub_total = $for['quant'] * $for['sub_total'];
		
		$servicos_total += $for['quant'];
		$valor_total += $sub_total;
		
		$item_tr = '<tr>'."\n";
		$item_tr .= '	<td style="border:solid 1px #FFF;background-color:#FFF;">'.$for['nome'].'' . ($for['validade']? '<br>Validade de <b>'.$for['validade'].'</b> dia(s)' : '') . ($for['observacao']? '<br>Observa&ccedil;&atilde;o: '.$for['observacao'] : '') . '</td>'."\n";
		$item_tr .= '	<td style="text-align:center;border:solid 1px #FFF;background-color:#FFF;">'.$for['quant'].'</td>'."\n";
		$item_tr .= '	<td style="text-align:center;border:solid 1px #FFF;background-color:#FFF;">R$ '.preparar_float_4_texto(number_format($for['sub_total'], 2, '.', '')).'</td>'."\n";
		$item_tr .= '	<td style="text-align:center;border:solid 1px #FFF;background-color:#FFF;">R$ '.preparar_float_4_texto(number_format($sub_total, 2, '.', '')).'</td>'."\n";
		$item_tr .= '</tr>';
		
		$table = modelo_var_in($table,'<!-- '.$cel_nome.' -->',$item_tr);
	}
	$table = modelo_var_troca($table,'<!-- '.$cel_nome.' -->','');
	
	$table .= '
<table cellpadding="10" width="100%" style="background-color:#E1E1E1;margin:0px 0px 15px 0px;">
    <tr cellspacing="5">
		<td style="font-weight:bold;">'.$servicos_total.' produto(s) no pedido.</td>
		<td style="text-align:right;font-weight:bold;">Total dos Produtos: R$ '.preparar_float_4_texto(number_format($valor_total, 2, '.', '')).'</td>
	</tr>
</table>';

	$pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'valor_frete',
		))
		,
		"pedidos",
		"WHERE id_pedidos='".$id_pedidos."'"
	);
	
	$valor_frete = ($pedidos[0]['valor_frete']?(float)$pedidos[0]['valor_frete']:0);
	
$table .= '
<table cellpadding="10" width="100%" style="background-color:#E1E1E1;margin:0px 0px 15px 0px;">
    <tr cellspacing="5">
		<td style="font-weight:bold;">&nbsp;</td>
		<td style="text-align:right;font-weight:bold;">Valor do Frete: R$ '.preparar_float_4_texto(number_format($valor_frete, 2, '.', '')).'</td>
	</tr>
</table>';
$table .= '
<table cellpadding="10" width="100%" style="background-color:#E1E1E1;margin:0px 0px 15px 0px;">
    <tr cellspacing="5">
		<td style="font-weight:bold;">&nbsp;</td>
		<td style="text-align:right;font-weight:bold;">Valor Total: R$ '.preparar_float_4_texto(number_format(($valor_total+$valor_frete), 2, '.', '')).'</td>
	</tr>
</table>';
	
	return $table;
}

function ecommerce_pagseguro_lista_servicos($id_pedidos){
	global $_CONEXAO_BANCO;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			't3.nome',
			't3.observacao',
			't2.sub_total',
			't2.validade',
			't2.id_servicos',
		))
		,
		"pedidos as t1,pedidos_servicos as t2,servicos as t3",
		"WHERE t1.id_pedidos='".$id_pedidos."'"
		." AND t1.id_pedidos=t2.id_pedidos"
		." AND t2.id_servicos=t3.id_servicos"
	);
	
	if($resultado)
	foreach($resultado as $res){
		$found = false;
		if($formatado)
		foreach($formatado as $key => $val){
			if($val['id_servicos'] == $res['t2.id_servicos']){
				$found = true;
				$formatado[$key]['quant']++;
				break;
			}
		}
		
		if(!$found){
			$formatado[] = Array(
				'quant' => 1,
				'sub_total' => (float)$res['t2.sub_total'],
				'id_servicos' => $res['t2.id_servicos'],
				'nome' => $res['t3.nome'],
				'validade' => $res['t2.validade'],
				'observacao' => $res['t3.observacao'],
			);
		}
	}
	
	$table = '
<h3>Seu Pedido</h3>
<table cellpadding="10" width="100%" style="background-color:#E1E1E1;margin:15px 0px 0px 0px;">
    <tr style="background-color:#E1E1E1;">
        <td style="font-weight:bold;">Serviço</td>
        <td style="width:20px;text-align:center;font-weight:bold;">Quant.</td>
        <td style="width:110px;text-align:center;font-weight:bold;">Preço unit.</td>
        <td style="width:110px;text-align:center;font-weight:bold;">Sub Total</td>
    </tr>
    <!-- item_tr -->
</table>';

	$servicos_total = 0;
	$valor_total = 0;

	$cel_nome = 'item_tr';
	if($formatado)
	foreach($formatado as $for){
		$sub_total = $for['quant'] * $for['sub_total'];
		
		$servicos_total += $for['quant'];
		$valor_total += $sub_total;
		
		$item_tr = '<tr>'."\n";
		$item_tr .= '	<td style="border:solid 1px #FFF;background-color:#FFF;">'.$for['nome'].'' . ($for['validade']? '<br>Validade de <b>'.$for['validade'].'</b> dia(s)' : '') . ($for['observacao']? '<br>Observa&ccedil;&atilde;o: '.$for['observacao'] : '') . '</td>'."\n";
		$item_tr .= '	<td style="text-align:center;border:solid 1px #FFF;background-color:#FFF;">'.$for['quant'].'</td>'."\n";
		$item_tr .= '	<td style="text-align:center;border:solid 1px #FFF;background-color:#FFF;">R$ '.preparar_float_4_texto(number_format($for['sub_total'], 2, '.', '')).'</td>'."\n";
		$item_tr .= '	<td style="text-align:center;border:solid 1px #FFF;background-color:#FFF;">R$ '.preparar_float_4_texto(number_format($sub_total, 2, '.', '')).'</td>'."\n";
		$item_tr .= '</tr>';
		
		$table = modelo_var_in($table,'<!-- '.$cel_nome.' -->',$item_tr);
	}
	$table = modelo_var_troca($table,'<!-- '.$cel_nome.' -->','');
	
	$table .= '
<table cellpadding="10" width="100%" style="background-color:#E1E1E1;margin:0px 0px 15px 0px;">
    <tr cellspacing="5">
		<td style="font-weight:bold;">'.$servicos_total.' serviço(s) no pedido.</td>
		<td style="text-align:right;font-weight:bold;">Total dos Serviços: R$ '.preparar_float_4_texto(number_format($valor_total, 2, '.', '')).'</td>
	</tr>
</table>';
	
	return $table;
}

function ecommerce_pagseguro_pagar(){
	global $_SYSTEM;
	global $_OPCAO;
	global $_CONEXAO_BANCO;
	global $_PROJETO;
	
	ecommerce_permissao_acesso();
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$usuario_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			't2.codigo',
			't1.id_pedidos',
		))
		,
		"usuario_pedidos as t1,pedidos as t2",
		"WHERE t1.id_usuario='".$usuario['id_usuario']."'"
		." AND t1.id_pedidos=t2.id_pedidos"
		." AND t1.pedido_atual IS NOT NULL"
	);
	
	if($usuario_pedidos){
		$codigo_referencia = $usuario_pedidos[0]['t2.codigo'];
		$id_pedidos = $usuario_pedidos[0]['t1.id_pedidos'];
		
		if($loja_online_produtos){
			$pedidos_produtos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't1.codigo',
					't1.quantidade',
					't1.sub_total',
					't2.nome',
				))
				,
				'pedidos_produtos as t1,produtos as t2',
				"WHERE t1.id_pedidos='".$id_pedidos."'"
				." AND t1.id_produtos=t2.id_produtos"
				." ORDER BY t2.nome ASC"
			);
			
			if($pedidos_produtos){
				include($_SYSTEM['PATH']."includes".$_SYSTEM['SEPARADOR']."php".$_SYSTEM['SEPARADOR']."pagseguro-2.2.1".$_SYSTEM['SEPARADOR']."source".$_SYSTEM['SEPARADOR']."PagSeguroLibrary".$_SYSTEM['SEPARADOR']."PagSeguroLibrary.php");
				
				$count = 0;
				$maxTries = 10;
				while(true) {
					try {
						$paymentRequest = new PagSeguroPaymentRequest();
					
						foreach($pedidos_produtos as $res){	
							$paymentRequest->addItem($res['t1.codigo'], $res['t2.nome'], (int)$res['t1.quantidade'],number_format((float)$res['t1.sub_total'], 2, '.', ''));
						}
						
						$paymentRequest->setCurrency("BRL");
						
						$pedidos = banco_select_name
						(
							banco_campos_virgulas(Array(
								'tipo_frete',
								'valor_frete',
								'dest_nome',
								'dest_endereco',
								'dest_num',
								'dest_complemento',
								'dest_bairro',
								'dest_cidade',
								'dest_uf',
								'dest_cep',
							))
							,
							'pedidos',
							"WHERE id_pedidos='".$id_pedidos."'"
						);
						
						if($pedidos)foreach($pedidos[0] as $var => $val)$$var = $val;
						
						if($tipo_frete)
						if($tipo_frete != '3'){
							switch($tipo_frete){
								case '1': $tipo = 1; break;
								case '2': $tipo = 2; break;
								default: $tipo = 3; break;
							}
							
							$shipping = new PagSeguroShipping();
							$type = new PagSeguroShippingType($tipo);
							$shipping->setType($type);
							
							$shipping->setCost(number_format((float)$valor_frete, 2, '.', ''));
							
							$dest_cep = preg_replace('/\./i', '', $dest_cep);
							$dest_cep = preg_replace('/\-/i', '', $dest_cep);
							
							$address_data = Array(
								'postalCode' => $dest_cep,
								'street' => $dest_endereco,
								'number' => $dest_num,
								'complement' => $dest_complemento,
								'district' => $dest_bairro,
								'city' => $dest_cidade,
								'state' => $dest_uf,
								'country' => 'BRA'  
							);
							
							$address = new PagSeguroAddress($address_data);
							$shipping->setAddress($address);
							
							$paymentRequest->setShipping($shipping);
						} else {
							$paymentRequest->setShippingType(3);
						}
						
						//$paymentRequest->setRedirectURL($url_redirect);
						$paymentRequest->setReference($codigo_referencia);
						
						$credentials = PagSeguroConfig::getAccountCredentials();
						
						$url = $paymentRequest->register($credentials);
						
						log_banco(Array(
							'id_referencia' => $id_pedidos,
							'grupo' => 'pedidos',
							'valor' => '<b>PagSeguro:</b> usuário redirecionado para o pagseguro',
						));
						
						header("Location: ".$url);
						
						break;
					} catch (Exception $e) {
						$count++;
						if($count >= $maxTries){
							alerta('<p>Houve um problema com o PagSeguro.</p><p>Por favor, tente novamente pagar com essa opção.</p>');
							redirecionar('pagamento');
							break;
						}
					}
					usleep(400);
				}
			} else {
				alerta('<p>O seu pedido não tem itens cadastrados. Favor entrar em contato com o suporte técnico para saber como proceder e informe o ERRO: pagseguro_pagar 1.</p>');
				redirecionar('/');
			}
		} else {
			$pedidos_servicos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't1.codigo',
					't1.quantidade',
					't1.sub_total',
					't2.nome',
				))
				,
				'pedidos_servicos as t1,servicos as t2',
				"WHERE t1.id_pedidos='".$id_pedidos."'"
				." AND t1.id_servicos=t2.id_servicos"
				." ORDER BY t2.nome ASC"
			);
			
			if($pedidos_servicos){
				include($_SYSTEM['PATH']."includes".$_SYSTEM['SEPARADOR']."php".$_SYSTEM['SEPARADOR']."pagseguro-2.2.1".$_SYSTEM['SEPARADOR']."source".$_SYSTEM['SEPARADOR']."PagSeguroLibrary".$_SYSTEM['SEPARADOR']."PagSeguroLibrary.php");
				
				$count = 0;
				$maxTries = 10;
				while(true) {
					try {
						$paymentRequest = new PagSeguroPaymentRequest();
					
						foreach($pedidos_servicos as $res){	
							$paymentRequest->addItem($res['t1.codigo'], $res['t2.nome'], (int)$res['t1.quantidade'],number_format((float)$res['t1.sub_total'], 2, '.', ''));
						}
						
						$paymentRequest->setCurrency("BRL");
						$paymentRequest->setShippingType(3);
						//$paymentRequest->setRedirectURL($url_redirect);
						$paymentRequest->setReference($codigo_referencia);
						
						$credentials = PagSeguroConfig::getAccountCredentials();
						
						$url = $paymentRequest->register($credentials);
						
						log_banco(Array(
							'id_referencia' => $id_pedidos,
							'grupo' => 'pedidos',
							'valor' => '<b>PagSeguro:</b> usuário redirecionado para o pagseguro',
						));
						
						header("Location: ".$url);
						
						break;
					} catch (Exception $e) {
						$count++;
						if($count >= $maxTries){
							alerta('<p>Houve um problema com o PagSeguro.</p><p>Por favor, tente novamente pagar com essa opção.</p>');
							redirecionar('pagamento');
							break;
						}
					}
					usleep(400);
				}
			} else {
				alerta('<p>O seu pedido não tem itens cadastrados. Favor entrar em contato com o suporte técnico para saber como proceder e informe o ERRO: pagseguro_pagar 1.</p>');
				redirecionar('/');
			}
		}
	} else {
		alerta('<p>Você ainda não tem pedidos cadastrados para fazer pagamentos.</p>');
		redirecionar('/');
	}
}

function ecommerce_pagseguro_notificacoes(){
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_HTML;
	global $_VARS;
	global $_PROJETO;
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}
	
	if($_SYSTEM['DOMINIO'] != 'localhost')$parametros['enviar_mail'] = true;
	
	include($_SYSTEM['PATH']."includes".$_SYSTEM['SEPARADOR']."php".$_SYSTEM['SEPARADOR']."pagseguro-2.2.1".$_SYSTEM['SEPARADOR']."source".$_SYSTEM['SEPARADOR']."PagSeguroLibrary".$_SYSTEM['SEPARADOR']."PagSeguroLibrary.php");
	
	$credentials = PagSeguroConfig::getAccountCredentials();
	
	//$transaction_id_teste = '0F7D0A90-D7B4-4709-B04F-132F03F1DE8D';
	
	/* Tipo de notificação recebida */  
	$type = $_POST['notificationType'];
	
	/* Código da notificação recebida */
	$code = $_POST['notificationCode'];
	
	/* Verificando tipo de notificação recebida */  
	if($type === 'transaction') {  
		
		/* Obtendo o objeto PagSeguroTransaction a partir do código de notificação */  
		$transaction = PagSeguroNotificationService::checkTransaction(  
			$credentials,  
			$code // código de notificação  
		);
		
		if($transaction){
			$reference = $transaction->getReference();
			$code = $transaction->getCode();
			$status = $transaction->getStatus();
			
			$status_valor = $status->getValue();
			
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			$resultado = banco_select_name
			(
				banco_campos_virgulas(Array(
					't3.nome',
					't3.email',
					't1.id_pedidos',
					't1.pagseguro_code',
					't1.status',
				))
				,
				"pedidos as t1,usuario_pedidos as t2,usuario as t3",
				"WHERE t1.codigo='".$reference."'"
				." AND t1.id_pedidos=t2.id_pedidos"
				." AND t2.id_usuario=t3.id_usuario"
			);
			
			$nome = $resultado[0]['t3.nome'];
			$email = $resultado[0]['t3.email'];
			$id_pedidos = $resultado[0]['t1.id_pedidos'];
			$pagseguro_code = $resultado[0]['t1.pagseguro_code'];
			$status_atual = $resultado[0]['t1.status'];
			
			if($pagseguro_code)
			if($pagseguro_code != $code && $status_atual == 'A'){
				$ignorar_mudanca_status = true;
			}
			
			if($ignorar_mudanca_status){
				switch($status_valor){
					case 1: $titulo = "Aguardando pagamento"; break;
					case 2: $titulo = "Em análise"; break;
					case 3: $titulo = "Pago"; break;
					case 4: $titulo = "Finalização da Transação"; break;
					case 5: $titulo = "Em disputa"; break;
					case 6: $titulo = "Dinheiro Devolvido"; break;
					case 7: $titulo = "Cancelado"; break;
				}
				
				log_banco(Array(
					'id_referencia' => $id_pedidos,
					'grupo' => 'pedidos',
					'valor' => '<b>PagSeguro:</b> tentativa de mudança de status para: <b>'.$titulo.'</b> NÃO permitida pois esta transação: '.$code.' é diferente da transação: '.$pagseguro_code.' com status <b>Pago</b>.',
				));
			} else {
				$dominio_sem_www = preg_replace('/www./i', '', $_SYSTEM['DOMINIO']);
				
				$parametros['from_name'] = $_HTML['TITULO'];
				$parametros['from'] = $_SYSTEM['SMTP_USER'].'@'.$dominio_sem_www;
				
				$parametros['email_name'] = $nome;
				$parametros['email'] = $email;
				
				$parametros['subject'] = $_VARS['ecommerce']['pagseguro_notificacoes_assunto'];
				
				$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#codigo#",$reference);
				
				switch($status_valor){
					case 1:
						$titulo = "Aguardando pagamento";
						$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
						$parametros['mensagem'] = $_VARS['ecommerce']['pagseguro_notificacoes_mens_'.$status_valor];
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$reference);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
					break;
					case 2:
						banco_update
						(
							"status='P'",
							"pedidos",
							"WHERE id_pedidos='".$id_pedidos."'"
						);
						if($loja_online_produtos){
							banco_update
							(
								"status='P'",
								"pedidos_produtos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						} else {
							banco_update
							(
								"status='P'",
								"pedidos_servicos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						}
						
						$titulo = "Em análise";
						$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
						$parametros['mensagem'] = $_VARS['ecommerce']['pagseguro_notificacoes_mens_'.$status_valor];
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$reference);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
					break;
					case 3:
						$url = html(Array(
							'tag' => 'a',
							'val' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'voucher',
							'attr' => Array(
								'href' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'voucher',
							)
						));
						
						banco_update
						(
							"pagseguro_code='".$code."',".
							"status='A'",
							"pedidos",
							"WHERE id_pedidos='".$id_pedidos."'"
						);
						if($loja_online_produtos){
							banco_update
							(
								"status='A'",
								"pedidos_produtos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						} else {
							banco_update
							(
								"status='A'",
								"pedidos_servicos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						}
						
						$titulo = "Pago";
						$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
						$parametros['mensagem'] = $_VARS['ecommerce']['pagseguro_notificacoes_mens_'.$status_valor];
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$reference);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#url#",$url);
					break;
					case 4:
						$titulo = "Finalização da Transação";
					break;
					case 5:
						banco_update
						(
							"pagseguro_code=NULL,".
							"status='5'",
							"pedidos",
							"WHERE id_pedidos='".$id_pedidos."'"
						);
						if($loja_online_produtos){
							banco_update
							(
								"status='5'",
								"pedidos_produtos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						} else {
							banco_update
							(
								"status='5'",
								"pedidos_servicos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						}
						
						$titulo = "Em disputa";
						$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
						$parametros['mensagem'] = $_VARS['ecommerce']['pagseguro_notificacoes_mens_'.$status_valor];
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$reference);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
					break;
					case 6:
						banco_update
						(
							"pagseguro_code=NULL,".
							"status='6'",
							"pedidos",
							"WHERE id_pedidos='".$id_pedidos."'"
						);
						if($loja_online_produtos){
							banco_update
							(
								"status='6'",
								"pedidos_produtos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						} else {
							banco_update
							(
								"status='6'",
								"pedidos_servicos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						}
						
						$titulo = "Dinheiro Devolvido";
						$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
						$parametros['mensagem'] = $_VARS['ecommerce']['pagseguro_notificacoes_mens_'.$status_valor];
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$reference);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
					break;
					case 7:
						banco_update
						(
							"pagseguro_code=NULL,".
							"status='7'",
							"pedidos",
							"WHERE id_pedidos='".$id_pedidos."'"
						);
						if($loja_online_produtos){
							banco_update
							(
								"status='7'",
								"pedidos_produtos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						} else {
							banco_update
							(
								"status='7'",
								"pedidos_servicos",
								"WHERE id_pedidos='".$id_pedidos."'"
							);
						}
						
						$titulo = "Cancelado";
						$parametros['subject'] = modelo_var_troca_tudo($parametros['subject'],"#status#",$titulo);
						$parametros['mensagem'] = $_VARS['ecommerce']['pagseguro_notificacoes_mens_'.$status_valor];
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#codigo#",$reference);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#status#",$titulo);
						$parametros['mensagem'] = modelo_var_troca_tudo($parametros['mensagem'],"#titulo#",$_HTML['TITULO']);
					break;
				}
				
				log_banco(Array(
					'id_referencia' => $id_pedidos,
					'grupo' => 'pedidos',
					'valor' => '<b>PagSeguro:</b> alterou o status para: <b>'.$titulo.'</b> | transactionID: '.$code,
				));
				
				if($loja_online_produtos) $parametros['mensagem'] .= ecommerce_pagseguro_lista_produtos($id_pedidos); else $parametros['mensagem'] .= ecommerce_pagseguro_lista_servicos($id_pedidos);
				$parametros['mensagem'] .= $_SYSTEM['MAILER_ASSINATURA'];
				
				if($parametros['enviar_mail'])enviar_mail($parametros);
			}
		}
	}
}

function ecommerce_pagseguro_retorno(){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_PROJETO;
	global $_VARS;
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}
	
	ecommerce_permissao_acesso();
	
	include($_SYSTEM['PATH']."includes".$_SYSTEM['SEPARADOR']."php".$_SYSTEM['SEPARADOR']."pagseguro-2.2.1".$_SYSTEM['SEPARADOR']."source".$_SYSTEM['SEPARADOR']."PagSeguroLibrary".$_SYSTEM['SEPARADOR']."PagSeguroLibrary.php");
	
	$credentials = PagSeguroConfig::getAccountCredentials();
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			't1.pagseguro_code',
			't1.id_pedidos',
		))
		,
		"pedidos as t1,usuario_pedidos as t2",
		"WHERE t2.pedido_atual IS NOT NULL"
		." AND t1.id_pedidos=t2.id_pedidos"
		." AND t2.id_usuario='".$usuario['id_usuario']."'"
	);
	
	$transaction_id = $resultado[0]['t1.pagseguro_code'];
	$id_pedidos = $resultado[0]['t1.id_pedidos'];
	
	if($transaction_id){
		$transaction = PagSeguroTransactionSearchService::searchByCode(  
			$credentials,  
			$transaction_id
		);

		$status = $transaction->getStatus();
		
		$status_valor = $status->getValue();
		
		if($status_valor == 3){
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['retorno_pagamento_sucesso_layout']){
					$layout = $_PROJETO['ecommerce']['retorno_pagamento_sucesso_layout'];
				}
			}
			
			$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
			$_HTML_DADOS['titulo'] = $titulo . 'Pagamento Efetuado com Sucesso.';
			
			$_HTML_DADOS['description'] = 'Página para de informação do retorno de pagamento de um pedido no pagseguro.';
			$_HTML_DADOS['keywords'] = 'pagamento retorno,pagamento serviços retorno,pagamento produtos retorno, retorno pagseguro';
			
			if(!$layout){
				$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- retorno_pagamento_sucesso'.($loja_online_produtos?'2':'').' < -->','<!-- retorno_pagamento_sucesso'.($loja_online_produtos?'2':'').' > -->');
				
				$layout = $pagina;
			}
			
			$url = 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].'voucher';
			
			if($loja_online_produtos){
				$layout = modelo_var_troca($layout,"#texto-informativo#",$_VARS['ecommerce']['produtos_pagamento_efetuado']);
			}
			
			$layout = modelo_var_troca($layout,"#url#",$url);
			
			log_banco(Array(
				'id_referencia' => $id_pedidos,
				'grupo' => 'pedidos',
				'valor' => '<b>PagSeguro:</b> usuário redirecionado do pagseguro status: <b>Pago</b>',
			));
		} else {
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['retorno_pagamento_aguarde_layout']){
					$layout = $_PROJETO['ecommerce']['retorno_pagamento_aguarde_layout'];
				}
			}
			
			$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
			$_HTML_DADOS['titulo'] = $titulo . 'Pagamento Aguardando Aprovação.';
			
			$_HTML_DADOS['description'] = 'Página para de informação do retorno de pagamento de um pedido no pagseguro.';
			$_HTML_DADOS['keywords'] = 'pagamento retorno,pagamento serviços retorno,pagamento produtos retorno, retorno pagseguro';
			
			if(!$layout){
				$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- retorno_pagamento_aguarde'.($loja_online_produtos?'2':'').' < -->','<!-- retorno_pagamento_aguarde'.($loja_online_produtos?'2':'').' > -->');
				
				$layout = $pagina;
			}
			
			log_banco(Array(
				'id_referencia' => $id_pedidos,
				'grupo' => 'pedidos',
				'valor' => '<b>PagSeguro:</b> usuário redirecionado do pagseguro status: <b>Pendente</b>',
			));
		}
	} else {
		alerta("<p>Não há compras efetuadas por você para fazer a retirada do produto / serviço</p>");
		redirecionar('/');
	}
	
	return $layout;
}

function ecommerce_ativar_pedido(){
	global $_CONEXAO_BANCO;
	global $_SYSTEM;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$usuario_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pedidos',
		))
		,
		"usuario_pedidos",
		"WHERE id_usuario='".$usuario['id_usuario']."'"
		." AND pedido_atual IS NOT NULL"
	);
	
	$id_pedidos = $usuario_pedidos[0]['id_pedidos'];
	
	banco_update
	(
		"status='A'",
		"pedidos",
		"WHERE id_pedidos='".$id_pedidos."'"
	);
	banco_update
	(
		"status='A'",
		"pedidos_servicos",
		"WHERE id_pedidos='".$id_pedidos."'"
	);
	
	redirecionar('voucher');
}

// ==========================================================================

function ecommerce_duvidas(){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	global $_CAMINHO;
	global $_CONEXAO_BANCO;
	global $_VARS;
	
	if($_PROJETO['ecommerce']){
		if($_PROJETO['ecommerce']['duvidas']){
			$layout = $_PROJETO['ecommerce']['duvidas'];
		}
	}
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . 'Indique.';
	
	$_HTML_DADOS['description'] = 'Página para efetuar a indicação de um serviço/produto.';
	$_HTML_DADOS['keywords'] = 'duvidas,duvidas produtos,duvidas serviços';
	
	if(!$layout){
		$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
		$pagina = modelo_tag_val($modelo,'<!-- duvidas < -->','<!-- duvidas > -->');
		
		$layout = $pagina;
	}
	
	$id = $_CAMINHO[1];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'titulo',
			'caminho_raiz',
		))
		,
		"conteudo",
		"WHERE identificador='".$id."'"
	);
	
	$url = html(Array(
		'tag' => 'a',
		'val' => $resultado[0]['titulo'],
		'attr' => Array(
			'href' => '/'.$_SYSTEM['ROOT'].$resultado[0]['caminho_raiz'].$id,
		)
	));
	
	$layout = modelo_var_troca_tudo($layout,"#identificador#",$url);
	$layout = modelo_var_troca_tudo($layout,"#id#",$id);
	
	$_VARIAVEIS_JS['recaptcha_public_key'] = $_VARS['recaptcha']['PUBLIC_KEY'];
	
	return $layout;
}

function ecommerce_duvidas_enviar(){
	global $_SYSTEM;
	global $_HTML;
	global $_VARS;
	global $_CONEXAO_BANCO;
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$id = $_REQUEST['duvidas-id'];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'titulo',
			'caminho_raiz',
		))
		,
		"conteudo",
		"WHERE identificador='".$id."'"
	);
	
	$url_id = $resultado[0]['caminho_raiz'].$id;
	
	if(recaptcha_verify()){
		
		if($_VARS['ecommerce']){
			if($_VARS['ecommerce']['duvidas_assunto']){
				$email_assunto = $_VARS['ecommerce']['duvidas_assunto'];
			}
		}
		if($_VARS['ecommerce']){
			if($_VARS['ecommerce']['duvidas_mensagem']){
				$email_mensagem = $_VARS['ecommerce']['duvidas_mensagem'];
			}
		}
		
		$url = html(Array(
			'tag' => 'a',
			'val' => $resultado[0]['titulo'],
			'attr' => Array(
				'href' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$url_id,
			)
		));
		
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#nome#",$_REQUEST["nome"]);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#email#",$_REQUEST["email"]);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#telefone#",$_REQUEST["telefone"]);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#cidade_uf#",$_REQUEST["cidade"] . ' / ' . $_REQUEST["uf"]);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#duvida#",$_REQUEST["duvida"]);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#url#",$url);
		
		$email_nome = $_SYSTEM['CONTATO_NOME'];
		$email = $_SYSTEM['CONTATO_EMAIL'];
		
		$assunto = $email_assunto;
		$mensagem = $email_mensagem;
		
		email_enviar(Array(
			'email_nome' => $email_nome,
			'email' => $email,
			'assunto' => $assunto,
			'mensagem' => $mensagem,
		));
		
		alerta('<p>Dúvida enviada com sucesso!</p>');
	} else {
		alerta('<p>Código de validação <b style="color:red;">inválido</b>!<p></p>O código recaptcha especificado é inválido!</p>');
	}
	
	redirecionar($url_id);
}

function ecommerce_indique(){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_VARIAVEIS_JS;
	global $_CAMINHO;
	global $_CONEXAO_BANCO;
	global $_VARS;
	
	if($_PROJETO['ecommerce']){
		if($_PROJETO['ecommerce']['indique']){
			$layout = $_PROJETO['ecommerce']['indique'];
		}
	}
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . 'Indique.';
	
	$_HTML_DADOS['description'] = 'Página para efetuar a indicação de um serviço/produto.';
	$_HTML_DADOS['keywords'] = 'indique,indique produtos,indique serviços';
	
	if(!$layout){
		$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
		$pagina = modelo_tag_val($modelo,'<!-- indique < -->','<!-- indique > -->');
		
		$layout = $pagina;
	}
	
	$id = $_CAMINHO[1];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'titulo',
			'caminho_raiz',
		))
		,
		"conteudo",
		"WHERE identificador='".$id."'"
	);
	
	$url = html(Array(
		'tag' => 'a',
		'val' => $resultado[0]['titulo'],
		'attr' => Array(
			'href' => '/'.$_SYSTEM['ROOT'].$resultado[0]['caminho_raiz'].$id,
		)
	));
	
	$layout = modelo_var_troca_tudo($layout,"#identificador#",$url);
	$layout = modelo_var_troca_tudo($layout,"#id#",$id);
	
	$_VARIAVEIS_JS['recaptcha_public_key'] = $_VARS['recaptcha']['PUBLIC_KEY'];
	
	return $layout;
}

function ecommerce_indique_enviar(){
	global $_SYSTEM;
	global $_HTML;
	global $_VARS;
	global $_CONEXAO_BANCO;
	
	$id = $_REQUEST['indique-id'];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			'titulo',
			'caminho_raiz',
		))
		,
		"conteudo",
		"WHERE identificador='".$id."'"
	);
	
	$url_id = $resultado[0]['caminho_raiz'].$id;
	
	if(recaptcha_verify()){
		if($_VARS['ecommerce']){
			if($_VARS['ecommerce']['indique_assunto']){
				$email_assunto = $_VARS['ecommerce']['indique_assunto'];
			}
		}
		if($_VARS['ecommerce']){
			if($_VARS['ecommerce']['indique_mensagem']){
				$email_mensagem = $_VARS['ecommerce']['indique_mensagem'];
			}
		}
		
		$url = html(Array(
			'tag' => 'a',
			'val' => $resultado[0]['titulo'],
			'attr' => Array(
				'href' => 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$url_id,
			)
		));
		
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#nome_amigo#",$_REQUEST["nome_amigo"]);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#nome#",$_REQUEST["nome"]);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#email#",$_REQUEST["email"]);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#mensagem#",$_REQUEST["mensagem"]);
		$email_mensagem = modelo_var_troca_tudo($email_mensagem,"#url#",$url);
		
		$email_nome = strip_tags($_REQUEST["nome_amigo"]);
		$email = strip_tags($_REQUEST["email_amigo"]);
		
		$assunto = $email_assunto;
		$mensagem = $email_mensagem;
		
		email_enviar(Array(
			'email_nome' => $email_nome,
			'email' => $email,
			'assunto' => $assunto,
			'mensagem' => $mensagem,
		));
		
		alerta('<p>Indicação enviada com sucesso!</p>');
	} else {
		alerta('<p>Código de validação <b style="color:red;">inválido</b>!<p></p>O código recaptcha especificado é inválido!</p>');
	}
	
	redirecionar($url_id);
}

function ecommerce_meus_pedidos($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_ECOMMERCE;
	global $_VARIAVEIS_JS;
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}
	
	ecommerce_permissao_acesso();
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$status_mudar = $_ECOMMERCE['status_mudar'];
	$cel_nome = 'meus-pedidos';
	$sem_resultados_titulo = 'Sem pedidos cadastrados!';
	
	if($ajax){
		$pagina = '
<table cellpadding="5" id="_meus-pedidos-table">
  <tr class="_meus-pedidos-header">
    <td>Op&ccedil;&otilde;es</td>
    <td>C&oacute;digo</td>
    <td>Data</td>
    <td>Status</td>
  </tr>
<!-- '.$cel_nome.' -->
</table>';
	} else {
		if($_PROJETO['ecommerce']){
			if($_PROJETO['ecommerce']['meus_pedidos']){
				$pagina = $_PROJETO['ecommerce']['meus_pedidos'];
			}
		}
		
		if(!$pagina){
			if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
			$pagina = modelo_tag_val($modelo,'<!-- meus-pedidos'.($loja_online_produtos?'2':'').' < -->','<!-- meus-pedidos'.($loja_online_produtos?'2':'').' > -->');
		}
		
		$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
		$_HTML_DADOS['titulo'] = $titulo . 'Meus Pedidos.';
		
		$_HTML_DADOS['description'] = 'Página para visualizar todos os pedidos e suas informações.';
		$_HTML_DADOS['keywords'] = 'meus pedidos,pedidos,pedidos lista';
	}
	
	if($_REQUEST['page']){
		$page = (int)$_REQUEST['page'];
	}
	if($_REQUEST['limite']){
		$limite = (int)$_REQUEST['limite'];
	} else {
		if(!$limite){
			$limite = (int)$_HTML['MENU_NUM_PAGINAS'];
		}
	}
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$resultado = banco_select_name
	(
		banco_campos_virgulas(Array(
			't2.id_pedidos',
			't2.codigo',
			't2.status',
			't2.data',
		))
		,
		"usuario_pedidos as t1,pedidos as t2",
		"WHERE t1.id_usuario='".$usuario['id_usuario']."'"
		." AND t1.id_pedidos=t2.id_pedidos"
		." ORDER BY t2.data DESC"
		." LIMIT ".($page?($page*$limite).',':'') . ($limite + 1)
	);
	
	$cel[$cel_nome] = '
	<div class="cont-'.$cel_nome.'">
		#campo#
		<div class="clear"></div>
	</div>';
	$barra = '
	<div class="cont-'.$cel_nome.'-barra"></div>';
	
	if($resultado){
		foreach($resultado as $pedido){
			$layout = false;
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['meus_pedidos_cel']){
					$layout = $_PROJETO['ecommerce']['meus_pedidos_cel'];
				}
			}
			
			if(!$layout){
				$layout = '
	<tr class="_meus-pedidos-cel">
		<td class="_meus-pedidos-opcoes-td">#opcoes#</td>
		<td class="_meus-pedidos-codigo">#codigo#</td>
		<td class="_meus-pedidos-data">#data#</td>
		<td class="_meus-pedidos-status">#status#</td>
	</tr>';
			}
			
			$status = $pedido['t2.status'];
			
			foreach($status_mudar as $chave => $valor){
				if($pedido['t2.status'] == $chave){
					$pedido['t2.status'] = $valor;
					break;
				}
			}
			
			$pedido['t2.data'] = data_hora_from_datetime_to_text($pedido['t2.data']);
			
			if($loja_online_produtos){
				$layout = modelo_var_troca($layout,"#opcoes#",($status == 'N' ? '<img src="/'.$_SYSTEM['ROOT'].'images/icons/pagar-mini.png" data-id="'.$pedido['t2.id_pedidos'].'" class="_meus-pedidos-opcoes _meus-pedidos-pagar" title="Efetuar o Pagamento">' : ''));
			} else {
				$layout = modelo_var_troca($layout,"#opcoes#",($status == 'A' ? '<img src="/'.$_SYSTEM['ROOT'].'images/icons/ico-visualizar.png" data-id="'.$pedido['t2.id_pedidos'].'" class="_meus-pedidos-opcoes _meus-pedidos-voucher" title="Visualizar o Voucher">' : '').($status == 'N' ? '<img src="/'.$_SYSTEM['ROOT'].'images/icons/pagar-mini.png" data-id="'.$pedido['t2.id_pedidos'].'" class="_meus-pedidos-opcoes _meus-pedidos-pagar" title="Efetuar o Pagamento">' : ''));
			}
			
			$layout = modelo_var_troca($layout,"#codigo#",$pedido['t2.codigo']);
			$layout = modelo_var_troca($layout,"#data#",$pedido['t2.data']);
			$layout = modelo_var_troca($layout,"#status#",$pedido['t2.status']);
			
			$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$layout);
			
			$cont++;
			if($cont >= $limite){
				break;
			}
			
		}
		
		$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
	} else {
		$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->',$sem_resultados_titulo);
	}
	
	$data_vars = Array(
		'limite' => $limite,
		'opcao' => $cel_nome,
	);
	
	$_VARIAVEIS_JS['mais_resultados'] = json_encode($data_vars);
	
	if($page){
		$page++;
	} else {
		$page = 1;
		$mais_resultados = '
	'.(!$ajax ? '<div class="clear"></div>' : '').'
	<div id="cont-'.$cel_nome.'-mais">Mais Resultados</div>';
	}
	
	if($limite >= count($resultado)){
		$sem_mais_resultados = true;
	} else {
		$pagina .= $mais_resultados;
	}
	
	if($ajax){
		return Array(
			'pagina' => $pagina,
			'sem_mais' => $sem_mais_resultados,
		);
	} else 
	return $pagina.'
		<div class="clear"></div>';
}

function ecommerce_remover_css($voucher){
	$valores = Array(
		'display: inline;',
		'display: table-row;',
		'border: 0px none rgb(0, 0, 0);',
		'outline: rgb(0, 0, 0) none 0px;',
		'background-color: rgba(0, 0, 0, 0);',
		'fill: rgb(0, 0, 0);',
		'cursor: default;',
		'background-repeat: repeat repeat;',
		'background-position: 0% 0%;',
		'zoom: 1;',
		'widows: auto;',
		'visibility: visible;',
		'-webkit-transition: all 0s ease 0s;',
		'transition: all 0s ease 0s;',
		'top: auto;',
		'stroke: none;',
		'speak: normal;',
		'right: auto;',
		'resize: none;',
		'position: static;',
		'overflow: visible;',
		'outline: rgb(0, 0, 0) none 0px;',
		'orphans: auto;',
		'order: 0;',
		'opacity: 1;',
		'mask: none;',
		'left: auto;',
		'kerning: 0px;',
		'font-weight: normal;',
		'font-variant: normal;',
		'font-style: normal;',
		'float: none;',
		'flex: 0 1 auto;',
		'filter: none;',
		'fill: rgb(0, 0, 0);',
		'display: table-cell;',
		'direction: ltr;',
		'cursor: auto;',
		'clip: auto;',
		'clear: none;',
		'bottom: auto;',
		'background-size: auto;',
		'background-image: none;',
		'background-clip: border-box;',
		'background-origin: padding-box;',
		'background-attachment: scroll;',
	);
	
	if($valores)
	foreach($valores as $valor){
		$voucher = preg_replace('| '.preg_quote($valor).'|i', '', $voucher);
		$voucher = preg_replace('|'.preg_quote($valor).'|i', '', $voucher);
	}
	
	$voucher = preg_replace('/ class=(")?[^"]+(?(1)")/i', '', $voucher);
	$voucher = preg_replace('/ id=(")?[^"]+(?(1)")/i', '', $voucher);
	$voucher = preg_replace('/font-family(:)?[^;]+(?(1);)/i', '', $voucher);
	
	return '<div style="-webkit-print-color-adjust:exact;">'.$voucher.'</div>';
}

function ecommerce_permissao_acesso(){
	global $_SYSTEM;
	global $_PROJETO;
	global $_ECOMMERCE;
	global $_OPCAO;
	
	if(!$_SESSION[$_SYSTEM['ID']."permissao"]){
		$redirect = true;
	} else {
		if($_SESSION[$_SYSTEM['ID']."permissao_id"] != $_ECOMMERCE['permissao_usuario']){
			$redirect = true;
			$permissao_id = true;
		}
	}
	
	if($redirect){
		if(!$permissao_id){
			$_SESSION[$_SYSTEM['ID'].'logar-local'] = $_OPCAO;
			redirecionar('autenticar');
		} else {
			redirecionar($local);
		}
	}
}

function ecommerce_endereco_entrega(){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	
	ecommerce_permissao_acesso();
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$usuario_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pedidos',
		))
		,
		"usuario_pedidos",
		"WHERE id_usuario='".$usuario['id_usuario']."'"
		." AND pedido_atual IS NOT NULL"
	);
	
	if($usuario_pedidos){
		$id_pedidos = $usuario_pedidos[0]['id_pedidos'];
		$pedido = banco_select_name
		(
			banco_campos_virgulas(Array(
				'codigo',
				'tipo_frete',
				'valor_frete',
				'valor_total',
				'dest_cep',
			))
			,
			"pedidos",
			"WHERE id_pedidos='".$id_pedidos."'"
		);
		
		$pedido_num = $pedido[0]['codigo'];
		$dest_cep = $pedido[0]['dest_cep'];
		$tipo_frete = $pedido[0]['tipo_frete'];
		$valor_frete = $pedido[0]['valor_frete'];
		$valor_total = $pedido[0]['valor_total'];
	}
	
	require_once $_SYSTEM['PATH']."includes".$_SYSTEM['SEPARADOR']."php".$_SYSTEM['SEPARADOR']."cepCorreios".$_SYSTEM['SEPARADOR']."Correios.php";
	
	$correios = new Correios;
	
	$cep_raw = preg_replace('/\./i', '', $dest_cep);
	$cep_raw = preg_replace('/\-/i', '', $cep_raw);
	
	if($cep_raw)$correios->retornaInformacoesCep($cep_raw);
	
	if($_PROJETO['ecommerce']){
		if($_PROJETO['ecommerce']['endereco_entrega_layout']){
			$layout = $_PROJETO['ecommerce']['endereco_entrega_layout'];
		}
	}
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . 'Endereço de Entrega.';
	
	$_HTML_DADOS['description'] = 'Página para preencher o endereço de entrega de um pedido.';
	$_HTML_DADOS['keywords'] = 'endereco,entrega,endereco de entrega,endereço,endereço de entrega';
	
	if(!$layout){
		$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
		$pagina = modelo_tag_val($modelo,'<!-- endereco_entrega < -->','<!-- endereco_entrega > -->');
		
		$layout = $pagina;
	}
	
	if($cep_raw){
		$endereco = $correios->informacoesCorreios->getLogradouro();
		$endereco_arr = explode(' - ',$endereco);
		$endereco = $endereco_arr[0];
	}
	
	$layout = modelo_var_troca($layout,"#frete#",'R$ '.preparar_float_4_texto(number_format($valor_frete, 2, '.', '')));
	$layout = modelo_var_troca($layout,"#total#",'R$ '.preparar_float_4_texto(number_format(((float)$valor_frete+(float)$valor_total), 2, '.', '')));
	$layout = modelo_var_troca($layout,"#nome#",$usuario['nome']);
	$layout = modelo_var_troca($layout,"#endereco#",$endereco);
	$layout = modelo_var_troca($layout,"#bairro#",($cep_raw?$correios->informacoesCorreios->getBairro():''));
	$layout = modelo_var_troca($layout,"#cidade#",($cep_raw?$correios->informacoesCorreios->getLocalidade():''));
	$layout = modelo_var_troca($layout,"#uf#",($cep_raw?$correios->informacoesCorreios->getUf():''));
	$layout = modelo_var_troca($layout,"#cep#",$dest_cep);
	$layout = modelo_var_troca($layout,"#pedido_num#",$pedido_num);
	
	return $layout;
}

function ecommerce_endereco_entrega_salvar(){
	global $_CONEXAO_BANCO;
	global $_SYSTEM;
	
	ecommerce_permissao_acesso();
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$usuario_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pedidos',
		))
		,
		"usuario_pedidos",
		"WHERE id_usuario='".$usuario['id_usuario']."'"
		." AND pedido_atual IS NOT NULL"
	);
	
	if($usuario_pedidos){
		$id_pedidos = $usuario_pedidos[0]['id_pedidos'];
		
		$campo_tabela = "pedidos";
		$campo_tabela_extra = "WHERE id_pedidos='".$id_pedidos."'";
		
		$campo_nome = "dest_nome"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";
		$campo_nome = "dest_endereco"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";
		$campo_nome = "dest_num"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";
		$campo_nome = "dest_complemento"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";
		$campo_nome = "dest_bairro"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";
		$campo_nome = "dest_cidade"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";
		$campo_nome = "dest_uf"; $editar[$campo_tabela][] = $campo_nome."='" . $_REQUEST[$campo_nome] . "'";
		
		$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
		
		if($editar_sql[$campo_tabela]){
			banco_update
			(
				$editar_sql[$campo_tabela],
				$campo_tabela,
				$campo_tabela_extra
			);
		}
		$editar = false;$editar_sql = false;
	}
	
	redirecionar('pagamento');
}

function ecommerce_pagamento(){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	
	ecommerce_permissao_acesso();
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	$usuario_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pedidos',
		))
		,
		"usuario_pedidos",
		"WHERE id_usuario='".$usuario['id_usuario']."'"
		." AND pedido_atual IS NOT NULL"
	);
	
	if($usuario_pedidos){
		$id_pedidos = $usuario_pedidos[0]['id_pedidos'];
		$pedido = banco_select_name
		(
			banco_campos_virgulas(Array(
				'codigo',
				'tipo_frete',
				'valor_frete',
				'valor_total',
				'dest_endereco',
			))
			,
			"pedidos",
			"WHERE id_pedidos='".$id_pedidos."'"
		);
		
		$pedido_num = $pedido[0]['codigo'];
		$valor_frete = (int)$pedido[0]['valor_frete'];
		$valor_total = (int)$pedido[0]['valor_total'];
	}
	
	if($loja_online_produtos){
		if($pedido[0]['tipo_frete'] != '3'){
			if(!$pedido[0]['dest_endereco']){
				redirecionar('endereco-entrega');
			}
		}
	}
	
	if($_PROJETO['ecommerce']){
		if($_PROJETO['ecommerce']['pagamento_layout']){
			$layout = $_PROJETO['ecommerce']['pagamento_layout'];
		}
	}
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . 'Pagamento.';
	
	$_HTML_DADOS['description'] = 'Página para efetuar o pagamento de um pedido.';
	$_HTML_DADOS['keywords'] = 'pagamento,pagamento serviços,pagamento produtos';
	
	if(!$layout){
		$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
		$pagina = modelo_tag_val($modelo,'<!-- pagamento'.($loja_online_produtos?'2':'').' < -->','<!-- pagamento'.($loja_online_produtos?'2':'').' > -->');
		
		$layout = $pagina;
	}
	
	$layout = modelo_var_troca($layout,"#frete#",'R$ '.preparar_float_4_texto(number_format((float)$valor_frete, 2, '.', '')));
	$layout = modelo_var_troca($layout,"#total#",'R$ '.preparar_float_4_texto(number_format(((float)$valor_frete+(float)$valor_total), 2, '.', '')));
	$layout = modelo_var_troca($layout,"#pedido_num#",$pedido_num);
	
	return $layout;
}

function ecommerce_voucher(){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_ECOMMERCE;
	global $_VARIAVEIS_JS;
	global $_VARS;
	
	ecommerce_permissao_acesso();
	
	$voucher_temas_colunas = 2;
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . 'Voucher.';
	
	$_HTML_DADOS['description'] = 'Página para gerar o voucher de um pedido.';
	$_HTML_DADOS['keywords'] = 'voucher,voucher serviços,voucher pedido';
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$usuario_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			't2.status',
			't2.id_pedidos',
		))
		,
		"usuario_pedidos as t1,pedidos as t2",
		"WHERE t1.id_usuario='".$usuario['id_usuario']."'"
		." AND t1.id_pedidos=t2.id_pedidos"
		." AND t1.pedido_atual IS NOT NULL"
	);
	
	if((!$usuario_pedidos) || ($usuario_pedidos && $usuario_pedidos[0]['t2.status'] != 'A')){
		if($usuario_pedidos && $usuario_pedidos[0]['t2.status'] != 'A'){
			alerta('<p style="color:red;">O seu pedido atual ainda não está ativo.</p><p>Você só pode gerar voucher dos seus pedidos antigos se houverem pedidos antigos <b>Pagos</b>.</p>');
			$pedido_atual_nao_pago = true;
		}
		
		$usuario_pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				't2.id_pedidos',
			))
			,
			"usuario_pedidos as t1,pedidos as t2",
			"WHERE t1.id_usuario='".$usuario['id_usuario']."'"
			." AND t1.id_pedidos=t2.id_pedidos"
			." AND t2.status='A'"
			." ORDER BY t2.data DESC"
		);
	}
	
	if($usuario_pedidos){
		$id_pedidos = $usuario_pedidos[0]['t2.id_pedidos'];
		
		// ============================== Pedido Atual
		
		$pedido = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
				'data',
				'presente',
				'de',
				'para',
				'mensagem',
				'codigo',
				'senha',
				'id_voucher_layouts',
				'voucher_por_servico',
			))
			,
			"pedidos",
			"WHERE id_pedidos='".$id_pedidos."'"
			." AND status='A'"
		);
		
		if($pedido){
			$pedido = $pedido[0];
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['voucher_titulo']){
					$voucher_titulo = $_PROJETO['ecommerce']['voucher_titulo'];
				}
			}
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['voucher_layout']){
					$voucher = $_PROJETO['ecommerce']['voucher_layout'];
				}
			}
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['voucher_menu']){
					$voucher_menu = $_PROJETO['ecommerce']['voucher_menu'];
				}
			}
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['voucher_topo']){
					$voucher_topo = $_PROJETO['ecommerce']['voucher_topo'];
				}
			}
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['voucher_email']){
					$voucher_email = $_PROJETO['ecommerce']['voucher_email'];
				}
			}
			if($_VARS['ecommerce']){
				if($_VARS['ecommerce']['voucher_base']){
					$voucher_base = $_VARS['ecommerce']['voucher_base'];
				}
			}
			if($_VARS['ecommerce']){
				if($_VARS['ecommerce']['voucher_mensagem_concluido']){
					$voucher_concluir = $_VARS['ecommerce']['voucher_mensagem_concluido'];
				}
			}
			if($_VARS['ecommerce']){
				if($_VARS['ecommerce']['voucher_temas_colunas']){
					$voucher_temas_colunas = $_VARS['ecommerce']['voucher_temas_colunas'];
				}
			}
			
			if(!$voucher_titulo){
				$voucher_titulo = '
<h1>Voucher</h1>';
			}
			if(!$voucher){
				$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- voucher < -->','<!-- voucher > -->');
				
				$voucher = $pagina;
			}
			if(!$voucher_menu){
				if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- voucher_menu < -->','<!-- voucher_menu > -->');
				
				$voucher_menu = $pagina;
			}
			if(!$voucher_presente){
				if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- voucher_presente < -->','<!-- voucher_presente > -->');
				
				$voucher_presente = $pagina;
			}
			if(!$voucher_email){
				if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- voucher_email < -->','<!-- voucher_email > -->');
				
				$voucher_email = $pagina;
			}
			if(!$voucher_base){
				if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- voucher_base < -->','<!-- voucher_base > -->');
				
				$voucher_base = $pagina;
			}
			
			// ============================== Voucher Layout
			
			$voucher_layout = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_voucher_layouts',
					'imagem_topo',
					'imagem_textura',
					'cor_fundo',
				))
				,
				"voucher_layouts",
				"WHERE status='A'"
				." AND id_voucher_layouts='".$pedido['id_voucher_layouts']."'"
			);
			
			$voucher_layouts = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_voucher_layouts',
					'imagem_topo',
					'imagem_textura',
					'cor_fundo',
				))
				,
				"voucher_layouts",
				"WHERE status='A'"
				." ORDER BY id_voucher_layouts ASC"
			);
			
			// ============================== Lista Pedidos
			
			$usuario_pedidos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't2.id_pedidos',
					't2.data',
					't2.codigo',
				))
				,
				"usuario_pedidos as t1,pedidos as t2",
				"WHERE t1.id_usuario='".$usuario['id_usuario']."'"
				." AND t1.id_pedidos=t2.id_pedidos"
				." AND t2.status='A'"
				." ORDER BY t2.data DESC"
			);
			
			$lista_pedidos = '<select id="_voucher-lista-pedidos">';
			
			if($usuario_pedidos)
			foreach($usuario_pedidos as $usuario_pedido){
				$lista_pedidos .= '
				<option value="'.$usuario_pedido['t2.id_pedidos'].'"'.($id_pedidos == $usuario_pedido['t2.id_pedidos'] ? ' selected' : '').'>'.data_hora_from_datetime_to_text($usuario_pedido['t2.data']).' - '.$usuario_pedido['t2.codigo'].'</option>';
			}
			
			$lista_pedidos .= '
			</select>';
			
			$data_full = $pedido['data'];
			$data_arr = explode(' ',$data_full);
			
			$voucher_base = modelo_var_troca($voucher_base,"#data-expiracao#",date("d/m/Y",strtotime($data_arr[0] . " + ".$_ECOMMERCE['pedido_validade']." day")));
			
			$voucher_menu = modelo_var_troca($voucher_menu,"#lista-pedidos#",$lista_pedidos);
			$voucher_menu = modelo_var_troca($voucher_menu,"#presente-flag#",($pedido['presente'] ? '2' : '1'));
			
			$voucher_presente = modelo_var_troca($voucher_presente,"#de#",$pedido['de']);
			$voucher_presente = modelo_var_troca($voucher_presente,"#para#",$pedido['para']);
			$voucher_presente = modelo_var_troca($voucher_presente,"#mensagem#",$pedido['mensagem']);
			
			$voucher = modelo_var_troca($voucher,"#voucher-topo#",$voucher_topo);
			$voucher = modelo_var_troca($voucher,"#voucher-base#",$voucher_base);
			
			$voucher = modelo_var_troca($voucher,"#voucher-codigo#",$pedido['codigo']);
			$voucher = modelo_var_troca($voucher,"#voucher-senha#",$pedido['senha']);
			$voucher = modelo_var_troca($voucher,"#voucher-de#",$pedido['de']);
			$voucher = modelo_var_troca($voucher,"#voucher-para#",$pedido['para']);
			$voucher = modelo_var_troca($voucher,"#voucher-mensagem#",$pedido['mensagem']);
			$voucher = modelo_var_troca($voucher,"#voucher-concluir#",$voucher_concluir);
			
			// ============ Voucher Layout
			
			if($voucher_layout){
				$voucher = modelo_var_troca($voucher,"#imagem_topo#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$voucher_layout[0]['imagem_topo']);
				$voucher = modelo_var_troca_tudo($voucher,"#imagem_textura#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$voucher_layout[0]['imagem_textura']);
				$voucher = modelo_var_troca($voucher,"#cor_fundo#",$voucher_layout[0]['cor_fundo']);
				
				$id_voucher_layouts = $voucher_layout[0]['id_voucher_layouts'];
			} else {
				$voucher = modelo_var_troca($voucher,"#imagem_topo#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$voucher_layouts[0]['imagem_topo']);
				$voucher = modelo_var_troca_tudo($voucher,"#imagem_textura#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$voucher_layouts[0]['imagem_textura']);
				$voucher = modelo_var_troca($voucher,"#cor_fundo#",$voucher_layouts[0]['cor_fundo']);
				
				$id_voucher_layouts = $voucher_layouts[0]['id_voucher_layouts'];
			}
			
			$cel_nome = 'voucher-layouts'; $cel[$cel_nome] = modelo_tag_val($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
			$voucher = modelo_tag_in($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
			
			if($voucher_layouts)
			foreach($voucher_layouts as $vl){
				$cel_aux = $cel[$cel_nome];
				
				$cel_aux = modelo_var_troca($cel_aux,"#voucher-tema-img-topo#",'/'.$_SYSTEM['ROOT'].$vl['imagem_topo']);
				$cel_aux = modelo_var_troca($cel_aux,"#voucher-tema-img-textura#",'/'.$_SYSTEM['ROOT'].$vl['imagem_textura']);
				$cel_aux = modelo_var_troca($cel_aux,"#voucher-tema-id#",$vl['id_voucher_layouts']);
				
				if($id_voucher_layouts == $vl['id_voucher_layouts']){
					$cel_aux = modelo_var_troca($cel_aux,"#voucher-tema-class#",'_voucher-temas-escolher1');
				} else {
					$cel_aux = modelo_var_troca($cel_aux,"#voucher-tema-class#",'_voucher-temas-escolher2');
				}
				
				$count++;
				$voucher = modelo_var_in($voucher,'<!-- '.$cel_nome.' -->',$cel_aux.($count % $voucher_temas_colunas == 0 ? '<div class="clear"></div>' : ''));
			}
			$voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' -->','');
			
			$voucher = modelo_var_troca($voucher,'#voucher-tema-id-pedido#',$id_pedidos);
			
			// ============
			
			$cel_nome = 'lista-servicos'; $cel[$cel_nome] = modelo_tag_val($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->');
			$voucher = modelo_tag_in($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','<!-- '.$cel_nome.' -->');
			
			$pedidos_servicos = banco_select_name
			(
				banco_campos_virgulas(Array(
					't1.codigo',
					't1.senha',
					't1.validade',
					't2.nome',
					't2.observacao',
					't1.voucher_por_servico',
					't1.de',
					't1.para',
					't1.mensagem',
				))
				,
				"pedidos_servicos as t1,servicos as t2",
				"WHERE t1.id_pedidos='".$id_pedidos."'"
				." AND t1.id_servicos=t2.id_servicos"
				." AND t1.status='A'"
				." ORDER BY t2.nome ASC"
			);
			
			$lista_servicos = '<div id="_voucher-lista-servicos-cont"><label>Servi&ccedil;os:</label><select id="_voucher-lista-servicos">';
			
			if($pedidos_servicos)
			foreach($pedidos_servicos as $pedido_servico){
				$flag_pedidos_servicos = true;
				
				if($pedido['voucher_por_servico'])
				if(!$pedido_servico['t1.voucher_por_servico']){
					$flag_pedidos_servicos = false;
				} else {
					if(
						$pedido_servico['t1.de'] && 
						$pedido_servico['t1.para'] && 
						$pedido_servico['t1.mensagem']
					){
						$pedido['de'] = $pedido_servico['t1.de'];
						$pedido['para'] = $pedido_servico['t1.para'];
						$pedido['mensagem'] = $pedido_servico['t1.mensagem'];
					}
				}
				
				if($flag_pedidos_servicos){
					$cel_nome = 'lista-servicos';
					$cel_aux = $cel[$cel_nome];
					
					$cel_aux = modelo_var_troca($cel_aux,"#servico-nome#",$pedido_servico['t2.nome'].($pedido_servico['t2.observacao']? '<br><span style="color:#888;"><b>Observa&ccedil;&atilde;o:</b> '.$pedido_servico['t2.observacao'] .'</span>': ''));
					$cel_aux = modelo_var_troca($cel_aux,"#servico-validade#",date("d/m/Y",strtotime($data_arr[0] . " + ".$pedido_servico['t1.validade']." day")));
					$cel_aux = modelo_var_troca($cel_aux,"#servico-codigo#",$pedido_servico['t1.codigo']);
					$cel_aux = modelo_var_troca($cel_aux,"#servico-senha#",$pedido_servico['t1.senha']);
					
					$voucher = modelo_var_in($voucher,'<!-- '.$cel_nome.' -->',$cel_aux);
				}
				
				$lista_servicos .= '
				<option value="'.$pedido_servico['t1.codigo'].'"'.($pedido_servico['t1.voucher_por_servico']?' selected="selected"':'').'>'.$pedido_servico['t1.codigo'].' - '.$pedido_servico['t2.nome'].'</option>';
				
				$count2++;
				
			}
			$voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' -->','');
			
			$lista_servicos .= '
			</select><div class="clear"></div></div>';
			
			if($count2 > 1){
				$lista_voucher = '<label>Voucher:</label><select id="_voucher-lista-voucher">';
				
				$lista_voucher .= '
					<option value="0">TODOS os serviços do pedido num único voucher.</option>';
				$lista_voucher .= '
					<option value="1"'.($pedido['voucher_por_servico']?' selected="selected"':'').'>CADA serviço individualizado em um ou mais voucher(s).</option>';
				
				$lista_voucher .= '
				</select><div class="clear"></div>';
			} else {
				$lista_servicos = '';
			}
			
			$voucher_menu = modelo_var_troca($voucher_menu,"#lista-servicos#",$lista_servicos);
			$voucher_menu = modelo_var_troca($voucher_menu,"#lista-voucher#",$lista_voucher);
			
			$voucher_presente = modelo_var_troca($voucher_presente,"#de#",$pedido['de']);
			$voucher_presente = modelo_var_troca($voucher_presente,"#para#",$pedido['para']);
			$voucher_presente = modelo_var_troca($voucher_presente,"#mensagem#",$pedido['mensagem']);
			
			$voucher = modelo_var_troca($voucher,"#voucher-de#",$pedido['de']);
			$voucher = modelo_var_troca($voucher,"#voucher-para#",$pedido['para']);
			$voucher = modelo_var_troca($voucher,"#voucher-mensagem#",$pedido['mensagem']);
			
			$voucher_impressao = $voucher;
			$cel_nome = 'remover_impressao'; $voucher_impressao = modelo_tag_in($voucher_impressao,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			
			if(!$pedido['presente']){
				$cel_nome = 'de-para'; $voucher_impressao = modelo_tag_in($voucher_impressao,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
				$cel_nome = 'de-para'; $voucher = modelo_tag_in($voucher,'<!-- '.$cel_nome.' < -->','<!-- '.$cel_nome.' > -->','');
			} else {
				$cel_nome = 'de-para'; $voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' < -->',''); $voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' > -->','');
			}
			
			$_SESSION[$_SYSTEM['ID']."versao-impressao"] = $voucher_impressao;
			
			$cel_nome = 'remover_impressao'; $voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' < -->',''); $voucher = modelo_var_troca($voucher,'<!-- '.$cel_nome.' > -->','');
			
			$voucher = $voucher_titulo.$voucher_menu.$voucher.$voucher_presente.$voucher_email.'<div id="_voucher-rodape"></div>';
			
			return $voucher;
		} else {
			alerta('<p style="color:red;">O seu pedido não está ativo</p><p>Houve algum problema com o pagamento ou o mesmo está em processamento.</p><p>Se você efetuou o pagamento e houve confirmação, aguarde no máximo 30 minutos até o sistema atualizar os pagamentos e tente novamente. De qualquer forma o sistema enviará automaticamente o seu voucher no seu email assim que houver confirmação de pagamento pelo sistema de pagamento escolhido.</p>');
			redirecionar('/');
		}
	} else {
		if($pedido_atual_nao_pago){
			redirecionar('meus-pedidos');
		} else {
			alerta('<p style="color:red;">Você não tem pedidos cadastrados</p>');
			redirecionar('/');
		}
	}
}

function ecommerce_voucher_form_presente(){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_ECOMMERCE;
	
	ecommerce_permissao_acesso();
	
	$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
	
	if(!$_CONEXAO_BANCO)$connect_db = true;
	if($connect_db)banco_conectar();
	
	$usuario_pedidos = banco_select_name
	(
		banco_campos_virgulas(Array(
			'id_pedidos',
		))
		,
		"usuario_pedidos",
		"WHERE id_usuario='".$usuario['id_usuario']."'"
		." AND pedido_atual IS NOT NULL"
	);
	
	if($usuario_pedidos){
		$id_pedidos = $usuario_pedidos[0]['id_pedidos'];
		
		$pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'voucher_por_servico',
				'de',
				'para',
				'mensagem',
			))
			,
			"pedidos",
			"WHERE id_pedidos='".$id_pedidos."'"
			." AND voucher_por_servico IS NOT NULL"
		);
		
		if($pedidos){
			$pedidos_servicos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_pedidos_servicos',
					'codigo',
				))
				,
				"pedidos_servicos",
				"WHERE id_pedidos='".$id_pedidos."'"
				." AND voucher_por_servico IS NOT NULL"
			);
			
			$campo_nome = "de"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
			$campo_nome = "para"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
			$campo_nome = "mensagem"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
			
			$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
			
			if($editar_sql[$campo_tabela]){
				banco_update
				(
					$editar_sql[$campo_tabela],
					"pedidos_servicos",
					"WHERE id_pedidos_servicos='".$pedidos_servicos[0]['id_pedidos_servicos']."'"
				);
			}
			
			log_banco(Array(
				'id_referencia' => $id_pedidos,
				'grupo' => 'pedidos',
				'valor' => '<b>Usuário:</b> mudou mensagem do PEDIDO SERVIÇO:'.$pedidos_servicos[0]['codigo'].' - <b>De:</b> '.strip_tags($_REQUEST['de']).' <b>Para:</b> '.strip_tags($_REQUEST['para']).' <b>Mensagem:</b> '.strip_tags($_REQUEST['mensagem']),
			));
			
			if(
				!$pedidos[0]['de'] ||
				!$pedidos[0]['para'] ||
				!$pedidos[0]['mensagem']
			){
				$campo_nome = "de"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
				$campo_nome = "para"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
				$campo_nome = "mensagem"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
				
				$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
				
				if($editar_sql[$campo_tabela]){
					banco_update
					(
						$editar_sql[$campo_tabela],
						"pedidos",
						"WHERE id_pedidos='".$id_pedidos."'"
					);
				}
			}
		} else {
			$campo_nome = "de"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
			$campo_nome = "para"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
			$campo_nome = "mensagem"; $editar[$campo_tabela][] = $campo_nome."='" . strip_tags($_REQUEST[$campo_nome]) . "'";
			
			$editar_sql[$campo_tabela] = banco_campos_virgulas($editar[$campo_tabela]);
			
			if($editar_sql[$campo_tabela]){
				banco_update
				(
					$editar_sql[$campo_tabela],
					"pedidos",
					"WHERE id_pedidos='".$id_pedidos."'"
				);
			}
			
			log_banco(Array(
				'id_referencia' => $id_pedidos,
				'grupo' => 'pedidos',
				'valor' => '<b>Usuário:</b> mudou mensagem <b>De:</b> '.strip_tags($_REQUEST['de']).' <b>Para:</b> '.strip_tags($_REQUEST['para']).' <b>Mensagem:</b> '.strip_tags($_REQUEST['mensagem']),
			));
		}
	}
	
	redirecionar('voucher');
}

function ecommerce_carrinho(){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}
	
	if($_PROJETO['ecommerce']){
		if($_PROJETO['ecommerce']['carrinho_layout']){
			$layout = $_PROJETO['ecommerce']['carrinho_layout'];
		}
	}
	
	$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
	$_HTML_DADOS['titulo'] = $titulo . 'Carrinho de Compras.';
	
	$_HTML_DADOS['description'] = 'Página para gerenciar os itens de um pedido.';
	$_HTML_DADOS['keywords'] = 'carrinho,pedido itens,carrinho de compra';
	
	if(!$layout){
		$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
		$pagina = modelo_tag_val($modelo,'<!-- carrinho'.($loja_online_produtos?'2':'').' < -->','<!-- carrinho'.($loja_online_produtos?'2':'').' > -->');
		
		$layout = $pagina;
	}
	
	return $layout;
}

function ecommerce_parcela_valor_pagseguro($valor_total,$parcela){
	$fator = Array(
		1 => 1,
		2 => 0.52255,
		3 => 0.35347,
		4 => 0.26898,
		5 => 0.21830,
		6 => 0.18453,
		7 => 0.16044,
		8 => 0.14240,
		9 => 0.12838,
		10 => 0.11717,
		11 => 0.10802,
		12 => 0.10040,
		13 => 0.09397,
		14 => 0.08846,
		15 => 0.08371,
		16 => 0.07955,
		17 => 0.07589,
		18 => 0.07265,
	);
	
	$parcela_valor = $valor_total * $fator[$parcela];
	
	if($parcela_valor >= 5){
		return Array($parcela => $parcela_valor);
	} else {
		if($parcela > 2){
			return ecommerce_parcela_valor_pagseguro($valor_total,$parcela-1);
		} else {
			return Array(1 => $valor_total);
		}
	}
}

function ecommerce_loja_online($params = false){
	if($params)foreach($params as $var => $val)$$var = $val;
	
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_ECOMMERCE;
	global $_VARIAVEIS_JS;
	global $_VARS;
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}

	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['limite_texto']){
		$limite_texto = $_PROJETO['ecommerce']['limite_texto'];
	}
	
	if($loja_online_produtos){
		$colunas = 3;
		$cel_nome = 'loja-online';
		$sem_resultados_titulo = 'Sem produtos disponíveis para venda!';
		
		if($ajax){
			$pagina = '<!-- '.$cel_nome.' -->';
		} else {
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['loja_online']){
					$pagina = $_PROJETO['ecommerce']['loja_online'];
				}
			}
			
			if(!$pagina){
				if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- loja-online < -->','<!-- loja-online > -->');
			}
			
			$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
			if(!$nao_mudar_titulo)$_HTML_DADOS['titulo'] = $titulo . 'Loja Online.';
			
			if(!$nao_mudar_metas){
				$_HTML_DADOS['description'] = 'Página de listagem de todos os produtos/serviços desse portal.';
				$_HTML_DADOS['keywords'] = 'loja online,loja,serviços,produtos';
			}
		}
		
		if($_REQUEST['page']){
			$page = (int)$_REQUEST['page'];
		}
		if($_REQUEST['limite']){
			$limite = (int)$_REQUEST['limite'];
		} else {
			if(!$limite){
				$limite = 18;
				
				if($_PROJETO['ecommerce'])
				if($_PROJETO['ecommerce']['loja_online_limite']){
					$limite = $_PROJETO['ecommerce']['loja_online_limite'];
				}
			}
		}
		
		if($_REQUEST['categorias_produtos']) $categorias_produtos = $_REQUEST['categorias_produtos'];
		
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		if($categorias_produtos){
			$categorias_arr = explode(',',$categorias_produtos);
			
			foreach($categorias_arr as $cat){
				$categorias_produtos_sql .= ($categorias_produtos_sql?" OR ":"")."t1.id_categorias_produtos='".$cat."'";
			}
		}
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.id_produtos',
				't1.nome',
				't1.imagem_path',
				't1.preco',
				't1.descricao',
				't1.desconto',
				't1.desconto_de',
				't1.desconto_ate',
				't1.quantidade',
				't2.caminho_raiz',
				't2.identificador',
			))
			,
			"produtos as t1,conteudo as t2,conteudo_permissao as t3",
			"WHERE t2.produto=t1.id_produtos"
			.($categorias_produtos?" AND (".$categorias_produtos_sql.")":"")
			." AND t3.produto IS NOT NULL"
			." AND t1.status='A'"
			." AND IF(visivel_de IS NOT NULL, visivel_de <= NOW(), 1)"
			." AND IF(visivel_ate IS NOT NULL, visivel_ate >= NOW(), 1)"
			." GROUP BY t2.identificador"
			." ORDER BY t1.nome ASC"
			." LIMIT ".($page?($page*$limite).',':'') . ($limite + 1)
		);
		
		$cel[$cel_nome] = '
		<div class="cont-'.$cel_nome.'">
			#campo#
			<div class="clear"></div>
		</div>';
		$barra = '
		<div class="cont-'.$cel_nome.'-barra"></div>';
		
		if($resultado){
			foreach($resultado as $produto){
				$time = time();
				$desconto_de_ate = false;
				
				if((int)$produto['t1.quantidade'] > 0){
					if($produto['t1.desconto']){
						$desconto_de_ate = true;
					}
					
					if($produto['t1.desconto_de']){
						$desconto_de_ate = true;
						$de = strtotime($produto['t1.desconto_de']);
						
						if($time < $de){
							$desconto_de_ate = false;
						}
					}
					
					if($produto['t1.desconto_ate']){
						$desconto_de_ate = true;
						$ate = strtotime($produto['t1.desconto_ate']);
						
						if($time > $ate){
							$desconto_de_ate = false;
						}
					}
					
					$layout = false;
					if($_PROJETO['ecommerce']){
						if($_PROJETO['ecommerce']['loja_online_layout']){
							$layout = $_PROJETO['ecommerce']['loja_online_layout'];
						}
					}
					
					if(!$layout){
						$layout = '
						
						<a href="/'.$_SYSTEM['ROOT'].'#url#" class="_loja-online-cont">
							<div class="_loja-online-img">#img#</div>
							<div class="_loja-online-titulo">#titulo#</div>
							<div class="_loja-online-descricao">#descricao#</div>
							<div class="_loja-online-preco">#preco#</div>
							<div class="clear"></div>
						</a>';
					}
					
					if($desconto_de_ate){
						$valor_desconto_float = (($produto['t1.preco'] * (100 - $produto['t1.desconto'])) / 100);
						$parcela_valor = ecommerce_parcela_valor_pagseguro($valor_desconto_float,10);
						
						if($parcela_valor)
						foreach($parcela_valor as $parcela => $valor_parcela){
							break;
						}
						
						$valor_total_parcelas = number_format(($valor_parcela*$parcela), 2, ",", ".");
						$valor_parcela = number_format(($valor_parcela?$valor_parcela:0), 2, ",", ".");
						$valor_total = number_format(($produto['t1.preco']?$produto['t1.preco']:0), 2, ",", ".");
						$valor_desconto = number_format($valor_desconto_float, 2, ",", ".");
						
						$preco = 'De <span class="_loja-online-valtxt4">R$ '.$valor_total.'</span><br>Por <span class="_loja-online-valtxt3">R$ '.$valor_desconto.'</span>'.($parcela > 1 ? '<br>em <span class="_loja-online-valtxt2">'.$parcela.'x</span> de <span class="_loja-online-valtxt2">R$ '.$valor_parcela.'</span>':'');
					} else {
						$parcela_valor = ecommerce_parcela_valor_pagseguro($produto['t1.preco'],10);
						
						if($parcela_valor)
						foreach($parcela_valor as $parcela => $valor_parcela){
							break;
						}
						
						$valor_total_parcelas = number_format(($valor_parcela*$parcela), 2, ",", ".");
						$valor_parcela = number_format(($valor_parcela?$valor_parcela:0), 2, ",", ".");
						$valor_total = number_format(($produto['t1.preco']?$produto['t1.preco']:0), 2, ",", ".");
						
						$preco = '<span class="_loja-online-valtxt">R$ '.$valor_total.'</span>'.($parcela > 1 ? '<br>em <span class="_loja-online-valtxt2">'.$parcela.'x</span> de <span class="_loja-online-valtxt2">R$ '.$valor_parcela.'</span>':'');
					}
					
					$caminho_fisico = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."produtos".$_SYSTEM['SEPARADOR']."produto".$produto['t1.id_produtos'].$_SYSTEM['SEPARADOR'];
					$caminho_internet = '/'.$_SYSTEM['ROOT']."files/produtos/produto".$produto['t1.id_produtos']."/";
					
					if(is_dir($caminho_fisico)){
						$abreDir = opendir($caminho_fisico);

						while (false !== ($file = readdir($abreDir))) {
							if ($file==".." || $file ==".") continue;
							
							if(preg_match('/produto_mini_/i', $file) > 0){
								$idExt = preg_replace('/produto_mini_/i', '', $file);
								$idExtArr = explode('.',$idExt);
								
								$mini = $file;
								$pequena = preg_replace('/mini_/i', 'pequeno_', $file);
								$grande = preg_replace('/mini_/i', '', $file);
								
								if(!$primeiraImagem){
									$imagem_path = '<img src="'.$caminho_internet.$pequena.'" data-zoom-image="'.$caminho_internet.$grande.'"/>';
								}
								
								break;
							}
						}

						closedir($abreDir);
					}
					
					$layout = modelo_var_troca($layout,"#img#",$imagem_path);
					$layout = modelo_var_troca($layout,"#tit#",$produto['t1.nome']);
					$layout = modelo_var_troca_tudo($layout,"#titulo#",($limite_texto?limitar_texto_html($produto['t1.nome'],$limite_texto, $tags_permitidas = ''):$produto['t1.nome']));
					$layout = modelo_var_troca_tudo($layout,"#descricao#",($limite_texto?limitar_texto_html($produto['t1.descricao'],$limite_texto, $tags_permitidas = ''):$produto['t1.descricao']));
					$layout = modelo_var_troca($layout,"#preco#",$preco);
					$layout = modelo_var_troca($layout,"#url#",$produto['t2.caminho_raiz'].$produto['t2.identificador']);
					
					$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$layout);
				} else {
					$layout = false;
					if($_PROJETO['ecommerce']){
						if($_PROJETO['ecommerce']['loja_online_indisponivel_layout']){
							$layout = $_PROJETO['ecommerce']['loja_online_indisponivel_layout'];
						}
					}
					
					if(!$layout){
						$layout = '
						
						<a href="/'.$_SYSTEM['ROOT'].'#url#" class="_loja-online-cont">
							<div class="_loja-online-img">#img#</div>
							<div class="_loja-online-titulo">#titulo#</div>
							<div class="_loja-online-descricao">#descricao#</div>
							<div class="_loja-online-indisponivel">Produto Indisponível</div>
							<div class="clear"></div>
						</a>';
					}
					
					$caminho_fisico = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."produtos".$_SYSTEM['SEPARADOR']."produto".$produto['t1.id_produtos'].$_SYSTEM['SEPARADOR'];
					$caminho_internet = '/'.$_SYSTEM['ROOT']."files/produtos/produto".$produto['t1.id_produtos']."/";
					
					if(is_dir($caminho_fisico)){
						$abreDir = opendir($caminho_fisico);

						while (false !== ($file = readdir($abreDir))) {
							if ($file==".." || $file ==".") continue;
							
							if(preg_match('/produto_mini_/i', $file) > 0){
								$idExt = preg_replace('/produto_mini_/i', '', $file);
								$idExtArr = explode('.',$idExt);
								
								$mini = $file;
								$pequena = preg_replace('/mini_/i', 'pequeno_', $file);
								$grande = preg_replace('/mini_/i', '', $file);
								
								if(!$primeiraImagem){
									$imagem_path = '<img src="'.$caminho_internet.$pequena.'" data-zoom-image="'.$caminho_internet.$grande.'"/>';
								}
								
								break;
							}
						}

						closedir($abreDir);
					}
					
					$layout = modelo_var_troca($layout,"#img#",$imagem_path);
					$layout = modelo_var_troca($layout,"#tit#",$produto['t1.nome']);
					$layout = modelo_var_troca_tudo($layout,"#titulo#",($limite_texto?limitar_texto_html($produto['t1.nome'],$limite_texto, $tags_permitidas = ''):$produto['t1.nome']));
					$layout = modelo_var_troca_tudo($layout,"#descricao#",($limite_texto?limitar_texto_html($produto['t1.descricao'],$limite_texto, $tags_permitidas = ''):$produto['t1.descricao']));
					$layout = modelo_var_troca($layout,"#url#",$produto['t2.caminho_raiz'].$produto['t2.identificador']);
					
					$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$layout);
				}
				
				$flag = true;
				$cont++;
				if($cont % $colunas == 0){
					$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->','<div class="clear"></div>');
				}
				
				if($cont >= $limite){
					break;
				}
			}
			
			$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
		} else {
			$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->',$sem_resultados_titulo);
		}
		
		$data_vars = Array(
			'limite' => $limite,
			'opcao' => $cel_nome,
		);
		
		$_VARIAVEIS_JS['mais_resultados'] = json_encode($data_vars);
		
		if($page){
			$page++;
		} else {
			$page = 1;
			$mais_resultados = '
		'.(!$ajax ? '<div class="clear"></div>' : '').'
		<div id="cont-'.$cel_nome.'-mais"'.($categorias_produtos?' data-categorias_produtos="'.$categorias_produtos.'"':'').'>Mais Resultados</div>';
		}
		
		if($limite >= count($resultado)){
			$sem_mais_resultados = true;
		} else {
			$pagina .= $mais_resultados;
		}
		
		if($ajax){
			return Array(
				'pagina' => $pagina,
				'sem_mais' => $sem_mais_resultados,
			);
		} else 
		return $pagina.'
			<div class="clear"></div>' . $_VARS['ecommerce']['loja_online_script_facebook'];
	} else {
		$colunas = 3;
		$cel_nome = 'loja-online';
		$sem_resultados_titulo = 'Sem serviços disponíveis para venda!';
		
		if($ajax){
			$pagina = '<!-- '.$cel_nome.' -->';
		} else {
			if($_PROJETO['ecommerce']){
				if($_PROJETO['ecommerce']['loja_online']){
					$pagina = $_PROJETO['ecommerce']['loja_online'];
				}
			}
			
			if(!$pagina){
				if(!$modelo)$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
				$pagina = modelo_tag_val($modelo,'<!-- loja-online < -->','<!-- loja-online > -->');
			}
			
			$titulo = $_HTML['TITULO'] . $_HTML['TITULO_SEPARADOR'];
			$_HTML_DADOS['titulo'] = $titulo . 'Loja Online.';
			
			$_HTML_DADOS['description'] = 'Página de listagem de todos os produtos/serviços desse portal.';
			$_HTML_DADOS['keywords'] = 'loja online,loja,serviços,produtos';
		}
		
		if($_REQUEST['page']){
			$page = (int)$_REQUEST['page'];
		}
		if($_REQUEST['limite']){
			$limite = (int)$_REQUEST['limite'];
		} else {
			if(!$limite){
				$limite = 18;
				
				if($_PROJETO['ecommerce'])
				if($_PROJETO['ecommerce']['loja_online_limite']){
					$limite = $_PROJETO['ecommerce']['loja_online_limite'];
				}
			}
		}
		
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.id_servicos',
				't1.nome',
				't1.imagem_path',
				't1.preco',
				't1.desconto',
				't1.desconto_de',
				't1.desconto_ate',
				't1.quantidade',
				't2.caminho_raiz',
				't2.identificador',
			))
			,
			"servicos as t1,conteudo as t2,conteudo_permissao as t3",
			"WHERE t2.servico=t1.id_servicos"
			." AND t3.servico IS NOT NULL"
			." AND t1.status='A'"
			." AND IF(visivel_de IS NOT NULL, visivel_de <= NOW(), 1)"
			." AND IF(visivel_ate IS NOT NULL, visivel_ate >= NOW(), 1)"
			." GROUP BY t2.identificador"
			." ORDER BY t1.nome ASC"
			." LIMIT ".($page?($page*$limite).',':'') . ($limite + 1)
		);
		
		$cel[$cel_nome] = '
		<div class="cont-'.$cel_nome.'">
			#campo#
			<div class="clear"></div>
		</div>';
		$barra = '
		<div class="cont-'.$cel_nome.'-barra"></div>';
		
		if($resultado){
			foreach($resultado as $servico){
				$time = time();
				$desconto_de_ate = false;
				
				if((int)$servico['t1.quantidade'] > 0){
					if($servico['t1.desconto']){
						$desconto_de_ate = true;
					}
					
					if($servico['t1.desconto_de']){
						$desconto_de_ate = true;
						$de = strtotime($servico['t1.desconto_de']);
						
						if($time < $de){
							$desconto_de_ate = false;
						}
					}
					
					if($servico['t1.desconto_ate']){
						$desconto_de_ate = true;
						$ate = strtotime($servico['t1.desconto_ate']);
						
						if($time > $ate){
							$desconto_de_ate = false;
						}
					}
					
					$layout = false;
					if($_PROJETO['ecommerce']){
						if($_PROJETO['ecommerce']['loja_online_layout']){
							$layout = $_PROJETO['ecommerce']['loja_online_layout'];
						}
					}
					
					if(!$layout){
						$layout = '
						
						<a href="/'.$_SYSTEM['ROOT'].'#url#" class="_loja-online-cont">
							<div class="_loja-online-img">#img#</div>
							<div class="_loja-online-titulo">#titulo#</div>
							<div class="_loja-online-preco">#preco#</div>
							<div class="clear"></div>
						</a>';
					}
					
					if($desconto_de_ate){
						$valor_desconto_float = (($servico['t1.preco'] * (100 - $servico['t1.desconto'])) / 100);
						$parcela_valor = ecommerce_parcela_valor_pagseguro($valor_desconto_float,10);
						
						if($parcela_valor)
						foreach($parcela_valor as $parcela => $valor_parcela){
							break;
						}
						
						$valor_total_parcelas = number_format(($valor_parcela*$parcela), 2, ",", ".");
						$valor_parcela = number_format($valor_parcela, 2, ",", ".");
						$valor_total = number_format($servico['t1.preco'], 2, ",", ".");
						$valor_desconto = number_format($valor_desconto_float, 2, ",", ".");
						
						$preco = 'De <span class="_loja-online-valtxt4">R$ '.$valor_total.'</span><br>Por <span class="_loja-online-valtxt3">R$ '.$valor_desconto.'</span>'.($parcela > 1 ? '<br>em <span class="_loja-online-valtxt2">'.$parcela.'x</span> de <span class="_loja-online-valtxt2">R$ '.$valor_parcela.'</span>':'');
					} else {
						$parcela_valor = ecommerce_parcela_valor_pagseguro($servico['t1.preco'],10);
						
						if($parcela_valor)
						foreach($parcela_valor as $parcela => $valor_parcela){
							break;
						}
						
						$valor_total_parcelas = number_format(($valor_parcela*$parcela), 2, ",", ".");
						$valor_parcela = number_format($valor_parcela, 2, ",", ".");
						$valor_total = number_format($servico['t1.preco'], 2, ",", ".");
						
						$preco = '<span class="_loja-online-valtxt">R$ '.$valor_total.'</span>'.($parcela > 1 ? '<br>em <span class="_loja-online-valtxt2">'.$parcela.'x</span> de <span class="_loja-online-valtxt2">R$ '.$valor_parcela.'</span>':'');
					}
					
					$layout = modelo_var_troca($layout,"#img#",'<img src="'.path_com_versao_arquivo($servico['t1.imagem_path']).' alt="'.$servico['t1.nome'].'">');
					$layout = modelo_var_troca($layout,"#tit#",$servico['t1.nome']);
					$layout = modelo_var_troca_tudo($layout,"#titulo#",$servico['t1.nome']);
					$layout = modelo_var_troca($layout,"#preco#",$preco);
					$layout = modelo_var_troca($layout,"#url#",$servico['t2.caminho_raiz'].$servico['t2.identificador']);
					
					$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$layout);
				} else {
					$layout = false;
					if($_PROJETO['ecommerce']){
						if($_PROJETO['ecommerce']['loja_online_indisponivel_layout']){
							$layout = $_PROJETO['ecommerce']['loja_online_indisponivel_layout'];
						}
					}
					
					if(!$layout){
						$layout = '
						
						<a href="/'.$_SYSTEM['ROOT'].'#url#" class="_loja-online-cont">
							<div class="_loja-online-img">#img#</div>
							<div class="_loja-online-titulo">#titulo#</div>
							<div class="_loja-online-descricao">#descricao#</div>
							<div class="_loja-online-indisponivel">Serviço Indisponível</div>
							<div class="clear"></div>
						</a>';
					}
					
					$caminho_fisico = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."produtos".$_SYSTEM['SEPARADOR']."produto".$produto['t1.id_produtos'].$_SYSTEM['SEPARADOR'];
					$caminho_internet = '/'.$_SYSTEM['ROOT']."files/produtos/produto".$produto['t1.id_produtos']."/";
					
					if(is_dir($caminho_fisico)){
						$abreDir = opendir($caminho_fisico);

						while (false !== ($file = readdir($abreDir))) {
							if ($file==".." || $file ==".") continue;
							
							if(preg_match('/produto_mini_/i', $file) > 0){
								$idExt = preg_replace('/produto_mini_/i', '', $file);
								$idExtArr = explode('.',$idExt);
								
								$mini = $file;
								$pequena = preg_replace('/mini_/i', 'pequeno_', $file);
								$grande = preg_replace('/mini_/i', '', $file);
								
								if(!$primeiraImagem){
									$imagem_path = '<img src="'.$caminho_internet.$pequena.'" data-zoom-image="'.$caminho_internet.$grande.'"/>';
								}
								
								break;
							}
						}

						closedir($abreDir);
					}
					
					$layout = modelo_var_troca($layout,"#img#",$imagem_path);
					$layout = modelo_var_troca($layout,"#tit#",$servico['t1.nome']);
					$layout = modelo_var_troca_tudo($layout,"#titulo#",($limite_texto?limitar_texto_html($servico['t1.nome'],$limite_texto, $tags_permitidas = ''):$servico['t1.nome']));
					$layout = modelo_var_troca_tudo($layout,"#descricao#",($limite_texto?limitar_texto_html($servico['t1.descricao'],$limite_texto, $tags_permitidas = ''):$servico['t1.descricao']));
					$layout = modelo_var_troca($layout,"#url#",$servico['t2.caminho_raiz'].$servico['t2.identificador']);
					
					$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->',$layout);
				}
				
				$flag = true;
				$cont++;
				if($cont % $colunas == 0){
					$pagina = modelo_var_in($pagina,'<!-- '.$cel_nome.' -->','<div class="clear"></div>');
				}
				
				if($cont >= $limite){
					break;
				}
				
			}
			
			$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->','');
		} else {
			$pagina = modelo_var_troca($pagina,'<!-- '.$cel_nome.' -->',$sem_resultados_titulo);
		}
		
		$data_vars = Array(
			'limite' => $limite,
			'opcao' => $cel_nome,
		);
		
		$_VARIAVEIS_JS['mais_resultados'] = json_encode($data_vars);
		
		if($page){
			$page++;
		} else {
			$page = 1;
			$mais_resultados = '
		'.(!$ajax ? '<div class="clear"></div>' : '').'
		<div id="cont-'.$cel_nome.'-mais">Mais Resultados</div>';
		}
		
		if($limite >= count($resultado)){
			$sem_mais_resultados = true;
		} else {
			$pagina .= $mais_resultados;
		}
		
		if($ajax){
			return Array(
				'pagina' => $pagina,
				'sem_mais' => $sem_mais_resultados,
			);
		} else 
		return $pagina.'
			<div class="clear"></div>' . $_VARS['ecommerce']['loja_online_script_facebook'];
	}
}

function ecommerce_layouts(){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_PROJETO;
	global $_VARS;
	

	$modelo = modelo_abrir($_SYSTEM['PATH'].'includes'.$_SYSTEM['SEPARADOR'].'ecommerce'.$_SYSTEM['SEPARADOR'].'html.html');
	$pagina = modelo_tag_val($modelo,'<!-- retorno_pagamento_sucesso2 < -->','<!-- retorno_pagamento_sucesso2 > -->');
	
	$layout = $pagina;

	$layout = modelo_var_troca($layout,"#texto-informativo#",$_VARS['ecommerce']['produtos_pagamento_efetuado']);

	
	return $layout;
}

function ecommerce_newsletter_produtos_servicos($id){
	global $_PROJETO;
	global $_HTML;
	global $_HTML_DADOS;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_ECOMMERCE;
	global $_VARIAVEIS_JS;
	global $_VARS;
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}

	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['limite_texto']){
		$limite_texto = $_PROJETO['ecommerce']['limite_texto'];
	}
	
	if($loja_online_produtos){
		$colunas = 3;
		$cel_nome = 'loja-online';
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.id_produtos',
				't1.nome',
				't1.imagem_path',
				't1.preco',
				't1.descricao',
				't1.desconto',
				't1.desconto_de',
				't1.desconto_ate',
				't1.quantidade',
				't2.caminho_raiz',
				't2.identificador',
			))
			,
			"produtos as t1,conteudo as t2,conteudo_permissao as t3",
			"WHERE t2.produto=t1.id_produtos"
			." AND t2.id_conteudo='".$id."'"
			." AND t3.produto IS NOT NULL"
			." AND t1.status='A'"
			." AND IF(visivel_de IS NOT NULL, visivel_de <= NOW(), 1)"
			." AND IF(visivel_ate IS NOT NULL, visivel_ate >= NOW(), 1)"
			." GROUP BY t2.identificador"
			." ORDER BY t1.nome ASC"
			." LIMIT 1"
		);
		
		if($resultado){
			$produto = $resultado[0];
			$time = time();
			$desconto_de_ate = false;
			
			if((int)$produto['t1.quantidade'] > 0){
				if($produto['t1.desconto']){
					$desconto_de_ate = true;
				}
				
				if($produto['t1.desconto_de']){
					$desconto_de_ate = true;
					$de = strtotime($produto['t1.desconto_de']);
					
					if($time < $de){
						$desconto_de_ate = false;
					}
				}
				
				if($produto['t1.desconto_ate']){
					$desconto_de_ate = true;
					$ate = strtotime($produto['t1.desconto_ate']);
					
					if($time > $ate){
						$desconto_de_ate = false;
					}
				}
				
				$layout = false;
				if($_PROJETO['ecommerce']){
					if($_PROJETO['ecommerce']['newsletter_loja_online_layout']){
						$layout = $_PROJETO['ecommerce']['newsletter_loja_online_layout'];
					}
				}
				
				if(!$layout){
					$layout = '
					
					<a href="#url#" class="_loja-online-cont">
						<div class="_loja-online-img">#img#</div>
						<div class="_loja-online-titulo">#titulo#</div>
						<div class="_loja-online-descricao">#descricao#</div>
						<div class="_loja-online-preco">#preco#</div>
						<div class="clear"></div>
					</a>';
				}
				
				if($desconto_de_ate){
					$valor_desconto_float = (($produto['t1.preco'] * (100 - $produto['t1.desconto'])) / 100);
					$parcela_valor = ecommerce_parcela_valor_pagseguro($valor_desconto_float,10);
					
					if($parcela_valor)
					foreach($parcela_valor as $parcela => $valor_parcela){
						break;
					}
					
					$valor_total_parcelas = number_format(($valor_parcela*$parcela), 2, ",", ".");
					$valor_parcela = number_format(($valor_parcela?$valor_parcela:0), 2, ",", ".");
					$valor_total = number_format(($produto['t1.preco']?$produto['t1.preco']:0), 2, ",", ".");
					$valor_desconto = number_format($valor_desconto_float, 2, ",", ".");
					
					if($_PROJETO['ecommerce']['newsletter_preco_2']){
						$preco = $_PROJETO['ecommerce']['newsletter_preco_2'];
						
						$preco = modelo_var_troca($preco,"#valor_total#",$valor_total);
						$preco = modelo_var_troca($preco,"#valor_desconto#",$valor_desconto);
						
						if($parcela > 1){
							$preco .= $_PROJETO['ecommerce']['newsletter_preco_parcelas'];
							
							$preco = modelo_var_troca($preco,"#parcela#",$parcela);
							$preco = modelo_var_troca($preco,"#valor_parcela#",$valor_parcela);
						}
					} else {
						$preco = 'De <span class="_loja-online-valtxt4">R$ '.$valor_total.'</span><br>Por <span class="_loja-online-valtxt3">R$ '.$valor_desconto.'</span>'.($parcela > 1 ? '<br>em <span class="_loja-online-valtxt2">'.$parcela.'x</span> de <span class="_loja-online-valtxt2">R$ '.$valor_parcela.'</span>':'');
					}
				} else {
					$parcela_valor = ecommerce_parcela_valor_pagseguro($produto['t1.preco'],10);
					
					if($parcela_valor)
					foreach($parcela_valor as $parcela => $valor_parcela){
						break;
					}
					
					$valor_total_parcelas = number_format(($valor_parcela*$parcela), 2, ",", ".");
					$valor_parcela = number_format(($valor_parcela?$valor_parcela:0), 2, ",", ".");
					$valor_total = number_format(($produto['t1.preco']?$produto['t1.preco']:0), 2, ",", ".");
					
					if($_PROJETO['ecommerce']['newsletter_preco_1']){
						$preco = $_PROJETO['ecommerce']['newsletter_preco_1'];
						
						$preco = modelo_var_troca($preco,"#valor_total#",$valor_total);
						$preco = modelo_var_troca($preco,"#valor_desconto#",$valor_desconto);
						
						if($parcela > 1){
							$preco .= $_PROJETO['ecommerce']['newsletter_preco_parcelas'];
							
							$preco = modelo_var_troca($preco,"#parcela#",$parcela);
							$preco = modelo_var_troca($preco,"#valor_parcela#",$valor_parcela);
						}
					} else {
						$preco = '<span class="_loja-online-valtxt">R$ '.$valor_total.'</span>'.($parcela > 1 ? '<br>em <span class="_loja-online-valtxt2">'.$parcela.'x</span> de <span class="_loja-online-valtxt2">R$ '.$valor_parcela.'</span>':'');
					}
				}
				
				$caminho_fisico = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."produtos".$_SYSTEM['SEPARADOR']."produto".$produto['t1.id_produtos'].$_SYSTEM['SEPARADOR'];
				$caminho_internet = 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT']."files/produtos/produto".$produto['t1.id_produtos']."/";
				
				if(is_dir($caminho_fisico)){
					$abreDir = opendir($caminho_fisico);

					while (false !== ($file = readdir($abreDir))) {
						if ($file==".." || $file ==".") continue;
						
						if(preg_match('/produto_mini_/i', $file) > 0){
							$idExt = preg_replace('/produto_mini_/i', '', $file);
							$idExtArr = explode('.',$idExt);
							
							$mini = $file;
							$pequena = preg_replace('/mini_/i', 'pequeno_', $file);
							$grande = preg_replace('/mini_/i', '', $file);
							
							if(!$primeiraImagem){
								$imagem_path = '<img src="'.$caminho_internet.$pequena.'" data-zoom-image="'.$caminho_internet.$grande.'"/>';
							}
							
							break;
						}
					}

					closedir($abreDir);
				}
				
				$layout = modelo_var_troca($layout,"#img#",$imagem_path);
				$layout = modelo_var_troca($layout,"#tit#",$produto['t1.nome']);
				$layout = modelo_var_troca_tudo($layout,"#titulo#",($limite_texto?limitar_texto_html($produto['t1.nome'],$limite_texto, $tags_permitidas = ''):$produto['t1.nome']));
				$layout = modelo_var_troca_tudo($layout,"#descricao#",($limite_texto?limitar_texto_html($produto['t1.descricao'],$limite_texto, $tags_permitidas = ''):$produto['t1.descricao']));
				$layout = modelo_var_troca($layout,"#preco#",$preco);
				$layout = modelo_var_troca($layout,"#url#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$produto['t2.caminho_raiz'].$produto['t2.identificador']);
				
				$pagina = $layout;
			} else {
				$layout = false;
				if($_PROJETO['ecommerce']){
					if($_PROJETO['ecommerce']['newsletter_loja_online_indisponivel_layout']){
						$layout = $_PROJETO['ecommerce']['newsletter_loja_online_indisponivel_layout'];
					}
				}
				
				if(!$layout){
					$layout = '
					
					<a href="#url#" class="_loja-online-cont">
						<div class="_loja-online-img">#img#</div>
						<div class="_loja-online-titulo">#titulo#</div>
						<div class="_loja-online-descricao">#descricao#</div>
						<div class="_loja-online-indisponivel">Produto Indispon&iacute;vel</div>
						<div class="clear"></div>
					</a>';
				}
				
				$caminho_fisico = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."produtos".$_SYSTEM['SEPARADOR']."produto".$produto['t1.id_produtos'].$_SYSTEM['SEPARADOR'];
				$caminho_internet = 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT']."files/produtos/produto".$produto['t1.id_produtos']."/";
				
				if(is_dir($caminho_fisico)){
					$abreDir = opendir($caminho_fisico);

					while (false !== ($file = readdir($abreDir))) {
						if ($file==".." || $file ==".") continue;
						
						if(preg_match('/produto_mini_/i', $file) > 0){
							$idExt = preg_replace('/produto_mini_/i', '', $file);
							$idExtArr = explode('.',$idExt);
							
							$mini = $file;
							$pequena = preg_replace('/mini_/i', 'pequeno_', $file);
							$grande = preg_replace('/mini_/i', '', $file);
							
							if(!$primeiraImagem){
								$imagem_path = '<img src="'.$caminho_internet.$pequena.'" data-zoom-image="'.$caminho_internet.$grande.'"/>';
							}
							
							break;
						}
					}

					closedir($abreDir);
				}
				
				$layout = modelo_var_troca($layout,"#img#",$imagem_path);
				$layout = modelo_var_troca($layout,"#tit#",$produto['t1.nome']);
				$layout = modelo_var_troca_tudo($layout,"#titulo#",($limite_texto?limitar_texto_html($produto['t1.nome'],$limite_texto, $tags_permitidas = ''):$produto['t1.nome']));
				$layout = modelo_var_troca_tudo($layout,"#descricao#",($limite_texto?limitar_texto_html($produto['t1.descricao'],$limite_texto, $tags_permitidas = ''):$produto['t1.descricao']));
				$layout = modelo_var_troca($layout,"#url#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$produto['t2.caminho_raiz'].$produto['t2.identificador']);
				
				$pagina = $layout;
			}
		}
		
		return $pagina;
	} else {
		$colunas = 3;
		$cel_nome = 'loja-online';
		
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		$resultado = banco_select_name
		(
			banco_campos_virgulas(Array(
				't1.id_servicos',
				't1.nome',
				't1.imagem_path',
				't1.preco',
				't1.desconto',
				't1.desconto_de',
				't1.desconto_ate',
				't1.quantidade',
				't2.caminho_raiz',
				't2.identificador',
			))
			,
			"servicos as t1,conteudo as t2,conteudo_permissao as t3",
			"WHERE t2.servico=t1.id_servicos"
			." AND t2.id_conteudo='".$id."'"
			." AND t3.servico IS NOT NULL"
			." AND t1.status='A'"
			." AND IF(visivel_de IS NOT NULL, visivel_de <= NOW(), 1)"
			." AND IF(visivel_ate IS NOT NULL, visivel_ate >= NOW(), 1)"
			." GROUP BY t2.identificador"
			." ORDER BY t1.nome ASC"
			." LIMIT 1"
		);
		
		if($resultado){
			$servico = $resultado[0];
			$time = time();
			$desconto_de_ate = false;
			
			if((int)$servico['t1.quantidade'] > 0){
				if($servico['t1.desconto']){
					$desconto_de_ate = true;
				}
				
				if($servico['t1.desconto_de']){
					$desconto_de_ate = true;
					$de = strtotime($servico['t1.desconto_de']);
					
					if($time < $de){
						$desconto_de_ate = false;
					}
				}
				
				if($servico['t1.desconto_ate']){
					$desconto_de_ate = true;
					$ate = strtotime($servico['t1.desconto_ate']);
					
					if($time > $ate){
						$desconto_de_ate = false;
					}
				}
				
				$layout = false;
				if($_PROJETO['ecommerce']){
					if($_PROJETO['ecommerce']['newsletter_loja_online_layout']){
						$layout = $_PROJETO['ecommerce']['newsletter_loja_online_layout'];
					}
				}
				
				if(!$layout){
					$layout = '
					
					<a href="#url#" class="_loja-online-cont">
						<div class="_loja-online-img">#img#</div>
						<div class="_loja-online-titulo">#titulo#</div>
						<div class="_loja-online-preco">#preco#</div>
						<div class="clear"></div>
					</a>';
				}
				
				if($desconto_de_ate){
					$valor_desconto_float = (($servico['t1.preco'] * (100 - $servico['t1.desconto'])) / 100);
					$parcela_valor = ecommerce_parcela_valor_pagseguro($valor_desconto_float,10);
					
					if($parcela_valor)
					foreach($parcela_valor as $parcela => $valor_parcela){
						break;
					}
					
					$valor_total_parcelas = number_format(($valor_parcela*$parcela), 2, ",", ".");
					$valor_parcela = number_format($valor_parcela, 2, ",", ".");
					$valor_total = number_format($servico['t1.preco'], 2, ",", ".");
					$valor_desconto = number_format($valor_desconto_float, 2, ",", ".");
					
					$preco = 'De <span class="_loja-online-valtxt4">R$ '.$valor_total.'</span><br>Por <span class="_loja-online-valtxt3">R$ '.$valor_desconto.'</span>'.($parcela > 1 ? '<br>em <span class="_loja-online-valtxt2">'.$parcela.'x</span> de <span class="_loja-online-valtxt2">R$ '.$valor_parcela.'</span>':'');
				} else {
					$parcela_valor = ecommerce_parcela_valor_pagseguro($servico['t1.preco'],10);
					
					if($parcela_valor)
					foreach($parcela_valor as $parcela => $valor_parcela){
						break;
					}
					
					$valor_total_parcelas = number_format(($valor_parcela*$parcela), 2, ",", ".");
					$valor_parcela = number_format($valor_parcela, 2, ",", ".");
					$valor_total = number_format($servico['t1.preco'], 2, ",", ".");
					
					$preco = '<span class="_loja-online-valtxt">R$ '.$valor_total.'</span>'.($parcela > 1 ? '<br>em <span class="_loja-online-valtxt2">'.$parcela.'x</span> de <span class="_loja-online-valtxt2">R$ '.$valor_parcela.'</span>':'');
				}
				
				$layout = modelo_var_troca($layout,"#img#",'<img src="'.path_com_versao_arquivo($servico['t1.imagem_path']).' alt="'.$servico['t1.nome'].'">');
				$layout = modelo_var_troca($layout,"#tit#",$servico['t1.nome']);
				$layout = modelo_var_troca_tudo($layout,"#titulo#",$servico['t1.nome']);
				$layout = modelo_var_troca($layout,"#preco#",$preco);
				$layout = modelo_var_troca($layout,"#url#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$servico['t2.caminho_raiz'].$servico['t2.identificador']);
				
				$pagina = $layout;
			} else {
				$layout = false;
				if($_PROJETO['ecommerce']){
					if($_PROJETO['ecommerce']['newsletter_loja_online_indisponivel_layout']){
						$layout = $_PROJETO['ecommerce']['newsletter_loja_online_indisponivel_layout'];
					}
				}
				
				if(!$layout){
					$layout = '
					
					<a href="#url#" class="_loja-online-cont">
						<div class="_loja-online-img">#img#</div>
						<div class="_loja-online-titulo">#titulo#</div>
						<div class="_loja-online-descricao">#descricao#</div>
						<div class="_loja-online-indisponivel">Serviço Indispon&iacute;vel</div>
						<div class="clear"></div>
					</a>';
				}
				
				$caminho_fisico = $_SYSTEM['PATH']."files".$_SYSTEM['SEPARADOR']."produtos".$_SYSTEM['SEPARADOR']."produto".$produto['t1.id_produtos'].$_SYSTEM['SEPARADOR'];
				$caminho_internet = 'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT']."files/produtos/produto".$produto['t1.id_produtos']."/";
				
				if(is_dir($caminho_fisico)){
					$abreDir = opendir($caminho_fisico);

					while (false !== ($file = readdir($abreDir))) {
						if ($file==".." || $file ==".") continue;
						
						if(preg_match('/produto_mini_/i', $file) > 0){
							$idExt = preg_replace('/produto_mini_/i', '', $file);
							$idExtArr = explode('.',$idExt);
							
							$mini = $file;
							$pequena = preg_replace('/mini_/i', 'pequeno_', $file);
							$grande = preg_replace('/mini_/i', '', $file);
							
							if(!$primeiraImagem){
								$imagem_path = '<img src="'.$caminho_internet.$pequena.'" data-zoom-image="'.$caminho_internet.$grande.'"/>';
							}
							
							break;
						}
					}

					closedir($abreDir);
				}
				
				$layout = modelo_var_troca($layout,"#img#",$imagem_path);
				$layout = modelo_var_troca($layout,"#tit#",$servico['t1.nome']);
				$layout = modelo_var_troca_tudo($layout,"#titulo#",($limite_texto?limitar_texto_html($servico['t1.nome'],$limite_texto, $tags_permitidas = ''):$servico['t1.nome']));
				$layout = modelo_var_troca_tudo($layout,"#descricao#",($limite_texto?limitar_texto_html($servico['t1.descricao'],$limite_texto, $tags_permitidas = ''):$servico['t1.descricao']));
				$layout = modelo_var_troca($layout,"#url#",'http://'.$_SYSTEM['DOMINIO'].'/'.$_SYSTEM['ROOT'].$servico['t2.caminho_raiz'].$servico['t2.identificador']);
				
				$pagina = $layout;
			}
		}
		
		return $pagina;
	}
}

function ecommerce_indisponivel(){
	global $_PROJETO;
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}
	
	seguranca_delay();
	
	$nome = $_REQUEST['indisponivel-nome'];
	$email = $_REQUEST['indisponivel-email'];
	$id = $_REQUEST['indisponivel-id'];
	
	if(
		$nome &&
		$email &&
		$id
	){
		$campos = null;
	
		if($loja_online_produtos){
			$campo_nome = "id_produtos"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		} else {
			$campo_nome = "id_servicos"; $campo_valor = $id; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		}
		
		$campo_nome = "nome"; $campo_valor = $nome; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		$campo_nome = "email"; $campo_valor = $email; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		$campo_nome = "data"; $campo_valor = 'NOW()'; 		$campos[] = Array($campo_nome,$campo_valor,true);
		$campo_nome = "status"; $campo_valor = 'A'; 		$campos[] = Array($campo_nome,$campo_valor,$campo_sem_aspas_simples);
		
		banco_insert_name
		(
			$campos,
			"produto_servico_indisponivel"
		);
	}
	
	alerta('<h3 style="color:#FFF;">Cadastro efetivado com sucesso!</h3><p>Quando seu produto e/ou serviço estiver disponível entraremos em contato.</p><p>Obrigado</p>');
	redirecionar('loja-online');
}

// E-Service

function eservice_checkout(){
	global $_LAYOUT_NUM;
	
	$_LAYOUT_NUM = '2';
	
	echo 's';
}

// Funções Locais

function ecommerce_ajax(){
	global $_OPCAO;
	global $_SYSTEM;
	global $_CONEXAO_BANCO;
	global $_VARS;
	global $_PROJETO;
	
	if($_PROJETO['ecommerce'])
	if($_PROJETO['ecommerce']['produtos']){
		$loja_online_produtos = true;
	}
	
	if($_OPCAO == 'ecommerce-cupom'){
		seguranca_delay();
		
		$codigo = $_REQUEST['cupom'];
		
		$resultado = banco_select_name
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
			"WHERE codigo='".$codigo."'"
			." AND status='A'"
		);
		
		if($resultado){
			$resultado2 = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_cupom_desconto',
				))
				,
				"cupom_desconto_pedidos",
				"WHERE id_cupom_desconto='".$resultado[0]['id_cupom_desconto']."'"
			);
			
			$cupom_de_ate = true;
			
			if($resultado[0]['data_inicio']){
				$cupom_de_ate = true;
				$data_inicio = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_cupom_desconto',
					))
					,
					"cupom_desconto",
					"WHERE id_cupom_desconto='".$resultado[0]['id_cupom_desconto']."'"
					." AND data_inicio <= NOW()"
				);
				
				if(!$data_inicio){
					$cupom_de_ate = false;
				}
			}
			
			if($resultado[0]['data_fim']){
				$cupom_de_ate = true;
				$data_fim = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_cupom_desconto',
					))
					,
					"cupom_desconto",
					"WHERE id_cupom_desconto='".$resultado[0]['id_cupom_desconto']."'"
					." AND data_fim >= NOW()"
				);
				
				if(!$data_fim){
					$cupom_de_ate = false;
				}
			}
			
			if($cupom_de_ate){
				if($resultado[0]['max_usos'] > count($resultado2)){
					$saida = Array(
						'ok' => true,
						'desconto' => $resultado[0]['desconto'],
					);
				} else {
					$saida = Array(
						'erro' => '<p>Não é possível mais usar esse cupom de desconto pois o mesmo já excedeu a quantidade máxima de usos.</p>',
					);
				}
			} else {
				$saida = Array(
					'erro' => '<p>Não é possível mais usar esse cupom de desconto pois o mesmo está fora do período de validade.</p>',
				);
			}
		} else {
			$saida = Array(
				'erro' => '<p>Cupom inválido!</p>',
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_OPCAO == 'voucher-presente'){
		ecommerce_permissao_acesso();
		
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$usuario_pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
			))
			,
			"usuario_pedidos",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND pedido_atual IS NOT NULL"
		);
		
		if($usuario_pedidos){
			$id_pedidos = $usuario_pedidos[0]['id_pedidos'];
			
			if($_REQUEST['flag'] == '1'){
				banco_update
				(
					"presente='1'",
					"pedidos",
					"WHERE id_pedidos='".$id_pedidos."'"
				);
			} else {
				banco_update
				(
					"presente=NULL",
					"pedidos",
					"WHERE id_pedidos='".$id_pedidos."'"
				);
			}
			
			log_banco(Array(
				'id_referencia' => $id_pedidos,
				'grupo' => 'pedidos',
				'valor' => '<b>Usuário:</b> mudou status de presente para '.($_REQUEST['flag'] == '1' ? 'Sim' : 'Não'),
			));
		}
	}
	
	if($_OPCAO == 'voucher-temas'){
		ecommerce_permissao_acesso();
		
		if(!$_CONEXAO_BANCO)$connect_db = true;
		if($connect_db)banco_conectar();
		
		$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
		
		$usuario_pedidos = banco_select_name
		(
			banco_campos_virgulas(Array(
				'id_pedidos',
			))
			,
			"usuario_pedidos",
			"WHERE id_usuario='".$usuario['id_usuario']."'"
			." AND id_pedidos='".$_REQUEST['id_pedidos']."'"
		);
		
		if($usuario_pedidos){
			if($_REQUEST['id']){
				banco_update
				(
					"id_voucher_layouts='".$_REQUEST['id']."'",
					"pedidos",
					"WHERE id_pedidos='".$_REQUEST['id_pedidos']."'"
				);
			}
			
			log_banco(Array(
				'id_referencia' => $_REQUEST['id_pedidos'],
				'grupo' => 'pedidos',
				'valor' => '<b>Usuário:</b> mudou tema para id_voucher_layouts='.$_REQUEST['id'],
			));
		}
	}
	
	if($_OPCAO == 'voucher-pedidos'){
		ecommerce_permissao_acesso();
		
		if($_REQUEST['id']){
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			
			$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
			
			if($_REQUEST['voucher']){
				$usuario_pedidos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_pedidos',
					))
					,
					"usuario_pedidos",
					"WHERE id_usuario='".$usuario['id_usuario']."'"
					." AND id_pedidos='".$_REQUEST['id']."'"
				);
				
				if($usuario_pedidos){
					banco_update
					(
						"voucher_por_servico=" . ($_REQUEST['voucher_opcao'] == '1' ? "'1'" : 'NULL'),
						"pedidos",
						"WHERE id_pedidos='".$_REQUEST['id']."'"
					);
				}
			} else if($_REQUEST['servico']){
				$usuario_pedidos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'id_pedidos',
					))
					,
					"usuario_pedidos",
					"WHERE id_usuario='".$usuario['id_usuario']."'"
					." AND id_pedidos='".$_REQUEST['id']."'"
				);
				
				if($usuario_pedidos){
					banco_update
					(
						"voucher_por_servico=NULL",
						"pedidos_servicos",
						"WHERE id_pedidos='".$_REQUEST['id']."'"
					);
					banco_update
					(
						"voucher_por_servico='1'",
						"pedidos_servicos",
						"WHERE id_pedidos='".$_REQUEST['id']."'"
						." AND codigo='".$_REQUEST['servico_opcao']."'"
					);
				}
			} else {
				banco_update
				(
					"pedido_atual=NULL",
					"usuario_pedidos",
					"WHERE id_usuario='".$usuario['id_usuario']."'"
				);
				banco_update
				(
					"pedido_atual='1'",
					"usuario_pedidos",
					"WHERE id_usuario='".$usuario['id_usuario']."'"
					." AND id_pedidos='".$_REQUEST['id']."'"
				);
			}
		}
	}
	
	if($_OPCAO == 'pagar-pedidos'){
		ecommerce_permissao_acesso();
		
		if($_REQUEST['id']){
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			
			$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
			
			banco_update
			(
				"pedido_atual=NULL",
				"usuario_pedidos",
				"WHERE id_usuario='".$usuario['id_usuario']."'"
			);
			banco_update
			(
				"pedido_atual='1'",
				"usuario_pedidos",
				"WHERE id_usuario='".$usuario['id_usuario']."'"
				." AND id_pedidos='".$_REQUEST['id']."'"
			);
			
			$usuario_pedidos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_pedidos',
				))
				,
				"usuario_pedidos",
				"WHERE id_usuario='".$usuario['id_usuario']."'"
				." AND id_pedidos='".$_REQUEST['id']."'"
			);
			
			if($usuario_pedidos){
				if($loja_online_produtos){
					$pedidos_produtos_banco = banco_select_name
					(
						banco_campos_virgulas(Array(
							't1.sub_total',
							't2.nome',
							't2.id_produtos',
						))
						,
						"pedidos_produtos as t1,produtos as t2",
						"WHERE t1.id_pedidos='".$_REQUEST['id']."'"
						." AND t1.id_produtos=t2.id_produtos"
					);
					
					if($pedidos_produtos_banco)
					foreach($pedidos_produtos_banco as $pp){
						$flag = false;
						$flag2 = false;
						if($pedidos_produtos){
							foreach($pedidos_produtos as $key => $pp2){
								if($pp2['id'] == $pp['t2.id_produtos']){
									$pedidos_produtos[$key]['quant']++;
									$flag2 = true;
									break;
								}
							}
							
							if(!$flag2){
								$flag = true;
							}
						} else {
							$flag = true;
						}
						
						if($flag){
							$pedidos_produtos[] = Array(
								'id' => $pp['t2.id_produtos'],
								'preco' => $pp['t1.sub_total'],
								'quant' => 1,
								'titulo' => $pp['t2.nome'],
								'href' => '',
							);
						}
					}
					
					$saida = json_encode($pedidos_produtos);
				} else {
					$pedidos_servicos_banco = banco_select_name
					(
						banco_campos_virgulas(Array(
							't1.sub_total',
							't2.nome',
							't2.id_servicos',
						))
						,
						"pedidos_servicos as t1,servicos as t2",
						"WHERE t1.id_pedidos='".$_REQUEST['id']."'"
						." AND t1.id_servicos=t2.id_servicos"
					);
					
					if($pedidos_servicos_banco)
					foreach($pedidos_servicos_banco as $ps){
						$flag = false;
						$flag2 = false;
						if($pedidos_servicos){
							foreach($pedidos_servicos as $key => $ps2){
								if($ps2['id'] == $ps['t2.id_servicos']){
									$pedidos_servicos[$key]['quant']++;
									$flag2 = true;
									break;
								}
							}
							
							if(!$flag2){
								$flag = true;
							}
						} else {
							$flag = true;
						}
						
						if($flag){
							$pedidos_servicos[] = Array(
								'id' => $ps['t2.id_servicos'],
								'preco' => $ps['t1.sub_total'],
								'quant' => 1,
								'titulo' => $ps['t2.nome'],
								'href' => '',
							);
						}
					}
					
					$saida = json_encode($pedidos_servicos);
				}
			}
		}
	}
	
	if($_OPCAO == 'voucher-enviar-email'){
		ecommerce_permissao_acesso();
		
		$voucher = $_REQUEST['voucher'];
		$email = $_REQUEST['email'];
		$id_pedidos = $_REQUEST['id_pedidos'];
		$flag = false;
		
		if($voucher && $email){
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			
			$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
			
			$usuario_pedidos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_pedidos',
				))
				,
				"usuario_pedidos",
				"WHERE id_pedidos='".$id_pedidos."'"
				." AND id_usuario='".$usuario['id_usuario']."'"
			);
			
			if($usuario_pedidos){
				$pedidos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'envios_email',
						'codigo',
					))
					,
					"pedidos",
					"WHERE id_pedidos='".$id_pedidos."'"
					." AND status='A'"
				);
				
				if($pedidos){
					if($pedidos[0]['envios_email']){
						if($pedidos[0]['envios_email'] > 10){
							$flag = true;
						}
					} else {
						banco_update
						(
							"envios_email=0",
							"pedidos",
							"WHERE id_pedidos='".$_REQUEST['id']."'"
						);
					}
					
					if(!$flag){
						banco_update
						(
							"envios_email=envios_email+1",
							"pedidos",
							"WHERE id_pedidos='".$_REQUEST['id']."'"
						);
						
						$assunto = $_VARS['ecommerce']['voucher_email_assunto'];
						$mensagem = $_VARS['ecommerce']['voucher_email_mensagem'];
						
						$voucher = $voucher;
						$voucher = preg_replace('/\\\"/i', '"', $voucher);
						$voucher = preg_replace("/\\\'/i", "'", $voucher);
						
						$voucher = ecommerce_remover_css($voucher);
						
						$assunto = modelo_var_troca($assunto,"#codigo#",$pedidos[0]['codigo']);
						$mensagem .= $voucher;
						
						email_enviar(Array(
							'email_nome' => $email_nome,
							'email' => $email,
							'assunto' => $assunto,
							'mensagem' => $mensagem,
						));
						
						$saida = Array(
							'ok' => true,
						);
						
						log_banco(Array(
							'id_referencia' => $id_pedidos,
							'grupo' => 'pedidos',
							'valor' => '<b>Usuário:</b> enviou voucher para o seguinte email -> '.$email,
						));
					} else {
						$saida = Array(
							'erro' => '<p>Você já enviou mais de 10 emails para esse pedido</p><p>Não é permitido enviar mais emails para esse pedido.</p>',
						);
					}
				} else {
					$saida = Array(
						'erro' => '<p>Não é permitido enviar emails de pedidos inativos no sistema.</p>',
					);
				}
			} else {
				$saida = Array(
					'erro' => '<p>Não é possível enviar esse email!</p><p>Esse pedido não pertence ao seu usuário.</p>',
				);
			}
		} else {
			$saida = Array(
				'erro' => '<p>Não é possível enviar esse email!</p><p>O email e/ou o voucher não está definido.</p>',
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_OPCAO == 'voucher-concluir'){
		ecommerce_permissao_acesso();
		
		$voucher = $_REQUEST['voucher'];
		$id_pedidos = $_REQUEST['id_pedidos'];
		$flag = false;
		
		if($voucher){
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			
			$usuario = $_SESSION[$_SYSTEM['ID']."usuario"];
			
			$usuario_pedidos = banco_select_name
			(
				banco_campos_virgulas(Array(
					'id_pedidos',
				))
				,
				"usuario_pedidos",
				"WHERE id_pedidos='".$id_pedidos."'"
				." AND id_usuario='".$usuario['id_usuario']."'"
			);
			
			if($usuario_pedidos){
				$email = $usuario['email'];
				$email_nome = $usuario['nome'];
				
				$pedidos = banco_select_name
				(
					banco_campos_virgulas(Array(
						'envios_email',
						'codigo',
					))
					,
					"pedidos",
					"WHERE id_pedidos='".$id_pedidos."'"
					." AND status='A'"
				);
				
				if($pedidos){
					if($pedidos[0]['envios_email']){
						if($pedidos[0]['envios_email'] > 10){
							$flag = true;
						}
					} else {
						banco_update
						(
							"envios_email=0",
							"pedidos",
							"WHERE id_pedidos='".$_REQUEST['id']."'"
						);
					}
					
					if(!$flag){
						banco_update
						(
							"envios_email=envios_email+1",
							"pedidos",
							"WHERE id_pedidos='".$_REQUEST['id']."'"
						);
						
						$assunto = $_VARS['ecommerce']['concluir_email_assunto'];
						$mensagem = $_VARS['ecommerce']['concluir_email_mensagem'];
						
						$voucher = $voucher;
						$voucher = preg_replace('/\\\"/i', '"', $voucher);
						$voucher = preg_replace("/\\\'/i", "'", $voucher);
						
						$voucher = ecommerce_remover_css($voucher);
						
						$assunto = modelo_var_troca($assunto,"#codigo#",$pedidos[0]['codigo']);
						$mensagem .= $voucher;
						
						email_enviar(Array(
							'email_nome' => $email_nome,
							'email' => $email,
							'assunto' => $assunto,
							'mensagem' => $mensagem,
						));
						
						$saida = Array(
							'ok' => true,
						);
						
						log_banco(Array(
							'id_referencia' => $id_pedidos,
							'grupo' => 'pedidos',
							'valor' => '<b>Usuário:</b> clicou em concluir e enviou email -> '.$email,
						));
					} else {
						$saida = Array(
							'erro' => '<p>Você já enviou mais de 10 emails para esse pedido</p><p>Não é permitido enviar mais emails para esse pedido.</p>',
						);
					}
				} else {
					$saida = Array(
						'erro' => '<p>Não é permitido enviar emails de pedidos inativos no sistema.</p>',
					);
				}
			} else {
				$saida = Array(
					'erro' => '<p>Não é possível enviar esse email!</p><p>Esse pedido não pertence ao seu usuário.</p>',
				);
			}
		} else {
			$saida = Array(
				'erro' => '<p>Não é possível enviar esse email!</p><p>O voucher não está definido.</p>',
			);
		}
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['opcao'] == 'loja-online'){
		$saida = ecommerce_loja_online(Array(
			'ajax' => true,
		));
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['opcao'] == 'meus-pedidos'){
		$saida = ecommerce_meus_pedidos(Array(
			'ajax' => true,
		));
		
		$saida = json_encode($saida);
	}
	
	if($_REQUEST['opcao'] == 'calcular-frete'){
		$cep = $_REQUEST['cep'];
		$produtos = $_REQUEST['produtos'];
		$quantidades = $_REQUEST['quantidades'];
		
		if($cep && $produtos){
			$produtos = explode(',',$produtos);
			$quantidades = explode(',',$quantidades);
			
			if(!$_CONEXAO_BANCO)$connect_db = true;
			if($connect_db)banco_conectar();
			
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
						switch($row->Codigo){
							case '40010':
								$fretes[] = Array(
									'tipo' => 'SEDEX',
									'valor' => (string)$row->Valor,
									'prazo' => (string)$row->PrazoEntrega,
									'codigo' => '1',
								);
							break;
							case '41106':
								$fretes[] = Array(
									'tipo' => 'PAC',
									'valor' => (string)$row->Valor,
									'prazo' => (string)$row->PrazoEntrega,
									'codigo' => '2',
								);
							break;
							
						}
					} else {
						return 'Erro: ' . $row -> Erro . ' | MsgErro: ' . $row -> MsgErro;
					}
				}
				
				if($_PROJETO['ecommerce'])
				if($_PROJETO['ecommerce']['frete-extra']){
					$frete_extra = $_PROJETO['ecommerce']['frete-extra'];
					
					foreach($frete_extra as $frete_val){
						$fretes[] = Array(
							'tipo' => $frete_val['tipo'],
							'valor' => $frete_val['valor'],
							'prazo' => $frete_val['prazo'],
							'codigo' => $frete_val['codigo'],
						);
					}
				}
				
				$saida = Array(
					'fretes' => $fretes,
				);
			} else {
				$saida =  'Erro CURL ou SIMPLEXML';
			}
			
			$saida = json_encode($saida);
		}
	}
	
	return $saida;
}

function ecommerce_xml(){

}

function ecommerce_main(){
	global $_LOCAL_ID;
	global $_SYSTEM;
	global $_HTML;
	global $_OPCAO_ANTERIOR;
	global $_ID_ANTERIOR;
	global $_OPCAO;
	global $_CAMINHO;
	global $_AJAX_PAGE;
	global $_DESATIVAR_PADRAO;
	global $_VARIAVEIS_JS;
	global $_PROJETO;
	
	if($_REQUEST[xml])				$xml = $_REQUEST[xml];
	if($_REQUEST[ajax])				$ajax = $_REQUEST[ajax];
	$opcao = $_OPCAO;
	
	if($_SESSION[$_SYSTEM['ID'].'ecommerce-limpar-carrinho']){
		$carrinho_id = $_SESSION[$_SYSTEM['ID'].'ecommerce-limpar-carrinho'];
		$_VARIAVEIS_JS['ecommerce_limpar_carrinho'] = true;
		$_VARIAVEIS_JS['ecommerce_id_pedido'] = ($carrinho_id[0] == "E" ? $carrinho_id : '0');
		$_VARIAVEIS_JS['ecommerce_vendedor_nome'] = $_HTML['TITULO'];
		$_VARIAVEIS_JS['ecommerce_pedido_itens'] = $_SESSION[$_SYSTEM['ID'].'ecommerce_pedido_itens'];
		$_SESSION[$_SYSTEM['ID'].'ecommerce-limpar-carrinho'] = false;
		$_SESSION[$_SYSTEM['ID'].'ecommerce_pedido_itens'] = false;
	}
	
	if(!$xml){
		if(!$ajax){
			switch($opcao){
				case 'pagseguro-notificacoes':	$saida = ecommerce_pagseguro_notificacoes(); break;
				case 'pagseguro-pagar':	$saida = ecommerce_pagseguro_pagar(); break;
				case 'pagseguro-retorno':	$saida = ecommerce_pagseguro_retorno(); break;
				case 'paypal-notificacoes':	$saida = ecommerce_paypal_notificacoes(); break;
				case 'paypal-pagar':	$saida = ecommerce_paypal_pagar(); break;
				case 'paypal-retorno':	$saida = ecommerce_paypal_retorno(); break;
				case 'paypal-cancelado':	$saida = ecommerce_paypal_cancelado(); break;
				case 'paypal-returnurl':	$saida = ecommerce_paypal_pagar2(); break;
				case 'paypal-cancelurl':	$saida = ecommerce_paypal_cancelado(); break;
				case 'voucher':	$saida = ecommerce_voucher(); break;
				case 'voucher-form-presente':	$saida = ecommerce_voucher_form_presente(); break;
				case 'endereco-entrega':	$saida = ecommerce_endereco_entrega(); break;
				case 'endereco-entrega-salvar':	$saida = ecommerce_endereco_entrega_salvar(); break;
				case 'pagamento':	$saida = ecommerce_pagamento(); break;
				case 'carrinho':	$saida = ecommerce_carrinho(); break;
				case 'loja-online':	$saida = ecommerce_loja_online(); break;
				case 'meus-pedidos':	$saida = ecommerce_meus_pedidos(); break;
				case 'indique':	$saida = ecommerce_indique(); break;
				case 'indique-enviar':	$saida = ecommerce_indique_enviar(); break;
				case 'duvidas':	$saida = ecommerce_duvidas(); break;
				case 'duvidas-enviar':	$saida = ecommerce_duvidas_enviar(); break;
				case 'ecommerce-indisponivel':	$saida = ecommerce_indisponivel(); break;
				case 'checkout':	$saida = eservice_checkout(); break;
			}
			
			return $saida;
		} else {
			return ecommerce_ajax();
		}
	} else {
		return ecommerce_xml();
	}
}

if(!$_ECOMMERCE['apenas_incluir'])return ecommerce_main();

?>