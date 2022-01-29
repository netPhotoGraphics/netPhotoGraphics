<?php

/**
 * Database core functions for the MySQLi library
 *
 * Note: PHP version 5 states that the MySQL library is "Maintenance only, Long term deprecation announced."
 * It recommends using the PDO::MySQL or the MySQLi library instead.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package core
 */
// force UTF-8 Ø

define('DATABASE_SOFTWARE', 'MySQLi');
define('DATABASE_MIN_VERSION', '5.0.0');
define('DATABASE_DESIRED_VERSION', '5.6.0');

/**
 * Connect to the database server and select the database.
 * @param array $config the db configuration parameters
 * @param bool $errorstop set to false to omit error messages
 * @return true if successful connection
 */
function db_connect($config, $errorstop = E_USER_ERROR) {
	global $_DB_connection, $_DB_details;
	$_DB_details = unserialize(DB_NOT_CONNECTED);
	if (function_exists('mysqli_connect')) {
		if (is_object($_DB_connection)) {
			$_DB_connection->close(); //	don't want to leave connections open
		}
		if (!isset($config['mysql_port']) || empty($config['mysql_port'])) {
			$config['mysql_port'] = ini_get('mysqli.default_port');
		}
		if (!isset($config['mysql_socket']) || $config['mysql_socket']) {
			$config['mysql_socket'] = ini_get('mysqli.default_socket');
		}

		for ($i = 1; $i <= MYSQL_CONNECTION_RETRIES; $i++) {
			$_DB_connection = @mysqli_connect($config['mysql_host'], $config['mysql_user'], $config['mysql_pass'], '', $config['mysql_port'], $config['mysql_socket']);
			$e = mysqli_connect_errno();
			$er = $e . ': ' . mysqli_connect_error();
			if (empty($errorstop) || is_object($_DB_connection)) {
				//	we either got connected or the caller is prepaired to deal with the failure
				break;
			}
			sleep($i);
		}
	} else {
		$er = gettext('"extension not loaded"');
	}
	if (!is_object($_DB_connection)) {
		if ($errorstop) {
			trigger_error(sprintf(gettext('MySQLi Error: netPhotoGraphics received the error %s when connecting to the database server.'), $er), $errorstop);
		}
		$_DB_connection = false;
		return false;
	}
	$_DB_details['mysql_host'] = $config['mysql_host'];
	if (!$_DB_connection->select_db($config['mysql_database'])) {
		if ($errorstop) {
			trigger_error(sprintf(gettext('MySQLi Error: MySQLi returned the error %1$s when netPhotoGraphics tried to select the database %2$s.'), $_DB_connection->error, $config['mysql_database']), $errorstop);
		}
		return false;
	}
	$_DB_details = $config;

	//set character set protocol
	$software = db_software();
	$version = $software['version'];
	if (version_compare($version, '5.5.3', '>=')) {
		$_DB_connection->set_charset("utf8mb4");
	} else {
		$_DB_connection->set_charset("utf8");
	}

	// set the sql_mode to relaxed (if possible)
	$_DB_connection->query('SET SESSION sql_mode="";');
	return $_DB_connection;
}

/**
 * The main query function. Runs the SQL on the connection and handles errors.
 * @param string $sql sql code
 * @param bool $errorstop set to false to supress the error message
 * @return results of the sql statements
 * @since 0.6
 */
function db_query($sql, $errorstop = true) {
	global $_DB_connection;
	if ($_DB_connection) {
		if (EXPLAIN_SELECTS && strpos($sql, 'SELECT') !== false) {
			$result = $_DB_connection->query('EXPLAIN ' . $sql);
			if ($result) {
				$explaination = array();
				while ($row = $result->fetch_assoc()) {
					$explaination[] = $row;
				}
				debugLogVar(["EXPLAIN $sql" => $explaination]);
			}
		}
		if ($result = $_DB_connection->query($sql)) {
			return $result;
		}
	}
	if ($errorstop) {
		dbErrorReport($sql);
	}
	return false;
}

/**
 * Runs a SQL query and returns an associative array of the first row.
 * Doesn't handle multiple rows, so this should only be used for unique entries.
 * @param string $sql sql code
 * @param bool $errorstop set to false to supress the error message
 * @return results of the sql statements
 * @since 0.6
 */
function query_single_row($sql, $errorstop = true) {
	global $_DB_connection;
	if (strpos('SELECT', $sql) === 0) {
		$sql = rtrim($sql, ';') . 'LIMIT 1';
	}
	$result = query($sql, $errorstop);
	if (is_object($result)) {
		$row = $result->fetch_assoc();
		mysqli_free_result($result);
		return $row;
	} else {
		return false;
	}
}

/**
 * Runs a SQL query and returns an array of associative arrays of every row returned.
 * @param string $sql sql code
 * @param bool $errorstop set to false to supress the error message
 * @param string $key optional array index key
 * @return results of the sql statements
 * @since 0.6
 */
function query_full_array($sql, $errorstop = true, $key = NULL) {
	global $_DB_connection;
	$result = query($sql, $errorstop);
	if (is_object($result)) {
		$allrows = array();
		if (is_null($key)) {
			while ($row = $result->fetch_assoc()) {
				$allrows[] = $row;
			}
		} else {
			while ($row = $result->fetch_assoc()) {
				$allrows[$row[$key]] = $row;
			}
		}
		mysqli_free_result($result);
		return $allrows;
	} else {
		return false;
	}
}

