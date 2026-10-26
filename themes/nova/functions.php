<?php

/*
 * Nova - theme for netPhotoGraphics
 * Author: Martin Kroul
 * Inspired by the "Bootstrap" theme by Vincent3569, whose functionality and
 * template structure it follows.
 * Developed in collaboration with Claude (AI assistant by Anthropic).
 * Released under the GNU General Public License, version 2 or later,
 * like netPhotoGraphics.
 */

// force UTF-8 Ø


if (!OFFSET_PATH) {

    // options forced by the theme (avoid conflicts with plugins)
    setOption('comment_form_toggle', false, true);
    setOption('comment_form_pagination', false, true);
    setOption('tinymce_comments', null, true);
    setOption('user_logout_login_form', 1, true);
    setOption('gmap_display', 'show', true);

    // thumbnail sizes (runtime only, not stored in the database)
    setOption('thumb_size', 300, false);
    setOption('thumb_crop_width', 300, false);
    setOption('thumb_crop_height', 300, false);
    setOption('zpB_album_thumb_width', 600, false);
    setOption('zpB_album_thumb_height', 400, false);

    $zpB_shorten_title_size = 50;

    $_current_page_check = 'my_checkPageValidity';

    $_zenpage_news_enabled = class_exists('CMS') && hasNews();
    $_zenpage_pages_enabled = class_exists('CMS') && hasPages();
}

/**
 * Sub pages of the current page as one row of chips.
 * printPageMenu('list-sub') opens an extra nested <ul> for every item on pages deeper than level 2,
 * which pushed all items except the first one into a second row. The lists are flattened here.
 */
function nova_printSubPages() {
    ob_start();
    printPageMenu('list-sub', '', '', 'pages-list', 'active');
    $html = trim(ob_get_clean());
    if ($html === '') {
        return;
    }
    $html = preg_replace('~</?ul[^>]*>~i', '', $html);
    $html = str_replace('<a href', '<a class="chip" href', $html);
    echo '<nav class="chip-bar chip-bar--pages"><ul class="chip-list">' . $html . '</ul></nav>';
}

/**
 * Search form with a translated placeholder.
 */
function nova_printSearchForm() {
    printSearchForm(NULL, 'search', false, '', NULL, NULL, NULL, NULL, gettext('What are you looking for?'));
}

/**
 * Collapsible EXIF / metadata panel (closed by default, like the comments).
 * Shows the fields selected for display in Options > Image > Metadata.
 * @return bool true when a panel was printed
 */
function nova_printExif() {
    global $_current_image, $_exifvars;
    $data = getImageMetaData($_current_image, true);
    if (!$data) {
        return false;
    }
    echo '<details class="panel panel--exif" id="exif-panel"><summary class="panel__title">' . nova_icon('info') . ' ' .
    html_encode(gettext('Image Info')) . '</summary><div class="panel__body"><table class="exif-table">';
    foreach ($data as $field => $value) {
        $label = $_exifvars[$field][METADATA_DISPLAY_TEXT];
        echo '<tr><th scope="row">' . html_encode($label) . '</th><td>' . html_encode(strval($value)) . '</td></tr>';
    }
    echo '</table></div></details>';
    return true;
}

/**
 * Menu entry for the favorites. The plugin's printFavoritesURL() prints nothing for the list that is
 * currently displayed, which left an empty menu item on the favorites page.
 */
function nova_printFavoritesMenu() {
    global $_myFavorites, $_gallery_page;
    if ($_gallery_page == 'favorites.php' && is_object($_myFavorites)) {
        $text = get_language_string(getOption('favorites_linktext'));
        $instance = $_myFavorites->instance;
        $display = $text . ($instance ? '[' . $instance . ']' : '');
        echo '<a href="' . html_encode($_myFavorites->getLink(NULL, $instance)) . '" class="favorite_link">' . html_encode($display) . '</a>';
    }
    printFavoritesURL();
}

/**
 * News categories as a row of labelled chips ("Categories: All news (72) | Photos (38) | ...").
 * Replaces printAllNewsCategories(), whose output is a plain list with an untranslated "articles" text.
 */
