<?php

/**
 * root object class
 * @package classes
 */
// force UTF-8 Ø
// classes.php

/* * *****************************************************************************
 * ******************************************************************************
 * Persistent Object Class *****************************************************
 *
 * Parent ABSTRACT class of all persistent objects. This class should not be
 * instantiated, only used for subclasses. This cannot be enforced, but please
 * follow it!
 *
 * A child class should run the following in its constructor:
 *
 * $new = $this->instantiate('tablename',
 *   array('uniquestring'=>$value, 'uniqueid'=>$uniqueid));
 *
 * where 'tablename' is the name of the database table to use for this object
 * type, and array('uniquestring'=>$value, ...) defines a unique set of columns
 * (keys) and their current values which uniquely identifies a single record in
 * that database table for this object.
 *
 * Note: This is a persistable model that does not save automatically. You MUST
 * call $this->save(); explicitly to persist the data in child classes.
 *
 * ******************************************************************************
 * **************************************************************************** */

// The query cache
$_object_cache = array();
define('OBJECT_CACHE_DEPTH', 150); //	how many objects to hold for each object class
// ABSTRACT

class PersistentObject {

	public $loaded = false;
	public $exists = false;
	public $table;
	public $transient;
	protected $id = 0;
	private $unique_set = array();
	private $cache_by;
	private $use_cache = false;
	private $tempdata = array();
	private $data = array();
	private $updates = array();

	/**
	  }
	 *
	 * Prime instantiator for objects
	 * @param $tablename	The name of the database table
	 * @param $unique_set	An array of unique fields
	 * @param $cache_by
	 * @param $use_cache
	 * @param $is_transient	Set true to prevent database insertion
	 * @param $allowCreate Set true to allow a new object to be made.
	 * @return bool will be true if the unique_set does not already exist
	 */
	function instantiate($tablename, $unique_set, $cache_by = NULL, $use_cache = true, $is_transient = false, $allowCreate = true) {
		global $_object_cache;
		//	insure a cache entry
		$classname = get_class($this);
		if (!isset($_object_cache[$classname])) {
			$_object_cache[$classname] = array();
		}
		// Initialize the variables.
		// Load the data into the data array using $this->load()
		$this->data = $this->tempdata = $this->updates = array();
		$this->loaded = false;
		$this->table = $tablename;
		$this->unique_set = array_change_key_case($unique_set, CASE_LOWER);
		if (is_null($cache_by)) {
			$this->cache_by = serialize($unique_set);
		} else {
			$this->cache_by = $this->unique_set[$cache_by];
		}
		$this->use_cache = $use_cache;
		$this->transient = $is_transient;
		$obj = $this->load($allowCreate);
		return $obj;
	}

	/**
	 *
	 * check the cache for presence of the entry and return it if found
	 * @param $entry
	 */
	private function getFromCache() {
		global $_object_cache;
		if (isset($_object_cache[$c = get_class($this)]) && isset($_object_cache[$c][$this->cache_by])) {
			return $_object_cache[$c][$this->cache_by];
		}
		return NULL;
	}

	/**
	 *
	 * add the entry to the cache
	 * @param $entry
	 */
	private function addToCache($entry) {
		global $_object_cache;
		if ($entry) {
			if (count($_object_cache[$classname = get_class($this)]) >= OBJECT_CACHE_DEPTH) {
				array_shift($_object_cache[$classname]); //	discard the oldest
			}
			$_object_cache[$classname][$this->cache_by] = $entry;
		}
	}

	/**
	 * Set a variable in this object. Does not persist to the database until
	 * save() is called. So, IMPORTANT: Call save() after set() to persist.
	 * If the requested variable is not in the database, sets it in temp storage,
	 * which won't be persisted to the database.
	 */
	function set($var, $value) {
		if (empty($var))
			return false;
		$var = strtolower($var);
		if ($this->loaded && !array_key_exists($var, $this->data)) {
			$this->tempdata[$var] = $value;
		} else {
			$this->updates[$var] = $value;
		}
		return true;
	}

