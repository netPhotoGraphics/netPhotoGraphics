<?php
/**
 * elFInder connector for  tinyMCE file handler instance
 *
 * @package plugins/elFinder
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/admin-functions.php');
npg_session_start();
admin_securityChecks(ALBUM_RIGHTS | ZENPAGE_PAGES_RIGHTS | ZENPAGE_PAGES_RIGHTS, currentRelativeURL());
XSRFdefender('elFinder');
$locale = substr(getOption('locale'), 0, 2);
if (empty($locale))
	$locale = 'en';
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>elFinder 2.0</title>

		<!-- jQuery and jQuery UI (REQUIRED) -->
		<?php
		load_jQuery_CSS();
		load_jQuery_scripts('admin');
		scriptLoader(CORE_SERVERPATH . PLUGIN_FOLDER . '/elFinder/css/elfinder.min.css');
		scriptLoader(CORE_SERVERPATH . PLUGIN_FOLDER . '/elFinder/css/theme.css');
		scriptLoader(CORE_SERVERPATH . PLUGIN_FOLDER . '/elFinder/js/elfinder.min.js');
		if ($locale != 'en') {
			?>
			<!-- elFinder translation (OPTIONAL) -->
			<?php
			scriptLoader(CORE_SERVERPATH . PLUGIN_FOLDER . '/elFinder/js/i18n/elfinder' . $locale . '.js');
		}
		?>

		<!-- elFinder initialization (REQUIRED) -->
		<script type="text/javascript" charset="utf-8">
			var FileBrowserDialogue = {
			init: function(file, fm) {
			// Here goes your code for setting your custom things onLoad.
			},
							mySubmit: function(file, fm) {
							// pass selected file data to TinyMCE
							var url, info;
							// URL normalization
							url = fm.convAbsUrl(file.url);
							info = file.name + ' (' + fm.formatSize(file.size) + ')';
							var windowManager = top != undefined && top.tinymceWindowManager != undefined ? top.tinymceWindowManager : '';
							if (windowManager != '') {
							if (top.tinymceCallBackURL != undefined){
							top.tinymceCallBackURL = url;
							top.tinymceCallBackInfo = info;
							}
							windowManager.close();
							}
							// close popup window
							top.tinymce.activeEditor.windowManager.close();
							}
			}

			$().ready(function() {
			var elf = $('#elfinder').elfinder({
			commands : [
							'open', 'reload', 'home', 'up', 'back', 'forward', 'getfile', 'quicklook',
<?php
if (npg_loggedin(FILES_RIGHTS)) {
	?>
				'download', 'rm', 'duplicate', 'rename', 'mkdir', 'mkfile', 'upload', 'copy',
								'cut', 'paste', 'edit', 'extract', 'archive', 'search',
								'resize',
	<?php
}
?>
			'info', 'view', 'help',
							'sort'
			],
							lang: '<?php echo $locale; ?>', // language (OPTIONAL)
							customData: {
							'XSRFToken':'<?php echo getXSRFToken('elFinder'); ?>',
											'user_auth':'<?php echo getNPGCookie('user_auth'); ?>',
											'origin':'tinyMCE',
											'type':'<?php echo sanitize(@$_GET['type']); ?>'
							},
							url : '<?php echo WEBPATH . '/' . CORE_FOLDER . '/' . PLUGIN_FOLDER; ?>/elFinder/php/connector_npg.php', // connector URL (REQUIRED)
							getFileCallback: function(file, fm) { // editor callback
							FileBrowserDialogue.mySubmit(file, fm); // pass selected file path to TinyMCE
							}
			}).elfinder('instance');
			});
		</script>
	</head>
	<body>

		<!-- Element where elFinder will be created (REQUIRED) -->
		<div id="elfinder"></div>

	</body>
</html>
