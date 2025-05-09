<?php

/**
 * Gallery Class
 * @package classes
 */
// force UTF-8 Ø

class Gallery {

	public $albumdir = NULL;
	public $table = 'gallery';
	public $name = '..gallery..';
	public $branded = false;
	protected $albums = NULL;
	protected $theme;
	protected $themes;
	protected $lastalbumsort = NULL;
	protected $data = array();
	protected $unprotected_pages = array();

	/**
	 * Creates an instance of a gallery
	 *
	 * @return Gallery
	 */
	function __construct() {
		// Set our album directory
		$this->albumdir = ALBUM_FOLDER_SERVERPATH;
		$data = getOption('gallery_data');
		if ($data) {
			$this->data = getSerializedArray($data);
		}
		if (isset($this->data['unprotected_pages'])) {
			$pages = getSerializedArray($this->data['unprotected_pages']);
			if (is_array($pages))
				$this->unprotected_pages = $pages; //	protect against a failure
		}
		$this->branded = !empty($this->get('sitelogoimage'));
	}

	/**
	 * Returns the gallery title
	 *
	 * @return string
	 */
	function getTitle($locale = NULL) {
		$text = $this->get('gallery_title');
		if ($locale !== 'all') {
			$text = get_language_string($text, $locale);
		}
		$text = npgFunctions::unTagURLs($text);
		return $text;
	}

	/**
	 * Returns a tag stripped title
	 * @param string $locale
	 * @return string
	 */
	function getBareTitle($locale = NULL) {
		return getBare($this->getTitle($locale));
	}

	function setTitle($title) {
		$this->set('gallery_title', npgFunctions::tagURLs($title));
	}

	/**
	 * Returns the gallery description
	 *
	 * @return string
	 */
	function getDesc($locale = NULL) {
		$text = $this->get('Gallery_description');
		if ($locale == 'all') {
			return npgFunctions::unTagURLs($text);
		} else {
			return applyMacros(npgFunctions::unTagURLs(get_language_string($text, $locale)));
		}
		return $text;
	}

	/**
	 * Sets the gallery description
	 * @param string $desc
	 */
	function setDesc($desc) {
		$desc = npgFunctions::tagURLs($desc);
		$this->set('Gallery_description', $desc);
	}

	function getCopyright($locale = NULL) {
		$text = $this->get('copyright');
		if ($locale == 'all') {
			return npgFunctions::unTagURLs($text);
		} else {
			return applyMacros(npgFunctions::unTagURLs(get_language_string($text, $locale)));
		}
		return $text;
	}

	function setCopyright($text) {
		$this->set('copyright', $text);
	}

	/**
	 * Returns the website logon welcome message
	 *
	 * @return string
	 */
	function getLogonWelcome($locale = NULL) {
		$text = $this->get('logon_welcome');
		if ($locale == 'all') {
			return npgFunctions::unTagURLs($text);
		} else {
			return applyMacros(npgFunctions::unTagURLs(get_language_string($text, $locale)));
		}
	}

	/**
	 * saves barnding logo
	 *
	 * @param string $logo path to logo image
	 */
	function setSiteLogo($logo) {
		$this->set('sitelogoimage', $logo);
	}

	/**
	 * Retrieves branding logo title
	 *
	 * @return string title
	 */
	function getSiteLogo($path = WEBPATH) {
		$image = $this->get('sitelogoimage');
		if (empty($image) || !file_exists(SERVERPATH . '/' . $image)) {
			return $path . '/' . CORE_FOLDER . '/images/admin-logo.png';
		} else {
			return $path . '/' . $image;
		}
	}

	/**
	 * saves barnding logo
	 *
	 * @param string $logo path to logo image
	 */
	function setSiteLogoTitle($logo) {
		$msg = npgFunctions::tagURLs($logo);
		$this->set('sitelogotitle', $msg);
		$this->branded = !empty($logo);
	}

	/**
	 * Retrieves branding logo title
	 *
	 * @return string path to logo image
	 */
	function getSiteLogoTitle($locale = NULL) {
		$text = $this->get('sitelogotitle');
		if ($locale == 'all') {
			return npgFunctions::unTagURLs($text);
		} else {
			return applyMacros(npgFunctions::unTagURLs(get_language_string($text, $locale)));
		}
	}

	/**
	 * sets the website logon welcome message
	 *
	 * @param $msg string
	 */
	function setLogonWelcome($msg) {
		$msg = npgFunctions::tagURLs($msg);
		$this->set('logon_welcome', $msg);
	}

	/**
	 * Returns the hashed password for guest gallery access
	 *
	 */
	function getPassword() {
		if (GALLERY_SECURITY == 'public') {
			$p = $this->get('gallery_password');
			if ($p) {
				return $p;
			}
		}
		return '';
	}

	function setPassword($value) {
		$this->set('gallery_password', $value);
	}

