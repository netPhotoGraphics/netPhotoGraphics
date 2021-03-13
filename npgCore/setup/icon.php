<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once (__DIR__ . '/setup-functions.php');
require_once(dirname(__DIR__) . '/functions-basic.php');
require_once(dirname(__DIR__) . '/initialize-basic.php');

$icon = (int) $_GET['icon'];
switch ($icon) {
	case 0:
		$which = gettext('Success');
		break;
	case 1:
		$which = gettext('Failure');
		break;
	case 2:
		$which = gettext('Deprecated');
		break;
}
sendImage($icon, $which);
exit();
