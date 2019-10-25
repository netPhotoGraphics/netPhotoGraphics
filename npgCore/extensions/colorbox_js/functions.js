/* Colorbox resize function for images */
var resizeTimer;

function resizeColorBoxImage() {
	if (resizeTimer)
		clearTimeout(resizeTimer);
	resizeTimer = setTimeout(function () {
		if (jQuery('#cboxOverlay').is(':visible')) {
			jQuery.colorbox.resize({width: '90%'});
			jQuery('#cboxLoadedContent img').css('max-width', '100%').css('height', 'auto');
		}
	}, 300)
}
// Colorbox resize function for Google Maps
function resizeColorBoxMap() {
	if (resizeTimer)
		clearTimeout(resizeTimer);
	resizeTimer = setTimeout(function () {
		var mapw = $(window).width() * 0.8;
		var maph = $(window).height() * 0.7;
		if (jQuery('#cboxOverlay').is(':visible')) {
			$.colorbox.resize({innerWidth: mapw, innerHeight: maph});
			$('#cboxLoadedContent iframe').contents().find('#map_canvas').css('width', '100%').css('height', maph - 20);
		}
	}, 500)
}
// Resize Colorbox when changing mobile device orientation 
window.addEventListener('orientationchange', function () {
	resizeColorBoxImage();
	parent.resizeColorBoxMap()
}, false);

