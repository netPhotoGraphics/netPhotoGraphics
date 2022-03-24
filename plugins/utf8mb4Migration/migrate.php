<?php

/*
 * This plugin is a migration tool to move TEXT and LONGTEXT database fields to
 * utf8mb4 encoding and collation
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/utf8mb4Migration
 *
 * @Copyright 2017 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 */

// force UTF-8 Ø

define('OFFSET_PATH', 3);
require_once(file_get_contents(dirname(dirname($_SERVER['SCRIPT_FILENAME'])) . '/core-locator.npg') . "admin-globals.php");

admin_securityChecks(ADMIN_RIGHTS, $return = currentRelativeURL());

XSRFdefender('utf8mb4Migration');

setOption('UTF-8', 'utf8mb4');
require_once(CORE_SERVERPATH . 'setup/setup-functions.php');
require_once(CORE_SERVERPATH . 'setup/database.php'); //	this will do the actual migration of the fields

header('Location: ' . getAdminLink('admin.php') . '?action=external&msg=' . gettext('utf8mb4 migration completed.'));
exit();
