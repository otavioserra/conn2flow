var _plugin_id = 'contents';
if(!b2make.plugin) b2make.plugin = {};
b2make.plugin[_plugin_id] = {};

function formatMoney(n){
	n = parseFloat(n);
var c = isNaN(c = Math.abs(c)) ? 2 : c, 
    d = d == undefined ? "," : d, 
    t = t == undefined ? "." : t, 
    s = n < 0 ? "-" : "", 
    i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))), 
    j = (j = i.length) > 3 ? j % 3 : 0;
   return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}

function contents_conteudo_tipo(){
	var obj = b2make.conteiner_child_obj;
	
	$('#b2make-contents-lista-cont').hide();
	$('#b2make-contents-lista-conteudo-tipo-cont').hide();
	$('#b2make-contents-options-cont').hide();
	
	switch($(obj).myAttr('data-conteudo-tipo')){
		case 'todos-posts': $('#b2make-contents-options-cont').show(); break;
		case 'escolha-pontual': 
			$('#b2make-contents-lista-cont').show();
			$('#b2make-contents-options-cont').show();
		break;
		case 'conteudo-tipo': 
			$('#b2make-contents-lista-conteudo-tipo-cont').show();
			$('#b2make-contents-options-cont').show();
		break;
	}
}

function contents_layout_tipo(){
	var obj = b2make.conteiner_child_obj;
	
	$('#b2make-wo-contents-tamanho-cont-lbl').show();
	$('#b2make-wo-contents-tamanho-cont').show();
	$('#b2make-wo-contents-tamanho-cont-2-lbl').show();
	$('#b2make-wo-contents-tamanho-cont-2').show();
	
	$('#b2make-wo-contents-margem-seta-lbl').show();
	$('#b2make-wo-contents-margem-seta').show();
	$('#b2make-wo-contents-tamanho-seta-lbl').show();
	$('#b2make-wo-contents-tamanho-seta').show();
	
	$('#b2make-wo-contents-seta-cor-lbl').show();
	$('#b2make-wo-contents-seta-cor-val').show();
	$('#b2make-wo-contents-textos-cor-lbl').show();
	$('#b2make-wo-contents-textos-cor-val').show();
	
	$('#b2make-wo-contents-linhas-descricao-lbl').hide();
	$('#b2make-wo-contents-linhas-descricao').hide();
	
	$('#b2make-wo-contents-altura-textos-cont-lbl').css('top','48px');
	$('#b2make-wo-contents-altura-textos-cont').css('top','48px');
	
	if($(obj).myAttr('data-layout-tipo') == 'padrao'){
		$('#b2make-wo-contents-acao-click').show();
		$('#b2make-wo-contents-acao-click-lbl').show();
		$('#b2make-wo-contents-altura-imagem').show();
		$('#b2make-wo-contents-altura-imagem-lbl').show();
		$('#b2make-wo-contents-botao-text-lbl').show();
		$('#b2make-wo-contents-botao-text-cor-val').show();
		$('#b2make-wo-contents-botao-text-cont').show();
		$('#b2make-wo-contents-tags-lbl').hide();
		$('#b2make-wo-contents-tags-text-cont').hide();
		$('#b2make-wo-contents-menu-tags-btn').hide();
		$('#b2make-wo-contents-menu-tags-lbl').hide();
		$('#b2make-contents-botao-texto').show();
		$('#b2make-contents-botao-texto-lbl').show();
		
		$('#b2make-wo-contents-botao-cor-lbl').show();
		$('#b2make-wo-contents-botao-cor-val').show();
		
		$('#b2make-wo-contents-caixa-cor-lbl').show();
		$('#b2make-wo-contents-caixa-cor-val').show();
		
		$('#b2make-wo-contents-altura-textos-cont-lbl').hide();
		$('#b2make-wo-contents-altura-textos-cont').hide();
		
		$('#b2make-wo-contents-menu-cor-lbl').hide();
		$('#b2make-wo-contents-menu-cor-val').hide();
		$('#b2make-wo-contents-menu-largura-cont-lbl').hide();
		$('#b2make-wo-contents-menu-largura-cont').hide();
		$('#b2make-wo-contents-menu-altura-cont-lbl').hide();
		$('#b2make-wo-contents-menu-altura-cont').hide();
		$('#b2make-wo-contents-menu-margem-cont-lbl').hide();
		$('#b2make-wo-contents-menu-margem-cont').hide();
		
		$('#b2make-wo-contents-menu-lbl').hide();
		$('#b2make-wo-contents-menu-text-cor-val').hide();
		$('#b2make-wo-contents-menu-text-cont').hide();
		
		$('#b2make-wo-contents-menu-botao-lbl').hide();
		$('#b2make-wo-contents-menu-botao-text-cor-val').hide();
		$('#b2make-wo-contents-menu-botao-text-cont').hide();
		
		$('#b2make-wo-contents-menu-botao-cor-lbl').hide();
		$('#b2make-wo-contents-menu-botao-cor-val').hide();
		$('#b2make-wo-contents-menu-botao-largura-cont-lbl').hide();
		$('#b2make-wo-contents-menu-botao-largura-cont').hide();
		$('#b2make-wo-contents-menu-botao-altura-cont-lbl').hide();
		$('#b2make-wo-contents-menu-botao-altura-cont').hide();
		$('#b2make-wo-contents-menu-botao-margem-cont-lbl').hide();
		$('#b2make-wo-contents-menu-botao-margem-cont').hide();
	} else {
		$('#b2make-wo-contents-acao-click').hide();
		$('#b2make-wo-contents-acao-click-lbl').hide();
		$('#b2make-wo-contents-altura-imagem').hide();
		$('#b2make-wo-contents-altura-imagem-lbl').hide();
		$('#b2make-wo-contents-botao-text-lbl').hide();
		$('#b2make-wo-contents-botao-text-cor-val').hide();
		$('#b2make-wo-contents-botao-text-cont').hide();
		$('#b2make-wo-contents-tags-lbl').show();
		$('#b2make-wo-contents-tags-text-cont').show();
		
		$('#b2make-wo-contents-altura-textos-cont-lbl').show();
		$('#b2make-wo-contents-altura-textos-cont').show();
		
		$('#b2make-wo-contents-botao-cor-lbl').show();
		$('#b2make-wo-contents-botao-cor-val').show();
		
		if($(obj).myAttr('data-layout-tipo') != 'lista-texto'){
			$('#b2make-wo-contents-linhas-descricao-lbl').show();
			$('#b2make-wo-contents-linhas-descricao').show();
		}
		
		$('#b2make-wo-contents-caixa-cor-lbl').hide();
		$('#b2make-wo-contents-caixa-cor-val').hide();
		
		if($(obj).myAttr('data-layout-tipo') == 'menu'){
			$('#b2make-wo-contents-menu-tags-btn').show();
			$('#b2make-wo-contents-menu-tags-lbl').show();
			$('#b2make-contents-botao-texto').show();
			$('#b2make-contents-botao-texto-lbl').show();
			
			$('#b2make-wo-contents-menu-cor-lbl').show();
			$('#b2make-wo-contents-menu-cor-val').show();
			$('#b2make-wo-contents-menu-largura-cont-lbl').show();
			$('#b2make-wo-contents-menu-largura-cont').show();
			$('#b2make-wo-contents-menu-altura-cont-lbl').show();
			$('#b2make-wo-contents-menu-altura-cont').show();
			$('#b2make-wo-contents-menu-margem-cont-lbl').show();
			$('#b2make-wo-contents-menu-margem-cont').show();
			
			$('#b2make-wo-contents-menu-lbl').show();
			$('#b2make-wo-contents-menu-text-cor-val').show();
			$('#b2make-wo-contents-menu-text-cont').show();
			
			$('#b2make-wo-contents-menu-botao-lbl').show();
			$('#b2make-wo-contents-menu-botao-text-cor-val').show();
			$('#b2make-wo-contents-menu-botao-text-cont').show();
			
			$('#b2make-wo-contents-menu-botao-cor-lbl').show();
			$('#b2make-wo-contents-menu-botao-cor-val').show();
			$('#b2make-wo-contents-menu-botao-largura-cont-lbl').show();
			$('#b2make-wo-contents-menu-botao-largura-cont').show();
			$('#b2make-wo-contents-menu-botao-altura-cont-lbl').show();
			$('#b2make-wo-contents-menu-botao-altura-cont').show();
			$('#b2make-wo-contents-menu-botao-margem-cont-lbl').show();
			$('#b2make-wo-contents-menu-botao-margem-cont').show();
		} else {
			if($(obj).myAttr('data-layout-tipo') == 'mosaico'){
				$('#b2make-wo-contents-tags-lbl').hide();
				$('#b2make-wo-contents-tags-text-cont').hide();
				
				$('#b2make-wo-contents-tamanho-cont-lbl').hide();
				$('#b2make-wo-contents-tamanho-cont').hide();
				$('#b2make-wo-contents-tamanho-cont-2-lbl').hide();
				$('#b2make-wo-contents-tamanho-cont-2').hide();
				
				$('#b2make-wo-contents-margem-seta-lbl').hide();
				$('#b2make-wo-contents-margem-seta').hide();
				$('#b2make-wo-contents-tamanho-seta-lbl').hide();
				$('#b2make-wo-contents-tamanho-seta').hide();
				
				$('#b2make-wo-contents-seta-cor-lbl').hide();
				$('#b2make-wo-contents-seta-cor-val').hide();
				$('#b2make-wo-contents-textos-cor-lbl').hide();
				$('#b2make-wo-contents-textos-cor-val').hide();
				
				$('#b2make-wo-contents-altura-textos-cont-lbl').css('top','0px');
				$('#b2make-wo-contents-altura-textos-cont').css('top','0px');
				
			}
			
			$('#b2make-wo-contents-botao-cor-lbl').hide();
			$('#b2make-wo-contents-botao-cor-val').hide();
			
			$('#b2make-wo-contents-menu-tags-btn').hide();
			$('#b2make-wo-contents-menu-tags-lbl').hide();
			$('#b2make-contents-botao-texto').hide();
			$('#b2make-contents-botao-texto-lbl').hide();
			
			$('#b2make-wo-contents-menu-cor-lbl').hide();
			$('#b2make-wo-contents-menu-cor-val').hide();
			$('#b2make-wo-contents-menu-largura-cont-lbl').hide();
			$('#b2make-wo-contents-menu-largura-cont').hide();
			$('#b2make-wo-contents-menu-altura-cont-lbl').hide();
			$('#b2make-wo-contents-menu-altura-cont').hide();
			$('#b2make-wo-contents-menu-margem-cont-lbl').hide();
			$('#b2make-wo-contents-menu-margem-cont').hide();
			
			$('#b2make-wo-contents-menu-lbl').hide();
			$('#b2make-wo-contents-menu-text-cor-val').hide();
			$('#b2make-wo-contents-menu-text-cont').hide();
			
			$('#b2make-wo-contents-menu-botao-lbl').hide();
			$('#b2make-wo-contents-menu-botao-text-cor-val').hide();
			$('#b2make-wo-contents-menu-botao-text-cont').hide();
			
			$('#b2make-wo-contents-menu-botao-cor-lbl').hide();
			$('#b2make-wo-contents-menu-botao-cor-val').hide();
			$('#b2make-wo-contents-menu-botao-largura-cont-lbl').hide();
			$('#b2make-wo-contents-menu-botao-largura-cont').hide();
			$('#b2make-wo-contents-menu-botao-altura-cont-lbl').hide();
			$('#b2make-wo-contents-menu-botao-altura-cont').hide();
			$('#b2make-wo-contents-menu-botao-margem-cont-lbl').hide();
			$('#b2make-wo-contents-menu-botao-margem-cont').hide();
		
		}
	}
}

