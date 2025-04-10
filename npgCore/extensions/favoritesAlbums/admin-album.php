<?php
/**
 * This script is used to create dynamic albums from a favorites page.
 * @author Stephen Billard (sbillard)
 *
 * @author Stephen Billard (sbillard)
 *
 * @Copyright 2014 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 * @package plugins/favoritesAlbums
 */
// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(dirname(__DIR__)) . '/admin-globals.php');
require_once(CORE_SERVERPATH . 'template-functions.php');

admin_securityChecks(ALBUM_RIGHTS, $return = currentRelativeURL());

$imagelist = array();

$user = $_current_admin_obj->getUser();
$favorite = trim(sanitize($_REQUEST['title']), '/');
if (isset($_GET['action']) && $_GET['action'] == 'savealbum') {
	XSRFdefender('savealbum');
	$albumname = sanitize($_POST['album']);
	if ($album = sanitize($_POST['albumselect'])) {
		$albumobj = newAlbum($album);
		$allow = $albumobj->isMyItem(UPLOAD_RIGHTS);
	} else {
		$allow = npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS);
	}
	if (!$allow) {
		if (!npgFilters::apply('admin_managed_albums_access', false, $return)) {
			trigger_error(gettext("You do not have edit rights on this album."), E_USER_WARNING);
		}
	}

	if (isset($_POST['thumb'])) {
		$thumb = sanitize($_POST['thumb']);
	} else {
		$thumb = '';
	}
	$redirect = $album . '/' . $albumname . '.fav';

	if (!empty($albumname)) {
		$f = fopen(internalToFilesystem(ALBUM_FOLDER_SERVERPATH . $redirect), 'w');
		if ($f !== false) {
			fwrite($f, "USER=$user\nTITLE=$favorite\nTHUMB=$thumb\n");
			fclose($f);
			// redirct to edit of this album
			header("Location: " . getAdminLink('admin-tabs/edit.php') . '?page=edit&album=' . pathurlencode($redirect));
			exit();
		}
	}
}
$_GET['page'] = 'edit'; // pretend to be the edit page.
printAdminHeader('edit', gettext('dynamic'));
echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
echo "\n" . '<div id="main">';
printTabs();
echo "\n" . '<div id="content">';
npgFilters::apply('admin_note', 'albums', 'dynamic');
echo "<h1>" . gettext("Create Favorites Album") . "</h1>\n";

if (isset($_POST['savealbum'])) { // we fell through, some kind of error
	echo "<div class=\"errorbox space\">";
	echo "<h2>" . gettext("Failed to save the album file") . "</h2>";
	echo "</div>\n";
}

$source = new favorites($user);
if ($favorite) {
	$source->instance = $favorite;
} else {
	$favorite = gettext('favorite');
}
$albumlist = genAlbumList();
$albumname = $user . '-' . $favorite;

$images = $source->getImages(0);
foreach ($images as $image) {
	$folder = $image['folder'];
	$filename = $image['filename'];
	$imagelist[] = '/' . $folder . '/' . $filename;
}

$subalbums = $source->getAlbums(0);
foreach ($subalbums as $folder) {
	getSubalbumImages($folder);
}
$albumname = sanitize_path($albumname);
$albumname = seoFriendly($albumname);
$old = '';
while ($old != $albumname) {
	$old = $albumname;
	$albumname = str_replace('--', '-', $albumname);
}
?>
<div class="tabbox">
	<form class="dirtylistening" onReset="setClean('savealbum_form');" id="savealbum_form" action="?action=savealbum" method="post">
		<?php XSRFToken('savealbum'); ?>
		<input type="hidden" name="savealbum" value="yes" />
		<input type="hidden" name="title" value="<?php echo sanitize($_GET['title']); ?>" />
		<table>
			<tr>
				<td><?php echo gettext("Album name:"); ?></td>
				<td>
					<input type="text" size="40" name="album" value="<?php echo html_encode($albumname) ?>" />
				</td>
			</tr>
			<tr>
				<td><?php echo gettext("Create in:"); ?></td>
				<td>
					<select id="albumselectmenu" name="albumselect">
						<?php
						if (accessAllAlbums(UPLOAD_RIGHTS)) {
							?>
							<option value="" selected="selected" style="font-weight: bold;">/</option>
							<?php
						}
						$bglevels = array('#fff', '#f8f8f8', '#efefef', '#e8e8e8', '#dfdfdf', '#d8d8d8', '#cfcfcf', '#c8c8c8');
						foreach ($albumlist as $fullfolder => $albumtitle) {
							$singlefolder = $fullfolder;
							$saprefix = "";
							$salevel = 0;
							// Get rid of the slashes in the subalbum, while also making a subalbum prefix for the menu.
							while (strstr($singlefolder, '/') !== false) {
								$singlefolder = substr(strstr($singlefolder, '/'), 1);
								$saprefix = "&nbsp; &nbsp;&raquo;&nbsp;" . $saprefix;
								$salevel++;
							}
							echo '<option value="' . $fullfolder . '"' . ($salevel > 0 ? ' style="background-color: ' . $bglevels[$salevel] . '; border-bottom: 1px dotted #ccc;"' : '')
							. ">" . $saprefix . $singlefolder . " (" . $albumtitle . ')' . "</option>\n";
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td><?php echo gettext("Thumbnail:"); ?></td>
				<td>
					<select id="thumb" name="thumb">
						<?php
						$selections = array();
						foreach ($_albumthumb_selector as $key => $selection) {
							$selections[$selection['desc']] = $key;
						}
						generateListFromArray(array(getOption('AlbumThumbSelect')), $selections, false, true);
						$showThumb = $_gallery->getThumbSelectImages();
						foreach ($imagelist as $imagepath) {
							$pieces = explode('/', $imagepath);
							$filename = array_pop($pieces);
							$folder = implode('/', $pieces);
							$albumx = newAlbum($folder);
							$image = newImage($albumx, $filename);
							if ($image->isPhoto() || !is_null($image->objectsThumb)) {
								echo "\n<option class=\"thumboption\"";
								if ($showThumb) {
									echo " style=\"background-image: url(" . html_encode($image->getSizedImage(80)) .
									"); background-repeat: no-repeat;\"";
								}
								echo " value=\"" . $imagepath . "\"";
								echo ">" . $image->getTitle();
								echo " ($imagepath)";
								echo "</option>";
							}
						}
						?>
					</select>
				</td>
			</tr>

		</table>
		<?php
		if (empty($albumlist)) {
			?>
			<p class="errorbox">
				<?php echo gettext('There is no place you are allowed to put this album.'); ?>
			</p>
			<p>
				<?php echo gettext('You must have <em>upload</em> rights to at least one album to have a place to store this album.'); ?>
			</p>
			<?php
		} else {
			applyButton(array('buttonText' => gettext('Create the album')));
		}
		?>
	</form>
	<br clear="all">
</div>
<?php
echo "\n" . '</div>';
printAdminFooter();
echo "\n" . '</div>';
echo "\n</body>";
echo "\n</html>";
?>

