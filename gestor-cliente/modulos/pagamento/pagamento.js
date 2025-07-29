$(document).ready(function(){
	
	function start(){
		if('pedidoGratuito' in gestor.pagamento){
			function pedido_gratuito_processar(p = {}){
				var opcao = 'pagamento';
				var ajaxOpcao = 'pedido-gratuito-processar';
				
				$.ajax({
					type: 'POST',
					url: gestor.raiz + 'pagamento/',
					data: {
						opcao : opcao,
						ajax : 'sim',
						ajaxOpcao : ajaxOpcao,
						codigo : gestor.pagamento.codigo,
					},
					dataType: 'json',
					beforeSend: function(){
						
					},
					success: function(dados){
						switch(dados.status){
							case 'OK':
								window.open('/voucher/?pedido='+gestor.pagamento.codigo, '_self');
							break;
							case 'API_ERROR':
								alerta({mensagem:dados.msg});
							break;
							case 'STATUS_INVALID':
								window.open('/meus-pedidos/', '_self');
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					},
					error: function(txt){
						switch(txt.status){
							case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "identificacao/"),"_self"); break;
							default:
								console.log('ERROR AJAX - '+opcao+' - Dados:');
								console.log(txt);
						}
					}
				});
			}
			
			pedido_gratuito_processar();
		} else {
			// ===== Criar primeira requisição de pagamento.
			
			gestor.ppplus = {};
			
			if('app_installed' in gestor.pagamento && 'app_active' in gestor.pagamento){
				if('paypal_plus_inactive' in gestor.pagamento){
					$('.paypalComp').show();
					$('.paypalBtnCont').show();
					
					paypal_botao_carregar();
				} else {
					gestor.ppplus.alvo = 'proprio';
					
					ppplus_criar_pagamento();
				}
			}
			
			// ===== Botões de opção de pagamento.
			
			$('.ui.menu .item').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				// ===== Somente aplicar a ação a itens não ativos.
				
				if(!$(this).hasClass('active')){
					carregando('abrir');
					
					// ===== Marcar ativo apenas ao clicado.
					
					$(this).parent().find('.item').removeClass('active');
					$(this).addClass('active');
					
					// ===== Tratar cada item selecionado.
					
					switch($(this).attr('data-id')){
						case 'proprio':
							paypal_pagamento();
						break;
						case 'terceiro':
							paypal_outro_pagador();
						break;
						case 'paypal':
							paypal_botao_pagamento();
						break;
						default:
							carregando('fechar');
					}
				}
			});
			
			function paypal_pagamento(){
				$('.pagComp').hide();
				$('.proprioComp').show();
				$('#proprioPPPIframe iframe').css('width','100%');
				$('.proprioBtnCont').show();
				
				gestor.ppplus.alvo = 'proprio';
				
				carregando('fechar');
			}
			
			function paypal_outro_pagador(){
				$('.pagComp').hide();
				$('.terceiroComp').show();
				$('#terceiroPPPIframe iframe').css('width','100%');
				$('.terceiroBtnCont').show();
				
				gestor.ppplus.alvo = 'terceiro';
				
				// ===== Form formOutroPagador.
				
				if(!('formularioCriado' in gestor)){
					gestor.formularioCriado = true;
					
					var formId = 'formOutroPagador';
					var formSelector = '#formOutroPagador';
					
					$(formSelector)
						.form({
							fields : (gestor.formulario[formId].regrasValidacao ? gestor.formulario[formId].regrasValidacao : {}),
							onSuccess(event, fields){
								fields.nome = primeiraLetraMaiuscula(fields.nome);
								
								$('.terceiroForm').hide();
								$('.terceiroPPPCont').show();
								$('.terceiroNome').html(fields.nome);
								
								$('.outroPagadorTrocar').on('mouseup tap',function(e){
									if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
									
									$(formSelector).form('reset');
									$('.terceiroForm').show();
									$('.terceiroPPPCont').hide();
									
									delete gestor.ppplus.outroPagadorStarted;
									
									gestor.ppplus.outroPagadorCaregandoHack = true;
								});
								
								ppplus_criar_pagamento({outroPagador : JSON.stringify(fields)});
								
								return false;
							}
						});
						
						
					// ===== CPF e CNPJ controles.
					
					$(formSelector).form('remove fields', ['cnpj']);
					
					$('.cpf').mask('000.000.000-00', {clearIfNotMatch: true});
					$('.cnpj').mask('00.000.000/0000-00', {clearIfNotMatch: true});
					
					$('.controleDoc').on('mouseup tap',function(e){
						if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
						
						var id = $(this).attr('data-id');
						
						switch(id){
							case 'cpf':
								$('.cpf').parent().show();
								$('.cnpj').parent().hide();
								$('.controleDoc[data-id="cpf"]').addClass('active');
								$('.controleDoc[data-id="cnpj"]').removeClass('active');
								$('input[name="cnpj_ativo"]').val('nao');
								$(formSelector).form('remove fields', ['cnpj']);
								$(formSelector).form('add rule', 'cpf',{ rules : gestor.formulario[formId].regrasValidacao['cpf'].rules });
							break;
							case 'cnpj':
								$('.cpf').parent().hide();
								$('.cnpj').parent().show();
								$('.controleDoc[data-id="cpf"]').removeClass('active');
								$('.controleDoc[data-id="cnpj"]').addClass('active');
								$('input[name="cnpj_ativo"]').val('sim');
								$(formSelector).form('remove fields', ['cpf']);
								$(formSelector).form('add rule', 'cnpj',{ rules : gestor.formulario[formId].regrasValidacao['cnpj'].rules });
							break;
						}
					});
					
					// ===== Telefone controle.
					
					var SPMaskBehavior = function (val) {
						return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
					},
					spOptions = {
						onKeyPress: function(val, e, field, options) {
							field.mask(SPMaskBehavior.apply({}, arguments), options);
						},
						clearIfNotMatch: true
					};

					$('.telefone').mask(SPMaskBehavior, spOptions);
				} else {
					
				}
				
				carregando('fechar');
			}
			
			function paypal_botao_pagamento(){
				$('.pagComp').hide();
				$('.paypalComp').show();
				$('.paypalBtnCont').show();
				
				gestor.ppplus.alvo = 'botao';
				
				paypal_botao_carregar();
			}
			
			// ===== Reiniciar processo de pagamento.
			
			function paypal_reiniciar(){
				switch(gestor.ppplus.alvo){
					case 'proprio': paypal_pagamento_reiniciar(); break;
					case 'terceiro': paypal_outro_pagador_reiniciar(); break;
					case 'botao': paypal_botao_reiniciar(); break;
				}
			}
			
			function paypal_pagamento_reiniciar(){
				delete gestor.ppplus.pagadorStarted;
				
				ppplus_criar_pagamento();
			}
			
			function paypal_outro_pagador_reiniciar(){
				$('.terceiroForm').show();
				$('.terceiroPPPCont').hide();
				
				delete gestor.ppplus.outroPagadorStarted;
			}
			
			function paypal_botao_reiniciar(){
				delete gestor.ppplus.paypalStarted;
				
				paypal_botao_carregar();
			}
			
			// ===== Funções do Paypal.
			
			function paypal_botao_carregar(p = {}){
				if(!('paypalLoaded' in gestor)){
					$.getScript('https://www.paypalobjects.com/api/checkout.js',function() {
						gestor.paypalLoaded = true;
						paypal_botao_iniciar(p);
					});
				} else {
					paypal_botao_iniciar(p);
				}
			}
			
			function paypal_botao_iniciar(p = {}){
				if(!('paypalStarted' in gestor.ppplus)){
					gestor.ppplus.paypalStarted = true;
					gestor.ppplus.alvo = 'botao';
					
					// ===== Remover o que tem dentro do conteiner do botão.
					
					$('#paypalBtnFake').hide();
					$('#paypalBtn').html('');
				
					// ===== Criar botão de pagamento do PayPal.
					
					var CREATE_PAYMENT_URL = '/pagamento/?ajax=sim&ajaxOpcao=ppplus-criar-pagamento&botao=sim&codigo='+gestor.pagamento.codigo;
					
					paypal.Button.render({
						env: ('app_live' in gestor.pagamento ? "production" : "sandbox"), // Or 'sandbox',

						commit: true, // Show a 'Pay Now' button
						locale: 'pt_BR',
						
						style: {
							label: 'pay',
							fundingicons: true,
							shape: 'rect',
							color: 'blue',
							size:'responsive'
						},

						payment: function(data, actions) {
							/*
							* Set up the payment here
							*/
							
							return paypal.request.post(CREATE_PAYMENT_URL).then(function(data) {
								switch(data.status){
									case 'OK':
										console.log(data);
										if(!('pay_id' in gestor.ppplus)){
											gestor.ppplus.pay_id = new Array();
										}
										
										gestor.ppplus.pay_id[gestor.ppplus.alvo] = data.ppplus.pay_id;
										
										return data.ppplus.pay_id;
									break;
									case 'API_ERROR':
										alerta({mensagem:data.msg});
									break;
									case 'STATUS_INVALID':
										window.open('/meus-pedidos/', '_self');
									break;
									default:
										console.log('ERROR - PayPal Botão - '+data.status);
										carregando('fechar');
								}
							});
						},

						onAuthorize: function(data, actions) {
							/*
							* Execute the payment here
							*/
							
							ppplus_pagar({
								payID : data.paymentID,
								payerID : data.payerID,
								paypalButton : true,
								rememberedCard : '',
								installmentsValue : ('installmentsValue' in data ? data.installmentsValue : '1')
							});
						},

						onCancel: function(data, actions) {
							/*
							* Buyer cancelled the payment
							*/
						},

						onError: function(err) {
							/*
							* An error occurred during the transaction
							*/
						},
						
						onEnter: function() {
							carregando('fechar');
						}
					}, '#paypalBtn');
				} else {
					carregando('fechar');
				}
			}
			
			function ppplus_criar_pagamento(p = {}){
				var opcao = 'pagamento';
				var ajaxOpcao = 'ppplus-criar-pagamento';
				
				$.ajax({
					type: 'POST',
					url: gestor.raiz + 'pagamento/',
					data: {
						opcao : opcao,
						ajax : 'sim',
						ajaxOpcao : ajaxOpcao,
						codigo : gestor.pagamento.codigo,
						outroPagador : ('outroPagador' in p ? p.outroPagador : 'nao' ),
					},
					dataType: 'json',
					beforeSend: function(){
						carregando('abrir');
					},
					success: function(dados){
						switch(dados.status){
							case 'OK':
								if(!('pay_id' in gestor.ppplus)){
									gestor.ppplus.pay_id = new Array();
								}
								
								gestor.ppplus.pay_id[gestor.ppplus.alvo] = dados.ppplus.pay_id;
								ppplus_carregar(dados.ppplus);
							break;
							case 'API_ERROR':
								//carregando('fechar');
								alerta({mensagem:dados.msg});
							break;
							case 'STATUS_INVALID':
								window.open('/meus-pedidos/', '_self');
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
								carregando('fechar');
							
						}
					},
					error: function(txt){
						switch(txt.status){
							case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "identificacao/"),"_self"); break;
							default:
								console.log('ERROR AJAX - '+opcao+' - Dados:');
								console.log(txt);
								carregando('fechar');
						}
					}
				});
			}
			
			function ppplus_carregar(p = {}){
				if(!('ppplusLoaded' in gestor)){
					$.getScript('https://www.paypalobjects.com/webstatic/ppplusdcc/ppplusdcc.min.js',function() {
						gestor.ppplusLoaded = true;
						ppplus_iniciar(p);
					});
				} else {
					ppplus_iniciar(p);
				}
			}
			
			function ppplus_iniciar(p = {}){
				if(!('ppplusStart' in gestor)){
					gestor.ppplusStart = true;
					
					if(window.addEventListener){
						window.addEventListener("message", ppplus_receive_message, false);
						console.log("addEventListener successful", "debug");
					} else if (window.attachEvent){
						window.attachEvent("onmessage", ppplus_receive_message);
						console.log("attachEvent successful", "debug");
					} else {
						console.log("Could not attach message listener", "debug");
						throw new Error("PayPal: Can't attach message listener");
					}
				}
				
				if(p.outro_pagador){
					if(!('outroPagadorStarted' in gestor.ppplus)){
						$('.terceiroPPPCont').removeClass('escondido');
						
						gestor.ppplus.outroPagadorStarted = true;
						gestor.ppplus.alvo = 'terceiro';
						
						$('#terceiroPPPIframe').html('');
						
						window.ppp2 = PAYPAL.apps.PPP({
							"approvalUrl": p.approval_url,
							"placeholder": "terceiroPPPIframe",
							"mode": ('app_live' in gestor.pagamento ? "live" : "sandbox"),
							"payerFirstName": p.first_name,
							"payerLastName": p.last_name,
							"payerEmail": p.email,
							"payerPhone": p.telefone,
							"payerTaxId": (p.cnpj_ativo ? p.cnpj : p.cpf),
							"payerTaxIdType": (p.cnpj_ativo ? 'CNPJ' : 'CPF'),
							"language": "pt_BR",
							"country": "BR",
							"rememberedCards": (p.ppp_remembered_card_hash ? p.ppp_remembered_card_hash : ''),
							"disableContinue": function (){
								$('#terceiro-btn').addClass('disabled');
							},
							"enableContinue": function (){
								$('#terceiro-btn').removeClass('disabled');
							},
							"onLoad": function(){
								$('#terceiro-btn').on('mouseup tap',function(e){
									if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
									
									carregando('abrir');
									
									ppp2.doContinue();
								});
								
								carregando('fechar');
							},
							"onError": ppplus_on_error
						});
					}
				} else {
					if(!('pagadorStarted' in gestor.ppplus)){
						$('.proprioComp').removeClass('escondido');
						
						gestor.ppplus.pagadorStarted = true;
						gestor.ppplus.alvo = 'proprio';
						
						window.ppp = PAYPAL.apps.PPP({
							"approvalUrl": p.approval_url,
							"placeholder": "proprioPPPIframe",
							"mode": ('app_live' in gestor.pagamento ? "live" : "sandbox"),
							"payerFirstName": p.first_name,
							"payerLastName": p.last_name,
							"payerEmail": p.email,
							"payerPhone": p.telefone,
							"payerTaxId": (p.cnpj_ativo ? p.cnpj : p.cpf),
							"payerTaxIdType": (p.cnpj_ativo ? 'CNPJ' : 'CPF'),
							"language": "pt_BR",
							"country": "BR",
							"rememberedCards": (p.ppp_remembered_card_hash ? p.ppp_remembered_card_hash : ''),
							"disableContinue": function (){
								$('#proprio-btn').addClass('disabled');
							},
							"enableContinue": function (){
								$('#proprio-btn').removeClass('disabled');
							},
							"onLoad": function(){
								$('#proprio-btn').on('mouseup tap',function(e){
									if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
									
									carregando('abrir');
									
									ppp.doContinue();
								});
								
								carregando('fechar');
							},
							"onError": ppplus_on_error
						});
					}
				}
			}
			
			function ppplus_pagar(p = {}){
				var opcao = 'pagamento';
				var ajaxOpcao = 'ppplus-pagar';
				
				$.ajax({
					type: 'POST',
					url: gestor.raiz + 'pagamento/',
					data: {
						opcao : opcao,
						ajax : 'sim',
						ajaxOpcao : ajaxOpcao,
						codigo : gestor.pagamento.codigo,
						pay_id : gestor.ppplus.pay_id[gestor.ppplus.alvo],
						paypalButton : (gestor.ppplus.alvo == 'botao' ? 'sim' : 'nao'),
						outroPagador : (gestor.ppplus.alvo == 'terceiro' ? 'sim' : 'nao'),
						payerID : p.payerID,
						rememberedCard : p.rememberedCard,
						installmentsValue : p.installmentsValue
					},
					dataType: 'json',
					beforeSend: function(){
						carregando('abrir');
					},
					success: function(dados){
						switch(dados.status){
							case 'OK':
								if(dados.pending == 'sim'){
									window.open('/meus-pedidos/', '_self');
								} else {
									window.open('/voucher/?pedido='+gestor.pagamento.codigo, '_self');
								}
							break;
							case 'API_ERROR':
								alerta({mensagem:dados.msg});
								paypal_reiniciar();
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
								carregando('fechar');
							
						}
					},
					error: function(txt){
						switch(txt.status){
							case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "identificacao/"),"_self"); break;
							default:
								console.log('ERROR AJAX - '+opcao+' - Dados:');
								console.log(txt);
								carregando('fechar');
						}
					}
				});
			}
			
			function ppplus_on_error(erro){
				var msg;
				var notReload = false;
				
				// ===== Erros não tratáveis
				
				if(typeof erro === 'string'){
					if(erro.match(/Unexpected token/g)){
						return false;
					}
				}
				
				switch(erro){
					case 'Unexpected token o in JSON at position 1':
						return false;
					break;
					case 'JSON Parse error: Unexpected identifier "object"':
						return false;
					break;
				}
				
				// ===== Analisar erros
				
				if(Array.isArray(erro)){
					if(
						erro[0].match(/payerLastName/) == 'payerLastName' ||
						erro[0].match(/payerFirstName/) == 'payerFirstName' ||
						erro[0].match(/payerEmail/) == 'payerEmail' ||
						erro[0].match(/payerPhone/) == 'payerPhone' ||
						erro[0].match(/payerTaxId/) == 'payerTaxId'
					){
						carregando('fechar');
						msg = 'Erro na conferência dos seus dados pelo gateway de pagamento. Um desses campos não foram aceitos: CPF ou CNPJ, nome, último nome, ou telefone. '+(gestor.ppplus.alvo == 'proprio' ? 'Favor acesse <a href="/minha-conta/" target="_parent">Sua Conta</a> , modifique seus dados, e então acesse <a href="/meus-pedidos/" target="_parent">Suas Compras</a> e clique no botão Pagar e tente novamente.' : 'Favor clique no botão "Trocar Pagador" e preencha os dados novamente.');
					} else {
						carregando('fechar');
						msg = 'Ocorreu um erro inesperado, favor entrar em contato com o suporte e informe o seguinte: <b>'+erro[0]+'</b>.';
					}
				} else {
					switch (erro){
						case "INTERNAL_SERVICE_ERROR": //javascript fallthrough
						case "SOCKET_HANG_UP": //javascript fallthrough
						case "socket hang up": //javascript fallthrough
						case "connect ECONNREFUSED": //javascript fallthrough
						case "connect ETIMEDOUT": //javascript fallthrough
						case "UNKNOWN_INTERNAL_ERROR": //javascript fallthrough
						case "fiWalletLifecycle_unknown_error": //javascript fallthrough
						case "Failed to decrypt term info": //javascript fallthrough
						case "RESOURCE_NOT_FOUND": //javascript fallthrough
						case "INTERNAL_SERVER_ERROR":
							//Generic error, inform the customer to try again; generate a new approval_url andreload the iFrame.
							gestor.ppplus.erro = true;
							msg = 'Ocorreu um erro inesperado, por favor tente novamente';
						break;
						case "RISK_N_DECLINE": //javascript fallthrough
						case "NO_VALID_FUNDING_SOURCE_OR_RISK_REFUSED": //javascript fallthrough
						case "TRY_ANOTHER_CARD": //javascript fallthrough
						case "NO_VALID_FUNDING_INSTRUMENT":
							//Risk denial, inform the customer to try again; generate a new approval_url and reload the iFrame.
							gestor.ppplus.erro = true;
							msg = 'Seu pagamento não foi aprovado. Por favor utilize outro cartão, caso o problema persista entre em contato com o PayPal (0800-047-4482).';
						break;
						case "CARD_ATTEMPT_INVALID":
							//03 maximum payment attempts with error, inform the customer to try again; generate a new approval_url and reload the iFrame.
							gestor.ppplus.erro = true;
							msg = 'Você alcançou o máximo de 3 tentativas com dados inválidos do cartão, clique em OK e tente novamente com outro cartão.';
						break;
						case "INVALID_OR_EXPIRED_TOKEN":
							//User session is expired, inform the customer to try again; generate a new approval_url and reload the iFrame.
							gestor.ppplus.erro = true;
							msg = 'A sua sessão de pagamento expirou, atualize sua página para tentar novamente.';
						break;
						case "CHECK_ENTRY":
							//Missing or invalid credit card information, inform your customer to check the inputs.
							msg = 'Por favor revise os dados de Cartão de Crédito inseridos.';
							notReload = true;
						break;
						default: //unknown error & reload payment flow
							//Generic error, inform the customer to try again; generate a new approval_url and reload the iFrame.
							gestor.ppplus.erro = true;
							msg = 'Ocorreu um erro inesperado, por favor tente novamente.';
					}
				}
				
				if(msg){
					if(notReload){
						alerta({mensagem:msg});
					} else {
						alerta({mensagem:msg});
						paypal_reiniciar();
					}
				} else {
					msg = 'Ocorreu um erro inesperado, por favor tente novamente.';
					alerta({mensagem:msg});
					paypal_reiniciar();
				}
				
				ppplus_log({
					erro : erro,
					msg : msg
				});
			}
			
			function ppplus_log(p={}){
				var erro = p.erro;
				var msg = p.msg;
				
				var erroJson = JSON.stringify(erro);
				var opcao = 'pagamento';
				var ajaxOpcao = 'ppplus-log';
				
				var data = {
					opcao : opcao,
					ajax : 'sim',
					ajaxOpcao : ajaxOpcao,
					codigo : gestor.pagamento.codigo,
					msg : msg,
					erro : erroJson,
				};
				
				$.ajax({
					type: 'POST',
					url: gestor.raiz + 'pagamento/',
					data: data,
					dataType: 'json',
					beforeSend: function(){
						
					},
					success: function(dados){
						switch(dados.status){
							case 'OK':
								
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					},
					error: function(txt){
						console.log('ERROR - '+opcao+' - '+txt);
					}
				});
			}
			
			function ppplus_receive_message(event){
				try {
					var message = JSON.parse(event.data);
					
					if(typeof message['cause'] !== 'undefined'){ //iFrame error handling
						ppplusError = message['cause'].replace (/['"]+/g,""); //log & attach this error into the order if possible
						ppplus_on_error(ppplusError);
					}
					
					if(message['action'] == 'loaded') {
						if('outroPagadorCaregandoHack' in gestor.ppplus){
							delete gestor.ppplus.outroPagadorCaregandoHack;
							carregando('fechar');
						}
					}
					
					if(message['action'] == 'enableContinueButton' && message['result'] == 'error') {
						carregando('fechar');
					}
					
					if(message['action'] == 'checkout') { //PPPlus session approved, do logic here
						var rememberedCard = null;
						var payerID = null;
						var installmentsValue= null;
						
						rememberedCard = message['result']['rememberedCards']; //save on user BD record
						payerID = message['result']['payer']['payer_info']['payer_id']; //use it on executePayment API
						
						if("term" in message){
							installmentsValue = message['result']['term']['term']; //installments value
						} else {
							installmentsValue=1; //no installments
						}
						
						// Next steps:
						//1) Save the rememberedCard value on the user record on your Database.
						//2) Save the installmentsValue value into the order (Optional).
						//3) Call executePayment API using payerID value to capture the payment.
						
						ppplus_pagar({
							payerID : payerID,
							rememberedCard : rememberedCard,
							installmentsValue : installmentsValue
						});
					}
				} catch (e){
					//ppplus_on_error(e.message);
				}
			}
			
			// ===== Funções Auxiliares.
			
			function carregando(opcao){
				switch(opcao){
					case 'abrir':
						if(!('carregando' in gestor)){
							$('.paginaCarregando').dimmer({
								closable: false
							});
							
							gestor.carregando = true;
						}
						
						$('.paginaCarregando').dimmer('show');
					break;
					case 'fechar':
						$('.paginaCarregando').dimmer('hide');
					break;
				}
			}
			
			function primeiraLetraMaiuscula(str){
				function capitalize(str) {
					return str.charAt(0).toUpperCase() + str.slice(1);
				}

				const caps = str.trim().split(' ').map(capitalize).join(' ');
				
				return caps;
			}
			
		}
		
		function alerta(p={}){
			if(p.mensagem){
				$('.ui.modal.alerta .content p').html(p.mensagem);
			}
			
			$('.ui.modal.alerta').modal({
				dimmerSettings:{
					dimmerName:'paginaCarregando' //className, NOT id (!)
				}
			}).modal('show');
		}
	}
	
	start();
	
});