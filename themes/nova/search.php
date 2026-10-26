<?php include('includes/header.php'); ?>

<header class="page-header">
    <h1 class="page-title"><?php echo gettext('Search'); ?></h1>
</header>

<div class="search-form-wrap"><?php nova_printSearchForm(); ?></div>

<div class="search-wrap">
    <?php
    $numimages = getNumImages();
    $numalbums = getNumAlbums();
    $total_gallery = $numimages + $numalbums;

    $numnews = $numpages = 0;
    if (class_exists('CMS') && !isArchive()) {
        if ($_zenpage_news_enabled) {
            $numnews = getNumNews();
        }
        if ($_zenpage_pages_enabled) {
            $numpages = getNumPages();
        }
    }
    $total = $total_gallery + $numnews + $numpages;

    $searchwords = getSearchWords();
    $searchdate = getSearchDate();
    if (!empty($searchdate)) {
        if (!empty($searchwords)) {
            $searchwords .= ': ';
        }
        $searchwords .= $searchdate;
    }
    ?>

    <p class="search-summary">
        <?php
        if ($total == 0) {
            echo gettext('Sorry, no matches found. Try refining your search.');
        } else {
            printf(ngettext('%1$u Hit for <em>%2$s</em>', '%1$u Hits for <em>%2$s</em>', $total), $total, html_encode($searchwords));
        }
        ?>
    </p>

    <?php
    if (getOption('search_no_albums')) {
        if ($numimages > 0) {
            echo '<h2 class="section-title">' . sprintf(gettext('Images (%s)'), $numimages) . '</h2>';
        }
    } else if (getOption('search_no_images')) {
        if ($numalbums > 0) {
            echo '<h2 class="section-title">' . sprintf(gettext('Albums (%s)'), $numalbums) . '</h2>';
        }
    } else if ($total_gallery > 0) {
        echo '<h2 class="section-title">' . sprintf(gettext('Albums (%1$s) &amp; Images (%2$s)'), $numalbums, $numimages) . '</h2>';
    }

    include('includes/print_pagination.php');
    if (getNumAlbums() > 0) {
        include('includes/print_album_thumb.php');
    }
    if (getNumImages() > 0) {
        include('includes/print_image_thumb.php');
    }
    include('includes/print_pagination.php');

    if (class_exists('CMS')) {
        if ($_zenpage_news_enabled && $numnews > 0) {
            ?>
            <h2 class="section-title"><?php printf(gettext('Articles (%s)'), $numnews); ?></h2>
            <?php while (next_news()) { ?>
                <article class="list-post list-post--compact">
                    <h3 class="post-title"><?php printNewsURL(); ?></h3>
                    <div class="post-content"><?php echo shortenContent(getBare(getNewsContent()), getOption('zpB_exerpt_length')); ?></div>
                </article>
                <?php
            }
        }
        if ($_zenpage_pages_enabled && $numpages > 0) {
            ?>
            <h2 class="section-title"><?php printf(gettext('Pages (%s)'), $numpages); ?></h2>
            <?php while (next_page()) { ?>
                <article class="list-post list-post--compact">
                    <h3 class="post-title"><?php printPageURL(); ?></h3>
                    <div class="post-content"><?php echo shortenContent(getBare(getPageContent()), getOption('zpB_exerpt_length')); ?></div>
                </article>
                <?php
            }
        }
    }
    ?>
</div>

<?php include('includes/footer.php'); ?>
