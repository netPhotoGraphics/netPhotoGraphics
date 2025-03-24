<?php
/**
 * provides the Themes tab of admin
 *
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 */
// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__DIR__) . '/admin-globals.php');

admin_securityChecks(THEMES_RIGHTS, currentRelativeURL());

$_GET['page'] = 'themes';

/* handle posts */
$message = null; // will hold error/success message displayed in a fading box
if (isset($_GET['action'])) {
	XSRFdefender('admin-tabs/themes');
	switch ($_GET['action']) {
		case 'settheme':
			if (isset($_GET['theme'])) {
				$alb = sanitize_path($_GET['themealbum']);
				$newtheme = sanitize_path($_GET['theme']);
				if (empty($alb)) {
					$_gallery->setCurrentTheme($newtheme);
					$_gallery->save();
					$_set_theme_album = NULL;
				} else {
					$_set_theme_album = newAlbum($alb);
					$oldtheme = $_set_theme_album->getAlbumTheme();
					$_set_theme_album->setAlbumTheme($newtheme);
					$_set_theme_album->save();
				}
				if (!getThemeOption('constructed', $_set_theme_album, $newtheme)) {
					$opthandler = SERVERPATH . '/' . THEMEFOLDER . '/' . $newtheme . '/themeoptions.php';
					if (file_exists($opthandler)) {
						require_once($opthandler);
						$opt = new ThemeOptions(true); //	prime the default options!
					}
					/* set any "standard" options that may not have been covered by the theme */
					standardThemeOptions($newtheme, $_set_theme_album);
				}
				header('Location: ' . getAdminLink('admin-tabs/themes.php') . '?themealbum=' . sanitize($_GET['themealbum']));
				exit();
			}
			break;
		// Duplicate a theme
		case 'copytheme':
			if (isset($_GET['source']) && isset($_GET['target']) && isset($_GET['name'])) {
				$message = copyThemeDirectory(sanitize($_GET['source'], 3), sanitize($_GET['target'], 3), sanitize($_GET['name'], 3));
			}
			$_gallery = new Gallery(); //	flush out remembered themes
			break;
		case 'deletetheme':
			if (isset($_GET['theme'])) {
				if (deleteDirectory(SERVERPATH . '/themes/' . internalToFilesystem($theme = sanitize($_GET['theme'], 3)))) {
					$message = sprintf(gettext("Theme <em>%s</em> removed."), html_encode($theme));
				} else {
					$message = sprintf(gettext('Error removing theme <em>%s</em>'), html_encode($theme));
				}
				$_gallery = new Gallery(); //	flush out remembered themes
				break;
			}
	}
}

printAdminHeader('themes');

// Script for the "Duplicate theme" feature
scriptLoader(CORE_SERVERPATH . 'js/sprintf.js');
?>
<script>
	//<!-- <![CDATA[
	function copyClick(source) {
		var targetname = prompt('<?php echo gettext('New theme name?'); ?>', sprintf('<?php echo gettext('Copy of %s'); ?>', source));
		if (targetname) {
			var targetdir = prompt('<?php echo gettext('Theme folder name?'); ?>', targetname.toLowerCase().replace(/ /g, '_').replace(/[^A-Za-z0-9_]/g, ''));
			if (targetdir) {
				launchScript('', ['action=copytheme', 'XSRFToken=<?php echo getXSRFToken('admin-tabs/themes') ?>', 'source=' + encodeURIComponent(source), 'target=' + encodeURIComponent(targetdir), 'name=' + encodeURIComponent(targetname)]);
				return false;
			}
		}
		return false;
	}

</script>

<?php
echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
echo "\n" . '<div id="main">';
printTabs();
echo "\n" . '<div id="content">';

$galleryTheme = $_gallery->getCurrentTheme();
$themelist = array();
if (npg_loggedin(ADMIN_RIGHTS)) {
	$gallery_title = $_gallery->getTitle();
	if ($gallery_title != gettext("Gallery")) {
		$gallery_title .= ' (' . gettext("Gallery") . ')';
	}
	$themelist[$gallery_title] = '';
}
$albums = $_gallery->getAlbums(0);
foreach ($albums as $alb) {
	$album = newAlbum($alb);
	if ($album->isMyItem(THEMES_RIGHTS)) {
		$key = $album->getTitle();
		if ($key != $alb) {
			$key .= " ($alb)";
		}
		$themelist[$key] = $alb;
	}
}
if (!empty($_REQUEST['themealbum'])) {
	$alb = sanitize_path($_REQUEST['themealbum']);
	$album = newAlbum($alb);
	$albumtitle = $album->getTitle();
	$themename = $album->getAlbumTheme();
	$current_theme = $themename;
} else {
	$current_theme = $galleryTheme;
	foreach ($themelist as $albumtitle => $alb)
		break;
	if (empty($alb)) {
		$themename = $_gallery->getCurrentTheme();
	} else {
		$alb = sanitize_path($alb);
		$album = newAlbum($alb);
		$albumtitle = $album->getTitle();
		$themename = $album->getAlbumTheme();
	}
}
$knownThemes = getSerializedArray(getOption('known_themes'));
$themes = $_gallery->getThemes();

