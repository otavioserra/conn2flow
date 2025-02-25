var _plugin_id = 'codigo-html';
if(!b2make.plugin) b2make.plugin = {};
b2make.plugin[_plugin_id] = {};

function codigo_html_html_update(){
	var obj = b2make.conteiner_child_obj;
	
}

function codigo_html_start(plugin_id){
	if(b2make.plugin[plugin_id].widget_added){
		codigo_html_html_update();
	}
	
	b2make.plugin[plugin_id].started = true;
	
	$(document.body).on('keyup','#b2make-wo-codigo-html-textarea',function(e){
		var value = $(this).val();
		var id = $(this).attr('id');
		
		$.input_delay_to_change({
			trigger_selector:'#b2make-listener',
			trigger_event:'b2make-wo-codigo-html-textarea-change',
			value:value
		});
	});
	
	$(document.body).on('b2make-wo-codigo-html-textarea-change','#b2make-listener',function(e,value,p){
		if(!p) p = {};
		
		var obj = b2make.conteiner_child_obj;
		
		$(obj).find('.b2make-widget-out').find('.b2make-codigo-html').html(value);
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
			
			if(b2make.plugin[plugin_id].widget_added){
				$.widget_specific_options_open();
				$.widget_sub_options_open();
			}
			
			$('.b2make-tooltip').tooltip({
				show: {
					effect: "fade",
					delay: 400
				}
			});
			
			codigo_html_start(plugin_id);
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
			codigo_html_html_update();
		}
	});
	
	$('#b2make-listener').on('b2make-conteiner-child-open',function(e){
		switch(b2make.conteiner_child_type){
			case plugin_id:
				var obj = b2make.conteiner_child_obj;
				
				$('#b2make-wo-codigo-html-textarea').val($(obj).find('.b2make-widget-out').find('.b2make-codigo-html').html());
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