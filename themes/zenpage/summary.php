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

		if (class_exists('RSS'))
			printRSSHeaderLink('Gallery', gettext('Gallery'));
		?>
	</head>

	<body>
		<?php npgFilters::apply('theme_body_open'); ?>

		<div id="main">

			<div id="header">
				<h1><?php printGalleryTitle(); ?></h1>
				<?php
				if (getOption('Allow_search')) {
					printSearchForm("", "search", "", gettext("Search gallery"));
				}
				?>
			</div>


			<div id="breadcrumb">
				<h2><a href="<?php echo getGalleryIndexURL(); ?>" title="<?php echo gettext('Index'); ?>"><?php echo gettext("Index"); ?></a> » <strong><?php echo gettext("Archive View"); ?></strong>
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
		<?php
		npgFilters::apply('theme_body_close');
		?>
	</body>
</html>