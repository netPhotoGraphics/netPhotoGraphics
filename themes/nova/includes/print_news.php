<article class="<?php echo $news_class; ?>">
    <div class="post-date" aria-hidden="true">
        <span class="month"><?php echo formattedDate('M', strtotime($_CMS_current_article->getDateTime())); ?></span>
        <span class="day"><?php echo date('d', strtotime($_CMS_current_article->getDateTime())); ?></span>
        <span class="year"><?php echo date('y', strtotime($_CMS_current_article->getDateTime())); ?></span>
    </div>
    <div class="post-main">
        <h2 class="post-title"><?php is_NewsArticle() ? printNewsTitle() : printNewsURL(); ?></h2>
        <div class="post-meta"><?php printNewsCategories('', '', 'tag-list'); ?></div>
        <div class="post-content">
            <?php
            if (is_NewsArticle()) {
                printNewsContent();
                printCodeblock(1);
            } else {
                // one way to continue a shortened article (theme option):
                // "readmore" = the "read more" link of the Zenpage options (default), "dots" = the shortening indicator as a link
                $nova_mode = getOption('zpB_news_more');
                if ($nova_mode == 'dots') {
                    $nova_more = get_language_string(READ_MORE);
                    $nova_dots = ' <a class="readmore-dots" href="' . html_encode($_CMS_current_article->getLink()) . '" title="' . html_encode($nova_more) . '">' . html_encode(SHORTENINDICATOR) . '</a>';
                    printNewsContent(false, $nova_dots, '');
                } else {
                    printNewsContent(false, ' …', NULL);
                }
            }
            ?>
        </div>
    </div>
</article>
