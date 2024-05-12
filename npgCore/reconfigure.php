<?php
/**
 * handles reconfiguration when the install signature has changed
 *
 * @author Stephen Billard (sbillard)
 *
 * @package core
 */
if (!defined('OFFSET_PATH')) {
	die();
}
// because we are loading the script from within a function!
global $subtabs, $_admin_menu, $_admin_tab, $_invisible_execute, $_gallery, $_conf_vars;

require_once(__DIR__ . '/admin-globals.php');

/**
 *
 * Executes the configuration change code
 */
function reconfigureAction($mandatory) {
	global $_conf_vars, $_gallery, $_invisible_execute;
	// do this before we might contaminate state
	$log = true;
	switch ($mandatory) {
		case 11:
			$reason = gettext('no configuration file');
			$log = false; // can't log if we don't know where to put the log
			break;
		case 12:
			$reason = sprintf(gettext('no %1$s PHP support'), $_conf_vars['db_software']);
			break;
		case 13:
			$reason = gettext('Database connection failure.');
			break;
		default:
			$reason = gettext('install signature option not set');
			$log = false;
			break;
	}
	list($diff, $needs) = checkSignature($mandatory);
	$diffkeys = array_keys($diff);
	if ($mandatory) {
		if (isset($_GET['rss']) || isset($_GET['external'])) {
			if (file_exists(SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/site_upgrade/closed.php')) {
				include(SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/site_upgrade/closed.php');
			}
			exit(); //	can't really run setup from an RSS feed.
		}

		if ($log) {
			debugLogBacktrace(sprintf(gettext('Setup required: %1$s.'), $reason));
		}

		if (empty($needs)) {
			$dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
			$p = strpos($dir, CORE_FOLDER);
			if ($p !== false) {
				$dir = substr($dir, 0, $p);
			}
			if (OFFSET_PATH) {
				$where = 'admin';
			} else {
				$where = 'gallery';
			}
			$dir = rtrim($dir, '/');
			if (isset($_SERVER['https'])) {
				$protocol = 'https';
			} else {
				$protocol = 'http';
			}
			$location = $protocol . '://' . $_SERVER['HTTP_HOST'] . $dir . "/" . CORE_FOLDER . "/setup/index.php?autorun=$where";
		} else {
			$location = '';
		}
		$_invisible_execute = 1;

		if (!defined('FULLWEBPATH')) {
			$protocol = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") ? 'http' : 'https';
			define('FULLHOSTPATH', $protocol . "://" . $_SERVER['HTTP_HOST']);
			define('FULLWEBPATH', FULLHOSTPATH . WEBPATH);
		}

		if ($notsent = !headers_sent()) {
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Content-Type: text/html; charset=UTF-8');
			header("Cache-Control: no-cache, no-store, private;, must-revalidate"); // HTTP 1.1.
			header("Pragma: no-cache"); // HTTP 1.0.
			header("Expires: 0"); // Proxies.
			header("HTTP/1.0 503 Service Unavailable");
			header("Status: 503 Service Unavailable");
			header("Retry-After: 300");
			?>
			<!DOCTYPE html>
			<html xmlns="http://www.w3.org/1999/xhtml">
				<head>
					<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
					<link rel="stylesheet" href = "<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/admin.css" type="text/css" />
					<?php reconfigureCS(); ?>
				</head>
				<body>
					<?php
					if ($_gallery) {
						printLogoAndLinks();
					} else {
						?>
						<div id="admin_head">
							<span id="administration">
								<img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/admin-logo.png" id="site logo" alt="site_logo" style="height:78px; width:auto;" />
							</span>
						</div>
						<?php
					}
					?>
					<div id="main">
						<?php
						if ($_gallery) {
							printTabs();
						}
					} else {
						reconfigureCS();
					}
					?>

					<div id="content">
						<br clear="all">
							<h1><?php echo gettext('Site unavailable'); ?></h1>
							<div class="tabbox">
								<div class="reconfigbox">
									<?php echo $reason; ?>
								</div>
								<?php
								if ($location) {
									echo sprintf(gettext('Run <a href=%1$s>setup</a>'), $location);
								}
								?>
							</div>
					</div>
					<?php
					if ($notsent) {
						?>
					</div>
				</body>
			</html>
			<?php
		}
		db_close();
		exit();
	} else {
		if (!empty($diff)) {
			if (class_exists('npgFilters') && npg_loggedin(ADMIN_RIGHTS)) {
				//	no point in telling someone who can't do anything about it
				npgFilters::register('admin_note', 'signatureChange', 9999);
				npgFilters::register('admin_head', 'reconfigureCS');
			}
		}
	}
}

/**
 *
 * Checks details of configuration change
 */
function checkSignature($mandatory) {
	global $_configMutex, $_DB_connection, $_reconfigureMutex;
	$old = NULL;
	if (function_exists('query_full_array') && $_DB_connection) {
		$old = getSerializedArray(getOption('netphotographics_install'));
		unset($old['SERVER_SOFTWARE']);
		unset($old['DATABASE']);
		$new = installSignature();
	}
	if (!is_array($old)) {
		$new = array();
		switch ($mandatory) {
			case 11:
				$reason = gettext('The configuration file is missing.');
				break;
			case 12:
				$reason = gettext('The <code>db_software</code> specification is not valid.');
				break;
			case 13:
				$reason = gettext('The database connection failed.');
				break;
			default:
				$reason = '';
				break;
		}
		$old = array('CONFIGURATION' => $reason);

		if (!$mandatory)
			$mandatory = 6;
	}

	$diff = array();
	$keys = array_unique(array_merge(array_keys($new), array_keys($old)));
	foreach ($keys as $key) {
		if (!array_key_exists($key, $new) || !array_key_exists($key, $old) || $old[$key] != $new[$key]) {
			$diff[$key] = array('old' => isset($old[$key]) ? $old[$key] : NULL, 'new' => isset($new[$key]) ? $new[$key] : NULL);
		}
	}

	$package = file_get_contents(__DIR__ . '/netPhotoGraphics.package');
	preg_match_all('|%core%/setup/(.*)|', $package, $matches);
	$needs = array();
	$restore = $found = false;
	foreach ($matches[1] as $need) {
		$needs[] = rtrim(trim($need), ":*");
	}
// serialize the following
	$_configMutex->lock();
	if (file_exists(__DIR__ . '/setup/')) {
		chdir(__DIR__ . '/setup/');
//just in case files were uploaded over a protected setup folder
		$have = safe_glob('*.php');
		foreach ($have as $key => $f) {
			$f = str_replace('.php', '.xxx', $f);
			if (file_exists($f)) {
				chmod($f, 0777);
				unlink($f);
			}
		}
		$restore = safe_glob('*.xxx');

		if (!empty($restore) && $mandatory > 1 && defined('ADMIN_RIGHTS') && npg_loggedin(ADMIN_RIGHTS)) {
			restoreSetupScrpts($mandatory);
		}
		$found = safe_glob('*.*');
		$needs = array_diff($needs, $found);
	}
	$_configMutex->unlock();
	return array($diff, $needs, $restore, $found);
}

/**
 *
 * Notificatnion handler for configuration change
 * @param string $tab
 * @param string $subtab
 * @return string
 */
function signatureChange($tab = NULL, $subtab = NULL) {
	list($diff, $needs) = checkSignature(0);
	reconfigurePage($diff, $needs, 0);
	return $tab;
}

/**
 *
 * CSS for the configuration change notification
 */
function reconfigureCS() {
	?>
	<style type="text/css">
		.reconfigbox {
			text-align: left;
			padding: 10px;
			color: black;
			background-color: #FFEFB7;
			border-width: 1px 1px 2px 1px;
			border-color: #FFDEB5;
			border-style: solid;
			margin-bottom: 10px;
			font-size: 100%;
			box-sizing: content-box !important;
			webkit-box-sizing: content-box !important;
		}

		.reconfigbox h1,.notebox strong {
			color: #663300;
			font-size: 120%;
			font-weight: bold;
			margin-bottom: 1em;
		}
		.reconfigbox a {
			color: blue;
		}
		.reconfigbox p {
			margin-top: 20px;
		}
		#errors ul {
			margin-left: 1em;
			list-style-type: square;
		}
		#files ul {
			margin-left: 1em;
			list-style-type: circle;
		}
	</style>
	<?php
}

