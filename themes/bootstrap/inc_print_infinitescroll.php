		<?php
		if (isAlbumPage()) {
			$containerClass = '.album-wrap';
			$itemClass = '.album-thumb';
			$viewMoreText = gettext('View more albums');
			$noMoreText = gettext('No more albums to display');
		} else if (isImagePage()) {
			$containerClass = '.image-wrap';
			$itemClass = '.image-thumb';
			$viewMoreText = gettext('View more images');
			$noMoreText = gettext('No more images to display');
		}
		
?>

		<?php if (hasNextPage()) { ?>
		<div class="infinite-pagination">
			<?php printNextPageURL(gettext('Next page'), NULL, 'infinite-next-page'); ?>
		</div>

		<div class="margin-bottom-double view-more">
			<button class="btn btn-default center-block"><?php echo $viewMoreText; ?></button>
		</div>

		<div class="page-load-status margin-top-double margin-bottom-double">
			<div class="loader-ellips infinite-scroll-request">
				<span class="loader-ellips-dot"></span>
				<span class="loader-ellips-dot"></span>
				<span class="loader-ellips-dot"></span>
				<span class="loader-ellips-dot"></span>
			</div>
			<div class="infinite-scroll-last infinite-scroll-error"><?php echo $noMoreText; ?></div>
		</div>
	<?php
	scriptLoader($_themeroot . '/js/infinite-scroll.pkgd.min.js');
	?>
		<script type="text/javascript">
		//<![CDATA[
			var $container = $('<?php echo $containerClass; ?>');
			var $pageLoadStatus = $('.page-load-status');
			var $viewMoreButton = $('.view-more');

			$(document).ready( function() {

				$container.infiniteScroll({
					path : '.infinite-next-page',
					append : '<?php echo $itemClass; ?>',
					hideNav : '.infinite-pagination',
					status : '.page-load-status',
					loadOnScroll : false,
				});

				$container.on( 'append.infiniteScroll', function( event, response, path, newElements ) {
					var $newElems = $(newElements).css({ opacity: 0 });
					$newElems.imagesLoaded( function() {
						$('.flag_thumbnail').removeAttr('style');
						$newElems.animate({ opacity: 1 });
					});
				});

				$viewMoreButton.on( 'click', function() {
					$container.infiniteScroll('loadNextPage');
					$container.infiniteScroll('option', {
						loadOnScroll : true,
					});
					$viewMoreButton.remove();
				});

				$container.on( 'last.infiniteScroll', function( event, response, path ) {
					$pageLoadStatus.animate({ opacity: 0 }, 5000);
				});

			});
		//]]>
		</script>
		<?php } ?>