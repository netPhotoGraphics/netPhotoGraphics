<?php

/**
 * Provides functions to print a tag cloud/list of all image tags from an album (optionally including the subalbums) or the album tags including sub album tags.
 * Or alternatively a tag cloud/list of all tags used by either Zenpage news articles or pages.
 *
 * Note: The optional counter prints the total number of the tag used, not just for the select items (as clicking on it will return all anyway.)
 *
 * @author Malte Müller (acrylian)
 *
 * @package plugins/tag_extras
 * @pluginCategory theme
 */
$plugin_description = gettext("Provides functions to print a tag cloud of all tags from an object.");

/**
 * Prints a tag cloud list of the tags in one album and optionally its subalbums. Returns FALSE if no value.
 *
 * @param string $albumname folder name of the album to get the tags from ($subalbums = true this is the base albums)- This value is mandatory.
 * @param bool $subalbums TRUE if the tags of subalbum should be. FALSE is default
 * @param string $mode "images" for image tags, "albums" for album tags."images" is default.
 * @return array
 */
function getAllTagsFromAlbum($albumname, $subalbums = false, $mode = 'images') {
	global $_gallery;
	$imageWhere = $tagWhere = $tags = FALSE;
	if (npg_loggedin(TAGS_RIGHTS)) {
		$private = '';
	} else {
		$private = ' AND (t.private=0)';
	}

	if (empty($albumname)) {
		return FALSE;
	}
	$albumobj = newAlbum($albumname);
	if (!$albumobj->exists) {
		return FALSE;
	}
	if (npg_loggedin()) {
		$albumWhere = "";
	} else {
		$albumscheck = query_full_array("SELECT `id`, `folder` FROM " . prefix('albums') . " ORDER BY title");
		$passwordcheck = array();
		foreach ($albumscheck as $albumcheck) {
			if (!checkAlbumPassword($albumcheck['folder'])) {
				$passwordcheck[] = $albumcheck['id'];
			}
		}
		$albumWhere = "WHERE `show`=1";
		if (!empty($passwordcheck)) {
			$albumWhere .= ' AND id in(' . implode(',', $passwordcheck) . ')';
		}
	}
	if ($subalbums) {
		$albumWhere .= " AND `folder` LIKE " . db_quote(db_LIKE_escape($albumname) . "%");
	} else {
		$albumWhere .= " AND `folder` = " . db_quote($albumname);
	}

	$albumids = query_full_array("SELECT id, folder FROM " . prefix('albums') . $albumWhere);
	switch ($mode) {
		case "images":
			if (count($albumids) == 0) {
				return FALSE;
			} else {
				$imageWhere = " WHERE ";
				$count = 0;
				foreach ($albumids as $albumid) {
					$count++;
					$imageWhere .= 'albumid=' . $albumid['id'];
					if ($count != count($albumids))
						$imageWhere .= " OR ";
				}
			}
			$imageids = query_full_array("SELECT id, albumid FROM " . prefix('images') . $imageWhere);
			// if the album has no direct images and $subalbums is set to false
			if (count($imageids) == 0) {
				return FALSE;
			} else {
				$count = 0;
				$tagWhere = " WHERE ";
				foreach ($imageids as $imageid) {
					$count++;
					$tagWhere .= '(o.objectid =' . $imageid['id'] . " AND o.tagid = t.id AND o.type = 'images')";
					if ($count != count($imageids))
						$tagWhere .= " OR ";
				}
			}
			if (empty($tagWhere)) {
				return FALSE;
			} else {
				$tags = query_full_array("SELECT DISTINCT t.name, t.id, (SELECT DISTINCT COUNT(*) FROM " . prefix('obj_to_tag') . " WHERE tagid = t.id AND type = 'images') AS count FROM  " . prefix('obj_to_tag') . " AS o," . prefix('tags') . " AS t" . $tagWhere . $private . " ORDER BY t.name");
			}
			break;
		case "albums":
			$count = 0;
			if (count($albumids) == 0) {
				return FALSE;
			} else {
				$tagWhere = " WHERE ";
				foreach ($albumids as $albumid) {
					$count++;
					$tagWhere .= '(o.objectid =' . $albumid['id'] . " AND o.tagid = t.id AND o.type = 'albums')";
					if ($count != count($albumids))
						$tagWhere .= " OR ";
				}
			}
			if (empty($tagWhere)) {
				return FALSE;
			} else {
				$tags = query_full_array("SELECT DISTINCT t.name, t.id, (SELECT DISTINCT COUNT(*) FROM " . prefix('obj_to_tag') . " WHERE tagid = t.id AND o.type = 'albums') AS count FROM " . prefix('obj_to_tag') . " AS o," . prefix('tags') . " AS t" . $tagWhere . " ORDER BY t.name");
			}
			break;
	}
	return $tags;
}

