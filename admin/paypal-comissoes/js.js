$(document).ready(function(){
	sep = "../../";
	
	$('#cobrar-manualmente').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		if(confirm('Você confirma que quer cobrar manualmente esta loja?'))
		window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'admin/paypal-comissoes/?opcao=cobrar-manualmente&id='+$(this).attr('data-id'),'_self');
	});
	
	$('#cobrar-com-paypal').on('mouseup tap',function(e){
		if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
		
		if(confirm('Você confirma que quer cobrar com o PayPal esta loja?'))
		window.open(document.location.protocol+'//'+document.location.hostname+variaveis_js.site_raiz+'admin/paypal-comissoes/?opcao=cobrar-com-paypal&id='+$(this).attr('data-id'),'_self');
	});
	
	
});