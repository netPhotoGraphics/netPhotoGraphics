<?php
/**
 * With Zenpage you can extend the easy to use interface to manage an entire site with a news section (blog) for
 * announcements. Considering netPhotoGraphic's image, video and audio management capabilites this is the ideal solution for
 * personal portfolio sites of artists, graphic/web designers, illustrators, musicians, multimedia/video artists,
 * photographers and many more.
 *
 * You could even run an audio or podcast blog with netPhotoGraphic and zenpage.
 *
 * <b>Features</b>
 * <ol>
 * <li>Custom page management</li>
 * <li>News section with nested categories (blog)</li>
 * <li>Tags for pages and news articles</li>
 * <li>Page and news category password protection</li>
 * <li>Scheduled publishing</li>
 * <li>RSS feed for news articles</li>
 * <li>Comments on news articles and pages incl. subscription via RSS</li>
 * </ol>
 *
 *
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @package plugins/zenpage
 * @pluginCategory theme
 */
$plugin_is_filter = defaultExtension(99 | CLASS_PLUGIN);
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext("A CMS plugin that adds the capability to run an entire gallery focused website.");
}
$option_interface = 'cmsFilters';

//Zenpage rewrite definitions
$_conf_vars['special_pages']['news'] = array('define' => '_NEWS_', 'rewrite' => getOption('NewsLink'),
		'option' => 'NewsLink', 'default' => 'news');
$_conf_vars['special_pages']['category'] = array('define' => '_CATEGORY_', 'rewrite' => getOption('categoryLink'),
		'option' => 'categoryLink', 'default' => '_NEWS_/category');
$_conf_vars['special_pages']['news_archive'] = array('define' => '_NEWS_ARCHIVE_', 'rewrite' => getOption('NewsArchiveLink'),
		'option' => 'NewsArchiveLink', 'default' => '_NEWS_/archive');
$_conf_vars['special_pages']['pages'] = array('define' => '_PAGES_', 'rewrite' => getOption('PagesLink'),
		'option' => 'PagesLink', 'default' => 'pages');

$_conf_vars['special_pages'][] = array('definition' => '%NEWS%', 'rewrite' => '_NEWS_');
$_conf_vars['special_pages'][] = array('definition' => '%CATEGORY%', 'rewrite' => '_CATEGORY_');
$_conf_vars['special_pages'][] = array('definition' => '%NEWS_ARCHIVE%', 'rewrite' => '_NEWS_ARCHIVE_');
$_conf_vars['special_pages'][] = array('definition' => '%PAGES%', 'rewrite' => '_PAGES_');

$_conf_vars['special_pages'][] = array('rewrite' => '^%PAGES%/*$',
		'rule' => '%REWRITE% index.php?p=pages [NC,L,QSA]');
$_conf_vars['special_pages'][] = array('rewrite' => '^%PAGES%/(.+?)/*$',
		'rule' => '%REWRITE% index.php?p=pages&title=$1 [L, QSA]');
$_conf_vars['special_pages'][] = array('rewrite' => '^%CATEGORY%/(.+)/([0-9]+)/*$',
		'rule' => '%REWRITE% index.php?p=news&category=$1&page=$2 [NC,L,QSA]');
$_conf_vars['special_pages'][] = array('rewrite' => '^%CATEGORY%/(.+?)/*$',
		'rule' => '%REWRITE% index.php?p=news&category=$1 [NC,L,QSA]');
$_conf_vars['special_pages'][] = array('rewrite' => '^%NEWS_ARCHIVE%/(.+)/([0-9]+)/*$',
		'rule' => '%REWRITE% index.php?p=news&date=$1&page=$2 [NC,L,QSA]');
$_conf_vars['special_pages'][] = array('rewrite' => '^%NEWS_ARCHIVE%/(.+?)/*$',
		'rule' => '%REWRITE% index.php?p=news&date=$1 [NC,L,QSA]');
