<?php
/**
 * This is the "files" upload tab
 *
 * @package plugins/elFinder
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/admin-globals.php');
admin_securityChecks(FILES_RIGHTS | UPLOAD_RIGHTS, currentRelativeURL());
setNPGCookie('uploadtype', 'elFinder');
$locale = substr(getOption('locale'), 0, 2);
if (empty($locale))
	$locale = 'en';
printAdminHeader('upload', 'files');

if (isset($_REQUEST['themeEdit'])) {
	$theme = sanitize($_REQUEST['themeEdit']);
	$_admin_tab = 'themes';
	$title = gettext('Theme Manager');
} else {
	$theme = false;
	$title = gettext('File Manager');
}
scriptLoader(CORE_SERVERPATH . PLUGIN_FOLDER . '/elFinder/css/elfinder.min.css');
scriptLoader(CORE_SERVERPATH . PLUGIN_FOLDER . '/elFinder/css/theme.css');
scriptLoader(CORE_SERVERPATH . PLUGIN_FOLDER . '/elFinder/js/elfinder.min.js');
if ($locale != 'en') {
	?>
	<!-- elFinder translation (OPTIONAL) -->
	<?php
	scriptLoader(CORE_SERVERPATH . PLUGIN_FOLDER . '/elFinder/js/i18n/elfinder' . $locale . '.js');
}

echo "\n</head>";
?>

<body>

	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<?php ?>
			<div id="container">
				<?php
				$subtab = getCurrentTab();
				npgFilters::apply('admin_note', 'upload', $subtab);
				?>
				<h1><?php echo $title; ?></h1>
				<div class="tabbox">
					<script type="text/javascript">
						$().ready(function () {
							var elf = $('#elfinder').elfinder({
								lang: '<?php echo $locale; ?>', // language (OPTIONAL)
								customData: {
									'XSRFToken': '<?php echo getXSRFToken('elFinder'); ?>',
									'user_auth': '<?php echo getNPGCookie('user_auth'); ?>',
<?php
if ($theme) {
	if (npg_loggedin(THEMES_RIGHTS) && is_dir(SERVERPATH . '/' . THEMEFOLDER . '/' . $theme)) {
		?>
											'themeEdit': '<?php echo $theme; ?>',
		<?php
	}
} else {
	$theme = false;
}
?>
									'origin': 'upload'
								},
								url: '<?php echo WEBPATH . '/' . CORE_FOLDER . '/' . PLUGIN_FOLDER; ?>/elFinder/php/connector_npg.php'  		// connector URL (REQUIRED)
							}).elfinder('instance');
						});
					</script>
					<?php
					if (npg_loggedin(ALBUM_RIGHTS) && !$theme) {
						?>
						<p class="notebox">
							<?php echo gettext('<strong>Note:</strong> Accessing the Albums folder with this utility is equivalent to using FTP to access it. <em>Copy</em> and <em>rename</em> do not carry the database data with the change.'); ?>
						</p>
						<?php
					}
					?>
					<!-- Element where elFinder will be created (REQUIRED) -->
					<div id="elfinder"></div>
				</div>
			</div>
		</div>
		<?php printAdminFooter(); ?>
	</div>
</body>
</html>
<?php ?>