/**
 * Gets all tags used by either all Zenpage news articles or pages.
 * @param string $mode "news" for Zenpage news article tags, "pages" for Zenpage pages tags
 *
 */
function getAllTagsFromZenpage($mode = 'news') {
	global $_gallery, $_CMS;
	if (!class_exists('CMS')) {
		return FALSE;
	}
	$ids = array();
	$where = '';
	$tagWhere = "";
	switch ($mode) {
		case 'news':
			if (npg_loggedin(ZENPAGE_NEWS_RIGHTS | ALL_NEWS_RIGHTS)) {
				$published = 'all';
			} else {
				$published = 'published';
			}
			$type = 'news';
			$items = $_CMS->getArticles(false, $published);
			foreach ($items as $item) {
				$obj = newArticle($item['titlelink']);
				if ($obj->checkAccess()) {
					$ids[] = $obj->getID();
				}
			}
			break;
		case 'pages':
			$published = !npg_loggedin(ZENPAGE_NEWS_RIGHTS | ALL_NEWS_RIGHTS);
			$type = 'pages';
			$items = $_CMS->getPages($published);
			foreach ($items as $item) {
				$obj = newPage($item['titlelink']);
				if ($obj->checkAccess()) {
					$ids[] = $obj->getID();
				}
			}
			break;
	}
	$count = 0;
	if (count($ids) == 0) {
		return FALSE;
	} else {
		$tagWhere = " WHERE ";
		foreach ($ids as $id) {
			$count++;
			$tagWhere .= '(o.objectid =' . $id . " AND o.tagid = t.id AND o.type = '" . $type . "')";
			if ($count != count($ids))
				$tagWhere .= " OR ";
		}
	}
	if (empty($tagWhere)) {
		return FALSE;
	} else {
		$tags = query_full_array("SELECT DISTINCT t.name, t.id, (SELECT DISTINCT COUNT(*) FROM " . prefix('obj_to_tag') . " WHERE tagid = t.id AND o.type = '" . $type . "') AS count FROM " . prefix('obj_to_tag') . " AS o," . prefix('tags') . " AS t" . $tagWhere . $private . " ORDER BY t.name");
	}
	return $tags;
}

/**
 * Prints a tag cloud list of the tags used by either all Zenpage news articles or pages.
 *
 * @param string $mode "news" for Zenpage news article tags, "pages" for Zenpage pages tags
 * @param string $separator how to separate the entries
 * @param string $class css classs to style the list
 * @param integer $showcounter if the tag count should be shown (no counter if $mode = "all")
 * @param bool $tagcloud if set to false a simple list without font size changes will be printed, set to true (default) prints a list as a tag cloud
 * @param integere $size_min smallest font size the cloud should display
 * @param integer $size_max largest font size the cloud should display
 * @param integer $count_min the minimum count for a tag to appear in the output
 * @param integer $count_max the floor count for setting the cloud font size to $size_max
 */
function printAllTagsFromZenpage($mode = 'news', $separator = '', $class = '', $showcounter = true, $tagcloud = true, $size_min = 1, $size_max = 5, $count_min = 1, $count_max = 50) {
	$tags = getAllTagsFromZenpage($mode);
	printAllTags($tags, $mode, $separator, $class, $showcounter, $tagcloud, $size_min, $size_max, $count_min, $count_max);
}

/**
 * Prints a tag cloud list of the tags in one album and optionally its subalbums.
 * Known limitation: If $mode is set to "all" there is no tag count and therefore no tag cloud but a simple list
 *
 * @param string $albumname folder name of the album to get the tags from ($subalbums = true this is the base albums)
 * @param bool $subalbums TRUE if the tags of subalbum should be. FALSE is default
 * @param string $mode "images" for image tags, "albums" for album tags, "all" for both mixed
 * @param string $separator how to separate the entries
 * @param string $class css classs to style the list
 * @param integer $showcounter if the tag count should be shown (no counter if $mode = "all")
 * @param bool $tagcloud if set to false a simple list without font size changes will be printed, set to true (default) prints a list as a tag cloud
 * @param integere $size_min smallest font size the cloud should display
 * @param integer $size_max largest font size the cloud should display
 * @param integer $count_min the minimum count for a tag to appear in the output
 * @param integer $count_max the floor count for setting the cloud font size to $size_max
 */
