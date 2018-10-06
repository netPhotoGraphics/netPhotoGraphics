<div class="row image-wrap margin-bottom-double">
	<?php
	while (next_image()) {
		?>
		<?php $fullimage = getFullImageURL(); ?>
		<?php if (!empty($fullimage)) { ?>
			<div class="col-xs-6 col-sm-3 image-thumb">
				<a class="thumb" href="<?php echo html_encode(pathurlencode($fullimage)); ?>" title="<?php echo html_encode(getBareImageTitle()); ?>" data-fancybox="images">
					<?php printImageThumb(getBareImageTitle(), 'remove-attributes img-responsive'); ?>
					<div class="hide caption">
						<h4><?php printBareImageTitle(); ?></h4>
						<?php echo printImageDesc(); ?>
					</div>
				</a>
				<a href="<?php echo html_encode(getImageURL()); ?>" title="<?php echo html_encode(getBareImageTitle()); ?>">
					<h5><?php printBareImageTitle(); ?></h5>
				</a>
			</div>
		<?php } ?>
	<?php } ?>
</div>