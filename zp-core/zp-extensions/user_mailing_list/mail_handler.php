<?php

/**
 * Handles sending the mailing list e-mails
 *
 * @Copyright 2014 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 * @package plugins/user_mailing_list
 */
// UTF-8 Ø
define('OFFSET_PATH', 4);
require_once(dirname(dirname(dirname(__FILE__))) . '/admin-globals.php');
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
$admins = $_authority->getAdministrators();
$admincount = count($admins);
foreach ($admins as $admin) {
	if (isset($_POST["admin_" . $admin['id']])) {
		if ($admin['name']) {
			$bccList[$admin['name']] = $admin['email'];
		} else {
			$bccList[] = $admin['email'];
		}
	}
}
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