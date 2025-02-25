var _plugin_id = 'menu-paginas';
if(!b2make.plugin) b2make.plugin = {};
b2make.plugin[_plugin_id] = {};

function menu_paginas_selecao_pontual_data_update(){
	var obj = b2make.conteiner_child_obj;
	
	setTimeout(function(){
		var ids_paginas = '';
		$('.b2make-womp-paginas-input').each(function(){
			var value = $(this).val();
			var checcked = $(this).prop('checked');
			
			if(checcked){
				ids_paginas = ids_paginas + (ids_paginas ? ',' : '') + value;
			}
		});
		
		$(obj).attr('data-ids-paginas',ids_paginas);
	},100);
}

function menu_paginas_selecao_pontual_menu_filhos(paginas,id_pai,nivel,paginas_ids){
	for(var key in paginas[id_pai]){
		var dados = paginas[id_pai][key];
		
		var input = $('<input type="checkbox"'+(paginas_ids[dados.id] ? ' checked="checked"' : '')+' id="b2make-womp-paginas-input-'+dados.id+'" class="b2make-womp-paginas-input" value="'+dados.id+'">');
		var label = $('<label type="checkbox" for="b2make-womp-paginas-input-'+dados.id+'" class="b2make-womp-paginas-label">'+dados.nome+'</label>');
		
		var conteiner = $('<div class="b2make-womp-paginas-cont"></div>');
		
		input.appendTo(conteiner);
		label.appendTo(conteiner);
		
		conteiner.appendTo('#b2make-womp-paginas');
		
		menu_paginas_selecao_pontual_menu_filhos(paginas,dados.id,nivel+1,paginas_ids);
	}
}

function menu_paginas_selecao_pontual_menu(p){
	if(!p) p = {};
	
	var obj = b2make.conteiner_child_obj;
	
	var ids_paginas = $(obj).attr('data-ids-paginas');
	var paginas_ids = new Array();
	ids_paginas_arr = ( ids_paginas ? ids_paginas.split(',') : false);
	
	if(ids_paginas_arr)
	for(var i=0;i<ids_paginas_arr.length;i++){
		paginas_ids[ids_paginas_arr[i]] = true;
	}
	
	if(!$.local_storage_get_array('paginas-arvore')){
		$('#b2make-listener').trigger('b2make-menu-paginas-start',['b2make-menu-paginas-started']);
		return false;
	}
	
	var paginas_arvore = $.local_storage_get_array('paginas-arvore');paginas_arvore = paginas_arvore[0];
	
	$('#b2make-womp-paginas').html('');
	
	var input = $('<input type="checkbox"'+(paginas_ids[paginas_arvore['0'].id] ? ' checked="checked"' : '')+' id="b2make-womp-paginas-input-'+paginas_arvore['0'].id+'" class="b2make-womp-paginas-input" value="'+paginas_arvore['0'].id+'">');
	var label = $('<label type="checkbox" for="b2make-womp-paginas-input-'+paginas_arvore['0'].id+'" class="b2make-womp-paginas-label">'+paginas_arvore['0'].nome+'</label>');
	
	var conteiner = $('<div class="b2make-womp-paginas-cont"></div>');
	
	input.appendTo(conteiner);
	label.appendTo(conteiner);
	
	conteiner.appendTo('#b2make-womp-paginas');
	
	menu_paginas_selecao_pontual_menu_filhos(paginas_arvore,paginas_arvore['0'].id,1,paginas_ids);
}

function menu_paginas_html_update(){
	var obj = b2make.conteiner_child_obj;
	var cont = $(obj).find('.b2make-widget-out').find('.b2make-menu-paginas');
	
	if($(obj).attr('data-escrito-menu')){
		cont.attr('data-escrito-menu',$(obj).attr('data-escrito-menu'));
	} else {
		cont.attr('data-escrito-menu','s');
	}
	
	$(obj).attr('data-font-family','Maven Pro');
	$(obj).attr('data-google-font','sim');
	
	$.google_fonts_wot_load({
		family : $(obj).attr('data-font-family')
	});
	
	cont.html('');
}

