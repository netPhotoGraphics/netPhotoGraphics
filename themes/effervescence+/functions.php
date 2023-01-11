<?php
// force UTF-8 Ø

npgFilters::register('themeSwitcher_head', 'switcher_head');
npgFilters::register('themeSwitcher_Controllink', 'switcher_controllink');
npgFilters::register('theme_head', 'EF_head', 0);

define('ALBUM_THMB_WIDTH', 170);
define('ALBUM_THUMB_HEIGHT', 80);
if (class_exists('CMS')) {
	setOption('gallery_index', 1, false);
}

$cwd = getcwd();
chdir(__DIR__);
$persona = safe_glob('*', GLOB_ONLYDIR);
chdir($cwd);
$personalities = array();
foreach ($persona as $personality) {
	if (file_exists(SERVERPATH . '/' . THEMEFOLDER . '/effervescence+/' . $personality . '/functions.php'))
		$personalities[ucfirst(str_replace('_', ' ', $personality))] = $personality;
}

chdir(SERVERPATH . "/themes/" . basename(__DIR__) . "/styles");
$filelist = safe_glob('*.txt');
if (file_exists(SERVERPATH . "/themes/" . basename(__DIR__) . "/data")) {
	chdir(SERVERPATH . "/themes/" . basename(__DIR__) . "/data");
	$userlist = safe_glob('*.txt');
	$filelist = array_merge($filelist, $userlist);
}
$themecolors = array();
foreach ($filelist as $file) {
	$themecolors[basename($file)] = stripSuffix(filesystemToInternal($file));
}
chdir($cwd);

if (class_exists('themeSwitcher')) {
	$themeColor = themeSwitcher::themeSelection('themeColor', $themecolors);
	if (!$themeColor) {
		$themeColor = getOption('Theme_colors');
	}

	$personality = themeSwitcher::themeSelection('themePersonality', $personalities);
	if ($personality) {
		setOption('effervescence_personality', $personality, false);
	} else {
		$personality = getOption('effervescence_personality');
	}
	$sets = getMenuSets();
	$sets[] = ''; //	the built-in menu
	$themeMenu = themeSwitcher::themeSelection('themeMenu', $sets);
	setOption('effervescence_menu', $themeMenu, false);
} else {
	$personality = getOption('effervescence_personality');
}
if ($personality) {
	$personality = strtolower($personality);
}

if (!in_array($personality, $personalities)) {
	$persona = $personalities;
	$personality = reset($persona);
}

if (($_ef_menu = getOption('effervescence_menu')) == 'effervescence' || $_ef_menu == 'zenpage') {
	enableExtension('print_album_menu', 1 | THEME_PLUGIN, false);
}
require_once(SERVERPATH . '/' . THEMEFOLDER . '/effervescence+/' . $personality . '/functions.php');
$_current_page_check = 'my_checkPageValidity';

define('_IMAGE_PATH', WEBPATH . '/' . THEMEFOLDER . '/effervescence+/images/');

function EF_head() {
	global $themeColor;
	if (!$themeColor) {
		$themeColor = getOption('Theme_colors');
	}
	$basePath = SERVERPATH . '/' . THEMEFOLDER . '/effervescence+/';
	$csfile = $basePath . 'data/styles/' . $themeColor . '.css';
	$genfile = $basePath . 'styles/' . $themeColor . '.txt';
	if (!file_exists($genfile)) {
		$genfile = $basePath . 'data/' . $themeColor . '.txt';
		if (!file_exists($genfile)) {
			$genfile = $basePath . 'styles/kish-my father.txt';
		}
	}

	if (!file_exists($csfile) || ($mtime = filemtime($csfile) < filemtime($genfile)) || $mtime < filemtime($basePath . '/base.css')) {
		eval(file_get_contents($genfile));
		$css = file_get_contents($basePath . '/base.css');
		$css = strtr($css, $tr);
		$css = preg_replace('|\.\./images/|', WEBPATH . '/' . THEMEFOLDER . '/effervescence+/images/', $css);
		$common = file_get_contents(SERVERPATH . '/' . THEMEFOLDER . '/effervescence+/common.css');
		$common = preg_replace('|images/|', WEBPATH . '/' . THEMEFOLDER . '/effervescence+/images/', $common);

		$buffer = preg_replace('~/\*[^*]*\*+([^/][^*]*\*+)*/~', '', $common . $css);
		$buffer = str_replace(': ', ':', $buffer);
		$buffer = preg_replace('/\s+/', ' ', $buffer);

		mkdir_recursive($basePath . '/data/styles', FOLDER_MOD);
		file_put_contents($csfile, $buffer);
	}
	scriptLoader(SERVERPATH . '/' . THEMEFOLDER . '/effervescence+/data/styles/' . $themeColor . '.css');
	?>
	<script type="text/javascript">
		
		function blurAnchors() {
			if (document.getElementsByTagName) {
				var a = document.getElementsByTagName("a");
				for (var i = 0; i < a.length; i++) {
					a[i].onfocus = function () {
						this.blur()
					};
				}
			}
		}
		
	</script>
	<?php
}

