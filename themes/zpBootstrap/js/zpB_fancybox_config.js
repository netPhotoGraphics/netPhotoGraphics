$(document).ready( function() {
	$().fancybox({
		// FancyBox shows only filtered items with Isotope
		selector : $("#isotope-wrap").length ? '.isotope-item:visible > [data-fancybox="images"]' : '[data-fancybox="images"]',
		loop : true,
		preventCaptionOverlap: false,
		margin : [20, 0],
		buttons : [
			'thumbs',
			'slideShow',
			'close'
		],
		protect : true,
		animationEffect : 'fade',
		touch : {
			vertical : false
		},
		slideShow : {
			autoStart : false,
			speed : 3000
		},

		clickContent : function( current, event ) {
			return current.type === 'image' ? 'toggleControls' : false;
		},
		clickSlide : false,
		clickOutside : false,
		dblclickContent : function( current, event ) {
			return current.type === 'image' ? 'next' : false;
		},

		caption : function( instance, item ) {
			if ($(this).find('.caption').length) {
				return $(this).find('.caption').html();
			} else {
				return $(this).attr('title');
			};
		},

		mobile : {
			thumbs : false,
			idleTime : 3,

			clickContent : function( current, event ) {
				return current.type === 'image' ? 'toggleControls' : false;
			},
			dblclickContent : function( current, event ) {
				return current.type === 'image' ? 'next' : false;
			},
			dblclickSlide : false,
		},
	});
});