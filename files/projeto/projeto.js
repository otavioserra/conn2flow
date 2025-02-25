var projeto_js = {};
var b2make = {};

$.projeto_links = function(params){
	var ajax_nao = params['ajax_nao'];
	var nao_fazer_nada = params['nao_fazer_nada'];
	var objeto = params['objeto'];
	
	//if($(objeto).attr('class') == 'class')nao_fazer_nada = true;
	
	return {
		nao_fazer_nada:nao_fazer_nada,
		ajax_nao:ajax_nao
	};
};

$.projeto_aplicar_scripts_after = function(params){
	if(!params)params = Array();
	var history = params['history'];
	
	if(params){
		
	}
};

$.projeto_aplicar_scripts = function(params){
	if(!params)params = Array();
	var history = params['history'];
	var href = params['href'];
	
	if($('#b2make-menu-categorias').length > 0){
		var width_cont = $('#b2make-menu-categorias').width();
		var width_total = 0;
		
		$('.b2make-menu-categoria').each(function(){
			width_total = width_total + $(this).width() + 80;
		});
		
		var cat_count = 0;
		$('.b2make-menu-categoria').each(function(i,e){
			if(!b2make.menu_categorias_first){
				b2make.menu_categorias_first = true;
				return false;
			}
			
			if(b2make.menu_categorias_pos >= 0){
				if(cat_count >= b2make.menu_categorias_pos){
					return false;
				}
				$('#b2make-menu-categorias').append(e);
				cat_count++;
			} else {
				if(cat_count <= b2make.menu_categorias_pos){
					return false;
				}
				$('#b2make-menu-categorias').prepend(e);
				cat_count--;
			}
		});
		
		if(width_total > width_cont){
			$('#b2make-menu-categorias-prev').show();
			$('#b2make-menu-categorias-next').show();
		} else {
			$('#b2make-menu-categorias-prev').hide();
			$('#b2make-menu-categorias-next').hide();
		}
		
		$('#b2make-menu-categorias-prev').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			b2make.menu_categorias_pos--;
			
			//$('#b2make-menu-categorias').prepend($('.b2make-menu-categoria:last-child'));
			var len = $('.b2make-menu-categoria').length;
			$('.b2make-menu-categoria').each(function(i,e){
				if(i==len-1){
					$('#b2make-menu-categorias').prepend(e);
				}
			});
			
			if(b2make.menu_categorias_pos < (-1)*(len-1)){
				b2make.menu_categorias_pos = 0;
			}
		});
		
		$('#b2make-menu-categorias-next').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			b2make.menu_categorias_pos++;
			
			//$('#b2make-menu-categorias').append($('.b2make-menu-categoria:first-child'));
			var len = $('.b2make-menu-categoria').length;
			$('.b2make-menu-categoria').each(function(i,e){
				if(i==0){
					$('#b2make-menu-categorias').append(e);
				}
			});
			
			if(b2make.menu_categorias_pos > (len-1)){
				b2make.menu_categorias_pos = 0;
			}
		});
	}
	
	if($('#b2make-esqueceu-senha-cont').length > 0){
		$('#esqueceu_senha label').each(function(){
			var id_pai = $(this).attr('for');
			var top = $('#'+id_pai).position().top;
			var left = $('#'+id_pai).position().left;
			var label = $(this);
			
			label.css({top:top,left:left});
			
			setTimeout(function() {
				if($('#'+id_pai).val()){
					label.hide();
				}
			});
		});
		
		$('#esqueceu_senha input').bind('keyup keydown change blur focus',function(e) {
			if($(this).val()){
				$('label[for="'+$(this).attr('id')+'"]').hide();
			} else {
				$('label[for="'+$(this).attr('id')+'"]').show();
			}
		});
	}
	
	if($('#b2make-page-plans').length > 0 || $('#b2make-signup-cont').length > 0){
		if(ajax_vars.plan_id){
			$('#b2make-signup-plano-val').html(ajax_vars.plan_name);
			$('#b2make-plano-selecionado').val(ajax_vars.plan_id);
		}
		
		if(localStorage['b2make.plan-selecionado']){
			$('#b2make-signup-plano-val').html(localStorage['b2make.plan-nome']);
			$('#b2make-plano-selecionado').val(localStorage['b2make.plan-selecionado']);
		}
		
		$('.b2make-spc-botao').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			var id = $(this).attr('data-id');
			var nome = $(this).attr('data-nome');
			
			if(!$('#b2make-signup-cont').length){
				localStorage['b2make.plan-selecionado'] = id;
				localStorage['b2make.plan-nome'] = nome;
				window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'signup','_self');
			} else {
				$('#b2make-signup-plano-val').html(nome);
				$('#b2make-plano-selecionado').val(id);
				
				$('#b2make-signup-lightbox-fechar').trigger('mouseup');
			}
		});
	}
	
	if($('#b2make-page-templates').length > 0 || $('#b2make-signup-cont').length > 0){
		if(ajax_vars.modelo_mais_paginas){
			$('#b2make-templates-mais-opcoes').show();
		} else {
			$('#b2make-templates-mais-opcoes').hide();
		}
		
		var modelo_inicial = '1';
		b2make.modelos_pagina = 1;
		
		if(localStorage['b2make.modelo-selecionado']){
			modelo_inicial = localStorage['b2make.modelo-selecionado'];
			$('#b2make-signup-template-val').html(localStorage['b2make.modelo-nome']);
			$('#b2make-modelo-selecionado').val(localStorage['b2make.modelo-selecionado']);
			
			if(localStorage['b2make.segmentos-selecionado']){
				b2make.modelo_selecionado = localStorage['b2make.modelo-selecionado'];
				segmento_selecionado(localStorage['b2make.segmentos-selecionado']);
			}
		}
		
		if(ajax_vars.template_id){
			$('#b2make-signup-template-val').html(ajax_vars.template_name);
			$('#b2make-modelo-selecionado').val(ajax_vars.template_id);
		}
		
		if($('#b2make-signup-cont').length){
			var obj_start = $('.b2make-templates-cont[data-id="'+modelo_inicial+'"]');
			
			obj_start.addClass('b2make-template-cont-selected');
			obj_start.find('.b2make-templates-imagem').addClass('b2make-template-selected');
			obj_start.find('.b2make-templates-imagem').find('.b2make-templates-escolher').html('Selecionado');
			obj_start.find('.b2make-templates-imagem').find('.b2make-templates-imagem-mask').show();
			obj_start.find('.b2make-templates-imagem').find('.b2make-templates-ver').show();
			obj_start.find('.b2make-templates-imagem').find('.b2make-templates-escolher').show();
		}
		
		$('#b2make-segmentos').on('change',function(e){
			var val = $(this).val();
			var opcao = 'b2make-segmentos';
			
			if($('#b2make-modelos-home').length)
				localStorage['b2make.segmentos-selecionado'] = val;
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					segmento_id : val
				},
				beforeSend: function(){
					//carregamento_open();
				},
				success: function(txt){
					if(
						txt.charAt(0) == "{" || 
						txt.charAt(0) == "["
					){
						var dados = eval('(' + txt + ')');
						
						switch(dados.status){
							case 'Ok':
								$('#b2make-templates').html('');
								$('#b2make-templates').html(dados.modelos);
								
								b2make.modelos_pagina = 1;
								
								if(dados.mais_paginas == 'sim'){
									$('#b2make-templates-mais-opcoes').show();
								} else {
									$('#b2make-templates-mais-opcoes').hide();
								}
								
								modelos_listeners();
								
								if($('#b2make-signup-cont').length){
									var obj = $('.b2make-templates-cont[data-id="1"]');
									
									obj.addClass('b2make-template-cont-selected');
									obj.find('.b2make-templates-imagem').addClass('b2make-template-selected');
									obj.find('.b2make-templates-imagem').find('.b2make-templates-escolher').html('Selecionado');
									obj.find('.b2make-templates-imagem').find('.b2make-templates-imagem-mask').show();
									obj.find('.b2make-templates-imagem').find('.b2make-templates-ver').show();
									obj.find('.b2make-templates-imagem').find('.b2make-templates-escolher').show();
									
									$('#b2make-modelo-selecionado').val('1');
								}
								
								if($('#b2make-modelos-home').length){
									var height = $('#b2make-modelos-home-cont').outerHeight(true);
									$('#b2make-modelos-home').css('height',height+'px');
									parallax_update();
								}
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
					//carregamento_close();
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
					//carregamento_close();
				}
			});
		});
		
		$('#b2make-templates-mais-opcoes').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			var val = $('#b2make-segmentos').val();
			var opcao = 'b2make-modelos-mais';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					segmento_id : val,
					pagina : (b2make.modelos_pagina + 1)
				},
				beforeSend: function(){
					//carregamento_open();
				},
				success: function(txt){
					if(
						txt.charAt(0) == "{" || 
						txt.charAt(0) == "["
					){
						var dados = eval('(' + txt + ')');
						
						switch(dados.status){
							case 'Ok':
								var stop = false;
								$('#b2make-templates').append(dados.modelos);
								
								if(dados.mais_paginas == 'sim'){
									$('#b2make-templates-mais-opcoes').show();
								} else {
									$('#b2make-templates-mais-opcoes').hide();
									stop = true;
								}
								
								b2make.modelos_pagina++;
								modelos_listeners();
								
								if(b2make.modelo_selecionado){
									if($('#b2make-signup-cont').length){
										var id = b2make.modelo_selecionado;
										
										if($('.b2make-templates-cont[data-id="'+id+'"]').length){
											var obj = $('.b2make-templates-cont[data-id="'+id+'"]');
											
											obj.addClass('b2make-template-cont-selected');
											obj.find('.b2make-templates-imagem').addClass('b2make-template-selected');
											obj.find('.b2make-templates-imagem').find('.b2make-templates-escolher').html('Selecionado');
											obj.find('.b2make-templates-imagem').find('.b2make-templates-imagem-mask').show();
											obj.find('.b2make-templates-imagem').find('.b2make-templates-ver').show();
											obj.find('.b2make-templates-imagem').find('.b2make-templates-escolher').show();
											
											$('#b2make-modelo-selecionado').val(id);
											b2make.modelo_selecionado = false;
										} else {
											if(!stop)$('#b2make-templates-mais-opcoes').trigger('mouseup');
										}
									}
								}
								
								if($('#b2make-modelos-home').length){
									var height = $('#b2make-modelos-home-cont').outerHeight(true);
									$('#b2make-modelos-home').css('height',height+'px');
									parallax_update();
								}
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
					//carregamento_close();
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
					//carregamento_close();
				}
			});
		});
		
		function segmento_selecionado(val){
			var opcao = 'b2make-segmentos';
			
			$.ajax({
				type: 'POST',
				url: '.',
				data: { 
					ajax : 'sim',
					opcao : opcao,
					segmento_id : val
				},
				beforeSend: function(){
					//carregamento_open();
				},
				success: function(txt){
					if(
						txt.charAt(0) == "{" || 
						txt.charAt(0) == "["
					){
						var dados = eval('(' + txt + ')');
						
						switch(dados.status){
							case 'Ok':
								$('#b2make-segmentos option[value="'+val+'"]').attr('selected', 'selected');
								
								$('#b2make-templates').html('');
								$('#b2make-templates').html(dados.modelos);
								
								b2make.modelos_pagina = 1;
								
								if(dados.mais_paginas == 'sim'){
									$('#b2make-templates-mais-opcoes').show();
								} else {
									$('#b2make-templates-mais-opcoes').hide();
								}
								
								modelos_listeners();
								
								if($('#b2make-signup-cont').length){
									var id = b2make.modelo_selecionado;
									
									if($('.b2make-templates-cont[data-id="'+id+'"]').length){
										var obj = $('.b2make-templates-cont[data-id="'+id+'"]');
										
										obj.addClass('b2make-template-cont-selected');
										obj.find('.b2make-templates-imagem').addClass('b2make-template-selected');
										obj.find('.b2make-templates-imagem').find('.b2make-templates-escolher').html('Selecionado');
										obj.find('.b2make-templates-imagem').find('.b2make-templates-imagem-mask').show();
										obj.find('.b2make-templates-imagem').find('.b2make-templates-ver').show();
										obj.find('.b2make-templates-imagem').find('.b2make-templates-escolher').show();
										
										$('#b2make-modelo-selecionado').val(id);
										b2make.modelo_selecionado = false;
									} else {
										$('#b2make-templates-mais-opcoes').trigger('mouseup');
									}
								}
							break;
							default:
								console.log('ERROR - '+opcao+' - '+dados.status);
							
						}
					} else {
						console.log('ERROR - '+opcao+' - '+txt);
					}
					//carregamento_close();
				},
				error: function(txt){
					console.log('ERROR AJAX - '+opcao+' - '+txt);
					//carregamento_close();
				}
			});
		}
		
		function modelos_listeners(){
			$('.b2make-templates-ver').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				e.stopPropagation();
				
				var id = $(this).parent().parent().attr('data-identificador');
				
				var newwindow = window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'preview/'+id,'_blank');
				if(window.focus) {newwindow.focus();}
				if(!newwindow.closed) {newwindow.focus();}
			});
			
			$('.b2make-templates-cont')
				.mouseenter(function() {
					$(this).find('.b2make-templates-imagem').find('.b2make-templates-imagem-mask').show();
					$(this).find('.b2make-templates-imagem').find('.b2make-templates-ver').show();
					$(this).find('.b2make-templates-imagem').find('.b2make-templates-escolher').show();
				})
				.mouseleave(function() {
					if(!$('#b2make-signup-cont').length){
						$(this).find('.b2make-templates-imagem').find('.b2make-templates-imagem-mask').hide();
						$(this).find('.b2make-templates-imagem').find('.b2make-templates-ver').hide();
						$(this).find('.b2make-templates-imagem').find('.b2make-templates-escolher').hide();
					} else {
						if(!$(this).find('.b2make-templates-imagem').hasClass('b2make-template-selected')){
							$(this).find('.b2make-templates-imagem').find('.b2make-templates-imagem-mask').hide();
							$(this).find('.b2make-templates-imagem').find('.b2make-templates-ver').hide();
							$(this).find('.b2make-templates-imagem').find('.b2make-templates-escolher').hide();
						}
					}
			});
			
			$('#b2make-templates div.b2make-templates-cont').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				var opcao = 'b2make-templates';
				var id = $(this).attr('data-id');
				var obj = $(this);
				
				$.ajax({
					type: 'POST',
					url: '.',
					data: { 
						ajax : 'sim',
						opcao : opcao,
						id : id
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
									if(!$('#b2make-signup-cont').length){
										localStorage['b2make.modelo-selecionado'] = id;
										localStorage['b2make.modelo-nome'] = $('.b2make-templates-cont[data-id="'+id+'"]').attr('data-nome');
										window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'signup','_self');
									} else {
										$('.b2make-templates-cont').find('.b2make-templates-imagem').each(function(){
											$(this).parent().removeClass('b2make-template-cont-selected');
											$(this).removeClass('b2make-template-selected');
											$(this).find('.b2make-templates-escolher').html('Escolher');
											$(this).find('.b2make-templates-imagem-mask').hide();
											$(this).find('.b2make-templates-ver').hide();
											$(this).find('.b2make-templates-escolher').hide();
										});
										
										obj.addClass('b2make-template-cont-selected');
										obj.find('.b2make-templates-imagem').addClass('b2make-template-selected');
										obj.find('.b2make-templates-imagem').find('.b2make-templates-escolher').html('Selecionado');
										obj.find('.b2make-templates-imagem').find('.b2make-templates-imagem-mask').show();
										obj.find('.b2make-templates-imagem').find('.b2make-templates-ver').show();
										obj.find('.b2make-templates-imagem').find('.b2make-templates-escolher').show();
										
										$('#b2make-signup-template-val').html($('.b2make-templates-cont[data-id="'+id+'"]').attr('data-nome'));
										$('#b2make-modelo-selecionado').val(id);
										
										$('#b2make-signup-lightbox-fechar').trigger('mouseup');
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
			});
		}
		
		modelos_listeners();
	}
	
	if($('#b2make-redefinir-senha-cont').length > 0){
		$('#redefinir_senha label').each(function(){
			var id_pai = $(this).attr('for');
			var top = $('#'+id_pai).position().top;
			var left = $('#'+id_pai).position().left;
			var label = $(this);
			
			label.css({top:top,left:left});
			
			setTimeout(function() {
				if($('#'+id_pai).val()){
					label.hide();
				}
			});
		});
		
		$('#redefinir_senha input').bind('keyup keydown change blur focus',function(e) {
			if($(this).val()){
				$('label[for="'+$(this).attr('id')+'"]').hide();
			} else {
				$('label[for="'+$(this).attr('id')+'"]').show();
			}
		});
	}
	
	
	$.banner_rules(href);
};

