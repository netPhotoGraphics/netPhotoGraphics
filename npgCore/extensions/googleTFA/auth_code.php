<?php
/**
 * presents a form to get the user's googleAuthenticator authorization code.
 */
require_once(dirname(dirname(__DIR__)) . '/admin-globals.php');
require_once (PLUGIN_SERVERPATH . 'googleTFA/Secret.php');
require_once (PLUGIN_SERVERPATH . 'googleTFA/SecretFactory.php');

if (isset($_SESSION['OTA'])) {
	$user = $_SESSION['OTA']['user'];

	$userobj = $_authority->getAnAdmin(array('`user`=' => $user, '`valid`=' => 1));
	if ($userobj && $userobj->getOTAsecret()) {

		if (isset($_POST['authenticate'])) {
			require_once (PLUGIN_SERVERPATH . 'common/Base32.php');
			require_once (PLUGIN_SERVERPATH . 'googleTFA/GoogleAuthenticator.php');
			$link = $_SESSION['OTA']['redirect'];
			unset($_SESSION['OTA']); // kill the possibility of a replay
			$secret = $userobj->getOTAsecret();
			$code = $_POST['authenticate'];
			$googleAuth = new Dolondro\GoogleAuthenticator\GoogleAuthenticator();
			$authOK = $googleAuth->authenticate($secret, $code);
			if ($authOK) {
				if (googleTFA::checkCache(crypt($secret . "|" . $code, md5($code)))) {
					npg_Authority::logUser($userobj);
					header('Location: ' . $link);
					exit();
				}
			}
			$_SESSION['OTA'] = array('user' => $user, 'redirect' => $link); //	restore for the next attempt
		}
		printAdminHeader('overview');
		echo "\n</head>";
		?>
		<body style="background-image: none">
			<div id="loginform">
				<p>
					<?php printSiteLogoImage(); ?>
				</p>

				<?php
				if (isset($authOK)) {
					?>
					<div class="errorbox" id="message">
						<h2><?php echo gettext("The Token you entered is not valid."); ?></h2>
					</div>
					<?php
				}
				?>
				<form name="OTP" id="OTP" action="<?php echo getAdminLink(PLUGIN_FOLDER . '/googleTFA/auth_code.php'); ?>" method="post">
					<input type="hidden" name="authenticate" value="1" />
					<fieldset id="logon_box">
						<legend><?php echo gettext('Google Authenticator Token'); ?></legend>
						<input class="textfield" name="authenticate" id="authcode" type="text" autofocus />
						<br />
						<br />

						<?php
						applyButton(array('buttonText' => CHECKMARK_GREEN . ' ' . gettext("Submit"), 'buttonClass' => 'submitbutton'));
						backButton(array('buttonText' => CROSS_MARK_RED . ' ' . gettext("Cancel"), 'buttonLink' => FULLWEBPATH));
						?>

						<br class="clearall" />
					</fieldset>
				</form>
			</div>
		</body>
		<?php
		echo "\n</html>";
		exit();
	}
}
