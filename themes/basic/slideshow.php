<?php
// force UTF-8 Ø
if (!defined('WEBPATH'))
	die();
if (function_exists('printSlideShow')) {
	?>
	<!DOCTYPE html>
	<html>
		<head>

			<?php
			npgFilters::apply('theme_head');

			scriptLoader($zenCSS);
			scriptLoader(dirname(dirname($zenCSS)) . '/common.css');
			?>
		</head>

		<body>
			<?php
			npgFilters::apply('theme_body_open');
			switch (getOption('Theme_colors')) {
				case 'light':
				case 'sterile-light':
					$class = 'slideshow_light';
					break;
				case 'dark':
				case 'sterile-dark':
				default:
					$class = 'slideshow_dark';
					break;
			}
			?>
			<div id="slideshowpage" class="<?php echo $class; ?>">
				<?php
				printSlideShow(true, true);
				?>
			</div>
	<?php npgFilters::apply('theme_body_close'); ?>
		</body>
	</html>
	<?php
} else {
	include(CORE_SERVERPATH . '404.php');
}
?>