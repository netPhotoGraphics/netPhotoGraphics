<?php

/**
 * Database core functions for the PDO::MySQL library
 *
 * Note: PHP version 5 states that the MySQL library is "Maintenance only, Long term deprecation announced."
 * It recommends using the PDO::MySQL or the MySQLi library instead.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package core
 */
// force UTF-8 Ø

define('DATABASE_SOFTWARE', 'PDO::MySQL');
Define('DATABASE_MIN_VERSION', '5.0.0');
Define('DATABASE_DESIRED_VERSION', '5.6.0');

/**
 * Connect to the database server and select the database.
 * @param array $config the db configuration parameters
 * @param bool $errorstop set to false to omit error messages
 * @return true if successful connection
 */
function db_connect($config, $errorstop = E_USER_ERROR) {
	global $_DB_connection, $_DB_details, $_DB_last_result;
	$_DB_details = unserialize(DB_NOT_CONNECTED);
	$_DB_last_result = NULL;
	if (class_exists('PDO')) {
		$denied = array(
				1044, /* invalid user */
				1045, /* invalid password */
				1698, /* no password */
				3118, /* account locked */
				3878, /* empty password */
				3955 /* account blocked by password lock */
		);
		list($username, $password) = selectDBuser($config);
		$db = $config['mysql_database'];
		$hostname = $config['mysql_host'];
		if (!isset($config['mysql_port']) || empty($config['mysql_port'])) {
			$port = ini_get('mysqli.default_port');
		} else {
			$port = $config['mysql_port'];
		}
		if (!isset($config['mysql_socket']) || $config['mysql_socket']) {
			$socket = ini_get('mysqli.default_socket');
		} else {
			$socket = $config['mysql_socket'];
		}

		if (is_object($_DB_connection)) {
			$_DB_connection = NULL; //	don't want to leave connections open
		}
		for ($i = 0; $i <= MYSQL_CONNECTION_RETRIES - 1; $i++) {
			try {
				$_DB_connection = new PDO("mysql:host=$hostname;port=$port;socket=$socket;dbname=$db", $username, $password);
				break;
			} catch (PDOException $e) {
				$_DB_last_result = $e;
				if (empty($errorstop) || in_array($e, $denied) || $i >= MYSQL_CONNECTION_RETRIES) {
					if ($errorstop) {
						trigger_error(sprintf(gettext('PDO_MySql Error: netPhotoGraphics received the error %s when connecting to the database server.'), $er . ': ' . $e->getMessage()), $errorstop);
					}
					//	the caller is prepaired to deal with the failure
					$_DB_connection = NULL;
					return false;
				}
				sleep(pow(2, $i));
			}
		}
	} else {
		trigger_error(gettext('PDO_MySQL extension not loaded.'), $errorstop);
	}

	$_DB_details = $config;
	//set character set protocol
	$software = db_software();
	$version = $software['version'];
	try {
		if (version_compare($version, '5.5.3', '>=')) {
			$_DB_connection->query("SET NAMES 'utf8mb4'");
		} else {
			$_DB_connection->query("SET NAMES 'utf8'");
		}
	} catch (PDOException $e) {
		//	:(
	}

	// set the sql_mode to relaxed (if possible)
	try {
		$_DB_connection->query('SET SESSION sql_mode="";');
	} catch (PDOException $e) {
		//	What can we do :(
	}
	return $_DB_connection;
}

/*
 * report the software of the database
 */

function db_software() {
	global $_DB_connection;
	if (is_object($_DB_connection)) {
		$dbversion = trim($_DB_connection->getAttribute(PDO::ATTR_SERVER_VERSION));
		preg_match('/[0-9,\.]*/', $dbversion, $matches);
		$max = query_single_row('SHOW GLOBAL VARIABLES LIKE "max_user_connections";');
		if (isset($max['Value']) || $max['Value'] == 0) {
			$max = query_single_row('SHOW GLOBAL VARIABLES LIKE "max_connections";');
		}
	}
	if (!isset($matches[0])) {
		$matches[0] = '?.?.?';
	}
	if (!isset($max['Value'])) {
		$max = array('Value' => 10);
	}
	return array('application' => DATABASE_SOFTWARE,
			'required' => DATABASE_MIN_VERSION,
			'desired' => DATABASE_DESIRED_VERSION,
			'version' => $matches[0],
			'connections' => $max['Value']
	);
}

/**
 * create the database
 */
function db_create() {
	global $_DB_details;
	$sql = 'CREATE DATABASE IF NOT EXISTS ' . '`' . $_DB_details['mysql_database'] . '` CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci';
	return db_query($sql, false);
}

/**
 * Returns user's permissions on the database
 */
