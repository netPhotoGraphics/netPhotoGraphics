<?php
/**
 * USER credentials library
 *
 * @package classes
 */
// force UTF-8 Ø
require_once(__DIR__ . '/classes.php');

class _Authority {

	protected $admin_users = array();
	protected $admin_groups = array();
	protected $admin_other = array();
	protected $rightsset = NULL;
	protected $master_user = NULL;
	protected $master_userObj = NULL;
	static $preferred_version = 4;
	static $supports_version = 4;
	//NOTE: if you add to $hashList you must add the alfrithm handling to the passwordHash() function
	static $hashList = array('md5' => 0, 'sha1' => 1, 'pbkdf2*' => 2, 'pbkdf2' => 3, 'Bcrypt' => 4, 'Argon2i' => 5, 'Argon2id' => 6);

	/**
	 * class instantiation function
	 *
	 * @return lib_auth_options
	 */
	function __construct() {

		if (OFFSET_PATH == 2) {
			setOptionDefault('strong_hash', 1000);
			setOptionDefault('password_strength', 10);
			setOptionDefault('min_password_lenght', 6);
			setOptionDefault('user_album_edit_default', 1);
			setOptionDefault('challenge_foil_enabled', 1);
			setOptionDefault('libauth_version', self::$preferred_version);
		}

		if (function_exists('password_hash')) {
			if (is_int(PASSWORD_DEFAULT)) {
				define('PASSWORD_FUNCTION_DEFAULT', PASSWORD_DEFAULT + 3);
			} else {
				if (PASSWORD_DEFAULT == '2y') {
					define('PASSWORD_FUNCTION_DEFAULT', self::$hashList['Bcrypt']);
				} else {
					if (isset(self::$hashList[ucfirst(PASSWORD_DEFAULT)])) {
						define('PASSWORD_FUNCTION_DEFAULT', self::$hashList[ucfirst(PASSWORD_DEFAULT)]);
					} else {
//	we need to add a new hash algorithm to the list!
						define('PASSWORD_FUNCTION_DEFAULT', end(self::$hashList));
					}
				}
			}
		} else {
			define('PASSWORD_FUNCTION_DEFAULT', 3);
		}
		$strongHash = (int) getOption('strong_hash');
		$list = self::getHashList();
		$list['default'] = 1000;
		if (!in_array($strongHash, $list)) {
			$strongHash = 1000;
			setOption('strong_hash', 1000);
		}
		Define('STRONG_PASSWORD_HASH', $strongHash);

		$sql = 'SELECT * FROM ' . prefix('administrators') . 'WHERE `valid`=1 ORDER BY `rights` DESC, `id`';
		if ($user = query_single_row($sql, false)) {
			$this->master_user = $user['user'];
		}
	}

	function getMasterUser() {
		global $_current_admin_obj;
		if (!is_object($this->master_userObj)) {
			if (is_object($_current_admin_obj) && $_current_admin_obj->master) {
				$this->master_userObj = $_current_admin_obj;
			} else {
				$this->master_userObj = new npg_Administrator($this->master_user, 1, FALSE);
			}
		}
		return $this->master_userObj;
	}

	function isMasterUser($user) {
		return $user == $this->master_user;
	}

	function validID($id) {
		$sql = 'SELECT `user` FROM ' . prefix('administrators') . ' WHERE `id`=' . $id;
		$result = query($sql);
		return $result && $result->num_rows > 0;
	}

	/**
	 * returns a list of allowable hashing algorithms
	 *
	 * @param bool $full_list set to true to include all algorithms
	 * @return array
	 */
	protected static function getHashList($fullList = false) {
		$full = $encodings = array_reverse(self::$hashList);

//	deprecate encodings
		unset($encodings['pbkdf2*']);
		if (!defined('PASSWORD_ARGON2ID')) {
			unset($encodings['Argon2id']);
		}
		if (!defined('PASSWORD_ARGON2I')) {
			unset($encodings['Argon2i']);
		}
		if (defined('PASSWORD_BCRYPT')) {
			unset($encodings['pbkdf2']);
			unset($encodings['sha1']);
			unset($encodings['md5']);
		} else {
			unset($encodings['Bcrypt']);
		}
		if ($fullList) {
			$full = array_flip($full);
			foreach ($full as $key => $name) {
				if (!isset($encodings[$name])) {
					$full[$key] = '<del>' . $full[$key] . '</del>';
				}
			}
			return array_flip($full);
		} else {

			return $encodings;
		}
	}

	/**
	 * Declares options used by lib-auth
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		$display = $encodings = self::getHashList();
		$encodings = array_merge(array(gettext('Default') => 1000), $encodings);
		$display = array_flip($display);
		$display[PASSWORD_FUNCTION_DEFAULT] .= '&dagger;';
		$display = array_flip($display);
		$options = array(
				gettext('Secure logout') => array('key' => 'SecureLogout', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 0,
						'desc' => gettext('Check if you need the browser to clear site data ("cache", "cookies", "storage", "executionContexts") on logout for security reasons. <strong>Note:</strong> This may degrade logout response time.'
						)),
				gettext('Primary album edit') => array('key' => 'user_album_edit_default', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 1,
						'desc' => gettext('Check if you want <em>edit rights</em> automatically assigned when a user <em>primary album</em> is created.'
						)),
				gettext('Minimum password strength') => array('key' => 'password_strength', 'type' => OPTION_TYPE_CUSTOM,
						'order' => 2,
						'desc' => sprintf(gettext('Users must provide passwords a strength of at least %s. The repeat password field will be disabled until this floor is met.'), '<span id="password_strength_display">' . getOption('password_strength') . '</span>')),
				gettext('Password hash algorithm') => array('key' => 'strong_hash', 'type' => OPTION_TYPE_ORDERED_SELECTOR,
						'order' => 3,
						'selections' => $encodings,
						'desc' => sprintf(gettext('The hashing algorithm to be used. In order of robustness the choices are %s'), '<code>' . implode('</code> > <code>', array_keys($display)) . '</code>') . '<br />&dagger; ' . gettest('The current default algorithm.') . '<br />' . gettext('Note: The <code>default</code> choice  is designed to change over time as new and stronger algorithms are added to PHP.')),
				gettext('Enable Challenge phrase') => array('key' => 'challenge_foil_enabled', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 5,
						'desc' => gettext('Check to allow password reset by challenge phrase responses.'))
		);
		if (getOption('challenge_foil_enabled')) {
			$options[gettext('Challenge phrase foils')] = array('key' => 'challenge_foil', 'type' => OPTION_TYPE_CUSTOM,
					'order' => 6,
					'desc' => gettext('These <em>foil</em> challenge phrases will be presented randomly. This list should not be empty, otherwise hackers can discover valid user IDs and know that there is an answer to any presented challenge phrase.'));
		}

		if (!in_array($hash = getOption('strong_hash'), $encodings)) {
			$options[NULL] = array('key' => 'lib_auth_note', 'type' => OPTION_TYPE_NOTE,
					'order' => 4,
					'desc' => '<span class="warningbox">' . gettext('You should use a more robust hashing algorithm.') . '</span>'
			);
			$list = array_flip(self::getHashList(true));
			$options[gettext('Password hash algorithm')]['selections'][$list[$hash]] = $hash;
		}
		return $options;
	}

	static function flagOptionTab() {
		$encodings = self::getHashList();
		$encodings = array_merge(array(gettext('Default') => 1000), $encodings);
		return !in_array($hash = getOption('strong_hash'), $encodings);
	}

	/**
	 * Dummy for object inheritance purposes
	 */
	function handleOption($option, $currentValue) {
		global $_current_admin_obj;
		switch ($option) {
			case 'password_strength':
				?>
				<input type="hidden" size="3" id="password_strength" name="password_strength" value="<?php echo getOption('password_strength'); ?>" />
				<script type="text/javascript">

					function sliderColor(strength) {
						d = 512 / 30; //	color gradient steps
						r = (30 - strength) * d;
						g = strength * d;
						url = 'linear-gradient(rgb(' + Math.round(Math.min(r - d, 255)) + ',' + Math.round(Math.min(g - d, 255)) + ',0), rgb(' + Math.round(Math.min(r, 255)) + ',' + Math.round(Math.min(g, 255)) + ',0))';
						$('#slider-password_strength').css('background-image', url);
					}
					$(function () {
						var handle = $("#strength-handle");
						$("#slider-password_strength").slider({
				<?php $v = getOption('password_strength'); ?>
							startValue: <?php echo $v; ?>,
							value: <?php echo $v; ?>,
							min: 1,
							max: 30,
							create: function () {
								handle.text($(this).slider("value"));
							},
							slide: function (event, ui) {
								handle.text(ui.value);
								$("#password_strength").val(ui.value);
								$('#password_strength_display').html(ui.value);
								sliderColor(ui.value);
							}
						});
						var strength = $("#slider-password_strength").slider("value");
						$("#password_strength").val(strength);
						$('#password_strength_display').html(strength);
						sliderColor(strength);
					});
				</script>
				<div id="slider-password_strength">
					<div id="strength-handle" class="ui-slider-handle"></div>
				</div>
				<?php
				break;
			case 'challenge_foil':
				$questions = getSerializedArray(getOption('challenge_foils'));
				$questions[] = array('');
				foreach ($questions as $key => $question) {
					?>
					<?php print_language_string_list($question, 'challenge_foil_' . $key, false, NULL, '100%'); ?>	<br/>
					<?php
				}
				break;
		}
	}

	function handleOptionSave($themename, $themealbum) {
		$questions = array();
		foreach ($_POST as $key => $value) {
			if (!empty($value)) {
				preg_match('~challenge_foil_(\d+)_(.*)~', $key, $matches);
				if (!empty($matches)) {
					$questions[$matches[1]][$matches[2]] = sanitize($value);
				}
			}
		}
		setOption('challenge_foils', serialize($questions));
	}

	static function getVersion() {
		$v = getOption('libauth_version');
		if (empty($v)) {
			return self::$preferred_version;
		} else {
			return $v;
		}
	}

