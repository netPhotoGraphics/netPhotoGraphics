<?php
/**
 * Provides automatic hitcounter counting for gallery objects
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/hitcounter
 * @pluginCategory theme
 */
/** Reset hitcounters ********************************************************** */
/* * ***************************************************************************** */

$plugin_is_filter = defaultExtension(5 | FEATURE_PLUGIN);
$plugin_description = gettext('Automatically increments hitcounters on gallery objects viewed by a <em>visitor</em>.');

$option_interface = 'hitcounter';

npgFilters::register('load_theme_script', 'hitcounter::load_script');
npgFilters::register('admin_utilities_buttons', 'hitcounter::button');
npgFilters::register('bulk_image_actions', 'hitcounter::bulkActions');
npgFilters::register('bulk_album_actions', 'hitcounter::bulkActions');
npgFilters::register('bulk_article_actions', 'hitcounter::bulkActions');
npgFilters::register('bulk_page_actions', 'hitcounter::bulkActions');

$_scriptpage_hitcounters = getSerializedArray(getOption('page_hitcounters'));

/**
 * Plugin option handling class
 *
 */
class hitcounter {

	public $defaultbots = 'Teoma, alexa, froogle, Gigabot,inktomi, looksmart, URL_Spider_SQL, Firefly, NationalDirectory, Ask Jeeves, TECNOSEEK, InfoSeek, WebFindBot, girafabot, crawler, www.galaxy.com, Googlebot, Scooter, Slurp, msnbot, appie, FAST, WebBug, Spade, ZyBorg, rabaz ,Baiduspider, Feedfetcher-Google, TechnoratiSnoop, Rankivabot, Mediapartners-Google, Sogou web spider, WebAlta Crawler';

	function __construct() {
		global $_scriptpage_hitcounters;
		if (OFFSET_PATH == 2) {
			setOptionDefault('hitcounter_ignoreIPList_enable', 0);
			setOptionDefault('hitcounter_ignoreSearchCrawlers_enable', 0);
			setOptionDefault('hitcounter_ignoreIPList', '');
			setOptionDefault('hitcounter_searchCrawlerList', $this->defaultbots);

			$options = getOptionsLike('Page-Hitcounter-');
			foreach ($options as $option => $value) {
				if ($value) {
					$_scriptpage_hitcounters[str_replace('Page-Hitcounter-', '', $option)] = $value;
				}
				purgeOption($option);
			}
			setOptionDefault('page_hitcounters', serialize($_scriptpage_hitcounters));

			$sql = 'UPDATE ' . prefix('plugin_storage') . ' SET `type`="hitcounter",`subtype`="rss" WHERE `type`="rsshitcounter"';
			query($sql);
		}
	}

	function getOptionsSupported() {
		return array(gettext('IP Address list') => array(
						'order' => 1,
						'key' => 'hitcounter_ignoreIPList',
						'type' => OPTION_TYPE_CUSTOM,
						'desc' => gettext('Comma-separated list of IP addresses to ignore.'),
				),
				gettext('Filter') => array(
						'order' => 0,
						'key' => 'hitcounter_ignore',
						'type' => OPTION_TYPE_CHECKBOX_ARRAY,
						'checkboxes' => array(gettext('IP addresses') => 'hitcounter_ignoreIPList_enable', gettext('Search Crawlers') => 'hitcounter_ignoreSearchCrawlers_enable'),
						'desc' => gettext('Check to enable. If a filter is enabled, viewers from in its associated list will not count hits.'),
				),
				gettext('Search Crawler list') => array(
						'order' => 2,
						'key' => 'hitcounter_searchCrawlerList',
						'type' => OPTION_TYPE_TEXTAREA,
						'multilingual' => false,
						'desc' => gettext('Comma-separated list of search bot user agent names.'),
				),
				' ' => array(
						'order' => 3,
						'key' => 'hitcounter_set_defaults',
						'type' => OPTION_TYPE_CUSTOM,
						'desc' => gettext('Reset options to their default settings.')
				)
		);
	}

	function handleOption($option, $currentValue) {
		switch ($option) {
			case 'hitcounter_set_defaults':
				?>
				<script>

					var reset = "<?php echo $this->defaultbots; ?>";
					function hitcounter_defaults() {
						$('#hitcounter_ignoreIPList').val('');
						$('#hitcounter_ip_button').prop('disabled', false);
						$('#__hitcounter_ignoreIPList_enable').prop('checked', false);
						$('#__hitcounter_ignoreSearchCrawlers_enable').prop('checked', false);
						$('#__hitcounter_searchCrawlerList').val(reset);
					}

				</script>
				<label><input id="hitcounter_reset_button" type="button" value="<?php echo gettext('Defaults'); ?>" onclick="hitcounter_defaults();" /></label>
				<?php
				break;
			case 'hitcounter_ignoreIPList':
				?>
				<input type="hidden" name="<?php echo CUSTOM_OPTION_PREFIX; ?>'text-hitcounter_ignoreIPList" value="0" />
				<input type="text" size="30" id="hitcounter_ignoreIPList" name="hitcounter_ignoreIPList" value="<?php echo html_encode($currentValue); ?>" />
				<script>

					function hitcounter_insertIP() {
						if ($('#hitcounter_ignoreIPList').val() == '') {
							$('#hitcounter_ignoreIPList').val('<?php echo getUserIP(); ?>');
						} else {
							$('#hitcounter_ignoreIPList').val($('#hitcounter_ignoreIPList').val() + ',<?php echo getUserIP(); ?>');
						}
						$('#hitcounter_ip_button').prop('disabled', true);
					}
					jQuery(window).on("load", function () {
						var current = $('#hitcounter_ignoreIPList').val();
						if (current.indexOf('<?php echo getUserIP(); ?>') < 0) {
							$('#hitcounter_ip_button').prop('disabled', false);
						}
					});

				</script>
				<label><input id="hitcounter_ip_button" type="button" value="<?php echo gettext('Insert my IP'); ?>" onclick="hitcounter_insertIP();" disabled="disabled" /></label>
				<?php
				break;
		}
	}