$_conf_vars['special_pages'][] = array('rewrite' => '^%NEWS%/([0-9]+)/*$',
		'rule' => '%REWRITE% index.php?p=news&page=$1 [NC,L,QSA]');
$_conf_vars['special_pages'][] = array('rewrite' => '^%NEWS%/(.+?)/*$',
		'rule' => '%REWRITE% index.php?p=news&title=$1 [NC,L,QSA]');
$_conf_vars['special_pages'][] = array('rewrite' => '^%NEWS%/*$',
		'rule' => '%REWRITE% index.php?p=news [NC,L,QSA]');


npgFilters::register('checkForGuest', 'cmsFilters::checkForGuest');
npgFilters::register('isMyItemToView', 'cmsFilters::isMyItemToView');
npgFilters::register('admin_toolbox_global', 'cmsFilters::admin_toolbox_global');
npgFilters::register('admin_toolbox_news', 'cmsFilters::admin_toolbox_news');
npgFilters::register('admin_toolbox_pages', 'cmsFilters::admin_toolbox_pages');
npgFilters::register('themeSwitcher_head', 'cmsFilters::switcher_head');
npgFilters::register('themeSwitcher_Controllink', 'cmsFilters::switcher_controllink', 0);
npgFilters::register('load_theme_script', 'cmsFilters::switcher_setup', 99);

require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/zenpage/classes.php');
require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/zenpage/class-news.php');
require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/zenpage/class-page.php');
require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/zenpage/class-category.php');

$_CMS = new CMS();

