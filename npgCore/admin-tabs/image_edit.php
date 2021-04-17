<?php
/*
 * This is the "guts" of the edit image page
 */

require_once(CORE_SERVERPATH . 'exif/exifTranslations.php');

$singleimagelink = $singleimage = NULL;
$showfilter = true;

$stuff = array('description' => gettext('Description'), 'metadata' => gettext('Metadata'), 'geotags' => gettext('Geolocation'), 'general' => gettext('General'), 'utilities' => gettext("Utilities"));
$stuff = array_merge($stuff, npgFilters::apply('mass_edit_selector', array(), 'images'));
asort($stuff, SORT_NATURAL | SORT_FLAG_CASE);


if (isset($_GET['singleimage']) && $_GET['singleimage'] || $totalimages == 1) {
	$showfilter = !isset($_GET['singleimage']);
	if ($totalimages == 1) {
		$_GET['singleimage'] = array_shift($images);
	}
	$singleimage = sanitize($_GET['singleimage']);
	$allimagecount = 1;
	$totalimages = 1;
	$images = array($singleimage);
	$singleimagelink = '&singleimage=' . html_encode($singleimage);
}
?>
<!-- Images List -->

<div id="tab_imageinfo" class="tabbox">
	<?php
	global $albumHeritage;
	$albumHeritage = array();
	$t = explode('/', $album->name);
	While (!empty($t)) {
		$name = implode('/', $t);
		array_pop($t);
		$albumHeritage[' ' . str_repeat('» ', count($t)) . basename($name)] = $name;
	}
	consolidatedEditMessages('imageinfo');
	if (!$singleimage) {
		?>
		<div class="floatleft">
			<?php
			echo gettext("Click on the image to change the thumbnail cropping.");
			?>
		</div>
		<?php
		printEditSelector('images_edit', $stuff);
	}
	?>
	<div>
		<?php
		if ($showfilter) {
			$numsteps = ceil(min(100, $allimagecount) / ADMIN_IMAGES_STEP);
			if ($numsteps) {
				?>
				<?php
				$steps = array();
				for ($i = 1; $i <= $numsteps; $i++) {
					$steps[] = $i * ADMIN_IMAGES_STEP;
				}
				printEditDropdown('imageinfo', $steps, $imagesTab_imageCount, '&amp;filter=' . $filter);
				?>
				<br style="clear:both"/><br />
				<?php
			}
			?>
			<form  name="albumedit3" style="float: right;padding-right: 14px;"	id="form_sortselect" action="?action=sortorder"	method="post" >
				<?php XSRFToken('albumsortorder'); ?>
				<input type="hidden" name="album"	value="<?php echo $album->name; ?>" />
				<input type="hidden" name="subpage" value="<?php echo html_encode($pagenum); ?>" />
				<input type="hidden" name="tagsort" value="<?php echo html_encode($tagsort); ?>" />
				<input type="hidden" name="filter" value="<?php echo html_encode($filter); ?>" />

				<?php echo gettext('Image filter'); ?>
				<select id="filter" name="filter" onchange="launchScript('<?php echo getAdminLink('admin-tabs/edit.php'); ?>', ['page=edit', 'album=<?php echo pathurlencode($album->name); ?>', 'subpage=1', 'tab=imageinfo', 'filter=' + $('#filter').val()]);">
					<option value=""<?php if (empty($filter)) echo ' selected="selected"'; ?>><?php echo gettext('all'); ?></option>
					<option value="unpublished"<?php if ($filter == 'unpublished') echo ' selected="selected"'; ?>><?php echo gettext('unpublished'); ?></option>
					<option value="published"<?php if ($filter == 'published') echo ' selected="selected"'; ?>><?php echo gettext('published'); ?></option>
				</select>
				<?php
				$sort = $_sortby;
				unset($sort[gettext('Owner')]); //	there is only him
				foreach ($sort as $key => $value) {
					$sort[sprintf(gettext('%s (descending)'), $key)] = $value . '_DESC';
				}
				$sort[gettext('Manual')] = 'manual';
				if ($direction)
					$oldalbumimagesort = $oldalbumimagesort . '_DESC';
				echo gettext("Display images by:");
				echo '<select id="albumimagesort" name="albumimagesort" onchange="this.form.submit();">';
				generateListFromArray(array($oldalbumimagesort), $sort, false, true);
				echo '</select>';
				?>
			</form>

			<?php
		} else {
			if (isset($_GET['subpage'])) {
				$parent .= '&album=' . pathurlencode($album->name) . '&tab=imageinfo&subpage=' . html_encode(sanitize($_GET['subpage']));
			}
		}
		?>
	</div>
	<br style='clear:both'/>
	<?php
	if ($allimagecount) {
		?>
		<form class="dirtylistening" onReset="setClean('form_imageedit'); $('.resetHide').hide();" name="albumedit2"	id="form_imageedit" action="?page=edit&amp;action=save<?php echo "&amp;album=" . pathurlencode($album->name); ?>"	method="post" autocomplete="off" >
			<?php XSRFToken('albumedit'); ?>
			<input type="hidden" name="album"	value="<?php echo $album->name; ?>" />
			<input type="hidden" name="totalimages" value="<?php echo $totalimages; ?>" />
			<input type="hidden" name="subpage" value="<?php echo html_encode($pagenum); ?>" />
			<input type="hidden" name="tagsort" value="<?php echo html_encode($tagsort); ?>" />
			<input type="hidden" name="filter" value="<?php echo html_encode($filter); ?>" />

			<?php
			if ($singleimage) {
				?>
				<input type="hidden" name="singleimage" value="<?php echo html_encode($singleimage); ?>" />
				<?php
			}
			?>

			<?php $totalpages = ceil(($allimagecount / $imagesTab_imageCount)); ?>

			<div style="padding: 10px;">
				<p>
					<?php
					if (is_numeric($pagenum)) {
						$backbutton = getAdminLink('admin-tabs/edit.php') . '?page=edit' . $parent . '&filter=' . $filter;
					} else {
						$image = newImage($album, $singleimage);
						$backbutton = $image->getLink();
					}
					backButton(array('buttonLink' => $backbutton));
					applyButton();
					resetButton();
					viewButton(array('buttonLink' => $album->getLink()));
					?>
				</p>
				<?php if (!$singleimage) printBulkActions($checkarray_images, true); ?>

				<?php
				$bglevels = array('#fff', '#f8f8f8', '#efefef', '#e8e8e8', '#dfdfdf', '#d8d8d8', '#cfcfcf', '#c8c8c8');

				$currentimage = (int) (!$singleimage && true);
				if (gl_imageCanRotate()) {
					$disablerotate = '';
				} else {
					$disablerotate = ' disabled="disabled"';
				}
				$target_image_nr = '';
				$thumbnail = $album->get('thumb');
				foreach ($images as $index => $filename) {
					$image = newImage($album, $filename);
					if ($image->exists) {
						if ($index == 0) {
							printImagePagination($album, $image, $singleimage, $allimagecount, $totalimages, $pagenum, $totalpages, $filter);
						}
						?>
						<br />
						<input type="hidden" name="<?php echo $currentimage; ?>-filename"	value="<?php echo $image->filename; ?>" />
						<div  class="formlayout">
							<br class="clearall" />
							<?php
							if ($currentimage > 0) {
								echo '<hr><br />';
							}
							?>

							<div class="floatleft leftdeatil">
								<div class="edit_thumb_container">
									<?php
									if ($zoom = isImagePhoto($image)) {
										?>
										<a href="<?php echo html_encode(pathurlencode($image->getFullImageURL())); ?>" class="colorbox fullimagelink" title="<?php echo gettext('zoom'); ?>" >
											<?php
										}
										?>
										<img id="thumb_img-<?php echo $currentimage; ?>" src="<?php echo html_encode(getAdminThumb($image, 'large-uncropped')); ?>" alt="<?php echo html_encode($image->filename); ?>" />
										<?php
										if ($zoom) {
											?>
											<div class="fullimage-icon">
												<?php echo MAGNIFY; ?>
											</div>
										</a>
										<?php
									}
									?>
								</div>
								<br clear="all">
								<?php viewButton(array('buttonLink' => $image->getLink(), 'buttonClass' => 'fillwidth')); ?>
								<br style="clear: both" />
								<p>
									<?php echo gettext('<strong>Filename:</strong>'); ?>
									<br />
									<?php
									echo truncate_string($image->filename, 25);
									?>
								</p>
								<p><?php echo gettext('<strong>Image id:</strong>'); ?> <?php echo $image->getID(); ?></p>
								<p><?php echo gettext("<strong>Dimensions:</strong>"); ?><br /><?php echo $image->getWidth(); ?> x  <?php echo $image->getHeight() . ' ' . gettext('px'); ?></p>
								<p><?php echo gettext("<strong>Size:</strong>"); ?><br /><?php echo byteConvert($image->getFilesize()); ?></p>
							</div>

							<div class="floatright top bulk_checkbox">
								<?php
								if (!$singleimage) {
									?>
									<div class="page-list_icon">
										<input class="checkbox" type = "checkbox" name = "ids[]" value="<?php echo $image->getFileName(); ?>" onclick="triggerAllBox(this.form, 'ids[]', this.form.allbox);" />
									</div>
									<?php
								}
								?>
							</div>

							<div class="floatleft">
								<table class="width100percent" id="image-<?php echo $currentimage; ?>">
									<tr>
										<td class="leftcolumn">
											<?php echo gettext("Title"); ?>
										</td>
										<td class="middlecolumn">
											<?php print_language_string_list($image->getTitle('all'), $currentimage . '-title', false, NULL, '', '100%'); ?>
										</td>
									</tr>
									<tr>
										<td class="leftcolumn">
											<span class="floatright">
												<?php echo linkPickerIcon($image, 'image_link-' . $currentimage); ?>
											</span>
										<td  class="middlecolumn">
											<?php echo linkPickerItem($image, 'image_link-' . $currentimage); ?>
										</td>
									</tr>
									<tr class="description_stuff">
										<td class="leftcolumn"><?php echo gettext("Description"); ?></td>
										<td class="middlecolumn"><?php print_language_string_list($image->getDesc('all'), $currentimage . '-desc', true, NULL, 'texteditor', '100%'); ?></td>
									</tr>
									<?php
									if ($image->get('hasMetadata')) {
										?>
										<tr class="metadata_stuff">
											<td class="leftcolumn"><?php echo gettext("Metadata"); ?></td>
											<td class="middlecolumn">
												<?php
												$data = '';
												$exif = $image->getMetaData();
												if (false !== $exif) {
													foreach ($exif as $field => $value) {
														$display = $_exifvars[$field][EXIF_DISPLAY] && !empty($value) && !($_exifvars[$field][EXIF_FIELD_TYPE] == 'time' && $value == '0000-00-00 00:00:00');
														if ($display) {
															$label = $_exifvars[$field][EXIF_DISPLAY_TEXT];
															$data .= "<tr><td class=\"medtadata_tag " . html_encode($field) . "\">$label: </td> <td>" . html_encode(exifTranslate($value, $field)) . "</td></tr>\n";
														}
													}
												}
												if (empty($data)) {
													echo gettext('None selected for display');
												} else {
													?>
													<div class="metadata_container">
														<table class="metadata_table" >
															<?php echo $data; ?>
														</table>
													</div>
													<?php
												}
												?>
											</td>
										</tr>
										<?php
									}
									?>
									<tr class="geotags_stuff">
										<td class="leftcolumn"><?php echo gettext("Geolocation"); ?></td>
										<td class="middlecolumn">
											<?php
											$lat = $image->getGPSLatitude();
											if ($lat < 0) {
												$lat = image::toDMS($lat, 'S');
											} else if ($lat == 0) {
												$lat = '';
											} else {
												$lat = image::toDMS($lat, 'N');
											}
											$long = $image->getGPSLongitude();
											if ($long < 0) {
												$long = image::toDMS($long, 'W');
											} else if ($long == 0) {
												$long = '';
											} else {
												$long = image::toDMS($long, 'E');
											}
											echo gettext('latitiude');
											?>
											<input name="<?php echo $currentimage; ?>-GPSLatitude" type="text" value="<?php echo html_encode($lat); ?>">
											<?php echo gettext('longitude'); ?>
											<input name="<?php echo $currentimage; ?>-GPSLongitude" type="text" value="<?php echo html_encode($long); ?>">
										</td>
									</tr>
									<?php
									echo npgFilters::apply('edit_image_custom', '', $image, $currentimage);
									if (!$singleimage) {
										?>
										<tr>
											<td colspan="100%" style="border-bottom:none;">
												<a href="<?php echo getAdminLink('admin-tabs/edit.php') . '?page=edit&tab=imageinfo&album=' . $album->name . '&singleimage=' . $image->filename . '&subpage=' . $pagenum; ?>&filter=<?php echo $filter; ?>">
													<?php echo PENCIL_ICON; ?>
													<?php echo gettext('Edit all image data'); ?>
												</a>
											</td>
										</tr>
										<?php
									}
									?>
								</table>
							</div>

							<div class="floatleft rightcolumn">
								<h2 class="h2_bordered_edit general_stuff"><?php echo gettext("General"); ?></h2>
								<div class="box-edit general_stuff">
									<label class="checkboxlabel">
										<input type="checkbox" id="Visible-<?php echo $currentimage; ?>"
													 name="<?php echo $currentimage; ?>-Visible"
													 value="1" <?php if ($image->getShow()) echo ' checked = "checked"'; ?>
													 onclick="$('#publishdate-<?php echo $currentimage; ?>').val('');
																		 $('#expirationdate-<?php echo $currentimage; ?>').val('');
																		 $('#publishdate-<?php echo $currentimage; ?>').css('color', 'black ');
																		 $('.expire-<?php echo $currentimage; ?>').html('');" />
													 <?php echo gettext("Published"); ?>
									</label>
									<?php
									$publishdate = $image->getPublishDate();
									$expirationdate = $image->getExpireDate();
									?>
									<script type="text/javascript">
										// <!-- <![CDATA[
										$(function () {
											$("#publishdate-<?php echo $currentimage; ?>,#expirationdate-<?php echo $currentimage; ?>").datepicker({
												dateFormat: 'yy-mm-dd',
												showOn: 'button',
												buttonImage: '<?php echo CALENDAR; ?>',
												buttonText: '<?php echo gettext("calendar"); ?>',
												buttonImageOnly: true
											});
											$('#publishdate-<?php echo $currentimage; ?>').change(function () {
												var today = new Date();
												var pub = $('#publishdate-<?php echo $currentimage; ?>').datepicker('getDate');
												if (pub.getTime() > today.getTime()) {
													$("Visible-<?php echo $currentimage; ?>").prop('checked', false);
													$('#publishdate-<?php echo $currentimage; ?>').css('color', 'blue');
												} else {
													$("Visible-<?php echo $currentimage; ?>").prop('checked', true);
													$('#publishdate-<?php echo $currentimage; ?>').css('color', 'black');
												}
											});
											$('#expirationdate-<?php echo $currentimage; ?>').change(function () {
												var today = new Date();
												var expiry = $('#expirationdate-<?php echo $currentimage; ?>').datepicker('getDate');
												if (expiry.getTime() > today.getTime()) {
													$(".expire<-<?php echo $currentimage; ?>").html('');
												} else {
													$(".expire-<?php echo $currentimage; ?>").html('<br /><?php echo addslashes(gettext('Expired!')); ?>');
												}
											});
										});
										// ]]> -->
									</script>
									<br class="clearall" />
									<p>
										<label for="publishdate-<?php echo $currentimage; ?>"><?php echo gettext('Publish date'); ?> <small>(YYYY-MM-DD)</small></label>
										<br /><input value="<?php echo $publishdate; ?>" type="text" size="20" maxlength="30" name="publishdate-<?php echo $currentimage; ?>" id="publishdate-<?php echo $currentimage; ?>" <?php
										if ($publishdate > date('Y-m-d H:i:s'))
											echo 'style="color:blue"';
										?> />
									</p>
									<p>
										<label for="expirationdate-<?php echo $currentimage; ?>"><?php echo gettext('Expiration date'); ?> <small>(YYYY-MM-DD)</small></label>
										<br /><input value="<?php echo $expirationdate; ?>" type="text" size="20" maxlength="30" name="expirationdate-<?php echo $currentimage; ?>" id="expirationdate-<?php echo $currentimage; ?>" />
										<strong class="expire-<?php echo $currentimage; ?>" style="color:red">
											<?php
											if (!empty($expirationdate) && ($expirationdate <= date('Y-m-d H:i:s'))) {
												echo '<br />' . gettext('Expired!');
											}
											?>
										</strong>
									</p>
									<?php
									if ($image->getlastchangeuser()) {
										?>
										<p>
											<?php printLastChange($image); ?>
										</p>
										<?php
									}
									?>
									<p>
										<?php printChangeOwner($image, UPLOAD_RIGHTS | ALBUM_RIGHTS, gettext("Owner"), $currentimage); ?>
									</p>
									<?php
									if (extensionEnabled('comment_form')) {
										?>
										<p class="checkbox">
											<label class="checkboxlabel">
												<input type="checkbox" id="allowcomments-<?php echo $currentimage; ?>" name="<?php echo $currentimage; ?>-allowcomments" value="1" <?php
												if ($image->getCommentsAllowed()) {
													echo ' checked = "checked"';
												}
												?> />
															 <?php echo gettext("Comments on"); ?>
											</label>
										</p>
										<?php
									}
									if (extensionEnabled('hitcounter')) {
										$hc = $image->get('hitcounter');
										if (empty($hc)) {
											$hc = '0';
										}
										?>
										<p class="checkbox">
											<label class="checkboxlabel">
												<input type="checkbox" name="reset_hitcounter<?php echo $currentimage; ?>"<?php if (!$hc) echo ' disabled="disabled"'; ?> />
												<?php echo sprintf(ngettext("Reset hitcounter (%u hit)", "Reset hitcounter (%u hits)", $hc), $hc); ?>
											</label>
										</p>
										<?php
									}
									if (extensionEnabled('rating')) {
										$tv = $image->get('total_value');
										$tc = $image->get('total_votes');

										if ($tc > 0) {
											$hc = $tv / $tc;
											?>
											<p class="checkbox">
												<label class="checkboxlabel">
													<input type="checkbox" id="reset_rating-<?php echo $currentimage; ?>" name="<?php echo $currentimage; ?>-reset_rating" value="1" />
													<?php printf(ngettext('Reset rating (%u star)', 'Reset rating (%u stars)', $hc), $hc); ?>
												</label>
											</p>
											<?php
										} else {
											?>
											<p class="checkbox">
												<label class="checkboxlabel">
													<input type="checkbox" id="reset_rating-<?php echo $currentimage; ?>" name="<?php echo $currentimage; ?>-reset_rating" value="1" disabled="disabled"/>
													<?php echo gettext('Reset rating (unrated)'); ?>
												</label>
											</p>
											<?php
										}
									}
									?>
									<br clear="all">
								</div>

								<h2 class="h2_bordered_edit utilities_stuff"\><?php echo gettext("Utilities"); ?></h2>
								<div class="box-edit utilities_stuff">
									<!-- Move/Copy/Rename this image -->
									<label class="checkboxlabel">
										<input type="radio" id="move-<?php echo $currentimage; ?>" name="<?php echo $currentimage; ?>-MoveCopyRename" value="move" onclick="toggleMoveCopyRename('<?php echo $currentimage; ?>', 'move');"  /> <?php echo gettext("Move"); ?>
									</label>
									<label class="checkboxlabel">
										<input type="radio" id="copy-<?php echo $currentimage; ?>" name="<?php echo $currentimage; ?>-MoveCopyRename" value="copy" onclick="toggleMoveCopyRename('<?php echo $currentimage; ?>', 'copy');"  /> <?php echo gettext("Copy"); ?>
									</label>
									<label class="checkboxlabel">
										<input type="radio" id="rename-<?php echo $currentimage; ?>" name="<?php echo $currentimage; ?>-MoveCopyRename" value="rename" onclick="toggleMoveCopyRename('<?php echo $currentimage; ?>', 'rename');"  /> <?php echo gettext("Rename File"); ?>
									</label>
									<label class="checkboxlabel">
										<input type="radio" id="Delete-<?php echo $currentimage; ?>" name="<?php echo $currentimage; ?>-MoveCopyRename" value="delete" onclick="toggleMoveCopyRename('<?php echo $currentimage; ?>', '');
															deleteConfirm('Delete-<?php echo $currentimage; ?>', '<?php echo $currentimage; ?>', '<?php echo addslashes(gettext("Are you sure you want to select this image for deletion?")); ?>')" /> <?php echo gettext("Delete image") ?>
									</label>
									<br class="clearall" />
									<div id="movecopydiv-<?php echo $currentimage; ?>" class="resetHide" style="padding-top: .5em; padding-left: .5em; padding-bottom: .5em; display: none;">
										<span class="nowrap">
											<?php echo gettext("to"); ?>:
											<select id="albumselectmenu-<?php echo $currentimage; ?>"	name="<?php echo $currentimage; ?>-albumselect" onchange="">
												<?php
												foreach ($mcr_albumlist as $fullfolder => $albumtitle) {
													$singlefolder = $fullfolder;
													$saprefix = "";
													$salevel = 0;
													$selected = "";
													if ($album->name == $fullfolder) {
														$selected = " selected=\"selected\" ";
													}
													// Get rid of the slashes in the subalbum, while also making a subalbum prefix for the menu.
													while (strstr($singlefolder, '/') !== false) {
														$singlefolder = substr(strstr($singlefolder, '/'), 1);
														$saprefix = "&nbsp; &nbsp;&nbsp;" . $saprefix;
														$salevel++;
													}
													echo '<option value="' . $fullfolder . '"' . ($salevel > 0 ? ' style="background-color: ' . $bglevels[$salevel] . ';"' : '')
													. "$selected>" . $saprefix . $singlefolder . "</option>\n";
												}
												?>
											</select>
										</span>
										<p>
											<?php npgButton('button', CROSS_MARK_RED . ' ' . gettext("Cancel"), array('buttonClick' => "toggleMoveCopyRename('" . $currentimage . "', '');")); ?>
										</p>
									</div>
									<div id="renamediv-<?php echo $currentimage; ?>" class="resetHide" style="padding-top: .5em; padding-left: .5em; display: none;">
										<span class="nowrap">
											<?php echo gettext("to"); ?>:
											<input name="<?php echo $currentimage; ?>-renameto" type="text" value="<?php echo $image->filename; ?>" />
										</span>
										<p>
											<?php npgButton('button', CROSS_MARK_RED . ' ' . gettext("Cancel"), array('buttonClick' => "toggleMoveCopyRename('" . $currentimage . "', '');")); ?>
										</p>
									</div>

									<div id="deletemsg<?php echo $currentimage; ?>" class="resetHide"	style="padding-top: .5em; padding-left: .5em; padding-bottom: .5em; color: red; display: none">
										<span class="nowrap">
											<?php echo gettext('Image will be deleted when changes are applied.'); ?>
										</span>
										<p>
											<?php npgButton('button', CROSS_MARK_RED . ' ' . gettext("Cancel"), array('buttonClick' => "toggleMoveCopyRename('" . $currentimage . "', '');")); ?>
										</p>
									</div>
									<div class="clearall" ></div>

									<?php
									if (isImagePhoto($image)) {
										?>
										<hr />
										<?php echo gettext("Rotation:"); ?>
										<br />
										<?php
										switch (substr(trim($image->get('rotation'), '!'), 0, 1)) {
											default:
											case 0:
											case 1:
											case 2:
												$rotation = 0;
												break;
											case 3:
											case 4:
												$rotation = 3;
												break;
											case 5:
											case 8:
												$rotation = 8;
												break;
											case 6:
											case 7:
												$rotation = 6;
												break;
										}
										?>
										<input type="hidden" name="<?php echo $currentimage; ?>-oldrotation" value="<?php echo $rotation; ?>" />
										<label class="checkboxlabel">
											<input type="radio" id="rotation_none-<?php echo $currentimage; ?>"	name="<?php echo $currentimage; ?>-rotation" value="0" <?php
											checked(0, $rotation);
											echo $disablerotate
											?> />
														 <?php echo gettext('none'); ?>
										</label>
										<label class="checkboxlabel">
											<input type="radio" id="rotation_90-<?php echo $currentimage; ?>"	name="<?php echo $currentimage; ?>-rotation" value="6" <?php
											checked(6, $rotation);
											echo $disablerotate
											?> />
														 <?php echo gettext('90 degrees'); ?>
										</label>
										<label class="checkboxlabel">
											<input type="radio" id="rotation_180-<?php echo $currentimage; ?>"	name="<?php echo $currentimage; ?>-rotation" value="3" <?php
											checked(3, $rotation);
											echo $disablerotate
											?> />
														 <?php echo gettext('180 degrees'); ?>
										</label>
										<label class="checkboxlabel">
											<input type="radio" id="rotation_270-<?php echo $currentimage; ?>"	name="<?php echo $currentimage; ?>-rotation" value="8" <?php
											checked(8, $rotation);
											echo $disablerotate
											?> />
														 <?php echo gettext('270 degrees'); ?>
										</label>
										<?php
									}
									?>
									<br class="clearall" />
									<hr />
									<?php
									npgButton('button', CIRCLED_BLUE_STAR . ' ' . gettext("Refresh Metadata"), array('buttonLink' => getAdminLink('admin-tabs/edit.php') . '?action=refresh&amp;album=' . pathurlencode($album->name) . '&amp;image=' . urlencode($image->filename) . '&amp;subpage=' . $pagenum . $singleimagelink . '&amp;tagsort=' . html_encode($tagsort) . '&amp;XSRFToken=' . getXSRFToken('imagemetadata'), 'buttonClass' => 'fixedwidth'));
									if (isImagePhoto($image) || !is_null($image->objectsThumb)) {
										npgButton('button', SHAPE_HANDLES . ' ' . gettext("Crop thumbnail"), array('buttonLink' => getAdminLink('admin-tabs/thumbcrop.php') . '?a=' . pathurlencode($album->name) . '&amp;i=' . urlencode($image->filename) . '&amp;subpage=' . $pagenum . $singleimagelink . '&amp;tagsort=' . html_encode($tagsort), 'buttonClass' => 'fixedwidth'));
									}
									echo npgFilters::apply('edit_image_utilities', '<!--image-->', $image, $currentimage, $pagenum, $tagsort, $singleimage); //pass space as HTML because there is already a button shown for cropimage
									?>
									<span class="clearall" ></span>
								</div>
							</div>
						</div>
						<br class="clearall" />
						<?php
						$currentimage++;
					}
				}
				?>
				<p>
					<?php
					backButton(array('buttonLink' => $backbutton));
					applyButton();
					resetButton();
					viewButton(array('buttonLink' => $album->getLink()));
					?>
				</p>
				<?php
				printImagePagination($album, $image, $singleimage, $allimagecount, $totalimages, $pagenum, $totalpages, $filter);
				?>
				<br class="clearall" />
			</div>
			<input type="hidden" name="checkForPostTruncation" value="1" />
		</form>
		<?php
	}
	?>
</div><!-- images -->