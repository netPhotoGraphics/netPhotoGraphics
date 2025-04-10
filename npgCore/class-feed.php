<?php

/**
 *
 * Base feed class from which all others descend.
 *
 * Plugins will set the <var>feedtype</var> property to the feed desired
 * <ul>
 * 	<li>gallery</li>
 * 	<li>news</li>
 * 	<li>pages</li>
 * 	<li>comments</li>
 * </ul>
 *
 * Feed details are determined by the <var>option</var> property.
 * Elements of this array and their meaning follow:
 * <ul>
 * 	<li>lang
 * 		<ul>
 * 			<li><i>locale</i></li>
 * 		</ul>
 * 	</li>
 * 	<li>sortdir
 * 		<ul>
 * 			<li>desc (default) for descending order</li>
 * 			<li>asc for ascending order</li>
 * 		</ul>
 * 	</li>
 * 	<li>sortorder</li>
 * 		<ul>
 * 			<li><i>latest</i> (default) for the latest uploaded by id (discovery order)</li>
 * 			<li><i>latest-date</i> for the latest fetched by date</li>
 * 			<li><i>latest-mtime</i> for the latest fetched by mtime</li>
 * 			<li><i>latest-publishdate</i> for the latest fetched by publishdate</li>
 * 			<li><i>popular</i> for the most popular albums</li>
 * 			<li><i>topratedv for the best voted</li>
 * 			<li><i>mostrated</i> for the most voted</li>
 * 			<li><i>random</i> for random order</li>
 * 			<li><i>id</i> internal <var>id</var> order</li>
 * 		</ul>
 * 	</li>
 * 	<li>albumname</li>
 * 	<li>albumsmode</li>
 * 	<li>folder</li>
 * 	<li>size</li>
 * 	<li>category</li>
 * 	<il>id</li>
 * 	<li>itemnumber</li>
 * 	<li>type (for comments feed)
 * 		<ul>
 * 			<li>albums</li>
 * 			<li>images</li>
 * 			<li>pages</li>
 * 			<li>news</li>
 * 		</ul>
 * 	</li>
 * </ul>
 *
 * @author Stephen Billard (sbillard)
 *
 * @package classes
 */
require_once(CORE_SERVERPATH . 'template-functions.php');

class feed {

	protected $feed = 'feed'; //	feed type
	protected $mode; //	feed mode
	protected $options; // This array will store the options for the feed.
	//general feed type gallery, news or comments
	protected $feedtype = NULL;
	protected $itemnumber = NULL;
	protected $locale = NULL; // standard locale for lang parameter
	protected $locale_xml = NULL; // xml locale within feed
	protected $host = NULL;
	protected $sortorder = NULL;
	protected $sortdirection = NULL;
	//gallery feed specific vars
	protected $albumfolder = NULL;
	protected $collection = NULL;
	protected $albumpath = NULL;
	protected $imagepath = NULL;
	protected $imagesize = NULL;
	protected $modrewritesuffix = NULL;
	// Zenpage news feed specific
	protected $catlink = NULL;
	protected $cattitle = NULL;
	protected $newsoption = NULL;
	protected $titleappendix = NULL;
	//comment feed specific
	protected $id = NULL;
	protected $commentfeedtype = NULL;
	protected $itemobj = NULL; // if comments for an item its object
	//channel vars
	protected $channel_title = NULL;
	protected $feeditem = array();

	/**
	 * Creates a file name from the options array
	 *
	 * @return string
	 */
	protected function getCacheFilename() {
		$filename = array();
		foreach ($this->options as $key => $value) {
			if (empty($value) || $key == 'albumsmode') { // supposed to be empty always
				$filename[] = $key;
			} else {
				$filename[] = $value;
			}
		}
		$filename = seoFriendly(implode('_', $filename));
		return $filename . ".xml";
	}

