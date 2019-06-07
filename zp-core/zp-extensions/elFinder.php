<?php
/**
 *
 *
 * The Admin <var>upload/files</var> tab and the <i>TinyMCE</i> file browser (if configured) use
 * a plugin to supply file handling and uploading.
 * This plugin supplies file handling using <i>elFinder</i> by {@link http://elfinder.org/ Studio-42 }.
 *
 * <hr>
 * <img src="%WEBPATH%/%CORE_FOLDER%/%PLUGIN_FOLDER%/elFInder/elfinder-logo.png" />
 * "<i>elFinder</i> is a file manager for web similar to that you use on your computer. Written in JavaScript
 * using jQuery UI, it just work's in any modern browser. Its creation is inspired by simplicity and
 * convenience of Finder.app program used in Mac OS X."
 *
 * elFinder uses UNIX command line utils <var>zip</var>, <var>unzip</var>, <var>rar</var>, <var>unrar</var>, <var>tar</var>,
 * <var>gzip</var>, <var>bzip2</var>, and <var>7za</var> for archives support,
 * on windows you need to have full {@link http://www.cygwin.com/ cygwin} support in your webserver environment.
 *
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/elFinder
 * @pluginCategory admin
 */
$plugin_is_filter = defaultExtension(50 | ADMIN_PLUGIN);
$plugin_description = gettext('Provides file handling for the <code>upload/files</code> tab and the <em>TinyMCE</em> file browser.');

$option_interface = 'elFinder_options';

/**
 * Option handler class
 *
 */
class elFinder_options {

	/**
	 * class instantiation function
	 *
	 */
	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('elFinder_files', 1);
			setOptionDefault('elFinder_themeeditor', 1);
			setOptionDefault('elFinder_tinymce', 0);
		}
	}

	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		$options = array(gettext('Files tab') => array('key' => 'elFinder_files', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Use as the upload <em>files</em> subtab.')),
				gettext('Edit themes') => array('key' => 'elFinder_themeeditor', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Enable elFinder for editing themes.')),
				gettext('TinyMCE plugin') => array('key' => 'elFinder_tinymce', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Enable plugin for TinyMCE.'))
		);
		return $options;
	}

	function handleOption($option, $currentValue) {

	}

}

if (getOption('elFinder_files') && npg_loggedin(FILES_RIGHTS | UPLOAD_RIGHTS)) {
	npgFilters::register('admin_tabs', 'elFinder_admin_tabs');
	if (getOption('elFinder_themeeditor')) {
		npgFilters::register('theme_editor', 'elFinderThemeEdit');
	}
}
if (getOption('elFinder_tinymce')) {
	npgFilters::register('tinymce_config', 'elFinder_tinymce');
}

function elFinder_admin_tabs($tabs) {
	if (npg_loggedin(UPLOAD_RIGHTS)) {
		$me = sprintf(gettext('files (%s)'), 'elFinder');
		if (is_null($tabs['upload'])) {
			$tabs['upload'] = array('text' => gettext("upload"),
					'link' => getAdminLink(PLUGIN_FOLDER . '/' . 'elFinder/filemanager.php') . '?page=upload&tab=elFinder&type=' . gettext('files'),
					'subtabs' => NULL,
					'default' => 'elFinder'
			);
		}
		$tabs['upload']['subtabs'][$me] = PLUGIN_FOLDER . '/' . 'elFinder/filemanager.php?page=upload&tab=elFinder&type=' . gettext('files');
		;
	}
	return $tabs;
}

function elFinder_tinymce($discard) {
	global $MCEspecial;

	$file = FULLWEBPATH . '/' . CORE_FOLDER . '/' . PLUGIN_FOLDER . '/elFinder/connector.mce.php?XSRFToken=' . getXSRFToken('elFinder');
	$MCEspecial ['file_picker_callback'] = 'elFinderBrowser';
	?>
	<script type="text/javascript">
		// <!-- <![CDATA[
		function elFinderBrowser(callback, value, meta) {
			var windowManagerURL = '<?php echo $file; ?>&type=' + meta.type,
							windowManagerCSS = '<style type="text/css">' +
							'.tox-dialog {max-width: 100%!important; width:900px!important; overflow: hidden; height:500px!important; bborder-radius:0.25em;}' +
							'.tox-dialog__header{ border-bottom: 1px solid lightgray!important; }' + // for custom header in filemanage
							'.tox-dialog__footer { display: none!important; }' + // for custom footer in filemanage
							'.tox-dialog__body { padding: 5!important; }' +
							'.tox-dialog__body-content > div { height: 100%; overflow:hidden}' +
							'</style > ';
			window.tinymceCallBackURL = '';
			window.tinymceWindowManager = tinymce.activeEditor.windowManager;
			tinymceWindowManager.open({
				title: 'elFinder',
				body: {
					type: 'panel',
					items: [{
							type: 'htmlpanel',
							html: windowManagerCSS + '<iframe src="' + windowManagerURL + '"  frameborder="0" style="width:100%; height:100%"></iframe>'
						}]
				},
				buttons: [],
				onClose: function () {
					//to set selected file path
					if (tinymceCallBackURL != '') {
						if (meta.filetype == 'image') {
							callback(tinymceCallBackURL, {alt: tinymceCallBackInfo});
						} else {
							callback(tinymceCallBackURL, {});
						}
					}
				}


			}
			);
			return false;
		}
		// ]]> -->
	</script>

	<?php
}

function elFinderThemeEdit($html, $theme) {
	$html = "launchScript('" . PLUGIN_FOLDER . "/elFinder/filemanager.php', [
													'page=upload',
													'tab=elFinder',
													'type=files',
													'themeEdit=" . urlencode($theme) . "'
												]);";
	return $html;
}
?>