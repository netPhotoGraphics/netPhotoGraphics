<nav class="breadcrumb" aria-label="<?php echo gettext('Breadcrumb'); ?>">
    <?php
    if (getGalleryTitle()) {
        printGalleryIndexURL(' » ', getGalleryTitle(), false);
    }
    printParentBreadcrumb('', ' » ', ' » ');
    if ($_gallery_page == 'image.php') {
        printAlbumBreadcrumb('', ' » ');
        printBareImageTitle();
    } else {
        printAlbumTitle();
    }
    ?>
</nav>
