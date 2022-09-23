<?php

clearstatcache();
$closed = file_exists('ROOT_FOLDER/extract.php');
if (!$closed) {
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
if (isset($_SERVER['SCRIPT_FILENAME'])) {
	$_themeScript = $_SERVER['SCRIPT_FILENAME'];
} else {
	$_themeScript = __FILE__;
}
if (file_exists(dirname($_themeScript) . '/DATA_FOLDER/CONFIGFILE')) {
	$_contents = file_get_contents(dirname($_themeScript) . '/DATA_FOLDER/CONFIGFILE');
	if ($_contents) {
		if (strpos($_contents, '<?php') !== false) {
			$_contents = '?>' . $_contents;
		}
		try {
			eval($_contents);
			if (isset($conf)) {
				$_conf_vars = $conf;
			} else {
				$_conf_vars = $_zp_conf_vars; //	backward compatibility
			}
			if ($closed || isset($_conf_vars['site_upgrade_state']) && $_conf_vars['site_upgrade_state'] == 'closed') {
				if (file_exists(dirname($_themeScript) . '/plugins/site_upgrade/closed.php')) {
					include(dirname($_themeScript) . '/plugins/site_upgrade/closed.php');
				}
				exit();
			}
		} catch (exception $e) {

		}
	}
	unset($_contents);
}
unset($closed);
include (__DIR__ . '/CORE_FOLDER/index.php');
