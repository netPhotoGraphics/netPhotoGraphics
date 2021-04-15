<?php
/**
 * provides the Plugins tab of admin
 *
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 */
// force UTF-8 Ø

if (isset($_REQUEST['subpage'])) {
	$subpage = (int) filter_var($_REQUEST['subpage'], FILTER_SANITIZE_NUMBER_FLOAT);
} else {
	$subpage = 0;
}
if (isset($_GET['action'])) {
	define('OFFSET_PATH', -2); //	prevent conflicting plugin loads
} else {
	define('OFFSET_PATH', 1);
}
require_once(dirname(__DIR__) . '/admin-globals.php');

admin_securityChecks(ADMIN_RIGHTS, currentRelativeURL());

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


/* handle posts */
if (isset($_GET['action'])) {
	$notify = '&post_error';
	switch ($_GET['action']) {
		case 'saveplugins':
			if (isset($_POST['checkForPostTruncation'])) {
				XSRFdefender('saveplugins');
				$plugins = array();
				foreach ($_POST as $plugin => $value) {
					preg_match('/^present__plugin_(.*)$/xis', $plugin, $matches);
					if ($matches) {
						$is = (int) isset($_POST['_plugin_' . $matches[1]]);
						if ($is) {
							$nv = sanitize_numeric($_POST['_plugin_' . $matches[1]]);
							$is = (int) ($nv && true);
						} else {
							$nv = NULL;
						}
						$was = (int) ($value && true);
						if ($was == $is) {
							$action = 1;
						} else if ($was) {
							$action = 2;
						} else {
							$action = 3;
						}
						$plugins[$matches[1]] = array('action' => $action, 'is' => $nv);
					}
				}
				$cleanup = array();
				foreach ($plugins as $_plugin_extension => $data) {
					$f = str_replace('-', '_', $_plugin_extension) . '_enable';
					$p = getPlugin($_plugin_extension . '.php');
					switch ($data['action']) {
						case 1:
							//no change
							break;
						case 2:
							//going from enabled to disabled
							enableExtension($_plugin_extension, 0);
							$cleanup[] = array('p' => $p, 'f' => $f);
							break;
						case 3:
							//going from disabled to enabled
							enableExtension($_plugin_extension, $data['is']);
							$option_interface = NULL;
							require_once($p);

							if ($option_interface && is_string($option_interface)) {
								$if = new $option_interface; //	prime the default options
							}

							if (function_exists($f)) {
								$f(true);
							}
							break;
					}
				}
				foreach ($cleanup as $clean) {
					require_once($clean['p']);
					if (function_exists($f = $clean['f'])) {
						$f(false);
					}
				}
				$notify = '&saved';
			}
			break;
		case 'delete':
			XSRFdefender('deleteplugin');
			$plugin = $_GET['plugin'];
			npgFunctions::removeDir(USER_PLUGIN_SERVERPATH . $plugin);
			unlink(USER_PLUGIN_SERVERPATH . $plugin . '.php');
			purgeOption('_PLUGIN_' . $plugin);
			$notify = '&deleted&plugin=' . $_GET['plugin'];
			if (isset($pluginDetails[$plugin]['deprecated'])) {
				$deprecatedDeleted = getSerializedArray(getOption('deleted_deprecated_plugins'));
				$deprecatedDeleted[] = $plugin;
				setOption('deleted_deprecated_plugins', serialize($deprecatedDeleted));
			}
			break;
	}
	header('Location: ' . getAdminLink('admin-tabs/plugins.php') . '?page=plugins&tab=' . html_encode($plugin_default) . "&subpage=" . html_encode($subpage) . $notify);
	exit();
}

$_GET['page'] = 'plugins';

$saved = isset($_GET['saved']);
printAdminHeader('plugins');

