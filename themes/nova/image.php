<?php include('includes/header.php'); ?>

<?php include('includes/print_breadcrumb.php'); ?>

<nav class="photo-pager" aria-label="<?php echo gettext('Images'); ?>">
    <?php if (hasPrevImage()) { ?>
        <a class="button" rel="prev" href="<?php echo html_encode(getPrevImageURL()); ?>" title="<?php echo gettext('Previous Image'); ?>">← <?php echo gettext('prev'); ?></a>
    <?php } else { ?>
        <span class="button is-disabled">← <?php echo gettext('prev'); ?></span>
    <?php } ?>
    <?php if (hasNextImage()) { ?>
        <a class="button" rel="next" href="<?php echo html_encode(getNextImageURL()); ?>" title="<?php echo gettext('Next Image'); ?>"><?php echo gettext('next'); ?> →</a>
    <?php } else { ?>
        <span class="button is-disabled"><?php echo gettext('next'); ?> →</span>
    <?php } ?>
</nav>

<div class="photo-stage">
    <?php printDefaultSizedImage(getBareImageTitle(), 'photo-stage__image'); ?>
</div>

<section class="photo-info">
    <div class="photo-info__main">
        <h1 class="photo-info__title"><?php printBareImageTitle(); ?></h1>
        <div class="photo-info__desc prose"><?php printImageDesc(); ?></div>
        <?php if (getOption('zpB_show_tags') && getTags()) { ?>
            <div class="photo-info__tags"><?php printTags('links', NULL, 'tag-list', NULL); ?></div>
        <?php } ?>
    </div>

    <aside class="photo-info__side">
        <?php if (extensionEnabled('rating')) { ?>
            <div class="rating"><?php printRating(); ?></div>
        <?php } ?>

        <?php if (npg_loggedin() && extensionEnabled('favoritesHandler')) { ?>
            <div class="favorites" data-instance-placeholder="<?php echo html_encode(gettext('Favorites list name (optional)')); ?>"><?php printAddToFavorites($_current_image); ?></div>
        <?php } ?>

        <div class="photo-info__actions">
            <a class="button" href="<?php echo html_encode(getFullImageURL()); ?>" title="<?php echo html_encode(getBareImageTitle()); ?>"><?php echo gettext('View full size'); ?></a>
        </div>
    </aside>
</section>

<?php
if (nova_option('zpB_show_exif', true)) {
    nova_printExif();
}
if (npg_loggedin(ADMIN_RIGHTS)) {
    // visible only to administrators in the page source - helps to find out why no EXIF panel is shown
    $nova_fields = getImageMetaData($_current_image, true);
    echo '<!-- nova exif: show=' . (int) nova_option('zpB_show_exif', true) . ' hasMetadata=' . (int) $_current_image->get('hasMetadata') . ' displayed_fields=' . ($nova_fields ? count($nova_fields) : 0) . ' -->';
}
?>

<?php if (extensionEnabled('comment_form')) { include('includes/print_comment.php'); } ?>

<?php include('includes/footer.php'); ?>
