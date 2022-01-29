<?php

/*
 * LDAP authorization module
 * Use to link site to an LDAP server for user verification.
 * It assumes that your LDAP server contains posix-style users and groups.
 *
 * @author Stephen Billard (sbillard), Arie (ariep)
 *
 * @package alt/LDAP_auth
 */

define('LDAP_DOMAIN', getOption('ldap_domain'));
define('LDAP_BASEDN', getOption('ldap_basedn'));
$_ous = array_map('trim', explode(',', getOption('ldap_ou')));
define('LDAP_OU', 'ou=' . implode(',ou=', $_ous));
define('LDAP_ID_OFFSET', getOption('ldap_id_offset')); //	number added to LDAP ID to insure it does not overlap any of our admin ids
define('LDAP_READER_OU', getOption('ldap_reader_ou'));
define('LDAP_READER_USER', getOption('ldap_reader_user'));
define('LDAP_READER_PASS', getOption('ldap_reader_pass'));
$_ous = array_map('trim', explode(',', getOption('ldap_group_ou')));
define('LDAP_GROUP_OU', 'ou=' . implode(',ou=', $_ous));
define('LDAP_MEMBERSHIP_ATTRIBUTE', getOption('ldap_membership_attribute'));
$_LDAPGroupMap = getSerializedArray(getOption('ldap_group_map'));

unset($_ous);

require_once(CORE_SERVERPATH . 'lib-auth.php');
if (extensionEnabled('user_groups')) {
	require_once(PLUGIN_SERVERPATH . 'user_groups.php');
}

class npg_Authority extends _Authority {

	function handleLogon() {
		global $_current_admin_obj;
		$loggedin = $user = $password = false;
		if (isset($_POST['user'])) {
			$user = sanitize($_POST['user'], 0);
		}
		if (isset($_POST['pass'])) {
			$password = sanitize($_POST['pass'], 0);
		}
		$loggedin = false;

		$ad = self::ldapInit(LDAP_DOMAIN);
		if ($ad) {
			$userdn = 'uid=' . $user . ',ou=' . LDAP_OU . ',' . LDAP_BASEDN;
			// We suppress errors in the binding process, to prevent a warning
			// in the case of authorisation failure.
			if (ldap_bind($ad, $userdn, $password)) { //	valid LDAP user
				self::ldapReader($ad);
				$userData = array_change_key_case(self::ldapUser($ad, "(uid={$user})"), CASE_LOWER);
				$userobj = self::setupUser($ad, $userData);
				if ($userobj) {
					$_current_admin_obj = $userobj;
					$loggedin = $_current_admin_obj->getRights();
					self::logUser($_current_admin_obj);
					if (DEBUG_LOGIN) {
						debugLog(sprintf('LDAPhandleLogon: authorized as %1$s->%2$X', $userdn, $loggedin));
					}
				} else {
					if (DEBUG_LOGIN) {
						debugLog("LDAPhandleLogon: no rights");
					}
				}
			} else {
				if (DEBUG_LOGIN) {
					debugLog("LDAPhandleLogon: Could not bind to LDAP");
				}
			}
			ldap_unbind($ad);
		}
		if ($loggedin) {
			return $loggedin;
		} else {
			// If the LDAP authorisation failed we try the standard logon, e.g. for a master administrator.
			return parent::handleLogon();
		}
	}

	function checkAuthorization($authCode, $id) {
		global $_current_admin_obj;
		if (LDAP_ID_OFFSET && $id > LDAP_ID_OFFSET) { //	LDAP ID
			$ldid = $id - LDAP_ID_OFFSET;
			$ad = self::ldapInit(LDAP_DOMAIN);
			if ($ad) {
				self::ldapReader($ad);
				$userData = self::ldapUser($ad, "(uidNumber={$ldid})");
				if ($userData) {
					$userData = array_change_key_case($userData, CASE_LOWER);
					if (DEBUG_LOGIN) {
						debugLogBacktrace("LDAPcheckAuthorization($authCode, $ldid)");
					}
					$goodAuth = npg_Authority::passwordHash($userData['uid'][0], serialize($userData));
					if ($authCode == $goodAuth) {
						$userobj = self::setupUser($ad, $userData);
						if ($userobj) {
							$_current_admin_obj = $userobj;
							$rights = $_current_admin_obj->getRights();
						} else {
							$rights = 0;
						}
						if (DEBUG_LOGIN) {
							debugLog(sprintf('LDAPcheckAuthorization: from %1$s->%2$X', $authCode, $rights));
						}
					} else {
						if (DEBUG_LOGIN) {
							debugLog(sprintf('LDAPcheckAuthorization: AuthCode %1$s <> %2$s', $goodAuth, $authCode));
						}
					}
				}
				ldap_unbind($ad);
			}
		}
		if ($_current_admin_obj) {
			return $_current_admin_obj->getRights();
		} else {
			return parent::checkAuthorization($authCode, $id);
		}
	}

