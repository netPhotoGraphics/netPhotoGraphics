<?php

/**
 * This is the class declaration
 * @author Stephen Billard (sbillard)
 * @package plugins/favoritesHandler
 */
class favorites extends AlbumBase {

	public $image_sort_direction;
	public $album_sort_direction;
	public $image_sort_type;
	public $album_sort_type;
	public $list = array('');
	public $owner;
	public $instance = '';
	public $multi;
	public $imageNames; // list of images for handling duplicate file names

	function __construct($user) {

		if (OFFSET_PATH == 2) {
			$sql = 'UPDATE ' . prefix('plugin_storage') . ' SET `type`="favoritesHandler" WHERE `type`="favorites"';
			query($sql);
		}
		$this->table = 'albums';
		$this->name = $user;
		$this->setOwner($this->owner = $user);
		$this->setTitle(get_language_string(getOption('favorites_title')));
		$this->setDesc(get_language_string(getOption('favorites_desc')));
		$this->image_sort_direction = getOption('favorites_image_sort_direction');
		$this->album_sort_direction = getOption('favorites_album_sort_direction');
		$this->image_sort_type = getOption('favorites_image_sort_type');
		$this->album_sort_type = getOption('favorites_album_sort_type');
		$this->multi = getOption('favorites_multi');
		$list = query_full_array('SELECT `aux` FROM ' . prefix('plugin_storage') . ' WHERE `type`="favoritesHandler" AND `aux` LIKE' . db_quote('%' . $user . '%'));

		foreach ($list as $aux) {
			$instance = getSerializedArray($aux['aux']);
			if (isset($instance[1])) {
				$this->multi = true;
				$this->list[$instance[1]] = $instance[1];
			}
		}
	}

	protected function getInstance() {
		if ($this->instance) {
			return serialize(array($this->owner, $this->instance));
		} else {
			return $this->owner;
		}
	}

	function getList() {
		return $this->list;
	}

	function addImage($img) {
		$folder = $img->imagefolder;
		$filename = $img->filename;
		if ($this->instance) {
			$subtype = '"named"';
		} else {
			$subtype = 'NULL';
		}
		$db_instance = db_quote($this->getInstance());
		$db_data = db_quote(serialize(array('type' => 'images', 'id' => $folder . '/' . $filename)));
		if (query_single_row('SELECT * FROM ' . prefix('plugin_storage') .
										' WHERE `type`="favoritesHandler"' .
										' AND `subtype`=' . $subtype .
										' AND `aux`=' . $db_instance .
										' AND `data`=' . $db_data)) {
			return; //	already a favorite
		}
		$sql = 'INSERT INTO ' . prefix('plugin_storage') . ' (`type`, `subtype`, `aux`,`data`) VALUES ("favoritesHandler",' . $subtype . ',' . $db_instance . ',' . $db_data . ')';
		query($sql);
		npgFilters::apply('favoritesHandler_action', 'add', $img, $this->name);
	}

	function removeImage($img) {
		$folder = $img->imagefolder;
		$filename = $img->filename;
		$sql = 'DELETE FROM ' . prefix('plugin_storage') . ' WHERE `type`="favoritesHandler" AND `aux`=' . db_quote($this->getInstance()) . ' AND `data`=' . db_quote(serialize(array('type' => 'images', 'id' => $folder . '/' . $filename)));
		query($sql);
		npgFilters::apply('favoritesHandler_action', 'remove', $img, $this->name);
	}

	function addAlbum($alb) {
		$folder = $alb->name;
		if ($this->instance) {
			$subtype = '"named"';
		} else {
			$subtype = 'NULL';
		}
		$db_instance = db_quote($this->getInstance());
		$db_data = db_quote(serialize(array('type' => 'albums', 'id' => $folder)));
		if (query_single_row('SELECT * FROM ' . prefix('plugin_storage') .
										' WHERE `type`="favoritesHandler"' .
										' AND `subtype`=' . $subtype .
										' AND `aux`=' . $db_instance .
										' AND `data`=' . $db_data)) {
			return; //	already a favorite
		}
		$sql = 'INSERT INTO ' . prefix('plugin_storage') . ' (`type`, `subtype`, `aux`,`data`) VALUES ("favoritesHandler",' . $subtype . ',' . $db_instance . ',' . $db_data . ')';
		query($sql);
		npgFilters::apply('favoritesHandler_action', 'add', $alb, $this->name);
	}

