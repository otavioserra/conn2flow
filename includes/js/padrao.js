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

function menu_mudar_pagina(){
	var obj = $('input[name="paginas"]');
	var value = obj.val();
	
	var form = obj.parent().parent().parent();
	
	form.find('input[name="opcao_menu"]').val('paginas');
	form.submit();
}

$(document).ready(function(){
	$('._in_menu_seta_pai').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;

		var form = $(this).parent();

		form.find('input[name="opcao_menu"]').val($(this).attr('data-id'));
		form.submit();
	});
	
	$('input[name="paginas"]').on('change',function(e){
		var form = $(this).parent().parent().parent();

		form.find('input[name="opcao_menu"]').val('paginas');
		form.submit();
	});
	
	$('.-in-pagina-numero').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		var form = $(this).parent();
		
		form.find('input[name="paginas"]').val($(this).html());

		form.find('input[name="opcao_menu"]').val('paginas');
		form.submit();
	});
});