	/**
	 * Returns the hind associated with the gallery password
	 *
	 * @return string
	 */
	function getPasswordHint($locale = NULL) {
		$text = $this->get('gallery_hint');
		if ($locale !== 'all') {
			$text = get_language_string($text, $locale);
		}
		$text = npgFunctions::unTagURLs($text);
		return $text;
	}

	function setPasswordHint($value) {
		$this->set('gallery_hint', npgFunctions::tagURLs($value));
	}

	function getUser() {
		return($this->get('gallery_user'));
	}

	function setUser($value) {
		$this->set('gallery_user', $value);
	}

	/**
	 * Returns the main albums directory
	 *
	 * @return string
	 */
	function getAlbumDir() {
		return $this->albumdir;
	}

	/**
	 * Returns the DB field corresponding to the album sort type desired
	 *
	 * @param string $sorttype the desired sort
	 * @return string
	 */
	function getAlbumSortKey($sorttype = null) {
		if (empty($sorttype)) {
			$sorttype = $this->getSortType();
		}
		return lookupSortKey($sorttype, 'sort_order', 'albums');
	}

	function getSortDirection($what = 'album') {
		if ($what == 'image') {
			return $this->get('image_sortdirection');
		} else {
			return $this->get('sort_direction');
		}
	}

	function setSortDirection($value, $what = 'album') {
		if ($what == 'image') {
			$this->set('image_sortdirection', (int) ($value && true));
		} else {
			$this->set('sort_direction', (int) ($value && true));
		}
	}

	function getSortType($what = 'album') {
		if ($what == 'image') {
			$type = $this->get('image_sorttype');
		} else {
			$type = $this->get('gallery_sorttype');
		}
		return $type;
	}

	function setSortType($value, $what = 'album') {
		if ($what == 'image') {
			$this->set('image_sorttype', $value);
		} else {
			$this->set('gallery_sorttype', $value);
		}
	}

	/**
	 * Get Albums will create our $albums array with a fully populated set of Album
	 * names in the correct order.
	 *
	 * Returns an array of albums (a pages worth if $page is not zero)
	 *
	 * @param int $page An option parameter that can be used to return a slice of the array.
	 * @param string $sorttype the kind of sort desired
	 * @param string $sortdirection set to a direction to override the default option
	 * @param bool $care set to false if the order of the albums does not matter
	 * @param bool $mine set true/false to override ownership
	 *
	 * @return  array
	 */
	function getAlbums($page = 0, $sorttype = null, $sortdirection = null, $care = true, $mine = NULL) {
		// Have the albums been loaded yet?
		if ($mine || is_null($this->albums) || $care && $sorttype . $sortdirection !== $this->lastalbumsort) {
			if (is_null($sorttype)) {
				$sorttype = $this->getSortType('album');
			}
			if (is_null($sortdirection)) {
				$sortdirection = $this->getSortDirection('album');
			}
			$sortdirection = $sortdirection && strtolower($sortdirection) != 'asc';
			$albumnames = $this->loadAlbumNames();
			$key = $this->getAlbumSortKey($sorttype);
			$albums = $this->sortAlbumArray(NULL, $albumnames, $key, $sortdirection, $mine);

			// Store the values
			$this->albums = $albums;
			$this->lastalbumsort = $sorttype . $sortdirection;
		}

		if ($page == 0) {
			return $this->albums;
		} else {
			return array_slice($this->albums, galleryAlbumsPerPage() * ($page - 1), galleryAlbumsPerPage());
		}
	}

	/**
	 * Load all of the albums names that are found in the Albums directory on disk.
	 * Returns an array containing this list.
	 *
	 * @return array
	 */
	private function loadAlbumNames() {
		$albumdir = $this->getAlbumDir();
		$dir = opendir($albumdir);
		if (!$dir) {
			if (is_dir($albumdir)) {
				$msg = sprintf(gettext('Error: The “albums” directory (%s) is not readable.'), $this->albumdir);
			} else {
				$msg = sprintf(gettext('Error: The “albums” directory (%s) cannot be found.'), $this->albumdir);
			}
			trigger_error($msg, E_USER_WARNING);
		}
		$albums = array();

		while ($dirname = readdir($dir)) {
			if ($dirname[0] != '.' && (is_dir($albumdir . $dirname) || hasDynamicAlbumSuffix($dirname))) {
				$albums[] = filesystemToInternal($dirname);
			}
		}
		closedir($dir);
		return npgFilters::apply('album_filter', $albums);
	}

	/**
	 * Returns the a specific album in the array indicated by index.
	 * Takes care of bounds checking, no need to check input.
	 *
	 * @param int $index the index of the album sought
	 * @return Album
	 */
	function getAlbum($index) {
		$this->getAlbums();
		if ($index >= 0 && $index < $this->getNumAlbums()) {
			return newAlbum($this->albums[$index]);
		} else {
			return false;
		}
	}

