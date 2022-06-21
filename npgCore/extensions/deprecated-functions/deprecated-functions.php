<?php

/**
 * General deprecated functions
 * @package plugins/deprecated-functions
 */
class internal_deprecations {
# all methods must be declared static
#
# example deprecated method
#	/**
#	 * @deprecated since 1.0.0
#	 */
#	static function PersistentObject() {
#		deprecated_functions::notify(gettext('Use the instantiate method instead'));
#	}
#
# example of method with deprecated parameters
#	/**
#	 * @deprecated since 1.0.0
#	 */
#	public static function next_album() {
#		deprecated_functions::notify(gettext('Sort parameter options should be set instead with the setSortType() and setSortDirection() object methods at the head of your script.'));
#	}

	/**
	 * @deprecated since 1.9.2
	 */
	static function getAuthor() {
		return $this->get('owner');
	}

	/**
	 * @deprecated since 1.9.2
	 */
	static function setAuthor($owner) {
		$this->set('owner', $owner);
	}

}

# For other deprecated functions simply move them here.
#
#
#/**
# * @deprecated since 1.0.0
# */
#function printCustomSizedImageMaxHeight($maxheight) {
#	deprecated_functions::notify(gettext('Use printCustomSizedImageMaxSpace().'));
#	if (getFullWidth() === getFullHeight() OR getDefaultHeight() > $maxheight) {
#		printCustomSizedImage(getImageTitle(), null, null, $maxheight, null, null, null, null, null, null);
#	} else {
#		printDefaultSizedImage(getImageTitle());
#	}
#}

/**
 * @deprecated since 1.0.0
 */
function printHeadTitle($separator = ' | ', $listparentalbums = true, $listparentpages = true) {
	deprecated_functions::notify(gettext('This feature is handled in the "theme_head" filter. For parameters set the theme options.'));
}

/**
 * @deprecated
 * @since 1.0.1
 */
function getAllTagsCount($language = NULL) {
	deprecated_functions::notify(gettext('Use getAllTagsUnique()'));
	return getAllTagsUnique($language, 1, true);
}

/**
 * @deprecated since 1.4.0
 */
function getAlbumCustomData() {
	global $_current_album;
	deprecated_functions::notify(gettext('Use customFieldExtender to define unique fields'));
	if (!is_null($_current_album)) {
		$data = $_current_album->getData();
		if (array_key_exists('customdata', $data)) {
			return $_current_album->getCustomData();
		}
	}
	return NULL;
}

/**
 * @deprecated since 1.4.0
 */
function printAlbumCustomData() {
	deprecated_functions::notify(gettext('Use customFieldExtender to define unique fields'));
	echo html_encodeTagged(getAlbumCustomData());
}

/**
 * @deprecated since 1.4.0
 */
function getImageCustomData() {
	global $_current_image;
	deprecated_functions::notify(gettext('Use customFieldExtender to define unique fields'));
	if (!is_null($_current_image)) {
		$data = $_current_image->getData();
		if (array_key_exists('customdata', $data)) {
			return $_current_image->getCustomData();
		}
	}
	return NULL;
}

/**
 * @deprecated since 1.4.0
 */
function printImageCustomData() {
	deprecated_functions::notify(gettext('Use customFieldExtender to define unique fields'));
	$data = getImageCustomData();
	$data = str_replace("\r\n", "\n", $data);
	$data = str_replace("\n", "<br />", $data);
	echo $data;
}

/**
 * @deprecated since 1.4.1
 */
function printSubtabs() {
	deprecated_functions::notify(gettext('Subtabs are no longer separate from tabs. If you need the current subtab use getCurrentTab() otherwise remove the call'));
	$current = getCurrentTab();
	return $current;
}

/**
 * @deprecated since 1.4.1
 */
function getSubtabs() {
	deprecated_functions::notify(gettext('Subtabs are no longer separate from tabs. If you need the current subtab use getCurrentTab() otherwise remove the call'));
	$current = getCurrentTab();
	return $current;
}

/**
 * @deprecated since 1.6.2
 */
