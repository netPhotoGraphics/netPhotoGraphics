<?php

// force UTF-8 Ø

/* Plug-in for theme option handling
 * The Admin Options page tests for the presence of this file in a theme folder
 * If it is present it is linked to with a require_once call.
 * If it is not present, no theme options are displayed.
 *
 */

class ThemeOptions {

	function __construct($setDefaultOptions) {

		$me = basename(__DIR__);
		setThemeOptionDefault('zenpage_zp_index_news', false);
		setThemeOptionDefault('Allow_search', true);
		setThemeOptionDefault('Use_thickbox', true);
		setThemeOptionDefault('zenpage_homepage', 'none');
		setThemeOptionDefault('zenpage_contactpage', true);
		setThemeOptionDefault('zenpage_custommenu', false);
		setThemeOptionDefault('albums_per_page', 6);
		setThemeOptionDefault('images_per_page', 20);
		setThemeOptionDefault('images_per_row', 5);
		setThemeOption('image_size', 580, NULL);
		setThemeOption('image_use_side', 'longest', NULL);
		setThemeOption('thumb_size', 95, NULL);
		setThemeOptionDefault('thumb_crop_width', 95);
		setThemeOptionDefault('thumb_crop_height', 95);
		setThemeOptionDefault('thumb_crop', 1);
		setThemeOptionDefault('thumb_transition', true);

		if (class_exists('cacheManager')) {
			cacheManager::deleteCacheSizes($me);
			cacheManager::addCacheSize($me, NULL, 580, 580, NULL, NULL, NULL, NULL, NULL, false, NULL, true);
			cacheManager::addCacheSize($me, 95, NULL, NULL, getThemeOption('thumb_crop_width'), getThemeOption('thumb_crop_height'), NULL, NULL, true, NULL, NULL, NULL);
		}
		if (function_exists('menuExists') && !menuExists('zenpage')) {
			$menuitems = array(
					array('type' => 'menulabel', 'title' => NEWS_LABEL, 'link' => '', 'show' => 1, 'nesting' => 0),
					array('type' => 'menufunction', 'title' => getAllTranslations('All'),
							'link' => 'printAllNewsCategories("All",TRUE,"","menu-active",false,"inner_ul",false,"list",false,getOption("menu_manager_truncate_string"));',
							'show' => 1, 'include_li' => 0, 'nesting' => 1),
					array('type' => 'html', 'title' => getAllTranslations('Articles Rule'), 'link' => '<li class="menu_rule menu_menulabel"></li>', 'show' => 1, 'include_li' => 0, 'nesting' => 0),
					array('type' => 'menulabel', 'title' => gettext('Gallery'), 'link' => '', 'show' => 1, 'nesting' => 0),
					array('type' => 'custompage', 'title' => getAllTranslations('All'), 'link' => 'gallery', 'show' => 1, 'nesting' => 1),
					array('type' => 'menufunction', 'title' => getAllTranslations('Album list'), 'link' => 'printAlbumMenuList("list",NULL,"","menu-active","inner_ul","menu-active","",false,false,false,false,getOption("menu_manager_truncate_string"));', 'show' => 1, 'include_li' => 0, 'nesting' => 1),
					array('type' => 'html', 'title' => getAllTranslations('Gallery Rule'), 'link' => '<li class="menu_rule menu_menulabel"></li>', 'show' => 1, 'include_li' => 0, 'nesting' => 0),
					array('type' => 'menulabel', 'title' => getAllTranslations('Pages'), 'link' => '', 'show' => 1, 'nesting' => 0),
					array('type' => 'menufunction', 'title' => getAllTranslations('All'), 'link' => 'printPageMenu("list","","menu-active","inner_ul","menu-active","",0,false,getOption("menu_manager_truncate_string"));', 'show' => 1, 'include_li' => 0, 'nesting' => 1, getOption("menu_manager_truncate_string"))
			);
			createMenu($menuitems, 'zenpage');
		}
	}

	function getOptionsSupported() {
		$unpublishedpages = query_full_array("SELECT title,titlelink FROM " . prefix('pages') . " WHERE `show` != 1 ORDER by `sort_order`");
		$list = array();
		foreach ($unpublishedpages as $page) {
			$list[get_language_string($page['title'])] = $page['titlelink'];
		}
		return array(gettext('Allow search') => array(
						'key' => 'Allow_search',
						'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Check to enable search form.')
				),
				gettext('Use Colorbox') => array(
						'key' => 'Use_thickbox',
						'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Check to display of the full size image with Colorbox.')
				),
				gettext('News on index page') => array(
						'key' => 'zenpage_zp_index_news',
						'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext("Enable this if you want to show the news section’s first page on the <code>index.php</code> page.")
				),
				gettext('Homepage') => array(
						'key' => 'zenpage_homepage',
						'type' => OPTION_TYPE_SELECTOR,
						'selections' => $list,
						'null_selection' => gettext('none'),
						'desc' => gettext("Choose here any <em>un-published Zenpage page</em> (listed by <em>titlelink</em>) to act as your site’s homepage instead the normal gallery index.") . "<p class='notebox'>" . gettext("<strong>Note:</strong> This of course overrides the <em>News on index page</em> option and your theme must be setup for this feature! Visit the theming tutorial for details.") . "</p>"
				),
				gettext('Use standard contact page') => array(
						'key' => 'zenpage_contactpage',
						'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Disable this if you do not want to use the separate contact page with the contact form. You can also use the codeblock of a page for this. See the contact_form plugin documentation for more info.')
				),
				gettext('Use custom menu') => array(
						'key' => 'zenpage_custommenu',
						'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Check this if you want to use the <em>menu_manager</em> plugin if enabled to build a custom menu instead of the separate standard ones. A standard menu named "zenpage" is created and used automatically.')
				)
		);
	}

	function getOptionsDisabled() {
		return array('image_size', 'thumb_size');
	}

	function handleOption($option, $currentValue) {

	}

}

?>