function printAllTagsFromAlbum($albumname = "", $subalbums = false, $mode = 'images', $separator = '', $class = '', $showcounter = true, $tagcloud = true, $size_min = 1, $size_max = 5, $count_min = 1, $count_max = 50) {
	if ($mode == 'all') {
		if (getAllTagsFromAlbum($albumname, $subalbums, 'albums') OR getAllTagsFromAlbum($albumname, $subalbums, 'images')) {
			$showcounter = false;
			$tags1 = getAllTagsFromAlbum($albumname, $subalbums, 'albums');
			$tags2 = getAllTagsFromAlbum($albumname, $subalbums, 'images');
			$tags = array_merge($tags1, $tags2);
			$tags = getAllTagsFromAlbum_multi_unique($tags);
		} else {
			return FALSE;
		}
	} else {
		if (getAllTagsFromAlbum($albumname, $subalbums, $mode)) {
			$tags = getAllTagsFromAlbum($albumname, $subalbums, $mode);
		} else {
			return FALSE;
		}
	}
	printAllTags($tags, $mode, $separator, $class, $showcounter, $tagcloud, $size_min, $size_max, $count_min, $count_max);
}

/**
 * Prints a tag cloud list of the tags in one album and optionally its subalbums. Base function to printAllTagsFromAlbum().
 *
 * Note meant to be used standalone.
 *
 * @param array $tags array of tags with the fields count, id, and name (as passed by the specific printAllTagsFrom.... functions)
 * @param string $mode "images" for image tags, "albums" for album tags, "all" for images and albums mixed or "news" for Zenpage news articles, "pages" for Zenpage pages
 * @param string $separator how to separate the entries
 * @param string $class css classs to style the list
 * @param integer $showcounter if the tag count should be shown (no counter if $mode = "all")
 * @param bool $tagcloud if set to false a simple list without font size changes will be printed, set to true (default) prints a list as a tag cloud
 * @param integere $size_min smallest font size the cloud should display
 * @param integer $size_max largest font size the cloud should display
 * @param integer $count_min the minimum count for a tag to appear in the output
 * @param integer $count_max the floor count for setting the cloud font size to $size_max
 */
function printAllTags($tags, $mode, $separator = '', $class = '', $showcounter = true, $tagcloud = true, $size_min = 1, $size_max = 5, $count_min = 1, $count_max = 50) {
	if (!is_array($tags)) {
		return FALSE;
	}
	if (!empty($class))
		$class = 'class="' . $class . '"';
	$counter = '';
	echo "<ul " . $class . ">\n";
	$loopcount = '';
	$tagcount = count($tags);
	foreach ($tags as $row) {
		if ($row['count'] >= $count_min) {
			$loopcount++;
			$count = $row['count'];
			$tid = $row['id'];
			$tname = $row['name'];
			$style = "";
			if ($tagcloud OR $mode == 'all') {
				$size = min(max(round(($size_max * ($count - $count_min)) / ($count_max - $count_min), 2), $size_min)
								, $size_max);
				$size = str_replace(',', '.', $size);
				$style = " style=\"font-size:" . $size . "em;\"";
			}
			if ($showcounter) {
				$counter = ' (' . $count . ')';
			}
			if ($loopcount == $tagcount)
				$separator = '';
			echo "<li><a class=\"tagLink\" href=\"" . html_encode(SearchEngine::getURL($tname, '', 'tags', 0)) . "\"" . $style . ">" . $tname . $counter . "</a>" . $separator . "</li>\n";
		}
	}
	echo "</ul>\n";
}

/**
 * Removes duplicate entries in multi dimensional array.
 * From kenrbnsn at rbnsn dot com http://uk.php.net/manual/en/function.array-unique.php#57202
 * @param array $array
 * @return array
 */
function getAllTagsFromAlbum_multi_unique($array) {
	foreach ($array as $k => $na)
		$new[$k] = serialize($na);
	$uniq = array_unique($new);
	foreach ($uniq as $k => $ser)
		$new1[$k] = getSerializedArray($ser);
	return ($new1);
}

?>
