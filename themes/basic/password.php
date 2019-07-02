<?php
// force UTF-8 Ø

if (!defined('WEBPATH'))
	die();
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
		<?php npgFilters::apply('theme_body_close'); ?>
	</body>
</html>
