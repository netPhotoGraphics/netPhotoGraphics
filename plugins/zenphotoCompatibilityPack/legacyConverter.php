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

require_once(file_get_contents(dirname(dirname($_SERVER['SCRIPT_FILENAME'])) . '/core-locator.npg') . "admin-globals.php");
admin_securityChecks(THEMES_RIGHTS, currentRelativeURL());

/**
 *
 * enumerates the files in folder(s)
 * @param $folder
 */
function getFiles($folder) {
	global $_resident_files;
	$localfiles = array();
	$localfolders = array();
	if (file_exists($folder)) {
		$dirs = scandir($folder);
		foreach ($dirs as $file) {
			if ($file[0] != '.') {
				$file = str_replace('\\', '/', $file);
				$key = $folder . '/' . $file;
				if (is_dir($folder . '/' . $file)) {
					$localfolders = array_merge($localfolders, getFiles($folder . '/' . $file));
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
			$file = USER_PLUGIN_SERVERPATH . $name . '.php';
			break;
		case'theme':
			$file = SERVERPATH . '/' . THEMEFOLDER . '/' . $name . '/theme_description.php';
			break;
	}
	if (file_exists($file)) {
		$body = file_get_contents($file);
		preg_match('~/\* LegacyConverter(.*)was here \*/~', $body, $matches);
		return isset($matches[1]) ? $matches[1] : FALSE;
	}
	return false;
}

if (isset($_GET['action'])) {
	$files = array();
	if (isset($_POST['themes'])) {
		foreach ($_POST['themes'] as $theme) {
			$themeFiles = getFiles(SERVERPATH . '/' . THEMEFOLDER . '/' . $theme);
			$files = array_merge($files, $themeFiles);
		}
	}
	if (isset($_POST['plugins'])) {
		foreach ($_POST['plugins'] as $plugin) {
			$pluginFiles = getFiles(USER_PLUGIN_SERVERPATH . $plugin);
			$pluginFiles[] = USER_PLUGIN_SERVERPATH . $plugin . '.php';
			$files = array_merge($files, $pluginFiles);
		}
	}
	$counter = 0;

	foreach ($files as $file) {
		$source = $body = file_get_contents($file);
		preg_match('~/\* LegacyConverter(.*)was here \*/~', $body, $matches);
		if (isset($matches[1])) {
			$body = str_replace($matches[0], '', $body);
		}
		preg_match('~\<\?php(.*)$~ixUs', $body, $matches);
		if (isset($matches[0])) {
			$body = str_replace($matches[0], "<?php\n/* LegacyConverter v" . NETPHOTOGRAPHICS_VERSION_CONCISE . " was here */\n" . trim($matches[1], "\n") . "\n?>", $body);
		}
		foreach ($legacyReplacements as $match => $replace) {
			$body = preg_replace('~' . $match . '~im', $replace, $body);
		}
		$body = preg_replace('~/\* TODO:replaced.*/\*(.*?)\*/.*\*/~', '/*$1*/', $body); //in case we came here twice

		do { //	remove trailing php close tokens
			$again = false;
			$body = trim($body);
			$close = substr($body, -2);
			if ($close == '?>') {
				$body = substr($body, 0, -2);
				$again = true;
			}
		} while ($again);
		$body = $body . "\n";

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
		<?php echo gettext('Note: you should review any the results of this conversion. Look for the <code>/* TODO:.... */</code> in the scripts as these contain suggestions on further improvements.'); ?>
	</div>
	<?php
	$themesP = $themes = $plugins = $pluginsP = $processedV = array();
	foreach ($_gallery->getThemes() as $theme => $data) {
		if (!protectedTheme($theme)) {
			$themes[] = $theme;
			if ($v = checkIfProcessed('theme', $theme)) {
				$themesP[] = $theme;
				$processedV[$theme] = $v;
			}
		}
	}
	$paths = getPluginFiles('*.php');
	foreach ($paths as $plugin => $path) {
		if (strpos($path, USER_PLUGIN_FOLDER) !== false) {
			$name = stripSuffix(basename($path));
			if (!distributedPlugin($name)) {
				$plugins[] = $name;
				if ($v = checkIfProcessed('plugin', $name)) {
					$pluginsP[] = $name;
					$processedV[$name] = $v;
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
											<?php echo gettext(' (already processed' . rtrim($processedV[$theme]) . ')'); ?>
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
											<?php echo gettext(' (already processed' . rtrim($processedV[$plugin]) . ')'); ?>
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

		<p>
			<?php
			applyButton();
			resetButton();
			?>
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
