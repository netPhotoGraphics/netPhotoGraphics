<?php
/**
 * This plugin handles <i>RSS</i> feeds:
 *
 * @author Stephen Billard (sbillard)
 * @package plugins/rss
 * @pluginCategory admin
 */
// force UTF-8 Ø

$plugin_is_filter = defaultExtension(910 | FEATURE_PLUGIN);
$plugin_description = gettext('The <em>RSS</em> handler.');
$plugin_notice = gettext('This plugin must be enabled to supply <em>RSS</em> feeds.') . '<br />' . gettext('<strong>Note:</strong> Theme support is required to display RSS links.');

$option_interface = 'rss_options';

npgFilters::register('site_upgrade_xml', 'rss_options::xmlfile');

class rss_options {

	function __construct() {
		global $plugin_is_filter;
		if (OFFSET_PATH == 2) {
			setOptionDefault('RSS_album_image', 1);
			setOptionDefault('RSS_comments', 1);
			setOptionDefault('RSS_articles', 1);
			setOptionDefault('RSS_pages', 1);
			setOptionDefault('RSS_article_comments', 1);
			setOptionDefault('RSS_truncate_length', '100');
			setOptionDefault('RSS_zenpage_items', '10');
			setOptionDefault('RSS_items', 10); // options for standard images rss
			setOptionDefault('RSS_imagesize', 240);
			setOptionDefault('RSS_sortorder', 'latest');
			setOptionDefault('RSS_items_albums', 10); // options for albums rss
			setOptionDefault('RSS_imagesize_albums', 240);
			setOptionDefault('RSS_sortorder_albums', 'latest');
			setOptionDefault('RSS_enclosure', '0');
			setOptionDefault('RSS_mediarss', '0');
			setOptionDefault('RSS_cache', '1');
			setOptionDefault('RSS_cache_expire', 86400);
			setOptionDefault('RSS_hitcounter', 1);
			setOptionDefault('RSS_title', 'both');

			require_once(PLUGIN_SERVERPATH . 'site_upgrade.php');
			if (site_upgrade::replace(USER_PLUGIN_SERVERPATH . 'site_upgrade/rss-closed.xml')) {
				site_upgrade::updateXML(array('rss-closed.xml' => 'RSS'));
			}
		}
	}

