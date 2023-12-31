<?php include('includes/header.php'); ?>

<!-- .container -->
<!-- .page-header -->
<!-- .header -->
<h3><?php printGalleryTitle(); ?></h3>
</div><!-- .header -->
</div><!-- /.page-header -->

<div class="breadcrumb">
	<h4>
		<?php printGalleryIndexURL(' » ', getGalleryTitle(), false); ?><?php printParentBreadcrumb('', ' » ', ' » '); ?><?php printAlbumBreadcrumb('', ' » '); ?><?php printBareImageTitle(); ?>
	</h4>
</div>

<nav class="nav_photo">
	<ul class="pager">
		<?php if (hasPrevImage()) { ?>
			<li><a href="<?php echo html_encode(getPrevImageURL()); ?>" title="<?php echo gettext('Previous Image'); ?>">&larr; <?php echo gettext('prev'); ?></a></li>
		<?php } else { ?>
			<li class="disabled"><a href="#">&larr; <?php echo gettext('prev'); ?></a></li>
		<?php } ?>

		<?php if (hasNextImage()) { ?>
			<li><a href="<?php echo html_encode(getNextImageURL()); ?>" title="<?php echo gettext('Next Image'); ?>"><?php echo gettext('next'); ?> &rarr;</a></li>
		<?php } else { ?>
			<li class="disabled"><a href="#"><?php echo gettext('next'); ?> &rarr;</a></li>
		<?php } ?>
	</ul>
</nav>

<?php
if ($_current_image->isPhoto() || $_current_image->isVideo()) {
	$frame = 'img-responsive';
} else {
	$frame = 'hundered_percent';
}
printDefaultSizedImage(getBareImageTitle(), 'remove-attributes center-block ' . $frame);
?>

<div class="photo-description row">
	<div class="col-sm-offset-2 col-sm-8">
		<h4>
			<?php printBareImageTitle(); ?>
			<?php if ((getOption('zpB_show_exif')) && (getImageMetaData())) { ?>
				<a href="#" data-toggle="modal" data-target="#exif_data"><span class="glyphicon glyphicon-info-sign"></span></a>
			<?php } ?>
		</h4>
	</div>
	<div class="col-sm-offset-2 col-sm-8">
		<?php printImageDesc(); ?>
	</div>

	<?php if ((getOption('zpB_show_exif')) && (getImageMetaData())) { ?>
		<div id="exif_data" class="modal" tabindex="-1" role="dialog">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-body">
						<?php printImageMetadata(NULL, false); ?>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo gettext('close'); ?></button>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>

	<?php if ((getOption('zpB_show_tags')) && (getTags())) { ?>
		<div class="col-sm-offset-2 col-sm-8">
			<?php printTags('links', NULL, 'nav nav-pills', NULL); ?>
		</div>
	<?php } ?>
</div>

<?php if ((npg_loggedin()) && (extensionEnabled('favoritesHandler'))) { ?>
	<div class="row">
		<div class="col-sm-offset-2 col-sm-8 photo-infos favorites">
			<?php printAddToFavorites($_current_image); ?>
		</div>
	</div>
<?php } ?>

<?php if (extensionEnabled('rating')) { ?>
	<div class="row">
		<div class="col-sm-offset-2 col-sm-8 photo-infos rating">
			<?php printRating(); ?>
		</div>
	</div>
<?php } ?>

<?php if (extensionEnabled('comment_form')) { ?>
	<?php include('includes/print_comment.php'); ?>
<?php } ?>

</div><!-- /.container main -->

<?php
include('includes/footer.php');
