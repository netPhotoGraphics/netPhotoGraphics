<?php
/**
 * Provides users a means to log in or out from the theme pages.
 *
 * Place a call on <var>printUserLogin_out()</var> where you want the link or form to appear.
 *
 * <b>Login form options:</b>
 * <dl>
 * 	<dt><i>None</i></dt><dd><i>Provides a link to the login form.</i></dd>
 *  <dt><i>Form</i></dt><dd><i>Displays the login form on the theme page.</i></dd>
 *  <dt><i>Colorbox link</i>*</dt><dd><i>Provides a link that when clicked will pop-up a login form.</i></dd>
 *  <dt><i>Colorbox inline</i>*</dt><dd><i>Displays the login form as a pop-up.</i></dd>
 * </dl>
 *
 * *If the <code>colorbox_js</code> plugin is not enabled, these options default to <I>None</i>.
 *
 *
 * <b>Note:</b> if your site is <i>private</i> you can use <i>Colorbox inline</i> to show a pop-up
 * login form on your index page instead of displaying just the login page.
 * To do this <code>index.php</code> must be checked in the <i>Unprotected pages</i> Gallery option.
 * There will be no button to close the pop-up without logging in, but be aware that if the visitor types an escape
 * the form will close and your index page will be visible.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/user_login-out
 * @pluginCategory users
 */
$plugin_is_filter = 900 | THEME_PLUGIN;
$plugin_description = gettext("Provides a means for users to login/out from your theme pages.");

$option_interface = 'user_logout_options';
if (isset($_gallery_page) && getOption('user_logout_login_form') > 1) {
	require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/colorbox_js.php');
	if (!npgFilters::has_filter('theme_head', 'colorbox::css')) {
		npgFilters::register('theme_head', 'colorbox::css');
	}
}

/**
 * Plugin option handling class
 *
 */
class user_logout_options {

	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('user_logout_login_form', 0);
		}
	}

	function getOptionsSupported() {
		return array(gettext('Login form') => array('key' => 'user_logout_login_form', 'type' => OPTION_TYPE_RADIO,
						'buttons' => array(gettext('None') => 0, gettext('Form') => 1, gettext('Colorbox link') => 2, gettext('Colorbox inline') => 3),
						'desc' => gettext('If the user is not logged-in display an <em>in-line</em> logon form or a link to a modal <em>Colorbox</em> form.'))
		);
	}

	function handleOption($option, $currentValue) {

	}

}

if (in_context(NPG_INDEX)) {
	if (isset($_GET['userlog'])) { // process the logout.
		if ($_GET['userlog'] == 0) {
			$__redirect = array();
			if (in_context(NPG_ALBUM)) {
				$__redirect['album'] = $_current_album->name;
			}
			if (in_context(NPG_IMAGE)) {
				$__redirect['image'] = $_current_image->filename;
			}
			if (in_context(ZENPAGE_PAGE)) {
				$__redirect['title'] = $_CMS_current_page->getTitlelink();
			}
			if (in_context(ZENPAGE_NEWS_ARTICLE)) {
				$__redirect['title'] = $_CMS_current_article->getTitlelink();
			}
			if (in_context(ZENPAGE_NEWS_CATEGORY)) {
				$__redirect['category'] = $_CMS_current_category->getTitlelink();
			}
			if (isset($_GET['p'])) {
				$__redirect['p'] = sanitize($_GET['p']);
			}
			if (isset($_GET['searchfields'])) {
				$__redirect['searchfields'] = sanitize($_GET['searchfields']);
			}
			if (isset($_GET['words'])) {
				$__redirect['words'] = sanitize($_GET['words']);
			}
			if (isset($_GET['date'])) {
				$__redirect['date'] = sanitize($_GET['date']);
			}
			if (isset($_GET['title'])) {
				$__redirect['title'] = sanitize($_GET['title']);
			}
			if (isset($_GET['page'])) {
				$__redirect['page'] = sanitize($_GET['page']);
			}

			$params = '';
			if (!empty($__redirect)) {
				foreach ($__redirect as $param => $value) {
					$params .= '&' . $param . '=' . $value;
				}
			}
			$location = npg_Authority::handleLogout(FULLWEBPATH . '/index.php?fromlogout' . $params);
			header("Location: " . $location);
			exit();
		}
	}
}

