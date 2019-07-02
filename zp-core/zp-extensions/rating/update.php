<?php

/**
 * rating plugin updater - Updates the rating in the database
 * @author Stephen Billard (sbillard)
 * @package plugins/rating
 */
define('OFFSET_PATH', 4);
require_once(dirname(dirname(dirname(__FILE__))) . '/functions.php');

if (isset($_GET['action']) && $_GET['action'] == 'clear_rating') {
	if (!npg_loggedin(ADMIN_RIGHTS)) {
		// prevent nefarious access to this page.
		header('Location: ' . getAdminLink('admin.php') . '?from=' . currentRelativeURL());
		exit();
	}

	require_once(CORE_SERVERPATH . 'admin-functions.php');
	if (session_id() == '') {
		// force session cookie to be secure when in https
		if (secureServer()) {
			$CookieInfo = session_get_cookie_params();
			session_set_cookie_params($CookieInfo['lifetime'], $CookieInfo['path'], $CookieInfo['domain'], TRUE);
		}
		npg_session_start();
	}
	XSRFdefender('clear_rating');
	query('UPDATE ' . prefix('images') . ' SET total_value=0, total_votes=0, rating=0, used_ips="" ');
	query('UPDATE ' . prefix('albums') . ' SET total_value=0, total_votes=0, rating=0, used_ips="" ');
	query('UPDATE ' . prefix('news') . ' SET total_value=0, total_votes=0, rating=0, used_ips="" ');
	query('UPDATE ' . prefix('pages') . ' SET total_value=0, total_votes=0, rating=0, used_ips="" ');
	header('Location: ' . getAdminLink('admin.php') . '?action=external&msg=' . gettext('All ratings have been set to <em>unrated</em>.'));
	exit();
}

if (extensionEnabled('rating') && isset($_POST['id']) && isset($_POST['table'])) {
	require_once(CORE_SERVERPATH . 'template-functions.php');
	require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/rating.php');
	$id = sanitize_numeric($_POST['id']);
	$table = sanitize($_POST['table'], 3);
	$dbtable = prefix($table);
	$ip = getUserID();
	$unique = '_' . $table . '_' . $id;
	if (isset($_POST['star_rating-value' . $unique])) {
		$rating = ceil(sanitize_numeric($_POST['star_rating-value' . $unique]) / max(1, getOption('rating_split_stars')));

		// Make sure the incoming rating isn't a hack
		$rating = min(getOption('rating_stars_count'), max(0, $rating));
		$IPlist = query_single_row("SELECT * FROM $dbtable WHERE id= $id");
		if (is_array($IPlist)) {
			$oldrating = jquery_rating::getRatingByIP($ip, $IPlist['used_ips'], $IPlist['rating']);
		} else {
			$oldrating = false;
		}
		if (!$oldrating || getOption('rating_recast')) {
			if ($rating) {
				$_rating_current_IPlist[$ip] = (float) $rating;
			} else {
				if (isset($_rating_current_IPlist[$ip])) {
					unset($_rating_current_IPlist[$ip]); // retract vote
				}
			}
			$insertip = serialize($_rating_current_IPlist);
			if ($oldrating) {
				if ($rating) {
					$voting = '';
				} else {
					$voting = ' total_votes=total_votes-1,'; // retract vote
				}
				$valuechange = $rating - $oldrating;
				if ($valuechange >= 0) {
					$valuechange = '+' . $valuechange;
				}
				$valuechange = ' total_value=total_value' . $valuechange . ',';
			} else {
				$voting = ' total_votes=total_votes+1,';
				$valuechange = ' total_value=total_value+' . $rating . ',';
			}
			$sql = "UPDATE " . $dbtable . ' SET' . $voting . $valuechange . " rating=total_value/total_votes, used_ips=" . db_quote($insertip) . " WHERE id='" . $id . "'";
			$rslt = query($sql, false);
		}
	}
}
?>
