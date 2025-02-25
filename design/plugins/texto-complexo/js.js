function texto_complexo_for_textarea(){
	var obj = b2make.conteiner_child_obj;
	var texto_cont = $(obj).find('.b2make-widget-out').find('.b2make-texto-complexo');
	
	var texto = texto_cont.html();
	texto_cont.html('');
	
	b2make.widget_mask_hide = true;
	$(obj).find('.b2make-widget-mask').hide();
	$('#b2make-selecionador-objetos-mask').hide();
	b2make.selecionador_objetos_mask_force_hide = true;
	
	if(!b2make.tinymce_count)b2make.tinymce_count = 0;
	b2make.tinymce_count++;
	
	b2make.texto_complexo_textarea = $('<div id="b2make-texto-complexo-conteiner"><div id="b2make-texto-complexo-textarea-'+b2make.tinymce_count+'"></div></div>');
	b2make.texto_complexo_textarea.attr('data-type','texto-complexo');
	b2make.texto_complexo_textarea.appendTo(texto_cont);

	if(b2make.google_fonts_collection){
		$.b2make_tinymce_start({selector:'#b2make-texto-complexo-textarea-'+b2make.tinymce_count,value:texto});
		
		b2make.copy_paste.inativo = true;
		b2make.texto_complexo_for_textarea = true;
	} else {
		texto_complexo_load_google_fonts({texto:texto});
	}
}

function textarea_for_texto_complexo(){
	var obj = b2make.conteiner_child_obj;
	var texto_cont = $(obj).find('.b2make-widget-out').find('.b2make-texto-complexo');
	
	$(obj).find('.b2make-widget-mask').show();
	$('#b2make-selecionador-objetos-mask').show();
	
	var texto = tinymce.activeEditor.getContent({format : 'raw'});
	
	$.b2make_tinymce_destroy(null);
	b2make.texto_complexo_textarea = false;
	
	texto_cont.html(texto);
	
	b2make.copy_paste.inativo = false;
	b2make.texto_complexo_for_textarea = false;
}

function texto_complexo_start(){
	$(window).bind('keyup',function(e) {
		if(e.keyCode == 27){ // ESC
			if(b2make.texto_complexo_for_textarea){
				textarea_for_texto_complexo();
			}
		}
	});
	
	$('#b2make-wotc-text-editar').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		if(b2make.texto_complexo_for_textarea){
			textarea_for_texto_complexo();
		} else {
			texto_complexo_for_textarea();
		}
	});
}

function texto_complexo_resize(){
	var conteiner = b2make.selecionador_objetos.conteiner;
	$.b2make_tinymce_resize({height:$(conteiner).outerHeight()});
}

function texto_complexo_load_google_fonts(p = {}){
	$.ajax({
		dataType: "json",
		url: 'webfonts/webfonts.js?v=3',
		data: { 
			
		},
		beforeSend: function(){
			$.carregamento_open();
		},
		success: function(txt){
			b2make.google_fonts_collection = txt;
			
			$.b2make_tinymce_start({selector:'#b2make-texto-complexo-textarea-'+b2make.tinymce_count,value:p.texto});
			
			b2make.copy_paste.inativo = true;
			b2make.texto_complexo_for_textarea = true;
			
			$.carregamento_close();
		},
		error: function(txt){
			console.log(txt.responseText);
			$.carregamento_close();
		}
	});
}

var _plugin_id = 'texto-complexo';

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
			
			$.fonts_load({obj:'#b2make-widget-options-'+plugin_id});
			$.jpicker_load({obj:'#b2make-widget-options-'+plugin_id});
			
			$.menu_conteiner_aba_load({
				id:plugin_id,
				html:html.find('#b2make-conteiner-aba-extra-'+plugin_id).clone()
			});
			
			$.fonts_load({obj:'.b2make-conteiner-aba-extra[data-type="'+plugin_id+'"]'});
			$.jpicker_load({obj:'.b2make-conteiner-aba-extra[data-type="'+plugin_id+'"]'});
			
			$.widget_specific_options_open();
			$.widget_sub_options_open();
			
			$('.b2make-tooltip').tooltip({
				show: {
					effect: "fade",
					delay: 400
				}
			});
			
			texto_complexo_start();
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
		b2make.texto_complexo_widget_add = true;
	});
	
	$('#b2make-listener').on('b2make-conteiner-child-open',function(e){
		switch(b2make.conteiner_child_type){
			case plugin_id:
				if(b2make.texto_complexo_widget_add){
					texto_complexo_for_textarea();
					b2make.texto_complexo_widget_add = false;
				}
			break;
		}
	});
	
	$('#b2make-listener').on('b2make-conteiner-child-close',function(e){
		switch(b2make.conteiner_child_type){
			case plugin_id:
				if(b2make.texto_complexo_for_textarea)textarea_for_texto_complexo();
			break;
		}
	});
	
	$('#b2make-listener').on('widgets-resize',function(e){
		switch(b2make.conteiner_child_type){
			case 'texto-complexo':
				texto_complexo_resize();
			break;
		}
	});
	
	$('#b2make-listener').on('selecionador-objetos-dblclick',function(e){
		switch(b2make.conteiner_child_type){
			case 'texto-complexo':
				if(b2make.texto_complexo_for_textarea){
					textarea_for_texto_complexo();
				} else {
					texto_complexo_for_textarea();
				}
			break;
		}
	});
	
	$('#b2make-listener').on('widgets-resize-finish widgets-change-width widgets-change-height',function(e){
		switch(b2make.conteiner_child_type){
			case 'texto-complexo':
				texto_complexo_resize();
			break;
		}
	});
	
	$('#b2make-selecionador-objetos-mask,.b2make-widget-mask[data-type="texto-complexo"]').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		switch(b2make.conteiner_child_type){
			case plugin_id:
				if(b2make.texto_complexo_for_textarea){
					$(this).hide();
				}
			break;
		}
	});
	
}

var fn = window[_plugin_id];fn();