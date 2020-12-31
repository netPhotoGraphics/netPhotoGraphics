<?php
/*
 * the "guts" of the mass album edit page
 */

// one time generation of this list.
$mcr_albumlist = genAlbumList();
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
		$edit = array('description' => 1, 'general' => 1, 'utilities' => 1, 'sort' => 1, 'theme' => 1, 'watermark' => 1);
		foreach ($_COOKIE as $cookie => $value) {
			if (strpos($cookie, 'album_edit_') === 0) {
				$item = substr($cookie, 11);
				$set = '$edit[\'' . $item . '\']=' . (int) (strtolower($value) == 'true') . ';';
				eval($set);
			}
		}
		?>

		<script type="text/javascript">
			function toggle_stuff(stuff) {
				state = $('#' + stuff + '_box').prop('checked')
				$('.' + stuff + '_stuff').toggle();
				$('.' + stuff + '_stuff :input').prop('disabled', !state);
				$('.initial_disabled').prop('disabled', true);
				setCookie('album_edit_' + stuff, state, 2, '<?php echo $cookiepath ?>');
			}
			window.addEventListener('load', function () {
<?php ?>
				$('input:disabled').addClass('initial_disabled');
<?php
foreach ($edit as $stuff => $state) {
	if (!$state) {
		?>
						toggle_stuff('<?php echo $stuff; ?>', false);
						setCookie('album_edit_' + stuff, 'false', 2, '<?php echo $cookiepath ?>');
		<?php
	}
}
?>
			}, false);
		</script>
		<div id="menu_selector_button">
			<div id="menu_button">
				<a onclick="$('#menu_selections').show();$('#menu_button').hide();" class="floatright" title="<?php echo gettext('Select what shows on page'); ?>"><?php echo '&nbsp;&nbsp;' . MENU_SYMBOL; ?></a>
			</div>
			<div id="menu_selections" style="display: none;">
				<a onclick="$('#menu_selections').hide();$('#menu_button').show();" class="floatright" title="<?php echo gettext('Select what shows on page'); ?>"><?php echo '&nbsp;&nbsp;' . MENU_SYMBOL; ?></a>
				<div class="floatright">
					<label>
						<input id="description_box" type="checkbox" class="ignoredirty" value="1" <?php if ($edit['description']) echo 'checked="checked"' ?> onclick="toggle_stuff('description');"><?php echo gettext('Description'); ?>
					</label>
					<br />
					<label>
						<input id="sort_box" type="checkbox" class="ignoredirty" value="1" <?php if ($edit['sort']) echo 'checked="checked"' ?> onclick="toggle_stuff('sort');"><?php echo gettext('Sorts'); ?>
					</label>
					<br />
					<?php
					if (!isset($_GET['album'])) {
						?>
						<label>
							<input id="theme_box" type="checkbox" class="ignoredirty" value="1" <?php if ($edit['theme']) echo 'checked="checked"' ?> onclick="toggle_stuff('theme');"><?php echo gettext('Album theme'); ?>
						</label>
						<br />
						<?php
					}
					?>
					<label>
						<input id="watermark_box" type="checkbox" class="ignoredirty" value="1" <?php if ($edit['watermark']) echo 'checked="checked"' ?> onclick="toggle_stuff('watermark');"><?php echo gettext('Watermarks'); ?>
					</label>
					<br />
					<label>
						<input id="general_box" type="checkbox" class="ignoredirty" value="1" <?php if ($edit['general']) echo 'checked="checked"' ?> onclick="toggle_stuff('general');"><?php echo gettext('General'); ?>
					</label>
					<br />
					<label>
						<input id="utilities_box" type="checkbox" class="ignoredirty" value="1" <?php if ($edit['utilities']) echo 'checked="checked"' ?> onclick="toggle_stuff('utilities');"><?php echo gettext('Utilities'); ?>
					</label>
					<br />
				</div>
			</div>
		</div>
		<br style="clear:both"/><br />


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