<?php
/*
 * Guts of the theme options tab
 */
$optionRights = THEMES_RIGHTS;

$themelist = array();
if (npg_loggedin(ADMIN_RIGHTS)) {
	$gallery_title = $_gallery->getTitle();
	if ($gallery_title != gettext("Gallery")) {
		$gallery_title .= ' (' . gettext("Gallery") . ')';
	}
	$themelist[$gallery_title] = '';
}
$albums = $_gallery->getAlbums(0);
foreach ($albums as $alb) {
	$_set_theme_album = newAlbum($alb);
	if ($_set_theme_album->isMyItem(THEMES_RIGHTS)) {
		$theme = $_set_theme_album->getAlbumTheme();
		if (!empty($theme)) {
			$key = $_set_theme_album->getTitle();
			if ($key != $alb) {
				$key .= " ($alb)";
			}
			$themelist[$key] = pathurlencode($alb);
		}
	}
}
$alb = $_set_theme_album = NULL;
$themename = $_gallery->getCurrentTheme();
if (!empty($_REQUEST['themealbum'])) {
	$alb = urldecode(sanitize_path($_REQUEST['themealbum']));
	$_set_theme_album = newAlbum($alb);
	$themename = $_set_theme_album->getAlbumTheme();
}
if (!empty($_REQUEST['optiontheme'])) {
	$themename = sanitize($_REQUEST['optiontheme']);
}
if (empty($alb)) {
	$alb = reset($themelist);
	$albumtitle = key($themelist);
	if (empty($alb)) {
		$_set_theme_album = NULL;
	} else {
		$alb = sanitize_path($alb);
		$_set_theme_album = newAlbum($alb);
		$themename = $_set_theme_album->getAlbumTheme();
	}
}
if (!(false === ($requirePath = getPlugin('themeoptions.php', $themename)))) {
	require_once($requirePath);
	$_gallery->setCurrentTheme($themename);
	$optionHandler = new ThemeOptions(!getThemeOption('constructed', $_set_theme_album, $themename));
} else {
	$optionHandler = NULL;
}

