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

		scriptLoader($_themeroot . '/style.css');
		?>
	</head>

	<body>
		<?php npgFilters::apply('theme_body_open'); ?>

		<div id="main">

			<div id="header">
				<h1><?php printGalleryTitle(); ?></h1>
			</div>

			<div id="content">
				<div id="breadcrumb">
					<h2><a href="<?php echo getGalleryIndexURL(); ?>">Index</a>
						<?php if (isset($hint)) {
							?> » <strong><?php echo gettext("A password is required for the page you requested"); ?></strong>
							<?php
						}
						?></h2>
				</div>

				<div id="content-error">

					<div class="errorbox">
						<?php printPasswordForm(isset($hint) ? $hint : NULL, isset($show) ? $show : TRUE, false, isset($hint) ? WEBPATH : NULL); ?>
					</div>

					<?php
					if (!npg_loggedin() && function_exists('printRegisterURL') && $_gallery->isUnprotectedPage('register')) {
						printRegisterURL(gettext('Register for this site'), '<br />');
						echo '<br />';
					}
					?>
				</div>


				<div id="footer">
					<?php include("footer.php"); ?>
				</div>



			</div><!-- content -->

		</div><!-- main -->
		<?php
		npgFilters::apply('theme_body_close');
		?>
	</body>
</html>
