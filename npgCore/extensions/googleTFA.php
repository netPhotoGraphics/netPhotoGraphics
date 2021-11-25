<?php
/**
 * This plugin is a handler for authentication via the Google Authenticator App.
 * It is based on the {@link https://github.com/Dolondro/google-authenticator google-authenticator library}
 * by Doug Dolondro.
 *
 * When the plugin is enabled there will be a check box for each user that allows him to use
 * <i>Two Factor Authentication</i> when logging onto your site. If <i>Two Factor Authentication</i>
 * is enabled there will also be a QR code image displayed
 * so that the user can add your site to his Google Authenticator providers.
 *
 * If a user has enabled <i>Two Factor Authentication</i> there will be an extra step added
 * to his logon process. After his <code>user name</code> and <code>password</code> have been verified he will be
 * asked to supply the <code>token</code> generated by the Google Authenticator App. If the <code>token</code>
 * he enters is valid the logon will complete.
 *
 * <strong>NOTE</strong>: Google Authenticator relies on time to create the <code>token</code>.
 * If your Server's clock is not in-sync with devices running Google Authenticator,
 * token validation may fail. This can be alleviated by setting the web servers to
 * synchronize with an accurate time source such as an NTP server.
 *
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/googleTFA
 * @pluginCategory security
 *
 * @Copyright 2018 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 */
$plugin_is_filter = 5 | CLASS_PLUGIN;
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext('Two Factor Authentication.');
}

$option_interface = 'googleTFA';

require_once(PLUGIN_SERVERPATH . 'common/fieldExtender.php');
require_once (PLUGIN_SERVERPATH . 'googleTFA/Secret.php');
require_once (PLUGIN_SERVERPATH . 'googleTFA/SecretFactory.php');

npgFilters::register('admin_login_attempt', 'googleTFA::check');
npgFilters::register('save_admin_data', 'googleTFA::save');
npgFilters::register('edit_admin_custom', 'googleTFA::edit', 999);
npgFilters::register("mass_edit_selector", "googleTFA::editSelector");
npgFilters::register('admin_head', 'googleTFA::head');

class googleTFA extends fieldExtender {

	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('googleTFA_issuer', $_SERVER['HTTP_HOST'] . WEBPATH);

