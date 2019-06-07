<?php
/**
 * user_groups log--tabs
 *
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 */
define('OFFSET_PATH', 1);
require_once(dirname(dirname(__FILE__)) . '/admin-globals.php');

admin_securityChecks(ADMIN_RIGHTS, currentRelativeURL());

$logtabs = $_admin_menu['logs']['subtabs'];
if (isset($_GET['tab']) && isset($logtabs[$_GET['tab']])) {
	$logname = $subtab = $_GET['tab'];
} else {
	$logname = $subtab = $_admin_menu['logs']['default'];
}
$baseName = preg_replace('~-\d*$~', '', $logname);

if (getOption($baseName . '_log_encryption')) {
	$_logCrypt = $_adminCript;
} else {
	$_logCrypt = NULL;
}

if (isset($_GET['action'])) {
	$action = sanitize($_GET['action'], 3);
	$what = sanitize($_GET['filename'], 3);
	$file = SERVERPATH . '/' . DATA_FOLDER . '/' . $what . '.log';

	if (npgFilters::apply('admin_log_actions', true, $file, $action)) {
		switch ($action) {
			case 'clear_log':
				XSRFdefender($action, $what);
				$_mutex->lock();
				$f = fopen($file, 'w');
				if (@ftruncate($f, 0)) {
					$class = 'messagebox';
					$result = sprintf(gettext('%s log was emptied.'), $what);
				} else {
					$class = 'errorbox';
					$result = sprintf(gettext('%s log could not be emptied.'), $what);
				}
				fclose($f);
				@chmod($file, LOGS_MOD);
				clearstatcache();
				$_mutex->unlock();
				if (basename($file) == 'security.log') {
					npgFilters::apply('admin_log_actions', true, $file, $action); // have to record the fact
				}
				break;
			case 'delete_log':
				XSRFdefender($action, $what);
				purgeOption('logviewed_' . $what);
				$_mutex->lock();
				@chmod($file, 0777);
				if (@unlink($file)) {
					$class = 'messagebox';
					$result = sprintf(gettext('%s log was removed.'), $what);
				} else {
					$class = 'errorbox';
					$result = sprintf(gettext('%s log could not be removed.'), $what);
				}
				clearstatcache();
				$_mutex->unlock();
				if (basename($file) == 'security.log') {
					npgFilters::apply('admin_log_actions', true, $file, $action); // have to record the fact
				}
				header('location: ' . getAdminLink('admin-tabs/logs.php'));
				exit();
			case 'download_log':
				XSRFdefender($action, $what);
				if ($_logCrypt) { //	an encrypted file is not so useful outside of netPhotoGraphics
					$logtext = explode(NEWLINE, file_get_contents($file));
					$logtextclear = array_map(array($_logCrypt, 'decrypt'), $logtext);
					$file = SERVERPATH . '/' . DATA_FOLDER . '/' . basename($file);
					file_put_contents($file, implode("\n", $logtextclear));
					putZip($what . '.zip', $file);
					unlink($file);
				} else {
					putZip($what . '.zip', $file);
				}
				exit();
		}
	}
}


printAdminHeader('logs', $subtab);

$_GET['tab'] = $subtab;
echo "\n</head>";
?>

