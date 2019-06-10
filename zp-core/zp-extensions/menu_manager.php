<?php

/**
 *
 * Lets you create custom menus and place them on your theme pages.
 *
 * Use the <var>Menu</var> tab to create your menus. Use <var>printCustomMenu()</var> to place them on your pages.
 *
 * This plugin is recommend for customized themes only that do not use the standard
 * display structure. Standard functions like the breadcrumb functions or the <var>next_album()</var>
 * loop for example will <b>NOT</b> take care of this menu's structure!
 *
 * @author Stephen Billard (sbillard), Malte Müller (acrylian)
 *
 * @package plugins/menu_manager
 * @pluginCategory theme
 */
$plugin_is_filter = 5 | ADMIN_PLUGIN | THEME_PLUGIN;
$plugin_description = gettext("A menu creation facility. The <em>Menu</em> tab admin interface lets you create arbitrary menu trees.");

$option_interface = 'menu_manager';

if (OFFSET_PATH) {
	require_once(dirname(dirname(__FILE__)) . '/template-functions.php');
	npgFilters::register('admin_tabs', 'menu_tabs');
} else {
	npgFilters::register('admin_toolbox_global', 'menu_admin_toolbox_global');
}

/**
 *
 * option handler
 * @author Stephen
 *
 */
class menu_manager {

	// these are the renamed types
	static $deprecated = array(
			'zenpagepage' => 'page',
			'zenpagecategory' => 'category',
			'zenpagenewsindex' => 'newsindex',
			'all_zenpagecategorys' => 'all_categorys',
			'all_zenpagepages' => 'all_pages',
			'galleryindex' => 'albumindex',
			'homepage' => 'siteindex'
	);

	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('menu_truncate_string', 0);
			setOptionDefault('menu_truncate_indicator', '');

			foreach (self::$deprecated as $old => $new) {
				$sql = 'UPDATE ' . prefix('menu') . ' SET `type`=' . db_quote($new) . ' WHERE `type`=' . db_quote($old);
				query($sql);
			}
		}
	}

	function getOptionsSupported() {

		$options = array(
				gettext('Truncate indicator*') => array('key' => 'menu_truncate_indicator', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 2,
						'desc' => gettext('Append this string to truncated titles.')),
				gettext('Truncate titles*') => array('key' => 'menu_truncate_string', 'type' => OPTION_TYPE_NUMBER,
						'order' => 1,
						'desc' => gettext('Limit titles to this many characters. Zero means no limit.'))
		);

		$options['note'] = array('key' => 'menu_truncate_note',
				'type' => OPTION_TYPE_NOTE,
				'order' => 8,
				'desc' => gettext('<p class="notebox">*<strong>Note:</strong> These options are shared among <em>menu_manager</em>, <em>print_album_menu</em>, and <em>zenpage</em>.</p>'));

		return $options;
	}

	function handleOption($option, $currentValue) {

	}

}

/**
 *
 * Add menu to the admin toolbox
 */
function menu_admin_toolbox_global() {
	if (npg_loggedin(ADMIN_RIGHTS)) {
		echo "<li>";
		printLinkHTML(getAdminLink(PLUGIN_FOLDER . '/menu_manager/menu_tab.php'), gettext("Menu"), NULL, NULL, NULL);
		echo "</li>\n";
	}
}

/**
 * Adds menu manager to admin tabs
 *
 * @param $tabs array Admin tabs
 * @param string $current current tab
 * @return unknown
 */
function menu_tabs($tabs) {
	if (npg_loggedin(ADMIN_RIGHTS)) {
		$tabs['menu'] = array('text' => gettext("menu"),
				'link' => getAdminLink(PLUGIN_FOLDER . '/menu_manager/menu_tab.php') . '?page=menu&amp;tab=menu',
				'default' => 'menu',
				'subtabs' => NULL
		);
	}
	return $tabs;
}

/*
 *
 * Common functions
 *
 */

$_menu_manager_items = array();

/**
 * Gets the menu items
 *
 * @param string $menuset the menu tree desired
 * @param string $visible
 * @return array
 */
function getMenuItems($menuset, $visible) {
	global $_menu_manager_items;
	if (array_key_exists($menuset, $_menu_manager_items) &&
					array_key_exists($visible, $_menu_manager_items[$menuset])) {
		return $_menu_manager_items[$menuset][$visible];
	}
	$_menu_manager_items[$menuset][$visible] = array();
	switch ($visible) {
		case 'visible':
			$where = " WHERE `show`=1 AND menuset = " . db_quote($menuset);
			break;
		case 'hidden':
			$where = " WHERE `show`=0 AND menuset = " . db_quote($menuset);
			break;
		default:
			$where = " WHERE menuset = " . db_quote($menuset);
			$visible = 'all';
			break;
	}
	$result = query("SELECT * FROM " . prefix('menu') . $where . " ORDER BY sort_order", false, 'sort_order');
	if ($result) {
		while ($row = db_fetch_assoc($result)) {
			$row['type'] = strtolower($row['type']);
			if (strpos($row['type'], 'zenpage') !== false)
				$row['type'] = str_replace('zenpage', '', $row['type']);
			$_menu_manager_items[$menuset][$visible][] = $row;
		}
		db_free_result($result);
	}

	return $_menu_manager_items[$menuset][$visible];
}

/**
 * Gets a menu item by its id
 *
 * @param integer $id id of the item
 * @return array
 */
function getItem($id) {
	$menuset = checkChosenMenuset();
	$result = query_single_row("SELECT * FROM " . prefix('menu') . " WHERE menuset = " . db_quote($menuset) . " AND id = " . $id);
	return $result;
}

/**
 * Checks which menu set is chosen via $_GET. If none is explicity chosen the "default" one (create initially) is used.
 *
 * @return string
 */
