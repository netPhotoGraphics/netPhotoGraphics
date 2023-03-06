<?php

/*
 * the "guts" of saving images
 */

if (isset($_POST['checkForPostTruncation'])) {
	$filter = sanitize($_REQUEST['filter']);
	$returntab = '&tagsort=' . $tagsort . '&tab=imageinfo&filter=' . $filter;
	if (isset($_POST['singleimage'])) {
		$single = sanitize($_POST['singleimage']);
	}

	$notify = $changed = FALSE;
	for ($i = 0; $i <= $_POST['totalimages']; $i++) {
		if (isset($_POST["$i-filename"])) {
			$filename = sanitize($_POST["$i-filename"]);
			$image = newImage($album, $filename, true);
			if ($image->exists) { // The file might no longer exist
				if (isset($_POST[$i . '-MoveCopyRename'])) {
					$movecopyrename_action = sanitize($_POST[$i . '-MoveCopyRename'], 3);
				} else {
					$movecopyrename_action = '';
				}
				if ($movecopyrename_action == 'delete') {
					unset($single);
					$image->remove();
				} else {
					if (isset($_POST[$i . '-reset_rating'])) {
						$image->set('total_value', 0);
						$image->set('total_votes', 0);
						$image->set('used_ips', NULL);
					}
					if (isset($_POST['publishdate-' . $i])) {
						$image->setPublishDate(sanitize($_POST['publishdate-' . $i]));
					}
					if (isset($_POST['expirationdate-' . $i])) {
						$image->setExpireDate(sanitize($_POST['expirationdate-' . $i]));
					}
					$image->setTitle(process_language_string_save("$i-title", 2));

					if (!$i || editSelectorEnabled('images_edit_description')) {
						/* single image or the General box is enabled
						 * needed to be sure we don't reset these values because the input was disabled
						 */
						$image->setDesc(process_language_string_save("$i-desc", EDITOR_SANITIZE_LEVEL));
					}

					if (isset($_POST[$i . '-owner'])) {
						$image->setOwner(sanitize($_POST[$i . '-owner']));
					}
					foreach (array('GPSLatitude', 'GPSLongitude') as $geo) {
						if (isset($_POST["$i-$geo"])) {
							$dms = parseDMS($_POST["$i-$geo"]);
							if (is_null($dms) && !empty($_POST["$i-$geo"])) {
								$notify .= '&dms=' . $geo;
							} else {
								$image->set($geo, $dms);
							}
						}
					}
					if (isset($_POST[$i . '-oldrotation']) && isset($_POST[$i . '-rotation'])) {
						$oldrotation = (int) $_POST[$i . '-oldrotation'];
						$r = $rotation = (int) $_POST[$i . '-rotation'];
						$mirror = isset($_POST[$i . '-mirror']) && $_POST[$i . '-mirror'];
						/*
						 * 	none					1 = Horizontal (normal)
						 * 	none&mirror		2 = Mirror horizontal
						 * 	flip					3 = Rotate 180
						 * 	flip&mirror		4 = Mirror vertical
						 * 	left&mirror		5 = Mirror horizontal and rotate 270 CW
						 * 	right					6 = Rotate 90 CW
						 * 	right&mirror	7 = Mirror horizontal and rotate 90 CW
						 * 	left					8 = Rotate 270 CW
						 */
						if ($mirror) {
							switch ($rotation) {
								default:
								case 1:
									$rotation = 2;
									break;
								case 3:
									$rotation = 4;
									break;
								case 6:
									$rotation = 7;
									break;
								case 8:
									$rotation = 5;
									break;
							}
						}

						if ($rotation != $oldrotation) {
							$image->set('rotation', $rotation);
							$image->updateDimensions();
							$album = $image->getAlbum();
							Gallery::clearCache($album->name);
						}
					}

					if (isset($_POST["reset_hitcounter$i"])) {
						$image->set('hitcounter', 0);
					}

					$image->set('filesize', filesize($image->localpath));

					if (!$i || editSelectorEnabled('images_edit_general')) {
						/* single image or the General box is enabled
						 * needed to be sure we don't reset these values because the input was disabled
						 */
						$image->setShow(isset($_POST["$i-Visible"]));
						$image->setCommentsAllowed(isset($_POST["$i-allowcomments"]));
					}

					npgFilters::apply('save_image_data', $image, $i);
					if ($image->save() == 1) {
						$changed = true;
					}

					// Process move/copy/rename
					if ($movecopyrename_action == 'move') {
						unset($single);
						$dest = sanitize_path($_POST[$i . '-albumselect']);
						if ($dest && $dest != $folder) {
							if ($e = $image->move($dest)) {
								$notify .= "&mcrerr=" . $e;
							}
						} else {
							// Cannot move image to same album.
							$notify .= "&mcrerr=2";
						}
					} else if ($movecopyrename_action == 'copy') {
						$dest = sanitize_path($_POST[$i . '-albumselect']);
						if ($dest && $dest != $folder) {
							if ($e = $image->copy($dest)) {
								$notify .= "&mcrerr=" . $e;
							}
						} else {
							// Cannot copy image to existing album.
							// Or, copy with rename?
							$notify .= "&mcrerr=2";
						}
					} else if ($movecopyrename_action == 'rename') {
						$renameto = sanitize_path($_POST[$i . '-renameto']);
						if ($e = $image->rename($renameto)) {
							$notify .= "&mcrerr=" . $e;
						} else {
							$single = $renameto;
						}
					}
				}
			}
		}
	}
	if (isset($_POST['ids'])) { //	process bulk actions
		$action = processImageBulkActions($album);
		if (!empty($action)) {
			$bulknotify = '&bulkmessage = ' . $action;
		}
	}
	if (empty($notify) && !$changed) {
		$notify = '&noaction';
	}
} else {
	$notify = '&post_error';
}