$.projeto_contato_campos = function(params){
	var campos_extra = new Array();
	
	/* campos_extra.push({
		campo : 'campo',
		post : 'post',
		mens : 'mens',
		campo_nao_obrigado : false,
	}); */
	
	return campos_extra;
};

$.projeto_enviar_formulario = function(params){
	var obj = params.objeto;
	var enviar = true;
	var campo;
	var post;
	var mens;
	var campos = Array();
	var posts = Array();
	var form_id = 'form_id'; // Obrigatório!
	var opcao = '';
	var href = '';
	var limpar_campos = true;
	var mudar_pagina = false;
	
	//campo = "campo"; mens = "É obrigatório definir o campo!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
	
	// Checar email
	//campo = "campo"; mens = "E-mail inválido, preencha o campo de e-mail válido!"; if(!$.checkMail($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
	
	return {
		enviar:enviar,
		form_id:form_id,
		campos:campos,
		posts:posts,
		opcao:opcao,
		href:href,
		limpar_campos:limpar_campos,
		mudar_pagina:mudar_pagina
	};
};

$.checkMail = function(mail){
	var er = new RegExp(/^[A-Za-z0-9_\-\.]+@[A-Za-z0-9_\-\.]{2,}\.[A-Za-z0-9]{2,}(\.[A-Za-z0-9])?/);
	if(typeof(mail) == "string"){
		if(er.test(mail)){ return true; }
	}else if(typeof(mail) == "object"){
		if(er.test(mail.value)){
					return true;
				}
	}else{
		return false;
		}
};

