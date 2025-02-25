var _plugin_id = 'breadcrumbs';
if(!b2make.plugin) b2make.plugin = {};
b2make.plugin[_plugin_id] = {};

function breadcrumbs_montar_menu(p = {}){
	var pagina_arvore = p.pagina_arvore;
	var inicio = 'In&iacute;cio';
	
	var url = '<a href="#" class="b2make-breadcrumbs-link'+(p.start? ' b2make-breadcrumbs-link-2':'')+'">'+pagina_arvore.nome+'</a>';
	
	if(pagina_arvore.pai){
		return breadcrumbs_montar_menu({pagina_arvore:pagina_arvore.pai}) + '<span class="b2make-breadcrumbs-link-sep"> / </span>' + url
	} else {
		return '<a href="#" class="b2make-breadcrumbs-link">'+inicio+'</a>' + (p.start? '' : '<span class="b2make-breadcrumbs-link-sep"> / </span>'+url);
	}
}

function breadcrumbs_html_update(p = {}){
	var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
	var cont_principal = $(obj).find('.b2make-widget-out').find('.b2make-breadcrumbs');
	var atual_id = $(obj).attr('data-id');
	var plugin_id = 'breadcrumbs';
	var id_func = 'pagina-arvore';
	
	cont_principal.html('');
	
	$.ajax({
		type: 'POST',
		url: 'plugins/'+plugin_id+'/index.php',
		data: { 
			ajax : 'sim',
			opcao : id_func,
			atual_id : atual_id
		},
		beforeSend: function(){
		},
		success: function(txt){
			if(
				txt.charAt(0) == "{" || 
				txt.charAt(0) == "["
			){
				var dados = eval('(' + txt + ')');
				
				switch(dados.status){
					case 'Ok':
						var pagina_arvore = dados.pagina_arvore;
						
						var breadcrumbs = breadcrumbs_montar_menu({pagina_arvore:pagina_arvore,start:true});
						cont_principal.html(breadcrumbs);
						
						$(obj).find('.b2make-widget-out').find('.b2make-widget-loading').hide();
					break;
					case 'NaoExisteId':
						//
					break;
					default:
						console.log('ERROR - '+id_func+' - '+dados.status);
					
				}
			} else {
				console.log('ERROR - '+id_func+' - '+txt);
			}
		},
		error: function(txt){
			console.log('ERROR AJAX - '+id_func+' - '+txt);
		}
	});
	
}

function breadcrumbs_start(plugin_id){
	if(b2make.plugin[plugin_id].widget_added){
		breadcrumbs_html_update();
	}
	
	b2make.plugin[plugin_id].started = true;
	
	$(b2make.widget).each(function(){
		if($(this).attr('data-type') != 'conteiner-area'){
			switch($(this).attr('data-type')){
				case plugin_id:
					breadcrumbs_html_update({obj:this});
				break;
			}
		}
	});
}

window[_plugin_id] = function(){
	var plugin_id = _plugin_id;
	
	$.ajax({
		type: 'POST',
		url: 'plugins/'+plugin_id+'/html.html',
		beforeSend: function(){
		},
		success: function(txt){
			var html = $('<div>'+txt+'</div>');
			
			var options = html.find('#b2make-widget-options-'+plugin_id).clone();
			options.appendTo('#b2make-widget-options-hide');
			
			$.fonts_load({obj:'#b2make-widget-options-'+plugin_id});
			$.jpicker_load({obj:'#b2make-widget-options-'+plugin_id});
			
			$('.b2make-tooltip').tooltip({
				show: {
					effect: "fade",
					delay: 400
				}
			});
			
			breadcrumbs_start(plugin_id);
		},
		error: function(txt){
			console.log('ERROR AJAX - '+plugin_id+' - html - '+txt);
		}
	});
	
	// =========
	
	$('#b2make-'+plugin_id+'-callback').on('callback',function(e){
		$.conteiner_child_open({select:true,widget_type:plugin_id});
	});
	
	$('#b2make-'+plugin_id+'-callback').on('widget_added',function(e){
		var obj = b2make.conteiner_child_obj;
		var atual_id = b2make.menu_paginas.atual_id;
		
		$(obj).attr('data-id',atual_id);
		
		if(!b2make.plugin[plugin_id].started){
			b2make.plugin[plugin_id].widget_added = true;			
		} else {
			breadcrumbs_html_update();
		}
	});
	
	$('#b2make-listener').on('b2make-conteiner-child-open',function(e){
		switch(b2make.conteiner_child_type){
			case plugin_id:
				
			break;
		}
	});
	
	$('#b2make-listener').on('b2make-conteiner-child-close',function(e){
		switch(b2make.conteiner_child_type){
			case plugin_id:
				
			break;
		}
	});
}

var fn = window[_plugin_id];fn();