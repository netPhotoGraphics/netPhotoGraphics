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
						<?php printHomeLink('', ' | '); ?><a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Gallery Index'); ?>"><?php printGalleryTitle(); ?></a>
					</span> | <?php echo gettext("Object not found"); ?>
				</h2>
			</div>
			<div id="padbox">
				<?php print404status(); ?>
			</div>
		</div>
		<div id="credit">
			<?php printSoftwareLink(); ?>
		</div>
		<?php
		npgFilters::apply('theme_body_close');
		?>
	</body>
</html>