function checkChosenMenuset() {
	if (isset($_REQUEST['menuset'])) {
		$menuset = sanitize($_REQUEST['menuset']);
		setOption('menu_lastChanged', $menuset);
	} else {
		$menuset = getOption('menu_lastChanged');
	}
	return $menuset;
}

/**
 * Checks if the menu item is set visible or not
 *
 * @return string
 */
function checkChosenItemStatus() {
	if (isset($_GET['visible'])) {
		return sanitize($_GET['visible']);
	} else {
		return 'all';
	}
}

/**
 * Gets the title, url and name of a menu item
 *
 * @return array
 */
function getItemTitleAndURL($item) {
	global $_gallery;
	$themename = $_gallery->getCurrentTheme();
	$array = array(
			"title" => '',
			"url" => '',
			"name" => '',
			'protected' => false,
			'error' => 0,
			'theme' => $themename);
	$error = 0;
	$title = get_language_string($item['title']);
	if (empty($title)) {
		$title = $item['link'];
	}
	switch ($item['type']) {
		case "galleryindex":
		case 'siteindex':
			$array = array(
					"title" => get_language_string($item['title']),
					"url" => getGalleryIndexURL(),
					"name" => getGalleryIndexURL(),
					'protected' => false,
					'theme' => $themename
			);
			break;
		case "album":
			$folderFS = internalToFilesystem($item['link']);
			$localpath = ALBUM_FOLDER_SERVERPATH . $folderFS;
			$dynamic = hasDynamicAlbumSuffix($folderFS) && !is_dir($folderFS);
			if (!file_exists($localpath) && ($dynamic || is_dir($localpath)) || strpos($localpath, '..') !== false) {
				$error = 2;
				$url = '';
				$protected = 0;
			} else {
				$obj = newAlbum($item['link']);
				$url = $obj->getLink(0);
				$protected = $obj->isProtected();
				$title = $obj->getTitle();
			}
			$array = array(
					"title" => $title,
					"url" => $url,
					"name" => $item['link'],
					'protected' => $protected,
					'theme' => $themename
			);
			break;
		case "page":
			$sql = 'SELECT * FROM ' . prefix('pages') . ' WHERE `titlelink`="' . $item['link'] . '"';
			$result = query_single_row($sql);
			if (is_array($result) && extensionEnabled('zenpage')) {
				$obj = newPage($item['link']);
				$url = $obj->getLink(0);
				$protected = $obj->isProtected();
				$title = $obj->getTitle();
			} else {
				$error = 3 - extensionEnabled('zenpage');
				$url = '';
				$protected = 0;
			}
			$array = array(
					"title" => $title,
					"url" => $url,
					"name" => $item['link'],
					'protected' => $protected,
					'theme' => $themename
			);
			break;
		case "newsindex":
			if (extensionEnabled('zenpage')) {
				$url = getNewsIndexURL();
			} else {
				$error = 3;
				$url = '';
			}
			$array = array(
					"title" => get_language_string($item['title']),
					"url" => $url,
					"name" => $url,
					'protected' => false
			);
			break;
		case "category":
			$sql = "SELECT title FROM " . prefix('news_categories') . " WHERE titlelink='" . $item['link'] . "'";
			$obj = query_single_row($sql, false);
			if ($obj && extensionEnabled('zenpage')) {
				$obj = newCategory($item['link']);
				$title = $obj->getTitle();
				$protected = $obj->isProtected();
				$url = $obj->getLink(0);
			} else {
				$error = 3 - (int) extensionEnabled('zenpage');
				$url = '';
				$protected = 0;
			}
			$array = array(
					"title" => $title,
					"url" => $url,
					"name" => $item['link'],
					'protected' => $protected,
					'theme' => $themename
			);
			break;
		case "albumindex":
			$item['link'] = 'gallery';
		case "custompage":
			$root = SERVERPATH . '/' . THEMEFOLDER . '/' . $themename . '/';
			if (file_exists($root . $item['link'] . '.php')) {
				$url = getCustomPageURL($item['link']);
			} else {
				$error = 1;
				$url = '';
			}
			$array = array(
					"title" => $title,
					"url" => $url,
					"name" => $item['link'],
					'protected' => false,
					'theme' => $themename
			);
			break;
		case "dynamiclink":
		case "customlink":
			$array = array(
					"title" => get_language_string($item['title']),
					"url" => $item['link'],
					"name" => $item['link'],
					'protected' => false,
					'theme' => $themename
			);
			break;
		case 'menulabel':
			$array = array(
					"title" => get_language_string($item['title']),
					"url" => NULL,
					'name' => $item['title'],
					'protected' => false,
					'theme' => $themename
			);
			break;
		default:
			$array = array(
					"title" => get_language_string($item['title']),
					"url" => $item['link'],
					"name" => $item['link'],
					'protected' => false,
					'theme' => $themename
			);
			break;
	}

	if (MENU_TRUNCATE_STRING) {
		$array['title'] = shortenContent($array['title'], MENU_TRUNCATE_STRING, MENU_TRUNCATE_INDICATOR);
	}
	$array['invalid'] = $error;

	return $array;
}

/* * *****************
 * Theme functions
 * ***************** */

/**
 * Gets the menu visibility
 * @return string
 */
function getMenuVisibility() {
	if (npg_loggedin(VIEW_ALL_RIGHTS)) {
		return "all";
	} else {
		return "visible";
	}
}

/**
 * "invents" a menu item for the current page (for when one does not exist)
 * Adds the item to the current menuset and modifies its "parent" as needed
 *
 * returns a contrived sort_order for the item.
 *
 * @param string $menuset
 * @param string $visibility
 * return string
 */
