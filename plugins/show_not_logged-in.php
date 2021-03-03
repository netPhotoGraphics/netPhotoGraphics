<?php
/**
 * When enabled, users will be appear not to be logged-in when viewing gallery pages
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/show_not_logged-in
 * @pluginCategory development
 */
$plugin_is_filter = 1001 | FEATURE_PLUGIN;
$plugin_description = sprintf(gettext("Treats users as not logged in for gallery pages."), DATA_FOLDER);


if (OFFSET_PATH) {
	npgFilters::register('admin_note', 'show_not_loggedin::note');
} else {
	npgFilters::register('guest_login_attempt', 'show_not_loggedin::adminLoginAttempt');
	npgFilters::register('login_redirect_link', 'show_not_loggedin::loginRedirect');
	show_not_loggedin::hideAdmin();
}

class show_not_loggedin {

	static function hideAdmin() {
		global $_loggedin, $_current_admin_obj, $_showNotLoggedin_real_auth;
		if (!OFFSET_PATH && is_object($_current_admin_obj)) {
			$_showNotLoggedin_real_auth = $_current_admin_obj;
			if (isset($_SESSION)) {
				unset($_SESSION['user_auth']);
			}
			if (isset($_COOKIE)) {
				unset($_COOKIE['user_auth']);
			}
			$_current_admin_obj = $_loggedin = NULL;
		}
	}

	static function adminLoginAttempt($success, $user, $pass, $athority) {
		if ($athority == 'admin_auth' && $success) {
			header('Location: ' . getAdminLink('admin.php'));
			exit();
		}
		return $success;
	}

	static function loginRedirect($link) {
		global $_showNotLoggedin_real_auth;
		if (is_object($_showNotLoggedin_real_auth)) {
			$link = getAdminLink('admin.php');
			?>
			<div class="error">
				<?php echo gettext('show_not_logged-in is active.'); ?>
			</div>
			<?php
		}
		return $link;
	}

	static function note($where) {
		?>
		<p class="errorbox">
			<strong><?php echo sprintf(gettext('%s is enabled!'), '<a href="' . getAdminLink('admin-tabs/plugins.php') . '?page=plugins&tab=development#show_not_logged-in">show_not_logged-in</a>'); ?></strong>
		</p>
		<?php
	}

}
?>