<?php
// force UTF-8 Ø
require_once (PLUGIN_SERVERPATH . 'image_album_statistics.php');
npgFilters::register('themeSwitcher_head', 'switcher_head');
npgFilters::register('themeSwitcher_Controllink', 'switcher_controllink');

$cwd = getcwd();
chdir(__DIR__);
$persona = safe_glob('*', GLOB_ONLYDIR);
chdir($cwd);
$personalities = array();
foreach ($persona as $personality) {
	if (file_exists(__DIR__ . '/' . $personality . '/functions.php'))
		$personalities[ucfirst(str_replace('_', ' ', $personality))] = $personality;
}

if (!OFFSET_PATH) {
	if (class_exists('themeSwitcher')) {
		$personality = themeSwitcher::themeSelection('themePersonality', $personalities);
		if ($personality) {
			setOption('garland_personality', $personality, false);
		} else {
			$personality = strtolower(getOption('garland_personality'));
		}
		$sets = getMenuSets();
		$sets[] = ''; //	the built-in menu
		$themeMenu = themeSwitcher::themeSelection('themeMenu', $sets);
		setOption('garland_menu', $themeMenu, false);
	} else {
		$personality = strtolower(getOption('garland_personality'));
	}
	if (!in_array($personality, $personalities)) {
		$persona = $personalities;
		$personality = reset($persona);
	}
	require_once(__DIR__ . '/' . $personality . '/functions.php');
	$_current_page_check = 'my_checkPageValidity';
}

function switcher_head($ignore) {
	?>
	<script type="text/javascript">
		// <!-- <![CDATA[
		function switchPersonality() {
			personality = $('#themePersonality').val();
			window.location = '?themePersonality=' + personality;
		}
		function switchMenu() {
			personality = $('#themeMenu').val();
			window.location = '?themeMenu=' + personality;
		}

		// ]]> -->
	</script>
	<?php
	return $ignore;
}

function switcher_controllink($html) {
	global $personality, $personalities, $themeMenu, $_gallery_page;
	if (!$personality) {
		$personality = getOption('garland_personality');
	}
	?>
	<span id="themeSwitcher_garland">
		<span title="<?php echo gettext("Garland image display handling."); ?>">
			<?php echo gettext('Personality'); ?>
			<select name="themePersonality" id="themePersonality" onchange="switchPersonality();">
				<?php generateListFromArray(array($personality), $personalities, false, true); ?>
			</select>
		</span>
		<?php
		$menus = getMenuSets();
		if ($menus) {
			if (!$themeMenu) {
				$themeMenu = getOption('garland_menu');
			}
			?>
			<span title="<?php echo gettext("Garland menu."); ?>">
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
	return $html;
}

function footer() {
	global $_gallery_page, $_CMS_current_category, $_gallery;
	?>
	<div id="footer">
		<?php
		if (function_exists('printFavoritesURL') && $_gallery_page != 'password.php' && $_gallery_page != 'favorites.php') {
			printFavoritesURL(NULL, '', ' | ', '<br />');
		}
		if (class_exists('RSS')) {
			$prev = ' | ';
			switch ($_gallery_page) {
				default:
					printRSSLink('Gallery', '', 'RSS', '');
					break;
				case 'album.php':
					printRSSLink('Album', '', 'RSS', '');
					break;
				case 'news.php':
					if (is_NewsCategory()) {
						printRSSLink('Category', '', 'RSS', '', true, null, '', NULL, $_CMS_current_category->getTitlelink());
					} else {
						printRSSLink('News', '', 'RSS', '');
					}
					break;
				case 'password.php':
					$prev = '';
					break;
			}
		} else {
			$prev = '';
		}
		if ($_gallery_page != 'password.php' && $_gallery_page != 'archive.php') {
			printCustomPageURL(gettext('Archive View'), 'archive', '', $prev, '');
			$prev = ' | ';
		}
		if (extensionEnabled('daily-summary')) {
			printDailySummaryLink(gettext('Daily summary'), '', $prev, '');

			$prev = ' | ';
		}
		if ($_gallery_page != 'contact.php' && extensionEnabled('contact_form') && ($_gallery_page != 'password.php' || $_gallery->isUnprotectedPage('contact'))) {
			printCustomPageURL(gettext('Contact us'), 'contact', '', $prev, '');
			$prev = ' | ';
		}
		?>
		<?php
		if ($_gallery_page != 'register.php' && function_exists('printRegisterURL') && !npg_loggedin() && ($_gallery_page != 'password.php' || $_gallery->isUnprotectedPage('register'))) {
			printRegisterURL(gettext('Register for this site'), $prev, '');
			$prev = ' | ';
		}
		?>
		<?php if (function_exists('printUserLogin_out')) printUserLogin_out($prev); ?>
		<br />
		<?php if (function_exists('mobileTheme::controlLink')) mobileTheme::controlLink(); ?>
		<br />
		<?php if (function_exists('printLanguageSelector')) printLanguageSelector(); ?>
		<?php print_SW_Link(); ?>
	</div>
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
					};
					$cat = getNewsCategories();
					printNewsDate();
					if ($count > 0) {
						echo ' | ';
						printf(gettext("Comments: %d"), $count);
					}
					?>
				</span>
				<?php
				if (!empty($cat) && !in_context(ZENPAGE_NEWS_CATEGORY)) {
					echo ' | ';
					printNewsCategories(", ", gettext("Categories: "), "newscategories");
				}
				?>
			</div> <!-- newsarticlecredit -->
			<br class="clearall" />
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

function my_checkPageValidity($request, $gallery_page, $page) {
	switch ($gallery_page) {
		case 'gallery.php':
			$gallery_page = 'index.php'; //	same as an album gallery index
			break;
		case 'index.php':
			if (!class_exists('CMS')) { // only one index page if CMS plugin is enabled or there is a custom index page
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
?>