<div class="album-grid">
    <?php while (next_album()) { ?>
        <article class="album-card">
            <a class="album-card__link" href="<?php echo html_encode(getAlbumURL()); ?>" title="<?php echo html_encode(getBareAlbumTitle()); ?>">
                <div class="album-card__media">
                    <?php
                    printCustomAlbumThumbImage(getBareAlbumTitle(), array(
                        'width' => getOption('zpB_album_thumb_width'),
                        'height' => getOption('zpB_album_thumb_height'),
                        'cw' => getOption('zpB_album_thumb_width'),
                        'ch' => getOption('zpB_album_thumb_height')), 'album-card__image');
                    ?>
                </div>
                <h2 class="album-card__title"><?php echo html_encode(getBareAlbumTitle()); ?></h2>
            </a>
        </article>
    <?php } ?>
</div>