	/**
	 * Returns the hash of the password
	 *
	 * @param string $user
	 * @param string $pass
	 * @return string
	 */
	static function passwordHash($user, $pass, $hash_type = NULL, $debug = true) {
		if (is_null($hash_type)) {
			$hash_type = STRONG_PASSWORD_HASH;
		}
		if (!array_search($hash_type, self::$hashList)) { //	default
			$hash_type = PASSWORD_FUNCTION_DEFAULT;
		}
		$hash = NULL;
		switch ($hash_type) {
			case 0:
				$hash = md5($user . $pass . HASH_SEED);
				break;
			case 1:
				$hash = sha1($user . $pass . HASH_SEED);
				break;
			case 2:
//	deprecated because of possible "+" in the text
				$hash = base64_encode(self::pbkdf2($pass, $user . HASH_SEED));
				break;
			case 3:
				$hash = str_replace('+', '-', base64_encode(self::pbkdf2($pass, $user . HASH_SEED)));
				break;
			case 4:
				if (defined('PASSWORD_BCRYPT')) {
					$hash = password_hash($pass, PASSWORD_BCRYPT);
				}
				break;
			case 5:
				if (defined('PASSWORD_ARGON2I')) {
					$hash = password_hash($pass, PASSWORD_ARGON2I);
				}
				break;
			case 6:
				if (defined('PASSWORD_ARGON2ID')) {
					$hash = password_hash($pass, PASSWORD_ARGON2ID);
				}
				break;
			default:
				$hash = NULL; //	current PHP version does not support the algorithm.
		}
		if ($debug && (DEBUG_LOGIN || !$hash)) {
			$hashNames = array_flip(self::$hashList);
			$algorithm = $hashNames[$hash_type];
			if (!$hash) {
				$hash = gettext('No PHP support');
			}
			debugLog("passwordHash($user, $algorithm)[ " . HASH_SEED . " ]:$hash");
		}
		return $hash;
	}

	/**
	 * Counts the users by criteria
	 *
	 * @param string $what: 'all' for everything, 'users' for valid users,
	 * 											'admin_other' for non-valid users, 'allusers' for all both valid and not valid users,
	 * 											'user_groups' for groups and 'group_templates' for templates
	 * @return int count
	 *
	 * @Copyright 2022 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
	 */
	function count($what) {
		switch ($what) {
			case 'users':
				$valid = ' WHERE `valid`=1';
				break;
			case 'user_groups':
				if (extensionEnabled('user_groups')) {
					$valid = ' WHERE `valid`=0 AND `name`="group"';
				} else {
					return 0;
				}
				break;
			case 'group_templates':
				if (extensionEnabled('user_groups')) {
					$valid = ' WHERE `valid`=0 AND `name`="template"';
				} else {
					return 0;
				}
				break;
			case 'admin_other':
				$valid = ' WHERE `valid`>1';
				break;
			case 'allusers':
				$valid = ' WHERE `valid`>0';
				break;
			case 'all':
				$valid = '';
				break;
			default:
				throw new Exception(gettext('Unknown count request.'));
				return 0;
		}
		if ($row = query_single_row($sql = 'SELECT COUNT(*) FROM ' . prefix('administrators') . $valid, false)) {
			return reset($row);
		}
		return NULL;
	}

	/**
	 * Returns an array of admin users, indexed by the userid and ordered by "privileges"
	 *
	 * @param string $what: 'all' for everything, 'users' for valid users,
	 * 											'admin_other' for non-valid users, 'allusers' for all both valid and not valid users,
	 * 											'groups' for groups and templates
	 * @return array
	 *
	 * @Copyright 2022 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
	 */
	function getAdministrators($what = 'users') {
		$list = array();
		switch ($what) {
			case 'users':
				$list = $this->admin_users;
				$valid = ' WHERE `valid`=1';
				break;
			case 'groups':
				if (extensionEnabled('user_groups')) {
					$list = $this->admin_groups;
					$valid = ' WHERE `valid`=0';
				} else {
					return array();
				}
				break;
			case 'admin_other':
				$list = $this->admin_other;
				$valid = ' WHERE `valid`>1';
				break;
			case 'all':
				$list = self::getAdministrators('groups');
			case 'allusers':
				$list = $list + self::getAdministrators('users') + self::getAdministrators('admin_other');
				return $list;
			default:
				throw new Exception(gettext('Unknown getAdministrators request.'));
				return array();
		}

		if (empty($list)) {
			$sql = 'SELECT ' .
							// per requirements from class-auth return the following fields
							'`id`, `valid`,	`user`,	`pass`,	`name`, `email`, `rights`, `group`, `other_credentials`, `lastloggedin`, `lastaccess`, `date`' .
							' FROM ' . prefix('administrators') . $valid . ' ORDER BY `rights` DESC, `id`';
			$admins = query($sql, false);
			if ($admins) {
				while ($user = db_fetch_assoc($admins)) {
					$list[$user['id']] = $user;
				}
				db_free_result($admins);
			}

			switch ($what) {
				case 'users':
					$this->admin_users = $list;
					break;
				case 'groups':
					$this->admin_groups = $list;
					break;
				case 'admin_other':
					$this->admin_other = $list;
					break;
			}
		}

		return $list;
	}

	/**
	 * Returns an admin object from the $pat:$criteria
	 * @param array $criteria [ match => criteria ]
	 * @return npg_Administrator
	 */
	function getAnAdmin($criteria) {
		$find = array();
		foreach ($criteria as $match => $value) {
			preg_match('/(.*)([<>=!])/', $match, $detail);
			$find[trim($detail[1], '`')] = array('field' => trim($detail[1]), 'op' => trim($detail[2]), 'value' => $value);
		}

		$selector = array();
		foreach ($find as $field => $select) {
			if (!is_numeric($value = $select['value'])) {
				$value = db_quote($value);
			}
			$selector[] = $select['field'] . $select['op'] . $value;
		}
		$sql = 'SELECT `user`, `valid` FROM ' . prefix('administrators') . ' WHERE ' . implode(' AND ', $selector);
		$admin = query_single_row($sql, false);

		if ($admin) {
			return self::newAdministrator($admin['user'], $admin['valid']);
		} else {
			return false;
		}
	}

	/**
	 * Returns the index of the hash algorithm used.
	 *
	 * @param array $userdata
	 * @return list ($index,name)
	 */
	static function getHashAlgorithm($userdata) {
		if (empty($userdata['pass'])) {
			$info['algo'] = false;
		} else {
			$info = password_get_info($userdata['pass']);
		}
		if ($info['algo']) {
			$name = ucfirst($info['algoName']);
			$index = self::$hashList[$name];
		} else {
			if (isset($userdata['passhash'])) {
				$index = $userdata['passhash'];
				if (!array_search($index, self::$hashList)) { //	default
					$index = PASSWORD_FUNCTION_DEFAULT;
				}
				$name = array_search($index, self::$hashList);
				require_once(PLUGIN_SERVERPATH . 'deprecated-functions.php');
				if (!empty($userdata['pass'])) {
					deprecated_functions::deprecationMessage(sprintf(gettext('The password for user %1$s is using the deprecated %2$s hashing method.'), $userdata['user'], $name));
				}
			} else {
				$index = false;
				$name = '';
			}
		}
		return array($index, $name);
	}

	/**
	 * Returns the administration rights of a saved authorization code
	 * Will promote an admin to ADMIN_RIGHTS if he is the most privileged admin
	 *
	 * @param string $authCode the hash code to check
	 * @param int $id whom we think this is
	 *
	 * @return bit
	 */
	function checkAuthorization($authCode, $id) {
		global $_current_admin_obj;
		if (DEBUG_LOGIN) {
			debugLogBacktrace("checkAuthorization($authCode, $id)");
		}

		if (is_object($_current_admin_obj) && $_current_admin_obj->reset) {
			if (DEBUG_LOGIN) {
				debugLog("checkAuthorization: reset request");
			}
			return $_current_admin_obj->getRights();
		}

		$_current_admin_obj = NULL;
		if (empty($authCode) || empty($id))
			return 0; //  so we don't "match" with an empty password
		if (DEBUG_LOGIN) {
			debugLogVar(["checkAuthorization: admins" => $this->getAdministrators()]);
		}
		$rights = 0;
		$criteria = array('`pass`=' => $authCode, '`id`=' => (int) $id, '`valid`=' => 1);
		$user = $this->getAnAdmin($criteria);

		if (is_object($user)) {
//	force new logon to update password hash if his algorithm is deprecated
			list($strength, $name) = self::getHashAlgorithm($user->getData());
			if ($strength >= PASSWORD_FUNCTION_DEFAULT) {
				$_current_admin_obj = $user;
				$rights = $user->getRights();
				if (DEBUG_LOGIN) {
					debugLog(sprintf('checkAuthorization: from %1$s->%2$X', $authCode, $rights));
				}
				return $rights;
			}
		}

		$_current_admin_obj = NULL;
		if (DEBUG_LOGIN) {
			debugLog("checkAuthorization: no match");
		}
		return 0; // no rights
	}

	/**
	 * Checks a logon user/password against admins
	 *
	 * Returns the user object if there is a match
	 *
	 * @param string $user
	 * @param string $pass
	 * @return object
	 */
	function checkLogon($user, $pass) {
		$userobj = $this->getAnAdmin(array('`user`=' => $user, '`valid`=' => 1));
		if ($userobj) {
			list($type, $algo) = self::getHashAlgorithm($userobj->getData());
			switch ($type) {
				case 0:
				case 1:
				case 2:
				case 3:
					$hash = self::passwordHash($user, $pass, $type);
					if ($hash != $userobj->getPass()) {
						$userobj = NULL;
					}
					break;
				default:
					$hash = $userobj->getPass();
					if (!password_verify($pass, $hash)) {
						$userobj = NULL;
					}
					break;
			}

			if ($userobj && $type < PASSWORD_FUNCTION_DEFAULT) {
//	update his password hash to more modern one
				$userobj->setPass($pass);
				$userobj->save();
			}
		} else {
			$hash = 'FALSE';
		}

		if (DEBUG_LOGIN) {
			if ($userobj) {
				$rights = sprintf('%X', $userobj->getRights());
			} else {
				$rights = 'FALSE';
			}
			debugLog(sprintf('checkLogon(%1$s, %2$s)->%3$s', $user, $hash, $rights));
		}

		return $userobj;
	}

	/**
	 * Returns the email addresses of the Admin with ADMIN_USERS rights
	 *
	 * @param bit $rights what kind of admins to retrieve
	 * @return array
	 */
	function getAdminEmail($rights = ADMIN_RIGHTS) {
		$emails = array();
		$admins = $this->getAdministrators();
		foreach ($admins as $user) {
			if (($user['rights'] & $rights) && npgFunctions::isValidEmail($user['email'])) {
				$name = $user['name'];
				if (empty($name)) {
					$name = $user['user'];
				}
				$emails[$name] = $user['email'];
			}
		}
		return $emails;
	}

