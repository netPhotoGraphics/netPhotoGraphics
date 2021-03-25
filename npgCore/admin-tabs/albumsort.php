<?php
/**
 * used in sorting the images within and album
 * @package admin
 *
 */
// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__DIR__) . '/admin-globals.php');

if (isset($_REQUEST['album'])) {
	$localrights = ALBUM_RIGHTS;
} else {
	$localrights = NULL;
}
admin_securityChecks($localrights, $return = currentRelativeURL());

if (isset($_GET['album'])) {
	$folder = sanitize($_GET['album']);
	$album = newAlbum($folder);
	if (!$album->isMyItem(ALBUM_RIGHTS)) {
		if (!npgFilters::apply('admin_managed_albums_access', false, $return)) {
			header('Location: ' . getAdminLink('admin.php'));
			exit();
		}
	}

	if (isset($_GET['saved'])) {
		XSRFdefender('save_sort');
		if (isset($_POST['ids'])) { //	process bulk actions, not individual image actions.
			$action = processImageBulkActions($album);
			if (!empty($action))
				$_GET['bulkmessage'] = $action;
		}
		parse_str($_POST['sortableList'], $inputArray);
		if (isset($inputArray['id'])) {
			$orderArray = $inputArray['id'];
			if (!empty($orderArray)) {
				foreach ($orderArray as $key => $id) {
					$sql = 'UPDATE ' . prefix('images') . ' SET `sort_order`=' . db_quote(sprintf('%03u', $key)) . ' WHERE `id`=' . sanitize_numeric($id);
					query($sql);
				}
				$album->setSortType("manual");
				$album->setSortDirection(false, 'image');
				$album->save();
				$_GET['saved'] = 1;
			}
		}
		if (!isset($_POST['checkForPostTruncation'])) {
			$_GET['post_error'] = 1;
		}
	}
} else {
	$album = $_missing_album;
}

// Print the admin header
setAlbumSubtabs($album);
printAdminHeader('edit', 'sort');
?>
<script type="text/javascript">
	//<!-- <![CDATA[
	$(function () {
		$('#images').sortable({
			change: function (event, ui) {
				$('#sortableListForm').addClass('dirty');
			}
		});
	});
	function postSort(form) {
		$('#sortableListForm').removeClass('dirty');
		$('#sortableList').val($('#images').sortable('serialize'));
		form.submit();
	}
	function cancelSort() {
		$('#images').sortable('cancel');
	}
	// ]]> -->
</script>
<?php
echo "\n</head>";
?>


<body>

	<?php
	$checkarray_images = array(
			gettext('*Bulk actions*') => 'noaction',
			gettext('Delete') => 'deleteall',
			gettext('Set to published') => 'showall',
			gettext('Set to unpublished') => 'hideall',
	);
	$checkarray_images = npgFilters::apply('bulk_image_actions', $checkarray_images);

	// Layout the page
	printLogoAndLinks();
	?>

	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<?php
			if ($album->getParent()) {
				$link = getAlbumBreadcrumbAdmin($album);
			} else {
				$link = '';
			}
			$alb = removeParentAlbumNames($album);

			npgFilters::apply('admin_note', 'albums', 'sort');
			?>
			<h1><?php printf(gettext('Edit Album: <em>%1$s%2$s</em>'), $link, $alb); ?></h1>
			<?php
			$images = $album->getImages();
			$subtab = getCurrentTab();
			$parent = dirname($album->name);
			if ($parent == '/' || $parent == '.' || empty($parent)) {
				$parent = '';
			} else {
				$parent = '&amp;album=' . $parent . '&amp;tab=subalbuminfo';
			}
			?>
			<div id="container">

				<div class="tabbox">
					<?php
					if (isset($_GET['saved'])) {
						if (sanitize_numeric($_GET['saved'])) {
							consolidatedEditMessages($subtab);
						} else {
							if (isset($_GET['bulkmessage'])) {
								consolidatedEditMessages($subtab);
							} else {
								$messagebox = gettext("Nothing changed");
							}
							?>
							<div class="messagebox fade-message">
								<h2><?php echo $messagebox; ?></h2>
							</div>
							<?php
						}
					}
					?>
					<form class="dirtylistening" onReset="setClean('sortableListForm'); cancelSort();" action="?page=edit&amp;album=<?php echo $album->getFileName(); ?>&amp;saved&amp;tab=sort" method="post" name="sortableListForm" id="sortableListForm" >
						<?php XSRFToken('save_sort'); ?>
						<?php printBulkActions($checkarray_images, true); ?>

						<p>
							<?php
							backButton(array('buttonLink' => getAdminLink('admin-tabs/edit.php') . '?page=edit' . $parent));
							applyButton(array('buttonClick' => 'postSort(this.form)'));
							resetButton();
							viewButton(array('buttonLink' => $album->getLink()));
							?>
						</p>
						<br class="clearall" />
						<p><?php echo gettext("Set the image order by dragging them to the positions you desire."); ?></p>
						<ul id="images">
							<?php
							$images = $album->getImages();
							foreach ($images as $imagename) {
								$image = newImage($album, $imagename);
								if ($image->exists) {
									?>
									<li id="id_<?php echo $image->getID(); ?>">
										<?php
										if (!$image->getShow()) {
											?>
											<div  class="images_publishstatus" title="<?php echo gettext('unpublished'); ?>" >
												<?php echo EXCLAMATION_RED; ?>
											</div>
											<?php
										}
										?>
										<img class="imagethumb"
												 src="<?php echo getAdminThumb($image, 'medium-uncropped'); ?>"
												 alt="<?php echo html_encode($image->getTitle()); ?>"
												 title="<?php echo html_encode($image->getTitle()) . ' (' . pathurlencode($album->name) . ')'; ?>"												   />
										<br />
										<input type="checkbox" name="ids[]" value="<?php echo $imagename; ?>" title="<?php echo gettext('bulk action'); ?>">
										<a href="<?php echo getAdminLink('admin-tabs/edit.php'); ?>?page=edit&amp;album=<?php echo pathurlencode($album->name); ?>&amp;image=<?php echo urlencode($imagename); ?>&amp;tab=imageinfo#IT" title="<?php echo gettext('edit'); ?>">
											<?php echo PENCIL_ICON; ?>
										</a>
										<?php
										if (isImagePhoto($image)) {
											?>
											<a href="<?php echo html_encode($image->getFullImageURL()); ?>" class="colorbox" title="zoom">
												<?php echo MAGNIFY; ?>
											</a>
											<?php
										}
										linkPickerIcon($image);
										?>
									</li>
									<?php
								}
							}
							?>
						</ul>
						<br class="clearall" />

						<div>
							<input type="hidden" id="sortableList" name="sortableList" value="" />
							<?php
							backButton(array('buttonLink' => getAdminLink('admin-tabs/edit.php') . '?page=edit' . $parent));
							applyButton(array('buttonClick' => 'postSort(this.form)'));
							resetButton();
							viewButton(array('buttonLink' => $album->getLink()));
							?>
							</p>
						</div>
						<input type="hidden" name="checkForPostTruncation" value="1" />
					</form>
					<br class="clearall" />
				</div>
			</div>
		</div>
		<?php
		printAdminFooter();
		?>
	</div>
</body>

<?php
echo "\n</html>";
?>
