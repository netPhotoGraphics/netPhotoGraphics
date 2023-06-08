<?php
/**
 * basic functions used by i.php
 * Keep this file to the minimum to allow the largest available memory for processing images!
 * Headers not sent yet!
 *
 * @author Stephen Billard (sbillard)
 *
 * @package functions
 *
 */
// force UTF-8 Ø

/**
 * Returns the viewer's IP address
 * Deals with transparent proxies
 *
 * @return string
 */
function getUserIP() {
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		if ($ip = filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
			return $ip;
		}
	}
	return filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6);
}

/**
 * Returns the viewer's IDs
 *
 * This is his username if logged in, otherwise we use getUserIP()
 *
 * @return string
 */
function getUserID() {
	global $_themeCript, $_adminCript;
	$id = getUserIP();
	if ($_themeCript) {
		$id = $_themeCript->encrypt($id);
	} else if ($_adminCript) {
		$id = $_adminCript->encrypt($id);
	}
	return $id;
}

/**
 * Traps exceptions for logging
 *
 * @param type $ex the exception
 */
function npgExceptionHandler($ex) {
	npgErrorHandler($ex->getCode(), $ex->getMessage(), $ex->getFile(), $ex->getLine(), null, $ex->getTrace());
}

/**
 *
 * Traps errors and insures thy are logged.
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param string $errline
 * @return void|boolean
 */
function npgErrorHandler($errno, $errstr = '', $errfile = '', $errline = '', $deprecated = null, $trace = 1) {
	global $_current_admin_obj, $_index_theme;
	// if error has been supressed with an @
	if (error_reporting() == 0 && !in_array($errno, array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE))) {
		return false;
	}

	switch ($errno) {
		case E_ERROR :
			$err = gettext('ERROR');
			break;
		case E_WARNING:
			$err = gettext('WARNING');
			break;
		case E_NOTICE:
			$err = gettext('NOTICE');
			break;
		case E_USER_ERROR:
			$err = gettext('USER ERROR');
			break;
		case E_USER_WARNING:
			$err = gettext('USER WARNING');
			break;
		case E_USER_NOTICE:
			$err = gettext('USER NOTICE');
			break;
		case E_STRICT:
			$err = gettext('STRICT NOTICE');
			break;
		default:
			$err = gettext("EXCEPTION ($errno)");
			$errno = E_ERROR;
	}
	$msg = sprintf(gettext('%1$s: "%2$s" in %3$s on line %4$s'), $err, $errstr, $errfile, $errline);
	debugLogBacktrace($msg, $trace);

	if ($errno == E_ERROR || $errno == E_USER_ERROR) {
		// out of curtesy show the error message on the WEB page since there will likely be a blank page otherwise
		?>
		<div style="padding: 10px 15px 10px 15px;	background-color: #FDD;	border-width: 1px 1px 2px 1px;	border-style: solid;	border-color: #FAA;	margin-bottom: 10px;	font-size: 100%;">
			<?php echo html_encode($msg); ?>
		</div>
		<?php
	}
	return false;
}

/**
 * shut-down handler, check for errors
 */
function npgShutDownFunction() {
	$error = error_get_last();
	if ($error && !in_array($error['type'], array(E_USER_ERROR, E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING, E_NOTICE, E_USER_NOTICE))) {
		$file = str_replace('\\', '/', $error['file']);
		preg_match('~(.*)/(' . USER_PLUGIN_FOLDER . '|' . PLUGIN_FOLDER . ')~', $file, $matches);
		if (isset($matches[2])) {
			$path = trim(preg_replace('~^.*' . $matches[2] . '~i', '', $file), '/');
			$path = explode('/', $path . '/');
			$extension = stripSuffix($path[0]);
			if ($extension) {
				enableExtension($extension, 0);
			}
		}
		npgErrorHandler($error['type'], $error['message'], $file, $error['line']);
	}
	if (function_exists('db_close')) {
		db_close();
	}
}

/**
 * Converts a file system filename to UTF-8 for internal storage
 *
 * @param string $filename the file name to convert
 * @return string
 */
function filesystemToInternal($filename) {
	global $_UTF8;
	if ($_UTF8 && FILESYSTEM_CHARSET != LOCAL_CHARSET) {
		$filename = str_replace('\\', '/', $_UTF8->convert($filename, FILESYSTEM_CHARSET, LOCAL_CHARSET));
	}
	return $filename;
}

/**
 * Converts an Internal filename string to one compatible with the file system
 *
 * @param string $filename the file name to convert
 * @return string
 */
function internalToFilesystem($filename) {
	global $_UTF8;
	if (FILESYSTEM_CHARSET != LOCAL_CHARSET) {
		$filename = $_UTF8->convert($filename, LOCAL_CHARSET, FILESYSTEM_CHARSET);
	}
	return $filename;
}

/**
 * Returns the suffix of a file name
 *
 * @param string $filename
 * @return string
 */
function getSuffix($filename) {
	if ($filename) {
		return strtolower(substr(strrchr($filename, "."), 1));
	} else {
		return '';
	}
}

/**
 * returns a file name sans the suffix
 *
 * @param unknown_type $filename
 * @return unknown
 */
function stripSuffix($filename) {
	$split = explode('/', $filename);
	$base = array_pop($split);
	$i = strrpos($base, '.');
	if ($i !== FALSE) {
		$base = substr($base, 0, $i);
	}
	array_push($split, $base);
	return implode('/', $split);
}

/**
 * Takes user input meant to be used within a path to a file or folder and
 * removes anything that could be insecure or malicious, or result in duplicate
 * representations for the same physical file.
 *
 * This function is used primarily for album names.
 * NOTE: The initial and trailing slashes are removed!!!
 *
 * Returns the sanitized path
 *
 * @param string $filename is the path text to filter.
 * @return string
 */
function sanitize_path($filename) {
	$filename = strip_tags(str_replace('\\', '/', $filename));
	$filename = preg_replace(array('/[[:cntrl:]]/', '/\/\.+/', '/^\.+/', '/</', '/>/', '/\?/', '/\*/', '/\"/', '/\|/', '/\/+$/', '/^\/+/'), '', $filename);
	$filename = preg_replace('/\/\/+/', '/', $filename);
	return $filename;
}

/**
 * Checks if the input is numeric, rounds if so, otherwise returns false.
 *
 * @param mixed $num the number to be sanitized
 * @return int
 */
