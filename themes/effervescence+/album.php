<?php
// force UTF-8 Ø

if (!defined('WEBPATH'))
	die();

$map = simpleMap::mapPlugin();
?>
<!DOCTYPE html>
<html<?php i18n::htmlLanguageCode(); ?>>
	<head>

		<?php npgFilters::apply('theme_head'); ?>

		<?php $handler->theme_head($_themeroot); ?>
	</head>

	<body onload="blurAnchors()">
		<?php npgFilters::apply('theme_body_open'); ?>
		<?php $handler->theme_bodyopen($_themeroot); ?>

		<!-- Wrap Header -->
		<div id="header">
			<div id="gallerytitle">

				<!-- Subalbum Navigation -->
				<div class="albnav">
					<div class="albprevious">
						<?php
						$album = getPrevAlbum();
						if (is_null($album)) {
							echo '<div class="albdisabledlink">«  ' . gettext('prev') . '</div>';
						} else {
							echo '<a href="' . $album->getLink() .
							'" title="' . html_encode($album->getTitle()) . '">« ' . gettext('prev') . '</a>';
						}
						?>
					</div> <!-- albprevious -->
					<div class="albnext">
						<?php
						$album = getNextAlbum();
						if (is_null($album)) {
							echo '<div class="albdisabledlink">' . gettext('next') . ' »</div>';
						} else {
							echo '<a href="' . $album->getLink() .
							'" title="' . html_encode($album->getTitle()) . '">' . gettext('next') . ' »</a>';
						}
						?>
					</div><!-- albnext -->
					<?php
					if (getOption('Allow_search')) {
						$album_list = array('albums' => array($_current_album->name), 'pages' => '0', 'news' => '0');
						printSearchForm(NULL, 'search', $_themeroot . '/images/search.png', gettext('Search album'), NULL, NULL, $album_list);
					}
					?>
				</div> <!-- header -->

				<!-- Logo -->
				<div id="logo">
					<?php
					printLogo();
					?>
				</div>
			</div> <!-- gallerytitle -->

			<!-- Crumb Trail Navigation -->
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
							<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Albums Index'); ?>"><?php printGalleryTitle(); ?></a> |
							<?php
						}
						printParentBreadcrumb();
						?></span>
					<?php printBareAlbumTitle(25); ?>
				</div>
			</div> <!-- wrapnav -->

			<!-- Random Image -->
			<?php
			if (isAlbumPage()) {
				printHeadingImage(getRandomImagesAlbum(NULL, getOption('effervescence_daily_album_image')));
			}
			?>
		</div> <!-- header -->

		<!-- Wrap Subalbums -->
		<div id="subcontent">
			<div id="submain">

				<!-- Album Description -->
				<div id="description">
					<?php
					printAlbumDesc();
					?>
				</div>

				<!-- SubAlbum List -->

				<?php
				$firstAlbum = null;
				$lastAlbum = null;
				while (next_album()) {
					if (is_null($firstAlbum)) {
						$lastAlbum = albumNumber();
						$firstAlbum = $lastAlbum;
						?>
						<ul id="albums">
							<?php
						} else {
							$lastAlbum++;
						}
						?>
						<li>
							<?php $annotate = annotateAlbum(); ?>
							<div class="imagethumb">
								<a href="<?php echo html_encode(getAlbumURL()); ?>" title="<?php echo html_encode($annotate) ?>">
									<?php printCustomAlbumThumbImage($annotate, array('width' => ALBUM_THMB_WIDTH, 'cw' => ALBUM_THMB_WIDTH, 'ch' => ALBUM_THUMB_HEIGHT)); ?></a>
							</div>
							<h4>
								<a href="<?php echo html_encode(getAlbumURL()); ?>" title="<?php echo html_encode($annotate) ?>">
									<?php printAlbumTitle(); ?>
								</a>
							</h4>
						</li>
						<?php
					}
					if (!is_null($firstAlbum)) {
						?>
					</ul>
					<?php
				}
				?>

				<div class="clearage"></div>
				<?php printNofM('Album', $firstAlbum, $lastAlbum, getNumAlbums()); ?>
			</div> <!-- submain -->

			<!-- Wrap Main Body -->
			<?php
			if (getNumImages() > 0) { /* Only print if we have images. */
				$handler->theme_content($map);
			} else { /* no images to display */
				if (getNumAlbums() == 0) {
					?>
					<div id="main3">
						<div id="main2">
							<br />
							<p align="center"><?php echo gettext('Album is empty'); ?></p>
						</div>
					</div> <!-- main3 -->
					<?php
				} else {
					?>
					<div id="main">
						<?php if (function_exists('printAddToFavorites')) printAddToFavorites($_current_album); ?>
						<?php if (function_exists('printRating')) printRating(); ?>
					</div>
					<?php
				}
			}
			?>

			<!-- Page Numbers -->
			<div id="pagenumbers">
				<?php
				printPageListWithNav("« " . gettext('prev'), gettext('next') . " »");
				?>
			</div> <!-- pagenumbers -->
			<?php commonComment(); ?>
		</div> <!-- subcontent -->

		<!-- Footer -->
		<br style="clear:all" />

		<?php
		printFooter();
		?>

	</body>
	<?php npgFilters::apply('theme_body_close'); ?>
</html>
