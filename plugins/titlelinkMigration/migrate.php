<?php

/*
 * This plugin is a migration tool append the <em>mod_rewrite_suffix</em> to
 * CMS titlelilnks
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/utf8mb4Migration
 *
 * @Copyright 2018 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 */

// force UTF-8 Ø

define('OFFSET_PATH', 4);
require_once(file_get_contents(dirname(dirname($_SERVER['SCRIPT_FILENAME'])) . '/core-locator.npg') . "admin-globals.php");

admin_securityChecks(ADMIN_RIGHTS, $return = currentRelativeURL());

XSRFdefender('titlelinkMigration');

$count = migrateTitleLinks('', RW_SUFFIX);
$msg = ngettext(sprintf(gettext('%1$s titlelink suffix migrated to "%2$s".'), $count, RW_SUFFIX), sprintf(gettext('%1$s titlelink suffixes migrated to "%2$s".'), $count, RW_SUFFIX), $count);

header('Location: ' . getAdminLink('admin.php') . '?action=external&msg=' . $msg);
exit();
