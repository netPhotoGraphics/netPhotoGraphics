<div id="album-wrap" class="clearfix">
	<ul>
		<?php while (next_album()) { ?>
			<li>
				<a class="album-thumb" href="<?php echo html_encode(getAlbumURL()); ?>" title="<?php echo gettext('View album:'); ?> <?php echo getBareAlbumTitle(); ?>"><?php printCustomAlbumThumbImage(getBareAlbumTitle(), array('width' => getOption('personnal_thumb_width'), 'height' => getOption('personnal_thumb_height'), 'cw' => getOption('personnal_thumb_width'), 'ch' => getOption('personnal_thumb_height'))); ?></a>
				<h4><a href="<?php echo html_encode(getAlbumURL()); ?>" title="<?php echo gettext('View album:'); ?> <?php echo getBareAlbumTitle(); ?>"><?php printAlbumTitle(); ?></a></h4>
			</li>
		<?php } ?>
	</ul>
</div>