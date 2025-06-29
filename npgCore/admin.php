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

require_once(__DIR__ . '/admin-globals.php');
require_once(CORE_SERVERPATH . 'reconfigure.php');
require_once(GITHUB_API_PATH);

$came_from = NULL;
if (npg_loggedin() && !empty($_admin_menu)) {
	if (!$_current_admin_obj->getID() || empty($msg) && !npg_loggedin(OVERVIEW_RIGHTS)) {
		// admin access without overview rights, redirect to first tab
		$tab = reset($_admin_menu);
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


if (npg_loggedin(ADMIN_RIGHTS)) {
	checkInstall();
	if (time() > getOption('last_garbage_collect') + 864000) {
		$_gallery->garbageCollect();
	}
}

if (isset($_GET['_login_error'])) {
	$_login_error = sanitize($_GET['_login_error']);
}

if (isset($_GET['report'])) {
	$class = 'messagebox fade-message';
	$msg = sanitize($_GET['report']);
} else {
	$msg = '';
}
if (class_exists('CMS')) {
	require_once(PLUGIN_SERVERPATH . 'zenpage/admin-functions.php');
}

if (npg_loggedin()) { /* Display the admin pages. Do action handling first. */
	if (isset($_GET['action'])) {
		$action = sanitize($_GET['action']);
		switch ($action) {
			case 'external':
			case 'session':
				$needs = ALL_RIGHTS;
				break;
			default:
				$needs = ADMIN_RIGHTS;
				break;
		}

		if (npg_loggedin($needs)) {
			switch ($action) {
				case 'purgeDBitems':
					XSRFdefender('purgeDBitems');
					$class = 'messagebox fade-message';
					$msg = gettext('Fields and indexes not used by netPhotoGraphics were removed from the database.');

					$sql = 'SELECT `type`, `subtype`, `aux` FROM ' . prefix('plugin_storage') . ' WHERE `type` LIKE ' . db_quote('db_orpahned_%');
					$result = query_full_array($sql);
					foreach ($result as $item) {
						if ($item['type'] == 'db_orpahned_index') {
							$what = ' INDEX';
						} else {
							$what = '';
						}
						$sql = 'ALTER TABLE ' . prefix($item['subtype']) . ' DROP' . $what . ' `' . $item['aux'] . '`';
						Query($sql, false);
					}
					$sql = 'DELETE FROM ' . prefix('plugin_storage') . ' WHERE `type` LIKE ' . db_quote('db_orpahned_%');
					query($sql);
					break;

				case 'optimizeTables':
					$dbTables = array();
					$result = db_show('tables');
					if ($result) {
						while ($row = db_fetch_assoc($result)) {
							$dbTables[] = $row;
						}
						db_free_result($result);
					}

					foreach ($dbTables as $row) {
						query('OPTIMIZE TABLE ' . prefix(reset($row)));
					}
					$class = 'messagebox fade-message';
					$msg = gettext('Database tables optimized.');

					break;

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
					require_once(PLUGIN_SERVERPATH . 'static_html_cache.php');
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
					if (npgFunctions::hasPrimaryScripts()) {
						XSRFdefender('restore_setup');
						list($diff, $needs) = checkSignature(2);
						if (empty($needs)) {
							$class = 'messagebox fade-message';
							$msg = gettext('Setup files restored.');
						} else {
							npgFilters::apply('log_setup', false, 'restore', implode(', ', $needs));
							$class = 'errorbox fade-message';
							$msg = gettext('Setup files restore failed.');
						}
					}
					break;

				/** protect the setup files ************************************************** */
				case 'protect_setup':
					if (npgFunctions::hasPrimaryScripts()) {
						XSRFdefender('protect_setup');
						chdir(CORE_SERVERPATH . 'setup/');
						$list = safe_glob('*.php');
						$exempt = array('setup-functions.php', 'icon.php');

						$rslt = array();
						foreach ($list as $component) {
							if (in_array($component, $exempt)) { // some plugins may need these.
								continue;
							}
							chmod(CORE_SERVERPATH . 'setup/' . $component, 0777);
							if (rename(CORE_SERVERPATH . 'setup/' . $component, CORE_SERVERPATH . 'setup/' . stripSuffix($component) . '.xxx')) {
								chmod(CORE_SERVERPATH . 'setup/' . stripSuffix($component) . '.xxx', FILE_MOD);
							} else {
								chmod(CORE_SERVERPATH . 'setup/' . $component, FILE_MOD);
								$rslt[] = $component;
							}
						}
						if (empty($rslt)) {
							npgFilters::apply('log_setup', true, 'protect', gettext('protected'));
							$class = 'messagebox fade-message';
							$msg = gettext('Setup files protected.');
						} else {
							npgFilters::apply('log_setup', false, 'protect', implode(', ', $rslt));
							$class = 'errorbox fade-message';
							$msg = gettext('Protecting setup files failed.');
						}
					}
					break;

				case 'install_update':
				case 'download_update':
					XSRFdefender('install_update');
					$msg = FALSE;
					if ($action === 'download_update') {
						$newestVersionURI = getOption('getUpdates_latest');
						if ($msg = getRemoteFile($newestVersionURI, SERVERPATH)) {
							$found = file_exists($file = SERVERPATH . '/' . basename($newestVersionURI));
							if ($found) {
								unlink($file);
							}
							purgeOption('getUpdates_lastCheck'); //	incase we missed the update
							$class = 'errorbox';
							$msg .= '<br /><br />' . gettext('The latest version may be downloded from the <a href="https://netPhotoGraphics.org">netPhotoGraphics</a> website.');
							$msg .= '<br /><br />' . sprintf(gettext('Click on the <code>%1$s</code> button to download the release to your computer, FTP the zip file to your site, and revisit the overview page. Then there will be an <code>Install</code> button that will install the update.'), ARROW_DOWN_GREEN . 'netPhotoGraphics');
							break;
						}
					}
					$found = safe_glob(SERVERPATH . '/setup-*.zip');
					if (!empty($found)) {
						$file = reset($found);
						if (!unzip($file, SERVERPATH)) {
							$class = 'errorbox';
							$msg = gettext('netPhotoGraphics could not extract extract.php.bin from zip file.');
							break;
						} else {
							unlink(SERVERPATH . '/readme.txt');
							unlink(SERVERPATH . '/release notes.htm');
						}
					}
					if (file_exists(SERVERPATH . '/extract.php.bin')) {
						if (isset($file)) {
							unlink($file);
						}
						if (!rename(SERVERPATH . '/extract.php.bin', SERVERPATH . '/extract.php')) {
							$class = 'errorbox';
							$msg = gettext('Renaming the <code>extract.php.bin</code> file failed.');
							break;
						}
					}
					if (file_exists(SERVERPATH . '/extract.php')) {
						chmod(SERVERPATH . '/extract.php', 0777);
						header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
						header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
						header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
						header("Cache-Control: post-check=0, pre-check=0", false);
						header("Pragma: no-cache");
						header('Location: ' . FULLWEBPATH . '/extract.php?npgUpdate=' . time());
						exit();
					} else {
						$class = 'errorbox';
						$msg = gettext('Did not find the <code>extract</code> file.');
					}
					break;

				/** external script return *************************************************** */
				case 'session':
					$msg = $_SESSION['errormessage'];
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
						$msg = sanitize($_GET['msg'], 1);
					} else if (!isset($msg)) {
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
					if (isset($_admin_button_actions) && in_array($func, $_admin_button_actions)) {
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

/*
 * connect with Github for curren release if not a clone install
 */
if (class_exists('Milo\Github\Api') && npgFunctions::hasPrimaryScripts()) {
	/*
	 * Update check Copyright 2017 by Stephen L Billard for use in https://%GITHUB%/netPhotoGraphics and derivitives
	 */
	if (getOption('getUpdates_lastCheck') + 8640 < time()) {
		setOption('getUpdates_lastCheck', time());
		fetchGithubLatest(GITHUB_ORG, 'netPhotoGraphics', 'getUpdates_latest');
	}

	$newestVersionURI = getOption('getUpdates_latest');
	$newestVersion = preg_replace('~[^0-9,.]~', '', str_replace('setup-', '', stripSuffix(basename(strval($newestVersionURI)))));
}

if (npg_loggedin() && $_admin_menu) {
	//	check rights if logged in, if not we will display the logon form below
	admin_securityChecks(OVERVIEW_RIGHTS, currentRelativeURL());
}

// Print our header
printAdminHeader('overview');
scriptLoader(PLUGIN_SERVERPATH . 'common/masonry/masonry.pkgd.min.js');
?>
<script>

	$(function () {
		$('#overviewboxes').masonry({
			// options
			itemSelector: '.overview-section',
			columnWidth: 560
		});
	});

</script>
<?php
echo "\n</head>";

if (!npg_loggedin()) {
	// If they are not logged in, display the login form and exit
	?>
	<body style="background-image: none">
		<?php $_authority->printLoginForm($came_from); ?>
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

	if (npg_loggedin(ADMIN_RIGHTS)) {
		$sql = 'SELECT `id` FROM ' . prefix('plugin_storage') . ' WHERE `type` LIKE ' . db_quote('db_orpahned_%') . ' LIMIT 1';
		$result = query_full_array($sql);
		if (!empty($result)) {
			$buttonlist[] = array(
					'XSRFTag' => 'purgeDBitems',
					'category' => gettext('Database'),
					'enable' => TRUE,
					'button_text' => $buttonText = gettext('Purge unused structure items'),
					'formname' => 'purgeDB_button',
					'action' => getAdminLink('admin.php') . '?action=purgeDBitems',
					'icon' => WASTEBASKET,
					'title' => gettext('Removes fields and indexes from the database that are not used by netPhotoGraphics.'),
					'alt' => '',
					'hidden' => '<input type="hidden" name="action" value="purgeDBitems" />',
					'rights' => ADMIN_RIGHTS
			);
		}

		$buttonlist[] = array(
				'XSRFTag' => 'optimizeTables',
				'category' => gettext('Database'),
				'enable' => TRUE,
				'button_text' => $buttonText = gettext('Optimize Database tables'),
				'formname' => 'optiomizeTables_button',
				'action' => getAdminLink('admin.php') . '?action=optimizeTables',
				'icon' => HIGH_VOLTAGE_SIGN,
				'title' => gettext('Performs an OPTIMIZE TABLES query on each table in the database.'),
				'alt' => '',
				'hidden' => '<input type="hidden" name="action" value="optimizeTables" />',
				'rights' => ADMIN_RIGHTS
		);
		if ($newVersionAvailable = isset($newestVersion) && $newestVersion) {
			$newVersionAvailable = version_compare(preg_replace('~[^0-9,.]~', '', $newestVersion), preg_replace('~[^0-9,.]~', '', NETPHOTOGRAPHICS_VERSION_CONCISE));
			if ($newVersionAvailable > 0) {
				if (!isset($_SESSION['new_version_available'])) {
					$_SESSION['new_version_available'] = $newestVersion;
					?>
					<div class="newVersion" style="height:78px;">
						<h2><?php echo gettext('There is a new version available.'); ?></h2>
						<?php
						if (npgFunctions::hasPrimaryScripts()) {
							printf(gettext('Version %s can be installed from the utility buttons.'), $newestVersion);
						} else {
							$clone = clonedFrom();
							$base = substr(SERVERPATH, 0, strlen(SERVERPATH) - strlen(WEBPATH));
							if (strpos($base, $clone) == 0) {
								$base = substr($clone, strlen($base));
								$link = $base . '/' . CORE_FOLDER . '/admin.php';
								$source = '<a href="' . $link . '">' . $_SERVER['HTTP_HOST'] . $base . '</a>';
							} else {
								$source = $clone;
							}
							printf(gettext('Version %1$s can be installed from %2$s.'), $newestVersion, $source);
						}
						?>
					</div>
					<?php
				}
			}
		}
	}
	if (TEST_RELEASE) {
		$official = gettext('<em>Debug</em>');
		$debug = explode('-', NETPHOTOGRAPHICS_VERSION);
		$v = $debug[0];
		$debug = explode('_', $debug[1]);
		array_shift($debug);
		if (!empty($debug)) {
			$debug = array_map('strtolower', $debug);
			$debug = array_map('ucfirst', $debug);
			$official .= ':</strong> ' . implode(', ', $debug) . '<strong>';
		}
	} else {
		$official = gettext('Production');
		$v = NETPHOTOGRAPHICS_VERSION;
	}
	?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<div id="npgRelease" class="genericbox">
				<h2 class="subheadline">
					<a href="<?php echo WEBPATH; ?>/docs/release%20notes.htm" class="doc" title="<?php echo gettext('release notes'); ?>">
						<?php printf(gettext('netPhotoGraphics version <strong>%1$s (%2$s)</strong>'), $v, $official); ?>
					</a>
				</h2>
			</div>
			<?php
			/*			 * * HOME ************************************************************************** */
			/*			 * ********************************************************************************* */

			$setupUnprotected = printSetupWarning();
			if (npgFunctions::hasPrimaryScripts()) {
				$found = safe_glob(SERVERPATH . '/setup-*.zip');
				if ($newVersion = npg_loggedin(ADMIN_RIGHTS) && (($extract = file_exists($file = SERVERPATH . '/extract.php.bin') || file_exists($file = SERVERPATH . '/extract.php')) || !empty($found))) {
					if ($extract) {
						$f = fopen($file, 'r');
						$buffer = fread($f, 1024);
						fclose($f);
						preg_match('~Extracting netPhotoGraphics (.*) files~', $buffer, $matches);
						$buttonText = sprintf(gettext('Install version %1$s'), $matches[1]);
						$buttonTitle = gettext('Install the netPhotoGraphics update.');
					} else {
						preg_match('~[^\d]*(.*)~', stripSuffix(basename($found[0])), $matches);
						$newestVersion = $matches[1];
						$buttonText = sprintf(gettext('Install %1$s'), $newestVersion);
						$buttonTitle = gettext('Extract and install the netPhotoGraphics update.');
					}
					?>
					<div class="notebox">
						<?php
						echo gettext('<strong>netPhotoGraphics</strong> has detected the presence of an installation file. To install the update click on the <em>Install</em> button below.') . ' ';
						?>
					</div>
					<?php
				}
			} else {
				$newVersion = FALSE;
			}

			npgFilters::apply('admin_note', 'overview', '');

			if (!empty($msg)) {
				?>
				<div class="<?php echo html_encode($class); ?>">
					<strong><?php echo html_encodeTagged($msg); ?></strong>
				</div>
				<?php
			}

			$curdir = getcwd();
			chdir(CORE_SERVERPATH . UTILITIES_FOLDER . '/');
			$filelist = safe_glob('*' . 'php');
			localeSort($filelist);
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
			if (npg_loggedin(ADMIN_RIGHTS)) {
				if (npgFunctions::hasPrimaryScripts()) {
					if ($newVersion) {
						$buttonlist[] = array(
								'XSRFTag' => 'install_update',
								'category' => gettext('Updates'),
								'enable' => 4,
								'button_text' => $buttonText,
								'formname' => 'install_update',
								'action' => getAdminLink('admin.php') . '?action=install_update',
								'icon' => INSTALL,
								'alt' => '',
								'title' => $buttonTitle,
								'hidden' => '<input type="hidden" name="action" value="install_update" />',
								'rights' => ADMIN_RIGHTS
						);
					} else {
						if ($newVersionAvailable != 0) {
							$buttonlist[] = array(
									'XSRFTag' => 'install_update',
									'category' => gettext('Updates'),
									'enable' => version_compare($newestVersion, NETPHOTOGRAPHICS_VERSION, '>') ? 4 : 2,
									'button_text' => sprintf(gettext('Install version %1$s'), $newestVersion),
									'formname' => 'download_update',
									'action' => getAdminLink('admin.php') . '?action=download_update',
									'icon' => INSTALL,
									'alt' => '',
									'title' => sprintf(gettext('Download and install netPhotoGraphics version %1$s on your site.'), $newestVersion),
									'hidden' => '<input type="hidden" name="action" value="download_update" />',
									'rights' => ADMIN_RIGHTS
							);
						}
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
								'action' => getAdminLink('admin.php') . '?action=restore_setup',
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
									'category' => gettext('Updates'),
									'enable' => true,
									'button_text' => gettext('Run setup'),
									'formname' => 'run_setup',
									'action' => getAdminLink('setup.php'),
									'icon' => SETUP,
									'alt' => '',
									'title' => gettext('Run the setup script.'),
									'rights' => ADMIN_RIGHTS
							);
						}
						if (npgFunctions::hasPrimaryScripts()) {
							$buttonlist[] = array(
									'XSRFTag' => 'protect_setup',
									'category' => gettext('Admin'),
									'enable' => 3,
									'button_text' => gettext('Setup » protect scripts'),
									'formname' => 'restore_setup',
									'action' => getAdminLink('admin.php') . '?action=protect_setup',
									'icon' => KEY_RED,
									'alt' => '',
									'title' => gettext('Protects setup files so setup cannot be run.'),
									'rights' => ADMIN_RIGHTS
							);
						}
						break;
				}
			}
			$buttonlist = npgFilters::apply('admin_utilities_buttons', $buttonlist);

			foreach ($buttonlist as $key => $button) {
				if (npg_loggedin($button['rights'])) {
					if (!array_key_exists('category', $button)) {
						$buttonlist[$key]['category'] = gettext('Misc');
					}
				} else {
					unset($buttonlist[$key]);
				}
			}

			$updates = array();
			$buttonlist = sortMultiArray($buttonlist, array('category' => false, 'button_text' => false), true, true);
			foreach ($buttonlist as $key => $button) {
				if ($button['category'] == 'Updates') {
					$updates[] = $button;
					unset($buttonlist[$key]);
				}
			}
			$buttonlist = array_merge($updates, $buttonlist);

			if (npg_loggedin(OVERVIEW_RIGHTS)) {
				?>
				<div id="overviewboxes">
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

								$color = '';
								$disable = false;
								switch ((int) $button['enable']) {
									case 0:
										$disable = ' disabled="disabled"';
										break;
									case 2:
										$color = 'overview_orange';
										break;
									case 3:
										$color = 'overview_red';
										break;
									case 4:
										$color = 'overview_blue"';
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
									<form name="<?php echo $button['formname']; ?>"	id="<?php echo $button['formname']; ?>" action="<?php echo $button['action']; ?>" method="post" class="overview_utility_buttons">
										<?php
										if (isset($button['XSRFTag']) && $button['XSRFTag']) {
											XSRFToken($button['XSRFTag']);
										}
										if (isset($button['hidden']) && $button['hidden']) {
											echo $button['hidden'];
										}
										if (isset($button['onclick'])) {
											$buttonType = 'button';
											$buttonClick = $button['onclick'];
										} else {
											$buttonType = 'submit';
											$buttonClick = NULL;
										}
										if (!empty($button_icon)) {
											if (strpos($button_icon, 'images/') === 0) {
												// old style icon image
												$icon = '<img src="' . $button_icon . '" alt="' . html_encode($button['alt']) . '" />';
											} else {
												$icon = $button_icon . ' ';
											}
										}
										if ($disable) {
											$class = 'fixedwidth tooltip disabled_button';
										} else {
											$class = 'fixedwidth tooltip';
										}
										?>
										<div>
											<?php npgButton($buttonType, $icon . ' <span class="overview_buttontext ' . $color . '">' . html_encode($button['button_text']) . '</span>', array('buttonClass' => $class, 'buttonClick' => $buttonClick, 'disable' => $disable, 'buttonTitle' => html_encode($button['title']))); ?>
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
								$t = $_gallery->getNumAlbums(true);
								$c = $t - $_gallery->getNumAlbums(true, true);
								if ($c > 0) {
									printf(ngettext('<strong>%1$u</strong> Album (%2$u un-published)', '<strong>%1$u</strong> Albums (%2$u un-published)', $t), $t, $c);
								} else {
									printf(ngettext('<strong>%u</strong> Album', '<strong>%u</strong> Albums', $t), $t);
								}
								?>
							</li>
							<li>
								<?php
								$t = $_gallery->getNumImages();
								$c = $t - $_gallery->getNumImages(true);
								if ($c > 0) {
									printf(ngettext('<strong>%1$u</strong> Image (%2$u un-published)', '<strong>%1$u</strong> Images (%2$u un-published)', $t), $t, $c);
								} else {
									printf(ngettext('<strong>%u</strong> Image', '<strong>%u</strong> Images', $t), $t);
								}
								?>
							</li>
							<?php
							if (class_exists('CMS')) {
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
								$t = $_gallery->getNumComments(true);
								$c = $t - $_gallery->getNumComments(false);
								if ($c > 0) {
									printf(ngettext('<strong>%1$u</strong> Comment (%2$u in moderation)', '<strong>%1$u</strong> Comments (%2$u in moderation)', $t), $t, $c);
								} else {
									printf(ngettext('<strong>%u</strong> Comment', '<strong>%u</strong> Comments', $t), $t);
								}
								?>
							</li>
							<li>
								<?php
								$t = $_authority->count('allusers');
								$c = $_authority->count('admin_other');
								if ($c) {
									printf(ngettext('<strong>%1$u</strong> User (%2$u expired)', '<strong>%1$u</strong> Users (%2$u expired)', $t), $t, $c);
								} else {
									printf(ngettext('<strong>%u</strong> User', '<strong>%u</strong> Users', $t), $t);
								}
								?>
							</li>

							<?php
							$g = $_authority->count('user_groups');
							$t = $_authority->count('group_templates');
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
					npgFilters::apply('admin_overview');
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
