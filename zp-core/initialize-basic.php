<?php

/*
 * one time initialization code for basic execution
 */
require_once(dirname(__FILE__) . '/global-definitions.php');
require_once(dirname(__FILE__) . '/lib-encryption.php');
require_once(dirname(__FILE__) . '/lib-utf8.php');
$_UTF8 = new utf8();

switch (PHP_MAJOR_VERSION) {
	case 5:
		switch (PHP_MINOR_VERSION) {
			case 0:
			case 1:
			case 2:
				define('ENT_FLAGS', ENT_QUOTES);
				break;
			case 3:
				define('ENT_FLAGS', ENT_QUOTES | ENT_IGNORE);
				break;
			default: // 4 and beyond
				define('ENT_FLAGS', ENT_QUOTES | ENT_SUBSTITUTE);
				break;
		}
		break;
	default: // PHP 6?
		define('ENT_FLAGS', ENT_QUOTES | ENT_SUBSTITUTE);
		break;
}

// Set error reporting.
error_reporting(E_ALL | E_STRICT);
if (DISPLAY_ERRORS) {
	@ini_set('display_errors', 1);
} else {
	@ini_set('display_errors', 0);
}

set_error_handler("npgErrorHandler");
set_exception_handler("npgExceptionHandler");
register_shutdown_function('npgShutDownFunction');
$_configMutex = new npgMutex('cF');
$_mutex = new npgMutex();

$_conf_options_associations = $_options = array();
$_conf_vars = array('db_software' => 'NULL', 'mysql_prefix' => '_', 'charset' => 'UTF-8', 'UTF-8' => 'utf8');
// Including the config file more than once is OK, and avoids $conf missing.

if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE)) {
	define('DATA_MOD', fileperms(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE) & 0777);
	$_conf_vars = getConfig();
} else {
	define('DATA_MOD', 0777);
}
if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/security.log')) {
	define('LOG_MOD', fileperms(SERVERPATH . '/' . DATA_FOLDER . '/' . '/security.log') & 0777);
} else {
	define('LOG_MOD', DATA_MOD);
}
define('DATABASE_PREFIX', $_conf_vars['mysql_prefix']);
define('LOCAL_CHARSET', isset($_conf_vars['charset']) ? $_conf_vars['charset'] : 'UTF-8');
if (!isset($_conf_vars['special_pages'])) {
	//	get the default version form the distribution files
	$stdConfig = getConfig(CORE_FOLDER . '/netPhotoGraphics_cfg.txt');
	$_conf_vars['special_pages'] = $stdConfig['special_pages'];
}

if (!defined('CHMOD_VALUE')) {
	define('CHMOD_VALUE', fileperms(dirname(__FILE__)) & 0666);
}
define('FOLDER_MOD', CHMOD_VALUE | 0311);
define('FILE_MOD', CHMOD_VALUE & 0666);

if (OFFSET_PATH != 2) {
	if (!file_exists(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE)) {
		_setup(11);
	} else if (!file_exists(dirname(__FILE__) . '/functions-db-' . $_conf_vars['db_software'] . '.php')) {
		_setup(12);
	}
}

if (!defined('FILESYSTEM_CHARSET')) {
	if (isset($_conf_vars['FILESYSTEM_CHARSET']) && $_conf_vars['FILESYSTEM_CHARSET'] != 'unknown') {
		define('FILESYSTEM_CHARSET', $_conf_vars['FILESYSTEM_CHARSET']);
	} else {
		define('FILESYSTEM_CHARSET', 'UTF-8');
	}
}

// If the server protocol is not set, set it to the default.
if (!isset($_conf_vars['server_protocol'])) {
	$_conf_vars['server_protocol'] = 'http';
} else {
	$_conf_vars['server_protocol'] = strtolower($_conf_vars['server_protocol']);
}

foreach ($_conf_vars as $name => $value) {
	if (!is_array($value)) {
		$_conf_options_associations[strtolower($name)] = $name;
		$_options[strtolower($name)] = $value;
	}
}

if (!defined('DATABASE_SOFTWARE') && extension_loaded(strtolower($_conf_vars['db_software']))) {
	require_once(dirname(__FILE__) . '/functions-db-' . $_conf_vars['db_software'] . '.php');
	$__initialDBConnection = db_connect(array_intersect_key($_conf_vars, array('db_software' => '', 'mysql_user' => '', 'mysql_pass' => '', 'mysql_host' => '', 'mysql_database' => '', 'mysql_prefix' => '', 'UTF-8' => '')), (defined('OFFSET_PATH') && OFFSET_PATH == 2) ? FALSE : E_USER_WARNING);
} else {
	$__initialDBConnection = false;
}
if (!function_exists('db_query')) {
	require_once(dirname(__FILE__) . '/functions-db-NULL.php');
}
$software = db_software();
define('MySQL_VERSION', $software['version']);

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
define('GALLERY_SESSION', @$data['album_session']);
define('GALLERY_SECURITY', @$data['gallery_security']);
unset($data);

// insure a correct timezone
if (function_exists('date_default_timezone_set')) {
	$level = error_reporting(0);
	$_server_timezone = date_default_timezone_get();
	date_default_timezone_set($_server_timezone);
	@ini_set('date.timezone', $_server_timezone);
	error_reporting($level);
}

// Set the memory limit to unlimited -- suppress errors if user doesn't have control.
@ini_set('memory_limit', '-1');

// Set the internal encoding
@ini_set('default_charset', LOCAL_CHARSET);
if (function_exists('mb_internal_encoding')) {
	@mb_internal_encoding(LOCAL_CHARSET);
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
	require_once(dirname(__FILE__) . '/' . array_shift($try));
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

if (secureServer()) {
	define('PROTOCOL', 'https');
} else {
	define('PROTOCOL', 'http');
}

define('FULLHOSTPATH', PROTOCOL . "://" . $_SERVER['HTTP_HOST']);
define('FULLWEBPATH', FULLHOSTPATH . WEBPATH);

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

define('SAFE_MODE', preg_match('#(1|ON)#i', ini_get('safe_mode')));
define('SAFE_MODE_ALBUM_SEP', '__');
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

define('HASH_SEED', getOption('extra_auth_hash_text'));

define('IP_TIED_COOKIES', getOption('IP_tied_cookies'));

define('NO_WATERMARK', '!');

// Don't let anything get above this, to save the server from burning up...
define('MAX_SIZE', getOption('image_max_size'));

define('MENU_TRUNCATE_STRING', getOption('menu_truncate_string'));
define('MENU_TRUNCATE_INDICATOR', getOption('menu_truncate_indicator'));



/**
 * TODO: This code should eventually be replaced by a simple define of GOTHUB_ORG once
 * the organization has been changed.
 */
if (getOption('GitHubOwner') == 'netPhotoGraphics') {
	define('GITHUB_ORG', 'netPhotoGraphics');
} else {
	if (version_compare(PHP_VERSION, '5.5.0', '>=')) {
		require_once(dirname(__FILE__) . '/github_locator.php');
	}
	if (!defined('GITHUB_ORG')) {
		define('GITHUB_ORG', 'ZenPhoto20');
	}
}
define('GITHUB', 'github.com/' . GITHUB_ORG . '/netPhotoGraphics');
