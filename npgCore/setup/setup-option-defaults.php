<?php
// force UTF-8 Ø

/**
 * stores all the default values for options
 *
 * @author Stephen Billard (sbillard)
 *
 * @package setup
 */
setupLog(gettext('Set default options'), true);

require_once(CORE_SERVERPATH . 'admin-globals.php');
if (CURL_ENABLED) {
	require (CORE_SERVERPATH . 'lib-CURL.php');
}

$setOptions = getOptionList();

$testFile = SERVERPATH . '/' . DATA_FOLDER . '/' . internalToFilesystem('charset_tést.cfg');
if (!file_exists($testFile)) {
	if (is_link($testFile)) {
		unlink($testFile); //	if it were a symbolic link....
	}
	file_put_contents($testFile, '');
}

if (isset($_GET['debug'])) {
	$debug = '&debug';
} else {
	$debug = '';
}
if ($test_release = getOption('markRelease_state')) {
	$test_release = strpos($test_release, '-DEBUG');
}
$testRelease = defined('TEST_RELEASE') && TEST_RELEASE || $test_release !== false;
if ($testRelease) {
	$fullLog = '&fullLog';
} else {
	$fullLog = false;
}

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

if (CURL_ENABLED) {
	//	preload for check images
	?>
	<link rel="preload" as="image" href="<?php echo FULLWEBPATH . '/' . CORE_FOLDER . '/setup/icon.php?icon=0'; ?>" />
	<?php
}
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
	//	test mod_rewrite
	$mod_rewrite_link = FULLWEBPATH . '/' . CORE_PATH . '/setup/setup_set-mod_rewrite' . getOption('mod_rewrite_suffix') . '?rewrite=' . MOD_REWRITE . '&unique=' . $unique;
} else {
	$mod_rewrite_link = false;
}

$themes = array_keys($info = $_gallery->getThemes());
localeSort($themes);

//	update creator for old zp_core indicators
$rslt = query('SELECT `id`,`creator` FROM ' . prefix('options') . ' WHERE `creator` LIKE "zp-core/%"');
if ($rslt) {
	while ($option = db_fetch_assoc($rslt)) {
		query('UPDATE ' . prefix('options') . ' SET `creator`="' . str_replace('zp-core/', 'npgCore/', $option['creator']) . '"');
	}
	db_free_result($rslt);
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

//tnyMCE v7 migration
$tinymce_v5_configs = array();
if (is_dir(USER_PLUGIN_SERVERPATH . 'tinymce/config')) {
	$tinymce_v5_configs[USER_PLUGIN_SERVERPATH . 'tinymce/config'] = USER_PLUGIN_FOLDER;
}
foreach ($themes as $key => $theme) {
	if (is_dir(SERVERPATH . '/' . THEMEFOLDER . '/' . $theme . '/tinymce/config')) {
		$tinymce_v5_configs[SERVERPATH . '/' . THEMEFOLDER . '/' . $theme . '/tinymce/config'] = THEMEFOLDER . '/' . $theme;
	}
}
if (!empty($tinymce_v5_configs)) {
	$found = false;
	$count = 0;
	foreach ($tinymce_v5_configs as $folder => $name) {
		$files = safe_glob($folder . '/*.php');
		foreach ($files as $file) {
			$config = file_get_contents($file);
			$i = strpos($config, '$MCEplugins');
			if ($i) {
				$config = substr($config, $i);
				$i = strpos($config, ';');
				$config = substr($config, 0, $i + 1);
				eval($config);
				if (!is_array($MCEplugins)) {
					$MCEplugins = explode(' ', $MCEplugins);
				}
				foreach ([
		'blockformats',
		'colorpicker',
		'fontformats',
		'fontselect',
		'fontsizes',
		'fontsizeselect',
		'formats',
		'formatselect',
		'hr',
		'imagetools',
		'paste',
		'styleselect',
		'template',
		'textcolor',
		'toc',
		'tocupdate'
				] as $target) {
					if (in_array($target, $MCEplugins)) {
						$found .= '<em>' . str_replace(SERVERPATH . '/', '', $file) . '</em>, ';
						$count++;
						break;
					}
				}
			}
		}
	}
	if ($found) {
		$found = rtrim($found, ', ');
		$last = strripos($found, ', ');
		switch ($count) {
			case 1:
				break;
			case 2:
				$found = substr($found, 0, $last) . gettext(' and ') . substr($found, $last + 1);
				break;
			default:
				$found = substr($found, 0, $last + 1) . gettext(' and ') . substr($found, $last + 1);

				break;
		}

		$msg = sprintf(gettext('Setup detected <em>tinymce version 5</em> configuration files. <strong>netPhotoGraphics</strong> has migrated tinyMCE to version 7. You will need to migrate %3$s.<br/>TinyMCE Migration is described in %1$s and %2$s'),
						'https://www.tiny.cloud/docs/tinymce/6/migration-from-5x/',
						'https://www.tiny.cloud/docs/tinymce/latest/migration-from-6x/',
						$found);
		setupLog('<span class="logwarning">' . $msg . '</span>', true);
		?>
		<div class="warningbox">
		<?php echo $msg; ?>
		</div>
		<?php
		$autorun = false;
	}
}

$thirdParty = $deprecated = false;

list($plugin_subtabs, $plugin_default, $plugins, $plugin_paths, $plugin_member, $classXlate, $pluginDetails) = getPluginTabs();

//set plugin default options by instantiating the options interface
$plugin_links = array();
$deprecatedDeleted = getSerializedArray(getOption('deleted_deprecated_plugins'));
localeSort($plugins);
$package = file_get_contents(CORE_SERVERPATH . 'netPhotoGraphics.package');
preg_match_all('~' . USER_PLUGIN_FOLDER . '/([^/]*).php~', $package, $matches);
$npgPlugins = $matches[1];

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
				if (file_exists(USER_PLUGIN_SERVERPATH . $extension . '.php')) {
					unlink(USER_PLUGIN_SERVERPATH . $extension . '.php');
				}
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
	$plugin_links[$extension] = FULLWEBPATH . '/' . CORE_FOLDER . '/setup/setup_pluginOptions.php?plugin=' . $extension . '&class=' . $class . $fullLog . '&from=' . $from . '&unique=' . $unique;
}

setOption('deleted_deprecated_plugins', serialize($deprecatedDeleted));

$theme_links = array();
setOption('known_themes', serialize(array())); //	reset known themes
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
	$theme_links[$theme] = FULLWEBPATH . '/' . CORE_FOLDER . '/setup/setup_themeOptions.php?theme=' . urlencode($theme) . '&class=' . $class . $fullLog . '&from=' . $from . '&unique=' . $unique;
}

$salt = 'abcdefghijklmnopqursuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789~!@#$%^&*()_+-={}[]|;,.<>?/';
$list = range(0, strlen($salt) - 1);
if (!isset($setOptions['extra_auth_hash_text'])) {
	// setup a hash seed
	$auth_extratext = "";
	shuffle($list);
	for ($i = 0; $i < 30; $i++) {
		$auth_extratext = $auth_extratext . $salt[$list[$i]];
	}
	setOption('extra_auth_hash_text', $auth_extratext);
}
if (!isset($setOptions['secret_key_text'])) {
	$auth_extratext = "";
	shuffle($list);
	for ($i = 0; $i < 30; $i++) {
		$auth_extratext = $auth_extratext . $salt[$list[$i]];
	}
	setOption('secret_key_text', $auth_extratext);
}
if (!isset($setOptions['secret_init_vector'])) {
	$auth_extratext = "";
	shuffle($list);
	for ($i = 0; $i < 30; $i++) {
		$auth_extratext = $auth_extratext . $salt[$list[$i]];
	}
	setOption('secret_init_vector', $auth_extratext);
}
purgeOption('adminTagsTab');