	/**
	 *
	 * Counts the hitcounter for the page/object
	 * @param string $script
	 * @param bool $valid will be false if the object is not found (e.g. there will be a 404 error);
	 * @return string
	 */
	static function load_script($script, $valid) {
		if ($script && $valid) {
			if (getOption('hitcounter_ignoreIPList_enable')) {
				$ignoreIPAddressList = explode(',', str_replace(' ', '', getOption('hitcounter_ignoreIPList')));
				$skip = in_array(getUserIP(), $ignoreIPAddressList);
			} else {
				$skip = false;
			}
			if (getOption('hitcounter_ignoreSearchCrawlers_enable') && !$skip && array_key_exists('HTTP_USER_AGENT', $_SERVER) && ($agent = $_SERVER['HTTP_USER_AGENT'])) {
				$botList = explode(',', getOption('hitcounter_searchCrawlerList'));
				foreach ($botList as $bot) {
					if (stripos($agent, trim($bot))) {
						$skip = true;
						break;
					}
				}
			}

			if (!$skip) {
				global $_gallery, $_gallery_page, $_current_album, $_current_image, $_CMS_current_article, $_CMS_current_page, $_CMS_current_category, $_scriptpage_hitcounters;
				if (checkAccess()) {
					// count only if permitted to access
					switch ($_gallery_page) {
						case'index.php':
							if (!npg_loggedin(ADMIN_RIGHTS)) {
								$_gallery->countHit();
							}
							break;
						case 'album.php':
							if (!$_current_album->isMyItem(ALBUM_RIGHTS) && getCurrentPage() == 1) {
								$_current_album->countHit();
							}
							break;
						case 'image.php':
							if (!$_current_album->isMyItem(ALBUM_RIGHTS)) {
								//update hit counter
								$_current_image->countHit();
							}
							break;
						case 'pages.php':
							if (class_exists('CMS') && !$_CMS_current_page->isMyItem(ZENPAGE_PAGES_RIGHTS)) {
								$_CMS_current_page->countHit();
							}
							break;
						case 'news.php':
							if (class_exists('CMS')) {
								if (is_NewsArticle() && !$_CMS_current_article->isMyItem(ZENPAGE_NEWS_RIGHTS)) {
									$_CMS_current_article->countHit();
								} else if (is_NewsCategory() && !$_CMS_current_category->isMyItem(ZENPAGE_NEWS_RIGHTS)) {
									$_CMS_current_category->countHit();
								}
							}
							break;
						default:
							if (!npg_loggedin(ADMIN_RIGHTS)) {
								$page = stripSuffix($_gallery_page);
								if (isset($_scriptpage_hitcounters[$page])) {
									$_scriptpage_hitcounters[$page] = $_scriptpage_hitcounters[$page] + 1;
								} else {
									$_scriptpage_hitcounters[$page] = 1;
								}
								setOption('page_hitcounters', serialize($_scriptpage_hitcounters));
							}
							break;
					}
				}
			}
		}
		return $script;
	}

	static function button($buttons) {
		$buttons[] = array(
				'XSRFTag' => 'hitcounter',
				'category' => gettext('Database'),
				'enable' => true,
				'button_text' => gettext('Reset all hitcounters'),
				'formname' => 'reset_all_hitcounters.php',
				'action' => getAdminLink(PLUGIN_FOLDER . '/hitcounter/reset_hitcounts.php') . '?action=reset_all_hitcounters',
				'icon' => RECYCLE_ICON,
				'alt' => '',
				'title' => gettext('Reset all hitcounters to zero'),
				'rights' => ADMIN_RIGHTS
		);
		return $buttons;
	}

	static function bulkActions($checkarray) {
		$checkarray[gettext('Reset hitcounter')] = 'resethitcounter';
		return $checkarray;
	}

}

/**
 * returns the hitcounter for the current page or for the object passed
 *
 * @param object $obj the album or page object for which the hitcount is desired
 * @return string
 */
function getHitcounter($obj = NULL) {
	global $_current_album, $_current_image, $_gallery, $_gallery_page, $_CMS_current_article, $_CMS_current_page, $_CMS_current_category, $_scriptpage_hitcounters;
	if (is_null($obj)) {
		switch ($_gallery_page) {
			case'index.php';
				$obj = $_gallery;
				break;
			case 'album.php':
				$obj = $_current_album;
				break;
			case 'image.php':
				$obj = $_current_image;
				break;
			case 'pages.php':
				$obj = $_CMS_current_page;
				break;
			case 'news.php':
				if (in_context(ZENPAGE_NEWS_CATEGORY)) {
					$obj = $_CMS_current_category;
				} else {
					$obj = $_CMS_current_article;
				}
				if (is_null($obj))
					return 0;
				break;
			default:
				$page = stripSuffix($_gallery_page);
				if (isset($_scriptpage_hitcounters[$page])) {
					return $_scriptpage_hitcounters[$page];
				} else {
					return NULL;
				}
		}
	}
	return $obj->getHitcounter();
}
?>