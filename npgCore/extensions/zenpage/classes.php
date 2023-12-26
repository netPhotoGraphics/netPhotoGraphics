<?php

/**
 * Zenpage root classes
 * @author Stephen Billard (sbillard), Malte Müller (acrylian)
 * @package plugins/zenpage
 */
/**
 * Some global variable setup
 *
 */
define('SHORTENINDICATOR', $shortenindicator = getOption('CMS_textshorten_indicator'));
define('SHORTEN_LENGTH', getOption('CMS_text_length'));
define('READ_MORE', getOption("CMS_read_more"));
define('ARTICLES_PER_PAGE', max(1, getOption("CMS_articles_per_page")));

class CMS {

	public $categoryStructure = array();
	protected $categoryCache = array();
	// category defaults
	protected $cat_sortorder = 'sort_order';
	protected $cat_sortdirection = false;
	// article defaults (mirrors category vars)
	protected $sortorder = 'date';
	protected $sortdirection = true;
	protected $sortSticky = true;
	// page defaults
	protected $page_sortorder = 'sort_order';
	protected $page_sortdirection = false;
	public $news_enabled = NULL;
	public $pages_enabled = NULL;

	/**
	 * Class instantiator
	 */
	function __construct() {
		$allcategories = query_full_array("SELECT * FROM " . prefix('news_categories') . " ORDER by sort_order");
		$this->categoryStructure = array();
		foreach ($allcategories as $cat) {
			$this->categoryStructure[$cat['id']] = $cat;
		}
		$this->news_enabled = getOption('CMS_enabled_items') & 1;
		$this->pages_enabled = getOption('CMS_enabled_items') & 2;
	}

	/**
	 * Provides the complete category structure regardless of permissions.
	 * This is needed for quick checking of status of a category and is used only internally to the Zenpage core.
	 * @return array
	 */
	private function getCategoryStructure() {
		return $this->categoryStructure;
	}

	/*	 * ********************************* */
	/* general page functions   */
	/*	 * ********************************* */

	function visibleCategory($cat) {
		if (npg_loggedin(MANAGE_ALL_NEWS_RIGHTS | VIEW_UNPUBLISHED_NEWS_RIGHTS))
			return true;
		$vis = $this->categoryStructure[$cat['cat_id']]['show'];
		if (!$vis && npg_loggedin()) {
			$catobj = newCategory($cat['titlelink']);
			if ($catobj->subRights()) {
				return true;
			}
		}
		return $vis;
	}

