var _plugin_id = 'accordion';
if(!b2make.plugin) b2make.plugin = {};
b2make.plugin[_plugin_id] = {};

function accordion_html_update(){
	var obj = b2make.conteiner_child_obj;
	
	var botao_cont = $(obj).find('.b2make-widget-out').find('.b2make-accordion');
	
	var texto = botao_cont.find('.b2make-accordion-texto-cont').html();
	var texto_botao = $(obj).attr('data-texto-botao');
	
	if(typeof texto_botao !== typeof undefined && texto_botao !== false) {
		var verdade = true;
	} else {
		texto_botao = b2make.accordion.texto_botao;
	}
	
	if(typeof texto !== typeof undefined && texto !== false) {
		var verdade = true;
	} else {
		texto = '';
	}
	
	botao_cont.html('<div class="b2make-accordion-table"><div class="b2make-accordion-cel">'+texto_botao+'</div></div><div class="b2make-accordion-texto-cont">'+texto+'</div>');
	
	var botao_texto_cont = botao_cont.find('.b2make-accordion-table').find('.b2make-accordion-cel');
	var texto_cont = botao_cont.find('.b2make-accordion-texto-cont');
	
	if(typeof $(obj).attr('data-accordion-preenchimento-color-ahex') !== typeof undefined && $(obj).attr('data-accordion-preenchimento-color-ahex') !== false){
		$(obj).css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-accordion-preenchimento-color-ahex')));
	} else {
		$(obj).css('background-color','#434142');
	}
	
	if(typeof $(obj).attr('data-accordion-preenchimento-texto-color-ahex') !== typeof undefined && $(obj).attr('data-accordion-preenchimento-texto-color-ahex') !== false){
		texto_cont.css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-accordion-preenchimento-texto-color-ahex')));
	} else {
		texto_cont.css('background-color','#ededed');
	}
	
	if(typeof $(obj).attr('data-accordion-texto-color-ahex') !== typeof undefined && $(obj).attr('data-accordion-texto-color-ahex') !== false){
		botao_texto_cont.css('color',$.jpicker_ahex_2_rgba($(obj).attr('data-accordion-texto-color-ahex')));
	} else {
		botao_texto_cont.css('color','#ffffff');
	}
	
	if(typeof $(obj).attr('data-accordion-botao-texto-font-family') !== typeof undefined && $(obj).attr('data-accordion-botao-texto-font-family') !== false){
		botao_texto_cont.css({'fontFamily': $(obj).attr('data-accordion-botao-texto-font-family')});
	} else {
		botao_texto_cont.css({'fontFamily': b2make.font});
	}

	if(typeof $(obj).attr('data-accordion-botao-texto-font-size') !== typeof undefined && $(obj).attr('data-accordion-botao-texto-font-size') !== false){
		botao_texto_cont.css({'fontSize': $(obj).attr('data-accordion-botao-texto-font-size')});
	} else {
		botao_texto_cont.css({'fontSize': '17px'});
	}
	
}

