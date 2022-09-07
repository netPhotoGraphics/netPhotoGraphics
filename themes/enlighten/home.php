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
		?>
	</head>
	<body>
		<?php npgFilters::apply('theme_body_open'); ?>

		<div id="main" class="home">

			<?php include("header.php"); ?>
			<div id="content">

				<div id="breadcrumb">
					<h2><strong><?php echo gettext('Index'); ?></strong></h2>
				</div>

				<div id="content-left">
				<!-- div class="gallerydesc"><?php /* printGalleryDesc(true); */ ?></div -->
					<h3 class="searchheader">Latest sins</h3>
					<div id="albums" style="margin-left: 4px;">
						<?php
						$latestImages = Utils::getLatestImages(4);
						$u = 0;
						foreach ($latestImages as $i) : $u++;
							?>
							<div class="album" <?php
							if ($u % 2 == 0) {
								echo 'style="margin-left: 8px;"';
							}
							?> >
								<div class="thumb">
									<?php
									$thumb = $i->getCustomImage(array('width' => 255, 'height' => 75, 'cw' => 255, 'ch' => 75, 'thumb' => TRUE));
									$link = $i->getLink();
									$date = $i->getDateTime();
									if ($date) {
										$date = date("d F Y", strtotime($date));
									}
									?>
									<a href="<?php echo $link; ?>">
										<?php
										$html = "<img src='$thumb' width='255' height='75'/>";
										if (ENCODING_FALLBACK) {
											$html = "<picture>\n<source srcset=\"" . html_encode($i->getCustomImage(array('width' => 255, 'height' => 75, 'cw' => 255, 'ch' => 75, 'thumb' => TRUE), FALLBACK_SUFFIX)) . "\">\n" . $html . "</picture>\n";
										}
										echo $html;
										?>
									</a>
								</div>
								<div class="albumdesc">
									<?php
									$_current_album = $i->getAlbum();
									?>
									<h3><span style="color: #999;">Album:</span> <a href="<?php echo htmlspecialchars(getAlbumURL()); ?>" title="<?php echo gettext('View album:'); ?> <?php echo $_current_album->getTitle(); ?>"><?php echo $_current_album->getTitle(); ?></a></h3>
									<h3 class="date"><?= $date; ?></h3>
								</div>
							</div>
						<?php endforeach ?>
						<?php
						$_current_album = NULL;
						?>
					</div>
					<br style="clear:both;" /><br />
					<h3 class="searchheader" >Latest words</h3>
					<?php
					$ln = getLatestNews(3);

					foreach ($ln as $n) :
						$_CMS_current_article = newArticle($n['titlelink']);
						?>


						<div class="newsarticlewrapper"><div class="newsarticle" style="border-width: 0;">
								<h3><?php printNewsURL(); ?></h3>
								<div class="newsarticlecredit"><span class="newsarticlecredit-left"><?php printNewsDate(); ?> | <?php echo gettext("Comments:"); ?> <?php echo getCommentCount(); ?></span>
									<?php
									if (is_NewsArticle()) {
										echo ' | ';
										printNewsCategories(", ", gettext("Categories: "), "newscategories");
									}
									?>
								</div>
								<?php printNewsContent(); ?>
								<?php printCodeblock(1); ?>
								<br style="clear:both; " />
							</div>
						</div>
					<?php endforeach; ?>

					<br style="clear:both;" />


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