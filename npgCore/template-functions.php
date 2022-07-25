<?php
/**
 * Functions used to display content in themes.
 * @package functions
 */
// force UTF-8 Ø

require_once(__DIR__ . '/functions.php');

//******************************************************************************
//*** Template Functions *******************************************************
//******************************************************************************

/* * * Generic Helper Functions ************ */
/* * *************************************** */

/**
 * Returns the version string
 */
function getVersion() {
	return NETPHOTOGRAPHICS_VERSION;
}

/**
 * Prints the version string
 */
function printVersion() {
	echo getVersion();
}

/**
 * Stuff that belongs in the theme <head> area
 */
function printThemeHeadItems() {
	printStandardMeta();
	?>
	<title><?php echo getHeadTitle(getOption('theme_head_separator'), getOption('theme_head_listparents')); ?></title>
	<?php
	scriptLoader(CORE_SERVERPATH . 'button.css');
	scriptLoader(CORE_SERVERPATH . 'loginForm.css');
	if (npg_loggedin()) {
		scriptLoader(getPlugin('toolbox.css', true));
	}
	load_jQuery_CSS();
	load_jQuery_scripts('theme');
}

/**
 * Stuff that belongs at then end of the theme html
 */
function printThemeCloseItems() {

}

/**
 * Prints the clickable drop down toolbox on any theme page with generic admin helpers
 *
 */
function adminToolbox() {
	global $_current_album, $_current_image, $_current_search, $_gallery_page, $_gallery, $_current_admin_obj, $_loggedin;
	if (npg_loggedin()) {
		$page = getCurrentPage();
		if (!$name = $_current_admin_obj->getName()) {
			$name = $_current_admin_obj->getUser();
		}

		if (npg_loggedin(UPLOAD_RIGHTS) && in_array($_gallery_page, array('index.php', 'gallery.php', 'album.php'))) {
			?>
			<script type="text/javascript">
				// <!-- <![CDATA[
				function newAlbum(folder, albumtab) {
					var album = prompt('<?php echo gettext('New album name?'); ?>', '<?php echo gettext('new album'); ?>');
					if (album) {
						window.location = '<?php echo getAdminLink('admin-tabs/edit.php'); ?>?action=newalbum&folder=' + encodeURIComponent(folder) + '&name=' + encodeURIComponent(album) + '&albumtab=' + albumtab + '&XSRFToken=<?php echo getXSRFToken('newalbum'); ?>';
					}
				}
				// ]]> -->
			</script>
			<?php
		}
		/* Note inline styles needed to override some theme javascript issues */
		?>
		<div id="admin_tb">
			<a onclick="$('#admin_tb_data').toggle();" title="<?php echo gettext('Logged in as') . ' ' . $name; ?>">
				<span class="adminGear">
					<?php echo GEAR_SYMBOL; ?>
				</span>
			</a>
		</div>
		<div id="admin_tb_data">
			<ul>
				<?php
				if (npg_loggedin(OVERVIEW_RIGHTS)) {
					?>
					<li>
						<?php printLinkHTML(getAdminLink('admin.php'), gettext("Overview"), NULL, NULL, NULL); ?>
					</li>
					<?php
				}
				if (npg_loggedin(UPLOAD_RIGHTS | FILES_RIGHTS | THEMES_RIGHTS)) {
					?>
					<li>
						<?php printLinkHTML(getAdminLink('admin-tabs/upload.php'), gettext("Upload"), NULL, NULL, NULL); ?>
					</li>
					<?php
				}
				if (npg_loggedin(ALBUM_RIGHTS)) {
					if (!$albums = npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
						foreach ($_gallery->getAlbums() as $key => $analbum) {
							$albumobj = newAlbum($analbum);
							if ($albumobj->isMyItem(ALBUM_RIGHTS)) {
								$albums = true;
								break;
							}
						}
					}
					if ($albums) {
						?>
						<li>
							<?php printLinkHTML(getAdminLink('admin-tabs/edit.php'), gettext("Albums"), NULL, NULL, NULL); ?>
						</li>
						<?php
					}
				}

				npgFilters::apply('admin_toolbox_global');

				if (npg_loggedin(TAGS_RIGHTS)) {
					?>
					<li>
						<?php printLinkHTML(getAdminLink('admin-tabs/tags.php'), gettext("Tags"), NULL, NULL, NULL); ?>
					</li>
					<?php
				}
				if (npg_loggedin(ADMIN_RIGHTS)) {
					?>
					<li>
						<?php printLinkHTML(getAdminLink('admin-tabs/users.php'), gettext("Users"), NULL, NULL, NULL); ?>
					</li>
					<?php
				} else {
					if (npg_loggedin(USER_RIGHTS)) {
						?>
						<li>
							<?php printLinkHTML(getAdminLink('admin-tabs/users.php'), gettext("My profile"), NULL, NULL, NULL); ?>
						</li>
						<?php
					}
				}


				if (!npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
					$sql = 'SELECT `filename` FROM ' . prefix('images') . ' WHERE `owner`=' . db_quote($_current_admin_obj->getUser()) . ' LIMIT 1';
					$found = query($sql);
					if ($found && $found->num_rows > 0) {
						?>
						<li>
							<?php printLinkHTML(getAdminLink('admin-tabs/images.php') . '?page=admin&tab=images', gettext("My images"), NULL, NULL, NULL); ?>
						</li>
						<?php
					}
				}

				if (npg_loggedin(OPTIONS_RIGHTS)) {
					?>
					<li>
						<?php printLinkHTML(getAdminLink('admin-tabs/options.php') . '?tab=general', gettext("Options"), NULL, NULL, NULL); ?>
					</li>
					<?php
				}
				if (npg_loggedin(THEMES_RIGHTS)) {
					?>
					<li>
						<?php printLinkHTML(getAdminLink('admin-tabs/themes.php'), gettext("Themes"), NULL, NULL, NULL); ?>
					</li>
					<?php
				}
				if (npg_loggedin(ADMIN_RIGHTS)) {
					?>
					<li>
						<?php printLinkHTML(getAdminLink('admin-tabs/plugins.php'), gettext("Plugins"), NULL, NULL, NULL); ?>
					</li>
					<li>
						<?php printLinkHTML(getAdminLink('admin-tabs/logs.php'), gettext("Logs"), NULL, NULL, NULL); ?>
					</li>
					<?php
				}

				$inImage = false;
				switch ($_gallery_page) {
					case 'index.php':
					case 'gallery.php':
						// script is either index.php or the gallery index page
						if (npg_loggedin(ADMIN_RIGHTS)) {
							?>
							<li>
								<?php printLinkHTML(getAdminLink('admin-tabs/edit.php') . '?page=edit', gettext("Sort Gallery"), NULL, NULL, NULL); ?>
							</li>
							<?php
						}
						if (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS)) {
							?>
							<li>
								<a href="javascript:newAlbum('',true);"><?php echo gettext("New Album"); ?></a>
							</li>
							<?php
						}
						npgFilters::apply('admin_toolbox_gallery');
						break;
					case 'image.php':
						$inImage = true; // images are also in albums[sic]
					case 'album.php':
						// script is album.php
						$albumname = $_current_album->name;
						if ($_current_album->isMyItem(ALBUM_RIGHTS)) {
							// admin is empowered to edit this album--show an edit link
							if ($inImage) {
								$imagepart = '&i=' . $_current_image->filename;
							} else {
								$imagepart = '';
							}
							?>
							<li>
								<?php printLinkHTML(getAdminLink('admin-tabs/edit.php') . '?page=edit&album=' . pathurlencode($_current_album->name) . '&subpage=object' . $imagepart, gettext('Edit album'), NULL, NULL, NULL); ?>
							</li>
							<?php
							if (!$_current_album->isDynamic()) {
								if ($_current_album->getNumAlbums()) {
									?>
									<li>
										<?php printLinkHTML(getAdminLink('admin-tabs/edit.php') . '?page=edit&album=' . pathurlencode($albumname) . '&tab=subalbuminfo', gettext("Sort subalbums"), NULL, NULL, NULL); ?>
									</li>
									<?php
								}
								if ($_current_album->getNumImages() > 0) {
									?>
									<li>
										<?php printLinkHTML(getAdminLink('admin-tabs/albumsort.php') . '?page=edit&album=' . pathurlencode($albumname) . '&tab=sort', gettext("Sort images"), NULL, NULL, NULL); ?>
									</li>
									<?php
								}
							}
							// and a delete link
							?>
							<script type="text/javascript">
								// <!-- <![CDATA[
								function confirmAlbumDelete() {
									if (confirm("<?php echo gettext("Are you sure you want to delete this entire album?"); ?>")) {
										if (confirm("<?php echo gettext("Are you Absolutely Positively sure you want to delete the album? THIS CANNOT BE UNDONE!"); ?>")) {
											window.location = '<?php echo getAdminLink('admin-tabs/edit.php'); ?>?page=edit&action=deletealbum&album=<?php echo pathurlencode($albumname) ?>&XSRFToken=<?php echo getXSRFToken('delete'); ?>';
														}
													}
												}
												// ]]> -->
							</script>
							<li>
								<a href="javascript:confirmAlbumDelete();" title="<?php echo gettext('Delete the album'); ?>"><?php echo gettext('Delete album'); ?></a>
							</li>
							<?php
						}
						if ($_current_album->isMyItem(UPLOAD_RIGHTS) && !$_current_album->isDynamic()) {
							// provide an album upload link if the admin has upload rights for this album and it is not a dynamic album
							?>
							<li>
								<?php printLinkHTML(getAdminLink('admin-tabs/upload.php') . '?album=' . pathurlencode($albumname), gettext("Upload here"), NULL, NULL, NULL); ?>
							</li>
							<li>
								<a href="javascript:newAlbum('<?php echo pathurlencode($albumname); ?>',true);"><?php echo gettext("New subalbum"); ?></a>
							</li>
							<?php
						}
					case 'favorites.php';
						$albumname = $_current_album->name;
						npgFilters::apply('admin_toolbox_album', $albumname);
						if ($inImage) {
							// script is image.php
							$imagename = $_current_image->filename;
							if (!$_current_album->isDynamic()) { // don't provide links when it is a dynamic album
								if ($_current_album->isMyItem(ALBUM_RIGHTS)) {
									// if admin has edit rights on this album, provide a delete link for the image.
									?>
									<script type='text/javascript'>
										function confirmImageDelete() {
											if (confirm('<?php echo gettext("Are you sure you want to delete the image? THIS CANNOT BE UNDONE!"); ?>')) {
												window.location = '<?php echo getAdminLink('admin-tabs/edit.php'); ?>?page=edit&action=deleteimage&album=<?php echo pathurlencode($albumname); ?>&image=<?php echo urlencode($imagename); ?>&XSRFToken=<?php echo getXSRFToken('delete'); ?>';
														}
													}
									</script>

									<li>
										<a href="javascript:confirmImageDelete();" title="<?php echo gettext("Delete the image"); ?>">
											<?php echo gettext("Delete image"); ?>
										</a>
									</li>
									<li>
										<a href="<?php echo getAdminLink('admin-tabs/edit.php'); ?>?page=edit&amp;album=<?php echo pathurlencode($albumname); ?>&amp;singleimage=<?php echo urlencode($imagename); ?>&amp;tab=imageinfo&amp;subpage=object"
											 title="<?php echo gettext('Edit image'); ?>"><?php echo gettext('Edit image'); ?></a>
									</li>
									<?php
								}
								// set return to this image page
								npgFilters::apply('admin_toolbox_image', $albumname, $imagename);
							}
						}
						break;
					case 'search.php':
						$words = $_current_search->getSearchWords();
						if (!empty($words)) {
							// script is search.php with a search string
							if (npg_loggedin(UPLOAD_RIGHTS)) {
								$link = getAdminLink('admin-tabs/dynamic-album.php') . '?' . substr($_current_search->getSearchParams(), 1);
								// if admin has edit rights allow him to create a dynamic album from the search
								?>
								<li>
									<a href="<?php echo $link; ?>" title="<?php echo gettext('Create an album from the search'); ?>" ><?php echo gettext('Create Album'); ?></a>
								</li>
								<?php
							}
							npgFilters::apply('admin_toolbox_search');
						}
						break;
					case 'pages.php':
						npgFilters::apply('admin_toolbox_pages');
						break;
					case 'news.php':
						npgFilters::apply('admin_toolbox_news');
						break;
					default:
						// arbitrary custom page
						npgFilters::apply('admin_toolbox_' . stripSuffix($_gallery_page));
						break;
				}
				npgFilters::apply('admin_toolbox_close');
				if ($_current_admin_obj->logout_link) {
					// logout link
					?>
					<li>
						<a href="<?php echo getLogoutLink(array('logout' => 2)); ?>" id="toolbox_logout"><?php echo gettext("Logout"); ?> </a>
					</li>
				</ul>
			</div>
			<?php
		}
	}
}

//*** Gallery Index (album list) Context ***
//******************************************

/**
 * Returns the raw title of the gallery.
 *
 * @return string
 */
function getGalleryTitle() {
	global $_gallery;
	return $_gallery->getTitle();
}

/**
 * Returns a text-only title of the gallery.
 *
 * @return string
 */
function getBareGalleryTitle() {
	return getBare(getGalleryTitle());
}

/**
 * Prints the title of the gallery.
 */
function printGalleryTitle() {
	echo html_encodeTagged(getGalleryTitle());
}

function printBareGalleryTitle() {
	echo html_encode(getBareGalleryTitle());
}

/**
 * Function to create the page title to be used within the html <head> <title></title> element.
 * Useful if you use one header.php for the header of all theme pages instead of individual ones on the theme pages
 * It returns the title and site name in reversed breadcrumb order:
 * <title of current page> | <parent item if present> | <gallery title>
 * It supports standard gallery pages as well a custom and Zenpage news articles, categories and pages.
 *
 * @param string $separator How you wish the parts to be separated
 * @param bool $listparents If the parent objects should be printed in reversed order before the current
 */
function getHeadTitle($separator = ' | ', $listparents = true) {
	global $_gallery, $_current_album, $_current_image, $_CMS_current_article, $_CMS_current_page, $_gallery_page, $_CMS_current_category, $_current_page, $_myFavorites;
	$mainsitetitle = html_encode(getBare(getMainSiteName()));
	$separator = html_encode($separator);
	if ($mainsitetitle) {
		$mainsitetitle = $separator . $mainsitetitle;
	}
	$gallerytitle = html_encode(getBareGalleryTitle());
	if ($_current_page > 1) {
		$pagenumber = ' (' . $_current_page . ')';
	} else {
		$pagenumber = '';
	}
	switch ($_gallery_page) {
		case 'index.php':
			return $gallerytitle . $mainsitetitle . $pagenumber;
			break;
		case 'album.php':
		case 'favorites.php';
		case 'image.php':
			if ($listparents) {
				$parents = getParentAlbums();
				$parentalbums = '';
				if (count($parents) != 0) {
					$parents = array_reverse($parents);
					foreach ($parents as $parent) {
						$parentalbums .= html_encode(getBare($parent->getTitle())) . $separator;
					}
				}
			} else {
				$parentalbums = '';
			}

			$albumtitle = html_encode(getBareAlbumTitle()) . $pagenumber . $separator . $parentalbums . $gallerytitle . $mainsitetitle;
			switch ($_gallery_page) {
				case 'album.php':
				case 'favorites.php';
					return $albumtitle;
					break;
				case 'image.php':
					return html_encode(getBareImageTitle()) . $separator . $albumtitle;
					break;
			}
			break;
		case 'news.php':
			if (function_exists("is_NewsArticle")) {
				if (is_NewsArticle()) {
					return html_encode(getBareNewsTitle()) . $pagenumber . $separator . NEWS_LABEL . $separator . $gallerytitle . $mainsitetitle;
				} else if (is_NewsCategory()) {
					return html_encode(getBare($_CMS_current_category->getTitle())) . $pagenumber . $separator . NEWS_LABEL . $separator . $gallerytitle . $mainsitetitle;
				} else {
					return NEWS_LABEL . $pagenumber . $separator . $gallerytitle . $mainsitetitle;
				}
			}
			break;
		case 'pages.php':
			if ($listparents) {
				$parents = $_CMS_current_page->getParents();
				$parentpages = '';
				if (count($parents) != 0) {
					$parents = array_reverse($parents);
					foreach ($parents as $parent) {
						$obj = newPage($parent);
						$parentpages .= html_encode(getBare($obj->getTitle())) . $separator;
					}
				}
			} else {
				$parentpages = '';
			}
			return html_encode(getBarePageTitle()) . $pagenumber . $separator . $parentpages . $gallerytitle . $mainsitetitle;
			break;
		case '404.php':
			return gettext('Object not found') . $separator . $gallerytitle . $mainsitetitle;
			break;
		default: // for all other possible static custom pages
			$custompage = stripSuffix($_gallery_page);
			$standard = array(
					'contact' => gettext('Contact'),
					'register' => gettext('Register'),
					'search' => gettext('Search'),
					'archive' => gettext('Archive view'),
					'password' => gettext('Password required')
			);
			if (is_object($_myFavorites)) {
				$standard['favorites'] = gettext('My favorites');
			}
			if (array_key_exists($custompage, $standard)) {
				return $standard[$custompage] . $pagenumber . $separator . $gallerytitle . $mainsitetitle;
			} else {
				return $custompage . $pagenumber . $separator . $gallerytitle . $mainsitetitle;
			}
			break;
	}
}

/**
 * Returns the raw description of the gallery.
 *
 * @return string
 */
function getGalleryDesc() {
	global $_gallery;
	return $_gallery->getDesc();
}

/**
 * Returns a text-only description of the gallery.
 *
 * @return string
 */
function getBareGalleryDesc() {
	return getBare(getGalleryDesc());
}

/**
 * Prints the description of the gallery.
 */
function printGalleryDesc() {
	echo html_encodeTagged(getGalleryDesc());
}

function printBareGalleryDesc() {
	echo html_encode(getBareGalleryDesc());
}

/**
 * Returns the name of the main website as set by the "Website Title" option
 * on the gallery options tab.
 *
 * @return string
 */
function getMainSiteName() {
	global $_gallery;
	return $_gallery->getWebsiteTitle();
}

/**
 * Returns the URL of the main website as set by the "Website URL" option
 * on the gallery options tab.
 *
 * @return string
 */
function getMainSiteURL() {
	global $_gallery;
	return $_gallery->getWebsiteURL();
}

/**
 * Returns the URL of the main gallery index.php page
 *
 * @return string
 */
function getGalleryIndexURL() {
	global $_gallery_page, $_current_album;
	$link = WEBPATH . "/";
	$page = getNPGCookie('index_page_paged');
	if ($page > 1) {
		$link = rewrite_path('/' . _PAGE_ . '/' . $page, "/index.php?" . "page=" . $page);
	}
	return npgFilters::apply('getLink', $link, 'index.php', NULL);
}

/**
 * Prints the above. Included for legacy compatibility
 * @global type $_gallery_page
 * @param type $after
 * @param type $text
 */
function printGalleryIndexURL($after = NULL, $text = NULL) {
	if (is_null($text)) {
		$text = gettext('Index');
	}
	printLinkHTML(getGalleryIndexURL(), $text, $text, 'galleryindexurl');
	echo $after;
}

/**
 * Returns the number of albums.
 *
 * @return int
 */
