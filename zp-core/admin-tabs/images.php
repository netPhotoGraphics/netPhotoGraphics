<?php
/**
 * editing of albums and images.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 */
// force UTF-8 Ø

/* Don't put anything before this line! */
define('OFFSET_PATH', 1);

require_once(dirname(dirname(__FILE__)) . '/admin-globals.php');
require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/tag_suggest.php');

if (!isset($_admin_menu['images'])) { //	if the tab is set he owns some images so is allowed access
	admin_securityChecks(ALBUM_RIGHTS, $return = currentRelativeURL());
}
updatePublished('albums');
updatePublished('images');

if (isset($_GET['tab'])) {
	$subtab = sanitize($_GET['tab']);
} else {
	$subtab = '';
}

define('ADMIN_IMAGES_STEP', 5); //	the step for imges per page
$imagesTab_imageCount = 10;
processEditSelection($subtab);

//check for security incursions
$album = NULL;
if (isset($_GET['album'])) {
	$album = newAlbum($_GET['album']);
}

$showDefaultThumbs = getSerializedArray(getOption('album_tab_showDefaultThumbs'));

$tagsort = 'alpha';
$mcr_errors = array();

if (isset($_GET['showthumbs'])) { // switch the display selector
	$how = sanitize($_GET['showthumbs']);
	$key = is_object($showDefaultThumbs) ? $album->name : '*';
	if ($how == 'no') {
		$showDefaultThumbs[$key] = $key;
	} else {
		unset($showDefaultThumbs[$key]);
	}
	setOption('album_tab_showDefaultThumbs', serialize($showDefaultThumbs));
}