function sanitize_numeric($num) {
	if ($num) {
		$f = filter_var(str_replace(', ', '.', trim($num)), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
		if ($f) {
			return (int) round($f);
		}
	}
	return 0;
}

/**
 * sanitize a date input
 *
 * @param type $date
 * @return mixed a canonical date string or FALSE if the date is invalid
 */
function sanitizeDate($date) {
	$timestamp = strtotime($date);
	if ($timestamp) {
		$format = array('Y-', 'm-', 'd ', 'H:', 'i:', 's');
		$d = strtr(rtrim($date, '/'), array('/' => '-', ' ' => '-', ':' => '-'));
		$count = count(explode('-', $d));
		while ($count < count($format)) {
			array_pop($format);
		}
		return formattedDate(trim(implode('', $format), '-:'), $timestamp);
	}
	return FALSE;
}

/**
 * removes script tags
 *
 * @param string $text
 * @return string
 */
function sanitize_script($text) {
	return preg_replace('~<script.*>.*</script>~isU', '', $text);
}

/** Make strings generally clean.  Takes an input string and cleans out
 * null-bytes, slashes (if magic_quotes_gpc is on), and optionally use KSES
 * library to prevent XSS attacks and other malicious user input.
 * @param string $input_string is a string that needs cleaning.
 * @param string $sanitize_level is a number between 0 and 3 that describes the
 * type of sanitizing to perform on $input_string.
 *   0 - Basic sanitation. Only strips null bytes. Not recommended for submitted form data.
 *   1 - User specified. (User defined code is allowed. Used for descriptions and comments.)
 *   2 - Text style/formatting. (Text style codes allowed. Used for titles.)
 *   3 - Full sanitation. (Default. No code allowed. Used for text only fields)
 * @return string the sanitized string.
 */
function sanitize($input_string, $sanitize_level = 3) {
	if (is_array($input_string)) {
		$output_string = array();
		foreach ($input_string as $output_key => $output_value) {
			$output_string[$output_key] = sanitize($output_value, $sanitize_level);
		}
	} else {
		$output_string = sanitize_string($input_string, $sanitize_level);
	}
	return $output_string;
}

/** returns a sanitized string for the sanitize function
 * @param string $input_string
 * @param string $sanitize_level See sanitize()
 * @return string the sanitized string.
 */
function sanitize_string($input, $sanitize_level) {
	if (is_string($input)) {
		$input = str_replace(chr(0), " ", $input);
		switch ($sanitize_level) {
			case 0:
				return $input;
			case 2:
				// Strips non-style tags.
				$input = sanitize_script($input);
				return kses($input, getAllowedTags('style_tags'));
			case 3:
				// Full sanitation.  Strips all code.
				return kses($input, array());
			case 1:
				// Text formatting sanititation.
				$input = sanitize_script($input);
				return kses($input, getAllowedTags('allowed_tags'));
			case 4:
			default:
				// for internal use to eliminate security injections
				return sanitize_script($input);
		}
	}
	return $input;
}

// database helper functions

/**
 * Prefix a table name with a user-defined string to avoid conflicts.
 * This MUST be used in all database queries.
 * @param string $tablename name of the table
 * @return prefixed table name
 * @since 0.6
 */
function prefix($tablename = NULL) {
	return '`' . DATABASE_PREFIX . $tablename . '`';
}

/**
 * Constructs a WHERE clause ("WHERE uniqueid1='uniquevalue1' AND uniqueid2='uniquevalue2' ...")
 *  from an array (map) of variables and their values which identifies a unique record
 *  in the database table.
 * @param string $unique_set what to add to the WHERE clause
 * @return contructed WHERE cleause
 * @since 0.6
 */
function getWhereClause($unique_set) {
	if (empty($unique_set))
		return ' ';
	$unique_set = array_change_key_case($unique_set, CASE_LOWER);
	$where = ' WHERE';
	foreach ($unique_set as $var => $value) {
		$where .= ' `' . $var . '` = ' . db_quote($value) . ' AND';
	}
	return substr($where, 0, -4);
}

/**
 * Constructs a SET clause ("SET uniqueid1='uniquevalue1', uniqueid2='uniquevalue2' ...")
 *  from an array (map) of variables and their values which identifies a unique record
 *  in the database table. Used to 'move' records. Note: does not check anything.
 * @param string $new_unique_set what to add to the SET clause
 * @return contructed SET cleause
 * @since 0.6
 */
function getSetClause($new_unique_set) {
	$i = 0;
	$set = ' SET';
	foreach ($new_unique_set as $var => $value) {
		$set .= ' `' . $var . '`=';
		if (is_null($value)) {
			$set .= 'NULL';
		} else {
			$set .= db_quote($value) . ',';
		}
	}
	return substr($set, 0, -1);
}

/**
 * gating function for all database queries
 * @param type $sql
 * @param type $errorstop
 */
function query($sql, $errorstop = true) {
	global $_DB_connection;
	$result = class_exists('npgFilters') ? npgFilters::apply('database_query', NULL, $sql) : NULL;
	if (is_null($result)) {
		$result = db_query($sql, $_DB_connection && $errorstop);
	}
	return $result;
}

/**
 * Common error reporting for query errors
 * @param type $sql
 */
function dbErrorReport($sql) {
	trigger_error(sprintf(gettext('%1$s Error: ( %2$s ) failed. %1$s returned the error %3$s'), DATABASE_SOFTWARE, $sql, db_errorno() . ': ' . db_error()), E_USER_ERROR);
}

/**
 * Returns a properly quoted string for DB queries
 * @param type $string
 * @return type
 */
function db_quote($string) {
	if ($string) {
		$string = db_escape($string);
	}
	return $string = "'" . $string . "'";
	;
}

/*
 * returns the connected database name
 */

function db_name() {
	return getOption('mysql_database');
}

/**
 * returns the count of items in $table[$field] that satisfy the $clause
 *
 * @param type $table
 * @param type $clause
 * @param type $field
 * @return int
 */
function db_count($table, $clause = NULL, $field = "*") {
	$sql = 'SELECT COUNT(' . $field . ') FROM ' . prefix($table) . ' ' . $clause;
	$result = query_single_row($sql);
	if ($result) {
		return reset($result);
	} else {
		return 0;
	}
}

/**
 * decoder for html strings
 *
 * @param string $string
 * @return string
 */
function html_decode($string) {
	$string = html_entity_decode($string, ENT_QUOTES, LOCAL_CHARSET);
	// Replace numeric entities because html_entity_decode doesn't do it for us.
	if (function_exists('mb_convert_encoding')) {
		$string = preg_replace_callback("/(&#[0-9]+;)/", function ($m) {
			return mb_convert_encoding($m[1], LOCAL_CHARSET, "HTML-ENTITIES");
		}, $string);
	}
	return $string;
}

/**
 * encodes a pre-sanitized string to be used in an HTML text-only field (value, alt, title, etc.)
 *
 * @param string $this_string
 * @return string
 */
function html_encode($this_string) {
	if ($this_string) {
		$this_string = htmlspecialchars($this_string, ENT_FLAGS, LOCAL_CHARSET);
	}
	return $this_string;
}

/**
 * rawurlencode function that is path-safe (does not encode /)
 *
 * @param string $path URL
 * @return string
 */
function pathurlencode($path) {
	$parts = mb_parse_url($path);
	if (isset($parts['query'])) {
		$pairs = array_map("html_encode", parse_query($parts['query']));
		$parts['query'] = http_build_query($pairs, '', '&amp;');
	}
	if (array_key_exists('path', $parts)) {
		$parts['path'] = implode("/", array_map("rawurlencode", explode("/", $parts['path'])));
	}
	return build_url($parts);
}

/**
 * Makes directory recursively, returns TRUE if exists or was created sucessfuly.
 * Note: PHP5 includes a recursive parameter to mkdir, but it apparently does not
 * 				does not traverse symlinks!
 * @param string $pathname The directory path to be created.
 * @return boolean TRUE if exists or made or FALSE on failure.
 */
function mkdir_recursive($pathname, $mode) {
	if (!is_dir(dirname($pathname))) {
		mkdir_recursive(dirname($pathname), $mode);
	}
	return is_dir($pathname) || mkdir($pathname, $mode);
}

function array_map_recursive(callable $func, array $array) {
	return filter_var($array, \FILTER_CALLBACK, ['options' => $func]);
}

/**
 * debugging tool shows labeled variable contents
 *
 * @param array $args
 */
function varDebug($args) {
	if (!is_array($args)) {
		$args = array('var' => $args);
	}
	$dump = explode("\n", var_export($args, true));
	//get rid of the outer array element
	unset($dump[0]);
	array_pop($dump);
	$br = '';
	echo '<pre>' . "\n";
	foreach ($dump as $i => $line) {
		if (trim($line) == 'array (') {
			echo 'array (';
		} else {
			$line = html_encode($line);
			$line = str_replace(' ', '&nbsp;', $line);
			echo $br . $line;
			$br = '<br />';
		}
	}
	echo '</pre>';
}

/**
 * Write output to the debug log
 * Use this for debugging when echo statements would come before headers are sent
 * or would create havoc in the HTML.
 * Creates (or adds to) a file named debug.log which is located in the core folder
 *
 * @param string $message the debug information
 * @param bool $reset set to true to reset the log to zero before writing the message
 * @param string $log

  alternative log file
 */
function debugLog($message, $reset = false, $log = 'debug') {
	global $_adminCript;
	if (getOption('debug_log_encryption')) {
		$_logCript = $_adminCript;
	} else {
		$_logCript = NULL;
	}

	if (defined('SERVERPATH')) {
		global $_mutex;
		$path = SERVERPATH . '/' . DATA_FOLDER . '/' . $log . '.log';
		if (file_exists($path)) {
			$size = filesize($path);
		} else {
			$size = 0;
		}
		$me = getmypid();
		if (is_object($_mutex))
			$_mutex->lock();
		if ($reset || $size == 0 || (defined('DEBUG_LOG_SIZE') && DEBUG_LOG_SIZE && $size > DEBUG_LOG_SIZE)) {
			if (!$reset && $size > 0) {
				$perms = fileperms($path);
				switchLog('debug');
			}
			$f = fopen($path, 'w');
			if ($f) {
				if (!class_exists('npgFunctions') || npgFunctions::hasPrimaryScripts()) {
					$clone = '';
				} else {
					$clone = ' ' . gettext('clone');
				}
				$preamble = '<span class="lognotice">{' . $me . ':' . gmdate('D, d M Y H:i:s') . " GMT} netPhotoGraphics v" . NETPHOTOGRAPHICS_VERSION . $clone . '</span>';
				if ($_logCript) {
					$preamble = $_logCript->encrypt($preamble);
				}
				fwrite($f, $preamble . NEWLINE);
				if (defined('LOG_MOD')) {
					chmod($path, LOG_MOD);
				}
			}
		} else {
			$f = fopen($path, 'a');
			if ($f) {
				$preamble = '<span class="lognotice">{' . $me . ':' . gmdate('D, d M Y H:i:s') . " GMT}</span>";
				if ($_logCript) {
					$preamble = $_logCript->encrypt($message);
				}
				fwrite($f, $preamble . NEWLINE);
			}
		}
		if ($f) {
			if ($_logCript) {
				$message = $_logCript->encrypt($message);
			}
			fwrite($f, " " . $message . NEWLINE);
			fclose($f);
			clearstatcache();
		}
		if (is_object($_mutex))
			$_mutex->unlock();
	}
}

/**
 * Logs the calling stack
 *
 * @param string $message Message to prefix the backtrace
 * @param int $omit count of "callers" to remove from backtrace
 * @param string $log

  alternative log file
 */
function debugLogBacktrace($message, $omit = 0, $log = 'debug') {
	global $_current_admin_obj, $_index_theme;
	$output = trim($message) . NEWLINE;
	$uri = FALSE;
	if (array_key_exists('REQUEST_URI', $_SERVER)) {
		$uri = sanitize($_SERVER['REQUEST_URI']);
		preg_match('|^(http[s]*\://[a-zA-Z0-9\-\.]+/?)*(.*)$|xis', $uri, $matches);
		$uri = $matches[2];
		if (!empty($matches[1])) {
			$uri = '/' . $uri;
		}
	} else {
		if (isset($_SERVER['SCRIPT_NAME'])) {
			$uri = sanitize($_SERVER['SCRIPT_NAME']);
		}
	}
	if ($uri) {
		$uri = "\n URI:" . urldecode(str_replace('\\', '/', $uri));
	}
	$uri .= "\n IP `" . getUserIP() . '`';
	if (is_object($_current_admin_obj)) {
		$uri .= "\n " . gettext('user') . ':' . $_current_admin_obj->getUser();
	}
	if ($_index_theme) {
		$uri .= "\n " . gettext('theme') . ':' . $_index_theme;
	}
	$output .= $uri . NEWLINE;
	// Get a backtrace.
	if (is_array($omit)) {
		$bt = $omit;
	} else {
		$bt = debug_backtrace();
		while ($omit >= 0) {
			array_shift($bt); // Get rid of debug_backtrace, callers in the backtrace.
			$omit--;
		}
	}

	$prefix = '  ';
	$line = '';
	$caller = '';
	foreach ($bt as $b) {
		$caller = (isset($b['class']) ? $b['class'] : '') . (isset($b['type']) ? $b['type'] : '') . $b['function'];
		if (!empty($line)) { // skip first output to match up functions with line where they are used.
			$prefix .= '  ';
			$output .= 'from ' . $caller . ' (' . $line . ")\n" . $prefix;
		} else {
			$output .= '  ' . $caller . " called ";
		}
		$date = false;
		if (isset($b['file']) && isset($b['line'])) {
			$line = basename($b['file']) . ' [' . $b['line'] . "]";
		} else {
			$line = 'unknown';
		}
	}
	if (!empty($line)) {
		$output .= 'from ' . $line;
	}
	debugLog($output, false, $log);
}

/**
 * Records a Var to the debug log
 *
 * @param string $var an array of variables to log [optional]
 * @param string $log alternative log file
 */
function debugLogVar($var) {
	$args = func_get_args();
	if (count($args) == 2) {
		$log = $args[1];
	} else {
		$log = 'debug';
	}
	ob_start();
	varDebug($var);
	$str = ob_get_clean();
	$str = preg_replace('~<[/]*pre(.*?)>~', '', $str);
	$str = html_decode($str);
	$str = str_replace('  ', "\t", $str);
	debugLog($str, false, $log);
}

/**
 *
 * Starts a session (perhaps a secure one)
 */
function npg_session_start() {
	global $_conf_vars;

	if (($id = session_id()) && session_name() == SESSION_NAME) {
		if (!defined('npg_SID')) {
			define('npg_SID', session_id());
		}
		return TRUE;
	} else {
		if ($id) {
			session_abort(); //	close existing session which has different name
		}
		session_name(SESSION_NAME);
		//	insure that the session data has a place to be saved
		if (isset($_conf_vars['session_save_path'])) {
			session_save_path($_conf_vars['session_save_path']);
		}
		$_session_path = session_save_path();
		if (ini_get('session.save_handler') == 'files' && !file_exists($_session_path) || !is_writable($_session_path)) {
			mkdir_recursive(SERVERPATH . '/PHP_sessions', (fileperms(__DIR__) & 0666) | 0311);
			session_save_path(SERVERPATH . '/PHP_sessions');
		}
		//	setup session cookie
		$sessionCookie = array(
				'lifetime' => 0,
				'path' => WEBPATH . '/',
				'domain' => $_SERVER['HTTP_HOST'],
				'secure' => PROTOCOL == 'https',
				'httponly' => TRUE,
				'samesite' => 'Strict'
		);
		if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
			session_set_cookie_params($sessionCookie);
		} else {
			session_set_cookie_params($sessionCookie['lifetime'], $sessionCookie['path'], $sessionCookie['domain'], $sessionCookie['secure'], $sessionCookie['httponly']);
		}
		$result = session_start();
		define('npg_SID', session_id());
		$_SESSION['version'] = NETPHOTOGRAPHICS_VERSION;
		return $result;
	}
}