function nova_printNewsCategories($label = true) {
    global $_CMS, $_CMS_current_category, $_gallery_page;
    if (!class_exists('CMS')) {
        return;
    }
    $items = $_CMS->getAllCategories();
    if (empty($items)) {
        return;
    }
    $isIndex = ($_gallery_page == 'news.php') && !is_NewsCategory() && !is_NewsArchive() && !is_NewsArticle();

    $save = $_CMS_current_category;
    $_CMS_current_category = NULL;
    $total = count($_CMS->getArticles(0));
    $_CMS_current_category = $save;

    echo '<nav class="chip-bar" aria-label="' . html_encode(gettext('Categories')) . '">';
    if ($label) {
        echo '<span class="chip-bar__label">' . html_encode(gettext('Categories')) . ':</span>';
    }
    echo '<ul class="chip-list">';
    echo '<li><a class="chip' . ($isIndex ? ' is-active' : '') . '" href="' . html_encode(getNewsIndexURL()) . '">' .
    html_encode(gettext('All news')) . ' <small>(' . $total . ')</small></a></li>';
    foreach ($items as $item) {
        $cat = newCategory($item['titlelink']);
        $count = count($cat->getArticles());
        if (!$count) {
            continue;
        }
        $active = is_object($_CMS_current_category) && $_CMS_current_category->getTitlelink() == $item['titlelink'];
        echo '<li><a class="chip' . ($active ? ' is-active' : '') . '" href="' . html_encode($cat->getLink()) . '">' .
        html_encode($cat->getTitle()) . ' <small>(' . $count . ')</small></a></li>';
    }
    echo '</ul></nav>';
}

/**
 * Boolean theme option with a fallback: options introduced in a newer version of the theme
 * have no stored value until the theme options page has been saved once.
 */
function nova_option($key, $default = true) {
    $value = getOption($key);
    return ($value === NULL) ? $default : (bool) $value;
}

function my_checkPageValidity($request, $gallery_page, $page) {
    if (($gallery_page == 'gallery.php') || ($gallery_page == 'home.php')) {
        $gallery_page = 'index.php';
    }
    return checkPageValidity($request, $gallery_page, $page);
}

/**
 * Inline SVG icon (stroke style, inherits currentColor).
 */
function nova_icon($name, $size = 18) {
    $paths = array(
        'map'     => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'comment' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>',
        'search'  => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
        'info'    => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'user'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'login'   => '<path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4M10 8l-4 4 4 4M6 12h10" transform="rotate(180 12 12)"/>',
        'logout'  => '<path d="M9 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h4M16 8l4 4-4 4M20 12H9"/>',
        'up'      => '<path d="M6 15l6-6 6 6"/>',
        'menu'    => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'moon'    => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
        'sun'     => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'close'   => '<path d="M6 6l12 12M18 6L6 18"/>',
    );
    if (!isset($paths[$name])) {
        return '';
    }
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
}

/**
 * Gallery statistics for the footer.
 */
function nova_getStats() {
    // cached for 6 hours - the counts are expensive on big galleries
    $cached = getOption('nova_stats_cache');
    if ($cached) {
        $data = json_decode($cached, true);
        if (is_array($data) && isset($data['time']) && isset($data['albums']) && (time() - $data['time']) < 21600) {
            return $data;
        }
    }
    $data = nova_computeStats();
    $data['time'] = time();
    setOption('nova_stats_cache', json_encode($data), true);
    return $data;
}

