<?php
/**
 * admin.php is the main script for administrative functions.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 */
// force UTF-8 Ø

/* Don't put anything before this line! */
define('OFFSET_PATH', 1);

require_once(dirname(__FILE__) . '/admin-globals.php');
require_once(SERVERPATH . '/' . ZENFOLDER . '/reconfigure.php');

$came_from = NULL;
if (zp_loggedin() && !empty($zenphoto_tabs)) {
	if (!$_zp_current_admin_obj->getID() || empty($msg) && !zp_loggedin(OVERVIEW_RIGHTS)) {
		// admin access without overview rights, redirect to first tab
		$tab = array_shift($zenphoto_tabs);
		$link = $tab['link'];
		header('location:' . $link);
		exit();
	}
} else {
	if (isset($_GET['from'])) {
		$came_from = sanitizeRedirect($_GET['from']);
	} else {
		$came_from = urldecode(currentRelativeURL());
	}
}

if (version_compare(PHP_VERSION, '5.5.0', '>=')) {
	require_once( SERVERPATH . '/' . ZENFOLDER . '/' . PLUGIN_FOLDER . '/common/gitHubAPI/github-api.php');
}

use Milo\Github;

if (zp_loggedin(ADMIN_RIGHTS)) {
	checkInstall();

	if (class_exists('Milo\Github\Api') && zpFunctions::hasPrimaryScripts()) {
		/*
		 * Update check Copyright 2017 by Stephen L Billard for use in https://%GITHUB%/netPhotoGraphics and derivitives
		 */
		if (getOption('getUpdates_lastCheck') + 8640 < time()) {
			setOption('getUpdates_lastCheck', time());
			try {
				$api = new Github\Api;
				$fullRepoResponse = $api->get('/repos/:owner/:repo/releases/latest', array('owner' => GITHUB_ORG, 'repo' => 'netPhotoGraphics'));
				$fullRepoData = $api->decode($fullRepoResponse);
				$assets = $fullRepoData->assets;
				if (!empty($assets)) {
					$item = array_pop($assets);
					setOption('getUpdates_latest', $item->browser_download_url);
				}
			} catch (Exception $e) {
				debugLog(gettext('GitHub repository not accessible. ') . $e);
			}
		}

		$newestVersionURI = getOption('getUpdates_latest');
		$repro = basename(dirname(dirname(dirname(dirname($newestVersionURI)))));
		$newestVersion = preg_replace('~[^0-9,.]~', '', str_replace('setup-', '', stripSuffix(basename($newestVersionURI))));
	}
}

if (isset($_GET['_zp_login_error'])) {
	$_zp_login_error = sanitize($_GET['_zp_login_error']);
}

if (time() > getOption('last_garbage_collect') + 864000) {
	$_zp_gallery->garbageCollect();
}
if (isset($_GET['report'])) {
	$class = 'messagebox fade-message';
	$msg = sanitize($_GET['report']);
} else {
	$msg = '';
}
if (extensionEnabled('zenpage')) {
	require_once(SERVERPATH . '/' . ZENFOLDER . '/' . PLUGIN_FOLDER . '/zenpage/admin-functions.php');
}

