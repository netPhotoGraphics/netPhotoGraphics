<?php
// force UTF-8 Ø

if (!defined('WEBPATH'))
	die();
if (function_exists('printRegistrationForm')) {
	?>
	<!DOCTYPE html>
	<html<?php i18n::htmlLanguageCode(); ?>>
		<head>

			<?php
			npgFilters::apply('theme_head');

			scriptLoader($basic_CSS);
			scriptLoader(dirname(dirname($basic_CSS)) . '/common.css');
			?>
		</head>
		<body>
			<?php npgFilters::apply('theme_body_open'); ?>
			<div id="main">
				<div id="gallerytitle">
					<h2>
						<?php printHomeLink('', ' | '); ?>
						<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Gallery Index'); ?>"><?php echo gettext('Gallery Index'); ?></a> |
						<em><?php echo gettext('Register') ?></em>
					</h2>
				</div>
				<h2><?php echo gettext('User Registration') ?></h2>
				<?php printRegistrationForm(); ?>
			</div>
			<?php
			if (function_exists('printUserLogin_out')) printUserLogin_out("");
			if (function_exists('printLanguageSelector')) printLanguageSelector();
			?>
			<div id="credit">
				<?php printSoftwareLink(); ?>
			</div>
	</body>
	<?php npgFilters::apply('theme_body_close'); ?>
	</html>
	<?php
} else {
	include(CORE_SERVERPATH . '404.php');
}
?>