function inventMenuItem($menuset, $visibility) {
	global $_gallery_page, $_current_album, $_current_image, $_current_search, $_menu_manager_items,
	$_CMS_current_article, $_CMS_current_page;
	$currentkey = $insertpoint = NULL;
	$newitems = array();
	switch ($_gallery_page) {
		case 'image.php':
			$name = '';
			if (in_context(SEARCH_LINKED) && !in_context(ALBUM_LINKED)) {
				$dynamic = $_current_search->getDynamicAlbum();
				if (empty($dynamic)) { //	smple search
					foreach ($_menu_manager_items[$menuset][$visibility] as $key => $item) {
						if ($item['type'] == 'custompage' && $item['link'] == 'search') {
							$insertpoint = $item['sort_order'];
							$currentkey = $insertpoint . '-9999';
							break;
						}
					}
				}
			} else {
				$name = $_current_album->name;
			}
			if (!empty($name)) {
				foreach ($_menu_manager_items[$menuset][$visibility] as $key => $item) {
					if ($item['type'] == 'album' && $item['title'] == $name) {
						$insertpoint = $item['sort_order'];
						$currentkey = $insertpoint . '-9999';
						break;
					}
				}
			}
			if (!empty($currentkey)) {
				$item = array(
						'id' => 9999,
						'sort_order' => $currentkey,
						'parentid' => $item['id'],
						'type' => 'image',
						'include_li' => true,
						'title' => $_current_image->getTitle(),
						'show' => 1,
						'link' => '',
						'menuset' => $menuset
				);
			}
			break;
		case 'news.php':
			if (in_context(SEARCH_LINKED)) {
				foreach ($_menu_manager_items[$menuset][$visibility] as $key => $item) {
					if ($item['type'] == 'custompage' && $item['link'] == 'search') {
						$insertpoint = $item['sort_order'];
						$currentkey = $insertpoint . '-9999';
						break;
					}
				}
			} else {
				foreach ($_menu_manager_items[$menuset][$visibility] as $key => $item) {
					if ($item['type'] == 'newsindex') {
						$insertpoint = $item['sort_order'];
						$currentkey = $insertpoint . '-9999';
						break;
					}
				}
			}
			if (!empty($currentkey)) {
				if (is_NewsArticle()) {
					$item = array(
							'id' => 9999,
							'sort_order' => $currentkey,
							'parentid' => $item['id'],
							'type' => 'article',
							'include_li' => true,
							'title' => $_CMS_current_article->getTitle(),
							'show' => 1,
							'link' => '',
							'menuset' => $menuset
					);
				} else {
					$currentkey = false; // not a news page, must be the index?
				}
			}
			break;
		case 'pages.php':
			if (in_context(SEARCH_LINKED)) {
				foreach ($_menu_manager_items[$menuset][$visibility] as $key => $item) {
					if ($item['type'] == 'custompage' && $item['link'] == 'search') {
						$insertpoint = $item['sort_order'];
						$currentkey = $insertpoint . '-9999';
						$item = array(
								'id' => 9999,
								'sort_order' => $currentkey,
								'parentid' => $item['id'],
								'type' => 'page',
								'include_li' => true,
								'title' => $_CMS_current_page->getTitle(),
								'show' => 1,
								'link' => '',
								'menuset' => $menuset
						);
						break;
					}
				}
			}
			break;
	}
	if (!empty($currentkey)) {
		foreach ($_menu_manager_items[$menuset][$visibility] as $key => $olditem) {
			$newitems[$key] = $olditem;
			if ($olditem['sort_order'] == $insertpoint) {
				$newitems[$currentkey] = $item;
			}
		}
		$_menu_manager_items[$menuset][$visibility] = $newitems;
	}
	return $currentkey;
}

/**
 * Returns the sort_order of the current menu item
 * @param string $menuset current menu set
 * @return int
 */
function getCurrentMenuItem($menuset) {
	$currentpageURL = rtrim(str_replace('\\', '/', html_encode(getRequestURI())), '/');

	if (isset($_GET['page'])) { // must strip out page numbers, all "pages" are equal
		if (MOD_REWRITE) {
			if (isset($_GET['album'])) {
				$target = '/' . _PAGE_ . '/' . sanitize($_GET['page']);
			} else {
				$target = '/' . sanitize($_GET['page']);
			}
			$i = strrpos($currentpageURL, $target);
			if ($i == (strlen($currentpageURL) - strlen($target))) {
				$currentpageURL = substr($currentpageURL, 0, $i);
			}
		} else {
			$target = '&amp;page=' . sanitize($_GET['page']);
			$i = strpos($currentpageURL, $target);
			if ($i !== false) {
				$currentpageURL = substr($currentpageURL, 0, $i) . substr($currentpageURL, $i + strlen($target));
			}
		}
	}
	$visibility = 'all';
	$items = getMenuItems($menuset, $visibility);
	$currentkey = NULL;
	foreach ($items as $key => $item) {
		switch ($item['type']) {
			case 'menulabel':
			case 'menufunction':
			case 'html':
				break;
			default:
				$checkitem = getItemTitleAndURL($item);
				if ($currentpageURL == html_encode(rtrim($checkitem['url'], '/'))) {
					$currentkey = $key;
					break 2;
				}
				break;
		}
	}
	if (is_null($currentkey)) {
		$currentkey = inventMenuItem($menuset, $visibility);
	}
	return $currentkey;
}

/**
 * Returns the link to the predicessor of the current menu item
 * @param string $menuset current menu set
 * @return string
 */
