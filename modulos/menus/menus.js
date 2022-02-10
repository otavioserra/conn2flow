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
			
			$('.ui.accordion')
				.accordion()
			;
			
			// ===== Iniciar menus.
			
			var menus = new Array('menuPaginaInicial');
			
			for(var i=0;i<menus.length;i++){
				var itens = dadosServidor[menus[i]].itens;
				
				for(var key in itens){
					var item = itens[key];
					
					var menuItem = $($('.menu-item-template').html());
					
					menuItem.find('.itemNome').html(item.label);
					menuItem.find('.itemUrl').html(item.url);
					menuItem.find('.itemTipo').html(item.tipo);
					
					menuItem.appendTo($('#menu-pagina-inicial'));
				}
			}
		}
		
		menus_iniciar();
	}
	
});