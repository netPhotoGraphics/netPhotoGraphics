<?php
/**
 * Provides extensions to the image utilities to crop images.
 *
 * Places an image crop button in the image utilities box of the images tab.
 *
 * You can either apply the crop to the original image or you can copy a link to the cropped
 * image and paste it elsewhere in your theme (or other web pages.)
 * <b>Note:</b> There is no <i>undo</i> once a crop is applied to the original image.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/crop_image
 * @pluginCategory media
 */
if (isset($_REQUEST['performcrop'])) {
	if (!defined('OFFSET_PATH'))
		define('OFFSET_PATH', 3);
	require_once(dirname(__DIR__) . '/admin-globals.php');
	require_once(dirname(__DIR__) . '/lib-image.php');
	admin_securityChecks(ALBUM_RIGHTS, $return = currentRelativeURL());
} else {
	npgFilters::register('admin_toolbox_image', 'crop_image::toolbox');
	npgFilters::register('edit_image_utilities', 'crop_image::edit', 99999); // we want this one to come right after the crop thumbnail button
	$plugin_is_filter = defaultExtension(5 | ADMIN_PLUGIN);
	$plugin_description = gettext("An image cropping tool.");

	return;
}

class crop_image {

	static function toolbox($albumname, $imagename) {
		$album = newAlbum($albumname);
		if ($album->isMyItem(ALBUM_RIGHTS)) {
			$image = newimage($album, $imagename);
			if ($image->isPhoto()) {
				?>
				<li>
					<a href="<?php echo getAdminLink(PLUGIN_FOLDER . '/crop_image.php'); ?>?a=<?php echo pathurlencode($albumname); ?>						 &amp;i=<?php echo urlencode($imagename); ?>&amp;performcrop=frontend "><?php echo gettext("Crop image"); ?></a>
				</li>
				<?php
			}
		}
	}

	static function edit($output, $image, $prefix, $subpage, $tagsort, $singleimage) {
		if ($image->isPhoto()) {
			if (is_array($image->filename)) {
				$albumname = dirname($image->filename['source']);
				$imagename = basename($image->filename['source']);
			} else {
				$albumname = $image->albumlink;
				$imagename = $image->filename;
			}
			if ($singleimage)
				$singleimage = '&amp;singleimage=' . $singleimage;

			$output .= get_npgButton('button', SHAPE_HANDLES . ' ' . gettext("Crop image"), array('buttonLink' => getAdminLink(PLUGIN_FOLDER . '/crop_image.php') . '?a=' . pathurlencode($albumname) . '&amp;i=' . urlencode($imagename) . '&amp;performcrop=backend&amp;subpage=' . $subpage . $singleimage . '&amp;tagsort=' . html_encode($tagsort), 'buttonTitle' => gettext('Permanently crop the actual image.'), 'buttonClass' => 'fixedwidth')) . '<br clear="all">';
		}
		return $output;
	}

}

$albumname = isset($_REQUEST['a']) ? sanitize_path($_REQUEST['a']) : NULL;
$imagename = isset($_REQUEST['a']) ? sanitize($_REQUEST['i']) : NULL;
$album = newAlbum($albumname, true, true);
if (!$album->exists || !$album->isMyItem(ALBUM_RIGHTS)) { // prevent nefarious access to this page.
	if (!npgFilters::apply('admin_managed_albums_access', false, $return)) {
		header('Location: ' . getAdminLink('admin.php') . '?from=' . $return);
		exit();
	}
}
if (isset($_REQUEST['singleimage'])) {
	$singleimage = sanitize($_REQUEST['singleimage']);
} else {
	$singleimage = '';
}

// get what image side is being used for resizing
$use_side = getOption('image_use_side');
// get full width and height
$albumobj = newAlbum($albumname);
$imageobj = newImage($albumobj, $imagename, true);

if ($imageobj->isPhoto()) {
	$imgpath = $imageobj->localpath;
	$imagepart = basename($imgpath);
	$timg = gl_imageGet($imgpath);
	$width = $imageobj->getWidth();
	$height = $imageobj->getHeight();
} else {
	die(gettext('attempt to crop an object which is not an image.'));
}

