<?php

/**
 * Support functions for "statistics" about images and albums.
 *
 * Supports such statistics as "most popular", "latest", "top rated", etc.
 *
 * <b>CAUTION:</b> The way to get a specific album has changed. You now have to pass
 * the foldername of an album instead the album title.
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @package plugins/image_album_statistics
 * @pluginCategory theme
 */
$plugin_description = gettext("Functions that provide various statistics about images and albums in the gallery.");

require_once(dirname(__DIR__) . '/template-functions.php');

/**
 * Returns an array of album objects accordingly to $option
 *
 * @param int $number the number of albums to get
 * @param string $option
 * 		"popular" for the most popular albums,
 * 		"latest" for the latest uploaded by id (Discovery)
 * 		"latest-date" for the latest by date
 * 		"latest-mtime" for the latest by mtime
 *   	"latest-publishdate" for the latest by publishdate
 *    "mostrated" for the most voted,
 * 		"toprated" for the best voted
 * 		"latestupdated" for the latest updated
 * 		"random" for random order (yes, strictly no statistical order...)
 * @param string $albumfolder The name of an album to get only the statistc for its subalbums
 * @param integer $threshold the minimum number of ratings (for rating options) or hits (for popular option) an album must have to be included in the list. (Default 0)
 * @param mixed $sortdirection "asc" for ascending order otherwise order is descending
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics to include all subalbum levels
 * @return array
 */
function getAlbumStatistic($number = 5, $option = 'latest', $albumfolder = '', $threshold = 0, $sortdirection = 'desc', $collection = false) {
	global $_gallery;
	$where = '';
	if ($albumfolder) {
		$obj = newAlbum($albumfolder);
		$where = ' WHERE parentid = ' . $obj->getID();
		if ($collection) {
			$ids = getAllSubAlbumIDs($albumfolder);
			if (!empty($ids)) {
				foreach ($ids as $id) {
					$getids[] = $id['id'];
				}
				$getids = implode(', ', $getids);
				$where = ' WHERE id IN (' . $getids . ')';
			}
		}
	} else {
		$obj = $_gallery;
	}
	switch (strtolower($sortdirection)) {
		case false:
		case 'asc':
			$sortdir = ' ASC';
			break;
		default:
			$sortdir = ' DESC';
	}

	$opt = '';
	switch ($option) {
		case "popular":
			$sortorder = "hitcounter";
			$opt = 'hitcounter >= ' . $threshold;
			break;
		default:
		case "latest":
			$sortorder = "id";
			break;
		case "latest-mtime":
			$sortorder = "mtime";
			break;
		case "latest-date":
			$sortorder = "date";
			break;
		case "latest-publishdate":
			$sortorder = "IFNULL(publishdate,date)";
			break;
		case "mostrated":
			$sortorder = "total_votes";
			break;
		case "toprated":
			$sortorder = "(total_value/total_votes) DESC, total_value";
			$opt = 'total_votes >= ' . $threshold;
			break;
		case "latestupdated":
			$sortorder = 'updateddate';
			break;
		case "random":
			$sortorder = "RAND()";
			break;
	}
	if ($opt) {
		if ($where) {
			$where .= ' AND ' . $opt;
		} else {
			$where = ' WHERE ' . $opt;
		}
	}
	$albums = array();
	if ($albumfolder && $obj->isDynamic()) {
		$albums = $obj->getAlbums(0, $sortorder, $sortdir);
		foreach ($albums as $album) {
			$album = newAlbum($album);
			if ($album->checkAccess()) {
				$albums[] = $album;
				if (count($albums) >= $number) { // got enough
					break;
				}
			}
		}
	} else {
		$result = query('SELECT id, title, folder, thumb FROM ' . prefix('albums') . $where . ' ORDER BY ' . $sortorder . $sortdir);
		if ($result) {
			while ($row = db_fetch_assoc($result)) {
				if (empty($albumfolder) || strpos($row['folder'], $albumfolder) === 0) {
					$obj = newAlbum($row['folder'], true);
					if ($obj->exists && $obj->checkAccess()) {
						$albums[] = newAlbum($row['folder']);
						if (count($albums) >= $number) { // got enough
							break;
						}
					}
				}
			}
			db_free_result($result);
		}
	}
	return $albums;
}

