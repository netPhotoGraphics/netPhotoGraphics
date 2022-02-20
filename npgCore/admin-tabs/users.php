<?php
/**
 * provides the Options tab of admin
 *
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 */
// force UTF-8 Ø

define('OFFSET_PATH', 1);
define('ADMIN_USERS_STEP', 5);

require_once(dirname(__DIR__) . '/admin-globals.php');

admin_securityChecks(USER_RIGHTS, currentRelativeURL());

if (isset($_GET['selection'])) {
	define('USERS_PER_PAGE', max(1, sanitize_numeric($_GET['selection'])));
	setNPGCookie('usersTab_userCount', USERS_PER_PAGE, 3600 * 24 * 365 * 10);
} else {
	if ($s = sanitize_numeric(getNPGCookie('usersTab_userCount'))) {
		define('USERS_PER_PAGE', $s);
	} else {
		define('USERS_PER_PAGE', 10);
	}
}

$stuff = array('rights' => gettext('Rights'), 'objects' => gettext('Managed objects'));
$stuff = array_merge($stuff, npgFilters::apply('mass_edit_selector', array(), 'users'));
asort($stuff, SORT_NATURAL | SORT_FLAG_CASE);

if (isset($_GET['ticket'])) {
	$ticket = '&ticket=' . sanitize($_GET['ticket']);
	if (isset($_GET['user'])) {
		$ticket .= sanitize($_GET['user']);
	}
} else {
	$ticket = '';
}

$newuser = array();
if (isset($_REQUEST['show']) && is_array($_REQUEST['show'])) {
	$showset = $_REQUEST['show'];
} else {
	$showset = array();
}


if (isset($_GET['subpage'])) {
	$subpage = sanitize($_GET['subpage']);
} else {
	if (isset($_POST['subpage'])) {
		$subpage = sanitize_numeric($_POST['subpage']);
	} else {
		$subpage = 0;
	}
}
if ($subpage !== 0) {
	$ticket .= '&subpage=' . $subpage;
}

if (!isset($_GET['page'])) {
	$_GET['page'] = 'admin';
}
$_current_tab = sanitize($_GET['page'], 3);

