<?php
npgFilters::register('themeSwitcher_head', 'switcher_head');
npgFilters::register('themeSwitcher_Controllink', 'switcher_controllink');

if (!OFFSET_PATH) {
	if ((getOption('use_galleriffic')) && !(($_gallery_page == 'image.php') || ($_gallery_page == 'search.php') || ($_gallery_page == 'favorites.php'))) {
		setOption('image_size', '555', false);
		setOption('image_use_side', 'longest', false);
		setOption('thumb_size', '85', false);
		setOption('thumb_crop', '1', false);
		setOption('thumb_crop_width', '85', false);
		setOption('thumb_crop_height', '85', false);
	}
	setOption('personnal_thumb_width', '267', false);
	setOption('personnal_thumb_height', '133', false);

	enableExtension('colorbox_js', 9 | THEME_PLUGIN, false); //force colorbox
	setOption('comment_form_toggle', false, false); // force this option of comment_form, to avoid JS conflits
	setOption('comment_form_pagination', false, false); // force this option of comment_form, to avoid JS conflits
	setOption('tinymce_comments', null, false); // force this option to disable tinyMCE for comment form

	$_zenpage_enabled = class_exists('CMS');
	$_current_page_check = 'my_checkPageValidity';
}
$themecolors = array('light', 'dark');
if (class_exists('themeSwitcher')) {
	$themeColor = themeSwitcher::themeSelection('themeColor', $themecolors);
}

function iconColor($icon) {
	global $themeColor;
	if (getOption('css_style') == 'dark') {
		$icon = stripSuffix($icon) . '-white.png';
	}
	return($icon);
}

function switcher_head($ignore) {
	global $personalities, $themecolors, $themeColor;
	$themeColor = getNPGCookie('themeSwitcher_themeColor');
	if (!empty($themeColor)) {
		setOption('css_style', $themeColor, false);
	}
	?>
	<script type="text/javascript">
		// <!-- <![CDATA[
		function switchColors() {
			personality = $('#themeColor').val();
			window.location = '?themeColor=' + personality;
		}
		// ]]> -->
	</script>
	<?php
	return $ignore;
}

function switcher_controllink($ignore) {
	global $themecolors;
	$color = getNPGCookie('themeSwitcher_themeColor');
	if (!$color) {
		$color = getOption('css_style');
	}
	?>
	<span title="<?php echo gettext("Default theme color scheme."); ?>">
		<?php echo gettext('Theme Color'); ?>
		<select name="themeColor" id="themeColor" onchange="switchColors();">
			<?php generateListFromArray(array($color), $themecolors, false, false); ?>
		</select>
	</span>
	<?php
	return $ignore;
}

function my_checkPageValidity($request, $gallery_page, $page) {
	if ($gallery_page == 'gallery.php') {
		$gallery_page = 'index.php';
	}
	return checkPageValidity($request, $gallery_page, $page);
}

/* zpArdoise_printRandomImages
  /*	- implements call of colorbox
 */

function zpArdoise_printRandomImages($number = 5, $class = NULL, $option = 'all', $rootAlbum = '', $width = NULL, $height = NULL, $crop = NULL, $fullimagelink = false, $a_class = NULL) {
	if (is_null($crop) && is_null($width) && is_null($height)) {
		$crop = 2;
	} else {
		if (is_null($width))
			$width = 85;
		if (is_null($height))
			$height = 85;
		if (is_null($crop)) {
			$crop = 1;
		} else {
			$crop = (int) $crop && true;
		}
	}
	if (!empty($class))
		$class = ' class="' . $class . '"';

	echo "<ul" . $class . ">";
	for ($i = 1; $i <= $number; $i++) {
		switch ($option) {
			case "all":
				$randomImage = getRandomImages();
				break;
			case "album":
				$randomImage = getRandomImagesAlbum($rootAlbum);
				break;
		}
		if (is_object($randomImage) && $randomImage->exists) {
			echo "<li>\n";
			if ($fullimagelink) {
				$aa_class = ' class="' . $a_class . '"';
				$randomImageURL = $randomImage->getFullimageURL();
			} else {
				$aa_class = NULL;
				$randomImageURL = $randomImage->getLink();
			}
			echo '<a href="' . html_encode($randomImageURL) . '"' . $aa_class . ' title="' . html_encode($randomImage->getTitle()) . '">';
			switch ($crop) {
				case 0:
					$html = "<img src=\"" . html_encode($randomImage->getCustomImage($width, NULL, NULL, NULL, NULL, NULL, NULL, TRUE)) . "\" alt=\"" . html_encode($randomImage->getTitle()) . "\" />\n";
					$webp = $randomImage->getCustomImage($width, NULL, NULL, NULL, NULL, NULL, NULL, TRUE, NULL, FALLBACK_SUFFIX);
					break;
				case 1:
					$html = "<img src=\"" . html_encode($randomImage->getCustomImage(NULL, $width, $height, $width, $height, NULL, NULL, TRUE)) . "\" alt=\"" . html_encode($randomImage->getTitle()) . "\" width=\"" . $width . "\" height=\"" . $height . "\" />\n";
					$webp = $randomImage->getCustomImage(NULL, $width, $height, $width, $height, NULL, NULL, TRUE, NULL, FALLBACK_SUFFIX);
					break;
				case 2:
					$html = "<img src=\"" . html_encode($randomImage->getThumb()) . "\" alt=\"" . html_encode($randomImage->getTitle()) . "\" />\n";
					$webp = $randomImage->getThumb(NULL, NULL, FALLBACK_SUFFIX);
					break;
			}
			$html = npgFilters::apply('custom_image_html', $html, FALSE);
			if (ENCODING_FALLBACK) {
				$html = "<picture>\n<source srcset=\"" . html_encode($webp) . "\">\n" . $html . "</picture>\n";
			}
			echo $html;
			echo "</a>";
			echo "</li>\n";
		} else {
			break;
		}
	}
	echo "</ul>";
}

/* zpArdoise_printEXIF */

function zpardoise_printEXIF() {
	$Meta_data = getImageMetaData(); // put all exif data in a array
	if (!is_null($Meta_data)) {
		$Exifs_list = '';
		if (isset($Meta_data['EXIFModel'])) {
			$Exifs_list .= html_encode($Meta_data['EXIFModel']);
		};
		if (isset($Meta_data['EXIFFocalLength'])) {
			$Exifs_list .= ' &ndash; ' . html_encode($Meta_data['EXIFFocalLength']);
		};
		if (isset($Meta_data['EXIFFNumber'])) {
			$Exifs_list .= ' &ndash; ' . html_encode($Meta_data['EXIFFNumber']);
		};
		if (isset($Meta_data['EXIFExposureTime'])) {
			$Exifs_list .= ' &ndash; ' . html_encode($Meta_data['EXIFExposureTime']);
		};
		if (isset($Meta_data['EXIFISOSpeedRatings'])) {
			$Exifs_list .= ' &ndash; ' . html_encode($Meta_data['EXIFISOSpeedRatings']) . ' ISO';
		};
		echo $Exifs_list;
	}
}
?>