function filterImageQuery($result, $source, $limit = 1, $photo = true) {
	deprecated_functions::notify(gettext('Use array_shift(filterImageQueryList())'));
	$list = filterImageQueryList($result, $source, $limit, $photo);
	if (!empty($list)) {
		return array_shift($list);
	}
	return NULL;
}

/**
 * @deprecated since 1.9.0
 */
function printZenphotoLink() {
	deprecated_functions::notify(gettext('Use print_SW_Link()'));
	print_SW_Link();
}

/**
 * @deprecated since version 1.8.1
 */
function exitZP() {
	deprecated_functions::notify(gettext('Use exit()'));
	exit();
}

/**
 * @deprecated since version 1.8.1
 */
function zp_error($error_msg, $error_type) {
	deprecated_functions::notify(gettext("triger_error($error_msg, $error_type)"));
	trigger_error($error_msg, $error_type);
}

/**
 * @deprecated since version 1.9.6
 */
function zp_register_filter($hook, $function_name, $priority = NULL) {
	deprecated_functions::notify(gettext('Use npgFilters::register()'));
	npgFilters::register($hook, $function_name, $priority);
}

/**
 * @deprecated since version 1.9.6
 */
function zp_apply_filter($hook, $value = NULL) {
	deprecated_functions::notify(gettext('Use npgFilters::apply()'));
//get the arguments for the $hook function call
	$args = array_slice(func_get_args(), 1); //	drop the $hook paremeter
	$args[0] = $value; //	if it was not passed
	array_unshift($args, $hook);
	return call_user_func_array('npgFilters::apply', $args);
}

/**
 * @deprecated since version 1.9.6
 */
function zp_remove_filter($hook, $function_to_remove, $priority = NULL, $accepted_args = 1) {
	deprecated_functions::notify(gettext('Use npgFilters::remove()'));
	return npgFilters::remove($hook, $function_to_remove, $priority, $accepted_args);
}

/**
 * @deprecated since version 1.9.6
 */
function zp_has_filter($hook, $function_to_check = false) {
	deprecated_functions::notify(gettext('Use npgFilters::has_filter()'));
	return npgFilters::has_filter($hook, $function_to_check);
}

/**
 * @deprecated since version 1.9.6
 */
function getSiteHomeURL() {
	deprecated_functions::notify(gettext('Use getGalleryIndexURL()'));
	return getGalleryIndexURL();
}

/**
 * @deprecated since version 1.9.6
 */
function getDataUsageNotice() {
	deprecated_functions::notify(gettext('Use the GDPR_required plugin'));
	return array();
}

/**
 * @deprecated since version 1.9.6
 */
function zp_loggedin($rights = ALL_RIGHTS) {
	deprecated_functions::notify(gettext('Use npg_loggedin()'));
	return npg_loggedin($rights);
}

/**
 * @deprecated since version 1.9.6
 */
function zp_setCookie($name, $value, $time = NULL, $security = true) {
	deprecated_functions::notify(gettext('Use setNPGCookie()'));
	setNPGCookie($name, $value);
}

/**
 * @deprecated since version 1.9.6
 */
function zp_getCookie($name) {
	deprecated_functions::notify(gettext('Use getNPGCookie()'));
	return getNPGCookie($name);
}

/**
 * @deprecated since version 1.9.6
 */
function zp_clearCookie($name) {
	deprecated_functions::notify(gettext('Use clearNPGCookie()'));
	clearNPGCookie($name);
}

/**
 * @deprecated since version 1.9.6
 */
function zpFormattedDate($format, $dt) {
	deprecated_functions::notify(gettext('Use formattedDate()'));
	formattedDate($format, $dt);
}

/**
 * @deprecated since version 2.00.07
 *
 * Gettext replacement function for separate translations of third party themes.
 * @param string $string The string to be translated
 * @param string $theme The name of the plugin. Only required for strings on the 'theme_description.php' file like the general theme description. If the theme is the current theme the function sets it automatically.
 * @return string
 */
