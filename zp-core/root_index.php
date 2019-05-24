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
if (preg_match('~(.*?)/(' . CORE_PATH . '.*?)\?~i', $uri . '?', $matches)) {
	unset($uri);
	$base = '/' . strtr($matches[2], array('npg-core' => 'zp-core', 'extensions' => 'zp-extensions')) . '.php';
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
if (!$_zp_script = @$_SERVER['SCRIPT_FILENAME']) {
	$_zp_script = __FILE__;
}
$_contents = @file_get_contents(dirname($_zp_script) . '/' . DATA_FOLDER . '/zenphoto.cfg.php');

if ($_contents) {
	if (strpos($_contents, '<?php') !== false)
		$_contents = '?>' . $_contents;
	@eval($_contents);
	if (@$_zp_conf_vars['site_upgrade_state'] == 'closed') {
		if (isset($_zp_conf_vars['special_pages']['page']['rewrite'])) {
			$page = $_zp_conf_vars['special_pages']['page']['rewrite'];
		} else {
			$page = 'page';
		}
		if (!preg_match('~' . preg_quote($page) . '/setup_set-mod_rewrite\?z=setup$~', $_SERVER['REQUEST_URI'])) {
			if (file_exists(dirname($_zp_script) . '/plugins/site_upgrade/closed.php')) {
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
include (dirname(__FILE__) . '/' . CORE_FOLDER . '/index.php');
?>