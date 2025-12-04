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

$root_classes = [
		'classes' => __DIR__ . '/classes.php',
		'gallery' => __DIR__ . '/class-gallery.php',
		'album' => __DIR__ . '/class-album.php',
		'image' => __DIR__ . '/class-image.php',
		'search' => __DIR__ . '/class-search.php'
];

foreach ($root_classes as $class => $path) {
	require_once($path);
	if (DEBUG_PLUGINS) {
		$_Script_processing_timer['root»' . $class] = microtime(true);
	}
}

if (!DEBUG_PLUGINS) {
	$_Script_processing_timer['root classes'] = microtime(true);
}

$_loaded_plugins = array();
// load the class & filter plugins
if (abs(OFFSET_PATH) == 2) {
	// setup does not need (and might have problems with) plugins so just load some specific ones
	//	NOTE: these should be ordered by priority, descending
	$enabled = array(
			'dynamic-locale' => array('priority' => 10 | CLASS_PLUGIN, 'path' => __DIR__ . '/' . PLUGIN_FOLDER . '/dynamic-locale.php')
	);
	if (extensionEnabled('googleTFA')) {
		$enabled['googleTFA'] = array('priority' => 5 | CLASS_PLUGIN, 'path' => __DIR__ . '/' . PLUGIN_FOLDER . '/googleTFA.php');
	}
} else {
	$enabled = getEnabledPlugins();
}

foreach ($enabled as $extension => $plugin) {
	$priority = $plugin['priority'];
	if ($priority & CLASS_PLUGIN) {
		require_once($plugin['path']);
		if (DEBUG_PLUGINS) {
			$_Script_processing_timer['classes»' . $extension] = microtime(true);
		}
		$_loaded_plugins[$extension] = $extension;
	}
}
if (!DEBUG_PLUGINS) {
	$_Script_processing_timer['plugin classes'] = microtime(true);
}


//	check for logged in users and set up the locale
require_once(__DIR__ . '/auth_processor.php');
define('SITE_LOCALE_OPTION', i18n::setMainDomain());
//	process any differred language strings
$_active_languages = $_all_languages = NULL; //	clear out so that they will get translated properly
foreach ($_plugin_differed_actions as $callback) {
	call_user_func($callback);
}
?>