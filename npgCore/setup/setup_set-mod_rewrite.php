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

define('OFFSET_PATH', 2);
require_once('setup-functions.php');
register_shutdown_function('shutDownFunction');
require_once(dirname(__DIR__) . '/initialize-basic.php');

$__script = 'Mod_rewrite';
list($usec, $sec) = explode(" ", microtime());
$start = (float) $usec + (float) $sec;

if ($test_release = getOption('markRelease_state')) {
	$test_release = strpos($test_release, '-DEBUG');
}
$fullLog = defined('TEST_RELEASE') && TEST_RELEASE || $test_release !== false;

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

list($usec, $sec) = explode(" ", microtime());
$last = (float) $usec + (float) $sec;
/* and record that we finished */
setupLog(sprintf(gettext('Mod_rewrite setup completed in %1$.4f seconds'), $last - $start), $fullLog);

if (isset($_GET['curl'])) {
	echo 1;
} else {
	sendImage(0, 'Mod_rewrite');
}
db_close();
exit();
?>