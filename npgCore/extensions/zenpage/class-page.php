<?php

/**
 * zenpage page class
 *
 * @author Malte Müller (acrylian)
 * @package plugins/zenpage
 */
// force UTF-8 Ø

function newPage($titlelink, $allowCreate = NULL) {
	return new Page($titlelink, $allowCreate);
}

/**
 * Returns true if the object is a 'page' object
 *
 * @param object $article
 * @return bool
 */
function isPageClass($page) {
	return is_object($page) && get_class($page) == 'Page';
}

class Page extends CMSItems {

	public $manage_rights = MANAGE_ALL_PAGES_RIGHTS;
	public $manage_some_rights = ZENPAGE_PAGES_RIGHTS;
	public $access_rights = ALL_PAGES_RIGHTS;

	function __construct($titlelink, $allowCreate = NULL) {
		if (is_array($titlelink)) {
			$titlelink = $titlelink['titlelink'];
		}
		$new = $this->instantiate('pages', array('titlelink' => $titlelink), 'titlelink', true, empty($titlelink), $allowCreate);
		$this->checkForPublish();
		if ($new || empty($titlelink)) {
			$this->setPermalink(1);
			$this->setDateTime(date('Y-m-d H:i:s'));
			$this->setDefaultSortOrder();
		}
		$this->exists = $this->loaded;
	}

	/**
	 * Returns the sort order
	 *
	 * @return string
	 */
	function getSortOrder() {
		return $this->get('sort_order');
	}

	/**
	 * Stores the sort order
	 *
	 * @param string $sortorder image sort order
	 */
	function setSortOrder($sortorder) {
		$this->set('sort_order', $sortorder);
	}

	/**
	 * Returns the guest user
	 *
	 * @return string
	 */
	function getUser() {
		return $this->get('user');
	}

	/**
	 * Sets the guest user
	 *
	 * @param string $user
	 */
	function setUser($user) {
		$this->set('user', $user);
	}

	/**
	 * Returns the password
	 *
	 * @return string
	 */
	function getPassword() {
		if (GALLERY_SECURITY != 'public') {
			return NULL;
		} else {
			return $this->get('password');
		}
	}

	/**
	 * Sets the encrypted password
	 *
	 * @param string $pwd the cleartext password
	 */
	function setPassword($pwd) {
		$this->set('password', $pwd);
	}

	/**
	 * Returns the password hint
	 *
	 * @return string
	 */
	function getPasswordHint($locale = NULL) {
		$text = ($this->get('password_hint'));
		if ($locale !== 'all') {
			$text = get_language_string($text, $locale);
		}
		$text = npgFunctions::unTagURLs($text);
		return $text;
	}

	/**
	 * Sets the password hint
	 *
	 * @param string $hint the hint text
	 */
	function setPasswordHint($hint) {
		$this->set('password_hint', npgFunctions::tagURLs($hint));
	}

	/**
	 * duplicates an article
	 * @param string $newtitle the title for the new article
	 */
	function copy($newtitle) {
		$newID = $newtitle;
		$id = parent::copy(array('titlelink' => $newID));
		if (!$id) {
			$newID = $newtitle . ':' . seoFriendly(date('Y-m-d_H-i-s'));
			$id = parent::copy(array('titlelink' => $newID));
		}
		if ($id) {
			$newobj = newPage($newID);
			$newobj->setTitle($newtitle);
			$newobj->setSortOrder(NULL);
			$newobj->setTags($this->getTags(false));
			$newobj->setDateTime('');
			$newobj->setShow(0);
			$newobj->save();
			return $newobj;
		}
		return false;
	}

	/**
	 * Deletes a page (and also if existing its subpages) from the database
	 *
	 */
	function remove() {
		if ($success = parent::remove()) {
			$sortorder = $this->getSortOrder();
			if ($this->id) {
				$success = $success && query("DELETE FROM " . prefix('obj_to_tag') . "WHERE `type`='pages' AND `objectid`=" . $this->id);
				$success = $success && query("DELETE FROM " . prefix('comments') . " WHERE ownerid = " . $this->getID() . ' AND type="pages"'); // delete any comments
				//	remove subpages
				$mychild = strlen($sortorder) + 4;
				$result = query_full_array('SELECT `titlelink`, `sort_order` FROM ' . prefix('pages') . " WHERE `sort_order` like '" . $sortorder . "-%'");
				if (is_array($result)) {
					foreach ($result as $row) {
						if (strlen($row['sort_order']) == $mychild) {
							$subpage = newPage($row['titlelink']);
							$success = $success && $subpage->remove();
						}
					}
				}
			}
		}
		return $success;
	}

	/**
	 * Gets the parent pages recursivly to the page whose parentid is passed or the current object
	 *
	 * @param int $parentid The parentid of the page to get the parents of
	 * @param bool $initparents
	 * @return array
	 */
	function getParents(&$parentid = '', $initparents = true) {
		global $parentpages, $_CMS;
		$allitems = $_CMS->getPages();
		if ($initparents) {
			$parentpages = array();
		}
		if (empty($parentid)) {
			$currentparentid = $this->getParentID();
		} else {
			$currentparentid = $parentid;
		}
		foreach ($allitems as $item) {
			$obj = newPage($item['titlelink']);
			$itemtitlelink = $obj->getTitlelink();
			$itemid = $obj->getID();
			$itemparentid = $obj->getParentID();
			if ($itemid == $currentparentid) {
				array_unshift($parentpages, $itemtitlelink);
				$obj->getParents($itemparentid, false);
			}
		}
		return $parentpages;
	}

