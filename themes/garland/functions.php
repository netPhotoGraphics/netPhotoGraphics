<?php
// force UTF-8 Ø
require_once (CORE_SERVERPATH .  PLUGIN_FOLDER . '/image_album_statistics.php');
npgFilters::register('themeSwitcher_head', 'switcher_head');
npgFilters::register('themeSwitcher_Controllink', 'switcher_controllink');

$cwd = getcwd();
chdir(dirname(__FILE__));
$persona = safe_glob('*', GLOB_ONLYDIR);
chdir($cwd);
$personalities = array();
foreach ($persona as $personality) {
	if (file_exists(SERVERPATH . '/' . THEMEFOLDER . '/garland/' . $personality . '/functions.php'))
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
	} else {
		$personality = strtolower(getOption('garland_personality'));
	}
	if (!in_array($personality, $personalities)) {
		$persona = $personalities;
		$personality = array_shift($persona);
	}

	require_once(SERVERPATH . '/' . THEMEFOLDER . '/garland/' . $personality . '/functions.php');
	$_oneImagePage = $handler->onePage();
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
		// ]]> -->
	</script>
	<?php
	return $ignore;
}

function switcher_controllink($html) {
	global $personality, $personalities, $_gallery_page;
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
		<?php @call_user_func('printUserLogin_out', $prev); ?>
		<br />
		<?php @call_user_func('mobileTheme::controlLink'); ?>
		<br />
		<?php @call_user_func('printLanguageSelector'); ?>
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
					$count = @call_user_func('getCommentCount');
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
			<br class="clearall">
			<?php printCodeblock(1); ?>
			<?php printNewsContent(); ?>
			<?php printCodeblock(2); ?>
			<br class="clearall">
		</div>
		<?php
	}
	if ($paged) {
		printNewsPageListWithNav(gettext('next »'), gettext('« prev'), true, 'pagelist', true);
	}
}

function exerpt($content) {
	return shortenContent($content, TRUNCATE_LENGTH, getOption("zenpage_textshorten_indicator"));
}

function my_checkPageValidity($request, $gallery_page, $page) {
	switch ($gallery_page) {
		case 'gallery.php':
			$gallery_page = 'index.php'; //	same as an album gallery index
			break;
		case 'index.php':
			if (!extensionEnabled('zenpage')) { // only one index page if zenpage plugin is enabled or there is a custom index page
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