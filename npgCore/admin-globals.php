<?php

/**
 * Initialize globals for Admin
 *
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 */
// force UTF-8 Ø

$_adminScript_timer['start'] = microtime();
ini_set('post_max_size', "10M");
ini_set('post_input_vars', "2500");

require_once(__DIR__ . '/functions-basic.php');
require_once(__DIR__ . '/initialize-basic.php');

define('TEXTAREA_COLUMNS', 50);
define('TEXT_INPUT_SIZE', 48);
define('TEXTAREA_COLUMNS_SHORT', 32);
define('TEXT_INPUT_SIZE_SHORT', 30);

define('ADMIN_THUMB_LARGE', 160);
define('ADMIN_THUMB_MEDIUM', 80);
define('ADMIN_THUMB_SMALL', 40);

define('UPLOAD_ERR_QUOTA', -1);
define('UPLOAD_ERR_BLOCKED', -2);

npg_session_start();
require_once(CORE_SERVERPATH . 'admin-functions.php');

$_admin_button_actions = $_admin_menu = array();

if (abs(OFFSET_PATH) != 2) {
	if (TEST_RELEASE) {
		enableExtension('debug', 10 | ADMIN_PLUGIN, false);
	}
	//load feature and admin plugins
	$enabled = getEnabledPlugins();

	foreach (array(FEATURE_PLUGIN, ADMIN_PLUGIN) as $mask) {
		if (DEBUG_PLUGINS) {
			switch ($mask) {
				case FEATURE_PLUGIN:
					debugLog('Loading the "feature" plugins.');
					break;
				case ADMIN_PLUGIN:
					debugLog('Loading the "admin" plugins.');
					break;
			}
		}


		foreach ($enabled as $extension => $plugin) {
			$priority = $plugin['priority'];
			if ($priority & $mask) {
				$start = microtime();
				require_once($plugin['path']);
				if (DEBUG_PLUGINS) {
					npgFunctions::pluginDebug($extension, $priority, $start);
				}
				$_loaded_plugins[$extension] = $extension;
			}
		}
	}

	if (!defined('EDITOR_SANITIZE_LEVEL'))
		define('EDITOR_SANITIZE_LEVEL', 1);

	//	just incase
	require_once(CORE_SERVERPATH . 'lib-filter.php');
	require_once(PLUGIN_SERVERPATH . 'dynamic-locale.php');
}
if (!defined('SEO_FULLWEBPATH')) {
	define('SEO_FULLWEBPATH', FULLWEBPATH);
	define('SEO_WEBPATH', WEBPATH);
}

$_SESSION['adminRequest'] = getNPGCookie(AUTHCOOKIE); //	Allow "unprotected" i.php if the request came from an admin session

require_once(CORE_SERVERPATH . 'rewrite.php');
if (OFFSET_PATH != 2 && !getOption('license_accepted') && !isset($_invisible_execute)) {
	require_once(__DIR__ . '/license.php');
}

$_sortby = array(
		gettext('Filename') => 'filename',
		gettext('Date') => 'date',
		gettext('Publish date') => 'publishdate',
		gettext('Title') => 'title',
		gettext('ID') => 'id',
		gettext('Filemtime') => 'mtime',
		gettext('Last change date') => 'lastchange',
		gettext('Owner') => 'owner',
		gettext('Published') => 'show'
);

