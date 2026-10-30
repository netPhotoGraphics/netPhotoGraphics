<?php

// force UTF-8 Ø
if (!defined('WEBPATH')) {
    die();
}

$_nova_fancybox = in_array($_gallery_page, array('album.php', 'favorites.php', 'news.php', 'pages.php', 'search.php'));

$_nova_gallery_active = ((!getOption('zpB_homepage') && $_gallery_page == 'index.php') ||
        in_array($_gallery_page, array('gallery.php', 'album.php', 'image.php')));

// page title
$_nova_title = '';
switch ($_gallery_page) {
    case 'index.php':
        $_nova_title = (isset($isHomePage) && $isHomePage) ? gettext('Home') : gettext('Gallery');
        break;
    case '404.php':
        $_nova_title = gettext('Object not found');
        break;
    case 'album.php':
        $_nova_title = getBareAlbumTitle() . ($_current_page > 1 ? ' [' . $_current_page . ']' : '');
        break;
    case 'archive.php':
        $_nova_title = gettext('Archive View');
        break;
    case 'summary.php':
        $_nova_title = gettext('Daily summary');
        break;
    case 'contact.php':
        $_nova_title = gettext('Contact');
        break;
    case 'favorites.php':
        $_nova_title = gettext('My favorites') . ($_current_page > 1 ? ' [' . $_current_page . ']' : '');
        break;
    case 'gallery.php':
        $_nova_title = gettext('Gallery') . ($_current_page > 1 ? ' [' . $_current_page . ']' : '');
        break;
    case 'image.php':
        $_nova_title = getBareAlbumTitle() . ' | ' . getBareImageTitle();
        break;
    case 'news.php':
        if (is_NewsArticle()) {
            $_nova_title = getBareNewsTitle();
        } else {
            $_nova_title = NEWS_LABEL . ($_current_page > 1 ? ' [' . $_current_page . ']' : '');
        }
        break;
    case 'pages.php':
        $_nova_title = getBarePageTitle();
        break;
    case 'photomap.php':
        $_nova_title = gettext('Photo map');
        break;
    case 'password.php':
        $_nova_title = gettext('Password Required...');
        break;
    case 'register.php':
        $_nova_title = gettext('Register');
        break;
    case 'search.php':
        $_nova_title = gettext('Search') . ($_current_page > 1 ? ' [' . $_current_page . ']' : '');
        break;
}
?>
<!DOCTYPE html>
<html<?php i18n::htmlLanguageCode(); ?>>
    <head>
        <meta charset="<?php echo getOption('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#111111">
        <script>
            /* set the colour scheme before first paint (no flash) */
            (function () {
                var t = null;
                try { t = localStorage.getItem('nova-theme'); } catch (e) {}
                if (t !== 'dark' && t !== 'light') {
                    t = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-theme', t);
            })();
        </script>
        <?php
        // load map scripts only where needed
        if (!((($_gallery_page == 'pages.php') && (getPageTitleLink() == 'map')) ||
                ($_gallery_page == 'album.php') ||
                ($_gallery_page == 'image.php' && get_class($_current_image) == 'GPX'))) {
            npgFilters::remove('theme_head', 'GoogleMap::js');
            npgFilters::remove('theme_head', 'openStreetMap::scripts');
        }
        // plugins (e.g. html_meta_tags) may print their own <title>; keep only the theme's one
        ob_start();
        npgFilters::apply('theme_head');
        echo preg_replace('~<title>.*?</title>\s*~is', '', ob_get_clean());
        ?>
        <?php if ($_gallery_page == 'photomap.php') { nova_loadLeaflet(); } ?>
        <title><?php echo html_encode(getMainSiteName() . ' | ' . $_nova_title); ?></title>
        <?php
        if (extensionEnabled('rss')) {
            if ($_zenpage_news_enabled && getOption('RSS_articles')) {
                printRSSHeaderLink('News', NEWS_LABEL);
            } else if (getOption('RSS_album_image')) {
                printRSSHeaderLink('Gallery', gettext('Latest images RSS'));
            }
        }
        ?>
        <?php
        if ($_nova_fancybox) {
            scriptLoader($_themeroot . '/css/jquery.fancybox.min.css');
        }
        // own files carry the file's change time, so browsers never keep an outdated stylesheet/script after an update
        foreach (array('css/base.css', 'css/gallery.css') as $_nova_f) {
            echo '<link rel="stylesheet" href="' . html_encode($_themeroot . '/' . $_nova_f . '?v=' . @filemtime(__DIR__ . '/../' . $_nova_f)) . '">' . "\n";
        }
        echo '<script src="' . html_encode($_themeroot . '/js/nova.js?v=' . @filemtime(__DIR__ . '/../js/nova.js')) . '" defer></script>' . "\n";

        if ($_nova_fancybox) {
            scriptLoader($_themeroot . '/js/jquery.fancybox.min.js');
            scriptLoader($_themeroot . '/js/nova-fancybox.js');
            $loc = substr(getOption('locale'), 0, 2);
            if (empty($loc)) {
                $loc = 'en';
            }
            ?>
            <script>
                //<![CDATA[
                jQuery(function ($) {
                    $.fancybox.defaults.lang = <?php echo json_encode($loc); ?>;
                    $.fancybox.defaults.i18n = {};
                    $.fancybox.defaults.i18n[<?php echo json_encode($loc); ?>] = {
                        CLOSE: <?php echo json_encode(gettext('close')); ?>,
                        NEXT: <?php echo json_encode(gettext('next')); ?>,
                        PREV: <?php echo json_encode(gettext('prev')); ?>,
                        PLAY_START: <?php echo json_encode(gettext('start slideshow')); ?>,
                        PLAY_STOP: <?php echo json_encode(gettext('stop slideshow')); ?>,
                        THUMBS: <?php echo json_encode(gettext('thumbnails')); ?>
                    };
                });
                //]]>
            </script>
            <?php
        }
        ?>
    </head>

    <body class="page-<?php echo basename($_gallery_page, '.php'); ?><?php echo !empty($isHomePage) ? ' is-home' : ''; ?>">
        <?php npgFilters::apply('theme_body_open'); ?>
        <a class="skip-link" href="#main"><?php echo gettext('Skip to content'); ?></a>

        <header class="site-header">
            <div class="site-header__inner">
                <a class="site-logo" href="<?php echo html_encode(getGalleryIndexURL()); ?>"><?php echo html_encode(getMainSiteName()); ?></a>

                <button type="button" class="nav-toggle" aria-controls="site-nav" aria-expanded="false" aria-label="<?php echo gettext('Menu'); ?>">
                    <?php echo nova_icon('menu', 24); ?>
                </button>

                <nav id="site-nav" class="site-nav" aria-label="<?php echo gettext('Menu'); ?>">
                    <ul class="site-nav__list">
                        <?php if (extensionEnabled('menu_manager') && getThemeOption('zpB_custom_menu')) { ?>
                            <?php printCustomMenu('nova', 'list-top', '', 'active'); ?>
                        <?php } else { ?>
                            <?php if (getOption('zpB_homepage')) { ?>
                                <li<?php echo (isset($isHomePage) && $isHomePage) ? ' class="active"' : ''; ?>>
                                    <a href="<?php echo html_encode(getGalleryIndexURL()); ?>"><?php echo gettext('Home'); ?></a>
                                </li>
                            <?php } ?>

                            <li<?php echo $_nova_gallery_active ? ' class="active"' : ''; ?>>
                                <?php printCustomPageURL(gettext('Gallery'), 'gallery'); ?>
                            </li>

                            <?php if ($_zenpage_news_enabled) { ?>
                                <li<?php echo $_gallery_page == 'news.php' ? ' class="active"' : ''; ?>>
                                    <?php printNewsIndexURL(); ?>
                                </li>
                            <?php } ?>

                            <?php if ($_zenpage_pages_enabled) { ?>
                                <?php printPageMenu('list-top', '', 'active', '', '', '', 0, false); ?>
                            <?php } ?>
                        <?php } ?>

                        <?php if (nova_mapPageEnabled() && !(extensionEnabled('menu_manager') && getThemeOption('zpB_custom_menu'))) { ?>
                            <li<?php echo $_gallery_page == 'photomap.php' ? ' class="active"' : ''; ?>>
                                <?php printCustomPageURL(gettext('Photo map'), 'photomap'); ?>
                            </li>
                        <?php } ?>

                        <?php if (npg_loggedin() && extensionEnabled('favoritesHandler')) { ?>
                            <li<?php echo $_gallery_page == 'favorites.php' ? ' class="active"' : ''; ?>>
                                <?php nova_printFavoritesMenu(); ?>
                            </li>
                        <?php } ?>

                        <?php if (extensionEnabled('contact_form')) { ?>
                            <li<?php echo $_gallery_page == 'contact.php' ? ' class="active"' : ''; ?>>
                                <?php printCustomPageURL(gettext('Contact'), 'contact'); ?>
                            </li>
                        <?php } ?>

                        <?php if (getOption('zpB_allow_search')) { ?>
                            <li class="site-nav__icon<?php echo $_gallery_page == 'archive.php' ? ' active' : ''; ?>">
                                <a href="<?php echo html_encode(getCustomPageURL('archive')); ?>" title="<?php echo gettext('Search'); ?>" aria-label="<?php echo gettext('Search'); ?>">
                                    <?php echo nova_icon('search'); ?><span class="site-nav__label"><?php echo gettext('Search'); ?></span>
                                </a>
                            </li>
                        <?php } ?>

                        <li class="site-nav__icon">
                            <button type="button" class="site-nav__button theme-toggle" title="<?php echo html_encode(gettext('Dark mode')); ?>" aria-label="<?php echo html_encode(gettext('Dark mode')); ?>">
                                <span class="theme-toggle__moon"><?php echo nova_icon('moon'); ?></span>
                                <span class="theme-toggle__sun"><?php echo nova_icon('sun'); ?></span>
                                <span class="site-nav__label"><?php echo gettext('Dark mode'); ?></span>
                            </button>
                        </li>

                        <?php if (extensionEnabled('user_login-out')) { ?>
                            <?php if (npg_loggedin()) { ?>
                                <li class="site-nav__user"><?php printUserLogin_out(); ?></li>
                            <?php } else { ?>
                                <li class="site-nav__user">
                                    <button type="button" class="site-nav__button" data-dialog-open="login-dialog">
                                        <?php echo nova_icon('login'); ?><span><?php echo gettext('Login'); ?></span>
                                    </button>
                                </li>
                            <?php } ?>
                        <?php } ?>

                        <?php if (!npg_loggedin() && extensionEnabled('register_user')) { ?>
                            <li class="site-nav__user"><?php printRegisterURL(gettext('Register')); ?></li>
                        <?php } ?>

                        <?php if (extensionEnabled('dynamic-locale')) { ?>
                            <li id="flags" class="site-nav__lang"><?php printLanguageSelector(); ?></li>
                        <?php } ?>
                    </ul>
                </nav>
            </div>
        </header>

        <?php if (extensionEnabled('user_login-out') && !npg_loggedin() && $_gallery_page != 'password.php' && $_gallery_page != 'register.php') { ?>
            <dialog id="login-dialog" class="dialog">
                <div class="dialog__body">
                    <button type="button" class="dialog__close" data-dialog-close aria-label="<?php echo gettext('close'); ?>"><?php echo nova_icon('close'); ?></button>
                    <?php printPasswordForm('', true, false); ?>
                </div>
            </dialog>
        <?php } ?>

        <main class="site-main" id="main" tabindex="-1">