	/**
	 * Migrates credentials
	 *
	 * @param int $oldversion
	 */
	function migrateAuth($to) {
		if ($to > self::$supports_version || $to < self::$preferred_version - 1) {
			trigger_error(sprintf(gettext('Cannot migrate rights to version %1$s (_Authority supports only %2$s and %3$s.)'), $to, self::$supports_version, self::$preferred_version), E_USER_NOTICE);
			return false;
		}
		$success = true;
		$oldversion = self::getVersion();
		setOption('libauth_version', $to);

		$sql = "SELECT `id`, `rights` FROM " . prefix('administrators') . "ORDER BY `rights` DESC, `id`";
		$admins = query($sql, false);
		if ($admins && $admins->num_rows > 0) { // something to migrate
			$oldrights = array();
			foreach (self::getRights($oldversion) as $key => $right) {
				$oldrights[$key] = $right['value'];
			}
			$currentrights = self::getRights($to);
			while ($user = db_fetch_assoc($admins)) {
				$update = false;
				$rights = $user['rights'];
				$newrights = $currentrights['NO_RIGHTS']['value'];
				foreach ($currentrights as $key => $right) {
					if ($right['display']) {
						if (array_key_exists($key, $oldrights) && $rights & $oldrights[$key]) {
							$newrights = $newrights | $right['value'];
						}
					}
				}
				if ($oldversion < 4) {
					$newrights = $newrights | $currentrights['USER_RIGHTS']['value'];
				}
				if ($to >= 3 && $oldversion < 3) {
					if ($rights & $oldrights['VIEW_ALL_RIGHTS']) {
						$updaterights = $currentrights['ALL_ALBUMS_RIGHTS']['value'] | $currentrights['ALL_PAGES_RIGHTS']['value'] |
										$currentrights['ALL_NEWS_RIGHTS']['value'] | $currentrights['VIEW_SEARCH_RIGHTS']['value'] |
										$currentrights['VIEW_GALLERY_RIGHTS']['value'] | $currentrights['VIEW_FULLIMAGE_RIGHTS']['value'];
						$newrights = $newrights | $updaterights;
					}
				}
				if ($oldversion >= 3 && $to < 3) {
					if ($oldrights['ALL_ALBUMS_RIGHTS'] || $oldrights['ALL_PAGES_RIGHTS'] || $oldrights['ALL_NEWS_RIGHTS']) {
						$newrights = $newrights | $currentrights['VIEW_ALL_RIGHTS']['value'];
					}
				}
				if ($oldversion == 1) { // need to migrate zenpage rights
					if ($rights & $oldrights['ZENPAGE_RIGHTS']) {
						$newrights = $newrights | $currentrights['ZENPAGE_PAGES_RIGHTS'] | $currentrights['ZENPAGE_NEWS_RIGHTS'] | $currentrights['FILES_RIGHTS'];
					}
				}
				if ($to >= 3) {
					if ($rights & $oldrights['ADMIN_RIGHTS']) {
						$newrights = $currentrights['ALL_RIGHTS']['value'];
					} else {
						if ($newrights & $currentrights['MANAGE_ALL_ALBUM_RIGHTS']['value']) {
// these are lock-step linked!
							$newrights = $newrights | $currentrights['ALBUM_RIGHTS']['value'];
						}
						if ($newrights & $currentrights['MANAGE_ALL_NEWS_RIGHTS']['value']) {
// these are lock-step linked!
							$newrights = $newrights | $currentrights['ZENPAGE_NEWS_RIGHTS']['value'];
						}
						if ($newrights & $currentrights['MANAGE_ALL_PAGES_RIGHTS']['value']) {
// these are lock-step linked!
							$newrights = $newrights | $currentrights['ZENPAGE_PAGES_RIGHTS']['value'];
						}
					}
				}

				$sql = 'UPDATE ' . prefix('administrators') . ' SET `rights`=' . $newrights . ' WHERE `id`=' . $user['id'];
				$success = $success && query($sql);
			} // end loop
			db_free_result($admins);
		}
		return $success;
	}

	/**
	 * Updates a field in admin record(s)
	 *
	 * @param string $update name of the field
	 * @param mixed $value what to store
	 * @param array $constraints on the update [ field<op>,value ]
	 * @return mixed Query result
	 */
	static function updateAdminField($update, $value, $constraints) {
		$where = '';
		foreach ($constraints as $field => $clause) {
			if (!empty($where))
				$where .= ' AND ';
			if (is_numeric($clause)) {
				$where .= $field . $clause;
			} else {
				$where .= $field . db_quote($clause);
			}
		}
		if (is_null($value)) {
			$value = 'NULL';
		} else {
			$value = db_quote($value);
		}
		$sql = 'UPDATE ' . prefix('administrators') . ' SET `' . $update . '`=' . $value . ' WHERE ' . $where;
		$result = query($sql);
		return $result;
	}

	/**
	 * Instantiates and returns administrator object
	 * @param $name
	 * @param $valid
	 * @return object
	 */
	static function newAdministrator($name, $valid = 1, $allowCreate = true) {
		$user = new npg_Administrator($name, $valid, $allowCreate);
		return $user;
	}