/**
 *
 * HTML for the configuration change notification
 */
function reconfigurePage($diff, $needs, $mandatory) {
	if (OFFSET_PATH) {
		$where = 'admin';
	} else {
		$where = 'gallery';
	}
	if (function_exists('getXSRFToken') && defined('npg_SID')) {
		$token = getXSRFToken('setup');
		if (isset($_GET['dismiss']) && isset($_GET['xsrfToken']) && $_GET['xsrfToken'] == $token) {
			setOption('netphotographics_install', serialize(installSignature()));
			return;
		}
		$where .= '&amp;xsrfToken=' . $token;
	} else {
		$where .= '&amp;notoken';
	}
//	leave this as a direct link incase the admin mod_rewrite mechanism has not yet been established
	$l1 = '<a href="' . WEBPATH . '/' . CORE_FOLDER . '/setup.php' . '?autorun=' . $where . '">';
	$l2 = '</a>';
	?>
	<div class="reconfigbox">
		<h1>
			<?php echo gettext('A change has been detected in the installation.'); ?>
		</h1>
		<div id="errors">
			<ul>
				<?php
				foreach ($diff as $thing => $rslt) {
					switch ($thing) {
						case 'NETPHOTOGRAPHICS':
							echo '<li>' . sprintf(gettext('Version %1$s has been copied over %2$s.'), $rslt['new'], $rslt['old']) . '</li>';
							break;
						case 'FOLDER':
							echo '<li>' . sprintf(gettext('The installation has moved from %1$s to %2$s.'), $rslt['old'], $rslt['new']) . '</li>';
							break;
						case 'CONFIGURATION':
							echo '<li>' . gettext('The installation configuration is damaged.') . ' ' . $rslt['old'] . '</li>';
							$l1 = '';
							break;
						case 'REQUESTS':
							if (!empty($rslt)) {
								echo '<li><div id="files">';
								echo gettext('setup has been requested by:');
								echo '<ul>';
								foreach ($rslt['old'] as $request) {
									echo '<li>' . $request . '</li>';
								}
								echo '</ul></div></li>';
							}
							break;
						default:
							$sz = filesize(CORE_SERVERPATH . $thing);
							if (getSuffix($thing) == 'php') {
								echo '<li>' . sprintf(gettext('The script <code>%1$s</code> has changed.'), $thing) . '</li>';
							} else {
								echo '<li>' . sprintf(gettext('The <code>%1$s</code> has changed.'), $thing) . '</li>';
							}
							break;
					}
				}
				?>
			</ul>
		</div>
		<p>
			<?php
			if ($mandatory) {
				printf(gettext('The change detected is critical. %1$s<em>setup</em>%2$s <strong>must</strong> be run for the site to function.'), $l1, $l2);
			} else {
				printf(gettext('The change detected may not be critical but you should run %1$ssetup%2$s at your earliest convenience.'), $l1, $l2);
				$request = mb_parse_url(getRequestURI());
				if (isset($request['query'])) {
					$query = parse_query($request['query']);
				} else {
					$query = array();
				}
				$query['dismiss'] = 'config_warning';
				$query['xsrfToken'] = $token;
				?>
				<p>
					<?php npgButton('button', gettext('dismiss'), array('buttonLink' => '?' . html_encode(http_build_query($query)), 'buttonTitle' => gettext('Ignore this configuration change.'))); ?>
				</p>
				<br class="clearall" />
				<?php
			}
			?>
		</p>
	</div>
	<?php
}

