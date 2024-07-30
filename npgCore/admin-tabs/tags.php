<?php
/**
 * provides the TAGS tab of admin
 *
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 */
define('OFFSET_PATH', 1);
require_once(dirname(__DIR__) . '/admin-globals.php');
require_once(CORE_SERVERPATH . 'template-functions.php');

admin_securityChecks(TAGS_RIGHTS, currentRelativeURL());

$_GET['page'] = 'tags';

$tagsort = getTagOrder();

$action = '';
if (isset($_GET['action'])) {
	$subaction = array();
	switch ($_GET['action']) {
		case 'newtags':
			XSRFdefender('new_tags');
			$language = sanitize($_POST['language']);
			$multi = getOption('multi_lingual') && !empty($language);
			foreach ($_POST['new_tag'] as $value) {
				if (!empty($value)) {
					$value = html_decode(sanitize($value, 3));
					$master = create_update_tag($value, $language);
					if ($master) {
						if ($multi) {
							foreach (i18n::generateLanguageList(false)as $text => $dirname) {
								if ($dirname != $language) {
									create_update_tag($value, $language, NULL, $master);
								}
							}
						}
					} else {
						$subaction[] = ltrim(sprintf(gettext('%1$s: %2$s not stored, duplicate tag.'), $language, $value), ': ');
					}
				}
			}
			$action = gettext('New tags added');
			break;
		case 'tag_action':
			XSRFdefender('tag_action');
			$language = sanitize($_POST['language']);
			$action = $_POST['tag_action'];
			if (isset($_POST['tag_list_tags_'])) {
				$tags = sanitize($_POST['tag_list_tags_']);
			} else {
				$tags = array();
			}
			if (isset($_POST['lang_list_tags_'])) {
				$langs = sanitize($_POST['lang_list_tags_']);
			} else {
				$langs = array();
			}

			switch ($action) {
				case'delete':
					if (count($tags) > 0) {
						$sql = "SELECT `id`, `masterid` FROM " . prefix('tags') . " WHERE ";
						foreach ($tags as $key => $tag) {
							if (isset($langs[$key])) {
								$lang = $langs[$key];
							} else {
								$lang = '';
							}
							$sql .= "(`name`=" . (db_quote($tag)) . ' AND `language`=' . db_quote($lang) . ") OR ";
						}
						$sql = substr($sql, 0, strlen($sql) - 4);
						$dbtags = query_full_array($sql);
						if (is_array($dbtags) && count($dbtags) > 0) {
							$sqltags = "DELETE FROM " . prefix('tags') . " WHERE ";
							$sqlobjects = "DELETE FROM " . prefix('obj_to_tag') . " WHERE ";
							foreach ($dbtags as $tag) {
								$sqltags .= "`id`='" . $tag['id'] . "' OR ";
								$sqlobjects .= "`tagid`='" . $tag['id'] . "' OR ";
								if (is_null($tag['masterid'])) {
									$sqltags .= "`masterid`='" . $tag['id'] . "' OR ";
									$sqlSub = "SELECT `id`, `masterid` FROM " . prefix('tags') . " WHERE `masterid`=" . $tag['id'];
									$subTags = query_full_array($sqlSub);
									if (is_array($subTags) && count($subTags) > 0) {
										foreach ($subTags as $subTag) {
											$sqlobjects .= "`tagid`='" . $subTag['id'] . "' OR ";
										}
									}
								}
							}
							$sqltags = substr($sqltags, 0, strlen($sqltags) - 4);
							query($sqltags);
							$sqlobjects = substr($sqlobjects, 0, strlen($sqlobjects) - 4);
							query($sqlobjects);
						}
					}
					$action = gettext('Checked tags deleted');
					break;
				case 'private':
				case 'notprivate':
					$private = (int) ($action == 'private');
					if (count($tags) > 0) {
						$sql = "UPDATE " . prefix('tags') . " SET `private`=$private WHERE ";
						foreach ($tags as $key => $tag) {
							if (isset($langs[$key])) {
								$lang = $langs[$key];
							} else {
								$lang = NULL;
							}
							$sql .= "(`name`=" . (db_quote($tag)) . ' AND `language`=' . db_quote($lang) . ") OR ";
						}
						$sql = substr($sql, 0, strlen($sql) - 4);
						query($sql);
					}
					if ($private) {
						$action = gettext('Checked tags marked private');
					} else {
						$action = gettext('Checked tags marked public');
					}
					break;
				case'assign':
					if (count($tags) > 0) {
						$tbdeleted = array();
						$multi = getOption('multi_lingual');
						$languageList = i18n::generateLanguageList(false);
						foreach ($tags as $key => $tagname) {
							if (isset($langs[$key])) {
								$lang = $langs[$key];
							} else {
								$lang = NULL;
							}
							$sql = 'UPDATE ' . prefix('tags') . ' SET `language`=' . db_quote($language) . ' WHERE `name`=' . db_quote($tagname) . ' AND `language`=' . db_quote($lang);
							$success = query($sql, false);
							if ($success) {
								$sql = 'SELECT `id` FROM ' . prefix('tags') . ' WHERE `name`=' . db_quote($tagname) . ' AND `language`=' . db_quote($language);
								$tagelement = query_single_row($sql);
								if ($multi && !empty($language)) {
									//create subtags
									foreach ($languageList as $text => $dirname) {
										if ($dirname != $language) {
											create_update_tag($tagname, $dirname, NULL, $tagelement['id']);
										}
									}
								} else if (empty($language)) {
									$tbdeleted[] = $tagelement['id'];
								}
							} else {
								$subaction[] = ltrim(sprintf(gettext('%1$s: %2$s language not changed, duplicate tag.'), $lang, $tagname), ': ');
							}
							if (!empty($tbdeleted)) {
								$sql = 'DELETE FROM ' . prefix('tags') . ' WHERE `masterid`=' . implode(' OR `masterid`=', $tbdeleted);
								query($sql);
							}
						}
					}
					$action = gettext('Checked tags language assigned');
					break;
			}
			break;
		case 'rename':
			XSRFdefender('tag_rename');
			$oldNames = $_POST['oldname'];
			$newNames = $_POST['newname'];
			$languages = $_POST['language'];
			foreach ($newNames as $key => $newName) {
				if (!empty($newName) && $newName != $oldNames[$key]) {
					$lang = $languages[$key];
					$newName = sanitize($newName, 3);
					$newtag = query_single_row('SELECT `id` FROM ' . prefix('tags') . ' WHERE LOWER(`name`)=LOWER(' . db_quote($newName) . ') AND `language`=' . db_quote($lang));
					$oldtag = query_single_row('SELECT `id` FROM ' . prefix('tags') . ' WHERE LOWER(`name`)=LOWER(' . db_quote($oldNames[$key]) . ') AND `language`=' . db_quote($lang));
					if (is_array($newtag)) { // there is an existing tag of the same name
						$existing = $newtag['id'] != $oldtag['id']; // but maybe it is actually the original in a different case.
					} else {
						$existing = false;
					}
					if ($existing) {
						$subaction[] = ltrim(sprintf(gettext('%1$s: %2$s not changed, duplicate tag.'), $lang, $oldNames[$key]), ': ');
					} else if (!empty($oldtag)) {
						query('UPDATE ' . prefix('tags') . ' SET `name` = ' . db_quote($newName) . ' WHERE `id` = ' . $oldtag['id']);
					}
				}
			}
			$action = gettext('Tags renamed');
			break;
	}
}

