<?php
/*
 * LDAP authorization plugin
 *
 * This plugin will link the site to an LDAP server for user verification.
 * It assumes that your LDAP server contains posix-style users and groups.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/LDAP_auth
 * @pluginCategory users
 */

$plugin_is_filter = 5 | CLASS_PLUGIN;
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext('Enable LDAP user authentication.');
	$plugin_disable = function_exists('ldap_connect') ? '' : gettext('php_ldap extension is not enabled');
}

$option_interface = 'LDAP_auth_options';

if (function_exists('ldap_connect') && !class_exists('_Authority')) {
	require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/LDAP_auth/LDAP auth.php');
}

class LDAP_auth_options {

	function __construct() {
		global $_authority;
		setOptionDefault('ldap_ou', 'Users');
		setOptionDefault('ldap_group_ou', 'Groups, Roles');
		setOptionDefault('ldap_reader_ou', 'Users');
		setOptionDefault('ldap_id_offset', 100000);
		setOptionDefault('ldap_membership_attribute', 'memberuid');
		if (extensionEnabled('user_groups')) {
			$ldap = getOption('ldap_group_map');
			if (is_null($ldap)) {
				$groups = $_authority->getAdministrators('groups');
				if (!empty($groups)) {
					foreach ($groups as $group) {
						if ($group['name'] != 'template') {
							$ldap[$group['user']] = $group['user'];
						}
					}
				}
				if (!empty($ldap)) {
					setOption('ldap_group_map', serialize($ldap));
				}
			}
		}
	}

	static function getOptionsSupported() {

		$ldapOptions = array(
				gettext('LDAP domain') => array('key' => 'ldap_domain', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 1,
						'desc' => gettext('Domain name of the LDAP server')),
				gettext('LDAP ou') => array('key' => 'ldap_ou', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 3,
						'desc' => gettext('Comma separated list of Organizational Units where user credentials are stored')),
				gettext('LDAP base dn') => array('key' => 'ldap_basedn', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 2,
						'desc' => gettext('Base distinguished name strings for the LDAP searches.')),
				gettext('ID offset for LDAP usersids') => array('key' => 'ldap_id_offset', 'type' => OPTION_TYPE_NUMBER,
						'order' => 4,
						'desc' => gettext('This number is added to the LDAP <em>userid</em> to insure that there is no overlap to netPhotoGraphics <em>userids</em>.')),
				gettext('LDAP reader ou') => array('key' => 'ldap_reader_ou', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 7,
						'desc' => gettext('Organizational Unit where the LDAP reader user credentials are stored')),
				gettext('LDAP reader userid') => array('key' => 'ldap_reader_user', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 5,
						'desc' => gettext('User ID used for LDAP searches.')),
				gettext('LDAP reader password') => array('key' => 'ldap_reader_pass', 'type' => OPTION_TYPE_PASSWORD,
						'order' => 6,
						'desc' => gettext('User password for LDAP searches.'))
		);
		if (extensionEnabled('user_groups')) {
			$ldapOptions[gettext('LDAP Group map')] = array('key' => 'ldap_group_map_custom', 'type' => OPTION_TYPE_CUSTOM,
					'order' => 7,
					'desc' => gettext('Mapping of LDAP groups to netPhotoGraphics groups') . '<p class="notebox">' . gettext('<strong>Note:</strong> if the LDAP group is empty no mapping will take place.') . '</p>');
			$ldapOptions[gettext('LDAP membership attribute')] = array('key' => 'ldap_membership_attribute', 'type' => OPTION_TYPE_SELECTOR,
					'selections' => array(gettext('Member') => 'member', gettext('Member Uid') => 'memberuid'),
					'order' => 8,
					'desc' => gettext('How users are mapped to LDAP groups.'));
			$ldapOptions[gettext('LDAP Group ou')] = array('key' => 'ldap_group_ou', 'type' => OPTION_TYPE_CLEARTEXT,
					'order' => 9,
					'desc' => gettext('Comma separated list of Organizational Units where Group Membership is stored.'));
			if (!extensionEnabled('LDAP_auth')) {
				$ldapOptions['note'] = array(
						'key' => 'LDAP_auth_note', 'type' => OPTION_TYPE_NOTE,
						'order' => 0,
						'desc' => '<p class="notebox">' . gettext('The LDAP Group map cannot be managed with the plugin disabled') . '</p>');
			}
		}

		return $ldapOptions;
	}

	static function handleOption($option, $currentValue) {
		global $_authority;
		if ($option == 'ldap_group_map_custom') {
			$groups = $_authority->getAdministrators('groups');
			$ldap = getSerializedArray(getOption('ldap_group_map'));
			if (empty($groups)) {
				echo gettext('No groups or templates are defined');
			} else {
				?>
				<dl>
					<dt><em><?php echo gettext('netPhotoGraphics group'); ?></em></dt>
					<dd><em><?php echo gettext('LDAP group'); ?></em></dd>
					<?php
					foreach ($groups as $group) {
						if ($group['name'] != 'template') {
							if (array_key_exists($group['user'], $ldap)) {
								$ldapgroup = $ldap[$group['user']];
							} else {
								$ldapgroup = $group['user'];
							}
							?>
							<dt>
								<?php echo html_encode($group['user']); ?>
							</dt>
							<dd>
								<?php echo '<input type="textbox" name="LDAP_group_for_' . $group['id'] . '" value="' . html_encode($ldapgroup) . '">'; ?>
							</dd>
							<?php
						}
					}
					?>
				</dl>
				<?php
			}
		}
	}

	static function handleOptionSave($themename, $themealbum) {
		global $_authority;
		$groups = $_authority->getAdministrators('groups');
		if (!empty($groups)) {
			$ldap = NULL;
			foreach ($_POST as $key => $v) {
				if (strpos($key, 'LDAP_group_for_') !== false) {
					$ldap[$groups[substr($key, 15)]['user']] = $v;
				}
			}
			if ($ldap) {
				setOption('ldap_group_map', serialize($ldap));
			}
		}
	}

}