/**
 * Prints album statistic according to $option as an unordered HTML list
 * A css id is attached by default named '$option_album'
 *
 * @param string $number the number of albums to get
 * @param string $option
 * 		"popular" for the most popular albums,
 * 		"latest" for the latest uploaded by id (Discovery)
 * 		"latest-date" for the latest by date
 * 		"latest-mtime" for the latest by mtime
 *   	"latest-publishdate" for the latest by publishdate
 *    "mostrated" for the most voted,
 * 		"toprated" for the best voted
 * 		"latestupdated" for the latest updated
 * 		"random" for random order (yes, strictly no statistical order...)
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 * 		"hitcounter" for showing the hitcounter (views),
 * 		"rating" for rating,
 * 		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param integer $threshold the minimum number of ratings (for rating options) or hits (for popular option) an album must have to be included in the list. (Default 0)
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics to include all subalbum levels
 */
function printAlbumStatistic($number, $option, $showtitle = false, $showdate = false, $showdesc = false, $desclength = 40, $showstatistic = '', $width = NULL, $height = NULL, $crop = NULL, $albumfolder = '', $firstimglink = false, $threshold = 0, $collection = false) {
	$albums = getAlbumStatistic($number, $option, $albumfolder, $threshold, $collection);
	echo "\n<div id=\"" . $option . "_album\">\n";
	echo "<ul>";
	foreach ($albums as $album) {
		printAlbumStatisticItem($album, $option, $showtitle, $showdate, $showdesc, $desclength, $showstatistic, $width, $height, $crop, $firstimglink);
	}
	echo "</ul></div>\n";
}

/**
 * A helper function that only prints a item of the loop within printAlbumStatistic()
 * Not for standalone use.
 *
 * @param array $album object
 * @param string $option
 * 		"popular" for the most popular albums,
 * 		"latest" for the latest uploaded by id (Discovery)
 * 		"latest-date" for the latest by date
 * 		"latest-mtime" for the latest by mtime
 *   	"latest-publishdate" for the latest by publishdate
 *    "mostrated" for the most voted,
 * 		"toprated" for the best voted
 * 		"latestupdated" for the latest updated
 * 		"random" for random order (yes, strictly no statistical order...)
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 * 		"hitcounter" for showing the hitcounter (views),
 * 		"rating" for rating,
 * 		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $firstimglink 'false' (default) if the album thumb link should lead to the album page, 'true' if to the first image of theh album if the album itself has images
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics to include all subalbum levels
 */
