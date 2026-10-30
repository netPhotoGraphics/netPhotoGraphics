<?php
/**
 * Data quality report (admin page of the quality_check plugin). Read-only.
 *
 * @package plugins/quality_check
 */
define('OFFSET_PATH', 4);
require_once(dirname(dirname(__DIR__)) . '/admin-globals.php');

admin_securityChecks(ADMIN_RIGHTS, currentRelativeURL());

$perPage = 100;
$distances = array(25, 50, 100, 300, 1000); //	km for the "far from the rest of the album" check
$km = isset($_GET['km']) ? (int) $_GET['km'] : 100;
if (!in_array($km, $distances)) {
	$km = 100;
}

$yearChoices = array(1, 2, 5, 10); //	album check: photos span at least N years (1 = any two different years)
$yrs = isset($_GET['yrs']) ? (int) $_GET['yrs'] : 1;
if (!in_array($yrs, $yearChoices)) {
	$yrs = 1;
}
$pctChoices = array(50, 60, 70, 80, 90, 100); //	album check: share of untitled photos
$pct = isset($_GET['pct']) ? (int) $_GET['pct'] : 90;
if (!in_array($pct, $pctChoices)) {
	$pct = 90;
}

$I = prefix('images');
$A = prefix('albums');
$T = prefix('obj_to_tag');

/* SQL fragments --------------------------------------------------------- */
$LAT = "CAST(REPLACE(i.`gpslatitude`, ',', '.') AS DECIMAL(10,6))";
$LON = "CAST(REPLACE(i.`gpslongitude`, ',', '.') AS DECIMAL(10,6))";
$hasLat = "(i.`gpslatitude` IS NOT NULL AND TRIM(i.`gpslatitude`) <> '' AND IFNULL($LAT, 0) <> 0)";
$hasLon = "(i.`gpslongitude` IS NOT NULL AND TRIM(i.`gpslongitude`) <> '' AND IFNULL($LON, 0) <> 0)";
$anyLat = "(i.`gpslatitude` IS NOT NULL AND TRIM(i.`gpslatitude`) <> '')";
$anyLon = "(i.`gpslongitude` IS NOT NULL AND TRIM(i.`gpslongitude`) <> '')";
$baseName = "LEFT(i.`filename`, GREATEST(CHAR_LENGTH(i.`filename`) - CHAR_LENGTH(SUBSTRING_INDEX(i.`filename`, '.', -1)) - 1, 0))";

$imgFrom = "FROM $I AS i JOIN $A AS a ON a.`id` = i.`albumid`";
$imgCols = "i.`id` AS id, i.`filename` AS filename, i.`title` AS title, i.`date` AS date, i.`gpslatitude` AS lat, i.`gpslongitude` AS lon, a.`folder` AS folder";

/* coordinate expressions for any alias of the images table */

function qc_lat($al) {
	return "CAST(REPLACE($al.`gpslatitude`, ',', '.') AS DECIMAL(10,6))";
}

function qc_lon($al) {
	return "CAST(REPLACE($al.`gpslongitude`, ',', '.') AS DECIMAL(10,6))";
}

function qc_has($al) {
	return "($al.`gpslatitude` IS NOT NULL AND TRIM($al.`gpslatitude`) <> '' AND IFNULL(" . qc_lat($al) . ", 0) <> 0 AND $al.`gpslongitude` IS NOT NULL AND TRIM($al.`gpslongitude`) <> '' AND IFNULL(" . qc_lon($al) . ", 0) <> 0)";
}

/* base name (file name without extension) of any alias of the images table */

function qc_base($al) {
	return "LEFT($al.`filename`, GREATEST(CHAR_LENGTH($al.`filename`) - CHAR_LENGTH(SUBSTRING_INDEX($al.`filename`, '.', -1)) - 1, 0))";
}

/* photo without a real title: empty, or the same as the file name */

