<?php
switch ($_gallery_page) {
    case 'album.php':
        $comments_open = getOption('comment_form_albums');
        $comments_allowed = $_current_album->getCommentsAllowed();
        break;
    case 'image.php':
        $comments_open = getOption('comment_form_images');
        $comments_allowed = $_current_image->getCommentsAllowed();
        break;
    case 'pages.php':
        $comments_open = getOption('comment_form_pages');
        $comments_allowed = $_CMS_current_page->getCommentsAllowed();
        break;
    case 'news.php':
        $comments_open = getOption('comment_form_articles');
        $comments_allowed = $_CMS_current_article->getCommentsAllowed();
        break;
    default:
        return;
}

if ($comments_open && ($comments_allowed || getCommentCount() > 0)) {
    $num = getCommentCount();
    ?>
    <details class="panel panel--comments" id="comments-panel" open>
        <summary class="panel__title">
            <?php echo nova_icon('comment'); ?>
            <?php echo $num == 0 ? gettext('No Comments') : sprintf(ngettext('%u Comment', '%u Comments', $num), $num); ?>
        </summary>
        <div class="panel__body"><?php printCommentForm(true, NULL, true); ?></div>
    </details>
    <?php
}
