<?php
/*
 * Guts of the security options tab
 */
$optionRights = ADMIN_RIGHTS;

function logSecuritySwitch($log) {
	global $_adminCript, $_mutex;
	$oldLogEncryption = getOption($log . '_log_encryption');
	setOption($log . '_log_encryption', $newLogEncryption = (int) isset($_POST[$log . '_log_encryption']));
	if ($oldLogEncryption != $newLogEncryption) {
		$logfile = SERVERPATH . "/" . DATA_FOLDER . '/' . $log . '.log';
		if (file_exists($logfile) && filesize($logfile) > 0) {
			$_mutex->lock();
			$logtext = explode(NEWLINE, file_get_contents($logfile));
			$header = $logtext[0];
			$fields = explode("\t", $header);
			if ($newLogEncryption) {
				$logtext = array_map(array($_adminCript, 'encrypt'), $logtext);
			} else {
				$logtext = array_map(array($_adminCript, 'decrypt'), $logtext);
			}
			if (count($fields) > 1) {
				$logtext[0] = $header; //	restore un-encrypted header
			}
			file_put_contents($logfile, rtrim(implode(NEWLINE, $logtext), NEWLINE) . NEWLINE);
			$_mutex->unlock();
		}
	}
}

function saveOptions() {
	global $_gallery, $_authority, $_config_contents, $_configMutex;
	if (method_exists($_authority, 'handleOptionSave')) {
		$_authority->handleOptionSave(NULL, NULL);
	}

	$_gallery->setUserLogonField(isset($_POST['login_user_field']));
	$_gallery->save();
	setOption('IP_tied_cookies', (int) isset($_POST['IP_tied_cookies']));
	setOption('obfuscate_cache', (int) isset($_POST['obfuscate_cache']));
	setOption('image_processor_flooding_protection', (int) isset($_POST['image_processor_flooding_protection']));

	logSecuritySwitch('security');
	logSecuritySwitch('setup');
	logSecuritySwitch('debug');

	return array("&tab=security", NULL, NULL, NULL, NULL);
}

