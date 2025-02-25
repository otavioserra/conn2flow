if(window.location.protocol == "http:" && window.location.hostname != 'localhost'){
	var restOfUrl = window.location.href.substr(5);
	window.location = "https:" + restOfUrl;
}

b2makeAdmin.stop_enter_preventDefaults = true;

$(document).ready(function(){
	sep = "../../";
	
	$('input[type="checkbox"]').on('mouseup tap',function(e){
		e.stopPropagation();
	});
	
	$('.tags-cont').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		var checkBoxes = $(this).find('input');
        checkBoxes.prop("checked", !checkBoxes.prop("checked"));
		
		if(checkBoxes.prop("checked")){
			$(this).attr('data-checked','sim');
		} else {
			$(this).removeAttr('data-checked');
		}
		
		$(this).find('input').trigger('change');
	});
	
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
		
		$("#"+campo).css('background-color',cor2);
	});
	
	$(".servicos_escolher").bind("click touchstart", function() {
		parent.servicos_escolher($(this).attr('servicos'));
	});
	
	// ===================== Conteúdos =======================
	
	$(window).bind('mouseup tap',function(e){
		if(b2make.conteudo_campos.form){
			var id = $(e.target).attr('id');
			
			if(
				!b2make.conteudo_campos.form_alerta &&
				id != 'add-field' &&
				id != 'add-campos-form' &&
				id != 'add-campos-nome' &&
				id != 'add-campos-button' 
			){
				$('#add-campos-form').hide();
				b2make.conteudo_campos.form = false;
			}
			
		}
		
		if(b2make.conteudo_campos.form_edit){
			var editar = false;
			
			if($(e.target).hasClass('campos-btns')){
				if($(e.target).attr('data-opcao') == 'campo-editar'){
					editar = true;
				}
			}
			
			if(
				!b2make.conteudo_campos.form_alerta &&
				!editar &&
				!$(e.target).hasClass('edit-campos-form') &&
				!$(e.target).hasClass('edit-campos-nome') &&
				!$(e.target).hasClass('edit-campos-button') 
			){
				$('.edit-campos-form').hide();
				b2make.conteudo_campos.form_edit = false;
			}
			
		}
		
	});
	
	function conteudo_campos_pagina_mestre_atualizar(p){
		if(!p)p={};
		
		var opcao = 'pagina-mestre-atualizar';
		
		var html = conteudo_campos_pagina_mestre_html({
			acao:p.acao,
			marcador:p.marcador,
			marcador_antigo:p.marcador_antigo,
			widget:p.widget
		});
		var html_mobile = conteudo_campos_pagina_mestre_html({
			acao:p.acao,
			marcador:p.marcador,
			marcador_antigo:p.marcador_antigo,
			widget:p.widget,
			mobile : true
		});
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : opcao,
				html : html,
				html_mobile : html_mobile,
				conteudo_tipo : $('#conteudo-tipo').val()
			},
			beforeSend: function(){
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							window.open(window.location.href,'_self');
						break;
						default:
							console.log('ERROR - '+opcao+' - '+dados.status);
					}
				} else {
					console.log('ERROR - '+opcao+' - '+txt);
				}
				
				$.loading_close();
			},
			error: function(txt){
				console.log('ERROR AJAX - '+opcao+' - '+txt);
				$.loading_close();
			}
		});
	}
	
	function conteudo_campos_pagina_mestre_html(p){
		if(!p)p={};
		
		var widget,pagina_mestre,campo,conteiner_area;
		
		switch(p.acao){
			case 'add':
				if(p.mobile){
					pagina_mestre = $('#pagina-mestre-conteudo-tipo-mobile-cont');
					
					campo = $('#campos-htmls-cont').find('#mobile-area-id-'+p.widget);
					
					campo.attr('id','area-'+p.marcador);
					campo.attr('data-id-original','area-'+p.marcador);
					
					widget = campo.find('#mobile-widget-id-'+p.widget);
					
					widget.attr('id',p.widget+'-'+p.marcador);
					widget.attr('data-id-original',p.widget+'-'+p.marcador);
					widget.attr('data-marcador','@'+p.marcador+'#');
				} else {
					pagina_mestre = $('#pagina-mestre-conteudo-tipo-cont');
					
					campo = $('#campos-htmls-cont').find('#area-id-'+p.widget);
					
					campo.attr('id','area-'+p.marcador);
					campo.attr('data-id-original','area-'+p.marcador);
					campo.attr('data-area','conteiner-area-'+p.marcador);
					
					conteiner_area = campo.find('#conteiner-area-id-'+p.widget);
					conteiner_area.attr('id','conteiner-area-'+p.marcador);
					conteiner_area.attr('data-id-original','conteiner-area-'+p.marcador);
					
					widget = conteiner_area.find('#widget-id-'+p.widget);
					
					widget.attr('id',p.widget+'-'+p.marcador);
					widget.attr('data-id-original',p.widget+'-'+p.marcador);
					widget.attr('data-marcador','@'+p.marcador+'#');
				}
				
				campo.appendTo(pagina_mestre);
			break;
			case 'edit':
				if(p.mobile){
					pagina_mestre = $('#pagina-mestre-conteudo-tipo-mobile-cont');
					
					campo = $('[data-id-original="area-'+p.marcador_antigo+'"]');
					if(campo){
						campo.attr('data-id-original',p.marcador);
					}
					
					widget = $('[data-id-original="'+p.widget+'-'+p.marcador_antigo);
					if(widget){
						widget.attr('data-id-original',p.widget+'-'+p.marcador);
						widget.attr('data-marcador','@'+p.marcador+'#');
					}
				} else {
					pagina_mestre = $('#pagina-mestre-conteudo-tipo-cont');
					
					campo = $('[data-id-original="area-'+p.marcador_antigo+'"]');
					if(campo){
						campo.attr('data-id-original','area-'+p.marcador);
						campo.attr('data-area','conteiner-area-'+p.marcador);
					}
					
					conteiner_area = $('[data-id-original="conteiner-area-'+p.marcador_antigo+'"]');
					if(conteiner_area){
						conteiner_area.attr('data-id-original','conteiner-area-'+p.marcador);
					}
					
					widget = $('[data-id-original="'+p.widget+'-'+p.marcador_antigo+'"]');
					if(widget){
						widget.attr('data-id-original',p.widget+'-'+p.marcador);
						widget.attr('data-marcador','@'+p.marcador+'#');
					}
				}
			break;
			case 'del':
				if(p.mobile){
					pagina_mestre = $('#pagina-mestre-conteudo-tipo-mobile-cont');
				} else {
					pagina_mestre = $('#pagina-mestre-conteudo-tipo-cont');
				}
				
				campo = $('[data-id-original="area-'+p.marcador+'"]');
				if(campo){
					campo.remove();
				}
				
				widget = $('[data-id-original="'+p.widget+'-'+p.marcador+'"]');
				if(widget){
					widget.remove();
				}
			break;
			
		}
		
		return pagina_mestre.html();
	}
	
	function conteudo_campos_enviar(){
		var enviar = false;
		var valor = $('#add-campos-nome').val();
		var mens_erro = '';
		var minimo_caracteres = 3;
		
		b2make.conteudo_campos.form_alerta = false;
		
		if(valor.length > minimo_caracteres){
			enviar = true;
		} else {
			mens_erro = 'Defina o campo com no mínimo '+minimo_caracteres+' caracteres!';
		}
		
		if(!enviar){
			alerta.html(mens_erro);
			alerta.dialog("open");
			b2make.conteudo_campos.form_alerta = true;
			return false;
		} else {
			$('#add-campos-form').hide();
			b2make.conteudo_campos.form = false;
			
			var opcao = 'campos-add';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					nome : valor,
					campo_tipo : $('#conteudo-campos').val(),
					conteudo_tipo : $('#conteudo-tipo').val()
				},
				beforeSend: function(){
					$.loading_open();
				},
				success: function(txt){
					if(
						txt.charAt(0) == "{" || 
						txt.charAt(0) == "["
					){
						var dados = eval('(' + txt + ')');
						
						switch(dados.status){
							case 'Ok':
								conteudo_campos_pagina_mestre_atualizar({marcador:dados.marcador,widget:dados.widget,acao:'add'});
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
								$.loading_close();
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
						$.loading_close();
					}
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
					$.loading_close();
				}
			});
		}
	}
	
	function conteudo_campos_editar(){
		var enviar = false;
		var valor = b2make.conteudo_campos.form_edit_campo_nome.val();
		var mens_erro = '';
		var minimo_caracteres = 3;
		var id = b2make.conteudo_campos.form_edit_id;
		
		b2make.conteudo_campos.form_alerta = false;
		
		if(valor.length > minimo_caracteres){
			enviar = true;
		} else {
			mens_erro = 'Defina o campo com no mínimo '+minimo_caracteres+' caracteres!';
		}
		
		if(!enviar){
			alerta.html(mens_erro);
			alerta.dialog("open");
			b2make.conteudo_campos.form_alerta = true;
			return false;
		} else {
			$('#edit-campos-form').hide();
			b2make.conteudo_campos.form_edit = false;
			
			var opcao = 'campos-edit';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					nome : valor,
					id : id,
					conteudo_tipo : $('#conteudo-tipo').val()
				},
				beforeSend: function(){
					$.loading_open();
				},
				success: function(txt){
					if(
						txt.charAt(0) == "{" || 
						txt.charAt(0) == "["
					){
						var dados = eval('(' + txt + ')');
						
						switch(dados.status){
							case 'Ok':
								conteudo_campos_pagina_mestre_atualizar({marcador_antigo:dados.marcador_antigo,marcador:dados.marcador,widget:dados.widget,acao:'edit'});
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
					
					$.loading_close();
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
					$.loading_close();
				}
			});
		}
	}
	
	function conteudo_campos_excluir(){
		var id = b2make.conteudo_campos.form_excluir_id;
		var opcao = 'campos-excluir';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : opcao,
				id : id,
				conteudo_tipo : $('#conteudo-tipo').val()
			},
			beforeSend: function(){
				$.loading_open();
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							conteudo_campos_pagina_mestre_atualizar({marcador:dados.marcador,acao:'del',widget:dados.widget});
						break;
						default:
							console.log('ERROR - '+opcao+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+opcao+' - '+txt);
				}
				
				$.loading_close();
			},
			error: function(txt){
				console.log('ERROR AJAX - '+opcao+' - '+txt);
				$.loading_close();
			}
		});
	}
	
	function conteudo_campos_padrao_excluir(){
		var id = b2make.conteudo_campos.form_padrao_excluir_id;
		var opcao = 'campos-padrao-excluir';
		
		$.ajax({
			type: 'POST',
			url: '.',
			data: { 
				ajax : 'sim',
				opcao : opcao,
				id : id,
				conteudo_tipo : $('#conteudo-tipo').val()
			},
			beforeSend: function(){
				$.loading_open();
			},
			success: function(txt){
				if(
					txt.charAt(0) == "{" || 
					txt.charAt(0) == "["
				){
					var dados = eval('(' + txt + ')');
					
					switch(dados.status){
						case 'Ok':
							window.open(window.location.href,'_self');
						break;
						default:
							console.log('ERROR - '+opcao+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+opcao+' - '+txt);
				}
				
				$.loading_close();
			},
			error: function(txt){
				console.log('ERROR AJAX - '+opcao+' - '+txt);
				$.loading_close();
			}
		});
	}
	
	function conteudo_campos(){
		b2make.conteudo_campos = {};
		
		var val = $('#conteudo-tipo').val();
		
		if(val == '-1'){
			$('.campos-btns-2').hide();
		}
		
		$(document.body).on('keydown','#add-campos-nome',function (e) {
			if(e.keyCode == 13){ // enter
				conteudo_campos_enviar();
				e.preventDefault();
			}
			
			if(e.keyCode == 27){ // ESC
				$('#add-campos-form').hide();
				b2make.conteudo_campos.form = false;
			}
		});
		
		$('#add-field').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var top = $(this).position().top;
			
			$('#add-campos-form').css('top',top);
			$('#add-campos-form').show();
			$('#add-campos-nome').focus();
			$('#add-campos-nome').select();
			b2make.conteudo_campos.form = true;
		});
		
		$('#add-campos-button').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			e.stopPropagation();
			conteudo_campos_enviar();
		});
		
		$(document.body).on('keydown','.edit-campos-nome',function (e) {
			if(e.keyCode == 13){ // enter
				conteudo_campos_editar();
				e.preventDefault();
			}
			
			if(e.keyCode == 27){ // ESC
				$('.edit-campos-form').hide();
				b2make.conteudo_campos.form_edit = false;
			}
		});
		
		$('.campos-btns[data-opcao="campo-editar"]').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$('.edit-campos-form').hide();
			
			var id = $(this).attr('data-id');
			var pai = $(this).parent().parent();
			var valor = pai.find('.campo-nome-cont').html();
			
			pai.find('.edit-campos-form').show();
			pai.find('.edit-campos-nome').focus();
			pai.find('.edit-campos-nome').val(valor);
			pai.find('.edit-campos-nome').select();
			b2make.conteudo_campos.form_edit = true;
			b2make.conteudo_campos.form_edit_id = id;
			b2make.conteudo_campos.form_edit_campo_nome = pai.find('.edit-campos-nome');
		});
		
		$('.edit-campos-button').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			e.stopPropagation();
			conteudo_campos_editar();
		});
		
		$('.campos-btns[data-opcao="campo-excluir"]').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			b2make.conteudo_campos.form_excluir_id = $(this).attr('data-id');
			
			if(confirm('Tem certeza que realmente deseja deletar este campo? Essa operação não poderá ser revertida.')){
				conteudo_campos_excluir();
			}
		});
		
		$('.campos-btns-2[data-opcao="campo-excluir"]').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			b2make.conteudo_campos.form_padrao_excluir_id = $(this).attr('data-id');
			
			if(confirm('Tem certeza que realmente deseja deletar este campo? Essa operação não poderá ser revertida.')){
				conteudo_campos_padrao_excluir();
			}
		});
		
		$('.campo-texto-complexo').each(function(){
			var id = $(this).attr('id');
			
			b2make.plataforma_nao_design = true;
			$.b2make_tinymce_start({selector:'#'+id});
		});
		
		if(variaveis_js.b2make_texto_complexo_ativo){
			var opcao = 'get webfonts';
			
			$.ajax({
				type: 'POST',
				url: raiz+'design/webfonts/webfonts.js',
				beforeSend: function(){
				},
				success: function(txt){
					if(
						txt.charAt(0) == "{" || 
						txt.charAt(0) == "["
					){
						var dados = eval('(' + txt + ')');
						
						b2make.google_fonts_collection = dados;
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
				}
			});
		}
	}
	
	conteudo_campos();
	
	// ===================== Uploads =======================
	
	$.statusbox_upload_dialbox_close = function(){
		var uploads_queueds = b2make.uploads_queueds;
		var pelo_menos_um = false;
		
		for(var i=0;i<uploads_queueds.length;i++){
			if(uploads_queueds[i]){
				pelo_menos_um = true;
			}
		}
		
		if(!pelo_menos_um){
			statusbox_close();
		} else {
			setTimeout($.statusbox_upload_dialbox_close,1000);
		}
	}
	
	$.statusbox_open = function(p){
		if(!b2make.statusbox){
			if(!p)p = {};
			b2make.statusbox = true;
			
			$("#b2make-statusbox").animate({bottom:0}, b2make.statusboxAnimateTime);
		}
	}
	
	function statusbox_close(){
		if(b2make.statusbox){
			b2make.statusbox = false;
			
			$("#b2make-statusbox").animate({bottom:-($('#b2make-statusbox').height() + 10)}, b2make.statusboxAnimateTime);
		}
	}
	
	function statusbox_remove_item_uploaded(id){
		setTimeout(function(){
			$('#b2make-statusbox-log li#'+id).fadeOut(b2make.statusboxAnimateTime);
		},b2make.statusboxRemoveItemUploadedTimeout);
	}
	
	function statusbox(){
		if(!b2make.statusboxAnimateTime)b2make.statusboxAnimateTime = 250;
		if(!b2make.statusboxRemoveItemUploadedTimeout)b2make.statusboxRemoveItemUploadedTimeout = 1000;
		
		b2make.uploads_queueds_num = 0;
		b2make.uploads_queueds = new Array();
		b2make.upload_clicked = new Array();
		
		var height = $('#b2make-statusbox').height() + 10;
		$('#b2make-statusbox').css('bottom',-height);
		$('#b2make-statusbox').show();
	}
	
	statusbox();
	
	$.upload_files_start = function(p = {}){
		var url = p.url_php;
		var input = p.input_selector;
		var file_type = p.file_type;
		var uploads_queueds_num = b2make.uploads_queueds_num;
		var max_files = 0;
		var dropZone = (typeof p.dropZone !== typeof undefined && p.dropZone !== false ? $(p.dropZone) : null);
		
		var acceptFileTypes = undefined;
		
		switch(file_type){
			case 'imagem': acceptFileTypes = /\.(gif|jpg|jpeg|png)$/i ; acceptFileAlert = 'S&oacute; s&atilde;o aceitos arquivos do tipo imagem (gif|jpg|jpeg|png).' ; break;
			case 'audio': acceptFileTypes = /\.(mp3)$/i ; acceptFileAlert = 'S&oacute; s&atilde;o aceitos arquivos do tipo &aacute;udio (mp3).' ; break;
		}
		
		$(input).fileupload({
			url: url,
			dropZone: dropZone,
			autoUpload: true,
			dataType: 'json',
		}).prop('disabled', !$.support.fileInput)
		.parent().addClass($.support.fileInput ? undefined : 'disabled');
		
		$(input).bind('fileuploadadd', function (e, data){
			var goUpload = true;
			var uploadFile = data.files[0];
			
			if(acceptFileTypes)
			if(!(acceptFileTypes).test(uploadFile.name)){
				$.dialogbox_open({
					msg: acceptFileAlert
				});
				goUpload = false;
			}
			
			if(goUpload){
				b2make.uploadFiles.ids++;
				var id = b2make.uploadFiles.ids;
				
				max_files++;
				
				var listitem='<li id="'+id+'">'+
					data.files[0].name+' ('+Math.round(data.files[0].size/1024)+' KB)'+
					'<div class="progressbar" ><div class="progress" style="width:0%"></div></div>'+
					'<span class="status" >Aguardando</span><span class="progressvalue" ></span>'+
					'</li>';
				$('#b2make-statusbox-log').append(listitem);
				
				b2make.uploads_queueds[uploads_queueds_num] = true;
				$.statusbox_open(false);
				setTimeout($.statusbox_upload_dialbox_close,1000);
				
				if (data.autoUpload || (data.autoUpload !== false && $(this).fileupload('option', 'autoUpload'))){
					data.process().done(function () {
						data.submit();
					});
				}
			}
		});
		
		$(input).bind('fileuploadsubmit', function (e, data){
			var id = b2make.uploadFiles.ids;
			var input_id = $(this).attr('id');
			
			data.formData = {
				id_upload: id,
				input_id: input_id,
				name: data.files[0].name,
				lastModified: data.files[0].lastModified,
				'user':variaveis_js.library_user,
				'session_id':variaveis_js.library_id
			};
			
			$(this).parent().parent().find('.uploads-dropzone-options').hide();
			
			if(p.post_params){
				var postVars = p.post_params();
				
				for(var i=0;i<postVars.length;i++){
					data.formData[postVars[i].variavel] = postVars[i].valor;
				}
			}
		});
		
		$(input).bind('fileuploadsend', function (e, data){
			var id = data.formData.id_upload;
			var status_log = $('#b2make-statusbox-log');
			
			$('#b2make-statusbox-log li#'+id).find('span.status').text('Enviando...');
			$('#b2make-statusbox-log li#'+id).find('span.progressvalue').text('0%');
		});
		
		$(input).bind('fileuploadprogress', function (e, data){
			var id = data.formData.id_upload;
			
			if(id){
				var progress = parseInt(data.loaded / data.total * 100, 10);
				
				$('#b2make-statusbox-log li#'+id).find('div.progress').css('width', progress+'%');
				$('#b2make-statusbox-log li#'+id).find('span.progressvalue').text(progress+'%');
				$('#b2make-statusbox-log').scrollTop(
					$('#b2make-statusbox-log li#'+id).offset().top - $('#b2make-statusbox-log').offset().top + $('#b2make-statusbox-log').scrollTop()
				);
				
				if(progress >= 100){
					$('#b2make-statusbox-log li#'+id).find('span.status').html('Processando...');
					$('#b2make-statusbox-log li#'+id).find('span.progressvalue').text('');
				}
			}
		});
		
		$(input).bind('fileuploaddone', function (e, data){
			var dados = data.result;
			var id = dados.id_upload;
			
			var item=$('#b2make-statusbox-log li#'+id);
			item.find('div.progress').css('width', '100%');
			item.find('span.progressvalue').text('');
			item.addClass('success').find('span.status').html('Terminou!!!');
			
			if(p.callback)p.callback(dados);
			
			max_files--;
			
			if(max_files <= 0){
				max_files = 0;
				b2make.uploads_queueds[uploads_queueds_num] = false;
			}
		});
		
		b2make.uploads_queueds_num++;
	}
	
	function upload_files_callback(dados){
		var id_func = 'upload_files';
		
		switch(dados.status){
			case 'Ok':
				var obj = $('#'+dados.input_id).parent().parent();
				
				obj.removeClass('uploads-without-img');
				obj.addClass('uploads-with-img');
				
				obj.find('.uploads-dropzone-options').hide();
				obj.find('.uploads-dropzone-dropinfo').hide();
				
				obj.css('backgroundImage','url('+dados.file_url_tmp+')');
				
				obj.parent().find('.uploads-input-change').val(dados.file_name_tmp);
				obj.parent().find('.uploads-input-data').val(encodeURIComponent(JSON.stringify(dados)));
			break;
			default:
				console.log('ERROR - '+id_func+' - '+dados.status);
			
		}
	}
	
	function upload_files(){
		b2make.uploadFiles = {};
		
		b2make.uploadFiles.ids = 0;
		
		$('.uploads').each(function(){
			var id = $(this).attr('data-id');
			var img = $(this).attr('data-img');
			
			$(this).html($('#uploads-cont').html());
			
			$(this).find('.uploads-cont').find('.uploads-dropzone').prop('id',id+'-dropzone');
			$(this).find('.uploads-cont').find('.uploads-btn').find('input').prop('id',id+'-input');
			
			$(this).find('.uploads-cont').find('.uploads-dropzone-options').show();
			
			$.upload_files_start({
				url_php : 'uploadfile.php',
				input_selector : '#'+id+'-input',
				dropZone : '#'+id+'-dropzone',
				file_type : 'imagem',
				callback : upload_files_callback
			});
			
			if(img){
				var obj = $(this).find('.uploads-cont');
				
				obj.removeClass('uploads-without-img');
				obj.addClass('uploads-with-img');
				
				obj.find('.uploads-dropzone-options').hide();
				
				obj.css('backgroundImage','url('+img+')');
			}
			
			$(this).find('.uploads-input-change').prop('name',id+'-input-change');
			$(this).find('.uploads-input-del').prop('name',id+'-input-del');
			$(this).find('.uploads-input-data').prop('name',id+'-input-data');
		});
		
		$('.uploads-dropzone').on('dragenter',function(e){
			var obj = $(this).parent();
			
			if(obj.hasClass('uploads-with-img')){
				obj.attr('data-img',obj.css('backgroundImage'));
				obj.css('backgroundImage','none');
				obj.removeClass('uploads-with-img');
				obj.addClass('uploads-without-img');
			}
			
			obj.find('.uploads-dropzone-options').hide();
			obj.find('.uploads-dropzone-dropinfo').show();
			obj.addClass('uploads-cont-hover');
		});
		
		$('.uploads-dropzone').on('dragleave',function(e){
			var obj = $(this).parent();
			
			if(typeof obj.attr('data-img') !== typeof undefined && obj.attr('data-img') !== false){
				obj.css('backgroundImage',obj.attr('data-img'));
				obj.addClass('uploads-with-img');
				obj.removeClass('uploads-without-img');
			} else {
				obj.find('.uploads-dropzone-options').show();
			}
			
			obj.find('.uploads-dropzone-dropinfo').hide();
			obj.removeClass('uploads-cont-hover');
		});
		
		$('.uploads-dropzone').on('drop',function(e){
			$(this).parent().removeClass('uploads-cont-hover');
			$(this).parent().find('.uploads-dropzone-dropinfo').hide();
		});
		
		$('.uploads-dropzone').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$(this).parent().find('.uploads-btn').find('input').click();
		});
		
		$('.uploads-change-btn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$(this).parent().parent().find('.uploads-btn').find('input').click();
		});
		
		$('.uploads-del-btn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var obj = $(this).parent().parent();
			
			obj.find('.uploads-input-del').val('sim');
			obj.find('.uploads-cont').css('backgroundImage','');
			obj.find('.uploads-cont').addClass('uploads-without-img');
			obj.find('.uploads-cont').removeClass('uploads-with-img');
			obj.find('.uploads-cont').find('.uploads-dropzone-options').show();
		});
	}
	
	upload_files();
	
	// ===================== Tags Conteúdos =======================
	
	if($('#tags-principal-sel option').length > 0){
		$('#tags-principal-cont').show();
	}
	
	$('.tags-chk').on('change',function(e){
		var count = 0;
		var checked = $(this).prop('checked');
		var value = $(this).prop('value');
		var txt = $(this).parent().find('span').html();
		
		$('.tags-chk').each(function(){
			if($(this).prop('checked'))	count++;
		});
		
		if(count > 0){
			$('#tags-principal-cont').show();
		} else {
			$('#tags-principal-cont').hide();
		}
		
		if(checked){
			$('#tags-principal-sel').append('<option value="'+value+'">'+txt+'</option>');
		} else {
			$('#tags-principal-sel').find('option[value="'+value+'"]').remove();
		}
	});
	
	function tags(){
		$('#tags-add-btn').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			if($('#tags-add-input').val().length >= 3){
				var nome = $('#tags-add-input').val();
				var raiz = ($('#tags-tipo').val() != '-1'? '&raiz='+$('#tags-tipo').val() : '');
				var callback = encodeURIComponent(window.location.href);
				
				window.open('tags/?opcao=add_base&callback='+callback+raiz+'&nome='+nome+'&cor=000000','_self');
			} else {
				alerta.html('É necessário definir o nome da tag com pelo menos 3 caracteres!');
				alerta.dialog("open");
			}
		});
	}
	
	tags();
	
	// ===================== Tipos de Conteúdos =======================
	
	$(window).bind('mouseup touchend',function(e){
		if(b2make.conteudo_tipos.form){
			var id = $(e.target).attr('id');
			
			if(
				!b2make.conteudo_tipos.form_alerta &&
				id != 'tipo-edit' &&
				id != 'tipo-add' &&
				id != 'tipo-form' &&
				id != 'tipo-nome' &&
				id != 'tipo-button' 
			){
				$('#tipo-form').hide();
				b2make.conteudo_tipos.form = false;
			}
			
		}
	});
	
	function conteudo_tipos_enviar(){
		var enviar = false;
		var valor = $('#tipo-nome').val();
		var mens_erro = '';
		var minimo_caracteres = 3;
		
		b2make.conteudo_tipos.form_alerta = false;
		
		if(valor.length > minimo_caracteres){
			enviar = true;
		} else {
			mens_erro = 'Defina o campo com no mínimo '+minimo_caracteres+' caracteres!';
		}
		
		if(!enviar){
			alerta.html(mens_erro);
			alerta.dialog("open");
			b2make.conteudo_tipos.form_alerta = true;
			return false;
		} else {
			$('#tipo-form').hide();
			b2make.conteudo_tipos.form = false;
			
			var opcao = 'tipo-add';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					opcao_atual : variaveis_js.opcao_atual,
					nome : valor,
					id : $('#conteudo-tipo').val(),
					id_conteudo : $('#id').val(),
					acao : b2make.conteudo_tipos.acao
				},
				beforeSend: function(){
					$.loading_open();
				},
				success: function(txt){
					if(
						txt.charAt(0) == "{" || 
						txt.charAt(0) == "["
					){
						var dados = eval('(' + txt + ')');
						
						switch(dados.status){
							case 'Ok':
								window.open(window.location.href,'_self');
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
					
					$.loading_close();
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
					$.loading_close();
				}
			});
		}
	}
	
	function conteudo_tipos(){
		b2make.conteudo_tipos = {};
		
		var val = $('#conteudo-tipo').val();
		
		if(val == '-1'){
			$('#tipo-edit').hide();
		}
		
		$('#tipo-add,#tipo-edit').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$('#tipo-nome').val('');
			
			if($(this).attr('id') == 'tipo-edit'){
				b2make.conteudo_tipos.acao = 'edit';
				var nome = '';
				
				$('#layout-mestre-select').find('.b2make-componentes-select-option').each(function(){
					if($('#conteudo-tipo').val() == $(this).attr('data-value')){
						nome = $(this).html();
						return true;
					}
				});
				
				$('#tipo-nome').val(nome);
			} else {
				b2make.conteudo_tipos.acao = 'add';
				$('#tipo-form').css('top',$('#tipo-add').position().top + 5);
			}
			
			$('#tipo-form').show();
			$('#tipo-nome').focus();
			$('#tipo-nome').select();
			b2make.conteudo_tipos.form = true;
		});
		
		$('#tipo-fechar').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$('#tipo-form').hide();
			b2make.conteudo_tipos.form = false;
			e.stopPropagation();
		});
		
		$('#tipo-button').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			e.stopPropagation();
			conteudo_tipos_enviar();
		});
		
		$(document.body).on('keydown','#tipo-nome',function (e) {
			if(e.keyCode == 13){ // enter
				conteudo_tipos_enviar();
				e.preventDefault();
			}
			
			if(e.keyCode == 27){ // ESC
				$('#tipo-form').hide();
				b2make.conteudo_tipos.form = false;
			}
		});
		
		$('#b2make-admin-listener').on('dialog-ui-close',function(e){
			b2make.conteudo_tipos.form_alerta = false;
		});
		
		$('#conteudo-tipo').on('change',function(e){
			var val = $(this).val();
			
			var opcao = 'tipo-mudar';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					opcao_atual : variaveis_js.opcao_atual,
					id_conteudo : $('#id').val(),
					id_site_conteudos_tipos : $('#conteudo-tipo').val()
				},
				beforeSend: function(){
					$.loading_open();
				},
				success: function(txt){
					if(
						txt.charAt(0) == "{" || 
						txt.charAt(0) == "["
					){
						var dados = eval('(' + txt + ')');
						
						switch(dados.status){
							case 'Ok':
								window.open(window.location.href,'_self');
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
					
					$.loading_close();
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
					$.loading_close();
				}
			});
		});
	}
	
	conteudo_tipos();
});