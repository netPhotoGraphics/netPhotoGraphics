<?php if (getOption('gmap_display') == 'colorbox') { ?>
    <p class="alert alert-danger"><?php echo gettext('The theme doesn\'t support colorbox option for googlemap plugin.'); ?></p>
<?php } else if (nova_albumHasGeodata()) { ?>
    <section class="panel panel--gmap">
        <h2 class="panel__title"><?php echo nova_icon('map'); ?> <?php echo gettext('Google Map'); ?></h2>
        <div class="panel__body"><?php printGoogleMap('', 'gmap_collapse'); ?></div>
    </section>
<?php } ?>