function getMenumanagerPredicessor($menuset = 'default') {
	$sortorder = getCurrentMenuItem($menuset);
	$items = getMenuItems($menuset, getMenuVisibility());
	if (count($items) == 0)
		return NULL;
	if (empty($sortorder))
		return NULL;
	$order = explode('-', $sortorder);
	$next = array_pop($order) - 1;
	$saveorder = $order;
	while ($next >= 0) {
		$order = $saveorder;
		array_push($order, sprintf('%03u', $next));
		$sortorder = implode('-', $order);
		if (array_key_exists($sortorder, $items) && $items[$sortorder]['type'] != 'menulabel') { // omit the menulabels
			return getItemTitleAndURL($items[$sortorder]);
		}
		$next--;
	}
	return NULL;
}

/**
 * Prints the previous link of the current menu item
 * @param string  $text
 * @param string  $menuset
 * @param string  $title
 * @param string  $class
 * @param string  $id
 */
function printMenumanagerPrevLink($text, $menuset = 'default', $title = NULL, $class = NULL, $id = NULL) {
	$itemarray = getMenumanagerPredicessor($menuset);
	if (is_array($itemarray)) {
		if (is_null($title))
			$title = $itemarray['title'];
		printLinkHTML($itemarray['url'], $text, $title, $class, $id);
	} else {
		echo '<span class="disabledlink">' . html_encode($text) . '</span>';
	}
}

/**
 * Returns the successor link of the current menu item
 * @param string $menuset
 * @return string
 */
function getMenumanagerSuccessor($menuset = 'default') {
	$sortorder = getCurrentMenuItem($menuset);
	$items = getMenuItems($menuset, getMenuVisibility());
	if (count($items) == 0)
		return NULL;
	if (empty($sortorder))
		return NULL;
	$order = explode('-', $sortorder);
	$next = array_pop($order) + 1;
	$short_order = $order;
	array_push($order, sprintf('%03u', $next));
	$sortorder = implode('-', $order);
	while ($next <= 999) {
		$order = $short_order;
		array_push($order, sprintf('%03u', $next));
		$sortorder = implode('-', $order);
		if (array_key_exists($sortorder, $items)) {
			if ($items[$sortorder]['type'] != 'menulabel') { // omit the menulabels
				return getItemTitleAndURL($items[$sortorder]);
			}
		}
		$next++;
	}
	return NULL;
}

/**
 * Gets the link to the next menu item
 * @param string $text
 * @param string $menuset current menu set
 * @param string $title
 * @param string $class
 * @param string $id
 */
function printMenumanagerNextLink($text, $menuset = 'default', $title = NULL, $class = NULL, $id = NULL) {
	$itemarray = getMenumanagerSuccessor($menuset);
	if (is_array($itemarray)) {
		if (is_null($title))
			$title = $itemarray['title'];
		printLinkHTML($itemarray['url'], $text, $title, $class, $id);
	} else {
		echo '<span class="disabledlink">' . html_encode($text) . '</span>';
	}
}

/**
 * Prints a list of all pages.
 *
 * @param string $prevtext Insert here the linktext like 'previous page'
 * @param string $menuset current menu set
 * @param string $menuset current menu set
 * @param string $class the css class to use, "pagelist" by default
 * @param string $nextprev set to true to get the 'next' and 'prev' links printed
 * @param string $id the css id to use
 * @param bool $firstlast Add links to the first and last pages of you gallery
 * @param int $navlen Number of navigation links to show (0 for all pages). Works best if the number is odd.
 */
function printMenuemanagerPageListWithNav($prevtext, $nexttext, $menuset = 'default', $class = 'pagelist', $nextprev = true, $id = NULL, $firstlast = true, $navlen = 9) {
	$currentitem = getMenuFromLink(html_encode(urldecode(getRequestURI())), $menuset);
	if (is_null($currentitem))
		return; // we are not in menuset
	$orders = explode('-', $currentitem['sort_order']);
	array_pop($orders);
	$lookfor = implode('-', $orders) . '-';
	$sql = 'SELECT `sort_order` FROM ' . prefix('menu') . ' WHERE `sort_order` LIKE "' . $lookfor . '%" ORDER BY `sort_order` ASC';
	$result = query_full_array($sql, false, 'sort_order');
	if (is_array($result)) {
		$l = strlen($lookfor) + 3;
		foreach ($result as $key => $item) { // discard next level items
			if (strlen($key) > $l)
				unset($result[$key]);
		}
		$itemlist = array_keys($result);
		$total = count($itemlist);
		$current = array_search($currentitem['sort_order'], $itemlist) + 1;
		if ($total < 2) {
			$class .= ' disabled_nav';
		}
		if ($navlen == 0)
			$navlen = $total;
		$extralinks = 2;
		if ($firstlast)
			$extralinks = $extralinks + 2;
		$len = floor(($navlen - $extralinks) / 2);
		$j = max(round($extralinks / 2), min($current - $len - (2 - round($extralinks / 2)), $total - $navlen + $extralinks - 1));
		$ilim = min($total, max($navlen - round($extralinks / 2), $current + floor($len)));
		$k1 = round(($j - 2) / 2) + 1;
		$k2 = $total - round(($total - $ilim) / 2);
		$items = getMenuItems($menuset, getMenuVisibility());
		echo "<div" . (($id) ? " id=\"$id\"" : "") . " class=\"$class\">\n";
		echo "<ul class=\"$class\">\n";
		if ($nextprev) {
			echo "<li class=\"prev\">";
			printMenumanagerPrevLink($prevtext, $menuset, $prevtext, gettext("Previous Page"));
			echo "</li>\n";
		}
		if ($firstlast) {
			echo '<li class="' . ($current == 1 ? 'current' : 'first') . '">';
			$itemarray = getItemTitleAndURL($items[$itemlist[0]]);
			printLinkHTML($itemarray['url'], 1, gettext("Page 1"));
			echo "</li>\n";
			if ($j > 2) {
				echo "<li>";
				$itemarray = getItemTitleAndURL($items[$itemlist[$k1 - 1]]);
				printLinkHTML($itemarray['url'], ($j - 1 > 2) ? '...' : $k1, sprintf(ngettext('Page %u', 'Page %u', $k1), $k1));
				echo "</li>\n";
			}
		}
		for ($i = $j; $i <= $ilim; $i++) {
			echo "<li" . (($i == $current) ? " class=\"current\"" : "") . ">";
			$itemarray = getItemTitleAndURL($items[$itemlist[$i - 1]]);
			if ($i == $current) {
				$title = sprintf(ngettext('Page %1$u (Current Page)', 'Page %1$u (Current Page)', $i), $i);
			} else {
				$title = sprintf(ngettext('Page %1$u', 'Page %1$u', $i), $i);
			}
			printLinkHTML($itemarray['url'], $i, $title);
			echo "</li>\n";
		}
		if ($i < $total) {
			echo "<li>";
			$itemarray = getItemTitleAndURL($items[$itemlist[$k2 - 1]]);
			printLinkHTML($itemarray['url'], ($total - $i > 1) ? '...' : $k2, sprintf(ngettext('Page %u', 'Page %u', $k2), $k2));
			echo "</li>\n";
		}
		if ($firstlast && $i <= $total) {
			echo "\n  <li class=\"last\">";
			$itemarray = getItemTitleAndURL($items[$itemlist[$total - 1]]);
			printLinkHTML($itemarray['url'], $total, sprintf(ngettext('Page {%u}', 'Page {%u}', $total), $total));
			echo "</li>";
		}
		if ($nextprev) {
			echo "<li class=\"next\">";
			printMenumanagerNextLink($nexttext, gettext("Next Page"));
			echo "</li>\n";
		}
		echo "</ul>\n";
		echo "</div>\n";
	}
}

