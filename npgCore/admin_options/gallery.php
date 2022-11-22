<?php
/*
 * Guts of the gallery options tab
 */

$optionRights = OPTIONS_RIGHTS;

function saveOptions() {
	global $_gallery;
	$notify = $returntab = NULL;
	$_gallery->setAlbumPublish((int) isset($_POST['album_default']));
	$_gallery->setImagePublish((int) isset($_POST['image_default']));
	setOption('AlbumThumbSelect', sanitize_numeric($_POST['thumbselector']));
	$_gallery->setThumbSelectImages((int) isset($_POST['thumb_select_images']));
	$_gallery->setSecondLevelThumbs((int) isset($_POST['multilevel_thumb_select_images']));
	$_gallery->setTitle(process_language_string_save('gallery_title', 2));
	$_gallery->setDesc(process_language_string_save('Gallery_description', EDITOR_SANITIZE_LEVEL));
	$_gallery->setWebsiteTitle(process_language_string_save('website_title', 2));
	$_gallery->setLogonWelcome(process_language_string_save('logon_welcome', EDITOR_SANITIZE_LEVEL));
	$limit = sanitize_numeric($_POST['threadConcurrency']);
	setOption('THREAD_CONCURRENCY', $limit);
	$_gallery->setSiteLogo(sanitize_path($_POST['sitelogoimage']));
	$_gallery->setSiteLogoTitle(process_language_string_save('sitelogotitle', EDITOR_SANITIZE_LEVEL));
	$_gallery->setCopyright(process_language_string_save('sitecopyright', EDITOR_SANITIZE_LEVEL));
	$web = sanitize($_POST['website_url'], 3);
	$_gallery->setWebsiteURL($web);
	$_gallery->setAlbumUseImagedate((int) isset($_POST['album_use_new_image_date']));
	$sorttype = strtolower(sanitize($_POST['gallery_sorttype'], 3));
	if ($sorttype == 'custom') {
		if (isset($_POST['customalbumssort'])) {
			$sorttype = implode(',', sanitize($_POST['customalbumssort']));
		} else {
			$sorttype = 'title';
		}
	}
	$_gallery->setSortType($sorttype);
	if (($sorttype == 'manual') || ($sorttype == 'random')) {
		$_gallery->setSortDirection(false);
	} else {
		$_gallery->setSortDirection(isset($_POST['gallery_sortdirection']));
	}
	foreach ($_POST as $item => $value) {
		if (strpos($item, 'gallery-page_') === 0) {
			$encoded = substr($item, 13);
			$item = sanitize(postIndexDecode($encoded));
			$_gallery->setUnprotectedPage($item, (int) isset($_POST['gallery_page_unprotected_' . $encoded]));
		}
	}
	$_gallery->setSecurity(sanitize($_POST['gallery_security'], 3));
	$notify = processCredentials($_gallery);
	if (npg_loggedin(CODEBLOCK_RIGHTS)) {
		processCodeblockSave(0, $_gallery);
	}
	$_gallery->save();
	$returntab = "&tab=gallery";

	return array($returntab, $notify, NULL, NULL, NULL);
}

