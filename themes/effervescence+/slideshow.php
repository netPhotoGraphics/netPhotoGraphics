<?php
// force UTF-8 Ø
if (!defined('WEBPATH'))
	die();
if (function_exists('printSlideShow')) {
	?>
	<!DOCTYPE html>
	<html<?php i18n::htmlLanguageCode(); ?>>
		<head>

			<?php npgFilters::apply('theme_head'); ?>

		</head>
		<body>
			<?php npgFilters::apply('theme_body_open'); ?>
			<!-- Wrap Everything -->
			<div id="main4">
				<div id="main2">

					<!-- Wrap Header -->
					<div id="galleryheader">
						<div id="gallerytitle">
							<div id="logo2">
								<?php printLogo(); ?>
							</div>
						</div> <!-- gallery title -->

						<div id="wrapnav">
							<div id="navbar">
								<span><?php printHomeLink('', ' | '); ?>
									<?php
									if (getOption('gallery_index')) {
										?>
										<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Main Index'); ?>"><?php printGalleryTitle(); ?></a> |
										<a href="<?php echo html_encode(getCustomPageURL('gallery')); ?>" title="<?php echo gettext('Albums Index'); ?>"><?php echo gettext('Gallery'); ?></a> |
										<?php
									} else {
										?>
										<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Albums Index'); ?>"><?php printGalleryTitle(); ?></a>
										<?php
									}
									?></a> 
									<?php
									if (is_null($_current_album)) {
										$search = new SearchEngine();
										if ($params = getNPGCookie('search_params')) {
											$params = trim($params);
										}
										$search->setSearchParams($params);
										$images = $search->getImages(0);
										$searchwords = $search->getSearchWords();
										$searchdate = $search->getSearchDate();
										$searchfields = $search->getSearchFields(true);
										$page = $search->page;
										$returnpath = SearchEngine::getURL($searchwords, $searchdate, $searchfields, $page);
										echo '<a href=' . html_encode($returnpath) . '><em>' . gettext('Search') . '</em></a> | ';
									} else {
										printParentBreadcrumb();
										printAlbumBreadcrumb("", " | ");
									}
									?> </span>
								<?php echo gettext('Slideshow'); ?>
							</div> <!-- navbar -->
						</div> <!-- wrapnav -->
					</div> <!-- galleryheader -->
				</div> <!-- main2 -->
				<div id="content">
					<div id="main">
						<div id="slideshowpage">
							<?php printSlideShow(false, true); ?>
						</div>
					</div> <!-- main -->
				</div> <!-- content -->
			</div> <!-- main4 -->
			<br style="clear:all" />
			<!-- Footer -->
			<?php
			printFooter();
			npgFilters::apply('theme_body_close');
			?>
		</body>
	</html>
	<?php
} else {
	include(CORE_SERVERPATH . '404.php');
}
?>