function printAlbumStatisticItem($album, $option, $showtitle = false, $showdate = false, $showdesc = false, $desclength = 40, $showstatistic = '', $width = NULL, $height = NULL, $crop = NULL, $firstimglink = false) {
	global $_gallery;
	$twidth = $width;
	$theight = $height;
	if (is_null($crop) && is_null($width) && is_null($height)) {
		$crop = 2;
	} else {
		if (is_null($width))
			$width = 85;
		if (is_null($height))
			$height = 85;
		if (is_null($crop)) {
			$crop = 1;
		} else {
			$crop = (int) $crop && true;
		}
	}

	if ($firstimglink && $tempimage = $album->getImage(0)) {
		$albumpath = $tempimage->getLink();
	} else {
		$albumpath = $album->getLink();
	}
	echo "<li><a href=\"" . $albumpath . "\" title=\"" . html_encode($album->getTitle()) . "\">\n";
	$albumthumb = $album->getAlbumThumbImage();
	switch ($crop) {
		case 0:
			$sizes = getSizeCustomImage(array('size' => $width), $albumthumb);
			$html = '<img src="' . html_encode($albumthumb->getCustomImage(array('size' => $width, 'thumb' => TRUE))) . '" width="' . $sizes[0] . '" height="' . $sizes[1] . '" alt="' . html_encode($albumthumb->getTitle()) . '" />';
			break;
		case 1;
			if ($albumthumb->isPhoto()) {
				$sizes = getSizeCustomImage(array('width' => $width, 'height' => $height, 'cw' => $width, 'ch' => $height), $albumthumb);
			} else {
				$sizes = array($width, $height);
			}
			$html = '<img src="' . html_encode($albumthumb->getCustomImage(array('size' => $width, 'height' => $height, 'cw' => $width, 'ch' => $height, 'thumb' => TRUE))) . '" width="' . $sizes[0] . '" height="' . $sizes[1] . '" alt="' . html_encode($albumthumb->getTitle()) . '" />';
			break;
		default:
			$sizes = getSizeDefaultThumb($albumthumb);
			$html = '<img src="' . html_encode($albumthumb->getThumb()) . '" width="' . $sizes[0] . '" height="' . $sizes[1] . '" alt="' . html_encode($albumthumb->getTitle()) . '" />';
			break;
	}
	npgFilters::apply('custom_album_thumb_html', $html) . "\n</a>\n";

	if ($showtitle) {
		echo "<h3><a href=\"" . $albumpath . "\" title=\"" . html_encode($album->getTitle()) . "\">\n";
		echo $album->getTitle() . "</a></h3>\n";
	}
	if ($showdate) {
		if ($option === "latestupdated") {
			$filechangedate = strtotime($album->getUpdatedDate());
			echo "<p>" . sprintf(gettext("Last update: %s"), formattedDate(DATE_FORMAT, $filechangedate)) . "</p>";
			$latestimage = query_single_row("SELECT mtime FROM " . prefix('images') . " WHERE albumid = " . $album->getID() . " AND `show`=1 ORDER BY id DESC");
			if ($latestimage) {
				$count = db_count('images', "WHERE albumid = " . $album->getID() . " AND mtime = " . $latestimage['mtime']);
				if ($count <= 1) {
					$image = gettext("image");
				} else {
					$image = gettext("images");
				}
				echo "<span>" . sprintf(gettext('%1$u new %2$s'), $count, $image) . "</span>";
			}
		} else {
			echo "<p>" . formattedDate(DATE_FORMAT, strtotime($album->getDateTime())) . "</p>";
		}
	}
	if ($showstatistic === "rating" OR $showstatistic === "rating+hitcounter") {
		$votes = $album->get("total_votes");
		$value = $album->get("total_value");
		if ($votes != 0) {
			$rating = round($value / $votes, 1);
		}
		echo "<p>" . sprintf(gettext('Rating: %1$u (Votes: %2$u)'), $rating, $album->get("total_votes")) . "</p>";
	}
	if ($showstatistic === "hitcounter" OR $showstatistic === "rating+hitcounter") {
		$hitcounter = $album->getHitcounter();
		if (empty($hitcounter)) {
			$hitcounter = "0";
		}
		echo "<p>" . sprintf(gettext("Views: %u"), $hitcounter) . "</p>";
	}
	if ($showdesc) {
		echo html_encodeTagged(shortenContent($album->getDesc(), $desclength, ' (...)'));
	}
	echo "</li>";
}

/**
 * Prints the most popular albums
 *
 * @param string $number the number of albums to get
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 * 		"hitcounter" for showing the hitcounter (views),
 * 		"rating" for rating,
 * 		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $firstimglink 'false' (default) if the album thumb link should lead to the album page, 'true' if to the first image of theh album if the album itself has images
 * @param integer $threshold the minimum number of ratings (for rating options) or hits (for popular option) an album must have to be included in the list. (Default 0)
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics to include all subalbum levels
 */
