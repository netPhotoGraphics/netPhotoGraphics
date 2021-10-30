<?php
/**
 * This template is used to generate cache images. Running it will process the entire gallery,
 * supplying an album name (ex: loadAlbums.php?album=newalbum) will only process the album named.
 * Passing clear=on will purge the designated cache before generating cache images
 * @package plugins/cacheManager
 */
// force UTF-8 Ø
define('OFFSET_PATH', 3);
require_once("../../admin-globals.php");
require_once(CORE_SERVERPATH . 'template-functions.php');
if (CURL_ENABLED) {
	require (CORE_SERVERPATH . 'lib-CURL.php');
}

if (isset($_REQUEST['album'])) {
	$localrights = ALBUM_RIGHTS;
} else {
	$localrights = NULL;
}
admin_securityChecks($localrights, $return = currentRelativeURL());

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
								'uri' => str_replace('check=', '', $uri),
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
									'uri' => str_replace('check=', '', preg_replace('~/i\.(.*)\?~', '/i.php?', $uri)),
									'cache' => ltrim(getImageCacheFilename($_current_image->album->name, $_current_image->filename, $args), '/')
							);
						}
					}
				}
				$count = count($needsCaching);
				if ($count) {
					echo '{ ';
					if (CURL_ENABLED) {
						$sections = array_chunk($needsCaching, PROCESSING_CONCURENCY, true);
						foreach ($sections as $block) {
							set_time_limit(200);
							$uriList = array();
							foreach ($block as $key => $img) {
								$uriList[$key] = FULLHOSTPATH . preg_replace('~\&cached=\d+\&~', '&', $img['uri']) . '&returncheckmark&curl&check=';
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
			}
		}
		printf(ngettext('[%u image]', '[%u images]', $count), $count);
		echo "<br />\n";
	}
	return $count + $tcount;
}

if (isset($_GET['album'])) {
	$alb = sanitize($_GET['album']);
} else if (isset($_POST['album'])) {
	$alb = sanitize(urldecode($_POST['album']));
} else {
	$alb = '';
}
if ($alb) {
	$folder = sanitize_path($alb);
	$object = $folder;
	$tab = 'edit';
	$album = newAlbum($folder);
	if (!$album->isMyItem(ALBUM_RIGHTS)) {
		if (!npgFilters::apply('admin_managed_albums_access', false, $return)) {
			header('Location: ' . getAdminLink('admin.php'));
			exit();
		}
	}
} else {
	$object = '<em>' . gettext('Gallery') . '</em>';
}
$custom = array();

$result = query('SELECT * FROM ' . prefix('plugin_storage') . ' WHERE `type` = "cacheManager"');
while ($row = db_fetch_assoc($result)) {
	$datarow = getSerializedArray($row['data']);
	if ($datarow['theme']) {
		$custom[] = $datarow;
	}
}
$custom = sortMultiArray($custom, array('theme', 'album', 'thumb', 'image_size', 'image_width', 'image_height'), false, true, true);

if (isset($_GET['action']) && $_GET['action'] == 'select' && isset($_POST['enable'])) {
	XSRFdefender('cacheImages');
	$enabled = $_POST['enable'];
} else {
	$enabled = false;
}

printAdminHeader('admin', 'images');
echo "\n</head>";
echo "\n<body>";

printLogoAndLinks();
echo "\n" . '<div id = "main">';
printTabs();
echo "\n" . '<div id = "content">';
npgFilters::apply('admin_note', 'cache', '');
$clear = sprintf(gettext('Refresh cache for %s'), $object);
$count = 0;

if ($alb) {
	$r = '/admin-tabs/edit.php?page = edit&album=' . $alb;
	echo "\n<h1>" . $clear . "</h1>";
} else {
	$r = '/admin.php';
	echo "\n<h1>" . $clear . "</h1>";
}
?>

<script type="text/javascript">
	$(function () {
		$('img').on("error", function () {
			var link = $(this).attr('src');
			var title = $(this).attr('title');
			$(this).parent().html('<a href="' + link + '&debug" target="_blank" title="' + title + '"><?php echo CROSS_MARK_RED; ?></a>');
		});
	});
