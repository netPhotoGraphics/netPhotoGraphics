<?php
/*
 * General options
 */
$optionRights = ADMIN_RIGHTS;

function saveOptions() {
	global $_gallery;

	$notify = $returntab = NULL;
	$returntab = "&tab=general";
	$tags = strtolower(sanitize($_POST['allowed_tags'], 0));

	$a = parseAllowedTags($tags);
	if (is_array($a)) {
		setOption('allowed_tags', $tags);
	} else {
		debugLog('tag parse error:' . $a);
		$notify .= '&tag_parse_error';
	}



	$oldloc = SITE_LOCALE; // get the option as stored in the database, not what might have been set by a cookie
	$newloc = sanitize($_POST['locale'], 3);
	$languages = i18n::generateLanguageList(true);
	$languages[''] = '';

	$disallow = array();
	foreach ($languages as $text => $lang) {
		if ($lang != $newloc && !isset($_POST['language_allow']['_' . $lang])) {
			$disallow[$lang] = $lang;
		}
	}

	if ($newloc != $oldloc) {
		$oldDisallow = getSerializedArray(getOption('locale_disallowed'));
		if (!empty($newloc) && isset($oldDisallow[$newloc])) {
			$notify .= '&local_failed=' . $newloc;
		} else {
			clearNPGCookie('dynamic_locale'); // clear the language cookie
			$result = i18n::setLocale($newloc);
			if (!empty($newloc) && ($result === false)) {
				$notify .= '&local_failed=' . $newloc;
			}
			setOption('locale', $newloc);
		}
	}
	setOption('locale_disallowed', serialize($disallow));

	setOption('mod_rewrite', (int) isset($_POST['mod_rewrite']));

	$oldsuffix = getOption('mod_rewrite_suffix');
	$newsuffix = sanitize($_POST['mod_rewrite_suffix'], 3);
	setOption('mod_rewrite_suffix', $newsuffix);
	if ($oldsuffix != $newsuffix) {
		require_once(CORE_SERVERPATH . 'setup/setup-functions.php');
		if (!updateRootIndexFile()) {
			$notify .= '&root_update_failed';
			setOption('mod_rewrite_suffix', $oldsuffix);
			$oldsuffix = NULL; //	prevent migrating the CMS links
		}
		if (!is_null($oldsuffix)) {
			//the suffix was changed as opposed to set for the first time
			migrateTitleLinks($oldsuffix, $newsuffix);
		}
	}
	setOption('unique_image_prefix', (int) isset($_POST['unique_image_prefix']));
	if (isset($_POST['time_zone'])) {
		setOption('time_zone', sanitize($_POST['time_zone'], 3));
		$offset = 0;
	} else {
		$offset = sanitize($_POST['time_offset'], 3);
	}
	setOption('time_offset', $offset);
	setOption('FILESYSTEM_CHARSET', sanitize($_POST['filesystem_charset']));

	$_gallery->setGallerySession((int) isset($_POST['album_session']));
	$_gallery->save();
	if (isset($_POST['cookie_path'])) {
		$p = sanitize($_POST['cookie_path']);
		if (empty($p)) {
			clearNPGCookie('cookie_path');
		} else {
			$p = '/' . trim($p, '/') . '/';
			if ($p == '//') {
				$p = '/';
			}
			//	save a cookie to see if change works
			$returntab .= '&cookiepath';
			setNPGCookie('cookie_path', $p, 600);
		}
		setOption('cookie_path', $p);
		if (isset($_POST['cookie_persistence'])) {
			setOption('cookie_persistence', sanitize_numeric($_POST['cookie_persistence']));
		}
	}

	setOption('GDPR_acknowledge', (int) isset($_POST['GDPR_acknowledge']));
	setOption('GDPR_text', process_language_string_save('GDPR_text', 4));
	setOption('GDPR_URL', sanitize($_POST['GDPR_URL']));
	if (isset($_POST['GDPR_re-acknowledge']) && $_POST['GDPR_re-acknowledge']) {
		$sql = 'UPDATE ' . prefix('administrators') . ' SET `policyACK`=0';
		query($sql);
		setOption('GDPR_cookie', md5(microtime()));
		npgFilters::apply('policy_ack', true, 'policyACK', NULL, gettext('All acknowledgements cleared'));
	}

	$email = sanitize($_POST['site_email']);
	if (empty($email) || npgFunctions::isValidEmail($email)) {
		setOption('site_email', $email);
	} else {
		$notify .= '&Invalid_email_format';
	}

	setOption('site_email_name', process_language_string_save('site_email_name', 3));
	setOption('dirtyform_enable', sanitize_numeric($_POST['dirtyform_enable']));
	setOption('multi_lingual', (int) isset($_POST['multi_lingual']));
	$f = sanitize($_POST['date_format_list'], 3);
	if ($f == 'custom')
		$f = sanitize($_POST['date_format'], 3);
	setOption('date_format', $f);
	setOption('UTF8_image_URI', (int) !isset($_POST['UTF8_image_URI']));
	foreach ($_POST as $key => $value) {
		if (preg_match('/^log_size.*_(.*)$/', $key, $matches)) {
			setOption($matches[1] . '_log_size', $value);
			setOption($matches[1] . '_log_mail', (int) isset($_POST['log_mail_' . $matches[1]]));
		}
	}
	if ($notify) {
		$notify = '?' . ltrim($notify, '&');
	}

	return array($returntab, $notify, NULL, NULL, NULL);
}

