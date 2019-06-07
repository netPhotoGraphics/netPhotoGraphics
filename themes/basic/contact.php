<?php
// force UTF-8 Ø

if (!defined('WEBPATH'))
	die();
if (function_exists('printContactForm')) {
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
			<?php npgFilters::apply('theme_body_open'); ?>
			<div id="main">
				<div id="gallerytitle">
					<h2>
						<?php printHomeLink('', ' | '); ?>
						<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Gallery Index'); ?>"><?php echo gettext('Gallery Index'); ?></a> |
						<em><?php echo gettext('Contact us'); ?></em>
					</h2>
				</div>
				<h3><?php echo gettext('Contact us') ?></h3>
				<?php printContactForm(); ?>
			</div>
			<?php @call_user_func('printLanguageSelector'); ?>
			<div id="credit">
				<?php printSoftwareLink(); ?>
			</div>
			<?php
			npgFilters::apply('theme_body_close');
			?>
		</body>
	</html>
	<?php
} else {
	include(CORE_SERVERPATH . '404.php');
}
?>