function printPopularAlbums($number = 5, $showtitle = false, $showdate = false, $showdesc = false, $desclength = 40, $showstatistic = 'hitcounter', $width = NULL, $height = NULL, $crop = NULL, $albumfolder = '', $firstimglink = false, $threshold = 0, $collection = false) {
	printAlbumStatistic($number, "popular", $showtitle, $showdate, $showdesc, $desclength, $showstatistic, $width, $height, $crop, $albumfolder, $firstimglink, $threshold, $collection);
}

/**
 * Prints the latest albums
 *
 * @param string $number the number of albums to get
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 * 		"hitcounter" for showing the hitcounter (views),
 * 		"rating" for rating,
 * 		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $firstimglink 'false' (default) if the album thumb link should lead to the album page, 'true' if to the first image of theh album if the album itself has images
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics to include all subalbum levels
 */
function printLatestAlbums($number = 5, $showtitle = false, $showdate = false, $showdesc = false, $desclength = 40, $showstatistic = '', $width = NULL, $height = NULL, $crop = NULL, $albumfolder = '', $firstimglink = false, $collection = false) {
	printAlbumStatistic($number, "latest", $showtitle, $showdate, $showdesc, $desclength, $showstatistic, $width, $height, $crop, $albumfolder, $firstimglink, $collection);
}

/**
 * Prints the most rated albums
 *
 * @param string $number the number of albums to get
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 * 		"hitcounter" for showing the hitcounter (views),
 * 		"rating" for rating,
 * 		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $firstimglink 'false' (default) if the album thumb link should lead to the album page, 'true' if to the first image of theh album if the album itself has images
 * @param integer $threshold the minimum number of ratings (for rating options) or hits (for popular option) an album must have to be included in the list. (Default 0)
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics to include all subalbum levels
 */
function printMostRatedAlbums($number = 5, $showtitle = false, $showdate = false, $showdesc = false, $desclength = 40, $showstatistic = '', $width = NULL, $height = NULL, $crop = NULL, $albumfolder = '', $firstimglink = false, $threshold = 0, $collection = false) {
	printAlbumStatistic($number, "mostrated", $showtitle, $showdate, $showdesc, $desclength, $showstatistic, $width, $height, $crop, $albumfolder, $firstimglink, $threshold, $collection);
}

/**
 * Prints the top voted albums
 *
 * @param string $number the number of albums to get
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 * 		"hitcounter" for showing the hitcounter (views),
 * 		"rating" for rating,
 * 		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $firstimglink 'false' (default) if the album thumb link should lead to the album page, 'true' if to the first image of theh album if the album itself has images
 * @param integer $threshold the minimum number of ratings (for rating options) or hits (for popular option) an album must have to be included in the list. (Default 0)
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics to include all subalbum levels
 */
function printTopRatedAlbums($number = 5, $showtitle = false, $showdate = false, $showdesc = false, $desclength = 40, $showstatistic = '', $width = NULL, $height = NULL, $crop = NULL, $albumfolder = '', $firstimglink = false, $threshold = 0, $collection = false) {
	printAlbumStatistic($number, "toprated", $showtitle, $showdate, $showdesc, $desclength, $showstatistic, $width, $height, $crop, $albumfolder, $firstimglink, $threshold, $collection);
}

/**
 * Prints the latest updated albums
 *
 * @param string $number the number of albums to get
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 * 		"hitcounter" for showing the hitcounter (views),
 * 		"rating" for rating,
 * 		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $firstimglink 'false' (default) if the album thumb link should lead to the album page, 'true' if to the first image of theh album if the album itself has images
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics to include all subalbum levels
 */
function printLatestUpdatedAlbums($number = 5, $showtitle = false, $showdate = false, $showdesc = false, $desclength = 40, $showstatistic = '', $width = NULL, $height = NULL, $crop = NULL, $albumfolder = '', $firstimglink = false, $collection = false) {
	printAlbumStatistic($number, "latestupdated", $showtitle, $showdate, $showdesc, $desclength, $showstatistic, $width, $height, $crop, $albumfolder, $firstimglink, $collection);
}

