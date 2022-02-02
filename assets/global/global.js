$(document).ready(function(){
	$('#entrey-menu-principal')
		.sidebar({
			dimPage          : false,
			transition       : 'push',
			mobileTransition : 'uncover'
		})
	;
	
	if($('._gestor-menuPrincipalMobile').length > 0){
		$('._gestor-menuPrincipalMobile').css('cursor','pointer');
		$('._gestor-menuPrincipalMobile').on('mouseup tap',function(e){
			if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
			
			$('#entrey-menu-principal').sidebar('toggle');
		});
	}
	
	if($(window).width() >= 770){
		$('#entrey-menu-principal').sidebar('show');
	} else {
		$('#entrey-menu-principal').sidebar('hide');
	}
	
	$(window).on('resize', function(){
		var win = $(this);
		if(win.width() >= 770){
			$('#entrey-menu-principal').sidebar('show');
		} else {
			$('#entrey-menu-principal').sidebar('hide');
		}
	});
	
});