	/**
	 * Starts static caching
	 *
	 */
	protected function startCache() {
		$caching = getOption($this->feed . "_cache") && !npg_loggedin();
		if ($caching) {
			$cachefilepath = SERVERPATH . '/' . STATIC_CACHE_FOLDER . '/' . strtolower($this->feed) . '/' . internalToFilesystem($this->getCacheFilename());
			if (file_exists($cachefilepath)) {
				if (time() - filemtime($cachefilepath) < getOption($this->feed . "_cache_expire")) {
					echo file_get_contents($cachefilepath);
					exit();
				} else {
					@chmod($cachefilepath, 0777);
					@unlink($cachefilepath);
				}
			}
			ob_start();
		}
	}

	/**
	 * Ends the static caching.
	 *
	 */
	protected function endCache() {
		$caching = getOption($this->feed . "_cache") && !npg_loggedin();
		if ($caching) {
			$cachefilepath = internalToFilesystem($this->getCacheFilename());
			if (!empty($cachefilepath)) {
				$cachefilepath = SERVERPATH . '/' . STATIC_CACHE_FOLDER . '/' . strtolower($this->feed) . '/' . $cachefilepath;
				mkdir_recursive(SERVERPATH . '/' . STATIC_CACHE_FOLDER . '/' . strtolower($this->feed) . '/', FOLDER_MOD);
				$pagecontent = ob_get_clean();
				if ($fh = fopen($cachefilepath, "w")) {
					fputs($fh, $pagecontent);
					fclose($fh);
				}
				echo $pagecontent;
			}
		}
	}

	/**
	 * Cleans out the cache folder
	 *
	 * @param string $cachefolder the sub-folder to clean
	 */
	function clearCache($cachefolder = NULL) {
		npgFunctions::removeDir(SERVERPATH . '/' . STATIC_CACHE_FOLDER . '/' . strtolower($this->feed) . '/' . $cachefolder, true);
	}

	function __construct($options) {
		$this->options = $options;
		$invalid_options = array();
		$this->locale = $this->getLang();
		$this->locale_xml = strtr($this->locale, '_', '-');
		$this->sortdirection = $this->getSortdir();
		$this->sortorder = $this->getSortorder();
		switch ($this->feedtype) {
			case 'comments':
				$this->commentfeedtype = $this->getCommentFeedType();
				$this->id = $this->getId();
				$invalid_options = array('albumsmode', 'folder', 'albumname', 'category', 'size');
				break;
			case 'gallery':
				if (isset($this->options['albumsmode'])) {
					$this->mode = 'albums';
				}
				if (isset($this->options['folder'])) {
					$this->albumfolder = $this->getAlbum('folder');
					$this->collection = true;
				} else if (isset($this->options['albumname'])) {
					$this->albumfolder = $this->getAlbum('albumname');
					$this->collection = false;
				} else {
					$this->collection = false;
				}
				if (is_null($this->sortorder)) {
					if ($this->mode == "albums") {
						$this->sortorder = getOption($this->feed . "_sortorder_albums");
					} else {
						$this->sortorder = getOption($this->feed . "_sortorder");
					}
				}
				$this->imagesize = $this->getImageSize();
				$invalid_options = array('id', 'type', 'category');
				break;
			case 'news':
				if ($this->sortorder == 'latest') {
					$this->sortorder = NULL;
				}
				$this->catlink = $this->getCategory();
				if (!empty($this->catlink)) {
					$catobj = new Category($this->catlink);
					$this->cattitle = $catobj->getTitle();
					$this->newsoption = 'category';
				} else {
					$this->catlink = '';
					$this->cattitle = '';
					$this->newsoption = 'news';
				}
				$invalid_options = array('folder', 'albumname', 'albumsmode', 'type', 'id', 'size');
				break;
			case 'pages':
				$invalid_options = array('folder', 'albumname', 'albumsmode', 'type', 'id', 'category', 'size');
				break;
			case 'null': //we just want the class instantiated
				return;
		}
		$this->unsetOptions($invalid_options); // unset invalid options that this feed type does not support
		if (isset($this->options['itemnumber'])) {
			$this->itemnumber = (int) $this->options['itemnumber'];
		} else {
			$this->itemnumber = getOption($this->feed . '_items');
		}
	}

