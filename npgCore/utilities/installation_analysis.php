<?php
/**
 *
 * Displays the Installation information
 *
 * @author Stephen Billard (sbillard)
 * @package plugins/search_statistics
 */
define('OFFSET_PATH', 3);
require_once(dirname(__DIR__) . '/admin-globals.php');
admin_securityChecks(ADMIN_RIGHTS, currentRelativeURL());

printAdminHeader('overview', 'Installation');

echo '</head>';
?>
<body>
	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<?php npgFilters::apply('admin_note', 'albums', ''); ?>
			<h1><?php echo gettext("Installation information"); ?></h1>

			<div class="overviewboxes">
				<?php
				if (npg_loggedin(ADMIN_RIGHTS)) {
					?>
					<div id="overview_left" class="box overview-section overview-install-info">
						<ul>
							<?php
							if (TEST_RELEASE) {
								$official = gettext('<em>Debug build</em>');
								$debug = explode('-', NETPHOTOGRAPHICS_VERSION);
								$v = $debug[0];
								$debug = explode('_', $debug[1]);
								unset($debug[0]);
								if (!empty($debug)) {
									$debug = array_map('strtolower', $debug);
									$debug = array_map('ucfirst', $debug);
									$official .= ':</strong> ' . implode(', ', $debug) . '<strong>';
								}
							} else {
								$official = gettext('Official build');
							}
							if (npgFunctions::hasPrimaryScripts()) {
								$source = '';
							} else {
								$clone = clonedFrom();
								$official .= ' <em>' . gettext('clone') . '</em>';
								$base = substr(SERVERPATH, 0, strlen(SERVERPATH) - strlen(WEBPATH));
								if (strpos($base, $clone) == 0) {
									$base = substr($clone, strlen($base));
									$link = $base . '/' . CORE_FOLDER . '/admin.php';
									$source = '<a href="' . $link . '">' . $_SERVER['HTTP_HOST'] . $base . '</a>';
								} else {
									$source = $clone;
								}
								$source = '<br />&nbsp;&nbsp;&nbsp;' . sprintf(gettext('source: %s'), $source);
							}

							$graphics_lib = gl_graphicsLibInfo();
							ksort($graphics_lib);
							if (file_exists(SERVERPATH . '/docs/release notes.htm')) {
								?>
								<script>
									<!--
									$(document).ready(function () {
										$(".doc").colorbox({
											close: '<?php echo gettext("close"); ?>',
											maxHeight: "98%",
											innerWidth: '560px'
										});
									});
									//-->
								</script>
								<li>
									<div class="hangng_indent">
										<?php
										$notes = '<br /><a href="' . WEBPATH . '/docs/release%20notes.htm" class="doc" title="' . gettext('release notes') . '">' . gettext('notes') . '</a>';
									} else {
										$notes = '';
									}
									printf(gettext('netPhotoGraphics version <strong>%1$s (%2$s)</strong>'), NETPHOTOGRAPHICS_VERSION_CONCISE, $official);
									echo $notes . $source;
									?>
								</div>
							</li>
							<li>
								<?php
								if (SITE_LOCALE_OPTION) {
									printf(gettext('Current locale setting: <strong>%1$s</strong>'), SITE_LOCALE_OPTION);
								} else {
									echo gettext('<strong>Locale setting has failed</strong>');
								}
								?>
							</li>
							<li>
								<?php
								printf(gettext('Site character set is <strong>%1$s</strong>'), LOCAL_CHARSET);
								?>
							</li>
							<li>
								<?php
								printf(gettext('File system character set is <strong>%1$s</strong>'), FILESYSTEM_CHARSET);
								if (UTF8_IMAGE_URI && FILESYSTEM_CHARSET != 'UTF-8') {
									echo ', ' . gettext('Image URIs are in <strong>UTF-8</strong>');
								}
								?>
							</li>
							<li>
								<?php
								echo gettext('WEB path:') . ' <strong>' . WEBPATH . '</strong>';
								if (isset($_conf_vars['server_protocol']) && $_conf_vars['server_protocol'] == 'https') {
									echo ' (' . gettext('HTTPS connection required') . ')';
								} elseif (getNPGCookie('ssl_state')) {
									echo ' (' . gettext('HTTPS connection') . ')';
								}
								?>
							</li>
							<li>
								<?php
								$themes = $_gallery->getThemes();
								$currenttheme = $_gallery->getCurrentTheme();
								if (array_key_exists($currenttheme, $themes) && isset($themes[$currenttheme]['name'])) {
									$currenttheme = $themes[$currenttheme]['name'];
								}
								printf(gettext('Current gallery theme: <strong>%1$s</strong>'), $currenttheme);
								?>
							</li>
							<li>
								<div class="hangng_indent">
									<?php echo gettext('Server path:') . ' <strong>' . SERVERPATH . '</strong>'; ?>
								</div>
							</li>
							<li>
								<?php
								$permission_names = array(
										0444 => gettext('readonly'),
										0644 => gettext('strict'),
										0664 => gettext('relaxed'),
										0666 => gettext('loose')
								);
								$try = CHMOD_VALUE & 0666 | 4;
								if (array_key_exists($try, $permission_names)) {
									$value = sprintf(gettext('<em>%1$s</em> (<code>0%2$o</code>)'), $permission_names[$try], CHMOD_VALUE);
								} else {
									$value = sprintf(gettext('<em>unknown</em> (<code>%o</code>)'), CHMOD_VALUE);
								}
								echo gettext('File permissions:') . ' <strong>' . $value . '</strong>';
								?>
							</li>
							<li>
								<div class="hangng_indent">
									<?php printf(gettext('Server software: <strong>%1$s</strong>'), html_encode($_SERVER['SERVER_SOFTWARE'])); ?>
								</div>
							</li>
							<li>
								<?php
								printf(gettext('PHP version: <strong>%1$s</strong>'), phpversion());
								?>
							</li>
							<li>
								<div class="hangng_indent">
									<?php echo gettext('PHP Session path:') . ' <strong>' . session_save_path() . '</strong>'; ?>
								</div>
							</li>

							<?php
							$loaded = get_loaded_extensions();
							$loaded = array_flip($loaded);
							$desired = DESIRED_PHP_EXTENSIONS;
							$missing = '';
							foreach ($desired as $module) {
								if (!isset($loaded[$module])) {
									$missing .= '<strong>' . $module . '</strong>, ';
								}
							}
							if (!empty($missing)) {
								?>
								<li>
									<div class="hangng_indent">
										<?php
										printf(gettext('The following desired PHP extensions are not enabled: %s'), rtrim($missing, ', '));
										?>
									</div>
								</li>
								<?php
							}
							?>
							<li>
								<?php
								$memoryLimit = INI_GET('memory_limit');
								printf(gettext('PHP memory limit: <strong>%1$s</strong>; <strong>%2$s</strong> used'), $memoryLimit < 0 ? 'none' : convert_size(parse_size($memoryLimit)), convert_size(memory_get_peak_usage(), 1));
								?>
							</li>

							<li>
								<?php
								if (class_exists('Collator')) {
									echo gettext('PHP Collaror class will be used for localized string sorting.');
								} else {
									echo gettext('PHP Collaror class is not present. Localized string sorting is not available.');
								}
								?>
							</li>
							<?php
							if (TEST_RELEASE) {
								?>
								<li>
									<div class="hangng_indent">
										<?php
										$erToTxt = array(E_ERROR => 'E_ERROR',
												E_WARNING => 'E_WARNING',
												E_PARSE => 'E_PARSE',
												E_NOTICE => 'E_NOTICE',
												E_CORE_ERROR => 'E_CORE_ERROR',
												E_CORE_WARNING => 'E_CORE_WARNING',
												E_COMPILE_ERROR => 'E_COMPILE_ERROR',
												E_COMPILE_WARNING => 'E_COMPILE_WARNING',
												E_USER_NOTICE => 'E_USER_NOTICE',
												E_USER_WARNING => 'E_USER_WARNING',
												E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
												E_DEPRECATED => 'E_DEPRECATED',
												E_USER_DEPRECATED => 'E_USER_DEPRECATED'
										);

										$reporting = error_reporting();
										$text = array();

										if ((($reporting | E_NOTICE ) & E_ALL) == E_ALL) {
											$t = 'E_ALL';
											$reporting = $reporting ^ E_ALL;
											if ($reporting & E_NOTICE) {
												$t .= ' ^ E_NOTICE';
												$reporting = $reporting ^ E_NOTICE;
											}
											$text[] = $t;
										} else {
											if (($reporting & E_ALL) == E_ALL) {
												$text[] = 'E_ALL';
												$reporting = $reporting ^ E_ALL;
											}
										}

										foreach ($erToTxt as $er => $name) {
											if ($reporting & $er) {
												$text[] = $name;
											}
										}
										printf(gettext('PHP Error reporting: <strong>%s</strong> '), implode(' | ', $text));
										if (filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN)) {
											?>
											<a title="<?php echo gettext('PHP error messages may be displayed on WEB pages. This may disclose site sensitive information.'); ?>"><?php echo gettext('<em>display_errors</em> is <strong>On</strong>') ?></a>
											<?php
										} else {
											echo gettext('<em>display_errors</em> is <strong>Off</strong>');
										}
										?>
									</div>
								</li>
								<?php
							}
							?>
							<li>
								<?php printf(gettext("Graphics support: <strong>%s</strong>"), $graphics_lib['Library_desc']); ?>
								<div class="indent">
									<?php
									unset($graphics_lib['Library']);
									unset($graphics_lib['Library_desc']);
									foreach ($graphics_lib as $key => $type) {
										if (!$type) {
											unset($graphics_lib[$key]);
										}
									}
									printf(gettext('supporting: %s'), '<em>' . strtolower(implode(', ', array_keys($graphics_lib))) . '</em>');
									?>
								</div>
							</li>
							<?php
							if (TEST_RELEASE) {
								?>
								<li>
									<div class="hangng_indent">
										<?php
										echo gettext('Image handlers') . ':<br />';
										$handlers = array();
										ksort($_images_classes, SORT_NATURAL | SORT_FLAG_CASE);
										foreach ($_images_classes as $suffix => $handler) {
											$handlers[$handler][] = $suffix;
										}
										ksort($handlers, SORT_NATURAL | SORT_FLAG_CASE);
										foreach ($handlers as $handler => $suffixes) {
											echo '<em>' . $handler . '</em>: ' . implode(', ', $suffixes) . '<br />';
										}
										?>
									</div>
								</li>
								<?php
							}
							?>
							<li>
								<?php
								$dbsoftware = db_software();
								list($user, $pass) = selectDBuser($_conf_vars);
								printf(gettext('%1$s version: <strong>%2$s</strong>'), $dbsoftware['application'], $dbsoftware['version']);
								echo '&nbsp;&nbsp;';
								$used = query_single_row("SELECT " . db_quote($user) .
												" user, COUNT(1) Connections FROM (SELECT user " .
												db_quote($user) .
												"FROM information_schema.processlist) A GROUP BY "
												. db_quote($user) .
												" WITH ROLLUP;");
								printf(ngettext('%1$d of %2$d connection used', '%1$d of %2$d connections used', MySQL_CONNECTIONS), $used['Connections'], MySQL_CONNECTIONS);
								?>
							</li>
							<li>
								<?php
								$host = $_conf_vars['mysql_host'];
								if (isset($_conf_vars['mysql_port']) && $_conf_vars['mysql_port']) {
									$host .= ':' . $_conf_vars['mysql_port'];
								}
								if (isset($_conf_vars['mysql_socket']) && $_conf_vars['mysql_socket']) {
									$host .= '[' . $_conf_vars['mysql_socket'] . ']';
								}
								printf(gettext('Database host <strong>%1$s</strong>'), $host);
								?>
							</li>
							<li>
								<?php
								printf(gettext('Database name: <strong>%1$s</strong>; '), db_name());
								?>
							</li>
							<li>
								<?php
								$prefix = trim(prefix(), '`');
								if (empty($prefix)) {
									$prefix = '<em>' . gettext('none') . '</em>';
								} else {
									$prefix = '<strong>' . $prefix . '</strong>';
								}
								echo sprintf(gettext('Table prefix: %1$s'), $prefix);
								?>
							</li>
							<li>
								<?php
								$authority = new ReflectionClass(get_class($_authority));
								$file = replaceScriptPath($authority->getFileName());
								echo gettext('Authentication authority: ') . '<strong>' . $file . '</strong>';
								?>
							</li>
							<li>
								<?php
								if (isset($_spamFilter)) {
									$filter = $_spamFilter->displayName();
								} else {
									$filter = '<em>' . gettext('No spam filter configured') . '</em>';
								}
								printf(gettext('Spam filter: %s'), $filter)
								?>
							</li>
							<?php
							if ($_captcha) {
								?>
								<li><?php printf(gettext('CAPTCHA generator: <strong>%s</strong>'), ($_captcha->name) ? $_captcha->name : gettext('none')) ?></li>
								<?php
							}
							npgFilters::apply('installation_overview');
							if (!npgFilters::has_filter('sendmail')) {
								?>
								<li style="color:RED"><?php echo gettext('There is no mail handler configured!'); ?></li>
								<?php
							}
							?>
						</ul>

						<?php
						require_once(CORE_SERVERPATH . 'template-filters.php');
						$plugins = array_keys(getEnabledPlugins());
						$filters = $_filters;
						$c = count($plugins);
						?>
					</div>
					<div class="box overview-section overview-install-info">
						<div class="overview-list-h3">
							<h3>
								<?php printf(ngettext("%u active plugin:", "%u active plugins:", $c), $c); ?>
							</h3>
						</div>
						<div class="overview_list">
							<ul class="plugins">
								<?php
								if ($c > 0) {
									localeSort($plugins);
									foreach ($plugins as $extension) {
										$pluginStream = file_get_contents(getPlugin($extension . '.php'));
										$plugin_version = '';
										if ($str = isolate('$plugin_version', $pluginStream)) {
											try {
												eval($str);
											} catch (Exception $e) {

											}
										}
										if ($plugin_version) {
											$version = ' v' . $plugin_version;
										} else {
											$version = '';
										}
										$plugin_is_filter = 1;
										if ($str = isolate('$plugin_is_filter', $pluginStream)) {
											try {
												eval($str);
											} catch (Exception $e) {

											}
										}
										echo "<li>" . $extension . $version . "</li>";
										preg_match_all('|npgFilters::register\s*\((.+?)\)\s*?;|', $pluginStream, $matches);
										foreach ($matches[1] as $paramsstr) {
											$params = explode(',', $paramsstr);
											if (array_key_exists(2, $params)) {
												$priority = (int) $params[2];
											} else {
												$priority = $plugin_is_filter & PLUGIN_PRIORITY;
											}
											$filter = unQuote(trim($params[0]));
											if ($filter != $params[0]) { //	don't know what to do if not a string constant
												$function = unQuote(trim($params[1]));
												$filters[$filter][$priority][$function] = array('function' => $function, 'script' => $extension . '.php');
											}
										}
									}
								} else {
									echo '<li>' . gettext('<em>none</em>') . '</li>';
								}
								?>
							</ul>
						</div><!-- plugins -->
					</div>
					<div class="box overview-section overview-install-info">
						<?php
						$c = count($filters);
						?>
						<div class="overview-list-h3">
							<h3>
								<?php printf(ngettext("%u filter:", "%u filters:", $c), $c); ?>
							</h3>
						</div>
						<div class="overview_list">
							<ul class="plugins">
								<?php
								if ($c > 0) {
									ksort($filters, SORT_LOCALE_STRING);
									foreach ($filters as $filter => $array_of_priority) {
										krsort($array_of_priority);
										?>
										<li>
											<em><?php echo $filter; ?></em>
											<ul class="filters">
												<?php
												foreach ($array_of_priority as $priority => $array_of_filters) {
													foreach ($array_of_filters as $data) {
														?>
														<li><em><?php echo $priority; ?></em>: <?php echo $data['script'] ?> =&gt; <?php echo $data['function'] ?></em></li>
														<?php
													}
												}
												?>
											</ul>
										</li>
										<?php
									}
								} else {
									?>
									<li><?php echo gettext('<em>none</em>'); ?></li>
									<?php
								}
								?>
							</ul>
						</div><!-- filters -->
					</div><!-- overview-info -->
					<?php npgFilters::apply('installation_information'); ?>
					<br class="clearall" />
					<?php
				}
				?>

			</div>
		</div>
		<?php printAdminFooter(); ?>
	</div>
</body>
<script>
									var height = Math.floor(($('#overview_left').height() - $('.overview-list-h3').height() * 2) / 2 - 7);
									$('.overview_list').height(height);
</script>

<?php
echo "</html>";
?>
