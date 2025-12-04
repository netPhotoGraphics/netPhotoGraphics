<?php

/**
 * root script for for the site
 * @package core
 *
 */
// force UTF-8 Ø
if (!defined('OFFSET_PATH'))
	die(); //	no direct linking

require_once(__DIR__ . '/functions.php');

if (GALLERY_SESSION || npg_loggedin()) {
	npg_session_start();
}
if (function_exists('openssl_encrypt')) {
	require_once(CORE_SERVERPATH . 'class.ncrypt.php');
	$_themeCript = new mukto90\Ncrypt;
	$_themeCript->set_secret_key(HASH_SEED);
	$_themeCript->set_secret_iv(SECRET_IV);
	$_themeCript->set_cipher(INCRIPTION_METHOD);
}
$_Script_processing_timer['basic requirements'] = microtime(true);

npgFilters::apply('feature_plugin_load');

foreach (getEnabledPlugins() as $extension => $plugin) {
	$loadtype = $plugin['priority'];
	if ($loadtype & FEATURE_PLUGIN) {
		require_once($plugin['path']);
		if (DEBUG_PLUGINS) {
			$_loaded_plugins[$extension] = $extension;
			$_Script_processing_timer['feature plugins»' . $extension] = microtime(true);
		}
	}
}
if (!DEBUG_PLUGINS) {
	$_Script_processing_timer['feature plugins'] = microtime(true);
}

require_once(CORE_SERVERPATH . 'rewrite.php');
require_once(CORE_SERVERPATH . 'template-functions.php');
if (!defined('SEO_FULLWEBPATH')) {
	define('SEO_FULLWEBPATH', FULLWEBPATH);
	define('SEO_WEBPATH', WEBPATH);
}
checkInstall();
// who cares if MOD_REWRITE is set. If we somehow got redirected here, handle the rewrite
rewriteHandler();
recordPolicyACK();
$_Script_processing_timer['general functions'] = microtime(true);

/**
 * Invoke the controller to handle requests
 */
require_once(CORE_SERVERPATH . 'lib-controller.php');
require_once(CORE_SERVERPATH . 'controller.php');

$_index_theme = $_themeScript = '';
$_current_page_check = 'checkPageValidity';

// Display an arbitrary theme-included PHP page
if (isset($_GET['p'])) {
	$_index_theme = Controller::prepareCustomPage();
	// Display an Image page.
} else if (in_context(NPG_IMAGE)) {
	$_index_theme = Controller::prepareImagePage();
	// Display an Album page.
} else if (in_context(NPG_ALBUM)) {
	$_index_theme = Controller::prepareAlbumPage();
	// Display the Index page.
} else if (in_context(NPG_INDEX)) {
	$_index_theme = Controller::prepareIndexPage();
} else {
	$_index_theme = setupTheme();
}
$_Script_processing_timer['controller'] = microtime(true);

//	Load the THEME plugins
if (preg_match('~' . CORE_FOLDER . '~', $_themeScript)) {
	$custom = false;
} else {
	foreach (getEnabledPlugins() as $extension => $plugin) {
		$loadtype = $plugin['priority'];
		if ($loadtype & THEME_PLUGIN) {
			require_once($plugin['path']);
			if (DEBUG_PLUGINS) {
				$_loaded_plugins[$extension] = $extension;
				$_Script_processing_timer['theme plugins»' . $extension] = microtime(true);
			}
		}
	}
	if (!DEBUG_PLUGINS) {
		$_Script_processing_timer['theme plugins'] = microtime(true);
	}
	$_themeScript = npgFilters::apply('load_theme_script', $_themeScript, $_requested_object);
	$custom = SERVERPATH . '/' . THEMEFOLDER . '/' . internalToFilesystem($_index_theme) . '/functions.php';
	if (file_exists($custom)) {
		require_once($custom);
	}
}

//	HTML caching?
if ($_requested_object) {
	$_HTML_cache->startHTMLCache();
}

if (in_context(NPG_ALBUM | NPG_SEARCH)) {
	$_transitionImageCount = getTransitionImageCount();
}

//check for valid page number (may be theme dependent!)
if ($_current_page < 0) {
	$_requested_object = false;
} else if ($_requested_object && $_current_page > 1) {
	$_requested_object = $_current_page_check($_requested_object, $_gallery_page, $_current_page);
	if (!$_requested_object && extensionEnabled('themeSwitcher') && isset($_GET['themeSwitcher'])) {
		//might just be a switched-to theme that does not have the same pagination,
		//set page to 1 and procede
		$_requested_object = $_current_page = 1;
	}
}

if ($_requested_object && $_themeScript && file_exists($_themeScript = SERVERPATH . "/" . internalToFilesystem($_themeScript))) {
	if (!checkAccess($hint, $show)) { // not ok to view
		//	don't cache the logon page or you can never see the real one
		$_HTML_cache->abortHTMLCache(true);
		header("Cache-Control: no-cache, no-store, private;, must-revalidate"); // HTTP 1.1.
		header("Pragma: no-cache"); // HTTP 1.0.
		header("Expires: 0"); // Proxies.
		$_gallery_page = 'password.php';
		$_themeScript = SERVERPATH . '/' . THEMEFOLDER . '/' . $_index_theme . '/password.php';
		if (!file_exists(internalToFilesystem($_themeScript))) {
			$_themeScript = CORE_SERVERPATH . 'password.php';
		}
	} else {
		unset($hint);
		unset($show);
	}

	//update publish state, but only on static cache expiry intervals
	if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/lastPublishCheck.cfg')) {
		$lastupdate = (int) file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/lastPublishCheck.cfg');
	} else {
		$lastupdate = NULL;
	}
	if (time() - $lastupdate > getOption('static_cache_expire')) {
		$tables = array('albums', 'images');
		if (class_exists('CMS')) {
			$tables = array_merge($tables, array('news', 'pages'));
		}
		foreach ($tables as $table) {
			updatePublished($table);
		}
		file_put_contents(SERVERPATH . '/' . DATA_FOLDER . '/lastPublishCheck.cfg', time());
	}

	// Include the appropriate page for the requested object, and a 200 OK header.
	header('Content-Type: text/html; charset=' . LOCAL_CHARSET);
	header("HTTP/1.0 200 OK");
	header("Status: 200 OK");
	npgFilters::apply('theme_headers');
	include(internalToFilesystem($_themeScript));
} else {
	// If the requested object does not exist, issue a 404 and redirect to the 404.php
	// in the core folder. This script will load the theme 404 page if it exists.
	$_HTML_cache->abortHTMLCache(false);
	include(CORE_SERVERPATH . '404.php');
}

$_Script_processing_timer['theme load'] = microtime(true);
npgFilters::apply('software_information', $_themeScript, $_loaded_plugins, $_index_theme);
db_close(); // close the database as we are done
if (isset($_siteMutex)) { //	unlock the thread mutex if it has been instantiated
	$_siteMutex->unlock();
}
if (TEST_RELEASE) {
	echo "\n";
	$first = $last = array_shift($_Script_processing_timer);

	foreach ($_Script_processing_timer as $step => $cur) {
		printf('<!-- ' . gettext('Script processing %1$s:%2$.6f seconds') . " -->\n", $step, $cur - $last);
		$last = $cur;
	}
	if (count($_Script_processing_timer) > 1) {
		printf('<!-- ' . gettext('Script processing total:%1$.6f seconds') . " -->\n", $last - $first);
	}
}
$_HTML_cache->endHTMLCache();
?>
