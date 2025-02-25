var _plugin_id = 'plugin_id';
if(!b2make.plugin) b2make.plugin = {};
b2make.plugin[_plugin_id] = {};

function plugin_func_html_update(){
	var obj = b2make.conteiner_child_obj;
	
}

function plugin_func_start(plugin_id){
	if(b2make.plugin[plugin_id].widget_added){
		plugin_func_html_update();
	}
	
	b2make.plugin[plugin_id].started = true;
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
			
			plugin_func_start(plugin_id);
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
			plugin_func_html_update();
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