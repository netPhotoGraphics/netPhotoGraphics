<?php
// force UTF-8 Ø
npgFilters::apply('theme_head');
?>
<title><?php echo getBareGalleryTitle(); ?></title>
<meta http-equiv="content-type" content="text/html; charset=<?php echo LOCAL_CHARSET; ?>" />
<?php
scriptLoader($_themeroot . '/style/style.css');
scriptLoader($_themeroot . '/style/' . getoption('zenfluid_theme') . '.css');
scriptLoader($_themeroot . '/style/' . getoption('zenfluid_border') . '.css');
scriptLoader($_themeroot . '/fonts/' . getoption('zenfluid_font') . '.css');

if (extensionEnabled('themeSwitcher')) {
	if (npg_loggedin(ADMIN_RIGHTS)) {
		scriptLoader($_themeroot . '/style/themeSwitcherWithAdmin.css');
	} else {
		scriptLoader($_themeroot . '/style/themeSwitcherNoAdmin.css');
	}
}
?>
