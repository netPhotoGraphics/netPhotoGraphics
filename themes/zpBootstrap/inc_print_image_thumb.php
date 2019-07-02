<div class="row image-wrap margin-bottom-double">
	<?php while (next_image()) { ?>
		<?php
		$fullimage = getFullImageURL();
		$isImagePhoto = isImagePhoto($_current_image);
		$isImageVideo = isImageVideo($_current_image);
		$isAudio = false;
		if ($isImageVideo) {
			$suffix = strtolower(getSuffix($_current_image->getFileName()));
			if ($suffix == 'mp3' || $suffix == 'm4a') {
				$isAudio = true;
			}
		}
		$objectclass = strtolower(get_class($_current_image));
		$isTextObject = false;
		if ($objectclass == 'textobject') {
			$isTextObject = true;
			$isEmbedVideo = false;
			$link = zpB_getLink($_current_image->getContent());
			if ($link) {
				$isEmbedVideo = true;
			}
		}
		?>
		<?php if (!empty($fullimage)) { ?>
			<div class="col-xs-6 col-sm-3 image-thumb">
				<?php
				// image file
				if ($isImagePhoto) {
					?>
					<a class="thumb" href="<?php echo html_encode($fullimage); ?>" title="<?php echo html_encode(getBareImageTitle()); ?>" data-fancybox="images">
			<?php printImageThumb(getBareImageTitle(), 'remove-attributes img-responsive'); ?>
						<div class="hide caption">
							<h4><?php printBareImageTitle(); ?></h4>
			<?php echo printImageDesc(); ?>
						</div>
					</a>
					<?php
					// audio file
				} else if ($isImageVideo && $isAudio) {
					?>
					<a class="thumb" href="javascript:;" data-type="iframe" data-src="<?php echo html_encode($fullimage); ?>" title="<?php echo html_encode(getBareImageTitle()); ?>" data-fancybox="images">
							<?php printImageThumb(getBareImageTitle(), 'remove-attributes img-responsive'); ?>
						<div class="hide caption">
							<h4><?php printBareImageTitle(); ?></h4>
					<?php echo printImageDesc(); ?>
						</div>
					</a>
					<?php
					// video file
				} else if ($isImageVideo && !$isAudio) {
					?>
					<a class="thumb" href="<?php echo html_encode($fullimage); ?>" title="<?php echo html_encode(getBareImageTitle()); ?>" data-fancybox="images">
						<?php printImageThumb(getBareImageTitle(), 'remove-attributes img-responsive'); ?>
					</a>
					<div class="hide caption">
						<h4><?php printBareImageTitle(); ?></h4>
					<?php echo printImageDesc(); ?>
					</div>
						<?php
						// embed online video (hack of textobject)
					} else if ($isTextObject && $isEmbedVideo) {
						?>
					<a class="thumb" href="<?php echo html_encode($link); ?>" title="<?php echo html_encode(getBareImageTitle()); ?>" data-fancybox="images">
			<?php printImageThumb(getBareImageTitle(), 'remove-attributes img-responsive'); ?>
						<div class="hide caption">
							<h4><?php printBareImageTitle(); ?></h4>
					<?php echo printImageDesc(); ?>
						</div>
					</a>
						<?php
						// txt, htm or html content
					} else if ($isTextObject && !$isEmbedVideo) {
						?>
					<a class="thumb" href="javascript:;" data-src="#item<?php echo $_current_image->getIndex(); ?>" title="<?php echo html_encode(getBareImageTitle()); ?>" data-fancybox="images">
			<?php printImageThumb(getBareImageTitle(), 'remove-attributes img-responsive'); ?>
						<div class="hide caption">
							<h4><?php printBareImageTitle(); ?></h4>
			<?php echo printImageDesc(); ?>
						</div>
					</a>
					<div style="display: none;" id="item<?php echo $_current_image->getIndex(); ?>">
					<?php echo $_current_image->getContent(); ?>
					</div>

						<?php
						// other media object not displayed in fancybox
					} else {
						?>
					<a class="thumb" href="<?php echo html_encode($fullimage); ?>" title="<?php echo html_encode(getBareImageTitle()); ?>">
			<?php printImageThumb(getBareImageTitle(), 'remove-attributes img-responsive'); ?>
						<h4><?php printBareImageTitle(); ?></h4>
			<?php echo printImageDesc(); ?>
				</div>
			</a>
		<?php } ?>

		<a href="<?php echo html_encode(getImageURL()); ?>" title="<?php echo html_encode(getBareImageTitle()); ?>">
			<h5><?php printBareImageTitle(); ?></h5>
		</a>
		</div>
	<?php } ?>
<?php } ?>
</div>