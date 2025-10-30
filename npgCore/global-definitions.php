<?php

//define('trace_debug', true); //	un comment this for load tracing

const stdExclude = array('Thumbs.db', 'readme.md', 'data', '.', '..');

Define('PHP_MIN_VERSION', '7.4');
Define('PHP_MIN_SUPPORTED_VERSION', '8.0');

$time = time();
switch ($time) {
	case $time <= strtotime('1/1/2026'):
		Define('PHP_DESIRED_VERSION', '8.1');
		break;
	case $time <= strtotime('1/1/2027'):
		Define('PHP_DESIRED_VERSION', '8.2');
		break;
	case $time <= strtotime('1/1/2028'):
		Define('PHP_DESIRED_VERSION', '8.3');
		break;
	case $time <= strtotime('1/1/2029'):
		Define('PHP_DESIRED_VERSION', '8.4');
		break;
	default:
		Define('PHP_DESIRED_VERSION', '8.5');
		break;
}
unset($time);

if (version_compare(PHP_VERSION, PHP_MIN_VERSION, '<')) {
	die(sprintf(gettext('netPhotoGraphics requires PHP version %s or greater'), PHP_MIN_VERSION));
}

if (!isset($_SERVER['HTTP_HOST']))
	die();

global $_conf_vars, $_options;
$_conf_options_associations = $_options = array();
$_conf_vars = array('db_software' => 'NULL', 'mysql_prefix' => '_', 'charset' => 'UTF-8', 'UTF-8' => 'utf8');

require_once(__DIR__ . '/functions-basic.php');

$v = explode("\n", file_get_contents(__DIR__ . '/version.php'));
foreach ($v as $line) {
	if (strpos($line, 'define') !== false) {
		eval($line); // Include the version info avoiding captured PHP script.
		break;
	}
}

$v = explode('-', NETPHOTOGRAPHICS_VERSION);
define('NETPHOTOGRAPHICS_VERSION_CONCISE', $v[0]);
unset($v);

if (!defined('SORT_FLAG_CASE')) {
	define('SORT_FLAG_CASE', 0);
}
if (!defined('SORT_NATURAL')) {
	define('SORT_NATURAL', 0);
}
if (!defined('SORT_LOCALE_STRING')) {
	define('SORT_LOCALE_STRING', 0);
}
define('NEWLINE', "\n");

define('SCRIPTPATH', str_replace('\\', '/', dirname(__DIR__)));

define('CORE_PATH', 'npg');
define('CORE_FOLDER', basename(__DIR__));
define('PLUGIN_PATH', 'extensions');
define('PLUGIN_FOLDER', PLUGIN_PATH);
define('COMMON_FOLDER', PLUGIN_FOLDER . '/common');
define('USER_PLUGIN_PATH', 'extensions');
define('USER_PLUGIN_FOLDER', 'plugins');
define('ALBUMFOLDER', 'albums');
define('THEMEFOLDER', 'themes');
define('DATA_FOLDER', 'npgData');
define('BACKUPFOLDER', DATA_FOLDER . '/backup');
define('CACHEFOLDER', 'cache');
define('UPLOAD_FOLDER', 'uploaded');
define('STATIC_CACHE_FOLDER', "cache_html");
define('CONFIGFILE', 'npg.cfg.php');
define('MUTEX_FOLDER', '.mutex');
define('UTILITIES_FOLDER', 'utilities');

//used by scriptLoader() to decide whether to inline the script (js or css)
define('INLINE_LOAD_THRESHOLD', 4096);

//bit masks for plugin priorities
define('CLASS_PLUGIN', 8192);
define('ADMIN_PLUGIN', 2048);
define('FEATURE_PLUGIN', 4096);
define('THEME_PLUGIN', 1024);
define('PLUGIN_PRIORITY', 1023);

//exif index defines
define('METADATA_SOURCE', 0);
define('METADATA_KEY', 1);
define('METADATA_DISPLAY_TEXT', 2);
define('METADATA_DISPLAY', 3);
define('METADATA_FIELD_SIZE', 4);
define('METADATA_FIELD_ENABLED', 5);
define('METADATA_FIELD_TYPE', 6);
define('METADATA_FIELD_LINKED', 7);