function npg_session_destroy() {
	$name = session_name();
	$_SESSION = array();
	if (ini_get("session.use_cookies")) {
		$params = session_get_cookie_params();
		setcookie($name, 'null', 1, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
	} else {
		setcookie($name, 'null', 1);
	}
	if (session_status() == PHP_SESSION_ACTIVE) {
		session_destroy();
	}
}

/**
 * Returns the value of a cookie from either the cookies or from $_SESSION[]
 *
 * @param string $name the name of the cookie
 */
function getNPGCookie($name) {
	if (isset($_COOKIE[$name])) {
		$cookiev = $_COOKIE[$name];
	} else {
		$cookiev = NULL;
	}
	if (DEBUG_LOGIN) {
		if (isset($_SESSION[$name])) {
			$sessionv = $_SESSION[$name];
		} else {
			$sessionv = '';
		}
		debugLog("getNPGCookie($name)::" . 'album_session=' . GALLERY_SESSION . "; SESSION[" . session_id() . "]=" . $sessionv . ", COOKIE=" . $cookiev);
	}
	if (!defined('GALLERY_SESSION') || !GALLERY_SESSION) {
		if (defined('IP_TIED_COOKIES') && IP_TIED_COOKIES) {
			if ($cookiev && !(strlen($cookiev) % 2)) {
				if (preg_match('~^[0-9A-F]+$~i', $cookiev)) {
					$cookiev = hex2bin($cookiev);
				}
				return rc4(getUserIP() . HASH_SEED, $cookiev);
			}
		}
		return $cookiev;
	}
	if (isset($_SESSION[$name])) {
		return $_SESSION[$name];
	}
	return NULL;
}

/**
 * Sets a cookie both in the browser cookies and in $_SESSION[]
 *
 * @param string $name The 'cookie' name
 * @param string $value The value to be stored
 * @param int $time The time delta until the cookie expires. Set negative to clear cookie,
 * 									set to FALSE to expire at end of session
 * @param array $uniqueoptions setCookie options array / bool $security TRUE for a secure cookie
 */
function setNPGCookie($name, $value, $time = NULL, $uniqueoptions = array()) {
	if (is_null($value)) {
		$cookiev = '';
	} else if ($value && defined('IP_TIED_COOKIES') && IP_TIED_COOKIES) {
		$cookiev = bin2hex(rc4(getUserIP() . HASH_SEED, $value));
	} else {
		$cookiev = $value;
	}
	if (is_null($t = $time)) {
		$t = time() + COOKIE_PERSISTENCE;
	} else {
		if (is_numeric($time)) {
			$t = time() + $time;
		}
	}
	$path = getOption('cookie_path');
	if (empty($path)) {
		$path = WEBPATH;
	}

	$options = array_merge(
					array(
							'expires' => (int) $t,
							'path' => rtrim($path, '/') . '/',
							'domain' => '',
							'httponly' => TRUE,
							'samesite' => 'Strict',
							'secure' => PROTOCOL == 'https'
					), $uniqueoptions);

	if (DEBUG_LOGIN) {
		debugLogVar(["setNPGCookie($name, $value)" => $options, 'album_session' => GALLERY_SESSION, 'SESSION' => session_id()]);
	}
	if (($time < 0) || !GALLERY_SESSION) {
		if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
			setcookie($name, $cookiev, $options);
		} else {
			setcookie($name, $cookiev, $options['expires'], $options['path'], $options['domain'], $options['secure'], $options['httponly']);
		}
	}
	if ($time < 0) {
		if (session_id()) {
			unset($_SESSION[$name]);
		}
		if (isset($_COOKIE)) {
			unset($_COOKIE[$name]);
		}
	} else {
		if (session_id()) {
			$_SESSION[$name] = $value;
		}
		$_COOKIE[$name] = $cookiev;
	}
}

