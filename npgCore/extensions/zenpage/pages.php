<?php
/**
 * zenpage pages.php
 *
 * @author Malte Müller (acrylian)
 * @package plugins/zenpage
 */
define('OFFSET_PATH', 4);
require_once(dirname(dirname(__DIR__)) . '/admin-globals.php');
require_once("admin-functions.php");

admin_securityChecks(ZENPAGE_PAGES_RIGHTS, currentRelativeURL());

$reports = array();
$nothing = false;
if (isset($_GET['deleted'])) {
	$reports[] = "<p class='messagebox fade-message'>" . gettext("Article successfully deleted!") . "</p>";
}
if (isset($_POST['update'])) {
	XSRFdefender('update');
	if (updateItemSortorder('pages')) {
		$reports[] = "<p class='messagebox fade-message'>" . gettext("Sort order saved.") . "</p>";
	} else {
		$nothing = true;
	}
}
// remove the page from the database
if (isset($_GET['delete'])) {
	XSRFdefender('delete');
	$msg = deleteZenpageObj(newPage(sanitize($_GET['delete']), 'pages.php'));
	if (!empty($msg)) {
		$reports[] = $msg;
	}
}
// publish or un-publish page by click
if (isset($_GET['publish'])) {
	XSRFdefender('update');
	$obj = newPage(sanitize($_GET['titlelink']));
	$obj->setShow(sanitize_numeric($_GET['publish']));
	$obj->save();
}

if (isset($_GET['commentson'])) {
	XSRFdefender('update');
	$obj = newPage(sanitize($_GET['titlelink']));
	$obj->setCommentsAllowed(sanitize_numeric($_GET['commentson']));
	$obj->save();
}
if (isset($_GET['hitcounter'])) {
	XSRFdefender('hitcounter');
	$obj = newPage(sanitize($_GET['titlelink']));
	$obj->set('hitcounter', 0);
	$obj->save();
	$reports[] = '<p class="messagebox fade-message">' . gettext("Hitcounter reset") . '</p>';
}

if (isset($_POST['checkallaction']) && $_POST['checkallaction'] != 'noaction') {
	$action = processZenpageBulkActions('Page');
	if ($report = zenpageBulkActionMessage($action)) {
		$reports[] = $report;
	} else {
		$nothing = true;
	}
}
if ($nothing & empty($reports)) {
	$reports[] = "<p class='messagebox fade-message'>" . gettext("Nothing changed.") . "</p>";
}
if (empty($reports)) {
	if (isset($_SESSION['reports'])) {
		$reports = $_SESSION['reports'];
		unset($_SESSION['reports']);
	}
} else {
	$_SESSION['reports'] = $reports;
	$uri = getAdminLink(PLUGIN_FOLDER . '/zenpage/pages.php');
	header('Location: ' . $uri);
	exit();
}

$_CMS = new CMS();

printAdminHeader('pages');
printSortableHead();
zenpageJSCSS();
updatePublished('pages');
?>
<script type="text/javascript">
	//<!-- <![CDATA[
	var deleteArticle = "<?php echo gettext("Are you sure you want to delete this article? THIS CANNOT BE UNDONE!"); ?>";
	var deletePage = "<?php echo gettext("Are you sure you want to delete this page? THIS CANNOT BE UNDONE!"); ?>";
	function confirmAction() {
		if ($('#checkallaction').val() == 'deleteall') {
			return confirm('<?php echo js_encode(gettext("Are you sure you want to delete the checked items?")); ?>');
		} else {
			return true;
		}
	}

	
</script>

