<?php
switch (simplemap::mapPlugin()) {
    case 'googleMap':
        include(__DIR__ . '/print_googlemap.php');
        break;
    case 'openStreetMap':
        include(__DIR__ . '/print_osm.php');
        break;
}