function getNumAlbums() {
	global $_gallery, $_current_album, $_current_search;
	if (in_context(NPG_SEARCH) && is_null($_current_album)) {
		return $_current_search->getNumAlbums();
	} else if (in_context(NPG_ALBUM)) {
		return $_current_album->getNumAlbums();
	} else {
		return $_gallery->getNumAlbums();
	}
}

/**
 * Returns the name of the currently active theme
 *
 * @return string
 */
function getCurrentTheme() {
	global $_gallery;
	return $_gallery->getCurrentTheme();
}

/* * * Album AND Gallery Context *********** */
/* * *************************************** */

/**
 * WHILE next_album(): context switches to Album.
 * If we're already in the album context, this is a sub-albums loop, which,
 * quite simply, changes the source of the album list.
 * Switch back to the previous context when there are no more albums.

 * Returns true if there are albums, false if none
 *
 * @param bool $all true to go through all the albums
 * @param bool $mine override the password checks
 * @return bool
 * @since 0.6
 */
function next_album($all = false, $mine = NULL) {
	global $__albums, $_gallery, $_current_album, $_current_page, $_current_album_restore, $_current_search;

	if (is_null($__albums)) {
		if (in_context(NPG_SEARCH)) {
			$__albums = $_current_search->getAlbums($all ? 0 : $_current_page, NULL, NULL, true, $mine);
		} else if (in_context(NPG_ALBUM)) {
			$__albums = $_current_album->getAlbums($all ? 0 : $_current_page, NULL, NULL, true, $mine);
		} else {
			$__albums = $_gallery->getAlbums($all ? 0 : $_current_page, NULL, NULL, true, $mine);
		}
		if (empty($__albums)) {
			$result = NULL;
		} else {
			$_current_album_restore = $_current_album;
			$album = reset($__albums);
			$_current_album = newAlbum($album, true, true);
			if ($_current_album_restore && $_current_album_restore->isDynamic()) {
				$_current_album->linkname = $_current_album_restore->linkname . '/' . basename($_current_album->linkname);
			}
			save_context();
			add_context(NPG_ALBUM);
			$result = true;
		}
	} else {
		$album = next($__albums);
		if ($album) {
			$_current_album = newAlbum($album, true, true);
			if ($_current_album_restore && $_current_album_restore->isDynamic()) {
				$_current_album->linkname = $_current_album_restore->linkname . '/' . basename($_current_album->name);
			}
			$result = true;
		} else {
			$__albums = NULL;
			$_current_album = $_current_album_restore;
			restore_context();
			$result = NULL;
		}
	}
	return npgFilters::apply('next_object_loop', $result, $_current_album);
}

/**
 * Returns the number of the current page without printing it.
 *
 * @return int
 */
function getCurrentPage() {
	global $_current_page;
	return $_current_page;
}

/**
 * Returns a list of all albums decendent from an album
 *
 * @param object $album optional album. If absent the current album is used
 * @return array
 */
function getAllAlbums($album = NULL) {
	global $_current_album, $_gallery;
	if (is_null($album))
		$album = $_current_album;
	if (!is_object($album))
		return;
	$list = array();
	$subalbums = $album->getAlbums(0);
	if (is_array($subalbums)) {
		foreach ($subalbums as $subalbum) {
			$list[] = $subalbum;
			$sub = newAlbum($subalbum);
			$list = array_merge($list, getAllAlbums($sub));
		}
	}
	return $list;
}

function getAlbumPageCount() {
	global $_gallery, $_current_album;
	if (in_context(NPG_ALBUM | NPG_SEARCH)) {
		$pageCount = ceil(getNumAlbums() / galleryAlbumsPerPage());
		return (int) $pageCount;
	}
	return 0;
}

/**
 * Returns the number of pages for the current object
 *
 * @return int
 */
function getTotalPages() {
	global $_gallery, $_current_album, $_transitionImageCount, $_CMS, $_CMS_current_category;
	if (in_context(NPG_ALBUM | NPG_SEARCH)) {
		$pageCount = getAlbumPageCount();
		$imageCount = getNumImages();
		if (!galleryImagesPerPage()) {
			$imageCount = min(1, $imageCount);
		}
		$images_per_page = max(1, galleryImagesPerPage());
		$pageCount = ($pageCount + ceil(($imageCount - $_transitionImageCount) / $images_per_page));
		return $pageCount;
	} else if (get_context() == NPG_INDEX) {
		return (int) ceil($_gallery->getNumAlbums() / max(1, galleryAlbumsPerPage()));
	} else if (isset($_CMS)) {
		if (in_context(ZENPAGE_NEWS_CATEGORY)) {
			$total = count($_CMS_current_category->getArticles(0));
		} else {
			$total = count($_CMS->getArticles(0));
		}
		return (int) ceil($total / ARTICLES_PER_PAGE);
	}
	return NULL;
}

/**
 * Returns the URL of the page number passed as a parameter
 *
 * @param int $page Which page is desired
 * @param int $total How many pages there are.
 * @return int
 */
function getPageNumURL($page, $total = null) {
	global $_current_album, $_gallery, $_current_search, $_gallery_page;
	if (is_null($total)) {
		$total = getTotalPages();
	}
	if ($page <= 0 || $page > $total) {
		return NULL;
	}
	if (in_context(NPG_SEARCH)) {
		$searchwords = $_current_search->codifySearchString();
		$searchdate = $_current_search->getSearchDate();
		$searchfields = $_current_search->getSearchFields(true);
		$searchpagepath = getSearchURL($searchwords, $searchdate, $searchfields, $page, array('albums' => $_current_search->getAlbumList()));
		return $searchpagepath;
	} else if (in_context(NPG_ALBUM)) {
		return $_current_album->getLink($page);
	} else if (in_array($_gallery_page, array('index.php', 'album.php', 'image.php'))) {
		if (in_context(NPG_INDEX)) {
			$pagination1 = '/';
			$pagination2 = 'index.php';
			if ($page > 1) {
				$pagination1 .= _PAGE_ . '/' . $page;
				$pagination2 .= '?page=' . $page;
			}
		} else {
			return NULL;
		}
	} else {
// handle custom page
		$pg = stripSuffix($_gallery_page);
		$pagination1 = getCustomPageRewrite($pg) . '/';
		$pagination2 = 'index.php?p=' . $pg;
		if ($page > 1) {
			$pagination1 .= $page;
			$pagination2 .= '&page=' . $page;
		}
	}
	return npgFilters::apply('getLink', rewrite_path($pagination1, $pagination2), $_gallery_page, $page);
}

/**
 * Returns true if there is a next page
 *
 * @return bool
 */
function hasNextPage() {
	return (getCurrentPage() < getTotalPages());
}

/**
 * Returns the URL of the next page. Use within If or while loops for pagination.
 *
 * @return string
 */
function getNextPageURL() {
	return getPageNumURL(getCurrentPage() + 1);
}

/**
 * Prints the URL of the next page.
 *
 * @param string $text text for the URL
 * @param string $title Text for the HTML title
 * @param string $class Text for the HTML class
 * @param string $id Text for the HTML id
 */
function printNextPageURL($text, $title = NULL, $class = false, $id = NULL) {
	if (hasNextPage()) {
		printLinkHTML(getNextPageURL(), $text, $title, $class, $id);
	} else {
		echo "<span class=\"disabledlink\">$text</span>";
	}
}

/**
 * Returns TRUE if there is a previous page. Use within If or while loops for pagination.
 *
 * @return bool
 */
function hasPrevPage() {
	return (getCurrentPage() > 1);
}

/**
 * Returns the URL of the previous page.
 *
 * @return string
 */
function getPrevPageURL() {
	return getPageNumURL(getCurrentPage() - 1);
}

/**
 * Returns the URL of the previous page.
 *
 * @param string $text The linktext that should be printed as a link
 * @param string $title The text the html-tag "title" should contain
 * @param string $class Insert here the CSS-class name you want to style the link with
 * @param string $id Insert here the CSS-ID name you want to style the link with
 */
function printPrevPageURL($text, $title = NULL, $class = false, $id = NULL) {
	if (hasPrevPage()) {
		printLinkHTML(getPrevPageURL(), $text, $title, $class, $id);
	} else {
		echo "<span class=\"disabledlink\">$text</span>";
	}
}

/**
 * Prints a page navigation including previous and next page links
 *
 * @param string $prevtext Insert here the linktext like 'previous page'
 * @param string $separator Insert here what you like to be shown between the prev and next links
 * @param string $nexttext Insert here the linktext like "next page"
 * @param string $class Insert here the CSS-class name you want to style the link with (default is "pagelist")
 * @param string $id Insert here the CSS-ID name if you want to style the link with this
 */
function printPageNav($prevtext, $separator, $nexttext, $class = 'pagenav', $id = NULL) {
	echo "<div" . (($id) ? " id=\"$id\"" : "") . " class=\"$class\">";
	printPrevPageURL($prevtext, gettext("Previous Page"));
	echo " $separator ";
	printNextPageURL($nexttext, gettext("Next Page"));
	echo "</div>\n";
}

/**
 * Prints a list of all pages.
 *
 * @param string $class the css class to use, "pagelist" by default
 * @param string $id the css id to use
 * @param int $navlen Number of navigation links to show (0 for all pages). Works best if the number is odd.
 */
function printPageList($class = 'pagelist', $id = NULL, $navlen = 9) {
	printPageListWithNav(null, null, false, false, $class, $id, false, $navlen);
}

/**
 * returns a page nav list.
 *
 * @param int $navlen Number of navigation links to show (0 for all pages). Works best if the number is odd.
 * @param bool $firstlast Add links to the first and last pages of you gallery
 * @param int $current the current page
 * @param int $total total number of pages
 *
 */
function getPageNavList($navlen, $firstlast, $current, $total) {
	$result = array();
	if (hasPrevPage()) {
		$result['prev'] = getPrevPageURL();
	} else {
		$result['prev'] = NULL;
	}
	if ($firstlast) {
		$result[1] = getPageNumURL(1, $total);
	}

	if ($navlen == 0) {
		$navlen = $total;
	}
	$extralinks = 2;
	if ($firstlast) {
		$extralinks = $extralinks + 2;
	}
	$len = floor(($navlen - $extralinks) / 2);
	$j = max(round($extralinks / 2), min($current - $len - (2 - round($extralinks / 2)), $total - $navlen + $extralinks - 1));
	$ilim = min($total, max($navlen - round($extralinks / 2), $current + floor($len)));
	$k1 = round(($j - 2) / 2) + 1;
	$k2 = $total - round(($total - $ilim) / 2);

	for ($i = $j; $i <= $ilim; $i++) {
		$result[$i] = getPageNumURL($i, $total);
	}
	if ($firstlast) {
		$result[$total] = getPageNumURL($total, $total);
	}
	if (hasNextPage()) {
		$result['next'] = getNextPageURL();
	} else {
		$result['next'] = NULL;
	}
	return $result;
}

/**
 * Prints a full page navigation including previous and next page links with a list of all pages in between.
 *
 * @param string $prevtext Insert here the linktext like 'previous page'
 * @param string $nexttext Insert here the linktext like 'next page'
 * @param bool $deprecated set to true if there is only one image page as, for instance, in flash themes
 * @param string $nextprev set to true to get the 'next' and 'prev' links printed
 * @param string $class Insert here the CSS-class name you want to style the link with (default is "pagelist")
 * @param string $id Insert here the CSS-ID name if you want to style the link with this
 * @param bool $firstlast Add links to the first and last pages of you gallery
 * @param int $navlen Number of navigation links to show (0 for all pages). Works best if the number is odd.
 */
function printPageListWithNav($prevtext, $nexttext, $deprecated = NULL, $nextprev = true, $class = 'pagelist', $id = NULL, $firstlast = true, $navlen = 9) {
	$total = max(1, getTotalPages());
	if ($total > 1) {
		$current = getCurrentPage();
		$nav = getPageNavList($navlen, $firstlast, $current, $total);
		?>
		<div <?php if ($id) echo ' id="' . $id . '"'; ?> class="<?php echo $class; ?>">
			<ul class="<?php echo $class; ?>">
				<?php
				$prev = $nav['prev'];
				unset($nav['prev']);
				$next = $nav['next'];
				unset($nav['next']);
				if ($nextprev) {
					?>
					<li class="prev">
						<?php
						if ($prev) {
							printLinkHTML($prev, html_encode($prevtext), gettext('Previous Page'));
						} else {
							?>
							<span class="disabledlink"><?php echo html_encode($prevtext); ?></span>
							<?php
						}
						?>
					</li>
					<?php
				}
				$last = NULL;
				if ($firstlast) {
					?>
					<li class="<?php
					if ($current == 1)
						echo 'current';
					else
						echo 'first';
					?>">
								<?php
								if ($current == 1) {
									echo '1';
								} else {
									printLinkHTML($nav[1], 1, gettext("Page 1"));
								}
								?>
					</li>
					<?php
					$last = 1;
					unset($nav[1]);
				}
				foreach ($nav as $i => $link) {
					$d = $i - $last;
					if ($d > 2) {
						?>
						<li>
							<?php
							$k1 = $i - (int) (($i - $last) / 2);
							printLinkHTML(getPageNumURL($k1, $total), '...', sprintf(ngettext('Page %u', 'Page %u', $k1), $k1));
							?>
						</li>
						<?php
					} else if ($d == 2) {
						?>
						<li>
							<?php
							$k1 = $last + 1;
							printLinkHTML(getPageNumURL($k1, $total), $k1, sprintf(ngettext('Page %u', 'Page %u', $k1), $k1));
							?>
						</li>
						<?php
					}
					?>
					<li<?php if ($current == $i) echo ' class="current"'; ?>>
						<?php
						if ($i == $current) {
							echo $i;
						} else {
							$title = sprintf(ngettext('Page %1$u', 'Page %1$u', $i), $i);
							printLinkHTML($link, $i, $title);
						}
						?>
					</li>
					<?php
					$last = $i;
					unset($nav[$i]);
					if ($firstlast && count($nav) == 1) {
						break;
					}
				}
				if ($firstlast) {
					foreach ($nav as $i => $link) {
						$d = $i - $last;
						if ($d > 2) {
							$k1 = $i - (int) (($i - $last) / 2);
							?>
							<li>
								<?php printLinkHTML(getPageNumURL($k1, $total), '...', sprintf(ngettext('Page %u', 'Page %u', $k1), $k1)); ?>
							</li>
							<?php
						} else if ($d == 2) {
							$k1 = $last + 1;
							?>
							<li>
								<?php printLinkHTML(getPageNumURL($k1, $total), $k1, sprintf(ngettext('Page %u', 'Page %u', $k1), $k1)); ?>
							</li>
							<?php
						}
						?>
						<li class="last<?php if ($current == $i) echo ' current'; ?>">
							<?php
							if ($current == $i) {
								echo $i;
							} else {
								printLinkHTML($link, $i, sprintf(ngettext('Page %u', 'Page %u', $i), $i));
							}
							?>
						</li>
						<?php
					}
				}
				if ($nextprev) {
					?>
					<li class="next">
						<?php
						if ($next) {
							printLinkHTML($next, html_encode($nexttext), gettext('Next Page'));
						} else {
							?>
							<span class="disabledlink"><?php echo html_encode($nexttext); ?></span>
							<?php
						}
						?>
					</li>
					<?php
				}
				?>
			</ul>
		</div>
		<?php
	}
}

//*** Album Context ************************
//******************************************

/**
 * Sets the album passed as the current album
 *
 * @param object $album the album to be made current
 */
function makeAlbumCurrent($album) {
	global $_current_album;
	$_current_album = $album;
	set_context(NPG_INDEX | NPG_ALBUM);
}

/**
 * Returns the raw title of the current album.
 *
 * @return string
 */
function getAlbumTitle() {
	if (!in_context(NPG_ALBUM))
		return false;
	global $_current_album;
	return $_current_album->getTitle();
}

/**
 * Returns a text-only title of the current album.
 *
 * @return string
 */
function getBareAlbumTitle() {
	return getBare(getAlbumTitle());
}

/**
 * Returns an album title taged with of Not visible or password protected status
 *
 * @return string;
 */
function getAnnotatedAlbumTitle() {
	global $_current_album;
	$title = getBareAlbumTitle();
	$pwd = $_current_album->getPassword();
	if (npg_loggedin() && !empty($pwd)) {
		$title .= "\n" . gettext('The album is password protected.');
	}
	if (!$_current_album->getShow()) {
		$title .= "\n" . gettext('The album is un-published.');
	}
	return $title;
}

function printAnnotatedAlbumTitle() {
	echo html_encode(getAnnotatedAlbumTitle());
}

/**
 * Prints an encapsulated title of the current album.
 * If you are logged in you can click on this to modify the title on the fly.
 *
 * @author Ozh
 */
function printAlbumTitle() {
	echo html_encodeTagged(getAlbumTitle());
}

function printBareAlbumTitle($length = 35) {
	echo html_encode(truncate_string(getBareAlbumTitle(), $length));
}

/**
 * Gets the 'n' for n of m albums
 *
 * @return int
 */
function albumNumber() {
	global $_current_album, $_current_image, $_current_search, $_gallery;
	$name = $_current_album->getFileName();
	if (in_context(NPG_SEARCH)) {
		$albums = $_current_search->getAlbums();
	} else if (in_context(NPG_ALBUM)) {
		$parent = $_current_album->getParent();
		if (is_null($parent)) {
			$albums = $_gallery->getAlbums();
		} else {
			$albums = $parent->getAlbums();
		}
	}
	$c = 0;
	foreach ($albums as $albumfolder) {
		$c++;
		if ($name == $albumfolder) {
			return $c;
		}
	}
	return false;
}

/**
 * Returns an array of the names of the parents of the current album.
 *
 * @param object $album optional album object to use inseted of the current album
 * @return array
 */
function getParentAlbums($album = null) {
	global $_current_album, $_current_search, $_gallery;
	$parents = array();
	if (in_context(NPG_ALBUM)) {
		if (is_null($album)) {
			if (in_context(SEARCH_LINKED) && !in_context(ALBUM_LINKED)) {
				$album = $_current_search->getDynamicAlbum();
				if (empty($album)) {
					return $parents;
				}
			} else {
				$album = $_current_album;
			}
		}
		$parentNames = $album->parentLinks;
		if ($parentNames) {
			foreach ($parentNames as $alb) {
				$album = newAlbum($alb);
				$parents[] = $album;
			}
		} else {
			while (!is_null($album = $album->getParent())) {
				array_unshift($parents, $album);
			}
		}
	}
	return $parents;
}

/**
 * returns the breadcrumb item for the current image's album
 *
 * @param string $title Text to be used as the URL title tag
 * @return array
 */
