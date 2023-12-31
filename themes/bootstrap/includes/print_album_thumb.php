<div class="row album-wrap margin-bottom-double">
	<?php while (next_album()) { ?>
		<div class="col-sm-4 album-thumb">
			<a class="thumb" href="<?php echo html_encode(getAlbumURL()); ?>" title="<?php echo html_encode(getBareAlbumTitle()); ?>">
				<?php printCustomAlbumThumbImage(getBareAlbumTitle(), array('width' => getOption('zpB_album_thumb_width'), 'height' => getOption('zpB_album_thumb_height'), 'cw' => getOption('zpB_album_thumb_width'), 'ch' => getOption('zpB_album_thumb_height')), 'remove-attributes img-responsive'); ?>
			</a>
			<a href="<?php echo html_encode(getAlbumURL()); ?>" title="<?php echo html_encode(getBareAlbumTitle()); ?>">
				<h5><?php printBareAlbumTitle(); ?></h5>
			</a>
		</div>
	<?php } ?>
</div>