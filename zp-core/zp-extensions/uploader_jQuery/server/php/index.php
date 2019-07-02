<?php
/*
 * adaptation of the upload handler
 */


define('OFFSET_PATH', 3);
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/admin-globals.php' );

$_loggedin = NULL;
if (isset($_POST['auth'])) {
	$hash = sanitize($_POST['auth']);
	$id = sanitize($_POST['id']);
	$_loggedin = $_authority->checkAuthorization($hash, $id);
	admin_securityChecks(UPLOAD_RIGHTS, $return = currentRelativeURL());
} else {
	if (isset($_POST['id'])) {
		?>
		{"files": [
		{
		}
		]}
		<?php
	}
	exit();
}

$folder = npgFilters::apply('admin_upload_process', sanitize_path($_POST['folder']));
$types = array_keys($_images_classes);
$types = npgFilters::apply('upload_filetypes', $types);

$options = array(
		'upload_dir' => $targetPath = ALBUM_FOLDER_SERVERPATH . internalToFilesystem($folder) . '/',
		'upload_url' => imgSrcURI(ALBUM_FOLDER_WEBPATH . $folder) . '/',
		'accept_file_types' => '/(' . implode('|\.', $types) . ')$/i'
);

$new = !is_dir($targetPath);

if (!empty($folder)) {
	if ($new) {
		$rightsalbum = newAlbum(dirname($folder), true, true);
	} else {
		$rightsalbum = newAlbum($folder, true, true);
	}
	if ($rightsalbum->exists) {
		if (!$rightsalbum->isMyItem(UPLOAD_RIGHTS)) {
			if (!npgFilters::apply('admin_managed_albums_access', false, $return)) {
				header('Location: ' . getAdminLink('admin.php'));
				exit();
			}
		}
	} else {
		// upload to the root
		if (!npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
			header('Location: ' . getAdminLink('admin.php'));
			exit();
		}
	}
	if ($new) {
		mkdir_recursive($targetPath, FOLDER_MOD);
		$album = newAlbum($folder);
		$album->setTitle(sanitize($_POST['albumtitle']));
		$album->setOwner($_current_admin_obj->getUser());
		$album->setShow((int) ($_POST['publishalbum'] == 'true'));
		$album->save();
	}
	@chmod($targetPath, FOLDER_MOD);
}

require('UploadHandler.php');
$upload_handler = new UploadHandler($options);