function db_permissions() {
	global $_DB_details;
	$sql = "SHOW GRANTS;";
	$result = db_query($sql, false);
	if ($result) {
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
	return db_query('SET SESSION sql_mode=""', false);
}

/**
 * Queries the SQL session mode
 */
function db_getSQLmode() {
	$result = db_query('SELECT @@SESSION.sql_mode;', false);
	if ($result) {
		$row = db_fetch_row($result);
		return $row[0];
	}
	return false;
}

function db_create_table(&$sql) {
	return db_query($sql, false);
}

function db_table_update(&$sql) {
	return db_query($sql, false);
}

function db_show($what, $aux = '') {
	global $_DB_details;
	switch ($what) {
		case 'tables':
			$sql = "SHOW TABLES FROM `" . $_DB_details['mysql_database'] . "` LIKE '" . db_LIKE_escape($_DB_details['mysql_prefix']) . "%'";
			return db_query($sql, false);
		case 'columns':
			$sql = 'SHOW FULL COLUMNS FROM `' . $_DB_details['mysql_prefix'] . $aux . '`';
			return db_query($sql, false);
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
		if ($result) {
			while ($row = db_fetch_assoc($result)) {
				$_tableFields[$table][$row['Field']] = $row;
			}
			$result->closeCursor();
		}
	}
	return $_tableFields[$table];
}

function db_truncate_table($table) {
	global $_DB_details;
	$sql = 'TRUNCATE ' . $_DB_details['mysql_prefix'] . $table;
	return db_query($sql, false);
}

function db_LIKE_escape($str) {
	return strtr($str, array('_' => '\\_', '%' => '\\%'));
}

/**
 * The main query function. Runs the SQL on the connection and handles errors.
 * @param string $sql sql code
 * @param bool $errorstop set to false to supress the error message
 * @return results of the sql statements
 * @since 0.6
 */
function db_query($sql, $errorstop = true) {
	global $_DB_connection, $_DB_last_result, $_DB_details;
	$_DB_last_result = false;
	if ($_DB_connection) {
		try {
			$_DB_last_result = $_DB_connection->query($sql);
		} catch (PDOException $e) {
			$_DB_last_result = false;
		}
	}
	if (!$_DB_last_result && $errorstop) {
		dbErrorReport($sql);
	}
	return $_DB_last_result;
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
	if (strpos('SELECT', $sql) === 0) {
		$sql = rtrim($sql, ';') . 'LIMIT 1';
	}
	$result = db_query($sql, $errorstop);
	if ($result) {
		$row = db_fetch_assoc($result);
		$result->closeCursor();
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
	$result = db_query($sql, $errorstop);
	if ($result) {
		$allrows = array();
		if (is_null($key)) {
			foreach ($result as $row) {
				$allrows[] = $row;
			}
		} else {
			foreach ($result as $row) {
				$allrows[$row[$key]] = $row;
			}
		}
		$result->closeCursor();
		return $allrows;
	} else {
		return false;
	}
}

/**
 * PDO real_escape_string standin that insures the DB connection is passed.
 *
 * @param string $string
 * @return string
 */
function db_escape($string) {
	global $_DB_connection;
	if ($_DB_connection) {
		return trim($_DB_connection->quote($string), "'" . '"');
	} else {
		return addslashes($string);
	}
}

/*
 * returns the insert id of the last database insert
 */

function db_insert_id() {
	global $_DB_connection;
	return $_DB_connection->lastInsertId();
}

/*
 * Fetch a result row as an associative array
 */

function db_fetch_assoc($resource) {
	if (is_object($resource)) {
		return $resource->fetch(PDO::FETCH_ASSOC);
	}
	return false;
}

/*
 * 	returns the error number from the previous operation
 */

function db_errorno() {
	global $_DB_connection;
	if (is_object($_DB_connection)) {
		return $_DB_last_result->getCode();
	}
	return '---';
}

/*
 * Returns the text of the error message from previous operation
 */

function db_error() {
	global $_DB_last_result;
	if (is_object($_DB_last_result)) {
		return $_DB_last_result->getMessage();
	} else {
		return sprintf(gettext('%s not connected'), DATABASE_SOFTWARE);
	}
}

/*
 * Get number of affected rows in previous operation
 */

function db_affected_rows() {
	global $_DB_last_result;
	if (is_object($_DB_last_result)) {
		return $_DB_last_result->rowCount();
	} else {
		return 0;
	}
}

/*
 * Get a result row as an enumerated array
 */

function db_fetch_row($result) {
	if (is_object($result)) {
		return $result->fetch(PDO::FETCH_NUM);
	}
	return false;
}

/*
 * Get number of rows in result
 */

function db_num_rows($result) {
	if (is_array($result)) {
		return count($result);
	} else {
		return $result->rowCount();
	}
}

/**
 * Closes the database
 */
function db_close() {
	global $_DB_connection;
	$_DB_connection = NULL;
	return true;
}

function db_free_result($result) {
	return $result->closeCursor();
}
