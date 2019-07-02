<?php

/**
 * Places security information in a security log
 * The logged data includes:
 * <ul>
 * 	<li>the ip address of the client browser</li>
 * 	<li>the type of entry</li>
 * 	<li>the user/user name</li>
 * 	<li>the success/failure</li>
 * 	<li>the <i>authority</i> granting/denying the request</li>
 * 	<li>Additional information, for instance on failure, the password used</li>
 * </ul>
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/security-logger
 * @pluginCategory admin
 */
$plugin_is_filter = defaultExtension(100 | CLASS_PLUGIN);
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext('Logs selected security events.');
}

$option_interface = 'security_logger';
global $_logCript, $_adminCript; //	incase we get demand loaded in a function
if (getOption('security_log_encryption')) {
	$_logCript = $_adminCript;
}
npgFilters::register('admin_allow_access', 'security_logger::adminGate'); //
npgFilters::register('federated_login_attempt', 'security_logger::federatedLoginlogger'); //	this is a surgote to "admin_login_attemt'

if (security_logger::register('admin_login_attempt', 'security_logger::adminLoginlogger'))
	npgFilters::register('admin_login_attempt', 'security_logger::adminLoginlogger');
if (security_logger::register('guest_login_attempt', 'security_logger::guestLoginlogger'))
	npgFilters::register('guest_login_attempt', 'security_logger::guestLoginlogger');
if (security_logger::register('authorization_cookie', 'security_logger::adminCookie', 0))
	npgFilters:: register('authorization_cookie', 'security_logger::adminCookie', 0);
if (security_logger::register('admin_managed_albums_access', 'security_logger::adminAlbumGate'))
	npgFilters:: register('admin_managed_albums_access', 'security_logger::adminAlbumGate');
if (security_logger::register('save_user_complete', 'security_logger::userSave'))
	npgFilters:: register('save_user_complete', 'security_logger::userSave');
if (security_logger::register('admin_XSRF_access', 'security_logger::admin_XSRF_access'))
	npgFilters:: register('admin_XSRF_access', 'security_logger::admin_XSRF_access');
if (security_logger::register('admin_log_actions', 'security_logger::log_action'))
	npgFilters:: register('admin_log_actions', 'security_logger::log_action');
if (security_logger::register('log_setup', 'security_logger::log_setup'))
	npgFilters:: register('log_setup', 'security_logger::log_setup');
if (security_logger::register('security_misc', 'security_logger::security_misc'))
	npgFilters:: register('security_misc', 'security_logger::security_misc');
if (security_logger::register('policy_ack', 'security_logger::policy_ack'))
	npgFilters:: register('policy_ack', 'security_logger::policy_ack');

/**
 * Option handler class
 *
 */
class security_logger {

	/**
	 * class instantiation function
	 *
	 * @return security_logger
	 */
	function __construct() {
		global $plugin_is_filter, $_securityLoggerList, $_securityLoggerLogging;
		if (OFFSET_PATH == 2) {
			foreach ($_securityLoggerList as $what => $where) {
				if (!is_null($_securityLoggerLogging[$where])) {
					setOptionDefault($where, $_securityLoggerLogging[$where]);
				}
			}
			setOptionDefault('logger_log_type', 'all');
			setOptionDefault('logger_access_log_type', 'all_user');
			setOptionDefault('security_log_size', 5000000);
			setOptionDefault('security_log_encryption', 0);
			setOptionDefault('admin_login_attempt', getOption('logger_log_admin'));
			purgeOption('logger_log_admin');
			setOptionDefault('guest_login_attempt', getOption('logger_log_guests'));
			purgeOption('logger_log_guests');
		}
	}

	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		global $_securityLoggerList, $_securityLoggerLogging;

