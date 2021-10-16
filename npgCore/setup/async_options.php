<?php
/*
 * pre-processing for theme, plugin, and mod_rewrite option handling
 */

if (isset($_GET['debug'])) {
	$debug = '&debug';
} else {
	$debug = '';
}
if (defined('TEST_RELEASE') && TEST_RELEASE || strpos(getOption('markRelease_state'), '-DEBUG') !== false) {
	$fullLog = '&fullLog';
} else {
	$fullLog = false;
}

//	preload for check images
$unique = time();
foreach (array('filterDoc', 'zenphoto_package', 'slideshow') as $remove) {
	if (is_dir(USER_PLUGIN_SERVERPATH . $remove)) {
		npgFunctions::removeDir(USER_PLUGIN_SERVERPATH . $remove);
	}
	if (file_exists(USER_PLUGIN_SERVERPATH . $remove . '.php')) {
		unlink(USER_PLUGIN_SERVERPATH . $remove . '.php');
	}
}
enableExtension('slideshow2', 0);
$old = getSerializedArray(getOption('netphotographics_install'));
if (isset($old['NETPHOTOGRAPHICS'])) {
	$from = preg_replace('/\[.*\]/', '', $old['NETPHOTOGRAPHICS']);
} else {
	$from = NULL;
}
?>
<link rel="preload" as="image" href="<?php echo FULLWEBPATH . '/' . CORE_FOLDER . '/setup/icon.php?icon=0'; ?>" />
<link rel="preload" as="image" href="<?php echo FULLWEBPATH . '/' . CORE_FOLDER . '/setup/icon.php?icon=1'; ?>" />
<link rel="preload" as="image" href="<?php echo FULLWEBPATH . '/' . CORE_FOLDER . '/setup/icon.php?icon=2'; ?>" />
<?php
purgeOption('mod_rewrite');
$sfx = getOption('mod_rewrite_image_suffix');
purgeOption('mod_rewrite_image_suffix');
if (!$sfx) {
	$sfx = '.htm';
}
setOptionDefault('mod_rewrite_suffix', $sfx);
setOptionDefault('dirtyform_enable', 2);
purgeOption('mod_rewrite_detected');

//	Update the root index.php file so admin mod_rewrite works
//	Note: this must be done AFTER the mod_rewrite_suffix option is set and before we test if mod_rewrite works!
$rootupdate = updateRootIndexFile();
if (isset($_GET['mod_rewrite'])) {
	$mod_rewrite_link = FULLWEBPATH . '/' . CORE_PATH . '/setup/setup_set-mod_rewrite' . getOption('mod_rewrite_suffix') . '?unique=' . $unique;
	?>
	<link rel="preload" as="image" href="<?php echo $mod_rewrite_link; ?>" />
	<?php
}

//effervescence_plus migration
if (file_exists(SERVERPATH . '/' . THEMEFOLDER . '/effervescence_plus')) {
	if ($_gallery->getCurrentTheme() == 'effervescence_plus') {
		$_gallery->setCurrentTheme('effervescence+');
		$_gallery->save();
	}
	$options = query_full_array('SELECT LCASE(`name`) as name, `value` FROM ' . prefix('options') . ' WHERE `theme`="effervescence_plus"');
	foreach ($options as $option) {
		setThemeOption($option['name'], $option['value'], NULL, 'effervescence+', true);
	}
	npgFunctions::removeDir(SERVERPATH . '/' . THEMEFOLDER . '/effervescence_plus');
}

$thirdParty = $deprecated = false;
setOptionDefault('deprecated_functions_signature', NULL);
//set plugin default options by instantiating the options interface
setOptionDefault('deprecated_functions_signature', NULL);
$plugins = getPluginFiles('*.php');
$plugins = array_keys($plugins);
$plugin_links = array();
$deprecatedDeleted = getSerializedArray(getOption('deleted_deprecated_plugins'));
localeSort($plugins);

foreach ($plugins as $key => $extension) {
	$class = 0;
	$path = getPlugin($extension . '.php');
	if (strpos($path, USER_PLUGIN_SERVERPATH) === 0) {
		if (distributedPlugin($plugin)) {
			unset($plugins[$key]);
		} else {
			$class = 1;
			$thirdParty = true;
		}
	} else {
		unset($plugins[$key]);
	}

	if (isset($pluginDetails[$extension]['deprecated'])) {
		// Was once a distributed plugin
		$k = array_search($extension, $deprecatedDeleted);
		if (is_numeric($k)) {
			if (extensionEnabled($extension)) {
				unset($deprecatedDeleted[$k]);
			} else {
				if (is_dir(USER_PLUGIN_SERVERPATH . $extension)) {
					npgFunctions::removeDir(USER_PLUGIN_SERVERPATH . $extension);
				}
				unlink(USER_PLUGIN_SERVERPATH . $extension . '.php');
				unset($plugins[$key]);
				continue;
			}
		}
		$class = 2;
		$deprecated = true;
		$addl = ' (' . gettext('deprecated') . ')';
	} else {
		$addl = '';
	}
	$plugin_links[$extension] = FULLWEBPATH . '/' . CORE_FOLDER . '/setup/setup_pluginOptions.php?plugin=' . $extension . $debug
					. '&class=' . $class . $fullLog . '&from=' . $from . '&unique=' . $unique;
	?>
	<link rel="preload" as="image" href="<?php echo $plugin_links[$extension]; ?>" />
	<?php
}

setOption('deleted_deprecated_plugins', serialize($deprecatedDeleted));

$theme_links = array();
setOption('known_themes', serialize(array())); //	reset known themes
$themes = array_keys($info = $_gallery->getThemes());
localeSort($themes);
foreach ($themes as $key => $theme) {
	$class = 0;
	if (protectedTheme($theme)) {
		unset($themes[$key]);
	} else {
		$class = 1;
		$thirdParty = true;
	}
	if (isset($info[$theme]['deprecated'])) {
		$class = 2;
		$deprecated = true;
	}
	$theme_links[$theme] = FULLWEBPATH . '/' . CORE_FOLDER . '/setup/setup_themeOptions.php?theme=' . urlencode($theme) . $debug
					. '&class=' . $class . $fullLog . '&from' . $from . '&unique=' . $unique;
	?>
	<link rel="preload" as="image" href="<?php echo $theme_links[$theme]; ?>" />
	<?php
}

