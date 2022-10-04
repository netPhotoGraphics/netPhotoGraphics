<?php
/**
 * basic functions used by netPhotoGraphics
 *
 * @package core
 *
 */
// force UTF-8 Ø

require_once(__DIR__ . '/functions-basic.php');
require_once(__DIR__ . '/initialize-basic.php');
require_once(__DIR__ . '/initialize-general.php');

/**
 * parses the allowed HTML tags for use by htmLawed
 *
 * @param string &$source by name, contains the string with the tag options
 * @return array the allowed_tags array.
 * @since 1.1.3
 * */
function parseAllowedTags($source) {
	$lines = explode("\n", $source);
	$allowed = array();
	foreach ($lines as $lineNO => $tag) {
		$parse = '(' . trim($tag, "\n") . ')';
		$a = parseTags($parse);
		if (is_array($a)) {
			$allowed = array_merge($allowed, $a);
		} else {
			return $a . '[' . $lineNO . '] ' . $tag;
		}
	}
	return $allowed;
}

function parseTags(&$source) {
	$source = trim($source);
	if (!$source || $source[0] != "(") {
		return gettext('Missing open paren');
	}
	$source = substr($source, 1); //strip off the open paren
	$a = array();
	while (strlen($source) > 1 && $source[0] != ")") {
		if (!preg_match('~^([a-zA-Z0-9_\-]*)\s*([^\(\s]*)~', $source, $matches) || $matches[2] != '=>') {
			$a = gettext('missing assignment operator ');
			break;
		}
		$i = strpos($source, '=>');
		$tag = trim(substr($source, 0, $i));
		//strip forbidden tags from list
		if ($tag == 'script') {
			$a = gettext('"script" tags are not permitted');
			break;
		}
		$source = trim(substr($source, $i + 2));
		if (!$source || $source[0] != "(") {
			$a = gettext('Missing open paren');
			break;
		}
		if (strlen($source) < 2 || $source[1] == ')') {
			$source = trim(substr($source, 2));
			$a[$tag] = array();
		} else {
			$x = parseTags($source);
			if (!is_array($x)) {
				return $x;
			}
			$a[$tag] = $x;
		}
	}
	if (is_array($a) && (!$source || $source[0] != ')')) {
		$a = gettext('Missing closing paren');
	} else {
		$source = trim(substr($source, 1)); //strip the close paren
	}

	return $a;
}

/**
 * Search for a thumbnail for the image
 *
 * @param $localpath local path of the image
 * @return string
 */
function checkObjectsThumb($localpath) {
	global $_supported_images;
	$image = stripSuffix($localpath);
	$l = strlen($image) + 1;
	$candidates = safe_glob($image . '.*');

	foreach ($candidates as $file) {
		$ext = substr($file, $l);
		if (in_array(strtolower($ext), $_supported_images)) {
			return basename($image . '.' . $ext);
		}
	}
	return NULL;
}

/**
 * Returns a truncated string
 *
 * @param string $string source string
 * @param int $length how long it should be
 * @param string $elipsis the text to tack on indicating shortening
 * @return string
 */
function truncate_string($string, $length, $elipsis = '...') {
	if (mb_strlen($string) > $length) {
		$string = mb_substr($string, 0, $length);
		$pos = mb_strrpos(strtr($string, array('~' => ' ', '!' => ' ', '@' => ' ', '#' => ' ', '$' => ' ', '%' => ' ', '^' => ' ', '&' => ' ', '*' => ' ', '(' => ' ', ')' => ' ', '+' => ' ', '=' => ' ', '-' => ' ', '{' => ' ', '}' => ' ', '[' => ' ', ']' => ' ', '|' => ' ', ':' => ' ', ';' => ' ', '<' => ' ', '>' => ' ', '.' => ' ', '?' => ' ', '/' => ' ', '\\', '\\' => ' ', "'" => ' ', "`" => ' ', '"' => ' ')), ' ');
		if ($pos === FALSE) {
			$string .= $elipsis;
		} else {
			$string = mb_substr($string, 0, $pos) . $elipsis;
		}
	}
	return $string;
}

/**
 * returns the web path to a script
 * The script must either include USER_PLUGIN_FOLDER or be relative to the CORE_FOLDER
 * allows us to SEO the links
 *
 * @param string $script the path to the script file
 * @return string
 */
function getAdminLink($script, $path = FULLWEBPATH) {
	if (MOD_REWRITE) {
		if (preg_match('~^' . USER_PLUGIN_FOLDER . '~i', $script)) {
			$script = preg_replace('~^' . USER_PLUGIN_FOLDER . '~i', USER_PLUGIN_PATH, stripSuffix($script));
			return($path . '/' . $script) . RW_SUFFIX;
		}
		$script = preg_replace('~^' . PLUGIN_FOLDER . '~i', PLUGIN_PATH, $script);
		return ($path . '/' . CORE_PATH . '/' . stripSuffix($script)) . RW_SUFFIX;
	} else {
		if (preg_match('~^' . USER_PLUGIN_FOLDER . '~i', $script)) {
			return ($path . '/' . $script);
		}
		return ($path . '/' . CORE_FOLDER . '/' . $script);
	}
}

/**
 * forms a canonical logout link
 *
 * @param string $what the query parameter describing the link
 * @return string the link
 */
function getLogoutLink($what) {
	$parts = mb_parse_url(getRequestURI());
	if (isset($parts['query'])) {
		$pairs = array_map("html_encode", parse_query($parts['query']));
		$pairs = array_merge($pairs, $what);
	} else {
		$pairs = $what;
	}
	$parts['query'] = http_build_query($pairs, '', '&amp;');
	if (array_key_exists('path', $parts)) {
		$parts['path'] = implode("/", array_map("rawurlencode", explode("/", $parts['path'])));
	}
	$logoutlink = build_url($parts);
	return $logoutlink;
}

/**
 * HTML encodes the non-metatag part of the string.
 *
 * @param string $original string to be encoded
 * @param bool $allowScript set to false to prevent pass-through of script tags.
 * @return string
 */
function html_encodeTagged($original, $allowScript = true) {
	$tags = array();
	$str = cleanHTML($original); //	start with a clean state!
	//javascript
	if ($allowScript) {
		preg_match_all('~<script.*>.*</script>~isU', $str, $matches);
		foreach (array_unique($matches[0]) as $key => $tag) {
			$tags[2]['%' . $key . '$j'] = $tag;
			$str = str_replace($tag, '%' . $key . '$j', $str);
		}
	} else {
		$str = preg_replace('|<a(.*)href(.*)=(.*)javascript|ixs', '%$x', $str);
		$tags[2]['%$x'] = '&lt;a href=<strike>javascript</strike>';
		$str = preg_replace('|<(.*)onclick|ixs', '%$c', $str);
		$tags[2]['%$c'] = '&lt;<strike>onclick</strike>';
	}
	//strip html comments
	$str = preg_replace('~<!--.*?-->~is', '', $str);
	// markup
	preg_match_all("/<\/?\w+((\s+(\w|\w[\w-]*\w)(\s*=\s*(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)\/?>/ims", $str, $matches);
	foreach (array_unique($matches[0]) as $key => $tag) {
		$tags[2]['%' . $key . '$s'] = $tag;
		$str = str_replace($tag, '%' . $key . '$s', $str);
	}
	$str = html_decode($str);
	$str = htmlentities($str, ENT_FLAGS, LOCAL_CHARSET);
	foreach (array_reverse($tags, true) as $taglist) {
		$str = strtr($str, $taglist);
	}
	if ($str != $original) {
		$original = cleanHTML($str);
	}
	return $original;
}

/**
 * Returns truncated html formatted content
 *
 * @param string $articlecontent the source string
 * @param int $shorten new size or TRUE to shorten to the first page break
 * @param string $shortenindicator
 * @return string
 *
 * Algorithm copyright by Stephen Billard for use in netPhotoGraphics and derivitive implementations
 */
function shortenContent($articlecontent, $shorten = TRUNCATE_LENGTH, $shortenindicator = NULL) {
	//conservatve check if the string is too long.
	if ($shorten && (mb_strlen(strip_tags($articlecontent)) > (int) $shorten)) {
		if (is_null($shortenindicator)) {
			$shortenindicator = getOption("CMS_textshorten_indicator");
		}
		//remove HTML comments (except for page break indicators)
		$content = preg_replace('~<!-- pagebreak -->~isU', '</PageBreak>', $articlecontent, -1, $breaks);
		$content = preg_replace('~<!--.*-->~isU', '', $content);

		//remove scripts to be added back later
		preg_match_all('~<script.*>.*</script>~isU', $content, $scripts);
		$content = preg_replace('~<script.*>.*</script>~isU', '<_Script_>', $content);

		$pagebreak = $html = $short = '';
		$count = 0;

		//separate out the HTML
		preg_match_all("~</?\w+((\s+(\w|\w[\w-]*\w)(\s*=\s*(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)/?>~i", $content, $markup);
		$parts = preg_split("~</?\w+((\s+(\w|\w[\w-]*\w)(\s*=\s*(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)/?>~i", $content);

		foreach ($parts as $key => $part) {
			if (array_key_exists($key, $markup[0])) {
				$html = $markup[0][$key];
				if ($html == '<_Script_>') {
					$html = array_shift($scripts[0]);
				}
			} else {
				$html = '';
			}

			//make html entities into characters so they count properly
			$cleanPart = html_decode($part);
			$add = mb_strlen($cleanPart);
			if ($count + $add >= $shorten) {
				if ($pagebreak) {
					//back up to prior page break if it exitst
					$short = $pagebreak . $shortenindicator;
				} else {
					//truncate to fit count
					$short .= htmlentities(mb_substr($cleanPart, 0, $shorten - $count), ENT_FLAGS, LOCAL_CHARSET);
					if (strpos($html, '</') === 0) {
						switch (strtolower($html)) {
							case '</p>':
							case '</div>':
								break;
							default:
								//close the tag
								$short .= $html;
							case '</PageBreak>';
								$html = '';
								break;
						}
					}
					$short .= $shortenindicator . $html;
				}
				break;
			}
			$short .= $part;
			if ($html == '</PageBreak>') {
				$pagebreak = $short;
			} else {
				$short .= $html;
			}
			$count = $count + $add;
		}

		//	tidy up the html--probably dropped a few closing tags!
		$articlecontent = trim(cleanHTML($short));
	}

	return $articlecontent;
}

/**
 * Returns either the rewrite path or the plain, non-mod_rewrite path
 * based on the mod_rewrite option.
 * The given paths can start /with or without a slash, it doesn't matter.
 *
 * IDEA: this function could be used to specially escape items in
 * the rewrite chain, like the # character (a bug in mod_rewrite).
 *
 * This is here because it's used in both template-functions.php and in the classes.
 * @param string $rewrite is the path to return if rewrite is enabled. (eg: "/myalbum")
 * @param string $plain is the path if rewrite is disabled (eg: "/?album=myalbum")
 * @param bool $webpath host path to be prefixed. If "false" is passed you will get a localized "WEBPATH"
 * @return string
 */
function rewrite_path($rewrite, $plain, $webpath = NULL) {
	if (is_null($webpath)) {
		if (defined('LOCALE_TYPE') && LOCALE_TYPE == 1) {
			$webpath = seo_locale::localePath();
		} else {
			$webpath = WEBPATH;
		}
	}
	if (MOD_REWRITE) {
		$path = $rewrite;
	} else {
		$path = $plain;
	}
	if ($path && $path[0] == "/") {
		$path = substr($path, 1);
	}
	return $webpath . "/" . $path;
}

/**
 * Returns a sort field part for querying
 * Note: $sorttype may be a comma separated list of field names. If so,
 *       these are peckmarked and returned otherwise unchanged.
 *
 * @param string $sorttype the 'Display" name of the sort
 * @param string $default the default if $sorttype is empty
 * @param string $table the database table being used.
 * @return string
 */
function lookupSortKey($sorttype, $default, $table) {
	global $_fieldLists;
	switch (strtolower($sorttype)) {
		case 'random':
			return 'RAND()';
		case "manual":
			return 'sort_order';
		default:
			if (empty($sorttype)) {
				if (empty($default)) {
					return 'id';
				}
				return $default;
			}
			if (substr($sorttype, 0) == '(') {
				return $sorttype;
			}
			if ($table == 'albums') { // filename is synonomon for folder with albums
				$sorttype = str_replace('filename', 'folder', $sorttype);
			}
			if (is_array($_fieldLists) && isset($_fieldLists[$table])) {
				$dbfields = $_fieldLists[$table];
			} else {
				$result = db_list_fields($table);
				$dbfields = array();
				if ($result) {
					foreach ($result as $row) {
						$dbfields[strtolower($row['Field'])] = $row['Field'];
					}
				}
				$_fieldLists[$table] = $dbfields;
			}
			$sorttype = strtolower($sorttype);
			$list = explode(',', $sorttype);
			$rslt = array();
			foreach ($list as $key => $field) {
				if (array_key_exists($field = trim($field, '`'), $dbfields)) {
					$rslt[] = '`' . trim($dbfields[$field]) . '`';
				}
			}
			if (empty($rslt)) {
				return 'id';
			}
			return implode(',', $rslt);
	}
}

