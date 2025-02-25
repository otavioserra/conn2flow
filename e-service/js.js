
$.holders_close = function(){
	if(b2make.holder_events_visible){
		for(var i=0;i<b2make.holder_events.length;i++){
			if(b2make.holder_events[i].visible){
				$("#"+b2make.holder_events[i].target).hide();
				$.holders_update({
					visible : false,
					id : b2make.holder_events[i].id
				});
			}
		}
	}
};

$.holders_update = function(p){
	for(var i=0;i<b2make.holder_events.length;i++){
		if(b2make.holder_events[i].id == p.id){
			b2make.holder_events[i].visible = p.visible;
		}
	}
	
	var visible = false;
	
	for(i=0;i<b2make.holder_events.length;i++){
		if(b2make.holder_events[i].visible){
			visible = true;
		}
	}
	
	b2make.holder_events_visible = visible;
};

$(document).ready(function(){
	if(variaveis_js.avatar){
		var avatar_version = '';
		if(localStorage.getItem('b2make.avatar_version')){
			avatar_version = '?v='+localStorage.getItem('b2make.avatar_version');
		}
		$('#b2make-account-menu-snapshot').css('backgroundImage','url('+variaveis_js.avatar+avatar_version+')');
		if($('#b2make-account-menu-snapshot').length)$('#b2make-account-menu-snapshot').css('backgroundImage','url('+variaveis_js.avatar+avatar_version+')');
	}

	if($('#b2make-account-menu-texto').length){
		var nome_arr = variaveis_js.usuario_nome.split(' ');
		$('#b2make-account-menu-texto').html(nome_arr[0]);
	}

	$('#b2make-account-menu-snapshot,#b2make-account-menu-snapshot').off('mouseup touchstart');
	$('#b2make-account-menu-snapshot,#b2make-account-menu-snapshot').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		localStorage.setItem('b2make.mudar_foto_perfil','1');
		window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'site','_self');
	});

	b2make.msgs = {};
	b2make.holder_events = new Array();
	b2make.holder_events_visible = false;

	if(!b2make.msgs.conteinerDontExist)b2make.msgs.conteinerDontExist = "<p>N&atilde;o existe nenhum conteiner criado. &Eacute; necess&aacute;rio cri&aacute;-los antes de selecion&aacute;-los.</p>";

	b2make.holder_events.push({
		id : "perfil",
		holder : "b2make-account-menu-texto",
		target : "b2make-menu-perfil",
		visible : false
	});

	for(var i=0;i<b2make.holder_events.length;i++){
		$("#"+b2make.holder_events[i].holder).off('mouseup touchend');
		$("#"+b2make.holder_events[i].holder).on('mouseup touchend',function(e){
			e.stopPropagation();
			$.holders_close();
			
			var vars = holder_vars($(this).attr('id'),'holder');
			var visible = false;
			
			if($("#"+vars.target+" li").length != 0){
				if($("#"+vars.target).is(":visible")){
					$("#"+vars.target).hide();
				} else {
					$("#"+vars.target).show();
					visible = true;
				}
				
				$.holders_update({
					visible : visible,
					id : vars.id
				});
			} else {
				var msg = b2make.msgs.conteinerDontExist;
				
				$.alerta_open(msg,false,false);
			}
			
			holder_menus_positions();
		});
		
		$("#"+b2make.holder_events[i].target).off('mouseup touchend');
		$("#"+b2make.holder_events[i].target).on('mouseup touchend',function(e){
			e.stopPropagation();
		});
		
		$("#"+b2make.holder_events[i].target+" li").off('mouseup touchend');
		$("#"+b2make.holder_events[i].target+" li").on('mouseup touchend',function(e){
			var type = $(this).attr('data-type');
			var vars = holder_vars($(this).parent().attr('id'),'target');
			var id = $(this).attr('data-id');
			
			switch(vars.id){
				case 'perfil':
				case 'perfil-2':
					perfil_listener(e,{id:id});
				break;
				
			}
			
			$("#"+vars.target).hide();
			
			$.holders_update({
				visible : false,
				id : vars.id
			});
		});
	}

	$("html").off('mouseup touchend');
	$("html").on('mouseup touchend',$.holders_close);

	function perfil_listener(e,p){
		e.stopPropagation();
		
		switch(p.id){
			case 'exit':
				window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'logout','_self');
			break;
			case 'my-profile':
				window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'my-profile','_self');
			break;
			case 'construtor':
				window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'site','_self');
			break;
			case 'payment':
				window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'payment','_self');
			break;
			case 'upgrade-plan':
				window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'upgrade-plan','_self');
			break;
			default:
				window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+p.id,'_self');
			
		}
	}

	function holder_menus_positions(){
		$('#b2make-menu-perfil').css({left:parseInt($('#b2make-account-menu-texto').offset().left)});
		if($('#b2make-menu-perfil').length)$('#b2make-menu-perfil').css({left:parseInt($('#b2make-account-menu-texto').offset().left)});
	}

	function holder_vars(id,type){
		for(var i=0;i<b2make.holder_events.length;i++){
			switch(type){
				case 'holder':
					if(id == b2make.holder_events[i].holder){
						return b2make.holder_events[i];
					}
				break;
				case 'target':
					if(id == b2make.holder_events[i].target){
						return b2make.holder_events[i];
					}
				break;
				
			}
		}
		
		return false;
	}
});	