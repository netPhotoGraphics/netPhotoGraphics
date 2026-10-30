<?php

/**
 * Quick captions: fast, keyboard friendly captioning of photos.
 *
 * Adds <i>Quick captions</i> to the <em>admin</em> tab. It shows one photo at a time that still has no
 * title (or no description), you type the text, press Enter and the next photo appears. Meant for
 * galleries with thousands of scanned photos that wait for captions.
 *
 * Only the title and description of the shown photo are written (and the "last changed" stamp),
 * nothing else - the image files are not opened, so the dates of the photos stay untouched.
 *
 * @author Martin Kroul with Claude (AI assistant by Anthropic)
 * @license GPL 2 or later, like netPhotoGraphics
 *
 * @package plugins/quick_caption
 * @pluginCategory admin
 */
$plugin_is_filter = 5 | ADMIN_PLUGIN;
$plugin_description = gettext('Fast captioning: one photo at a time, type the title and description, press Enter, the next photo appears.');
$plugin_version = '1.0';
$option_interface = 'quick_caption';

npgFilters::register('admin_tabs', 'quick_caption::tabs');

class quick_caption {

	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('quick_caption_editor', 0);
			setOptionDefault('quick_caption_filename', 1);
		}
	}

	function getOptionsSupported() {
		return array(
				gettext('File name counts as missing title') => array('key' => 'quick_caption_filename', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('A title that is only the file name of the photo counts as missing (when looking for photos without a title). Switch it off if you use file names as titles on purpose.')),
				gettext('Visual editor for the description') => array('key' => 'quick_caption_editor', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Edit the description with the TinyMCE editor of the gallery (needs the tinymce plugin enabled and a configuration selected for "Text editor configuration - gallery"). The plain text field is faster for short texts.'))
		);
	}

	function handleOption($option, $currentValue) {

	}

	static function tabs($tabs) {
		if (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
			$tabs['admin']['subtabs'][gettext('Quick captions')] = PLUGIN_FOLDER . '/quick_caption/captions.php?page=admin&tab=captions';
		}
		return $tabs;
	}

}
