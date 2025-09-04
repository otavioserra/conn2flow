$(document).ready(function(){
	
	if($('#_gestor-interface-visualizar-dados').length > 0){
		// ===== Dados do comprador popup.
		
		$('.icon.buttons .button').popup({
			delay: {
				show: 150,
				hide: 0
			},
			position:'top right',
			variation:'inverted'
		});
		
		// ===== Dados do comprador ajax.
		
		$('.dadosPagadorBtn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var opcao = 'visualizar';
			var ajaxOpcao = 'dados-do-comprador';
			
			$.ajax({
				type: 'POST',
				url: gestor.raiz + gestor.moduloId + '/',
				data: {
					opcao : opcao,
					ajax : 'sim',
					ajaxOpcao : ajaxOpcao,
					pagamento_id : $(this).attr('data-id')
				},
				dataType: 'json',
				beforeSend: function(){
					carregando('abrir');
				},
				success: function(dados){
					switch(dados.status){
						case 'OK':
							modal({mensagem:dados.pagador});
						break;
						case 'ERROR':
							modal({mensagem:dados.msg});
						break;
						default:
							console.log('ERROR - '+opcao+' - '+dados.status);
							carregando('fechar');
						
					}
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
		});
		
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
		
		function modal(p={}){
			if(p.mensagem){
				$('.ui.modal.simples .content p').html(p.mensagem);
			}
			
			$('.ui.modal.simples').modal({
				dimmerSettings:{
					dimmerName:'paginaCarregando' //className, NOT id (!)
				}
			}).modal('show');
		}
	}
	
});