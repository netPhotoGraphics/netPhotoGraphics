<?php

/*
 * Applies browser differed loading to image content.
 *
 * There are options to control how the lazy loading is applied.
 * Since thumbnails are typically small images lazy loading is by default not enabled
 * for them. You can override this by setting the "Thumbnails" option.
 * For videos, the differed loading is invoked only if there is a poster image.
 *
 * For both thumbnails and images there is an option to load the first "n" occurrences
 * normally. It is generally considered undesirable to lazy load items that are in
 * the first visible viewport as this can detract from the user experience. These options
 * help you tune the lazy loading behavior to your viewport
 *
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/lazyLoader
 * @pluginCategory media
 *
 * @Copyright 2020 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 */

$plugin_is_filter = defaultExtension(9 | FEATURE_PLUGIN);
$plugin_description = gettext('A plugin to apply lazy loading to images and videos.');

$option_interface = 'lazyLoader';

lazyLoader::register();

class lazyLoader {

	private static $imageFilters = array(
			'standard_image_html',
			'custom_image_html'
	);
	private static $thumbFilters = array(
			'standard_album_thumb_html',
			'custom_album_thumb_html',
			'standard_image_thumb_html'
	);

	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('lazyLoader_Thumbnails', 0);
			setOptionDefault('lazyLoader_Images', 1);
			setOptionDefault('lazyLoader_Video', 1);
			setOptionDefault('lazyLoader_SkipImages', 1);
			setOptionDefault('lazyLoader_SkipThumbs', 10);
		}
	}

	function getOptionsSupported() {
		return array(
				gettext('Thumbnails') => array('key' => 'lazyLoader_Thumbnails', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 'as_defined',
						'desc' => gettext('Apply lazy loading to thumbnails.')
				),
				gettext('Skip thumbnails') => array('key' => 'lazyLoader_SkipThumbs', 'type' => OPTION_TYPE_NUMBER,
						'desc' => sprintf(ngettext('Do not apply lazy loading to the first thumbnail displayed.', 'Do not apply lazy loading to the first %1$d thumbnails displayed.', getOption('lazyLoader_SkipImages')), getOption('lazyLoader_SkipImages'))
				),
				gettext('Images') => array('key' => 'lazyLoader_Images', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Apply lazy loading to images.')
				),
				gettext('Skip images') => array('key' => 'lazyLoader_SkipImages', 'type' => OPTION_TYPE_NUMBER,
						'desc' => sprintf(ngettext('Do not apply lazy loading to the first image displayed.', 'Do not apply lazy loading to the first %1$d images displayed.', getOption('lazyLoader_SkipImages')), getOption('lazyLoader_SkipImages'))
				),
				gettext('Videos') => array('key' => 'lazyLoader_Video', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('Delaly loading of videos if there is a poster image.')
				)
		);
	}

	static function register() {
		if (getoption('lazyLoader_Video')) {
			npgFilters::register('standard_video_html', 'lazyLoader::videoHtml');
		}
		if (getoption('lazyLoader_Images')) {
			foreach (self::$imageFilters as $filter) {
				npgFilters::register($filter, 'lazyLoader::imageHtml');
			}
		}
		if (getoption('lazyLoader_Thumbnails')) {
			foreach (self::$thumbFilters as $filter) {
				npgFilters::register($filter, 'lazyLoader::thumbHtml');
			}
		}
	}

	static function imageHtml($html) {
		global $_lazyLoder_imageCount;
		if (++$_lazyLoder_imageCount > getOption('lazyLoader_SkipImages')) {
			$html = preg_replace('~<\s*img(.+)\s*/>~', '<img $1 loading="lazy" />', $html);
		}
		return $html;
	}

	static function thumbHtml($html) {
		global $_lazyLoder_thumbCount;
		if (++$_lazyLoder_thumbCount > getOption('lazyLoader_SkipThumbs')) {
			$html = preg_replace('~<\s*img(.+)\s*/>~', '<img $1 loading="lazy" />', $html);
		}
		return $html;
	}

	static function videoHtml($html) {
		//	do not pre-load video if there is a poster
		if (strpos($html, 'poster=') !== FALSE) {
			$html = preg_replace('~<\s*video(.+)\s*>~', '<video $1 preload="none" >', $html);
		}
		return $html;
	}

}
