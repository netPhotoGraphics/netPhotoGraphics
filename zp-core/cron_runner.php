<?php

/**
 *
 * Cron task handler
 *
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 */
$_current_admin_obj = $_loggedin = NULL;
if (isset($_POST['link'])) {
	if (isset($_GET['offsetPath'])) {
		define('OFFSET_PATH', (int) $_GET['offsetPath']);
	} else {
		define('OFFSET_PATH', 1);
	}
	$_invisible_execute = 1;
	require_once(dirname(__FILE__) . '/functions.php');
	$link = sanitize($_POST['link']);
	if (isset($_POST['auth'])) {
		$auth = sanitize($_POST['auth'], 0);
		$admin = $_authority->getMasterUser();
		if (sha1($link . serialize($admin)) == $auth && $admin->getRights()) {
			$_current_admin_obj = $admin;
			$_loggedin = $admin->getRights();
		}
	}
	require_once('admin-globals.php');
	require_once(CORE_SERVERPATH . 'admin-functions.php');

	admin_securityChecks(ADMIN_RIGHTS, currentRelativeURL());
	npgFilters::apply('security_misc', true, 'cron_runner', 'admin_auth', sprintf('executing %1$s', $link));

	if (isset($_POST['XSRFTag'])) {
		$_REQUEST['XSRFToken'] = $_POST['XSRFToken'] = $_GET['XSRFToken'] = getXSRFToken(sanitize($_POST['XSRFTag']));
	} else {
		unset($_POST['XSRFToken']);
		unset($_GET['XSRFToken']);
		unset($_REQUEST['XSRFToken']);
	}
	require_once($link);
}
?>