printAdminHeader('admin');
?>
</head>
<body>
	<?php
	printLogoAndLinks();
	?>
	<div id="main">
		<?php
		printTabs();
		?>
		<div id="content">
			<?php
			if (!empty($action)) {
				?>
				<div class = "messagebox fade-message">
					<h2><?php echo $action; ?></h2>
				</div>
				<?php
				if (!empty($subaction)) {
					?>
					<div class = "errorbox">
						<?php
						$br = '';
						foreach ($subaction as $action) {
							$flag = '';
							if (preg_match('~([a-z]{2}_*[A-Z]{0, 2}.*):\s*(.*)~', $action, $matches)) {
								$action = $matches[2];
								if ($matches[1]) {
									$flag = '<img src = "' . getLanguageFlag($matches[1]) . '" height = "10" width = "15" /> ';
								}
							}
							echo $br . $flag . $action;
							$br = '<br />';
						}
						?>
					</div>
					<?php
				}
			}

			npgFilters::apply('admin_note', 'tags', '');
			echo "<h1>" . gettext("Tag Management") . "</h1>";
			echo gettext('Order by');
			?>

			<select name="tagsort" id="tagsort_selector" class="ignoredirty" onchange="window.location = '?tagsort=' + $('#tagsort_selector').val();">
				<option value = "alpha" <?php if ($tagsort == 'alpha') echo ' selected="selected"'; ?>><?php echo gettext('Alphabetic'); ?></option>
				<option value="mostused" <?php if ($tagsort == 'mostused') echo ' selected="selected"'; ?>><?php echo gettext('Most used'); ?></option>
				<option value="language" <?php if ($tagsort == 'language') echo ' selected="selected"'; ?>><?php echo gettext('Language'); ?></option>
				<option value="recent" <?php if ($tagsort == 'recent') echo ' selected="selected"'; ?>><?php echo gettext('Most recent'); ?></option>
				<option value="private" <?php if ($tagsort == 'private') echo ' selected="selected"'; ?>><?php echo gettext('Private first'); ?></option>
			</select>
			<div floatright" style="padding-bottom: 5px;">
				<?php resetButton(array('buttonClick' => "$('#tag_action_form').trigger('reset');
						$('#form_tagrename').trigger('reset');
						$('#form_newtags').trigger('reset');")); ?>
			</div>

			<br class="clearall" />
			<p class="notebox">
				<?php echo gettext('Indented tags are language translations of the superior (master) tag. If you delete a master tag, the language translations will also be deleted.'); ?>
			</p>
			<div class="tabbox">
				<div class="floatleft">
					<h2 class="h2_bordered_edit"><?php echo gettext("Tags"); ?>
						<label id="autocheck" style="float:right">
							<input type="checkbox" name="checkAllAuto" id="checkAllAuto" onclick="$('.checkTagsAuto').prop('checked', $('#checkAllAuto').prop('checked'));"/>
							<span id="autotext"><?php echo gettext('all'); ?></span>
						</label>
					</h2>
					<form onReset="setClean('tag_action_form');" name="tag_action_form" id="tag_action_form" action="?action=tag_action&amp;tagsort=<?php echo html_encode($tagsort); ?>" method="post" autocomplete="off" >
						<?php XSRFToken('tag_action'); ?>
						<input type="hidden" name="tag_action" id="tag_action" value="delete" />
						<div class="box-tags-unpadded">
							<?php
							tagSelector(NULL, 'tags_', true, $tagsort, false);
							$list = $_admin_ordered_taglist;
							?>
						</div>

						<p>
							<?php npgButton('button', WASTEBASKET . ' ' . gettext("Delete checked tags"), array('buttonClick' => "$('#tag_action').val('delete');	this.form.submit();")); ?>
						</p>
						<p>
							<?php npgButton('button', LOCK . ' ' . gettext("Mark checked tags private"), array('buttonClick' => "$('#tag_action').val('private');	this.form.submit();")); ?>
						</p>
						<p>
							<?php npgButton('button', LOCK_OPEN . ' ' . gettext("Mark checked tags public"), array('buttonClick' => "$('#tag_action').val('notprivate');	this.form.submit();")); ?>
						</p>

						<?php
						if (getOption('multi_lingual')) {
							npgButton('button', ARROW_RIGHT_BLUE . ' ' . gettext("Assign to"), array('buttonTitle' => gettext('Assign tags to selected language'), 'buttonClick' => "$('#tag_action').val('assign'); this.form.submit();"))
							?>
							<span style="line-height: 35px;">
								<select name="language" id="language" class="ignoredirty" >
									<option value=""><?php echo gettext('Universal'); ?></option>
									<?php
									foreach ($_active_languages as $text => $lang) {
										?>
										<option value="<?php echo $lang; ?>"><?php echo html_encode($text); ?></option>
										<?php
									}
									?>
								</select>
							</span>
							<?php
						} else {
							?>
							<input type="hidden" name="language" value="" />
							<?php
						}
						?>
						<div class="clearall"></div>
					</form>

					<div class="tagtext" style="padding-top: 7px;">
						<p><?php
							echo gettext('Place a checkmark in the box for each tag you wish to act upon then press the appropriate button. The brackets contain the number of times the tag appears.');
							echo gettext('Tags that are <span class="privatetag">highlighted</span> are private.');
							?></p>
					</div>
				</div>

				<div class="floatleft">
					<h2 class="h2_bordered_edit"><?php echo gettext("Rename tags"); ?></h2>
					<form class="dirtylistening" onReset="setClean('form_tagrename');" name="tag_rename" id="form_tagrename" action="?action=rename&amp;tagsort=<?php echo html_encode($tagsort); ?>" method="post" autocomplete="off" >
						<?php XSRFToken('tag_rename'); ?>
						<div class="box-tags-unpadded">
							<ul class="tagrenamelist">
								<?php
								foreach ($list as $tagitem) {
									$item = html_encode($tagitem['tag']);
									?>
									<li>
										<span class="nowrap">
											<?php
											if ($lang = $tagitem['lang']) {
												?>
												<img src="<?php echo getLanguageFlag($lang); ?>" height="10" width="16" title="<?php echo i18n::getDisplayName($lang); ?>" />
												<?php
											}
											?>
											<input name="newname[]" type="text" size='33' value="<?php echo $item; ?>" />
										</span>
										<input type="hidden" name="oldname[]" value="<?php echo $item; ?>">
										<input type="hidden" name="language[]" value="<?php echo html_encode($lang); ?>" />

										<?php
										if (is_array($tagitem['subtags'])) {
											$itemarray = $tagitem['subtags'];
											ksort($itemarray);
											foreach ($itemarray as $lang => $tagitem) {
												$tag = html_encode($tagitem['tag']);
												?>
												<span class="nowrap">
													&nbsp;&nbsp;<img src="<?php echo getLanguageFlag($lang); ?>" height="10" width="16" title="<?php echo i18n::getDisplayName($lang); ?>" />
													<input name="newname[]" type="text" size='33' value="<?php echo $tag; ?>"/>
												</span>
												<input type="hidden" name="oldname[]" value="<?php echo $tag; ?>">
												<input type="hidden" name="language[]" value="<?php echo html_encode($lang); ?>" />
												<?php
											}
										}
										?>
									</li>
									<?php
								}
								?>
							</ul>
						</div>
						<p>
							<?php applyButton(array('buttonText' => CHECKMARK_GREEN . ' ' . gettext("Rename tags"))); ?>
						</p>
						<div class="clearall"></div>
					</form>

					<div class="tagtext" style="padding-top: 7px;">
						<p><?php echo gettext('To change the value of a tag enter a new value in the text box below the tag. Then press the <em>Rename tags</em> button'); ?></p>
					</div>
				</div>

				<div class="floatleft">
					<h2 class="h2_bordered_edit"><?php echo gettext("New tags"); ?></h2>
					<form class="dirtylistening" onReset="setClean('form_newtags');"  name="new_tags" id="form_newtags" action="?action=newtags&amp;tagsort=<?php echo html_encode($tagsort); ?>" method="post" autocomplete="off" >
						<?php XSRFToken('new_tags'); ?>
						<div class="box-tags-unpadded">
							<ul class="tagnewlist">
								<?php
								for ($i = 0; $i < 40; $i++) {
									?>
									<li>
										<input name="new_tag[]" type="text" size='33'/>
									</li>
									<?php
								}
								?>
							</ul>
						</div>
						<span <?php if (getOption('multi_lingual')) echo ' style = "padding-bottom: 25px;"'; ?>>
							<?php
							applyButton(array('buttonText' => PLUS_ICON . ' ' . gettext("Add tags")));
							if (getOption('multi_lingual')) {
								?>
								<span style="line-height: 35px;">
									<select name="language" id="language" class="ignoredirty">
										<option value="" selected="language"><?php echo gettext('Universal'); ?></option>
										<?php
										foreach ($_active_languages as $text => $lang) {
											?>
											<option value="<?php echo $lang; ?>" ><?php echo html_encode($text); ?></option>
											<?php
										}
										?>
									</select>
								</span>
								<?php
							} else {
								?>
								<input type="hidden" name="language" value="" />
								<?php
							}
							?>
						</span>
						<div class="clearall"></div>
					</form>

					<div class="tagtext" style="padding-top: 7px;">
						<p><?php
							echo gettext("Add tags to the list by entering their names in the input fields of the <em>New tags</em> list. Then press the <em>Add tags</em> button.");
							if (getOption('multi_lingual')) {
								echo ' ' . gettext('You can assign a language to the tags with the language selector.');
							}
							?></p>
					</div>
				</div>
				<br class="clearall" />
			</div>

		</div>
		<?php
		printAdminFooter();
		?>
	</div>
</body>
</html>