function gettext_th($string, $theme = Null) {
	global $_gallery;
	deprecated_functions::notify(gettext('Use <code>gettext()</code> and use your translation tool to merge your language file with the one in the distribution <em>locale</em> folder. See the user guide under Multi-language support.'));
	if (empty($theme)) {
		$theme = $_gallery->getCurrentTheme();
	}
	i18n::setupDomain($theme, 'theme');
	$translation = gettext($string);
	i18n::setupDomain();
	return $translation;
}

/**
 * @deprecated since version 2.00.07
 *
 * ngettext replacement function for separate translations of third party themes.
 * @param string $msgid1
 * @param string $msgid2
 * @param int $n
 * @param string $plugin
 * @return string
 */
function ngettext_th($msgid1, $msgid2, $n, $theme = NULL) {
	global $_gallery;
	deprecated_functions::notify(gettext('Use <code>ngettext()</code> and use your translation tool to merge your language file with the one in the distribution <em>locale</em> folder. See the user guide under Multi-language support.'));
	if (empty($theme)) {
		$theme = $_gallery->getCurrentTheme();
	}
	i18n::setupDomain($theme, 'theme');
	$translation = ngettext($msgid1, $msgid2, $n);
	i18n::setupDomain();
	return $translation;
}

/**
 * @deprecated since version 2.00.07
 *
 * Gettext replacement function for separate translations of third party plugins within the root plugins folder.
 * @param string $string The string to be translated
 * @param string $plugin The name of the plugin. Required.
 * @return string
 */
function gettext_pl($string, $plugin) {
	i18n::setupDomain($plugin, 'plugin');
	deprecated_functions::notify(gettext('Use <code>gettext()</code> and use your translation tool to merge your language file with the one in the distribution <em>locale</em> folder. See the user guide under Multi-language support.'));
	$translation = gettext($string);
	i18n::setupDomain();
	return $translation;
}

/**
 * @deprecated since version 2.00.07
 *
 * ngettext replacement function for separate translations of third party plugins within the root plugins folder.
 * @param string $msgid1
 * @param string $msgid2
 * @param int $n
 * @param string $plugin
 * @return string
 */
function ngettext_pl($msgid1, $msgid2, $n, $plugin) {
	deprecated_functions::notify(gettext('Use <code>ngettext()</code> and use your translation tool to merge your language file with the one in the distribution <em>locale</em> folder. See the user guide under Multi-language support.'));
	i18n::setupDomain($plugin, 'plugin');
	$translation = ngettext($msgid1, $msgid2, $n);
	i18n::setupDomain();
	return $translation;
}

/**
 * @deprecated since version 2.00.11
 *
 * Returns video argument of the current Image.
 *
 * @param object $image optional image object
 * @return bool
 */
function isImageVideo($image = NULL) {
	global $_current_image;
	deprecated_functions::notify(gettext('Use <code>$imageObject->isVideo()</code> object method instead.'));
	if (is_null($image)) {
		if (!in_context(NPG_IMAGE)) {
			return false;
		}
		$image = $_current_image;
	}
	return $image->isVideo();
}

/**
 * @deprecated since version 2.00.11
 *
 * Returns true if the image is a standard photo type
 *
 * @param object $image optional image object
 * @return bool
 */
function isImagePhoto($image = NULL) {
	global $_current_image;
	deprecated_functions::notify(gettext('Use <code>$imageObject->isPhoto()</code> object method instead.'));
	if (is_null($image)) {
		if (!in_context(NPG_IMAGE))
			return false;
		$image = $_current_image;
	}
	return $image->isPhoto();
}

/**
 * @deprecated since version 2.00.11
 *
 * Returns the oldest ancestor of an album (or an image's album);
 *
 * @param string $album an album object
 * @return object
 */
function getUrAlbum($album) {
	deprecated_functions::notify(gettext('Use <code>getUrALbum()</code> object method instead.'));
	if (is_object($album)) {
		return $album->getUrALbum();
	}
	return NULL;
}

/**
 * @deprecated since version 2.00.11
 *
 * @param type $suffix
 * @return type
 */
function getMimeString($suffix) {
	deprecated_functions::notify(gettext('Use <code>mimeTypes"::getType()</code> method instead.'));
	return mimeTypes::getType($suffix);
}
