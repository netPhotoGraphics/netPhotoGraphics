<?php include('includes/header.php'); ?>

<h1 class="visually-hidden"><?php echo html_encode(getMainSiteName()); ?></h1>

<?php if (file_exists(__DIR__ . '/images/logo.png')) { ?>
    <section class="home-hero">
        <img class="home-hero__logo" src="<?php echo $_themeroot; ?>/images/logo.png" alt="<?php echo html_encode(getMainSiteName()); ?>">
    </section>
<?php } ?>

<?php if (trim(strip_tags(getGalleryDesc()))) { ?>
    <div class="home-layout">
        <div class="home-description prose"><?php printGalleryDesc(); ?></div>
    </div>
<?php } ?>

<?php nova_printHomePhotos(); ?>

<?php if ($_zenpage_news_enabled && getNumNews() > 0 && getOption('zpB_latest_news_homepage')) { ?>
    <aside class="home-news">
        <h2 class="home-news__title"><?php echo NEWS_LABEL; ?></h2>
        <?php printLatestNews(nova_homeNewsCount(), '', true, true, 300, false); ?>
    </aside>
<?php } ?>

<?php nova_printHomeStats(); ?>

<?php include('includes/footer.php'); ?>
