<?php
if (!defined('WEBPATH'))
	die();
?>
<!DOCTYPE html>
<html>
	<head>

		<?php
		npgFilters::apply('theme_head');

		scriptLoader($_themeroot . '/zen.css');
		?>
	</head>
	<body class="sidebars">
		<?php npgFilters::apply('theme_body_open'); ?>
		<div id="navigation"></div>
		<div id="wrapper">
			<div id="container">
				<div id="header">
					<div id="logo-floater">
						<div>
							<h1 class="title"><a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Gallery Index'); ?>"><?php echo html_encode(getGalleryTitle()); ?></a></h1>
						</div>
					</div>
				</div>
				<!-- header -->
				<div class="sidebar">
					<div id="leftsidebar">
						<?php include("sidebar.php"); ?>
					</div>
				</div>

				<div id="center">
					<div id="squeeze">
						<div class="right-corner">
							<div class="left-corner">
								<!-- begin content -->
								<?php
								include getPlugin('/daily-summary/daily-summary_content.php');
								?>
								<span class="clear"></span>
							</div>
						</div>
					</div>
				</div>
				<div class="sidebar">
					<div id="rightsidebar">
						<?php
						if (hasNextImage()) {
							?>
							<div id="nextalbum" class="slides">
								<a href="<?php echo html_encode(getNextImageURL()); ?>" title="<?php echo gettext('Next image'); ?>">
									<h2><?php echo gettext('Next »'); ?></h2>
									<img src="<?php echo html_encode(getNextImageThumb()); ?>" />
								</a>
							</div>
							<?php
						}
						if (hasPrevImage()) {
							?>
							<div id="prevalbum" class="slides">
								<a href="<?php echo html_encode(getPrevImageURL()); ?>" title="<?php echo gettext('Previous image'); ?>">
									<h2><?php echo gettext('« Previous'); ?></h2>
									<img src="<?php echo html_encode(getPrevImageThumb()); ?>" />
								</a>
							</div>
							<?php
						}
						?>
						<p><?php printImageDesc(); ?></p>
						<?php printTags('links', gettext('Tags: '), NULL, ''); ?>
						<?php
						if (getImageMetaData()) {
							printImageMetadata(NULL, 'colorbox');
							?>
							<br class="clearall">
							<?php
						}
						if (simplemap::mapPlugin()) {
							simpleMap::setMapDisplay('colorbox');
							?>
							<span id="map_link">
								<?php simpleMap::printMap(); ?>
							</span>
							<br class="clearall">
							<?php
						}
						?>
					</div>
				</div>
				<span class="clear"></span> </div>
			<!-- /container -->
		</div>
		<?php
		npgFilters::apply('theme_body_close');
		?>
	</body>
</html>