</head>
<body>
	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<?php
			npgFilters::apply('admin_note', 'pages', '');
			?>
			<h1><?php echo gettext('Pages'); ?></h1>

			<div class="tabbox">
				<?php
				if ($reports) {
					$show = array();
					preg_match_all('/<p class=[\'"](.*?)[\'"]>(.*?)<\/p>/', implode('', $reports), $matches);
					foreach ($matches[1] as $key => $report) {
						$show[$report][] = $matches[2][$key];
					}
					foreach ($show as $type => $list) {
						echo '<p class="' . $type . '">' . implode('<br />', $list) . '</p>';
					}
				}

				$admin = $_current_admin_obj->getUser();
				$pagelist = $_CMS->getPages();
				foreach ($pagelist as $key => $apage) {
					$pageobj = newPage($apage['titlelink']);
					if (!($pageobj->getOwner() == $admin || $pageobj->subRights() & MANAGED_OBJECT_RIGHTS_EDIT)) {
						unset($pagelist[$key]);
					}
				}

				if (!empty($pagelist) || npg_loggedin(MANAGE_ALL_PAGES_RIGHTS)) {
					?>
					<span class="zenpagestats"><?php printPagesStatistic(); ?></span>
					<form class="dirtylistening" onReset="setClean('sortableListForm');$('#pagesort').sortable('cancel');" action="<?php echo getAdminLink(PLUGIN_FOLDER . '/zenpage/pages.php'); ?>" method="post" name="update" id="sortableListForm" onsubmit="return confirmAction();" autocomplete="off">
						<?php
						XSRFToken('update');
						printSortableDirections(gettext("Select a page to edit or drag the pages into the order, including subpage levels, you wish them displayed."));
						if (GALLERY_SECURITY == 'public') {
							?>
							<p class="notebox">
								<?php echo gettext("<strong>Note:</strong> Subpages of password protected pages inherit the protection."); ?>
							</p>
							<?php
						}
						?>
						<div style="padding-bottom: 5px;">
							<?php
							applyButton(array('buttonClass' => 'serialize'));
							resetButton();
							?>
							<?php
							if (npg_loggedin(MANAGE_ALL_PAGES_RIGHTS)) {
								?>
								<span class="floatright" style="padding-left: 10px;">
									<?php npgButton('button', PLUS_ICON . ' ' . gettext('New Page'), array('buttonLink' => getAdminLink(PLUGIN_FOLDER . '/zenpage/edit.php') . '?page&amp;add&amp;XSRFToken=' . getXSRFToken('add'))); ?>
								</span>
								<?php
							}
							?>
							<div class="headline">
								<?php
								$checkarray = array(
										gettext('*Bulk actions*') => 'noaction',
										gettext('Delete') => 'deleteall',
										gettext('Set to published') => 'showall',
										gettext('Set to unpublished') => 'hideall'
								);
								if (npg_loggedin(MANAGE_ALL_PAGES_RIGHTS)) {
									$checkarray[gettext('Change author')] = array('name' => 'changeowner', 'action' => 'mass_owner_data');
								}

								$checkarray = npgFilters::apply('bulk_page_actions', $checkarray);
								printBulkActions($checkarray);
								?>
							</div>
						</div>
						<br clear="all">
						<div class="bordered">

							<div class="subhead">
								<label style="float: right;padding-top:5px;padding-right:5px;"><?php echo gettext("Check All"); ?> <input type="checkbox" name="allbox" id="allbox" onclick="checkAll(this.form, 'ids[]', this.checked);" />
								</label>
							</div>
							<ul class="page-list" id="pagesort">
								<?php $toodeep = printNestedItemsList('pages-sortablelist'); ?>
							</ul>

						</div>

						<?php
						if ($toodeep) {
							echo '<div class="errorbox">';
							echo '<h2>' . gettext('The sort position of the indicated pages cannot be recorded because the nesting is too deep. Please move them to a higher level and save your order.') . '</h2>';
							echo '</div>';
						}
						?>
						<span id="serializeOutput"></span>
						<input name="update" type="hidden" value="Save Order" />
						<p>
							<?php
							applyButton(array('buttonClass' => 'serialize'));
							resetButton();
							?>
						</p>
					</form>
					<?php
					printZenpageIconLegend();
				} else {
					echo gettext('There are no pages for you to edit.');
				}
				?>
			</div>
		</div>
		<?php printAdminFooter(); ?>
	</div>
</body>
</html>