	/**
	 * Gets all pages or published ones.
	 *
	 * NOTE: Since this function only returns titlelinks for use with the object model it does not exclude pages that are password protected
	 *
	 * @param bool $published TRUE for published or FALSE for all pages including un-published
	 * @param bool $toplevel TRUE for only the toplevel pages
	 * @param int $number number of pages to get (NULL by default for all)
	 * @param string $sorttype NULL for the standard order as sorted on the backend, "title", "date", "id", "popular", "mostrated", "toprated", "random"
	 * @param bool $sortdirection false for ascenting, true for descending
	 * @param object $pageObj page object from which to get the pages
	 * @return array
	 */
	function getPages($published = NULL, $toplevel = false, $number = NULL, $sorttype = NULL, $sortdirection = NULL, $pageObj = NULL) {
		global $_loggedin;
		if (is_null($sortdirection)) {
			$sortdirection = $this->getSortDirection('pages');
		}
		if (is_null($sorttype)) {
			$sorttype = $this->getSortType('pages');
		}
		if (is_null($published)) {
			$published = !npg_loggedin();
			$all = npg_loggedin(MANAGE_ALL_PAGES_RIGHTS | VIEW_UNPUBLISHED_PAGE_RIGHTS);
		} else {
			$all = !$published;
		}
		$published = $published && !npg_loggedin(ZENPAGE_PAGES_RIGHTS);

		if ($published) {
			$show = '`show`=1';
		} else {
			$show = '';
		}

		if ($pageObj) {
			if ($toplevel) {
				$toplevel = ' `parentid` = ' . $pageObj->getID();
			} else {
				$toplevel = ' `sort_order` LIKE "' . $pageObj->getSortOrder() . '-%"';
			}
		} else if ($toplevel) {
			$toplevel = ' `parentid` IS NULL';
		}

		if ($toplevel) {
			if ($show) {
				$show .= ' AND ';
			}
			$show = ' WHERE ' . $show . $toplevel;
		} else if ($show) {
			$show = ' WHERE ' . $show;
		}

		if ($sortdirection) {
			$sortdir = ' DESC';
		} else {
			$sortdir = ' ASC';
		}

		$order = [];
		switch ($sorttype) {
			default:
				$order[$sorttype] = false;
				break;
			case 'popular':
				$order['hitcounter'] = false;
				break;
			case 'mostrated':
				$order['total_votes'] = false;
				break;
			case 'toprated':
				$order['rating'] = (bool) $sortdir;
				$order['total_value'] = false;
				break;
			case 'random':
				$order['RAND()'] = false;
				break;
		}



		$all_pages = array(); // Disabled cache var for now because it does not return un-publishded and published if logged on index.php somehow if logged in.


		$sql = 'SELECT total_value/total_votes as rating, id, parentid, title, titlelink, permalink, sort_order, `show`, locked, date, publishdate, expiredate, owner
lastchange, lastchangeuser, hitcounter, rating, rating_status, used_ips, total_value, total_votes
user, password, password_hint, commentson, truncation, content, codeblock, extracontent FROM ' . prefix('pages') . $show;

		if (!empty($order)) {
			$sql .= ' ORDER BY';
			foreach ($order as $field => $direction) {
				$sql .= ' ' . $field;
				if ($direction) {
					$sql .= ' DESC,';
				} else {
					$sql .= ',';
				}
			}
			$sql = rtrim($sql, ',');
		}
		$result = query($sql);
		if ($result) {
			while ($row = db_fetch_assoc($result)) {
				if ($all || $row['show']) {
					$all_pages[] = $row;
				} else if ($_loggedin) {
					$page = newPage($row['titlelink']);
					if ($page->subRights()) {
						$all_pages[] = $row;
					} else {
						$parentid = $page->getParentID();
						if ($parentid) {
							$parent = getItemByID('pages', $parentid);
							if ($parent && $parent->subRights() & MANAGED_OBJECT_RIGHTS_VIEW) {
								$all_pages[] = $row;
							}
						}
					}
				}
				if ($number && count($all_pages) >= $number) {
					break;
				}
			}
			db_free_result($result);
		}
		return $all_pages;
	}

	/*	 * ********************************* */
	/* general news article functions   */
	/*	 * ********************************* */

