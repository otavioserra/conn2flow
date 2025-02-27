var alerta;
var alerta_php;
var alerta_box_open = false;
var alerta_box_mudou_tit = false;
var alerta_box_mudou_ok = false;
var alerta_box;
var alerta_box_shadow;
var alerta_box_texto;
var tempo_animacao = 150;
var ajax_vars = variaveis_js;
var debug_retorno = false;
var href_atual = location.href;
var scripts_functions = {};
var global_vars = {};

if(!window.console){ window.console = {log: function(){} }; }

// Extend the default Number object with a formatMoney() method:
// usage: someVar.formatMoney(decimalPlaces, symbol, thousandsSeparator, decimalSeparator)
// defaults: (2, "$", ",", ".")
Number.prototype.formatMoney = function(places, symbol, thousand, decimal) {
	places = !isNaN(places = Math.abs(places)) ? places : 2;
	symbol = symbol !== undefined ? symbol : "$";
	thousand = thousand || ",";
	decimal = decimal || ".";
	var number = this, 
		negative = number < 0 ? "-" : "",
		i = parseInt(number = Math.abs(+number || 0).toFixed(places), 10) + "",
		j = (j = i.length) > 3 ? j % 3 : 0;
	return symbol + negative + (j ? i.substr(0, j) + thousand : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousand) + (places ? decimal + Math.abs(number - i).toFixed(places).slice(2) : "");
};

$.link_trigger = function(href){
	$(document).ready(function(){
		if(!window.link_trigger){
			window.link_trigger = $("<a></a>");
			window.link_trigger.appendTo('body');
			window.link_trigger.hide();
		}
		
		window.link_trigger.attr('href',document.location.protocol+'//'+document.location.hostname+(variaveis_js.site_raiz?variaveis_js.site_raiz:'')+href).trigger('click');
	});
};

$.aplicar_scripts_after = function(params){
	if(!params)params = Array();
	
	var scripts_after = scripts_functions.aplicar_scripts_after;
	if(scripts_after){
		for(var i=0;i<scripts_after.length;i++){
			window[scripts_after[i]](); 
		}
	}
};

$.aplicar_scripts = function(params){
	if(!params)params = Array();
	
	var scripts = scripts_functions.aplicar_scripts;
	if(scripts){
		for(var i=0;i<scripts.length;i++){
			window[scripts[i]]();
		}
	}
};

$.aplicar_scripts_after_add = function(func){
	if(!scripts_functions.aplicar_scripts_after) scripts_functions.aplicar_scripts_after = Array();
	scripts_functions.aplicar_scripts_after.push(func);
	window[func]();
};

$.aplicar_scripts_add = function(func){
	if(!scripts_functions.aplicar_scripts) scripts_functions.aplicar_scripts = Array();
	scripts_functions.aplicar_scripts.push(func);
	window[func]();
};

$.alerta_open = function(texto,titulo,botao){
	var padrao_tit = 'Alerta';
	var padrao_bot = 'Ok';
	
	if(global_vars.alerta_appendto_body)
	if(global_vars.alerta_appendto_body.length > 0){
		var appendto_body = $(global_vars.alerta_appendto_body);
		appendto_body.appendTo('body');
		global_vars.alerta_appendto_body = '';
	}
	
	if(!alerta_box_open){
		if(alerta_box_mudou_tit){
			$('#alerta_box_header').html(padrao_tit);
			alerta_box_mudou_tit = false;
		}
		
		if(alerta_box_mudou_ok){
			$('#alerta_box_botao').html(padrao_bot);
			alerta_box_mudou_ok = false;
		}
		
		if(titulo || variaveis_js.alerta_tit_padrao){
			$('#alerta_box_header').html((titulo?titulo:variaveis_js.alerta_tit_padrao));
			alerta_box_mudou_tit = true;
		}
		
		if(botao || variaveis_js.alerta_botao_padrao){
			$('#alerta_box_botao').html((botao?botao:variaveis_js.alerta_botao_padrao));
			alerta_box_mudou_ok = true;
		}
		
		alerta_box.center();
		alerta_box_shadow.css('z-index','9998');
		alerta_box.css('z-index','9999');
		alerta_box_texto.html(texto);
		alerta_box_shadow.fadeIn(tempo_animacao,function(){$(this).css('z-index','9998');});
		alerta_box.fadeIn(tempo_animacao,function(){$(this).center();$(this).css('z-index','9999');});
		
		alerta_box_open = true;
	}
};

$.alerta_close = function(){
	alerta_box_shadow.fadeOut(tempo_animacao);
	alerta_box.fadeOut(tempo_animacao);
	alerta_box_open = false;
};

$.fn.image = function(src, f){
	return this.each(function(){
		var i = new Image();
				i.src = src;
				i.onload = f;
				this.appendChild(i);
		});
}

