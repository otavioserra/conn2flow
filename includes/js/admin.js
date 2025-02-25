var b2makeAdmin = {};
b2makeAdmin.msgs = {};

var sep;
var raiz;
var alerta;
var alerta_php;
var popup;
var popup_ativo = false;
var debug_retorno = true;
var b2make = {};

b2make.console_tempo_animacao = 300;
b2make.console_tempo_mostrar_texto = 2000;

$.b2make_enviar_formulario = function(p){
	/* 
		Função responsável por fazer uma requisição ajax e enviar o formulário
	*/
	if(!p)p = {};
	
	var form_id = p.form_id;
	var dataString = 'ajax=1&b2make_ajax=1';
	
	dataString = dataString + '&' + $('#'+form_id).serialize();
	if(!p.local)p.local = $('#_formulario-local').val();
	
	$.ajax({
		type: 'POST',
		url: '.',
		data: dataString,
		beforeSend: function(){
			$.b2make_console_formulario({estado:'open',local:p.local});
		},
		success: function(txt){
			if(debug_retorno)console.log(txt);
			var dados = eval('(' + txt + ')');
			
			if(dados.ERRO)console.log(dados.ERRO);
			
			$.b2make_console_formulario({estado:'success',local:p.local,dados:dados});
			
			if(!p.dont_close_console){
				setTimeout(function(){
					$.b2make_console_formulario({estado:'close',local:p.local});
					
					setTimeout(function(){
						$.b2make_console_formulario({estado:'reset',reset:true,local:p.local});
					},b2make.console_tempo_animacao);
				},b2make.console_tempo_mostrar_texto);
			}
		},
		error: function(txt){
			$.b2make_console_formulario({texto:'Erro: '+txt,local:p.local});
		}
	});
};

$.b2make_enviar_requisicao = function(p){
	/* 
		Função responsável por fazer uma requisição ajax e enviar o variáveis para uma operação
	*/
	if(!p)p = {};

	var dataString = 'ajax=1&b2make_ajax=1&_formulario-auth='+encodeURIComponent($('#_formulario-auth').val())+'&_formulario-key='+encodeURIComponent($('#_formulario-key').val());
	
	if(p.params)dataString = dataString + '&' + $.param(p.params);
	if(!p.local)p.local = $('#_formulario-local').val();
	
	$.ajax({
		type: 'POST',
		url: '.',
		data: dataString,
		beforeSend: function(){
			$.b2make_console_formulario({estado:'open',local:p.local});
		},
		success: function(txt){
			if(debug_retorno)console.log(txt);
			var dados = eval('(' + txt + ')');
			
			$.b2make_console_formulario({estado:'success',local:p.local,dados:dados});
			
			if(!p.dont_close_console){
				setTimeout(function(){
					$.b2make_console_formulario({estado:'close',local:p.local,dados:dados});
					
					setTimeout(function(){
						$.b2make_console_formulario({estado:'reset',reset:true,local:p.local,dados:dados});
					},b2make.console_tempo_animacao);
				},b2make.console_tempo_mostrar_texto);
			}
		},
		error: function(txt){
			$.b2make_console_formulario({texto:'Erro: '+txt,local:p.local});
		}
	});
};

