<?php

/**
 * @package plugins/site_upgrade
 */
if (!defined('OFFSET_PATH')) {
	define('OFFSET_PATH', 3);
}
require_once(dirname(dirname(__DIR__)) . '/admin-globals.php');
require_once(CORE_SERVERPATH . 'lib-config.php');

admin_securityChecks(ADMIN_RIGHTS, currentRelativeURL());
XSRFdefender('site_upgrade');

$report = '';
switch (isset($_REQUEST['siteState']) ? $_REQUEST['siteState'] : NULL) {
	case 'closed':
		setSiteState('closed');
		npgFilters::apply('security_misc', true, 'site_upgrade', 'admin_auth', 'closed');

		if (extensionEnabled('clone')) {
			updateClones('closed');
		}
		break;
	case 'open':
		$report = '?report=' . gettext('Site is viewable.');
		setSiteState('open');
		npgFilters::apply('security_misc', true, 'site_upgrade', 'admin_auth', 'open');

		if (extensionEnabled('clone')) {
			updateClones('open');
		}
		break;
	case 'closed_for_test':
		setSiteState('closed_for_test');
		npgFilters::apply('security_misc', true, 'site_upgrade', 'admin_auth', 'closed_for_test');

		if (extensionEnabled('clone')) {
			updateClones('closed_for_test');
		}
		break;
}

header('Location: ' . getAdminLink('admin.php') . $report);
exit();

/**
 * updates the site status
 * @param string $state
 */
function setSiteState($state, $folder = NULL) {
	if (is_null($folder)) {
		$folder = SERVERPATH . '/';
	}
	$_configMutex = new npgMutex('cF', NULL, $folder . DATA_FOLDER . '/.mutex');
	$_configMutex->lock();
	$_config_contents = file_get_contents($folder . DATA_FOLDER . '/' . CONFIGFILE);
	$_config_contents = configFile::update('site_upgrade_state', $state, $_config_contents);
	configFile::store($_config_contents, $folder);
	$_configMutex->unlock();
}

function updateClones($state) {
	require_once(PLUGIN_SERVERPATH . 'clone.php');
	if (class_exists('npgClone')) {
		$clones = npgClone::clones();
		foreach ($clones as $clone => $data) {
			setSiteState($state, $clone . '/');
		}
	}
}

?>