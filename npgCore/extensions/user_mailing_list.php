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

if (npg_loggedin(ADMIN_RIGHTS)) {
	npgFilters::register('admin_tabs', 'user_mailing_list::admin_tabs', -1300);
}

class user_mailing_list {

	static function admin_tabs($tabs) {
		$tabs['admin']['subtabs'][gettext('Mailing list')] = PLUGIN_FOLDER . '/user_mailing_list/user_mailing_listTab.php?tab=mailinglist';
		return $tabs;
	}

	function getOptionsSupported() {
		global $_authority;
		$admins = $_authority->getAdministrators();
		$list = array();
		foreach ($admins as $key => $admin) {
			if (!empty($admin['email'])) {
				$list[] = $admin['user'];
			}
		}
		$options = array(
				gettext('Un-subscribed users') => array('key' => 'user_mailing_list_unsubscribed', 'type' => OPTION_TYPE_CHECKBOX_ARRAY_UL,
						'checkboxes' => $list,
						'desc' => gettext('Users who have unsubscribed from the mailing list are checked. Un-check to re-subscribe the user.')
				)
		);

		return $options;
	}

}

?>