define('SYMLINK', function_exists('symlink') && strpos(ini_get("suhosin.executor.func.blacklist"), 'symlink') === false);
define('CASE_INSENSITIVE', file_exists(dirname(__FILE__) . '/VERSION.PHP'));

preg_match('/-(.*)/', NETPHOTOGRAPHICS_VERSION, $_debug);
if (isset($_debug[1])) {
	$_debug = $_debug[1];
} else {
	$_debug = '';
}

define('TEST_RELEASE', !empty($_debug));
define('DISPLAY_ERRORS', (bool) strpos($_debug, 'DISPLAY‑ERRORS')); // set to true to have PHP show errors on the web pages
define('DEBUG_403', (bool) strpos($_debug, '403')); // set to true to log 403 error processing debug information.
define('DEBUG_404', (bool) strpos($_debug, '404')); // set to true to log 404 error processing debug information.
define('EXPLAIN_SELECTS', (bool) strpos($_debug, 'EXPLAIN')); //	set to true to log the "EXPLAIN" of SQL SELECT queries
define('DEBUG_FILTERS', (bool) strpos($_debug, 'FILTERS')); // set to true to log filter application sequence.
define('DEBUG_IMAGE', (bool) strpos($_debug, 'IMAGE')); // set to true to log image processing debug information.
define('DEBUG_LOCALE', (bool) strpos($_debug, 'LOCALE')); // used for examining language selection problems
define('DEBUG_LOGIN', (bool) strpos($_debug, 'LOGIN')); // set to true to log admin saves and login attempts
define('DEBUG_PLUGINS', (bool) strpos($_debug, 'PLUGINS')); // set to true to log plugin load sequence.
define('DEBUG_FEED', (bool) strpos($_debug, 'FEED')); // set to true to log class feed detected issues.
define('DEBUG_OBJECTS', (bool) strpos($_debug, 'OBJECTS')); // set to true to log object management.
define('TESTING_MODE', (bool) strpos($_debug, 'TESTING'));

unset($_debug);

$_DB_details = array(
		'mysql_host' => 'not connected',
		'mysql_database' => 'not connected',
		'mysql_prefix' => 'not connected',
		'mysql_user' => ['' => '']
);
define('DB_NOT_CONNECTED', serialize($_DB_details));
define('MYSQL_CONNECTION_RETRIES', 5);

/**
 * OFFSET_PATH definitions:
 * 		0		root scripts (e.g. the root index.php)
 * 		1		core scripts
 * 		2		setup scripts
 * 		3		plugin scripts
 * 		4		sub-folders scripts
 */
$const_webpath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$const_serverpath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']));

/**
 * see if we are executing out of any of the known script folders. If so we know how to adjust the paths
 * if not we presume the script is in the root of the installation. If it is not the script better have set
 * the SERVERPATH and WEBPATH defines to the correct values
 */
if (!preg_match('~(.*?)/(' . CORE_FOLDER . ')~', $const_webpath, $matches)) {
	preg_match('~(.*?)/(' . USER_PLUGIN_FOLDER . '|' . THEMEFOLDER . ')~', $const_webpath, $matches);
}

if ($matches) {
	$const_webpath = $matches[1];
	$const_serverpath = substr($const_serverpath, 0, strpos($const_serverpath, '/' . $matches[2]));

	if (!defined('OFFSET_PATH')) {
		switch ($matches[2]) {
			case CORE_FOLDER:
				define('OFFSET_PATH', 1);
				break;
			case USER_PLUGIN_FOLDER:
				define('OFFSET_PATH', 3);
				break;
			case THEMEFOLDER:
				define('OFFSET_PATH', 4);
				break;
		}
	}
} else {
	if (!defined('OFFSET_PATH')) {
		define('OFFSET_PATH', 0);
	}
}

if (file_exists($const_serverpath . '/' . DATA_FOLDER . '/' . CONFIGFILE)) {
	require ($const_serverpath . '/' . DATA_FOLDER . '/' . CONFIGFILE);
	if (isset($conf)) {
		$_conf_vars = $conf;
		unset($conf);
	}
}


