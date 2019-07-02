<?php

/*
 *
 * This plugin provides a mechanism to mechanically translate Titles, Descriptions, and CMS content
 * of an object. Checkboxes are placed on the object's edit page for each of these
 * translatable properties. If the box is checked, the text associated with the user's locale (e.g. <code>%LOCALE%</code>)
 * will be translated into the other enabled languages. (You should carefully consider what languages
 * to enable as the translation process is resource intensive.)
 *
 * The plugin uses the Google Translation API.
 * It is based on the {@link https://statickidz.com/ Statickidz} GoogleTranslator class by
 * Adrián Barrio Andrés and Paris N. Baltazar Salguero.
 * Source language text submitted for translation is limited to approximately 5000 characters.
 * The PHP <code>Curl</code> extension must be enabled.
 *
 * <b>Note 1:</b> Mechanical translations such as supplied by this plugin are intended as
 * a starting point. They may not accurately represent the content translated nor may they be
 * gramatically proper.
 *
 * <b>Note 2:</b> The Google Translate site monitors the amount of traffic from your computer and
 * may suspend translations thinking (rightfully) that it is a robot asking for them. If this happens,
 * any content changes you have made will <b>NOT</b> be saved as you will be redirected to a Google "sorry" web page
 * explaining that they have detected unusual traffic from your computer. We suggest that you save any edits
 * you have made <b>BEFORE</b> attempting the translations.
 *
 *
 * @Copyright 2019 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/googleTranslate
 * @pluginCategory development
 */

$plugin_is_filter = 5 | ADMIN_PLUGIN;
$plugin_description = gettext('Provides a Google translation facility.');
$plugin_disable = !function_exists('curl_version') ? gettext('The PHP <em>Curl</em> extension must be enabled for this plugin to function.') : false;

require_once(CORE_SERVERPATH .  PLUGIN_FOLDER . '/googleTranslate/GoogleTranslate.php');

npgFilters::register('edit_cms_utilities', 'translator::cms_utilities');
npgFilters::register('save_article_data', 'translator::save');
npgFilters::register('save_page_data', 'translator::save');
npgFilters::register('save_category_data', 'translator::save');

npgFilters::register('edit_album_utilities', 'translator::media_utilities');
npgFilters::register('edit_image_utilities', 'translator::media_utilities');
npgFilters::register('save_album_data', 'translator::save');
npgFilters::register('save_image_data', 'translator::save');

use \Statickidz\GoogleTranslate;

$trans = new GoogleTranslate();

class translator {

	static function doTranslation($sourceLocale, $obj, $field) {
		global $trans;
		$active_languages = array_flip(i18n::generateLanguageList());
		unset($active_languages[$sourceLocale]);
		$getField = 'get' . $field;
		$source = $obj->$getField();
		$text = get_language_string($source, $sourceLocale);
		if ($text) { //	don't bother if there is no text
			$translations = array($sourceLocale => $text);

			//remove comments to be added back later
			preg_match_all('~<!--.*-->~isU', $text, $comments);
			$text = preg_replace('~<!--.*-->~isU', '<_Comment_>', $text);
			$comments = $comments[0];

			//remove scripts to be added back later
			preg_match_all('~<script.*>.*</script>~isU', $text, $scripts);
			$text = preg_replace('~<script.*>.*</script>~isU', '<_Script_>', $text);
			$scripts = $scripts[0];

			//separate out the HTML
			preg_match_all("~</?\w+((\s+(\w|\w[\w-]*\w)(\s*=\s*(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)/?>~i", $text, $markup);
			$markup = $markup[0];

			foreach ($markup as $key => $mark) {
				switch ($mark) {
					case '<_Script_>':
						$markup[$key] = array_shift($scripts);
						break;
					case '<_Comment_>':
						$markup[$key] = array_shift($comments);
						break;
				}
			}

			$parts = preg_split("~</?\w+((\s+(\w|\w[\w-]*\w)(\s*=\s*(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)/?>~i", $text);
			$offered = implode('<p>', $parts);
			foreach ($active_languages as $target => $l) {
				$translated = $trans->translate($sourceLocale, $target, $offered);
				$parts = explode('<p>', $translated);
				$translated = '';
				foreach ($parts as $key => $part) {
					$translated .= $part;
					if (isset($markup[$key])) {
						$translated .= $markup[$key];
					}
				}
				$translations[$target] = $translated;
			}
			$setField = 'set' . $field;
			$obj->$setField(serialize($translations));
		}
	}

	static function media_utilities($before, $object, $instance = NULL) {
		if ($before) {
			$before .= '<hr />';
		}
		$output = '<label>' . "\n" .
						'<input type="checkbox" name="translateTitle' . $instance . '" id="translateTitle' . $instance .
						'" value="1" /> ' . gettext('Translate Title') . "\n</label>\n";
		$output .= '<label><br />' . "\n" .
						'<input type="checkbox" name="translateDesc' . $instance . '" id="translateDesc' . $instance .
						'" value="1" /> ' . gettext('Translate Description') . "\n</label>\n";
		return $before . $output;
	}

	static function cms_utilities($before, $object) {
		if ($before) {
			$before .= '<hr />';
		}
		$output = '<p class="checkbox">' . "\n" . '<label>' . "\n" .
						'<input type="checkbox" name="translateTitle' . '" id="translateTitle' .
						'" value="1" /> ' . gettext('Translate Title') . "\n</label>\n</p>\n";
		if (get_class($object) == 'Category') {
			$output .= '<p class="checkbox">' . "\n" . '<label>' . "\n" .
							'<input type="checkbox" name="translateDesc' . '" id="translateDesc' .
							'" value="1" /> ' . gettext('Translate Description') . "\n</label>\n</p>\n";
		} else {
			$output .= '<p class="checkbox">' . "\n" . '<label>' . "\n" .
							'<input type="checkbox" name="translateContent' . '" id="translateContent' .
							'" value="1" /> ' . gettext('Translate Content') . "\n</label>\n</p>\n";
			$fields = db_list_fields($object->table);
			if (isset($fields['extracontent'])) {
				$output .= '<p class="checkbox">' . "\n" . '<label>' . "\n" .
								'<input type="checkbox" name="translateExtraContent' . '" id="translateExtraContent' .
								'" value="1" /> ' . gettext('Translate Extra Content') . "\n</label>\n</p>\n";
			}
		}
		return $before . $output;
	}

	static function save($obj, $instance = NULL) {
		$sourceLocale = i18n::getUserLocale();
		if (isset($_POST['translateTitle' . $instance])) {
			translator::doTranslation($sourceLocale, $obj, 'title');
		}
		if (isset($_POST['translateContent' . $instance])) {
			translator::doTranslation($sourceLocale, $obj, 'Content');
		}
		if (isset($_POST['translateExtraContent' . $instance])) {
			translator::doTranslation($sourceLocale, $obj, 'ExtraContent');
		}
		if (isset($_POST['translateDesc' . $instance])) {
			translator::doTranslation($sourceLocale, $obj, 'Desc');
		}
	}

}
