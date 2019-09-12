<?php

/**
 *
 * A tool to send e-mails to all registered users who have provided an e-mail address.
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @package plugins/user_mailing_list
 * @pluginCategory users
 */
$plugin_is_filter = 5 | ADMIN_PLUGIN;
$plugin_description = gettext("Provides a utility function to send e-mails to all users who have provided an e-mail address.");


if (npg_loggedin(ADMIN_RIGHTS)) {
	npgFilters::register('admin_tabs', 'user_mailing_list::admin_tabs', -1300);
}

class user_mailing_list {

	static function admin_tabs($tabs) {
		$tabs['admin']['subtabs'][gettext('Mailing list')] = PLUGIN_FOLDER . '/user_mailing_list/user_mailing_listTab.php?tab=mailinglist';
		return $tabs;
	}

}

?>