$(document).ready(function(){
	sep = "../../";
	
	$(".float").maskMoney({showSymbol:false,decimal:",",thousands:".",precision:2});
	$(".inteiro").numeric();
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

	$("#form").submit(function() {
		var enviar = true;
		var campo;
		var mens;
		var mens_extra;
		var cor1 = "#FF5B5B";
		var cor2 = "#0C9";
		
		campo = "nome"; if(!$("#"+campo).val()){ $("#"+campo).css('background-color',cor1); enviar = false; } else { $("#"+campo).css('background-color',cor2); }
		
		if(!enviar){
			alerta.html("É obrigatório preencher os campos marcados em vermelho!" + ( mens_extra ? "\n\nNOTA: " + mens_extra : ""));
			alerta.dialog("open");
			return false;
		}
		
		campo = "desconto"; 
		if($("#"+campo).val()){ 
			if( parseInt($("#"+campo).val()) > 100 ){
				alerta.html("O campo de desconto pode ter um valor no máximo de 100%!" + ( mens_extra ? "\n\nNOTA: " + mens_extra : ""));
				alerta.dialog("open");
				$("#"+campo).css('background-color',cor1);
				return false;
			} else {
				$("#"+campo).css('background-color',cor2);
			}
		} else {
			$("#"+campo).css('background-color',cor2);
		}
	});
	
	$(".produto_escolher").bind("click touchstart", function() {
		parent.produto_escolher($(this).attr('produto'));
	});
	
	$('#swfupload-control').swfupload({
		upload_url: "uploadimg.php",
		file_post_name: 'uploadfile',
		file_size_limit : "10000",
		file_types : "*.jpg;*.png;*.gif",
		file_types_description : "Image files",
		file_upload_limit : 100,
		flash_url : sep+"includes/js/swfupload/swfupload/swfupload.swf",
		button_image_url : sep+'includes/js/swfupload/swfupload/wdp_buttons_upload_114x29.png',
		button_width : 114,
		button_height : 29,
		button_placeholder : $('#swfupload-button')[0],
		debug: false,
		post_params: {
			'id':$("#id").val(),
			'usuario':$("#usuario").val(),
			'sessao':$("#sessao").val(),
		}
	})
	.bind('fileQueued', function(event, file){
		var listitem='<li id="'+file.id+'" >'+
			'Arquivo: <em>'+file.name+'</em> ('+Math.round(file.size/1024)+' KB) <span class="progressvalue" ></span>'+
			'<div class="progressbar" ><div class="progress" ></div></div>'+
			'<p class="status" >Aguardando</p>'+
			'<span class="cancel" >&nbsp;</span>'+
			'</li>';
		$('#log').append(listitem);
		$('li#'+file.id+' .cancel').bind('click', function(){
			var swfu = $.swfupload.getInstance('#swfupload-control');
			swfu.cancelUpload(file.id);
			$('li#'+file.id).slideUp('fast');
		});
		// start the upload since it's queued
		$(this).swfupload('startUpload');
	})
	.bind('fileQueueError', function(event, file, errorCode, message){
		alert('O tamanho do arquivo '+file.name+' é maior que o limite máximo');
	})
	.bind('fileDialogComplete', function(event, numFilesSelected, numFilesQueued){
		$('#queuestatus').text('Arquivos selecionados: '+numFilesSelected+' / Arquivos na Fila: '+numFilesQueued);
	})
	.bind('uploadStart', function(event, file){
		$('#log li#'+file.id).find('p.status').text('Enviando...');
		$('#log li#'+file.id).find('span.progressvalue').text('0%');
		$('#log li#'+file.id).find('span.cancel').hide();
	})
	.bind('uploadProgress', function(event, file, bytesLoaded){
		//Show Progress
		var percentage=Math.round((bytesLoaded/file.size)*100);
		$('#log li#'+file.id).find('div.progress').css('width', percentage+'%');
		$('#log li#'+file.id).find('span.progressvalue').text(percentage+'%');
	})
	.bind('uploadSuccess', function(event, file, serverData){
		var item=$('#log li#'+file.id);
		item.find('div.progress').css('width', '100%');
		item.find('span.progressvalue').text('100%');
		var pathtofile= /*'<a href="uploads/'+file.name+'" target="_blank" >view &raquo;</a>'*/'';
		item.addClass('success').find('p.status').html('Terminou!!!'+pathtofile);
		
		var imgPath = serverData.split(',');
		
		if(imgPath.length > 1){
			var img = $('<div class="galeria-foto"><div class="galeria-excluir-mask"><div class="galeria-excluir-foto" data-img-grande="'+imgPath[0]+'" data-img-pequena="'+imgPath[1]+'" data-img-mini="'+imgPath[2]+'"></div></div></div>');
			
			img.css('backgroundImage','url('+imgPath[2]+')');
			
			img.appendTo('#galeria-cont');
		}
	})
	.bind('uploadComplete', function(event, file){
		// upload has completed, try the next one in the queue
		$(this).swfupload('startUpload');
	});
	
	$(document.body).on("click touchstart",".galeria-excluir-foto", function() {
		var img_grande = $(this).attr('data-img-grande');
		var img_pequena = $(this).attr('data-img-pequena');
		var img_mini = $(this).attr('data-img-mini');
		var id = $('#id').val();
		var objeto = $(this);
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim' ,
				exluir_foto : 'sim' ,
				img_grande:img_grande ,
				img_pequena:img_pequena ,
				img_mini:img_mini ,
				id:id ,
				usuario: $("#usuario").val() ,
				sessao: $("#sessao").val()
			},
			beforeSend: function(){
			},
			success: function(txt){
				var arr = txt.split(',');
				if(arr.length > 1){
					if(arr[0] == 'ok' && arr[1] == 'ok2' && arr[2] == 'ok3'){
						objeto.parent().parent().remove();
					}
				}
			},
			error: function(txt){
				//
			}
		});
	});
	
	// ====================================== Categorias =====================================
	
	var categoria_id_global;
	var categoria_objeto;
	var categoria_selecionada;
	var categoria_txt_modo = 'categoria_add';
	var categoria_seta = $('<div id="categoria-selecionada-seta"></div>');
	
	if($("div#categorias-lista ul li.categoria-selecionada").length > 0){
		$("div#categorias-lista ul li.categoria-selecionada").append(categoria_seta);
		categoria_selecionada = true;
	}
	
	$("#categoria-add").bind("click touchstart", function() {
		categoria_txt_modo = 'categoria_add';
		$("#categoria-add-txt").val('');
		$("#categoria-add-cont").appendTo($(this).parent());
		$("#categoria-add-cont").show();
		$("#categoria-add-txt").focus();
		categoria_id_global = null;
	});
	
	$("#categoria-add-btn").bind("click touchstart", function() {
		categoria_add_editar();
	});
	
	$("#categoria-add-txt").keydown(function(e){
		if(e.keyCode == 13){
			categoria_add_editar();
			e.preventDefault();
			return false;
		}
		
		if(e.keyCode == 27){
			$("#categoria-add-cont").hide();
		}
	});
	
	$("div#categorias-lista ul li").mouseover(function(e) {
		$(this).addClass('barra1');
		$(this).css('color', '#FFF');
		$(this).find('.categorias-controles').last().show();
		e.stopPropagation();
	}).mouseout(function(e) {
		$(this).removeClass('barra1');
		$(this).css('color', '#58585B');
		$(this).find('.categorias-controles').last().hide();
	});
	
	$(document.body).on("click touchstart","div#categorias-lista ul li", function(e) {
		$("div#categorias-lista ul li").removeClass('categoria-selecionada');
		categoria_seta.appendTo($(this));
		
		if($('#id_categorias_produtos').val() && $('#id_categorias_produtos').val() == $(this).attr('data-id') && categoria_selecionada){
			$('#id_categorias_produtos').val('');
			categoria_selecionada = false;
			categoria_seta.hide();
		} else {
			$(this).addClass('categoria-selecionada');
			$('#id_categorias_produtos').val($(this).attr('data-id'));
			categoria_seta.show();
			categoria_selecionada = true;
		}
		e.stopPropagation();
	});
	
	$(document.body).on("click touchstart",".categorias-add", function(e) {
		categoria_txt_modo = 'categoria_add';
		$("#categoria-add-txt").val('');
		$("#categoria-add-cont").appendTo($(this).parent().parent());
		$("#categoria-add-cont").show();
		$("#categoria-add-txt").focus();
		categoria_id_global = $(this).parent().parent().attr('data-id');
		categoria_objeto = $(this).parent().parent();
		e.stopPropagation();
	});
	
	$(document.body).on("click touchstart",".categorias-editar", function(e) {
		categoria_txt_modo = 'categoria_editar';
		$("#categoria-add-txt").val($(this).parent().parent().find('.categoria-nome').html());
		$("#categoria-add-cont").appendTo($(this).parent().parent());
		$("#categoria-add-cont").show();
		$("#categoria-add-txt").focus();
		categoria_id_global = $(this).parent().parent().attr('data-id');
		categoria_objeto = $(this).parent().parent();
		e.stopPropagation();
	});

	$(document.body).on("click touchstart",".categorias-excluir", function(e) {
		categoria_id_global = $(this).parent().parent().attr('data-id');
		categoria_objeto = $(this).parent().parent();
		e.stopPropagation();
		
		if(confirm('Deseja realmente excluir essa categoria?')){
			categoria_excluir();
		}
	});

	function categoria_add_editar(){
		var categoria = $("#categoria-add-txt").val();
		var categoria_id;
		
		if(categoria_id_global){
			categoria_id = categoria_id_global;
			categoria_id_global = null;
		} else {
			categoria_id = '';
		}
		
		if(categoria){
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : categoria_txt_modo,
					categoria_id:categoria_id,
					categoria:categoria
				},
				beforeSend: function(){
					$('#ajax_lendo').fadeIn(150);
				},
				success: function(txt){
					$('#ajax_lendo').fadeOut(150);
					
					switch(categoria_txt_modo){
						case 'categoria_add':
							var li = $('<li data-id="'+txt+'"><div class="categoria-nome">'+categoria+'</div><div class="categorias-controles"><div class="categorias-add"></div><div class="categorias-editar"></div><div class="categorias-excluir"></div></div></li>');
					
							if(categoria_id){
								if($('#categoria-pai-'+categoria_id).length == 0){
									var ul = $('<ul id="categoria-pai-'+categoria_id+'"></ul>');
									categoria_objeto.find('.categoria-nome').after(ul);
								}
								
								li.appendTo('#categoria-pai-'+categoria_id);
							} else {
								if($('#categoria-principal').length == 0){
									var ul = $('<div id="categorias-lista"><ul id="categoria-principal"></ul></div>');
									$('#categorias-produtos-txt').append(ul);
								}
								
								li.appendTo('#categoria-principal');
							}
							
							$("div#categorias-lista ul li").mouseover(function(e) {
								$(this).addClass('barra1');
								$(this).css('color', '#FFF');
								$(this).find('.categorias-controles').last().show();
								e.stopPropagation();
							}).mouseout(function(e) {
								$(this).removeClass('barra1');
								$(this).css('color', '#58585B');
								$(this).find('.categorias-controles').last().hide();
							});
						break;
						case 'categoria_editar':
							categoria_objeto.find('.categoria-nome').first().html(categoria);
						break;
					}
				},
				error: function(txt){
					//
				}
			});
		}
		
		$("#categoria-add-cont").hide();
	}
	
	function categoria_excluir(){
		var categoria_id;
		
		if(categoria_id_global){
			categoria_id = categoria_id_global;
			categoria_id_global = null;
		} else {
			categoria_id = '';
		}
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : 'categoria_excluir',
				categoria_id:categoria_id
			},
			beforeSend: function(){
				$('#ajax_lendo').fadeIn(150);
			},
			success: function(txt){
				$('#ajax_lendo').fadeOut(150);
				
				categoria_objeto.remove();
			},
			error: function(txt){
				//
			}
		});
	}
	
});