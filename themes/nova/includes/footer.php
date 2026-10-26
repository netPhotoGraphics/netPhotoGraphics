        </main>

        <footer class="site-footer">
            <div class="site-footer__inner">
                <div class="site-footer__primary">
                    <div class="site-footer__branding"><?php echo html_encode(getMainSiteName()); ?></div>
                    <ul class="site-footer__menu">
                        <?php if (getOption('zpB_show_archive')) { ?>
                            <li><?php printCustomPageURL(gettext('Archive View'), 'archive'); ?></li>
                        <?php } ?>
                        <?php if (extensionEnabled('daily-summary')) { ?>
                            <li><?php printDailySummaryLink(gettext('Daily summary'), '', '', ''); ?></li>
                        <?php } ?>
                        <?php
                        if (extensionEnabled('rss')) {
                            if ($_zenpage_news_enabled && getOption('RSS_articles')) {
                                echo '<li>';
                                printRSSLink('News', '', 'RSS ' . NEWS_LABEL, '', false, 'rss');
                                echo '</li>';
                            } else if (getOption('RSS_album_image')) {
                                echo '<li>';
                                printRSSLink('Gallery', '', 'RSS', '', false, 'rss');
                                echo '</li>';
                            }
                        }
                        ?>
                    </ul>
                </div>

                <?php if (nova_option('zpB_show_stats', true)) { $stats = nova_getStats(); ?>
                    <div class="site-footer__statistics">
                        <div class="site-statistic">
                            <span class="site-statistic__label"><?php echo gettext('Images with GPS coordinates'); ?></span>
                            <span class="site-statistic__value"><strong><?php echo number_format($stats['gps'], 0, ',', '&nbsp;'); ?></strong>&nbsp;(<?php echo $stats['gps_percent']; ?>&nbsp;%)</span>
                        </div>
                        <div class="site-statistic">
                            <span class="site-statistic__label"><?php echo gettext('Items with descriptions'); ?></span>
                            <span class="site-statistic__value"><strong><?php echo number_format($stats['described'], 0, ',', '&nbsp;'); ?></strong>&nbsp;(<?php echo $stats['described_percent']; ?>&nbsp;%)</span>
                        </div>
                    </div>
                <?php } ?>

                <div class="site-footer__secondary"><?php print_SW_Link(); ?></div>
            </div>
        </footer>

        <button type="button" class="scroll-top" hidden aria-label="<?php echo gettext('Top'); ?>"><?php echo nova_icon('up', 22); ?></button>

        <?php
        if (npg_loggedin(ADMIN_RIGHTS)) {
            // visible only to administrators in the page source - helps to check that the translation file is loaded
            $nova_loc = getOption('locale');
            echo '<!-- nova i18n: locale=' . html_encode((string) $nova_loc) .
            ' core.mo=' . (int) file_exists(USER_PLUGIN_SERVERPATH . 'locale/' . $nova_loc . '/LC_MESSAGES/core.mo') .
            ' sample="' . html_encode(gettext('Dark mode')) . '" -->';
        }
        ?>
        <?php npgFilters::apply('theme_body_close'); ?>
    </body>
</html>
