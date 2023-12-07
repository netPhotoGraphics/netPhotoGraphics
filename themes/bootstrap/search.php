<?php include('includes/header.php'); ?>

<!-- .container main -->
<!-- .page-header -->
<!-- .header -->
<h3><?php echo gettext('Search'); ?></h3>
</div><!-- .header -->
</div><!-- /.page-header -->

<div class="page-header row">
	<div class="col-xs-offset-1 col-xs-10 col-sm-offset-2 col-sm-8 col-md-offset-3 col-md-6">
		<?php printSearchForm(); ?>
	</div>
</div>

<div class="search-wrap">
	<?php
	$numimages = getNumImages();
	$numalbums = getNumAlbums();
	$total_gallery = $numimages + $numalbums;

	$numnews = $numpages = 0;
	if (class_exists('CMS') && !isArchive()) {
		if ($_zenpage_news_enabled) {
			$numnews = getNumNews();
		}
		if ($_zenpage_pages_enabled) {
			$numpages = getNumPages();
		}
	}
	$total = $total_gallery + $numnews + $numpages;

	$searchwords = getSearchWords();
	$searchdate = getSearchDate();
	if (!empty($searchdate)) {
		if (!empty($searchwords)) {
			$searchwords .= ": ";
		}
		$searchwords .= $searchdate;
	}
	?>

	<div class="page-header">
		<h4>
			<?php
			if ($total == 0) {
				echo gettext("Sorry, no matches found. Try refining your search.");
			} else {
				printf(ngettext('%1$u Hit for <em>%2$s</em>', '%1$u Hits for <em>%2$s</em>', $total), $total, html_encode($searchwords));
			}
			?>
		</h4>
	</div>

	<?php
	if (getOption('search_no_albums')) {		//test of images search
		if ($numimages > 0) {
			echo '<h4 class="margin-top-double margin-bottom-double"><strong>';
			printf(gettext('Images (%s)'), $numimages);
			echo '</strong></h4>';
		}
	} else {
		if (getOption('search_no_images')) {	 //test of albums search
			if ($numalbums > 0) {
				echo '<h4 class="margin-top-double margin-bottom-double"><strong>';
				printf(gettext('Albums (%s)'), $numalbums);
				echo '</strong></h4>';
			}
		} else {
			if ($total_gallery > 0) {		 //test of albums and images search
				echo '<h4 class="margin-top-double margin-bottom-double"><strong>';
				printf(gettext('Albums (%1$s) &amp; Images (%2$s)'), $numalbums, $numimages);
				echo '</strong></h4>';
			}
		}
	}

	printPageListWithNav('«', '»', false, true, 'pagination pagination-sm', NULL, true, 7);

	if (getNumAlbums() > 0) {
		include('includes/print_album_thumb.php');
	}
	if (getNumImages() > 0) {
		include('includes/print_image_thumb.php');
	}

	printPageListWithNav('«', '»', false, true, 'pagination pagination-sm margin-top-reset', NULL, true, 7);

	if ((class_exists('CMS')) /* && ($_current_page == 1) */) {	//test of CMS searches
		if ($_zenpage_news_enabled && ($numnews > 0)) {
			?>
			<h4 class="margin-top-double margin-bottom-double"><strong><?php printf(gettext('Articles (%s)'), $numnews); ?></strong></h4>
			<?php while (next_news()) { ?>
				<div class="list-post clearfix">
					<h4 class="post-title"><?php printNewsURL(); ?></h4>
					<div class="post-content clearfix">
				<?php echo shortenContent(getBare(getNewsContent()), getOption("zpB_exerpt_length")); ?>
					</div>
				</div>
			<?php
		}
	}

	if ($_zenpage_pages_enabled && ($numpages > 0)) {
		?>
			<h4 class="margin-top-double margin-bottom-double"><strong><?php printf(gettext('Pages (%s)'), $numpages); ?></strong></h4>
			<?php while (next_page()) { ?>
				<div class="list-post clearfix">
					<h4 class="post-title"><?php printPageURL(); ?></h4>
					<div class="post-content clearfix">
			<?php echo shortenContent(getBare(getPageContent()), getOption("zpB_exerpt_length")); ?>
					</div>
				</div>
			<?php
		}
	}
}
?>
</div><!-- /.search-wrap -->

</div><!-- /.container main -->

<?php include('includes/footer.php');
