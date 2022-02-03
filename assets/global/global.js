$(document).ready(function(){
	// ===== Menu Principal do gestor.
	
	if($('._gestor-menuPrincipalMobile').length > 0){
		$('#entrey-menu-principal')
			.sidebar({
				dimPage          : true,
				transition       : 'overlay',
				mobileTransition : 'uncover'
			})
		;
	
		$('._gestor-menuPrincipalMobile').css('cursor','pointer');
		
		$('._gestor-menuPrincipalMobile').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$('#entrey-menu-principal').sidebar('toggle');
		});
	}
	
});