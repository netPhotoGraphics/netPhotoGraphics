<?php include('inc_header.php'); ?>

	<!-- .container -->
		<!-- .page-header -->
			<!-- .header -->
				<h3><?php printGalleryTitle(); ?></h3>
			</div><!-- .header -->
		</div><!-- /.page-header -->

		<div class="slider">
			<?php
			$album_filename = getOption('zpB_homepage_album_filename');
			if (empty($album_filename)) {
				$option = 'all';
			} else {
				$option = 'album';
			}

			$number = getOption('zpB_homepage_random_pictures');
			if (empty($number)) {
				$number = 5;
			}

			$slides = zpB_getRandomImages($number, $option, $album_filename);
			?>
			<div class="flexslider">
			<?php if (!empty($slides)) { ?>
			<ul class="slides">
				<?php foreach($slides as $slide) {
					makeImageCurrent($slide);
					?>
					<li>
						<a href="<?php echo html_encode(getCustomPageURL('gallery')); ?>" title="<?php html_encode(gettext('Gallery')); ?>">
							<?php printCustomSizedImage(gettext('Gallery'), NULL, 1000, 500, 1000, 500, NULL, NULL, 'remove-attributes img-responsive'); ?>
						</a>
					</li>
				<?php } ?>
			</ul>
			<?php } else { ?>
				<img class="img-responsive" src="http://via.placeholder.com/1000x500?text=<?php echo gettext('Slideshow'); ?> (1000 x 500)">
			<?php } ?>
			</div>
		</div>

		<div class="row site-description">
			<?php
			$_latest_news_homepage = ($_zenpage_news_enabled) && (getNumNews() > 0) && (getOption('zpB_latest_news_homepage'));
			if ($_latest_news_homepage) {
				$col_sd = 'col-sm-offset-1 col-sm-6';
			} else {
				$col_sd = 'col-sm-offset-1 col-sm-10';
			}
			?>
			<div class="<?php echo $col_sd; ?>">
				<h3><?php echo gettext('Home'); ?></h3>
				<div><?php printGalleryDesc(); ?></div>
			</div>
			<?php if ($_latest_news_homepage) { ?>
			<div class="col-sm-5">
			<h3><?php echo NEWS_LABEL; ?></h3>
				<?php printLatestNews( 1, '', true, true, 200, false); ?>
			</div>
			<?php } ?>
		</div>

	</div><!-- /.container main -->

<?php include('inc_footer.php'); ?>