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

function loadAlbum($album) {
	global $_current_album, $_current_image, $_gallery, $custom, $enabled, $fullImage;
	$subalbums = $album->getAlbums();
	sort($subalbums);
	$started = false;
	$tcount = $count = 0;
	foreach ($subalbums as $folder) {
		$subalbum = newAlbum($folder);
		if (!$subalbum->isDynamic()) {
			$tcount = $tcount + loadAlbum($subalbum);
		}
	}
	$theme = $_gallery->getCurrentTheme();
	$id = 0;
	$parent = $album->getUrAlbum();
	$albumtheme = $parent->getAlbumTheme();
	if (!empty($albumtheme)) {
		$theme = $albumtheme;
		$id = $parent->getID();
	}
	loadLocalOptions($id, $theme);
	$_current_album = $album;
	if ($album->getNumImages() > 0) {
		$needsCaching = array();

		echo "<br />" . $album->name . ' ';
		while (next_image(true)) {
			if ($_current_image->isPhoto()) {

				if ($fullImage) {
					$uri = getFullImageURL(NULL, 'Protected');
					if (strpos($uri, 'full-image.php?') !== false) {
						$needsCaching[] = array(
								'uri' => preg_replace('~/i\.(.*)\?~', '/i.php?', $uri),
								'cache' => ltrim(getImageCacheFilename($_current_image->album->name, $_current_image->filename, $args), '/')
						);
					}
				}

				foreach ($custom as $key => $cacheimage) {
					if (in_array($key, $enabled)) {
						$size = isset($cacheimage['image_size']) ? $cacheimage['image_size'] : NULL;
						$width = isset($cacheimage['image_width']) ? $cacheimage['image_width'] : NULL;
						$height = isset($cacheimage['image_height']) ? $cacheimage['image_height'] : NULL;
						$thumb = isset($cacheimage['thumb']) ? $cacheimage['thumb'] : NULL;
						if ($special = ($thumb === true)) {
							list($special, $cw, $ch, $cx, $cy) = $_current_image->getThumbCropping($size, $width, $height);
						}
						if (!$special) {
							$cw = isset($cacheimage['crop_width']) ? $cacheimage['crop_width'] : NULL;
							$ch = isset($cacheimage['crop_height']) ? $cacheimage['crop_height'] : NULL;
							$cx = isset($cacheimage['crop_x']) ? $cacheimage['crop_x'] : NULL;
							$cy = isset($cacheimage['crop_y']) ? $cacheimage['crop_y'] : NULL;
						}
						$effects = isset($cacheimage['gray']) ? $cacheimage['gray'] : NULL;
						if (isset($cacheimage['wmk'])) {
							$WM = $cacheimage['wmk'];
						} else if ($cacheimage['thumb'] < 0) {
							$WM = '!';
						} else if ($thumb) {
							$WM = getWatermarkParam($_current_image, WATERMARK_THUMB);
						} else {
							$WM = getWatermarkParam($_current_image, WATERMARK_IMAGE);
						}
						if (isset($cacheimage['maxspace'])) {
							getMaxSpaceContainer($width, $height, $_current_image, $thumb);
						}
						$args = array('size' => $size, 'width' => $width, 'height' => $height, 'cw' => $cw, 'ch' => $ch, 'cx' => $cx, 'cy' => $cy, 'thumb' => $thumb, 'WM' => $WM, 'effects' => $effects);
						$args = getImageParameters($args, $album->name);
						$uri = getImageURI($args, $_current_image->album->name, $_current_image->filename, $_current_image->filemtime);
						if (strpos($uri, '/' . CORE_FOLDER . '/i.') !== false) {
							$needsCaching[] = array(
									'uri' => preg_replace('~/i\.(.*)\?~', '/i.php?', $uri),
									'cache' => ltrim(getImageCacheFilename($_current_image->album->name, $_current_image->filename, $args), '/')
							);
						}
					}
				}
			}
		}
		$count = count($needsCaching);
		if ($count) {
			echo '{ ';
			if (CURL_ENABLED) {
				$sections = array_chunk($needsCaching, min(5, THREAD_CONCURRENCY ? (THREAD_CONCURRENCY - 1) : 5), true);
				foreach ($sections as $block) {
					set_time_limit(200);
					$uriList = array();
					foreach ($block as $key => $img) {
						$uriList[$key] = FULLHOSTPATH . preg_replace('~\&cached=\d+\&~', '&', $img['uri']) . '&returncheckmark&curl=' . sha1(CORE_SERVERPATH);
					}
					$checks = new ParallelCURL($uriList);
					$rsp = $checks->getResults();
					foreach ($block as $key => $img) {
						if ($key) {
							echo ' | ';
						}
						if (isset($rsp[$key]) && is_numeric($rsp[$key])) {
							?>
							<img src = "<?php echo FULLWEBPATH . '/' . CORE_FOLDER . '/setup/icon.php?icon=' . ($rsp[$key] - 1); ?>" title="<?php echo html_encode($img['cache']); ?>" height = "16px" width = "16px">
							<?php
						} else {
							?>
							<a href="<?php echo $img['uri'] . '&amp;debug'; ?>" target="_blank" title="<?php echo html_encode($img['cache']); ?>"><?php echo CROSS_MARK_RED; ?></a>
							<?php
						}
					}
					npgFunctions::flushOutput();
				}
			} else {
				set_time_limit(200);
				foreach ($needsCaching as $key => $img) {
					if ($key) {
						echo ' | ';
					}
					?>
					<a href="<?php echo $img['uri']; ?>&amp;admin&amp;returncheckmark&amp;debug">
						<?php echo '<img src="' . $img['uri'] . '&amp;returncheckmark" title="' . html_encode($img['cache']) . '" height="16" width="16" alt="X" />' . "\n"; ?>
					</a>
					<?php
				}
			}
			echo '} ';
		}
		printf(ngettext('[%u image]', '[%u images]', $count), $count);
		echo "<br />\n";
	}
	return $count + $tcount;
}