	/**
	 * Validates and gets the "lang" parameter option value
	 *
	 * @global array $_active_languages
	 * @return string
	 */
	protected function getLang() {
		if (isset($this->options['lang'])) {
			$langs = i18n::generateLanguageList();
			$valid = array_values($langs);
			if (in_array($this->options['lang'], $valid)) {
				return $this->options['lang'];
			}
		}
		return SITE_LOCALE;
	}

	/**
	 * Validates and gets the "sortdir" parameter option value
	 *
	 * @return bool
	 */
	protected function getSortdir() {
		$valid = array('desc', 'asc');
		if (isset($this->options['sortdir']) && in_array($this->options['sortdir'], $valid)) {
			return strtolower($this->options['sortdir']) != 'asc';
		}
		$this->options['sortdir'] = 'desc'; // make sure this is a valid default name
		return TRUE;
	}

	/**
	 * Validates and gets the "sortorder" parameter option value
	 *
	 * @return string
	 */
	protected function getSortorder() {
		if (isset($this->options['sortorder'])) {
			$valid = array('latest', 'latest-date', 'latest-mtime', 'latest-publishdate', 'popular', 'toprated', 'mostrated', 'random', 'id');
			if (in_array($this->options['sortorder'], $valid)) {
				$this->options['sortdir'] = $this->options['sortorder']; // make sure this is a valid default name
				return $this->options['sortorder'];
			} else {
				$this->unsetOptions(array('sortorder'));
			}
		}
		return NULL;
	}

	/**
	 * Validates and gets the "type" parameter option value for comment feeds
	 *
	 * @return string
	 */
	protected function getCommentFeedType() {
		if (isset($this->options['type'])) {
			if (in_array($this->options['type'], array('image', 'album', 'pages'))) {
				$this->options['type'] = $this->options['type'] . 's'; //	some old feeds have the singular
				return $this->options['type'];
			}
			if (in_array($this->options['type'], array('albums', 'images', 'pages', 'news'))) {
				return $this->options['type'];
			}
		}
		return 'all';
	}

	/**
	 * Validates and gets the "id" parameter option value for comments feeds of a specific item
	 *
	 * @return int
	 */
	protected function getID() {
		if (isset($this->options['id'])) {
			$type = $this->getCommentFeedType();
			if ($type != 'all') {
				$id = (int) $this->options['id'];
				$found = query('SELECT `id` FROM ' . prefix($type) . ' WHERE id =' . $id . ' LIMIT 1');
				if ($found && db_num_rows($found) > 0) {
					return $id;
				}
			}
		}
		$this->unsetOptions(array('id'));
		return NULL;
	}

	/**
	 * Validates and gets the "folder" or 'albumname" parameter option value
	 * @param string $option "folder" or "albumname"
	 * @return int
	 */
	protected function getAlbum($option) {
		if (in_array($option, array('folder', 'albumname')) && isset($this->options[$option])) {
			$albumobj = newAlbum($this->options[$option], true, true);
			if ($albumobj->exists) {
				return $this->options[$option];
			}
		}
		$this->unsetOptions(array($option));
		return NULL;
	}

	/**
	 * Validates and gets the "category" parameter option value
	 *
	 * @return int
	 */
	protected function getCategory() {
		if (isset($this->options['category']) && class_exists('Category')) {
			$catobj = newCategory($this->options['category']);
			if ($catobj->exists) {
				return $this->options['category'];
			}
		}
		$this->unsetOptions(array('category'));
		return NULL;
	}