function accordion_start(plugin_id){
	if(b2make.plugin[plugin_id].widget_added){
		accordion_html_update();
		$('#b2make-listener').trigger('b2make-conteiner-child-open');
	}
	
	b2make.plugin[plugin_id].started = true;
	
	$('#b2make-accordion-preenchimento-cor-val,#b2make-accordion-preenchimento-2-cor-val,#b2make-accordion-texto-cor-val,#b2make-accordion-texto-2-cor-val,#b2make-accordion-preenchimento-texto-cor-val').on('changeColor',function(e){
		var id = $(this).attr('id');
		var bg = $(b2make.jpicker.obj).css('background-color');
		var ahex = $(b2make.jpicker.obj).attr('data-ahex');
		var obj = b2make.conteiner_child_obj;
		
		switch(id){
			case 'b2make-accordion-preenchimento-cor-val':
				$(obj).css('background-color',bg);
				$(obj).attr('data-accordion-preenchimento-color-ahex',ahex);
			break;
			case 'b2make-accordion-preenchimento-2-cor-val':
				$(obj).attr('data-accordion-preenchimento-2-color-ahex',ahex);
			break;
			case 'b2make-accordion-texto-cor-val':
				$(obj).find('.b2make-widget-out').find('.b2make-accordion').find('.b2make-accordion-table').find('.b2make-accordion-cel').css('color',bg);
				$(obj).attr('data-accordion-texto-color-ahex',ahex);
			break;
			case 'b2make-accordion-texto-2-cor-val':
				$(obj).attr('data-accordion-texto-2-color-ahex',ahex);
			break;
			case 'b2make-accordion-preenchimento-texto-cor-val':
				$(obj).find('.b2make-widget-out').find('.b2make-accordion').find('.b2make-accordion-texto-cont').css('background-color',bg);
				$(obj).attr('data-accordion-preenchimento-texto-color-ahex',ahex);
			break;
			
		}
	});
	
	$('#b2make-accordion-botao-texto-cont').on('changeFontFamily changeFontSize changeFontAlign changeFontItalico changeFontNegrito',function(e){
		var obj = b2make.conteiner_child_obj;
		var target;
		var pai;
		var target2 = false;
		var cssVar = '';
		var noSize = false;
		var type = $(this).attr('id')
		
		target = $(obj).find('.b2make-widget-out').find('.b2make-accordion').find('.b2make-accordion-table').find('.b2make-accordion-cel');
		
		switch(e.type){
			case 'changeFontFamily': cssVar = 'fontFamily'; target.css(cssVar,$(this).attr('data-font-family')); $(obj).attr('data-accordion-botao-texto-font-family',$(this).attr('data-font-family')); break;
			case 'changeFontSize': cssVar = 'fontSize';  target.css(cssVar,$(this).attr('data-font-size')+'px'); target.css('line-height',$(this).attr('data-font-size')+'px'); $(obj).attr('data-accordion-botao-texto-font-size',$(this).attr('data-font-size')); break;
			case 'changeFontAlign': cssVar = 'textAlign'; target.css(cssVar,$(this).attr('data-font-align'));$(obj).attr('data-accordion-botao-texto-font-align',$(this).attr('data-font-align')); break;
			case 'changeFontItalico': cssVar = 'fontStyle'; target.css(cssVar,($(this).attr('data-font-italico') == 'sim' ? 'italic' : 'normal')); $(obj).attr('data-accordion-botao-texto-font-italico',$(this).attr('data-font-italico')); break;
			case 'changeFontNegrito': cssVar = 'fontWeight'; target.css(cssVar,($(this).attr('data-font-negrito') == 'sim' ? 'bold' : 'normal')); $(obj).attr('data-accordion-botao-texto-font-negrito',$(this).attr('data-font-negrito')); break;
		}
	});
	
	$(document.body).on('keyup','#b2make-accordion-botao-texto-val',function(e){
		var value = $(this).val();
		var id = $(this).attr('id');
		
		$.input_delay_to_change({
			trigger_selector:'#b2make-listener',
			trigger_event:'b2make-accordion-botao-texto-val-change',
			value:value
		});
	});
	
	$(document.body).on('b2make-accordion-botao-texto-val-change','#b2make-listener',function(e,value,p){
		if(!p) p = {};
		
		var obj = b2make.conteiner_child_obj;
		$(obj).attr('data-accordion-botao-texto-val',value);
		
		$(obj).find('.b2make-widget-out').find('.b2make-accordion').find('.b2make-accordion-table').find('.b2make-accordion-cel').html(value);
	});
}

