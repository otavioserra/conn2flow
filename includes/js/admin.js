var sep;
var raiz;
var alerta;
var alerta_php;
var popup;
var popup_ativo = false;

var url_name = function (){
	var url_aux = location.pathname;
	var url_parts;
	
	url_parts = url_aux.split('/');
	
	if(url_parts[url_parts.length-1])
		return url_parts[url_parts.length-1];
	else
		return '.';
}

var excluir = function(url,id,opcao){
	if(id){
		if(confirm("Tem certeza que deseja excluir esse item?")){
			window.open(url+"?opcao="+opcao+"&id="+id,"_self");
		}
	}
}

$(document).ready(function(){
	raiz = variaveis_js.site_raiz;
	
	alerta = $("#alerta");
	alerta_php = $("#alerta_php");
	popup = $("#popup");
	
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
	
	alerta.dialog({
		autoOpen: false,
		modal: true,
		title: 'Alerta',
		buttons: { "Ok": function() { $(this).dialog("close"); }}
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
	
	$('#ajax_lendo').center();
	$('#ajax_erro').center();
	
	$('a,img,input,textarea,div,label,td,tr').tooltip({
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
			$(this).find('td.lista_cel').css('background-color', '#EDEEF0');
			//$(this).find('td.lista_header').css('background-color', '#666666');
			$(this).find('td.nao_mudar_cor').css('background-color', '#F7F7F8');
		},
		function(){
			$(this).find('td.lista_cel').css('background-color', '#F7F7F8');
			//$(this).find('td.lista_header').css('background-color', '#86C525');
		}
	);
	
	$("div.lista_cel").hover(
		function(){
			$(this).css('background-color', '#EDEEF0');
		},
		function(){
			$(this).css('background-color', '#F7F7F8');
		}
	);
	
	$(".textarea_noenter").keypress(function(event) {
		if(event.which == '13') {
			return false;
		}
	});
	
	$(".interface_ordenar").hover(
		function(){
			$(this).css('background-color', '#464E56');
			$(this).css('color', '#FFF');
		},
		function(){
			$(this).css('background-color', '#D7D9DD');
			$(this).css('color', '#58585B');
		}
	);
	
	$(".interface_ordenar").click(function(){
		var id = this.id;
		window.open("?interface_ordenar="+id,"_self");
	});
	
	
	//tabela_lista
	
	if($('#input_ordem_salvar').length > 0){
		function input_salvar_posicao(){
			var pos = parseInt($('#input_ordem_salvar').attr('data-posicao'));
			var pos2 = $('table.tabela_lista tr td:nth-child('+pos+')').position();
			var pos3 = $('table.tabela_lista').position() + $('table.tabela_lista').height();
			
			$('#input_ordem_salvar').css('left',pos2.left);
			$('#input_ordem_salvar').css('top',pos3.top);
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
	$("input.auto_complete").autocomplete({
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
		}
	});
	
});