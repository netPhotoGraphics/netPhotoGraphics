<?php

/**
 * This plugin deals with functions that have either been altered or removed completely.
 *
 * The actual set of functions resides in a <var>deprecated-functions.php</var> script within
 * the plugins folder. (General deprecated functions are in the <var>%PLUGIN_FOLDER%/deprecated-functins</var> folder)
 *
 * Convention is that the deprecated functions script will have a class defined indicataing the following:
 *
 * <dl>
 * 	<dt><var>public static</var></dt><dd>general functions with parameters which have been deprecated.</dd>
 * 	<dt><var>static</var></dt><dd>class methods that have been deprecated.</dd>
 * 	<dt><var>final static</var></dt><dd>class methods with parameters which have been deprecated.</dd>
 * </dl>
 *
 * 	A log entry in the <var>deprecated</var> log is created if a deprecated function is invoked.
 *
 * A utility button is provided that allows you to search themes and plugins for uses of functions which have been deprecated.
 * Use it to be proactive in replacing or changing these items.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/deprecated-functions
 * @pluginCategory development
 */
$plugin_is_filter = 900 | CLASS_PLUGIN;
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext("Provides replacements for deprecated functions.");
	$plugin_notice = gettext("This plugin is <strong>NOT</strong> required for the distributed code.");
}

npgFilters::register('admin_tabs', 'deprecated_functions::tabs', -308);

//Load the deprecated function scripts
require_once(stripSuffix(__FILE__) . '/deprecated-functions.php');
require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/deprecated-functions/class.php');
foreach (getPluginFiles('*.php') as $extension => $plugin) {
	$deprecated = stripSuffix($plugin) . '/deprecated-functions.php';
	if (file_exists($deprecated)) {
		require_once($deprecated);
	}
	unset($deprecated);
}
?>
