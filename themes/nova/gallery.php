<?php include('includes/header.php'); ?>

<header class="page-header">
    <?php if (getGalleryTitle()) { ?>
        <h1 class="page-title"><?php printGalleryTitle(); ?></h1>
    <?php } ?>
    <?php if (!getOption('zpB_homepage') && getGalleryDesc()) { ?>
        <div class="page-description prose"><?php printGalleryDesc(); ?></div>
    <?php } ?>
</header>

<?php include('includes/print_pagination.php'); ?>
<?php if (isAlbumPage()) { include('includes/print_album_thumb.php'); } ?>
<?php include('includes/print_pagination.php'); ?>

<?php include('includes/footer.php'); ?>
