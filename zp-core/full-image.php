<?php

/**
 * handles the watermarking and protecting of the full image link
 *
 * @author Stephen Billard (sbillard)
 *
 * @package core
 */
// force UTF-8 Ø
if (!defined('OFFSET_PATH'))
	define('OFFSET_PATH', 1);
require_once(dirname(__FILE__) . "/functions.php");
require_once(dirname(__FILE__) . "/lib-image.php");

$disposal = getOption('protect_full_image');
if ($disposal == 'No access') { // illegal use of the script!
	imageProcessing::error('403 Forbidden', gettext("Forbidden"));
} else {
	if (isset($_GET['dsp'])) {
		$disposal = sanitize($_GET['dsp']);
	}
}
// Check for minimum parameters.
if (!isset($_GET['a']) || !isset($_GET['i'])) {
	imageProcessing::error('404 Not Found', gettext("Too few arguments! Image not found."), 'err-imagenotfound.png');
}

list($album8, $image8) = rewrite_get_album_image('a', 'i');
$album = internalToFilesystem($album8);
$image = internalToFilesystem($image8);

/* Prevent hotlinking to the full image from other domains. */
if (getOption('hotlink_protection')) {
	if (isset($_SERVER['HTTP_REFERER'])) {
		preg_match('|(.*)//([^/]*)|', $_SERVER['HTTP_REFERER'], $matches);
		$checkstring = isset($matches[2]) ? preg_replace('/^www./', '', strtolower($matches[2])) : '';
		if (strpos($checkstring, ":")) {
			$checkstring = substr($checkstring, 0, strpos($checkstring, ":"));
		}
	} else {
		$checkstring = '';
	}
	if (preg_replace('/^www./', '', strtolower($_SERVER['SERVER_NAME'])) != $checkstring) {
		/* It seems they are directly requesting the full image. */
		header('Location: ' . FULLWEBPATH . '/index.php?album=' . $album8 . '&image=' . $image8);
		exit();
	}
}

$albumobj = newAlbum($album8, true, true);
$imageobj = newImage($albumobj, $image8, true);
$args = getImageArgs($_GET);
$args[0] = 'FULL';
$adminrequest = $args[12];

if ($forbidden = getOption('image_processor_flooding_protection') && (!isset($_GET['check']) || $_GET['check'] != ipProtectTag($album, $image, $args))) {
	// maybe it was from javascript which does not know better!
	npg_session_start();
	$forbidden = !isset($_SESSION['adminRequest']) || $_SESSION['adminRequest'] != @$_COOKIE['user_auth'];
}

$args[0] = 'FULL';

$hash = getOption('protected_image_password');
if (($hash || !$albumobj->checkAccess()) && !npg_loggedin(VIEW_FULLIMAGE_RIGHTS)) {
	//	handle password form if posted
	handle_password('image_auth', getOption('protected_image_password'), getOption('protected_image_user'));
	//check for passwords
	$authType = 'image_auth';
	$hint = get_language_string(getOption('protected_image_hint'));
	$show = getOption('protected_image_user');
	if (empty($hash)) { // check for album password
		$hash = $albumobj->getPassword();
		$authType = "album_auth_" . $albumobj->getID();
		$hint = $albumobj->getPasswordHint();
		$show = $albumobj->getUser();
		if (empty($hash)) {
			$albumobj = $albumobj->getParent();
			while (!is_null($albumobj)) {
				$hash = $albumobj->getPassword();
				$authType = "album_auth_" . $albumobj->getID();
				$hint = $albumobj->getPasswordHint();
				$show = $albumobj->getUser();
				if (!empty($hash)) {
					break;
				}
				$albumobj = $albumobj->getParent();
			}
		}
	}
	if (empty($hash)) { // check for gallery password
		$hash = $_gallery->getPassword();
		$authType = 'gallery_auth';
		$hint = $_gallery->getPasswordHint();
		$show = $_gallery->getUser();
	}

	if (empty($hash) || (!empty($hash) && getNPGCookie($authType) != $hash)) {
		require_once(CORE_SERVERPATH . 'rewrite.php');
		require_once(dirname(__FILE__) . "/template-functions.php");
		require_once(CORE_SERVERPATH . 'lib-controller.php');
		Controller::load_gallery();

		foreach (getEnabledPlugins() as $extension => $plugin) {
			if ($plugin['priority'] & THEME_PLUGIN) {
				require_once($plugin['path']);
				$_loaded_plugins[$extension] = $extension;
			}
		}

		$theme = setupTheme($albumobj);
		$custom = $_themeroot . '/functions.php';
		if (file_exists($custom)) {
			require_once($custom);
		}
		$_gallery_page = 'password.php';
		$_themeScript = $_themeroot . '/password.php';
		if (!file_exists(internalToFilesystem($_themeScript))) {
			$_themeScript = CORE_SERVERPATH . 'password.php';
		}
		header('Content-Type: text/html; charset=' . LOCAL_CHARSET);
		header("HTTP/1.0 302 Found");
		header("Status: 302 Found");
		header('Last-Modified: ' . NPG_LAST_MODIFIED);
		include(internalToFilesystem($_themeScript));
		exit();
	}
}

$image_path = $imageobj->localpath;
$suffix = getSuffix($image_path);