	function getOptionsSupported() {
		$options = array(gettext('RSS feeds enabled:') => array('key' => 'RSS_feed_list', 'type' => OPTION_TYPE_CHECKBOX_ARRAY,
						'order' => 0,
						'checkboxes' => array(gettext('Gallery') => 'RSS_album_image',
								gettext('Gallery Comments') => 'RSS_comments',
								gettext('All News') => 'RSS_articles',
								gettext('All Pages') => 'RSS_pages',
								gettext('News/Page Comments') => 'RSS_article_comments'
						),
						'desc' => gettext('Check each RSS feed you wish to activate.')),
				gettext('Image feed items:') => array('key' => 'RSS_items', 'type' => OPTION_TYPE_NUMBER,
						'order' => 1,
						'desc' => gettext("The number of new images and comments you want to appear in your site’s RSS feed")),
				gettext('Album feed items:') => array('key' => 'RSS_items_albums', 'type' => OPTION_TYPE_NUMBER,
						'order' => 2,
						'desc' => gettext("The number of new images and comments you want to appear in your site’s RSS feed")),
				gettext('Image size') => array('key' => 'RSS_imagesize', 'type' => OPTION_TYPE_NUMBER,
						'order' => 3,
						'desc' => gettext('Size of RSS image feed images:')),
				gettext('Album image size') => array('key' => 'RSS_imagesize_albums', 'type' => OPTION_TYPE_NUMBER,
						'order' => 4,
						'desc' => gettext('Size of RSS album feed images :')),
				gettext('Image feed sort order:') => array('key' => 'RSS_sortorder', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 6,
						'selections' => array(gettext('latest by id') => 'latest',
								gettext('latest by date') => 'latest-date',
								gettext('latest by mtime') => 'latest-mtime',
								gettext('latest by publishdate') => 'latest-publishdate'
						),
						'desc' => gettext("Choose between latest by id for the latest uploaded, latest by date for the latest uploaded fetched by date, or latest by mtime for the latest uploaded fetched by the file’ last change timestamp.")),
				gettext('Album feed sort order:') => array('key' => 'RSS_sortorder_albums', 'type' => OPTION_TYPE_SELECTOR,
						'selections' => array(gettext('latest by id') => 'latest',
								gettext('latest by date') => 'latest-date',
								gettext('latest by mtime') => 'latest-mtime',
								gettext('latest by publishdate') => 'latest-publishdate',
								gettext('latest updated') => 'latestupdated'
						),
						'order' => 7,
						'desc' => gettext('Choose between latest by id for the latest uploaded and latest updated')),
				gettext('RSS enclosure:') => array('key' => 'RSS_enclosure', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 8,
						'desc' => gettext('Check if you want to enable the RSS enclosure feature which provides a direct download for full images, movies etc. from within certain RSS reader clients (only Images RSS).')),
				gettext('Media RSS:') => array('key' => 'RSS_mediarss', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 9,
						'desc' => gettext('Check if media RSS support is to be enabled. This support is used by some services and programs (only Images RSS).')),
				gettext('Cache') => array('key' => 'RSS_cache', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 10,
						'desc' => sprintf(gettext('Check if you want to enable static RSS feed caching. The cached file will be placed within the <em>%s</em> folder.'), STATIC_CACHE_FOLDER)),
				gettext('Cache expiration') => array('key' => 'RSS_cache_expire', 'type' => OPTION_TYPE_NUMBER,
						'order' => 11,
						'desc' => gettext('Cache expire default is 86400 seconds (1 day = 24 hrs * 60 min * 60 sec).')),
				gettext('Hitcounter') => array('key' => 'RSS_hitcounter', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 12,
						'desc' => gettext('Check if you want to store the hitcount on RSS feeds.')),
				gettext('Title') => array('key' => 'RSS_title', 'type' => OPTION_TYPE_RADIO,
						'order' => 13,
						'buttons' => array(gettext('Gallery title') => 'gallery', gettext('Website title') => 'website', gettext('Both') => 'both'),
						'desc' => gettext("Select what you want to use as the main RSS feed (channel) title. “Both” means Website title followed by Gallery title")),
				gettext('Portable RSS link') => array('key' => 'RSS_portable_link', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 14,
						'desc' => gettext('If checked links generated for logged‑in users will include a token identifying the user. Use of that link when not logged‑in will give the same feed as if the user were logged‑in.'))
		);
		if (class_exists('CMS')) {
			$options[gettext('Feed text length')] = array('key' => 'RSS_truncate_length', 'type' => OPTION_TYPE_NUMBER,
					'order' => 5.5,
					'desc' => gettext("The text length of the Zenpage RSS feed items. No value for full length."));
			$options[gettext('Zenpage feed items')] = array('key' => 'RSS_zenpage_items', 'type' => OPTION_TYPE_NUMBER,
					'order' => 5,
					'desc' => gettext("The number of news articles you want to appear in your site’s News RSS feed."));
		}
		return $options;
	}

	function handleOption($option, $currentValue) {

	}

