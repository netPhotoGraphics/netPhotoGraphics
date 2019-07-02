<?php
/**
 * This is the "files" upload tab
 *
 * @package plugins/deprecated-functions
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/admin-globals.php');
require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/deprecated-functions.php');

admin_securityChecks(DEBUG_RIGHTS, $return = currentRelativeURL());
$subtab = getCurrentTab();
printAdminHeader('development', $subtab);

echo "\n</head>";
?>

<body>

	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<div id="container">
				<?php npgFilters::apply('admin_note', 'development', ''); ?>
				<h1>
					<?php
					echo gettext('Deprecated Functions');
					?>
				</h1>
				<div class="tabbox">
					<?php
					$list = array();
					$deprecated = new deprecated_functions();
					$listed = $deprecated->listed_functions;
					if (empty($listed)) {
						?>
						<p>
							<?php echo gettext('There are no deprecated functions at this time.'); ?>
						</p>
						<?php
					} else {
						?>
						<p>
							<?php
							echo gettext('Functions flagged with an "*" are class methods. Ones flagged with "+" have deprecated parameters.');
							foreach ($listed as $details) {
								switch ($details['class']) {
									case 'static':
										$class = '*';
										break;
									case 'public static':
										$class = '+';
										break;
									case 'final static':
										$class = '*+';
										break;
									default:
										$class = '';
										break;
								}
								$list[$details['since']][$details['plugin']][] = $details['function'] . $class;
								uksort($list, 'version_compare');
								$list = array_reverse($list);
							}
							?>
						<ul style="list-style-type: none;">
							<?php
							foreach ($list as $release => $plugins) {
								?>
								<li>
									<h1><?php echo $release; ?></h1>
									<ul style="list-style-type: none;">
										<?php
										ksort($plugins, SORT_NATURAL | SORT_FLAG_CASE);
										foreach ($plugins as $plugin => $functions) {
											natcasesort($functions);
											if (empty($plugin) || $plugin == 'core')
												$plugin = "<em>core</em>";
											?>
											<li>
												<h2><?php echo $plugin; ?></h2>
												<ul style="list-style-type: none;">
													<?php
													foreach ($functions as $function) {
														?>
														<li>
															<?php echo $function; ?>
														</li>
														<?php
													}
													?>
												</ul>
												<br />
											</li>
											<?php
										}
										?>
									</ul>
									<br />
								</li>
								<?php
							}
							?>
						</ul>
						<?php
					}
					?>
				</div>
			</div>
		</div>
		<?php printAdminFooter(); ?>
	</div>
</body>
</html>