	/**
	 * Gets all news articles titlelink.
	 *
	 * NOTE: Since this function only returns titlelinks for use with the object model it does not exclude articles that are password protected via a category
	 *
	 *
	 * @param int $articles_per_page The number of articles to get
	 * @param string $published "published" for an published articles,
	 * 													"unpublished" for an unpublised articles,
	 * 													"published-unpublished" for published articles only from an unpublished category,
	 * 													"sticky" for sticky articles (published or not!) for admin page use only,
	 * 													"all" for all articles
	 * @param boolean $ignorepagination Since also used for the news loop this function automatically paginates the results if the "page" GET variable is set. To avoid this behaviour if using it directly to get articles set this TRUE (default FALSE)
	 * @param string $sortorder "date" (default), "title", "id, "popular", "mostrated", "toprated", "random"
	 * 													This parameter is not used for date archives
	 * @param bool $sortdirection TRUE for descending, FALSE for ascending. Note: This parameter is not used for date archives
	 * @param bool $sticky set to true to place "sticky" articles at the front of the list.
	 * @param obj $category Optional category to get the article from
	 * @param string $author Optional author name to get the articles of
	 * @param int $limit if passed, the max number of articles to return
	 * @return array
	 */
	function getArticles($articles_per_page = 0, $published = NULL, $ignorepagination = false, $sortorder = NULL, $sortdirection = NULL, $sticky = NULL, $category = NULL, $author = null, $limit = NULL) {
		global $_CMS_current_category, $_post_date, $_newsCache;

		if (empty($published)) {
			if (npg_loggedin(ZENPAGE_NEWS_RIGHTS | VIEW_UNPUBLISHED_NEWS_RIGHTS)) {
				$published = "all";
			} else {
				$published = "published";
			}
		}
		if ($category && $category->exists) {
			$sortObj = $category;
			$cat = $category->getTitlelink();
		} else if (is_object($_CMS_current_category)) {
			$sortObj = $_CMS_current_category;
			$cat = $sortObj->getTitlelink();
		} else {
			$sortObj = $this;
			$cat = '*';
		}
		if (is_null($sticky)) {
			$sticky = $sortObj->getSortSticky();
		}

		if (is_null($sortdirection)) {
			$sortdirection = $sortObj->getSortDirection('news');
		}
		if (is_null($sortorder)) {
			$sortorder = $sortObj->getSortType('news');
			if (empty($sortorder)) {
				$sortorder = 'date';
			}
		}

		$newsCacheIndex = "$sortorder-$sortdirection-$published-$cat-$author-" . (int) $sticky;
		if ($limit) {
			$newsCacheIndex .= '_' . $limit;
		}

		if (isset($_newsCache[$newsCacheIndex])) {
			$result = $_newsCache[$newsCacheIndex];
		} else {
			$cat = $show = $currentCat = false;
			if ($category) {
				if ($category->exists) {
					if (is_object($_CMS_current_category)) {
						$currentCat = $_CMS_current_category->getTitlelink();
					}
					// new code to get nested cats
					$catid = $category->getID();
					$subcats = $category->getSubCategories();
					if ($subcats) {
						$cat = " (cat.cat_id = '" . $catid . "'";
						foreach ($subcats as $subcat) {
							$subcatobj = newCategory($subcat);
							$cat .= " OR cat.cat_id = '" . $subcatobj->getID() . "' ";
						}
						$cat .= ") AND cat.news_id = news.id";
					} else {
						$cat = " cat.cat_id = '" . $catid . "' AND cat.news_id = news.id";
					}
				} else {
					$category = NULL;
					$cat = '(`id` NOT IN (';
					$rslt = query_full_array('SELECT DISTINCT `news_id` FROM ' . prefix('news2cat'));
					if (!empty($rslt)) {
						$cat = ' `id` NOT IN (';
						foreach ($rslt as $row) {
							$cat .= $row['news_id'] . ',';
						}
						$cat = substr($cat, 0, -1) . ')';
					}
				}
			}

			if ($author) {
				if ($cat) {
					$author_conjuction = ' AND ';
				} else {
					$author_conjuction = ' WHERE ';
				}
				$show .= $author_conjuction . ' author = ' . db_quote($author);
			}
			$order = [];
			if ($sticky) {
				$order ['sticky'] = true;
			}

			if (in_context(ZENPAGE_NEWS_DATE)) {
				switch ($published) {
					case "published":
					case "unpublished":
					case "all":
						$datesearch = "date LIKE '$_post_date%' ";
						break;
					default:
						$datesearch = '';
						break;
				}
				if ($datesearch) {
					if ($show) {
						$datesearch = ' AND ' . $datesearch . ' ';
					}
				}
				$order['date'] = true;
			} else {
				$datesearch = "";
				// sortorder and sortdirection (only used for all news articles and categories naturally)
				switch ($sortorder) {
					case "popular":
						$order['hitcounter'] = $sortdirection;
						break;
					case "mostrated":
						$order['total_votes'] = (bool) $sortdirection;
						break;
					case "toprated":
						$order['rating'] = true;
						$order['total_value'] = false;
						break;
					case "random":
						$order['RAND()'] = false;
						break;
					default:
						$order[$sortorder] = (bool) $sortdirection;
						break;
				}
			}
			if ($category) {
				$join = ', ' . prefix('news2cat') . ' as cat WHERE' . $cat;
			} else {
				$join = '';
			}
			$sql = "SELECT DISTINCT news.date as date, news.publishdate as publishdate, news.expiredate as expiredate, news.lastchange as lastchange, news.title as title, news.titlelink as titlelink, news.sticky as sticky, news.total_value/news.total_votes as rating FROM " . prefix('news') . " as news" . $join;
			if ($show || $datesearch) {
				if ($cat) {
					$sql .= ' AND ';
				} else {
					$sql .= ' WHERE ';
				}
				$sql .= $show . $datesearch;
			}

			if (!empty($order)) {
				$sql .= ' ORDER BY';
				foreach ($order as $field => $direction) {
					$sql .= ' ' . $field;
					if ($direction) {
						$sql .= ' DESC,';
					} else {
						$sql .= ',';
					}
				}
				$sql = rtrim($sql, ',');
			}
			$resource = query($sql);
			$result = array();
			if ($resource) {
				while ($item = db_fetch_assoc($resource)) {
					$article = newArticle($item['titlelink']);
					if ($incurrent = $currentCat) {
						$incurrent = $article->inNewsCategory($currentCat);
					}
					$subrights = $article->subRights();
					if (npg_loggedin(VIEW_UNPUBLISHED_NEWS_RIGHTS) //	override published
									|| ($article->getShow() && (($incurrent || $article->categoryIsVisible()) || $subrights)) //	published in "visible" or managed category
									|| ($subrights & MANAGED_OBJECT_RIGHTS_VIEW) //	he is allowed to see unpublished articles in one of the article's categories
									|| $article->isMyItem(ZENPAGE_NEWS_RIGHTS)
					) {
						$result[] = $item;
						if ($limit && count($result) >= $limit) {
							break;
						}
					}
				}
				db_free_result($resource);
				if ($sortorder == 'title') { // multi-lingual field!
					$result = sortByMultilingual($result, 'title', $sortdirection);
					if ($sticky) {
						$stickyItems = array();
						foreach ($result as $key => $element) {
							if ($element['sticky']) {
								array_unshift($stickyItems, $element);
								unset($result[$key]);
							}
						}
						$stickyItems = sortMultiArray($stickyItems, ['sticky' => true]);
						$result = array_merge($stickyItems, $result);
					}
				}
			}
			$_newsCache[$newsCacheIndex] = $result;
		}

		if ($articles_per_page) {
			if ($ignorepagination) {
				$offset = 0;
			} else {
				$offset = self::getOffset($articles_per_page);
			}
			$result = array_slice($result, $offset, $articles_per_page);
		}
		return $result;
	}

