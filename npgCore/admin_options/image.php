<?php
/*
 * Guts of the image options tab
 */
$optionRights = OPTIONS_RIGHTS;

require_once(CORE_SERVERPATH . 'lib-Imagick.php');
require_once(CORE_SERVERPATH . 'lib-GD.php');

function saveOptions() {
	global $_gallery, $_images_classes, $_exifvars, $_configMutex;

	$notify = $returntab = NULL;

	setOption('image_quality', sanitize($_POST['imagequality'], 3));
	setOption('thumb_quality', sanitize($_POST['thumbquality'], 3));
	setOption('image_allow_upscale', (int) isset($_POST['image_allow_upscale']));
	setOption('thumb_sharpen', (int) isset($_POST['thumb_sharpen']));
	setOption('image_sharpen', (int) isset($_POST['image_sharpen']));
	setOption('image_interlace', (int) isset($_POST['image_interlace']));
	setOption('ImbedIPTC', (int) isset($_POST['ImbedIPTC']));
	setOption('sharpen_amount', sanitize_numeric($_POST['sharpen_amount']));
	$num = str_replace(',', '.', sanitize($_POST['sharpen_radius']));
	if (is_numeric($num)) {
		setOption('sharpen_radius', $num);
	}
	setOption('sharpen_threshold', sanitize_numeric($_POST['sharpen_threshold']));

	if (isset($_POST['fullimage_watermark'])) {
		$new = sanitize($_POST['fullimage_watermark'], 3);
		setOption('fullimage_watermark', $new);
	}
	if (isset($_POST['fullsizeimage_watermark'])) {
		$new = sanitize($_POST['fullsizeimage_watermark'], 3);
		setOption('fullsizeimage_watermark', $new);
	}

	setOption('watermark_scale', sanitize($_POST['watermark_scale'], 3));
	setOption('watermark_allow_upscale', (int) isset($_POST['watermark_allow_upscale']));
	setOption('watermark_h_offset', sanitize($_POST['watermark_h_offset'], 3));
	setOption('watermark_w_offset', sanitize($_POST['watermark_w_offset'], 3));
	setOption('encoding_fallback', (int) isset($_POST['encoding_fallback']));
	if (isset($_POST['encoding_fallback']) && sanitize($_POST['image_cache_suffix']) == FALLBACK_SUFFIX) {
		$_POST['image_cache_suffix'] = '';
	}
	setOption('image_cache_suffix', sanitize($_POST['image_cache_suffix']));
	$imageplugins = array_unique($_images_classes);
	foreach ($imageplugins as $plugin) {
		$opt = $plugin . '_watermark';
		if (isset($_POST[$opt])) {
			$new = sanitize($_POST[$opt], 3);
			setOption($opt, $new);
		}
	}

	setOption('full_image_quality', sanitize($_POST['fullimagequality'], 3));
	setOption('cache_full_image', (int) isset($_POST['cache_full_image']));
	setOption('protect_full_image', sanitize($_POST['protect_full_image'], 3));
	$processNotify = processCredentials('protected_image');
	if ($processNotify) {
		if ($notify) {
			$notify .= str_replace('?', '&', $processNotify);
		} else {
			$notify = $processNotify;
		}
	}

	setOption('secure_image_processor', (int) isset($_POST['secure_image_processor']));
	if (isset($_POST['protected_image_cache'])) {
		setOption('protected_image_cache', 1);
		copy(CORE_SERVERPATH . 'cacheprotect', SERVERPATH . '/' . CACHEFOLDER . '/.htaccess');
		chmod(SERVERPATH . '/' . CACHEFOLDER . '/.htaccess', 0444);
	} else {
		if (file_exists(SERVERPATH . '/' . CACHEFOLDER . '/.htaccess')) {
			chmod(SERVERPATH . '/' . CACHEFOLDER . '/.htaccess', 0777);
			unlink(SERVERPATH . '/' . CACHEFOLDER . '/.htaccess');
		}
		setOption('protected_image_cache', 0);
	}

	setOption('hotlink_protection', (int) isset($_POST['hotlink_protection']));
	setOption('use_lock_image', (int) isset($_POST['use_lock_image']));
	$sorttype = sanitize($_POST['image_sorttype'], 3);
	if ($sorttype == 'custom') {
		if (isset($_POST['customimagessort'])) {
			$sorttype = implode(',', sanitize($_POST['customimagessort']));
		} else {
			$sorttype = $_gallery->getSortType('image');
		}
	}
	$_gallery->setSortType($sorttype, 'image');
	$_gallery->setSortDirection((int) isset($_POST['image_sortdirection']), 'image');
	setOption('use_embedded_thumb', (int) isset($_POST['use_embedded_thumb']));
	setOption('IPTC_encoding', sanitize($_POST['IPTC_encoding']));
	setOption('transform_newlines', (int) isset($_POST['transform_newlines']));
	$disableEmpty = isset($_POST['disableEmpty']);

	$oldDisabled = getSerializedArray(getOption('metadata_disabled'));

	$dbChange = $enableSource = $disableSource = $disable = $display = array();

	if (isset($_POST['restore_to_defaults'])) {
		$exifvars = npgFunctions::exifvars(true);

		foreach ($exifvars as $key => $item) {
			if ($exifvars[$key][EXIF_DISPLAY]) {
				$display[$key] = $key;
			}
			if (!$exifvars[$key][EXIF_FIELD_ENABLED]) {
				$disable[$key] = $key;
			}
			if ($item[EXIF_FIELD_SIZE]) { // item has data (size != 0)
				if ((int) in_array($key, $oldDisabled) != (int) !$exifvars[$key][EXIF_FIELD_ENABLED]) {
					$dbChange[$item[EXIF_SOURCE] . ' Metadata'] = $item[EXIF_SOURCE] . ' Metadata';
				}
			}
		}
	} else {
		foreach ($_exifvars as $key => $item) {
			if (isset($_POST[$key])) {
				if ($item[EXIF_FIELD_LINKED] && !$_exifvars[$item[EXIF_FIELD_LINKED]][EXIF_FIELD_ENABLED]) {
					$v = 2;
				} else {
					$v = sanitize_numeric($_POST[$key]);
				}
			} else {
				$v = 2;
			}

			switch ($v) {
				case 1: //show
					$display[$key] = $key;
				case 0: //hide
					if ($item[EXIF_FIELD_SIZE]) { // item has data (size != 0)
						if ($disableEmpty) {
							$sql = "SELECT `id`, $key FROM " . prefix('images') . " WHERE $key IS NOT NULL AND TRIM($key) <> '' LIMIT 1";
							$found = query($sql, false);
							if (!$found) {
								$disable[$key] = $key;
								$dbChange[$item[EXIF_SOURCE] . ' Metadata'] = $item[EXIF_SOURCE] . ' Metadata';
							}
						}
						if (in_array($key, $oldDisabled)) {
							$dbChange[$item[EXIF_SOURCE] . ' Metadata'] = $item[EXIF_SOURCE] . ' Metadata';
							$enableSource[] = $item[EXIF_SOURCE] . ' Metadata';
						}
					}
					break;
				case 2: //disable
					if ($item[EXIF_FIELD_SIZE]) { // item has data (size != 0)
						if (!in_array($key, $oldDisabled)) {
							$dbChange[$item[EXIF_SOURCE] . ' Metadata'] = $item[EXIF_SOURCE] . ' Metadata';
							$disableSource[] = $item[EXIF_SOURCE] . ' Metadata';
						}
					}
					$disable[$key] = $key;
					break;
			}
		}

		foreach ($_exifvars as $key => $item) {
			if ($item[EXIF_FIELD_LINKED]) {
				$d = $_exifvars[$item[EXIF_FIELD_LINKED]][EXIF_FIELD_ENABLED];
				if ($item[EXIF_FIELD_SIZE]) { // item has data (size != 0)
					if ($d == in_array($key, $oldDisabled)) {
						$dbChange[$item[EXIF_SOURCE] . ' Metadata'] = $item[EXIF_SOURCE] . ' Metadata';
					}
				}
				if (!$d) {
					$disable[$key] = $key;
				} else {
					unset($disable[$key]);
				}
			}
		}
	}

	setOption('metadata_disabled', serialize($disable));
	setOption('metadata_displayed', serialize($display));

	foreach ($dbChange as $requestor) {
		switch ((int) in_array($requestor, $enableSource) + 2 * (int) in_array($requestor, $disableSource)) {
			case 1:
				$report = gettext('Metadata fields will be added to the Image object.');
				break;
			case 2:
				$report = gettext('Metadata fields will be <span style="color:red;font-weight:bold;">dropped</span> from the Image object.');
				break;
			case 3:
				$report = gettext('Metadata fields will be added and <span style="color:red;font-weight:bold;">dropped</span> from the Image object.');
				break;
			default:
				$report = gettext('Metadata fields will be unchanged.');
				break;
		}
		requestSetup($requestor, $report);
	}

	$_gallery->save();
	$returntab = "&tab=image";

	return array($returntab, $notify, NULL, NULL, NULL);
}

