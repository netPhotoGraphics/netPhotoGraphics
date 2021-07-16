<?php

/**
 * lib-i18n.php -- support functions for internationalization
 *
 * @author Stephen Billard (sbillard)
 *
 * @package core
 */
// force UTF-8 Ø

if (!extension_loaded('intl')) {

	require_once(CORE_SERVERPATH . 'localeList.php');

	class locale {

		static function getDisplayName($locale) {
			global $__languages;
			if (isset($__languages[$locale])) {
				return $__languages[$locale];
			}
			return NULL;
		}

	}

	class ResourceBundle {

		static function getLocales($param) {
			ob_start();
			system('locale -a');
			$locales = ob_get_clean();
			return explode("\n", trim($locales));
		}

	}

}

class i18n {

	/**
	 * returns a text string with the "name" of the locale
	 *
	 * @param string $locale
	 * @return string
	 */
	static function getDisplayName($locale) {
		if (!$text = locale::getDisplayName($locale)) {
			$parts = explode('_', $locale);
			if (!$text = locale::getDisplayName($parts[0])) {
				$text = $locale;
			}
		}
		return $text;
	}

	/**
	 * Returns an array of available language locales.
	 *
	 * @return array
	 *
	 */
	static function generateLanguageList($all = false) {
		global $_active_languages, $_all_languages;
		$disallow = getSerializedArray(getOption('locale_disallowed'));
		if (is_null($_all_languages)) {
			$_active_languages = $_all_languages = array();
			foreach (array(CORE_SERVERPATH . 'locale/', USER_PLUGIN_SERVERPATH . 'locale/') as $source) {
				if (is_dir($source)) {
					$dir = opendir($source);
					while ($dirname = readdir($dir)) {
						if (is_dir($source . $dirname) && (substr($dirname, 0, 1) != '.')) {
							$language = self::getDisplayName($dirname);
							$_all_languages[$language] = $dirname;
							if (!isset($disallow[$dirname])) {
								$_active_languages[$language] = $dirname;
							}
						}
					}
					closedir($dir);
				}
			}
			ksort($_all_languages, SORT_LOCALE_STRING);
			ksort($_active_languages, SORT_LOCALE_STRING);
		}
		if ($all) {
			return $_all_languages;
		} else {
			return $_active_languages;
		}
	}

	/**
	 * Wrapper function for setLocale() so that all the proper permutations are used
	 * Returns the result from the setLocale call
	 * @param $locale the local desired
	 * @return string
	 */
	static function setLocale($locale) {
		global $_RTL_css;
		$en2 = str_replace('ISO-', 'ISO', LOCAL_CHARSET);
		$locale_hyphen = str_replace('_', '-', $locale);
		$simple = explode('-', $locale_hyphen);
		$try[$locale . '.UTF8'] = $locale . '.UTF8';
		$try[$locale . '.UTF-8'] = $locale . '.UTF-8';
		$try[$locale . '.@euro'] = $locale . '.@euro';
		$try[$locale . '.' . $en2] = $locale . '.' . $en2;
		$try[$locale . '.' . LOCAL_CHARSET] = $locale . '.' . LOCAL_CHARSET;
		$try[$locale] = $locale;
		$try[$simple[0]] = $simple[0];
		$try['NULL'] = NULL;
		$rslt = setlocale(LC_ALL, $try);

		putenv("LC_ALL=$locale");
		putenv("LANG=$locale");
		putenv("LANGUAGE=$locale");

		if (function_exists('T_setlocale')) { //	using php-gettext
			T_setlocale(LC_ALL, $locale);
		}

		$_RTL_css = in_array(substr($rslt, 0, 2), array('fa', 'ar', 'he', 'hi', 'ur'));
		if (DEBUG_LOCALE) {
			debugLog("setlocale(" . implode(',', $try) . ") returned: $rslt");
		}
		return $rslt;
	}

	/**
	 * Sets up the translation domain and language file path
	 *
	 * @param $domaine If $type "plugin" or "theme" the file/folder name of the theme or plugin
	 * @param $type NULL (main translation), "theme" or "plugin" NP type is deprecated!
	 */
	static function setupDomain($domain = 'core', $type = NULL) {
		global $_active_languages, $_all_languages, $_current_locale;
		$theme = NULL;
		switch ($type) {
			case "theme":
				$theme = $domain;
			case "plugin":
				$domainpath = getPlugin($domain . "/locale/", $theme);
				if ($domainpath) {
					break;
				}
			default:
				$domainpath = self::languageFolder($_current_locale);
				break;
		}
		bindtextdomain($domain, $domainpath);
		bind_textdomain_codeset($domain, 'UTF-8');
		textdomain($domain);
		//invalidate because the locale was not setup until now
		$_active_languages = $_all_languages = NULL;
	}

