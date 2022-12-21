<?php
/**
 * zenpage edit.php
 *
 * @author Malte Müller (acrylian)
 * @package plugins/zenpage
 */
define('OFFSET_PATH', 4);
require_once(dirname(dirname(__DIR__)) . '/admin-globals.php');
require_once("admin-functions.php");
require_once(PLUGIN_SERVERPATH . 'tag_suggest.php');

if (is_AdminEditPage('page')) {
	$rights = ZENPAGE_PAGES_RIGHTS;
	$script = 'pages.php?tab=pages';
	$page = $tab = 'pages';
	$tab = NULL;
	$new = 'newPage';
	$update = 'updatePage';
	$returnpage = 'page';
} else if (is_AdminEditPage('newsarticle')) {
	$rights = ZENPAGE_NEWS_RIGHTS;
	$script = 'news.php?tab=articles';
	$page = 'news';
	$_GET['tab'] = $tab = 'articles';
	$new = 'newArticle';
	$update = 'updateArticle';
	$returnpage = 'newsarticle';
} else if (is_AdminEditPage('newscategory')) {
	$rights = ZENPAGE_NEWS_RIGHTS;
	$script = 'categories.php?tab=categories';
	$page = 'news';
	$_GET['tab'] = $tab = 'categories';
	$new = 'newCategory';
	$update = 'updateCategory';
	$returnpage = 'newscategory';
} else {
//we should not be here!
	header('Location: ' . getAdminLink('admin.php'));
	exit();
}


admin_securityChecks($rights, currentRelativeURL());

updatePublished('news');
updatePublished('pages');

$saveitem = '';
$reports = array();

$redirect = false;
if (isset($_GET['titlelink'])) {
	$result = $new(urldecode(sanitize($_GET['titlelink'])));
} else if (isset($_GET['update'])) {
	XSRFdefender('update');
	$result = $update($reports);
	if (getCheckboxState('copy_delete_object')) {
		switch (sanitize($_POST['copy_delete_object'])) {
			case 'copy':
				$as = trim(sanitize($_POST['copy_object_as']));
				if (empty($as)) {
					$as = sprintf(gettext('copy of %s'), $result->getTitle());
				}
				$as = seoFriendly($as);
				$result->copy($as);
				$result = $new($as);
				$_GET['titlelink'] = $as;
				break;
			case 'delete':
				$reports[] = deleteZenpageObj($result, $script);
				unset($_POST['subpage']);
				break;
		}
	}

	if (isset($_POST['subpage']) && $_POST['subpage'] == 'object' && count($reports) <= 1) {
		if (isset($_POST['category'])) {
			$_CMS_current_category = newCategory(sanitize($_POST['category']), false);
			$cat = $_CMS_current_category->exists;
		} else {
			$cat = NULL;
		}
		header('Location: ' . $result->getLink($cat));
	} else {
		$redirect = getAdminLink(PLUGIN_FOLDER . '/zenpage/edit.php') . '?' . $returnpage . '&titlelink=' . html_encode($result->getTitlelink());
	}
} else {
	$result = $new('');
}

if (isset($_GET['save'])) {
	XSRFdefender('save');
	$result = $update($reports, true);
	$redirect = getAdminLink(PLUGIN_FOLDER . '/zenpage/edit.php') . '?' . $returnpage . '&titlelink=' . html_encode($result->getTitlelink());
}
if (isset($_GET['delete'])) {
	XSRFdefender('delete');
	$msg = deleteZenpageObj('new' . $new(sanitize($_GET['delete']), 'pages.php'));
	if (!empty($msg)) {
		$reports[] = $msg;
	}
}
if ($redirect) {
	$_SESSION['reports'] = $reports;
	header('Location: ' . $redirect);
	exit();
} else if (isset($_SESSION['reports'])) {
	$reports = $_SESSION['reports'];
	unset($_SESSION['reports']);
}
/*
 * Here we should restart if any action processing has occurred to be sure that everything is
 * in its proper state. But that would require significant rewrite of the handling and
 * reporting code so is impractical. Instead we will presume that all that needs to be restarted
 * is the CMS object.
 */
$_CMS = new CMS();

