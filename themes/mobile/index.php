<?php
// force UTF-8 Ø
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
		?>
	</head>

	<body>
		<?php npgFilters::apply('theme_body_open'); ?>

		<div data-role="page" id="mainpage">

			<?php jqm_printMainHeaderNav(); ?>

			<div class="ui-content" role="main">

				<div class="content-primary">
					<?php printGalleryDesc(); ?>
					<?php
					if (function_exists('printLatestImages')) {
						?>
						<h2><?php echo gettext('Latest images'); ?></h2>
						<?php $latestimages = getImageStatistic(8, 'latest', '', false, 0, 'desc'); ?>
						<div class="ui-grid-c">
							<?php
							$count = 0;
							foreach ($latestimages as $image) {
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
								<a class="image<?php echo $imgclass; ?>" href="<?php echo html_encode($image->getLink()); ?>" title="<?php echo html_encode($image->getTitle()); ?>">
									<?php
									$html = '<img src="' . $image->getCustomImage(array('width' => 230, 'height' => 230, 'cw' => 230, 'ch' => 230, 'thumb' => TRUE)) . '" alt="' . $image->getTitle() . '">';
									$html = npgFilters::apply('custom_image_html', $html, FALSE);
									if (ENCODING_FALLBACK) {
										$html = "<picture>\n<source srcset=\"" . html_encode($image->getCustomImage(array('width' => 230, 'height' => 230, 'cw' => 230, 'ch' => 230, 'thumb' => TRUE), FALLBACK_SUFFIX)) . "\">\n" . $html . "</picture>\n";
									}
									echo $html;
									?>
								</a>
								<?php
							}
						}
						?>
					</div>
					<br class="clearall" />
					<br />
					<?php if (function_exists('next_news')) { ?>
						<ul data-role="listview" data-inset="true" data-theme="a" class="ui-listview ui-group-theme-a">
							<li data-role="list-divider"><h2><?php echo NEWS_LABEL; ?></h2></li>
							<?php while (next_news()): ?>
								<li>
									<a href="<?php echo html_encode(jqm_getLink()); ?>" title="<?php printBareNewsTitle(); ?>">
										<?php printNewsTitle(); ?> <small>(<?php printNewsDate(); ?>)</small>
									</a>
								</li>
								<?php
							endwhile;
							?>
						</ul>
						<?php
					}
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