if (isset($_conf_vars['CURL_ENABLED'])) {
	define('CURL_ENABLED', $_conf_vars['CURL_ENABLED']);
} else {
	define('CURL_ENABLED', function_exists('curl_init'));
}

if (isset($_conf_vars['WEBPATH'])) {
	define('WEBPATH', $_conf_vars['WEBPATH']);
} else {
	$const_webpath = rtrim($const_webpath, '/');
	if ($const_webpath == '.') {
		$const_webpath = '';
	}
	define('WEBPATH', $const_webpath);
}

if (isset($_conf_vars['SERVERPATH'])) {
	define('SERVERPATH', $_conf_vars['SERVERPATH']);
} else {
	define('SERVERPATH', $const_serverpath);
}
define('CORE_SERVERPATH', SERVERPATH . '/' . CORE_FOLDER . '/');
define('PLUGIN_SERVERPATH', SERVERPATH . '/' . CORE_FOLDER . '/' . PLUGIN_FOLDER . '/');
define('USER_PLUGIN_SERVERPATH', SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/');
define('GITHUB_API_PATH', PLUGIN_SERVERPATH . 'common/github-api-2.0.2/github-api.php');

unset($matches);
unset($const_webpath);
unset($const_serverpath);

if (isset($_conf_vars['server_protocol']) && $_conf_vars['server_protocol'] == 'https') {
	$protocol = 'https';
} else if (!(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on")) {
	$protocol = 'https';
} else if (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == "https") {
	$protocol = 'https';
} else if (isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] )) {
	$protocol = 'https';
} else if (isset($_SERVER['HTTP_FORWARDED']) && preg_match("/^(.+[,;])?\s*proto=https\s*([,;].*)$/", strtolower($_SERVER['HTTP_FORWARDED']))) {
	$protocol = 'https';
} else if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && ('https' == strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']))) {
	$protocol = 'https';
} else {
	$protocol = 'http';
}
define('PROTOCOL', $protocol);
unset($protocol);

define('FULLHOSTPATH', PROTOCOL . "://" . $_SERVER['HTTP_HOST']);
define('FULLWEBPATH', FULLHOSTPATH . WEBPATH);

define('SESSION_NAME', 'Session_' . preg_replace('~[^a-zA-Z0-9_]+~', '_', trim(FULLWEBPATH, '/') . '_' . NETPHOTOGRAPHICS_VERSION_CONCISE));

define('FALLBACK_SUFFIX', 'webp');

define('AUTHCOOKIE', 'user_auth' . str_replace('/', '_', WEBPATH));

define('DESIRED_PHP_EXTENSIONS', array(
		'bz2',
		'curl',
		'ctype',
		'dom',
		'exif',
		'fileinfo',
		'gettext',
		'gmp',
		'hash',
		'iconv',
		'intl',
		'json',
		'mbstring',
		'openssl',
		'session',
		'tidy',
		'xml',
		'zip'
				)
);

// Contexts (Bitwise and combinable)
define("NPG_INDEX", 1);
define("NPG_ALBUM", 2);
define("NPG_IMAGE", 4);
define("NPG_COMMENT", 8);
define("NPG_SEARCH", 16);
define("SEARCH_LINKED", 32);
define("ALBUM_LINKED", 64);
define('IMAGE_LINKED', 128);
define('ZENPAGE_NEWS_PAGE', 256);
define('ZENPAGE_NEWS_ARTICLE', 512);
define('ZENPAGE_NEWS_CATEGORY', 1024);
define('ZENPAGE_NEWS_DATE', 2048);
define('ZENPAGE_PAGE', 4096);
define('ZENPAGE_SINGLE', 8192);

define('CALENDAR', WEBPATH . '/' . CORE_FOLDER . '/images/calendar.png');

