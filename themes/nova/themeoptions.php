<?php

/* Theme options for Nova */

require_once(SERVERPATH . '/' . CORE_FOLDER . '/admin-functions.php');

class ThemeOptions {

    function __construct() {

        $me = basename(__DIR__);
        setThemeOptionDefault('albums_per_page', 12);
        setThemeOptionDefault('images_per_row', 4);
        setThemeOptionDefault('images_per_page', 24);
        setThemeOptionDefault('thumb_size', 300);
        setThemeOptionDefault('thumb_crop', 1);
        setThemeOptionDefault('thumb_crop_width', 300);
        setThemeOptionDefault('thumb_crop_height', 300);
        purgeOption('image_size', $me);
        setThemeOptionDefault('image_size', 1600);
        setThemeOptionDefault('image_use_side', 'longest');
        setThemeOptionDefault('custom_index_page', 'gallery');

        setThemeOptionDefault('zpB_homepage', true);
        setThemeOptionDefault('zpB_latest_news_homepage', false);
        setThemeOptionDefault('zpB_allow_search', true);
        setThemeOptionDefault('zpB_exerpt_length', 200);
        setThemeOptionDefault('zpB_show_archive', false);
        setThemeOptionDefault('zpB_show_map', true);
        setThemeOptionDefault('zpB_show_map_page', false);
        setThemeOptionDefault('zpB_home_photos', 'random');
        setThemeOptionDefault('zpB_home_photos_count', 3);
        setThemeOptionDefault('zpB_home_stats', true);
        setThemeOptionDefault('zpB_home_news_count', 1);
        setThemeOptionDefault('zpB_news_more', 'readmore');
        setThemeOptionDefault('zpB_show_tags', true);
        setThemeOptionDefault('zpB_show_exif', true);
        setThemeOptionDefault('zpB_show_stats', true);
        setThemeOptionDefault('zpB_custom_menu', false);

        if (class_exists('cacheManager')) {
            cacheManager::deleteCacheSizes($me);
        }

        if (class_exists('menu_manager')) {
            $menuitems = array(
                array('type' => 'siteindex', 'title' => gettext('Home'), 'link' => '', 'show' => 1, 'nesting' => 0),
                array('type' => 'newsindex', 'title' => NEWS_LABEL, 'link' => '', 'show' => 1, 'nesting' => 0),
                array('type' => 'albumindex', 'title' => gettext('Gallery'), 'link' => '', 'show' => 1, 'nesting' => 0),
                array('type' => 'menufunction', 'title' => gettext('Pages'), 'link' => 'printPageMenu("list-top", "", "active", "", "", "", 0, false);', 'show' => 1, 'nesting' => 0),
            );
            createMenuIfNotExists($menuitems, 'nova');
        }
    }

    function getOptionsDisabled() {
        return array('thumb_size', 'image_size', 'custom_index_page');
    }

    function getOptionsSupported() {

        return array(
            gettext('Homepage') => array('order' => 0, 'key' => 'zpB_homepage', 'type' => OPTION_TYPE_CHECKBOX,
                'desc' => gettext('Display a home page with the gallery description and (optionally) the latest news.')),
            gettext('Latest news on Homepage') => array('order' => 1, 'key' => 'zpB_latest_news_homepage', 'type' => OPTION_TYPE_CHECKBOX,
                'desc' => gettext('Display the latest news on the home page (Homepage option has to be selected too).')),
            gettext('Number of news on the home page') => array('order' => 2.5, 'key' => 'zpB_home_news_count', 'type' => OPTION_TYPE_NUMBER,
                'desc' => gettext('1 to 3 latest news below the photos (needs the "Latest news on Homepage" option).')),
            gettext('Use custom menu') => array('order' => 2, 'key' => 'zpB_custom_menu', 'type' => OPTION_TYPE_CHECKBOX,
                'desc' => gettext('Use the "menu_manager" menu named "nova" instead of the standard menu.')),
            gettext('Allow search') => array('order' => 5, 'key' => 'zpB_allow_search', 'type' => OPTION_TYPE_CHECKBOX,
                'desc' => gettext('Show the search icon in the menu.')),
            gettext('Excerpt length') => array('order' => 6, 'key' => 'zpB_exerpt_length', 'type' => OPTION_TYPE_NUMBER,
                'desc' => gettext('Excerpt length for news and pages lists in search page.')),
            gettext('Archive View') => array('order' => 7, 'key' => 'zpB_show_archive', 'type' => OPTION_TYPE_CHECKBOX,
                'desc' => gettext('Display a link to the Archive list in the footer.')),
            gettext('Tags') => array('order' => 8, 'key' => 'zpB_show_tags', 'type' => OPTION_TYPE_CHECKBOX,
                'desc' => gettext('Show tags on the image page and a tag cloud in the Archive.')),
            gettext('Exif') => array('order' => 9, 'key' => 'zpB_show_exif', 'type' => OPTION_TYPE_CHECKBOX,
                'desc' => gettext('Show the collapsible EXIF panel on the image page. The metadata to show is chosen in Options > Image > Metadata.')),
            gettext('Map') => array('order' => 10, 'key' => 'zpB_show_map', 'type' => OPTION_TYPE_CHECKBOX,
                'desc' => gettext('Show a map with the photos on album pages that contain photos with GPS coordinates (needs the openStreetMap or googleMap plugin).')),
            gettext('Photo map page') => array('order' => 13, 'key' => 'zpB_show_map_page', 'type' => OPTION_TYPE_CHECKBOX,
                'desc' => gettext('Enable the "Photo map" page (/page/photomap) with all photos that have GPS coordinates, plus its menu link. Safe for very large galleries: only the visible part of the map is loaded and nearby photos are grouped on the server. With a custom menu, add the page link yourself as a custom page "photomap".')),
            gettext('Photos on the home page') => array('order' => 3, 'key' => 'zpB_home_photos', 'type' => OPTION_TYPE_SELECTOR,
                'selections' => array(gettext('None') => 'none', gettext('Random photos') => 'random', gettext('Latest photos') => 'latest'),
                'desc' => gettext('A small strip of photos below the description on the home page (Homepage option has to be selected). The selection is cached (random: 6 hours, latest: 1 hour), so the page stays fast even in very large galleries.')),
            gettext('Number of photos on the home page') => array('order' => 4, 'key' => 'zpB_home_photos_count', 'type' => OPTION_TYPE_NUMBER,
                'desc' => gettext('1 to 6 photos; 3 fits one row.')),
            gettext('Statistics and tag cloud on the home page') => array('order' => 14, 'key' => 'zpB_home_stats', 'type' => OPTION_TYPE_CHECKBOX,
                'desc' => gettext('Show the numbers of photos, albums, pages and news and a cloud of the most used tags at the bottom of the home page (cached for 6 hours).')),
            gettext('Shortened news articles') => array('order' => 12, 'key' => 'zpB_news_more', 'type' => OPTION_TYPE_SELECTOR,
                'selections' => array(gettext('"Read more" link') => 'readmore', gettext('Clickable shortening indicator') => 'dots'),
                'desc' => gettext('How a shortened article in the news list continues: with the "read more" link from the Zenpage options, or with the shortening indicator (e.g. "(...)") as a link.')),
            gettext('Footer statistics') => array('order' => 11, 'key' => 'zpB_show_stats', 'type' => OPTION_TYPE_CHECKBOX,
                'desc' => gettext('Show GPS and description statistics in the footer (runs a few database counts on every page; disable on very large galleries if pages feel slow).')),
        );
    }

    function handleOption($option, $currentValue) {

    }

}
