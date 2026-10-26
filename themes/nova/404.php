<?php include('includes/header.php'); ?>

<header class="page-header">
    <h1 class="page-title"><?php echo gettext('Object not found'); ?></h1>
    <nav class="breadcrumb"><?php printGalleryIndexURL(' » ', getGalleryTitle(), false); ?></nav>
</header>
<p><?php print404status(isset($album) ? $album : NULL, isset($image) ? $image : NULL, $obj); ?></p>

<?php include('includes/footer.php'); ?>