localeSort($pluginlist);
$rangeset = getPageSelector($pluginlist, PLUGINS_PER_PAGE);
$filelist = array_slice($pluginlist, $subpage * PLUGINS_PER_PAGE, PLUGINS_PER_PAGE);
?>
<script type="text/javascript">
	<!--
	var pluginsToPage = ['<?php echo implode("','", array_map('strtolower', $pluginlist)); ?>'];
	function gotoPlugin(plugin) {
		i = Math.floor(jQuery.inArray(plugin, pluginsToPage) / <?php echo PLUGINS_PER_PAGE; ?>);
		window.location = '<?php echo getAdminLink('admin-tabs/plugins.php') ?>?page=plugins&tab=<?php echo html_encode($plugin_default); ?>&subpage=' + i + '&show=' + plugin + '#' + plugin;
	}

	function showPluginInfo(plugin) {
		$.colorbox({
			close: '<?php echo gettext("close"); ?>',
			maxHeight: '90%',
			maxWidth: '80%',
			innerWidth: '800px',
			href: plugin
		});
	}
-->
</script>
<?php
echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
echo "\n" . '<div id="main">';

printTabs();
echo "\n" . '<div id="content">';

/* Page code */

if ($saved) {
	echo '<div class="messagebox fade-message">';
	echo "<h2>" . gettext("Applied") . "</h2>";
	echo '</div>';
}
if (isset($_GET['deleted']) && isset($_GET['plugin'])) {
	echo '<div class="messagebox fade-message">';
	echo "<h2>" . sprintf(gettext('%1$s was deleted.'), $_GET['plugin']) . "</h2>";
	echo '</div>';
}

npgFilters::apply('admin_note', 'plugins', '');
?>
<h1>
	<?php
	printf(gettext('%1$s plugins'), ucfirst($classXlate[$plugin_default]));
	?>
</h1>

