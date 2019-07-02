<?php
/**
 * user_groups plugin--tabs
 * @author Stephen Billard (sbillard)
 * @package plugins/user-expiry
 */
define('OFFSET_PATH', 4);
require_once(dirname(dirname(dirname(__FILE__))) . '/admin-globals.php');

admin_securityChecks(ADMIN_RIGHTS, currentRelativeURL());

$subscription = 86400 * getOption('user_expiry_interval');
$now = time();
$warnInterval = $now + getOption('user_expiry_warn_interval') * 86400;

$admins = $_authority->getAdministrators('all');
foreach ($admins as $key => $user) {
	if ($user['valid'] && !($user['rights'] & ADMIN_RIGHTS)) {
		if ($subscription) {
			$admins[$key]['expires'] = strtotime($user['date']) + $subscription;
		} else {
			$admins[$key]['expires'] = 0;
		}
	} else {
		unset($admins[$key]);
	}
}

if ($subscription) {
	$admins = sortMultiArray($admins, array('expires'), false);
} else {
	$admins = sortMultiArray($admins, array('lastlogon'), true);
}

$adminordered = array();
foreach ($admins as $user) {
	$adminordered[] = $user;
}

$msg = NULL;
if (isset($_GET['action'])) {
	$action = sanitize($_GET['action']);
	XSRFdefender($action);
	if ($action == 'expiry') {
		foreach ($_POST as $key => $action) {
			if (strpos($key, 'r_') === 0) {
				$userobj = $_authority->getAnAdmin(array('`id`=' => sanitize(postIndexDecode(str_replace('r_', '', $key)))));
				if ($userobj) {
					switch ($action) {
						case 'delete':
							$userobj->remove();
							break;
						case 'disable':
							$userobj->setValid(2);
							$userobj->save();
							break;
						case 'enable':
							$userobj->setValid(1);
							$userobj->save();
							break;
						case 'renew':
							$newdate = getOption('user_expiry_interval') * 86400 + strtotime($userobj->getDateTime());
							if ($newdate + getOption('user_expiry_interval') * 86400 < time()) {
								$newdate = time() + getOption('user_expiry_interval') * 86400;
							}
							$userobj->setDateTime(date('Y-m-d H:i:s', $newdate));
							$userobj->setValid(1);
							$userobj->save();
							break;
						case 'force':
							$userobj->set('passupdate', NULL);
							$userobj->save();
							break;
						case 'revalidate':
							$site = $_gallery->getTitle();
							$user_e = $userobj->getEmail();
							$user = $userobj->getUser();
							$key = bin2hex(serialize(array('user' => $user, 'email' => $user_e, 'date' => time())));
							$link = FULLWEBPATH . '/index.php?user_expiry_reverify=' . $key;
							$message = sprintf(gettext('Your %1$s credentials need to be renewed. Visit %2$s to renew your logon credentials.'), $site, $link);
							$msg = npgFunctions::mail(sprintf(gettext('%s renewal required'), $site), $message, array($user => $user_e));
							break;
					}
				}
			}
		}
		header("Location: " . getAdminLink(PLUGIN_FOLDER . '/user-expiry/user-expiry-tab.php') . '?page=admin&tab=expiry&applied=' . $msg);
		exit();
	}
}

printAdminHeader('admin');
echo '</head>' . "\n";
?>

