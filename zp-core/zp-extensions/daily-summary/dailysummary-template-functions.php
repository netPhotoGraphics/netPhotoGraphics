<?php

/**
 * Daily Summary template functions
 *
 * @author Marcus Wong (wongm) with updates by Stephen Billard
 * @package plugins/daily-summary
 */

/**
 *
 * @global type $_zp_current_DailySummaryItem
 * @param type $format
 * @return boolean
 */
function getDailySummaryDate($format = null) {
	global $_zp_current_DailySummaryItem;
	$d = $_zp_current_DailySummaryItem->getDateTime();
	if (empty($d) || ($d == '0000-00-00 00:00:00')) {
		return false;
	}
	if (is_null($format)) {
		return $d;
	}
	return zpFormattedDate($format, strtotime($d));
}

function getDailySummaryModifiedDate($format = null) {
	global $_zp_current_DailySummaryItem;
	$d = $_zp_current_DailySummaryItem->getModifedDateTime();
	if (empty($d) || ($d == '0000-00-00 00:00:00')) {
		return false;
	}
	if (is_null($format)) {
		return $d;
	}
	return zpFormattedDate($format, strtotime($d));
}

function getDailySummaryUrl() {
	global $_zp_current_DailySummaryItem;
	return $_zp_current_DailySummaryItem->getLink();
}

function getDailySummaryTitle() {
	global $_zp_current_DailySummaryItem;
	$count = $_zp_current_DailySummaryItem->getNumImages();
	return sprintf(ngettext('%1$s - 1 new photo', '%1$s - %2$s new photos', $count), date("l, F j Y", strtotime($_zp_current_DailySummaryItem->getDateTime())), $count);
}

function printDailySummaryUrl($text, $title, $class = NULL, $id = NULL) {
	printLinkHTML(getDailySummaryUrl(), $text, $title, $class, $id);
}

function getDailySummaryDesc() {
	global $_zp_current_DailySummaryItem;
	$count = $_zp_current_DailySummaryItem->getNumImages();
	$albums = getDailySummaryAlbumNameText();
	if ($_zp_current_DailySummaryItem->getNumAlbums() > 1) {
		return sprintf(ngettext('New photo in the %1$s albums.', 'New photos in the %1$s albums', $count), $albums);
	} else {
		return sprintf(ngettext('New photo in the %1$s album.', 'New photos in the %1$s album', $count), $albums);
	}
}

function getDailySummaryNumImages() {
	global $_zp_current_DailySummaryItem;
	return $_zp_current_DailySummaryItem->getNumImages();
}

function printDailySummaryAlbumNameList($includeLinks = false, $listType = "ul") {
	global $_zp_current_DailySummaryItem;
	$albums = $_zp_current_DailySummaryItem->getAlbumsArray();

	if (count($albums) == 0) {
		return;
	}

	echo "<$listType class=\"DailySummaryAlbumList\">";
	foreach ($albums as $folder => $albumtitle) {
		if ($includeLinks) {
			$rewrite = pathurlencode($folder) . '/';
			$plain = '/index.php?album=' . pathurlencode($folder);
			$albumtitle = "<a href=\"" . rewrite_path($rewrite, $plain) . "\">$albumtitle</a>";
		}

		echo "<li>$albumtitle</li>";
	}
	echo "</$listType>";
}

function getDailySummaryAlbumNameText($includeLinks = false) {
	global $_zp_current_DailySummaryItem;

	$albumcount = 1;
	$description = "";
	$albums = $_zp_current_DailySummaryItem->getAlbumsArray();

	foreach ($albums as $folder => $albumtitle) {
		if ($albumcount == count($albums) AND $albumcount > 1) {
			$description .= " and ";
		} else if ($albumcount > 1) {
			$description .= ", ";
		}

		if ($includeLinks) {
			$rewrite = pathurlencode($folder) . '/';
			$plain = '/index.php?album=' . pathurlencode($folder);
			$albumtitle = "<a href=\"" . rewrite_path($rewrite, $plain) . "\">$albumtitle</a>";
		}

		$description .= '<em>' . $albumtitle . '</em>';
		$albumcount++;
	}
	return $description;
}

function getCustomDailySummaryThumb($size, $width = NULL, $height = NULL, $cropw = NULL, $croph = NULL, $cropx = NULL, $cropy = null, $effects = NULL) {
	global $_zp_current_DailySummaryItem;
	$thumb = $_zp_current_DailySummaryItem->getDailySummaryThumbImage();
	return $thumb->getCustomImage($size, $width, $height, $cropw, $croph, $cropx, $cropy, true, $effects);
}

function next_DailySummaryItem($all = false) {
	global $_zp_DailySummaryItems, $_zp_current_DailySummaryItem, $_zp_current_DailySummaryItem_restore, $_zp_page, $_zp_current_DailySummary;

	if (is_null($_zp_DailySummaryItems)) {
		$_zp_DailySummaryItems = $_zp_current_DailySummary->getAlbums($all ? 0 : $_zp_page);
		$_zp_current_DailySummaryItem_restore = $_zp_current_DailySummaryItem;
		save_context();
		add_context(ZP_ALBUM);
	}
	if (empty($_zp_DailySummaryItems)) {
		$_zp_DailySummaryItems = NULL;
		$_zp_current_DailySummaryItem = $_zp_current_DailySummaryItem_restore;
		restore_context();
		return false;
	} else {
		$_zp_current_DailySummaryItem = new DailySummaryItem(array_shift($_zp_DailySummaryItems));
		return true;
	}
}

