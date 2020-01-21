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
require_once(dirname(__DIR__) . '/functions-basic.php');

npg_session_start();

require_once(dirname(__DIR__) . '/initialize-basic.php');

list($usec, $sec) = explode(" ", microtime());
$startPO = (float) $usec + (float) $sec;

require_once(dirname(__DIR__) . '/admin-globals.php');
@ini_set('display_errors', 1);
require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/cacheManager.php');

define('ZENFOLDER', CORE_FOLDER); //	since the zenphotoCompatibilityPack will not be present

$icon = $_GET['class'];
$fullLog = isset($_GET['fullLog']) || $icon == 2;

$extension = sanitize($_REQUEST['plugin']);
$__script = 'Plugin:' . $extension;

if ($icon == 2) {
	$name = '<span style="text-decoration: line-through;">' . $extension . '</span>';
} else {
	$name = $extension;
}
setupLog(sprintf(gettext('Plugin:%s setup started'), $name), $fullLog);

$path = getPlugin($extension . '.php');
$p = file_get_contents($path);
if (extensionEnabled($extension)) {
	//	update the enabled priority
	if ($str = isolate('$plugin_is_filter', $p)) {
		eval($str);
	} else {
		$plugin_is_filter = 5 | THEME_PLUGIN;
	}
	$priority = $plugin_is_filter & PLUGIN_PRIORITY;
	if ($plugin_is_filter & CLASS_PLUGIN) {
		$priority .= ' | CLASS_PLUGIN';
	}
	if ($plugin_is_filter & ADMIN_PLUGIN) {
		$priority .= ' | ADMIN_PLUGIN';
	}
	if ($plugin_is_filter & FEATURE_PLUGIN) {
		$priority .= ' | FEATURE_PLUGIN';
	}
	if ($plugin_is_filter & THEME_PLUGIN) {
		$priority .= ' | THEME_PLUGIN';
	}

	setupLog(sprintf(gettext('Plugin:%s enabled (%2$s)'), $name, $priority), $fullLog);
	enableExtension($extension, $plugin_is_filter);
}

$_conf_vars['special_pages'] = array(); //	we want to look only at ones set by this plugin
unset($plugin_disable);
require_once($path); //	If it faults the shutdown functioin will disable it
if (isset($plugin_disable) && $plugin_disable) {
	enableExtension($extension, 0);
	setupLog(sprintf(gettext('Plugin:%s disabled by <code>$plugin_disable</code>'), $name), $fullLog);
}
foreach ($_conf_vars['special_pages'] as $definition) {
	if (isset($definition['option'])) {
		setOptionDefault($definition['option'], $definition['default'], '', CORE_FOLDER . '/' . PLUGIN_FOLDER . '/' . $extension . '.php');
	}
}
if ($str = isolate('$option_interface', $p)) {
	//	prime the default options
	eval($str);
	setupLog(sprintf(gettext('Plugin:%1$s option interface instantiated (%2$s)'), $name, $option_interface), $fullLog);
	$option_interface = new $option_interface;
	if (method_exists($option_interface, 'getOptionsSupported')) {
		if (!$_current_admin_obj) {
			$_current_admin_obj = $_authority->getMasterUser(); //	option interface can presume logged in
			$_loggedin = $_current_admin_obj->getRights();
		}
		ob_start(); //	some plugins emit output from the getOptionsSupported() method
		$options = $option_interface->getOptionsSupported();
		ob_end_clean();
		$owner = replaceScriptPath($path);
		foreach ($options as $option) {
			if (isset($option['key'])) {
				setOptionDefault($option['key'], NULL, '', $owner);
			}
		}
	}
}

sendImage($icon, 'plugin_' . $extension);

list($usec, $sec) = explode(" ", microtime());
$last = (float) $usec + (float) $sec;
/* and record that we finished */
setupLog(sprintf(gettext('Plugin:%1$s setup completed in %2$.4f seconds'), $name, $last - $startPO), $fullLog);

exit();
?>