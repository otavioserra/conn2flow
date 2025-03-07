$(document).ready(function(){
	
	if($('#menus-cont').length > 0){
		// ===== Dados do servidor.
		
		var dadosServidor = {};
		
		function iniciarDadosServidor(){
			dadosServidor = JSON.parse($('#dadosServidor').val());
		}
		
		function salvarDadosServidor(){
			$('#dadosServidor').val(JSON.stringify(dadosServidor));
		}
		
		// ===== Iniciação dos menus.
		
		function menus_iniciar(){
			iniciarDadosServidor();
			
			// ===== Iniciar menus.
			
			for(var key in dadosServidor){
				// ===== Varrer itens do menu.
				
				var itens = dadosServidor[key].itens;
				
				for(var key2 in itens){
					var item = itens[key2];
					
					var menuItem = $($('.menu-item-template').html());
					
					menuItem.attr('data-id',key2);
					
					if('inativo' in item){
						menuItem.find('.itemInativo').find('input').prop('checked',true);
					}
					
					menuItem.find('.itemNome').html((('titulo' in item) ? item.titulo : item.label));
					menuItem.find('.itemUrl').html(item.url);
					menuItem.find('.itemTipo').html(item.tipo);
					
					menuItem.appendTo($('div[data-tab="'+key+'"]').find('.menu-itens-cont'));
				}
			}
			
			$('.ui.checkbox')
				.checkbox({
					onChange: function(){
						var obj = $(this).parents('.menu-item');
						var objPai = $(this).parents('.menuCont');
						var checked = $(this).prop('checked');
						
						var idPai = objPai.attr('data-tab');
						var id = obj.attr('data-id');
						
						if(checked){
							dadosServidor[idPai].itens[id].inativo = true;
						} else {
							delete dadosServidor[idPai].itens[id].inativo;
						}
						
						salvarDadosServidor();
					}
				});
				
			$('.ui.accordion')
				.accordion();
			
			var itens = {};
			var currentMousePos = { x: -1, y: -1, mX: 20, mY: 30 };
			var currentElementClone;
			
			$('.menu-item').on('dragstart',function(e){
				var objPai = $(this).parents('.menu-itens-cont');
				
				// ===== Pegar a posição de todos os itens.
				
				var posicoes = new Array();
				
				objPai.find('.menu-item').each(function(){
					posicoes.push({
						
					});
				});
				
				itens.posicoes = posicoes;
				
				// ===== Criar ou modificar área de drop.
				
				
				
				// ===== Elemento clone.
				
				currentElementClone = $(this).clone();
				
				currentElementClone.css('position','absolute');
				
				currentElementClone.css('top',$(this).position().top);
				currentElementClone.css('left',$(this).position().left);
				currentElementClone.css('width',$(this).outerWidth(true));
				currentElementClone.css('height',$(this).outerHeight(true));
				currentElementClone.css('backgroundColor','#FFF');
				
				currentElementClone.hide();
				
				currentElementClone.appendTo($('#menu-pagina-inicial'));
				
				// ===== Esconder elemento.
				
				$(this).hide();
			});
			
			$('.menu-item').on('drag',function(e){
				currentElementClone.show();
				currentElementClone.css('top',currentMousePos.y);
				currentElementClone.css('left',currentMousePos.x);
			});
			
			$('.menu-item').on('dragend',function(e){
				currentElementClone.remove();
				
				$(this).show();
			});
			
			$('.menu-itens-cont').on('dragover',function(e){
				currentMousePos.x = e.pageX - $(this).offset().left - currentMousePos.mX;
				currentMousePos.y = e.pageY - $(this).offset().top - currentMousePos.mY;
			});
		}
		
		menus_iniciar();
	}
	
});