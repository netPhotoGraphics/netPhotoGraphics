<?php

/**
 * Used for insuring that URLs in the database are in "tagged" from and therefor
 * portable.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package setup
 *
 */
// force UTF-8 Ø

define('OFFSET_PATH', 3);
require_once(file_get_contents(dirname(dirname($_SERVER['SCRIPT_FILENAME'])) . '/core-locator.npg') . "admin-globals.php");

admin_securityChecks(ADMIN_RIGHTS, $return = currentRelativeURL());

XSRFdefender('tagURLs');

require_once(PLUGIN_SERVERPATH . 'zenpage.php');

$_gallery->setTitle($_gallery->getTitle('all'));
$_gallery->setDesc($_gallery->getDesc('all'));
$_gallery->setLogonWelcome($_gallery->getLogonWelcome('all'));
$_gallery->setCodeblock($_gallery->getCodeblock('all'));
$updated = $_gallery->save();
if ($updated && $updated != 2) {
	$found = 1;
} else {
	$found = 0;
}

$tables = array('albums', 'images', 'news', 'pages', 'news_categories');
foreach ($tables as $table) {
	$ids = query_full_array('SELECT `id` FROM ' . prefix($table));
	foreach ($ids as $id) {
		set_time_limit(200);
		$obj = getItemByID($table, $id['id']);
		if ($obj) {

			//	general objects
			$obj->setTitle($obj->getTitle('all'));
			$obj->setCodeblock($obj->getCodeblock());

			if (method_exists($obj, 'getDesc')) {
				$obj->setDesc($obj->getDesc('all'));
			}

			if (method_exists($obj, 'setPasswordHint')) {
				$obj->setPasswordHint($obj->getPasswordHint('all'));
			}

			//	albums
			if (method_exists($obj, 'getLocation')) {
				$obj->setLocation($obj->getLocation('all'));
			}

			//	images
			if (method_exists($obj, 'setCopyright')) {
				$obj->setCopyright($obj->getCopyright('all'));
			}

			//	zenpage objects
			if (method_exists($obj, 'getExtraContent')) {
				$obj->setContent($obj->getContent('all'));
				$obj->setExtraContent($obj->getExtraContent('all'));
			}

			$updated = $obj->save();
			if ($updated && $updated != 2) {
				$found++;
			}
		}
	}
}
$message = sprintf(ngettext('%1$s object with explicit URLs changed', '%1$s objects with explicit URLs changed', $found), $found);
header('Location: ' . getAdminLink('admin.php') . '?action=external&msg=' . $message);
exit();