function contents_widget_setinha_altura(p){
	if(!p)p={};
	
	var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
	
	var next = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next');
	var previous = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous');
	var tamanho = ($(obj).myAttr('data-tamanho-seta') ? $(obj).myAttr('data-tamanho-seta') : 15);
	
	var height = $(obj).find('.b2make-widget-out').find('.b2make-contents').height();
	var top = Math.floor(parseInt(height)/2) - Math.floor(parseInt(tamanho)/2);
	
	next.css('top',top+'px');
	previous.css('top',top+'px');
}

function contents_widget_setinha_update(p){
	if(!p)p={};
	
	var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
	
	var next = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next');
	var previous = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous');
	var tamanho = ($(obj).myAttr('data-tamanho-seta') ? $(obj).myAttr('data-tamanho-seta') : 15);
	
	next.css('width',tamanho+'px');
	next.css('height',tamanho+'px');
	next.css('line-height',tamanho+'px');
	next.css('font-size',tamanho+'px');
	
	previous.css('width',tamanho+'px');
	previous.css('height',tamanho+'px');
	previous.css('line-height',tamanho+'px');
	previous.css('font-size',tamanho+'px');
	
	contents_widget_setinha_altura(p);
}

function contents_widget_html(p){
	if(!p)p={};
	
	var content_cont;
	var mais_opcoes = true;
	
	switch(p.layout_tipo){
		case 'padrao':
			content_cont = $('<div class="b2make-content-cont" data-id="'+p.content.id+'" data-href="'+p.content.url+'"></div>');
			
			var imagem = $('<div class="b2make-content-imagem" style="background-image:url('+(p.content.url_imagem_2 ? p.content.url_imagem_2 : '//platform.b2make.com/site/images/b2make-album-sem-imagem.png')+');"></div>');
			var name = $('<div class="b2make-content-name">'+p.content.nome+'</div>');
			var texto = $('<div class="b2make-content-texto">'+p.content.texto+'</div>');
			var acessar = $('<a class="b2make-content-acessar" href="'+p.content.url+'">'+p.botao_texto+'</a>');
			
			imagem.appendTo(content_cont);
			name.appendTo(content_cont);
			texto.appendTo(content_cont);
			acessar.appendTo(content_cont);
			
			if(p.altura_img){
				imagem.height(p.altura_img);
			}
		break;
		case 'imagem':
			content_cont = $('<div class="b2make-content-cont-2" data-id="'+p.content.id+'" data-href="'+p.content.url+'"></div>');
			
			var imagem = $('<div class="b2make-content-imagem-2" style="background-image:url('+(p.content.url_imagem_2 ? p.content.url_imagem_2 : '//platform.b2make.com/site/images/b2make-album-sem-imagem.png')+');"></div>');
			var name = $('<div class="b2make-content-name-2">'+p.content.nome+'</div>');
			var texto = $('<div class="b2make-content-texto-2"'+(p.linhas_descricao ? ' style="max-height:'+(p.linhas_descricao * 15)+'px;"':'')+'>'+p.content.texto+'</div>');
			var texto_cont = $('<div class="b2make-content-texto-cont"></div>');
			
			imagem.appendTo(content_cont);
			name.appendTo(texto_cont);
			texto.appendTo(texto_cont);
			
			if(p.content.tags){
				var tags = p.content.tags;
				var principal;
				
				for(var i=0;i<tags.length;i++){
					if(!principal)principal = tags[0];
					
					if(tags[i].principal){
						principal = tags[i];
						break;
					}
				}
				
				var tag_cont = $('<div class="b2make-content-tag-cont" style="border-bottom:12px solid #'+principal.cor+'; color: #'+principal.cor+';">'+principal.nome+'</div>');
				
				tag_cont.appendTo(texto_cont);
			}
			
			texto_cont.appendTo(content_cont);
			
			if(p.altura_textos){
				texto_cont.height(p.altura_textos);
			}
		break;
		case 'menu':
			content_cont = $('<div class="b2make-content-cont-2" data-id="'+p.content.id+'" data-href="'+p.content.url+'"></div>');
			
			var imagem = $('<div class="b2make-content-imagem-2" style="background-image:url('+(p.content.url_imagem_2 ? p.content.url_imagem_2 : '//platform.b2make.com/site/images/b2make-album-sem-imagem.png')+');"></div>');
			var name = $('<div class="b2make-content-name-2">'+p.content.nome+'</div>');
			var texto = $('<div class="b2make-content-texto-2"'+(p.linhas_descricao ? ' style="max-height:'+(p.linhas_descricao * 15)+'px;"':'')+'>'+p.content.texto+'</div>');
			var texto_cont = $('<div class="b2make-content-texto-cont"></div>');
			var acessar = $('<div class="b2make-content-acessar-mask"><a class="b2make-content-acessar-2" href="'+p.content.url+'">'+p.botao_texto+'</a></div>');
			
			imagem.appendTo(content_cont);
			name.appendTo(texto_cont);
			texto.appendTo(texto_cont);
			acessar.appendTo(content_cont);
			
			texto_cont.appendTo(content_cont);
			
			if(p.altura_textos){
				texto_cont.height(p.altura_textos);
			}
		break;
		case 'mosaico':
			mais_opcoes = false;
			
			if(p.num > 3) break;
			
			content_cont = $('<div class="b2make-content-cont-3" data-pos="'+(p.num+1)+'" data-id="'+p.content.id+'" data-href="'+p.content.url+'"></div>');
			
			var imagem = $('<div class="b2make-content-imagem-2" style="background-image:url('+(p.content.url_imagem_2 ? p.content.url_imagem_2 : '//platform.b2make.com/site/images/b2make-album-sem-imagem.png')+');"></div>');
			var name = $('<div class="b2make-content-name-2">'+p.content.nome+'</div>');
			var texto = $('<div class="b2make-content-texto-2"'+(p.linhas_descricao ? ' style="max-height:'+(p.linhas_descricao * 15)+'px;"':'')+'>'+p.content.texto+'</div>');
			var texto_cont = $('<div class="b2make-content-texto-cont"></div>');
			
			imagem.appendTo(content_cont);
			name.appendTo(texto_cont);
			texto.appendTo(texto_cont);
			texto_cont.appendTo(content_cont);
			
			if(p.altura_textos){
				texto_cont.height(p.altura_textos);
			}
			
		break;
		case 'lista-texto':
			mais_opcoes = false;
			
			content_cont = $('<a class="b2make-content-cont-4" href="'+p.content.url+'">'+p.content.nome+'</a>');
			
		break;
	}
	
	if(mais_opcoes){
		if(p.largura_cont){
			content_cont.width(p.largura_cont);
		}
		
		if(p.altura_cont){
			content_cont.height(p.altura_cont);
		}
		
		if(p.margem){
			content_cont.css('margin',p.margem+'px');
		}
	}
	
	return content_cont;
}

function contents_widget_update_resize(p = {}){
	var obj = p.obj;
	
	if($(obj).myAttr('data-layout-tipo') == 'mosaico'){
		var width = $(obj).width();
		var height = $(obj).height();
		var margem = ($(obj).myAttr('data-margem') ? parseInt($(obj).myAttr('data-margem')) : 10);
		
		$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').each(function(){
			var pos = $(this).myAttr('data-pos');
			var col_width = Math.ceil((width-2*margem)/3);
			var line_height = Math.ceil((height-margem)/2);
			
			switch(pos){
				case '1':
					$(this).css('width',col_width+'px');
					$(this).css('height',height+'px');
					$(this).css('top','0px');
					$(this).css('left','0px');
				break;
				case '2':
					$(this).css('width',col_width+'px');
					$(this).css('height',line_height+'px');
					$(this).css('top','0px');
					$(this).css('left',(col_width+margem)+'px');
				break;
				case '3':
					$(this).css('width',col_width+'px');
					$(this).css('height',line_height+'px');
					$(this).css('top','0px');
					$(this).css('left',(2*(col_width+margem))+'px');
				break;
				case '4':
					$(this).css('width',((2*col_width)+margem)+'px');
					$(this).css('height',line_height+'px');
					$(this).css('top',(line_height+margem)+'px');
					$(this).css('left',(col_width+margem)+'px');
				break;
				
			}
		});
	}
}

