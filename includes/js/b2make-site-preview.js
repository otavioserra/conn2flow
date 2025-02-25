$(document).ready(function(){
	
	$.widgets_read_google_font = function(p){
		switch(p.tipo){
			case 1:
				if(p.obj.attr('data-font-family')){
					var font_family = p.obj.attr('data-font-family');
					var found = false;
					$('.b2make-fonts-list li').each(function(){
						if($(this).html() == font_family || '"'+$(this).html()+'"' == font_family){
							found = true;
						}
					});
					
					if(!found){
						$.google_fonts_wot_load({
							family : font_family,
							nao_carregamento : true
						});
					}
				}
			break;
			case 2:
				var types = p.types;
				
				for(var i=0;i<types.length;i++){
					var type = types[i];
					
					
					if(p.obj.attr('data-'+type+'-font-family')){
						var font_family = p.obj.attr('data-'+type+'-font-family');
						var found = false;
						$('.b2make-fonts-list li').each(function(){
							if($(this).html() == font_family || '"'+$(this).html()+'"' == font_family){
								found = true;
							}
						});
						
						if(!found){
							$.google_fonts_wot_load({
								family : font_family,
								nao_carregamento : true
							});
						}
					}
				}
			break;
		}
	}

	$.google_fonts_wot_load = function(p){
		var found = false;
		
		if(!b2make.google_fonts_loaded){
			b2make.google_fonts_loaded = new Array();
		}
		
		for(var i=0;i<b2make.google_fonts_loaded.length;i++){
			if(b2make.google_fonts_loaded[i] == p.family){
				found = true;
				break;
			}
		}
		
		if(!found){
			b2make.google_fonts_loaded.push(p.family);
			WebFont.load({
				google: {
					families: [p.family]
				},
				loading: function() {if(!p.nao_carregamento)$.carregamento_open();},
				active: function() {if(!p.nao_carregamento)$.carregamento_close();},
				inactive: function() {
					$.dialogbox_open({
						msg: b2make.msgs.googleFontsInative
					});
					
					if(!p.nao_carregamento)$.carregamento_close();
				},
				fontloading: function(familyName, fvd) {},
				fontactive: function(familyName, fvd) {},
				fontinactive: function(familyName, fvd) {}
			});
		}
	}

	$('.b2make-widget').each(function(){
		if($(this).attr('data-type') != 'conteiner-area'){
			switch($(this).attr('data-type')){
				case 'conteiner':
					$(this).css('width','100%');
					$(this).css('min-width','1000px');
				break;
				case 'iframe':
					$(this).find('.b2make-widget-out').html(decodeURIComponent($(this).attr('data-iframe-code')));
					$(this).attr('data-iframe-code',false);
				break;
				case 'albummusicas':
					$.widgets_read_google_font({
						tipo : 2,
						types : new Array('titulo','player','lista'),
						obj : $(this)
					});
				break;
				case 'texto':
					if($(this).attr('data-google-font') == 'sim'){
						$.google_fonts_wot_load({
							family : $(this).attr('data-font-family'),
							nao_carregamento : true
						});
					}
				break;
				case 'player':
					$.widgets_read_google_font({
						tipo : 1,
						obj : $(this)
					});
				break;
				case 'agenda':
					$.widgets_read_google_font({
						tipo : 2,
						types : new Array('dia','mes','titulo','cidade'),
						obj : $(this)
					});
				break;
				case 'menu':
					$.widgets_read_google_font({
						tipo : 1,
						obj : $(this).find('.b2make-widget-out').find('.b2make-widget-menu')
					});
				break;
				case 'albumfotos':
					$.widgets_read_google_font({
						tipo : 1,
						obj : $(this)
					});
				break;
				case 'download':
					$.widgets_read_google_font({
						tipo : 1,
						obj : $(this)
					});
				break;
			}
		}		
	});
});