<?php include('includes/header.php'); ?>

<header class="page-header">
    <h1 class="page-title"><?php
        if (is_NewsArticle()) {
            ob_start();
            printZenpageItemsBreadcrumb();
            $nova_crumb = trim(ob_get_clean());
            echo $nova_crumb !== '' ? $nova_crumb : html_encode(NEWS_LABEL);
        } else {
            echo html_encode(NEWS_LABEL);
            printCurrentNewsCategory(' | ' . gettext('Category') . ' : ');
            printCurrentNewsArchive(' | ');
        }
        ?></h1>
</header>

<?php if (is_NewsArticle()) { $news_class = 'post'; ?>

    <?php if (getPrevNewsURL() || getNextNewsURL()) { ?>
        <nav class="photo-pager photo-pager--news">
            <?php if (getPrevNewsURL()) { $article_url = getPrevNewsURL(); ?>
                <a class="button" rel="prev" href="<?php echo $article_url['link']; ?>" title="<?php echo html_encode($article_url['title']); ?>">← <?php echo html_encode(shortenContent($article_url['title'], $zpB_shorten_title_size, ' (...)')); ?></a>
            <?php } ?>
            <?php if (getNextNewsURL()) { $article_url = getNextNewsURL(); ?>
                <a class="button" rel="next" href="<?php echo $article_url['link']; ?>" title="<?php echo html_encode($article_url['title']); ?>"><?php echo html_encode(shortenContent($article_url['title'], $zpB_shorten_title_size, ' (...)')); ?> →</a>
            <?php } ?>
        </nav>
    <?php } ?>

    <div class="content-layout<?php echo getNewsExtraContent() ? ' has-aside' : ''; ?>">
        <div><?php include('includes/print_news.php'); ?></div>
        <?php if (getNewsExtraContent()) { ?>
            <aside class="extra-content prose"><?php printNewsExtraContent(); ?></aside>
        <?php } ?>
    </div>

    <?php if (extensionEnabled('comment_form')) { include('includes/print_comment.php'); } ?>

<?php } else { $news_class = 'list-post'; ?>

    <?php nova_printNewsCategories(); ?>

    <nav class="pagination-wrapper"><?php printNewsPageListWithNav('»', '«', true, 'pagination', true, 7); ?></nav>

    <div class="news-wrap">
        <?php while (next_news()) { include('includes/print_news.php'); } ?>
    </div>

    <nav class="pagination-wrapper"><?php printNewsPageListWithNav('»', '«', true, 'pagination', true, 7); ?></nav>

<?php } ?>

<?php include('includes/footer.php'); ?>