/**
 * Returns an array of image objects according to $option
 *
 * NOTE: for performance reasons this function will NOT discover new images.
 *
 * @param string $number the number of images to get
 * @param string $option "popular" for the most popular images,
 * 		"popular" for the most popular albums,
 * 		"latest" for the latest uploaded by id (Discovery)
 * 		"latest-date" for the latest by date
 * 		"latest-mtime" for the latest by mtime
 *   	"latest-publishdate" for the latest by publishdate
 *    "mostrated" for the most voted,
 * 		"toprated" for the best voted
 * 		"latestupdated" for the latest updated
 * 		"random" for random order (yes, strictly no statistical order...)
 * @param string $albumfolder foldername of an specific album
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @param integer $threshold the minimum number of ratings (for rating options) or hits (for popular option) an image must have to be included in the list. (Default 0)
 * @param mixed $sortdirection "asc" for ascending order otherwise order is descending
 * @return array
 */
function getImageStatistic($number, $option, $albumfolder = NULL, $collection = false, $threshold = 0, $sortdirection = 'desc') {
	global $_gallery;
	$where = '';
	$obj = NULL;
	if ($albumfolder) {
		$obj = newAlbum($albumfolder);

		if ($collection) {
			$ids = getAllSubAlbumIDs($albumfolder);
			$ids[] = array('id' => $obj->getID());
		} else {
			$where = 'albumid = ' . $obj->getID();
		}
	} else if (!npg_loggedin()) {
		//	limit search to published albums
		$sql = 'SELECT `id` FROM ' . prefix('albums') . ' WHERE `show`=1';
		$ids = query_full_array($sql);
	}
	if (isset($ids) && !empty($ids)) {
		foreach ($ids as $id) {
			$getids[] = $id['id'];
		}
		$getids = implode(', ', $getids);
		$where = 'albumid IN (' . $getids . ')';
	}

	switch (strtolower($sortdirection)) {
		case false:
		case 'asc':
			$sortdir = 'ASC';
			break;
		default:
			$sortdir = 'DESC';
			break;
	}
	switch ($option) {
		case "popular":
			$sortorder = "hitcounter";
			$where .= ' AND hitcounter >= ' . $threshold;
			break;
		case "latest-date":
			$sortorder = "date";
			break;
		case "latest-mtime":
			$sortorder = "mtime";
			break;
		default:
		case "latest":
			$sortorder = "id";
			break;
		case "latest-publishdate":
			$sortorder = "IFNULL(publishdate,date)";
			break;
		case "mostrated":
			$sortorder = "total_votes";
			break;
		case "toprated":
			$sortorder = "(total_value/total_votes) DESC, total_value";
			$where .= ' AND total_votes >= ' . $threshold;
			break;
		case "random":
			$row = query_single_row('SELECT COUNT(*) FROM ' . prefix('images'));
			if ($row && 5000 < $count = reset($row)) {
				$sample = ceil((max(1000, $number * 100) / $count) * 100);
				$where .= ' AND CAST((RAND() * 100 * `id`) % 100 as UNSIGNED) < ' . $sample;
			}
			$sortorder = "RAND()";
			$sortdir = NULL;
			break;
	}
	if ($where) {
		$where = ' WHERE ' . ltrim($where, ' AND');
	}

	$imageArray = array();
	if ($obj && $obj->isDynamic()) {
		$sorttype = str_replace('images.', '', $sortorder);
		$images = $obj->getImages(0, 0, $sorttype, $sortdir);
		foreach ($images as $image) {
			$image = newImage($obj, $image);
			if ($image->checkAccess()) {
				$imageArray[] = $image;
				if (count($imageArray) >= $number) { // got enough
					break;
				}
			}
		}
	} else {
		$sql = "SELECT `id` FROM " . prefix('images') . $where . " ORDER BY " . $sortorder . " " . $sortdir;
		$result = query($sql);
		$imageArray = filterImageQueryList($result, $obj, $number, false);
	}
	return $imageArray;
}

