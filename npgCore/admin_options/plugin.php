<?php
/*
 * Guts of the plugin options tab
 */
$optionRights = ADMIN_RIGHTS;

define('PLUGINS_STEP', 20);
if (isset($_GET['selection'])) {
	define('PLUGINS_PER_PAGE', max(1, sanitize_numeric($_GET['selection'])));
	setNPGCookie('pluginsTab_pluginCount', PLUGINS_PER_PAGE, 3600 * 24 * 365 * 10);
} else {
	if ($s = sanitize_numeric(getNPGCookie('pluginsTab_pluginCount'))) {
		define('PLUGINS_PER_PAGE', $s);
	} else {
		define('PLUGINS_PER_PAGE', 10);
	}
}

function saveOptions() {
	global $_gallery;

	$notify = $returntab = NULL;
	if (isset($_GET['single'])) {
		$returntab = "&tab=plugin&single=" . sanitize($_GET['single']);
	} else {
		$returntab = "&tab=plugin&subpage=$subpage";
	}

	if (!isset($_POST['checkForPostTruncation'])) {
		// all plugin options are handled by the custom option code.
		$notify = '?post_error';
	}

	return array($returntab, $notify, NULL, NULL, NULL);
}

function getOptionContent() {
	global $_gallery, $pluginlist;

	if (isset($_GET['subpage'])) {
		$subpage = sanitize_numeric($_GET['subpage']);
	} else {
		if (isset($_POST['subpage'])) {
			$subpage = sanitize_numeric($_POST['subpage']);
		} else {
			$subpage = 0;
		}
	}

	if (npg_loggedin(ADMIN_RIGHTS)) {
		if (isset($_GET['single'])) {
			$showExtension = sanitize($_GET['single']);
			$_GET['show-' . $showExtension] = true;
		} else {
			$showExtension = NULL;
		}

		$_plugin_count = 0;

		$plugins = array();
		$list = array_keys(getPluginFiles('*.php'));
		localeSort($list);
		foreach ($list as $extension) {
			$option_interface = NULL;
			$path = getPlugin($extension . '.php');
			$pluginStream = file_get_contents($path);
			$str = isolate('$option_interface', $pluginStream);
			if (false !== $str) {
				$plugins[] = $extension;
			}
		}
		$rangeset = getPageSelector($plugins, PLUGINS_PER_PAGE);

		if (isset($_GET['single'])) {
			$single = sanitize($_GET['single']);
			$list = $plugins;
			$plugins = array($showExtension);
			if (isset($_GET['from'])) {
				$backlink = getAdminLink('admin-tabs/plugins.php') . '?page=plugins&tab=' . $_GET['from'];
			} else {
				$backlink = getAdminLink('admin-tabs/options.php') . '?page=options&tab=plugin';
			}
			if (isset($_GET['subpage']) && $_GET['subpage']) {
				$backlink .= '&subpage=' . $_GET['subpage'];
			}
		} else {
			$single = false;
			$plugins = array_slice($plugins, $subpage * PLUGINS_PER_PAGE, PLUGINS_PER_PAGE);
		}
		?>
		<div id="tab_plugin" class="tabbox">
			<script type="text/javascript">

				var optionholder = new Array();

			</script>

			<?php
			if (!$showExtension) {
				$allplugincount = count($pluginlist);
				$numsteps = ceil(min(100, $allplugincount) / PLUGINS_STEP);
				if ($numsteps) {
					?>
					<?php
					$steps = array();
					for ($i = 1; $i <= $numsteps; $i++) {
						$steps[] = $i * PLUGINS_STEP;
					}
					printEditDropdown('pluginOptionInfo', $steps, PLUGINS_PER_PAGE, '&amp;tab=plugin');
				}
			}
			?>

			<form class="dirtylistening" onReset="setClean('form_options');" id="form_options" action="?action=saveoptions<?php if (isset($_GET['single'])) echo '&amp;single=' . $showExtension; ?>" method="post" autocomplete="off" >
				<?php XSRFToken('saveoptions'); ?>
				<input type="hidden" name="saveoptions" value="plugin" />
				<input type="hidden" name="subpage" value="<?php echo $subpage; ?>" />
				<table>
					<?php
					if (!$showExtension) {
						?>
						<tr>
							<th style="text-align:left">
							</th>
							<th style="text-align:right; padding-right: 10px;">
								<?php printPageSelector($subpage, $rangeset, 'admin-tabs/options.php', array('page' => 'options', 'tab' => 'plugin')); ?>
							</th>
							<th>

							</th>
						</tr>
						<?php
					}
					foreach ($plugins as $extension) {
						$option_interface = NULL;
						$enabled = extensionEnabled($extension);
						$path = getPlugin($extension . '.php');
						if (!empty($path)) {
							$key = array_search($extension, $list);
							if ($key > 0) {
								$prev = $list[$key - 1];
							} else {
								$prev = NULL;
							}
							if ($key + 1 >= count($list)) {
								$next = NULL;
							} else {
								$next = $list[$key + 1];
							}

							$pluginStream = file_get_contents($path);
							if ($str = isolate('$plugin_description', $pluginStream)) {
								if (false === eval($str)) {
									$plugin_description = '';
								}
							} else {
								$plugin_description = '';
							}

							$str = isolate('$option_interface', $pluginStream);
							if (false !== $str) {
								require_once($path);
								if (preg_match('/\s*=\s*new\s(.*)\(/i', $str)) {
									eval($str);
									$warn = gettext('<strong>Note:</strong> Instantiating the option interface within the plugin may cause performance issues. You should instead set <code>$option_interface</code> to the name of the class as a string.');
								} else {
									eval($str);
									$option_interface = new $option_interface;
									$warn = '';
								}
							}
							if (!empty($option_interface)) {
								$_plugin_count++;
								if ($single) {
									?>
									<tr>
										<td colspan="100%">
											<p class="padded">
												<?php
												if ($prev) {
													?>
													<a href="?page=options&amp;tab=plugin&amp;single=<?php echo urlencode($prev); ?>"><?php echo $prev; ?></a>
													<?php
												}
												if ($next) {
													?>
													<span class="floatright" >
														<a href="?page=options&amp;tab=plugin&amp;single=<?php echo urlencode($next); ?>"><?php echo $next; ?></a>
													</span>
													<?php
												}
												?>
											</p>
										</td>
									</tr>

									<tr>
										<td colspan="100%">
											<p>
												<?php
												backButton(array('buttonLink' => $backlink));
												applyButton();
												resetButton();
												?>
												<br /><br /><br />
											</p>
										</td>
									</tr>
									<?php
								}
								?>




								<!-- <?php echo $extension; ?> -->
								<tr>
									<td class="option_name<?php if ($showExtension) echo ' option_shaded'; ?>">
										<span id="<?php echo $extension; ?>">
											<?php
											if ($showExtension) {
												echo $extension;
												if (!$enabled) {
													?>
													<a title="<?php echo gettext('The plugin is not enabled'); ?>">
														<?php echo WARNING_SIGN_ORANGE; ?>
													</a>
													<?php
												}
											} else {
												$optionlink = getAdminLink('admin-tabs/options.php') . '?page=options&amp;tab=plugin&amp;subpage=' . $subpage . '&amp;single=' . html_encode($extension);
												?>
												<span class="icons">
													<a href="<?php echo $optionlink; ?>" title="<?php printf(gettext("Change %s options"), html_encode($extension)); ?>">
														<span<?php if (!$enabled) echo ' style="color: orange"'; ?>>
															<?php echo OPTIONS_ICON; ?>
														</span>
														<?php echo $extension; ?>
													</a>
												</span>
												<?php
											}
											if ($warn) {
												echo EXCLAMATION_RED;
											}
											?>
										</span>
									</td>
									<td class="option_value<?php if ($showExtension) echo ' option_shaded'; ?>">
										<?php echo $plugin_description; ?>
									</td>
								</tr>
								<?php
								if ($warn) {
									?>
									<tr style="display:none" class="pluginextrahide">
										<td colspan="100%">
											<p class="notebox" ><?php echo $warn; ?></p>
										</td>
									</tr>
									<?php
								}
								if ($showExtension) {
									$supportedOptions = $option_interface->getOptionsSupported();
									if (count($supportedOptions) > 0) {
										customOptions($option_interface, '', NULL, false, $supportedOptions, NULL, NULL, $extension);
									}
								}
							}
						}
					}
					if ($_plugin_count == 0) {
						?>
						<tr>
							<td style="padding: 0;margin:0" colspan="100%">
								<?php
								echo gettext("There are no plugin options to administer.");
								?>
							</td>
						</tr>
						<?php
					} else {
						if ($single) {
							?>
							<tr>
								<td colspan="100%">
									<p>
										<?php
										backButton(array('buttonLink' => $backlink));
										applyButton();
										resetButton();
										?>
									</p>
								</td>
							</tr>
							<?php
						} else {
							?>
							<tr>
								<th></th>
								<th style="text-align:right; padding-right: 10px;">
									<?php printPageSelector($subpage, $rangeset, 'admin-tabs/options.php', array('page' => 'options', 'tab' => 'plugin')); ?>
								</th>
								<th></th>
							</tr>
							<?php
						}
						?>
					</table>
					<input type="hidden" name="checkForPostTruncation" value="1" />
					<?php
				}
				?>
			</form>

		</div>
		<!-- end of tab_plugin div -->
		<?php
	}
}