printAdminHeader($page, $tab);
npgFilters::apply('texteditor_config', 'CMS');
zenpageJSCSS();
datepickerJS();
codeblocktabsJS();
$tagsort = 'alpha';
?>
<script type="text/javascript">
	//<!-- <![CDATA[
	var deleteArticle = "<?php echo gettext("Are you sure you want to delete this article? THIS CANNOT BE UNDONE!"); ?>";
	var deletePage = "<?php echo gettext("Are you sure you want to delete this page? THIS CANNOT BE UNDONE!"); ?>";
	var deleteCategory = "<?php echo gettext("Are you sure you want to delete this category? THIS CANNOT BE UNDONE!"); ?>";

	function checkFutureExpiry() {
		var expiry = $('#expiredate').datepicker('getDate');
		var today = new Date();
		if (expiry.getTime() > today.getTime()) {
			$(".expire").html('');
		} else {
			$(".expire").html('<?php echo addslashes(gettext('This is not a future date!')); ?>');
		}
	}
	function checkFuturePub() {
		var today = new Date();
		var pub = $('#pubdate').datepicker('getDate');
		if (pub.getTime() > today.getTime()) {
			$('#show').prop('checked', false);
			$("#pubdate").css("color", "blue");
		} else {
			$('#show').prop('checked', true);
			$("#pubdate").css("color", "black");

		}
	}
	function toggleTitlelink() {
		if (jQuery('#edittitlelink:checked').val() == 1) {
			$('#titlelinkrow').show();
			$('#titlelink').removeAttr("disabled");
		} else {
			$('#titlelink').attr("disabled", true);
			$('#titlelinkrow').hide();
		}
	}

	function resizeTable() {
		$('.width100percent').width($('.formlayout').width() - $('.rightcolumn').width() - 30);
	}

	window.addEventListener('load', function () {
		resizeTable();
	}, false);

	$(function () {
		$("#date").datepicker({
			dateFormat: 'yy-mm-dd',
			showOn: 'button',
			buttonImage: '<?php echo CALENDAR; ?>',
			buttonText: '<?php echo gettext('calendar'); ?>',
			buttonImageOnly: true
		});
	});
	