	static function languageFolder($lang) {
		if (is_dir(USER_PLUGIN_SERVERPATH . 'locale/' . $lang)) {
			return USER_PLUGIN_SERVERPATH . 'locale/';
		} else {
			return CORE_SERVERPATH . 'locale/';
		}
	}

	/**
	 * Setup code for gettext translation
	 * Returns the result of the setlocale call
	 *
	 * @param string $override force locale to this
	 * @return mixed
	 */
	static function setupCurrentLocale($override = NULL) {
		global $_current_locale;
		if (is_null($override)) {
			$locale = getOption('locale');
		} else {
			$locale = $override;
		}
		$disallow = getSerializedArray(getOption('locale_disallowed'));
		$languages = self::generateLanguageList();
		if (isset($disallow[$locale])) {
			if (DEBUG_LOCALE)
				debugLogBacktrace("self::setupCurrentLocale($override): $locale denied by option.");
			$locale = getOption('locale');
			if (empty($locale) || isset($disallow[$locale])) {
				$locale = array_shift($languages);
			}
		}
		// gettext setup
		$result = self::setLocale($locale);
		if (!$result) {
			if (isset($_REQUEST['locale']) || is_null($override)) { // and it was chosen via locale
				if (isset($_REQUEST['oldlocale'])) {
					$locale = sanitize($_REQUEST['oldlocale'], 3);
					setOption('locale', $locale, false);
					clearNPGCookie('dynamic_locale');
				}
			}
		}
		if (DEBUG_LOCALE)
			debugLogBacktrace("self::setupCurrentLocale($override): locale=$locale, \$result=$result");
		$_current_locale = $locale;
		self::setupDomain();
		return $result;
	}

	/**
	 * This function will parse a given HTTP Accepted language instruction
	 * (or retrieve it from $_SERVER if not provided) and will return a sorted
	 * array. For example, it will parse fr;en-us;q=0.8
	 *
	 * Thanks to Fredbird.org for this code.
	 *
	 * @param string $str optional language string
	 * @return array
	 */
	static function parseHttpAcceptLanguage($str = NULL) {
		// getting http instruction if not provided
		if (!$str && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$str = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}
		if (!is_string($str)) {
			return array();
		}
		$langs = explode(',', $str);
		// creating output list
		$accepted = array();
		foreach ($langs as $lang) {
			// parsing language preference instructions
			// 2_digit_code[-longer_code][;q=coefficient]
			if (preg_match('/([A-Za-z]{1,2})(-([A-Za-z0-9]+))?(;q=([0-9\.]+))?/', $lang, $found)) {
				// 2 digit lang code
				$code = $found[1];
				// lang code complement
				$morecode = array_key_exists(3, $found) ? $found[3] : false;
				// full lang code
				$fullcode = $morecode ? $code . '_' . $morecode : $code;
				// coefficient (preference value, will be used in sorting the list)
				$coef = sprintf('%3.1f', array_key_exists(5, $found) ? $found[5] : '1');
				// for sorting by coefficient
				if ($coef) { //	q=0 means do not supply this language
					// adding
					$accepted[$coef . '-' . $code] = array('code' => $code, 'coef' => $coef, 'morecode' => $morecode, 'fullcode' => $fullcode);
				}
			}
		}

		// sorting the list by coefficient desc
		krsort($accepted);
		if (DEBUG_LOCALE) {
			debugLog("self::parseHttpAcceptLanguage($str)");
			debugLogVar(['self::parseHttpAcceptLanguage::$accepted' => $accepted]);
		}
		return $accepted;
	}

	/**
	 * checks a "supplied" locale against the valid locales.
	 * Returns a valid locale if one exists else returns NULL
	 * @param string $userlocale
	 */
	static function validateLocale($userlocale, $source) {
		if (DEBUG_LOCALE)
			debugLog("self::validateLocale($userlocale,$source)");
		$userlocale = strtoupper(str_replace('-', '_', $userlocale));
		$languageSupport = self::generateLanguageList();

		$locale = NULL;
		if (!empty($userlocale)) {
			foreach ($languageSupport as $key => $value) {
				if (strtoupper($value) == $userlocale) { // we got a match
					$locale = $value;
					if (DEBUG_LOCALE)
						debugLog("locale set from $source: " . $locale);
					break;
				} else if (preg_match('+^' . $userlocale . '+', strtoupper($value))) { // we got a partial match
					$locale = $value;
					if (DEBUG_LOCALE)
						debugLog("locale set from $source (partial match): " . $locale);
					break;
				}
			}
		}
		return $locale;
	}

