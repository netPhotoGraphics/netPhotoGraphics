<?php
if (!defined('WEBPATH'))
	die();
?>
<!DOCTYPE html>
<html<?php i18n::htmlLanguageCode(); ?>>
	<head>
		<?php
		npgFilters::apply('theme_head');

		scriptLoader($_themeroot . '/style.css');
		?>
	</head>

	<body>
		<?php npgFilters::apply('theme_body_open'); ?>

		<div id="main">

			<?php include("header.php"); ?>

			<div id="content">
				<div id="breadcrumb">
					<h2>
						<?php if (class_exists('CMS')) { ?>
							<a href="<?php echo getGalleryIndexURL(); ?>" title="<?php echo gettext('Index'); ?>"><?php echo gettext("Index"); ?></a>»
						<?php } ?>
						<a href="<?php echo htmlspecialchars(getCustomPageURl('gallery')); ?>" title="<?php echo gettext('Gallery'); ?>"><?php echo gettext("Gallery"); ?></a>
						<?php if (isset($hint)) {
							?>
							» <strong><?php echo gettext("A password is required for the page you requested"); ?></strong>
							<?php
						}
						?>
					</h2>
				</div>

				<div id="content-error">

					<div class="errorbox">
						<?php printPasswordForm(isset($hint) ? $hint : NULL, isset($show) ? $show : TRUE, false); ?>
					</div>

					<?php
					if (!npg_loggedin() && function_exists('printRegistrationForm') && $_gallery->isUnprotectedPage('register')) {
						printCustomPageURL(gettext('Register for this site'), 'register', '', '<br />');
						echo '<br />';
					}
					?>
				</div>


				<div id="footer">
					<?php include("footer.php"); ?>
				</div>



			</div><!-- content -->

		</div><!-- main -->
	</body>
	<?php npgFilters::apply('theme_body_close'); ?>
</html>
