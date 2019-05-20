<?php

/**
 * Loads Colorbox JS and CSS scripts for selected theme page scripts.
 *
 * Note that this plugin does not attach Colorbox to any element because there are so many different options and usages.
 * You need to do this in your theme yourself. Visit the {@link http://www.jacklmoore.com/colorbox/ colorbox} site for information.
 *
 * The plugin has built in support for 5 example Colorbox themes shown below:
 *
 * 		<img src="%WEBPATH%/%ZENFOLDER%/%PLUGIN_FOLDER%/colorbox_js/themes/example1.jpg" />
 * 		<img src="%WEBPATH%/%ZENFOLDER%/%PLUGIN_FOLDER%/colorbox_js/themes/example2.jpg" />
 * 		<img src="%WEBPATH%/%ZENFOLDER%/%PLUGIN_FOLDER%/colorbox_js/themes/example3.jpg" />
 * 		<img src="%WEBPATH%/%ZENFOLDER%/%PLUGIN_FOLDER%/colorbox_js/themes/example4.jpg" />
 * 		<img src="%WEBPATH%/%ZENFOLDER%/%PLUGIN_FOLDER%/colorbox_js/themes/example5.jpg" />
 *
 * If you select <i>custom (within theme)</i> on the plugin option for Colorbox you need to place a folder
 * <i>colorbox_js</i> containing a <i>colorbox.css</i> file and a folder <i>images</i> within the current theme
 * to use a custom Colorbox theme.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/colorbox_js
 * @pluginCategory media
 */
$plugin_is_filter = 800 | THEME_PLUGIN;
$plugin_description = gettext('Loads Colorbox JS and CSS scripts for selected theme page scripts.');
$plugin_notice = gettext('Note that this plugin does not attach Colorbox to any element. You need to do this on your theme yourself.');
$option_interface = 'colorbox';

if (OFFSET_PATH) {
	zp_register_filter('admin_head', 'colorbox::js');
	zp_register_filter('admin_head', 'colorbox::css');
} else {
	zp_register_filter('theme_body_close', 'colorbox::js');
	zp_register_filter('theme_head', 'colorbox::css'); //	things don't work right if this is in the body close
}

class colorbox {

	function __construct() {
		if (OFFSET_PATH == 2) {
			$result = getOptionsLike('colorbox_');
			unset($result['colorbox_theme']);
			foreach ($result as $option => $value) {
				purgeOption('colorbox_' . $option);
			}
			setOptionDefault('colorbox_theme', 'example1');
		}
	}

	function getOptionsSupported() {
		global $_zp_gallery;
		$themes = getPluginFiles('colorbox_js/themes/*.*');
		$list = array('Custom (theme based)' => 'custom');
		foreach ($themes as $theme) {
			$theme = stripSuffix(basename($theme));
			$list[ucfirst($theme)] = $theme;
		}

		return array(gettext('Colorbox theme') => array('key' => 'colorbox_theme', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 0,
						'selections' => $list,
						'desc' => gettext("The Colorbox script comes with 5 example themes you can select here. If you select <em>custom (within theme)</em> you need to place a folder <em>colorbox_js</em> containing a <em>colorbox.css</em> file and a folder <em>images</em> within the current theme to override to use a custom Colorbox theme."))
		);
	}

	function handleOption($option, $currentValue) {

	}

	/**
	 * Use by themes to declare which scripts should have the colorbox CSS loaded
	 *
	 * @param string $theme
	 * @param array $scripts list of the scripts
	 * @deprecated since version 1.9
	 */
	static function registerScripts($scripts, $theme = NULL) {
		require_once(SERVERPATH . '/' . ZENFOLDER . '/' . PLUGIN_FOLDER . '/deprecated-functions.php');
		deprecated_functions::notify('registerScripts() is no longer used. You may delete the calls.');
	}

	/**
	 * Checks if the theme script is registered for colorbox. If not it will register the script
	 * so next time things will workl
	 *
	 * @global type $_zp_gallery
	 * @global type $_zp_gallery_page
	 * @param string $theme
	 * @param string $script
	 * @return boolean true registered
	 * @deprecated since version 1.9
	 */
	static function scriptEnabled($theme, $script) {
		require_once(SERVERPATH . '/' . ZENFOLDER . '/' . PLUGIN_FOLDER . '/deprecated-functions.php');
		deprecated_functions::notify('scriptEnabled() is no longer used. You may delete the calls.');
		return true;
	}

	static function css() {
		global $_zp_gallery;
		$inTheme = false;
		if (OFFSET_PATH) {
			$themepath = 'colorbox_js/themes/example4/colorbox.css';
		} else {
			$theme = getOption('colorbox_theme');
			if (empty($theme)) {
				$themepath = 'colorbox_js/themes/example4/colorbox.css';
			} else {
				if ($theme == 'custom') {
					$themepath = zp_apply_filter('colorbox_themepath', 'colorbox_js/colorbox.css');
				} else {
					$themepath = 'colorbox_js/themes/' . $theme . '/colorbox.css';
				}
				$inTheme = $_zp_gallery->getCurrentTheme();
			}
		}
		scriptLoader(getPlugin($themepath, $inTheme));
	}

	static function js() {
		scriptLoader(SERVERPATH . '/' . ZENFOLDER . '/' . PLUGIN_FOLDER . '/colorbox_js/jquery.colorbox-min.js');
		scriptLoader(SERVERPATH . '/' . ZENFOLDER . '/' . PLUGIN_FOLDER . '/colorbox_js/functions.js');
	}

}

?>