	/**
	 * Sets default values for new objects using the set() method.
	 * Should do nothing in the base class; subclasses should override.
	 */
	protected function setDefaults() {

	}

	/**
	 * Change one or more values of the unique set assigned to this record.
	 * Checks if the record already exists first, if so returns false.
	 * If successful returns true and changes $this->unique_set
	 * A call to move is instant, it does not require a save() following it.
	 */
	function move($new_unique_set) {
		// Check if we have a row
		$new_unique_set = array_change_key_case($new_unique_set, CASE_LOWER);
		$result = query_single_row('SELECT `id` FROM ' . prefix($this->table) . getWhereClause($new_unique_set));
		if (!$result || $result['id'] == $this->id) { //	we should not find an entry for the new unique set!
			if (!npgFilters::apply('move_object', true, $this, $new_unique_set)) {
				return false;
			}
			$sql = 'UPDATE ' . prefix($this->table) . getSetClause($new_unique_set) . ' ' . getWhereClause($this->unique_set);
			$result = query($sql);
			if ($result && db_affected_rows() == 1) { //	and the update should have effected just one record
				$this->unique_set = $new_unique_set;
				return true;
			}
		}
		return false;
	}

	/**
	 * Copy this record to another unique set. Checks if the record exists there
	 * first, if so returns false. If successful returns true. No changes are made
	 * to this object and no other objects are created, just the database entry.
	 * A call to copy is instant, it does not require a save() following it.
	 */
	function copy($new_unique_set) {
		// Check if we have a row
		$new_unique_set = array_change_key_case($new_unique_set, CASE_LOWER);
		$result = query('SELECT `id` FROM ' . prefix($this->table) . getWhereClause($new_unique_set) . ' LIMIT 1;');

		if ($result && db_num_rows($result) == 0) {
			if (!npgFilters::apply('copy_object', true, $this, $new_unique_set)) {
				return false;
			}
			// Note: It's important for $new_unique_set to come last, as its values should override.
			$insert_data = array_merge($this->data, $this->updates, $new_unique_set);
			unset($insert_data['id']);
			unset($insert_data['hitcounter']); //	start fresh on new copy
			if (empty($insert_data)) {
				return true;
			}
			$sql = 'INSERT INTO ' . prefix($this->table) . ' (';
			$i = 0;
			foreach (array_keys($insert_data) as $col) {
				if ($i > 0)
					$sql .= ", ";
				$sql .= "`$col`";
				$i++;
			}
			$sql .= ') VALUES (';
			$i = 0;
			foreach (array_values($insert_data) as $value) {
				if ($i > 0)
					$sql .= ', ';
				if (is_null($value)) {
					$sql .= 'NULL';
				} else {
					$sql .= db_quote($value);
				}
				$i++;
			}
			$sql .= ');';
			$success = query($sql);
			if ($success && db_affected_rows() == 1) {
				return npgFilters::apply('copy_object', db_insert_id(), $this);
			}
		}
		return false;
	}

	/**
	 * Deletes object from the database
	 *
	 * @return bool
	 */
	function remove() {
		if (!npgFilters::apply('remove_object', true, $this)) {
			return false;
		}
		$id = $this->id;
		if (empty($id)) {
			$id = ' is NULL'; //	allow delete of bad item!
		} else {
			$id = '=' . $id;
		}
		$sql = 'DELETE FROM ' . prefix($this->table) . ' WHERE `id`' . $id;
		$this->loaded = false;
		$this->transient = true;
		return query($sql);
	}

	/**
	 * Returns the id
	 *
	 * @return string
	 */
	function getID() {
		return $this->id;
	}

	/**
	 * returns the database record of the object
	 *
	 * @return array
	 */
	function getData() {
		foreach ($this->updates as $key => $value) {
			$this->data[$key] = $value;
		}
		return $this->data;
	}

