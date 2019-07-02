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
require_once(dirname(dirname(__FILE__)) . '/functions-basic.php');

npg_session_start();

require_once(dirname(dirname(__FILE__)) . '/initialize-basic.php');

list($usec, $sec) = explode(" ", microtime());
$startTO = (float) $usec + (float) $sec;

require_once(dirname(dirname(__FILE__)) . '/admin-globals.php');

define('ZENFOLDER', CORE_FOLDER); //	since the zenphotoCompatibilityPack will not be present

@ini_set('display_errors', 1);

$fullLog = isset($_GET['fullLog']);

$theme = sanitize($_REQUEST['theme']);
$__script = 'Theme:' . $theme;

setupLog(sprintf(gettext('Theme:%s setup started'), $theme), $fullLog);

$requirePath = getPlugin('themeoptions.php', $theme);
if (!empty($requirePath)) {
	//	load some theme support plugins that have option interedependencies
	require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/cacheManager.php');
	require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/menu_manager.php');
	require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/colorbox_js.php');
	require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/deprecated-functions.php');

	require_once(SERVERPATH . '/' . THEMEFOLDER . '/' . $theme . '/themeoptions.php');
	/* prime the default theme options */
	$optionHandler = new ThemeOptions(true);
	setThemeOption('constructed', 1, NULL, $theme); //	mark the theme "constructed"

	setupLog(sprintf(gettext('Theme:%s option interface instantiated'), $theme), $fullLog);
}
/* then set any "standard" options that may not have been covered by the theme */
standardThemeOptions($theme, NULL);

sendImage($_GET['class'], 'theme_' . $theme);

list($usec, $sec) = explode(" ", microtime());
$last = (float) $usec + (float) $sec;
/* and record that we finished */
setupLog(sprintf(gettext('Theme:%s setup completed in %2$.4f seconds'), $theme, $last - $startTO), $fullLog);

exit();
?>