function menu_paginas_start(plugin_id){
	if(b2make.plugin[plugin_id].widget_added){
		menu_paginas_html_update();
	}
	
	b2make.plugin[plugin_id].started = true;
	
	$('#b2make-womp-label-sel').on('change',function(e){
		var obj = b2make.conteiner_child_obj;
		$(obj).attr('data-escrito-menu',$(this).val());
		menu_paginas_html_update();
	});
	
	$('#b2make-womp-type-sel').on('change',function(e){
		var obj = b2make.conteiner_child_obj;
		$(obj).attr('data-tipo-menu',$(this).val());
	});
	
	$(document.body).on('change','#b2make-womp-options-sel',function(){
		var obj = b2make.conteiner_child_obj;
		var value = $(this).val();
		
		$(obj).attr('data-paginas-opcao',value);
		
		if(!b2make.menu_paginas_selecao_pontual_pronto){
			b2make.menu_paginas_selecao_pontual_pronto = true;
			menu_paginas_selecao_pontual_menu({});
		}
		
		if($(obj).attr('data-paginas-opcao') == 'todas'){
			$('#b2make-womp-paginas-escolha-pontual-cont').hide();
		} else {
			$('#b2make-womp-paginas-escolha-pontual-cont').show();
		}
	});
	
	$('#b2make-listener').on('b2make-menu-paginas-started',function(e){
		menu_paginas_selecao_pontual_menu({});
	});

	$('#b2make-womp-selecionar-todos').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		$('.b2make-womp-paginas-cont').find('input').prop('checked','checked');
		
		menu_paginas_selecao_pontual_data_update();
	});
	
	$('#b2make-womp-deselecionar-todos').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		$('.b2make-womp-paginas-cont').find('input').prop('checked',false);
		
		menu_paginas_selecao_pontual_data_update();
	});
	
	$(document.body).on('mouseup tap','.b2make-womp-paginas-label,.b2make-womp-paginas-input',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		var obj = b2make.conteiner_child_obj;
		var ids_paginas = $(obj).attr('data-ids-paginas');
		var ids_paginas_arr = (ids_paginas ? ids_paginas.split(',') : new Array());
		
		if($(this).hasClass('b2make-womp-paginas-input')){
			var input = $(this);
		} else {
			var input = $(this).parent().find('input');
		}
		
		var id = input.val();
		
		setTimeout(function(){
			var ids_novo = '';
			if(!input.prop('checked')){
				if(ids_paginas_arr)
				for(var i=0;i<ids_paginas_arr.length;i++){
					if(ids_paginas_arr[i] != id){
						ids_novo = ids_novo + (ids_novo ? ',':'') + ids_paginas_arr[i];
					}
				}
			} else {
				ids_novo = ids_paginas + (ids_paginas ? ',':'') + id;
			}
			
			$(obj).attr('data-ids-paginas',ids_novo);
		},100);
	});
	
	$(document.body).on('changeColor','#b2make-wo-menu-paginas-widget-cor-val',function(e){
		var id = $(this).attr('id');
		var bg = $(b2make.jpicker.obj).css('background-color');
		var ahex = $(b2make.jpicker.obj).attr('data-ahex');
		var obj = b2make.conteiner_child_obj;
		
		switch(id){
			case 'b2make-wo-menu-paginas-widget-cor-val':
				$(obj).find('.b2make-widget-out').find('.b2make-menu-paginas').css('background-color',bg);
				$(obj).attr('data-widget-color-ahex',ahex);	
			break;				
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
			var sub_options = html.find('#b2make-widget-sub-options-'+plugin_id).clone();
			sub_options.appendTo('#b2make-widget-options-hide');
			
			$.menu_conteiner_aba_load({
				id:plugin_id,
				html:html.find('#b2make-conteiner-aba-extra-'+plugin_id).clone()
			});
			
			$.fonts_load({obj:'.b2make-conteiner-aba-extra[data-type="'+plugin_id+'"]'});
			$.jpicker_load({obj:'.b2make-conteiner-aba-extra[data-type="'+plugin_id+'"]'});
			
			if(b2make.plugin[plugin_id].widget_added){
				$.menu_conteiner_aba_extra_open();
				$.widget_specific_options_open();
			}
			
			menu_paginas_start(plugin_id);
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
		if(!b2make.plugin[plugin_id].started){
			b2make.plugin[plugin_id].widget_added = true;			
		} else {
			menu_paginas_html_update();
		}
	});
	
	$('#b2make-listener').on('b2make-conteiner-child-open',function(e){
		switch(b2make.conteiner_child_type){
			case plugin_id:
				var obj = b2make.conteiner_child_obj;
				
				if($(obj).attr('data-escrito-menu')){
					var option = $('#b2make-womp-label-sel').find("[value='" + $(obj).attr('data-escrito-menu') + "']");
					option.prop('selected', 'selected');
				} else {
					var option = $('#b2make-womp-label-sel').find(":first");
					option.prop('selected', 'selected');
				}
				
				if($(obj).attr('data-tipo-menu')){
					var option = $('#b2make-womp-type-sel').find("[value='" + $(obj).attr('data-tipo-menu') + "']");
					option.prop('selected', 'selected');
				} else {
					var option = $('#b2make-womp-type-sel').find(":first");
					option.prop('selected', 'selected');
				}
				
				if($(obj).attr('data-paginas-opcao')){
					if($(obj).attr('data-paginas-opcao') == 'todas'){
						$('#b2make-womp-paginas-escolha-pontual-cont').hide();
					} else {
						$('#b2make-womp-paginas-escolha-pontual-cont').show();
					}
					
					var option = $('#b2make-womp-options-sel').find("[value='" + $(obj).attr('data-paginas-opcao') + "']");
					option.prop('selected', 'selected');
				} else {
					$('#b2make-womp-paginas-escolha-pontual-cont').hide();
					
					var option = $('#b2make-womp-options-sel').find(":first");
					option.prop('selected', 'selected');
				}
				
				if($(obj).attr('data-widget-color-ahex')){
					$('#b2make-wo-menu-paginas-widget-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-widget-color-ahex')));
					$('#b2make-wo-menu-paginas-widget-cor-val').attr('data-ahex',$(obj).attr('data-widget-color-ahex'));
				} else {
					$('#b2make-wo-menu-paginas-widget-cor-val').css('background-color','#003F72');
					$('#b2make-wo-menu-paginas-widget-cor-val').attr('data-ahex','003F72FF');
				}
				
				menu_paginas_selecao_pontual_menu({});
			break;
		}
	});
	
	$('#b2make-listener').on('b2make-conteiner-child-close',function(e){
		switch(b2make.conteiner_child_type){
			case plugin_id:
				
			break;
		}
	});
	
	$(b2make.widget).each(function(){
		if($(this).attr('data-type') == plugin_id){
			$.widgets_read_google_font({
				tipo : 1,
				obj : $(this)
			});
		}
	});
}

var fn = window[_plugin_id];fn();