	/**
	 * Get the value of a variable. If $current is false, return the value
	 * as of the last save of this object.
	 */
	function get($var, $current = true) {
		$var = strtolower($var);
		if ($current && array_key_exists($var, $this->updates)) {
			return $this->updates[$var];
		} else if (array_key_exists($var, $this->data)) {
			return $this->data[$var];
		} else if (array_key_exists($var, $this->tempdata)) {
			return $this->tempdata[$var];
		} else {
			return NULL;
		}
	}

	/**
	 * Load the data array from the database, using the unique id set to get the unique record.
	 *
	 * @param bool $allowCreate set to true to enable new object creation.
	 * @return false if the record already exists, true if a new record was created.
	 */
	private function load($allowCreate) {
		$new = $entry = null;
		// First, try the cache.
		if ($this->use_cache) {
			$entry = $this->getFromCache();
		}
		// Check the database if: 1) not using cache, or 2) didn't get a hit.
		if (empty($entry) && !$this->transient) {
			$sql = 'SELECT * FROM ' . prefix($this->table) . getWhereClause($this->unique_set) . ';';
			$entry = query_single_row($sql, false);
			// Save this entry into the cache so we get a hit next time.
			if ($entry) {
				$entry = array_change_key_case($entry, CASE_LOWER);
				$this->addToCache($entry);
			}
		}

		// If we don't have an entry yet, this is a new record. Create it.
		if (empty($entry)) {
			//	populate $this->data so that the set method will work correctly
			$result = db_list_fields($this->table);
			if ($result) {
				foreach ($result as $row) {
					$this->data[strtolower($row['Field'])] = NULL;
				}
			}
			$this->data = array_merge($this->data, $this->unique_set);

			if ($this->transient || !$allowCreate) { // don't save it in the DB!
				if ($allowCreate) {
					$entry = $this->data;
					$entry['id'] = 0;
					$this->addToCache($entry);
				} else {
					return NULL; // does not exist and we are not allowed to create it
				}
			} else {
				$new = true;
				$this->save();
				$entry = query_single_row($sql);
				// If we still don't have an entry, something went wrong...
				if (!$entry) {
					return NULL;
				}
				// Save this new entry into the cache so we get a hit next time.
				$entry = array_change_key_case($entry, CASE_LOWER);
				$this->addToCache($entry);
			}
		}
		$this->data = $entry;
		$this->id = (int) $entry['id'];
		$this->loaded = TRUE;
		return $new;
	}

	/**
	 * Save the updates made to this object since the last update. Returns
	 * true if successful, false if not.
	 */
	function save() {
		global $_current_admin_obj, $_authority;
		if ($_current_admin_obj) {
			$updateUser = $_current_admin_obj;
		} else {
			$updateUser = $_authority->getMasterUser();
		}

		if ($this->transient) {
			return 0; // If this object isn't supposed to be persisted, don't save it.
		}
		if (!$this->unique_set) { // If we don't have a unique set, then this is incorrect. Don't attempt to save.
			trigger_error('empty $this->unique set is empty', E_USER_ERROR);
			return 0;
		}
		if (!npgFilters::apply('save_object', TRUE, $this, $this->updates)) {
			// filter aborted the save
			return 0;
		}
		if ($this->id <= 0) {
			//	prevent recursive save form default processing
			$this->transient = TRUE;
			$this->setDefaults();
			$this->transient = FALSE;
			$insert_data = array_merge($this->unique_set, $this->updates);
			if (empty($insert_data)) {
				return 2;
			}
			if (array_key_exists('lastchange', $this->data)) { //	if the object has these keys, provide the data
				$insert_data = array_merge($insert_data, array('lastchange' => date('Y-m-d H:i:s'), 'lastchangeuser' => $updateUser->getUser()));
			}

			$cols = $vals = '';
			foreach ($insert_data as $col => $value) {
				if (!empty($cols)) {
					$cols .= ", ";
					$vals .= ", ";
				}
				$cols .= "`$col`";
				if (is_null($value)) {
					$vals .= "NULL";
				} else {
					$vals .= db_quote($value);
				}
			}
			$sql = 'INSERT INTO ' . prefix($this->table) . ' (' . $cols . ') VALUES (' . $vals . ')';
			$success = query($sql);
			if (!$success || db_affected_rows() != 1) {
				return 0;
			}
			foreach ($insert_data as $key => $value) { // copy over any changes
				$this->data[$key] = $value;
			}
			$this->updates = array();

			$this->data['id'] = $this->id = (int) db_insert_id(); // so 'get' will retrieve it!
			$this->loaded = TRUE;
		} else {
			// Save the existing object (updates only) based on the existing id.
			if (empty($this->updates)) {
				return 2;
			} else {
				$sql = '';
				foreach ($this->updates as $col => $value) {
					if ($this->data[$col] !== $value) {
						if ($sql) {
							$sql .= ",";
						}
						if (is_null($value)) {
							$sql .= " `$col` = NULL";
						} else {
							$sql .= " `$col` = " . db_quote($value);
						}
						$this->data[$col] = $value;
					}
				}
				if (empty($sql)) {
					$success = 2;
				} else {
					if (array_key_exists('lastchange', $this->data)) { //	if the object has these keys, provide the data
						$sql .= ',`lastchange`=' . db_quote(date('Y-m-d H:i:s')) . ',`lastchangeuser`=' . db_quote($updateUser->getUser());
					}
					$sql = 'UPDATE ' . prefix($this->table) . ' SET' . $sql . ' WHERE id=' . $this->id . ';';
					$success = query($sql);
					if (!$success || db_affected_rows() != 1) {
						return 0;
					} else {
						$success = 1;
					}
				}
				$this->updates = array();
			}
		}
		$this->addToCache($this->data);
		return $success;
	}

