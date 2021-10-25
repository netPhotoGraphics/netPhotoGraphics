<?php

// force UTF-8 Ø

/* Plug-in for theme option handling
 * The Admin Options page tests for the presence of this file in a theme folder
 * If it is present it is linked to with a require_once call.
 * If it is not present, no theme options are displayed.
 *
 */

class ThemeOptions {

	function __construct() {
		$me = basename(__DIR__);
		setThemeOptionDefault('Allow_search', true);
		setThemeOptionDefault('thumb_transition', true);
		setThemeOption('thumb_size', 79, NULL);
		setThemeOptionDefault('thumb_crop_width', 79);
		setThemeOptionDefault('thumb_crop_height', 79);
		setThemeOptionDefault('thumb_crop', 1);
		setThemeOptionDefault('albums_per_page', 6);
		setThemeOptionDefault('images_per_page', 24);

		if (class_exists('cacheManager')) {
			$me = basename(__DIR__);
			cacheManager::deleteCacheSizes($me);
			cacheManager::addCacheSize($me, NULL, 79, 79, 79, 79, NULL, NULL, true, NULL, NULL, NULL);
		}
	}

	function getOptionsSupported() {
		return array(
				gettext('Allow search') => array(
						'key' => 'Allow_search',
						'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Check to enable search form.')
				),
				gettext('Allow direct link from multimedia') => array(
						'key' => 'zpmobile_mediadirectlink',
						'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Check to enable a direct link to multimedia items on the single image page in case the player is not supported by the device but the actual format is.')
				)
		);
	}

	function getOptionsDisabled() {
		return array('image_size', 'thumb_size');
	}

	function handleOption($option, $currentValue) {

	}

}

?>