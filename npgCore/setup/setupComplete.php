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
require_once(dirname(__DIR__) . '/functions.php');
require_once(__DIR__ . '/setup-functions.php');

npg_session_start();
if (sanitize($_POST['errors'])) {
	$result = gettext('Setup <span class="logerror"> completed with errors</span>');
} else {
	$result = gettext('Completed');
}
setupLog(sprintf(gettext('Option setup completed in %1$.4f seconds'), $_POST['optionComplete']), true);
npgFilters::apply('log_setup', true, 'install', $result);
unset($_SESSION['SetupStarted']);

db_close();
exit();
