$(document).ready(function(){
	
	function carrinho_alterar(opcao,id,variacao_id = false){
		// ===== Alterar dados do carrinho disparado pelos botões.
		
		var ajaxOpcao = 'carrinho_alterar';
		var data = {
			opcao : gestor.moduloOpcao,
			ajax : 'sim',
			ajaxOpcao : ajaxOpcao,
			ajaxPagina : 'sim',
			id_alt : id
		};
		
		data[opcao] = 'sim';
		
		if(variacao_id){
			data['variacao_id'] = variacao_id;
		}
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: data,
			dataType: 'json',
			beforeSend: function(){
				$('#gestor-listener').trigger('carregar_abrir');
			},
			success: function(dados){
				switch(dados.status){
					case 'OK':
						$('.contServicos').html(dados.servicos);
						$('.contResumo').html(dados.resumo);
						
						if('vazio' in dados){
							$('.botaoProximo').hide();
						} else {
							$('.botaoProximo').show();
						}
					break;
					default:
						if(dados.msg){
							$('#gestor-listener').trigger('alerta',[{msg:dados.msg}]);
						} else {
							console.log('ERROR - '+ajaxOpcao+' - Dados:');
							console.log(dados);
						}
				}
				
				$('#gestor-listener').trigger('carregar_fechar');
			},
			error: function(txt){
				switch(txt.status){
					case 401: window.open(gestor.raiz + (txt.responseJSON.redirect ? txt.responseJSON.redirect : "identificacao/"),"_self"); break;
					default:
						console.log('ERROR AJAX - '+ajaxOpcao+' - Dados:');
						console.log(txt);
				}
				
				$('#gestor-listener').trigger('carregar_abrir');
			}
		});
	}
	
	function start(){
		// ===== Botão menos.
		
		$(document.body).on('mouseup tap','.botaoMenos',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var cont = $(this).parents('div[data-id]');
			var id = cont.attr('data-id');
			var variacao_id = cont.attr('data-id-varicao');
			
			if(typeof variacao_id !== 'undefined' && variacao_id !== false){
				carrinho_alterar('diminuir',id,variacao_id);
			} else {
				carrinho_alterar('diminuir',id);
			}
		});
		
		// ===== Botão mais.
		
		$(document.body).on('mouseup tap','.botaoMais',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var cont = $(this).parents('div[data-id]');
			var id = cont.attr('data-id');
			var variacao_id = cont.attr('data-id-varicao');
			
			if(typeof variacao_id !== 'undefined' && variacao_id !== false){
				carrinho_alterar('adicionar',id,variacao_id);
			} else {
				carrinho_alterar('adicionar',id);
			}
		});
		
		// ===== Botão excluir.
		
		$(document.body).on('mouseup tap','.excluir',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var cont = $(this).parents('div[data-id]');
			var id = cont.attr('data-id');
			var variacao_id = cont.attr('data-id-varicao');
			
			if(typeof variacao_id !== 'undefined' && variacao_id !== false){
				carrinho_alterar('excluir',id,variacao_id);
			} else {
				carrinho_alterar('excluir',id);
			}
		});
		
		// ===== Se o carrinho for vazio, remover botão próxima etapa.
		
		if('vazio' in gestor.carrinho){
			$('.botaoProximo').hide();
		}
		
		// ===== Continuar comprando botão.
		
		$('.botaoContinuarComprando').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			window.open(gestor.carrinho.continuarComprando, '_self');
		});
		
		// ===== Continuar comprando botão.
		
		$('.botaoProximo').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			window.open(gestor.carrinho.botaoProximo + '?carrinho=sim', '_self');
		});
	}
	
	start();
	
});