function saveOptions() {
	global $_gallery;
	$themeswitch = $_set_theme_album = $notify = $table = NULL;
	$themename = urldecode(sanitize($_POST['optiontheme'], 3));
	$returntab = "&tab=theme";
	if ($themename)
		$returntab .= '&optiontheme=' . urlencode($themename);
	// all theme specific options are custom options, handled below
	if (!isset($_POST['themealbum']) || empty($_POST['themealbum'])) {
		$themeswitch = urldecode(sanitize_path($_POST['old_themealbum'])) != '';
	} else {
		$alb = urldecode(sanitize_path($_POST['themealbum']));
		$_set_theme_album = newAlbum($alb);
		if ($_set_theme_album->exists) {
			$returntab .= '&themealbum=' . pathurlencode($alb) . '&tab=theme';
			$themeswitch = $alb != urldecode(sanitize_path($_POST['old_themealbum']));
		} else {
			$_set_theme_album = NULL;
		}
	}

	if ($themeswitch) {
		$notify = '?switched';
	} else {
		if (isset($_POST['savethemeoptions']) && $_POST['savethemeoptions'] == 'reset') {
			$sql = 'DELETE FROM ' . prefix('options') . ' WHERE `theme`=' . db_quote($themename);
			if ($_set_theme_album) {
				$sql .= ' AND `ownerid`=' . $_set_theme_album->getID();
			} else {
				$sql .= ' AND `ownerid`=0';
			}
			query($sql); //	delete the theme options
			$sql = 'DELETE FROM ' . prefix('menu') . ' WHERE `menuset`=' . db_quote($themename);
			query($sql); //	delete the theme menu

			$themeswitch = true;
		} else {
			setThemeOption('constructed', 1, $_set_theme_album, $themename);
			$ncw = $cw = getThemeOption('thumb_crop_width', $_set_theme_album, $themename);
			$nch = $ch = getThemeOption('thumb_crop_height', $_set_theme_album, $themename);
			if (isset($_POST['image_size']))
				setThemeOption('image_size', sanitize_numeric($_POST['image_size']), $_set_theme_album, $themename);
			if (isset($_POST['image_use_side']))
				setThemeOption('image_use_side', sanitize($_POST['image_use_side']), $_set_theme_album, $themename);
			setThemeOption('thumb_crop', (int) isset($_POST['thumb_crop']), $_set_theme_album, $themename);
			if (isset($_POST['thumb_size'])) {
				$ts = sanitize_numeric($_POST['thumb_size']);
				setThemeOption('thumb_size', $ts, $_set_theme_album, $themename);
			} else {
				$ts = getThemeOption('thumb_size', $_set_theme_album, $themename);
			}
			if (isset($_POST['thumb_crop_width'])) {
				if (is_numeric($_POST['thumb_crop_width'])) {
					$ncw = round($ts - $ts * 2 * sanitize_numeric($_POST['thumb_crop_width']) / 100);
				}
				setThemeOption('thumb_crop_width', $ncw, $_set_theme_album, $themename);
			}
			if (isset($_POST['thumb_crop_height'])) {
				if (is_numeric($_POST['thumb_crop_height'])) {
					$nch = round($ts - $ts * 2 * sanitize_numeric($_POST['thumb_crop_height']) / 100);
				}
				setThemeOption('thumb_crop_height', $nch, $_set_theme_album, $themename);
			}

			if (isset($_POST['albums_per_page'])) {
				setThemeOption('albums_per_page', $_POST['albums_per_page'], $_set_theme_album, $themename);
			}
			if (isset($_POST['images_per_page'])) {
				setThemeOption('images_per_page', $_POST['images_per_page'], $_set_theme_album, $themename);
			}
			if (isset($_POST['images_per_row'])) {
				setThemeOption('images_per_row', $_POST['images_per_row'], $_set_theme_album, $themename);
			}

			if (isset($_POST['theme_head_separator'])) {
				setThemeOption('theme_head_separator', sanitize($_POST['theme_head_separator']), $_set_theme_album, $themename);
			}
			setThemeOption('theme_head_listparents', (int) isset($_POST['theme_head_listparents']), $_set_theme_album, $themename);

			if (isset($_POST['thumb_transition']))
				setThemeOption('thumb_transition', (int) ((sanitize_numeric($_POST['thumb_transition']) - 1) && true), $_set_theme_album, $themename);
			$otg = getThemeOption('thumb_gray', $_set_theme_album, $themename);
			setThemeOption('thumb_gray', (int) isset($_POST['thumb_gray']), $_set_theme_album, $themename);
			if ($otg = getThemeOption('thumb_gray', $_set_theme_album, $themename))
				$wmo = 99; // force cache clear
			$oig = getThemeOption('image_gray', $_set_theme_album, $themename);
			setThemeOption('image_gray', (int) isset($_POST['image_gray']), $_set_theme_album, $themename);
			if ($oig = getThemeOption('image_gray', $_set_theme_album, $themename))
				$wmo = 99; // force cache clear
			if ($nch != $ch || $ncw != $cw) { // the crop height/width has been changed
				$sql = 'UPDATE ' . prefix('images') . ' SET `thumbX`=NULL,`thumbY`=NULL,`thumbW`=NULL,`thumbH`=NULL WHERE `thumbY` IS NOT NULL';
				query($sql);
				$wmo = 99; // force cache clear as well.
			}
			if (isset($wmo)) {
				Gallery::clearCache();
			}
		}
	}

	return array($returntab, $notify, $_set_theme_album, $themename, $themeswitch);
}