$.b2make_console_formulario = function(p){
	if(!p)p = {};
	
	if(p.resize && !b2make.console_formulario_existe){
		return;
	}
	
	var console;
	var console_shadow;
	var console_txt;
	var console_tit;
	
	if(!b2make.console_formulario_existe){
		console = $.b2make_criar_tag({nome:'b2make-console-formulario'});
		console_shadow = $.b2make_criar_tag({nome:'b2make-console-shadow',start_hide:true});
		console_tit = $.b2make_criar_tag({nome:'b2make-console-formulario-tit',tag:'h3'});
		console_txt = $.b2make_criar_tag({nome:'b2make-console-formulario-txt'});
		
		console_tit.html(p.console_tit?p.console_tit:'Console');
		console_tit.appendTo(console);
		console_txt.appendTo(console);

		var pl = $(window).width() / 2 - console.width() / 2;
		var pt = $(window).height();
		
		console.css('top',pt);
		console.css('left',pl);
		
		b2make.console_formulario_existe = true;
	} else {
		console = $('#b2make-console-formulario');
		console_shadow = $('#b2make-console-shadow');
		console_tit = $('#b2make-console-formulario-tit');
		console_txt = $('#b2make-console-formulario-txt');
	}
	
	if(p.texto){
		console_txt.append('<div>'+p.texto+'</div>');
		console_txt.scrollTop(console_txt[0].scrollHeight);
	}
	
	if(p.reset){
		console_txt.html('');
	}
	
	if(p.resize){
		if(b2make.console_formulario_aberto){
			var pl = $(window).width() / 2 - console.width() / 2;
			var pt = $(window).height() - console.height();
			
			console.css('top',pt);
			console.css('left',pl);
		} else {
			var pl = $(window).width() / 2 - console.width() / 2;
			var pt = $(window).height();
			
			console.css('top',pt);
			console.css('left',pl);
		}
	}
	
	switch(p.estado){
		case 'open':
			if(!b2make.console_formulario_aberto){
				b2make.console_formulario_aberto = true;
				console_shadow.fadeIn(b2make.console_tempo_animacao);
				console.animate({top:$(window).height() - console.height()},b2make.console_tempo_animacao,function(){$.b2make_console_calback(p);});
			}
		break;
		case 'close':
			if(b2make.console_formulario_aberto){
				b2make.console_formulario_aberto = false;
				console_shadow.fadeOut(b2make.console_tempo_animacao);
				console.animate({top:$(window).height()},b2make.console_tempo_animacao,function(){$.b2make_console_calback(p);});
			}
		break;
		case 'close-time':
			if(b2make.console_formulario_aberto){
				b2make.console_formulario_aberto = false;
				setTimeout(function(){
					console_shadow.fadeOut(b2make.console_tempo_animacao);
					console.animate({top:$(window).height()},b2make.console_tempo_animacao,function(){$.b2make_console_calback(p);});
					
					setTimeout(function(){
						$.b2make_console_formulario({estado:'reset',reset:true,local:p.local});
					},b2make.console_tempo_animacao);
				},b2make.console_tempo_mostrar_texto);
			}
		break;
		default:
			$.b2make_console_calback(p);
	}
};

$.b2make_console_calback = function(p){
	if(!p)p = {};
	
	if($.b2make_local_callback){
		$.b2make_local_callback(p);
	}
};

$.b2make_criar_tag = function(p){
	if(!p)p = {};
	
	var conteiner;
	
	if(!p.tag){p.tag = 'div';}
	if(!p.nome){$.b2make_log({mens:'[b2make_criar_tag]! É obrigatório preencher o nome do conteiner!'});return false;}
	
	if($('#'+p.nome).length == 0){
		conteiner = $('<'+p.tag+' id="'+p.nome+'">'+(p.tag_aberta?'':'</'+p.tag+'>'));
		conteiner.appendTo('body');
		
		if(p.start_hide)conteiner.hide();
	} else {
		conteiner = $('#'+p.nome);
	}
	
	return conteiner;
};

$.b2make_redirect = function(p){
	if(!p)p = {};
	
	if(!p.local){
		p.local = '.';
	} else {
		p.local = document.location.protocol+'//'+document.location.hostname+raiz+p.local;
	}
	
	if(!p.target)p.target = '_self';
	
	window.open(p.local,p.target);
};

$.b2make_log = function(p){
	if(!p)p = {};
	
	console.log('B2Make: '+p.mens);
};

var url_name = function (){
	var url_aux = location.pathname;
	var url_parts;
	
	url_parts = url_aux.split('/');
	
	if(url_parts[url_parts.length-1])
		return url_parts[url_parts.length-1];
	else
		return '.';
};

var excluir = function(url,id,opcao){
	if(id){
		if(confirm("Tem certeza que deseja excluir esse item?")){
			window.open(url+"?opcao="+opcao+"&id="+id,"_self");
		}
	}
};

function b2make_init_callback(obj){
	b2make.tinymce_editor_obj = obj;
	
	if(!b2make.plataforma_nao_design){	
		$.b2make_tinymce_change({ready:true,obj:obj});
		$.b2make_tinymce_resize({height:$(b2make.selecionador_objetos.conteiner).outerHeight(true)});
	} else {
		b2make.tinymce_ready = true;
		$.b2make_tinymce_resize({});
	}
}

