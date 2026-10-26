<?php if (nova_albumHasGeodata()) { ?>
    <section class="panel osm_panel">
        <h2 class="panel__title"><?php echo nova_icon('map'); ?> <?php echo gettext('Map'); ?></h2>
        <div class="panel__body"><?php printOpenStreetMap(); ?></div>
    </section>
<?php } ?>
