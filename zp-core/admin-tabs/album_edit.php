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
		echo gettext('Drag the albums into the order you wish them displayed.');

		printEditDropdown('subalbuminfo', array('1', '2', '3', '4', '5'), $subalbum_nesting);
		$sort = $_sortby;
		foreach ($sort as $name => $action) {
			$sort[$name . ' (' . gettext('descending') . ')'] = $action . '_DESC';
		}
		?>
		<br clear="all"><br />
		<?php
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
			?>
			<span class="buttons">
				<a href="<?php echo getAdminLink('admin-tabs/edit.php') . '?page=edit' . $parent; ?>">
					<?php echo BACK_ARROW_BLUE; ?>
					<strong><?php echo gettext("Back"); ?></strong>
				</a>
				<?php
				if ($enableEdit) {
					?>
					<button class="serialize buttons" type="submit">
						<?php echo CHECKMARK_GREEN; ?>
						<strong><?php echo gettext("Apply"); ?></strong>
					</button>
					<button type="reset" value="<?php echo gettext('Reset') ?>">
						<?php echo CROSS_MARK_RED; ?>
						<strong><?php echo gettext("Reset"); ?></strong>
					</button>

					<div class="floatright" style="padding-right: 5px">
						<button type="button" title="<?php echo addslashes(gettext('New subalbum')); ?>" onclick="newAlbumJS('<?php echo pathurlencode($album->name); ?>', false);">
							<img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/folder.png" alt="" />
							<strong><?php echo gettext('New subalbum'); ?></strong>
						</button>
						<?php
						if (!$album->isDynamic()) {
							?>
							<button type="button" title="<?php echo addslashes(gettext('New dynamic subalbum')); ?>" onclick="newAlbumJS('<?php echo pathurlencode($album->name); ?>', true);">
								<img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/folder.png" alt="" />
								<strong><?php echo gettext('New dynamic subalbum'); ?></strong>
							</button>
							<?php
						}
						?>
						<a href="<?php echo WEBPATH . "/index.php?album=" . pathurlencode($album->getFileName()); ?>">
							<?php echo BULLSEYE_BLUE; ?>
							<strong><?php echo gettext('View Album'); ?></strong>
						</a>
					</div>
					<?php
				}
				?>
			</span>
			<br class="clearall"><br />

			<div class="headline" style="text-align: left;"><?php echo gettext("Edit this album"); ?>
				<?php
				if ($enableEdit) {
					printBulkActions($checkarray_albums);
				}
				?>
			</div>
			<div class="subhead">
				<label class="buttons" style="float: left;padding-top:3px;">
					<a href="<?php echo getAdminLink('admin-tabs/edit.php'); ?>?page=edit&amp;album=<?php echo pathurlencode($album->name); ?>&amp;tab=subalbuminfo&amp;showthumbs=<?php echo $thumbshow ?>" title="<?php echo addslashes(gettext('Thumbnail generation may be time consuming on slow servers or when there are a lot of images.')); ?>">
						<?php echo $thumbmsg; ?>
					</a>
				</label>
				<?php
				if ($enableEdit) {
					?>
					<label style="float: right; padding-right:20px;">
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
			<span class="buttons">
				<a href="<?php echo getAdminLink('admin-tabs/edit.php') . '?page=edit' . $parent; ?>&filter=<?php echo $filter; ?>">
					<?php echo BACK_ARROW_BLUE; ?>
					<strong><?php echo gettext("Back"); ?></strong>
				</a>
				<button class="serialize buttons" type="submit">
					<?php echo CHECKMARK_GREEN; ?>
					<strong><?php echo gettext("Apply"); ?></strong>
				</button>
				<button type="reset" value="<?php echo gettext('Reset') ?>">
					<?php echo CROSS_MARK_RED; ?>
					<strong><?php echo gettext("Reset"); ?></strong>
				</button>
				<div class="floatright">
					<button type="button" title="<?php echo addslashes(gettext('New subalbum')); ?>" onclick="newAlbumJS('<?php echo pathurlencode($album->name); ?>', false);">
						<img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/folder.png" alt="" />
						<strong><?php echo gettext('New subalbum'); ?></strong>
					</button>
					<?php if (!$album->isDynamic()) { ?>
						<button type="button" title="<?php echo addslashes(gettext('New dynamic subalbum')); ?>" onclick="newAlbumJS('<?php echo pathurlencode($album->name); ?>', false);">
							<img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/folder.png" alt="" />
							<strong><?php echo gettext('New dynamic subalbum'); ?></strong>
						</button>
					<?php } ?>
				</div>
			</span>
		</form>
		<br class="clearall">
	</div><!-- subalbum -->
	<?php
}