/**
 * Prints the logout link if the user is logged in.
 * This is for album passwords only, not admin users;
 *
 * @param string $before before text
 * @param string $after after text
 * @param int $showLoginForm to display a login form
 * 				0: to not display a login form, but just a login link
 * 				1: to display a login form
 * 				2: to display a link to a login form which will display in colorbox if the colorbox_js plugin is enabled.
 * @param string $logouttext optional replacement text for "Logout"
 */
function printUserLogin_out($before = '', $after = '', $showLoginForm = NULL, $logouttext = NULL) {
	global $_gallery, $__redirect, $_current_admin_obj, $_login_error, $_gallery_page;
	$excludedPages = array('password.php', 'register.php', 'favorites.php', '404.php');
	$logintext = gettext('Login');
	if (is_null($logouttext))
		$logouttext = gettext("Logout");
	$params = array("userlog=0");
	if (!empty($__redirect)) {
		foreach ($__redirect as $param => $value) {
			$params[] .= $param . '=' . urlencode($value);
		}
	}
	if (is_null($showLoginForm)) {
		$showLoginForm = getOption('user_logout_login_form');
	}
	if (is_object($_current_admin_obj)) {
		if (!$_current_admin_obj->logout_link) {
			return;
		}
	}
	$cookies = npg_Authority::getAuthCookies();
	if (empty($cookies) || !npg_loggedin()) {
		if (!in_array($_gallery_page, $excludedPages)) {
			switch ($showLoginForm) {
				case 1:
					?>
					<div class="passwordform">
						<?php printPasswordForm('', true, false); ?>
					</div>
					<?php
					break;
				case 2:
				case 3:
					if (extensionEnabled('colorbox_js')) {
						if (!npgFilters::has_filter('theme_head', 'colorbox::css')) {
							colorbox::css();
						}
						?>
						<script type="text/javascript">
							// <!-- <![CDATA[
							window.addEventListener('load', function () {
							$(".logonlink").colorbox({
							inline: true,
											innerWidth: "400px",
											href: "#passwordform",
											close: '<?php echo gettext("close"); ?>',
						<?php
						if ($showLoginForm == 3) {
							?>
								closeButton:false,
												open: 1
							<?php
						} else if (isset($_GET['logon_step'])) {
							?>
								open: 1
							<?php
						} else {
							?>
								open: $('#passwordform_enclosure .errorbox').length
							<?php
						}
						?>
							});
							}
							, false);
							// ]]> -->
						</script>
						<?php
						if ($before) {
							echo '<span class="beforetext">' . html_encodeTagged($before) . '</span>';
						}
						?>
						<a href="#" class="logonlink" title="<?php echo $logintext; ?>"><?php echo $logintext; ?></a>
						<span id="passwordform_enclosure" style="display:none">
							<div class="passwordform">
								<?php printPasswordForm('', true, false); ?>
							</div>
						</span>
						<?php
						if ($after) {
							echo '<span class="aftertext">' . html_encodeTagged($after) . '</span>';
						}
						break;
					}
				default:
					$theme = $_gallery->getCurrentTheme();
					if (file_exists(SERVERPATH . '/' . THEMEFOLDER . '/' . $theme . '/password.php')) {
						$link = getCustomPageURL('password');
					} else {
						$link = getAdminLink('admin.php');
					}
					if ($loginlink = npgFilters::apply('login_link', $link)) {
						if ($before) {
							echo '<span class="beforetext">' . html_encodeTagged($before) . '</span>';
						}
						?>
						<a href="<?php echo $loginlink; ?>" class="logonlink" title="<?php echo $logintext; ?>">
							<?php echo $logintext; ?>
						</a>
						<?php
						if ($after) {
							echo '<span class="aftertext">' . html_encodeTagged($after) . '</span>';
						}
					}
			}
		}
	} else {
		if ($before) {
			echo '<span class="beforetext">' . html_encodeTagged($before) . '</span>';
		}
		$logoutlink = FULLWEBPATH . '?' . implode('&', $params);
		?>
		<a href="<?php echo html_encode($logoutlink); ?>" title="<?php echo $logouttext; ?>">
			<?php echo $logouttext; ?>
		</a>
		<?php
		if ($after) {
			echo '<span class="aftertext">' . html_encodeTagged($after) . '</span>';
		}
	}
}
?>