$(document).ready(function(){
	var versao = '2.9.2';
	var animar,banner;
	var site_raiz = variaveis_js.site_raiz;
	var site_teste = variaveis_js.site_teste;
	var audio_path = variaveis_js.audio_path;
	var opcao_atual = variaveis_js.opcao_atual;
	var menu_dinamico_inicial = variaveis_js.menu_dinamico_inicial;
	var cont_principal = '#cont_principal';
	var cont_secundario = '#cont_secundario';
	var menu_paginas_inicio = '#menu_paginas';
	var menu_anterior = '';
	var history_flag = true;
	var history_first = true;
	var history_1st_access = true;
	var menu_pagina = 1;
	var menu_paginas = (variaveis_js.menu_paginas?variaveis_js.menu_paginas:0);
	var tempo_animacao2 = 150;
	var tempo_animacao3 = 300;
	var fadeIn = false;
	var showSubMenu = false;
	var all_js_ativo = true;
	var urls = Array();
	var flags = Array();
	var menus = Array();
	var menus2 = Array();
	var recaptcha_public_key = variaveis_js.recaptcha_public_key;
	var link_clicked = false;
	var scripts_loaded = Array();
	var stylesheets_loaded = Array();
	
	if(history_1st_access){
		if($.browser.msie){
			var arr = location.href.split('?&_suid');
			
			if(arr.length > 1){
				var arr2;
				var end = arr[0];
				var id;
				var id_aux;
				var host_aux;
				
				arr2 = end.split('#./');
				if(arr2[1]){
					id = arr2[1];
				} else {
					arr2 = end.split('#/');
					if(arr2[1]){
						id = arr2[1];
					} else {
						arr2 = end.split('#');
						id = arr2[1];
					}
				}
				
				var patt = new RegExp(site_raiz,'gi');
				var patt2 = new RegExp(site_raiz.replace('/',''),'gi');
				
				id_aux = id.replace(patt,'');
				id_aux = id_aux.replace(patt2,'');
				host_aux = location.hostname.replace(patt,'');
				
				all_js_ativo = false;
				window.open(document.location.protocol+'//'+host_aux+site_raiz+id_aux,'_self');
			}
			history_1st_access = false;
		}
	}
	
	if(window._gaq && site_teste != 'sim' && !variaveis_js.noindex)window._gaq.push(['_trackPageview', location.href]);
	
	if(!all_js_ativo)return;
	
	window.link_trigger = $("<a></a>");
	window.link_trigger.appendTo('body');
	window.link_trigger.hide();

	if(variaveis_js.ler_css){
		ler_css(variaveis_js.ler_css);
	}
	if(variaveis_js.ler_scripts){
		ler_scripts(variaveis_js.ler_scripts);
	}
	
	menus2[0] = menus['procurar'] = 'menu_procurar';
	menus2[1] = menus['blog'] = 'menu_blog';
	menus2[2] = menus['noticias'] = 'menu_noticias';
	
	flags['scroll'] = false;
	
	if(menu_dinamico_inicial){
		if(menu_dinamico_inicial.length > 0){
			menus2[menus2.length] = menus[menu_dinamico_inicial] = 'menu_'+menu_dinamico_inicial;
			flags['scroll'] = true;
		}
	}
	
	if(menus[opcao_atual]){
		opcao_atual = menus[opcao_atual];
		flags['scroll'] = true;
	}
	
	flags['scroll2'] = true;
	
	// ======================== KeyUp and KeyDown ==================================
	
	$(document).keydown(function(e) {
		if(e.keyCode == 17 || e.keyCode == 16) {
			global_vars.ctrl_ativo = true;
		}
	});
	
	$(document).keyup(function(e) {
		if(login_box_open) { 
			if(e.keyCode == 27) { 
				login_close();
			}
		}
		
		if(janela.open){
			if(e.keyCode == 27) { 
				janela_close(null);
			}
		}
		
		if(janela_custom.open){
			if(e.keyCode == 27) { 
				janela_custom_close(null);
			}
		}
		
		if(alerta_box_open){
			if(e.keyCode == 27) { 
				$.alerta_close();
			}
		}
		
		if(e.keyCode == 17 || e.keyCode == 16) {
			global_vars.ctrl_ativo = false;
		}
	});
	
	// ======================== Login ==================================
	
	var login_box_open = false;
	var login_box;
	var login_box_shadow;
	
	$('#login_box_shadow,#login_box_botao,#login_box_fechar,#esqueceu-senha').live('click touchstart',function(){
		login_close();
	});
	
	function login_open(){
		if(!login_box_open){
			login_box.center();
			login_box_shadow.css('z-index','9996');
			login_box.css('z-index','9997');
			if($.browser.msie){setTimeout(function(){login_box_shadow.fadeIn(tempo_animacao,function(){$(this).css('z-index','9996');});},50);} else {login_box_shadow.fadeIn(tempo_animacao,function(){$(this).css('z-index','9996');});}
			login_box.fadeIn(tempo_animacao,function(){$(this).center();$(this).css('z-index','9997');});
			login_box_open = true;
		}
	}
	
	function login_close(){
		login_box_shadow.fadeOut(tempo_animacao,function(){
			login_box_shadow.remove();
			login_box_open = false;
		});
		login_box.fadeOut(tempo_animacao,function(){
			login_box.remove();
			login_box_open = false;
		});
	}
	
	function login(){
		login_box_shadow.css('width','100%');
		login_box_shadow.css('height',$("body").height() > $(document).height() ? $("body").height() : $(document).height());
		
		$("#login_box input#usuario").focus();
		
		login_open();
		$('body').scrollTop(0);
		$('html').scrollTop(0);
		$(document).scrollTop(0);
	}
	
	// ======================== Janela ==================================
	
	var janela = {};
	
	janela.shadow = $("<div></div>");
	janela.margin_bottom = $("<div></div>");
	janela.open = false;
	janela.first_open = true;
	janela.margin = 50;
	janela.doc_height = jQuery(document).height();
	
	janela.shadow.attr('id','ajax_janela_shadow');
	janela.margin_bottom.attr('id','ajax_margin_bottom');
	
	janela.margin_bottom.appendTo("body");
	janela.shadow.appendTo("body");
	
	janela.shadow.css('width','100%');
	janela.shadow.css('height',$("body").height() > janela.doc_height ? $("body").height() : janela.doc_height);
	
	$('#ajax_janela_shadow,#ajax_janela_fechar').live('click touchstart',function(){
		janela_close(null);
	});
	
	function janela_open(){
		janela.fechar = $("<div></div>");
		janela.fechar.attr('id','ajax_janela_fechar');
		janela.fechar.appendTo($(cont_principal));
		
		if(!janela.open){		
			$('body').scrollTop(0);
			$('html').scrollTop(0);
			$(document).scrollTop(0);
			
			janela.shadow.css('z-index','9994');
			$(cont_principal).css('z-index','9995');
			
			if($.browser.msie){setTimeout(function(){janela.shadow.fadeIn(tempo_animacao,function(){$(this).css('z-index','9994');});},50);} else {janela.shadow.fadeIn(tempo_animacao,function(){$(this).css('z-index','9994');});}
			$(cont_principal).fadeIn(tempo_animacao,function(){$(this).css('z-index','9995');});
			
			$(cont_principal).css({
				position:	'absolute', 
				left:		'50%', 
				top:		janela.margin + 'px', 
				zIndex:		'9995'
			}).css({
				marginLeft:	'-' + ($(cont_principal).outerWidth() / 2) + 'px',
			});


			$(cont_principal).css({
				marginTop:	parseInt($(cont_principal).css('marginTop'), 10) + jQuery(window).scrollTop(), 
				marginLeft:	parseInt($(cont_principal).css('marginLeft'), 10) + jQuery(window).scrollLeft()
			});
			
			if(janela.first_open){
				janela.first_open = false;
				janela.doc_height = jQuery(document).height();
			}
			
			janela.margin_bottom.css({
				position:	'absolute', 
				left:		'0px',
				top:		($(cont_principal).outerHeight(true)+(2*janela.margin) > janela.doc_height ? janela.doc_height + janela.margin + parseInt(ajax_vars.janela_correcao_margin) : 0)+'px',
				zIndex:		'9995',
				width: 		'100%',
				height: 	'1px'
			});
			
			janela.open = true;
		}
	}
	
	function janela_close(params){
		if(!params) params = new Array();
		if(janela.open){
			janela.shadow.fadeOut(tempo_animacao,function(){
				janela.open = false;
			});
			if(params.history){
				$(cont_principal).css({
					position:	'static', 
					left:		'auto', 
					top:		'auto', 
					zIndex:		'auto',
					marginTop:		'auto',
					marginLeft:		'auto'
				});
			} else {
				$(cont_principal).fadeOut(tempo_animacao,function(){
					$(cont_principal).css({
						position:	'static', 
						left:		'auto', 
						top:		'auto', 
						zIndex:		'auto',
						marginTop:		'auto',
						marginLeft:		'auto'
					});
					
					janela.open = false;
				});
			}
		}
	}
	
	// ======================== Janela Custom  ==================================
	
	var janela_custom = {};
	
	janela_custom.shadow = $("<div></div>");
	janela_custom.margin_bottom = $("<div></div>");
	janela_custom.open = false;
	janela_custom.first_open = true;
	janela_custom.margin = 50;
	janela_custom.doc_height = jQuery(document).height();
	janela_custom.data = new Array();
	
	janela_custom.shadow.attr('id','ajax_janela_custom_shadow');
	janela_custom.margin_bottom.attr('id','ajax_margin_bottom');
	
	janela_custom.margin_bottom.appendTo("body");
	janela_custom.shadow.appendTo("body");
	
	janela_custom.shadow.css('width','100%');
	janela_custom.shadow.css('height',$("body").height() > janela_custom.doc_height ? $("body").height() : janela_custom.doc_height);
	janela_custom.shadow.hide();
	
	$('#ajax_janela_custom_shadow,#ajax_janela_custom_fechar').live('click touchstart',function(){
		janela_custom_close(null);
	});
	
	$('.ajax_janela_custom').live('click touchstart',function(){
		janela_custom.data['id'] = $(this).attr('data-id');
		janela_custom.data['width'] = $(this).attr('data-width');
		janela_custom.data['height'] = $(this).attr('data-height');
		janela_custom.obj = $("#"+janela_custom.data['id']);
		
		janela_custom.obj.addClass('ajax_janela');
		
		janela_custom_open();
	});
	
	function janela_custom_open(){
		janela_custom.fechar = $("<div></div>");
		janela_custom.fechar.attr('id','ajax_janela_custom_fechar');
		janela_custom.fechar.appendTo(janela_custom.obj);
		
		if(!janela_custom.open){		
			$('body').scrollTop(0);
			$('html').scrollTop(0);
			$(document).scrollTop(0);
			
			if(janela_custom.data['width'])janela_custom.obj.css('width',janela_custom.data['width']);
			if(janela_custom.data['height'])janela_custom.obj.css('height',janela_custom.data['height']);
			
			janela_custom.shadow.css('z-index','9994');
			janela_custom.obj.css('z-index','9995');
			
			if($.browser.msie){setTimeout(function(){janela_custom.shadow.fadeIn(tempo_animacao,function(){$(this).css('z-index','9994');});},50);} else {janela_custom.shadow.fadeIn(tempo_animacao,function(){$(this).css('z-index','9994');});}
			janela_custom.obj.fadeIn(tempo_animacao,function(){$(this).css('z-index','9995');});
			
			janela_custom.obj.css({
				position:	'absolute', 
				left:		'50%', 
				top:		janela_custom.margin + 'px', 
				zIndex:		'9995'
			}).css({
				marginLeft:	'-' + (janela_custom.obj.outerWidth() / 2) + 'px',
			});


			janela_custom.obj.css({
				marginTop:	parseInt(janela_custom.obj.css('marginTop'), 10) + jQuery(window).scrollTop(), 
				marginLeft:	parseInt(janela_custom.obj.css('marginLeft'), 10) + jQuery(window).scrollLeft()
			});
			
			if(janela_custom.first_open){
				janela_custom.first_open = false;
				janela_custom.doc_height = jQuery(document).height();
			}
			
			janela_custom.margin_bottom.css({
				position:	'absolute', 
				left:		'0px',
				top:		(janela_custom.obj.outerHeight(true)+(2*janela_custom.margin) > janela_custom.doc_height ? janela_custom.doc_height + janela_custom.margin + parseInt(ajax_vars.janela_correcao_margin) : 0)+'px',
				zIndex:		'9995',
				width: 		'100%',
				height: 	'1px'
			});
			
			janela_custom.open = true;
		}
	}
	
	function janela_custom_close(params){
		if(!params) params = new Array();
		if(janela_custom.open){
			janela_custom.shadow.fadeOut(tempo_animacao,function(){
				janela_custom.open = false;
			});
			if(params.history){
				janela_custom.obj.css({
					position:	'static', 
					left:		'auto', 
					top:		'auto', 
					zIndex:		'auto',
					marginTop:		'auto',
					marginLeft:		'auto'
				});
			} else {
				janela_custom.obj.fadeOut(tempo_animacao,function(){
					janela_custom.obj.css({
						position:	'static', 
						left:		'auto', 
						top:		'auto', 
						zIndex:		'auto',
						marginTop:		'auto',
						marginLeft:		'auto'
					});
					
					janela_custom.open = false;
				});
			}
		}
	}
	
	// ======================== Alerta ==================================
	
	alerta = $("#alerta");
	alerta_php = $("#alerta_php");
	alerta_box = $("#alerta_box");
	alerta_box_shadow = $("#alerta_box_shadow");
	alerta_box_texto = $("#alerta_box_texto");
	
	alerta_box.center();
	
	alerta_box_shadow.css('width','100%');
	alerta_box_shadow.css('height',$("body").height() > $(document).height() ? $("body").height() : $(document).height());
	
	$('#alerta_box_shadow,#alerta_box_botao,#alerta_box_fechar').live('click touchstart',function(){
		$.alerta_close();
	});
	
	$('#alerta_box_fechar').live({
		mouseenter: function(){
			$('body').css('cursor', 'pointer');
		},
		mouseleave: function(){
			$('body').css('cursor', 'default'); 
		}
	});
	
	if(alerta.html()){
		alerta_box.center();
		alerta_box_shadow.css('z-index','9998');
		alerta_box.css('z-index','9999');
		alerta_box_texto.html(alerta.html());
		alerta_box_shadow.fadeIn(tempo_animacao,function(){$(this).css('z-index','9998');});
		alerta_box.fadeIn(tempo_animacao,function(){$(this).center();$(this).css('z-index','9999');});
	}
	
	if(alerta_php.html()){
		alerta_box.center();
		alerta_box_shadow.css('z-index','9998');
		alerta_box.css('z-index','9999');
		alerta_box_texto.html(alerta_php.html());
		alerta_box_shadow.fadeIn(tempo_animacao,function(){$(this).css('z-index','9998');});
		alerta_box.fadeIn(tempo_animacao,function(){$(this).center();$(this).css('z-index','9999');});
	}
	
	// ======================== Início Search ==================================
	
	if($(menu_paginas_inicio).val()){
		menu_paginas = $(menu_paginas_inicio).val();
		flags['scroll'] = true;
	}
	
	// ======================== Scripts Iniciais ==================================
	
	function aplicar_scripts_iniciais(){
		if($("#_usuario-login-logout").length){
			var usuario_nome = variaveis_js.usuario_nome;
			var login_logout_url = variaveis_js.login_logout_url;
			var logar = (variaveis_js.logout_txt?variaveis_js.logout_txt:'Bem vindo, <span id="_usuario-login-logout-txt">faça seu login</span> para fazer suas compras');
			var logado = (variaveis_js.login_txt?variaveis_js.login_txt:'Bem vindo, #usuario# - <span id="_usuario-login-logout-txt">Sair</span>');
			
			if(variaveis_js.permissao){
				if(usuario_nome.length > 30){
					usuario_nome = usuario_nome.substr(0, 29) + '...';
				}
				logado = logado.replace(/#usuario#/gi,usuario_nome);
				
				$("#_usuario-login-logout").html(logado);
				$("#_usuario-login-logout").attr('data-url','logout');
			} else {
				$("#_usuario-login-logout").html(logar);
				$("#_usuario-login-logout").attr('data-url',login_logout_url);
			}
			
			$("#_usuario-login-logout-txt").live('click touchstart',function(){
				window.link_trigger.attr('href',document.location.protocol+'//'+document.location.hostname+site_raiz+$("#_usuario-login-logout").attr('data-url')).trigger('click');
			});
		}
	}
	
	aplicar_scripts_iniciais();
	
	// ======================== Scripts Dinâmicos ==================================
	
	function aplicar_scripts_after(params){
		if(menu_principal_flag){
			menu_principal();
		}
		
		if($("#_background_video").length){
			if(!video_primeira_exec){
				video_trigger();
			}
		}
		
		$.projeto_aplicar_scripts_after(params);
		$.aplicar_scripts_after(params);
	}
	
	function aplicar_scripts(params){
		if(!params) params = new Array();
		if(!params.href)params.href = location.href;
		
		if($.browser.msie){
			var href_aux = params.href;
			var href_arr = href_aux.split('#.');
			params.href = href_arr[0];
		}
		
		if(ajax_vars.janela || params.janela){
			$(cont_principal).addClass('ajax_janela');
			janela_open();
		} else if(janela.open){
			$(cont_principal).removeClass('ajax_janela');
			janela_close(params);
			$(cont_principal).show();
		} else {
			$(cont_principal).removeClass('ajax_janela');
			$(cont_principal).show();
		}
		
		janela_custom.open = false;
		
		if($("#login_box").length){
			login_box_shadow = $("#login_box_shadow");
			login_box = $("#login_box");
			login_box_shadow.appendTo("body");
			login_box.appendTo("body");
			if(document.location.protocol == 'http:' && ajax_vars.ambiente_seguro && document.location.hostname != 'localhost')
				document.location.protocol = 'https:';
			login();
		}
		
		if($("a[rel^='prettyPhoto']").length){
			var prettyphoto_var = {animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false, twitter: true, facebook: true};
			
			if(projeto_js)
			if(projeto_js.prettyphoto)
				prettyphoto_var = projeto_js.prettyphoto;
			
			setTimeout(function(){ $("a[rel^='prettyPhoto']").prettyPhoto(prettyphoto_var); }, 100);
		}
		
		// ================================== Glossário ========================
		
		if(ajax_vars.glossario){
			setTimeout(function(){ $('#conteudo').glossary(document.location.protocol+'//'+document.location.hostname+site_raiz+'index.php?ajax=sim&glossario=sim',{ignorecase:true,excludetags:["a","h1","h2","h3"]}); }, 1000);
		}
		
		// ================================== Facebook Comments ========================
		
		if($("#facebook-commentarios").length){
			if(global_vars.fb_sdk){
				window.FB.XFBML.parse();
			} 
		}
		
		global_vars.fb_sdk = true;
		
		// ================================== Image Zoom ========================
		
		if($("#_elevateZoom").length){
			$("#_elevateZoom").elevateZoom({gallery:'_elevateZoom-gallery', cursor: 'pointer', galleryActiveClass: 'active', imageCrossfade: true, loadingIcon: '../../images/icons/lendo2.gif'});
			
			$("#_elevateZoom").bind("click", function(e) { var ez = $('#_elevateZoom').data('elevateZoom');	$.fancybox(ez.getGalleryList()); return false; });
		}
		
		// ================================== ColorBox ========================
		
		if($("._colorbox").length){
			$("._colorbox").colorbox({inline:true,width:'500px'});
			
			$(document).bind('cbox_open', function(){
				var element = $.colorbox.element();
				if(element.hasClass('_servico-duvidas')){
					$('#recaptcha_div').appendTo('#recaptcha_div_duvidas');
				} else if(element.hasClass('_servico-indique')){
					$('#recaptcha_div').appendTo('#recaptcha_div_indique');
				}
			});
			
			$(document).bind('cbox_complete', function(){
				global_vars.colorbox_open = true;
			});
			
			$(document).bind('cbox_closed', function(){
				global_vars.colorbox_open = false;
			});
		}
		
		// ================================== jPlayer ========================
	
		if($("#jquery_jplayer_1").length){
			$("#jquery_jplayer_1").jPlayer({
				ready: function () {
				$(this).jPlayer("setMedia", {
					mp3: site_raiz+audio_path
					});
				},
				swfPath: site_raiz+"includes/js/jPlayer/jquery.jplayer/",
				supplied: "mp3"
			});
		}
		
		// ================================== Esqueceu Senha ========================
		
		if($('#esqueceu_senha').length){
			Recaptcha.create(ajax_vars.recaptcha_public_key, 'recaptcha_div', {
				lang : 'pt',
				theme: "clean"
			});
			
			$("#esqueceu_senha").bind('submit',function(){
				var enviar = true;
				var campo;
				var post;
				var mens;
				var campos = Array();
				var posts = Array();
				var opcao = 'esqueceu_senha_banco';
				var form_id = 'esqueceu_senha';
				var href = '';
				var limpar_campos = true;
				var mudar_pagina = false;
				
				campo = "esqueceu_senha-email"; mens = "É obrigatório definir o E-mail!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				
				// Checar email
				campo = "esqueceu_senha-email"; mens = "E-mail inválido, preencha o campo de e-mail válido!"; if(!checkMail($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				
				if(!enviar){
					return false;
				} else {
					$.ajax({
						type: 'POST',
						url: url_name(),
						data: { ajax : 'sim' , recaptcha : 'sim' , recaptcha_challenge_field : Recaptcha.get_challenge() , recaptcha_response_field : Recaptcha.get_response() },
						beforeSend: function(){
							$('#ajax_lendo').fadeIn(tempo_animacao);
						},
						success: function(txt){
							$('#ajax_lendo').fadeOut(tempo_animacao);
							
							if(txt == 'sim'){
								form_serialize = $('#'+form_id).serialize();
								$('#'+form_id)[0].reset();
								enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
							} else {
								Recaptcha.reload();
								mens = "<p>Código de validação <b style=\"color:red;\">inválido</b>!<p></p>Favor preencher o código de validação novamente!</p>";
								$.alerta_open(mens,false,false);
							}
						},
						error: function(txt){
							
						}
					});
				}
				
				return false;
			});
		}
		
		// ================================== Redefinir Senha ========================
		
		if($('#redefinir_senha').length){
			$("#cadastro-senha").blur(function() {
				validar_senha();
			});
			
			$("#cadastro-senha2").blur(function() {
				validar_senha();
			});
			
			$("#cadastro-senha").keyup(function(eventObject){
				var perc;
				var bpos;
				
				if(eventObject.keyCode != 9){
					if($('#cadastro-usuario').val()){
						$('#result').html(passwordStrength($('#cadastro-senha').val(),$('#cadastro-usuario').val())) ; 
						perc = passwordStrengthPercent($('#cadastro-senha').val(),$('#cadastro-usuario').val());
						
						bpos=" $('#colorbar').css( {backgroundPosition: \"0px -" ;
						bpos = bpos + perc + "px";
						bpos = bpos + "\" } );";
						bpos=bpos +" $('#colorbar').css( {width: \"" ;
						bpos = bpos + (perc * 1.9) + "px";
						bpos = bpos + "\" } );";
						eval(bpos);
							$('#percent').html(" " + perc  + "% ");
					} else {
						$.alerta_open("<p>Defina o usuário antes da senha!</p>",false,false);
					}
				}
			});
			
			$("#redefinir_senha").bind('submit',function(){
				var enviar = true;
				var campo;
				var post;
				var mens;
				var campos = Array();
				var posts = Array();
				var opcao = 'redefinir_senha_banco';
				var form_id = 'redefinir_senha';
				var href = '';
				var limpar_campos = true;
				var mudar_pagina = false;
				
				campo = "cadastro-senha"; mens = "É obrigatório definir a Senha!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				campo = "cadastro-senha2"; mens = "É obrigatório definir o Redigite a Senha!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				
				if(
					!cadastrar_senha
				){ mens = "É necessário validar os campos antes de enviar!"; $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				
				if(!enviar){
					return false;
				} else {
					form_serialize = $('#'+form_id).serialize();
					$('#'+form_id)[0].reset();
					enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
				}
				
				return false;
			});
		}
		
		// ================================== Cadastro Usuário ========================
		
		if($('#form_usuario').length){
			$(".cep").mask("99.999-999");
			$(".telefone").mask("(99) 9999-9999");
			
			Recaptcha.create(recaptcha_public_key, 'recaptcha_div', {
				lang : 'pt',
				theme: "white"
			});
			
			$("#cadastro-usuario").blur(function() {
				if($("#cadastro-usuario").val()){
					$.ajax({
						type: 'POST',
						url: url_name(),
						data: { ajax : 'sim' , usuario : ($("#cadastro-usuario").val()?$("#cadastro-usuario").val():'') , edit_usuario : ($("#edit_usuario").val()?$("#edit_usuario").val():'') },
						beforeSend: function(){
							$('#ajax_lendo').fadeIn(tempo_animacao);
						},
						success: function(txt){
							$('#ajax_lendo').fadeOut(tempo_animacao);
							var valido = true;
							var mens = "";
							var cor;
							
							if(checkStr($("#cadastro-usuario").val())){
								valido = false;
								mens = $("#d_caracter_in").html();
								$.alerta_open(mens,false,false);
							}
							
							if(!limites_str($("#cadastro-usuario").val(),3,20)){
								valido = false;
								mens = $("#d_caracter").html();
								$.alerta_open(mens,false,false);
							}
							
							if(txt == 'sim'){
								valido = false;
								mens = $("#d_usuario").html();
								$.alerta_open(mens,false,false);
							}
							
							$("#mens_usuario").removeClass('ui-state-highlight');
							$("#cadastro-usuario").removeClass('input-vazio');
							$("#mens_usuario").removeClass('input-vazio');
							
							if(!valido){
								cor = cor1;
								cadastrar_usuario = false;
								$("#cadastro-usuario").addClass('input-vazio');
								$("#mens_usuario").addClass('input-vazio');
							} else {
								mens = "Usuário OK.";
								cor = cor2;
								cadastrar_usuario = true;
								$("#mens_usuario").addClass('ui-state-highlight');
								$("#cadastro-senha").focus();
							}
							
							$("#mens_usuario").html(mens);
						},
						error: function(txt){
							
						}
					});
				} else {
					cadastrar_usuario = false;
					$("#cadastro-usuario").addClass('input-vazio');
					$("#mens_usuario").addClass('input-vazio');
					$("#mens_usuario").html("Preencha o usuário!");
				}
			});
			
			$("#cadastro-senha").blur(function() {
				validar_senha();
			});
			
			$("#cadastro-senha2").blur(function() {
				validar_senha();
			});
			
			$("#cadastro-email").blur(function() {
				if($("#cadastro-email").val()){
					var mail = $("#cadastro-email").val();
					var mens;
					var cor;
					
					$("#mens_email").removeClass('ui-state-highlight');
					$("#cadastro-email").removeClass('input-vazio');
					$("#mens_email").removeClass('input-vazio');
					
					if(!checkMail(mail)){
						mens = "E-mail inválido.";
						$.alerta_open(mens,false,false);
						cor = cor1;
						cadastrar_usuario = false;
						$("#cadastro-email").addClass('input-vazio');
						$("#mens_email").addClass('input-vazio');
					} else {
						$.ajax({
							type: 'POST',
							url: url_name(),
							data: { ajax : 'sim' , email : ($("#cadastro-email").val()?$("#cadastro-email").val():'') , edit_email : ($("#edit_email").val()?$("#edit_email").val():'') },
							beforeSend: function(){
								$('#ajax_lendo').fadeIn(tempo_animacao);
							},
							success: function(txt){
								$('#ajax_lendo').fadeOut(tempo_animacao);
								var valido = true;
								var mens = "";
								var cor;
								
								if(txt == 'sim'){
									valido = false;
									mens = $("#d_email").html();
									$.alerta_open(mens,false,false);
								}
								
								if(!valido){
									cor = cor1;
									cadastrar_usuario = false;
									$("#cadastro-email").addClass('input-vazio');
									$("#mens_email").addClass('input-vazio');
								} else {
									mens = "E-mail OK.";
									cor = cor2;
									cadastrar_usuario = true;
									$("#mens_email").addClass('ui-state-highlight');
									$("#nome").focus();
								}
								
								$("#mens_email").html(mens);
							},
							error: function(txt){
								
							}
						});
					}
					
					$("#mens_email").html(mens);
					
				} else {
					cadastrar_usuario = false;
					$("#cadastro-email").addClass('input-vazio');
					$("#mens_email").addClass('input-vazio');
					$("#mens_email").html("Preencha o e-mail!");
				}
			});
			
			$("#cadastro-senha").keyup(function(eventObject){
				var perc;
				var bpos;
				
				if(eventObject.keyCode != 9){
					if($('#cadastro-usuario').val()){
						$('#result').html(passwordStrength($('#cadastro-senha').val(),$('#cadastro-usuario').val())) ; 
						perc = passwordStrengthPercent($('#cadastro-senha').val(),$('#cadastro-usuario').val());
						
						bpos=" $('#colorbar').css( {backgroundPosition: \"0px -" ;
						bpos = bpos + perc + "px";
						bpos = bpos + "\" } );";
						bpos=bpos +" $('#colorbar').css( {width: \"" ;
						bpos = bpos + (perc * 1.9) + "px";
						bpos = bpos + "\" } );";
						eval(bpos);
							$('#percent').html(" " + perc  + "% ");
					} else {
						$.alerta_open("<p>Defina o usuário antes da senha!</p>",false,false);
					}
				}
			});
			
			$("#cadastro-botao").bind('click touchstart',function(){
				var enviar = true;
				var campo;
				var post;
				var mens;
				var campos = Array();
				var posts = Array();
				var opcao = 'cadastro_banco';
				var form_id = 'form_usuario';
				var href = '';
				var limpar_campos = true;
				var mudar_pagina = true;
				
				campo = "cadastro-usuario"; mens = "É obrigatório definir o Usuário!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				campo = "cadastro-senha"; mens = "É obrigatório definir a Senha!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				campo = "cadastro-senha2"; mens = "É obrigatório definir o Redigite a Senha!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				campo = "cadastro-email"; mens = "É obrigatório definir o E-mail!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				
				// Checar email
				campo = "cadastro-email"; mens = "E-mail inválido, preencha o campo de e-mail válido!"; if(!checkMail($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				
				campo = "cadastro-nome"; mens = "É obrigatório definir o Nome!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				
				if(
					!cadastrar_usuario ||
					!cadastrar_senha
				){ mens = "É necessário validar os campos antes de enviar!"; $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				
				if(!enviar){
					return false;
				} else {
					$.ajax({
						type: 'POST',
						url: url_name(),
						data: { ajax : 'sim' , recaptcha : 'sim' , recaptcha_challenge_field : Recaptcha.get_challenge() , recaptcha_response_field : Recaptcha.get_response() },
						beforeSend: function(){
							$('#ajax_lendo').fadeIn(tempo_animacao);
						},
						success: function(txt){
							$('#ajax_lendo').fadeOut(tempo_animacao);
							
							if(txt == 'sim'){
								form_serialize = $('#'+form_id).serialize();
								enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
							} else {
								Recaptcha.reload();
								mens = "<p>Código de validação <b style=\"color:red;\">inválido</b>!<p></p>Favor preencher o código de validação novamente!</p>";
								$.alerta_open(mens,false,false);
							}
						},
						error: function(txt){
							
						}
					});
				}
			});
			
		}
		
		// ============================== Comentários ==================
		
		if($('#form_comentarios').length){
			Recaptcha.create(recaptcha_public_key, 'recaptcha_div', {
				lang : 'pt',
				theme: "white"
			});
			
			$('.comentarios-responder').click(function(){
				$(this).after($('#comentarios-form-cont'));
				$('#comentario-cancelar').show();
				$('#comentarios-pai').val($(this).attr('data'));
			});
			
			$('#comentario-cancelar').live( 'click' , function(){
				$('#comentario-posicao-form').after($('#comentarios-form-cont'));
				$(this).hide();
				$('#comentarios-pai').val('');
			});
			
			$("#comentario-form-botao").bind('click touchstart',function(){
				var enviar = true;
				var campo;
				var post;
				var mens;
				var campos = Array();
				var posts = Array();
				var opcao = 'comentarios_banco';
				var form_id = 'form_comentarios';
				var href = '';
				var limpar_campos = true;
				var mudar_pagina = false;
				
				campo = "comentarios-nome"; mens = "É obrigatório definir o Nome!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				campo = "comentarios-email"; mens = "É obrigatório definir o E-mail!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				campo = "comentarios-conteudo"; mens = "É obrigatório definir o Comentário!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				
				// Checar email
				campo = "comentarios-email"; mens = "E-mail inválido, preencha o campo de e-mail válido!"; if(!checkMail($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				
				if(!enviar){
					return false;
				} else {
					$.ajax({
						type: 'POST',
						url: url_name(),
						data: { ajax : 'sim' , recaptcha : 'sim' , recaptcha_challenge_field : Recaptcha.get_challenge() , recaptcha_response_field : Recaptcha.get_response() },
						beforeSend: function(){
							$('#ajax_lendo').fadeIn(tempo_animacao);
						},
						success: function(txt){
							$('#ajax_lendo').fadeOut(tempo_animacao);
							
							if(txt == 'sim'){
								form_serialize = $('#'+form_id).serialize();
								enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
								$('#'+form_id)[0].reset();
								$('#comentario-posicao-form').after($('#comentarios-form-cont'));
								$('#comentario-cancelar').hide();
								$('#comentarios-pai').val('');
								var top = $(cont_secundario,document.body).offset().top;
								Recaptcha.reload();
								$(window).scrollTop(top);
							} else {
								Recaptcha.reload();
								mens = "<p>Código de validação <b style=\"color:red;\">inválido</b>!<p></p>Favor preencher o código de validação novamente!</p>";
								$.alerta_open(mens,false,false);
							}
						},
						error: function(txt){
							
						}
					});
				}
			});
		}
		
		// ============================== E-commerce ==================
		
		if($('#_indique-form').length){
			Recaptcha.create(ajax_vars.recaptcha_public_key, 'recaptcha_div', {
				lang : 'pt',
				theme: "clean",
				callback: recaptcha_eventos
			});
			
			$(".telefone").mask("(99) 9999-9999?9");
			
			function recaptcha_eventos(){
				$("#recaptcha_reload_btn,#recaptcha_switch_audio_btn,#recaptcha_whatsthis_btn,#recaptcha_switch_img_btn").bind('click touchstart',function(e){
					e.stopPropagation();					
				});
			}
			
			$("#_indique-enviar").bind('click touchstart',function(){
				var enviar = true;
				var campo;
				var post;
				var mens;
				var campos = Array();
				var posts = Array();
				var form_id = '_indique-form';
				var opcao = '';
				var href = '';
				var limpar_campos = true;
				var mudar_pagina = false;
				
				campo = '_indique-nome'; mens = "&Eacute; obrigat&oacute;rio definir o Seu Nome!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				campo = '_indique-email'; mens = "&Eacute; obrigat&oacute;rio definir o Seu E-mail!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				campo = '_indique-nome_amigo'; mens = "&Eacute; obrigat&oacute;rio definir o Nome amigo(a)!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				campo = '_indique-email_amigo'; mens = "&Eacute; obrigat&oacute;rio definir o E-mail amigo(a)!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				
				// Checar email
				campo = '_indique-email'; mens = "E-mail inv&aacute;lido, preencha o campo de e-mail v&aacute;lido!"; if(!checkMail($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				campo = '_indique-email_amigo'; mens = "E-mail inv&aacute;lido, preencha o campo de e-mail v&aacute;lido!"; if(!checkMail($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				
				if(!enviar){
					return false;
				} else {
					$.ajax({
						type: 'POST',
						url: url_name(),
						data: { ajax : 'sim' , recaptcha : 'sim' , recaptcha_challenge_field : Recaptcha.get_challenge() , recaptcha_response_field : Recaptcha.get_response() },
						beforeSend: function(){
							$('#ajax_lendo').fadeIn(tempo_animacao);
						},
						success: function(txt){
							$('#ajax_lendo').fadeOut(tempo_animacao);
							
							if(txt == 'sim'){
								window.form_serialize = $('#'+form_id).serialize();
								window.enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
								$('#'+form_id)[0].reset();
								cadastrar_usuario = false;
								cadastrar_senha = false;
							} else {
								Recaptcha.reload();
								mens = "<p>C&oacute;digo de valida&ccedil;&atilde;o <b style=\"color:red;\">inv&aacute;lido</b>!<p></p>Favor preencher o c&oacute;digo de valida&ccedil;&atilde;o novamente!</p>";
								$.alerta_open(mens,false,false);
							}
						},
						error: function(txt){
							
						}
					});
				}
			});
			
			function checkMail(mail){
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
			}
			
			function url_name(){
				var url_aux = location.pathname;
				var url_parts;
				
				url_parts = url_aux.split('/');
				
				if(url_parts[url_parts.length-1])
					return url_parts[url_parts.length-1];
				else
					return '.';
			}
		}
		
		if($('#_duvidas-form').length){
			/* Recaptcha.create(ajax_vars.recaptcha_public_key, 'recaptcha_div2', {
				lang : 'pt',
				theme: "clean"
			}); */
			
			$(".telefone").mask("(99) 9999-9999?9");
			
			$("#_duvidas-enviar").bind('click touchstart',function(){
				var enviar = true;
				var campo;
				var post;
				var mens;
				var campos = Array();
				var posts = Array();
				var form_id = '_duvidas-form';
				var opcao = '';
				var href = '';
				var limpar_campos = true;
				var mudar_pagina = false;
				
				campo = '_duvidas-nome'; mens = "&Eacute; obrigat&oacute;rio definir o Seu Nome!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				campo = '_duvidas-email'; mens = "&Eacute; obrigat&oacute;rio definir o E-mail!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				campo = '_duvidas-telefone'; mens = "&Eacute; obrigat&oacute;rio definir o Telefone!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				campo = '_duvidas-cidade'; mens = "&Eacute; obrigat&oacute;rio definir a Cidade!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				campo = '_duvidas-duvida'; mens = "&Eacute; obrigat&oacute;rio definir a D&uacute;vida!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				
				// Checar email
				campo = '_duvidas-email'; mens = "E-mail inv&aacute;lido, preencha o campo de e-mail v&aacute;lido!"; if(!checkMail($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
				
				if(!enviar){
					return false;
				} else {
					$.ajax({
						type: 'POST',
						url: url_name(),
						data: { ajax : 'sim' , recaptcha : 'sim' , recaptcha_challenge_field : Recaptcha.get_challenge() , recaptcha_response_field : Recaptcha.get_response() },
						beforeSend: function(){
							$('#ajax_lendo').fadeIn(tempo_animacao);
						},
						success: function(txt){
							$('#ajax_lendo').fadeOut(tempo_animacao);
							
							if(txt == 'sim'){
								window.form_serialize = $('#'+form_id).serialize();
								window.enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
								$('#'+form_id)[0].reset();
								cadastrar_usuario = false;
								cadastrar_senha = false;
							} else {
								Recaptcha.reload();
								mens = "<p>C&oacute;digo de valida&ccedil;&atilde;o <b style=\"color:red;\">inv&aacute;lido</b>!<p></p>Favor preencher o c&oacute;digo de valida&ccedil;&atilde;o novamente!</p>";
								$.alerta_open(mens,false,false);
							}
						},
						error: function(txt){
							
						}
					});
				}
			});
			
			function checkMail(mail){
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
			}
			
			function url_name(){
				var url_aux = location.pathname;
				var url_parts;
				
				url_parts = url_aux.split('/');
				
				if(url_parts[url_parts.length-1])
					return url_parts[url_parts.length-1];
				else
					return '.';
			}
		}
		
		if(ajax_vars.ecommerce_limpar_carrinho){
			var pedido_itens = eval('(' + ajax_vars.ecommerce_pedido_itens + ')');
			var itens = Array();
			var itens_obj;
			
			for(var i=0;i<pedido_itens.length;i++){
				itens_obj = {
					id:pedido_itens[i].id,
					preco:parseFloat(pedido_itens[i].preco),
					quant:parseInt(pedido_itens[i].quant),
					titulo:pedido_itens[i].titulo,
					href:pedido_itens[i].href
				}
				
				itens.push(itens_obj);
			}
			
			global_vars.pedido = null;
			localStorage['pedido_itens'] = JSON.stringify(itens);
			
			if(window._gaq){
				var stored = localStorage['pedido_itens'];
				var id_pedido = (ajax_vars.ecommerce_id_pedido ? ajax_vars.ecommerce_id_pedido : '0');
				if(stored){
					var item_cache = {};
					var trans_total = 0;
					item_cache.itens = JSON.parse(stored);
					
					for(var i=0;i<item_cache.itens.length;i++){
						item_cache.item = item_cache.itens[i];
						trans_total = trans_total + item_cache.item.quant*item_cache.item.preco;
					}
					
					window._gaq.push(['_addTrans',
						id_pedido,      	 // order ID
						(ajax_vars.ecommerce_vendedor_nome ? ajax_vars.ecommerce_vendedor_nome : 'Site'),  // store
						trans_total,    																  // total
						(ajax_vars.ecommerce_pedido_taxa ? ajax_vars.ecommerce_pedido_taxa : '0'),      	 // tax
						(ajax_vars.ecommerce_pedido_frete ? ajax_vars.ecommerce_pedido_frete : '0'),       // shipping
						(ajax_vars.ecommerce_vendedor_cidade ? ajax_vars.ecommerce_vendedor_cidade : 'Sao Paulo'),    // city
						(ajax_vars.ecommerce_vendedor_estado ? ajax_vars.ecommerce_vendedor_estado : 'Sao Paulo'),   // state
						(ajax_vars.ecommerce_vendedor_pais ? ajax_vars.ecommerce_vendedor_pais : 'Brazil')       // country
					]);
					
					for(i=0;i<item_cache.itens.length;i++){
						item_cache.item = item_cache.itens[i];
						item_cache.item_subtotal = item_cache.item.quant*item_cache.item.preco;
						
						window._gaq.push(['_addItem',
							id_pedido,         			// transaction ID - necessary to associate item with transaction
							item_cache.item.id,         // SKU/code - required
							item_cache.item.titulo,      // product name - necessary to associate revenue with product
							(item_cache.item.categoria?item_cache.item.categoria:'sem categoria'), // category or variation
							item_cache.item.preco.formatMoney(2, "", "", "."),        // unit price - required
							item_cache.item.quant             // quantity - required
					   ]);
					}
					
					window._gaq.push(['_set', 'currencyCode', 'BRL']);
					window._gaq.push(['_trackTrans']);
				}
			}
			
			global_vars.ecommerce = {};
			localStorage['ecommerce_itens'] = JSON.stringify(Array());
			
			if(variaveis_js.ecommerce_carrinho_quant_show){
				$('#_carrinho-widget-holder-quant').show();
			} else {
				if($('#_carrinho-widget-holder-quant').length > 0){
					$('#_carrinho-widget-holder-quant').hide();
				}
			}
			if($('#_carrinho-widget-holder-val').length > 0){
				$('#_carrinho-widget-holder-val').hide();
			}
			if($('#_carrinho-widget-holder-empty').length > 0){
				$('#_carrinho-widget-holder-empty').show();
			}
		}
		
		if($('._servico-cont').length){
			// ==================================================
			
			if($('#_indisponivel-form').length){
				$("#_indisponivel-btn").bind('click touchstart',function(){
					$('#_indisponivel-form').show();
				});
				
				$('#_indisponivel-form').bind('submit',function(){
					var enviar = true;
					var campo;
					var post;
					var mens;
					var campos = Array();
					var posts = Array();
					var form_id = this.id;
					var opcao = '';
					var href = '';
					var limpar_campos = true;
					var mudar_pagina = false;
					
					campo = '_indisponivel-nome'; mens = "&Eacute; obrigat&oacute;rio definir o Seu Nome!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
					campo = '_indisponivel-email'; mens = "&Eacute; obrigat&oacute;rio definir o E-mail!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
					
					// Checar email
					campo = '_indisponivel-email'; mens = "E-mail inv&aacute;lido, preencha o campo de e-mail v&aacute;lido!"; if(!checkMail($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
					
					if(enviar){
						window.enviar_form_simples(form_id);
					}
					
					return false;
				});
			}

			
			// ==================================================
			if(!global_vars.ecommerce)global_vars.ecommerce = {};
			
			var stored = localStorage['ecommerce_itens'];
			if (stored){
				global_vars.ecommerce.itens = JSON.parse(stored);
			}
			
			if(global_vars.ecommerce.itens){
				$('._servico-cont').each(function(i){
					var id = $(this).attr('id');
					var itens = global_vars.ecommerce.itens;
					for(var i=0;i<itens.length;i++){
						if(itens[i].id == id){
							$(this).find('._servico-quant').find('._servico-quant-inp').val(itens[i].quant);
							break;
						}
					}
				});
			}
			
			$("._servico-botao").bind('click touchstart',function(){
				var id = $(this).parent().attr('id');
				
				var flag;
				var item_num;
				if(global_vars.ecommerce.itens){
					var itens = global_vars.ecommerce.itens;
					for(var i=0;i<itens.length;i++){
						if(itens[i].id == id){
							item_num = i;
							flag = true;
							break;
						}
					}
				}
				
				var quant = parseInt($(this).parent().find('._servico-quant-inp').val());
				
				if(!flag){
					var preco = parseFloat($(this).parent().attr('data-preco'));
					var titulo = $(this).parent().attr('data-titulo');
					var validade = $(this).parent().attr('data-validade');
					var observacao = $(this).parent().attr('data-observacao');
					var desconto = $(this).parent().attr('data-desconto');
					
					global_vars.ecommerce.item_add = {
						id:id,
						preco:preco,
						quant:quant,
						titulo:titulo,
						validade:validade,
						observacao:observacao,
						desconto:desconto,
						href:params.href
					};
				} else {
					global_vars.ecommerce.atualizar_item = {
						item_num:item_num,
						quant:quant
					};
				}
				
				window.link_trigger.attr('href',document.location.protocol+'//'+document.location.hostname+site_raiz+'carrinho').trigger('click');
			});
		}
		
		$.projeto_aplicar_scripts(params);
		$.aplicar_scripts(params);
	}
	
	aplicar_scripts_after(null);
	aplicar_scripts(null);
	
	// ======================== Scroll Search ==================================
	
	var scroll_delay = 1000;
	flags['scroll_delay'] = true;
	
	$(window).scroll(function(){
		if(flags['scroll'])
		if(flags['scroll2'] && !flags['history'] && menu_paginas > menu_pagina){
			var fator_scroll = 400;
			var height = $(cont_secundario).height();
			var top = $(cont_secundario,document.body).offset().top;
			
			if($(window).scrollTop()+$(window).height() > top + height - fator_scroll){
				menu_pagina++
				var dataString = 'ajax_page=1&opcao='+opcao_atual+'&opcao_menu=paginas&paginas='+menu_pagina+'&layout_basico=sim';
				var mostrar_label = false;
				
				flags['scroll_delay'] = false;
				setTimeout(function(){ flags['scroll_delay'] = true; }, scroll_delay);
				
				$.ajax({
					type: 'POST',
					url: location.href,
					data: dataString,
					beforeSend: function(){
						if(mostrar_label)$('#ajax_lendo').fadeIn(tempo_animacao);
					},
					success: function(txt){
						var dados = eval('(' + txt + ')');
						
						if(mostrar_label)$('#ajax_lendo').fadeOut(tempo_animacao);
						$(cont_secundario).append(dados.page);
						if(dados.debug)alert(txt);
						if(dados.alerta){ if(!alerta.dialog('isOpen')){alerta.html(dados.alerta); alerta.dialog('open');}}
						history_lista(false);
					},
					error: function(txt){
						if(mostrar_label){
							$('#ajax_erro').fadeIn(tempo_animacao);
							setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
						}
					}
				});
			}
		}
		if(flags['history'])flags['history'] = false;
	});
	
	// ================================== Navegação Ajax ========================
	
	var conteiner_posicao = false;
	var conteiner_posicao_dados = new Array();
	var conteiner_linhas = new Array();
	var conteiner_posicao_x = new Array();
	var conteiner_posicao_y = new Array();
	var conteiner_alturas = new Array();
	var conteiner_posicao_x_anterior = 0;
	var conteiner_posicao_y_anterior = 0;
	var conteiner_n_colunas = 1;
	var conteiner_n_linhas = 1;
	var conteiner_navegacao;
	var conteiner_height = new Array();
	var conteiner_tempo_padrao = 300;
	var conteiner_efeito_padrao = "linear";
	
	if(variaveis_js.procurar_pesquisa){
		History.pushState({
			page:$(cont_principal).html(),
			ajax_vars:ajax_vars,
			addthis:null,
			opcao_atual:opcao_atual,
			menu_paginas:menu_paginas,
			menu_pagina:menu_pagina,
			audio_path:audio_path,
			conteiner_posicao:variaveis_js.conteiner_posicao,
			conteiner_posicao_x:variaveis_js.conteiner_posicao_x,
			conteiner_posicao_y:variaveis_js.conteiner_posicao_y,
			conteiner_posicao_efeito:variaveis_js.conteiner_posicao_efeito,
			conteiner_posicao_tempo:variaveis_js.conteiner_posicao_tempo,
			janela:variaveis_js.janela
		},document.title,document.location.protocol+'//'+location.hostname+site_raiz+'procurar/'+variaveis_js.procurar_pesquisa);
	} else {
		History.pushState({
			page:$(cont_principal).html(),
			ajax_vars:ajax_vars,
			addthis:null,
			opcao_atual:opcao_atual,
			menu_paginas:menu_paginas,
			menu_pagina:menu_pagina,
			audio_path:audio_path,
			conteiner_posicao:variaveis_js.conteiner_posicao,
			conteiner_posicao_x:variaveis_js.conteiner_posicao_x,
			conteiner_posicao_y:variaveis_js.conteiner_posicao_y,
			conteiner_posicao_efeito:variaveis_js.conteiner_posicao_efeito,
			conteiner_posicao_tempo:variaveis_js.conteiner_posicao_tempo,
			janela:variaveis_js.janela
		},document.title,location.href);
	}
	
	$('#ajax_lendo').center();
	$('#ajax_erro').center();
	
	function history_lista(link_call){
		if(!link_call){
			history_flag = false;
			var State = History.getState();
			State.data.page = $(cont_principal).html();
			State.data.addthis = 0;
			State.data.menu_paginas = menu_paginas;
			State.data.menu_pagina = menu_pagina;
			State.data.opcao_atual = opcao_atual;
			History.replaceState(State.data,State.title,State.url);
			history_flag = true;
		}
	}
	
	if(!$.browser.msie){
		history_first = false;
	}
	
	History.Adapter.bind(window,'statechange',function(){
		flags['history'] = true;
		
		if($.browser.msie && link_clicked){
			history_flag = false;
		}
		
		if(history_flag && !history_first){
			var State = History.getState();
			
			navegacao_conteiner(State.data,'');
			
			ajax_vars = State.data.ajax_vars;
			
			if(State.data.addthis){
				exec_addthis();
			}
			if(State.data.menu_paginas){
				menu_paginas = State.data.menu_paginas;
				menu_pagina = State.data.menu_pagina;
			}
			if(State.data.audio_path){
				audio_path = State.data.audio_path;
			}
			
			href_atual = State.url;
			menu_principal();
			
			opcao_atual = State.data.opcao_atual;
			
			var menus_aux = menus2;
			var max = menus2.length;
			for(var i=0;i<max;i++){
				var opcao = menus_aux.pop();
				if(opcao == opcao_atual){
					flags['scroll'] = true;
					break;
				}
			}
			
			aplicar_scripts_after({href:State.url,banner_href:State.url,history:true});
			aplicar_scripts({href:State.url,janela:State.data.janela,history:true});
		}
		
		if($.browser.msie && link_clicked){
			history_flag = true;
			link_clicked = false;
		}
		
		if(history_first){
			history_first = false;
		}
	});
	
	$('a,area').live('click',function(event){
		history_flag = false;
		flags['scroll'] = false;
		var href = this.href;
		var target = this.target;
		var ajax_nao = false;
		var nao_fazer_nada = false;
		var pagina_anterior = '';
		
		href_atual = href;
		
		if(target != '_blank'){
			if(global_vars.ctrl_ativo){
				return;
			}
			
			if(href.search(/mailto:/)>=0)ajax_nao = true;
			if(href.search(/msnim:/)>=0)ajax_nao = true;
			if(href.search(/callto:/)>=0)ajax_nao = true;
			if(href.search(/http:/)>=0)ajax_nao = true;
			if(href.search(/https:/)>=0)ajax_nao = true;
			if(href.search(/ftp:/)>=0)ajax_nao = true;
			
			if(href.search('http://'+location.hostname)>=0)ajax_nao = false;
			if(href.search('https://'+location.hostname)>=0)ajax_nao = false;
			
			if(href.search(/#/)>=0)ajax_nao = true;
			if($(this).attr('rel')){
				var rel = $(this).attr('rel');
				if(rel.search(/prettyPhoto/)>=0)ajax_nao = true;
			}
			
			if(this.id == 'recaptcha_reload_btn')ajax_nao = true;
			if(this.id == 'recaptcha_switch_audio_btn')ajax_nao = true;
			if(this.id == 'recaptcha_switch_img_btn')ajax_nao = true;
			if($(this).hasClass('ui-datepicker-prev'))ajax_nao = true;
			if($(this).hasClass('ui-datepicker-next'))ajax_nao = true;
			if($(this).hasClass('fancybox-nav'))ajax_nao = true;
			if($(this).hasClass('fancybox-close'))ajax_nao = true;
			if($(this).hasClass('_ajax_nao'))ajax_nao = true;
			
			if(global_vars.colorbox_open){$.colorbox.close();global_vars.colorbox_open = false;}
			
			var params = $.projeto_links({objeto:this,ajax_nao:ajax_nao});
			
			nao_fazer_nada = params['nao_fazer_nada'];
			ajax_nao = params['ajax_nao'];
			
			if(nao_fazer_nada){
				aplicar_scripts_after({banner_href:href});
				history_flag = true;
				return false;
			} else {
				if(!ajax_nao){
					aplicar_scripts_after({banner_href:href});
					history_lista(true);
					event.preventDefault();
					$('#ajax_lendo').fadeIn(tempo_animacao);
					if($(cont_principal).length){
						pagina_anterior = $(cont_principal).html();
						$('<div id="_cont_ajax"></div>').load(href,{ajax_page:1,projeto_js:(projeto_js?JSON.stringify(projeto_js):{})}, function(response, status, xhr) {
							$('#ajax_lendo').fadeOut(tempo_animacao);
							if(status == "error"){
								$('#ajax_erro').fadeIn(tempo_animacao);
								setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
								history_flag = true;
							} else {
								if(debug_retorno)console.log(response);
								var dados = eval('(' + response + ')');
								
								if(dados.log)console.log(dados.log);
								if(dados.redirecionar)window.open(document.location.protocol+'//'+document.location.hostname+dados.redirecionar,'_self');
								if(dados.redirecionar_ajax)window.link_trigger.attr('href',document.location.protocol+'//'+document.location.hostname+dados.redirecionar_ajax).trigger('click');
								if(dados.ler_css)ler_css(dados.ler_css);
								if(dados.ler_scripts)ler_scripts(dados.ler_scripts);
								if(dados.alerta_appendto_body) global_vars.alerta_appendto_body = dados.alerta_appendto_body;
								
								ajax_vars = dados;
								
								if(dados.menu_dinamico){
									if(!menus[dados.menu_dinamico]){
										menus2[menus2.length] = menus[dados.menu_dinamico] = 'menu_'+dados.menu_dinamico;
									}
								}
								if(dados.menu_paginas){
									menu_paginas = dados.menu_paginas;
									menu_pagina = 1;
								}
								if(menus[dados.opcao]){
									flags['scroll'] = true;
									opcao_atual = menus[dados.opcao];
								} else {
									opcao_atual = dados.opcao;
								}
								if(dados.audio_path){
									audio_path = dados.audio_path;
								}
								
								History.pushState({
									page:dados.page,
									ajax_vars:ajax_vars,
									addthis:dados.addthis,
									janela:dados.janela,
									opcao_atual:opcao_atual,
									menu_paginas:menu_paginas,
									menu_pagina:menu_pagina,
									audio_path:audio_path,
									conteiner_posicao:dados.conteiner_posicao,
									conteiner_posicao_x:dados.conteiner_posicao_x,
									conteiner_posicao_y:dados.conteiner_posicao_y,
									conteiner_posicao_efeito:dados.conteiner_posicao_efeito,
									conteiner_posicao_tempo:dados.conteiner_posicao_tempo
								},dados.titulo,href);
								
								navegacao_conteiner(dados,pagina_anterior);
								
								flags['scroll2'] = false;
								flags['scroll2'] = true;
								if(dados.addthis == '1')exec_addthis();
								if(dados.recaptcha_public_key)recaptcha_public_key = dados.recaptcha_public_key;
								if(window._gaq && site_teste != 'sim' && !dados.noindex){window._gaq.push(['_trackPageview', href]);}
								
								aplicar_scripts({href:href});
								
								if(dados.debug)$.alerta_open(response,false,false);
								if(dados.alerta)$.alerta_open(dados.alerta,false,false);
								
								if(!global_vars.link_nao_mudar_scroll){
									$('body').scrollTop(0);
									$('html').scrollTop(0);
									$(document).scrollTop(0);
									global_vars.link_nao_mudar_scroll = false;
								}
								history_flag = true;
								link_clicked = true;
								
								$('body').css('cursor','default');
							}
						});
					} else {
						$('#ajax_lendo').fadeOut(tempo_animacao);
						$.alerta_open("<p>Conteiner #cont_principal nÃ£o encontrado!</p><p>Ã‰ necessÃ¡rio ter conteiner em algum lugar do layout:</p><p>&lt;div id=\"cont_principal\"&gt;!#body#!&lt;/div&gt;</p>",false,false);
						history_flag = true;
					}
				} else {
					history_flag = true;
				}
			}
		}
	});
	
	function navegacao_conteiner(dados,pagina_anterior){
		var conteiner_aux;
		var conteiner_aux2;
		var width;
		var height;
		var pos_x = 0;
		var pos_y = 0;
		var i;
		var found_before = false;
		var found_primeiro = false;
		var linha_criada = false;
		var criar_item = false;
		var cont_aux_primeira = true;
		var posicao_y_insert;
		var posicao_x_insert;
		var conteiner_posicao_y_aux;
		var conteiner_linha;
		var conteiner_linha1;
		var conteiner_linha2;
		var largura = 0;
		var altura = 0;
		
		if(conteiner_posicao){
			width = $(cont_principal).width();
			
			if(dados.conteiner_posicao){
				pos_x = parseInt(dados.conteiner_posicao_x);
				pos_y = parseInt(dados.conteiner_posicao_y);
				
				if(!conteiner_posicao_dados[pos_x]){
					conteiner_posicao_x.push(pos_x);
					conteiner_posicao_x.sort(function(a,b){return a - b});
					conteiner_posicao_dados[pos_x] = new Array();
					conteiner_posicao_y[pos_x] = new Array();
					linha_criada = true;
				}
				
				if(!conteiner_posicao_dados[pos_x][pos_y]){
					criar_item = true;
					conteiner_posicao_y[pos_x].push(pos_y);
					
					conteiner_posicao_y[pos_x].sort(function(a,b){return a - b});
					conteiner_posicao_dados[pos_x][pos_y] = {
						efeito:dados.conteiner_posicao_efeito,
						tempo:dados.conteiner_posicao_tempo
					};
					
					if(conteiner_posicao_dados[pos_x].length > conteiner_n_colunas){
						conteiner_n_colunas++;
						conteiner_navegacao.css('width',((conteiner_n_colunas)*width)+"px");
					}
					
					conteiner_aux = $('<div></div>')
						.css('width',width+"px")
						.css('overflow','hidden')
						.css('position','relative')
						.css('float','left')
						.attr('id','cont_navegacao_'+pos_x+'_'+pos_y);
					
					conteiner_aux.html(dados.page);
					
					if(linha_criada){
						conteiner_linha = $('<div></div>')
							.attr('id','_conteiner_linha_'+pos_x);
						
						conteiner_n_linhas++;
						
						conteiner_linha.append(conteiner_aux);
						conteiner_linha.append($('<div></div>').css('clear','both'));
						
						for(i=0;i<conteiner_posicao_x.length;i++){
							if(conteiner_posicao_x[i] == pos_x){
								if(i == 0){
									found_before = true;
									posicao_x_insert = conteiner_posicao_x[1];
								} else {
									posicao_x_insert = conteiner_posicao_x[i-1];
								}
								
								break;
							}
						}
						
						if(found_before){
							conteiner_navegacao.find('#_conteiner_linha_'+posicao_x_insert).before(conteiner_linha).after($('<div></div>').css('clear','both'));
						} else {
							conteiner_navegacao.find('#_conteiner_linha_'+posicao_x_insert).after(conteiner_linha).after($('<div></div>').css('clear','both'));
						}
					} else {
						for(i=0;i<conteiner_posicao_y[pos_x].length;i++){
							if(conteiner_posicao_y[pos_x][i] == pos_y){
								if(i == 0){
									found_before = true;
									posicao_y_insert = conteiner_posicao_y[pos_x][1];
								} else {
									posicao_y_insert = conteiner_posicao_y[pos_x][i-1];
								}
								
								break;
							}
						}
						
						if(found_before){
							conteiner_navegacao.find('#cont_navegacao_'+pos_x+'_'+posicao_y_insert).before(conteiner_aux);
						} else {
							conteiner_navegacao.find('#cont_navegacao_'+pos_x+'_'+posicao_y_insert).after(conteiner_aux);
						}
					}
				} else {
					conteiner_navegacao.find('#cont_navegacao_'+pos_x+'_'+pos_y).html(dados.page);
				}
				
				height = $('#cont_navegacao_'+pos_x+'_'+pos_y).height();
				
				if(!height)
					height = 0;
				
				if(!conteiner_height[pos_x])
					conteiner_height[pos_x] = height;
				
				if(conteiner_height[pos_x] < height){
					conteiner_height[pos_x] = height;
				}
				
				$(cont_principal)
					.css('height',height+'px');
			} else {
				pos_x = 1;
				pos_y = 1;
				
				conteiner_navegacao.find('#cont_navegacao_1_1').html(dados.page);
				
				height = $('#cont_navegacao_1_1').height();
				
				if(!height)
					height = 0;
					
				if(cont_aux_primeira){
					criar_item = true;
					cont_aux_primeira = false;
				}
				
				if(!conteiner_height[pos_x]){
					conteiner_height[pos_x] = height;
				}
				
				if(conteiner_height[pos_x] < height){
					conteiner_height[pos_x] = height;
				}
				
				$(cont_principal)
					.css('height',height+'px');
			}
			
			if(criar_item && pos_x < conteiner_posicao_x_anterior){
				var altura_aux = 0;
				for(i=0;i<conteiner_posicao_x.length;i++){
					if(conteiner_posicao_x[i] == conteiner_posicao_x_anterior){
						break;
					} else {
						altura_aux = altura_aux + (conteiner_height[conteiner_posicao_x[i]]?conteiner_height[conteiner_posicao_x[i]]:0);
					}
				}
				conteiner_navegacao.css('top','-'+(altura_aux)+"px");
			}
			
			if(criar_item && pos_y < conteiner_posicao_y_anterior){
				var largura_aux = 0;
				for(i=0;i<conteiner_posicao_y[pos_x].length;i++){
					if(conteiner_posicao_y[pos_x][i] == conteiner_posicao_y_anterior){
						break;
					} else {
						largura_aux++;
					}
				}
				conteiner_navegacao.css('left','-'+(largura_aux*width)+"px");
			}
			
			for(i=0;i<conteiner_posicao_y[pos_x].length;i++){
				if(conteiner_posicao_y[pos_x][i] == pos_y){
					break;
				} else {
					largura++;
				}
			}
			
			for(i=0;i<conteiner_posicao_x.length;i++){
				if(conteiner_posicao_x[i] == pos_x){
					break;
				} else {
					altura = altura + (conteiner_height[conteiner_posicao_x[i]]?conteiner_height[conteiner_posicao_x[i]]:0);
				}
			}
			
			setTimeout(function(){conteiner_navegacao.stop().animate( {left: (largura>0?"-":"")+(largura*width)+"px" , top: (altura>0?"-":"")+altura+"px" }, (dados.conteiner_posicao_tempo?parseInt(dados.conteiner_posicao_tempo):conteiner_tempo_padrao), (dados.conteiner_posicao_efeito?dados.conteiner_posicao_efeito:conteiner_efeito_padrao));},tempo_animacao);
			
			conteiner_posicao_x_anterior = pos_x;
			conteiner_posicao_y_anterior = pos_y;
		} else {
			if(dados.conteiner_posicao){
				pos_x = parseInt(dados.conteiner_posicao_x);
				pos_y = parseInt(dados.conteiner_posicao_y);
				
				conteiner_posicao = true;
				width = $(cont_principal).width();
				
				conteiner_posicao_x.push(pos_x);
				conteiner_posicao_y[pos_x] = new Array();
				conteiner_posicao_y[pos_x].push(pos_y);
				
				conteiner_posicao_dados[pos_x] = new Array();
				conteiner_posicao_dados[pos_x][pos_y] = {
					efeito:variaveis_js.conteiner_posicao_efeito,
					tempo:variaveis_js.conteiner_posicao_tempo
				};
				
				if(pos_x != 1){
					conteiner_posicao_x.push(1);
					conteiner_posicao_y[1] = new Array();
					
					conteiner_posicao_dados[1] = new Array();
				}
				
				conteiner_posicao_y[1].push(1);
				conteiner_posicao_dados[1][1] = {
					efeito:conteiner_efeito_padrao,
					tempo:conteiner_tempo_padrao
				};
				
				conteiner_posicao_x.sort(function(a,b){return a - b});
				conteiner_posicao_y[1].sort(function(a,b){return a - b});
				
				conteiner_linha1 = $('<div></div>')
					.css('position','relative')
					.attr('id','_conteiner_linha_1');
				
				conteiner_navegacao = $('<div></div>')
					.css('position','absolute')
					.css('top','0px')
					.css('left','0px')
					.attr('id','_conteiner_navegacao');
				
				conteiner_aux = $('<div></div>')
					.css('width',width+"px")
					.css('overflow','hidden')
					.css('float','left')
					.attr('id','cont_navegacao_1_1');
				
				conteiner_aux.html(pagina_anterior);
				
				conteiner_aux2 = $('<div></div>')
					.css('width',width+"px")
					.css('overflow','hidden')
					.css('position','relative')
					.css('float','left')
					.attr('id','cont_navegacao_'+pos_x+'_'+pos_y);
				
				conteiner_aux2.html(dados.page);
				
				if(pos_x == 1){
					conteiner_navegacao.css('width',(2*width)+"px");
					
					conteiner_n_colunas++;
					
					conteiner_linha1.append(conteiner_aux);
					conteiner_linha1.append(conteiner_aux2);
					conteiner_linha1.append($('<div></div>').css('clear','both'));
					
					conteiner_navegacao.append(conteiner_linha1);
					conteiner_navegacao.append($('<div></div>').css('clear','both'));
				} else {
					conteiner_navegacao.css('width',(width)+"px");
					
					conteiner_linha2 = $('<div></div>')
					.css('position','relative')
					.attr('id','_conteiner_linha_'+pos_x);
					
					conteiner_n_linhas++;
					
					conteiner_linha1.append(conteiner_aux);
					conteiner_linha1.append($('<div></div>').css('clear','both'));
					
					conteiner_linha2.append(conteiner_aux2);
					conteiner_linha2.append($('<div></div>').css('clear','both'));
					
					conteiner_navegacao.append(conteiner_linha1);
					conteiner_navegacao.append($('<div></div>').css('clear','both'));
					conteiner_navegacao.append(conteiner_linha2);
					conteiner_navegacao.append($('<div></div>').css('clear','both'));
				}
				
				$(cont_principal).html('');
				$(cont_principal).append(conteiner_navegacao);
				
				height = $('#cont_navegacao_'+pos_x+'_'+pos_y).height();
				
				if(pos_x == 1){
					conteiner_height[pos_x] = height;
				} else {
					conteiner_height[1] = $('#cont_navegacao_1_1').height();
					conteiner_height[pos_x] = height;
				}
				
				$(cont_principal)
					.css('overflow','hidden')
					.css('position','relative')
					.css('height',height+'px');
				
				if(pos_x == 1){
					setTimeout(function(){conteiner_navegacao.stop().animate( {left: "-"+width+"px"}, (dados.conteiner_posicao_tempo?parseInt(dados.conteiner_posicao_tempo):conteiner_tempo_padrao), (dados.conteiner_posicao_efeito?dados.conteiner_posicao_efeito:conteiner_efeito_padrao));},tempo_animacao);
				} else {
					height = $('#cont_navegacao_1_1').height();
					setTimeout(function(){conteiner_navegacao.stop().animate( {top: "-"+height+"px"}, (dados.conteiner_posicao_tempo?parseInt(dados.conteiner_posicao_tempo):conteiner_tempo_padrao), (dados.conteiner_posicao_efeito?dados.conteiner_posicao_efeito:conteiner_efeito_padrao));},tempo_animacao);
				}
				
				conteiner_posicao_x_anterior = pos_x;
				conteiner_posicao_y_anterior = pos_y;
			} else {
				$(cont_principal).html(dados.page);
			}
		}
	}
	
	function navegacao_conteiner_start(){
		var conteiner_aux;
		var conteiner_aux2;
		var conteiner_linha1;
		var conteiner_linha2;
		var width;
		var height;
		var pos_x = 0;
		var pos_y = 0;
		
		if(variaveis_js.conteiner_posicao){
			pos_x = parseInt(variaveis_js.conteiner_posicao_x);
			pos_y = parseInt(variaveis_js.conteiner_posicao_y);
			
			conteiner_posicao = true;
			width = $(cont_principal).width();
			
			conteiner_posicao_x.push(pos_x);
			conteiner_posicao_y[pos_x] = new Array();
			conteiner_posicao_y[pos_x].push(pos_y);
			
			conteiner_posicao_dados[pos_x] = new Array();
			conteiner_posicao_dados[pos_x][pos_y] = {
				efeito:variaveis_js.conteiner_posicao_efeito,
				tempo:variaveis_js.conteiner_posicao_tempo
			};
			
			if(pos_x != 1){
				conteiner_posicao_x.push(1);
				conteiner_posicao_y[1] = new Array();
				
				conteiner_posicao_dados[1] = new Array();
			}
			
			conteiner_posicao_y[1].push(1);
			conteiner_posicao_dados[1][1] = {
				efeito:conteiner_efeito_padrao,
				tempo:conteiner_tempo_padrao
			};
			
			conteiner_posicao_x.sort(function(a,b){return a - b});
			conteiner_posicao_y[1].sort(function(a,b){return a - b});
			
			conteiner_linha1 = $('<div></div>')
				.attr('id','_conteiner_linha_1');
			
			conteiner_navegacao = $('<div></div>')
				.css('position','absolute')
				.css('top','0px')
				.css('left','0px')
				.attr('id','_conteiner_navegacao');
			
			conteiner_aux = $('<div>&nbsp;</div>')
				.css('width',width+"px")
				.css('overflow','hidden')
				.css('float','left')
				.attr('id','cont_navegacao_1_1');
			
			conteiner_aux2 = $('<div></div>')
				.css('width',width+"px")
				.css('position','relative')
				.css('overflow','hidden')
				.css('float','left')
				.attr('id','cont_navegacao_'+pos_x+'_'+pos_y);
			
			conteiner_aux2.html($(cont_principal).html());
			
			if(pos_x == 1){
				conteiner_navegacao.css('width',(2*width)+"px");
				
				conteiner_n_colunas++;
				
				conteiner_linha1.append(conteiner_aux);
				conteiner_linha1.append(conteiner_aux2);
				conteiner_linha1.append($('<div></div>').css('clear','both'));
				
				conteiner_navegacao.append(conteiner_linha1);
				conteiner_navegacao.append($('<div></div>').css('clear','both'));
			} else {
				conteiner_navegacao.css('width',(width)+"px");
				
				conteiner_linha2 = $('<div></div>')
				.attr('id','_conteiner_linha_'+pos_x);
				
				conteiner_n_linhas++;
				
				conteiner_linha1.append(conteiner_aux);
				conteiner_linha1.append($('<div></div>').css('clear','both'));
				
				conteiner_linha2.append(conteiner_aux2);
				conteiner_linha2.append($('<div></div>').css('clear','both'));
				
				conteiner_navegacao.append(conteiner_linha1);
				conteiner_navegacao.append($('<div></div>').css('clear','both'));
				conteiner_navegacao.append(conteiner_linha2);
				conteiner_navegacao.append($('<div></div>').css('clear','both'));
			}
			
			$(cont_principal).html('');
			$(cont_principal).append(conteiner_navegacao);
			
			height = $('#cont_navegacao_'+pos_x+'_'+pos_y).height();
			
			if(pos_x == 1){
				conteiner_height[pos_x] = height;
			} else {
				conteiner_height[1] = $('#cont_navegacao_1_1').height();
				conteiner_height[pos_x] = height;
			}
			
			$(cont_principal)
				.css('overflow','hidden')
				.css('position','relative')
				.css('height',height+'px');
			
			if(pos_x == 1){
				conteiner_navegacao.css( 'left' , "-"+width+"px");
			} else {
				height = $('#cont_navegacao_1_1').height();
				conteiner_navegacao.css( 'top' , "-"+height+"px");
			}
			
			conteiner_posicao_x_anterior = pos_x;
			conteiner_posicao_y_anterior = pos_y;
		}
	}
	
	navegacao_conteiner_start();
	
	if(location.hostname != 'localhost'){
		var script = document.location.protocol+'//s7.addthis.com/js/300/addthis_widget.js?async=1&domready=1&pubid=ra-4dc8b14029ceaa85';
		
		$.getScript(script,function() {
			window.addthis.update('config', 'data_track_clickback', true);
			window.addthis.update('share', 'url', href_atual);
			window.addthis.update('share', 'title', document.title);
			window.addthis.toolbox(".addthis_toolbox");
		});
	}
	
	function exec_addthis(){
		if(location.hostname != 'localhost'){
			if(window.addthis){
				var State = History.getState();
				
				window.addthis.update('config', 'data_track_clickback', true);
				window.addthis.update('share', 'url', State.url);
				window.addthis.update('share', 'title', State.title);
				window.addthis.toolbox(".addthis_toolbox");
			}
		}
	}
	
	if(variaveis_js.addthis){
		exec_addthis();
	}
	
	// ==============================================================================
	
	$(".link_hover").live({
		mouseenter: function(){
			$('body').css('cursor', 'pointer');
		},
		mouseleave: function(){
			$('body').css('cursor', 'default'); 
		}
	});

	$(".image_hover").live({
		mouseenter: function(){
			var src = $(this).attr("src");
			var url = $(this).attr("url");
			$(this).attr("src",url);
			$(this).attr("url",src);
		},
		mouseleave: function(){
			var src = $(this).attr("src");
			var url = $(this).attr("url");
			$(this).attr("src",url);
			$(this).attr("url",src);
		}
	});
	
	$("a > .image_hover").live({
		mouseup: function(){
			var src = $(this).attr("src");
			var url = $(this).attr("url");
			$(this).attr("src",url);
			$(this).attr("url",src);
		},
		mousedown: function(){
			
		}
	});
	
	$(".image_hover").live('click touchstart',function(){
		var src = $(this).attr("src");
		var url = $(this).attr("url");
		$(this).attr("src",url);
		$(this).attr("url",src);
	});
	
	$('.imagens_animate').find('div.boxgrid').live({
		mouseenter: function(){
			$(this).find('.slide').stop().find('span.curve').stop().animate({marginTop:'25px'},{queue:false,duration:160,easing:'swing'});
			$(this).stop().find('.title').animate({height:'20px'},{queue:false,duration:80});
		},
		mouseleave: function(){
			$(this).find('.slide').stop().find('span.curve').stop().animate({marginTop:'0px'},{queue:false,duration:160,easing:'swing'});
			$(this).stop().find('.title').animate({height:'0px'},{queue:false,duration:80});
		}
	});
	
	$('input[type="password"],input[type="text"]').live('click touchstart',function(){
		this.select();
	});
	
	// ============================================ Formulários Dinâmicos ====================================
	
	$("#login_box").live('submit',function() {
		var enviar = true;
		var campo;
		var mens;
		
		campo = "usuario"; mens = "Preencha o usuário"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
		campo = "senha"; mens = "Preencha a senha"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio'); }
		
		if(!enviar){
			return false;
		}
	});
	
	$("#contato_botao").live('click touchstart',function(){
		var enviar = true;
		var campo;
		var post;
		var mens;
		var campos = Array();
		var posts = Array();
		var opcao = 'contato_banco';
		var href = '';
		var limpar_campos = true;
		var mudar_pagina = false;
		
		campo = "contato_nome"; post = "nome"; mens = "É obrigatório definir o nome!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');campos.push(campo);posts.push(post);}
		campo = "contato_email"; post = "email"; mens = "É obrigatório definir o email!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');campos.push(campo);posts.push(post);}
		campo = "contato_mensagem"; post = "mensagem"; mens = "É obrigatório definir a mensagem!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');campos.push(campo);posts.push(post);}
		
		// Checar email
		campo = "contato_email"; mens = "E-mail inválido, preencha o campo de e-mail válido!"; if(!checkMail($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
		
		var campos_extra = $.projeto_contato_campos(null);
		var campo_aux;
		
		if(campos_extra){
			for(var i=0;i<campos_extra.length;i++){
				campo_aux = campos_extra[i];
				campo = campo_aux.campo; post = campo_aux.post; mens = campo_aux.mens; 
				if(!campo_aux.campo_nao_obrigado){
					if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');campos.push(campo);posts.push(post);}
				} else {
					campos.push(campo);posts.push(post);
				}
			}
		}
		
		if(!enviar){
			return false;
		} else {
			enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
		}
	});
	
	$("#buscar_pesquisa").live('keyup',function(e){
		if(e.which == 13){
			pesquisar();
		}
	});
	
	$("#buscar_botao").live('click touchstart',pesquisar);
	
	$("#emarkenting_botao").live('click touchstart',function(){
		var enviar = true;
		var campo;
		var post;
		var mens;
		var campos = Array();
		var posts = Array();
		var opcao = 'emarkenting';
		var href = '';
		var limpar_campos = true;
		var mudar_pagina = false;
		
		campo = "emarkenting_nome"; post = "nome"; mens = "É obrigatório definir o nome!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');campos.push(campo);posts.push(post);}
		campo = "emarkenting_email"; post = "email"; mens = "É obrigatório definir o email!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');campos.push(campo);posts.push(post);}
		
		// Checar email
		campo = "emarkenting_email"; mens = "E-mail inválido, preencha o campo de e-mail válido!"; if(!checkMail($("#"+campo).val())){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');}
		
		if(!enviar){
			return false;
		} else {
			enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
		}
	});
	
	$("#form_pesquisar").live('submit',function(){
		return false;
	});
	
	$(".enviar_formulario").live('click touchstart',function(){
		var retorno = $.projeto_enviar_formulario({objeto:this});
		
		if(!retorno.enviar){
			return false;
		} else {
			form_serialize = $('#'+retorno.form_id).serialize();
			$('#'+retorno.form_id).find(':input').each(function() {
				switch(this.type) {
					case 'password':
					case 'select-multiple':
					case 'select-one':
					case 'text':
					case 'textarea':
						$(this).val('');
						break;
					case 'checkbox':
					case 'radio':
						this.checked = false;
				}
			});
			enviar_form(retorno.campos,retorno.posts,retorno.opcao,retorno.href,retorno.limpar_campos,retorno.mudar_pagina);
		}
	});
	
	// ============================================ Formulário Cadastre-se ====================================
	
	var sep = "";
	var cadastrar_usuario = false;
	var cadastrar_senha = false;
	var cor1 = '#F00'; // Vermelho
	var cor2 = '#0C6'; // Verde
	
	function url_name(){
		var url_aux = location.pathname;
		var url_parts;
		
		url_parts = url_aux.split('/');
		
		if(url_parts[url_parts.length-1])
			return url_parts[url_parts.length-1];
		else
			return '.';
	}
	
	function validar_senha(){
		var mens;
		var cor;
		
		if($("#cadastro-senha").val() && $("#cadastro-senha2").val()){
			var valido = true;
			
			if($("#cadastro-senha").val() != $("#cadastro-senha2").val()){
				valido = false;
				mens = "Senha e Redigite a Senha são diferentes.";
				$.alerta_open(mens,false,false);
			}
			
			if(checkStr($("#cadastro-senha").val())){
				valido = false;
				mens = "Caracteres inválidos, apenas caracteres alfanuméricos e ( _ ou @ ou . )";
				$.alerta_open(mens,false,false);
			}
			
			if(!limites_str($("#cadastro-senha").val(),4,20)){
				valido = false;
				mens = "Senha no mínimo 4 e no máx 20 caracteres.";
				$.alerta_open(mens,false,false);
			}
			
			$("#mens_senha").removeClass('ui-state-highlight');
			$("#cadastro-senha2").removeClass('input-vazio');
			$("#cadastro-senha").removeClass('input-vazio');
			$("#mens_senha").removeClass('input-vazio');
			
			if(!valido){
				cor = cor1;
				cadastrar_senha = false;
				$("#cadastro-senha").val('');
				$("#cadastro-senha2").val('');
				$("#cadastro-senha").addClass('input-vazio');
				$("#cadastro-senha2").addClass('input-vazio');
				$("#mens_senha").addClass('input-vazio');
			} else {
				mens = "Senha OK.";
				cor = cor2;
				cadastrar_senha = true;
				$("#mens_senha").addClass('ui-state-highlight');
			}
			
			$("#mens_senha").html(mens);
		}
	}
	
	// ============================================ Formulários Estáticos ====================================
	
	var alert_status = false;
	
	if($("#install").length){
		$("#install").submit(function() {
			var enviar = true;
			var campo;
			var mens;
			
			alert_status = false;
			validar_usuario();
			validar_senha2();
			
			campo = "usuario"; mens = "É obrigatório definir o Usuário!"; if(!$("#"+campo).val()){ if(!alert_status){alert(mens);alert_status = true;} enviar = false; }
			campo = "senha"; mens = "É obrigatório definir a Senha!"; if(!$("#"+campo).val()){ if(!alert_status){alert(mens);alert_status = true;} enviar = false; }
			campo = "senha2"; mens = "É obrigatório definir o Redigite a Senha!"; if(!$("#"+campo).val()){ if(!alert_status){alert(mens);alert_status = true;} enviar = false; }
			campo = "dominio"; mens = "É obrigatório definir o Domínio!"; if(!$("#"+campo).val()){ if(!alert_status){alert(mens);alert_status = true;} enviar = false; }
			
			if(
				!cadastrar_usuario ||
				!cadastrar_senha
			){	mens = "É necessário validar os campos antes de enviar!"; if(!alert_status){alert(mens);alert_status = true;}  enviar = false; }
			
			if(!enviar){
				return false;
			}
		});
	}
	
	// ================================== Ecommerce ========================
	
	if(localStorage['ecommerce_itens'])
	if(localStorage['ecommerce_itens'].length > 0){
		var stored = localStorage['ecommerce_itens'];
		var itens = JSON.parse(stored);
		var valor_total = 0;
		var quantidades = 0;
		
		for(var i=0;i<itens.length;i++){
			valor_total = valor_total + (itens[i].preco * itens[i].quant);
			quantidades = quantidades + itens[i].quant;
		}
		
		var valor_str = valor_total.formatMoney(2, "R$ ", ".", ",");
		
		if(valor_total > 0){
			if($('#_carrinho-widget-holder-quant').length > 0){
				$('#_carrinho-widget-quant').html(quantidades);
				$('#_carrinho-widget-holder-quant').show();
			}
			if($('#_carrinho-widget-holder-val').length > 0){
				$('#_carrinho-widget-val').html(valor_str);
				$('#_carrinho-widget-holder-val').show();
			}
			if($('#_carrinho-widget-holder-empty').length > 0){
				$('#_carrinho-widget-holder-empty').hide();
			}
		} else {
			if(variaveis_js.ecommerce_carrinho_quant_show){
				$('#_carrinho-widget-quant').html('0');
				$('#_carrinho-widget-holder-quant').show();
			} else {
				if($('#_carrinho-widget-holder-quant').length > 0){
					$('#_carrinho-widget-holder-quant').hide();
				}
			}
			if($('#_carrinho-widget-holder-val').length > 0){
				$('#_carrinho-widget-holder-val').hide();
			}
			if($('#_carrinho-widget-holder-empty').length > 0){
				$('#_carrinho-widget-holder-empty').show();
			}
		}
	}
	
	// ======================== Menu Principal ==================================
	
	var menu_principal_ids = Array();
	var menu_atual = '';
	var height_anterior = 0;
	var menu_principal_anterior = '';
	var menu_principal_flag = false;
	var menu_principal_filhos = false;
	var menu_principal_cont = false;
	var menu_principal_1st_execution = true;
	var menu_barra_processado = Array();
	var menu_obj_ant = $("<div></div>");
	
	function menu_principal_start(){
		var str_aux = variaveis_js.menu_principal_ids;
		
		if(str_aux){
			var arr = str_aux.split(',');
			var count = 0;
			
			for(var i=0;i<arr.length;i++){
				if(arr[i].length > 0){
					menu_principal_ids[count] = arr[i];
					count++;
				}
			}
			
			menu_principal_flag = true;
			menu_principal();
		}
	}
	
	function menu_principal(){
		var tempo = 200;
		var height = 0;
		var local = href_atual;
		var margin_bottom = parseInt($(".menu_div").css('marginBottom'));
		var margin_top = parseInt($(".menu_div").css('marginTop'));
		
		local = local.replace(site_raiz,'');
		
		menu_atual = '';
		menu_principal_filhos = false;
		for(var i=0;i<menu_principal_ids.length;i++){
			if(local.match(menu_principal_ids[i])){
				menu_atual = menu_principal_ids[i];
				menu_principal_filhos = true;
				break;
			}
		}
		
		if(menu_atual && menu_principal_filhos){
			if(!menu_principal_cont)$("#menu-secundario").css({'position':'absolute','visibility':'hidden','display':'block'});
			$("#_menu_"+menu_atual).css({'position':'absolute','visibility':'hidden','display':'block'});
			height = $("#_menu_"+menu_atual).outerHeight() + margin_bottom + margin_top;
			$("#_menu_"+menu_atual).css({'position':'static','visibility':'visible','display':'none'});
			if(!menu_principal_cont)$("#menu-secundario").css({'position':'static','visibility':'visible','display':'none'});
			
			if(menu_principal_anterior == menu_atual){
				$("#_menu_"+menu_principal_anterior).css('display','block');
			}
			
			$("#menu-secundario").height(height_anterior?height_anterior:height);
			height_anterior = height;
		}
		
		if(!menu_principal_cont){
			if(menu_principal_filhos){
				//$("#menu-secundario").slideDown(tempo);
				$("#menu-secundario").fadeIn(tempo);
				if(!menu_principal_1st_execution){
					$("#menu-secundario").animate({height:height},tempo);
				} else {
					menu_principal_1st_execution = false;
				}
				menu_principal_cont = true;
			}
		} else {
			if(!menu_principal_filhos){
				$("#menu-secundario").hide();
				menu_principal_cont = false;
			} else {
				$("#menu-secundario").animate({height:height},tempo);
			}
		}
		
		if(menu_atual && menu_principal_filhos){
			$("#_menu_"+menu_atual).fadeIn(tempo);
			if(menu_principal_anterior != menu_atual)$("#_menu_"+menu_principal_anterior).fadeOut(tempo/2);
		}
		
		if(menu_atual && variaveis_js.menu_principal_colunas){
			if(menu_obj_ant.attr("id") != $("#menu-"+menu_atual).attr("id")){
				if(variaveis_js.menu_principal_bg)$("#menu-"+menu_atual).css('background-image', 'url('+variaveis_js.site_raiz+'files/projeto/images/'+$("#menu-"+menu_atual).attr("id")+'-2.png)');
				if(variaveis_js.menu_setinha){
					var width = $("#menu-"+menu_atual).outerWidth();
					var left1 = $("#menu-"+menu_atual,document.body).offset().left;
					var left2 = $("#main",document.body).offset().left;
					var left = left1 - left2;
					var width_seta = parseInt($('#menu-setinha').css('width'));
					$('#menu-setinha').css('left',(left+(width/2)-(width_seta/2))+'px');
					setTimeout(function(){$('#menu-setinha').show();}, tempo);
				}
			}
			
			if(menu_obj_ant && menu_obj_ant.attr("id") != $("#menu-"+menu_atual).attr("id")){
				if(variaveis_js.menu_principal_bg)menu_obj_ant.css('background-image', 'url('+variaveis_js.site_raiz+'files/projeto/images/'+menu_obj_ant.attr("id")+'.png)');
			}
			
			if(menu_obj_ant.attr("id") != $("#menu-"+menu_atual).attr("id"))menu_obj_ant = $("#menu-"+menu_atual);
			
			if($('.menu_coluna').length > 0 && !menu_barra_processado["#_menu_"+menu_atual]){
				setTimeout(function(){
					var menu_tamanho = 0;
					
					$("#_menu_"+menu_atual).find('.menu_coluna').each(function(index) {
						if(menu_tamanho < $(this).height()){
							menu_tamanho = $(this).height();
						}
					});
					
					$("#_menu_"+menu_atual).find('.menu_coluna').each(function(index2) {
						$(this).css('height',menu_tamanho+'px');
					});
					
					menu_barra_processado["#_menu_"+menu_atual] = true;
				}, tempo);
			}
		} else {
			if(variaveis_js.menu_setinha)$('#menu-setinha').hide();
			if(variaveis_js.menu_principal_bg)if(menu_obj_ant)menu_obj_ant.css('background-image', 'url('+variaveis_js.site_raiz+'files/projeto/images/'+menu_obj_ant.attr("id")+'.png)');
			menu_obj_ant = $("<div></div>");
		}
		
		if(menu_atual)menu_principal_anterior = menu_atual;
	}
	
	menu_principal_start();

	// ================================== jPlayer ========================
	
	if($("#_background_video").length){
		var auto_play = true;
		var player_id = "#_background_player";
		var site_bg = "#_background_site_bg";
		var site_bg_mask = "#_background_site_bg_mask";
		var player_mask = "#_background_video_mask";
		var lista_videos = Array();
		var lista_imagens = Array();
		var num_video = 0;
		var num_imagem = 0;
		var total_videos;
		var total_imagens;
		var video_def_w = 1920;
		var video_def_h = 1080;
		var video_w = 0;
		var video_h = 0;
		var margin_w = 0;
		var margin_h = 0;
		var video_on;
		var video_on2;
		var video_ready;
		var video_primeira_exec = true;
		var arr_img_vid;
		
		if(!variaveis_js.mobile){
			var bg_dinam = variaveis_js.background_dinamico;
			
			for(var i=0;i<bg_dinam.length;i++){
				if(bg_dinam[i]){
					arr_img_vid = bg_dinam[i].split('.');
					
					switch(arr_img_vid[1]){
						case 'm4v':
						case 'webm':
						case 'ogv':
							lista_videos.push(site_raiz+bg_dinam[i]);
						break;
						case 'jpg':
						case 'gif':
						case 'png':
							lista_imagens.push(site_raiz+bg_dinam[i]);
						break;
						
					}
				}
				
			}
			
			total_videos = lista_videos.length;
			total_imagens = lista_imagens.length;
			
			video_resolution();
		}
		
		$(player_id).hide();
		$(site_bg).hide();
		
		if(!variaveis_js.mobile){
			$(player_id).jPlayer( {
				swfPath: site_raiz+"includes/js/jPlayer/jquery.jplayer",
				supplied: "webm, ogv, m4v",
				errorAlerts: true,
				loop : true,
				preload : "auto",
				size: {
					width: video_w,
					height: video_h,
					cssClass: 'jp-video-180p'
				},
				ready: function () {
					video_ready = true;
					
					if(total_videos > 0){
						arr_img_vid = lista_videos[num_video].split('.');
						
						switch(arr_img_vid[1]){
							case 'webm':
								$(player_id).jPlayer("setMedia", {
									webm: lista_videos[num_video]
								});
							break;
							case 'ogv':
								$(player_id).jPlayer("setMedia", {
									ogv: lista_videos[num_video]
								});
							break;
							case 'm4v':
								$(player_id).jPlayer("setMedia", {
									m4v: lista_videos[num_video]
								});
							break;
							
						}
						
						$(player_id).bind($.jPlayer.event.ended + ".jp-repeat", function(event) { // Using ".jp-repeat" namespace so we can easily remove this event
							num_video++;
							
							if(num_video >= total_videos){
								num_video = 0;
							}
							
							arr_img_vid = lista_videos[num_video].split('.');
							
							switch(arr_img_vid[1]){
								case 'webm':
									$(player_id).jPlayer("setMedia", {
										webm: lista_videos[num_video]
									});
								break;
								case 'ogv':
									$(player_id).jPlayer("setMedia", {
										ogv: lista_videos[num_video]
									});
								break;
								case 'm4v':
									$(player_id).jPlayer("setMedia", {
										m4v: lista_videos[num_video]
									});
								break;
								
							}
							
							$(player_id).jPlayer("play");
						});
					}
				}
			});
		}
		
		$(window).resize(function () {
			video_resolution();
		});
		
		function video_resolution(){
			var correcao = 0;
			var window_w = parseInt($(window).width())+correcao;
			var window_h = parseInt($(window).height());
			
			if(video_def_w / video_def_h > window_w / window_h){
				video_h = window_h;
				video_w = (window_h*video_def_w)/video_def_h;
			} else if(video_def_w / video_def_h < window_w / window_h) {
				video_w = window_w;
				video_h = (window_w*video_def_h)/video_def_w;
			} else {
				video_w = window_w;
				video_h = window_h;
			}
			
			video_w = Math.round(video_w);
			video_h = Math.round(video_h);
			
			if(total_videos > 0){
				if(!variaveis_js.mobile)
					$(player_id).jPlayer("option","size",{
						width: video_w,
						height: video_h
					});
			}
			
			$(site_bg).width(window_w);
			$(site_bg).height(window_h);
		}
		
		function video_trigger(){
			if(!variaveis_js.mobile && total_videos > 0){
				if('http://'+location.hostname+site_raiz == href_atual){
					video_on = true;
					video_on2 = true;
				} else {
					video_on = false;
				}
				
				video_verify();
			} else {
				change_img_bg();
			}
		}
		
		function video_player(){
			if(video_on){
				$(player_id).show();
				$(site_bg).hide();
				
				$(player_id).jPlayer("play");
				
				if(video_primeira_exec){
					video_primeira_exec = false;
					
					var click = document.ontouchstart === undefined ? 'click' : 'touchstart';
					var kickoff = function () {
					  $(player_id).jPlayer("play");
					  document.documentElement.removeEventListener(click, kickoff, true);
					};
					document.documentElement.addEventListener(click, kickoff, true);
				}
			} else {
				if(video_on2){
					$(player_id).jPlayer("pause");
					$(player_id).hide();
				}
				
				change_img_bg();
				video_on2 = false;
			}
		}
		
		function num_bg(){
			var rand = Math.random();
			var fator = 1 / total_imagens;
			var retorno = Math.floor((rand / fator));
			
			if(retorno > total_imagens)
				return total_imagens;
			else
				return retorno;
		}
		
		function video_verify(){
			if(!video_ready){
				setTimeout(video_verify,100);
			} else {
				video_player();
			}
		}
		
		function change_img_bg(){
			var img_src;
			
			if(total_imagens > 0){
				num_imagem = num_bg();
				img_src = lista_imagens[num_imagem];
				
				if(video_primeira_exec){
					video_primeira_exec = false;
				}
				
				$(site_bg).hide();
				$(site_bg).html('');
				$(site_bg).image(img_src);
				$(site_bg).show();
			}
		}
		
		video_trigger();
		
	}
	
	// ======================================================================================================
	
	var form_serialize = '';
	window.form_serialize = '';
	
	function pesquisar(){
		var enviar = true;
		var campo;
		var post;
		var mens;
		var campos = Array();
		var posts = Array();
		var opcao = 'procurar';
		var href = 'procurar';
		var limpar_campos = false;
		var mudar_pagina = true;
		
		campo = "buscar_pesquisa"; post = "pesquisa"; mens = "É obrigatório definir a pesquisa!"; if(!$("#"+campo).val()){ $.alerta_open(mens,false,false); $("#"+campo).addClass('input-vazio'); enviar = false; } else { $("#"+campo).removeClass('input-vazio');campos.push(campo);posts.push(post);}
		
		if(!enviar){
			return false;
		} else {
			enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina);
		}
	}
	
	window.enviar_form_simples = function enviar_form_simples(form_id){
		flags['scroll'] = false;
		var dataString = 'ajax_page=1';
		
		dataString = dataString + '&' + $('#'+form_id).serialize();
		$('#'+form_id)[0].reset();
		
		aplicar_scripts_after(null);
		if(global_vars.colorbox_open){$.colorbox.close();global_vars.colorbox_open = false;}
		
		$.ajax({
			type: 'POST',
			url: document.location.protocol+'//'+location.hostname+site_raiz,
			data: dataString,
			beforeSend: function(){
				$('#ajax_lendo').fadeIn(tempo_animacao);
			},
			success: function(txt){
				if(debug_retorno)console.log(txt);
				var dados = eval('(' + txt + ')');
				
				if(dados.log)console.log(dados.log);
				if(dados.redirecionar)window.open(document.location.protocol+'//'+document.location.hostname+dados.redirecionar,'_self');
				if(dados.redirecionar_ajax)window.link_trigger.attr('href',document.location.protocol+'//'+document.location.hostname+dados.redirecionar_ajax).trigger('click');
				if(dados.ler_css)ler_css(dados.ler_css);
				if(dados.ler_scripts)ler_scripts(dados.ler_scripts);
				if(dados.alerta_appendto_body) global_vars.alerta_appendto_body = dados.alerta_appendto_body;
				
				ajax_vars = dados;
				
				$('#ajax_lendo').fadeOut(tempo_animacao);
				
				if(dados.alerta)$.alerta_open(dados.alerta,false,false);
			},
			error: function(txt){
				$('#ajax_erro').fadeIn(tempo_animacao);
				setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
			}
		});
	}
	
	window.enviar_form = function enviar_form(campos,posts,opcao,href,limpar_campos,mudar_pagina){
		flags['scroll'] = false;
		var dataString = 'ajax_page=1';
		
		if(window.form_serialize != ''){
			dataString = dataString + '&' + window.form_serialize;
			window.form_serialize = '';
		} else {
			if(form_serialize != ''){
				dataString = dataString + '&' + form_serialize;
				form_serialize = '';
			} else {
				for(var i=0;i<campos.length;i++){
					dataString = dataString + '&' + posts[i] + '=' + $("#"+campos[i]).val();
					if(limpar_campos)$("#"+campos[i]).val('');
				}
				
				dataString = dataString + '&opcao=' + opcao;
			}
		}
		
		href_atual = href;
		
		aplicar_scripts_after({banner_href:href});
		if(global_vars.colorbox_open){$.colorbox.close();global_vars.colorbox_open = false;}
		
		$.ajax({
			type: 'POST',
			url: document.location.protocol+'//'+location.hostname+site_raiz+href,
			data: dataString,
			beforeSend: function(){
				$('#ajax_lendo').fadeIn(tempo_animacao);
			},
			success: function(txt){
				if(debug_retorno)console.log(txt);
				var dados = eval('(' + txt + ')');
				
				if(dados.log)console.log(dados.log);
				if(dados.redirecionar)window.open(document.location.protocol+'//'+document.location.hostname+dados.redirecionar,'_self');
				if(dados.redirecionar_ajax)window.link_trigger.attr('href',document.location.protocol+'//'+document.location.hostname+dados.redirecionar_ajax).trigger('click');
				if(dados.ler_css)ler_css(dados.ler_css);
				if(dados.ler_scripts)ler_scripts(dados.ler_scripts);
				if(dados.alerta_appendto_body) global_vars.alerta_appendto_body = dados.alerta_appendto_body;
				
				ajax_vars = dados;
				
				$('#ajax_lendo').fadeOut(tempo_animacao);
				
				if(mudar_pagina){
					history_flag = false;
					
					if(dados.menu_paginas){
						menu_paginas = dados.menu_paginas;
						menu_pagina = 1;
					}
					if(menus[dados.opcao]){
						flags['scroll'] = true;
						opcao_atual = menus[dados.opcao];
					} else {
						opcao_atual = dados.opcao;
					}
					
					History.pushState({
						page:dados.page,
						ajax_vars:ajax_vars,
						addthis:dados.addthis,
						opcao_atual:opcao_atual,
						menu_paginas:menu_paginas,
						menu_pagina:menu_pagina
					},dados.titulo,document.location.protocol+'//'+location.hostname+site_raiz+href+(dados.procurar_pesquisa?'/'+dados.procurar_pesquisa:''));
					
					$(cont_principal).html(dados.page);
					flags['scroll2'] = false;
					flags['scroll2'] = true;
					if(dados.addthis == '1')exec_addthis();
					if(window._gaq && site_teste != 'sim' && !dados.noindex)window._gaq.push(['_trackPageview', href+(dados.procurar_pesquisa?'/'+dados.procurar_pesquisa:'')]);
					if(dados.debug)$.alerta_open(txt,false,false);
					history_flag = true;
					
					aplicar_scripts({href:document.location.protocol+'//'+location.hostname+site_raiz+href});
					
					$('body').scrollTop(0);
					$('html').scrollTop(0);
					$(document).scrollTop(0);
				}
				
				if(dados.alerta)$.alerta_open(dados.alerta,false,false);
			},
			error: function(txt){
				$('#ajax_erro').fadeIn(tempo_animacao);
				setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
			}
		});
	}
	
	function checkMail(mail){
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
	}
	
	function validar_senha2(){
		var mens;
		var cor;
		
		if($("#senha").val() && $("#senha2").val()){
			var valido = true;
			
			if($("#senha").val() != $("#senha2").val()){
				valido = false;
				mens = "Senha e Redigite a Senha são diferentes.";
				if(!alert_status){alert(mens);alert_status = true;}
			}
			
			if(checkStr($("#senha").val())){
				valido = false;
				mens = "Caracteres inválidos, apenas caracteres alfanuméricos e ( _ ou @ ou . )";
				if(!alert_status){alert(mens);alert_status = true;}
			}
			
			if(!limites_str($("#senha").val(),3,20)){
				valido = false;
				mens = "Senha no mínimo 3 e no máx 20 caracteres.";
				if(!alert_status){alert(mens);alert_status = true;}
			}
			
			if(!valido){
				cor = cor1;
				cadastrar_senha = false;
				$("#senha").val('');
				$("#senha2").val('');
			} else {
				mens = "Senha OK.";
				cor = cor2;
				cadastrar_senha = true;
			}
			
			$("#mens_senha").html(mens);
		}
	}
	
	function validar_usuario(){
		var valido = true;
		var mens = "";
		var cor;
		
		if(checkStr($("#usuario").val())){
			valido = false;
			mens = "Caracteres inválidos, apenas caracteres alfanuméricos e ( _ ou @ ou . ).";
			if(!alert_status){alert(mens);alert_status = true;}
		}
		
		if(!limites_str($("#usuario").val(),3,20)){
			valido = false;
			mens = "Usuário no mínimo 3 e no máx 20 caracteres.";
			if(!alert_status){alert(mens);alert_status = true;}
		}
		
		if(!valido){
			cor = cor1;
			cadastrar_usuario = false;
		} else {
			mens = "Usuário OK.";
			cor = cor2;
			cadastrar_usuario = true;
			$("#senha").focus();
		}
		
		$("#mens_usuario").html(mens);
	}
	
	function checkStr(pass){
		var er = new RegExp(/[^A-Za-z0-9_@.]/);
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
	
	function limites_str(str,l1,l2){
		if(str.length >= l1 && str.length <= l2 )	
			return true;
		else
			return false;
	}
	
	function ler_scripts(scripts){
		if(scripts){
			for(var i=0;i<scripts.length;i++){
				if(!scripts_loaded[scripts[i]]){
					$.getScript(site_raiz+scripts[i]);
					scripts_loaded[scripts[i]] = true;
				}
			}
		}
	}
	
	function ler_css(stylesheets){
		if(stylesheets){
			for(var i=0;i<stylesheets.length;i++){
				if(!stylesheets_loaded[stylesheets[i]]){
					$('<link rel="stylesheet" type="text/css" href="'+site_raiz+stylesheets[i]+'" >').appendTo("head");
					stylesheets_loaded[stylesheets[i]] = true;
				}
			}
		}
	}
	
});