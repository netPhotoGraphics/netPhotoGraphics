<?php

/**
 * Used to set the mod_rewrite option.
 * It will not be found unless mod_rewrite is working.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package setup
 *
 */
ini_set('display_errors', 1);

$__script = 'Mod_rewrite';
require_once('setup-functions.php');
register_shutdown_function('shutDownFunction');
require_once(dirname(__DIR__) . '/functions-basic.php');
require_once(dirname(__DIR__) . '/initialize-basic.php');

npg_session_start();

list($usec, $sec) = explode(" ", microtime());
$start = (float) $usec + (float) $sec;

$fullLog = defined('TEST_RELEASE') && TEST_RELEASE || strpos(getOption('markRelease_state'), '-DEBUG') !== false;

setupLog(sprintf(gettext('Mod_rewrite setup started')), $fullLog);

$mod_rewrite = isset($_GET['rewrite']);
if (is_null($mod_rewrite)) {
	$msg = gettext('The option “mod_rewrite” will be set to “enabled”.');
	setOption('mod_rewrite', 1);
} else if ($mod_rewrite) {
	$msg = gettext('The option “mod_rewrite” is “enabled”.');
} else {
	$msg = gettext('The option “mod_rewrite” is “disabled”.');
}
setOption('mod_rewrite_detected', 1);
setOptionDefault('mod_rewrite', 1);
setupLog('<span class="lognotice">' . gettext('Note: “Module mod_rewrite” is working.') . '</span><div class="logAddl">' . $msg . '</div>', $fullLog);

if (isset($_GET['curl'])) {
	echo 0;
} else {
	sendImage(0, 'plugin_' . $extension);
}

list($usec, $sec) = explode(" ", microtime());
$last = (float) $usec + (float) $sec;
/* and record that we finished */
setupLog(sprintf(gettext('Mod_rewrite setup completed in %1$.4f seconds'), $last - $start), $fullLog);

exit();
?>