	/**
	 * Returns the total number of TOPLEVEL albums in the gallery (does not include sub-albums)
	 * @param bool $db whether or not to use the database (includes ALL detected albums) or the directories
	 * @param bool $publishedOnly set to true to exclude un-published albums
	 * @return int
	 */
	function getNumAlbums($db = false, $publishedOnly = false) {
		$count = -1;
		if (!$db) {
			$this->getAlbums(0, NULL, NULL, false);
			$count = count($this->albums);
		} else {
			$sql = '';
			if ($publishedOnly) {
				$sql = 'WHERE `show`=1';
			}
			$count = db_count('albums', $sql);
		}
		return $count;
	}

	/**
	 * Populates the theme array and returns it. The theme array contains information about
	 * all the currently available themes.
	 * @return array
	 */
	function getThemes() {
		if (empty($this->themes)) {
			$themedir = SERVERPATH . "/" . THEMEFOLDER;
			if ($dp = opendir($themedir)) {
				while (false !== ($dir = readdir($dp))) {
					if (substr($dir, 0, 1) != "." && is_dir("$themedir/$dir")) {
						$themefile = $themedir . "/$dir/theme_description.php";
						$dir8 = filesystemToInternal($dir);
						$this->themes[$dir8] = array('name' => gettext('Unknown'), 'author' => gettext('Unknown'), 'version' => gettext('Unknown'), 'desc' => gettext('<strong>Missing theme info file!</strong>'), 'date' => gettext('Unknown'));
						if (file_exists($themefile)) {
							$theme_description = array();
							require($themefile);
							$this->themes[$dir8] = $theme_description;
						}
					}
				}
				ksort($this->themes, SORT_LOCALE_STRING);
			}
		}
		return $this->themes;
	}

	/**
	 * Returns the foldername of the current theme.
	 * if no theme is set, picks the "first" theme.
	 * @return string
	 */
	function getCurrentTheme() {
		if (empty($this->theme)) {
			$theme = $this->get('current_theme');
			if (empty($theme) || !file_exists(SERVERPATH . "/" . THEMEFOLDER . "/$theme")) {
				$themes = array_keys($this->getThemes());
				if (!empty($themes)) {
					$theme = reset($themes);
				}
			}
			$this->theme = $theme;
		}
		return $this->theme;
	}

	/**
	 * Sets the current theme
	 * @param string $theme the name of the current theme
	 * @param bool $transient Set to true if this state is not to be saved
	 *
	 */
	function setCurrentTheme($theme, $transient = NULL) {
		$this->theme = $theme;
		if (!$transient) {
			$this->set('current_theme', $this->theme);
		}
	}

	/**
	 * Returns the number of images in the gallery
	 * @param int $what 0: all images from the database
	 * 									1: published images from the database
	 * 									2: "viewable" images via the object model
	 * @return int
	 */
	function getNumImages($what = 0) {
		switch ((int) $what) {
			case 0:
				return db_count('images', '');
				break;
			case 1:
				$rows = query("SELECT `id` FROM " . prefix('albums') . " WHERE `show`=0");
				$idlist = array();
				$exclude = 'WHERE `show`=1';
				if ($rows) {
					while ($row = db_fetch_assoc($rows)) {
						$idlist[] = $row['id'];
					}
					if (!empty($idlist)) {
						$exclude .= ' AND `albumid` NOT IN (' . implode(',', $idlist) . ')';
					}
					db_free_result($rows);
				}
				return db_count('images', $exclude);
				break;
			case 2:
				$count = 0;
				$albums = $this->getAlbums(0);
				foreach ($albums as $analbum) {
					$album = newAlbum($analbum);
					if (!$album->isDynamic()) {
						$count = $count + self::getImageCount($album);
					}
				}
				return $count;
				break;
		}
	}

	private static function getImageCount($album) {
		$count = $album->getNumImages();
		$albums = $album->getAlbums(0);
		foreach ($albums as $analbum) {
			$album = newAlbum($analbum);
			if (!$album->isDynamic()) {
				$count = $count + self::getImageCount($album);
			}
		}
		return $count;
	}

	/**
	 * Returns the count of comments
	 *
	 * @param bool $moderated set true if you want to see moderated comments
	 * @return array
	 */
	function getNumComments($moderated = false) {
		$sql = '';
		if (!$moderated) {
			$sql = "WHERE `inmoderation`=0";
		}
		return db_count('comments', $sql);
	}

