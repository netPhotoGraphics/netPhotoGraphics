<?php

$_Script_processing_timer['start'] = microtime(true);
$closed = file_exists('SITE_ROOT/extract.php');
if ($closed) {
	if (isset($_GET['npgUpdate'])) {
		require( 'SITE_ROOT/extract.php');
		exit();
	}
} else {
	//	redirect to the admin core?
	if (array_key_exists('REQUEST_URI', $_SERVER)) {
		$uri = str_replace('\\', '/', $_SERVER['REQUEST_URI']);
		preg_match('|^(http[s]*\://[a-zA-Z0-9\-\.]+/?)*(.*)$|xis', $uri, $matches);
		$uri = $matches[2];
		if (!empty($matches[1])) {
			$uri = '/' . $uri;
		}
	} else {
		$uri = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
	}
	$parts = explode('?', $uri);
	$uri = $parts[0];
	unset($parts);
	if (preg_match('~(.*?)/(admin|CORE_PATH|USER_PLUGIN_PATH)/?(.*?)\?~i', $uri . '?', $matches)) {
		$base = '/' . $matches[2] . ($matches[3] ? '/' . $matches[3] : '');
		foreach (array('/CORE_PATH/PLUGIN_PATH/' => '/CORE_FOLDER/PLUGIN_FOLDER/', '/USER_PLUGIN_PATH/' => '/USER_PLUGIN_FOLDER/', '/CORE_PATH/' => '/CORE_FOLDER/', '/admin' => '/CORE_FOLDER/admin') as $from => $to) {
			$base = preg_replace('~' . $from . '~', $to, $base, 1, $count);
			if ($count) {
				break;
			}
		}
		if (preg_match('~\.php$~i', $base)) {
			if (file_exists(__DIR__ . $base)) {
				trigger_error('Malformed admin link: ' . $base, E_USER_DEPRECATED);
			}
		} else {
			$base = preg_replace('~RW_SUFFIX$~i', '', $base) . '.php';
		}
		if (file_exists(__DIR__ . $base)) {
			//	mock up things as if the the uri went directly to the script
			$_SERVER['SCRIPT_NAME'] = $matches[1] . $base;
			$_SERVER['SCRIPT_FILENAME'] = dirname($_SERVER['SCRIPT_FILENAME']) . $base;
			unset($uri);
			unset($matches);
			unset($base);
			unset($closed);
			chdir(dirname($_SERVER['SCRIPT_FILENAME']));
			include($_SERVER['SCRIPT_FILENAME']);
			exit();
		}
	}
	unset($matches);
	unset($uri);
	unset($base);
}

define('OFFSET_PATH', 0);

if (file_exists(__DIR__ . '/DATA_FOLDER/CONFIGFILE')) {
	require(__DIR__ . '/DATA_FOLDER/CONFIGFILE');
	if ($closed || isset($conf['site_upgrade_state']) && $conf['site_upgrade_state'] == 'closed') {
		if (file_exists('SITE_ROOT/USER_PLUGIN_FOLDER/site_upgrade/closed.php')) {
			include('SITE_ROOT/USER_PLUGIN_FOLDER/site_upgrade/closed.php');
		}
		exit();
	}
	unset($conf);
}
unset($_contents);
unset($closed);
include (__DIR__ . '/CORE_FOLDER/index.php');
