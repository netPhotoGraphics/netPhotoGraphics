<?php include('includes/header.php'); ?>

<?php include('includes/print_breadcrumb.php'); ?>

<header class="page-header">
    <h1 class="page-title"><?php echo html_encode(getBareAlbumTitle()); ?></h1>
    <?php if (getAlbumDesc()) { ?>
        <div class="page-description prose"><?php printAlbumDesc(); ?></div>
    <?php } ?>
</header>

<?php include('includes/print_pagination.php'); ?>
<?php
if (isAlbumPage()) {
    include('includes/print_album_thumb.php');
}
if (isImagePage()) {
    include('includes/print_image_thumb.php');
}
?>
<?php include('includes/print_pagination.php'); ?>

<?php if (npg_loggedin() && extensionEnabled('favoritesHandler')) { ?>
    <div class="favorites" data-instance-placeholder="<?php echo html_encode(gettext('Favorites list name (optional)')); ?>"><?php printAddToFavorites($_current_album); ?></div>
<?php } ?>

<?php
if (nova_option('zpB_show_map', true)) {
    include('includes/print_map.php');
}
if (npg_loggedin(ADMIN_RIGHTS)) {
    // visible only to administrators in the page source - helps to find out why a map is missing
    echo '<!-- nova map: show=' . (int) nova_option('zpB_show_map', true) . ' plugin=' . html_encode((string) simplemap::mapPlugin()) . ' images_page=' . (int) isImagePage() . ' geodata=' . (int) nova_albumHasGeodata() . ' -->';
}
?>

<?php if (extensionEnabled('comment_form')) { include('includes/print_comment.php'); } ?>

<?php include('includes/footer.php'); ?>
