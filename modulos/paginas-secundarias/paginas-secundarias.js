$(document).ready(function(){
	$('.special.cards .image').dimmer({
		// As hover is not working on mobile, you might use click on those devices as fallback
		on: 'ontouchstart' in document.documentElement ? 'click' : 'hover',
	});
});