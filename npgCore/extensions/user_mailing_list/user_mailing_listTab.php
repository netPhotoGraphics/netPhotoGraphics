<?php
/**
 *
 * Admin tab for user mailing list
 *
 * @Copyright 2019 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 * @package plugins/user_mailing_list
 */
if (!defined('OFFSET_PATH'))
	define('OFFSET_PATH', 4);
require_once(dirname(dirname(__DIR__)) . '/admin-globals.php');

admin_securityChecks(ADMIN_RIGHTS, currentRelativeURL());

$admins = $_authority->getAdministrators();
$unsubscribe_list = getSerializedArray(getOption('user_mailing_list_unsubscribed'));

var_dump($unsubscribe_list);

printAdminHeader('admin', 'Mailing');
npgFilters::apply('texteditor_config', 'forms');
?>
</head>
<body>
	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<?php npgFilters::apply('admin_note', 'user_mailing', ''); ?>
			<h1><?php echo gettext('User mailing list'); ?></h1>
			<div class="tabbox">
				<p><?php echo gettext("A tool to send e-mails to all registered users who have provided an e-mail address. There is always a copy sent to the current admin and all e-mails are sent as <em>blind copies</em>."); ?></p>
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
				<form class="dirtylistening" onReset="setClean('massmail');" id="massmail" action="<?php echo getAdminLink(PLUGIN_FOLDER . '/user_mailing_list/mail_handler.php'); ?>?sendmail" method="post" accept-charset="UTF-8" autocomplete="off">
					<?php XSRFToken('mailing_list'); ?>


					<div class="floatleft">
						<labelfor="subject"><?php echo gettext('Subject:'); ?></label><br />
							<input type="text" id="subject" name="subject" value="" size="70"<?php echo $disableForm; ?> /><br /><br />
							<label for="message"><?php echo gettext('Message:'); ?></label><br />
							<textarea id="message" class="texteditor" name="message" value="" cols="68" rows="10"<?php echo $disableForm; ?> ></textarea>
					</div>

					<div class="floatleft">

						<div>
							<?php echo gettext('Select users:'); ?>

							<span class="floatright">
								<input type="checkbox" class="ignoredirty" checked="checked" onclick="$('.anuser').prop('checked', $(this).prop('checked'))"/><?php echo gettext('all'); ?>
							</span>
						</div>
						<ul class="unindentedchecklist" style="height: 205px; width: 30em; padding:5px;">
							<?php
							$currentadminuser = $_current_admin_obj->getUser();
							foreach ($admins as $admin) {
								if (!empty($admin['email']) && $currentadminuser != $admin['user'] && !in_array($admin['user'], $unsubscribe_list)) {
									?>
									<li>
										<label for="admin_<?php echo $admin['id']; ?>">
											<input class="anuser ignoredirty" name="admin_<?php echo $admin['id']; ?>" id="admin_<?php echo $admin['id']; ?>" type="checkbox" value="<?php echo html_encode($admin['email']); ?>" checked="checked" />
											<?php
											echo $admin['user'];
											echo " (";
											if (!empty($admin['name'])) {
												echo '"' . $admin['name'] . '" &lt;' . $admin['email'] . '&gt;';
											} else {
												echo $admin['email'];
											}
											echo ")";
											?>
										</label>
									</li>
									<?php
								}
							}
							?>
						</ul>

					</div>
					<br class="clearall" />
					<script type="text/javascript">
						$('form#massmail').submit(function () {
<?php
if (extensionEnabled('tinymce') && getOption('tinymce_forms')) {
	//	force update of textarea
	?>
								message = tinymce.activeEditor.getContent();
								$('#message').html(message);
	<?php
}
?>
							$.post($(this).attr('action'), $(this).serialize(), function (res) {
// Do something with the response `res`
								console.log(res);
							});
							$('form#massmail').trigger('reset');
							$('#sent').show();
							$("#sent").fadeTo(5000, 1).fadeOut(1000);
							return false; // prevent default action
						});
					</script>
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