/**
 * Prints a full page navigation including previous and next page links with a list of all pages in between.
 *
 * @param string $nexttext Insert here the linktext like 'next page'
 * @param string $class Insert here the CSS-class name you want to style the link with (default is "pagelist")
 * @param string $id Insert here the CSS-ID name if you want to style the link with this
 * @param bool $firstlast Add links to the first and last pages of you gallery
 * @param int $navlen Number of navigation links to show (0 for all pages). Works best if the number is odd.
 */
function printMenuemanagerPageList($menuset = 'default', $class = 'pagelist', $id = NULL, $firstlast = true, $navlen = 9) {
	printMenuemanagerPageListWithNav(null, null, $menuset, false, $class, $id, false, $navlen);
}

/**
 * Gets the parent menu items of the current menu item. Returns the array of the items or false.
 * @param string $menuset current menu set
 * @return array|false
 */
function getParentMenuItems($menuset = 'default') {
	$sortorder = getCurrentMenuItem($menuset);
	$items = getMenuItems($menuset, getMenuVisibility());
	if (count($items) > 0) {
		if ($sortorder) {
			$parents = array();
			$order = explode('-', $sortorder);
			array_pop($order);
			$look = array();
			while (count($order) > 0) {
				$look = implode('-', $order);
				array_pop($order);
				if (array_key_exists($look, $items)) {
					array_unshift($parents, $items[$look]);
				}
			}
			if (!empty($parents)) {
				return sortMultiArray($parents, 'sort_order', $descending = false, $natsort = false, $case_sensitive = false);
			}
		}
	}
	return false;
}

/**
 * Prints the breadcrumbs of the current page
 *
 * NOTE: this function is entirely dependedn on the menu tree you have
 * generated. It will work only with static menu trees. That is, if the page
 * upon which you call this function is not present in your menu tree it will
 * not have any parent pages. Thus, menu items generated for instance by function
 * calls cannot have parents in the printMenumanagerBreadcrumb sense.
 *
 * Likewise if you have non exclusive menu links to a page the parentage of that
 * page with respect to breadcrumbs may not reflect on the menu transitions that
 * the user used to arrive on the page.
 *
 * @param string $menuset current menu set
 * @param string $before before text
 * @param string $between between text
 * @param string $after after text
 */
function printMenumanagerBreadcrumb($menuset = 'default', $before = '', $between = ' | ', $after = ' | ') {
	$parents = getParentMenuItems($menuset);
	if ($parents) {
		if ($before) {
			echo '<span class="beforetext">' . html_encode($before) . '</span>';
		}
		if ($between) {
			$between = '<span class="betweentext">' . html_encode($between) . '</span>';
		}
		$i = 0;
		foreach ($parents as $item) {
			if ($i > 0) {
				echo $between;
			}
			$itemarray = getItemTitleAndURL($item);
			if ($item['type'] == 'menulabel') {
				echo html_encode($itemarray['title']);
			} else {
				printLinkHTML($itemarray['url'], $itemarray['title'], $itemarray['title']);
			}
			$i++;
		}
		if ($after) {
			echo '<span class="aftertext">' . html_encode($after) . '</span>';
		}
	}
}

/**
 * Returns the menu item corresponding to $link
 * @param string $link
 * @param string $menuset
 * @return array
 */
function getMenuFromLink($link, $menuset = 'default') {
	$link = rtrim(str_replace('\\', '/', $link), '/');
	$items = getMenuItems($menuset, getMenuVisibility());
	foreach ($items as $item) {
		$itemarray = getItemTitleAndURL($item);
		if ($itemarray['url'] == $link)
			return $item;
	}
	return NULL;
}

/**
 * Returns true if the current menu item is a sub item of $link
 * @param string $link possible parent
 * @param string $menuset current menuset
 * @return bool
 */