function getOptionContent() {
	global $_gallery, $_server_timezone, $_UTF8, $_authority;
	?>
	<script type="text/javascript">
		// <!-- <![CDATA[
		var oldselect = '<?php echo SITE_LOCALE; ?>';
		function radio_click(id) {
			if ($('#r_' + id).prop('checked')) {
				$('#language_allow_' + oldselect).prop('disabled', false);
				oldselect = id;
				$('#language_allow_' + id).prop('disabled', true);
			}
		}
		function enable_click(id) {
			if ($('#language_allow_' + id).prop('checked')) {
				$('#r_' + id).prop('disabled', false);
			} else {
				$('#r_' + id).prop('disabled', true);
			}
		}

		// ]]> -->
	</script>
	<div id="tab_gallery" class="tabbox">
		<?php
		if (isset($_GET['local_failed'])) {
			$languages = array_flip(i18n::generateLanguageList('all'));
			$locale = sanitize($_GET['local_failed']);
			echo '<div class="errorbox">';
			echo "<h2>" .
			sprintf(gettext("<em>%s</em> is not available."), html_encode($languages[$locale])) .
			' ' . sprintf(gettext("The locale %s is not supported on your server."), html_encode($locale)) .
			"</h2>";
			echo gettext('You can use the <em>debug</em> plugin to see which locales your server supports.');
			echo '</div>';
		}
		if (isset($_GET['root_update_failed'])) {
			echo '<div class="errorbox">';
			echo "<h2>" .
			gettext("Could not update the root index.php file") .
			"</h2>";
			echo gettext("Perhaps there is a permissions issue. Your <em>mod_rewrite suffix</em> was not changed.");
			echo '</div>';
		}
		if (isset($_GET['Invalid_email_format'])) {
			echo '<div class="errorbox">';
			echo "<h2>" .
			gettext("Invaid email address") .
			"</h2>";
			echo gettext("The gallery email address is not valid.");
			echo '</div>';
		}
		?>
		<form class="dirtylistening" onReset="setClean('form_options');" id="form_options" action="<?php echo getAdminLink('admin-tabs/options.php'); ?>?action=saveoptions" method="post" autocomplete="off" >
			<?php XSRFToken('saveoptions'); ?>
			<input	type="hidden" name="saveoptions" value="general" />
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
						<?php
						if (function_exists('date_default_timezone_get')) {
							$offset = timezoneDiff($_server_timezone, $tz = getOption('time_zone'));
							setOption('time_offset', $offset);
							?>
							<td class="option_name"><?php echo gettext("Time zone"); ?></td>
							<td class="option_value">
								<?php
								$zones = getTimezones();
								?>
								<select id="time_zone" name="time_zone">
									<option value="" style="background-color:LightGray"><?php echo gettext('*not specified'); ?></option>
									<?php generateListFromArray(array($tz), $zones, false, false); ?>
								</select>
							</td>
							<td class="option_desc">
								<span class="option_info">
									<?php echo INFORMATION_BLUE; ?>
									<div class="option_desc_hidden">
										<p><?php printf(gettext('Your server reports its time zone as: <code>%s</code>.'), $_server_timezone); ?></p>
										<p><?php printf(ngettext('Your time zone offset is %d hour. If your time zone is different from the servers, select the correct time zone here.', 'Your time zone offset is: %d hours. If your time zone is different from the servers, select the correct time zone here.', $offset), $offset); ?></p>
									</div>
								</span>
							</td>
							<?php
						} else {
							$offset = getOption('time_offset');
							?>
							<td class="option_name"><?php echo gettext("Time offset (hours)"); ?></td>
							<td class="option_value">
								<input type="text" size="3" name="time_offset" value="<?php echo html_encode($offset); ?>" />
							</td>
							<td class="option_desc">
								<span class="option_info">
									<?php echo INFORMATION_BLUE; ?>
									<div class="option_desc_hidden">
										<p><?php echo gettext("If you are in a different time zone from your server, set the offset in hours of your time zone from that of the server. For instance if your server is on the US East Coast (<em>GMT</em> - 5) and you are on the Pacific Coast (<em>GMT</em> - 8), set the offset to 3 (-5 - (-8))."); ?></p>
									</div>
								</span>
							</td>
							<?php
						}
						?>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("URL options"); ?></td>
						<td class="option_value">
							<?php
							if (MOD_REWRITE) {
								$state = ' checked="checked"';
							} else {
								$state = '';
							}
							if (!getOption('mod_rewrite_detected')) {
								$disable = ' disabled="disabled"';
							} else {
								$disable = '';
							}
							?>
							<label>
								<input type="checkbox" name="mod_rewrite" value="1"<?php echo $state . $disable; ?> />	<?php echo gettext('mod rewrite'); ?>
							</label>
							<br />

							<?php echo gettext("mod_rewrite suffix"); ?> <input type="text" size="10" name="mod_rewrite_suffix" value="<?php echo html_encode(getOption('mod_rewrite_suffix')); ?>"<?php echo $disable; ?> />
							<br />
							<?php
							if (FILESYSTEM_CHARSET != LOCAL_CHARSET) {
								?>
								<label>
									<input type="checkbox" name="UTF8_image_URI"<?php echo $disable; ?> value="1"<?php checked('0', UTF8_IMAGE_URI) ?> />	<?php echo gettext('<em>filesystem</em> image URIs'); ?>
								</label>
								<br />
								<?php
							}
							if (UNIQUE_IMAGE) {
								$unique = ' checked="checked"';
							} else {
								$unique = '';
							}
							?>
							<label>
								<input type="checkbox" name="unique_image_prefix"<?php echo $unique . $disable; ?>> <?php echo gettext("unique images"); ?>
							</label>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<p>
										<?php
										echo gettext("If you have Apache <em>mod rewrite</em> (or equivalent), put a checkmark on the <em>mod rewrite</em> option and you will get nice cruft-free URLs.");
										echo sprintf(gettext('The <em>tokens</em> used in rewritten URIs may be altered to your taste. See the <a href="%s">plugin options</a> for <code>rewriteTokens</code>.'), getAdminLink('admin-tabs/options.php') . '?page=options&tab=plugin&single=rewriteTokens');
										if (!getOption('mod_rewrite_detected'))
											echo '<p class="notebox">' . gettext('Setup did not detect a working <em>mod_rewrite</em> facility.'), '</p>';
										?>
									</p>
									<p><?php echo gettext("If <em>mod_rewrite</em> is checked above, the <em>mod_rewrite suffix</em> will be appended to the end of URLs. (This helps search engines.) Examples: <em>.htm, .view</em>, etc."); ?></p>
									<p>
										<?php
										if (FILESYSTEM_CHARSET != LOCAL_CHARSET) {
											echo '<p>' . gettext("If you are having problems with images whose names contain characters with diacritical marks try changing the <em>image URI</em> setting.");
											switch (getOption('UTF8_image_URI_found')) {
												case'unknown':
													echo '<p class="notebox">' . gettext('Setup could not determine a setting that allowed images with diacritical marks in the name.'), '</p>';
													break;
												case 'internal':
													if (!getOption('UTF8_image_URI')) {
														echo '<p class="notebox">' . sprintf(gettext('Setup detected <em>%s</em> image URIs.'), LOCAL_CHARSET), '</p>';
													}
													break;
												case 'filesystem':
													if (getOption('UTF8_image_URI')) {
														echo '<p class="notebox">' . gettext('Setup detected <em>file system</em> image URIs.'), '</p>';
													}
													break;
											}
											echo '</p>';
										}
										?>

										<?php
										printf(gettext('If <em>Unique images</em> is checked, image links will omit the image suffix. E.g. a link to the image page for <code>myalbum/myphoto.jpg</code> will appear as <code>myalbum/myphoto%s</code>'), RW_SUFFIX);
										echo '<p class="notebox">';
										echo gettext('<strong>Note:</strong> This option requires <em>mod rewrite</em> to be set and the image prefixes must be unique within an album!');
										echo '</p>';
										?>
									</p>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Language"); ?></td>
						<td class="option_value">
							<div id="languagelist">
								<ul class="languagelist">
									<?php
									$unsupported = getSerializedArray(getOption('locale_unsupported'));
									$disallow = getSerializedArray(getOption('locale_disallowed'));
									$locales = i18n::generateLanguageList('all');
									$locales[gettext("HTTP_Accept_Language")] = '';
									ksort($locales, SORT_LOCALE_STRING);
									$c = 0;
									foreach ($locales as $language => $dirname) {
										$languageAlt = $language;
										$languageP = '';
										if (!empty($dirname)) {
											$flag = getLanguageFlag($dirname);
											$source = i18n::languageFolder($dirname) . $dirname . '/LC_MESSAGES/core.po';
											if (file_exists($source)) {
												$po = file_get_contents($source);
												preg_match_all('~^#,\sfuzzy\s+~ims', $po, $fuzzy);
												if (count($fuzzy[0])) {
													preg_match_all('~^#:.*?msgid~ims', $po, $msgid);
													$needswork = round(count($fuzzy[0]) / count($msgid[0]) * 100);
													$languageP .= ' <span style="font-size:xx-small;color: red;">[' . $needswork . '%]</span>';
												}
											}
										} else {
											$flag = WEBPATH . '/' . CORE_FOLDER . '/locale/auto.png';
										}
										if (isset($unsupported[$dirname])) {
											$c_attrs = $r_attrs = ' disabled="disabled"';
										} else {
											if (isset($disallow[$dirname])) {
												$c_attrs = '';
												$r_attrs = ' disabled="disabled"';
											} else {
												$c_attrs = ' checked="checked"';
												$r_attrs = '';
											}
										}

										if ($dirname == SITE_LOCALE) {
											$r_attrs = ' checked="checked"';
											$c_attrs = ' checked="checked" disabled="disabled"';
											?>
											<input type="hidden" name="language_allow[_<?php echo $dirname; ?>]" value="1" />
											<script type="text/javascript">
												window.addEventListener('load', function () {
													$('ul.languagelist').scrollTo('li:eq(<?php echo ($c - 2); ?>)');
												}, false);
											</script>
											<?php
										}
										$c++;
										?>
										<li>
											<label class="displayinline">
												<input type="radio" name="locale" id="r_<?php echo $dirname; ?>" value="<?php echo $dirname; ?>"
															 onclick="radio_click('<?php echo $dirname; ?>');" <?php echo $r_attrs; ?>/>
											</label>
											<label class="flags">
												<span class="displayinline">
													<input id="language_allow_<?php echo $dirname; ?>" name="language_allow[_<?php echo $dirname; ?>]" type="checkbox"
																 value="<?php echo $dirname; ?>"<?php echo $c_attrs; ?>
																 onclick="enable_click('<?php echo $dirname; ?>');" />
													<img src="<?php echo $flag; ?>" alt="<?php echo $languageAlt; ?>" width="24" height="16" />
													<?php echo $language; ?>
												</span>
												<?php echo $languageP; ?>
											</label>
										</li>
										<?php
									}
									?>
								</ul>
								<?php echo '<span class="floatright" style="font-size:xx-small;">' . gettext('Percent mechanically translated in red.'); ?></span
							</div>
							<br class="clearall" />
							<label class="checkboxlabel">
								<input type="checkbox" name="multi_lingual" value="1"	<?php checked('1', getOption('multi_lingual')); ?> />
								<?php echo gettext('multi-lingual'); ?>
							</label>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<p>
										<?php
										echo gettext("You can disable languages by unchecking their checkboxes. Only checked languages will be available to the installation.") . ' ';
										echo gettext("Select the preferred language to display text in. (Set to <em>HTTP_Accept_Language</em> to use the language preference specified by the viewer’s browser.)") . ' ';
										echo gettext('More languages can be found in the netPhotoGraphics <a href="https://github.com/netPhotoGraphics/language-files" />language-files</a> gitHub repository.');
										?>
									</p>
									<p>
										<?php echo gettext("Set <em>Multi-lingual</em> to enable multiple language input for options that provide theme text."); ?>
									</p>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Date format"); ?></td>
						<td class="option_value">
							<?php
							$formats = array(
									'02/25/08 15:30' => 'm/d/y H:i',
									'02/25/08' => 'm/d/y',
									'02/25/2008 15:30' => 'm/d/Y H:i',
									'02/25/2008' => 'm/d/Y',
									'02-25-08 15:30' => 'm-d-y H:i',
									'02-25-08' => 'm-d-y',
									'02-25-2008 15:30' => 'm-d-Y H:i',
									'02-25-2008' => 'm-d-Y',
									'Feb 25, 2008 15:30' => 'M d, Y H:i',
									'Feb 25, 2008' => 'M d, Y',
									'February 25, 2008 15:30' => 'F d, Y H:i',
									'February 25, 2008' => 'F d, Y',
									'25/02/08 15:30' => 'd/m/y H:i',
									'25/02/08' => 'd/m/y',
									'25/02/2008 15:30' => 'd/m/Y H:i',
									'25/02/2008' => 'd/m/Y',
									'25-02-08 15:30' => 'd-m-y H:i',
									'25-02-08' => 'd-m-y',
									'25-02-2008 15:30' => 'd-m-Y H:i',
									'25-02-2008' => 'd-m-Y',
									'25-Feb-08 15:30' => 'd-M-y H:i',
									'25-Feb-08' => 'd-M-y',
									'25-Feb-2008 15:30' => 'd-M-Y H:i',
									'25-Feb-2008' => 'd-M-Y',
									'25 Feb 2008 15:30' => 'd F Y H:i',
									'25 Feb 2008' => 'd F Y',
									'25 February 2008 15:30' => 'd F Y H:i',
									'25 February 2008' => 'd F Y',
									'25.02.08 15:30' => 'd.m.y H:i',
									'25.02.08' => 'd.m.y',
									'25.02.2008 15:30' => 'd.m.Y H:i',
									'25.02.2008' => 'd.m.Y',
									'25. Feb 2008 15:30' => 'd. F Y H:i',
									'25. Feb 2008' => 'd. F Y',
									'25. Feb. 08 15:30' => 'd. M y H:i',
									'25. Feb. 08' => 'd. M y',
									'25. February 2008 15:30' => 'd. F Y H:i',
									'25. February 2008' => 'd. F Y',
									'08/02/25 15:30' => 'y/m/d H:i',
									'08/02/25' => 'y/m/d',
									'2008/02/25 15:30' => 'Y/m/d H:i',
									'2008/02/25' => 'Y/m/d',
									'08-02-25 15:30' => 'y-m-d H:i',
									'08-02-25' => 'y-m-d',
									'2008-02-25 15:30' => 'Y-m-d H:i',
									'2008-02-25' => 'Y-m-d',
									'08.02.25 15:30' => 'y.m.d H:i',
									'08.02.25' => 'y.m.d',
									'2008.02.25 15:30' => 'Y.m.d H:i',
									'2008.02.25' => 'Y.m.d',
									'2008. February 25. 15:30' => 'Y. F d. H:i',
									'2008. February 25.' => 'Y. F d.'
							);

							$t = mktime(date('h'), date('i'), date('s'), 2, 5, date('y'));
							$formatlist = [];
							foreach ($formats as $disp => $fmt) {
								$formatlist[formattedDate($fmt, $t)] = $fmt;
							}
							$formatlist[gettext('Preferred date representation')] = '%x';
							if (in_array(DATE_FORMAT, $formatlist)) {
								$dsp = 'none';
								$formatlist[gettext('Custom')] = 'custom';
								$cv = DATE_FORMAT;
							} else {
								$dsp = 'block';
								$formatlist[sprintf(gettext('custom: %1$s'), formattedDate(DATE_FORMAT, time()))] = 'custom';
								$cv = 'custom';
							}
							?>
							<select id="date_format_list" name="date_format_list" onchange="showfield(this, 'customTextBox')">
								<?php
								generateListFromArray(array($cv), $formatlist, NULL, true);
								?>
							</select>
							<div id="customTextBox" class="customText" style="display:<?php echo $dsp; ?>">
								<br />
								<input type="text" size="<?php echo TEXT_INPUT_SIZE; ?>" name="date_format" value="<?php echo html_encode(DATE_FORMAT); ?>" />
							</div>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext('Format for dates. Select from the list or set to <code>custom</code> and provide a <a href="https://www.php.net/manual/en/datetime.format.php"><span class="nowrap"><code>date()</code></span></a> format string in the text box.'); ?>
								</div>
							</span>
						</td>
					</tr>

					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Filesystem Charset"); ?></td>
						<td class="option_value">
							<select id="filesystem_charset" name="filesystem_charset">
								<?php
								foreach ($_UTF8->charsets as $key => $char) {
									if ($key == FILESYSTEM_CHARSET) {
										$selected = ' selected="selected"';
									} else {
										$selected = '';
									}
									?>
									<option value="<?php echo $key; ?>"<?php echo $selected; ?>><?php echo $char; ?></option>
									<?php
								}
								?>
							</select>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext('The character encoding to use for the filesystem.'); ?>
								</div>
							</span>
						</td>
					</tr>

					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Allowed tags"); ?></td>
						<td class="option_value">
							<?php
							$tags = getOption('allowed_tags');
							if (empty($tags)) {
								$tags = getOption('allowed_tags_default');
							}
							?>
							<textarea name="allowed_tags" id="allowed_tags" class="fullwidth" rows="4" cols="35"><?php echo $tags; ?></textarea>
							<p>
								<?php npgButton('button', CLOCKWISE_OPEN_CIRCLE_ARROW_GREEN . ' ' . gettext('Revert to default'), array('buttonClick' => "resetallowedtags()")); ?>
							</p>
						</td>
						<td class="option_desc">
							<script type="text/javascript">
								// <!-- <![CDATA[
								function resetallowedtags() {
									$('#allowed_tags').val(<?php
							$t = getOption('allowed_tags_default');
							$tags = explode("\n", $t);
							$c = 0;
							foreach ($tags as $t) {
								$t = trim($t);
								if (!empty($t)) {
									if ($c > 0) {
										echo '+';
										echo "\n";
										?>
				<?php
			}
			$c++;
			echo "'" . $t . '\'+"\n"';
		}
	}
	?>);
								}
								// ]]> -->
							</script>
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<p><?php echo gettext("Tags and attributes allowed in comments, descriptions, and other fields."); ?></p>
									<p><?php echo gettext("Follow the form <em>tag</em> =&gt; (<em>attribute</em> =&gt; (<em>attribute</em> =&gt; (), <em>attribute</em> =&gt; ()...)))"); ?></p>
									<?php if (EDITOR_SANITIZE_LEVEL == 4) { ?>
										<p class="notebox"><?php echo gettext('<strong>Note:</strong> visual editing is enabled so the editor overrides these settings on tags where it is active.'); ?></p>
									<?php } ?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Usage policy"); ?></td>
						<td class="option_value">
							<label>
								<input type="checkbox" name="GDPR_acknowledge" value="1" <?php checked(1, getOption('GDPR_acknowledge')); ?> onclick="$('#GDR_Details').toggle();<?php if (!extensionEnabled('GDPR_required')) echo '$(\'#GDPR_clear\').toggle();'; ?>" />
								<?php echo gettext('require acknowledgement'); ?>
							</label>
							<p id="GDPR_clear" <?php if (!(getOption('GDPR_acknowledge') || extensionEnabled('GDPR_required'))) echo ' style="display:none"'; ?>>
								<label>
									<input type="checkbox" name="GDPR_re-acknowledge" value="1" />
									<?php echo gettext('clear remembered acknowledgements'); ?>
								</label>
							</p>
							<div id="GDR_Details" <?php if (!GetOption('GDPR_acknowledge')) echo ' style="display:none"'; ?>>

								<?php echo gettext('policy URL'); ?>
								<input type="text" class="fullwidth" name="GDPR_URL" value="<?php echo getOption('GDPR_URL'); ?>" />

								<?php
								echo gettext('notice text') . ' ';
								print_language_string_list(get_language_string(getOption('GDPR_text')), 'GDPR_text', false, null, '', '100%');
								?>
							</div>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext('Require policy notice acknowledgement according to the <a href="https://en.wikipedia.org/wiki/General_Data_Protection_Regulation">General Data Protection Regulation</a>. The policy notice URL must point to your site usage policy.<br /><br />
Standard forms which collect user data will have a policy acknowledgement checkbox which must be checked before the “submit” button is present.<br /><br />The acknowledgement will be remembered. They persist for site users until you clear the remembered acknowledgements. For anonymous visitors it persists for the cookie expiration interval (or browser session if <em>gallery sessions</em> is enabled.)'); ?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name">
							<?php echo gettext("Cookies"); ?>
						</td>
						<td class="option_value">
							<?php
							if (!GALLERY_SESSION) {
								echo gettext('path');
								?>
								<input type="text" class="fullwidth" id="cookie_path" name="cookie_path"  value="<?php echo getOption('cookie_path'); ?>" />
								<p>
									<?php
									echo gettext('duration');
									?>
									<input type="text" name="cookie_persistence" value="<?php echo COOKIE_PERSISTENCE; ?>" />
								</p>
								<?php
							}
							?>
							<p>
								<label>
									<input type="checkbox" name="album_session" id="album_session" value="1" <?php checked('1', GALLERY_SESSION); ?> />
									<?php echo gettext("enable gallery sessions"); ?>
								</label>
							</p>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php
									if (!GALLERY_SESSION) {
										?>
										<p><?php printf(gettext('The <em>path</em> to use when storing cookies. (Leave empty to default to <em>%s</em>)'), WEBPATH); ?></p>
										<p><?php echo gettext("Set to the time in seconds that cookies should be kept by browsers."); ?></p>
										<?php
									}
									?>
									<p><?php echo gettext('If the gallery sessions option is selected <a href="http://www.w3schools.com/php/php_sessions.asp">PHP sessions</a> will be used instead of cookies to make visitor settings persistent.'); ?></p>
									<p class="notebox"><?php echo gettext('<strong>NOTE</strong>: Sessions will normally close when the browser closes causing all password and other data to be discarded. They may close more frequently depending on the runtime configuration. Longer <em>lifetime</em> of sessions is generally more conducive to a pleasant user experience. Cookies are the preferred storage option since their duration is determined by the <em>Cookie duration</em> option. ') ?>
									</p>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name">
							<?php echo gettext("Name"); ?>
							<p><?php echo gettext("Email"); ?></p>
						</td>
						<td class="option_value">
							<input type="text" class="fullwidth" name="site_email_name" value="<?php echo get_language_string(getOption('site_email_name')) ?>" />
							<input type="text" class="fullwidth" id="site_email" name="site_email"  value="<?php echo getOption('site_email'); ?>" />
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext("This email name and address will be used as the <em>From</em> address for all mails sent by the gallery."); ?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name">
							<?php echo gettext('Registration'); ?>
						</td>
						<td class="option_value">
							<?php
							$mailinglist = $_authority->getAdminEmail(ADMIN_RIGHTS);
							?>
							<label><input type="checkbox" id="site_email" name="register_user_notify"  value="1" <?php checked('1', getOption('register_user_notify') && $mailinglist); ?> <?php if (!$mailinglist) echo ' disabled="disabled"'; ?> /> <?php echo gettext('notify'); ?></label>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php
									echo gettext('If checked, an e-mail will be sent to the gallery admin when a new user has registered on the site.');
									if (count($mailinglist) == 0) { //	no one to send the notice to!
										echo ' ' . gettext('Of course there must be some Administrator with an e-mail address for this option to make sense!');
									}
									?>
								</div>
							</span>
					</tr>
					<tr class="optionSet">
						<td class="option_name">
							<?php echo gettext('Online threshold'); ?>
						</td>
						<td class="option_value">
							<label>
								<input type="number" min="0" step='5' id="user_persistance" name="online_persistance"  value="<?php echo getOption('online_persistance') ?>" />
							</label>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php
									printf(gettext('Users will be considered to be "on-line" if their last page request was no more than %1$s minutes ago.'), getOption('online_persistance'));
									?>
								</div>
							</span>
					</tr>
					<tr class="optionSet">
						<td class="option_name">
							<?php echo gettext('Dirty form check'); ?>
						</td>
						<td class="option_value">
							<label>
								<input type="radio" name="dirtyform_enable" value="0"<?php checked('0', getOption('dirtyform_enable')); ?> />
								<?php echo gettext("ignore"); ?>
							</label>
							<label>
								<input type="radio" name="dirtyform_enable" value="1"<?php checked('1', getOption('dirtyform_enable')); ?> />
								<?php echo gettext("exclude tinyMCE"); ?>
							</label>
							<label>
								<input type="radio" name="dirtyform_enable" value="2"<?php checked('2', getOption('dirtyform_enable')); ?> />
								<?php echo gettext("detect all changes"); ?>
							</label>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext("Enable checking for form changes before leaving pages."); ?>
								</div>
							</span>
						</td>
					</tr>
					<?php
					$subtabs = array('security' => gettext('security'), 'debug' => gettext('debug'));
					?>
					<tr class="optionSet">
						<td class="option_name">
							<?php
							foreach ($subtabs as $subtab => $log) {
								if (!is_null(getOption($subtab . '_log_size'))) {
									printf(gettext('<p>%s log limit</p>'), ucfirst($log));
								}
							}
							?>
						</td>
						<td class="option_value">
							<?php
							foreach ($subtabs as $subtab => $log) {
								if (!is_null($size = getOption($subtab . '_log_size'))) {
									?>
									<p>
										<label>
											<input type="text" size="4" id="<?php echo $log ?>_log" name="log_size_<?php echo $subtab; ?>" value="<?php echo $size; ?>" />
										</label>
										<label>
											<input type="checkbox" id="<?php echo $log ?>_log" name="log_mail_<?php echo $subtab; ?>" value="1" <?php checked('1', getOption($subtab . '_log_mail')); ?> /> <?php echo gettext('e-mail when exceeded'); ?>
										</label>
									</p>
									<?php
								}
							}
							?>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext('Logs will be "rolled" over when they exceed the specified size. If checked, the administrator will be e-mailed when this occurs.') ?>
								</div>
							</span>
						</td>
					</tr>
					<?php npgFilters::apply('admin_general_data'); ?>
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
	<!-- end of tab-general div -->
	<?php
}