	/**
	 * Returns an article from the album based on the index passed.
	 *
	 * @param int $index
	 * @return int
	 */
	function getArticle($index, $published = NULL, $sortorder = NULL, $sortdirection = NULL, $sticky = true) {
		global $_CMS_current_category;
		if (in_context(ZENPAGE_NEWS_CATEGORY)) {
			$category = $_CMS_current_category;
		} else {
			$category = NULL;
		}
		$articles = $this->getArticles(0, NULL, true, $sortorder, $sortdirection, $sticky, $category);
		if ($index >= 0 && $index < count($articles)) {
			$article = $articles[$index];
			$obj = newArticle($articles[$index]['titlelink']);
			return $obj;
		}
		return false;
	}

	/**
	 * Gets the LIMIT and OFFSET for the query that gets the news articles
	 *
	 * @param int $articles_per_page The number of articles to get
	 * @param bool $ignorepagination If pagination should be ingored so always with the first is started (false is default)
	 * @return string
	 */
	static function getOffset($articles_per_page, $ignorepagination = false) {
		global $_current_page, $subpage;
		if (OFFSET_PATH) {
			$page = $subpage + 1;
		} else {
			$page = $_current_page;
		}
		if ($ignorepagination || is_null($page)) { //	maybe from a feed since this means that $_current_page is not set
			$offset = 0;
		} else {
			$offset = ($page - 1) * $articles_per_page;
		}
		return $offset;
	}

	/**
	 * Returns the articles count
	 *
	 */
	function getTotalArticles() {
		return count($this->getArticles(0));
	}

	function getTotalNewsPages() {
		return ceil($this->getTotalArticles() / ARTICLES_PER_PAGE);
	}

