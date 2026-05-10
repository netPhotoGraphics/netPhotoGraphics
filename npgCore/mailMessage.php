<?php
/**
 *
 * send an email to a user
 *
 * @Copyright 2019 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 * @package core
 */
define('OFFSET_PATH', 1);
require_once(__DIR__ . '/admin-globals.php');

admin_securityChecks(ADMIN_RIGHTS, currentRelativeURL());

$user = $_authority->getAnAdmin(['`id`=' => $_REQUEST['user']]);

if (isset($_POST['send'])) {
	XSRFdefender('mailMessage');
	$subject = NULL;
	$message = NULL;
	if (isset($_POST['subject'])) {
		$subject = sanitize($_POST['subject']);
	}
	if (isset($_POST['message'])) {
		$message = sanitize($_POST['message'], 0);
	}
	$toList = array();
	if ($user->getName()) {
		$toList[$user->getName()] = $user->getEmail();
	} else {
		$toList[] = $user->getEmail();
	}

	$err_msg = npgFunctions::mail($subject, $message, $toList, NULL, NULL);
	if (!$err_msg) {
		header('Location: ' . getAdminLink('admin-tabs/users.htm') . '?page=admin&tab=users&sent&show[]=' . $user->getUser());
		exit();
	}
} else {
	$err_msg = FALSE;
}

printAdminHeader('admin', 'Mailing');
npgFilters::apply('texteditor_config', 'forms');
?>
</head>
<body>
	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<?php
			npgFilters::apply('admin_note', 'mailMessage', '');
			if ($err_msg) {
				echo '<div class="errorbox">';
				echo "<h2>" . gettext('Sending mail failed') . "</h2>";
				echo $err_msg;
				echo '</div>';
			}
			?>

			<h1><?php echo gettext('Send e-mail'); ?></h1>
			<div class="tabbox">

				<?php
				if (!npgFilters::has_filter('sendmail')) {
					$disableForm = ' disabled="disabled"';
					?>
					<p class="notebox">
						<?php
						echo gettext("<strong>Note: </strong>No <em>sendmail</em> filter is registered. You must activate and configure a mailer plugin.");
						?>
					</p>
					<?php
				} else {
					$disableForm = '';
				}
				?>
				<p id="sent" class="messagebox" style="display:none;">
					<?php echo gettext('Mail sent'); ?>
				</p>

				<h2><?php echo gettext('Please enter the message you want to send.'); ?></h2>
				<form class="dirtylistening" onReset="setClean('smail');" id="smail" action="<?php echo getAdminLink('mailMessage.php') ?>" method="post" accept-charset="UTF-8" autocomplete="off">
					<input type="hidden" name="user" value="<?php echo $user->getID(); ?>" />
					<input type="hidden" name="send" value="1" />
					<?php XSRFToken('mailMessage'); ?>


					<div class="floatleft">
						<labelfor="subject"><?php echo gettext('Subject:'); ?></label><br />
							<input type="text" id="subject" name="subject" value="" size="70"<?php echo $disableForm; ?> /><br /><br />
							<label for="message"><?php echo gettext('Message:'); ?></label><br />
							<textarea id="message" class="texteditor" name="message" value="" cols="68" rows="20"<?php echo $disableForm; ?> ></textarea>
					</div>

					<br class="clearall" />

					<p>
						<?php
						applyButton(array('buttonText' => CHECKMARK_GREEN . '	' . gettext("Send mail"), 'disabled' => $disableForm));
						resetButton(array('disabled' => $disableForm));
						?>
					</p>
					<br style="clear: both" />
				</form>

			</div>
		</div><!-- content -->
		<?php printAdminFooter(); ?>
	</div><!-- main -->
</body>
</html>