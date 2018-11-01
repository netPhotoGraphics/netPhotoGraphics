<?php
// force UTF-8 Ø
zp_apply_filter('theme_head');
?>
<title><?php echo getBareGalleryTitle(); ?></title>
<meta http-equiv="content-type" content="text/html; charset=<?php echo LOCAL_CHARSET; ?>" />
<?php
scriptLoader($_zp_themeroot . '/style/style.css', false);
scriptLoader($_zp_themeroot . '/style/' . getoption('zenfluid_theme') . '.css', false);
scriptLoader($_zp_themeroot . '/style/' . getoption('zenfluid_border') . '.css', false);
scriptLoader($_zp_themeroot . '/fonts/' . getoption('zenfluid_font') . '.css', false);

if (extensionEnabled('themeSwitcher')) {
	if (zp_loggedin(ADMIN_RIGHTS)) {
		scriptLoader($_zp_themeroot . '/style/themeSwitcherWithAdmin.css', false);
	} else {
		scriptLoader($_zp_themeroot . '/style/themeSwitcherNoAdmin.css', false);
	}
}
?>
