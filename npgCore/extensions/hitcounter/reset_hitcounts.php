<?php

/*
 * The reset code for hitcounters
 */

define('OFFSET_PATH', 4);
require_once(dirname(dirname(__DIR__)) . '/admin-functions.php');
if (isset($_GET['action'])) {
	if (sanitize($_GET['action']) == 'reset_all_hitcounters') {
		if (!npg_loggedin(ADMIN_RIGHTS)) {
			// prevent nefarious access to this page.
			header('Location: ' . getAdminLink('admin.php') . '?from=' . currentRelativeURL());
			exit();
		}
		npg_session_start();
		XSRFdefender('hitcounter');
		$_gallery->set('hitcounter', 0);
		$_gallery->save();
		query('UPDATE ' . prefix('albums') . ' SET `hitcounter`= 0');
		query('UPDATE ' . prefix('images') . ' SET `hitcounter`= 0');
		query('UPDATE ' . prefix('news') . ' SET `hitcounter`= 0');
		query('UPDATE ' . prefix('pages') . ' SET `hitcounter`= 0');
		query('UPDATE ' . prefix('news_categories') . ' SET `hitcounter`= 0');
		purgeOption('page_hitcounters');
		query("DELETE FROM " . prefix('plugin_storage') . " WHERE `type` = 'hitcounter' AND `subtype`='rss'");
		header('Location: ' . getAdminLink('admin.php') . '?action=external&msg=' . gettext('All hitcounters have been set to zero.'));
		exit();
	}
}