/* handle posts */
if (isset($_GET['action'])) {
	if (($action = sanitize($_GET['action'])) != 'saveoptions') {
		admin_securityChecks(ADMIN_RIGHTS, currentRelativeURL());
	}

	$themeswitch = false;
	switch ($action) {
		case 'viewadmin':
			XSRFdefender('viewadmin');
			$adminobj = npg_Authority::newAdministrator(sanitize($_GET['adminuser']), 1);
			npg_Authority::logUser($adminobj);
			header("Location: " . getAdminLink('admin.php'));
			exit();

		case 'migrate_rights':
			XSRFdefender('migrate_rights');
			if (isset($_GET['revert'])) {
				$v = getOption('libauth_version') - 1;
			} else {
				$v = npg_Authority::$supports_version;
			}
			if ($_authority->migrateAuth($v)) {
				$notify = '';
			} else {
				$notify = '&migration_error';
			}
			header("Location: " . getAdminLink('admin-tabs/users.php') . '?page=admin&subpage=' . $subpage . $notify);
			exit();

		case 'deleteadmin':
			XSRFdefender('deleteadmin');
			$adminobj = npg_Authority::newAdministrator(sanitize($_GET['adminuser']), 1);
			npgFilters::apply('save_user_complete', '', $adminobj, 'delete');
			$adminobj->remove();
			header('Location: ' . getAdminLink('admin-tabs/users.php') . '?page=admin&deleted&subpage=' . $subpage);
			exit();
			break;
		case 'saveoptions':
			XSRFdefender('saveadmin');
			$notify = '?saved';
			$returntab = $msg = '';
			unset($_SESSION['notify']);
			if (isset($_POST['newuser'])) {
				$newuserid = $_POST['newuser'];
			} else {
				$newuserid = NULL;
			}
			if (isset($_POST['saveadminoptions'])) {
				if (isset($_POST['checkForPostTruncation'])) {
					$userlist = $_POST['user'];
					if (isset($_POST['alter_enabled']) || sanitize_numeric($_POST['totaladmins']) > 1 ||
									trim($userlist[0]['adminuser']) != $_current_admin_obj->getUser() ||
									$newuserid === 0) {
						if (!$_current_admin_obj->reset) {
							if (isset($_POST['alter_enabled'])) {
								$rights = ADMIN_RIGHTS;
							} else {
								$rights = USER_RIGHTS;
							}
							admin_securityChecks($rights, currentRelativeURL());
						}
					}

					$alter = isset($_POST['alter_enabled']);
					$nouser = true;
					$returntab = $newuser = false;

					for ($i = 0; $i < sanitize_numeric($_POST['totaladmins']); $i++) {
						$error = false;
						$userobj = NULL;
						$msg = '';
						$pass = trim(sanitize($userlist[$i]['pass'], 0));
						$user = trim(sanitize($userlist[$i]['adminuser']));
						if (empty($user) && !empty($pass)) {
							$_SESSION['notify']['nouser'] = gettext('No user ID was supplied.');
							$error = true;
						}
						if (!empty($user)) {
							$nouser = false;
							if ($i == $newuserid) {
								$newuser = $user;
								$userobj = $_authority->getAnAdmin(array('`user`=' => $user, '`valid`>' => 0));
								if (is_object($userobj)) {
									$_SESSION['notify'][] = sprintf(gettext('%1$s already exists.'), $user);
									break;
								} else {
									$what = 'new';
									$userobj = npg_Authority::newAdministrator('');
									$userobj->setUser($user);
								}
							} else {
								$what = 'update';
								$userobj = npg_Authority::newAdministrator($user);
							}
							if (isset($userlist[$i]['policyAck'])) {
								//possible states: not present--policy acknowledgement not active
								//                 1--force acknowledgement
								//                 2--clear acknowledgement
								$userobj->setPolicyACK($v = $userlist[$i]['policyAck'] & 1);
								npgFilters::apply('policy_ack', true, 'PolicyACK', $v, $userobj->getUser());
							}
							if (isset($userlist[$i]['admin_name'])) {
								$admin_n = trim(sanitize($userlist[$i]['admin_name']));
								$userobj->setName($admin_n);
							}
							if (isset($userlist[$i]['admin_email'])) {
								$admin_e = strtolower(trim(sanitize($userlist[$i]['admin_email'])));
								if ($userobj->getEmail() != $admin_e) {
									if (npgFunctions::isValidEmail($admin_e)) {
										$sql = 'SELECT `user`, `email` FROM' . prefix('administrators') . ' WHERE `email`=' . db_quote($admin_e) . ' AND `user`<>' . db_quote($user);
										$result = query_single_row($sql);
										if ($result) {
											$msg = sprintf(gettext('%1$s: email address %2$s is already used by %3$s.'), $userobj->getUser(), $admin_e, $result['user']);
										}
									} else {
										if ($admin_e) { //	invalid email address
											$msg = sprintf(gettext('%1$s: %2$s is not a valid e-mail address.'), $userobj->getUser(), $admin_e);
										}
									}
								}
								if (empty($msg)) {
									$userobj->setEmail($admin_e);
								} else {
									$_SESSION['notify'][] = $msg;
									$error = true;
								}
							}

							if (empty($pass)) {
								if ($newuser || isset($userlist[$i]['passrequired']) && $userlist[$i]['passrequired']) {
									$_SESSION['notify'][] = sprintf(gettext('%s password may not be empty!'), $admin_n);
									$error = true;
								}
							} else {
								if (isset($userlist[$i]['disclose_password']) && $userlist[$i]['disclose_password'] == 'on') {
									$pass2 = $pass;
								} else {
									if (isset($userlist[$i]['pass_r'])) {
										$pass2 = trim(sanitize($userlist[$i]['pass_r'], 0));
									} else {
										$pass2 = NULL;
									}
								}
								if ($pass == $pass2) {
									$pass2 = $userobj->getPass($pass);
									if ($msg = npgFilters::apply('can_set_user_password', false, $pass, $userobj)) {
										$_SESSION['notify'][] = $msg;
									} else {
										$userobj->setPass($pass);
									}
								} else {
									$_SESSION['notify'][] = sprintf(gettext('%1$s: Password mismatch.'), $userobj->getUser());
									$error = true;
								}
							}
							if (isset($userlist[$i]['challengephrase'])) {
								$challenge = sanitize($userlist[$i]['challengephrase']);
								$response = sanitize($userlist[$i]['challengeresponse']);
								$info = $userobj->getChallengePhraseInfo();
								if ($challenge != $info['challenge'] || $response != $info['response']) {
									$userobj->setChallengePhraseInfo($challenge, $response);
								}
							}
							$lang = sanitize($userlist[$i]['admin_language'], 3);
							if ($lang != $userobj->getLanguage()) {
								$userobj->setLanguage($lang);
								clearNPGCookie('dynamic_locale');
							}
							$rights = 0;
							if ($alter && (!isset($userlist[$i]['group']) || $userlist[$i]['group'] == array(''))) {
								if (isset($userlist[$i]['rightsenabled'])) {
									$oldrights = $userobj->getRights();
									$rights = processRights($i);
									if ($rights != $oldrights) {
										$userobj->setRights($rights | NO_RIGHTS);
									}
								}
								$oldobjects = $userobj->getObjects();
								$oldrights = $rights;
								$objects = processManagedObjects($i, $rights);
								if (!editSelectorEnabled('user_edit_objects') || compareObjects($objects, $oldobjects)) {
									$userobj->setObjects(NULL); //	indicates no change
								} else {
									$userobj->setObjects($objects);
								}
								if ($rights != $oldrights && editSelectorEnabled('user_edit_rights')) {
									$userobj->setRights($rights | NO_RIGHTS);
								}
							} else {
								$userobj->setObjects($oldobjects = NULL); // indicates no change
							}
							if (isset($userlist[$i]['delinkAlbum'])) {
								$userobj->setAlbum(NULL);
							}
							if (isset($userlist[$i]['createAlbum'])) {
								$userobj->createPrimealbum();
							}
							npgFilters::apply('save_admin_data', $userobj, $i, $alter);

							if (!($error && $_current_admin_obj->getID() <= 0)) { //	new install and password problems, leave with no admin
								$userobj->transient = false;
								$saved = $userobj->save();
								if ($saved == 1) {
									$msg = npgFilters::apply('save_user_complete', $msg, $userobj, $what);
									$returntab .= '&show[]=' . $user;
								}
								if (!$_current_admin_obj->getID()) {
									// avoid the logon screen for first user established
									npg_Authority::logUser($userobj);
								}
							}
						}
					}
					if ($nouser) {
						$_SESSION['notify']['nouser'] = gettext('No user ID was supplied.');
					}
				} else {
					$notify = '?post_error';
				}
			}
			break;
	}

	if (isset($returntab)) { //	if "nothing happened" just fall thru. (Open Admib situation.)
		$returntab .= "&page=admin&tab=users";
		if (!empty($newuser)) {
			$returntab .= '&show[]=' . $newuser;
		}

		if (isset($_SESSION['notify'])) {
			$notify = '?mismatch';
		}

		header("Location: " . $notify . $returntab . $ticket);
		exit();
	}
}
$refresh = false;

