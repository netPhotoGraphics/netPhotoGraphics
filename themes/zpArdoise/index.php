<?php

// force UTF-8 ø
if (!defined('WEBPATH'))
	die();

if ($_zenpage_enabled) { // check if Zenpage is enabled or not
	if (checkForPage(getOption('zenpage_homepage'))) { // switch to a home page
		$isHomePage = true;
		include ('pages.php');
	} else {
		include ('gallery.php');
	}
} else {
	include ('gallery.php');
}
?>