	function removeAlbum($alb) {
		$folder = $alb->name;
		$sql = 'DELETE FROM ' . prefix('plugin_storage') . ' WHERE `type`="favoritesHandler" AND `aux`=' . db_quote($this->getInstance()) . ' AND `data`=' . db_quote(serialize(array('type' => 'albums', 'id' => $folder)));
		query($sql);
		$this->_removeCache(internalToFilesystem($folder));
		npgFilters::apply('favoritesHandler_action', 'remove', $alb, $this->name);
	}

	/**
	 * returns an array of users watching the object
	 * @param object $obj
	 * @return array
	 */
	static function getWatchers($obj) {
		switch ($obj->table) {
			case 'images':
				$folder = $obj->imagefolder;
				$filename = $obj->filename;
				$sql = 'SELECT DISTINCT `aux` FROM ' . prefix('plugin_storage') . '  WHERE `type`="favoritesHandler" AND `data`=' . db_quote(serialize(array('type' => 'images', 'id' => $folder . '/' . $filename)));
				break;
			case 'albums':
				$folder = $obj->name;
				$sql = 'SELECT DISTINCT `aux` FROM ' . prefix('plugin_storage') . '  WHERE `type`="favoritesHandler" AND `data`=' . db_quote(serialize(array('type' => 'albums', 'id' => $folder)));
				break;
		}
		$watchers = array();
		$result = query_full_array($sql);
		if ($result) {
			foreach ($result as $watch) {
				$watchers[] = $watch['aux'];
			}
		}
		return $watchers;
	}

	/**
	 * Prints a list of users/instances watching the object
	 * NOTE: the caller must enclose the list with the appropriate UL, OL, or DL html
	 * @param type $obj
	 * @param type $list_type if NULL, use li tags. otherwise the array will have the start, middle, and end tags.
	 */
	static function listWatchers($obj, $list_type = NULL) {
		$watchers = self::getWatchers($obj);

		if (!empty($watchers)) {
			if (is_null($list_type)) {
				$start = '<li>';
				$separate = '';
				$end = '</li>';
			} else {
				list($start, $separate, $end) = $list_type;
			}

			foreach ($watchers as $key => $aux) {
				$watchers[$key] = getSerializedArray($aux);
			}
			$watchers = sortMultiArray($watchers, array(1 => false, 2 => false));

			foreach ($watchers as $aux) {
				$watchee = $aux[0];
				if (isset($aux[1])) {
					$instance = $aux[1];
					if (is_null($list_type)) {
						$instance = ' [' . $instance . ']';
					}
				} else {
					$instance = '';
				}
				echo $start . html_encode($watchee) . $separate . html_encode($instance) . $end;
			}
		}
	}

	/**
	 * Returns either the subalbum sort direction or the image sort direction of the album
	 *
	 * @param string $what 'image_sortdirection' if you want the image direction,
	 *        'album_sortdirection' if you want it for the album
	 *
	 * @return string
	 */
	function getSortDirection($what = 'image') {
		global $_gallery;
		if ($what == 'image') {
			$direction = $this->image_sort_direction;
			$type = $this->image_sort_type;
		} else {
			$direction = $this->album_sort_direction;
			$type = $this->album_sort_type;
		}
		if (empty($type)) {
			// using inherited type, so use inherited direction
			if ($what == 'image') {
				$direction = IMAGE_SORT_DIRECTION;
			} else {
				$direction = $_gallery->getSortDirection();
			}
		}
		return $direction;
	}

	/**
	 * sets sort directions
	 *
	 * @param bool $val the direction
	 * @param string $what 'images' if you want the image direction,
	 *        'albums' if you want it for the album
	 */
	function setSortDirection($val, $what = 'image') {
		if ($what == 'image') {
			$this->image_sort_direction = (int) ($val && true);
		} else {
			$this->album_sort_direction = (int) ($val && true);
		}
	}

	/**
	 * Returns the sort type of the album images
	 * Will return a parent sort type if the sort type for this album is empty
	 *
	 * @return string
	 */
	function getSortType($what = 'image') {
		global $_gallery;
		if ($what == 'image') {
			$type = $this->image_sort_type;
		} else {
			$type = $this->album_sort_type;
		}
		if (empty($type)) {
			if ($what == 'image') {
				$type = IMAGE_SORT_TYPE;
			} else {
				$type = $_gallery->getSortType();
			}
		}
		return $type;
	}

	function getImageSortKey($sorttype = null) {
		if (is_null($sorttype)) {
			$sorttype = $this->getSortType();
		}
		if ($sorttype == 'favoritesorder') {
			return 'favoritesorder';
		}
		return lookupSortKey($sorttype, 'filename', 'images');
	}

