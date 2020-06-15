<?php
// force UTF-8 Ø

if (!defined('WEBPATH'))
	die();
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
					<span>
						<?php printHomeLink('', ' | '); ?>
						<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Gallery Index'); ?>"><?php printGalleryTitle(); ?></a>
					</span>
					<?php
					if (isset($hint)) {
						echo '| ' . gettext("A password is required for the page you requested");
					}
					?>
				</h2>
			</div>
			<div id="padbox">
				<?php printPasswordForm(isset($hint) ? $hint : NULL, isset($show) ? $show : TRUE, false, isset($hint) ? WEBPATH : NULL); ?>
			</div>
		</div>
		<div id="credit">
			<?php
			if (!npg_loggedin() && function_exists('printRegisterURL') && $_gallery->isUnprotectedPage('register')) {
				echo '<p>';
				printRegisterURL(gettext('Register for this site'), '<br />');
				echo '</p>';
			}
			?>
			<?php printSoftwareLink(); ?>
		</div>
	</body>
	<?php npgFilters::apply('theme_body_close'); ?>
</html>