	/** For every album in the gallery, look for its file. Delete from the database
	 * if the file does not exist. Do the same for images. Clean up comments that have
	 * been left orphaned.
	 *
	 * Returns true if the operation was interrupted because it was taking too long
	 *
	 * @param bool $cascade garbage collect every image and album in the gallery.
	 * @param bool $complete garbage collect every image and album in the *database* - completely cleans the database.
	 * @param  int $restart Image ID to restart scan from
	 * @return bool
	 */
	function garbageCollect($cascade = true, $complete = false, $restart = '') {
		global $_gallery, $_authority;
		require_once(CORE_SERVERPATH . '/' . PLUGIN_FOLDER . '/comment_form/functions.php'); // in case comment_form not enabled
		if (empty($restart)) {
			setOption('last_garbage_collect', time());
			/* purge old search cache items */
			$sql = 'DELETE FROM ' . prefix('search_cache');
			if (!$complete) {
				$sql .= ' WHERE `date`<' . db_quote(date('Y-m-d H:i:s', time() - SEARCH_CACHE_DURATION * 60));
			}
			$result = query($sql);

			/* clean the comments table */
			$this->commentClean('images');
			$this->commentClean('albums');
			$this->commentClean('news');
			$this->commentClean('pages');
			// clean up obj_to_tag
			$dead = array();
			$result = query("SELECT `id`, `type`, `tagid`, `objectid` FROM " . prefix('obj_to_tag'));
			if ($result) {
				while ($row = db_fetch_assoc($result)) {
					$tbl = $row['type'];
					$dbtag = query_single_row($sql = "SELECT `id` FROM " . prefix('tags') . " WHERE `id`='" . $row['tagid'] . "'", false);
					if (!$dbtag) {
						$dead[$row['id']]['tags'] = $row['tagid'];
					}
					$dbtag = query_single_row($sql = "SELECT `id` FROM " . prefix($tbl) . " WHERE `id`='" . $row['objectid'] . "'", false);
					if (!$dbtag) {
						$dead[$row['id']][$tbl] = $row['objectid'];
					}
				}
				db_free_result($result);
			}
			if (!empty($dead)) {
				if (DEBUG_OBJECTS) {
					debugLogVar(['Garbage Collect `obj_to_tag`' => $dead]);
				}
				query('DELETE FROM ' . prefix('obj_to_tag') . ' WHERE `id` IN(' . implode(',', array_keys($dead)) . ')');
			}
			// clean up admin_to_object
			$dead = array();
			$result = query("SELECT `id`, `adminid`, `objectid`, `type` FROM " . prefix('admin_to_object'));
			if ($result) {
				while ($row = db_fetch_assoc($result)) {
					if (!$_authority->validID($row['adminid'])) {
						$dead[$row['id']]['user'] = $row['adminid'];
					}
					$tbl = $row['type'];
					$dbtag = query_single_row($sql = "SELECT `id` FROM " . prefix($tbl) . " WHERE `id`='" . $row['objectid'] . "'", false);
					if (!$dbtag) {
						$dead[$row['id']][$tbl] = $row['objectid'];
					}
				}
				db_free_result($result);
			}
			if (!empty($dead)) {
				if (DEBUG_OBJECTS) {
					debugLogVar(['Garbage Collect `admin_to_object`' => $dead]);
				}
				query('DELETE FROM ' . prefix('admin_to_object') . ' WHERE `id` IN(' . implode(',', array_keys($dead)) . ')');
			}
			// clean up news2cat
			$dead = array();
			$result = query("SELECT `id`, `news_id`, `cat_id` FROM " . prefix('news2cat'));
			if ($result) {
				while ($row = db_fetch_assoc($result)) {
					$dbtag = query_single_row($sql = "SELECT `id` FROM " . prefix('news') . " WHERE `id`='" . $row['news_id'] . "'", false);
					if (!$dbtag) {
						$dead[$row['id']]['news'] = $row['news_id'];
					}
					$dbtag = query_single_row($sql = "SELECT `id` FROM " . prefix('news_categories') . " WHERE `id`='" . $row['cat_id'] . "'", false);
					if (!$dbtag) {
						$dead[$row['id']]['categories'] = $row['cat_id'];
					}
				}
				db_free_result($result);
			}
			if (!empty($dead)) {
				if (DEBUG_OBJECTS) {
					debugLogVar(['Garbage Collect `news2cat`' => $dead]);
				}
				query('DELETE FROM ' . prefix('news2cat') . ' WHERE `id` IN(' . implode(',', array_keys($dead)) . ')');
			}

			// Check for the existence albums
			$dead = array();
			$live = array(''); // purge the root album if it exists
			$deadalbumthemes = array();
			// Load the albums from disk
			$result = query("SELECT `id`, `folder`, `album_theme` FROM " . prefix('albums'));
			if ($result) {
				while ($row = db_fetch_assoc($result)) {
					$albumpath = internalToFilesystem($row['folder']);
					$albumpath_valid = preg_replace('~/\.*/~', '/', $albumpath);
					$albumpath_valid = ltrim(trim($albumpath_valid, '/'), './');
					$illegal = $albumpath != $albumpath_valid;
					$valid = file_exists(ALBUM_FOLDER_SERVERPATH . $albumpath_valid) && (hasDynamicAlbumSuffix($albumpath_valid) || is_dir(ALBUM_FOLDER_SERVERPATH . $albumpath_valid));
					if ($valid && $illegal) { // maybe there is only one record so we can fix it.
						$valid = query('UPDATE ' . prefix('albums') . ' SET `folder`=' . db_quote($albumpath_valid) . ' WHERE `id`=' . $row['id'], false);
						debugLog(sprintf(gettext('Invalid album folder: %1$s %2$s'), $albumpath, $valid ? gettext('fixed') : gettext('discarded')));
					}
					if (!$valid || in_array($row['folder'], $live)) {
						$dead[] = $row['id'];
						if ($row['album_theme'] !== '') { // orphaned album theme options table
							$deadalbumthemes[$row['id']] = $row['folder'];
						}
					} else {
						$live[] = $row['folder'];
					}
				}
				db_free_result($result);
			}

			if (count($dead) > 0) { /* delete the dead albums from the DB */
				asort($dead);
				$criteria = '(' . implode(',', $dead) . ')';
				$first = array_pop($dead);
				$sql1 = "DELETE FROM " . prefix('albums') . " WHERE `id` IN $criteria";
				$n = query($sql1);
				if (!$complete && $n && $cascade) {
					$sql2 = "DELETE FROM " . prefix('images') . " WHERE `albumid` IN $criteria";
					query($sql2);
					$sql3 = "DELETE FROM " . prefix('comments') . " WHERE `type`='albums' AND `ownerid` IN $criteria";
					query($sql3);
					$sql4 = "DELETE FROM " . prefix('obj_to_tag') . " WHERE `type`='albums' AND `objectid` IN $criteria";
					query($sql4);
				}
			}
			if (count($deadalbumthemes) > 0) { // delete the album theme options tables for dead albums
				foreach ($deadalbumthemes as $id => $deadtable) {
					$sql = 'DELETE FROM ' . prefix('options') . ' WHERE `ownerid`=' . $id;
					query($sql, false);
				}
			}
		}

		if ($complete) {
			if (empty($restart)) {
				/* check album parent linkage */
				$albums = $_gallery->getAlbums();
				foreach ($albums as $album) {
					checkAlbumParentid($album, NULL, 'debuglog');
				}

				/* refresh 'metadata' albums */
				$albumids = query("SELECT `id`, `mtime`, `folder` FROM " . prefix('albums'));
				if ($albumids) {
					while ($analbum = db_fetch_assoc($albumids)) {
						if (($mtime = filemtime(ALBUM_FOLDER_SERVERPATH . internalToFilesystem($analbum['folder']))) > $analbum['mtime']) {
							// refresh
							$album = newAlbum($analbum['folder'], false, true);
							$album->set('mtime', $mtime);
							if ($this->getAlbumUseImagedate()) {
								$album->setDateTime(NULL);
							}
							if ($album->isDynamic()) {
								$data = file_get_contents($album->localpath);
								$thumb = getOption('AlbumThumbSelect');
								$words = $fields = '';
								while (!empty($data)) {
									$data1 = trim(substr($data, 0, $i = strpos($data, "\n")));
									if ($i === false) {
										$data1 = $data;
										$data = '';
									} else {
										$data = substr($data, $i + 1);
									}
									if (strpos($data1, 'WORDS=') !== false) {
										$words = "words=" . urlencode(substr($data1, 6));
									}
									if (strpos($data1, 'THUMB=') !== false) {
										$thumb = trim(substr($data1, 6));
									}
									if (strpos($data1, 'FIELDS=') !== false) {
										$fields = "&searchfields=" . trim(substr($data1, 7));
									}
								}
								if (!empty($words)) {
									if (empty($fields)) {
										$fields = '&searchfields=tags';
									}
								}
								$album->set('search_params', $words . $fields);
								$album->set('thumb', $thumb);
							}
							$album->save();
							npgFilters::apply('album_refresh', $album);
						}
					}
					db_free_result($albumids);
				}

				/* Delete all image entries that don't belong to an album at all. */

				$albumids = query("SELECT `id` FROM " . prefix('albums')); /* all the album IDs */
				$idsofalbums = array();
				if ($albumids) {
					while ($row = db_fetch_assoc($albumids)) {
						$idsofalbums[] = $row['id'];
					}
					db_free_result($albumids);
				}
				$imageAlbums = query("SELECT DISTINCT `albumid` FROM " . prefix('images')); /* albumids of all the images */
				$albumidsofimages = array();
				if ($imageAlbums) {
					while ($row = db_fetch_assoc($imageAlbums)) {
						$albumidsofimages[] = $row['albumid'];
					}
					db_free_result($imageAlbums);
				}
				$orphans = array_diff($albumidsofimages, $idsofalbums); /* albumids of images with no album */
				if (count($orphans) > 0) { /* delete dead images from the DB */
					$sql = "DELETE FROM " . prefix('images') . " WHERE ";
					foreach ($orphans as $id) {
						if (is_null($id)) {
							$sql .= "`albumid` is NULL OR ";
						} else {
							$sql .= " `albumid`='" . $id . "' OR ";
						}
					}
					$sql = substr($sql, 0, -4);
					query($sql);

					// Then go into existing albums recursively to clean them... very invasive.
					foreach ($this->getAlbums(0) as $folder) {
						$album = newAlbum($folder);
						if (!$album->isDynamic()) {
							if (is_null($album->getDateTime())) { // see if we can get one from an image
								$images = $album->getImages(0, 0);
								if (count($images) > 0) {
									$image = newImage($album, reset($images));
									$album->setDateTime($image->getDateTime());
									$album->save();
								}
							}
							$album->garbageCollect(true);
						}
						npgFilters::apply('album_refresh', $album);
					}
				}
			}

			/* Look for image records where the file no longer exists. While at it, check for images with IPTC data to update the DB */

			if (!empty($restart)) {
				$restartwhere = ' WHERE `id`>' . $restart . ' AND `mtime`=0';
			} else {
				$restartwhere = ' WHERE `mtime`=0';
			}
			define('RECORD_LIMIT', 50);
			$sql = 'SELECT `id`, `albumid`, `filename`, `mtime` FROM ' . prefix('images') . $restartwhere . ' ORDER BY `id` LIMIT ' . (RECORD_LIMIT + 2);
			$images = query($sql);
			if ($images) {
				$c = 0;
				$imagetypes = npg_image_types('"');
				while ($image = db_fetch_assoc($images)) {
					$albumobj = getItemByID('albums', $image['albumid']);
					if ($albumobj && $albumobj->exists && file_exists($imageName = internalToFilesystem(ALBUM_FOLDER_SERVERPATH . $albumobj->name . '/' . $image['filename']))) {
						if ($image['filename'] != $mtime = filemtime($imageName)) { // file has changed since we last saw it
							$imageobj = newImage($albumobj, $image['filename']);
							$imageobj->set('mtime', $mtime);
							$imageobj->updateMetaData(); // prime the EXIF/IPTC fields
							$imageobj->updateDimensions(); // update the width/height & account for rotation
							$imageobj->save();
							npgFilters::apply('image_refresh', $imageobj);
						}
					} else {
						$sql = 'DELETE FROM ' . prefix('images') . ' WHERE `id`="' . $image['id'] . '";';
						$result = query($sql);
						if ($imagetypes) {
							$sql = 'DELETE FROM ' . prefix('comments') . ' WHERE `type` IN (' . $imagetypes . ') AND `ownerid` ="' . $image['id'] . '";';
							$result = query($sql);
						}
					}
					if (++$c >= RECORD_LIMIT) {
						return $image['id']; // avoide excessive processing
					}
				}
				db_free_result($images);
			}
			// cleanup the tables
			$resource = db_show('tables');
			if ($resource) {
				while ($row = db_fetch_assoc($resource)) {
					$tbl = reset($row);
					query('OPTIMIZE TABLE `' . $tbl . '`');
				}
				db_free_result($resource);
			}
		}
		return false;
	}

