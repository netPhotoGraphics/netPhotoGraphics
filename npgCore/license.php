<?php
/**
 *
 * netPhotoGraphics license agreement
 *
 */
if (!defined('OFFSET_PATH')) {
	define('OFFSET_PATH', 1);
}
require_once(__DIR__ . '/admin-globals.php');

checkInstall(); /* incase someone has dropped tables and not run setup */

if (isset($_GET['licenseAccept'])) {
	if (isset($_SESSION['license_return']) && $_SESSION['license_return']) {
		$return_to = $_SESSION['license_return'];
		unset($_SESSION['license_return']);
	} else {
		$return_to = getAdminLink('admin.php');
	}
	setOption('license_accepted', NETPHOTOGRAPHICS_VERSION);
	header('Location: ' . $return_to);
	exit();
}

printAdminHeader('license');

echo "\n</head>";
?>

<body>

	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<h1><?php echo gettext('netPhotoGraphics License agreement'); ?></h1>
			<div id="container">
				<p class="notebox">
					<?php printf(gettext('This license is in English because the <em>Free Software Foundation</em> does not approve translations as officially valid. Unofficial translations are available <a href="%s">here</a>.'), 'https://www.gnu.org/licenses/old-licenses/gpl-2.0-translations.html'); ?>
				</p>
				<?php
				if (!getOption('license_accepted')) {
					$_SESSION['license_return'] = getRequestURI();
					?>
					<p>
						<?php npgButton('button', gettext('I agree to these terms and conditions'), array('buttonLink' => getAdminLink('license.php') . '?licenseAccept')); ?>
					</p>
					<br class="clearall" />
					<?php
				}
				?>
				<br class="clearall" />
				<div class="tabbox">
					<iframe src="<?php echo FULLWEBPATH . '/' . CORE_FOLDER; ?>/gpl-2.0-standalone.htm" width="100%" height="480" style="border: 0">
					</iframe>
				</div>
			</div>
		</div>

		<?php printAdminFooter(); ?>
	</div>
</body>
</html>
<?php
exit();
?>