// ================================== Banners ========================

var banner_principal;
var banner_count = Array();

$.banner_rules = function(href){
	if(!href)href = location.href;
	
	var site = document.location.protocol+'//'+location.hostname+variaveis_js.site_raiz;
	
	href = href.replace(site,'');
	
	var href_aux = href;
	var href_arr;
	
	if(href_aux.match(/\./gi)){href_arr = href_aux.split('.');href = href_arr[0];href_aux = href;}
	if(href_aux.match(/\#/gi)){href_arr = href_aux.split('#');href = href_arr[0];href_aux = href;}
	if(href_aux.match(/\?/gi)){href_arr = href_aux.split('?');href = href_arr[0];}
	
	href = site+href;
	
	if(href.match(/blog/) == 'blog'){
		if(b2make.facebook_comments_flag){
			if(!b2make.facebook_comments_added){
				var facebook_coments = $('<div id="fb-root"></div><script>(function(d, s, id) {var js, fjs = d.getElementsByTagName(s)[0];if (d.getElementById(id)) return;js = d.createElement(s); js.id = id;js.src = "//connect.facebook.net/pt_BR/sdk.js#xfbml=1&version=v2.8";	fjs.parentNode.insertBefore(js, fjs);	}(document, \'script\', \'facebook-jssdk\'));</script>');
				facebook_coments.appendTo('body');
			}
			
			var width = parseInt($(window).width());
			
			if(width < 800){
				width = width - 30;
			} else {
				width = 800;
			}
			
			var facebook_div = $('<div class="fb-comments" data-href="'+ajax_vars.facebook_coments_url+'" data-width="'+width+'" data-numposts="5"></div>');
			facebook_div.appendTo('#b2make-facebook-comments');
			
			if(b2make.facebook_comments_added)FB.XFBML.parse();
			
			b2make.facebook_comments_added = true;
		}
		
		b2make.facebook_comments_flag = true;
	}
	
	if(
		site+'login/' == href ||
		site+'login' == href ||
		site+'logout/' == href ||
		site+'logout' == href
	){
	} else {
		
	}
};

$.cycle_nav_start = function(id){
	var padding_right = 0;
	var padding_left = 0;
	var padding_top = 0;
	var count = 0;
	
};

$(document).ready(function(){
	b2make.menu_categorias_pos = 0;
	
	$(window).resize(function() {
		if(b2make.facebook_comments_flag){
			$('#b2make-facebook-comments').html('');
			
			var width = parseInt($(window).width());
			
			if(width < 800){
				width = width - 30;
			} else {
				width = 800;
			}
			
			var facebook_div = $('<div class="fb-comments" data-href="'+ajax_vars.facebook_coments_url+'" data-width="'+width+'" data-numposts="5"></div>');
			facebook_div.appendTo('#b2make-facebook-comments');
			
			if(b2make.facebook_comments_added)FB.XFBML.parse();
		}
		
		menus_position(0);
	});
	
	function menu_categorias(){
		$(window).resize(function() {
			if($('#b2make-menu-categorias').length > 0){
				var width_cont = $('#b2make-menu-categorias').width();
				var width_total = 0;
				
				$('.b2make-menu-categoria').each(function(){
					width_total = width_total + $(this).width() + 80;
				});
				
				if(width_total > width_cont){
					$('#b2make-menu-categorias-prev').show();
					$('#b2make-menu-categorias-next').show();
				} else {
					$('#b2make-menu-categorias-prev').hide();
					$('#b2make-menu-categorias-next').hide();
				}
			}
		});
		
	}
	
	menu_categorias();
	
	function menus_close(){
		if(b2make.menus_visible){
			for(var i=0;i<b2make.menus.length;i++){
				if(b2make.menus[i].visible){
					$("#"+b2make.menus[i].target).hide();
					menus_update({
						visible : false,
						id : b2make.menus[i].id
					});
				}
			}
		}
	}
	
	function menus_update(p){
		for(var i=0;i<b2make.menus.length;i++){
			if(b2make.menus[i].id == p.id){
				b2make.menus[i].visible = p.visible;
			}
			
			menus_position(i);
		}
		
		var visible = false;
		
		for(i=0;i<b2make.menus.length;i++){
			if(b2make.menus[i].visible){
				visible = true;
			}
		}
		
		b2make.menus_visible = visible;
	}
	
	function menus_position(i){
		$("#"+b2make.menus[i].target).css('top',($("#"+b2make.menus[i].holder).offset().top + $("#"+b2make.menus[i].holder).height()) + (b2make.menus[i].ajuste_top ? b2make.menus[i].ajuste_top : 0));
		$("#"+b2make.menus[i].target).css('left',($("#"+b2make.menus[i].holder).offset().left - $("#"+b2make.menus[i].holder).width()) + (b2make.menus[i].ajuste_left ? b2make.menus[i].ajuste_left : 0));
	}
	
	function menus_vars(id,type){
		for(var i=0;i<b2make.menus.length;i++){
			switch(type){
				case 'holder':
					if(id == b2make.menus[i].holder){
						return b2make.menus[i];
					}
				break;
				case 'target':
					if(id == b2make.menus[i].target){
						return b2make.menus[i];
					}
				break;
				
			}
		}
		
		return false;
	}
	
	function menus_positions(){
		for(var i=0;i<b2make.menus.length;i++){
			if($("#"+b2make.menus[i].target).lenth > 0)
			$("#"+b2make.menus[i].target).css({
				top:$("#"+b2make.menus[i].holder).offset().top+(b2make.menus[i].start_top?b2make.menus[i].start_top:0),
				left:$("#"+b2make.menus[i].holder).offset().left+(b2make.menus[i].start_left?b2make.menus[i].start_left:0)
			});
		}
	}
	
	function menus(){
		b2make.menus = new Array();
		b2make.menus.manter_menu_aberto_apos_click = false;
		b2make.menus.push({
			id : "menu-mais",
			holder : "b2make-menu-mais",
			target : "b2make-menu-mais-options",
			ajuste_top : 0,
			ajuste_left : -15,
			visible : false
		});
		
		$(window).resize(function() {
			menus_positions();
		});
		
		$(window).bind('keyup',function(e) {
			if(e.keyCode == 27){ // ESC
				menus_close();
			}
		});
		
		$("html").on('mouseup tap',menus_close);
		
		menus_positions();
		
		for(var i=0;i<b2make.menus.length;i++){
			$("#"+b2make.menus[i].holder).on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				e.stopPropagation();
				
				var vars = menus_vars($(this).attr('id'),'holder');
				var visible = false;
				
				if($("#"+vars.target+" li").length != 0){
					if($("#"+vars.target).is(":visible")){
						$("#"+vars.target).hide();
					} else {
						$("#"+vars.target).show();
						visible = true;
					}
					
					menus_update({
						visible : visible,
						id : vars.id
					});
				} else {
					var msg = b2make.msgs.conteinerDontExist;
					
					$.dialogbox_open({
						msg: msg
					});
				}
			});
			
			$("#"+b2make.menus[i].target).on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				if(b2make.menus.manter_menu_aberto_apos_click)e.stopPropagation();
			});
			
			$("#"+b2make.menus[i].target+" li").on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				var type = $(this).attr('data-type');
				var vars = menus_vars($(this).parent().attr('id'),'target');
				var id = $(this).attr('data-id');
				
				switch(vars.id){
					case 'menu-mais':
						//
					break;
				}
				
				$("#"+vars.target).hide();
				
				menus_update({
					visible : false,
					id : vars.id
				});
			});
		}
	}
	
	menus();
	$.banner_rules();
});