	/**
	 * Sets the locale, etc. to the domain details.
	 * Returns the result of i18n::setupCurrentLocale()
	 *
	 */
	static function setMainDomain() {
		global $_current_admin_obj;
		if (DEBUG_LOCALE)
			debugLogBackTrace("i18n::setMainDomain()");

		//	check url language for language
		if (isset($_REQUEST['locale'])) {
			$new_locale = self::validateLocale(sanitize($_REQUEST['locale']), (isset($_POST['locale'])) ? 'POST' : 'URI string');
			if ($new_locale) {
				setNPGCookie('dynamic_locale', $new_locale);
			} else {
				clearNPGCookie('dynamic_locale');
			}
			if (DEBUG_LOCALE)
				debugLog("dynamic_locale from URL: " . sanitize($_REQUEST['locale']) . "=>$new_locale");
		} else {
			if (isset($_SERVER['HTTP_HOST'])) {
				$matches = explode('.', $_SERVER['HTTP_HOST']);
			} else {
				$matches = array(NULL);
			}
			$new_locale = self::validateLocale($matches[0], 'HTTP_HOST');
			if ($new_locale && getNPGCookie('dynamic_locale')) {
				clearNPGCookie('dynamic_locale');
			}
			if (DEBUG_LOCALE)
				debugLog("dynamic_locale from HTTP_HOST: " . sanitize($matches[0]) . "=>$new_locale");
		}

		//	check for a language cookie
		if (!$new_locale) {
			$new_locale = self::validateLocale(getNPGCookie('dynamic_locale'), 'dynamic_locale cookie');
			if (DEBUG_LOCALE)
				debugLog("locale from cookie: " . $new_locale . ';');
		}

		//	check if the user has a language selected
		if (!$new_locale && is_object($_current_admin_obj)) {
			$new_locale = self::validateLocale($_current_admin_obj->getLanguage(), 'user language');
			;
			if (DEBUG_LOCALE)
				debugLog("locale from user: " . $new_locale);
		}

		//	check the language option
		if (!$new_locale) {
			$new_locale = self::validateLocale(getOption('locale'), 'locale option');
			if (DEBUG_LOCALE)
				debugLog("locale from option: " . $new_locale);
		}

		//check the HTTP accept lang
		if (empty($new_locale)) { // if one is not set, see if there is a match from 'HTTP_ACCEPT_LANGUAGE'
			$languageSupport = self::generateLanguageList();
			$userLang = self::parseHttpAcceptLanguage();
			foreach ($userLang as $lang) {
				$l = strtoupper($lang['fullcode']);
				$new_locale = self::validateLocale($l, 'HTTP Accept Language');
				if ($new_locale)
					break;
			}
		}

		if (empty($new_locale)) {
			// return "default" language, English if allowed, otherwise whatever is the "first" allowed language
			$languageSupport = self::generateLanguageList();
			if (defined('BASE_LOCALE') && BASE_LOCALE) {
				$loc = BASE_LOCALE;
			} else {
				$loc = 'en_US';
			}
			if (empty($languageSupport) || in_array($loc, $languageSupport)) {
				$new_locale = $loc;
			} else {
				$new_locale = array_shift($languageSupport);
			}
			if (DEBUG_LOCALE)
				debugLog("locale from language list: " . $new_locale);
		} else {
			setOption('locale', $new_locale, false);
		}

		if (DEBUG_LOCALE)
			debugLog("self::getUserLocale Returning locale: " . $new_locale);

		if (class_exists('Collator')) {
			//	create the global $collator variable
			$GLOBALS['Collator'] = new Collator($new_locale);
			$GLOBALS['Collator']->setAttribute(Collator::NUMERIC_COLLATION, Collator::ON);
		}

		return self::setupCurrentLocale($new_locale);
	}

	/**
	 * Returns a saved (or posted) locale. Posted locales are stored as a cookie.
	 *
	 * @param string $separator the character used to separate sub language
	 */
	static function getUserLocale($separator = '_') {
		global $_current_locale;
		return str_replace('_', $separator, $_current_locale);
	}

	/**
	 * Returns the SO 639-1 Language Code
	 * @param string $locale optional locale
	 * @return string
	 */
	static function htmlLanguageCode($locale = NULL) {
		if ($locale) {
			echo ' lang="' . str_replace('_', '-', $locale);
		} else if ($lang = self::getUserLocale('-')) {
			echo ' lang="' . $lang;
		}
	}

}

/**
 * Returns the string for the current language from a serialized set of language strings
 * Defaults to the string for the current locale, the en_US string, or the first string which ever is present
 *
 * @param string $dbstring either a serialized language string array or a single string
 * @param string $locale optional locale of the translation desired
 * @return string
 */
function get_language_string($dbstring, $locale = NULL) {
	$strings = getSerializedArray($dbstring);
	if (count($strings) > 1) {
		if (!empty($locale) && isset($strings[$locale])) {
			return $strings[$locale];
		}
		if (isset($strings[$locale = getOption('locale')])) {
			return $strings[$locale];
		}
		if (isset($strings['en_US'])) {
			return $strings['en_US'];
		}
	}
	return array_shift($strings);
}

