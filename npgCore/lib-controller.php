<?php

/**
 * Common functions used in the controller for getting/setting current classes,
 * redirecting URLs, and working with the context.
 * @package core
 */
// force UTF-8 Ø

class Controller {

	/**
	 * Creates a "REWRITE" url given the query parameters that represent the link
	 *
	 * @param type $query
	 * @return string
	 */
	static function rewriteURL($query) {
		$redirectURL = '';
		if (isset($query['p'])) {
			sanitize($query);
			switch ($query['p']) {
				case 'news':
					$redirectURL = _NEWS_;
					if (isset($query['category'])) {
						$obj = newCategory(trim($query['category'], '/'), false);
						if (!$obj->loaded)
							return '';
						$redirectURL = $obj->getLink();
						unset($query['category']);
					} else if (isset($query['date'])) {
						$redirectURL = _NEWS_ARCHIVE_ . '/' . trim($query['date'], '/') . '/';
						unset($query['date']);
					}
					if (isset($query['title'])) {
						$obj = newArticle(trim($query['title'], '/'), false);
						if (!$obj->loaded)
							return '';
						$redirectURL = $obj->getLink();
						unset($query['title']);
					}
					break;
				case 'pages':
					if (isset($query['title'])) {
						$obj = newPage(trim($query['title'], '/'), false);
						if (!$obj->loaded)
							return '';
						$redirectURL = $obj->getLink();
						unset($query['title']);
					}
					break;
				case'search':
					$redirectURL = _SEARCH_;
					if (isset($query['date'])) {
						$redirectURL = _ARCHIVE_ . '/' . trim($query['date'], '/') . '/';
						unset($query['date']);
					} else if (isset($query['searchfields']) && $query['searchfields'] == 'tags') {
						$redirectURL = _TAGS_;
						unset($query['searchfields']);
					}
					if (isset($query['words'])) {
						if (!preg_match('/^[0-9A-F]+\.[0-9A-F]+$/i', $query['words'])) {
							$query['words'] = SearchEngine::encode($query['words']);
						}
						$redirectURL .= '/' . $query['words'] . '/';
						unset($query['words']);
					}
					break;
				default:
					$redirectURL = getCustomPageURL(trim($query['p'], '/'));
					break;
			}
			unset($query['p']);
			if (isset($query['page'])) {
				$redirectURL = rtrim($redirectURL, '/') . '/' . trim($query['page'], '/');
				unset($query['page']);
			}
		} else if (isset($query['album'])) {
			if (isset($query['image'])) {
				$obj = newImage(array('folder' => $query['album'], 'filename' => $query['image']), NULL, true);
				unset($query['image']);
			} else {
				$obj = newAlbum($query['album'], NULL, true);
			}
			if (is_object($obj) && !$obj->exists)
				return '';

			unset($query['album']);
			$redirectURL = preg_replace('~^' . WEBPATH . '/~', '', $obj->getLink(isset($query['page']) ? $query['page'] : NULL));
			unset($query['page']);
		} else if (isset($query['page'])) { //index page
			$redirectURL = _PAGE_ . '/' . trim($query['page'], '/');
			unset($query['page']);
		}

		if ($redirectURL && !empty($query)) {
			$redirectURL .= '?' . http_build_query($query);
		}
		return $redirectURL;
	}

