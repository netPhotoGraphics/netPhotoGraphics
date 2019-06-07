<?php
if (!defined('WEBPATH'))
	die();
// force UTF-8 Ø
?>
<!DOCTYPE html>
<html>
	<head>

		<?php npgFilters::apply('theme_head'); ?>

	</head>

	<body onload="blurAnchors()">
		<?php npgFilters::apply('theme_body_open'); ?>

		<!-- Wrap Header -->
		<div id="header">
			<div id="gallerytitle">

				<!-- Logo -->
				<div id="logo">
					<?php printLogo(); ?>
				</div>
			</div> <!-- gallerytitle -->

			<!-- Crumb Trail Navigation -->
			<div id="wrapnav">
				<div id="navbar">
					<span><?php printHomeLink('', ' | '); ?>
						<?php
						if (getOption('gallery_index')) {
							?>
							<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Main Index'); ?>"><?php printGalleryTitle(); ?></a>
							<?php
						} else {
							?>
							<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Albums Index'); ?>"><?php printGalleryTitle(); ?></a>
							<?php
						}
						?></a></span> | <?php echo gettext('Daily summary'); ?>
				</div>
			</div> <!-- wrapnav -->

		</div> <!-- header -->

		<!-- Wrap Main Body -->
		<div id="content">

			<small>&nbsp;</small>
			<div id="main2">
				<?php
				if ($zenpage = extensionEnabled('zenpage')) {
					?>
					<div id="content-left">
						<?php
						include getPlugin('/daily-summary/daily-summary_content.php');
						?>
						?>
					</div><!-- content left-->
					<div id="sidebar">
						<?php include("sidebar.php"); ?>
					</div><!-- sidebar -->
					<?php
				}
				?>
				<br style="clear:both" />
			</div> <!-- main2 -->

		</div> <!-- content -->

		<?php
		printFooter();
		npgFilters::apply('theme_body_close');
		?>

	</body>
</html>