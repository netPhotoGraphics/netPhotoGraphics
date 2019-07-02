</div>		<!-- END #CONTAINER -->

<div id="footer">
	<?php if ((getOption('allow_search')) || (extensionEnabled('print_album_menu'))) { ?>
		<div id="jump-search" class="clearfix">
			<?php
			if (extensionEnabled('print_album_menu')) {
				printAlbumMenu('jump', NULL, '', '', '', '', gettext('Gallery Index'));
			}
			if (getOption('allow_search')) {
				printSearchForm('', 'search', '', gettext('Search'), "$_themeroot/images/search-drop.png", NULL, NULL, "$_themeroot/images/reset.gif");
			}
			?>
		</div>
	<?php } ?>

	<div id="foot-left">
		<?php if ((extensionEnabled('rss')) && ((getOption('RSS_album_image')) || (($_zenpage_enabled) && (getOption('RSS_articles'))))) { ?>
			<div id="rsslinks">
				<?php
				$rss = false;
				if (getOption('RSS_album_image')) {
					printRSSLink('Gallery', '', gettext('Images'), '', false, 'rss');
					$rss = true;
				}
				if (($_zenpage_enabled) && (getOption('RSS_articles'))) {
					if ($rss) {
						$separ = ' | ';
					} else {
						$separ = '';
					};
					printRSSLink('News', $separ, NEWS_LABEL, '', false, 'rss');
				}
				?>
				<script type="text/javascript">
					//<![CDATA[
					$('.rss').prepend('<img alt="RSS Feed" src="<?php echo $_themeroot; ?>/images/rss.png">&nbsp;');
					//]]>
				</script>
			</div>
		<?php } ?>

		<div id="copyright">
			<?php
			echo getMainSiteName();
			printCustomPageURL(gettext('Archive View'), 'archive', '', ' | ');
			if (extensionEnabled('daily-summary')) {
				printDailySummaryLink(gettext('Daily summary'), '', ' | ');
			}
			if (extensionEnabled('user_login-out')) {
				printUserLogin_out(' | ', '', 2);
			}
			if ((!npg_loggedin()) && (extensionEnabled('register_user'))) {
				printRegisterURL(gettext('Register'), ' | ');
			}
			?>
		</div>

		<div id="zpcredit">
			<?php
			print_SW_Link();
			?>
			<?php
			if (($_gallery_page == 'image.php') ||
							(($_gallery_page == 'album.php') && (getOption('use_galleriffic')) && (getNumImages() > 0)) ||
							(($_zenpage_enabled) && (is_NewsArticle()))) {
				?>
				<img id="icon-help" src="<?php echo $_themeroot; ?>/images/help.png" title="<?php echo gettext('You can browse with the arrows keys of your keyboard'); ?>" alt="help" />
			<?php } ?>
		</div>
	</div>
</div>		<!-- END #FOOTER -->
</div>			<!-- END #PAGE -->

<?php
npgFilters::apply('theme_body_close');
?>

</body>
</html>
<!-- zpArdoise 1.4.13 - a netPhotoGraphics/ZenPage theme by Vincent3569  -->