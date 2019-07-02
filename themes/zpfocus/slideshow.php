<?php
/**
 * Slideshow page for gslideshow
 * Also covers for core slideshow script when gslideshow is deactivated.
 */
// force UTF-8 Ø

if (!defined('WEBPATH'))
	die(); // are we in the netPhotoGraphics environment? if not, kill application.
?>

<?php if (function_exists('printGslideshow')) { ?>

	<!DOCTYPE html>
	<html>
		<head>
			<?php npgFilters::apply('theme_head'); ?>
			<meta name="viewport" content="width=device-width" />
			<title><?php echo gettext('Slideshow') . ' | ' . html_encode(getBareGalleryTitle()); ?></title>
		</head>
		<body>
			<?php npgFilters::apply('theme_body_open'); ?>
			<?php printGslideshow(); ?>
			<?php npgFilters::apply('theme_body_close'); ?>
		</body>
	</html>

<?php } else { ?>

	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<?php
			npgFilters::apply('theme_head');
			scriptLoader(SEREVERPATH . '/' . THEMEFOLDER . '/zpfocus/slideshow.css');
			?>
		</head>
		<body>
			<?php npgFilters::apply('theme_body_open'); ?>
			<div id='slideshowpage">
					 <?php printSlideShow(true, true); ?>
					 </div>
					 <?php npgFilters::apply('theme_body_close'); ?>

					 </body>
					 </html>

				 <?php } ?>