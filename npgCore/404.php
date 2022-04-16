<?php
// force UTF-8  Ø
clearNPGCookie('index_page_paged');
list($album, $image) = rewrite_get_album_image('album', 'image');
if ($image) { //	maybe we can find it
	$folders = explode('/', $album);
	if (!empty($folders)) {
		switch ($folders[0]) {
			case CACHEFOLDER:
				// a failed reference to a cached image?
				require_once(CORE_SERVERPATH . 'admin-functions.php');
				require_once(PLUGIN_SERVERPATH . 'cacheManager/functions.php');
				unset($folders[0]);
				$folders[] = $image;
				list($i, $args) = getImageProcessorURIFromCacheName(implode('/', $folders), getWatermarks());
				$split = explode('?', $i);
				$i = reset($split);

				$base = stripSuffix($i);
				foreach ($_supported_images as $suffix) {
					if (file_exists(getAlbumFolder() . $base . '.' . $suffix)) {
						$uri = getImageURI($args, dirname($i), basename($base) . '.' . $suffix, NULL, $suffix);
						break;
					}
				}
				if (!isset($uri)) { //	Might be a special image
					$uri = getSpecialImageImageProcessorURI($i, getImageURI($args, dirname($i), basename($i), NULL));
				}
				if ($uri) {
					header("HTTP/1.0 302 Found");
					header("Status: 302 Found");
					header('Location: ' . $uri);
					exit();
				}
		}
	}
}
if (isset($_GET['logout']) && $_GET['logout'] < 0) {
	header("HTTP/1.0 302 Found");
	header("Status: 302 Found");
	header('Location: ' . WEBPATH . '/index.php');
	exit();
}
if (empty($image) && Gallery::imageObjectClass($album)) {
	$image = basename($album);
	$album = dirname($album);
}
if (isset($_gallery_page)) {
	$obj = $_gallery_page;
} else {
	$obj = NULL;
}
$_gallery_page = '404.php';
if (!isset($_index_theme)) {
	$_index_theme = NULL;
}
if (!isset($_current_page)) {
	$_current_page = NULL;
}
$_404_data = array($album, $image, $obj, $_index_theme, $_current_page);

if (isset($_index_theme)) {
	$_themeScript = SERVERPATH . "/" . THEMEFOLDER . '/' . internalToFilesystem($_index_theme) . '/404.php';
} else {
	$_themeScript = NULL;
}

header('Content-Type: text/html; charset=' . LOCAL_CHARSET);
header("HTTP/1.0 404 Not Found");
header("Status: 404 Not Found");
npgFilters::apply('theme_headers');
if ($_themeScript && file_exists($_themeScript)) {
	$custom = SERVERPATH . '/' . THEMEFOLDER . '/' . internalToFilesystem($_index_theme) . '/functions.php';
	if (file_exists($custom)) {
		require_once($custom);
	}
	include($_themeScript);
} else {
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml"<?php i18n::htmlLanguageCode(); ?>>
		<head>
		</head>
		<body>
			<?php
			npgFilters::apply('theme_body_open');
			print404status();
			?>
			<br />
			<a href="<?php echo html_encode(getGalleryIndexURL()); ?>"
				 title="<?php echo gettext('Index'); ?>"><?php echo sprintf(gettext("Return to %s"), getGalleryTitle()); ?></a>
		</body>
		<?php npgFilters::apply('theme_body_close'); ?>
	</html>
	<?php
}
?>