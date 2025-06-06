<?php

/**
 * Used for setting theme/plugin default options
 *
 * @author Stephen Billard (sbillard)
 *
 * @package setup
 *
 */
define('OFFSET_PATH', 2);
require_once('setup-functions.php');
register_shutdown_function('shutDownFunction');
require_once(dirname(__DIR__) . '/initialize-basic.php');

if ($nolog = isset($_GET['debug']) || isset($_GET['fail'])) {
	ini_set('display_errors', 1);
} else {
	ini_set('display_errors', 0);
}

list($usec, $sec) = explode(" ", microtime());
$startTO = (float) $usec + (float) $sec;

require_once(dirname(__DIR__) . '/admin-globals.php');

define('ZENFOLDER', CORE_FOLDER); //	since the zenphotoCompatibilityPack will not be present

$icon = $_GET['class'];
$fullLog = !$nolog && (isset($_GET['fullLog']) || $icon == 2);

$theme = sanitize($_REQUEST['theme']);
if ($icon == 2) {
	$name = '<s>' . $theme . '</s>';
} else {
	$name = $theme;
}
$__script = 'Theme:' . $theme;

setupLog(sprintf(gettext('Theme:%s setup started'), $name), $fullLog);

$requirePath = getPlugin('themeoptions.php', $theme);
if (!empty($requirePath)) {
	//	load some theme support plugins that have option interedependencies
	require_once(PLUGIN_SERVERPATH . 'cacheManager.php');
	require_once(PLUGIN_SERVERPATH . 'menu_manager.php');
	require_once(PLUGIN_SERVERPATH . 'colorbox_js.php');
	require_once(PLUGIN_SERVERPATH . 'deprecated-functions.php');

	ob_start(); //	Just in case the themeOptions emits output!
	require_once(SERVERPATH . '/' . THEMEFOLDER . '/' . $theme . '/themeoptions.php');
	/* prime the default theme options */
	if (!$_current_admin_obj) {
		$_current_admin_obj = $_authority->getMasterUser(); //	option interface can presume logged in
		$_loggedin = $_current_admin_obj->getRights();
	}
	$optionHandler = new ThemeOptions(true);
	setThemeOption('constructed', 1, NULL, $theme); //	mark the theme "constructed"
	@ob_end_clean(); //	Flush any unwanted output

	setupLog(sprintf(gettext('Theme:%s option interface instantiated'), $name), $fullLog);
}
/* then set any "standard" options that may not have been covered by the theme */
standardThemeOptions($theme, NULL);

if (protectedTheme($theme)) {
	//	purge obsolete theme options
	purgeOption('albums_per_row', $theme);
}

list($usec, $sec) = explode(" ", microtime());
$last = (float) $usec + (float) $sec;
/* and record that we finished */
setupLog(sprintf(gettext('Theme:%s setup completed in %2$.4f seconds'), $name, $last - $startTO), $fullLog);

if (isset($_GET['curl'])) {
	echo $icon + 1;
} else {
	sendImage($icon, 'plugin_' . $extension);
}
db_close();
if (function_exists('opcache_reset')) {
	opcache_reset();
}
exit();
