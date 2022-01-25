$(document).ready(function(){
	
	if($('#_gestor-interface-edit-dados').length > 0 || $('#_gestor-interface-insert-dados').length > 0){
		// ===== TinyMCE
		
		var editorHtmlAtivo = true;
		
		if(editorHtmlAtivo){
			tinymce.init({
				menubar: false,
				selector: 'textarea.tinymce',
				toolbar: 'undo redo | styleselect | bold italic underline | link image | alignleft aligncenter alignright alignjustify | fontselect fontsizeselect',
				plugins: "image imagetools link",
				directionality: 'pt_BR',
				min_height: 450,
				language: 'pt_BR',
				language_url: gestor.raiz+'tinymce/langs/pt_BR.js',
				font_formats: 'Verdana=Verdana;Arial=arial,helvetica,sans-serif;',
				branding: false,
				valid_elements: '*[*]',
				init_instance_callback: tinyMCEReady
			});
		}
		
		function tinyMCEReady(){
			
		}
		
		// ===== Format caminho
		
		$(document.body).on('keyup blur','input[name="nome"]',function(e){
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