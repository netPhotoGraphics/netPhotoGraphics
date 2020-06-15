<!DOCTYPE html>
<html<?php i18n::htmlLanguageCode(); ?>>
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
	</body>
	<?php npgFilters::apply('theme_body_close'); ?>
</html>