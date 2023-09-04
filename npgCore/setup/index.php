<?php
/**
 * install routine
 *
 * @author Stephen Billard (sbillard)
 *
 * @package setup
 */
// force UTF-8 Ø

ini_set('session.use_strict_mode', 1);
ob_implicit_flush(true);

define('OFFSET_PATH', 2);

require_once(dirname(__DIR__) . '/global-definitions.php');

clearstatcache();
if (defined('CHMOD_VALUE')) {
	$chmod = CHMOD_VALUE;
} else {
	$chmod = fileperms(dirname(__DIR__)) & 0666;
}

if (!file_exists(SERVERPATH . '/' . DATA_FOLDER)) {
	mkdir(SERVERPATH . '/' . DATA_FOLDER, $chmod | 0311);
}

$_initial_session_path = session_save_path();

require_once(dirname(__DIR__) . '/functions.php');
require_once(__DIR__ . '/setup-functions.php');

//allow only one setup to run
$setupMutex = new npgMutex('sP');
$setupMutex->lock();

if ($debug = isset($_REQUEST['debug'])) {
	if (!$debug = $_REQUEST['debug']) {
		$debug = 'debug';
	}
	$debugq = '&' . $debug;
} else {
	$debugq = '';
}

$upgrade = false;

require_once(dirname(__DIR__) . '/lib-utf8.php');
if (isset($_REQUEST['autorun'])) {
	$displayLimited = true;
	if (!empty($_REQUEST['autorun'])) {
		$autorun = strip_tags($_REQUEST['autorun']);
	} else {
		$autorun = 'admin';
	}
	$autorunq = '&autorun=' . $autorun;
} else {
	$displayLimited = $autorun = false;
	$autorunq = '';
}

if (file_exists(SERVERPATH . '/zp-core')) {
	if (file_exists(SERVERPATH . '/zp-data/zenphoto.cfg.php')) {
		$_config_contents = file_get_contents(SERVERPATH . '/zp-data/zenphoto.cfg.php');
		$i = strpos($_config_contents, '/** Do not edit below this line. **/');
		$_config_contents = substr($_config_contents, 0, $i + 36);
		file_put_contents(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE, $_config_contents);
	}
	if (file_exists(SERVERPATH . '/zp-data/charset_tést')) {
		rename(SERVERPATH . '/zp-data/charset_tést', SERVERPATH . '/' . DATA_FOLDER . '/charset_tést.cfg');
	}
	if (file_exists(SERVERPATH . '/zp-data')) {
		npgFunctions::removeDir(SERVERPATH . '/zp-data');
	}
	npgFunctions::removeDir(SERVERPATH . '/zp-core');
	$setupMutex->unlock();
	//	redirect to get the config file loaded.
	$q = '?' . ltrim($debugq . $autorunq, '&');
	header('Location: ' . FULLWEBPATH . '/' . CORE_FOLDER . '/setup/index.php' . $q);
	exit();
}

session_cache_limiter('nocache');
$session = npg_session_start();
$setup_checked = false;

if (isset($_REQUEST['xsrfToken']) || isset($_REQUEST['update']) || isset($_REQUEST['checked'])) {
	if (isset($_SESSION['save_session_path'])) {
		$setup_checked = isset($_GET['checked']);
		$_initial_session_path = $_SESSION['save_session_path'];
	} else {
		$_initial_session_path = false;
		unset($_REQUEST['update']);
		unset($_REQUEST['checked']);
	}
}
$_SESSION['save_session_path'] = session_save_path();

clearstatcache();

$en_US = dirname(__DIR__) . '/locale/en_US/';
if (!file_exists($en_US)) {
	mkdir(dirname(__DIR__) . '/locale/', $chmod | 0311);
	mkdir($en_US, $chmod | 0311);
}

if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/' . stripSuffix(CONFIGFILE) . '.bak')) {
	unlink(SERVERPATH . '/' . DATA_FOLDER . '/' . stripSuffix(CONFIGFILE) . '.bak'); //	remove any old backup file
}

$newconfig = false;
if (file_exists($oldconfig = SERVERPATH . '/' . DATA_FOLDER . '/zenphoto.cfg.php')) {
	if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/zenphoto.cfg.bak.php')) {
		unlink(SERVERPATH . '/' . DATA_FOLDER . '/zenphoto.cfg.bak.php');
	}
	$config_contents = file_get_contents($oldconfig);
	if (strpos($config_contents, '<?php') === false) {
		$config_contents = "<?php\n" . $config_contents . "\n?>";
	}
	$config_contents = strtr($config_contents, array('global $_zp_conf_vars;' => '', '$_zp_conf_vars = $conf;' => '', 'unset($conf);' => '')); //	migrate of zenphoto config file
	file_put_contents(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE, $config_contents);
	configMod();
	unlink(SERVERPATH . '/' . DATA_FOLDER . '/zenphoto.cfg.php');
	setupLog(gettext('config file migrated'));
	header('Location: ' . FULLWEBPATH . '/' . CORE_FOLDER . '/setup/index.php');
	exit();
} else if (file_exists($oldconfig = dirname(dirname(__DIR__)) . '/' . CORE_FOLDER . '/zp-config.php')) {
	//migrate old root configuration file.
	$config_contents = file_get_contents($oldconfig);
	$i = strpos($config_contents, '/** Do not edit above this line. **/');
	$config_contents = "<?php\nglobal \$_conf_vars;\n\$conf = array()\n" . substr($config_contents, $i);
	file_put_contents(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE, $config_contents);
	$result = unlink(dirname(dirname(__DIR__)) . '/' . CORE_FOLDER . '/zp-config.php');
	configMod();
} else if (file_exists($oldconfig = SERVERPATH . '/' . DATA_FOLDER . '/zenphoto.cfg')) {
	$config_contents = "<?php\n" . file_get_contents($oldconfig) . "\n?>";
	file_put_contents(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE, $config_contents);
	unlink(SERVERPATH . '/' . DATA_FOLDER . '/zenphoto.cfg');
	configMod();
} else if (!file_exists(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE)) {
	$update_config = true;
	copy(dirname(__DIR__) . '/netPhotoGraphics_cfg.txt', SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE);
	configMod();
}

if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/.htaccess')) {
	chmod(SERVERPATH . '/' . DATA_FOLDER . '/.htaccess', 0777);
}
copy(dirname(__DIR__) . '/dataaccess', SERVERPATH . '/' . DATA_FOLDER . '/.htaccess');
chmod(SERVERPATH . '/' . DATA_FOLDER . '/.htaccess', 0444);

if (!file_exists(SERVERPATH . '/' . BACKUPFOLDER)) {
	@mkdir(SERVERPATH . '/' . BACKUPFOLDER, $chmod | 0311);
}
if (file_exists(SERVERPATH . '/backup')) {
	/* move the files */
	if (($dir = opendir(SERVERPATH . '/backup')) !== false) {
		if (file_exists(SERVERPATH . '/backup/.htaccess')) {
			chmod(SERVERPATH . '/backup/.htaccess', 0777);
			unlink(SERVERPATH . '/backup/.htaccess');
		}
		while (($file = readdir($dir)) !== false) {
			if ($file != '.' && $file != '..') {
				chmod(SERVERPATH . '/backup/' . $file, 0777);
				rename(SERVERPATH . '/backup/' . $file, SERVERPATH . '/' . BACKUPFOLDER . '/' . $file);
			}
		}
		closedir($dir);
	}
	/* remove the folder */
	chmod(SERVERPATH . '/backup', 0777);
	rmdir(SERVERPATH . '/backup');
}
if (file_exists(SERVERPATH . '/' . BACKUPFOLDER . '/.htaccess')) {
	chmod(SERVERPATH . '/' . BACKUPFOLDER . '/.htaccess', 0777);
}
copy(dirname(__DIR__) . '/denyaccess', SERVERPATH . '/' . BACKUPFOLDER . '/.htaccess');
chmod(SERVERPATH . '/' . BACKUPFOLDER . '/.htaccess', 0444);

if (!file_exists(SERVERPATH . '/' . DATA_FOLDER . '/' . MUTEX_FOLDER)) {
	@mkdir(SERVERPATH . '/' . DATA_FOLDER . '/' . MUTEX_FOLDER, $chmod | 0311);
}
if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/' . MUTEX_FOLDER . '/.htaccess')) {
	chmod(SERVERPATH . '/' . DATA_FOLDER . '/' . MUTEX_FOLDER . '/.htaccess', 0777);
}
copy(dirname(__DIR__) . '/denyaccess', SERVERPATH . '/' . DATA_FOLDER . '/' . MUTEX_FOLDER . '/.htaccess');
chmod(SERVERPATH . '/' . DATA_FOLDER . '/' . MUTEX_FOLDER . '/.htaccess', 0444);

if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/lastPublishCheck')) {
	chmod(SERVERPATH . '/' . DATA_FOLDER . '/lastPublishCheck', 0777);
	rename(SERVERPATH . '/' . DATA_FOLDER . '/lastPublishCheck', SERVERPATH . '/' . DATA_FOLDER . '/lastPublishCheck.cfg');
}

if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/recentIP')) {
	chmod(SERVERPATH . '/' . DATA_FOLDER . '/recentIP', 0777);
	rename(SERVERPATH . '/' . DATA_FOLDER . '/recentIP', SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg');
}

if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/charset_tést')) {
	chmod(SERVERPATH . '/' . DATA_FOLDER . '/charset_tést', 0777);
	rename(SERVERPATH . '/' . DATA_FOLDER . '/charset_tést', SERVERPATH . '/' . DATA_FOLDER . '/charset_tést.cfg');
}

if (isset($_GET['mod_rewrite'])) {
	$mod = '&mod_rewrite=' . $_GET['mod_rewrite'];
} else {
	$mod = '';
}

$_config_contents = file_exists(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE) ? file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE) : NULL;
$update_config = false;

if (strpos($_config_contents, "\$conf['charset']") === false) {
	$k = strpos($_config_contents, "\$conf['UTF-8'] = true;");
	$_config_contents = substr($_config_contents, 0, $k) . "\$conf['charset'] = 'UTF-8';\n" . substr($_config_contents, $k);
	$update_config = true;
}

