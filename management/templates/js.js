b2makeAdmin.stop_enter_preventDefaults = true;

$(document).ready(function(){
	sep = "../../";
	
	if(variaveis_js.modelo_mais_paginas){
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
										window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'design','_self');
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
});