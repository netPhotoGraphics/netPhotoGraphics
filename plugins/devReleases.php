<?php
/**
 * Provides an install button for development releases
 *
 * This plugin is will place an install button pointing to the current Development
 * release (sbillard/netPhotoGraphics-DEV) repository. Clicking the button will download
 * and install this development build.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/devReleases
 * @pluginCategory development
 *
 * @Copyright 2017 by Stephen L Billard for use in https://%GITHUB%/netPhotoGraphics and derivitives
 *
 * permission granted for use in conjunction with netPhotoGraphics. All other rights reserved
 */
// force UTF-8 Ø

$plugin_is_filter = 5 | ADMIN_PLUGIN;
$plugin_description = gettext('Provides an install button from the development releases.');

require_once(PLUGIN_SERVERPATH . 'common/gitHubAPI/github-api.php');

use Milo\Github;

if (isset($_GET['action'])) {
	if ($_GET['action'] == 'check_update') {
		XSRFdefender('check_update');
		purgeOption('getDEVUpdates_lastCheck');
		purgeOption('getUpdates_lastCheck');
		$_GET['update_check'] = true;
	}
	if ($_GET['action'] == 'install_dev') {
		XSRFdefender('install_update');
		admin_securityChecks(ADMIN_RIGHTS, currentRelativeURL());
		$devVersionURI = getOption('getDEVUpdates_latest');
		if ($msg = getRemoteFile($devVersionURI, SERVERPATH)) {
			$found = file_exists($file = SERVERPATH . '/' . basename($devVersionURI));
			if ($found) {
				unlink($file);
			}
			purgeOption('getDEVUpdates_lastCheck');
			purgeOption('getUpdates_lastCheck');
			$_SESSION['errormessage'] = $msg;
			header('HTTP/1.0 303 See Other');
			header("Status: 303 See Other");
			header('location: ' . getAdminLink('admin.php') . '?action=session&error');
		} else {
			// let the admin script handle the install
			header('HTTP/1.0 303 See Other');
			header("Status: 303 See Other");
			header('location: ' . getAdminLink('admin.php') . '?action=install_update&XSRFToken=' . getXSRFToken('install_update'));
		}
		exit();
	}
}

if (class_exists('Milo\Github\Api') && npgFunctions::hasPrimaryScripts()) {
	if (getOption('getDEVUpdates_lastCheck') + 8640 < time()) {
		setOption('getDEVUpdates_lastCheck', time());
		try {
			$api = new Github\Api;
			$fullRepoResponse = $api->get('/repos/:owner/:repo/releases/latest', array('owner' => 'sbillard', 'repo' => 'netPhotoGraphics-DEV'));
			$fullRepoData = $api->decode($fullRepoResponse);
			$assets = $fullRepoData->assets;

			if (!empty($assets)) {
				$item = array_pop($assets);
				setOption('getDEVUpdates_latest', $item->browser_download_url);
			}
		} catch (Exception $e) {
			debugLog(gettext('GitHub repository not accessible. ') . $e);
		}
	}

	npgFilters::register('admin_utilities_buttons', 'devReleases::buttons');
	if (isset($_GET['update_check'])) {
		npgFilters::register('admin_note', 'devReleases::notice');
	}
}

class devReleases {

	static function buttons($buttons) {
		preg_match('~[^\d]*(.*)~', stripSuffix(basename(getOption('getDEVUpdates_latest'))), $matches);
		$devVersion = $matches[1];
		$npgVersion = preg_replace('~[^0-9,.]~', '', NETPHOTOGRAPHICS_VERSION_CONCISE);

		if (version_compare(preg_replace('~[^0-9,.]~', '', $devVersion), $npgVersion, '>')) {
			$buttons[] = array(
					'XSRFTag' => 'install_update',
					'category' => gettext('Updates'),
					'enable' => 2,
					'button_text' => sprintf(gettext('Install DEV %1$s'), $devVersion),
					'formname' => 'download_Dev_update',
					'action' => getAdminLink('admin.php') . '?action=install_dev',
					'icon' => INSTALL,
					'alt' => '',
					'title' => sprintf(gettext('Download and install netPhotoGraphics development version %1$s on your site.'), $devVersion),
					'hidden' => '<input type = "hidden" name = "action" value = "install_dev" />',
					'rights' => ADMIN_RIGHTS
			);
		} else {
			$buttons[] = array(
					'XSRFTag' => 'check_update',
					'category' => gettext('Updates'),
					'enable' => 1,
					'button_text' => gettext('Check for updates'),
					'formname' => 'check_update',
					'action' => getAdminLink('admin.php') . '?action=check_update',
					'icon' => CLOCKWISE_OPEN_CIRCLE_ARROW_GREEN,
					'alt' => '',
					'title' => gettext('Check for newer versions of netPhotoGraphics.'),
					'hidden' => '<input type = "hidden" name = "action" value = "check_update" />',
					'rights' => ADMIN_RIGHTS
			);
		}
		return $buttons;
	}

	static function notice() {
		$newestVersionURI = getOption('getUpdates_latest');
		$newestVersion = preg_replace('~[^0-9,.]~', '', str_replace('setup-', '', stripSuffix(basename($newestVersionURI))));
		$npgVersion = preg_replace('~[^0-9,.]~', '', NETPHOTOGRAPHICS_VERSION_CONCISE);

		switch (version_compare($newestVersion, $npgVersion)) {
			case -1:
				$msg = gettext('You are running a version greater than the current release.');
				break;
			case 0:
				$msg = gettext('You are running the latest netPhotoGraphics version.');
				break;
			case 1:
				$msg = gettext('There is an update available.');
				break;
		}
		?>
		<div class="messagebox fade-message">
			<h2><?php echo $msg; ?></h2>
		</div>
		<?php
	}

}