	/**
	 * Returns an array of the rights definitions for $version (default returns current version rights)
	 *
	 * @param $version
	 */
	static function getRights($version = NULL) {
		if (empty($version)) {
			$v = self::getVersion();
		} else {
			$v = $version;
		}
		switch ($v) {
			default:
				trigger_error(sprintf(gettext('_Authority::getRights() does not support version %1$s'), $v), E_USER_ERROR);
				return self::getRights();
			case 4:
				$rightsset = array('NO_RIGHTS' => array('value' => 1,
								'name' => gettext('No rights'),
								'set' => '',
								'display' => false,
								'hint' => ''),
						'OVERVIEW_RIGHTS' => array('value' => pow(2, 2),
								'name' => gettext('Overview'),
								'set' => gettext('General'),
								'sort' => 0,
								'display' => true,
								'hint' => gettext('Users with this right may view the admin overview page.')),
						'USER_RIGHTS' => array('value' => pow(2, 3),
								'name' => gettext('User'),
								'set' => gettext('General'),
								'sort' => 0,
								'display' => true,
								'hint' => gettext('Users must have this right to change their credentials.')),
						'DEBUG_RIGHTS' => array('value' => pow(2, 4),
								'name' => gettext('Debug'),
								'set' => gettext('General'),
								'sort' => 0,
								'display' => true,
								'hint' => gettext('Allows viewing of the debug tab items.')),
						'VIEW_GALLERY_RIGHTS' => array('value' => pow(2, 5),
								'name' => gettext('View gallery'),
								'set' => gettext('Gallery'),
								'sort' => 1,
								'display' => true,
								'hint' => gettext('Users with this right may view otherwise protected generic gallery pages.')),
						'VIEW_SEARCH_RIGHTS' => array('value' => pow(2, 6),
								'name' => gettext('View search'),
								'set' => gettext('Gallery'),
								'sort' => 1,
								'display' => true,
								'hint' => gettext('Users with this right may view search pages even if password protected.')),
						'VIEW_FULLIMAGE_RIGHTS' => array('value' => pow(2, 7),
								'name' => gettext('View fullimage'),
								'set' => gettext('Albums'),
								'sort' => 2,
								'display' => true,
								'hint' => gettext('Users with this right may view all full sized (raw) images.')),
						'ALL_NEWS_RIGHTS' => array('value' => pow(2, 8),
								'name' => gettext('Access all'),
								'set' => gettext('News'),
								'sort' => 3,
								'display' => true,
								'hint' => gettext('Users with this right have access to all zenpage news articles.')),
						'ALL_PAGES_RIGHTS' => array('value' => pow(2, 9),
								'name' => gettext('Access all'),
								'set' => gettext('Pages'),
								'sort' => 4,
								'display' => true,
								'hint' => gettext('Users with this right have access to all zenpage pages.')),
						'ALL_ALBUMS_RIGHTS' => array('value' => pow(2, 10),
								'name' => gettext('Access all'),
								'set' => gettext('Albums'),
								'sort' => 2,
								'display' => true,
								'hint' => gettext('Users with this right have access to all albums.')),
						'VIEW_UNPUBLISHED_RIGHTS' => array('value' => pow(2, 11),
								'name' => gettext('View unpublished'),
								'set' => gettext('Albums'),
								'sort' => 2,
								'display' => true,
								'hint' => gettext('Users with this right will see all unpublished items.')),
						'VIEW_UNPUBLISHED_NEWS_RIGHTS' => array('value' => pow(2, 12),
								'name' => gettext('View unpublished'),
								'set' => gettext('News'),
								'sort' => 3,
								'display' => true,
								'hint' => gettext('Users with this right will see all unpublished items.')),
						'POST_COMMENT_RIGHTS' => array('value' => pow(2, 13),
								'name' => gettext('Post comments'),
								'set' => gettext('Gallery'),
								'sort' => 1,
								'display' => true,
								'hint' => gettext('When the comment_form plugin is used for comments and its "Only members can comment" option is set, only users with this right may post comments.')),
						'COMMENT_RIGHTS' => array('value' => pow(2, 14),
								'name' => gettext('Comments'),
								'set' => gettext('Gallery'),
								'sort' => 1,
								'display' => true,
								'hint' => gettext('Users with this right may make comments tab changes.')),
						'UPLOAD_RIGHTS' => array('value' => pow(2, 15),
								'name' => gettext('Upload'),
								'set' => gettext('Albums'),
								'sort' => 2,
								'display' => true,
								'hint' => gettext('Users with this right may upload to the albums for which they have management rights.')),
						'VIEW_UNPUBLISHED_PAGE_RIGHTS' => array('value' => pow(2, 16),
								'name' => gettext('View unpublished'),
								'set' => gettext('Pages'),
								'sort' => 4,
								'display' => true,
								'hint' => gettext('Users with this right will see all unpublished items.')),
						'ZENPAGE_NEWS_RIGHTS' => array('value' => pow(2, 17),
								'name' => gettext('News'),
								'set' => gettext('News'),
								'sort' => 3,
								'display' => false,
								'hint' => gettext('Users with this right may edit and manage Zenpage articles and categories.')),
						'ZENPAGE_PAGES_RIGHTS' => array('value' => pow(2, 18),
								'name' => gettext('Pages'),
								'set' => gettext('Pages'),
								'sort' => 4,
								'display' => false,
								'hint' => gettext('Users with this right may edit and manage Zenpage pages.')),
						'FILES_RIGHTS' => array('value' => pow(2, 19),
								'name' => gettext('Files'),
								'set' => gettext('Gallery'),
								'sort' => 1,
								'display' => true,
								'hint' => gettext('Allows the user access to the “filemanager” located on the upload: files sub-tab.')),
						'ALBUM_RIGHTS' => array('value' => pow(2, 20),
								'name' => gettext('Albums'),
								'set' => gettext('Albums'),
								'sort' => 2,
								'display' => false,
								'hint' => gettext('Users with this right may access the “albums” tab to make changes.')),
						'MANAGE_ALL_NEWS_RIGHTS' => array('value' => pow(2, 21),
								'name' => gettext('Manage all'),
								'set' => gettext('News'),
								'sort' => 3,
								'display' => true,
								'hint' => gettext('Users who do not have “Admin” rights normally are restricted to manage only objects to which they have been assigned. This right allows them to manage any Zenpage news article or category.')),
						'MANAGE_ALL_PAGES_RIGHTS' => array('value' => pow(2, 22),
								'name' => gettext('Manage all'),
								'set' => gettext('Pages'),
								'sort' => 4,
								'display' => true,
								'hint' => gettext('Users who do not have “Admin” rights normally are restricted to manage only objects to which they have been assigned. This right allows them to manage any Zenpage page.')),
						'MANAGE_ALL_ALBUM_RIGHTS' => array('value' => pow(2, 23),
								'name' => gettext('Manage all'),
								'set' => gettext('Albums'),
								'sort' => 2,
								'display' => true,
								'hint' => gettext('Users who do not have “Admin” rights normally are restricted to manage only objects to which they have been assigned. This right allows them to manage any album in the gallery.')),
						//	pow(2, 24)
						'CODEBLOCK_RIGHTS' => array('value' => pow(2, 25),
								'name' => gettext('Codeblock'),
								'set' => gettext('General'),
								'sort' => 0,
								'display' => true,
								'hint' => gettext('Users with this right may edit Codeblocks.')),
						'THEMES_RIGHTS' => array('value' => pow(2, 26),
								'name' => gettext('Themes'),
								'set' => gettext('Gallery'),
								'sort' => 1,
								'display' => true,
								'hint' => gettext('Users with this right may make themes related changes. These are limited to the themes associated with albums checked in their managed albums list.')),
						//	pow(2, 27)
						'TAGS_RIGHTS' => array('value' => pow(2, 28),
								'name' => gettext('Tags'),
								'set' => gettext('Gallery'),
								'sort' => 1,
								'display' => true,
								'hint' => gettext('Users with this right may make additions and changes to the set of tags.')),
						'OPTIONS_RIGHTS' => array('value' => pow(2, 29),
								'name' => gettext('Options'),
								'set' => gettext('General'),
								'sort' => 0,
								'display' => true,
								'hint' => gettext('Users with this right may make changes on the options.')),
						'ADMIN_RIGHTS' => array('value' => pow(2, 30),
								'name' => gettext('Master rights'),
								'set' => gettext('General'),
								'sort' => 0,
								'display' => false,
								'hint' => gettext('The master privilege. A user with "Admin" can do anything. (No matter what his other rights might indicate!)')));
				break;
			case 3:
				$rightsset = array('NO_RIGHTS' => array('value' => 1,
								'name' => gettext('No rights'),
								'set' => '',
								'display' => false,
								'hint' => ''),
						'OVERVIEW_RIGHTS' => array('value' => pow(2, 2),
								'name' => gettext('Overview'),
								'set' => gettext('General'),
								'display' => true,
								'hint' => gettext('Users with this right may view the admin overview page.')),
						'VIEW_GALLERY_RIGHTS' => array('value' => pow(2, 4),
								'name' => gettext('View gallery'),
								'set' => gettext('Gallery'),
								'display' => true, 'hint' =>
								gettext('Users with this right may view otherwise protected generic gallery pages.')),
						'VIEW_SEARCH_RIGHTS' => array('value' => pow(2, 5),
								'name' => gettext('View search'),
								'set' => gettext('Gallery'),
								'display' => true,
								'hint' => gettext('Users with this right may view search pages even if password protected.')),
						'VIEW_FULLIMAGE_RIGHTS' => array('value' => pow(2, 6),
								'name' => gettext('View fullimage'),
								'set' => gettext('Albums'),
								'display' => true,
								'hint' => gettext('Users with this right may view all full sized (raw) images.')),
						'ALL_NEWS_RIGHTS' => array('value' => pow(2, 7),
								'name' => gettext('Access all'),
								'set' => gettext('News'),
								'display' => true,
								'hint' => gettext('Users with this right have access to all zenpage news articles.')),
						'ALL_PAGES_RIGHTS' => array('value' => pow(2, 8),
								'name' => gettext('Access all'),
								'set' => gettext('Pages'),
								'display' => true,
								'hint' => gettext('Users with this right have access to all zenpage pages.')),
						'ALL_ALBUMS_RIGHTS' => array('value' => pow(2, 9),
								'name' => gettext('Access all'),
								'set' => gettext('Albums'),
								'display' => true,
								'hint' => gettext('Users with this right have access to all albums.')),
						'VIEW_UNPUBLISHED_RIGHTS' => array('value' => pow(2, 10),
								'name' => gettext('View unpublished'),
								'set' => gettext('Albums'),
								'display' => true,
								'hint' => gettext('Users with this right will see all unpublished items.')),
						'POST_COMMENT_RIGHTS' => array('value' => pow(2, 11),
								'name' => gettext('Post comments'),
								'set' => gettext('Gallery'),
								'display' => true,
								'hint' => gettext('When the comment_form plugin is used for comments and its "Only members can comment" option is set, only users with this right may post comments.')),
						'COMMENT_RIGHTS' => array('value' => pow(2, 12),
								'name' => gettext('Comments'),
								'set' => gettext('Gallery'),
								'display' => true,
								'hint' => gettext('Users with this right may make comments tab changes.')),
						'UPLOAD_RIGHTS' => array('value' => pow(2, 13),
								'name' => gettext('Upload'),
								'set' => gettext('Albums'),
								'display' => true,
								'hint' => gettext('Users with this right may upload to the albums for which they have management rights.')),
						'ZENPAGE_NEWS_RIGHTS' => array('value' => pow(2, 15),
								'name' => gettext('News'),
								'set' => gettext('News'),
								'display' => false,
								'hint' => gettext('Users with this right may edit and manage Zenpage articles and categories.')),
						'ZENPAGE_PAGES_RIGHTS' => array('value' => pow(2, 16),
								'name' => gettext('Pages'),
								'set' => gettext('Pages'),
								'display' => false,
								'hint' => gettext('Users with this right may edit and manage Zenpage pages.')),
						'FILES_RIGHTS' => array('value' => pow(2, 17),
								'name' => gettext('Files'),
								'set' => gettext('Gallery'),
								'display' => true,
								'hint' => gettext('Allows the user access to the “filemanager” located on the upload: files sub-tab.')),
						'ALBUM_RIGHTS' => array('value' => pow(2, 18),
								'name' => gettext('Albums'),
								'set' => gettext('Albums'),
								'display' => false,
								'hint' => gettext('Users with this right may access the “albums” tab to make changes.')),
						'MANAGE_ALL_NEWS_RIGHTS' => array('value' => pow(2, 21),
								'name' => gettext('Manage all'),
								'set' => gettext('News'),
								'display' => true,
								'hint' => gettext('Users who do not have “Admin” rights normally are restricted to manage only objects to which they have been assigned. This right allows them to manage any Zenpage news article or category.')),
						'MANAGE_ALL_PAGES_RIGHTS' => array('value' => pow(2, 22),
								'name' => gettext('Manage all'),
								'set' => gettext('Pages'),
								'display' => true,
								'hint' => gettext('Users who do not have “Admin” rights normally are restricted to manage only objects to which they have been assigned. This right allows them to manage any Zenpage page.')),
						'MANAGE_ALL_ALBUM_RIGHTS' => array('value' => pow(2, 23),
								'name' => gettext('Manage all'),
								'set' => gettext('Albums'),
								'display' => true,
								'hint' => gettext('Users who do not have “Admin” rights normally are restricted to manage only objects to which they have been assigned. This right allows them to manage any album in the gallery.')),
						'THEMES_RIGHTS' => array('value' => pow(2, 26),
								'name' => gettext('Themes'),
								'set' => gettext('Gallery'),
								'display' => true,
								'hint' => gettext('Users with this right may make themes related changes. These are limited to the themes associated with albums checked in their managed albums list.')),
						'TAGS_RIGHTS' => array('value' => pow(2, 28),
								'name' => gettext('Tags'),
								'set' => gettext('Gallery'),
								'display' => true,
								'hint' => gettext('Users with this right may make additions and changes to the set of tags.')),
						'OPTIONS_RIGHTS' => array('value' => pow(2, 29),
								'name' => gettext('Options'),
								'set' => gettext('General'),
								'display' => true,
								'hint' => gettext('Users with this right may make changes on the options.')),
						'ADMIN_RIGHTS' => array('value' => pow(2, 30),
								'name' => gettext('All rights'),
								'set' => gettext('General'),
								'display' => false,
								'hint' => gettext('The master privilege. A user with "Admin" can do anything. (No matter what his other rights might indicate!)')));
				break;
		}
		$allrights = $hiddenrights = 0;
		foreach ($rightsset as $key => $right) {
			$allrights = $allrights | $right['value'];
			if (!$right['display']) {
				$hiddenrights = $hiddenrights | $right['value'];
			}
		}
		$rightsset['ALL_RIGHTS'] = array('value' => $allrights,
				'name' => gettext('All rights'),
				'set' => '',
				'display' => false,
				'hint' => '');
		$rightsset['HIDDEN_RIGHTS'] = array('value' => $hiddenrights,
				'name' => gettext('Hidden rights'),
				'set' => '',
				'display' => false,
				'hint' => 'Rights not shown by PrintAdminRIghtsTable()');
		$rightsset['DEFAULT_RIGHTS'] = array('value' => $rightsset['OVERVIEW_RIGHTS']['value'] + $rightsset['POST_COMMENT_RIGHTS']['value'],
				'name' => gettext('Default rights'),
				'set' => '',
				'display' => false,
				'hint' => '');
		if (isset($rightsset['VIEW_ALL_RIGHTS']['value'])) {
			$rightsset['DEFAULT_RIGHTS']['value'] = $rightsset['DEFAULT_RIGHTS']['value'] | $rightsset['VIEW_ALL_RIGHTS']['value'];
		} else {
			$rightsset['DEFAULT_RIGHTS']['value'] = $rightsset['DEFAULT_RIGHTS']['value'] | $rightsset['ALL_ALBUMS_RIGHTS']['value'] |
							$rightsset['ALL_PAGES_RIGHTS']['value'] | $rightsset['ALL_NEWS_RIGHTS']['value'] |
							$rightsset['VIEW_SEARCH_RIGHTS']['value'] | $rightsset['VIEW_GALLERY_RIGHTS']['value'];
		}
		return $rightsset;
	}

	static function getResetTicket($user, $pass) {
		$req = time();
		$ref = sha1($req . $user . $pass);
		$time = bin2hex(rc4('ticket' . HASH_SEED, $req));
		return $time . $ref;
	}

	function validateTicket($ticket, $user) {
		global $_current_admin_obj;
		$admins = $this->getAdministrators();
		foreach ($admins as $tuser) {
			if ($tuser['user'] == $user) {
				if ($tuser['rights'] & USER_RIGHTS) {
					$request_date = rc4('ticket' . HASH_SEED, pack("H*", $time = substr($ticket, 0, 20)));
					$ticket = substr($ticket, 20);
					$ref = sha1($request_date . $user . $tuser['pass']);
					if ($ref === $ticket) {
						if (time() <= ($request_date + (3 * 24 * 60 * 60))) {
// limited time offer
							$_current_admin_obj = new npg_Administrator($user, 1);
							$_current_admin_obj->reset = true;
						}
					}
					break;
				}
			}
		}
	}

