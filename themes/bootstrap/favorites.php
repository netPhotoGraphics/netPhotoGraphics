<?php 
if (extensionEnabled('favoritesHandler')) {
	include ('includes/header.php');
?>

	<!-- .container main -->
		<!-- .page-header -->
			<!-- .header -->
				<h3><?php printGalleryTitle(); ?></h3>
			</div><!-- .header -->
		</div><!-- /.page-header -->

		<div class="breadcrumb">
			<h4>
				<?php printCustomPageURL(getGalleryTitle(), 'gallery', '', '', ' » '); ?><?php printAlbumTitle(); ?>
			</h4>
		</div>

		<div class="page-header bottom-margin-reset">
			<p><?php printAlbumDesc(); ?></p>
		</div>

		<?php
		printPageListWithNav('«', '»', false, true, 'pagination pagination-sm', NULL, true, 7);

		if (isAlbumPage()) {
			include('includes/print_album_thumb.php');
		}

		if (isImagePage()) {
			include('includes/print_image_thumb.php');
		}

		printPageListWithNav('«', '»', false, true, 'pagination pagination-sm', NULL, true, 7);
		?>

	</div><!-- /.container main -->

<?php
	include('includes/footer.php');
} else {
	include(CORE_SERVERPATH . '404.php');
}
