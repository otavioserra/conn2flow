$(document).ready(function() {
	// ===== Input delay
	
	$.input_delay_to_change = function(p){
		if(!gestor.input_delay){
			gestor.input_delay = new Array();
			gestor.input_delay_count = 0;
		}
		
		gestor.input_delay_count++;
		
		var valor = gestor.input_delay_count;
		
		gestor.input_delay.push(valor);
		gestor.input_value = p.value;
		
		setTimeout(function(){
			if(gestor.input_delay[gestor.input_delay.length - 1] == valor){
				input_change_after_delay({value:gestor.input_value,trigger_selector:p.trigger_selector,trigger_event:p.trigger_event});
			}
		},gestor.input_delay_timeout);
	}
	
	function input_change_after_delay(p){
		$(p.trigger_selector).trigger(p.trigger_event,[p.value,gestor.input_delay_params]);
		
		gestor.input_delay = false;
	}
	
	function input_delay(){
		if(!gestor.input_delay_timeout) gestor.input_delay_timeout = 400;
		
	}
	
	input_delay();
	
	// ===== Interfaces
	
	function alerta(p={}){
		if(p.msg){
			$('.ui.modal.alerta .content p').html(p.msg);
		}
		
		$('.ui.modal.alerta').modal('show');
	}
	
	function carregar_abrir(){
		var timeOut = 5000;
		
		if(!gestor.carregandoNum){
			gestor.carregandoNum = 1;
			
			$('.ui.modal.carregando').modal({
				closable : false,
				onShow: function(){
					var num = gestor.carregandoNum;
					setTimeout(function(){
						if(num == gestor.carregandoNum && gestor.carregando){
							$('.ui.modal.carregando').modal('hide');
							alerta({msg: gestor.componentes.ajaxTimeoutMessage});
						}
					},timeOut);
				}
			});
			
			$('.ui.modal.carregando').modal('setting', "duration", "0");
		} else {
			gestor.carregandoNum++;
		}
		
		$('.ui.modal.carregando').modal('show');
		gestor.carregando = true;
		gestor.carregandoTime = Date.now();
	}
	
	function carregar_fechar(){
		gestor.carregando = false;
		
		var timeTransition = 200;
		var timeOut = timeTransition - (Date.now() - gestor.carregandoTime);
		
		if(timeOut > 0){
			setTimeout(function(){
				$('.ui.modal.carregando').modal('hide');
			},timeOut);
		} else {
			$('.ui.modal.carregando').modal('hide');
		}
	}
	
	function interface_start(){
		
		if(typeof gestor.interface !== typeof undefined && gestor.interface !== false){
			if(typeof gestor.interface.alerta !== typeof undefined && gestor.interface.alerta !== false){
				alerta(gestor.interface.alerta);
			}
		}
		
		// ===== Triggers principais
		
		$('#gestor-listener').on('carregar_abrir',function(e){
			carregar_abrir();
		});
		
		$('#gestor-listener').on('carregar_fechar',function(e){
			carregar_fechar();
		});
		
		$('#gestor-listener').on('alerta',function(e,p){
			alerta(p);
		});
		
		// ===== Regras para ler mais entradas do histórico.
		
		if('historico' in gestor.interface){
			var historicoPaginaAtual = 0;
			var button_id = '_gestor-interface-edit-historico-mais';
			var opcao = 'historico-mais-resultados';
			
			$('#'+button_id).on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var ajaxOpcao = 'historico-mais-resultados';
				
				if('id' in gestor.interface){
					var id = gestor.interface.id;
				} else {
					var id = '';
				}
				
				historicoPaginaAtual++;
				
				var pagina = historicoPaginaAtual;
				
				$.ajax({
					type: 'POST',
					url: gestor.raiz + gestor.moduloCaminho + '/',
					data: { 
						opcao : gestor.moduloOpcao,
						ajax : 'sim',
						ajaxOpcao : ajaxOpcao,
						pagina : pagina,
						id : id
					},
					dataType: 'json',
					beforeSend: function(){
						carregar_abrir();
					},
					success: function(dados){
						switch(dados.status){
							case 'Ok':
								$('#'+button_id).parent().parent().before(dados.pagina);
								
								var totalPaginas = gestor.interface.totalPaginas;
								if(historicoPaginaAtual >= parseInt(totalPaginas) - 1){
									$('#'+button_id).hide();
								}
								
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
						
						carregar_fechar();
					},
					error: function(txt){
						switch(txt.status){
							case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"),"_self"); break;
							default:
								console.log('ERROR AJAX - '+opcao+' - Dados:');
								console.log(txt);
								carregar_fechar();
						}
					}
				});
			});
		}
		
		// ===== Autorização Provisória
		
		if($('.autorizacaoProvisoria').length > 0){
			$('.ui.modal.autorizacaoProvisoria').modal({
				closable : false,
				onApprove: function(){
					return false;
				},
				onDeny: function(){
					return false;
				},
			});
			
			$('.ui.modal.autorizacaoProvisoria').modal('show');
		}
	}
	
	interface_start();
});