<body>

	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php
		printTabs();

		setOption('logviewed_' . $subtab, time());
		foreach ($logtabs as $text => $link) {
			preg_match('~tab=(.*?)(&|$)~', $link, $matches);
			if (isset($matches[1])) {
				if ($matches[1] == $subtab) {
					$logname = $text;
					break;
				}
			}
		}
		?>
		<div id="content">
			<?php npgFilters::apply('admin_note', 'logs', $subtab); ?>
			<h1><?php echo ucfirst($logname); ?></h1>

			<div id="container">
				<?php
				if ($subtab) {
					$logfiletext = str_replace('_', ' ', $subtab);
					$logfiletext = strtoupper(substr($logfiletext, 0, 1)) . substr($logfiletext, 1);
					$logfile = SERVERPATH . "/" . DATA_FOLDER . '/' . $subtab . '.log';
					if (file_exists($logfile) && filesize($logfile) > 0) {
						$logtext = explode(NEWLINE, file_get_contents($logfile));
						$header = $logtext[0];
						if ($_logCrypt) {
							$logtext = array_map(array($_logCrypt, 'decrypt'), $logtext);
						}
					} else {
						$logtext = array();
					}
					?>

					<!-- A log -->
					<div class="tabbox">
						<?php
						if (isset($result)) {
							?>
							<div class="<?php echo $class; ?> fade-message">
								<h2><?php echo $result; ?></h2>
							</div>
							<?php
						}
						?>
						<form method="post" action="<?php echo getAdminLink('admin-tabs/logs.php'); ?>?action=change_size&amp;page=logs&amp;tab=<?php echo html_encode($subtab) . '&amp;filename=' . html_encode($subtab); ?>" >
							<span class="button buttons">
								<a href="<?php echo getAdminLink('admin-tabs/logs.php') . '?action=delete_log&amp;page=logs&amp;tab=' . html_encode($subtab) . '&amp;filename=' . html_encode($subtab); ?>&amp;XSRFToken=<?php echo getXSRFToken('delete_log', $subtab); ?>">
									<?php echo WASTEBASKET; ?>
									<?php echo gettext('Delete'); ?></a>
							</span>
							<?php
							if (!empty($logtext)) {
								?>
								<span class="button buttons">
									<a href="<?php echo getAdminLink('admin-tabs/logs.php') . '?action=clear_log&amp;page=logs&amp;tab=' . html_encode($subtab) . '&amp;filename=' . html_encode($subtab); ?>&amp;XSRFToken=<?php echo getXSRFToken('clear_log', $subtab); ?>">
										<?php echo CLOCKWISE_OPEN_CIRCLE_ARROW_GREEN; ?>
										<?php echo gettext('Reset'); ?>
									</a>
								</span>
								<span class="button buttons">
									<a href="<?php echo getAdminLink('admin-tabs/logs.php') . '?action=download_log&amp;page=logs&amp;tab=' . html_encode($subtab) . '&amp;filename=' . html_encode($subtab); ?>&amp;XSRFToken=<?php echo getXSRFToken('download_log', $subtab); ?>">
										<?php echo ARROW_DOWN_GREEN; ?>
										<?php echo gettext('Download'); ?>
									</a>
								</span>
								<?php
							}
							?>
						</form>
						<br class="clearall">
						<br />
						<blockquote class="logtext">
							<?php
							if (!empty($logtext)) {
								$fields = explode("\t", $header);
								if (count($fields) > 1) { // there is a header row, display in a table
									unset($logtext[0]); //	delete the header
									?>
									<table id="log_table">
										<thead>
											<tr>
												<?php
												foreach ($fields as $field) {
													?>
													<th>
														<span class="nowrap"><?php echo $field; ?></span>
													</th>
													<?php
												}
												?>
											</tr>
										</thead>
										<tbody>
											<?php
											foreach ($logtext as $line) {
												?>
												<tr>
													<?php
													$fields = explode("\t", trim($line));
													foreach ($fields as $key => $field) {
														?>
														<td>
															<?php
															if ($field) {
																?>
																<span class="nowrap"><?php echo html_encodeTagged($field); ?></span>
																<?php
															}
															?>
														</td>
														<?php
													}
													?>
												</tr>
												<?php
											}
											?>
										</tbody>
									</table>
									<?php scriptLoader(CORE_SERVERPATH . 'js/jquery.stickytableheaders.min.js'); ?>
									<script>
										$(function () {
											$('#log_table').stickyTableHeaders({scrollableArea: $('.logtext'), cacheHeaderHeight: true});
										});
									</script>
									<?php
								} else {
									foreach ($logtext as $line) {
										if ($line) {
											$line = str_replace("\t", '&nbsp;&nbsp;', $line);
											?>
											<p>
												<span class="nowrap">
													<?php
													echo html_encodeTagged($line);
													?>
												</span>
											</p>
											<?php
										}
									}
								}
							}
							?>
							<span id="bottom"></span>
						</blockquote>
					</div>
					<?php
				} else {
					?>
					<h2><?php echo gettext("There are no logs to view."); ?></h2>
					<?php
				}
				?>
			</div>
		</div>
		<?php printAdminFooter(); ?>
	</div>

	<script type="text/javascript">
		window.addEventListener('load', function () {
			$('.logtext').scrollTo('#bottom');
		}, false);
	</script>
	<?php
	// to fool the validator
	echo "\n</body>";
	echo "\n</html>";
	?>
