<?php

/**
 * Changes <i>white space</i> characters to hyphens. Bypasses the standard replacement of
 * non-ascii characaters with a hyphen
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/seo_null
 * @pluginCategory seo
 */
$plugin_is_filter = 5 | ADMIN_PLUGIN;
$plugin_description = gettext('SEO <em>Null</em> filter.');
$plugin_notice = gettext('The only translation performed is one or more <em>white space</em> characters are converted to a <em>hyphen</em>.');
$plugin_disable = npgFilters::has_filter('seoFriendly') && !extensionEnabled('seo_null') ? sprintf(gettext('Only one SEO filter plugin can be enabled. <em>%1$s</em> is already enabled.'), stripSuffix(npgFilters::script('seoFriendly'))) : '';

if ($plugin_disable) {
	enableExtension('seo_null', 0);
} else {
	npgFilters::register('seoFriendly', 'null_seo::filter');
	npgFilters::register('seoFriendly_js', 'null_seo::js');
}

/**
 * Option handler class
 *
 */
class null_seo {

	/**
	 * class instantiation function
	 *
	 */
	function __construct() {

	}

	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {

	}

	function handleOption($option, $currentValue) {

	}

	/**
	 * The presence of these filter functions suppresses the netPhotoGraphics standard
	 * SEO translations. Only the space to hyphen processing is done (by the invoking
	 * functions.)
	 *
	 * @param string $string
	 * @return string
	 */
	static function filter($string) {
		return $string;
	}

	static function js($string) {
		return $string;
	}

}

?>