	protected function getChannelTitleExtra() {
		switch ($this->sortorder) {
			default:
			case 'latest':
			case 'latest-date':
			case 'latest-mtime':
			case 'latest-publishdate':
				if ($this->mode == 'albums') {
					$albumextra = ' (' . gettext('Latest albums') . ')'; //easier to understand for translators as if I would treat "images"/"albums" in one place separately
				} else {
					$albumextra = ' (' . gettext('Latest images') . ')';
				}
				break;
			case 'latestupdated':
				$albumextra = ' (' . gettext('latest updated albums') . ')';
				break;
			case 'popular':
				if ($this->mode == 'albums') {
					$albumextra = ' (' . gettext('Most popular albums') . ')';
				} else {
					$albumextra = ' (' . gettext('Most popular images') . ')';
				}
				break;
			case 'toprated':
				if ($this->mode == 'albums') {
					$albumextra = ' (' . gettext('Top rated albums') . ')';
				} else {
					$albumextra = ' (' . gettext('Top rated images') . ')';
				}
				break;
			case 'random':
				if ($this->mode == 'albums') {
					$albumextra = ' (' . gettext('Random albums') . ')';
				} else {
					$albumextra = ' (' . gettext('Random images') . ')';
				}
				break;
		}
		return $albumextra;
	}

	/**
	 * Helper function that gets the images size of the "size" get parameter
	 *
	 * @return string
	 */
	protected function getImageSize() {
		if (isset($this->options['size'])) {
			$imagesize = (int) $this->options['size'];
		} else {
			$imagesize = NULL;
		}
		if ($this->mode == 'albums') {
			if (is_null($imagesize) || $imagesize > getOption($this->feed . '_imagesize_albums')) {
				$imagesize = getOption($this->feed . '_imagesize_albums'); // un-cropped image size
			}
		} else {
			if (is_null($imagesize) || $imagesize > getOption($this->feed . '_imagesize')) {
				$imagesize = getOption($this->feed . '_imagesize'); // un-cropped image size
			}
		}
		return $imagesize;
	}

	/**
	 * Unsets certain option name indices from the $options property.
	 * @param array $options Array of option (parameter) names to be unset
	 */
	protected function unsetOptions($options = null) {
		if (!empty($options)) {
			foreach ($options as $option) {
				unset($this->options[$option]);
			}
		}
	}

	/**
	 * Gets the feed items
	 *
	 * @return array
	 */
	public function getitems() {
		global $_CMS;
		switch ($this->feedtype) {
			case 'gallery':
				if ($this->mode == "albums") {
					$items = getAlbumStatistic($this->itemnumber, $this->sortorder, $this->albumfolder, 0, $this->sortdirection);
				} else {
					$items = getImageStatistic($this->itemnumber, $this->sortorder, $this->albumfolder, $this->collection, 0, $this->sortdirection);
				}
				break;
			case 'news':
				switch ($this->newsoption) {
					case "category":
						if ($this->sortorder) {
							$items = getZenpageStatistic($this->itemnumber, 'categories', $this->sortorder, $this->sortdirection);
						} else {
							$items = getLatestNews($this->itemnumber, $this->catlink, false, $this->sortdirection);
						}
						break;
					default:
					case "news":
						if ($this->sortorder) {
							$items = getZenpageStatistic($this->itemnumber, 'news', $this->sortorder, $this->sortdirection);
						} else {
							// Needed baceause type variable "news" is used by the feed item method and not set by the class method getArticles!
							$items = getLatestNews($this->itemnumber, '', false, $this->sortdirection);
						}
						break;
				}
				break;
			case "pages":
				if ($this->sortorder) {
					$items = getZenpageStatistic($this->itemnumber, 'pages', $this->sortorder, $this->sortdirection);
				} else {
					$items = $_CMS->getPages(NULL, false, $this->itemnumber);
				}
				break;
			case 'comments':
				switch ($type = $this->commentfeedtype) {
					case 'gallery':
						$items = getLatestComments($this->itemnumber, 'all');
						break;
					case 'albums':
						$items = getLatestComments($this->itemnumber, 'album', $this->id);
						break;
					case 'images':
						$items = getLatestComments($this->itemnumber, 'image', $this->id);
						break;
					case 'news':
					case 'pages':
						if (function_exists('getLatestZenpageComments')) {
							$items = getLatestZenpageComments($this->itemnumber, $type, $this->id);
						}
						break;
					case 'allcomments':
						$items_alb = getLatestComments($this->itemnumber, 'album');
						$items_img = getLatestComments($this->itemnumber, 'image');
						$items_zenpage = array();
						if (function_exists('getLatestZenpageComments')) {
							$items_zenpage = getLatestZenpageComments($this->itemnumber);
							$items = array_merge($items, $items_zenpage);
							$items = sortMultiArray($items, ['date' => true]);
							$items = array_slice($items, 0, $this->itemnumber);
						}
						break;
				}
				break;
		}
		if (isset($items)) {
			return $items;
		}
		if (DEBUG_FEED) {
			debugLogBacktrace(gettext('Bad ' . $this->feed . ' feed:' . $this->feedtype . (isset($type) ? '»' . $type : '')), E_USER_WARNING);
		}
		return NULL;
	}