/**
 *
 * Clears a cookie
 * @param string $name
 */
function clearNPGCookie($name) {
	if (isset($_COOKIE[$name])) {
		setNPGCookie($name, 'null', -368000, ['secure' => TRUE]);
		setNPGCookie($name, 'null', -368000, ['secure' => FALSE]);
	}
}

/**
 * test for serialized array string
 *
 * @param type string
 * @return boolean
 */
function is_serialized($data) {
	// if it isn't a string, it isn't serialized
	if (!is_string($data))
		return false;
	$data = trim($data);
	if ('N;' == $data)
		return true;
	if (preg_match('/^([adObis]):/', $data, $badions)) {
		switch ($badions[1]) {
			case 'a' :
			case 'C':
			case 'O' :
			case 'R':
			case 'S' :
			case 's' :
			case 'U':
				if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data)) {
					return true;
				}
				break;
			case 'b' :
			case 'i' :
			case 'd' :
				if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/i", $data)) {
					return true;
				}
				break;
		}
	}
	return false;
}

/**
 * if $string is an serialzied array it is unserialized otherwise an appropriate array is returned
 *
 * @param string $string
 *
 * @return array
 */
function getSerializedArray($string) {
	if (is_array($string)) {
		return $string;
	}
	if (is_null($string)) {
		return array();
	}
	if (is_serialized($string)) {
		$strings = unserialize($string, ['allowed_classes' => false]);
		if (is_array($strings)) {
			return $strings;
		}
		return array($strings);
	}
	return array($string);
}

/**
 * returns the owner fields of an option. Typically used when the option is set
 * to its default value
 *
 * @return array
 */
function getOptionOwner() {
	$creator = NULL;
	$bt = debug_backtrace();
	$b = reset($bt); // this function
	$b = next($bt); //the setOption... function
	//$b now has the calling file/line# of the setOption... function
	$creator = replaceScriptPath($b['file']);
	$matches = explode('/', $creator);
	if (array_pop($matches) == 'themeoptions.php') {
		$theme = array_pop($matches);
		$creator = THEMEFOLDER . '/' . $theme . '/themeoptions.php';
	} else if ($matches[0] == THEMEFOLDER) {
		$theme = $matches[1];
	} else {
		$theme = '';
	}
	if (isset($b['line'])) {
		$creator .= '[' . $b['line'] . ']';
	}
	return array($theme, $creator);
}

/**
 * Sets the default value of an option.
 *
 * If the option is NULL or has never been set it is set to the value passed
 *
 * @param string $key the option name
 * @param mixed $default the value to be used as the default
 */
function setOptionDefault($key, $default, $theme = NULL, $creator = NULL) {
	global $_options;

	if (is_null($creator)) {
		list($theme, $creator) = getOptionOwner();
	}
	$sql = 'INSERT INTO ' . prefix('options') . ' (`name`, `value`, `ownerid`, `theme`, `creator`) VALUES (' . db_quote($key) . ',';
	if (is_null($default)) {
		$value = 'NULL';
	} else {
		$value = $default;
		if (is_bool($value)) {
			$default = $value = (int) $default;
		}
		$value = db_quote($value);
	}
	$sql .= $value . ',0,' . db_quote($theme) . ',' . db_quote($creator) . ')' .
					' ON DUPLICATE KEY UPDATE `theme`=' . db_quote($theme) . ', `creator`=' . db_quote($creator) . ';';
	query($sql, false);

	if (!isset($_options[strtolower($key)]) || is_null($_options[strtolower($key)])) {
		$_options[strtolower($key)] = $default;
	}
}

/**
 * Loads option table with album/theme options
 *
 * @param int $albumid
 * @param string $theme
 */
function loadLocalOptions($albumid, $theme) {
	global $_options, $_conf_vars;
	//start with the config file
	$options = $_conf_vars;
	//raw theme options Order is so that Album theme options will override simple theme options
	$sql = "SELECT LCASE(`name`) as name, `value`, `ownerid` FROM " . prefix('options') . ' WHERE `theme`=' . db_quote($theme) . ' AND (`ownerid`=0 OR `ownerid`=' . $albumid . ') ORDER BY `ownerid` ASC';
	$optionlist = query_full_array($sql, false);
	if (!empty($optionlist)) {
		foreach ($optionlist as $option) {
			$_options[$option['name']] = $option['value'];
		}
	}
}

/**
 *
 * @global array $_options
 * @param string $key
 */
function purgeOption($key, $theme = NULL) {
	global $_options;
	unset($_options[strtolower($key)]);
	$sql = 'DELETE FROM ' . prefix('options') . ' WHERE `name`=' . db_quote($key);
	if ($theme) {
		$sql .= ' AND `theme`=' . db_quote($theme);
	}
	query($sql, false);
}

/**
 * Returns the option array
 *
 * @return array
 */
function getOptionList() {
	global $_options;
	return $_options;
}

/**
 * Cloned installations may be using symLinks to the "standard" script files.
 * This can cause a problem examining the "path" to the file. __FILE__ and other functions will
 * return the actual path to the file, e.g. the path to the parent installation of
 * a clone. SERVERPATH is the path to the clone installation and will not be the same
 * as the script path to the symLinked files.
 *
 * This function deals with the situation and returns the relative path
 *
 * @param string $file
 * @return string the relative path to the file
 */
function replaceScriptPath($file, $replace = '') {
	$file = str_replace('\\', '/', $file);
	return trim(preg_replace('~^(' . SCRIPTPATH . '|' . SERVERPATH . ')~i', $replace, $file), '/');
}

/**
 * Returns true if the file has the dynamic album suffix
 *
 * @param string $path
 * @return bool
 */
function hasDynamicAlbumSuffix($path) {
	global $_albumHandlers;
	return array_key_exists(getSuffix($path), $_albumHandlers);
}