// get appropriate $sizedwidth and $sizedheight
switch ($use_side) {
	case 'longest':
		$originalSize = min($width, $height);
		$size = min(400, $width, $height);
		if ($width >= $height) {
			$sr = $size / $width;
			$sizedwidth = $size;
			$sizedheight = round($height / $width * $size);
		} else {
			$sr = $size / $height;
			$sizedwidth = round($width / $height * $size);
			$sizedheight = $size;
		}
		break;
	case 'shortest':
		$originalSize = min($width, $height);
		$size = min(400, $width, $height);
		if ($width < $height) {
			$sr = $size / $width;
			$sizedwidth = $size;
			$sizedheight = round($height / $width * $size);
		} else {
			$sr = $size / $height;
			$sizedwidth = round($width / $height * $size);
			$sizedheight = $size;
		}
		break;
	case 'width':
		$originalSize = $size = $width;
		$sr = 1;
		$sizedwidth = $size;
		$sizedheight = round($height / $width * $size);
		break;
	case 'height':
		$originalSize = $size = $height;
		$sr = 1;
		$sizedwidth = round($width / $height * $size);
		$sizedheight = $size;
		break;
}
$pasteobj = isset($_REQUEST['performcrop']) && $_REQUEST['performcrop'] == 'pasteobj';

$imageurl = getImageProcessorURI(array($size, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL), $albumname, $imagepart);
$iW = round($sizedwidth * 0.9);
$iH = round($sizedheight * 0.9);
$iX = round($sizedwidth * 0.05);
$iY = round($sizedheight * 0.05);

if (isset($_GET['action']) && $_GET['action'] == 'crop') {
	XSRFdefender('crop');
	$cw = $_REQUEST['w'];
	$ch = $_REQUEST['h'];
	$cx = $_REQUEST['x'];
	$cy = $_REQUEST['y'];

	$rw = $width / $sizedwidth;
	$rh = $height / $sizedheight;
	$cw = round($cw * $rw);
	$ch = round($ch * $rh);
	$cx = round($cx * $rw);
	$cy = round($cy * $rh);

//create a new image with the set cropping
	$quality = getOption('full_image_quality');
	$rotate = false;
	if (gl_imageCanRotate()) {
		$rotate = imageProcessing::getRotation($imageobj);
	}
	if (DEBUG_IMAGE)
		debugLog("image_crop: crop " . basename($imgpath) . ":\$cw=$cw, \$ch=$ch, \$cx=$cx, \$cy=$cy \$rotate=$rotate");

	if ($rotate) {
		$timg = imageProcessing::transform($timg, $rotate);
	}

	$newim = gl_createImage($cw, $ch);
	gl_resampleImage($newim, $timg, 0, 0, $cx, $cy, $cw, $ch, $cw, $ch, getSuffix($imagename));
	chmod($imgpath, 0777);
	unlink($imgpath);
	if (gl_imageOutput($newim, getSuffix($imgpath), $imgpath, $quality)) {
		if (DEBUG_IMAGE)
			debugLog('image_crop Finished:' . basename($imgpath));
	} else {
		if (DEBUG_IMAGE)
			debugLog('image_crop: failed to create ' . $imgpath);
	}
	chmod($imgpath, FILE_MOD);
	gl_imageKill($newim);
	gl_imageKill($timg);
	Gallery::clearCache($albumname);
// update the image data
	$imageobj->set('rotation', 0);
	$imageobj->updateDimensions();
	$imageobj->set('thumbX', NULL);
	$imageobj->set('thumbY', NULL);
	$imageobj->set('thumbW', NULL);
	$imageobj->set('thumbH', NULL);
	$imageobj->save();

	if ($_REQUEST['performcrop'] == 'backend') {
		$return = getAdminLink('admin-tabs/edit.php') . '?page=edit&album=' . pathurlencode($albumname) . '&saved&subpage=' . sanitize($_REQUEST['subpage']) . '&tagsort=' . sanitize($_REQUEST['tagsort']) . '&tab=imageinfo';
		if ($singleimage)
			$return .= '&singleimage=' . html_encode($singleimage);
	} else {
		$return = FULLWEBPATH . $imageobj->getLink();
	}

	header('Location: ' . $return);
	exit();
}
if (isset($_REQUEST['subpage'])) {
	$subpage = sanitize($_REQUEST['subpage']);
	$tagsort = sanitize($_REQUEST['tagsort']);
} else {
	$subpage = $tagsort = '';
}