/**
 * mysqli_real_escape_string standin that insures the DB connection is passed.
 *
 * @param string $string
 * @return string
 */
function db_escape($string) {
	global $_DB_connection;
	if ($_DB_connection) {
		return $_DB_connection->real_escape_string($string);
	} else {
		return addslashes($string);
	}
}

/*
 * returns the insert id of the last database insert
 */

function db_insert_id() {
	global $_DB_connection;
	return $_DB_connection->insert_id;
}

/*
 * Fetch a result row as an associative array
 */

function db_fetch_assoc($resource) {
	if ($resource) {
		return $resource->fetch_assoc();
	}
	return false;
}

/*
 * 	returns the error number from the previous operation
 */

function db_errorno() {
	global $_DB_connection;
	if (is_object($_DB_connection)) {
		return mysqli_errno($_DB_connection);
	}
	return mysqli_connect_errno();
}

/**
 * Returns the text of the error message from previous operation
 */
function db_error() {
	global $_DB_connection;
	if (is_object($_DB_connection)) {
		return mysqli_error($_DB_connection);
	}
	if (!$msg = mysqli_connect_error()) {
		$msg = sprintf(gettext('%s not connected'), DATABASE_SOFTWARE);
	}
	return $msg;
}

/*
 * Get number of affected rows in previous operation
 */

function db_affected_rows() {
	global $_DB_connection;
	return $_DB_connection->affected_rows;
}

/*
 * Get a result row as an enumerated array
 */

function db_fetch_row($result) {
	if (is_object($result)) {
		return $result->fetch_row();
	}
	return false;
}

/*
 * Get number of rows in result
 */

function db_num_rows($result) {
	return $result->num_rows;
}

/**
 * Closes the database
 */
function db_close() {
	global $_DB_connection;
	if ($_DB_connection) {
		$rslt = $_DB_connection->close();
	} else {
		$rslt = true;
	}
	$_DB_connection = NULL;
	return $rslt;
}

/*
 * report the software of the database
 */

function db_software() {
	global $_DB_connection;
	if (is_object($_DB_connection)) {
		$dbversion = trim($_DB_connection->get_server_info());
		preg_match('/[0-9,\.]*/', $dbversion, $matches);
	} else {
		$matches[0] = '?.?.?';
	}
	return array('application' => DATABASE_SOFTWARE, 'required' => DATABASE_MIN_VERSION, 'desired' => DATABASE_DESIRED_VERSION, 'version' => $matches[0]);
}

/**
 * create the database
 */
function db_create() {
	global $_DB_details;
	$sql = 'CREATE DATABASE IF NOT EXISTS ' . '`' . $_DB_details['mysql_database'] . '` CHARACTER SET utf8 COLLATE utf8_unicode_ci';
	return query($sql, false);
}

/**
 * Returns user's permissions on the database
 */
function db_permissions() {
	global $_DB_details;
	$sql = "SHOW GRANTS FOR " . $_DB_details['mysql_user'] . ";";
	$result = query($sql, false);
	if (!$result) {
		$result = query("SHOW GRANTS;", false);
	}
	if (is_object($result)) {
		$db_results = array();
		while ($onerow = db_fetch_row($result)) {
			$db_results[] = $onerow[0];
		}
		return $db_results;
	} else {
		return false;
	}
}

/**
 * Sets the SQL session mode to empty
 */
function db_setSQLmode() {
	return query('SET SESSION sql_mode=""', false);
}

/**
 * Queries the SQL session mode
 */
function db_getSQLmode() {
	$result = query('SELECT @@SESSION.sql_mode;', false);
	if (is_object($result)) {
		$row = db_fetch_row($result);
		return $row[0];
	}
	return false;
}

function db_create_table(&$sql) {
	return query($sql, false);
}

function db_table_update(&$sql) {
	return query($sql, false);
}

function db_show($what, $aux = '') {
	global $_DB_details;
	switch ($what) {
		case 'tables':
			$sql = "SHOW TABLES FROM `" . $_DB_details['mysql_database'] . "` LIKE '" . db_LIKE_escape($_DB_details['mysql_prefix']) . "%'";
			return query($sql, false);
		case 'columns':
			$sql = 'SHOW FULL COLUMNS FROM `' . $_DB_details['mysql_prefix'] . $aux . '`';
			return query($sql, false);
		case 'variables':
			$sql = "SHOW VARIABLES LIKE '$aux'";
			return query_full_array($sql);
		case 'index':
			$sql = "SHOW INDEX FROM `" . $_DB_details['mysql_database'] . '`.' . $aux;
			return query_full_array($sql, false);
	}
}

function db_list_fields($table) {
	global $_tableFields;
	if (!isset($_tableFields[$table])) {
		$_tableFields[$table] = array();
		$result = db_show('columns', $table);
		if (is_object($result)) {
			while ($row = db_fetch_assoc($result)) {
				$_tableFields[$table][$row['Field']] = $row;
			}
		}
	}
	return $_tableFields[$table];
}

function db_truncate_table($table) {
	global $_DB_details;
	$sql = 'TRUNCATE ' . $_DB_details['mysql_prefix'] . $table;
	return query($sql, false);
}

function db_LIKE_escape($str) {
	return strtr($str, array('_' => '\\_', '%' => '\\%'));
}

function db_free_result($result) {
	return mysqli_free_result($result);
}

?>