//icons
define('ARROW_DOWN_GREEN', '<span class="font_icon" style="color: green; font-size: large;">&dArr;</span>');
define('ARROW_RIGHT_BLUE', '<span class="font_icon" style="color: blue; font-size:large;">&rArr;</span>');
define('ARROW_UP_GRAY', '<span class="font_icon" style="color: lightgray; font-size: large;">&uArr;</span>');
define('ARROW_UP_GREEN', '<span class="font_icon" style="color: green; font-size: large;">&uArr;</span>');
define('BACK_ARROW_BLUE', '<span class="font_icon" style="color: blue; font-size:large;">&#10094;</span>');
define('BADGE_BLUE', '<span class="font_icon"><img src="' . WEBPATH . '/' . CORE_FOLDER . '/images/np_blue.png" /></span>');
define('BADGE_GOLD', '<span class="font_icon"><img src="' . WEBPATH . '/' . CORE_FOLDER . '/images/np_gold.png" /></span>');
define('BULLSEYE_BLUE', '<span class="font_icon" style="color: blue; font-size: large;">&#9678;</span>');
define('BULLSEYE_DARKORANGE', '<span class="font_icon" style="color: darkorange; font-size: large;;">&#9678;</span>');
define('BULLSEYE_GREEN', '<span class="font_icon" style="color: green; font-size: large;">&#9678;</span>');
define('BULLSEYE_LIGHTGRAY', '<span class="font_icon" style="color: lightgray; font-size: large;">&#9678;</span>');
define('BULLSEYE_RED', '<span class="font_icon" style="color: red; font-size: large;">&#9678;</span>');
define('BURST_BLUE', '<span class="font_icon" style="color: blue; font-size: large;">&#10040;</span>');
define('CHECKMARK_GREEN', '<span class="font_icon" style="color: green; font-size: large;">&#10003;</span>');
define('CIRCLED_BLUE_STAR', '<span class="font_icon" style="color: blue; font-size: large;">&#10026;</span>');
define('CLIPBOARD', '<span class="font_icon" style="font-family: Sego UI Emoji; color: goldenrod;">&#128203;</span>');
define('CLOCKFACE', '<span class="font_icon" style="letter-spacing: -4px;">&#128343;</span>');
define('CLOCKWISE_OPEN_CIRCLE_ARROW_GREEN', '<span class="font_icon" style="font-size:large;color:green;">&#8635;</span>');
define('CLOCKWISE_OPEN_CIRCLE_ARROW_RED', '<span class="font_icon" style="font-size:large;color:red;">&#8635;</span>');
define('CROSS_MARK_RED', '<span class="font_icon" style="color: red; vertical-align: 2px;">&#10060;</span>');
define('CURVED_UPWARDS_AND_RIGHTWARDS_ARROW_BLUE', '<span class="font_icon" style="color:blue; font-size:large;">&#10150;</span>');
define('DRAG_HANDLE', '<span class="font_icon" style="color:lightsteelblue; font-size: x-large;">&#10021;</span>');
define('DRAG_HANDLE_ALERT', '<span class="font_icon" style="color:red; font-size: x-large;">&#10021;</span>');
define('DUPLICATE_ICON', '<span class="font_icon" style="font-size: large;">&#x1F5D7;</span>');
define('ELECTRIC_ARROW', '<span class="font_icon" style="color:green; font-size: x-large; font-weight: bold;">&#x2301;</span>');
define('ENVELOPE', '<span class="font_icon" style="font-size: large;">&#9993;</span>');
define('EXCLAMATION_RED', '<span class="font_icon" style="color: red; font-family: Times New Roman; font-weight: bold; font-size: large;">&#33;</span>');
define('EXPORT_ICON', '<span class="font_icon" style="font-size: large;">&#x1F5CE;</span>');
define('FOLDER_ICON', '<span class="font_icon" style="color: goldenrod;">&#x1F4C1;</span>');
define('GEAR_SYMBOL', '&#9881;');
define('HIDE_ICON', '<span class="font_icon" style="font-size: large; color: red;">&#x1F441;</span>');
define('HIGH_VOLTAGE_SIGN', '<span class="font_icon">&#x26A1;</span>');
define('IMAGE_FOLDER', '<span class="font_icon" style="font-size: large;">&#x1F5BC;</span>');
define('IMAGE_FOLDER_DYNAMIC', '<span class="font_icon" style="color: lightgray; font-size: large;">&#x1F5BC;</span>');
define('INFORMATION_BLUE', '<span class="font_icon" style="color: blue; font-family: Times New Roman; font-size: large;">&#8505;</span>');
define('INSTALL', '<span class="font_icon"><img src="' . WEBPATH . '/' . CORE_FOLDER . '/images/install_icon.png" /></span>');
define('KEY_RED', '<span class="font_icon" style="color: red;">&#128273;</span>');
define('LOCK', '<span class="font_icon"><img src="' . WEBPATH . '/' . CORE_FOLDER . '/images/lock_icon.png" /></span>');
define('LOCK_OPEN', '<span class="font_icon"><img src="' . WEBPATH . '/' . CORE_FOLDER . '/images/lock_open_icon.png" /></span>');
define('MAGNIFY', '<span class="font_icon; font-size: large;">&#x1F50D;</span>');
define('MENU_SYMBOL', '&#9776;');
define('NO_ENTRY', '<span class="font_icon" style="color: red;">&#9940;</span>');
define('NORTH_WEST_CORNER_ARROW', '<span class="font_icon" style="color: green; font-size: large;">&#8689;</span>');
define('OPTIONS_ICON', '<span class="font_icon" style="font-size: large;">' . GEAR_SYMBOL . '</span>');
define('PENCIL_ICON', '<span class="font_icon" style="color: darkgoldenrod; font-size: large; line-height: 97%;">&#x270E;</span>');
define('PICTURE_FOLDER', '<span class="font_icon" style="font-size: large;">&#x1F5BF;</span>');
define('PICTURE_FOLDER_DYNAMIC', '<span class="font_icon" style="color: lightgray; font-size: large;">&#x1F5BF;</span>');
define('PLACEHOLDER_ICON', '<span class="font_icon" style="font-size: large; vertical-align: -1px; color: transparent;">&#x25FB;</span>');
define('PLUGIN_ADMIN', '<span class="font_icon" style="font-size: large; font-weight: bold; color:darkgoldenrod; vertical-align: 1px;">&#x2B58;</span>');
define('PLUGIN_CLASS', '<span class="font_icon" style="font-size: large; color:darkgoldenrod;">&#x229B;</span>');
define('PLUGIN_FEATURE', '<span class="font_icon" style="font-size: x-large; color:darkgoldenrod; padding-left: 1px;">&#x29c7;</span>');
define('PLUGIN_THEME', '<span class="font_icon" style="font-size: x-large; color:darkgoldenrod; vertical-align: -1px;">&#x25FB;</span>');
define('PROHIBITED', '<span class="font_icon" style="font-size: large; color:red; vertical-align: -2px;">&#x1F6C7;</span>');
define('PLUS_ICON', '<span class="font_icon" style="color: green; font-size: large;">&#x271A;</span>');
define('RECYCLE_ICON', '<span class="font_icon" style="color: red; font-size: large; font-weight: bold;">&#x2672;</span>');
define('RIGHT_POINTNG_TRIANGLE_GREEN', '<span class="font_icon" style="color: green; font-size: large; font-weight: bold;">&#9654;</span>');
define('SEARCHFIELDS_ICON', '<span class="font_icon; font-size: large; font-weight: bold; color: blue;">&#x1D11A;</span>');
define('SETUP', '<span class="font_icon" style="color: black;">&#x1F6E0;&#xFE0F;</span>');
define('SHAPE_HANDLES', '<span class="font_icon" style="color: blue; font-size: x-large; font-weight: bold; vertical-align: -4px;">&#x2BCF;</span>');
define('SOUTH_EAST_CORNER_ARROW', '<span class="font_icon" style="color: green; font-size: large;">&#8690;</span>');
define('SWAP_ICON', '<span class="font_icon" style="font-size: x-large;">&#x21C4;</span>');
define('WARNING_SIGN_ORANGE', '<span class="font_icon" style="color: darkorange; font-size: large;">&#9888;</span>');
define('WASTEBASKET', '<span class="font_icon" style="font-size: large; font-weight: bold; color: red;">&#x1F5D1;</span>');
//end icons
