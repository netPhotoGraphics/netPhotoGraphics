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

		if (npgFilters::has_filter('theme_head', 'colorbox::css')) {
			?>
			<script type="text/javascript">
				// <!-- <![CDATA[
				window.addEventListener('load', function () {
					$(".colorbox").colorbox({
						inline: true,
						href: "#imagemetadata",
						close: '<?php echo gettext("close"); ?>'
					});
					$("a.thickbox").colorbox({
						maxWidth: "98%",
						maxHeight: "98%",
						photo: true,
						close: '<?php echo gettext("close"); ?>',
						onComplete: function () {
							$(window).resize(resizeColorBoxImage);
						}
					});
				}, false);
				// ]]> -->
			</script>
		<?php } ?>
		<?php if (class_exists('RSS')) printRSSHeaderLink('Album', getAlbumTitle()); ?>
	</head>
	<body>
		<?php npgFilters::apply('theme_body_open'); ?>

		<div id="main">
			<div id="header">
				<h1><?php printGalleryTitle(); ?></h1>
				<div class="imgnav">
					<?php if (hasPrevImage()) { ?>
						<div class="imgprevious"><a href="<?php echo html_encode(getPrevImageURL()); ?>" title="<?php echo gettext("Previous Image"); ?>">« <?php echo gettext("prev"); ?></a></div>
					<?php } if (hasNextImage()) { ?>
						<div class="imgnext"><a href="<?php echo html_encode(getNextImageURL()); ?>" title="<?php echo gettext("Next Image"); ?>"><?php echo gettext("next"); ?> »</a></div>
					<?php } ?>
				</div>
			</div>

			<div id="content">

				<div id="breadcrumb">
					<h2><a href="<?php echo getGalleryIndexURL(); ?>" title="<?php echo gettext('Index'); ?>"><?php echo gettext("Index"); ?></a> » <?php
						printParentBreadcrumb("", " » ", " » ");
						printAlbumBreadcrumb("  ", " » ");
						?>
						<strong><?php printImageTitle(); ?></strong> (<?php echo imageNumber() . "/" . getNumImages(); ?>)
					</h2>
				</div>
				<div id="content-left">

					<!-- The Image -->
					<?php
					//
					if (function_exists('printThumbNav')) {
						printThumbNav(3, 6, 50, 50, 50, 50, FALSE);
					} else if (function_exists('printPagedThumbsNav')) {
						printPagedThumbsNav(6, FALSE, gettext('« prev thumbs'), gettext('next thumbs »'), 40, 40);
					}
					?>

					<div id="image">
						<?php
						if (getOption("Use_thickbox") && !isImageVideo()) {
							$boxclass = " class=\"thickbox\"";
						} else {
							$boxclass = "";
						}
						if (isImagePhoto()) {
							$tburl = getFullImageURL();
						} else {
							$tburl = NULL;
						}
						if (!empty($tburl)) {
							?>
							<a href="<?php echo html_encode($tburl); ?>"<?php echo $boxclass; ?> title="<?php printBareImageTitle(); ?>">
								<?php
							}
							printCustomSizedImageMaxSpace(getBareImageTitle(), 580, 580);
							?>
							<?php
							if (!empty($tburl)) {
								?>
							</a>
							<?php
						}
						?>
					</div>
					<div id="narrow">
						<div id="imagedesc"><?php printImageDesc(); ?></div>
						<?php
						if (getTags()) {
							echo gettext('<strong>Tags:</strong>');
						} printTags('links', '', 'taglist', ', ');
						?>
						<br style="clear:both;" /><br />
						<?php
						if (function_exists('printSlideShowLink') && isImagePhoto()) {
							echo '<span id="slideshowlink">';
							printSlideShowLink();
							echo '</span>';
						}
						?>

						<?php
						if (getImageMetaData()) {
							printImageMetadata(NULL, 'colorbox');
						}
						?>

						<br style="clear:both" />
						<?php
						If (function_exists('printAddToFavorites'))
							printAddToFavorites($_current_image);
						@call_user_func('printRating');
						simpleMap::printMap();
						?>

					</div>
					<?php @call_user_func('printCommentForm'); ?>

				</div><!-- content-left -->

				<div id="sidebar">
					<?php include("sidebar.php"); ?>
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
