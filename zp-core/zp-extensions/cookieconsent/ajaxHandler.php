<?php

/**
 * used to capture ajax requests from front-end pages. For instance, to log cookie concents
 *
 */
define('OFFSET_PATH', 1);
require_once(dirname(dirname(dirname(__FILE__))) . "/admin-globals.php");

if (isset($_POST['ajaxRequest'])) {
	switch ($_POST['ajaxRequest']) {
		case 'cookieconsent':
			npgFilters::apply('policy_ack', $_POST['status'] == 'dismiss', 'CookieConsent', NULL, $_POST['status']);
			break;
	}
}