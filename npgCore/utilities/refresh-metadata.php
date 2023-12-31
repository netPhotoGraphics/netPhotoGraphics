<?php
/**
 * This template is used to reload metadata from images. Running it will process the entire gallery,
 * supplying an album name (ex: loadAlbums.php?album=newalbum) will only process the album named.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 */
// force UTF-8 Ø

define('OFFSET_PATH', 3);
require_once(dirname(__DIR__) . '/admin-globals.php');

// need the class plugins to handle video, etc.
foreach (getEnabledPlugins() as $extension => $plugin) {
	if ($plugin['priority'] & CLASS_PLUGIN)
		require_once($plugin['path']);
}

require_once(CORE_SERVERPATH . 'template-functions.php');

if (isset($_REQUEST['album'])) {
	$localrights = ALBUM_RIGHTS;
} else {
	$localrights = NULL;
}

admin_securityChecks($localrights, $return = currentRelativeURL());
XSRFdefender('refresh');

$_admin_menu = $_SESSION['navigation_tabs']; //	Remembered since we are not loading all the plugins

$imageid = '';

if (isset($_GET['tab']) && $_GET['tab'] == 'prune') {
	if (isset($_GET['id'])) {
		$imageid = sanitize_numeric($_GET['id']);
	}
	$imageid = $_gallery->garbageCollect(true, true, $imageid);
	$type = 'tab=prune&amp;';
	$title = gettext('Refresh Database');
	$finished = gettext('Finished refreshing the database');
	$incomplete = gettext('Database refresh is incomplete');
	$allset = gettext("We are all set to refresh the database");
	$continue = gettext('Continue refreshing the database.');
} else {
	$type = 'tab=refresh&amp;';
	$title = gettext('Refresh Metadata');
	$finished = gettext('Finished refreshing the metadata');
	$incomplete = gettext('Metadata refresh is incomplete');
	$allset = gettext("We are all set to refresh the metadata");
	$continue = gettext('Continue refreshing the metadata.');
}

if (isset($_REQUEST['album'])) {
	$tab = 'edit';
} else {
	$tab = 'admin';
}
$albumparm = $folder = $albumwhere = $imagewhere = $id = $r = '';
$ret = '';
$backurl = getAdminLink('admin.php');
if (isset($_REQUEST['return'])) {
	$return = $_REQUEST['return'];
	if ($return == '*') {
		$backurl = getAdminLink('admin-tabs/edit.php');
	} else {
		$r = '?page=edit&amp;album=' . pathurlencode($ret = sanitize_path($return));
		if (strpos($return, '*') === 0) {
			$r .= '&amp;tab=subalbuminfo';
			$star = '*';
		} else {
			$star = '';
		}
		$backurl = getAdminLink('admin-tabs/edit.php') . $r . '&amp;return=' . $star . pathurlencode($ret);
	}
}

if (isset($_REQUEST['album'])) {
	if (isset($_POST['album'])) {
		$folder = sanitize_path(urldecode($_POST['album']));
	} else {
		$folder = sanitize_path($_GET['album']);
	}
	if (!empty($folder)) {
		$album = newAlbum($folder);
		if (!$album->isMyItem(ALBUM_RIGHTS)) {
			if (!npgFilters::apply('admin_managed_albums_access', false, $return)) {
				header('Location: ' . getAdminLink('admin.php'));
				exit();
			}
		}
	}
	$albumparm = '&amp;album=' . pathurlencode($folder);
}

if (isset($_GET['refresh'])) {
	if (empty($imageid)) {
		$metaURL = $backurl;
	} else {
		if (!empty($ret)) {
			$ret = '&amp;return=' . $ret;
		}
		$metaURL = $redirecturl = '?' . $type . 'refresh=continue&amp;id=' . $imageid . $albumparm . $ret . '&XSRFToken=' . getXSRFToken('refresh');
	}
} else {
	if (isset($_REQUEST['return']))
		$ret = sanitize($_REQUEST['return']);
	if (!empty($ret))
		$ret = '&amp;return=' . $ret;
	$metaURL = $starturl = '?' . $type . 'refresh=start' . $albumparm . '&amp;XSRFToken=' . getXSRFToken('refresh') . $ret;
}