	function commentClean($table) {
		$ids = query('SELECT `id` FROM ' . prefix($table)); /* all the IDs */
		$idsofitems = array();
		if ($ids) {
			while ($row = db_fetch_assoc($ids)) {
				$idsofitems[] = $row['id'];
			}
			db_free_result($ids);
		}
		$sql = "SELECT DISTINCT `ownerid` FROM " . prefix('comments') . ' WHERE `type` =' . db_quote($table);
		$commentOwners = query($sql); /* all the comments */
		$idsofcomments = array();
		if ($commentOwners) {
			while ($row = db_fetch_assoc($commentOwners)) {
				$idsofcomments [] = $row['ownerid'];
			}
			db_free_result($commentOwners);
		}
		$orphans = array_diff($idsofcomments, $idsofitems); /* owner ids of comments with no owner */

		if (count($orphans) > 0) { /* delete dead comments from the DB */
			$sql = "DELETE FROM " . prefix('comments') . " WHERE `type`=" . db_quote($table) . " AND (`ownerid`=" . implode(' OR `ownerid`=', $orphans) . ')';
			query($sql);
		}
	}

	/**
	 * Cleans out the cache folder
	 *
	 * @param string $cachefolder the sub-folder to clean
	 */
	static function clearCache($cachefolder = NULL) {
		npgFunctions::removeDir(SERVERCACHE . '/' . $cachefolder, true);
	}