	/**
	 * Set log-in cookie for a user
	 * @param object $user
	 */
	static function logUser($user) {
		$user->set('lastloggedin', date('Y-m-d H:i:s'));
		$user->save();
		setNPGCookie(AUTHCOOKIE, $user->getPass() . '.' . $user->getID());
	}

	/**
	 * User authentication support
	 */
	function handleLogon() {
		global $_current_admin_obj, $_login_error, $_captcha, $_loggedin, $_gallery;
		if (isset($_POST['login'])) {
			if (isset($_POST['user'])) {
				$post_user = sanitize($_POST['user'], 0);
			} else {
				$post_user = NULL;
			}
			if (isset($_POST['pass'])) {
				$post_pass = sanitize($_POST['pass'], 0);
			} else {
				$post_pass = NULL;
			}
			$_loggedin = false;
			switch (isset($_POST['password']) ? $_POST['password'] : NULL) {
				default:
					if (isset($_POST['user'])) { //	otherwise must be a guest logon, don't even try admin path
						$user = self::checkLogon($post_user, $post_pass);
						if ($user) {
							$_loggedin = $user->getRights();
						}

						$_loggedin = npgFilters::apply('admin_login_attempt', $_loggedin, $post_user, $post_pass, $user ? $user : gettext('N/A'));
						if ($_loggedin) {
							self::logUser($user);
							$_current_admin_obj = $user;
						} else {
							clearNPGCookie(AUTHCOOKIE); // Clear the cookie, just in case
							$_login_error = 1;
						}
					}
					break;
				case 'challenge':
					$user = $this->getAnAdmin(array('`user`=' => $post_user, '`valid`=' => 1));
					if (is_object($user)) {
						$info = $user->getChallengePhraseInfo();
						if ($post_pass && $info['response'] == $post_pass) {
							$ref = self::getResetTicket($post_user, $user->getPass());
							header('location:' . getAdminLink('admin-tabs/users.php') . '?ticket=' . $ref . '&user=' . $post_user);
							exit();
						}
					}
					$_login_error = gettext('Sorry, that is not the answer.');
					$_REQUEST['logon_step'] = 'challenge';
					break;
				case 'captcha':
					if ($_captcha->checkCaptcha(trim(isset($_POST['code']) ? $_POST['code'] : ''), sanitize(isset($_POST['code_h']) ? $_POST['code_h'] : '', 3))) {
						require_once(__DIR__ . '/load_objectClasses.php'); // be sure that the plugins are loaded for the mail handler
						if (empty($post_user)) {
							$requestor = gettext('You are receiving this e-mail because of a password reset request on your gallery.');
						} else {
							$requestor = sprintf(gettext("You are receiving this e-mail because of a password reset request on your gallery from a user who tried to log in as %s."), $post_user);
						}
						$admins = $this->getAdministrators();
						$mails = array();
						$user = NULL;
						foreach ($admins as $key => $tuser) {
							if (empty($tuser['email'])) {
								unset($admins[$key]); // we want to ignore users with no email address here!
							} else {
								if (!empty($post_user) && ($tuser['user'] == $post_user || $tuser['email'] == $post_user)) {
									$name = $tuser['name'];
									if (empty($name)) {
										$name = $tuser['user'];
									}
									$mails[$name] = $tuser['email'];
									$user = $tuser;
									unset($admins[$key]); // drop him from alternate list.
								} else {
									if (!($tuser['rights'] & ADMIN_RIGHTS)) {
										unset($admins[$key]); // eliminate any peons from the list
									}
								}
							}
						}
						$found = !empty($mails);
						$bccList = array();
						foreach ($admins as $tuser) {
							$name = $tuser['name'];
							if (empty($name)) {
								$name = $tuser['user'];
							}
							if (is_null($user)) {
								$user = $tuser;
								$mails[$name] = $tuser['email'];
							} else {
								$bccList[$name] = $tuser['email'];
							}
						}

						if (is_null($user)) {
							$_login_error = gettext('There was no one to which to send the reset request.');
						} else {
							$ref = self::getResetTicket($user['user'], $user['pass']);
							$msg = "<p>" . $requestor . "</p>\n";
							if ($found) {
								$msg .= "<p>" . sprintf(gettext('To reset your Admin passwords visit <a href="%1$s">%2$s/reset</a>'), getAdminLink('admin-tabs/users.php') . '?user=' . $user['user'] . '&ticket=' . $ref, FULLWEBPATH) .
												"</p>\n<p>" . gettext("If you do not wish to reset your passwords just ignore this message. This ticket will automatically expire in 3 days.") . "</p>\n";
							} else {
								$msg .= "<p>" . gettext('No matching user was found.' . "</p>\n");
							}
							$err_msg = npgFunctions::mail(gettext("The information you requested"), $msg, $mails, NULL, $bccList, NULL, sprintf(gettext('%1$s password reset request mail failed.'), $user['user']));
							if (empty($err_msg)) {
								$_login_error = 2;
							} else {
								debugLog($err_msg);
								$_login_error = gettext('Reset request failed.');
							}
						}
					} else {
						$_login_error = gettext('CAPTCHA verification failed.');
						$_REQUEST['logon_step'] = 'captcha';
					}
					break;
			}
		}
		return $_loggedin;
	}

	/**
	 *
	 * returns an array of the active "password" cookies
	 *
	 * NOTE: this presumes the general form of an authrization cookie is:
	 * npg_xxxxx_auth{_dddd) where xxxxx is the authority (e.g. gallery, image, search, ...)
	 * and dddd if present is the object id.
	 *
	 */
	static function getAuthCookies() {
		$candidates = array();
		if (isset($_COOKIE)) {
			$candidates = $_COOKIE;
		}
		if (isset($_SESSION)) {
			$candidates = array_merge($candidates, $_SESSION);
		}
		foreach ($candidates as $key => $candidate) {
			if (strpos($key, '_auth') === false) {
				unset($candidates[$key]);
			}
		}
		return $candidates;
	}

	/**
	 * Cleans up on logout
	 *
	 */
	static function handleLogout($location) {
		global $_loggedin, $_pre_authorization, $_current_admin_obj;
		$location = npgFilters::apply('logout', $location, $_current_admin_obj);

		foreach (self::getAuthCookies() as $cookie => $value) {
			clearNPGCookie($cookie);
		}
		clearNPGCookie('ssl_state');
		if ($_current_admin_obj) {
			$_current_admin_obj->updateLastAccess(FALSE);
		}
		$_loggedin = false;
		$_pre_authorization = array();
		npg_session_destroy();

//	try to prevent browser, etc. from using logged-on versions of pages
		if (getOption('SecureLogout')) {
			header('Clear-Site-Data: "cache", "cookies", "storage", "executionContexts"');
		} else {
			header('Clear-Site-Data: "cache"');
		}
		header("Cache-Control: no-cache; private; no-store; must-revalidate"); // HTTP 1.1.
		header("Pragma: no-cache"); // HTTP 1.0.
		header("Expires: 0"); // Proxies.

		header("Location: " . $location);
		exit();
	}

	/**
	 * Checks saved cookies to see if a user is logged in
	 */
	function checkCookieCredentials() {
		$auth = $cookie = getNPGCookie(AUTHCOOKIE);

		if ($cookie) {
			$idLoc = strrpos($cookie, '.');
			if ($idLoc) {
				$id = (int) substr($cookie, $idLoc + 1);
				$auth = substr($cookie, 0, $idLoc);
			} else {
				$id = 0;
			}
			$loggedin = npgFilters::apply('authorization_cookie', $this->checkAuthorization($auth, $id), $auth, $id);
			if ($loggedin) {
//	refresh the cookie so if he visits often enough it is persistent
				setNPGCookie(AUTHCOOKIE, $cookie);
				return $loggedin;
			} else {
				clearNPGCookie(AUTHCOOKIE);
			}
		} else {
			global $_current_admin_obj;
			$row = query_single_row('SELECT `id` FROM ' . prefix('administrators') . 'WHERE `valid`=1', false);
			if (empty($row)) {
				if (DEBUG_LOGIN) {
					debugLog("checkAuthorization: no admins");
				}
				$_current_admin_obj = new npg_Administrator('', 1);
				$_current_admin_obj->set('id', 0);
				$_current_admin_obj->reset = true;
				return ADMIN_RIGHTS;
			}
		}
		return NULL;
	}

