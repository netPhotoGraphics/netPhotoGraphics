<?php

/*
 * provide functions, methods, and such that are used by legacy zenphoto
 *
 * This plugin should be enabled if you are using themes or plugins developed
 * for zenphoto 1.4.6 or later.
 *
 * You should udate the theme/plugin you wish to use. Use the LegacyConverter
 * development subtab to alter your scripts to use the appropriate
 * methods and properties.
 *
 * @author Stephen Billard (sbillard)
 *
 * @Copyright 2014-2018 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 * @package plugins/zenphotoCompatibilityPack
 * @pluginCategory development
 */
$plugin_is_filter = defaultExtension(1 | CLASS_PLUGIN);
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext("Zenphoto compatibility.");
}

$legacyReplacements = array(
		'SERVER_HTTP_HOST' => 'FULLHOSTPATH',
		'new ZenpagePage' => 'newPage',
		'new ZenpageNews' => 'newArticle',
		'new ZenpageCategory' => 'newCategory',
		'\$_zp_zenpage' => '$_CMS',
		'\$_zp_CMS' => '$_CMS',
		'ZP_NEWS_ENABLED' => 'extensionEnabled(\'zenpage\') && hasNews()/* TODO:replaced ZP_NEWS_ENABLED */',
		'ZP_PAGES_ENABLED' => 'extensionEnabled(\'zenpage\') && hasPages()/* TODO:replaced ZP_PAGES_ENABLED */',
		'getAllTagsCount\(.*?\)' => 'getAllTagsUnique(NULL, 1, true)',
		'printHeadTitle\(.*?\);?' => '/* TODO:replaced printHeadTitle() */',
		'getSiteHomeURL\(.*?\)' => 'getGalleryIndexURL() /* TODO:replaced getSiteHomeURL() */',
		'printSiteHomeURL\(.*?\);?' => '/* TODO:replaced printSiteHomeURL() */',
		'getNextPrevNews\([\'"](.*)[\'"]\)' => 'get$1News() /* TODO:replaced getNextPrevNews(\'$1\') */',
		'zenpagePublish\((.*)\,(.*)\)' => '$1->setShow($2) /* TODO:replaced zenpagePublish() */',
		'getImageCustomData\(\)' => '($_current_image)?$_current_image->get("custom_data"):NULL /* TODO: Use customFieldExtender to define unique fields */',
		'printImageCategoryCustomData\(\)' => 'echo ($_current_image)?$_current_image->get("custom_data"):NULL /* TODO: Use customFieldExtender to define unique fields */',
		'getAlbumCustomData\(\)' => '($_current_album)?$_current_album->get("custom_data"):NULL /* TODO: Use customFieldExtender to define unique fields */',
		'printAlbumCategoryCustomData\(\)' => 'echo ($_current_album)?$_current_album->get("custom_data"):NULL /* TODO: Use customFieldExtender to define unique fields */',
		'getPageCustomData\(\)' => '($_CMS_current_page)?$_CMS_current_page->get("custom_data"):NULL /* TODO: Use customFieldExtender to define unique fields */',
		'printPageCategoryCustomData\(\)' => 'echo ($_CMS_current_page)?$_CMS_current_page->get("custom_data"):NULL /* TODO: Use customFieldExtender to define unique fields */',
		'getNewsCustomData\(\)' => '($_CMS_current_article)?$_CMS_current_article->get("custom_data"):NULL /* TODO: Use customFieldExtender to define unique fields */',
		'printNewsCustomData\(\)' => 'echo ($_CMS_current_article)?$_CMS_current_article->get("custom_data"):NULL /* TODO: Use customFieldExtender to define unique fields */',
		'getNewsCategoryCustomData\(\)' => '($_CMS_current_category)?$_CMS_current_category->get("custom_data"):NULL /* TODO: Use customFieldExtender to define unique fields */',
		'printNewsCategoryCustomData\(\)' => 'echo ($_CMS_current_category)?$_CMS_current_category->get("custom_data"):NULL /* TODO: Use customFieldExtender to define unique fields */',
		'class_exists\([\'"]Zenpage[\'"]\)' => 'class_exists("CMS")',
		'\$_zp_current_zenpage_news' => '$_CMS_current_article',
		'\$_zp_current_zenpage_page' => '$_CMS_current_page',
		'\$_zp_current_article' => '$_CMS_current_article',
		'\$_zp_current_page' => '$_CMS_current_page',
		'\$_zp_current_category' => '$_CMS_current_category',
		'->getFullImage\(' => '->getFullImageURL(/* TODO: if this was to get the actual filename, revert by removing "URL" */',
		'getFullImage\(' => 'getFullImageURL(',
		'tinymce4_' => 'tinymce_',
		'getSubtabs' => 'getCurrentTab	/* TODO:replaced printSubtabs. Remove this if you do not use the return value */',
		'printSubtabs' => 'getCurrentTab	/* TODO:replaced printSubtabs. Remove this if you do not use the return value */',
		'gettext\(\'news\'\)' => 'NEWS_LABEL', 'gettext("news")' => 'NEWS_LABEL',
		'addThemeCacheSize' => 'addCacheSize',
		'deleteThemeCacheSizes' => 'deleteCacheSizes',
		'cacheManager::addDefaultThumbSize\(.*?\)' => '/* TODO:cacheManager::addDefaultThumbSize removed */',
		'cacheManager::addThemeDefaultThumbSize\(.*?\)' => '/* TODO:cacheManager::addThemeDefaultThumbSize removed */',
		'cacheManager::addDefaultSizedImageSize\(.*?\)' => '/* TODO:cacheManager::addDefaultSizedImageSize removed */',
		'cacheManager::addThemeDefaultSizedImageSize\(.*?\)' => '/* TODO:cacheManager::addThemeDefaultSizedImageSize removed */',
		'exitZP\(\)' => 'exit()',
		'printZenphotoLink\(.*\)' => 'print_SW_Link()',
		'scriptEnabled\(.*?\)' => 'TRUE/* TODO:scriptEnabled removed */',
		'registerScripts\(.*?\)' => '/* TODO:registerScripts removed */',
		'->getAuthor\(' => '->getOwner(',
		'->setAuthor\(' => '->setOwner(',
		'getGeoCoord\(' => 'simpleMap::getCoord(',
		'addGeoCoord\(' => 'simpleMap::addCoord(',
		'edit_admin_custom_data' => 'edit_admin_custom',
		'edit_album_custom_data' => 'edit_album_custom',
		'edit_image_custom_data' => 'edit_image_custom',
		'edit_article_custom_data' => 'edit_article_custom',
		'edit_category_custom_data' => 'edit_category_custom',
		'edit_page_custom_data' => 'edit_page_custom',
		'save_admin_custom_data' => 'save_admin_data',
		'save_album_utilities_data' => 'save_album_data',
		'save_image_utilities_data' => 'save_image_data',
		'ZENPHOTO_VERSION' => 'NETPHOTOGRAPHICS_VERSION',
		'ZENFOLDER' => 'CORE_FOLDER',
		'ZP_ALBUM' => 'NPG_ALBUM',
		'ZP_IMAGE' => 'NPG_IMAGE',
		'ZP_COMMENT' => 'NPG_COMMENT',
		'ZP_SEARCH' => 'NPG_COMMENT',
		'NPG_SEARCH_LINKED' => 'SEARCH_LINKED',
		'NPG_ALBUM_LINKED' => 'ALBUM_LINKED',
		'ZP_IMAGE_LINKED' => 'IMAGE_LINKED',
		'ZP_ZENPAGE_NEWS_PAGE' => 'ZENPAGE_NEWS_PAG',
		'ZP_ZENPAGE_NEWS_ARTICLE' => 'ZENPAGE_NEWS_ARTICLE',
		'ZP_ZENPAGE_NEWS_CATEGORY' => 'ZENPAGE_NEWS_CATEGORY',
		'ZP_ZENPAGE_NEWS_DATE' => 'ZENPAGE_NEWS_DATE',
		'ZP_ZENPAGE_PAGE' => 'ZENPAGE_PAGE',
		'ZP_ZENPAGE_SINGLE' => 'ZENPAGE_SINGLE',
		'\$_zp_captcha' => '$_captcha',
		'\$_zp_gallery_page' => '$_gallery_page',
		'\$_zp_gallery' => '$_gallery',
		'\$_zp_authority' => '$_authority',
		'\$_zp_current_admin_obj' => '$_current_admin_obj',
		'\$_zp_current_search' => '$_current_search',
		'\$_zp_current_image' => '$_current_image',
		'\$_zp_page' => '$_current_page',
		'\$_zp_current_album' => '$_current_album',
		'\$_zp_themeroot' => '$_themeroot',
		'zp-core' => 'CORE_FOLDER',
		'zp-extensions' => 'PLUGIN_FOLDER',
		'zp_register_filter\(' => 'npgFilters::register(',
		'zp_apply_filter\(' => 'npgFilters::apply(',
		'zp_remove_filter\(' => 'npgFilters::remove(',
		'getDataUsageNotice\(\)' => "array('url'=>NULL, 'linktext'=>NULL, 'linktext'=>NULL)/* TODO:replaced getDataUsageNotice Use the GDPR_required plugin instead */",
		'zp_loggedin\(' => 'npg_loggedin(',
		'\$_zp_loggedin' => '$_loggedin',
		'zp_setCookie\(' => 'setNPGCookie(',
		'zp_getCookie\(' => 'getNPGCookie(',
		'zp_clearCookie\(' => 'clearNPGCookie(',
		'zpFunctions::' => 'npgFunctions::',
		'zpFormattedDate\(' => 'formattedDate(',
		'\$_zp_current_DailySummary' => '$_current_DailySummary'
);

