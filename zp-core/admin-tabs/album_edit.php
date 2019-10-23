<?php
/*
 * the "guts" of the album edit page
 */
?>
<!-- Subalbum list goes here -->
<?php
if (count($subalbums) > 0) {
	$enableEdit = $album->subRights() & MANAGED_OBJECT_RIGHTS_EDIT;
	?>
	<div id="tab_subalbuminfo" class="tabbox">
		<?php
		consolidatedEditMessages('subalbuminfo');
		printSortableDirections(gettext('Drag the albums into the order you wish them displayed.'));

		printEditDropdown('subalbuminfo', array('1', '2', '3', '4', '5'), $subalbum_nesting);
		$sort = $_sortby;
		foreach ($sort as $name => $action) {
			$sort[$name . ' (' . gettext('descending') . ')'] = $action . '_DESC';
		}

		if (is_null($album->getParent())) {
			$globalsort = gettext("*gallery album sort order");
		} else {
			$globalsort = gettext("*parent album subalbum sort order");
		}
		$type = strtolower($album->get('subalbum_sort_type'));
		if ($type && !in_array($type, $sort)) {
			if ($type == 'manual') {
				$sort[gettext('Manual')] = $type;
			} else {
				$sort[gettext('Custom')] = $type = 'custom';
			}
		}
		if ($album->getSortDirection('albums')) {
			$type .= '_DESC';
		}
		$cv = array($type);
		if (($type == 'manual') || ($type == 'random') || ($type == '')) {
			$dsp = 'none';
		} else {
			$dsp = 'inline';
		}
		?>
		<form name="subalbum_sort" style="float: right;padding-right: 10px;" method="post" action="<?php echo getAdminLink('admin-tabs/edit.php'); ?>?page=edit&album=<?php echo pathurlencode($album->name); ?>&tab=subalbuminfo&action=subalbum_sortorder" >
			<?php XSRFToken('subalbum_sortorder'); ?>
			<span class="nowrap">
				<?php echo gettext('Sort subalbums by:'); ?>
				<select id="albumsortselect" name="subalbum_sortby" onchange="this.form.submit();">
					<option value =''><?php echo $globalsort; ?></option>
					<?php generateListFromArray($cv, $sort, false, true); ?>
				</select>
			</span>
		</form>
		<br clear="all">
		<form class="dirtylistening" onReset="setClean('sortableListForm');$('#albumsort').sortable('cancel');" action="?page=edit&amp;album=<?php echo pathurlencode($album->name); ?>&amp;action=savesubalbumorder&amp;tab=subalbuminfo" method="post" name="sortableListForm" id="sortableListForm" onsubmit="return confirmAction();" autocomplete="off" >
			<?php XSRFToken('savealbumorder'); ?>
			<p class="notebox">
				<?php echo gettext('<strong>Note:</strong> Dragging an album under a different parent will move the album. You cannot move albums under a <em>dynamic</em> album.'); ?>
			</p>
			<?php
			if ($enableEdit) {
				?>
				<p>
					<?php printf(gettext('Select an album to edit its description and data.'), pathurlencode($album->name)); ?>
				</p>
				<?php
			}
			backButton(array('buttonLink' => getAdminLink('admin-tabs/edit.php') . '?page=edit' . $parent));
			if ($enableEdit) {
				applyButton(array('buttonClass' => 'serialize'));
				resetButton();
				?>
				<div class="floatright" style="padding-right: 5px">
					<?php
					npgButton('button', FOLDER_ICON . ' ' . gettext('New subalbum'), array('buttonClick' => "newAlbumJS('" . pathurlencode($album->name) . "', false);"));
					if (!$album->isDynamic()) {
						npgButton('button', FOLDER_ICON . ' ' . gettext('New dynamic subalbum'), array('buttonTitle' => addslashes(gettext('New dynamic subalbum')), 'buttonClick' => "newAlbumJS('" . pathurlencode($album->name) . "', true);"));
					}
					viewButton(array('buttonLink' => $album->getLink()));
				}
				?>
			</div>
			<br class="clearall"><br />

			<div class="headline" style="text-align: left;">&nbsp;
				<?php
				if ($enableEdit) {
					printBulkActions($checkarray_albums);
				}
				?>
			</div>
			<div class="subhead">
				<label style="float: left;padding-top:3px;">
					<?php
					npgButton('button', $thumbmsg, array(
							'buttonLink' => getAdminLink('admin-tabs/edit.php') . '?page=edit&amp;album=' . pathurlencode($album->name) . '&amp;tab=subalbuminfo&amp;showthumbs=' . $thumbshow,
							'buttonTitle' => addslashes(gettext('Thumbnail generation may be time consuming on slow servers or when there are a lot of images.'))
									)
					);
					?>
				</label>
				<?php
				if ($enableEdit) {
					?>
					<label style="float: right; padding-top:5px;padding-right:25px;">
						<?php echo gettext("Check All"); ?> <input type="checkbox" name="allbox" id="allbox" onclick="checkAll(this.form, 'ids[]', this.checked);" />
					</label>
					<?php
				}
				?>
			</div>
			<div class="bordered">
				<ul class="page-list" id="albumsort">
					<?php
					printNestedAlbumsList($subalbums, $showthumb, $album);
					?>
				</ul>

			</div>
			<?php printAlbumLegend(); ?>
			<span id="serializeOutput"></span>
			<input name="update" type="hidden" value="Save Order" />
			<br />
			<span>
				<?php
				backButton(array('buttonLink' => getAdminLink('admin-tabs/edit.php') . '?page=edit' . $parent));
				applyButton(array('buttonClass' => 'serialize'));
				resetButton();
				?>
				<div class="floatright">
					<?php
					npgButton('button', FOLDER_ICON . ' ' . gettext('New subalbum'), array('buttonClick' => "newAlbumJS('" . pathurlencode($album->name) . "', false);"));
					if (!$album->isDynamic()) {
						npgButton('button', FOLDER_ICON . ' ' . gettext('New dynamic subalbum'), array('buttonClick' => "newAlbumJS('" . pathurlencode($album->name) . "', false);", 'buttonTitle' => addslashes(gettext('New dynamic subalbum'))));
					}
					?>
				</div>
			</span>
		</form>
		<br class="clearall">
	</div><!-- subalbum -->
	<?php
}
