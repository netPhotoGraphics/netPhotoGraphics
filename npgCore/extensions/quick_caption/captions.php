<?php
/**
 * Quick captions page of the quick_caption plugin.
 *
 * @package plugins/quick_caption
 */
define('OFFSET_PATH', 4);
require_once(dirname(dirname(__DIR__)) . '/admin-globals.php');

admin_securityChecks(MANAGE_ALL_ALBUM_RIGHTS, currentRelativeURL());

$I = prefix('images');
$A = prefix('albums');
$locale = getOption('locale');

/* SQL conditions ----------------------------------------------------------- */

/*
 * Missing text in a language ($loc).
 * With several languages the text is stored per language (a:1:{s:5:"cs_CZ";s:4:"Text";}); a language that has no entry
 * or an empty one counts as missing. A plain (not serialized) text counts as present.
 */

function qcap_missing($col, $loc) {
	if (!getOption('multi_lingual') || !$loc) {
		return "($col IS NULL OR TRIM($col) = '' OR ($col LIKE 'a:%{%' AND $col LIKE '%\"\";}'))";
	}
	$key = db_quote('%s:5:"' . $loc . '";s:%');
	$empty = db_quote('%s:5:"' . $loc . '";s:0:"";%');
	return "($col IS NULL OR TRIM($col) = '' OR ($col LIKE 'a:%{%' AND ($col NOT LIKE $key OR $col LIKE $empty)))";
}

function qcap_base($al) {
	return "LEFT($al.`filename`, GREATEST(CHAR_LENGTH($al.`filename`) - CHAR_LENGTH(SUBSTRING_INDEX($al.`filename`, '.', -1)) - 1, 0))";
}

/* no title: empty, and - when $fileName is true - also a title that is only the file name (also as the text of one language) */

function qcap_no_title($al = 'i', $loc = '', $fileName = true) {
	$cond = qcap_missing("$al.`title`", $loc);
	if ($fileName) {
		$b = qcap_base($al);
		$cond = "($cond OR $al.`title` = $b OR $al.`title` = $al.`filename`";
		if (getOption('multi_lingual') && $loc) {
			$l = addslashes($loc);
			$cond .= " OR $al.`title` LIKE CONCAT('%s:5:\"$l\";s:', CHAR_LENGTH($b), ':\"', $b, '\";%')" .
							" OR $al.`title` LIKE CONCAT('%s:5:\"$l\";s:', CHAR_LENGTH($al.`filename`), ':\"', $al.`filename`, '\";%')";
		}
		$cond .= ")";
	}
	return $cond;
}

function qcap_no_desc($al = 'i', $loc = '') {
	return qcap_missing("$al.`desc`", $loc);
}

/* the text of a stored value in a language ($loc); with no language it is the text the gallery shows */

function qcap_text($raw, $loc = '') {
	$raw = (string) $raw;
	if ($raw === '') {
		return '';
	}
	if ($loc && getOption('multi_lingual') && is_serialized($raw)) {
		$arr = getSerializedArray($raw);
		return isset($arr[$loc]) ? (string) $arr[$loc] : '';
	}
	return (string) (function_exists('get_language_string') ? get_language_string($raw) : $raw);
}

/*
 * Descriptions are stored as HTML by the editor of the gallery (<p>text</p>). A description made only of
 * paragraphs and line breaks is shown as plain text, one paragraph per line; anything richer (bold, links,
 * images ...) is shown and saved as it is, so no formatting is ever lost.
 */

function qcap_desc_simple($html) {
	return $html === '' || !preg_match('~<(?!/?(p|br)\b)[a-z!/]~i', $html);
}

function qcap_desc_to_text($html) {
	$t = preg_replace('~<br\s*/?>~i', "\n", $html);
	$t = preg_replace('~</p>\s*(<p[^>]*>)?~i', "\n", $t);
	$t = preg_replace('~<p[^>]*>~i', '', $t);
	$t = html_entity_decode(strip_tags($t), ENT_QUOTES, 'UTF-8');
	return trim(preg_replace("~\n{3,}~", "\n\n", str_replace("\r", '', $t)));
}

