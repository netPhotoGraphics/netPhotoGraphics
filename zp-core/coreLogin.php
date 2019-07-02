<?php
/**
 * script for netPhotoGraphics logon button action.
 *
 * @Copyright 2017 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 */
// force UTF-8 Ø

define('OFFSET_PATH', 1);

require_once(dirname(__FILE__) . '/admin-globals.php');
npgFilters::register('alt_login_handler', 'removeAltHandlers', 0);

printAdminHeader('login');
echo "\n</head>";
?>
<body style="background-image: none">
	<?php $_authority->printLoginForm($_GET['redirect']); ?>
</body>
<?php
echo "\n</html>";
exit();

function removeAltHandlers($list) {
	return array();
}
?>