printAdminHeader('edit', gettext('crop image'));
if ($pasteobj) {
	?>
	<style>
		body {
			text-align: center;
			background-color: white;
			background-image: none;
		}
		#pasteobj {
			margin: 10px;
		}
	</style>
	<?php
}
scriptLoader(CORE_SERVERPATH . 'js/Jcrop/jquery.Jcrop.css');
scriptLoader(PLUGIN_SERVERPATH . 'crop_image/crop_image.css');
scriptLoader(CORE_SERVERPATH . 'js/Jcrop/jquery.Jcrop.js');
scriptLoader(CORE_SERVERPATH . 'js/htmlencoder.js');
?>
<script type="text/javascript" >
	//<!-- <![CDATA[
	var jcrop_api;
	var sizedWidth = <?php echo $sizedwidth ?>;
	var sizedHeight = <?php echo $sizedheight ?>;
	var oldSize = <?php echo $size; ?>;
	jQuery(window).on("load", function () {
		initJcrop();
		function initJcrop() {
			jcrop_api = jQuery.Jcrop('#cropbox');

			jcrop_api.setOptions({
				onchange: showCoords,
				onSelect: showCoords,
				bgOpacity: .4,
				bgColor: 'black'
			});
			jcrop_api.setOptions({aspectRatio: 0});
			resetBoundingBox();
		}
<?php
if ($pasteobj && isset($_REQUEST['size'])) {
	?>
			jQuery('#new_size').val(<?php echo (int) sanitize_numeric($_REQUEST['size']); ?>);
			sizeChange();
	<?php
}
?>
		jQuery('#aspect-ratio-width').keyup(aspectChange);
		jQuery('#aspect-ratio-height').keyup(aspectChange);
		jQuery('#new_size').keyup(sizeChange);
		$('#crop').removeClass('dirty');
	});

	function sizeChange() {
		var size = jQuery('#new_size').val();
		if (size > 0) {
			r = oldSize / size;
			sizedWidth = Math.round(sizedWidth * r);
			sizedHeight = Math.round(sizedHeight * r);
			oldSize = size;
			showCoords(jcrop_api.tellSelect());
		}
	}

	function watermarkChange() {
		showCoords(jcrop_api.tellSelect());
	}

	function resetButton() {
		jcrop_api.setOptions({aspectRatio: 0});
		$('#aspect-ratio-width').val('');
		$('#aspect-ratio-height').val('');
		sizedWidth = <?php echo $sizedwidth ?>;
		sizedHeight = <?php echo $sizedheight ?>;
		jQuery('#new_size').val(<?php echo $originalSize; ?>);
		resetBoundingBox();
		showCoords(jcrop_api.tellSelect());
		$('#crop').removeClass('dirty');
	}

	function aspectChange() {
		var aspectWidth = jQuery('#aspect-ratio-width').val();
		var aspectHeight = jQuery('#aspect-ratio-height').val();
		if (!aspectWidth)
			aspectWidth = aspectHeight;
		if (!aspectHeight)
			aspectHeight = aspectWidth;
		if (aspectHeight) {
			jcrop_api.setOptions({aspectRatio: aspectWidth / aspectHeight});
		} else {
			jcrop_api.setOptions({aspectRatio: 0});
		}
		showCoords(jcrop_api.tellSelect());
	}

	function swapAspect() {
		var aspectHeight = $('#aspect-ratio-width').val();
		var aspectWidth = $('#aspect-ratio-height').val();
		$('#aspect-ratio-width').val(aspectWidth);
		$('#aspect-ratio-height').val(aspectHeight);
		jcrop_api.setOptions({aspectRatio: aspectWidth / aspectHeight});
		showCoords(jcrop_api.tellSelect());
	}

	// Our simple event handler, called from onchange and onSelect
	// event handlers, as per the Jcrop invocation above
	function showCoords(c) {

		var new_width = Math.round(c.w * (<?php echo $width ?> / sizedWidth));
		var new_height = Math.round(c.h * (<?php echo $height ?> / sizedHeight));

		jQuery('#x').val(c.x);
		jQuery('#y').val(c.y);
		jQuery('#x2').val(c.x2);
		jQuery('#y2').val(c.y2);
		jQuery('#w').val(c.w);
		jQuery('#h').val(c.h);
		jQuery('#new-width').text(new_width);
		jQuery('#new-height').text(new_height);

		cw = Math.round(c.w * <?php echo $width / $sizedwidth; ?>);
		ch = Math.round(c.h * <?php echo $width / $sizedwidth; ?>);
		cx = Math.round(c.x * <?php echo $width / $sizedwidth; ?>);
		cy = Math.round(c.y * <?php echo $height / $sizedheight; ?>);
		wmk = jQuery('#watermark').val();
		if (wmk == '') {
			wmk = '!';
		}

		uri = '<?php echo WEBPATH . '/' . CORE_FOLDER . '/i.php?a=' . pathurlencode($albumname) . '&i=' . urlencode($imagename); ?>' + '&w=' + new_width + '&h=' + new_height + '&cw=' + cw + '&ch=' + ch + '&cx=' + cx + '&cy=' + cy + '&wmk=' + wmk;
		jQuery('#imageURI').val(uri);
		jQuery('#imageURI').attr('size', uri.length + 10);
		$('#crop').addClass('dirty');
	}

	function resetBoundingBox() {
		jcrop_api.setSelect([<?php echo $iX; ?>, <?php echo $iY; ?>, <?php echo $iX + $iW; ?>, <?php echo $iY + $iH; ?>]);
	}

	function checkCoords() {
		return confirm('<?php echo gettext('Are you sure you want to permanently alter this image?'); ?>');
	}

	