	function handleOptionSave() {
		if (isset($_POST['saverssoptions'])) {
			setOption('RSS_items', sanitize($_POST['RSS_items'], 3));
			setOption('RSS_imagesize', sanitize($_POST['RSS_imagesize'], 3));
			setOption('RSS_sortorder', sanitize($_POST['RSS_sortorder'], 3));
			setOption('RSS_items_albums', sanitize($_POST['RSS_items_albums'], 3));
			setOption('RSS_imagesize_albums', sanitize($_POST['RSS_imagesize_albums'], 3));
			setOption('RSS_sortorder_albums', sanitize($_POST['RSS_sortorder_albums'], 3));
			setOption('RSS_title', sanitize($_POST['RSS_title'], 3));
			setOption('RSS_cache_expire', sanitize($_POST['RSS_cache_expire'], 3));
			setOption('RSS_enclosure', (int) isset($_POST['RSS_enclosure']));
			setOption('RSS_mediarss', (int) isset($_POST['RSS_mediarss']));
			setOption('RSS_cache', (int) isset($_POST['RSS_cache']));
			setOption('RSS_album_image', (int) isset($_POST['RSS_album_image']));
			setOption('RSS_comments', (int) isset($_POST['RSS_comments']));
			setOption('RSS_articles', (int) isset($_POST['RSS_articles']));
			setOption('RSS_pages', (int) isset($_POST['RSS_pages']));
			setOption('RSS_article_comments', (int) isset($_POST['RSS_article_comments']));
			setOption('RSS_hitcounter', (int) isset($_POST['RSS_hitcounter']));
			setOption('RSS_portable_link', (int) isset($_POST['RSS_portable_link']));
			$returntab = "&tab=rss";
		}
	}

	static function xmlfile($filelist) {
		$filelist['rss-closed.xml'] = 'RSS';
		return $filelist;
	}

}

/**
 * Prints a RSS link for if (class_exists('RSS')) printRSSLink() and if (class_exists('RSS')) printRSSHeaderLink()
 *
 * @param string $option type of RSS: "Gallery" feed for latest images of the whole gallery
 * 																		"Album" for latest images only of the album it is called from
 * 																		"Collection" for latest images of the album it is called from and all of its subalbums
 * 																		"Comments" for all comments of all albums and images
 * 																		"Comments-image" for latest comments of only the image it is called from
 * 																		"Comments-album" for latest comments of only the album it is called from
 * 																		"AlbumsRSS" for latest albums
 * 																		"AlbumsRSScollection" only for latest subalbums with the album it is called from
 * 															or
 * 																		"News" feed for all news articles
 * 																		"Category" for only the news articles of the category that is currently selected
 * 																		"NewsWithImages" for all news articles and latest images
 * 																		"Comments" for all news articles and pages
 * 																		"Comments-news" for comments of only the news article it is called from
 * 																		"Comments-page" for comments of only the page it is called from
 * 																		"Comments-all" for comments from all albums, images, news articels and pages
 * 																		"Pages" feed for all pages
 * @param string $lang optional to display a feed link for a specific language. Enter the locale like "de_DE" (the locale must be installed to work of course). If empty the locale set in the admin option or the language selector (getOption('locale') is used.
 * @param string $addl provided additional data for feeds (e.g. album object for album feeds, $categorylink for zenpage categories
 */
