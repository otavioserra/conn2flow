var tempo_animacao = 150;
var variaveis_js = {};
var href_atual = location.href;
var b2make_url = 'http://localhost/sistemas/b2make/';
var scripts_functions = {};
var global_vars = {};
var stylesheets_loaded = Array();
var scripts_loaded = Array();
var b2make = eval('(' + $('meta[name=b2make]').attr("content") + ')');
	
variaveis_js.projeto = {
	site_raiz : '/b2make-site/',
	js : 'http://localhost/sistemas/b2make/files/projeto/projeto.js',
	css : 'http://localhost/sistemas/b2make/files/projeto/layout.css',
	css_ie7 : 'http://localhost/sistemas/b2make/files/projeto/projeto-ie7.css',
	css_ie8 : 'http://localhost/sistemas/b2make/files/projeto/projeto-ie8.css',
	css_ie9_acima : 'http://localhost/sistemas/b2make/files/projeto/projeto-ie9-gte.css',
	css_not_ie : 'http://localhost/sistemas/b2make/files/projeto/projeto-not-ie.css'
};

variaveis_js.ler_css = new Array(
	'http://fonts.googleapis.com/css?family=Ubuntu:400,700,400italic,700italic',
	b2make_url+'includes/css/padrao.css',
	b2make_url+'files/projeto/layout-padrao.css',
	b2make_url+'includes/css/interface.css',
	b2make_url+'includes/css/index.css',
	b2make_url+'includes/js/prettyPhoto/css/prettyPhoto.css',
	b2make_url+'includes/js/jPlayer/blue.monday/jplayer.blue.monday.css',
	b2make_url+'includes/js/jquery.elevatezoom/jquery.fancybox.css',
	b2make_url+'includes/js/colorbox/colorbox.css',
	variaveis_js.projeto.css
);

variaveis_js.ler_scripts = new Array(
	b2make_url+'includes/js/padrao.js',
	b2make_url+'includes/js/history/history.adapter.jquery.js',
	b2make_url+'includes/js/history/history.js',
	b2make_url+'includes/js/history/history.html4.js',
	b2make_url+'includes/js/jquery_ui_effects/jquery-ui-1.8.21.custom.min.js',
	b2make_url+'includes/js/imageCycle/jquery.cycle.all.js',
	b2make_url+'includes/js/prettyPhoto/js/jquery.prettyPhoto.js',
	b2make_url+'includes/js/jPlayer/jquery.jplayer.min.js',
	b2make_url+'includes/js/jquery.elevatezoom/jquery.elevateZoom-3.0.8.min.js',
	b2make_url+'includes/js/jquery.elevatezoom/jquery.fancybox.pack.js',
	b2make_url+'includes/js/colorbox/jquery.colorbox-min.js',
	variaveis_js.projeto.js
);

function msieversion(){
	var ua = window.navigator.userAgent;
	var msie = ua.indexOf("MSIE ");

	if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./))      // If Internet Explorer, return version number
		return parseInt(ua.substring(msie + 5, ua.indexOf(".", msie)));
	else                 // If another browser, return 0
		return -1;
}

if(msieversion() > 0 && msieversion() < 7){console.log('s');document.write('<script src="'+b2make_url+'includes/js/ie6/warning.js"><\/script>'); window.onload = function(){e(b2make_url+"includes/js/ie6/");}}
if(typeof window.JSON === 'undefined'){document.write('<script src="'+b2make_url+'includes/js/history/json2.js"><\/script>');}

if(!window.console){ window.console = {log: function(){} }; }

function ler_scripts(scripts){
	if(scripts){
		for(var i=0;i<scripts.length;i++){
			if(!scripts_loaded[scripts[i]]){
				$.getScript(scripts[i]);
				scripts_loaded[scripts[i]] = true;
			}
		}
	}
}

function ler_css(stylesheets){
	if(stylesheets){
		for(var i=0;i<stylesheets.length;i++){
			if(!stylesheets_loaded[stylesheets[i]]){
				$('<link rel="stylesheet" type="text/css" href="'+stylesheets[i]+'" >').appendTo("head");
				stylesheets_loaded[stylesheets[i]] = true;
			}
		}
	}
}

