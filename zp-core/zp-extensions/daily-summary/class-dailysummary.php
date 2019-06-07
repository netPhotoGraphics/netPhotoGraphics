<?php

/**
 * Daily Summary base class
 *
 * @author Marcus Wong (wongm) with updates by Stephen Billard
 * @package plugins/daily-summary
 */
class DailySummary extends Gallery {

	var $imagaecount;

	protected function loadAlbumNames() {
		$minDate = floor(strtotime('-' . getOption('DailySummaryDays') . ' days') / 86400) * 86400;
		$cleandates = array();
		$sql = "SELECT `id`,`date` FROM " . prefix('images');
		if (!npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS | VIEW_UNPUBLISHED_RIGHTS)) {
			$sql .= " WHERE `show`=1";
		}
		$hidealbums = getNotViewableAlbums();
		if (!empty($hidealbums)) {
			if (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS | VIEW_UNPUBLISHED_RIGHTS)) {
				$sql .= ' WHERE ';
			} else {
				$sql .= ' AND ';
			}
			$sql .= '`albumid` NOT IN (' . implode(',', $hidealbums) . ')';
		}
		$sql .=' ORDER BY `date` DESC';
		$result = query($sql);
		while ($row = db_fetch_assoc($result)) {
			if (!empty($row['date'])) {
				$image = getItemByID('images', $row['id'], true, false);
				if ($image) {
					$d = substr($row['date'], 0, 10);
					$rowDate = strtotime($d);
					if ($rowDate < $minDate) {
						break;
					}
					$cleandates[] = $d;
				}
			}
		}
		db_free_result($result);
		$this->imagaecount = count($cleandates);
		if ($datecount = array_count_values($cleandates)) {
			krsort($datecount);
		}
		$items = array_keys($datecount);

		return $items;
	}

	function getAlbums($page = 0, $sorttype = NULL, $direction = NULL, $care = true, $mine = NULL) {
		if (is_null($this->albums)) {
			$this->albums = $this->loadAlbumNames();
		}
		if ($page == 0) {
			return $this->albums;
		} else {
			$items_per_page = max(1, getOption('DailySummaryItemsPage'));
			return array_slice($this->albums, $items_per_page * ($page - 1), $items_per_page);
		}
	}

	function getTotalImages() {
		return $this->imagaecount;
	}

	function getTotalItems() {
		return count($this->getAlbums(0));
	}

	static function pageCount($count, $gallery_page, $page) {
		global $_firstPageImages, $_current_DailySummary;
		$items_per_page = max(1, getOption('DailySummaryItemsPage'));
		return (int) ceil($_current_DailySummary->getTotalItems() / $items_per_page);
	}

}

?>