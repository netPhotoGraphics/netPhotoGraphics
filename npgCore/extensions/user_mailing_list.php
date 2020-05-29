<?php

/**
 *
 * A tool to send e-mails to all registered users who have provided an e-mail address.
 *
 * Mails sent from this plugin will use the e-mail form template as specified by the "forms" option.
 * If you wish to provide your users with an <i>un-subscribe</i> link you can insert
 * the following in the mailform template. (This link is part of the distributed mailform.)
 *
 * <var>&lt;a href="&#37;WEBPATH&#37;/&#37;CORE_PATH&#37;/&#37;PLUGIN_PATH&#37;/user_mailing_list/subscription&#37;RW_SUFFIX&#37;?usubscribe"&gt;usubscribe&lt;/a&gt;</var>
 *
 * The "%" constants will be replaced by your sites settings to give a valid link
 * to the <i>user_mailing_list</i> subscription handler.
 *
 * @author Stephen Billard (sbillard), Malte Müller (acrylian)
 * @package plugins/user_mailing_list
 * @pluginCategory users
 */
$plugin_is_filter = 5 | ADMIN_PLUGIN;
$plugin_description = gettext("Provides a utility function to send e-mails to all users who have provided an e-mail address.");

$option_interface = 'user_mailing_list';

if (npg_loggedin(USER_RIGHTS)) {
	npgFilters::register('admin_tabs', 'user_mailing_list::admin_tabs', -1300);
	npgFilters::register('save_admin_data', 'user_mailing_list::save');
	npgFilters::register('edit_admin_custom', 'user_mailing_list::edit', 999);
}

class user_mailing_list {

	static function admin_tabs($tabs) {
		$tabs['admin']['subtabs'][gettext('Mailing list')] = PLUGIN_FOLDER . '/user_mailing_list/user_mailing_listTab.php?tab=mailinglist';
		return $tabs;
	}

	function getOptionsSupported() {
		global $_authority;
		$admins = $_authority->getAdministrators('all');
		$admins = sortMultiArray($admins, array('valid', 'user'), false, TRUE, TRUE, TRUE);
		$groups = $list = array();
		foreach ($admins as $key => $admin) {
			if ($admin['valid']) {
				if (!empty($admin['email'])) {
					$list[] = $admin['user'];
				}
			} else {
				if ($admin['name'] == 'group') {
					$groups[] = $admin['user'];
				}
			}
		}

		$options = array(
				gettext('Un-subscribed users') => array('key' => 'user_mailing_list_unsubscribed', 'type' => OPTION_TYPE_CHECKBOX_ARRAY_UL,
						'checkboxes' => $list,
						'desc' => gettext('Users who have unsubscribed from the mailing list are checked. Un-check to re-subscribe the user.')
				),
				gettext('Un-subscribed groups') => array('key' => 'user_mailing_list_excluded', 'type' => OPTION_TYPE_CHECKBOX_ARRAY_UL,
						'checkboxes' => $groups,
						'desc' => gettext('Check the groups you wish excluded from the mailing recipient list.')
				)
		);

		return $options;
	}

	static function edit($html, $userobj, $id, $background, $current) {
		global $_current_admin_obj;
		if ($userobj == $_current_admin_obj) {
			$unsubscribe_list = getSerializedArray(getOption('user_mailing_list_unsubscribed'));
			$whom = $userobj->getUser();
			if (in_array($whom, $unsubscribe_list)) {
				$checked = ' checked="checked"';
			} else {
				$checked = '';
			}

			$result = '<div class="user_left">' . "\n"
							. "<label>\n"
							. '<input type="checkbox" name="user[' . $id . '][mailinglist]" value="1" ' . $checked . ' />&nbsp;'
							. gettext("Opt-out of the mailing list") . "\n"
							. "</label>\n"
							. "</div>\n"
							. '<br clear="all">' . "\n";
			$html .= $result;
		}
		return $html;
	}

	static function save($userobj, $i, $alter) {
		global $_current_admin_obj;
		if ($userobj->getID() == $_current_admin_obj->getID()) {
			$whom = $userobj->getUser();
			if (isset($_POST['user'][$i]['mailinglist'])) {
				$unsubscribe_list[] = $whom;
			} else {
				$unsubscribe_list = getSerializedArray(getOption('user_mailing_list_unsubscribed'));
				$key = array_search($whom, $unsubscribe_list);
				if ($key !== FALSE) {
					unset($unsubscribe_list[$key]);
				}
			}
			setOption('user_mailing_list_unsubscribed', serialize(array_unique($unsubscribe_list)));
		}
	}

}

?>