function getAlbumBreadcrumb($title = NULL) {
	global $_current_search, $_gallery, $_current_album, $_last_album;
	$output = array();
	if (in_context(SEARCH_LINKED)) {
		$album = NULL;
		$dynamic_album = $_current_search->getDynamicAlbum();
		if (empty($dynamic_album)) {
			if (!is_null($_current_album)) {
				if (in_context(ALBUM_LINKED) && $_last_album == $_current_album->name) {
					$album = $_current_album;
				}
			}
		} else {
			if (in_context(NPG_IMAGE) && in_context(ALBUM_LINKED)) {
				$album = $_current_album;
			} else {
				if ($_current_album) {
					$dynamic_album->linkname = $_current_album->linkname;
					$dynamic_album->parentLinks = $_current_album->parentLinks;
					$dynamic_album->index = $_current_album->index;
				}
				$album = $dynamic_album;
			}
		}
	} else {
		$album = $_current_album;
	}
	if ($album) {
		if (is_null($title)) {
			$title = $album->getTitle();
			if (empty($title)) {
				$title = gettext('Album Thumbnails');
			}
		}
		return array('link' => $album->getLink(getAlbumPage()), 'text' => $title, 'title' => truncate_string(getBare($album->getDesc()), 100));
	}
	return false;
}

/**
 * prints the breadcrumb item for the current image's album
 *
 * @param string $before Text to place before the breadcrumb
 * @param string $after Text to place after the breadcrumb
 * @param string $title Text to be used as the URL title tag
 */
function printAlbumBreadcrumb($before = '', $after = '', $title = NULL) {
	if ($breadcrumb = getAlbumBreadcrumb($title)) {
		if ($before) {
			$output = '<span class="beforetext">' . html_encode($before) . '</span>';
		} else {
			$output = '';
		}
		$output .= '<a href="' . html_encode($breadcrumb['link']) . '" title="' . html_encode($breadcrumb['title']) . '">';
		$output .= html_encode($breadcrumb['text']);
		$output .= '</a>';
		if ($after) {
			$output .= '<span class="aftertext">' . html_encode($after) . '</span>';
		}
		echo $output;
	}
}

/**
 * Prints the "breadcrumb" for a search page
 * 		if the search was for a data range, the breadcrumb is "Archive"
 * 		otherwise it is "Search"
 * @param string $between Insert here the text to be printed between the links
 * @param string $class is the class for the link (if present)
 * @param string $search text for a search page title
 * @param string $archive text for an archive page title
 * @param string $format data format for archive page crumb
 */
function printSearchBreadcrumb($between = NULL, $class = false, $search = NULL, $archive = NULL, $format = 'F Y') {
	global $_current_search;
	if (is_null($between)) {
		$between = ' | ';
	}
	if ($class) {
		$class = ' class="' . $class . '"';
	}
	if ($d = $_current_search->getSearchDate()) {
		if (is_null($archive)) {
			$text = gettext('Archive');
			$textdecoration = true;
		} else {
			$text = html_encode(getBare($archive));
			$textdecoration = false;
		}
		echo "<a href=\"" . html_encode(getCustomPageURL('archive')) . "\"$class title=\"" . $text . "\">";
		printf('%s' . $text . '%s', $textdecoration ? '<em>' : '', $textdecoration ? '</em>' : '');
		echo "</a>";
		echo '<span class="betweentext">' . html_encode($between) . '</span>';
		if ($format) {
			$d = strtotime($d);
			$d = date($format, $d);
		}
		echo $d;
	} else {
		if (is_null($search)) {
			$text = gettext('Search');
			$textdecoration = true;
		} else {
			$text = html_encode(getBare($search));
			$textdecoration = false;
		}
		printf('%s' . $text . '%s', $textdecoration ? '<em>' : '', $textdecoration ? '</em>' : '');
	}
}

/**
 * returns the breadcrumb navigation for album, gallery and image view.
 *
 * @return array
 */
function getParentBreadcrumb() {
	global $_gallery, $_current_search, $_current_album, $_last_album;
	$output = array();
	if (in_context(SEARCH_LINKED)) {
		$page = $_current_search->page;
		$searchwords = $_current_search->getSearchWords();
		$searchdate = $_current_search->getSearchDate();
		$searchfields = $_current_search->getSearchFields(true);
		$search_album_list = $_current_search->getAlbumList();
		if (!is_array($search_album_list)) {
			$search_album_list = array();
		}
		$searchpagepath = getSearchURL($searchwords, $searchdate, $searchfields, $page, array('albums' => $search_album_list));
		$dynamic_album = $_current_search->getDynamicAlbum();
		if (empty($dynamic_album)) {
			if (empty($searchdate)) {
				$output[] = array('link' => $searchpagepath, 'title' => gettext("Return to search"), 'text' => gettext("Search"));
				if (is_null($_current_album)) {
					return $output;
				} else {
					$parents = getParentAlbums();
				}
			} else {
				return array(array('link' => $searchpagepath, 'title' => gettext("Return to archive"), 'text' => gettext("Archive")));
			}
		} else {
			if ($_current_album) {
				$dynamic_album->linkname = $_current_album->linkname;
				$dynamic_album->parentLinks = $_current_album->parentLinks;
				$dynamic_album->index = $_current_album->index;
			}
			$album = $dynamic_album;
			$parents = getParentAlbums($album);
			if (in_context(ALBUM_LINKED)) {
				array_push($parents, $album);
			}
		}



		if (!empty($dynamic_album)) {
			// remove parent links that are not in the search path
			foreach ($parents as $key => $analbum) {
				$target = $analbum->name;
				if ($target !== $dynamic_album->name && !in_array($target, $search_album_list)) {
					unset($parents[$key]);
				}
			}
		}
	} else {
		$parents = getParentAlbums();
	}

	$n = count($parents);
	if ($n > 0) {
		//the following loop code is @Copyright 2016 by Stephen L Billard for use in netPhotoGraphics and derivitives
		array_push($parents, $_current_album);
		$parent = reset($parents);
		while ($parent != $_current_album) {
			$fromAlbum = next($parents);
			//cleanup things in description for use as attribute tag
			$desc = getBare(preg_replace('|</p\s*>|i', '</p> ', preg_replace('|<br\s*/>|i', ' ', $parent->getDesc())));
			$output[] = array('link' => html_encode($parent->getLink($fromAlbum->getGalleryPage())), 'title' => $desc, 'text' => $parent->getTitle());
			$parent = $fromAlbum;
		}
	}
	return $output;
}

/**
 * Prints the breadcrumb navigation for album, gallery and image view.
 *
 * @param string $before Insert here the text to be printed before the links
 * @param string $between Insert here the text to be printed between the links
 * @param string $after Insert here the text to be printed after the links
 * @param mixed $truncate if not empty, the max lenght of the description.
 * @param string $elipsis the text to append to the truncated description
 */
function printParentBreadcrumb($before = NULL, $between = NULL, $after = NULL, $truncate = NULL, $elipsis = NULL) {
	$crumbs = getParentBreadcrumb();
	if (!empty($crumbs)) {
		if (is_null($between)) {
			$between = ' | ';
		}
		if (is_null($after)) {
			$after = ' | ';
		}
		if (is_null($elipsis)) {
			$elipsis = '...';
		}
		if ($before) {
			$output = '<span class="beforetext">' . html_encode($before) . '</span>';
		} else {
			$output = '';
		}
		if ($between) {
			$between = '<span class="betweentext">' . html_encode($between) . '</span>';
		}
		$i = 0;
		foreach ($crumbs as $crumb) {
			if ($i > 0) {
				$output .= $between;
			}
//cleanup things in description for use as attribute tag
			$desc = getBare($crumb['title']);
			if (!empty($desc) && $truncate) {
				$desc = truncate_string($desc, $truncate, $elipsis);
			}
			$output .= '<a href="' . html_encode($crumb['link']) . '"' . ' title="' . html_encode(getBare($desc)) . '">' . html_encode($crumb['text']) . '</a>';
			$i++;
		}
		if ($after) {
			$output .= '<span class="aftertext">' . html_encode($after) . '</span>';
		}
		echo $output;
	}
}

/**
 * Prints a link to the 'main website'
 * Only prints the link if the url is not empty and does not point back the gallery page
 *
 * @param string $before text to precede the link
 * @param string $after text to follow the link
 * @param string $title Title text
 * @param string $class optional css class
 * @param string $id optional css id
 *  */
function printHomeLink($before = '', $after = '', $title = NULL, $class = false, $id = NULL) {
	global $_gallery;
	$site = rtrim($_gallery->getWebsiteURL(), '/');
	if (!empty($site)) {
		$name = $_gallery->getWebsiteTitle();
		if (empty($name)) {
			$name = gettext('Home');
		}
		if ($site != SEO_FULLWEBPATH) {
			if ($before) {
				echo '<span class="beforetext">' . html_encode($before) . '</span>';
			}
			printLinkHTML($site, $name, $title, $class, $id);
			if ($after) {
				echo '<span class="aftertext">' . html_encode($after) . '</span>';
			}
		}
	}
}

/**
 * Returns the formatted date field of the album
 *
 * @param string $format optional format string for the date
 * @return string
 */
function getAlbumDate($format = null) {
	global $_current_album;
	$d = $_current_album->getDateTime();
	if (empty($d)) {
		return false;
	}
	if (is_null($format)) {
		return $d;
	}
	return formattedDate($format, strtotime($d));
}

/**
 * Prints the date of the current album
 *
 * @param string $before Insert here the text to be printed before the date.
 * @param string $format Format string for the date formatting
 */
function printAlbumDate($before = '', $format = NULL) {
	global $_current_album;
	if (is_null($format)) {
		$format = DATE_FORMAT;
	}
	$date = getAlbumDate($format);
	if ($date) {
		if ($before) {
			$date = '<span class="beforetext">' . html_encode($before) . '</span>' . $date;
		}
	}
	echo $date;
}

/**
 * Returns the Location of the album.
 *
 * @return string
 */
function getAlbumLocation() {
	global $_current_album;
	return $_current_album->getLocation();
}

/**
 * Prints the location of the album
 *
 * @author Ozh
 */
function printAlbumLocation() {
	echo html_encodeTagged(getAlbumLocation());
}

/**
 * Returns the raw description of the current album.
 *
 * @return string
 */
function getAlbumDesc() {
	if (!in_context(NPG_ALBUM))
		return false;
	global $_current_album;
	return $_current_album->getDesc();
}

/**
 * Returns a text-only description of the current album.
 *
 * @return string
 */
function getBareAlbumDesc() {
	return getBare(getAlbumDesc());
}

/**
 * Prints description of the current album
 *
 * @author Ozh
 */
function printAlbumDesc() {
	global $_current_album;
	echo html_encodeTagged(getAlbumDesc());
}

function printBareAlbumDesc() {
	echo html_encode(getBareAlbumDesc());
}

/**
 * A composit for getting album data
 *
 * @param string $field which field you want
 * @return string
 */
function getAlbumData($field) {
	if (!in_context(NPG_IMAGE))
		return false;
	global $_album_image;
	return get_language_string($_album_image->get($field));
}

/**
 * Prints arbitrary data from the album object
 *
 * @param string $field the field name of the data desired
 * @param string $label text to label the field
 * @author Ozh
 */
function printAlbumData($field, $label = '') {
	global $_current_album;
	echo html_encodeTagged($_current_album->get($field));
}

/**
 * Returns the album link url of the current album. If on an image page the link will
 * be to the page of the album that contains the image
 *
 * @return string
 */
function getAlbumURL() {
	global $_current_album;
	return $_current_album->getLink(getAlbumPage());
}

/**
 * Returns the album page number of the current image
 *
 * @return integer
 */
function getAlbumPage() {
	global $_current_album, $_current_image, $_current_search, $_transitionImageCount;
	if (in_context(NPG_IMAGE) && !in_context(NPG_SEARCH)) {
		$imageindex = $_current_image->getIndex();
		$numalbums = $_current_album->getNumAlbums();
		$albums_per_page = galleryAlbumsPerPage();
		$imagepage = floor(($imageindex - $_transitionImageCount) / max(1, galleryImagesPerPage())) + 1;
		$albumpages = ceil($numalbums / $albums_per_page);
		if ($albumpages == 0 && $_transitionImageCount > 0) {
			$imagepage++;
		}
		$page = $albumpages + $imagepage;
	} else {
		$page = 0;
	}
	return $page;
}

/**
 * Prints the album link url of the current album.
 *
 * @param string $text Insert the link text here.
 * @param string $title Insert the title text here.
 * @param string $class Insert here the CSS-class name with with you want to style the link.
 * @param string $id Insert here the CSS-id name with with you want to style the link.
 */
function printAlbumURL($text, $title, $class = false, $id = NULL) {
	printLinkHTML(getAlbumURL(), $text, $title, $class, $id);
}

/**
 * Returns the name of the defined album thumbnail image.
 *
 * @return string
 */
function getAlbumThumb() {
	global $_current_album;
	return $_current_album->getThumb();
}

/**
 * Returns an img src link to the password protect thumb substitute
 *
 * @param string $extra extra stuff to put in the HTML
 * @return string
 */
function getPasswordProtectImage($extra) {
	global $_themeroot;
	$image = '';
	$themedir = SERVERPATH . '/themes/' . basename($_themeroot);
	if (file_exists(internalToFilesystem($themedir . '/images/err-passwordprotected.png'))) {
		$image = $_themeroot . '/images/err-passwordprotected.png';
	} else {
		$image = WEBPATH . '/' . CORE_FOLDER . '/images/err-passwordprotected.png';
	}
	return '<img src="' . $image . '" ' . $extra . ' alt="protected" />';
}

/**
 * Prints the album thumbnail image.
 *
 * @param string $alt Insert the text for the alternate image name here.
 * @param string $class Insert here the CSS-class name with with you want to style the link.
 * @param string $id Insert here the CSS-id name with with you want to style the link.
 * @param string $title option title attribute
 *  */
function printAlbumThumbImage($alt, $class = false, $id = NULL, $title = NULL) {
	global $_current_album, $_themeroot;
	if (!$_current_album->getShow()) {
		$class .= " not_visible";
	}
	$pwd = $_current_album->getPassword();
	if (!empty($pwd)) {
		$class .= " password_protected";
	}

	$class = trim($class);
	if ($class) {
		$class = ' class="' . $class . '"';
	}
	if ($id) {
		$id = ' id="' . $id . '"';
	}

	if ($title) {
		if ($title === TRUE) {
			$title = $_current_album->getTitle();
		}
		$title = ' title="' . $title . '"';
	}
	$thumbobj = $_current_album->getAlbumThumbImage();
	$sizes = getSizeDefaultThumb($thumbobj);
	$size = ' width="' . $sizes[0] . '" height="' . $sizes[1] . '"';
	if (empty($pwd) || !getOption('use_lock_image') || $_current_album->isMyItem(LIST_RIGHTS) || $_current_album->checkforGuest()) {
		$html = '<img src="' . html_encode($thumbobj->getThumb('album')) . '"' . $size . ' alt="' . html_encode($alt) . '"' . $class . $id . $title . " />\n";
		$html = npgFilters::apply('standard_album_thumb_html', $html);
		if (ENCODING_FALLBACK) {
			$html = "<picture>\n<source srcset=\"" . html_encode($thumbobj->getThumb('album', NULL, FALLBACK_SUFFIX)) . "\">\n" . $html . "</picture>\n";
		}
		echo $html;
	} else {
		echo getPasswordProtectImage($size);
	}
}

/**
 * Returns a link to a custom sized thumbnail of the current album
 *
 * @param array $args of parameters
 * @param string suffix of imageURI
 *
 * @return string
 */
function getCustomAlbumThumb($args, $suffix = NULL) {
	global $_current_album;
	if (!is_array($args)) {
		$a = array('size', 'width', 'height', 'cw', 'ch', 'cx', 'cy', 'thumb', 'effects', 'suffix');
		$p = func_get_args();
		$args = array();
		foreach ($p as $k => $v) {
			$args[$a[$k]] = $v;
		}
		if (isset($args['suffix'])) {
			$suffix = $args['suffix'];
			unset($args['suffix']);
		} else {
			$suffix = NULL;
		}

		require_once(PLUGIN_SERVERPATH . 'deprecated-functions.php');
		deprecated_functions::notify_call('getCustomAlbumThumb', gettext('The function should be called with an image arguments array.') . sprintf(gettext(' e.g. %1$s '), npgFunctions::array_arg_example($args)));
	}
	$args['thumb'] = TRUE;
	$thumb = $_current_album->getAlbumThumbImage();
	return $thumb->getCustomImage($args, $suffix);
}

/**
 * Prints a link to a custom sized thumbnail of the current album
 *
 * See getCustomImageURL() for details.
 *
 * @param string $alt Alt attribute text
 * @param array $args image argument array
 * @param string $class css class
 * @param string $id html id for the element
 * @param string $title the title of the element
 *
 * @return string
 */
function printCustomAlbumThumbImage($alt, $args, $class = false, $id = NULL, $title = NULL) {
	global $_current_album;

	if (!is_array($args)) {
		$a = array(NULL, 'size', 'width', 'height', 'cw', 'ch', 'cx', 'cy', 'class', 'id', 'title');
		$p = func_get_args();
		unset($p[0]); //	$alt
		$args = array();
		foreach ($p as $k => $v) {
			$args[$a[$k]] = $v;
		}
		if (array_key_exists('class', $args)) {
			$class = $args['class'];
			unset($args['class']);
			if (is_null($class)) {
				$class = false;
			}
		} else {
			$class = false;
		}
		if (array_key_exists('id', $args)) {
			$id = $args['id'];
			unset($args['id']);
		} else {
			$id = NULL;
		}
		if (array_key_exists('title', $args)) {
			$title = $args['title'];
			unset($args['title']);
		} else {
			$title = NULL;
		}

		require_once(PLUGIN_SERVERPATH . 'deprecated-functions.php');
		deprecated_functions::notify_call('printCustomAlbumThumbImage', gettext('The function should be called with an image arguments array.') . sprintf(gettext(' e.g. %1$s '), npgFunctions::array_arg_example($args)));
	}

	$args['thumb'] = TRUE;

	$size = $width = $height = $cw = $ch = $cx = $cy = $thumb = NULL;
	extract($args);
	if (!$_current_album->getShow()) {
		$class .= " not_visible";
	}
	$pwd = $_current_album->getPassword();
	if (!empty($pwd)) {
		$class .= " password_protected";
	}
	$class = trim($class);
	/* set the HTML image width and height parameters in case this image was "imageDefault.png" substituted for no thumbnail then the thumb layout is preserved */
	$sizing = '';
	if (is_null($width)) {
		if (!is_null($cw) && !is_null($ch)) {
			if (empty($height)) {
				$height = $size;
			}
			$s = round($height * ($cw / $ch));
			if (!empty($s))
				$sizing = ' width = "' . $s . '"';
		}
	} else {
		$sizing = ' width = "' . $width . '"';
	}
	if (is_null($height)) {
		if (!is_null($cw) && !is_null($ch)) {
			if (empty($width)) {
				$width = $size;
			}
			$s = round($width * ($ch / $cw));
			if (!empty($s))
				$sizing = $sizing . ' height = "' . $s . '"';
		}
	} else {
		$sizing = $sizing . ' height = "' . $height . '"';
	}

	if ($id) {
		$id = ' id = "' . $id . '"';
	}
	if ($class) {
		$class = ' class = "' . $class . '"';
	}
	if (empty($title)) {
		$title = $alt;
	}
	if ($title) {
		if ($title === TRUE) {
			$title = $_current_album->getTitle();
		}
		$title = ' title = "' . html_encode($title) . '"';
	}

	if (empty($pwd) || !getOption('use_lock_image') || $_current_album->isMyItem(LIST_RIGHTS) || $_current_album->checkforGuest()) {
		$html = '<img src = "' . html_encode(getCustomAlbumThumb($args)) . '"' . $sizing . ' alt = "' . html_encode($alt) . '"' . $class . $id . $title . " />\n";
		$html = npgFilters::apply('custom_album_thumb_html', $html);
		if (ENCODING_FALLBACK) {
			$html = "<picture>\n<source srcset=\"" . html_encode(getCustomAlbumThumb($args, FALLBACK_SUFFIX)) . "\">\n" . $html . "</picture>\n";
		}
		echo $html;
	} else {
		echo getPasswordProtectImage($sizing);
	}
}