class cmsFilters {

	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('zenpage_articles_per_page', '10');
			setOptionDefault('zenpage_text_length', '500');
			setOptionDefault('zenpage_textshorten_indicator', ' (...)');
			setOptionDefault('zenpage_read_more', getAllTranslations('Read more'));
			setOptionDefault('zenpage_enabled_items', 3);
		}
	}

	function getOptionsSupported() {

		$options = array(
				gettext('News label') => array('key' => 'zenpage_news_label', 'type' => OPTION_TYPE_TEXTBOX,
						'multilingual' => true,
						'order' => 0,
						'desc' => gettext('Change this option if you want the news items named something else. This option also changes the rewrite token for <em>news</em>. For multilingual sites, the token will use the site language (if set) defaulting to the string for the current locale, the en_US string, or the first string which ever is present. Note: Themes should be using the define <var>NEWS_LABEL:</var> instead of <var>gettext("News")</var>. The change applies to the front-end only, admin pages still refer to <em>news</em> as news.')),
				'hidden' => array('key' => 'zenpage_news_label_prior', 'type' => OPTION_TYPE_HIDDEN, 'value' => getOption('zenpage_news_label')),
				gettext('Enabled CMS items') => array(
						'key' => 'zenpage_enabled_items',
						'type' => OPTION_TYPE_RADIO,
						'order' => 7,
						'buttons' => array(
								gettext('News') => 1,
								gettext('Pages') => 2,
								gettext('Both') => 3
						),
						'desc' => gettext('Select the CMS features you wish to use on your site.')
				),
				gettext('Articles per page (theme)') => array('key' => 'zenpage_articles_per_page', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 0.5,
						'desc' => gettext("How many news articles you want to show per page on the news or news category pages.")),
				gettext('News article text length') => array('key' => 'zenpage_text_length', 'type' => OPTION_TYPE_NUMBER,
						'order' => 1,
						'desc' => gettext("The length of news article excerpts in news or news category pages. Leave empty for full text.")),
				gettext('News article text shorten indicator') => array('key' => 'zenpage_textshorten_indicator', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 2,
						'desc' => gettext("Something that indicates that the article text is shortened, “ (...)” by default.")),
				gettext('Read more') => array('key' => 'zenpage_read_more', 'type' => OPTION_TYPE_TEXTBOX, 'multilingual' => 1,
						'order' => 3,
						'desc' => gettext("The text for the link to the full article.")),
				gettext('Truncate titles*') => array('key' => 'menu_truncate_string', 'type' => OPTION_TYPE_NUMBER,
						'order' => 23,
						'desc' => gettext('Limit titles to this many characters. Zero means no limit.')),
				gettext('Truncate indicator*') => array('key' => 'menu_truncate_indicator', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 24,
						'desc' => gettext('Append this string to truncated titles.'))
		);

		$options['note'] = array('key' => 'menu_truncate_note',
				'type' => OPTION_TYPE_NOTE,
				'order' => 25,
				'desc' => gettext('<p class="notebox">*<strong>Note:</strong> These options are shared among <em>menu_manager</em>, <em>print_album_menu</em>, and <em>zenpage</em>.</p>'));
		return $options;
	}

	function handleOptionSave($option, $currentValue) {
		$newslabel = getOption('zenpage_news_label');
		if (!empty($newslabel) && $newslabel != getOptionPost('zenpage_news_label_prior')) {
			$newslink = get_language_string(getSerializedArray($newslabel), getOption('locale'));
			setOption('NewsLink', strtolower($newslink));
		}
	}

	static function switcher_head($list) {
		?>
		<script type="text/javascript">
			// <!-- <![CDATA[
			function switchCMS(checked) {
				window.location = '?cmsSwitch=' + checked;
			}
			// ]]> -->
		</script>
		<?php
		return $list;
	}

	static function switcher_controllink($theme) {
		global $_gallery_page;
		if ($_gallery_page == 'pages.php' || $_gallery_page == 'news.php') {
			$disabled = ' disabled="disalbed"';
		} else {
			$disabled = '';
		}
		if (getPlugin('pages.php', $theme)) { // it supports zenpage
			?>
			<span id="themeSwitcher_zenpage" title="<?php echo gettext("Enable Zenpage CMS plugin"); ?>">
				<label>
					Zenpage
					<input type="checkbox" name="cmsSwitch" id="cmsSwitch" value="1"<?php if (extensionEnabled('zenpage')) echo $disabled . ' checked="checked"'; ?> onclick="switchCMS(this.checked);" />
				</label>
			</span>
			<?php
		}
		return $theme;
	}

	static function switcher_setup($ignore) {
		global $_CMS;
		if (class_exists('themeSwitcher') && themeSwitcher::active()) {
			if (isset($_GET['cmsSwitch'])) {
				setOption('themeSwitcher_zenpage_switch', $cmsSwitch = (int) ($_GET['cmsSwitch'] == 'true'));
				if (!$cmsSwitch) {
					enableExtension('zenpage', 0, false);
				}
			}
		}
		if (extensionEnabled('zenpage')) {
			require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/zenpage/template-functions.php');
		} else {
			unset($GLOBALS['_CMS']);
		}
		return $ignore;
	}