	/**
	 * Sort the album array based according to the sort key.
	 * Default is to sort on the `sort_order` field.
	 *
	 * Returns an array with the albums in the desired sort order
	 *
	 * @param  array $albums array of album names
	 * @param  string $sortkey the sorting scheme
	 * @param string $sortdirection
	 * @param bool $mine set true/false to override ownership
	 * @return array
	 *
	 * @author Todd Papaioannou (lucky@luckyspin.org)
	 * @since  1.0.0
	 */
	function sortAlbumArray($parentalbum, $albums, $sortkey, $sortdirection, $mine) {
		if (is_null($albums) || count($albums) == 0) {
			return array();
		}
		if (is_null($mine) && npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
			$mine = true;
		}
		if (is_null($parentalbum)) {
			$albumid = ' IS NULL';
			$obj = $this;
			$viewUnpublished = $mine;
		} else {
			$albumid = '=' . $parentalbum->getID();
			$obj = $parentalbum;
			$viewUnpublished = (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS | VIEW_UNPUBLISHED_RIGHTS) || $obj->subRights() & (MANAGED_OBJECT_RIGHTS_EDIT | MANAGED_OBJECT_RIGHTS_VIEW));
		}

		if ((trim($sortkey . '`') == 'sort_order') || ($sortkey == 'RAND()')) { // manual sort is always ascending
			$order = false;
		} else {
			if (is_null($sortdirection)) {
				$sortdirection = $this->getSortDirection('album');
			}
			$order = $sortdirection && strtolower($sortdirection) != 'asc';
		}
		if ($sortkey == 'RAND()') {
			$key = '';
		} else {
			$key = ', ' . $sortkey;
		}
		$sql = 'SELECT `id`, `folder`' . $key . ' FROM ' . prefix("albums") . ' WHERE `parentid`' . $albumid . ' ORDER BY ' . db_escape($sortkey);
		if ($order)
			$sql .= ' DESC';
		$result = query($sql);
		$results = array();
		//	check database aganist file system
		if ($result) {
			while ($row = db_fetch_assoc($result)) {
				$folder = $row['folder'];
				if (($key = array_search($folder, $albums)) !== false) { // album exists in filesystem
					$results[$row['folder']] = $row;
					unset($albums[$key]);
				} else { // album no longer exists
					$id = $row['id'];
					query("DELETE FROM " . prefix('albums') . " WHERE `id`=$id"); // delete the record
					query("DELETE FROM " . prefix('comments') . " WHERE `type` ='images' AND `ownerid`= '$id'"); // remove image comments
					query("DELETE FROM " . prefix('obj_to_tag') . "WHERE `type`='albums' AND `objectid`=" . $id);
					query("DELETE FROM " . prefix('albums') . " WHERE `id` = " . $id);
				}
			}
			db_free_result($result);
		}
		foreach ($albums as $folder) { // these albums are not in the database
			$albumobj = newAlbum($folder);
			$results[$folder] = $albumobj->getData();
		}
		//	now put the results in the right order
		$results = sortByKey($results, $sortkey, $order);

