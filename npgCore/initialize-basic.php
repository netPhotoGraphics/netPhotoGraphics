<?php

/*
 * one time initialization code for basic execution
 */
require_once(__DIR__ . '/global-definitions.php');
require_once(__DIR__ . '/class-mutex.php');
require_once(__DIR__ . '/lib-kses.php');
require_once(__DIR__ . '/lib-encryption.php');
require_once(__DIR__ . '/lib-utf8.php');

$_UTF8 = new utf8();

define('ENT_FLAGS', ENT_QUOTES | ENT_SUBSTITUTE);

ini_set('session.use_strict_mode', 1);

// Set error reporting
error_reporting(E_ALL | E_STRICT);
if (DISPLAY_ERRORS) {
	ini_set('display_errors', 1);
} else {
	ini_set('display_errors', 0);
}

set_error_handler("npgErrorHandler");
set_exception_handler("npgExceptionHandler");
register_shutdown_function('npgShutDownFunction');
$_configMutex = new npgMutex('cF');
$_mutex = new npgMutex();

if (OFFSET_PATH >= 0 && OFFSET_PATH != 2 && isset($_conf_vars['THREAD_CONCURRENCY']) && $_conf_vars['THREAD_CONCURRENCY']) {
	$_siteMutex = new npgMutex('tH', $_conf_vars['THREAD_CONCURRENCY']);
	$_siteMutex->lock();
}

if (!defined('CHMOD_VALUE')) {
	define('CHMOD_VALUE', isset($_conf_vars['CHMOD']) ? $_conf_vars['CHMOD'] : fileperms(__DIR__) & 0666);
}
define('FOLDER_MOD', CHMOD_VALUE | 0311);
define('FILE_MOD', CHMOD_VALUE & 0666 | 0400);
if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/security.log')) {
	define('LOG_MOD', fileperms(SERVERPATH . '/' . DATA_FOLDER . '/' . '/security.log') & 0777);
} else if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE)) {
	define('LOG_MOD', fileperms(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE) & 0777);
} else {
	define('LOG_MOD', FILE_MOD);
}
if (!isset($_conf_vars['mysql_prefix'])) {
	$_conf_vars['mysql_prefix'] = '';
}
define('DATABASE_PREFIX', $_conf_vars['mysql_prefix']);
if (!isset($_conf_vars['charset'])) {
	$_conf_vars['charset'] = 'UTF-8';
}
define('LOCAL_CHARSET', $_conf_vars['charset']);
if (!isset($_conf_vars['special_pages'])) {
	//	get the default version form the distribution files
	$stdConfig = getConfig(CORE_FOLDER . '/netPhotoGraphics_cfg.txt');
	$_conf_vars['special_pages'] = $stdConfig['special_pages'];
}

if (OFFSET_PATH != 2) {
	if (!file_exists(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE)) {
		_setup(11);
	} else if (!file_exists(__DIR__ . '/functions-db-' . $_conf_vars['db_software'] . '.php')) {
		_setup(12);
	}
}

if (!defined('FILESYSTEM_CHARSET')) {
	if (!isset($_conf_vars['FILESYSTEM_CHARSET']) || $_conf_vars['FILESYSTEM_CHARSET'] == 'unknown') {
		$_conf_vars['FILESYSTEM_CHARSET'] = 'UTF-8';
	}
}
define('FILESYSTEM_CHARSET', $_conf_vars['FILESYSTEM_CHARSET']);

foreach ($_conf_vars as $name => $value) {
	if (!is_array($value)) {
		$_conf_options_associations[strtolower($name)] = $name;
		$_options[strtolower($name)] = $value;
	}
}

if (!defined('DATABASE_SOFTWARE') && extension_loaded(strtolower($_conf_vars['db_software']))) {
	require_once(__DIR__ . '/functions-db-' . $_conf_vars['db_software'] . '.php');
	$__initialDBConnection = db_connect(array_intersect_key($_conf_vars, array(
			'db_software' => '',
			'mysql_user' => '',
			'mysql_pass' => '',
			'mysql_host' => '',
			'mysql_port' => '',
			'mysql_socket' => '',
			'mysql_database' => '',
			'mysql_prefix' => '',
			'UTF-8' => '')
					), (defined('OFFSET_PATH') && OFFSET_PATH == 2) ? FALSE : E_USER_WARNING);
} else {
	$__initialDBConnection = false;
}

if (!function_exists('db_query')) {
	require_once(__DIR__ . '/functions-db-NULL.php');
}
$software = db_software();
define('MySQL_VERSION', $software['version']);
define('MySQL_CONNECTIONS', $software['connections']);

if (!$__initialDBConnection && OFFSET_PATH != 2) {
	_setup(13);
}

