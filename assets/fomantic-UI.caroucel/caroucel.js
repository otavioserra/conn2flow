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
			settings.totalSlides = 0;
			settings.currentSlide = 1;
			
			// ===== .
			
			var contHeight = 300;
			
			if(settings.verticalResize === 'auto'){
				$(obj).css('height','auto');
			} else {
				$(obj).css('height',settings.verticalResize+'px');
				contHeight = parseInt(settings.verticalResize);
			}
			
			// ===== Control center holder.
			
			var controlCenterHolder = $('<div class="holder"></div>');
			var controlCenter = $('<div class="control-center"></div>');
			
			$(controlCenterHolder).append(controlCenter);
			$(obj).append(controlCenterHolder);
			
			// ===== Change all items.
			
			var first = true;
			var num = 0;
			$(obj).find('.items').find('.item').each(function(){
				// ===== Apply img bg for all items slides.
				
				if($(this).attr('data-src') !== undefined) {
					$(this).css('backgroundImage','url('+$(this).attr('data-src')+')');
				}
				
				// ===== Central circles controls.
				
				num++;
				settings.totalSlides++;
				
				if(first){
					var controlCircle = $('<i class="circle inverted secondary link icon" data-num="'+num+'"></i>');
					first = false;
				} else {
					var controlCircle = $('<i class="circle inverted link icon" data-num="'+num+'"></i>');
				}
				
				controlCenter.append(controlCircle);
				
				// ===== Apply same identification number of control circle to slide for correlation.
				
				$(this).attr('data-num',num);
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
			
			$('.control-center i').on('mouseup tap',function(e){
				if(e.which != 1 && e.which != 0 && e.which != undefined) return false;
				
				changeSlide({
					direction : 'right',
					slide : $(this).attr('data-num')
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
		
		// ===== Get next slide num.
		
		var actualSlide, nextSlide, nextSlideNum;
		
		if('slide' in opt){
			if(settings.currentSlide == parseInt(opt.slide)){
				return false;
			} else {
				nextSlideNum = parseInt(opt.slide);
			}
			
			if(parseInt(opt.slide) > settings.currentSlide){
				opt.direction = 'right';
			} else {
				opt.direction = 'left';
			}
		} else {
			switch(opt.direction){
				case 'right':
					if(settings.totalSlides <= settings.currentSlide + 1){
						nextSlideNum = settings.currentSlide + 1;
					} else {
						nextSlideNum = 1;
					}
				break;
				default:
					if(0 < settings.currentSlide - 1){
						nextSlideNum = settings.currentSlide - 1;
					} else {
						nextSlideNum = settings.totalSlides;
					}
			}
		}
		
		// ===== Get both actual slide and next slide.
		
		var first = true;
		$(obj).find('.items').find('.item').each(function(){
			var interactionNum = parseInt($(this).attr('data-num'));
			
			// ===== Get actual slide obj.
			
			if(interactionNum == settings.currentSlide){
				actualSlide = $(this);
				actualSlide.remove();
			}
			
			// ===== Get the next slide.
			
			if(interactionNum == nextSlideNum){
				nextSlide = $(this);
				nextSlide.remove();
			}
		});
		
		$(obj).find('.items').prepend(nextSlide);
		$(obj).find('.items').prepend(actualSlide);
		
		// ===== Change state to animating for prevent overflow.
		
		settings.animating = true;
		
		// ===== Get parent size to use as root coordinates for the change animation.
		
		var parentWidth = $(obj).outerWidth(true);
		var parentHeight = $(obj).outerHeight(true);
		
		// ===== Change slides options before animation and repositioning the actual slides on DOM to last position.
		
		actualSlide.css('position','absolute');
		actualSlide.css('zIndex','1');
		actualSlide.css('top','0');
		actualSlide.css('left','0');
		actualSlide.css('width',parentWidth);
		actualSlide.css('height',parentHeight);
		
		nextSlide.css('position','absolute');
		nextSlide.css('zIndex','1');
		nextSlide.css('top','0');
		nextSlide.css('left','0');
		nextSlide.css('width',parentWidth);
		nextSlide.css('height',parentHeight);
		
		// ===== Animate and change slides based on direction and when was finish return actualSlide and nextSlide to default behavior.
		
		var leftEnd;
		switch(opt.direction){
			case 'right':
				leftEnd = parentWidth+'px';
				nextSlide.css('left','-'+parentWidth+'px');
			break;
			default:
				leftEnd = '-'+parentWidth+'px';
				nextSlide.css('left',parentWidth+'px');
		}
		
		console.log('parentWidth: '+parentWidth+', parentHeight: '+parentHeight+', leftEnd: '+leftEnd);
		
		actualSlide.animate({
			left: leftEnd,
		}, settings.animation.time, function() {
			actualSlide.css('position','relative');
			actualSlide.css('zIndex','auto');
			actualSlide.css('left','auto');
			actualSlide.css('top','auto');
			actualSlide.css('width','inherit');
			actualSlide.css('height','inherit');
			
			actualSlide.remove();
			
			$(obj).find('.items').append(actualSlide);
		
			settings.animating = false;
		});
		
		nextSlide.animate({
			left: '0',
		}, settings.animation.time, function() {
			nextSlide.css('position','relative');
			nextSlide.css('zIndex','auto');
			nextSlide.css('left','auto');
			nextSlide.css('top','auto');
			nextSlide.css('width','inherit');
			nextSlide.css('height','inherit');
		
			settings.animating = false;
			
			settings.currentSlide = parseInt(nextSlide.attr('data-num'));
			
			refreshControlCenter();
		});
	}
	
	function refreshControlCenter(){
		// ===== Compare the num of all slides with all dot's control center to mark current active slide.
		
		var first = true;
		
		$(obj).find('.items').find('.item').each(function(){
			var currentNum = $(this).attr('data-num');
			
			$(obj).find('.control-center').find('i').each(function(){
				var currentControlNum = $(this).attr('data-num');
				
				if(currentNum == currentControlNum){
					if(first){
						$(this).addClass('secondary');
					} else {
						$(this).removeClass('secondary');
					}
				}
			});
			
			first = false;
		});
	}
	
})( jQuery );