/**
 * Handles the special cases of album/image[rewrite_suffix]
 *
 * Separates the image part from the album if it is an image reference
 * Strips off the mod_rewrite_suffix if present
 * Handles dynamic album names that do not have the .alb suffix appended
 *
 * @param string $albumvar	$_GET index for "albums"
 * @param string $imagevar	$_GET index for "images"
 */
function rewrite_get_album_image($albumvar, $imagevar) {
	global $_rewritten, $_albumHandlers;
	$ralbum = isset($_GET[$albumvar]) ? trim(sanitize($_GET[$albumvar]), '/') : NULL;
	$rimage = isset($_GET[$imagevar]) ? sanitize($_GET[$imagevar]) : NULL;
	//	we assume that everything is correct if rewrite rules were not applied
	if ($_rewritten) {
		if (!empty($ralbum) && empty($rimage)) { //	rewrite rules never set the image part!
			if (!is_dir(internalToFilesystem(getAlbumFolder(SERVERPATH) . $ralbum))) {
				if (RW_SUFFIX && preg_match('|^(.*)' . preg_quote(RW_SUFFIX) . '$|', $ralbum, $matches)) {
					//has an RW_SUFFIX attached
					$rimage = basename($matches[1]);
					$ralbum = trim(dirname($matches[1]), '/');
				} else { //	have to figure it out
					if (Gallery::imageObjectClass($ralbum)) {
						//	it is an image request
						$rimage = basename($ralbum);
						$ralbum = trim(dirname($ralbum), '/');
					}
				}
			}
		}
		if (empty($ralbum)) {
			unset($_GET[$albumvar]);
		} else {
			$_GET[$albumvar] = $ralbum;
		}
		if (empty($rimage)) {
			unset($_GET[$imagevar]);
		} else {
			$_GET[$imagevar] = $rimage;
		}
	}
	return array($ralbum, $rimage);
}

/**
 * Returns the path of an image for uses in caching it
 * NOTE: character set if for the filesystem
 *
 * @param string $album album folder
 * @param string $image image file name
 * @param array $args cropping arguments
 * @return string
 */
function getImageCacheFilename($album8, $image8, $args, $suffix = NULL) {
	global $_cachefileSuffix;
	// this function works in FILESYSTEM_CHARSET, so convert the file names
	$album = internalToFilesystem($album8);
	if (is_array($image8)) {
		$image8 = $image8['name'];
	}
	$image = stripSuffix(internalToFilesystem($image8));

	if (!$suffix) {
		if (IMAGE_CACHE_SUFFIX) {
			$suffix = IMAGE_CACHE_SUFFIX;
		} else {
			$sfx = strtoupper(getSuffix($image8));
			if (isset($cachefileSuffix[$sfx]) && $_cachefileSuffix[$sfx]) {
				$suffix = $_cachefileSuffix[$sfx];
			} else {
				$suffix = 'jpg';
			}
		}
	}
	// Set default variable values.
	$postfix = getImageCachePostfix($args);

	if (getOption('obfuscate_cache')) {
		$result = '/' . $album . '/' . sha1($image . HASH_SEED . $postfix) . '.' . $image . $postfix . '.' . $suffix;
	} else {
		$result = '/' . $album . '/' . $image . $postfix . '.' . $suffix;
	}

	return $result;
}

/**
 * Returns the crop/sizing string to postfix to a cache image
 *
 * @param array $args cropping arguments
 * @return string
 */
function getImageCachePostfix($args) {
	$size = $width = $height = $cw = $ch = $ch = $cx = $cy = $quality = $thumb = $crop = $WM = $adminrequest = $effects = NULL;
	extract($args);

	$postfix_string = ($size ? "_s$size" : "") .
					($width ? "_w$width" : "") .
					($height ? "_h$height" : "") .
					($cw ? "_cw$cw" : "") .
					($ch ? "_ch$ch" : "") .
					(is_numeric($cx) ? "_cx$cx" : "") .
					(is_numeric($cy) ? "_cy$cy" : "") .
					($thumb ? '_thumb' : '') .
					($adminrequest ? '_admin' : '') .
					(($WM && $WM != NO_WATERMARK) ? '_' . $WM : '') .
					($effects ? '_' . $effects : '');
	return $postfix_string;
}

/**
 * Validates and edits image size/cropping parameters
 *
 * @param array $args cropping arguments
 * @return array
 */
function getImageParameters($args, $album = NULL) {
	$size = $width = $height = $cw = $ch = $ch = $cx = $cy = $quality = $thumb = $crop = $WM = $adminrequest = $effects = NULL;

	$thumb_crop = getOption('thumb_crop');
	$thumb_size = getOption('thumb_size');
	$thumb_crop_width = getOption('thumb_crop_width');
	$thumb_crop_height = getOption('thumb_crop_height');
	$image_default_size = getOption('image_size');
	$quality = getOption('image_quality');
	// Set up the parameters
	$thumb = $crop = false;
	extract($args);

	switch ($size) {
		case 'thumb':
			$thumb = true;
			if ($thumb_crop) {
				$cw = (int) $thumb_crop_width;
				$ch = (int) $thumb_crop_height;
			}
			$size = (int) round($thumb_size);
			break;
		case 'default':
			$size = $image_default_size;
			break;
		case 0:
		default:
			if (empty($size) || !is_numeric($size)) {
				$size = false; // 0 isn't a valid size anyway, so this is OK.
			} else {
				$size = (int) round($size);
			}
			break;
	}

	if (is_numeric($width)) {
		$width = (int) round($width);
	} else {
		$width = false;
	}
	if (is_numeric($height)) {
		$height = (int) round($height);
	} else {
		$height = false;
	}
	if (empty($size) && $width == $height) {
		//square image
		$size = $height;
		$width = $height = false;
	}
	if (is_numeric($cw)) {
		$cw = (int) round($cw);
	} else {
		$cw = false;
	}
	if (is_numeric($ch)) {
		$ch = (int) round($ch);
	} else {
		$ch = false;
	}
	if (is_numeric($quality)) {
		$quality = (int) round($quality);
	} else {
		$quality = false;
	}
	if (empty($quality)) {
		if ($thumb) {
			$quality = (int) round(getOption('thumb_quality'));
		} else {
			$quality = (int) round(getOption('image_quality'));
		}
	}


	if (!is_null($cx)) {
		$cx = (int) round($cx);
	}
	if (!is_null($cy)) {
		$cy = (int) round($cy);
	}

	if (!empty($cw) || !empty($ch)) {
		$crop = true;
	}
	if (is_null($effects)) {
		if ($thumb) {
			if (getOption('thumb_gray')) {
				$effects = 'gray';
			}
		} else {
			if (getOption('image_gray')) {
				$effects = 'gray';
			}
		}
	}
	if (empty($WM)) {
		if (!$thumb) {
			if (!empty($album)) {
				$WM = getAlbumInherited($album, 'watermark', $id);
			}
			if (empty($WM)) {
				$WM = IMAGE_WATERMARK;
			}
		}
	}
	// Return an array of parameters used in image conversion.
	$args = array('size' => $size, 'width' => $width, 'height' => $height, 'cw' => $cw, 'ch' => $ch, 'cx' => $cx, 'cy' => $cy, 'quality' => $quality, 'crop' => $crop, 'thumb' => $thumb, 'WM' => $WM, 'adminrequest' => $adminrequest, 'effects' => $effects);
	return $args;
}

/**
 * generates the image processor protection check code
 *
 * @param array $args
 * @return string
 */
function ipProtectTag($album, $image, $args) {
	$valid = array(
			'size',
			'width',
			'height',
			'cw',
			'ch',
			'cx',
			'cy',
			'quality',
			'crop',
			'thumb',
			'WM',
			'effects'
	);

	if (is_array($image)) {
		$image = $image['name'];
	}
	$key = '';
	foreach ($valid as $index) {
		if (isset($args[$index]) && $args[$index]) {
			$key .= $index . $args[$index];
		}
	}

	$tag = sha1(HASH_SEED . $album . $image . $key);
	return $tag;
}

