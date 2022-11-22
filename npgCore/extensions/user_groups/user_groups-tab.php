<?php
/**
 * user_groups plugin--tabs
 * @author Stephen Billard (sbillard)
 * @package plugins/user_groups
 */
define('OFFSET_PATH', 4);
require_once(dirname(dirname(__DIR__)) . '/admin-globals.php');

admin_securityChecks(ADMIN_RIGHTS, currentRelativeURL());

switch (isset($_GET['tab']) ? $_GET['tab'] : NULL) {
	case 'assignment':
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

		break;
	case 'group':
	case 'template':
	default:
		if ($s = sanitize_numeric(getNPGCookie('usersTab_userCount'))) {
			define('USERS_PER_PAGE', $s);
		} else {
			define('USERS_PER_PAGE', 10);
		}

		if (isset($_GET['selection'])) {
			define('GROUPS_PER_PAGE', max(1, sanitize_numeric($_GET['selection'])));
			setNPGCookie('groupsTab_groupCount', GROUPS_PER_PAGE, 3600 * 24 * 365 * 10);
		} else {
			if ($s = sanitize_numeric(getNPGCookie('groupsTab_groupCount'))) {
				define('GROUPS_PER_PAGE', $s);
			} else {
				define('GROUPS_PER_PAGE', 10);
			}
		}

		break;
}
define('GROUP_STEP', 5);

if (isset($_GET['subpage'])) {
	$subpage = sanitize_numeric($_GET['subpage']);
} else {
	if (isset($_POST['subpage'])) {
		$subpage = sanitize_numeric($_POST['subpage']);
	} else {
		$subpage = 0;
	}
}

$admins = $_authority->getAdministrators('all');

$adminordered = sortMultiArray($admins, 'user');

