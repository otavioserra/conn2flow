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
		$('._gestor-menuPrincipalMobile').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$('.ui.menu.sidebar').sidebar('toggle');
		});
	}
	
});