</script>
<?php npg_Authority::printPasswordFormJS(); ?>
</head>
<body onresize="resizeTable()">
	<?php
	printLogoAndLinks();
	?>
	<div id="main">
		<?php
		printTabs();
		?>
		<div id="content">
			<?php
			if (empty($_GET['subpage'])) {
				$page = "";
				$pageno = 0;
			} else {
				$pageno = sanitize($_GET['subpage']);
				$page = '&amp;subpage=' . $pageno;
			}

			$saveitem = $updateitem = gettext('Apply');

			if (is_AdminEditPage('newsarticle')) {
				$admintype = 'newsarticle';
				$additem = gettext('New Article');
				$deleteitem = gettext('Article');
				$themepage = 'news';
				$locked = !checkIfLocked($result);
				$me = 'news';
				$backurl = getAdminLink(PLUGIN_FOLDER . '/zenpage/news.php') . '?' . $page;
				if (isset($_GET['category']))
					$backurl .= '&amp;category=' . html_encode(sanitize($_GET['category']));
				if (isset($_GET['date']))
					$backurl .= '&amp;date=' . html_encode(sanitize($_GET['date']));
				if (isset($_GET['published']))
					$backurl .= '&amp;published=' . html_encode(sanitize($_GET['published']));
				if (isset($_GET['sortorder']))
					$backurl .= '&amp;sortorder=' . html_encode(sanitize($_GET['sortorder']));
				if (isset($_GET['articles_page']))
					$backurl .= '&amp;articles_page=' . html_encode(sanitize($_GET['articles_page']));
			}

			if (is_AdminEditPage('newscategory')) {
				$admintype = 'newscategory';
				IF (npg_loggedin(MANAGE_ALL_NEWS_RIGHTS)) {
					$additem = gettext('New Category');
				} else {
					$additem = '';
				}
				$deleteitem = gettext('Category');
				$themepage = 'news';
				$locked = false;
				$me = 'news';
				$backurl = $backurl = getAdminLink(PLUGIN_FOLDER . '/zenpage/categories.php') . '?';
			}

			if (is_AdminEditPage('page')) {
				$admintype = 'page';
				$additem = gettext('New Page');
				$deleteitem = gettext('Page');
				$themepage = 'pages';
				$locked = !checkIfLocked($result);
				$me = 'page';
				$backurl = getAdminLink(PLUGIN_FOLDER . '/zenpage/pages.php');
			}
			if (!is_numeric($pageno)) {
				$backurl = $result->getLink();
			}

			if (!$result->isMyItem($result->manage_some_rights)) {
				$locked = true;
			}
			npgFilters::apply('admin_note', $me, 'edit');

			if (!$result->loaded && !$result->transient) {
				$result->transient = true;
				?>
				<div class="errorbox fade-message">
					<?php
					if (is_AdminEditPage('newsarticle')) {
						?>
						<h1><?php printf(gettext('Article <em>%s</em> not found'), html_encode(sanitize($_GET['titlelink']))); ?></h1>
						<?php
					}
					if (is_AdminEditPage('newscategory')) {
						?>
						<h1><?php printf(gettext('Category <em>%s</em> not found'), html_encode(sanitize($_GET['titlelink']))); ?></h1>
						<?php
					}
					if (is_AdminEditPage('page')) {
						?>
						<h1><?php printf(gettext('Page <em>%s</em> not found'), html_encode(sanitize($_GET['titlelink']))); ?></h1>
						<?php
					}
					?>
				</div>
				<?php
			}
			if ($result->transient) {
				if (is_AdminEditPage('newsarticle')) {
					?>
					<h1><?php echo gettext('New Article'); ?></h1>
					<?php
				}
				if (is_AdminEditPage('newscategory')) {
					?>
					<h1><?php echo gettext('New Category'); ?></h1>
					<?php
				}
				if (is_AdminEditPage('page')) {
					?>
					<h1><?php echo gettext('New Page'); ?></h1>
					<?php
				}
			} else {
				if (is_AdminEditPage('newsarticle')) {
					?>
					<h1><?php echo gettext('Edit Article:'); ?> <em><?php checkForEmptyTitle($result->getTitle(), 'news', false); ?></em></h1>
					<?php
					if ($result->getPublishDate() >= date('Y-m-d H:i:s')) {
						echo '<small><strong id="scheduldedpublishing">' . gettext('(Article scheduled for publishing)') . '</strong></small>';
					}
					if ($result->inProtectedCategory()) {
						echo '<p class="notebox">' . gettext('<strong>Note:</strong> This article belongs to a password protected category.') . '</p>';
					}
				}
				if (is_AdminEditPage('newscategory')) {
					?>
					<h1><?php echo gettext('Edit Category:'); ?> <em><?php checkForEmptyTitle($result->getTitle(), 'category', false); ?></em></h1>
					<?php
				}
				if (is_AdminEditPage('page')) {
					?>
					<h1><?php echo gettext('Edit Page:'); ?> <em><?php checkForEmptyTitle($result->getTitle(), 'page', false); ?></em></h1>
					<?php
					if ($result->getPublishDate() >= date('Y-m-d H:i:s')) {
						echo ' <small><strong id="scheduldedpublishing">' . gettext('(Page scheduled for publishing)') . '</strong></small>';
					}
					if ($result->getPassword()) {
						echo '<p class="notebox">' . gettext('<strong>Note:</strong> This page is password protected.') . '</p>';
					}
				}
			}
			if ($result->loaded || $result->transient) {
				if ($result->transient) {
					?>
					<form class="dirtylistening" onReset="setClean('addnews_form');" id="addnews_form" method="post" name="addnews" action="<?php echo getAdminLink(PLUGIN_FOLDER . '/zenpage/edit.php') . '?' . $admintype; ?>&amp;save" autocomplete="off">
						<?php
						XSRFToken('save');
					} else {
						if ($locked) {
							?>
							<script type="text/javascript">
								window.addEventListener('load', function () {
									$('#form_cmsItemEdit :input').prop('disabled', true);
									$('input[type="submit"]').prop('disabled', true);
									$('input[type="reset"]').prop('disabled', true);
								}, false);
							</script>
							<?php
						}
						?>

						<div id="tab_articles" class="tabbox">

							<form class="dirtylistening" onReset="setClean('form_cmsItemEdit');$('.resetHide').hide();" method="post" name="update" id="form_cmsItemEdit" action="<?php echo getAdminLink(PLUGIN_FOLDER . '/zenpage/edit.php') . '?' . $admintype; ?>&amp;update<?php echo $page; ?>" autocomplete="off">
								<?php
								XSRFToken('update');
							}
							if (isset($_GET['subpage'])) {
								?>
								<input type="hidden" name="subpage" id="subpage" value="<?php echo html_encode(sanitize($_GET['subpage'])); ?>" />
								<?php
							}
							if (isset($_GET['category'])) {
								?>
								<input type="hidden" name="category" id="subpage" value="<?php echo html_encode(sanitize($_GET['category'])); ?>" />
								<?php
							}
							?>

							<input type="hidden" name="id" value="<?php echo $result->getID(); ?>" />
							<input type="hidden" name="titlelink-old" id="titlelink-old" value="<?php echo html_encode($result->getTitlelink()); ?>" />
							<?php
							if ($reports) {
								$show = array();
								preg_match_all('/<p class=[\'"](.*?)[\'"]>(.*?)<\/p>/', implode('', $reports), $matches);
								foreach ($matches[1] as $key => $report) {
									$show[$report][] = $matches[2][$key];
								}
								foreach ($show as $type => $list) {
									echo '<p class="' . $type . '">' . implode('<br />', $list) . '</p>';
								}
							}
							?>
							<?php
							backButton(array('buttonLink' => $backurl));
							if ($result->transient) {
								$buttonText = $saveitem;
							} else {
								$buttonText = $updateitem;
							}
							applyButton(array('buttonText' => CHECKMARK_GREEN . ' ' . $buttonText));
							resetButton(array('buttonClick' => "$('.copydelete').hide();"));
							?>
							<div class="floatright">
								<?php
								if ($additem) {
									npgButton('button', PLUS_ICON . ' ' . $additem, array('buttonLink' => getAdminLink(PLUGIN_FOLDER . '/zenpage/edit.php') . '?' . $admintype . '&amp;add&amp;XSRFToken=' . getXSRFToken('add')));
								}
								npgButton('button', INFORMATION_BLUE . ' ' . gettext("Usage tips"), array('buttonClick' => "$('#tips').toggle('slow');"));
								if (!$result->transient) {
									viewButton(array('buttonLink' => $result->getlink()));
								}
								?>
							</div>
							<br style="clear: both" /><br style="clear: both" />

							<div id="tips" style="display:none">
								<br />
								<h2><?php echo gettext("Usage tips"); ?></h2>
								<p><?php echo gettext("Check <em>Edit Titlelink</em> if you need to customize how the title appears in URLs. Otherwise it will be automatically updated to any changes made to the title. If you want to prevent this check <em>Enable permaTitlelink</em> and the titlelink stays always the same (recommended if you use multilingual mode)."); ?></p>
								<p class="notebox"><?php echo gettext("<strong>Note:</strong> Edit titlelink overrides the permalink setting."); ?></p>
								<p class="notebox"><?php echo gettext("<strong>Important:</strong> If you are using multi-lingual mode the Titlelink is generated from the Title of the currently selected language."); ?></p>
								<p><?php echo gettext("If you lock an article only the current active author/user or any user with full admin rights will be able to edit it later again!"); ?></p>
								<?php
								if (is_AdminEditPage("newsarticle")) {
									?>
									<p><?php echo gettext("<em>Custom article shortening:</em> You can set a custom article shorten length for the news loop excerpts by using the standard TinyMCE <em>page break</em> plugin button. This will override the general shorten length set on the plugin option then."); ?></p>
									<?php
								}
								?>
								<p><?php echo gettext("<em>Scheduled publishing:</em> To automatically publish a page/news article in the future set it to “published” and enter a future date in the date field manually. Note this works on server time!"); ?></p>
								<p><?php echo gettext("<em>Expiration date:</em> Enter a future date in the date field manually to set a date the page or article will be set un-published automatically. After the page/article has been expired it can only be published again if the expiration date is deleted. Note this works on server time!"); ?></p>
								<p><?php echo gettext("<em>ExtraContent:</em> Here you can enter extra content for example to be printed on the sidebar"); ?></p>
								<p>
									<?php
									echo gettext("<em>Codeblocks:</em> Use these fields if you need to enter php code (for example template functions) or JavaScript code.") . ' ';
									echo gettext("You also can use the codeblock fields as custom fields.") . '';
									echo gettext("Note that your theme must be setup to use the codeblock functions. Note also that codeblock fields are not multi-lingual.");
									?>
								</p>
								<p class="notebox"><?php echo gettext("<strong>Important:</strong> If setting a password for a page its subpages inherit the protection."); ?></p>
								<p><?php echo gettext("Hint: If you need more space for your text use TinyMCE’s full screen mode. (Click the fullscreen icon of editor's control bar or use CTRL+SHIFT+F.)"); ?></p>
							</div>

							<br class="clearall" />
							<div>
								<div class="formlayout">
									<div class="floatleft">
										<table class="width100percent">
											<tr>
												<td class="leftcolumn"><?php echo gettext("Title"); ?></td>
												<td class="middlecolumn">
													<?php print_language_string_list($result->getTitle('all'), 'title', false, NULL, 'title', '95%', 10); ?>
												</td>
											</tr>

											<?php
											if (!$result->transient) {
												?>
												<tr>
													<td>
														<span class="floatright">
															<?php echo linkPickerIcon($result, 'pick_link') ?>
														</span>
													</td>
													<td class="middlecolumn">
														<?php echo linkPickerItem($result, 'pick_link'); ?>
													</td>
												</tr>
												<?php
											}
											?>

											<tr id="titlelinkrow" style="display:none">
												<td><?php echo gettext("TitleLink"); ?></td>
												<td class="middlecolumn">
													<?php
													if ($result->transient) {
														echo gettext("A search engine friendly <em>titlelink</em> (aka slug) without special characters to be used in URLs is generated from the title of the currently chosen language automatically. You can edit it manually later after saving if necessary.");
													} else {
														?>
														<input name="titlelink" type="text" id="titlelink" value="<?php echo $result->getTitlelink(); ?>" disabled="disabled" style="width: 95%;" />
														<?php
													}
													?>
												</td>
											</tr>
											<?php
											if (get_class($result) == 'Page' || get_class($result) == 'Category') {
												$hint = $result->getPasswordHint('all');
												$user = $result->getUser();
												$x = $result->getPassword();
												?>
												<input	type="hidden" name="password_enabled" id="password_enabled" value="0" />

												<?php
												if (GALLERY_SECURITY == 'public') {
													?>
													<tr>
														<td class="leftcolumn">
														</td>
														<td class="middlecolumn">
															<p class="passwordextrashow">
																<?php
																if (empty($x)) {
																	?>
																	<a onclick="toggle_passwords('', true);">
																		<?php echo gettext("Password:"); ?>
																		<?php echo LOCK_OPEN; ?>
																	</a>
																	<?php
																} else {
																	$info = password_get_info($x);
																	$x = '          ';
																	?>
																	<a onclick="resetPass('');" title="<?php echo gettext('clear password'); ?>">
																		<?php echo gettext("Password:"); ?>
																		<?php echo LOCK; ?>
																	</a>
																	<?php
																	if (!$info['algo']) {
																		?>
																		<a title="<?php echo gettext('Password is encrypted with a deprecated password hashing algorithm.'); ?>"><?php echo WARNING_SIGN_ORANGE; ?>											</a>
																		<?php
																	}
																}
																?>
															</p>
															<div class="passwordextrahide" style="display:none; width:200px;">
																<a onclick="toggle_passwords('', false);">
																	<?php echo gettext("Guest user:"); ?>
																</a>
																<br />
																<input type="text"
																			 class="passignore ignoredirty" autocomplete="off"
																			 size="27"
																			 id="user_name"
																			 name="user"
																			 onkeydown="passwordClear('');"
																			 value="<?php echo html_encode($user); ?>" />
																<br />
																<span id="strength"><?php echo gettext("Password:"); ?></span>
																<label class="floatright">
																	<input type="checkbox"
																				 name="disclose_password"
																				 id="disclose_password"
																				 onclick="passwordClear('');
																									 togglePassword('');">
																				 <?php echo gettext('Show'); ?>
																</label>
																<br />
																<input type="password"
																			 class="passignore ignoredirty" autocomplete="off"
																			 size="27"
																			 id="pass" name="pass"
																			 onkeydown="passwordClear('');"
																			 onkeyup="passwordStrength('');"
																			 value="<?php echo $x; ?>" />

																<br />
																<span class="password_field_">
																	<span id="match"><?php echo gettext("(repeat)"); ?></span>
																	<br />
																	<input type="password"
																				 class="passignore ignoredirty" autocomplete="off"
																				 size="27"
																				 id="pass_r" name="pass_r" disabled="disabled"
																				 onkeydown="passwordClear('');"
																				 onkeyup="passwordMatch('');"
																				 value="<?php echo $x; ?>" />
																</span>

																<br />
																<?php echo gettext("Password hint:"); ?>
																<br />
																<?php print_language_string_list($hint, 'hint', false, NULL, 'hint'); ?>
																<br />
															</div>
														</td>
													</tr>
													<?php
												}
											} else {
												$hint = $user = $x = '';
											}
											?>
											<tr>
												<td class="leftcolumn">
													<?php
													if (is_AdminEditPage("newscategory")) {
														echo gettext('Description');
													} else {
														echo gettext("Content");
													}
													?></td>
												<td class="middlecolumn">
													<?php
													if (is_AdminEditPage("newscategory")) {
														print_language_string_list($result->getDesc('all'), 'desc', true, NULL, 'desc', '100%', 10);
													} else {
														print_language_string_list($result->getContent('all'), 'content', true, NULL, 'content', '100%', 13);
													}
													?>
												</td>
											</tr>
											<?php
											if (is_AdminEditPage("newsarticle")) {
												$custom = npgFilters::apply('edit_article_custom', '', $result);
											}
											if (is_AdminEditPage("newscategory")) {
												$custom = npgFilters::apply('edit_category_custom', '', $result);
											}
											if (is_AdminEditPage("page")) {
												$custom = npgFilters::apply('edit_page_custom', '', $result);
											}
											echo $custom;
											?>
										</table>
										<?php
										if (is_AdminEditPage("newscategory")) {
											?>
											<br />
											<?php
										}
										?>
									</div>


									<div class="floatleft">
										<div class="rightcolumn">
											<h2 class="h2_bordered_edit"><?php echo gettext("General"); ?></h2>
											<div class="box-edit">
												<label class="checkboxlabel">
													<input name="show"
																 type="checkbox"
																 id="show"
																 value="1" <?php checkIfChecked($result->getShow()); ?>
																 onclick="$('#pubdate').val('');
																			 $('#expiredate').val('');
																			 $('#pubdate').css('color', 'black');
																			 $('.expire').html('');"
																 />
													<?php echo gettext("Published"); ?></label>

												<?php
												if (!$result->transient) {
													?>
													<label class="checkboxlabel">
														<input name="edittitlelink" type="checkbox" id="edittitlelink" value="1" onclick="toggleTitlelink();" />
														<?php echo gettext("Edit TitleLink"); ?></label>

													<?php
												}
												?>
												<label class="checkboxlabel">
													<input name="permalink"
																 type="checkbox" id="permalink"
																 value="1" <?php checkIfChecked($result->getPermalink()); ?>
																 />
													<?php echo gettext("Enable permaTitlelink"); ?></label>

												<?php
												if (!is_AdminEditPage("newscategory")) {
													?>
													<label class="checkboxlabel">
														<input name="locked" type="checkbox" id="locked" value="1" <?php checkIfChecked($result->getLocked()); ?> />
														<?php echo gettext("Locked for changes"); ?></label>
													<?php
												}
												?>
												<br clear="all">
												<?php
												if (is_AdminEditPage('newsarticle')) {
													$sticky = $result->get('sticky');
													?>
													<p>
														<?php echo gettext("Position:"); ?>
														<select id="sticky" name="sticky">
															<option value="<?php echo NEWS_POSITION_NORMAL; ?>" <?php if ($sticky == NEWS_POSITION_NORMAL) echo 'selected="selected"'; ?>><?php echo gettext("normal"); ?></option>
															<option value="<?php echo NEWS_POSITION_STICKY; ?>" <?php if ($sticky == NEWS_POSITION_STICKY) echo 'selected="selected"'; ?>><?php echo gettext("sticky"); ?></option>
															<option value="<?php echo NEWS_POSITION_STICK_TO_TOP; ?>" <?php if ($sticky == NEWS_POSITION_STICK_TO_TOP) echo 'selected="selected"'; ?>><?php echo gettext("Stick to top"); ?></option>
														</select>
													</p>
													<?php
												}

												if (!is_AdminEditPage("newscategory")) {
													?>
													<p>
														<?php echo gettext('Date'); ?> <small>(YYYY-MM-DD)</small>
														<?php $date = $result->getDatetime(); ?>
														<input name="date" type="text" id="date" value="<?php echo $date; ?>" />
													</p>
													<p>
														<?php echo gettext('Publish date'); ?> <small>(YYYY-MM-DD)</small>
														<?php $date = $result->getPublishDate(); ?>
														<input name="pubdate" type="text" id="pubdate" value="<?php echo $date; ?>" onchange="checkFuturePub();" <?php if ($date > date('Y-m-d H:i:s')) echo 'style="color:blue"'; ?> />
													</p>
													<p>
														<script type="text/javascript">
															
															$(function () {
																$("#expiredate").datepicker({
																	dateFormat: 'yy-mm-dd',
																	showOn: 'button',
																	buttonImage: '<?php echo CALENDAR; ?>',
																	buttonText: '<?php echo gettext('calendar'); ?>',
																	buttonImageOnly: true
																});
															});
															
														</script>

														<?php echo gettext("Expiration date"); ?>  <small>(YYYY-MM-DD)</small>
														<?php $date = $result->getExpireDate(); ?>
														<br />
														<input name="expiredate" type="text" id="expiredate" value="<?php echo $date; ?>" onchange="checkFutureExpiry();" />
														<br />
														<strong class='expire'>
															<?php
															if (!empty($date) && ($date <= date('Y-m-d H:i:s'))) {
																echo '<br />' . gettext('This is not a future date!');
															}
															?>
														</strong>
													</p>
													<?php
													printLastChange($result);

													if (!is_AdminEditPage("newscategory")) {
														if (is_AdminEditPage("newsarticle")) {
															$manager = MANAGE_ALL_NEWS_RIGHTS;
															$rightsDesired = ADMIN_RIGHTS | ZENPAGE_NEWS_RIGHTS;
														} else {
															$manager = MANAGE_ALL_NEWS_RIGHTS;
															$rightsDesired = ADMIN_RIGHTS | ZENPAGE_PAGES_RIGHTS;
														}
														?>
														<p>
															<?php printChangeOwner($result, $rightsDesired, gettext("Author")); ?>
														</p>
														<?php
													}
													if (extensionEnabled('comment_form')) {
														?>
														<p class="checkbox">
															<input name="commentson" type="checkbox" id="commentson" value="1" <?php checkIfChecked($result->getCommentsAllowed()); ?> />
															<label for="commentson"> <?php echo gettext("Comments on"); ?></label>
														</p>
														<?php
													}
													if (!$result->transient && extensionEnabled('hitcounter')) {
														$hc = $result->getHitcounter();
														?>
														<p class="checkbox">
															<input name="resethitcounter" type="checkbox" id="resethitcounter" value="1"<?php if (!$hc) echo ' disabled="disabled"'; ?> />
															<label for="resethitcounter"> <?php printf(ngettext("Reset hitcounter (%u hit)", "Reset hitcounter (%u hits)", $hc), $hc); ?></label>
														</p>
														<?php
													}
													if (extensionEnabled('rating')) {
														?>
														<p class="checkbox">
															<?php
															$tv = $result->get('total_value');
															$tc = $result->get('total_votes');

															if ($tc > 0) {
																$hc = $tv / $tc;
																?>
																<label>
																	<input type="checkbox" id="reset_rating" name="reset_rating" value="1" />
																	<?php printf(gettext('Reset rating (%u stars)'), $hc); ?>
																</label>
																<?php
															} else {
																?>
																<label>
																	<input type="checkbox" name="reset_rating" value="1" disabled="disabled"/>
																	<?php echo gettext('Reset rating (unrated)'); ?>
																</label>
																<?php
															}
															?>
														</p>
														<?php
													}
												} // if !category end
												?>
											</div>

											<?php
											$utilities = npgFilters::apply('edit_cms_utilities', '', $result);
											?>
											<h2 class="h2_bordered_edit"><?php echo gettext("Utilities"); ?></h2>
											<div class="box-edit">
												<?php
												if (!$result->transient) {
													?>
													<label class="checkboxlabel">
														<input type="radio" id="copy_object" name="copy_delete_object" value="copy" onclick="$('#copyfield').show();
																		$('#deletemsg').hide();" />
																	 <?php echo gettext("Copy"); ?>
													</label>
													<label class="checkboxlabel">
														<input type="radio" id="delete_object" name="copy_delete_object" value="delete" onclick="deleteConfirm('delete_object', '', '<?php addslashes(printf(gettext('Are you sure you want to delete this %s?'), $deleteitem)); ?>');
																		$('#copyfield').hide();" />
																	 <?php echo gettext('delete'); ?>
													</label>
													<br class="clearall" />
													<div class = "copydelete resetHide" id = "copyfield" style = "display:none" >
														<?php printf(gettext('copy as: %s'), '<input type="text" name="copy_object_as" value = "" />');
														?>
														<p>
															<?php npgButton('button', CROSS_MARK_RED . ' ' . gettext("Cancel"), array('buttonClick' => "$('#copy_object').prop('checked', false);$('#copyfield').hide();")); ?>
														</p>
													</div>
													<div class="copydelete resetHide" id="deletemsg"	style="padding-top: .5em; padding-left: .5em; color: red; display: none">
														<?php printf(gettext('%s will be deleted when changes are applied.'), $deleteitem);
														?>
														<p>
															<?php npgButton('button', CROSS_MARK_RED . ' ' . gettext("Cancel"), array('buttonClick' => "$('#delete_object').prop('checked', false);$('#deletemsg').hide();")); ?>
														</p>
													</div>
													<?php
												}
												if ($utilities) {
													echo '<hr>';
												}
												echo $utilities;
												?>
											</div>
											<?php
											if (is_AdminEditPage("newsarticle")) {
												?>
												<h2 class="h2_bordered_edit"><?php echo gettext("Categories"); ?></h2>
												<div class="zenpagechecklist">
													<?php
													if (is_object($result)) {
														?>
														<ul>
															<?php printNestedItemsList('cats-checkboxlist', $result->getID()); ?>
														</ul>
														<?php
													} else {
														?>
														<ul>
															<?php printNestedItemsList('cats-checkboxlist', '', 'all'); ?>
														</ul>
														<?php
													}
													?>
													<?php
												} // if article for categories
												?>
											</div>
											<br />

										</div>
									</div>

									<br class="clearall" />
									<?php
									backButton(array('buttonLink' => $backurl));
									applyButton(array('buttonText' => CHECKMARK_GREEN . ' ' . $buttonText));
									resetButton(array('buttonClick' => "$('.copydelete').hide();"));
									?>
									<div class="floatright">
										<a href="<?php echo getAdminLink(PLUGIN_FOLDER . '/zenpage/edit.php') . '?' . $admintype; ?>&amp;add&amp;XSRFToken=<?php echo getXSRFToken('add') ?>" title="<?php echo $additem; ?>">
											<?php echo PLUS_ICON; ?>
											<strong><?php echo $additem; ?></strong>
										</a>
										<?php
										if (!$result->transient) {
											viewButton(array('buttonLink' => $result->getlink()));
										}
										?>
									</div>
									<br class="clearall" />
								</div>
						</form>
						<?php
					}
					?>
				</div>

		</div>
	</div>
	<?php printAdminFooter(); ?>
</div>
</body>
</html>