function nova_computeStats() {
    $row = query_single_row('SELECT count(*) FROM ' . prefix('images'));
    $total = (int) reset($row);

    $row = query_single_row('SELECT count(*) FROM ' . prefix('images') . ' WHERE `gpslatitude` IS NOT NULL AND `gpslongitude` IS NOT NULL');
    $gps = (int) reset($row);

    $row = query_single_row('SELECT count(*) FROM ' . prefix('images') .
            ' WHERE (`title` IS NOT NULL AND `title` != ""' .
            ' AND `title` != REPLACE(`filename`, ".jpg", "")' .
            ' AND `title` != REPLACE(`filename`, ".avif", "")' .
            ' AND `title` != REPLACE(`filename`, ".webp", "")' .
            ' AND `title` != REPLACE(`filename`, ".pdf", "")' .
            ' AND `title` != REPLACE(`filename`, ".docx", "")' .
            ' AND `title` != REPLACE(`filename`, ".mp4", ""))');
    $described = (int) reset($row);

    $count = function ($table, $where = '') {
        $r = query_single_row('SELECT count(*) FROM ' . prefix($table) . $where);
        return $r ? (int) reset($r) : 0;
    };
    $albums = $count('albums', ' WHERE `show` = 1');
    $pages = 0;
    $news = 0;
    if (extensionEnabled('zenpage')) {
        $pages = $count('pages', ' WHERE `show` = 1');
        $news = $count('news', ' WHERE `show` = 1');
    }
    $tags = $count('tags');

    $d = max($total, 1);
    return array(
        'albums' => $albums,
        'pages' => $pages,
        'news' => $news,
        'tags' => $tags,
        'total' => $total,
        'gps' => $gps,
        'gps_percent' => round($gps / $d * 100),
        'described' => $described,
        'described_percent' => round($described / $d * 100),
    );
}

/**
 * Does the current album contain at least one image with GPS coordinates?
 */
function nova_albumHasGeodata() {
    global $_current_album;
    if (!is_object($_current_album) || !isImagePage()) {
        return false;
    }
    if (!$_current_album->isDynamic()) {
        $row = query_single_row('SELECT `id` FROM ' . prefix('images') .
                ' WHERE `albumid` = ' . (int) $_current_album->getID() .
                ' AND `gpslatitude` IS NOT NULL AND `gpslongitude` IS NOT NULL LIMIT 1');
        return !empty($row);
    }
    foreach ($_current_album->getImages() as $file) {
        $image = newImage($_current_album, $file);
        if (!empty($image->getGPSLatitude()) && !empty($image->getGPSLongitude())) {
            return true;
        }
    }
    return false;
}

/**
 * Source link of an online video embedded in a txt/htm/html file.
 */
function zpB_getLink($content) {
    $link = false;
    if (preg_match('/<iframe/', $content) && preg_match('/src="([^"]+)"/', $content, $result)) {
        $link = pathurlencode($result[1]);
    }
    return $link;
}

/* ------------------------------------------------------------------
 * Photo map page (photomap.php): server side helpers.
 * The map never receives all points at once. The browser asks for the
 * visible area only and the server answers with grid clusters (zoomed
 * out) or single points (zoomed in).
 * ------------------------------------------------------------------ */

/**
 * Albums the current visitor may not see (cached for 10 minutes in the session
 * because evaluating every album can be slow in big galleries).
 */
function nova_geoHiddenAlbums() {
    if (npg_loggedin(ADMIN_RIGHTS | MANAGE_ALL_ALBUM_RIGHTS)) {
        return array();
    }
    $key = 'nova_geo_hidden';
    if (isset($_SESSION[$key]) && is_array($_SESSION[$key]) && $_SESSION[$key]['t'] > time() - 600) {
        return $_SESSION[$key]['ids'];
    }
    $ids = array_map('intval', (array) getNotViewableAlbums());
    $_SESSION[$key] = array('t' => time(), 'ids' => $ids);
    return $ids;
}

/**
 * Derived table with numeric coordinates of every visible geotagged image.
 */
function nova_geoSource() {
    $lat = "CAST(REPLACE(`gpslatitude`, ',', '.') AS DECIMAL(10,6))";
    $lon = "CAST(REPLACE(`gpslongitude`, ',', '.') AS DECIMAL(10,6))";
    $where = "`show` = 1 AND `gpslatitude` IS NOT NULL AND `gpslongitude` IS NOT NULL";
    $hidden = nova_geoHiddenAlbums();
    if ($hidden) {
        $where .= ' AND `albumid` NOT IN (' . implode(',', $hidden) . ')';
    }
    if (!npg_loggedin(ADMIN_RIGHTS | MANAGE_ALL_ALBUM_RIGHTS)) {
        $where .= ' AND `albumid` IN (SELECT `id` FROM ' . prefix('albums') . ' WHERE `show` = 1)';
    }
    return '(SELECT `id`, ' . $lat . ' AS la, ' . $lon . ' AS lo FROM ' . prefix('images') . ' WHERE ' . $where . ') AS g';
}