	/**
	 * Retrieves a list of all unique years & months
	 * @param bool $yearsonly If set to true only the years' count is returned (Default false)
	 * @param string $order 'desc' (default) or 'asc' for descending or ascending
	 * @return array
	 */
	function getAllArticleDates($yearsonly = false, $order = 'desc') {
		$alldates = array();
		$cleandates = array();
		$sql = "SELECT date FROM " . prefix('news');
		if (!npg_loggedin(MANAGE_ALL_NEWS_RIGHTS)) {
			$sql .= " WHERE `show`=1";
		}
		$result = query_full_array($sql);
		foreach ($result as $row) {
			$alldates[] = $row['date'];
		}
		foreach ($alldates as $adate) {
			if (!empty($adate)) {
				if ($yearsonly) {
					$cleandates[] = substr($adate, 0, 4);
				} else {
					$cleandates[] = substr($adate, 0, 7) . "-01";
				}
			}
		}
		$datecount = array_count_values($cleandates);
		switch ($order) {
			case 'desc':
			default:
				krsort($datecount);
				break;
			case 'asc':
				ksort($datecount);
				break;
		}
		return $datecount;
	}

	/**
	 *
	 * filters query results for only news that should be shown. (that is fit to print?)
	 * @param $sql query to return all candidates of interest
	 * @param $offset skip this many legitimate items (used for pagination)
	 * @param $limit return only this many items
	 */
	protected function siftResults($sql, $offset, $limit) {
		$resource = $result = query($sql);
		if ($resource) {
			$result = array();
			while ($item = db_fetch_assoc($resource)) {
				if ($item['type'] == 'news') {
					$article = newArticle($item['titlelink']);
					if (!$article->categoryIsVisible()) {
						continue;
					}
				}
				$offset--;
				if ($offset < 0) {
					$result[] = $item;
					if ($limit && count($result) >= $limit) {
						break;
					}
				}
			}
			db_free_result($resource);
		}
		return $result;
	}

	/*	 * ********************************* */
	/* general news category functions  */
	/*	 * ********************************* */

	/**
	 * Gets a category titlelink by id
	 *
	 * @param int $id id of the category
	 * @return array
	 */
	function getCategory($id) {
		foreach ($this->getAllCategories(false) as $cat) {
			if ($cat['id'] == $id) {
				return $cat;
			}
		}
		return '';
	}

	/**
	 * Gets all categories
	 * @param bool $visible TRUE for published and unprotected
	 * @param string $sorttype NULL for the standard order as sorted on the backend, "title", "id", "popular", "random"
	 * @param bool $sortdirection TRUE for descending or FALSE for ascending order
	 * @return array
	 */
	function getAllCategories($visible = true, $sorttype = NULL, $sortdirection = NULL) {

		$structure = $this->getCategoryStructure();
		if (is_null($sortdirection)) {
			$sortdirection = $this->cat_sortdirection;
		}
		if (is_null($sorttype)) {
			$sorttype = $this->cat_sortorder;
		}

		switch ($sorttype) {
			case "id":
				$sortorder = "id";
				break;
			case "title":
				$sortorder = "title";
				break;
			case "popular":
				$sortorder = 'hitcounter';
				break;
			case "random":
				$sortorder = 'random';
				break;
			default:
				$sortorder = "sort_order";
				$sortdirection = false;
				break;
		}
		$all = npg_loggedin(MANAGE_ALL_NEWS_RIGHTS);
		if (array_key_exists($key = $sortorder . (int) $sortdirection . (bool) $visible . (bool) $all, $this->categoryCache)) {
			return $this->categoryCache[$key];
		} else {
			if ($visible) {
				foreach ($structure as $key => $cat) {
					$catobj = newCategory($cat['titlelink']);
					if ($all || $catobj->getShow() || $catobj->subRights()) {
						$structure[$key]['show'] = 1;
					} else {
						unset($structure[$key]);
					}
				}
			}

			if (!is_null($sorttype) || !is_null($sortdirection)) {
				if ($sorttype == 'random') {
					shuffle($structure);
				} else {
					$structure = sortMultiArray($structure, [$sortorder => $sortdirection], true, false, false);
				}
			}
			$this->categoryCache[$key] = $structure;
			return $structure;
		}
	}

	/**
	 *
	 * "Magic" function to return a string identifying the object when it is treated as a string
	 * @return string
	 */
	public function __toString() {
		return 'CMS Object';
	}