function submenuOf($link, $menuset = 'default') {
	$link_menu = getMenuFromLink($link, $menuset);
	if (is_array($link_menu)) {
		$current = getCurrentMenuItem($menuset);
		$items = getMenuItems($menuset, getMenuVisibility());
		if (!is_null($current)) {
			$sortorder = $link_menu['sort_order'];
			if (strlen($current) > strlen($sortorder)) {
				$p = strpos($current, $sortorder);
				return $p === 0;
			}
		}
	}
	return false;
}

/**
 * checks if there is a menu by the name $menuset
 *
 * @param string $menuset
 */
function menuExists($menuset) {
	return db_count('menu', 'WHERE menuset=' . db_quote($menuset));
}

/**
 * Creates a menu set from the items passed if the menu set does not already exist
 *
 * NOTE: it is better to check use menuExists() to check and createMenu() to built
 * the menu if the menu is multi-lingual. This avoids the overhead of doing the gettext()
 * translations for each menu item. This latter can chew up a lot of processing.
 *
 * @param array $menuitems
 * @param string $menuset
 * @return true if the menuset was created successfully
 */
function createMenuIfNotExists($menuitems, $menuset = 'default') {
	if (!menuExists($menuset)) {
		return createMenu($menuitems, $menuset);
	}
	return false;
}

/**
 * Creates a menu set from the items passed.
 * @param array $menuitems items for the menuset
 * 		array elements:
 * 			'type'=>menuset type
 * 			'title'=>title for the menu item
 * 			'link'=>URL or other data for the item link
 * 			'show'=>set to 1:"visible" or 0:"hidden",
 * 			'nesting'=>nesting level of this item in the menu heirarchy
 *
 * @param string $menuset current menuset
 */
function createMenu($menuitems, $menuset = 'default') {
	require_once(dirname(__FILE__) . '/menu_manager/menu_manager-admin-functions.php');
	$errors = false;
	$orders = array();
	foreach ($menuitems as $key => $result) {
		$success = 1;

		if (array_key_exists('nesting', $result)) {
			$nesting = $result['nesting'];
		} else {
			$nesting = 0;
		}
		while ($nesting + 1 < count($orders))
			array_pop($orders);
		while ($nesting + 1 > count($orders))
			array_push($orders, -1);
		$result['id'] = 0;
		if (isset($result['include_li'])) {
			$includeli = $result['include_li'];
		} else {
			$includeli = 1;
		}
		$type = $result['type'];
		switch ($type) {
			case 'all_items':
				$orders[$nesting] ++;
				query("INSERT INTO " . prefix('menu') . " (`title`,`link`,`type`,`show`,`menuset`,`sort_order`) " .
								"VALUES ('" . gettext('Home') . "', '" . WEBPATH . '/' . "','siteindex','1'," . db_quote($menuset) . ',' . db_quote($orders), true);
				$orders[$nesting] = addAlbumsToDatabase($menuset, $orders);
				if (extensionEnabled('zenpage')) {
					$orders[$nesting] ++;
					query("INSERT INTO " . prefix('menu') . " (title`,`link`,`type`,`show`,`menuset`,`sort_order`) " .
									"VALUES ('" . gettext('News index') . "', '" . getNewsIndexURL() . "','newsindex','1'," . db_quote($menuset) . ',' . db_quote(sprintf('%03u', $base + 1)), true);
					$orders[$nesting] = addPagesToDatabase($menuset, $orders) + 1;
					$orders[$nesting] = addCategoriesToDatabase($menuset, $orders);
				}
				$type = false;
				break;
			case 'all_albums':
				$orders[$nesting] ++;
				$orders[$nesting] = addAlbumsToDatabase($menuset, $orders);
				$type = false;
				break;
			case 'all_zenpagepages':
				$type = 'all_pages';
				$success = -5;
			case 'all_Pages':
				$orders[$nesting] ++;
				$orders[$nesting] = addPagesToDatabase($menuset, $orders);
				$type = false;
				break;
			case 'all_zenpagecategorys':
				$type = 'all_categorys';
				$success = -5;
			case 'all_categories':
				$orders[$nesting] ++;
				$orders[$nesting] = addCategoriesToDatabase($menuset, $orders);
				$type = false;
				break;
			case 'album':
				if (empty($result['link'])) {
					$success = -1;
					debugLogVar([sprintf(gettext('createMenu item %s has an empty link.'), $key) => $result]);
				}
				break;
			case 'homepage':
				$type = 'siteindex';
				$success = -5;
			case 'siteindex':
				$result['link'] = NULL;
				if (empty($result['title'])) {
					$success = -1;
					debugLogVar([sprintf(gettext('createMenu item %s has an empty title.'), $key) => $result]);
				}
				break;
			case 'zenpagepage':
				$type = 'page';
				$success = -5;
			case 'page':
				if (empty($result['link'])) {
					$success = -1;
					debugLogVar([sprintf(gettext('createMenu item %s has an empty link.'), $key) => $result]);
				}
				break;
			case 'zenpagenewsindex':
				$type = 'newsindex';
				$success = -5;
			case 'newsindex':
				$result['link'] = NULL;
				if (empty($result['title'])) {
					$success = -2;
					debugLogVar([sprintf(gettext('createMenu item %s has an empty title.'), $key) => $result]);
				}
				break;
			case 'zenpagecategory':
				$type = 'category';
				$success = -5;
			case 'category':
				if (empty($result['link'])) {
					$success = -1;
					debugLogVar([sprintf(gettext('createMenu item %s has an empty link.'), $key) => $result]);
				}
				$result['link'] = NULL;
				break;
			case 'custompage':
				if (empty($result['title']) || empty($result['link'])) {
					$success = -1;
					debugLogVar([sprintf(gettext('createMenu item %s has an empty title or link.'), $key) => $result]);
				}
				break;
			case 'galleryindex':
				$type = 'albumindex';
				$success = -5;
			case 'albumindex':
				if (empty($result['title'])) {
					$success = -1;
					debugLogVar([sprintf(gettext('createMenu item %s has an empty title.'), $key) => $result]);
				}
				$result['link'] = NULL;
				break;
			case 'dynamiclink':
			case 'customlink':
				if (empty($result['title'])) {
					$success = -1;
					debugLogVar([sprintf(gettext('createMenu item %s has an empty title.'), $key) => $result]);
				} else if (empty($result['link'])) {
					$result['link'] = seoFriendly(get_language_string($result['title']));
				}
				break;
			case 'menulabel':
				if (empty($result['title'])) {
					$success = -1;
					debugLogVar([sprintf(gettext('createMenu item %s has an empty title.'), $key) => $result]);
				}
				$result['link'] = sha1($result['title']);
				break;
			case 'menufunction':
			case 'html':
				if (empty($result['title']) || empty($result['link'])) {
					$success = -1;
					debugLogVar([sprintf(gettext('createMenu item %s has an empty title or link.'), $key) => $result]);
				}
				break;
			default:
				$success = -1;
				debugLogVar([sprintf(gettext('createMenu item %1$s has an invalid type (%2$s).'), $key, $type) => $result]);
				break;
		}
		if ($success == -5) {
			debugLog(sprintf(gettext('%1$s item %2$s menu type %3$s is deprecated, use %4$s.'), $menuset, $key, $result['type'], $type));
			$success = 1;
			$errors = true;
		}
		if ($success > 0 && $type) {
			$orders[$nesting] ++;
			$sort_order = '';
			for ($i = 0; $i < count($orders); $i++) {
				$sort_order .= sprintf('%03u', $orders[$i]) . '-';
			}
			$sort_order = substr($sort_order, 0, -1);
			$sql = "INSERT INTO " . prefix('menu') . " (`title`,`link`,`type`,`show`,`menuset`,`sort_order`,`include_li`) " .
							"VALUES (" . db_quote($result['title']) .
							", " . db_quote($result['link']) .
							"," . db_quote($result['type']) . "," . $result['show'] .
							"," . db_quote($menuset) . "," . db_quote($sort_order) . ",$includeli)";
			if (!query($sql, false)) {
				$success = -2;
				debugLog(sprintf(gettext('createMenu item %1$s query (%2$s) failed: %3$s.'), $key, $sql, db_error()));
			}
		}
		$errors = $errors || $success < 0;
	}

	if ($errors) {
		trigger_error(sprintf(gettext('createMenu has posted errors to your debug log while processing the %1$s menu.'), $menuset), E_USER_NOTICE);
	}
	return $success;
}

