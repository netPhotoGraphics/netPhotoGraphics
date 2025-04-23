<?php
// force UTF-8
if (!defined('WEBPATH'))
	die();
?>
<!DOCTYPE html>
<html<?php i18n::htmlLanguageCode(); ?>>
	<head>
		<?php
		npgFilters::apply('theme_head');

		scriptLoader($basic_CSS);
		scriptLoader(dirname(dirname($basic_CSS)) . '/common.css');

		if (class_exists('RSS'))
			printRSSHeaderLink('Gallery', gettext('Gallery'));
		?>
		<script>

			function toggleExtraElements(category, show) {
				if (show) {
					jQuery('.' + category + '_showless').show();
					jQuery('.' + category + '_showmore').hide();
					jQuery('.' + category + '_extrashow').show();
				} else {
					jQuery('.' + category + '_showless').hide();
					jQuery('.' + category + '_showmore').show();
					jQuery('.' + category + '_extrashow').hide();
				}
			}

		</script>
	</head>
	<body>
		<?php
		npgFilters::apply('theme_body_open');
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
		$searchwords = getSearchWords();
		$searchdate = getSearchDate();
		if (!empty($searchdate)) {
			if (!empty($searchwords)) {
				$searchwords .= ": ";
			}
			$searchwords .= $searchdate;
		}
		?>
		<div id="main">
			<div id="gallerytitle">
				<?php
				printSearchForm();
				?>
				<h2>
					<span>
						<?php printHomeLink('', ' | '); ?>
						<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo ('Gallery Index'); ?>"><?php printGalleryTitle(); ?></a>
					</span>
					|
					<?php printSearchBreadcrumb(' | '); ?>
				</h2>
			</div>
			<div id="padbox">
				<?php
				if ($total) {
					echo '<p>' . sprintf(gettext('Total matches for <em>%1$s</em>: %2$u'), html_encode($searchwords), $total) . '</p>';
				} else {
					echo "<p>" . gettext('Sorry, no matches for your search.') . "</p>";
				}

				if ($zenpage && $_current_page == 1) { //test of CMS searches
					define('SHOW_ITEMS', 5);
					?>
					<div>
						<?php
						if ($numpages > 0) {
							?>
							<div id="garland_searchhead_pages">
								<h3><?php printf(gettext('Pages (%s)'), $numpages); ?></h3>
								<?php
								if ($numpages > SHOW_ITEMS) {
									?>
									<p class="pages_showmore"><a href="javascript:toggleExtraElements('pages',true);"><?php echo gettext('Show more results'); ?></a></p>
									<p class="pages_showless" style="display:none;"><a href="javascript:toggleExtraElements('pages',false);"><?php echo gettext('Show fewer results'); ?></a></p>
									<?php
								}
								?>
							</div>
							<div>
								<ul>
									<?php
									$c = 0;
									while (next_page()) {
										$c++;
										?>
										<li<?php if ($c > SHOW_ITEMS) echo ' class="pages_extrashow" style="display:none;"'; ?>>
											<?php printPageURL(); ?>
											<p style="text-indent:1em;"><?php echo shortenContent($_CMS_current_page->getContent(), 80); ?></p>
										</li>
										<?php
									}
									?>
								</ul>
							</div>
							<?php
						}
						if ($numnews > 0) {
							if ($numpages > 0)
								echo '<br />';
							?>
							<div>
								<h3><?php printf(gettext('Articles (%s)'), $numnews); ?></h3>
								<?php
								if ($numnews > SHOW_ITEMS) {
									?>
									<p class="news_showmore"><a href="javascript:toggleExtraElements('news',true);"><?php echo gettext('Show more results'); ?></a></p>
									<p class="news_showless" style="display:none;"><a href="javascript:toggleExtraElements('news',false);"><?php echo gettext('Show fewer results'); ?></a></p>
									<?php
								}
								?>
							</div>
							<div>
								<ul>
									<?php
									$c = 0;
									while (next_news()) {
										$c++;
										?>
										<li<?php if ($c > SHOW_ITEMS) echo ' class="news_extrashow" style="display:none;"'; ?>>
											<?php printNewsURL(); ?>
											<p style="text-indent:1em;"><?php echo shortenContent($_CMS_current_article->getContent(), 80); ?></p>
										</li>
										<?php
									}
									?>
								</ul>
							</div>
							<?php
						}
					}
					if ($total > 0 && ($numpages + $numnews) > 0) {
						?>
						<br />
						<div>
							<h3>
								<?php
								if (getOption('search_no_albums')) {
									if (!getOption('search_no_images')) {
										printf(gettext('Images (%s)'), $numimages);
									}
								} else {
									if (getOption('search_no_images')) {
										printf(gettext('Albums (%s)'), $numalbums);
									} else {
										printf(gettext('Albums (%1$s) &amp; Images (%2$s)'), $numalbums, $numimages);
									}
								}
								?>
							</h3>
						</div>
						<?php
					}
					?>
				</div>

				<div id="albums">
					<?php
					$c = 0;
					while (next_album()) {
						$c++;
						?>
						<div class="album">
							<div class="thumb">
								<a href="<?php echo html_encode(getAlbumURL()); ?>" title="<?php echo gettext('View album:'); ?> <?php printAnnotatedAlbumTitle(); ?>"><?php printAlbumThumbImage(getAnnotatedAlbumTitle()); ?></a>
							</div>
							<div class="albumdesc">
								<h3><a href="<?php echo html_encode(getAlbumURL()); ?>" title="<?php echo gettext('View album:'); ?> <?php printAnnotatedAlbumTitle(); ?>"><?php printAlbumTitle(); ?></a></h3>
								<p><?php printAlbumDesc(); ?></p>
								<small><?php printAlbumDate(gettext("Date:") . ' '); ?> </small>
							</div>
						</div>
						<?php
					}
					?>
				</div>
				<br class="clearall" />
				<div id="images">
					<?php
					while (next_image()) {
						$c++;
						?>
						<div class="image">
							<div class="imagethumb">
								<a href="<?php echo html_encode(getImageURL()); ?>" title="<?php printBareImageTitle(); ?>"><?php printImageThumb(getAnnotatedImageTitle()); ?></a>
							</div>
						</div>
						<?php
					}
					?>
				</div>
				<br class="clearall" />
				<?php
				if (function_exists('printSlideShowLink'))
					printSlideShowLink();
				printPageListWithNav("« " . gettext("prev"), gettext("next") . " »");
				?>
			</div>
		</div>
		<div id="credit">
			<?php
			if (function_exists('printFavoritesURL')) {
				printFavoritesURL(NULL, '', ' | ', '<br />');
			}
			?>
			<?php if (class_exists('RSS')) printRSSLink('Gallery', '', gettext('Gallery'), ' | '); ?>
			<?php printCustomPageURL(gettext("Archive View"), "archive"); ?> |
			<?php
			if (extensionEnabled('daily-summary')) {
				printDailySummaryLink(gettext('Daily summary'), '', '', ' | ');
			}
			?>
			<?php printSoftwareLink(); ?>
			<?php if (function_exists('printUserLogin_out')) printUserLogin_out(" | "); ?>
		</div>
	</body>
	<?php npgFilters::apply('theme_body_close'); ?>
</html>