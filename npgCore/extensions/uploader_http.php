<?php

/**
 *
 * This plugin provides an HTTP based image upload handler for the <i>upload/images</i> admin tab.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/uploader_http
 * @pluginCategory admin
 *
 */
$plugin_is_filter = defaultExtension(30 | ADMIN_PLUGIN);
$plugin_description = gettext('<em>http</em> image upload handler.');

npgFilters::register('admin_tabs', 'httpUploadHandler_admin_tabs');
if (npg_loggedin(UPLOAD_RIGHTS)) {
	npgFilters::register('upload_handlers', 'httpUploadHandler');
}

function httpUploadHandler($uploadHandlers) {
	$uploadHandlers['http'] = CORE_SERVERPATH .  PLUGIN_FOLDER . '/uploader_http';
	return $uploadHandlers;
}

function httpUploadHandler_admin_tabs($tabs) {
	if (npg_loggedin(UPLOAD_RIGHTS)) {
		$me = sprintf(gettext('images (%s)'), 'http');
		if (is_null($tabs['upload'])) {
			$tabs['upload'] = array('text' => gettext("upload"),
					'link' => getAdminLink('admin-tabs/upload.php') . '?page=upload&tab=http&type=' . gettext('images'),
					'subtabs' => NULL,
					'default' => 'http'
			);
		}
		$tabs['upload']['subtabs'][$me] = 'admin-tabs/upload.php?page=upload&tab=http&type=' . gettext('images');
		;
	}
	return $tabs;
}

?>