function getOptionContent() {
	global $_gallery, $_images_classes, $_exifvars, $_graphics_optionhandlers, $_sortby, $_cachefileSuffix, $_UTF8;
	?>

	<script type="text/javascript">

		function checkMeta(cls) {
			$('.' + cls).prop('checked', 'checked');
		}
		function setMetaDefaults() {
			$('.showMeta').prop('checked', 'checked');
	<?php
	$exifstuff = npgFunctions::exifvars(true);
	foreach ($exifstuff as $key => $data) {
		if ($data[EXIF_FIELD_LINKED] && !$exifstuff[$data[EXIF_FIELD_LINKED]][EXIF_FIELD_ENABLED]) {
			?>
					$('#<?php echo $key; ?>_disable').prop('checked', 'checked');
					$('.<?php echo $key; ?>_metaDisable').prop('disabled', 'disabled');
			<?php
		} else {
			?>
					$('.<?php echo $key; ?>_metaDisable').removeAttr('disabled');
			<?php
			if (!$data[EXIF_FIELD_ENABLED]) {
				?>
						$('#<?php echo $key; ?>_disable').prop('checked', 'checked');
				<?php
			} else if (!$data[EXIF_DISPLAY] || !$data[EXIF_FIELD_SIZE]) {
				?>
						$('#<?php echo $key; ?>_hide').prop('checked', 'checked');
				<?php
			}
		}
	}
	?>
		}
		$(function () {
			$("#resizable").resizable({
				minHeight: 120,
				resize: function (event, ui) {
					$(this).css("width", '');
					$('#metadatalist').height($('#resizable').height());
				}
			});
		});

	</script>

	<div id="tab_image" class="tabbox">
		<form class="dirtylistening" onReset="setClean('form_options');" id="form_options" action="?action=saveoptions" method="post" autocomplete="off" >
			<?php XSRFToken('saveoptions'); ?>
			<input type="hidden" name="saveoptions" value="image" />
			<p align="center">
				<?php echo gettext('See also the <a href="?tab=theme">Theme Options</a> for theme specific image options.'); ?>
			</p>
			<p>
				<?php
				applyButton();
				resetButton();
				?>
			</p>
			<br clear="all">
			<div id="columns">
				<table id="npgOptions">
					<?php
					foreach ($_graphics_optionhandlers as $handler) {
						customOptions($handler, '');
					}
					?>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Sort images by"); ?></td>
						<td class="option_value">
							<?php
							$sort = $_sortby;
							$cvt = $cv = IMAGE_SORT_TYPE;
							$sort[gettext('Custom')] = 'custom';

							/*
							 * not recommended--screws with peoples minds during pagination!
							  $sort[gettext('Random')] = 'random';
							 */
							$flip = array_flip($sort);
							if (isset($flip[$cv])) {
								$dspc = 'none';
							} else {
								$dspc = 'block';
							}
							if (($cv == 'manual') || ($cv == 'random') || ($cv == '')) {
								$dspd = 'none';
							} else {
								$dspd = 'block';
							}
							?>
							<span class="nowrap">
								<select id="imagesortselect" name="image_sorttype" onchange="update_direction(this, 'image_sortdirection', 'customTextBox3')">
									<?php
									if (array_search($cv, $sort) === false)
										$cv = 'custom';
									generateListFromArray(array($cv), $sort, false, true);
									?>
								</select>
								<label id="image_sortdirection" style="display:<?php echo $dspd; ?>white-space:nowrap;">
									<input type="checkbox" name="image_sortdirection"	value="1" <?php checked('1', $_gallery->getSortDirection('image')); ?> />
									<?php echo gettext("descending"); ?>
								</label>
							</span>

							<span id="customTextBox3" class="customText" style="display:<?php echo $dspc; ?>">
								<br />
								<?php echo gettext('custom fields') ?>
								<span class="tagSuggestContainer">
									<span class="tagSuggestContainer">
										<ul class="searchchecklist">
											<?php dbFieldSelector('images', $cvt); ?>
										</ul>
									</span>
								</span>
							</span>

						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<p><?php echo gettext("Default sort order for images."); ?></p>
									<p><?php echo gettext('Custom sort values must be database field names. You can have multiple fields separated by commas.') ?></p>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Image quality"); ?></td>
						<td class="option_value">
							<?php putSlider(gettext('normal image'), 'imagequality', 0, 100, getOption('image_quality')); ?>
							<?php putSlider(gettext('<em>full</em> Image'), 'fullimagequality', 0, 100, getOption('full_image_quality')); ?>
							<?php putSlider(gettext('thumbnail'), 'thumbquality', 0, 100, getOption('thumb_quality')); ?>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">

									<p><?php echo gettext("Compression quality for generated images and thumbnails generated."); ?></p>
									<p><?php echo gettext("Quality ranges from 0 (worst quality, smallest file) to 100 (best quality, biggest file). "); ?></p>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Interlace"); ?></td>
						<td class="option_value">
							<label>
								<input type="checkbox" name="image_interlace" value="1" <?php checked('1', getOption('image_interlace')); ?> />
							</label>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext("If checked, resized images will be created <em>interlaced</em> (if the format permits)."); ?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext('Use embedded thumbnail'); ?></td>
						<?php
						if (function_exists('exif_thumbnail')) {
							$disabled = '';
						} else {
							$disabled = ' disabled="disabled"';
							setOption('use_embedded_thumb', 0);
						}
						?>
						<td class="option_value">
							<label>
								<input type="checkbox" name="use_embedded_thumb" value="1" <?php checked('1', getOption('use_embedded_thumb')); ?>
											 <?php echo $disabled; ?> />
							</label>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<p><?php echo gettext('If set, thumbnail imbedded in the image will be used when creating a cached image that is equal or smaller in size. Note: the quality of this image varies by camera and its orientation may not match the master image.'); ?></p>
									<?php
									if ($disabled) {
										?>
										<p class="notebox"><?php echo gettext('The PHP EXIF extension is required for this option.') ?></p>
										<?php
									}
									?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Allow upscale"); ?></td>
						<td class="option_value">
							<input type="checkbox" name="image_allow_upscale" value="1" <?php checked('1', getOption('image_allow_upscale')); ?> />
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext("Allow images to be scaled up to the requested size. This could result in loss of quality, so it is off by default."); ?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Sharpen"); ?></td>
						<td class="option_value">
							<!--<span class="nowrap">-->
							<label>
								<input type="checkbox" name="image_sharpen" value="1" <?php checked('1', getOption('image_sharpen')); ?> />
								<?php echo gettext('images'); ?>
							</label>
							<label>
								<input type="checkbox" name="thumb_sharpen" value="1" <?php checked('1', getOption('thumb_sharpen')); ?> />
								<?php echo gettext('thumbnails'); ?>
							</label>
							</span>
							<br />
							<?php putSlider(gettext('amount'), 'sharpen_amount', 0, 100, getOption('sharpen_amount')); ?>

							<table>
								<tr class="optionSet">
									<td class="image_option_tablerow"><?php echo gettext('Radius'); ?>&nbsp;</td>
									<td class="image_option_tablerow"><input type="text" name = "sharpen_radius" size="2" value="<?php echo getOption('sharpen_radius'); ?>" /></td>
								</tr>
								<tr class="optionSet">
									<td class="image_option_tablerow"><?php echo gettext('Threshold'); ?>&nbsp;</td>
									<td class="image_option_tablerow"><input type="text" name = "sharpen_threshold" size="3" value="<?php echo getOption('sharpen_threshold'); ?>" /></td>
								</tr>
							</table>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<p><?php echo gettext("Add an unsharp mask to images and/or thumbnails.") . "</p><p class='notebox'>" . gettext("<strong>WARNING</strong>: can overload slow servers."); ?></p>
									<p><?php echo gettext("<em>Amount</em>: the strength of the sharpening effect. Values are between 0 (least sharpening) and 100 (most sharpening)."); ?></p>
									<p><?php echo gettext("<em>Radius</em>: the pixel radius of the sharpening mask. A smaller radius sharpens smaller details, and a larger radius sharpens larger details."); ?></p>
									<p><?php echo gettext("<em>Threshold</em>: the color difference threshold required for sharpening. A low threshold sharpens all edges including faint ones, while a higher threshold only sharpens more distinct edges."); ?></p>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Watermarks"); ?></td>
						<td class="option_value">
							<table>
								<tr class="optionSet">
									<td class="image_option_tablerow"><?php echo gettext('images'); ?> </td>
									<td class="image_option_tablerow">
										<select id="fullimage_watermark" name="fullimage_watermark">
											<?php $current = IMAGE_WATERMARK; ?>
											<option value="" <?php if (empty($current)) echo ' selected="selected"' ?> style="background-color:LightGray"><?php echo gettext('none'); ?></option>
											<?php
											$watermarks = getWatermarks();
											generateListFromArray(array($current), $watermarks, false, false);
											?>
										</select>
									</td>
								</tr>
								<tr class="optionSet">
									<td class="image_option_tablerow"><?php echo gettext('full sized images'); ?> </td>
									<td class="image_option_tablerow">
										<select id="fullsizeimage_watermark" name="fullsizeimage_watermark">
											<?php $current = FULLIMAGE_WATERMARK; ?>
											<option value="" <?php if (empty($current)) echo ' selected="selected"' ?> style="background-color:LightGray"><?php echo gettext('none'); ?></option>
											<?php
											$watermarks = getWatermarks();
											generateListFromArray(array($current), $watermarks, false, false);
											?>
										</select>
									</td>
								</tr>
								<?php
								$imageplugins = array_unique($_images_classes);
								ksort($imageplugins, SORT_LOCALE_STRING);
								foreach ($imageplugins as $plugin) {
									$opt = $plugin . '_watermark';
									$current = getOption($opt);
									?>
									<tr class="optionSet">
										<td class="image_option_tablerow">
											<?php printf(gettext('%s thumbnails'), lcfirst(gettext($plugin))); ?>
										</td>
										<td class="image_option_tablerow">
											<select id="<?php echo $opt; ?>" name="<?php echo $opt; ?>">
												<option value="" <?php if (empty($current)) echo ' selected="selected"' ?> style="background-color:LightGray">
													<?php
													if ($plugin == 'Image')
														echo gettext('none');
													else
														echo gettext('image thumbnail')
														?>
												</option>
												<?php
												$watermarks = getWatermarks();
												generateListFromArray(array($current), $watermarks, false, false);
												?>
											</select>
										</td>
									</tr>
									<?php
								}
								?>
							</table>
							<p class="nowrap">
								<?php echo gettext('cover') . ' '; ?>
								<input type="text" size="2" name="watermark_scale"
											 value="<?php echo html_encode(getOption('watermark_scale')); ?>" /><?php /* xgettext:no-php-format */ echo gettext('% of image') ?>
								<label>
									<input type="checkbox" name="watermark_allow_upscale" value="1"	<?php checked('1', getOption('watermark_allow_upscale')); ?> />
									<?php echo gettext("allow upscale"); ?>
								</label>
							</p>
							<p class="nowrap">
								<?php echo gettext("offset h"); ?>
								<input type="text" size="2" name="watermark_h_offset"
											 value="<?php echo html_encode(getOption('watermark_h_offset')); ?>" /><?php echo /* xgettext:no-php-format */ gettext("% w, "); ?>
								<input type="text" size="2" name="watermark_w_offset"
											 value="<?php echo html_encode(getOption('watermark_w_offset')); ?>" /><?php /* xgettext:no-php-format */ echo gettext("%"); ?>
							</p>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<p><?php echo gettext("The watermark image is scaled by to cover <em>cover percentage</em> of the image and placed relative to the upper left corner of the image."); ?></p>
									<p><?php echo gettext("It is offset from there (moved toward the lower right corner) by the <em>offset</em> percentages of the height and width difference between the image and the watermark."); ?></p>
									<p><?php echo gettext("If <em>allow upscale</em> is not checked the watermark will not be made larger than the original watermark image."); ?></p>
									<p><?php printf(gettext('Custom watermarks should be placed in the <code>/%s/watermarks/</code> folder. The images must be in png-24 format.'), USER_PLUGIN_FOLDER); ?></p>

								</div>
							</span>
						</td>
					</tr>
					<?php
					$cachesuffix = array_unique($_cachefileSuffix);
					//	we don't want to cache to these formats
					unset($cachesuffix['XBM']);
					unset($cachesuffix['XPM']);
					unset($cachesuffix['TGA']);
					unset($cachesuffix['GIF']);
					unset($cachesuffix['BMP']);
					unset($cachesuffix['WBMP']);
					unset($cachesuffix['WBM']);
					if (in_array(FALLBACK_SUFFIX, $cachesuffix)) {
						if (MOD_REWRITE) {
							if (getOption('encoding_fallback')) {
								$enabled = ' checked="checked"';
							} else {
								$enabled = '';
							}
						} else {
							$enabled = ' disabled="disabled"';
						}
						?>
						<tr class="optionSet">
							<td class="option_name"><?php printf(gettext('%1$s <em>fallback</em>'), FALLBACK_SUFFIX); ?></td>
							<td class="option_value">
								<label>
									<input type="checkbox" name="encoding_fallback" value="fallback"<?php echo $enabled; ?> />
								</label>
							</td>
							<td class = "option_desc">
								<span class = "option_info">
									<?php echo INFORMATION_BLUE;
									?>
									<div class="option_desc_hidden">
										<?php
										printf(gettext('If %1$s <em>fallback</em> is checked, the standard functions to display images will offer %1$s images and allow the browser to fall back to the <em>Cache as</em> selection if <em>%1$s</em> is not supported.'), FALLBACK_SUFFIX);
										if (!MOD_REWRITE) {
											echo '<br /><br />' . sprintf(gettext('%1$s <em>fallback</em> requires that mod rewrite be enabled.'), FALLBACK_SUFFIX);
										}
										?>
									</div>
								</span>
							</td>
						</tr>
						<?php
					}
					?>				<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Cache as"); ?></td>
						<td class="option_value">
							<?php
							$type = IMAGE_CACHE_SUFFIX;
							?>
							<input type="radio" name="image_cache_suffix" value=""<?php if (empty($type)) echo ' checked="checked"'; ?> />&nbsp;<?php echo gettext("original"); ?>
							<?php
							foreach ($cachesuffix as $suffix) {
								if ($suffix) {
									$checked = '';
									if (ENCODING_FALLBACK && $suffix == FALLBACK_SUFFIX) {
										$disable = ' disabled="disabled"';
									} else {
										$disable = '';
										if ($type == $suffix) {
											$checked = ' checked="checked"';
										}
									}
									?>
									<label>
										<input type="radio" name="image_cache_suffix" value="<?php echo $suffix; ?>"<?php echo $checked . $disable ?> />&nbsp;<?php echo $suffix; ?>
									</label>
									<?php
								}
							}
							?>
						</td>
						<td class = "option_desc">
							<span class = "option_info">
								<?php echo INFORMATION_BLUE;
								?>
								<div class="option_desc_hidden">
									<?php
									echo gettext("Select a type for the images stored in the image cache. Select <em>Original</em> to preserve the original image’s type.");
									?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Protect image cache"); ?></td>
						<td class="option_value">
							<input type="checkbox" name="protected_image_cache" value="1" <?php checked('1', getOption('protected_image_cache')); ?> />
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php
									echo gettext('If checked all image URIs will link to the image processor and the image cache will be disabled to browsers via an <em>.htaccess</em> file. Images are still cached but the image processor is used to serve the image rather than allowing the browser to fetch the file.') .
									'<p class="notebox">' . gettext('<strong>WARNING	:</strong> This option adds significant overhead to <strong>each and every</strong> image reference! Some <em>JavaScript</em> and <em>Flash</em> based image handlers will not work with an image processor URI and are incompatible with this option.') . '</p>';
									?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Secure image processor"); ?></td>
						<td class="option_value">
							<input type="checkbox" name="secure_image_processor" value="1" <?php checked('1', getOption('secure_image_processor')); ?> />
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php
									echo gettext('When enabled, the image processor will check album access credentials.') .
									'<p class="notebox">' . gettext('<strong>WARNING	:</strong> This option adds memory overhead to image caching! You may be unable to cache some images depending on your server memory availability.') . '</p>';
									?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Full image protection"); ?></td>
						<td class="option_value" style="margin:0">
							<label>
								<input type="checkbox" name="hotlink_protection" value="1" <?php checked('1', getOption('hotlink_protection')); ?> />
								<?php echo gettext('disable hotlinking'); ?>
							</label>
							<br />
							<label>
								<input type="checkbox" name="cache_full_image" value="1" <?php checked('1', getOption('cache_full_image')); ?> />
								<?php echo gettext('cache the full image'); ?>
							</label>
							<br />

							<input	type="hidden" name="password_enabled" id="password_enabled" value="0" />
							<?php
							if (GALLERY_SECURITY == 'public') {
								?>
								<br class="clearall" />
								<table class="compact">
									<tr class="passwordextrashow">
										<td style="margin:0; padding:0">
											<a onclick="toggle_passwords('', true);">
												<?php echo gettext("password"); ?>
											</a>
										</td>
										<td style="margin:0; padding:0">
											<?php
											$x = getOption('protected_image_password');
											if (empty($x)) {
												?>
												<?php echo LOCK_OPEN; ?>
												<?php
											} else {
												$x = '          ';
												?>
												<a onclick="resetPass('');" title="<?php echo gettext('clear password'); ?>">
													<?php echo LOCK; ?>
												</a>
												<?php
											}
											?>
										</td>
									</tr>
									<tr class="passwordextrahide" style="display:none">
										<td style="margin:0; padding:0">
											<a onclick="toggle_passwords('', false);">
												<?php echo gettext("user"); ?>
											</a>
										</td>
										<td style="margin:0; padding:0">
											<input type="text" size="30"
														 class="passignore ignoredirty" autocomplete="off"
														 onkeydown="passwordClear('');"
														 id="user_name"  name="user"
														 value="<?php echo html_encode(getOption('protected_image_user')); ?>" />
										</td>
									</tr>
									<tr class="passwordextrahide" style="display:none" >
										<td style="margin:0; padding:0">
											<span id="strength">
												<?php echo gettext("password"); ?>
											</span>
											<br />
											<span id="match" class="password_field_">
												<?php echo gettext("(repeat)"); ?>
											</span>
										</td>
										<td style="margin:0; padding:0">
											<input type="password"
														 class="passignore ignoredirty" autocomplete="off"
														 size="30"
														 id="pass" name="pass"
														 onkeydown="passwordClear('');"
														 onkeyup="passwordStrength('');"
														 value="<?php echo $x; ?>" />
											<label>
												<input type="checkbox"
															 name="disclose_password"
															 id="disclose_password"
															 onclick="passwordClear('');
																	 togglePassword('');" />
															 <?php echo gettext('Show'); ?>
											</label>

											<br />
											<span class="password_field_">
												<input type="password"
															 class="passignore ignoredirty" autocomplete="off"
															 size="30"
															 id="pass_r" name="pass_r" disabled="disabled"
															 onkeydown="passwordClear('');"
															 onkeyup="passwordMatch('');"
															 value="<?php echo $x; ?>" />
											</span>
										</td>
									</tr>
									<tr class="passwordextrahide" style="display:none" >
										<td style="margin:0; padding:0">
											<?php echo gettext("hint"); ?>
										</td>
										<td style="margin:0; padding:0">
											<?php print_language_string_list(getOption('protected_image_hint'), 'hint', false, NULL, 'hint', '100%'); ?>
										</td>
									</tr>
								</table>
								<?php
							}
							?>
							<p>
								<?php
								echo "<select id=\"protect_full_image\" name=\"protect_full_image\">\n";
								$protection = getOption('protect_full_image');
								$list = array(gettext('Protected view') => 'Protected', gettext('Download') => 'Download', gettext('No access') => 'No access');
								if (getOption('album_folder_class') != 'external') {
									$list[gettext('Unprotected')] = 'Unprotected';
								}
								generateListFromArray(array($protection), $list, false, true);
								echo "</select>\n";
								?>
							</p>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<p><?php echo gettext("Disabling hotlinking prevents linking to the full image from other domains. If enabled, external links are redirect to the image page. If you are having problems with full images being displayed, try disabling this setting. Hotlinking is not prevented if <em>Full&nbsp;image&nbsp;protection</em> is <em>Unprotected</em> or if the image is cached."); ?></p>
									<p><?php echo gettext("If <em>Cache the full image</em> is checked the full image will be loaded to the cache and served from there after the first reference. <em>Full&nbsp;image&nbsp;protection</em> must be set to <em>Protected&nbsp;view</em> for the image to be cached. However, once cached, no protections are applied to the image."); ?></p>
									<p><?php echo gettext("The <em>user</em>, <em>password</em>, and <em>hint</em> apply to the <em>Download</em> and <em>Protected view</em> level of protection. If there is a password set, the viewer must supply this password to access the image."); ?></p>
									<p><?php echo gettext("Select the level of protection for full sized images. <em>Download</em> forces a download dialog rather than displaying the image. <em>No&nbsp;access</em> prevents a link to the image from being shown. <em>Protected&nbsp;view</em> forces image processing before the image is displayed, for instance to apply a watermark or to check passwords. <em>Unprotected</em> allows direct display of the image."); ?></p>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("Use lock image"); ?></td>
						<td class="option_value">
							<input type="checkbox" name="use_lock_image" value="1"
										 <?php checked('1', getOption('use_lock_image')); ?> />
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext("Substitute a <em>lock</em> image for thumbnails of password protected albums when the viewer has not supplied the password. If your theme supplies an <code>images/err-passwordprotected.png</code> image, it will be shown. Otherwise the default lock image is displayed."); ?>
								</div>
							</span>
						</td>
					</tr>

					<tr class="optionSet">
						<td class="option_name"><?php
							echo gettext("Metadata");
							$exifstuff = sortMultiArray($_exifvars, array(EXIF_DISPLAY_TEXT, EXIF_SOURCE));
							?></td>
						<td class="option_value">
							<div id="resizable">
								<ul id="metadatalist" class="metadatalist">
									<?php
									foreach ($exifstuff as $key => $item) {
										$checked_show = $checked_hide = $checked_disabled = '';
										$class_show = ' class="showMeta ' . $key . '_metaDisable"';
										$class_hide = ' class="hideMeta ' . $key . '_metaDisable"';
										if ($item[EXIF_FIELD_LINKED] && !$exifstuff[$item[EXIF_FIELD_LINKED]][EXIF_FIELD_ENABLED]) {
											$checked_hide = $checked_show = ' disabled="disabled"';
											$checked_disabled = ' checked="checked" disabled="disabled"';
										} else {
											if (!$item[EXIF_FIELD_ENABLED]) {
												$checked_disabled = ' checked="checked"';
											} else {
												if ($item[EXIF_DISPLAY]) {
													$checked_show = ' checked="checked"';
												} else {
													$checked_hide = ' checked="checked"';
												}
											}
										}
										if (!$item[EXIF_FIELD_SIZE]) {
											$checked_show = ' disabled="disabled"';
											$class_show = '';
											$class_hide = ' class="showMeta hideMeta"';
										}
										?>
										<li class="nowrap">
											<label title="<?php echo gettext('show'); ?>">
												<input id="<?php echo $key; ?>_show" name="<?php echo $key; ?>" type="radio" <?php echo $class_show . $checked_show ?> value="1" />

												<?php echo CHECKMARK_GREEN; ?>
											</label>
											<label title="<?php echo gettext('hide'); ?>">
												<input id="<?php echo $key; ?>_hide" name="<?php echo $key; ?>" type="radio" <?php echo $class_hide . $checked_hide ?> value="0" />
												<?php echo HIDE_ICON; ?>
											</label>
											<label title="<?php echo gettext('disable'); ?>">
												<input id="<?php echo $key; ?>_disable" name="<?php echo $key; ?>" type="radio" class="<?php echo $key; ?>_metaDisable"<?php echo $checked_disabled ?> value="2" />
												<?php echo CROSS_MARK_RED; ?>
											</label>
											<?php echo $item[EXIF_DISPLAY_TEXT] . ' {' . $item[EXIF_SOURCE] . '}'; ?>
										</li>
										<?php
									}
									?>
								</ul>
							</div>
							<span class="floatright">
								<label title="<?php echo gettext('restore defaults'); ?>">
									<input type="radio" name="all_metadata" onclick="setMetaDefaults();" />
									<?php echo gettext('default'); ?>
								</label>
								<label title="<?php echo gettext('show'); ?>">
									<input type="radio" name="all_metadata" onclick="checkMeta('showMeta');" />
									<?php echo CHECKMARK_GREEN; ?>
								</label>
								<label title="<?php echo gettext('hide'); ?>">
									<input type="radio" name="all_metadata" onclick="checkMeta('hideMeta');" />
									<?php echo HIDE_ICON; ?>
								</label>
								<label title="<?php echo gettext('disable'); ?>">
									<input type="radio" name="all_metadata" onclick="checkMeta('disableMeta');" />
									<?php echo CROSS_MARK_RED; ?>
								</label>
								&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							</span>

							<br clear="all"/>
							<p>
								<label>
									<input type="checkbox" name="restore_to_defaults" value="1" />
									<?php echo gettext('restore fields to defaults'); ?>
								</label>
								<br />
								<label>
									<input type="checkbox" name="disableEmpty" value="1" />
									<?php echo gettext('mark unused fields <em>do not process</em>'); ?>
								</label>
								<br />
								<label>
									<input type="checkbox" name="transform_newlines" value="1" />
									<?php echo gettext('replace newlines'); ?>
								</label>

							</p>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<p>
										<?php echo gettext("Select how image metadata fields are handled."); ?>
									<ul style="list-style: none;">
										<li>
											<?php echo CHECKMARK_GREEN; ?>
											<?php echo gettext('Show the field'); ?>
										</li>
										<li>
											<?php echo HIDE_ICON; ?>
											<?php echo gettext('Hide the field'); ?>
										</li>
										<li>
											<?php echo CROSS_MARK_RED; ?>
											<?php echo gettext('Do not process the field'); ?>
										</li>
									</ul>
									</p>
									<p>
										<?php echo gettext('Hint: you can drag down the <em>drag handle</em> in the lower right corner to show more selections.') ?>
									</p>
									<p><?php echo gettext('If <em>restore fields to defaults</em> is selected the default values for <code>show</code>, <code>hide</code>, and <code>Do not process</code> will be restored.'); ?></p>
									<?php echo gettext('Columns for fields marked <em>do not process</em> will be removed from the database on the next <code>setup</code> execution. Selecting the <em>Mark unused fields do not process</em> will cause metadata fields that have no values to be marked <em>do not process</em> allowing them to be removed from the database.') ?>
									</p>
									<p><?php echo gettext('If <em>replace newlines</em> is selected <code>&lt;br /&gt;</code> will replace <em>newline</em> characters from image metadata destined for <em>titles</em> and <em>descriptions</em>. This happens only when the metadata is imported so you may need to refresh your metadata to see the results.'); ?></p>
								</div>
							</span>
						</td>
					</tr>
					<?php
					$sets = array_merge($_UTF8->iconv_sets, $_UTF8->mb_sets);
					ksort($sets, SORT_LOCALE_STRING);
					if (!empty($sets)) {
						?>
						<tr class="optionSet">
							<td class="option_name"><?php echo gettext("IPTC encoding"); ?></td>
							<td class="option_value">
								<select id="IPTC_encoding" name="IPTC_encoding">
									<?php generateListFromArray(array(getOption('IPTC_encoding')), array_flip($sets), false, true) ?>
								</select>
							</td>
							<td class="option_desc">
								<span class="option_info">
									<?php echo INFORMATION_BLUE; ?>
									<div class="option_desc_hidden">
										<?php echo gettext("The default character encoding of image IPTC metadata."); ?>
									</div>
								</span>
							</td>
						</tr>
						<?php
					}
					if (GRAPHICS_LIBRARY == 'Imagick') {
						$optionText = gettext('Imbed IPTC copyright');
						$desc = gettext('If checked and an image has no IPTC data a copyright notice will be imbedded cached copies.');
					} else {
						$optionText = gettext('replicate IPTC metadata');
						$desc = gettext('If checked IPTC data from the original image will be imbedded in cached copies. If the image has no copyright notice the gallery copyright will be used.');
					}
					?>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext("IPTC Imbedding"); ?></td>
						<td class="option_value">
							<label>
								<input type="checkbox" name="ImbedIPTC" value="1"	<?php checked('1', getOption('ImbedIPTC')); ?> />
								<?php echo $optionText; ?>
							</label>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo $desc; ?>
									<p class="notebox">
										<?php echo gettext('<strong>NOTE:</strong> This option  applies only to JPEG format cached images.'); ?>
									</p>
							</span>
							</div>
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
	</div><!-- end of tab_image div -->
	<?php
}