	/**
	 * Gets the feed item data in a gallery feed
	 *
	 * @param object $item Object of an image or album
	 * @return array
	 */
	protected function getItemGallery($item) {
		if ($this->mode == "albums") {
			$albumobj = $item;
			$totalimages = $albumobj->getNumImages();
			$itemlink = $this->host . $albumobj->getLink();
			$thumb = $albumobj->getAlbumThumbImage();
			$thumburl = '<img border="0" src="' . FULLHOSTPATH . html_encode($thumb->getCustomImage(array('size' => $this->imagesize, 'thumb' => TRUE))) . '" alt="' . html_encode($albumobj->getTitle($this->locale)) . '" />';
			$title = $albumobj->getTitle($this->locale);
			if ($this->sortorder == "latestupdated") {
				$filechangedate = filectime(ALBUM_FOLDER_SERVERPATH . internalToFilesystem($albumobj->name));
				$latestimage = query_single_row("SELECT mtime FROM " . prefix('images') . " WHERE albumid = " . $albumobj->getID() . " AND `show`=1 ORDER BY id DESC");
				if ($latestimage && $this->sortorder == 'latestupdated') {
					$count = db_count('images', "WHERE albumid = " . $albumobj->getID() . " AND mtime = " . $latestimage['mtime']);
				} else {
					$count = $totalimages;
				}
				if ($count != 0) {
					$imagenumber = sprintf(ngettext('%s (%u image)', '%s (%u images)', $count), $title, $count);
				} else {
					$imagenumber = $title;
				}
				$feeditem['desc'] = '<a title="' . $title . '" href="' . PROTOCOL . '://' . $itemlink . '">' . $thumburl . '</a>' .
								'<p>' . html_encode($imagenumber) . '</p>' . $albumobj->getDesc($this->locale) . '<br />' . sprintf(gettext("Last update: %s"), formattedDate(DATE_FORMAT, $filechangedate));
			} else {
				if ($totalimages != 0) {
					$imagenumber = sprintf(ngettext('%s (%u image)', '%s (%u images)', $totalimages), $title, $totalimages);
				} else {
					$imagenumber = $title;
				}
				$feeditem['desc'] = '<a title="' . html_encode($title) . '" href="' . PROTOCOL . '://' . $itemlink . '">' . $thumburl . '</a>' . $item->getDesc($this->locale) . '<br />' . sprintf(gettext("Date: %s"), formattedDate(DATE_FORMAT, $item->get('mtime')));
			}
			$ext = getSuffix($thumb->localpath);
		} else {
			$ext = getSuffix($item->localpath);
			$albumobj = $item->getAlbum();
			$itemlink = $item->getLink();
			$fullimagelink = html_encode($item->getFullImageURL());
			$thumburl = '<img border="0" src="' . FULLHOSTPATH . html_encode($item->getCustomImage(array('size' => $this->imagesize, 'thumb' => TRUE))) . '" alt="' . $item->getTitle($this->locale) . '" /><br />';
			$title = $item->getTitle($this->locale);
			$albumtitle = $albumobj->getTitle($this->locale);
			$datecontent = '<br />Date: ' . formattedDate(DATE_FORMAT, $item->get('mtime'));
			if ((($ext == "flv") || ($ext == "mp3") || ($ext == "mp4") || ($ext == "3gp") || ($ext == "mov")) AND $this->mode != "album") {
				$feeditem['desc'] = '<a title="' . html_encode($title) . ' in ' . html_encode($albumobj->getTitle($this->locale)) . '" href="' . FULLHOSTPATH . $itemlink . '">' . $thumburl . '</a>' . $item->getDesc($this->locale) . $datecontent;
			} else {
				$feeditem['desc'] = '<a title="' . html_encode($title) . ' in ' . html_encode($albumobj->getTitle($this->locale)) . '" href="' . FULLHOSTPATH . $itemlink . '"><img src="' . FULLHOSTPATH . html_encode($item->getCustomImage(array('size' => $this->imagesize, 'thumb' => TRUE))) . '" alt="' . html_encode($title) . '" /></a>' . $item->getDesc($this->locale) . $datecontent;
			}
		}
		// title
		if ($this->mode != "albums") {
			$feeditem['title'] = sprintf('%1$s (%2$s)', $item->getTitle($this->locale), $albumobj->getTitle($this->locale));
		} else {
			$feeditem['title'] = $imagenumber;
		}
		//link
		$feeditem['link'] = FULLHOSTPATH . $itemlink;

		// enclosure
		$feeditem['enclosure'] = '';
		if (getOption("RSS_enclosure") AND $this->mode != "albums") {
			$feeditem['enclosure'] = '<enclosure url="' . FULLHOSTPATH . $fullimagelink . '" type="' . mimeTypes::getType($ext) . '" length="' . filesize($item->localpath) . '" />';
		}
		//category
		if ($this->mode != "albums") {
			$feeditem['category'] = html_encode($albumobj->getTitle($this->locale));
		} else {
			$feeditem['category'] = html_encode($albumobj->getTitle($this->locale));
		}
		//media content
		$feeditem['media_content'] = '';
		$feeditem['media_thumbnail'] = '';
		if (getOption("RSS_mediarss") AND $this->mode != "albums") {
			$feeditem['media_content'] = '<media:content url="' . FULLHOSTPATH . $fullimagelink . '" type="image/jpeg" />';
			$feeditem['media_thumbnail'] = '<media:thumbnail url="' . FULLHOSTPATH . $fullimagelink . '" width="' . $this->imagesize . '"	height="' . $this->imagesize . '" />';
		}
		//date
		if ($this->mode != "albums") {
			$feeditem['pubdate'] = formattedDate("r", strtotime($item->getPublishDate()));
		} else {
			$feeditem['pubdate'] = formattedDate("r", strtotime($albumobj->getPublishDate()));
		}
		return $feeditem;
	}