function qc_untitled($al) {
	$b = qc_base($al);
	/* the title may also be stored as a one-language array: a:1:{s:5:"cs_CZ";s:8:"IMG_0001";} */
	return "($al.`title` IS NULL OR TRIM($al.`title`) = '' OR $al.`title` = $b OR $al.`title` = $al.`filename`" .
					" OR ($al.`title` LIKE 'a:1:{%' AND ($al.`title` LIKE '%\"\";}' OR $al.`title` LIKE CONCAT('%\"', $b, '\";}') OR $al.`title` LIKE CONCAT('%\"', $al.`filename`, '\";}'))))";
}

/* photo with a usable date */

function qc_dated($al) {
	return "($al.`date` IS NOT NULL AND $al.`date` >= '1850-01-01' AND $al.`date` <= DATE_ADD(NOW(), INTERVAL 1 YEAR))";
}

$untitledCnt = "(SELECT COUNT(*) FROM $I AS x WHERE x.`albumid` = a.`id` AND " . qc_untitled('x') . ")";
$totalCnt = "(SELECT COUNT(*) FROM $I AS x WHERE x.`albumid` = a.`id`)";
$yearMin = "(SELECT MIN(YEAR(x.`date`)) FROM $I AS x WHERE x.`albumid` = a.`id` AND " . qc_dated('x') . ")";
$yearMax = "(SELECT MAX(YEAR(x.`date`)) FROM $I AS x WHERE x.`albumid` = a.`id` AND " . qc_dated('x') . ")";

/* distance in km between photo i and photo h (flat approximation, good enough for a 25-1000 km limit) */
$dist = '111.2 * SQRT(POW(' . qc_lat('h') . ' - ' . qc_lat('i') . ', 2) + POW((' . qc_lon('h') . ' - ' . qc_lon('i') . ') * COS(RADIANS(' . qc_lat('i') . ')), 2))';
$sameAlbum = "h.`albumid` = i.`albumid` AND h.`id` <> i.`id` AND " . qc_has('h');
$farWhere = qc_has('i') . " AND (SELECT COUNT(*) FROM $I AS x WHERE x.`albumid` = i.`albumid` AND " . qc_has('x') . ") >= 3" .
				" AND NOT EXISTS (SELECT 1 FROM $I AS h WHERE $sameAlbum AND $dist <= " . (int) $km . ")";
$farExtra = array('km' => "(SELECT ROUND(MIN($dist)) FROM $I AS h WHERE $sameAlbum)");

/**
 * check definitions: key => array(group, label, help, where (images/albums), type, from, cols, extra)
 */
