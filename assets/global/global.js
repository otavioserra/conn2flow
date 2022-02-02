$(document).ready(function(){
	$('.ui.menu.sidebar')
		.sidebar({
			dimPage          : true,
			transition       : 'overlay',
			mobileTransition : 'uncover'
		})
	;
	
	if($('._gestor-menuPrincipalMobile').length > 0){
		$('._gestor-menuPrincipalMobile').css('cursor','pointer');
		$('._gestor-menuPrincipalMobile').sidebar('toggle');
	}
	
});