function getRSSLink($option, $lang = NULL, $addl = NULL) {
	global $_current_album, $_current_image, $_current_admin_obj, $_CMS_current_category;
	if (empty($lang)) {
		$lang = npgFunctions::getLanguageText(getOption('locale'));
	}
	$link = NULL;
	switch (strtolower($option)) {
		case 'gallery':
			if (getOption('RSS_album_image')) {
				$link = array('rss' => 'gallery');
			}
			break;
		case 'album':
			if (getOption('RSS_album_image')) {
				if (is_object($addl)) {
					$album = $addl;
				} else {
					$album = $_current_album;
				}
				$link = array('rss' => 'gallery', 'albumname' => $album->getFileName());
				break;
			}
		case 'collection':
			if (getOption('RSS_album_image')) {
				if (is_object($addl)) {
					$album = $addl;
				} else {
					$album = $_current_album;
				}
				$link = array('rss' => 'gallery', 'folder' => $album->getFileName());
			}
			break;
		case 'comments':
			if (getOption('RSS_comments')) {
				$link = array('rss' => 'comments', 'type' => 'gallery');
			}
			break;
		case 'comments-image':
			if (getOption('RSS_comments')) {
				$link = array('rss' => 'comments', 'type' => 'images', 'id' => (string) $_current_image->getID());
			}
			break;
		case 'comments-album':
			if (getOption('RSS_comments')) {
				$link = array('rss' => 'comments', 'type' => 'albums', 'id' => (string) $_current_album->getID());
			}
			break;
		case 'albumsrss':
			if (getOption('RSS_album_image')) {
				$link = array('rss' => 'gallery', 'albumsmode' => '');
			}
			break;
		case 'albumsrsscollection':
			if (getOption('RSS_album_image')) {
				$link = array('rss' => 'gallery', 'folder' => $_current_album->getFileName(), 'albumsmode' => '');
			}
			break;
		case 'pages':
			if (getOption('RSS_pages')) {
				$link = array('rss' => 'pages');
			}
			break;
		case 'news':
			if (getOption('RSS_articles')) {
				$link = array('rss' => 'news');
			}
			break;
		case 'category':
			if (getOption('RSS_articles')) {
				if (empty($addl) && !is_null($_CMS_current_category)) {
					$addl = $_CMS_current_category->getTitlelink();
				}
				if (empty($addl)) {
					$link = array('rss' => 'news');
				} else {
					$link = array('rss' => 'news', 'category' => $addl);
				}
			}
			break;
		case 'newswithimages':
			if (getOption('RSS_articles')) {
				$link = array('rss' => 'news', 'withimages' => '');
			}
			break;
		case 'comments':
			if (getOption('RSS_article_comments')) {
				$link = array('comments' => 1, 'type' => 'zenpage');
			}
			break;
		case 'comments-news':
			if (getOption('RSS_article_comments')) {
				$link = array('rss' => 'comments', 'type' => 'news', 'id' => (string) getNewsID(), 'type' => 'news');
			}
			break;
		case 'comments-page':
			if (getOption('RSS_article_comments')) {
				$link = array('rss' => 'comments', 'type' => 'page', 'id' => (string) getPageID(), 'type' => 'page');
			}
			break;
		case 'comments-all':
			if (getOption('RSS_article_comments')) {
				$link = array('rss' => 'comments', 'type' => 'allcomments');
			}
			break;
		default:
			$link = array('rss' => '');
			break;
	}
	if (is_array($link)) {
		switch (defined('LOCALE_TYPE') ? LOCALE_TYPE : 0) {
			case 2:
			case 1:
				break;
			default:
				$link['lang'] = $lang;
				break;
		}

		if (npg_loggedin() && getOption('RSS_portable_link')) {
			$link['user'] = (string) $_current_admin_obj->getID();
			$link['token'] = rss::generateToken($link);
		}
		$uri = FULLWEBPATH . '/index.php?' . rtrim(str_replace('=&', '&', http_build_query($link)), '=');
		return $uri;
	}
	return NULL;
}

/**
 * Prints an RSS link
 *
 * @param string $option type of RSS: See getRSSLink for details
 * @param string $prev text to before before the link
 * @param string $linktext title of the link
 * @param string $next text to appear after the link
 * @param bool $printIcon print an RSS icon beside it? if true, the icon is the core rss.png image
 * @param string $class css class
 * @param string $lang optional to display a feed link for a specific language. Enter the locale like "de_DE" (the locale must be installed to work of course). If empty the locale set in the admin option or the language selector (getOption('locale') is used.
 * @param string $addl provided additional data for feeds (e.g. album object for album feeds, $categorylink for zenpage categories
 */
function printRSSLink($option, $prev, $linktext, $next, $printIcon = true, $class = false, $lang = '', $addl = NULL) {
	if ($printIcon) {
		$icon = ' <img src="' . FULLWEBPATH . '/' . CORE_FOLDER . '/images/rss.png" alt="RSS Feed" />';
	} else {
		$icon = '';
	}
	if (!$class) {
		$class = 'class="' . $class . '"';
	}
	$link = getRSSLink($option, $lang, $addl);
	if ($link) {
		echo $prev . "<a $class href=\"" . html_encode($link) . "\" title=\"" . html_encode($linktext) . "\" rel=\"nofollow\">" . $linktext . "$icon</a>" . $next;
	}
}