if (isset($_GET['action'])) {
	$action = sanitize($_GET['action']);
	switch ($action) {
		default:
			$return = sanitize_path($r = @$_GET['return']);
			if (!empty($return)) {
				$return = '&album=' . $return;
				if (strpos($r, '*') === 0) {
					$return .= '&tab=subalbuminfo';
				}
			}
			header('Location: ' . getAdminLink('admin-tabs/images.php') . '?page=edit' . $return);
			exit();

		case 'sorttags':
			if (isset($_GET['subpage'])) {
				$pg = '&subpage=' . sanitize($_GET['subpage']);
				$tab = '&tab=imageinfo';
			} else {
				$pg = '';
				$tab = '';
			}
			header('Location: ' . getAdminLink('admin-tabs/images.php') . '?page=edit&album=' . $folder . $pg . '&tagsort=' . html_encode($tagsort) . $tab);
			exit();
			break;

		case 'comments':
			XSRFdefender('albumedit');
			$album = newAlbum($folder);
			$album->setCommentsAllowed(sanitize_numeric($_GET['commentson']));
			$album->save();
			$return = sanitize_path($r = $_GET['return']);
			if (!empty($return)) {
				$return = '&album=' . $return;
				if (strpos($r, '*') === 0) {
					$return .= '&tab=subalbuminfo';
				}
			}
			header('Location: ' . getAdminLink('admin-tabs/images.php') . '?page = edit' . $return);
			exit();
			break;

		/** Reset hitcounters ********************************************************** */
		/*		 * ***************************************************************************** */
		case "reset_hitcounters":
			XSRFdefender('hitcounter');
			$id = sanitize_numeric($_REQUEST['albumid']);
			$imgwhere = ' WHERE `albumid` = ' . $id;
			$return = sanitize_path($r = $_GET['return']);
			if (!empty($return)) {
				$return = '&album=' . $return;
				if (strpos($r, '*') === 0) {
					$return .= '&tab=subalbuminfo';
				}
			}
			query("UPDATE " . prefix('images') . " SET `hitcounter`= 0" . $imgwhere);
			header('Location: ' . getAdminLink('admin-tabs/images.php') . '?page = edit' . $return . '&counters_reset');
			exit();
			break;

//** DELETEIMAGE **************************************************************/
		/*		 * *************************************************************************** */
		case 'deleteimage':
			XSRFdefender('delete');
			$albumname = sanitize_path($_REQUEST['album']);
			$imagename = sanitize_path($_REQUEST['image']);
			$album = newAlbum($albumname);
			$image = newImage($album, $imagename);
			if ($image->remove()) {
				$nd = 1;
			} else {
				$nd = 2;
			}
			header('Location: ' . getAdminLink('admin-tabs/images.php') . '?page = edit&album=' . pathurlencode($albumname) . '&ndeleted = ' . $nd);
			exit();
			break;

		/** REFRESH IMAGE METADATA */
		case 'refresh':
			XSRFdefender('imagemetadata');
			$albumname = sanitize_path($_REQUEST['album']);
			$imagename = sanitize_path($_REQUEST['image']);
			$image = newImage(array('folder' => $albumname, 'filename' => $imagename));
			$image->updateMetaData();
			$image->save();
			if (isset($_GET['album'])) {
				$return = pathurlencode(sanitize_path($_GET['album']));
			} else {
				$return = pathurlencode(sanitize_path(urldecode($_POST['album'])));
			}

			$return = '?page = edit&tab=imageinfo&album=' . $return . '&metadata_refresh';
			if (isset($_REQUEST['singleimage'])) {
				$return .= '&singleimage=' . sanitize($_REQUEST['singleimage']);
			}
			header('Location: ' . getAdminLink('admin-tabs/images.php') . $return);
			exit();
			break;

		/** SAVE ********************************************************************* */
		/*		 * *************************************************************************** */
		case "save":
			unset($folder);
			$bulknotify = $notify = $returntab = '';
			XSRFdefender('albumedit');
			/** SAVE A SINGLE ALBUM ****************************************************** */
			if (isset($_POST['album'])) {
				$folder = sanitize_path($_POST['album']);
				$album = newAlbum($folder, false, true);
				$returnalbum = NULL;
				if (isset($_POST['savealbuminfo']) && $album->exists) {
					$notify = processAlbumEdit(0, $album, $returnalbum);
					$returntab = '&tagsort=' . $tagsort . '&tab=albuminfo';
				}

				if (isset($single)) {
					$qs_albumsuffix = '&singleimage=' . $single;
				} else {
					$qs_albumsuffix = '';
				}
// Redirect to the same album we saved.
				if (isset($folder) && !empty($folder)) {
					$qs_albumsuffix .= '&album=' . pathurlencode($folder);
				}
				if (isset($_POST['subpage'])) {
					$pg = '&subpage=' . ($subpage = sanitize($_POST['subpage']));
				} else {
					$subpage = $pg = false;
				}
				if (isset($_POST['totalimages']) && $album->exists) {
					require_once(CORE_SERVERPATH . 'admin-tabs/image_save.php');
				}
				if (!is_null($returnalbum)) {
					$folder = $returnalbum;
				}
			}

			$msg = npgFilters::apply('edit_error', '');
			if ($msg) {
				$notify .= '&edit_error=' . $msg;
			}
			if ($notify == '&') {
				$notify = '';
			} else {
				if (empty($notify))
					$notify = '&saved';
			}
			if ($notify == '&saved' && $subpage && $subpage == 'object') {
				if (isset($image)) {
					$link = $image->getLink();
				} else {
					$link = $album->getLink();
				}
				header('Location: ' . $link);
				exit();
			}
			header('Location: ' . getAdminLink('admin-tabs/images.php') . '?page = edit' . $qs_albumsuffix . $bulknotify . $notify . $pg . $returntab);
			exit();
			break;
	} // end of switch
}



/* NO Admin-only content between this and the next check. */

/* * ********************************************************************************* */
/** End Action Handling ************************************************************ */
/* * ********************************************************************************* */


if (empty($subtab)) {
	if (isset($_GET['album'])) {
		$subtab = 'albuminfo';
	}
}