	/**
	 * Print the login form . This will take into account whether mod_rewrite is enabled or not.
	 *
	 * @param string $redirect URL to return to after login
	 * @param bool $logo set to true to display the ADMIN logo.
	 * @param bool $showUserField set to true to display the user input
	 * @param bool [deprecated] $deprecated set to false to not display the forgot password captcha.
	 * @param string $hint optional hint for the password
	 *
	 */
	function printLoginForm($redirect = null, $logo = true, $showUserField = true, $deprecated = NULL, $hint = NULL) {
		global $_login_error, $_captcha, $_gallery;

		if (is_null($logo)) {
			$logo = $_gallery->branded;
		}
		if (is_null($redirect)) {
			$redirect = getRequestURI();
		}
		$redirect = npgFilters::apply('login_redirect_link', $redirect);
		if (is_null($showUserField)) {
			$showUserField = $_gallery->getUserLogonField();
		}

		$cycle = sanitize_numeric(isset($_GET['cycle']) ? $_GET['cycle'] : 0) + 1;
		if (isset($_POST['user'])) {
			$requestor = sanitize($_POST['user'], 0);
		} else {
			$requestor = '';
		}
		if (empty($requestor)) {
			if (isset($_GET['ref'])) {
				$requestor = sanitize($_GET['ref']);
			}
		}
		$alt_handlers = npgFilters::apply('alt_login_handler', array());
		ksort($alt_handlers, SORT_LOCALE_STRING);

		$star = false;
		$mails = array();
		$info = array('challenge' => '', 'response' => '');
		if (!empty($requestor)) {
			if ($admin = $this->getAnAdmin(array('`user`=' => $requestor, '`valid`=' => 1))) {
				$info = $admin->getChallengePhraseInfo();
			} else {
				$info = array('challenge' => '');
			}
			if (empty($info['challenge']) || ($cycle > 2 && ($cycle % 5) != 1)) {
				$locale = i18n::getUserLocale();
				$questions = array();
				foreach (getSerializedArray(getOption('challenge_foils')) as $question) {
					$questions[] = get_language_string($question);
				}
				$rslt = query('SELECT `challenge_phrase`,`language` FROM ' . prefix('administrators') . ' WHERE `challenge_phrase` IS NOT NULL');
				while ($row = db_fetch_assoc($rslt)) {
					if (is_null($row['language']) || $row['language'] == $locale) {
						$q = getSerializedArray($row['challenge_phrase']);
						$questions[] = $q['challenge'];
					}
				}
				db_free_result($rslt);
				$questions = array_unique($questions);
				if (!empty($questions)) {
					shuffle($questions);
					$info = array('challenge' => $questions[$cycle % count($questions)], 'response' => 0x00);
				}
			} else {
				if ($admin->getEmail()) {
					$star = true;
				}
			}
		}
		if (!$star) {
			$admins = $this->getAdministrators();
			foreach ($admins as $user) {
				if ($user['email']) {
					$star = true;
					break;
				}
			}
		}
		$whichForm = sanitize(isset($_REQUEST['logon_step']) ? $_REQUEST['logon_step'] : NULL);
		if ($logo && $_gallery->branded) {
			$logo = $_gallery->getSiteLogo(SERVERPATH);
			$im = gl_imageGet($logo);
			$scale = 78 / gl_imageHeight($im);
			$w = gl_imageWidth($im) * $scale;
			if ($w > 355) {
				?>
				<style type="text/css">
					#loginform {
						width: <?php echo $w + 10; ?>px !important;
					}
					#loginform-content {
						padding-left: <?php echo ($w - 347) / 2; ?>px;
					}
				</style>
				<?php
			}
		}
		?>

		<div id="loginform">
			<?php
			if ($logo) {
				?>
				<p>
					<?php printSiteLogoImage(); ?>
				</p>
				<?php
			}
			?>
			<div id="loginform-content">
				<?php
				if ($hint) {
					$welcome = get_language_string($hint);
				} else {
					$welcome = $_gallery->getLogonWelcome();
				}
				if ($welcome) {
					?>
					<p class="logon_welcome">
						<?php echo html_encodeTagged($welcome); ?>
					</p>
					<?php
				}

				switch ($_login_error) {
					case 1:
						?>
						<div class="errorbox" id="message"><h2><?php echo gettext("There was an error logging in."); ?></h2>
							<?php
							if ($showUserField) {
								echo gettext("Check your username and password and try again.");
							} else {
								echo gettext("Check password and try again.");
							}
							?>
						</div>
						<?php
						break;
					case 2:
						?>
						<div class="messagebox fade-message">
							<h2><?php echo gettext("A reset request has been sent."); ?></h2>
						</div>
						<?php
						break;
					default:
						if (!empty($_login_error)) {
							?>
							<div class="errorbox fade-message">
								<h2><?php echo $_login_error; ?></h2>
							</div>
							<?php
						}
						break;
				}

				switch ($whichForm) {
					case 'challenge':
						?>
						<form name="login" id="login" action="<?php echo getAdminLink('admin.php') ?>" method="post">
							<fieldset id="logon_box">
								<input type="hidden" name="login" value="1" />
								<input type="hidden" name="password" value="challenge" />
								<input type="hidden" name="redirect" value="<?php echo pathurlencode($redirect); ?>" />
								<fieldset>
									<legend><?php echo gettext('User') ?></legend>
									<input class="textfield" name="user" id="user" type="text" size="35" value="<?php echo html_encode($requestor); ?>" />
								</fieldset>
								<?php
								if ($requestor) {
									?>
									<p class="logon_form_text"><?php echo gettext('Supply the correct response to the question below and you will be directed to a page where you can change your password.'); ?></p>
									<fieldset><legend><?php echo gettext('Challenge question:') ?></legend>
										<?php
										echo html_encode($info['challenge']);
										?>
									</fieldset>
									<fieldset><legend><?php echo gettext('Your response') ?></legend>
										<input class="textfield" name="pass" id="pass" type="text" size="35" />
									</fieldset>
									<br />
									<?php
								} else {
									?>
									<p class="logon_form_text">
										<?php
										echo gettext('Enter your User ID and press <code>Refresh</code> to get your challenge question.');
										?>
									</p>
									<?php
								}
								?>
								<div>
									<?php
									npgButton('submit', CHECKMARK_GREEN . ' ' . gettext("Log in"), array('buttonClass' => 'submitbutton', 'disabloe' => !$info['challenge']));
									npgButton('button', CLOCKWISE_OPEN_CIRCLE_ARROW_GREEN . ' ' . gettext("Refresh"), array('buttonClick' => "window.location='?logon_step=challenge&amp;ref=' + $('#user').val();"));
									npgButton('button', BACK_ARROW_BLUE . ' ' . gettext("Back"), array('buttonClick' => "window.location='?logon_step=&amp;ref=' + $('#user').val();"));
									?>
								</div>
								<br class="clearall" />
							</fieldset>
							<br />
							<?php
							if ($star) {
								?>
								<p class="logon_link">
									<a onclick="window.location = '?logon_step=captcha&amp;ref=' + $('#user').val();" >
										<?php echo gettext('Request reset by e-mail'); ?>
									</a>
								</p>
								<?php
							}
							?>
						</form>
						<?php
						break;
					default:
						npg_Authority::printPasswordFormJS();
						if (empty($alt_handlers)) {
							$legend = gettext('Login');
						} else {
							?>
							<script type="text/javascript">

								var handlers = [];
					<?php
					$list = '<select id="logon_choices" onchange="changeHandler(handlers[$(this).val()]);">' .
									'<option value="0">' . html_encode(get_language_string($_gallery->getTitle())) . "</option>\n";
					$c = 0;
					foreach ($alt_handlers as $handler => $details) {
						$c++;
						$details['params'][] = 'redirect=' . $redirect;
						if (!empty($requestor)) {
							$details['params'][] = 'requestor=' . $requestor;
						}
						echo "handlers[" . $c . "]=['" . $details['script'] . "','" . implode("','", $details['params']) . "'];\n";

						$list .= '<option value="' . $c . '">' . $handler . "</option>\n";
					}
					$list .= '</select>';
					$legend = sprintf(gettext('Logon using:%s'), $list);
					?>
								function changeHandler(handler) {
									handler.push('user=' + $('#user').val());
									var script = handler.shift();
									window.location = script + '?' + handler.join('&');
								}

							</script>
							<?php
						}
						?>
						<form name="login" id="login" action="<?php echo pathurlencode($redirect); ?>" method="post">
							<input type="hidden" name="login" value="1" />
							<input type="hidden" name="password" value="1" />
							<input type="hidden" name="redirect" value="<?php echo pathurlencode($redirect); ?>" />
							<fieldset id="logon_box"><legend><?php echo $legend; ?></legend>
								<?php
								if ($showUserField) { //	requires a "user" field
									?>
									<fieldset class="login_input"><legend><?php echo gettext("User"); ?></legend>
										<input class="textfield" name="user" id="user" type="text"  value="<?php echo html_encode($requestor); ?>" />
									</fieldset>
									<?php
								}
								?>
								<fieldset class="login_input"><legend><?php echo gettext("Password"); ?></legend>
									<label class="show_checkbox">
										<input type="checkbox" name="disclose_password" id="disclose_password" onclick="togglePassword('');" />
										<?php echo gettext('Show') ?>
									</label>
									<input class="textfield" name="pass" id="pass" type="password"  />
								</fieldset>
								<br />
								<div>
									<?php
									npgButton('submit', CHECKMARK_GREEN . ' ' . gettext("Log in"), array('buttonClass' => 'submitbutton'));
									npgButton('reset', CROSS_MARK_RED . ' ' . gettext("Reset"), array('buttonClass' => 'resetbutton'));
									?>
								</div>
								<br class="clearall" />
							</fieldset>
						</form>

						<?php
						if ($showUserField && OFFSET_PATH != 2) {
							if (getOption('challenge_foil_enabled')) {
								?>
								<p class="logon_link">
									<a onclick="window.location = '?logon_step=challenge&amp;ref=' + $('#user').val();" >
										<?php echo gettext('I forgot my <strong>User ID</strong>/<strong>Password</strong>'); ?>
									</a>
								</p>
								<?php
							} else {
								if ($star) {
									?>
									<p class="logon_link">
										<a onclick="window.location = '?logon_step=captcha&amp;ref=' + $('#user').val();" >
											<?php echo gettext('I forgot my <strong>User ID</strong>/<strong>Password</strong>'); ?>
										</a>
									</p>
									<?php
								}
							}
						}
						break;
					case 'captcha':
						$extra = $class = $buttonClass = $buttonExtra = '';
						$captcha = $_captcha->getCaptcha(NULL);
						if (isset($captcha['submitButton'])) {
							$extra = ' class="' . $captcha['submitButton']['class'] . '" ' . $captcha['submitButton']['extra'];
							$buttonExtra = $captcha['submitButton']['extra'];
							$buttonClass = $captcha['submitButton']['class'];
						}
						?>
						<script type="text/javascript">
							function toggleSubmit() {
								if ($('#user').val()) {
									$('#submitButton').prop('disabled', false);
								} else {
									$('#submitButton').prop('disabled', 'disabled');
								}
							}
						</script>
						<form name="login" id="login" action="<?php echo getAdminLink('admin.php'); ?>" method="post">
							<?php
							if (isset($captcha['hidden']))
								echo $captcha['hidden'];
							?>
							<input type="hidden" name="login" value="1" />
							<input type="hidden" name="password" value="captcha" />
							<input type="hidden" name="redirect" value="<?php echo pathurlencode($redirect); ?>" />
							<fieldset id="logon_box">
								<fieldset><legend><?php echo gettext('User name or e-mail address'); ?></legend>
									<input class="textfield" name="user" id="user" type="text" value="<?php echo html_encode($requestor); ?>" onkeyup="toggleSubmit();"/>
								</fieldset>
								<?php
								if (isset($captcha['html']))
									echo $captcha['html'];
								?>
								<?php
								if (isset($captcha['input'])) {
									?>
									<fieldset><legend><?php echo gettext("Enter CAPTCHA"); ?></legend>
										<?php echo $captcha['input']; ?>
									</fieldset>
									<?php
								}
								?>
								<br />
								<div>
									<?php
									npgButton('submit', CHECKMARK_GREEN . ' ' . gettext("Request password reset"), array('buttonClass' => $buttonClass, 'disabled' => empty($requestor), 'id' => 'submitButton', 'buttonExtra' => $buttonExtra));
									npgButton('button', BACK_ARROW_BLUE . ' ' . gettext("Back"), array('buttonClick' => "window.location='?logon_step=&amp;ref=' + $('#user').val();"));
									?>
								</div>
								<br class="clearall" />
							</fieldset>
						</form>
						<?php
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 *
	 * Javascript for password change input handling
	 */
	static function printPasswordFormJS($all = false) {
		?>
		<script type="text/javascript">

		<?php
		if (OFFSET_PATH || $all) {
			?>
				function passwordStrength(id) {
					var inputa = '#pass' + id;
					var inputb = '#pass_r' + id;
					var displaym = '#match' + id;
					var displays = '#strength' + id;
					var numeric = 0;
					var special = 0;
					var upper = 0;
					var lower = 0;
					var str = $(inputa).val();
					var len = str.length;
					var strength = 0;
					for (c = 0; c < len; c++) {
						if (str[c].match(/[0-9]/)) {
							numeric++;
						} else if (str[c].match(/[^A-Za-z0-9]/)) {
							special++;
						} else if (str[c].toUpperCase() == str[c]) {
							upper++;
						} else {
							lower++;
						}
					}
					if (upper != len) {
						upper = upper * 2;
					}
					if (lower == len) {
						lower = lower * 0.75;
					}
					if (numeric != len) {
						numeric = numeric * 4;
					}
					if (special != len) {
						special = special * 5;
					}
					len = Math.max(0, (len - 6) * .35);
					strength = Math.min(30, Math.round(upper + lower + numeric + special + len));
					if (str.length == 0) {
						$(displays).css('color', 'black');
						$(displays).html('<?php echo gettext('Password'); ?>');
						$(inputa).css('background-image', 'none');
					} else {
						if (strength < 15) {
							$(displays).css('color', '#ff0000');
							$(displays).html('<?php echo gettext('password strength weak'); ?>');
						} else if (strength < 25) {
							$(displays).css('color', '#ff0000');
							$(displays).html('<?php echo gettext('password strength good'); ?>');
						} else {
							$(displays).css('color', '#008000');
							$(displays).html('<?php echo gettext('password strength strong'); ?>');
						}
						if (strength < <?php echo (int) getOption('password_strength'); ?>) {
							$(inputb).prop('disabled', true);
							$(displays).css('color', '#ff0000');
							$(displays).html('<?php echo gettext('password strength too weak'); ?>');
						} else {
							$(inputb).parent().removeClass('ui-state-disabled');
							$(inputb).prop('disabled', false);
							passwordMatch(id);
						}
						d = 512 / 30; //	color gradient steps
						r = (30 - strength) * d;
						g = strength * d;
						url = 'linear-gradient(rgb(' + Math.round(Math.min(r - d, 255)) + ',' + Math.round(Math.min(g - d, 255)) + ',0), rgb(' + Math.round(Math.min(r, 255)) + ',' + Math.round(Math.min(g, 255)) + ',0))';
						$(inputa).css('background-image', url);
						$(inputa).css('background-size', '100%');
					}
				}

				function passwordMatch(id) {
					var inputa = '#pass' + id;
					var inputb = '#pass_r' + id;
					var display = '#match' + id;
					if ($('#disclose_password' + id).prop('checked')) {
						if ($(inputa).val() === $(inputb).val()) {
							if ($(inputa).val().trim() !== '') {
								$(display).css('color', '#008000');
								$(display).html('<?php echo gettext('passwords match'); ?>');
							}
						} else {
							$(display).css('color', '#ff0000');
							$(display).html('<?php echo gettext('passwords do not match'); ?>');
						}
					}
				}

				function passwordClear(id) {
					var inputa = '#pass' + id;
					var inputb = '#pass_r' + id;
					if ($(inputa).val().trim() === '') {
						$(inputa).val('');
					}
					if ($(inputb).val().trim() === '') {
						$(inputb).val('');
					}
				}
			<?php
		}
		?>
			function togglePassword(id) {
				if ($('#pass' + id).attr('type') == 'password') {
					var oldp = $('#pass' + id);
					var newp = oldp.clone();
					newp.attr('type', 'text');
					newp.insertAfter(oldp);
					oldp.remove();
					$('.password_field_' + id).hide();
				} else {
					var oldp = $('#pass' + id);
					var newp = oldp.clone();
					newp.attr('type', 'password');
					newp.insertAfter(oldp);
					oldp.remove();
					$('.password_field_' + id).show();
				}
			}

		</script>
		<?php
	}

	/**
	 * provides the form for password handling
	 *
	 * @param int $id id number for when there are multiple forms on a page
	 * @param bool $pad if true the password will have dummy asterisk filled in
	 * @param bool $disable for disabling the field
	 * @param bool $required if a password is required
	 * @param string $flag to "flag" the field as required
	 */
	static function printPasswordForm($id = '', $pad = false, $disable = NULL, $required = false, $flag = '') {
		if ($pad) {
			$x = '          ';
		} else {
			$x = '';
		}
		if (is_numeric($id)) {
			$format = 'user[%2$s][%1$s]';
		} else {
			$format = '%1$s%2$s';
		}
		?>
		<input type="hidden" name="<?php printf($format, 'passrequired', $id); ?>" id="passrequired-<?php echo $id; ?>" value="<?php echo (int) $required; ?>" class="inputbox"/>
		<p>
			<label for="pass<?php echo $id; ?>_text" id="strength<?php echo $id; ?>">
				<?php echo gettext("Password") . $flag; ?>
			</label>
			<span id="show_disclose_password<?php echo $id; ?>" class="disclose_password_show" style="float: right !important; padding-right: 15px; display: none;">
				<label>
					<?php echo gettext('Show'); ?>
					<input type="checkbox"
								 class="disclose_password"
								 style="float: right !important;"
								 name="<?php printf($format, 'disclose_password', $id); ?>"
								 id="disclose_password<?php echo $id; ?>"
								 onclick="passwordClear('<?php echo $id; ?>');
										 togglePassword('<?php echo $id; ?>');">
				</label>
			</span>
			<label for="pass<?php echo $id; ?>" id="strength<?php echo $id; ?>">
				<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>"
							 name="<?php printf($format, 'pass', $id); ?>" value="<?php echo $x; ?>"
							 id="pass<?php echo $id; ?>"
							 onchange="$('#passrequired-<?php echo $id; ?>').val(1);"
							 onclick="passwordClear('<?php echo $id; ?>');
									 $('#show_disclose_password<?php echo $id; ?>').show();"
							 onkeyup="passwordStrength('<?php echo $id; ?>');"
							 <?php echo $disable; ?> class="password_input inputbox"/>
			</label>
			<br clear="all">
		</p>
		<p class="password_field password_field_<?php echo $id; ?>">
			<label for="pass_r<?php echo $id; ?>" id="match<?php echo $id; ?>">
				<?php echo gettext("Repeat password"); ?>
			</label>
			<input type="password" size="<?php echo TEXT_INPUT_SIZE; ?>"
						 name="<?php printf($format, 'pass_r', $id); ?>" value="<?php echo $x; ?>"
						 id="pass_r<?php echo $id; ?>"
						 disabled="disabled"
						 onchange="$('#passrequired-<?php echo $id; ?>').val(1);"
						 onkeydown="passwordClear('<?php echo $id; ?>');"
						 onkeyup="passwordMatch('<?php echo $id; ?>');" class="inputbox"/>
		</p>
		<?php
	}

	/** PBKDF2 Implementation (described in RFC 2898)
	 *
	 *  @param string p password
	 *  @param string s salt
	 *  @param int c iteration count (use 1000 or higher)
	 *  @param int kl derived key length
	 *  @param string a hash algorithm
	 *
	 *  @return string derived key
	 */
	static function pbkdf2($p, $s, $c = 1000, $kl = 32, $a = 'sha256') {
		$hl = strlen(hash($a, false, true)); # Hash length
		$kb = ceil($kl / $hl); # Key blocks to compute
		$dk = ''; # Derived key
# Create key
		for ($block = 1; $block <= $kb; $block++) {
# Initial hash for this block
			$ib = $b = hash_hmac($a, $s . pack('N', $block), $p, true);
# Perform block iterations
			for ($i = 1; $i < $c; $i++)
# XOR each iterate
				$ib ^= ($b = hash_hmac($a, $b, $p, true));
			$dk .= $ib; # Append iterated block
		}
# Return derived key of correct length
		return substr($dk, 0, $kl);
	}

}