	/**
	 * Checks to see if the current URL is a query string url when mod_rewrite is active.
	 * If so it will redirects to the rewritten URL with a 301 Moved Permanently.
	 */
	static function fix_path_redirect() {
		if (MOD_REWRITE) {
			$request_uri = getRequestURI(false);
			$parts = mb_parse_url($request_uri);
			$redirectURL = NULL;
			if (isset($parts['path'])) { // don't know how this can happen, but if it does, don't redirect
				if (isset($parts['query'])) {
					parse_str($parts['query'], $query);
					$redirectURL = self::rewriteURL($query);
				} else {
					$query = array();
				}

				if (isset($_GET['album'])) {
					if (isset($_GET['image'])) {
						//image URLs should not end in a slash
						if (substr($parts['path'], -1, 1) == '/') {
							$redirectURL = self::rewriteURL($_GET);
						}
					} else {
						//album URLs should end in a slash for consistency
						if (substr($parts['path'], -1, 1) != '/') {
							$redirectURL = self::rewriteURL($_GET);
						}
					}
				}

				if (isset($_GET['p'])) {
					switch ($_GET['p']) {
						case 'news':
							if (class_exists('CMS')) {
								if (isset($_GET['title'])) {
									//article URLs should not end in slash
									if (substr($parts['path'], -1, 1) == '/') {
										$redirectURL = self::rewriteURL($_GET);
									}
								} else {
									//should be news/
									if (substr($parts['path'], -1, 1) != '/') {
										$redirectURL = self::rewriteURL($_GET);
									}
								}
								break;
							}
						case 'search':
							if (isset($_GET['date'])) {
								if (substr($parts['path'], -1, 1) != '/') {
									$redirectURL = self::rewriteURL($_GET);
								}
							}
							break;
					}
				}
				//page numbers do not have trailing slash
				if (isset($_GET['page'])) {
					if (substr($parts['path'], -1, 1) == '/') {
						$redirectURL = self::rewriteURL($_GET);
					}
				}

				if ($redirectURL) {
					$parts2 = mb_parse_url($redirectURL);
					if (isset($parts2['query'])) {
						parse_str($parts2['query'], $query2);
					} else {
						$query2 = array();
					}

					if ($query != $query2 || preg_replace('~^' . WEBPATH . '/~', '', $parts['path']) != preg_replace('~^' . WEBPATH . '/~', '', html_encode($parts['path']))) {
						header("HTTP/1.0 301 Moved Permanently");
						header("Status: 301 Moved Permanently");
						header('Location: ' . FULLWEBPATH . '/' . preg_replace('~^' . WEBPATH . '/~', '', $redirectURL));
						exit();
					}
				}
			}
		}
	}

	/**
	 * Redirects to moved link with suffix added
	 *
	 * @param string $tofix the string missing the suffix
	 * @param string $toadd the missing suffix
	 */
	protected static function fix_suffix_redirect($tofix, $toadd = RW_SUFFIX) {
		$request_uri = getRequestURI(false);
		$redirectURL = str_replace($tofix, $tofix . $toadd, $request_uri);
		header("HTTP/1.0 301 Moved Permanently");
		header("Status: 301 Moved Permanently");
		header('Location: ' . FULLWEBPATH . '/' . preg_replace('~^' . WEBPATH . '/~', '', $redirectURL));
		exit();
	}

	/**
	 * checks if there is a file with the prefix and one of the
	 * handled suffixes. Returns the found suffix
	 *
	 * @param type $path SERVER path to be tested
	 * @return string
	 */
	protected static function isHandledAlbum($path) {
		global $_albumHandlers;
		foreach (array_keys($_albumHandlers) as $suffix) {
			if (file_exists($path . '.' . $suffix)) {
				//	it is a handled album sans suffix
				return $suffix;
			}
		} return NULL;
	}

	protected static function load_page() {
		global $_current_page;
		if (isset($_GET['page'])) {
			$_current_page = sanitize_numeric($_GET['page']);
		} else {
			$_current_page = 1;
		}
	}

	/**
	 * initializes the gallery.
	 */
	static function load_gallery() {
		global $_current_album, $_current_album_restore, $__albums,
		$_current_image, $_current_image_restore, $__images, $_current_comment,
		$_comments, $_current_context, $_current_search,
		$_CMS_current_page, $_CMS_current_category, $_post_date, $_pre_authorization;
		$_current_album = NULL;
		$_current_album_restore = NULL;
		$__albums = NULL;
		$_current_image = NULL;
		$_current_image_restore = NULL;
		$__images = NULL;
		$_current_comment = NULL;
		$_comments = NULL;
		$_current_context = 0;
		$_current_search = NULL;
		$_CMS_current_article = NULL;
		$_CMS_current_page = NULL;
		$_CMS_current_category = NULL;
		$_post_date = NULL;
		$_pre_authorization = array();
		set_context(NPG_INDEX);
	}

