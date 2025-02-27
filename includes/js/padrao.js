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

$(document).ready(function(){
	
});