<?php

/**
 * "Clones" the current installation to a new location using symlinks.
 * The <i>%CORE_FOLDER%</i>, and <i>%USER_PLUGIN_FOLDER%</i>
 * folders file are symlinked. Setup will create the other needed folders.
 *
 * The <i>Clone</i> tab will take you to the cloning page.
 *
 * Links to previously cloned installations will be listed on this page.
 *
 * You can select a folder destination
 * for a new cloned installation. Upon successful cloning there will be a link to <var>setup</var> for the new
 * installation. (This presumes the <var>setup</var> files are present. If not you will be told which files
 * need to be reloaded.)
 *
 *
 * <b>Note:</b> If the destination already has a installation these files and folders will be removed by the cloning
 * process!
 *
 * The <i>Delete setup scripts</i> button will remove the <var>setup</var> files from the current installation. This is
 * the same function provided by <i>Setup</i> after a successful install. It is provided here because you will likely not want to
 * remove the setup scripts until you have cloned and installed all desired destinations.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/clone
 * @pluginCategory admin
 */
$plugin_is_filter = 5 | ADMIN_PLUGIN;
$plugin_description = gettext('Allows multiple installations to share a single set of script files.');
$plugin_disable = (SYMLINK) ? (npgFunctions::hasPrimaryScripts()) ? false : gettext('Only the primary installation may clone offspring installations.') : gettext('Your server does not support symbolic linking.');

if (OFFSET_PATH == 2) {
	if ($priority = extensionEnabled('cloneZenphoto')) {
		enableExtension('clone', $priority);
		enableExtension('cloneZenphoto', 0);
	}
	$sql = 'UPDATE ' . prefix('plugin_storage') . ' SET `type`="clone" WHERE `type`="cloneZenphoto"';
	query($sql);
}


if ($plugin_disable) {
	enableExtension('clone', 0);
} else {
	require_once(CORE_SERVERPATH . 'reconfigure.php');
	npgFilters::register('admin_tabs', 'npgClone::tabs', -312);

	class npgClone {

		static function tabs($tabs) {
			global $_current_admin_obj;
			if ((npg_loggedin(ADMIN_RIGHTS) && $_current_admin_obj->getID())) {
				$subtabs = $tabs['admin']['subtabs'];
				$subtabs[gettext("clone")] = PLUGIN_FOLDER . '/clone/cloneTab.php?page=admin&tab=clone';
				$tabs['admin']['subtabs'] = $subtabs;
			}
			return $tabs;
		}

		/**
		 * get a list of cloned installations
		 *
		 * @global type $_current_admin_obj
		 * @param bool $valid if true, do not return obsolete entries
		 * @return array
		 */
		static function clones($only_valid = true) {
			global $_current_admin_obj;
			$clones = array();
			$sig = @file_get_contents(CORE_SERVERPATH . 'version.php');
			if ($result = query('SELECT * FROM ' . prefix('plugin_storage') . ' WHERE `type`="clone"')) {
				while ($row = db_fetch_assoc($result)) {
					if (SYMLINK) {
						$path = str_replace('\\', '/', @readlink($row['aux'] . '/' . CORE_FOLDER));
						$valid = !(empty($path) || $path != SERVERPATH . '/' . CORE_FOLDER);
					} else { //	best guess if the clone has been changed
						$clonesig = @file_get_contents($row['aux'] . '/' . CORE_FOLDER . '/version.php');
						$valid = $sig == $clonesig;
					}
					$link = mb_parse_url($row['data']);
					if ($link['host'] != $_SERVER['HTTP_HOST']) {
						$valid = false;
					}
					if ($valid || !$only_valid) {
						$clones[$row['aux']] = array('url' => $row['data'] . '/', 'valid' => $valid);
					}
				}
				db_free_result($result);
			}
			return $clones;
		}

	}

}
?>