function qcap_text_to_desc($text) {
	$out = array();
	foreach (preg_split("~\n+~", str_replace("\r", '', trim($text))) as $line) {
		$line = trim($line);
		if ($line !== '') {
			$out[] = '<p>' . htmlspecialchars($line, ENT_NOQUOTES, 'UTF-8') . '</p>';
		}
	}
	return implode("\n", $out);
}

/* the new stored value: with a multilingual gallery the text is kept per language, like the core does */

function qcap_store($raw, $text, $locale) {
	if (!getOption('multi_lingual')) {
		return $text;
	}
	$arr = (is_string($raw) && $raw !== '' && is_serialized($raw)) ? getSerializedArray($raw) : array();
	if ($text === '') {
		unset($arr[$locale]);
	} else {
		$arr[$locale] = $text;
	}
	return $arr ? serialize($arr) : '';
}

/* the tag list posted as JSON: clean array of names */

function qcap_clean_tags($json) {
	$list = json_decode((string) $json, true);
	$out = array();
	if (is_array($list)) {
		foreach ($list as $tag) {
			$tag = trim(sanitize((string) $tag, 3));
			if ($tag !== '' && mb_strlen($tag) <= 255 && !in_array(mb_strtolower($tag), array_map('mb_strtolower', $out))) {
				$out[] = $tag;
			}
		}
	}
	return $out;
}

/* request ------------------------------------------------------------------ */
$mode = (isset($_REQUEST['mode']) && in_array($_REQUEST['mode'], array('desc', 'tags'))) ? $_REQUEST['mode'] : 'title';
$folder = isset($_REQUEST['album']) ? trim(sanitize_path($_REQUEST['album']), '/') : '';
$languages = (getOption('multi_lingual') && class_exists('i18n')) ? i18n::generateLanguageList() : array();
$lang = (isset($_REQUEST['lang']) && in_array($_REQUEST['lang'], $languages)) ? $_REQUEST['lang'] : $locale;
if (!getOption('multi_lingual')) {
	$lang = '';
}
$fnTitle = getOption('quick_caption_filename') !== '0' && getOption('quick_caption_filename') !== 0; // a title that is only the file name counts as missing (plugin option, on unless switched off)
// the visual editor (TinyMCE) for the description: only when the gallery's TinyMCE plugin is on and set up for image
$editorAvail = class_exists('tinymce') && getOption('tinymce_photo');
$useEditor = $editorAvail && getOption('quick_caption_editor'); // plugin option
$posA = isset($_REQUEST['a']) ? (int) $_REQUEST['a'] : 0;
$posI = isset($_REQUEST['i']) ? (int) $_REQUEST['i'] : 0;

function qcap_url($params = array()) {
	global $mode, $folder, $lang;
	$params = array_merge(array('page' => 'admin', 'tab' => 'captions', 'mode' => $mode),
					$lang !== '' ? array('lang' => $lang) : array(), $folder !== '' ? array('album' => $folder) : array(), $params);
	return getAdminLink(PLUGIN_FOLDER . '/quick_caption/captions.php') . '?' . http_build_query($params);
}

if ($mode == 'desc') {
	$cond = qcap_no_desc('i', $lang);
} else if ($mode == 'tags') {
	$cond = "NOT EXISTS (SELECT 1 FROM " . prefix('obj_to_tag') . " AS t WHERE t.`type` = 'images' AND t.`objectid` = i.`id`)";
} else {
	$cond = qcap_no_title('i', $lang, $fnTitle);
}
$albumCond = '';
if ($folder !== '') {
	$albumCond = " AND (a.`folder` = " . db_quote($folder) . " OR a.`folder` LIKE " . db_quote(addcslashes($folder, '%_\\') . '/%') . ")";
}