/**
 * forms the i.php parameter list for an image.
 *
 * @param array $args
 * @param string $album the album name
 * @param string $image the image name
 * @return string
 */
function getImageProcessorURI($args, $album, $image, $suffix = NULL) {
	$size = $width = $height = $cw = $ch = $ch = $cx = $cy = $quality = $thumb = $crop = $WM = $adminrequest = $effects = NULL;
	extract($args);

	if (!$s = $suffix) {
		if (MOD_REWRITE) {
			$s = getSuffix(getImageCacheFilename($album, $image, $args));
		} else {
			$s = 'php';
		}
	}
	$uri = WEBPATH . '/' . CORE_FOLDER . '/i.' . $s . '?a=' . pathurlencode($album);

	if (is_array($image)) {
		$uri .= '&i=' . urlencode($image['name']) . '&z=' . ($z = pathurlencode($image['source']));
	} else {
		$uri .= '&i=' . urlencode($image);
	}
	if (!empty($size)) {
		$uri .= '&s=' . (int) $size;
	}
	if ($width) {
		$uri .= '&w=' . (int) $width;
	}
	if ($height) {
		$uri .= '&h=' . (int) $height;
	}
	if (!is_null($cw)) {
		$uri .= '&cw=' . (int) $cw;
	}
	if (!is_null($ch)) {
		$uri .= '&ch=' . (int) $ch;
	}
	if (!is_null($cx)) {
		$uri .= '&cx=' . (int) $cx;
	}
	if (!is_null($cy)) {
		$uri .= '&cy=' . (int) $cy;
	}
	if ($quality) {
		$uri .= '&q=' . (int) $quality;
	}
	if ($crop) {
		$uri .= '&c=' . (int) $crop;
	}
	if ($thumb) {
		$uri .= '&t=' . (int) $thumb;
	}
	if ($WM) {
		$uri .= '&wmk=' . $WM;
	}
	if ($adminrequest) {
		$uri .= '&admin=1';
	}
	if ($effects) {
		$uri .= '&effects=' . $effects;
	}
	if ($suffix) {
		$uri .= '&suffix=' . $suffix;
		$args['suffix'] = $suffix;
	}
	if (isset($z)) {
		$args['z'] = $z;
	}

	$uri .= '&ipcheck=' . ipProtectTag(internalToFilesystem($album), internalToFilesystem($image), $args) . '&cached=' . rand();

	$uri = npgFilters::apply('image_processor_uri', $uri, $args, $album, $image);

	return $uri;
}

/**
 * Extract the image parameters from the input variables
 * @param array $set
 * @return array
 */
function getImageArgs($set) {
	$args = array();
	if (isset($set['s'])) { //0
		if (is_numeric($s = $set['s'])) {
			if ($s) {
				$args['size'] = (int) abs($s);
			}
		} else {
			$args['size'] = sanitize($set['s']);
		}
	} else {
		if (!isset($set['w']) && !isset($set['h'])) {
			$args['size'] = 3000; // you didn't specify a size so we arbitrarily pick one
		}
	}
	$i = 0;
	if (isset($set['w'])) { //1
		$args['width'] = (int) abs(sanitize_numeric($set['w']));
	}
	if (isset($set['h'])) { //2
		$args['height'] = (int) abs(sanitize_numeric($set['h']));
	}
	if (isset($set['cw'])) { //3
		$args['cw'] = (int) sanitize_numeric(($set['cw']));
	}
	if (isset($set['ch'])) { //4
		$args['ch'] = (int) sanitize_numeric($set['ch']);
	}
	if (isset($set['cx'])) { //5
		$args['cx'] = (int) sanitize_numeric($set['cx']);
	}
	if (isset($set['cy'])) { //6
		$args['cy'] = (int) sanitize_numeric($set['cy']);
	}
	if (isset($set['q'])) { //7
		$args['quality'] = (int) sanitize_numeric($set['q']);
	}
	if (isset($set['c'])) {// 9
		$args['crop'] = (int) sanitize($set['c']);
	}
	if (isset($set['t'])) { //10
		$args['thumb'] = (int) sanitize($set['t']);
	}
	if (isset($set['wmk']) && !isset($_GET['admin'])) {
		$args['WM'] = sanitize($set['wmk']);
	}
	$args['adminrequest'] = (bool) isset($_GET['admin']);

	if (isset($set['effects'])) { //13
		$args['effects'] = sanitize($set['effects']);
	}

	return $args;
}

/**
 *
 * Returns an URI to the image:
 *
 * 	If the image is not cached, the uri will be to the image processor
 * 	If the image is cached then the uri will depend on the site option for
 * 	cache serving. If the site is set for open cache the uri will point to
 * 	the cached image. If the site is set for protected cache the uri will
 * 	point to the image processor (which will serve the image from the cache.)
 * 	NOTE: this latter implies added overhead for each and every image fetch!
 *
 * @param array $args
 * @param string $album the album name
 * @param string $image the image name
 * @param int $mitme mtime of the image
 * @return string
 */
function getImageURI($args, $album, $image, $mtime, $suffix = NULL) {
	$cachefilename = getImageCacheFilename($album, $image, $args, $suffix);
	if (OPEN_IMAGE_CACHE && file_exists(SERVERCACHE . $cachefilename)) {
		if (($cachefiletime = filemtime(SERVERCACHE . $cachefilename)) >= $mtime) {
			return WEBPATH . '/' . CACHEFOLDER . pathurlencode(imgSrcURI($cachefilename)) . '?cached=' . $cachefiletime;
		}
	}
	return getImageProcessorURI($args, $album, $image, $suffix);
}

/**
 * Returns an img src URI encoded based on the OS of the server
 *
 * @param string $uri uri in FILESYSTEM_CHARSET encoding
 * @return string
 */
function imgSrcURI($uri) {
	if (UTF8_IMAGE_URI) {
		$uri = filesystemToInternal($uri);
	}
	return $uri;
}

/**
 *
 * Returns an array of html tags allowed
 * @param string $which either 'allowed_tags' or 'style_tags' depending on which is wanted.
 */
function getAllowedTags($which) {
	global $_user_tags, $_style_tags, $_default_tags;
	switch ($which) {
		case 'style_tags':
			if (is_null($_style_tags)) {
				$allowed_tags = parseAllowedTags(getOption('style_tags'));
				if (!is_array($allowed_tags)) { // Nobody should be messing with this option! but be safe.
					debugLog(sprintf(gettext('Style tags parse error: %1$s'), $allowed_tags));
					$allowed_tags = array();
				}
				$_style_tags = $allowed_tags;
			}
			return $_style_tags;
			break;
		case 'allowed_tags':
			if (!empty(getOption('allowed_tags'))) {
				if (is_null($_user_tags)) {
					$allowed_tags = parseAllowedTags(getOption('allowed_tags'));
					if (!is_array($allowed_tags)) { // revert to the default
						debugLog(sprintf(gettext('Allowed tags parse error: %1$s'), $allowed_tags));
						$allowed_tags = getAllowedTags('allowed_tags_default');
					}
					$_user_tags = $allowed_tags;
					return $_user_tags;
				}
				break;
			}
		default:
			if (is_null($_default_tags)) {
				$allowed_tags = parseAllowedTags(getOption('allowed_tags_default'));
				if (!is_array($allowed_tags)) { // someone has screwed with the 'allowed_tags' option row in the database, but better safe than sorry
					debugLog(sprintf(gettext('Allowed tags default parse error: %1$s'), $allowed_tags));
					$allowed_tags = array();
				}
				$_default_tags = $allowed_tags;
			}
			return $_default_tags;
			break;
	}
	return array();
}

/**
 * parses a query string WITHOUT url decoding it!
 * @param string $str
 */
function parse_query($str) {
	$pairs = explode('&', $str);
	$params = array();
	foreach ($pairs as $pair) {
		if (strpos($pair, '=') === false) {
			$params[$pair] = NULL;
		} else {
			list($name, $value) = explode('=', $pair, 2);
			$params[$name] = $value;
		}
	}
	return $params;
}