		//	albums are now in the correct order
		$albums_ordered = array();
		foreach ($results as $folder => $row) { // check for visible
			$album = newAlbum($folder, true, true);
			$subrights = $album->subrights();
			if ($mine ||
							($album->getShow() || $viewUnpublished) // published or overridden by parameter
							|| $subrights && is_null($album->getParent()) // is the user's managed album
							|| $subrights && ($subrights & MANAGED_OBJECT_RIGHTS_VIEW ) //	managed subalbum and user has unpublished rights
			) {
				$albums_ordered[] = $folder;
			}
		}
		return $albums_ordered;
	}

	/**
	 * Returns the hitcount
	 *
	 * @return int
	 */
	function getHitcounter() {
		return $this->get('hitcounter');
	}

	/**
	 * counts visits to the object
	 */
	function countHit() {
		$this->set('hitcounter', $this->get('hitcounter') + 1);
		$this->save();
	}

	/**
	 * Title to be used for the home (not gallery) WEBsite
	 */
	function getWebsiteTitle($locale = NULL) {
		$text = $this->get('website_title');
		if ($locale !== 'all') {
			$text = get_language_string($text, $locale);
		}
		$text = npgFunctions::unTagURLs($text);
		return $text;
	}

	function setWebsiteTitle($value) {
		$this->set('website_title', npgFunctions::tagURLs($value));
	}

	/**
	 * The URL of the home (not gallery) WEBsite
	 */
	function getWebsiteURL() {
		return $this->get('website_url');
	}

	function setWebsiteURL($value) {
		$this->set('website_url', $value);
	}

	/**
	 * Option to allow only registered users view the site
	 */
	function getSecurity() {
		return $this->get('gallery_security');
	}

	function setSecurity($value) {
		$this->set('gallery_security', $value);
	}

	/**
	 * Option to expose the user field on logon forms
	 */
	function getUserLogonField() {
		return $this->get('login_user_field');
	}

	function setUserLogonField($value) {
		$this->set('login_user_field', $value);
	}

	/**
	 * Option to update album date from date of new images
	 */
	function getAlbumUseImagedate() {
		return $this->get('album_use_new_image_date');
	}

	function setAlbumUseImagedate($value) {
		$this->set('album_use_new_image_date', $value);
	}

	/**
	 * Option to show images in the thumbnail selector
	 */
	function getThumbSelectImages() {
		return $this->get('thumb_select_images');
	}

	function setThumbSelectImages($value) {
		$this->set('thumb_select_images', $value);
	}

	/**
	 * Option to show subalbum images in the thumbnail selector
	 */
	function getSecondLevelThumbs() {
		return $this->get('multilevel_thumb_select_images');
	}

	function setSecondLevelThumbs($value) {
		$this->set('multilevel_thumb_select_images', $value);
	}

	/**
	 * Option of for gallery sessions
	 */
	function getGallerySession() {
		return $this->get('album_session');
	}

	function setGallerySession($value) {
		$this->set('album_session', $value);
	}

	/**
	 *
	 * Tests if a page is excluded from password protection
	 * @param $page
	 */
	function isUnprotectedPage($page) {
		if (in_array($page, $this->unprotected_pages)) {
			return true;
		}
		return npgFilters::apply('isUnprotectedPage', false, $page);
	}

	function setUnprotectedPage($page, $on) {
		if ($on) {
			array_unshift($this->unprotected_pages, $page);
			$this->unprotected_pages = array_unique($this->unprotected_pages);
		} else {
			$key = array_search($page, $this->unprotected_pages);
			if ($key !== false) {
				unset($this->unprotected_pages[$key]);
			}
		}
		$this->set('unprotected_pages', serialize($this->unprotected_pages));
	}

	function getAlbumPublish() {
		return $this->get('album_publish');
	}

	function setAlbumPublish($v) {
		$this->set('album_publish', $v);
	}

	function getImagePublish() {
		return $this->get('image_publish');
	}

	function setImagePublish($v) {
		$this->set('image_publish', $v);
	}

	/**
	 * Returns the codeblocks as an serialized array
	 *
	 * @return array
	 */
	function getCodeblock() {
		return npgFunctions::unTagURLs($this->get("codeblock"));
	}

	/**
	 * set the codeblocks as an serialized array
	 *
	 */
	function setCodeblock($cb) {
		$this->set('codeblock', npgFunctions::tagURLs($cb));
	}

	/**
	 * Checks if guest is loggedin for the album
	 * @param unknown_type $hint
	 * @param unknown_type $show
	 */
	function checkforGuest(&$hint = NULL, &$show = NULL) {
		if (!(GALLERY_SECURITY != 'public')) {
			return false;
		}
		$hint = $_gallery->getPasswordHint();
		$pwd = $this->getPassword();
		if (!empty($pwd)) {
			return 'gallery_auth';
		}
		return 'public_access';
	}

	/**
	 *
	 * returns true if there is any protection on the gallery
	 */
	function isProtected() {
		return $this->checkforGuest() != 'public_access';
	}

	function get($field) {
		if (isset($this->data[$field])) {
			return $this->data[$field];
		}
		return NULL;
	}

	function set($field, $value) {
		$this->data[$field] = $value;
	}

	function save() {
		$olddata = getOption('gallery_data');
		$newdata = serialize($this->data);
		if ($newdata == $olddata) {
			return 2;
		}
		setOption('gallery_data', $newdata);
		return 1;
	}

	/**
	 *
	 * "Magic" function to return a string identifying the object when it is treated as a string
	 * @return string
	 */
	public function __toString() {
		return 'Gallery object';
	}

	/**
	 * registers object handlers for image varients
	 * @param type $suffix
	 * @param type $objectName
	 */
	static function addImageHandler($suffix, $objectName) {
		global $_images_classes;
		$suffix = strtolower($suffix);
		if (!isset($_images_classes[$suffix])) { //	plugin priority determines who handles
			$_images_classes[$suffix] = $objectName;
		}
	}

	/**
	 * Returns the object class based in the filename suffix
	 * @param string $filename
	 * @return string
	 */
	static function imageObjectClass($filename) {
		global $_images_classes;
		if (isset($_images_classes[$suffix = getSuffix($filename)])) {
			return $_images_classes[$suffix];
		} else {
			return false;
		}
	}

	/**
	 * registers object handlers for album varients
	 * @global array $_albumHandlers
	 * @param type $suffix
	 * @param type $objectName
	 */
	static function addAlbumHandler($suffix, $objectName) {
		global $_albumHandlers;
		$_albumHandlers[strtolower($suffix)] = $objectName;
	}

	function getData() {
		return $this->data;
	}

	function getLink($page = NULL) {
		$rewrite = '';
		$plain = '/index.php';
		if ($page > 1) {
			$rewrite .= _PAGE_ . '/' . $page;
			$plain .= "&page=$page";
		}
		return npgFilters::apply('getLink', rewrite_path($rewrite, $plain), $this, $page);
	}

}

$_gallery = new Gallery();