/**
 * Returns a list of time zones
 *
 * @return unknown
 */
function getTimezones() {
	$cities = array();
	if (function_exists('timezone_abbreviations_list')) {
		$timezones = timezone_abbreviations_list();
		foreach ($timezones as $key => $zones) {
			foreach ($zones as $id => $zone) {
				/**
				 * Only get time zones explicitely not part of "Others".
				 * @see http://www.php.net/manual/en/timezones.others.php
				 */
				if (preg_match('~^(Africa/|America/|Antarctica/|Arctic/|Asia/|Atlantic/|Australia/|Europe/|Indian/|Pacific/|UTC)~', $zone['timezone_id'])) {
					$cities[] = $zone['timezone_id'];
				}
			}
		}
		// Only keep one city (the first and also most important) for each set of possibilities.
		$cities = array_unique($cities);

		// Sort by area/city name.
		ksort($cities, SORT_LOCALE_STRING);
	}
	return $cities;
}

/**
 * Returns the difference between the server timez one and the local (users) time zone
 *
 * @param string $server
 * @param string $local
 * @return int
 */
function timezoneDiff($server, $local) {
	if (function_exists('timezone_abbreviations_list')) {
		$timezones = timezone_abbreviations_list();
		foreach ($timezones as $key => $zones) {
			foreach ($zones as $id => $zone) {
				if (!isset($offset_server) && $zone['timezone_id'] === $server) {
					$offset_server = (int) $zone['offset'];
				}
				if (!isset($offset_local) && $zone['timezone_id'] === $local) {
					$offset_local = (int) $zone['offset'];
				}
				if (isset($offset_server) && isset($offset_local)) {
					return ($offset_server - $offset_local) / 3600;
				}
			}
		}
	}
	return 0;
}

/**
 * returns a serialized "multilingual array" of translations
 * Used for setting default options with multi-lingual strings.
 * @param string $text to be translated
 */
function getAllTranslations($text) {
	global $__languages, $__translations_seen;
	$hash = md5($text);
	if (isset($__translations_seen[$hash]) && $__translations_seen[$hash]['text'] == $text) {
		return $__translations_seen[$hash]['translations'];
	}
	if (!$__languages) {
		$__languages = i18n::generateLanguageList();
		$key = array_search('en_US', $__languages);
		unset($__languages[$key]);
	}
	$entry_locale = i18n::getUserLocale();
	$result = array('en_US' => $text);
	foreach ($__languages as $language) {
		i18n::setupCurrentLocale($language);
		$xlated = gettext($text);
		if ($xlated != $text) { // the string has a translation in this language
			$result[$language] = $xlated;
		}
	}
	$__translations_seen[$hash] = array('text' => $text, 'translations' => $translated = serialize($result));
	i18n::setupCurrentLocale($entry_locale);
	return $translated;
}

if (function_exists('date_default_timezone_set')) { // insure a correct time zone
	$tz = getOption('time_zone');
	if (!empty($tz)) {
		$err = error_reporting(0);
		date_default_timezone_set($tz);
		ini_set('date.timezone', $tz);
		error_reporting($err);
	}
	unset($tz);
}

/**
 * Sort by locale rules with fallback if Collator class not present
 *
 * @param array $strings passed by reference
 * @param bool $case TRUE for case insensitive sort
 * @return bool true if success
 */
function localeSort(&$strings, $case = TRUE) {
	global $Collator;
	if (isset($Collator)) {
		$Collator->setAttribute(Collator::CASE_FIRST, $case ? Collator::OFF : Collator::UPPER_FIRST);
		return $Collator->asort($strings, Collator::SORT_STRING);
	} else {
		if ($case) {
			return natcasesort($strings);
		} else {
			return natsort($strings);
		}
	}
}

/**
 * locale aware comparator with fallback if Collator class not present
 *
 * @param string $a
 * @param string $b
 * @param bool $nat true for natural comparison
 * @param bool $case true for case insensitive comparison
 * @return int 1 if $a is greater than $b
 *             0 if $a is equal to $b
 *            -1 if $a is less than $b
 */
function localeCompare($a, $b, $nat, $case) {
	global $Collator;
	if ($nat && isset($Collator)) {
		$Collator->setAttribute(Collator::CASE_FIRST, $case ? Collator::OFF : Collator::UPPER_FIRST);
		return $Collator->compare($a, $b);
	} else {
		//create the comparator function
		$comp = 'str';
		if ($nat) {
			$comp .= 'nat';
		}
		if ($case) {
			$comp .= 'case';
		}
		$comp .= 'cmp';
		return $comp($a, $b);
	}
}