	/**
	 * Loads the search object.
	 */
	static function load_search() {
		global $_current_search;
		clearNPGCookie("search_params");
		if (!is_object($_current_search)) {
			$_current_search = new SearchEngine();
		}
		add_context(NPG_SEARCH);
		$params = urldecode($_current_search->getSearchParams());
		setNPGCookie("search_params", $params, SEARCH_DURATION);
		return $_current_search;
	}

	/**
	 * load_album - loads the album given by the folder name $folder into the
	 * global context, and sets the context appropriately.
	 * @param $folder the folder name of the album to load. Ex: 'testalbum', 'test/subalbum', etc.
	 * @param $force_cache whether to force the use of the global object cache.
	 * @return the loaded album object on success, or (===false) on failure.
	 */
	static function load_album($folder, $force_nocache = false) {
		global $_current_album, $_gallery, $_albumHandlers;
		$path = internalToFilesystem(getAlbumFolder(SERVERPATH) . $folder);

		$handled = array();
		foreach (array_keys($_albumHandlers) as $key => $suffix) {
			$handled[$key] = '.' . $suffix;
		}
		array_push($handled, '');

		if (!is_dir($path)) {
			//see if there is a dynamic album in the path
			$parents = array();
			$folders = explode('/', $folder);
			$build = '';
			$album = NULL;
			foreach ($folders as $try) {
				if ($build) {
					$build .= '/';
				}
				$build .= $try;
				if ($album) {
					// find within the album's subalbums
					$subalbums = $album->getAlbums();
					$parents[$try] = $album->name;
					$fail = true;
					$c = 0;
					foreach ($subalbums as $sub) {
						$c++;
						foreach ($handled as $suffix) {
							if ($try . $suffix == basename($sub)) {
								$album = newAlbum($sub);
								$album->linkname = $build;
								$album->parentLinks = $parents;
								$album->index = $c;
								$fail = false;
								break;
							}
						}
					}
					if ($fail) {
						$album = NULL;
						break;
					}
				} else {
					if (is_dir($path = internalToFilesystem(getAlbumFolder(SERVERPATH) . $build))) {
						// natural album
						$parents[$try] = $build;
					} else {
						//	dynamic album in path?
						if ($suffix = self::isHandledAlbum($path)) {
							$suffix = '.' . $suffix;
						}
						$album = newAlbum($build . $suffix, !$force_nocache, true);
						if (!is_object($album) || !$album->exists) {
							//	404 material
							$album = NULL;
							break;
						}
						$album->linkname = $build;
						$album->parentLinks = $parents;
					}
				}
			}

			$_current_album = $album;
		} else {
			$_current_album = newAlbum($folder, !$force_nocache, true);
		}
		if (!is_object($_current_album) || !$_current_album->exists) {
			if ($force_nocache) {
				return false;
			}
			$rimage = basename($folder);
			$ralbum = dirname($folder);
			$image = self::load_image($ralbum, $rimage);
			if ($image && $image->getFileName() != $rimage) {
				$suffix = false;
				if (RW_SUFFIX && !preg_match('|^(.*)' . preg_quote(RW_SUFFIX) . '$|', $rimage)) {
					// must be missing the rewrite suffix
					$suffix = RW_SUFFIX;
				} else if (!UNIQUE_IMAGE) {
					//missing the file suffix
					$suffix = '.' . getSuffix($image->getFileName());
				}
				if ($suffix) {
					self::fix_suffix_redirect($rimage, $suffix);
				}
			}
			return $image;
		}
		add_context(NPG_ALBUM);
		return $_current_album;
	}