if (strpos($_config_contents, "\$conf['special_pages']") === false) {
	$template = file_get_contents(dirname(__DIR__) . '/netPhotoGraphics_cfg.txt');
	$i = strpos($template, "\$conf['special_pages']");
	$j = strpos($template, '//', $i);
	$k = strpos($_config_contents, '/** Do not edit below this line. **/');
	if ($k !== false) {
		$_config_contents = substr($_config_contents, 0, $k) . str_pad('', 80, '/') . "\n" .
						substr($template, $i, $j - $i) . str_pad('', 5, '/') . "\n" .
						substr($_config_contents, $k);
		$update_config = true;
	}
}

$i = strpos($_config_contents, 'define("DEBUG", false);');
if ($i !== false) {
	$update_config = true;
	$j = strpos($_config_contents, "\n", $i);
	$_config_contents = substr($_config_contents, 0, $i) . substr($_config_contents, $j); // remove this so it won't be defined twice
}

if (isset($_POST['db'])) { //try to update the config file
	setupXSRFDefender('db');
	setupLog(gettext("db POST handling"));
	$update_config = true;
	if (isset($_POST['db_software'])) {
		$_config_contents = configFile::update('db_software', trim(sanitize($_POST['db_software'], 0)), $_config_contents);
	}
	if (isset($_POST['db_user'])) {
		$_config_contents = configFile::update('mysql_user', trim(sanitize($_POST['db_user'], 0)), $_config_contents);
	}
	if (isset($_POST['db_pass'])) {
		$_config_contents = configFile::update('mysql_pass', trim(sanitize($_POST['db_pass'], 0)), $_config_contents);
	}
	if (isset($_POST['db_host'])) {
		$_config_contents = configFile::update('mysql_host', trim(sanitize($_POST['db_host'], 0)), $_config_contents);
	}
	if (isset($_POST['db_port'])) {
		$_config_contents = configFile::update('mysql_port', trim(sanitize($_POST['db_port'], 0)), $_config_contents);
	}
	if (isset($_POST['db_socket'])) {
		$_config_contents = configFile::update('mysql_socket', trim(sanitize($_POST['db_socket'], 0)), $_config_contents);
	}
	if (isset($_POST['db_database'])) {
		$_config_contents = configFile::update('mysql_database', trim(sanitize($_POST['db_database'], 0)), $_config_contents);
	}
	if (isset($_POST['db_prefix'])) {
		$_config_contents = configFile::update('mysql_prefix', str_replace(array('.', '/', '\\', '`', '"', "'"), '_', trim(sanitize($_POST['db_prefix'], 0))), $_config_contents);
	}
}

define('ACK_DISPLAY_ERRORS', 2);

if (isset($_GET['security_ack'])) {
	setupXSRFDefender('security_ack');
	$_config_contents = configFile::update('security_ack', (isset($conf['security_ack']) ? $cache['keyword'] : NULL) | (int) $_GET['security_ack'], $_config_contents, false);
	$update_config = true;
}

$permission_names = array(
		0444 => gettext('readonly'),
		0644 => gettext('strict'),
		0664 => gettext('relaxed'),
		0666 => gettext('loose')
);
$permissions = array_keys($permission_names);
if ($updatechmod = isset($_REQUEST['chmod_permissions'])) {
	setupXSRFDefender('chmod_permissions');
	$selected = round($_REQUEST['chmod_permissions']);
	if ($selected >= 0 && $selected < count($permissions)) {
		$chmod = $permissions[$selected];
	} else {
		$updatechmod = false;
	}
} if (strpos($_config_contents, "defined('CHMOD_VALUE'") !== false) {
	$updatechmod = true;
}

if ($updatechmod) {
	$_config_contents = configFile::update('CHMOD', sprintf('0%o', $chmod), $_config_contents, false);
	if (strpos($_config_contents, "defined('CHMOD_VALUE'") !== false) {
		$_config_contents = preg_replace("|if\s*\(!defined\('CHMOD_VALUE'\)\)\s*{\n*\s*define\(\'CHMOD_VALUE\'\,(.*)\);\s*\n*}\n*|i", "", $_config_contents);
	}
	$update_config = true;
}

if (isset($_REQUEST['FILESYSTEM_CHARSET'])) {
	setupXSRFDefender('FILESYSTEM_CHARSET');
	$fileset = $_REQUEST['FILESYSTEM_CHARSET'];
	$_config_contents = configFile::update('FILESYSTEM_CHARSET', $fileset, $_config_contents);
	$update_config = true;
}

if ($update_config) {
	configFile::store($_config_contents);
	//	reload the page so that the database config takes effect
	$q = '?' . ltrim($debugq . $autorunq, '&') . '&db_config';
	setuplog(gettext('Configuration file updated'));
	header('Location: ' . FULLWEBPATH . '/' . CORE_FOLDER . '/setup/index.php' . $q);
	exit();
}

// Important. when adding new database support this switch may need to be extended,
$engines = array();

$preferences = array('MySQLi' => 1, 'PDO_MySQL' => 2);

$cur = 999999;
$preferred = NULL;

$dir = opendir(dirname(__DIR__));
while (($engineMC = readdir($dir)) !== false) {
	if (preg_match('/^functions-db-(.+)\.php/', $engineMC)) {
		$engineMC = substr($engineMC, 13, -4);
		$engine = strtolower($engineMC);
		if (array_key_exists($engineMC, $preferences)) {
			$order = $preferences[$engineMC];
			$enabled = extension_loaded($engine);
			if ($enabled && $order < $cur) {
				$preferred = $engineMC;
				$cur = $order;
			}
			$engines[$order] = array('user' => true, 'pass' => true, 'host' => true, 'database' => true, 'prefix' => true, 'engine' => $engineMC, 'enabled' => $enabled);
		}
	}
}
ksort($engines, SORT_NATURAL);

if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE)) {
	require(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE);
	if (isset($conf)) {
		$_conf_vars = $conf;
	}
	if (isset($_conf_vars) && isset($_conf_vars['special_pages'])) {
		if (isset($_conf_vars['db_software'])) {
			$confDB = $_conf_vars['db_software'];
			if (extension_loaded(strtolower($confDB)) && file_exists(dirname(__DIR__) . '/functions-db-' . $confDB . '.php')) {
				$selected_database = $confDB;
			} else {
				$selected_database = $preferred;
				if ($preferred) {
					$_conf_vars['db_software'] = $preferred;
					$_config_contents = configFile::update('db_software', $preferred, $_config_contents);
					$update_config = true;
				}
			}
		} else {
			$_conf_vars['db_software'] = $selected_database = $preferred;
			$_config_contents = configFile::update('db_software', $_config_contents, $preferred);
			$update_config = true;
			$confDB = NULL;
		}

		if (!$selected_database) {
			require_once(dirname(__DIR__) . '/functions-db-NULL.php');
		}
	} else {
		// There is a problem with the configuration file
		?>
		<div style="background-color: red;font-size: xx-large;">
			<p>
				<?php echo gettext('A corrupt configuration file was detected. You should remove or repair the file and re-run setup.'); ?>
			</p>
		</div>
		<?php
		exit();
	}
}

if ($update_config) {
	setuplog(sprintf(gettext('db_software set to %1$s.'), $preferred));
	configFile::store($_config_contents);
}
$result = true;
$environ = false;
$DBcreated = false;
$oktocreate = false;
$connection = false;
$connectDBErr = '';

if ($selected_database) {
	$connectDBErr = '';
	$connection = $__initialDBConnection;
	if ($connection) { // got the database handler and the database itself connected
		//	Flag a successful connection has occurred
		$_conf_vars['db_client'] = $connection->client_info;
		$_config_contents = configFile::update('db_client', $connection->client_info, $_config_contents);
		configFile::store($_config_contents);

		$result = query("SELECT `id` FROM " . $_conf_vars['mysql_prefix'] . 'options' . " LIMIT 1", false);
		if ($result && $result->num_rows > 0) {
			$upgrade = gettext("upgrade");
		} else {
			$upgrade = gettext("install");
		}
		$environ = true;
	} else {
		if ($_DB_connection) { // there was a connection to the database handler but not to the database.
			if (!empty($_conf_vars['mysql_database'])) {
				if (isset($_GET['Create_Database'])) {
					$result = db_create();
					if ($result && ($connection = db_connect($_conf_vars, false))) {
						$environ = true;
					} else {
						if ($result) {
							$DBcreated = true;
						} else {
							$connectDBErr = db_error();
						}
					}
				} else {
					$oktocreate = true;
				}
			}
		} else {
			$connectDBErr = db_error();
		}
	}
}

require_once(dirname(__DIR__) . '/admin-functions.php');
require_once(dirname(__DIR__) . '/' . PLUGIN_FOLDER . '/security-logger.php');

$system_check = !$connection || !$setup_checked && (($upgrade && $autorun) || setupUserAuthorized());

header('Content-Type: text/html; charset=UTF-8');
header("HTTP/1.0 200 OK");
header("Status: 200 OK");
header("Cache-Control: no-cache, must-revalidate, no-store, pre-check=0, post-check=0, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 19 Nov 1981 08:52:00 GMT");

if (defined('CHMOD_VALUE')) {
	$chmod = CHMOD_VALUE & 0666;
}

enableExtension('security-logger', 100 | CLASS_PLUGIN);

$cloneid = bin2hex(FULLWEBPATH);
$forcerewrite = isset($_SESSION['clone'][$cloneid]['mod_rewrite']) && $_SESSION['clone'][$cloneid]['mod_rewrite'] && !file_exists(SERVERPATH . '/.htaccess');
$newht = file_get_contents(CORE_SERVERPATH . 'htaccess');
if ($newconfig || isset($_GET['copyhtaccess']) || $forcerewrite) {
	if (($newconfig || $forcerewrite) && !file_exists(SERVERPATH . '/.htaccess') || setupUserAuthorized()) {
		if (file_exists(SERVERPATH . '/.htaccess')) {
			chmod(SERVERPATH . '/.htaccess', 0777);
		}
		file_put_contents(SERVERPATH . '/.htaccess', $newht);
		chmod(SERVERPATH . '/.htaccess', 0444);
	}
}

