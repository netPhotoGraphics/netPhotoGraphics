		<?php if (zpB_hasNextNewsPage()) { ?>
		<div class="infinite-pagination">
			<?php zpB_printNextNewsPageURL(gettext('Next page'), 'infinite-next-page'); ?>
		</div>

		<div class="margin-top-double margin-bottom-double view-more">
			<button class="btn btn-default center-block"><?php echo gettext('View more news'); ?></button>
		</div>

		<div class="page-load-status margin-top-double margin-bottom-double">
			<div class="loader-ellips infinite-scroll-request">
				<span class="loader-ellips-dot"></span>
				<span class="loader-ellips-dot"></span>
				<span class="loader-ellips-dot"></span>
				<span class="loader-ellips-dot"></span>
			</div>
			<div class="infinite-scroll-last infinite-scroll-error"><?php echo gettext('No more news to display'); ?></div>
		</div>

	<?php
	scriptLoader($_themeroot . '/js/infinite-scroll.pkgd.min.js');
	?>
		<script type="text/javascript">
		//<![CDATA[
			var $container = $('.news-wrap');
			var $pageLoadStatus = $('.page-load-status');
			var $viewMoreButton = $('.view-more');
			$('.infinite-pagination a').addClass('infinite-next-page');

			$(document).ready( function() {

				$container.infiniteScroll({
					path : '.infinite-next-page',
					append : '.list-post',
					hideNav : '.infinite-pagination',
					status : '.page-load-status',
					loadOnScroll : false,
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