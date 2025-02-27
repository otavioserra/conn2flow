$(document).ready(function(){
	var modulo_versao = '1.0.0';
	sep = "../../";
	
	function debugar(txt){
		if($("#debugar").length <= 0){
			$("body").append('<div id="debugar" style="width:400px;height:500px;overflow:scroll;position:absolute;bottom:0px;right:0px;background-color:rgb(230,230,230);color:#000000;"></div>');
		}
		
		$("#debugar").prepend('<pre>'+txt+'</pre>');
	}
	
	$(".opcao").hover(
		function(){
			$(this).css('background-image', 'url(../../images/admin/box-cinza.png?v=1)');
		},
		function(){
			$(this).css('background-image', 'url(../../images/admin/box-branco.png?v=1)');
		}
	);
	
	$('textarea.tinymce').tinymce({
		height : "400",
		width : "760",
		extended_valid_elements : "iframe[src|width|height|name|align|frameborder]",
		document_base_url: raiz,
		relative_urls: false,
		convert_urls: false,
		// Location of TinyMCE script
		script_url : raiz+'includes/js/tiny_mce/tiny_mce.js',
		
		// General options
		theme : "advanced",
		plugins : "pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,advlist",
		plugin_insertdate_dateFormat : "%d/%m/%Y",
        plugin_insertdate_timeFormat : "%H:%M:%S",
		
		// Theme options
		theme_advanced_buttons1 : "undo,redo,|,cut,copy,paste,pastetext,pasteword,removeformat,|,formatselect,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,outdent,indent,blockquote",
		theme_advanced_buttons2 : "fullscreen,preview,code,print,visualaid,|,link,unlink,anchor,image,media,charmap,emotions,hr,advhr,|,insertdate,inserttime,sub,sup,|,tablecontrols",
		theme_advanced_buttons3 : "",
		theme_advanced_buttons4 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true,

		// Example content CSS (should be your site CSS)
		//content_css : raiz+"includes/css/padrao.css",

		// Drop lists for link/image/media/template dialogs
		template_external_list_url : "lists/template_list.js",
		external_link_list_url : "lists/link_list.js",
		external_image_list_url : "lists/image_list.js",
		media_external_list_url : "lists/media_list.js",
		file_browser_callback: filebrowser,
		
		// Language
		language : 'pt',
		
		// Replace values for the template plugin
		template_replace_values : {
			username : "Some User",
			staffid : "991234"
		}
	});
	
	function filebrowser(field_name, url, type, win) {
		fileBrowserURL = raiz+"includes/php/pdw_file_browser/index.php?filter=" + type;
		
		tinyMCE.activeEditor.windowManager.open({
			title: "PDW Explorador de Arquivos",
			url: fileBrowserURL,
			width: 950,
			height: 650,
			inline: 0,
			maximizable: 1,
			close_previous: 0
		},{
			window : win,
			input : field_name
		});
	}
	
	$('textarea.tinymce_mini').tinymce({
		height : "100",
		document_base_url: sep,
		relative_urls: false,
		convert_urls: false,
		// Location of TinyMCE script
		script_url : sep+'includes/js/tiny_mce/tiny_mce.js',

		// General options
		theme : "advanced",
		plugins : "paste,xhtmlxtras",
		
		// Theme options
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough",
		theme_advanced_buttons2 : "",
		theme_advanced_buttons3 : "",
		theme_advanced_buttons4 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true,

		// Example content CSS (should be your site CSS)
		content_css : sep+"includes/css/textos.css",

		// Drop lists for link/image/media/template dialogs
		template_external_list_url : "lists/template_list.js",
		external_link_list_url : "lists/link_list.js",
		external_image_list_url : "lists/image_list.js",
		media_external_list_url : "lists/media_list.js",

		// Replace values for the template plugin
		template_replace_values : {
			username : "Some User",
			staffid : "991234"
		},
		
		// Language
		
		language : "pt"
	});
	
	$("#form").submit(function() {
		if(!confirm("O envio do arquivo poderá levar alguns minutos dependendo da velocidade da sua conexão nos quais não poderá sair dessa tela até a confirmação do envio. Tem certeza que deseja continuar?")){return false;}
	});
	
	var params = new Array();
	
	$("#instalar").click(function() {
		$(this).attr("disabled", true);
		$("#instalacao-conteiner").append('<h2>Instalação Iniciada</h2>');
		iniciacao();
	});
	
	if(variaveis_js.atualizar){
		$("#instalar").attr("disabled", true);
		iniciacao();
	}
	
	var sub_passo = new Array();
	
	function update(){
		$.ajax({
			type: 'POST',
			url: 'robo.php',
			data: { passo : params['passo'], sub_passo: sub_passo[params['passo']] ? sub_passo[params['passo']] : '0' },
			beforeSend: function(){
				if($("#cont-update-"+params['passo']).length <= 0){
					sub_passo[params['passo']] = 1;
					$("#instalacao-conteiner").append('<div style="width:800px;float:letf;" class="lista_cel update_cel" id="cont-update-'+params['passo']+'">Executando '+params['passos'][params['passo']].titulo+'</div>');
					$("#cont-update-"+params['passo']).append('<div class="update_carregando" id="cont-update-'+params['passo']+'-carregando"></div>');
				} else {
					$("#cont-update-"+params['passo']).append('<div class="update_carregando" id="cont-update-'+params['passo']+'-carregando"></div><div style="clear:both;" id="cont-update-'+params['passo']+'-carregando-clear"></div>');
					sub_passo[params['passo']]++;
				}
			},
			success: function(txt){
				if(txt[0] != '{'){
					debugar(txt);
					debugar("<h1>Janela do Erro</h1>");
				}
				
				var dados = eval('(' + txt + ')');
				
				$("#cont-update-"+params['passo']+"-carregando").remove();
				if($("#cont-update-"+params['passo']+"-carregando-clear").length > 0)$("#cont-update-"+params['passo']+"-carregando-clear").remove();
				
				if($("#cont-mensagem-"+params['passo']).length <= 0){
					$("#cont-update-"+params['passo']).append('<div style="float:right;color:#093;" id="cont-mensagem-'+params['passo']+'"><div>'+dados.mensagem+'</div></div>');
					$("#cont-update-"+params['passo']).append('<div style="clear:both;"></div>');
				} else {
					$("#cont-mensagem-"+params['passo']).append('<div>'+dados.mensagem+'</div>');
				}
				
				params['passo'] = dados.passo;
				
				if(dados.atualizacao_update){
					$.jStorage.set("instalacao-conteiner", $("#instalacao-conteiner").html());
					window.open("index.php?opcao=atualizar","_self");
				} else {
					
					if(params['passo'] >= params['passos'].length){
						$("#instalacao-conteiner").append('<div style="width:800px;float:letf;color:#093;" class="lista_cel update_cel" id="cont-fim">Atualização Finalizada com Sucesso</div>');
						$("html, body").animate({ scrollTop: $(document).height() }, 0);
					} else {
						$("html, body").animate({ scrollTop: $(document).height() }, 0);
						if(!dados.erro)update();
					}
				}
			},
			error: function(txt){
				
			}
		});
	}
	
	function iniciacao(){
		$.ajax({
			type: 'POST',
			url: 'robo.php',
			data: { opcao : 'iniciacao' },
			beforeSend: function(){
				if(variaveis_js.atualizar){
					$("#instalacao-conteiner").append($.jStorage.get("instalacao-conteiner"));
					$.jStorage.deleteKey("instalacao-conteiner");
				} else {
					$("#instalacao-conteiner").append('<div style="width:800px;float:letf;" class="lista_cel update_cel" id="cont-iniciacao">Iniciando Variáveis</div>');
					$("#cont-iniciacao").append('<div class="update_carregando" id="cont-iniciacao-carregando"></div>');
				}
			},
			success: function(txt){
				var dados = eval('(' + txt + ')');
				
				params['passos'] = dados.passos;
				
				if(variaveis_js.atualizar){
					params['passo'] = 2;
				} else {
					params['passo'] = 0;
					$("#cont-iniciacao-carregando").remove();
					$("#cont-iniciacao").append('<div style="float:right;color:#093;">Ok</div>');
				}
				
				update();
			},
			error: function(txt){
				
			}
		});
	}
	
});