/* save --------------------------------------------------------------------- */
$saved = false;
if (isset($_POST['save'])) {
	XSRFdefender('quick_caption');
	$id = (int) $_POST['id'];
	$row = query_single_row("SELECT `id`, `albumid`, `title`, `desc` FROM $I WHERE `id` = $id");
	if ($row) {
		$sets = array();
		$title = sanitize(isset($_POST['title']) ? trim($_POST['title']) : '', 2);
		$saveLoc = $lang !== '' ? $lang : $locale;
		$desc = isset($_POST['desc']) ? trim(str_replace("\r", '', $_POST['desc'])) : '';
		$descChanged = $desc !== (string) $_POST['orig_desc'];
		if (!empty($_POST['desc_simple'])) {
			$desc = qcap_text_to_desc($desc);
		}
		$desc = sanitize($desc, defined('EDITOR_SANITIZE_LEVEL') ? EDITOR_SANITIZE_LEVEL : 4);
		if ($title !== (string) $_POST['orig_title']) {
			$sets[] = '`title` = ' . db_quote(qcap_store($row['title'], $title, $saveLoc));
		}
		if ($descChanged) {
			$sets[] = '`desc` = ' . db_quote(qcap_store($row['desc'], $desc, $saveLoc));
		}
		$newTags = qcap_clean_tags(isset($_POST['tags']) ? $_POST['tags'] : '[]');
		$oldTags = qcap_clean_tags(isset($_POST['orig_tags']) ? $_POST['orig_tags'] : '[]');
		$tagsChanged = $newTags != $oldTags;
		if ($tagsChanged) {
			storeTags($newTags, (int) $row['id'], 'images');
		}
		if ($sets || $tagsChanged) {
			global $_current_admin_obj;
			$sets[] = '`lastchange` = ' . db_quote(date('Y-m-d H:i:s'));
			if (is_object($_current_admin_obj)) {
				$sets[] = '`lastchangeuser` = ' . db_quote($_current_admin_obj->getUser());
			}
			query("UPDATE $I SET " . implode(', ', $sets) . " WHERE `id` = $id");
		}
		header('Location: ' . qcap_url(array('a' => (int) $row['albumid'], 'i' => (int) $row['id'], 'saved' => ($sets || $tagsChanged) ? 1 : 0)));
		exit();
	}
}
$saved = isset($_GET['saved']) && $_GET['saved'] == 1;

/* data --------------------------------------------------------------------- */
$next = query_single_row("SELECT i.`id`, i.`albumid`, i.`filename`, i.`title`, i.`desc`, i.`mtime`, a.`folder`, a.`title` AS albumtitle " .
				"FROM $I AS i JOIN $A AS a ON a.`id` = i.`albumid` WHERE $cond$albumCond " .
				"AND (i.`albumid` > $posA OR (i.`albumid` = $posA AND i.`id` > $posI)) ORDER BY i.`albumid`, i.`id` LIMIT 1");
$remaining = (int) (query_single_row("SELECT COUNT(*) AS n FROM $I AS i JOIN $A AS a ON a.`id` = i.`albumid` WHERE $cond$albumCond")['n'] ?? 0);
$inAlbum = 0;
$prevText = '';
if ($next) {
	$inAlbum = (int) (query_single_row("SELECT COUNT(*) AS n FROM $I AS i WHERE i.`albumid` = " . (int) $next['albumid'] . " AND $cond")['n'] ?? 0);
	$prev = query_single_row("SELECT i.`title`, i.`desc` FROM $I AS i WHERE i.`albumid` = " . (int) $next['albumid'] . " AND i.`id` < " . (int) $next['id'] . " AND NOT $cond ORDER BY i.`id` DESC LIMIT 1");
	if ($prev) {
		$prevText = ($mode == 'desc') ? (qcap_desc_simple(qcap_text($prev['desc'], $lang)) ? qcap_desc_to_text(qcap_text($prev['desc'], $lang)) : '') : qcap_text($prev['title'], $lang);
	}
}
$curTags = $prevTags = array();
if ($next) {
	$curTags = readTags((int) $next['id'], 'images', '');
	$pt = query_single_row("SELECT i.`id` FROM $I AS i WHERE i.`albumid` = " . (int) $next['albumid'] . " AND i.`id` < " . (int) $next['id'] . " ORDER BY i.`id` DESC LIMIT 1");
	if ($pt) {
		$prevTags = readTags((int) $pt['id'], 'images', '');
	}
}
$topTags = array();
foreach (query_full_array("SELECT t.`name`, COUNT(*) AS n FROM " . prefix('tags') . " AS t JOIN " . prefix('obj_to_tag') . " AS o ON o.`tagid` = t.`id` WHERE o.`type` = 'images' GROUP BY t.`id`, t.`name` ORDER BY n DESC LIMIT 24") as $r) {
	$topTags[] = $r['name'];
}
$allTags = array();
foreach (query_full_array("SELECT `name` FROM " . prefix('tags') . " ORDER BY `name` LIMIT 6000") as $r) {
	$allTags[] = $r['name'];
}
$albumsWith = query_full_array("SELECT a.`folder`, COUNT(*) AS n FROM $I AS i JOIN $A AS a ON a.`id` = i.`albumid` WHERE $cond GROUP BY a.`id`, a.`folder` ORDER BY a.`folder`");

