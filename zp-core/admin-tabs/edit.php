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

admin_securityChecks(ALBUM_RIGHTS, $return = currentRelativeURL());
updatePublished('albums');
updatePublished('images');

if (isset($_GET['tab'])) {
	$subtab = sanitize($_GET['tab']);
} else {
	$subtab = '';
}
$is_massedit = $subtab == 'massedit';

$subalbum_nesting = 1;
$album_nesting = 1;
define('ADMIN_IMAGES_STEP', 5); //	the step for imges per page
$imagesTab_imageCount = 10;
processEditSelection($subtab);

//check for security incursions
$album = NULL;
$allow = true;
if (isset($_GET['album'])) {
	$folder = sanitize_path($_GET['album']);
	$album = newAlbum($folder, false, true);
	if ($album->exists) {
		$allow = $album->isMyItem(ALBUM_RIGHTS);
		if (!$allow) {
			if (isset($_GET['uploaded'])) { // it was an upload to an album which we cannot edit->return to sender
				header('Location: ' . getAdminLink('admin-tabs/upload.php') . '?uploaded=1');
				exit();
			}
		}
	} else {
		$album = NULL;
		unset($_GET['album']);
	}
}

$showDefaultThumbs = getSerializedArray(getOption('album_tab_showDefaultThumbs'));

