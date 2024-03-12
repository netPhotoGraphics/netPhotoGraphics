<?php
/**
 * Mailing list unsubscribe handler
 *
 * @author Stephen Billard (sbillard)
 *
 * @Copyright 2020 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 * @package plugins/user_mailing_list
 */
// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(dirname(__DIR__)) . '/admin-globals.php');

admin_securityChecks(NO_RIGHTS, $return = currentRelativeURL());

$unsubscribe_list = getSerializedArray(getOption('user_mailing_list_unsubscribed'));
$whom = $_current_admin_obj->getUser();
$subscribed = !isset($_GET['subscribe']);

printAdminHeader('admin', 'Mailing');
?>
</head>
<body>
	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<div id="container">
				<?php
				if ($subscribed) {
					$unsubscribe_list[] = $whom;
					?>
					<div style="line-height: 20em; text-align: center;">
						<p>
							<?php echo gettext('You are no longer subscribed to the site mailing list'); ?>
						</p>
					</div>
					<p style="text-align: center;">
						<a href="<?php echo getAdminLink(PLUGIN_FOLDER . '/user_mailing_list/subscription.php') . '?subscribe'; ?>">
							<?php echo gettext('Click to resubscribe'); ?>
						</a>
					</p>
					<?php
				} else {
					$key = array_search($whom, $unsubscribe_list);
					if ($key !== FALSE) {
						unset($unsubscribe_list[$key]);
					}
					?>
					<div style="line-height: 20em; text-align: center;">
						<p>
							<?php echo gettext('You are now subscribed to the site mailing list'); ?>
						</p>
					</div>
					<p style="text-align: center;">
						<a href="<?php echo getAdminLink(PLUGIN_FOLDER . '/user_mailing_list/subscription.php') . '?unsubscribe'; ?>">
							<?php echo gettext('Click to un-subscribe'); ?>
						</a>
					</p>
					<?php
				}
				setOption('user_mailing_list_unsubscribed', serialize(array_unique($unsubscribe_list)));
				?>
			</div>
		</div>
		<?php printAdminFooter(); ?>
	</div>

	<script>
		$('.hiddenOrphan').hide();
<?php
if (!isset($highlighted)) {
	?>
			$('.highlighted').remove();
	<?php
}
?>
	</script>
</body>
</html>
