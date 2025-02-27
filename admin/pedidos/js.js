$(document).ready(function(){
	sep = "../../";
	
	$("#imprimir").bind('click touchstart',function(){
		window.open(sep+"includes/ecommerce/print.php","Imprimir","menubar=0,location=0,height=700,width=700");
	});
	
	$('textarea.tinymce').tinymce({
		height : "400",
		extended_valid_elements : "iframe[src|width|height|name|align|frameborder]",
		document_base_url: sep,
		relative_urls: false,
		convert_urls: false,
		// Location of TinyMCE script
		script_url : sep+'includes/js/tiny_mce/tiny_mce.js',
		
		// General options
		theme : "advanced",
		plugins : "pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,advlist",

		// Theme options
		theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,fontselect,fontsizeselect",
		theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
		theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
		theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true,

		// Example content CSS (should be your site CSS)
		content_css : sep+"includes/css/padrao.css",

		// Drop lists for link/image/media/template dialogs
		template_external_list_url : "lists/template_list.js",
		external_link_list_url : "lists/link_list.js",
		external_image_list_url : "lists/image_list.js",
		media_external_list_url : "lists/media_list.js",
		
		// Language
		language : 'pt',
		
		// Replace values for the template plugin
		template_replace_values : {
			username : "Some User",
			staffid : "991234"
		}
	});

	$("#form").submit(function() {
		return false;
	});
	
	$(".inteiro").numeric();
	
	$(".alterar-validade").bind('click touchstart',function(){
		$(this).parent().find('.caixa-validade').show();
		$(this).parent().find('.caixa-validade').find('.data').focus();
	});
	
	$(".botao-validade").bind('click touchstart',function(){
		var tempo_animacao = 150;
		var pai = $(this).parent();
		var id = pai.parent().find('.alterar-validade').attr('data-id');
		var data_pedido = pai.parent().find('.alterar-validade').attr('data-data');
		var validade = pai.parent().find('.validade-value');
		var data = pai.find('.data');
		
		pai.hide();
		
		if(data.val().length > 0 && data.val() != '__/__/____'){			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , validade : data.val() , data_pedido : data_pedido , id : id },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					
					
					if(txt.length > 0){
						alerta.html(txt);
						alerta.dialog("open");
					} else {
						validade.html(data.val());
					}
					data.val('');
				},
				error: function(txt){
					
				}
			});
		}
	});
	
	$(".data").mask("99/99/9999",{completed:function(){
		var data = this.val();
		var data_aux = data.split('/');
		var alerta = "Data inválida";
		var bissexto = false;
		var dia_str;
		var mes_str;
		var ano_str;
		var dia_aux = data_aux[0];
		var mes_aux = data_aux[1];
		
		if(dia_aux[0] == '0') dia_str = dia_aux[1]; else dia_str = dia_aux;
		if(mes_aux[0] == '0') mes_str = mes_aux[1]; else mes_str = mes_aux;
		ano_str = data_aux[2];
		
		var dia = parseInt(dia_str);
		var mes = parseInt(mes_str);
		var ano = parseInt(ano_str);
		
		if(mes > 12 || mes == 0){
			this.val('');
			alert(alerta);
			return false;
		}
		
		switch(mes){
			case 1:
			case 3:
			case 5:
			case 7:
			case 8:
			case 10:
			case 12:
				if(dia > 31){
					this.val('');
					alert(alerta);
					return false;
				}
			break;
			case 4:
			case 6:
			case 9:
			case 11:
				if(dia > 30){
					this.val('');
					alert(alerta);
					return false;
				}
			break;
			case 2:
				if(dia > 28){
					if(ano % 4 == 0){
						bissexto = true;
					}
					if(ano % 100 == 0){
						bissexto = false;
					}
					if(ano % 400 == 0){
						bissexto = true;
					}
					
					if(bissexto == true){
						if(dia > 29){
							this.val('');
							alert(alerta);
							return false;
						}
					} else {
						this.val('');
						alert(alerta);
						return false;
					}
				}
			break;
		}
		
		if(ano < 1875 || ano > 2200){
			this.val('');
			alert(alerta);
			return false;
		}
	}});

	$("input.data").keyup(function(e){
		var code = e.keyCode || e.which;
		if(code == 13) { //Enter keycode
			var tempo_animacao = 150;
			var pai = $(this).parent();
			var id = pai.parent().find('.alterar-validade').attr('data-id');
			var data_pedido = pai.parent().find('.alterar-validade').attr('data-data');
			var validade = pai.parent().find('.validade-value');
			var data = pai.find('.data');
			
			pai.hide();
			
			if(data.val().length > 0 && data.val() != '__/__/____'){			
				$.ajax({
					type: 'POST',
					url: '.',
					data: { ajax : 'sim' , validade : data.val() , data_pedido : data_pedido , id : id },
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						
						
						if(txt.length > 0){
							alerta.html(txt);
							alerta.dialog("open");
						} else {
							validade.html(data.val());
						}
						data.val('');
					},
					error: function(txt){
						
					}
				});
			}
		}
		
		if(code == 27) { //Enter keycode
			var pai = $(this).parent();
			
			pai.hide();
		}
	});
	
	$("#envio_id").bind('change',function(){
		var selected = $(this).find(':selected').val();
		
		$('#codigo_rastreio-cont').hide();
		
		switch(selected){
			// Entregue
			case 'F':
				mudar_envio(selected,'');
			break;
			// Enviado
			case 'E':
				status_enviado();
			break;
			// Não enviado
			case 'N':
				mudar_envio(selected,'');
			break;
			case 'M':
				mudar_envio(selected,'');
			break;
			
		}
	});
	
	function status_enviado(){
		$('#codigo_rastreio-cont').show();
	}
	
	$("#codigo_rastreio-btn").bind('click touchstart',function(){
		var codigo = $('#codigo_rastreio-txt').val();
		
		if(codigo.length > 0){
			mudar_envio('E',codigo);
			$('#codigo_rastreio-td').html(codigo);
			$('#codigo_rastreio-txt').val('');
			$('#codigo_rastreio-cont').hide();
		} else {
			alerta.html('<p>Defina o código de rastreio antes de enviar ou então escolha a opção <b>Sem Código</b></p>');
			alerta.dialog("open");
		}
	});
	
	$("#codigo_rastreio-btn2").bind('click touchstart',function(){
		mudar_envio('E','');
		$('#codigo_rastreio-cont').hide();
	});
	
	function mudar_envio(opcao,codigo){
		var id = $('#id').val();
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { ajax : 'sim' , mudar_envio : 'sim' , opcao : opcao , codigo : codigo , id : id },
			beforeSend: function(){
				$('#ajax_lendo').fadeIn(150);
			},
			success: function(txt){
				$('#ajax_lendo').fadeOut(150);
				
				if(txt){
					/* if($('#teste').length == 0){
						var teste = $('<div id="teste"></div>');
						
						teste.appendTo('#form');
					} else {
						var teste = $('#teste');
					}
					
					teste.html(txt); */
					alerta.html(txt);
					alerta.dialog("open");
				} else {
					switch(opcao){
						// Entregue
						case 'F':
							$('#envio-td').html('<b><span style="color:green;">Entregue</span></b>');
						break;
						// Enviado
						case 'E':
							$('#envio-td').html('<b><span style="color:blue;">Enviado</span></b>');
						break;
						// Não enviado
						case 'N':
							$('#envio-td').html('<b><span style="color:red;">Não enviado</span></b>');
						break;
						case 'M':
							$('#envio-td').html('<b><span style="color:green;">Retirado em Mãos</span></b>');
						break;
						
					}
				}
			},
			error: function(txt){
				
			}
		});
	}
});