/* the photo (the image object is not created on purpose: loading it could refresh metadata and change the date) */
$imgUrl = $galleryUrl = '';
if ($next) {
	$gf = $next['filename'];
	if (defined('UNIQUE_IMAGE') && UNIQUE_IMAGE) {
		$gf = stripSuffix($gf);
	}
	$galleryUrl = rewrite_path(pathurlencode($next['folder']) . '/' . urlencode($gf) . (defined('RW_SUFFIX') ? RW_SUFFIX : ''), '/index.php?album=' . pathurlencode($next['folder']) . '&image=' . urlencode($gf));
	$args = getImageParameters(array('size' => 1000), $next['folder']);
	$imgUrl = getImageURI($args, $next['folder'], $next['filename'], (int) $next['mtime']);
}

printAdminHeader('admin', 'captions');
?>
<style>
	.qcap { max-width: 1500px; }
	.qcap .bar { display: flex; flex-wrap: wrap; gap: .75rem 1.25rem; align-items: center; margin-bottom: 1rem; }
	.qcap .work { display: grid; grid-template-columns: minmax(0, 3fr) minmax(280px, 2fr); gap: 1.5rem; align-items: start; }
	.qcap .photo { text-align: center; background: #f1f3f6; border-radius: 8px; padding: .5rem; }
	.qcap .photo img { max-width: 100%; max-height: 72vh; width: auto; height: auto; }
	.qcap label { display: block; font-weight: 600; margin: .8rem 0 .25rem; }
	.qcap input[type=text], .qcap textarea { width: 100%; box-sizing: border-box; font-size: 1.1em; }
	.qcap textarea { min-height: 7em; }
	.qcap .actions { margin-top: 1rem; display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; }
	.qcap .actions button { cursor: pointer; padding: .55rem 1.2rem; font-size: 1em; }
	.qcap .hint { color: #5b6475; font-size: .9em; }
	.qcap .meta { color: #5b6475; font-size: .9em; margin: .3rem 0 0; word-break: break-all; }
	.qcap .done { color: #1f7a3d; font-weight: 600; }
	.qcap .saved { color: #1f7a3d; margin: 0 0 .6rem; }
	.qcap .btnlink { display: inline-block; padding: .5rem 1rem; border: 1px solid #d9dde4; border-radius: 8px; background: #fff; color: inherit; text-decoration: none; }
	.qcap .btnlink:hover { background: #f4f5f7; text-decoration: none; }
	.qcap .tagbox { position: relative; display: flex; flex-wrap: wrap; gap: .3rem; align-items: center; border: 1px solid #d9dde4; border-radius: 7px; padding: .3rem; background: #fff; }
	.qcap .tagbox input[type=text] { flex: 1 1 8em; width: auto; border: 0; outline: 0; font-size: 1em; min-width: 8em; }
	.qcap .chip { display: inline-flex; align-items: center; gap: .3rem; background: #e6edfb; color: #2456c9; border-radius: 999px; padding: .15rem .3rem .15rem .65rem; font-size: .95em; }
	.qcap .chip button { border: 0; background: none; cursor: pointer; color: inherit; font-size: 1.1em; padding: 0 .3rem; line-height: 1; }
	.qcap .sugg { position: absolute; left: 0; right: 0; top: 100%; z-index: 20; background: #fff; border: 1px solid #d9dde4; border-radius: 7px; box-shadow: 0 6px 20px rgba(20,30,50,.15); margin-top: 2px; max-height: 14em; overflow: auto; }
	.qcap .sugg div { padding: .35rem .7rem; cursor: pointer; }
	.qcap .sugg div.on, .qcap .sugg div:hover { background: #e6edfb; }
	.qcap .toptags button { margin: 0 .3rem .3rem 0; padding: .2rem .65rem; border: 1px solid #d9dde4; border-radius: 999px; background: #fff; cursor: pointer; font-size: .92em; }
	.qcap .toptags button.on { background: #2456c9; border-color: #2456c9; color: #fff; }
	@media (max-width: 900px) { .qcap .work { grid-template-columns: 1fr; } }
</style>
<?php
if ($useEditor) {
	npgFilters::apply('texteditor_config', 'photo');
}
echo '</head>';
?>
<body>
	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<h1><?php echo gettext('Quick captions'); ?></h1>
			<div class="tabbox qcap">
				<form method="get" action="<?php echo html_encode(getAdminLink(PLUGIN_FOLDER . '/quick_caption/captions.php')); ?>" class="bar ignoredirty">
					<input type="hidden" name="page" value="admin" />
					<input type="hidden" name="tab" value="captions" />
					<label style="display:inline;margin:0"><?php echo gettext('Show image'); ?>:
						<select name="mode" onchange="this.form.submit()">
							<option value="title"<?php echo $mode == 'title' ? ' selected' : ''; ?>><?php echo gettext('without a title'); ?></option>
							<option value="desc"<?php echo $mode == 'desc' ? ' selected' : ''; ?>><?php echo gettext('without a description'); ?></option>
							<option value="tags"<?php echo $mode == 'tags' ? ' selected' : ''; ?>><?php echo gettext('without tags'); ?></option>
						</select>
					</label>
					<label style="display:inline;margin:0"><?php echo gettext('Album'); ?>:
						<select name="album" onchange="this.form.submit()">
							<option value=""><?php echo gettext('all albums'); ?></option>
							<?php foreach ($albumsWith as $r) { ?>
								<option value="<?php echo html_encode($r['folder']); ?>"<?php echo $folder === $r['folder'] ? ' selected' : ''; ?>><?php echo html_encode($r['folder']) . ' (' . (int) $r['n'] . ')'; ?></option>
							<?php } ?>
						</select>
					</label>
					<?php if (count($languages) > 1) { ?>
						<label style="display:inline;margin:0"><?php echo gettext('Language'); ?>:
							<select name="lang" onchange="this.form.submit()">
								<?php foreach ($languages as $name => $code) { ?>
									<option value="<?php echo html_encode($code); ?>"<?php echo $lang === $code ? ' selected' : ''; ?>><?php echo html_encode($name); ?></option>
								<?php } ?>
							</select>
						</label>
					<?php } ?>
					<?php
					if (count($languages) < 2) {
						$allLang = class_exists('i18n') ? array_flip(i18n::generateLanguageList(true)) : array();
						$curLang = $lang !== '' ? $lang : $locale;
						$curName = isset($allLang[$curLang]) ? $allLang[$curLang] : $curLang;
						?>
						<span class="hint" title="<?php echo html_encode(getOption('multi_lingual') ? gettext('Only one language is enabled, so there is nothing to choose. More languages can be enabled in Options > General.') : gettext('The gallery is not multi-lingual, so there is no language to choose: the text is saved as plain text. Switch it on in Options > General > multi-lingual.')); ?>"><?php printf(gettext('Language of the texts: %s'), '<strong>' . html_encode($curName) . '</strong>'); ?> <?php echo getOption('multi_lingual') ? '' : '(' . gettext('single-language gallery') . ')'; ?></span>
					<?php } ?>
					<?php if ($mode == 'title' && $fnTitle) { ?>
						<span class="hint" title="<?php echo html_encode(gettext('Change it in Options > Plugins > quick_caption.')); ?>"><?php echo gettext('a title that is only the file name counts as missing'); ?></span>
					<?php } ?>
					<span class="hint" title="<?php echo html_encode(gettext('All image of the selected album and its sub-albums that still need the text, including the one shown.')); ?>"><?php printf(gettext('In this selection: %s image to do'), number_format($remaining, 0, ',', "\xC2\xA0")); ?></span>
				</form>
				<?php if ($saved) { ?><p class="saved">&#10003; <?php echo gettext('Saved.'); ?></p><?php } ?>
				<?php if (!$next) { ?>
					<p class="done"><?php echo $remaining ? gettext('No more image after this one. Start from the beginning:') : gettext('Nothing to do - all image in this selection have the text.'); ?>
						<?php if ($remaining) { ?><a href="<?php echo html_encode(qcap_url()); ?>"><?php echo gettext('first photo'); ?></a><?php } ?></p>
					<?php
				} else {
					$title = qcap_text($next['title'], $lang);
					$descHtml = qcap_text($next['desc'], $lang);
					$descSimple = !$useEditor && qcap_desc_simple($descHtml);
					$desc = $descSimple ? qcap_desc_to_text($descHtml) : $descHtml;
					$editLink = getAdminLink('admin-tabs/edit.php') . '?page=edit&album=' . pathurlencode($next['folder']) . '&singleimage=' . urlencode($next['filename']) . '&tab=imageinfo';
					?>
					<div class="work">
						<div class="photo">
							<img src="<?php echo html_encode($imgUrl); ?>" alt="" />
						</div>
						<form method="post" action="<?php echo html_encode(qcap_url()); ?>" id="capform" class="ignoredirty">
							<?php XSRFToken('quick_caption'); ?>
							<input type="hidden" name="save" value="1" />
							<input type="hidden" name="id" value="<?php echo (int) $next['id']; ?>" />
							<input type="hidden" name="mode" value="<?php echo html_encode($mode); ?>" />
							<input type="hidden" name="album" value="<?php echo html_encode($folder); ?>" />
							<?php
							// the real title is always shown; a title that is only the file name is selected so typing replaces it
							$base = pathinfo($next['filename'], PATHINFO_FILENAME);
							$shownTitle = $title;
							$selectTitle = ($title !== '' && ($title === $base || $title === $next['filename']));
							?>
							<input type="hidden" name="lang" value="<?php echo html_encode($lang); ?>" />
							<input type="hidden" name="orig_title" value="<?php echo html_encode($shownTitle); ?>" />
							<input type="hidden" name="orig_desc" value="<?php echo html_encode($desc); ?>" />
							<input type="hidden" name="desc_simple" value="<?php echo $descSimple ? 1 : 0; ?>" />
							<p class="meta"><strong><?php echo html_encode($next['folder']); ?></strong><?php echo $next['albumtitle'] ? ' &ndash; ' . html_encode(getBare(qcap_text($next['albumtitle']))) : ''; ?><br />
								<?php echo html_encode($next['filename']); ?> &middot; <?php printf(gettext('%d image to do in this album'), $inAlbum); ?></p>
							<label for="cap_title"><?php echo gettext('Title'); ?></label>
							<input type="text" id="cap_title" name="title" value="<?php echo html_encode($shownTitle); ?>" autocomplete="off" autofocus />
							<label for="cap_desc"><?php echo gettext('Description'); ?></label>
							<textarea id="cap_desc" name="desc"<?php echo $useEditor ? ' class="texteditor"' : ''; ?>><?php echo html_encode($desc); ?></textarea>
							<label for="cap_tag"><?php echo gettext('Tags'); ?></label>
							<div id="tagbox" class="tagbox">
								<span id="chips"></span>
								<input type="text" id="cap_tag" autocomplete="off" placeholder="<?php echo html_encode(gettext('type a tag, Enter adds it')); ?>" />
								<div id="sugg" class="sugg" hidden></div>
							</div>
							<input type="hidden" name="tags" id="tags_json" value="<?php echo html_encode(json_encode(array_values($curTags))); ?>" />
							<input type="hidden" name="orig_tags" value="<?php echo html_encode(json_encode(array_values($curTags))); ?>" />
							<?php if ($topTags) { ?>
								<p class="hint" style="margin-bottom:.3rem"><?php echo gettext('Most used tags'); ?>:</p>
								<p id="toptags" class="toptags"></p>
							<?php } ?>
							<?php if ($prevTags) { ?>
								<p class="hint"><?php echo gettext('Tags of the previous photo'); ?>: <em><?php echo html_encode(implode(', ', $prevTags)); ?></em>
									<button type="button" id="usePrevTags" class="ignoredirty"><?php echo gettext('use them'); ?></button></p>
							<?php } ?>
							<?php if ($prevText !== '') { ?>
								<p class="hint"><?php echo gettext('Previous photo of the album'); ?>: <em><?php echo html_encode(getBare($prevText)); ?></em>
									<button type="button" id="usePrev" class="ignoredirty"><?php echo gettext('use it'); ?></button></p>
							<?php } ?>
							<div class="actions">
								<button type="submit" class="ignoredirty"><strong><?php echo gettext('Save and next'); ?></strong></button>
								<a class="btnlink" href="<?php echo html_encode(qcap_url(array('a' => (int) $next['albumid'], 'i' => (int) $next['id']))); ?>"><?php echo gettext('Skip'); ?></a>
								<a class="btnlink" href="<?php echo html_encode($editLink); ?>" target="_blank"><?php echo gettext('Open full editing'); ?></a>
								<a class="btnlink" href="<?php echo html_encode($galleryUrl); ?>" target="_blank"><?php echo gettext('Open in gallery'); ?></a>
							</div>
							<p class="hint"><?php echo gettext('Enter in the title saves and shows the next photo. Ctrl+Enter does the same from the description.'); ?></p>
						</form>
					</div>
					<script>
						(function () {
							var f = document.getElementById('capform'), d = document.getElementById('cap_desc'), t = document.getElementById('cap_title');
	<?php
	if (!empty($selectTitle) && $mode == 'title') {
		?>
								t.select();
		<?php
	}
	?>
							var visual = <?php echo $useEditor ? 'true' : 'false'; ?>;
							function sendForm() {
								if (visual && window.tinymce) {
									tinymce.triggerSave();
								}
								f.submit();
							}
							d.addEventListener('keydown', function (e) {
								if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
									e.preventDefault();
									sendForm();
								}
							});
							if (visual && window.tinymce) {
								tinymce.on('AddEditor', function (ev) {
									ev.editor.on('keydown', function (k) {
										if (k.key === 'Enter' && (k.ctrlKey || k.metaKey)) {
											k.preventDefault();
											sendForm();
										}
									});
									if (<?php echo json_encode($mode); ?> === 'desc') {
										ev.editor.on('init', function () {
											ev.editor.focus();
										});
									}
								});
							}
							var u = document.getElementById('usePrev');
							if (u) {
								var prev = <?php echo json_encode($prevText); ?>;
								u.addEventListener('click', function () {
									if (visual && <?php echo json_encode($mode); ?> === 'desc' && window.tinymce && tinymce.get('cap_desc')) {
										var ed = tinymce.get('cap_desc'), div = document.createElement('div');
										ed.setContent(prev.split(/\n+/).map(function (l) {
											div.textContent = l;
											return '<p>' + div.innerHTML + '</p>';
										}).join(''));
										ed.focus();
										return;
									}
									(<?php echo json_encode($mode); ?> === 'desc' ? d : t).value = prev;
									(<?php echo json_encode($mode); ?> === 'desc' ? d : t).focus();
								});
							}
							var tagMode = <?php echo json_encode($mode); ?> === 'tags';
							var tags = <?php echo json_encode(array_values($curTags)); ?>.slice();
							var allTags = <?php echo json_encode($allTags); ?>;
							var top = <?php echo json_encode($topTags); ?>;
							var prevTags = <?php echo json_encode(array_values($prevTags)); ?>;
							var inp = document.getElementById('cap_tag'), chips = document.getElementById('chips'),
											json = document.getElementById('tags_json'), sugg = document.getElementById('sugg'),
											topBox = document.getElementById('toptags'), hl = -1, shown = [];

							function has(n) {
								return tags.some(function (x) {
									return x.toLowerCase() === n.toLowerCase();
								});
							}
							function render() {
								chips.innerHTML = '';
								tags.forEach(function (n) {
									var c = document.createElement('span'), b = document.createElement('button');
									c.className = 'chip';
									c.appendChild(document.createTextNode(n));
									b.type = 'button';
									b.className = 'ignoredirty';
									b.setAttribute('aria-label', 'x');
									b.textContent = '\u00d7';
									b.addEventListener('click', function () {
										remove(n);
										inp.focus();
									});
									c.appendChild(b);
									chips.appendChild(c);
								});
								json.value = JSON.stringify(tags);
								if (topBox) {
									Array.prototype.forEach.call(topBox.children, function (el) {
										el.classList.toggle('on', has(el.getAttribute('data-t')));
									});
								}
							}
							function add(n) {
								n = n.replace(/^[\s,]+|[\s,]+$/g, '');
								if (n && !has(n)) {
									tags.push(n);
								}
								render();
							}
							function remove(n) {
								tags = tags.filter(function (x) {
									return x.toLowerCase() !== n.toLowerCase();
								});
								render();
							}
							function closeSugg() {
								sugg.hidden = true;
								hl = -1;
							}
							function openSugg() {
								var q = inp.value.trim().toLowerCase();
								if (!q) {
									closeSugg();
									return;
								}
								shown = allTags.filter(function (n) {
									return n.toLowerCase().indexOf(q) !== -1 && !has(n);
								})
												.sort(function (a, b) {
													return (a.toLowerCase().indexOf(q) === 0 ? 0 : 1) - (b.toLowerCase().indexOf(q) === 0 ? 0 : 1);
												}).slice(0, 8);
								sugg.innerHTML = '';
								shown.forEach(function (n, i) {
									var el = document.createElement('div');
									el.textContent = n;
									el.addEventListener('mousedown', function (e) {
										e.preventDefault();
										add(n);
										inp.value = '';
										closeSugg();
										inp.focus();
									});
									sugg.appendChild(el);
								});
								sugg.hidden = !shown.length;
								hl = -1;
							}
							function mark() {
								Array.prototype.forEach.call(sugg.children, function (el, i) {
									el.classList.toggle('on', i === hl);
								});
							}
							inp.addEventListener('input', openSugg);
							inp.addEventListener('blur', function () {
								closeSugg();
							});
							inp.addEventListener('keydown', function (e) {
								if (e.key === 'ArrowDown' && shown.length) {
									e.preventDefault();
									hl = (hl + 1) % shown.length;
									mark();
								} else if (e.key === 'ArrowUp' && shown.length) {
									e.preventDefault();
									hl = (hl <= 0 ? shown.length : hl) - 1;
									mark();
								} else if (e.key === 'Enter' || e.key === ',') {
									e.preventDefault();
									if (hl >= 0 && shown[hl]) {
										add(shown[hl]);
									} else {
										add(inp.value);
									}
									inp.value = '';
									closeSugg();
								} else if (e.key === 'Backspace' && !inp.value && tags.length) {
									tags.pop();
									render();
								}
							});
							document.getElementById('tagbox').addEventListener('click', function () {
								inp.focus();
							});
							if (topBox) {
								top.forEach(function (n) {
									var b = document.createElement('button');
									b.type = 'button';
									b.className = 'ignoredirty';
									b.textContent = n;
									b.setAttribute('data-t', n);
									b.addEventListener('click', function () {
										if (has(n)) {
											remove(n);
										} else {
											add(n);
										}
									});
									topBox.appendChild(b);
								});
							}
							var up = document.getElementById('usePrevTags');
							if (up) {
								up.addEventListener('click', function () {
									prevTags.forEach(add);
									inp.focus();
								});
							}
							f.addEventListener('submit', function () {
								if (visual && window.tinymce) {
									tinymce.triggerSave();
								}
								if (inp.value.trim()) {
									add(inp.value);
									inp.value = '';
								}
							});
							render();
							if (tagMode) {
								inp.focus();
							} else if (!(visual && <?php echo json_encode($mode); ?> === 'desc')) {
								(<?php echo json_encode($mode); ?> === 'desc' ? d : t).focus();
							}
						})();
					</script>
				<?php } ?>
			</div>
		</div><!-- content -->
		<?php printAdminFooter(); ?>
	</div><!-- main -->
</body>
<?php
echo "</html>";
?>