	function getAlbumSortKey($sorttype = null) {
		if (empty($sorttype)) {
			$sorttype = $this->getSortType('album');
		}
		if ($sorttype == 'favoritesorder') {
			return 'favoritesorder';
		}
		return lookupSortKey($sorttype, 'sort_order', 'albums');
	}

	function setSortType($sorttype, $what = 'image') {
		if ($what == 'image') {
			$this->image_sort_type = $sorttype;
		} else {
			$this->album_sort_type = $sorttype;
		}
	}

	/**
	 * Returns all folder names for all the subdirectories.
	 *
	 * @param string $page  Which page of subalbums to display.
	 * @param string $sorttype The sort strategy
	 * @param string $sortdirection The direction of the sort
	 * @param bool $care set to false if the order does not matter
	 * @param bool $mine set true/false to override ownership
	 * @return array
	 */
	function getAlbums($page = 0, $sorttype = null, $sortdirection = null, $care = true, $mine = NULL) {
		global $_gallery;
		if ($mine || is_null($this->subalbums) || $care && $sorttype . $sortdirection !== $this->lastsubalbumsort) {
			$results = array();
			$result = query($sql = 'SELECT `id`, `data` FROM ' . prefix('plugin_storage') . ' WHERE `type`="favoritesHandler" AND `aux`=' . db_quote($this->getInstance()) . ' AND `data` LIKE "%s:4:\"type\";s:6:\"albums\";%"');
			if ($result) {
				while ($row = db_fetch_assoc($result)) {
					$data = getSerializedArray($row['data']);
					$albumobj = newAlbum($data['id'], true, true);
					if ($albumobj->exists) { // fail to instantiate?
						$results[$data['id']] = $albumobj->getData();
						$results[$data['id']]['favoritesorder'] = $data['id'];
					} else {
						query("DELETE FROM " . prefix('plugin_storage') . ' WHERE `id`=' . $row['id']);
					}
				}
				db_free_result($result);
				if (is_null($sorttype)) {
					$sorttype = $this->getSortType('album');
				}
				if (is_null($sortdirection)) {
					if ($this->getSortDirection('album')) {
						$sortdirection = 'DESC';
					} else {
						$sortdirection = '';
					}
				}
				$sortkey = $this->getAlbumSortKey($sorttype);
				if ((trim($sortkey . '`') == 'sort_order') || ($sortkey == 'RAND()')) { // manual sort is always ascending
					$order = false;
				} else {
					if (!is_null($sortdirection)) {
						$order = $sortdirection && strtolower($sortdirection) == 'desc';
					} else {
						$order = $this->getSortDirection('album');
					}
				}
				$results = sortByKey($results, $sortkey, $order);
				$this->subalbums = array_keys($results);
				$this->lastsubalbumsort = $sorttype . $sortdirection;
			}
		}
		return parent::getAlbums($page);
	}

	/**
	 * Returns a of a slice of the images for this album. They will
	 * also be sorted according to the sort type of this album, or by filename if none
	 * has been set.
	 *
	 * @param string $page  Which page of images should be returned. If zero, all images are returned.
	 * @param int $firstPageCount count of images that go on the album/image transition page
	 * @param string $sorttype optional sort type
	 * @param string $sortdirection optional sort direction
	 * @param bool $care set to false if the order of the images does not matter
	 * @param bool $mine set true/false to override ownership
	 *
	 * @return array
	 */
	function getImages($page = 0, $firstPageCount = 0, $sorttype = null, $sortdirection = null, $care = true, $mine = NULL) {
		if ($mine || is_null($this->images) || $care && $sorttype . $sortdirection !== $this->lastimagesort) {
			$this->images = NULL;
			$images = array();
			$result = query($sql = 'SELECT `id`, `data` FROM ' . prefix('plugin_storage') . ' WHERE `type`="favoritesHandler" AND `aux`=' . db_quote($this->getInstance()) . ' AND `data` LIKE "%s:4:\"type\";s:6:\"images\";%"');
			if ($result) {
				while ($row = db_fetch_assoc($result)) {
					$id = $row['id'];
					$data = getSerializedArray($row['data']);
					$imageObj = newImage(array('folder' => dirname($data['id']), 'filename' => basename($data['id'])), true, true);
					if ($imageObj->exists) {
						$images[] = array_merge(array('folder' => dirname($data['id']), 'filename' => basename($data['id']), 'favoritesorder' => $id), $imageObj->getData());
					} else {
						query("DELETE FROM " . prefix('plugin_storage') . ' WHERE `id`=' . $row['id']);
					}
				}

				db_free_result($result);
				if (is_null($sorttype)) {
					$sorttype = $this->getSortType();
				}
				$sortkey = str_replace('` ', ' ', $this->getImageSortKey($sorttype));
				if ((trim($sortkey . '`') == 'sort_order') || ($sortkey == 'RAND()')) {
					// manual sort is always ascending
					$order = false;
				} else {
					if (!is_null($sortdirection)) {
						$order = $sortdirection && strtolower($sortdirection) == 'desc';
					} else {
						$order = $this->getSortDirection('image');
					}
				}
				$images = sortByKey($images, $sortkey, $order);
				$this->imageNames = $this->images = array();
				foreach ($images as $data) {
					$this->images[] = array('folder' => $data['folder'], 'filename' => $data['filename']);
					$this->imageNames[$data['folder'] . '/' . $data['filename']] = $data['filename'];
				}
				ksort($this->imageNames, SORT_LOCALE_STRING);
				$this->lastimagesort = $sorttype . $sortdirection;
			}
		}
		return parent::getImages($page, $firstPageCount);
	}

