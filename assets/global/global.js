$(document).ready(function(){
	$('#entrey-menu-principal')
		.sidebar({
			dimPage          : true,
			transition       : 'push',
			mobileTransition : 'uncover'
		})
	;
	
	if($('._gestor-menuPrincipalMobile').length > 0){
		var sidebarShow = false;
		
		$('._gestor-menuPrincipalMobile').css('cursor','pointer');
		$('._gestor-menuPrincipalMobile').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			if(sidebarShow){
				$('#entrey-menu-principal').sidebar('hide');
			} else {
				$('#entrey-menu-principal').show();
				$('#entrey-menu-principal').sidebar('show');
			}
		});
	}
	
});