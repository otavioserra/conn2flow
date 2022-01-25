$(document).ready(function(){
	
	if($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0){
		// ===== Codemirror 
		
		var codemirrors_instances = new Array();
		
		var codemirror_css = document.getElementsByClassName("codemirror-css");
		
		if(codemirror_css.length > 0){
			for(var i=0;i<codemirror_css.length;i++){
				var codeMirrorCss = CodeMirror.fromTextArea(codemirror_css[i],{
					lineNumbers: true,
					lineWrapping: true,
					styleActiveLine: true,
					matchBrackets: true,
					mode: "css",
					htmlMode: true,
					indentUnit: 4,
					theme: "tomorrow-night-bright",
					extraKeys: {
						"F11": function(cm) {
							cm.setOption("fullScreen", !cm.getOption("fullScreen"));
						},
						"Esc": function(cm) {
							if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
						}
					}
				});
				
				codeMirrorCss.setSize('100%', 500);
				codemirrors_instances.push(codeMirrorCss);
			}
		}
		
		var codemirror_html = document.getElementsByClassName("codemirror-html");
		
		if(codemirror_html.length > 0){
			for(var i=0;i<codemirror_html.length;i++){
				var CodeMirrorHtml = CodeMirror.fromTextArea(codemirror_html[i],{
					lineNumbers: true,
					lineWrapping: true,
					styleActiveLine: true,
					matchBrackets: true,
					mode: "htmlmixed",
					htmlMode: true,
					indentUnit: 4,
					theme: "tomorrow-night-bright",
					extraKeys: {
						"F11": function(cm) {
							cm.setOption("fullScreen", !cm.getOption("fullScreen"));
						},
						"Esc": function(cm) {
							if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
						}
					}
				});
				
				CodeMirrorHtml.setSize('100%', 500);
				codemirrors_instances.push(CodeMirrorHtml);
			}
		}
		
		// ===== Semantic UI
		
		var tabActive = localStorage.getItem(gestor.moduloId+'tabActive');
		
		if(tabActive !== null){
			$('.menu .item').tab('change tab', tabActive);
			
			switch(tabActive){
				case 'codigo-html':
					CodeMirrorHtml.refresh();
				break;
				case 'css':
					codeMirrorCss.refresh();
				break;
			}
		}
		
		
		$('.menu .item').tab({
			onLoad: function(tabPath, parameterArray, historyEvent){
				switch(tabPath){
					case 'codigo-html':
						CodeMirrorHtml.refresh();
					break;
					case 'css':
						codeMirrorCss.refresh();
					break;
				}
				
				localStorage.setItem(gestor.moduloId+'tabActive',tabPath);
			}
		});
		
		$('.ui.accordion').accordion();
		
		// ===== Backup Campo Mudar
		
		$('#gestor-listener').on('adminPaginasBackupCampo',function(e,p){
			var campo = p.campo;
			var valor = p.valor;
			
			switch(campo){
				case 'html':
					if(gestor.editorHtmlAtivo){
						if(codeHtmlChanged){
							valor = indentHtml(valor);
							
							CodeMirrorHtml.getDoc().setValue(valor);
							CodeMirrorHtml.refresh();
						} else {
							tinymce.activeEditor.setContent(valor,{format : 'raw'});
						}
					} else {
						CodeMirrorHtml.getDoc().setValue(valor);
						CodeMirrorHtml.refresh();
					}
				break;
				case 'css':
					codeMirrorCss.getDoc().setValue(valor);
					codeMirrorCss.refresh();
				break;
			}
		});
		
		// ===== Input delay
		
		$.input_delay_to_change = function(p){
			if(!gestor.input_delay){
				gestor.input_delay = new Array();
				gestor.input_delay_count = 0;
			}
			
			gestor.input_delay_count++;
			
			var valor = gestor.input_delay_count;
			
			gestor.input_delay.push(valor);
			gestor.input_value = p.value;
			
			setTimeout(function(){
				if(gestor.input_delay[gestor.input_delay.length - 1] == valor){
					input_change_after_delay({value:gestor.input_value,trigger_selector:p.trigger_selector,trigger_event:p.trigger_event});
				}
			},gestor.input_delay_timeout);
		}
		
		function input_change_after_delay(p){
			$(p.trigger_selector).trigger(p.trigger_event,[p.value,gestor.input_delay_params]);
			
			gestor.input_delay = false;
		}
		
		function input_delay(){
			if(!gestor.input_delay_timeout) gestor.input_delay_timeout = 800;
			
		}
		
		input_delay();
		
		// ===== Format caminho
		
		$(document.body).on('keyup blur','input[name="pagina-nome"]',function(e){
			var value = $(this).val();
			
			$.input_delay_to_change({
				trigger_selector:'#gestor-listener',
				trigger_event:'nome-change',
				value:value
			});
		});
		
		$(document.body).on('nome-change','#gestor-listener',function(e,value,p){
			if(!p) p = {};
			
			value = formatar_nome(value);
			
			if($('#_gestor-interface-edit-dados').length > 0){
				if($('input[name="paginaCaminho"]').val().length == 0) $('input[name="paginaCaminho"]').val(formatar_url(value));
			} else {
				$('input[name="paginaCaminho"]').val(formatar_url(value));
			}
		});
		
		$(document.body).on('blur','input[name="paginaCaminho"]',function(e){
			var value = $(this).val();
			
			$('input[name="paginaCaminho"]').val(formatar_url(value));
		});
		
		function formatar_url(url){
			url = url + '/';
			url = url.normalize("NFD").replace(/[\u0300-\u036f]/g, ""); // Trocar todos os caracteres com acentos pelos seus similares sem acento.
			url = url.replace(/[^a-zA-Z0-9 \-\/]/g,''); // Remover todos os caracteres que não são alfanuméricos ou espaço ou traço ou barra.
			url = url.toLowerCase(); // Passar para letras minúsculas
			url = url.trim(); // Remover espaço do início e fim.
			url = url.replace(/\s/g,'-'); // Trocar todos os espaços por traço.
			url = url.replace(/\-{2,}/g,'-'); // Remover a repetição de traços para um único traço.
			url = url.replace(/\/{2,}/g,'/'); // Remover a repetição de barras para uma única barra.
			
			return url;
		}
		
		function formatar_nome(nome){
			var string = nome;
			
			string = string.normalize("NFD").replace(/[\u0300-\u036f]/g, ""); // Trocar todos os caracteres com acentos pelos seus similares sem acento.
			string = string.replace(/[^a-zA-Z0-9 \-]/g,''); // Remover todos os caracteres que não são alfanuméricos ou espaço ou traço.
			string = string.toLowerCase(); // Passar para letras minúsculas
			string = string.trim(); // Remover espaço do início e fim.
			string = string.replace(/\s/g,'-'); // Trocar todos os espaços por traço.
			string = string.replace(/\-{2,}/g,'-'); // Remover a repetição de traços para um único traço.
			
			return string;
		}
	}
});