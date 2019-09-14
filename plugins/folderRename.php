<?php

/*
 *
 * This plugin enables sites prepare for the renaming of the zp-core and zp-extension
 * folders which will occur in a future release.
 * You can temporarily rename the folders to test if there are any adverse side effects.
 *
 * The <i>utility functions</i> button will cause a renaming of the <i>core</i> and <i>extension</i> folders.
 * if the "<i>core</i>" folder is <i>zp-core</i>:
 *
 * <code>zp-core</code> =&gt; <code>npgCore</code> and
 * <code>zp-extensions</code> =&gt; <code>extensions</code>
 *
 * if the "<i>core</i>" folder is <i>npgCore</i>:
 *
 * <code>npgCore</code> =&gt; <code>zp-core</code> and
 * <code>extensions</code> =&gt; <code>zp-extensions</code>
 *
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/folderRename
 * @pluginCategory tools
 *
 * @Copyright Stephen L Billard permission granted for use in conjunction with netPhotoGraphics. All other rights reserved
 */
// force UTF-8 Ø

$plugin_is_filter = 5 | ADMIN_PLUGIN;
$plugin_description = gettext('Renames the core and extension folders.');
$plugin_disable = npgFunctions::hasPrimaryScripts() ? '' : gettext('Disabled for clone sites.');

npgFilters::register('admin_utilities_buttons', 'folderRename_button');

function folderRename_button($buttons) {
	switch (CORE_FOLDER) {
		case 'zp-core':
			$extensions = PLUGIN_PATH;
			$core = 'npgCore';
			break;
		default:
			$extensions = 'zp-extensions';
			$core = 'zp-core';
			break;
	}
	$buttons[] = array(
			'category' => gettext('Updates'),
			'enable' => true,
			'button_text' => gettext('Rename core folders'),
			'formname' => 'folderRename_template.php',
			'action' => '#',
			'icon' => SETUP,
			'title' => sprintf(gettext('Renames the "%1$s" folder to "%2$s" and the "%3$s" folder to "%4$s."'), CORE_FOLDER, $core, PLUGIN_FOLDER, $extensions),
			'alt' => '',
			'hidden' => '<input type="hidden" name="renameFolders" value="1" />',
			'rights' => ADMIN_RIGHTS,
			'XSRFTag' => 'renameFolders'
	);
	return $buttons;
}

if (isset($_GET['renameFolders'])) {
	XSRFdefender('renameFolders');

	require_once(CORE_SERVERPATH . 'reconfigure.php');

	list($diff, $needs) = checkSignature(2);
	if (empty($needs)) {

		require_once(CORE_SERVERPATH . 'setup/setup-functions.php');
		switch (CORE_FOLDER) {
			case 'zp-core':
				$extensions = basename($rename['zp-core/zp-extensions'] = 'zp-core/' . PLUGIN_PATH);
				$core = $rename['zp-core'] = 'npgCore';
				break;
			default:
				$extensions = $rename['npgCore/' . PLUGIN_PATH] = 'npgCore/zp-extensions';
				$core = $rename['npgCore'] = 'zp-core';
				break;
		}


		foreach ($rename as $oldname => $newname) {
			chmod(SERVERPATH . '/' . $oldname, 0777);
			rename(SERVERPATH . '/' . $oldname, SERVERPATH . '/' . $newname);
			chmod(SERVERPATH . '/' . $newname, FOLDER_MOD);
			npgFilters::apply('security_misc', true, 'folder_rename', 'admin_auth', $oldname . ' => ' . $newname);
		}

		unlink(SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/core-locator.npg'); //	so setup won't undo the request
		header('Location:' . WEBPATH . '/' . $core . '/setup/index.php?autorun=admin');
		exit();
	} else {
		npgFilters::apply('log_setup', false, 'restore', implode(', ', $needs));
		$class = 'errorbox fade-message';
		$msg = gettext('Setup files restore failed.');
	}
}