if (isset($_GET['action'])) {
	$action = sanitize($_GET['action']);
	$themeswitch = false;
	if (isset($_REQUEST['tab'])) {
		$subtab = $_REQUEST['tab'];
	} else {
		$subtab = 'assignment';
	}
	switch ($action) {
		case 'deletegroup':
			XSRFdefender('deletegroup');
			$groupname = trim(sanitize($_GET['groupname']));
			$groupobj = npg_Authority::newAdministrator($groupname, 0);
			$groupobj->remove();
			// clear out existing user assignments
			npg_Authority::updateAdminField('group', NULL, array('`valid`>=' => '1', '`group`=' => $groupname));
			header("Location: " . getAdminLink(PLUGIN_FOLDER . '/user_groups/user_groups-tab.php') . '?page=admin&tab=' . $subtab . '&deleted&subpage=' . $subpage);
			exit();
		case 'savegroups':
			XSRFdefender('savegroups');
			if (isset($_POST['checkForPostTruncation'])) {
				$saved = false;
				$newgroupid = isset($_POST['newgroup']) ? $_POST['newgroup'] : NULL;
				$grouplist = $_POST['user'];
				foreach ($grouplist as $i => $groupelement) {
					$groupname = trim(sanitize($groupelement['groupname']));
					if (!empty($groupname)) {
						$rights = 0;
						$group = npg_Authority::newAdministrator($groupname, 0);
						if (isset($groupelement['initgroup']) && !empty($groupelement['initgroup'])) {
							$initgroupname = trim(sanitize($groupelement['initgroup'], 3));
							$initgroup = npg_Authority::newAdministrator($initgroupname, 0);
							$rights = $initgroup->getRights();
							$group->setObjects(processManagedObjects($group->getID(), $rights));
							$group->setRights(NO_RIGHTS | $rights);
						} else {
							$rights = processRights($i);
							$group->setObjects(processManagedObjects($i, $rights));
							$group->setRights(NO_RIGHTS | $rights);
						}
						$group->setCredentials(trim(sanitize($groupelement['desc'], 3)));
						$group->setName(trim(sanitize($groupelement['type'], 3)));
						$group->setValid(0);
						$group->setDesc(trim(sanitize($groupelement['desc'], 3)));
						npgFilters::apply('save_admin_data', $group, $i, true);
						if ($group->save() == 1) {
							$saved = true;
						}

						if ($group->getName() == 'group') {
							//have to update any users who have this group designate.
							$groupname = $group->getUser();
							foreach ($admins as $admin) {
								$hisgroups = explode(',', strval($admin['group']));
								if (in_array($groupname, $hisgroups)) {
									$userobj = npg_Authority::newAdministrator($admin['user'], $admin['valid']);
									user_groups::merge_rights($userobj, $hisgroups, user_groups::getPrimeObjects($userobj));
									$success = $userobj->save();
									if ($success == 1) {
										npgFilters::apply('save_user_complete', '', $userobj, 'update');
									}
								}
							}
							//user assignments: first clear out existing ones
							npg_Authority::updateAdminField('group', NULL, array('`valid`>=' => '1', '`group`=' => $groupname));
							if (isset($groupelement['userlist'])) {
								//then add the ones marked
								foreach ($groupelement['userlist'] as $list) {
									$username = $list['checked'];
									$userobj = $_authority->getAnAdmin(array('`user`=' => $username, '`valid`>=' => 1));
									$hisgroups = explode(',', $userobj->getGroup());
									if (!in_array($groupname, $hisgroups)) {
										$hisgroups[] = $groupname;
										$userobj->setGroup(implode(',', $hisgroups));
									}
									user_groups::merge_rights($userobj, array(1 => $groupname), user_groups::getPrimeObjects($userobj));
									$success = $userobj->save();
									if ($success == 1) {
										$saved = true;
										npgFilters::apply('save_user_complete', '', $userobj, 'update');
									}
								}
							}
						}
					}
				}
				if ($saved) {
					$notify = '&saved';
				} else {
					$notify = '&nochange';
				}
			} else {
				$notify = '&post_error';
			}
			header("Location: " . getAdminLink(PLUGIN_FOLDER . '/user_groups/user_groups-tab.php') . '?page=admin&tab=' . $subtab . '&subpage=' . $subpage . $notify);
			exit();
		case 'saveauserassignments':
			XSRFdefender('saveauserassignments');
			if (isset($_POST['checkForPostTruncation'])) {
				$userlist = $_POST['user'];
				foreach ($userlist as $i => $user) {
					if (isset($user['group'])) {
						$newgroups = sanitize($user['group']);
						$username = trim(sanitize($user['userid'], 3));
						$userobj = $_authority->getAnAdmin(array('`user`=' => $username, '`valid`>=' => 1));
						user_groups::merge_rights($userobj, $newgroups, user_groups::getPrimeObjects($userobj));
						$success = $userobj->save();
						if ($success === TRUE) {
							npgFilters::apply('save_user_complete', '', $userobj, 'update');
						}
					}
				}
				$notify = '&saved';
			} else {
				$notify = '&post_error';
			}
			header("Location: " . getAdminLink(PLUGIN_FOLDER . '/user_groups/user_groups-tab.php') . '?page=admin&tab=' . $subtab . '&subpage=' . $subpage . $notify);
			exit();
	}
}

printAdminHeader('admin');
$background = '';
scriptLoader(CORE_SERVERPATH . 'js/sprintf.js');
echo '</head>' . "\n";
?>

