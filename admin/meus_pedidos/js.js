$(document).ready(function(){
	sep = "../../";
	
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
		var enviar = true;
		var campo;
		var mens;
		var mens_extra;
		var cor1 = "#FF5B5B";
		var cor2 = "#0C9";
		
		campo = "nome"; if(!$("#"+campo).val()){ $("#"+campo).css('background-color',cor1); enviar = false; } else { $("#"+campo).css('background-color',cor2); }
		
		if(!enviar){
			alerta.html("� obrigat�rio preencher os campos marcados em vermelho!" + ( mens_extra ? "\n\nNOTA: " + mens_extra : ""));
			alerta.dialog("open");
			return false;
		}
	});
	
	if($('#_voucher-cont').length > 0){
		$("#_voucher-imprimir").bind('click touchstart',function(){
			window.open(variaveis_js.site_raiz+"includes/ecommerce/print.php","Imprimir","menubar=0,location=0,height=700,width=700");
		});
		
		$("#_voucher-alterar-campos").bind('click touchstart',function(){
			$('#_voucher-cont').hide();
			$('#_voucher-form-presente').show();
		});
		
		$("#_voucher-visulizar").bind('click touchstart',function(){
			$('#_voucher-cont').show();
			$('#_voucher-form-presente').hide();
		});
		
		$("#_voucher-lista-pedidos").bind('change',function(){
			var id = $(this).val();

			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'voucher-pedidos' , id : id },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					$.link_trigger('voucher');
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		var presente_outro = 'Para Presente';
		var presente_voce = 'Para Voc�';
		var tempo_animacao = 150;
		
		$("#_voucher-presente").bind('click touchstart',function(){
			var flag = $(this).attr('data-flag');
			
			if(flag == '1'){
				$(this).attr('data-flag','2');
				$(this).val(presente_voce);
				$('#_voucher-cont').hide();
				$('#_voucher-form-presente').show();
			} else {
				$(this).attr('data-flag','1');
				$(this).val(presente_outro);
				$('#_voucher-cont').show();
				$('#_voucher-form-presente').hide();
			}
			
			voucher_mudar_campos();
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { ajax : 'sim' , opcao : 'voucher-presente' , flag : flag },
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					
					if(flag == '2'){
						//$.link_trigger('voucher');
					}
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
				}
			});
		});
		
		$("#_voucher-form-presente").bind('submit',function() {
			var enviar = true;
			var campo;
			var mens;
			
			campo = "_voucher-form-presente-de"; mens = "Preencha o campo De"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			campo = "_voucher-form-presente-para"; mens = "Preencha o campo Para"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			campo = "_voucher-form-presente-mensagem"; mens = "Preencha o campo Mensagem"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
			
			if(enviar){
				$('#_voucher-form-presente').attr('action','.');
				$('#_voucher-form-presente').attr('method','post');
			} else {
				return false;
			}
		});
		
		function voucher_mudar_campos(){
			var flag = $("#_voucher-presente").attr('data-flag');
			
			if(flag == '2'){
				$('#_voucher-lay-de').show();
				$('#_voucher-lay-para').show();
				$('#_voucher-lay-mens').show();
				$('#_voucher-alterar-campos').show();
			} else {
				$('#_voucher-lay-de').hide();
				$('#_voucher-lay-para').hide();
				$('#_voucher-lay-mens').hide();
				$('#_voucher-alterar-campos').hide();
			}
		}
		
		voucher_mudar_campos();
		
	}
	
	
	
});