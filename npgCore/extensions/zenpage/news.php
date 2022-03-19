<?php
/**
 * zenpage news.php
 *
 * @author Malte Müller (acrylian)
 * @package plugins/zenpage
 */
define('OFFSET_PATH', 4);
require_once(dirname(dirname(__DIR__)) . '/admin-globals.php');
require_once("admin-functions.php");

admin_securityChecks(ZENPAGE_NEWS_RIGHTS, currentRelativeURL());

if (isset($_GET['articles_page'])) {
	if ($_GET['articles_page'] == 'all') {
		$articles_page = 0;
	} else {
		$articles_page = max(1, sanitize_numeric($_GET['articles_page']));
	}
	setNPGCookie('articleTab_articleCount', $articles_page, 3600 * 24 * 365 * 10);
} else {
	$c = getNPGCookie('articleTab_articleCount');
	if (!$c) {
		$c = 15;
	}
	$articles_page = max(1, $c);
}

$reports = array();

if (isset($_GET['delete'])) {
	XSRFdefender('delete');
	$msg = deleteZenpageObj(newArticle(sanitize($_GET['delete']), 'news.php'));
	if (!empty($msg)) {
		$reports[] = $msg;
	}
}

// publish or un-publish page by click
if (isset($_GET['publish'])) {
	XSRFdefender('update');
	$obj = newArticle(sanitize($_GET['titlelink']));
	$obj->setShow(sanitize_numeric($_GET['publish']));
	$obj->save();
}

if (isset($_GET['commentson'])) {
	XSRFdefender('update');
	$obj = newArticle(sanitize($_GET['titlelink']));
	$obj->setCommentsAllowed(sanitize_numeric($_GET['commentson']));
	$obj->save();
}

if (isset($_GET['hitcounter'])) {
	XSRFdefender('hitcounter');
	$obj = newArticle(sanitize($_GET['titlelink']));
	$obj->set('hitcounter', 0);
	$obj->save();
	$reports[] = '<p class="messagebox fade-message">' . gettext("Hitcounter reset") . '</p>';
}

if (isset($_POST['checkallaction'])) { // true if apply is pressed
	XSRFdefender('checkeditems');
	$action = processZenpageBulkActions('Article');
	if ($report = zenpageBulkActionMessage($action)) {
		$reports[] = $report;
	} else {
		if (empty($reports)) {
			$reports[] = "<p class='messagebox fade-message'>" . gettext("Nothing changed.") . "</p>";
		}
	}
}

if (empty($reports)) {
	if (isset($_SESSION['reports'])) {
		$reports = $_SESSION['reports'];
		unset($_SESSION['reports']);
	}
} else {
	$_SESSION['reports'] = $reports;
	$uri = getAdminLink(PLUGIN_FOLDER . '/zenpage/news.php') . getNewsAdminOptionPath(getNewsAdminOption(NULL));
	header('Location: ' . $uri);
	exit();
}

printAdminHeader('news', 'articles');
zenpageJSCSS();
datepickerJS();
updatePublished('news');
?>

<script type="text/javascript">
	//<!-- <![CDATA[
	var deleteArticle = "<?php echo gettext("Are you sure you want to delete this article? THIS CANNOT BE UNDONE!"); ?>";
	function confirmAction() {
		if ($('#checkallaction').val() == 'deleteall') {
			return confirm('<?php echo js_encode(gettext("Are you sure you want to delete the checked items?")); ?>');
		} else {
			return true;
		}
	}

	// ]]> -->
</script>

