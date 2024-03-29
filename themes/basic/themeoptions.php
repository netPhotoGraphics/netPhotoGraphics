<?php

// force UTF-8 Ø

/* Plug-in for theme option handling
 * The Admin Options page tests for the presence of this file in a theme folder
 * If it is present it is linked to with a require_once call.
 * If it is not present, no theme options are displayed.
 *
 */

require_once(__DIR__ . '/functions.php');

class ThemeOptions {

	function __construct() {
		$me = basename(__DIR__);
		setThemeOptionDefault('Allow_search', true);
		setThemeOptionDefault('Theme_colors', 'light');
		setThemeOptionDefault('albums_per_page', 6);
		setThemeOptionDefault('images_per_page', 20);
		setThemeOptionDefault('images_per_row', 5);
		setThemeOptionDefault('image_size', 595);
		setThemeOptionDefault('image_use_side', 'longest');
		setThemeOptionDefault('thumb_size', 100);
		setThemeOptionDefault('thumb_crop_width', 100);
		setThemeOptionDefault('thumb_crop_height', 100);
		setThemeOptionDefault('thumb_crop', 1);
		setThemeOptionDefault('thumb_transition', true);

		if (class_exists('cacheManager')) {
			$me = basename(__DIR__);
			cacheManager::deleteCacheSizes($me);
			cacheManager::addCacheSize($me, getThemeOption('image_size'), NULL, NULL, NULL, NULL, NULL, NULL, false, NULL, NULL, NULL);
			cacheManager::addCacheSize($me, getThemeOption('thumb_size'), NULL, NULL, NULL, NULL, NULL, NULL, true, NULL, NULL, NULL);
		}
	}

	function getOptionsSupported() {
		return array(
				gettext('Allow search') => array(
						'key' => 'Allow_search',
						'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Check to enable search form.')
				),
				gettext('Theme colors') => array(
						'key' => 'Theme_colors',
						'type' => OPTION_TYPE_CUSTOM,
						'desc' => gettext('Select the colors of the theme')
				)
		);
	}

	function getOptionsDisabled() {
		return array();
	}

	function handleOption($option, $currentValue) {
		global $themecolors;
		if ($option == 'Theme_colors') {
			echo '<select id="EF_themeselect_colors" name="' . $option . '"' . ">\n";
			generateListFromArray(array($currentValue), $themecolors, false, false);
			echo "</select>\n";
		}
	}

}

?>