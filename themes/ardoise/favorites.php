<?php 
if (extensionEnabled('favoritesHandler')) {
	include ('includes/header.php');
?>

		<div id="headline" class="clearfix">
			<h3><?php printAlbumTitle(); ?></h3>
			<div class="headline-text"><?php printAlbumDesc(); ?></div>
		</div>

		<?php if (function_exists('printSlideShowLink')) { ?>
		<div class="control-nav">
			<div class="control-slide">
				<?php printSlideShowLink(gettext('Slideshow')); ?>
			</div>
		</div>
		<?php } ?>

		<div>
			<div class="pagination-nogal clearfix">
				<?php printPageListWithNav(' « ', ' » ', false, true, 'clearfix', NULL, true, 7); ?>
			</div>

			<?php
			if (getNumAlbums() > 0) {
				include('includes/print_album_thumb.php');
			}
			if (getNumImages() > 0) {
				include('includes/print_image_thumb.php');
			}
			?>

			<div class="pagination-nogal clearfix">
				<?php printPageListWithNav(' « ', ' » ', false, true, 'clearfix', NULL, true, 7); ?>
			</div>

		</div>

<?php
	include('includes/footer.php');

} else {
	include(CORE_SERVERPATH . '404.php');
} ?>