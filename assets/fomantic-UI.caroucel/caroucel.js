(function( $ ){
	var obj;
	var settings;
    var pubMethods = {
        init : function(options) {
			// ===== Get main object.
			
			obj = this;
			
			// ===== default options.
			
			var defaultOptions = {
				verticalResize : 'auto', // Auto / Value resize of main holder - Accept: 'auto' or 'int value' to fix vertical height
				animation: { // Default values of animation slides
					time: 300, // Time until animation finish
				}
			};
			
			settings = $.extend(defaultOptions, options);
			
			// ===== Internal settings.
			
			settings.animating = false; // State of animating or stoped.
			
			// ===== .
			
			var contHeight = 300;
			
			if(settings.verticalResize === 'auto'){
				$(obj).css('height','100%');
			} else {
				$(obj).css('height',settings.verticalResize+'px');
				contHeight = parseInt(settings.verticalResize);
			}
			
			// ===== Control center holder.
			
			var controlCenter = $('<div class="control-center"></div>');
			$(obj).append(controlCenter);
			
			// ===== Change all items.
			
			var first = true;
			$(obj).find('.items').find('.item').each(function(){
				// ===== Apply img bg for all items.
				
				if($(this).attr('data-src') !== undefined) {
					$(this).css('backgroundImage','url('+$(this).attr('data-src')+')');
				}
				
				// ===== Central circles controls.
				
				if(first){
					var controlCircle = $('<i class="circle inverted secondary link icon"></i>');
					first = false;
				} else {
					var controlCircle = $('<i class="circle inverted link icon"></i>');
				}
				
				controlCenter.append(controlCircle);
			});
			
			// ===== Controls.
			
			var controlLeft = $('<div class="control-left"><i class="chevron left inverted link big icon"></i></div>');
			var controlRight = $('<div class="control-right"><i class="chevron right inverted link big icon"></i></div>');
			
			$(obj).append(controlLeft);
			$(obj).append(controlRight);
			
			var controlTop = (contHeight) / 2;
			
			controlLeft.css('top',controlTop+'px');
			controlRight.css('top',controlTop+'px');
			
			// ===== Show caroucel conteiner.
			
			$(obj).show();
			
			// ===== Apply margin ajusts.
			
			var controlTop = (contHeight) / 2 - (controlLeft.outerHeight(true)) / 2;
			
			controlLeft.css('top',controlTop+'px');
			controlRight.css('top',controlTop+'px');
			
			// ===== Listeners of buttons.
			
			$('.control-left').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				console.log('.control-left');
				
				changeSlide({
					direction : 'left'
				});
			});
			
			$('.control-right').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				changeSlide({
					direction : 'right'
				});
			});
        },
        show : function( ) {    },// IS
        hide : function( ) {  },// GOOD
        update : function( content ) {  }// !!!
    };

    $.fn.caroucel = function(methodOrOptions) {
        if ( pubMethods[methodOrOptions] ) {
            return pubMethods[ methodOrOptions ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof methodOrOptions === 'object' || ! methodOrOptions ) {
            // Default to "init"
            return pubMethods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  methodOrOptions + ' does not exist on jQuery.caroucel' );
        }    
    };
	
	function changeSlide(opt = {}){
		// ===== Return false if we have less than 2 slides or if other animation was started after.
		
		if($(obj).find('.items').find('.item').length < 2 || settings.animating){
			return false;
		}
		
		// ===== Change state to animating for prevent overflow.
		
		settings.animating = true;
		
		// ===== Get parent resolution to use as root coordinates for the change animation.
		
		var parentWidth = $(obj).outerWidth(true);
		var parentHeight = $(obj).outerHeight(true);
		
		// ===== Get both actual slide and next slide.
		
		var actualSlide, nextSlide;
		
		var first = true;
		$(obj).find('.items').find('.item').each(function(){
			
			if(first){
				actualSlide = $(this);
				first = false;
			} else {
				nextSlide = $(this);
				return false;
			}
		});
		
		// ===== Change slides options before animation and repositioning the actual slides on DOM to last position.
		
		actualSlide.css('position','absolute');
		actualSlide.css('zIndex','1');
		actualSlide.css('top','0');
		actualSlide.remove();
		
		$(obj).find('.items').appendTo(obj);
		
		// ===== Animate and change slides based on direction and when was finish return actualSlide do default behavior.
		
		var leftEnd;
		switch(opt.direction){
			case 'right':
				actualSlide.css('left','0');
				leftEnd = parentHeight;
			break;
			default:
				actualSlide.css('left',parentHeight);
				leftEnd = '0';
		}
		
		actualSlide.animate({
			left: leftEnd,
		}, settings.animation.time, function() {
			actualSlide.css('position','relative');
			actualSlide.css('zIndex','1');
			settings.animating = false;
		});
	}
	
})( jQuery );