/**
 * Prints the RSS link for use in the HTML HEAD
 *
 * @param string $option type of RSS: "Gallery" feed for latest images of the whole gallery
 * 																		"Album" for latest images only of the album it is called from
 * 																		"Collection" for latest images of the album it is called from and all of its subalbums
 * 																		"Comments" for all comments of all albums and images
 * 																		"Comments-image" for latest comments of only the image it is called from
 * 																		"Comments-album" for latest comments of only the album it is called from
 * 																		"AlbumsRSS" for latest albums
 * 																		"AlbumsRSScollection" only for latest subalbums with the album it is called from
 * @param string $linktext title of the link
 * @param string $lang optional to display a feed link for a specific language. Enter the locale like "de_DE" (the locale must be installed to work of course). If empty the locale set in the admin option or the language selector (getOption('locale') is used.
 * @param string $addl provided additional data for feeds (e.g. album object for album feeds, $categorylink for zenpage categories
 *
 */
function printRSSHeaderLink($option, $linktext, $lang = '', $addl = NULL) {
	echo "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"" . html_encode(getBare($linktext)) . "\" href=\"" .
	PROTOCOL . '://' . html_encode($_SERVER["HTTP_HOST"]) . html_encode(getRSSLink($option, $lang, $addl)) . "\" />\n";
}

require_once(CORE_SERVERPATH . 'class-feed.php');
require_once(CORE_SERVERPATH . 'lib-MimeTypes.php');

class RSS extends feed {

	protected $feed = 'RSS';
	protected $feeditems = NULL;