/**
 * Prints image statistic according to $option as an unordered HTML list
 * A css id is attached by default named accordingly'$option'
 *
 * @param string $number the number of albums to get
 * @param string $option "popular" for the most popular images,
 * 		"popular" for the most popular albums,
 * 		"latest" for the latest uploaded by id (Discovery)
 * 		"latest-date" for the latest by date
 * 		"latest-mtime" for the latest by mtime
 *   	"latest-publishdate" for the latest by publishdate
 *    "mostrated" for the most voted,
 * 		"toprated" for the best voted
 * 		"latestupdated" for the latest updated
 * 		"random" for random order (yes, strictly no statistical order...)
 * @param string $albumfolder foldername of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic "hitcounter" for showing the hitcounter (views),
 * 		"rating" for rating,
 * 		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' if the thumb should be cropped, 'false' if not
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @param bool $fullimagelink 'false' (default) for the image page link , 'true' for the unprotected full image link (to use Colorbox for example)
 * @param integer $threshold the minimum number of ratings (for rating options) or hits (for popular option) an image must have to be included in the list. (Default 0)
 */
function printImageStatistic($number, $option, $albumfolder = NULL, $showtitle = false, $showdate = false, $showdesc = false, $desclength = 40, $showstatistic = '', $width = NULL, $height = NULL, $crop = NULL, $collection = false, $fullimagelink = false, $threshold = 0) {
	$images = getImageStatistic($number, $option, $albumfolder, $collection, $threshold);
	if (is_null($crop) && is_null($width) && is_null($height)) {
		$crop = 2;
	} else {
		if (is_null($width))
			$width = 85;
		if (is_null($height))
			$height = 85;
		if (is_null($crop)) {
			$crop = 1;
		} else {
			$crop = (int) $crop && true;
		}
	}
	echo "\n<div id=\"$option\">\n";
	echo "<ul>";
	foreach ($images as $image) {
		if ($fullimagelink) {
			$imagelink = $image->getFullImageURL();
		} else {
			$imagelink = $image->getLink();
		}
		echo '<li><a href="' . html_encode($imagelink) . '" title="' . html_encode($image->getTitle()) . "\">\n";
		switch ($crop) {
			case 0:
				$sizes = getSizeCustomImage(array('size' => $width), $image);
				$html = '<img src="' . html_encode($image->getCustomImage(array('size' => $width, 'height' => $height, 'cw' => $width, 'ch' => $height, 'thumb' => TRUE))) . '" width="' . $sizes[0] . '" height="' . $sizes[1] . '" alt="' . html_encode($image->getTitle()) . ' />';
				break;
			case 1:
				$sizes = getSizeCustomImage(array('width' => $width, 'height' => $height, 'cw' => $width, 'ch' => $height), $image);
				$html = '<img src="' . html_encode($image->getCustomImage(array('width' => $width, 'height' => $height, 'cw' => $width, 'ch' => $height, 'thumb' => TRUE))) . '" width="' . $sizes[0] . '" height="' . $sizes[1] . '" alt="' . html_encode($image->getTitle()) . ' />';
				break;
			default:
				$sizes = getSizeDefaultThumb($image);
				$html = '<img src="' . html_encode($image->getThumb()) . '" width="' . $sizes[0] . '" height="' . $sizes[1] . '" alt="' . html_encode($image->getTitle()) . ' />';
				break;
		}
		echo npgFilters::apply('custom_image_html', $html) . "\n</a>\n";

		if ($showtitle) {
			echo '<h3><a href="' . html_encode($image->getLink()) . '" title="' . html_encode($image->getTitle()) . "\">\n";
			echo $image->getTitle() . "</a></h3>\n";
		}
		if ($showdate) {
			echo "<p>" . formattedDate(DATE_FORMAT, strtotime($image->getDateTime())) . "</p>";
		}
		if ($showstatistic === "rating" OR $showstatistic === "rating+hitcounter") {
			$votes = $image->get("total_votes");
			$value = $image->get("total_value");
			if ($votes != 0) {
				$rating = round($value / $votes, 1);
			}
			echo "<p>" . sprintf(gettext('Rating: %1$u (Votes: %2$u)'), $rating, $votes) . "</p>";
		}
		if ($showstatistic === "hitcounter" OR $showstatistic === "rating+hitcounter") {
			$hitcounter = $image->getHitcounter();
			if (empty($hitcounter)) {
				$hitcounter = "0";
			}
			echo "<p>" . sprintf(gettext("Views: %u"), $hitcounter) . "</p>";
		}
		if ($showdesc) {
			echo html_encodeTagged(shortenContent($image->getDesc(), $desclength, ' (...)'));
		}
		echo "</li>";
	}
	echo "</ul></div>\n";
}

