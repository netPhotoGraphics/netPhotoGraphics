<?php

/**
 * @package plugins/uploader_jQuery
 */
function upload_head() {
	?>
	<!-- Force latest IE rendering engine or ChromeFrame if installed -->
	<!--[if IE]>
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<![endif]-->

	<script type="text/javascript">
		var extension_link = '<?php echo getAdminLink(PLUGIN_FOLDER . '/uploader_jQuery/server/php/index.php'); ?>';
	</script>

	<?php
	scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/css/uploader_bootstrap.css');
	scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/css/blueimp-gallery.min.css');
	scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/css/jquery.fileupload.css');
	scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/css/jquery.fileupload-ui.css');

	return getAdminLink(PLUGIN_FOLDER . '/uploader_jQuery/uploader.php');
}

function upload_extra($uploadlimit, $passedalbum) {
	global $_current_admin_obj;
	?>

	<div>
		<!-- The file upload form used as target for the file upload widget -->
		<form id="fileupload" action="<?php echo getAdminLink(PLUGIN_FOLDER . '/uploader_jQuery/server/php/index.php'); ?>" method="POST" enctype="multipart/form-data">

			<noscript><?php echo gettext('This uploader requires browser javaScript support.'); ?></noscript>

			<!-- netPhotoGraphics needed parameters -->
			<input type="hidden" name="existingfolder" id="existingfolder" value="false" />
			<input type="hidden" name="auth" id="auth" value="<?php echo $_current_admin_obj->getPass(); ?>" />
			<input type="hidden" name="id" id="id" value="<?php echo $_current_admin_obj->getID(); ?>" />
			<input type="hidden" name="folder" id="folderslot" value="<?php echo html_encode($passedalbum); ?>" />
			<input type="hidden" name="albumtitle" id="albumtitleslot" value="" />
			<input type="hidden" name="publishalbum" id="publishalbumslot" value="" />

			<!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
			<div class="row fileupload-buttonbar">
				<div class="col-lg-7">
					<span class="fileUploadActions">
						<!-- The fileinput-button span is used to style the file input field as button -->
						<span class="btn btn-success fileinput-button">
							<i class="glyphicon glyphicon-plus"></i>
							<span><?php echo gettext('Add files...'); ?></span>
							<input type="file" name="files[]" multiple>
						</span>

						<button class="buttons" type="submit" class="btn btn-primary start">
							<i class="glyphicon glyphicon-upload"></i>
							<span><?php echo gettext('Start upload'); ?></span>
						</button>
						<button class="buttons" type="reset" class="btn btn-warning cancel">
							<i class="glyphicon glyphicon-ban-circle"></i>
							<span><?php echo gettext('Cancel upload'); ?></span>
						</button>
						<!--
						<button class="buttons" type="button" class="btn btn-danger delete">
							<i class="glyphicon glyphicon-trash"></i>
							<span><?php echo gettext('Delete'); ?></span>
						</button>
						<input type="checkbox" class="toggle">
						-->
					</span>
					<!-- The global file processing state -->
					<span class="fileupload-process"></span>
				</div>
				<!-- The global progress state -->
				<div class="col-lg-5 fileupload-progress fade">
					<!-- The global progress bar -->
					<div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
						<div class="progress-bar progress-bar-success" style="width:0%;"></div>
					</div>
					<!-- The extended global progress state -->
					<div class="progress-extended">&nbsp;</div>
				</div>
			</div>
			<!-- The table listing the files available for upload/download -->
			<table role="presentation" class="table table-striped"><tbody class="files"></tbody></table>
		</form>

	</div>
	<!-- The blueimp Gallery widget -->
	<div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls" data-filter=":even">
		<div class="slides"></div>
		<h3 class="title"></h3>
		<a class="prev">‹</a>
		<a class="next">›</a>
		<a class="close">×</a>
		<a class="play-pause"></a>
		<ol class="indicator"></ol>
	</div>
	<!-- The template to display files available for upload -->
	<script id="template-upload" type="text/x-tmpl">
		{% for (var i=0, file; file=o.files[i]; i++) { %}
		<tr class="template-upload fade">
		<td>
		<span class="preview"></span>
		</td>
		<td>
		<p class="name">{%=file.name%}</p>
		<strong class="error text-danger"></strong>
		</td>
		<td>
		<p class="size"><?php echo gettext('Processing...'); ?></p>
		<div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar progress-bar-success" style="width:0%;"></div></div>
		</td>
		<td>
		{% if (!i && !o.options.autoUpload) { %}
		<button class="btn btn-primary start" disabled>
		<i class="glyphicon glyphicon-upload"></i>
		<span>Start</span>
		</button>
		{% } %}
		{% if (!i) { %}
		<button class="btn btn-warning cancel">
		<i class="glyphicon glyphicon-ban-circle"></i>
		<span><?php echo gettext('Cancel'); ?></span>
		</button>
		{% } %}
		</td>
		</tr>
		{% } %}
	</script>
	<!-- The template to display files available for download -->
	<script id="template-download" type="text/x-tmpl">
		{% for (var i=0, file; file=o.files[i]; i++) { %}
		<tr class="template-download fade">
		<td>
		<span class="preview">
		{% if (file.thumbnailUrl) { %}
		<a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>
		{% } %}
		</span>
		</td>
		<td>
		<p class="name">
		{% if (file.url) { %}
		<a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?'data-gallery':''%}>{%=file.name%}</a>
		{% } else { %}
		<span>{%=file.name%}</span>
		{% } %}
		</p>
		{% if (file.error) { %}
		<div><span class="label label-danger"><?php echo gettext('Error'); ?></span> {%=file.error%}</div>
		{% } %}
		</td>
		<td>
		<span class="size">{%=o.formatFileSize(file.size)%}</span>
		</td>
		<td>
		{% if (file.deleteUrl) { %}
		<button class="btn btn-danger delete" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %}>
		<!--
		<i class="glyphicon glyphicon-trash"></i>
		<span><?php echo gettext('Delete'); ?></span>
		</button>
		<input type="checkbox" name="delete" value="1" class="toggle">
		-->
		{% } else { %}
		<button class="btn btn-warning cancel">
		<i class="glyphicon glyphicon-ban-circle"></i>
		<span><?php echo gettext('Cancel'); ?></span>
		</button>
		{% } %}
		</td>
		</tr>
		{% } %}
	</script>
	<?php
	scriptLoader(PLUGIN_SERVERPATH . 'common/bootstrap/bootstrap.min.js');
	scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/js/tmpl.min.js');
	scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/js/load-image.all.min.js');
	scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/js/canvas-to-blob.min.js');
	scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/js/jquery.blueimp-gallery.min.js');
	scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/js/jquery.iframe-transport.js');
	scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/js/jquery.fileupload.js');
	scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/js/jquery.fileupload-process.js');
	scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/js/jquery.fileupload-image.js');
	scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/js/jquery.fileupload-audio.js');
	scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/js/jquery.fileupload-video.js');
	scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/js/jquery.fileupload-validate.js');
	scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/js/jquery.fileupload-ui.js');
	//NOTE: has some self relative references, so cannot be served inline
	scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/js/main.js', false);
	?>
	<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
	<!--[if (gte IE 8)&(lt IE 10)]>
	<?php scriptLoader(PLUGIN_SERVERPATH . 'uploader_jQuery/js/cors/jquery.xdr-transport.js'); ?>
	<![endif]-->

	<?php
}

function upload_form($uploadlimit, $passedalbum) {

}
?>