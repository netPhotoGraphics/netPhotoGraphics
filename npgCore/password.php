<?php if (!defined('WEBPATH')) die(); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php npgFilters::apply('theme_head'); ?>
		<title><?php echo gettext("Password required"); ?></title>
		<?php scriptLoader(CORE_SERVERPATH . 'admin.css'); ?>
	</head>

	<body>
		<?php printPasswordForm($hint, $show); ?>
		<div id="credit">
			<?php print_SW_Link(); ?>
		</div>
	</body>
</html>