/**
 * Builds a url from parts
 * @param array $parts
 * @return string
 */
function build_url($parts) {
	$u = '';
	if (isset($parts['scheme'])) {
		$u .= $parts['scheme'] . '://';
	}
	if (isset($parts['host'])) {
		$u .= $parts['host'];
	}
	if (isset($parts['port'])) {
		$u .= ':' . $parts['port'];
	}
	if (isset($parts['path'])) {
		if (empty($u)) {
			$u = $parts['path'];
		} else {
			$u .= '/' . ltrim($parts['path'], '/');
		}
	}
	if (isset($parts['query']) && $parts['query']) {
		$u .= '?' . $parts['query'];
	}
	if (isset($parts['fragment '])) {
		$u .= '#' . $parts['fragment '];
	}
	return $u;
}

/**
 * UTF-8 aware parse_url() replacement.
 *
 * @return array
 */
function mb_parse_url($url) {
	$enc_url = preg_replace_callback('%[^:/@?&=#]+%usD', function ($matches) {
		return urlencode($matches[0]);
	}, $url);

	$parts = parse_url($enc_url);
	if ($parts === false) {
		if (TEST_RELEASE) {
			debugLogBacktrace('Malformed URL: ' . $url);
		}
		return array('path' => '/');
	}

	foreach ($parts as $name => $value) {
		$parts[$name] = urldecode($value);
	}

	return $parts;
}

/**
 * Returns the fully qualified path to the album folders
 *
 * @param string $root the base from whence the path dereives
 * @return sting
 */
function getAlbumFolder($root = SERVERPATH) {
	static $_album_folder;
	if (is_null($_album_folder)) {
		if (empty(getOption('album_folder'))) {
			setOption('album_folder', $_album_folder = '/' . ALBUMFOLDER . '/');
		} else {
			$_album_folder = str_replace('\\', '/', getOption('album_folder'));
			if (substr($_album_folder, -1) != '/') {
				$_album_folder .= '/';
			}
		}
	}
	$root = str_replace('\\', '/', $root);
	switch (getOption('album_folder_class')) {
		default:
		case 'std':
			return $root . $_album_folder;
		case 'in_webpath':
			if (WEBPATH) { // strip off the WEBPATH
				$pos = strrpos($root, WEBPATH);
				if ($pos !== false) {
					$root = substr_replace($root, '', $pos, strlen(WEBPATH));
				}
				if ($root == '/') {
					$root = '';
				}
			}
			return $root . $_album_folder;
		case 'external':
			return $_album_folder;
	}
}

/**
 * Rolls a log over if it has grown too large.
 *
 * @param string $log
 */
function switchLog($log) {
	$dir = getcwd();
	chdir(SERVERPATH . '/' . DATA_FOLDER);
	$list = safe_glob($log . '-*.log');
	$counter = count($list) + 1;

	chdir($dir);
	copy(SERVERPATH . '/' . DATA_FOLDER . '/' . $log . '.log', SERVERPATH . '/' . DATA_FOLDER . '/' . $log . '-' . $counter . '.log');
	if (getOption($log . '_log_mail')) {
		npgFunctions::mail(sprintf(gettext('%s log size limit exceeded'), $log), sprintf(gettext('The %1$s log has exceeded its size limit and has been renamed to %2$s.'), $log, $log . '-' . $counter . '.log'));
	}
}

/**
 * Tool to log execution times of script bits
 *
 * @param string $point location identifier
 */
function instrument($point) {
	static $_run_timer;
	$now = microtime(true);
	if (empty($_run_timer)) {
		$delta = '';
	} else {
		$delta = ' (' . ($now - $_run_timer) . ')';
	}
	$_run_timer = microtime(true);
	debugLogBacktrace($point . ' ' . $now . $delta);
}

/** getAlbumArray - returns an array of folder names corresponding to the given album string.
 * @param string $albumstring is the path to the album as a string. Ex: album/subalbum/my-album
 * @param string $includepaths is a boolean whether or not to include the full path to the album
 *    in each item of the array. Ex: when $includepaths==false, the above array would be
 *    ['album', 'subalbum', 'my-album'], and with $includepaths==true,
 *    ['album', 'album/subalbum', 'album/subalbum/my-album']
 *  @return array
 */
function getAlbumArray($albumstring, $includepaths = false) {
	$albums = explode('/', $albumstring);
	if ($includepaths) {
		$albumPaths = array();
		$next = '';
		foreach ($albums as $album) {
			$albumPaths[] = $next . $album;
			$next = $next . $album . '/';
		}
		return $albumPaths;
	} else {
		return $albums;
	}
}

/**
 * returns the non-empty value of $field from the album or one of its parents
 *
 * @param string $folder the album name
 * @param string $field the desired field name
 * @param int $id will be set to the album `id` of the album which has the non-empty field
 * @return string
 */
function getAlbumInherited($folder, $field, &$id) {
	if ($folder) {
		$folders = explode('/', filesystemToInternal($folder));
		$album = array_shift($folders);
		$like = ' LIKE ' . db_quote(db_LIKE_escape($album));
		foreach ($folders as $folder) {
			$album .= '/' . $folder;
			$like .= ' OR `folder` LIKE ' . db_quote(db_LIKE_escape($album));
		}
		$sql = 'SELECT `id`, `' . $field . '` FROM ' . prefix('albums') . ' WHERE `folder`' . $like;
		$result = query_full_array($sql);
		if (!is_array($result))
			return '';
		while (count($result) > 0) {
			$try = array_pop($result);
			if (!empty($try[$field])) {
				$id = $try['id'];
				return $try[$field];
			}
		}
	}
	return '';
}

/**
 * primitive theme setup for image handling scripts
 *
 * we need to conserve memory so loading the classes is out of the question.
 *
 * @param string $album
 * @return string
 */
function imageThemeSetup($album) {
	// we need to conserve memory in i.php so loading the classes is out of the question.
	$id = 0;
	$theme = getAlbumInherited(filesystemToInternal($album), 'album_theme', $id);
	if (empty($theme)) {
		$galleryoptions = getSerializedArray(getOption('gallery_data'));
		$theme = isset($galleryoptions['current_theme']) ? $galleryoptions['current_theme'] : NULL;
	}
	loadLocalOptions($id, $theme);
	return $theme;
}

/**
 * Returns the path to a watermark
 *
 * @param string $wm watermark name
 * @return string
 */
function getWatermarkPath($wm) {
	foreach (array(USER_PLUGIN_SERVERPATH, CORE_SERVERPATH) as $loc) {
		$path = $loc . 'watermarks/' . internalToFilesystem($wm) . '.png';
		if (file_exists($path)) {
			return $path;
		}
	}
	return FALSE;
}

/**
 *
 * Returns the script requesting URI.
 * 	Uses $_SERVER[REQUEST_URI] if it exists, otherwise it concocts the URI from
 * 	$_SERVER[SCRIPT_NAME] and $_SERVER[QUERY_STRING]
 *
 * @param bool $decode Set true to urldecode the uri
 * @return string
 */
function getRequestURI($decode = true) {
	if (array_key_exists('REQUEST_URI', $_SERVER)) {
		$uri = sanitize(str_replace('\\', '/', $_SERVER['REQUEST_URI']));
		preg_match('|^(http[s]*\://[a-zA-Z0-9\-\.]+/?)*(.*)$|xis', $uri, $matches);
		$uri = $matches[2];
		if (!empty($matches[1])) {
			$uri = '/' . $uri;
		}
	} else {
		if (isset($_SERVER['SCRIPT_NAME'])) {
			$uri = sanitize(str_replace('\\', '/', $_SERVER['SCRIPT_NAME']));
		} else {
			$uri = NULL;
		}
	}
	if ($decode) {
		$uri = sanitize(urldecode($uri));
	}
	return $uri;
}

/**
 * Provide an alternative to glob which does not return filenames with accented characters in them
 *
 * NOTE: this function ignores "hidden" files whose name starts with a period!
 *
 * @param string $pattern the 'pattern' for matching files
 * @param bit $flags glob 'flags'
 */
