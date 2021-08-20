<?php
/**
 * provides the Upload tab of admin
 *
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 */
// force UTF-8 Ø

define('OFFSET_PATH', 1);

require_once(dirname(__DIR__) . '/admin-globals.php');
admin_securityChecks(UPLOAD_RIGHTS, $return = currentRelativeURL());

if (isset($_GET['page'])) {
	$page = sanitize($_GET['page']);
} else {
	$link = $_admin_menu['upload']['link'];
	if (strpos($link, 'admin-tabs/upload.php') == false) {
		header('location: ' . $link);
		exit();
	}
	$page = "upload";
	$_GET['page'] = 'upload';
}

if (isset($_GET['tab'])) {
	$uploadtype = sanitize($_GET['tab']);
	setNPGCookie('uploadtype', $uploadtype);
} else {
	$uploadtype = getNPGCookie('uploadtype');
	$_GET['tab'] = $uploadtype;
}
$handlers = array_keys($uploadHandlers = npgFilters::apply('upload_handlers', array()));
if (count($handlers) > 0) {
	if (!isset($uploadHandlers[$uploadtype]) || !file_exists($uploadHandlers[$uploadtype] . '/upload_form.php')) {
		$uploadtype = array_shift($handlers);
	}
	require_once($uploadHandlers[$uploadtype] . '/upload_form.php');
} else {
	require_once(CORE_SERVERPATH . 'no_uploader.php');
	exit();
}
printAdminHeader('upload', 'albums');

//	load the uploader specific header stuff
$formAction = upload_head();

echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
?>
<div id="main">
	<?php
	printTabs();
	?>
	<div id="content">
		<?php
		$albumlist = genAlbumList();
		//	remove dynamic albums--can't upload to them
		foreach ($albumlist as $key => $albumname) {
			if (hasDynamicAlbumSuffix($key) && !is_dir(ALBUM_FOLDER_SERVERPATH . $key)) {
				unset($albumlist[$key]);
			}
		}
		?>
		<script type="text/javascript">
			// <!-- <![CDATA[
			// Array of album names for javascript functions.
			var albumArray = new Array(
<?php
$separator = '';
foreach ($albumlist as $key => $value) {
	echo $separator . "'" . addslashes($key) . "'";
	$separator = ", ";
}
?>);
			// ]]> -->
		</script>
		<?php npgFilters::apply('admin_note', 'upload', 'images'); ?>
		<h1><?php echo gettext("Upload Images"); ?></h1>

		<div class="tabbox">
			<p>
				<?php
				localeSort($_supported_images);
				$types = array_keys($_images_classes);
				$types[] = 'ZIP';
				$types = npgFilters::apply('upload_filetypes', $types);
				localeSort($types);
				$upload_extensions = $types;
				$last = strtoupper(array_pop($types));
				$s1 = strtoupper(implode(', ', $types));
				$used = 0;

				if (count($types) > 1) {
					printf(gettext('This web-based upload accepts the file formats: %s, and %s.'), $s1, $last);
				} else {
					printf(gettext('This web-based upload accepts the file formats: %s and %s.'), $s1, $last);
				}
				?>
			</p>
			<p class="notebox">
				<?php
				echo gettext('<strong>Note: </strong>');
				?>
				<br />
				<?php
				if ($last == 'ZIP') {
					echo gettext('ZIP files must contain only supported <em>image</em> types.');
					?>
					<br />
					<?php
				}
				$maxupload = ini_get('upload_max_filesize');
				$maxpost = ini_get('post_max_size');
				$maxuploadint = parse_size($maxupload);
				$maxpostint = parse_size($maxpost);
				if ($maxuploadint < $maxpostint) {
					echo sprintf(gettext("The maximum size for any one file is <strong>%sB</strong> and the maximum size for one total upload is <strong>%sB</strong> which are set by your PHP configuration <code>upload_max_filesize</code> and <code>post_max_size</code>."), $maxupload, $maxpost);
				} else {
					echo ' ' . sprintf(gettext("The maximum size for your total upload is <strong>%sB</strong> which is set by your PHP configuration <code>post_max_size</code>."), $maxpost);
				}
				$uploadlimit = npgFilters::apply('get_upload_limit', $maxuploadint);
				$maxuploadint = min($maxuploadint, $uploadlimit);
				?>
				<br />
				<?php
				echo npgFilters::apply('get_upload_header_text', gettext('Don’t forget, you can also use <acronym title="File Transfer Protocol">FTP</acronym> to upload folders of images into the albums directory!'));
				?>
			</p>
			<?php
			if (isset($_GET['error'])) {
				$errormsg = sanitize($_GET['error']);
				?>
				<div class="errorbox fade-message">
					<h2><?php echo gettext("Upload Error"); ?></h2>
					<?php echo (empty($errormsg) ? gettext("There was an error submitting the form. Please try again.") : html_encode($errormsg)); ?>
				</div>
				<?php
			}
			if (isset($_GET['uploaded'])) {
				?>
				<div class="messagebox fade-message">
					<h2><?php echo gettext("Upload complete"); ?></h2>
					<?php echo gettext('Your files have been uploaded.'); ?>
				</div>
				<?php
			}
			$rootrights = npgFilters::apply('upload_root_ui', accessAllAlbums(UPLOAD_RIGHTS));
			if ($rootrights || !empty($albumlist)) {
				echo gettext("Upload to:");
				if (isset($_GET['new'])) {
					$checked = ' checked="checked"';
				} else {
					$checked = '';
				}
				?>
				<script type="text/javascript">
					// <!-- <![CDATA[
	<?php seoFriendlyJS(); ?>
					function buttonstate(good) {
						$('#albumtitleslot').val($('#albumtitle').val());
						$('#publishalbumslot').val($('#publishalbum').prop('checked'));
						if (good) {
							$('.fileUploadActions').show();
						} else {
							$('.fileUploadActions').hide();
						}
					}
					function publishCheck() {
						$('#publishalbumslot').val($('#publishalbum').prop('checked'));
					}
					function albumSelect() {
						var sel = document.getElementById('albumselectmenu');
						var selected = sel.options[sel.selectedIndex].value;
						$('#folderslot').val(selected);
						$('#go_to_album').show();
						var state = albumSwitch(sel, true, '<?php echo addslashes(gettext('That name is already used.')); ?>', '<?php echo addslashes(gettext('This upload has to have a folder. Type a title or folder name to continue...')); ?>');
						buttonstate(state);
					}
					// ]]> -->
				</script>
				<div id="albumselect">

					<form name="file_upload_datum" id="file_upload_datum" method="post" action="<?php echo $formAction; ?>" enctype="multipart/form-data" >

						<select id="albumselectmenu" name="albumselect" onchange="albumSelect()">
							<?php
							if ($rootrights) {
								?>
								<option value="" selected="selected" style="font-weight: bold;">/</option>
								<?php
							}
							$gotobuttonState = ' style="display:none"';
							$bglevels = array('#fff', '#f8f8f8', '#efefef', '#e8e8e8', '#dfdfdf', '#d8d8d8', '#cfcfcf', '#c8c8c8');
							if (isset($_GET['album'])) {
								$passedalbum = sanitize($_GET['album']);
								$gotobuttonState = '';
							} else {
								if ($rootrights) {
									$passedalbum = NULL;
								} else {
									reset($albumlist);
									$passedalbum = key($albumlist);
								}
							}
							foreach ($albumlist as $fullfolder => $albumtitle) {
								$singlefolder = $fullfolder;
								$saprefix = "";
								$salevel = 0;
								if (!is_null($passedalbum) && ($passedalbum == $fullfolder)) {
									$selected = " selected=\"selected\" ";
								} else {
									$selected = "";
								}
								// Get rid of the slashes in the subalbum, while also making a subalbum prefix for the menu.
								while (strstr($singlefolder, '/') !== false) {
									$singlefolder = substr(strstr($singlefolder, '/'), 1);
									$saprefix = "&nbsp; &nbsp;&raquo;&nbsp;" . $saprefix;
									$salevel++;
								}
								echo '<option value="' . $fullfolder . '"' . ($salevel > 0 ? ' style="background-color: ' . $bglevels[$salevel] . '; border-bottom: 1px dotted #ccc;"' : '')
								. "$selected>" . $saprefix . $singlefolder . " (" . $albumtitle . ')' . "</option>\n";
							}
							if (isset($_GET['publishalbum'])) {
								$publishchecked = ' checked="checked"';
							} else {
								if ($albpublish = $_gallery->getAlbumPublish()) {
									$publishchecked = ' checked="checked"';
								} else {
									$publishchecked = '';
								}
							}
							?>
						</select>
						<?php
						if (npg_loggedin(ALBUM_RIGHTS | MANAGE_ALL_ALBUM_RIGHTS)) {
							?>
							<input type="button" id="go_to_album"<?php echo $gotobuttonState; ?> onclick="launchScript('<?php echo getAdminLink('admin-tabs/edit.php'); ?>', ['page=edit', 'tab=imageinfo', 'album=' + encodeURIComponent($('#albumselectmenu').val()), 'uploaded=1', 'albumimagesort=id_desc']);" value="<?php echo gettext('Go to album'); ?>"></button>
							<?php
						}
						?>


						<?php
						if (empty($passedalbum)) {
							$modified_rights = MANAGED_OBJECT_RIGHTS_EDIT;
						} else {
							$rightsalbum = $rightsalbum = newAlbum($passedalbum);
							$modified_rights = $rightsalbum->subRights();
						}
						if ($modified_rights & MANAGED_OBJECT_RIGHTS_EDIT) { //	he has edit rights, allow new album creation
							$display = '';
						} else {
							$display = ' display:none;';
						}
						?>
						<div id="newalbumbox" style="margin-top: 5px;<?php echo $display; ?>">
							<div>
								<input type="checkbox" name="newalbum" id="newalbumcheckbox"<?php echo $checked; ?> onclick="albumSwitch(this.form.albumselect, false, '<?php echo addslashes(gettext('That name is already used.')); ?>', '<?php echo addslashes(gettext('This upload has to have a folder. Type a title or folder name to continue...')); ?>')" />
								<label for="newalbumcheckbox">
									<?php echo gettext("Make a new Album"); ?>
								</label>
							</div>
							<div id="publishtext"><?php echo gettext("and"); ?>
								<input type="checkbox" name="publishalbum" id="publishalbum" value="true" <?php echo $publishchecked; ?> onchange="publishCheck();" />
								<label for="publishalbum">
									<?php echo gettext("Publish the album so everyone can see it."); ?>
								</label>
							</div>
						</div>
						<div id="albumtext" style="margin-top: 5px;<?php echo $display; ?>">
							<?php echo gettext("titled:"); ?>
							<input type="text" name="albumtitle" id="albumtitle" size="42"
										 onkeyup="buttonstate(updateFolder(this, 'folderdisplay', 'autogen', '<?php echo addslashes(gettext('That name is already used.')); ?>', '<?php echo addslashes(gettext('This upload has to have a folder. Type a title or folder name to continue...')); ?>'));" />

							<div style="position: relative; margin-top: 4px;">
								<?php echo gettext("with the folder name:"); ?>
								<div id="foldererror" style="display: none; color: #D66; position: absolute; z-index: 100; top: 2.5em; left: 0px;"></div>
								<input type="text" name="folderdisplay" disabled="disabled" id="folderdisplay" size="18"
											 onkeyup="buttonstate(validateFolder(this, '<?php echo addslashes(gettext('That name is already used.')); ?>', '<?php echo addslashes(gettext('This upload has to have a folder. Type a title or folder name to continue...')); ?>'));" />
								<input type="checkbox" name="autogenfolder" id="autogen" checked="checked"
											 onclick="buttonstate(toggleAutogen('folderdisplay', 'albumtitle', this));" />
								<label for="autogen">
									<?php echo gettext("Auto-generate"); ?>
								</label>
								<br />
								<br />
							</div>
						</div>
						<hr />
						<?php upload_form($uploadlimit, $passedalbum); ?>
					</form>
					<div id="upload_action">
						<?php
						//	load the uploader specific form stuff
						upload_extra($uploadlimit, $passedalbum);
						?>
					</div><!-- upload action -->

					<script type="text/javascript">
						//<!-- <![CDATA[
	<?php
	echo npgFilters::apply('upload_helper_js', '') . "\n";
	if ($passedalbum) {
		?>
							buttonstate(true);
							$('#folderdisplay').val('<?php echo html_encode($passedalbum); ?>');
		<?php
	}
	?>
						albumSwitch(document.getElementById('albumselectmenu'), false, '<?php echo addslashes(gettext('That name is already used.')); ?>', '<?php echo addslashes(gettext('This upload has to have a folder. Type a title or folder name to continue...')); ?>');
	<?php
	if (isset($_GET['folderdisplay'])) {
		?>
							$('#folderdisplay').val('<?php echo html_encode(sanitize($_GET['folderdisplay'])); ?>');
		<?php
	}
	if (isset($_GET['albumtitle'])) {
		?>
							$('#albumtitle').val('<?php echo html_encode(sanitize($_GET['albumtitle'])); ?>');
		<?php
	}
	if (isset($_GET['autogen']) && !$_GET['autogen']) {
		?>
							$('#autogen').prop('checked', false);
							$('#folderdisplay').prop('disabled', false);
							if ($('#folderdisplay').val() != '') {
								$('#foldererror').hide();
							}
		<?php
	} else {
		?>
							$('#autogen').checked;
							$('#folderdisplay').prop('disabled', true);
							if ($('#albumtitle').val() != '') {
								$('#foldererror').hide();
							}
		<?php
	}
	?>
						buttonstate($('#folderdisplay').val() != '');
						// ]]> -->
					</script>
					<?php
				} else {
					echo gettext("There are no albums to which you can upload.");
				}
				?>
			</div><!-- albumselect -->

		</div><!-- tabbox -->
	</div><!-- content -->
	<?php printAdminFooter(); ?>
</div><!-- main -->
</body>
</html>




