<?php
if (extensionEnabled('favoritesHandler')) {
    include('includes/header.php');
    ?>
    <nav class="breadcrumb">
        <?php printCustomPageURL(getGalleryTitle() ? getGalleryTitle() : gettext('Gallery'), 'gallery', '', '', ' » '); ?><?php printAlbumTitle(); ?>
    </nav>

    <header class="page-header">
        <h1 class="page-title"><?php printAlbumTitle(); ?></h1>
        <?php if (getAlbumDesc()) { ?><div class="page-description prose"><?php printAlbumDesc(); ?></div><?php } ?>
    </header>

    <?php
    include('includes/print_pagination.php');
    if (isAlbumPage()) {
        include('includes/print_album_thumb.php');
    }
    if (isImagePage()) {
        include('includes/print_image_thumb.php');
    }
    include('includes/print_pagination.php');

    include('includes/footer.php');
} else {
    include(CORE_SERVERPATH . '404.php');
}
