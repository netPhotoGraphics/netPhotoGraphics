<?php

/**
 * Daily Summary items class
 *
 * @author Marcus Wong (wongm) with updates by Stephen Billard
 * @package plugins/daily-summary
 */
class DailySummaryItem extends Album {

	/**
	 *
	 * @param type $dateValue date of the summary
	 * @param type $cache ignored
	 * @param type $quiet ignored
	 */
	function __construct($dateValue, $cache = true, $quiet = false) {

		$imageAlbums = array();

		$this->linkname = $dateValue;

		$d1 = $dateValue . " 00:00:00";
		$d2 = $dateValue . " 23:59:59";

		$imageSql = "SELECT i.filename, i.date, i.mtime, i.title AS thumbtitle, a.folder AS folder, a.title AS albumtitle, a.show AS album_show, a.dynamic AS album_dynamic
			FROM " . prefix('images') . " AS i
			INNER JOIN " . prefix('albums') . " AS a ON i.albumid = a.id
			WHERE i.`date` >= \"$d1\" AND i.`date` < \"$d2\"
			ORDER BY i.date DESC";
		$results = query_full_array($imageSql);

		if (sizeof($results) == 0) {
			$this->set('albums', array());
			return;
		}
		$count = 0;
		foreach ($results as $album) {
			$albumobj = newAlbum($album['folder'], false);
			if ($albumobj && $albumobj->checkAccess()) {
				if (empty($this->date)) {
					$this->set('date', dateTimeConvert($album['date']));
					$this->set('mtime', strftime('%Y-%m-%d %H:%M:%S', $album['mtime']));
					$this->set('thumbfolder', $album['folder']);
					$this->set('thumbimage', $album['filename']);
					$this->set('thumbtitle', $album['thumbtitle']);
				}
				$count++;
				$folder = $album['folder'];
				$text = $album['albumtitle'];
				$text = get_language_string($text);
				$text = npgFunctions::unTagURLs($text);
				$imageAlbums[$folder] = $text;
			}
		}

		if ($albumCount = count($imageAlbums)) {
			ksort($imageAlbums, SORT_NATURAL | SORT_FLAG_CASE);
		}

		$this->set('albums', $imageAlbums);
		$this->set('imagecount', $count);
		$this->set('albumcount', $albumCount);
	}

	function getNumImages() {
		return $this->get('imagecount');
	}

	function getNumAlbums() {
		return $this->get('albumcount');
	}

	function getAlbumNames() {
		return array_values($this->get('albums'));
	}

	function getAlbumsArray() {
		return $this->get('albums');
	}

	// overloaded functions inherited from Album
	// these ones do stuff
	function getAlbums($page = 0, $sorttype = NULL, $direction = NULL, $care = true, $mine = NULL) {
		return array_keys($this->get('albums'));
	}

	function getDateTime() {
		return ($this->get('date'));
	}

	function getModifedDateTime() {
		return ($this->get('mtime'));
	}

	function getLink($page = NULL) {
		return getSearchURL('', $this->linkname, '', 0, NULL);
	}

	function getDailySummaryThumbImage() {
		if (!is_null($this->albumthumbnail)) {
			return $this->albumthumbnail;
		}
		$this->albumthumbnail = newImage(newAlbum($this->get('thumbfolder')), $this->get('thumbimage'));
		return $this->albumthumbnail;
	}

	// don't want these ones to do anything
	function save() {
		return 2; //	nohing changed
	}

}

?>