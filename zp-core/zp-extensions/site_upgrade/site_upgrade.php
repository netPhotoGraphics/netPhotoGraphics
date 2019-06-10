<?php

/**
 * @package plugins/site_upgrade
 */
define('OFFSET_PATH', 3);
require_once(dirname(dirname(dirname(__FILE__))) . '/admin-globals.php');
require_once(CORE_SERVERPATH . 'lib-config.php');

admin_securityChecks(ALBUM_RIGHTS, currentRelativeURL());

switch (isset($_GET['siteState']) ? $_GET['siteState'] : NULL) {
	case 'closed':
		$report = '';
		setSiteState('closed');
		npgFilters::apply('security_misc', true, 'site_upgrade', 'admin_auth', 'closed');

		if (extensionEnabled('clone')) {
			require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/clone.php');
			if (class_exists('clone')) {
				$clones = npgClone::clones();
				foreach ($clones as $clone => $data) {
					setSiteState('closed', $clone . '/');
				}
			}
		}
		break;
	case 'open':
		$report = gettext('Site is viewable.');
		setSiteState('open');
		npgFilters::apply('security_misc', true, 'site_upgrade', 'admin_auth', 'open');

		if (extensionEnabled('clone')) {
			require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/clone.php');
			if (class_exists('clone')) {
				$clones = npgClone::clones();
				foreach ($clones as $clone => $data) {
					setSiteState('open', $clone . '/');
				}
			}
		}
		break;
	case 'closed_for_test':
		$report = '';
		setSiteState('closed_for_test');
		npgFilters::apply('security_misc', true, 'site_upgrade', 'admin_auth', 'closed_for_test');

		if (extensionEnabled('clone')) {
			require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/clone.php');
			if (class_exists('clone')) {
				$clones = npgClone::clones();
				foreach ($clones as $clone => $data) {
					setSiteState('closed_for_test', $clone . '/');
				}
			}
		}
		break;
}

header('Location: ' . getAdminLink('admin.php') . '?report=' . $report);
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
	$_config_contents = @file_get_contents($folder . DATA_FOLDER . '/' . CONFIGFILE);
	$_config_contents = configFile::update('site_upgrade_state', $state, $_config_contents);
	configFile::store($_config_contents, $folder);
	$_configMutex->unlock();
}

?>