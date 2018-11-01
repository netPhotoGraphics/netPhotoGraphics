<!DOCTYPE html>
<head>
	<?php
	scriptLoader($_zp_themeroot . '/style.css');
	scriptLoader($_zp_themeroot . '/slideshow.css');
	zp_apply_filter('theme_head');
	?>

</head>
<body>
	<?php zp_apply_filter('theme_body_open'); ?>
	<div id="slideshowpage">
		<?php printSlideShow(true, true); ?>
	</div>
	<?php zp_apply_filter('theme_body_close'); ?>
</body>
</html>