	/**
	 * load_image - loads the image given by the $folder and $filename into the
	 * global context, and sets the context appropriately.
	 * @param $folder is the folder name of the album this image is in. Ex: 'testalbum'
	 * @param $filename is the filename of the image to load.
	 * @return the loaded album object on success, or (===false) on failure.
	 */
	static function load_image($folder, $filename) {
		global $_supported_images, $_current_image, $_current_album, $_current_search, $_current_page;
		if (!is_object($_current_album) || $_current_album->name != $folder) {
			$album = self::load_album($folder, true);
		} else {
			$album = $_current_album;
		}
		if (!is_object($album) || !$album->exists) {
			return false;
		}
		$images = $album->getImages();
		if (!empty($images) && !in_array(getSuffix($filename), $_supported_images)) { //	still some work to do
			foreach ($images as $image) {
				if (is_array($image)) {
					$image = $image['filename'];
				}
				if (stripSuffix($image) == $filename) {
					$filename = $image;
					break;
				}
			}
		}
		if ($album->isDynamic() && $_current_page) {
			$matches = array_keys($album->imageNames, $filename);
			if (isset($matches[$_current_page - 1]) && $albumName = $matches[$_current_page - 1]) {
				$filename = array('folder' => dirname($albumName), 'filename' => $filename);
			}
			$_current_page = NULL;
		}
		$_current_image = newImage($album, $filename, true);
		if (!is_object($_current_image) || !$_current_image->exists) {
			return false;
		}
		$_current_image->albumanmealbum = $album;

		add_context(NPG_IMAGE | NPG_ALBUM);
		return $_current_image;
	}

	/**
	 * Loads a zenpage pages page
	 * Sets up $_CMS_current_page and returns it as the function result.
	 * @param $titlelink the titlelink of a zenpage page to setup a page object directly. Used for custom
	 * page scripts based on a zenpage page.
	 *
	 * @return object
	 */
	static function load_zenpage_pages($titlelink) {
		global $_CMS_current_page;
		$_CMS_current_page = newPage($titlelink);
		if ($_CMS_current_page->loaded) {
			add_context(ZENPAGE_PAGE | ZENPAGE_SINGLE);
		} else {
			//check if it is an old link missing the suffix adn redirect if so
			if (RW_SUFFIX && !preg_match('|^(.*)' . preg_quote(RW_SUFFIX) . '$|', $titlelink)) {
				$_CMS_current_page = newPage($titlelink . RW_SUFFIX);
				if ($_CMS_current_page->loaded) {
					self::fix_suffix_redirect($titlelink);
				}
			}
			$_GET['p'] = 'PAGES:' . $titlelink;
			return NULL;
		}
		return $_CMS_current_page;
	}

	/**
	 * Loads a zenpage news article
	 * Sets up $_CMS_current_article and returns it as the function result.
	 *
	 * @param array $request an array with one member: the key is "date", "category", or "title" and specifies
	 * what you want loaded. The value is the date or title of the article wanted
	 *
	 * @return object
	 */
	static function load_zenpage_news($request) {
		global $_CMS_current_article, $_CMS_current_category, $_post_date;
		if (isset($request['date'])) {
			add_context(ZENPAGE_NEWS_DATE);
			$_post_date = sanitize(trim($request['date'], '/'));
		}
		if (isset($request['category'])) {
			$titlelink = sanitize(trim($request['category'], '/'));
			$_CMS_current_category = new Category($titlelink);
			if ($_CMS_current_category->loaded) {
				add_context(ZENPAGE_NEWS_CATEGORY);
			} else {
				$_GET['p'] = 'CATEGORY:' . $titlelink;
				unset($_GET['category']);
				return false;
			}
		}
		if (isset($request['title'])) {
			$titlelink = sanitize(trim($request['title'], '/'));
			$sql = 'SELECT `id` FROM ' . prefix('news') . ' WHERE `titlelink`=' . db_quote($titlelink) . ' LIMIT 1';
			$found = query($sql);
			if ($found && $found->num_rows > 0) {
				add_context(ZENPAGE_NEWS_ARTICLE | ZENPAGE_SINGLE);
				$_CMS_current_article = newArticle($titlelink);
			} else {
				//check if it is an old link missing the suffix and redirect if so
				if (RW_SUFFIX && !preg_match('|^(.*)' . preg_quote(RW_SUFFIX) . '$|', $titlelink)) {
					$sql = 'SELECT `id` FROM ' . prefix('news') . ' WHERE `titlelink`=' . db_quote($titlelink . RW_SUFFIX) . ' LIMIT 1';
					$found = query($sql);
					if ($found && $found->num_rows > 0) {
						self::fix_suffix_redirect($titlelink);
					}
				}
				$_GET['p'] = 'NEWS:' . $titlelink;
			}
			return $_CMS_current_article;
		}
		return true;
	}