printAdminHeader('images', $subtab);
datepickerJS();
codeblocktabsJS();

if (isset($_GET['album']) && (empty($subtab) || $subtab == 'albuminfo')) {
	$result = db_list_fields('albums');
	$dbfields = array();
	if ($result) {
		foreach ($result as $row) {
			$dbfields[] = "'" . $row['Field'] . "'";
		}
	}
	sort($dbfields);
	$albumdbfields = implode(', ', $dbfields);
	$result = db_list_fields('images');
	$dbfields = array();
	if ($result) {
		foreach ($result as $row) {
			$dbfields[] = "'" . $row['Field'] . "'";
		}
	}
	sort($dbfields);
	$imagedbfields = implode(', ', $dbfields);
	?>
	<script type="text/javascript">
		//<!-- <![CDATA[
		var albumdbfields = [<?php echo $albumdbfields; ?>];
		$(function () {
			$('.customalbumsort').tagSuggest({
				tags: albumdbfields
			});
		});
		var imagedbfields = [<?php echo $imagedbfields; ?>];
		$(function () {
			$('.customimagesort').tagSuggest({
				tags: imagedbfields
			});
		});
		// ]]> -->
	</script>
	<?php
}
?>
<script type="text/javascript">
	//<!-- <![CDATA[
	var deleteAlbum1 = "<?php echo gettext("Are you sure you want to delete this entire album?"); ?>";
	var deleteAlbum2 = "<?php echo gettext("Are you Absolutely Positively sure you want to delete the album? THIS CANNOT BE UNDONE!"); ?>";
	function newAlbumJS(folder, dynamic) {
		var album = prompt('<?php echo addslashes(gettext('New album name?')); ?>', '<?php echo gettext('album'); ?>.' + $.now());
		if (album) {
			if (dynamic) {
				launchScript('<?php echo getAdminLink('admin-tabs/dynamic-album.php') ?>', ['action=newalbum', 'folder=' + folder, 'name=' + encodeURIComponent(album)]);
			} else {
				launchScript('', ['action=newalbum', 'folder=' + folder, 'name=' + encodeURIComponent(album), 'XSRFToken=<?php echo getXSRFToken('newalbum'); ?>']);
			}
		}
	}

	function confirmAction() {
		if ($('#checkallaction').val() == 'deleteall') {
			return confirm('<?php echo js_encode(gettext("Are you sure you want to delete the checked items?")); ?>');
		} else if ($('#checkallaction').val() == 'deleteallalbum') {
			if (confirm(deleteAlbum1)) {
				return confirm(deleteAlbum2);
			} else {
				return false;
			}
		} else {
			return true;
		}

	}

	var extraWidth;
	function resizeTable() {
		$('.width100percent').width($('.formlayout').width() - extraWidth);
	}

	window.addEventListener('load', function () {
		extraWidth = $('.rightcolumn').width() + 40;
<?php
if ($subtab == 'imageinfo') {
	?>
			extraWidth = extraWidth + $('.bulk_checkbox').width() + $('.leftdeatil').width() + 10;
	<?php
}
?>
		resizeTable();
	}, false);
	// ]]> -->
</script>

<?php
npgFilters::apply('texteditor_config', 'zenphoto');
npg_Authority::printPasswordFormJS();

echo "\n</head>";
?>