window[_plugin_id] = function(){
	var plugin_id = _plugin_id;
	
	b2make.accordion = {};
	
	if(!b2make.accordion.texto_botao) b2make.accordion.texto_botao = 'Clique para Visualizar';
	
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
			
			if(b2make.plugin[plugin_id].widget_added){
				$.widget_specific_options_open();
				$.widget_sub_options_open();
				$.menu_conteiner_aba_extra_open();
			}
			
			$('.b2make-tooltip').tooltip({
				show: {
					effect: "fade",
					delay: 400
				}
			});
			
			accordion_start(plugin_id);
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
			accordion_html_update();
		}
	});
	
	$('#b2make-listener').on('b2make-conteiner-child-open',function(e){
		switch(b2make.conteiner_child_type){
			case plugin_id:
				var obj = b2make.conteiner_child_obj;
				
				if(typeof $(obj).attr('data-accordion-preenchimento-color-ahex') !== typeof undefined && $(obj).attr('data-accordion-preenchimento-color-ahex') !== false){
					$('#b2make-accordion-preenchimento-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-accordion-preenchimento-color-ahex')));
					$('#b2make-accordion-preenchimento-cor-val').attr('data-ahex',$(obj).attr('data-accordion-preenchimento-color-ahex'));
				} else {
					$('#b2make-accordion-preenchimento-cor-val').css('background-color','#434142');
					$('#b2make-accordion-preenchimento-cor-val').attr('data-ahex','434142ff');
				}
				
				if(typeof $(obj).attr('data-accordion-preenchimento-2-color-ahex') !== typeof undefined && $(obj).attr('data-accordion-preenchimento-2-color-ahex') !== false){
					$('#b2make-accordion-preenchimento-2-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-accordion-preenchimento-2-color-ahex')));
					$('#b2make-accordion-preenchimento-2-cor-val').attr('data-ahex',$(obj).attr('data-accordion-preenchimento-2-color-ahex'));
				} else {
					$('#b2make-accordion-preenchimento-2-cor-val').css('background-color','#686868');
					$('#b2make-accordion-preenchimento-2-cor-val').attr('data-ahex','686868ff');
				}
				
				if(typeof $(obj).attr('data-accordion-texto-color-ahex') !== typeof undefined && $(obj).attr('data-accordion-texto-color-ahex') !== false){
					$('#b2make-accordion-texto-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-accordion-texto-color-ahex')));
					$('#b2make-accordion-texto-cor-val').attr('data-ahex',$(obj).attr('data-accordion-texto-color-ahex'));
				} else {
					$('#b2make-accordion-texto-cor-val').css('background-color','#ffffff');
					$('#b2make-accordion-texto-cor-val').attr('data-ahex','ffffffff');
				}
				
				if(typeof $(obj).attr('data-accordion-texto-2-color-ahex') !== typeof undefined && $(obj).attr('data-accordion-texto-2-color-ahex') !== false){
					$('#b2make-accordion-texto-2-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-accordion-texto-2-color-ahex')));
					$('#b2make-accordion-texto-2-cor-val').attr('data-ahex',$(obj).attr('data-accordion-texto-2-color-ahex'));
				} else {
					$('#b2make-accordion-texto-2-cor-val').css('background-color','#ededed');
					$('#b2make-accordion-texto-2-cor-val').attr('data-ahex','edededff');
				}
				
				if(typeof $(obj).attr('data-accordion-preenchimento-texto-color-ahex') !== typeof undefined && $(obj).attr('data-accordion-preenchimento-texto-color-ahex') !== false){
					$('#b2make-accordion-preenchimento-texto-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-accordion-preenchimento-texto-color-ahex')));
					$('#b2make-accordion-preenchimento-texto-cor-val').attr('data-ahex',$(obj).attr('data-accordion-preenchimento-texto-color-ahex'));
				} else {
					$('#b2make-accordion-preenchimento-texto-cor-val').css('background-color','#ededed');
					$('#b2make-accordion-preenchimento-texto-cor-val').attr('data-ahex','edededff');
				}
				
				if(typeof $(obj).attr('data-accordion-botao-texto-font-family') !== typeof undefined && $(obj).attr('data-accordion-botao-texto-font-family') !== false){
					$('#b2make-accordion-botao-texto-cont').find('.b2make-fonts-holder').css({
						'fontFamily': $(obj).attr('data-accordion-botao-texto-font-family')
					});
					$('#b2make-accordion-botao-texto-cont').find('.b2make-fonts-holder').html($(obj).attr('data-accordion-botao-texto-font-family'));
				} else {
					$('#b2make-accordion-botao-texto-cont').find('.b2make-fonts-holder').css({
						'fontFamily': b2make.font
					});
					$('#b2make-accordion-botao-texto-cont').find('.b2make-fonts-holder').html(b2make.font);
				}

				if(typeof $(obj).attr('data-accordion-botao-texto-font-size') !== typeof undefined && $(obj).attr('data-accordion-botao-texto-font-size') !== false){
					$('#b2make-accordion-botao-texto-cont').find('.b2make-fonts-size').val($(obj).attr('data-accordion-botao-texto-font-size'));
				} else {
					$('#b2make-accordion-botao-texto-cont').find('.b2make-fonts-size').val(17);
				}
				
				if(typeof $(obj).attr('data-accordion-botao-texto-val') !== typeof undefined && $(obj).attr('data-accordion-botao-texto-val') !== false){
					$('#b2make-accordion-botao-texto-val').val($(obj).attr('data-accordion-botao-texto-val'));
				} else {
					$('#b2make-accordion-botao-texto-val').val('Clique para Visualizar');
				}
				
			break;
		}
	});
	
	$('#b2make-listener').on('b2make-conteiner-child-close',function(e){
		switch(b2make.conteiner_child_type){
			case plugin_id:
				
			break;
		}
	});
	
	$('#b2make-listener').on('b2make-lightbox-opened',function(e){
		switch(b2make.conteiner_child_type){
			case plugin_id:
				var obj = b2make.conteiner_child_obj;
				
				var texto = $(obj).find('.b2make-widget-out').find('.b2make-accordion').find('.b2make-accordion-texto-cont').html();
				
				if(typeof texto !== typeof undefined && texto !== false) {
					var verdade = true;
				} else {
					texto = '';
				}
				
				b2make.accordion.lightbox_opened = true;
				b2make.accordion.obj = b2make.conteiner_child_obj;
				b2make.accordion.texto_antigo = texto;
				
				$.b2make_tinymce_start({selector:'#b2make-accordion-texto',value:texto,nao_texto_complexo:true});
			break;
		}
	});
	
	$('#b2make-listener').on('b2make-lightbox-closed',function(e){
		if(b2make.accordion.lightbox_opened){
			var obj = b2make.accordion.obj;
			
			var texto_antigo = b2make.accordion.texto_antigo;
			var texto_novo = tinymce.activeEditor.getContent({format : 'raw'});
			
			$.b2make_tinymce_destroy_2({selector:'b2make-accordion-texto'});
			$('#b2make-accordion-texto-conteiner').html('<div id="b2make-accordion-texto"></div>');
			
			if(texto_antigo != texto_novo){
				$(obj).find('.b2make-widget-out').find('.b2make-accordion').find('.b2make-accordion-texto-cont').html(texto_novo);
			}
			
			b2make.accordion.lightbox_opened = false;
		}
	});
	
}

var fn = window[_plugin_id];fn();