/**
 * Called by ***MaxSpace functions to compute the parameters to be passed to xxCustomyyy functions.
 *
 * @param int $width maxspace width
 * @param int $height maxspace height
 * @param object $image the image in question
 * @param bool $thumb true if for a thumbnail
 */
function getMaxSpaceContainer(&$width, &$height, $image, $thumb = false) {
	global $_gallery;
	$upscale = getOption('image_allow_upscale');
	$imagename = $image->filename;
	if (!$image->isPhoto() & $thumb) {
		$imgfile = $image->getThumbImageFile();
		$image = gl_imageGet($imgfile);
		$s_width = gl_imageWidth($image);
		$s_height = gl_imageHeight($image);
	} else {
		$s_width = $image->get('width');
		if ($s_width == 0)
			$s_width = max($width, $height, 1);
		$s_height = $image->get('height');
		if ($s_height == 0)
			$s_height = max($width, $height, 1);
	}

	$newW = round($height / $s_height * $s_width);
	$newH = round($width / $s_width * $s_height);
	if (DEBUG_IMAGE)
		debugLog("getMaxSpaceContainer($width, $height, $imagename, $thumb): \$s_width=$s_width; \$s_height=$s_height; \$newW=$newW; \$newH=$newH; \$upscale=$upscale;");
	if ($newW > $width) {
		if ($upscale || $s_height > $newH) {
			$height = $newH;
		} else {
			$height = $s_height;
			$width = $s_width;
		}
	} else {
		if ($upscale || $s_width > $newW) {
			$width = $newW;
		} else {
			$height = $s_height;
			$width = $s_width;
		}
	}
}

/**
 * Returns a link to a un-cropped custom sized version of the current album thumb within the given height and width dimensions.
 *
 * @param int $width width
 * @param int $height height
 * @param string $suffix
 * @return string
 */
function getCustomAlbumThumbMaxSpace($width, $height, $suffix = NULL) {
	global $_current_album;
	$albumthumb = $_current_album->getAlbumThumbImage();
	getMaxSpaceContainer($width, $height, $albumthumb, true);
	return getCustomAlbumThumb(array('width' => $width, 'height' => $height), $suffix);
}

/**
 * Prints a un-cropped custom sized album thumb within the given height and width dimensions.
 * Note: a class of 'not_visible' or 'password_protected' will be added as appropriate
 *
 * @param string $alt Alt text for the url
 * @param int $width width
 * @param int $height height
 * @param string $class Optional style class
 * @param string $id Optional style id
 * @param string $title optional title attribute
 */
function printCustomAlbumThumbMaxSpace($alt, $width, $height, $class = false, $id = NULL, $title = NULL) {
	global $_current_album;
	$albumthumb = $_current_album->getAlbumThumbImage();
	getMaxSpaceContainer($width, $height, $albumthumb, true);
	printCustomAlbumThumbImage($alt, array('width' => $width, 'height' => $height), $class, $id, $title);
}

/**
 * Returns the next album
 *
 * @return object
 */
function getNextAlbum() {
	global $_current_album, $_current_search, $_gallery;
	if (in_context(NPG_SEARCH) || in_context(SEARCH_LINKED)) {
		$nextalbum = $_current_search->getNextAlbum($_current_album->name);
	} else if (in_context(NPG_ALBUM)) {
		$nextalbum = $_current_album->getNextAlbum();
	} else {
		return null;
	}
	return $nextalbum;
}

/**
 * Get the URL of the next album in the gallery.
 *
 * @return string
 */
function getNextAlbumURL() {
	$nextalbum = getNextAlbum();
	if ($nextalbum) {
		return $nextalbum->getLink();
	}
	return false;
}

/**
 * Returns the previous album
 *
 * @return object
 */
function getPrevAlbum() {
	global $_current_album, $_current_search;
	if (in_context(NPG_SEARCH) || in_context(SEARCH_LINKED)) {
		$prevalbum = $_current_search->getPrevAlbum($_current_album->name);
	} else if (in_context(NPG_ALBUM)) {
		$prevalbum = $_current_album->getPrevAlbum();
	} else {
		return null;
	}
	return $prevalbum;
}

/**
 * Get the URL of the previous album in the gallery.
 *
 * @return string
 */
function getPrevAlbumURL() {
	$prevalbum = getPrevAlbum();
	if ($prevalbum) {
		return $prevalbum->getLink();
	}
	return false;
}

/**
 * Returns true if this page has image thumbs on it
 *
 * @return bool
 */
function isImagePage() {
	global $_current_page, $_transitionImageCount;
	$pageCount = (int) Ceil(getNumAlbums() / galleryAlbumsPerPage());
	return getNumImages() && ($_current_page > $pageCount || $_current_page == $pageCount && $_transitionImageCount);
}

/**
 * Returns true if this page has album thumbs on it
 *
 * @return bool
 */
function isAlbumPage() {
	global $_current_page;
	$pageCount = Ceil(max(1, getNumAlbums()) / galleryAlbumsPerPage());
	return ($_current_page <= $pageCount);
}

/**
 * Returns the number of images in the album.
 *
 * @return int
 */
function getNumImages() {
	global $_current_album, $_current_search;
	if ((in_context(SEARCH_LINKED) && !in_context(ALBUM_LINKED)) || in_context(NPG_SEARCH) && is_null($_current_album)) {
		return $_current_search->getNumImages();
	} else {
		return $_current_album->getNumImages();
	}
}

/**
 * Returns the count of all the images in the album and any subalbums
 *
 * @param object $album The album whose image count you want
 * @return int
 * @since 1.1.4
 */
function getTotalImagesIn($album) {
	global $_gallery, $__albums_visited_getTotalImagesIn;
	$__albums_visited_getTotalImagesIn[] = $album->name;
	$sum = $album->getNumImages();
	$subalbums = $album->getAlbums(0);
	while (count($subalbums) > 0) {
		$albumname = array_pop($subalbums);
		if (!in_array($albumname, $__albums_visited_getTotalImagesIn)) {
			$album = newAlbum($albumname);
			$sum = $sum + getTotalImagesIn($album);
		}
	}
	return $sum;
}

/**
 * Returns the next image on a page.
 * sets $_current_image to the next image in the album.

 * Returns true if there is an image to be shown
 *
 * @param bool $all set to true disable pagination
 * @param int $firstPageCount the number of images which can go on the page that transitions between albums and images
 * 							Normally this parameter should be NULL so as to use the default computations.
 * @param bool $mine overridePassword the password check
 * @return bool
 *
 * @return bool
 */
function next_image($all = false, $firstPageCount = NULL, $mine = NULL) {
	global $__images, $_current_image, $_current_album, $_current_page, $_current_image_restore, $_current_search, $_gallery, $_transitionImageCount, $_imagePageOffset;

	if (is_null($firstPageCount)) {
		$firstPageCount = $_transitionImageCount;
	}
	if (is_null($_imagePageOffset)) {
		$_imagePageOffset = getAlbumPageCount();
	}
	if ($all) {
		$imagePage = 1;
		$firstPageCount = 0;
	} else {
		$_transitionImageCount = $firstPageCount; /* save this so pagination can see it */
		$imagePage = $_current_page - $_imagePageOffset;
	}
	if ($firstPageCount > 0 && $_imagePageOffset > 0) {
		$imagePage = $imagePage + 1; /* can share with last album page */
	}
	if ($imagePage <= 0) {
		$result = false; /* we are on an album page */
	} else {
		if (is_null($__images)) {
			if (in_context(NPG_SEARCH)) {
				$__images = $_current_search->getImages($all ? 0 : ($imagePage), $firstPageCount, NULL, NULL, true, $mine);
			} else {
				$__images = $_current_album->getImages($all ? 0 : ($imagePage), $firstPageCount, NULL, NULL, true, $mine);
			}
			if (empty($__images)) {
				$result = NULL;
			} else {
				$_current_image_restore = $_current_image;
				$img = reset($__images);
				$_current_image = newImage($_current_album, $img, true, true);
				save_context();
				add_context(NPG_IMAGE);
				$result = true;
			}
		} else {
			$img = next($__images);
			if ($img) {
				$_current_image = newImage($_current_album, $img, true, true);
				$result = true;
			} else {
				$__images = NULL;
				$_current_image = $_current_image_restore;
				restore_context();
				$result = false;
			}
		}
	}
	return npgFilters::apply('next_object_loop', $result, $_current_image);
}

//*** Image Context ************************
//******************************************

/**
 * Sets the image passed as the current image
 *
 * @param object $image the image to become current
 */
function makeImageCurrent($image) {
	if (!is_object($image))
		return;
	global $_current_album, $_current_image;
	$_current_image = $image;
	$_current_album = $_current_image->getAlbum();
	save_context();
	set_context(NPG_INDEX | NPG_ALBUM | NPG_IMAGE);
}

/**
 * Returns the raw title of the current image.
 *
 * @return string
 */
function getImageTitle() {
	if (!in_context(NPG_IMAGE))
		return false;
	global $_current_image;
	return $_current_image->getTitle();
}

/**
 * Returns a text-only title of the current image.
 *
 * @return string
 */
function getBareImageTitle() {
	return getBare(getImageTitle());
}

/**
 * Returns the image title taged with not visible annotation.
 *
 * @return string
 */
function getAnnotatedImageTitle() {
	global $_current_image;
	$title = getBareImageTitle();
	if (!$_current_image->getShow()) {
		$title .= "\n" . gettext('The image is marked un-published.');
	}
	return $title;
}

function printAnnotatedImageTitle() {
	echo html_encode(getAnnotatedImageTitle());
}

/**
 * Prints title of the current image
 *
 * @author Ozh
 */
function printImageTitle() {
	echo html_encodeTagged(getImageTitle());
}

function printBareImageTitle() {
	echo html_encode(getBareImageTitle());
}

/**
 * Returns the 'n' of n of m images
 *
 * @return int
 */
function imageNumber() {
	global $_current_image, $_current_search, $_current_album;
	$name = $_current_image->getFileName();
	if (in_context(NPG_SEARCH) || (in_context(SEARCH_LINKED) && !in_context(ALBUM_LINKED))) {
		$folder = $_current_image->imagefolder;
		$images = $_current_search->getImages();
		$c = 0;
		foreach ($images as $image) {
			$c++;
			if ($name == $image['filename'] && $folder == $image['folder']) {
				return $c;
			}
		}
	} else {
		return $_current_image->getIndex() + 1;
	}
	return false;
}

/**
 * Returns the image date of the current image in yyyy-mm-dd hh:mm:ss format.
 * Pass it a date format string for custom formatting
 *
 * @param string $format formatting string for the data
 * @return string
 */
function getImageDate($format = null) {
	if (!in_context(NPG_IMAGE))
		return false;
	global $_current_image;
	$d = $_current_image->getDateTime();
	if (empty($d)) {
		return false;
	}
	if (is_null($format)) {
		return $d;
	}
	return formattedDate($format, strtotime($d));
}

/**
 * Prints the date of the current album
 *
 * @param string $before Insert here the text to be printed before the date.
 * @param string $format Format string for the date formatting
 */
function printImageDate($before = '', $format = null) {
	global $_current_image;
	if (is_null($format)) {
		$format = DATE_FORMAT;
	}
	$date = getImageDate($format);
	if ($date) {
		if ($before) {
			$date = '<span class = "beforetext">' . html_encode($before) . '</span>' . $date;
		}
	}
	echo $date;
}

// IPTC fields
/**
 * Returns the Location field of the current image
 *
 * @return string
 */
function getImageLocation() {
	if (!in_context(NPG_IMAGE))
		return false;
	global $_current_image;
	return $_current_image->getLocation();
}

/**
 * Returns the City field of the current image
 *
 * @return string
 */
function getImageCity() {
	if (!in_context(NPG_IMAGE))
		return false;
	global $_current_image;
	return $_current_image->getcity();
}

/**
 * Returns the State field of the current image
 *
 * @return string
 */
function getImageState() {
	if (!in_context(NPG_IMAGE))
		return false;
	global $_current_image;
	return $_current_image->getState();
}

/**
 * Returns the Country field of the current image
 *
 * @return string
 */
function getImageCountry() {
	if (!in_context(NPG_IMAGE))
		return false;
	global $_current_image;
	return $_current_image->getCountry();
}

/**
 * Returns the raw description of the current image.
 * new lines are replaced with <br /> tags
 *
 * @return string
 */
function getImageDesc() {
	if (!in_context(NPG_IMAGE))
		return false;
	global $_current_image;
	return $_current_image->getDesc();
}

/**
 * Returns a text-only description of the current image.
 *
 * @return string
 */
function getBareImageDesc() {
	return getBare(getImageDesc());
}

/**
 * Prints the description of the current image.
 * Converts and displays line breaks set in the admin field as <br />.
 *
 */
function printImageDesc() {
	echo html_encodeTagged(getImageDesc());
}

function printBareImageDesc() {
	echo html_encode(getBareImageDesc());
}

/**
 * A composit for getting image data
 *
 * @param string $field which field you want
 * @return string
 */
function getImageData($field) {
	if (!in_context(NPG_IMAGE))
		return false;
	global $_current_image;
	return get_language_string($_current_image->get($field));
}

/**
 * Prints arbitrary data from the image object
 *
 * @param string $field the field name of the data desired
 * @param string $label text to label the field.
 * @author Ozh
 */
function printImageData($field, $label = '') {
	global $_current_image;
	$text = getImageData($field);
	if (!empty($text)) {
		echo html_encodeTagged($label . $text);
	}
}

/**
 * Returns the file size of the full original image
 *
 * @global obj $_current_image
 * @return int
 */
function getFullImageFilesize() {
	global $_current_image;
	$filesize = $_current_image->getFilesize();
	if ($filesize) {
		return byteConvert($filesize);
	}
}

/**
 * True if there is a next image
 *
 * @return bool
 */
function hasNextImage() {
	global $_current_image;
	if (is_null($_current_image))
		return false;
	return $_current_image->getNextImage();
}

/**
 * True if there is a previous image
 *
 * @return bool
 */
function hasPrevImage() {
	global $_current_image;
	if (is_null($_current_image))
		return false;
	return $_current_image->getPrevImage();
}

/**
 * Returns the url of the next image.
 *
 * @return string
 */
function getNextImageURL() {
	global $_current_image;
	if (!in_context(NPG_IMAGE))
		return false;
	if (is_null($_current_image))
		return false;
	$nextimg = $_current_image->getNextImage();
	return $nextimg->getLink();
}

/**
 * Returns the url of the previous image.
 *
 * @return string
 */
function getPrevImageURL() {
	global $_current_image;
	if (!in_context(NPG_IMAGE))
		return false;
	if (is_null($_current_image))
		return false;
	$previmg = $_current_image->getPrevImage();
	return $previmg->getLink();
}

/**
 * Returns the thumbnail of the previous image.
 *
 * @return string
 */
function getPrevImageThumb($suffix = NULL) {
	if (!in_context(NPG_IMAGE))
		return false;
	global $_current_image;
	if (is_null($_current_image))
		return false;
	$img = $_current_image->getPrevImage();
	return $img->getThumb($suffix);
}

/**
 * Returns the thumbnail of the next image.
 *
 * @return string
 */
function getNextImageThumb($suffix = NULL) {
	if (!in_context(NPG_IMAGE))
		return false;
	global $_current_image;
	if (is_null($_current_image))
		return false;
	$img = $_current_image->getNextImage();
	return $img->getThumb($suffix);
}

/**
 * Returns the url of the current image.
 *
 * @return string
 */
function getImageURL() {
	global $_current_image;
	if (!in_context(NPG_IMAGE))
		return false;
	if (is_null($_current_image))
		return false;
	return $_current_image->getLink();
}

/**
 * Prints the link to the current  image.
 *
 * @param string $text text for the link
 * @param string $title title tag for the link
 * @param string $class optional style class for the link
 * @param string $id optional style id for the link
 */
function printImageURL($text, $title, $class = false, $id = NULL) {
	printLinkHTML(getImageURL(), $text, $title, $class, $id);
}

/**
 * Returns the Metadata infromation from the current image
 *
 * @param $image optional image object
 * @param string $displayonly set to true to return only the items selected for display
 * @return array
 */
function getImageMetaData($image = NULL, $displayonly = true) {
	global $_current_image, $_exifvars;
	require_once(CORE_SERVERPATH . 'exif/exifTranslations.php');
	if (is_null($image))
		$image = $_current_image;
	if (is_null($image) || !$image->get('hasMetadata')) {
		return false;
	}
	$data = $image->getMetaData();

	foreach ($data as $field => $value) { //	remove the empty or not selected to display
		if ($_exifvars[$field][EXIF_FIELD_TYPE] == 'time' && $value == '0000-00-00 00:00:00') {
			$value = ''; // really it is empty
		}
		if ($displayonly && (!$value || !$_exifvars[$field][EXIF_DISPLAY])) {
			unset($data[$field]);
		} else {
			$data[$field] = exifTranslate($value, $field);
		}
	}
	if (count($data) > 0) {
		return $data;
	}
	return false;
}

/**
 * Prints the Metadata data of the current image
 *
 * @param string $title title tag for the class
 * @param bool $toggle set to true to get a javascript toggle on the display of the data
 * @param string $id style class id
 * @param string $class style class
 * @author Ozh
 */
function printImageMetadata($title = NULL, $toggle = TRUE, $id = 'imagemetadata', $class = false, $span = NULL) {
	global $_exifvars, $_current_image;
	if (false === ($exif = getImageMetaData($_current_image, true))) {
		return;
	}


	if (is_null($title)) {
		$title = gettext('Image Info');
	}
	if ($class) {
		$class = ' class = "' . $class . '"';
	}
	if (!$span) {
		$span = 'exif_link';
	}
	$dataid = $id . '_data';
	if ($id) {
		$id = ' id = "' . $id . '"';
	}
	$refh = $refa = $style = '';
	if ($toggle === 'colorbox' && npgFilters::has_filter('theme_head', 'colorbox::css')) {
		$refh = '<a href = "#" class = "colorbox" title = "' . $title . '">';
		$refa = '</a>';
		$style = ' style = "display:none"';
	} else if ($toggle) {
		$refh = '<a onclick = "$(\'#' . $dataid . '\').toggle();" title = "' . $title . '">';
		$refa = '</a>';
		$style = ' style = "display:none"';
	}
	?>
	<span id="<?php echo $span; ?>" class="metadata_title">
		<?php echo $refh; ?><?php echo $title; ?><?php echo $refa; ?>
	</span>
	<div id="<?php echo $dataid; ?>"<?php echo $style; ?>>
		<div<?php echo $id . $class; ?>>
			<table>
				<?php
				foreach ($exif as $field => $value) {
					$label = $_exifvars[$field][EXIF_DISPLAY_TEXT];
					echo "<tr><td class=\"label " . html_encode($field) . "\">$label:</td><td class=\"value\">";
					echo html_encode($value);
					echo "</td></tr>\n";
				}
				?>
			</table>
		</div>
	</div>
	<?php
}