	/**
	 *
	 * "Magic" function to return a string identifying the object when it is treated as a string
	 * @return string
	 */
	public function __toString() {
		if ($this->table) {
			return $this->table . " (" . $this->id . ")";
		} else {
			return get_class($this) . ' ' . gettext('Object');
		}
	}

	/**
	 * Magic  function to implement get/set methods for custom defined fields
	 *
	 * @param type $method
	 * @param type $args
	 * @return type
	 * @throws Exception
	 */
	public function __call($method, $args) {
		$how = strtolower(substr($method, 0, 3));
		$what = strtolower(substr($method, 3));
		$arg = reset($args);
		switch ($how) {
			case 'get':
				if (array_key_exists($what, $this->updates)) {
					return $this->updates[$what];
				} else if (array_key_exists($what, $this->data)) {
					return $this->data[$what];
				} else if (array_key_exists($what, $this->tempdata)) {
					return $this->tempdata[$what];
				}
				break;
			case 'set':
				return $this->set($what, $arg);
		}

		$caller = debug_backtrace();
		$call = reset($caller);
		if (isset($call['class']) && $call['class'] == 'PersistentObject') {
			$call = next($caller);
		}
		$msg = sprintf(gettext('Call to undefined method %1$s() in %2$s on line %3$s'), get_class($this) . '::' . $method, $call['file'], $call['line']);
		trigger_error($msg);
	}

	public static function __callStatic($name, $arguments) {
		//	zenphoto has moved some general functions to static class methods. There was really no need to do so
		//	but this will catch the calls and revert them to the originals.
		if (function_exists($name)) {
			return call_user_func_array($name, $arguments);
		}
		$caller = debug_backtrace();
		$call = reset($caller);
		if (isset($call['class']) && $call['class'] == 'PersistentObject') {
			$call = next($caller);
		}
		$msg = sprintf(gettext('Call to undefined method %1$s() in %2$s on line %3$s'), get_called_class() . '::' . $name, $call['file'], $call['line']);
		trigger_error($msg);
	}

}

//////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * The basic ThemeObject class. Extends PersistentObject, is extended by various Theme related objects.
 * Provides some basic methods that all use.
 */
class ThemeObject extends PersistentObject {

	private $commentcount; //Contains the number of comments
	public $comments = NULL; //Contains an array of the comments of the object
	public $manage_rights = ADMIN_RIGHTS;
	public $manage_some_rights = ADMIN_RIGHTS;
	public $access_rights = VIEW_ALL_RIGHTS;

