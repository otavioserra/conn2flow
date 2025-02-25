function menu_paginas(id){
	var submit = false;
	switch(id){
		case 1:
			document.getElementById('opcao_menu').value = 'comeco';
			submit = true;
			break;
		case 2:
			document.getElementById('opcao_menu').value = 'anterior';
			submit = true;
			break;
		case 3:
			document.getElementById('opcao_menu').value = 'paginas';
			submit = true;
			break;
		case 4:
			document.getElementById('opcao_menu').value = 'proximo';
			submit = true;
			break;
		case 5:
			document.getElementById('opcao_menu').value = 'ultimo';
			submit = true;
			break;
	}
	
	if(submit){
		document.getElementById('menu_form').submit();
	}
}

function menu_paginas2(form,id){
	var submit = false;
	
	switch(id){
		case 1:
			form.opcao_menu.value = 'comeco';
			submit = true;
			break;
		case 2:
			form.opcao_menu.value = 'anterior';
			submit = true;
			break;
		case 3:
			form.opcao_menu.value = 'paginas';
			submit = true;
			break;
		case 4:
			form.opcao_menu.value = 'proximo';
			submit = true;
			break;
		case 5:
			form.opcao_menu.value = 'ultimo';
			submit = true;
			break;
	}
	
	if(submit){
		form.submit();
	}
}

//set up the theme switcher on the homepage
$('div').live('pagecreate',function(event){
	if( !$(this).is('.ui-dialog')){ 
		var appendEl = $(this).find('.ui-footer:last');
		
		if( !appendEl.length ){
			appendEl = $(this).find('.ui-content');
		}
		
		if( appendEl.is("[data-position]") ){
			return;
		}
	}	

});

//collapse page navs after use
$(function(){
	$('body').delegate('.content-secondary .ui-collapsible-content', 'vclick',  function(){
		$(this).trigger("collapse")
	});
});

function setDefaultTransition(){
	var winwidth = $( window ).width(),
		trans ="slide";
		
	if( winwidth >= 1000 ){
		trans = "none";
	}
	else if( winwidth >= 650 ){
		trans = "fade";
	}

	$.mobile.defaultPageTransition = trans;
}

//set default documentation 
$( document ).bind( "mobileinit", setDefaultTransition );
$(function(){
	$( window ).bind( "throttledresize", setDefaultTransition );
});