//	if your are installing, you must be OK
if ($_current_admin_obj) {
	$_current_admin_obj->setPolicyAck(1);
	$_current_admin_obj->save();
}

/* fix for NULL theme name */
Query('UPDATE ' . prefix('options') . ' SET `theme`="" WHERE `theme` IS NULL');

/* fix the admin_to_object table. type=news should have been type=news_categories */
$sql = 'UPDATE ' . prefix('admin_to_object') . ' SET `type`="news_categories" WHERE `type`="news"';
query($sql);

$sql = 'SELECT `id`, `creator` FROM ' . prefix('options') . ' WHERE `theme`="" AND `creator` LIKE "themes/%";';
$result = query_full_array($sql);
foreach ($result as $row) {
	$elements = explode('/', $row['creator']);
	$theme = $elements[1];
	$sql = 'UPDATE ' . prefix('options') . ' SET `theme`=' . db_quote($theme) . ' WHERE `id`=' . $row['id'] . ';';
	if (!query($sql, false)) {
		$rslt = query('DELETE FROM ' . prefix('options') . ' WHERE `id`=' . $row['id'] . ';');
	}
}

//migrate plugin enables removing "zp" from name
$sql = 'SELECT `id`, `name` FROM ' . prefix('options') . ' WHERE `name` LIKE "zp\_plugin\_%"';
$result = query($sql);
if ($result) {
	while ($row = db_fetch_assoc($result)) {
		$sql = 'UPDATE ' . prefix('options') . ' SET `name`=' . db_quote(substr($row['name'], 2)) . ' WHERE `id`=' . $row['id'];
		if (!query($sql, false)) {
			// the plugin has executed defaultExtension() which has set the _plugin_ option already
			$sql = 'DELETE FROM ' . prefix('options') . ' WHERE `id`=' . $row['id'];
			query($sql);
		}
	}
	db_free_result($result);
}
//clean up plugin creator field
$sql = 'UPDATE ' . prefix('options') . ' SET `creator`=' . db_quote(CORE_FOLDER . '/setup/setup-option-defaults.php[' . __LINE__ . ']') . ' WHERE `name` LIKE "\_plugin\_%" AND `creator` IS NULL;';
query($sql);

//clean up tag list quoted strings
$sql = 'SELECT `id`, `name` FROM ' . prefix('tags') . ' WHERE `name` LIKE \'"%\' OR `name` LIKE "\'%"';
$result = query($sql);
if ($result) {
	while ($row = db_fetch_assoc($result)) {
		$sql = 'UPDATE ' . prefix('tags') . ' SET `name`=' . db_quote(trim($row['name'], '"\'')) . ' WHERE `id`=' . $row['id'];
		if (!query($sql, false)) {
			$oldtag = $row['id'];
			$sql = 'DELETE FROM ' . prefix('tags') . ' WHERE `id`=' . $oldtag;
			query($sql);
			$sql = 'SELECT `id`, `name` FROM ' . prefix('tags') . ' WHERE `name`=' . db_quote(trim($row['name'], '"\''));
			$row = query_single_row($sql);
			if (!empty($row)) {
				$sql = 'UPDATE ' . prefix('obj_to_tag') . ' SET `tagid`=' . $row['id'] . ' WHERE `tagid`=' . $oldtag;
			}
		}
	}
	db_free_result($result);
}

//migrate "publish" dates
foreach (array('albums', 'images', 'news', 'pages') as $table) {
	$sql = 'UPDATE ' . prefix($table) . ' SET `publishdate`=NULL WHERE `publishdate` ="0000-00-00 00:00:00"';
	query($sql);
	$sql = 'UPDATE ' . prefix($table) . ' SET `expiredate`=NULL WHERE `expiredate` ="0000-00-00 00:00:00"';
	query($sql);
	$sql = 'UPDATE ' . prefix($table) . ' SET `publishdate`=`date` WHERE `publishdate` IS NULL AND `show`="1"';
	query($sql);
	$sql = 'UPDATE ' . prefix($table) . ' SET `lastchange`=`date` WHERE `lastchange` IS NULL';
	query($sql);
	$sql = 'UPDATE ' . prefix($table) . ' SET `date`=`lastchange` WHERE `date` IS NULL';
	query($sql);
	$sql = 'UPDATE ' . prefix($table) . ' SET `lastchangeuser`=`owner` WHERE `lastchangeuser` IS NULL';
	query($sql);
}
// published albums where both the `publishdate` and the `date` were NULL
$sql = 'SELECT `mtime`,`id` FROM ' . prefix('albums') . ' WHERE `publishdate` IS NULL AND `show`="1"';
$result = query($sql);
if ($result) {
	while ($row = db_fetch_assoc($result)) {
		$sql = 'UPDATE ' . prefix('albums') . ' SET `publishdate`=' . db_quote(date('Y-m-d H:i:s', $row['mtime'])) . ' WHERE `id`=' . $row['id'];
		query($sql);
	}
	db_free_result($result);
}
//	fix empty sort_order
foreach (array('news_categories', 'pages', 'images', 'albums', 'menu') as $table) {
	$sql = 'UPDATE ' . prefix($table) . ' SET `sort_order`="000" WHERE (`sort_order` IS NULL OR `sort_order`="")';
	query($sql);
}

//migrate rotation and GPS data
$result = db_list_fields('images');

$where = '';
if (isset($result['EXIFOrientation'])) {
	$where = ' OR (`rotation` IS NULL AND `EXIFOrientation`!="")';
}
if (isset($result['EXIFGPSLatitude'])) {
	$where .= ' OR (`GPSLatitude` IS NULL AND NOT `EXIFGPSLatitude` IS NULL)';
} else if (isset($result['EXIFGPSLongitude'])) {
	$where .= ' OR (`GPSLongitude` IS NULL AND NOT `EXIFGPSLongitude` IS NULL)';
} else if (isset($result['EXIFGPSAltitude'])) {
	$where .= ' OR (`GPSAltitude` IS NULL AND NOT `EXIFGPSAltitude` IS NULL)';
}
$where = ltrim($where, ' OR ');

if (!empty($where)) {
	$sql = 'SELECT `id` FROM ' . prefix('images') . ' WHERE ' . $where;
	$result = query($sql);
	if ($result) {
		while ($row = db_fetch_assoc($result)) {
			$img = getItemByID('images', $row['id']);
			if ($img) {
				foreach (array('EXIFGPSLatitude', 'EXIFGPSLongitude') as $source) {
					$data = floatval($img->get($source));
					if (!empty($data)) {
						if (in_array(strtoupper($img->get($source . 'Ref')), array('S', 'W'))) {
							$data = -$data;
						}
						$img->set(substr($source, 4), $data);
					}
				}
				$alt = floatval($img->get('EXIFGPSAltitude'));
				if (!empty($alt)) {
					$ref = $img->get('EXIFGPSAltitudeRef');
					if (!is_null($ref) && $ref != 0) {
						$alt = -$alt;
					}
					$img->set('GPSAltitude', $alt);
				}
				$img->set('rotation', substr(trim($img->get('EXIFOrientation'), '!'), 0, 1));
				$img->save();
			}
		}
		db_free_result($result);
	}
}

