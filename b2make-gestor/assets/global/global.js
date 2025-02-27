$(document).ready(function(){
	// ===== Menu Principal do gestor.
	
	if($('.menuComputerCont').length > 0){
		// ===== Manter a posição do scroll dos dois menus de maneira persistente entre páginas.
		
		$('.menuComputerCont').on('scroll',function(e){
			sessionStorage.setItem('menuComputerContScroll', $(this).scrollTop());
		});
		
		if(sessionStorage.getItem("menuComputerContScroll")){
			$('.menuComputerCont').scrollTop(sessionStorage.getItem("menuComputerContScroll"));
		}
		
		$('#entrey-menu-principal').on('scroll',function(e){
			sessionStorage.setItem('menuMobileContScroll', $(this).scrollTop());
		});
		
		if(sessionStorage.getItem("menuMobileContScroll")){
			$('#entrey-menu-principal').scrollTop(sessionStorage.getItem("menuMobileContScroll"));
		}
	}
	
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