/**
 * Overall numbers for the page (count and bounds of the visible geotagged images). Cached 1 hour.
 */
function nova_geoMeta() {
    $key = 'guest';
    $cache = getOption('nova_geo_meta_cache');
    $cache = $cache ? @unserialize($cache) : array();
    if (!is_array($cache)) {
        $cache = array();
    }
    if (isset($cache[$key]) && $cache[$key]['t'] > time() - 3600 && !npg_loggedin()) {
        return $cache[$key]['d'];
    }
    $row = query_single_row('SELECT COUNT(*) AS n, MIN(la) AS mila, MAX(la) AS mala, MIN(lo) AS milo, MAX(lo) AS malo FROM ' . nova_geoSource() .
            ' WHERE (la <> 0 OR lo <> 0) AND la BETWEEN -90 AND 90 AND lo BETWEEN -180 AND 180');
    $meta = array('n' => (int) ($row['n'] ?? 0), 'bounds' => null);
    if ($meta['n'] > 0) {
        $meta['bounds'] = array((float) $row['mila'], (float) $row['milo'], (float) $row['mala'], (float) $row['malo']);
    }
    if (!npg_loggedin()) {
        $cache[$key] = array('t' => time(), 'd' => $meta);
        setOption('nova_geo_meta_cache', serialize($cache));
    }
    return $meta;
}

function nova_geoJson($data, $maxAge = 0) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: ' . ($maxAge ? 'private, max-age=' . (int) $maxAge : 'no-store'));
    echo json_encode($data);
    exit;
}

/**
 * JSON endpoint. Called from photomap.php before any HTML is produced.
 *   ?nova_geo=view&s=&w=&n=&e=&z=   clusters or points inside the view
 *   ?nova_geo=items&s=&w=&n=&e=     up to 9 image previews inside a tiny box
 */
function nova_geoEndpoint() {
    $mode = $_GET['nova_geo'];
    $s = max(-90, min(90, (float) ($_GET['s'] ?? -90)));
    $n = max(-90, min(90, (float) ($_GET['n'] ?? 90)));
    $w = max(-180, min(180, (float) ($_GET['w'] ?? -180)));
    $e = max(-180, min(180, (float) ($_GET['e'] ?? 180)));
    if ($s > $n) {
        list($s, $n) = array($n, $s);
    }
    if ($w > $e) {
        list($w, $e) = array($e, $w);
    }
    $src = nova_geoSource();
    $box = ' WHERE la BETWEEN ' . $s . ' AND ' . $n . ' AND lo BETWEEN ' . $w . ' AND ' . $e . ' AND (la <> 0 OR lo <> 0)';

    if ($mode === 'items') {
        $rows = query_full_array('SELECT `id` FROM ' . $src . $box . ' ORDER BY `id` DESC LIMIT 30');
        $total = (int) ((query_single_row('SELECT COUNT(*) AS n FROM ' . $src . $box)['n']) ?? 0);
        $items = array();
        foreach ((array) $rows as $r) {
            $img = nova_geoImage((int) $r['id']);
            if (!$img) {
                continue;
            }
            $items[] = array(
                'title' => $img->getTitle(),
                'url' => $img->getLink(),
                'thumb' => $img->getCustomImage(array('width' => 200, 'height' => 200, 'cw' => 200, 'ch' => 200, 'thumb' => true)),
                'album' => $img->getAlbum()->getTitle(),
            );
            if (count($items) >= 9) {
                break;
            }
        }
        nova_geoJson(array('total' => $total, 'items' => $items), 300);
    }

    // view
    $z = max(0, min(22, (int) ($_GET['z'] ?? 5)));
    $cap = 300;
    $n_in = (int) ((query_single_row('SELECT COUNT(*) AS n FROM ' . $src . $box)['n']) ?? 0);
    $out = array('total' => $n_in, 'pts' => array());
    // one code path: grid clusters; with few photos in view the grid is ~1 m, so every photo is its own point
    $cell = ($n_in <= $cap) ? 0.00001 : 60 * 360 / (256 * pow(2, $z));
    $rows = query_full_array('SELECT COUNT(*) AS c, AVG(la) AS avla, AVG(lo) AS avlo, MIN(la) AS mila, MAX(la) AS mala, MIN(lo) AS milo, MAX(lo) AS malo FROM ' . $src . $box .
            ' GROUP BY FLOOR(la / ' . $cell . '), FLOOR(lo / ' . $cell . ') LIMIT 2000');
    foreach ((array) $rows as $r) {
        $out['pts'][] = array(round((float) $r['avla'], 6), round((float) $r['avlo'], 6), (int) $r['c'],
            (float) $r['mila'], (float) $r['milo'], (float) $r['mala'], (float) $r['malo']);
    }
    nova_geoJson($out, 300);
}

