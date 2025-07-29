$(document).ready(function(){
	
	if($('.gestor-servico-comprar').length > 0){
		
		$('.gestor-servico-comprar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			window.open(gestor.raiz + gestor.servicos.carrinho_url + '?adicionar=sim&id=' + gestor.servicos.id_servicos, '_self');
		});
	}
	
	if($('.gestor-lote-variacao-comprar').length > 0){
		const formCurrency = new Intl.NumberFormat('pt-BR', {
			style: 'currency',
			currency: 'BRL',
			minimumFractionDigits: 2
		});
		
		var quantidadeTotal = 0;
		var variacoes = {};
		var total = 0;
		
		// ===== Iniciação das variações.
		
		if('variacoes' in gestor.servicos){
			for(var key in gestor.servicos.variacoes){
				variacoes[key] = gestor.servicos.variacoes[key];
				
				quantidadeTotal += parseInt(variacoes[key].quantidade);
				total += parseInt(variacoes[key].quantidade) * parseFloat(variacoes[key].preco);
			}
			
			// ===== Habilitar ou desabilitar o botão comprar.
		
			if(quantidadeTotal <= 0){
				$('.gestor-lote-variacao-comprar').addClass('disabled');
			} else {
				$('.gestor-lote-variacao-comprar').removeClass('disabled');
			}
			
			// ===== Atualizar valor total.
			
			var totalStr = formCurrency.format(total);
			$('.total span').html(totalStr);
		}
		
		// ===== Função de alteração do carrinho.
		
		function carrinho_alterar(opcao,id,variacao_id = false){
			// ===== Alterar dados do carrinho disparado pelos botões.
			
			var ajaxOpcao = 'carrinho_alterar';
			var data = {
				opcao : gestor.moduloOpcao,
				ajax : 'sim',
				ajaxOpcao,
				ajaxPagina : 'sim',
				id
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
							// ===== Remover o subtotal.
							
							var subtotal = parseInt(variacoes[variacao_id].quantidade) * parseFloat(variacoes[variacao_id].preco);
							total = total - subtotal;
							
							// ===== Atualizar a quantidade no objeto.
							
							if(opcao == 'adicionar'){
								variacoes[variacao_id].quantidade++;
								quantidadeTotal++;
							} else {
								variacoes[variacao_id].quantidade--;
								quantidadeTotal--;
							}
							
							// ===== Modificar a interface gráfica.
							
							var subtotal = parseInt(variacoes[variacao_id].quantidade) * parseFloat(variacoes[variacao_id].preco);
							var subtotalStr = formCurrency.format(subtotal);
							
							total += subtotal;
							
							var totalStr = formCurrency.format(total);
							$('.total span').html(totalStr);
							
							$('.variacaoCel[data-id="'+variacao_id+'"]').find('.quantidade').html(variacoes[variacao_id].quantidade);
							$('.variacaoCel[data-id="'+variacao_id+'"]').find('.subtotal').html('Subtotal: '+subtotalStr);
							
							// ===== Habilitar ou desabilitar o botão comprar.
						
							if(quantidadeTotal <= 0){
								$('.gestor-lote-variacao-comprar').addClass('disabled');
							} else {
								$('.gestor-lote-variacao-comprar').removeClass('disabled');
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
		
		$('.gestor-lote-variacao-comprar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			window.open(gestor.raiz + gestor.servicos.carrinho_url, '_self');
		});
		
		// ===== Botão menos.
		
		$(document.body).on('mouseup tap','.botaoMenos',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var cont = $(this).parents('div[data-id]');
			var id = gestor.servicos.id_servicos;
			var variacao_id = cont.attr('data-id');
			
			if(variacoes[variacao_id].quantidade > 0){
				carrinho_alterar('diminuir',id,variacao_id);
			}
			
		});
		
		// ===== Botão mais.
		
		$(document.body).on('mouseup tap','.botaoMais',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var cont = $(this).parents('div[data-id]');
			var id = gestor.servicos.id_servicos;
			var variacao_id = cont.attr('data-id');
			
			carrinho_alterar('adicionar',id,variacao_id);
		});
		
		
	}
	
});