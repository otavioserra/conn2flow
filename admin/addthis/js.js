$(document).ready(function(){
	sep = "../../";
	
	$(".opcao").hover(
		function(){
			$(this).css('background-color', '#EDEEF0');
		},
		function(){
			$(this).css('background-color', '#F7F7F8');
		}
	);
	
	$("#form").submit(function() {
		if(!confirm("Ser� necess�rio reiniciar o sistema. Tem certeza que deseja GRAVAR as altera��es?")){return false;}
	});
	
	
});