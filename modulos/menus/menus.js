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
				
				console.log('for menus');
				if(itens.length > 0){
					console.log(itens);
					
					for(var key in itens){
						var obj = data[key];
						
						var menuItem = $($('.menu-item-template').html());
						
						menuItem.find('.itemNome').html(obj.label);
						menuItem.find('.itemUrl').html(obj.url);
						menuItem.find('.itemTipo').html(obj.tipo);
						
						menuItem.appendTo($('#menu-pagina-inicial'));
					}
				}
			}
		}
		
		menus_iniciar();
	}
	
});