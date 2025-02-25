var _plugin_id = 'progresso';
if(!b2make.plugin) b2make.plugin = {};
b2make.plugin[_plugin_id] = {};

function progresso_html_update(p = {}){
	var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
	
	var cont_principal = $(obj).find('.b2make-widget-out').find('.b2make-progresso');

	cont_principal.html('');
	
	if($(obj).attr('data-porcentagem')){
		var porcentagem = $(obj).attr('data-porcentagem');
	} else {
		var porcentagem = 10;
	}
	
	if($(obj).attr('data-espessura')){
		var espessura = $(obj).attr('data-espessura');
	} else {
		var espessura = 10;
	}
	
	var barra1 = $('<div class="b2make-progresso-barra-1"></div>');
	var barra2 = $('<div class="b2make-progresso-barra-2"></div>');
	var barra_cont = $('<div class="b2make-progresso-barra-cont"></div>');
	
	barra_cont.css('height',espessura+'px');
	barra2.css('width',porcentagem+'%');
	
	var texto = $('<div class="b2make-progresso-texto">'+porcentagem+'%</div>');
	
	barra1.appendTo(barra_cont);
	barra2.appendTo(barra_cont);
	
	barra_cont.appendTo(cont_principal);
	texto.appendTo(cont_principal);
	
	var ids = new Array('porcentagem');
	var mudar_height = false;
	var target;
	
	for(var i=0;i<ids.length;i++){
		var id = ids[i];
		
		target = $(obj).find('.b2make-widget-out').find('.b2make-progresso').find('.b2make-progresso-texto'); mudar_height = true;
		
		if($(obj).attr('data-'+id+'-font-family'))target.css('fontFamily',$(obj).attr('data-'+id+'-font-family'));
		if($(obj).attr('data-'+id+'-font-size')){
			target.css('fontSize',$(obj).attr('data-'+id+'-font-size')+'px');
			var line_height = parseInt($(obj).attr('data-'+id+'-font-size'));
			target.css('line-height',line_height+'px');
		}
		if($(obj).attr('data-'+id+'-font-align'))target.css('textAlign',$(obj).attr('data-'+id+'-font-align'));
		if($(obj).attr('data-'+id+'-font-italico'))target.css('fontStyle',($(obj).attr('data-'+id+'-font-italico') == 'sim' ? 'italic' : 'normal'));
		if($(obj).attr('data-'+id+'-font-negrito'))target.css('fontWeight',($(obj).attr('data-'+id+'-font-negrito') == 'sim' ? 'bold' : 'normal'));
	}
	
	if($(obj).attr('data-barra1-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-progresso').find('.b2make-progresso-barra-cont').find('.b2make-progresso-barra-1').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-barra1-color-ahex')));
	if($(obj).attr('data-barra2-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-progresso').find('.b2make-progresso-barra-cont').find('.b2make-progresso-barra-2').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-barra2-color-ahex')));
	if($(obj).attr('data-texto-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-progresso').find('.b2make-progresso-texto').css('color',$.jpicker_ahex_2_rgba($(obj).attr('data-texto-color-ahex')));
}

function progresso_start(plugin_id){
	if(b2make.plugin[plugin_id].widget_added){
		progresso_html_update();
	}
	
	b2make.plugin[plugin_id].started = true;
	
	b2make.progresso = {};
	
	b2make.progresso.porcentagem_max = 100;
	b2make.progresso.porcentagem_min = 0;
	b2make.progresso.espessura_max = 500;
	b2make.progresso.espessura_min = 0;
	
	$(b2make.widget).each(function(){
		if($(this).attr('data-type') != 'conteiner-area'){
			switch($(this).attr('data-type')){
				case 'progresso':
					$.widgets_read_google_font({
						tipo : 2,
						types : new Array('porcentagem'),
						obj : $(this)
					});
					
					progresso_html_update({obj:this});
				break;
			}
		}
	});

	$(document.body).on('keyup','#b2make-wo-progresso-porcentagem',function(e){
		var value = parseInt($(this).val());
		var id = $(this).attr('id');
		
		if(value > b2make.progresso.porcentagem_max){
			this.value = b2make.progresso.porcentagem_max;
			value = b2make.progresso.porcentagem_max;
		}
		
		if(value < b2make.progresso.porcentagem_min){
			value = b2make.progresso.porcentagem_min;
		}
		
		if(!value){
			value = b2make.progresso.porcentagem_min;
		}
		
		$.input_delay_to_change({
			trigger_selector:'#b2make-listener',
			trigger_event:'b2make-wo-progresso-porcentagem-change',
			value:value
		});
	});
	
	$(document.body).on('b2make-wo-progresso-porcentagem-change','#b2make-listener',function(e,value,p){
		if(!p) p = {};
		
		var obj = b2make.conteiner_child_obj;
		
		$(obj).attr('data-porcentagem',value);
		
		progresso_html_update({});
	});
	
	$(document.body).on('keyup','#b2make-wo-progresso-espessura',function(e){
		var value = parseInt($(this).val());
		var id = $(this).attr('id');
		
		if(value > b2make.progresso.espessura_max){
			this.value = b2make.progresso.espessura_max;
			value = b2make.progresso.espessura_max;
		}
		
		if(value < b2make.progresso.espessura_min){
			value = b2make.progresso.espessura_min;
		}
		
		if(!value){
			value = b2make.progresso.espessura_min;
		}
		
		$.input_delay_to_change({
			trigger_selector:'#b2make-listener',
			trigger_event:'b2make-wo-progresso-espessura-change',
			value:value
		});
	});
	
	$(document.body).on('b2make-wo-progresso-espessura-change','#b2make-listener',function(e,value,p){
		if(!p) p = {};
		
		var obj = b2make.conteiner_child_obj;
		
		$(obj).attr('data-espessura',value);
		
		progresso_html_update({});
	});

	$(document.body).on('changeColor','#b2make-wo-progresso-barra1-cor-val,#b2make-wo-progresso-barra2-cor-val,#b2make-wo-progresso-texto-cor-val',function(e){
		var id = $(this).attr('id');
		var bg = $(b2make.jpicker.obj).css('background-color');
		var ahex = $(b2make.jpicker.obj).attr('data-ahex');
		var obj = b2make.conteiner_child_obj;
		
		switch(id){
			case 'b2make-wo-progresso-barra1-cor-val':
				$(obj).find('.b2make-widget-out').find('.b2make-progresso').find('.b2make-progresso-barra-cont').find('.b2make-progresso-barra-1').css('background-color',bg);
				$(obj).attr('data-barra1-color-ahex',ahex);	
			break;
			case 'b2make-wo-progresso-barra2-cor-val':
				$(obj).find('.b2make-widget-out').find('.b2make-progresso').find('.b2make-progresso-barra-cont').find('.b2make-progresso-barra-2').css('background-color',bg);
				$(obj).attr('data-barra2-color-ahex',ahex);	
			break;
			case 'b2make-wo-progresso-texto-cor-val':
				$(obj).find('.b2make-widget-out').find('.b2make-progresso').find('.b2make-progresso-texto').css('color',bg);
				$(obj).attr('data-texto-color-ahex',ahex);
			break;					
		}
	});
	
	$(document.body).on('changeFontFamily changeFontSize changeFontAlign changeFontItalico changeFontNegrito','#b2make-wo-progresso-porcentagem-text-cont',function(e){
		var obj = b2make.conteiner_child_obj;
		var target;
		var cssVar = '';
		var noSize = false;
		var nao_mudar_line_height = false;
		var id_bruto = $(this).attr('id');
		var mudar_height = false;
		var id = id_bruto.replace(/b2make-wo-progresso-/gi,'');
		
		id = id.replace(/-text-cont/gi,'');
		
		target = $(obj).find('.b2make-widget-out').find('.b2make-progresso').find('.b2make-progresso-texto'); mudar_height = true;
		
		switch(e.type){
			case 'changeFontFamily': cssVar = 'fontFamily'; target.css(cssVar,$(this).attr('data-font-family')); $(obj).attr('data-'+id+'-font-family',$(this).attr('data-font-family')); break;
			case 'changeFontSize': 
				cssVar = 'fontSize';  target.css(cssVar,$(this).attr('data-font-size')+'px'); if(!nao_mudar_line_height) target.css('line-height',$(this).attr('data-font-size')+'px'); $(obj).attr('data-'+id+'-font-size',$(this).attr('data-font-size')); 
				
				if(!nao_mudar_line_height){
					var line_height = parseInt($(this).attr('data-font-size'));
					target.css('line-height',line_height+'px');
				}
			break;
			case 'changeFontAlign': cssVar = 'textAlign'; target.css(cssVar,$(this).attr('data-font-align'));$(obj).attr('data-'+id+'-font-align',$(this).attr('data-font-align')); break;
			case 'changeFontItalico': cssVar = 'fontStyle'; target.css(cssVar,($(this).attr('data-font-italico') == 'sim' ? 'italic' : 'normal')); $(obj).attr('data-'+id+'-font-italico',$(this).attr('data-font-italico')); break;
			case 'changeFontNegrito': cssVar = 'fontWeight'; target.css(cssVar,($(this).attr('data-font-negrito') == 'sim' ? 'bold' : 'normal')); $(obj).attr('data-'+id+'-font-negrito',$(this).attr('data-font-negrito')); break;
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
			
			$.widget_specific_options_open();
			
			$('.b2make-tooltip').tooltip({
				show: {
					effect: "fade",
					delay: 400
				}
			});
			
			progresso_start(plugin_id);
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
			progresso_html_update();
		}
	});
	
	$('#b2make-listener').on('b2make-conteiner-child-open',function(e){
		switch(b2make.conteiner_child_type){
			case plugin_id:
				var obj = b2make.conteiner_child_obj;
				
				if($(obj).attr('data-porcentagem')){
					$('#b2make-wo-progresso-porcentagem').val($(obj).attr('data-porcentagem'));
				} else {
					$('#b2make-wo-progresso-porcentagem').val(10);
				}
				
				if($(obj).attr('data-espessura')){
					$('#b2make-wo-progresso-espessura').val($(obj).attr('data-espessura'));
				} else {
					$('#b2make-wo-progresso-espessura').val(10);
				}
				
				if($(obj).attr('data-widget-color-ahex')){
					$('#b2make-wo-contents-widget-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-widget-color-ahex')));
					$('#b2make-wo-contents-widget-cor-val').attr('data-ahex',$(obj).attr('data-widget-color-ahex'));
				} else {
					$('#b2make-wo-contents-widget-cor-val').css('background-color','transparent');
					$('#b2make-wo-contents-widget-cor-val').attr('data-ahex',false);
				}
				
				if($(obj).attr('data-barra1-color-ahex')){
					$('#b2make-wo-progresso-barra1-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-barra1-color-ahex')));
					$('#b2make-wo-progresso-barra1-cor-val').attr('data-ahex',$(obj).attr('data-barra1-color-ahex'));
				} else {
					$('#b2make-wo-progresso-barra1-cor-val').css('background-color','#cccccc');
					$('#b2make-wo-progresso-barra1-cor-val').attr('data-ahex','ccccccff');
				}
				
				if($(obj).attr('data-barra2-color-ahex')){
					$('#b2make-wo-progresso-barra2-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-barra2-color-ahex')));
					$('#b2make-wo-progresso-barra2-cor-val').attr('data-ahex',$(obj).attr('data-barra2-color-ahex'));
				} else {
					$('#b2make-wo-progresso-barra2-cor-val').css('background-color','#1e7fd0');
					$('#b2make-wo-progresso-barra2-cor-val').attr('data-ahex','1e7fd0ff');
				}
				
				if($(obj).attr('data-texto-color-ahex')){
					$('#b2make-wo-progresso-texto-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).attr('data-texto-color-ahex')));
					$('#b2make-wo-progresso-texto-cor-val').attr('data-ahex',$(obj).attr('data-texto-color-ahex'));
				} else {
					$('#b2make-wo-progresso-texto-cor-val').css('background-color','#58585B');
					$('#b2make-wo-progresso-texto-cor-val').attr('data-ahex','58585bff');
				}
				
				var types = new Array('porcentagem');
				
				for(var i=0;i<types.length;i++){
					var type = types[i];
					var tamanho;
					
					switch(type){
						case 'porcentagem': tamanho = 11; break;
					}
					
					if($(obj).attr('data-'+type+'-font-family')){
						$('#b2make-wo-progresso-'+type+'-text-cont').find('.b2make-fonts-holder').css({
							'fontFamily': $(obj).attr('data-'+type+'-font-family')
						});
						$('#b2make-wo-progresso-'+type+'-text-cont').find('.b2make-fonts-holder').html($(obj).attr('data-'+type+'-font-family'));
					} else {
						$('#b2make-wo-progresso-'+type+'-text-cont').find('.b2make-fonts-holder').css({
							'fontFamily': 'Roboto Condensed'
						});
						$('#b2make-wo-progresso-'+type+'-text-cont').find('.b2make-fonts-holder').html('Roboto Condensed');
					}
					
					if($(obj).attr('data-'+type+'-font-size')){
						$('#b2make-wo-progresso-'+type+'-text-cont').find('.b2make-fonts-size').val($(obj).attr('data-'+type+'-font-size'));
					} else {
						$('#b2make-wo-progresso-'+type+'-text-cont').find('.b2make-fonts-size').val(tamanho);
					}
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
}

var fn = window[_plugin_id];fn();