function nova_geoImage($id) {
    $row = query_single_row('SELECT i.`filename`, a.`folder` FROM ' . prefix('images') . ' AS i JOIN ' . prefix('albums') . ' AS a ON a.`id` = i.`albumid` WHERE i.`id` = ' . (int) $id);
    if (!$row) {
        return false;
    }
    $album = newAlbum($row['folder'], true, true);
    if (!$album || !$album->exists) {
        return false;
    }
    $img = newImage($album, $row['filename'], true);
    if (!$img || !$img->exists || !$img->checkAccess()) {
        return false;
    }
    return $img;
}

/**
 * Load the Leaflet files shipped with the openStreetMap plugin (only the map core, no cluster plugin needed).
 */
function nova_loadLeaflet() {
    $dir = PLUGIN_SERVERPATH . 'openstreetmap/';
    scriptLoader($dir . 'leaflet.css');
    scriptLoader($dir . 'leaflet.js');
}

function nova_mapPageEnabled() {
    return (bool) nova_option('zpB_show_map_page', false);
}

/* ------------------------------------------------------------------
 * Photos on the home page (random or latest). The candidate list is
 * built by one cheap query and cached, so the home page never runs a
 * slow "random image" search and the same few thumbnails are reused.
 * ------------------------------------------------------------------ */

function nova_homePhotosMode() {
    $mode = getOption('zpB_home_photos');
    return in_array($mode, array('none', 'random', 'latest'), true) ? $mode : 'random';
}

function nova_homePhotosCount() {
    $n = (int) getOption('zpB_home_photos_count');
    return $n < 1 ? 3 : min($n, 6);
}

function nova_homePhotos() {
    $mode = nova_homePhotosMode();
    $count = nova_homePhotosCount();
    if ($mode === 'none') {
        return array();
    }
    $ttl = ($mode === 'random') ? 6 * 3600 : 3600;
    $cache = getOption('nova_home_pool');
    $cache = $cache ? @unserialize($cache) : array();
    if (!is_array($cache)) {
        $cache = array();
    }
    if (!isset($cache[$mode]) || $cache[$mode]['t'] < time() - $ttl || empty($cache[$mode]['ids'])) {
        $order = ($mode === 'random') ? 'RAND()' : 'i.`id` DESC';
        $rows = query_full_array('SELECT i.`id` FROM ' . prefix('images') . ' AS i JOIN ' . prefix('albums') . ' AS a ON a.`id` = i.`albumid`' .
                ' WHERE i.`show` = 1 AND a.`show` = 1 ORDER BY ' . $order . ' LIMIT 24');
        $ids = array();
        foreach ((array) $rows as $r) {
            $ids[] = (int) $r['id'];
        }
        $cache[$mode] = array('t' => time(), 'ids' => $ids);
        setOption('nova_home_pool', serialize($cache));
    }
    $images = array();
    foreach ($cache[$mode]['ids'] as $id) {
        $img = nova_geoImage($id); // checks that this visitor may see it
        if ($img) {
            $images[] = $img;
            if (count($images) >= $count) {
                break;
            }
        }
    }
    return $images;
}

