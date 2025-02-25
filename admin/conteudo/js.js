var galeria_escolher = function(id){
	var tempo_animacao = 100;
	popup.dialog('close');
	
	$("#galeria_id").val(id);
	
	$.ajax({
		type: 'POST',
		url: url_name(),
		data: { ajax : 'sim' , galeria_nome : 'sim' , galeria_id : id },
		beforeSend: function(){
			$('#ajax_lendo').fadeIn(tempo_animacao);
		},
		success: function(txt){
			var dados = eval('(' + txt + ')');
			
			$('#ajax_lendo').fadeOut(tempo_animacao);
			
			$("#galeria").val(dados.nome);
			$("#galeria_imagens_cont").html(dados.galeria_imagens);
			
			setTimeout(function(){ $("a[rel^='prettyPhoto']").prettyPhoto({animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false}); }, 100);
		},
		error: function(txt){
			$('#ajax_lendo').fadeOut(tempo_animacao);
			$('#ajax_erro').fadeIn(tempo_animacao);
			setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
		}
	});
}

var galeria_grupo_escolher = function(id){
	var tempo_animacao = 100;
	popup.dialog('close');
	
	$("#galeria_grupo_id").val(id);
	
	$.ajax({
		type: 'POST',
		url: url_name(),
		data: { ajax : 'sim' , galeria_grupo_nome : 'sim' , galeria_grupo_id : id },
		beforeSend: function(){
			$('#ajax_lendo').fadeIn(tempo_animacao);
		},
		success: function(txt){
			var dados = eval('(' + txt + ')');
			
			$('#ajax_lendo').fadeOut(tempo_animacao);
			
			$("#galeria_grupo").val(dados.nome);
		},
		error: function(txt){
			$('#ajax_lendo').fadeOut(tempo_animacao);
			$('#ajax_erro').fadeIn(tempo_animacao);
			setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
		}
	});
}

var produto_escolher = function(id){
	var tempo_animacao = 100;
	popup.dialog('close');
	
	$("#produto").val(id);
	
	$.ajax({
		type: 'POST',
		url: url_name(),
		data: { ajax : 'sim' , produto_nome : 'sim' , produto : id },
		beforeSend: function(){
			$('#ajax_lendo').fadeIn(tempo_animacao);
		},
		success: function(txt){
			var dados = eval('(' + txt + ')');
			
			$('#ajax_lendo').fadeOut(tempo_animacao);
			$("#produto_txt").val(dados.nome);
		},
		error: function(txt){
			$('#ajax_lendo').fadeOut(tempo_animacao);
			$('#ajax_erro').fadeIn(tempo_animacao);
			setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
		}
	});
}

var servicos_escolher = function(id){
	var tempo_animacao = 100;
	popup.dialog('close');
	
	$("#servico").val(id);
	
	$.ajax({
		type: 'POST',
		url: url_name(),
		data: { ajax : 'sim' , servico_nome : 'sim' , servico : id },
		beforeSend: function(){
			$('#ajax_lendo').fadeIn(tempo_animacao);
		},
		success: function(txt){
			var dados = eval('(' + txt + ')');
			
			$('#ajax_lendo').fadeOut(tempo_animacao);
			$("#servico_txt").val(dados.nome);
		},
		error: function(txt){
			$('#ajax_lendo').fadeOut(tempo_animacao);
			$('#ajax_erro').fadeIn(tempo_animacao);
			setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
		}
	});
}

var videos_escolher = function(id){
	var tempo_animacao = 100;
	popup.dialog('close');
	
	$("#videos_id").val(id);
	
	$.ajax({
		type: 'POST',
		url: url_name(),
		data: { ajax : 'sim' , videos_nome : 'sim' , videos_id : id },
		beforeSend: function(){
			$('#ajax_lendo').fadeIn(tempo_animacao);
		},
		success: function(txt){
			var dados = eval('(' + txt + ')');
			
			$('#ajax_lendo').fadeOut(tempo_animacao);
			
			$("#videos").val(dados.nome);
			$("#galerias_videos_cont").html(dados.galerias_videos);
			
			setTimeout(function(){ $("a[rel^='prettyPhoto']").prettyPhoto({animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false}); }, 100);
		},
		error: function(txt){
			$('#ajax_lendo').fadeOut(tempo_animacao);
			$('#ajax_erro').fadeIn(tempo_animacao);
			setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
		}
	});
}

