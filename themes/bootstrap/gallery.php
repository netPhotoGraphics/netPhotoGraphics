<?php include('inc_header.php'); ?>

	<!-- .container -->
		<!-- .page-header -->
			<!-- .header -->
				<h3><?php printGalleryTitle(); ?></h3>
			</div><!-- .header -->
		</div><!-- /.page-header -->

		<div class="breadcrumb">
			<h4>
				<?php printGalleryIndexURL(' » ', getGalleryTitle(), false); ?>
			</h4>
		</div>

		<?php if (!getOption('zpB_homepage')) { ?>
		<div class="page-header top-margin-reset bottom-margin-reset">
			<p><?php printGalleryDesc(); ?></p>
		</div>
		<?php } ?>

		<?php
		if (!getOption('zpB_use_infinitescroll_gallery')) {
			printPageListWithNav('«', '»', false, true, 'pagination pagination-sm', NULL, true, 7);
		}

		if (isAlbumPage()) {
			include('inc_print_album_thumb.php');
		}

		if (!getOption('zpB_use_infinitescroll_gallery')) {
			printPageListWithNav('«', '»', false, true, 'pagination pagination-sm top-margin-reset', NULL, true, 7);
		} else {
			include('inc_print_infinitescroll.php');
		}
		?>

	</div><!-- /.container main -->

<?php include('inc_footer.php'); ?>