function contents_widget_update(p){
	if(!p)p={};
	
	var obj = (p.obj ? p.obj : b2make.conteiner_child_obj);
	var id_func = 'contents-html-list';
	
	$.ajax({
		type: 'POST',
		url: '.',
		data: { 
			ajax : 'sim',
			opcao : id_func
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
						if($(obj).find('.b2make-widget-out').find('.b2make-widget-loading').length == 0)$(obj).find('.b2make-widget-out').append('<div class="b2make-widget-loading"></div>');
						
						$(obj).find('.b2make-widget-out').find('.b2make-contents').html('');
						
						var contents_ids = $(obj).myAttr('data-contents-ids');
						var contents_conteudo_tipo_ids = $(obj).myAttr('data-contents-conteudo-tipo-ids');
						var found_content;
						
						$(obj).find('.b2make-widget-out').find('.b2make-contents').append('<div class="b2make-content-next">&#10095;</div>');
						$(obj).find('.b2make-widget-out').find('.b2make-contents').append('<div class="b2make-content-previous">&#10094;</div>');
						
						if(contents_ids)contents_ids = contents_ids.split(',');
						if(contents_conteudo_tipo_ids)contents_conteudo_tipo_ids = contents_conteudo_tipo_ids.split(',');
						
						if($(obj).myAttr('data-tamanho-cont')){
							var largura_cont = $(obj).myAttr('data-tamanho-cont');
						} else {
							var largura_cont = 160;
						}
						
						if($(obj).myAttr('data-tamanho-cont-2')){
							var altura_cont = $(obj).myAttr('data-tamanho-cont-2');
						} else {
							var altura_cont = 280;
						}
						
						if($(obj).myAttr('data-altura-imagem')){
							var altura_img = $(obj).myAttr('data-altura-imagem');
						} else {
							var altura_img = 160;
						}
						
						if($(obj).myAttr('data-margem')){
							var margem = $(obj).myAttr('data-margem');
						} else {
							var margem = 10;
						}
						
						if($(obj).myAttr('data-margem-seta')){
							var margem_seta = $(obj).myAttr('data-margem-seta');
						} else {
							var margem_seta = 15;
						}
						
						if($(obj).myAttr('data-botao-texto')){
							var botao_texto = $(obj).myAttr('data-botao-texto');
						} else {
							var botao_texto = b2make.msgs.contentsBotaoTexto;
						}
						
						if($(obj).myAttr('data-layout-tipo')){
							var layout_tipo = $(obj).myAttr('data-layout-tipo');
						} else {
							var layout_tipo = 'padrao';
						}
						
						if($(obj).myAttr('data-altura-textos-cont')){
							var altura_textos = $(obj).myAttr('data-altura-textos-cont');
						} else {
							var altura_textos = false;
						}
						
						if($(obj).myAttr('data-linhas-descricao')){
							var linhas_descricao = parseInt($(obj).myAttr('data-linhas-descricao'));
						} else {
							var linhas_descricao = 3;
						}
						
						switch($(obj).myAttr('data-conteudo-tipo')){
							case 'todos-posts':
								if(dados.conteudos_list){
									var ordem = ($(obj).myAttr('data-ordem') ? $(obj).myAttr('data-ordem') : 'data-desc');
									switch(ordem){
										case 'alfabetica-asc':
											dados.conteudos_list.sort(function(a, b){
												if(a.nome < b.nome) return -1;
												if(a.nome > b.nome) return 1;
												return 0;
											});
										break;
										case 'alfabetica-desc':
											dados.conteudos_list.sort(function(a, b){
												if(a.nome > b.nome) return -1;
												if(a.nome < b.nome) return 1;
												return 0;
											});
										break;
										case 'data-asc':
											dados.conteudos_list.sort(function(a, b){
												if(!a.data_criacao)a.data_criacao = 0;
												if(!b.data_criacao)b.data_criacao = 0;
												
												if(a.data_criacao < b.data_criacao) return -1;
												if(a.data_criacao > b.data_criacao) return 1;
												return 0;
											});
										break;
										case 'data-desc':
											dados.conteudos_list.sort(function(a, b){
												if(!a.data_criacao)a.data_criacao = 0;
												if(!b.data_criacao)b.data_criacao = 0;
												
												if(a.data_criacao > b.data_criacao) return -1;
												if(a.data_criacao < b.data_criacao) return 1;
												return 0;
											});
										break;
										case 'data-modificacao-asc':
											dados.conteudos_list.sort(function(a, b){
												if(!a.data_modificacao)a.data_modificacao = 0;
												if(!b.data_modificacao)b.data_modificacao = 0;
												
												if(a.data_modificacao < b.data_modificacao) return -1;
												if(a.data_modificacao > b.data_modificacao) return 1;
												return 0;
											});
										break;
										case 'data-modificacao-desc':
											dados.conteudos_list.sort(function(a, b){
												if(!a.data_modificacao)a.data_modificacao = 0;
												if(!b.data_modificacao)b.data_modificacao = 0;
												
												if(a.data_modificacao > b.data_modificacao) return -1;
												if(a.data_modificacao < b.data_modificacao) return 1;
												return 0;
											});
										break;
									}
									
									var num = 0;
									for(var i=0;i<dados.conteudos_list.length;i++){
										$(obj).find('.b2make-widget-out').find('.b2make-contents').append(contents_widget_html({
											linhas_descricao:linhas_descricao,
											num:num,
											altura_textos:altura_textos,
											layout_tipo:layout_tipo,
											margem:margem,
											botao_texto:botao_texto,
											content:dados.conteudos_list[i],
											largura_cont:largura_cont,
											altura_cont:altura_cont,
											altura_img:altura_img
										}));
										num++;
									}
								}
							break;
							case 'escolha-pontual':
								var num = 0;
								if(dados.conteudos_list){
									var ordem = ($(obj).myAttr('data-ordem') ? $(obj).myAttr('data-ordem') : 'data-desc');
									switch(ordem){
										case 'alfabetica-asc':
											dados.conteudos_list.sort(function(a, b){
												if(a.nome < b.nome) return -1;
												if(a.nome > b.nome) return 1;
												return 0;
											});
										break;
										case 'alfabetica-desc':
											dados.conteudos_list.sort(function(a, b){
												if(a.nome > b.nome) return -1;
												if(a.nome < b.nome) return 1;
												return 0;
											});
										break;
										case 'data-asc':
											dados.conteudos_list.sort(function(a, b){
												if(!a.data_criacao)a.data_criacao = 0;
												if(!b.data_criacao)b.data_criacao = 0;
												
												if(a.data_criacao < b.data_criacao) return -1;
												if(a.data_criacao > b.data_criacao) return 1;
												return 0;
											});
										break;
										case 'data-desc':
											dados.conteudos_list.sort(function(a, b){
												if(!a.data_criacao)a.data_criacao = 0;
												if(!b.data_criacao)b.data_criacao = 0;
												
												if(a.data_criacao > b.data_criacao) return -1;
												if(a.data_criacao < b.data_criacao) return 1;
												return 0;
											});
										break;
										case 'data-modificacao-asc':
											dados.conteudos_list.sort(function(a, b){
												if(!a.data_modificacao)a.data_modificacao = 0;
												if(!b.data_modificacao)b.data_modificacao = 0;
												
												if(a.data_modificacao < b.data_modificacao) return -1;
												if(a.data_modificacao > b.data_modificacao) return 1;
												return 0;
											});
										break;
										case 'data-modificacao-desc':
											dados.conteudos_list.sort(function(a, b){
												if(!a.data_modificacao)a.data_modificacao = 0;
												if(!b.data_modificacao)b.data_modificacao = 0;
												
												if(a.data_modificacao > b.data_modificacao) return -1;
												if(a.data_modificacao < b.data_modificacao) return 1;
												return 0;
											});
										break;
									}
									
									for(var i=0;i<dados.conteudos_list.length;i++){
										found_content = false;
										if(contents_ids)
										for(var j=0;j<contents_ids.length;j++){
											if(contents_ids[j] == dados.conteudos_list[i].id){
												found_content = true;
											}
										}
										
										if(!found_content)continue;
										
										$(obj).find('.b2make-widget-out').find('.b2make-contents').append(contents_widget_html({
											linhas_descricao:linhas_descricao,
											num:num,
											altura_textos:altura_textos,
											layout_tipo:layout_tipo,
											margem:margem,
											botao_texto:botao_texto,
											content:dados.conteudos_list[i],
											largura_cont:largura_cont,
											altura_cont:altura_cont,
											altura_img:altura_img
										}));
										num++;
									}
								}
							break;
							case 'conteudo-tipo':
								var num = 0;
								if(dados.conteudos_list){
									var ordem = ($(obj).myAttr('data-ordem') ? $(obj).myAttr('data-ordem') : 'data-desc');
									switch(ordem){
										case 'alfabetica-asc':
											dados.conteudos_list.sort(function(a, b){
												if(a.nome < b.nome) return -1;
												if(a.nome > b.nome) return 1;
												return 0;
											});
										break;
										case 'alfabetica-desc':
											dados.conteudos_list.sort(function(a, b){
												if(a.nome > b.nome) return -1;
												if(a.nome < b.nome) return 1;
												return 0;
											});
										break;
										case 'data-asc':
											dados.conteudos_list.sort(function(a, b){
												if(!a.data_criacao)a.data_criacao = 0;
												if(!b.data_criacao)b.data_criacao = 0;
												
												if(a.data_criacao < b.data_criacao) return -1;
												if(a.data_criacao > b.data_criacao) return 1;
												return 0;
											});
										break;
										case 'data-desc':
											dados.conteudos_list.sort(function(a, b){
												if(!a.data_criacao)a.data_criacao = 0;
												if(!b.data_criacao)b.data_criacao = 0;
												
												if(a.data_criacao > b.data_criacao) return -1;
												if(a.data_criacao < b.data_criacao) return 1;
												return 0;
											});
										break;
										case 'data-modificacao-asc':
											dados.conteudos_list.sort(function(a, b){
												if(!a.data_modificacao)a.data_modificacao = 0;
												if(!b.data_modificacao)b.data_modificacao = 0;
												
												if(a.data_modificacao < b.data_modificacao) return -1;
												if(a.data_modificacao > b.data_modificacao) return 1;
												return 0;
											});
										break;
										case 'data-modificacao-desc':
											dados.conteudos_list.sort(function(a, b){
												if(!a.data_modificacao)a.data_modificacao = 0;
												if(!b.data_modificacao)b.data_modificacao = 0;
												
												if(a.data_modificacao > b.data_modificacao) return -1;
												if(a.data_modificacao < b.data_modificacao) return 1;
												return 0;
											});
										break;
									}
									
									for(var i=0;i<dados.conteudos_list.length;i++){
										found_content = false;
										if(contents_conteudo_tipo_ids)
										for(var j=0;j<contents_conteudo_tipo_ids.length;j++){
											if(contents_conteudo_tipo_ids[j] == dados.conteudos_list[i].id_site_conteudos_tipos){
												found_content = true;
											}
										}
										
										if(!found_content)continue;
										
										$(obj).find('.b2make-widget-out').find('.b2make-contents').append(contents_widget_html({
											linhas_descricao:linhas_descricao,
											num:num,
											altura_textos:altura_textos,
											layout_tipo:layout_tipo,
											margem:margem,
											botao_texto:botao_texto,
											content:dados.conteudos_list[i],
											largura_cont:largura_cont,
											altura_cont:altura_cont,
											altura_img:altura_img
										}));
										num++;
									}
								}
							break;
						}
						
						if(layout_tipo == 'menu'){
							if($(obj).find('.b2make-widget-out').find('.b2make-menu-tags').length == 0)$(obj).find('.b2make-widget-out').prepend('<div class="b2make-menu-tags"></div>');
							
							$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').html('');
							
							var menu_tags = $(obj).find('.b2make-widget-out').find('.b2make-menu-tags');
							var contents_tags = ($(obj).myAttr('data-contents-tags-ids') ? $(obj).myAttr('data-contents-tags-ids') : '');
							var contents_tags_arr = (contents_tags ? contents_tags.split(',') : new Array());
							var tag_id = '-1';
							
							if($(obj).myAttr('data-menu-largura-cont')){
								var menu_largura_cont = $(obj).myAttr('data-menu-largura-cont');
							} else {
								var menu_largura_cont = 200;
							}
							
							if($(obj).myAttr('data-menu-altura-cont')){
								var menu_altura_cont = $(obj).myAttr('data-menu-altura-cont');
							} else {
								var menu_altura_cont = 80;
							}
							
							if($(obj).myAttr('data-menu-margem-cont')){
								var menu_margem_cont = $(obj).myAttr('data-menu-margem-cont');
							} else {
								var menu_margem_cont = 5;
							}
							
							if($(obj).myAttr('data-menu-botao-largura-cont')){
								var menu_botao_largura_cont = $(obj).myAttr('data-menu-botao-largura-cont');
							} else {
								var menu_botao_largura_cont = 100;
							}
							
							if($(obj).myAttr('data-menu-botao-altura-cont')){
								var menu_botao_altura_cont = $(obj).myAttr('data-menu-botao-altura-cont');
							} else {
								var menu_botao_altura_cont = 40;
							}
							
							if($(obj).myAttr('data-menu-botao-margem-cont')){
								var menu_botao_margem_cont = $(obj).myAttr('data-menu-botao-margem-cont');
							} else {
								var menu_botao_margem_cont = 30;
							}
							
							if(contents_tags_arr){
								var conteudos_tags_lista = b2make.conteudos_tags_lista;
								var cor_atual = '';
								
								for(var i=0;i<contents_tags_arr.length;i++){
									for(var j=0;j<conteudos_tags_lista.length;j++){
										if(conteudos_tags_lista[j].id == contents_tags_arr[i]){
											var cor = conteudos_tags_lista[j].cor;
											var nome = conteudos_tags_lista[j].nome;
											
											if(i == 0){
												tag_id = contents_tags_arr[i];
												cor_atual = cor;
												menu_tags.append($('<div class="b2make-menu-tag" style="border-bottom: solid 5px #'+cor+'; color: #'+cor+'; width: '+menu_largura_cont+'px; line-height: '+menu_altura_cont+'px; margin: '+menu_margem_cont+'px;" data-cor="'+cor+'" data-atual="sim">'+nome+'</div>'));
											} else {
												menu_tags.append($('<div class="b2make-menu-tag" style="width: '+menu_largura_cont+'px; line-height: '+menu_altura_cont+'px; margin: '+menu_margem_cont+'px;" data-cor="'+cor+'">'+nome+'</div>'));
											}
										}
									}
								}
								
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').each(function(){
									var id = $(this).myAttr('data-id');
									
									if(dados.conteudos_list)
									for(var i=0;i<dados.conteudos_list.length;i++){
										var conteudos_list = dados.conteudos_list[i];
										var found = false;
										
										if(id == conteudos_list.id){
											if(conteudos_list.tags){
												var tags = conteudos_list.tags;
												
												for(var j=0;j<tags.length;j++){
													if(tags[j].id == tag_id){
														found = true;
														break;
													}
												}
											}
											
											if(found){
												$(this).show();
												$(this).myAttr('data-show',true);
												$(this).find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2').css('background-color','#'+cor_atual);
												$(this).find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2').width(menu_botao_largura_cont);
												$(this).find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2').css('line-height',menu_botao_altura_cont+'px');
												$(this).find('.b2make-content-acessar-mask').css('bottom','-'+menu_botao_margem_cont+'px');
												$(this).css('marginBottom',(parseInt(menu_botao_margem_cont)+parseInt(menu_botao_altura_cont))+'px');
											} else {
												$(this).hide();
											}
										}
									}
								});
							}
						} else {
							$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').remove();
						}
						
						$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next').css('right',margem_seta+'px');
						$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous').css('left',margem_seta+'px');
						
						contents_widget_setinha_update({obj:obj});
						
						if($(obj).myAttr('data-widget-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-widget-color-ahex')));
						if($(obj).myAttr('data-seta-color-ahex')){
							$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-seta-color-ahex')));
							$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-seta-color-ahex')));
						}
						
						switch(layout_tipo){
							case 'padrao':
								if($(obj).myAttr('data-caixa-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex')));
								if($(obj).myAttr('data-botao-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-acessar').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-botao-color-ahex')));
								if($(obj).myAttr('data-titulo-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-name').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-text-color-ahex')));
								if($(obj).myAttr('data-texto-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-texto').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-texto-text-color-ahex')));
								if($(obj).myAttr('data-botao-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-acessar').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-botao-text-color-ahex')));
							break;
							case 'imagem':
								if($(obj).myAttr('data-caixa-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex')));
								if($(obj).myAttr('data-titulo-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-name-2').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-text-color-ahex')));
								if($(obj).myAttr('data-texto-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-texto-2').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-texto-text-color-ahex')));
								if($(obj).myAttr('data-texto-textos-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-texto-textos-color-ahex')));
							break;
							case 'menu':
								if($(obj).myAttr('data-caixa-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex')));
								if($(obj).myAttr('data-titulo-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-name-2').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-text-color-ahex')));
								if($(obj).myAttr('data-texto-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-texto-2').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-texto-text-color-ahex')));
								if($(obj).myAttr('data-texto-textos-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-texto-textos-color-ahex')));
								if($(obj).myAttr('data-menu-color-ahex')){
									$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').find('.b2make-menu-tag').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-menu-color-ahex')));
									$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').find('.b2make-menu-tag[data-atual!="sim"]').css('border-bottom','solid 5px '+$.jpicker_ahex_2_rgba($(obj).myAttr('data-menu-color-ahex')));
								}
								if($(obj).myAttr('data-menu-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').find('.b2make-menu-tag[data-atual!="sim"]').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-menu-text-color-ahex')));
								if($(obj).myAttr('data-menu-botao-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-menu-botao-color-ahex')));
								
							break;
							case 'mosaico':
								if($(obj).myAttr('data-caixa-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex')));
								if($(obj).myAttr('data-titulo-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-name-2').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-text-color-ahex')));
								if($(obj).myAttr('data-texto-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-texto-2').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-texto-text-color-ahex')));
								if($(obj).myAttr('data-texto-textos-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-texto-textos-color-ahex')));
						
								contents_widget_update_resize({obj:obj});
							break;
							case 'lista-texto':
								if($(obj).myAttr('data-caixa-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex')));
								if($(obj).myAttr('data-titulo-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-name-2').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-text-color-ahex')));
								if($(obj).myAttr('data-texto-text-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-texto-2').css('color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-texto-text-color-ahex')));
								if($(obj).myAttr('data-texto-textos-color-ahex'))$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-texto-textos-color-ahex')));
						
								contents_widget_update_resize({obj:obj});
							break;
						}
						
						var ids = new Array('titulo','texto','botao','tags','menu','menu-botao');
						var mudar_height = false;
						var target;
						
						for(var i=0;i<ids.length;i++){
							var id = ids[i];
							
							mudar_height = false;
							
							switch(layout_tipo){
								case 'padrao':
									switch(id){
										case 'titulo': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-name'); mudar_height = true; break;
										case 'texto': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-texto'); mudar_height = true; break;
										case 'botao': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-acessar'); break;
									}
								break;
								case 'imagem':
									switch(id){
										case 'titulo': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-name-2'); mudar_height = true; break;
										case 'texto': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-texto-2'); mudar_height = true; break;
										case 'tags': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-tag-cont'); mudar_height = true; break;
									}
								break;
								case 'menu':
									switch(id){
										case 'titulo': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-name-2'); mudar_height = true; break;
										case 'texto': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-texto-2'); mudar_height = true; break;
										case 'tags': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-tag-cont'); mudar_height = true; break;
										case 'menu': target = $(obj).find('.b2make-widget-out').find('.b2make-menu-tags').find('.b2make-menu-tag'); break;
										case 'menu-botao': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2'); break;
									}
								break;
								case 'mosaico':
									switch(id){
										case 'titulo': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-name-2'); mudar_height = true; break;
										case 'texto': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-texto-2'); mudar_height = true; break;
										case 'tags': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-tag-cont'); mudar_height = true; break;
									}
								break;
								case 'lista-texto':
									switch(id){
										case 'titulo': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-name-2'); mudar_height = true; break;
										case 'texto': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-texto-2'); mudar_height = true; break;
										case 'tags': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-tag-cont'); mudar_height = true; break;
									}
								break;
								
							}
							
							if($(obj).myAttr('data-'+id+'-font-family'))target.css('fontFamily',$(obj).myAttr('data-'+id+'-font-family'));
							if($(obj).myAttr('data-'+id+'-font-size')){
								target.css('fontSize',$(obj).myAttr('data-'+id+'-font-size')+'px');
								
								var height = b2make.contents.conteiner_height_lines*($(obj).myAttr('data-titulo-font-size') ? parseInt($(obj).myAttr('data-titulo-font-size')) : b2make.contents.conteiner_height_name) + b2make.contents.conteiner_height_lines*($(obj).myAttr('data-texto-font-size') ? parseInt($(obj).myAttr('data-texto-font-size')) : b2make.contents.conteiner_height_texto);
								height = height + b2make.contents.conteiner_height_default;
								
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').css('height',height+'px');
								
								if(mudar_height){
									var line_height = parseInt($(obj).myAttr('data-'+id+'-font-size')) + b2make.contents.conteiner_height_margin;
									target.css('line-height',line_height+'px');
								
									target.css('max-height',(line_height*b2make.contents.conteiner_height_lines)+'px');
								}
							}
							if($(obj).myAttr('data-'+id+'-font-align'))target.css('textAlign',$(obj).myAttr('data-'+id+'-font-align'));
							if($(obj).myAttr('data-'+id+'-font-italico'))target.css('fontStyle',($(obj).myAttr('data-'+id+'-font-italico') == 'sim' ? 'italic' : 'normal'));
							if($(obj).myAttr('data-'+id+'-font-negrito'))target.css('fontWeight',($(obj).myAttr('data-'+id+'-font-negrito') == 'sim' ? 'bold' : 'normal'));
						}
						
						if(layout_tipo == 'mosaico' || layout_tipo == 'lista-texto'){
							$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next').hide();
							$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous').hide();
						} else {
							$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next').show();
							$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous').show();
						}
						
						$(obj).find('.b2make-widget-out').find('.b2make-widget-loading').hide();
					break;
					case 'Vazio':
						// Nada a fazer
					break;
					case 'LojaBloqueada':
						b2make.contents_blocked_alerta = dados.alerta;
						b2make.contents_blocked = true;
						$.conteiner_child_close();
						$.dialogbox_open({
							msg:dados.alerta
						});
					break;
					default:
						console.log('ERROR - '+id_func+' - '+dados.status);
					
				}
			} else {
				console.log('ERROR - '+id_func+' - '+txt);
			}
		},
		error: function(txt){
			console.log('ERROR AJAX - '+id_func+' - '+txt);
		}
	});
}