if ($_current_admin_obj->reset) {
	if (isset($_GET['saved'])) {
		$refresh = '<meta http-equiv="refresh" content="3; url=admin.php" />';
	}
}

if (!$_current_admin_obj && $_current_admin_obj->getID()) {
	header("HTTP/1.0 302 Found");
	header("Status: 302 Found");
	header('Location: ' . getAdminLink('admin.php'));
	exit();
}

printAdminHeader($_current_tab);
echo $refresh;
?>
<script type='text/javascript'>
	var visible = false;
	function getVisible(id, category, show, hide) {
		prefix = '#' + category + '-' + id + ' ';
		v = $(prefix + '.' + category + 'extrainfo').is(':hidden');
		if (v) {
			$('#toggle_' + id).prop('title', hide);
		} else {
			$('#toggle_' + id).prop('title', show);
		}
		return v;
	}
</script>
<?php npg_Authority::printPasswordFormJS(); ?>
</head>
<body>
	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<?php
			if ($_current_admin_obj->exists && $_current_admin_obj->reset && !$refresh) {
				echo "<div class=\"errorbox space\">";
				echo "<h2>" . gettext("Password reset request.") . "</h2>";
				echo "</div>";
			}
			npgFilters::apply('admin_note', 'admin', 'users');
			if (npg_loggedin(ADMIN_RIGHTS) && !$_current_admin_obj->reset || !$_current_admin_obj->getID()) {
				echo '<h1>' . gettext('Users') . '</h1>';
				$alterrights = false;
			} else {
				echo '<h1>' . gettext('Profile') . '</h1>';
				$alterrights = ' disabled="disabled"';
			}
			?>
			<div id="container">
				<?php
				if (isset($_GET['post_error'])) {
					echo '<div class="errorbox">';
					echo "<h2>" . gettext('Error') . "</h2>";
					echo gettext('The form submission is incomplete. Perhaps the form size exceeds configured server or browser limits.');
					echo '</div>';
				}
				if (isset($_GET['saved'])) {
					echo '<div class="messagebox fade-message">';
					echo "<h2>" . gettext("Saved") . "</h2>";
					echo '</div>';
				}
				if (isset($_GET['sent'])) {
					echo '<div class="messagebox fade-message">';
					echo "<h2>" . gettext("email sent") . "</h2>";
					echo '</div>';
				}
				if (isset($_GET['showgroup'])) {
					$showgroup = sanitize($_GET['showgroup'], 3);
				} else {
					$showgroup = '';
				}
				$stamp = time() - getOption('online_persistance') * 60;
				?>
				<?php
				global $_authority;
				?>
				<div id="tab_admin" class="tabbox">
					<?php
					$pages = 0;
					$clearPass = false;
					if (!$_current_admin_obj->getID() && $_current_admin_obj->reset) {
						$clearPass = true;
					}
					$alladmins = $seenGroups = $rangeset = array();
					$nogroup = $pending = false;
					if (npg_loggedin(ADMIN_RIGHTS) && !$_current_admin_obj->reset || !$_current_admin_obj->getID()) {
						$admins = $_authority->getAdministrators('allusers');
						foreach ($admins as $key => $user) {
							$alladmins[] = $user['user'];
							if ($user['valid'] > 1) {
								unset($admins[$key]);
							} else {
								if (empty($user['group'])) {
									$nogroup = true;
								} else {
									$groups = explode(',', $user['group']);
									$seenGroups = array_merge($seenGroups, $groups);
								}
								if ($user['rights'] == 0) {
									$pending = true;
								}
							}
						}
						$totalUsers = count($admins);

						$seenGroups = array_unique($seenGroups);
						if (empty($admins) || !$_current_admin_obj->getID()) {
							$rights = ALL_RIGHTS;
							$groupname = 'administrators';
							$showset = array('');
						} else {
							if (!empty($showgroup)) {
								foreach ($admins as $key => $user) {
									switch ($showgroup) {
										case '@': //	online users
											if (isset($user['lastaccess']) && $user['lastaccess'] < $stamp) {
												unset($admins[$key]);
											}
											break;
										case '*':
											if ($user['rights'] != 0) {
												unset($admins[$key]);
											}
											break;
										case '$':
											if (!empty($user['group'])) {
												unset($admins[$key]);
											}
											break;
										default:
											$hisgroups = explode(',', $user['group']);
											if (!in_array($showgroup, $hisgroups)) {
												unset($admins[$key]);
											}
											break;
									}
								}
							}
							$admins = sortMultiArray($admins, 'user', false, true, true);
							$rights = DEFAULT_RIGHTS;
							$groupname = 'default';
							$pagination = array();
							foreach ($admins as $admin) {
								$pagination[] = $admin['user'];
							}
							$rangeset = getPageSelector($pagination, USERS_PER_PAGE);
						}
						if ($showgroup != '@') {
							$newuser = array('id' => -1, 'user' => '', 'pass' => '', 'passhash' => getOption('strong_hash'), 'name' => '', 'email' => '', 'rights' => $rights, 'custom_data' => NULL, 'valid' => 1, 'group' => $groupname);
						}
					} else {
						if ($_current_admin_obj) {
							$admins = array($_current_admin_obj->getUser() => $_current_admin_obj->getData());
							$showset = array($_current_admin_obj->getUser());
						} else {
							$admins = $showset = array();
						}
					}

					$max = floor((count($admins) - 1) / USERS_PER_PAGE);
					if ($subpage > $max) {
						$subpage = $max;
					}
					$userlist = array_slice($admins, $subpage * USERS_PER_PAGE, USERS_PER_PAGE);
					if (isset($_GET['user'])) {
						$user = sanitize($_GET['user']);
						foreach ($admins as $u) {
							if ($u['user'] == $user && $u['valid'] == 1) {
								$userlist = array($u['id'] => $u);
								$newuser = NULL;
								break;
							}
						}
					}
					if (!$showgroup && count($userlist) == 1) {
						$u = reset($userlist);
						$showset = array($u['user']);
					}
					if (isset($_GET['deleted'])) {
						echo '<div class="messagebox fade-message">';
						echo "<h2>Deleted</h2>";
						echo '</div>';
					}
					if (isset($_GET['migration_error'])) {
						echo '<div class="errorbox fade-message">';
						echo "<h2>" . gettext("Rights migration failed.") . "</h2>";
						echo '</div>';
					}
					if (isset($_GET['exists'])) {
						echo '<div class="errorbox fade-message">';
						echo "<h2>" . gettext("User id already used.") . "</h2>";
						echo '</div>';
					}
					if (isset($_GET['mismatch'])) {
						echo '<div class="errorbox fade-message">';
						foreach ($_SESSION['notify'] as $msg) {
							echo "<h2>" . html_encode($msg) . "</h2>";
						}
						unset($_SESSION['notify']);
						echo '</div>';
					}
					if (isset($_GET['badurl'])) {
						echo '<div class="errorbox fade-message">';
						echo "<h2>" . gettext("Your Website URL is not valid") . "</h2>";
						echo '</div>';
					}
					?>
					<script type="text/javascript">
						function languageChange(id, lang) {
							var oldid = '#' + $('#admin_language_' + id).val() + '_' + id;
							var newid = '#' + lang + '_' + id;
							$(oldid).attr('class', '');
							if (oldid == newid) {
								$('#admin_language_' + id).val('');
							} else {
								$(newid).attr('class', 'currentLanguage');
								$('#admin_language_' + id).val(lang);
							}
						}
						function closePasswords() {
							$('.disclose_password').each(function () {
								if ($(this).prop('checked')) {
									id = $(this).attr('id').replace('disclose_password', '');
									togglePassword(id);
								}
							});
						}
					</script>
					<?php
					printEditSelector('user_edit', $stuff, "toggleExtraInfo('', 'user', true);");
					$count = count($admins);
					if ($count > ADMIN_USERS_STEP) {
						$numsteps = ceil(min(100, $count) / ADMIN_USERS_STEP);
						if ($numsteps) {
							?>
							<?php
							$steps = array();
							for ($i = 1; $i <= $numsteps; $i++) {
								$steps[] = $i * ADMIN_USERS_STEP;
							}
							printEditDropdown('userinfo', $steps, USERS_PER_PAGE, '&amp;showgroup=' . $showgroup);
						}
					}
					?>
					<br style="clear:both"/>

					<form class="dirtylistening" onReset="closePasswords();
							setClean('user_form');" id="user_form" action="?action=saveoptions<?php echo str_replace('&', '&amp;', $ticket); ?>" method="post" autocomplete="off" onsubmit="return checkNewuser();" >
								<?php XSRFToken('saveadmin'); ?>
						<input type="hidden" name="saveadminoptions" value="yes" />
						<input type="hidden" name="subpage" value="<?php echo $subpage; ?>" />
						<?php
						if (empty($alterrights)) {
							?>
							<input type="hidden" name="alter_enabled" value="1" />
							<?php
						}
						?>
						<p>
							<?php
							applyButton();
							resetButton();
							?>
						</p>

						<table class="unbordered"> <!-- main table -->
							<tr>
								<td style="width: 30%;">
									<?php
									if ($showgroup || count($userlist) != 1) {
										?>
										<span class="nowrap" style="font-weight: normal;">
											<a onclick="toggleExtraInfo('', 'user', true);"><?php echo gettext('Expand all'); ?></a>
											|
											<a onclick="toggleExtraInfo('', 'user', false);"><?php echo gettext('Collapse all'); ?></a>
										</span>
										<?php
									}
									?>
								</td>
								<td>
									<?php
									if ($showgroup || $pending || count($seenGroups) > 0) {
										echo gettext('show');
										?>
										<select name="showgroup" id="showgroup" class="ignoredirty" onchange="launchScript('<?php echo getAdminLink('admin-tabs/users.php'); ?>', ['showgroup=' + $('#showgroup').val()]);" >
											<option value=""<?php if (!$showgroup) echo ' selected="selected"'; ?>><?php echo gettext('all'); ?></option>
											<?php
											if ($totalUsers > 1) {
												?>
												<option value = "@"<?php if ($showgroup == '@') echo ' selected="selected"'; ?>><?php echo gettext('online'); ?></option>
												<?php
											}
											if ($pending) {
												?>
												<option value = "*"<?php if ($showgroup == '*') echo ' selected="selected"'; ?>><?php echo gettext('pending verification'); ?></option>
												<?php
											}
											if (!empty($seenGroups) && extensionEnabled('user_groups')) {
												if ($nogroup) {
													?>
													<option value="$"<?php if ($showgroup == '$') echo ' selected="selected"'; ?>><?php echo gettext('no group'); ?></option>
													<?php
												}
												foreach ($seenGroups as $group) {
													?>
													<option value="<?php echo $group; ?>"<?php if ($showgroup == $group) echo ' selected="selected"'; ?>><?php printf('%s group', $group); ?></option>
													<?php
												}
											}
											?>
										</select>
										<?php
									}
									?>
								</td>
								<td>
									<span class="floatright padded">
										<?php printPageSelector($subpage, $rangeset, 'admin-tabs/users.php', array('page' => 'users')); ?>
									</span>
									<br clear="all">
									<br />
								</td>
							</tr>

							<?php
							$id = 0;
							$albumlist = array();
							foreach ($_gallery->getAlbums() as $folder) {
								$alb = newAlbum($folder);
								$name = $alb->getTitle();
								$albumlist[$name] = $folder;
							}
							$background = '';
							$showlist = array();
							if (!empty($newuser)) {
								$userlist[-1] = $newuser;
							}

							foreach ($userlist as $key => $user) {
								$ismaster = false;
								$local_alterrights = $alterrights;
								$userid = $user['user'];

								$current = in_array($userid, $showset);
								if ($userid == $_current_admin_obj->getuser()) {
									$userobj = $_current_admin_obj;
								} else {
									$userobj = npg_Authority::newAdministrator($userid, 1, false);
									if ($userid && $userobj->transient) {
										continue;
									}
								}

								list($oldHashWeight, $oldHashName) = npg_Authority::getHashAlgorithm($user);

								if (empty($userid)) {
									$userobj->setGroup($user['group']);
									$userobj->setRights($user['rights']);
									$userobj->setValid(1);
								}
								$groupname = $userobj->getGroup();
								if ($pending = $userobj->getRights() == 0) {
									$master = '(<em>' . gettext('pending verification') . '</em>)';
								} else {
									$master = '&nbsp;';
								}
								if ($userobj->master && $_current_admin_obj->getID()) {
									if (npg_loggedin(ADMIN_RIGHTS)) {
										$master = "(<em>" . gettext("Master") . "</em>)";
										$userobj->setRights($userobj->getRights() | ADMIN_RIGHTS);
										$ismaster = true;
									}
								}
								if ($background) {
									$background = "";
								} else {
									$background = "background-color:#f0f4f5;";
								}
								if ($_current_admin_obj->reset) {
									$custom_row = NULL;
								} else {
									?>
									<!-- apply alterrights filter -->
									<?php
									$local_alterrights = npgFilters::apply('admin_alterrights', $local_alterrights, $userobj);
									?>
									<!-- apply admin_custom_data filter -->
									<?php
									$custom_row = npgFilters::apply('edit_admin_custom', '', $userobj, $id, $background, $current, $local_alterrights);
								}
								?>
								<!-- finished with filters -->
								<tr>
									<td colspan="100%" style="margin: 0pt; padding: 0pt;<?php echo $background; ?>">
										<table class="unbordered" id='user-<?php echo $id; ?>'>
											<tr>
												<td style="margin-top: 0px; width: 48en;line-height: 2em;<?php echo $background; ?>" valign="top">
													<?php
													if (empty($userid)) {
														$displaytitle = gettext("Show details");
														$hidetitle = gettext("Hide details");
													} else {
														$displaytitle = sprintf(gettext('Show details for user %s'), $userid);
														$hidetitle = sprintf(gettext('Hide details for user %s'), $userid);
													}
													?>
													<a id="toggle_<?php echo $id; ?>" onclick="visible = getVisible('<?php echo $id; ?>', 'user', '<?php echo $displaytitle; ?>', '<?php echo $hidetitle; ?>');
																$('#show_<?php echo $id; ?>').val(visible);
																toggleExtraInfo('<?php echo $id; ?>', 'user', visible);" title="<?php echo $displaytitle; ?>" >
															 <?php
															 if (empty($userid)) {
																 ?>
															<input type="hidden" name="newuser" value="<?php echo $id ?>" />

															<em><?php echo gettext("New User"); ?></em>
															<input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" id="adminuser<?php echo $id; ?>" name="user[<?php echo $id; ?>][adminuser]" value=""
																		 onclick="toggleExtraInfo('<?php echo $id; ?>', 'user', visible);
																						 $('#adminuser<?php echo $id; ?>').focus();" />

															<?php
														} else {
															?>
															<input type="hidden" id="adminuser<?php echo $id; ?>" name="user[<?php echo $id; ?>][adminuser]" value="<?php echo $userid ?>" />
															<?php
															if (empty($alterrights)) {
																echo '<strong>' . $userid . '</strong> ';
																if (!empty($userid)) {
																	echo $master;
																}
															}
														}
														?>
													</a>

													<?php
													if (!$alterrights || !$userobj->getID()) {
														if ($pending) {
															?>
															<input type="checkbox" name="user[<?php echo $id ?>][confirmed]" value="<?php
															echo NO_RIGHTS . '"';
															echo $alterrights;
															?>" />
																		 <?php echo gettext("Authenticate user"); ?>
																		 <?php
																	 } else {
																		 ?>
															<input type = "hidden" name="user[<?php echo $id ?>][confirmed]"	value="<?php echo NO_RIGHTS; ?>" />
															<?php
														}
														if ($oldHashWeight < PASSWORD_FUNCTION_DEFAULT) {
															$defaultHash = array_search(PASSWORD_FUNCTION_DEFAULT, npg_Authority::$hashList);
															if ($oldHashName !== $hashName = strip_tags($oldHashName)) {

																echo '<span title="' . sprintf(gettext('User\'s password is encrypted with the deprecated %1$s password hashing algorithm.'), $hashName) . '">' . WARNING_SIGN_ORANGE . '</span>';
															} else {
																echo '<span title="' . sprintf(gettext('User\'s password is encrypted with the %1$s password hashing algorithm which is less secure than %2$s.'), $oldHashName, $defaultHash) . '">' . WARNING_SIGN_ORANGE . '</span>';
															}
														}
														?>
													</td>
													<td style="margin-top: 0px;<?php echo $background; ?>" valign="top">
														<?php
														if (!empty($userid) && $totalUsers > 1) {
															$msg = gettext('Are you sure you want to delete this user?');
															if ($ismaster) {
																$msg .= ' ' . gettext('This is the master user account. If you delete it another user will be promoted to master user.');
															}
															?>
															<span class="floatright">
																<?php
																if (!$pending && $_current_admin_obj && $user['user'] != $_current_admin_obj->getUser()) {
																	?>
																	<a href="<?php echo getAdminLink('admin-tabs/users.php'); ?>?action=viewadmin&adminuser=<?php echo addslashes($user['user']); ?>&amp;XSRFToken=<?php echo getXSRFToken('viewadmin') ?>"
																		 title="<?php printf(gettext('Log on as %s.'), $user['user']); ?>">
																			 <?php echo BULLSEYE_BLUE; ?>
																	</a>
																	<?php
																} else {
																	echo PLACEHOLDER_ICON;
																}
																?>
															</span>
															<span class="floatright">
																<a href="javascript:if(confirm(<?php echo "'" . js_encode($msg) . "'"; ?>)) { window.location='?action=deleteadmin&adminuser=<?php echo addslashes($user['user']); ?>&amp;subpage=<?php echo $subpage; ?>&amp;XSRFToken=<?php echo getXSRFToken('deleteadmin') ?>'; }"
																	 title="<?php echo gettext('Delete this user.'); ?>" style="color: #c33;">
																		 <?php echo WASTEBASKET; ?>
																</a>
															</span>
															<?php
															if (isset($user['lastaccess'])) {
																?>
																<div class="floatright">
																	<?php
																	$online = $user['lastaccess'];
																	if ($online > $stamp) {
																		echo '<span class="lognotice">' . gettext('online') . '</span>';
																	} else if ($online) {
																		printf('Last visited %1$s', formattedDate(DATE_FORMAT, $online));
																	}
																	?>
																	&nbsp;&nbsp;&nbsp;&nbsp;
																	</span>
																	<?php
																}
																?>
															</div>
															<?php
														}
														?>
														&nbsp;
													</td>
													<?php
												} else {
													?>
													<td style="margin-top: 0px;<?php echo $background; ?>" valign="top"></td>
													<?php
												}
												?>
											</tr>
											<?php
											$no_change = array();
											if (!npg_loggedin(ADMIN_RIGHTS) && !$_current_admin_obj->reset) {
												?>
												<tr class="userextrainfo<?php if (!$current) echo ' hidden'; ?>">
													<td <?php if (!empty($background)) echo " style=\"$background\""; ?> colspan="100%">
														<p class="notebox">
															<?php echo gettext('<strong>Note:</strong> You must have ADMIN rights to alter anything but your personal information.'); ?>
														</p>
													</td>
												</tr>
												<?php
											}
											if (getOption('GDPR_acknowledge') || extensionEnabled('GDPR_required')) {
												?>
												<tr class="userextrainfo <?php if (!$current) echo ' hidden'; ?>">
													<td <?php if (!empty($background)) echo " style=\"$background\""; ?> colspan="100%">
														<div class="user_left">
															<p>
																<?php
																if ($set = $userobj->getPolicyAck()) {
																	echo gettext('Usage policy has been acknowledged.');
																	?>
																	<span style="float: right; padding-right: 15px;">
																		<?php echo gettext('Clear'); ?>
																		<input type="checkbox" name="user[<?php echo $id ?>][policyAck]" value="2">
																	</span>
																	<?php
																} else {
																	echo '<span style="color: red;">' . gettext('Usage policy has not been acknowledged.') . '</span>';
																	?>
																	<span style="float: right; padding-right: 15px;">
																		<?php echo gettext('Set'); ?>
																		<input type="checkbox" name="user[<?php echo $id ?>][policyAck]" value="1">
																	</span>
																	<?php
																}
																?>
															</p>
														</div>
													</td>
												</tr>
												<?php
											}
											?>
											<tr class="userextrainfo<?php if (!$current) echo ' hidden'; ?>">
												<td <?php if (!empty($background)) echo " style=\"$background\""; ?> valign="top" colspan="100%">
													<div class="user_left">
														<p>
															<?php
															$pad = false;
															$pwd = $userobj->getPass();
															if (!empty($userid) && !$clearPass) {
																if (!empty($pwd)) {
																	$pad = true;
																}
															}
															if (!empty($pwd) && in_array('password', $no_change)) {
																$password_disable = ' disabled="disabled"';
															} else {
																$password_disable = '';
															}



															if ($oldHashName) {
																$oldHashName = '<small> (' . $oldHashName . ')</small>';
															}

															npg_Authority::printPasswordForm($id, $pad, $password_disable, $clearPass, $oldHashName);
															?>
														</p>
														<?php
														if (getOption('challenge_foil_enabled')) {
															if (in_array('challenge_phrase', $no_change)) {
																$_disable = ' disabled="disabled"';
															} else {
																$_disable = '';
															}
															$challenge = $userobj->getChallengePhraseInfo();
															?>
															<p>
																<?php echo gettext('Challenge phrase') ?><br />
																<input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" id="challengephrase-<?php echo $id ?>" name="user[<?php echo $id ?>][challengephrase]" value="<?php echo html_encode($challenge['challenge']); ?>"<?php echo $_disable; ?> />
																<br />
																<?php echo gettext('Challenge response') ?><br />
																<input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" id="<?php echo $id ?>-challengeresponse" name="user[<?php echo $id ?>][challengeresponse]" value="<?php echo html_encode($challenge['response']); ?>"<?php echo $_disable; ?> />

															</p>
															<?php
														}
														?>
														<?php echo gettext("Full name"); ?><br />
														<input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" id="admin_name-<?php echo $id ?>" name="user[<?php echo $id ?>][admin_name]"
																	 value="<?php echo html_encode($userobj->getName()); ?>"<?php if (in_array('name', $no_change)) echo ' disabled="disabled"'; ?> />

														<p>
															<?php echo gettext("Email"); ?><br />
															<input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" id="admin_email-<?php echo $id ?>" name="user[<?php echo $id ?>][admin_email]"
																		 value="<?php echo html_encode($userobj->getEmail()); ?>"<?php if (in_array('email', $no_change)) echo ' disabled="disabled"'; ?> />
														</p>
														<?php
														if ($userobj->getEmail() && npgFilters::has_filter('sendmail')) {
															?>
															<p>
																<?php
																npgButton('button', gettext('e-mail User'), array('buttonLink' => getAdminLink('mailMessage.php') . '?user=' . $userobj->getID()));
																?>
																<br clear="all">
															</p>
															<?php
														}
														$primeAlbum = $userobj->getAlbum();
														if (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
															if (empty($primeAlbum)) {
																if (!($userobj->getRights() & (ADMIN_RIGHTS | MANAGE_ALL_ALBUM_RIGHTS))) {
																	?>
																	<p>
																		<label>
																			<input type="checkbox" name="user[<?php echo $id ?>][createAlbum]" id="createAlbum_<?php echo $id ?>" value="1" <?php echo $alterrights; ?>/>
																			<?php echo gettext('create primary album'); ?>
																		</label>
																	</p>
																	<?php
																}
															} else {
																?>
																<p>
																	<label>
																		<input type="checkbox" name="user[<?php echo $id ?>][delinkAlbum]" id="delinkAlbum_<?php echo $id ?>" value="1" <?php echo $alterrights; ?>/>
																		<?php printf(gettext('delink primary album <strong>%1$s</strong>(<em>%2$s</em>)'), $primeAlbum->getTitle(), $primeAlbum->name); ?>
																	</label>
																</p>
																<p class="notebox">
																	<?php echo gettext('The primary album was created in association with the user. It will be removed if the user is deleted. Delinking the album removes this association.'); ?>
																</p>
																<?php
															}
														}
														$currentValue = $userobj->getLanguage();

														if ($userobj->getlastchangeuser()) {
															?>
															<p>
																<?php
																printf(gettext('Last changed %1$s by %2$s'), $userobj->getLastchange() . '<br />', $userobj->getlastchangeuser());
															}
															?>
														</p>
														<?php ?>
														<p>
															<label for="admin_language_<?php echo $id ?>">
																<?php echo gettext('Language:'); ?>
															</label>
														</p>
														<input type="hidden" name="user[<?php echo $id ?>][admin_language]" id="admin_language_<?php echo $id ?>" value="<?php echo $currentValue; ?>" />
														<ul class="flags" style="margin-left: 0px;">
															<?php
															$languages = i18n::generateLanguageList();
															asort($languages);
															$flags = getLanguageFlags();
															$flags[''] = WEBPATH . '/' . CORE_FOLDER . '/locale/auto.png';
															$c = 0;
															foreach ($languages as $text => $lang) {
																$current = $lang == $currentValue;
																?>
																<li id="<?php echo $lang . '_' . $id; ?>"<?php if ($current) echo ' class="currentLanguage"'; ?>>
																	<a onclick="languageChange('<?php echo $id; ?>', '<?php echo $lang; ?>');" >
																		<img src="<?php echo $flags[$lang]; ?>" alt="<?php echo $text; ?>" title="<?php echo $text; ?>" />
																	</a>
																</li>
																<?php
																$c++;
																if (($c % 7) == 0)
																	echo '<br class="clearall" />';
															}
															?>
														</ul>
													</div>

													<div class="user_right">
														<?php
														printAdminRightsTable($id, $background, $local_alterrights, $userobj->getRights());
														if (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
															$alter_rights = $local_alterrights;
														} else {
															$alter_rights = ' disabled="disabled"';
														}
														if ($ismaster) {
															echo '<p>' . gettext("The <em>master</em> account has full rights to all objects.") . '</p>';
														} else {
															if (is_object($primeAlbum)) {
																$flag = array($primeAlbum->name);
															} else {
																$flag = array();
															}
															printManagedObjects('albums', $albumlist, $alter_rights, $userobj, $id, gettext('user'), $flag);
															if (class_exists('CMS')) {
																if (npg_loggedin(MANAGE_ALL_PAGES_RIGHTS)) {
																	$alter_rights = $local_alterrights;
																} else {
																	$alter_rights = ' disabled="disabled"';
																}
																$pagination = array();
																$pages = $_CMS->getPages(false);
																foreach ($pages as $page) {
																	if (!$page['parentid']) {
																		$pagination[get_language_string($page['title'])] = $page['titlelink'];
																	}
																}
																$newslist = array('"' . gettext('un-categorized') . '"' => '`');
																$categories = $_CMS->getAllCategories(false);
																foreach ($categories as $category) {
																	$newslist[get_language_string($category['title'])] = $category['titlelink'];
																}
																printManagedObjects('news_categories', $newslist, $alter_rights, $userobj, $id, gettext('user'), NULL);
																if (npg_loggedin(MANAGE_ALL_NEWS_RIGHTS)) {
																	$alter_rights = $local_alterrights;
																} else {
																	$alter_rights = ' disabled = "disabled"';
																}
																printManagedObjects('pages', $pagination, $alter_rights, $userobj, $id, gettext('user'), NULL);
															}
														}
														?>
													</div>
													<br class="clearall" />
													<div class="userextrainfo">
														<?php
														if ($custom_row) {
															echo stripTableRows($custom_row);
														}
														?>
													</div>
													<br class="clearall" />
												</td>
											</tr>

										</table> <!-- end individual admin table -->
									</td>
								</tr>
								<?php
								$id++;
							}
							if ($subpage || count($userlist) > 1) {
								?>
								<tr>
									<td colspan="100%">
										<span class="floatright padded">
											<?php printPageSelector($subpage, $rangeset, 'admin-tabs/users.php', array('page' => 'users', 'showgroup' => $showgroup)); ?>
										</span>
									</td>
								</tr>
								<?php
							}
							?>
						</table> <!-- main admin table end -->

						<input type="hidden" name="totaladmins" value="<?php echo $id; ?>" />
						<input type="hidden" name="checkForPostTruncation" value="1" />
						<?php
						if (!$_current_admin_obj->transient) {
							?>
							<p>
								<?php
								applyButton();
								resetButton();
								?>
							</p>
							<?php
						}
						?>
					</form>
					<?php
					if (npg_loggedin(ADMIN_RIGHTS)) {
						if (npg_Authority::getVersion() < npg_Authority::$supports_version) {
							?>
							<br class="clearall" />
							<p class="notebox">
								<?php printf(gettext('The <em>_Authority</em> object supports a higher version of user rights than currently selected. You may wish to migrate the user rights to gain the new functionality this version provides.'), npg_Authority::getVersion(), npg_Authority::$supports_version); ?>
								<br class="clearall" />
								<?php npgButton('button', gettext('Migrate rights'), array('buttonClick' => "launchScript('', ['action=migrate_rights', 'XSRFToken=" . getXSRFToken('migrate_rights') . "']);")); ?>
								<br class="clearall" />
							</p>
							<br class="clearall" />
							<?php
						} else if (npg_Authority::getVersion() > npg_Authority::$preferred_version) {
							?>
							<br class="clearall" />
							<p class="notebox">
								<?php printf(gettext('You may wish to revert the <em>_Authority</em> user rights to version %s for backwards compatibility with prior releases.'), npg_Authority::getVersion() - 1); ?>
								<br class="clearall" />
								<?php npgButton('button', gettext('Revert rights'), array('buttonClick' => "launchScript('', ['action=migrate_rights', 'revert=true', 'XSRFToken=" . getXSRFToken('migrate_rights') . "']);")); ?>
								<br class="clearall" />
							</p>
							<br class="clearall" />
							<?php
						}
					}
					?>
					<script type="text/javascript">
						//<!-- <![CDATA[
						var admins = ["<?php echo implode('","', $alladmins); ?>"];
						function checkNewuser() {
							newuserid = <?php echo ($id - 1); ?>;
							newuser = $('#adminuser' + newuserid).val().replace(/^\s+|\s+$/g, "");
							if (newuser == '')
								return true;
							if (newuser.indexOf('?') >= 0 || newuser.indexOf('&') >= 0 || newuser.indexOf('"') >= 0 || newuser.indexOf('\'') >= 0) {
								alert('<?php echo js_encode(gettext('User names may not contain “?”, “&", or quotation marks.')); ?>');
								return false;
							}
							for (i = 0; i < admins.length; i++) {
								if (admins[i] == newuser) {
									alert(sprintf('<?php echo js_encode(gettext('The user “%s” already exists.')); ?>', newuser));
									return false;
								}
							}
							return true;
						}
						// ]]> -->
					</script>

					<br class="clearall" />

				</div><!-- end of tab_admin div -->

			</div><!-- end of container -->
		</div><!-- end of content -->
		<?php printAdminFooter(); ?>
	</div><!-- end of main -->
</body>
</html>



