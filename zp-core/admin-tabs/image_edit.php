<?php
/*
 * This is the "guts" of the edit image page
 */

require_once(CORE_SERVERPATH . 'exif/exifTranslations.php');
$singleimagelink = $singleimage = NULL;
$showfilter = true;

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
	?>
	<div style="padding-bottom:10px;">
		<?php
		echo gettext("Click on the image to change the thumbnail cropping.");
		if ($showfilter) {
			$numsteps = ceil(max($allimagecount, $imagesTab_imageCount) / ADMIN_IMAGES_STEP);
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
				<select id="filter" name="filter" onchange="launchScript('<?php echo getAdminLink('admin-tabs/edit.php'); ?>', ['page=edit', 'album=<?php echo html_encode($album->name); ?>', 'subpage=1', 'tab=imageinfo', 'filter=' + $('#filter').val()]);">
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
		<form class="dirtylistening" onReset="setClean('form_imageedit');$('.resetHide').hide();" name="albumedit2"	id="form_imageedit" action="?page=edit&amp;action=save<?php echo "&amp;album=" . pathurlencode($album->name); ?>"	method="post" autocomplete="off" >
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
				<p class="buttons">
					<?php
					if (is_numeric($pagenum)) {
						$backbutton = getAdminLink('admin-tabs/edit.php') . '?page=edit' . $parent . '&filter=' . $filter;
					} else {
						$image = newImage($album, $singleimage);
						$backbutton = $image->getLink();
					}
					?>
					<a href="<?php echo $backbutton; ?>">
						<?php echo BACK_ARROW_BLUE; ?>
						<strong><?php echo gettext("Back"); ?></strong>
					</a>
					<button type="submit">
						<?php echo CHECKMARK_GREEN; ?>
						<strong><?php echo gettext("Apply"); ?></strong>
					</button>
					<button type="reset">
						<?php echo CROSS_MARK_RED; ?>
						<strong><?php echo gettext("Reset"); ?></strong>
					</button>
					<a href="<?php echo WEBPATH . "/index.php?album=" . pathurlencode($album->getFileName()); ?>" >
						<?php echo BULLSEYE_BLUE; ?>
						<strong><?php echo gettext('View Album'); ?></strong>
					</a>
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
							<br class="clearall">
							<?php
							if ($currentimage > 0) {
								echo '<hr><br />';
							}
							?>

							<div class="floatleft leftdeatil">
								<div style="width: 135px;">
									<?php
									if ($close = (isImagePhoto($image) || !is_null($image->objectsThumb))) {
										?>
										<a href="<?php echo getAdminLink('admin-tabs/thumbcrop.php'); ?>?a=<?php echo pathurlencode($album->name); ?>&amp;i=<?php echo urlencode($image->filename); ?>&amp;subpage=<?php echo $pagenum; ?>&amp;singleimage=<?php echo urlencode($image->filename); ?>&amp;tagsort=<?php echo html_encode($tagsort); ?>" title="<?php html_encode(printf(gettext('crop %s'), $image->filename)); ?>">
											<?php
										}
										?>

										<img id="thumb_img-<?php echo $currentimage; ?>" src="<?php echo html_encode(getAdminThumb($image, 'medium')); ?>" alt="<?php echo html_encode($image->filename); ?>" />
										<?php
										if ($close) {
											?>
										</a>
										<?php
									}
									?>
								</div>
								<?php
								if (isImagePhoto($image)) {
									?>
									<p class="buttons"><a href="<?php echo html_encode($image->getFullImageURL()); ?>" class="colorbox"><img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/magnify.png" alt="" /><strong><?php echo gettext('Zoom'); ?></strong></a></p><br style="clear: both" />
									<?php
								}
								?>
								<p class="buttons">
									<a href="<?php echo $image->getLink(); ?>">
										<?php echo BULLSEYE_BLUE; ?>
										<strong><?php echo gettext('View'); ?></strong>
									</a>
								</p><br style="clear: both" />
								<p>
									<?php echo gettext('<strong>Filename:</strong>'); ?>
									<br />
									<?php
									echo truncate_string($image->filename, 25);
									?>
								</p>
								<p><?php echo gettext('<strong>Image id:</strong>'); ?> <?php echo $image->getID(); ?></p>
								<p><?php echo gettext("<strong>Dimensions:</strong>"); ?><br /><?php echo $image->getWidth(); ?> x  <?php echo $image->getHeight() . ' ' . gettext('px'); ?></p>
								<p><?php echo gettext("<strong>Size:</strong>"); ?><br /><?php echo byteConvert($image->getImageFootprint()); ?></p>
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
									<tr>
										<td class="leftcolumn"><?php echo gettext("Description"); ?></td>
										<td class="middlecolumn"><?php print_language_string_list($image->getDesc('all'), $currentimage . '-desc', true, NULL, 'texteditor', '100%'); ?></td>
									</tr>
									<?php
									if ($image->get('hasMetadata')) {
										?>
										<tr>
											<td class="leftcolumn"><?php echo gettext("Metadata"); ?></td>
											<td class="middlecolumn">
												<?php
												$data = '';
												$exif = $image->getMetaData();
												if (false !== $exif) {
													foreach ($exif as $field => $value) {
														if (!(empty($value) || $_exifvars[$field][EXIF_FIELD_TYPE] == 'time' && $value = '0000-00-00 00:00:00')) {
															$display = $_exifvars[$field][EXIF_DISPLAY];
															if ($display) {
																$label = $_exifvars[$field][EXIF_DISPLAY_TEXT];
																$data .= "<tr><td class=\"medtadata_tag " . html_encode($field) . "\">$label: </td> <td>" . html_encode(exifTranslate($value)) . "</td></tr>\n";
															}
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
									<tr>
										<td class="leftcolumn"><?php echo gettext("Geo location"); ?></td>
										<td class="middlecolumn">
											<?php
											$lat = $image->get('GPSLatitude');
											if ($lat < 0) {
												$lat = image::toDMS($lat, 'S');
											} else if ($lat == 0) {
												$lat = '';
											} else {
												$lat = image::toDMS($lat, 'N');
											}
											$long = $image->get('GPSLongitude');
											if ($long < 0) {
												$long = image::toDMS($long, 'W');
											} else if ($long == 0) {
												$long = '';
											} else {
												$long = image::toDMS($long, 'E');
											}
											?>
											<input name="<?php echo $currentimage; ?>-GPSLatitude" type="text" value="<?php echo html_encode($lat); ?>"><input name="<?php echo $currentimage; ?>-GPSLongitude" type="text" value="<?php echo html_encode($long); ?>">
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
								<h2 class="h2_bordered_edit"><?php echo gettext("General"); ?></h2>
								<div class="box-edit">
									<label class="checkboxlabel">
										<input type="checkbox" id="Visible-<?php echo $currentimage; ?>"
													 name="<?php echo $currentimage; ?>-Visible"
													 value="1" <?php if ($image->getShow()) echo ' checked = "checked"'; ?>
													 onclick="$('#publishdate-<?php echo $currentimage; ?>').val('');
																		 $('#expirationdate-<?php echo $currentimage; ?>').val('');
																		 $('#publishdate-<?php echo $currentimage; ?>').css('color', 'black ');
																		 $('.expire-<?php echo $currentimage; ?>').html('');"
													 />
													 <?php echo gettext("Published"); ?>
									</label>
									<?php
									if (extensionEnabled('comment_form')) {
										?>
										<label class="checkboxlabel">
											<input type="checkbox" id="allowcomments-<?php echo $currentimage; ?>" name="<?php echo $currentimage; ?>-allowcomments" value="1" <?php
											if ($image->getCommentsAllowed()) {
												echo ' checked = "checked"';
											}
											?> />
														 <?php echo gettext("Allow Comments"); ?>
										</label>
										<?php
									}
									if (extensionEnabled('hitcounter')) {
										$hc = $image->get('hitcounter');
										if (empty($hc)) {
											$hc = '0';
										}
										?>
										<label class="checkboxlabel">
											<input type="checkbox" name="reset_hitcounter<?php echo $currentimage; ?>"<?php if (!$hc) echo ' disabled = "disabled"'; ?> />
											<?php echo sprintf(ngettext("Reset hitcounter (%u hit)", "Reset hitcounter (%u hits)", $hc), $hc); ?>
										</label>
										<?php
									}
									if (extensionEnabled('rating')) {
										$tv = $image->get('total_value');
										$tc = $image->get('total_votes');

										if ($tc > 0) {
											$hc = $tv / $tc;
											?>
											<label class="checkboxlabel">
												<input type="checkbox" id="reset_rating-<?php echo $currentimage; ?>" name="<?php echo $currentimage; ?>-reset_rating" value="1" />
												<?php printf(ngettext('Reset rating (%u star)', 'Reset rating (%u stars)', $hc), $hc); ?>
											</label>
											<?php
										} else {
											?>
											<label class="checkboxlabel">
												<input type="checkbox" id="reset_rating-<?php echo $currentimage; ?>" name="<?php echo $currentimage; ?>-reset_rating" value="1" disabled="disabled"/>
												<?php echo gettext('Reset rating (unrated)'); ?>
											</label>
											<?php
										}
									}
									$publishdate = $image->getPublishDate();
									$expirationdate = $image->getExpireDate();
									?>
									<script type="text/javascript">
										// <!-- <![CDATA[
										$(function () {
											$("#publishdate-<?php echo $currentimage; ?>,#expirationdate-<?php echo $currentimage; ?>").datepicker({
												dateFormat: 'yy-mm-dd',
												showOn: 'button',
												buttonImage: '<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/calendar.png',
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
									<br class="clearall">
									<hr />
									<p>
										<label for="publishdate-<?php echo $currentimage; ?>"><?php echo gettext('Publish date'); ?> <small>(YYYY-MM-DD)</small></label>
										<br /><input value="<?php echo $publishdate; ?>" type="text" size="20" maxlength="30" name="publishdate-<?php echo $currentimage; ?>" id="publishdate-<?php echo $currentimage; ?>" <?php if ($publishdate > date('Y-m-d H:i:s')) echo 'style="color:blue"'; ?> />
										<br /><label for="expirationdate-<?php echo $currentimage; ?>"><?php echo gettext('Expiration date'); ?> <small>(YYYY-MM-DD)</small></label>
										<br /><input value="<?php echo $expirationdate; ?>" type="text" size="20" maxlength="30" name="expirationdate-<?php echo $currentimage; ?>" id="expirationdate-<?php echo $currentimage; ?>" />
										<strong class="expire-<?php echo $currentimage; ?>" style="color:red">
											<?php
											if (!empty($expirationdate) && ($expirationdate <= date('Y-m-d H:i:s'))) {
												echo '<br />' . gettext('Expired!');
											}
											?>
										</strong>
										<?php
										if ($image->getlastchangeuser()) {
											?>
											<br />
											<?php
											printf(gettext('Last changed %1$s by %2$s'), $image->getLastchange() . '<br />', $image->getlastchangeuser());
										}
										?>
									<hr />
									<?php
									if (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
										echo gettext("Owner");
										?>
										<select name="<?php echo $currentimage; ?>-owner" size='1'>
											<?php echo admin_owner_list($image->getOwner(), UPLOAD_RIGHTS | ALBUM_RIGHTS); ?>
										</select>
										<?php
									} else {
										printf(gettext('Owner: %1$s'), $image->getOwner());
									}
									?>
									</p>
								</div>

								<h2 class="h2_bordered_edit"><?php echo gettext("Utilities"); ?></h2>
								<div class="box-edit">
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
									<br class="clearall">
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
										<p class="buttons">
											<a onclick="toggleMoveCopyRename('<?php echo $currentimage; ?>', '');">
												<?php echo CROSS_MARK_RED; ?>
												<?php echo gettext("Cancel"); ?>
											</a>
										</p>
									</div>
									<div id="renamediv-<?php echo $currentimage; ?>" class="resetHide" style="padding-top: .5em; padding-left: .5em; display: none;">
										<span class="nowrap">
											<?php echo gettext("to"); ?>:
											<input name="<?php echo $currentimage; ?>-renameto" type="text" value="<?php echo $image->filename; ?>" />
										</span>
										<p class="buttons">
											<a	onclick="toggleMoveCopyRename('<?php echo $currentimage; ?>', '');">
												<?php echo CROSS_MARK_RED; ?>
												<?php echo gettext("Cancel"); ?>
											</a>
										</p>
									</div>

									<div id="deletemsg<?php echo $currentimage; ?>" class="resetHide"	style="padding-top: .5em; padding-left: .5em; padding-bottom: .5em; color: red; display: none">
										<span class="nowrap">
											<?php echo gettext('Image will be deleted when changes are applied.'); ?>
										</span>
										<p class="buttons">
											<a	onclick="toggleMoveCopyRename('<?php echo $currentimage; ?>', '');">
												<?php echo CROSS_MARK_RED; ?>
												<?php echo gettext("Cancel"); ?>
											</a>
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
										$unflip = array(0 => 0, 1 => 0, 2 => 0, 3 => 3, 4 => 3, 5 => 8, 6 => 6, 7 => 6, 8 => 8);
										$rotation = @$unflip[substr(trim($image->get('rotation'), '!'), 0, 1)];
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
									<br class="clearall">
									<hr />
									<div class="button buttons tooltip" title="<?php printf(gettext('Refresh %s metadata'), $image->filename); ?>">
										<a href="<?php echo getAdminLink('admin-tabs/edit.php') ?>?action=refresh&amp;album=<?php echo pathurlencode($album->name); ?>&amp;image=<?php echo urlencode($image->filename); ?>&amp;subpage=<?php echo $pagenum . $singleimagelink; ?>&amp;tagsort=<?php echo html_encode($tagsort); ?>&amp;XSRFToken=<?php echo getXSRFToken('imagemetadata'); ?>" >
											<?php echo CIRCLED_BLUE_STAR; ?>
											<?php echo gettext("Refresh Metadata"); ?>
										</a>
										<br class="clearall">
									</div>
									<?php
									if (isImagePhoto($image) || !is_null($image->objectsThumb)) {
										?>
										<div class="button buttons tooltip" title="<?php printf(gettext('crop %s'), $image->filename); ?>">
											<a href="<?php echo getAdminLink('admin-tabs/thumbcrop.php') ?>?a=<?php echo pathurlencode($album->name); ?>&amp;i=<?php echo urlencode($image->filename); ?>&amp;subpage=<?php echo $pagenum . $singleimagelink; ?>&amp;tagsort=<?php echo html_encode($tagsort); ?>" >
												<img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/shape_handles.png" alt="" /><?php echo gettext("Crop thumbnail"); ?>
											</a>
											<br class="clearall">
										</div>
										<?php
									}
									echo npgFilters::apply('edit_image_utilities', '<!--image-->', $image, $currentimage, $pagenum, $tagsort, $singleimage); //pass space as HTML because there is already a button shown for cropimage
									?>
									<span class="clearall" ></span>
								</div>
							</div>
						</div>
						<br class="clearall">
						<?php
						$currentimage++;
					}
				}
				?>
				<p class="buttons">
					<a href="<?php $backbutton; ?>">
						<?php echo BACK_ARROW_BLUE; ?>
						<strong><?php echo gettext("Back"); ?></strong>
					</a>
					<button type="submit">
						<?php echo CHECKMARK_GREEN; ?>
						<strong><?php echo gettext("Apply"); ?></strong>
					</button>
					<button type="reset">
						<?php echo CROSS_MARK_RED; ?>
						<strong><?php echo gettext("Reset"); ?></strong>
					</button>
				</p>
				<?php
				printImagePagination($album, $image, $singleimage, $allimagecount, $totalimages, $pagenum, $totalpages, $filter);
				?>
				<br class="clearall">
			</div>
			<input type="hidden" name="checkForPostTruncation" value="1" />
		</form>
		<?php
	}
	?>
</div><!-- images -->