	static function loadScript($script, $request) {
		global $_current_admin_obj, $_gallery_page, $_current_album, $_conf_vars, $_myFavorites;
		if ($_myFavorites && isset($_REQUEST['instance'])) {
			$_myFavorites->instance = sanitize(rtrim($_REQUEST['instance'], '/'));
			if ($_myFavorites->instance)
				$_myFavorites->setTitle($_myFavorites->getTitle() . '[' . $_myFavorites->instance . ']');
		}
		if ($_gallery_page == "favorites.php") {
			if (npg_loggedin()) {
				$_current_album = $_myFavorites;
				add_context(NPG_ALBUM);
				Controller::prepareAlbumPage();
				$_gallery_page = 'favorites.php';
			} else {
				$script = false;
			}
		}
		return $script;
	}

	static function pageCount($count, $gallery_page, $page) {
		global $_transitionImageCount;
		if (stripSuffix($gallery_page) == 'favorites') {
			$albums_per_page = galleryAlbumsPerPage();
			$pageCount = (int) ceil(getNumAlbums() / $albums_per_page);
			$imageCount = getNumImages();
			if (!galleryImagesPerPage()) {
				$imageCount = min(1, $imageCount);
			}

			$count = ($pageCount + (int) ceil(($imageCount - $_transitionImageCount) / $images_per_page));
			if ($count < $page && isset($_POST['addToFavorites']) && !$_POST['addToFavorites']) {
				//We've deleted last item on page, need a place to land when we return
				global $_current_page;
				header('location: ' . FULLWEBPATH . '/' . $this->getLink($_current_page - 1));
				exit();
			}
		}
		return $count;
	}

	function getLink($page = NULL, $instance = NULL) {
		$link = _FAVORITES_ . '/';
		$link_no = 'index.php?p=favorites';
		if (is_null($instance))
			$instance = $this->instance;
		if ($instance) {
			$instance = rtrim($instance, '/');
			$link .= $instance . '/';
			$link_no .= '&instance=' . $instance;
		}
		if ($page > 1) {
			$link .= $page;
			$link_no .= '&page=' . $page;
		}
		return npgFilters::apply('getLink', rewrite_path($link, $link_no), 'favorites.php', $page);
	}

	static function ad_removeButton($obj, $id, $v, $add, $instance, $multi, $required) {
		global $_myFavorites;
		$table = $obj->table;
		if ($v) {
			$tag = '_add';
		} else {
			$tag = '_remove';
		}
		if ($instance && $multi) {
			$add .= ' [' . $instance . ']';
		}
		?>
		<form name="<?php echo $table . $obj->getID(); ?>Favorites_<?php echo $instance . $tag; ?>" class = "<?php echo $table; ?>Favorites<?php echo $tag; ?>"  action = "<?php echo html_encode(getRequestURI()); ?>" method = "post" accept-charset = "UTF-8">
			<input type = "hidden" name = "addToFavorites" value = "<?php echo $v; ?>" />
			<input type = "hidden" name = "type" value = "<?php echo html_encode($table); ?>" />
			<input type = "hidden" name = "id" value = "<?php echo html_encode($id); ?>" />
			<button type = "submit" class = "button buttons" title = "<?php echo $add; ?>"/><?php echo $add; ?></button>
		<?php
		if ($v) {
			if ($multi) {
				?>
				<span class="tagSuggestContainer">
					<input type="text" name="instance" id="favorite_instance_<?php echo $v; ?>" class="favorite_instance" value=""<?php if ($required) echo ' required'; ?> />
				</span>
				<?php
			}
		} else {
			?>
			<input type="hidden" name="instance" value="<?php echo $_myFavorites->instance; ?>" />
			<?php
		}
		?>
		</form>
		<?php
	}

}
