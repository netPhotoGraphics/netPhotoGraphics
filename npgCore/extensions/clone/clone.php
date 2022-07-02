<?php

/**
 *
 * site cloner
 *
 * @package admin/clone
 */
// UTF-8 Ø
define('OFFSET_PATH', 4);
require_once(dirname(dirname(__DIR__)) . '/admin-globals.php');
require_once(CORE_SERVERPATH . 'reconfigure.php');
require_once(CORE_SERVERPATH . 'lib-config.php');

admin_securityChecks(ADMIN_RIGHTS, currentRelativeURL());
XSRFdefender('clone');

if (isset($_GET['purge'])) {
	$clones = npgClone::clones(false);
	foreach ($clones as $clone => $data) {
		if (!$data['valid']) {
			query('DELETE FROM ' . prefix('plugin_storage') . ' WHERE `type`="clone" AND `aux`=' . db_quote($clone));
		}
	}
} else {
	$msg = array();
	$folder = sanitize($_GET['clonePath']);
	$newinstall = trim(sanitize($_GET['cloneWebPath']), '/') . '/';
	if (trim($folder, '/') == SERVERPATH) {
		$msg[] = gettext('You attempted to clone to the master install.');
		$success = false;
	} else {
		$success = true;
		$targets = array('docs' => 'dir', CORE_FOLDER => 'dir', USER_PLUGIN_FOLDER => 'dir');

		foreach ($_gallery->getThemes() as $theme => $data) {
			$targets[THEMEFOLDER . '/' . $theme] = 'dir';
		}

		if (!is_dir($folder . DATA_FOLDER)) {
			if (file_exists($folder . ($old = '/zp-data'))) {
				chmod($folder . $old, 0777);
				rename($folder . $old, $folder . '/' . DATA_FOLDER);
			} else {
				mkdir($folder . DATA_FOLDER);
			}
			chmod(SERVERPATH . '/' . DATA_FOLDER, FOLDER_MOD);
		}
		foreach (array(internalToFilesystem('charset_tést.cfg'), internalToFilesystem('charset.tést')) as $charset) {
			if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/' . $charset)) {
				if (file_exists($folder . DATA_FOLDER . '/' . $charset)) {
					chmod($folder . DATA_FOLDER . '/' . $charset, 0777);
					unlink($folder . DATA_FOLDER . '/' . $charset);
				}
				copy(SERVERPATH . '/' . DATA_FOLDER . '/' . $charset, $folder . DATA_FOLDER . '/' . $charset);
			}
		}

		if (!is_dir($folder . THEMEFOLDER)) {
			mkdir($folder . THEMEFOLDER);
		}
		if (!file_exists($folder . '/' . DATA_FOLDER . '/' . CONFIGFILE)) {
			$path = str_replace(array(' ', '/'), '_', trim(str_replace(str_replace(WEBPATH, '/', SERVERPATH), '', $folder), '/')) . '_';
			$_config_contents = file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE);
			$_config_contents = configFile::update('mysql_prefix', $path, $_config_contents);
			file_put_contents($folder . '/' . DATA_FOLDER . '/' . CONFIGFILE, $_config_contents);
		}

		foreach ($targets as $target => $type) {
			if (file_exists($folder . $target)) {
				$link = str_replace('\\', '/', readlink($folder . $target));
				switch ($type) {
					case 'dir':
						if (empty($link) || $link == $folder . $target) {
							// an actual folder
							if (npgFunctions::removeDir($folder . $target)) {
								if (SYMLINK && symlink(SERVERPATH . '/' . $target, $folder . $target)) {
									$msg[] = sprintf(gettext('The existing folder <code>%s</code> was replaced.'), $folder . filesystemToInternal($target)) . "<br />\n";
								} else {
									$msg[] = sprintf(gettext('The existing folder <code>%1$s</code> was removed but Link creation failed.'), $target) . "<br />\n";
									$success = false;
								}
							} else {
								$msg[] = sprintf(gettext('The existing folder <code>%s</code> could not be removed.'), $folder . filesystemToInternal($target)) . "<br />\n";
								$success = false;
							}
						} else {
							// is a symlink
							chmod($folder . $target, 0777);
							$success = @rmdir($folder . $target);
							if (!$success) { // some systems treat it as a dir, others as a file!
								$success = @unlink($folder . $target);
							}
							if ($success) {
								if (SYMLINK && symlink(SERVERPATH . '/' . $target, $folder . $target)) {
									$msg[] = sprintf(gettext('The existing symlink <code>%s</code> was replaced.'), $folder . filesystemToInternal($target)) . "<br />\n";
								} else {
									$msg[] = sprintf(gettext('The existing symlink <code>%s</code> was removed but Link creation failed.'), $target) . "<br />\n";
									$success = false;
								}
								chmod($folder . $target, FOLDER_MOD);
							} else {
								$msg[] = sprintf(gettext('The existing symlink <code>%s</code> could not be removed.'), $folder . filesystemToInternal($target)) . "<br />\n";
							}
						}
						break;
					case 'file':
						chmod($folder . $target, 0777);
						if (unlink($folder . $target)) {
							if (SYMLINK && symlink(SERVERPATH . '/' . $target, $folder . $target)) {
								if ($folder . $target == $link) {
									$msg[] = sprintf(gettext('The existing file <code>%s</code> was replaced.'), $folder . filesystemToInternal($target)) . "<br />\n";
								} else {
									$msg[] = sprintf(gettext('The existing symlink <code>%s</code> was replaced.'), $folder . filesystemToInternal($target)) . "<br />\n";
								}
							} else {
								$msg[] = sprintf(gettext('The existing file <code>%s</code> was removed but Link creation failed.'), $target) . "<br />\n";
								$success = false;
							}
						} else {
							if ($folder . $target == $link) {
								$msg[] = sprintf(gettext('The existing file <code>%s</code> could not be removed.'), $folder . filesystemToInternal($target)) . "<br />\n";
							} else {
								$msg[] = sprintf(gettext('The existing symlink <code>%s</code> could not be removed.'), $folder . filesystemToInternal($target)) . "<br />\n";
							}
							$success = false;
						}
						chmod($folder . $target, FILE_MOD);
						break;
				}
			} else {
				if (SYMLINK && @symlink(realpath(SERVERPATH . '/' . $target), $folder . $target)) {
					$msg[] = sprintf(gettext('<code>%s</code> Link created.'), $target) . "<br />\n";
					switch ($target) {
						case 'zp-core':
							$core = 'npgCore';
							break;
						case 'npgCore':
							$core = 'zp-core';
							break;
						default:
							$core = '';
							break;
					}
					if ($core && file_exists($folder . $core)) {
						$link = str_replace('\\', '/', readlink($folder . $core));
						if (empty($link) || $link == $folder . $core) {
							$success = npgFunctions::removeDir($folder . $core);
						} else {
							$success = rmdir($folder . $core);
							if (!$success) { // some systems treat it as a dir, others as a file!
								$success = unlink($folder . $core);
							}
						}
						if ($success) {
							$msg[] = sprintf(gettext('The existing folder <code>%s</code> was removed.'), $folder . filesystemToInternal($core)) . "<br />\n";
						} else {
							$msg[] = sprintf(gettext('The existing folder <code>%s</code> could not be removed.'), $folder . filesystemToInternal($core)) . "<br />\n";
						}
					}
				} else {
					$msg[] = sprintf(gettext('<code>%s</code> Link creation failed.'), $target) . "<br />\n";
					$success = false;
				}
			}
		}
	}
	if ($success) {
		array_unshift($msg, '<h2>' . sprintf(gettext('Successful clone to %s'), $folder) . '</h2>' . "\n");
		list($diff, $needs) = checkSignature(4);
		if (empty($needs)) {
			$rslt = query_single_row('SELECT `id` FROM ' . prefix('plugin_storage') . ' WHERE `type`="clone" AND `aux`=' . db_quote(rtrim($folder, '/')));
			if (empty($rslt)) {
				query('INSERT INTO ' . prefix('plugin_storage') . '(`type`,`aux`,`data`) VALUES("clone",' . db_quote(rtrim($folder, '/')) . ',' . db_quote(trim($newinstall, '/')) . ')');
			} else {
				query('UPDATE ' . prefix('plugin_storage') . 'SET `data`=' . db_quote(trim($newinstall, '/')) . ' WHERE `id`=' . $rslt['id']);
			}
			$cloneid = bin2hex(rtrim($newinstall, '/'));
			$_SESSION['clone'][$cloneid] = array(
					'link' => $newinstall,
					'UTF8_image_URI' => UTF8_IMAGE_URI,
					'mod_rewrite' => MOD_REWRITE,
					'hash' => HASH_SEED,
					'strong_hash' => getOption('strong_hash'),
					'deprecated_functions_signature' => getOption('deprecated_functions_signature'),
					'zenphotoCompatibilityPack_signature' => getOption('zenphotoCompatibilityPack_signature'),
					'plugins' => getOptionsLike('_plugin_')
			);

			$adminTableDB = db_list_fields('administrators');
			$adminTable = array();
			foreach ($adminTableDB as $key => $datum) {
				// remove don't care fields
				unset($datum['Key']);
				unset($datum['Extra']);
				unset($datum['Privileges']);
				$adminTable[$datum['Field']] = $datum;
			}
			$_SESSION['admin']['db_admin_fields'] = $adminTable;
			$_SESSION['admin'][$cloneid] = serialize($_current_admin_obj);
			//	leave as direct link incase the admin mod_rewrite mechanism is not yet setup
			$msg[] = get_npgButton('button', gettext('setup the new install'), array('buttonClick' => 'window.open(\'' . $newinstall . CORE_FOLDER . '/setup/index.php?autorun\');')) . '<br clear="all">';
		} else {
			$reinstall = '<p>' . sprintf(gettext('Before running setup for <code>%1$s</code> please reinstall the following setup files from the %2$s to this installation:'), $newinstall, NETPHOTOGRAPHICS_VERSION) .
							"\n" . '<ul>' . "\n";
			if (!empty($needs)) {
				foreach ($needs as $script) {
					$reinstall .= '<li>' . CORE_FOLDER . '/setup/' . $script . '</li>' . "\n";
				}
			}
			$reinstall .= '</ul></p>' . "\n";
			$msg[] = $reinstall;
		}
	} else {
		array_unshift($msg, '<h2>' . sprintf(gettext('Clone to <code>%s</code> failed'), $folder) . '</h2>');
	}
}
require_once(PLUGIN_SERVERPATH . 'clone/cloneTab.php');
?>