	/**
	 * Creates a feed object from the URL parameters fetched
	 *
	 */
	function __construct($options = NULL) {
		global $_gallery, $_authority, $_current_admin_obj, $_loggedin, $_gallery_page;
		$_gallery_page = 'RSS.php';
		if (empty($options))
			self::feed404();

		$this->feedtype = $options['rss'];
		parent::__construct($options);

		if (isset($options['token'])) {
			//	The link camed from a logged in user, see if it is valid
			$link = $options;
			unset($link['token']);
			$token = rss::generateToken($link);
			if ($token == $options['token']) {
				$adminobj = $_authority->getAnAdmin(array('`id`=' => (int) $link['user']));
				if ($adminobj) {
					$_current_admin_obj = $adminobj;
					$_loggedin = $_current_admin_obj->getRights();
				}
			}
		}
		// general feed setup
		$channeltitlemode = getOption('RSS_title');
		$this->host = html_encode($_SERVER["HTTP_HOST"]);

		//channeltitle general
		switch ($channeltitlemode) {
			case 'gallery':
				$this->channel_title = $_gallery->getBareTitle($this->locale);
				break;
			case 'website':
				$this->channel_title = getBare($_gallery->getWebsiteTitle($this->locale));
				break;
			case 'both':
				$website_title = $_gallery->getWebsiteTitle($this->locale);
				$this->channel_title = $_gallery->getBareTitle($this->locale);
				if (!empty($website_title)) {
					$this->channel_title = $website_title . ' - ' . $this->channel_title;
				}
				break;
		}

		// individual feedtype setup
		switch ($this->feedtype) {

			case 'gallery':
				if (!getOption('RSS_album_image')) {
					self::feed404();
				}
				$albumname = $this->getChannelTitleExtra();
				if ($this->albumfolder) {
					$alb = newAlbum($this->albumfolder, true, true);
					if ($alb->exists) {
						$albumtitle = $alb->getTitle();
						if ($this->mode == 'albums' || $this->collection) {
							$albumname = ' - ' . html_encode($albumtitle) . $this->getChannelTitleExtra();
						}
					} else {
						self::feed404();
					}
				} else {
					$albumtitle = '';
				}
				$albumname = $this->getChannelTitleExtra();

				$this->channel_title = html_encode($this->channel_title . ' ' . getBare($albumname));
				require_once(PLUGIN_SERVERPATH . 'image_album_statistics.php');
				break;

			case 'news': //Zenpage News RSS
				if (!getOption('RSS_articles')) {
					self::feed404();
				}
				$titleappendix = gettext(' (Latest news)');

				switch ($this->newsoption) {
					case 'withalbums':
					case 'withalbums_mtime':
					case 'withalbums_publishdate':
					case 'withalbums_latestupdated':
						$titleappendix = gettext(' (Latest news and albums)');
						break;
					case 'withimages':
					case 'withimages_mtime':
					case 'withimages_publishdate':
						$titleappendix = gettext(' (Latest news and images)');
						break;
					default:
						switch ($this->sortorder) {
							case 'popular':
								$titleappendix = gettext(' (Most popular news)');
								break;
							case 'mostrated':
								$titleappendix = gettext(' (Most rated news)');
								break;
							case 'toprated':
								$titleappendix = gettext(' (Top rated news)');
								break;
							case 'random':
								$titleappendix = gettext(' (Random news)');
								break;
						}
						break;
				}

				if ($this->cattitle) {
					$cattitle = " - " . $this->cattitle;
				} else {
					$cattitle = "";
				}
				$this->channel_title = html_encode($this->channel_title . $cattitle . $titleappendix);
				$this->itemnumber = getOption("RSS_zenpage_items"); // # of Items displayed on the feed
				require_once(PLUGIN_SERVERPATH . 'image_album_statistics.php');
				require_once(PLUGIN_SERVERPATH . 'zenpage/template-functions.php');

				break;

			case 'pages': //Zenpage News RSS
				if (!getOption('RSS_pages')) {
					self::feed404();
				}
				switch ($this->sortorder) {
					case 'popular':
						$titleappendix = gettext(' (Most popular pages)');
						break;
					case 'mostrated':
						$titleappendix = gettext(' (Most rated pages)');
						break;
					case 'toprated':
						$titleappendix = gettext(' (Top rated pages)');
						break;
					case 'random':
						$titleappendix = gettext(' (Random pages)');
						break;
					default:
						$titleappendix = gettext(' (Latest pages)');
						break;
				}
				$this->channel_title = html_encode($this->channel_title . $titleappendix);
				require_once(PLUGIN_SERVERPATH . 'zenpage/template-functions.php');
				break;

			case 'comments': //Comments RSS

				if (!getOption('RSS_comments')) {
					self::feed404();
				}
				if ($this->id) {
					$this->itemobj = getItemByID($this->commentfeedtype, $this->id);
					if ($this->itemobj) {
						$title = ' - ' . $this->itemobj->getTitle();
					} else {
						self::feed404();
					}
				} else {
					$this->itemobj = NULL;
					$title = NULL;
				}
				$this->channel_title = html_encode($this->channel_title . $title . gettext(' (latest comments)'));
				if (class_exists('CMS')) {
					require_once(PLUGIN_SERVERPATH . 'zenpage/template-functions.php');
				}
				break;

			case 'null': // we just want the class instantiated
				return;

			default: // an unknown request
				self::feed404();
				break;
		}
		$this->feeditems = $this->getitems();
	}

	/**
	 * generates a Token that will be used to verify the validity of a portable RSS link
	 *
	 * @param array $link the link array
	 * @return type
	 */
	static function generateToken($link) {
		$token = str_replace('+', '-', base64_encode(_Authority::pbkdf2(serialize($link), HASH_SEED)));
		return $token;
	}