			parent::constructor('googleTFA', self::fields());
		}
	}

	function getOptionsSupported() {
		return array(
				gettext('Issuer name') => array('key' => 'googleTFA_issuer', 'type' => OPTION_TYPE_TEXTBOX,
						'desc' => gettext('This is the name the Google Authenticator app associate with the onetime pin code.'))
		);
	}

	static function fields() {
		return array(
				array('table' => 'administrators', 'name' => 'OTAsecret', 'desc' => gettext('secret for googleAuthenticator'), 'type' => 'tinytext'),
				array('table' => 'administrators', 'name' => 'QRuri', 'desc' => gettext('googleAuthenticator QR code data'), 'type' => 'tinytext')
		);
	}

	static function check($loggedin, $post_user, $post_pass, $userobj) {
		if ($loggedin && $userobj->getOTAsecret()) {
			npg_session_start();
			$_SESSION['OTA'] = array('user' => $post_user, 'redirect' => $_POST['redirect']);
			header('Location: ' . getAdminLink(PLUGIN_FOLDER . '/googleTFA/auth_code.php'));
			exit();
		}
		// redirect to form to have the user provide the googleAuth key
		return $loggedin;
	}

	static function save($userobj, $i, $alter) {
		if (editSelectorEnabled('user_edit_googleTFA')) {
			/* single image or the General box is enabled
			 * needed to be sure we don't reset these values because the input was disabled
			 */
			if (isset($_POST['user'][$i]['otp']) && $alter) {
				if (!$userobj->getOTAsecret()) {
					$secretFactory = new \Dolondro\GoogleAuthenticator\SecretFactory();
					$secret = $secretFactory->create(WEBPATH, $userobj->getUser());
					$userobj->setOTAsecret($secret->getSecretKey());
					$userobj->setQRuri($secret->getUri());
				}
			} else {
				if ($userobj->getOTAsecret()) {
					$userobj->setOTAsecret(NULL);
				}
			}
		}
		return $userobj;
	}

	static function head() {
		?>
		<script>
			function googleTFA_exposeSecret(id) {
				if ($('#secret_' + id).css('display') == 'none') {
					$('#secret_' + id).show();
					$('#secret_' + id).height(25);
					$('#secret_' + id).position({
						my: "left",
						at: "center",
						of: "#googleTFA_" + id
					});
					$('#googleTFA_QR_' + id).prop('title', '<?php echo gettext('Click to hide secret'); ?>');
					$('#secret_' + id).select();
				} else {
					$('#secret_' + id).hide();
					$('#googleTFA_QR_' + id).prop('title', '<?php echo gettext('Click to show secret'); ?>');
				}
			}
		</script>
		<?php
	}

	static function edit($html, $userobj, $id, $background, $current) {
		if ($userobj->getOTAsecret()) {
			$checked = ' checked="checked"';
		} else {
			$checked = '';
		}
		$result = '<div class="user_left googleTFA_stuff">' . "\n"
						. "<label>\n"
						. '<input type="checkbox" name="user[' . $id . '][otp]" value="1" ' . $checked . ' />&nbsp;'
						. gettext("Two Factor Authentication") . "\n"
						. "</label>\n";

		if ($checked) {
			$secret = html_encode($userobj->getOTAsecret());
			$result .= "<br />\n"
							. '<fieldset id="googleTFA_' . $id . '">' . "\n"
							. '<legend>' . gettext('Provide to GoogleAuthenticator') . "</legend>\n"
							. '<div style="display: flex; justify-content: center;">' . "\n"
							. '<img src="' . WEBPATH . '/' . CORE_FOLDER . '/' . PLUGIN_FOLDER . '/qrcode/image.php?content=' . html_encode($userobj->getQRuri())
							. '" title="' . gettext('Click to show secret') . '" onclick="googleTFA_exposeSecret(\'' . $id . '\')"'
							. ' id="googleTFA_QR_' . $id . '"'
							. '/>' . "\n"
							. '<br />' . "\n"
							. '<input name="selectable" readonly="readonly" style="display: none;border: none;background: transparent;" id="secret_' . $id . '" value="' . $secret . '" >' . "\n"
							. "</div>\n"
							. "</fieldset>\n";
		}
		$result .= "</div>\n"
						. '<br clear="all">' . "\n";
		return $html . $result;
	}

	static function editSelector($stuff, $whom) {
		switch ($whom) {
			case 'users':
				$stuff['googleTFA'] = gettext('Two factor authenitcation');
		}
		return $stuff;
	}

	static function checkCache($key) {
		global $otpCache;
		$temp = sys_get_temp_dir() . '/_OTP_cache.txt';
		$validTags = array();
		if (file_exists($temp)) {
			$data = explode("\n", file_get_contents($temp));
			$validTags = array_combine(explode(" ", $data[0]), explode(" ", $data[1]));
			foreach ($validTags as $index => $expire) {
				//	remove expired items
				if ($expire < time()) {
					unset($validTags[$index]);
				}
			}
			if (array_key_exists($key, $validTags)) {
				return FALSE;
			}
		}
		$validTags[$key] = time() + 30; //30 second life
		$data = implode(' ', array_keys($validTags)) . "\n" . implode(" ", $validTags);
		file_put_contents($temp, $data);
		return TRUE;
	}

}

function googleTFA_enable($enabled) {
	if ($enabled) {
		$report = gettext('<em>OTAsecret</em> field will be added to the Administrator object.');
	} else {
		$report = gettext('<em>OTAsecret</em> field will be <span style = "color:red;font-weight:bold;">dropped</span> from the Administrator object.');
	}
	requestSetup('googleTFA', $report);
}