class zenPhotoCompatibilityPack {

	static function scriptFilter($param = NULL) {
		//zenphoto variables
		global $_zp_authority, $_zp_loggedin, $_zp_current_admin_obj, $_zp_gallery_page, $_zp_page, $_zp_current_search, $_zp_themeroot, $_zp_current_DailySummary;
		//netPhotoGraphic variables
		global $_authority, $_loggedin, $_current_admin_obj, $_gallery_page, $_current_page, $_current_search, $_themeroot, $_current_DailySummary;

		self::nextObjFilter(NULL, NULL);

		if (is_object($_authority)) {
			$_zp_authority = clone $_authority;
		}
		if (is_object($_current_admin_obj)) {
			$_zp_current_admin_obj = clone $_current_admin_obj;
		}
		if (is_object($_current_search)) {
			$_zp_current_search = clone $_current_search;
		}
		if (is_object($_current_DailySummary)) {
			$_zp_current_DailySummary = clone $_current_DailySummary;
		}
		$_zp_loggedin = $_loggedin;
		$_zp_page = $_current_page;
		$_zp_gallery_page = $_gallery_page;
		$_zp_themeroot = $_themeroot;


		return $param;
	}

	static function nextObjFilter($result, $current) {
		//zenphoto variables
		global $_zp_current_zenpage_news, $_zp_current_zenpage_page, $_zp_current_album, $_zp_current_image;
		//old netPhotoGraphic variables
		global $_zp_current_article, $_zp_current_page, $_zp_current_category;
		//current netPhotoGraphics variables
		global $_CMS_current_article, $_CMS_current_page, $_CMS_current_category, $_current_album, $_current_image;

		if ($_current_album) {
			$_zp_current_album = clone $_current_album;
		} else {
			$_zp_current_album = NULL;
		}
		if ($_current_image) {
			$_zp_current_image = clone $_current_image;
		} else {
			$_zp_current_image = NULL;
		}
		if ($_CMS_current_page) {
			$_zp_current_zenpage_page = clone $_CMS_current_page;
			$_zp_current_page = clone $_CMS_current_page;
		} else {
			$_zp_current_zenpage_page = $_zp_current_page = NULL;
		}
		if ($_CMS_current_article) {
			$_zp_current_zenpage_news = clone $_CMS_current_article;
			$_zp_current_article = clone $_CMS_current_article;
		} else {
			$_zp_current_zenpage_news = $_zp_current_article = NULL;
		}
		if ($_CMS_current_category) {
			$_zp_current_category = clone $_CMS_current_category;
		} else {
			$_zp_current_category = NULL;
		}
		return $result;
	}