	public function __call($method, $args) {
		return parent::__call($method, $args);
	}

	/**
	 * Class instantiator
	 */
	function __construct() {
		// no action required
	}

	/**
	 * returns the owner of the object
	 *
	 * @return string
	 */
	function getOwner() {
		return $this->get('owner');
	}

	/**
	 * sets the owner of an the object
	 *
	 * @param string $user
	 */
	function setOwner($user) {
		$this->set('owner', $user);
	}

	/**
	 * Returns the last change date
	 *
	 * @return string
	 */
	function getLastchange() {
		return $this->get("lastchange");
	}

	/**
	 * Returns the last change user
	 *
	 * @return string
	 */
	function getlastchangeuser() {
		return $this->get("lastchangeuser");
	}

	/**
	 * checks if the publish state should be altered due to
	 * the maturing of the publish date or passing the expire date
	 */
	protected function checkForPublish() {
		//update published state if needed
		$now = date('Y-m-d H:i:s');
		if ($this->getShow()) {
			$d = $this->getExpireDate();
			if ($d && $d < $now || $this->getPublishDate() > $now) {
				$this->setShow(0);
				$this->save();
			}
		} else {
			$d = $this->getPublishDate();
			if ($d && $d <= $now) {
				$this->setShow(1);
				$this->save();
			}
		}
	}

	/**
	 * Returns the title
	 *
	 * @return string
	 */
	function getTitle($locale = NULL) {
		$text = $this->get('title');
		if ($locale !== 'all') {
			$text = get_language_string($text, $locale);
		}
		$text = npgFunctions::unTagURLs($text);
		return $text;
	}

	/**
	 * Stores the title
	 *
	 * @param string $title the title
	 */
	function setTitle($title) {
		$this->set('title', npgFunctions::tagURLs($title));
	}

	/**
	 * Returns the partent id
	 *
	 * @return string
	 */
	function getParentID() {
		return $this->get('parentid');
	}

	/**
	 * Sets the ParentID field
	 * @param $v id of the parent
	 */
	function setParentID($v) {
		$this->set('parentid', $v);
	}

	/**
	 * Returns the hitcount
	 *
	 * @return int
	 */
	function getHitcounter() {
		return $this->get('hitcounter');
	}

	/**
	 * counts visits to the object
	 */
	function countHit() {
		$this->set('hitcounter', $this->get('hitcounter') + 1);
		$this->save();
	}

	/**
	 * Returns true published
	 *
	 * @return bool
	 */
	function getShow() {
		return $this->get('show');
	}

	/**
	 * Stores the published value
	 *
	 * @param bool $show True if the album is published
	 */
	function setShow($show) {
		$old_show = (int) ($this->get('show') && true);
		$new_show = (int) ($show && true);
		$this->set('show', $new_show);
		if ($old_show !== $new_show) {
			if ($this->get('id')) {
				npgFilters::apply('show_change', $this);
			}
			if ((int) ($this->get('show') && true) === $new_show) { //	filter did not reverse the change
				$p = $this->get("publishdate");
				$d = date('Y-m-d H:i:s');
				if ($new_show) { //	going from unpublished to published
					$this->setPublishDate($d); // published NOW
					$this->setExpireDate(NULL); // "kill" any scheduled expiry
				} else { //	going from published to unpulbished
					if ($p && $p <= $d) {
						$this->setPublishDate(NULL); // "kill" scheduled publish
					}
					if (($e = $this->get("expiredate")) && ($e >= $d)) {
						$this->setExpireDate(NULL); // "kill" scheduled expiry
					}
				}
			}
		}
	}

	/**
	 * Returns the tag data
	 *
	 * @param string $language
	 * @return string
	 */
	function getTags($language = NULL) {
		return readTags($this->getID(), $this->table, $language);
	}