//	cleanup mutexes
$list = safe_glob(SERVERPATH . '/' . DATA_FOLDER . '/' . MUTEX_FOLDER . '/*');
foreach ($list as $file) {
	switch (basename($file)) {
		case 'sP':
		case 'npg':
			//these are used during setup!
			break;
		default:
			unlink($file);
			break;
	}
}

//	migrate theme name changes
$migrate = array('zpArdoise' => 'zpArdoise', 'zpBootstrap' => 'zpBootstrap', 'zpEnlighten' => 'zpEnlighten', 'zpmasonry' => 'zpMasonry', 'zpminimal' => 'zpMinimal', 'zpmobile' => 'zpMobile');
foreach ($migrate as $file => $theme) {
	deleteDirectory(SERVERPATH . '/' . THEMEFOLDER . '/' . $file); //	remove old version
	$newtheme = lcfirst(substr($theme, 2));
	$result = query('SELECT `id`, `creator` FROM ' . prefix('options') . ' WHERE `theme`=' . db_quote($theme));
	if ($result) {
		while ($row = db_fetch_assoc($result)) {
			$newcreator = str_replace($theme, $newtheme, $row['creator']);
			query('UPDATE ' . prefix('options') . ' SET `theme`=' . db_quote($newtheme) . ', `creator`=' . db_quote($newcreator) . ' WHERE `id`=' . $row['id'], FALSE);
		}
		db_free_result($result);
	}
}
if (in_array($_gallery->getCurrentTheme(), $migrate)) {
	$_gallery->setCurrentTheme(substr($current_theme, 2));
	$_gallery->save();
}

if (SYMLINK && !npgFunctions::hasPrimaryScripts()) {
	$themes = array();
	if ($dp = opendir(SERVERPATH . '/' . THEMEFOLDER)) {
		while (false !== ($theme = readdir($dp))) {
			$p = SERVERPATH . '/' . THEMEFOLDER . '/' . $theme;
			if (is_link($p)) {
				if (!is_dir(readlink($p))) { //	theme removed from master install
					if (!@rmdir($p)) {
						unlink($p);
					}
				}
			}
		}
	}
	//	update symlinks
	$master = clonedFrom();
	foreach ($migrate as $theme) {
		$theme = lcfirst(substr($theme, 2));
		if (!is_link($p = SERVERPATH . '/' . THEMEFOLDER . '/' . $theme)) {
			symlink($master . '/' . THEMEFOLDER . '/' . $theme, $p);
		}
	}
}