/**
 * Returns an array with the height & width
 *
 * @param array $args of parameters
 * @param string suffix of imageURI
 * @return array
 */
function getSizeCustomImage($args, $image = NULL) {
	global $_current_image;

	if (!is_array($args)) {
		$a = array('size', 'width', 'height', 'cw', 'ch', 'cx', 'cy', 'image');
		$p = func_get_args();
		$args = array();
		foreach ($p as $k => $v) {
			$args[$a[$k]] = $v;
		}
		if (isset($args['image'])) {
			$image = $args['image'];
			unset($args['image']);
		} else {
			$image = NULL;
		}
		if (isset($args['suffix'])) {
			$suffix = $args['suffix'];
			unset($args['suffix']);
		} else {
			$suffix = NULL;
		}

		require_once(PLUGIN_SERVERPATH . 'deprecated-functions.php');
		deprecated_functions::notify_call('getSizeCustomImage', gettext('The function should be called with an image arguments array.') . sprintf(gettext(' e.g. %1$s '), npgFunctions::array_arg_example($args)));
	}
	$size = $width = $height = $cw = $ch = $cx = $cy = $thumb = NULL;
	extract($args);

	if (is_null($image)) {
		$image = $_current_image;
	}
	if (is_null($image))
		return false;

	$h = $image->getHeight();
	$w = $image->getWidth();
	if ($image->isVideo()) { // size is determined by the player
		return array($w, $h);
	}

	$side = getOption('image_use_side');
	$us = (bool) getOption('image_allow_upscale');

	$args = getImageParameters($args, $image->album->name);
	$size = $width = $height = $cw = $ch = $cx = $cy = $thumb = $WM = NULL;
	extract($args);

	if (!empty($size)) {
		$dim = $size;
		$width = $height = false;
	} else if (!empty($width)) {
		$dim = $width;
		$size = $height = false;
	} else if (!empty($height)) {
		$dim = $height;
		$size = $width = false;
	} else {
		$dim = 1;
	}

	if ($w == 0) {
		$hprop = 1;
	} else {
		$hprop = round(($h / $w) * $dim);
	}
	if ($h == 0) {
		$wprop = 1;
	} else {
		$wprop = round(($w / $h) * $dim);
	}

	if ($cw || $ch) { //	image is being cropped
		if ($cw && $cw <= $w) {
			$neww = $cw;
		} else {
			$neww = $w;
		}
		if ($ch && $ch <= $h) {
			$newh = $ch;
		} else {
			$newh = $h;
		}
	} else if (($size && ($side == 'longest' && $h > $w) || ($side == 'height') || ($side == 'shortest' && $h < $w)) || $height) {
// Scale the height
		$newh = $dim;
		$neww = $wprop;
	} else {
// Scale the width
		$neww = $dim;
		$newh = $hprop;
	}
	if (!$us && ($newh >= $h || $neww >= $w)) { //	upscaling required but not allowed
		return array((int) $w, (int) $h);
	} else {
		return array((int) $neww, (int) $newh);
	}
}

/**
 * Returns an array [width, height] of the default-sized image.
 *
 * @param int $size override the 'image_zize' option
 * @param $image object the image for which the size is desired. NULL means the current image
 *
 * @return array
 */
function getSizeDefaultImage($size = NULL, $image = NULL) {
	if (is_null($size))
		$size = getOption('image_size');
	return getSizeCustomImage(array('size' => $size), $image);
}

/**
 * Returns an array [width, height] of the original image.
 *
 * @param $image object the image for which the size is desired. NULL means the current image
 *
 * @return array
 */
function getSizeFullImage($image = NULL) {
	global $_current_image;
	if (is_null($image))
		$image = $_current_image;
	if (is_null($image))
		return false;
	return array($image->getWidth(), $image->getHeight());
}

/**
 * The width of the default-sized image (in printDefaultSizedImage)
 *
 * @param $image object the image for which the size is desired. NULL means the current image
 *
 * @return int
 */
function getDefaultWidth($size = NULL, $image = NULL) {
	$size_a = getSizeDefaultImage($size, $image);
	return $size_a[0];
}

/**
 * Returns the height of the default-sized image (in printDefaultSizedImage)
 *
 * @param $image object the image for which the size is desired. NULL means the current image
 *
 * @return int
 */
function getDefaultHeight($size = NULL, $image = NULL) {
	$size_a = getSizeDefaultImage($size, $image);
	return $size_a[1];
}

/**
 * Returns the width of the original image
 *
 * @param $image object the image for which the size is desired. NULL means the current image
 *
 * @return int
 */
function getFullWidth($image = NULL) {
	global $_current_image;
	if (is_null($image))
		$image = $_current_image;
	if (is_null($image))
		return false;
	return $image->getWidth();
}

/**
 * Returns the height of the original image
 *
 * @param $image object the image for which the size is desired. NULL means the current image
 *
 * @return int
 */
function getFullHeight($image = NULL) {
	global $_current_image;
	if (is_null($image))
		$image = $_current_image;
	if (is_null($image))
		return false;
	return $image->getHeight();
}

/**
 * Returns true if the image is landscape-oriented (width is greater than height)
 *
 * @param $image object the image for which the size is desired. NULL means the current image
 *
 * @return bool
 */
function isLandscape($image = NULL) {
	if (getFullWidth($image) >= getFullHeight($image))
		return true;
	return false;
}

/**
 * Returns the url to the default sized image.
 *
 * @param $image object the image for which the size is desired. NULL means the current image
 *
 * @return string
 */
function getDefaultSizedImage($image = NULL, $suffix = NULL) {
	global $_current_image;
	if (is_null($image))
		$image = $_current_image;
	if (is_null($image))
		return false;
	return $image->getSizedImage(getOption('image_size', $suffix));
}

/**
 * Show video player with video loaded or display the image.
 *
 * @param string $alt Alt text
 * @param string $class Optional style class
 * @param string $id Optional style id
 * @param string $title Title attribute
 */
function printDefaultSizedImage($alt, $class = false, $id = NULL, $title = NULL) {
	global $_current_image;
	$content = $_current_image->getContent();

	preg_match('~class\s*=\s*"([^"]+)"~', $content, $matches);
	if ($matches) {
		$class .= ' ' . $matches[1];
		$content = preg_replace('~' . $matches[0] . '~', '@class@', $content);
	} else {
		debugLogBacktrace(gettext(get_class($_current_image) . '->getContent() did not provide a class attribute'));
	}
	$class = ' class="' . trim($class) . '"';
	if (isset($id)) {
		$id = ' id="' . $id . '"';
	}
	if (isset($alt)) {
		$alt = ' alt="' . $alt . '"';
	}
	if (isset($title)) {
		$title = ' title="' . $title . '"';
	}
	$content = preg_replace('~@class@~', $id . $class . $title . $alt, $content);

	echo $content;
}

/**
 * Returns the url to the thumbnail of the current image.
 *
 * @return string
 */
function getImageThumb($suffix = NULL) {
	global $_current_image;
	if (is_null($_current_image))
		return false;
	return $_current_image->getThumb(NULL, NULL, $suffix);
}

/**
 * @param string $alt Alt text
 * @param string $class optional class tag
 * @param string $id optional id tag
 * @param string $title Title attribute
 */
function printImageThumb($alt, $class = false, $id = NULL, $title = NULL) {
	global $_current_image;
	if (is_null($_current_image))
		return;
	if (!$_current_image->getShow()) {
		$class .= " not_visible";
	}
	$album = $_current_image->getAlbum();
	$pwd = $album->getPassword();
	if (!empty($pwd)) {
		$class .= " password_protected";
	}
	$url = getImageThumb();
	$sizes = getSizeDefaultThumb();
	$size = ' width="' . $sizes[0] . '" height="' . $sizes[1] . '"';
	$class = trim($class);
	if ($class) {
		$class = ' class="' . $class . '"';
	}
	if ($id) {
		$id = ' id="' . $id . '"';
	}
	if ($title) {
		$title = ' title="' . html_encode($title) . '"';
	}

	$html = '<img src="' . html_encode($url) . '"' . $size . ' alt="' . html_encode($alt) . '"' . $class . $id . $title . " />";
	$html = npgFilters::apply('standard_image_thumb_html', $html);
	if (ENCODING_FALLBACK) {
		$html = "<picture>\n<source srcset = \"" . html_encode(getImageThumb(FALLBACK_SUFFIX)) . "\">\n" . $html . "</picture>\n";
	}
	echo $html;
}

/**
 * Gets the width and height of a default thumb for the <img> tag height/width
 * @global type $_current_image
 * @param obj $image Image object, if NULL the current image is used
 * @return aray
 */
function getSizeDefaultThumb($image = NULL) {
	global $_current_image;
	if (is_null($image)) {
		$image = $_current_image;
	}
	$s = max(getOption('thumb_size'), 1);
	if (getOption('thumb_crop')) {
		$w = max(getOption('thumb_crop_width'), 1);
		$h = max(getOption('thumb_crop_height'), 1);
		if ($w > $h) {
//landscape
			$h = round($h * $s / $w);
			$w = $s;
		} else {
//portrait
			$w = round($w * $s / $h);
			$h = $s;
		}
	} else {
		$w = $h = $s;
		getMaxSpaceContainer($w, $h, $image, true);
	}
	return array($w, $h);
}

/**
 * Returns the url to original image.
 * It will return a protected image is the option "protect_full_image" is set
 *
 * @param $image optional image object
 * @return string
 */
function getFullImageURL($image = NULL) {
	global $_current_image;
	if (is_null($image)) {
		$image = $_current_image;
	}
	if (is_null($image)) {
		return false;
	}
	switch ($outcome = getOption('protect_full_image')) {
		case 'No access':
			return NULL;
		case 'Unprotected':
			return $image->getFullImageURL();
		default:
			return getProtectedImageURL($image, $outcome);
	}
}

/**
 * Returns the "raw" url to the image in the albums folder
 *
 * @param $image optional image object
 * @return string
 *
 */
function getUnprotectedImageURL($image = NULL) {
	global $_current_image;
	if (is_null($image)) {
		$image = $_current_image;
	}
	if (!is_null($image)) {
		return $image->getFullImageURL();
	}
}

/**
 * Returns an url to the password protected/watermarked current image
 *
 * @param object $image optional image object overrides the current image
 * @param string $disposal set to override the 'protect_full_image' option
 * @return string
 * */
function getProtectedImageURL($image = NULL, $disposal = NULL) {
	global $_current_image;
	if (is_null($disposal)) {
		$disposal = getOption('protect_full_image');
	}
	if ($disposal == 'No access')
		return NULL;
	if (is_null($image)) {
		if (!in_context(NPG_IMAGE))
			return false;
		if (is_null($_current_image))
			return false;
		$image = $_current_image;
	}

	$album = $image->getAlbum();
	$watermark_use_image = getWatermarkParam($image, WATERMARK_FULL);
	if (!empty($watermark_use_image)) {
		$wmt = $watermark_use_image;
	} else {
		$wmt = false;
	}
	$args = array('size' => 'FULL', 'quality' => (int) getOption('full_image_quality'), 'WM' => $wmt);
	$cache_file = getImageCacheFilename($album->name, $image->filename, $args);
	$cache_path = SERVERCACHE . $cache_file;
	if ($disposal != 'Download' && OPEN_IMAGE_CACHE && file_exists($cache_path)) {
		return WEBPATH . '/' . CACHEFOLDER . pathurlencode(imgSrcURI($cache_file));
	} else if ($disposal == 'Unprotected') {
		return getImageURI($args, $album->name, $image->filename, $image->filemtime);
	} else {
		$params = '&q=' . getOption('full_image_quality');
		if (!empty($watermark_use_image)) {
			$params .= '&wmk=' . $watermark_use_image;
		}
		if ($disposal) {
			$params .= '&dsp=' . $disposal;
		}
		if (is_array($image->filename)) {
			$album = dirname($image->filename['source']);
			$image = basename($image->filename['source']);
		} else {
			$album = $album->name;
			$image = $image->filename;
		}
		$params .= '&ipcheck=' . ipProtectTag(internalToFilesystem($album), internalToFilesystem($image), $args) . '&cached=' . rand();

		return WEBPATH . '/' . CORE_FOLDER . '/full-image.php?a=' . pathurlencode($album) . '&i=' . urlencode($image) . $params;
	}
}

/**
 * Returns a link to the current image custom sized to $size
 *
 * @param int $size The size the image is to be
 */
function getSizedImageURL($size) {
	return getCustomImageURL(array('size' => $size));
}

/**
 * Returns the url to the image with the dimensions you define with this function.
 *
 * @param array $args of parameters
 * @param string $suffix url suffix desired
 * @return string
 *
 * $size, $width, and $height are used in determining the final image size.
 * At least one of these must be provided. If $size is provided, $width and
 * $height are ignored. If both $width and $height are provided, the image
 * will have those dimensions regardless of the original image height/width
 * ratio. (Yes, this means that the image may be distorted!)
 *
 * The $crop* parameters determine the portion of the original image that
 * will be incorporated into the final image.
 *
 * $cw and $ch "sizes" are typically proportional. That is you can
 * set them to values that reflect the ratio of width to height that you
 * want for the final image. Typically you would set them to the final
 * height and width. These values will always be adjusted so that they are
 * not larger than the original image dimensions.
 *
 * The $cx and $cy values represent the offset of the crop from the
 * top left corner of the image. If these values are provided, the $ch
 * and $cw parameters are treated as absolute pixels not proportions of
 * the image. If cropx and cropy are not provided, the crop will be
 * "centered" in the image.
 *
 * When $cx and $cy are not provided the crop is offset from the top
 * left proportionally to the ratio of the final image size and the crop
 * size.
 *
 * Some typical croppings:
 *
 * $size=200, $width=NULL, $height=NULL, $cw=200, $ch=100,
 * $cx=NULL, $cy=NULL produces an image cropped to a 2x1 ratio which
 * will fit in a 200x200 pixel frame.
 *
 * $size=NULL, $width=200, $height=NULL, $cw=200, $ch=100, $cx=100,
 * $cy=10 will will take a 200x100 pixel slice from (10,100) of the
 * picture and create a 200x100 image
 *
 * $size=NULL, $width=200, $height=100, $cw=200, $ch=120, $cx=NULL,
 * $cy=NULL will produce a (distorted) image 200x100 pixels from a 1x0.6
 * crop of the image.
 *
 * $size=NULL, $width=200, $height=NULL, $cw=180, $ch=120, $cx=NULL, $cy=NULL
 * will produce an image that is 200x133 from a 1.5x1 crop that is 5% from the left
 * and 15% from the top of the image.
 *
 */
function getCustomImageURL($args, $suffix = NULL) {
	global $_current_image;

	if (is_null($_current_image)) {
		return false;
	}
	if (!is_array($args)) {
		$a = array('size', 'width', 'height', 'cw', 'ch', 'cx', 'cy', 'thumb', 'effects', 'suffix');
		$p = func_get_args();
		$args = array();
		foreach ($p as $k => $v) {
			$args[$a[$k]] = $v;
		}
		if (isset($args['suffix'])) {
			$suffix = $args['suffix'];
			unset($args['suffix']);
		} else {
			$suffix = NULL;
		}

		require_once(PLUGIN_SERVERPATH . 'deprecated-functions.php');
		deprecated_functions::notify_call('getCustomImageURL', gettext('The function should be called with an image arguments array.') . sprintf(gettext(' e.g. %1$s '), npgFunctions::array_arg_example($args)));
	}
	return $_current_image->getCustomImage($args, $suffix);
}

/**
 * Print normal video or custom sized images.
 * Note: a class of 'not_visible' or 'password_protected' will be added as appropriate
 *
 * Notes on cropping:
 *
 * The $crop* parameters determine the portion of the original image that will be incorporated
 * into the final image. The w and h "sizes" are typically proportional. That is you can set them to
 * values that reflect the ratio of width to height that you want for the final image. Typically
 * you would set them to the fincal height and width.
 *
 * @param string $alt Alt text for the url
 * @param array $args of parameters
 * @param string $title title attribute
 * */
function printCustomSizedImage($alt, $args, $class = false, $id = NULL, $title = NULL) {
	global $_current_image;
	if (is_null($_current_image))
		return;

	if (is_array($args)) {
		if (!isset($args['thumb'])) {
			$args['thumb'] = NULL;
		}
	} else {
		$a = array(NULL, 'size', 'width', 'height', 'cw', 'ch', 'cx', 'cy', 'class', 'id', 'thumb', 'effects', 'title');
		$p = func_get_args();
		unset($p[0]); //	$alt
		$args = array();
		foreach ($p as $k => $v) {
			$args[$a[$k]] = $v;
		}

		if (array_key_exists('class', $args)) {
			$class = $args['class'];
			unset($args['class']);
			if (is_null($class)) {
				$class = false;
			}
		} else {
			$class = false;
		}
		if (array_key_exists('id', $args)) {
			$id = $args['id'];
			unset($args['id']);
		} else {
			$id = NULL;
		}
		if (array_key_exists('title', $args)) {
			$title = $args['title'];
			unset($args['title']);
		} else {
			$title = NULL;
		}

		require_once(PLUGIN_SERVERPATH . 'deprecated-functions.php');
		deprecated_functions::notify_call('printCustomSizedImage', gettext('The function should be called with an image arguments array.') . sprintf(gettext(' e.g. %1$s '), npgFunctions::array_arg_example($args)));
	}
	$size = $width = $height = $cw = $ch = $cx = $cy = $thumb = NULL;
	extract($args);

	if (!$_current_image->getShow()) {
		$class .= " not_visible";
	}
	$album = $_current_image->getAlbum();
	$pwd = $album->getPassword();
	if (!empty($pwd)) {
		$class .= " password_protected";
	}
	$sizing = '';
	if ($size) {
		$dims = getSizeCustomImage(array('size' => $size));
		if ($dims[0])
			$sizing = ' width="' . $dims[0] . '"';
		if ($dims[1])
			$sizing .= ' height="' . $dims[1] . '"';
	} else {
		if ($width)
			$sizing .= ' width="' . $width . '"';
		if ($height)
			$sizing .= ' height="' . $height . '"';
	}
	if ($id) {
		$id = ' id="' . $id . '"';
	}
	if ($class) {
		$class = ' class="' . $class . '"';
	}
	if ($title) {
		if ($title === TRUE) {
			$title = $_current_image->getTitle();
		}
		$title = ' title="' . html_encode($title) . '"';
	}
	if ($_current_image->isPhoto() || $thumb) {
		$html = '<img src="' . html_encode($_current_image->getCustomImage($args)) . '"' .
						' alt="' . html_encode($alt) . '"' .
						$id . $class . $sizing . $title . ' />';
		$html = npgFilters::apply('custom_image_html', $html, $thumb);
		if (ENCODING_FALLBACK) {
			$html = "<picture>\n<source srcset=\"" . html_encode($_current_image->getCustomImage($args, FALLBACK_SUFFIX)) . "\">\n" . $html . "</picture>\n";
		}
		echo $html;
	} else { // better be a plugin
		echo $_current_image->getContent($width, $height);
	}
}