	/**
	 * Stores tag information
	 *
	 * @param string $tags the tag list
	 */
	function setTags($tags) {
		if (!$this->getID()) { //	requires a valid id to link tags to the object
			$this->save();
		}
		$oldtags = $this->getTags();
		sort($oldtags);
		storeTags(array_unique($tags), $this->getID(), $this->table);
		$newtags = $this->getTags();
		sort($newtags);
		if ($oldtags != $newtags) {
			$this->set('lastchange', date('Y-m-d H:i:s'));
		}
	}

	/**
	 * Checks if an object has a tag assigned.
	 *
	 * @param string $checktag tag to check for
	 *
	 * @return bool
	 */
	function hasTag($checktag) {
		$tags = $this->getTags(false);
		return in_array($checktag, $tags);
	}

	/**
	 * Returns the unformatted date
	 *
	 * @return date
	 */
	function getDateTime() {
		$d = $this->get('date');
		if ($d && $d != '0000-00-00 00:00:00') {
			return $d;
		}
		return false;
	}

	/**
	 * Stores the date
	 *
	 * @param string $datetime formatted date
	 */
	function setDateTime($datetime) {
		if ($datetime) {
			$newtime = dateTimeConvert($datetime);
			if ($newtime !== false) {
				$this->set('date', $newtime);
			}
		} else {
			$this->set('date', NULL);
		}
	}

	/**
	 * Returns the codeblocks as an serialized array
	 *
	 * @return array
	 */
	function getCodeblock() {
		return npgFunctions::unTagURLs($this->get("codeblock"));
	}

	/**
	 * set the codeblocks as an serialized array
	 *
	 */
	function setCodeblock($cb) {
		$this->set('codeblock', npgFunctions::tagURLs($cb));
	}

	/**
	 * Retuns true if comments are allowed
	 *
	 * @return bool
	 */
	function getCommentsAllowed() {
		return $this->get('commentson');
	}

	/**
	 * Sets the comments allowed flag
	 *
	 * @param bool $commentson true if they are allowed
	 */
	function setCommentsAllowed($commentson) {
		$this->set('commentson', (int) ($commentson && true));
	}

	/**
	 * Returns an array of comments for this album
	 *
	 * @param bool $moderated if false, ignores comments marked for moderation
	 * @param bool $private if false ignores private comments
	 * @param bool $desc set to true for descending order
	 * @return array
	 */
	function getComments($moderated = false, $private = false, $desc = false) {
		$sql = "SELECT * FROM " . prefix('comments') .
						" WHERE `type`='" . $this->table . "' AND `ownerid`='" . $this->getID() . "'";
		if (!$moderated) {
			$sql .= " AND `inmoderation`=0";
		}
		if (!$private) {
			$sql .= " AND `private`=0";
		}
		$sql .= " ORDER BY id";
		if ($desc) {
			$sql .= ' DESC';
		}
		$comments = query_full_array($sql);
		$this->comments = $comments;
		return $this->comments;
	}

	/**
	 * Adds comments to an object
	 * assumes data is coming straight from GET or POST
	 *
	 * Returns a comment object
	 *
	 * @param string $name Comment author name
	 * @param string $email Comment author email
	 * @param string $website Comment author website
	 * @param string $comment body of the comment
	 * @param string $code CAPTCHA code entered
	 * @param string $code_ok CAPTCHA hash expected
	 * @param string $ip the IP address of the comment poster
	 * @param bool $private set to true if the comment is for the admin only
	 * @param bool $anon set to true if the poster wishes to remain anonymous
	 * @param string $customdata
	 * @return object
	 */
	function addComment($name, $email, $website, $comment, $code, $code_ok, $ip, $private, $anon, $customdata) {
		$commentobj = npgFilters::apply('object_addComment', NULL, $name, $email, $website, $comment, $code, $code_ok, $this, $ip, $private, $anon, $customdata);
		return $commentobj;
	}

