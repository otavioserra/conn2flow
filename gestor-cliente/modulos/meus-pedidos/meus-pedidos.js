$(document).ready(function(){
	
	function start(){
		// ===== Regras para ler mais entradas do histórico.
		
		var resultadoPaginaAtual = 0;
		var button_id = '.carregarMais';
		
		$(button_id).on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var opcao = 'pedidos';
			var ajaxOpcao = 'mais-resultados';
			
			resultadoPaginaAtual++;
			
			var pagina = resultadoPaginaAtual;
			
			$.ajax({
				type: 'POST',
				url: '/meus-pedidos/',
				data: { 
					opcao,
					ajax : 'sim',
					ajaxPagina : 'sim',
					ajaxOpcao,
					pagina : pagina
				},
				dataType: 'json',
				beforeSend: function(){
					carregando('abrir');
				},
				success: function(dados){
					switch(dados.status){
						case 'OK':
							$('.pedidos').append(dados.resultados);
							
							var totalPaginas = gestor.pedidos.totalPaginas;
							if(resultadoPaginaAtual >= parseInt(totalPaginas) - 1){
								$(button_id).parent().hide();
							}
							
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
	}
	
	start();
	
});