</head>
<body>
	<?php
	$subtab = getCurrentTab();
	if (isset($_GET['author'])) {
		$cur_author = sanitize($_GET['author']);
	} else {
		$cur_author = NULL;
	}
	printLogoAndLinks();
	?>
	<div id="main">
		<?php
		printTabs();
		?>
		<div id="content">
			<?php npgFilters::apply('admin_note', 'news', $subtab); ?>
			<h1>
				<?php echo gettext('Articles'); ?>
			</h1>
			<div id = "container">
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

					if (isset($_GET['author'])) {
						echo "<em><small>" . html_encode(sanitize($_GET['author'])) . '</small></em>';
					}
					$catobj = $category = NULL;
					if (isset($_GET['category'])) {
						if ('`' == $category = sanitize($_GET['category'])) {
							$category = gettext('Un-categorized');
						} else {
							$catobj = newCategory($category);
						}
						echo "<em><small>" . html_encode($category) . '</small></em>';
					}
					if (isset($_GET['date'])) {
						$_post_date = sanitize($_GET['date']);
						echo '<em><small> (' . html_encode($_post_date) . ')</small></em>';
						// require so the date dropdown is working
						set_context(ZENPAGE_NEWS_DATE);
					}
					if (isset($_GET['published'])) {
						switch ($_GET['published']) {
							case 'no':
								$published = 'unpublished';
								break;
							case 'yes':
								$published = 'published';
								break;
							case 'sticky':
								$published = 'sticky';
						}
					} else {
						$published = 'all';
					}
					$sortorder = 'publishdate';
					$direction = $sortdirection = true;
					if (isset($_GET['sortorder'])) {
						list($sortorder, $sortdirection) = explode('-', $_GET['sortorder']);
						$direction = $sortdirection && $sortdirection == 'desc';
					}
					$admin = $_current_admin_obj->getUser();
					$resultU = $_CMS->getArticles(0, 'unpublished', false, $sortorder, $direction, false, $catobj);
					$result = $_CMS->getArticles(0, $published, false, $sortorder, $direction, false, $catobj);
					foreach (array('result' => $result, 'resultU' => $resultU) as $which => $list) {
						foreach ($list as $key => $article) {
							$article = newArticle($article['titlelink']);
							$subrights = $article->subRights();
							$author = $article->getOwner();
							if (!($author == $admin || $article->isMyItem(ZENPAGE_NEWS_RIGHTS) && $subrights & MANAGED_OBJECT_RIGHTS_EDIT) ||
											($cur_author && $cur_author != $article->getOwner()) ||
											(is_null($catobj) && !is_null($category) && !empty($article->getCategories()))) {
								unset($$which[$key]);
							}
						}
					}

					$categories = $_CMS->getAllCategories();
					foreach ($categories as $key => $cat) {
						$catobj = newCategory($cat['titlelink']);
						if (!($catobj->subRights() & MANAGED_OBJECT_RIGHTS_EDIT)) {
							unset($categories[$key]);
						}
					}

					$total = 1;
					$articles = count($result);
					if ($articles || !empty($categories) || npg_loggedin(MANAGE_ALL_NEWS_RIGHTS)) {
						// Basic setup for the global for the current admin page first
						if (!isset($_GET['subpage'])) {
							$subpage = 0;
						} else {
							$subpage = sanitize_numeric($_GET['subpage']);
						}
						if ($articles_page) {
							$total = ceil($articles / $articles_page);
							//Needed check if we really have articles for page x or not otherwise we are just on page 1
							if ($total <= $subpage) {
								$subpage = 0;
							}
							$offset = CMS::getOffset($articles_page);
							$list = array();
							foreach ($result as $article) {
								$item = $article[$sortorder];
								if ($sortorder == 'title') {
									$item = getSerializedArray($item);
									$item = get_language_string($item);
								}
								if ($item) {
									$list[] = $item;
								} else {
									$list[] = '';
								}
							}
							if ($sortorder == 'title') {
								$rangeset = getPageSelector($list, $articles_page);
							} else {
								$rangeset = getPageSelector($list, $articles_page, 'dateDiff');
							}
							$options = array_merge(array('page' => 'news', 'tab' => 'articles'), getNewsAdminOption(NULL));
							$result = array_slice($result, $offset, $articles_page);
						} else {
							$rangeset = $options = array();
						}
						?>
						<span class="zenpagestats"><?php printNewsStatistic($articles, count($resultU)); ?></span>
						<br class="clearall" />
						<div class="floatright">
							<?php
							printAuthorDropdown();
							printCategoryDropdown();
							printNewsDatesDropdown();
							printUnpublishedDropdown();
							printSortOrderDropdown();
							printArticlesPerPageDropdown($subpage);
							?>
						</div>
						<br class="clearall" />
						<?php
						$option = getNewsAdminOptionPath(getNewsAdminOption(NULL));
						?>
						<form class="dirtylistening" onReset="setClean('sortableListForm');" action="<?php echo getAdminLink(PLUGIN_FOLDER . '/zenpage/news.php') . $option; ?>" method="post" name="checkeditems" id="sortableListForm" onsubmit="return confirmAction();" autocomplete="off">
							<?php
							XSRFToken('checkeditems');
							applyButton();
							?>
							<span class="floatright">
								<?php npgButton('button', PLUS_ICON . '	' . gettext("New Article"), array('buttonLink' => getAdminLink(PLUGIN_FOLDER . '/zenpage/edit.php') . '?newsarticle&amp;add&amp;XSRFToken=' . getXSRFToken('add'))); ?>
							</span>
							<br class="clearall" />
							<div class="headline">
								<span class="floatright padded">
									<?php
									$checkarray = array(
											gettext('*Bulk actions*') => 'noaction',
											gettext('Delete') => 'deleteall',
											gettext('Set to published') => 'showall',
											gettext('Set to unpublished') => 'hideall',
											gettext('Add categories') => array('name' => 'addcats', 'action' => 'mass_cats_data'),
											gettext('Clear categories') => 'clearcats'
									);
									if (npg_loggedin(MANAGE_ALL_NEWS_RIGHTS)) {
										$checkarray[gettext('Change author')] = array('name' => 'changeowner', 'action' => 'mass_owner_data');
									}
									$checkarray = npgFilters::apply('bulk_article_actions', $checkarray);
									printBulkActions($checkarray);
									?>
								</span>

								<span class="floatright" style="padding-right:30%;">
									<?php printPageSelector($subpage, $rangeset, PLUGIN_FOLDER . '/zenpage/news.php', $options); ?>
								</span>
							</div>
							<table class="bordered">
								<tr>
									<td><!--title--></td>
									<td><?php echo gettext('Categories'); ?></td>
									<td><?php echo gettext('Author'); ?></td>
									<td>
										<?php
										if ($sortorder == 'date') {
											echo gettext('Created');
										} else {
											echo gettext('Last changed');
										}
										?>
									</td>
									<td><?php echo gettext('Published'); ?></td>
									<td><?php echo gettext('Expires'); ?></td>
									<td class="subhead" colspan="100%">
										<label class="floatright"><?php echo gettext("Check All"); ?> <input type="checkbox" name="allbox" id="allbox" onclick="checkAll(this.form, 'ids[]', this.checked);" />
										</label>
									</td>
								</tr>
								<?php
								foreach ($result as $article) {
									$article = newArticle($article['titlelink']);
									?>
									<tr>
										<td>
											<?php
											switch ($article->getSticky()) {
												case 1:
													$sticky = ' <small>[' . gettext('sticky') . ']</small>';
													break;
												case 9:
													$sticky = ' <small><strong>[' . gettext('sticky') . ']</strong></small>';
													break;
												default:
													$sticky = '';
													break;
											}
											echo '<a href="' . getAdminLink(PLUGIN_FOLDER . '/zenpage/edit.php') . getNewsAdminOptionPath(array_merge(array('newsarticle' => NULL, 'titlelink' => urlencode($article->getTitlelink())), getNewsAdminOption(NULL))) . '">';
											checkForEmptyTitle($article->getTitle(), "news");
											echo '</a>' . checkHitcounterDisplay($article->getHitcounter()) . $sticky;
											?>

										</td>
										<td>
											<?php printCategoriesList($article) ?><br />
										</td>
										<td>
											<?php echo html_encode($article->getOwner()); ?>
										</td>
										<td>
											<?php
											echo $article->getLastchange();
											?>
										</td>
										<td>
											<?php printPublished($article); ?>
										</td>
										<td>
											<?php printExpired($article); ?>
										</td>

										<td>
											<div class="page-list_icon">
												<?php
												if ($article->inProtectedCategory()) {
													echo LOCK;
												} else {
													echo LOCK_OPEN;
												}
												?>
											</div>
											<div class="page-list_icon">
												<?php echo linkPickerIcon($article); ?>
											</div >
											<?php
											$option = getNewsAdminOptionPath(getNewsAdminOption(NULL));
											if (empty($option)) {
												$divider = '?';
											} else {
												$divider = '&amp;';
											}
											if (checkIfLocked($article)) {
												?>
												<div class="page-list_icon">
													<?php printPublishIconLink($article, $option); ?>
												</div>
												<?php
												if (extensionEnabled('comment_form')) {
													?>
													<div class="page-list_icon">
														<?php
														if ($article->getCommentsAllowed()) {
															?>
															<a href="<?php echo $option . $divider; ?>commentson=0&amp;titlelink=<?php
															echo html_encode($article->getTitlelink());
															?>&amp;XSRFToken=<?php echo getXSRFToken('update') ?>" title="<?php echo gettext('Disable comments'); ?>">
																	 <?php echo BULLSEYE_GREEN; ?>
															</a>
															<?php
														} else {
															?>
															<a href="<?php echo $option . $divider; ?>commentson=1&amp;titlelink=<?php
															echo html_encode($article->getTitlelink());
															?>&amp;XSRFToken=<?php echo getXSRFToken('update') ?>" title="<?php echo gettext('Enable comments'); ?>">
																	 <?php echo BULLSEYE_RED; ?>
															</a>
															<?php
														}
														?>
													</div>
													<?php
												}
											} else {
												?>
												<div class="page-list_icon">
													<?php echo BULLSEYE_LIGHTGRAY; ?>
												</div>
												<div class="page-list_icon">
													<?php echo BULLSEYE_LIGHTGRAY; ?>
												</div>
												<?php
											}
											?>

											<div class="page-list_icon">
												<a target="_blank" href="<?php echo $article->getlink(); ?>" title="<?php echo gettext('View article'); ?>">
													<?php echo BULLSEYE_BLUE; ?>
												</a>
											</div>
											<?php
											if ($unlocked = checkIfLocked($article)) {
												if (extensionEnabled('hitcounter')) {
													?>
													<div class="page-list_icon">
														<a href="<?php echo $option . $divider; ?>hitcounter=1&amp;titlelink=<?php
														echo html_encode($article->getTitlelink());
														?>&amp;XSRFToken=<?php echo getXSRFToken('hitcounter') ?>" title="<?php echo gettext('Reset hitcounter'); ?>">
																 <?php echo RECYCLE_ICON; ?>
														</a>
													</div>
													<?php
												}
												?>
												<div class="page-list_icon">
													<a href="javascript:confirmDelete('<?php echo getAdminLink(PLUGIN_FOLDER . '/zenpage/news.php') . $option . $divider; ?>delete=<?php echo $article->getTitlelink(); ?>&amp;XSRFToken=<?php echo getXSRFToken('delete') ?>','<?php echo js_encode(gettext('Are you sure you want to delete this article? THIS CANNOT BE UNDONE!')); ?>')" title="<?php echo gettext('Delete article'); ?>">
														<?php echo WASTEBASKET; ?>
													</a>
												</div>
												<?php
											} else {
												?>
												<div class="page-list_icon">
													<?php echo BULLSEYE_LIGHTGRAY; ?>
												</div>
												<div class="page-list_icon">
													<?php echo BULLSEYE_LIGHTGRAY; ?>
												</div>

												<?php
											}
											?>
										</td>
										<td>
											<div class="floatright">
												<input type="checkbox" name="ids[]" value="<?php echo $article->getTitlelink(); ?>"<?php if (!$unlocked) echo ' disabled="disabled"'; ?>/>
											</div>
										</td>
									</tr>
									<?php
								}
								?>

							</table>
							<p class="centered">
								<?php printPageSelector($subpage, $rangeset, PLUGIN_FOLDER . '/zenpage/news.php', $options); ?>
							</p>
							<p>
								<?php applyButton(); ?>
							</p>
						</form>
						<?php
						printZenpageIconLegend();
					} else {
						echo gettext('There are no articles for you to edit.');
					}
					?>
				</div> <!-- tab_articles -->
			</div> <!-- content -->
		</div> <!-- container -->
		<?php printAdminFooter(); ?>
	</div> <!-- main -->
</body>
</html>