/**
 * control when and how setup scripts are turned back into PHP files
 * @param int reason
 * 						 1	No prior install signature
 * 						 2	restore setup files button
 * 						 4	Clone request
 * 						 5	Setup run with proper XSRF token
 * 						 6	checkSignature and no prior signature
 * 						11	No config file
 * 						12	No database specified
 * 						13	No DB connection
 */
function restoreSetupScrpts($reason) {
//log setup file restore no matter what!
	require_once(PLUGIN_SERVERPATH . 'security-logger.php');

	$allowed = defined('ADMIN_RIGHTS') && npg_loggedin(ADMIN_RIGHTS) && npgFunctions::hasPrimaryScripts();

	switch ($reason) {
		case 6:
		case 13:
			if (isset($_conf_vars['db_client']) && !empty($_conf_vars['db_client'])) {
				$allowed = false; // If this was set, there was once a good connection to the DB
			}
		default:
			$addl = sprintf(gettext('to run setup [%s]'), $reason);
			break;
		case 2:
			$addl = gettext('by Admin request');
			break;
		case 4:
			$addl = gettext('by cloning');
			break;
	}
	security_logger::log_setup($allowed, 'restore', $addl);
	if ($allowed) {
		if (!defined('FILE_MOD')) {
			define('FILE_MOD', 0666);
		}
		chdir(__DIR__ . '/setup/');
		$found = safe_glob('*.xxx');
		foreach ($found as $script) {
			chmod($script, 0777);
			if (rename($script, stripSuffix($script) . '.php')) {
				chmod(stripSuffix($script) . '.php', FILE_MOD);
			} else {
				chmod($script, FILE_MOD);
			}
		}
	}
}
?>