$checks = array(
		'gps_half' => array('image', gettext('GPS: only one coordinate'),
				gettext('Latitude or longitude is empty or zero while the other one is filled in. Such a photo would land in a wrong place (e.g. in Africa) and is not shown by the theme or plugin maps.'),
				"($hasLat AND NOT $hasLon) OR ($hasLon AND NOT $hasLat)"),
		'gps_zero' => array('image', gettext('GPS: coordinates 0, 0'),
				gettext('Both coordinates are present but zero (the camera had no GPS fix).'),
				"$anyLat AND $anyLon AND NOT $hasLat AND NOT $hasLon"),
		'gps_range' => array('image', gettext('GPS: out of range'),
				gettext('Latitude outside -90 to 90 or longitude outside -180 to 180.'),
				"$hasLat AND $hasLon AND (ABS($LAT) > 90 OR ABS($LON) > 180)"),
		'gps_far' => array('image', sprintf(gettext('GPS: no other photo of the album within %d km'), $km),
				gettext('The photo is far from all other geotagged photos of its album (albums with at least 3 geotagged photos). Typical for a wrong coordinate or a photo placed in the wrong album.'),
				$farWhere, $farExtra),
		'no_date' => array('image', gettext('Photos without a usable date'),
				gettext('The date is empty, 0000-00-00 or outside the years 1850 - next year. Scanned photos often have no date; the date is needed for sorting and the archive.'),
				"(i.`date` IS NULL OR i.`date` < '1850-01-01' OR i.`date` > DATE_ADD(NOW(), INTERVAL 1 YEAR))"),
		'no_tags' => array('image', gettext('Photos without tags'),
				gettext('The photo has no tag at all.'),
				"NOT EXISTS (SELECT 1 FROM $T AS t WHERE t.`type` = 'images' AND t.`objectid` = i.`id`)"),
		'hidden_img' => array('image', gettext('Hidden (not published) photos'),
				gettext('Photos that visitors cannot see.'),
				"i.`show` = 0"),
		'alb_no_desc' => array('album', gettext('Albums without a description'),
				gettext('The description of the album is empty.'),
				"(a.`desc` IS NULL OR TRIM(a.`desc`) = '')"),
		'alb_untitled' => array('album', sprintf(gettext('Albums where at least %d %% of photos have no title'), $pct),
				gettext('A photo counts as untitled when its title is empty or the same as its file name. Typical for freshly uploaded scans that still wait for captions.'),
				"$totalCnt > 0 AND $untitledCnt * 100 >= " . (int) $pct . " * $totalCnt", array('cnt' => $untitledCnt, 'total' => $totalCnt)),
		'alb_no_gps' => array('album', gettext('Albums where no photo has GPS coordinates'),
				gettext('The album contains photos, but none of them has both coordinates. Sorted by the number of photos, so the biggest gaps come first - a good place to add coordinates in bulk.'),
				"$totalCnt > 0 AND NOT EXISTS (SELECT 1 FROM $I AS i WHERE i.`albumid` = a.`id` AND " . qc_has('i') . ")", array('total' => $totalCnt), "total DESC, a.`folder`"),
		'alb_empty' => array('album', gettext('Empty albums'),
				gettext('No photos and no sub-albums in the database.'),
				"NOT EXISTS (SELECT 1 FROM $I AS x WHERE x.`albumid` = a.`id`) AND NOT EXISTS (SELECT 1 FROM $A AS y WHERE y.`parentid` = a.`id`)"),
		'alb_hidden' => array('album', gettext('Hidden (not published) albums'),
				gettext('Albums that visitors cannot see.'),
				"a.`show` = 0"),
);

/* build count / list queries --------------------------------------------- */

function qc_sql($key, $def, $mode, $limit = 0, $offset = 0) {
	global $imgFrom, $imgCols, $A;
	$where = $def[3];
	$extra = '';
	if (isset($def[4])) {
		foreach ($def[4] as $alias => $expr) {
			$extra .= ", $expr AS $alias";
		}
	}
	if ($def[0] == 'image') {
		return ($mode == 'count') ? "SELECT COUNT(*) AS n $imgFrom WHERE $where" : "SELECT $imgCols$extra $imgFrom WHERE $where ORDER BY a.`folder`, i.`filename` LIMIT $offset, $limit";
	}
	return ($mode == 'count') ? "SELECT COUNT(*) AS n FROM $A AS a WHERE $where" : "SELECT a.`id` AS id, a.`folder` AS folder, a.`title` AS title$extra FROM $A AS a WHERE $where ORDER BY " . (isset($def[5]) ? $def[5] : "a.`folder`") . " LIMIT $offset, $limit";
}

/* titles of multilingual galleries are stored as serialized arrays: pick the text in the current language */

function qc_text($v) {
	$v = (string) $v;
	if (function_exists('get_language_string')) {
		$v = (string) get_language_string($v);
	}
	return getBare($v);
}

function qc_url($params = array()) {
	global $km, $yrs, $pct;
	$params = array_merge(array('page' => 'overview', 'tab' => 'quality', 'km' => $km, 'yrs' => $yrs, 'pct' => $pct), $params);
	return getAdminLink(PLUGIN_FOLDER . '/quality_check/report.php') . '?' . http_build_query($params);
}

/* page ------------------------------------------------------------------- */
$selected = (isset($_GET['check']) && isset($checks[$_GET['check']])) ? $_GET['check'] : NULL;
$pageNo = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;

