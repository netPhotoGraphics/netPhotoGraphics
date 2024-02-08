<?php
if (!defined('WEBPATH'))
	die();
?>
<!DOCTYPE html>
<html<?php i18n::htmlLanguageCode(); ?>>
	<head>

		<?php
		npgFilters::apply('theme_head');

		scriptLoader($_themeroot . '/garland.css');

		if (npgFilters::has_filter('theme_head', 'colorbox::css')) {
			?>
			<script>
				
				$(document).ready(function () {
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
				});
				
			</script>
		<?php } ?>
		<?php if (class_exists('RSS')) printRSSHeaderLink('Album', gettext('Gallery')); ?>
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
								<div class="main section" id="main">
									<h2 id="gallerytitle">
										<?php printHomeLink('', ' » '); ?>
										<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Gallery Index'); ?>"><?php printGalleryTitle(); ?></a> »
										<?php
										printParentBreadcrumb("", " » ", " » ");
										printAlbumBreadcrumb("  ", " » ");
										?>
										<?php printImageTitle(); ?>
									</h2>
									<?php printCodeblock(1); ?>
									<div id="image_container">
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
											printCustomSizedImage(getImageTitle(), array('width' => 520));
											if (!empty($fullimage)) {
												?>
											</a>
											<?php
										}
										?>
									</div>
									<?php
									If (function_exists('printAddToFavorites'))
										printAddToFavorites($_current_image);
									if (function_exists('printRating'))
										printRating();
									if (function_exists('printCommentForm'))
										printCommentForm();
									printCodeblock(2);
									footer();
									?>
									<p style="clear: both;"></p>
								</div>
								<!-- end content -->
								<span class="clear"></span> </div>
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
									<?php
									$html = '<img src="' . html_encode(getNextImageThumb()) . '" />';
									$html = npgFilters::apply('standard_album_thumb_html', $html);
									if (ENCODING_FALLBACK) {
										$html = "<picture>\n<source srcset=\"" . html_encode(getNextImageThumb(FALLBACK_SUFFIX)) . "\">\n" . $html . "</picture>\n";
									}
									echo $html;
									?>
								</a>
							</div>
							<?php
						}
						if (hasPrevImage()) {
							?>
							<div id="prevalbum" class="slides">
								<a href="<?php echo html_encode(getPrevImageURL()); ?>" title="<?php echo gettext('Previous image'); ?>">
									<h2><?php echo gettext('« Previous'); ?></h2>
									<?php
									$html = '<img src="' . html_encode(getPrevImageThumb()) . '" />';
									$html = npgFilters::apply('standard_album_thumb_html', $html);
									if (ENCODING_FALLBACK) {
										$html = "<picture>\n<source srcset=\"" . html_encode(getPrevImageThumb(FALLBACK_SUFFIX)) . "\">\n" . $html . "</picture>\n";
									}
									echo $html;
									?>
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
							<br class="clearall" />
							<?php
						}
						if (simplemap::mapPlugin()) {
							simpleMap::setMapDisplay('colorbox');
							?>
							<span id="map_link">
							<?php simplemap::printMap(); ?>
							</span>
							<br class="clearall" />
							<?php
						}
						?>
					</div>
				</div>
				<span class="clear"></span> </div>
			<!-- /container -->
		</div>
	</body>
<?php npgFilters::apply('theme_body_close'); ?>
</html>
