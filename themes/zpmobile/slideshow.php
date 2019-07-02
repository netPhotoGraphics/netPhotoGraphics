<?php
// force UTF-8 Ø
if (!defined('WEBPATH'))
	die();
if (function_exists('printSlideShow')) {
	?>
	<!DOCTYPE html>
	<html>
		<head>
			<?php npgFilters::apply('theme_head'); ?>
			<meta charset="<?php echo LOCAL_CHARSET; ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<?php scriptLoader($_themeroot . '/style.css'); ?>
		</head>
		<body>
			<?php npgFilters::apply('theme_body_open'); ?>
			<div id="slideshowpage">
				<?php printSlideShow(true, true); ?>
			</div>
			<?php npgFilters::apply('theme_body_close'); ?>
		</body>
	</html>
	<?php
} else {
	include(CORE_SERVERPATH . '404.php');
}
?>