/**
 * Returns a link to a un-cropped custom sized version of the current image within the given height and width dimensions.
 * Use for sized images.
 *
 * @param int $width width
 * @param int $height height
 * @return string
 */
function getCustomSizedImageMaxSpace($width, $height) {
	global $_current_image;
	if (is_null($_current_image))
		return false;
	getMaxSpaceContainer($width, $height, $_current_image);
	return getCustomImageURL(array('width' => $width, 'height' => $height));
}

/**
 * Returns a link to a un-cropped custom sized version of the current image within the given height and width dimensions.
 * Use for sized thumbnails.
 *
 * @param int $width width
 * @param int $height height
 * @return string
 */
function getCustomSizedImageThumbMaxSpace($width, $height) {
	global $_current_image;
	if (is_null($_current_image))
		return false;
	getMaxSpaceContainer($width, $height, $_current_image, true);
	return getCustomImageURL(array('width' => $width, 'height' => $height, 'thumb' => TRUE));
}

/**
 * Creates image thumbnails which will fit un-cropped within the width & height parameters given
 *
 * @param string $alt Alt text for the url
 * @param int $width width
 * @param int $height height
 * @param string $class Optional style class
 * @param string $id Optional style id
 * @param string $title Option title attribute
 */
function printCustomSizedImageThumbMaxSpace($alt, $width, $height, $class = false, $id = NULL, $title = NULL) {
	global $_current_image;
	if (is_null($_current_image))
		return;
	getMaxSpaceContainer($width, $height, $_current_image, true);
	printCustomSizedImage($alt, array('width' => $width, 'height' => $height), $class, $id, $title);
}

/**
 * Print normal video or un-cropped within the given height and width dimensions. Use for sized images or thumbnails in an album.
 * Note: a class of 'not_visible' or 'password_protected' will be added as appropriate
 *
 * @param string $alt Alt text for the url
 * @param int $width width
 * @param int $height height
 * @param string $class Optional style class
 * @param string $id Optional style id
 * @param string $title Option title attribute
 */
function printCustomSizedImageMaxSpace($alt, $width, $height, $class = false, $id = NULL, $thumb = false, $title = NULL) {
	global $_current_image;
	if (is_null($_current_image))
		return;
	getMaxSpaceContainer($width, $height, $_current_image, $thumb);
	printCustomSizedImage($alt, array('width' => $width, 'height' => $height), $class, $id, $title);
}

/**
 * Prints link to an image of specific size
 * @param int $size how big
 * @param string $text URL text
 * @param string $title URL title
 * @param string $class optional URL class
 * @param string $id optional URL id
 */
function printSizedImageURL($size, $text, $title, $class = false, $id = NULL) {
	printLinkHTML(getSizedImageURL($size), $text, $title, $class, $id);
}

/**
 * performs a query and then filters out "illegal" images
 *
 * @param object $result query result
 * @param string $source album object if this is search within the album
 * @param int $limit How many images to fetch (0 will fetch all)
 * @param bool $photos set true to return only imagePhotos
 *
 * @return array
 */
function filterImageQueryList($result, $source, $limit = 1, $photo = true) {
	$list = array();
	if ($result) {
		while ($limit && $row = db_fetch_assoc($result)) {
			set_time_limit(120);
			$image = getItemByID('images', $row['id']);
			if ($image && $image->exists) {
				$album = $image->album;
				if ($album->name == $source || $album->checkAccess()) {
					if (!$photo || $image->isPhoto()) {
						if ($image->checkAccess()) {
							$list[] = $image;
							$limit--;
						}
					}
				}
			}
		}
		db_free_result($result);
	}
	return $list;
}

/**
 * Returns a randomly selected image from the gallery. (May be NULL if none exists)
 * @param bool $daily set to true and the picture changes only once a day.
 * @param int $limit How many images to cache.
 *
 * Note for any given instantiation, multiple calls will not return a previously selected
 * image.
 *
 * (May return NULL if no images remain)
 *
 * @return object
 */
function getRandomImages($daily = false, $limit = 1) {
	global $_gallery, $_random_image_list;
	if ($daily && ($potd = getOption('picture_of_the_day'))) {
		$potd = getSerializedArray($potd);
		if (date('Y-m-d', $potd['day']) == date('Y-m-d')) {
			$album = newAlbum($potd['folder'], true, true);
			if ($album->exists) {
				$image = newImage($album, $potd['filename'], true);
				if ($image->exists) {
					return $image;
				}
			}
		}
		setThemeOption('picture_of_the_day', NULL, NULL, $_gallery->getCurrentTheme());
	}
	if (is_null($_random_image_list)) {
		if (npg_loggedin()) {
			$imageWhere = '';
		} else {
			$imageWhere = " WHERE `show`=1";
		}
		$row = query_single_row('SELECT COUNT(*) FROM ' . prefix('images'));
		if (5000 < $count = reset($row)) {
			$sample = ceil((max(1000, $limit * 100) / $count) * 100);
			if ($imageWhere) {
				$imageWhere .= ' AND';
			} else {
				$imageWhere = ' WHERE';
			}
			$imageWhere .= ' CAST((RAND() * 100 * `id`) % 100 as UNSIGNED) < ' . $sample;
		}
		$sql = 'SELECT `id` FROM ' . prefix('images') . $imageWhere . ' ORDER BY RAND()';
		$result = query($sql);
		$_random_image_list = filterImageQueryList($result, NULL, $limit, TRUE);
	}

	$image = array_shift($_random_image_list);
	if ($image) {
		if ($daily) {
			$potd = array('day' => time(), 'folder' => $image->getAlbumName(), 'filename' => $image->getFileName());
			setThemeOption('picture_of_the_day', serialize($potd), NULL, $_gallery->getCurrentTheme());
		}
		return $image;
	}
	return NULL;
}

/**
 * Returns  a randomly selected image from the album or one of its subalbums
 * if the album has no images.
 *
 * Note for any given instantiation, multiple calls will not return a previously selected
 * image.
 *
 * (May return NULL if no images remain)
 *
 * @param mixed $rootAlbum optional album object/folder from which to get the image.
 * @param bool $daily set to true to change picture only once a day.
 *
 * @return object
 */
function getRandomImagesAlbum($rootAlbum = NULL, $daily = false) {
	global $_current_album, $_gallery, $_current_search, $_random_images_album;
	if (empty($rootAlbum)) {
		$album = $_current_album;
	} else {
		if (is_object($rootAlbum)) {
			$album = $rootAlbum;
		} else {
			$album = newAlbum($rootAlbum);
		}
	}
	if ($daily && ($potd = getOption('picture_of_the_day:' . $album->name))) {
		$potd = getSerializedArray($potd);
		if (date('Y-m-d', $potd['day']) == date('Y-m-d')) {
			$rndalbum = newAlbum($potd['folder']);
			$image = newImage($rndalbum, $potd['filename']);
			if ($image->exists) {
				return $image;
			}
		}
	}
	if (!isset($_random_images_album[$album->name])) {
		$_random_images_album[$album->name] = array();
		$album->setSortType('random');
		foreach ($album->getImages(0) as $imagename) {
			$_random_images_album[$album->name][] = $imagename;
		}
	}

	$image = array_shift($_random_images_album[$album->name]);
	if ($image) {
		$image = newImage($album, $image);
	} else {
		$album->setSortType('random', 'album');
		foreach ($album->getAlbums() as $subalbum) {
			if ($image = getRandomImagesAlbum($subalbum))
				break;
		}
	}

	if ($image && $image->exists) {
		if ($daily) {
			$potd = array('day' => time(), 'folder' => $image->getAlbumName(), 'filename' => $image->getFileName());
			setThemeOption('picture_of_the_day:' . $album->name, serialize($potd), NULL, $_gallery->getCurrentTheme());
		}
		return $image;
	}
	return NULL;
}

/**
 * returns a picture of the day image.
 *
 * If called from an album page it will get the image from the album (or subalbums if
 * the album has no images.) Otherwise it selects one randomly from the gallery.
 *
 * Once selected, the image remains until the following day. That is it will change
 * only once a day.
 *
 * @return obj image
 */
function getPictureOfTheDay() {
	global $gallery_page;
	if ($gallery_page == 'album.php') {
		$image = getRandomImagesAlbum(NULL, true);
	} else {
		$image = getRandomImages(true);
	}
	return $image;
}

/**
 * Puts up random image thumbs from the gallery
 *
 * @param int $number how many images
 * @param string $class optional class
 * @param string $option what you want selected: all for all images, album for selected ones from an album
 * @param mixed $rootAlbum optional album object/folder from which to get the image.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size.
 * @param integer $height the height/cropheight of the thumb if crop=true else not used
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $fullimagelink 'false' (default) for the image page link , 'true' for the unprotected full image link (to use Colorbox for example)
 */
function printRandomImages($number = 5, $class = false, $option = 'all', $rootAlbum = '', $width = NULL, $height = NULL, $crop = NULL, $fullimagelink = false) {
	if (is_null($crop) && is_null($width) && is_null($height)) {
		$crop = 2;
	} else {
		if (is_null($width))
			$width = 85;
		if (is_null($height))
			$height = 85;
		if (is_null($crop)) {
			$crop = 1;
		} else {
			$crop = (int) $crop && true;
		}
	}
	if (!empty($class)) {
		$class = ' class="' . $class . '"';
	}
	echo "<ul" . $class . ">";
	for ($i = 1; $i <= $number; $i++) {
		switch ($option) {
			case "all":
				$randomImage = getRandomImages(false, $number);
				break;
			case "album":
				$randomImage = getRandomImagesAlbum($rootAlbum);
				break;
		}
		if (is_object($randomImage) && $randomImage->exists) {
			echo "<li>\n";
			if ($fullimagelink) {
				$randomImageURL = $randomImage->getFullimageURL();
			} else {
				$randomImageURL = $randomImage->getLink();
			}
			echo '<a href="' . html_encode($randomImageURL) . '" title="' . sprintf(gettext('View image: %s'), html_encode($randomImage->getTitle())) . '">';
			switch ($crop) {
				case 0:
					$sizes = getSizeCustomImage(array('size' => $width), $randomImage);
					$html = '<img src="' . html_encode($randomImage->getCustomImage(array('size' => $width, 'thumb' => TRUE))) . '" width="' . $sizes[0] . '" height="' . $sizes[1] . '" alt="' . html_encode($randomImage->getTitle()) . '" />' . "\n";
					$webp = $randomImage->getCustomImage(array('size' => $width, 'thumb' => TRUE, FALLBACK_SUFFIX));
					break;
				case 1:
					$sizes = getSizeCustomImage(array('width' => $width, 'height' => $height, 'cw' => $width, 'ch' => $height), $randomImage);
					$html = '<img src="' . html_encode($randomImage->getCustomImage(array('width' => $width, 'height' => $height, 'cw' => $width, 'ch' => $height, 'thumb' => TRUE))) . '" width="' . $sizes[0] . '" height="' . $sizes[1] . '" alt="' . html_encode($randomImage->getTitle()) . '" />' . "\n";
					$webp = $randomImage->getCustomImage(array('width' => $width, 'height' => $height, 'cw' => $width, 'ch' => $height, 'thumb' => TRUE, FALLBACK_SUFFIX));
					break;
				case 2:
					$sizes = getSizeDefaultThumb($randomImage);
					$html = '<img src="' . html_encode($randomImage->getThumb()) . '" width="' . $sizes[0] . '" height="' . $sizes[1] . '" alt="' . html_encode($randomImage->getTitle()) . '" />' . "\n";
					$webp = $randomImage->getThumb(NULL, NULL, FALLBACK_SUFFIX);
					break;
			}
			$html = npgFilters::apply('custom_image_html', $html, FALSE);
			if (ENCODING_FALLBACK) {
				$html = "<picture>\n<source srcset=\"" . html_encode($webp) . "\">\n" . $html . "</picture>\n";
			}
			echo $html;
			echo "</a>";
			echo "</li>\n";
		} else {
			break;
		}
	}
	echo "</ul>";
}

/**
 * Returns a list of tags for context of the page called where called
 *
 * @return string
 * @since 1.1
 */
function getTags() {
	if (in_context(NPG_IMAGE)) {
		global $_current_image;
		$tags = $_current_image->getTags();
	} else if (in_context(NPG_ALBUM)) {
		global $_current_album;
		$tags = $_current_album->getTags();
	} else if (in_context(ZENPAGE_PAGE)) {
		global $_CMS_current_page;
		$tags = $_CMS_current_page->getTags();
	} else if (in_context(ZENPAGE_NEWS_ARTICLE)) {
		global $_CMS_current_article;
		$tags = $_CMS_current_article->getTags();
	} else {
		$tags = array();
	}
	return $tags;
}

/**
 * Prints a list of tags, editable by admin
 *
 * @param string $option links by default, if anything else the
 *               tags will not link to all other images with the same tag
 * @param string $preText text to go before the printed tags
 * @param string $class css class to apply to the div surrounding the UL list
 * @param string $separator what charactor shall separate the tags
 * @since 1.1
 */
function printTags($option = 'links', $preText = NULL, $class = false, $separator = ', ') {
	global $_current_search;
	if (!$class) {
		$class = 'taglist';
	}
	$singletag = getTags();
	$tagstring = implode(', ', $singletag);
	if ($tagstring === '' or $tagstring === NULL) {
		$preText = '';
	}
	if (in_context(NPG_IMAGE)) {
		$object = "image";
	} else if (in_context(NPG_ALBUM)) {
		$object = "album";
	} else if (in_context(ZENPAGE_PAGE)) {
		$object = "pages";
	} else if (in_context(ZENPAGE_NEWS_ARTICLE)) {
		$object = "news";
	}
	if (count($singletag) > 0) {
		if (!empty($preText)) {
			echo "<span class=\"tags_title\">" . $preText . "</span>";
		}
		echo "<ul class=\"" . $class . "\">\n";
		if (is_object($_current_search)) {
			$albumlist = $_current_search->getAlbumList();
		} else {
			$albumlist = NULL;
		}
		$ct = count($singletag);
		$x = 0;
		foreach ($singletag as $atag) {
			if (++$x == $ct) {
				$separator = "";
			}
			if ($option === "links") {
				$links1 = "<a href=\"" . html_encode(getSearchURL(SearchEngine::searchQuote($atag), '', 'tags', 0, array('albums' => $albumlist))) . "\" title=\"" . html_encode($atag) . "\">";
				$links2 = "</a>";
			} else {
				$links1 = $links2 = '';
			}
			echo "\t<li>" . $links1 . $atag . $links2 . $separator . "</li>\n";
		}
		echo "</ul>";
	} else {
		echo "$tagstring";
	}
}

/**
 * Either prints all of the galleries tgs as a UL list or a cloud
 *
 * @param string $option "cloud" for tag cloud, "list" for simple list
 * @param string $class CSS class
 * @param string $sort "results" for relevance list, "random" for random ordering, otherwise the list is alphabetical
 * @param bool $counter TRUE if you want the tag count within brackets behind the tag
 * @param bool $links set to TRUE to have tag search links included with the tag.
 * @param int $maxfontsize largest font size the cloud should display
 * @param int $maxcount the floor count for setting the cloud font size to $maxfontsize
 * @param int $mincount the minimum count for a tag to appear in the output
 * @param int $limit set to limit the number of tags displayed to the top $numtags
 * @param int $minfontsize minimum font size the cloud should display
 * @since 1.1
 */
function printAllTagsAs($option, $class = '', $sort = NULL, $counter = FALSE, $links = TRUE, $maxfontsize = 2, $maxcount = 50, $mincount = 10, $limit = NULL, $minfontsize = 0.8) {
	global $_current_search;
	$option = strtolower($option);
	if ($class != "") {
		$class = ' class="' . $class . '"';
	}
	$tagcount = getAllTagsUnique(NULL, $mincount, true);

	if (!is_array($tagcount)) {
		return false;
	}
	arsort($tagcount);
	if (!is_null($limit)) {
		$tagcount = array_slice($tagcount, 0, $limit);
	}
	$keys = array_keys($tagcount);
	switch ($sort) {
		default:
			localeSort($keys);
			break;
		case 'results':
			//already in tag count order
			break;
		case 'random':
			shuffle_assoc($keys);
			break;
	}
	?>
	<ul<?php echo $class; ?>>
		<?php
		if (count($tagcount) > 0) {
			foreach ($keys as $key) {
				$val = $tagcount[$key];
				if (!$counter) {
					$counter = "";
				} else {
					$counter = " (" . $val . ") ";
				}
				if ($option == "cloud") { // calculate font sizes, formula from wikipedia
					if ($val <= $mincount) {
						$size = $minfontsize;
					} else {
						$size = min(max(round(($maxfontsize * ($val - $mincount)) / ($maxcount - $mincount), 2), $minfontsize), $maxfontsize);
					}
					$size = str_replace(',', '.', $size);
					$size = ' style="font-size:' . $size . 'em;"';
				} else {
					$size = '';
				}

				if ($links) {
					if (is_object($_current_search)) {
						$albumlist = $_current_search->getAlbumList();
					} else {
						$albumlist = NULL;
					}
					$link = getSearchURL(SearchEngine::searchQuote($key), '', 'tags', 0, array('albums' => $albumlist));
					?>
					<li>
						<a href="<?php echo html_encode($link); ?>" rel="nofollow"<?php echo $size; ?>><?php echo str_replace(' ', '&nbsp;', html_encode($key)) . $counter; ?></a>
					</li>
					<?php
				} else {
					?>
					<li<?php echo $size; ?>><?php echo str_replace(' ', '&nbsp;', html_encode($key)) . $counter; ?></li>
					<?php
				}
			}
		} else {
			?>
			<li><?php echo gettext('No popular tags'); ?></li>
			<?php
		}
		?>
	</ul>
	<?php
}

/**
 * Retrieves a list of all unique years & months from the images in the gallery
 *
 * @param string $order set to 'desc' for the list to be in descending order
 * @return array
 */
function getAllDates($order = 'asc') {
	$alldates = array();
	$cleandates = array();
	$sql = "SELECT `date` FROM " . prefix('images');
	if (!npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS | VIEW_UNPUBLISHED_RIGHTS)) {
		$sql .= " WHERE `show`=1";
	}
	$hidealbums = getNotViewableAlbums();
	if (!empty($hidealbums)) {
		if (npg_loggedin(MANAGE_ALL_ALBUM_RIGHTS | VIEW_UNPUBLISHED_RIGHTS)) {
			$sql .= ' WHERE ';
		} else {
			$sql .= ' AND ';
		}
		$sql .= '`albumid` NOT IN (' . implode(',', $hidealbums) . ')';
	}
	$result = query($sql);
	if ($result) {
		while ($row = db_fetch_assoc($result)) {
			$alldates[] = $row['date'];
		}
		db_free_result($result);
	}
	foreach ($alldates as $adate) {
		if (!empty($adate)) {
			$cleandates[] = substr($adate, 0, 7) . "-01";
		}
	}
	$datecount = array_count_values($cleandates);
	if ($order == 'desc') {
		krsort($datecount);
	} else {
		ksort($datecount);
	}
	return $datecount;
}

/**
 * Prints a compendum of dates and links to a search page that will show results of the date
 *
 * @param string $class optional class
 * @param string $yearid optional class for "year"
 * @param string $monthid optional class for "month"
 * @param string $order set to 'desc' for the list to be in descending order
 */
