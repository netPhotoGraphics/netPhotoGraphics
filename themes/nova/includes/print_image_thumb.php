<div class="image-grid">
    <?php
    while (next_image()) {
        $fullimage = getFullImageURL();
        if (empty($fullimage)) {
            continue;
        }
        $isPhoto = $_current_image->isPhoto();
        $isVideo = $_current_image->isVideo();
        $isAudio = false;
        if ($isVideo) {
            $suffix = strtolower(getSuffix($_current_image->getFileName()));
            $isAudio = ($suffix == 'mp3' || $suffix == 'm4a');
        }
        $objectclass = strtolower(get_class($_current_image));
        $isTextObject = $objectclass == 'textobject';
        $isEmbedLink = $objectclass == 'externallink';

        // how does the thumbnail open?
        $attrs = 'href="' . html_encode($fullimage) . '" data-fancybox="images"';
        if ($isPhoto || ($isVideo && !$isAudio)) {
            // default
        } else if ($isVideo && $isAudio) {
            $attrs = 'href="javascript:;" data-type="iframe" data-src="' . html_encode($fullimage) . '" data-fancybox="images"';
        } else if ($isEmbedLink) {
            $attrs = 'href="' . html_encode(zpB_getLink($_current_image->getContent())) . '" data-fancybox="images"';
        } else if ($isTextObject) {
            $attrs = 'href="javascript:;" data-src="#item' . $_current_image->getIndex() . '" data-fancybox="images"';
        } else {
            $attrs = 'href="' . html_encode(getImageURL()) . '"';
        }
        if ($_gallery_page == 'search.php') {
            // search results lead to the image page, not to the full-screen lightbox
            $attrs = 'href="' . html_encode(getImageURL()) . '"';
        }
        ?>
        <figure class="image-card">
            <a class="image-card__thumb" <?php echo $attrs; ?> title="<?php echo html_encode(getBareImageTitle()); ?>">
                <?php printImageThumb(getBareImageTitle(), 'image-card__image'); ?>
                <div class="caption" hidden>
                    <h4><?php printBareImageTitle(); ?></h4>
                    <?php printImageDesc(); ?>
                </div>
            </a>
            <?php if ($isTextObject) { ?>
                <div id="item<?php echo $_current_image->getIndex(); ?>" hidden><?php echo $_current_image->getContent(); ?></div>
            <?php } ?>
            <figcaption class="image-card__title">
                <a href="<?php echo html_encode(getImageURL()); ?>" title="<?php echo html_encode(getBareImageTitle()); ?>"><?php printBareImageTitle(); ?></a>
            </figcaption>
        </figure>
    <?php } ?>
</div>
