<?php
/*
 * the "guts" of the mass album edit page
 */

$albumdir = "";
if (isset($_GET['album'])) {
	$folder = sanitize_path($_GET['album']);
	$album = newAlbum($folder);
	if ($album->isMyItem(ALBUM_RIGHTS)) {
		$albums = $album->getAlbums();
		$pieces = explode('/', $folder);
		$albumdir = "&album=" . pathurlencode($folder) . '&tab=subalbuminfo';
	} else {
		$albums = array();
	}
} else {
	$albumsprime = $_gallery->getAlbums();
	$albums = array();
	foreach ($albumsprime as $folder) { // check for rights
		$album = newAlbum($folder);
		if ($album->isMyItem(ALBUM_RIGHTS)) {
			$albums[] = $folder;
		}
	}
}
npgFilters::apply('admin_note', 'albums', $subtab);
?>
<h1>
	<?php
	if (!isset($_GET['album'])) {
		$what = gettext("Gallery");
	} else {
		$what = "<em>" . pathurlencode($album->name) . "</em>";
	}
	printf(gettext('Edit All Albums in %1$s'), $what);
	?>
</h1>
<div class="tabbox">
	<?php consolidatedEditMessages('massedit'); ?>
	<form class="dirtylistening" onReset="setClean('form_albumedit-multi');" ame="albumedit" id="form_albumedit-multi" autocomplete="off"	action="?page=edit&amp;action=save<?php echo $albumdir ?>" method="POST" >
		<?php XSRFToken('albumedit'); ?>
		<input type="hidden" name="totalalbums" value="<?php echo sizeof($albums); ?>" />
		<?php
		backButton(array('buttonLink' => getAdminLink('admin-tabs/edit.php') . '?page=edit'));
		applyButton();
		resetButton();
		if (($cookiepath = WEBPATH) == '') {
			$cookiepath = '/';
		}

		$stuff = array('description' => gettext('Description'), 'general' => gettext('General'), 'utilities' => gettext("Utilities"), 'sort' => gettext('Sorts'), 'watermark' => gettext('Watermarks'));
		if (!isset($_GET['album'])) {
			$stuff['theme'] = gettext('Album theme');
		}
		$stuff = array_merge($stuff, npgFilters::apply('mass_edit_selector', array(), 'albums'));
		asort($stuff, SORT_NATURAL | SORT_FLAG_CASE);
		printEditSelector('albums_edit', $stuff);
		?>
		<br style="clear:both"/>
		<br />
		<div class = "outerbox">
			<?php
			$currentalbum = 1;
			foreach ($albums as $folder) {
				$album = newAlbum($folder);
				echo "\n<!-- " . $album->name . " -->\n";
				?>
				<div class="innerbox<?php if (!($currentalbum % 2)) echo '_dark'; ?>" style="padding-left: 15px;padding-right: 15px;">

					<a href="<?php echo getAdminLink('admin-tabs/edit.php'); ?>?page=edit&album=<?php echo urlencode($album->name); ?>&tab=albuminfo">
						<em><strong><?php echo pathurlencode($album->name); ?></strong></em></a>
					<br /><br />
					<?php
					printAlbumEditForm($currentalbum, $album, false);
					$currentalbum++;
					?>
				</div>
				<?php
			}
			?>
		</div>
		<br />
		<?php
		backButton(array('buttonLink' => getAdminLink('admin-tabs/edit.php') . '?page=edit'));
		applyButton();
		resetButton();
		?>
		<br class="clearall" />
	</form>
</div>
				<?php
				/*				 * * EDIT ALBUM SELECTION ******************************************************************** */