if ($setup_checked) {
	setupLog(gettext("Completed system check"), true);

	if (isset($_COOKIE['setup_test_cookie'])) {
		$setup_cookie = $_COOKIE['setup_test_cookie'];
	} else {
		$setup_cookie = '';
	}
	clearNPGCookie('setup_test_cookie');
	if ($setup_cookie == NETPHOTOGRAPHICS_VERSION) {
		setupLog(gettext('Setup cookie test successful'));
	} else {
		setupLog('<span class="logwarning">' . gettext('Setup cookie test unsuccessful') . '</span>', true);
	}

	if ($s = getOption('users_per_page')) {
		setNPGCookie('usersTab_userCount', $s, 3600 * 24 * 365 * 10);
		purgeOption('users_per_page');
	}
	if ($s = getOption('plugins_per_page')) {
		setNPGCookie('pluginsTab_pluginCount', $s, 3600 * 24 * 365 * 10);
		purgeOption('plugins_per_page');
	}
	if ($s = getOption('groups_per_page')) {
		setNPGCookie('groupsTab_groupCount', $s, 3600 * 24 * 365 * 10);
		purgeOption('groups_per_page');
	}
	if ($s = getOption('articles_per_page')) {
		setNPGCookie('articleTab_articleCount', $s, 3600 * 24 * 365 * 10);
		purgeOption('articles_per_page');
	}
} else {
	if (isset($_POST['db'])) {
		setupLog(gettext("Post of Database credentials"), true);
	} else {

		if (!isset($_SESSION['SetupStarted']) || $_SESSION['SetupStarted'] != NETPHOTOGRAPHICS_VERSION) {
			$_SESSION['SetupStarted'] = NETPHOTOGRAPHICS_VERSION;
			npgFilters::apply('log_setup', true, 'install', gettext('Started'));
		}

		$me = realpath(dirname(dirname(dirname(str_replace('\\', '/', __FILE__)))));
		$mine = realpath(SERVERPATH);
		if (caseInsensitiveOS()) {
			$me = strtolower($me);
			$mine = strtolower($mine);
		}

		if ($mine == $me) {
			$clone = '';
		} else {
			$clone = ' ' . gettext('clone');
		}

		setupLog(sprintf(gettext('netPhotoGraphics Setup v%1$s%2$s: %3$s'), NETPHOTOGRAPHICS_VERSION, $clone, date('r')), true, true); // initialize the log file
	}

	if ($environ) {
		setupLog(gettext("Full environment"));
	} else {
		setupLog(gettext("Primitive environment"));
		if ($connectDBErr) {
			setupLog(sprintf(gettext("Query error: %s"), $connectDBErr), true);
		}
	}
	setNPGCookie('setup_test_cookie', NETPHOTOGRAPHICS_VERSION, 3600);
}

if (!isset($_setupCurrentLocale_result) || empty($_setupCurrentLocale_result)) {
	if (DEBUG_LOCALE)
		debugLog('Setup checking locale');
	$_setupCurrentLocale_result = i18n::setMainDomain();
	if (DEBUG_LOCALE)
		debugLog('$_setupCurrentLocale_result = ' . $_setupCurrentLocale_result);
}
if ($test_release = getOption('markRelease_state')) {
	$test_release = strpos($test_release, '-DEBUG');
}
$testRelease = defined('TEST_RELEASE') && TEST_RELEASE || $test_release !== false;