function printAllDates($class = 'archive', $yearid = 'year', $monthid = 'month', $order = 'asc') {
	global $_current_search, $_gallery_page;
	if (empty($class)) {
		$classactive = 'archive_active';
	} else {
		$classactive = $class . '_active';
		$class = "class=\"$class\"";
	}
	if ($_gallery_page == 'search.php') {
		$activedate = getSearchDate('Y-m');
	} else {
		$activedate = '';
	}
	if (!empty($yearid)) {
		$yearid = "class=\"$yearid\"";
	}
	if (!empty($monthid)) {
		$monthid = "class=\"$monthid\"";
	}
	$datecount = getAllDates($order);
	$lastyear = "";
	echo "\n<ul $class>\n";
	$nr = 0;
	foreach ($datecount as $key => $val) {
		$nr++;
		if ($key == '0000-00-01') {
			$year = "no date";
			$month = "";
		} else {
			$dt = date('Y-F', strtotime($key));
			$year = substr($dt, 0, 4);
			$month = substr($dt, 5);
		}

		if ($lastyear != $year) {
			$lastyear = $year;
			if ($nr != 1) {
				echo "</ul>\n</li>\n";
			}
			echo "<li $yearid>$year\n<ul $monthid>\n";
		}
		if (is_object($_current_search)) {
			$albumlist = $_current_search->getAlbumList();
		} else {
			$albumlist = NULL;
		}
		$datekey = substr($key, 0, 7);
		if ($activedate = $datekey) {
			$cl = ' class="' . $classactive . '"';
		} else {
			$cl = '';
		}
		echo "<li" . $cl . "><a href=\"" . html_encode(getSearchURl('', $datekey, '', 0, array('albums' => $albumlist))) . "\" rel=\"nofollow\">$month ($val)</a></li>\n";
	}
	echo "</ul>\n</li>\n</ul>\n";
}

/**
 * returns the rewrite part of a custom page link
 *
 * @global array $_conf_vars
 * @param string $page
 * @return string
 */
function getCustomPageRewrite($page) {
	global $_conf_vars;
	if (isset($_conf_vars['special_pages'][$page]['rewrite'])) {
		return preg_replace('~^_PAGE_/~', _PAGE_ . '/', $_conf_vars['special_pages'][$page]['rewrite']);
	} else {
		return '/' . _PAGE_ . '/' . $page;
	}
}

/**
 * Produces the url to a custom page (e.g. one that is not album.php, image.php, or index.php)
 *
 * @param string $page page name to include in URL
 * @param string $q query string to add to url
 * @param int $pageno set to a page number if that needs to be included in the URL
 * @return string
 */
function getCustomPageURL($page, $q = '', $pageno = NULL) {
	global $_current_album, $_gallery_page;
	$result_r = getCustomPageRewrite($page);
	$result = "index.php?p=$page";

	if ($pageno > 1) {
		$result_r .= '/' . $pageno;
		$result .= '&page=' . $pageno;
	}
	if (!empty($q)) {
		$result_r .= "?$q";
		$result .= "&$q";
	}

	return npgFilters::apply('getLink', rewrite_path($result_r, $result), $page . '.php', NULL);
}

/**
 * Prints the url to a custom page (e.g. one that is not album.php, image.php, or index.php)
 *
 * @param string $linktext Text for the URL
 * @param string $page page name to include in URL
 * @param string $q query string to add to url
 * @param string $prev text to insert before the URL
 * @param string $next text to follow the URL
 * @param string $class optional class
 */
function printCustomPageURL($linktext, $page, $q = '', $prev = '', $next = '', $class = false) {
	if (!$class) {
		$class = 'class="' . $class . '"';
	}
	echo $prev . "<a href=\"" . html_encode(getCustomPageURL($page, $q)) . "\" $class title=\"" . html_encode($linktext) . "\">" . html_encode($linktext) . "</a>" . $next;
}

//*** Search functions *******************************************************
//****************************************************************************

/**
 * tests if a search page is an "archive" page
 *
 * @return bool
 */
function isArchive() {
	return isset($_REQUEST['date']);
}

/**
 * Returns a search URL
 *
 * @param mixed $words the search words target
 * @param mixed $dates the dates that limit the search
 * @param mixed $fields the fields on which to search
 * NOTE: $words and $dates are mutually exclusive and $fields applies only to $words searches
 * @param int $page the page number for the URL
 * @param array $object_list the list of objects to search
 * @return string
 * @since 1.1.3
 */
function getSearchURL($words, $dates, $fields, $page, $object_list = NULL) {
	$urls = array();
	$rewrite = false;
	if (MOD_REWRITE) {
		$rewrite = true;
		if (is_array($object_list)) {
			foreach ($object_list as $obj) {
				if ($obj) {
					$rewrite = false;
					break;
				}
			}
		}
	}

	if ($rewrite) {
		$url = SEO_WEBPATH . '/' . _SEARCH_ . '/';
	} else {
		$url = SEO_WEBPATH . "/index.php";
		$urls[] = 'p=search';
	}
	if ($words) {
		if (is_array($words)) {
			foreach ($words as $key => $word) {
				$words[$key] = SearchEngine::searchQuote($word);
			}
			$words = implode(',', $words);
		}
		$words = SearchEngine::encode($words);
		if ($rewrite) {
			$url .= $words . '/';
		} else {
			$urls[] = 'words=' . $words;
		}
		if (!empty($fields)) {
			if (!is_array($fields)) {
				$fields = explode(',', $fields);
			}
			$temp = $fields;
			if ($rewrite && count($fields) == 1 && reset($temp) == 'tags') {
				$url = SEO_WEBPATH . '/' . _TAGS_ . '/' . $words . '/';
			} else {
				$search = new SearchEngine();
				$urls[] = $search->getSearchFieldsText($fields, 'searchfields=');
			}
		}
	} else { //	dates
		if (is_array($dates)) {
			$dates = implode(',', $dates);
		}
		if ($rewrite) {
			$url = SEO_WEBPATH . '/' . _ARCHIVE_ . '/' . $dates . '/';
		} else {
			$urls[] = "date=$dates";
		}
	}
	if ($page > 1) {
		if ($rewrite) {
			$url .= $page;
		} else {
			$urls[] = "page=$page";
		}
	}

	if (is_array($object_list)) {
		foreach ($object_list as $key => $list) {
			if (!empty($list)) {
				if (is_array($list)) {
					$list = implode(',', $list);
				}
				$urls[] = 'in' . $key . '=' . urlencode($list);
			}
		}
	}
	if (!empty($urls)) {
		$url .= '?' . implode('&', $urls);
	}

	return $url;
}

/**
 * Prints the search form
 *
 * Search works on a list of tokens entered into the search form.
 *
 * Tokens may be part of boolean expressions using &, |, !, and parens. (Comma is retained as a synonom of | for
 * backwords compatibility.)
 *
 * Tokens may be enclosed in quotation marks to create exact pattern matches or to include the boolean operators and
 * parens as part of the tag..
 *
 * @param string $options options array / text to go before the search form
 * @param string $id css id for the search form, default is 'search'
 * @param string $buttonSource optional path to the image for the button or if not a path to an image,
 * 											this will be the button hint
 * @param string $buttontext optional text for the button ("Search" will be the default text)
 * @param string $iconsource optional theme based icon for the search fields toggle
 * @param array $query_fields override selection for enabled fields with this list
 * @param array $objects_list optional array of things to search eg. [albums]=>[list], etc.
 * 														if the list is simply 0, the objects will be omitted from the search
 * @param string $within set to true to search within current results, false to search fresh
 * @param string $placeholder HTML5 placeholder text for search words input field
 * @since 1.1.3
 */
function printSearchForm($options = NULL, $id = 'search', $buttonSource = false, $buttontext = '', $iconsource = NULL, $query_fields = NULL, $object_list = NULL, $within = NULL, $placeholder = NULL) {
	global $_current_search, $_current_album;

	if (is_array($options)) {
		$default = array('prevtext' => NULL, 'id' => 'search', 'buttonSource' => gettext("Search"), 'buttontext' => '', 'iconsource' => NULL, 'query_fields' => NULL, 'object_list' => NULL, 'within' => NULL, 'placeholder' => gettext('Search target'));
		$options = array_merge($default, $options);
		extract($options);
	} else {
		$prevtext = $options;
		if (is_null($buttonSource)) {
			$buttonSource = false;
		}
		if (empty($buttontext)) {
			$buttontext = gettext("Search");
		}
		if (is_null($placeholder)) {
			$placeholder = gettext('Search target');
		}
	}
	$engine = new SearchEngine();
	if (!is_null($_current_search) && !$_current_search->getSearchWords()) {
		$engine->clearSearchWords();
	}

	if ($placeholder) {
		$placeholder = ' placeholder="' . $placeholder . '"';
	}
	$searchwords = $engine->codifySearchString();
	if (substr($searchwords, -1, 1) == ',') {
		$searchwords = substr($searchwords, 0, -1);
	}
	if (empty($searchwords)) {
		$within = false;
	} else if (is_null($within)) {
		$within = getOption('search_within');
	}

	$hint_new = $buttontext;
	$hint_in = gettext('Search within previous results');
	if ($within) {
		$button = ' title="' . $hint_in . '"';
		$buttontext = $hint_in;
	} else {
		$button = ' title="' . $buttontext . '"';
	}
	if (preg_match('!\/(.*)[\.png|\.jpg|\.jpeg|\.gif]$!', $buttonSource)) {
		$buttonSource = 'src="' . $buttonSource . '" alt="' . $buttontext . '"';
		$type = 'image';
	} else {
		$button = 'value="' . $buttontext . '" ' . $button;
		$type = 'submit';
		$buttonSource = '';
	}

	if (empty($iconsource)) {
		$iconsource = SEARCHFIELDS_ICON;
	} else {
		$iconsource = '<image src="' . $iconsource . '" alt="' . gettext('fields') . '" id="searchfields_icon" />';
	}

	if (MOD_REWRITE) {
		$searchurl = SEO_WEBPATH . '/' . _SEARCH_ . '/';
	} else {
		$searchurl = WEBPATH . "/index.php?p=search";
	}

	if (!$within) {
		$engine->clearSearchWords();
	}

	$fields = $engine->allowedSearchFields();
	?>
	<div id="<?php echo $id; ?>"><!-- start of search form -->
		<!-- search form -->
		<form method="post" action="<?php echo $searchurl; ?>" id="search_form">
			<?php echo $prevtext; ?>
			<div>
				<span class="tagSuggestContainer">
					<input type="text" name="words" value="" id="search_input" class="tagsuggest" size="10"<?php echo $placeholder; ?> />
				</span>
				<?php
				if (count($fields) > 1 || $searchwords) {
					?>
					<span class="searchextra">
						<a onclick="$('#searchextrashow').toggle();" style="cursor: pointer;" title="<?php echo gettext('search options'); ?>">
							<?php echo $iconsource; ?>
						</a>
					</span>
					<?php
				}
				?>
				<input type="<?php echo $type; ?>" <?php echo $button; ?> class="buttons" id="search_submit" <?php echo $buttonSource; ?> data-role="none" />
				<?php
				if (is_array($object_list)) {
					foreach ($object_list as $key => $list) {
						if (is_array($list)) {
							if ($key == 'albums' && count($list) == 1 && $_current_album && $_current_album->name == end($list)) {
								// special case for current album, search its offspring
								$list = array_merge($list, $_current_album->getOffspring());
							}
							$list = implode(',', $list);
						}
						?>
						<input type="hidden" name="in<?php echo $key ?>" value="<?php echo html_encode($list); ?>" />
						<?php
					}
				}
				?>
				<br />
				<?php
				if (count($fields) > 1 || $searchwords) {
					if (is_null($query_fields)) {
						$query_fields = $engine->parseQueryFields();
					} else {
						if (!is_array($query_fields)) {
							$query_fields = $engine->numericFields($query_fields);
						}
					}
					if (count($query_fields) == 0) {
						$query_fields = $engine->allowedSearchFields();
					}
					?>
					<div style="display:none;" id="searchextrashow">
						<ul>
							<?php
							if ($searchwords) {
								if (count($fields) > 1 && !$within) {
									?>
									<li style="border-bottom: 1px dotted;">
										<?php
									} else {
										?>
									<li>
										<?php
									}
									?>
									<label title="<?php echo $hint_in; ?>">
										<input type="radio" name="search_within" value="1"<?php if ($within) echo ' checked="checked"'; ?>  />
										<?php echo gettext('Within'); ?>
									</label>
									<label title="<?php echo $hint_new; ?>">
										<input type="radio" name="search_within" value="0"<?php if (!$within) echo ' checked="checked"'; ?> />
										<?php echo gettext('New'); ?>
									</label>
									&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									<?php
								}

								if (count($fields) > 1) {
									if (!$searchwords) {
										?>
									<li  style="border-bottom: 1px dotted;">
										<?php
									}
									?>
									<label title="<?php echo gettext('Select/deselect all fields'); ?>">
										<input type="checkbox" class="SEARCH_new" id="SEARCH_checkall" checked="checked" onclick="search_all();" <?php if ($within) echo ' disabled="disabled"'; ?> /> <strong><em><?php echo gettext('All'); ?></em></strong>
									</label>
								</li>
								<?php
								foreach ($fields as $display => $key) {
									?>
									<li>
										<?php
										if (in_array($key, $query_fields)) {
											$checked = ' checked="checked"';
											?>
											<input type="hidden" class="SEARCH_within" name="SEARCH_<?php echo $key; ?>" value="<?php echo html_encode($key); ?>"<?php if (!$within) echo ' disabled="disabled"'; ?> />
											<?php
										} else {
											$checked = '';
										}
										?>
										<label>
											<input class="SEARCH_checkall SEARCH_new" id="SEARCH_<?php echo $key; ?>" name="SEARCH_<?php echo $key; ?>" type="checkbox"<?php echo $checked; ?> value="<?php echo html_encode($key); ?>"<?php if ($within) echo ' disabled="disabled"'; ?> />
											<?php echo html_encode(trim($display, ':')); ?>
										</label>

									</li>
									<?php
								}
							} else {
								?>
								<input type="hidden" name="SEARCH_<?php echo $key = array_pop($fields); ?>" value="<?php echo html_encode($key); ?>" />
								</li>
								<?php
							}
							?>
						</ul>
					</div>
					<?php
				}
				?>
			</div>
			<script type="text/javascript">
				// <!-- <![CDATA[
				var within = <?php echo (int) $within; ?>;
				$("input[name='search_within']").change(function () {
					within = (within + 1) & 1;
					if (within) {
						$('#search_submit').prop('title', '<?php echo $hint_in; ?>');
						$('.SEARCH_new').prop('checked', false);
						$('.SEARCH_within').each(function () {
							id = $(this).attr('name');
							$('#' + id).prop('checked', 'checked');
						});
						$('.SEARCH_new').prop('disabled', 'disabled');
						$('.SEARCH_within').removeAttr('disabled');
					} else {
						lastsearch = '';
						$('#search_submit').prop('title', '<?php echo $hint_new; ?>');
						$('.SEARCH_new').removeAttr('disabled');
						$('.SEARCH_within').prop('disabled', 'disabled');
					}
					$('#search_input').val('');
				});

				$('#search_form').submit(function () {
					if (within) {
						var newsearch = $.trim($('#search_input').val());
						if (newsearch.substring(newsearch.length - 1) == ',') {
							newsearch = newsearch.substr(0, newsearch.length - 1);
						}
						if (newsearch.length > 0) {
							$('#search_input').val('(<?php echo $searchwords; ?>) AND (' + newsearch + ')');
						} else {
							$('#search_input').val('<?php echo $searchwords; ?>');
						}
					}
					return true;
				});
				function search_all() {
					//search all is Copyright 2014 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives. All rights reserved
					var check = $('#SEARCH_checkall').prop('checked');
					$('.SEARCH_checkall').prop('checked', check);
				}

				// ]]> -->
			</script>
		</form>
	</div><!-- end of search form -->
	<?php
}

/**
 * Returns the a sanitized version of the search string
 *
 * @return string
 * @since 1.1
 */
function getSearchWords() {
	global $_current_search;
	if (!in_context(NPG_SEARCH))
		return '';
	return stripcslashes($_current_search->codifySearchString());
}

/**
 * Returns the date of the search
 *
 * @param string $format formatting of the date, default 'F Y'
 * @return string
 * @since 1.1
 */
function getSearchDate($format = 'F Y') {
	if (in_context(NPG_SEARCH)) {
		global $_current_search;
		$date = $_current_search->getSearchDate();
		if (empty($date)) {
			return "";
		}
		if ($date == '0000-00') {
			return gettext("no date");
		}
		$dt = strtotime($date . "-01");
		return formattedDate($format, $dt);
	}
	return false;
}

/**
 * controls the thumbnail layout of themes.
 *
 * Computes a normalized images/albums per page and computes the number of
 * images that will fit on the "transitional" page between album thumbs and
 * image thumbs. This function is "internal" and is called from the root
 * index.php script before the theme script is loaded.
 */
function getTransitionImageCount() {
	$transitionImages = false;
	if (getOption('thumb_transition') && in_context(NPG_ALBUM | NPG_SEARCH)) {
		if ($imagesPerPage = galleryImagesPerPage()) {
			$count = getNumAlbums();
			if ($count > 0) {
				$albums_per_page = galleryAlbumsPerPage();
				$orphans = $count % $albums_per_page;
				if ($orphans > 0) {
					$available = 1 - $orphans / $albums_per_page;
					$transitionImages = round($available * $imagesPerPage);
					if ($imageMulltiple = getOption('images_per_row')) {
						$transitionImages = floor($transitionImages / $imageMulltiple) * $imageMulltiple;
					}
				}
			}
		} else {
			$transitionImages = getNumImages();
		}
	}
	return (int) $transitionImages;
}

//************************************************************************************************
// album password handling
//************************************************************************************************

/**
 * returns the auth type of a guest login
 *
 * @param string $hint
 * @param string $show
 * @return string
 */
function checkForGuest(&$hint = NULL, &$show = NULL) {
	global $_gallery, $_gallery_page, $_CMS_current_page, $_CMS_current_category, $_CMS_current_article;
	if (in_context(NPG_SEARCH)) { // search page
		$hash = getOption('search_password');
		if (getOption('search_user') != '')
			$show = true;
		$hint = get_language_string(getOption('search_hint'));
		$authType = 'search_auth';
		if (empty($hash)) {
			$hash = $_gallery->getPassword();
			if ($_gallery->getUser() != '')
				$show = true;
			$hint = $_gallery->getPasswordHint();
			$authType = 'gallery_auth';
		}
		if (!empty($hash) && getNPGCookie($authType) == $hash) {
			return $authType;
		}
	} else if (!is_null($_CMS_current_article)) {
		$authType = $_CMS_current_article->checkAccess($hint, $show);
		return $authType;
	} else if (!is_null($_CMS_current_page)) {
		$authType = $_CMS_current_page->checkAccess($hint, $show);
		return $authType;
	} else if (!is_null($_CMS_current_category)) {
		$authType = $_CMS_current_category->checkAccess($hint, $show);
		return $authType;
	} else if (isset($_GET['album'])) { // album page
		list($album, $image) = rewrite_get_album_image('album', 'image');
		if ($authType = checkAlbumPassword($album, $hint)) {
			return $authType;
		} else {
			$alb = newAlbum($album);
			if ($alb->getUser() != '')
				$show = true;
			return false;
		}
	} else { // other page
		$hash = $_gallery->getPassword();
		if ($_gallery->getUser() != '') {
			$show = true;
		}
		$hint = $_gallery->getPasswordHint();
		if (!empty($hash) && getNPGCookie('gallery_auth') == $hash) {
			return 'gallery_auth';
		}
	}
	if (empty($hash))
		return 'public_access';
	return false;
}

