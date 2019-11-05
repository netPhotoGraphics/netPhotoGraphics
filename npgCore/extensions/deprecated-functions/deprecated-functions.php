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
	return $_current_album->getCustomData();
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
	return $_current_image->getCustomData();
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
 * @deprecated since version 1.9.06
 */
function zp_register_filter($hook, $function_name, $priority = NULL) {
	deprecated_functions::notify(gettext('Use npgFilters::register()'));
	npgFilters::register($hook, $function_name, $priority);
}

/**
 * @deprecated since version 1.9.06
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
 * @deprecated since version 1.9.06
 */
function zp_remove_filter($hook, $function_to_remove, $priority = NULL, $accepted_args = 1) {
	deprecated_functions::notify(gettext('Use npgFilters::remove()'));
	return npgFilters::remove($hook, $function_to_remove, $priority, $accepted_args);
}

/**
 * @deprecated since version 1.9.06
 */
function zp_has_filter($hook, $function_to_check = false) {
	deprecated_functions::notify(gettext('Use npgFilters::has_filter()'));
	return npgFilters::has_filter($hook, $function_to_check);
}

/**
 * @deprecated since version 1.9.06
 */
function getSiteHomeURL() {
	deprecated_functions::notify(gettext('Use getGalleryIndexURL()'));
	return getGalleryIndexURL();
}

/**
 * @deprecated since version 1.9.06
 */
function getDataUsageNotice() {
	deprecated_functions::notify(gettext('Use the GDPR_required plugin'));
	return array();
}

/**
 * @deprecated since version 1.9.06
 */
function zp_loggedin($rights = ALL_RIGHTS) {
	deprecated_functions::notify(gettext('Use npg_loggedin()'));
	return npg_loggedin($rights);
}

/**
 * @deprecated since version 1.9.06
 */
function zp_setCookie($name, $value, $time = NULL, $security = true) {
	deprecated_functions::notify(gettext('Use setNPGCookie()'));
	setNPGCookie($name, $value);
}

/**
 * @deprecated since version 1.9.06
 */
function zp_getCookie($name) {
	deprecated_functions::notify(gettext('Use getNPGCookie()'));
	return getNPGCookie($name);
}

/**
 * @deprecated since version 1.9.06
 */
function zp_clearCookie($name) {
	deprecated_functions::notify(gettext('Use clearNPGCookie()'));
	clearNPGCookie($name);
}

/**
 * @deprecated since version 1.9.06
 */
function zpFormattedDate($format, $dt) {
	deprecated_functions::notify(gettext('Use formattedDate()'));
	formattedDate($format, $dt);
}