// zenpage filters

	/**
	 * Handles password checks
	 * @param string $auth
	 */
	static function checkForGuest($auth) {
		global $_CMS_current_page, $_CMS_current_category;
		if (!is_null($_CMS_current_page)) { // zenpage page
			$authType = $_CMS_current_page->checkforGuest();
			return $authType;
		} else if (!is_null($_CMS_current_category)) {
			$authType = $_CMS_current_category->checkforGuest();
			return $authType;
		}
		return $auth;
	}

	/**
	 * Handles item ownership
	 * returns true for allowed access, false for denied, returns original parameter if not my gallery page
	 * @param bool $fail
	 */
	static function isMyItemToView($fail) {
		global $_gallery_page, $_CMS_current_page, $_CMS_current_article, $_CMS_current_category;
		switch ($_gallery_page) {
			case 'pages.php':
				if (is_object($_CMS_current_page)) {
					return $_CMS_current_page->isMyItem(LIST_RIGHTS);
				}
				return false;
			case 'news.php':
				if (in_context(ZENPAGE_NEWS_ARTICLE)) {
					if ($_CMS_current_article->isMyItem(LIST_RIGHTS)) {
						return true;
					}
				} else { //	must be category or main news page?
					if (npg_loggedin(MANAGE_ALL_NEWS_RIGHTS) || !is_object($_CMS_current_category) || !$_CMS_current_category->isProtected()) {
						return true;
					}
					if (is_object($_CMS_current_category)) {
						if ($_CMS_current_category->isMyItem(LIST_RIGHTS)) {
							return true;
						}
					}
				}
				return false;
		}
		return $fail;
	}

	static function admin_pages() {
		global $_CMS, $_loggedin, $_current_admin_obj;
		$articlestab = $categorystab = $pagestab = false;
		if ($_CMS) {
			if ($_loggedin & ADMIN_RIGHTS) {
				$_loggedin = ALL_RIGHTS;
			} else {
				if ($_loggedin & MANAGE_ALL_NEWS_RIGHTS) {
					// these are lock-step linked!
					$_loggedin = $_loggedin | ZENPAGE_NEWS_RIGHTS;
				}
				if ($_loggedin & MANAGE_ALL_PAGES_RIGHTS) {
					// these are lock-step linked!
					$_loggedin = $_loggedin | ZENPAGE_PAGES_RIGHTS;
				}
			}
			$admin = $_current_admin_obj->getUser();
			if ($_CMS->news_enabled) {
				$articlestab = $categorystab = $_loggedin & (MANAGE_ALL_NEWS_RIGHTS | ZENPAGE_NEWS_RIGHTS);
				if (!$articlestab) {
					$articles = query('SELECT `titlelink` FROM ' . prefix('news') . ' WHERE `owner`=' . db_quote($admin));
					if ($articles) {
						$_loggedin = $_loggedin | ZENPAGE_NEWS_RIGHTS; //	Owners get rights to edit their articles
						$articlestab = true;
					}
				}

				if ($_CMS->pages_enabled) {
					$pagestab = $_loggedin & (MANAGE_ALL_PAGES_RIGHTS | ZENPAGE_PAGES_RIGHTS);
					if (!$pagestab) {
						$pagelist = query('SELECT `titlelink` FROM ' . prefix('pages') . ' WHERE `owner`=' . db_quote($admin));
						if ($pagelist) {
							$_loggedin = $_loggedin | ZENPAGE_PAGES_RIGHTS; //	Owners get rights to edit their pages
							$pagestab = true;
						}
					}
				}
			}
		}
		return array($articlestab, $categorystab, $pagestab);
	}

	/**
	 *
	 * Zenpage admin toolbox links
	 */
	static function admin_toolbox_global() {
		list($articlestab, $categorystab, $pagestab) = self::admin_pages();
		if ($articlestab || $categorystab) {
			// admin has zenpage rights, provide link to the Zenpage admin tab
			echo '<li><a href="' . getAdminLink(PLUGIN_FOLDER . '/zenpage/news.php') . '">' . NEWS_LABEL . '</a></li>';
		}
		if ($pagestab) {
			echo "<li><a href=\"" . getAdminLink(PLUGIN_FOLDER . '/zenpage/pages.php') . '">' . gettext("Pages") . '</a></li>';
		}
	}

	static function admin_toolbox_pages($redirect) {
		global $_CMS, $_CMS_current_page;

		if (npg_loggedin(ZENPAGE_PAGES_RIGHTS) && $_CMS && $_CMS->pages_enabled && ($_CMS_current_page->subrights() & MANAGED_OBJECT_RIGHTS_EDIT)) {
			// page is zenpage page--provide edit, delete, and add links
			?>
			<li>
				<a href="<?php echo getAdminLink(PLUGIN_FOLDER . '/zenpage/edit.php'); ?>?page&amp;edit&amp;titlelink=<?php echo urlencode(getPageTitlelink()); ?>&amp;subpage=object"><?php echo gettext("Edit Page"); ?>
				</a>
			</li>
			<script type='text/javascript'>
				function confirmPageDelete() {
					if (confirm('<?php echo gettext("Are you sure you want to delete the page? THIS CANNOT BE UNDONE!"); ?>')) {
						window.location = '<?php echo getAdminLink(PLUGIN_FOLDER . '/zenpage/edit.php'); ?>?delete=<?php echo $_CMS_current_page->getTitlelink(); ?>&add&XSRFToken=<?php echo getXSRFToken('delete'); ?>';
								}
							}
			</script>
			<li>
				<a href="javascript:confirmPageDelete();" title="<?php echo gettext("Delete page"); ?>"><?php echo gettext("Delete Page"); ?>
				</a>
			</li>
			<?php
			echo '<li><a href="' . getAdminLink(PLUGIN_FOLDER . '/zenpage/edit.php') . '?page&amp;add">' . gettext("Add Page") . '</a></li>';
		}
		return $redirect . '&amp;title=' . urlencode(getPageTitlelink());
	}

	static function admin_toolbox_news($redirect) {
		global $_CMS, $_CMS_current_category, $_CMS_current_article;
		if (!empty($_CMS_current_category)) {
			$cat = '&amp;category=' . $_CMS_current_category->getTitlelink();
		} else {
			$cat = '';
		}

		if (is_NewsArticle()) {
			if (npg_loggedin(ZENPAGE_NEWS_RIGHTS) && $_CMS && $_CMS->news_enabled && ($_CMS_current_article->subrights() & MANAGED_OBJECT_RIGHTS_EDIT)) {
				// page is a NewsArticle--provide zenpage edit, delete, and Add links
				echo '<li><a href="' . getAdminLink(PLUGIN_FOLDER . '/zenpage/edit.php') . '?newsarticle&amp;edit&amp;titlelink=' . html_encode($_CMS_current_article->getTitleLink()) . $cat . '&amp;subpage=object">' . gettext("Edit Article") . '</a></li>';
				?>
				<script type='text/javascript'>
					function confirmArticleDelete() {
						if (confirm('<?php echo gettext("Are you sure you want to delete the article? THIS CANNOT BE UNDONE!"); ?>')) {
							window.location = '<?php echo getAdminLink(PLUGIN_FOLDER . '/zenpage/edit.php'); ?>?delete=<?php echo $_CMS_current_article->getTitlelink(); ?>&XSRFToken=<?php echo getXSRFToken('delete'); ?>';
									}
								}
				</script>
				<li>
					<a href="javascript:confirmArticleDelete();" title="<?php echo gettext("Delete article"); ?>"><?php echo gettext("Delete Article"); ?>	</a>
				</li>
				<?php
				echo '<li><a href="' . getAdminLink(PLUGIN_FOLDER . '/zenpage/edit.php') . '?newsarticle&amp;
add">' . gettext("Add Article") . '</a></li>';
			}
			$redirect .= '&amp;title=' . urlencode($_CMS_current_article->getTitlelink());
		} else {
			$redirect .= $cat;
		}
		return $redirect;
	}

}

/**
 * Returns the full path of the news index page
 *
 * @return string
 */
function getNewsIndexURL() {
	global $_CMS_current_article;
	$p_rewrite = $p = '';
	if (in_context(ZENPAGE_NEWS_ARTICLE) && in_context(ZENPAGE_SINGLE)) {
		$pos = floor(($_CMS_current_article->getIndex() / ARTICLES_PER_PAGE) + 1);
		if ($pos > 1) {
			$p_rewrite = $pos;
			$p = '&page=' . $pos;
		}
	}

	return npgFilters::apply('getLink', rewrite_path(_NEWS_ . '/' . $p_rewrite, "/index.php?p=news" . $p), 'news.php', NULL);
}

/**
 * Returns the full path of the news archive page
 *
 * @param string $date the date of the archive page
 * @return string
 */
function getNewsArchiveURL($date) {
	return npgFilters::apply('getLink', rewrite_path(_NEWS_ARCHIVE_ . '/' . $date . '/', "/index.php?p=news&date=$date"), 'news.php', NULL);
}
?>