if (zp_loggedin()) { /* Display the admin pages. Do action handling first. */
	if (isset($_GET['action'])) {
		$action = sanitize($_GET['action']);
		if ($action == 'external') {
			$needs = ALL_RIGHTS;
		} else {
			$needs = ADMIN_RIGHTS;
		}
		if (zp_loggedin($needs)) {
			switch ($action) {
				/** clear the image cache **************************************************** */
				case "clear_cache":
					XSRFdefender('clear_cache');
					Gallery::clearCache();
					$class = 'messagebox fade-message';
					$msg = gettext('Image cache cleared.');
					break;

				/** clear the RSScache ********************************************************** */
				case "clear_rss_cache":
					if (class_exists('RSS')) {
						XSRFdefender('clear_cache');
						$RSS = new RSS(array('rss' => 'null'));
						$RSS->clearCache();
						$class = 'messagebox fade-message';
						$msg = gettext('RSS cache cleared.');
					}
					break;

				/** clear the HTMLcache ****************************************************** */
				case 'clear_html_cache':
					XSRFdefender('ClearHTMLCache');
					require_once(SERVERPATH . '/' . ZENFOLDER . '/' . PLUGIN_FOLDER . '/static_html_cache.php');
					static_html_cache::clearHTMLCache();
					$class = 'messagebox fade-message';
					$msg = gettext('HTML cache cleared.');
					break;
				/** clear the search cache ****************************************************** */
				case 'clear_search_cache':
					XSRFdefender('ClearSearchCache');
					SearchEngine::clearSearchCache(NULL);
					$class = 'messagebox fade-message';
					$msg = gettext('Search cache cleared.');
					break;
				/** restore the setup files ************************************************** */
				case 'restore_setup':
					XSRFdefender('restore_setup');
					list($diff, $needs) = checkSignature(2);
					if (empty($needs)) {
						$class = 'messagebox fade-message';
						$msg = gettext('Setup files restored.');
					} else {
						zp_apply_filter('log_setup', false, 'restore', implode(', ', $needs));
						$class = 'errorbox fade-message';
						$msg = gettext('Setup files restore failed.');
					}
					break;

				/** protect the setup files ************************************************** */
				case 'protect_setup':
					XSRFdefender('protect_setup');
					chdir(SERVERPATH . '/' . ZENFOLDER . '/setup/');
					$list = safe_glob('*.php');

					$rslt = array();
					foreach ($list as $component) {
						if ($component == 'setup-functions.php') { // some plugins may need these.
							continue;
						}
						@chmod(SERVERPATH . '/' . ZENFOLDER . '/setup/' . $component, 0777);
						if (@rename(SERVERPATH . '/' . ZENFOLDER . '/setup/' . $component, SERVERPATH . '/' . ZENFOLDER . '/setup/' . stripSuffix($component) . '.xxx')) {
							@chmod(SERVERPATH . '/' . ZENFOLDER . '/setup/' . stripSuffix($component) . '.xxx', FILE_MOD);
						} else {
							@chmod(SERVERPATH . '/' . ZENFOLDER . '/setup/' . $component, FILE_MOD);
							$rslt[] = $component;
						}
					}
					if (empty($rslt)) {
						zp_apply_filter('log_setup', true, 'protect', gettext('protected'));
						$class = 'messagebox fade-message';
						$msg = gettext('Setup files protected.');
					} else {
						zp_apply_filter('log_setup', false, 'protect', implode(', ', $rslt));
						$class = 'errorbox fade-message';
						$msg = gettext('Protecting setup files failed.');
					}
					break;

				case 'install_update':
				case 'download_update':
					XSRFdefender('install_update');
					$msg = FALSE;
					if ($action == 'download_update') {
						@ini_set('allow_url_fopen', 1);
						if (($fopen = ini_get('allow_url_fopen')) && copy($newestVersionURI, SERVERPATH . '/setupnpg.zip')) {
							if (!unzip(SERVERPATH . '/setupnpg.zip', SERVERPATH)) {
								$class = 'errorbox';
								$msg = gettext('netPhotoGraphics could not extract extract.php.bin from zip file.');
							} else {
								unlink(SERVERPATH . '/readme.txt');
								unlink(SERVERPATH . '/release notes.htm');
								unlink(SERVERPATH . '/setupnpg.zip');
							}
						} else {
							$class = 'errorbox';
							$msg = gettext('netPhotoGraphics could not download the update.');
							if (!$fopen) {
								$msg .= ' ' . gettext('<em>allow_url_fopen</em> is not enabled in your PHP.ini configuration file. ');
							}
						}
						if ($msg) {
							$msg .= ' ' . sprintf(gettext('Click on the <code>%1$s</code> button to download the release to your computer, FTP the <code>extract.php.bin</code> file to your site, and revisit the overview page. Then there will be an <code>Install update</code> button that will install the update.'), ARROW_DOWN_GREEN . 'netPhotoGraphics ' . $newestVersion);
							break;
						}
					}
					if (rename(SERVERPATH . '/extract.php.bin', SERVERPATH . '/extract.php')) {
						header('Location: ' . FULLWEBPATH . '/extract.php');
						exit();
					} else {
						$class = 'errorbox';
						$msg = gettext('Renaming the <code>extract.php.bin</code> file failed.');
					}
					break;

				/** external script return *************************************************** */
				case 'external':
					if (isset($_GET['error'])) {
						$class = sanitize($_GET['error']);
						if (empty($class)) {
							$class = 'errorbox fade-message';
						}
					} else {
						$class = 'messagebox fade-message';
					}
					if (isset($_GET['msg'])) {
						$msg = sanitize($_GET['msg']);
					} else {
						$msg = '';
					}
					if (isset($_GET['more'])) {
						$class = 'messagebox'; //	no fade!
						$more = $_SESSION[$_GET['more']];
						foreach ($more as $detail) {
							$msg .= '<br />' . $detail;
						}
					}
					break;

				/** default ****************************************************************** */
				default:
					$func = preg_replace('~\(.*\);*~', '', $action);
					if (in_array($func, $_zp_button_actions)) {
						call_user_func($action);
					}
					break;
			}
		} else {
			$class = 'errorbox fade-message';
			$actions = array(
					'clear_cache' => gettext('purge Image cache'),
					'clear_rss_cache' => gettext('purge RSS cache'),
					'reset_hitcounters' => gettext('reset all hitcounters'),
					'clear_search_cache' => gettext('purge search cache')
			);
			if (array_key_exists($action, $actions)) {
				$msg = $actions[$action];
			} else {
				$msg = '<em>' . html_encode($action) . '</em>';
			}
			$msg = sprintf(gettext('You do not have proper rights to %s.'), $msg);
		}
	} else {
		if (isset($_GET['from'])) {
			$class = 'errorbox fade-message';
			$msg = sprintf(gettext('You do not have proper rights to access %s.'), html_encode(sanitize($_GET['from'])));
		}
	}

	/*	 * ********************************************************************************* */
	/** End Action Handling ************************************************************ */
	/*	 * ********************************************************************************* */
}

