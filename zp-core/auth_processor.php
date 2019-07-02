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
	require_once(dirname(__FILE__) . '/class-auth.php');
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

// we have the ssl marker cookie, normally we are already logged in
// but we need to redirect to ssl to retrive the auth cookie (set as secure).
httpsRedirect();


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
		$_authority->validateTicket(sanitize($_GET['ticket']), sanitize(@$_GET['user']));
	} else {
		$_loggedin = $_authority->checkCookieCredentials();
		$cloneid = bin2hex(FULLWEBPATH);
		if (!$_loggedin && isset($_SESSION['admin'][$cloneid])) { //	"passed" login
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
	if (secureServer()) {
		// https: refresh the 'ssl_state' marker for redirection
		setNPGCookie("ssl_state", "needed", NULL, false);
	}
} else {
	if (class_exists('ipBlocker')) {
		ipBlocker::load();
	}
}
// Handle a logout action.
if (isset($_REQUEST['logout'])) {

	$redirect = '?fromlogout';
	if (isset($_GET['p'])) {
		$redirect .= "&p=" . sanitize($_GET['p']);
	}
	if (isset($_GET['searchfields'])) {
		$redirect .= "&searchfields=" . sanitize($_GET['searchfields']);
	}
	if (isset($_GET['words'])) {
		$redirect .= "&words=" . sanitize($_GET['words']);
	}
	if (isset($_GET['date'])) {
		$redirect .= "&date=" . sanitize($_GET['date']);
	}
	if (isset($_GET['album'])) {
		$redirect .= "&album=" . sanitize($_GET['album']);
	}
	if (isset($_GET['image'])) {
		$redirect .= "&image=" . sanitize($_GET['image']);
	}
	if (isset($_GET['title'])) {
		$redirect .= "&title=" . sanitize($_GET['title']);
	}
	if (isset($_GET['page'])) {
		$redirect .= "&page=" . sanitize($_GET['page']);
	}
	if (!empty($redirect)) {
		$redirect = '?' . substr($redirect, 1);
	}
	$location = FULLWEBPATH . '/index.php' . $redirect;
	$location = npg_Authority::handleLogout($location);
	header("Location: " . $location);
	exit();
}
?>