function iconColor($icon) {
	global $themeColor;
	if (!$themeColor) {
		list($personality, $themeColor) = getPersonality();
	}
	switch ($themeColor) {
		case 'rainbow':
		case 'effervescence':
			return($icon);
		default:
			return (stripSuffix($icon) . '-gray.png');
	}
}

function switcher_head($ignore) {
	?>
	<script type="text/javascript">
		
		function switchColors() {
			personality = $('#themeColor').val();
			window.location = '?themeColor=' + personality;
		}
		function switchPersonality() {
			personality = $('#themePersonality').val();
			window.location = '?themePersonality=' + personality;
		}
		function switchMenu() {
			personality = $('#themeMenu').val();
			window.location = '?themeMenu=' + personality;
		}
		
	</script>
	<?php
	return $ignore;
}

function switcher_controllink($ignore) {
	global $personality, $personalities, $themecolors, $_gallery_page, $themeColor, $themeMenu;
	$themeColor = getNPGCookie('themeSwitcher_themeColor');
	if (!$themeColor) {
		list($personality, $themeColor, $themeMenu) = getPersonality();
	}
	?>
	<span id="themeSwitcher_effervescence">
		<span title="<?php echo gettext("Effervescence color scheme."); ?>">
			<?php echo gettext('Theme Color'); ?>
			<select name="themeColor" id="themeColor" onchange="switchColors();">
				<?php generateListFromArray(array($themeColor), $themecolors, false, false); ?>
			</select>
		</span>
		<?php
		if (!$personality) {
			$personality = getOption('effervescence_personality');
		}
		?>
		<span title="<?php echo gettext("Effervescence image display handling."); ?>">
			<?php echo gettext('Personality'); ?>
			<select name="themePersonality" id="themePersonality" onchange="switchPersonality();">
				<?php generateListFromArray(array($personality), $personalities, false, true); ?>
			</select>
		</span>
		<?php
		$menus = getMenuSets();
		if ($menus) {
			if (!$themeMenu) {
				$themeMenu = getOption('effervescence_menu');
			}
			?>
			<span title="<?php echo gettext("Effervescence menu."); ?>">
				<?php echo gettext('Menu'); ?>
				<select name="themeMenu" id="themeMenu" onchange="switchMenu();">
					<option value =''><?php echo gettext('*standard menu'); ?></option>
					<?php generateListFromArray(array($themeMenu), $menus, false, true); ?>
				</select>
			</span>
			<?php
		}
		?>
	</span>
	<?php
	return $ignore;
}

/* SQL Counting Functions */

function get_subalbum_count() {
	$where = "WHERE parentid IS NOT NULL";
	if (!npg_loggedin()) {
		$where .= " AND `show`=1";
	} /* exclude the un-published albums */
	return db_count('albums', $where);
}

function show_sub_count_index() {
	echo getNumAlbums();
}