	/**
	 * Figures out what is being accessed and calls the appropriate load function
	 *
	 * @return bool
	 */
	static function load_request() {
		global $_CMS;
		if ($success = npgFilters::apply('load_request', true)) { // filter allowed the load
			self::load_page();
			if (isset($_GET['p'])) {
				$page = str_replace(array('/', '\\', '.'), '', sanitize($_GET['p']));
				switch ($page) {
					case 'search':
						return self::load_search();
					case 'pages':
						if (class_exists('CMS') && $_CMS->pages_enabled) {
							return self::load_zenpage_pages(sanitize(trim(isset($_GET['title']) ? $_GET['title'] : NULL, '/')));
						}
						return false;
					case 'news':
						if (class_exists('CMS') && $_CMS->news_enabled) {
							return self::load_zenpage_news(sanitize($_GET));
						}
						return false;
					case 'functions':
					case 'themeoptions':
					case 'theme_description':
						return false; //	disallowed as theme pages
				}
			}

			//	may need image and album parameters processed
			list($album, $image) = rewrite_get_album_image('album', 'image');
			if (!empty($image)) {
				return self::load_image($album, $image);
			} else if (!empty($album)) {
				return self::load_album($album);
			}
		}
		return $success;
	}

	/**
	 *
	 * sets up for loading the index page
	 * @return string
	 */
	static function prepareIndexPage() {
		global $_gallery_page, $_themeScript, $_current_page;
		setNPGCookie('index_page_paged', $_current_page, FALSE);
		handleSearchParms('index');
		$theme = setupTheme();
		$_gallery_page = basename($_themeScript = THEMEFOLDER . "/$theme/index.php");
		return $theme;
	}

	/**
	 *
	 * sets up for loading an album page
	 */
	static function prepareAlbumPage() {
		global $_current_album, $_gallery_page, $_themeScript;
		$theme = setupTheme();
		$_gallery_page = "album.php";
		$_themeScript = THEMEFOLDER . "/$theme/album.php";
		if ($search = $_current_album->getSearchEngine()) {
			setNPGCookie("search_params", $search->getSearchParams(), SEARCH_DURATION);
		} else {
			handleSearchParms('album', $_current_album);
		}
		return $theme;
	}

	/**
	 *
	 * sets up for loading an image page
	 * @return string
	 */
	static function prepareImagePage() {
		global $_current_album, $_current_image, $_gallery_page, $_themeScript;
		handleSearchParms('image', $_current_album, $_current_image);
		$theme = setupTheme();
		$_gallery_page = basename($_themeScript = THEMEFOLDER . "/$theme/image.php");
		// re-initialize video dimensions if needed
		if ($_current_image->isVideo()) {
			$_current_image->updateDimensions();
		}
		return $theme;
	}

	/**
	 *
	 * sets up for loading p=page pages
	 * @return string
	 */
	static function prepareCustomPage() {
		global $_current_album, $_current_image, $_gallery_page, $_themeScript, $_current_search;
		$searchalbums = handleSearchParms('page', $_current_album, $_current_image);
		$album = NULL;
		$page = str_replace(array('/', '\\', '.'), '·', sanitize($_GET['p']));
		$_gallery_page = $page . '.php';
		switch ($_gallery_page) {
			case 'search.php':
				if (!empty($searchalbums)) { //	we are within a search of a specific album(s)
					$albums = array();
					foreach ($searchalbums as $analbum) {
						$album = newAlbum($analbum, true, true);
						if (is_object($album) && $album->exists) {
							$parent = $album->getUrAlbum();
							$albums[$parent->getID()] = $parent;
						}
					}
					if (count($albums) == 1) { // there is only one parent album for the search
						$album = reset($albums);
					}
				}
				break;
		}

		$theme = setupTheme($album);
		if (empty($_themeScript)) {
			$_themeScript = THEMEFOLDER . "/$theme/$page.php";
		}
		return $theme;
	}

}

//force license page if not acknowledged
if (!getOption('license_accepted')) {
	if (isset($_GET['z']) && $_GET['z'] != 'setup') {
		// License needs agreement
		$_GET['p'] = 'license';
		$_GET['z'] = '';
	}
}

