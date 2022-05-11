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
				
				for(var key in itens){
					var item = itens[key];
					
					var menuItem = $($('.menu-item-template').html());
					
					menuItem.find('.itemNome').html(item.label);
					menuItem.find('.itemUrl').html(item.url);
					menuItem.find('.itemTipo').html(item.tipo);
					
					menuItem.appendTo($('div[data-tab="'+key+'"]').find('.menu-itens-cont'));
				}
			}
			
			$('.ui.accordion')
				.accordion()
			;
			
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