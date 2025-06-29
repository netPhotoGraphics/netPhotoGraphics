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
require_once(PLUGIN_SERVERPATH . 'cacheManager/functions.php');
if (CURL_ENABLED) {
	require (CORE_SERVERPATH . 'lib-CURL.php');
}

if (isset($_REQUEST['album'])) {
	$localrights = ALBUM_RIGHTS;
} else {
	$localrights = NULL;
}

admin_securityChecks($localrights, $return = currentRelativeURL());

if (isset($_GET['action']) && $_GET['action'] == 'select' && isset($_POST['enable'])) {
	XSRFdefender('cacheImages');
	$enabled = $_POST['enable'];
} else {
	$enabled = false;
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

$result = query('SELECT `data` FROM ' . prefix('plugin_storage') . ' WHERE `type` = "cacheManager"');
if ($result) {
	while ($row = db_fetch_assoc($result)) {
		$datarow = getSerializedArray($row['data']);
		if ($datarow['theme']) {
			$custom[] = $datarow;
		}
	}
	db_free_result($result);
}
$custom = sortMultiArray($custom, array('theme' => false, 'album' => false, 'thumb' => false, 'image_size' => false, 'image_width' => false, 'image_height' => false), true, true);

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

if (!CURL_ENABLED) {
	?>

	<script>
		$(function () {
			$('img').on("error", function () {
				var link = $(this).attr('src');
				var title = $(this).attr('title');
				$(this).parent().html('<a href="' + link + '&debug" target="_blank" title="' + title + '"><?php echo CROSS_MARK_RED; ?></a>');
			});
		});
	</script>
	<?php
}
?>

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

	<form class="dirtylistening" onReset="setClean('cache_size_selections');" id="cache_size_selections" name="cache_size_selections" action="?tab=images&action=select&album=<?php echo pathurlencode($alb); ?>" method="post" autocomplete="off">
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
					$owner = $themeid = $cacheimage['theme'];
					$checked = '';

					if (array_key_exists('class', $cacheimage)) {
						$type = $cacheimage['class'];
					} else {
						$type = 'legacy';
					}
					switch ($type) {
						default:
						case 'legacy':
						case 'custom':
							break;
						case 'theme':
							if (is_dir(SERVERPATH . '/' . THEMEFOLDER . '/' . $owner)) {
								break;
							}
						case 'plugin':
							if (getPlugin($owner . '.php')) {
								if (!extensionEnabled($owner)) {
									$themeid = '<span class="deprecated" title="' . gettext('Plugin is not enabled') . '">' . $themeid . '</span>';
									$checked = ' disabled="disabled"';
								}
								break;
							}
						case 'deprecated';
							//	owner no longer exists
							$themeid = '<span class="deprecated" title="' . gettext('Owner no longer exists') . '">' . $themeid . '</span>';
							$checked = ' disabled="disabled"';
							break;
					}

					if (is_array($enabled)) {
						$checked = ' checked="checked" disabled="disabled"';
					} else {
						if ($currenttheme == $cacheimage['theme'] || $cacheimage['theme'] == 'admin' || $cacheimage['album']) {
							$checked = ' checked="checked"';
						}
					}

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
				$complete = true;
				echo '<p>';
				printf(ngettext('%u cache size to apply.', '%u cache sizes to apply.', $cachesizes), $cachesizes);
				echo '</p>';
				if ($alb) {
					$album = newAlbum($folder);
					$count = loadAlbum($album);
				} else {
					$albums = $_gallery->getAlbums();
					sort($albums);
					foreach ($albums as $key => $folder) {
						$album = newAlbum($folder);
						if (!$album->isDynamic()) {
							$count = $count + loadAlbum($album);
							if ($count > 500) {
								$complete = $key + 1 >= count($albums);

								break;
							}
						}
					}
				}
				$partb = sprintf(ngettext('%u cache size requested', '%u cache sizes requested', $count * $cachesizes), $count * $cachesizes);
				echo "\n" . "<br />";
				if ($complete) {
					printf(ngettext('Finished processing %1$u image (%2$s).', 'Finished processing %1$u images (%2$s).', $count), $count, $partb);
				} else {
					printf(ngettext('Interum processing %1$u image (%2$s).', 'Interum processing %1$u images (%2$s).', $count), $count, $partb);
				}
				if ($count && $complete) {
					$button = array('text' => gettext("Refresh"), 'title' => gettext('Refresh the caching of the selected image sizes if some images did not render.'));
				} else if (!$complete) {
					$button = array('text' => gettext("Continue"), 'title' => gettext('Refresh the caching of the selected image sizes if some images did not render.'));
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
