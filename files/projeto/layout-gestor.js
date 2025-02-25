var b2make_gestor = {};

var tempo_animacao = 300;

$(document).ready(function(){
	$('._menu_principal').appendTo('#b2make-menu');
	$('#lay_topo_2').appendTo('#b2make-menu');
	
	function dark_mode_reload_page(){
		window.open(window.location,'_self');
	}
	
	function dark_mode_change_server(p = {}){
		var opcao = 'dark-mode-change';
		var mode = p.mode;
		
		b2make_gestor.dark_mode.communicating_server = true;
		
		$.ajax({
			type: 'POST',
			url: variaveis_js.site_raiz + 'dashboard/.',
			data: { 
				ajax : 'sim',
				opcao : opcao,
				mode : mode
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
							b2make_gestor.dark_mode.communicating_server = false;
							
							if(!b2make_gestor.dark_mode.animating){
								dark_mode_reload_page();
							}
						break;
						default:
							console.log('ERROR - '+opcao+' - '+dados.status);
						
					}
				} else {
					console.log('ERROR - '+opcao+' - '+txt);
				}
			},
			error: function(txt){
				console.log('ERROR AJAX - '+opcao+' - '+txt);
			}
		});
	}
	
	function dark_mode(){
		b2make_gestor.dark_mode = {};
		
		b2make_gestor.dark_mode.animation_time = 300;
		
		if(variaveis_js.dark_mode){
			b2make_gestor.dark_mode.active = true;
		}
		
		$('#b2make-gestor-dark-mode-circle').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			if(!b2make_gestor.dark_mode.active){
				b2make_gestor.dark_mode.animating = true;
				$(this).animate({left:21},b2make_gestor.dark_mode.animation_time,'swing',function(){
					b2make_gestor.dark_mode.animating = false;
					
					if(!b2make_gestor.dark_mode.communicating_server){
						dark_mode_reload_page();
					}
				});
				dark_mode_change_server({mode:'dark'});
			} else {
				b2make_gestor.dark_mode.animating = true;
				$(this).animate({left:2},b2make_gestor.dark_mode.animation_time,'swing',function(){
					b2make_gestor.dark_mode.animating = false;
					
					if(!b2make_gestor.dark_mode.communicating_server){
						dark_mode_reload_page();
					}
				});
				dark_mode_change_server({mode:'light'});
			}
		});
	}
	
	dark_mode();
	
	function perfil(){
		$('#b2make-gestor-account-snapshot').off('mouseup touchstart');
		$('#b2make-gestor-account-snapshot').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			//localStorage.setItem('b2make.mudar_foto_perfil','1');
			window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'management/my-profile/','_self');
		});
		
		$('#b2make-gestor-account-user').html('C&oacute;digo: '+b2make_menu.disk_usage.user);
	}
	
	perfil();
	
	// ============================= Funções Padrões ========================
	
	$.input_delay_to_change = function(p){
		if(!b2make_gestor.input_delay){
			b2make_gestor.input_delay = new Array();
			b2make_gestor.input_delay_count = 0;
		}
		
		b2make_gestor.input_delay_count++;
		
		var valor = b2make_gestor.input_delay_count;
		
		b2make_gestor.input_delay.push(valor);
		b2make_gestor.input_value = p.value;
		
		setTimeout(function(){
			if(b2make_gestor.input_delay[b2make_gestor.input_delay.length - 1] == valor){
				input_change_after_delay({value:b2make_gestor.input_value,trigger_selector:p.trigger_selector,trigger_event:p.trigger_event});
			}
		},b2make_gestor.input_delay_timeout);
	}
	
	function input_change_after_delay(p){
		$(p.trigger_selector).trigger(p.trigger_event,[p.value,b2make_gestor.input_delay_params]);
		
		b2make_gestor.input_delay = false;
	}
	
	function input_delay(){
		if(!b2make_gestor.input_delay_timeout) b2make_gestor.input_delay_timeout = 400;
		
	}
	
	input_delay();
	
	// ============================= B2make Statusbox ========================
	
	$.statusbox_upload_dialbox_close = function(){
		var uploads_queueds = b2make_gestor.uploads_queueds;
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
		if(!b2make_gestor.statusbox){
			if(!p)p = {};
			b2make_gestor.statusbox = true;
			
			$("#b2make-statusbox").animate({bottom:0}, b2make_gestor.statusboxAnimateTime);
		}
	}
	
	function statusbox_close(){
		if(b2make_gestor.statusbox){
			b2make_gestor.statusbox = false;
			
			$("#b2make-statusbox").animate({bottom:-($('#b2make-statusbox').height() + 10)}, b2make_gestor.statusboxAnimateTime);
		}
	}
	
	function statusbox_remove_item_uploaded(id){
		setTimeout(function(){
			$('#b2make-statusbox-log li#'+id).fadeOut(b2make_gestor.statusboxAnimateTime);
		},b2make_gestor.statusboxRemoveItemUploadedTimeout);
	}
	
	function statusbox(){
		if(!b2make_gestor.statusboxAnimateTime)b2make_gestor.statusboxAnimateTime = 250;
		if(!b2make_gestor.statusboxRemoveItemUploadedTimeout)b2make_gestor.statusboxRemoveItemUploadedTimeout = 1000;
		
		b2make_gestor.uploads_queueds_num = 0;
		b2make_gestor.uploads_queueds = new Array();
		b2make_gestor.upload_clicked = new Array();
		
		var height = $('#b2make-statusbox').height() + 10;
		$('#b2make-statusbox').css('bottom',-height);
	}
	
	statusbox();
	
	// ============================= B2make FileUpload ========================
	
	$.upload_files_start = function(p = {}){
		var url = p.url_php;
		var input = p.input_selector;
		var file_type = p.file_type;
		var uploads_queueds_num = b2make_gestor.uploads_queueds_num;
		var max_files = 0;
		
		var acceptFileTypes = undefined;
		
		switch(file_type){
			case 'imagem': acceptFileTypes = /\.(gif|jpg|jpeg|png)$/i ; acceptFileAlert = 'S&oacute; s&atilde;o aceitos arquivos do tipo imagem (gif|jpg|jpeg|png).' ; break;
			case 'audio': acceptFileTypes = /\.(mp3)$/i ; acceptFileAlert = 'S&oacute; s&atilde;o aceitos arquivos do tipo &aacute;udio (mp3).' ; break;
		}
		
		$(input).fileupload({
			url: url,
			dropZone: null,
			autoUpload: true,
			dataType: 'json',
		}).prop('disabled', !$.support.fileInput)
		.parent().addClass($.support.fileInput ? undefined : 'disabled');
		
		$(input).bind('fileuploadadd', function (e, data){
			$.upload_files_mask_close();
			
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
				b2make_gestor.uploadFiles.ids++;
				var id = b2make_gestor.uploadFiles.ids;
				
				max_files++;
				
				var listitem='<li id="'+id+'">'+
					data.files[0].name+' ('+Math.round(data.files[0].size/1024)+' KB)'+
					'<div class="progressbar" ><div class="progress" style="width:0%"></div></div>'+
					'<span class="status" >Aguardando</span><span class="progressvalue" ></span>'+
					'</li>';
				$('#b2make-statusbox-log').append(listitem);
				
				b2make_gestor.uploads_queueds[uploads_queueds_num] = true;
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
			var id = b2make_gestor.uploadFiles.ids;
			
			data.formData = {
				id_upload: id,
				name: data.files[0].name,
				lastModified: data.files[0].lastModified,
				'user':variaveis_js.library_user,
				'session_id':variaveis_js.library_id
			};
			
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
				b2make_gestor.uploads_queueds[uploads_queueds_num] = false;
			}
		});
		
		b2make_gestor.uploads_queueds_num++;
	}
	
	function upload_files_mask_size(){
		if(b2make_gestor.uploadFiles.mask){
			b2make_gestor.uploadFiles.mask.css('width','100%');
			b2make_gestor.uploadFiles.mask.css('height',$(window).height()+'px');
		}
	}
	
	$.upload_files_mask_close = function(){
		if(b2make_gestor.uploadFiles.buttonClicked){
			setTimeout(function(){
				b2make_gestor.uploadFiles.mask.hide();
				b2make_gestor.uploadFiles.buttonClicked = false;
			},200);
		}
	}
	
	$.upload_files_mask_open = function(){
		if(!b2make_gestor.uploadFiles.mask){
			b2make_gestor.uploadFiles.mask = $('<div></div>');
			
			b2make_gestor.uploadFiles.mask.css('zIndex',9999);
			b2make_gestor.uploadFiles.mask.css('position','fixed');
			b2make_gestor.uploadFiles.mask.css('top','0px');
			b2make_gestor.uploadFiles.mask.css('left','0px');
			upload_files_mask_size();
			
			b2make_gestor.uploadFiles.mask.appendTo('body');
		}
		
		b2make_gestor.uploadFiles.mask.show();
	}
	
	$.upload_files_start_buttons = function(){
		$('.b2make-uploads-btn').on('mousedown touchstart',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			b2make_gestor.uploadFiles.buttonClickedSelf = true;
			b2make_gestor.uploadFiles.buttonClicked = true;
			$.upload_files_mask_open();
			
			$(this).find('.b2make-uploads-input').click();
		});
	}
	
	function upload_files(){
		b2make_gestor.uploadFiles = {};
		
		b2make_gestor.uploadFiles.buttonClicked = false;
		b2make_gestor.uploadFiles.ids = 0;
		
		$.upload_files_start_buttons();
		
		document.body.onfocus = function(){ 
			if(b2make_gestor.uploadFiles.buttonClicked){
				$.upload_files_mask_close();
			}
		}
	}
	
	upload_files();
	
	// ============================= B2make Interfaces ========================
	
	function b2make_interface_box(){
		if($('.bi-box').length > 0){
			$(window).bind('mouseup touchend',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var parents = $(e.target).parents();
				var close = true;
				
				if(parents)
				for(var i=0;i<parents.length;i++){
					if($(parents[i]).hasClass('bi-box')){
						close = false;
						break;
					}
				}
				
				if(close){
					$('.bi-box').hide();
				}
			});
			
			$('.bi-box-close').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				$('.bi-box').hide();
			});
			
			$('.bi-box-open').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var top = $(this).position().top;
				var left = $(this).position().left;
				var id = $(this).attr('data-box-id');
				var event_open = $(this).attr('data-event-open');
				var event_close = $(this).attr('data-event-close');
				var title = $(this).attr('data-btn-title');
				$(id).trigger(event_open);
				$(id).attr('data-event-close',event_close);
				$(id).find('.bi-box-btn').attr('value',title);
				$(id).css('top',top);
				$(id).css('left',left);
				$(id).show();
				e.stopPropagation();
			});
			
			$('.bi-box-btn').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var id = '#'+$(this).parent().attr('id');
				var event_close = $(this).parent().attr('data-event-close');
				$(id).hide();
				$(id).trigger(event_close);
				e.stopPropagation();
			});
		}
	}
	
	function b2make_interface_aba_opcoes(){
		// =================== Montar menu de opções
		
		$('.bi-aba-opcoes-menu').each(function(){
			var count=0;
			var obj_menu = $(this);
			var conts_arr = obj_menu.attr('data-conts').split(';');
			var atual = parseInt(obj_menu.attr('data-atual'));
			
			for(var i=0;i<conts_arr.length;i++){
				var cont_arr = conts_arr[i].split(',');
				var label = cont_arr[0];
				var id = cont_arr[1];
				var title = cont_arr[2];
				
				var menu_opcao = $('<div class="bi-aba-opcoes-opcao bi-noselect bi-botao'+(atual == i+1 ? ' bi-aba-opcoes-atual':'')+'" data-id="'+id+'" title="'+title+'" data-num="'+(i+1)+'">'+label+'</div>');
				menu_opcao.appendTo(obj_menu);
			}
			
			switch(obj_menu.attr('data-type')){
				case 'content-switch':
					obj_menu.parent().find('.bi-aba-opcoes-conts').find('.bi-aba-opcoes-cont').each(function(){
						count++;
						
						var obj_cont = $(this);
						var id2 = obj_cont.attr('data-id');
						
						if(atual == count){
							obj_cont.show();
						}
					});
				break;
			}
			
			if(obj_menu.attr('data-input')){
				$('<input type="hidden" name="'+obj_menu.attr('data-input')+'" id="'+obj_menu.attr('data-input')+'" value="'+atual+'">').appendTo(obj_menu.parent());
			}
		});
		
		// =================== Listener para mudar entre as abas
		
		$('.bi-aba-opcoes-opcao').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			switch($(this).parent().attr('data-type')){
				case 'content-switch':
					$(this).parent().find('.bi-aba-opcoes-opcao').each(function(){
						$(this).removeClass('bi-aba-opcoes-atual');
						$(this).parent().parent().find('.bi-aba-opcoes-conts').find('.bi-aba-opcoes-cont').hide();
					});
					
					$(this).addClass('bi-aba-opcoes-atual');
					$(this).parent().parent().find('.bi-aba-opcoes-conts').find('.bi-aba-opcoes-cont[data-id="'+$(this).attr('data-id')+'"]').show();
				break;
				case 'callback':
					$(this).parent().find('.bi-aba-opcoes-opcao').each(function(){
						$(this).removeClass('bi-aba-opcoes-atual');
					});
					
					$(this).addClass('bi-aba-opcoes-atual');
				break;
			}
			
			var atual = $(this).attr('data-num');
			
			$(this).parent().attr('data-atual',atual);
			
			if($(this).parent().attr('data-input')){
				$('#'+$(this).parent().attr('data-input')).val(atual);
			}
			
			if($(this).parent().attr('data-callback')){
				$(this).parent().trigger('botao-clicked',atual);
			}
		});
	}
	
	function b2make_interface_carrossel(){
		var animation_time = 400;
		var numMaxCont = 10;
		var ativo = false;
		
		function carrossel_start(p={}){
			var dados = p.dados;
			var c = p.carrossel;
			var nc = (dados.images ? Math.ceil(dados.images.length/numMaxCont) : 1);
			var nmc = numMaxCont;
			var imgs = 0;
			var imgSelecionada = (dados.imagem_selecionada ? parseInt(dados.imagem_selecionada) : 0);
			var ca = $('<div class="bi-carrossel-area"></div>');
			var cah = $('<div class="bi-carrossel-area-hidden"></div>');
			var co = $('<div class="bi-carrossel-controls"><div class="bi-carrossel-prev"><div class="bi-icon"></div></div><div class="bi-carrossel-stats"></div><div class="bi-carrossel-next"><div class="bi-icon"></div></div></div>');
			var contFoco = (imgSelecionada > 0 ? false : true);
			var pagina = 0;
			
			c.attr('data-max',nc);
			c.attr('data-img-selecionada',imgSelecionada);
			
			for(var i=0;i<nc;i++){
				var cc = $('<div class="bi-carrossel-cont" data-num="'+(i+1)+'"></div>');
				
				if(dados.images)
				for(var j=0;j<nmc;j++){
					var image = dados.images[imgs];
					imgs++;
					var selecionada = false;
					if(image.id == imgSelecionada){
						contFoco = true;
						selecionada = true;
					}
					var cd = $('<div class="bi-carrossel-unidade'+(selecionada ? ' bi-carrossel-unidade-selecionada':'')+' b2make-tooltip bi-noselect" data-id="'+image.id+'" title="Imagem nome: '+image.file+' | '+image.width+'x'+image.height+'" style="background-image:url('+image.mini+');"><div class="bi-icon bi-carrossel-delete"></div><div class="bi-num"></div></div>');
					cd.appendTo(cc);
					
					if(dados.images.length == imgs){
						break;
					}
				}
				
				if(contFoco){
					cc.appendTo(ca);
					pagina = i+1;
				} else {
					cc.appendTo(cah);
				}
				
				contFoco = false;
			}
			
			ca.appendTo(c);
			cah.appendTo(c);
			co.find('.bi-carrossel-stats').html('P&aacute;gina: '+pagina+' de '+nc+' | Total Imagens: '+imgs);
			co.appendTo(c);
			
			if(nc<2){
				co.hide();
			}
			
			c.attr('data-num-imgs',imgs);
			c.attr('data-pagina-atual',pagina);
			
			if(!b2make_gestor.carrossel){
				b2make_gestor.carrossel = new Array();
			}
			
			$('.b2make-tooltip').tooltip({
				show: {
					effect: "fade",
					delay: 400
				}
			});
		}
		
		function carrossel_start_vars(p={}){
			var c = p.carrossel;
			
			if(!b2make_gestor.carrossel[c]){
				b2make_gestor.carrossel[c] = {};
				b2make_gestor.carrossel[c].max = c.attr('data-max');
				b2make_gestor.carrossel[c].width = c.width();
				b2make_gestor.carrossel[c].numItems = c.attr('data-num-imgs');
				b2make_gestor.carrossel[c].paginaAtual = c.attr('data-pagina-atual');
			}
		}
		
		function carrossel_select_unidade(p={}){
			var c = p.carrossel;
			var u = p.unidade;
			
			c.find('.bi-carrossel-unidade').removeClass('bi-carrossel-unidade-selecionada');
			u.addClass('bi-carrossel-unidade-selecionada');
			c.attr('data-img-selecionada',u.attr('data-id'));
			
			if(c.attr('data-callback')){
				c.trigger('selecionar-unidade',u.attr('data-id'));
			}
		}
		
		function carrossel_update_stats(p={}){
			var c = p.carrossel;
			
			var paginaAtual = b2make_gestor.carrossel[c].paginaAtual;
			var paginaMax = b2make_gestor.carrossel[c].max;
			var numItems = b2make_gestor.carrossel[c].numItems;
			
			c.find('.bi-carrossel-controls').find('.bi-carrossel-stats').html('P&aacute;gina: '+paginaAtual+' de '+paginaMax+' | Total Imagens: '+numItems);
		}
		
		function carrossel_adicionar(p={}){
			var c = p.carrossel;
			var image = p.dados;
			
			carrossel_start_vars({carrossel:c});
			
			var numItems = parseInt(b2make_gestor.carrossel[c].numItems);
			var max = parseInt(b2make_gestor.carrossel[c].max);
			
			c.find('.bi-carrossel-area').find('.bi-carrossel-cont').appendTo(c.find('.bi-carrossel-area-hidden'));
			
			if(c.find('.bi-carrossel-cont[data-num="'+max+'"]').find('.bi-carrossel-unidade').length == numMaxCont){
				var cc = $('<div class="bi-carrossel-cont" data-num="'+(max+1)+'"></div>');
				cc.appendTo(c.find('.bi-carrossel-area'));
				
				max = b2make_gestor.carrossel[c].paginaAtual = b2make_gestor.carrossel[c].max = max+1;
			} else {
				c.find('.bi-carrossel-cont[data-num="'+max+'"]').appendTo(c.find('.bi-carrossel-area'));
			}
			
			var cont = c.find('.bi-carrossel-cont[data-num="'+max+'"]');
			cont.css('left','0px');
			
			numItems++;
			
			var cd = $('<div class="bi-carrossel-unidade b2make-tooltip bi-noselect" data-id="'+image.id+'" title="Imagem nome: '+image.file+' | '+image.width+'x'+image.height+'" style="background-image:url('+image.mini+');"><div class="bi-icon bi-carrossel-delete"></div><div class="bi-num"></div></div>');
			cd.appendTo(cont);
			
			$('.b2make-tooltip').tooltip({
				show: {
					effect: "fade",
					delay: 400
				}
			});
			
			b2make_gestor.carrossel[c].numItems = numItems;
			
			if(numItems > numMaxCont){
				c.find('.bi-carrossel-controls').show();
			}
			
			carrossel_update_stats({carrossel:c});
		}
		
		function carrossel_delete(p={}){
			var c = p.carrossel;
			var u = p.unidade;
			var num = parseInt(u.parent().attr('data-num'));
			var imgSelecionada = c.attr('data-img-selecionada');
			var imgNum = u.attr('data-id');
			
			carrossel_start_vars({carrossel:c});
			
			var max = parseInt(b2make_gestor.carrossel[c].max);
			var numItems = parseInt(b2make_gestor.carrossel[c].numItems);
			var paginaAtual = b2make_gestor.carrossel[c].paginaAtual;
			
			for(var i=num;i<max;i++){
				var unidadeMudar = c.find('.bi-carrossel-area-hidden').find('.bi-carrossel-cont[data-num="'+(i+1)+'"]').find('.bi-carrossel-unidade').first();
				
				if(i == num){
					unidadeMudar.appendTo(u.parent());
				} else {
					unidadeMudar.appendTo(c.find('.bi-carrossel-area-hidden').find('.bi-carrossel-cont[data-num="'+(i)+'"]'));
				}
			}
			
			numItems--;
			b2make_gestor.carrossel[c].numItems = numItems;
			
			u.remove();
			
			if(c.find('.bi-carrossel-cont[data-num="'+max+'"]').find('.bi-carrossel-unidade').length == 0){
				if(max > 1){
					c.find('.bi-carrossel-cont[data-num="'+max+'"]').remove();
					b2make_gestor.carrossel[c].paginaAtual = b2make_gestor.carrossel[c].max = c.find('.bi-carrossel-cont').length;
					
					if(c.find('.bi-carrossel-area').find('.bi-carrossel-cont').length == 0){
						c.find('.bi-carrossel-area-hidden').find('.bi-carrossel-cont').last().css('left','0px').appendTo(c.find('.bi-carrossel-area'));
					}
				}
			}
			
			if(numItems == numMaxCont){
				c.find('.bi-carrossel-controls').hide();
			}
			
			if(imgSelecionada == imgNum){
				c.removeAttr('data-img-selecionada');
			}
			
			carrossel_update_stats({carrossel:c});
		}
		
		function carrossel_remover_selecao(p={}){
			var c = p.carrossel;
			
			c.find('.bi-carrossel-unidade').removeClass('bi-carrossel-unidade-selecionada');
			c.removeAttr('data-img-selecionada');
			if(c.attr('data-callback')){
				c.trigger('remover-selecao');
			}
		}
		
		function animate_next(p={}){
			var c = p.carrossel;
			
			carrossel_start_vars({carrossel:c});
			
			if(!b2make_gestor.carrossel[c].animate){
				b2make_gestor.carrossel[c].animate = true;
				
				var num = parseInt(c.find('.bi-carrossel-area').find('.bi-carrossel-cont').attr('data-num'));
				var max = parseInt(b2make_gestor.carrossel[c].max);
				
				if(num == max){
					var numDepois = 1;
				} else {
					var numDepois = num+1;
				}
				
				var sai = c.find('.bi-carrossel-area').find('.bi-carrossel-cont[data-num="'+num+'"]');
				var entra = c.find('.bi-carrossel-area-hidden').find('.bi-carrossel-cont[data-num="'+numDepois+'"]');
				
				entra.css('left',b2make_gestor.carrossel[c].width+'px');
				entra.appendTo(c.find('.bi-carrossel-area'));
				
				sai.animate(
					{left:'-'+b2make_gestor.carrossel[c].width+'px'},
					animation_time,
					'swing',
					function(){
						sai.appendTo(c.find('.bi-carrossel-area-hidden'));
					}
				);
				entra.animate(
					{left:'0px'},
					animation_time,
					'swing',
					function(){
						if(b2make_gestor.carrossel[c].next){
							b2make_gestor.carrossel[c].next = false;
							animate_next(p);
						}
						
						b2make_gestor.carrossel[c].animate = false;
						b2make_gestor.carrossel[c].paginaAtual = numDepois;
						carrossel_update_stats({carrossel:c});
					}
				);
			} else {
				b2make_gestor.carrossel[c].next = true;
			}
		}
		
		function animate_prev(p={}){
			var c = p.carrossel;
			
			carrossel_start_vars({carrossel:c});
			
			if(!b2make_gestor.carrossel[c].animate){
				b2make_gestor.carrossel[c].animate = true;
				
				var num = parseInt(c.find('.bi-carrossel-area').find('.bi-carrossel-cont').attr('data-num'));
				var max = parseInt(b2make_gestor.carrossel[c].max);
				
				if(num == 1){
					var numDepois = max;
				} else {
					var numDepois = num-1;
				}
				
				var sai = c.find('.bi-carrossel-area').find('.bi-carrossel-cont[data-num="'+num+'"]');
				var entra = c.find('.bi-carrossel-area-hidden').find('.bi-carrossel-cont[data-num="'+numDepois+'"]');
				
				entra.css('left','-'+b2make_gestor.carrossel[c].width+'px');
				entra.appendTo(c.find('.bi-carrossel-area'));
				
				sai.animate(
					{left:b2make_gestor.carrossel[c].width+'px'},
					animation_time,
					'swing',
					function(){
						sai.appendTo(c.find('.bi-carrossel-area-hidden'));
					}
				);
				entra.animate(
					{left:'0px'},
					animation_time,
					'swing',
					function(){
						if(b2make_gestor.carrossel[c].prev){
							b2make_gestor.carrossel[c].prev = false;
							animate_next(p);
						}
						
						b2make_gestor.carrossel[c].animate = false;
						b2make_gestor.carrossel[c].paginaAtual = numDepois;
						carrossel_update_stats({carrossel:c});
					}
				);
			} else {
				b2make_gestor.carrossel[c].prev = true;
			}
		}
		
		$('.bi-carrossel').each(function(){
			ativo = true;
			
			if(!b2make_gestor.carrossel){
				b2make_gestor.carrossel = new Array();
			} 
		});
		
		if(ativo){
			$(document.body).on('mouseup tap','.bi-carrossel-next',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				animate_next({
					carrossel:$(this).parent().parent()
				});
			});
			
			$(document.body).on('mouseup tap','.bi-carrossel-prev',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				animate_prev({
					carrossel:$(this).parent().parent()
				});
			});
			
			$(document.body).on('mouseup tap','.bi-carrossel-unidade',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				carrossel_select_unidade({
					carrossel:$(this).parent().parent().parent(),
					unidade:$(this)
				});
			});
			
			$(document.body).on('mouseup tap','.bi-carrossel-delete',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				var c = $(this).parent().parent().parent().parent();
				
				if(c.attr('data-callback')){
					c.trigger('remover-unidade-verificar',{
						carrossel:c,
						unidade:$(this).parent()
					});
				} else {
					carrossel_delete({
						carrossel:c,
						unidade:$(this).parent()
					});
				}
				
				e.stopPropagation();
			});
			
			$('.bi-carrossel-remover-selecao').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				carrossel_remover_selecao({
					carrossel:$($(this).attr('data-carrossel'))
				});
			});
			
			// ======== Callbacks
			
			$('.bi-carrossel').on('start',function(e,dados){
				carrossel_start({
					carrossel:$(this),
					dados:dados
				});
			});
			
			$('.bi-carrossel').on('adicionar-unidade',function(e,dados){
				carrossel_adicionar({
					carrossel:$(this),
					dados:dados
				});
			});
			
			$('.bi-carrossel').on('remover-unidade',function(e,dados){
				carrossel_delete(dados);
			});
		}
	}
	
	function b2make_interface_area_teste(p={}){
		var ww = $(window).width();
		var wh = $(window).height();
		var cw = Math.floor(ww*0.8);
		var ch = Math.floor(wh*0.8);
		var ct = Math.floor(wh/2 - ch/2);
		var cl = Math.floor(ww/2 - cw/2);
		
		var cont = $('<div style="z-index:999; position:absolute; top:'+ct+'px; left:'+cl+'px; width:'+cw+'px; height:'+ch+'px; background-color:white; padding:30px;">'+(p.html ? p.html : '')+'</div>');
		
		cont.appendTo('body');
	}
	
	function b2make_interface(){
		b2make_interface_box();
		b2make_interface_aba_opcoes();
		b2make_interface_carrossel();
	}
	
	b2make_interface();
});	