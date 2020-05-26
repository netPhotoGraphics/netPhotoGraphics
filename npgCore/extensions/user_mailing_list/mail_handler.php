<?php

/**
 * Handles sending the mailing list e-mails
 *
 * @Copyright 2019 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 * @package plugins/user_mailing_list
 */
// UTF-8 Ø
define('OFFSET_PATH', 4);
require_once(dirname(dirname(__DIR__)) . '/admin-globals.php');
require_once(CORE_SERVERPATH . 'reconfigure.php');

admin_securityChecks(ADMIN_RIGHTS, currentRelativeURL());

XSRFdefender('mailing_list');

//form handling stuff to add...
$subject = NULL;
$message = NULL;
if (isset($_POST['subject'])) {
	$subject = sanitize($_POST['subject']);
}
if (isset($_POST['message'])) {
	$message = sanitize($_POST['message'], 0);
}

$toList = $bccList = array();
$admins = $_POST['mailto'];
$adminlist = $_authority->getAdministrators('all');

foreach ($admins as $adminid) {
	$admin = $adminlist[$adminid];
	if ($admin['valid']) { //	is a user
		if ($admin['name']) {
			$bccList[$admin['name']] = $admin['email'];
		} else {
			$bccList[] = $admin['email'];
		}
	} else {
		if ($admin['name'] == 'group') {
			$group = $admin['user'];
			foreach ($adminlist as $member) {
				if ($member['group'] == $group && $member['email']) {
					if ($member['name']) {
						$bccList[$member['name']] = $member['email'];
					} else {
						$bccList[] = $member['email'];
					}
				}
			}
		}
	}
}
$bccList = array_unique($bccList);

$currentadminmail = $_current_admin_obj->getEmail();
if (!empty($currentadminmail)) {
	$name = $_current_admin_obj->getName();
	if ($name) {
		$toList[$name] = $currentadminmail;
	} else {
		$toList[] = $currentadminmail;
	}
}

$err_msg = npgFunctions::mail($subject, $message, $toList, NULL, $bccList);
if ($err_msg) {
	debugLogVar([gettext('user_mailing_list error') => $err_msg]);
}
?>