function getOptionContent() {
	global $_gallery, $_config_contents, $_configMutex, $_authority;
	?>
	<div id="tab_security" class="tabbox">
		<?php
		$authority = new ReflectionClass('_Authority');
		$file = trim(str_replace(SERVERPATH, '', str_replace('\\', '/', $authority->getFileName())), '/');
		echo gettext('Authentication authority: ') . '<strong>' . stripSuffix($file) . '</strong>';
		?>
		<form class="dirtylistening" onReset="setClean('form_options');" id="form_options" action="?action=saveoptions" method="post" autocomplete="off" >
			<?php XSRFToken('saveoptions'); ?>
			<input type="hidden" name="saveoptions" value="security" />
			<p>
				<?php
				applyButton();
				resetButton();
				?>
			</p>
			<br clear="all">
			<div id="columns">
				<table id="npgOptions">
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext('Cookie security') ?></td>
						<td class="option_value">
							<label>
								<input type="checkbox" name="IP_tied_cookies" value="1" <?php checked(1, getOption('IP_tied_cookies')); ?> />
							</label>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext('Tie cookies to the IP address of the browser.'); ?>
									<p class="notebox">
										<?php
										if (!getOption('IP_tied_cookies')) {
											echo ' ' . gettext('<strong>Note</strong>: If your browser does not present a consistent IP address during a session you may not be able to log into your site when this option is enabled.') . ' ';
										}
										echo gettext(' You <strong>WILL</strong> have to login after changing this option.');
										if (!getOption('IP_tied_cookies')) {
											echo ' ' . gettext('If you set the option and cannot login, you will have to restore your database to a point when the option was not set, so you might want to backup your database first.');
										}
										?>
									</p>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext('Obscure cache filenames'); ?></td>
						<td class="option_value">
							<label>
								<input type="checkbox" name="obfuscate_cache" id="obfuscate_cache" value="1" <?php checked(1, getOption('obfuscate_cache')); ?> />
							</label>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext('Cause the filename of cached items to be obscured. This makes it difficult for someone to "guess" the name in a URL.'); ?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext('Image Processor security') ?></td>
						<td class="option_value">
							<label>
								<input type="checkbox" name="image_processor_flooding_protection" value="1" <?php checked(1, getOption('image_processor_flooding_protection')); ?> />
							</label>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php echo gettext('Add a security parameter to image processor URIs to prevent denial of service attacks requesting arbitrary sized images.'); ?>
								</div>
							</span>
						</td>
					</tr>
					<tr class="optionSet">
						<td class="option_name"><?php echo gettext('Log encryption') ?></td>
						<td class="option_value">
							<?php
							if (function_exists('openssl_encrypt')) {
								$disabled = '';
							} else {
								$disabled = ' disabled="disabled"';
							}
							?>
							<label>
								<input type="checkbox" name="security_log_encryption" value="1" <?php
								checked(1, getOption('security_log_encryption'));
								echo $disabled;
								?> />
											 <?php echo gettext('Security log'); ?>
							</label>
							<label>
								<input type="checkbox" name="setup_log_encryption" value="1" <?php
								checked(1, getOption('setup_log_encryption'));
								echo $disabled;
								?> />
											 <?php echo gettext('Setup log'); ?>
							</label>
							<label>
								<input type="checkbox" name="debug_log_encryption" value="1" <?php
								checked(1, getOption('debug_log_encryption'));
								echo $disabled;
								?> />
											 <?php echo gettext('Debug log'); ?>
							</label>
						</td>
						<td class="option_desc">
							<span class="option_info">
								<?php echo INFORMATION_BLUE; ?>
								<div class="option_desc_hidden">
									<?php
									echo gettext('Add encrypts the logs.') . '<p class="notebox">' . gettext('<strong>Note</strong>: Encrypting the debug log is not recommended. See the Version 1.7 release notes for details.');
									if ($disabled) {
										?>
										<p class="notebox">
											<?php
											echo gettext('The <code>php_openssl</code> library needs to be enabled')
											?>
										</p>
										<?php
									}
									?>
								</div>
							</span>
						</td>
					</tr>
					<?php
					if (GALLERY_SECURITY == 'public') {
						$disable = $_gallery->getUser() || getOption('search_user') || getOption('protected_image_user') || getOption('downloadList_user');
						?>
						<tr class="public_gallery">
							<td class="option_name"><?php echo gettext('User name'); ?></td>
							<td class="option_value">
								<label>
									<?php
									if ($disable) {
										?>
										<input type="hidden" name="login_user_field" value="1" />
										<input type="checkbox" name="login_user_field_disabled" id="login_user_field"
													 value="1" checked="checked" disabled="disabled" />
													 <?php
												 } else {
													 ?>
										<input type="checkbox" name="login_user_field" id="login_user_field"
													 value="1" <?php checked('1', $_gallery->getUserLogonField()); ?> />
													 <?php
												 }
												 ?>
								</label>
							</td>
							<td class="option_desc">
								<span class="option_info">
									<?php echo INFORMATION_BLUE; ?>
									<div class="option_desc_hidden">
										<?php
										echo gettext('If enabled guest logon forms will include the <em>User Name</em> field. This allows users to logon from the form.');
										if ($disable) {
											echo '<p class = "notebox">' . gettext('<strong>Note</strong>: This field is required because one or more of the <em>Guest</em> passwords has a user name associated.') . '</p>';
										}
										?>
									</div>
								</span>
							</td>
						</tr>
						<?php
					} else {
						?>
						<input type="hidden" name="login_user_field" id="login_user_field"	value="<?php echo $_gallery->getUserLogonField(); ?>" />
						<?php
					}
					if (method_exists($_authority, 'getOptionsSupported')) {
						$supportedOptions = $_authority->getOptionsSupported();
						if (count($supportedOptions) > 0) {
							?>
							<tr class="optionSet">
								<?php customOptions($_authority, ''); ?>
							</tr>
							<?php
						}
					}
					?>
				</table> <!-- security page table -->
			</div>
			<p>
				<?php
				applyButton();
				resetButton();
				?>
			</p>
			<br clear="all">
		</form>
	</div>
	<!-- end of tab_security div -->
	<?php
}
