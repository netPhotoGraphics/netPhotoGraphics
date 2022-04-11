<?php

/**
 * processes the authorization (or login) of users
 *
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 */
// force UTF-8 Ø

global $_current_admin_obj, $_loggedin, $_authority;
$_current_admin_obj = null;
if (!class_exists('npg_Authority')) {
	require_once(__DIR__ . '/class-auth.php');
}
$_authority = new npg_Authority();

foreach (npg_Authority::getRights() as $key => $right) {
	define($key, $right['value']);
}

define('MANAGED_OBJECT_RIGHTS_EDIT', 1);
define('MANAGED_OBJECT_RIGHTS_UPLOAD', 2);
define('MANAGED_OBJECT_RIGHTS_VIEW', 4);
define('MANAGED_OBJECT_MEMBER', 16);
define('LIST_RIGHTS', NO_RIGHTS);
if (!defined('USER_RIGHTS')) {
	define('USER_RIGHTS', NO_RIGHTS);
}

if (defined('VIEW_ALL_RIGHTS')) {
	define('ALL_ALBUMS_RIGHTS', VIEW_ALL_RIGHTS);
	define('ALL_PAGES_RIGHTS', VIEW_ALL_RIGHTS);
	define('ALL_NEWS_RIGHTS', VIEW_ALL_RIGHTS);
	define('VIEW_SEARCH_RIGHTS', NO_RIGHTS);
	define('VIEW_GALLERY_RIGHTS', NO_RIGHTS);
	define('VIEW_FULLIMAGE_RIGHTS', NO_RIGHTS);
} else {
	define('VIEW_ALL_RIGHTS', ALL_ALBUMS_RIGHTS | ALL_PAGES_RIGHTS | ALL_NEWS_RIGHTS);
}


// If the auth variable gets set somehow before this, get rid of it.
$_loggedin = false;

if (isset($_POST['login'])) { //	Handle the login form.
	$_loggedin = $_authority->handleLogon();
	if ($_loggedin) {
		if (isset($_POST['redirect'])) {
			$redirect = sanitizeRedirect($_POST['redirect']);
			if (!empty($redirect)) {
				header("Location: " . $redirect);
				exit();
			}
		}
	}
} else { //	no login form, check the cookie
	if (isset($_GET['ticket'])) { // password reset query
		if (isset($_GET['user'])) {
			$get_user = sanitize($_GET['user']);
		} else {
			$get_user = NULL;
		}
		$_authority->validateTicket(sanitize($_GET['ticket']), $get_user);
	} else {
		$_loggedin = $_authority->checkCookieCredentials();
		if (!$_loggedin && isset($_SESSION['admin'][$cloneid = bin2hex(FULLWEBPATH)])) { //	"passed" login
			$user = unserialize($_SESSION['admin'][$cloneid]);
			$user2 = $_authority->getAnAdmin(array('`user`=' => $user->getUser(), '`valid`=' => 1));
			if ($user2 && $user->getPass() == $user2->getPass()) {
				npg_Authority::logUser($user2);
				$_current_admin_obj = $user2;
				$_loggedin = $_current_admin_obj->getRights();
			}
			unset($_SESSION['admin'][$cloneid]);
		}
		unset($cloneid);
	}
}
if ($_loggedin) {
	if (!$_current_admin_obj->reset) {
		$_current_admin_obj->updateLastAccess(TRUE);
	}
	if (secureServer()) {
		// https: refresh the 'ssl_state' marker for redirection
		setNPGCookie("ssl_state", "needed", NULL, ['secure' => FALSE]);
	}
} else {
	if (class_exists('ipBlocker')) {
		ipBlocker::load();
	}
}
// Handle a logout action.
if (isset($_REQUEST['logout']) && $_REQUEST['logout'] > 0) {
	npg_Authority::handleLogout(html_decode(getLogoutLink(array('logout' => -$_REQUEST['logout']))));
}
?>