primeOptions();
define('SITE_LOCALE', getOption('locale'));

$data = getOption('gallery_data');
if ($data) {
	$data = getSerializedArray($data);
} else {
	$data = array();
}
define('GALLERY_SESSION', isset($data['album_session']) ? $data['album_session'] : NULL);
define('GALLERY_SECURITY', isset($data['gallery_security']) ? $data['gallery_security'] : NULL);
unset($data);

// insure a correct timezone
if (function_exists('date_default_timezone_set')) {
	$level = error_reporting(0);
	$_server_timezone = date_default_timezone_get();
	date_default_timezone_set($_server_timezone);
	ini_set('date.timezone', $_server_timezone);
	error_reporting($level);
}

// Set the memory limit to unlimited -- suppress errors if user doesn't have control.
ini_set('memory_limit', '-1');

// Set the internal encoding
ini_set('default_charset', LOCAL_CHARSET);
if (function_exists('mb_internal_encoding')) {
	mb_internal_encoding(LOCAL_CHARSET);
}

// load graphics libraries in priority order
// once a library has concented to load, all others will
// abdicate.
$_graphics_optionhandlers = array();
$try = array('lib-GD.php', 'lib-NoGraphics.php');
if (getOption('use_imagick')) {
	array_unshift($try, 'lib-Imagick.php');
}
while (!function_exists('gl_graphicsLibInfo')) {
	require_once(__DIR__ . '/' . array_shift($try));
}
unset($try);
$_cachefileSuffix = gl_graphicsLibInfo();

define('GRAPHICS_LIBRARY', $_cachefileSuffix['Library']);
unset($_cachefileSuffix['Library']);
unset($_cachefileSuffix['Library_desc']);
$_supported_images = $_images_classes = array();
foreach ($_cachefileSuffix as $key => $type) {
	if ($type) {
		$_images_classes[$_supported_images[] = strtolower($key)] = 'Image';
	}
}

if (!defined('COOKIE_PERSISTENCE')) {
	$persistence = getOption('cookie_persistence');
	if (!$persistence)
		$persistence = 5184000;
	define('COOKIE_PERSISTENCE', $persistence);
	unset($persistence);
}
if ($c = getOption('cookie_path')) {
	define('COOKIE_PATH', $c);
} else {
	define('COOKIE_PATH', WEBPATH);
}
unset($c);

define('SERVERCACHE', SERVERPATH . '/' . CACHEFOLDER);
define('MOD_REWRITE', getOption('mod_rewrite'));

define('DEBUG_LOG_SIZE', getOption('debug_log_size'));

define('ALBUM_FOLDER_WEBPATH', getAlbumFolder(WEBPATH));
define('ALBUM_FOLDER_SERVERPATH', getAlbumFolder(SERVERPATH));
define('ALBUM_FOLDER_EMPTY', getAlbumFolder(''));

define('IMAGE_WATERMARK', getOption('fullimage_watermark'));
define('FULLIMAGE_WATERMARK', getOption('fullsizeimage_watermark'));
define('THUMB_WATERMARK', getOption('Image_watermark'));
define('OPEN_IMAGE_CACHE', !getOption('protected_image_cache'));
define('IMAGE_CACHE_SUFFIX', getOption('image_cache_suffix'));

define('DATE_FORMAT', getOption('date_format'));

define('RW_SUFFIX', getOption('mod_rewrite_suffix'));
define('UNIQUE_IMAGE', getOption('unique_image_prefix') && MOD_REWRITE);
define('UTF8_IMAGE_URI', getOption('UTF8_image_URI'));
define('MEMBERS_ONLY_COMMENTS', getOption('comment_form_members_only'));

define('HASH_SEED', strval(getOption('extra_auth_hash_text')));

define('IP_TIED_COOKIES', getOption('IP_tied_cookies'));

define('NO_WATERMARK', '!');

define('MENU_TRUNCATE_STRING', getOption('menu_truncate_string'));
define('MENU_TRUNCATE_INDICATOR', getOption('menu_truncate_indicator'));

define('GITHUB_ORG', 'netPhotoGraphics');
define('GITHUB', 'github.com/' . GITHUB_ORG . '/netPhotoGraphics');

define('ENCODING_FALLBACK', getOption('encoding_fallback') && MOD_REWRITE);

define('CONCURRENCY_MAX', (int) ceil(MySQL_CONNECTIONS * 0.8));

$chunk = getOption('THREAD_CONCURRENCY');
if (!$chunk) {
	$chunk = min((int) ceil(CONCURRENCY_MAX * 0.75), 50);
}
define('THREAD_CONCURRENCY', $chunk);

unset($chunk);
