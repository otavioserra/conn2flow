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

};

$.cycle_nav_start = function(id){
	var padding_right = 0;
	var padding_left = 0;
	var padding_top = 0;
	var count = 0;
	$('#'+id+' .img-container').each(function () {
		count++;
	});
	
	banner_count[id] = count;
	if(count < 2){
		$('#'+id+'-left').hide();
		$('#'+id+'-right').hide();
	} else {
		var w1 = parseInt($('#'+id).css('width'));
		var w2 = parseInt($('.nav-right').css('width'));
		var h1 = parseInt($('#'+id).css('height'));
		var h2 = parseInt($('.nav-left').css('height'));
		var m1 = h1/2 + h2/2 + padding_top;
		var m2 = w1 - w2;
		
		$('#'+id+'-left').css('margin-top','-'+m1+'px');
		$('#'+id+'-left').css('margin-left',(padding_left)+'px');
		$('#'+id+'-right').css('margin-top','-'+m1+'px');
		$('#'+id+'-right').css('margin-left',(m2+padding_right)+'px');
	}
};

$(document).ready(function(){
	// variaveis_js - variável global de dados do php
	// ================================== Banners ========================
	
});