	/**
	 * Updates the hitcoutner for RSS in the plugin_storage db table.
	 *
	 */
	protected function hitcounter() {
		if (!npg_loggedin() && getOption('RSS_hitcounter')) {
			$rssuri = $this->getCacheFilename();
			$sql = "UPDATE " . prefix('plugin_storage') . " SET `data`=`data`+1 WHERE `type`='hitcounter' AND `subtype`='rss' AND `aux`=" . db_quote($rssuri);
			query($sql, false);
			if (!db_affected_rows()) {
				$sql = 'INSERT INTO ' . prefix('plugin_storage') . ' (`type`,`subtype`,`aux`,`data`) VALUES ("hitcounter","rss",' . db_quote($rssuri) . ',1)';
				query($sql);
			}
		}
	}

	/**
	 * Prints the RSS feed xml
	 *
	 */
	public function printFeed($feeditems = NULL) {
		global $_gallery;
		if (is_null($feeditems)) {
			$feeditems = $this->getitems();
		}

		if (is_array($feeditems)) {
			//NOTE: feeditems are complete HTML so necessarily must have been properly encoded by the server function!

			header('Content-Type: application/xml');
			$this->hitcounter();
			$this->startCache();
			echo '<?xml-stylesheet type="text/css" href="' . WEBPATH . '/' . CORE_FOLDER . '/' . PLUGIN_FOLDER . '/rss/rss.css" ?>' . "\n";
			?>
			<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">
				<channel>
					<title><![CDATA[<?php echo $this->channel_title; ?>]]></title>
					<link><?php echo FULLHOSTPATH . WEBPATH; ?></link>
					<atom:link href="<?php echo PROTOCOL; ?>://<?php echo $this->host; ?><?php echo html_encode(getRequestURI()); ?>" rel="self"	type="application/rss+xml" />
					<description><![CDATA[<?php echo html_encode(getBare($_gallery->getDesc($this->locale))); ?>]]></description>
					<language><?php echo $this->locale_xml; ?></language>
					<pubDate><?php echo formattedDate("r", time()); ?></pubDate>
					<lastBuildDate><?php echo formattedDate("r", time()); ?></lastBuildDate>
					<docs>http://blogs.law.harvard.edu/tech/rss</docs>
					<generator>netPhotoGraphics RSS Generator</generator>
					<?php
					foreach ($feeditems as $feeditem) {
						switch ($this->feedtype) {
							case 'gallery':
								$item = $this->getItemGallery($feeditem);
								break;
							case 'news':
								$item = $this->getItemNews($feeditem);
								break;
							case 'pages':
								$item = $this->getitemPages($feeditem, getOption('RSS_truncate_length'));
								break;
							case 'comments':
								$item = $this->getitemComments($feeditem);
								break;
							default:
								$item = $feeditem;
								break;
						}
						?>
						<item>
							<title><![CDATA[<?php echo $item['title']; ?>]]></title>
							<link><?php echo html_encode($item['link']); ?></link>
							<description><![CDATA[<?php echo $item['desc']; ?>]]></description>
							<?php
							if (!empty($item['enclosure'])) {
								echo $item['enclosure']; //prints xml as well
							}
							if (!empty($item['category'])) {
								?>
								<category><![CDATA[<?php echo $item['category']; ?>]]></category>
								<?php
							}
							if (!empty($item['media_content'])) {
								echo $item['media_content']; //prints xml as well
							}
							if (!empty($item['media_thumbnail'])) {
								echo $item['media_thumbnail']; //prints xml as well
							}
							?>
							<guid><?php echo html_encode($item['link']); ?></guid>
							<pubDate><?php echo html_encode($item['pubdate']); ?></pubDate>
						</item>
						<?php
					} // foreach
					?>
				</channel>
			</rss>
			<?php
			$this->endCache();
		} else {
			self::feed404();
		}
	}

}

function executeRSS() {
	global $_gallery_page;
	if (!$_GET['rss']) {
		$_GET['rss'] = 'gallery';
	}
	$_gallery_page = 'rss.php';
	$rss = new RSS(sanitize($_GET));
	$rss->printFeed();
	exit();
}

// RSS feed calls before anything else
if (!OFFSET_PATH && isset($_GET['rss'])) {
	npgFilters::register('load_theme_script', 'executeRSS', 9999);
}
?>