function safe_glob($pattern, $flags = 0) {
	$split = explode('/', $pattern);
	$match = '/^' . strtr(addcslashes(array_pop($split), '\\.+^$(){}=!<>|[]'), array('*' => '.*', '?' => '.?')) . '$/i';

	$path_return = $path = implode('/', $split);
	if (empty($path)) {
		$path = '.';
	} else {
		$path_return = $path_return . '/';
	}

	if (!is_dir($path))
		return array();
	if (($dir = opendir($path)) !== false) {
		$glob = array();
		while (($file = readdir($dir)) !== false) {
			if (preg_match($match, $file) && $file[0] != '.') {
				if (is_dir("$path/$file")) {
					if ($flags & GLOB_MARK) {
						$file .= '/';
					}
					$glob[] = $path_return . $file;
				} else if (!is_dir("$path/$file") && !($flags & GLOB_ONLYDIR)) {
					$glob[] = $path_return . $file;
				}
			}
		}
		closedir($dir);
		if (!($flags & GLOB_NOSORT))
			sort($glob);
		return $glob;
	} else {
		return array();
	}
}

/**
 *
 * Check to see if the setup script needs to be run
 */
function checkInstall() {
	if (OFFSET_PATH != 2) {
		$i = getOption('netphotographics_install');
		if ($i != serialize(installSignature())) {
			_setup((int) ($i === NULL));
		}
	}
}

/**
 * registers a request to have setup run
 * @param string $whom the requestor
 * @param string $addl additional information for request message
 *
 * @author Stephen Billard
 * @Copyright 2015 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 */
function requestSetup($whom, $addl = NULL) {
	$sig = getSerializedArray(getOption('netphotographics_install'));
	$sig['REQUESTS'][$whom] = $whom;
	if (!is_null($addl)) {
		$sig['REQUESTS'][$whom] .= ' (' . $addl . ')';
	}

	setOption('netphotographics_install', serialize($sig));
}

/**
 * Force a setup to get the configuration right
 *
 * @param int $action if positive the setup is mandatory
 *
 * @author Stephen Billard
 * @Copyright 2015 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 */
function _setup($action) {
	require_once(__DIR__ . '/reconfigure.php');
	reconfigureAction($action);
}

/**
 *
 * Computes the "installation signature" of the install
 * @return string
 */
function installSignature() {
	$folder = __DIR__;
	$testFiles = array(
			'template-functions.php' => filesize($folder . '/template-functions.php'),
			'lib-filter.php' => filesize($folder . '/lib-filter.php'),
			'lib-auth.php' => filesize($folder . '/lib-auth.php'),
			'lib-utf8.php' => filesize($folder . '/lib-utf8.php'),
			'functions.php' => filesize($folder . '/functions.php'),
			'functions-basic.php' => filesize($folder . '/functions-basic.php'),
			'lib-controller.php' => filesize($folder . '/lib-controller.php'),
			'lib-image.php' => filesize($folder . '/lib-image.php'),
			'databaseTemplate' => filesize($folder . '/databaseTemplate')
	);

	$dbs = db_software();
	$version = NETPHOTOGRAPHICS_VERSION;
	$i = strpos($version, '-');
	if ($i !== false) {
		$version = substr($version, 0, $i);
	}
	return array_merge($testFiles, array(
			'NETPHOTOGRAPHICS' => $version,
			'FOLDER' => dirname(__DIR__),
					)
	);
}

/**
 * centralize evaluating the config file
 * @global type $_conf_vars
 * @param type $from the config file name
 */
function getConfig($from = DATA_FOLDER . '/' . CONFIGFILE) {
	global $_conf_vars;
	eval('?>' . file_get_contents(SERVERPATH . '/' . $from));
	if (isset($conf)) {
		return $conf;
	}
	return $_conf_vars;
}

function primeOptions() {
	global $_options, $_conf_vars;
	$_options = array();
	foreach ($_conf_vars as $name => $value) {
		$_options[strtolower($name)] = $value;
	}
	$sql = "SELECT `name`, `value` FROM " . prefix('options') . ' WHERE `theme`="" AND `ownerid`=0 ORDER BY `name`';
	$rslt = query($sql, false);
	if ($rslt) {
		while ($option = db_fetch_assoc($rslt)) {
			$_options[strtolower($option['name'])] = $option['value'];
		}
	}
}

/**
 * Get a option stored in the database.
 * This function reads the options only once, in order to improve performance.
 * @param string $key the name of the option.
 */
function getOption($key) {
	global $_options;
	if (isset($_options[$key = strtolower($key)])) {
		return $_options[$key];
	} else {
		return NULL;
	}
}

/**
 * Returns a list of options that match $pattern
 * @param string $pattern
 * @return array
 */
function getOptionsLike($pattern) {
	global $_options;
	$result = array();
	foreach ($_options as $key => $value) {
		if (preg_match('~' . $pattern . '.*~i', $key)) {
			$result[$key] = $value;
		}
	}

	return $result;
}

/**
 * Stores an option value.
 *
 * @param string $key name of the option.
 * @param mixed $value new value of the option.
 * @param bool $persistent set to false if the option is stored in memory only
 * otherwise it is preserved in the database
 */
function setOption($key, $value, $persistent = true) {
	global $_options, $_conf_options_associations, $_conf_vars, $_configMutex;
	$_options[$keylc = strtolower($key)] = $value;
	if ($persistent) {
		list($theme, $creator) = getOptionOwner();
		if (is_null($value)) {
			$v = 'NULL';
		} else {
			if (is_bool($value)) {
				$value = (int) $value;
			}
			$v = db_quote($value);
		}
		$sql = 'INSERT INTO ' . prefix('options') . ' (`name`,`value`,`ownerid`,`theme`,`creator`) VALUES (' . db_quote($key) . ',' . $v . ',0,' . db_quote($theme) . ',' . db_quote($creator) . ') ON DUPLICATE KEY UPDATE `value`=' . $v;
		$result = query($sql, false);
		if ($result) {
			if (array_key_exists($keylc, $_conf_options_associations)) {
				$configKey = $_conf_options_associations[$keylc];
				if ($_conf_vars[$configKey] !== $value) {
					//	it is stored in the config file, update that too
					require_once(CORE_SERVERPATH . 'lib-config.php');
					$_configMutex->lock();
					$_config_contents = file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE);
					$_config_contents = configFile::update($configKey, $value, $_config_contents);
					configFile::store($_config_contents);
					$_configMutex->unlock();
				}
			}
		}
		return $result;
	} else {
		return true;
	}
}

//	PHP fallback functions

if (!function_exists('str_starts_with')) {

	function str_starts_with($haystack, $needle) {
		return (string) $needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
	}

}
if (!function_exists('str_ends_with')) {

	function str_ends_with($haystack, $needle) {
		return $needle !== '' && substr($haystack, -strlen($needle)) === (string) $needle;
	}

}
if (!function_exists('str_contains')) {

	function str_contains($haystack, $needle) {
		return $needle !== '' && mb_strpos($haystack, $needle) !== false;
	}

}

if (!function_exists('ctype_digit')) {

	function ctype_digit($digit) {
		return !preg_match('`[^0-9]`', $digit);
	}

}
if (!function_exists('ctype_xdigit')) {

	function ctype_xdigit($hex) {
		return !preg_match('`[^a-fA-F0-9]`', $hex);
	}

}

if (!function_exists('gmp_gcd')) {

	function gmp_gcd($x, $y) {
		if ($x == 0 || $y == 0) {
			return 1;
		}
		if ($x < $y) {
			list($y, $x) = array($x, $y);
		}
		if ($x % $y == 0) {
			return $y;
		} else {
			return gmp_gcd($y, $x % $y);
		}
	}

}

if (!function_exists("json_encode")) {
	// load the drop-in replacement library
	require_once(__DIR__ . '/lib-json.php');
}

if (!function_exists("gettext")) {
	require_once(__DIR__ . '/php-gettext/gettext.inc');
}
