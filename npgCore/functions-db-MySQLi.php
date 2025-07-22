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
function db_connect($config, $errorstop = E_USER_WARNING) {
	global $_DB_connection, $_DB_details;
	$_DB_details = unserialize(DB_NOT_CONNECTED);
	if (function_exists('mysqli_connect')) {
		$er = 'MySQLi::__construct()';
		mysqli_report(MYSQLI_REPORT_OFF); //	preserve the pre PHP 8.1 setting

		$denied = array(
				1040, /* Too Many Questions */
				1044, /* invalid user */
				1045, /* invalid password */
				1698, /* no password */
				3118, /* account locked */
				3878, /* empty password */
				3955 /* account blocked by password lock */
		);
		if (is_object($_DB_connection)) {
			$_DB_connection->close(); //	don't want to leave connections open
		}

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

		//	supress error reports for this loop
		$er_reporting = error_reporting();
		error_reporting(0);
		for ($i = 0; $i <= MYSQL_CONNECTION_RETRIES - 1; $i++) {
			$_DB_connection = @mysqli_connect($hostname, $username, $password, $db, $port, $socket);
			if (is_object($_DB_connection) || empty($errorstop) || in_array(db_errorno(), $denied)) {
				break;
			}
			sleep(pow(2, $i));
		}
		error_reporting($er_reporting);
	} else {
		$er = gettext('MySQLi extension not loaded');
	}


	if (!is_object($_DB_connection)) {
		if ($errorstop) {
			dbErrorReport(null, $er);
		}
		$_DB_connection = false;
		return false;
	}

	if (!$_DB_connection->select_db($db)) {
		if ($errorstop) {
			dbErrorReport(null, 'MySQLi::select_db()');
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
				mysqli_free_result($result);
				debugLogBacktrace($sql);
				debugLogVar(["EXPLAIN" => $explaination]);
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
	$result = db_query($sql, $errorstop);
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
	$result = db_query($sql, $errorstop);
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
		return array();
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
		$max = query_single_row('SHOW GLOBAL VARIABLES LIKE "max_user_connections";', false);
		if (!isset($max['Value']) || $max['Value'] == 0) {
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
			'connections' => $max['Value'],
			'client_info' => mysqli_get_client_info()
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
	$sql = "SHOW GRANTS;
				";
	$result = db_query($sql, false);
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
	return db_query('SET SESSION sql_mode=""', false);
}

/**
 * Queries the SQL session mode
 */
function db_getSQLmode() {
	$result = db_query('SELECT @@SESSION.sql_mode;', false);
	if (is_object($result)) {
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
		if (is_object($result)) {
			while ($row = mysqli_fetch_assoc($result)) {
				$_tableFields[$table][$row['Field']] = $row;
			}
			mysqli_free_result($result);
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

function db_free_result($result) {
	if (is_object($result)) {
		return mysqli_free_result($result);
	}
	return false;
}

?>