$taskDisplay = array('create' => gettext("create"), 'update' => gettext("update"));
clearstatcache();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml"<?php i18n::htmlLanguageCode(); ?>>
	<head>
		<title><?php printf('netPhotoGraphics %s', $upgrade); ?></title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<?php
		load_jQuery_CSS();
		load_jQuery_scripts('theme');
		?>
		<script type="text/javascript">
			var imageErr = false;
			function toggle_visibility(id) {
				var e = document.getElementById(id);
				if (e.style.display == 'block')
					e.style.display = 'none';
				else
					e.style.display = 'block';
			}
		</script>
		<?php
		scriptLoader(CORE_SERVERPATH . 'admin.css');
		scriptLoader(CORE_SERVERPATH . 'setup/setup.css');
		scriptLoader(CORE_SERVERPATH . 'loginForm.css');
		?>
	</head>
	<body>
		<div id="main">
			<h1>
				<?php printSiteLogoImage(gettext('netPhotoGraphics Setup')); ?>
				<span class="install_type"><?php echo $upgrade; ?></span>
			</h1>
			<br />
			<div id="content">
				<?php
				$blindInstall = $warn = false;
				if ($connection && empty($_options)) {
					primeOptions();
				}
				if ($system_check) {
					if ($blindInstall = ($upgrade && $autorun) && !setupUserAuthorized()) {
						ob_start(); //	hide output for auto-upgrade
					}
					?>
					<p>
						<?php printf(gettext('Welcome to netPhotoGraphics! This page will set up version %1$s on your web server.'), NETPHOTOGRAPHICS_VERSION); ?>
					</p>
					<h2><?php echo gettext("Systems Check:"); ?></h2>
					<?php
					/**
					 * ************************************************************************
					 *                                                                        *
					 *                             SYSTEMS CHECK                              *
					 *                                                                        *
					 * ************************************************************************
					 */
					global $_conf_vars;
					$good = true;
					if ($connection && $_loggedin != ADMIN_RIGHTS) {
						if ($testRelease) {
							?>
							<ul>
								<li class="note">
									<div class="notebox">
										<?php echo '<p>' . gettext('<strong>Note:</strong> The release you are installing has debugging settings enabled!') . '</p>'; ?>
									</div>
								</li>
							</ul>
							<?php
						}
						?>
						<ul>
							<?php
						} else {
							?>
							<ul>
								<?php
								$prevRel = false;
								checkmark(1, sprintf(gettext('Installing netPhotoGraphics v%s'), NETPHOTOGRAPHICS_VERSION), '', '');
							}

							if (preg_match('~/npg/~', SERVERPATH . '/')) {
								checkmark(0, gettext('Installation folder'), '', gettext('There may not be any folders named <em>npg</em> in the installation path.<br />Rename the <em>npg</em> folder and re-run setup.'));
								$good = false;
							}

							chdir(dirname(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE));
							$test = safe_glob('*.log');
							array_push($test, basename(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE));
							$p = true;
							$wrong = array();
							foreach ($test as $file) {
								$permission = fileperms(dirname(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE) . '/' . $file) & 0777;
								if (!checkPermissions($permission, 0600)) {
									$p = -1;
									$wrong[$file] = sprintf('%04o', $permission);
								}
							}

							checkMark($p, sprintf(gettext('<em>%s</em> security'), DATA_FOLDER), sprintf(gettext('<em>%s</em> security [is compromised]'), DATA_FOLDER), sprintf(gettext('You should make the sensitive files in the %1$s folder accessible by <em>owner</em> only (permissions = 0600). The file permissions for <em>%2$s</em> are %3$s which may allow unauthorized access.'), DATA_FOLDER, implode(', ', array_keys($wrong)), implode(', ', $wrong)));

							$err = versionCheck(PHP_MIN_VERSION, PHP_DESIRED_VERSION, PHP_VERSION);
							if (version_compare(PHP_VERSION, PHP_MIN_SUPPORTED_VERSION, '<')) {
								$vers = ' style="color: red;font-weight:bold;"';
							} else {
								$vers = '';
							}
							$good = checkMark($err, '<span' . $vers . '>' . sprintf(gettext("PHP version %s"), PHP_VERSION) . '</span>', "", sprintf(gettext('PHP Version %1$s or greater is required. Version %2$s or greater is strongly recommended as earlier versions may not be <a href="http://php.net/supported-versions.php">actively supported</a>. Use earlier versions at your own risk.'), PHP_MIN_VERSION, PHP_DESIRED_VERSION), false) && $good;

							checkmark($session && session_id() && $_initial_session_path !== false, gettext('PHP <code>Sessions</code>.'), gettext('PHP <code>Sessions</code> [appear to not be working].'), sprintf(gettext('PHP Sessions are required for administrative functions. Check your <code>session.save_path</code> (<code>%1$s</code>) and the PHP configuration <code>[session]</code> settings'), session_save_path()), true);

							if (preg_match('#(1|ON)#i', ini_get('session.use_strict_mode'))) {
								$strictSession = 1;
							} else {
								$strictSession = -1;
							}
							$good = checkMark($strictSession, gettext('PHP <code>session.use_strict_mode</code>'), gettext('PHP <code>session.use_strict_mode</code> [is not set]'), gettext('Enabling <code>session.use_strict_mode</code> is mandatory for general session security. Change your PHP.ini settings to <code>session.use_strict_mode = on</code>.')) && $good;

							if (!extension_loaded('suhosin')) {
								$blacklist = ini_get("suhosin.executor.func.blacklist");
								if ($blacklist) {
									$requiredUses = array('symlink' => 0);
									$abort = $issue = 0;
									$blacklist = explode(',', $blacklist);
									foreach ($blacklist as $key => $func) {
										if (array_key_exists($func, $requiredUses)) {
											$abort = true;
											$issue = $issue | $requiredUses[$func];
											if ($requiredUses[$func]) {
												$blacklist[$key] = '<span style="color: red;">' . $func . '*</span>';
											}
										}
									}
									$issue--;
									$good = checkMark($issue, '', gettext('<code>Suhosin</code> module [is enabled]'), sprintf(gettext('The following PHP functions are blocked: %s. Flagged functions are required. Other functions in the list may be used, possibly causing reduced functionality or failures.'), '<code>' . implode('</code>, <code>', $blacklist) . '</code>'), $abort) && $good;
								}
							}

							primeMark(gettext('Display_errors'));
							if (filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN)) {
								if ($testRelease || ((isset($_conf_vars['security_ack']) ? $_conf_vars['security_ack'] : NULL) & ACK_DISPLAY_ERRORS)) {
									$display = -1;
									$aux = '';
								} else {
									$display = 0;
									$aux = ' ' . acknowledge(ACK_DISPLAY_ERRORS);
								}
							} else {
								$display = true;
								$aux = '';
							}
							checkmark($display, gettext('PHP <code>display_errors</code>'), sprintf(gettext('PHP <code>display_errors</code> [is enabled]'), $display), gettext('This setting may result in PHP error messages being displayed on WEB pages. These displays may contain sensitive information about your site.') . $aux, $display && !$testRelease);

							$loaded = get_loaded_extensions();
							$loaded = array_flip($loaded);
							$desired = DESIRED_PHP_EXTENSIONS;
							$missing = '';
							$check = 1;
							foreach ($desired as $module) {
								if (!isset($loaded[$module])) {
									$missing .= '<strong>' . $module . '</strong>, ';
									$check = -1;
								}
							}
							checkMark($check, gettext('PHP extensions'), gettext('PHP extensions [missing]'), sprintf(gettext('To improve netPhotoGraphics performance and functionality you should enable the following PHP extensions: %s'), rtrim($missing, ', ')), false);

							if (!$setupMutex) {
								checkMark(-1, '', gettext('Locking the <em>setup</em> mutex failed.'), gettext('Without execution serialization sites may experience <em>race conditions</em> which may be causing errors or inconsistent data.'));
							}
							if ($_setupCurrentLocale_result === false) {
								checkMark(-1, gettext('PHP <code>setlocale()</code>'), ' ' . gettext('PHP <code>setlocale()</code> failed'), gettext("Locale functionality is not implemented on your platform or the specified locale does not exist. Language translation may not work.") . '<br />' .
												gettext('You can use the <em>debug</em> plugin to see which locales your server supports.'), false);
							}

							primeMark(gettext('mb_strings'));
							if (function_exists('mb_internal_encoding')) {
								if (mb_internal_encoding(LOCAL_CHARSET)) {
									$mbcharset = LOCAL_CHARSET;
									$mb = 1;
								} else {
									$mbcharset = mb_internal_encoding();
									$mb = -1;
								}
								$m2 = sprintf(gettext('<code>mb_internal_encoding("%1$s")</code> failed. Accented and multi-byte characters may notfunction properly.'), LOCAL_CHARSET);
								checkMark($mb, gettext("PHP <code>mbstring</code> package"), sprintf(gettext('PHP <code>mbstring</code> package [Your internal character set is <strong>%s</strong>]'), $mbcharset), $m2);
							} else {
								if (LOCAL_CHARSET == 'ISO-8859-1') {
									$set = 'UTF-8';
								} else {
									$set = 'ISO-8859-1';
								}
								$test = $_UTF8->convert('test', $set, LOCAL_CHARSET);
								if (empty($test)) {
									$m2 = gettext("You need to install the <code>mbstring</code> package or correct the issue with <code>iconv()</code>");
									checkMark(0, '', gettext("PHP <code>mbstring</code> package [is not present and <code>iconv()</code> is not working]"), $m2);
								} else {
									$m2 = gettext("Strings generated internally by PHP may not display correctly. (e.g. dates)");
									checkMark(-1, '', gettext("PHP <code>mbstring</code> package [is not present]"), $m2);
								}
							}
							if (($mbcharset = ini_get('default_charset')) == LOCAL_CHARSET) {
								$mb = 1;
							} else {
								$mb = -1;
							}
							checkMark($mb, gettext("PHP <code>default_charset</code>"), sprintf(gettext('PHP <code>default_charset</code> [Your default character set is <strong>%s</strong>]'), ini_get('default_charset')), sprintf(gettext('Setting <em>default_charset</em> to <strong>%s</strong> in your <em>php.ini</em> file is recommended to insure accented and multi-byte characters function properly.'), LOCAL_CHARSET));

							if ($environ) {
								/* Check for graphic library and image type support. */
								primeMark(gettext('Graphics library'));
								if (function_exists('gl_graphicsLibInfo')) {
									$graphics_lib = gl_graphicsLibInfo();
									if (array_key_exists('Library_desc', $graphics_lib)) {
										$library = $graphics_lib['Library_desc'];
									} else {
										$library = '';
									}
									$good = checkMark(!empty($library), sprintf(gettext("Graphics support: <code>%s</code>"), $library), gettext('Graphics support [is not installed]'), gettext('You need to install a graphics support library such as the <em>GD library</em> in your PHP')) && $good;
									if (!empty($library)) {
										$missing = array();
										if (!isset($graphics_lib['JPG'])) {
											$missing[] = 'JPEG';
										}
										if (!(isset($graphics_lib['GIF']))) {
											$missing[] = 'GIF';
										}
										if (!(isset($graphics_lib['PNG']))) {
											$missing[] = 'PNG';
										}
										if (!(isset($graphics_lib['WEBP']))) {
											$missing[] = 'WEBP';
										}
										if (count($missing) > 0) {

											switch (count($missing)) {
												case 1:
													$imgmissing = sprintf(gettext('Your PHP graphics library does not support %1$s'), $missing[0]);
													$err = -1;
													break;
												case 2:
													$imgmissing = sprintf(gettext('Your PHP graphics library does not support %1$s or %2$s'), $missing[0], $missing[1]);
													$err = -1;
													break;
												default:
													$last = array_pop($missing);
													$list = implode(', ', $missing);
													$imgmissing = sprintf(gettext('Your PHP graphics library does not support %1$s, or %2$s'), $list, $last);
													$err = 0;
													$good = false;
													break;
											}
											$mandate = gettext("To correct this you need to install a Graphics library with appropriate image support in your PHP");
											checkMark($err, gettext("PHP graphics image support"), '', $imgmissing .
															"<br />" . gettext("The unsupported image types will not be viewable in your albums.") .
															"<br />" . $mandate);
										}
										if (!gl_imageCanRotate()) {
											checkMark(-1, '', gettext('Graphics Library rotation support [is not present]'), gettext('The graphics support library does not provide support for image rotation.'));
										}
									}
								} else {
									$graphicsmsg = '';
									foreach ($_graphics_optionhandlers as $handler) {
										$graphicsmsg .= $handler->canLoadMsg($handler);
									}
									checkmark(0, '', gettext('Graphics support [configuration error]'), gettext('No image handling library was loaded. Be sure that your PHP has a graphics support.') . ' ' . trim($graphicsmsg));
								}
							}
							if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE)) {
								require( SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE);
								$cfg = true;
							} else {
								$cfg = false;
							}


							$good = checkMark($cfg, sprintf(gettext('<em>%1$s</em> file'), CONFIGFILE), sprintf(gettext('<em>%1$s</em> file [does not exist]'), CONFIGFILE), sprintf(gettext('Setup was not able to create this file. You will need to copy the <code>%1$s/netPhotoGraphics_cfg.txt</code> file to <code>%2$s/%3$s</code> then edit it as indicated in the file’s comments.'), CORE_FOLDER, DATA_FOLDER, CONFIGFILE)) && $good;
							if ($cfg) {
								primeMark(gettext('File permissions'));
								if ($environ) {
									$chmodselector = '<form action="#">' .
													'<input type="hidden" name="xsrfToken" value="' . setupXSRFToken() . '" />' .
													'<input type="hidden" name="autorun" value="' . str_replace('&autorun=', '', $autorunq) . '">' .
													'<input type="hidden" name="debug" value="' . intval($debug) . '">' .
													sprintf(gettext('Set File permissions to %s'), permissionsSelector($permission_names, $chmod)) .
													'</form>';
								} else {
									$chmodselector = '';
								}
								if (array_key_exists($chmod | 4, $permission_names)) {
									$value = sprintf(gettext('<em>%1$s</em> (<code>0%2$o</code>)'), $permission_names[$chmod | 4], $chmod);
								} else {
									$value = sprintf(gettext('<em>unknown</em> (<code>%o</code>)'), $chmod);
								}
								if ($chmod > 0664) {
									if (isset($_conf_vars['CHMOD'])) {
										$severity = -3;
									} else {
										$severity = -1;
									}
								} else {
									$severity = -2;
								}
								$msg = sprintf(gettext('File Permissions [are %s]'), $value);
								checkMark($severity, $msg, $msg, gettext('If file permissions are not set to <em>strict</em> or tighter there could be a security risk. However, on some servers the software does not function correctly with tight file permissions. If permission errors occur, run setup again and select a more relaxed permission.') .
												$chmodselector);

								$notice = 0;
								if (setupUserAuthorized()) {
									if ($environ) {
										if (strtoupper(PHP_OS) == 'DARWIN') {
											checkMark(-1, '', gettext('Your filesystem is Macintosh'), gettext('Macintosh file names containing diacritical marks are beyond the scope of this software. You should avoid these.'), false);
											?>
											<input type="hidden" name="FILESYSTEM_CHARSET" value="UTF-8" />
											<?php
										} else {
											primeMark(gettext('Character set'));
											$sets = array_merge($_UTF8->iconv_sets, $_UTF8->mb_sets);
											$charset_defined = isset($sets[FILESYSTEM_CHARSET]) ? $sets[FILESYSTEM_CHARSET] : NULL;
											$test = '';
											if (($dir = opendir(SERVERPATH . '/' . DATA_FOLDER . '/')) !== false) {
												while (($file = readdir($dir)) !== false) {
													if (preg_match('/^charset([\._])t.*$/', $file, $matches)) {
														$test = $file;
														$test_internal = 'charset' . $matches[1] . 'tést';
														if (getSuffix($file)) {
															$test_internal .= '.' . getSuffix($file);
														}
														break;
													}
												}
												closedir($dir);
											}
											if (isset($_REQUEST['charset_attempts'])) {
												$tries = sanitize_numeric($_REQUEST['charset_attempts']);
											} else {
												$tries = 0;
											}

											switch (FILESYSTEM_CHARSET) {
												case 'ISO-8859-1':
													if ($tries & 2) {
														$trialset = 'unknown';
													} else {
														$trialset = 'UTF-8';
														$tries = $tries | 1;
													}
													break;
												default:
													if ($tries & 1) {
														$trialset = 'unknown';
													} else {
														$trialset = 'ISO-8859-1';
														$tries = $tries | 2;
													}
													break;
											}
											$msg2 = sprintf(gettext('If your server filesystem character set is different from <code>%s</code> and you create album or image filenames names containing characters with diacritical marks you may have problems with these objects.'), $charset_defined) . '<br />' . "\n" .
															'<form action="#">' .
															'<input type="hidden" name="xsrfToken" value="' . setupXSRFToken() . '" />' . "\n" .
															'<input type="hidden" name="charset_attempts" value="' . $tries . '" />' . "\n" .
															'<input type="hidden" name="autorun" value="' . str_replace('&autorun=', '', $autorunq) . '">' . "\n" .
															'<input type="hidden" name="debug" value="' . $debug . '">' . "\n" .
															"\n" .
															gettext('Change the filesystem character set define to %1$s') . "\n" .
															"\n" .
															'</form>' . "\n" .
															'<br class="clearall" />' . "\n";

											if (isset($_conf_vars['FILESYSTEM_CHARSET'])) {
												$selectedset = $_conf_vars['FILESYSTEM_CHARSET'];
											} else {
												$selectedset = 'unknown';
											}
											$msg = '';
											if ($test) {
												//	fount the test file
												if (file_exists(internalToFilesystem($test_internal))) {
													//	and the active character set define worked
													if (!isset($_conf_vars['FILESYSTEM_CHARSET'])) {
														$_config_contents = configFile::update('FILESYSTEM_CHARSET', FILESYSTEM_CHARSET, $_config_contents);
														configFile::store($_config_contents);
													}
													$notice = 1;
													$msg = sprintf(gettext('The filesystem character define is %1$s [confirmed]'), $charset_defined);
													$msg1 = '';
												} else {
													if ($selectedset == 'unknown') {
														$notice = 1;
														$msg = gettext('The filesystem character define is UTF-8 [assumed]');
														$msg1 = '';
													} else {
														//	active character set is not correct
														$notice = 0;
														$msg1 = sprintf(gettext('The filesystem character define is %1$s [which seems wrong]'), $charset_defined);
													}
												}
											} else {
												//	no test file
												$msg1 = sprintf(gettext('The filesystem character define is %1$s [no test performed]'), $charset_defined);
												if (isset($_conf_vars['FILESYSTEM_CHARSET'])) {
													//	but we have a define value
													$notice = -3;
												} else {
													//	no defined value, who knows....
													$notice = -1;
												}
											}
											checkMark($notice, $msg, $msg1, sprintf($msg2, charsetSelector($trialset)));
										}
										// UTF-8 URI
										if ($notice != -1) {
											$test = copy(CORE_SERVERPATH . 'images/np_gold.png', $testjpg = SERVERPATH . '/' . DATA_FOLDER . '/' . internalToFilesystem('tést.jpg'));
											if (file_exists($testjpg)) {
												?>
												<li id="internal" class="pass limited">
													<span>
														<?php echo CHECKMARK_GREEN; ?>
														<?php echo gettext('Image URIs appear to require the <em>UTF-8</em> character set.') ?>
														<img src="<?php echo WEBPATH . '/' . DATA_FOLDER . '/' . urlencode('tést.jpg'); ?>" class="test_image"  onerror="imgError('internal');" width="1" height="1" />
													</span>
												</li>
												<li id="filesystem" class="fail limited" style="display: none;">
													<span>
														<?php echo CHECKMARK_GREEN; ?>
														<?php echo gettext('Image URIs appear require the <em>filesystem</em> character set.'); ?>
														<img src="<?php echo WEBPATH . '/' . DATA_FOLDER . '/' . urlencode(internalToFilesystem('tést.jpg')); ?>" title="filesystem" class="test_image" onerror="imgError('filesystem');" />
													</span>
												</li>
												<li id="unknown" class="warn" style="display: none;">
													<span>
														<?php echo WARNING_SIGN_ORANGE; ?>
														<?php echo gettext('Image URIs with diacritical marks appear to fail.'); ?>
													</span>
												</li>
												<script type="text/javascript">

						<?php
						if ($displayLimited) {
							?>
														window.addEventListener('load', function () {
															$('.limited').hide();
														}, false);
							<?php
						}
						?>

													var failed = 0;
													function imgError(title) {
														failed++;
														$('#' + title).hide();
														if (failed > 1) {
															$('#unknown').show();
															$('#setUTF8URI').val('unknown');
														} else {
															if (title == 'internal') {
																$('#setUTF8URI').val('filesystem');
																$('#filesystem').show();
															}
														}
													}
												</script>
												<?php
											}
										}
									}
								}
							}
							primeMark(gettext('Database'));
							foreach ($engines as $engine) {
								$handler = $engine['engine'];
								if ($handler == $confDB && $engine['enabled']) {
									$good = checkMark(1, sprintf(gettext('PHP <code>%s</code> support for configured Database'), $handler), '', '') && $good;
								} else {
									if (!$displayLimited) {
										if ($engine['enabled']) {

											if (isset($enabled['experimental'])) {
												?>
												<li class="note_warn">
													<?php echo BULLSEYE_DARKORANGE; ?>
													<?php echo sprintf(gettext(' <code>%1$s</code> support (<a onclick="$(\'#%1$s\').toggle()" >experimental</a>)'), $handler); ?>
												</li>
												<p class="warning" id="<?php echo $handler; ?>" style="display: none;">
													<?php echo $enabled['experimental'] ?>
												</p>
												<?php
											} else {
												?>
												<li class="note_ok">
													<?php echo BULLSEYE_GREEN; ?>
													<?php echo sprintf(gettext('PHP <code>%s</code> support'), $handler); ?>
												</li>
												<?php
											}
										} else {
											?>
											<li class="note_exception">
												<?php echo BULLSEYE_RED; ?>
												<?php echo sprintf(gettext('PHP <code>%s</code> support [is not installed]'), $handler); ?>
											</li>
											<?php
										}
									}
								}
							}
							if ($connection) {
								if (empty($_conf_vars['mysql_database'])) {
									$connection = false;
									$connectDBErr = gettext('No database selected');
								}
							} else {
								$connectDBErr = db_error();
							}
							if ($_DB_connection) { // connected to DB software
								$dbsoftware = db_software();
								$dbapp = $dbsoftware['application'];
								$dbversion = $dbsoftware['version'];
								$required = $dbsoftware['required'];
								$desired = $dbsoftware['desired'];
								$deprecated = isset($dbsoftware['deprecated']);
								$sqlv = versionCheck($required, $desired, $dbversion);
								if ($sqlv && $deprecated) {
									$preferredDB = array_keys($preferences);
									$preferredDB = reset($preferredDB);
									checkMark(-1, sprintf(gettext('%1$s version %2$s'), $dbapp, $dbversion), "", sprintf(gettext('The PHP %1$s extension is deprecated. You should enable the PHP %2$s extension.'), $dbapp, $preferredDB), false);
								} else {
									$good = checkMark($sqlv, sprintf(gettext('%1$s version %2$s'), $dbapp, $dbversion), "", sprintf(gettext('%1$s Version %2$s or greater is required. Version %3$s or greater is preferred. Use a lower version at your own risk.'), $dbapp, $required, $desired), false) && $good;
								}
							}
							primeMark(gettext('Database connection'));

							if ($cfg) {
								if ($adminstuff = !extension_loaded(strtolower($selected_database)) || !$connection) {
									if (is_writable(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE)) {
										$good = false;
										checkMark(false, '', gettext("Database credentials in configuration file"), sprintf(gettext('<em>%1$s</em> reported: %2$s'), DATABASE_SOFTWARE, $connectDBErr));
										// input form for the information
										if (!isset($_conf_vars['mysql_port']) || empty($_conf_vars['mysql_port'])) {
											$_conf_vars['mysql_port'] = ini_get('mysqli.default_port');
										}
										if (!isset($_conf_vars['mysql_socket']) || empty($_conf_vars['mysql_socket'])) {
											$_conf_vars['mysql_socket'] = ini_get('mysqli.default_socket');
										}
										include(__DIR__ . '/setup-sqlform.php');
									} else {
										if ($connectDBErr) {
											$msg = $connectDBErr;
										} else {
											$msg = gettext("You have not correctly set your <strong>Database</strong> <code>user</code>, <code>password</code>, etc. in your configuration file and <strong>setup</strong> is not able to write to the file.");
										}
										$good = checkMark(!$adminstuff, gettext("Database setup in configuration file"), '', $msg) && $good;
									}
								} else {
									$good = checkMark((bool) $connection, sprintf(gettext('Connect to %s'), DATABASE_SOFTWARE), gettext("Connect to Database [<code>CONNECT</code> query failed]"), $connectDBErr) && $good;
								}
							}

							if ($_DB_connection) {
								if ($connection) {
									if ($DBcreated) {
										checkMark(1, sprintf(gettext('Database <code>%s</code> created'), $_conf_vars['mysql_database']), '');
									}
								} else {
									$good = 0;
									if ($oktocreate) {
										?>
										<li class="note">
											<div class="notebox">
												<p><?php echo sprintf(gettext('Click here to attempt to create <a href="?Create_Database" >%s</a>.'), $_conf_vars['mysql_database']); ?></p>
											</div>
										</li>
										<?php
									} else if (!empty($_conf_vars['mysql_database'])) {
										checkMark(0, '', sprintf(gettext('Database <code>%s</code> not created [<code>CREATE DATABASE</code> query failed]'), $_conf_vars['mysql_database']), $connectDBErr);
									}
								}
								if ($environ && $connection) {
									$oldmode = db_getSQLmode();
									$result = db_setSQLmode();
									$msg = gettext('You may need to set <code>SQL mode</code> <em>empty</em> in your Database configuration.');
									if ($result) {
										$mode = db_getSQLmode();
										if ($mode === false) {
											checkMark(-1, '', sprintf(gettext('<code>SQL mode</code> [query failed]'), $oldmode), $msg);
										} else {
											if ($oldmode != $mode) {
												checkMark(-1, sprintf(gettext('<code>SQL mode</code> [<em>%s</em> overridden]'), $oldmode), '', gettext('Consider setting it <em>empty</em> in your Database configuration.'));
											} else {
												if (!empty($mode)) {
													$err = -1;
												} else {
													$err = 1;
												}
												checkMark($err, gettext('<code>SQL mode</code>'), sprintf(gettext('<code>SQL mode</code> [is set to <em>%s</em>]'), $mode), gettext('Consider setting it <em>empty</em> if you get Database errors.'));
											}
										}
									} else {
										checkMark(-1, '', gettext('<code>SQL mode</code> [SET SESSION failed]'), $msg);
									}

									$dbn = "`" . $_conf_vars['mysql_database'] . "`.*";
									$db_results = db_permissions();

									$access = -1;
									$rightsfound = 'unknown';
									$rightsneeded = array(gettext('Select') => 'SELECT', gettext('Create') => 'CREATE', gettext('Drop') => 'DROP', gettext('Insert') => 'INSERT',
											gettext('Update') => 'UPDATE', gettext('Alter') => 'ALTER', gettext('Delete') => 'DELETE', gettext('Index') => 'INDEX');
									ksort($rightsneeded, SORT_LOCALE_STRING);
									$neededlist = '';
									foreach ($rightsneeded as $right => $value) {
										$neededlist .= '<code>' . $right . '</code>, ';
									}
									$neededlist = substr($neededlist, 0, -2) . ' ';
									$i = strrpos($neededlist, ',');
									$neededlist = substr($neededlist, 0, $i) . ' ' . gettext('and') . substr($neededlist, $i + 1);
									if ($db_results) {
										$report = "<br /><br /><em>" . gettext("Grants found:") . "</em> ";
										foreach ($db_results as $row) {
											$row = stripcslashes($row);
											$row_report = "<br /><br />" . $row;
											$r = str_replace(',', '', $row);
											preg_match('/\sON(.*)\sTO\s?/i', $r, $matches);
											$found = trim(isset($matches[1]) ? $matches[1] : NULL);
											if ($partial = (($i = strpos($found, '%')) !== false)) {
												$found = substr($found, 0, $i);
											}
											$rights = array_flip(explode(' ', $r));
											$rightsfound = 'insufficient';
											if (($found == $dbn) || ($found == "*.*") || $partial && preg_match('/^' . $found . '/xis', $dbn)) {
												$allow = true;
												foreach ($rightsneeded as $key => $right) {
													if (!isset($rights[$right])) {
														$allow = false;
													}
												}
												if (isset($rights['ALL']) || $allow) {
													$access = 1;
												}
												$report .= '<strong>' . $row_report . '</strong>';
											} else {
												$report .= $row_report;
											}
										}
									} else {
										$report = "<br /><br />" . gettext("The <em>SHOW GRANTS</em> query failed.");
									}
									checkMark($access, sprintf(gettext('Database <code>access rights</code> for <em>%s</em>'), $_conf_vars['mysql_database']), sprintf(gettext('Database <code>access rights</code> for <em>%1$s</em> [%2$s]'), $_conf_vars['mysql_database'], $rightsfound), sprintf(gettext("Your Database user must have %s rights."), $neededlist) . $report);
								}
							}

							primeMark(gettext('netPhotoGraphics files'));
							clearstatcache();
							set_time_limit(120);
							$base = SERVERPATH . '/';
							getResidentFiles(SERVERPATH . '/' . CORE_FOLDER, stdExclude);
							if (CASE_INSENSITIVE) {
								$res = array_search(strtolower($base . CORE_FOLDER . '/netPhotoGraphics.package'), $_resident_files);
								$base = strtolower($base);
							} else {
								$res = array_search($base . CORE_FOLDER . '/netPhotoGraphics.package', $_resident_files);
							}
							unset($_resident_files[$res]);
							$cum_mean = filemtime(CORE_SERVERPATH . 'netPhotoGraphics.package');
							$hours = 3600;
							$lowset = $cum_mean - $hours;
							$highset = $cum_mean + $hours;

							$package_file_count = false;
							$package = file_get_contents(CORE_SERVERPATH . 'netPhotoGraphics.package');
							if (!empty($package)) {
								$package = strtr($package, array('%extensions%' => CORE_FOLDER . '/' . PLUGIN_FOLDER, '%core%' => CORE_FOLDER));
								if (CASE_INSENSITIVE) { // case insensitive file systems
									$package = strtolower($package);
								}
								$installed_files = explode("\n", trim($package));
								$count = array_pop($installed_files);
								$package_file_count = is_numeric($count) && ($count > 0) && ($count == count($installed_files));
							}
							if (!$package_file_count) {
								checkMark(-1, '', gettext("netPhotoGraphics package [missing]"), gettext('The file <code>netPhotoGraphics.package</code> is either missing, not readable, or defective. Your installation may be corrupt!'));
								$installed_files = array();
							}
							$folders = array();
							if ($updatechmod) {
								$permissions = 1;
								setupLog(sprintf(gettext('Setting permissions (0%o) for netPhotoGraphics package.'), $chmod), true);
							} else {
								$permission = 0;
							}
							foreach ($installed_files as $key => $value) {
								$component_data = explode(':', $value);
								$value = trim($component_data[0]);
								if (count($component_data) > 1) {
									$fromPackage = trim($component_data[1]);
								} else {
									$fromPackage = '';
								}
								$component = $base . $value;
								if (file_exists($component)) {
									$res = array_search($component, $_resident_files);
									if ($res !== false) {
										unset($_resident_files[$res]);
									}
									if (is_dir($component)) {
										if ($updatechmod) {
											chmod($component, $chmod | 0311);
											clearstatcache();
											$perms = fileperms($component) & 0777;
											if ($permissions == 1 && !checkPermissions($perms, $chmod | 0311)) {
												if (checkPermissions($perms & 0755, 0755) || $testRelease) { // could not set them, but they will work.
													$permissions = -1;
												} else {
													$permissions = 0;
												}
											}
										}
										$folders[$component] = $component;
										unset($installed_files[$key]);
										if (dirname($value) == THEMEFOLDER) {
											getResidentFiles($base . $value, stdExclude);
										}
									} else {
										if ($updatechmod) {
											chmod($component, $chmod);
											clearstatcache();
											$perms = fileperms($component) & 0777;
											if ($permissions == 1 && !checkPermissions($perms, $chmod)) {
												if (checkPermissions($perms & 0644, 0644) || $testRelease) { // could not set them, but they will work.
													$permissions = -1;
												} else {
													$permissions = 0;
												}
											}
										}

										$t = filemtime($component);
										if ((!($testRelease || $fromPackage == '*') && ($t < $lowset || $t > $highset))) {
											$installed_files[$key] = $value;
										} else {
											unset($installed_files[$key]);
										}
									}
								}
							}
							if ($updatechmod && count($folders) > 0) {
								foreach ($folders as $key => $folder) {
									if (!checkPermissions(fileperms($folder) & 0777, 0755)) { // need to set them?.
										chmod($folder, $chmod | 0311);
										clearstatcache();
										$perms = fileperms($folder) & 0777;
										if ($permissions == 1 && !checkPermissions($perms, $chmod | 0311)) {
											if (checkPermissions($perms & 0755, 0755) || $testRelease) { // could not set them, but they will work.
												$permissions = 0;
											} else {
												$permissions = -1;
											}
										}
									}
								}
							}
							$plugin_subfolders = array();
							$Cache_html_subfolders = array();
							foreach ($installed_files as $key => $component) {
								$folders = explode('/', $component);
								$folder = array_shift($folders);
								switch ($folder) {
									case ALBUMFOLDER:
									case CACHEFOLDER:
									case DATA_FOLDER:
									case UPLOAD_FOLDER:
										unset($installed_files[$key]);
										break;
									case USER_PLUGIN_FOLDER:
										if ($folder[strlen($folder) - 1] == '/') {
											$plugin_subfolders[] = implode('/', rtrim($folders, '/'));
										}
										unset($installed_files[$key]); // not required
										break;
									case STATIC_CACHE_FOLDER:
										$Cache_html_subfolders[] = implode('/', $folders);
										unset($installed_files[$key]);
										break;
								}
							}
							$filelist = '<br />';

							foreach ($installed_files as $extra) {
								$filelist .= filesystemToInternal(str_replace($base, '', $extra) . '<br />');
							}

							if (npgFunctions::hasPrimaryScripts() && count($installed_files) > 0) {
								if ($testRelease) {
									$msg1 = gettext("Core files [This is a <em>debug</em> build. Some files are missing or seem wrong]");
								} else {
									$msg1 = gettext("Core files [Some files are missing or seem wrong]");
								}
								$msg2 = gettext('Perhaps there was a problem with the upload. You should check the following files: ') . '<span class="filelist"><code>' . substr($filelist, 0, -6) . '</code></span>';
								$mark = -1;
							} else {
								if (isset($rootupdate) && !$rootupdate) {
									$mark = 0;
									$msg1 = gettext("Core files [Could not update the root <em>index.php</em> file.]");
									$msg2 = sprintf(gettext('Perhaps there is a permissions issue. You should manually copy the %s <em>root_index.php</em> file to the installation root and rename it <em>index.php</em>.'), CORE_FOLDER) . ' ' . gettext('Then manually edit the file to replace the defines with their definition values.');
								} else {
									if (npgFunctions::hasPrimaryScripts()) {
										if ($testRelease) {
											$mark = -1;
											$msg1 = gettext("Core files [This is a <em>debug</em> build]");
										} else {
											$msg1 = '';
											$mark = 1;
										}
									} else {
										$mark = -1;
										$msg1 = gettext("Core files [This is a <em>clone</em> installation]");
									}
									$msg2 = '';
								}
							}
							checkMark($mark, gettext("Core files"), $msg1, $msg2, false);
							primeMark(gettext('Installation files'));
							if (setupUserAuthorized() && $connection && npgFunctions::hasPrimaryScripts()) {
								$systemlist = $filelist = array();
								$phi_ini_count = $svncount = 0;
								foreach ($_resident_files as $extra) {
									if (getSuffix($extra) == 'xxx') {
										unlink($extra); //	presumed to be protected copies of the setup files
									} else if (strpos($extra, 'php.ini') !== false) {
										$phi_ini_count++;
									} else if ($testRelease || (strpos($extra, '/.svn') === false)) {
										$systemlist[] = str_replace($base, '', $extra);
									} else {
										$svncount++;
									}
								}
								if ($svncount) {
									$filelist[] = '<br />' . sprintf(ngettext('.svn [%s instance]', '.svn [%s instances]', $svncount), $svncount);
								}
								if ($phi_ini_count && $testRelease) {
									$filelist[] = '<br />' . sprintf(ngettext('php.ini [%s instance]', 'php.ini [%s instances]', $phi_ini_count), $phi_ini_count);
								}
								if ($package_file_count) { //	no point in this if the package list was damaged!
									if (!empty($systemlist)) {
										if (!TEST_RELEASE) { //	we don't want to accidentally delete a new script during debug
											foreach ($systemlist as $key => $file_s) {
												$file8 = $_UTF8->convert($file_s, FILESYSTEM_CHARSET, 'UTF-8');
												$file = $base . $file_s;
												if (!is_dir($file)) {
													chmod($file, 0777);
													if (!unlink($file) || file_exists($file)) {
														$filelist[] = $file8;
													} else {
														unset($systemlist[$key]);
														setuplog(sprintf(gettext('Deleted %1$s'), $file8), true);
													}
												}
											}
											rsort($systemlist);
											foreach ($systemlist as $key => $file_s) {
												$file8 = $_UTF8->convert($file, FILESYSTEM_CHARSET, 'UTF-8');
												$file = $base . $file_s;
												chmod($file, 0777);
												if (is_dir($file)) {
													$offspring = safe_glob($file . '/*.*');
													foreach ($offspring as $child) {
														if (!(unlink($child) || !file_exists($child))) {
															$filelist[] = $file8 . '/' . $_UTF8->convert($file_s, FILESYSTEM_CHARSET, 'UTF-8');
														}
													}
													if (!@rmdir($file) || is_dir($file)) {
														$filelist[] = $file8;
													} else {
														setuplog(sprintf(gettext('Deleted %1$s'), $file8), true);
													}
												} else {
													if (!unlink($file) || file_exists($file)) {
														$filelist[] = $file8;
													} else {
														unset($systemlist[$key]);
														setuplog(sprintf(gettext('Deleted %1$s'), $file8), true);
													}
												}
											}
											if (!empty($filelist)) {
												checkmark(-1, '', gettext('Core folders [Some unknown files were found]'), gettext('The following files could not be deleted.') . '<br /><code>' . implode('<br />', $filelist) . '<code>');
											}
										} else {
											checkMark(-1, '', gettext('Core folders [Some unknown files were found]'), gettext('You should remove the following files: ') . '<br /><code>' . $_UTF8->convert(implode('<br />', $systemlist), FILESYSTEM_CHARSET, 'UTF-8') .
															'</code>');
										}
									}
									checkMark($permissions, gettext("Core file permissions"), gettext("Core file permissions [not correct]"), gettext('Setup could not set the one or more components to the selected permissions level. You will have to set the permissions manually.'));
								}
							}

							$msg = gettext("<em>.htaccess</em> file");
							$Apache = stristr($_SERVER['SERVER_SOFTWARE'], "apache");
							$Nginx = stristr($_SERVER['SERVER_SOFTWARE'], "nginx");
							$htfile = SERVERPATH . '/.htaccess';
							if (file_exists($htfile)) {
								$ht = trim(file_get_contents($htfile));
							} else {
								$ht = false;
								$copyaccess = $Apache;
							}
							$vr = "";
							$ch = 1;
							$j = 0;
							$err = '';
							$desc = '';
							if (empty($ht)) {
								$err = gettext("<em>.htaccess</em> file [is empty or does not exist]");
								$ch = -1;
								if ($Apache) {
									$desc = gettext('If you have the mod_rewrite module enabled an <em>.htaccess</em> file is required the root folder to create cruft-free URLs.') .
													'<br /><br />' . gettext('You can ignore this warning if you do not intend to set the <code>mod_rewrite</code> option.');
									if (setupUserAuthorized()) {
										$desc .= ' ' . get_npgButton('button', gettext('Make setup create the file'), array('buttonLink' => "?copyhtaccess")) . '<br clear="all"><br />';
									}
								} else if ($Nginx) {
									$err = gettext("Server seems to be <em>nginx</em>");
									$mod = "&amp;mod_rewrite"; //	enable test to see if it works.
									$desc = gettext('If you wish to create cruft-free URLs, you will need to configuring <em>rewriting</em> for your NGINX server so that any link that does not go directly to a file goes to the installation root <code>index.php</code> script.') . ' ' .
													'<br /><br />' . gettext('You can ignore this warning if you do not intend to set the <code>mod_rewrite</code> option.');
								} else {
									$mod = "&amp;mod_rewrite"; //	enable test to see if it works.
									$desc = gettext("Server seems not to be <em>Apache</em> or <em>Apache-compatible</em>, <code>mod_rewrite</code> may not be available.");
								}
							} else {
								preg_match('~version (.*);~i', $newht, $matches);
								$newvr = $matches[1];

								if (preg_match('~version (.*);~i', $ht, $matches)) {
									$vr = $matches[1];
								} else {
									$vr = false;
								}

								$ch = empty($vr) || version_compare($vr, $newvr, '>=');
								$d = rtrim(str_replace('\\', '/', dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])))), '/') . '/';
								$d = str_replace(' ', '%20', $d); //	apache appears to trip out if there is a space in the rewrite base

								if (!$ch) {
									if (!$Apache) {
										$desc = gettext("Server seems not to be Apache or Apache-compatible, <code>.htaccess</code> not required.");
										$ch = -1;
									} else {
										$desc = sprintf(gettext("The <em>.htaccess</em> file in your root folder is not the same version as the one distributed with this version of netPhotoGraphics. If you have made changes to <em>.htaccess</em>, merge those changes with the <em>%s/htaccess</em> file to produce a new <em>.htaccess</em> file."), CORE_FOLDER);
										if (setupUserAuthorized()) {
											$desc .= ' ' . get_npgButton('button', gettext('Replace the existing <em>.htaccess</em> file with the current version'), array('buttonLink' => "?copyhtaccess")) . '<br clear="all"><br />';
										}
									}
									$err = gettext("<em>.htaccess</em> file [wrong version]");
								}
							}

							$rw = '';
							if ($ch > 0) {
								if (preg_match('~RewriteEngine\s+(.*)\s~i', $ht, $matches)) {
									$rw = $matches[1];
								} else {
									$rw = 'off';
								}
								$msg = sprintf(gettext("<em>.htaccess</em> file (<em>RewriteEngine</em> is <strong>%s</strong>)"), $rw);
								$mod = "&amp;mod_rewrite=$rw";
							}
							$good = checkMark($ch, $msg, $err, $desc, false) && $good;

							$base = true;
							$f = '';
							if ($ht) {
								if (preg_match('~RewriteBase\s+(.*)\s~', $ht, $matches)) {
									$bs = $matches[1];
									$base = ($bs == $d);
									$b = sprintf(gettext("<em>.htaccess</em> RewriteBase is <code>%s</code>"), $bs);
									$err = sprintf(gettext("<em>.htaccess</em> RewriteBase is <code>%s</code> [Does not match install folder]"), $bs);
								} else {
									$base = 0;
									$b = '';
									$err = gettext("<em>.htaccess</em> RewriteBase [is <em>missing</em>]");
								}

								$f = '';
								$save = false;
								if (!$base) {
									if ($base === 0) {
										$ht = preg_replace('~RewriteEngine\s+(.*)\s~i', "RewriteEngine $1\n\nRewriteBase $d\n", $ht);
									} else {
										$ht = preg_replace('~RewriteBase\s+(.*)\s~i', "RewriteBase $d\n", $ht);
									}
									$save = $base = true;
									$b = sprintf(gettext("<em>.htaccess</em> RewriteBase is <code>%s</code> (fixed)"), $d);
								}

								if ($save) {
									// try and fix it
									chmod($htfile, 0777);
									if (is_writeable($htfile)) {
										if (file_put_contents($htfile, $ht)) {
											$err = '';
										}
										clearstatcache();
									}
									chmod($htfile, 0444);
								}
								$good = checkMark($base, $b, $err, gettext("Setup was not able to write to the file. Change RewriteBase match the install folder.") .
																"<br />" . sprintf(gettext("Either make the file writeable or set <code>RewriteBase</code> in your <code>.htaccess</code> file to <code>%s</code>."), $d)) && $good;
							}
							//robots.txt file
							$robots = file_get_contents(dirname(__DIR__) . '/robots.txt');
							if ($robots === false) {
								checkmark(-1, gettext('<em>robots.txt</em> file'), gettext('<em>robots.txt</em> file [Not created]'), gettext('Setup could not find the  <em>example_robots.txt</em> file.'));
							} else {
								if (file_exists(SERVERPATH . '/robots.txt')) {
									checkmark(-2, gettext('<em>robots.txt</em> file'), gettext('<em>robots.txt</em> file [Not created]'), gettext('Setup did not create a <em>robots.txt</em> file because one already exists.'));
								} else {
									$robots = str_replace('%FULLWEBPATH%', FULLWEBPATH, $robots);
									$rslt = file_put_contents(SERVERPATH . '/robots.txt', $robots);
									if ($rslt === false) {
										$rslt = -1;
									} else {
										$rslt = 1;
									}
									checkmark($rslt, gettext('<em>robots.txt</em> file'), gettext('<em>robots.txt</em> file [Not created]'), gettext('Setup could not create a <em>robots.txt</em> file.'));
								}
							}

							if (isset($_conf_vars['album_folder'])) {
								$albumfolder = str_replace('\\', '/', $_conf_vars['album_folder']);
								switch ($_conf_vars['album_folder_class']) {
									default:
										$_conf_vars['album_folder_class'] = 'std';
									case 'std':
										$albumfolder = str_replace('\\', '/', SERVERPATH) . $albumfolder;
										break;
									case 'in_webpath':
										$webpath = $_SERVER['SCRIPT_NAME'];
										$root = SERVERPATH;
										if (!empty($webpath)) {
											$root = str_replace('\\', '/', dirname($root));
										}
										$albumfolder = $root . $albumfolder;
										break;
								}
								$good = folderCheck('albums', $albumfolder, $_conf_vars['album_folder_class'], NULL, true, $chmod | 0311, $updatechmod) && $good;
							} else {
								checkmark(-1, gettext('<em>albums</em> folder'), gettext('<em>albums</em> folder [The line <code>\$conf[\'album_folder\']</code> is missing from your configuration file]'), sprintf(gettext('You should update your configuration file to conform to the current %1$s example file.'), CONFIGFILE));
							}

							$good = folderCheck('cache', SERVERPATH . '/' . CACHEFOLDER . '/', 'std', NULL, true, $chmod | 0311, $updatechmod) && $good;
							$good = checkmark(file_exists($en_US), gettext('<em>locale</em> folders'), gettext('<em>locale</em> folders [Are not complete]'), gettext('Be sure you have uploaded the complete netPhotoGraphics package. You must have at least the <em>en_US</em> folder.')) && $good;
							$good = folderCheck(gettext('uploaded'), SERVERPATH . '/' . UPLOAD_FOLDER . '/', 'std', NULL, false, $chmod | 0311, $updatechmod) && $good;
							$good = folderCheck(DATA_FOLDER, SERVERPATH . '/' . DATA_FOLDER . '/', 'std', NULL, false, $chmod | 0311, $updatechmod) && $good;
							if (is_dir(SERVERPATH . '/' . DATA_FOLDER . '/mutex')) {
								npgFunctions::removeDir(SERVERPATH . '/' . DATA_FOLDER . '/mutex');
							}
							if (!is_dir(SERVERPATH . '/' . DATA_FOLDER . '/' . MUTEX_FOLDER)) {
								mkdir(SERVERPATH . '/' . DATA_FOLDER . '/' . MUTEX_FOLDER, $chmod | 0311);
							}

							$good = folderCheck(gettext('HTML cache'), SERVERPATH . '/' . STATIC_CACHE_FOLDER . '/', 'std', $Cache_html_subfolders, true, $chmod | 0311, $updatechmod) && $good;
							$good = folderCheck(gettext('Third party plugins'), USER_PLUGIN_SERVERPATH, 'std', $plugin_subfolders, true, $chmod | 0311, $updatechmod) && $good;
							?>
						</ul>
						<?php
						if ($good) {
							$dbmsg = "";
						} else {
							if (setupUserAuthorized()) {
								?>
								<ul>
									<li class="fail"><?php npgButton('button', CLOCKWISE_OPEN_CIRCLE_ARROW_RED . ' ' . gettext("Refresh"), array('buttonLink' => "?refresh", 'buttonTitle' => gettext("Setup failed."), 'buttonClass' => 'submitbutton')); ?></li>
								</ul>
								<div class="error">
									<?php echo gettext("You need to address the problems indicated above then run <code>setup</code> again."); ?>
								</div>
								<br class="clearall" />
								<br />
								<?php
							} else {
								?>
								<div class="error">
									<?php
									if (npg_loggedin()) {
										echo gettext("You need <em>USER ADMIN</em> rights to run setup.");
									} else {
										echo gettext('You must be logged in to run setup.');
									}
									?>
								</div>
								<?php
								$_authority->printLoginForm('', false);
							}
							?>
							<br class="clearall" />
							<?php
							echo "\n</div><!-- content -->";
							printSetupFooter($setup_checked);
							echo "\n</div><!-- main -->";
							echo "</body>";
							echo "</html>";
							exit();
						}
					} else {
						$dbmsg = gettext("database connected");
					} // system check



					if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE)) {
						require(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE);

						$task = '';
						if (isset($_GET['create'])) {
							$task = 'create';
							$create = array_flip(explode(',', sanitize($_REQUEST['create'])));
						}
						if (isset($_REQUEST['update'])) {
							$task = 'update';
						}
						$updateErrors = false;

						if (isset($_GET['create']) || isset($_REQUEST['update']) && db_connect($_conf_vars, false)) {
							primeMark(gettext('Database update'));
							if (getOption('UTF-8') !== 'utf8mb4') {
								$sql = 'SELECT COLLATION_NAME FROM information_schema.columns ' .
												'WHERE TABLE_SCHEMA = "' . $_conf_vars['mysql_database'] . '" ' .
												'AND TABLE_NAME = "' . trim(prefix('albums'), '`') . '" ' .
												'AND COLUMN_NAME = "title"';
								$result = query_single_row($sql);
								if ($result && array_pop($result) == 'utf8mb4_unicode_ci') {
									$_config_contents = @file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE);
									$_config_contents = configFile::update('UTF-8', 'utf8mb4', $_config_contents);
									configFile::store($_config_contents);
								}
							}


							require_once(CORE_SERVERPATH . 'setup/database.php');

							unset($_tableFields);
							if ($updateErrors) {
								$autorun = false;
								$msg = gettext('Database structure update completed with errors. See the <code>setup</code> log for details.');
							} else if ($_DB_Structure_change) {
								$msg = gettext('Database structure updated.');
							} else {
								$msg = gettext('Database update is not required.');
							}


							setupLog($msg, true);
							?>
							<h3><?php echo $msg; ?></h3>
							<script type = "text/javascript">
								$("#prime<?php echo $primeid; ?>").remove();
							</script>
							<?php
							// set defaults on any options that need it
							if (ob_get_length()) {
								ob_flush();
							}
							flush();
							require(__DIR__ . '/setup-option-defaults.php');

							if ($debug == 'albumids') {
								// fixes 1.2 move/copy albums with wrong ids
								$albums = $_gallery->getAlbums();
								foreach ($albums as $album) {
									checkAlbumParentid($album, NULL, 'setuplog');
								}
							}

							if ($_loggedin == ADMIN_RIGHTS) {
								$filelist = safe_glob(SERVERPATH . "/" . BACKUPFOLDER . '/*.zdb');
								if (count($filelist) > 0) {
									$link = sprintf(gettext('You may %1$sset your admin user and password%3$s or %2$srun backup-restore%3$s'), '<a href="' . getAdminLink('admin-tabs/users.php') . '?page=admin">', '<a href="' . getAdminLink(UTILITIES_FOLDER . '/backup_restore.php') . '">', '</a>');
									$autorun = false;
								} else {
									$link = sprintf(gettext('You need to %1$sset your admin user and password%2$s'), '<a href="' . getAdminLink('admin-tabs/users.php') . '?page=admin">', '</a>');
									if ($autorun == 'admin' || $autorun == 'gallery') {
										$autorun = getAdminLink('admin-tabs/users.php') . '?page=admin';
									}
								}
							}
							require_once(PLUGIN_SERVERPATH . 'clone.php');
							?>
							<p id="warning" class="warningbox cloneLink" style="display:none;">
								<?php printf(gettext('Your browser is blocking popups from %1$s so automatic setup of clones installs did not happen. Click the links below to setup your clone installations.'), FULLWEBPATH); ?>
							</p>
							<script>
								function launchClone(url) {
									var cloneWindow = window.open(url, "", "");
									if (!cloneWindow || cloneWindow.closed || typeof cloneWindow.closed == 'undefined') {
										$('.cloneLink').show();
										imageErr = true;	//	stop autorun
									}
								}
							</script>
							<?php
							$noclones = true;
							if (class_exists('npgClone')) {
								foreach (npgClone::clones() as $clone => $data) {
									$noclones = false;
									$url = $data['url'] . CORE_FOLDER . '/setup/index.php?autorun';
									?>
									<p class="cloneLink" style="display:none;margin-left: 2em;">
										<?php echo sprintf(gettext('Setup <a href="%1$s" target="_blank">%2$s</a>'), $url, $clone);
										?>
									</p>
									<script type="text/javascript">launchClone('<?php echo $url; ?>');</script>
									<?php
								}
								?>
								<p id="spacer" style="display:none; margin-bottom: 2em;"></p>
								<script type="text/javascript">
									if ($('#warning').is(":visible")) {
										$('#spacer').show();
									}
								</script>
								<?php
							}
							//	if the install is a new version, the NETPHOTOGRAPHICS_VERSION define will not be updated
							//	by the debug plugin, so we fecth the actual option to be sure to be current.
							if (!$test_release = getOption('markRelease_state')) {
								$test_release = '';
							}
							if (strpos($test_release, '_UNPROTECT') !== FALSE) {
								$query = '';
							} else {
								$query = '?action=protect_setup&XSRFToken=' . getXSRFToken('protect_setup');
							}
							?>
							<div id ="golink" class="delayshow"<?php echo (CURL_ENABLED) ? '' : ' style="display:none;"'; ?>>
								<?php
								if ($query && !$noclones) {
									?>
									<p class="warning">
										<?php printf(gettext('You may <a href="%1$s">administer your gallery</a> once all clones have completed setup.'), getAdminLink('admin.php') . $query); ?>
									</p>
									<?php
								} else {
									?>
									<p>
										<?php printf(gettext('You may now <a href="%1$s">administer your gallery</a>.'), getAdminLink('admin.php') . $query); ?>
									</p>
									<?php
								}
								?>
							</div>
							<?php
							switch ($autorun) {
								case false:
									break;
								case 'gallery':
								case 'admin':
									$autorun = getAdminLink('admin.php') . $query;
									break;
								default:
									break;
							}
							?>
							<input type="hidden" id="setupErrors" value="<?php echo (int) $updateErrors; ?>" />
							<script type="text/javascript">
								function launchAdmin() {
									window.location = '<?php echo getAdminLink('admin.php') . $query; ?>';
								}
								window.onload = function () {
									var errors = $('#setupErrors').val();
									$.ajax({
										type: 'POST',
										cache: false,
										data: 'errors=' + errors,
										url: '<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/setup/setupComplete.php'
									});
									$('.delayshow').show();
		<?php
		if ($autorun && ($noclones || !$query)) { //	don't autorun and protect the scripts if there are clones
			?>
										if (!imageErr) {
											$('#golink').hide();
											launchAdmin();
										}
			<?php
		}
		?>
								}
							</script>
							<?php
						} else if (db_connect($_conf_vars, false)) {
							$task = '';
							if (setupUserAuthorized() || $blindInstall) {
								if (!empty($dbmsg)) {
									?>
									<h2><?php echo $dbmsg; ?></h2>
									<?php
								}
								$task = "update" . $debugq . $autorunq;
								if (isset($copyaccess)) {
									$task .= '&copyhtaccess';
								}
							}
							$hideGoButton = '';

							if ($warn) {
								$icon = WARNING_SIGN_ORANGE;
							} else {
								$icon = CHECKMARK_GREEN;
							}

							$task .= $autorunq;
							if ($blindInstall) {
								@ob_end_clean();
								$blindInstall = false;
								$stop = !$autorun;
							} else {
								$stop = !setupUserAuthorized();
							}
							if ($stop) {
								?>
								<div class="error">
									<?php
									if (npg_loggedin()) {
										echo gettext("You need <em>USER ADMIN</em> rights to run setup.");
									} else {
										echo gettext('You must be logged in to run setup.');
									}
									?>
								</div>
								<?php
								$_authority->printLoginForm('', false);
							} else {
								if (!empty($task) && substr($task, 0, 1) != '&') {
									$task = '&' . $task;
								}
								$task = html_encode($task);
								?>
								<form id="setup" action="<?php echo WEBPATH . '/' . CORE_FOLDER, '/setup/index.php?checked' . $task . $mod; ?>" method="post"<?php echo $hideGoButton; ?> >
									<input type="hidden" name="setUTF8URI" id="setUTF8URI" value="internal" />
									<input type="hidden" name="xsrfToken" value="<?php echo setupXSRFToken(); ?>" />
									<?php
									if ($autorun) {
										?>
										<input type="hidden" id="autorun" name="autorun" value="<?php echo html_encode($autorun); ?>" />
										<?php
									}
									?>
									<ul>
										<li class="pass"><?php applyButton(array('buttonText' => $icon . ' ' . gettext("Go"))); ?></li>
									</ul>
									<br class="clearall" />
									<br />
								</form>
								<?php
							}
							if ($autorun) {
								?>
								<script type="text/javascript">
									$('#submitbutton').hide();
									$('#setup').submit();
								</script>
								<?php
							}
						} else {
							?>
							<div class="error">
								<h3><?php echo gettext("database did not connect"); ?></h3>
								<p>
									<?php echo gettext("If you have not created the database yet, now would be a good time."); ?>
								</p>
							</div>
							<?php
						}
					} else {
						// The config file hasn't been created yet. Show the steps.
						?>
						<div class="error">
							<?php echo sprintf(gettext('The %1$s file does not exist.'), CONFIGFILE); ?>
						</div>
						<?php
					}

					if ($blindInstall) {
						@ob_end_clean();
					}
					?>
					<br class="clearall" />
			</div><!-- content -->
			<?php
			printSetupFooter($setup_checked);
			?>
		</div><!-- main -->
	</body>
</html>
<?php
$setupMutex->unlock();
exit();
?>