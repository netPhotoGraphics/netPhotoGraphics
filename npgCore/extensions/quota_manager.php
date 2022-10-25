<?php

/**
 * A quota management system to limit the sum of sizes of uploaded images.
 *
 * Set the default quota on the plugin options page.
 * You can change the quota for individual users on the Admin tab. Users with <var>ADMIN_RIGHTS</var> or <var>MANAGE_ALL_ALBUM_RIGHTS</var>
 * are not subject to quotas and will not be assigned ownership of an image.
 *
 * Images uploaded by a user will be marked as his and will count toward his quota.
 * Images uploaded via FTP or from the <var>files</var> tab will not necessarily have an owner assigned.
 *
 * You may also assign the complete set of images in an albums to a user. (Just the images in the
 * album. If you want to assign images from subalbums, you need to do that for each
 * subalbum.)
 *
 * A user who exceeds his quota will not be allowed to upload more files.
 *
 * Because of the difficulty of policing quotas when ZIP files are uploaded this plugin
 * has an option to diable ZIP file upload.
 *
 * Since uploads via the <var>files</var> tab are like FTP uploads and are not assigned to the user, you should not assign <var>files</var> rights
 * to users with upload limits.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/quota_manager
 * @pluginCategory users
 */
$plugin_is_filter = 5 | ADMIN_PLUGIN;
$plugin_description = gettext("Provides a quota management system to limit the sum of sizes of images a user uploads.");
$plugin_notice = gettext("<strong>Note:</strong> if FTP is used to upload images, manual user assignment is necessary. ZIP file upload is disabled by default as quotas are not applied to the files contained therein.");
$plugin_disable = (npgFilters::has_filter('get_upload_header_text') && !extensionEnabled('quota_manager')) ? sprintf(gettext('<a href="#%1$s"><code>%1$s</code></a> is already enabled.'), stripSuffix(npgFilters::script('get_upload_header_text'))) : '';

$option_interface = 'quota_manager';

require_once(PLUGIN_SERVERPATH . 'common/fieldExtender.php');

if ($plugin_disable) {
	enableExtension('quota_manager', 0);
} else {
	npgFilters::register('save_admin_data', 'quota_manager::save_admin');
	npgFilters::register('edit_admin_custom', 'quota_manager::edit_admin');
	npgFilters::register("mass_edit_selector", "quota_manager::editSelector");
	npgFilters::register('new_image', 'quota_manager::new_image');
	npgFilters::register('image_refresh', 'quota_manager::image_refresh');
	npgFilters::register('check_upload_quota', 'quota_manager::checkQuota');
	npgFilters::register('get_upload_limit', 'quota_manager::getUploadLimit');
	npgFilters::register('get_upload_header_text', 'quota_manager::get_header');
	npgFilters::register('upload_filetypes', 'quota_manager::upload_filetypes');
	npgFilters::register('upload_helper_js', 'quota_manager::upload_helper_js');
}

/**
 * Option handler class
 *
 */
class quota_manager extends fieldExtender {