switch ($suffix) {
	case 'wbm':
	case 'wbmp':
		$suffix = 'wbmp';
		break;
	case 'jpg':
		$suffix = 'jpeg';
		break;
	case 'png':
	case 'gif':
	case 'jpeg':
		break;
	default:
		if ($disposal == 'Download') {
			require_once(dirname(__FILE__) . '/lib-MimeTypes.php');
			$mimetype = getMimeString($suffix);
			header('Content-Disposition: attachment; filename="' . $image . '"'); // enable this to make the image a download
			$fp = fopen($image_path, 'rb');
			// send the right headers
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header("Content-Type: $mimetype");
			header("Content-Length: " . filesize($image_path));
			// dump the picture and stop the script
			fpassthru($fp);
			fclose($fp);
		} else {
			header('Location: ' . $imageobj->getFullImageURL(), true, 301);
		}
		exit();
}
if ($force_cache = getOption('cache_full_image')) {
	$cache_file = getImageCacheFilename($album, $image, $args);
	$cache_path = SERVERCACHE . $cache_file;
	mkdir_recursive(dirname($cache_path), FOLDER_MOD);
} else {
	$cache_file = $album . "/" . stripSuffix($image) . '_FULL.' . $suffix;
	$cache_path = NULL;
}

$process = $rotate = false;
if (gl_imageCanRotate()) {
	$rotate = imageProcessing::getRotation($imageobj);
	$process = $rotate;
}
$watermark_use_image = getWatermarkParam($imageobj, WATERMARK_FULL);
if ($watermark_use_image == NO_WATERMARK) {
	$watermark_use_image = '';
} else {
	$process = 2;
	$watermark_use_image = getWatermarkPath($watermark_use_image);
}

if (isset($_GET['q'])) {
	$quality = sanitize_numeric($_GET['q']);
} else {
	$quality = getOption('full_image_quality');
}

if (!($process || $force_cache)) { // no processing needed
	if (getOption('album_folder_class') != 'external' && $disposal != 'Download') { // local album system, return the image directly
		header('Content-Type: image/' . $suffix);
		if (UTF8_IMAGE_URI) {
			header("Location: " . getAlbumFolder(FULLWEBPATH) . pathurlencode($album8) . "/" . rawurlencode($image8));
		} else {
			header("Location: " . getAlbumFolder(FULLWEBPATH) . pathurlencode($album) . "/" . rawurlencode($image));
		}
		exit();
	} else { // the web server does not have access to the image, have to supply it
		$fp = fopen($image_path, 'rb');
		// send the right headers
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header("Content-Type: image/$suffix");
		if ($disposal == 'Download') {
			header('Content-Disposition: attachment; filename="' . $image . '"'); // enable this to make the image a download
		}
		header("Content-Length: " . filesize($image_path));
		// dump the picture and stop the script
		fpassthru($fp);
		fclose($fp);
		exit();
	}
}

header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header("Content-Type: image/$suffix");
if ($disposal == 'Download') {
	header('Content-Disposition: attachment; filename="' . $image . '"'); // enable this to make the image a download
}

if (is_null($cache_path) || !file_exists($cache_path)) { //process the image
	if ($forbidden) {
		imageProcessing::error('403 Forbidden', gettext("Forbidden(2)"));
	}
	if ($force_cache && !$process) {
		// we can just use the original!
		if (SYMLINK && @symlink($image_path, $cache_path)) {
			if (DEBUG_IMAGE)
				debugLog("full-image:symlink original " . basename($image));
			clearstatcache();
		} else if (@copy($image_path, $cache_path)) {
			if (DEBUG_IMAGE)
				debugLog("full-image:copy original " . basename($image));
			clearstatcache();
		}
	} else {
		//	have to create the image
		$iMutex = new npgMutex('i', getOption('imageProcessorConcurrency'));
		$iMutex->lock();
		$newim = gl_imageGet($image_path);
		if ($rotate) {
			$newim = gl_rotateImage($newim, $rotate);
		}
		if ($watermark_use_image) {
			$newim = imageProcessing::watermarkImage($newim, $watermark_use_image, $image_path);
		}

		$iMutex->unlock();
		if (!gl_imageOutputt($newim, $suffix, $cache_path, $quality) && DEBUG_IMAGE) {
			debugLog('full-image failed to create:' . $image);
		}
		if (isset($_GET['returncheckmark'])) {
			//	from the cachemanager cache image generator
			require_once(CORE_SERVERPATH . 'setup/setup-functions.php');
			sendImage(0, 'i.php');
			exit();
		}
	}
}

if (!is_null($cache_path)) {
	if ($disposal == 'Download' || !OPEN_IMAGE_CACHE) {
		require_once(dirname(__FILE__) . '/lib-MimeTypes.php');
		$mimetype = getMimeString($suffix);
		$fp = fopen($cache_path, 'rb');
		// send the right headers
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header("Content-Type: $mimetype");
		header("Content-Length: " . filesize($image_path));
		// dump the picture and stop the script
		fpassthru($fp);
		fclose($fp);
	} else {
		header('Location: ' . FULLWEBPATH . '/' . CACHEFOLDER . pathurlencode(imgSrcURI($cache_file)), true, 301);
	}
	exit();
}
?>

