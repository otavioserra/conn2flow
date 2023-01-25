$(document).ready(function(){
	
	// ===== cards special 'a plataforma'.

	$('.special.cards .image').dimmer({
		on: 'ontouchstart' in document.documentElement ? 'click' : 'hover',
	});
	
	// ===== animação em 'como funciona'.
	
	/* $('.iconAnimation')
		.transition({
			animation : 'jiggle',
			duration  : 800,
			interval  : 1200
		})
		.transition('set looping')
		; */
});