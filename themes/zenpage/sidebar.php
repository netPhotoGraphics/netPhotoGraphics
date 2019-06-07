<?php
// force UTF-8 Ø
$pages = $news = NULL;
if (function_exists('printCustomMenu') && getOption('zenpage_custommenu')) {
	?>
	<div class="menu">
		<?php printCustomMenu('zenpage', 'list', '', "menu-active", "submenu", "menu-active", 2); ?>
	</div>
	<?php
} else {
	if (extensionEnabled('zenpage') && ($news = hasNews())) {
		?>
		<div class="menu">
			<h3><?php echo NEWS_LABEL; ?></h3>
			<?php
			printAllNewsCategories(gettext("All"), TRUE, "", "menu-active", true, "submenu", "menu-active");
			?>
		</div>
	<?php } ?>

	<?php if (function_exists("printAlbumMenu")) { ?>
		<div class="menu">
			<?php
			if (extensionEnabled('zenpage')) {
				if ($_gallery_page == 'index.php' || $_gallery_page != 'gallery.php') {
					?>
					<h3>
						<a href="<?php echo html_encode(getCustomPageURL('gallery')); ?>" title="<?php echo gettext('Album index'); ?>"><?php echo gettext("Gallery"); ?></a>
					</h3>
					<?php
				} else {
					?>
					<h3><?php echo gettext("Gallery"); ?></h3>
					<?php
				}
			} else {
				?>
				<h3><?php echo gettext("Gallery"); ?></h3>
				<?php
			}
			printAlbumMenu("list", "count", "", "menu-active", "submenu", "menu-active", '');
			?>
		</div>
	<?php } ?>

	<?php if (extensionEnabled('zenpage') && (hasPages())) { ?>
		<div class="menu">
			<h3><?php echo gettext("Pages"); ?></h3>
			<?php printPageMenu("list", "", "menu-active", "submenu", "menu-active"); ?>
		</div>
		<?php
	}
} // custom menu check end
?>

<div class="menu">
	<h3><?php printCustomPageURL(gettext('Archive'), "archive"); ?></h3>
</div>
<?php
if (extensionEnabled('daily-summary')) {
	?>
	<div class="menu">
		<h3>
			<?php
			printDailySummaryLink(gettext('Daily summary'), '', '', '');
			?>
		</h3>
	</div>
	<?php
}
?>
<?php
if (class_exists('RSS') && (getOption('RSS_album_image') || getOption('RSS_articles'))) {
	?>
	<div class="menu">
		<h3><?php echo gettext("RSS"); ?></h3>
		<ul>
			<?php
			if (!is_null($_current_album)) {
				printRSSLink('Album', '<li>', gettext('Album'), '</li>');
				?>
				<?php
			}
			?>
			<?php
			printRSSLink('Gallery', '<li>', gettext('Gallery'), '</li>');
			?>
			<?php
			if ($news) {
				printRSSLink("News", "<li>", NEWS_LABEL, '</li>');
			}
			?>
		</ul>
	</div>
	<?php
}
?>

<?php
if (getOption("zenpage_contactpage") && extensionEnabled('contact_form')) {
	?>
	<div class="menu">
		<ul>
			<li>
				<?php
				if ($_gallery_page != 'contact.php') {
					printCustomPageURL(gettext('Contact us'), 'contact', '', '');
				} else {
					echo gettext("Contact us");
				}
				?></li>
		</ul>
	</div>
	<?php
}
if ((function_exists("printUserLogin_out") ) || !npg_loggedin() && function_exists('printRegistrationForm') || class_exists('mobileTheme')) {
	?>
	<div class="menu">
		<ul>
			<?php
			if (!npg_loggedin() && function_exists('printRegisterURL')) {
				?>
				<li>
					<?php
					if ($_gallery_page != 'register.php') {
						printRegisterURL(gettext('Register for this site'));
					} else {
						echo gettext("Register for this site");
					}
					?>
				</li>
				<?php
			}
			if (function_exists('printFavoritesURL')) {
				printFavoritesURL(NULL, '<li>', '</li><li>', '</li>');
			}
			if (function_exists("printUserLogin_out")) {
				printUserLogin_out("<li>", "</li>");
			}
			if (class_exists('mobileTheme')) {
				?>
				<li>
					<?php mobileTheme::controlLink(NULL, '', ''); ?>
				</li>
				<?php
			}
			?>
		</ul>
	</div>
	<?php
}
?>
<?php @call_user_func('printLanguageSelector'); ?>