function printHeadingImage($randomImage) {
	global $_themeroot, $_current_album;
	if ($_current_album) {
		$id = $_current_album->getId();
	} else {
		$id = 0;
	}
	echo '<div id="randomhead">';
	if (is_null($randomImage)) {
		printSiteLogoImage(gettext('There were no images from which to select the random heading.'));
	} else {
		$randomAlbum = $randomImage->getAlbum();
		$randomAlt1 = $randomAlbum->getTitle();
		if ($randomAlbum->getID() <> $id) {
			$randomAlbum = $randomAlbum->getParent();
			while (!is_null($randomAlbum) && ($randomAlbum->getID() <> $id)) {
				$randomAlt1 = $randomAlbum->getTitle() . ":\n" . $randomAlt1;
				$randomAlbum = $randomAlbum->getParent();
			}
		}
		$randomImageURL = html_encode($randomImage->getLink());
		if (getOption('allow_upscale')) {
			$wide = 620;
			$high = 180;
		} else {
			$wide = min(620, $randomImage->getWidth());
			$high = min(180, $randomImage->getHeight());
		}

		echo "<a href='" . $randomImageURL . "' title='" . gettext('Random picture...') . "'>";
		$html = "<img src='" . html_encode($randomImage->getCustomImage(array('width' => $wide, 'height' => $high, 'cw' => $wide, 'ch' => $high, 'thumb' => !getOption('Watermark_head_image')))) .
						"' width='$wide' height='$high' alt=" . '"' .
						html_encode($randomAlt1) .
						":\n" . html_encode($randomImage->getTitle()) .
						'" />';
		$html = npgFilters::apply('custom_image_html', $html, FALSE);
		if (ENCODING_FALLBACK) {
			$html = "<picture>\n<source srcset=\"" . html_encode($randomImage->getCustomImage(array('width' => $wide, 'height' => $high, 'cw' => $wide, 'ch' => $high, 'thumb' => !getOption('Watermark_head_image')), NULL, FALLBACK_SUFFIX)) . "\">\n" . $html . "</picture>\n";
		}
		echo $html;
		echo '</a>';
	}
	echo '</div>';
}

/* Custom caption functions */

function getCustomAlbumDesc() {
	if (!in_context(NPG_ALBUM))
		return false;
	global $_current_album;
	$desc = $_current_album->getDesc();
	if (strlen($desc) == 0) {
		$desc = $_current_album->getTitle();
	} else {
		$desc = $_current_album->getTitle() . "\n" . $desc;
	}
	return $desc;
}

function getImage_AlbumCount() {
	$c = getNumAlbums();
	if ($c > 0) {
		$result = "\n " . sprintf(ngettext("%u album", "%u albums", $c), $c);
	} else {
		$result = '';
	}
	$c = getNumImages();
	if ($c > 0) {
		$result .= "\n " . sprintf(ngettext("%u image", "%u images", $c), $c);
	}
	return $result;
}

function printNofM($what, $first, $last, $total) {
	if (!is_null($first)) {
		echo "<p align=\"center\">";
		if ($first == $last) {
			if ($what == 'Album') {
				printf(gettext('Album %1$u of %2$u'), $first, $total);
			} else {
				printf(gettext('Photo %1$u of %2$u'), $first, $total);
			}
		} else {
			if ($what == 'Album') {
				printf(gettext('Albums %1$u-%2$u of %3$u'), $first, $last, $total);
			} else {
				printf(gettext('Photos %1$u-%2$u of %3$u'), $first, $last, $total);
			}
		}
		echo "</p>";
	}
}

function getPersonality() {
	global $themeColor, $themecolors;
	if (!$themeColor) {
		$themeColor = getOption('Theme_colors');
	}
	if (!in_array($themeColor, $themecolors)) {
		$themeColor = 'kish-my father';
	}
	$personality = getOption('effervescence_personality');
	$themeMenu = getOption('effervescence_menu');
	return array($personality, $themeColor, $themeMenu);
}