if (!npgFilters::apply('admin_managed_albums_access', $allow, $return)) {
	header('Location: ' . getAdminLink('admin.php') . '?from=' . $return);
	exit();
}
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
			header('Location: ' . getAdminLink('admin-tabs/edit.php') . '?page=edit' . $return);
			exit();
		/** reorder the tag list ***************************************************** */
		/*		 * *************************************************************************** */
		case 'savealbumorder':
			XSRFdefender('savealbumorder');
			$notify = postAlbumSort(NULL);
			if ($notify) {
				if ($notify === true) {
					$notify = '&saved';
				} else {
					$notify = '&saved' . $notify;
				}
				$_gallery->setSortDirection(0);
				$_gallery->setSortType('manual');
				$_gallery->save();
			} else {
				$notify = '&noaction';
			}

			$notify = processAlbumBulkActions();
			if (empty($notify)) {
				$notify = '&noaction';
			} else {
				$notify = '&bulkmessage=' . $notify;
			}

			header('Location: ' . getAdminLink('admin-tabs/edit.php') . '?page=edit' . $notify);
			exit();
			break;
		case 'savesubalbumorder':
			XSRFdefender('savealbumorder');
			$notify = postAlbumSort($album->getID());
			if ($notify) {
				if ($notify === true) {
					$notify = '&saved';
				} else {
					$notify = '&saved' . $notify;
				}
				$album = newAlbum($folder);
				$album->setSortType('manual', 'album');
				$album->setSortDirection(false, 'album');
				$album->save();
			} else {
				$notify = '&noaction';
			}
			if ($_POST['checkallaction'] == 'noaction') {
				$notify = processAlbumBulkActions();
				if (empty($notify)) {
					$notify = '&noaction';
				} else {
					$notify = '&bulkmessage=' . $notify;
				}
			}
			header('Location: ' . getAdminLink('admin-tabs/edit.php') . '?page=edit&album=' . $folder . '&tab=subalbuminfo' . $notify);
			exit();
			break;
		case 'sorttags':
			if (isset($_GET['subpage'])) {
				$pg = '&subpage=' . sanitize($_GET['subpage']);
				$tab = '&tab=imageinfo';
			} else {
				$pg = '';
				$tab = '';
			}
			header('Location: ' . getAdminLink('admin-tabs/edit.php') . '?page=edit&album=' . $folder . $pg . '&tagsort=' . html_encode($tagsort) . $tab);
			exit();
			break;

		/** clear the cache ********************************************************** */
		/*		 * *************************************************************************** */
		case "clear_cache":
			XSRFdefender('clear_cache');
			if (isset($_GET['album'])) {
				$album = sanitize_path($_GET['album']);
			} else {
				$album = sanitize_path($_POST['album']);
			}
			Gallery::clearCache($album);
			header('Location: ' . getAdminLink('admin-tabs/edit.php') . '?page=edit&cleared&album=' . $album);
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
			header('Location: ' . getAdminLink('admin-tabs/edit.php') . '?page=edit' . $return);
			exit();
			break;

		/** Publish album  *********************************************************** */
		/*		 * *************************************************************************** */
		case "publish":
			XSRFdefender('albumedit');
			$album = newAlbum($folder);
			$album->setShow($_GET['value']);
			$album->save();
			$return = sanitize_path($r = $_GET['return']);
			if (!empty($return)) {
				$return = '&album=' . $return;
				if (strpos($r, '*') === 0) {
					$return .= '&tab=subalbuminfo';
				}
			}
			header('Location: ' . getAdminLink('admin-tabs/edit.php') . '?page=edit' . $return);
			exit();
			break;

		/** Reset hitcounters ********************************************************** */
		/*		 * ***************************************************************************** */
		case "reset_hitcounters":
			XSRFdefender('hitcounter');
			$id = sanitize_numeric($_REQUEST['albumid']);
			$where = ' WHERE `id`=' . $id;
			$imgwhere = ' WHERE `albumid`=' . $id;
			$return = sanitize_path($r = $_GET['return']);
			if (!empty($return)) {
				$return = '&album=' . $return;
				if (strpos($r, '*') === 0) {
					$return .= '&tab=subalbuminfo';
				}
			}
			query("UPDATE " . prefix('albums') . " SET `hitcounter`= 0" . $where);
			query("UPDATE " . prefix('images') . " SET `hitcounter`= 0" . $imgwhere);
			header('Location: ' . getAdminLink('admin-tabs/edit.php') . '?page=edit' . $return . '&counters_reset');
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
			header('Location: ' . getAdminLink('admin-tabs/edit.php') . '?page=edit&album=' . pathurlencode($albumname) . '&ndeleted=' . $nd);
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

			$return = '?page=edit&tab=imageinfo&album=' . $return . '&metadata_refresh';
			if (isset($_REQUEST['singleimage'])) {
				$return .= '&singleimage=' . sanitize($_REQUEST['singleimage']);
			}
			header('Location: ' . getAdminLink('admin-tabs/edit.php') . $return);
			exit();
			break;

		/**
		 * change sort order
		 */
		case "sortorder":
			XSRFdefender('albumsortorder');
			$oldsort = strtolower($_gallery->getSortType('image'));
			if ($_gallery->getSortDirection('image'))
				$oldsort = $oldsort . '_DESC';
			$newsort = sanitize($_POST['albumimagesort'], 3);
			if ($newsort != $oldsort && in_array(str_replace('_DESC', '', $newsort), $_sortby)) {
				if (strpos($newsort, '_DESC')) {

					echo "<br/>descending";

					$_gallery->setSortType(substr($newsort, 0, -5), 'image');
					$_gallery->setSortDirection('1', 'image');
				} else {
					$_gallery->setSortType($newsort, 'image');
					$_gallery->setSortDirection('0', 'image');
				}
				$_gallery->save();
			}
			$albumname = sanitize_path($_REQUEST['album']);
			if (isset($_POST['subpage'])) {
				$pg = '&subpage=' . sanitize($_POST['subpage']);
			} else {
				$pg = false;
			}
			$filter = sanitize($_REQUEST['filter']);
			if ($filter)
				$filter = '&filter=' . $filter;

			header('Location: ' . getAdminLink('admin-tabs/edit.php') . '?page=edit&album=' . $albumname . $pg . '&tagsort=' . $tagsort . '&tab=imageinfo' . $filter);
			exit();
			break;

		case "gallery_sortorder":
			XSRFdefender('gallery_sortorder');
			$oldsort = strtolower($_gallery->getSortType('album'));
			if ($_gallery->getSortDirection('albums')) {
				$oldsort = $oldsort . '_DESC';
			}
			$newsort = sanitize($_POST['gallery_sortby'], 3);
			if ($newsort != $oldsort && in_array(str_replace('_DESC', '', $newsort), $_sortby)) {
				if (strpos($newsort, '_DESC')) {
					$_gallery->setSortType(substr($newsort, 0, -5), 'album');
					$_gallery->setSortDirection('1', 'album');
				} else {
					$_gallery->setSortType($newsort, 'album');
					$_gallery->setSortDirection('0', 'album');
				}
				$_gallery->save();
			}
			header('Location: ' . getAdminLink('admin-tabs/edit.php') . '?page = edit');
			exit();
			break;

		case "subalbum_sortorder":
			XSRFdefender('subalbum_sortorder');
			$oldsort = strtolower($album->getSortType('album'));
			if ($album->getSortDirection('albums'))
				$oldsort = $oldsort . '_DESC';
			$newsort = sanitize($_POST['subalbum_sortby'], 3);
			if ($newsort != $oldsort && in_array(str_replace('_DESC', '', $newsort), $_sortby)) {
				if (strpos($newsort, '_DESC')) {
					$album->setSortType(substr($newsort, 0, -5), 'albums');
					$album->setSortDirection('1', 'albums');
				} else {
					$album->setSortType($newsort, 'albums');
					$album->setSortDirection('0', 'albums');
				}
				$album->save();
			}
			$albumname = sanitize_path($_REQUEST['album']);
			header('Location: ' . getAdminLink('admin-tabs/edit.php') . '?page=edit&album=' . $albumname . '&tab=subalbuminfo');
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

				$qs_albumsuffix = '';

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
					if (isset($single)) {
						$qs_albumsuffix = '&album=' . $album->name . '&singleimage=' . $single;
					}
				}
				if (!is_null($returnalbum)) {
					$folder = $returnalbum;
				}
				/** SAVE MULTIPLE ALBUMS ***************************************************** */
			} else if ($_POST['totalalbums']) {
				$notify = '';
				for ($i = 1; $i <= sanitize_numeric($_POST['totalalbums']); $i++) {
					if ($i > 0) {
						$prefix = $i . "-";
					} else {
						$prefix = '';
					}
					$f = sanitize_path(trim(sanitize($_POST[$prefix . 'folder'])));
					$album = newAlbum($f);
					$returnalbum = '';
					$rslt = processAlbumEdit($i, $album, $returnalbum);
					if (!empty($rslt)) {
						$notify = $rslt;
					}
				}
				$qs_albumsuffix = '&tab=massedit';
				if (isset($_GET['album'])) {
					$qs_albumsuffix = '&album=' . sanitize($_GET['album']) . $qs_albumsuffix;
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
			header('Location: ' . getAdminLink('admin-tabs/edit.php') . '?page = edit' . $qs_albumsuffix . $bulknotify . $notify . $pg . $returntab);
			exit();
			break;

		/** DELETION ***************************************************************** */
		/*		 * ************************************************************************** */
		case "deletealbum":
			XSRFdefender('delete');
			if ($folder) {
				$album = newAlbum($folder);
				if ($album->remove()) {
					$nd = 3;
				} else {
					$nd = 4;
				}
				if (isset($_GET['return'])) {
					$albumdir = sanitize($_GET['return'], 3);
				} else {
					$albumdir = dirname($folder);
				}
				if ($albumdir != '/' && $albumdir != '.') {
					$albumdir = "&album=" . pathurlencode($albumdir);
				} else {
					$albumdir = '';
				}
			} else {
				$albumdir = '';
			}

			header("Location: " . getAdminLink('admin-tabs/edit.php') . '?page=edit' . $albumdir . '&ndeleted=' . $nd);
			exit();
			break;
		case 'newalbum':
			XSRFdefender('newalbum');
			$name = sanitize($_GET['name']);
			$folder = sanitize_path($_GET['folder']);
			$seoname = seoFriendly($name);
			if (empty($folder) || $folder == '/' || $folder == '.') {
				$albumdir = '';
				$folder = $seoname;
			} else {
				$albumdir = "&album=" . pathurlencode($folder);
				$folder = $folder . '/' . $seoname;
			}
			$uploaddir = $_gallery->albumdir . internalToFilesystem($folder);
			if (is_dir($uploaddir)) {
				if ($name != $seoname)
					$name .= ' (' . $seoname . ')';
				if (isset($_GET['albumtab'])) {
					if (empty($albumdir)) {
						$tab = '';
					} else {
						$tab = '&tab=subalbuminfo';
					}
				} else {
					$tab = '&tab=albuminfo';
				}
				header("Location: " . getAdminLink('admin-tabs/edit.php') . '?page=edit$albumdir&exists=' . urlencode($name) . $tab);
				exit();
			} else {
				mkdir_recursive($uploaddir, FOLDER_MOD);
			}
			@chmod($uploaddir, FOLDER_MOD);

			$album = newAlbum($folder);
			if ($album->exists) {
				$album->setTitle($name);
				$album->save();
				header("Location: " . getAdminLink('admin-tabs/edit.php') . '?page=edit' . '&album=' . pathurlencode($folder));
				exit();
			} else {
				$AlbumDirName = str_replace(SERVERPATH, '', $_gallery->albumdir);
				$errorbox[] = gettext("The album couldn’t be created in the “albums” folder. This is usually a permissions problem. Try setting the permissions on the albums and cache folders to be world-writable using a shell:") . " <code>chmod 777 " . $AlbumDirName . '/' . CACHEFOLDER . '/' . "</code>, "
								. gettext("or use your FTP program to give everyone write permissions to those folders.");
			}
			break;
	} // end of switch
}