function nova_printHomePhotos() {
    $images = nova_homePhotos();
    if (!$images) {
        return;
    }
    $title = (nova_homePhotosMode() === 'random') ? gettext('Random photos') : gettext('Latest photos');
    ?>
    <section class="home-photos" style="--cols: <?php echo min(count($images), 3); ?>">
        <h2 class="home-photos__title"><?php echo html_encode($title); ?></h2>
        <div class="home-photos__grid">
            <?php foreach ($images as $img) { ?>
                <a class="home-photos__item" href="<?php echo html_encode($img->getLink()); ?>" title="<?php echo html_encode($img->getTitle()); ?>">
                    <img loading="lazy" src="<?php echo html_encode($img->getCustomImage(array('width' => 600, 'height' => 400, 'cw' => 600, 'ch' => 400, 'thumb' => true))); ?>" alt="<?php echo html_encode($img->getTitle()); ?>">
                </a>
            <?php } ?>
        </div>
    </section>
    <?php
}


/**
 * Most used tags (cached 6 hours, separately for guests and logged in users).
 */
function nova_getTags($limit = 60) {
    $key = npg_loggedin() ? 'user' : 'guest';
    $cache = getOption('nova_tagcloud_cache');
    $cache = $cache ? @unserialize($cache) : array();
    if (!is_array($cache)) {
        $cache = array();
    }
    if (isset($cache[$key]) && $cache[$key]['t'] > time() - 21600) {
        return $cache[$key]['tags'];
    }
    $tags = getAllTagsUnique(NULL, 1, true);
    $tags = is_array($tags) ? $tags : array();
    arsort($tags);
    $tags = array_slice($tags, 0, $limit, true);
    $cache[$key] = array('t' => time(), 'tags' => $tags);
    setOption('nova_tagcloud_cache', serialize($cache));
    return $tags;
}

/**
 * Small gallery statistics and a tag cloud (bottom of the home page).
 */
function nova_printHomeStats() {
    if (!nova_option('zpB_home_stats', true)) {
        return;
    }
    $st = nova_getStats();
    $items = array(
        array($st['total'], gettext('Photos')),
        array($st['albums'], gettext('Albums')),
    );
    if ($st['pages'] > 0) {
        $items[] = array($st['pages'], gettext('Pages'));
    }
    if ($st['news'] > 0) {
        $items[] = array($st['news'], NEWS_LABEL);
    }
    if ($st['tags'] > 0) {
        $items[] = array($st['tags'], gettext('Tags'));
    }
    ?>
    <section class="home-stats">
        <ul class="stat-tiles">
            <?php foreach ($items as $it) { ?>
                <li><strong><?php echo number_format($it[0], 0, ',', '&nbsp;'); ?></strong><span><?php echo html_encode($it[1]); ?></span></li>
            <?php } ?>
        </ul>
        <?php
        $tags = nova_getTags(60);
        if ($tags) {
            $min = min($tags);
            $max = max($tags);
            $keys = array_keys($tags);
            localeSort($keys);
            ?>
            <ul class="tag-cloud" aria-label="<?php echo gettext('Tags'); ?>">
                <?php
                foreach ($keys as $tag) {
                    $w = ($max > $min) ? sqrt(($tags[$tag] - $min) / ($max - $min)) : 0.5;
                    $size = round(0.85 + $w * 0.95, 2);
                    $link = SearchEngine::getURL(SearchEngine::searchQuote($tag), '', 'tags', 0, array('albums' => NULL));
                    ?>
                    <li><a href="<?php echo html_encode($link); ?>" rel="nofollow" style="font-size:<?php echo str_replace(',', '.', $size); ?>em" title="<?php echo (int) $tags[$tag]; ?>"><?php echo str_replace(' ', '&nbsp;', html_encode($tag)); ?></a></li>
                <?php } ?>
            </ul>
        <?php } ?>
    </section>
    <?php
}


function nova_homeNewsCount() {
    $n = (int) getOption('zpB_home_news_count');
    return $n < 1 ? 1 : min($n, 3);
}
