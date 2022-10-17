<?php

/**
 * @package plugins/uploader_http
 */
function upload_head() {
	$myfolder = PLUGIN_SERVERPATH . 'uploader_http';
	scriptLoader($myfolder . '/httpupload.css');
	scriptLoader($myfolder . '/httpupload.js');
	return getAdminLink(PLUGIN_FOLDER . '/uploader_http/uploader.php');
}

function upload_extra($uploadlimit, $passedalbum) {

}

function upload_form($uploadlimit, $passedalbum) {
	global $_current_admin_obj;

	XSRFToken('upload');
	?>
	<script type="text/javascript">
		// <!-- <![CDATA[
		window.totalinputs = 5;
		function addUploadBoxes(num) {
			for (i = 0; i < num; i++) {
				jQuery('#uploadboxes').append('<div class="fileuploadbox"><input type="file" size="40" name="files[]" /></div>');
				window.totalinputs++;
				if (window.totalinputs >= 50) {
					jQuery('#addUploadBoxes').toggle('show');
					return;
				}
			}
		}
		function resetBoxes() {
			$('#uploadboxes').empty();
			window.totalinputs = 0;
			addUploadBoxes(5);
		}
		// ]]> -->
	</script>

	<input type="hidden" name="existingfolder" id="existingfolder" value="false" />
	<input type="hidden" name="auth" id="auth" value="<?php echo $_current_admin_obj->getPass(); ?>" />
	<input type="hidden" name="id" id="id" value="<?php echo $_current_admin_obj->getID(); ?>" />
	<input type="hidden" name="processed" id="processed" value="1" />
	<input type="hidden" name="folder" id="folderslot" value="<?php echo html_encode($passedalbum); ?>" />
	<input type="hidden" name="albumtitle" id="albumtitleslot" value="" />
	<input type="hidden" name="publishalbum" id="publishalbumslot" value="" />
	<div id="uploadboxes">
		<div class="fileuploadbox"><input type="file" size="40" name="files[]" /></div>
		<div class="fileuploadbox"><input type="file" size="40" name="files[]" /></div>
		<div class="fileuploadbox"><input type="file" size="40" name="files[]" /></div>
		<div class="fileuploadbox"><input type="file" size="40" name="files[]" /></div>
		<div class="fileuploadbox"><input type="file" size="40" name="files[]" /></div>
	</div>

	<p id="addUploadBoxes"><a href="javascript:addUploadBoxes(5)" title="<?php echo gettext("Does not reload!"); ?>">+ <?php echo gettext("Add more upload boxes"); ?></a> <small>
			<?php echo gettext("(will not reload the page, but remember your upload limits!)"); ?></small></p>

	<p class="fileUploadActions buttons" style="display: none;">
		<?php
		applyButton(array('buttonText' => CHECKMARK_GREEN . ' ' . gettext('Upload'), 'buttonClick' => "$('#folderslot').val($('#folderdisplay').val());"));
		npgButton("button", CROSS_MARK_RED . ' ' . gettext('Cancel'), array('buttonClick' => "resetBoxes();"));
		?>
	</p>
	<br class="clearall" />
	<?php
}
?>
