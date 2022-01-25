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
		
		var tabActive = localStorage.getItem(gestor.moduloId+'tabActive');
		
		if(tabActive !== null){
			$('.menu .item').tab('change tab', tabActive);
		}
		
		$('.ui.accordion').accordion();
		
		// ===== Backup Campo Mudar
		
		$('#gestor-listener').on('templatesBackupCampo',function(e,p){
			var campo = p.campo;
			var valor = p.valor;
			
			switch(campo){
				case 'html':
					CodeMirrorHtml.getDoc().setValue(valor);
					CodeMirrorHtml.refresh();
				break;
				case 'css':
					codeMirrorCss.getDoc().setValue(valor);
					codeMirrorCss.refresh();
				break;
			}
		});
	}
	
	if($('.optionsCont').length > 0){
		// ===== Estado dos conteiners baseado nas flags.
		
		if('optionsContShow' in gestor.templates){
			$('.optionsCont').removeClass('hidden-obj');
		}
		
		if($('.templateCont').length > 0){
			$('.listSelectorsCont').removeClass('hidden-obj');
		} else {
			$('.withoutResultsCont').removeClass('hidden-obj');
		}
		
		// ===== Dropdown da categoria.
		
		var modelo = gestor.templates.modelo;
		var padrao = '';
		
		if('padrao' in gestor.templates){
			padrao = '&padrao=sim';
		}
		
		$('.ui.dropdown')
			.dropdown({
				onChange : function(value, text){
					window.open(gestor.raiz + gestor.moduloId + '/seletores/?modelo='+modelo+padrao+'&categoria_id='+value,'_self');
				}
			})
		;
		
		// ===== Funções dos Botões
		
		$(document.body).on('mouseup tap','.templateSelect',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var sendMessage = function (msg) {
				// Make sure you are sending a string, and to stringify JSON
				window.parent.postMessage(msg, '*');
			};
			
			var messageParent = {
				moduloId : gestor.moduloId,
				moduloOpcao : gestor.moduloOpcao,
				data : $(this).attr('data-dados'),
			};
			
			sendMessage(JSON.stringify(messageParent));
		});
	}
	
});