<body>
	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<?php
			if (isset($_GET['post_error'])) {
				echo '<div class="errorbox">';
				echo "<h2>" . gettext('Error') . "</h2>";
				echo gettext('The form submission is incomplete. Perhaps the form size exceeds configured server or browser limits.');
				echo '</div>';
			}
			if (isset($_GET['deleted'])) {
				echo '<div class="messagebox fade-message">';
				echo "<h2>" . gettext('Deleted') . "</h2>";
				echo '</div>';
			}
			if (isset($_GET['saved'])) {
				echo '<div class="messagebox fade-message">';
				echo "<h2>" . gettext('Saved') . "</h2>";
				echo '</div>';
			} else if (isset($_GET['nochange'])) {
				echo '<div class="messagebox fade-message">';
				echo "<h2>" . gettext('Nothing changed') . "</h2>";
				echo '</div>';
			}
			$subtab = getCurrentTab();
			npgFilters::apply('admin_note', 'admin', $subtab);
			?>
			<h1>
				<?php
				switch ($subtab) {
					case 'group':
						echo gettext('Groups');
						break;
					case 'template':
						echo gettext('Templates');
						break;
					default:
						echo gettext('Assignments');
						break;
				}
				?>
			</h1>

			<div id = "tab_users" class = "tabbox">
				<?php
				switch ($subtab) {
					case 'group':
					case 'template':
						$adminlist = $adminordered;
						$list = $users = $groups = array();
						$userCount = $groupCount = 0;
						foreach ($adminlist as $user) {
							if ($user['valid']) {
								$users[] = $user['user'];
								$userCount++;
							} else {
								if ($user['name'] == $subtab) {
									$groups[] = $user;
									$list[] = $user['user'];
									$groupCount++;
								}
							}
						}

						$max = floor((count($list) - 1) / GROUPS_PER_PAGE);
						if ($subpage > $max) {
							$subpage = $max;
						}
						$rangeset = getPageSelector($list, GROUPS_PER_PAGE);

						$groups = array_slice($groups, $subpage * GROUPS_PER_PAGE, GROUPS_PER_PAGE);
						if (count($groups) == 1) {
							$display = '';
						} else {
							$display = ' style="display:none"';
						}
						$albumlist = array();
						foreach ($_gallery->getAlbums() as $folder) {
							$alb = newAlbum($folder);
							$name = $alb->getTitle();
							$albumlist[$name] = $folder;
						}
						if ($groupCount > GROUP_STEP) {
							?>
							<div class="floatright">
								<?php
								$numsteps = ceil(min(100, $groupCount) / GROUP_STEP);
								if ($numsteps) {
									?>
									<?php
									$steps = array();
									for ($i = 1; $i <= $numsteps; $i++) {
										$steps[] = $i * GROUP_STEP;
									}
									printEditDropdown('groupinfo', $steps, GROUPS_PER_PAGE, '&amp;tab=' . $subtab);
								}
								?>
							</div>
							<?php
						}
						?>
						<p>
							<?php
							echo gettext("Set group rights and select one or more albums for the users in the group to manage. Users with <em>User admin</em> or <em>Manage all albums</em> rights can manage all albums. All others may manage only those that are selected.");
							?>
						</p>
						<form class="dirtylistening" onReset="setClean('savegroups_form');" id="savegroups_form" action="?action=savegroups&amp;tab=<?php echo $subtab; ?>" method="post" autocomplete="off" onsubmit="return checkSubmit()" >
							<?php XSRFToken('savegroups'); ?>
							<p>
								<?php
								applyButton();
								resetButton();
								?>
								<br /><br />
							</p>

							<input type="hidden" name="savegroups" value="yes" />
							<input type="hidden" name="subpage" value="<?php echo $subpage; ?>" />

							<table class="bordered">
								<tr>
									<th>
										<?php
										if (count($groups) != 1) {
											?>
											<span style="font-weight: normal">
												<a onclick="toggleExtraInfo('', 'user', true);"><?php echo gettext('Expand all'); ?></a>
												|
												<a onclick="toggleExtraInfo('', 'user', false);"><?php echo gettext('Collapse all'); ?></a>
											</span>
											<?php
										}
										?>
									</th>
									<th>
										<?php
										printPageSelector($subpage, $rangeset, PLUGIN_FOLDER . '/user_groups/user_groups-tab.php', array('page' => 'admin', 'tab' => $subtab));
										?>
									</th>
								</tr>

								<?php
								$user_count = array();
								foreach ($admins as $key => $user) {
									if (!empty($user['group'])) {
										$membership[$user['user']] = $belongs = explode(',', $user['group']);
										foreach ($belongs as $group) {
											if (!isset($user_count[$group])) {
												$user_count[$group] = 1;
											} else {
												$user_count[$group]++;
											}
										}
									} else {
										$membership[$user['user']] = array();
									}
								}

								$id = 0;
								$groupselector = $groups;
								$groupselector[''] = array('id' => -1, 'user' => '', 'name' => 'group', 'rights' => ALL_RIGHTS ^ MANAGE_ALL_ALBUM_RIGHTS, 'valid' => 0, 'other_credentials' => '');
								foreach ($groupselector as $key => $user) {
									$groupname = $user['user'];
									$groupid = $user['id'];
									$rights = $user['rights'];
									$grouptype = $user['name'];
									$desc = get_language_string($user['other_credentials']);
									$groupobj = new npg_Administrator($groupname, 0);
									if ($grouptype == 'group') {
										$kind = gettext('group');
										if (isset($user_count[$groupname])) {
											$count = ' (' . (int) $user_count[$groupname] . ')';
										} else {
											$count = '';
										}
									} else {
										$kind = gettext('template');
										$count = '';
									}
									if ($background) {
										$background = "";
									} else {
										$background = "background-color:#f0f4f5;";
									}
									?>
									<tr id="user-<?php echo $id; ?>">

										<td style="border-top: 4px solid #D1DBDF;<?php echo $background; ?>" valign="top" colspan="100%">
											<div class="user_left">
												<?php
												if (empty($groupname)) {
													?>
													<input type="hidden" name="newgroupid" value="<?php echo $id; ?>" />
													<input type="hidden" name="user[<?php echo $id; ?>][type]" value="<?php echo $subtab; ?>" checked="checked"  />
													<em>
														<?php echo gettext('New'); ?>
													</em>
													<br />
													<input type="text" size="35" id="group-<?php echo $id ?>" name="user[<?php echo $id ?>][groupname]" value=""
																 onclick="toggleExtraInfo('<?php echo $id; ?>', 'user', true);" />
																 <?php
															 } else {
																 ?>
													<span class="userextrashow">

														<a onclick="toggleExtraInfo('<?php echo $id; ?>', 'user', true);" title="<?php echo $groupname; ?>" >
															<strong><?php echo $groupname; ?></strong> <?php echo $count; ?>
														</a>
													</span>
													<span style="display:none;" class="userextrahide">
														<em><?php echo $kind; ?></em>:
														<a onclick="toggleExtraInfo('<?php echo $id; ?>', 'user', false);" title="<?php echo $groupname; ?>" >
															<strong><?php echo $groupname; ?></strong> <?php echo $count; ?>
														</a>
													</span>
													<input type="hidden" id="group-<?php echo $id ?>" name="user[<?php echo $id ?>][groupname]" value="<?php echo html_encode($groupname); ?>" />
													<input type="hidden" name="user[<?php echo $id ?>][type]" value="<?php echo html_encode($grouptype); ?>" />
													<?php
												}
												?>
												<input type="hidden" name="user[<?php echo $id ?>][confirmed]" value="1" />
											</div>
											<div class="floatright">
												<?php
												if (!empty($groupname)) {
													$msg = gettext('Are you sure you want to delete this group?');
													?>
													<a href="javascript:if(confirm(<?php echo "'" . $msg . "'"; ?>)) { launchScript('',['tab=<?php echo $subtab; ?>', 'action=deletegroup','groupname=<?php echo addslashes($groupname); ?>','XSRFToken=<?php echo getXSRFToken('deletegroup') ?>']); }"
														 title="<?php echo gettext('Delete this group.'); ?>" style="color: #c33;">
															 <?php echo WASTEBASKET; ?>
													</a>
													<?php
												}
												?>
											</div>
											<br class="clearall" />
											<div class="user_left userextrainfo"<?php echo $display; ?>>
												<?php
												printAdminRightsTable($id, '  ', ' ', $rights);

												if (empty($groupname) && !empty($groups)) {
													?>
													<?php echo gettext('clone:'); ?>
													<br />
													<select name="user[<?php echo $id; ?>][initgroup]" onchange="javascript:$('#hint<?php echo $id; ?>').html(this.options[this.selectedIndex].title);">
														<option title=""></option>
														<?php
														foreach ($groups as $user) {
															$hint = '<em>' . html_encode($desc) . '</em>';
															if ($groupname == $user['user']) {
																$selected = ' selected="selected"';
															} else {
																$selected = '';
															}
															?>
															<option<?php echo $selected; ?> title="<?php echo $hint; ?>"><?php echo $user['user']; ?></option>
															<?php
														}
														?>
													</select>
													<span class="hint<?php echo $id; ?>" id="hint<?php echo $id; ?>"></span><br /><br />
													<?php
												}
												?>

											</div>
											<div class="user_right userextrainfo" <?php echo $display; ?>>
												<strong><?php echo gettext('description:'); ?></strong>
												<br />
												<textarea name="user[<?php echo $id; ?>][desc]" cols="40" rows="4"><?php echo html_encode($desc); ?></textarea>

												<br /><br />
												<div id="users<?php echo $id; ?>" <?php if ($grouptype == 'template') echo ' style="display:none"' ?>>
													<h2 class="h2_bordered_edit"><?php echo gettext("Assign users"); ?></h2>
													<div class="box-tags-unpadded">
														<?php
														$members = array();
														if (!empty($groupname)) {
															foreach ($adminlist as $user) {
																if ($user['valid']) {
																	if (in_array($groupname, $membership[$user['user']])) {
																		$members[] = $user['user'];
																	}
																}
															}
														}
														?>
														<ul class="shortchecklist">
															<?php generateUnorderedListFromArray($members, $members, 'user[' . $id . '][userlist]', false, true, false, NULL, NULL, 2); ?>
															<?php generateUnorderedListFromArray(array(), array_diff($users, $members), 'user[' . $id . '][userlist]', false, true, false, NULL, NULL, 2); ?>
														</ul>
													</div>
												</div>

												<?php
												printManagedObjects('albums', $albumlist, NULL, $groupobj, $id, $kind, array());
												if (class_exists('CMS')) {
													$newslist = array();
													$categories = $_CMS->getAllCategories(false);
													foreach ($categories as $category) {
														$newslist[get_language_string($category['title'])] = $category['titlelink'];
													}
													printManagedObjects('news_categories', $newslist, NULL, $groupobj, $id, $kind, NULL);
													$pagelist = array();
													$pages = $_CMS->getPages(false);
													foreach ($pages as $page) {
														if (!$page['parentid']) {
															$pagelist[get_language_string($page['title'])] = $page['titlelink'];
														}
													}
													printManagedObjects('pages', $pagelist, NULL, $groupobj, $id, $kind, NULL);
												}
												?>

											</div>
											<br class="clearall" />
											<div class="userextrainfo" <?php echo $display; ?>>
												<?php
												$custom = npgFilters::apply('edit_admin_custom', '', $groupobj, $id, $background, true, '');
												if ($custom) {
													echo stripTableRows($custom);
												}
												?>
											</div>
										</td>
									</tr>
									<?php
									$id++;
									$display = ' style="display:none"';
								}
								?>
								<tr>
									<th>
										<?php
										if (count($groups) != 1) {
											?>
											<span style="font-weight: normal">
												<a onclick="toggleExtraInfo('', 'user', true);"><?php echo gettext('Expand all'); ?></a>
												|
												<a onclick="toggleExtraInfo('', 'user', false);"><?php echo gettext('Collapse all'); ?></a>
											</span>
											<?php
										}
										?>
									</th>
									<th>
										<?php
										printPageSelector($subpage, $rangeset, PLUGIN_FOLDER . '/user_groups/user_groups-tab.php', array('page' => 'admin', 'tab' => $subtab));
										?>
									</th>
								</tr>
							</table>
							<p>
								<?php
								applyButton();
								resetButton();
								?>
								<br /><br />
							</p>

							<input type="hidden" name="totalgroups" value="<?php echo $id; ?>" />
							<input type="hidden" name="checkForPostTruncation" value="1" />
						</form>
						<script type="text/javascript">
							//<!-- <![CDATA[
							function checkSubmit() {
								newgroupid = <?php echo ($id - 1); ?>;
								var c = 0;
		<?php
		foreach ($users as $name) {
			?>
									c = 0;
									for (i = 0; i <= newgroupid; i++) {
										if ($('#user_' + i + '-<?php echo postIndexEncode($name); ?>').prop('checked'))
											c++;
									}
									if (c > 1) {
										alert('<?php echo sprintf(gettext('User %s is assigned to more than one group.'), $name); ?>');
										return false;
									}
			<?php
		}
		?>
								newgroup = $('#group-' + newgroupid).val().replace(/^\s+|\s+$/g, "");
								if (newgroup == '')
									return true;
								if (newgroup.indexOf('?') >= 0 || newgroup.indexOf('&') >= 0 || newgroup.indexOf('"') >= 0 || newgroup.indexOf('\'') >= 0) {
									alert('<?php echo gettext('Group names may not contain “?”, “&”, or quotation marks.'); ?>');
									return false;
								}
								for (i = newgroupid - 1; i >= 0; i--) {
									if ($('#group-' + i).val() == newgroup) {
										alert(sprintf('<?php echo gettext('The group “%s” already exists.'); ?>', newgroup));
										return false;
									}
								}
								return true;
							}
							// ]]> -->
						</script>
						<br class="clearall" />
						<?php
						break;
					case 'assignment':
						$list = $groups = array();
						$userCount = $groupCount = 0;
						foreach ($adminordered as $user) {
							if ($user['valid']) {
								$users[] = $user;
								$list[] = $user['user'];
								$userCount++;
							} else {
								if ($user['name'] == 'group') {
									$groups[] = $user;
									$groupCount++;
								}
							}
						}
						$rangeset = getPageSelector($list, USERS_PER_PAGE);
						$max = floor((count($users) - 1) / USERS_PER_PAGE);
						if ($subpage > $max) {
							$subpage = $max;
						}
						$userlist = array_slice($users, $subpage * USERS_PER_PAGE, USERS_PER_PAGE);
						if ($userCount > GROUP_STEP) {
							?>
							<div class="floatright">
								<?php
								$numsteps = ceil(min(100, $userCount) / GROUP_STEP);
								if ($numsteps) {
									?>
									<?php
									$steps = array();
									for ($i = 1; $i <= $numsteps; $i++) {
										$steps[] = $i * GROUP_STEP;
									}
									printEditDropdown('groupinfo', $steps, USERS_PER_PAGE, '&amp;tab=assignment');
								}
								?>
							</div>
							<?php
						}
						?>
						<p>
							<?php
							echo gettext("Assign users to groups.");
							?>
						</p>
						<form class="dirtylistening" onReset="setClean('saveAssignments_form');" id="saveAssignments_form" action="?tab=<?php echo $subtab; ?>&amp;action=saveauserassignments" method="post" autocomplete="off" >
							<?php XSRFToken('saveauserassignments'); ?>
							<div class="notebox">
								<?php echo gettext('<strong>Note:</strong> When a group is assigned <em>rights</em> and <em>managed objects</em> are determined by the group!'); ?>
							</div>
							<p>
								<?php
								applyButton();
								resetButton();
								?>
								<br />
							</p>

							<input type="hidden" name="saveauserassignments" value="yes" />
							<div class="floatright">

								<?php
								printPageSelector($subpage, $rangeset, PLUGIN_FOLDER . '/user_groups/user_groups-tab.php', array('page' => 'admin', 'tab' => $subtab));
								?>
							</div>

							<table class="bordered">
								<?php
								$id = 0;
								foreach ($userlist as $user) {
									$userobj = new npg_Administrator($user['user'], $user['valid']);
									$group = $user['group'];
									?>
									<tr>
										<td width="20%" style="border-top: 1px solid #D1DBDF;" valign="top">
											<input type="hidden" name="user[<?php echo $id; ?>][userid]" value="<?php echo $user['user']; ?>" />
											<?php echo $user['user']; ?>
										</td>
										<td style="border-top: 1px solid #D1DBDF;" valign="top" >
											<?php echo user_groups::groupList($userobj, $id, '', $user['group'], false); ?>
										</td>
									</tr>
									<?php
									$id++;
								}
								?>
							</table>

							<div class="floatright">
								<?php
								printPageSelector($subpage, $rangeset, PLUGIN_FOLDER . '/user_groups/user_groups-tab.php', array('page' => 'admin', 'tab' => $subtab));
								?>
							</div>
							<p>
								<?php
								applyButton();
								resetButton();
								?>
							</p>

							<input type="hidden" name="totalusers" value="<?php echo $id; ?>" />
							<input type="hidden" name="checkForPostTruncation" value="1" />
						</form>
						<br class="clearall" />
						<?php
						break;
				}
				?>
			</div>
		</div>
		<?php printAdminFooter(); ?>
	</div>
</body>

</html>