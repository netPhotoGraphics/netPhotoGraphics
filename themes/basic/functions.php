<?php
npgFilters::register('themeSwitcher_head', 'switcher_head');
npgFilters::register('themeSwitcher_Controllink', 'switcher_controllink');
npgFilters::register('theme_head', 'css_head', 500);

$curdir = getcwd();
chdir(SERVERPATH . "/themes/" . basename(__DIR__) . "/styles");
$filelist = safe_glob('*.css');
$themecolors = array();
foreach ($filelist as $file) {
	$themecolors[] = stripSuffix(filesystemToInternal($file));
}
chdir($curdir);
if (class_exists('themeSwitcher')) {
	$themeColor = themeSwitcher::themeSelection('themeColor', $themecolors);
}

function css_head() {
	global $themecolors, $basic_CSS, $themeColor, $_themeroot;
	if (!$themeColor) {
		$themeColor = getOption('Theme_colors');
	}

	if ($editorConfig = getOption('tinymce_comments')) {
		if (strpos($themeColor, 'dark') !== false) {
			$editorConfig = str_replace('_dark', '', stripSuffix($editorConfig)) . '_dark.php';
			setOption('tinymce_comments', $editorConfig, false);
		}
	}

	$basic_CSS = $_themeroot . '/styles/' . $themeColor . '.css';
	if (!file_exists(SERVERPATH . internalToFilesystem(str_replace(WEBPATH, '', $basic_CSS)))) {
		$basic_CSS = $_themeroot . "/styles/light.css";
	}
}

function iconColor($icon) {
	global $themeColor;
	if (!$themeColor) {
		$themeColor = getOption('Theme_colors');
	}
	if (strpos($themeColor, 'dark') !== false) {
		$icon = stripSuffix($icon) . '-white.png';
	}
	return($icon);
}

function printSoftwareLink() {
	global $themeColor;
	switch ($themeColor) {
		case 'dark':
			$logo = 'blue';
			break;
		case'light':
			$logo = 'light';
			break;
		default:
			$logo = 'sterile';
			break;
	}
	print_SW_Link();
}

function switcher_head($ignore) {
	?>
	<script type="text/javascript">
		
		function switchColors() {
			personality = $('#themeColor').val();
			window.location = '?themeColor=' + personality;
		}
		
	</script>
	<?php
	return $ignore;
}

function switcher_controllink($ignore) {
	global $themecolors;
	$color = getNPGCookie('themeSwitcher_themeColor');
	if (!$color) {
		$color = getOption('Theme_colors');
	}
	?>
	<span title="<?php echo gettext("Default theme color scheme."); ?>">
		<?php echo gettext('Theme Color'); ?>
		<select name="themeColor" id="themeColor" onchange="switchColors();">
			<?php generateListFromArray(array($color), $themecolors, false, false); ?>
		</select>
	</span>
	<?php
	return $ignore;
}

$_current_page_check = 'checkPageValidity'; //	opt-in, standard behavior
?>