printAdminHeader('overview', 'quality');
?>
<style>
	.qc table { border-collapse: collapse; width: 100%; margin: 1em 0; }
	.qc th, .qc td { text-align: left; padding: 5px 10px; border-bottom: 1px solid #ddd; vertical-align: top; }
	.qc .n { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
	.qc table.checks td.n { width: 6.5rem; padding-right: 1.75rem; }
	.qc .ok { color: #2e7d32; }
	.qc .warn { color: #b3261e; font-weight: bold; }
	.qc .help { color: #666; font-size: .9em; }
	.qc h2 { margin-top: 1.5em; }
	.qc .pager a, .qc .pager span { margin-right: 8px; }
</style>
<?php echo '</head>'; ?>
<body>
	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<h1><?php echo gettext('Data quality'); ?></h1>
			<div class="tabbox qc">
				<?php
				$started = microtime(true);
				if ($selected) {
					$def = $checks[$selected];
					$total = (int) (query_single_row(qc_sql($selected, $def, 'count'))['n'] ?? 0);
					$pages = max(1, (int) ceil($total / $perPage));
					$pageNo = min($pageNo, $pages);
					?>
					<p><a href="<?php echo html_encode(qc_url()); ?>">&laquo; <?php echo gettext('Back to the overview'); ?></a></p>
					<h2><?php echo html_encode($def[1]); ?> (<?php echo number_format($total, 0, ',', "\xC2\xA0"); ?>)</h2>
					<p class="help"><?php echo html_encode($def[2]); ?></p>
					<?php if ($selected == 'gps_far') { ?>
						<p><?php echo gettext('Distance limit'); ?>:
							<?php foreach ($distances as $d) { ?>
								<?php echo $d == $km ? '<strong>' . $d . ' km</strong>' : '<a href="' . html_encode(qc_url(array('check' => $selected, 'km' => $d))) . '">' . $d . ' km</a>'; ?>
							<?php } ?>
						</p>
					<?php } ?>
					<?php if (false) { ?>
						<p><?php echo gettext('Year span at least'); ?>:
							<?php foreach ($yearChoices as $d) { ?>
								<?php echo $d == $yrs ? '<strong>' . $d . '</strong>' : '<a href="' . html_encode(qc_url(array('check' => $selected, 'yrs' => $d))) . '">' . $d . '</a>'; ?>
							<?php } ?>
						</p>
					<?php } ?>
					<?php if ($selected == 'alb_untitled') { ?>
						<p><?php echo gettext('Share of untitled photos at least'); ?>:
							<?php foreach ($pctChoices as $d) { ?>
								<?php echo $d == $pct ? '<strong>' . $d . ' %</strong>' : '<a href="' . html_encode(qc_url(array('check' => $selected, 'pct' => $d))) . '">' . $d . ' %</a>'; ?>
							<?php } ?>
						</p>
					<?php } ?>
					<?php
					$rows = $total ? query_full_array(qc_sql($selected, $def, 'list', $perPage, ($pageNo - 1) * $perPage)) : array();
					if ($rows) {
						?>
						<table>
							<tr>
								<th><?php echo $def[0] == 'album' ? gettext('Album') : gettext('Photo'); ?></th>
								<?php if ($def[0] != 'album') { ?><th><?php echo gettext('Album'); ?></th><?php } ?>
								<?php if ($def[0] != 'album' || isset($def[4])) { ?><th><?php echo gettext('Data'); ?></th><?php } ?>
								<th></th>
							</tr>
							<?php
							foreach ($rows as $r) {
								$editAlbum = getAdminLink('admin-tabs/edit.php') . '?page=edit&album=' . pathurlencode($r['folder']);
								if ($def[0] == 'album') {
									$edit = $editAlbum;
									$data = '';
									if ($selected == 'alb_years') {
										$data = (int) $r['ymin'] . ' - ' . (int) $r['ymax'];
									} else if ($selected == 'alb_no_gps') {
										$data = sprintf(gettext('%d photos'), (int) $r['total']);
									} else if ($selected == 'alb_untitled') {
										$data = sprintf(gettext('%1$d of %2$d photos without a title (%3$d %%)'), (int) $r['cnt'], (int) $r['total'], round(100 * $r['cnt'] / max(1, $r['total'])));
									}
									?>
									<tr>
										<td><?php echo html_encode($r['folder']); ?><?php echo $r['title'] ? ' &ndash; ' . html_encode(qc_text($r['title'])) : ''; ?></td>
										<?php if (isset($def[4])) { ?><td><?php echo html_encode($data); ?></td><?php } ?>
										<td class="n"><a href="<?php echo html_encode($edit); ?>"><?php echo gettext('Edit'); ?></a></td>
									</tr>
									<?php
								} else {
									$edit = $editAlbum . '&singleimage=' . urlencode($r['filename']);
									if (strpos($selected, 'gps') === 0) {
										$data = 'GPS: ' . trim($r['lat']) . ' ; ' . trim($r['lon']);
										if (isset($r['km'])) {
											$data .= ' (' . sprintf(gettext('nearest photo of the album: %d km'), (int) $r['km']) . ')';
										}
									} else if ($selected == 'no_date') {
										$data = (string) $r['date'];
									} else {
										$data = qc_text($r['title']);
									}
									?>
									<tr>
										<td><?php echo html_encode($r['filename']); ?></td>
										<td><?php echo html_encode($r['folder']); ?></td>
										<td><?php echo html_encode($data); ?></td>
										<td class="n"><a href="<?php echo html_encode($edit); ?>"><?php echo gettext('Edit'); ?></a></td>
									</tr>
									<?php
								}
							}
							?>
						</table>
						<p class="pager">
							<?php
							if ($pages > 1) {
								printf(gettext('Page %1$d of %2$d'), $pageNo, $pages);
								echo ' &nbsp; ';
								if ($pageNo > 1) {
									echo '<a href="' . html_encode(qc_url(array('check' => $selected, 'p' => $pageNo - 1))) . '">&laquo; ' . gettext('previous') . '</a> ';
								}
								if ($pageNo < $pages) {
									echo '<a href="' . html_encode(qc_url(array('check' => $selected, 'p' => $pageNo + 1))) . '">' . gettext('next') . ' &raquo;</a>';
								}
							}
							?>
						</p>
					<?php } else { ?>
						<p class="ok"><?php echo gettext('Nothing found. All good.'); ?></p>
					<?php } ?>
					<?php
				} else {
					$groups = array('image' => gettext('Photos'), 'album' => gettext('Albums'));
					?>
					<p class="help"><?php echo gettext('Read-only report. Nothing is changed in the database. Click a number to see the list with links to edit each item.'); ?></p>
					<?php
					foreach (array('image' => array('image'), 'album' => array('album')) as $group => $kinds) {
						?>
						<h2><?php echo html_encode($groups[$group]); ?></h2>
						<table class="checks">
							<?php
							foreach ($checks as $key => $def) {
								if (!in_array($def[0], $kinds)) {
									continue;
								}
								$n = (int) (query_single_row(qc_sql($key, $def, 'count'))['n'] ?? 0);
								$info = in_array($key, array('hidden_img', 'alb_hidden'));
								?>
								<tr>
									<td class="n <?php echo $n ? ($info ? '' : 'warn') : 'ok'; ?>">
										<?php if ($n) { ?><a href="<?php echo html_encode(qc_url(array('check' => $key))); ?>"><?php echo number_format($n, 0, ',', "\xC2\xA0"); ?></a><?php } else { ?>&#10003;&nbsp;0<?php } ?>
									</td>
									<td><strong><?php echo html_encode($def[1]); ?></strong><br /><span class="help"><?php echo html_encode($def[2]); ?></span></td>
								</tr>
								<?php
							}
							?>
						</table>
						<?php
					}
				}
				?>
				<p class="help"><?php printf(gettext('Generated in %s s.'), number_format(microtime(true) - $started, 2)); ?></p>
			</div>
		</div><!-- content -->
		<?php printAdminFooter(); ?>
	</div><!-- main -->
</body>
<?php
echo "</html>";
?>