function b2make_init_callback_2(obj){
	b2make.tinymce_editor_obj = obj;
	
	$.b2make_tinymce_change_2({ready:true,obj:obj});
	$.b2make_tinymce_resize_2({});
}

$.b2make_tinymce_resize = function(p){
	if(b2make.tinymce_ready){
		if(!b2make.plataforma_nao_design){
			var editor = b2make.tinymce_editor_obj;
			var edToolbar = $('#b2make-texto-complexo-textarea-'+b2make.tinymce_count).parent().find("div.mce-toolbar-grp");
			var edStatusbar = $('#b2make-texto-complexo-textarea-'+b2make.tinymce_count).parent().find("div.mce-statusbar");
			
			editor.theme.resizeTo('100%', p.height - edToolbar.outerHeight(true) - edStatusbar.outerHeight(true) - 5);
		} else {
			var editor = b2make.tinymce_editor_obj;
			var width = parseInt($('.campo-texto-complexo').css('width'));
			var height = (b2make.tinymce_mce_height ? b2make.tinymce_mce_height : parseInt($('.campo-texto-complexo').css('height')));
			
			editor.theme.resizeTo('100%',height);
		}
	}
};

$.b2make_tinymce_resize_2 = function(p){
	if(b2make.tinymce_ready){
		var editor = b2make.tinymce_editor_obj;
		var width = parseInt($('#b2make-accordion-texto-conteiner').css('width'));
		var height = parseInt($('#b2make-accordion-texto-conteiner').css('height'));
		
		editor.theme.resizeTo(width,height);
	}
};

$.b2make_tinymce_google_fonts_load = function(p){
	var found = false;
	
	if(variaveis_js.b2make_local != 'design'){
		return false;
	}
	
	if(fonts_installed_before){
		var fonts_installed_before = variaveis_js.google_fonts_installed;
		var fonts_arr = fonts_installed_before.split('|');
		
		for(var j=0;j<fonts_arr.length;j++){
			if(p.family == fonts_arr[j].replace(/\+/gi,' ')){
				return false;
			}
		}
	}
	
	if(!b2make.tinymce_google_fonts_loaded){
		b2make.tinymce_google_fonts_loaded = new Array();
	}
	
	for(var i=0;i<b2make.tinymce_google_fonts_loaded.length;i++){
		if(b2make.tinymce_google_fonts_loaded[i] == p.family){
			found = true;
			break;
		}
	}
	
	if(!found){
		b2make.tinymce_google_fonts_loaded.push(p.family);
		WebFont.load({
			google: {
				families: [p.family]
			},
			loading: function() {if(!p.nao_carregamento)$.carregamento_open();},
			active: function() {if(!p.nao_carregamento)$.carregamento_close();},
			inactive: function() {
				$.dialogbox_open({
					msg: b2make.msgs.googleFontsInative
				});
				
				if(!p.nao_carregamento)$.carregamento_close();
			},
			fontloading: function(familyName, fvd) {},
			fontactive: function(familyName, fvd) {},
			fontinactive: function(familyName, fvd) {}
		});
	}
}