/**
 * Gets the direct child menu items of the current menu item. Returns the array of the items or false.
 * @param string $menuset current menu set
 * @param bool $all Set to false (default) for the next level children, true for children of all further levels
 * @return array|false
 */
function getMenuItemChildren($menuset = 'default', $all = false) {
	$sortorder = getCurrentMenuItem($menuset);
	$items = getMenuItems($menuset, getMenuVisibility());
	if (count($items) > 0) {
		if ($sortorder) {
			$length = strlen($sortorder);
			$level = explode('-', $sortorder);
			$level = count($level);
			$children = array();
			foreach ($items as $item) {
				$itemlevel = explode('-', $item['sort_order']);
				$itemlevel = count($itemlevel);
				if ($all) {
					$is_validchild = true;
				} else {
					if ($itemlevel == $level + 1) {
						$is_validchild = true;
					} else {
						$is_validchild = false;
					}
				}
				if (substr($item['sort_order'], 0, $length) == $sortorder && $item['sort_order'] != $sortorder && $is_validchild) {
					array_push($children, $item);
				}
			}
			if (!empty($children)) {
				return $children;
			}
		}
	}
	return false;
}

/**
 * Prints a context sensitive menu of all pages as a unordered html list
 *
 * @param string $menuset the menu tree to output
 * @param string $option The mode for the menu:
 * 												"list" context sensitive toplevel plus sublevel pages,
 * 												"list-top" only top level pages,
 * 												"omit-top" only sub level pages
 * 												"list-sub" lists only the current pages direct offspring
 * @param string $css_id CSS id of the top level list
 * @param string $css_class_topactive class of the active item in the top level list
 * @param string $css_class CSS class of the sub level list(s)
 * @param string $css_class_active CSS class of the sub level list(s)
 * @param int $showsubs Set to depth of sublevels that should be shown always. 0 by default. To show all, set to a true! Only valid if option=="list".
 * @param bool $counter TRUE (FALSE default) if you want the count of articles for news categories or images/subalbums for albums.
 *
 * @return string
 */