	/**
	 * Gets the sub pages of a page
	 * @param bool $published TRUE for published or FALSE for all pages including un-published
	 * @param bool $toplevel ignored, left for parameter compatibility
	 * @param int $number number of pages to get (NULL by default for all)
	 * @param string $sorttype NULL for the standard order as sorted on the backend, "title", "date", "popular", "mostrated", "toprated", "random"
	 * @param string $sortdirection false for ascending, true for descending
	 * @return array
	 */
	function getPages($published = NULL, $toplevel = false, $number = NULL, $sorttype = NULL, $sortdirection = NULL) {
		global $_CMS;
		return $_CMS->getPages($published, $toplevel, $number, $sorttype, $sortdirection, $this);
	}

	/**
	 * Checks if user is allowed to access the page
	 * @param $hint
	 * @param $show
	 */
	function checkforGuest(&$hint = NULL, &$show = NULL) {
		if (!parent::checkForGuest($hint, $show)) {
			return false;
		}
		$pageobj = $this;
		$hash = $this->getPassword();
		while (empty($hash) && !is_null($pageobj)) {
			$parentID = $pageobj->getParentID();
			if (empty($parentID)) {
				$pageobj = NULL;
			} else {
				$sql = 'SELECT `titlelink` FROM ' . prefix('pages') . ' WHERE `id`=' . $parentID;
				$result = query_single_row($sql);
				$pageobj = newPage($result['titlelink']);
				$hash = $pageobj->getPassword();
			}
		}
		if (empty($hash)) { // no password required
			return 'public_access';
		} else {
			$authType = "zenpage_page_auth_" . $pageobj->getID();
			$saved_auth = getNPGCookie($authType);
			if ($saved_auth == $hash) {
				return $authType;
			} else {
				$user = $pageobj->getUser();
				$show = (!empty($user));
				$hint = $pageobj->getPasswordHint();
				return false;
			}
		}
	}

	/**
	 * Checks if a page is protected and returns TRUE or FALSE
	 * NOTE: This function does only check if a password is set not if it has been entered! Use $this->checkforGuest() for that.
	 *
	 * @return bool
	 */
	function isProtected() {
		return $this->checkforGuest() != 'public_access';
	}

	/**
	 * checks if the page and its parents are published
	 * @return boolean
	 */
	function isPublished() {
		if ($this->getShow()) {
			$parentID = $this->getParentID();
			if (empty($parentID)) {
				return TRUE;
			} else {
				$sql = 'SELECT `titlelink` FROM ' . prefix('pages') . ' WHERE `id`=' . $parentID;
				$result = query_single_row($sql);
				$parent = newPage($result['titlelink']);
				return $parent->isPublished();
			}
		}
		return FALSE;
	}

	function subRights() {
		global $_current_admin_obj;
		if (!is_null($this->subrights)) {
			return $this->subrights;
		}
		$this->subrights = 0;
		if (npg_loggedin()) {
			if (npg_loggedin($this->manage_rights)) {
				$this->subrights = MANAGED_OBJECT_RIGHTS_EDIT | MANAGED_OBJECT_RIGHTS_VIEW;
				return $this->subrights;
			}
			$objects = $_current_admin_obj->getObjects();
			$me = $this->getTitlelink();
			foreach ($objects as $object) {
				if ($object['type'] == $this->table) {
					if ($object['data'] == $me) {
						$this->subrights = $object['edit'] | MANAGED_OBJECT_MEMBER;
						break;
					}
				}
			}
		}
		return $this->subrights;
	}

	/**
	 * Checks if user is author of page
	 * @param bit $action what the caller wants to do
	 *
	 * returns true of access is allowed
	 */
	function isMyItem($action) {
		global $_current_admin_obj;
		if (parent::isMyItem($action)) {
			return true;
		}
		if ($_current_admin_obj && $_current_admin_obj->getUser() == $this->getOwner()) {
			return true;
		}

		if (npg_loggedin($action)) {
			if ($action == LIST_RIGHTS && $this->isPublished()) {
				return LIST_RIGHTS;
			}
			$subRights = $this->subRights();
			if ($subRights) {
				$rights = LIST_RIGHTS;
				if ($subRights & (MANAGED_OBJECT_RIGHTS_EDIT)) {
					$rights = $rights | ZENPAGE_PAGES_RIGHTS;
				}
				if ($action & $rights) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Returns full path to a specific page
	 *
	 * @return string
	 */
	function getLink() {
		return npgFilters::apply('getLink', rewrite_path(_PAGES_ . '/' . $this->getTitlelink(), '/index.php?p=pages&title=' . $this->getTitlelink()), $this, NULL);
	}

}

?>