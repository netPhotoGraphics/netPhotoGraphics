<?php
/**
 * This script is used to create dynamic albums from a search.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package core
 */
// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__DIR__) . '/admin-globals.php');
require_once(CORE_SERVERPATH . 'template-functions.php');

admin_securityChecks(ALBUM_RIGHTS, $return = currentRelativeURL());

$imagelist = array();

$search = new SearchEngine(true);
if (isset($_GET['action']) && $_GET['action'] == 'savealbum') {
	XSRFdefender('savealbum');
	$msg = gettext("Failed to save the album file");
	$_GET['name'] = $albumname = sanitize($_POST['album']);

	if (trim($_POST['words'])) {
		if ($album = sanitize($_POST['albumselect'])) {
			$albumobj = newAlbum($album);
			$allow = $albumobj->isMyItem(ALBUM_RIGHTS);
		} else {
			$allow = npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS);
		}
		$allow = npgFilters::apply('admin_managed_albums_access', $allow, $return);

		if ($allow) {
			if ($_POST['create_tagged'] == 'static') {
				//	create the tag
				$words = sanitize($_POST['album_tag']);
				$success = create_update_tag(sanitize($_POST['album_tag']), NULL, 1);
				if (isset($_POST['return_unpublished'])) {
					$search->setSearchUnpublished();
				}
				$_POST['return_unpublished'] = true; //	state is frozen at this point, so unpublishing should not impact

				$searchfields[] = 'tags_exact';
				// now tag each element
				if (isset($_POST['return_albums'])) {
					$subalbums = $search->getAlbums();
					foreach ($subalbums as $analbum) {
						$albumobj = newAlbum($analbum);
						$tags = array_unique(array_merge($albumobj->getTags(false), array($words)));
						$albumobj->setTags($tags);
						$albumobj->save();
					}
				}
				if (isset($_POST['return_images'])) {
					$images = $search->getImages();
					foreach ($images as $animage) {
						$image = newImage(newAlbum($animage['folder']), $animage['filename']);
						$tags = array_unique(array_merge($image->getTags(false), array($words)));
						$image->setTags($tags);
						$image->save();
					}
				}
			} else {
				$searchfields = array();
				foreach ($_POST as $key => $v) {
					if (strpos($key, 'SEARCH_') === 0) {
						$searchfields[] = $v;
					}
				}
				$criteria = explode('::', sanitize($_POST['words']));
				if (isset($criteria[0])) {
					$words = $criteria[0];
				} else {
					$words = NULL;
				}
			}
			if (isset($_POST['thumb'])) {
				$thumb = sanitize($_POST['thumb']);
			} else {
				$thumb = '';
			}
			$inalbums = (int) (isset($_POST['return_albums']));
			$inAlbumlist = sanitize($_POST['albumlist']);
			if ($inAlbumlist) {
				$inalbums .= ':' . $inAlbumlist;
			}

			$constraints = "\nCONSTRAINTS=" . 'inalbums=' . $inalbums . '&inimages=' . ((int) (isset($_POST['return_images']))) . '&unpublished=' . ((int) (isset($_POST['return_unpublished'])));

			$constraints .= '&exact_tag_match=' . sanitize($_POST['tag_match']) . '&exact_string_match=' . sanitize($_POST['string_match']) . '&search_space_is=' . sanitize($_POST['search_space_is']) . '&languageTagSearch=' . sanitize($_POST['languageTagSearch']);

			$redirect = $album . '/' . $albumname . '.alb';

			if (!empty($albumname)) {
				$f = fopen(internalToFilesystem(ALBUM_FOLDER_SERVERPATH . $redirect), 'w');
				if ($f !== false) {
					fwrite($f, "WORDS=$words\nTHUMB=$thumb\nFIELDS=" . implode(',', $searchfields) . $constraints . "\n");
					fclose($f);
					clearstatcache();
					// redirct to edit of this album
					header("Location: " . getAdminLink('admin-tabs/edit.php') . '?page=edit&album=' . pathurlencode($redirect));
					exit();
				}
			}
		} else {
			$msg = gettext("You do not have edit rights on this album.");
		}
	} else {
		$msg = gettext('Your search criteria is empty.');
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
echo "<h1>" . gettext("Create Dynamic Album") . "</h1>\n";
?>
<div class="tabbox">
	<?php
	if (isset($_POST['savealbum'])) { // we fell through, some kind of error
		echo "<div class=\"errorbox space\">";
		echo "<h2>" . $msg . "</h2>";
		echo "</div>\n";
	}

	$albumlist = genAlbumList();
	$fields = $search->fieldList;
	$words = $search->codifySearchString();
	$inAlbumlist = $search->getAlbumList();

	if (empty($inAlbumlist)) {
		$inalbums = '';
	} else {
		$inalbums = implode(',', $inAlbumlist);
	}

	if (isset($_GET['name'])) {
		$albumname = sanitize($_GET['name']);
	} else {
		$albumname = seoFriendly(sanitize_path($words));
		$old = '';
		while ($old != $albumname) {
			$old = $albumname;
			$albumname = str_replace('--', '-', $albumname);
		}
	}

	$images = $search->getImages(0);
	foreach ($images as $image) {
		$folder = $image['folder'];
		$filename = $image['filename'];
		$imagelist[] = '/' . $folder . '/' . $filename;
	}
	$subalbums = $search->getAlbums(0);
	foreach ($subalbums as $folder) {
		getSubalbumImages($folder);
	}
	?>
	<form class="dirtylistening" onReset="setClean('savealbun_form');" id="savealbun_form" action="?action=savealbum" method="post" autocomplete="off" >
		<?php XSRFToken('savealbum'); ?>
		<input type="hidden" name="savealbum" value="yes" />
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
							<option value="" style="font-weight: bold;">/</option>
							<?php
						}
						if (isset($_GET['folder'])) {
							$parentalbum = sanitize($_GET['folder']);
						} else {
							$parentalbum = NULL;
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
							$selected = '';
							if ($parentalbum == $fullfolder) {
								$selected = ' selected="selected"';
							}
							echo '<option value="' . $fullfolder . '"' . $selected . '>' . $saprefix . $singlefolder . ' (' . $albumtitle . ')' . '</option>\n';
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
									echo " style=\"background-image: url(" . html_encode($image->getSizedImage(ADMIN_THUMB_MEDIUM)) .
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
			<tr>
				<td class="nowrap"><?php echo gettext("Search criteria:"); ?></td>
				<td>
					<input type="text" size="60" name="words" value="<?php echo html_encode($words); ?>" />
					<br />
					<label>
						<input type="checkbox" name="return_albums" value="1"<?php if (!getOption('search_no_albums')) echo ' checked="checked"' ?> />
						<?php echo gettext('Return albums found') ?>
					</label>
					<label>
						<input type="checkbox" name="return_images" value="1"<?php if (!getOption('search_no_images')) echo ' checked="checked"' ?> />
						<?php echo gettext('Return images found') ?>
					</label>
					<label>
						<input type="checkbox" name="return_unpublished" value="1" />
						<?php echo gettext('Return unpublished items') ?>
					</label>
					<br />

					<?php
					echo gettext('string matching');
					generateRadiobuttonsFromArray((int) getOption('exact_string_match'), array(gettext('<em>pattern</em>') => 0, gettext('<em>partial word</em>') => 1, gettext('<em>word</em>') => 2), 'string_match', 'string_match', false, false);
					?>
					<br />
					<?php
					echo gettext('tag matching');
					generateRadiobuttonsFromArray((int) getOption('exact_tag_match'), array(gettext('<em>partial</em>') => 0, gettext('<em>word</em>') => 2, gettext('<em>exact</em>') => 1), 'tag_match', 'tag_match', false, false);
					?>
					<br />
					<?php echo gettext('language specific tags'); ?>
					<label>
						<input type="radio" name="languageTagSearch"  value="" <?php if (getOption('languageTagSearch') == 0) echo ' checked="checked"'; ?> /><?php echo gettext('off'); ?>
					</label>
					<label>
						<input type="radio" name="languageTagSearch"  value="1" <?php if (getOption('languageTagSearch') == 1) echo ' checked="checked"'; ?> /><?php echo gettext('generic'); ?>
					</label>
					<label>
						<input type="radio" name="languageTagSearch"  value="2" <?php if (getOption('languageTagSearch') == 2) echo ' checked="checked"'; ?> /><?php echo gettext('specific'); ?>
					</label>
					<br />
					<?php
					echo gettext('treat spaces as');
					generateRadiobuttonsFromArray(getOption('search_space_is'), array(gettext('<em>space</em>') => '', gettext('<em>OR</em>') => 'OR', gettext('<em>AND</em>') => 'AND'), 'search_space_is', 'search_space_is', false, false);
					?>
				</td>
			</tr>
			<tr>
				<td></td>
				<td>
					<?php echo gettext('within'); ?>
					<input type="text" size="60" name="albumlist" value="<?php echo html_encode($inalbums); ?>" />
				</td>
			</tr>

			<script>

				function setTagged(state) {
					if (state) {
						$('#album_tag').show();
						$('.searchcheck').prop('disabled', true);
					} else {
						$('.searchcheck').prop('disabled', false);
						$('#album_tag').hide();
					}
				}

			</script>

			<tr>
				<td>
					<label class="nowrap">
						<input type="radio" name="create_tagged" value="dynamic" onchange="setTagged(false)" checked="checked" />
						<?php echo gettext('dynamic'); ?>
					</label>
					<label class="nowrap">
						<input type="radio" name="create_tagged" value="static" onchange="setTagged(true)"/>
						<?php echo gettext('tagged'); ?>
					</label>
					&nbsp;
				</td>
				<td>
					<?php echo gettext('Select <em>tagged</em> to statically define the search results as the album contents.'); ?>
				</td>
			</tr>
			<tr id="album_tag" style="display: none">
				<td><?php echo gettext('Album <em>Tag</em>'); ?></td>
				<td>
					<input type="text" size="40" name="album_tag" id="album_tag" value="<?php echo html_encode($albumname) . '.' . time(); ?>" />
					<span class="info_info">
						<?php echo INFORMATION_BLUE; ?>
						<div class="info_desc_hidden">
							<?php echo gettext('This tag will be assigned to each item found by the search. The search criteria for the album will be exclusively the tag. Thus the contents of this dynamic album are defined statically at the time of creation.'); ?>
						</div>
					</span>
				</td>
			</tr>
			<tr>
				<td class="nowrap"><?php echo gettext("Search fields:"); ?></td>
				<td>
					<ul class="searchchecklist">
						<?php
						$selected_fields = array();
						$engine = new SearchEngine(true);
						$available_fields = $engine->allowedSearchFields();
						if (count($fields) == 0) {
							$selected_fields = $available_fields;
						} else {
							foreach ($available_fields as $display => $key) {
								if (in_array($key, $fields)) {
									$selected_fields[$display] = $key;
								}
							}
						}
						generateUnorderedListFromArray($selected_fields, $available_fields, 'SEARCH_', false, true, true, 'searchcheck');
						?>
					</ul>
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
		?></form>
	<br clear="all">
</div>
<?php
echo "\n" . '</div>';
printAdminFooter();
echo "\n" . '</div>';
echo "\n</body>";
echo "\n</html>";
?>

