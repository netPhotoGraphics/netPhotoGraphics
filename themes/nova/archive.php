<?php include('includes/header.php'); ?>

<header class="page-header">
    <h1 class="page-title"><?php echo gettext('Search'); ?></h1>
</header>

<div class="search-form-wrap"><?php nova_printSearchForm(); ?></div>

<div class="archive-grid">
    <section class="panel" id="archives">
        <h2 class="panel__title"><?php echo gettext('Gallery archive'); ?></h2>
        <div class="panel__body"><?php printAllDates('date-list', 'year', 'month', 'desc'); ?></div>
    </section>

    <?php if ($_zenpage_news_enabled) { ?>
        <section class="panel" id="newsarchives">
            <h2 class="panel__title"><?php echo NEWS_LABEL; ?></h2>
            <div class="panel__body"><?php printNewsArchive('date-list', 'year', 'month', null, false, 'desc'); ?></div>
        </section>
    <?php } ?>
</div>

<?php if (getOption('zpB_show_tags')) { ?>
    <section class="panel" id="tags">
        <h2 class="panel__title"><?php echo gettext('Tags'); ?></h2>
        <div class="panel__body"><?php printAllTagsAs('list', 'tag-list', 'abc', true, true); ?></div>
    </section>
<?php } ?>

<?php if ($_zenpage_news_enabled) { ?>
    <section class="panel" id="cat">
        <h2 class="panel__title"><?php echo gettext('Categories'); ?></h2>
        <div class="panel__body"><?php nova_printNewsCategories(false); ?></div>
    </section>
<?php } ?>

<?php include('includes/footer.php'); ?>
