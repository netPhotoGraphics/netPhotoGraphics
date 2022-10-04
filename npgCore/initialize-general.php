<?php

/*
 * Standard initialization code
 */

global $_current_context_stack, $_HTML_cache;

//	insure the site is secure if that is the site's intent
httpsRedirect();

require_once(__DIR__ . '/lib-filter.php');

$_captcha = new _captcha(); // this will be overridden by the plugin if enabled.
$_HTML_cache = new _npg_HTML_cache(); // this will be overridden by the plugin if enabled.
require_once(__DIR__ . '/lib-i18n.php');

//encrypt/decrypt constants
define('SECRET_KEY', getOption('secret_key_text'));
define('SECRET_IV', getOption('secret_init_vector'));
define('INCRIPTION_METHOD', 'AES-256-CBC');

if (function_exists('openssl_encrypt')) {
	require_once(__DIR__ . '/class.ncrypt.php');
	$_adminCript = new mukto90\Ncrypt;
	$_adminCript->set_secret_key(SECRET_KEY);
	$_adminCript->set_secret_iv(SECRET_IV);
	$_adminCript->set_cipher(INCRIPTION_METHOD);
} else {
	$_adminCript = NULL;
}


require_once(__DIR__ . '/load_objectClasses.php');

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
$_locale_Subdomains = npgFunctions::languageSubdomains();

//	use this for labeling "News" pages, etc.
define('NEWS_LABEL', get_language_string(getSerializedArray(getOption('CMS_news_label'))));

$_tagURLs_tags = array('{*FULLWEBPATH*}', '{*WEBPATH*}', '{*PLUGIN_FOLDER*}', '{*PLUGIN_PATH*}', '{*CORE_FOLDER*}', '{*CORE_PATH*}', '{*USER_PLUGIN_FOLDER*}');
$_tagURLs_values = array(FULLWEBPATH, WEBPATH, CORE_FOLDER . '/' . PLUGIN_FOLDER, CORE_PATH . '/' . PLUGIN_PATH, CORE_FOLDER, CORE_PATH, USER_PLUGIN_FOLDER);
