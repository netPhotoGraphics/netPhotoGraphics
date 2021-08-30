<?php

/**
 * Used for setting theme/plugin default options
 *
 * @author Stephen Billard (sbillard)
 *
 * @package setup
 *
 */
ini_set('display_errors', 1);

define('OFFSET_PATH', 2);
require_once('setup-functions.php');
register_shutdown_function('shutDownFunction');
require_once(dirname(__DIR__) . '/functions-basic.php');

require_once(dirname(__DIR__) . '/initialize-basic.php');

npg_session_start();

list($usec, $sec) = explode(" ", microtime());
$startTO = (float) $usec + (float) $sec;

require_once(dirname(__DIR__) . '/admin-globals.php');

define('ZENFOLDER', CORE_FOLDER); //	since the zenphotoCompatibilityPack will not be present

$fullLog = isset($_GET['fullLog']);

$theme = sanitize($_REQUEST['theme']);
$__script = 'Theme:' . $theme;

setupLog(sprintf(gettext('Theme:%s setup started'), $theme), $fullLog);

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

	setupLog(sprintf(gettext('Theme:%s option interface instantiated'), $theme), $fullLog);
}
/* then set any "standard" options that may not have been covered by the theme */
standardThemeOptions($theme, NULL);

if (protectedTheme($theme)) {
	//	purge obsolete theme options
	purgeOption('albums_per_row', $theme);
}

sendImage($_GET['class'], 'theme_' . $theme);

list($usec, $sec) = explode(" ", microtime());
$last = (float) $usec + (float) $sec;
/* and record that we finished */
setupLog(sprintf(gettext('Theme:%s setup completed in %2$.4f seconds'), $theme, $last - $startTO), $fullLog);

exit();
?>