var UTF8 = {
    encode: function(s){
        for(var c, i = -1, l = (s = s.split("")).length, o = String.fromCharCode; ++i < l;
            s[i] = (c = s[i].charCodeAt(0)) >= 127 ? o(0xc0 | (c >>> 6)) + o(0x80 | (c & 0x3f)) : s[i]
        );
        return s.join("");
    },
    decode: function(s){
        for(var a, b, i = -1, l = (s = s.split("")).length, o = String.fromCharCode, c = "charCodeAt"; ++i < l;
            ((a = s[i][c](0)) & 0x80) &&
            (s[i] = (a & 0xfc) == 0xc0 && ((b = s[i + 1][c](0)) & 0xc0) == 0x80 ?
            o(((a & 0x03) << 6) + (b & 0x3f)) : o(128), s[++i] = "")
        );
        return s.join("");
    }
};

if(variaveis_js.ler_css){
	ler_css(variaveis_js.ler_css);
}
if(variaveis_js.ler_scripts){
	ler_scripts(variaveis_js.ler_scripts);
}

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

jQuery.fn.center = function () {
    this.css("position","absolute");
    this.css("top", Math.max(0, (($(window).height() - $(this).outerHeight()) / 2) + $(window).scrollTop()) + "px");
    this.css("left", Math.max(0, (($(window).width() - $(this).outerWidth()) / 2) + $(window).scrollLeft()) + "px");
    return this;
}

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
	var cont_principal = '#cont_principal';
	var cont_secundario = '#cont_secundario';
	var history_flag = true;
	var history_first = true;
	var history_1st_access = true;
	var tempo_animacao2 = 150;
	var tempo_animacao3 = 300;
	var all_js_ativo = true;
	var link_clicked = false;
	
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
	
	function b2make_inicio(){
		if($.browser.msie){
			var href_aux = href_atual;
			var href_arr = href_aux.split('#.');
			href_atual = href_arr[0];
		}
		
		if(b2make.root != href_atual){
			var body_atual = $('body').html();
			$('body').html('<div id="cont_principal"></div>');
			
			$.ajax({
				type: 'GET',
				url: b2make.root,
				data: {ajax_page:1},
				dataType: 'html',
				beforeSend: function(){
					
				},
				success: function(txt){
					var re = new RegExp("<body>(?:.|\n|\r)+?</body>", "gim");
					var body_arr = re.exec(txt);
					var body_layout;
					
					if(body_arr != null){
						re = new RegExp("<body>", "gim");
						body_arr[0] = body_arr[0].replace(re,"" );
						re = new RegExp("</body>", "gim");
						body_layout = body_arr[0].replace(re,"" );
						$('body').html(body_layout);
						$('#cont_principal').html(body_atual);
					}
				},
				error: function(txt){
					$('#cont_principal').html(body_atual);
				}
			});
		}
	}
	
	b2make_inicio();
	
	$(window).load(function(){
		if(window._gaq && site_teste != 'sim' && !variaveis_js.noindex)window._gaq.push(['_trackPageview', location.href]);
		
		if(!all_js_ativo)return;
		
		window.link_trigger = $("<a></a>");
		window.link_trigger.appendTo('body');
		window.link_trigger.hide();

		if(msieversion() > 0){$('.borderitem').css('border-style','solid');}
		
		if(msieversion() == 7){ler_css(new Array(variaveis_js.projeto.css_ie7));}
		if(msieversion() == 8){ler_css(new Array(variaveis_js.projeto.css_ie8));}
		if(msieversion() >= 9){ler_css(new Array(variaveis_js.projeto.css_ie9_acima));}
		if(msieversion() < 0){ler_css(new Array(variaveis_js.projeto.css_not_ie));}
		
		// ======================== KeyUp and KeyDown ==================================
		
		$(document).keydown(function(e){
			if(e.keyCode == 17 || e.keyCode == 16){
				global_vars.ctrl_ativo = true;
			}
		});
		
		$(document).keyup(function(e){
			if(e.keyCode == 17 || e.keyCode == 16){
				global_vars.ctrl_ativo = false;
			}
		});
		
		// ======================== Scripts Iniciais ==================================
		
		function aplicar_scripts_iniciais(){
			
		}
		
		aplicar_scripts_iniciais();
		
		// ======================== Scripts Din?cos ==================================
		
		function aplicar_scripts_after(params){
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
			
			if($("a[rel^='prettyPhoto']").length){
				var prettyphoto_var = {animation_speed:'fast',slideshow:3000, hideflash: true, deeplinking: false, twitter: true, facebook: true};
				
				if(projeto_js)
				if(projeto_js.prettyphoto)
					prettyphoto_var = projeto_js.prettyphoto;
				
				setTimeout(function(){ $("a[rel^='prettyPhoto']").prettyPhoto(prettyphoto_var); }, 100);
			}
			
			// ================================== Facebook Comments ========================
			
			if($("#facebook-commentarios").length){
				if(!global_vars.fb_sdk){
					global_vars.fb_sdk = true;
				} else {
					window.FB.XFBML.parse();
				} 
			}
			
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
			
			$.aplicar_scripts(params);
		}
		
		aplicar_scripts_after(null);
		aplicar_scripts(null);
		
		// ================================== Navega? Ajax ========================
		
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
		
		function history_start(){
			if(History.Adapter){
				History.Adapter.bind(window,'statechange',function(){
					if($.browser.msie && link_clicked){
						history_flag = false;
					}
					
					if(history_flag && !history_first){
						var State = History.getState();
						
						href_atual = State.url;
						
						aplicar_scripts_after({history:true});
						aplicar_scripts({history:true});
						
						$(cont_principal).html(State.data.page);
					}
					
					if($.browser.msie && link_clicked){
						history_flag = true;
						link_clicked = false;
					}
					
					if(history_first){
						history_first = false;
					}
				});
			} else {
				setTimeout(history_start,100);
			}
		}
		
		history_start();
		
		var ajax_lendo = $('<div id="ajax_lendo">Carregando</div>');
		ajax_lendo.center();
		ajax_lendo.appendTo('body');
		var ajax_erro = $('<div id="ajax_erro">Erro<p>Não foi possível ler a página!</p></div>');
		ajax_erro.center();
		ajax_erro.appendTo('body');
		
		$('a,area').live('click',function(event){
			history_flag = false;
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
				if(href.search(/ftp:/)>=0)ajax_nao = true;
				
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
						$('#ajax_erro').hide();
						$('#ajax_lendo').fadeIn(tempo_animacao);
						pagina_anterior = $(cont_principal).html();
						if($(cont_principal).length){
							$.ajax({
								type: 'GET',
								url: href,
								data: {ajax_page:1},
								dataType: 'html',
								beforeSend: function(){
									
								},
								success: function(txt){
									var re = new RegExp("<body>(?:.|\n|\r)+?</body>", "gim");
									var body_arr = re.exec(txt);
									var body_layout;
									
									if(body_arr != null){
										re = new RegExp("<body>", "gim");
										body_arr[0] = body_arr[0].replace(re,"" );
										re = new RegExp("</body>", "gim");
										body_layout = body_arr[0].replace(re,"" );
										
										$(cont_principal).html(body_layout);
										
										var re2 = new RegExp("<title>(?:.|\n|\r)+?</title>", "gim");
										var title_arr = re2.exec(txt);
										var titulo;
										
										if(title_arr != null){
											re2 = new RegExp("<title>", "gim");
											title_arr[0] = title_arr[0].replace(re2,"" );
											re2 = new RegExp("</title>", "gim");
											titulo = title_arr[0].replace(re2,"" );
										}
										
										history_flag = true;
										link_clicked = true;
										
										History.pushState({
											page:$(cont_principal).html()
										},titulo,href);
										
										aplicar_scripts({href:href});
										
										if(!global_vars.link_nao_mudar_scroll){
											$('body').scrollTop(0);
											$('html').scrollTop(0);
											$(document).scrollTop(0);
											global_vars.link_nao_mudar_scroll = false;
										}
										
										$('body').css('cursor','default');
										$('#ajax_lendo').fadeOut(tempo_animacao);
									} else {
										$('#ajax_lendo').hide();
										$('#ajax_erro').fadeIn(tempo_animacao);
										setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
										history_flag = true;
										
										$(cont_principal).html('<div id="conteudo"><h2>404</h2><p>Página Não Encontrada</p></div>');
										titulo = '404 - Página Não Encontrada';
										
										History.pushState({
											page:$(cont_principal).html()
										},titulo,href);
									}
								},
								error: function(txt){
									$('#ajax_lendo').hide();
									$('#ajax_erro').fadeIn(tempo_animacao);
									setTimeout(function(){ $('#ajax_erro').fadeOut(tempo_animacao); }, 1000);
									history_flag = true;
									
									$(cont_principal).html('<div id="conteudo"><h2>404</h2><p>Página Não Encontrada</p></div>');
									titulo = '404 - Página Não Encontrada';
									
									History.pushState({
										page:$(cont_principal).html()
									},titulo,href);
								}
							});
						} else {
							$('#ajax_lendo').fadeOut(tempo_animacao);
							$.alerta_open("<p>Conteiner #cont_principal não encontrado!</p><p>É necessário ter conteiner em algum lugar do layout:</p><p>&lt;div id=\"cont_principal\"&gt;!#body#!&lt;/div&gt;</p>",false,false);
							history_flag = true;
						}
					} else {
						history_flag = true;
					}
				}
			}
		});
		
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
		
	});
});