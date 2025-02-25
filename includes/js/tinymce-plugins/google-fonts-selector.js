tinymce.PluginManager.add('googleFontsSelector', function(editor, url) {
	// Add a button that opens a window
	editor.addButton('googleFontsSelector', {
		text: 'Instalar Fonts',
		icon: false,
		onclick: function() {
			// Open window
			
			var fonts_collection = b2make.google_fonts_collection;
			var fonts_installed = b2make.google_fonts_installed;
			var fonts_installed_before = variaveis_js.google_fonts_installed;
			var body = new Array();
			
			for(var i=0;i<fonts_collection.length;i++){
				var checked = false;
				
				if(fonts_installed){
					for(var j=0;j<fonts_installed.length;j++){
						if(fonts_collection[i].family == fonts_installed[j].family){
							checked = true;
							break;
						}
					}
				} else if(fonts_installed_before){
					var fonts_arr = fonts_installed_before.split('|');
					
					for(var j=0;j<fonts_arr.length;j++){
						if(fonts_collection[i].family == fonts_arr[j].replace(/\+/gi,' ')){
							checked = true;
							break;
						}
					}
				}
				
				body.push({type: 'checkbox', name: 'font'+i, checked: checked, text: fonts_collection[i].family});
			}
			
			editor.windowManager.open({
				title: 'Instalar Fonts',
				minWidth: 300,
				maxHeight: 700,
				autoScroll: true,
				body: body,
				onsubmit: function(e) {
					// Insert content when the window form is submitted
					//editor.insertContent('Title: ' + e.data.title);
					
					var fonts = new Array();
					var count = 0;
					var max = 5;
					
					for(var l=0;l<fonts_collection.length;l++){
						if(e.data['font'+l]){
							fonts.push(fonts_collection[l]);
							count++;
						}
					}
					
					if(count > max){
						var msg = 'Voc&ecirc; instalou mais do que '+max+' fontes. N&atilde;o &eacute; recomend&aacute;vel instalar mais do que '+max+' fontes, uma vez que cada fonte tem um peso de 200 kb em m&eacute;dia e cada p&aacute;gina ser&aacute; lida com todas as fontes instaladas. Portanto, mais do que este valor pode ocasionar lentid&atilde;o na leitura.';
						
						if(!b2make.plataforma_nao_design){
							$.dialogbox_open({
								msg: msg
							});
						} else {
							alerta.html(msg);
							alerta.dialog('open');
						}
					}
					
					b2make.google_fonts_installed = fonts;
					
					$.b2make_tinymce_change_google_fonts();
				}
			});
		}
	});
});