function _url_name(){
	var url_aux = location.pathname;
	var url_parts;
	
	url_parts = url_aux.split('/');
	
	return url_parts[url_parts.length-1];
}

function escolher_para(id){
	var window_escolher_para = window.open(_url_name()+"?opcao=escolher_para&id="+id,'window_para','top=100,left=200,width=600,height=600,channelmode=no,directories=no,fullscreen=no,toolbar=no,titlebar=no,status=no,menubar=no,location=no,scrollbars=yes');
	window_escolher_para.focus();
}

$(document).ready(function(){
	sep = "../../";
	
	$("#form").submit(function(){
		if(!$("#para_id").val()){					alert("É obrigatório definir o campo Para!");	return false;}
		if(!$("#assunto").val()){					alert("É obrigatório preencher o Assunto!");	return false;}
		if(!$("#mensagem").val()){					alert("É obrigatório preencher a Mensagem!");	return false;}
	});
	
	$("#form_usuario").submit(function(){
		if($("#nome_id").val() > 0){
			$('#para_opcao', window.opener.document).val('usuario');
			$('#para', window.opener.document).val($("#busca_nome2").val());
			$('#para_id', window.opener.document).val($("#nome_id").val());
			
			window.opener.focus();
			window.close();
		} else {
			alert("Escolha um usuário antes de clicar em ESCOLHER.\n\nNOTA: Selecione a caixa de texto. Digite uma parte do texto do nome do usuário que está buscando, aguarde um pouco que o sistema retorna as possibilidades em uma lista, escolha uma opção e clique no botão ESCOLHER ou então aperte a tecla ENTER do teclado.");
		}
		return false;
	});
	
	$("#form_grupo").submit(function(){
		var grupo_nomes = "";
		var grupo_id = "";
		
		for(var i=0;i<parseInt($('#grupo_num').val());i++){
			if($("#grupo_id"+i+":checked").val()){	
				if(grupo_id)		grupo_id = grupo_id + ',' + $('#grupo_id'+i).val(); 				else grupo_id = $('#grupo_id'+i).val();
				if(grupo_nomes)	grupo_nomes = grupo_nomes + ', ' + $('#grupo_nome'+i).html(); 	else grupo_nomes = $('#grupo_nome'+i).html();
			}
		}
		
		$('#para_opcao', window.opener.document).val('grupo');
		$('#para', window.opener.document).val(grupo_nomes);
		$('#para_id', window.opener.document).val(grupo_id);
		
		window.opener.focus();
		window.close();
		
		return false;
	});
	
	
});