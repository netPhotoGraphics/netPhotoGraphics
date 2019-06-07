<?php
if (!defined('WEBPATH'))
	die();
?>
<!DOCTYPE html>
<head>
	<?php
	printZDRoundedCornerJS();
	npgFilters::apply('theme_head');

	scriptLoader($_themeroot . '/style.css');
	printRSSHeaderLink('Gallery', gettext('Gallery'));
	?>
</head>

<body>
	<?php npgFilters::apply('theme_body_open'); ?>

	<div id="main">

		<?php include("header.php"); ?>


		<div id="breadcrumb">
			<h2>
				<?php if (extensionEnabled('zenpage')) { ?>
					<a href="<?php echo getGalleryIndexURL(); ?>" title="<?php echo gettext('Index'); ?>"><?php echo gettext("Index"); ?></a>»
				<?php } ?>
				<a href="<?php echo htmlspecialchars(getCustomPageURl('gallery')); ?>" title="<?php echo gettext('Gallery'); ?>"><?php echo gettext("Gallery") . " » "; ?></a>
				<strong><?php echo gettext("Daily summary"); ?></strong>
			</h2>
		</div>

		<div id="content">
			<div id="content-left">
				<?php
				include getPlugin('/daily-summary/daily-summary_content.php');
				?>
			</div><!-- content left-->



			<div id="sidebar">
				<?php include("sidebar.php"); ?>
			</div><!-- sidebar -->

			<div id="footer">
				<?php include("footer.php"); ?>
			</div>
		</div><!-- content -->

	</div><!-- main -->
	<?php npgFilters::apply('theme_body_close'); ?>
</body>
</html>