class _Administrator extends PersistentObject {

	/**
	 * This is a simple class so that we have a convienient "handle" for manipulating Administrators.
	 *
	 * NOTE: one should use the npg_Authority newAdministrator() method rather than directly instantiating
	 * an administrator object
	 *
	 */
	protected $objects = NULL;
	public $master = false; //	will be set to true if this is the inherited master user
	public $msg = NULL; //	a means of storing error messages from filter processing
	public $logout_link = true; // for a logout
	public $reset = false; // if true the user was setup by a "reset password" event

	/**
	 * Constructor for an Administrator
	 *
	 * @param string $user.
	 * @param int $valid used to signal kind of admin object
	 * @return Administrator
	 */
	function __construct($user, $valid, $create = true) {
		global $_authority;
		$this->instantiate('administrators', array('user' => $user, 'valid' => $valid), NULL, false, empty($user), $create);
		$this->setUser($user);
		$this->setValid($valid);

		if (empty($user)) {
			$this->set('id', $this->id = -1);
		}
		if ($this->loaded) {
			$this->exists = TRUE;
			$rights = $this->getRights();
			$new_rights = 0;
			if ($_authority->isMasterUser($user)) {
				$new_rights = ALL_RIGHTS;
				$this->master = true;
			} else {
// make sure that the "hidden" gateway rights are set for managing objects
				if ($rights & MANAGE_ALL_ALBUM_RIGHTS) {
					$new_rights = $new_rights | ALBUM_RIGHTS;
				}
				if ($rights & MANAGE_ALL_NEWS_RIGHTS) {
					$new_rights = $new_rights | ZENPAGE_PAGES_RIGHTS;
				}
				if ($rights & MANAGE_ALL_PAGES_RIGHTS) {
					$new_rights = $new_rights | ZENPAGE_NEWS_RIGHTS;
				}
				$this->getObjects();
				foreach ($this->objects as $object) {
					switch ($object['type']) {
						case 'albums':
							if ($object['edit'] & MANAGED_OBJECT_RIGHTS_EDIT) {
								$new_rights = $new_rights | ALBUM_RIGHTS;
							}
							break;
						case 'pages':
							if ($object['edit'] & MANAGED_OBJECT_RIGHTS_EDIT) {
								$new_rights = $new_rights | ZENPAGE_PAGES_RIGHTS;
							}
							break;
						case 'news_categories':
							if ($object['edit'] & MANAGED_OBJECT_RIGHTS_EDIT) {
								$new_rights = $new_rights | ZENPAGE_NEWS_RIGHTS;
							}
							break;
					}
				}
			}
			if ($new_rights) {
				$this->setRights($rights | $new_rights);
			}
		}
	}

	/**
	 * Returns the unformatted date
	 *
	 * @return date
	 */
	function getDateTime() {
		$d = $this->get('date');
		if ($d && $d != '0000-00-00 00:00:00') {
			return $d;
		}
		return false;
	}

	/**
	 * Stores the date
	 *
	 * @param string $datetime formatted date
	 */
	function setDateTime($datetime) {
		$this->set('date', $datetime);
	}

	function getID() {
		return $this->get('id');
	}

	/**
	 * Hashes and stores the password
	 * @param $pwd
	 */
	function setPass($pwd) {
		$pwd = npg_Authority::passwordHash($this->getUser(), $pwd, STRONG_PASSWORD_HASH);
		$this->set('pass', $pwd);
		$this->set('passupdate', date('Y-m-d H:i:s'));
		return $pwd;
	}