function printThemeInfo() {
	if (getThemeOption('display_theme_info')) {
		list($personality, $themeColor) = getPersonality();
		if ($themeColor == 'effervescence') {
			$themeColor = '';
		}
		if ($personality == 'Image page') {
			$personality = '';
		} else if (($personality == 'Simpleviewer' && !class_exists('simpleviewer')) ||
						($personality == 'Colorbox' && !npgFilters::has_filter('admin_head', 'colorbox::css'))) {
			$personality = "<strike>$personality</strike>";
		}
		$personality = str_replace('_', ' ', $personality);
		if (empty($themeColor) && empty($personality)) {
			echo '<p><small>Effervescence</small></p>';
		} else if (empty($themeColor)) {
			echo '<p><small>' . sprintf(gettext('Effervescence %s'), $personality) . '</small></p>';
		} else if (empty($personality)) {
			echo '<p><small>' . sprintf(gettext('Effervescence %s'), $themeColor) . '</small></p>';
		} else {
			echo '<p><small>' . sprintf(gettext('Effervescence %1$s %2$s'), $themeColor, $personality) . '</small></p>';
		}
	}
}

function printLinkWithQuery($url, $query, $text) {
	$url = rtrim($url, '/') . (MOD_REWRITE ? "?" : "&amp;");
	echo "<a href=\"$url$query\">$text</a>";
}

function printLogo() {
	global $_themeroot;
	$name = get_language_string(getOption('Theme_logo'));
	if ($img = getOption('Graphic_logo')) {
		$fullimg = '/' . UPLOAD_FOLDER . '/images/' . $img . '.png';
		if (file_exists(SERVERPATH . $fullimg)) {
			echo '<img src="' . pathurlencode(WEBPATH . $fullimg) . '" alt="Logo"/>';
		} else {
			echo '<img src="' . $_themeroot . '/images/effervescence.png" alt="Logo"/>';
		}
	} else {
		if (empty($name)) {
			$name = sanitize($_SERVER['HTTP_HOST']);
		}
	}
	if (!empty($name)) {
		echo "<h1>$name</h1>";
	}
}

function annotateAlbum() {
	global $_current_album;
	$tagit = '';
	$pwd = $_current_album->getPassword();
	if (npg_loggedin() && !empty($pwd)) {
		$tagit = "\n" . gettext('The album is password protected.');
	}
	if (!$_current_album->getShow()) {
		$tagit .= "\n" . gettext('The album is not published.');
	}
	return sprintf(gettext('View the Album: %s'), getBareAlbumTitle()) . getImage_AlbumCount() . $tagit;
}

function annotateImage() {
	global $_current_image;
	if (is_object($_current_image)) {
		if (!$_current_image->getShow()) {
			$tagit = "\n" . gettext('The image is marked not visible.');
		} else {
			$tagit = '';
		}
		return sprintf(gettext('View the image: %s'), GetBareImageTitle()) . $tagit;
	}
}

function printFooter($admin = true) {
	global $_themeroot, $_gallery, $_gallery_page;
	$h = NULL;
	?>
	<!-- Footer -->
	<div class="footlinks">
		<?php
		if (function_exists('getHitCounter')) {
			$h = getHitCounter();
		} else {
			$h = 0;
		}
		if (!is_null($h)) {
			?>
			<p>
				<?php printf(ngettext('%1$u hit on this %2$s', '%1$u hits on this %2$s', $h), $h, gettext('page')); ?>
			</p>
			<?php
		}
		if ($_gallery_page == 'gallery.php') {
			?>
			<p>
				<small>
					<?php
					$albumNumber = getNumAlbums();
					echo sprintf(ngettext("%u Album", "%u Albums", $albumNumber), $albumNumber);
					?> &middot;
					<?php
					$c = get_subalbum_count();
					echo sprintf(ngettext("%u Subalbum", "%u Subalbums", $c), $c);
					?> &middot;
					<?php
					$photosNumber = db_count('images');
					echo sprintf(ngettext("%u Image", "%u Images", $photosNumber), $photosNumber);
					?>
					<?php if (function_exists('printCommentForm')) { ?>
						&middot;
						<?php
						$commentsNumber = db_count('comments', " WHERE inmoderation = 0");
						echo sprintf(ngettext("%u Comment", "%u Comments", $commentsNumber), $commentsNumber);
					}
					?>
				</small>
			</p>
			<?php
		}
		?>

		<?php printThemeInfo(); ?>
		<?php print_SW_Link(); ?>
		<br />
		<?php
		if (function_exists('printFavoritesURL') && $_gallery_page != 'password.php' && $_gallery_page != 'favorites.php') {
			printFavoritesURL(NULL, '', ' | ', '<br />');
		}
		?>
		<?php
		if ($_gallery_page == 'gallery.php') {
			if (class_exists('RSS'))
				printRSSLink('Gallery', '', 'Gallery', '');
			echo '<br />';
		}
		?>
		<?php
		if (function_exists('printUserLogin_out'))
			printUserLogin_out('', '<br />');
		?>
		<?php
		if ($_gallery_page != 'contact.php' && extensionEnabled('contact_form') && ($_gallery_page != 'password.php' || $_gallery->isUnprotectedPage('contact'))) {
			printCustomPageURL(gettext('Contact us'), 'contact', '', '');
			echo '<br />';
		}
		?>
		<?php
		if ($_gallery_page != 'register.php' && function_exists('printRegisterURL') && !npg_loggedin() && ($_gallery_page != 'password.php' || $_gallery->isUnprotectedPage('register'))) {
			printRegisterURL(gettext('Register for this site'), '');
			echo '<br />';
		}
		?>
		<?php if (function_exists('mobileTheme::controlLink')) mobileTheme::controlLink(); ?>
		<?php if (function_exists('printLanguageSelector')) printLanguageSelector(); ?>
		<br class="clearall" />
	</div>
	<!-- Administration Toolbox -->
	<?php
}