	function validID($id) {
		return $id > LDAP_ID_OFFSET || parent::validID($id);
	}

	static function setupUser($ad, $userData) {
		global $_authority;
		$user = $userData['uid'][0];
		$id = $userData['uidnumber'][0] + LDAP_ID_OFFSET;
		$name = $userData['cn'][0];
		switch (LDAP_MEMBERSHIP_ATTRIBUTE) {
			case 'member':
				$target = $name;
				break;
			default:
			case 'memberuid':
				$target = $user;
				break;
		}

		$groups = self::getNPGGroups($ad, $target);

		$adminObj = npg_Authority::newAdministrator('');
		$adminObj->setID($id);
		$adminObj->transient = true;

		if (isset($userData['email'][0])) {
			$adminObj->setEmail($userData['email'][0]);
		}
		$adminObj->setUser($user);
		$adminObj->setName($name);
		$adminObj->setPass(serialize($userData));

		if (class_exists('user_groups')) {
			user_groups::merge_rights($adminObj, $groups, array());
			if (DEBUG_LOGIN) {
				debugLogVar(["LDAsetupUser: groups:" => $adminObj->getGroup()]);
			}
			$rights = $adminObj->getRights() & ~ USER_RIGHTS;
			$adminObj->setRights($rights);
		} else {
			$rights = DEFAULT_RIGHTS & ~ USER_RIGHTS;
			$adminObj->setRights(DEFAULT_RIGHTS & ~ USER_RIGHTS);
		}

		if ($rights) {
			if (empty($this->admin_users)) {
				$this->getAdministrators();
			}
			$this->admin_users[$adminObj->getID()] = $adminObj->getData();
			return $adminObj;
		}
		return NULL;
	}

	/*
	 * This function searches in LDAP tree ($ad -LDAP link identifier),
	 * starting under the branch specified by $basedn, for a single entry
	 * specified by $filter, and returns the requested attributes or null
	 * on failure.
	 */

	static function ldapSingle($ad, $filter, $basedn, $attributes) {
		$search = NULL;
		$lfdp = ldap_search($ad, $basedn, $filter, $attributes);
		if ($lfdp) {
			$entries = ldap_get_entries($ad, $lfdp);
			if ($entries['count'] != 0) {
				$search = $entries[0];
			}
		}
		ldap_free_result($lfdp);
		return $search;
	}

	static function ldapUser($ad, $filter) {
		return self::ldapSingle($ad, $filter, 'ou=Users,' . LDAP_BASEDN, array('uid', 'uidNumber', 'cn', 'email'));
	}

	/**
	 * returns an array the user's of groups
	 * @param type $ad
	 */
	static function getNPGGroups($ad, $target) {
		global $_LDAPGroupMap;
		$groups = array();
		foreach ($_LDAPGroupMap as $NPGgroup => $LDAPgroup) {
			if (!empty($LDAPgroup)) {
				$group = self::ldapSingle($ad, '(cn=' . $LDAPgroup . ')', LDAP_GROUP_OU . ', ' . LDAP_BASEDN, array(LDAP_MEMBERSHIP_ATTRIBUTE));
				if ($group) {
					$group = array_change_key_case($group, CASE_LOWER);
					if (in_array($target, $group[LDAP_MEMBERSHIP_ATTRIBUTE])) {
						$groups[] = $NPGgroup;
					}
				}
			}
		}
		return $groups;
	}

	static function ldapInit($domain) {
		if ($domain) {
			if ($ad = ldap_connect("ldap://{$domain}")) {
				ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, 3);
				ldap_set_option($ad, LDAP_OPT_REFERRALS, 0);
				return $ad;
			} else {
				trigger_error(gettext('Could not connect to LDAP server.'), E_USER_ERROR);
			}
		}
		return false;
	}

	/**
	 * login the ldapReader user if defined
	 */
	static function ldapReader($ad) {
		if (LDAP_READER_USER) {
			$userdn = 'uid=' . LDAP_READER_USER . ',ou=' . LDAP_READER_OU . ',' . LDAP_BASEDN;
			if (!ldap_bind($ad, $userdn, LDAP_READER_PASS)) {
				debugLog('LDAP reader authorization failed.');
			}
		}
	}

}

class npg_Administrator extends _Administrator {

	function setID($id) {
		$this->set('id', $id);
	}

	function setPass($pwd) {
		$hash = parent::setPass($pwd);
		$this->set('passupdate', NULL);
		return $hash;
	}

}

?>
