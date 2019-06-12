<?php

/*
 * Standard initialization code
 */

global $_current_context_stack, $_HTML_cache;

if (!function_exists("json_encode")) {
// load the drop-in replacement library
	require_once(dirname(__FILE__) . '/lib-json.php');
}

require_once(dirname(__FILE__) . '/lib-filter.php');
require_once(dirname(__FILE__) . '/lib-kses.php');

if (class_exists('tidy')) {

	function cleanHTML($html) {
		$tidy = new tidy();
		$tidy->parseString($html, array('preserve-entities' => TRUE, 'indent' => TRUE, 'markup' => TRUE, 'show-body-only' => TRUE, 'wrap' => 0, 'quote-marks' => TRUE), 'utf8');
		$tidy->cleanRepair();
		return $tidy;
	}

} else {
	require_once(CORE_SERVERPATH . 'htmLawed.php');

	function cleanHTML($html) {
		return htmLawed($html, array('tidy' => '2s2n'));
	}

}
if (!function_exists('hex2bin')) {

	function hex2bin($h) {
		if (!is_string($h))
			return null;
		$r = '';
		for ($a = 0; $a < strlen($h); $a += 2) {
			$r .= chr(hexdec($h{$a} . $h{($a + 1)}));
		}
		return $r;
	}

}

$_captcha = new _captcha(); // this will be overridden by the plugin if enabled.
$_HTML_cache = new _npg_HTML_cache(); // this will be overridden by the plugin if enabled.
require_once(dirname(__FILE__) . '/lib-i18n.php');

//encrypt/decrypt constants
define('SECRET_KEY', getOption('secret_key_text'));
define('SECRET_IV', getOption('secret_init_vector'));
define('INCRIPTION_METHOD', 'AES-256-CBC');

if (function_exists('openssl_encrypt')) {
	require_once(dirname(__FILE__) . '/class.ncrypt.php');
	$_adminCript = new mukto90\Ncrypt;
	$_adminCript->set_secret_key(SECRET_KEY);
	$_adminCript->set_secret_iv(SECRET_IV);
	$_adminCript->set_cipher(INCRIPTION_METHOD);
} else {
	$_adminCript = NULL;
}

require_once(dirname(__FILE__) . '/load_objectClasses.php');

$_albumthumb_selector = array(array('field' => '', 'direction' => '', 'desc' => gettext('random')),
		array('field' => 'id', 'direction' => 'DESC', 'desc' => gettext('most recent')),
		array('field' => 'mtime', 'direction' => '', 'desc' => gettext('oldest')),
		array('field' => 'title', 'direction' => '', 'desc' => gettext('first alphabetically')),
		array('field' => 'hitcounter', 'direction' => 'DESC', 'desc' => gettext('most viewed'))
);

$_current_context_stack = array();

$_missing_album = new TransientAlbum(gettext('missing'));
$_missing_image = new Transientimage($_missing_album, CORE_SERVERPATH . 'images/err-imagenotfound.png');

define('SELECT_IMAGES', 1);
define('SELECT_ALBUMS', 2);
define('SELECT_PAGES', 4);
define('SELECT_ARTICLES', 8);

$_exifvars = npgFunctions::exifvars();
$_locale_Subdomains = npgFunctions::LanguageSubdomains();

//	use this for labeling "News" pages, etc.
define('NEWS_LABEL', get_language_string(getSerializedArray(getOption('zenpage_news_label'))));

$_tagURLs_tags = array('{*FULLWEBPATH*}', '{*WEBPATH*}', '{*CORE_FOLDER*}', '{*CORE_PATH*}', '{*PLUGIN_FOLDER*}', '{*PLUGIN_PATH*}', '{*USER_PLUGIN_FOLDER*}');
$_tagURLs_values = array(FULLWEBPATH, WEBPATH, CORE_FOLDER, CORE_PATH, PLUGIN_FOLDER, PLUGIN_PATH, USER_PLUGIN_FOLDER);
