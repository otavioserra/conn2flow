$(document).ready(function(){
	sep = "../../";
	
	$(".opcao").hover(
		function(){
			$(this).css('background-image', 'url(../../images/admin/box-cinza.png?v=1)');
		},
		function(){
			$(this).css('background-image', 'url(../../images/admin/box-branco.png?v=1)');
		}
	);
	
	$("#form").submit(function() {
		if(!confirm("Ser� necess�rio reiniciar o sistema. Tem certeza que deseja GRAVAR as altera��es?")){return false;}
	});
	
	
});