	static function admin_tabs($tabs) {
		global $_gallery_page, $_authority, $_current_admin_obj;
		if (npg_loggedin(ADMIN_RIGHTS)) {
			if (!isset($tabs['development'])) {
				$tabs['development'] = array('text' => gettext("development"),
						'link' => getAdminLink(USER_PLUGIN_FOLDER . '/zenphotoCompatibilityPack/legacyConverter.php') . '?page=development&tab=legacyConverter',
						'subtabs' => NULL);
			}
			$tabs['development']['subtabs'][gettext("legacy Converter")] = USER_PLUGIN_FOLDER . '/zenphotoCompatibilityPack/legacyConverter.php?page=development&tab=legacyConverter';
		}
		$_zp_authority = clone $_authority;
		$_zp_current_admin_obj = clone $_current_admin_obj;

		return $tabs;
	}

}

switch (OFFSET_PATH) {
	case 2:
		break;

	default:
		if (class_exists('CMS')) {

			class Zenpage extends CMS {

			}

			class ZenpagePage extends Page {

			}

			class ZenpageNews extends Article {

			}

			class ZenpageCategory extends Category {

			}

			$_zp_zenpage = clone $_CMS;
			$_zp_CMS = clone $_CMS;

			//define the useless legacy definitions
			define('ZP_NEWS_ENABLED', $_CMS->news_enabled);
			define('ZP_PAGES_ENABLED', $_CMS->pages_enabled);
		}

		class zpFunctions extends npgFunctions {

		}

		$_zp_captcha = clone $_captcha;
		$_zp_gallery = clone $_gallery;

		define('ZENFOLDER', CORE_FOLDER);
		define('SERVER_HTTP_HOST', FULLHOSTPATH);

		define("ZP_INDEX", NPG_INDEX);
		define("ZP_ALBUM", NPG_ALBUM);
		define("ZP_IMAGE", NPG_IMAGE);
		define("ZP_COMMENT", NPG_COMMENT);
		define("ZP_SEARCH", NPG_COMMENT);
		define('ZP_SEARCH_LINKED', SEARCH_LINKED);
		define('ZP_ALBUM_LINKED', ALBUM_LINKED);
		define('ZP_IMAGE_LINKED', IMAGE_LINKED);
		define('ZP_ZENPAGE_NEWS_PAGE', ZENPAGE_NEWS_PAGE);
		define('ZP_ZENPAGE_NEWS_ARTICLE', ZENPAGE_NEWS_ARTICLE);
		define('ZP_ZENPAGE_NEWS_CATEGORY', ZENPAGE_NEWS_CATEGORY);
		define('ZP_ZENPAGE_NEWS_DATE', ZENPAGE_NEWS_DATE);
		define('ZP_ZENPAGE_PAGE', ZENPAGE_PAGE);
		define('ZP_ZENPAGE_SINGLE', ZENPAGE_SINGLE);

		npgFilters::register('load_theme_script', 'zenphotoCompatibilityPack::scriptFilter', 99999);
		npgFilters::register('next_object_loop', 'zenphotoCompatibilityPack::nextObjFilter', 99999);
		npgFilters::register('admin_tabs', 'zenphotoCompatibilityPack::admin_tabs');
}