// setup sub-tab arrays for use in dropdown
if (isset($_loggedin) && $_loggedin) {
	if ($_current_admin_obj->reset) {
		$_loggedin = USER_RIGHTS;
		$_admin_menu['admin'] = array(
				'text' => gettext("admin"),
				'link' => getAdminLink('admin-tabs/users.php') . '?page=admin&tab=users',
				'ordered' => true,
				'subtabs' => NULL
		);
	} else {
		$admin = $_current_admin_obj->getUser();
		if ($_loggedin & ADMIN_RIGHTS) {
			$_loggedin = ALL_RIGHTS;
		} else {
			if ($_loggedin & MANAGE_ALL_ALBUM_RIGHTS) {
				// these are lock-step linked!
				$_loggedin = $_loggedin | ALBUM_RIGHTS;
			}
		}

		//	establish the menu order
		$_admin_menu['overview'] = NULL;
		$_admin_menu['options'] = NULL;
		$_admin_menu['logs'] = NULL;
		$_admin_menu['admin'] = NULL;
		$_admin_menu['images'] = NULL;
		$_admin_menu['edit'] = NULL;
		$_admin_menu['news'] = NULL;
		$_admin_menu['pages'] = NULL;
		$_admin_menu['comments'] = NULL;
		$_admin_menu['themes'] = NULL;
		$_admin_menu['plugins'] = NULL;
		$_admin_menu['menu'] = NULL;
		$_admin_menu['upload'] = NULL;
		$_admin_menu['development'] = NULL;

		if ($_loggedin & OVERVIEW_RIGHTS) {
			$_admin_menu['overview'] = array('text' => gettext("overview"),
					'link' => getAdminLink('admin.php'),
					'subtabs' => NULL);
			$_admin_menu['overview']['subtabs'][gettext('Gallery statistics')] = '/' . CORE_FOLDER . '/utilities/gallery_statistics.php?tab=gallerystats';
		}

		npgFilters::register('admin_tabs', 'refresh_subtabs', -1800);

		if ($_loggedin & ALBUM_RIGHTS) {
			if (!$albums = npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
				foreach ($_gallery->getAlbums() as $key => $analbum) {
					$albumobj = newAlbum($analbum);
					if ($albumobj->isMyItem(ALBUM_RIGHTS)) {
						$albums = true;
						break;
					}
				}
			}
			if ($albums) {
				$_admin_menu['edit'] = array('text' => gettext("albums"),
						'link' => getAdminLink('admin-tabs/edit.php'),
						'subtabs' => NULL);
			}
		}


		if (isset($_CMS)) {
			list($articlestab, $categorystab, $pagestab) = cmsFilters::admin_pages();
			if ($categorystab) {
				$_admin_menu['news'] = array('text' => gettext('news'),
						'link' => getAdminLink(PLUGIN_FOLDER . '/zenpage/news.php'),
						'subtabs' => array(gettext('articles') => PLUGIN_FOLDER . '/zenpage/news.php?page=news&tab=articles',
								gettext('categories') => PLUGIN_FOLDER . '/zenpage/categories.php?page=news&tab=categories'),
						'ordered' => true,
						'default' => 'articles');
			} else if ($articlestab) {
				$_admin_menu['news'] = array('text' => gettext('news'),
						'link' => getAdminLink(PLUGIN_FOLDER . '/zenpage/news.php'),
						'subtabs' => NULL,
						'ordered' => true,
						'default' => 'articles');
			}
			if ($pagestab) {
				$_loggedin = $_loggedin | ZENPAGE_PAGES_RIGHTS;
				$_admin_menu['pages'] = array('text' => gettext("pages"),
						'link' => getAdminLink(PLUGIN_FOLDER . '/zenpage/pages.php'),
						'subtabs' => NULL);
			}
		}

		if (getOption('adminTagsTab')) {
			npgFilters::register('admin_tabs', 'tags_subtab', -1900);
		}
		if ($_loggedin & ADMIN_RIGHTS) {
			$_admin_menu['admin'] = array(
					'text' => gettext("admin"),
					'link' => getAdminLink('admin-tabs/users.php') . '?page=admin&tab=users',
					'ordered' => true,
					'subtabs' => array(gettext('users') => 'admin-tabs/users.php?page=admin&tab=users')
			);
		} else {
			if ($_loggedin & USER_RIGHTS) {
				$_admin_menu['admin'] = array(
						'text' => gettext("my profile"),
						'link' => getAdminLink('admin-tabs/users.php') . '?page=admin&tab=users',
						'ordered' => true,
						'subtabs' => NULL
				);
			}
		}
		if (!npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
			$sql = 'SELECT `filename` FROM ' . prefix('images') . ' WHERE `owner`=' . db_quote($_current_admin_obj->getUser()) . ' LIMIT 1';
			$found = query($sql);
			if ($found && $found->num_rows > 0) {
				$_admin_menu['images'] = array(
						'text' => gettext("my images"),
						'link' => getAdminLink('admin-tabs/images.php') . '?page=admin&tab=images',
						'ordered' => true,
						'subtabs' => NULL
				);
			}
		}

		$subtabs = array();
		$optiondefault = '';
		if ($_loggedin & OPTIONS_RIGHTS) {
			if ($_loggedin & ADMIN_RIGHTS) {
				$optiondefault = '&tab=general';
				$subtabs[gettext("general")] = 'admin-tabs/options.php?page=options&tab=general';
			} else {
				$optiondefault = '&tab=gallery';
			}
			$subtabs[gettext("gallery")] = 'admin-tabs/options.php?page=options&tab=gallery';
			if ($_loggedin & ADMIN_RIGHTS) {
				$subtabs[gettext("security")] = 'admin-tabs/options.php?page=options&tab=security';
				if (npg_Authority::flagOptionTab()) {
					$new = array(gettext("security"));
				} else {
					$new = array();
				}
			}
			$subtabs[gettext("image")] = 'admin-tabs/options.php?page=options&tab=image';
			$subtabs[gettext("search")] = 'admin-tabs/options.php?page=options&tab=search';
			$subtabs[gettext("forms")] = 'admin-tabs/options.php?page=options&tab=forms';
			if ($_loggedin & ADMIN_RIGHTS) {
				$subtabs[gettext("plugin")] = 'admin-tabs/options.php?page=options&tab=plugin';
			}

			if ($_loggedin & THEMES_RIGHTS) {
				if (empty($optiondefault))
					$optiondefault = '&tab=theme';
				$subtabs[gettext("theme")] = 'admin-tabs/options.php?page=options&tab=theme';
			}
			$_admin_menu['options'] = array('text' => gettext("options"),
					'link' => getAdminLink('admin-tabs/options.php') . '?page=options' . $optiondefault,
					'subtabs' => $subtabs,
					'alert' => $new,
					'ordered' => true,
					'default' => 'gallery');
		}

		if ($_loggedin & THEMES_RIGHTS) {
			$_admin_menu['themes'] = array('text' => gettext("themes"),
					'link' => getAdminLink('admin-tabs/themes.php'),
					'subtabs' => NULL);
		}

		if ($_loggedin & ADMIN_RIGHTS && OFFSET_PATH != 2) {
			//NOTE: the following listed variables will be assumed by the admin:plugins script
			list($plugin_subtabs, $plugin_default, $pluginlist, $plugin_paths, $plugin_member, $classXlate, $pluginDetails) = getPluginTabs();
			$_admin_menu['plugins'] = array('text' => gettext("plugins"),
					'link' => getAdminLink('admin-tabs/plugins.php'),
					'subtabs' => $plugin_subtabs,
					'ordered' => true,
			);
			npgFilters::register('admin_tabs', 'backup_subtab', -200);
			$_admin_menu['overview']['subtabs'][gettext('Installation information')] = '/' . CORE_FOLDER . '/utilities/installation_analysis.php?tab=installstats';
		}

		if ($_loggedin & ADMIN_RIGHTS) {
			list($subtabs, $default, $new) = getLogTabs();
			$_admin_menu['logs'] = array('text' => gettext("logs"),
					'link' => getAdminLink('admin-tabs/logs.php') . '?page=logs',
					'subtabs' => $subtabs,
					'alert' => $new,
					'default' => $default);
			$_admin_menu['overview']['subtabs'][gettext('Database Reference')] = "/" . CORE_FOLDER . '/utilities/database_reference.php?tab=databaseref';
			$_admin_menu['overview']['subtabs'][gettext('HTTP header inspector')] = "/" . CORE_FOLDER . '/utilities/http_header_inspector.php';
		}

		if (!$_current_admin_obj->getID()) {
			$filelist = safe_glob(SERVERPATH . "/" . BACKUPFOLDER . '/*.zdb');
			if (count($filelist) > 0) {
				$_admin_menu['admin']['subtabs']['restore'] = 'utilities/backup_restore.php?tab=backup';
			}
		}

		$_admin_menu = npgFilters::apply('admin_tabs', $_admin_menu);
		foreach ($_admin_menu as $tab => $value) {
			if (is_null($value)) {
				unset($_admin_menu[$tab]);
			}
		}

		if (isset($_admin_menu['admin']['subtabs']) && count($_admin_menu['admin']['subtabs']) == 1) {
			$_admin_menu['admin']['subtabs'] = NULL;
		}

		//	so as to make it generally available as we make much use of it
		if (OFFSET_PATH != 2) {
			require_once(PLUGIN_SERVERPATH . 'colorbox_js.php');
		}
	}
	loadLocalOptions(0, $_gallery->getCurrentTheme());
}

if (defined('MOD_REWRITE') && MOD_REWRITE && OFFSET_PATH != 2) {
	$uri = getRequestURI();
	$parts = mb_parse_url($uri);
	if (strpos($parts['path'], 'zp-core') !== FALSE) {
		//	deprecated use of zp-core in URL
		require_once(PLUGIN_SERVERPATH . 'deprecated-functions/class.php');
		deprecated_functions::logZPCore($uri, '');
	}
}

if (version_compare(PHP_VERSION, PHP_MIN_SUPPORTED_VERSION, '<')) {
	npgFilters::register('admin_note', 'phpWarn');
}