	/**
	 * Returns stored password hash
	 */
	function getPass() {
		return $this->get('pass');
	}

	/**
	 * Stores the user name
	 */
	function setName($admin_n) {
		$this->set('name', $admin_n);
	}

	/**
	 * Returns the user name
	 */
	function getName() {
		return $this->get('name');
	}

	/**
	 * Stores the user email
	 */
	function setEmail($admin_e) {
		$this->set('email', strtolower($admin_e));
	}

	/**
	 * Returns the user email
	 */
	function getEmail() {
		return $this->get('email');
	}

	/**
	 * Stores user rights
	 */
	function setRights($rights) {
		$this->set('rights', $rights);
	}

	/**
	 * Returns user rights
	 */
	function getRights() {
		return $this->get('rights');
	}

	/**
	 * Stores local copy of managed objects.
	 * NOTE: The database is NOT updated by this, the user object MUST be saved to
	 * cause an update
	 * use setObjects(NULL) to indicate no change in the objects
	 *
	 * @param array $objects the object list.
	 */
	function setObjects($objects) {
		if (DEBUG_OBJECTS) {
			if (!function_exists('compareObjects') || !compareObjects($this->objects, $objects)) {
				$name = $this->getName();
				if ($name) {
					$name = ' (' . $name . ')';
				}
				debugLogBacktrace($this->getUser() . $name . "->setObjects()");
				debugLogVar(['old' => $this->objects]);
				debugLogVar(['new' => $objects]);
			}
		}
		$this->objects = $objects;
	}

	/**
	 * Returns local copy of managed objects.
	 */
	function getObjects($what = NULL, $full = NULL) {
		if (!is_array($this->objects)) {
			if ($this->transient) {
				$this->objects = array();
			} else {
				$this->objects = populateManagedObjectsList(NULL, $this->getID());
			}
		}
		if (empty($what)) {
			return $this->objects;
		}
		$result = array();
		foreach ($this->objects as $object) {
			if ($object['type'] == $what) {
				if ($full) {
					$result[$object['data']] = $object;
				} else {
					$result[$object['name']] = $object['data'];
				}
			}
		}
		return $result;
	}

	/**
	 * Sets the "valid" flag. Valid is 1 for users, 0 for groups and templates
	 */
	function setValid($valid) {
		$this->set('valid', $valid);
	}

	/**
	 * Returns the valid flag
	 */
	function getValid() {
		return $this->get('valid');
	}

	/**
	 * Sets the user's group.
	 * NOTE this does NOT set rights, etc. that must be done separately
	 */
	function setGroup($group) {
		$this->set('group', $group);
	}

	/**
	 * Returns user's group
	 */
	function getGroup() {
		return $this->get('group');
	}

	/**
	 * Sets the user's user id
	 */
	function setUser($user) {
		$this->set('user', $user);
	}

	/**
	 * Returns user's user id
	 */
	function getUser() {
		return $this->get('user');
	}

	/**
	 * Sets the users quota
	 */
	function setQuota($v) {
		$this->set('quota', $v);
	}

	/**
	 * Returns the users quota
	 */
	function getQuota() {
		return $this->get('quota');
	}

	/**
	 * Returns the user's prefered language
	 */
	function getLanguage() {
		return $this->get('language');
	}

	/**
	 * Sets the user's preferec language
	 */
	function setLanguage($locale) {
		$this->set('language', $locale);
	}

	/**
	 * Updates the database with all changes
	 */
	function save() {
		global $_gallery;
		if (DEBUG_LOGIN) {
			debugLogVar(["npg_Administrator->save()" => $this]);
		}
		if (is_null($this->get('date'))) {
			$this->set('date', date('Y-m-d H:i:s'));
		}
		$updated = parent::save();

		if (is_array($this->objects)) {
			if (DEBUG_OBJECTS) {
				$name = $this->getName();
				if ($name) {
					$name = ' (' . $name . ')';
				}
				debugLogBacktrace($this->getUser() . $name . "->save()");
				debugLogVar(['objects' => $this->objects]);
			}
			$id = $this->getID();
			$old = array();
			$sql = "SELECT `id`, `objectid`, `type`, `edit` FROM " . prefix('admin_to_object') . ' WHERE `adminid`=' . $id;
			$result = query($sql, false);
			while ($row = db_fetch_assoc($result)) {
				$old[$row['id']] = array($row['objectid'], $row['type'], $row['edit']);
			}
			db_free_result($result);
			foreach ($this->objects as $object) {
				$edit = MANAGED_OBJECT_MEMBER;
				if (array_key_exists('edit', $object)) {
					$edit = $object['edit'] | MANAGED_OBJECT_MEMBER;
				}
				$table = $object['type'];
				switch ($table) {
					case 'albums':
						$obj = newAlbum($object['data']);
						$objectid = $obj->getID();
						break;
					case 'pages':
						$obj = newPage($object['data']);
						$objectid = $obj->getID();
						break;
					case 'news_categories':
						if ($object['data'] == '`') { //uncategorized
							$objectid = 0;
						} else {
							$obj = newCategory($object['data']);
							$objectid = $obj->getID();
						}
						break;
				}
				if ($keys = array_keys($old, array($objectid, $table, $edit))) {
					$key = reset($keys);
					unset($old[$key]);
				} else {
					$sql = 'INSERT INTO ' . prefix('admin_to_object') . " (adminid, objectid, type, edit) VALUES ($id, $objectid, '$table', $edit)";
					$result = query($sql);
					$updated = 1;
				}
			}
			if (!empty($old)) {
				$sql = 'DELETE FROM ' . prefix('admin_to_object') . ' WHERE `id` IN(' . implode(',', array_keys($old)) . ')';
				query($sql);
				$updated = 1;
			}
		}
		return $updated;
	}

	/**
	 * Removes a user from the system
	 */
	function remove() {
		npgFilters::apply('remove_user', $this);
		$album = $this->getAlbum();
		$id = $this->getID();
		if (parent::remove()) {
			if (!empty($album)) { //	Remove users album as well
				$album->remove();
			}
			if (DEBUG_OBJECTS) {
				$name = $this->getName();
				if ($name) {
					$name = ' (' . $name . ')';
				}
				debugLogBacktrace($this->getUser() . $name . "->remove()");
			}
			$sql = "DELETE FROM " . prefix('admin_to_object') . " WHERE `adminid`=$id";
			$result = query($sql);
		} else {
			return false;
		}
		return $result;
	}

	/**
	 * Returns the user's "prime" album. See setAlbum().
	 */
	function getAlbum() {
		$id = $this->get('prime_album');
		if (!empty($id)) {
			$sql = 'SELECT `folder` FROM ' . prefix('albums') . ' WHERE `id`=' . $id;
			$result = query_single_row($sql);
			if ($result) {
				$album = newAlbum($result['folder']);
				return $album;
			}
		}
		return false;
	}

	/**
	 * Records the "prime album" of a user. Prime albums are linked to the user and
	 * removed if the user is removed.
	 */
	function setAlbum($album) {
		if ($album) {
			$this->set('prime_album', $album->getID());
		} else {
			$this->set('prime_album', NULL);
		}
	}

	/**
	 * Data to support other credential systems integration
	 */
	function getCredentials() {
		return getSerializedArray($this->get('other_credentials'));
	}

	function setCredentials($cred) {
		$this->set('other_credentials', serialize($cred));
	}

	/**
	 * Creates a "prime" album for the user. Album name is based on the userid
	 */
	function createPrimealbum($new = true, $name = NULL) {
//	create his album
		$t = 0;
		$ext = '';
		if (is_null($name)) {
			$filename = internalToFilesystem(str_replace(array('<', '>', ':', '"' . '/' . '\\', '|', '?', '*'), '_', seoFriendly($this->getUser())));
		} else {
			$filename = internalToFilesystem(str_replace(array('<', '>', ':', '"' . '/' . '\\', '|', '?', '*'), '_', $name));
		}
		while ($new && file_exists(ALBUM_FOLDER_SERVERPATH . $filename . $ext)) {
			$t++;
			$ext = '-' . $t;
		}
		$path = ALBUM_FOLDER_SERVERPATH . $filename . $ext;
		$albumname = filesystemToInternal($filename . $ext);
		if (mkdir_recursive($path, FOLDER_MOD)) {
			$album = newAlbum($albumname);
			if ($title = $this->getName()) {
				$album->setTitle($title);
			}
			$album->setOwner($this->getUser());
			$album->save();
			$this->setAlbum($album);
			$this->setRights($this->getRights() | ALBUM_RIGHTS);
			if (getOption('user_album_edit_default')) {
				$subrights = MANAGED_OBJECT_RIGHTS_EDIT;
			} else {
				$subrights = 0;
			}
			if ($this->getRights() & UPLOAD_RIGHTS) {
				$subrights = $subrights | MANAGED_OBJECT_RIGHTS_UPLOAD;
			}
			$objects = $this->getObjects();
			$objects[] = array('data' => $albumname, 'name' => $albumname, 'type' => 'albums', 'edit' => $subrights);
			$this->setObjects($objects);
		}
	}

	function getChallengePhraseInfo() {
		$info = $this->get('challenge_phrase');
		if ($info) {
			return getSerializedArray($info);
		} else {
			return array('challenge' => '', 'response' => '');
		}
	}

	function setChallengePhraseInfo($challenge, $response) {
		$this->set('challenge_phrase', serialize(array('challenge' => $challenge, 'response' => $response)));
	}

	/**
	 *
	 * returns the last time the user has logged on
	 */
	function getLastLogon() {
		return $this->get('lastloggedin');
	}

	/**
	 * stores current timestamp for user's last page fetch
	 *
	 * @param bool $loggedin true if user is on-line
	 */
	function updateLastAccess($loggedin) {
		global $_authority;
		if ($loggedin) {
			$loggedin = time();
		}
		$this->set('lastaccess', $loggedin);
		$sql = 'UPDATE ' . prefix('administrators') . ' SET `lastaccess`=' . $loggedin . ' WHERE `id`=' . $this->id;
		query($sql, false);
	}

	/**
	 * returns the timestamp of a user's last page fetch
	 *
	 * @return timestamp
	 */
	function getLastAccess() {
		return $this->get('lastaccess');
	}

	/**
	 * retrieves the state of the user policy acknowledgement
	 * @return int
	 */
	function getPolicyACK() {
		return $this->get('policyAck');
	}

	/**
	 * sets the state of the user policy acknowledgement
	 *
	 * @param int $v the state
	 */
	function setPolicyACK($v) {
		$this->set('policyAck', (int) $v);
	}

	function debugRights() {
		global $_authority;
		$rights = $this->getRights();
		echo '<br/>Rights: ';
		foreach ($_authority->getRights()as $right => $detail) {
			if ($rights & $detail['value']) {
				echo $right . ' ';
			}
		}
		echo '<br />';
	}

}
?>
