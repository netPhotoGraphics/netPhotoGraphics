<footer id="footer" class="footer">
	<div class="container">
		<div id="copyright">
			<?php
			echo getMainSiteName();
			if (getOption('zpB_show_archive')) {
				printCustomPageURL(gettext('Archive View'), 'archive', '', ' | ');
			}
			if (extensionEnabled('daily-summary')) {
				printDailySummaryLink(gettext('Daily summary'), '', ' | ', '');
			}
			?>
		</div>
		<div>
			<?php print_SW_Link(); ?> & <a href="https://getbootstrap.com/docs/3.4/" target="_blank" title="Bootstrap">Bootstrap</a>
		</div>
	</div>
</footer>
	</body>
	<?php npgFilters::apply('theme_body_close'); ?>
</html>
<!-- Bootstrap 2.3 - a theme by Vincent3569 -->