	/**
	 * class instantiation function
	 *
	 */
	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('quota_default', 250000);
			setOptionDefault('quota_allowZIP', 1);
			parent::constructor('quota_manager', self::fields());
			if (extensionEnabled('quota_manager')) {
				//	populate the `filesize` field
				$sql = 'SELECT a.folder, i.filename, i.id FROM ' . prefix('albums') . ' AS a, ' . prefix('images') . ' AS i WHERE i.filesize IS NULL AND a.id = i.albumid;';
				$result = query($sql);
				while ($row = db_fetch_assoc($result)) {
					$size = filesize(SERVERPATH . '/' . ALBUMFOLDER . '/' . $row['folder'] . '/' . $row['filename']);
					query('UPDATE ' . prefix('images') . ' SET `filesize`=' . $size . ' WHERE `id`=' . $row['id']);
				}
				db_free_result($result);
			}
		}
	}

	static function fields() {
		return array(
				array('table' => 'images', 'name' => 'filesize', 'desc' => gettext('Stores image disk footprint'), 'type' => 'int', 'size' => 4)
		);
	}

	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array(gettext('Default quota') => array('key' => 'quota_default', 'type' => OPTION_TYPE_NUMBER,
						'desc' => gettext('Default size limit in kilobytes.')),
				gettext('Allow ZIP files') => array('key' => 'quota_allowZIP', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('The size of a ZIP file may be slightly smaller than the sum of the <em>image</em> files it contains. Un-check this box if you wish to disable uploading of ZIP files.'))
		);
	}

	function handleOption($option, $currentValue) {

	}

	/**
	 * Saves admin custom data
	 * Called when an admin is saved
	 *
	 * @param object $userobj admin user object
	 * @param string $i prefix for the admin
	 * @param bool $alter will be true if critical admin data may be altered
	 * @return bool
	 */
	static function save_admin($userobj, $i, $alter) {
		if (editSelectorEnabled('user_edit_quota_manager')) {
			/* single image or the General box is enabled
			 * needed to be sure we don't reset these values because the input was disabled
			 */
			if (isset($_POST['user'][$i]['quota']) && $alter) {
				$userobj->setQuota(sanitize_numeric($_POST['user'][$i]['quota']));
			}
		}
		return $userobj;
	}

	/**
	 * Returns table row(s) for edit of an admin user's custom data
	 *
	 * @param string $html always empty
	 * @param $userobj Admin user object
	 * @param string $i prefix for the admin
	 * @param string $background background color for the admin row
	 * @param bool $current true if this admin row is the logged in admin
	 * @return string
	 */
	static function edit_admin($html, $userobj, $i, $background, $current, $local_alterrights) {
		if ($userobj->getRights() & (ADMIN_RIGHTS | MANAGE_ALL_ALBUM_RIGHTS))
			return $html;
		if (!($userobj->getRights() & UPLOAD_RIGHTS))
			return $html;
		$quota = $userobj->getQuota();
		if ($quota == NULL) {
			$quota = getOption('quota_default');
		}
		if ($userobj->getValid()) {
			$used = sprintf(gettext('(%s kb used)'), number_format(quota_manager::getCurrentUse($userobj)));
		} else {
			$used = '';
		}
		$result = '<div class="user_left quota_manager_stuff">' . "\n"
						. gettext("Image storage quota:") . '&nbsp;' .
						sprintf(gettext('Allowed: %s kb'), '<input type="text" size="10" name="user[' . $i . '][quota]" value="' . $quota . '" ' . $local_alterrights . ' />') . ' ' .
						$used . "\n"
						. '</div>' . "\n"
						. '<br class="clearall" />' . "\n";

		return $html . $result;
	}

	static function editSelector($stuff, $whom) {
		switch ($whom) {
			case 'users':
				$stuff['quota_manager'] = gettext('Quotas');
		}
		return $stuff;
	}

	/**
	 * Returns current image usage
	 * @param $userobj Admin user object
	 * @return float
	 */
	static function getCurrentUse($userobj) {
		global $_current_admin_obj;
		if (is_null($userobj)) {
			$userobj = $_current_admin_obj;
		}
		$sql = 'SELECT sum(`filesize`) FROM ' . prefix('images') . ' WHERE `owner`="' . $userobj->getUser() . '"';
		$result = query_single_row($sql);
		return reset($result) / 1024;
	}

	/**
	 * Assigns owner to new image
	 * @param string $image
	 * @return object
	 */
	static function new_image($image) {
		global $_current_admin_obj;
		if (is_object($_current_admin_obj)) {
			$image->set('owner', $_current_admin_obj->getUser());
		}
		$image->save();
		return $image;
	}

	/**
	 * checks to see if the filesize is set and sets it if not
	 * @param unknown_type $image
	 * @return object
	 */
	static function image_refresh($image) {
		$image->save();
		return $image;
	}

	/**
	 * Returns the user's quota
	 * @param int $quota
	 * @return int
	 */
	static function getUploadQuota($quota) {
		global $_current_admin_obj;
		if (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
			$quota = -1;
		} else {
			$quota = $_current_admin_obj->getQuota();
			if ($quota == NULL)
				$quota = getOption('quota_default');
		}
		return $quota;
	}

	/**
	 * Returns the upload limit
	 * @param int $uploadlimit
	 * @return int
	 */
	static function getUploadLimit($uploadlimit) {
		if (!npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
			$uploadlimit = (quota_manager::getUploadQuota(0) - quota_manager::getCurrentUse(NULL)) * 1024;
		}
		return $uploadlimit;
	}

	/**
	 * Checks if upload should be allowed
	 * @param int $error
	 * @param string $image
	 * @return int
	 */
	static function checkQuota($error, $image) {
		if (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
			return UPLOAD_ERR_OK;
		}
		if (getSuffix($image) == 'zip') {
			return UPLOAD_ERR_EXTENSION;
		}
		$quota = quota_manager::getUploadQuota(0);
		if ($image) {
			$size = round(filesize($image) / 1024);
		} else {
			$size = 0; //	no image passed, just want to see if we are at or above quota.
		}
		if ($quota > 0) {
			if (quota_manager::getCurrentUse(NULL) + $size >= $quota) {
				$error = UPLOAD_ERR_QUOTA;
			}
		}
		return $error;
	}

	/**
	 * Returns quota text for header, etc.
	 * @param string $default
	 * @return string
	 */
	static function get_header($default) {
		if (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
			return $default;
		}
		$uploadlimit = quota_manager::getUploadLimit(0);
		if ($uploadlimit <= 1024) {
			$color = 'style="color:red;"';
			$warn = ' <span style="color:red;">' . gettext('Uploading is disabled.') . '</span>';
		} else {
			$color = '';
			$warn = '';
		}
		return sprintf(gettext('Your available upload quota is <span %1$s>%2$s</span> kb.'), $color, number_format(round($uploadlimit / 1024))) . $warn;
	}

	/**
	 * Returns Javascript needed to support quota system
	 * @param string $defaultJS
	 * @return string
	 */
	static function upload_helper_js($defaultJS) {
		$quota = quota_manager::getUploadLimit(99999);
		$quotaOK = $quota < 0 || $quota > 1024;
		if ($quotaOK) {
			$quota_management_js = '';
		} else {
			$quota_management_js = "
				$(document).ready(function() {
					$('#albumselect').hide();
				});";
		}
		return $quota_management_js . $defaultJS;
	}

	/**
	 * Removes ZIP from list of upload suffixes
	 * @param array $types
	 * @return array
	 */
	static function upload_filetypes($types) {
		if (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS) || (getoption('quota_allowZIP'))) {
			return $types;
		}
		$key = array_search('ZIP', $types);
		if ($key !== false)
			unset($types[$key]);
		return $types;
	}

}

function quota_manager_enable($enabled) {
	if ($enabled) {
		$report = gettext('<em>filesize</em> field will be added to the images database.');
	} else {
		$report = gettext('<em>filesize</em> field will be <span style = "color:red;font-weight:bold;">dropped</span> from the images database.');
	}
	requestSetup('quota_manager', $report);
}

?>