var videos_grupo_escolher = function(id){
	var tempo_animacao = 100;
	popup.dialog('close');
	
	$("#videos_grupo_id").val(id);
	
	$.ajax({
		type: 'POST',
		url: url_name(),
		data: { ajax : 'sim' , videos_grupo_nome : 'sim' , videos_grupo_id : id },
		beforeSend: function(){
			$('#ajax_lendo').fadeIn(tempo_animacao);
		},
		success: function(txt){
			var dados = eval('(' + txt + ')');
			
			$('#ajax_lendo').fadeOut(tempo_animacao);
			
			$("#videos_grupo").val(dados.nome);
		},
		error: function(txt){
			$('#ajax_lendo').fadeOut(tempo_animacao);
			$('#ajax_erro').fadeIn(tempo_animacao);
			setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
		}
	});
}

$(document).ready(function(){
	sep = "../../";
	var tempo_animacao = 100;
	
	tinymce.init({
		menubar: false,
		selector: 'textarea.tinymce-conteudo',
		toolbar: 'code | undo redo | styleselect | bold italic underline | link image | alignleft aligncenter alignright alignjustify resizeEditor | table',
		plugins: "image imagetools link table code",
		directionality: 'pt_BR',
		language_url: raiz+'includes/js/tinyMce/pt_BR.js',
		init_instance_callback: $.b2make_tinymce_change({ready:true}),
		branding: false,
		relative_urls : false,
		remove_script_host : false,
		convert_urls : true
	});
	
	//$('#menu').tabify();
	$('#tab-container').easytabs({animate:false});
	
	$("textarea[maxlength]").keypress(function(event){
		var key = event.which;
	 
		//todas as teclas incluindo enter
		if(key >= 33 || key == 13) {
			var maxLength = $(this).attr("maxlength");
			var length = this.value.length;
			if(length >= maxLength) {
				event.preventDefault();
			}
		}
	});
	
	if($("a[rel^='prettyPhoto']").length){
		setTimeout(function(){ $("a[rel^='prettyPhoto']").prettyPhoto({animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false}); }, 100);
	}
	
	$(".inteiro").numeric();
	$(".data").mask("99/99/9999");
	$(".hora").mask("99:99");
	
	$('.input_ordem').click(function(e){
		this.select();
	});
	
	$('.input_ordem').keyup(function(e){
		var code = (e.keyCode ? e.keyCode : e.which);
		var id = this.id;
		var id_a;
		var id_flag = false;
		
		if(code == 13){
			salvar_ordem();
		}
		
		if(
			(code >= 48 && code <= 57) ||
			(code >= 96 && code <= 105)
		){
			ids_unicos(this.value,this.id);
		}
		
		if(code == 38){
			$('.input_ordem').each(function(){
				var id_aux = this.id;
				
				if(id_aux == id){
					if(id_a){
						$('#'+id_a).focus();
						$('#'+id_a).select();
					} else {
						$('.input_ordem:last').focus();
						$('.input_ordem:last').select();
					}
				}
				
				id_a = id_aux;
			});
			
			ids_unicos(this.value,this.id);
		}
		
		if(code == 40){
			$('.input_ordem').each(function(){
				var id_aux = this.id;
				
				if(id_flag){
					$('#'+id_aux).focus();
					$('#'+id_aux).select();
					id_flag = false;
				}
				
				if(id_aux == id){
					id_flag = true;
				}
			});
			
			if(id_flag){
				$('.input_ordem:first').focus();
				$('.input_ordem:first').select();
			}
			
			ids_unicos(this.value,this.id);
		}
		
	});
	
	function ids_unicos(valor,id){
		var valor_aux = parseInt(valor);
		$('.input_ordem').each(function(){
			var val_aux = parseInt(this.value);
			if(valor_aux == val_aux && this.id != id && valor_aux > 0){
				this.value = val_aux + 1;
				ids_unicos(this.value,this.id);
			}
		});
	}
	
	$("#input_ordem_salvar").hover(
		function(){
			$(this).css('background-color', '#464E56');
			$(this).css('color', '#FFF');
		},
		function(){
			$(this).css('background-color', '#D7D9DD');
			$(this).css('color', '#58585B');
		}
	);
	
	$("#input_ordem_salvar").click(salvar_ordem);
	
	function salvar_ordem(){
		if(confirm('Tem certeza que deseja salvar a ordem atual?')){
			var id = '';
			
			$('.input_ordem').each(function(){
				var id_name = this.id;
				
				id_name = id_name.replace(/input_ordem_/gi,'');
				
				id = id + id_name + ',' + this.value + ';';
			});
			
			window.open(raiz+"admin/conteudo/?opcao=ordem&id="+id,"_self");
		}
	}
	
	$('.checkbox_label').click(function(e){
		if(e.target.nodeName != 'INPUT'){
			var input = $(this).find('td').find('input');
			
			if(input.attr('checked'))
				input.attr('checked', false);
			else
				input.attr('checked', true);
		}
	});
	
	$('.ordenacao').keydown(function(event){
		if(event.keyCode == '13'){
			var id = this.id.replace(/ordem/, '');
			var valor = this.value;
			var opcao = 'ordenacao';
			window.open(url_name()+"?opcao="+opcao+"&id="+id+"&valor="+valor,"_self");
		}
	});

	$("#form").submit(function() {
		
	});
	
	$("#form2").submit(function() {
		var enviar = true;
		var campo;
		var mens;
		
		campo = "titulo"; mens = "Preencha o TÌtulo"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
		
		campo = "data_automatica2"; if($("#"+campo).is(':checked')){ 
			campo = "data"; mens = "… obrigatÛrio definir a data!"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
			campo = "hora"; mens = "… obrigatÛrio definir a hora!"; if(!$("#"+campo).val()){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
			campo = "data"; mens = "Data inv·lida!"; if(!data_validar($("#"+campo).val())){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
			campo = "hora"; mens = "Hor·rio inv·lido!"; if(!hora_validar($("#"+campo).val())){ if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');} $("#"+campo).addClass('ui-state-error'); enviar = false; } else { $("#"+campo).removeClass('ui-state-error'); }
		}
		
		if(!enviar){
			return false;
		}
	});
	
	function hora_validar(hora){
		var horaArr = hora.split(':');
		var horaAux = parseInt(horaArr[0]);
		var minAux = parseInt(horaArr[1]);
		
		if(horaAux > 23){
			return false;
		} else if(minAux > 59){
			return false;
		} else {
			return true;
		}
	}
	
	function data_validar(data){
		var bissexto = 0;
		var dia = data.substr(0,2);
		var mes = data.substr(3,2);
		var ano = data.substr(6,4);
		
		if((ano > 1900)||(ano < 2100)){
			switch(mes){
				case '01':
				case '03':
				case '05':
				case '07':
				case '08':
				case '10':
				case '12':
					if(dia <= 31){
						return true;
					}
					break;
				case '04':              
				case '06':
				case '09':
				case '11':
					if(dia <= 30){
						return true;
					}
					break;
				case '02':
					/* Validando ano Bissexto / fevereiro / dia */ 
					if((ano % 4 == 0) || (ano % 100 == 0) || (ano % 400 == 0)){ 
						bissexto = 1; 
					}
					
					if((bissexto == 1) && (dia <= 29)){ 
						return true;                             
					}
					
					if((bissexto != 1) && (dia <= 28)){ 
						return true; 
					}                       
					break;                                          
			}
		}
		
		return false;
	}
	
	// ======================== Tags ==================================
	
	$("#tags-add").bind("click touchstart", function() {
		$("#tags-add-cont").toggle(150);
		$("#tags-add-texto").focus();
	});
	
	$("#tags-add-button").bind("click touchstart", add_tags);
	
	$("#tags-add-texto").bind("keypress", function(e){
		var code = (e.keyCode ? e.keyCode : e.which);
		if(code == 13) {
			add_tags();
			return false;
		}
	});
	
	$(document.body).on('change',".tags-checkbox",function(e){
		$("#tags-flag").val('s');
	});
	
	$(document.body).on('click touchstart',".tags-nome",function(e){
		if(e.target.nodeName != 'INPUT'){
			var input = $(this).parent().find('input');
			
			if(input.attr('checked'))
				input.attr('checked', false);
			else
				input.attr('checked', true);
				
			$("#tags-flag").val('s');
		}
	});
	
	$(document.body).on('click touchstart',".tags-excluir",function(){
		if(confirm("Tem certeza que deseja excluir esse item?")){
			var id = $(this).parent().attr('id');
			$(this).parent().remove();
			$("#tags-add-texto").focus();
			
			$.ajax({
				type: 'POST',
				url: url_name(),
				data: { ajax : 'sim' , del_tags : 'sim' , id : id},
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(tempo_animacao);
				},
				success: function(txt){
					var dados = eval('(' + txt + ')');
					$('#ajax_lendo').fadeOut(tempo_animacao);
				},
				error: function(txt){
					$('#ajax_lendo').fadeOut(tempo_animacao);
					$('#ajax_erro').fadeIn(tempo_animacao);
					setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
				}
			});
		}
	});
	
	function add_tags(){
		var text = $("#tags-add-texto").val();
		
		if(text.length > 0){
			if(!checkStr(text)){
				$.ajax({
					type: 'POST',
					url: url_name(),
					data: { ajax : 'sim' , add_tags : 'sim' , text : text},
					beforeSend: function(){
						$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						var dados = eval('(' + txt + ')');
						$('#ajax_lendo').fadeOut(tempo_animacao);
						
						if(dados.erro == 1){
							if(!alerta.dialog('isOpen')){alerta.html(dados.mens); alerta.dialog('open');}
							$("#tags-add-texto").focus();
						} else {
							var cont = $('<div id="tags'+dados.id+'" class="tags-conteiner-entry"></div>');
							var input = $('<input type="checkbox" value="1" name="tags'+dados.id+'" class="tags-checkbox" checked="checked" />');
							var nome = $('<div class="tags-nome">'+text+'</div>');
							var excluir = $('<div class="tags-excluir"></div>');
							var clear = $('<div class="clear"></div>');
							
							input.appendTo(cont);
							nome.appendTo(cont);
							excluir.appendTo(cont);
							clear.appendTo(cont);
							
							$("#tags-marcador").before(cont);
							$("#tags-add-texto").val('');
						}
					},
					error: function(txt){
						$('#ajax_lendo').fadeOut(tempo_animacao);
						$('#ajax_erro').fadeIn(tempo_animacao);
						setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
					}
				});
			} else {
				var mens = '<p>N„o È permitido caracteres especiais na formaÁ„o da Tag</p><p>Caracteres Permitidos: A atÈ Z, a atÈ z, 0 atÈ 9, _, - e .</p>';
				if(!alerta.dialog('isOpen')){alerta.html(mens); alerta.dialog('open');}
			}
		}
	}
	
	function checkStr(pass){
		var er = new RegExp(/[^A-Za-z0-9_.\sÈ˙ÌÛ·…⁄Õ”¡Ë˘ÏÚ‡»ŸÃ“¿ı„Ò’√—Í˚ÓÙ‚ €Œ‘¬Îˇ¸Ôˆ‰ÀY‹œ÷ƒ-]/);
		if(typeof(pass) == "string"){
			if(er.test(pass)){ return true; }
		}else if(typeof(pass) == "object"){
			if(er.test(pass.value)){
						return true;
					}
		}else{
			return false;
			}
	}
	
	// ======================== Galeria ==================================
	
	$("#galeria_escolher").bind("click touchstart", function() {
		popup.find('iframe').attr('src',raiz+'admin/galerias/popup.php');
		popup.dialog('option','title','Escolha a Galeria de Imagens');
		popup.dialog('open');
		popup_ativo = true;
	});
	
	$("#galeria_grupo_escolher").bind("click touchstart", function() {
		popup.find('iframe').attr('src',raiz+'admin/galerias/popup.php?opcao=grupos');
		popup.dialog('option','title','Escolha a Galeria de Imagens');
		popup.dialog('open');
		popup_ativo = true;
	});
	
	$("#videos_escolher").bind("click touchstart", function() {
		popup.find('iframe').attr('src',raiz+'admin/videos/popup.php');
		popup.dialog('option','title','Escolha a Galeria de VÌdeos');
		popup.dialog('open');
		popup_ativo = true;
	});
	
	$("#videos_grupo_escolher").bind("click touchstart", function() {
		popup.find('iframe').attr('src',raiz+'admin/videos/popup.php?opcao=grupos');
		popup.dialog('option','title','Escolha a Galeria de VÌdeos');
		popup.dialog('open');
		popup_ativo = true;
	});
	
	$("#servico_escolher").bind("click touchstart", function() {
		popup.find('iframe').attr('src',raiz+'admin/servicos/popup.php');
		popup.dialog('option','title','Escolha o ServiÁo');
		popup.dialog('open');
		popup_ativo = true;
	});
	
	$("#produto_escolher").bind("click touchstart", function() {
		popup.find('iframe').attr('src',raiz+'admin/produtos/popup.php');
		popup.dialog('option','title','Escolha o Produto');
		popup.dialog('open');
		popup_ativo = true;
	});
	
});