/**
 * Checks to see if a password is needed
 *
 * Returns true if access is allowed
 *
 * The password protection is hereditary. This normally only impacts direct url access to an object since if
 * you are going down the tree you will be stopped at the first place a password is required.
 *
 *
 * @param string $hint the password hint
 * @param bool $show whether there is a user associated with the password.
 * @return bool
 * @since 1.1.3
 */
function checkAccess(&$hint = NULL, &$show = NULL) {
	global $_current_album, $_current_search, $_gallery, $_gallery_page,
	$_CMS_current_page, $_CMS_current_article;

	if (GALLERY_SECURITY != 'public') { // only registered users allowed
		$show = true; //	therefore they will need to supply their user id if something fails below
	} else if (is_null($show)) {
		$show = $_gallery->getUserLogonField();
	}

	if ($_gallery->isUnprotectedPage(stripSuffix($_gallery_page)))
		return true;
	if (npg_loggedin()) {
		$fail = npgFilters::apply('isMyItemToView', NULL);
		if (!is_null($fail)) { //	filter had something to say about access, honor it
			return $fail;
		}
		switch ($_gallery_page) {
			case 'album.php':
			case 'image.php':
				if ($_current_album->isMyItem(LIST_RIGHTS)) {
					return true;
				}
				break;
			case 'search.php':
				if (npg_loggedin(VIEW_SEARCH_RIGHTS)) {
					return true;
				}
				break;
			default:
				if (npg_loggedin(VIEW_GALLERY_RIGHTS)) {
					return true;
				}
				break;
		}
	}
	if (GALLERY_SECURITY == 'public' && ($access = checkForGuest($hint, $show))) {
		return $access; // public page or a guest is logged in
	}
	return false;
}

/**
 * Prints the album password form
 *
 * @param string $hint hint to the password
 * @param bool $showProtected set false to supress the password protected message
 * @param bool $showuser set true to force the user name filed to be present
 * @param string $redirect optional URL to send the user to after successful login
 *
 * @since 1.1.3
 */
function printPasswordForm($_password_hint, $_password_showuser = NULL, $_password_showProtected = true, $password_redirect = NULL, $showLogo = NULL) {
	global $_login_error, $_password_form_printed, $_current_search, $_gallery, $_gallery_page,
	$_current_album, $_current_image, $theme, $_CMS_current_page, $_authority;
	if ($_password_form_printed)
		return;
	$_password_form_printed = true;
	if (is_null($password_redirect)) {
		$parts = mb_parse_url(getRequestURI());
		if (array_key_exists('query', $parts)) {
			$query = parse_query($parts['query']);
			unset($query['logout']);
		} else {
			$query = array();
		}
		if (isset($_GET['p']) && $_GET['p'] == 'password') {
			// redirecting here would be terribly confusing
			unset($query['p']);
			$parts['path'] = SEO_WEBPATH;
		}
		$parts['query'] = http_build_query($query);
		$password_redirect = build_url($parts);
	}
	?>
	<div id="passwordform">
		<?php
		if ($_password_showProtected && !$_login_error) {
			?>
			<p>
				<?php echo gettext("The page you are trying to view is password protected."); ?>
			</p>
			<?php
		}
		if ($loginlink = npgFilters::apply('login_link', NULL)) {
			$logintext = gettext('login');
			?>
			<a href="<?php echo $loginlink; ?>" title="<?php echo $logintext; ?>"><?php echo $logintext; ?></a>
			<?php
		} else {
			$_authority->printLoginForm($password_redirect, $showLogo, $_password_showuser, NULL, $_password_hint);
		}
		?>
	</div>
	<?php
}

/**
 * Prints the logo and link
 *
 */
function print_SW_Link() {
	?>
	<span class="npg-logo" style="text-decoration: none;">
		<a href="https://netPhotoGraphics.org" title="<?php echo gettext('A media oriented content management system'); ?>">
			<?php printf(gettext('Powered by %s'), swLogo()); ?>
		</a>
	</span>
	<?php
}

/**
 * Expose some informations in a HTML comment
 *
 * @param string $obj the path to the page being loaded
 * @param array $plugins list of activated plugins
 * @param string $theme The theme being used
 */
function exposeSoftwareInformation($obj = '', $plugins = '', $theme = '') {
	global $_filters;
	$unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
	$memoryLimit = INI_GET('memory_limit');
	if ($memoryLimit > 0) {
		$suffixes = array('' => 1, 'k' => 1024, 'm' => 1048576, 'g' => 1073741824);
		if (preg_match('/([0-9]+)\s*(k|m|g)?(b?(ytes?)?)/i', $memoryLimit, $match)) {
			$memoryLimit = $match[1] * $suffixes[strtolower($match[2])];
		}
		$memoryLimit = round($memoryLimit / pow(1024, ($i = floor(log($memoryLimit, 1024)))), 1) . ' ' . $unit[$i];
	} else {
		$memoryLimit = 'none';
	}
	$memoryUsed = memory_get_peak_usage();
	$memoryUsed = round($memoryUsed / pow(1024, ($i = floor(log($memoryUsed, 1024)))), 1) . ' ' . $unit[$i];
	$a = basename($obj);

	echo "\n<!--\n netPhotoGraphics version " . NETPHOTOGRAPHICS_VERSION . "\n";
	echo " THEME: " . $theme . " (" . $a . ")\n";
	$graphics = gl_graphicsLibInfo();
	$graphics = str_replace('<br />', ', ', $graphics['Library_desc']);
	printf(gettext(' PHP memory limit: %1$s; %2$s used' . "\n"), $memoryLimit, $memoryUsed);
	echo " GRAPHICS LIB: " . strip_tags($graphics) . "\n";
	echo " PLUGINS:\n";
	if (count($plugins) > 0) {
		sort($plugins);
		foreach ($plugins as $plugin) {
			echo '  ' . $plugin . "\n";
		}
	} else {
		echo "none \n";
	}
	echo " -->\n";
}

/**
 * Checks for acknowledged site policy
 *
 * @global object $_current_admin_obj
 * @return bool true if acknowledged
 */
function policyACKCheck() {
	global $_current_admin_obj;
	return ($_current_admin_obj && $_current_admin_obj->getPolicyAck()) || getNPGCookie('policyACK') >= getOption('GDPR_cookie');
}

/**
 * displays a policy submit controlled button
 *
 * @global type $_current_admin_obj
 * @param string $buttonText The text displayed on the button
 * @param string $buttonClass optional class to be added to the button
 * @param string $buttonExtra provided for captcha support
 * @param array $buttonLinked an array of buttons that should be shown when the acknowledge is checked
 */
function policySubmitButton($buttonText, $buttonClass = NULL, $buttonExtra = NULL, $buttonLinked = NULL) {
	global $_current_admin_obj;
	if (getOption('GDPR_acknowledge') && !policyACKCheck()) {
		$linked = '';
		if ($buttonLinked) {
			foreach ($buttonLinked as $button) {
				$linked .= "$('#" . $button . "').show();";
			}
		}
		?>
		<span class="policy_acknowledge_check_box">
			<input id="GDPR_acknowledge" type="checkbox" name="policy_acknowledge" onclick="$(this).parent().next().show();
						 <?php echo $linked; ?>
					$(this).parent().hide();" value="<?php echo md5(getUserID() . getOption('GDPR_cookie')); ?>">
						 <?php
						 echo sprintf(get_language_string(getOption('GDPR_text')), getOption('GDPR_URL'));
						 ?>
		</span>
		<?php
		$display = ' style="display:none;" ';
	} else {
		$display = '';
	}
	npgButton('submit', $buttonText, array('buttonClass' => 'policyButton ' . $buttonClass, 'id' => 'submitbutton', 'buttonExtra' => $display . $buttonExtra));

	return $display;
}

function recordPolicyACK($user = NULL) {
	global $_current_admin_obj;
	if (is_null($user)) {
		$user = $_current_admin_obj;
	}
	if (isset($_POST['policy_acknowledge']) && $_POST['policy_acknowledge'] == md5(getUserID() . getOption('GDPR_cookie'))) {
		if ($user) {
			$user->setPolicyAck(1);
			$user->save();
			$what = $user->getUser();
		} else {
			setNPGCookie('policyACK', getOption('GDPR_cookie'));
			require_once (CORE_SERVERPATH . 'class-browser.php');
			$browser = new Browser();
			$what = sprintf(gettext('%1$s policyACK cookie'), $browser->getBrowser());
		}
		if (extensionEnabled('security-logger')) {
			require_once(PLUGIN_SERVERPATH . 'security-logger.php');
			npgFilters::apply('policy_ack', true, 'PolicyACK', 1, $what);
		}
	}
}

/**
 * Gets the content of a codeblock for an image, album or Zenpage newsarticle or page.
 *
 * The priority for codeblocks will be (based on context)
 * 	1: articles
 * 	2: pages
 * 	3: images
 * 	4: albums
 * 	5: gallery.
 *
 * This means, for instance, if we are in ZENPAGE_NEWS_ARTICLE context we will use the news article
 * codeblock even if others are available.
 *
 * Note: Echoing this array's content does not execute it. Also no special chars will be escaped.
 * Use printCodeblock() if you need to execute script code.
 *
 * @param int $number The codeblock you want to get
 * @param mixed $what optonal object for which you want the codeblock
 *
 * @return string
 */
function getCodeblock($number = 1, $object = NULL) {
	global $_current_album, $_current_image, $_CMS_current_article, $_CMS_current_page, $_gallery, $_gallery_page;
	if (!$number) {
		setOptionDefault('codeblock_first_tab', 0);
	}
	if (!is_object($object)) {
		if ($_gallery_page == 'index.php' || $_gallery_page == 'gallery.php') {
			$object = $_gallery;
		}
		if (in_context(NPG_ALBUM)) {
			$object = $_current_album;
		}
		if (in_context(NPG_IMAGE)) {
			$object = $_current_image;
		}
		if (in_context(ZENPAGE_PAGE)) {
			if ($_CMS_current_page->checkAccess()) {
				$object = $_CMS_current_page;
			}
		}
		if (in_context(ZENPAGE_NEWS_ARTICLE)) {
			if ($_CMS_current_article->checkAccess()) {
				$object = $_CMS_current_article;
			}
		}
	}
	if (!is_object($object)) {
		return NULL;
	}
	$codeblock = getSerializedArray($object->getcodeblock());
	if (isset($codeblock[$number])) {
		$codeblock = npgFilters::apply('codeblock', $codeblock[$number], $object, $number);
		if ($codeblock) {
			$codeblock = applyMacros($codeblock);
		}
	} else {
		$codeblock = '';
	}
	return $codeblock;
}

/**
 * Prints the content of a codeblock for an image, album or Zenpage newsarticle or page.
 *
 * @param int $number The codeblock you want to get
 * @param mixed $what optonal object for which you want the codeblock
 *
 * @return string
 */
function printCodeblock($number = 1, $what = NULL) {
	$codeblock = getCodeblock($number, $what);
	if ($codeblock) {
		$context = get_context();
		eval('?>' . $codeblock);
		set_context($context);
	}
}

/**
 * Checks for URL page out-of-bounds for "standard" themes
 * Note: This function assumes that an "index" page will display albums
 * and the pagination be determined by them. Any other "index" page strategy needs to be
 * handled by the theme itself.
 *
 * @param boolean $request
 * @param string $gallery_page
 * @param int $page
 * @return boolean will be true if all is well, false if a 404 error should occur
 */
function checkPageValidity($request, $gallery_page, $page) {
	global $_gallery, $_transitionImageCount, $_CMS, $_CMS_current_category;
	$count = NULL;
	switch ($gallery_page) {
		case 'album.php':
		case 'favorites.php';
		case 'search.php':
			$albums_per_page = galleryAlbumsPerPage();
			$pageCount = (int) ceil(getNumAlbums() / $albums_per_page);
			$imageCount = getNumImages();
			$images_per_page = galleryImagesPerPage();
			if (!$images_per_page) {
				$imageCount = min(1, $imageCount);
				$images_per_page = 1;
			}

			$count = ($pageCount + (int) ceil(($imageCount - $_transitionImageCount) / $images_per_page));
			break;
		case 'index.php':
			if (galleryAlbumsPerPage() != 0) {
				$count = (int) ceil($_gallery->getNumAlbums() / galleryAlbumsPerPage());
			}
			break;
		case 'news.php':
			if (in_context(ZENPAGE_NEWS_CATEGORY)) {
				$count = count($_CMS_current_category->getArticles());
			} else {
				$count = count($_CMS->getArticles());
			}
			$count = (int) ceil($count / ARTICLES_PER_PAGE);
			break;
		default:
			$count = npgFilters::apply('checkPageValidity', NULL, $gallery_page, $page);
			break;
	}
	if ($page > $count) {
		$request = false; //	page is out of range
	}

	return $request;
}

function print404status() {
	global $_404_data;
	list($album, $image, $galleryPage, $theme, $page) = $_404_data;

	$log = npgFilters::apply('log_404', DEBUG_404 && !preg_match('~\.(css|js|min)\.map$~i', $album), $_404_data); //	don't log these
	if ($log) {
		$list = explode('/', $album);
		if (reset($list) != 'cache') {
			$target = getRequestURI();
			if (!in_array($target, array(WEBPATH . '/favicon.ico', WEBPATH . '/' . DATA_FOLDER . '/tést.jpg'))) {
				$output = "404 error details\n\t\t\tSERVER:\n";
				foreach (array('REQUEST_URI', 'HTTP_REFERER', 'REMOTE_ADDR', 'HTTP_USER_AGENT', 'REDIRECT_STATUS') as $key) {
					if (isset($_SERVER[$key])) {
						$value = "'$_SERVER[$key]'";
					} else {
						$value = 'NULL';
					}
					$output .= "\t\t\t\t\t$key\t=>\t$value\n";
				}
				$output .= "\t\t\tREQUEST:\n";
				$request = $_REQUEST;
				$request['theme'] = $theme;
				if (!empty($image)) {
					$request['image'] = $image;
				}
				foreach ($request as $key => $value) {
					if (is_array($value)) {
						$value = '*ARRAY*';
					} else {
						$value = truncate_string($value, 50);
					}
					$output .= "\t\t\t\t\t$key\t=>\t'$value'\n";
				}
				debugLog($output);
			}
		}
	}

	echo "\n<strong>" . gettext("Error:</strong> the requested object was not found.");
	if ($album) {
		echo '<br />' . sprintf(gettext('Album: %s'), html_encode($album));
		if ($image) {
			echo '<br />' . sprintf(gettext('Image: %s'), html_encode($image));
		}
	} else {
		echo '<br />' . sprintf(gettext('Page: %s'), html_encode(substr(basename($galleryPage), 0, -4)));
	}
	if ($page > 1) {
		echo '/' . $page;
	}
}

function loadJqueryMobile() {
	scriptLoader(PLUGIN_SERVERPATH . 'common/jquerymobile/jquery.mobile-1.4.5.min.css');
	scriptLoader(PLUGIN_SERVERPATH . 'common/jquerymobile/jquery.mobile-1.4.5.min.js');
}

/**
 *
 * @return type an array of the menus defined
 */
function getMenuSets() {
	if (extensionEnabled('menu_manager')) {
		$menusets = array();
		$result = query_full_array("SELECT DISTINCT menuset FROM " . prefix('menu') . " ORDER BY menuset");
		foreach ($result as $set) {
			$menusets[$set['menuset']] = $set['menuset'];
		}
		return $menusets;
	}
	return array();
}

/**
 * A class to allow simple geo map printing independent of which plugin is enabled.
 *
 * Note: it will gracefully do nothing if there is no map plugin enabled.
 */
class simpleMap {
	/*
	 * You can customize (within reason) some of these by copying this class
	 * and changing these values. But note, of course, that the two plugins do
	 * things differently.
	 */

// default values for printGoogleMap parameters
	static $text = NULL;
	static $hide = NULL;
// default values for printOpenStreetMap parameters
	static $width = NULL;
	static $height = NULL;
	static $mapcenter = NULL;
	static $zoom = NULL;
	static $fitbounds = NULL;
	static $mapnumber = NULL;
	static $minimap = false;

	/**
	 * returns the name of the map print function (if there is one)
	 *
	 * @return string
	 */
	static function mapPlugin() {
		if (class_exists('googleMap')) {
			return 'googleMap';
		}
		if (class_exists('openStreetMap')) {
			return 'openStreetMap';
		}
		return NULL;
	}

	/**
	 * used to collect geo coordinate points (typically for album pages)
	 *
	 * @param type $image
	 * @return boolean
	 */
	static function getCoord($image) {
		if (class_exists('googleMap')) {
			return GoogleMap::getGeoCoord($image);
		}
		if (class_exists('openStreetMap')) {
			return openStreetMap::getGeoCoord($image);
		}
		return false;
	}

	static function mapDisplay() {
		if (class_exists('googleMap')) {
			return getOption('gmap_display');
		}
		if (class_exists('openStreetMap')) {
			return getOption('osmap_display');
		}
		return 'show';
	}

	static function setMapDisplay($hide) {
		if (class_exists('googleMap')) {
			setOption('gmap_display', $hide, false);
		}
		if (class_exists('openStreetMap')) {
			setOption('osmap_display', $hide, false);
		}
	}

	/**
	 * This is the generic print map function. If you want any refinement then
	 * you will have to call the appropriate plugin directly since there is no
	 * mapping of their parameters.
	 *
	 * @global type $_simpleMap_map_points collector for the map points
	 *
	 * @param array $points
	 * @param array $options an array of index=>value as per below
	 *        text label for the map†
	 *        id css id of the map
	 *        hide show/hide the map†
	 *        obj the "thing" which the map is showing
	 *        class css class of the map†
	 *
	 * † use is plugin dependent
	 */
	static function printMap($points = NULL, $options = array()) {
		$text = $id = $hide = $obj = NULL;
		$class = '';
		extract($options);
		global $_simpleMap_map_points;
		if (class_exists('googleMap')) {
			global $_simpleMap_map_points;
			if (!is_null($callback = $points)) {
				$_simpleMap_map_points = $points;
				$callback = 'simpleMap::callback';
			}
			printGoogleMap(self::$text, $id, $hide, $obj, $callback);
		} else if (class_exists('openStreetMap')) {
			printOpenStreetMap($points, self::$width, self::$height, self::$mapcenter, self::$zoom, self::$fitbounds, $class, '', $obj, self::$minimap, $id, $hide, $text);
		}
	}

	/**
	 * callback function for printGoogleMap
	 *
	 * @global type $_simpleMap_map_points collector for the map points
	 *
	 * @param object $map
	 */
	static function callback($map) {
		global $_simpleMap_map_points;
		foreach ($_simpleMap_map_points as $coord) {
			GoogleMap::addGeoCoord($map, $coord);
		}
	}

}

require_once(CORE_SERVERPATH . 'template-filters.php');
?>
