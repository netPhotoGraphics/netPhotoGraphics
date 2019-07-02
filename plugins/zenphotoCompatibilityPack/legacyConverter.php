<?php
/*
 * utility to convert legacy zenphoto themes/plugins to netPhotoGraphics syntax.
 *
 * @author Stephen Billard
 * @Copyright 2015 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 * @package plugins/zenphotoCompatibilityPack
 */
// force UTF-8 Ø

define('OFFSET_PATH', 3);
require_once(dirname(dirname(dirname(__FILE__))) . "/zp-core/admin-globals.php");
admin_securityChecks(THEMES_RIGHTS, currentRelativeURL());

/**
 *
 * enumerates the files in folder(s)
 * @param $folder
 */
function getResidentFiles($folder) {
	global $_resident_files;
	$localfiles = array();
	$localfolders = array();
	if (file_exists($folder)) {
		$dirs = scandir($folder);
		foreach ($dirs as $file) {
			if ($file{0} != '.') {
				$file = str_replace('\\', '/', $file);
				$key = $folder . '/' . $file;
				if (is_dir($folder . '/' . $file)) {
					$localfolders = array_merge($localfolders, getResidentFiles($folder . '/' . $file));
				} else {
					if (getSuffix($key) == 'php') {
						$localfiles[] = $key;
					}
				}
			}
		}
	}
	return array_merge($localfiles, $localfolders);
}

function checkIfProcessed($kind, $name) {
	$file = 'none';
	switch ($kind) {
		case 'plugin':
			$file = SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/' . $name . '.php';
			break;
		case'theme':
			$file = SERVERPATH . '/' . THEMEFOLDER . '/' . $name . '/theme_description.php';
			break;
	}
	if (file_exists($file)) {
		$body = file_get_contents($file);
		return (strpos($body, '/* LegacyConverter was here */') !== false);
	}
	return false;
}

if (isset($_GET['action'])) {
	$files = array();
	if (isset($_POST['themes'])) {
		foreach ($_POST['themes'] as $theme) {
			$themeFiles = getResidentFiles(SERVERPATH . '/' . THEMEFOLDER . '/' . $theme);
			$files = array_merge($files, $themeFiles);
		}
	}
	if (isset($_POST['plugins'])) {
		foreach ($_POST['plugins'] as $plugin) {
			$pluginFiles = getResidentFiles(SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/' . $plugin);
			$pluginFiles[] = SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/' . $plugin . '.php';
			$files = array_merge($files, $pluginFiles);
		}
	}
	$counter = 0;
	foreach ($files as $file) {
		$source = $body = file_get_contents($file);
		if (strpos($body, '/* LegacyConverter was here */') === false) {
			preg_match('~\<\?php(.*)\?>~ixUs', $body, $matches);
			if (isset($matches[0])) {
				$body = str_replace($matches[0], "<?php\n/* LegacyConverter was here */\n" . trim($matches[1], "\n") . "\n?>", $body);
			}
		}
		foreach ($legacyReplacements as $match => $replace) {
			$body = preg_replace('~' . $match . '~im', $replace, $body);
		}
		$body = preg_replace('~/\* TODO:replaced.*/\*(.*?)\*/.*\*/~', '/*$1*/', $body); //in case we came here twice

		if ($source != $body) {
			file_put_contents($file, $body);
			$counter++;
		}
	}
}

printAdminHeader('development', gettext('legacyConverter'));
echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
echo "\n" . '<div id="main">';
printTabs();
echo "\n" . '<div id="content">';
npgFilters::apply('admin_note', 'development', '');
echo "\n" . '<div id="container">';
?>
<h1><?php echo gettext('Convert legacy Zenphoto themes and plugins'); ?></h1>

<div class="tabbox">
	<?php
	if (isset($counter)) {
		?>
		<div class="messagebox fade-message">
			<h2><?php printf(ngettext('%s file updated', '%s files updated', $counter), $counter); ?></h2>
		</div>
		<?php
	}
	?>
	<div>
		<?php echo gettext('Note: you should review any the results of this conversion. Lood for the <code>/* TODO:.... */</code> in the scripts as these contain suggestions on further improvements.'); ?>
	</div>
	<?php
	$themesP = $themes = $plugins = $pluginsP = array();
	foreach ($_gallery->getThemes() as $theme => $data) {
		if (!protectedTheme($theme)) {
			$themes[] = $theme;
			if (checkIfProcessed('theme', $theme)) {
				$themesP[] = $theme;
			}
		}
	}
	$paths = getPluginFiles('*.php');
	foreach ($paths as $plugin => $path) {
		if (strpos($path, USER_PLUGIN_FOLDER) !== false) {
			$name = stripSuffix(basename($path));
			if (!distributedPlugin($name)) {
				$plugins[] = $name;
				if (checkIfProcessed('plugin', $name)) {
					$pluginsP[] = $name;
				}
			}
		}
	}
	?>
	<form class="dirtylistening" id="form_convert" action="?page=development&tab=legacyConverter&action=process" method="post" >
		<ul class="page-list">
			<?php
			XSRFToken('saveoptions');
			if (!empty($themes)) {
				?>
				<li>
					<ul>
						<?php
						echo gettext('Themes');
						foreach ($themes as $theme) {
							?>
							<li>
								<label>
									<input type="checkbox" name="themes[]" value="<?php echo html_encode($theme); ?>" >
									<?php
									echo html_encode($theme);
									if (in_array($theme, $themesP)) {
										?>
										<span style="color: orangered">
											<?php echo gettext(' (already processed)'); ?>
										</span>
										<?php
									}
									?>
								</label>
							</li>
							<?php
						}
						?>
					</ul>
				</li>
				<br />
				<?php
			}
			if (!empty($plugins) || !empty($pluginsP)) {
				?>
				<li>
					<ul>
						<?php
						echo gettext('Plugins');
						foreach ($plugins as $plugin) {
							?>
							<li>
								<label>
									<input type="checkbox" name="plugins[]" value="<?php echo html_encode($plugin); ?>" >
									<?php
									echo html_encode($plugin);
									if (in_array($plugin, $pluginsP)) {
										?>
										<span style="color: orangered">
											<?php echo gettext(' (already processed)'); ?>
										</span>
										<?php
									}
									?>
								</label>
							</li>
							<?php
						}
						?>
					</ul>
				</li>
				<?php
			}
			?>
		</ul>

		<p class="buttons">
			<button type="submit" >
				<?php echo CHECKMARK_GREEN; ?>
				<strong><?php echo gettext("Apply"); ?></strong>
			</button>
			<button type="reset">
				<?php echo CROSS_MARK_RED; ?>
				<strong><?php echo gettext("Reset"); ?></strong>
			</button>

		</p>

		<br clear="all">

	</form>
</div>
<?php
echo "\n" . '</div>'; //content
echo "\n" . '</div>'; //container
echo "\n" . '</div>'; //main

printAdminFooter();
echo "\n</body>";
echo "\n</html>";
?>