/**
 * Prints the most popular images
 *
 * @param string $number the number of images to get
 * @param string $albumfolder folder of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 * 		"hitcounter" for showing the hitcounter (views),
 * 		"rating" for rating,
 * 		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @param bool $fullimagelink 'false' (default) for the image page link , 'true' for the unprotected full image link (to use Colorbox for example)
 * @param integer $threshold the minimum number of ratings (for rating options) or hits (for popular option) an image must have to be included in the list. (Default 0)
 */
function printPopularImages($number = 5, $albumfolder = '', $showtitle = false, $showdate = false, $showdesc = false, $desclength = 40, $showstatistic = '', $width = NULL, $height = NULL, $crop = NULL, $collection = false, $fullimagelink = false, $threshold = 0) {
	printImageStatistic($number, "popular", $albumfolder, $showtitle, $showdate, $showdesc, $desclength, $showstatistic, $width, $height, $crop, $collection, $fullimagelink, $threshold);
}

/**
 * Prints the n top rated images
 *
 * @param int $number The number if images desired
 * @param string $albumfolder folder of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 * 		"hitcounter" for showing the hitcounter (views),
 * 		"rating" for rating,
 * 		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @param bool $fullimagelink 'false' (default) for the image page link , 'true' for the unprotected full image link (to use Colorbox for example)
 * @param integer $threshold the minimum number of ratings (for rating options) or hits (for popular option) an image must have to be included in the list. (Default 0)
 */
function printTopRatedImages($number = 5, $albumfolder = "", $showtitle = false, $showdate = false, $showdesc = false, $desclength = 40, $showstatistic = '', $width = NULL, $height = NULL, $crop = NULL, $collection = false, $fullimagelink = false, $threshold = 0) {
	printImageStatistic($number, "toprated", $albumfolder, $showtitle, $showdate, $showdesc, $desclength, $showstatistic, $width, $height, $crop, $collection, $fullimagelink, $threshold);
}

/**
 * Prints the n most rated images
 *
 * @param int $number The number if images desired
 * @param string $albumfolder folder of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 * 		"hitcounter" for showing the hitcounter (views),
 * 		"rating" for rating,
 * 		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @param bool $fullimagelink 'false' (default) for the image page link , 'true' for the unprotected full image link (to use Colorbox for example)
 * @param integer $threshold the minimum number of ratings (for rating options) or hits (for popular option) an image must have to be included in the list. (Default 0)
 */
function printMostRatedImages($number = 5, $albumfolder = '', $showtitle = false, $showdate = false, $showdesc = false, $desclength = 40, $showstatistic = '', $width = NULL, $height = NULL, $crop = NULL, $collection = false, $fullimagelink = false, $threshold = 0) {
	printImageStatistic($number, "mostrated", $albumfolder, $showtitle, $showdate, $showdesc, $desclength, $showstatistic, $width, $height, $crop, $collection, $fullimagelink, $threshold);
}

