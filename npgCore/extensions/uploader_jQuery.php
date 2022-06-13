<?php

/**
 *
 * This plugin provides an image upload handler for the <i>upload/images</i> admin tab
 * based on the {@link https://github.com/blueimp/jQuery-File-Upload <i>jQuery File Upload Plugin</i>}
 * by Sebastian Tschan.
 *
 * PHP 5.3 or greater is required by the encorporated software.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/uploader_jQuery
 * @pluginCategory admin
 *
 * @deprecated since 2.00.15
 */
$plugin_is_filter = defaultExtension(40 | ADMIN_PLUGIN);
$plugin_description = gettext('<em>jQuery</em> image upload handler.');
$plugin_disable = (version_compare(PHP_VERSION, '5.3') >= 0) ? false : gettext('jQuery uploader requires PHP 5.3 or greater.');
$plugin_notice = gettext('This plugin will throw <em>deprecated</em> errors on PHP 8.1 and will cease working on a future version of PHP. The author has archived <em>blueimp/jQuery-File-Upload</em> upon which the plugin is based so no further updates are expected.');

if ($plugin_disable) {
	enableExtension('uploader_jQuery', 0);
} else {
	if (npg_loggedin(UPLOAD_RIGHTS)) {
		npgFilters::register('upload_handlers', 'jQueryUploadHandler');
	}
	npgFilters::register('admin_tabs', 'jQueryUploadHandler_admin_tabs');
}

function jQueryUploadHandler($uploadHandlers) {
	$uploadHandlers['jQuery'] = CORE_SERVERPATH . PLUGIN_FOLDER . '/uploader_jQuery';
	return $uploadHandlers;
}

function jQueryUploadHandler_admin_tabs($tabs) {
	if (npg_loggedin(UPLOAD_RIGHTS)) {
		$me = sprintf(gettext('images (%s)'), 'jQuery');
		if (is_null($tabs['upload'])) {
			$tabs['upload'] = array('text' => gettext("upload"),
					'link' => getAdminLink('admin-tabs/upload.php') . '?page=upload&tab=jQuery&type=' . gettext('images'),
					'subtabs' => NULL,
					'default' => 'jQuery'
			);
		}
		$tabs['upload']['subtabs'][$me] = 'admin-tabs/upload.php?page=upload&tab=jQuery&type=' . gettext('images');
	}
	return $tabs;
}

?>