//	check custom email form for unsubscribe link
if (file_exists(SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/forms/mailForm.htm')) {
	$form = file_get_contents(SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/forms/mailForm.htm');
	if (strpos($form, '%WEBPATH%/%CORE_PATH%/%PLUGIN_PATH%/user_mailing_list/subscription') !== FALSE) {
		setupLog(gettext('<span style="color: red;">Setup detected an un-subscribe link in your custom mail form. You should remove the link as it is supplied automatically when appropriate.</span>'), TRUE);
	}
}

setOption('last_admin_action', time());
setOptionDefault('galleryToken_link', '_PAGE_/gallery');
setOptionDefault('gallery_data', NULL);

purgeOption('netphotographics_install');
setOption('netphotographics_install', serialize(installSignature()));

$questions[] = getSerializedArray(getAllTranslations("What is your father’s middle name?"));
$questions[] = getSerializedArray(getAllTranslations("What street did your Grandmother live on?"));
$questions[] = getSerializedArray(getAllTranslations("Who was your favorite singer?"));
$questions[] = getSerializedArray(getAllTranslations("When did you first get a computer?"));
$questions[] = getSerializedArray(getAllTranslations("How much wood could a woodchuck chuck if a woodchuck could chuck wood?"));
$questions[] = getSerializedArray(getAllTranslations("What is the date of the Ides of March?"));
setOptionDefault('challenge_foils', serialize($questions));
setOptionDefault('online_persistance', 5);

if ($_authority->count('allusers') == 0) { //	empty administrators table
	$groupsdefined = NULL;
	if (isset($_SESSION['clone'][$cloneid])) { //replicate the user who cloned the install
		$clone = $_SESSION['clone'][$cloneid];
		setOption('UTF8_image_URI', $clone['UTF8_image_URI']);
		setOption('strong_hash', $clone['strong_hash']);
		setOption('extra_auth_hash_text', $clone['hash']);
		setOption('deprecated_functions_signature', $clone['deprecated_functions_signature']);
		setOption('zenphotoCompatibilityPack_signature', $clone['zenphotoCompatibilityPack_signature']);
		if ($clone['mod_rewrite']) {
			$_GET['mod_rewrite'] = true;
			setOption('mod_rewrite', 1);
		}
		//	replicate plugins state
		foreach ($clone['plugins'] as $pluginOption => $priority) {
			setOption($pluginOption, $priority);
		}
		$admin_obj = unserialize($_SESSION['admin'][$cloneid]);
		$admindata = $admin_obj->getData();
		$myadmin = new npg_Administrator($admindata['user'], 1);
		unset($admindata['id']);
		unset($admindata['user']);
		foreach ($admindata as $key => $value) {
			$myadmin->set($key, $value);
		}
		$myadmin->save();
		npg_Authority::logUser($myadmin);
		$_loggedin = ALL_RIGHTS;
		setOption('license_accepted', NETPHOTOGRAPHICS_VERSION);
		unset($_SESSION['clone'][$cloneid]);
		unset($_SESSION['admin'][$cloneid]);
	} else {
		if (npg_Authority::$preferred_version > ($oldv = getOption('libauth_version'))) {
			if (empty($oldv)) {
				//	The password hash of these old versions did not have the extra text.
				//	Note: if the administrators table is empty we will re-do this option with the good stuff.
				setOption('extra_auth_hash_text', '');
			} else {
				$msg = sprintf(gettext('Migrating lib-auth data version %1$s => version %2$s '), $oldv, npg_Authority::$preferred_version);
				if (!$_authority->migrateAuth(npg_Authority::$preferred_version)) {
					$msg .= ': ' . gettext('failed');
				}
				echo $msg;
				setupLog($msg, true);
			}
		}
	}
} else {
	$groupsdefined = getSerializedArray(getOption('defined_groups'));
}
purgeOption('defined_groups');

// old configuration opitons. preserve them
$conf = $_conf_vars;

$showDefaultThumbs = array();
foreach (getOptionsLike('album_tab_default_thumbs_') as $option => $value) {
	if ($value) {
		$tab = str_replace('album_tab_default_thumbs_', '', $option);
		if (empty($tab))
			$tab = '*';
		$showDefaultThumbs[$tab] = $tab;
	}
	purgeOption($option);
}
setOptionDefault('album_tab_showDefaultThumbs', serialize($showDefaultThumbs));

$showDefaultThumbs = getSerializedArray(getOption('album_tab_showDefaultThumbs'));
foreach ($showDefaultThumbs as $key => $value) {
	if (!file_exists(getAlbumFolder() . $value)) {
		unset($showDefaultThumbs[$key]);
	}
}
setOption('album_tab_showDefaultThumbs', serialize($showDefaultThumbs));

setOptionDefault('time_zone', formattedDate('T'));

if (isset($_GET['mod_rewrite'])) {
	?>
	<p>
	<?php echo gettext('Mod_Rewrite '); ?>
		<span>
	<?php
	if (CURL_ENABLED) {
		$icon = curlRequest($mod_rewrite_link . '&curl');
		if (is_numeric($icon)) {
			?>
					<img id = "MODREWRITE" src = "<?php echo FULLWEBPATH . '/' . CORE_FOLDER . '/setup/icon.php?icon=0'; ?>" height = "16px" width = "16px">
					<?php
				} else {
					?>
					<img src="<?php echo FULLWEBPATH . '/' . CORE_FOLDER; ?>/images/action.png" title="<?php echo gettext('Mod Rewrite is not working'); ?>" />
					<?php
				}
			} else {
				?>
				<img id = "MODREWRITE" src = "<?php echo $mod_rewrite_link; ?>" height = "16px" width = "16px" onerror = "this.onerror=null;this.src='<?php echo FULLWEBPATH . '/' . CORE_FOLDER; ?>/images/action.png';this.title='<?php echo gettext('Mod Rewrite is not working'); ?>'" />
				<?php
			}
			?>
		</span>
	</p>
			<?php
		}

		if (!CURL_ENABLED) {
			?>
	<script>
		$(function () {
			$('img').on("error", function () {
				var link = $(this).attr('src');
				var title = $(this).attr('title');
				$(this).parent().html('<a href="' + link + '&debug' + '" target="_blank" title="' + title + '"><?php echo CROSS_MARK_RED; ?></a>');
				imageErr = true;
				$('#setupErrors').val(1);
				$('#errornote').show();
			});
		});
	</script>
	<?php
}

setOptionDefault('UTF8_image_URI_found', 'unknown');
if (isset($_POST['setUTF8URI'])) {
	setOption('UTF8_image_URI_found', sanitize($_POST['setUTF8URI']));
	if ($_POST['setUTF8URI'] == 'unknown') {
		setupLog(gettext('Setup could not find a configuration that allows image URIs containing diacritical marks.'), true);
		setOptionDefault('UTF8_image_URI', 1);
	} else {
		setOptionDefault('UTF8_image_URI', (int) ( $_POST['setUTF8URI'] == 'internal'));
	}
}
setOptionDefault('unique_image_prefix', NULL);

setOptionDefault('charset', "UTF-8");
setOptionDefault('image_quality', 85);
setOptionDefault('thumb_quality', 75);
setOptionDefault('last_garbage_collect', time());
setOptionDefault('cookie_persistence', 5184000);
setOptionDefault('cookie_path', WEBPATH);

setOptionDefault('search_password', '');
setOptionDefault('search_hint', NULL);

setOptionDefault('backup_compression', 0);
setOptionDefault('license_accepted', 0);

setOptionDefault('protected_image_cache', NULL);
setOptionDefault('secure_image_processor', NULL);

$cachesuffix = array_unique($_cachefileSuffix);
if (ENCODING_FALLBACK && in_array(FALLBACK_SUFFIX, $cachesuffix)) {
	if (getOption('image_cache_suffix') == FALLBACK_SUFFIX) {
		setOption('image_cache_suffix', '');
	}
} else {
	purgeOption('encoding_fallback');
}
$s = getOption('image_cache_suffix');
if ($s && !in_array($s, $cachesuffix)) {
	setOption('image_cache_suffix', '');
}

setoptionDefault('image_allow_upscale', NULL);
setoptionDefault('image_cache_suffix', NULL);
setoptionDefault('image_sharpen', NULL);
setoptionDefault('image_interlace', NULL);
setOptionDefault('thumb_sharpen', NULL);
setOptionDefault('use_embedded_thumb', NULL);

setOptionDefault('watermark_image', 'watermarks/watermark.png');
if (getOption('perform_watermark')) {
	$v = str_replace('.png', "", basename(getOption('watermark_image')));
} else {
	$v = NULL;
}
setoptionDefault('fullimage_watermark', $v);

setOptionDefault('pasteImageSize', NULL);
setOptionDefault('watermark_h_offset', 90);
setOptionDefault('watermark_w_offset', 90);
setOptionDefault('watermark_scale', 5);
setOptionDefault('watermark_allow_upscale', 1);
setOptionDefault('perform_video_watermark', 0);
setOptionDefault('ImbedIPTC', NULL);

if (getOption('perform_video_watermark')) {
	$v = str_replace('.png', "", basename(getOption('video_watermark_image')));
	setoptionDefault('video_watermark', $v);
}

setOptionDefault('hotlink_protection', '1');

setOptionDefault('search_fields', 'title,desc,tags,file,location,city,state,country,content,author');

$style_tags = "abbr=>(class=>() id=>() title=>() lang=>())\n" .
				"acronym=>(class=>() id=>() title=>() lang=>())\n" .
				"b=>(class=>() id=>() lang=>())\n" .
				"blockquote=>(class=>() id=>() cite=>() lang=>())\n" .
				"br=>(class=>() id=>())\n" .
				"code=>(class=>() id=>() lang=>())\n" .
				"em=>(class=>() id=>() lang=>())\n" .
				"i=>(class=>() id=>() lang=>())\n" .
				"strike=>(class=>() id=>() lang=>())\n" .
				"strong=>(class=>() id=>() lang=>())\n" .
				"sup=>(class=>() id=>() lang=>())\n" .
				"sub=>(class=>() id=>() lang=>())\n" .
				"del => (class=>() id=>() lang=>())\n"
;
$a = parseAllowedTags($style_tags);
if (!is_array($a)) {
	debugLog('$style_tags parse error:' . $a);
}

$general_tags = "a=>(href=>() title=>() target=>() class=>() id=>() rel=>() lang=>())\n" .
				"ul=>(class=>() id=>() lang=>())\n" .
				"ol=>(class=>() id=>() lang=>())\n" .
				"li=>(class=>() id=>() lang=>())\n" .
				"dl =>(class=>() id=>() lang=>())\n" .
				"dt =>(class=>() id=>() lang=>())\n" .
				"dd =>(class=>() id=>() lang=>())\n" .
				"p=>(class=>() id=>() style=>() lang=>())\n" .
				"h1=>(class=>() id=>() style=>() lang=>())\n" .
				"h2=>(class=>() id=>() style=>() lang=>())\n" .
				"h3=>(class=>() id=>() style=>() lang=>())\n" .
				"h4=>(class=>() id=>() style=>() lang=>())\n" .
				"h5=>(class=>() id=>() style=>() lang=>())\n" .
				"h6=>(class=>() id=>() style=>() lang=>())\n" .
				"pre=>(class=>() id=>() style=>() lang=>())\n" .
				"address=>(class=>() id=>() style=>() lang=>())\n" .
				"span=>(class=>() id=>() style=>() lang=>())\n" .
				"div=>(class=>() id=>() style=>() lang=>())\n" .
				"img=>(class=>() id=>() style=>() src=>() title=>() alt=>() width=>() height=>() sizes=>() srcset=>() loading=>() lang=>())\n" .
				"iframe=>(class=>() id=>() style=>() src=>() title=>() width=>() height=>() loading=>() lang=>())\n" .
				"figure=>(class=>() id=>() style=>() lang=>())\n" .
				"figcaption=>(class=>() id=>() style=>() lang=>())\n" .
				"article=>(class=>() id=>() style=>() lang=>())\n" .
				"section=>(class=>() id=>() style=>() lang=>())\n" .
				"nav=>(class=>() id=>() style=>() lang=>())\n" .
				"video=>(class=>() id=>() style=>() src=>() controls=>() autoplay=>() buffered=>() height=>() width=>() loop=>() muted=>() preload=>() poster=>() lang=>())\n" .
				"audio=>(class=>() id=>() style=>() src=>() controls=>() autoplay=>() buffered=>() height=>() width=>() loop=>() muted=>() preload=>() volume=>() lang=>())\n" .
				"picture=>(class=>() id=>() lang=>())\n" .
				"source=>(src=>() srcset=>() sizes=>() type=>() media=>() lang=>())\n" .
				"track=>(src=>() kind=>() srclang=>() label=>() default=>() lang=>())\n" .
				"table=>(class=>() id=>() lang=>())\n" .
				"caption=>(class=>() id=>() lang=>())\n" .
				"tr=>(class=>() id=>() lang=>())\n" .
				"th=>(class=>() id=>() colspan=>() lang=>())\n" .
				"td=>(class=>() id=>() colspan=>() lang=>())\n" .
				"thead=>(class=>() id=>() lang=>())\n" .
				"tbody=>(class=>() id=>() lang=>())\n" .
				"tfoot=>(class=>() id=>() lang=>())\n" .
				"colgroup=>(class=>() id=>() lang=>())\n" .
				"col=>(class=>() id=>() lang=>())\n" .
				"form=>(class=>() id=>() title=>() action=>() method=>() accept-charset=>() name=>() target=>() lang=>())\n";
;
$a = parseAllowedTags($general_tags);
if (!is_array($a)) {
	debugLog('$general_tags parse error:' . $a);
}

if (getOption('allowed_tags_default') == getOption('allowed_tags')) {
	purgeOption('allowed_tags'); //	propegate any updates
}
setOption('allowed_tags_default', $style_tags . $general_tags);
setOptionDefault('style_tags', $style_tags);

setOptionDefault('GDPR_text', getAllTranslations('Check to acknowledge the site <a href="%s">usage policy</a>.'));
$GDPR_cookie = getOption('GDPR_cookie');
if (!$GDPR_cookie || strpos(' ', $GDPR_cookie) !== FALSE) {
	setOption('GDPR_cookie', md5(microtime()), NULL, FALSE);
}

setOptionDefault('full_image_quality', 75);
if (getOption('protect_full_image') == 'Protected view') {
	purgeOption('protext_full_image');
}
setOptionDefault('protect_full_image', 'Protected');

setOptionDefault('locale', '');

//	update old strftime date formats to be compatible with DateTime formatting as strftime is deprecated in PHP 8.0
$strftimeXlate = array(
		//Day		--- ---
		'%a' => 'D', // An abbreviated textual representation of the day	Sun through Sat
		'%A' => 'l', // A full textual representation of the day	Sunday through Saturday
		'%d' => 'd', // Two-digit day of the month (with leading zeros)	01 to 31
		'%e' => 'j', // Day of the month, with a space preceding single digits. Not implemented as described on Windows. See below for more information.	1 to 31
		'%j' => 'z', // Day of the year, 3 digits with leading zeros	001 to 366
		'%u' => 'N', // ISO-8601 numeric representation of the day of the week	1 (for Monday) through 7 (for Sunday)
		'%w' => 'w', // Numeric representation of the day of the week	0 (for Sunday) through 6 (for Saturday)
		//Week	---	---
		'%U' => 'W', // Week number of the given year, starting with the first Sunday as the first week	13 (for the 13th full week of the year)
		'%V' => 'W', // [sic]ISO-8601:1988 week number of the given year, starting with the first week of the year with at least 4 weekdays, with Monday being the start of the week	01 through 53 (where 53 accounts for an overlapping week)
		'%W' => 'W', // [sic]A numeric representation of the week of the year, starting with the first Monday as the first week	46 (for the 46th week of the year beginning with a Monday)
		//Month	---	---
		'%b' => 'M', // Abbreviated month name, based on the locale	Jan through Dec
		'%B' => 'F', // Full month name, based on the locale	January through December
		'%h' => 'M', // Abbreviated month name, based on the locale (an alias of %b)	Jan through Dec
		'%m' => 'm', // Two digit representation of the month	01 (for January) through 12 (for December)
		//Year	---	---
		'%C' => 'Y', // [sic]Two digit representation of the century (year divided by 100, truncated to an integer)	19 for the 20th Century
		'%g' => 'o', // Two digit representation of the year going by ISO-8601:1988 standards (see %V)	Example: 09 for the week of January 6, 2009
		'%G' => 'Y', // [sic]The full four-digit version of %g	Example: 2008 for the week of January 3, 2009
		'%y' => 'y', // Two digit representation of the year	Example: 09 for 2009, 79 for 1979
		'%Y' => 'Y', // Four digit representation for the year	Example: 2038
		//Time	--- ---
		'%H' => 'H', // Two digit representation of the hour in 24-hour format	00 through 23
		'%k' => 'G', // Hour in 24-hour format, with a space preceding single digits	0 through 23
		'%I' => 'h', // Two digit representation of the hour in 12-hour format	01 through 12
		'%l' => 'h', // [sic](lower-case 'L')	Hour in 12-hour format, with a space preceding single digits	1 through 12
		'%M' => 'i', // Two digit representation of the minute	00 through 59
		'%p' => 'A', // UPPER-CASE 'AM' or 'PM' based on the given time	Example: AM for 00:31, PM for 22:23
		'%P' => 'a', // lower-case 'am' or 'pm' based on the given time	Example: am for 00:31, pm for 22:23
		'%r' => 'h:i:s', // Same as "%I:%M:%S %p"	Example: 09:34:17 PM for 21:34:17
		'%R' => 'H:i', // Same as "%H:%M"	Example: 00:35 for 12:35 AM, 16:44 for 4:44 PM
		'%S' => 's', // Two digit representation of the second	00 through 59
		'%T' => 'H:i:s', // Same as "%H:%M:%S"	Example: 21:34:17 for 09:34:17 PM
		'%z' => 'O', // The time zone offset. Not implemented as described on Windows. See below for more information.	Example: -0500 for US Eastern Time
		'%Z' => 'T', // The time zone abbreviation. Not implemented as described on Windows. See below for more information.	Example: EST for Eastern Time
		//Time and Date Stamps	---	---
		'%D' => 'm/d/y', // Same as "%m/%d/%y"	Example: 02/05/09 for February 5, 2009
		'%F' => 'y-m-d', // Same as "%Y-%m-%d" (commonly used in database datestamps)	Example: 2009-02-05 for February 5, 2009
		'%s' => 'u', // Unix Epoch Time timestamp (same as the time() function)	Example: 305815200 for September 10, 1979 08:40:00 AM
//	'%X' => '%X', // Preferred time representation based on locale, without the date	Example: 03:59:16 or 15:59:16
//	'%x' => '%x', // Preferred date representation based on locale, without the time	Example: 02/05/09 for February 5, 2009
		'%c' => '%x %X', // Preferred date and time stamp based on locale	Example: Tue Feb 5 00:45:10 2009 for February 5, 2009 at 12:45:10 AM
		//Miscellaneous	---	---
		'%n' => "\n", //	A newline character ("\n")
		'%t' => "\t", // A Tab character ("\t")
		'%%' => "%" // A literal percentage character ("%")
);
$old = getOption('date_format');
if ($old) {
	$new = strtr($old, $strftimeXlate);
	if ($old != $new) {
		setOption('date_format', $new);
	}
}
setOptionDefault('date_format', '%x');

setOptionDefault('use_lock_image', 1);
setOptionDefault('search_user', '');
setOptionDefault('multi_lingual', 0);
setOptionDefault('tagsort', 0);
setOptionDefault('albumimagesort', 'ID');
setOptionDefault('albumimagedirection', 'DESC');
setOptionDefault('cache_full_image', 0);
setOptionDefault('exact_tag_match', 0);
setOptionDefault('IPTC_encoding', 'ISO-8859-1');
setOptionDefault('sharpen_amount', 40);
setOptionDefault('sharpen_radius', 0.5);
setOptionDefault('sharpen_threshold', 3);

// default groups
if (!is_array($groupsdefined)) {
	$groupsdefined = array();
}

$groupobj = NUll;
if (!in_array('administrators', $groupsdefined) || $groupobj = $_authority->getAnAdmin(array('`user`=' => 'administrators', '`valid`=' => 0))) {
	if (!$groupobj) {
		$groupobj = npg_Authority::newAdministrator('administrators', 0);
		$groupsdefined[] = 'administrators';
	}
	$groupobj->setName('group');
	$groupobj->setRights(ALL_RIGHTS);
	$groupobj->set('other_credentials', getAllTranslations('Users with full privileges'));
	$groupobj->setValid(0);
	$groupobj->save();
}
$groupobj = NUll;
if (!in_array('viewers', $groupsdefined) || $groupobj = $_authority->getAnAdmin(array('`user`=' => 'viewers', '`valid`=' => 0))) {
	if (!$groupobj) {
		$groupobj = npg_Authority::newAdministrator('viewers', 0);
		$groupsdefined[] = 'blocked';
	}
	$groupobj->setName('group');
	$groupobj->setRights(NO_RIGHTS | POST_COMMENT_RIGHTS | VIEW_ALL_RIGHTS);
	$groupobj->set('other_credentials', getAllTranslations('Users allowed only to view and comment'));
	$groupobj->setValid(0);
	$groupobj->save();
}
$groupobj = NUll;
if (!in_array('blocked', $groupsdefined) || $groupobj = $_authority->getAnAdmin(array('`user`=' => 'blocked', '`valid`=' => 0))) {
	if (!$groupobj) {
		$groupobj = npg_Authority::newAdministrator('blocked', 0);
		$groupsdefined[] = 'blocked';
	}
	$groupobj->setName('group');
	$groupobj->setRights(0);
	$groupobj->set('other_credentials', getAllTranslations('Banned users'));
	$groupobj->setValid(0);
	$groupobj->save();
}
$groupobj = NUll;
if (!in_array('album managers', $groupsdefined) || $groupobj = $_authority->getAnAdmin(array('`user`=' => 'album managers', '`valid`=' => 0))) {
	if (!$groupobj) {
		$groupobj = npg_Authority::newAdministrator('album managers', 0);
		$groupsdefined[] = 'album managers';
	}
	$groupobj->setName('template');
	$groupobj->setRights(NO_RIGHTS | OVERVIEW_RIGHTS | POST_COMMENT_RIGHTS | VIEW_ALL_RIGHTS | UPLOAD_RIGHTS | COMMENT_RIGHTS | ALBUM_RIGHTS | THEMES_RIGHTS);
	$groupobj->set('other_credentials', getAllTranslations('Managers of one or more albums'));
	$groupobj->setValid(0);
	$groupobj->save();
}
$groupobj = NUll;
if (!in_array('default', $groupsdefined) || $groupobj = $_authority->getAnAdmin(array('`user`=' => 'default', '`valid`=' => 0))) {
	if (!$groupobj) {
		$groupobj = npg_Authority::newAdministrator('default', 0);
		$groupsdefined[] = 'default';
	}
	$groupobj->setName('template');
	$groupobj->setRights(DEFAULT_RIGHTS);
	$groupobj->set('other_credentials', getAllTranslations('Default user settings'));
	$groupobj->setValid(0);
	$groupobj->save();
}
$groupobj = NUll;
if (!in_array('newuser', $groupsdefined) || $groupobj = $_authority->getAnAdmin(array('`user`=' => 'newuser', '`valid`=' => 0))) {
	if (!$groupobj) {
		$groupobj = npg_Authority::newAdministrator('newuser', 0);
		$groupsdefined[] = 'newuser';
	}
	$groupobj->setName('template');
	$groupobj->setRights(NO_RIGHTS);
	$groupobj->set('other_credentials', getAllTranslations('Newly registered and verified users'));
	$groupobj->setValid(0);
	$groupobj->save();
}
setOption('defined_groups', serialize($groupsdefined)); // record that these have been set once (and never again)

setOptionDefault('AlbumThumbSelect', 1);

setOptionDefault('site_email', "netPhotoGraphics@" . $_SERVER['SERVER_NAME']);
setOptionDefault('site_email_name', 'netPhotoGraphics');

setOptionDefault('register_user_notify', 1);
setOptionDefault('CMS_news_label', getAllTranslations('News'));

setOptionDefault('obfuscate_cache', 0);

//	obsolete plugin cleanup.
$sql = 'DELETE FROM ' . prefix('options') . ' WHERE `name` LIKE "tinymce_tinyzenpage%";';
query($sql);
$sql = 'DELETE FROM ' . prefix('options') . ' WHERE `name` LIKE "tinymce4%";';
query($sql);
$sql = 'DELETE FROM ' . prefix('options') . ' WHERE `name` LIKE "zenpage_combinews%";';
query($sql);
$sql = 'DELETE FROM ' . prefix('options') . ' WHERE `name` LIKE "cycle-slideshow_%_slideshow";';
query($sql);
purgeOption('tinyMCEPresent');
purgeOption('enable_ajaxfilemanager');
purgeOption('zenphoto_theme_list');
purgeOption('spam_filter');
purgeOption('site_upgrade_state');
purgeOption('last_update_check');

foreach (array('images_per_page', 'albums_per_page', 'image_size', 'image_use_side', 'thumb_size', 'thumb_crop_width', 'thumb_crop_height', 'thumb_crop', 'thumb_transition') as $option) {
	$sql = 'DELETE FROM ' . prefix('options') . ' WHERE `name`=' . db_quote($option) . ' AND `theme`=""';
	query($sql);
}

foreach (getOptionsLike('logviewed_') as $option => $value) {
	$file = SERVERPATH . '/' . DATA_FOLDER . '/' . str_replace('logviewed_', '', $option) . '.log';
	if (!file_exists($file)) {
		purgeOption($option);
	}
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
$displayErrors = false;

//migrate favorites data
$all = query_full_array('SELECT `id`, `aux` FROM ' . prefix('plugin_storage') . ' WHERE `type`="favoritesHandler" AND `subtype` IS NULL');
foreach ($all as $aux) {
	$instance = getSerializedArray($aux['aux']);
	if (isset($instance[1])) {
		query('UPDATE ' . prefix('plugin_storage') . ' SET `subtype`="named" WHERE `id`=' . $aux['id']);
	}
}

query('DELETE FROM ' . prefix('options') . ' WHERE  `name` ="search_space_is_OR"', false);

if (file_exists(SERVERPATH . '/favicon.ico')) {
	$replace = array(
			'2a479b69ab8479876cb5a7e6384e7a85', //	hash of legacy zenphoto favicon
			'8eac492afff6cbb0d3f1e4b913baa8a3', //	hash of zenphoto20 favicon
			'6221c9f071b8347592701c632ffd85c7' //	hash of low resolution nPG favicon
	);
	if (in_array(md5_file(SERVERPATH . '/favicon.ico'), $replace)) {
		unlink(SERVERPATH . '/favicon.ico');
		copy(CORE_SERVERPATH . 'images/favicon.ico', SERVERPATH . '/favicon.ico');
		setupLog('<span class="logwarning">' . gettext('Site <em>favicon.ico</em> updated.') . '</span>', true);
	}
} else {
	copy(CORE_SERVERPATH . 'images/favicon.ico', SERVERPATH . '/favicon.ico');
}

setOptionDefault('fullsizeimage_watermark', getOption('fullimage_watermark'));

$data = getOption('gallery_data');
if ($data) {
	$data = getSerializedArray($data);
	if (isset($data['Gallery_description'])) {
		$data['Gallery_description'] = getSerializedArray($data['Gallery_description']);
	}
	if (isset($data['gallery_title'])) {
		$data['gallery_title'] = getSerializedArray($data['gallery_title']);
	}
	if (isset($data['unprotected_pages'])) {
		$data['unprotected_pages'] = getSerializedArray($data['unprotected_pages']);
	}
} else {
	$data = array();
}

if (!isset($data['gallery_sortdirection'])) {
	$data['gallery_sortdirection'] = (int) getOption('gallery_sortdirection');
}
if (!isset($data['gallery_sorttype'])) {
	$data['gallery_sorttype'] = getOption('gallery_sorttype');
	if (empty($data['gallery_sorttype'])) {
		$data['gallery_sorttype'] = 'ID';
	}
}
if (!isset($data['gallery_title'])) {
	$data['gallery_title'] = getOption('gallery_title');
	if (is_null($data['gallery_title'])) {
		gettext($str = "Gallery");
		$data['gallery_title'] = gettext("Gallery");
	}
}
if (!isset($data['Gallery_description'])) {
	$data['Gallery_description'] = getOption('Gallery_description');
	if (is_null($data['Gallery_description'])) {
		$data['Gallery_description'] = gettext('You can insert your Gallery description on the Admin Options Gallery tab.');
	}
}
if (!isset($data['gallery_password']))
	$data['gallery_password'] = getOption('gallery_password');
if (!isset($data['gallery_user']))
	$data['gallery_user'] = getOption('gallery_user');
if (!isset($data['gallery_hint']))
	$data['gallery_hint'] = getOption('gallery_hint');
if (!isset($data['copyright']) || empty($data['copyright'])) {
	$text = getOption('default_copyright');
	if (empty($text)) {
		$admin = $_authority->getMasterUser();
		if (!$author = $admin->getName()) {
			$author = $admin->getUser();
		}
		$text = sprintf(gettext('© %1$u : %2$s - %3$s'), date('Y'), FULLHOSTPATH, $author);
	} else {
		purgeOption('default_copyright');
	}
	$data['copyright'] = $text;
}
if (!isset($data['hitcounter'])) {
	$data['hitcounter'] = $result = getOption('Page-Hitcounter-index');
	purgeOption('Page-Hitcounter-index');
}

if (!isset($data['website_title']))
	$data['website_title'] = getOption('website_title');
if (!isset($data['website_url']))
	$data['website_url'] = getOption('website_url');
if (!isset($data['gallery_security'])) {
	$data['gallery_security'] = getOption('gallery_security');
	if (is_null($data['gallery_security'])) {
		$data['gallery_security'] = 'public';
	}
}
if (!isset($data['login_user_field']))
	$data['login_user_field'] = getOption('login_user_field');
if (!isset($data['album_use_new_image_date']))
	$data['album_use_new_image_date'] = getOption('album_use_new_image_date');
if (!isset($data['thumb_select_images']))
	$data['thumb_select_images'] = getOption('thumb_select_images');
if (!isset($data['unprotected_pages']))
	$data['unprotected_pages'] = getOption('unprotected_pages');
if ($data['unprotected_pages']) {
	$unprotected = $data['unprotected_pages'];
} else {
	$unprotected = array('register', 'contact');
}

primeOptions(); // get a fresh start
$optionlist = getOptionsLike('gallery_page_unprotected_');
foreach ($optionlist as $key => $option) {
	if ($option) {
		$name = str_replace('gallery_page_unprotected_', '', $key);
		$unprotected[] = $name;
		purgeOption($key);
	}
}
$unprotected = array_unique($unprotected);

if (!isset($data['album_publish'])) {
	$set = getOption('album_default');
	if (is_null($set))
		$set = 1;
	$data['album_publish'] = $set;
}
if (!isset($data['image_publish'])) {
	$set = getOption('image_default');
	if (is_null($set))
		$set = 1;
	$data['image_publish'] = $set;
}
$data['unprotected_pages'] = $unprotected;
if (!isset($data['image_sorttype'])) {
	$set = getOption('image_sorttype');
	if (is_null($set))
		$set = 'Filename';
	$data['image_sorttype'] = $set;
}
if (!isset($data['image_sortdirection'])) {
	$set = getOption('image_sortdirection');
	if (is_null($set))
		$set = 0;
	$data['image_sorttype'] = $set;
}
setOption('gallery_data', serialize($data));
// purge the old versions of these
foreach ($data as $key => $value) {
	purgeOption($key);
}

$_gallery = new Gallery(); // insure we have the proper options instantiated

setOptionDefault('search_cache_duration', 30);
setOptionDefault('cache_random_search', 1);
setOptionDefault('search_within', 1);

setOptionDefault('debug_log_size', 5000000);

if (array_key_exists('PROCESSING_CONCURRENCY', $_conf_vars)) {
	$_configMutex->lock();
	$_config_contents = @file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE);
	$_config_contents = preg_replace('~\$conf\[\'PROCESSING_CONCURRENCY\'\].*\n~i', '', $_config_contents);
	configFile::store($_config_contents);
	$_configMutex->unlock();
}
purgeOption('PROCESSING_CONCURRENCY');
purgeOption('imageProcessorConcurrency');

if (!array_key_exists('THREAD_CONCURRENCY', $_conf_vars)) {
	$_configMutex->lock();
	$_config_contents = @file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE);
	$_config_contents = configFile::update('THREAD_CONCURRENCY', THREAD_CONCURRENCY, $_config_contents);
	configFile::store($_config_contents);
	$_configMutex->unlock();
}

setOptionDefault('search_album_sort_type', 'title');
setOptionDefault('search_album_sort_direction', '');
setOptionDefault('search_image_sort_type', 'title');
setOptionDefault('search_image_sort_direction', '');
setOptionDefault('search_article_sort_type', 'date');
setOptionDefault('search_article_sort_direction', '');
setOptionDefault('search_page_sort_type', 'title');
setOptionDefault('search_page_sort_direction', '');

query('UPDATE ' . prefix('administrators') . ' SET `passupdate`=' . db_quote(date('Y-m-d H:i:s')) . ' WHERE `valid`>=1 AND `passupdate` IS NULL');
setOptionDefault('image_processor_flooding_protection', 1);
setOptionDefault('codeblock_first_tab', 1);
setOptionDefault('GD_FreeType_Path', USER_PLUGIN_SERVERPATH . 'gd_fonts');

setOptionDefault('theme_head_listparents', 0);
setOptionDefault('theme_head_separator', ' | ');

setOptionDefault('tagsort', 'alpha');
setOptionDefault('languageTagSearch', 1);

$vers = explode('.', NETPHOTOGRAPHICS_VERSION_CONCISE . '.0.0.0');
$npg_version = $vers[0] . '.' . $vers[1] . '.' . $vers[2];
$_languages = i18n::generateLanguageList('all');

$unsupported = $disallow = array();
$disallowd = getOptionsLike('disallow_');

foreach ($disallowd as $key => $option) {
	purgeOption($key);
	if ($option) {
		$lang = str_replace('disallow_', '', $key);
		$disallow[$lang] = $lang;
	}
}
setOptionDefault('locale_disallowed', serialize($disallow));

foreach ($_languages as $language => $dirname) {
	if (!empty($dirname) && $dirname != 'en_US') {
		if (!i18n::setLocale($dirname)) {
			$unsupported[$dirname] = $dirname;
		}
	}
}
setOption('locale_unsupported', serialize($unsupported));
i18n::setupCurrentLocale($_setupCurrentLocale_result);
?>
<p>
<?php
setOption('known_themes', serialize(array())); //	reset known themes
$themes = array_keys($info = $_gallery->getThemes());
localeSort($themes);
echo gettext('Theme setup:') . '<br />';
$displayErrors = $displayErrors || optionCheck($theme_links);
?>
</p>

	<?php
	localeSort($plugins);
	$deprecatedDeleted = getSerializedArray(getOption('deleted_deprecated_plugins'));
	?>
<p>
<?php
echo gettext('Plugin setup:') . '<br />';
$displayErrors = $displayErrors || optionCheck($plugin_links);
?>
</p>
<p>
	<span class = "floatright delayshow" style = "display:none">
		<img src = "<?php echo FULLWEBPATH . '/' . CORE_FOLDER . '/setup/icon.php?icon=0'; ?>" alt = "<?php echo gettext('success'); ?>" height = "16px" width = "16px" /> <?php
	echo gettext('Successful initialization');
	if ($thirdParty) {
		?>
			<img src="<?php echo FULLWEBPATH . '/' . CORE_FOLDER . '/setup/icon.php?icon=1'; ?>" alt="<?php echo gettext('success'); ?>" height="16px" width="16px" /> <?php
		echo gettext('Successful initialization (third party item)');
	}
	?>
		<span id="errornote" style="display:<?php echo $displayErrors ? 'show' : 'none'; ?>"><?php echo CROSS_MARK_RED . ' ' . gettext('Error initializing (click to debug)'); ?></span>
		<?php
		if ($deprecated) {
			?>
			<img src="<?php echo FULLWEBPATH . '/' . CORE_FOLDER . '/setup/icon.php?icon=2'; ?>" alt="<?php echo gettext('deprecated'); ?>" height="16px" width="16px" /> <?php echo gettext('Deprecated'); ?>
			<?php
		}
		?>
	</span>
</p>
<br clear="all">
		<?php
		if ($displayErrors) {
			$autorun = false;
		}
		$userPlugins = array_diff($plugins, $npgPlugins);
		if (!empty($themes) || !empty($userPlugins)) {
			//	There are either un-distributed themes or un-distributed plugins present
			require_once(PLUGIN_SERVERPATH . 'deprecated-functions.php');
			$deprecated = new deprecated_functions();
			$current = array('signature' => sha1(serialize($deprecated->listed_functions)), 'themes' => $themes, 'plugins' => $userPlugins);
			$prior = getSerializedArray(getOption('deprecated_functions_signature'));
			if (!array_key_exists('signature', $prior)) {
				$prior = array('signature' => reset($prior), 'themes' => array(), 'plugins' => array());
			}
			if ($current != $prior) {
				$newThemes = array_diff($themes, $prior['themes']);
				$newPlugins = array_diff($userPlugins, $prior['plugins']);
				if ($current['signature'] != $prior['signature'] || !empty($newThemes) | !empty($newPlugins)) {
					setOption('deprecated_functions_signature', serialize($current));
					enableExtension('deprecated-functions', 900 | CLASS_PLUGIN);
					if (!empty($prior['signature'])) {
						setupLog('<span class="logwarning">' . gettext('There has been a change in function deprecation, Themes, or Plugins. The deprecated-functions plugin has been enabled.') . '</span>', true);
					}
				}
			}
		}

		$compatibilityIs = array('themes' => $themes, 'plugins' => $plugins);
		$compatibilityWas = getSerializedArray(getOption('zenphotoCompatibilityPack_signature'));
		if (empty($compatibilityWas)) {
			$compatibilityWas = $compatibilityIs;
		} else {
			$compatibilityWas = array_merge(array('themes' => array(), 'plugins' => array()), $compatibilityWas);
		}
		$newPlugins = array_diff($compatibilityIs['plugins'], $compatibilityWas['plugins'], $npgPlugins);
		$newThemes = array_diff($compatibilityIs['themes'], $compatibilityWas['themes']);

		if (!empty($newPlugins) || !empty($newThemes)) {

			$themeChange = array_diff($compatibilityIs['themes'], $compatibilityWas['themes']);
			$pluginChange = array_diff($compatibilityIs['plugins'], $compatibilityWas['plugins']);
			if (!empty($themeChange) || !empty($pluginChange)) {
				enableExtension('zenphotoCompatibilityPack', 1 | CLASS_PLUGIN);
				setupLog('<span class="logwarning">' . gettext('There has been a change of themes or plugins. The zenphotoCompatibilityPack plugin has been enabled.') . '</span>', true);
				if (!empty($themeChange)) {
					setupLog('<span class="logwarning">' . sprintf(gettext('New themes: <em>%1$s</em>'), trim(implode(', ', $themeChange), ',')) . '</span>', true);
				}
				if (!empty($pluginChange)) {
					setupLog('<span class="logwarning">' . sprintf(gettext('New plugins: <em>%1$s</em>'), trim(implode(', ', $pluginChange), ',')) . '</span>', true);
				}
			}
		}


		$_gallery->garbageCollect();

		setOption('zenphotoCompatibilityPack_signature', serialize($compatibilityIs));