/* NO Admin-only content between this and the next check. */

/* * ********************************************************************************* */
/** End Action Handling ************************************************************ */
/* * ********************************************************************************* */

// Print our header
if (isset($_GET['album'])) {
	$folder = sanitize_path($_GET['album']);
	if ($folder == '/' || $folder == '.') {
		$parent = '';
	} else {
		$parent = '&amp;album=' . $folder . '&amp;tab=subalbuminfo';
	}
	$album = newAlbum($folder);
	$subtab = setAlbumSubtabs($album);
} else {
	$_admin_menu['edit']['subtabs'][gettext('Mass-edit albums')] = "/" . CORE_FOLDER . '/admin-tabs/edit.php?tab=massedit';
}
if (empty($subtab)) {
	if (isset($_GET['album'])) {
		$subtab = 'albuminfo';
	}
}

printAdminHeader('edit', $subtab);
datepickerJS();
codeblocktabsJS();

if ((!$is_massedit && !isset($_GET['album'])) || $subtab == 'subalbuminfo') {
	printSortableHead();
}
if (isset($_GET['album']) && (empty($subtab) || $subtab == 'albuminfo') || $is_massedit) {
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
			if (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
				$checkarray_images[gettext('Change owner')] = array('name' => 'changeowner', 'action' => 'mass_owner_data');
			}
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
			$checkarray_albums = npgFilters::apply('bulk_album_actions', $checkarray_albums);

			/** EDIT ***************************************************************************
			 *
			 *  ********************************************************************************
			 */
			if (isset($_GET['album']) && !$is_massedit) {
				/** SINGLE ALBUM ******************************************************************* */
				if (isset($_SESSION['mcr_albumlist'])) {
					$mcr_albumlist = $_SESSION['mcr_albumlist'];
				} else {
					// one time generation of this list.
					$mcr_albumlist = array();
					genAlbumList($mcr_albumlist);
					$_SESSION['mcr_albumlist'] = $mcr_albumlist;
				}

				$oldalbumimagesort = $_gallery->getSortType('image');
				$direction = $_gallery->getSortDirection('image');

				if ($album->isDynamic()) {
					$subalbums = array();
					$allimages = array();
				} else {
					$subalbums = getNestedAlbumList($album, $subalbum_nesting);
					if (!($album->subRights() & MANAGED_OBJECT_RIGHTS_EDIT)) {
						$allimages = array();
						$requestor = $_current_admin_obj->getUser();
						$albumowner = $album->getOwner();
						if ($albumowner == $requestor) {
							$retunNull = '`owner` IS NULL OR ';
						} else {
							$retunNull = '';
						}
						$sql = 'SELECT * FROM ' . prefix('images') . ' WHERE (`albumid`=' . $album->getID() . ') AND (' . $retunNull . ' `owner`="' . $requestor . '") ORDER BY `' . $oldalbumimagesort . '`';
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
				<h1><?php printf(gettext('Edit Album: <em>%1$s%2$s</em>'), $link, $alb); ?></h1>
				<?php
				$subtab = getCurrentTab();
				if ($subtab == 'albuminfo') {
					?>
					<!-- Album info box -->
					<div id="tab_albuminfo" class="tabbox">
						<?php consolidatedEditMessages('albuminfo'); ?>
						<form class="dirtylistening" onReset="toggle_passwords('', false);setClean('form_albumedit');$('.resetHide').hide();" name="albumedit1" id="form_albumedit" autocomplete="off" action="?page=edit&amp;action=save<?php echo "&amp;album=" . pathurlencode($album->name); ?>"	method="post" >
							<?php XSRFToken('albumedit'); ?>
							<input type="hidden" name="album"	value="<?php echo $album->name; ?>" />
							<input type="hidden"	name="savealbuminfo" value="1" />
							<?php printAlbumEditForm(0, $album); ?>
						</form>
						<br class="clearall">

					</div>
					<?php
				} else if ($subtab == 'subalbuminfo' && !$album->isDynamic()) {
					require_once(CORE_SERVERPATH . 'admin-tabs/album_edit.php');
				} else if ($subtab == 'imageinfo') {
					$backButton = getAdminLink('admin-tabs/edit.php') . '?page=edit' . $parent;
					require_once(CORE_SERVERPATH . 'admin-tabs/image_edit.php');
				}

				if ($subtab != "albuminfo") {
					?>
					<!-- page trailer -->
					<?php
				}
				/*				 * * MULTI-ALBUM ************************************************************************** */
			} else

			if ($is_massedit) {
				require_once(CORE_SERVERPATH . 'admin-tabs/album_masedit.php');
			} else { /* Display a list of albums to edit. */
				npgFilters::apply('admin_note', 'albums', $subtab);
				?>
				<h1><?php echo gettext("Albums"); ?></h1>
				<div class="tabbox">
					<?php
					consolidatedEditMessages('');
					$albums = getNestedAlbumList(NULL, $album_nesting);
					if (count($albums) > 0) {
						if (npg_loggedin(ADMIN_RIGHTS) && (count($albums)) > 1) {

							printEditDropdown('', array('1', '2', '3', '4', '5'), $album_nesting);

							$sort = $_sortby;
							foreach ($sort as $name => $action) {
								$sort[$name . ' (' . gettext('descending') . ')'] = $action . '_DESC';
							}
							?>
							<br clear="all"><br />
							<?php
							$type = strtolower($_gallery->getSortType());
							if ($type && !in_array($type, $sort)) {
								if ($type == 'manual') {
									$sort[gettext('Manual')] = $type;
								} else {
									$sort[gettext('Custom')] = $type = 'custom';
								}
							}
							if ($_gallery->getSortDirection()) {
								$type .= '_DESC';
							}
							$cv = array($type);
							if (($type == 'manual') || ($type == 'random') || ($type == '')) {
								$dsp = 'none';
							} else {
								$dsp = 'inline';
							}
							echo gettext('Drag the albums into the order you wish them displayed.');
							?>
							<form name="gallery_sort" style="float: right;padding-right: 10px;" method="post" action="<?php echo getAdminLink('admin-tabs/edit.php'); ?>?page=edit&action=gallery_sortorder" >
								<?php XSRFToken('gallery_sortorder'); ?>
								<span class="nowrap">
									<?php echo gettext('Sort albums by:'); ?>
									<select id="albumsortselect" name="gallery_sortby" onchange="this.form.submit();">
										<?php generateListFromArray($cv, $sort, false, true); ?>
									</select>
								</span>
							</form>
							<br clear="all">
							<p class="notebox">
								<?php echo gettext('<strong>Note:</strong> Dragging an album under a different parent will move the album. You cannot move albums under a <em>dynamic</em> album.'); ?>
							</p>
							<?php
						}
						?>
						<p>
							<?php
							echo gettext('Select an album to edit its description and data.');
							?>
						</p>

						<form class="dirtylistening" onReset="setClean('sortableListForm');$('#albumsort').sortable('cancel');" action="?page=edit&amp;action=savealbumorder" method="post" name="sortableListForm" id="sortableListForm" onsubmit="return confirmAction();" autocomplete="off" >
							<?php XSRFToken('savealbumorder'); ?>
							<span class="buttons">
								<?php
								if ($album_nesting > 1 || npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
									?>
									<button class="serialize buttons" type="submit" >
										<?php echo CHECKMARK_GREEN; ?>
										<strong><?php echo gettext("Apply"); ?></strong>
									</button>
									<button type="reset" value="<?php echo gettext('Reset') ?>">
										<?php echo CROSS_MARK_RED; ?>
										<strong><?php echo gettext("Reset"); ?></strong>
									</button>
									<?php
								}
								if (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
									?>
									<span class="floatright" style="padding-right: 3px;">
										<button type="button" onclick="newAlbumJS('', false);"><img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/folder.png" alt="" /><strong><?php echo gettext('New album'); ?></strong></button>
										<button type="button" onclick="newAlbumJS('', true);"><img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/folder.png" alt="" /><strong><?php echo gettext('New dynamic album'); ?></strong></button>
									</span>
									<?php
								}
								?>
							</span>
							<br class="clearall">
							<br />

							<div class="headline"><?php echo gettext("Edit this album"); ?>
								<?php printBulkActions($checkarray_albums); ?>
							</div>
							<div class="subhead">
								<label class="buttons" style="float: left;padding-top:3px;">
									<a href="<?php echo getAdminLink('admin-tabs/edit.php'); ?>?page=admin&tab=edit
										 &showthumbs=<?php echo $thumbshow ?>" title="<?php echo gettext('Thumbnail generation may be time consuming on slow servers or when there are a lot of images.'); ?>">
											 <?php echo $thumbmsg; ?>
									</a>
								</label>
								<label style="float: right;padding-right:20px;">
									<?php echo gettext("Check All"); ?> <input type="checkbox" name="allbox" id="allbox" onclick="checkAll(this.form, 'ids[]', this.checked);" />
								</label>
							</div>
							<div class="bordered">
								<ul class="page-list" id="albumsort">
									<?php printNestedAlbumsList($albums, $showthumb, NULL); ?>
								</ul>

							</div>
							<div>
								<?php printAlbumLegend(); ?>
							</div>

							<br class="clearall">
							<span id="serializeOutput"></span>
							<input name="update" type="hidden" value="Save Order" />

							<div class="buttons">
								<?php
								if ($album_nesting > 1 || npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
									?>
									<button class="serialize buttons" type="submit" >
										<?php echo CHECKMARK_GREEN; ?> <strong><?php echo gettext("Apply"); ?></strong>
									</button>
									<button type="reset" value="<?php echo gettext('Reset') ?>">
										<?php echo CROSS_MARK_RED; ?>
										<strong><?php echo gettext("Reset"); ?></strong>
									</button>
									<?php
								}
								if (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
									?>
									<span class="floatright">
										<button type="button" onclick="newAlbumJS('', false);"><img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/folder.png" alt="" /><strong><?php echo gettext('New album'); ?></strong></button>
										<button type="button" onclick="newAlbumJS('', true);"><img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/folder.png" alt="" /><strong><?php echo gettext('New dynamic album'); ?></strong></button>
									</span>
									<?php
								}
								?>
							</div>

						</form>
						<br class="clearall">
					</div>

					<?php
				} else {
					echo gettext("There are no albums for you to edit.");
					if (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
						?>
						<span class="floatright">
							<p class="buttons">
								<button type="button" onclick="newAlbumJS('', false);">
									<img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/folder.png" alt="" /><strong><?php echo gettext('New album'); ?></strong>
								</button>
								<button type="button" onclick="newAlbumJS('', true);">
									<img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/folder.png" alt="" /><strong><?php echo gettext('New dynamic album'); ?></strong>
								</button>
							</p>
						</span>
						<?php
					}
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