	/**
	 * Gets the feed item data in a Zenpage news feed
	 *
	 * @param array $item Titlelink a Zenpage article or filename of an image if a combined feed
	 * @return array
	 */
	protected function getItemNews($item) {
		$categories = '';
		$feeditem['enclosure'] = '';
		$obj = newArticle($item['titlelink']);
		$title = $feeditem['title'] = get_language_string($obj->getTitle('all'), $this->locale);
		$link = $obj->getLink();
		$count2 = 0;
		$plaincategories = $obj->getCategories();
		$categories = '';
		foreach ($plaincategories as $cat) {
			$catobj = newCategory($cat['titlelink']);
			$categories .= get_language_string($catobj->getTitle('all'), $this->locale) . ', ';
		}
		$categories = rtrim($categories, ', ');
		$feeditem['desc'] = shortenContent($obj->getContent($this->locale), getOption('RSS_truncate_length'), '...');

		if (!empty($categories)) {
			$feeditem['category'] = html_encode($categories);
			$feeditem['title'] = $title . ' (' . $categories . ')';
		}
		$feeditem['link'] = FULLHOSTPATH . $link;
		$feeditem['media_content'] = '';
		$feeditem['media_thumbnail'] = '';
		$feeditem['pubdate'] = formattedDate("r", strtotime($item['date']));

		return $feeditem;
	}