$.b2make_tinymce_change_google_fonts = function(p){
	var opcao = 'google-fonts-change';
	var google_fontes = '';
	var selector = '';
	
	var texto = tinymce.activeEditor.getContent({format : 'raw'});
	
	$.b2make_tinymce_destroy();
	b2make.tinymce_count++;
	
	if(!b2make.plataforma_nao_design){
		$('#b2make-texto-complexo-conteiner').html('');
		$('#b2make-texto-complexo-conteiner').append('<div id="b2make-texto-complexo-textarea-'+b2make.tinymce_count+'"></div>');
		
		selector = '#b2make-texto-complexo-textarea-'+b2make.tinymce_count;
	} else {
		var id = tinymce.activeEditor.id;
		
		$('.b2make-texto-complexo-conteiner[data-id="'+id+'"]').html('');
		$('.b2make-texto-complexo-conteiner[data-id="'+id+'"]').append('<textarea name="'+id+'" id="'+id+'" class="campo-texto-complexo">'+texto+'</textarea>');
		
		selector = '#'+id;
	}
	
	var fonts_installed = b2make.google_fonts_installed;
	
	if(fonts_installed){
		for(var j=0;j<fonts_installed.length;j++){
			$.b2make_tinymce_google_fonts_load({family:fonts_installed[j].family,nao_carregamento:true});
			google_fontes = google_fontes + (google_fontes ? '|' : '') + fonts_installed[j].family.replace(/ /gi,'+');
		}
	}
	
	$.ajax({
		type: 'POST',
		url: raiz+'design/',
		data: { 
			ajax : 'sim',
			opcao : opcao,
			google_fontes : google_fontes
		},
		beforeSend: function(){
			if(!b2make.plataforma_nao_design)$.carregamento_open();
		},
		success: function(txt){
			if(
				txt.charAt(0) == "{" || 
				txt.charAt(0) == "["
			){
				var dados = eval('(' + txt + ')');
				
				switch(dados.status){
					case 'Ok':
						$.b2make_tinymce_start({selector:selector,value:texto});
					break;
					default:
						console.log('ERROR - '+opcao+' - '+dados.status);
					
				}
			} else {
				console.log('ERROR - '+opcao+' - '+txt);
			}
			
			if(!b2make.plataforma_nao_design)$.carregamento_close();
		},
		error: function(txt){
			console.log('ERROR AJAX - '+opcao+' - '+txt);
			if(!b2make.plataforma_nao_design)$.carregamento_close();
		}
	});
};

$.b2make_tinymce_destroy = function(p){
	if(!p) p = {};
	
	if(b2make.tinymce_ready){
		tinymce.execCommand('mceRemoveControl', true, 'b2make-texto-complexo-textarea-'+b2make.tinymce_count);
		b2make.tinymce_ready = false;
	}
};

$.b2make_tinymce_destroy_2 = function(p){
	if(!p) p = {};
	
	if(b2make.tinymce_ready){
		tinymce.execCommand('mceRemoveControl', true, p.selector);
		b2make.tinymce_ready = false;
	}
};

$.b2make_tinymce_change = function(p){
	if(!p) p = {};
	
	if(p.ready){
		b2make.tinymce_ready = true;
	}
	
	if(b2make.tinymce_ready){
		var val;
		
		if(p.value){
			val = p.value;
		} else if(b2make.tinymce_change){
			val = b2make.tinymce_change;
			b2make.tinymce_change = false;
		}
		
		if(val){
			if(p.obj){
				p.obj.setContent(val);
				p.obj.focus();
			} else {
				tinymce.get('b2make-texto-complexo-textarea-'+b2make.tinymce_count).setContent(val);
			}
		}
	} else {
		b2make.tinymce_change = p.value;
	}
};

$.b2make_tinymce_change_2 = function(p){
	if(!p) p = {};
	
	if(p.ready){
		b2make.tinymce_ready = true;
	}
	
	if(b2make.tinymce_ready){
		var val;
		
		if(p.value){
			val = p.value;
		} else if(b2make.tinymce_change){
			val = b2make.tinymce_change;
			b2make.tinymce_change = false;
		}
		
		
		if(val){
			if(p.obj){
				p.obj.setContent(val);
				p.obj.focus();
			} else {
				tinymce.get('b2make-accordion-texto').setContent(val);
			}
		}
	} else {
		b2make.tinymce_change = p.value;
	}
};

