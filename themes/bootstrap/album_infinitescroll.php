<?php
if ((getNumAlbums() > 0) && (getNumImages() > 0)) {
	include('album_standard.php');
} else {
	include('includes/header.php');
	?>

	<!-- .container main -->
	<!-- .page-header -->
	<!-- .header -->
	<h3><?php printGalleryTitle(); ?></h3>
	</div><!-- .header -->
	</div><!-- /.page-header -->

	<div class="breadcrumb">
		<h4>
			<?php printGalleryIndexURL(' » ', getGalleryTitle(), false); ?><?php printParentBreadcrumb('', ' » ', ' » '); ?><?php printAlbumTitle(); ?>
		</h4>
	</div>

	<div class="page-header margin-bottom-reset">
		<?php printAlbumDesc(); ?>
	</div>

	<?php
	if (isAlbumPage()) {
		include('includes/print_album_thumb.php');
	}
	if (isImagePage()) {
		include('includes/print_image_thumb.php');
	}

	include('includes/print_infinitescroll.php');
	?>

	<?php if ((npg_loggedin()) && (extensionEnabled('favoritesHandler'))) { ?>
		<div class="favorites panel-group" role="tablist">
			<?php printAddToFavorites($_current_album); ?>
		</div>
	<?php } ?>

	<?php
	switch (simplemap::mapPlugin()) {
		case 'googleMap':
			include('includes/print_googlemap.php');
			break;
		case 'openStreetMap':
			include('includes/print_osm.php');
			break;
	}

	if (extensionEnabled('comment_form')) {
		include('includes/print_comment.php');
	}
	?>

	</div><!-- /.container main -->

	<?php
	include('includes/footer.php');
}
?>