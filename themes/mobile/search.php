<?php
if (!defined('WEBPATH'))
	die();
?>
<!DOCTYPE html>
<html<?php i18n::htmlLanguageCode(); ?>>
	<head>
		<?php npgFilters::apply('theme_head'); ?>



		<meta name="viewport" content="width=device-width, initial-scale=1">

		<?php
		scriptLoader($_themeroot . '/style.css');
		jqm_loadScripts();
		printZDSearchToggleJS();
		?>
	</head>

	<body>
		<?php npgFilters::apply('theme_body_open'); ?>
		<div data-role="page" id="mainpage">

			<?php jqm_printMainHeaderNav(); ?>

			<div class="ui-content" role="main">
				<div class="content-primary">

					<h2><?php printSearchBreadcrumb(); ?></h2>
					<?php
					$zenpage = class_exists('CMS');
					$numimages = getNumImages();
					$numalbums = getNumAlbums();
					$total = $numimages + $numalbums;
					if ($zenpage && !isArchive()) {
						$numpages = getNumPages();
						$numnews = getNumNews();
						$total = $total + $numnews + $numpages;
					} else {
						$numpages = $numnews = 0;
					}
					if ($total == 0) {
						$_current_search->clearSearchWords();
					}
					if (getOption('Allow_search')) {
						$categorylist = $_current_search->getCategoryList();
						if (!empty($categorylist)) {
							$catlist = array('news' => $categorylist, 'albums' => '0', 'images' => '0', 'pages' => '0');
							printSearchForm(NULL, 'search', NULL, gettext('Search category'), NULL, NULL, $catlist);
						} else {
							$albumlist = $_current_search->getAlbumList();
							if (!empty($albumlist)) {
								$album_list = array('albums' => $albumlist, 'pages' => '0', 'news' => '0');
								printSearchForm(NULL, 'search', NULL, gettext('Search album'), NULL, NULL, $album_list);
							} else {
								printSearchForm("", "search", NULL, gettext("Search gallery"));
							}
						}
					}
					$searchwords = getSearchWords();
					$searchdate = getSearchDate();
					if (!empty($searchdate)) {
						if (!empty($searchwords)) {
							$searchwords .= ": ";
						}
						$searchwords .= $searchdate;
					}
					if ($total > 0) {
						?>
						<h3>
							<?php
							printf(ngettext('%1$u Hit for <em>%2$s</em>', '%1$u Hits for <em>%2$s</em>', $total), $total, html_encode($searchwords));
							?>
						</h3>
						<?php
					}
					if ($_current_page == 1) { //test of CMS searches
						if ($numpages > 0) {
							$number_to_show = 5;
							$c = 0;
							?>
							<hr />
							<h3><?php printf(gettext('Pages (%s)'), $numpages); ?> <small><?php printZDSearchShowMoreLink("pages", $number_to_show); ?></small></h3>
							<ul data-role="listview" data-inset="true" data-theme="c" class="ui-listview ui-group-theme-a">
								<?php
								while (next_page()) {
									$c++;
									?>
									<li<?php printZDToggleClass('pages', $c, $number_to_show); ?>>
										<h4><?php printPageURL(); ?></h4>
										<p class="zenpageexcerpt"><?php echo html_encodeTagged(shortenContent(getPageContent(), 80)); ?></p>
									</li>
									<?php
								}
								?>
							</ul>
							<?php
						}
						if ($numnews > 0) {
							$number_to_show = 5;
							$c = 0;
							?>
							<h3><?php printf(gettext('Articles (%s)'), $numnews); ?> <small><?php printZDSearchShowMoreLink("news", $number_to_show); ?></small></h3>
							<ul data-role="listview" data-inset="true" data-theme="c" class="ui-listview ui-group-theme-a">
								<?php
								while (next_news()) {
									$c++;
									?>
									<li<?php printZDToggleClass('news', $c, $number_to_show); ?>>
										<h4><?php printNewsURL(); ?></h4>
										<p class="zenpageexcerpt"><?php echo html_encodeTagged(shortenContent(getNewsContent(), 80)); ?></p>
									</li>
									<?php
								}
								?>
							</ul>
							<?php
						}
					}
					?>
					<h3>
						<?php
						if (getOption('search_no_albums')) {
							if (!getOption('search_no_images') && ($numpages + $numnews) > 0) {
								printf(gettext('Images (%s)'), $numimages);
							}
						} else {
							if (getOption('search_no_images')) {
								if (($numpages + $numnews) > 0) {
									printf(gettext('Albums (%s)'), $numalbums);
								}
							} else {
								printf(gettext('Albums (%1$s) &amp; Images (%2$s)'), $numalbums, $numimages);
							}
						}
						?>
					</h3>
					<?php if (getNumAlbums() != 0) { ?>
						<ul data-role="listview" data-inset="true">
							<?php while (next_album()): ?>
								<li>
									<a href="<?php echo html_encode(getAlbumURL()); ?>" title="<?php
									echo gettext('View album:');
									printAnnotatedAlbumTitle();
									?>">
											 <?php printCustomAlbumThumbImage(getAnnotatedAlbumTitle(), array('width' => 79, 'height' => 79, 'cw' => 79, 'ch' => 79)); ?>
										<h3><?php printAlbumTitle(); ?><small> (<?php printAlbumDate(''); ?>)</small></h3>
										<div class="albumdesc"><?php echo html_encodeTagged(shortenContent(getAlbumDesc(), 100, '(...)', false)); ?></div>
										<small class="ui-li-aside ui-li-count"><?php jqm_printImageAlbumCount() ?></small>
									</a>
								</li>
							<?php endwhile; ?>
						</ul>
					<?php } ?>
					<?php if (getNumImages() > 0) { ?>
						<div class="ui-grid-c">
							<?php
							$count = 0;
							while (next_image()) {
								$count++;
								switch ($count) {
									case 1:
										$imgclass = ' ui-block-a';
										break;
									case 2:
										$imgclass = ' ui-block-b';
										break;
									case 3:
										$imgclass = ' ui-block-c';
										break;
									case 4:
										$imgclass = ' ui-block-d';
										$count = 0; // reset to start with a again;
										break;
								}
								?>
								<a class="image<?php echo $imgclass; ?>" href="<?php echo html_encode(getImageURL()); ?>" title="<?php printBareImageTitle(); ?>">
									<?php printCustomSizedImage(getAnnotatedImageTitle(), array('width' => 230, 'height' => 230, 'cw' => 230, 'ch' => 230, 'thumb' => TRUE)); ?>
								</a>
							<?php } ?>
						</div>
						<br class="clearall" />
					<?php } ?>
					<?php
					if (function_exists('printSlideShowLink'))
						printSlideShowLink(gettext('View Slideshow'));
					if ($total == 0) {
						echo "<p>" . gettext("Sorry, no matches found. Try refining your search.") . "</p>";
					}

					printPageListWithNav("« " . gettext("prev"), gettext("next") . " »");
					?>

				</div>
				<div class="content-secondary">
					<?php jqm_printMenusLinks(); ?>
				</div>
			</div><!-- /content -->
			<?php jqm_printBacktoTopLink(); ?>
			<?php jqm_printFooterNav(); ?>
		</div><!-- /page -->
	</body>
	<?php npgFilters::apply('theme_body_close'); ?>
</html>