function getOptionContent() {
	global $_gallery, $_set_theme_album, $optionHandler, $themelist, $themename, $_set_theme_album, $alb, $album, $albumtitle;
	?>
	<script>
		window.addEventListener('load', function () {
			customTable = Math.round($('#customOptions').width());
			if (customTable > table) {
				table = customTable;
				$('.colwidth').width(table);
				setColumns();
			}
		}, false);
	</script>
	<div id="tab_theme" class="tabbox">
		<?php
		if ($optionHandler) {
			$supportedOptions = $optionHandler->getOptionsSupported();
			if (method_exists($optionHandler, 'getOptionsDisabled')) {
				$unsupportedOptions = $optionHandler->getOptionsDisabled();
			} else {
				$unsupportedOptions = array();
			}
		} else {
			$unsupportedOptions = array();
			$supportedOptions = array();
		}
		standardThemeOptions($themename, $album);
		?>
		<?php
		if (count($themelist) == 0) {
			?>
			<div class="errorbox" id="no_themes">
				<h2><?php echo gettext("There are no themes for which you have rights to administer."); ?></h2>
			</div>

			<?php
		} else {
			/* handle theme options */
			$themes = $_gallery->getThemes();
			if (array_key_exists($themename, $themes)) {
				$theme = $themes[$themename];

				$prev = $next = '&nbsp;';
				$found = NULL;
				foreach ($themes as $atheme => $data) {
					unset($themes[$atheme]);
					if ($atheme == $themename) {
						$found = true;
					} else {
						if ($found) {
							$next = $atheme;
							break;
						}
						$prev = $atheme;
					}
				}
				?>
				<p class="padded">
					<a href="?page=options&tab=theme&optiontheme=<?php echo urlencode($prev); ?>"><?php echo $prev; ?></a>
					<span class="floatright" >
						<a href="?page=options&tab=theme&optiontheme=<?php echo urlencode($next); ?>"><?php echo $next; ?></a>
					</span>
				</p>
				<?php
			}
			?>

			<form class="dirtylistening" onReset="setClean('themeoptionsform');" action="?action=saveoptions" method="post" id="themeoptionsform" autocomplete="off" >
				<?php XSRFToken('saveoptions'); ?>
				<input type="hidden" id="saveoptions" name="saveoptions" value="theme" />
				<input type="hidden" id="savethemeoptions" name="savethemeoptions" value="" />
				<input type="hidden" name="optiontheme" value="<?php echo urlencode($themename); ?>" />
				<input type="hidden" name="old_themealbum" value="<?php echo pathurlencode($alb); ?>" />
				<p>
					<?php
					applyButton();
					npgButton('button', CLOCKWISE_OPEN_CIRCLE_ARROW_GREEN . ' ' . gettext("Revert to default"), array('buttonClick' => "$('#savethemeoptions').val('reset');$('#themeoptionsform').submit();"));
					resetButton();
					?>
				</p>
				<br clear="all">
				<br />
				<div id="columns">
					<div class="colwidth">
						<p>
							<?php echo gettext('<em>These image and album presentation options provided by the Core for all themes.</em>'); ?>
						</p>
						<p class="notebox">
							<?php echo gettext('<strong>Note:</strong> These are <em>recommendations</em> as themes may choose to override them for design reasons.'); ?>
						</p>
					</div>
					<table id="npgOptions" class="colwidth">
						<tr>
							<td class="option_name"><?php echo gettext("Albums"); ?></td>
							<td class="option_value">
								<?php
								if (in_array('albums_per_page', $unsupportedOptions)) {
									$disable = ' disabled="disabled"';
								} else {
									$disable = '';
								}
								?>
								<input type="text" size="3" name="albums_per_page" value="<?php echo getThemeOption('albums_per_page', $album, $themename); ?>"<?php echo $disable; ?> /> <?php echo gettext('thumbnails per page'); ?>
							</td>
							<td class="option_desc">
								<span class="option_info">
									<?php echo INFORMATION_BLUE; ?>
									<div class="option_desc_hidden">
										<?php
										echo gettext('This is the number of albums thumbnails you wish per page.');
										?>
									</div>
								</span>
							</td>
						</tr>
						<tr>
							<td class="option_name"><?php echo gettext("Images"); ?></td>
							<td class="option_value">
								<?php
								if (in_array('images_per_page', $unsupportedOptions)) {
									$disable = ' disabled="disabled"';
								} else {
									$disable = '';
								}
								?>
								<input type="text" size="3" name="images_per_page" value="<?php echo getThemeOption('images_per_page', $album, $themename); ?>"<?php echo $disable; ?> /> <?php echo gettext('thumbnails per page'); ?>
							</td>
							<td class="option_desc">
								<span class="option_info">
									<?php echo INFORMATION_BLUE; ?>
									<div class="option_desc_hidden">
										<?php
										echo gettext('This is the number of image thumbnails that you wish per page. Leave empty if you wish all the images to be on one page–­e.g. if the images are shown as a slide show.');
										?>
									</div>
								</span>
							</td>
						</tr>
						<?php
						if (in_array('thumb_transition', $unsupportedOptions)) {
							$disable = ' disabled="disabled"';
						} else {
							$disable = '';
						}
						?>
						<tr>
							<td class="option_name"><?php echo gettext('Transition'); ?></td>
							<td class="option_value">
								<span class="nowrap">
									<?php
									if (getThemeOption('thumb_transition', $album, $themename)) {
										$separate = '';
										$combined = ' checked="checked"';
									} else {
										$separate = ' checked="checked"';
										$combined = '';
									}
									if ($disable) {
										$combined = $separate = ' disabled="disabled"';
									}
									?>
									<label>
										<input type="radio" name="thumb_transition" value="1"<?php echo $separate; ?> />
										<?php echo gettext('separate'); ?>
									</label>
									<label>
										<input type="radio" name="thumb_transition" value="2"<?php echo $combined; ?> />
										<?php echo gettext('combined'); ?>
									</label>
								</span>
							</td>
							<td class="option_desc">
								<span class="option_info">
									<?php echo INFORMATION_BLUE; ?>
									<div class="option_desc_hidden">
										<?php echo gettext('Choose if album thumbnails and image thumbnails are placed together on the page that transitions from only album thumbnails to only image thumbnails.'); ?>
									</div>
								</span>
							</td>
						</tr>

						<tr>
							<td class="option_name"></td>
							<td class="option_value">
								<span class="nowrap">
									<label>
										<?php echo gettext('image thumbnail multiple'); ?>
										<input type="text" size="3" name="images_per_row" value="<?php echo getThemeOption('images_per_row', $album, $themename); ?>" <?php echo $combined; ?> />
									</label>
								</span>
							</td>
							<td class="option_desc">
								<span class="option_info">
									<?php echo INFORMATION_BLUE; ?>
									<div class="option_desc_hidden">
										<?php echo gettext('If this option is set, the image thumbnails on the transition page will be an even multiple of the value.'); ?>
									</div>
								</span>
							</td>
						</tr>

						<?php
						if (in_array('thumb_size', $unsupportedOptions)) {
							$disable = ' disabled="disabled"';
						} else {
							$disable = '';
						}
						$ts = max(1, getThemeOption('thumb_size', $album, $themename));
						$iw = getThemeOption('thumb_crop_width', $album, $themename);
						$ih = getThemeOption('thumb_crop_height', $album, $themename);
						$cl = round(($ts - $iw) / $ts * 50, 1);
						$ct = round(($ts - $ih) / $ts * 50, 1);
						?>
						<tr>
							<td class="option_name"><?php echo gettext("Thumbnail size"); ?></td>
							<td class="option_value">
								<input type="text" size="3" name="thumb_size" value="<?php echo $ts; ?>"<?php echo $disable; ?> />
							</td>
							<td class="option_desc">
								<span class="option_info">
									<?php echo INFORMATION_BLUE; ?>
									<div class="option_desc_hidden">
										<?php printf(gettext("Standard thumbnails will be scaled to %u pixels."), $ts); ?>
									</div>
								</span>
							</td>
						</tr>

						<?php
						if (in_array('thumb_crop', $unsupportedOptions)) {
							$disable = ' disabled="disabled"';
						} else {
							$disable = '';
						}
						?>
						<tr>
							<td class="option_name"><?php echo gettext("Crop thumbnails"); ?></td>
							<td class="option_value">
								<input type="checkbox" name="thumb_crop" value="1" <?php checked('1', $tc = getThemeOption('thumb_crop', $album, $themename)); ?><?php echo $disable; ?> />
								&nbsp;&nbsp;
								<span class="nowrap">
									<?php printf(gettext('%s%% left &amp; right'), '<input type="text" size="3" name="thumb_crop_width" id="thumb_crop_width" value="' . $cl . '"' . $disable . ' />')
									?>
								</span>&nbsp;
								<span class="nowrap">
									<?php printf(gettext('%s%% top &amp; bottom'), '<input type="text" size="3" name="thumb_crop_height" id="thumb_crop_height"	value="' . $ct . '"' . $disable . ' />');
									?>
								</span>
							</td>
							<td class="option_desc">
								<span class="option_info">
									<?php echo INFORMATION_BLUE; ?>
									<div class="option_desc_hidden">
										<?php printf(gettext('If checked the thumbnail will be cropped %1$.1f%% in from the top and the bottom margins and %2$.1f%% in from the left and the right margins.'), $ct, $cl); ?>
										<br />
										<p class='notebox'><?php echo gettext('<strong>Note:</strong> changing crop will invalidate existing custom crops.'); ?></p>
									</div>
								</span>
							</td>
						</tr>
						<tr>
							<td class="option_name"><?php echo gettext("Gray scale conversion"); ?></td>
							<td class="option_value">
								<label class="checkboxlabel">
									<?php echo gettext('image') ?>
									<input type="checkbox" name="image_gray" id="image_gray" value="1" <?php checked('1', getThemeOption('image_gray', $album, $themename)); ?> />
								</label>
								<label class="checkboxlabel">
									<?php echo gettext('thumbnail') ?>
									<input type="checkbox" name="thumb_gray" id="thumb_gray" value="1" <?php checked('1', getThemeOption('thumb_gray', $album, $themename)); ?> />
								</label>
							</td>
							<td class="option_desc">
								<span class="option_info">
									<?php echo INFORMATION_BLUE; ?>
									<div class="option_desc_hidden">
										<?php echo gettext("If checked, images/thumbnails will be created in gray scale."); ?>
									</div>
								</span>
							</td>
						</tr>
						<?php
						if (in_array('image_size', $unsupportedOptions)) {
							$disable = ' disabled="disabled"';
						} else {
							$disable = '';
						}
						if (in_array('image_size', $unsupportedOptions)) {
							$disable = ' disabled="disabled"';
						} else {
							$disable = '';
						}
						if (in_array('image_use_side', $unsupportedOptions)) {
							$disableside = ' disabled="disabled"';
						} else {
							$disableside = '';
						}
						?>
						<tr>
							<td class="option_name"><?php echo gettext("Image size"); ?></td>
							<td class="option_value"><?php $side = getThemeOption('image_use_side', $album, $themename); ?>
								<table>
									<tr>
										<td rowspan="2" style="margin: 0; padding: 0"><input type="text"
																																				 size="3" name="image_size"
																																				 value="<?php echo getThemeOption('image_size', $album, $themename); ?>"
																																				 <?php echo $disable; ?> /></td>
										<td style="margin: 0; padding: 0"><label> <input type="radio"
																																		 id="image_use_side1" name="image_use_side" value="height"
																																		 <?php if ($side == 'height') echo ' checked="checked"'; ?>
																																		 <?php echo $disableside; ?> /> <?php echo gettext('height') ?> </label>
											<label> <input type="radio" id="image_use_side2"
																		 name="image_use_side" value="width"
																		 <?php if ($side == 'width') echo ' checked="checked"'; ?>
																		 <?php echo $disableside; ?> /> <?php echo gettext('width') ?> </label>
										</td>
									</tr>
									<tr>
										<td style="margin: 0; padding: 0"><label> <input type="radio"
																																		 id="image_use_side3" name="image_use_side" value="shortest"
																																		 <?php if ($side == 'shortest') echo ' checked="checked"'; ?>
																																		 <?php echo $disableside; ?> /> <?php echo gettext('shortest side') ?>
											</label> <label> <input type="radio" id="image_use_side4"
																							name="image_use_side" value="longest"
																							<?php if ($side == 'longest') echo ' checked="checked"'; ?>
																							<?php echo $disableside; ?> /> <?php echo gettext('longest side') ?> </label>
										</td>
									</tr>
								</table>
							</td>
							<td class="option_desc">
								<span class="option_info">
									<?php echo INFORMATION_BLUE; ?>
									<div class="option_desc_hidden">
										<?php echo gettext("Default image display size."); ?> <br />
										<?php echo gettext("The image will be sized so that the <em>height</em>, <em>width</em>, <em>shortest side</em>, or the <em>longest side</em> will be equal to <em>image size</em>."); ?>
									</div>
								</span>
							</td>
						</tr>
						<?php
						if (is_null($album)) {
							?>
							<tr>
								<td class="option_name"><?php echo gettext("Theme head &lt;title&gt; tag"); ?></td>
								<td class="option_value">
									<label>
										<input type="checkbox" name="theme_head_listparents" value="1"<?php if (getThemeOption('theme_head_listparents', $album, $themename)) echo ' checked="checked"'; ?> />
										<?php echo gettext('List parents'); ?>
									</label>
									<br />
									<input type="text" name="theme_head_separator" size="2em" value="<?php echo getThemeOption('theme_head_separator', $album, $themename); ?>" />
									<?php echo gettext("separator"); ?>
								</td>

								<td class="option_desc">
									<span class="option_info">
										<?php echo INFORMATION_BLUE; ?>
										<div class="option_desc_hidden">
											<?php echo gettext('Select if you want parent breadcrumbs and if so the separator for them.'); ?>
										</div>
									</span></td>
							</tr>
						</table>
						<?php
					}
					if (count($supportedOptions) > 0) {
						?>
						<div class="colwidth breakpoint">
							<p>
								<em><?php printf(gettext('The following are options specifically implemented by %s.'), $theme['name']); ?></em>
							</p>
						</div>
						<table id="customOptions" class="colwidth">
							<?php
							customOptions($optionHandler, '', $album, false, $supportedOptions, $themename);
							?>
						</table>
						<?php
					}
					?>
					<br clear="all">
				</div>
				<p>
					<?php
					applyButton();
					npgButton('button', CLOCKWISE_OPEN_CIRCLE_ARROW_GREEN . ' ' . gettext("Revert to default"), array('buttonClick' => "$('#savethemeoptions').val('reset');$('#themeoptionsform').submit();"));
					resetButton();
					?>
				</p>
				<br clear="all">
			</form>
		</div>
		<!-- end of tab_theme div -->
		<?php
	}
}
