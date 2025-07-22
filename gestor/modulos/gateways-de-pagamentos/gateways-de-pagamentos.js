$(document).ready(function(){
	
	if($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0){
		
	}
	
	if($('.ativacao').length > 0){
		// ===== Popup dos botões.
		
		$('.button')
			.popup()
		;
		
		// ===== Regras de exclusão
		
		$(document.body).on('mouseup tap','.desinstalarBtn',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			desinstalar_confirmacao();
		});
		
		function desinstalar_confirmacao(){
			$('.ui.modal.confirm').modal({
				onApprove: function() {
					carregando('abrir');
					
					window.open('/gateways-de-pagamentos/paypal/?desinstalar=sim',"_self");
					
					return false;
				}
			});
			
			$('.ui.modal.confirm').modal('show');
		}
		
		// ===== Botões de alteração de estado.
		
		$(document.body).on('mouseup tap','.buttons .button',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var btn = $(this);
			
			if(!btn.hasClass('active')){
				// ===== Ação do botão.
				
				var acao = btn.attr('data-acao');
				
				// ===== AJAX para alteração no servidor.
				
				var opcao = 'paypal';
				var ajaxOpcao = 'paypal';
				
				$.ajax({
					type: 'POST',
					url: gestor.raiz + gestor.moduloId + '/',
					data: {
						opcao : opcao,
						ajax : 'sim',
						ajaxOpcao : ajaxOpcao,
						ajaxRegistroId : gestor.moduloRegistroId,
						acao : acao
					},
					dataType: 'json',
					beforeSend: function(){
						carregando('abrir');
					},
					success: function(dados){
						switch(dados.status){
							case 'Ok':
								// ===== Remover classes de todos os botões de cor marcadora e de estado ativo.
								
								btn.parent().find('.button').removeClass('active');
								btn.parent().find('.button').removeClass('blue');
								
								// ===== Ativar e marcar botão atual.
								
								btn.addClass('active');
								btn.addClass('blue');
							break;
							case 'ERROR':
								alerta({mensagem:dados.msg});
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
						
						carregando('fechar');
					},
					error: function(txt){
						switch(txt.status){
							case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "signin/"),"_self"); break;
							default:
								console.log('ERROR AJAX - '+opcao+' - Dados:');
								console.log(txt);
								carregando('fechar');
						}
					}
				});
			}
		});
		
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
		
		function alerta(p={}){
			if(p.mensagem){
				$('.ui.modal.alerta .content p').html(p.mensagem);
			}
			
			$('.ui.modal.alerta').modal('show');
		}
	}
});