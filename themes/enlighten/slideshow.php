<!DOCTYPE html>
<head>
	<?php
	scriptLoader($_themeroot . '/style.css');
	scriptLoader($_themeroot . '/slideshow.css');
	npgFilters::apply('theme_head');
	?>

</head>
<body>
	<?php npgFilters::apply('theme_body_open'); ?>
	<div id="slideshowpage">
		<?php printSlideShow(true, true); ?>
	</div>
	<?php npgFilters::apply('theme_body_close'); ?>
</body>
</html>