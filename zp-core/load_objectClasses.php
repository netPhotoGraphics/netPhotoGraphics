<?php

/**
 *
 * Load the base classes (Image, Album, Gallery, etc.)
 * and any enabled "class" plugins
 *
 * @author Stephen Billard (sbillard)
 *
 * @package core
 */
$_plugin_differed_actions = array(); //	final initialization for class plugins (mostly for language translation issues)

require_once(dirname(__FILE__) . '/classes.php');
require_once(dirname(__FILE__) . '/class-gallery.php');
require_once(dirname(__FILE__) . '/class-album.php');
require_once(dirname(__FILE__) . '/class-image.php');
require_once(dirname(__FILE__) . '/class-search.php');

$_loaded_plugins = array();
// load the class & filter plugins
if (DEBUG_PLUGINS) {
	debugLog('Loading the "class" plugins.');
}
if (abs(OFFSET_PATH) == 2) {
	// setup does not need (and might have problems with) plugins so just load some specific ones
	//	NOTE: these should be ordered by priority, descending
	$enabled = array(
			'dynamic-locale' => array('priority' => 10 | CLASS_PLUGIN, 'path' => dirname(__FILE__) . '/' . PLUGIN_FOLDER . '/dynamic-locale.php')
	);
	if (extensionEnabled('googleTFA')) {
		$enabled['googleTFA'] = array('priority' => 5 | CLASS_PLUGIN, 'path' => dirname(__FILE__) . '/' . PLUGIN_FOLDER . '/googleTFA.php');
	}
} else {
	$enabled = getEnabledPlugins();
}
foreach ($enabled as $extension => $plugin) {
	$priority = $plugin['priority'];
	if ($priority & CLASS_PLUGIN) {
		$start = microtime();
		require_once($plugin['path']);
		if (DEBUG_PLUGINS) {
			npgFunctions::pluginDebug($extension, $priority, $start);
		}
		$_loaded_plugins[$extension] = $extension;
	}
}

//	check for logged in users and set up the locale
require_once(dirname(__FILE__) . '/auth_processor.php');
define('SITE_LOCALE_OPTION', i18n::setMainDomain());
//	process any differred language strings
$_active_languages = $_all_languages = NULL; //	clear out so that they will get translated properly
foreach ($_plugin_differed_actions as $callback) {
	call_user_func($callback);
}
?>