if (zp_loggedin() && $zenphoto_tabs) {
	//	check rights if logged in, if not we will display the logon form below
	admin_securityChecks(OVERVIEW_RIGHTS, currentRelativeURL());
}

// Print our header
printAdminHeader('overview');
scriptLoader(SERVERPATH . '/' . ZENFOLDER . '/' . PLUGIN_FOLDER . '/common/masonry/masonry.pkgd.min.js');
?>
<script type="text/javascript">
	// <!-- <![CDATA[
	$(function () {
		$('#overviewboxes').masonry({
			// options
			itemSelector: '.overview-section',
			columnWidth: 560
		});
	});
	// ]]> -->
</script>
<?php
echo "\n</head>";

if (!zp_loggedin()) {
	// If they are not logged in, display the login form and exit
	?>
	<body style="background-image: none">
		<?php $_zp_authority->printLoginForm($came_from); ?>
	</body>
	<?php
	echo "\n</html>";
	exit();
}
$buttonlist = array();
?>
<body>
	<?php
	/* Admin-only content safe from here on. */
	printLogoAndLinks();

	if (zp_loggedin(ADMIN_RIGHTS)) {

		if ($newVersionAvailable = isset($newestVersion)) {
			$zenphoto_version = explode('-', ZENPHOTO_VERSION);
			$zenphoto_version = preg_replace('~[^0-9,.]~', '', array_shift($zenphoto_version));
			if ($newVersionAvailable = version_compare($newestVersion, $zenphoto_version, '>')) {
				if (!isset($_SESSION['new_version_available'])) {
					$_SESSION['new_version_available'] = $newestVersion;
					?>
					<div class="newVersion" style="height:78px;">
						<h2><?php echo gettext('There is a new version is available.'); ?></h2>
						<?php
						printf(gettext('Version %s can be downloaded by the utility button.'), $newestVersion);
						?>
					</div>
					<?php
				}
				$buttonlist[] = array(
						'category' => gettext('Admin'),
						'enable' => 2,
						'button_text' => $repro . ' ' . $newestVersion,
						'formname' => 'getUpdates_button',
						'action' => $newestVersionURI,
						'icon' => ARROW_DOWN_GREEN,
						'title' => sprintf(gettext('Download %1$s version %2$s to your computer.'), $repro, $newestVersion),
						'alt' => '',
						'hidden' => '',
						'rights' => ADMIN_RIGHTS
				);
			}
		}
	}
	?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<?php
			/*			 * * HOME ************************************************************************** */
			/*			 * ********************************************************************************* */
			$setupUnprotected = printSetupWarning();
			if ($newVersion = file_exists(SERVERPATH . '/extract.php.bin') && zp_loggedin(ADMIN_RIGHTS)) {
				?>
				<div class="notebox">
					<h2><?php echo gettext('Extract file detected.'); ?></h2>
					<?php
					echo gettext('<strong>netPhotoGraphics</strong> has detected the presence of an <code>extract.php.bin</code> file. To install the update click on the <em>Install update</em> button below.') . ' ';
					?>
				</div>
				<?php
			}

			zp_apply_filter('admin_note', 'overview', '');

			if (!empty($msg)) {
				?>
				<div class="<?php echo html_encode($class); ?>">
					<h2><?php echo html_encodeTagged($msg); ?></h2>
				</div>
				<?php
			}


			$curdir = getcwd();
			chdir(SERVERPATH . "/" . ZENFOLDER . '/' . UTILITIES_FOLDER . '/');
			$filelist = safe_glob('*' . 'php');
			natcasesort($filelist);
			foreach ($filelist as $utility) {
				$utilityStream = file_get_contents($utility);
				$s = strpos($utilityStream, '$buttonlist');
				if ($s !== false) {
					$e = strpos($utilityStream, ';', $s);
					if ($e) {
						$str = substr($utilityStream, $s, $e - $s) . ';';
						eval($str);
					}
				}
			}
			$buttonlist = zp_apply_filter('admin_utilities_buttons', $buttonlist);

			foreach ($buttonlist as $key => $button) {
				if (zp_loggedin($button['rights'])) {
					if (!array_key_exists('category', $button)) {
						$buttonlist[$key]['category'] = gettext('Misc');
					}
				} else {
					unset($buttonlist[$key]);
				}
			}
			if (zp_loggedin(ADMIN_RIGHTS)) {

				if ($newVersion) {
					$buttonlist[] = array(
							'XSRFTag' => 'install_update',
							'category' => gettext('Admin'),
							'enable' => true,
							'button_text' => gettext('Install update'),
							'formname' => 'install_update',
							'action' => FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?action=install_update',
							'icon' => BADGE_GOLD,
							'alt' => '',
							'title' => gettext('Extract and install the netPhotoGraphics update.'),
							'hidden' => '<input type="hidden" name="action" value="install_update" />',
							'rights' => ADMIN_RIGHTS
					);
				} else {
					if ($newVersionAvailable) {
						$buttonlist[] = array(
								'XSRFTag' => 'install_update',
								'category' => gettext('Admin'),
								'enable' => true,
								'button_text' => sprintf(gettext('Install version %1$s'), $newestVersion),
								'formname' => 'download_update',
								'action' => FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?action=download_update',
								'icon' => BADGE_GOLD,
								'alt' => '',
								'title' => sprintf(gettext('Download and install %1$s version %2$s.'), $repro, $newestVersion),
								'hidden' => '<input type="hidden" name="action" value="download_update" />',
								'rights' => ADMIN_RIGHTS
						);
					}
				}

				//	button to restore setup files if needed
				switch (abs($setupUnprotected)) {
					case 1:
						$buttonlist[] = array(
								'XSRFTag' => 'restore_setup',
								'category' => gettext('Admin'),
								'enable' => true,
								'button_text' => gettext('Setup » restore scripts'),
								'formname' => 'restore_setup',
								'action' => FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?action=restore_setup',
								'icon' => LOCK_OPEN,
								'alt' => '',
								'title' => gettext('Restores setup files so setup can be run.'),
								'hidden' => '<input type="hidden" name="action" value="restore_setup" />',
								'rights' => ADMIN_RIGHTS
						);
						break;
					case 2:
						if (!$newVersion) {
							$buttonlist[] = array(
									'category' => gettext('Admin'),
									'enable' => true,
									'button_text' => gettext('Run setup'),
									'formname' => 'run_setup',
									'action' => FULLWEBPATH . '/' . ZENFOLDER . '/setup.php',
									'icon' => BADGE_GOLD,
									'alt' => '',
									'title' => gettext('Run the setup script.'),
									'hidden' => '',
									'rights' => ADMIN_RIGHTS
							);
						}
						if (zpFunctions::hasPrimaryScripts()) {
							$buttonlist[] = array(
									'XSRFTag' => 'protect_setup',
									'category' => gettext('Admin'),
									'enable' => 3,
									'button_text' => gettext('Setup » protect scripts'),
									'formname' => 'restore_setup',
									'action' => FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?action=protect_setup',
									'icon' => KEY_RED,
									'alt' => '',
									'title' => gettext('Protects setup files so setup cannot be run.'),
									'hidden' => '<input type="hidden" name="action" value="protect_setup" />',
									'rights' => ADMIN_RIGHTS
							);
						}
						break;
				}
			}

			$buttonlist = sortMultiArray($buttonlist, array('category', 'button_text'), false, true, true);

			if (zp_loggedin(OVERVIEW_RIGHTS)) {
				if (TEST_RELEASE) {
					$official = gettext('<em>Debug build</em>');
					$debug = explode('-', ZENPHOTO_VERSION);
					$v = $debug[0];
					$debug = explode('_', $debug[1]);
					array_shift($debug);
					if (!empty($debug)) {
						$debug = array_map('strtolower', $debug);
						$debug = array_map('ucfirst', $debug);
						$official .= ': ' . implode(', ', $debug);
					}
				} else {
					$official = gettext('Official build');
					$v = ZENPHOTO_VERSION;
				}
				?>
				<div id="overviewboxes">
					<div class="box overview-section overview_utilities">
						<h2 class="h2_bordered">
							<a href="<?php echo WEBPATH; ?>/docs/release%20notes.htm" class="doc" title="<?php echo gettext('release notes'); ?>">
								<?php printf(gettext('netPhotoGraphics version <strong>%1$s (%2$s)</strong>'), $v, $official); ?>
							</a>
						</h2>
						<?php
						if (!empty($buttonlist)) {
							?>
							<div class="box overview-section overview_utilities">
								<h2 class="h2_bordered"><?php echo gettext("Utility functions"); ?></h2>
								<?php
								$category = '';
								foreach ($buttonlist as $button) {
									$button_category = $button['category'];
									$button_icon = $button['icon'];

									$color = $disable = '';
									switch ((int) $button['enable']) {
										case 0:
											$disable = ' disabled="disabled"';
											break;
										case 2:
											$color = ' style="color:orange"';
											break;
										case 3:
											$color = ' style="color:red"';
											break;
									}
									if ($category != $button_category) {
										if ($category) {
											?>
											</fieldset>
											<?php
										}
										$category = $button_category;
										?>
										<fieldset class="overview_utility_buttons_field"><legend><?php echo $category; ?></legend>
											<?php
										}
										?>
										<form name="<?php echo $button['formname']; ?>"	id="<?php echo $button['formname']; ?>" action="<?php echo $button['action']; ?>" class="overview_utility_buttons">
											<?php
											if (isset($button['XSRFTag']) && $button['XSRFTag'])
												XSRFToken($button['XSRFTag']);
											echo $button['hidden'];
											if (isset($button['onclick'])) {
												$type = 'type="button" onclick="' . $button['onclick'] . '"';
											} else {
												$type = 'type="submit"';
											}
											?>
											<div class="buttons tooltip" title="<?php echo html_encode($button['title']); ?>">
												<button class="fixedwidth<?php if ($disable) echo ' disabled_button'; ?>" <?php echo $type . $disable; ?>>
													<?php
													if (!empty($button_icon)) {
														if (strpos($button_icon, 'images/') === 0) {
															// old style icon image
															?>
															<img src="<?php echo $button_icon; ?>" alt="<?php echo html_encode($button['alt']); ?>" />
															<?php
														} else {
															echo $button_icon . ' ';
														}
													}
													echo '<span' . $color . '>' . html_encode($button['button_text']) . '</span>';
													?>
												</button>
											</div><!--buttons -->
										</form>
										<?php
									}
									if ($category) {
										?>
									</fieldset>
									<?php
								}
								?>
							</div><!-- overview-section -->
							<?php
						}
						?>
						<div class="box overview-section overiew-gallery-stats">
							<h2 class="h2_bordered"><?php echo gettext("Gallery Stats"); ?></h2>
							<ul>
								<li>
									<?php
									$t = $_zp_gallery->getNumAlbums(true);
									$c = $t - $_zp_gallery->getNumAlbums(true, true);
									if ($c > 0) {
										printf(ngettext('<strong>%1$u</strong> Album (%2$u un-published)', '<strong>%1$u</strong> Albums (%2$u un-published)', $t), $t, $c);
									} else {
										printf(ngettext('<strong>%u</strong> Album', '<strong>%u</strong> Albums', $t), $t);
									}
									?>
								</li>
								<li>
									<?php
									$t = $_zp_gallery->getNumImages();
									$c = $t - $_zp_gallery->getNumImages(true);
									if ($c > 0) {
										printf(ngettext('<strong>%1$u</strong> Image (%2$u un-published)', '<strong>%1$u</strong> Images (%2$u un-published)', $t), $t, $c);
									} else {
										printf(ngettext('<strong>%u</strong> Image', '<strong>%u</strong> Images', $t), $t);
									}
									?>
								</li>
								<?php
								if (extensionEnabled('zenpage')) {
									?>
									<li>
										<?php printPagesStatistic(); ?>
									</li>
									<li>
										<?php printCategoriesStatistic(); ?>
									</li>
									<li>
										<?php printNewsStatistic(); ?>
									</li>
									<?php
								}
								?>
								<li>
									<?php
									$t = $_zp_gallery->getNumComments(true);
									$c = $t - $_zp_gallery->getNumComments(false);
									if ($c > 0) {
										printf(ngettext('<strong>%1$u</strong> Comment (%2$u in moderation)', '<strong>%1$u</strong> Comments (%2$u in moderation)', $t), $t, $c);
									} else {
										printf(ngettext('<strong>%u</strong> Comment', '<strong>%u</strong> Comments', $t), $t);
									}
									?>
								</li>
								<li>
									<?php
									$users = $_zp_authority->getAdministrators('allusers');
									$t = count($users);
									$c = 0;
									foreach ($users as $key => $user) {
										if ($user['valid'] > 1) {
											$c++;
										}
									}
									if ($c) {
										printf(ngettext('<strong>%1$u</strong> User (%2$u expired)', '<strong>%1$u</strong> Users (%2$u expired)', $t), $t, $c);
									} else {
										printf(ngettext('<strong>%u</strong> User', '<strong>%u</strong> Users', $t), $t);
									}
									?>
								</li>

								<?php
								$g = $t = 0;
								foreach ($_zp_authority->getAdministrators('groups') as $element) {
									if ($element['name'] == 'group') {
										$g++;
									} else {
										$t++;
									}
								}
								if ($g) {
									?>
									<li>
										<?php printf(ngettext('<strong>%u</strong> Group', '<strong>%u</strong> Groups', $g), $g); ?>
									</li>
									<?php
								}
								if ($t) {
									?>
									<li>
										<?php printf(ngettext('<strong>%u</strong> Template', '<strong>%u</strong> Templates', $t), $t); ?>
									</li>
									<?php
								}
								?>
							</ul>
						</div><!-- overview-gallerystats -->

						<?php
						zp_apply_filter('admin_overview');
						?>
					</div><!-- boxouter -->
				</div><!-- content -->
				<?php
			} else {
				?>
				<div class="errorbox">
					<?php echo gettext('Your user rights do not allow access to administrative functions.'); ?>
				</div>
				<?php
			}
			?>
		</div>
		<br clear="all">
		<?php printAdminFooter(); ?>
	</div><!-- main -->
</body>
<?php
// to fool the validator
echo "\n</html>";
exit();
