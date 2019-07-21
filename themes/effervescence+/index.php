<?php

// force UTF-8 Ø
if (getOption('gallery_index')) {
	if ($zenpage = class_exists('CMS')) {
		$imagereduction = 1 / 2;
	} else {
		$imagereduction = 1;
	}
	require('indexpage.php');
} else {
	require('gallery.php');
}
?>