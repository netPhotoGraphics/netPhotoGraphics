<?php

/*
 * This plugin will force update objects in the database such that any hard-coded
 * URLs to the site are converted to portable URLs.
 *
 * Caution, this process will update the objects last change date!
 *
 * Note, only fields where these
 * urls are expected are migrated!
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/tagURLs
 * @pluginCategory admin
 *
 * @Copyright 2020 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 */

// force UTF-8 Ø

$plugin_is_filter = defaultExtension(5 | ADMIN_PLUGIN);
$plugin_description = gettext("Convert Explicit site URLs to portable URLs.");


npgFilters::register('admin_utilities_buttons', 'tagURLs::buttons');

class tagURLs {

	static function buttons($buttons) {
		$buttons[] = array(
				'category' => gettext('Database'),
				'enable' => true,
				'button_text' => gettext('Tag Explicit URLs'),
				'formname' => 'tagurlsbutton',
				'action' => getAdminLink(USER_PLUGIN_FOLDER . '/tagURLs/migrateURLs.php'),
				'icon' => BADGE_BLUE,
				'title' => gettext('A utility to change explicit site urls in netPhotoGraphics objects to portable urls.'),
				'alt' => '',
				'rights' => ADMIN_RIGHTS,
				'XSRFTag' => 'tagURLs'
		);
		return $buttons;
	}

}