function printCustomMenu($menuset = 'default', $option = 'list', $css_id = '', $css_class_topactive = '', $css_class = '', $css_class_active = '', $showsubs = 0, $counter = false) {
	$itemcounter = '';
	if ($css_id != "") {
		$css_id = " id='" . $css_id . "'";
	}
	if ($showsubs === true)
		$showsubs = 9999999999;

	$sortorder = getCurrentMenuItem($menuset);
	$items = getMenuItems($menuset, getMenuVisibility());

	if (count($items) == 0)
		return; // nothing to do
	$currentitem_parentid = @$items[$sortorder]['parentid'];
	if ($startlist = !($option == 'omit-top' || $option == 'list-sub')) {
		echo "<ul$css_id>";
	}
	$pageid = @$items[$sortorder]['id'];
	$baseindent = max(1, count(explode("-", $sortorder)));
	$indent = 1;
	$open = array($indent => 0);
	$parents = array(NULL);
	$order = explode('-', $sortorder);
	$mylevel = count($order);
	$myparentsort = array_shift($order);

	for ($c = 0; $c <= $mylevel; $c++) {
		$parents[$c] = NULL;
	}
	foreach ($items as $item) {
		$itemarray = getItemTitleAndURL($item);
		$itemURL = $itemarray['url'];
		$itemtitle = get_language_string($itemarray['title']);
		$level = max(1, count(explode('-', $item['sort_order'])));
		$process = (($level <= $showsubs && $option == "list") // user wants all the pages whose level is <= to the parameter
						|| ($option == 'list' || $option == 'list-top') && $level == 1 // show the top level
						|| (($option == 'list' || ($option == 'omit-top' && $level > 1)) && (($item['id'] == $pageid) // current page
						|| ($item['parentid'] == $pageid) // offspring of current page
						|| ($level < $mylevel && $level > 1 && strpos($item['sort_order'], $myparentsort) === 0)) // direct ancestor
						|| (($level == $mylevel) && ($currentitem_parentid == $item['parentid'])) // sibling
						) || ($option == 'list-sub' && ($item['parentid'] == $pageid) // offspring of the current page
						)
						);
		if ($process && !$itemarray['invalid']) {
			if ($level > $indent) {
				echo "\n" . str_pad("\t", $indent, "\t") . "<ul class=\"$css_class menu_{$item['type']}\">\n";
				$indent++;
				$parents[$indent] = NULL;
				$open[$indent] = 0;
			} else if ($level < $indent) {
				$parents[$indent] = NULL;
				while ($indent > $level) {
					if ($open[$indent]) {
						$open[$indent] --;
						echo "</li>\n";
					}
					$indent--;
					echo str_pad("\t", $indent, "\t") . "</ul>\n";
				}
			} else { // level == indent, have not changed
				if ($open[$indent]) { // level = indent
					echo str_pad("\t", $indent, "\t") . "</li>\n";
					$open[$indent] --;
				} else {
					echo "\n";
				}
			}

			if ($open[$indent]) { // close an open LI if it exists
				echo "</li>\n";
				$open[$indent] --;
			}

			echo str_pad("\t", $indent - 1, "\t");
			$open[$indent] += $item['include_li'];
			$parents[$indent] = $item['id'];
			if ($counter) {
				switch ($item['type']) {
					case'album':
						$albumobj = newAlbum($item['link']);
						$numimages = $albumobj->getNumImages();
						$numsubalbums = $albumobj->getNumAlbums();
						$itemcounter = ' <span style="white-space:nowrap;"><small>(';
						if ($numsubalbums != 0) {
							$itemcounter .= sprintf(ngettext('%u album', '%u albums', $numsubalbums), $numsubalbums);
						}
						if ($numimages != 0) {
							if ($numsubalbums != 0) {
								$itemcounter .= ' ';
							}
							$itemcounter .= sprintf(ngettext('%u image', '%u images', $numimages), $numimages);
						}
						$itemcounter .= ')</small></span>';

						break;
					case 'zenpagecategory':
					case'category':
						if ((npg_loggedin(ZENPAGE_NEWS_RIGHTS | ALL_NEWS_RIGHTS))) {
							$published = "all";
						} else {
							$published = "published";
						}
						$catobj = newCategory($item['link']);
						$catcount = count($catobj->getArticles(0, $published));
						$itemcounter = "<small> (" . $catcount . ")</small>";
						break;
				}
			}
			if ($item['id'] == $pageid && !is_null($pageid)) {
				if ($level == 1) { // top level
					$class = $css_class_topactive;
				} else {
					$class = $css_class_active;
				}
				echo '<li class="menu_' . trim($item['type'] . ' ' . $class) . '">' . $itemtitle . $itemcounter;
			} else {
				if (strpos($sortorder, $item['sort_order']) === 0) { // we are in the heritage chain
					$class = ' ' . $css_class_active . '-' . ($mylevel - $level);
				} else {
					$class = '';
				}
				if ($item['include_li']) {
					echo '<li class="menu_' . $item['type'] . $class . '">';
				}
				if ($item['span_id'] || $item['span_class']) {
					echo '<span';
					if ($item['span_id'])
						echo ' id="' . $item['span_id'] . '"';
					if ($item['span_class'])
						echo ' class="' . $item['span_class'] . '"';
					echo '>';
				}
				switch ($item['type']) {
					case 'html':
						echo $item['link'];
						break;
					case 'menufunction':
						$i = strpos($itemURL, '(');
						if ($i) {
							if (function_exists(trim(substr($itemURL, 0, $i)))) {
								eval($itemURL);
							}
						}
						break;
					case 'menulabel':
						echo $itemtitle;
						break;
					case'dynamiclink':
						eval('$itemURL=' . $itemURL . ';');
					default:
						if (empty($itemURL)) {
							$itemURL = FULLWEBPATH;
						}
						echo '<a href="' . $itemURL . '" title="' . html_encode(getBare($itemtitle)) . '">' . $itemtitle . '</a>' . $itemcounter;
						break;
				}
				if ($item['span_id'] || $item['span_class']) {
					echo '</span>';
				}
			}
		}
	}
// cleanup any hanging list elements
	while ($indent > 1) {
		if ($open[$indent]) {
			echo "</li>\n";
			$open[$indent] --;
		}
		$indent--;
		echo str_pad("\t", $indent, "\t") . "</ul>";
	}
	if ($open[$indent]) {
		echo "</li>\n";
		$open[$indent] --;
	} else {
		echo "\n";
	}
	if ($startlist) {
		echo "</ul>\n";
	}
}

?>