</script>

<div class="tabbox">
	<?php
	$cachesizes = 0;
	$currenttheme = $_gallery->getCurrentTheme();
	$themes = array();
	foreach ($_gallery->getThemes() as $theme => $data) {
		$themes[$theme] = $data['name'];
	}
	$last = '';
	cacheManager::printShowHide();
	?>

	<form class="dirtylistening" onReset="setClean('size_selections');" id="size_selections" name="size_selections" action="?tab=images&action=select&album=<?php echo $alb; ?>" method="post" autocomplete="off">
		<?php XSRFToken('cacheImages') ?>
		<ol class="no_bullets">
			<?php
			if (getOption('cache_full_image') && (!is_array($enabled) || in_array('*', $enabled))) {
				if (is_array($enabled)) {
					$fullImage = true;
					unset($enabled[array_search('*', $enabled)]);
					$checked = ' checked="checked" disabled="disabled"';
				} else {
					$checked = '';
				}
				$cachesizes++;
				?>
				<li>
					<?php
					if (!is_array($enabled)) {
						?>
						<span class="icons" id="<?php echo $theme; ?>_arrow">
							<?php echo PLACEHOLDER_ICON; ?>
						</span>
						<?php
					}
					?>
					<label>
						<input type="checkbox" name="enable[*]" value="*" <?php echo $checked; ?> />
						<?php echo gettext('Apply'); ?> <code><?php echo gettext('Full Image'); ?></code>
					</label>
				</li>
				<?php
			}
			$seen = array();

			foreach ($custom as $key => $cacheimage) {
				if (!is_array($enabled) || in_array($key, $enabled)) {
					$themeid = $cacheimage['theme'];
					$theme = preg_replace('/[^A-Za-z0-9\-_]/', '', $themeid);
					if (isset($themes[$theme])) {
						$themeid = $themes[$theme];
					}
					if (isset($cacheimage['album']) && $cacheimage['album']) {
						$theme .= '_' . $cacheimage['album'];
						$themeid .= ' (' . $cacheimage['album'] . ')';
					} else {
						$cacheimage['album'] = NULL;
					}

					if (is_array($enabled)) {
						$checked = ' checked="checked" disabled="disabled"';
					} else {
						if ($currenttheme == $cacheimage['theme'] || $cacheimage['theme'] == 'admin' || $cacheimage['album']) {
							$checked = ' checked="checked"';
						} else {
							$checked = '';
						}
					}
					$cachesizes++;
					$size = isset($cacheimage['image_size']) ? $cacheimage['image_size'] : NULL;
					$width = isset($cacheimage['image_width']) ? $cacheimage['image_width'] : NULL;
					$height = isset($cacheimage['image_height']) ? $cacheimage['image_height'] : NULL;
					$cw = isset($cacheimage['crop_width']) ? $cacheimage['crop_width'] : NULL;
					$ch = isset($cacheimage['crop_height']) ? $cacheimage['crop_height'] : NULL;
					$cx = isset($cacheimage['crop_x']) ? $cacheimage['crop_x'] : NULL;
					$cy = isset($cacheimage['crop_y']) ? $cacheimage['crop_y'] : NULL;
					$thumb = isset($cacheimage['thumb']) ? $cacheimage['thumb'] : NULL;
					$effects = isset($cacheimage['gray']) ? $cacheimage['gray'] : NULL;
					$WM = isset($cacheimage['wmk']) ? $cacheimage['wmk'] : NULL;
					$args = array('size' => $size, 'width' => $width, 'height' => $height, 'cw' => $cw, 'ch' => $ch, 'cx' => $cx, 'cy' => $cy, 'thumb' => $thumb, 'WM' => $WM, 'effects' => $effects);
					$postfix = getImageCachePostfix($args);
					if (isset($cacheimage['maxspace']) && $cacheimage['maxspace']) {
						if ($width && $height) {
							$postfix = str_replace('_w', '_wMax', $postfix);
							$postfix = str_replace('_h', '_hMax', $postfix);
						} else {
							$postfix = '_' . gettext('invalid MaxSpace');
							$checked = ' disabled="disabled"';
						}
					}
					if (empty($postfix)) {
						$postfix = gettext('invalid Cache Set');
						$checked = ' disabled="disabled"';
					}

					if ($theme != $last && !is_array($enabled)) {
						if ($last) {
							?>
						</ol>
						</span>
						</li>
						<?php
					}
					$last = $theme;
					?>
					<li>
						<span class="icons upArrow" id="<?php echo $theme; ?>_arrow">
							<a onclick="showTheme('<?php echo $theme; ?>');" title="<?php echo gettext('Show'); ?>">
								<?php echo ARROW_DOWN_GREEN; ?>
							</a>
						</span>
						<label>
							<input type="checkbox" name="<?php echo $theme; ?>" id="<?php echo $theme; ?>" value="" onclick="checkTheme('<?php echo $theme; ?>');"<?php echo $checked; ?> /><?php printf(gettext('all sizes for <em>%1$s</em>'), $themeid); ?>
						</label>
						<span id="<?php echo $theme; ?>_list" style="display:none">
							<ol class="no_bullets"><!-- <?php echo $last; ?> -->
								<?php
							}
							$show = true;
							if (is_array($enabled)) {
								if (array_key_exists($postfix, $seen)) {
									$show = false;
									unset($custom[$key]);
								}
								$seen[$postfix] = true;
							}
							if ($show) {
								?>
								<li class="no_bullets">
									<?php
									if (is_array($enabled)) {
										?>
										<input type="hidden" name="enable[]" value="<?php echo $key; ?>" />
										<?php
									}
									?>
									<label>
										<input type="checkbox" name="enable[]" class="<?php echo $theme; ?>" value="<?php echo $key; ?>" <?php echo $checked; ?> />
										<?php echo gettext('Apply'); ?> <code><?php echo ltrim($postfix, '_'); ?></code>
									</label>
								</li>
								<?php
							}
						}
					}
					if (!is_array($enabled)) {
						?>
					</ol>
				</span>
			</li>
			<?php
		}
		?>
		</ol>
		<?php
		if (is_array($enabled)) {
			if ($cachesizes) {
				echo '<p>';
				printf(ngettext('%u cache size to apply.', '%u cache sizes to apply.', $cachesizes), $cachesizes);
				echo '</p>';
				if ($alb) {
					$album = newAlbum($folder);
					$count = loadAlbum($album);
				} else {
					$albums = $_gallery->getAlbums();
					sort($albums);
					foreach ($albums as $folder) {
						$album = newAlbum($folder);
						if (!$album->isDynamic()) {
							$count = $count + loadAlbum($album);
						}
					}
				}
				$partb = sprintf(ngettext('%u cache size requested', '%u cache sizes requested', $count * $cachesizes), $count * $cachesizes);
				echo "\n" . "<br />" . sprintf(ngettext('Finished processing %1$u image (%2$s).', 'Finished processing %1$u images (%2$s).', $count), $count, $partb);
				if ($count) {
					$button = array('text' => gettext("Refresh"), 'title' => gettext('Refresh the caching of the selected image sizes if some images did not render.'));
				} else {
					$button = false;
				}
			} else {
				$button = false;
				?>
				<p><?php echo gettext('No cache sizes enabled.'); ?></p>';
				<?php
			}
		} else {
			$button = array('text' => gettext("Cache the images"), 'title' => gettext('Executes the caching of the selected image sizes.'));
		}
		?>

		<?php
		if ($button) {
			?>
			<p>
				<?php applyButton(array('buttonText' => CURVED_UPWARDS_AND_RIGHTWARDS_ARROW_BLUE . '' . $button['text'], 'buttonCass' => 'tooltip')); ?>
			</p>
			<?php
		}
		?>
		<br class="clearall" />
	</form>

	<?php
	echo "\n" . '</div>';
	echo "\n" . '</div>';
	printAdminFooter();
	echo "\n" . '</div>';
	echo "\n</body>";
	?>