/**
 * Prints the latest images by ID (the order the images are recognized on the filesystem)
 *
 * @param string $number the number of images to get
 * @param string $albumfolder folder of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 * 		"hitcounter" for showing the hitcounter (views),
 * 		"rating" for rating,
 * 		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @param bool $fullimagelink 'false' (default) for the image page link , 'true' for the unprotected full image link (to use Colorbox for example)
 */
function printLatestImages($number = 5, $albumfolder = '', $showtitle = false, $showdate = false, $showdesc = false, $desclength = 40, $showstatistic = '', $width = NULL, $height = NULL, $crop = NULL, $collection = false, $fullimagelink = false) {
	printImageStatistic($number, "latest", $albumfolder, $showtitle, $showdate, $showdesc, $desclength, $showstatistic, $width, $height, $crop, $collection, $fullimagelink);
}

/**
 * Prints the latest images by date order (date taken order)
 *
 * @param string $number the number of images to get
 * @param string $albumfolder folder of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 * 		"hitcounter" for showing the hitcounter (views),
 * 		"rating" for rating,
 * 		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @param bool $fullimagelink 'false' (default) for the image page link , 'true' for the unprotected full image link (to use Colorbox for example)
 */
function printLatestImagesByDate($number = 5, $albumfolder = '', $showtitle = false, $showdate = false, $showdesc = false, $desclength = 40, $showstatistic = '', $width = NULL, $height = NULL, $crop = NULL, $collection = false, $fullimagelink = false) {
	printImageStatistic($number, "latest-date", $albumfolder, $showtitle, $showdate, $showdesc, $desclength, $showstatistic, $width, $height, $crop, $collection, $fullimagelink);
}

/**
 * Prints the latest images by mtime order (date uploaded order)
 *
 * @param string $number the number of images to get
 * @param string $albumfolder folder of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 * 		"hitcounter" for showing the hitcounter (views),
 * 		"rating" for rating,
 * 	"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @param bool $fullimagelink 'false' (default) for the image page link , 'true' for the unprotected full image link (to use Colorbox for example)
 */
function printLatestImagesByMtime($number = 5, $albumfolder = '', $showtitle = false, $showdate = false, $showdesc = false, $desclength = 40, $showstatistic = '', $width = NULL, $height = NULL, $crop = NULL, $collection = false, $fullimagelink = false) {
	printImageStatistic($number, "latest-mtime", $albumfolder, $showtitle, $showdate, $showdesc, $desclength, $showstatistic, $width, $height, $crop, $collection, $fullimagelink);
}

/**
 * A little helper function that checks if an image or album is to be considered 'new' within the time range set in relation to getImageDate()/getAlbumDate()
 * Returns true or false.
 *
 * @param string $mode What to check "image" or "album".
 * @param integer $timerange The time range the item should be considered new. Default is 604800 (unix time seconds = ca. 7 days)
 * @return bool
 */
function checkIfNew($mode = "image", $timerange = 604800) {
	$currentdate = date("U");
	switch ($mode) {
		case "image":
			$itemdate = getImageDate('u');
			break;
		case "album":
			$itemdate = getAlbumDate('u');
			break;
	}
	$newcheck = $currentdate - $itemdate;
	if ($newcheck < $timerange) {
		return TRUE;
	} else {
		return FALSE;
	}
}

/**
 * Gets the number of all subalbums of all subalbum levels of either the current album or $albumobj
 *
 * @param object $albumobj Optional album object to check
 * @param string $pre Optional text you want to print before the number
 * @return bool
 */
function getNumAllSubalbums($albumobj, $pre = '') {
	global $_gallery, $_current_album;
	if (is_null($albumobj)) {
		$albumobj = $_current_album;
	}
	$count = '';
	$albums = getAllAlbums($_current_album);
	if (count($albums) != 0) {
		$count = '';
		foreach ($albums as $album) {
			$count++;
		}
		return $pre . $count;
	} else {
		return false;
	}
}

?>