	/**
	 * Returns the count of comments in the album. Ignores comments in moderation
	 *
	 * @return int
	 */
	function getCommentCount() {
		if (is_null($this->commentcount)) {
			if ($this->comments == null) {
				$count = db_count("comments", "WHERE `type`='" . $this->table . "' AND `inmoderation`=0 AND `private`=0 AND `ownerid`=" . $this->getID());
				$this->commentcount = $count;
			} else {
				$this->commentcount = count($this->comments);
			}
		}
		return $this->commentcount;
	}

	/**
	 * Checks basic access rights of an object
	 * @param bit $action what the caller wants to do
	 */
	function isMyItem($action) {
		if (npg_loggedin($this->manage_rights)) {
			return true;
		}
		if ($action == LIST_RIGHTS && npg_loggedin($this->access_rights)) {
			return true;
		}
		if (npgFilters::apply('check_credentials', false, $this, $action)) {
			return true;
		}
		return NULL;
	}

	/**
	 * returns false (deny) if gallery is "private"
	 * @param $hint
	 * @param $show
	 */
	function checkForGuest(&$hint = NULL, &$show = NULL) {
		return !(GALLERY_SECURITY != 'public');
	}

	/**
	 *
	 * Checks if viewing of object is allowed
	 * @param string $hint
	 * @param string $show
	 */
	function checkAccess(&$hint = NULL, &$show = NULL) {
		if ($this->isMyItem(LIST_RIGHTS)) {
			return true;
		}
		return $this->checkforGuest($hint, $show);
	}

	/**
	 * Returns the publish date
	 *
	 * @return string
	 */
	function getPublishDate() {
		return $this->get("publishdate");
	}

	/**
	 * sets the publish date
	 *
	 */
	function setPublishDate($ed) {
		if ($ed) {
			$newtime = dateTimeConvert($ed);
			if ($newtime === false)
				return;
			$this->set('publishdate', $newtime);
		} else {
			$this->set('publishdate', NULL);
		}
	}

	/**
	 * Returns the expire date
	 *
	 * @return string
	 */
	function getExpireDate() {
		return $this->get("expiredate");
	}

	/**
	 * sets the expire date
	 *
	 */
	function setExpireDate($ed) {
		if ($ed) {
			$newtime = dateTimeConvert($ed);
			if ($newtime === false)
				return;
			$this->set('expiredate', $newtime);
		} else {
			$this->set('expiredate', NULL);
		}
	}

	/**
	 * Invalidate the search cache because something has definately changed
	 */
	function remove() {
		if (class_exists('SearchEngine')) {
			SearchEngine::clearSearchCache($this);
		}
		return parent::remove();
	}

	function move($new_unique_set) {
		if (class_exists('SearchEngine')) {
			SearchEngine::clearSearchCache($this);
		}
		return parent::move($new_unique_set);
	}

	/**
	 * determines what the next sort order should be for new items.
	 *
	 * @param type $qualifier
	 * @return type
	 */
	protected function setDefaultSortOrder($qualifier = NULL) {
		$sql = 'SELECT `sort_order` FROM ' . prefix($this->table) . $qualifier . ' ORDER BY sort_order DESC';
		$result = query_single_row($sql);
		if ($rslt) {
			$new = isset($result['sort_order']) ? sprintf('%03u', min(999, (int) substr($result['sort_order'], 0, 3) + 1)) : '000';
			$this->setSortOrder($new);
		}
	}

}

//////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Root class for images and albums
 *
 */
class MediaObject extends ThemeObject {

	/**
	 * Class instantiator
	 */
	function __construct() {
		//	no actions required
	}

	/**
	 * Returns the description
	 *
	 * @return string
	 */
	function getDesc($locale = NULL) {
		$text = $this->get('desc');
		if ($locale == 'all') {
			return npgFunctions::unTagURLs($text);
		} else {
			return applyMacros(npgFunctions::unTagURLs(get_language_string($text, $locale)));
		}
	}

	/**
	 * Stores the description
	 *
	 * @param string $desc description text
	 */
	function setDesc($desc) {
		if ($desc) {
			$desc = npgFunctions::tagURLs($desc);
		}
		$this->set('desc', $desc);
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
		$text = $this->get('password_hint');
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

}
