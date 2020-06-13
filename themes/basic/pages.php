<?php
// force UTF-8 Ø
if (!defined('WEBPATH'))
	die();
if (class_exists('CMS')) {
	?>
	<!DOCTYPE html>
	<html<?php i18n::htmlLanguageCode(); ?>>
		<head>

			<?php
			npgFilters::apply('theme_head');

			scriptLoader($basic_CSS);
			scriptLoader(dirname(dirname($basic_CSS)) . '/common.css');

			if (class_exists('RSS'))
				printRSSHeaderLink("Pages", "CMS pages", "");
			?>
		</head>

		<body>
			<?php npgFilters::apply('theme_body_open'); ?>
			<div id="main">
				<div id="header">
					<div id="gallerytitle">
						<?php
						if (getOption('Allow_search')) {
							printSearchForm('');
						}
						?>
						<h2>
							<?php printHomeLink('', ' | '); ?>
							<a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Index'); ?>"><?php printGalleryTitle(); ?></a>
							<?php
							printZenpageItemsBreadcrumb(" | ", "");
							printPageTitle(" | ");
							?>
						</h2>
					</div>

				</div>
				<h2><?php printPageTitle(); ?></h2>
				<div id="pagetext">
					<?php printCodeblock(1); ?>
					<?php printPageContent(); ?>
					<?php printCodeblock(2); ?>
				</div>

				<?php
				@call_user_func('printCommentForm');

				$pages = $_CMS_current_page->getPages(NULL, true); // top level only
				if (!empty($pages)) {
					?>
					<br /><hr />
					<?php
					foreach ($pages as $item) {
						$pageobj = newPage($item['titlelink']);
						?>
						<span class="npg_link">
							<a href="<?php echo $pageobj->getLink(); ?>"><?php echo html_encode($pageobj->getTitle()); ?></a>
						</span>
						<?php
					}
				}
				?>

			</div>
			<div id="credit">
				<?php
				if (function_exists('printFavoritesURL')) {
					printFavoritesURL(NULL, '', ' | ', '<br />');
				}
				?>
				<?php if (class_exists('RSS')) printRSSLink('Gallery', '', 'RSS', ' | '); ?>
				<?php printCustomPageURL(gettext("Archive View"), "archive"); ?> | <?php printSoftwareLink(); ?>
				<?php
				if (extensionEnabled('daily-summary')) {
					printDailySummaryLink(gettext('Daily summary'), '', '', ' | ');
				}
				?>
				<?php @call_user_func('printUserLogin_out', " | "); ?>
			</div>
	</body>
	<?php npgFilters::apply('theme_body_close'); ?>
	</html>
	<?php
} else {
	include(CORE_SERVERPATH . '404.php');
}
?>