function getDailySummaryTitleAndDesc() {
	$count = $_zp_current_DailySummaryItem->getNumImages();
	$albums = getDailySummaryAlbumNameText();
	if ($_zp_current_DailySummaryItem->getNumAlbums() > 1) {
		return sprintf(ngettext('%1$s - 1 new photo in the %1$s albums.', '%1$s - %2$s new photos in the %1$s albums', $count), $albums);
	} else {
		return sprintf(ngettext('%1$s - 1 new photo in the %1$s album.', '%1$s - %2$s new photos in the %1$s album', $count), $albums);
	}
}

/**
 * Prints the full news page navigation with prev/next links and the page number list
 *
 * @param string $next The next page link text
 * @param string $prev The prev page link text
 * @param bool $nextprev If the prev/next links should be printed
 * @param string $class The CSS class for the disabled link
 * @param bool $firstlast Add links to the first and last pages of you gallery
 * @param int $navlen Number of navigation links to show (0 for all pages). Works best if the number is odd.
 *
 * @return string
 */
function printDailySummaryPageListWithNav($next, $prev, $nextprev = true, $class = 'pagelist', $firstlast = true, $navlen = 9) {
	global $_zp_current_DailySummary, $_zp_page;
	$total = ceil($_zp_current_DailySummary->getTotalItems() / getOption('DailySummaryItemsPage'));
	if ($total > 1) {
		if ($navlen == 0)
			$navlen = $total;
		$extralinks = 2;
		if ($firstlast)
			$extralinks = $extralinks + 2;
		$len = floor(($navlen - $extralinks) / 2);
		$j = max(round($extralinks / 2), min($_zp_page - $len - (2 - round($extralinks / 2)), $total - $navlen + $extralinks - 1));
		$ilim = min($total, max($navlen - round($extralinks / 2), $_zp_page + floor($len)));
		$k1 = round(($j - 2) / 2) + 1;
		$k2 = $total - round(($total - $ilim) / 2);
		echo "<ul class=\"$class\">\n";
		if ($nextprev) {
			echo "<li class=\"prev\">";
			if ($_zp_page > 1) {
				$i = $_zp_page - 1;
				echo '<a href = "' . html_encode(getCustomPageURL('daily-summary', '', $i)) . '" title = "' . sprintf(ngettext('Page %1$u', 'Page %1$u', $i), $i) . '">' . $prev . '</a>';
			} else {
				echo "<span class=\"disabledlink\">" . html_encode($next) . "</span>\n";
			}
			echo "</li>\n";
		}
		if ($firstlast) {
			echo '<li class = "' . ($_zp_page == 1 ? 'current' : 'first') . '">';
			if ($_zp_page == 1) {
				echo "1";
			} else {
				echo '<a href = "' . html_encode(getCustomPageURL('daily-summary', '', 1)) . '" title = "' . gettext("Page") . ' 1">1</a>';
			}
			echo "</li>\n";
			if ($j > 2) {
				echo "<li>";
				$linktext = ($j - 1 > 2) ? '...' : $k1;
				echo '<a href = "' . html_encode(getCustomPageURL('daily-summary', '', $k1)) . '" title = "' . sprintf(ngettext('Page %u', 'Page %u', $k1), $k1) . '">' . $linktext . '</a>';
				echo "</li>\n";
			}
		}
		for ($i = $j; $i <= $ilim; $i++) {
			echo "<li" . (($i == $_zp_page) ? " class=\"current\"" : "") . ">";
			if ($i == $_zp_page) {
				echo $i;
			} else {
				echo '<a href = "' . html_encode(getCustomPageURL('daily-summary', '', $i)) . '" title = "' . sprintf(ngettext('Page %1$u', 'Page %1$u', $i), $i) . '">' . $i . '</a>';
			}
			echo "</li>\n";
		}
		if ($i < $total) {
			echo "<li>";
			$linktext = ($total - $i > 1) ? '...' : $k2;
			echo '<a href = "' . html_encode(getCustomPageURL('daily-summary', '', $k2)) . '" title = "' . sprintf(ngettext('Page %u', 'Page %u', $k2), $k2) . '">' . $linktext . '</a>';
			echo "</li>\n";
		}
		if ($firstlast && $i <= $total) {
			echo "\n  <li class=\"last\">";
			if ($_zp_page == $total) {
				echo $total;
			} else {
				echo '<a href = "' . html_encode(getCustomPageURL('daily-summary', '', $total)) . '" title = "' . sprintf(ngettext('Page {%u}', 'Page {%u}', $total), $total) . '">' . $total . '</a>';
			}
			echo "</li>\n";
		}
		if ($nextprev) {
			echo '<li class = "next">';
			if ($_zp_page < $total) {
				$i = $_zp_page + 1;
				echo '<a href = "' . html_encode(getCustomPageURL('daily-summary', '', $i)) . '" title = "' . sprintf(ngettext('Page %1$u', 'Page %1$u', $i), $i) . '">' . $next . '</a>';
			} else {
				echo "<span class=\"disabledlink\">" . html_encode($next) . "</span>\n";
			}
			echo "</li>\n";
		}
		echo "</ul>\n";
	}
}

?>