$.b2make_tinymce_start = function(p){
	if(!p) p = {};
	
	if(typeof tinymce !== 'undefined'){
		if(!p.selector){
			tinymce.init({
				menubar: false,
				selector: 'textarea.tinymce_mini',
				toolbar: 'undo redo code | styleselect | bold italic underline | link image | alignleft aligncenter alignright alignjustify',
				plugins: "image imagetools link code",
				directionality: 'pt_BR',
				language_url: raiz+'includes/js/tinyMce/pt_BR.js',
				branding: false,
				relative_urls : false,
				remove_script_host : false,
				resize: "both",
				convert_urls : true
			});
		} else {
			if(p.value){
				b2make.tinymce_change = p.value;
			}
			
			var fonts_installed = b2make.google_fonts_installed;
			var fonts_installed_before = variaveis_js.google_fonts_installed;
			var fonts_extra = '';
			
			if(fonts_installed){
				for(var j=0;j<fonts_installed.length;j++){
					fonts_extra = fonts_extra + fonts_installed[j].family + '=' + fonts_installed[j].family + ';';
				}
			} else if(fonts_installed_before){
				var fonts_arr = fonts_installed_before.split('|');
				
				for(var j=0;j<fonts_arr.length;j++){
					fonts_extra = fonts_extra + fonts_arr[j].replace(/\+/gi,' ') + '=' + fonts_arr[j].replace(/\+/gi,' ') + ';';
				}
			}
			
			if(!p.nao_texto_complexo){
				if(p.sem_filemanager_sem_instalar_fonts){
					tinymce.init({
						menubar: false,
						selector: (p.selector ? p.selector : 'textarea.tinymce'),
						toolbar: 'undo redo code | styleselect | bold italic underline | link image | alignleft aligncenter alignright alignjustify resizeEditor | fontselect fontsizeselect',
						plugins: "image imagetools link advcode",
						directionality: 'pt_BR',
						language: 'pt_BR',
						language_url: raiz+'includes/js/tinyMce5/langs/pt_BR.js',
						font_formats: fonts_extra+'Verdana=Verdana;Arial=arial,helvetica,sans-serif;',
						init_instance_callback: 'b2make_init_callback',
						content_css: variaveis_js.site_raiz+'design/?css=sim&opcao=tinymce&v=' + new Date().getTime(),
						image_advtab: true,
						branding: false,
						relative_urls : false,
						remove_script_host : false,
						resize: (!b2make.plataforma_nao_design ? false : "both"),
						style_formats_merge: true,
						convert_urls : true
					});
				} else {
					tinymce.init({
						menubar: false,
						selector: (p.selector ? p.selector : 'textarea.tinymce'),
						toolbar: 'undo redo code | styleselect | bold italic underline | link image responsivefilemanager | alignleft aligncenter alignright alignjustify resizeEditor | fontselect fontsizeselect googleFontsSelector',
						plugins: "googleFontsSelector responsivefilemanager image imagetools link code",
						directionality: 'pt_BR',
						language_url: raiz+'includes/js/tinyMce/pt_BR.js',
						font_formats: fonts_extra+'Verdana=Verdana;Arial=arial,helvetica,sans-serif;',
						init_instance_callback: 'b2make_init_callback',
						content_css: variaveis_js.site_raiz+'design/?css=sim&opcao=tinymce&v=' + new Date().getTime(),
						filemanager_title:"Responsive Filemanager",
						image_advtab: true,
						filemanager_crossdomain: true,
						external_filemanager_path: variaveis_js.site_raiz+"includes/js/tinymce-plugins/filemanager/",
						external_plugins: { "filemanager" : raiz+"includes/js/tinymce-plugins/filemanager/plugin.min.js?v=1" , "responsivefilemanager" : raiz+"includes/js/tinymce-plugins/responsivefilemanager/plugin.min.js?v=1" , "googleFontsSelector" : raiz+"includes/js/tinymce-plugins/google-fonts-selector.js"},
						branding: false,
						relative_urls : false,
						remove_script_host : false,
						resize: (!b2make.plataforma_nao_design ? false : "both"),
						style_formats_merge: true,
						convert_urls : true
					});
				}
			} else {
				tinymce.init({
					menubar: false,
					selector: (p.selector ? p.selector : 'textarea.tinymce'),
					toolbar: 'undo redo code | styleselect | bold italic underline | link image responsivefilemanager | alignleft aligncenter alignright alignjustify resizeEditor | fontselect fontsizeselect',
					plugins: "googleFontsSelector responsivefilemanager image imagetools link code",
					directionality: 'pt_BR',
					language_url: raiz+'includes/js/tinyMce/pt_BR.js',
					font_formats: fonts_extra+'Verdana=Verdana;Arial=arial,helvetica,sans-serif;',
					init_instance_callback: 'b2make_init_callback_2',
					content_css: variaveis_js.site_raiz+'design/?css=sim&opcao=tinymce&v=' + new Date().getTime(),
					filemanager_title:"Responsive Filemanager",
					image_advtab: true,
					filemanager_crossdomain: true,
					external_filemanager_path: variaveis_js.site_raiz+"includes/js/tinymce-plugins/filemanager/",
					external_plugins: { "filemanager" : raiz+"includes/js/tinymce-plugins/filemanager/plugin.min.js?v=1" , "responsivefilemanager" : raiz+"includes/js/tinymce-plugins/responsivefilemanager/plugin.min.js?v=1" , "googleFontsSelector" : raiz+"includes/js/tinymce-plugins/google-fonts-selector.js"},
					branding: false,
					relative_urls : false,
					remove_script_host : false,
					resize: false,
					style_formats_merge: true,
					convert_urls : true
				});
			}
		}
	}
};

