<?php

/**
 * DO NOT MODIFY THIS FILE.
 * If you wish to change the appearance or behavior of
 * the site when closed you may edit the .htm and .xmp files
 */
if (isset($_SERVER['SCRIPT_FILENAME'])) {
	$_dir = dirname($_SERVER['SCRIPT_FILENAME']);
} else {
	$_dir = __DIR__;
}
if (file_exists(dirname($_dir) . '/DATA_FOLDER/CONFIGFILE') && !file_exists(dirname($_dir) . '/extract.php')) {
	$_contents = file_get_contents(dirname($_dir) . '/DATA_FOLDER/CONFIGFILE');
	if (strpos($_contents, '<?php') !== false)
		$_contents = '?>' . $_contents;
	eval($_contents);
	if (isset($conf['site_upgrade_state']) && $conf['site_upgrade_state'] == 'open') {
		// site is now open, redirect to index
		header("HTTP/1.0 303 See Other");
		header("Status: 303 See Other");
		header('Location: ' . $_dir . 'SITEINDEX');
		exit();
	}
}

$glob = array();
if (($dir = opendir($_dir)) !== false) {
	while (($file = readdir($dir)) !== false) {
		preg_match('~(.*)\-closed\.*~', $file, $matches);
		if (isset($matches[1]) && $matches[1]) {
			$glob[$matches[1]] = $file;
		}
	}
}

foreach ($glob as $key => $file) {
	if (isset($_GET[$key])) {
		$path = $_dir . '/' . $file;
		$xml = file_get_contents($path);
		$xml = preg_replace('~<atom:link href="(.*) rel=~', '<atom:link href="' . $_dir . 'SITEINDEX" rel=', $xml);
		$xml = preg_replace('~<pubDate>(.*)</pubDate>~', '<pubDate>' . date("r", time()) . '</pubDate>', $xml);
		header('Content-Type: application/xml');
		echo $xml;
		exit();
	}
}

header("HTTP/1.1 503 Service Unavailable");
header("Status: 503 Service Unavailable");
header('Pragma: no-cache');
header('Retry-After: 300');
header('Cache-Control: no-cache, must-revalidate, max-age=0');
$protocol = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") ? 'http' : 'https';

echo file_get_contents(__DIR__ . '/closed.htm');
exit();
?>