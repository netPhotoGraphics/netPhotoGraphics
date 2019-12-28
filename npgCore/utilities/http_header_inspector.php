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
					$check_headers = array(
							array(
									'headline' => FULLWEBPATH . '/index.php',
									'headers' => @get_headers(FULLWEBPATH . '/index.php'),
							),
							array(
									'headline' => FULLWEBPATH . '/' . CORE_FOLDER . '/admin.php',
									'headers' => @get_headers(FULLWEBPATH . '/' . CORE_FOLDER . '/admin.php')
							)
					);
					foreach ($check_headers as $check_header) {
						?>
						<h2><?php echo html_encode($check_header['headline']); ?></h2>
						<ul>
							<?php
							if (is_array($check_header)) {
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
