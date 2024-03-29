<?php

/**
 * @package plugins/cacheManager
 */
define("CACHE_HASH_LENGTH", strlen(sha1(HASH_SEED)));

function getImageProcessorURIFromCacheName($match, $watermarks) {
	$set = array();
	$done = false;
	$params = explode('_', stripSuffix($match));
	while (!$done && count($params) > 1) {
		$check = array_pop($params);
		$c = substr($check, 0, 1);
		if ($c == 'w' || $c == 'h' || $c == 's') {
			if (is_numeric($v = substr($check, 1))) {
				$set[$c] = (int) $v;
				continue;
			}
		}
		if ($c == 'c') {
			$c = substr($check, 0, 2);
			if (is_numeric($v = substr($check, 2))) {
				$set[$c] = (int) $v;
				continue;
			}
		}
		if (!isset($set['w']) && !isset($set['h']) && !isset($set['s'])) {
			if (!isset($set['wmk']) && in_array($check, $watermarks)) {
				$set['wmk'] = $check;
			} else if ($check == 'thumb') {
				$set['t'] = true;
			} else {
				$set['effects'] = $check;
			}
		} else {
			array_push($params, $check);
			break;
		}
	}
	if (!isset($set['wmk'])) {
		$set['wmk'] = '!';
	}
	$image = preg_replace('~.*/' . CACHEFOLDER . '/~', '', implode('_', $params)) . '.' . getSuffix($match);
	//	strip out the obfustication
	$album = dirname($image);
	$image = preg_replace('~^[0-9a-f]{' . CACHE_HASH_LENGTH . '}\.~', '', basename($image));
	$image = $album . '/' . $image;
	return array($image, getImageArgs($set));
}

function getItemTitle($table, $row) {
	switch ($table) {
		case 'images':
			$album = query_single_row('SELECT `folder` FROM ' . prefix('albums') . ' WHERE `id`=' . $row['albumid']);
			$title = sprintf(gettext('%1$s: image %2$s'), $album['folder'], $row['filename']);
			break;
		case 'albums':
			$title = sprintf(gettext('album %s'), $row['folder']);
			break;
		case 'news':
		case 'pages':
			$title = sprintf(gettext('%1$s: %2$s'), $table, $row['titlelink']);
			break;
	}
	return $title;
}

function getSpecialImageImageProcessorURI($i, $uri) {
	global $_gallery;
	$folders = explode('/', $i);
	$folders = explode('_', end($folders));
	$base = array_shift($folders);
	switch ($base) {
		case USER_PLUGIN_FOLDER:
			$uri = str_replace(USER_PLUGIN_FOLDER, USER_PLUGIN_PATH, $uri);
		case USER_PLUGIN_PATH:
			$uri .= '&z=' . USER_PLUGIN_FOLDER . '/' . implode('/', $folders);
			break;
		case THEMEFOLDER:
			$theme = array_shift($folders);
			if (!is_dir(SERVERPATH . '/' . THEMEFOLDER . '/' . $theme)) {
				array_unshift($folders, $theme);
				$theme = $_gallery->getCurrentTheme();
				$uri = str_replace(THEMEFOLDER, THEMEFOLDER . '_' . $theme, $uri);
			}
			$uri .= '&z=' . THEMEFOLDER . '/' . $theme . '/' . implode('/', $folders);
			break;
		case CORE_FOLDER:
			$uri = str_replace(CORE_FOLDER, CORE_PATH, $uri);
		case CORE_PATH:
			switch ($folders[0]) {
				case PLUGIN_PATH;
					$uri = str_replace(PLUGIN_FOLDER, PLUGIN_PATH, $uri);
					$folders[0] = PLUGIN_FOLDER;
					break;
				case 'images':
					break;
				default:
					array_unshift($folders, PLUGIN_FOLDER);
					break;
			}
			$uri .= '&z=' . CORE_FOLDER . '/' . implode('/', $folders);
			break;
		default:
			$uri = NULL;
			break;
	}
	return $uri;
}

function recordMissing($table, $row, $image) {
	global $missingImages;
	$obj = getItemByID($table, $row['id']);
	if ($obj) {
		$missingImages[] = '<a href="' . $obj->getLink() . '">' . $obj->getTitle() . '</a> (' . html_encode($image) . ')<br />';
	}
}

/**
 * Updates the path to the cache folder
 * @param mixed $text
 * @param string $target
 * @param string $update
 * @return mixed
 */
function updateCacheName($text, $target, $update) {
	if (is_string($text) && preg_match('/^a:[0-9]+:{/', $text)) { //	serialized array
		$text = getSerializedArray($text);
		$serial = true;
	} else {
		$serial = false;
	}
	if (is_array($text)) {
		foreach ($text as $key => $textelement) {
			$text[$key] = updateCacheName($textelement, $target, $update);
		}
		if ($serial) {
			$text = serialize($text);
		}
	} else {
		$text = str_replace($target, $update, $text);
	}
	return $text;
}

?>