/**
 * Returns a formatted date for output
 *
 * @param string $format the DateTime::format()  format string
 * @param date $dt the date to be output
 * @return string
 */
function formattedDate($format, $dt) {
	global $_UTF8, $_current_locale;

	if (strtolower($format) == '%x') {
		if ($_current_locale && class_exists('IntlDateFormatter')) {
			//handle "preferred date representation
			if ($format == '%x') { // local date format
				$formatter = new IntlDateFormatter($_current_locale, IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
			} else {
				// local time format
				$formatter = new IntlDateFormatter($_current_locale, IntlDateFormatter::NONE, IntlDateFormatter::SHORT);
			}
			if ($formatter === null) {
				throw new InvalidConfigException(intl_get_error_message());
			}
			$fdate = $formatter->format($dt);
		} else {
			if ($format == '%x') { // local date format
				$format = 'n/j/y'; //	default to US standard date format
			} else {
				$format = 'H:i:s'; //	default to US standard time format
			}
		}
	}
	if (!isset($fdate)) {
		$fdate = date($format, $dt);
	}
	if (function_exists('mb_internal_encoding')) {
		if (($charset = mb_internal_encoding()) == LOCAL_CHARSET) {
			return $fdate;
		}
	} else {
		$charset = 'ISO-8859-1';
	}
	return $_UTF8->convert($fdate, $charset, LOCAL_CHARSET);
}

/**
 * Sorts the results of a DB search by the current locale string for $field
 *
 * @param array $dbresult the result of the DB query
 * @param string $field the field name to sort on
 * @param bool $descending the direction of the sort
 * @return array the sorted result
 */
function sortByMultilingual($dbresult, $field, $descending) {
	$temp = array();
	foreach ($dbresult as $key => $row) {
		$temp[$key] = get_language_string($row[$field]);
	}
	localeSort($temp);
	if ($descending) {
		$temp = array_reverse($temp, true);
	}
	$result = array();
	foreach ($temp as $key => $v) {
		$result[$key] = $dbresult[$key];
	}
	return $result;
}

/**
 * Checks access for the album root
 *
 * @param bit $action what the caller wants to do
 *
 */
function accessAllAlbums($action) {
	global $_admin_owner_list, $_loggedin;
	if (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
		if (npg_loggedin($action))
			return true;
	}
	if (npg_loggedin(ALL_ALBUMS_RIGHTS) && ($action == LIST_RIGHTS)) { // sees all
		return $_loggedin;
	}
	return false;
}

/**
 * Checks to see access is allowed to an album
 * Returns true if access is allowed.
 * There is no password dialog--you must have already had authorization via a cookie.
 *
 * @param string $album album object or name of the album
 * @param string &$hint becomes populated with the password hint.
 * @return bool
 */
function checkAlbumPassword($album, &$hint = NULL) {
	global $_pre_authorization, $_gallery;
	if (is_object($album)) {
		$albumname = $album->name;
	} else {
		$album = newAlbum($albumname = $album, true, true);
	}
	if (isset($_pre_authorization[$albumname])) {
		return $_pre_authorization[$albumname];
	}
	$hash = $album->getPassword();
	if (empty($hash)) {
		$album = $album->getParent();
		while (!is_null($album)) {
			$hash = $album->getPassword();
			$authType = "album_auth_" . $album->getID();
			$saved_auth = getNPGCookie($authType);

			if (!empty($hash)) {
				if ($saved_auth == $hash) {
					$_pre_authorization[$albumname] = $authType;
					return $authType;
				} else {
					$hint = $album->getPasswordHint();
					return false;
				}
			}
			$album = $album->getParent();
		}
		// revert all tlhe way to the gallery
		$hash = $_gallery->getPassword();
		$authType = 'gallery_auth';
		$saved_auth = getNPGCookie($authType);
		if (empty($hash)) {
			$authType = 'public_access';
		} else {
			if ($saved_auth != $hash) {
				$hint = $_gallery->getPasswordHint();
				return false;
			}
		}
	} else {
		$authType = "album_auth_" . $album->getID();
		$saved_auth = getNPGCookie($authType);
		if ($saved_auth != $hash) {
			$hint = $album->getPasswordHint();
			return false;
		}
	}
	$_pre_authorization[$albumname] = $authType;
	return $authType;
}

/**
 * Returns a consolidated list of plugins
 * The array structure is key=plugin name, value=plugin path
 *
 * @param string $pattern File system wildcard matching pattern to limit the search
 * @param string $folder subfolder within the plugin folders to search
 * @param bool $stripsuffix set to true to remove the suffix from the key name in the array
 * @return array
 */
function getPluginFiles($pattern, $folder = '', $stripsuffix = true, $flags = 0) {
	if (!empty($folder) && substr($folder, -1) != '/')
		$folder .= '/';
	$list = array();
	$curdir = getcwd();
	$sources = array(PLUGIN_SERVERPATH . $folder, USER_PLUGIN_SERVERPATH . $folder);

	foreach ($sources as $basepath) {
		if (is_dir($basepath)) {
			chdir($basepath);
			$filelist = safe_glob($pattern, $flags);
			foreach ($filelist as $file) {
				$key = filesystemToInternal($file);
				if ($stripsuffix) {
					$key = stripSuffix($key);
				}
				if (realpath($basepath . $file)) { //	sometimes you just can't get there from here!
					$list[$key] = $basepath . $file;
				}
			}
		}
	}
	chdir($curdir);
	return $list;
}

/**
 * Returns the fully qualified file name of the plugin file.
 *
 * Note: order of selection is:
 * 	1-theme folder file (if $inTheme is set)
 *  2-user plugin folder file
 *  3-extensions folder file
 * first file found is used
 *
 * @param string $plugin is the name of the plugin file, typically something.php
 * @param bool $inTheme tells where to find the plugin file.
 * @param bool $webpath return a WEBPATH rather than a SERVERPATH
 *
 * @return string
 */
function getPlugin($plugin, $inTheme = FALSE, $webpath = FALSE) {
	global $_gallery;
	$pluginFile = NULL;
	$plugin_fs = internalToFilesystem($plugin);
	$sources = array('/' . USER_PLUGIN_FOLDER . '/' . $plugin_fs, '/' . CORE_FOLDER . '/' . PLUGIN_FOLDER . '/' . $plugin_fs, '/' . CORE_FOLDER . '/' . $plugin_fs);
	if ($inTheme === TRUE) {
		$inTheme = $_gallery->getCurrentTheme();
	}
	if ($inTheme) {
		array_unshift($sources, '/' . THEMEFOLDER . '/' . internalToFilesystem($inTheme . '/' . $plugin));
	}

	foreach ($sources as $file) {
		if (file_exists(SERVERPATH . $file)) {
			$pluginFile = $file;
			break;
		}
	}

	if ($pluginFile) {
		if ($webpath) {
			if (!is_string($webpath))
				$webpath = WEBPATH;
			return $webpath . filesystemToInternal($pluginFile);
		} else {
			return SERVERPATH . $pluginFile;
		}
	}
	return false;
}

/**
 * Returns an array of the currently enabled plugins
 *
 * @return array
 */
function getEnabledPlugins() {
	global $_EnabledPlugins;
	if (is_array($_EnabledPlugins)) {
		return $_EnabledPlugins;
	}
	$seenPlugins = $_EnabledPlugins = array();
	$sortlist = getPluginFiles('*.php');
	foreach ($sortlist as $extension => $path) {
		if (!isset($seenPlugins[strtolower($extension)])) { //	in case of filename case sensitivity
			$seenPlugins[strtolower($extension)] = true;
			$opt = '_plugin_' . $extension;
			if ($option = getOption($opt)) {
				$_EnabledPlugins[$extension] = array('priority' => $option, 'path' => $path);
			}
		}
	}
	$_EnabledPlugins = sortMultiArray($_EnabledPlugins, 'priority', true, true, false, true);
	return $_EnabledPlugins;
}

/**
 * Returns if a plugin is enabled
 * @param string $extension
 * @return bool
 */
function extensionEnabled($extension) {
	return getOption('_plugin_' . $extension);
}

/**
 * Enables a plugin
 * @param string $extension
 * @param int $priority
 * @param bool $persistent
 */
function enableExtension($extension, $priority, $persistent = true) {
	setOption('_plugin_' . $extension, $priority, $persistent);
}

/**
 * call this if the extension should be enabled by default
 */
function defaultExtension($priority) {
	if (OFFSET_PATH == 2) {
		$bt = debug_backtrace();
		$b = reset($bt);
		setOptionDefault('_plugin_' . stripSuffix(basename($b['file'])), $priority);
	}
	return $priority;
}

/**
 * Populates and returns the $_admin_owner_list array
 * @return array
 */
function getManagedAlbumList() {
	global $_admin_owner_list, $_current_admin_obj;
	$_admin_owner_list = array();
	if (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
		$sql = "SELECT `folder` FROM " . prefix('albums') . ' WHERE `parentid` IS NULL';
		$albums = query($sql);
		if ($albums) {
			while ($album = db_fetch_assoc($albums)) {
				$_admin_owner_list[$album['folder']] = 32767;
			}
			db_free_result($albums);
		}
	} else {
		if ($_current_admin_obj) {
			$_admin_owner_list = array();
			$objects = $_current_admin_obj->getObjects();
			foreach ($objects as $object) {
				if ($object['type'] == 'albums') {
					$_admin_owner_list[$object['data']] = $object['edit'];
				}
			}
		}
	}
	return array_keys($_admin_owner_list);
}

/**
 * Returns a list of album names managed by $id
 *
 * @param string $type which kind of object
 * @param int $id admin ID
 * @param bool $rights set true for album sub-rights
 * @return array
 */
function populateManagedObjectsList($type, $id, $rights = false) {
	if ($id <= 0) {
		return array();
	}
	$cv = array();
	if (empty($type) || substr($type, 0, 5) == 'album') {
		$sql = "SELECT " . prefix('albums') . ".`folder`," . prefix('albums') . ".`title`," . prefix('admin_to_object') . ".`edit` FROM " . prefix('albums') . ", " .
						prefix('admin_to_object') . " WHERE " . prefix('admin_to_object') . ".adminid=" . $id .
						" AND " . prefix('albums') . ".id=" . prefix('admin_to_object') . ".objectid AND " . prefix('admin_to_object') . ".type LIKE 'album%'";
		$currentvalues = query($sql, false);
		if ($currentvalues) {
			while ($albumitem = db_fetch_assoc($currentvalues)) {
				$folder = $albumitem['folder'];
				$name = get_language_string($albumitem['title']);
				if ($type && !$rights) {
					$cv[$name] = $folder;
				} else {
					$cv[] = array('data' => $folder, 'name' => $name, 'type' => 'albums', 'edit' => (int) $albumitem['edit']);
				}
			}
			db_free_result($currentvalues);
		}
	}
	if (empty($type) || $type == 'pages') {
		$sql = 'SELECT ' . prefix('pages') . '.`title`,' . prefix('pages') . '.`titlelink`, ' . prefix('admin_to_object') . '.`edit` FROM ' . prefix('pages') . ', ' . prefix('admin_to_object') . " WHERE " . prefix('admin_to_object') . ".adminid=" . $id . " AND " . prefix('pages') . ".id=" . prefix('admin_to_object') . ".objectid AND " . prefix('admin_to_object') . ".type='pages'";
		$currentvalues = query($sql, true);
		if ($currentvalues) {
			while ($item = db_fetch_assoc($currentvalues)) {
				if ($type) {
					$cv[get_language_string($item['title'])] = $item['titlelink'];
				} else {
					$cv[] = array('data' => $item['titlelink'], 'name' => get_language_string($item['title']), 'type' => 'pages', 'edit' => (int) $item['edit']);
				}
			}
			db_free_result($currentvalues);
		}
	}
	if (empty($type) || $type == 'news_categories') {
		$sql = 'SELECT ' . prefix('news_categories') . '.`titlelink`,' . prefix('news_categories') . '.`title`, ' . prefix('admin_to_object') . '.`edit` FROM ' . prefix('news_categories') . ', ' .
						prefix('admin_to_object') . " WHERE " . prefix('admin_to_object') . ".adminid=" . $id .
						" AND " . prefix('news_categories') . ".id=" . prefix('admin_to_object') . ".objectid AND " . prefix('admin_to_object') . ".type='news_categories'";
		$currentvalues = query($sql, false);
		if ($currentvalues) {
			while ($item = db_fetch_assoc($currentvalues)) {
				if ($type) {
					$cv[get_language_string($item['title'])] = $item['titlelink'];
				} else {
					$cv[] = array('data' => $item['titlelink'], 'name' => get_language_string($item['title']), 'type' => 'news_categories', 'edit' => (int) $item['edit']);
				}
			}
			db_free_result($currentvalues);
		}
		$item = query_single_row('SELECT `edit` FROM ' . prefix('admin_to_object') . "WHERE adminid=$id AND objectid=0 AND type='news_categories'", false);
		if ($item) {
			$cv[] = array('data' => '`', 'name' => '"' . gettext('un-categorized') . '"', 'type' => 'news_categories', 'edit' => (int) $item['edit']);
		}
	}
	return $cv;
}

/**
 * Returns  an array of album ids whose parent is the folder
 * @param string $albumfolder folder name if you want a album different >>from the current album
 * @return array
 */
function getAllSubAlbumIDs($albumfolder = '') {
	global $_current_album;
	if (empty($albumfolder)) {
		if (isset($_current_album)) {
			$albumfolder = $_current_album->getFileName();
		} else {
			return null;
		}
	}
	$query = "SELECT `id`,`folder`, `show` FROM " . prefix('albums') . " WHERE `folder` LIKE " . db_quote(db_LIKE_escape($albumfolder) . '%');
	$subIDs = query_full_array($query);
	return $subIDs;
}

/**
 * recovers search parameters from stored cookie, clears the cookie
 *
 * @param string $what the page type
 * @param string $album Name of the album
 * @param string $image Name of the image
 */
function handleSearchParms($what, $album = NULL, $image = NULL) {
	global $_current_search, $_requested_object, $_last_album, $_current_album,
	$_CMS_current_article, $_CMS_current_page, $_gallery, $_loggedin, $_HTML_cache;

	$_last_album = getNPGCookie('last_album');
	if (is_object($_requested_object) && get_class($_requested_object) == 'SearchEngine') { //	we are are on a search
		if (is_null($album)) {
			clearNPGCookie('last_album');
		}
		return $_requested_object->getAlbumList();
	}
	$params = getNPGCookie('search_params');
	if (!empty($params)) {
		$context = get_context();
		$_current_search = new SearchEngine();
		$_current_search->setSearchParams($params);
		// check to see if we are still "in the search context"
		if (!is_null($image)) {
			$dynamic_album = $_current_search->getDynamicAlbum();
			if ($_current_search->getImageIndex($album->name, $image->filename) !== false) {
				if ($dynamic_album) {
					$dynamic_album->linkname = $_current_album->linkname;
					$dynamic_album->parentLinks = $_current_album->parentLinks;
					$dynamic_album->index = $_current_album->index;
					$_current_album = $dynamic_album;
				}
				$context = $context | SEARCH_LINKED | IMAGE_LINKED;
			}
		}
		if (is_null($album)) {
			clearNPGCookie('last_album');
		} else {
			$albumname = $album->name;
			setNPGCookie('last_album', $albumname);
			if (hasDynamicAlbumSuffix($albumname) && !is_dir(ALBUM_FOLDER_SERVERPATH . $albumname)) {
				$albumname = stripSuffix($albumname); // strip off the suffix as it will not be reflected in the search path
			}
			//	see if the album is within the search context. NB for these purposes we need to look at all albums!
			$save_logon = $_loggedin;
			$_loggedin = $_loggedin | VIEW_ALL_RIGHTS;
			$search_album_list = $_current_search->getAlbums(0);
			$_loggedin = $save_logon;
			foreach ($search_album_list as $searchalbum) {
				if (strpos($albumname, $searchalbum) !== false) {
					$context = $context | SEARCH_LINKED | ALBUM_LINKED;
					break;
				}
			}
		}
		if (!is_null($_CMS_current_page)) {
			$pages = $_current_search->getPages();
			if (!empty($pages)) {
				$titlelink = $_CMS_current_page->getTitlelink();
				foreach ($pages as $apage) {
					if ($apage == $titlelink) {
						$context = $context | SEARCH_LINKED;
						break;
					}
				}
			}
		}
		if (!is_null($_CMS_current_article)) {
			$news = $_current_search->getArticles(0, NULL, true);
			if (!empty($news)) {
				$titlelink = $_CMS_current_article->getTitlelink();
				foreach ($news as $anews) {
					if ($anews['titlelink'] == $titlelink) {
						$context = $context | SEARCH_LINKED;
						break;
					}
				}
			}
		}
		if (($context & SEARCH_LINKED)) {
			set_context($context);
			$_HTML_cache->abortHTMLCache(true);
		} else { // not an object in the current search path
			$_current_search = null;
			rem_context(NPG_SEARCH);
			if (!isset($_REQUEST['preserve_search_params'])) {
				clearNPGCookie("search_params");
			}
		}
	}
}

/**
 * Updates published status based on publishdate and expiredate
 *
 * @param string $table the database table
 */
function updatePublished($table) {
	//publish items that have matured
	$sql = 'SELECT `id` FROM ' . prefix($table) . ' WHERE `show`=0 AND `publishdate`IS NOT NULL AND `publishdate`<=' . db_quote(date('Y-m-d H:i:s'));
	$result = query($sql);
	if ($result) {
		while ($row = db_fetch_assoc($result)) {
			$obj = getItemByID($table, $row['id']);
			if ($obj) {
				$obj->setShow(1);
				$obj->save();
			}
		}
	}

	//unpublish items that have expired or are not yet published
	$sql = 'SELECT `id` FROM ' . prefix($table) . ' WHERE `show`=1 AND (`expiredate` IS NOT NULL AND `expiredate`<' . db_quote(date('Y-m-d H:i:s')) . ' OR `publishdate`>' . db_quote(date('Y-m-d H:i:s')) . ')';
	$result = query($sql);
	if ($result) {
		while ($row = db_fetch_assoc($result)) {
			$obj = getItemByID($table, $row['id']);
			if ($obj) {
				$obj->setShow(0);
				$obj->save();
			}
		}
	}
}

/**
 * Returns the number of album thumbs that go on a gallery page
 *
 * @return int
 */
function galleryAlbumsPerPage() {
	return max(1, getOption('albums_per_page'));
}

/**
 * Returns the number of image thumbs that go on a gallery page
 *
 * @return int
 */
function galleryImagesPerPage() {
	return getOption('images_per_page');
}

/**
 * Returns the theme folder
 * If there is an album theme, loads the theme options.
 *
 * @param object $album album object if override desired
 *
 * @return string
 */
function setupTheme($album = NULL) {
	global $_gallery, $_current_album, $_current_search, $_themeroot;
	$albumtheme = '';
	if (is_null($album)) {
		if (in_context(SEARCH_LINKED)) {
			if (!$album = $_current_search->getDynamicAlbum()) {
				$album = $_current_album;
			}
		} else {
			$album = $_current_album;
		}
	}
	$theme = $_gallery->getCurrentTheme();
	$id = 0;
	if (!is_null($album)) {
		$parent = $album->getUrAlbum();
		$albumtheme = $parent->getAlbumTheme();
		if (!empty($albumtheme)) {
			if (is_dir(SERVERPATH . "/" . THEMEFOLDER . "/$albumtheme")) {
				$theme = $albumtheme;
				$id = $parent->getID();
			}
		}
	}
	$theme = npgFilters::apply('setupTheme', $theme);
	$_gallery->setCurrentTheme($theme, true); //	don't make it permanant if someone saves the gallery
	$themeindex = getPlugin('index.php', $theme);
	if (empty($theme) || empty($themeindex)) {
		header('Content-Type: text/html; charset=' . LOCAL_CHARSET);
		?>
		<!DOCTYPE html>
		<html xmlns="http://www.w3.org/1999/xhtml"<?php i18n::htmlLanguageCode(); ?>>
			<head>
			</head>
			<body>
				<strong><?php printf(gettext('No theme scripts found. Please check the <em>%s</em> folder of your installation.'), THEMEFOLDER); ?></strong>
			</body>
		</html>
		<?php
		exit();
	} else {
		loadLocalOptions($id, $theme);
		$_themeroot = WEBPATH . "/" . THEMEFOLDER . "/$theme";
	}
	return $theme;
}

/**
 * Returns an array of unique tag names
 *
 * Tags are shown only if their occurance count is greater or equal to the threshold
 * value passed.
 *
 * If the site visitor is not logged in this function returns only tags associated
 * with "published" objects. However, the publish state is limited to the `show` column
 * of the object. This is not totally "correct", however the computatioal intensity
 * of returning only that might link to "visible" objects is prohibitive.
 *
 * Logged-in users will see all tags subject to the threshold limits
 *
 * @param $language string exclude language tags other than this string
 * @param $count int threshold occurance count
 * @param $returnCount bool set to true to return the tag counts
 * @return array
 *
 * @author Stephen Billard
 * @Copyright 2014 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 */
function getAllTagsUnique($language = NULL, $count = 1, $returnCount = NULL) {
	global $_unique_tags, $_count_tags, $_current_locale, $_loggedin;
	if (!getOption('adminTagsTab')) { //	no tags on this installation
		return array();
	}
	if (is_null($returnCount)) {
		$list = &$_unique_tags;
	} else {
		$list = &$_count_tags;
	}
	if (is_null($language)) {
		switch (getOption('languageTagSearch')) {
			case 1:
				$language = substr($_current_locale, 0, 2);
				break;
			case 2:
				$language = $_current_locale;
				break;
			default:
				$language = 0;
				break;
		}
	}

	if (!isset($list[$language][$count])) {
		$list[$language][$count] = array();

		if (($_loggedin & TAGS_RIGHTS) || ($_loggedin & VIEW_UNPUBLISHED_PAGE_RIGHTS & VIEW_UNPUBLISHED_NEWS_RIGHTS & VIEW_UNPUBLISHED_RIGHTS == VIEW_UNPUBLISHED_PAGE_RIGHTS & VIEW_UNPUBLISHED_NEWS_RIGHTS & VIEW_UNPUBLISHED_RIGHTS)) {
			$source = prefix('obj_to_tag');
		} else {
			// create a table of only "published" tag assignments
			$source = 'taglist';
			query('CREATE TEMPORARY TABLE IF NOT EXISTS taglist (
														`tagid` int(11) UNSIGNED NOT NULL,
														`type` tinytext,
														`objectid` int(11) UNSIGNED NOT NULL,
														KEY (tagid),
														KEY (objectid)
														) CHARACTER SET utf8 COLLATE utf8_unicode_ci');
			$tables = array('images' => VIEW_UNPUBLISHED_RIGHTS, 'albums' => VIEW_UNPUBLISHED_RIGHTS);
			if (class_exists('CMS')) {
				$tables = array_merge($tables, array('pages' => VIEW_UNPUBLISHED_PAGE_RIGHTS, 'news' => VIEW_UNPUBLISHED_NEWS_RIGHTS));
			}
			foreach ($tables as $table => $rights) {
				if ($_loggedin & $rights) {
					$show = '';
				} else {
					$show = ' AND tag.objectid=object.id AND object.show=1';
				}
				$sql = 'INSERT INTO taglist SELECT tag.tagid, tag.type, tag.objectid FROM ' . prefix('obj_to_tag') . ' tag, ' . prefix($table) . ' object WHERE tag.type="' . $table . '"' . $show;
				query($sql);
			}
		}

		if (empty($language)) {
			$lang = '';
		} else {
			$lang = ' AND (tag.language="" OR tag.language LIKE ' . db_quote(db_LIKE_escape($language) . '%') . ')';
		}
		if ($_loggedin & TAGS_RIGHTS) {
			$private = '';
		} else {
			$private = ' AND (tag.private=0)';
		}

		$sql = 'SELECT tag.name, count(DISTINCT tag.name, obj.type, obj.objectid) as count FROM ' . prefix('tags') . ' tag, ' . $source . ' obj WHERE (tag.id=obj.tagid) ' . $lang . $private . ' GROUP BY tag.name';
		$unique_tags = query($sql);

		if ($unique_tags) {
			while ($tagrow = db_fetch_assoc($unique_tags)) {
				if ($tagrow['count'] >= $count) {
					if ($returnCount) {
						$list[$language][$count][$tagrow['name']] = $tagrow['count'];
					} else {
						$list[$language][$count][mb_strtolower($tagrow['name'])] = $tagrow['name'];
					}
				}
			}
		}
		db_free_result($unique_tags);

		if ($source == 'taglist') {
			query('DROP TEMPORARY TABLE taglist');
		}
	}
	return $list[$language][$count];
}

/**
 * Stores tags for an object
 *
 * @param array $tags the tag values
 * @param int $id the record id of the album/image
 * @param string $tbl database table of the object
 */
function storeTags($tags, $id, $tbl) {
	if ($id) {
		$tagsLC = array();
		foreach ($tags as $key => $tag) {
			$tag = trim($tag, '\'"');
			if (!empty($tag)) {
				$lc_tag = mb_strtolower($tag);
				if (!in_array($lc_tag, $tagsLC)) {
					$tagsLC[$tag] = $lc_tag;
				}
			}
		}
		$sql = "SELECT `id`, `tagid` from " . prefix('obj_to_tag') . " WHERE `objectid`='" . $id . "' AND `type`='" . $tbl . "'";
		$result = query($sql);
		$existing = array();
		if ($result) {
			while ($row = db_fetch_assoc($result)) {
				$dbtag = query_single_row("SELECT `name` FROM " . prefix('tags') . " WHERE `id`='" . $row['tagid'] . "'");
				$existingLC = mb_strtolower($dbtag['name']);
				if (in_array($existingLC, $tagsLC)) { // tag already set no action needed
					$existing[] = $existingLC;
				} else { // tag no longer set, remove it
					query("DELETE FROM " . prefix('obj_to_tag') . " WHERE `id`='" . $row['id'] . "'");
				}
			}
			db_free_result($result);
		}
		$tags = array_diff($tagsLC, $existing); // new tags for the object
		foreach ($tags as $key => $tag) {
			$dbtag = query_single_row("SELECT `id` FROM " . prefix('tags') . " WHERE `name`=" . db_quote($key));
			if (!is_array($dbtag)) { // tag does not exist
				query('INSERT INTO ' . prefix('tags') . ' (name) VALUES (' . db_quote($key) . ')', false);
				$dbtag = array('id' => db_insert_id());
			}
			query("INSERT INTO " . prefix('obj_to_tag') . "(`objectid`, `tagid`, `type`) VALUES (" . $id . "," . $dbtag['id'] . ",'" . $tbl . "')");
		}
	}
}

/**
 * Retrieves the tags for an object
 * Returns them in an array
 *
 * @param int $id the record id of the album/image
 * @param string $tbl 'albums' or 'images', etc.
 * @return array
 */
function readTags($id, $tbl, $language, $full = false) {
	global $_current_locale;
	if (is_null($language)) {
		switch (getOption('languageTagSearch')) {
			case 1:
				$language = substr($_current_locale, 0, 2);
				break;
			case 2:
				$language = $_current_locale;
				break;
			default:
				$langage = '';
				break;
		}
	}
	if (npg_loggedin(TAGS_RIGHTS)) {
		$private = '';
	} else {
		$private = ' AND tags.private=0';
	}

	$tagsFull = $tags = array();

	$sql = 'SELECT * FROM ' . prefix('tags') . ' AS tags, ' . prefix('obj_to_tag') . ' AS objects WHERE `type`="' . $tbl . '" AND `objectid`="' . $id . '" AND tagid=tags.id' . $private;

	if ($language) {
		$sql .= ' AND (tags.language="" OR tags.language LIKE ' . db_quote(db_LIKE_escape($language) . '%') . ')';
	}
	$result = query($sql);
	if ($result) {
		while ($row = db_fetch_assoc($result)) {
			$tagsFull[] = $row;
			$tags[] = $row['name'];
		}
		db_free_result($result);
	}
	if ($full) {
		return $tagsFull;
	} else {
		$tags = array_unique($tags);
		localeSort($tags);
		return $tags;
	}
}

/**
 * Creates the body of a select list
 *
 * @param array $currentValue list of items to be flagged as checked
 * @param array $list the elements of the select list
 * @param bool $descending set true for a reverse order sort
 * @param bool $localize set true if $list is an array of "language string" => value to sort on the language string
 */
function generateListFromArray($currentValue, $list, $descending, $localize) {
	if (!is_null($descending)) {
		if ($localize) {
			$list = array_flip($list);
			localeSort($list);
			$list = array_flip($list);
		} else {
			localeSort($list);
		}
	}
	if ($descending) {
		$list = array_reverse($list, true);
	}

	foreach ($list as $key => $item) {
		echo '<option value="' . html_encode($item) . '"';
		if (in_array($item, $currentValue)) {
			echo ' selected="selected"';
		}
		if ($localize)
			$display = $key;
		else
			$display = $item;
		echo '>' . $display . "</option>" . "\n";
	}
}

/**
 * Generates a selection list from files found on disk
 *
 * @param strig $currentValue the current value of the selector
 * @param string $root directory path to search
 * @param string $suffix suffix to select for
 * @param bool $descending set true to get a reverse order sort
 */
function generateListFromFiles($currentValue, $root, $suffix, $descending = false) {
	if (is_dir($root)) {
		$curdir = getcwd();
		chdir($root);
		$filelist = safe_glob('*' . $suffix);
		$list = array();
		foreach ($filelist as $file) {
			$file = str_replace($suffix, '', $file);
			$list[] = filesystemToInternal($file);
		}
		generateListFromArray(array($currentValue), $list, $descending, false);
		chdir($curdir);
	}
}

/**
 * @param string $url The link URL
 * @param string $text The text to go with the link
 * @param string $title Text for the title tag
 * @param string $class optional class
 * @param string $id optional id
 */
function getLinkHTML($url, $text, $title = NULL, $class = false, $id = NULL) {
	return "<a href=\"" . html_encode($url) . "\"" .
					(($title) ? " title=\"" . html_encode(getBare($title)) . "\"" : "") .
					(($class) ? " class=\"$class\"" : "") .
					(($id) ? " id=\"$id\"" : "") . ">" .
					html_encode($text) . "</a>";
}

/**
 * General link printing function
 * @param string $url The link URL
 * @param string $text The text to go with the link
 * @param string $title Text for the title tag
 * @param string $class optional class
 * @param string $id optional id
 */
function printLinkHTML($url, $text, $title = NULL, $class = false, $id = NULL) {
	echo getLinkHTML($url, $text, $title, $class, $id);
}

/**
 * Central place for meta header handling
 */
function printStandardMeta() {
	?>
	<meta name="generator" content="netPhotoGraphics <?php echo NETPHOTOGRAPHICS_VERSION_CONCISE . ' https://' . GITHUB; ?>" />
	<meta charset="<?php echo LOCAL_CHARSET; ?>" />
	<?php
}

/**
 * prints the software logo
 */
function swLogo() {
	return '<span style = \'display: inline-block;font-family: Palitino, "Times New Roman", Times, serif;vertical-align: -6%;\'>
		<span style="display: inline-block;vertical-align: 6%;font-size: 80%;">NET</span><span style="font-size: 150%;">P</span><span style="display: inline-block;vertical-align: 6%;font-size: 80%;">H</span><span style="display: inline-block;vertical-align: 0.8%;font-size: 110%;font-weight: bolder;">&#9678;</span><span style="display: inline-block;vertical-align: 6%;font-size: 80%;">TO</span><span style="font-size: 150%;">G</span><span style="display: inline-block;vertical-align: 6%;font-size: 80%;">RAPHICS</span>
	</span>';
}

/**
 *
 * @global object $_gallery
 * @param string $title title for the image
 */
function printSiteLogoImage($title = NULL) {
	global $_gallery;
	if (empty($title)) {
		$title = $_gallery->getSiteLogoTitle();
		if (empty($title)) {
			$title = 'netPhotoGraphics';
		}
	}
	$logo = $_gallery->getSiteLogo();
	//NOTE: we "know" that the netPhotoGraphics logo is 78 pixels high and 282 pixels wide so the 78px is hard coded
	?>
	<img src="<?php echo $logo; ?>" id="site logo" alt="site_logo" title="<?php echo $title; ?>" style="height:78px; width:auto;" />
	<?php
}

/**
 * Creates a standard button
 *
 * @param string $buttonType button, submit, etc.
 * @param type $buttonText what shows on the button
 * @param array $options array(	//	pass any of this list that is needed
 * 															'buttonClass' any added classes needed
 * 															'buttonLink' URL if the button loads a script
 * 															'buttonTitle' title
 * 															'buttonClick' javaScript for the button "onclick"
 * 															'disabled' true if the button is disabled
 * 															'id' CSS ID
 * 															'buttonExtra' any other "stuff" that needs to be part of the button
 * 														)
 */
function npgButton($buttonType, $buttonText, $options = array()) {
	$options = array_merge(array(
			'buttonClass' => NULL,
			'buttonLink' => NULL,
			'buttonTitle' => NULL,
			'buttonClick' => NULL,
			'disabled' => NULL,
			'id' => NULL,
			'buttonExtra' => NULL
					), $options
	);
	extract($options);

	if ($disabled) {
		$buttonClass .= ' disabled';
		$disabled = ' disabled="disabled"';
	}
	$buttonClass = trim('npgButton ' . $buttonClass);
	if ($buttonLink) {
		$buttonLink = ' onclick="window.location=\'' . $buttonLink . '\'"';
	} else {
		if ($buttonClick) {
			$buttonLink = ' onclick="' . $buttonClick . '"';
		}
	}
	if ($buttonType == 'button' && empty($buttonLink)) {
		trigger_error(gettext('buttonType "button" buttons must have a buttonLink;'), E_USER_NOTICE);
	}
	if ($buttonTitle) {
		$buttonTitle = ' Title="' . $buttonTitle . '"';
	}
	if ($id) {
		$id = ' id="' . $id . '"';
	}
	?>
	<button type="<?php echo $buttonType; ?>" class="<?php echo $buttonClass; ?>"<?php echo $id . $buttonTitle . $buttonLink . $disabled . $buttonExtra; ?>>
		<span class="buttonText">
			<?php echo $buttonText; ?>
		</span>
	</button>
	<?php
}

/**
 * shuffles an array maintaining the keys
 *
 * @param array $array
 * @return boolean
 */
function shuffle_assoc(&$array) {
	$keys = array_keys($array);
	shuffle($keys);
	foreach ($keys as $key) {
		$new[$key] = $array[$key];
	}
	$array = $new;
	return true;
}

/**
 * sorts the found albums (images) by the required key(s)
 *
 * NB: this sort is sensitive to the key(s) chosen and makes
 * the appropriate sorts based on same. Some multi-key sorts
 * will not make any sense and will give unexpected results.
 * Most notably any that contain the keys "title" or "desc"
 * as these require multi-lingual sorts.
 *
 * @param array $results
 * @param string $sortkey
 * @param string $order
 */
function sortByKey($results, $sortkey, $order) {
	if (empty($results)) { //	order does not matter if nothing is there
		return $results;
	}
	$sortkey = str_replace('`', '', $sortkey);
	switch ($sortkey) {
		case 'title':
		case 'desc':
			return sortByMultilingual($results, $sortkey, $order);
		case 'RAND()':
			shuffle_assoc($results);
			return $results;
		default:
			if (preg_match('`[\/\(\)\*\+\-!\^\%\<\>\ = \&\|]`', $sortkey)) {
				return $results; //	We cannot deal with expressions
			}
	}
	$indicies = explode(',', $sortkey);
	foreach ($indicies as $key => $index) {
		$indicies[$key] = trim($index);
	}
	$results = sortMultiArray($results, $indicies, $order, true, false, true);
	return $results;
}

/**
 * Sorts an array by column(s)
 *
 * @param array $data							input array
 * @param array $field						column(s) to sort by
 * @param bool $desc							sort descending
 * @param bool $nat								"Natural" comparisons
 * @param bool $case							case insensitive comparisons
 * @param bool $preserveKeys			if set false the array will be re-indexed
 * @param array $removeCriteria		Fields to be removed from the array
 * @return array									The sorted array
 *
 * @Copyright 2016 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 */
function sortMultiArray($data, $field, $desc = false, $nat = true, $case = false, $preserveKeys = true, $removeCriteria = array()) {

	if (!is_array($field)) {
		$field = array($field);
	}

	uasort($data, function ($a, $b) use ($field, $nat, $case) {
		global $coll;
		$retval = 0;
		foreach ($field as $fieldname) {
			if ($retval == 0) {
				switch ($fieldname) {
					case 'title':
					case 'desc':
						$s1 = isset($a[$fieldname]) ? get_language_string($a[$fieldname]) : '';
						$s2 = isset($b[$fieldname]) ? get_language_string($b[$fieldname]) : '';
						break;
					default:
						$s1 = isset($a[$fieldname]) ? $a[$fieldname] : '';
						$s2 = isset($b[$fieldname]) ? $b[$fieldname] : '';
						break;
				}
				$retval = localeCompare($s1, $s2, $nat, $case);
			} else {
				break;
			}
		}
		return $retval;
	});

	if ($desc) {
		$data = array_reverse($data, true);
	}

	if (!empty($removeCriteria)) {
		foreach ($data as $key => $datum) {
			foreach ($removeCriteria as $column) {
				unset($data[$key][$column]);
			}
		}
	}

	if (!$preserveKeys) {
		$data = array_values($data);
	}

	return $data;
}

/**
 * Returns a list of album IDs that the current viewer is not allowed to see
 *
 * @return array
 */
function getNotViewableAlbums() {
	global $_not_viewable_album_list;
	if (npg_loggedin(ADMIN_RIGHTS | MANAGE_ALL_ALBUM_RIGHTS))
		return array(); //admins can see all
	if (is_null($_not_viewable_album_list)) {
		$sql = 'SELECT `folder`, `id` FROM ' . prefix('albums');
		$result = query($sql);
		if ($result) {
			$_not_viewable_album_list = array();
			while ($row = db_fetch_assoc($result)) {
				$album = newAlbum($row['folder'], false, true);
				if (!$album || !$album->exists || !$album->checkAccess()) {
					$_not_viewable_album_list[] = $row['id'];
				}
			}
			db_free_result($result);
		}
	}
	return $_not_viewable_album_list;
}

/**
 * pattern match function Works with characters with diacritical marks where the PHP one does not.
 *
 * @param string $pattern pattern
 * @param string $string haystack
 * @return bool
 */
function safe_fnmatch($pattern, $string) {
	return preg_match('/^' . strtr(addcslashes($pattern, '\\.+^$(){}=!<>|'), array('*' => '.*', '?' => '.?')) . '$/i', $string);
}

/**
 * Returns an i.php "image name" for an image not within the albums structure
 *
 * @param string $image Path to the image
 * @return string
 */
function makeSpecialImageName($image) {
	$filename = basename($image);
	$base = explode('/', replaceScriptPath(dirname($image)));
	$nameBase = $sourceFolder = array_shift($base);
	$sourceSubfolder = implode('/', $base);
	$subFolder = reset($base);

	switch ($nameBase) {
		case USER_PLUGIN_FOLDER:
			$nameBase = USER_PLUGIN_PATH;
			break;
		case THEMEFOLDER:
			break;
		case CORE_FOLDER:
			$nameBase = CORE_PATH;
			if ($subFolder == PLUGIN_FOLDER) {
				$subFolder = PLUGIN_PATH;
			}
			break;
	}
	$nameBase .= '_' . $subFolder;

	return array('source' => $sourceFolder . '/' . $sourceSubfolder . '/' . $filename, 'name' => $nameBase . '_' . basename($sourceSubfolder) . '_' . $filename);
}

/**
 * Returns the watermark image to pass to i.php
 *
 * Note: this should be used for "real" images only since thumbnail handling for Video and TextObjects is special
 * and the "album" thumbnail is not appropriate for the "default" images for those
 *
 * @param $image image object in question
 * @param $use what the watermark use is
 * @return string
 */
function getWatermarkParam($image, $use) {
	$watermark_use_image = $image->getWatermark();
	if (!empty($watermark_use_image) && ($image->getWMUse() & $use)) { //	Use the image defined watermark
		return $watermark_use_image;
	}
	$id = NULL;
	$album = $image->album;
	switch ($use) {
		case WATERMARK_FULL:
			$watermark_use_image = getAlbumInherited($album->name, 'watermark', $id);
			if (empty($watermark_use_image)) {
				$watermark_use_image = FULLIMAGE_WATERMARK;
			}
			break;
		case WATERMARK_IMAGE:
			$watermark_use_image = getAlbumInherited($album->name, 'watermark', $id);
			if (empty($watermark_use_image)) {
				$watermark_use_image = IMAGE_WATERMARK;
			}
			break;
		case WATERMARK_THUMB:
			$watermark_use_image = getAlbumInherited($album->name, 'watermark_thumb', $id);
			if (empty($watermark_use_image)) {
				$watermark_use_image = THUMB_WATERMARK;
			}
			break;
	}

	if (!empty($watermark_use_image)) {
		return $watermark_use_image;
	}
	return NO_WATERMARK; //	apply no watermark
}

/**
 * Copies a directory recursively
 * @param string $srcdir the source directory.
 * @param string $dstdir the destination directory.
 * @return the total number of files copied.
 */
function dircopy($srcdir, $dstdir) {
	$num = 0;
	if (!is_dir($dstdir))
		mkdir($dstdir);
	if ($curdir = opendir($srcdir)) {
		while ($file = readdir($curdir)) {
			if ($file != '.' && $file != '..') {
				$srcfile = $srcdir . '/' . $file;
				$dstfile = $dstdir . '/' . $file;
				if (is_file($srcfile)) {
					if (is_file($dstfile))
						$ow = filemtime($srcfile) - filemtime($dstfile);
					else
						$ow = 1;
					if ($ow > 0) {
						if (copy($srcfile, $dstfile)) {
							touch($dstfile, filemtime($srcfile));
							$num++;
						}
					}
				} else if (is_dir($srcfile)) {
					$num += dircopy($srcfile, $dstfile);
				}
			}
		}
		closedir($curdir);
	}
	return $num;
}

/**
 * Returns a byte size from a size value (eg: 100M).
 *
 * @param int $bytes
 * @return string
 */
function byteConvert($bytes) {
	if ($bytes <= 0)
		return gettext('0 Bytes');
	$convention = 1024; //[1000->10^x|1024->2^x]
	$s = array('Bytes', 'kB', 'mB', 'GB', 'TB', 'PB', 'EB', 'ZB');
	$e = floor(log($bytes, $convention));
	return round($bytes / pow($convention, $e), 2) . ' ' . $s[$e];
}

/**
 * Converts a datetime to connoical form
 *
 * @param string $datetime input date/time string
 * @param bool $raw set to true to return the timestamp otherwise you get a string
 * @return mixed
 */
function dateTimeConvert($datetime, $raw = false) {
	// Convert 'yyyy:mm:dd hh:mm:ss' to 'yyyy-mm-dd hh:mm:ss' for Windows' strtotime compatibility
	$datetime = preg_replace('/(\d{4}):(\d{2}):(\d{2})/', ' \1-\2-\3', $datetime);
	$time = strtotime($datetime);
	if ($time == -1 || $time === false)
		return NULL;
	if ($raw)
		return $time;
	return date('Y-m-d H:i:s', $time);
}

/* * * Context Manipulation Functions ****** */
/* * *************************************** */

/* Contexts are simply constants that tell us what variables are available to us
 * at any given time. They should be set and unset with those variables.
 */

function get_context() {
	global $_current_context;
	return $_current_context;
}

function set_context($context) {
	global $_current_context;
	$_current_context = $context;
}

function in_context($context) {
	return get_context() & $context;
}

function add_context($context) {
	set_context(get_context() | $context);
}

function rem_context($context) {
	global $_current_context;
	set_context(get_context() & ~$context);
}

// Use save and restore rather than add/remove when modifying contexts.
function save_context() {
	global $_current_context, $_current_context_stack;
	array_push($_current_context_stack, $_current_context);
}

function restore_context() {
	global $_current_context, $_current_context_stack;
	$_current_context = array_pop($_current_context_stack);
}

/**
 * Cleans tags and some content.
 * @param type $content
 * @return type
 */
function getBare($content) {
	return kses($content, array());
}

/**
 *
 * Sanitizes a "redirect" post Note: redirects are forced to be within the site
 *
 * @param string $redirectTo
 * @return string
 */
function sanitizeRedirect($redirectTo) {
	$redir = mb_parse_url($redirectTo);
	if (isset($redir['path'])) {
		$redirect = urldecode(sanitize($redir['path']));
	} else {
		$redirect = '/';
	}
	if (WEBPATH && strpos($redirect, WEBPATH) !== 0) {
		$redirect = FULLWEBPATH . $redirect;
	} else {
		$redirect = FULLHOSTPATH . $redirect;
	}
	if (isset($redir['query'])) {
		$redirect .= '?' . sanitize($redir['query']);
	}
	if (isset($redir['fragment'])) {
		$redirect .= '#' . sanitize($redir['fragment']);
	}
	return $redirect;
}

/**
 * checks password posting or existing password cookie
 *
 * @param string $authType override of athorization type
 *
 * @return bool true if authorized
 */
function handle_password($authType = NULL, $check_auth = NULL, $check_user = NULL) {
	global $_loggedin, $_login_error, $_current_album, $_CMS_current_page, $_CMS_current_category, $_CMS_current_article, $_gallery;
	$success = false;
	if (empty($authType)) { // not supplied by caller
		$check_auth = '';
		$auth = array();
		if (isset($_GET['z']) && isset($_GET['p']) && ($_GET['p'] == 'full-image' || isset($_GET['p']) && $_GET['p'] == '*full-image')) {
			$authType = 'image_auth';
			$check_auth = getOption('protected_image_password');
			$check_user = getOption('protected_image_user');
			$auth = array(array('authType' => $authType, 'check_auth' => $check_auth, 'check_user' => $check_user));
		} else if (in_context(NPG_SEARCH)) { // search page
			$authType = 'search_auth';
			$check_auth = getOption('search_password');
			$check_user = getOption('search_user');
			$auth = array(array($authType, $check_auth, $check_user));
		} else if (in_context(NPG_ALBUM)) { // album page
			$authType = "album_auth_" . $_current_album->getID();
			$check_auth = $_current_album->getPassword();
			$check_user = $_current_album->getUser();
			if (empty($check_auth)) {
				$parent = $_current_album->getParent();
				while (!is_null($parent)) {
					$check_auth = $parent->getPassword();
					$check_user = $parent->getUser();
					$authType = "album_auth_" . $parent->getID();
					if (!empty($check_auth)) {
						break;
					}
					$parent = $parent->getParent();
				}
			}
			$auth = array(array('authType' => $authType, 'check_auth' => $check_auth, 'check_user' => $check_user));
		} else if (in_context(ZENPAGE_PAGE)) {
			$authType = "zenpage_page_auth_" . $_CMS_current_page->getID();
			$check_auth = $_CMS_current_page->getPassword();
			$check_user = $_CMS_current_page->getUser();
			if (empty($check_auth)) {
				$pageobj = $_CMS_current_page;
				while (empty($check_auth)) {
					$parentID = $pageobj->getParentID();
					if ($parentID == 0)
						break;
					$pageobj = getItemByID('pages', $parentID);
					if ($pageobj) {
						$authType = "zenpage_page_auth_" . $pageobj->getID();
						$check_auth = $pageobj->getPassword();
						$check_user = $pageobj->getUser();
					}
				}
			}
			$auth = array(array('authType' => $authType, 'check_auth' => $check_auth, 'check_user' => $check_user));
		} else if (in_context(ZENPAGE_NEWS_CATEGORY) || in_context(ZENPAGE_NEWS_ARTICLE)) {
			if (in_context(ZENPAGE_NEWS_CATEGORY)) {
				$categories = array(array('titlelink' => $_CMS_current_category->getTitleLink()));
			} else {
				$categories = $_CMS_current_article->getCategories();
			}
			foreach ($categories as $category) {
				$cat = newCategory($category['titlelink']);
				while ($cat) { // all categories in the family
					$check_auth = $cat->getPassword();
					if (!empty($check_auth)) {
						$authType = 'zenpage_category_auth_' . $cat->getID();
						$check_user = $cat->getUser();
						$auth[] = array('authType' => $authType, 'check_auth' => $check_auth, 'check_user' => $check_user);
						break; //	only the deepest password is required
					}
					$parentID = $cat->getParentID();
					if ($parentID) {
						$cat = getItemByID('news_categories', $parentID);
					} else {
						$cat = NULL;
					}
				}
			}
		}
		if (empty($auth)) { // anything else is controlled by the gallery credentials
			$authType = 'gallery_auth';
			$check_auth = $_gallery->getPassword();
			$check_user = $_gallery->getUser();
			$auth = array(array('authType' => $authType, 'check_auth' => $check_auth, 'check_user' => $check_user));
		}
	} else {
		$auth = array(array('authType' => $authType, 'check_auth' => $check_auth, 'check_user' => $check_user));
	}

	// Handle the login form.
	if (DEBUG_LOGIN) {
		debugLogVar(["handle_password:" => $auth]);
	}

	if (isset($_POST['password']) && isset($_POST['pass'])) { // process login form
		if (isset($_POST['user'])) {
			$post_user = sanitize($_POST['user'], 0);
		} else {
			$post_user = '';
		}
		$post_pass = sanitize($_POST['pass'], 0);
		if (!empty($auth) && !empty($post_pass)) {

			foreach ($auth as $try) {
				$authType = $try['authType'];
				$check_auth = $try['check_auth'];
				$check_user = $try['check_user'];
				$info = password_get_info($check_auth);
				if ($info['algo']) {
					$success = password_verify($post_pass, $check_auth);
					$authCookie = $check_auth;
					if (DEBUG_LOGIN)
						debugLog("handle_password($success): \$post_user=$post_user; \$post_pass=$post_pass; \$check_auth=$check_auth; \$auth=$authCookie;");
					break;
				}

				if (!isset($alternates)) {
					$alternates = array();
					for ($hi = 0; $hi <= 3; $hi++) {
						if ($h = npg_Authority::passwordHash($post_user, $post_pass, $hi, false)) {
							array_push($alternates, $h);
						}
					}
				}
				foreach ($alternates as $authCookie) {
					if ($post_user == $check_user) {
						$success = ($authCookie == $check_auth);
						if (DEBUG_LOGIN)
							debugLog("handle_password($success): \$post_user=$post_user; \$post_pass=$post_pass; \$check_auth=$check_auth; \$auth=$authCookie;");
						if ($success) {
							break 2;
						}
					}
				}
			}

			$success = npgFilters::apply('guest_login_attempt', $success, $post_user, $post_pass, $authType);

			if ($success) {
				// Correct auth info. Set the cookie.
				if (DEBUG_LOGIN)
					debugLog("handle_password: valid credentials");
				setNPGCookie($authType, $authCookie);
				if (isset($_POST['redirect'])) {
					$redirect_to = sanitizeRedirect($_POST['redirect']);
					if (!empty($redirect_to)) {
						header("Location: " . $redirect_to);
						exit();
					}
				}
			} else {
				// Clear the cookie, just in case
				if (DEBUG_LOGIN)
					debugLog("handle_password: invalid credentials");
				clearNPGCookie($authType);
				$_login_error = true;
			}
			return $success;
		}
	}
	if (empty($check_auth)) { //no password on record or admin logged in
		return true;
	}

	foreach ($auth as $try) {
		$authType = $try['authType'];
		$saved_auth = getNPGCookie($authType);
		if ($saved_auth != '') {
			if ($saved_auth == $check_auth) {
				if (DEBUG_LOGIN)
					debugLog("handle_password: valid cookie");
				return true;
			} else {
				// Clear the cookie
				if (DEBUG_LOGIN)
					debugLog("handle_password: invalid cookie");
				clearNPGCookie($authType);
			}
		}
	}

	return false;
}

/**
 *
 * Gets an option directly from the database.
 * @param string $key
 */
function getOptionFromDB($key) {
	$sql = "SELECT `value` FROM " . prefix('options') . " WHERE `name`=" . db_quote($key) . " AND `ownerid`=0";
	if ($optionlist = query_single_row($sql, false)) {
		return $optionlist['value'];
	}
	return NULL;
}

/**
 * Set options local to theme and/or album
 *
 * @param string $key
 * @param string $value
 * @param object $album
 * @param string $theme default theme
 * @param bool $default set to true for setting default theme options (does not set the option if it already exists)
 * @param string $creator the caller of setThemeOptionDefault()
 */
function setThemeOption($key, $value, $album = NULL, $theme = NULL, $default = false, $creator = NULL) {
	global $_options;
	if (is_null($album)) {
		$id = 0;
	} else {
		$id = $album->getID();
		$theme = $album->getAlbumTheme();
	}
	if (!$creator) {
		list($th, $cr) = getOptionOwner();
		if (is_null($theme) || $theme == basename($th)) {
			$theme = $th;
			$creator = $cr;
		} else { // core functions in behalf of the theme
			$creator = THEMEFOLDER . '/' . $theme . '[' . $cr . ']';
		}
	}


	$sql = 'INSERT INTO ' . prefix('options') . ' (`name`,`ownerid`,`theme`,`creator`,`value`) VALUES (' . db_quote($key) . ',' . $id . ',' . db_quote($theme) . ',' . db_quote($creator) . ',';
	if (is_null($value)) {
		$value_db = 'NULL';
	} else {
		if (is_bool($value)) {
			$value = (int) $value;
		}
		$value_db = db_quote($value);
	}
	$sql .= $value_db . ')' . ' ON DUPLICATE KEY UPDATE ';
	if ($default) {
		$sql .= '`theme`=' . db_quote($theme) . ', `creator`=' . db_quote($creator) . ';';
	} else {
		$sql .= '`value` = ' . $value_db . ';';
	}
	query($sql, false);

	if (!$default || !isset($_options[strtolower($key)]) || is_null($_options[strtolower($key)])) {
		$_options[strtolower($key)] = $value;
	}
}

/**
 * Used to set default values for theme specific options
 *
 * @param string $key
 * @param mixed $value
 */
function setThemeOptionDefault($key, $value) {
	list($theme, $creator) = getOptionOwner();
	setThemeOption($key, $value, NULL, $theme, true, $creator);
}

/**
 * Returns the value of a theme option
 *
 * @param string $option option key
 * @param object $album
 * @param string $theme default theme name
 * @return mixed
 */
function getThemeOption($option, $album = NULL, $theme = NULL) {
	global $_set_theme_album, $_gallery;
	if (is_null($album)) {
		$album = $_set_theme_album;
	}
	if (is_null($album)) {
		$id = 0;
	} else {
		$id = $album->getID();
		$theme = $album->getAlbumTheme();
	}
	if (empty($theme)) {
		$theme = $_gallery->getCurrentTheme();
	}
	// album-theme order of preference is: Album theme => Theme => album => general
	$sql = "SELECT `name`, `value`, `ownerid`, `theme` FROM " . prefix('options') . " WHERE `name`=" . db_quote($option) . " AND (`ownerid`=" . $id . " OR `ownerid`=0) AND (`theme`=" . db_quote($theme) . ' OR `theme` = "") ORDER BY `theme` DESC, `id` DESC';
	$db = query_single_row($sql);
	if (empty($db)) {
		return NULL;
	} else {
		return $db['value'];
	}
}

/**
 * Returns a list of database tables for the installation
 * @return type
 */
function getDBTables() {
	$tables = array();
	$prefix = trim(prefix(), '`');
	$resource = db_show('tables');
	if ($resource) {
		$result = array();
		while ($row = db_fetch_assoc($resource)) {
			$table = reset($row);
			$table = substr($table, strlen($prefix));
			$tables[] = $table;
		}
		db_free_result($resource);
	}
	return $tables;
}

/**
 * Strips out and/or replaces characters from the string that are not "soe" friendly
 *
 * @param string $string
 * @return string
 */
function seoFriendly($string) {
	$string = trim(preg_replace('~\s+\.\s*~', '.', $string));
	if (npgFilters::has_filter('seoFriendly')) {
		$string = npgFilters::apply('seoFriendly', $string);
	} else { // no filter, do basic cleanup
		$string = preg_replace("/[^a-zA-Z0-9_.-]/", "-", $string);
	}
	$string = preg_replace("/\s+/", "-", $string);
	$string = preg_replace('/--+/', '-', $string);
	return $string;
}

/**
 *
 * emit the javascript seojs() function
 */
function seoFriendlyJS() {
	?>
	<script type="text/javascript">
		function seoFriendlyJS(fname) {
			fname = fname.trim();
			fname = fname.replace(/\s+\.\s*/, '.');
	<?php
	if (npgFilters::has_filter('seoFriendly_js')) {
		?>
		<?php
		echo npgFilters::apply('seoFriendly_js', '');
	} else { // no filter, do basic cleanup
		?>
				fname = fname.replace(/[^a-zA-Z0-9_.-]/g, '-');
		<?php
	}
	?>
			fname = fname.replace(/\s+/g, '-');
			fname = fname.replace(/--+/g, '-');
			return fname;
		}
	</script>
	<?php
}

/**
 *
 * General handler for generating HTML for loading css and js files
 *
 * The funcation checks the size of the file. If it is "small" it loads the script
 * in-line. Otherwise it uses the normal HTML script loading syntax. BUT, it will
 * append a release unique tag to the URL to avoid caching issues.
 *
 * @param string $script
 * @param bool $inline	set to FALSE to prevent rendering the script in-line, for
 * 											instance if the script has self relative url links.
 *                      set to TRUE to force rendering the script in-line
 *
 */
function scriptLoader($script, $inline = 1) {
	$script = str_replace('\\', '/', $script); //	in case strip_suffix(__FILE__) is passed
	if (strpos($script, SERVERPATH) === false) {
		if (strpos($script, FULLWEBPATH) === 0) {
			$script = SERVERPATH . substr($script, strlen(FULLWEBPATH));
		} else {
			$script = SERVERPATH . substr($script, strlen(WEBPATH));
		}
	}

	$scriptFS = internalToFilesystem($script);
	if ($inline && file_exists($scriptFS)) {
		if (filesize($scriptFS) < INLINE_LOAD_THRESHOLD || is_bool($inline)) {
			$content = file_get_contents($scriptFS);
			$found = preg_match_all('~url\s*\((.+)\)~i', $content, $matches);
			if ($found) {
				$serverbase = dirname($script) . '/';
				$webbase = str_replace(SERVERPATH, WEBPATH, $serverbase);
				foreach ($matches[1] as $key => $match) {
					if (file_exists($serverbase . $match)) {
						$found--;
						$content = str_replace($matches[0][$key], 'url(' . $webbase . $match . ')', $content);
					} else {
						break;
					}
				}
			}
			if (!$found) { //	no unresolved self relative links
				$content = preg_replace('~/\*[^*]*\*+([^/][^*]*\*+)*/~', '', $content);
				$content = preg_replace('~[^\S\r\n]+~', ' ', $content) . "\n";
				$content = preg_replace('~[\r\n]+~', "\n", $content);
				if (getSuffix($scriptFS) == 'css') {
					echo "\n" . '<style type="text/css">/* src="' . str_replace(SERVERPATH . '/', '', $script) . '" */';
					$content = str_replace(': ', ':', $content);
					echo $content;
					echo "</style>\n";
				} else {
					echo "\n" . '<script type="text/javascript">/* src="' . str_replace(SERVERPATH . '/', '', $script) . '" */';
					echo $content;
					echo "</script>\n";
				}
				return;
			}
		}
	}
	$script = str_replace(SERVERPATH, FULLWEBPATH, $script);
	$version = NETPHOTOGRAPHICS_VERSION_CONCISE;
	if (TESTING_MODE) {
		$version .= '.' . time();
	}
	if (getSuffix($script) == 'css') {
		echo '<link rel="stylesheet" href="' . pathurlencode($script) . '?npg' . $version . '" type="text/css" />' . "\n";
	} else {
		echo '<script src="' . pathurlencode($script) . '?npg' . $version . '" type="text/javascript"></script>' . "\n";
	}
}

function load_jQuery_CSS() {
	scriptLoader(CORE_SERVERPATH . 'js/jQueryui/jquery-ui-1.12.min.css');
	scriptLoader(CORE_SERVERPATH . 'js/jQueryui/base-1.12.min.css');
}

function load_jQuery_scripts($where, $ui = true) {
	switch (getOption('jQuery_Migrate_' . $where)) {
		default:
		case 0: //	no migration script
			scriptLoader(CORE_SERVERPATH . 'js/jQuery/jquery-3.6.0.min.js');
			break;
		case 1: //	production version
			scriptLoader(CORE_SERVERPATH . 'js/jQuery/jquery-3.6.0.min.js');
			?>
			<!-- for production purposes -->
			<?php
			scriptLoader(CORE_SERVERPATH . 'js/jQuery/jquery-migrate-3.3.2.min.js');
			break;
		case 2: //	debug version
			scriptLoader(CORE_SERVERPATH . 'js/jQuery/jquery-3.6.0.min.js');
			?>
			<!-- for migration to jQuery 3.0 purposes -->
			<?php
			scriptLoader(CORE_SERVERPATH . 'js/jQuery/jquery-migrate-3.3.2.js');
			break;
		case 3: //	use legacy jQuery
			?>
			<!-- for migration to jQuery 1.9+ purposes -->
			<?php
			scriptLoader(CORE_SERVERPATH . 'js/jQuery/jquery-1.12.4.min.js');
			scriptLoader(CORE_SERVERPATH . 'js/jQuery/jquery-migrate-1.4.1.js');
			break;
	}
	if ($ui) {
		scriptLoader(CORE_SERVERPATH . 'js/jQueryui/jquery-ui-1.12.1.min.js');
	}
}

/**
 * encodes a pre-sanitized string to be used as a Javascript parameter
 *
 * @param string $this_string
 * @return string
 */
function js_encode($this_string) {
	$this_string = preg_replace("/\r?\n/", "\\n", $this_string);
	$this_string = utf8::encode_javascript($this_string);
	return $this_string;
}

/**
 * returns an XSRF token
 * @param string $action
 * @param string $modifier optional extra data. Use, for instance to include
 * 																							parts of URL being used for more security
 */
function getXSRFToken($action, $modifier = NULL) {
	return sha1($action . $modifier . npg_SID);
}

/**
 * Emits a "hidden" input for the XSRF token
 * @param string $action
 * @param string $modifier optional extra data. Use, for instance to include
 * 																							parts of URL being used for more security
 */
function XSRFToken($action, $modifier = NULL) {
	?>
	<input type="hidden" name="XSRFToken" class="XSRFToken" value="<?php echo getXSRFToken($action, $modifier); ?>" />
	<?php
}

/**
 *
 * Checks if protocol not https and redirects if https required
 */
function httpsRedirect() {
	global $_conf_vars;
	if (getNPGCookie('ssl_state') || isset($_conf_vars['server_protocol']) && $_conf_vars['server_protocol'] == 'https') {
		// force https
		if (!isset($_SERVER["HTTPS"]) || strtolower($_SERVER["HTTPS"]) == 'off') {
			npg_session_destroy();
			$redirect = "https://" . $_SERVER['HTTP_HOST'] . getRequestURI();
			header("Location:$redirect");
			exit();
		}
	}
}

/**
 * Starts a sechedule script run
 * @param string $script The script file to load
 * @param array $params "POST" parameters
 * @param bool $inline set to true to run the task "in-line". Set false run asynchronously
 */
function cron_starter($script, $params, $offsetPath, $inline = false) {
	global $_authority, $_loggedin, $_current_admin_obj, $_HTML_cache;
	$admin = $_authority->getMasterUser();
	if ($admin) {
		if ($inline) {
			$_current_admin_obj = $admin;
			$_loggedin = $_current_admin_obj->getRights();
			foreach ($params as $key => $value) {
				if ($key == 'XSRFTag') {
					$key = 'XSRFToken';
					$value = getXSRFToken($value);
				}
				$_POST[$key] = $_GET[$key] = $_REQUEST[$key] = $value;
			}
			require_once($script);
		} else {
			$auth = sha1($script . serialize($admin));
			$paramlist = 'link=' . $script;
			foreach ($params as $key => $value) {
				$paramlist .= '&' . $key . '=' . $value;
			}
			$paramlist .= '&auth=' . $auth . '&offsetPath=' . $offsetPath;
			$_HTML_cache->abortHTMLCache(true);
			?>
			<script type="text/javascript" >
				// <!-- <![CDATA[
				$.ajax({
					type: 'POST',
					cache: false,
					data: '<?php echo $paramlist; ?>',
					url: '<?php echo FULLWEBPATH . '/' . CORE_FOLDER . '/cron_runner.php' ?>'
				});
				// ]]> -->
			</script>
			<?php
		}
	}
}

/**
 *
 * Check if logged in (with specific rights)
 * Returns a true value if there is a user logged on with the required rights
 *
 * @param bit $rights rights required by the caller
 *
 * @return bool
 */
function npg_loggedin($rights = ALL_RIGHTS) {
	global $_loggedin;
	return $_loggedin & ($rights | ADMIN_RIGHTS);
}

/**
 * Provides an error protected read of image EXIF/IPTC data
 *
 * @param string $path image path
 * @return array
 *
 */
function read_exif_data_protected($path) {
	if (DEBUG_EXIF) {
		debugLog("Begin read_exif_data_protected($path)");
		$start = microtime(true);
	}
	try {
		$rslt = read_exif_data_raw($path, false);
	} catch (Exception $e) {
		debugLog("read_exif_data($path) exception: " . $e->getMessage());
		$rslt = array();
	}
	if (DEBUG_EXIF) {
		$time = microtime(true) - $start;
		debugLog(sprintf("End read_exif_data_protected($path) [%f]", $time));
	}
	return $rslt;
}

/**
 *
 * fetches the path to the flag image
 * @param string $lang whose flag
 * @return string
 */
function getLanguageFlag($lang) {
	if (file_exists(USER_PLUGIN_SERVERPATH . 'locale/' . $lang . '/flag.png')) {
		$flag = WEBPATH . '/' . USER_PLUGIN_FOLDER . '/locale/' . $lang . '/flag.png';
	} else if (file_exists(CORE_SERVERPATH . 'locale/' . $lang . '/flag.png')) {
		$flag = WEBPATH . '/' . CORE_FOLDER . '/locale/' . $lang . '/flag.png';
	} else {
		$flag = WEBPATH . '/' . CORE_FOLDER . '/locale/missing_flag.png';
	}
	return $flag;
}

/**
 * Gets an item object by id
 *
 * @param string $table database table to search
 * @param int $id id of the item to get
 * @param bool $quiet set to false to supress error messages
 * @return mixed
 */
function getItemByID($table, $id, $quiet = true) {
	if ($result = query_single_row('SELECT * FROM ' . prefix($table) . ' WHERE id=' . (int) $id)) {
		switch ($table) {
			case 'images':
				if ($alb = getItemByID('albums', $result['albumid'], false, $quiet)) {
					$obj = newImage($alb, $result['filename'], $quiet);
				} else {
					$obj = NULL;
				}
				break;
			case 'albums':
				$obj = newAlbum($result['folder'], false, $quiet);
				break;
			case 'news':
				$obj = newArticle($result['titlelink'], false);
				break;
			case 'pages':
				$obj = newPage($result['titlelink'], false);
				break;
			case 'news_categories':
				$obj = new Category($result['titlelink'], false);
				break;
		}
		if ($obj && $obj->loaded) {
			return $obj;
		}
	}
	return NULL;
}

/**
 * Deals with the [macro parameters] substitutions
 *
 * See the macroList plugin for details
 *
 * @param string $text
 * @return string
 */
function applyMacros($text) {
	global $_UTF8;
	$content_macros = getMacros();
	preg_match_all('/\[(\w+)(.*?)\]/i', $text, $instances);

	foreach ($instances[0] as $instance => $macro_instance) {
		$macroname = strtoupper($instances[1][$instance]);
		if (array_key_exists($macroname, $content_macros)) {
			$macro = $content_macros[$macroname];
			$p = $instances[2][$instance];
			$data = NULL;
			$class = $macro['class'];
			if ($p) {
				$p = trim($_UTF8->sanitize(str_replace("\xC2\xA0", ' ', strip_tags($p)))); //	remove hard spaces and invalid characters
				$p = preg_replace("~\s+=\s+(?=(?:[^\"]*+\"[^\"]*+\")*+[^\"]*+$)~", "=", $p); //	deblank assignment operator
				preg_match_all("~'[^'\"]++'|\"[^\"]++\"|[^\s]++~", $p, $l); //	parse the parameter list
				$parms = array();
				$k = 0;
				foreach ($l[0] as $s) {
					if ($s != ',') {
						$parms[$k++] = trim($s, ',\'"'); //	remove any quote marks
					}
				}
			} else {
				$parms = array();
			}
			$parameters = array();
			if (!empty($macro['params'])) {
				$err = false;
				foreach ($macro['params'] as $key => $type) {
					$data = false;
					if (array_key_exists($key, $parms)) {
						switch (trim($type, '*')) {
							case 'int':
								if (is_numeric($parms[$key])) {
									$parameters[] = (int) $parms[$key];
								} else {
									$data = '<span class="error">' . sprintf(gettext('<em>[%1$s]</em> parameter %2$d should be a number.'), trim($macro_instance, '[]'), $key + 1) . '</span>';
									$class = 'error';
								}
								break;
							case 'string':
								if (is_string($parms[$key])) {
									$parameters[] = $parms[$key];
								} else {
									$data = '<span class="error">' . sprintf(gettext('<em>[%1$s]</em> parameter %2$d should be a string.'), trim($macro_instance, '[]'), $key + 1) . '</span>';
									$class = 'error';
								}
								break;
							case 'bool':
								switch (strtolower($parms[$key])) {
									case ("true"):
										$parameters[] = true;
										break;
									case ("false"):
										$parameters[] = false;
										break;
									default:
										$data = '<span class="error">' . sprintf(gettext('<em>[%1$s]</em> parameter %2$d should be <code>true</code> or <code>false</code>.'), trim($macro_instance, '[]'), $key + 1) . '</span>';
										$class = 'error';
										break;
								}
								break;
							case 'array':
								$l = array_slice($parms, $key);
								$parms = array();
								foreach ($l as $key => $p) {
									$x = explode('=', $p);
									if (count($x) == 2) {
										$parms[$x[0]] = $x[1];
									} else {
										$parms[$key] = $x[0];
									}
								}
								$parameters[] = $parms;
								break;
							default:
								$data = '<span class="error">' . sprintf(gettext('<em>[%1$s]</em> parameter %2$d is incorrectly defined.'), trim($macro_instance, '[]'), $key + 1) . '</span>';
								$class = 'error';
								break;
						}
					} else {
						if (strpos($type, '*') === false) {
							$data = '<span class="error">' . sprintf(gettext('<em>[%1$s]</em> parameter %2$d is missing.'), trim($macro_instance, '[]'), $key + 1) . '</span>';
							$class = 'error';
						}
						break;
					}
				}
			} else {
				if (!empty($p)) {
					$class = 'error';
					$data = '<span class="error">' . sprintf(gettext('<em>[%1$s]</em> macro does not take parameters'), trim($macro_instance, '[]')) . '</span>';
				}
			}
			switch ($class) {
				case 'error':
					break;
				case 'function';
				case 'procedure':
					if (is_callable($macro['value'])) {
						if ($class == 'function') {
							ob_start();
							$data = call_user_func_array($macro['value'], $parameters);
							$output = ob_get_clean();
							if (is_null($data)) {
								$data = $output;
							}
						} else {
							ob_start();
							call_user_func_array($macro['value'], $parameters);
							$data = ob_get_clean();
							if (empty($data)) {
								$data = NULL;
							}
						}
						if (is_null($data)) {
							$data = '<span class="error">' . sprintf(gettext('<em>[%1$s]</em> retuned no data'), trim($macro_instance, '[]')) . '</span>';
						} else {
							$data = "\n<!--Begin " . $macroname . "-->\n" . $data . "\n<!--End " . $macroname . "-->\n";
						}
					} else {
						$data = '<span class="error">' . sprintf(gettext('<em>[%1$s]</em> <code>%2$s</code> is not callable'), trim($macro_instance, '[]'), $macro['value']) . '</span>';
					}
					break;
				case 'constant':
					$data = "\n<!--Begin " . $macroname . "-->\n" . $macro['value'] . "\n<!--End " . $macroname . "-->\n";
					break;
				case 'expression':
					$expression = '$data = ' . $macro['value'];
					$parms = array_reverse($parms, true);
					preg_match_all('/\$\d+/', $macro['value'], $replacements);
					foreach ($replacements as $rkey => $v) {
						if (empty($v))
							unset($replacements[$rkey]);
					}
					if (count($parms) == count($replacements)) {

						foreach ($parms as $key => $value) {
							$key++;
							$expression = preg_replace('/\$' . $key . '/', db_quote($value), $expression);
						}
						eval($expression);
						if (!isset($data) || is_null($data)) {
							$data = '<span class="error">' . sprintf(gettext('<em>[%1$s]</em> retuned no data'), trim($macro_instance, '[]')) . '</span>';
						} else {
							$data = "\n<!--Begin " . $macroname . "-->\n" . $data . "\n<!--End " . $macroname . "-->\n";
						}
					} else {
						$data = '<span class="error">' . sprintf(ngettext('<em>[%1$s]</em> takes %2$d parameter', '<em>[%1$s]</em> takes %2$d parameters', count($replacements)), trim($macro_instance, '[]'), count($replacements)) . '</span>';
					}
					break;
			}
			$text = str_replace($macro_instance, $data, $text);
		}
	}
	return $text;
}

function getMacros() {
	global $_content_macros;
	if (is_null($_content_macros)) {
		$_content_macros = npgFilters::apply('content_macro', array());
	}
	return $_content_macros;
}

if (class_exists('tidy')) {

	function cleanHTML($html) {
		$tidy = new tidy();
		$tidy->parseString($html, array('preserve-entities' => TRUE, 'indent' => TRUE, 'markup' => TRUE, 'show-body-only' => TRUE, 'wrap' => 0, 'quote-marks' => TRUE), 'utf8');
		$tidy->cleanRepair();
		return $tidy;
	}

} else {
	require_once(CORE_SERVERPATH . 'htmLawed.php');

	function cleanHTML($html) {
		//htmLawed does not deal well with non-breaking spaces, so replace them with the html entity
		$html = str_replace(html_entity_decode('&nbsp;'), '&nbsp;', $html);
		return htmLawed($html, array('tidy' => '2s2n', 'unique_ids' => 0, 'style_pass' => 1));
	}

}

class npgFunctions {

	/**
	 *
	 * creates an SEO language prefix list
	 */
	static function LanguageSubdomains() {
		$domains = array();
		$langs = i18n::generateLanguageList();
		$domains = array();
		foreach ($langs as $value) {
			$domains[substr($value, 0, 2)][] = $value;
		}
		$langs = array();
		foreach ($domains as $simple => $full) {
			if (count($full) > 1) {
				foreach ($full as $loc) {
					$langs[$loc] = $loc;
				}
			} else {
				$langs[$full[0]] = $simple;
			}
		}
		return $langs;
	}

	/**
	 * Returns a canonical language name string for the location
	 *
	 * @param string $loc the location. If NULL use the current cookie
	 * @param string separator will be used between the major and qualifier parts, e.g. en_US
	 *
	 * @return string
	 */
	static function getLanguageText($loc = NULL, $separator = NULL) {
		global $_locale_Subdomains;
		if (is_null($loc)) {
			$loc = getNPGCookie('dynamic_locale');
		}
		if (isset($_locale_Subdomains[$loc])) {
			$text = $_locale_Subdomains[$loc];
		} else {
			$text = '';
		}

		if (!is_null($separator)) {
			$text = str_replace('_', $separator, $text);
		}
		return $text;
	}

	/**
	 * initializes the $_exifvars array display state
	 *
	 * @author Stephen Billard
	 * @Copyright 2015 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
	 */
	static function exifvars($default = false) {
		global $_images_classes;

		/*
		 * Note: If fields are added or deleted, setup should be run or the new data won't be stored
		 * (but existing fields will still work; nothing breaks).
		 *
		 * This array should be ordered by logical associations as it will be the order that EXIF information
		 * is displayed
		 */
		$exifvars = array();
		$handlers = array_unique($_images_classes);
		$handlers[] = 'xmpMetadata';
		foreach ($handlers as $handler) {
			if (class_exists($handler)) {
				$exifvars = array_merge($exifvars, $handler::getMetadataFields());
			}
		}
		$exifvars = sortMultiArray($exifvars, 2, false, true, false, true);
		if ($default) {
			return $exifvars;
		}

		$disable = getSerializedArray(getOption('metadata_disabled'));
		$display = getSerializedArray(getOption('metadata_displayed'));
		foreach ($exifvars as $key => $item) {
			if (in_array($key, $disable)) {
				$exifvars[$key][EXIF_DISPLAY] = $exifvars[$key][EXIF_FIELD_ENABLED] = false;
			} else {
				$exifvars[$key][EXIF_DISPLAY] = isset($display[$key]);
				$exifvars[$key][EXIF_FIELD_ENABLED] = true;
			}
		}
		return $exifvars;
	}

	/**
	 * handler for "image" plugin metadata enable/disable
	 * @param string $whom
	 * @param int $disable
	 * @param array $list
	 *
	 * @author Stephen Billard
	 * @Copyright 2015 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
	 */
	static function exifOptions($whom, $disable, $list) {
		$reenable = false;
		$disabled = getSerializedArray(getOption('metadata_disabled'));

		foreach ($list as $key => $exifvar) {
			$v = $exifvar[5] = in_array($key, $disabled);
			if ($exifvar[4] && $v != $disable) {
				$reenable = true;
			}
			if ($disable | $v) {
				$disabled[$key] = $key;
			} else {
				unset($disabled[$key]);
			}
			$list[$key][5] = $disable == 0;
		}
		setOption('metadata_disabled', serialize($disabled));

		if (OFFSET_PATH == 2) {
			metadataFields($list);
		} else {
			if ($reenable) {
				requestSetup($whom);
			}
		}
	}

	/**
	 *
	 * Returns true if the install is not a "clone"
	 */
	static function hasPrimaryScripts() {
		if (!defined('PRIMARY_INSTALLATION')) {
			if (function_exists('readlink') && is_link(SERVERPATH . '/' . CORE_FOLDER)) {
				define('PRIMARY_INSTALLATION', false);
			} else {
				define('PRIMARY_INSTALLATION', true);
			}
		}
		return PRIMARY_INSTALLATION;
	}

	/**
	 *
	 * Recursively clears and removes a folder
	 * @param string $path
	 * @return boolean
	 */
	static function removeDir($path, $within = false) {
		if (is_dir($path) && ($dir = opendir($path)) !== false) {
			while (($file = readdir($dir)) !== false) {
				if ($file != '.' && $file != '..') {
					if ((is_dir($path . '/' . $file))) {
						if (!self::removeDir($path . '/' . $file)) {
							return false;
						}
					} else {
						chmod($path . '/' . $file, 0777);
						if (!unlink($path . '/' . $file)) {
							return false;
						}
					}
				}
			}
			closedir($dir);
			if (!$within) {
				chmod($path, 0777);
				if (!rmdir($path)) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * inserts location independent WEB path tags in place of site path tags
	 * @param string $text
	 */
	static function tagURLs($text) {
		global $_tagURLs_tags, $_tagURLs_values;
		if ($serial = is_serialized($text)) {
			$text = unserialize($text);
		}
		if (is_array($text)) {
			foreach ($text as $key => $textelement) {
				$text[$key] = self::TagURLs($textelement);
			}
			if ($serial) {
				$text = serialize($text);
			}
		} else {
			preg_match_all("/href\s*=\s*(?:(?:\"(?:\\\\\"|[^\"])+\")|(?:'(?:\\\'|[^'])+'))/is", $text, $hrefmatch);
			preg_match_all("/src\s*=\s*(?:(?:\"(?:\\\\\"|[^\"])+\")|(?:'(?:\\\'|[^'])+'))/is", $text, $srcmatch);
			preg_match_all("/url\(\s*(?:(?:\"(?:\\\\\"|[^\"])+\")|(?:'(?:\\\'|[^'])+'))\)/is", $text, $urlmatch);
			$targets = array_unique(array_merge($hrefmatch[0], $srcmatch[0], $urlmatch[0]));
			foreach ($targets as $target) {
				$tagged = str_replace($_tagURLs_values, $_tagURLs_tags, $target);
				$text = str_replace($target, $tagged, $text);
			}
		}
		return $text;
	}

	/**
	 * reverses tagURLs()
	 * @param string $text
	 * @return string
	 */
	static function unTagURLs($text, $debug = NULL) {
		global $_tagURLs_tags, $_tagURLs_values;
		if ($text) {
			if ($serial = is_serialized($text)) {
				$text = unserialize($text);
			}
			if (is_array($text)) {
				foreach ($text as $key => $textelement) {
					$text[$key] = self::unTagURLs($textelement, $debug + 1);
				}
				if ($serial) {
					$text = serialize($text);
				}
			} else {
				$text = str_replace($_tagURLs_tags, $_tagURLs_values, $text);
			}
		}
		return $text;
	}

	/**
	 * Searches out i.php image links and replaces them with cache links if image is cached
	 * @param string $text
	 * @param bool $force used by cachemanager to get update i.php links to cache links
	 * @return string
	 */
	static function updateImageProcessorLink($text, $force = false) {
		if (is_string($text) && preg_match('/^a:[0-9]+:{/', $text)) { //	serialized array
			$text = getSerializedArray($text);
			$serial = true;
		} else {
			$serial = false;
		}
		if (is_array($text)) {
			foreach ($text as $key => $textelement) {
				$text[$key] = self::updateImageProcessorLink($textelement, $force);
			}
			if ($serial) {
				$text = serialize($text);
			}
		} else {
			preg_match_all('|\<\s*img.*\ssrc\s*=\s*"(.*i\.php\?.*)\"|U', $text, $matches);
			foreach ($matches[1] as $key => $match) {
				preg_match('|.*i\.php\?(.*)|', $match, $imgproc);
				if ($imgproc) {
					$match = preg_split('~\&[amp;]*~', $imgproc[1]);
					$set = array();
					foreach ($match as $v) {
						$s = explode('=', $v);
						$set[$s[0]] = $s[1];
					}
					$args = getImageArgs($set);

					if ($force) {
						$cachefilename = getImageCacheFilename(urldecode($set['a']), urldecode($set['i']), $args);
						$imageuri = '{*WEBPATH*}/' . CACHEFOLDER . pathurlencode(imgSrcURI($cachefilename));
						$text = str_replace($matches[1][$key], $imageuri, $text);
					} else {
						$imageuri = self::tagURLs(getImageURI($args, urldecode($set['a']), urldecode($set['i']), NULL));
						if (strpos($imageuri, '/' . CORE_FOLDER . '/i.') === false) {
							$text = str_replace($matches[1][$key], $imageuri, $text);
						}
					}
				}
			}
		}
		return $text;
	}

	static function getPriorityDisplay($priority) {
		$class = array();
		if ($priority & CLASS_PLUGIN) {
			$class[] = 'CLASS';
		}
		if ($priority & ADMIN_PLUGIN) {
			$class[] = 'ADMIN';
		}
		if ($priority & FEATURE_PLUGIN) {
			$class[] = 'FEATURE';
		}
		if ($priority & THEME_PLUGIN) {
			$class[] = 'THEME';
		}
		if (empty($class)) {
			$class[] = 'THEME';
		}
		return sprintf('%s | %u', implode(' | ', $class), $priority & PLUGIN_PRIORITY);
	}

	static function pluginDebug($extension, $priority, $start) {
		list($usec, $sec) = explode(" ", $start);
		$start = (float) $usec + (float) $sec;
		list($usec, $sec) = explode(" ", microtime());
		$end = (float) $usec + (float) $sec;
		$priority = self::getPriorityDisplay($priority);
		debugLog(sprintf('    ' . $extension . '(%s)=>%.4fs', $priority, $end - $start));
	}

	/**
	 * handles compound plugin disable criteria
	 * @param array $criteria
	 * @return boolean
	 *
	 * @author Stephen Billard
	 * @Copyright 2015 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
	 */
	static function pluginDisable($criteria) {
		foreach ($criteria as $try) {
			if ($try[0]) {
				return $try[1];
			}
		}
		return false;
	}

	/**
	 * Send an mail to the mailing list. We also attempt to intercept any form injection
	 * attacks by slime ball spammers. Returns error message if send failure.
	 *
	 * @param string $subject  The subject of the email.
	 * @param string $message  The message contents of the email.
	 * @param array $email_list a list of email addresses to send to
	 * @param array $cc_addresses a list of addresses to send copies to.
	 * @param array $bcc_addresses a list of addresses to send blind copies to.
	 * @param string $replyTo reply-to address
	 * @param string $failMessage will be displayed upon a failure to send
	 * @param array $fromMail an array of name=>email address for the sender. Defaults to the site email name and address
	 *
	 * @return string
	 *
	 * @author Todd Papaioannou (lucky@luckyspin.org)
	 * @since  1.0.0
	 */
	static function mail($subject, $message, $email_list = NULL, $cc_addresses = NULL, $bcc_addresses = NULL, $replyTo = NULL, $failMessage = NULL, $fromMail = NULL) {
		global $_authority, $_gallery, $_UTF8;
		if (is_null($failMessage)) {
			$failMessage = gettext('Mail send failed.') . ' ';
		}
		$result = '';
		if (empty($subject)) {
			if (empty($result)) {
				$result = $failMessage;
			}
			$result .= gettext('Mail messagaes must have a subject. ');
		}
		if (empty($message)) {
			if (empty($result)) {
				$result = $failMessage;
			}
			$result .= gettext('Mail messagaes must have a body. ');
		}
		if ($replyTo) {
			if (!npgFunctions::isValidEmail($m = reset($replyTo))) {
				if (empty($result)) {
					$result = $failMessage;
				}
				$result .= sprintf(gettext('Invalid “reply-to” mail address %s. '), '"' . $m . '"');
			}
		}
		if (is_null($email_list)) {
			if ($_authority) {
				$email_list = $_authority->getAdminEmail();
			} else {
				return $failMessage . gettext('There is no administrator with an e-mail address. ');
			}
		} else {
			foreach ($email_list as $key => $email) {
				if (!npgFunctions::isValidEmail($email)) {
					unset($email_list[$key]);
					if (empty($result)) {
						$result = $failMessage;
					}
					$result .= ' ' . sprintf(gettext('Invalid “to” mail address %s.'), '"' . $email . '" ');
				}
			}
		}
		if (is_null($cc_addresses)) {
			$cc_addresses = array();
		} else {
			if (empty($email_list) && !empty($cc_addresses)) {
				if (empty($result)) {
					$result = $failMessage;
				}
				$result .= ' ' . gettext('“cc” list provided without “to” address list. ');
				return $result;
			}
			foreach ($cc_addresses as $key => $email) {
				if (!npgFunctions::isValidEmail($email)) {
					unset($cc_addresses[$key]);
					if (empty($result)) {
						$result = $failMessage;
					}
					$result = ' ' . sprintf(gettext('Invalid “cc” mail address %s. '), '"' . $email . '"');
				}
			}
		}
		if (is_null($bcc_addresses)) {
			$bcc_addresses = array();
		} else {
			foreach ($bcc_addresses as $key => $email) {
				if (!npgFunctions::isValidEmail($email)) {
					unset($bcc_addresses[$key]);
					if (empty($result)) {
						$result = $failMessage;
					}
					$result = ' ' . sprintf(gettext('Invalid “bcc” mail address %s. '), '"' . $email . '"');
				}
			}
		}

		if (count($email_list) + count($bcc_addresses) > 0) {

			if (npgFilters::has_filter('sendmail')) {

				if (is_array($fromMail)) {
					$from_mail = reset($fromMail);
					$from_name = key($fromMail);
				} else {
					$from_mail = getOption('site_email');
					$from_name = get_language_string(getOption('site_email_name'));
				}

				// Convert to UTF-8
				if (LOCAL_CHARSET != 'UTF-8') {
					$subject = $_UTF8->convert($subject, LOCAL_CHARSET);
					$message = $_UTF8->convert($message, LOCAL_CHARSET);
				}

				$formFile = getPlugin('forms/mailForm.htm');
				if ($formFile) {
					$form = file_get_contents($formFile);
					$form = npgFilters::apply('mail_form', preg_replace('~\<\!--.*--\>~mUs', '', $form));
					if (preg_match('~\<div id\=\"emailbody\".*\>(.*)\</div\>~mUs', $form, $matches)) {
						$form = strtr($form, array(
								'%WEBPATH%' => FULLWEBPATH,
								'%CORE_PATH%' => CORE_PATH,
								'%PLUGIN_PATH%' => PLUGIN_PATH,
								'%USER_PLUGIN_PATH%' => USER_PLUGIN_PATH,
								'%RW_SUFFIX%' => RW_SUFFIX,
								'%LOGO%' => $_gallery->getSiteLogo(FULLWEBPATH)
						));
						$message = str_replace($matches[1], $message, $form);
					}
				}
				// Send the mail
				$result = npgFilters::apply('sendmail', $result, $email_list, $subject, $message, $from_mail, $from_name, $cc_addresses, $bcc_addresses, $replyTo); // will be true if all mailers succeeded
			} else {
				$result = $failMessage . ' ' . gettext('There is no mail handler configured. ');
			}
		} else {
			if (empty($result)) {
				$result = $failMessage;
			}
			$result .= ' ' . gettext('No “to” address list provided. ');
		}
		return $result;
	}

	/**
	 * Checks to see if a URL is valid
	 *
	 * @param string $url the URL being checked
	 * @return bool
	 */
	static function isValidURL($url) {
		$parts = parse_url($url);
		if (isset($parts['host'])) {
			if (!isset($parts['scheme']) || in_array(strtolower($parts['scheme']), array('http', 'https'))) {
				return TRUE;
			}
		}
		return 0;
	}

	/**
	 * Checks if an e-mail address is valid
	 * @param type $email
	 * @return type
	 */
	static function isValidEmail($email) {
		return filter_var($email, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE);
	}

	static function array_arg_example($args) {
		$example = '';
		foreach ($args as $arg => $v) {
			if (!is_null($v)) {
				$example .= ",'" . $arg . "'=>";
				if (is_numeric($v)) {
					$example .= $v;
				} else if (is_bool($v)) {
					if ($v) {
						$example .= 'true';
					} else {
						$example .= 'false';
					}
				} else {
					$example .= "'" . $v . "'";
				}
			}
		}
		return '[' . ltrim($example, ',') . ']';
	}

}

/**
 * Standins for when no captcha is enabled
 */
class _captcha {

	public $name = NULL; // "captcha" name if no captcha plugin loaded

	function getCaptcha($prompt = NULL) {
		return array('input' => NULL, 'html' => '<p class="errorbox">' . gettext('No captcha handler is enabled.') . '</p>', 'hidden' => '');
	}

	function checkCaptcha($s1, $s2) {
		return false;
	}

}

/**
 * stand-in for when there is no HTML cache plugin enabled
 */
class _npg_HTML_cache {

	function disable() {

	}

	function startHTMLCache() {

	}

	function abortHTMLCache($flush) {

	}

	function endHTMLCache() {

	}

	function clearHtmlCache() {

	}

}
