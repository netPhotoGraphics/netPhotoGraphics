<?php

/*
 * These functions are supplied for compatibility with legacy ZenPhoto themes
 * and plugins. They were never defined in netPhotoGraphics.
 */

function printLangAttribute($locale = null) {
	i18n::htmlLanguageCode();
}

/**
 * Wrapper to call a plugin or otherwise possibly undefined function or method to avoid themes breaking if the plugin is not active
 *
 * Follows PHP's native call_user_func_array() but tries to catch invalid calls
 *
 * Note: Invalid static calls to non static class methods cannot be catched unless the native PHP extension Reflection is available.
 *
 * @since 1.6
 *
 * @param string|array $function A function name, static class method calls like classname::methodname, an array with a class name and static method name or a cass object and a non static class name
 * @param array $parameter Parameters of the function/method as one dimensional array
 */
function callUserFunction($function, $parameter = array()) {
	$callablename = $functioncall = null;
	if (!is_array($parameter)) {
		//Fallback for call_user_func() usages
		$args = func_get_args();
		unset($args[0]);
		$parameter = $args;
	}
	if (is_callable($function, true, $callablename)) {
		if (is_string($function)) {
			if (function_exists($function)) {
				//procedural function or $object->method;
				$functioncall = $callablename;
			} else if (strpos($function, '::')) {
				// static class method call like class::method
				$explode = explode('::', $function);
				if (count($explode) == 2 && class_exists($explode[0]) && method_exists($explode[0], $explode[1])) {
					if (extension_loaded('Reflection')) {
						$methodcheck = new ReflectionMethod($explode[0], $explode[1]);
						if ($methodcheck->isStatic()) {
							$functioncall = $function;
						}
					} else {
						// without reflection hope for the best
						$functioncall = $function;
					}
				}
			}
		} else if (is_array($function) && count($function) == 2) {
			if (is_object($function[0]) && method_exists($function[0], $function[1])) {
				//array: object and method
				$functioncall = $function; // we need the array for object usage
			} else if (class_exists($function[0]) && method_exists($function[0], $function[1])) {
				//array: classname  + static method
				if (extension_loaded('Reflection')) {
					$methodcheck = new ReflectionMethod($function[0], $function[1]);
					if ($methodcheck->isStatic()) {
						$functioncall = $function[0] . '::' . $function[1];
					}
				} else {
					// without reflection hope for the best
					$functioncall = $function[0] . '::' . $function[1];
				}
			}
		}
		if (!is_null($functioncall) && is_array($parameter)) {
			return call_user_func_array($functioncall, $parameter);
		}
	}
	return false;
}

/**
 * Gets current item's owner (gallery images and albums) or author (Zenpage articles and pages)
 *
 * @since 1.5.2
 *
 * @global obj $_zp_current_album
 * @global obj $_zp_current_image
 * @global obj $_zp_current_zenpage_page
 * @global obj $_zp_current_zenpage_news
 * @param boolean $fullname If the owner/author has a real user account and there is a full name set it is returned
 * @return boolean
 */
function getOwnerAuthor($fullname = false) {
	global $_zp_current_album, $_zp_current_image, $_zp_current_zenpage_page, $_zp_current_zenpage_news;
	$currentObj = false;
	if (in_context(NPG_IMAGE)) {
		$currentObj = $_current_image;
	} else if (in_context(NPG_ALBUM)) {
		$currentObj = $_current_album;
	}
	if (extensionEnabled('zenpage')) {
		if (is_Pages()) {
			$currentObj = $_current_zenpage_page;
		} else if (is_NewsArticle()) {
			$currentObj = $_current_zenpage_news;
		}
	}
	if ($currentObj) {
		$owner = $currentObj->getOwner();
		if ($fullname) {
			$ownerOBJ = npg_Authority::newAdministrator($owner, 1);
			$owner = $ownerObj->getName();
		}
		return $owner;
	}
	return false;
}

/**
 * Prints current item's owner (gallery images and albums) or author (Zenpage articles and pages)
 *
 * @since 1.5.2
 *
 * @param type $fullname
 */
function printOwnerAuthor($fullname = false) {
	echo html_encode(getOwnerAuthor($fullname));
}

/**
 * Returns the search url for items the current item's owner (gallery) or author (Zenpage) is assigned to
 *
 * This eventually may return the url to an actual user profile page in the future.
 *
 * @since 1.5.2
 *
 * @return type
 */
function getOwnerAuthorURL() {
	$ownerauthor = getOwnerAuthor(false);
	if ($ownerauthor) {
		if (in_context(NPG_IMAGE) || in_context($NPG_ALBUM)) {
			return getUserURL($ownerauthor, 'gallery');
		}
		if (extensionEnabled('zenpagae') && (is_Pages() || is_NewsArticle())) {
			return getUserURL($ownerauthor, 'zenpage');
		}
	}
}

/**
 * Prints the link to the search engine for results of all items the current item's owner (gallery) or author (Zenpage) is assigned to
 *
 * This eventually may return the url to an actual user profile page in the future.
 *
 * @since 1.5.2
 *
 * @param type $fullname
 * @param type $resulttype
 * @param type $class
 * @param type $id
 * @param type $title
 */