		return array(
				gettext('Logging filters') => array('key' => '', 'type' => OPTION_TYPE_CHECKBOX_UL,
						'checkboxes' => $_securityLoggerList,
						'desc' => sprintf(gettext('The logging actions for the selected filters will be processed. Further details on these filters can be found in the <a href="%1$s">filter documentation</a>.'), getAdminLink(PLUGIN_FOLDER . '/debug/admin_tab.php') . '?page=development&tab=filters#Admin_Security')
				),
				gettext('Record failed admin access') => array('key' => 'logger_access_log_type', 'type' => OPTION_TYPE_RADIO,
						'buttons' => array(gettext('All attempts') => 'all', gettext('Only user attempts') => 'all_user'),
						'desc' => gettext('Record admin page access failures.')),
				gettext('Record logon') => array('key' => 'logger_log_type', 'type' => OPTION_TYPE_RADIO,
						'buttons' => array(gettext('All attempts') => 'all', gettext('Successful attempts') => 'success', gettext('unsuccessful attempts') => 'fail'),
						'desc' => gettext('Record login failures, successes, or all attempts.'))
		);
	}

	function handleOption($option, $currentValue) {

	}

	static function register($what, $where) {
		global $_securityLoggerLogging, $_securityLoggerList;
		$_securityLoggerList[$what] = $where;
		return $_securityLoggerLogging[$where] = getOption($where) || is_null(getOption($where));
	}

	/**
	 * Does the log handling
	 *
	 * @param int $success
	 * @param string $user
	 * @param string $name
	 * @param string $type
	 * @param string $authority kind of login
	 * @param string $addl more info
	 */
	private static function logger($success, $user, $name, $action, $authority, $addl = NULL) {
		global $_authority, $_mutex, $_logCript;
		$ip = sanitize($_SERVER['REMOTE_ADDR']);
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$proxy_list = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
			$forwardedIP = trim(sanitize(end($proxy_list)));
			if ($forwardedIP) {
				$ip .= ' {' . $forwardedIP . '}';
			}
		}
		$admin = $_authority->getMasterUser();
		$locale = $admin->getLanguage();
		if (empty($locale)) {
			$locale = 'en_US';
		}
		$cur_locale = i18n::getUserLocale();
		i18n::setupCurrentLocale($locale); //	the log will be in the language of the master user.
		switch ($action) {
			case 'clear_log':
				$type = gettext('Log reset');
				break;
			case 'delete_log':
				$type = gettext('Log deleted');
				break;
			case 'download_log':
				$type = gettext('Log downloaded');
				break;
			case 'setup_install':
				$aux1 = $addl;
				$success = 3;
				$type = gettext('Install');
				$addl = gettext('version') . ' ' . NETPHOTOGRAPHICS_VERSION;
				if (!npgFunctions::hasPrimaryScripts()) {
					$addl .= ' ' . gettext('clone');
				}
				break;
			case 'setup_restore':
				$type = gettext('Restore setup scripts');
				break;
			case 'setup_protect':
				$type = gettext('Protect setup scripts');
				break;
			case 'user_new':
				$type = gettext('Request add user');
				break;
			case 'user_update':
				$type = gettext('Request update user');
				break;
			case 'user_delete':
				$type = gettext('Request delete user');
				break;
			case 'XSRF_blocked':
				$type = gettext('Cross Site Reference');
				break;
			case 'blocked_album':
				$type = gettext('Album access');
				break;
			case 'blocked_access':
				$type = gettext('Admin access');
				break;
			case 'Front-end':
				$type = gettext('Guest login');
				break;
			case 'Back-end':
				$type = gettext('Admin login');
				break;
			case 'auth_cookie':
				$type = gettext('Authorization cookie check');
				break;
			default:
				$type = $action;
				break;
		}

		$file = SERVERPATH . '/' . DATA_FOLDER . '/security.log';
		$max = getOption('security_log_size');
		$_mutex->lock();
		if ($max && @filesize($file) > $max) {
			switchLog('security');
		}
		$preexists = file_exists($file) && filesize($file) > 0;
		$f = fopen($file, 'a');
		if ($f) {
			if (!$preexists) { // add a header
				@chmod($file, LOG_MOD);
				$message = gettext('date' . "\t" . 'requestor’s IP' . "\t" . 'type' . "\t" . 'user ID' . "\t" . 'user name' . "\t" . 'outcome' . "\t" . 'authority' . "\tadditional information");
				fwrite($f, $message . NEWLINE);
			}
			$message = date('Y-m-d H:i:s') . "\t";
			$message .= $ip . "\t";
			$message .= $type . "\t";
			$message .= $user . "\t";
			$message .= $name . "\t";
			switch ($success) {
				case 0:
					$message .= '<span class="error">' . gettext("Failed") . "</span>\t";
					break;
				case 1:
					$message .= gettext("Success") . "\t";
					break;
				case 2:
					$message .= '<span class="logwarning">' . gettext("Blocked") . "</span>\t";
					break;
				case 3:
					$message .= $aux1 . "\t";
					break;
			}
			$message .= str_replace('_auth', '', $authority);
			if ($addl) {
				$message .= "\t" . $addl;
			}
			if ($_logCript) {
				$message = $_logCript->encrypt($message);
			}
			fwrite($f, $message . NEWLINE);
			fclose($f);
			clearstatcache();
		}
		$_mutex->unlock();
		i18n::setupCurrentLocale($cur_locale); //	restore to whatever was in effect.
	}

	/**
	 * returns the user id and name of the logged in user
	 */
	private static function populate_user() {
		global $_current_admin_obj;
		if (is_object($_current_admin_obj)) {
			$user = $_current_admin_obj->getUser();
			$name = $_current_admin_obj->getName();
		} else {
			$user = $name = NULL;
		}
		return array($user, $name);
	}

	/**
	 * Logs an attempt to log onto the back-end or as an admin user
	 * Returns the rights to grant
	 *
	 * @param int $success the admin rights granted
	 * @param string $user
	 * @param string $pass
	 * @return int
	 */
	static function adminLoginlogger($success, $user, $pass, $auth = 'admin_auth') {
		global $_authority;
		switch (getOption('logger_log_type')) {
			case 'all':
				break;
			case 'success':
				if (!$success)
					return false;
				break;
			case 'fail':
				if ($success)
					return true;
				break;
		}
		$name = '';
		if ($success) {
			$admin = $_authority->getAnAdmin(array('`user`=' => $user, '`valid`=' => 1));
			$pass = ''; // mask it from display
			if (is_object($admin)) {
				$name = $admin->getName();
			}
		}
		security_logger::logger((int) ($success && true), $user, $name, 'Back-end', $auth, $pass);
		return $success;
	}

	/**
	 * Logs an attempt to log on via the federated_logon plugin
	 * Returns the rights to grant
	 *
	 * @param int $success the admin rights granted
	 * @param string $user
	 * @param string $pass
	 * @return int
	 */
	static function federatedLoginlogger($success, $user, $auth) {
		return security_logger::adminLoginlogger($success, $user, 'n/a', $auth);
	}

	/**
	 * Logs an attempt for a guest user to log onto the site
	 * Returns the "success" parameter.
	 *
	 * @param bool $success
	 * @param string $user
	 * @param string $pass
	 * @param string $athority what kind of login
	 * @return bool
	 */
	static function guestLoginlogger($success, $user, $pass, $athority) {
		global $_authority;
		switch (getOption('logger_log_type')) {
			case 'all':
				break;
			case 'success':
				if (!$success)
					return false;
				break;
			case 'fail':
				if ($success)
					return true;
				break;
		}
		$name = '';
		if ($success) {
			$admin = $_authority->getAnAdmin(array('`user`=' => $user, '`valid`=' => 1));
			$pass = ''; // mask it from display
			if (is_object($admin)) {
				$name = $admin->getName();
			}
		}
		security_logger::logger((int) ($success && true), $user, $name, 'Front-end', $athority, $pass);
		return $success;
	}

	/**
	 * Logs blocked accesses to Admin pages
	 * @param bool $allow set to true to override the block
	 * @param string $page the "return" link
	 */
	static function adminGate($allow, $page) {
		list($user, $name) = security_logger::populate_user();
		if (!$allow) {
			switch (getOption('logger_access_log_type')) {
				case 'all':
					break;
				case 'all_user':
					if (!$user)
						return $allow;
					break;
			}
			security_logger::logger(0, $user, $name, 'blocked_access', '', getRequestURI());
		}
		return $allow;
	}

	static function adminCookie($allow, $auth, $id) {
		if (!$allow && $auth && $id) { //	then it was a cononical auth cookie that is no longer valid or was forged
			switch (getOption('logger_log_type')) {
				case 'all':
				case 'fail':
					security_logger::logger(0, NULL, NULL, 'auth_cookie', '', (int) $id . ':' . $auth);
			}
		}
		return $allow;
	}

	/**
	 * Logs blocked accesses to Managed albums
	 * @param bool $allow set to true to override the block
	 * @param string $page the "return" link
	 */
	static function adminAlbumGate($allow, $page) {
		list($user, $name) = security_logger::populate_user();
		switch (getOption('logger_log_type')) {
			case 'all':
				break;
			case 'all_user':
				if (!$user)
					return $allow;
				break;
		}
		if (!$allow)
			security_logger::logger(2, $user, $name, 'blocked_album', '', getRequestURI());
		return $allow;
	}

	/**
	 * logs attempts to save on the user tab
	 * @param string $discard
	 * @param object $userobj user object upon which the save was targeted
	 * @param string $class what the action was.
	 */
	static function userSave($discard, $userobj, $class) {
		list($user, $name) = security_logger::populate_user();
		security_logger::logger(1, $user, $name, 'user_' . $class, 'admin_auth', $userobj->getUser());
		return $discard;
	}

	/**
	 * Logs Cross Site Request Forgeries
	 *
	 * @param bool $discard
	 * @param string $token
	 * @return bool
	 */
	static function admin_XSRF_access($discard, $token) {
		list($user, $name) = security_logger::populate_user();
		$uri = getRequestURI();
		security_logger::logger(2, $user, $name, 'XSRF_blocked', $token, $uri);
		return false;
	}

	/**
	 * logs security log actions
	 * @param bool $allow
	 * @param string $log
	 * @param string $action
	 */
	static function log_action($allow, $log, $action) {
		list($user, $name) = security_logger::populate_user();
		security_logger::logger((int) ($allow && true), $user, $name, $action, 'admin_auth', basename($log));
		return $allow;
	}

	/**
	 * Logs setup actions
	 * @param bool $success
	 * @param string $action
	 * @param string $file
	 */
	static function log_setup($success, $action, $txt) {
		list($user, $name) = security_logger::populate_user();
		security_logger::logger((int) ($success && true), $user, $name, 'setup_' . $action, 'admin_auth', $txt);
		return $success;
	}

	/**
	 * Catch all logger for miscellaneous security records
	 * @param bool $success
	 * @param string $requestor
	 * @param string $auth
	 * @param string $txt
	 */
	static function security_misc($success, $requestor, $auth, $txt) {
		list($user, $name) = security_logger::populate_user();
		security_logger::logger((int) $success, $name, NULL, $requestor, $auth, $txt);
		return $success;
	}

	/**
	 * Logs changes to usage policy acknowledgment
	 * @param type $success
	 * @param type $set
	 * @param type $what
	 * @return type
	 */
	static function policy_ack($success, $requestor, $set, $what) {
		list($user, $name) = security_logger::populate_user();
		if (!is_null($set)) {
			if ($set) {
				$what = sprintf(gettext('%1$s set to acknowledged'), $what);
			} else {
				$what = sprintf(gettext('%1$s acknowledgement cleared'), $what);
			}
		}
		security_logger::logger((int) $success, $user, $name, $requestor, 'admin_auth', $what);
		return $success;
	}

}

?>