function contents_menu_html(dados){
	if(!dados)dados = {};
	$('#b2make-contents-lista').prepend($('<div class="b2make-contents-lista"><div class="b2make-contents-show b2make-tooltip" title="'+b2make.msgs.contentsShow+'" data-status="'+(dados.content_show ? 'show' : 'not-show')+'" data-id="'+dados.content_id+'" data-imagem-path="'+dados.content_imagem_path+'"></div><div class="b2make-contents-nome b2make-tooltip" title="'+b2make.msgs.contentsNome+'" data-id="'+dados.content_id+'">'+dados.content_nome+'</div><div class="b2make-contents-edit b2make-tooltip" data-id="'+dados.content_id+'" title="'+b2make.msgs.contentsEdit+'"></div><div class="clear"></div></div>'));
}

function contents_start(){
	var id_func = 'contents';
	var id_plugin = 'contents';
	
	b2make.plugin[id_plugin].started = true;
	
	$.ajax({
		type: 'POST',
		url: '.',
		data: { 
			ajax : 'sim',
			opcao : id_func
		},
		beforeSend: function(){
		},
		success: function(txt){
			if(
				txt.charAt(0) == "{" || 
				txt.charAt(0) == "["
			){
				var dados = eval('(' + txt + ')');
				
				if(b2make.contents_added){
					return;
				} else {
					b2make.contents_added = true;
				}
				
				switch(dados.status){
					case 'Ok':
						var content_show,content_selected;
						var conteudos_lista = new Array();
						
						for(var i=0;i<dados.resultado.length;i++){
							
							content_show = true;
							content_selected = false;
							
							if(i==dados.resultado.length - 1){
								b2make.content_atual = dados.resultado[i].id_site_conteudos;
								b2make.content_nome = dados.resultado[i].nome;
								content_selected = true;
							}
							
							conteudos_lista.push({
								nome : dados.resultado[i].nome,
								texto : dados.resultado[i].texto,
								imagem_path : dados.resultado[i].imagem_path,
								id : dados.resultado[i].id_site_conteudos
							});
							
							$.lista_add_linha({
								lista_id : 'b2make-contents-lista',
								data_id : dados.resultado[i].id_site_conteudos,
								status : 'B',
								fields : {
									nome : dados.resultado[i].nome
								}
							});
						}
						
						b2make.conteudos_lista = conteudos_lista;
						
						var conteudos_tags_lista = new Array();
						
						if(dados.tags)
						for(var i=0;i<dados.tags.length;i++){
							if(i==dados.tags.length - 1){
								b2make.content_tags_atual = dados.tags[i].id_site_conteudos_tags;
								b2make.content_tags_nome = dados.tags[i].nome;
							}
							
							conteudos_tags_lista.push({
								nome : dados.tags[i].nome,
								cor : dados.tags[i].cor,
								id : dados.tags[i].id_site_conteudos_tags
							});
							
							$.lista_add_linha({
								lista_id : 'b2make-contents-lista-tags',
								data_id : dados.tags[i].id_site_conteudos_tags,
								status : 'B',
								fields : {
									nome : dados.tags[i].nome
								}
							});
						}
						
						b2make.conteudos_tags_lista = conteudos_tags_lista;
						
						var conteudos_tipos_lista = new Array();
						
						if(dados.conteudos_tipos)
						for(var i=0;i<dados.conteudos_tipos.length;i++){
							conteudos_tipos_lista.push({
								nome : dados.conteudos_tipos[i].nome,
								id : dados.conteudos_tipos[i].id_site_conteudos_tipos
							});
							
							$.lista_add_linha({
								lista_id : 'b2make-contents-lista-conteudo-tipo',
								data_id : dados.conteudos_tipos[i].id_site_conteudos_tipos,
								status : 'B',
								fields : {
									nome : dados.conteudos_tipos[i].nome
								}
							});
						}
						
						b2make.conteudos_tipos_lista = conteudos_tipos_lista;
						
						b2make.contents_prontos = true;
						
						if(b2make.contents_widget_added || variaveis_js.widget_id){
							b2make.contents_widget_added = false;
							$('#b2make-contents-callback').trigger('widget_added');
							$.conteiner_child_open({select:true,widget_type:'contents'});
						}
						
						$('.b2make-tooltip').tooltip({
							show: {
								effect: "fade",
								delay: 400
							}
						});
						
						$('#b2make-contents-lista').on('edit',function(event,params){
							var obj = b2make.conteiner_child_obj;
							window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'content/?opcao=editar&id='+params.id+'&site=true&widget_id='+$(obj).myAttr('id'),'_self');
						});
						
						$('#b2make-contents-lista').on('block',function(event,params){
							var obj = b2make.conteiner_child_obj;
							var id = params.id;
							var status = $('#b2make-contents-lista').find('.b2make-lista-linha[data-id="'+id+'"]').find('.b2make-lista-coluna').find('.b2make-lista-option-block').myAttr('data-status');
			
							var contents = ($(obj).myAttr('data-contents-ids') ? $(obj).myAttr('data-contents-ids') : '');
							var contents_arr = (contents ? contents.split(',') : new Array());
							var contents_saida = '';
							
							if(status == 'A'){
								contents_saida = contents + (contents ? ',':'') + id
							} else {
								if(contents_arr)
								for(var i=0;i<contents_arr.length;i++){
									if(id != contents_arr[i]){
										contents_saida = contents_saida + (contents_saida ? ',':'') + contents_arr[i]
									}
								}
							}
							
							$(obj).myAttr('data-contents-ids',contents_saida);
							contents_widget_update({obj:obj});
						
						});
						
						$('#b2make-contents-lista-tags').on('edit',function(event,params){
							var obj = b2make.conteiner_child_obj;
							window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'content/tags/?opcao=editar&id='+params.id+'&site=true&widget_id='+$(obj).myAttr('id'),'_self');
						});
						
						$('#b2make-contents-lista-tags').on('block',function(event,params){
							var obj = b2make.conteiner_child_obj;
							var id = params.id;
							var status = $('#b2make-contents-lista-tags').find('.b2make-lista-linha[data-id="'+id+'"]').find('.b2make-lista-coluna').find('.b2make-lista-option-block').myAttr('data-status');
			
							var contents_tags = ($(obj).myAttr('data-contents-tags-ids') ? $(obj).myAttr('data-contents-tags-ids') : '');
							var contents_tags_arr = (contents_tags ? contents_tags.split(',') : new Array());
							var contents_tags_saida = '';
							
							if(status == 'A'){
								contents_tags_saida = contents_tags + (contents_tags ? ',':'') + id
							} else {
								if(contents_tags_arr)
								for(var i=0;i<contents_tags_arr.length;i++){
									if(id != contents_tags_arr[i]){
										contents_tags_saida = contents_tags_saida + (contents_tags_saida ? ',':'') + contents_tags_arr[i]
									}
								}
							}
							
							$(obj).myAttr('data-contents-tags-ids',contents_tags_saida);
							contents_widget_update({obj:obj});
						});
						
						$('#b2make-contents-lista-conteudo-tipo').on('block',function(event,params){
							var obj = b2make.conteiner_child_obj;
							var id = params.id;
							var status = $('#b2make-contents-lista-conteudo-tipo').find('.b2make-lista-linha[data-id="'+id+'"]').find('.b2make-lista-coluna').find('.b2make-lista-option-block').myAttr('data-status');
			
							var contents_tipos = ($(obj).myAttr('data-contents-conteudo-tipo-ids') ? $(obj).myAttr('data-contents-conteudo-tipo-ids') : '');
							var contents_tipos_arr = (contents_tipos ? contents_tipos.split(',') : new Array());
							var contents_tipos_saida = '';
							
							if(status == 'A'){
								contents_tipos_saida = contents_tipos + (contents_tipos ? ',':'') + id
							} else {
								if(contents_tipos_arr)
								for(var i=0;i<contents_tipos_arr.length;i++){
									if(id != contents_tipos_arr[i]){
										contents_tipos_saida = contents_tipos_saida + (contents_tipos_saida ? ',':'') + contents_tipos_arr[i]
									}
								}
							}
							
							$(obj).myAttr('data-contents-conteudo-tipo-ids',contents_tipos_saida);
							contents_widget_update({obj:obj});
						});
						
						$(b2make.widget).each(function(){
							if($(this).myAttr('data-type') != 'conteiner-area'){
								switch($(this).myAttr('data-type')){
									case 'contents':
										$.widgets_read_google_font({
											tipo : 2,
											types : new Array('titulo','texto','botao','tags','menu','menu-botao'),
											obj : $(this)
										});
										
										contents_widget_update({obj:this});
										
										
									break;
								}
							}
						});
						
						if(b2make.plugin[id_plugin].widget_added){
							b2make.plugin[id_plugin].widget_added = false;
							$('#b2make-contents-callback').trigger('conteiner_child_open');
							$('#b2make-contents-callback').trigger('conteiner_child_open_finished');
							$.menu_conteiner_aba_extra_open();
							$.widget_specific_options_open();
							$.widget_sub_options_open();
						}
					break;
					case 'Vazio':
						// Nada a fazer
					break;
					case 'LojaBloqueada':
						b2make.contents_blocked_alerta = dados.alerta;
						b2make.contents_blocked = true;
						$.conteiner_child_close();
						$.dialogbox_open({
							msg:dados.alerta
						});
					break;
					default:
						console.log('ERROR - '+id_func+' - '+dados.status);
					
				}
			} else {
				console.log('ERROR - '+id_func+' - '+txt);
			}
		},
		error: function(txt){
			console.log('ERROR AJAX - '+id_func+' - '+txt);
		}
	});
	
	$('#b2make-contents-callback').on('conteiner_child_open',function(e){
		if(!b2make.contents_blocked){
			var obj = b2make.conteiner_child_obj;
			
			if($(obj).myAttr('data-type') != 'contents')return;
			
			if(!$(obj).myAttr('data-conteudo-tipo')) $(obj).myAttr('data-conteudo-tipo','escolha-pontual');
			
			var contents = ($(obj).myAttr('data-contents-ids') ? $(obj).myAttr('data-contents-ids') : '');
			var contents_arr = (contents ? contents.split(',') : new Array());
			
			$('#b2make-contents-lista').find('.b2make-lista-linha').find('.b2make-lista-coluna').find('.b2make-lista-option-block').each(function(){
				var id = $(this).myAttr('data-id');
				var found = false;
				
				if(contents_arr)
				for(var i=0;i<contents_arr.length;i++){
					if(id == contents_arr[i]){
						found = true;
					}
				}
				
				if(found){
					$(this).myAttr('data-status','A');
				} else {
					$(this).myAttr('data-status','B');
				}
			});	
			
			var contents_tags = ($(obj).myAttr('data-contents-tags-ids') ? $(obj).myAttr('data-contents-tags-ids') : '');
			var contents_tags_arr = (contents_tags ? contents_tags.split(',') : new Array());
			
			$('#b2make-contents-lista-tags').find('.b2make-lista-linha').find('.b2make-lista-coluna').find('.b2make-lista-option-block').each(function(){
				var id = $(this).myAttr('data-id');
				var found = false;
				
				if(contents_tags_arr)
				for(var i=0;i<contents_tags_arr.length;i++){
					if(id == contents_tags_arr[i]){
						found = true;
					}
				}
				
				if(found){
					$(this).myAttr('data-status','A');
				} else {
					$(this).myAttr('data-status','B');
				}
			});	
			
			var contents_tipos = ($(obj).myAttr('data-contents-conteudo-tipo-ids') ? $(obj).myAttr('data-contents-conteudo-tipo-ids') : '');
			var contents_tipos_arr = (contents_tipos ? contents_tipos.split(',') : new Array());
			
			$('#b2make-contents-lista-conteudo-tipo').find('.b2make-lista-linha').find('.b2make-lista-coluna').find('.b2make-lista-option-block').each(function(){
				var id = $(this).myAttr('data-id');
				var found = false;
				
				if(contents_tipos_arr)
				for(var i=0;i<contents_tipos_arr.length;i++){
					if(id == contents_tipos_arr[i]){
						found = true;
					}
				}
				
				if(found){
					$(this).myAttr('data-status','A');
				} else {
					$(this).myAttr('data-status','B');
				}
			});	
			
			if($(obj).myAttr('data-acao-click')){
				var option = $('#b2make-wo-contents-acao-click').find("[value='" + $(obj).myAttr('data-acao-click') + "']");
				option.prop('selected', 'selected');
			} else {
				var option = $('#b2make-wo-contents-acao-click').find(":first");
				option.prop('selected', 'selected');
			}

			contents_layout_tipo();
			contents_conteudo_tipo();
			
			var layout_tipo = '';
			
			if($(obj).myAttr('data-layout-tipo')){
				layout_tipo = $(obj).myAttr('data-layout-tipo');
				var option = $('#b2make-wo-contents-layout-tipo').find("[value='" + $(obj).myAttr('data-layout-tipo') + "']");
				option.prop('selected', 'selected');
			} else {
				var option = $('#b2make-wo-contents-layout-tipo').find(":first");
				option.prop('selected', 'selected');
			}			
			
			if($(obj).myAttr('data-conteudo-tipo')){
				var option = $('#b2make-contents-options-sel').find("[value='" + $(obj).myAttr('data-conteudo-tipo') + "']");
				option.prop('selected', 'selected');
			} else {
				var option = $('#b2make-contents-options-sel').find(":last");
				option.prop('selected', 'selected');
			}
			
			if($(obj).myAttr('data-ordem')){
				var option = $('#b2make-contents-ordem-sel').find("[value='" + $(obj).myAttr('data-ordem') + "']");
				option.prop('selected', 'selected');
			} else {
				var option = $('#b2make-contents-ordem-sel').find(":first");
				option.prop('selected', 'selected');
			}

			if($(obj).myAttr('data-widget-color-ahex')){
				$('#b2make-wo-contents-widget-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-widget-color-ahex')));
				$('#b2make-wo-contents-widget-cor-val').myAttr('data-ahex',$(obj).myAttr('data-widget-color-ahex'));
			} else {
				$('#b2make-wo-contents-widget-cor-val').css('background-color','transparent');
				$('#b2make-wo-contents-widget-cor-val').myAttr('data-ahex',false);
			}
			
			if($(obj).myAttr('data-caixa-color-ahex')){
				$('#b2make-wo-contents-caixa-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-caixa-color-ahex')));
				$('#b2make-wo-contents-caixa-cor-val').myAttr('data-ahex',$(obj).myAttr('data-caixa-color-ahex'));
			} else {
				$('#b2make-wo-contents-caixa-cor-val').css('background-color','#FFFFFF');
				$('#b2make-wo-contents-caixa-cor-val').myAttr('data-ahex','ffffffff');
			}
			
			if($(obj).myAttr('data-botao-color-ahex')){
				$('#b2make-wo-contents-botao-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-botao-color-ahex')));
				$('#b2make-wo-contents-botao-cor-val').myAttr('data-ahex',$(obj).myAttr('data-botao-color-ahex'));
			} else {
				$('#b2make-wo-contents-botao-cor-val').css('background-color','#141414');
				$('#b2make-wo-contents-botao-cor-val').myAttr('data-ahex','141414ff');
			}
			
			if($(obj).myAttr('data-titulo-text-color-ahex')){
				$('#b2make-wo-contents-titulo-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-titulo-text-color-ahex')));
				$('#b2make-wo-contents-titulo-cor-val').myAttr('data-ahex',$(obj).myAttr('data-titulo-text-color-ahex'));
			} else {
				if(layout_tipo == 'imagem' || layout_tipo == 'mosaico'){
					$('#b2make-wo-contents-titulo-cor-val').css('background-color','#ffffff');
					$('#b2make-wo-contents-titulo-cor-val').myAttr('data-ahex','ffffffff');
				} else {
					$('#b2make-wo-contents-titulo-cor-val').css('background-color','#58585B');
					$('#b2make-wo-contents-titulo-cor-val').myAttr('data-ahex','58585bff');
				}
			}
			
			if($(obj).myAttr('data-texto-text-color-ahex')){
				$('#b2make-wo-contents-texto-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-texto-text-color-ahex')));
				$('#b2make-wo-contents-texto-cor-val').myAttr('data-ahex',$(obj).myAttr('data-texto-text-color-ahex'));
			} else {
				if(layout_tipo == 'imagem' || layout_tipo == 'mosaico'){
					$('#b2make-wo-contents-texto-cor-val').css('background-color','#ffffff');
					$('#b2make-wo-contents-texto-cor-val').myAttr('data-ahex','ffffffff');
				} else {
					$('#b2make-wo-contents-texto-cor-val').css('background-color','#58585B');
					$('#b2make-wo-contents-texto-cor-val').myAttr('data-ahex','58585bff');
				}
			}
			
			if($(obj).myAttr('data-texto-textos-color-ahex')){
				$('#b2make-wo-contents-textos-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-texto-textos-color-ahex')));
				$('#b2make-wo-contents-textos-cor-val').myAttr('data-ahex',$(obj).myAttr('data-texto-textos-color-ahex'));
			} else {
				$('#b2make-wo-contents-textos-cor-val').css('background-color','rgba(0,0,0,0.4)');
				$('#b2make-wo-contents-textos-cor-val').myAttr('data-ahex','00000066');
			}
			
			if($(obj).myAttr('data-menu-color-ahex')){
				$('#b2make-wo-contents-menu-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-menu-color-ahex')));
				$('#b2make-wo-contents-menu-cor-val').myAttr('data-ahex',$(obj).myAttr('data-menu-color-ahex'));
			} else {
				$('#b2make-wo-contents-menu-cor-val').css('background-color','#e1e1e1');
				$('#b2make-wo-contents-menu-cor-val').myAttr('data-ahex','e1e1e1ff');
			}
			
			if($(obj).myAttr('data-menu-text-color-ahex')){
				$('#b2make-wo-contents-menu-text-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-menu-text-color-ahex')));
				$('#b2make-wo-contents-menu-text-cor-val').myAttr('data-ahex',$(obj).myAttr('data-menu-text-color-ahex'));
			} else {
				$('#b2make-wo-contents-menu-text-cor-val').css('background-color','#606060');
				$('#b2make-wo-contents-menu-text-cor-val').myAttr('data-ahex','606060ff');
			}
			
			if($(obj).myAttr('data-menu-botao-color-ahex')){
				$('#b2make-wo-contents-menu-botao-text-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-menu-botao-color-ahex')));
				$('#b2make-wo-contents-menu-botao-text-cor-val').myAttr('data-ahex',$(obj).myAttr('data-menu-botao-color-ahex'));
			} else {
				$('#b2make-wo-contents-menu-botao-text-cor-val').css('background-color','#ffffff');
				$('#b2make-wo-contents-menu-botao-text-cor-val').myAttr('data-ahex','ffffffff');
			}
			
			if($(obj).myAttr('data-botao-text-color-ahex')){
				$('#b2make-wo-contents-botao-text-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-botao-text-color-ahex')));
				$('#b2make-wo-contents-botao-text-cor-val').myAttr('data-ahex',$(obj).myAttr('data-botao-text-color-ahex'));
			} else {
				$('#b2make-wo-contents-botao-text-cor-val').css('background-color','#ffffff');
				$('#b2make-wo-contents-botao-text-cor-val').myAttr('data-ahex','ffffffff');
			}
			
			if($(obj).myAttr('data-seta-color-ahex')){
				$('#b2make-wo-contents-seta-cor-val').css('background-color',$.jpicker_ahex_2_rgba($(obj).myAttr('data-seta-color-ahex')));
				$('#b2make-wo-contents-seta-cor-val').myAttr('data-ahex',$(obj).myAttr('data-seta-color-ahex'));
			} else {
				$('#b2make-wo-contents-seta-cor-val').css('background-color','#000000');
				$('#b2make-wo-contents-seta-cor-val').myAttr('data-ahex','000000ff');
			}
			
			if($(obj).myAttr('data-botao-texto')){
				$('#b2make-contents-botao-texto').val($(obj).myAttr('data-botao-texto'));
			} else {
				$('#b2make-contents-botao-texto').val('Leia Mais');
			}
			
			var types = new Array('titulo','texto','botao','tags','menu','menu-botao');
			
			for(var i=0;i<types.length;i++){
				var type = types[i];
				var tamanho;
				
				switch(type){
					case 'titulo': tamanho = 18; break;
					case 'texto': tamanho = 13; break;
					case 'tags': tamanho = 13; break;
					case 'menu': tamanho = 20; break;
					case 'botao': tamanho = 11; break;
					case 'menu-botao': tamanho = 11; break;
				}
				
				if($(obj).myAttr('data-'+type+'-font-family')){
					$('#b2make-wo-contents-'+type+'-text-cont').find('.b2make-fonts-holder').css({
						'fontFamily': $(obj).myAttr('data-'+type+'-font-family')
					});
					$('#b2make-wo-contents-'+type+'-text-cont').find('.b2make-fonts-holder').html($(obj).myAttr('data-'+type+'-font-family'));
				} else {
					$('#b2make-wo-contents-'+type+'-text-cont').find('.b2make-fonts-holder').css({
						'fontFamily': 'Roboto Condensed'
					});
					$('#b2make-wo-contents-'+type+'-text-cont').find('.b2make-fonts-holder').html('Roboto Condensed');
				}
				
				if($(obj).myAttr('data-'+type+'-font-size')){
					$('#b2make-wo-contents-'+type+'-text-cont').find('.b2make-fonts-size').val($(obj).myAttr('data-'+type+'-font-size'));
				} else {
					$('#b2make-wo-contents-'+type+'-text-cont').find('.b2make-fonts-size').val(tamanho);
				}
			}
			
			if($(obj).myAttr('data-botao-texto')){
				$('#b2make-contents-botao-texto').val($(obj).myAttr('data-botao-texto'));
			} else {
				$('#b2make-contents-botao-texto').val(b2make.msgs.contentsBotaoTexto);
			}
			
			if($(obj).myAttr('data-tamanho-cont')){
				$('#b2make-wo-contents-tamanho-cont').val($(obj).myAttr('data-tamanho-cont'));
			} else {
				$('#b2make-wo-contents-tamanho-cont').val('160');
			}
			
			if($(obj).myAttr('data-tamanho-cont-2')){
				$('#b2make-wo-contents-tamanho-cont-2').val($(obj).myAttr('data-tamanho-cont-2'));
			} else {
				$('#b2make-wo-contents-tamanho-cont-2').val('280');
			}
			
			if($(obj).myAttr('data-altura-imagem')){
				$('#b2make-wo-contents-altura-imagem').val($(obj).myAttr('data-altura-imagem'));
			} else {
				$('#b2make-wo-contents-altura-imagem').val('160');
			}
			
			if($(obj).myAttr('data-margem')){
				$('#b2make-wo-contents-margem-cont').val($(obj).myAttr('data-margem'));
			} else {
				$('#b2make-wo-contents-margem-cont').val('10');
			}
			
			if($(obj).myAttr('data-tamanho-seta')){
				$('#b2make-wo-contents-tamanho-seta').val($(obj).myAttr('data-tamanho-seta'));
			} else {
				$('#b2make-wo-contents-tamanho-seta').val('15');
			}
		
			if($(obj).myAttr('data-altura-textos-cont')){
				$('#b2make-wo-contents-altura-textos-cont').val($(obj).myAttr('data-altura-textos-cont'));
			} else {
				$('#b2make-wo-contents-altura-textos-cont').val('');
			}
			
			if($(obj).myAttr('data-menu-largura-cont')){
				$('#b2make-wo-contents-menu-largura-cont').val($(obj).myAttr('data-menu-largura-cont'));
			} else {
				$('#b2make-wo-contents-menu-largura-cont').val('200');
			}
			
			if($(obj).myAttr('data-menu-altura-cont')){
				$('#b2make-wo-contents-menu-altura-cont').val($(obj).myAttr('data-menu-altura-cont'));
			} else {
				$('#b2make-wo-contents-menu-altura-cont').val('80');
			}
			
			if($(obj).myAttr('data-menu-margem-cont')){
				$('#b2make-wo-contents-menu-margem-cont').val($(obj).myAttr('data-menu-margem-cont'));
			} else {
				$('#b2make-wo-contents-menu-margem-cont').val('5');
			}
			
			if($(obj).myAttr('data-menu-botao-largura-cont')){
				$('#b2make-wo-contents-menu-botao-largura-cont').val($(obj).myAttr('data-menu-botao-largura-cont'));
			} else {
				$('#b2make-wo-contents-menu-botao-largura-cont').val('100');
			}
			
			if($(obj).myAttr('data-menu-botao-altura-cont')){
				$('#b2make-wo-contents-menu-botao-altura-cont').val($(obj).myAttr('data-menu-botao-altura-cont'));
			} else {
				$('#b2make-wo-contents-menu-botao-altura-cont').val('40');
			}
			
			if($(obj).myAttr('data-menu-botao-margem-cont')){
				$('#b2make-wo-contents-menu-botao-margem-cont').val($(obj).myAttr('data-menu-botao-margem-cont'));
			} else {
				$('#b2make-wo-contents-menu-botao-margem-cont').val('30');
			}
			
			if($(obj).myAttr('data-linhas-descricao')){
				$('#b2make-wo-contents-linhas-descricao').val($(obj).myAttr('data-linhas-descricao'));
			} else {
				$('#b2make-wo-contents-linhas-descricao').val('3');
			}
			
		}
	});
	
	$('#b2make-contents-callback').on('conteiner_child_open_finished',function(e){
		if(!b2make.contents_prontos) return false;
		if(b2make.contents_blocked){
			$.conteiner_child_close();
			$.dialogbox_open({
				msg:b2make.contents_blocked_alerta
			});
		} else {
			var obj = b2make.conteiner_child_obj;
			var ids_str = $(obj).myAttr('data-contents-ids');
			var ids = new Array();
			
			if(ids_str){
				ids = ids_str.split(',');
			}
			
			if(ids){
				$('#b2make-contents-lista').find('.b2make-lista-linha').each(function(){
					var show_cont = $(this);
					
					if($(this).hasClass('b2make-lista-cabecalho')){
						return true;
					}
					
					var id = show_cont.myAttr('data-id');
					var found = false;
					
					for(var i=0;i<ids.length;i++){
						if(ids[i] == id){
							found = true;							
							break;
						}
					}
					
					if(found){
						show_cont.myAttr('data-status','show');
						show_cont.find('.b2make-lista-option-block').myAttr('data-status','A');
					} else {
						show_cont.myAttr('data-status','not-show');
						show_cont.find('.b2make-lista-option-block').myAttr('data-status','B');
					}
				});
			}
			
			if(b2make.contents_widget_added){
				contents_widget_update({});
			}
		}
	});
	
	$('#b2make-contents-add').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		var obj = b2make.conteiner_child_obj;
		window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'content/?opcao=add&site=true&widget_id='+$(obj).myAttr('id'),'_self');
	});
	
}