<div class="tabbox">
	<?php
	if (isset($_GET['post_error'])) {
		echo '<div class="errorbox">';
		echo "<h2>" . gettext('Error') . "</h2>";
		echo gettext('The form submission is incomplete. Perhaps the form size exceeds configured server or browser limits.');
		echo '</div>';
	}
	?>
	<p>
		<?php
		echo gettext("Plugins provide optional functionality.") . ' ';
		echo gettext("They may be provided as part of the distribution or as offerings from third parties.") . ' ';
		echo sprintf(gettext("Third party plugins are placed in the <code>%s</code> folder and are automatically discovered."), USER_PLUGIN_FOLDER) . ' ';
		echo gettext("If the plugin checkbox is checked, the plugin will be loaded and its functions made available. If the checkbox is not checked the plugin is disabled and occupies no resources.");
		?>
	</p>
	<p class='notebox'><?php echo gettext("<strong>Note:</strong> Support for a particular plugin may be theme dependent! You may need to add the plugin theme functions if the theme does not currently provide support."); ?>
	</p>

	<div class="floatright">
		<?php
		$allplugincount = count($pluginlist);
		$numsteps = ceil(min(100, $allplugincount) / PLUGINS_STEP);
		if ($numsteps) {
			?>
			<?php
			$steps = array();
			for ($i = 1; $i <= $numsteps; $i++) {
				$steps[] = $i * PLUGINS_STEP;
			}
			printEditDropdown('plugininfo', $steps, PLUGINS_PER_PAGE, '&amp;tab=' . $plugin_default);
		}
		?>
	</div>

	<form class = "dirtylistening" onReset = "setClean('form_plugins');" id = "form_plugins" action = "?action=saveplugins&amp;page=plugins&amp;tab=<?php echo html_encode($plugin_default); ?>" method = "post" autocomplete = "off" >
		<?php XSRFToken('saveplugins');
		?>
		<input type="hidden" name="saveplugins" value="yes" />
		<input type="hidden" name="subpage" value="<?php echo $subpage; ?>" />
		<p>
			<?php
			applyButton();
			resetButton();
			?>
		</p>
		<br class="clearall" /><br /><br />
		<table>
			<tr>
				<th class="centered" colspan="100%">
					<?php printPageSelector($subpage, $rangeset, 'admin-tabs/plugins.php', array('page' => 'plugins', 'tab' => $plugin_default)); ?>
				</th>
			</tr>
			<tr>
				<th colspan="2"><span class="displayleft"><?php echo gettext("Available Plugins"); ?></span></th>
				<th>
					<span class="displayleft"><?php echo gettext("Description"); ?></span>
				</th>

			</tr>
			<?php
			foreach ($filelist as $extension) {
				$opt = '_plugin_' . $extension;
				$details = $pluginDetails[$extension];
				$parserr = 0;
				$plugin_URL = getAdminLink('pluginDoc.php') . '?extension=' . $extension;
				switch ($details['thridparty']) {
					case 0:
						$whose = gettext('Official plugin');
						$ico = '<span class="font_icon"><img src="' . WEBPATH . '/' . CORE_FOLDER . '/images/np_gold.png" alt="logo" title="' . $whose . '" /></span>';
						break;
					case 1:
						$whose = gettext('Supplemental plugin');
						$ico = '<span class="font_icon"><img src="' . WEBPATH . '/' . CORE_FOLDER . '/images/np_blue.png" alt="logo" title="' . $whose . '" /></span>';
						$plugin_URL .= '&type=supplemental';
						break;
					case 2:
						$whose = gettext('Third party plugin');
						$path = stripSuffix($plugin_paths[$extension]) . '/logo.png';
						if (file_exists($path)) {
							$ico = '<span class="font_icon"><img src="' . str_replace(SERVERPATH, WEBPATH, $path) . '" alt="logo" title="' . $whose . '" /></span>';
						} else {
							$ico = '<span class="plugin_icon" title="' . $whose . '">' . PLACEHOLDER_ICON . '</span>';
						}
						$plugin_URL .= '&type=thirdparty';
						break;
				}

				$plugin_is_filter = $details['plugin_is_filter'];
				$plugin_deprecated = isset($pluginDetails[$extension]['deprecated']) ? $pluginDetails[$extension]['deprecated'] : '';
				if (isset($details['plugin_description'])) {
					if (false === eval($details['plugin_description'])) {
						$parserr = $parserr | 1;
						$plugin_description = gettext('<strong>Error parsing <em>plugin_description</em> string!</strong>.');
					}
				} else {
					$plugin_description = '';
				}
				if (isset($details['plugin_notice'])) {
					if (false === eval($details['plugin_notice'])) {
						$parserr = $parserr | 2;
						$plugin_notice = gettext('<strong>Error parsing <em>plugin_notice</em> string!</strong>.');
					}
				} else {
					$plugin_notice = '';
				}
				if (isset($details['plugin_version'])) {
					if (false === eval($details['plugin_version'])) {
						$parserr = $parserr | 4;
						$plugin_version = ' ' . gettext('<strong>Error parsing <em>plugin_version</em> string!</strong>.');
					}
				} else {
					$plugin_version = '';
				}
				if (isset($details['plugin_disable'])) {
					if (false === eval($details['plugin_disable'])) {
						$parserr = $parserr | 8;
						$plugin_URL = gettext('<strong>Error parsing <em>plugin_disable</em> string!</strong>.');
					} else {
						if ($plugin_disable) {
							setOption($opt, 0);
						}
					}
				} else {
					$plugin_disable = false;
				}
				$currentsetting = getOptionFromDB($opt);
				$optionlink = NULL;

				if (isset($details['option_interface'])) {
					$str = $details['option_interface'];
					if (preg_match('/\s*=\s*new\s(.*)\(/i', $str)) {
						$plugin_notice .= '<br /><br />' . gettext('<strong>Note:</strong> Instantiating the option interface within the plugin may cause performance issues. You should instead set <code>$option_interface</code> to the name of the class as a string.');
					} else {
						$option_interface = NULL;
						eval($str);
						if ($option_interface) {
							$optionlink = getAdminLink('admin-tabs/options.php') . '?page=options&amp;tab=plugin&amp;from=' . $plugin_default . '&amp;subpage=' . $subpage . '&amp;single=' . $extension;
						}
					}
				}
				$selected_style = '';
				if ($currentsetting > THEME_PLUGIN) {
					$selected_style = ' class="currentselection"';
				}
				if (isset($_GET['show']) && strtolower($_GET['show']) == strtolower($extension)) {
					$selected_style = ' class="highlightselection"';
				}

				if ($plugin_is_filter & CLASS_PLUGIN) {
					$iconA = '<span span class="plugin_icon" title="' . gettext('class plugin') . '">' .
									PLUGIN_CLASS .
									'</span>';
					$iconT = PLACEHOLDER_ICON;
				} else {
					if ($plugin_is_filter & ADMIN_PLUGIN) {
						$iconA = '<span class="plugin_icon" title="' . gettext('admin plugin') . '">' .
										PLUGIN_ADMIN .
										'</span>';
					} else {
						$iconA = PLACEHOLDER_ICON;
					}
					if ($plugin_is_filter & FEATURE_PLUGIN) {
						$iconT = '<span class="plugin_icon" title="' . gettext('feature plugin') . '">'
										. PLUGIN_FEATURE .
										'</span>';
					} else if ($plugin_is_filter & THEME_PLUGIN) {
						$iconT = '<span class="plugin_icon" title="' . gettext('theme plugin') . '">' .
										PLUGIN_THEME .
										'</span>';
					} else {
						$iconT = PLACEHOLDER_ICON;
					}
				}

				$attributes = '';
				if ($parserr) {
					$optionlink = false;
					$attributes .= ' disabled="disabled"';
				} else {
					if ($currentsetting > THEME_PLUGIN) {
						$attributes .= ' checked="checked"';
					}
				}
				if ($plugin_disable) {
					preg_match('/\<a href="#(.*)">/', $plugin_disable, $matches);
					if ($matches) {
						$plugin_disable = str_replace($matches[0], '<a onclick="gotoPlugin(\'' . strtolower($matches[1]) . '\');">', $plugin_disable);
					}
				}
				?>
				<tr<?php echo $selected_style; ?>>
					<td min-width="30%"  class="nowrap">
						<?php
						if (in_array($plugin_default, array(
												'all',
												'thirdparty',
												'enabled',
												'disabled',
												'deprecated',
												'class_plugin',
												'feature_plugin',
												'admin_plugin',
												'theme_plugin')
										)
						) {
							$tab = $plugin_member[$extension];
							?>
							<span class="displayrightsmall">
								<a href="<?php echo FULLWEBPATH . html_encode($plugin_subtabs[$tab]); ?>" title="<?php printf(gettext('Go to &quot;%s&quot; plugin page.'), $tab); ?>">
									<em><?php echo $tab; ?></em>
								</a>
							</span>
							<?php
						}
						?>

						<input type="hidden" name="present_<?php echo $opt; ?>" id="present_<?php echo $opt; ?>" value="<?php echo $currentsetting; ?>" />
						<span id="<?php echo strtolower($extension); ?>" class="floatleft">
							<?php
							if ($plugin_disable) {
								?>
								<span class="text_pointer">
									<?php
								}
								echo $ico;
								echo $iconT;
								echo $iconA;

								if ($plugin_disable) {
									?>
								</span>
								<span class="plugin_disable">
									<div class="plugin_disable_hidden">
										<?php echo $plugin_disable; ?>
									</div>
									<span class="icons" style="padding-left: 4px;padding-right: 3px;">
										<?php echo NO_ENTRY; ?>
									</span>
									<input type="hidden" name="<?php echo $opt; ?>" id="<?php echo $opt; ?>" value="0" />
									<?php
								} else {
									?>
									<label>
										<span style="padding-left: 3px;padding-right: 2px;">
											<input type="checkbox" name="<?php echo $opt; ?>" id="<?php echo $opt; ?>" value="<?php echo $plugin_is_filter; ?>"<?php echo $attributes; ?> />
										</span>
										<?php
									}
									if ($plugin_deprecated) {
										if ($plugin_notice) {
											$plugin_notice .= '<br />';
										}
										$plugin_notice .= '<strong>' . gettext('Plugin is deprecated') . '</strong> ' . trim(str_replace('deprecated', '', $plugin_deprecated));
										?>
										<span class="deprecated">
											<?php
										}
										echo $extension;
										if (!empty($plugin_version)) {
											echo ' v' . $plugin_version;
										}
										if ($plugin_deprecated) {
											?>
										</span>
										<?php
										if ($plugin_disable) {
											?>
									</span>
									<?php
								} else {
									?>
									</label>
									<?php
								}
							}
							?>

						</span>
					</td>
					<td>
						<span class="nowrap">
							<span class="icons plugin_info" id="doc_<?php echo $extension; ?>">
								<a onclick="showPluginInfo('<?php echo $plugin_URL; ?>');" title="<?php echo gettext('Show plugin usage information.'); ?>">
									<?php echo INFORMATION_BLUE; ?>
								</a>
							</span>
							<?php
							if (npgFunctions::hasPrimaryScripts() && ($plugin_default == 'thirdparty' || $plugin_default == 'deprecated')) {
								?>
								<span class="icons">
									<?php
									if (extensionEnabled($extension)) {
										echo PLACEHOLDER_ICON;
									} else {
										?>
										<a href="javascript:confirmDelete('<?php echo getAdminLink('admin-tabs/plugins.php'); ?>?action=delete&plugin=<?php echo html_encode($extension); ?>&tab=<?php echo html_encode($plugin_default); ?>&subpage=<?php echo $subpage; ?>&XSRFToken=<?php echo getXSRFToken('deleteplugin'); ?>','<?php printf(gettext('Ok to delete %1$s? This cannot be undone.'), $extension); ?>')" title="<?php echo gettext('Delete the plugin.'); ?>">
											<?php echo WASTEBASKET; ?>
										</a>
										<?php
									}
									?>
								</span>
								<?php
							}
							if ($optionlink) {
								?>
								<span class="icons">
									<a href="<?php echo $optionlink; ?>" title="<?php printf(gettext("Change %s options"), $extension); ?>">
										<?php echo OPTIONS_ICON; ?>
									</a>
								</span>
								<?php
							} else {
								echo PLACEHOLDER_ICON;
							}
							if ($plugin_notice) {
								?>
								<span class="icons">
									<span class="plugin_warning">
										<?php echo WARNING_SIGN_ORANGE; ?>
										<div class="plugin_warning_hidden">
											<?php echo $plugin_notice; ?>
										</div>
									</span>
								</span>
								<?php
							}
							?>
						</span>
					</td>
					<td colspan="100%">
						<div style="max-width:60em;">
							<?php echo $plugin_description; ?>
						</div>
					</td>
				</tr>
				<?php
			}
			?>
			<tr>
				<td colspan="100%" class="centered">
					<?php printPageSelector($subpage, $rangeset, 'admin-tabs/plugins.php', array('page' => 'plugins', 'tab' => $plugin_default)); ?>
				</td>
			</tr>
		</table>
		<br />
		<ul class="iconlegend">
			<li>
				<img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/np_gold.png" alt="">
				<?php echo gettext('Official plugin'); ?>
			</li>
			<li>
				<img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/np_blue.png" alt="">
				<?php echo gettext('Supplemental plugin'); ?>
			</li>
			<li>
				<?php echo PLUGIN_CLASS; ?>
				<?php echo gettext('Class plugin'); ?>
			</li>
			<li>
				<?php echo PLUGIN_ADMIN; ?>
				<?php echo gettext('Admin plugin'); ?>
			</li>
			<li>
				<?php echo PLUGIN_FEATURE; ?>
				<?php echo gettext('Feature plugin'); ?>
			</li>
			<li>
				<?php echo PLUGIN_THEME; ?>
				<?php echo gettext('Theme plugin'); ?>
			</li>
			<li>
				<?php echo INFORMATION_BLUE; ?>
				<?php echo gettext('Usage info'); ?>
			</li>
			<li>
				<?php echo OPTIONS_ICON; ?>
				<?php echo gettext('Options'); ?>
			</li>
			<li>
				<?php echo WARNING_SIGN_ORANGE; ?>
				<?php echo gettext('Warning note'); ?>
			</li>
		</ul>
		<p>
			<?php
			applyButton();
			resetButton();
			?>
		</p><br /><br />
		<input type="hidden" name="checkForPostTruncation" value="1" />
	</form>
</div>
<?php
echo "\n" . '</div>'; //content
printAdminFooter();
echo "\n" . '</div>'; //main
echo "\n</body>";
echo "\n</html>";
?>



