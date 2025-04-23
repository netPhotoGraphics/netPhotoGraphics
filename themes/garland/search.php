<?php
// force UTF-8 Ø
if (!defined('WEBPATH'))
	die();
?>
<!DOCTYPE html>
<html<?php i18n::htmlLanguageCode(); ?>>
	<head>
		<?php
		npgFilters::apply('theme_head');
		$handler->theme_head($_themeroot);

		scriptLoader($_themeroot . '/garland.css');

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
	<body class="sidebars">
		<?php
		npgFilters::apply('theme_body_open');
		$handler->theme_bodyopen($_themeroot);
		$numimages = getNumImages();
		$numalbums = getNumAlbums();
		$total = $numimages + $numalbums;
		$zenpage = class_exists('CMS');
		if ($zenpage && !isArchive()) {
			$numpages = getNumPages();
			$numnews = getNumNews();
			$total = $total + $numnews + $numpages;
		} else {
			$numpages = $numnews = 0;
		}
		$searchwords = getSearchWords();
		$searchdate = getSearchDate();
		if (!empty($searchdate)) {
			if (!empty($seachwords)) {
				$searchwords .= ": ";
			}
			$searchwords .= $searchdate;
		}
		if (!$total) {
			$_current_search->clearSearchWords();
		}
		?>
		<div id="navigation"></div>
		<div id="wrapper">
			<div id="container">
				<div id="header">
					<div id="logo-floater">
						<div>
							<h1 class="title"><a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Gallery Index'); ?>"><?php echo html_encode(getGalleryTitle()); ?></a></h1>
							<span id="galleryDescription"><?php printGalleryDesc(); ?></span>
						</div>
					</div>
				</div>
				<!-- header -->
				<div class="sidebar">
					<div id="leftsidebar">
						<?php include("sidebar.php"); ?>
					</div>
				</div>
				<div id="center">
					<div id="squeeze">
						<div class="right-corner">
							<div class="left-corner">
								<!-- begin content -->
								<div class="main section" id="main">
									<h2 id="gallerytitle">
										<?php printHomeLink('', ' » '); ?>
										<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Gallery Index'); ?>"><?php echo html_encode(getGalleryTitle()); ?></a> » <?php printSearchBreadcrumb(' » '); ?>
									</h2>

									<?php
									if ($total > 0) {
										?>
										<p>
											<?php
											printf(ngettext('%1$u Hit for <em>%2$s</em>', '%1$u Hits for <em>%2$s</em>', $total), $total, html_encode($searchwords));
											?>
										</p>
										<?php
									} else {
										echo "<p>" . gettext('Sorry, no matches for your search.') . "</p>";
										$_current_search->setSearchParams('words=');
									}
									?>
									<?php
									if ($zenpage && $_current_page == 1) { //test of CMS searches
										define('SHOW_ITEMS', 5);
										?>
										<div id="garland_search">
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
												<div class="garland_searchtext">
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
												<div id="garland_searchhead_news">
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
												<div class="garland_searchtext">
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
											<div id="garland_searchhead_gallery">
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
										while (next_album()) {
											?>
											<div class="album">
												<a class="albumthumb" href="<?php echo getAlbumURL(); ?>" title="<?php printf(gettext('View album: %s'), html_encode(getBareAlbumTitle())); ?>">
													<?php printCustomAlbumThumbImage(getAlbumTitle(), array('size' => 85, 'cw' => 85, 'ch' => 85)); ?>
												</a>
												<div class="albumdesc">
													<h3>
														<a href="<?php echo getAlbumURL(); ?>" title="<?php printf(gettext('View album: %s'), html_encode(getBareAlbumTitle())); ?>">
															<?php printAlbumTitle(); ?>
														</a>
													</h3>
													<br />
													<small><?php printAlbumDate(); ?></small>
												</div>
												<p style="clear: both;"></p>
											</div>
											<?php
										}
										?>
									</div>
									<p style="clear: both; "></p>
									<?php $handler->theme_content(NULL); ?>
									<?php
									printPageListWithNav(gettext("« prev"), gettext("next »"));
									footer();
									?>
									<p style="clear: both;"></p>
								</div>
								<!-- end content -->
								<span class="clear"></span>
							</div>
						</div>
					</div>
					<div class="sidebar">
						<div id="rightsidebar">
						</div>
					</div>
					<span class="clear"></span>
				</div>
				</body>
				<?php npgFilters::apply('theme_body_close'); ?>
				</html>
