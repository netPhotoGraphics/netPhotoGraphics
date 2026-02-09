<?php
/**
 * Displays which HTTP headers your site sends
 * @author Malte Müller (acrylian>
 * @package admin
 */
define('OFFSET_PATH', 3);
require_once(dirname(__DIR__) . '/admin-globals.php');

admin_securityChecks(NULL, currentRelativeURL());

printAdminHeader('overview', 'http_header_inspector');
?>
</head>
<body>
	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<div class="tabbox">
				<h1><?php echo (gettext('HTTP header inspector')); ?></h1>
				<?php
				if (in_array(PROTOCOL, stream_get_wrappers())) {
					$er_reporting = error_reporting(); //	supress any error reports
					error_reporting(0);
					$check_headers = array(
							array(
									'headline' => FULLWEBPATH . '/index.php',
									'headers' => get_headers(FULLWEBPATH . '/index.php'),
							),
							array(
									'headline' => FULLWEBPATH . '/' . CORE_FOLDER . '/admin.php',
									'headers' => get_headers(FULLWEBPATH . '/' . CORE_FOLDER . '/admin.php')
							)
					);
					error_reporting($er_reporting);
					foreach ($check_headers as $check_header) {
						?>
						<h2><?php echo html_encode($check_header['headline']); ?></h2>
						<ul>
							<?php
							if ($check_header['headers']) {
								foreach ($check_header['headers'] as $header) {
									echo '<li>' . $header . '</li>';
								}
							} else {
								echo gettext('No headers were returned.');
							}
							?>
						</ul>
						<hr>
						<?php
					}
				} else {
					echo '<p class="notebox">' . sprintf(gettext("Your server does not support getting header info via %s"), PROTOCOL) . '</p>';
				}
				?>
			</div>
		</div><!-- content -->
	</div><!-- main -->
	<?php printAdminFooter(); ?>
</body>
</html>