	function getSortDirection($what = 'news') {
		switch ($what) {
			case 'pages':
				$type = $this->page_sortdirection;
				break;
			case'categories':
				$type = $this->cat_sortdirection;
				break;
			default:
				$type = $this->sortdirection;
				break;
		}
		return $type;
	}

	function setSortDirection($value, $what = 'news') {
		switch ($what) {
			case 'pages':
				$this->page_sortdirection = $value;
				break;
			case'categories':
				$this->cat_sortdirection = $value;
				break;
			default:
				$this->sortdirection = $value;
				break;
		}
	}

	function getSortType($what = 'news') {
		switch ($what) {
			case 'pages':
				$type = $this->page_sortorder;
				break;
			case'categories':
				$type = $this->cat_sortorder;
				break;
			default:
				$type = $this->sortorder;
				break;
		}
		return $type;
	}

	function setSortType($value, $what = 'news') {
		switch ($what) {
			case 'pages':
				$this->page_sortorder = $value;
				break;
			case'categories':
				$this->cat_sortorder = $value;
				break;
			default:
				$this->sortorder = $value;
				break;
		}
	}

	function getSortSticky() {
		return $this->sortSticky;
	}

	function setSortSticky($value) {
		$this->sortSticky = (bool) $value;
	}

}

// ZenpageCMS

/**
 *
 * Base class from which all Zenpage classes derive
 *
 */
class CMSRoot extends ThemeObject {

	protected $sortorder;
	protected $sortdirection;
	protected $sortSticky = true;

	/**
	 *
	 * "Magic" function to return a string identifying the object when it is treated as a string
	 * @return string
	 */
	public function __toString() {
		if ($this->table) {
			return $this->table . " (" . $this->getTitlelink() . ")";
		} else {
			return get_class($this) . ' ' . gettext('Object');
		}
	}

	/**
	 * Returns the perma link status (only used on admin)
	 *
	 * @return string
	 */
	function getPermalink() {
		return $this->get("permalink");
	}

	/*	 * '
	 * sets the permalink
	 */

	function setPermalink($v) {
		$this->set('permalink', $v);
	}

	/**
	 * Returns the titlelink
	 *
	 * @return string
	 */
	function getTitlelink() {
		return $this->get("titlelink");
	}

	/**
	 * sets the title link
	 * @param $v
	 */
	function setTitlelink($v) {
		$this->set("titlelink", $v);
	}

}

// Zenpage main class end

/**
 *
 * Base class from which Zenpage news articles and pages derive
 *
 */
class CMSItems extends CMSRoot {

	protected $subrights = NULL; //	cache for subrights

	/**
	 * Class instantiator
	 */
	function __construct() {
		// no action required
	}

	/**
	 * Returns the author
	 *
	 * @return string
	 */
	function getOwner() {
		return $this->get("owner");
	}

	/**
	 * Returns the content
	 *
	 * @return string
	 */
	function getContent($locale = NULL) {
		$text = $this->get("content");
		if ($locale == 'all') {
			return npgFunctions::unTagURLs($text);
		} else {
			return applyMacros(npgFunctions::unTagURLs(get_language_string($text, $locale)));
		}
	}

	/**
	 *
	 * Set the content datum
	 * @param $c full language string
	 */
	function setContent($c) {
		$c = npgFunctions::tagURLs($c);
		$this->set("content", $c);
	}

	/**
	 * Returns the locked status , "1" if locked (only used on the admin)
	 *
	 * @return string
	 */
	function getLocked() {
		return $this->get("locked");
	}

	/**
	 * sets the locked status , "1" if locked (only used on the admin)
	 *
	 */
	function setLocked($l) {
		$this->set("locked", $l);
	}

	/**
	 * Returns the extra content
	 *
	 * @return string
	 */
	function getExtraContent($locale = NULL) {
		$text = $this->get("extracontent");
		if ($locale == 'all') {
			return npgFunctions::unTagURLs($text);
		} else {
			return applyMacros(npgFunctions::unTagURLs(get_language_string($text, $locale)));
		}
	}

	/**
	 * sets the extra content
	 *
	 */
	function setExtraContent($ec) {
		$this->set("extracontent", npgFunctions::tagURLs($ec));
	}

}
