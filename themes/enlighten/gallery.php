<?php
if (!defined('WEBPATH'))
	die();
?>
<!DOCTYPE html>
<html<?php i18n::htmlLanguageCode(); ?>>
	<head>
		<?php
		npgFilters::apply('theme_head');
		printZDRoundedCornerJS();

		scriptLoader($_themeroot . '/style.css');
		if (extensionEnabled('rss')) {
			printRSSHeaderLink('Gallery', gettext('Gallery'));
		}
		?>
	</head>
	<body>
		<?php npgFilters::apply('theme_body_open'); ?>

		<div id="main">

			<?php include("header.php"); ?>

			<div id="content">

				<div id="breadcrumb">
					<h2>
						<?php if (class_exists('CMS')) { ?>
							<a href="<?php echo getGalleryIndexURL(); ?>" title="<?php echo gettext('Index'); ?>"><?php echo gettext("Index"); ?></a>»
						<?php } ?>
						<strong><?php echo gettext("Gallery"); ?></strong></a>
					</h2>
				</div>

				<div id="content-left">
					<?php if (!getOption("zenpage_zp_index_news") OR!function_exists("printNewsPageListWithNav")) { ?>
						<div class="gallerydesc" style="margin-right: 20px; margin-left: 2px;"><?php printGalleryDesc(); ?> </div>
						<div id="albums">
							<?php $u = 0; ?>
							<?php while (next_album()): $u++; ?>
								<div class="album">
									<div class="thumb">
										<a href="<?php echo html_encode(getAlbumURL()); ?>" title="<?php echo gettext('View album:'); ?> <?php echo html_encode(getBareAlbumTitle()); ?>"><?php printCustomAlbumThumbImage(getBareAlbumTitle(), array('width' => 255, 'height' => 75, 'cw' => 255, 'ch' => 75)); ?></a>
									</div>
									<div class="albumdesc">
										<h3><a href="<?php echo html_encode(getAlbumURL()); ?>" title="<?php echo gettext('View album:'); ?> <?php echo html_encode(getBareAlbumTitle()); ?>"><?php printAlbumTitle(); ?></a></h3>
										<h3 class="date"><?php printAlbumDate("", "", "F Y"); ?></h3>
									<!-- p><?php echo html_encodeTagged(shortenContent(getAlbumDesc(), 45)); ?></p --></h3>
									</div>
									<p style="clear: both; "></p>
								</div>
							<?php endwhile; ?>
							<?php while ($u % 2 != 0) : $u++; ?>
								<div class="album">
									<div class="thumb"><a><img style="width: 255px; height: 75px;"src="<?= $_themeroot ?>/images/trans.png" /></a></div>
									<div class="albumdesc">
										<h3 style="color: transparent;">No album</h3>
										<h3 class="date" style="color: transparent;">No Date</h3>
									</div>
								</div>
							<?php endwhile ?>
						</div>
						<br style="clear: both" />
						<?php printPageListWithNav("« " . gettext("prev"), gettext("next") . " »"); ?>

						<?php
					} else { // news article loop
						printNewsPageListWithNav(gettext('next »'), gettext('« prev'));
						echo "<hr />";
						while (next_news()):;
							?>
							<div class="newsarticle">
								<h3><?php printNewsURL(); ?></h3>
								<div class="newsarticlecredit"><span class="newsarticlecredit-left"><?php printNewsDate(); ?> | <?php echo gettext("Comments:"); ?> <?php echo getCommentCount(); ?></span>
									<?php
									printNewsCategories(", ", gettext("Categories: "), "newscategories");
									?>
								</div>
								<?php printNewsContent(); ?>
								<?php printCodeblock(1); ?>
								<?php printTags('links', gettext('<strong>Tags:</strong>') . ' ', 'taglist', ', '); ?>
							</div>
							<?php
						endwhile;
						printNewsPageListWithNav(gettext('next »'), gettext('« prev'));
					}
					?>

				</div><!-- content left-->


				<div id="sidebar">
					<?php include("sidebar.php"); ?>
				</div><!-- sidebar -->



				<div id="footer">
					<?php include("footer.php"); ?>
				</div>

			</div><!-- content -->

		</div><!-- main -->
	</body>
	<?php npgFilters::apply('theme_body_close'); ?>
</html>