	/**
	 * Gets the feed item data in a Zenpage news feed
	 *
	 * @param array $item Titlelink a Zenpage article or filename of an image if a combined feed
	 * @return array
	 */
	protected function getitemPages($item, $len) {
		$obj = newPage($item['titlelink']);
		$feeditem['title'] = $feeditem['title'] = get_language_string($obj->getTitle('all'), $this->locale);
		$feeditem['link'] = FULLHOSTPATH . $obj->getLink();
		$desc = $obj->getContent($this->locale);
		$desc = str_replace('//<![CDATA[', '', $desc);
		$desc = str_replace('//]]>', '', $desc);
		$feeditem['desc'] = shortenContent($desc, $len, '...');
		$feeditem['enclosure'] = '';
		$feeditem['category'] = '';
		$feeditem['media_content'] = '';
		$feeditem['media_thumbnail'] = '';
		if ($pubdate = $obj->getPublishDate()) {
			$feeditem['pubdate'] = formattedDate("r", strtotime($pubdate));
		} else {
			$feeditem['pubdate'] = '';
		}

		return $feeditem;
	}

	/**
	 * Gets the feed item data in a comments feed
	 *
	 * @param array $item Array of a comment
	 * @return array
	 */
	protected function getitemComments($item) {
		if ($item['anon']) {
			$author = "";
		} else {
			$author = " " . gettext("by") . " " . $item['name'];
		}
		$commentpath = $imagetag = $title = '';
		switch ($item['type']) {
			case 'images':
				$title = get_language_string($item['title']);
				$obj = newImage(array('folder' => $item['folder'], 'filename' => $item['filename']));
				$link = $obj->getlink();
				$feeditem['pubdate'] = formattedDate("r", strtotime($item['date']));
				$category = get_language_string($item['albumtitle']);
				$website = $item['website'];
				$title = $category . ": " . $title;
				$commentpath = FULLHOSTPATH . $link . "#_comment_id_" . $item['id'];
				break;
			case 'albums':
				$obj = newAlbum($item['folder']);
				$link = rtrim($obj->getLink(), '/');
				$feeditem['pubdate'] = formattedDate("r", strtotime($item['date']));
				$title = get_language_string($item['albumtitle']);
				$website = $item['website'];
				$commentpath = FULLHOSTPATH . $link . "#_comment_id_" . $item['id'];
				break;
			case 'news':
			case 'pages':
				if (class_exists('CMS')) {
					$feeditem['pubdate'] = formattedDate("r", strtotime($item['date']));
					$category = '';
					$title = get_language_string($item['title']);
					$titlelink = $item['titlelink'];
					$website = $item['website'];
					if ($item['type'] == 'news') {
						$obj = newArticle($titlelink);
					} else {
						$obj = newPage($titlelink);
					}
					$commentpath = FULLHOSTPATH . html_encode($obj->getLink()) . "#" . $item['id'];
				} else {
					$commentpath = '';
				}

				break;
		}
		$feeditem['title'] = getBare($title . $author);
		$feeditem['link'] = $commentpath;
		$feeditem['desc'] = $item['comment'];
		return $feeditem;
	}

	static protected function feed404() {
		include(CORE_SERVERPATH . '404.php');
		exit();
	}

}

?>