$(document).ready(function(){
	raiz = variaveis_js.site_raiz;
	
	alerta = $("#alerta");
	alerta_php = $("#alerta_php");
	popup = $("#popup");
	
	var listener = $('<div id="b2make-admin-listener"></div>');
	listener.hide();
	listener.appendTo('body');
	
	$.b2make_tinymce_start(false);
	
	$('#videos_youtube').bind('keyup change input propertychange',function(e){
		var change = false;
		
		switch(e.type){
			case 'input':
				change = true;
			break;
		}
		
		switch(e.which){
			case 86:
				change = true;
			break;
		}
		
		if(change){
			var dados = this.value;
			var str_aux;
			var mudou = false;
			
			str_aux = dados;
			
			if(str_aux.search(/http:\/\/www\.youtube\.com\/watch\?v=/gi) >= 0){str_aux = str_aux.replace(/http:\/\/www\.youtube\.com\/watch\?v=/gi,''); mudou = true;}
			if(str_aux.search(/www\.youtube\.com\/watch\?v=/gi) >= 0){str_aux = str_aux.replace(/www\.youtube\.com\/watch\?v=/gi,''); mudou = true;}
			if(str_aux.search(/http:\/\/youtu\.be\//gi) >= 0){str_aux = str_aux.replace(/http:\/\/youtu\.be\//gi,''); mudou = true;}
			if(str_aux.search(/&.*/gi) >= 0){str_aux = str_aux.replace(/&.*/gi,''); mudou = true;}
			
			if(mudou)
				this.value = str_aux + ',';
		}
	});
	
	if(!variaveis_js.jquery_ui_custom){
		alerta.dialog({
			autoOpen: false,
			modal: true,
			title: 'Alerta',
			buttons: { "Ok": function() { $(this).dialog("close"); }},
			close: function(){
				$('#b2make-admin-listener').trigger('dialog-ui-close');
			}
		});
		
		alerta_php.dialog({
			autoOpen: (alerta_php.html()?true:false),
			modal: true,
			title: 'Alerta',
			buttons: { "Ok": function() { $(this).dialog("close"); }}
		});
		
		popup.dialog({
			autoOpen: false,
			modal: true,
			title: '',
			width: 1120,
			height: 600,
			buttons: { "Ok": function() { $(this).dialog("close"); }}
		});
	}
	
	$('#ajax_lendo').center();
	$('#ajax_erro').center();
	
	if(!variaveis_js.jquery_ui_custom)$('a,img,input,textarea,div,label,td,tr,span').tooltip({
		track: true,
		delay: 450,
		showURL: false,
		showBody: " - ",
		fade: 250,
		fixPNG: true
	});

	$(".link_hover").hover(
		function(){
			$('body').css('cursor', 'pointer');
		},
		function(){
			$('body').css('cursor', 'default'); 
		}
	);
	
	$(".tabela_lista tr").hover(
		function(){
			$(this).find('td.lista_cel').css('background-color', 'rgba(50,140,230,0.08)');
			$(this).find('td.lista_cel').find('.in-menu-opcoes-3-pontos').css('background-color', 'rgb(30,143,255)');
			//$(this).find('td.lista_header').css('background-color', '#666666');
			$(this).find('td.nao_mudar_cor').css('background-color', '#FFFFFF');
		},
		function(){
			if(variaveis_js.dark_mode){
				$(this).find('td.lista_cel').css('background-color', '#40434A');
			} else {
				$(this).find('td.lista_cel').css('background-color', '#FFFFFF');
			}
			$(this).find('td.lista_cel').find('.in-menu-opcoes-3-pontos').css('background-color', '#9D9D9D');
			//$(this).find('td.lista_header').css('background-color', '#86C525');
		}
	);
	
	$(".div_lista_tr").hover(
		function(){
			$(this).find('.div_lista_cel').css('background-color', 'rgba(50,140,230,0.08)');
			$(this).find('.div_lista_cel').find('.in-menu-opcoes-3-pontos').css('background-color', 'rgb(30,143,255)');
			$(this).find('.nao_mudar_cor').css('background-color', '#FFFFFF');
		},
		function(){
			if(variaveis_js.dark_mode){
				$(this).find('.div_lista_cel').css('background-color', '#40434A');
			} else {
				$(this).find('.div_lista_cel').css('background-color', '#FFFFFF');
			}
			$(this).find('.div_lista_cel').find('.in-menu-opcoes-3-pontos').css('background-color', '#9D9D9D');
		}
	);
	
	$("div.lista_cel").hover(
		function(){
			$(this).css('background-color', 'rgba(50,240,230,0.08)');
		},
		function(){
			$(this).css('background-color', '#FFFFFF');
		}
	);
	
	$(".textarea_noenter").keypress(function(event) {
		if(event.which == 13) {
			event.preventDefault();
			return false;
		}
	});
	
	$(".interface_ordenar").hover(
		function(){
			$(this).css('color', '#328CE6');
		},
		function(){
			if($(this).hasClass('in_ordenar_down') || $(this).hasClass('in_ordenar_up')){
				$(this).css('color', '#328CE6');
			} else {
				if(variaveis_js.dark_mode){
					$(this).css('color', 'rgba(255,255,255,0.87)');
				} else {
					$(this).css('color', 'rgba(0,0,0,0.38)');
				}
			}
		}
	);
	
	$(".interface_ordenar").click(function(){
		var id = this.id;
		window.open("?interface_ordenar="+id,"_self");
	});
	
	$('.div_lista_tr').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		if(typeof $(this).attr('data-id') !== typeof undefined && $(this).attr('data-id') !== false) {
			var id = $(this).attr('data-id');
			
			var url = variaveis_js.gestor_opcao_editar_url;
			
			if(!url){
				var opcao = (variaveis_js.gestor_opcao_editar ? variaveis_js.gestor_opcao_editar : 'editar');
				window.open('.?opcao='+opcao+'&id='+id,'_self');
			} else {
				window.open(url+id,'_self');
			}
		}
	});
	
	//tabela_lista
	
	if($('#input_ordem_salvar').length > 0){
		function input_salvar_posicao(){
			var pos = parseInt($('#input_ordem_salvar').attr('data-posicao'));
			var pos2 = $('table.tabela_lista tr td:nth-child('+pos+')').position();
			var pos3 = $('table.tabela_lista').position() + $('table.tabela_lista').height();
			
			if(pos2)$('#input_ordem_salvar').css('left',pos2.left);
			if(pos3)$('#input_ordem_salvar').css('top',pos3.top);
		}
		
		input_salvar_posicao();
		
		$(window).on('resize', function(){
			input_salvar_posicao();
		});
	}
	
	function layout_timer(){
		if($("#lay_timer").length){
			var date = new Date();
			
			var dia = date.getDate();
			var mes = date.getMonth()+1;
			var ano = date.getFullYear();
			
			var seg = date.getSeconds();
			var min = date.getMinutes();
			var hor = date.getHours();
			
			if(dia < 10) dia = new String('0'+dia);
			if(mes < 10) mes = new String('0'+mes);
			if(seg < 10) seg = new String('0'+seg);
			if(min < 10) min = new String('0'+min);
			if(hor < 10) hor = new String('0'+hor);
			
			var timer = dia+'/'+mes+'/'+ano+' - '+hor+':'+min+':'+seg;
			
			$("#lay_timer").html(timer);
		}
	
		setTimeout(layout_timer, 1000);
	}
	
	layout_timer();
	
	//we will be using this to cache the responses from the server
	var ajaxCache = {};
	
	//activate autocomplete on boxes that have the autocomplete class
	
	if(!variaveis_js.jquery_ui_custom)$("input.auto_complete").autocomplete({
		source: function(request, response) {
			//what are we searching for
			var query_id = $(this).attr('element').attr('id');
			//the cacheterm that we use to save it in the cache
			var cachedTerm = (request.term + '' + query_id) . toLowerCase();
			//if the data is in the cache and the data is not too long, use it
			
			$.ajax({
				url: url_name(),
				dataType: "json",
				data: {
					ajax: 1,
					query_id: query_id,
					query: request.term
				},
				success: function(data) {
					if(data){
						//cache the data for later
						ajaxCache[cachedTerm] = data;
						//map the data into a response that will be understood by the autocomplete widget
						response($.map(data, function(item) {
							return {
								label: item.value,
								value: item.value,
								id: item.id
							}
						}));
					}
				}
			});
		},
		//start looking at 3 characters because mysql's limit is 4
		minLength: 1,
		//when you have selected something
		select: function(event, ui) {
			//close the drop down
			
			$('#nome_id').val(ui.item.id);
			this.close;
		},
		//show the drop down
		open: function() {
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
		},
		//close the drop down
		close: function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
			$(this).parent().submit();
		}
	});
	
	/*************************** B2Make ******************************/
	
	$(window).resize(function (){
		$.b2make_console_formulario({resize:true});
	});
	
	$(window).keydown(function(event){
		if(event.keyCode == 13){
			if(!b2makeAdmin.stop_enter_preventDefaults){
				event.preventDefault();
				return false;
			}
		}
	});

	$.loading_open = function(){
		if(!b2makeAdmin.loading_conteiner){
			b2makeAdmin.loading_conteiner = $('<div id="b2make-loading-conteiner"><div id="b2make-loading-texto">'+b2makeAdmin.msgs.loading+'</div></div>');
			b2makeAdmin.loading_conteiner.appendTo('body');
			loading_position();
		}
		
		b2makeAdmin.loading_conteiner.fadeIn(b2makeAdmin.loading.animation);
	}
	
	$.loading_close = function(){
		if(b2makeAdmin.loading_conteiner){
			b2makeAdmin.loading_conteiner.fadeOut(b2makeAdmin.loading.animation);
		}
	}
	
	function loading_position(){
		$('#b2make-loading-texto').css({top:$(window).height()/2 - $('#b2make-loading-texto').height()/2});	
		$('#b2make-loading-texto').css({left:$(window).width()/2 - $('#b2make-loading-texto').width()/2});	
	}
	
	function loading(){
		b2makeAdmin.loading = {};
		
		b2makeAdmin.loading.animation = 150;
		
		if(!b2makeAdmin.msgs.loading)b2makeAdmin.msgs.loading = 'Carregando';
	}
	
	loading();
	
	$('.b2make-check-box').each(function(){
		var campo = $(this).attr('data-request-field');
		var num = parseInt($(this).attr('data-checked-num'));
		var cont = 1;
		var val = '';
		
		if($(this).find('div').length > 1){
			$(this).find('div').each(function(){
				if(num == cont){
					$(this).attr('data-checked','checked');
					val = $(this).attr('data-val');
					return false;
				}
				
				cont++;
			});
		} else {
			$(this).find('div').attr('data-only-one','true');
			
			if(num){
				val = $(this).find('div').attr('data-val');
			}
		}
		
		var input = $('<input type="hidden" value="'+val+'" name="'+campo+'" id="'+campo+'">');
		$(this).after(input);
	});
	
	$('.b2make-check-box div').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		var disabled = $(this).parent().attr('data-disabled');
		
		if(disabled) return false;
		
		var campo = $(this).parent().attr('data-request-field');
		var callback = $(this).parent().attr('data-callback');
		
		if($(this).parent().find('div').length > 1){
			$(this).parent().find('div').each(function(){
				$(this).removeAttr('data-checked');
			});
			
			$(this).attr('data-checked','checked');
			var val = $(this).attr('data-val');
			
			$('#'+campo).val(val);
		} else {
			if($(this).attr('data-checked')){
				$(this).removeAttr('data-checked');
				$('#'+campo).val('');
			} else {
				$(this).attr('data-checked','checked');
				var val = $(this).attr('data-val');
				
				$('#'+campo).val(val);
			}
		}
		
		if(callback){
			$(callback).trigger('b2make-check-box-clicked');
		}
	});
});