if (empty($themename)) {
	$current_theme = $galleryTheme;
	$theme = $themes[$galleryTheme];
	$themenamedisplay = '</em><small>' . gettext("no theme assigned, defaulting to Gallery theme") . '</small><em>';
	$gallerydefault = true;
} else {
	$theme = $themes[$themename];
	$themenamedisplay = $theme['name'];
	$gallerydefault = false;
}

if (count($themelist) == 0) {
	echo '<div class="errorbox" id="no_themes">';
	echo "<h2>" . gettext("There are no themes for which you have rights to administer.") . "</h2>";
	echo '</div>';
} else {
	npgFilters::apply('admin_note', 'themes', '');

	echo "<h1>" . sprintf(gettext('Current theme for <code><strong>%1$s</strong></code>: <em>%2$s</em>'), $albumtitle, $themenamedisplay);
	if (!empty($alb) && !empty($themename)) {
		?>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<a class="reset" onclick="launchScript('', ['action=settheme', 'themealbum=<?php echo pathurlencode($album->name); ?>', 'theme=', 'XSRFToken=<?php echo getXSRFToken('admin-tabs/themes'); ?>']);" title="<?php printf(gettext('Clear theme assignment for %s'), pathurlencode($album->name)); ?>">
			<?php echo CROSS_MARK_RED_LARGE; ?>
		</a>
		<?php
	}
	echo "</h1>\n";
	if (count($themelist) > 1) {
		echo '<form action="#" method="post">';
		echo gettext("Show theme for: ");
		echo '<select id="themealbum" class="ignoredirty truncate" name="themealbum" onchange="this.form.submit()">';
		generateListFromArray(array(pathurlencode($alb)), $themelist, false, true);
		echo '</select>';
		echo '</form>';
	}
	?>

	<?php
	if ($message) {
		echo '<div class="messagebox fade-message">';
		echo "<h2>$message</h2>";
		echo '</div>';
	}
	?>

	<p>
		<?php echo gettext('Themes allow you to visually change the entire look and feel of your gallery. Theme files are located in your <code>/themes</code> folder.'); ?>
		<?php echo gettext('Place the downloaded themes in the <code>/themes</code> folder and they will be available for your use.') ?>
	</p>

	<p>
		<?php echo gettext("You can edit files from custom themes. Official themes shipped with the software are not editable, since your changes would be lost on next update."); ?>
		<?php echo gettext("If you want to customize an official theme, please first <em>duplicate</em> it. This will place a copy in your <code>/themes</code> folder for you to edit."); ?>
	</p>
	<table class="bordered">
		<tr>
			<th colspan="2"><b><?php echo gettext('Installed themes'); ?></b></th>
			<th class="centered"><b><?php echo gettext('Action'); ?></b></th>
		</tr>
		<?php
		$software_date = date('Y-m-d', filemtime(CORE_SERVERPATH . 'version.php'));
		$current_theme_style = 'class="currentselection"';
		foreach ($themes as $theme => $themeinfo) {
			$style = ($theme == $current_theme) ? ' ' . $current_theme_style : '';
			$themedir = SERVERPATH . '/themes/' . internalToFilesystem($theme);

			$themeweb = WEBPATH . "/themes/$theme";
			if (protectedTheme($theme)) {
				$whose = 'Official theme';
				$ico = '<img class="npg_logoicon" src="' . WEBPATH . '/' . CORE_FOLDER . '/images/np_gold.png" alt="' . gettext('logo') . '" title="' . $whose . '" />';
			} else {
				$whose = gettext('Third party theme');
				$ico = BULLSEYE_BLUE;
			}
			$path = $themedir . '/logo.png';
			if (file_exists($path)) {
				$ico = '<img class="npg_logoicon" src="' . $themeweb . '/logo.png" alt="' . gettext('logo') . '" title="' . $whose . '" />';
			}
			?>
			<tr>
				<td style="margin: 0px; padding: 0px;">
					<?php
					if (file_exists("$themedir/theme.png")) {
						$themeimage = "$themeweb/theme.png";
					} else if (file_exists("$themedir/theme.gif")) {
						$themeimage = "$themeweb/theme.gif";
					} else if (file_exists("$themedir/theme.jpg")) {
						$themeimage = "$themeweb/theme.jpg";
					} else {
						$themeimage = false;
					}
					if ($themeimage) {
						?>
						<img height="150" width="150" src="<?php echo $themeimage; ?>" alt="Theme Screenshot" />
						<?php
					}
					?>
				</td>
				<td <?php echo $style; ?>>
					<?php echo $ico; ?>

					<?php
					$msg = gettext('Theme is deprecated');
					if (isset($themeinfo['deprecated'])) {
						if (is_string($themeinfo['deprecated'])) {
							$msg .= ': ' . $themeinfo['deprecated'];
						}
						?>
						<strong><span class="deprecated"><?php echo $themeinfo['name']; ?></span></strong> <a title="<?php echo $msg; ?>"><?php echo WARNING_SIGN_ORANGE; ?></a>
						<?php
					} else {
						?>
						<strong><?php echo $themeinfo['name']; ?></strong>
						<?php
					}
					?>
					<br />
					<?php echo $themeinfo['author']; ?>
					<br />
					<?php
					if (strpos($ico, 'images/np_gold.png') !== false || $themeinfo['version'] === true) {
						$version = NETPHOTOGRAPHICS_VERSION_CONCISE;
						$date = $software_date;
					} else {
						$version = $themeinfo['version'];
						$date = $themeinfo['date'];
					}
					echo gettext('Version') . ' ' . $version . ', ' . $date;
					?>
					<br />
					<?php
					echo $themeinfo['desc'];
					$linkto = urlencode($theme);
					if ($alb) {
						$linkto .= '&amp;themealbum=' . pathurlencode($alb);
					}
					?>
					<br /><br />
					<a href="<?php echo getAdminLink('admin-tabs/options.php'); ?>?page=options&amp;tab=theme&amp;optiontheme=<?php echo $linkto; ?>" ><?php echo sprintf(gettext('Set <em>%s</em> theme options'), $themeinfo['name']); ?></a>
					<?php
					if (!isset($knownThemes[$theme])) {
						?>
						<span class="notebox"><?php echo gettext('The default options for this theme have not been set.'); ?></span>
						<?php
					}
					?>
				</td>
				<td width="20%" <?php echo $style; ?>>
					<ul class="theme_links">
						<?php
						if ($theme != $current_theme) {
							?>
							<li>

								<?php npgButton('button', CHECKMARK_GREEN . ' ' . gettext("Activate"), array('buttonClick' => "launchScript('', ['action=settheme', 'themealbum=" . pathurlencode($alb) . "', 'theme=" . urlencode($theme) . "', 'XSRFToken=" . getXSRFToken('admin-tabs/themes') . "']);", 'buttonClass' => 'fixedwidth')); ?>


							</li>
							<?php
						} else {
							if ($gallerydefault) {
								?>
								<li>

									<?php npgButton('button', CHECKMARK_GREEN . ' ' . gettext("Assign"), array('buttonClick' => "launchScript('', ['action=settheme', 'themealbum=" . pathurlencode($alb) . "', 'theme=" . urlencode($theme) . "', 'XSRFToken=" . getXSRFToken('admin-tabs/themes') . "']);", 'buttonClass' => 'fixedwidth')); ?>

								</li>
								<?php
							} else {
								echo "<li><strong>" . gettext("Current Theme") . "</strong></li>";
							}
						}

						$editable = npgFilters::apply('theme_editor', '', $theme);
						if (themeIsEditable($theme)) {
							if ($editable) {
								?>
								<li>
									<?php npgButton('button', PENCIL_ICON . ' ' . gettext("Edit"), array('buttonClick' => $editable, 'buttonClass' => 'fixedwidth')); ?>
								</li>
								<?php
							}
							if ($theme != $current_theme) {
								$delete_url = getAdminLink('admin-tabs/themes.php') . '?action=deletetheme&amp;themealbum=' . pathurlencode($alb) . '&amp;theme=' . $theme . '&amp;XSRFToken=' . getXSRFToken('admin-tabs/themes');
								$delete_msg = gettext('Do you really want to delete this theme?');
								?>
								<li>

									<?php npgButton('button', WASTEBASKET . ' ' . gettext("Delete"), array('buttonClick' => "confirmDelete('" . $delete_url . "', '" . $delete_msg . "');", 'buttonClass' => 'fixedwidth')); ?>

								</li>
								<?php
							}
						} else {
							?>
							<li class="copy_theme">
								<?php npgButton('button', DUPLICATE_ICON . ' ' . gettext("Duplicate"), array('buttonClick' => "copyClick('" . $theme . "');", 'buttonClass' => 'fixedwidth')); ?>
							</li>
							<?php
						}
						npgFilters::apply('admin_theme_buttons', $theme, $alb);
						?>
					</ul>
				</td>
			</tr>

			<?php
		}
		?>
	</table>


	<?php
}

echo "\n" . '</div>'; //content
printAdminFooter();
echo "\n" . '</div>'; //main

echo "\n</body>";
echo "\n</html>";
?>
