jQuery(function ($) {
    $().fancybox({
        selector: '[data-fancybox="images"]',
        loop: true,
        preventCaptionOverlap: false,
        margin: [20, 0],
        buttons: ['thumbs', 'slideShow', 'close'],
        protect: true,
        animationEffect: 'fade',
        touch: {vertical: false},
        slideShow: {autoStart: false, speed: 3000},
        clickContent: function (current) {
            return current.type === 'image' ? 'toggleControls' : false;
        },
        clickSlide: false,
        clickOutside: false,
        dblclickContent: function (current) {
            return current.type === 'image' ? 'next' : false;
        },
        caption: function () {
            var c = $(this).find('.caption');
            return c.length ? c.html() : $(this).attr('title');
        },
        mobile: {
            thumbs: false,
            idleTime: 3,
            dblclickSlide: false
        }
    });
});