function getOptionContent() {
	global $_gallery, $_albumthumb_selector, $_sortby;

	codeblocktabsJS();
	?>
	<div id="tab_gallery" class="tabbox">
		<form class="dirtylistening" onReset="toggle_passwords('', false);
				setClean('form_options');" id="form_options" action="?action=saveoptions" method="post" autocomplete="off" >
					<?php XSRFToken('saveoptions'); ?>
			<input type="hidden" name="saveoptions" value="gallery" />
			<input type="hidden" name="password_enabled" id="password_enabled" value="0" />
			<!--	catch and discard browser auto filling of user/password so they do not go where they are not wanted! -->
			<input type="text" id="username" style="width:0;height:0;visibility:hidden;position:absolute;left:0;top:0" />
			<input type="password" style="width:0;height:0;visibility:hidden;position:absolute;left:0;top:0" />
			<p>
				<?php
				applyButton();
				resetButton();
				?>
			</p>
			<br clear="all">
			<div id="columns">
				<table id="npgOptions">
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Gallery title"); ?></td>
						<td class="option_value">
							<?php print_language_string_list($_gallery->getTitle('all'), 'gallery_title', false, null, '', '100%'); ?>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext("What you want to call your site."); ?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Gallery description"); ?></td>
						<td class="option_value">
							<?php print_language_string_list($_gallery->getDesc('all'), 'Gallery_description', true, NULL, 'texteditor', '100%'); ?>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext("A brief description of your gallery. Some themes may display this text."); ?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Branding logo"); ?></td>
						<td class="option_value">
							<?php
							$sitelogo = ltrim(str_replace(WEBPATH, '', $_gallery->getSiteLogo()), '/');
							if ($sitelogo == CORE_FOLDER . '/images/admin-logo.png') {
								$sitelogo = '';
							}
							?>
							<input type="text" style="width:100%;" name="sitelogoimage" value="<?php echo $sitelogo; ?>" onchange="$('#sitelogotitle').show();" />
							<?php
							if ($sitelogo && !file_exists(SERVERPATH . '/' . $sitelogo)) {
								?>
								<br />
								<span style="color: red"><?php echo gettext('The image cannot be found.'); ?></span>
								<?php
							}
							?>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo sprintf(gettext('A relative link to a logo image (e.g. <code>%1$s/custom_logo.png</code> for an image you have uploaded to your <em>%1$s</em> folder.) If this is set, your image will replace the netPhotoGraphics logo. For best results the image should be 78 pixels high.'), UPLOAD_FOLDER); ?>
								</div>
							</span>
						</td>
					</tr>
					<tr id="sitelogotitle"<?php if (empty($sitelogo)) echo ' style="display: none;"'; ?> class="optionSet">
						<td class="option_name"><?php echo gettext("Branding logo title"); ?></td>
						<td class="option_value">
							<?php print_language_string_list($_gallery->getSiteLogoTitle('all'), 'sitelogotitle', false, null, '', '100%'); ?>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext("Enter the title text for your branding logo."); ?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext('Site copyright'); ?></td>
						<td class="option_value">
							<input type="text" style="width:100%;" name="sitecopyright" value="<?php echo $_gallery->getCopyright(); ?>" />
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext("Enter the text for your site copyright."); ?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext('Gallery type'); ?></td>
						<td class="option_value">
							<label>
								<input type="radio" name="gallery_security" value="public" alt="<?php echo gettext('public'); ?>"<?php if (GALLERY_SECURITY == 'public') echo ' checked="checked"' ?> onclick="$('.public_gallery').show();" />
								<?php echo gettext('public'); ?>
							</label>
							<label>
								<input type="radio" name="gallery_security" value="private" alt="<?php echo gettext('private'); ?>"<?php if (GALLERY_SECURITY != 'public') echo 'checked="checked"' ?> onclick="$('.public_gallery').hide();" />
								<?php echo gettext('private'); ?>
							</label>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext('Private galleries are viewable only by registered users.'); ?>
								</div>
							</span>
						</td>
					</tr>
					<?php
					if (GALLERY_SECURITY == 'public') {
						?>
						<tr class="passwordextrashow public_gallery">
							<td class="option_name">
								<a onclick="toggle_passwords('', true);">
									<?php echo gettext("Gallery password"); ?>
								</a>
							</td>
							<td class="option_value">
								<?php
								$x = $_gallery->getPassword();
								$info = password_get_info($x);
								if (empty($x)) {
									?>
									<a onclick="toggle_passwords('', true);" >
										<?php echo LOCK_OPEN; ?>
									</a>
									<?php
								} else {
									$x = '          ';
									?>
									<a onclick="resetPass('');" title="<?php echo gettext('clear password'); ?>">
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
							</td>
							<td class="option_desc">
								<span class="option_info">
									<?php echo INFORMATION_BLUE; ?>
									<div class="option_desc_hidden">
										<p>
											<?php echo gettext("Master password for the gallery. Click on <em>Gallery password</em> to change."); ?>
										</p>
									</div>
								</span>
							</td>
						</tr>
						<tr class="passwordextrahide" style="display:none">
							<td class="option_name">
								<a onclick="toggle_passwords('', false);">
									<?php echo gettext("Gallery guest user"); ?>
								</a>
							</td>
							<td class="option_value">
								<input type="text"
											 class="passignore ignoredirty" autocomplete="off"
											 size="<?php echo TEXT_INPUT_SIZE; ?>"
											 onkeydown="passwordClear('');"
											 id="user_name"  name="user"
											 value="<?php echo html_encode($_gallery->getUser()); ?>" />
							</td>
							<td class="option_desc">
								<span class="option_info">
									<?php echo INFORMATION_BLUE; ?>
									<div class="option_desc_hidden">
										<?php echo gettext("User ID for the gallery guest user") ?>
									</div>
								</span>
							</td>
						</tr>
						<tr class="passwordextrahide" style="display:none" >
							<td class="option_name">
								<span id="strength">
									<?php echo gettext("Gallery password"); ?>
								</span>
								<br />
								<span id="match" class="password_field_">
									&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo gettext("(repeat)"); ?>
								</span>
							</td>
							<td class="option_value">

								<input type="password"
											 class="passignore ignoredirty" autocomplete="off"
											 size="<?php echo TEXT_INPUT_SIZE; ?>"
											 id="pass" name="pass"
											 onkeydown="passwordClear('');"
											 onkeyup="passwordStrength('');"
											 value="<?php echo $x; ?>" />
								<label>
									<input type="checkbox"
												 name="disclose_password"
												 id="disclose_password"
												 onclick="passwordClear('');
														 togglePassword('');" /><?php echo gettext('Show'); ?>
								</label>

								<br />
								<span class="password_field_">
									<input type="password"
												 class="passignore ignoredirty" autocomplete="off"
												 size="<?php echo TEXT_INPUT_SIZE; ?>"
												 id="pass_r" name="pass_r" disabled="disabled"
												 onkeydown="passwordClear('');"
												 onkeyup="passwordMatch('');"
												 value="<?php echo $x; ?>" />
								</span>
							</td>
							<td class="option_desc">
								<span class="option_info">
									<?php echo INFORMATION_BLUE; ?>
									<div class="option_desc_hidden">
										<?php echo gettext("Master password for the gallery. If this is set, visitors must know this password to view the gallery."); ?>
									</div>
								</span>
							</td>
						</tr>
						<tr class="passwordextrahide" style="display:none" >
							<td class="option_name">
								<?php echo gettext("Gallery password hint"); ?>
							</td>
							<td class="option_value">
								<?php print_language_string_list($_gallery->getPasswordHint('all'), 'hint', false, NULL, 'hint'); ?>
							</td>
							<td class="option_desc">
								<span class="option_info">
									<?php echo INFORMATION_BLUE; ?>
									<div class="option_desc_hidden">
										<?php echo gettext("A reminder hint for the password."); ?>
									</div>
								</span>
							</td>
						</tr>
						<?php
					}
					?>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext('Logon welcome'); ?></td>
						<td class="option_value">
							<?php print_language_string_list($_gallery->getLogonWelcome('all'), 'logon_welcome', false, null, '', '100%'); ?>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext('If you place a message here it will be shown on the login form above the password pad box.'); ?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Thread concurrency"); ?></td>
						<td class="option_value">
							<?php
							$max = min(CONCURRENCY_MAX, 60);
							putSlider('', 'threadConcurrency', 1, $max, THREAD_CONCURRENCY);
							?>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php
									echo gettext('Limit to the number of front-end scripts that will execute concurrently.');
									?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext('Unprotected pages'); ?></td>
						<td class="option_value">
							<?php
							$curdir = getcwd();
							$root = SERVERPATH . '/' . THEMEFOLDER . '/' . $_gallery->getCurrentTheme() . '/';
							chdir($root);
							$filelist = safe_glob('*.php');
							$list = array();
							foreach ($filelist as $file) {
								$file = filesystemToInternal($file);
								$list[$file] = str_replace('.php', '', $file);
							}
							chdir($curdir);
							$list = array_diff($list, standardScripts(array()));
							$list['index.php'] = 'index';
							$current = array();
							foreach ($list as $page) {
								?>
								<input type="hidden" name="gallery-page_<?php echo postIndexEncode($page); ?>" value="0" />
								<?php
								if ($_gallery->isUnprotectedPage($page)) {
									$current[] = $page;
								}
							}
							?>
							<ul class="shortchecklist">
								<?php generateUnorderedListFromArray($current, $list, 'gallery_page_unprotected_', false, true, true); ?>
							</ul>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext('Place a checkmark on any page scripts which should not be protected by the gallery password.'); ?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Website title"); ?></td>
						<td class="option_value">
							<?php print_language_string_list($_gallery->getWebsiteTitle('all'), 'website_title', false, null, '', '100%'); ?>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext("Your web site title."); ?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Website url"); ?></td>
						<td class="option_value"><input type="text" name="website_url" style="width:100%;"
																						value="<?php echo html_encode($_gallery->getWebsiteURL()); ?>" /></td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext("This is used to link back to your main site, but your theme must support it."); ?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Album thumbnails"); ?></td>
						<td class="option_value">
							<?php
							$selections = array();
							foreach ($_albumthumb_selector as $key => $selection) {
								$selections[$selection['desc']] = $key;
							}
							?>
							<select id="thumbselector" name="thumbselector">
								<?php
								generateListFromArray(array(getOption('AlbumThumbSelect')), $selections, false, true);
								?>
							</select>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext("Default thumbnail selection for albums."); ?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Sort gallery by"); ?></td>
						<td class="option_value">
							<?php
							$sort = $_sortby;
							$sort[gettext('Manual')] = 'manual';
							$sort[gettext('Custom')] = 'custom';
							/*
							 * not recommended--screws with peoples minds during pagination!

							  $sort[gettext('Random')] = 'random';
							 */
							$cvt = $cv = strtolower($_gallery->getSortType());
							ksort($sort, SORT_LOCALE_STRING);
							$flip = array_flip($sort);
							if (isset($flip[$cv])) {
								$dspc = 'style="margin-top:5px;display:none"';
							} else {
								$dspc = 'style="margin-top:5px;"';
							}
							if (($cv == 'manual') || ($cv == 'random') || ($cv == '')) {
								$dspd = 'none';
							} else {
								$dspd = 'inline-block';
							}
							if (array_search($cv, $sort) === false) {
								$cv = 'custom';
							}
							?>
							<select id="gallerysortselect" name="gallery_sorttype" onchange="update_direction(this, 'gallery_sortdirection', 'customTextBox2')">
								<?php
								generateListFromArray(array($cv), $sort, false, true);
								?>
							</select>
							<span id="gallery_sortdirection" style="display:<?php echo $dspd; ?>">
								<label>
									<input type="checkbox" name="gallery_sortdirection"	value="1" <?php checked('1', $_gallery->getSortDirection()); ?> />
									<?php echo gettext("descending"); ?>
								</label>
							</span>

							<div id="customTextBox2" class="customText" <?php echo $dspc; ?>>
								<?php echo gettext('custom fields') ?>
								<span class="tagSuggestContainer">
									<span class="tagSuggestContainer">
										<ul class="searchchecklist">
											<?php dbFieldSelector('albums', $cvt); ?>
										</ul>
									</span>
								</span>
							</div>

						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php
									echo gettext('Sort order for the albums on the index of the gallery. Custom sort values must be database field names. You can have multiple fields separated by commas. This option is also the default sort for albums and subalbums.');
									?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Gallery behavior"); ?></td>
						<td class="option_value">
							<label>
								<input type="checkbox" name="album_default"	value="1"<?php if ($_gallery->getAlbumPublish()) echo ' checked="checked"'; ?> />
								<?php echo gettext("publish albums by default"); ?>
							</label>
							<br />
							<label>
								<input type="checkbox" name="image_default"	value="1"<?php if ($_gallery->getImagePublish()) echo ' checked="checked"'; ?> />
								<?php echo gettext("publish images by default"); ?>
							</label>
							<br />
							<label>
								<input type="checkbox" name="album_use_new_image_date" id="album_use_new_image_date"
											 value="1" <?php checked('1', $_gallery->getAlbumUseImagedate()); ?> />
											 <?php echo gettext("use latest image date as album date"); ?>
							</label>
							<br />
							<label>
								<input type="checkbox" name="thumb_select_images" id="thumb_select_images"
											 value="1" <?php checked('1', $_gallery->getThumbSelectImages()); ?> />
											 <?php echo gettext("visual thumb selection"); ?>
							</label>
							<br />
							<label>
								<input type="checkbox" name="multilevel_thumb_select_images" id="multilevel_thumb_select_images"
											 value="1" <?php checked('1', $_gallery->getSecondLevelThumbs()); ?> />
											 <?php echo gettext("show subalbum thumbs"); ?>
							</label>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">

									<p><?php echo gettext("<em>publish albums by default</em> sets the default behavior for when an album is discovered. If checked, the album will be published, if unchecked it will be unpublished.") ?></p>

									<p><?php echo gettext("<em>publish images by default</em> sets the default behavior for when an image is discovered. If checked, the image will be published, if unchecked it will be unpublished.") ?></p>

									<p>
										<?php echo gettext("If you wish your album date to reflect the date of the latest image uploaded set <em>use latest image date as album date</em>. Otherwise the date will be set initially to the date the album was created.") ?>
									</p>
									<p class="notebox">
										<?php echo gettext('<strong>NOTE</strong>: The album date will be updated only if an image is discovered which is newer than the current date of the album.'); ?>
									</p>

									<p><?php echo gettext("Setting <em>visual thumb selection</em> places thumbnails in the album thumbnail selection list (the dropdown list on each album’s edit page). In Firefox the dropdown shows the thumbs, but in IE and Safari only the names are displayed (even if the thumbs are loaded!). In albums with many images loading these thumbs takes much time and is unnecessary when the browser will not display them. Uncheck this option and the images will not be loaded. "); ?></p>

									<p><?php echo gettext("Setting <em>subalbum thumb selection</em> allows selecting images from subalbums as well as from the album. Naturally populating these images adds overhead. If your album edit tabs load too slowly, do not select this option."); ?></p>

								</div>
							</span>
						</td>
					</tr>

					<tr valign="top">
						<td class="topalign-nopadding"><br /><?php echo gettext("Codeblocks"); ?></td>
						<td>
							<?php $hint = printCodeblockEdit($_gallery, 0, FALSE); ?>
						</td>
						<td>
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo $hint; ?>
								</div>
							</span>
						</td>
					</tr>
				</table>
			</div>
			<p>
				<?php
				applyButton();
				resetButton();
				?>
			</p>
			<br clear="all">
		</form>
	</div>
	<!-- end of tab-gallery div -->
	<?php
}