if ($type !== 'tab=prune&amp;') {
	if (!empty($folder)) {
		$album = newAlbum($folder);
		if (!$album->isMyItem(ALBUM_RIGHTS)) {
			if (!npgFilters::apply('admin_managed_albums_access', false, $return)) {
				header('Location: ' . getAdminLink('admin.php'));
				exit();
			}
		}
		$sql = "SELECT `id` FROM " . prefix('albums') . " WHERE `folder`=" . db_quote($folder);
		$row = query_single_row($sql);
		if ($row) {
			$id = $row['id'];
			$r = " $folder";
		}
	}
}

printAdminHeader($tab, 'Refresh');
if (!empty($metaURL)) {
	?>
	<meta http-equiv="refresh" content="1; url=<?php echo $metaURL; ?>" />
	<?php
}
echo "\n</head>";
echo "\n<body>";
printLogoAndLinks();
echo "\n" . '<div id="main">';
printTabs();
?>
<div id="content">
	<h1><?php echo $title; ?></h1>
	<div class="tabbox">
		<?php
		if (isset($_GET['refresh'])) {
			$dateset = '';
			if (!empty($folder) && $_gallery->getAlbumUseImagedate()) {
				$albumobj = newAlbum($folder);
				if ($albumobj->getNumImages()) {
					//	there will be an image to set the album date from
					$dateset = ", `date`=NULL";
				}
			}

			if ($type !== 'tab=prune&amp;') {
				if (!empty($id)) {
					$imagewhere = "WHERE `albumid`=$id";
					$albumwhere = "WHERE `parentid`=$id";
					$sql = "UPDATE " . prefix('albums') . " SET `mtime`=0" . $dateset . " WHERE `id`=$id";
					query($sql);
				}
				$sql = "UPDATE " . prefix('albums') . " SET `mtime`=0 $albumwhere";
				query($sql);
				$sql = "UPDATE " . prefix('images') . " SET `mtime`=0 $imagewhere;";
				query($sql);
			}
			if (empty($imageid)) {
				?>
				<h3><?php echo $finished; ?></h3>
				<p><?php echo gettext('you should return automatically. If not press: '); ?></p>
				<p><a href="<?php echo $backurl; ?>">&laquo; <?php echo gettext('Back'); ?></a></p>
				<?php
			} else {
				?>
				<h3><?php echo $incomplete; ?></h3>
				<p><?php echo gettext('This process should continue automatically. If not press: '); ?></p>
				<p><a href="<?php echo $redirecturl; ?>" title="<?php echo $continue; ?>" style="font-size: 15pt; font-weight: bold;">
						<?php echo gettext("Continue!"); ?></a>
				</p>
				<?php
			}
		} else {
			if (!empty($folder) && empty($id)) {
				echo "<p> " . sprintf(gettext("<em>%s</em> not found"), $folder) . "</p>";
			} else {
				if (empty($r)) {
					echo "<p>" . $allset . "</p>";
				} else {
					echo "<p>" . sprintf(gettext("We are all set to refresh the metadata for <em>%s</em>"), $r) . "</p>";
				}
				echo '<p>' . gettext('This process should start automatically. If not press: ') . '</p>';
				?>
				<p><a href="<?php echo $starturl . '&amp;XSRFToken=' . getXSRFToken('refresh'); ?>"
							title="<?php echo $title; ?>" style="font-size: 15pt; font-weight: bold;">
						<?php echo gettext("Go!"); ?></a>
				</p>
				<?php
			}
		}

		echo "\n" . '</div>';
		echo "\n" . '</div>';
		printAdminFooter();
		echo "\n" . '</div>';
		echo "\n</body>";
		echo "\n</html>";
		?>



