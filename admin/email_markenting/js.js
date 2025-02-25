function _url_name(){
	var url_aux = location.pathname;
	var url_parts;
	
	url_parts = url_aux.split('/');
	
	return url_parts[url_parts.length-1];
}

function enviar(id){
	var start = true;
	
	if(id){
		if(confirm("Esse processo dará início ao envio de e-mails. Deseja continuar ?")){
			if(start)
			start = "&email_start=sim";
			
			window.open(_url_name()+"?opcao=email_loop&id="+id+start,'email_markenting_sender','top=200,left=200,width=500,height=420,channelmode=no,directories=no,fullscreen=no,toolbar=no,titlebar=no,status=no,menubar=no,location=no,scrollbars=no');
		}
	}
}

function escolher_grupos(id){
	window.open(_url_name()+"?opcao=escolher_grupos&id="+id,'email_markenting_sender','top=200,left=200,width=500,height=420,channelmode=no,directories=no,fullscreen=no,toolbar=no,titlebar=no,status=no,menubar=no,location=no,scrollbars=yes');
}

$(document).ready(function(){
	sep = "../../";

	if($('#opcao').val() == 'envios'){
		setTimeout(function(){ window.open("?opcao=envios","_self"); }, 5000);
	}
	
	$("#form").submit(function() {
		if(!$("#assunto").val()){							alert("É obrigatório preencher o Assunto!");	return false;}
	});
	
	$(".link_hover").hover(
		function(){
			$('body').css('cursor', 'pointer');
		},
		function(){
			$('body').css('cursor', 'default'); 
		}
	);
	
	$(".enviar_mail").click(function() {
		var enviar = false;
		var id = this.id;
		var enviando = $('#enviando').val();
		
		id = id.replace(/enviar_mail_/gi,'');
		
		if(enviando){
			if(confirm("JÁ EXISTE UMA NEWSLETTER SENDO ENVIADA. Deseja parar o envio atual e iniciar o envio dessa newsletter selecionada?")){
				enviar = true;			
			}
		} else if(confirm("Esse processo dará início ao envio de e-mails. Deseja continuar ?")){
			enviar = true;
		}
		
		if(enviar){
			$.ajax({
				url: 'index.php',
				data: { ajax : 'sim', id : id , enviar_mail : 'sim' }
			});
			
			if(confirm("Deseja visualizar a tela de status de envio?")){
				window.open("?opcao=envios","_self");
			}			
		}
	});
	
	$(".check_all").click(function() {
		var check_name = $("#check_name").val();
		var campos_num = parseInt($("#campos_num").val());
		
		for(var i=0;i<campos_num;i++){
			$("#"+check_name+i).attr('checked', true);
		}
	});
	
	$(".uncheck_all").click(function() {
		var check_name = $("#check_name").val();
		var campos_num = parseInt($("#campos_num").val());
		
		for(var i=0;i<campos_num;i++){
			$("#"+check_name+i).attr('checked', false);
		}
	});
	
	function checkMail(mail){
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
	}
	
	function load_popup(){
		var id = $("#id").val();
		
		window.open(url_name()+"?opcao=email_loop&id="+id,'email_markenting_sender','top=200,left=200,width=500,height=420,channelmode=no,directories=no,fullscreen=no,toolbar=no,titlebar=no,status=no,menubar=no,location=no,scrollbars=no');
	}
	
});