function printOwnerAuthorURL($fullname = false, $resulttype = 'all', $class = null, $id = null, $title = null) {
	$author = $linktext = $title = getOwnerAuthor(false);
	if ($author) {
		if ($fullname) {
			$linktext = getOwnerAuthor(true);
		}
		if (is_null($title)) {
			$title = $linktext;
		}
		printUserURL($author, $resulttype, $linktext, $class, $id, $title);
	}
}

/**
 * Returns a an url for the search engine for results of all items the user with $username is assigned to either as owner (gallery) or author (Zenpage)
 *  Note there is no check if the user name is actually a vaild user account name, owner or author! Use the *OwerAuthor() function for that instead
 *
 * This eventually may return the url to an actual user profile page in the future.
 *
 * @since 1.5.2
 *
 * @param string $username The user name of a user. Note there is no check if the user name is actually valid!
 * @param string $resulttype  'all' for owner and author, 'gallery' for owner of images/albums only, 'zenpage' for author of news articles and pages
 * @return string|null
 */
function getUserURL($username, $resulttype = 'all') {
	if (empty($username)) {
		return null;
	}
	return SearchEngine::getSearchURL(SearchEngine::getSearchQuote($username), '', 'owner', 1, null);
}

/**
 * Prints the link to the search engine for results of all items $username is assigned to either as owner (gallery) or author (Zenpage)
 * Note there is no check if the user name is actually a vaild user account name, owner or author! Use the *OwerAuthor() function for that instead
 *
 * This eventually may point to an actual user profile page in the future.
 *
 * @since 1.5.2
 *
 * @param string $username The user name of a user.
 * @param string $resulttype  'all' for owner and author, 'gallery' for owner of images/albums only, 'zenpage' for author of news articles and pages
 * @param string $linktext The link text. If null the user name will be used
 * @param string $class The CSS class to attach, default null.
 * @param type $id The CSS id to attach, default null.
 * @param type $title The title attribute to attach, default null so the user name is used
 */
function printUserURL($username, $resulttype = 'all', $linktext = null, $class = null, $id = null, $title = null) {
	if ($username) {
		$url = getUserURL($username, $resulttype);
		if (is_null($linktext)) {
			$linktext = $username;
		}
		if (is_null($title)) {
			$title = $username;
		}
		printLinkHTML($url, $linktext, $title, $class, $id);
	}
}

/**
 * Display the site or image copyright notice if defined and display is enabled
 *
 * @since 1.5.8
 * @since 1.6 Also handles the image copyright notice
 *
 * @global obj $_zp_gallery
 * @param string $before Text to print before it
 * @param string $after Text to print after it
 * œparam bool $linked Default true to use the copyright URL if defined
 */
function printCopyrightNotice($before = '', $after = '', $linked = true, $type = 'gallery') {
	global $_zp_gallery, $_zp_current_image;
	$copyrigth_url = '';
	switch ($type) {
		default:
		case 'gallery':
			$copyright_notice = $_zp_gallery->getCopyright();
			//$copyrigth_url = $_zp_gallery->getCopyrightURL();
			$copyright_notice_enabled = getOption('display_copyright_notice');
			break;
		case 'image':
			if (!in_context(NPG_IMAGE)) {
				return false;
			}
			$copyright_notice = $_zp_current_image->getCopyright();
			//$copyrigth_url = $_zp_current_image->getCopyrightURL();
			$copyright_notice_enabled = getOption('display_copyright_image_notice');
			break;
	}
	if (!empty($copyright_notice) && $copyright_notice_enabled) {
		$notice = $before . $copyright_notice . $after;
		if ($linked && !empty($copyrigth_url)) {
			printLinkHTML($copyrigth_url, $notice, $notice);
		} else {
			echo $notice;
		}
	}
}

/**
 * Display the site copyright notice if defined and display is enabled
 *
 * @since 1.6 - Added as shortcut to the general printCopyRightNotice
 *
 * @param string $before Text to print before it
 * @param string $after Text to print after it
 * œparam bool $linked Default true to use the copyright URL if defined
 */
function printGalleryCopyrightNotice($before = '', $after = '', $linked = true) {
	printCopyrightNotice($before, $after, $linked, 'gallery');
}

/**
 * Display the image copyright notice if defined and display iss enabled
 *
 * @since 1.6 - Added as shortcut to the general printCopyRightNotice
 *
 * @param string $before Text to print before it
 * @param string $after Text to print after it
 * œparam bool $linked Default true to use the copyright URL if defined
 */
function printImageCopyrightNotice($before = '', $after = '', $linked = true) {
	printCopyrightNotice($before, $after, $linked, 'image');
}

/**
 * Gets the current page number if it is larger than 1 for use on paginated pages for SEO reason to avoid duplicate titles
 *
 * @since 1.6
 *
 * @param string $before Text to add before the page number. Default ' (';
 * @param string $after Text to add ager the page number. Default ')';
 * @return string
 */
function getCurrentPageAppendix($before = ' (', $after = ')') {
	global $_current_page;
	if ($_current_page > 1) {
		return $before . $_current_page . $after;
	}
	return '';
}

/**
 * Prints the current page number if it is larger than 1 for use on paginated pages for SEO reason to avoid duplicate titles
 *
 * @since 1.6
 *
 * @param string $before Text to add before the page number. Default ' (';
 * @param string $after Text to add larger the page number. Default ')';
 */
function printCurrentPageAppendix($before = ' (', $after = ')') {
	echo getCurrentPageAppendix($before, $after);
}