function commonNewsLoop($paged) {
	$newstypes = array('album' => gettext('album'), 'image' => gettext('image'), 'video' => gettext('video'), 'news' => NEWS_LABEL);
	while (next_news()) {
		$newstypedisplay = NEWS_LABEL;
		if (stickyNews()) {
			$newstypedisplay .= ' <small><em>' . gettext('sticky') . '</em></small>';
		}
		?>
		<div class="newsarticle<?php if (stickyNews()) echo ' sticky'; ?>">
			<h3><?php printNewsURL(); ?><?php echo " <span class='newstype'>[" . $newstypedisplay . "]</span>"; ?></h3>
			<div class="newsarticlecredit">
				<span class="newsarticlecredit-left">
					<?php
					if (function_exists('getCommentCount')) {
						$count = getCommentCount();
					} else {
						$count = 0;
					}
					$cat = getNewsCategories();
					printNewsDate();
					if ($count > 0) {
						echo ' | ';
						printf(gettext("Comments: %d"), $count);
					}
					?>
				</span>
				<?php
				if (!empty($cat)) {
					echo ' | ';
					printNewsCategories(", ", gettext("Categories: "), "newscategories");
				}
				?>
			</div> <!-- newsarticlecredit -->
			<br clear="all">
			<?php printCodeblock(1); ?>
			<?php printNewsContent(); ?>
			<?php printCodeblock(2); ?>
			<br class="clearall" />
		</div>
		<?php
	}
	if ($paged) {
		printNewsPageListWithNav(gettext('next »'), gettext('« prev'), true, 'pagelist', true);
	}
}

function commonComment() {
	if (function_exists('printCommentForm')) {
		?>
		<div id="commentbox">
			<?php
			if (getCommentErrors() || getCommentCount() == 0) {
				$style = NULL;
				$head = '';
			} else {
				$style = ' class="commentx" style="display:block;"';
				$head = "<div$style><h3>" . gettext('Add a comment') . '</h3></div>';
			}
			printCommentForm(true, $head, true, $style);
			?>
		</div><!-- id="commentbox" -->
		<?php
	}
}

function my_checkPageValidity($request, $gallery_page, $page) {
	switch ($gallery_page) {
		case 'gallery.php':
			$gallery_page = 'index.php'; //	same as an album gallery index
			break;
		case 'index.php':
			if (!getOption('gallery_index')) { // only one index page if CMS plugin is enabled or gallery index page is set
				break;
			}
		default:
			if ($page != 1) {
				return false;
			}
		case 'news.php':
		case 'album.php':
		case 'favorites.php';
		case 'search.php':
			break;
	}
	return checkPageValidity($request, $gallery_page, $page);
}
