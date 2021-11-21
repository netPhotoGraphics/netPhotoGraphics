<?php

/**
 * Normally a <em>private</em> gallery is not accessable to anyone but the registered
 * users. There is a gallery option to expose particular pages (such as the index.php page
 * or the register.php page) to site visitors.
 *
 * This plugin extends the concept of "public" access to parts of a <em>private</em> gallery
 * to include CMS items such as specific <em>pages</em> or article <em>categories</em>. (This is probably
 * not very useful unless the <code>index.php</code> script is also made public in the gallery options!)
 *
 * Any CMS <em>page</em> or <em>category</em> you select in the options will be accessable by not logged in site
 * visitors. In addition, articles that are not in a <em>category</em> can also be accessed.
 * (Public sites also have this behavior--password protection only applies to defined
 * CMS <em>categories</em>.)
 *
 *
 * @author Stephen Billard (sbillard)
 *
 * @Copyright 2019 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 * @pluginCategory admin
 * @package plugins/publicCMS
 *
 */
$plugin_is_filter = 9 | FEATURE_PLUGIN;
$plugin_description = gettext('Provide public CMS <em>pages</em> and <em>categories</em> in a private gallery.');
$plugin_disable = extensionEnabled('zenpage') ? '' : gettext('This plugin is not useful if the <code>zenpage</code> plugin is not enabled.');

$option_interface = 'publicCMS';

npgFilters::register('isUnprotectedPage', 'publicCMS::test');
npgFilters::register('isPublicCategory', 'publicCMS::allowCategory');

$_publicPages = getSerializedArray(getOption('publicCMS_pages'));
$_publicCategories = getSerializedArray(getOption('publicCMS_categories'));

class publicCMS {

	function getOptionsSupported() {
		global $_CMS;
		$pageList = $categoryList = [];
		if (class_exists('CMS')) {
			foreach ($_CMS->getPages() as $page) {
				$pageList[get_language_string($page['title'])] = $page['titlelink'];
			}
			foreach ($_CMS->getAllCategories() as $category) {
				$categoryList[get_language_string($category['title'])] = $category['titlelink'];
			}
		}
		$options = array(
				gettext('Public pages') => array(
						'key' => 'publicCMS_pages',
						'order' => 2,
						'type' => OPTION_TYPE_CHECKBOX_ARRAY_UL,
						'checkboxes' => $pageList,
						'desc' => gettext('Check the pages you want to be public.')
				),
				gettext('Public categories') => array(
						'key' => 'publicCMS_categories',
						'order' => 3,
						'type' => OPTION_TYPE_CHECKBOX_ARRAY_UL,
						'checkboxes' => $categoryList,
						'desc' => gettext('Check the categories you want to be public. Note: uncategorized articles are by definition public.')
				)
		);
		if (GALLERY_SECURITY == 'public') {
			$options['note'] = array(
					'key' => 'publicCMS_note',
					'order' => 1,
					'type' => OPTION_TYPE_NOTE,
					'desc' => '<div class="warningbox">' . gettext('Note: your site is public, so this plugin is of little use.') . '</div>'
			);
		}
		return $options;
	}

	static function test($allow, $page) {
		global $_publicPages, $_CMS_current_page, $_CMS_current_article;
		switch ($page) {
			case 'pages':
				return in_array($_CMS_current_page->getTitleLink(), $_publicPages);
			case 'news':
				if ($_CMS_current_article && in_context(ZENPAGE_SINGLE)) {
					return self::allowCategory($_CMS_current_article->getCategories());
				}
				return true; // by definition, the news list page is public
		}
		return $allow;
	}

	static function allowCategory($allow, $catlist) {
		global $_publicCategories;
		if (empty($catlist)) {
			return true;
		}
		$categories = array();
		foreach ($catlist as $category) {
			$categories[] = $category['titlelink'];
		}
		return count(array_intersect($categories, $_publicCategories));
	}

}