<body onresize="resizeTable()">

	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<?php
			$key = is_object($album) ? $album->name : '*';
			$showthumb = !in_array($key, $showDefaultThumbs);
			if ($showthumb) {
				$thumbshow = 'no';
				$thumbmsg = gettext('Show thumbnail stand-in');
			} else {
				$thumbshow = 'yes';
				$thumbmsg = gettext('Show album thumb');
			}
			$checkarray_images = array(
					gettext('*Bulk actions*') => 'noaction',
					gettext('Delete') => 'deleteall',
					gettext('Set to published') => 'showall',
					gettext('Set to unpublished') => 'hideall',
					gettext('Disable comments') => 'commentsoff',
					gettext('Enable comments') => 'commentson'
			);
			if (extensionEnabled('hitcounter')) {
				$checkarray_images[gettext('Reset hitcounter')] = 'resethitcounter';
			}
			$checkarray_albums = array_merge($checkarray_images, array(
					gettext('Delete') => 'deleteallalbum'
							)
			);
			$checkarray_images = array_merge($checkarray_images, array(
					gettext('Delete') => 'deleteall',
					gettext('Move') => array('name' => 'moveimages', 'action' => 'mass_movecopy_data'),
					gettext('Copy') => array('name' => 'copyimages', 'action' => 'mass_movecopy_data')
							)
			);
			$checkarray_images = npgFilters::apply('bulk_image_actions', $checkarray_images);

			/** EDIT ***************************************************************************
			 *
			 *  ********************************************************************************
			 */
			if (isset($_GET['album'])) {
				/** SINGLE ALBUM ******************************************************************* */
				// one time generation of this list.
				$mcr_albumlist = array();
				genAlbumList($mcr_albumlist);

				$oldalbumimagesort = $_gallery->getSortType('image');
				$direction = $_gallery->getSortDirection('image');

				if (!($album->subRights() & MANAGED_OBJECT_RIGHTS_EDIT)) {
					$allimages = array();
					$requestor = $_current_admin_obj->getUser();

					$albumowner = $album->getOwner();

					$sql = 'SELECT * FROM ' . prefix('images') . ' WHERE (`albumid`=' . $album->getID() . ') AND (`owner`="' . $requestor . '") ORDER BY `' . $oldalbumimagesort . '`';
					if ($direction)
						$sql .= ' DESC';

					$result = query($sql);
					if ($result) {
						while ($row = db_fetch_assoc($result)) {
							$allimages[] = $row['filename'];
						}
						db_free_result($result);
					}
				} else {
					$allimages = $album->getImages(0, 0, $oldalbumimagesort, $direction ? 'desc' : 'asc');
				}


				if (isset($_GET['filter'])) {
					$filter = sanitize($_GET['filter']);
				} else {
					$filter = '';
				}
				switch ($filter) {
					case'unpublished':
						$sql = 'SELECT `filename` FROM ' . prefix('images') . ' WHERE (`albumid`=' . $album->getID() . ') AND `show`="0"';
						$select = query_full_array($sql);
						break;
					case'published':
						$sql = 'SELECT `filename` FROM ' . prefix('images') . ' WHERE (`albumid`=' . $album->getID() . ') AND `show`="1"';
						$select = query_full_array($sql);
						break;
					default:
						$select = false;
				}
				if (!empty($select)) {
					$include = array();
					foreach ($select as $img) {
						$include[] = $img['filename'];
					}
					$allimages = array_intersect($allimages, $include);
				}

				$allimagecount = count($allimages);
				if (isset($_GET['tab']) && $_GET['tab'] == 'imageinfo' && isset($_GET['image'])) { // directed to an image
					$target_image = urldecode(sanitize($_GET['image']));
					$imageno = array_search($target_image, $allimages);
					if ($imageno !== false) {
						$pagenum = ceil(($imageno + 1) / $imagesTab_imageCount);
					}
				} else {
					$target_image = '';
				}
				if (!isset($pagenum)) {
					if (isset($_GET['subpage'])) {
						if (is_numeric($_GET['subpage'])) {
							$pagenum = max(intval($_GET['subpage']), 1);
							if (($pagenum - 1) * $imagesTab_imageCount >= $allimagecount)
								$pagenum--;
						} else {
							$pagenum = sanitize($_GET['subpage']);
						}
					} else {
						$pagenum = 1;
					}
				}
				if (is_numeric($pagenum)) {
					$images = array_slice($allimages, ($pagenum - 1) * $imagesTab_imageCount, $imagesTab_imageCount);
				} else {
					$images = $allimages;
				}

				$totalimages = count($images);

				$parent = dirname($album->name);
				if (($parent == '/') || ($parent == '.') || empty($parent)) {
					$parent = '';
				} else {
					$parent = "&amp;album=" . pathurlencode($parent);
				}
				if (isset($_GET['metadata_refresh'])) {
					echo '<div class="messagebox fade-message">';
					echo "<h2>" . gettext("Image metadata refreshed.") . "</h2>";
					echo '</div>';
				}

				if ($album->getParent()) {
					$link = getAlbumBreadcrumbAdmin($album);
				} else {
					$link = '';
				}
				$alb = removeParentAlbumNames($album);
				npgFilters::apply('admin_note', 'albums', $subtab);
				?>
				<h1><?php printf(gettext('Album: <em>%1$s%2$s</em>'), $link, $alb); ?></h1>
				<?php
				$subtab = getCurrentTab();
				if ($subtab == 'imageinfo') {
					$backButton = getAdminLink('admin-tabs/images.php') . '?page=edit' . $parent;
					require_once(CORE_SERVERPATH . 'admin-tabs/image_edit.php');
				}
			} else { /* Display a list of albums to edit. */
				npgFilters::apply('admin_note', 'albums', $subtab);
				?>
				<h1><?php echo gettext("Albums"); ?></h1>
				<div class="tabbox">
					<?php
					consolidatedEditMessages('');

					$albums = array();
					$sql = 'SELECT * FROM ' . prefix('albums') . ' as a, ' . prefix('images') . 'as i WHERE a.id=i.albumid AND i.owner=' . db_quote($owner = $_current_admin_obj->getUser()) . ' ORDER BY a.folder';
					$result = query($sql);

					while ($row = db_fetch_assoc($result)) {
						$folder = $row['folder'];
						if (isset($albums[$folder])) {
							$albums[$folder]['image_count'] ++;
						} else {
							$albums[$folder] = array('folder' => $folder, 'image_count' => 1);
						}
					}
					if (count($albums) > 0) {
						$list = array();
						foreach ($albums as $album => $data) {
							$level = array();
							$parts = explode('/', $album);
							$base = '';
							foreach ($parts as $cur => $analbum) {
								$albumObj = newalbum($base . $analbum);
								$level[$cur] = sprintf('%03u', $albumobj->getSortOrder());
								if (isset($albums[$base . $analbum])) {
									$count = $albums[$base . $analbum]['image_count'];
								} else {
									$count = 0;
								}
								$list[$base . $analbum] = array('name' => $base . $analbum, 'sort_order' => $level, 'image_count' => $count);
								$base .= $analbum . '/';
							}
						}
						?>
						<p>
							<?php
							echo gettext('Select an album to view your images.');
							?>
						</p>

						<div class="headline">
						</div>
						<div class="subhead">
							<label class="buttons" style="float: left;padding-top:3px;">
								<a href="<?php echo getAdminLink('admin-tabs/images.php') ?>?page=admin&tab=images
									 &showthumbs=<?php echo $thumbshow ?>" title="<?php echo gettext('Thumbnail generation may be time consuming on slow servers or when there are a lot of images.'); ?>">
										 <?php echo $thumbmsg; ?>
								</a>
							</label>

						</div>
						<div class="bordered">
							<ul class="page-list" id="albumsort">
								<?php printNestedImageList($list, $showthumb, NULL); ?>
							</ul>

						</div>

						<br class="clearall">
					</div>

					<?php
				} else {
					echo gettext("You have no images assigned to you.");
				}
			}
			?>
		</div><!-- content -->

		<?php printAdminFooter(); ?>
	</div><!-- main -->
</body>
<?php
// to fool the validator
echo "\n</html>";
?>