function contents(){
	b2make.contents = {};
	if(!b2make.msgs.contentsEdit)b2make.msgs.contentsEdit = 'Clique para Editar o este conte&uacute;do';
	if(!b2make.msgs.contentsNome)b2make.msgs.contentsNome = 'Clique para alterar as fotos deste conte&uacute;do';
	if(!b2make.msgs.contentsShow)b2make.msgs.contentsShow = 'Clique para que mostrar/n&atilde;o mostrar este conte&uacute;do no widget Conte&uacute;dos';
	if(!b2make.msgs.contentsBotaoTexto)b2make.msgs.contentsBotaoTexto = 'Leia Mais';
	
	b2make.contents.conteiner_height_default = 220;
	b2make.contents.conteiner_height_lines = 3;
	b2make.contents.conteiner_height_margin = 2;
	b2make.contents.conteiner_height_name = 18;
	b2make.contents.conteiner_height_texto = 13;
	b2make.contents.linhas_descricao_max = 10;
	
	var id_func = 'contents';
	var id_plugin = 'contents';
	
	// Install B2make Widget Options
	
	$.ajax({
		type: 'POST',
		url: 'plugins/'+id_plugin+'/html.html',
		beforeSend: function(){
		},
		success: function(txt){
			var html = $('<div>'+txt+'</div>');
			
			var options = html.find('#b2make-widget-options-'+id_plugin).clone();
			options.appendTo('#b2make-widget-options-hide');
			var sub_options = html.find('#b2make-widget-sub-options-'+id_plugin).clone();
			sub_options.appendTo('#b2make-widget-options-hide');
			var sub_options2 = html.find('#b2make-widget-sub-options-contents-tags').clone();
			sub_options2.appendTo('#b2make-widget-options-hide');
			
			$.fonts_load({obj:'#b2make-widget-options-'+id_plugin});
			$.jpicker_load({obj:'#b2make-widget-options-'+id_plugin});
			$.lista_start($('#b2make-contents-lista').get(0));
			$.lista_start($('#b2make-contents-lista-conteudo-tipo').get(0));
			$.lista_start($('#b2make-contents-lista-tags').get(0));
			
			$.menu_conteiner_aba_load({
				id:id_plugin,
				html:html.find('#b2make-conteiner-aba-extra-'+id_plugin).clone()
			});
			$.fonts_load({obj:'.b2make-conteiner-aba-extra[data-type="'+id_plugin+'"]'});
			$.jpicker_load({obj:'.b2make-conteiner-aba-extra[data-type="'+id_plugin+'"]'});
			
			if(b2make.plugin[id_plugin].widget_added){
				$.menu_conteiner_aba_extra_open();
			}
			
			$(document.body).on('changeColor','#b2make-wo-contents-menu-botao-text-cor-val,#b2make-wo-contents-menu-text-cor-val,#b2make-wo-contents-menu-cor-val,#b2make-wo-contents-textos-cor-val,#b2make-wo-contents-seta-cor-val,#b2make-wo-contents-widget-cor-val,#b2make-wo-contents-botao-text-cor-val,#b2make-wo-contents-texto-cor-val,#b2make-wo-contents-titulo-cor-val,#b2make-wo-contents-caixa-cor-val,#b2make-wo-contents-botao-cor-val',function(e){
				var id = $(this).myAttr('id');
				var bg = $(b2make.jpicker.obj).css('background-color');
				var ahex = $(b2make.jpicker.obj).myAttr('data-ahex');
				var obj = b2make.conteiner_child_obj;
				var layout_tipo = ($(obj).myAttr('data-layout-tipo') ? $(obj).myAttr('data-layout-tipo') : 'padrao');
				
				switch(id){
					case 'b2make-wo-contents-widget-cor-val':
						$(obj).find('.b2make-widget-out').find('.b2make-contents').css('background-color',bg);
						$(obj).myAttr('data-widget-color-ahex',ahex);	
					break;
					case 'b2make-wo-contents-seta-cor-val':
						$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-next').css('color',bg);
						$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-previous').css('color',bg);
						$(obj).myAttr('data-seta-color-ahex',ahex);
					break;					
				}
				
				switch(layout_tipo){
					case 'padrao':
						switch(id){
							case 'b2make-wo-contents-caixa-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').css('background-color',bg);
								$(obj).myAttr('data-caixa-color-ahex',ahex);	
							break;
							case 'b2make-wo-contents-botao-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-acessar').css('background-color',bg);
								$(obj).myAttr('data-botao-color-ahex',ahex);
							break;
							case 'b2make-wo-contents-titulo-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-name').css('color',bg);
								$(obj).myAttr('data-titulo-text-color-ahex',ahex);
							break;
							case 'b2make-wo-contents-texto-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-texto').css('color',bg);
								$(obj).myAttr('data-texto-text-color-ahex',ahex);
							break;
							case 'b2make-wo-contents-botao-text-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-acessar').css('color',bg);
								$(obj).myAttr('data-botao-text-color-ahex',ahex);
							break;
							
						}
					break;
					case 'imagem':
						switch(id){
							case 'b2make-wo-contents-caixa-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').css('background-color',bg);
								$(obj).myAttr('data-caixa-color-ahex',ahex);	
							break;
							case 'b2make-wo-contents-titulo-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-name-2').css('color',bg);
								$(obj).myAttr('data-titulo-text-color-ahex',ahex);
							break;
							case 'b2make-wo-contents-texto-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-texto-2').css('color',bg);
								$(obj).myAttr('data-texto-text-color-ahex',ahex);
							break;
							case 'b2make-wo-contents-textos-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').css('background-color',bg);
								$(obj).myAttr('data-texto-textos-color-ahex',ahex);
							break;							
						}
					break;
					case 'menu':
						switch(id){
							case 'b2make-wo-contents-caixa-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').css('background-color',bg);
								$(obj).myAttr('data-caixa-color-ahex',ahex);	
							break;
							case 'b2make-wo-contents-titulo-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-name-2').css('color',bg);
								$(obj).myAttr('data-titulo-text-color-ahex',ahex);
							break;
							case 'b2make-wo-contents-texto-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-texto-2').css('color',bg);
								$(obj).myAttr('data-texto-text-color-ahex',ahex);
							break;
							case 'b2make-wo-contents-textos-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').css('background-color',bg);
								$(obj).myAttr('data-texto-textos-color-ahex',ahex);
							break;
							case 'b2make-wo-contents-menu-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').find('.b2make-menu-tag').css('background-color',bg);
								$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').find('.b2make-menu-tag[data-atual!="sim"]').css('border-bottom','solid 5px '+bg);
								$(obj).myAttr('data-menu-color-ahex',ahex);
							break;
							case 'b2make-wo-contents-menu-text-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-menu-tags').find('.b2make-menu-tag[data-atual!="sim"]').css('color',bg);
								$(obj).myAttr('data-menu-text-color-ahex',ahex);
							break;
							case 'b2make-wo-contents-menu-botao-text-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2').css('color',bg);
								$(obj).myAttr('data-menu-botao-color-ahex',ahex);
							break;						
						}
					break;
					case 'mosaico':
						switch(id){
							case 'b2make-wo-contents-caixa-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').css('background-color',bg);
								$(obj).myAttr('data-caixa-color-ahex',ahex);	
							break;
							case 'b2make-wo-contents-titulo-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-name-2').css('color',bg);
								$(obj).myAttr('data-titulo-text-color-ahex',ahex);
							break;
							case 'b2make-wo-contents-texto-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-texto-2').css('color',bg);
								$(obj).myAttr('data-texto-text-color-ahex',ahex);
							break;
							case 'b2make-wo-contents-textos-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').css('background-color',bg);
								$(obj).myAttr('data-texto-textos-color-ahex',ahex);
							break;							
						}
					break;
					case 'lista-texto':
						switch(id){
							case 'b2make-wo-contents-caixa-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').css('background-color',bg);
								$(obj).myAttr('data-caixa-color-ahex',ahex);	
							break;
							case 'b2make-wo-contents-titulo-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-name-2').css('color',bg);
								$(obj).myAttr('data-titulo-text-color-ahex',ahex);
							break;
							case 'b2make-wo-contents-texto-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-texto-2').css('color',bg);
								$(obj).myAttr('data-texto-text-color-ahex',ahex);
							break;
							case 'b2make-wo-contents-textos-cor-val':
								$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').css('background-color',bg);
								$(obj).myAttr('data-texto-textos-color-ahex',ahex);
							break;							
						}
					break;
					
				}
			});
			
			$(document.body).on('changeFontFamily changeFontSize changeFontAlign changeFontItalico changeFontNegrito','#b2make-wo-contents-menu-botao-text-cont,#b2make-wo-contents-menu-text-cont,#b2make-wo-contents-tags-text-cont,#b2make-wo-contents-botao-text-cont,#b2make-wo-contents-texto-text-cont,#b2make-wo-contents-titulo-text-cont',function(e){
				var obj = b2make.conteiner_child_obj;
				var target;
				var cssVar = '';
				var noSize = false;
				var nao_mudar_line_height = false;
				var id_bruto = $(this).myAttr('id');
				var mudar_height = false;
				var id = id_bruto.replace(/b2make-wo-contents-/gi,'');
				var layout_tipo = ($(obj).myAttr('data-layout-tipo') ? $(obj).myAttr('data-layout-tipo') : 'padrao');
				
				id = id.replace(/-text-cont/gi,'');
				
				switch(layout_tipo){
					case 'padrao':
						switch(id_bruto){
							case 'b2make-wo-contents-titulo-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-name'); mudar_height = true; break;
							case 'b2make-wo-contents-texto-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-texto'); mudar_height = true; break;
							case 'b2make-wo-contents-botao-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').find('.b2make-content-acessar'); break;
						}
					break;
					case 'imagem':
						switch(id_bruto){
							case 'b2make-wo-contents-titulo-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-name-2'); mudar_height = true; break;
							case 'b2make-wo-contents-texto-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-texto-2'); mudar_height = true; break;
							case 'b2make-wo-contents-tags-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-tag-cont'); mudar_height = true; break;
						}
					break;
					case 'menu':
						switch(id_bruto){
							case 'b2make-wo-contents-titulo-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-name-2'); mudar_height = true; break;
							case 'b2make-wo-contents-texto-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-texto-2'); mudar_height = true; break;
							case 'b2make-wo-contents-tags-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-texto-cont').find('.b2make-content-tag-cont'); mudar_height = true; break;
							case 'b2make-wo-contents-menu-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-menu-tags').find('.b2make-menu-tag'); nao_mudar_line_height = true; break;
							case 'b2make-wo-contents-menu-botao-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-2').find('.b2make-content-acessar-mask').find('.b2make-content-acessar-2'); nao_mudar_line_height = true; break;
						}
					break;
					case 'mosaico':
						switch(id_bruto){
							case 'b2make-wo-contents-titulo-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-name-2'); mudar_height = true; break;
							case 'b2make-wo-contents-texto-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-texto-2'); mudar_height = true; break;
							case 'b2make-wo-contents-tags-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-tag-cont'); mudar_height = true; break;
						}
					break;
					case 'lista-texto':
						switch(id_bruto){
							case 'b2make-wo-contents-titulo-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-name-2'); mudar_height = true; break;
							case 'b2make-wo-contents-texto-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-texto-2'); mudar_height = true; break;
							case 'b2make-wo-contents-tags-text-cont': target = $(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont-3').find('.b2make-content-texto-cont').find('.b2make-content-tag-cont'); mudar_height = true; break;
						}
					break;
					
				}
				
				switch(e.type){
					case 'changeFontFamily': cssVar = 'fontFamily'; target.css(cssVar,$(this).myAttr('data-font-family')); $(obj).myAttr('data-'+id+'-font-family',$(this).myAttr('data-font-family')); break;
					case 'changeFontSize': 
						cssVar = 'fontSize';  target.css(cssVar,$(this).myAttr('data-font-size')+'px'); if(!nao_mudar_line_height) target.css('line-height',$(this).myAttr('data-font-size')+'px'); $(obj).myAttr('data-'+id+'-font-size',$(this).myAttr('data-font-size')); 
						
						if(!nao_mudar_line_height){
							var height = b2make.contents.conteiner_height_lines*parseInt($('#b2make-wo-contents-texto-text-cont').find('.b2make-fonts-size').val()) + b2make.contents.conteiner_height_lines*parseInt($('#b2make-wo-contents-titulo-text-cont').find('.b2make-fonts-size').val());
							height = height + b2make.contents.conteiner_height_default;
							
							$(obj).find('.b2make-widget-out').find('.b2make-contents').find('.b2make-content-cont').css('height',height+'px');
							
							var line_height = parseInt($(this).myAttr('data-font-size')) + b2make.contents.conteiner_height_margin;
							target.css('line-height',line_height+'px');
						}
						
						if(mudar_height){
							target.css('max-height',(line_height*b2make.contents.conteiner_height_lines)+'px');
						}
					break;
					case 'changeFontAlign': cssVar = 'textAlign'; target.css(cssVar,$(this).myAttr('data-font-align'));$(obj).myAttr('data-'+id+'-font-align',$(this).myAttr('data-font-align')); break;
					case 'changeFontItalico': cssVar = 'fontStyle'; target.css(cssVar,($(this).myAttr('data-font-italico') == 'sim' ? 'italic' : 'normal')); $(obj).myAttr('data-'+id+'-font-italico',$(this).myAttr('data-font-italico')); break;
					case 'changeFontNegrito': cssVar = 'fontWeight'; target.css(cssVar,($(this).myAttr('data-font-negrito') == 'sim' ? 'bold' : 'normal')); $(obj).myAttr('data-'+id+'-font-negrito',$(this).myAttr('data-font-negrito')); break;
				}
			});
			
			$(document.body).on('change','#b2make-wo-contents-acao-click',function(){
				var obj = b2make.conteiner_child_obj;
				var value = $(this).val();
				
				$(obj).myAttr('data-acao-click',value);
			});
			
			$(document.body).on('change','#b2make-wo-contents-layout-tipo',function(){
				var obj = b2make.conteiner_child_obj;
				var value = $(this).val();
				
				$(obj).myAttr('data-layout-tipo',value);
				
				contents_layout_tipo();
				
				if($(obj).myAttr('data-layout-tipo') == 'padrao'){
					$('#b2make-wo-contents-acao-click').show();
					$('#b2make-wo-contents-acao-click-lbl').show();
				} else {
					$('#b2make-wo-contents-acao-click').hide();
					$('#b2make-wo-contents-acao-click-lbl').hide();
				}
				
				$('#b2make-contents-callback').trigger('conteiner_child_open');
				
				contents_widget_update({});
			});
			
			$(document.body).on('change','#b2make-contents-options-sel',function(){
				var obj = b2make.conteiner_child_obj;
				var value = $(this).val();
				
				$(obj).myAttr('data-conteudo-tipo',value);
				
				$('#b2make-contents-lista-cont').hide();
				$('#b2make-contents-lista-conteudo-tipo-cont').hide();
				$('#b2make-contents-options-cont').hide();
				
				switch($(obj).myAttr('data-conteudo-tipo')){
					case 'todos-posts': $('#b2make-contents-options-cont').show(); break;
					case 'escolha-pontual': 
						$('#b2make-contents-lista-cont').show();
						$('#b2make-contents-options-cont').show();
					break;
					case 'conteudo-tipo': 
						$('#b2make-contents-lista-conteudo-tipo-cont').show();
						$('#b2make-contents-options-cont').show();
					break;
				}
				
				contents_widget_update({});
			});
			
			$(document.body).on('change','#b2make-contents-ordem-sel',function(){
				var obj = b2make.conteiner_child_obj;
				var value = $(this).val();
				
				$(obj).myAttr('data-ordem',value);
				
				contents_widget_update({});
			});
			
			$(document.body).on('keyup','#b2make-contents-botao-texto',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-contents-botao-texto-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-contents-botao-texto-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-botao-texto',value);
				
				contents_widget_update({});
			});
			
			$(document.body).on('keyup','#b2make-wo-contents-tamanho-cont',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-contents-tamanho-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-contents-tamanho-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-tamanho-cont',value);
				
				contents_widget_update({});
			});
			
			$(document.body).on('keyup','#b2make-wo-contents-tamanho-cont-2',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-contents-tamanho-2-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-contents-tamanho-2-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-tamanho-cont-2',value);
				
				contents_widget_update({});
			});
			
			$(document.body).on('keyup','#b2make-wo-contents-altura-imagem',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-contents-altura-imagem-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-contents-altura-imagem-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-altura-imagem',value);
				
				contents_widget_update({});
			});
			
			$(document.body).on('keyup','#b2make-wo-contents-margem-cont',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-contents-margem-cont-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-contents-margem-cont-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-margem',value);
				
				contents_widget_update({});
			});
			
			$(document.body).on('keyup','#b2make-wo-contents-margem-seta',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-contents-margem-seta-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-contents-margem-seta-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-margem-seta',value);
				
				contents_widget_update({});
			});
			
			$(document.body).on('keyup','#b2make-wo-contents-tamanho-seta',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-contents-tamanho-seta-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-contents-tamanho-seta-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-tamanho-seta',value);
				
				contents_widget_update({});
			});
			
			$(document.body).on('keyup','#b2make-wo-contents-altura-textos-cont',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-contents-altura-textos-cont-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-contents-altura-textos-cont-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-altura-textos-cont',value);
				
				contents_widget_update({});
			});
			
			$(document.body).on('keyup','#b2make-wo-contents-menu-largura-cont',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-contents-menu-largura-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-contents-menu-largura-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-menu-largura-cont',value);
				
				contents_widget_update({});
			});
			
			$(document.body).on('keyup','#b2make-wo-contents-menu-altura-cont',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-contents-menu-altura-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-contents-menu-altura-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-menu-altura-cont',value);
				
				contents_widget_update({});
			});
			
			$(document.body).on('keyup','#b2make-wo-contents-menu-margem-cont',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-contents-menu-margem-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-contents-menu-margem-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-menu-margem-cont',value);
				
				contents_widget_update({});
			});
			
			$(document.body).on('keyup','#b2make-wo-contents-menu-botao-largura-cont',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-contents-menu-botao-largura-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-contents-menu-botao-largura-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-menu-botao-largura-cont',value);
				
				contents_widget_update({});
			});
			
			$(document.body).on('keyup','#b2make-wo-contents-menu-botao-altura-cont',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-contents-menu-botao-altura-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-contents-menu-botao-altura-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-menu-botao-altura-cont',value);
				
				contents_widget_update({});
			});
			
			$(document.body).on('keyup','#b2make-wo-contents-menu-botao-margem-cont',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-contents-menu-botao-margem-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-contents-menu-botao-margem-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				$(obj).myAttr('data-menu-botao-margem-cont',value);
				
				contents_widget_update({});
			});
			
			$(document.body).on('widgets-resize','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				if($(obj).myAttr('data-type') == 'contents'){
					contents_widget_update_resize({
						obj:obj
					});
				}
			});
			
			$(document.body).on('keyup','#b2make-wo-contents-linhas-descricao',function(e){
				var value = $(this).val();
				var id = $(this).myAttr('id');
				
				$.input_delay_to_change({
					trigger_selector:'#b2make-listener',
					trigger_event:'b2make-wo-contents-linhas-descricao-change',
					value:value
				});
			});
			
			$(document.body).on('b2make-wo-contents-linhas-descricao-change','#b2make-listener',function(e,value,p){
				if(!p) p = {};
				
				var obj = b2make.conteiner_child_obj;
				
				var val = parseInt(value);
				
				if(val > b2make.contents.linhas_descricao_max){
					val = b2make.contents.linhas_descricao_max;
					$('#b2make-wo-contents-linhas-descricao').val(val);
					value = val;
				}
				
				$(obj).myAttr('data-linhas-descricao',value);
				
				contents_widget_update({});
			});
			
			contents_start();
		},
		error: function(txt){
			console.log('ERROR AJAX - '+id_plugin+' - html - '+txt);
			console.log(txt);
		}
	});
	
	$('#b2make-listener').on('widgets-resize',function(e){
		switch(b2make.conteiner_child_type){
			case id_plugin:
				contents_widget_setinha_altura({});
			break;
		}
	});
	
	$('#b2make-contents-callback').on('callback',function(e){
		$.conteiner_child_open({select:true,widget_type:'contents'});
	});
	
	$('#b2make-contents-callback').on('widget_added',function(e){
		if(!b2make.contents_blocked && !b2make.contents_prontos){
			
		} else {
			b2make.contents_widget_added = true;
			b2make.contents_widget_added_2 = true;
		}
		
		if(!b2make.plugin[id_plugin].started){
			b2make.plugin[id_plugin].widget_added = true;			
		}
	});
	
	$('#b2make-listener').on('publish-page',function(e,type,obj){
		if(type == id_plugin){
			$(obj).find('.b2make-widget-out').find('.b2make-widget-loading').show();
		}
	});
}

contents();