<body>
	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<?php
			if (isset($_GET['applied'])) {
				$msg = sanitize($_GET['applied']);
				if ($msg) {
					echo "<div class=\"errorbox space\">";
					echo "<h2>" . $msg . "</h2>";
					echo "</div>";
				} else {
					echo '<div class="messagebox fade-message">';
					echo "<h2>" . gettext('Processed') . "</h2>";
					echo '</div>';
				}
			}
			$subtab = getCurrentTab();
			npgFilters::apply('admin_note', 'admin', $subtab);
			echo '<h1>' . gettext('User expiry') . '</h1>';
			?>
			<div id="tab_users" class="tabbox">
				<?php
				$groups = array();
				?>
				<p>
					<?php echo gettext("Manage user expiry."); ?>
				</p>
				<form action="?action=expiry&tab=expiry" class="dirtylistening" onReset="setClean('userExpiry_form');" id="userExpiry_form" method="post" autocomplete="off" >
					<?php XSRFToken('expiry'); ?>
					<span class="buttons">
						<button type="submit">
							<?php echo CHECKMARK_GREEN; ?>
							<strong><?php echo gettext("Apply"); ?></strong>
						</button>
						<button type="reset">
							<?php echo CLOCKWISE_OPEN_CIRCLE_ARROW_RED; ?>
							<strong><?php echo gettext("Reset"); ?></strong>
						</button>
						<div class="floatright">
							<a href="<?php echo getAdminLink('admin-tabs/options.php'); ?>?page=options&amp;tab=plugin&amp;single=user-expiry#user-expiry">
								<?php echo OPTIONS_ICON; ?>
								<strong><?php echo gettext('Options') ?></strong>
							</a>
						</div>
					</span>
					<br class="clearall">
					<br />
					<ul class="fullchecklist">
						<?php
						foreach ($adminordered as $user) {
							?>
							<li>
								<?php
								$checked_delete = $checked_disable = $checked_renew = $dup = '';
								$expires = $user['expires'];
								$expires_display = date('Y-m-d', $expires);
								$loggedin = $user['loggedin'];
								if (empty($loggedin)) {
									$loggedin = gettext('never');
								} else {
									$loggedin = date('Y-m-d', strtotime($loggedin));
								}
								if ($subscription) {
									if ($expires < $now) {
										$expires_display = sprintf(gettext('Expired:%s; '), '<span style="color:red" >' . $expires_display . '</span>');
									} else {
										if ($expires < $warnInterval) {
											$expires_display = sprintf(gettext('Expires:%s; '), '<span style="color:orange" class="tooltip" title="' . gettext('Expires soon') . '">' . $expires_display . '</span>');
										} else {
											$expires_display = sprintf(gettext('Expires:%s; '), $expires_display);
										}
									}
								} else {
									$expires_display = $r3 = $r4 = '';
								}
								$userid = html_encode($user['user']);
								if ($user['valid'] == 2) {
									$hits = 0;
									foreach ($adminordered as $tuser) {
										if ($tuser['user'] == $user['user']) {
											$hits++;
										}
									}
									if ($hits > 1) {
										$checked_delete = ' checked="checked"';
										$checked_disable = ' disabled="disabled"';
										$expires_display = ' <span style="color:red">' . gettext('User id has been preempted') . '</span> ';
									}
								}
								$id = postIndexEncode($user['id']);
								?>
								<label class="displayinline">
									<?php echo WASTEBASKET; ?><input type="radio" name="r_<?php echo $id; ?>" value="delete"<?php echo $checked_delete; ?> title="<?php echo gettext('Delete'); ?>" />
								</label>&nbsp;&nbsp;
								<label class="displayinline">
									<?php
									if ($user['valid'] == 2) {
										echo LOCK;
										?>
										<input type="radio" name="r_<?php echo $id; ?>" value="enable"<?php echo $checked_disable; ?> title="<?php echo gettext('Enable'); ?>" />
										<?php
										$userid = '<span style="color: darkred;">' . $userid . '</span>';
									} else {
										echo LOCK_OPEN;
										?>
										<input type="radio" name="r_<?php echo $id; ?>" value="disable"<?php echo $checked_disable; ?> title="<?php echo gettext('Disable'); ?>" />
										<?php
									}
									?>
								</label>&nbsp;&nbsp;
								<?php
								if (getOption('user_expiry_password_cycle')) {
									?>
									<label class="displayinline">
										<?php echo CLOCKWISE_OPEN_CIRCLE_ARROW_RED; ?>
										<input type="radio" name="r_<?php echo $id; ?>" value="force"<?php echo $checked_delete; ?> title="<?php echo gettext('Force password change'); ?>" />
									</label>&nbsp;&nbsp;
									<?php
								}
								if ($subscription) {
									?>
									<label class="displayinline">
										<?php
										echo CLOCKWISE_OPEN_CIRCLE_ARROW_GREEN;
										?>
										</span> <input type="radio" name="r_<?php echo $id; ?>" value="renew"<?php echo $checked_renew . $checked_disable; ?> title="<?php echo gettext('Renew'); ?>" />
									</label>&nbsp;&nbsp;
									<label class="displayinline">
										<?php
										if ($user['email']) {
											$title = gettext('Email renewal link');
										} else {
											$title = gettext('User has no email address');
											$checked_disable = ' disabled="disabled"';
										}
										echo ENVELOPE;
										?>
										<input type="radio" name="r_<?php echo $id; ?>" value="revalidate"<?php echo $checked_disable; ?> title="<?php echo $title; ?>" />
									</label>&nbsp;&nbsp;
									<?php
								}
								printf(gettext('<strong>%1$s</strong> (%2$slast logon:%3$s)'), $userid, $expires_display, $loggedin);
								?>
							</li>
							<?php
						}
						?>
					</ul>
					<?php
					echo WASTEBASKET . ' ' . gettext('Remove') . '&nbsp;&nbsp;';
					echo LOCK . ' ' . gettext('Disabled') . '&nbsp;&nbsp;';
					echo LOCK_OPEN . gettext('Enabled') . '&nbsp;&nbsp;';
					if (getOption('user_expiry_password_cycle')) {
						echo CLOCKWISE_OPEN_CIRCLE_ARROW_RED . ' ' . gettext('Force password renewal') . '&nbsp;&nbsp;';
					}
					if ($subscription) {
						echo CLOCKWISE_OPEN_CIRCLE_ARROW_GREEN . ' ' . gettext('Renew') . '&nbsp;&nbsp;';
						echo ENVELOPE . ' ' . gettext('Email renewal link');
					}
					?>
					<p class="buttons">
						<button type="submit">
							<?php echo CHECKMARK_GREEN; ?>
							<strong><?php echo gettext("Apply"); ?></strong>
						</button>
						<button type="reset">
							<?php echo CLOCKWISE_OPEN_CIRCLE_ARROW_RED; ?>
							<strong><?php echo gettext("Reset"); ?></strong>
						</button>
					</p>
					<br class="clearall">
				</form>
			</div>
		</div>
		<?php printAdminFooter(); ?>
	</div>
</body>
</html>