</script>
</head>
<body>
	<?php
	if ($pasteobj) {
		?>

		<div id="pasteobj">
			<?php
		} else {
			printLogoAndLinks();
			?>

			<div id="main">
				<?php printTabs(); ?>
				<div id="content">
					<?php npgFilters::apply('admin_note', 'crop_image', ''); ?>
					<h1><?php echo gettext("Image cropping") . ": <em>" . $albumobj->name . " (" . $albumobj->getTitle() . ") /" . $imageobj->filename . " (" . $imageobj->getTitle() . ")</em>"; ?></h1>
					<div id="notice_div">
						<p><?php echo gettext('You can crop your image by dragging the crop handles on the image'); ?></p>
						<p id="notice" class="notebox" style="width:<?php echo $sizedwidth; ?>px" ><?php echo gettext('<strong>Note:</strong> If you save these changes they are permanent!'); ?></p>
					</div>
					<?php
				}
				?>
				<div class="tabbox">

					<div style="text-align:left; float: left;">
						<!-- This is the form that our event handler fills -->
						<form class="dirtylistening" onReset="setClean('crop');"  name="crop" id="crop" method="post" action="?action=crop" onsubmit="return checkCoords();">
							<?php XSRFToken('crop'); ?>

							<div style="width: <?php echo $sizedwidth; ?>px; height: <?php echo $sizedheight; ?>px; margin-bottom: 10px; border: 4px solid gray;">
								<!-- This is the image we're attaching Jcrop to -->
								<img src="<?php echo html_encode($imageurl); ?>" id="cropbox" />
								<span class="floatright">
									<?php echo sprintf(gettext('(<span id="new-width">%1$u</span> x <span id="new-height">%2$u</span> pixels)'), round($iW * ($width / $sizedwidth)), round($iH * ($height / $sizedheight)));
									?>
								</span>
							</div>
							<span class="clearall" ></span>
							<p>
								<?php echo gettext('size'); ?>
								<input type = "text" name = "new_size" id = "new_size" size = "5" value = "<?php echo $originalSize; ?>" />
							</p>
							<p>
								<?php
								printf(gettext('crop width:%1$s %2$s crop height:%3$s'), '<input type="text" id="aspect-ratio-width" name="aspect-ratio-width" value="" size="5" />', '&nbsp;<span id="aspect" ><a id="swap_button" onclick="swapAspect();" title="' . gettext('swap width and height fields') . '" > ' . SWAP_ICON . ' </a></span>&nbsp;', '<input type="text" id="aspect-ratio-height" name="aspect-ratio-height" value="" size="5" />');
								?>
							</p>
							<?php
							if ($singleimage) {
								?>
								<input type="hidden" name="singleimage" value="<?php echo html_encode($singleimage); ?>" />
								<?php
							}
							?>
							<input type="hidden" size="4" id="x" name="x" value="<?php echo $iX ?>" />
							<input type="hidden" size="4" id="y" name="y" value="<?php echo $iY ?>" />
							<input type="hidden" size="4" id="x2" name="x2" value="<?php echo $iX + $iW ?>" />
							<input type="hidden" size="4" id="y2" name="y2" value="<?php echo $iY + $iH ?>" />
							<input type="hidden" size="4" id="w" name="w" value="<?php echo $iW ?>" />
							<input type="hidden" size="4" id="h" name="h" value="<?php echo $iH ?>"  />
							<input type="hidden" id="a" name="a" value="<?php echo html_encode($albumname); ?>" />
							<input type="hidden" id="i" name="i" value="<?php echo html_encode($imagename); ?>" />
							<input type="hidden" id="tagsort" name="tagsort" value="<?php echo html_encode($tagsort); ?>" />
							<input type="hidden" id="subpage" name="subpage" value="<?php echo html_encode($subpage); ?>" />
							<input type="hidden" id="crop" name="crop" value="crop" />
							<input type="hidden" id="performcrop" name="performcrop" value="<?php echo html_encode(sanitize($_REQUEST['performcrop'])); ?>" />
							<p>
								<?php
								echo gettext('Link image watermark');
								$watermarks = getWatermarks();
								$current = IMAGE_WATERMARK;
								?>
								<select id="watermark" name="watermark" onchange="watermarkChange();">
									<option value="" <?php if (empty($current)) echo ' selected="selected"' ?> style="background-color:LightGray"><?php echo gettext('none'); ?></option>
									<?php
									generateListFromArray(array($current), $watermarks, false, false);
									?>
								</select>
							</p>
							<p>
								<?php
								if (!$pasteobj) {
									echo linkPickerIcon($imageobj, 'imageURI', "+'&pick[picture]=' + $('#imageURI').val().replaceAll('&', ':')");
								}
								echo linkPickerItem($imageobj, 'imageURI');
								?>
							</p>
							<p>
								<?php
								if ($_REQUEST['performcrop'] == 'backend') {
									$backlink = getAdminLink('admin-tabs/edit.php') . '?page=edit&album=' . pathurlencode($albumname) . '&subpage=' . $subpage . '&tagsort=' . html_encode($tagsort) . '&tab=imageinfo';
									if (($singleimage)) {
										$backlink .= '&singleimage=' . html_encode($singleimage);
									}
									backbutton(array('buttonLink' => $backlink));
								} else if ($pasteobj) {
									ob_start();
									linkPickerPick($imageobj, 'imageURI', "+'&pick[picture]=' + $('#imageURI').val().replaceAll('&', ':')");
									$click = ob_get_clean() . "\n\tsetClean('crop');\n\twindow.history.back();";
									npgButton('button', BACK_ARROW_BLUE . ' ' . gettext("Done"), array('buttonClick' => $click));
								} else {
									backButton(array('buttonLink' => '../../index.php?album=' . pathurlencode($albumname) . '&image=' . urlencode($imagename)));
								}
								if (!$pasteobj) {
									applyButton();
								}
								resetButton();
								?>
							</p>
						</form>
					</div>

					<br class="clearall" />
				</div><!-- block -->
			</div><!-- content -->
			<?php
			if (!$pasteobj) {
				?>
				<?php
				printAdminFooter();
				?>
				<?php
			}
			?>
		</div><!-- main -->
</body>

</html>
