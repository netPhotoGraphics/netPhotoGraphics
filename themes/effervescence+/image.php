<?php
// force UTF-8 Ø
if (!defined('WEBPATH'))
	die();
?>
<!DOCTYPE html>
<html<?php i18n::htmlLanguageCode(); ?>>
	<head>

		<?php npgFilters::apply('theme_head'); ?>

		<?php if (npgFilters::has_filter('theme_head', 'colorbox::css')) { ?>
			<script type="text/javascript">
				// <!-- <![CDATA[
				window.addEventListener('load', function () {
					$(".colorbox").colorbox({
						inline: true,
						href: "#imagemetadata",
						close: '<?php echo gettext("close"); ?>'
					});
	<?php
	$disposal = getOption('protect_full_image');
	if ($disposal != 'Download') {
		?>
						$("a.thickbox").colorbox({
							maxWidth: "98%",
							maxHeight: "98%",
							photo: true,
							close: '<?php echo gettext("close"); ?>',
							onComplete: function () {
								$(window).resize(resizeColorBoxImage);
							}
						});
		<?php
	}
	?>
				}, false);
				// ]]> -->
			</script>
		<?php } ?>
		<?php if (class_exists('RSS')) printRSSHeaderLink('Gallery', 'Gallery RSS'); ?>
	</head>

	<body onload="blurAnchors()">
		<?php npgFilters::apply('theme_body_open'); ?>

		<!-- Wrap Everything -->
		<div id="main4">
			<div id="main2">

				<!-- Wrap Header -->
				<div id="galleryheader">
					<div id="gallerytitle">

						<!-- Image Navigation -->
						<div class="imgnav">
							<div class="imgprevious">
								<?php
								global $_current_image;
								if (hasPrevImage()) {
									$image = $_current_image->getPrevImage();
									echo '<a href="' . html_encode(getPrevImageURL()) . '" title="' . html_encode($image->getTitle()) . '">« ' . gettext('prev') . '</a>';
								} else {
									echo '<div class="imgdisabledlink">« ' . gettext('prev') . '</div>';
								}
								?>
							</div>
							<div class="imgnext">
								<?php
								if (hasNextImage()) {
									$image = $_current_image->getNextImage();
									echo '<a href="' . html_encode(getNextImageURL()) . '" title="' . html_encode($image->getTitle()) . '">' . gettext('next') . ' »</a>';
								} else {
									echo '<div class="imgdisabledlink">' . gettext('next') . ' »</div>';
								}
								?>
							</div>
						</div>

						<!-- Logo -->
						<div id="logo2">
							<?php printLogo(); ?>
						</div>
					</div>

					<!-- Crumb Trail Navigation -->
					<div id="wrapnav">
						<div id="navbar">
							<span>
								<?php printHomeLink('', ' | '); ?>
								<?php
								if (getOption('gallery_index')) {
									?>
									<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Main Index'); ?>"><?php printGalleryTitle(); ?></a> |
									<a href="<?php echo html_encode(getCustomPageURL('gallery')); ?>" title="<?php echo gettext('Albums Index'); ?>"><?php echo gettext('Gallery'); ?></a>
									<?php
								} else {
									?>
									<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Albums Index'); ?>"><?php printGalleryTitle(); ?></a>
									<?php
								}
								?></a> |
								<?php
								printParentBreadcrumb();
								printAlbumBreadcrumb("", " | ");
								?>
							</span>
							<?php printImageTitle(); ?>
						</div>
					</div>
				</div>

				<!-- The Image -->
				<?php
				$size_a = getSizeDefaultImage();
				$s = $size_a[0] + 22;
				$wide = " style=\"width:" . $s . "px;";
				$s = $size_a[1] + 72;
				$high = " height:" . $s . "px;\"";
				?>
				<div id="image" <?php echo $wide . $high; ?>>
					<?php
					if ($_current_image->isPhoto()) {
						$fullimage = getFullImageURL();
						$imgclass = 'photo';
					} else {
						$fullimage = NULL;
						$imgclass = 'video';
					}
					?>
					<div id="image_container" class="<?php echo $imgclass; ?>">
						<?php
						if ($_current_image->isPhoto()) {
							$fullimage = getFullImageURL();
						} else {
							$fullimage = NULL;
						}
						if (!empty($fullimage)) {
							?>
							<a href="<?php echo html_encode($fullimage); ?>" title="<?php printBareImageTitle(); ?>" class="thickbox">
								<?php
							}
							printDefaultSizedImage(getImageTitle());
							if (!empty($fullimage)) {
								?>
							</a>
							<?php
						}
						?>
					</div>
				</div>
				<br class="clearall" />
			</div>

			<!-- Image Description -->

			<div id="description">
				<p><?php printImageDesc(); ?></p>
				<?php
				if (function_exists('printRating'))
					printRating();
				If (function_exists('printAddToFavorites'))
					printAddToFavorites($_current_image);
				if (simpleMap::mapPlugin()) {
					?>
					<div id="map_link">
	<?php simpleMap::printMap(); ?>
					</div>
					<br class="clearall" />
					<?php
				}
				if (getImageMetaData()) {
					printImageMetadata(NULL, 'colorbox');
				}
				?>
			</div>

		</div>

		<!-- Wrap Bottom Content -->
		<?php
		commonComment();
		printFooter();
		?>

	</body>
<?php npgFilters::apply('theme_body_close'); ?>
</html>
