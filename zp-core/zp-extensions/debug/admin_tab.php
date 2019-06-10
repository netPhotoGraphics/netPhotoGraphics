<?php
/**
 * This is the "tokens" upload tab
 *
 * @author Stephen Billard (sbillard)
 *
 * @Copyright 2014 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 * @package plugins/debug
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/admin-globals.php');
admin_securityChecks(DEBUG_RIGHTS, $return = currentRelativeURL());

if (isset($_POST['delete_cookie'])) {
	foreach ($_POST['delete_cookie']as $cookie => $v) {
		clearNPGCookie(postIndexDecode($cookie));
	}
	header('location: ?page=develpment&tab=cookie');
	exit();
}

printAdminHeader('development', @$_GET['tab']);
$subtab = getCurrentTab();

echo "\n</head>";
?>

<body>

	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<div id="container">
				<?php
				npgFilters::apply('admin_note', 'development', $subtab);

				switch ($subtab) {
					case 'phpinfo':
						?>
						<h1>
							<?php
							echo gettext('Your PHP configuration information.');
							?>
						</h1>
						<?php
						break;
					case 'env':
						?>

						<h1>
							<?php echo gettext('Environment variable array'); ?>
						</h1>

						<span class="option_info floatright">
							<?php echo INFORMATION_BLUE; ?>
							<div class="option_desc_hidden">
								<?php echo gettext('Environmental variables will not be provide unless your PHP.ini directive <code>variables_order</code> includes "E". e.g. <code>variables_order = "EGPCS"</code>'); ?>
							</div>
						</span>
						<?php
						break;
					case 'server':
						?>
						<h1>
							<?php
							echo gettext('SERVER array');
							?>
						</h1>
						<?php
						break;
					case 'session':
						?>
						<h1>
							<?php
							echo gettext('SESSION array');
							?>
						</h1>
						<?php
						break;
					case 'http':
						?>
						<h1>
							<?php
							echo ('Http Accept Languages:');
							?>
						</h1>
						<?php
						break;
					default:
					case 'locale':
						?>
						<h1>
							<?php
							echo gettext('Server supported locales:');
							?>
						</h1>
						<?php
						break;
					case 'cookie':
						?>
						<h1>
							<?php
							echo gettext('Site browser cookies found.');
							?>
						</h1>
						<?php
						break;
					case 'filters':
						?>
						<h1>
							<?php
							echo gettext('Defined filters.');
							?>
						</h1>
						<?php
						break;
				}
				?>
				<div class="tabbox">
					<?php
					switch ($subtab) {
						case 'phpinfo':
							//	need to cleanup the phpinfo() output because it thinks it is a page unto itself
							ob_start();
							phpinfo();
							$phpinfo = ob_get_clean();
							@ob_end_flush();


							$i = strpos($phpinfo, '<div class="center">');
							$phpinfo = substr($phpinfo, $i);
							$phpinfo = str_replace('</body></html>', '', $phpinfo);

							file_put_contents(SERVERPATH . '/' . DATA_FOLDER . '/phpinfo.htm', $phpinfo);
							?>
							<style type="text/css">
								pre {margin: 0; font-family: monospace;}
								a:link {color: #009; text-decoration: none;}
								a:hover {text-decoration: underline;}
								table {border-collapse: collapse; border: 0; width: 934px; box-shadow: 1px 2px 3px #ccc;}
								.center {text-align: center;}
								.center table {margin: 1em auto; text-align: left;}
								.center th {text-align: center !important;}
								td, th {border: 1px solid #666; font-size: 75%; vertical-align: baseline; padding: 4px 5px;}
								h1 {font-size: 150%;}
								h2 {font-size: 125%;}
								.p {text-align: left;}
								.e {background-color: #ccf; width: 300px; font-weight: bold;}
								.h {background-color: #99c; font-weight: bold;}
								.v {background-color: #ddd; max-width: 300px; overflow-x: auto; word-wrap: break-word;}
								.v i {color: #999;}
								img {float: right; border: 0;}
								hr {width: 934px; background-color: #ccc; border: 0; height: 1px;}
							</style>
							<?php
							echo $phpinfo;
							break;
						case 'env':
							?>
							<style type="text/css">
								.var {
									vertical-align: top;
								}
								.val{
									word-wrap: break-word;
								}
							</style>
							<?php
							$env = getenv();
							if (empty($env)) {
								echo gettext('There are no environmental variables passed.');
							} else {
								?>
								<dl class="list">
									<?php
									foreach ($env as $var => $val) {
										?>
										<dt class="var"><?php echo $var; ?></dt><dd class="val"><?php echo $val; ?></dd>
										<?php
									}
									?>
								</dl>
								<?php
							}
							break;
						case 'server':
							$server = preg_replace('/^Array\n/', '<pre>', print_r($_SERVER, true)) . '</pre>';
							echo $server;
							break;
						case 'session':
							$session = preg_replace('/^Array\n/', '<pre>', print_r($_SESSION, true)) . '</pre>';
							echo $session;
							break;
						default:
						case 'http':
							$httpaccept = i18n::parseHttpAcceptLanguage();
							if (count($httpaccept) > 0) {
								$accept = reset($httpaccept);
								?>
								<table>
									<tr>
										<th width = 100 align="left">Key</th>
										<?php
										foreach ($accept as $key => $value) {
											?>
											<th width = 100 align="left"><?php echo html_encode($key); ?></th>
											<?php
										}
										?>
									</tr>
									<?php
									foreach ($httpaccept as $key => $accept) {
										?>
										<tr>
											<td width=100 align="left"><?php echo html_encode($key); ?></td>
											<?php
											foreach ($accept as $value) {
												?>
												<td width=100 align="left"><?php echo html_encode($value); ?></td>
												<?php
											}
											?>
										</tr>
										<?php
									}
									?>
								</table>

								<?php
							}
							break;
						case 'locale':
							?>
							<h2><?php echo gettext('The following locals are reported by your server.'); ?></h2>
							<div>
								<?php echo gettext('Languages in boldface have translations.'); ?>
							</div>
							<?php
							if (!extension_loaded('intl')) {
								?>
								<div class="warningbox">
									<?php echo gettext('Note: the PHP Internationalization Functions module is not enabled.'); ?>
								</div>
								<?php
							}
							?>
							<br />
							<?php
							$list = ResourceBundle::getLocales('');
							$support = array();
							foreach ($list as $locale) {
								$parts = explode('_', $locale);
								if (count($parts) > 1) { // only pay attention to the connical xx_YY locales
									$support[$parts[0]]['locales'][] = $locale;
									if (!isset($support[$parts[0]]['text'])) {
										$language = locale::getDisplayName($parts[0]);
										if ($language) {
											$support[$parts[0]]['text'] = $language;
										}
									}
									if (is_dir(CORE_SERVERPATH . 'locale/' . $locale)) {
										$support[$parts[0]]['npgsupport'] = true;
									}
								}
							}
							foreach ($support as $key => $lang) {
								if (!isset($lang['text'])) {
									$support[$key]['text'] = $key;
								}
							}
							$support = sortMultiArray($support, array('text'));
							foreach ($support as $key => $lang) {
								$text = '<em>' . $lang['text'] . '</em>';
								if (isset($lang['npgsupport'])) {
									$text = '<strong>' . $text . '</strong>';
								}
								echo $text . ':&nbsp;&nbsp;' . implode(', ', $lang['locales']) . '<br />';
							}
							break;
						case 'cookie':
							?>

							<form name="cookie_form" class="dirtychyeck" method="post" action="?page=develpment&amp;tab=cookie">
								<table class="compact">
									<?php
									foreach ($_COOKIE as $cookie => $cookiev) {
										?>
										<tr>
											<td><input type="checkbox" name="delete_cookie[<?php echo html_encode(postIndexEncode($cookie)); ?>]" value="1"></td>
											<td><?php echo html_encode($cookie); ?> </td>
											<td><?php echo html_encode(encodeNPGCookie($cookiev)); ?></td>
										</tr>
										<?php
									}
									?>
								</table>
								<p class="buttons">
									<button type="submit">
										<?php echo WASTEBASKET; ?>
										<strong><?php echo gettext("Delete"); ?></strong>
									</button>
									<button type="reset">
										<?php echo CROSS_MARK_RED; ?>
										<strong><?php echo gettext("Reset"); ?></strong>
									</button>
								</p>
							</form>
							<?php
							break;
						case 'filters':
							?>
							<div class="tabbox">
								<?php include (SERVERPATH . '/docs/filterDoc.htm'); ?>
							</div>
							<?php
							break;
					}
					?>


				</div>
			</div>
		</div>
		<?php printAdminFooter(); ?>
	</div>
</body>
</html>
