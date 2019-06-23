<?php

//	redirect to the admin core
if (array_key_exists('REQUEST_URI', $_SERVER)) {
	$uri = str_replace('\\', '/', $_SERVER['REQUEST_URI']);
	preg_match('|^(http[s]*\://[a-zA-Z0-9\-\.]+/?)*(.*)$|xis', $uri, $matches);
	$uri = $matches[2];
	if (!empty($matches[1])) {
		$uri = '/' . $uri;
	}
} else {
	$uri = str_replace('\\', '/', @$_SERVER['SCRIPT_NAME']);
}
if (preg_match('~(.*?)/(CORE_PATH|USER_PLUGIN_PATH)(.*?)\?~i', $uri . '?', $matches)) {
	unset($uri);
	$base = '/' . strtr($matches[2] . $matches[3], array('CORE_PATH/PLUGIN_PATH' => 'CORE_FOLDER/PLUGIN_FOLDER', 'CORE_PATH' => 'CORE_FOLDER', 'USER_PLUGIN_PATH' => 'USER_PLUGIN_FOLDER'));
	if (preg_match('~\.php$~i', $base)) {
		trigger_error('Malformed admin link: ' . $base, E_USER_DEPRECATED);
	} else {
		$base = preg_replace('~RW_SUFFIX$~i', '', $base) . '.php';
	}
	if (file_exists(dirname(__FILE__) . $base)) {
		//	mock up things as if the the uri went directly to the script
		$_SERVER['SCRIPT_NAME'] = $matches[1] . $base;
		$_SERVER['SCRIPT_FILENAME'] = dirname($_SERVER['SCRIPT_FILENAME']) . $base;
		unset($matches);
		chdir(dirname($_SERVER['SCRIPT_FILENAME']));
		include($_SERVER['SCRIPT_FILENAME']);
		exit();
	}
	unset($matches);
}

define('OFFSET_PATH', 0);
if (!$_themeScript = @$_SERVER['SCRIPT_FILENAME']) {
	$_themeScript = __FILE__;
}
$_contents = @file_get_contents(dirname($_themeScript) . '/DATA_FOLDER/CONFIGFILE');

if ($_contents) {
	if (strpos($_contents, '<?php') !== false)
		$_contents = '?>' . $_contents;
	@eval($_contents);
	if (isset($conf)) {
		$_conf_vars = $conf;
	} else {
		$_conf_vars = $_zp_conf_vars;
	}
	if (@$_conf_vars['site_upgrade_state'] == 'closed') {
		if (isset($_conf_vars['special_pages']['page']['rewrite'])) {
			$page = $_conf_vars['special_pages']['page']['rewrite'];
		} else {
			$page = 'page';
		}
		if (!preg_match('~' . preg_quote($page) . '/setup_set-mod_rewrite\?z=setup$~', $_SERVER['REQUEST_URI'])) {
			if (file_exists(dirname($_themeScript) . '/plugins/site_upgrade/closed.php')) {
				if (isset($_SERVER['HTTPS'])) {
					$protocol = 'https';
				} else {
					$protocol = 'http';
				}
				header('location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . str_replace('index.php', '', $_SERVER['SCRIPT_NAME']) . 'plugins/site_upgrade/closed.php');
			}
			exit();
		}
	}
}
unset($_contents);
include (dirname(__FILE__) . '/CORE_FOLDER/index.php');
